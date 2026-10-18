<?php
declare(strict_types=1);

const SQL_PROJETO_RESUMO = "SELECT p.*, c.nome AS cliente_nome, o.numero AS orcamento_numero, o.valor_total AS orcamento_valor,
    (SELECT COUNT(*) FROM tarefa t WHERE t.projeto_id = p.id) AS total_tarefas,
    (SELECT COUNT(*) FROM tarefa t WHERE t.projeto_id = p.id AND t.status = 'concluida') AS concluidas,
    (SELECT COUNT(*) FROM tarefa t WHERE t.projeto_id = p.id AND t.status <> 'concluida' AND t.prazo < :hoje) AS atrasadas,
    (SELECT COALESCE(SUM(t.horas_previstas), 0) FROM tarefa t WHERE t.projeto_id = p.id) AS horas
  FROM projeto p
  JOIN cliente c ON c.id = p.cliente_id
  LEFT JOIN orcamento o ON o.id = p.orcamento_id";

function index(): void
{
    require_perm('projetos');
    $status = (string) input('status', '');
    $where = 'p.escritorio_id = :eid';
    $params = ['eid' => eid(), 'hoje' => today()];
    if (isset(PROJETO_STATUS[$status])) {
        $where .= ' AND p.status = :status';
        $params['status'] = $status;
    }
    $lista = all(SQL_PROJETO_RESUMO . " WHERE $where ORDER BY p.status = 'concluido', p.entrega, p.id DESC", $params);
    view('projetos/index', ['titulo' => 'Projetos', 'lista' => $lista, 'status' => $status]);
}

function novo(): void
{
    require_perm('projetos', NIVEL_EDITAR);
    $oid = input_int('orcamento_id');
    $o = null;
    if ($oid) {
        $o = one(
            "SELECT o.*, c.nome AS cliente_nome FROM orcamento o JOIN cliente c ON c.id = o.cliente_id
             WHERE o.id = ? AND o.escritorio_id = ? AND o.status = 'aprovado'",
            [$oid, eid()]
        );
        if (!$o) {
            flash('Só dá para criar projeto a partir de um orçamento aprovado.', 'erro');
            redirect('orcamentos');
        }
        if (val('SELECT id FROM projeto WHERE orcamento_id = ?', [$oid])) {
            flash('Este orçamento já tem um projeto.', 'erro');
            redirect('orcamentos/ver', ['id' => $oid]);
        }
        $o['dias'] = (int) val(
            'SELECT COALESCE(SUM(d), 0) FROM (SELECT MAX(duracao_dias) AS d FROM orcamento_item WHERE orcamento_id = ? GROUP BY etapa_ordem) x',
            [$oid]
        );
    }
    view('projetos/novo', [
        'titulo' => 'Novo projeto',
        'o' => $o,
        'clientes' => all("SELECT id, nome FROM cliente WHERE escritorio_id = ? ORDER BY nome", [eid()]),
        'podeFinanceiro' => can('financeiro', NIVEL_EDITAR),
    ]);
}

/**
 * Cria o projeto. Vindo de um orçamento aprovado:
 * - as tarefas do escopo orçado viram tarefas do projeto;
 * - o prazo de cada etapa é calculado em sequência a partir da data de início;
 * - opcionalmente, as parcelas do orçamento viram entradas no financeiro.
 */
function criar(): void
{
    require_perm('projetos', NIVEL_EDITAR);
    $oid = input_int('orcamento_id');
    $inicio = (string) input('inicio') ?: today();
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
        $inicio = today();
    }

    $o = null;
    if ($oid) {
        $o = one("SELECT * FROM orcamento WHERE id = ? AND escritorio_id = ? AND status = 'aprovado'", [$oid, eid()]);
        if (!$o || val('SELECT id FROM projeto WHERE orcamento_id = ?', [$oid])) {
            flash('Orçamento inválido ou já usado.', 'erro');
            redirect('orcamentos');
        }
        $clienteId = (int) $o['cliente_id'];
    } else {
        $clienteId = input_int('cliente_id');
        if (!one('SELECT id FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()])) {
            flash('Escolha o cliente.', 'erro');
            redirect('projetos/novo');
        }
    }

    $nome = (string) input('nome') ?: ($o['titulo'] ?? 'Projeto');

    db()->beginTransaction();
    $pid = insert('projeto', [
        'escritorio_id' => eid(),
        'codigo' => proximo_codigo_projeto(),
        'nome' => $nome,
        'cliente_id' => $clienteId,
        'orcamento_id' => $o['id'] ?? null,
        'status' => 'em_andamento',
        'inicio' => $inicio,
        'entrega' => input('entrega') ?: null,
        'criado_em' => now(),
    ]);

    $entrega = $inicio;
    if ($o) {
        $itens = all('SELECT * FROM orcamento_item WHERE orcamento_id = ? ORDER BY etapa_ordem, id', [$o['id']]);
        $prazoEtapa = [];
        $cursor = $inicio;
        foreach ($itens as $it) {
            $ord = (int) $it['etapa_ordem'];
            if (!isset($prazoEtapa[$ord])) {
                $cursor = add_days($cursor, (int) $it['duracao_dias']);
                $prazoEtapa[$ord] = $cursor;
            }
            insert('tarefa', [
                'projeto_id' => $pid,
                'etapa_ordem' => $ord,
                'etapa_nome' => $it['etapa_nome'],
                'nome' => $it['tarefa_nome'],
                'prazo' => $prazoEtapa[$ord],
                'prioridade' => 'media',
                'status' => 'a_fazer',
                'horas_previstas' => $it['horas'],
            ]);
        }
        $entrega = $cursor;
        update('projeto', ['entrega' => input('entrega') ?: $entrega], 'id = :id', ['id' => $pid]);

        if (input('gerar_parcelas') && can('financeiro', NIVEL_EDITAR)) {
            $n = max(1, (int) $o['parcelas']);
            $base = round((float) $o['valor_total'] / $n, 2);
            $primeira = (string) input('primeiro_vencimento') ?: $inicio;
            for ($i = 0; $i < $n; $i++) {
                // A última parcela absorve a diferença de arredondamento.
                $valor = $i === $n - 1 ? round((float) $o['valor_total'] - $base * ($n - 1), 2) : $base;
                $venc = date('Y-m-d', strtotime("$primeira +$i month"));
                insert('lancamento', [
                    'escritorio_id' => eid(),
                    'tipo' => 'entrada',
                    'descricao' => sprintf('%s · parcela %d/%d', $nome, $i + 1, $n),
                    'valor' => $valor,
                    'vencimento' => $venc,
                    'competencia' => substr($venc, 0, 7),
                    'categoria' => 'Honorários de projeto',
                    'cliente_id' => $clienteId,
                    'projeto_id' => $pid,
                    'status' => 'aguardando',
                    'criado_em' => now(),
                ]);
            }
        }
    }
    db()->commit();

    log_atividade("Criou o projeto \"$nome\"");
    flash('Projeto criado.');
    redirect('projetos/ver', ['id' => $pid]);
}

function ver(): void
{
    require_perm('projetos');
    $p = one(SQL_PROJETO_RESUMO . ' WHERE p.id = :id AND p.escritorio_id = :eid', ['id' => input_int('id'), 'eid' => eid(), 'hoje' => today()]);
    if (!$p) {
        not_found();
    }
    $tarefas = all(
        'SELECT t.*, u.nome AS responsavel_nome FROM tarefa t LEFT JOIN usuario u ON u.id = t.responsavel_id
         WHERE t.projeto_id = ? ORDER BY t.etapa_ordem, t.prazo, t.id',
        [$p['id']]
    );
    $financeiro = null;
    if (can('financeiro')) {
        $financeiro = one(
            "SELECT
               COALESCE(SUM(CASE WHEN tipo = 'entrada' AND status = 'realizado' THEN valor END), 0) AS recebido,
               COALESCE(SUM(CASE WHEN tipo = 'entrada' AND status <> 'realizado' THEN valor END), 0) AS a_receber,
               COALESCE(SUM(CASE WHEN tipo = 'saida' AND status = 'realizado' THEN valor END), 0) AS pago,
               COALESCE(SUM(CASE WHEN tipo = 'saida' AND status <> 'realizado' THEN valor END), 0) AS a_pagar
             FROM lancamento WHERE projeto_id = ? AND escritorio_id = ?",
            [$p['id'], eid()]
        );
    }
    view('projetos/ver', [
        'titulo' => $p['codigo'] . ' · ' . $p['nome'],
        'p' => $p,
        'documentos' => can('documentos') ? all('SELECT id, tipo, titulo, status FROM documento WHERE projeto_id = ? AND escritorio_id = ? ORDER BY id DESC', [$p['id'], eid()]) : [],
        'briefings' => can('briefings') ? all('SELECT id, titulo, status FROM briefing WHERE escritorio_id = ? AND (projeto_id = ? OR (orcamento_id IS NOT NULL AND orcamento_id = ?)) ORDER BY id DESC', [eid(), $p['id'], $p['orcamento_id'] ?? 0]) : [],
        'tarefas' => $tarefas,
        'equipe' => all('SELECT id, nome FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]),
        'financeiro' => $financeiro,
    ]);
}

/**
 * Etapas de um projeto com início, fim e andamento.
 * O início de cada etapa é o dia seguinte ao fim da anterior (a primeira começa no início do projeto).
 */
function etapas_do_projeto(array $p): array
{
    $rows = all(
        "SELECT etapa_ordem, etapa_nome, MIN(prazo) AS primeiro, MAX(prazo) AS ultimo, COUNT(*) AS total,
                SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) AS feitas
         FROM tarefa WHERE projeto_id = ? GROUP BY etapa_ordem, etapa_nome ORDER BY etapa_ordem",
        [$p['id']]
    );
    $etapas = [];
    $cursor = $p['inicio'] ?: substr($p['criado_em'], 0, 10);
    foreach ($rows as $r) {
        $fim = $r['ultimo'] ?: $cursor;
        $ini = min($cursor, $r['primeiro'] ?: $cursor);
        $situacao = $r['feitas'] == $r['total'] ? 'feita' : ($fim < today() ? 'atrasada' : ($ini <= today() ? 'andamento' : 'futura'));
        $etapas[] = [
            'nome' => $r['etapa_nome'],
            'inicio' => $ini,
            'fim' => max($ini, $fim),
            'pct' => $r['total'] ? (int) round($r['feitas'] / $r['total'] * 100) : 0,
            'situacao' => $situacao,
        ];
        $cursor = add_days(max($ini, $fim), 1);
    }
    return $etapas;
}

function cronograma(): void
{
    require_perm('projetos');
    $de = (string) input('de', date('Y-m-01'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $de)) {
        $de = date('Y-m-01');
    }
    $meses = max(1, min(12, input_int('meses') ?: 3));
    $ate = date('Y-m-d', strtotime("$de +$meses months -1 day"));
    $pid = input_int('projeto');

    $sql = "SELECT p.*, c.nome AS cliente_nome FROM projeto p JOIN cliente c ON c.id = p.cliente_id WHERE p.escritorio_id = ?";
    $params = [eid()];
    if ($pid) {
        $sql .= ' AND p.id = ?';
        $params[] = $pid;
    } else {
        $sql .= " AND p.status <> 'concluido'";
    }
    $linhas = [];
    foreach (all($sql . ' ORDER BY p.entrega IS NULL, p.entrega, p.id', $params) as $p) {
        $etapas = etapas_do_projeto($p);
        // Só entra quem tem alguma etapa dentro do período.
        $visiveis = array_filter($etapas, fn($e) => $e['fim'] >= $de && $e['inicio'] <= $ate);
        if ($visiveis || $pid) {
            $linhas[] = ['p' => $p, 'etapas' => $etapas];
        }
    }
    view('projetos/cronograma', [
        'titulo' => 'Cronograma',
        'de' => $de,
        'ate' => $ate,
        'meses' => $meses,
        'pid' => $pid,
        'linhas' => $linhas,
        'todos' => all('SELECT id, codigo, nome FROM projeto WHERE escritorio_id = ? ORDER BY id DESC', [eid()]),
        'marcos' => all(
            "SELECT inicio, titulo, projeto_id FROM evento WHERE escritorio_id = ? AND tipo = 'entrega' AND inicio BETWEEN ? AND ?",
            [eid(), "$de 00:00:00", "$ate 23:59:59"]
        ),
    ]);
}

function mudarStatus(): void
{
    require_perm('projetos', NIVEL_EDITAR);
    $id = input_int('id');
    $st = (string) input('status');
    if (isset(PROJETO_STATUS[$st])) {
        q('UPDATE projeto SET status = ? WHERE id = ? AND escritorio_id = ?', [$st, $id, eid()]);
        log_atividade('Mudou o status de um projeto para ' . PROJETO_STATUS[$st]);
    }
    redirect('projetos/ver', ['id' => $id]);
}

function excluir(): void
{
    require_perm('projetos', NIVEL_EDITAR);
    $id = input_int('id');
    $p = one('SELECT * FROM projeto WHERE id = ? AND escritorio_id = ?', [$id, eid()]);
    if ($p) {
        q('DELETE FROM projeto WHERE id = ?', [$id]);
        log_atividade("Excluiu o projeto \"{$p['nome']}\"");
        flash('Projeto excluído. Os lançamentos financeiros ficaram sem projeto vinculado.');
    }
    redirect('projetos');
}
