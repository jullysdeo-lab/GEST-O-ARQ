<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title><?= e($titulo ?? '') ?><?= !empty($escritorio) ? ' · ' . e($escritorio) : '' ?></title>
<link rel="stylesheet" href="<?= asset('app.css') ?>">
</head>
<body>
<div class="publico">
  <?php if (!empty($escritorio)): ?>
    <div class="publico-topo">
      <span class="brand-mark"><?= e(mb_strtoupper(mb_substr($escritorio, 0, 1))) ?></span>
      <strong><?= e($escritorio) ?></strong>
    </div>
  <?php endif; ?>
  <?= $content ?>
</div>
<script src="<?= asset('app.js') ?>"></script>
</body>
</html>
