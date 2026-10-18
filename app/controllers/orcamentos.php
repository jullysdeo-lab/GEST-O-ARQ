<?php
declare(strict_types=1);

function carregar_orcamento(int $id): array
{
    $o = one(
        'SELECT o.*, c.nome AS cliente_nome, c.tipo AS cliente_tipo, c.email AS cliente_email
         FROM orcamento o JOIN cliente c ON c.id = o.cliente_id
         WHERE o.id = ? AND o.escritorio_id = ?',
        [$id, eid()]
    );
    if (!$o) {
        not_found();
    }
    $o['itens'] = all('SELECT * FROM orcamento_item WHERE orcamento_id = ? ORDER BY etapa_ordem, id', [$id]);
    $o['projeto'] = one('SELECT id, codigo, nome FROM projeto WHERE orcamento_id = ?', [$id]);
    return $o;
}

function editavel(array $o): bool
{
    return in_array($o['status'], ['rascunho', 'enviado'], true) && can('precificacao', NIVEL_EDITAR);
}

function index(): void
{
    require_perm('precificacao');
    $status = (string) input('status', '');
    $where = 'o.escritorio_id = ?';
    $params = [eid()];
    if (isset(ORCAMENTO_STATUS[$status])) {
        $where .= ' AND o.status = ?';
        $params[] = $status;
    } else {
        $where .= " AND o.status <> 'arquivado'";
    }
    $lista = all(
        "SELECT o.*, c.nome AS cliente_nome FROM orcamento o JOIN cliente c ON c.id = o.cliente_id
         WHERE $where ORDER BY o.criado_em DESC",
        $params
    );
    $resumo = [];
    foreach (all('SELECT status, COUNT(*) n, COALESCE(SUM(valor_total), 0) v FROM orcamento WHERE escritorio_id = ? GROUP BY status', [eid()]) as $r) {
        $resumo[$r['status']] = $r;
    }
    view('orcamentos/index', ['titulo' => 'Orçamentos', 'lista' => $lista, 'status' => $status, 'resumo' => $resumo]);
}

function novo(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    view('orcamentos/novo', [
        'titulo' => 'Novo orçamento',
        'clientes' => all('SELECT id, nome, tipo FROM cliente WHERE escritorio_id = ? ORDER BY nome', [eid()]),
        'templates' => all('SELECT id, nome FROM template WHERE escritorio_id = ? ORDER BY nome', [eid()]),
        'cliente_id' => input_int('cliente_id'),
        'ht' => hora_tecnica(),
    ]);
}

function criar(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $titulo = (string) input('titulo');
    $clienteId = input_int('cliente_id');
    $novoCliente = (string) input('novo_cliente');

    if ($titulo === '') {
        flash('Dê um título ao orçamento.', 'erro');
        redirect('orcamentos/novo');
    }

    db()->beginTransaction();
    if ($novoCliente !== '') {
        $clienteId = insert('cliente', [
            'escritorio_id' => eid(),
            'nome' => $novoCliente,
            'tipo' => 'prospecto',
            'email' => (string) input('novo_email'),
            'telefone' => (string) input('novo_telefone'),
            'origem' => (string) input('novo_origem'),
            'funil_etapa' => 'novo',
            'temperatura' => 'morno',
            'responsavel_id' => current_user()['id'],
            'criado_em' => now(),
        ]);
    } elseif (!one('SELECT id FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()])) {
        db()->rollBack();
        flash('Escolha um cliente ou cadastre um novo.', 'erro');
        redirect('orcamentos/novo');
    }

    $area = parse_num(input('area_m2'));
    $id = insert('orcamento', [
        'escritorio_id' => eid(),
        'numero' => proximo_numero_orcamento(),
        'cliente_id' => $clienteId,
        'titulo' => $titulo,
        'status' => 'rascunho',
        'area_m2' => $area ?: null,
        'custo_hora' => hora_tecnica()['custo_hora'],
        'parcelas' => max(1, (int) input('parcelas', 1)),
        'criado_por' => current_user()['id'],
        'criado_em' => now(),
    ]);

    $tid = input_int('template_id');
    if ($tid && one('SELECT id FROM template WHERE id = ? AND escritorio_id = ?', [$tid, eid()])) {
        $itens = all(
            'SELECT e.ordem, e.nome AS etapa, e.duracao_dias, t.nome, t.horas
             FROM template_etapa e JOIN template_tarefa t ON t.etapa_id = e.id
             WHERE e.template_id = ? ORDER BY e.ordem, t.ordem',
            [$tid]
        );
        foreach ($itens as $it) {
            insert('orcamento_item', [
                'orcamento_id' => $id,
                'etapa_ordem' => $it['ordem'],
                'etapa_nome' => $it['etapa'],
                'duracao_dias' => $it['duracao_dias'],
                'tarefa_nome' => $it['nome'],
                'horas' => $it['horas'],
            ]);
        }
    }
    recalcular_orcamento($id);
    db()->commit();

    log_atividade("Criou o orçamento \"$titulo\"");
    redirect('orcamentos/ver', ['id' => $id]);
}

function ver(): void
{
    require_perm('precificacao');
    $o = carregar_orcamento(input_int('id'));
    $horasBase = array_sum(array_map(fn($i) => (float) $i['horas'], $o['itens']));
    view('orcamentos/ver', [
        'titulo' => $o['numero'],
        'o' => $o,
        'calc' => calcular_orcamento($horasBase, (float) $o['custo_hora']),
        'editavel' => editavel($o),
        'm2' => param('honorario_m2'),
    ]);
}

function salvarItem(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $o = carregar_orcamento(input_int('orcamento_id'));
    if (!editavel($o)) {
        flash('Este orçamento não pode mais ser editado.', 'erro');
        redirect('orcamentos/ver', ['id' => $o['id']]);
    }
    $etapa = (string) input('etapa_nome');
    $tarefa = (string) input('tarefa_nome');
    if ($etapa === '' || $tarefa === '') {
        flash('Informe a etapa e a tarefa.', 'erro');
        redirect('orcamentos/ver', ['id' => $o['id']]);
    }
    // Se a etapa já existe no orçamento, reaproveita ordem e duração.
    $existente = one('SELECT etapa_ordem, duracao_dias FROM orcamento_item WHERE orcamento_id = ? AND etapa_nome = ? LIMIT 1', [$o['id'], $etapa]);
    $ordem = $existente['etapa_ordem'] ?? ((int) val('SELECT COALESCE(MAX(etapa_ordem), 0) FROM orcamento_item WHERE orcamento_id = ?', [$o['id']]) + 1);
    insert('orcamento_item', [
        'orcamento_id' => $o['id'],
        'etapa_ordem' => $ordem,
        'etapa_nome' => $etapa,
        'duracao_dias' => $existente['duracao_dias'] ?? max(0, (int) input('duracao_dias', 7)),
        'tarefa_nome' => $tarefa,
        'horas' => parse_num(input('horas')),
    ]);
    recalcular_orcamento((int) $o['id']);
    redirect('orcamentos/ver', ['id' => $o['id']]);
}

function excluirItem(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $o = carregar_orcamento(input_int('orcamento_id'));
    if (editavel($o)) {
        q('DELETE FROM orcamento_item WHERE id = ? AND orcamento_id = ?', [input_int('id'), $o['id']]);
        recalcular_orcamento((int) $o['id']);
    }
    redirect('orcamentos/ver', ['id' => $o['id']]);
}

function salvarDados(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $o = carregar_orcamento(input_int('id'));
    if (!editavel($o)) {
        flash('Este orçamento não pode mais ser editado.', 'erro');
        redirect('orcamentos/ver', ['id' => $o['id']]);
    }
    $manual = trim((string) input('valor_manual'));
    $dados = [
        'titulo' => (string) input('titulo') ?: $o['titulo'],
        'area_m2' => parse_num(input('area_m2')) ?: null,
        'parcelas' => max(1, (int) input('parcelas', 1)),
        'observacoes' => (string) input('observacoes'),
        'valor_manual' => $manual === '' ? null : parse_num($manual),
    ];
    if (input('atualizar_hora')) {
        $dados['custo_hora'] = hora_tecnica()['custo_hora'];
    }
    update('orcamento', $dados, 'id = :id', ['id' => $o['id']]);
    recalcular_orcamento((int) $o['id']);
    flash('Orçamento atualizado.');
    redirect('orcamentos/ver', ['id' => $o['id']]);
}

function mudarStatus(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $o = carregar_orcamento(input_int('id'));
    $novo = (string) input('status');
    $permitidos = [
        'rascunho' => ['enviado', 'arquivado'],
        'enviado' => ['aprovado', 'recusado', 'rascunho', 'arquivado'],
        'recusado' => ['rascunho', 'arquivado'],
        'aprovado' => ['arquivado'],
        'arquivado' => ['rascunho'],
    ];
    if (!in_array($novo, $permitidos[$o['status']] ?? [], true)) {
        flash('Mudança de status não permitida.', 'erro');
        redirect('orcamentos/ver', ['id' => $o['id']]);
    }
    if ($novo === 'aprovado' && !$o['itens']) {
        flash('Adicione ao menos uma tarefa antes de aprovar.', 'erro');
        redirect('orcamentos/ver', ['id' => $o['id']]);
    }

    $dados = ['status' => $novo];
    if ($novo === 'enviado') {
        $dados['enviado_em'] = now();
    }
    update('orcamento', $dados, 'id = :id', ['id' => $o['id']]);

    // Reflete no funil do CRM e no histórico do cliente.
    $cid = (int) $o['cliente_id'];
    if ($novo === 'enviado') {
        avancar_funil($cid, 'proposta');
        registrar_interacao($cid, 'email', "Proposta {$o['numero']} enviada (" . money($o['valor_total']) . ').');
    } elseif ($novo === 'aprovado') {
        // Ao aprovar, o prospecto vira cliente.
        avancar_funil($cid, 'ganho');
        registrar_interacao($cid, 'nota', "Orçamento {$o['numero']} aprovado (" . money($o['valor_total']) . ').');
    } elseif ($novo === 'recusado') {
        avancar_funil($cid, 'negociacao');
        registrar_interacao($cid, 'nota', "Orçamento {$o['numero']} recusado pelo cliente.");
    }
    log_atividade("Mudou o orçamento {$o['numero']} para " . ORCAMENTO_STATUS[$novo]);

    if ($novo === 'aprovado') {
        flash('Orçamento aprovado. ' . $o['cliente_nome'] . ' agora é cliente. Crie o projeto a partir dele.');
    } else {
        flash('Status atualizado.');
    }
    redirect('orcamentos/ver', ['id' => $o['id']]);
}

function imprimir(): void
{
    require_perm('precificacao');
    $o = carregar_orcamento(input_int('id'));
    $etapas = [];
    foreach ($o['itens'] as $i) {
        $etapas[$i['etapa_ordem']]['nome'] = $i['etapa_nome'];
        $etapas[$i['etapa_ordem']]['dias'] = $i['duracao_dias'];
        $etapas[$i['etapa_ordem']]['tarefas'][] = $i['tarefa_nome'];
    }
    ksort($etapas);
    view('orcamentos/imprimir', [
        'o' => $o,
        'etapas' => $etapas,
        'escritorio' => val('SELECT nome FROM escritorio WHERE id = ?', [eid()]),
    ], null);
}
