<?php
declare(strict_types=1);

function index(): void
{
    require_perm('central');
    $u = current_user();
    $hoje = today();
    $ini = date('Y-m-01');
    $fim = date('Y-m-t');

    $verFin = can('financeiro');
    $fin = null;
    $alertasFin = [];
    if ($verFin) {
        $receita = soma_lanc('entrada', 'realizado', $ini, $fim, 'realizado_em');
        $despesa = soma_lanc('saida', 'realizado', $ini, $fim, 'realizado_em');
        $fin = [
            'faturamento' => $receita,
            'a_receber' => soma_lanc('entrada', 'aberto', $ini, $fim),
            'a_pagar' => soma_lanc('saida', 'aberto', $ini, $fim),
            'pagar_atrasado' => soma_atrasado('saida'),
            'receber_atrasado' => soma_atrasado('entrada'),
            'margem' => $receita > 0 ? ($receita - $despesa) / $receita * 100 : null,
            'meta' => param('meta_margem_pct'),
        ];
        $alertasFin = all(
            "SELECT l.*, c.nome AS cliente_nome FROM lancamento l LEFT JOIN cliente c ON c.id = l.cliente_id
             WHERE l.escritorio_id = ? AND l.status <> 'realizado' AND l.vencimento <= ?
             ORDER BY l.vencimento LIMIT 8",
            [eid(), add_days($hoje, 3)]
        );
    }

    $projetos = [];
    foreach (all('SELECT status, entrega FROM projeto WHERE escritorio_id = ?', [eid()]) as $p) {
        $k = $p['status'] !== 'concluido' && $p['entrega'] && $p['entrega'] < $hoje ? 'atrasado' : $p['status'];
        $projetos[$k] = ($projetos[$k] ?? 0) + 1;
    }

    $minhas = all(
        "SELECT t.*, p.codigo, p.id AS projeto_id FROM tarefa t JOIN projeto p ON p.id = t.projeto_id
         WHERE p.escritorio_id = ? AND t.responsavel_id = ? AND t.status <> 'concluida' AND t.prazo <= ?
         ORDER BY t.prazo LIMIT 8",
        [eid(), $u['id'], add_days($hoje, 2)]
    );
    $atrasadas = (int) val(
        "SELECT COUNT(*) FROM tarefa t JOIN projeto p ON p.id = t.projeto_id
         WHERE p.escritorio_id = ? AND t.status <> 'concluida' AND t.prazo < ?",
        [eid(), $hoje]
    );

    $compromissos = [];
    foreach (itens_agenda($hoje, add_days($hoje, 6), (int) $u['id'], ['eventos']) as $dia => $lista) {
        foreach ($lista as $it) {
            $compromissos[] = $it + ['dia' => $dia];
        }
    }

    view('central/index', [
        'titulo' => 'Painel',
        'u' => $u,
        'compromissos' => array_slice($compromissos, 0, 6),
        'retornos' => can('crm') ? all(
            "SELECT id, nome, proximo_contato FROM cliente WHERE escritorio_id = ? AND proximo_contato <= ?
             AND funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao') ORDER BY proximo_contato LIMIT 6",
            [eid(), $hoje]
        ) : [],
        'esquecidos' => can('crm') ? count(leads_esquecidos(100)) : 0,
        'fin' => $fin,
        'alertasFin' => $alertasFin,
        'projetos' => $projetos,
        'minhas' => $minhas,
        'atrasadas' => $atrasadas,
        'saude' => indice_saude(),
        'serie' => $verFin ? serie_mensal(6) : null,
        'orcamentos' => can('precificacao') ? all(
            "SELECT o.id, o.numero, o.titulo, o.valor_total, o.status, c.nome AS cliente_nome
             FROM orcamento o JOIN cliente c ON c.id = o.cliente_id
             WHERE o.escritorio_id = ? AND o.status IN ('rascunho', 'enviado') ORDER BY o.criado_em DESC LIMIT 5",
            [eid()]
        ) : [],
        'atividades' => all(
            'SELECT a.*, u.nome AS usuario_nome FROM atividade a LEFT JOIN usuario u ON u.id = a.usuario_id
             WHERE a.escritorio_id = ? ORDER BY a.id DESC LIMIT 8',
            [eid()]
        ),
        'primeirosPassos' => [
            'Cadastrar custos do escritório' => (bool) val('SELECT id FROM custo WHERE escritorio_id = ? LIMIT 1', [eid()]),
            'Preencher os dados do escritório (para os contratos)' => conf_texto('esc_cnpj') !== '',
            'Enviar um briefing ao cliente' => (bool) val('SELECT id FROM briefing WHERE escritorio_id = ? LIMIT 1', [eid()]),
            'Criar o primeiro orçamento' => (bool) val('SELECT id FROM orcamento WHERE escritorio_id = ? LIMIT 1', [eid()]),
            'Aprovar um orçamento' => (bool) val("SELECT id FROM orcamento WHERE escritorio_id = ? AND status = 'aprovado' LIMIT 1", [eid()]),
            'Criar um projeto a partir do orçamento' => (bool) val('SELECT id FROM projeto WHERE escritorio_id = ? AND orcamento_id IS NOT NULL LIMIT 1', [eid()]),
            'Gerar e assinar o contrato' => (bool) val("SELECT id FROM documento WHERE escritorio_id = ? AND tipo = 'contrato' AND status = 'assinado' LIMIT 1", [eid()]),
            'Registrar um recebimento' => (bool) val("SELECT id FROM lancamento WHERE escritorio_id = ? AND tipo = 'entrada' AND status = 'realizado' LIMIT 1", [eid()]),
        ],
    ]);
}
