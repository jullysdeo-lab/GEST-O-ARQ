<?php
declare(strict_types=1);

const MODULOS = [
    'central' => 'Painel',
    'agenda' => 'Agenda',
    'crm' => 'CRM',
    'clientes' => 'Clientes',
    'precificacao' => 'Precificação',
    'projetos' => 'Projetos',
    'financeiro' => 'Financeiro',
    'documentos' => 'Documentos',
    'briefings' => 'Briefings',
    'equipe' => 'Equipe',
];

const PERFIS = [
    'senior' => 'Sênior',
    'administrativo' => 'Administrativo',
    'gestor_projetos' => 'Gestor de Projetos',
    'colaborador' => 'Colaborador',
];

const NIVEL_SEM = 0;
const NIVEL_VER = 1;
const NIVEL_EDITAR = 2;

const NIVEIS = [NIVEL_SEM => 'Sem acesso', NIVEL_VER => 'Ver', NIVEL_EDITAR => 'Editar'];

function start_session(): void
{
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('gestaoarq');
    session_start();

    $timeout = (int) config('session_timeout', 7200);
    if (!empty($_SESSION['uid']) && time() - ($_SESSION['last_seen'] ?? 0) > $timeout) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['expired'] = true;
    }
    $_SESSION['last_seen'] = time();
}

function attempt_login(string $email, string $senha): bool
{
    $u = one('SELECT * FROM usuario WHERE email = ? AND ativo = 1', [mb_strtolower($email)]);
    if (!$u || !password_verify($senha, $u['senha_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    update('usuario', ['ultimo_acesso' => now()], 'id = :id', ['id' => $u['id']]);
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $uid = $_SESSION['uid'] ?? null;
    $user = $uid ? one('SELECT * FROM usuario WHERE id = ? AND ativo = 1', [$uid]) : null;
    return $user;
}

function eid(): int
{
    // Nas páginas públicas (link do cliente) o escritório vem do token, não do login.
    if (isset($GLOBALS['eid_publico'])) {
        return (int) $GLOBALS['eid_publico'];
    }
    return (int) current_user()['escritorio_id'];
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('login');
    }
    return $u;
}

/**
 * Nível de acesso do usuário a um módulo.
 * Sênior e Administrativo têm acesso total; Gestor de Projetos não vê o Financeiro;
 * Colaborador usa a tabela de permissões (padrão: só ver Projetos e o Painel).
 */
function nivel(string $modulo, ?array $u = null): int
{
    $u ??= current_user();
    if (!$u) {
        return NIVEL_SEM;
    }
    switch ($u['perfil']) {
        case 'senior':
        case 'administrativo':
            return NIVEL_EDITAR;
        case 'gestor_projetos':
            return match ($modulo) {
                'financeiro' => NIVEL_SEM,
                'equipe' => NIVEL_VER,
                default => NIVEL_EDITAR,
            };
        default:
            $n = val('SELECT nivel FROM permissao WHERE usuario_id = ? AND modulo = ?', [$u['id'], $modulo]);
            if ($n !== null) {
                return (int) $n;
            }
            // A agenda é aberta a todos; cada um cria os próprios compromissos.
            if ($modulo === 'agenda') {
                return NIVEL_EDITAR;
            }
            return in_array($modulo, ['central', 'projetos'], true) ? NIVEL_VER : NIVEL_SEM;
    }
}

function can(string $modulo, int $minimo = NIVEL_VER): bool
{
    return nivel($modulo) >= $minimo;
}

function proibido(string $mensagem = 'Você não tem permissão para esta ação.'): never
{
    http_response_code(403);
    view('erro', ['titulo' => 'Sem permissão', 'mensagem' => $mensagem]);
    exit;
}

function require_perm(string $modulo, int $minimo = NIVEL_VER): void
{
    if (!can($modulo, $minimo)) {
        http_response_code(403);
        view('erro', [
            'titulo' => 'Sem permissão',
            'mensagem' => 'Você não tem acesso a esta área. Peça a um Sênior para liberar em Equipe → Permissões.',
        ]);
        exit;
    }
}
