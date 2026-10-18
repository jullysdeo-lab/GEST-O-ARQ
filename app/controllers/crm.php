<?php
declare(strict_types=1);

function carregar_lead(int $id): array
{
    $c = one(
        'SELECT c.*, u.nome AS responsavel_nome FROM cliente c LEFT JOIN usuario u ON u.id = c.responsavel_id
         WHERE c.id = ? AND c.escritorio_id = ?',
        [$id, eid()]
    );
    if (!$c) {
        not_found();
    }
    return $c;
}

/** Funil em colunas (Kanban). */
function index(): void
{
    require_perm('crm');
    $resp = input_int('responsavel');
    $where = 'c.escritorio_id = ?';
    $params = [eid()];
    if ($resp) {
        $where .= ' AND c.responsavel_id = ?';
        $params[] = $resp;
    }
    // Ganhos e perdidos: só os dos últimos 30 dias, para o quadro não crescer sem fim.
    $corte = date('Y-m-d H:i:s', strtotime('-30 days'));
    $leads = all(
        "SELECT c.*, u.nome AS responsavel_nome,
            (SELECT MAX(o.valor_total) FROM orcamento o WHERE o.cliente_id = c.id AND o.status IN ('enviado', 'aprovado')) AS valor_proposta
         FROM cliente c LEFT JOIN usuario u ON u.id = c.responsavel_id
         WHERE $where AND (c.funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao')
               OR COALESCE(c.ultimo_contato, c.criado_em) >= ?)
         ORDER BY c.proximo_contato IS NULL, c.proximo_contato, c.id DESC",
        array_merge($params, [$corte])
    );
    $colunas = array_fill_keys(array_keys(FUNIL), []);
    foreach ($leads as $l) {
        $colunas[$l['funil_etapa'] ?: 'novo'][] = $l;
    }

    $desde = date('Y-m-d', strtotime('-180 days'));
    $ganhos = (int) val("SELECT COUNT(*) FROM cliente WHERE escritorio_id = ? AND funil_etapa = 'ganho' AND COALESCE(ultimo_contato, criado_em) >= ?", [eid(), $desde]);
    $perdidos = (int) val("SELECT COUNT(*) FROM cliente WHERE escritorio_id = ? AND funil_etapa = 'perdido' AND COALESCE(ultimo_contato, criado_em) >= ?", [eid(), $desde]);

    view('crm/index', [
        'titulo' => 'CRM',
        'aba' => 'funil',
        'colunas' => $colunas,
        'resp' => $resp,
        'equipe' => all('SELECT id, nome FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]),
        'kpi' => [
            'ativos' => (int) val("SELECT COUNT(*) FROM cliente WHERE escritorio_id = ? AND funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao')", [eid()]),
            'pipeline' => (float) val("SELECT COALESCE(SUM(valor_estimado), 0) FROM cliente WHERE escritorio_id = ? AND funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao')", [eid()]),
            'conversao' => ($ganhos + $perdidos) ? round($ganhos / ($ganhos + $perdidos) * 100) : null,
            'esquecidos' => count(leads_esquecidos(100)),
            'hoje' => (int) val("SELECT COUNT(*) FROM cliente WHERE escritorio_id = ? AND proximo_contato <= ? AND funil_etapa IN ('novo', 'qualificacao', 'reuniao', 'proposta', 'negociacao')", [eid(), today()]),
        ],
    ]);
}

/** Relatório: origem dos leads e motivos de perda. */
function relatorio(): void
{
    require_perm('crm');
    $desde = (string) input('desde', date('Y-m-d', strtotime('-12 months')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
        $desde = date('Y-m-d', strtotime('-12 months'));
    }
    view('crm/relatorio', [
        'titulo' => 'CRM',
        'aba' => 'relatorio',
        'desde' => $desde,
        'origens' => all(
            "SELECT COALESCE(NULLIF(origem, ''), 'Não informada') AS origem, COUNT(*) AS total,
                SUM(CASE WHEN funil_etapa = 'ganho' THEN 1 ELSE 0 END) AS ganhos,
                SUM(CASE WHEN funil_etapa = 'perdido' THEN 1 ELSE 0 END) AS perdidos
             FROM cliente WHERE escritorio_id = ? AND criado_em >= ?
             GROUP BY COALESCE(NULLIF(origem, ''), 'Não informada') ORDER BY total DESC",
            [eid(), $desde]
        ),
        'motivos' => all(
            "SELECT COALESCE(NULLIF(motivo_perda, ''), 'Não informado') AS motivo, COUNT(*) AS total
             FROM cliente WHERE escritorio_id = ? AND funil_etapa = 'perdido' AND criado_em >= ?
             GROUP BY COALESCE(NULLIF(motivo_perda, ''), 'Não informado') ORDER BY total DESC",
            [eid(), $desde]
        ),
        'etapas' => all(
            'SELECT funil_etapa, COUNT(*) AS total, COALESCE(SUM(valor_estimado), 0) AS valor FROM cliente
             WHERE escritorio_id = ? AND criado_em >= ? GROUP BY funil_etapa',
            [eid(), $desde]
        ),
        'esquecidos' => leads_esquecidos(50),
    ]);
}

function lead(): void
{
    require_perm('crm');
    $c = carregar_lead(input_int('id'));
    view('crm/lead', [
        'titulo' => $c['nome'],
        'c' => $c,
        'interacoes' => all(
            'SELECT i.*, u.nome AS usuario_nome FROM interacao i LEFT JOIN usuario u ON u.id = i.usuario_id
             WHERE i.cliente_id = ? AND i.escritorio_id = ? ORDER BY i.data DESC, i.id DESC',
            [$c['id'], eid()]
        ),
        'orcamentos' => all('SELECT id, numero, titulo, status, valor_total FROM orcamento WHERE cliente_id = ? AND escritorio_id = ? ORDER BY id DESC', [$c['id'], eid()]),
        'briefings' => all('SELECT id, titulo, status FROM briefing WHERE cliente_id = ? AND escritorio_id = ? ORDER BY id DESC', [$c['id'], eid()]),
        'eventos' => all('SELECT id, titulo, inicio FROM evento WHERE cliente_id = ? AND escritorio_id = ? AND inicio >= ? ORDER BY inicio LIMIT 5', [$c['id'], eid(), today()]),
        'equipe' => all('SELECT id, nome FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]),
    ]);
}

function form(): void
{
    require_perm('crm', NIVEL_EDITAR);
    $id = input_int('id');
    view('crm/form', [
        'titulo' => $id ? 'Editar lead' : 'Novo lead',
        'c' => $id ? carregar_lead($id) : null,
        'equipe' => all('SELECT id, nome FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]),
    ]);
}

function salvar(): void
{
    require_perm('crm', NIVEL_EDITAR);
    $id = input_int('id');
    $c = $id ? carregar_lead($id) : null;
    $nome = (string) input('nome');
    if ($nome === '') {
        flash('Informe o nome.', 'erro');
        redirect('crm/form', $id ? ['id' => $id] : []);
    }
    $resp = input_int('responsavel_id');
    $temp = (string) input('temperatura', 'morno');
    $prox = (string) input('proximo_contato');
    $dados = [
        'nome' => $nome,
        'email' => (string) input('email'),
        'telefone' => (string) input('telefone'),
        'origem' => (string) input('origem'),
        'tipo_projeto' => (string) input('tipo_projeto'),
        'valor_estimado' => (string) input('valor_estimado') !== '' ? parse_num(input('valor_estimado')) : null,
        'temperatura' => isset(TEMPERATURAS[$temp]) ? $temp : 'morno',
        'responsavel_id' => $resp && one('SELECT id FROM usuario WHERE id = ? AND escritorio_id = ?', [$resp, eid()]) ? $resp : null,
        'proximo_contato' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $prox) ? $prox : null,
        'observacoes' => (string) input('observacoes'),
    ];
    if ($c) {
        update('cliente', $dados, 'id = :id', ['id' => $id]);
        flash('Lead salvo.');
    } else {
        $etapa = (string) input('funil_etapa', 'novo');
        $dados += [
            'escritorio_id' => eid(),
            'tipo' => 'prospecto',
            'funil_etapa' => in_array($etapa, FUNIL_ABERTO, true) ? $etapa : 'novo',
            'criado_em' => now(),
        ];
        $id = insert('cliente', $dados);
        $primeiro = trim((string) input('primeiro_contato'));
        registrar_interacao($id, (string) input('canal', 'nota'), $primeiro !== '' ? $primeiro : 'Lead cadastrado.');
        log_atividade("Cadastrou o lead {$nome}");
        flash('Lead cadastrado.');
    }
    redirect('crm/lead', ['id' => $id]);
}

/** Muda a etapa do lead (pelo quadro, arrastando, ou pela ficha). */
function mover(): void
{
    require_perm('crm', NIVEL_EDITAR);
    $c = carregar_lead(input_int('id'));
    $etapa = (string) input('etapa');
    if (!isset(FUNIL[$etapa])) {
        is_ajax() ? json_out(['ok' => false, 'erro' => 'Etapa inválida']) : redirect('crm');
    }
    if ($etapa === 'perdido' && !input('motivo_perda')) {
        // Perder exige motivo: no quadro, abre a ficha para escolher.
        is_ajax() ? json_out(['ok' => false, 'abrir' => url('crm/lead', ['id' => $c['id'], 'perder' => 1])]) : redirect('crm/lead', ['id' => $c['id'], 'perder' => 1]);
    }
    if ($etapa !== $c['funil_etapa']) {
        $dados = ['funil_etapa' => $etapa];
        if ($etapa === 'ganho') {
            $dados['tipo'] = 'cliente';
            $dados['motivo_perda'] = null;
        } elseif ($etapa === 'perdido') {
            $dados['motivo_perda'] = mb_substr((string) input('motivo_perda'), 0, 160);
            $dados['proximo_contato'] = null;
        } else {
            $dados['motivo_perda'] = null;
        }
        update('cliente', $dados, 'id = :id', ['id' => $c['id']]);
        $texto = 'Etapa: ' . FUNIL[$c['funil_etapa'] ?: 'novo'] . ' → ' . FUNIL[$etapa] . ($etapa === 'perdido' ? " (motivo: {$dados['motivo_perda']})" : '') . '.';
        registrar_interacao((int) $c['id'], 'nota', $texto);
    }
    if (is_ajax()) {
        json_out(['ok' => true]);
    }
    flash('Etapa atualizada.');
    redirect(input('voltar') === 'quadro' ? 'crm' : 'crm/lead', input('voltar') === 'quadro' ? [] : ['id' => $c['id']]);
}

function interacao(): void
{
    require_perm('crm', NIVEL_EDITAR);
    $c = carregar_lead(input_int('cliente_id'));
    $desc = trim((string) input('descricao'));
    if ($desc === '') {
        flash('Escreva o que foi conversado.', 'erro');
        redirect('crm/lead', ['id' => $c['id']]);
    }
    $data = (string) input('data');
    $data = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $data) ? str_replace('T', ' ', $data) . ':00' : now();
    registrar_interacao((int) $c['id'], (string) input('tipo'), $desc, $data);

    $dados = [];
    $prox = (string) input('proximo_contato');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $prox)) {
        $dados['proximo_contato'] = $prox;
    } elseif (input('limpar_proximo')) {
        $dados['proximo_contato'] = null;
    }
    $temp = (string) input('temperatura');
    if (isset(TEMPERATURAS[$temp])) {
        $dados['temperatura'] = $temp;
    }
    if ($dados) {
        update('cliente', $dados, 'id = :id', ['id' => $c['id']]);
    }
    flash('Contato registrado.');
    redirect('crm/lead', ['id' => $c['id']]);
}

function excluirInteracao(): void
{
    require_perm('crm', NIVEL_EDITAR);
    $c = carregar_lead(input_int('cliente_id'));
    q('DELETE FROM interacao WHERE id = ? AND cliente_id = ? AND escritorio_id = ?', [input_int('id'), $c['id'], eid()]);
    redirect('crm/lead', ['id' => $c['id']]);
}
