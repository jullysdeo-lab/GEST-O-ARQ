<?php
declare(strict_types=1);

function configuracoes(): void
{
    require_perm('precificacao');
    $params = [];
    foreach (array_keys(PARAMETROS_PADRAO) as $k) {
        $params[$k] = param($k);
    }
    view('precificacao/config', [
        'titulo' => 'Precificação',
        'aba' => 'custos',
        'ht' => hora_tecnica(),
        'custos' => all('SELECT * FROM custo WHERE escritorio_id = ? ORDER BY tipo, nome', [eid()]),
        'params' => $params,
        'exemplo' => calcular_orcamento(100, hora_tecnica()['custo_hora']),
    ]);
}

function salvarParametros(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    foreach (array_keys(PARAMETROS_PADRAO) as $k) {
        if (isset($_POST[$k])) {
            $v = parse_num($_POST[$k]);
            if (str_ends_with($k, '_pct')) {
                $v = max(0, min($k === 'impostos_pct' ? 90 : 500, $v));
            }
            set_param($k, $v);
        }
    }
    log_atividade('Atualizou os parâmetros de precificação');
    flash('Parâmetros salvos.');
    redirect('precificacao');
}

function salvarCusto(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $nome = (string) input('nome');
    $tipo = input('tipo') === 'variavel' ? 'variavel' : 'fixo';
    if ($nome === '') {
        flash('Dê um nome ao custo.', 'erro');
        redirect('precificacao');
    }
    insert('custo', [
        'escritorio_id' => eid(),
        'tipo' => $tipo,
        'nome' => $nome,
        'valor_mensal' => parse_num(input('valor_mensal')),
    ]);
    flash('Custo adicionado.');
    redirect('precificacao');
}

function excluirCusto(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    q('DELETE FROM custo WHERE id = ? AND escritorio_id = ?', [input_int('id'), eid()]);
    flash('Custo removido.');
    redirect('precificacao');
}

function templates(): void
{
    require_perm('precificacao');
    $lista = all(
        'SELECT t.*,
            (SELECT COUNT(*) FROM template_etapa e WHERE e.template_id = t.id) AS etapas,
            (SELECT COALESCE(SUM(tt.horas), 0) FROM template_tarefa tt JOIN template_etapa e ON e.id = tt.etapa_id WHERE e.template_id = t.id) AS horas,
            (SELECT COALESCE(SUM(e.duracao_dias), 0) FROM template_etapa e WHERE e.template_id = t.id) AS dias
         FROM template t WHERE t.escritorio_id = ? ORDER BY t.nome',
        [eid()]
    );
    view('precificacao/templates', ['titulo' => 'Precificação', 'aba' => 'templates', 'lista' => $lista]);
}

function carregar_template(int $id): ?array
{
    $t = one('SELECT * FROM template WHERE id = ? AND escritorio_id = ?', [$id, eid()]);
    if (!$t) {
        return null;
    }
    $t['etapas'] = all('SELECT * FROM template_etapa WHERE template_id = ? ORDER BY ordem', [$id]);
    foreach ($t['etapas'] as &$et) {
        $et['tarefas'] = all('SELECT * FROM template_tarefa WHERE etapa_id = ? ORDER BY ordem', [$et['id']]);
    }
    return $t;
}

function template(): void
{
    require_perm('precificacao');
    $id = input_int('id');
    $t = $id ? carregar_template($id) : ['id' => '', 'nome' => '', 'etapas' => []];
    if (!$t) {
        not_found();
    }
    view('precificacao/template', ['titulo' => 'Template de projeto', 't' => $t]);
}

/**
 * Salva o template inteiro. Cada etapa vem com nome, duração e uma lista de tarefas,
 * uma por linha, no formato "Nome da tarefa; horas".
 */
function salvarTemplate(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    $id = input_int('id');
    $nome = (string) input('nome');
    if ($nome === '') {
        flash('Dê um nome ao template.', 'erro');
        redirect('precificacao/template', $id ? ['id' => $id] : []);
    }
    if ($id && !one('SELECT id FROM template WHERE id = ? AND escritorio_id = ?', [$id, eid()])) {
        not_found();
    }

    db()->beginTransaction();
    if ($id) {
        update('template', ['nome' => $nome], 'id = :id', ['id' => $id]);
        q('DELETE FROM template_etapa WHERE template_id = ?', [$id]);
    } else {
        $id = insert('template', ['escritorio_id' => eid(), 'nome' => $nome]);
    }

    $nomes = $_POST['etapa_nome'] ?? [];
    $dias = $_POST['etapa_dias'] ?? [];
    $tarefas = $_POST['etapa_tarefas'] ?? [];
    $ordem = 0;
    foreach ((array) $nomes as $i => $en) {
        $en = trim((string) $en);
        if ($en === '') {
            continue;
        }
        $etid = insert('template_etapa', [
            'template_id' => $id,
            'ordem' => ++$ordem,
            'nome' => $en,
            'duracao_dias' => max(0, (int) ($dias[$i] ?? 0)),
        ]);
        $linhas = preg_split('/\R/', (string) ($tarefas[$i] ?? '')) ?: [];
        $to = 0;
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }
            $partes = array_map('trim', explode(';', $linha, 2));
            insert('template_tarefa', [
                'etapa_id' => $etid,
                'ordem' => ++$to,
                'nome' => $partes[0],
                'horas' => parse_num($partes[1] ?? 0),
            ]);
        }
    }
    db()->commit();

    log_atividade("Salvou o template \"$nome\"");
    flash('Template salvo.');
    redirect('precificacao/template', ['id' => $id]);
}

function excluirTemplate(): void
{
    require_perm('precificacao', NIVEL_EDITAR);
    q('DELETE FROM template WHERE id = ? AND escritorio_id = ?', [input_int('id'), eid()]);
    flash('Template excluído.');
    redirect('precificacao/templates');
}
