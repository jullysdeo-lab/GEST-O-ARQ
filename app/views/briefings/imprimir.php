<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($b['titulo']) ?></title>
<link rel="stylesheet" href="<?= asset('app.css') ?>">
</head>
<body style="background:#eee">
<div class="no-print" style="text-align:center;padding:16px"><button class="btn btn-primary" onclick="window.print()">Imprimir ou salvar em PDF</button></div>
<div class="print-doc">
  <div style="display:flex;justify-content:space-between;border-bottom:2px solid #222;padding-bottom:12px">
    <div><strong><?= e($esc) ?></strong><br><small>Relatório de briefing</small></div>
    <div style="text-align:right"><small>Respondido em <?= $b['respondido_em'] ? e(date('d/m/Y', strtotime($b['respondido_em']))) : '—' ?></small></div>
  </div>
  <h1 style="font-size:22px;margin:20px 0 4px"><?= e($b['titulo']) ?></h1>
  <div>Cliente: <strong><?= e($b['cliente_nome']) ?></strong></div>
  <?php require __DIR__ . '/_respostas.php'; ?>
</div>
</body>
</html>
