<?php
declare(strict_types=1);

const FILTROS_TAREFA = [
    'abertas' => 'Em aberto',
    'atrasadas' => 'Atrasadas',
    'hoje' => 'Vencem hoje',
    'semana' => 'Próximos 7 dias',
    'minhas' => 'Minhas tarefas',
    'concluidas' => 'Concluídas',
];

function index(): void
{
    require_perm('projetos');
    $filtro = (string) input('filtro', 'abertas');
    if (!isset(FILTROS_TAREFA[$filtro])) {
        $filtro = 'abertas';
    }
    $hoje = today();
    $where = 'p.escritorio_id = ?';
    $params = [eid()];
    switch ($filtro) {
        case 'atrasadas':
            $where .= " AND t.status <> 'concluida' AND t.prazo < ?";
            $params[] = $hoje;
            break;
        case 'hoje':
            $where .= " AND t.status <> 'concluida' AND t.prazo = ?";
            $params[] = $hoje;
            break;
        case 'semana':
            $where .= " AND t.status <> 'concluida' AND t.prazo BETWEEN ? AND ?";
            array_push($params, $hoje, add_days($hoje, 7));
            break;
        case 'minhas':
            $where .= " AND t.status <> 'concluida' AND t.responsavel_id = ?";
            $params[] = current_user()['id'];
            break;
        case 'concluidas':
            $where .= " AND t.status = 'concluida'";
            break;
        default:
            $where .= " AND t.status <> 'concluida' AND p.status <> 'concluido'";
    }
    $lista = all(
        "SELECT t.*, p.codigo, p.nome AS projeto_nome, p.id AS projeto_id, c.nome AS cliente_nome, u.nome AS responsavel_nome
         FROM tarefa t
         JOIN projeto p ON p.id = t.projeto_id
         JOIN cliente c ON c.id = p.cliente_id
         LEFT JOIN usuario u ON u.id = t.responsavel_id
         WHERE $where
         ORDER BY CASE WHEN t.prazo IS NULL THEN 1 ELSE 0 END, t.prazo, t.id
         LIMIT 500",
        $params
    );
    view('tarefas/index', ['titulo' => 'Tarefas', 'lista' => $lista, 'filtro' => $filtro]);
}

function tarefa_do_escritorio(int $id): array
{
    $t = one(
        'SELECT t.* FROM tarefa t JOIN projeto p ON p.id = t.projeto_id WHERE t.id = ? AND p.escritorio_id = ?',
        [$id, eid()]
    );
    if (!$t) {
        not_found();
    }
    return $t;
}

function voltar(int $projetoId): never
{
    if (input('voltar') === 'projeto') {
        redirect('projetos/ver', ['id' => $projetoId]);
    }
    redirect('tarefas', ['filtro' => (string) input('filtro', 'abertas')]);
}

/** Cria uma tarefa (com projeto_id) ou altera um campo de uma tarefa existente (com id). */
function salvar(): void
{
    require_perm('projetos', NIVEL_EDITAR);
    $id = input_int('id');

    if ($id) {
        $t = tarefa_do_escritorio($id);
        $campo = (string) input('campo');
        $valor = input('valor');
        $permitidos = ['responsavel_id', 'prazo', 'prioridade', 'nome'];
        if (in_array($campo, $permitidos, true)) {
            if ($campo === 'responsavel_id') {
                $valor = $valor && one('SELECT id FROM usuario WHERE id = ? AND escritorio_id = ?', [(int) $valor, eid()]) ? (int) $valor : null;
            } elseif ($campo === 'prazo') {
                $valor = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $valor) ? $valor : null;
            } elseif ($campo === 'prioridade' && !isset(PRIORIDADES[$valor])) {
                $valor = 'media';
            }
            update('tarefa', [$campo => $valor], 'id = :id', ['id' => $id]);
        }
        voltar((int) $t['projeto_id']);
    }

    $pid = input_int('projeto_id');
    if (!one('SELECT id FROM projeto WHERE id = ? AND escritorio_id = ?', [$pid, eid()])) {
        not_found();
    }
    $nome = (string) input('nome');
    if ($nome === '') {
        flash('Dê um nome à tarefa.', 'erro');
        voltar($pid);
    }
    $etapa = (string) input('etapa_nome') ?: 'Geral';
    $ordem = val('SELECT etapa_ordem FROM tarefa WHERE projeto_id = ? AND etapa_nome = ? LIMIT 1', [$pid, $etapa]);
    if ($ordem === null) {
        $ordem = (int) val('SELECT COALESCE(MAX(etapa_ordem), 0) FROM tarefa WHERE projeto_id = ?', [$pid]) + 1;
    }
    $prazo = (string) input('prazo');
    $prioridade = (string) input('prioridade', 'media');
    insert('tarefa', [
        'projeto_id' => $pid,
        'etapa_ordem' => (int) $ordem,
        'etapa_nome' => $etapa,
        'nome' => $nome,
        'prazo' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $prazo) ? $prazo : null,
        'prioridade' => isset(PRIORIDADES[$prioridade]) ? $prioridade : 'media',
        'status' => 'a_fazer',
        'horas_previstas' => parse_num(input('horas_previstas')),
    ]);
    voltar($pid);
}

function mudarStatus(): void
{
    require_perm('projetos');
    $t = tarefa_do_escritorio(input_int('id'));
    // Quem só tem "Ver" pode atualizar as próprias tarefas.
    if (!can('projetos', NIVEL_EDITAR) && (int) $t['responsavel_id'] !== (int) current_user()['id']) {
        require_perm('projetos', NIVEL_EDITAR);
    }
    $st = (string) input('status');
    if (isset(TAREFA_STATUS[$st])) {
        update('tarefa', [
            'status' => $st,
            'concluida_em' => $st === 'concluida' ? now() : null,
        ], 'id = :id', ['id' => $t['id']]);
        if ($st === 'concluida') {
            log_atividade("Concluiu a tarefa \"{$t['nome']}\"");
        }
    }
    voltar((int) $t['projeto_id']);
}

function excluir(): void
{
    require_perm('projetos', NIVEL_EDITAR);
    $t = tarefa_do_escritorio(input_int('id'));
    q('DELETE FROM tarefa WHERE id = ?', [$t['id']]);
    voltar((int) $t['projeto_id']);
}
