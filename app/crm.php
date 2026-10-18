<?php
declare(strict_types=1);

const FUNIL = [
    'novo' => 'Novo contato',
    'qualificacao' => 'Qualificação',
    'reuniao' => 'Briefing / reunião',
    'proposta' => 'Proposta enviada',
    'negociacao' => 'Negociação',
    'ganho' => 'Ganho',
    'perdido' => 'Perdido',
];

// Etapas em aberto, na ordem do funil.
const FUNIL_ABERTO = ['novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao'];

const TEMPERATURAS = ['quente' => 'Quente', 'morno' => 'Morno', 'frio' => 'Frio'];

const TIPOS_INTERACAO = [
    'whatsapp' => 'WhatsApp',
    'ligacao' => 'Ligação',
    'email' => 'E-mail',
    'reuniao' => 'Reunião',
    'visita' => 'Visita',
    'nota' => 'Anotação',
];

const ORIGENS = ['Instagram', 'Indicação', 'Google', 'Site', 'WhatsApp', 'Facebook', 'Evento', 'Cliente antigo', 'Outro'];

const MOTIVOS_PERDA = ['Preço', 'Fechou com outro profissional', 'Desistiu do projeto', 'Adiou o projeto', 'Sem retorno', 'Fora do perfil do escritório', 'Outro'];

// Dias sem contato para um lead em aberto ser considerado esquecido.
const DIAS_LEAD_FRIO = 15;

const TIPOS_EVENTO = [
    'reuniao' => ['Reunião', '#2c5f8a'],
    'visita' => ['Visita técnica', '#2f7a4f'],
    'entrega' => ['Entrega', '#b5532f'],
    'obra' => ['Obra', '#9a6a0b'],
    'interno' => ['Interno', '#6b5ea8'],
    'pessoal' => ['Pessoal / ausência', '#736c62'],
];

/**
 * Avança o lead no funil por um evento do sistema (briefing enviado, proposta enviada, orçamento aprovado).
 * Nunca volta etapas e não mexe em quem já foi ganho ou perdido, a não ser para marcar como ganho.
 */
function avancar_funil(int $clienteId, string $etapa): void
{
    $atual = (string) val('SELECT funil_etapa FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()]);
    if ($etapa === 'ganho') {
        if ($atual !== 'ganho') {
            update('cliente', ['funil_etapa' => 'ganho', 'tipo' => 'cliente', 'motivo_perda' => null], 'id = :id', ['id' => $clienteId]);
        }
        return;
    }
    $posAtual = array_search($atual ?: 'novo', FUNIL_ABERTO, true);
    $posNova = array_search($etapa, FUNIL_ABERTO, true);
    if ($posAtual !== false && $posNova !== false && $posNova > $posAtual) {
        update('cliente', ['funil_etapa' => $etapa], 'id = :id', ['id' => $clienteId]);
    }
}

function registrar_interacao(int $clienteId, string $tipo, string $descricao, ?string $data = null): void
{
    $data ??= now();
    insert('interacao', [
        'escritorio_id' => eid(),
        'cliente_id' => $clienteId,
        'usuario_id' => current_user()['id'] ?? null,
        'tipo' => isset(TIPOS_INTERACAO[$tipo]) ? $tipo : 'nota',
        'descricao' => $descricao,
        'data' => $data,
    ]);
    $ultimo = (string) val('SELECT ultimo_contato FROM cliente WHERE id = ?', [$clienteId]);
    if ($ultimo === '' || $data > $ultimo) {
        update('cliente', ['ultimo_contato' => $data], 'id = :id', ['id' => $clienteId]);
    }
}

/** Leads em aberto sem contato há mais de DIAS_LEAD_FRIO dias. */
function leads_esquecidos(int $limite = 10): array
{
    $corte = date('Y-m-d H:i:s', strtotime('-' . DIAS_LEAD_FRIO . ' days'));
    return all(
        "SELECT id, nome, funil_etapa, COALESCE(ultimo_contato, criado_em) AS referencia
         FROM cliente
         WHERE escritorio_id = ? AND funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao')
           AND COALESCE(ultimo_contato, criado_em) < ?
         ORDER BY referencia LIMIT $limite",
        [eid(), $corte]
    );
}

function dias_desde(?string $data): ?int
{
    if (!$data) {
        return null;
    }
    return (int) floor((strtotime(today()) - strtotime(substr($data, 0, 10))) / 86400);
}

/**
 * Itens do calendário entre duas datas (inclusive), em quatro camadas:
 * eventos, prazos de tarefas, contatos agendados no CRM e vencimentos financeiros.
 * $uid filtra por pessoa (participante/responsável); null mostra a equipe toda.
 */
function itens_agenda(string $de, string $ate, ?int $uid, array $camadas): array
{
    $itens = [];
    $fimDia = $ate . ' 23:59:59';

    if (in_array('eventos', $camadas, true)) {
        $sql = 'SELECT e.*, c.nome AS cliente_nome, p.codigo AS projeto_codigo FROM evento e
                LEFT JOIN cliente c ON c.id = e.cliente_id LEFT JOIN projeto p ON p.id = e.projeto_id
                WHERE e.escritorio_id = ? AND e.inicio <= ? AND e.fim >= ?';
        $params = [eid(), $fimDia, $de . ' 00:00:00'];
        if ($uid) {
            $sql .= ' AND (e.criado_por = ? OR EXISTS (SELECT 1 FROM evento_participante ep WHERE ep.evento_id = e.id AND ep.usuario_id = ?))';
            array_push($params, $uid, $uid);
        }
        foreach (all($sql . ' ORDER BY e.inicio', $params) as $e) {
            // Evento de vários dias aparece em cada dia.
            $d = max($de, substr($e['inicio'], 0, 10));
            $ultimo = min($ate, substr($e['fim'], 0, 10));
            while ($d <= $ultimo) {
                $itens[$d][] = [
                    'tipo' => 'evento',
                    'id' => $e['id'],
                    'hora' => $e['dia_todo'] ? null : substr($e['inicio'], 11, 5),
                    'titulo' => $e['titulo'],
                    'cor' => TIPOS_EVENTO[$e['tipo']][1] ?? '#736c62',
                    'sub' => trim(($e['local'] ?? '') . ($e['cliente_nome'] ? ' · ' . $e['cliente_nome'] : ''), ' ·'),
                    'url' => url('agenda/evento', ['id' => $e['id']]),
                ];
                $d = add_days($d, 1);
            }
        }
    }

    if (in_array('tarefas', $camadas, true) && can('projetos')) {
        $sql = "SELECT t.id, t.nome, t.prazo, t.status, p.id AS projeto_id, p.codigo FROM tarefa t JOIN projeto p ON p.id = t.projeto_id
                WHERE p.escritorio_id = ? AND t.prazo BETWEEN ? AND ? AND t.status <> 'concluida'";
        $params = [eid(), $de, $ate];
        if ($uid) {
            $sql .= ' AND t.responsavel_id = ?';
            $params[] = $uid;
        }
        foreach (all($sql . ' ORDER BY t.prazo', $params) as $t) {
            $itens[$t['prazo']][] = [
                'tipo' => 'tarefa',
                'id' => $t['id'],
                'hora' => null,
                'titulo' => $t['nome'],
                'cor' => $t['prazo'] < today() ? '#b3261e' : '#a39b8f',
                'sub' => 'Prazo · ' . $t['codigo'],
                'url' => url('projetos/ver', ['id' => $t['projeto_id']]),
            ];
        }
    }

    if (in_array('crm', $camadas, true) && can('crm')) {
        $sql = "SELECT id, nome, proximo_contato FROM cliente WHERE escritorio_id = ? AND proximo_contato BETWEEN ? AND ?
                AND funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao')";
        $params = [eid(), $de, $ate];
        if ($uid) {
            $sql .= ' AND responsavel_id = ?';
            $params[] = $uid;
        }
        foreach (all($sql, $params) as $c) {
            $itens[$c['proximo_contato']][] = [
                'tipo' => 'crm',
                'id' => $c['id'],
                'hora' => null,
                'titulo' => 'Retornar: ' . $c['nome'],
                'cor' => '#c2410c',
                'sub' => 'CRM',
                'url' => url('crm/lead', ['id' => $c['id']]),
            ];
        }
    }

    if (in_array('financeiro', $camadas, true) && can('financeiro')) {
        $rows = all(
            "SELECT id, tipo, descricao, valor, vencimento FROM lancamento WHERE escritorio_id = ? AND status <> 'realizado' AND vencimento BETWEEN ? AND ?",
            [eid(), $de, $ate]
        );
        foreach ($rows as $l) {
            $itens[$l['vencimento']][] = [
                'tipo' => 'financeiro',
                'id' => $l['id'],
                'hora' => null,
                'titulo' => ($l['tipo'] === 'entrada' ? 'Receber ' : 'Pagar ') . money($l['valor']),
                'cor' => $l['tipo'] === 'entrada' ? '#2f7a4f' : '#9a6a0b',
                'sub' => $l['descricao'],
                'url' => url('financeiro/form', ['id' => $l['id']]),
            ];
        }
    }

    foreach ($itens as &$lista) {
        // Com hora primeiro, em ordem; depois os do dia todo.
        usort($lista, fn($a, $b) => [$a['hora'] === null, $a['hora']] <=> [$b['hora'] === null, $b['hora']]);
    }
    ksort($itens);
    return $itens;
}
