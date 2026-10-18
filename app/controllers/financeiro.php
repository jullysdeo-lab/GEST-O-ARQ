<?php
declare(strict_types=1);

const CATEGORIAS = [
    'entrada' => ['Honorários de projeto', 'Acompanhamento de obra', 'Consultoria', 'RT / comissão', 'Outras receitas'],
    'saida' => ['Aluguel', 'Salários e pró-labore', 'Impostos', 'Softwares', 'Marketing', 'Cursos/capacitação', 'Impressões', 'Deslocamento', 'Fornecedores', 'Outras despesas'],
];

const FORMAS_PAGAMENTO = ['Pix', 'Boleto', 'Transferência', 'Cartão de crédito', 'Cartão de débito', 'Dinheiro'];

function periodo(): array
{
    $de = (string) input('de', date('Y-m-01'));
    $ate = (string) input('ate', date('Y-m-t'));
    $ok = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    return [$ok($de) ? $de : date('Y-m-01'), $ok($ate) ? $ate : date('Y-m-t')];
}

function index(): void
{
    require_perm('financeiro');
    [$de, $ate] = periodo();
    $saldo = soma_lanc('entrada', 'realizado') - soma_lanc('saida', 'realizado');
    $categorias = all(
        "SELECT COALESCE(categoria, 'Sem categoria') AS categoria, SUM(valor) AS total
         FROM lancamento WHERE escritorio_id = ? AND tipo = 'saida' AND vencimento BETWEEN ? AND ?
         GROUP BY COALESCE(categoria, 'Sem categoria') ORDER BY total DESC",
        [eid(), $de, $ate]
    );
    view('financeiro/index', [
        'titulo' => 'Financeiro',
        'aba' => 'dashboard',
        'de' => $de,
        'ate' => $ate,
        'saldo' => $saldo,
        'k' => [
            'a_receber' => soma_lanc('entrada', 'aberto', $de, $ate),
            'recebido' => soma_lanc('entrada', 'realizado', $de, $ate, 'realizado_em'),
            'a_pagar' => soma_lanc('saida', 'aberto', $de, $ate),
            'pago' => soma_lanc('saida', 'realizado', $de, $ate, 'realizado_em'),
            'receber_atrasado' => soma_atrasado('entrada'),
            'pagar_atrasado' => soma_atrasado('saida'),
        ],
        'serie' => serie_mensal(6),
        'categorias' => $categorias,
    ]);
}

function lancamentos(): void
{
    require_perm('financeiro');
    $tipo = input('tipo') === 'saida' ? 'saida' : 'entrada';
    [$de, $ate] = periodo();
    $situacao = (string) input('situacao', '');
    $busca = (string) input('busca', '');
    $hoje = today();

    $where = 'l.escritorio_id = ? AND l.tipo = ?';
    $params = [eid(), $tipo];
    switch ($situacao) {
        case 'atrasado':
            // Atrasados aparecem independente do período.
            $where .= " AND l.status <> 'realizado' AND l.vencimento < ?";
            $params[] = $hoje;
            break;
        case 'hoje':
            $where .= " AND l.status <> 'realizado' AND l.vencimento = ?";
            $params[] = $hoje;
            break;
        case 'avencer':
            $where .= " AND l.status <> 'realizado' AND l.vencimento > ? AND l.vencimento BETWEEN ? AND ?";
            array_push($params, $hoje, $de, $ate);
            break;
        case 'realizado':
            $where .= " AND l.status = 'realizado' AND l.vencimento BETWEEN ? AND ?";
            array_push($params, $de, $ate);
            break;
        default:
            $where .= ' AND l.vencimento BETWEEN ? AND ?';
            array_push($params, $de, $ate);
    }
    if ($busca !== '') {
        $where .= ' AND (l.descricao LIKE ? OR c.nome LIKE ? OR p.nome LIKE ?)';
        array_push($params, "%$busca%", "%$busca%", "%$busca%");
    }
    $lista = all(
        "SELECT l.*, c.nome AS cliente_nome, p.codigo AS projeto_codigo, p.nome AS projeto_nome
         FROM lancamento l
         LEFT JOIN cliente c ON c.id = l.cliente_id
         LEFT JOIN projeto p ON p.id = l.projeto_id
         WHERE $where ORDER BY l.vencimento, l.id",
        $params
    );
    view('financeiro/lancamentos', [
        'titulo' => 'Financeiro',
        'aba' => $tipo,
        'tipo' => $tipo,
        'lista' => $lista,
        'de' => $de,
        'ate' => $ate,
        'situacao' => $situacao,
        'busca' => $busca,
    ]);
}

function form(): void
{
    require_perm('financeiro', NIVEL_EDITAR);
    $id = input_int('id');
    $l = $id ? one('SELECT * FROM lancamento WHERE id = ? AND escritorio_id = ?', [$id, eid()]) : null;
    if ($id && !$l) {
        not_found();
    }
    $tipo = $l['tipo'] ?? (input('tipo') === 'saida' ? 'saida' : 'entrada');
    $clienteId = (int) ($l['cliente_id'] ?? 0);
    view('financeiro/form', [
        'titulo' => ($l ? 'Editar ' : 'Nova ') . ($tipo === 'entrada' ? 'entrada' : 'saída'),
        'l' => $l,
        'tipo' => $tipo,
        'clientes' => all('SELECT id, nome FROM cliente WHERE escritorio_id = ? ORDER BY nome', [eid()]),
        'projetos' => $clienteId
            ? all('SELECT id, codigo, nome FROM projeto WHERE escritorio_id = ? AND cliente_id = ? ORDER BY codigo DESC', [eid(), $clienteId])
            : [],
    ]);
}

function salvar(): void
{
    require_perm('financeiro', NIVEL_EDITAR);
    $id = input_int('id');
    $l = $id ? one('SELECT * FROM lancamento WHERE id = ? AND escritorio_id = ?', [$id, eid()]) : null;
    if ($id && !$l) {
        not_found();
    }
    $tipo = $l['tipo'] ?? (input('tipo') === 'saida' ? 'saida' : 'entrada');
    $clienteId = input_int('cliente_id') ?: null;
    $projetoId = input_int('projeto_id') ?: null;
    if ($clienteId && !one('SELECT id FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()])) {
        $clienteId = null;
    }
    // O projeto tem que ser do cliente escolhido.
    if ($projetoId && !one('SELECT id FROM projeto WHERE id = ? AND escritorio_id = ? AND (? IS NULL OR cliente_id = ?)', [$projetoId, eid(), $clienteId, $clienteId])) {
        $projetoId = null;
    }
    $venc = (string) input('vencimento');
    $valor = parse_num(input('valor'));
    $descricao = (string) input('descricao');
    if ($descricao === '' || $valor <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
        flash('Preencha descrição, valor maior que zero e vencimento.', 'erro');
        redirect('financeiro/form', $id ? ['id' => $id] : ['tipo' => $tipo]);
    }
    $dados = [
        'descricao' => $descricao,
        'valor' => $valor,
        'vencimento' => $venc,
        'competencia' => preg_match('/^\d{4}-\d{2}$/', (string) input('competencia')) ? input('competencia') : substr($venc, 0, 7),
        'categoria' => (string) input('categoria'),
        'forma_pagamento' => (string) input('forma_pagamento'),
        'cliente_id' => $clienteId,
        'projeto_id' => $projetoId,
    ];
    if (!$l && input('ja_realizado')) {
        $dados['status'] = 'realizado';
        $dados['realizado_em'] = $venc;
    }
    if ($l) {
        update('lancamento', $dados, 'id = :id', ['id' => $id]);
    } else {
        $dados += ['escritorio_id' => eid(), 'tipo' => $tipo, 'status' => 'aguardando', 'criado_em' => now()];
        $id = insert('lancamento', $dados);
        log_atividade(($tipo === 'entrada' ? 'Lançou entrada' : 'Lançou saída') . " \"$descricao\" (" . money($valor) . ')');
    }
    flash('Lançamento salvo.');
    redirect('financeiro/lancamentos', ['tipo' => $tipo]);
}

function lancamento_do_escritorio(): array
{
    $l = one('SELECT * FROM lancamento WHERE id = ? AND escritorio_id = ?', [input_int('id'), eid()]);
    if (!$l) {
        not_found();
    }
    return $l;
}

function voltar_lista(array $l): never
{
    redirect('financeiro/lancamentos', array_filter([
        'tipo' => $l['tipo'],
        'situacao' => (string) input('situacao'),
        'de' => (string) input('de'),
        'ate' => (string) input('ate'),
    ]));
}

function baixar(): void
{
    require_perm('financeiro', NIVEL_EDITAR);
    $l = lancamento_do_escritorio();
    $data = (string) input('data') ?: today();
    update('lancamento', [
        'status' => 'realizado',
        'realizado_em' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : today(),
    ], 'id = :id', ['id' => $l['id']]);
    log_atividade("Deu baixa em \"{$l['descricao']}\" (" . money($l['valor']) . ')');
    flash($l['tipo'] === 'entrada' ? 'Recebimento registrado.' : 'Pagamento registrado.');
    voltar_lista($l);
}

/** Desfaz uma baixa feita por engano. */
function estornar(): void
{
    require_perm('financeiro', NIVEL_EDITAR);
    $l = lancamento_do_escritorio();
    update('lancamento', ['status' => 'aguardando', 'realizado_em' => null], 'id = :id', ['id' => $l['id']]);
    log_atividade("Voltou \"{$l['descricao']}\" para aguardando");
    flash('Lançamento voltou para aguardando.');
    voltar_lista($l);
}

function duplicar(): void
{
    require_perm('financeiro', NIVEL_EDITAR);
    $l = lancamento_do_escritorio();
    $venc = date('Y-m-d', strtotime($l['vencimento'] . ' +1 month'));
    $novo = $l;
    unset($novo['id']);
    $novo = array_merge($novo, [
        'descricao' => $l['descricao'],
        'vencimento' => $venc,
        'competencia' => substr($venc, 0, 7),
        'status' => 'aguardando',
        'realizado_em' => null,
        'criado_em' => now(),
    ]);
    insert('lancamento', $novo);
    flash('Lançamento duplicado para o mês seguinte.');
    voltar_lista($l);
}

function excluir(): void
{
    require_perm('financeiro', NIVEL_EDITAR);
    $l = lancamento_do_escritorio();
    q('DELETE FROM lancamento WHERE id = ?', [$l['id']]);
    log_atividade("Excluiu o lançamento \"{$l['descricao']}\"");
    flash('Lançamento excluído.');
    voltar_lista($l);
}

function projetosDoCliente(): void
{
    require_perm('financeiro');
    json_out(all(
        'SELECT id, codigo, nome FROM projeto WHERE escritorio_id = ? AND cliente_id = ? ORDER BY codigo DESC',
        [eid(), input_int('cliente_id')]
    ));
}
