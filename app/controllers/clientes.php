<?php
declare(strict_types=1);

function index(): void
{
    require_perm('clientes');
    $tipo = (string) input('tipo', '');
    $busca = (string) input('busca', '');
    $where = 'c.escritorio_id = ?';
    $params = [eid()];
    if (in_array($tipo, ['cliente', 'prospecto'], true)) {
        $where .= ' AND c.tipo = ?';
        $params[] = $tipo;
    }
    if ($busca !== '') {
        $where .= ' AND (c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ?)';
        array_push($params, "%$busca%", "%$busca%", "%$busca%");
    }
    $lista = all(
        "SELECT c.*,
            (SELECT COUNT(*) FROM projeto p WHERE p.cliente_id = c.id) AS projetos,
            (SELECT COUNT(*) FROM orcamento o WHERE o.cliente_id = c.id) AS orcamentos,
            (SELECT COALESCE(SUM(l.valor), 0) FROM lancamento l WHERE l.cliente_id = c.id AND l.tipo = 'entrada' AND l.status = 'realizado') AS faturado
         FROM cliente c WHERE $where ORDER BY c.nome",
        $params
    );
    view('clientes/index', ['titulo' => 'Clientes', 'lista' => $lista, 'tipo' => $tipo, 'busca' => $busca]);
}

function form(): void
{
    require_perm('clientes', NIVEL_EDITAR);
    $id = input_int('id');
    $c = $id ? one('SELECT * FROM cliente WHERE id = ? AND escritorio_id = ?', [$id, eid()]) : null;
    if ($id && !$c) {
        not_found();
    }
    view('clientes/form', ['titulo' => $c ? 'Editar cliente' : 'Novo cliente', 'c' => $c]);
}

function salvar(): void
{
    require_perm('clientes', NIVEL_EDITAR);
    $id = input_int('id');
    if ($id && !one('SELECT id FROM cliente WHERE id = ? AND escritorio_id = ?', [$id, eid()])) {
        not_found();
    }
    $dados = [
        'nome' => (string) input('nome'),
        'tipo' => input('tipo') === 'cliente' ? 'cliente' : 'prospecto',
        'email' => (string) input('email'),
        'telefone' => (string) input('telefone'),
        'documento' => (string) input('documento'),
        'origem' => (string) input('origem'),
        'endereco' => (string) input('endereco'),
        'observacoes' => (string) input('observacoes'),
    ];
    if ($dados['nome'] === '') {
        flash('Informe o nome.', 'erro');
        redirect('clientes/form', $id ? ['id' => $id] : []);
    }
    if ($id) {
        update('cliente', $dados, 'id = :id', ['id' => $id]);
    } else {
        $dados['escritorio_id'] = eid();
        $dados['criado_em'] = now();
        $dados['funil_etapa'] = $dados['tipo'] === 'cliente' ? 'ganho' : 'novo';
        $dados['temperatura'] = 'morno';
        insert('cliente', $dados);
        log_atividade("Cadastrou {$dados['nome']}");
    }
    flash('Cadastro salvo.');
    redirect('clientes');
}

/** Gera (ou renova) o link de uso único para o cliente completar o cadastro. */
function linkDados(): void
{
    require_perm('clientes', NIVEL_EDITAR);
    $id = input_int('id');
    $c = one('SELECT id, nome FROM cliente WHERE id = ? AND escritorio_id = ?', [$id, eid()]);
    if (!$c) {
        not_found();
    }
    update('cliente', ['token_dados' => novo_token()], 'id = :id', ['id' => $id]);
    log_atividade("Pediu os dados cadastrais de {$c['nome']} por link");
    flash('Link criado. Copie e envie ao cliente; ele vale para um único envio.');
    redirect('clientes/form', ['id' => $id]);
}

function excluir(): void
{
    require_perm('clientes', NIVEL_EDITAR);
    $id = input_int('id');
    $uso = (int) val('SELECT COUNT(*) FROM orcamento WHERE cliente_id = ?', [$id])
        + (int) val('SELECT COUNT(*) FROM projeto WHERE cliente_id = ?', [$id]);
    if ($uso > 0) {
        flash('Este cliente tem orçamentos ou projetos e não pode ser excluído.', 'erro');
        redirect('clientes');
    }
    q('DELETE FROM cliente WHERE id = ? AND escritorio_id = ?', [$id, eid()]);
    flash('Cliente excluído.');
    redirect('clientes');
}
