<?php
declare(strict_types=1);

const MESES_CURTOS = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

/** Receitas e despesas realizadas por mês, dos últimos $n meses (incluindo o atual). */
function serie_mensal(int $n = 6): array
{
    $inicio = date('Y-m-01', strtotime('-' . ($n - 1) . ' months', strtotime(date('Y-m-01'))));
    $rows = all(
        "SELECT SUBSTR(realizado_em, 1, 7) AS mes, tipo, SUM(valor) AS total
         FROM lancamento
         WHERE escritorio_id = ? AND status = 'realizado' AND realizado_em >= ?
         GROUP BY SUBSTR(realizado_em, 1, 7), tipo",
        [eid(), $inicio]
    );
    $serie = [];
    for ($i = 0; $i < $n; $i++) {
        $m = date('Y-m', strtotime("$inicio +$i months"));
        $serie[$m] = ['label' => MESES_CURTOS[(int) substr($m, 5, 2)], 'entrada' => 0.0, 'saida' => 0.0];
    }
    foreach ($rows as $r) {
        if (isset($serie[$r['mes']])) {
            $serie[$r['mes']][$r['tipo']] = (float) $r['total'];
        }
    }
    return $serie;
}

/** Soma de lançamentos com filtros simples. */
function soma_lanc(string $tipo, string $status, ?string $de = null, ?string $ate = null, string $campoData = 'vencimento'): float
{
    $sql = 'SELECT COALESCE(SUM(valor), 0) FROM lancamento WHERE escritorio_id = ? AND tipo = ?';
    $p = [eid(), $tipo];
    if ($status === 'aberto') {
        $sql .= " AND status <> 'realizado'";
    } elseif ($status !== '') {
        $sql .= ' AND status = ?';
        $p[] = $status;
    }
    if ($de) {
        $sql .= " AND $campoData >= ?";
        $p[] = $de;
    }
    if ($ate) {
        $sql .= " AND $campoData <= ?";
        $p[] = $ate;
    }
    return (float) val($sql, $p);
}

function soma_atrasado(string $tipo): float
{
    return (float) val(
        "SELECT COALESCE(SUM(valor), 0) FROM lancamento WHERE escritorio_id = ? AND tipo = ? AND status <> 'realizado' AND vencimento < ?",
        [eid(), $tipo, today()]
    );
}

/**
 * Índice de saúde do escritório (0 a 100), média de três notas:
 * - Financeiro: parte dos lançamentos em aberto que NÃO está atrasada;
 * - Operacional: parte das tarefas em aberto que NÃO está atrasada;
 * - Comercial: taxa de aprovação dos orçamentos decididos nos últimos 180 dias (50 sem dados).
 */
function indice_saude(): array
{
    $hoje = today();
    $abertos = (int) val("SELECT COUNT(*) FROM lancamento WHERE escritorio_id = ? AND status <> 'realizado'", [eid()]);
    $atrasados = (int) val("SELECT COUNT(*) FROM lancamento WHERE escritorio_id = ? AND status <> 'realizado' AND vencimento < ?", [eid(), $hoje]);
    $fin = $abertos ? (int) round(100 * (1 - $atrasados / $abertos)) : 100;

    $tAbertas = (int) val("SELECT COUNT(*) FROM tarefa t JOIN projeto p ON p.id = t.projeto_id WHERE p.escritorio_id = ? AND t.status <> 'concluida'", [eid()]);
    $tAtrasadas = (int) val("SELECT COUNT(*) FROM tarefa t JOIN projeto p ON p.id = t.projeto_id WHERE p.escritorio_id = ? AND t.status <> 'concluida' AND t.prazo < ?", [eid(), $hoje]);
    $op = $tAbertas ? (int) round(100 * (1 - $tAtrasadas / $tAbertas)) : 100;

    $desde = date('Y-m-d', strtotime('-180 days'));
    $aprov = (int) val("SELECT COUNT(*) FROM orcamento WHERE escritorio_id = ? AND status = 'aprovado' AND criado_em >= ?", [eid(), $desde]);
    $recus = (int) val("SELECT COUNT(*) FROM orcamento WHERE escritorio_id = ? AND status = 'recusado' AND criado_em >= ?", [eid(), $desde]);
    $com = ($aprov + $recus) ? (int) round(100 * $aprov / ($aprov + $recus)) : 50;

    $total = (int) round(($fin + $op + $com) / 3);
    return [
        'total' => $total,
        'rotulo' => $total >= 80 ? 'Bom' : ($total >= 60 ? 'Regular' : 'Atenção'),
        'financeiro' => $fin,
        'operacional' => $op,
        'comercial' => $com,
    ];
}
