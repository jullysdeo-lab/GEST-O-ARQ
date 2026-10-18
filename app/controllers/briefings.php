<?php
declare(strict_types=1);

const BRIEFING_STATUS = [
    'aguardando' => 'Aguardando o cliente',
    'respondido' => 'Respondido',
];

function carregar_briefing(int $id): array
{
    $b = one(
        'SELECT b.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email,
                p.codigo AS projeto_codigo, o.numero AS orcamento_numero
         FROM briefing b JOIN cliente c ON c.id = b.cliente_id
         LEFT JOIN projeto p ON p.id = b.projeto_id
         LEFT JOIN orcamento o ON o.id = b.orcamento_id
         WHERE b.id = ? AND b.escritorio_id = ?',
        [$id, eid()]
    );
    if (!$b) {
        not_found();
    }
    $b['perguntas'] = json_decode($b['perguntas_json'], true) ?: [];
    $b['respostas'] = json_decode((string) $b['respostas_json'], true) ?: [];
    return $b;
}

function carregar_modelo(int $id): array
{
    $m = one('SELECT * FROM briefing_modelo WHERE id = ? AND escritorio_id = ?', [$id, eid()]);
    if (!$m) {
        not_found();
    }
    $m['perguntas'] = all('SELECT * FROM briefing_pergunta WHERE modelo_id = ? ORDER BY ordem, id', [$id]);
    return $m;
}

function index(): void
{
    require_perm('briefings');
    seed_briefings(eid());
    $status = (string) input('status', '');
    $where = 'b.escritorio_id = ?';
    $params = [eid()];
    if (isset(BRIEFING_STATUS[$status])) {
        $where .= ' AND b.status = ?';
        $params[] = $status;
    }
    $lista = all(
        "SELECT b.id, b.titulo, b.status, b.enviado_em, b.respondido_em, b.perguntas_json, b.respostas_json, c.nome AS cliente_nome
         FROM briefing b JOIN cliente c ON c.id = b.cliente_id WHERE $where ORDER BY b.id DESC",
        $params
    );
    foreach ($lista as &$b) {
        $b['total'] = count(json_decode($b['perguntas_json'], true) ?: []);
        $b['respondidas'] = count(array_filter(json_decode((string) $b['respostas_json'], true) ?: [], fn($v) => $v !== '' && $v !== []));
        unset($b['perguntas_json'], $b['respostas_json']);
    }
    view('briefings/index', ['titulo' => 'Briefings', 'aba' => 'enviados', 'lista' => $lista, 'status' => $status]);
}

function novo(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    seed_briefings(eid());
    view('briefings/novo', [
        'titulo' => 'Enviar briefing',
        'modelos' => all('SELECT m.*, (SELECT COUNT(*) FROM briefing_pergunta p WHERE p.modelo_id = m.id) AS n FROM briefing_modelo m WHERE m.escritorio_id = ? AND m.ativo = 1 ORDER BY m.categoria, m.nome', [eid()]),
        'clientes' => all('SELECT id, nome FROM cliente WHERE escritorio_id = ? ORDER BY nome', [eid()]),
        'projetos' => all('SELECT id, codigo, nome FROM projeto WHERE escritorio_id = ? ORDER BY id DESC', [eid()]),
        'orcamentos' => all("SELECT id, numero, titulo FROM orcamento WHERE escritorio_id = ? AND status <> 'arquivado' ORDER BY id DESC", [eid()]),
        'cliente_id' => input_int('cliente_id'),
        'projeto_id' => input_int('projeto_id'),
        'orcamento_id' => input_int('orcamento_id'),
    ]);
}

function criar(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $m = carregar_modelo(input_int('modelo_id'));
    if (!$m['perguntas']) {
        flash('Este modelo não tem perguntas.', 'erro');
        redirect('briefings/novo');
    }
    $projetoId = input_int('projeto_id') ?: null;
    $orcamentoId = input_int('orcamento_id') ?: null;
    $clienteId = input_int('cliente_id');
    // O cliente vem do projeto ou do orçamento, se escolhidos.
    if ($projetoId) {
        $clienteId = (int) val('SELECT cliente_id FROM projeto WHERE id = ? AND escritorio_id = ?', [$projetoId, eid()]);
    } elseif ($orcamentoId) {
        $clienteId = (int) val('SELECT cliente_id FROM orcamento WHERE id = ? AND escritorio_id = ?', [$orcamentoId, eid()]);
    }
    $cliente = one('SELECT nome FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()]);
    if (!$cliente) {
        flash('Escolha o cliente, o projeto ou o orçamento.', 'erro');
        redirect('briefings/novo');
    }

    // Copia as perguntas: editar o modelo depois não muda um briefing já enviado.
    $perguntas = [];
    foreach ($m['perguntas'] as $p) {
        $perguntas[] = [
            'k' => 'p' . $p['id'],
            'secao' => $p['secao'] ?: 'Geral',
            'texto' => $p['texto'],
            'tipo' => $p['tipo'],
            'opcoes' => $p['opcoes'] ? array_values(array_filter(array_map('trim', preg_split('/\R/', $p['opcoes'])))) : [],
            'obrigatoria' => (bool) $p['obrigatoria'],
        ];
    }
    $id = insert('briefing', [
        'escritorio_id' => eid(),
        'modelo_id' => $m['id'],
        'titulo' => (string) input('titulo') ?: $m['nome'] . ' — ' . $cliente['nome'],
        'cliente_id' => $clienteId,
        'projeto_id' => $projetoId,
        'orcamento_id' => $orcamentoId,
        'perguntas_json' => json_encode($perguntas, JSON_UNESCAPED_UNICODE),
        'respostas_json' => '{}',
        'status' => 'aguardando',
        'token' => novo_token(),
        'enviado_em' => now(),
        'criado_por' => current_user()['id'],
    ]);
    log_atividade("Criou o briefing \"{$m['nome']}\" para {$cliente['nome']}");
    avancar_funil($clienteId, 'reuniao');
    registrar_interacao($clienteId, 'nota', "Briefing \"{$m['nome']}\" enviado.");
    flash('Briefing criado. Copie o link e envie ao cliente.');
    redirect('briefings/ver', ['id' => $id]);
}

function ver(): void
{
    require_perm('briefings');
    $b = carregar_briefing(input_int('id'));
    view('briefings/ver', [
        'titulo' => $b['titulo'],
        'b' => $b,
        'link' => url_absoluta('briefing', ['t' => $b['token']]),
    ]);
}

function imprimir(): void
{
    require_perm('briefings');
    $b = carregar_briefing(input_int('id'));
    view('briefings/imprimir', [
        'b' => $b,
        'esc' => conf_texto('esc_razao') ?: (string) val('SELECT nome FROM escritorio WHERE id = ?', [eid()]),
    ], null);
}

function reabrir(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $b = carregar_briefing(input_int('id'));
    update('briefing', ['status' => 'aguardando'], 'id = :id', ['id' => $b['id']]);
    flash('Briefing reaberto: o cliente pode completar ou mudar as respostas pelo mesmo link.');
    redirect('briefings/ver', ['id' => $b['id']]);
}

function excluir(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $b = carregar_briefing(input_int('id'));
    q('DELETE FROM briefing WHERE id = ?', [$b['id']]);
    flash('Briefing excluído.');
    redirect('briefings');
}

/* ---------- Modelos e perguntas ---------- */

function modelos(): void
{
    require_perm('briefings');
    seed_briefings(eid());
    view('briefings/modelos', [
        'titulo' => 'Briefings',
        'aba' => 'modelos',
        'lista' => all(
            'SELECT m.*, (SELECT COUNT(*) FROM briefing_pergunta p WHERE p.modelo_id = m.id) AS n,
                    (SELECT COUNT(*) FROM briefing b WHERE b.modelo_id = m.id) AS usos
             FROM briefing_modelo m WHERE m.escritorio_id = ? ORDER BY m.ativo DESC, m.categoria, m.nome',
            [eid()]
        ),
    ]);
}

function modelo(): void
{
    require_perm('briefings');
    $m = carregar_modelo(input_int('id'));
    view('briefings/modelo', ['titulo' => 'Modelo de briefing', 'm' => $m, 'editar' => can('briefings', NIVEL_EDITAR)]);
}

function salvarModelo(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $id = input_int('id');
    $nome = (string) input('nome');
    if ($nome === '') {
        flash('Dê um nome ao modelo.', 'erro');
        redirect($id ? 'briefings/modelo' : 'briefings/modelos', $id ? ['id' => $id] : []);
    }
    $dados = [
        'nome' => $nome,
        'categoria' => (string) input('categoria'),
        'descricao' => mb_substr((string) input('descricao'), 0, 255),
        'ativo' => input('ativo', $id ? null : '1') ? 1 : 0,
    ];
    if ($id) {
        carregar_modelo($id);
        update('briefing_modelo', $dados, 'id = :id', ['id' => $id]);
        flash('Modelo salvo.');
    } else {
        $dados['escritorio_id'] = eid();
        $dados['ativo'] = 1;
        $id = insert('briefing_modelo', $dados);
        flash('Modelo criado. Agora adicione as perguntas.');
    }
    redirect('briefings/modelo', ['id' => $id]);
}

function duplicarModelo(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $m = carregar_modelo(input_int('id'));
    $novo = insert('briefing_modelo', [
        'escritorio_id' => eid(),
        'nome' => $m['nome'] . ' (cópia)',
        'categoria' => $m['categoria'],
        'descricao' => $m['descricao'],
        'ativo' => 1,
    ]);
    foreach ($m['perguntas'] as $p) {
        unset($p['id']);
        $p['modelo_id'] = $novo;
        insert('briefing_pergunta', $p);
    }
    flash('Modelo duplicado.');
    redirect('briefings/modelo', ['id' => $novo]);
}

function excluirModelo(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $m = carregar_modelo(input_int('id'));
    // Briefings já enviados guardam a própria cópia das perguntas e continuam funcionando.
    q('DELETE FROM briefing_modelo WHERE id = ?', [$m['id']]);
    flash('Modelo excluído.');
    redirect('briefings/modelos');
}

function salvarPergunta(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $m = carregar_modelo(input_int('modelo_id'));
    $id = input_int('id');
    $tipo = (string) input('tipo');
    if (!isset(TIPOS_PERGUNTA[$tipo])) {
        $tipo = 'texto';
    }
    $texto = mb_substr((string) input('texto'), 0, 255);
    $opcoes = trim((string) input('opcoes'));
    if ($texto === '') {
        flash('Escreva a pergunta.', 'erro');
        redirect('briefings/modelo', ['id' => $m['id']]);
    }
    if (in_array($tipo, ['opcao_unica', 'multipla'], true) && count(array_filter(preg_split('/\R/', $opcoes))) < 2) {
        flash('Perguntas de escolha precisam de pelo menos duas opções (uma por linha).', 'erro');
        redirect('briefings/modelo', ['id' => $m['id']]);
    }
    $dados = [
        'secao' => mb_substr((string) input('secao'), 0, 80) ?: 'Geral',
        'texto' => $texto,
        'tipo' => $tipo,
        'opcoes' => in_array($tipo, ['opcao_unica', 'multipla'], true) ? $opcoes : null,
        'obrigatoria' => input('obrigatoria') ? 1 : 0,
    ];
    if ($id) {
        q(
            'UPDATE briefing_pergunta SET secao = ?, texto = ?, tipo = ?, opcoes = ?, obrigatoria = ? WHERE id = ? AND modelo_id = ?',
            [$dados['secao'], $dados['texto'], $dados['tipo'], $dados['opcoes'], $dados['obrigatoria'], $id, $m['id']]
        );
    } else {
        $dados['modelo_id'] = $m['id'];
        $dados['ordem'] = (int) val('SELECT COALESCE(MAX(ordem), 0) FROM briefing_pergunta WHERE modelo_id = ?', [$m['id']]) + 1;
        $id = insert('briefing_pergunta', $dados);
    }
    redirect('briefings/modelo', ['id' => $m['id'], '_' => $id]);
}

function moverPergunta(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $m = carregar_modelo(input_int('modelo_id'));
    $ids = array_map(fn($p) => (int) $p['id'], $m['perguntas']);
    $pos = array_search(input_int('id'), $ids, true);
    $alvo = $pos === false ? false : $pos + (input('direcao') === 'cima' ? -1 : 1);
    if ($pos !== false && isset($ids[$alvo])) {
        [$ids[$pos], $ids[$alvo]] = [$ids[$alvo], $ids[$pos]];
        foreach ($ids as $i => $pid) {
            q('UPDATE briefing_pergunta SET ordem = ? WHERE id = ?', [$i + 1, $pid]);
        }
    }
    redirect('briefings/modelo', ['id' => $m['id']]);
}

function excluirPergunta(): void
{
    require_perm('briefings', NIVEL_EDITAR);
    $m = carregar_modelo(input_int('modelo_id'));
    q('DELETE FROM briefing_pergunta WHERE id = ? AND modelo_id = ?', [input_int('id'), $m['id']]);
    redirect('briefings/modelo', ['id' => $m['id']]);
}
