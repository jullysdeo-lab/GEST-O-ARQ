<?php
// Página de erro: usa o layout completo só se houver usuário logado.
if (!current_user()) {
    echo '<!doctype html><meta charset="utf-8"><title>' . e($titulo) . '</title>'
        . '<link rel="stylesheet" href="' . asset('app.css') . '">'
        . '<body class="login-page"><div class="login-card"><h1>' . e($titulo) . '</h1><p>' . e($mensagem)
        . '</p><a class="btn btn-primary" href="' . url('login') . '">Ir para o login</a></div></body>';
    exit;
}
?>
<div class="empty card">
  <h2><?= e($titulo) ?></h2>
  <p class="muted"><?= e($mensagem) ?></p>
  <a class="btn btn-primary" href="<?= url('') ?>">Voltar ao painel</a>
</div>
