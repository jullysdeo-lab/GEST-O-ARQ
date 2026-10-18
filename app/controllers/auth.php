<?php
declare(strict_types=1);

function login(): void
{
    if (current_user()) {
        redirect('');
    }
    $erro = null;
    $expirou = !empty($_SESSION['expired']);
    unset($_SESSION['expired']);

    if (is_post()) {
        // Atraso fixo dificulta tentativas de senha em massa.
        usleep(300000);
        if (attempt_login((string) input('email'), (string) ($_POST['senha'] ?? ''))) {
            redirect('');
        }
        $erro = 'E-mail ou senha incorretos.';
    }
    view('auth/login', ['erro' => $erro, 'expirou' => $expirou, 'email' => input('email', '')], null);
}

function sair(): void
{
    logout();
    redirect('login');
}
