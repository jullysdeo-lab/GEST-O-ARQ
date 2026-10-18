<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar · <?= e(config('app_name')) ?></title>
<link rel="stylesheet" href="<?= asset('app.css') ?>">
<script>try{if(localStorage.getItem('tema')==='escuro')document.documentElement.dataset.theme='dark'}catch(e){}</script>
</head>
<body class="login-page">
  <form class="login-card" method="post" action="<?= url('login') ?>">
    <?= csrf_field() ?>
    <div class="brand">
      <span class="brand-mark"><?= e(mb_substr(config('app_name'), 0, 1)) ?></span>
      <strong><?= e(config('app_name')) ?></strong>
    </div>
    <h1>Entrar</h1>
    <p class="muted">Gestão de projetos, orçamentos e financeiro do seu escritório.</p>

    <?php if ($expirou): ?>
      <div class="flash flash-warn">Sua sessão foi encerrada depois de 2 horas sem uso. Entre de novo.</div>
    <?php endif; ?>
    <?php if ($erro): ?>
      <div class="flash flash-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <label>E-mail
      <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">
    </label>
    <label>Senha
      <input type="password" name="senha" required autocomplete="current-password">
    </label>
    <button class="btn btn-primary btn-block" type="submit">Entrar</button>
  </form>
</body>
</html>
