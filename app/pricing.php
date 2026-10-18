<?php
declare(strict_types=1);

const PARAMETROS_PADRAO = [
    'impostos_pct' => 6.0,       // Simples Nacional, por exemplo
    'reserva_pct' => 10.0,       // reserva técnica / imprevistos
    'margem_horas_pct' => 15.0,  // margem de segurança nas horas estimadas
    'lucro_pct' => 20.0,         // lucro desejado sobre o custo
    'honorario_m2' => 0.0,       // referência por m² (opcional)
    'meta_margem_pct' => 30.0,   // meta de margem para o painel
];

function param(string $chave): float
{
    $v = val('SELECT valor FROM config WHERE escritorio_id = ? AND chave = ?', [eid(), $chave]);
    return $v === null ? (float) (PARAMETROS_PADRAO[$chave] ?? 0) : (float) $v;
}

function set_param(string $chave, float $valor): void
{
    $existe = val('SELECT id FROM config WHERE escritorio_id = ? AND chave = ?', [eid(), $chave]);
    if ($existe) {
        update('config', ['valor' => (string) $valor], 'id = :id', ['id' => $existe]);
    } else {
        insert('config', ['escritorio_id' => eid(), 'chave' => $chave, 'valor' => (string) $valor]);
    }
}

/**
 * Base da hora técnica do escritório:
 * custo mensal (custos fixos + variáveis + salários da equipe ativa) ÷ horas mensais da equipe ativa.
 */
function hora_tecnica(): array
{
    $custos = (float) val('SELECT COALESCE(SUM(valor_mensal), 0) FROM custo WHERE escritorio_id = ?', [eid()]);
    $equipe = one(
        'SELECT COALESCE(SUM(salario), 0) AS salarios, COALESCE(SUM(horas_dia * dias_mes), 0) AS horas
         FROM usuario WHERE escritorio_id = ? AND ativo = 1',
        [eid()]
    );
    $total = $custos + (float) $equipe['salarios'];
    $horas = (float) $equipe['horas'];
    return [
        'custos' => $custos,
        'salarios' => (float) $equipe['salarios'],
        'custo_mensal' => $total,
        'horas_mes' => $horas,
        'custo_hora' => $horas > 0 ? round($total / $horas, 2) : 0.0,
    ];
}

/**
 * Calcula o valor de um orçamento a partir das horas.
 *   horas ajustadas = horas × (1 + margem de segurança)
 *   custo           = horas ajustadas × custo da hora
 *   preço           = custo × (1 + lucro) × (1 + reserva) ÷ (1 − impostos)
 */
function calcular_orcamento(float $horas, float $custo_hora): array
{
    $margem = param('margem_horas_pct') / 100;
    $lucro = param('lucro_pct') / 100;
    $reserva = param('reserva_pct') / 100;
    $impostos = min(param('impostos_pct') / 100, 0.95);

    $horas_aj = $horas * (1 + $margem);
    $custo = $horas_aj * $custo_hora;
    $preco = $custo * (1 + $lucro) * (1 + $reserva) / (1 - $impostos);

    return [
        'horas' => $horas,
        'horas_ajustadas' => round($horas_aj, 2),
        'custo' => round($custo, 2),
        'impostos' => round($preco * $impostos, 2),
        'valor' => round($preco, 2),
    ];
}

function proximo_numero_orcamento(): string
{
    $ano = date('Y');
    $n = (int) val(
        'SELECT COUNT(*) FROM orcamento WHERE escritorio_id = ? AND numero LIKE ?',
        [eid(), "ORC-$ano-%"]
    );
    return sprintf('ORC-%s-%03d', $ano, $n + 1);
}

function proximo_codigo_projeto(): string
{
    $aa = date('y');
    $n = (int) val(
        'SELECT COUNT(*) FROM projeto WHERE escritorio_id = ? AND codigo LIKE ?',
        [eid(), "$aa%"]
    );
    return sprintf('%s%03d', $aa, $n + 1);
}

/** Recalcula horas e valor de um orçamento a partir dos itens. */
function recalcular_orcamento(int $id): void
{
    $o = one('SELECT * FROM orcamento WHERE id = ? AND escritorio_id = ?', [$id, eid()]);
    if (!$o) {
        return;
    }
    $horas = (float) val('SELECT COALESCE(SUM(horas), 0) FROM orcamento_item WHERE orcamento_id = ?', [$id]);
    $calc = calcular_orcamento($horas, (float) $o['custo_hora']);
    update('orcamento', [
        'horas_total' => $calc['horas_ajustadas'],
        'custo_total' => $calc['custo'],
        'valor_total' => $o['valor_manual'] !== null ? (float) $o['valor_manual'] : $calc['valor'],
    ], 'id = :id', ['id' => $id]);
}
