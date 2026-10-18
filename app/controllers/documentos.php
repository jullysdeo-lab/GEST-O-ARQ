<?php
declare(strict_types=1);

const DOC_STATUS = [
    'rascunho' => 'Rascunho',
    'enviado' => 'Aguardando assinatura',
    'assinado' => 'Assinado',
];

function carregar_documento(int $id): array
{
    $d = one(
        'SELECT d.*, c.nome AS cliente_nome, c.email AS cliente_email, p.codigo AS projeto_codigo, p.nome AS projeto_nome, o.numero AS orcamento_numero
         FROM documento d
         JOIN cliente c ON c.id = d.cliente_id
         LEFT JOIN projeto p ON p.id = d.projeto_id
         LEFT JOIN orcamento o ON o.id = d.orcamento_id
         WHERE d.id = ? AND d.escritorio_id = ?',
        [$id, eid()]
    );
    if (!$d) {
        not_found();
    }
    return $d;
}

function index(): void
{
    require_perm('documentos');
    $tipo = (string) input('tipo', '');
    $status = (string) input('status', '');
    $where = 'd.escritorio_id = ?';
    $params = [eid()];
    if (isset(TIPOS_DOCUMENTO[$tipo])) {
        $where .= ' AND d.tipo = ?';
        $params[] = $tipo;
    }
    if (isset(DOC_STATUS[$status])) {
        $where .= ' AND d.status = ?';
        $params[] = $status;
    }
    $lista = all(
        "SELECT d.id, d.tipo, d.titulo, d.status, d.criado_em, d.assinado_em, c.nome AS cliente_nome, p.codigo AS projeto_codigo
         FROM documento d JOIN cliente c ON c.id = d.cliente_id LEFT JOIN projeto p ON p.id = d.projeto_id
         WHERE $where ORDER BY d.id DESC",
        $params
    );
    view('documentos/index', ['titulo' => 'Documentos', 'aba' => 'lista', 'lista' => $lista, 'tipo' => $tipo, 'status' => $status]);
}

function novo(): void
{
    require_perm('documentos', NIVEL_EDITAR);
    view('documentos/novo', [
        'titulo' => 'Novo documento',
        'tipo' => (string) input('tipo', 'contrato'),
        'cliente_id' => input_int('cliente_id'),
        'projeto_id' => input_int('projeto_id'),
        'orcamento_id' => input_int('orcamento_id'),
        'clientes' => all('SELECT id, nome FROM cliente WHERE escritorio_id = ? ORDER BY nome', [eid()]),
        'projetos' => all('SELECT p.id, p.codigo, p.nome, p.cliente_id FROM projeto p WHERE p.escritorio_id = ? ORDER BY p.id DESC', [eid()]),
        'orcamentos' => all("SELECT id, numero, titulo, cliente_id FROM orcamento WHERE escritorio_id = ? AND status <> 'arquivado' ORDER BY id DESC", [eid()]),
    ]);
}

function criar(): void
{
    require_perm('documentos', NIVEL_EDITAR);
    $tipo = (string) input('tipo');
    if (!isset(TIPOS_DOCUMENTO[$tipo])) {
        flash('Escolha o tipo de documento.', 'erro');
        redirect('documentos/novo');
    }
    $projetoId = input_int('projeto_id') ?: null;
    $orcamentoId = input_int('orcamento_id') ?: null;
    $clienteId = input_int('cliente_id');

    // Projeto ou orçamento escolhidos definem o cliente.
    if ($projetoId) {
        $p = one('SELECT cliente_id, orcamento_id FROM projeto WHERE id = ? AND escritorio_id = ?', [$projetoId, eid()]);
        if (!$p) {
            not_found();
        }
        $clienteId = (int) $p['cliente_id'];
        $orcamentoId = $orcamentoId ?: ($p['orcamento_id'] ? (int) $p['orcamento_id'] : null);
    }
    if ($orcamentoId) {
        $o = one('SELECT cliente_id FROM orcamento WHERE id = ? AND escritorio_id = ?', [$orcamentoId, eid()]);
        if (!$o || ($clienteId && (int) $o['cliente_id'] !== $clienteId)) {
            flash('O orçamento escolhido é de outro cliente.', 'erro');
            redirect('documentos/novo', ['tipo' => $tipo]);
        }
        $clienteId = (int) $o['cliente_id'];
    }
    $cliente = one('SELECT nome FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()]);
    if (!$cliente) {
        flash('Escolha o cliente, o projeto ou o orçamento.', 'erro');
        redirect('documentos/novo', ['tipo' => $tipo]);
    }

    seed_documentos(eid());
    $modelo = (string) val('SELECT conteudo FROM documento_modelo WHERE escritorio_id = ? AND tipo = ?', [eid(), $tipo]);
    $conteudo = preencher_campos($modelo, contexto_documento($clienteId, $projetoId, $orcamentoId));

    $id = insert('documento', [
        'escritorio_id' => eid(),
        'tipo' => $tipo,
        'titulo' => TIPOS_DOCUMENTO[$tipo] . ' — ' . $cliente['nome'],
        'cliente_id' => $clienteId,
        'projeto_id' => $projetoId,
        'orcamento_id' => $orcamentoId,
        'conteudo' => $conteudo,
        'status' => 'rascunho',
        'criado_por' => current_user()['id'],
        'criado_em' => now(),
    ]);
    log_atividade('Gerou ' . mb_strtolower(TIPOS_DOCUMENTO[$tipo]) . ' para ' . $cliente['nome']);
    flash('Documento gerado. Revise os trechos marcados em amarelo antes de enviar.');
    redirect('documentos/editar', ['id' => $id]);
}

function editar(): void
{
    require_perm('documentos', NIVEL_EDITAR);
    $d = carregar_documento(input_int('id'));
    if ($d['status'] === 'assinado') {
        flash('Documento assinado não pode ser alterado.', 'erro');
        redirect('documentos/ver', ['id' => $d['id']]);
    }
    if (is_post()) {
        $conteudo = (string) ($_POST['conteudo'] ?? '');
        if (trim($conteudo) === '') {
            flash('O documento não pode ficar vazio.', 'erro');
            redirect('documentos/editar', ['id' => $d['id']]);
        }
        $dados = ['titulo' => (string) input('titulo') ?: $d['titulo'], 'conteudo' => $conteudo];
        if (input('regerar')) {
            $modelo = (string) val('SELECT conteudo FROM documento_modelo WHERE escritorio_id = ? AND tipo = ?', [eid(), $d['tipo']]);
            $dados['conteudo'] = preencher_campos($modelo, contexto_documento((int) $d['cliente_id'], $d['projeto_id'] ? (int) $d['projeto_id'] : null, $d['orcamento_id'] ? (int) $d['orcamento_id'] : null));
        }
        // Mudou o texto depois de enviado: volta a rascunho e invalida o link antigo.
        if ($d['status'] === 'enviado' && $dados['conteudo'] !== $d['conteudo']) {
            $dados['status'] = 'rascunho';
            $dados['token'] = null;
            $dados['enviado_em'] = null;
        }
        update('documento', $dados, 'id = :id', ['id' => $d['id']]);
        flash(input('regerar') ? 'Texto refeito a partir do modelo atual.' : 'Documento salvo.');
        redirect(input('ir_para') === 'ver' ? 'documentos/ver' : 'documentos/editar', ['id' => $d['id']]);
    }
    view('documentos/editar', ['titulo' => 'Editar documento', 'd' => $d]);
}

function ver(): void
{
    require_perm('documentos');
    $d = carregar_documento(input_int('id'));
    view('documentos/ver', [
        'titulo' => $d['titulo'],
        'd' => $d,
        'link' => $d['token'] ? url_absoluta('assinar', ['t' => $d['token']]) : null,
        'integro' => $d['status'] !== 'assinado' || hash('sha256', $d['conteudo']) === $d['assinado_hash'],
    ]);
}

function enviar(): void
{
    require_perm('documentos', NIVEL_EDITAR);
    $d = carregar_documento(input_int('id'));
    if ($d['status'] === 'assinado') {
        redirect('documentos/ver', ['id' => $d['id']]);
    }
    $pend = pendencias_documento($d['conteudo']);
    if ($pend && !input('mesmo_assim')) {
        flash('Ainda há campos para preencher no texto: ' . implode(', ', array_slice($pend, 0, 5)) . '. Edite o documento ou confirme o envio mesmo assim.', 'erro');
        redirect('documentos/ver', ['id' => $d['id'], 'pendente' => 1]);
    }
    update('documento', [
        'status' => 'enviado',
        'token' => $d['token'] ?: novo_token(),
        'enviado_em' => now(),
    ], 'id = :id', ['id' => $d['id']]);
    log_atividade("Enviou para assinatura: {$d['titulo']}");
    flash('Link de assinatura criado. Copie e envie ao cliente.');
    redirect('documentos/ver', ['id' => $d['id']]);
}

/** Registra uma assinatura feita fora do sistema (em papel ou outra plataforma). */
function assinadoManual(): void
{
    require_perm('documentos', NIVEL_EDITAR);
    $d = carregar_documento(input_int('id'));
    if ($d['status'] === 'assinado') {
        redirect('documentos/ver', ['id' => $d['id']]);
    }
    $data = (string) input('data') ?: today();
    update('documento', [
        'status' => 'assinado',
        'assinado_em' => (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : today()) . ' 12:00:00',
        'assinado_nome' => (string) input('nome') ?: $d['cliente_nome'],
        'assinado_agente' => 'Registrado manualmente por ' . current_user()['nome'],
        'assinado_hash' => hash('sha256', $d['conteudo']),
        'token' => null,
    ], 'id = :id', ['id' => $d['id']]);
    log_atividade("Registrou assinatura de {$d['titulo']}");
    flash('Assinatura registrada.');
    redirect('documentos/ver', ['id' => $d['id']]);
}

function imprimir(): void
{
    require_perm('documentos');
    $d = carregar_documento(input_int('id'));
    view('documentos/imprimir', [
        'd' => $d,
        'esc' => conf_texto('esc_razao') ?: (string) val('SELECT nome FROM escritorio WHERE id = ?', [eid()]),
        'responsavel' => conf_texto('esc_responsavel'),
        'cau' => conf_texto('esc_cau'),
    ], null);
}

function excluir(): void
{
    require_perm('documentos', NIVEL_EDITAR);
    $d = carregar_documento(input_int('id'));
    if ($d['status'] === 'assinado') {
        flash('Documento assinado não pode ser excluído.', 'erro');
        redirect('documentos/ver', ['id' => $d['id']]);
    }
    q('DELETE FROM documento WHERE id = ?', [$d['id']]);
    flash('Documento excluído.');
    redirect('documentos');
}

function modelos(): void
{
    require_perm('documentos');
    seed_documentos(eid());
    $mods = [];
    foreach (all('SELECT tipo, conteudo, atualizado_em FROM documento_modelo WHERE escritorio_id = ?', [eid()]) as $m) {
        $mods[$m['tipo']] = $m;
    }
    $padrao = modelos_documento_padrao();
    $alterados = [];
    foreach (array_keys(TIPOS_DOCUMENTO) as $t) {
        $alterados[$t] = isset($mods[$t]) && $mods[$t]['conteudo'] !== $padrao[$t];
    }
    view('documentos/modelos', [
        'titulo' => 'Documentos',
        'aba' => 'modelos',
        'mods' => $mods,
        'alterados' => $alterados,
    ]);
}

function modelo(): void
{
    require_perm('documentos');
    $tipo = (string) input('tipo');
    if (!isset(TIPOS_DOCUMENTO[$tipo])) {
        not_found();
    }
    seed_documentos(eid());
    if (is_post()) {
        require_perm('documentos', NIVEL_EDITAR);
        $conteudo = input('restaurar') ? modelos_documento_padrao()[$tipo] : (string) ($_POST['conteudo'] ?? '');
        if (trim($conteudo) === '') {
            flash('O modelo não pode ficar vazio.', 'erro');
            redirect('documentos/modelo', ['tipo' => $tipo]);
        }
        q('UPDATE documento_modelo SET conteudo = ?, atualizado_em = ? WHERE escritorio_id = ? AND tipo = ?', [$conteudo, now(), eid(), $tipo]);
        log_atividade('Editou as cláusulas do modelo: ' . TIPOS_DOCUMENTO[$tipo]);
        flash(input('restaurar') ? 'Modelo original restaurado.' : 'Modelo salvo. Os próximos documentos deste tipo usam este texto.');
        redirect('documentos/modelo', ['tipo' => $tipo]);
    }
    view('documentos/modelo', [
        'titulo' => 'Modelo: ' . TIPOS_DOCUMENTO[$tipo],
        'tipo' => $tipo,
        'conteudo' => (string) val('SELECT conteudo FROM documento_modelo WHERE escritorio_id = ? AND tipo = ?', [eid(), $tipo]),
    ]);
}
