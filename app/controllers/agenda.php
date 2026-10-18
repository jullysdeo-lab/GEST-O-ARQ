<?php
declare(strict_types=1);

const CAMADAS_AGENDA = [
    'eventos' => 'Compromissos',
    'tarefas' => 'Prazos de tarefas',
    'crm' => 'Retornos do CRM',
    'financeiro' => 'Vencimentos',
];

const DIAS_SEMANA = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

function carregar_evento(int $id): array
{
    $e = one(
        'SELECT e.*, c.nome AS cliente_nome, p.codigo AS projeto_codigo, p.nome AS projeto_nome, u.nome AS criador_nome
         FROM evento e LEFT JOIN cliente c ON c.id = e.cliente_id LEFT JOIN projeto p ON p.id = e.projeto_id
         LEFT JOIN usuario u ON u.id = e.criado_por
         WHERE e.id = ? AND e.escritorio_id = ?',
        [$id, eid()]
    );
    if (!$e) {
        not_found();
    }
    $e['participantes'] = all(
        'SELECT u.id, u.nome FROM evento_participante ep JOIN usuario u ON u.id = ep.usuario_id WHERE ep.evento_id = ? ORDER BY u.nome',
        [$id]
    );
    return $e;
}

/** Quem criou o evento, ou um Sênior/Administrativo, pode alterá-lo. */
function pode_editar_evento(array $e): bool
{
    $u = current_user();
    return can('agenda', NIVEL_EDITAR)
        && ((int) $e['criado_por'] === (int) $u['id'] || in_array($u['perfil'], ['senior', 'administrativo'], true));
}

function filtros_agenda(): array
{
    $pessoa = input('pessoa', null);
    $uid = $pessoa === null ? (int) current_user()['id'] : (int) $pessoa; // padrão: minha agenda
    $camadas = $_GET['c'] ?? ['eventos', 'tarefas', 'crm'];
    $camadas = array_values(array_intersect(array_keys(CAMADAS_AGENDA), (array) $camadas));
    return [$uid ?: null, $camadas];
}

function index(): void
{
    require_perm('agenda');
    [$uid, $camadas] = filtros_agenda();
    $modo = input('modo') === 'lista' ? 'lista' : 'mes';
    $mes = (string) input('mes', date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        $mes = date('Y-m');
    }

    if ($modo === 'mes') {
        $primeiro = "$mes-01";
        // A grade começa na segunda-feira da semana do dia 1 e termina no domingo da última semana.
        $inicio = date('Y-m-d', strtotime($primeiro . ' -' . ((int) date('N', strtotime($primeiro)) - 1) . ' days'));
        $ultimo = date('Y-m-t', strtotime($primeiro));
        $fim = date('Y-m-d', strtotime($ultimo . ' +' . (7 - (int) date('N', strtotime($ultimo))) . ' days'));
    } else {
        $inicio = (string) input('de', today());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
            $inicio = today();
        }
        $fim = add_days($inicio, 30);
    }

    view('agenda/index', [
        'titulo' => 'Agenda',
        'modo' => $modo,
        'mes' => $mes,
        'inicio' => $inicio,
        'fim' => $fim,
        'uid' => $uid,
        'camadas' => $camadas,
        'itens' => itens_agenda($inicio, $fim, $uid, $camadas),
        'equipe' => all('SELECT id, nome FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]),
    ]);
}

function evento(): void
{
    require_perm('agenda');
    $e = carregar_evento(input_int('id'));
    view('agenda/evento', ['titulo' => $e['titulo'], 'e' => $e, 'podeEditar' => pode_editar_evento($e)]);
}

function form(): void
{
    require_perm('agenda', NIVEL_EDITAR);
    $id = input_int('id');
    $e = $id ? carregar_evento($id) : null;
    if ($e && !pode_editar_evento($e)) {
        proibido('Só quem criou o compromisso, ou um Sênior/Administrativo, pode alterá-lo.');
    }
    $data = (string) input('data', today());
    view('agenda/form', [
        'titulo' => $e ? 'Editar compromisso' : 'Novo compromisso',
        'e' => $e ?? [
            'id' => '',
            'titulo' => (string) input('titulo', ''),
            'tipo' => (string) input('tipo', 'reuniao'),
            'inicio' => (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : today()) . ' 09:00:00',
            'fim' => (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : today()) . ' 10:00:00',
            'dia_todo' => 0,
            'local' => '',
            'descricao' => '',
            'cliente_id' => input_int('cliente_id') ?: null,
            'projeto_id' => input_int('projeto_id') ?: null,
            'participantes' => [['id' => current_user()['id']]],
        ],
        'equipe' => all('SELECT id, nome FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]),
        'clientes' => all('SELECT id, nome FROM cliente WHERE escritorio_id = ? ORDER BY nome', [eid()]),
        'projetos' => all("SELECT id, codigo, nome FROM projeto WHERE escritorio_id = ? AND status <> 'concluido' ORDER BY id DESC", [eid()]),
    ]);
}

function salvar(): void
{
    require_perm('agenda', NIVEL_EDITAR);
    $id = input_int('id');
    $atual = $id ? carregar_evento($id) : null;
    if ($atual && !pode_editar_evento($atual)) {
        proibido('Só quem criou o compromisso, ou um Sênior/Administrativo, pode alterá-lo.');
    }

    $titulo = trim((string) input('titulo'));
    $data = (string) input('data');
    $dataFim = (string) input('data_fim') ?: $data;
    $diaTodo = input('dia_todo') ? 1 : 0;
    $hi = (string) input('hora_inicio', '09:00');
    $hf = (string) input('hora_fim', '10:00');
    $okData = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    $okHora = fn($h) => (bool) preg_match('/^\d{2}:\d{2}$/', $h);
    $volta = fn() => redirect('agenda/form', $id ? ['id' => $id] : ['data' => $okData($data) ? $data : today()]);

    if ($titulo === '' || !$okData($data) || !$okData($dataFim) || (!$diaTodo && (!$okHora($hi) || !$okHora($hf)))) {
        flash('Preencha título, data e horários.', 'erro');
        $volta();
    }
    $inicio = $diaTodo ? "$data 00:00:00" : "$data $hi:00";
    $fim = $diaTodo ? "$dataFim 23:59:59" : "$dataFim $hf:00";
    if ($fim <= $inicio) {
        flash('O fim precisa ser depois do início.', 'erro');
        $volta();
    }

    $tipo = (string) input('tipo');
    $clienteId = input_int('cliente_id') ?: null;
    $projetoId = input_int('projeto_id') ?: null;
    if ($clienteId && !one('SELECT id FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()])) {
        $clienteId = null;
    }
    if ($projetoId) {
        $p = one('SELECT id, cliente_id FROM projeto WHERE id = ? AND escritorio_id = ?', [$projetoId, eid()]);
        $projetoId = $p ? (int) $p['id'] : null;
        $clienteId = $clienteId ?: ($p ? (int) $p['cliente_id'] : null);
    }
    $dados = [
        'titulo' => mb_substr($titulo, 0, 160),
        'tipo' => isset(TIPOS_EVENTO[$tipo]) ? $tipo : 'reuniao',
        'inicio' => $inicio,
        'fim' => $fim,
        'dia_todo' => $diaTodo,
        'local' => mb_substr((string) input('local'), 0, 200),
        'descricao' => (string) input('descricao'),
        'cliente_id' => $clienteId,
        'projeto_id' => $projetoId,
    ];

    $participantes = array_map('intval', (array) ($_POST['participantes'] ?? []));
    $validos = $participantes
        ? array_map('intval', array_column(all(
            'SELECT id FROM usuario WHERE escritorio_id = ? AND ativo = 1 AND id IN (' . implode(',', array_fill(0, count($participantes), '?')) . ')',
            array_merge([eid()], $participantes)
        ), 'id'))
        : [];

    db()->beginTransaction();
    if ($atual) {
        update('evento', $dados, 'id = :id', ['id' => $id]);
        q('DELETE FROM evento_participante WHERE evento_id = ?', [$id]);
    } else {
        $dados += ['escritorio_id' => eid(), 'criado_por' => current_user()['id'], 'criado_em' => now()];
        $id = insert('evento', $dados);
    }
    foreach ($validos as $uid) {
        insert('evento_participante', ['evento_id' => $id, 'usuario_id' => $uid]);
    }
    db()->commit();

    // Registro no histórico do cliente, quando o compromisso é com ele.
    if (!$atual && $clienteId && in_array($dados['tipo'], ['reuniao', 'visita'], true)) {
        registrar_interacao($clienteId, $dados['tipo'], 'Agendado: ' . $dados['titulo'] . ' em ' . date('d/m/Y H:i', strtotime($inicio)) . '.', now());
    }

    // Conflitos de horário dos participantes (só avisa, não impede).
    $conflitos = [];
    if (!$diaTodo && $validos) {
        $conflitos = all(
            'SELECT DISTINCT u.nome, e.titulo FROM evento e
             JOIN evento_participante ep ON ep.evento_id = e.id JOIN usuario u ON u.id = ep.usuario_id
             WHERE e.escritorio_id = ? AND e.id <> ? AND e.dia_todo = 0 AND e.inicio < ? AND e.fim > ?
               AND ep.usuario_id IN (' . implode(',', array_fill(0, count($validos), '?')) . ')',
            array_merge([eid(), $id, $fim, $inicio], $validos)
        );
    }
    if ($conflitos) {
        flash('Compromisso salvo, mas há choque de horário: ' . implode('; ', array_map(fn($c) => "{$c['nome']} já tem \"{$c['titulo']}\"", $conflitos)) . '.', 'warn');
    } else {
        flash('Compromisso salvo.');
    }
    log_atividade(($atual ? 'Alterou' : 'Agendou') . " \"{$dados['titulo']}\" em " . date('d/m H:i', strtotime($inicio)));
    redirect('agenda/evento', ['id' => $id]);
}

function excluir(): void
{
    require_perm('agenda', NIVEL_EDITAR);
    $e = carregar_evento(input_int('id'));
    if (!pode_editar_evento($e)) {
        proibido('Só quem criou o compromisso, ou um Sênior/Administrativo, pode alterá-lo.');
    }
    q('DELETE FROM evento WHERE id = ?', [$e['id']]);
    log_atividade("Cancelou \"{$e['titulo']}\"");
    flash('Compromisso excluído.');
    redirect('agenda', ['mes' => substr($e['inicio'], 0, 7)]);
}

/** Gera (ou troca) o link pessoal de assinatura do calendário. */
function token(): void
{
    require_perm('agenda');
    update('usuario', ['token_agenda' => novo_token()], 'id = :id', ['id' => current_user()['id']]);
    flash('Novo link de calendário criado. O link antigo deixou de funcionar.');
    redirect('agenda/sincronizar');
}

function sincronizar(): void
{
    require_perm('agenda');
    $u = current_user();
    view('agenda/sincronizar', [
        'titulo' => 'Agenda',
        'link' => $u['token_agenda'] ? url_absoluta('agenda.ics', ['t' => $u['token_agenda']]) : null,
    ]);
}

function ics_texto(string $s): string
{
    return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $s);
}

/** Linhas do iCalendar têm no máximo 75 octetos; as continuações começam com um espaço. */
function ics_dobrar(string $linha): string
{
    $out = '';
    $limite = 75;
    while (strlen($linha) > $limite) {
        $corte = $limite;
        while ($corte > 0 && (ord($linha[$corte]) & 0xC0) === 0x80) {
            $corte--; // não corta no meio de um caractere UTF-8
        }
        $out .= substr($linha, 0, $corte) . "\r\n ";
        $linha = substr($linha, $corte);
        $limite = 74; // o espaço inicial conta
    }
    return $out . $linha;
}

/** Feed iCalendar pessoal (assinatura no Google Agenda, Apple, Outlook). Acesso pelo token. */
function ics(): void
{
    $t = (string) input('t', '');
    $u = preg_match('/^[a-f0-9]{48}$/', $t) ? one('SELECT * FROM usuario WHERE token_agenda = ? AND ativo = 1', [$t]) : null;
    if (!$u) {
        http_response_code(404);
        exit('Link de calendário inválido.');
    }
    $GLOBALS['eid_publico'] = (int) $u['escritorio_id'];
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $de = date('Y-m-d', strtotime('-60 days'));
    $ate = date('Y-m-d', strtotime('+365 days'));

    $linhas = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . config('app_name') . '//Agenda//PT-BR', 'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH', 'X-WR-CALNAME:' . ics_texto(config('app_name') . ' · ' . $u['nome']), 'X-WR-TIMEZONE:' . config('timezone'),
    ];
    $eventos = all(
        'SELECT e.*, c.nome AS cliente_nome FROM evento e LEFT JOIN cliente c ON c.id = e.cliente_id
         WHERE e.escritorio_id = ? AND e.inicio <= ? AND e.fim >= ?
           AND (e.criado_por = ? OR EXISTS (SELECT 1 FROM evento_participante ep WHERE ep.evento_id = e.id AND ep.usuario_id = ?))',
        [$u['escritorio_id'], "$ate 23:59:59", "$de 00:00:00", $u['id'], $u['id']]
    );
    $tz = new DateTimeZone(config('timezone'));
    $utc = fn(string $local) => (new DateTime($local, $tz))->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    foreach ($eventos as $e) {
        $linhas[] = 'BEGIN:VEVENT';
        $linhas[] = "UID:evento-{$e['id']}@$host";
        $linhas[] = 'DTSTAMP:' . $utc($e['criado_em']);
        if ($e['dia_todo']) {
            $linhas[] = 'DTSTART;VALUE=DATE:' . date('Ymd', strtotime($e['inicio']));
            $linhas[] = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime(substr($e['fim'], 0, 10) . ' +1 day'));
        } else {
            $linhas[] = 'DTSTART:' . $utc($e['inicio']);
            $linhas[] = 'DTEND:' . $utc($e['fim']);
        }
        $linhas[] = 'SUMMARY:' . ics_texto($e['titulo']);
        if ($e['local']) {
            $linhas[] = 'LOCATION:' . ics_texto($e['local']);
        }
        $desc = trim(($e['cliente_nome'] ? 'Cliente: ' . $e['cliente_nome'] . "\n" : '') . (string) $e['descricao']);
        if ($desc !== '') {
            $linhas[] = 'DESCRIPTION:' . ics_texto($desc);
        }
        $linhas[] = 'END:VEVENT';
    }
    // Prazos das tarefas da pessoa, como eventos de dia inteiro.
    $tarefas = all(
        "SELECT t.id, t.nome, t.prazo, p.codigo FROM tarefa t JOIN projeto p ON p.id = t.projeto_id
         WHERE p.escritorio_id = ? AND t.responsavel_id = ? AND t.status <> 'concluida' AND t.prazo BETWEEN ? AND ?",
        [$u['escritorio_id'], $u['id'], $de, $ate]
    );
    foreach ($tarefas as $t2) {
        array_push(
            $linhas,
            'BEGIN:VEVENT',
            "UID:tarefa-{$t2['id']}@$host",
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:' . date('Ymd', strtotime($t2['prazo'])),
            'DTEND;VALUE=DATE:' . date('Ymd', strtotime($t2['prazo'] . ' +1 day')),
            'SUMMARY:' . ics_texto("Prazo: {$t2['nome']} ({$t2['codigo']})"),
            'TRANSP:TRANSPARENT',
            'END:VEVENT'
        );
    }
    $linhas[] = 'END:VCALENDAR';

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="agenda.ics"');
    echo implode("\r\n", array_map('ics_dobrar', $linhas)) . "\r\n";
    exit;
}
