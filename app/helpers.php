<?php
declare(strict_types=1);

function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function money(mixed $v): string
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}

function num(mixed $v, int $dec = 1): string
{
    return number_format((float) $v, $dec, ',', '.');
}

// Aceita "1.234,56", "1234.56" ou "1234,56".
function parse_num(mixed $v): float
{
    $s = trim((string) $v);
    if ($s === '') {
        return 0.0;
    }
    if (str_contains($s, ',')) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    }
    return (float) preg_replace('/[^0-9.\-]/', '', $s);
}

function brdate(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : '—';
}

function today(): string
{
    return date('Y-m-d');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function add_days(string $date, int $days): string
{
    return date('Y-m-d', strtotime("$date +$days days"));
}

function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return rtrim($dir, '/');
}

function url(string $path = '', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    if ($query) {
        $u .= '?' . http_build_query($query);
    }
    return $u;
}

/** URL completa (com domínio), para links enviados ao cliente. */
function url_absoluta(string $path = '', array $query = []): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . url($path, $query);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query));
    exit;
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function input(string $key, mixed $default = null): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key): int
{
    return (int) input($key, 0);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    if (!is_post()) {
        return;
    }
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente de novo.');
    }
}

function view(string $name, array $data = [], ?string $layout = 'layout'): void
{
    extract($data);
    ob_start();
    require APP_ROOT . "/app/views/$name.php";
    $content = ob_get_clean();
    if ($layout === null) {
        echo $content;
        return;
    }
    require APP_ROOT . "/app/views/$layout.php";
}

function json_out(mixed $data): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function not_found(): never
{
    http_response_code(404);
    view('erro', ['titulo' => 'Página não encontrada', 'mensagem' => 'O endereço que você abriu não existe.']);
    exit;
}

function log_atividade(string $acao): void
{
    $u = current_user();
    if (!$u) {
        return;
    }
    insert('atividade', [
        'escritorio_id' => $u['escritorio_id'],
        'usuario_id' => $u['id'],
        'acao' => $acao,
        'criado_em' => now(),
    ]);
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function initials(string $nome): string
{
    $parts = preg_split('/\s+/', trim($nome)) ?: [];
    $ini = mb_substr($parts[0] ?? '', 0, 1);
    if (count($parts) > 1) {
        $ini .= mb_substr(end($parts), 0, 1);
    }
    return mb_strtoupper($ini);
}

/** Status de vencimento de um lançamento financeiro. */
function status_vencimento(array $l): array
{
    if ($l['status'] === 'realizado') {
        return ['realizado', $l['tipo'] === 'entrada' ? 'Recebido' : 'Pago'];
    }
    $hoje = today();
    if ($l['vencimento'] < $hoje) {
        return ['atrasado', 'Atrasado'];
    }
    if ($l['vencimento'] === $hoje) {
        return ['hoje', 'Vence hoje'];
    }
    return ['avencer', 'A vencer'];
}

const PROJETO_STATUS = [
    'planejamento' => 'Planejamento',
    'em_andamento' => 'Em andamento',
    'pausado' => 'Pausado',
    'concluido' => 'Concluído',
];

const TAREFA_STATUS = [
    'a_fazer' => 'A fazer',
    'em_andamento' => 'Em andamento',
    'concluida' => 'Concluída',
];

const ORCAMENTO_STATUS = [
    'rascunho' => 'Rascunho',
    'enviado' => 'Enviado',
    'aprovado' => 'Aprovado',
    'recusado' => 'Recusado',
    'arquivado' => 'Arquivado',
];

const PRIORIDADES = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
