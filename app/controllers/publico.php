<?php
declare(strict_types=1);

/*
 * Páginas abertas ao cliente por link, sem login.
 * O acesso é dado pelo token aleatório na URL; cada página só mostra o que o token libera.
 */

function token_valido(): string
{
    $t = (string) input('t', '');
    if (!preg_match('/^[a-f0-9]{48}$/', $t)) {
        link_invalido();
    }
    return $t;
}

function link_invalido(): never
{
    http_response_code(404);
    view('publico/aviso', [
        'titulo' => 'Link indisponível',
        'mensagem' => 'Este link não existe mais ou já foi usado. Peça um novo link ao escritório.',
        'escritorio' => null,
    ], 'publico/layout');
    exit;
}

function nome_escritorio(int $eid): string
{
    $GLOBALS['eid_publico'] = $eid;
    return conf_texto('esc_razao') ?: (string) val('SELECT nome FROM escritorio WHERE id = ?', [$eid]);
}

/** Leitura e aceite eletrônico de um documento. */
function assinar(): void
{
    $t = token_valido();
    $d = one(
        "SELECT d.*, c.nome AS cliente_nome FROM documento d JOIN cliente c ON c.id = d.cliente_id WHERE d.token = ?",
        [$t]
    );
    if (!$d || !in_array($d['status'], ['enviado', 'assinado'], true)) {
        link_invalido();
    }
    $esc = nome_escritorio((int) $d['escritorio_id']);
    $erro = null;

    if (is_post() && $d['status'] === 'enviado') {
        $nome = trim((string) input('nome'));
        $doc = preg_replace('/\D/', '', (string) input('documento'));
        if (mb_strlen($nome) < 5 || !in_array(strlen($doc), [11, 14], true) || !input('aceite')) {
            $erro = 'Informe o nome completo, um CPF (11 dígitos) ou CNPJ (14 dígitos) e marque a confirmação.';
        } else {
            // A assinatura só vale para o texto exatamente como foi lido.
            if (hash('sha256', $d['conteudo']) !== (string) input('versao')) {
                $erro = 'O documento foi alterado enquanto você lia. Recarregue a página e leia a versão atual.';
            } else {
                update('documento', [
                    'status' => 'assinado',
                    'assinado_em' => now(),
                    'assinado_nome' => mb_substr($nome, 0, 150),
                    'assinado_documento' => $doc,
                    'assinado_ip' => cliente_ip(),
                    'assinado_agente' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'assinado_hash' => hash('sha256', $d['conteudo']),
                ], "id = :id AND status = 'enviado'", ['id' => $d['id']]);
                insert('atividade', [
                    'escritorio_id' => $d['escritorio_id'],
                    'usuario_id' => null,
                    'acao' => "{$nome} assinou eletronicamente: {$d['titulo']}",
                    'criado_em' => now(),
                ]);
                registrar_interacao((int) $d['cliente_id'], 'nota', "Assinou eletronicamente: {$d['titulo']}.");
                redirect('assinar', ['t' => $t]);
            }
        }
    }

    view('publico/assinar', ['titulo' => $d['titulo'], 'd' => $d, 'escritorio' => $esc, 'erro' => $erro, 't' => $t], 'publico/layout');
}

/** Normaliza a resposta conforme o tipo da pergunta; respostas fora das opções são descartadas. */
function limpar_resposta(array $p, mixed $v): string|array
{
    switch ($p['tipo']) {
        case 'multipla':
            $v = is_array($v) ? $v : [];
            return array_values(array_intersect($p['opcoes'], array_map('strval', $v)));
        case 'opcao_unica':
            return in_array((string) $v, $p['opcoes'], true) ? (string) $v : '';
        case 'sim_nao':
            return in_array($v, ['sim', 'nao'], true) ? $v : '';
        case 'escala':
            $n = (int) $v;
            return $n >= 1 && $n <= 5 ? (string) $n : '';
        case 'numero':
            $s = trim((string) $v);
            return $s === '' ? '' : (string) parse_num($s);
        case 'data':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : '';
        default:
            return is_string($v) ? mb_substr(trim($v), 0, $p['tipo'] === 'texto' ? 500 : 5000) : '';
    }
}

/** O cliente responde o briefing; pode salvar e continuar depois. */
function briefing(): void
{
    $t = token_valido();
    $b = one('SELECT b.*, c.nome AS cliente_nome FROM briefing b JOIN cliente c ON c.id = b.cliente_id WHERE b.token = ?', [$t]);
    if (!$b) {
        link_invalido();
    }
    $esc = nome_escritorio((int) $b['escritorio_id']);
    $perguntas = json_decode($b['perguntas_json'], true) ?: [];
    $respostas = json_decode((string) $b['respostas_json'], true) ?: [];
    $faltando = [];

    if ($b['status'] === 'respondido') {
        view('publico/aviso', [
            'titulo' => 'Briefing enviado',
            'mensagem' => 'Suas respostas já foram enviadas para ' . $esc . '. Se precisar mudar algo, fale com o escritório.',
            'escritorio' => $esc,
        ], 'publico/layout');
        return;
    }

    if (is_post()) {
        $enviadas = $_POST['r'] ?? [];
        if (!is_array($enviadas)) {
            $enviadas = [];
        }
        foreach ($perguntas as $p) {
            $respostas[$p['k']] = limpar_resposta($p, $enviadas[$p['k']] ?? '');
        }
        $finalizar = input('acao') === 'enviar';
        if ($finalizar) {
            foreach ($perguntas as $p) {
                $r = $respostas[$p['k']] ?? '';
                if ($p['obrigatoria'] && ($r === '' || $r === [])) {
                    $faltando[] = $p['k'];
                }
            }
        }
        $dados = ['respostas_json' => json_encode($respostas, JSON_UNESCAPED_UNICODE)];
        if ($finalizar && !$faltando) {
            $dados['status'] = 'respondido';
            $dados['respondido_em'] = now();
        }
        update('briefing', $dados, 'id = :id', ['id' => $b['id']]);

        if ($finalizar && !$faltando) {
            insert('atividade', [
                'escritorio_id' => $b['escritorio_id'],
                'usuario_id' => null,
                'acao' => "{$b['cliente_nome']} respondeu o briefing \"{$b['titulo']}\"",
                'criado_em' => now(),
            ]);
            registrar_interacao((int) $b['cliente_id'], 'nota', "Respondeu o briefing \"{$b['titulo']}\".");
            view('publico/aviso', [
                'titulo' => 'Obrigado!',
                'mensagem' => 'Suas respostas foram enviadas para ' . $esc . '. Em breve entraremos em contato.',
                'escritorio' => $esc,
            ], 'publico/layout');
            return;
        }
        if (!$finalizar) {
            $salvo = true;
        }
    }

    view('publico/briefing', [
        'titulo' => $b['titulo'],
        'b' => $b,
        'perguntas' => $perguntas,
        'respostas' => $respostas,
        'faltando' => $faltando,
        'salvo' => $salvo ?? false,
        'escritorio' => $esc,
        't' => $t,
    ], 'publico/layout');
}

/** O cliente completa o próprio cadastro (usado nos contratos). */
function cadastro(): void
{
    $t = token_valido();
    $c = one('SELECT * FROM cliente WHERE token_dados = ?', [$t]);
    if (!$c) {
        link_invalido();
    }
    $esc = nome_escritorio((int) $c['escritorio_id']);
    $erro = null;

    if (is_post()) {
        $dados = [
            'nome' => mb_substr(trim((string) input('nome')), 0, 150),
            'documento' => mb_substr(trim((string) input('documento')), 0, 30),
            'email' => mb_substr(trim((string) input('email')), 0, 190),
            'telefone' => mb_substr(trim((string) input('telefone')), 0, 40),
            'endereco' => mb_substr(trim((string) input('endereco')), 0, 255),
        ];
        if ($dados['nome'] === '' || $dados['documento'] === '' || $dados['endereco'] === '') {
            $erro = 'Preencha pelo menos nome, CPF/CNPJ e endereço.';
        } elseif ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erro = 'O e-mail informado não é válido.';
        } else {
            $dados['dados_atualizados_em'] = now();
            $dados['token_dados'] = null; // link de uso único
            update('cliente', $dados, 'id = :id', ['id' => $c['id']]);
            insert('atividade', [
                'escritorio_id' => $c['escritorio_id'],
                'usuario_id' => null,
                'acao' => "{$dados['nome']} atualizou os próprios dados cadastrais",
                'criado_em' => now(),
            ]);
            view('publico/aviso', [
                'titulo' => 'Dados enviados',
                'mensagem' => 'Obrigado! Seus dados foram enviados para ' . $esc . '.',
                'escritorio' => $esc,
            ], 'publico/layout');
            return;
        }
        $c = array_merge($c, $dados);
    }

    view('publico/cadastro', ['titulo' => 'Seus dados', 'c' => $c, 'escritorio' => $esc, 'erro' => $erro, 't' => $t], 'publico/layout');
}
