<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proposta <?= e($o['numero']) ?></title>
<link rel="stylesheet" href="<?= asset('app.css') ?>">
<style>
  body { background: #eee; }
  .print-doc h1 { font-size: 26px; margin-bottom: 4px; }
  .print-doc h2 { margin-top: 28px; font-size: 15px; text-transform: uppercase; letter-spacing: .05em; color: #b5532f; }
  .print-doc ul { margin: 6px 0 0; padding-left: 18px; }
  .print-doc .etapa { margin-top: 14px; }
  .print-doc .valor { font-size: 28px; font-weight: 700; margin-top: 8px; }
  .print-doc .topo { display: flex; justify-content: space-between; border-bottom: 2px solid #222; padding-bottom: 14px; }
</style>
</head>
<body>
<div class="no-print" style="text-align:center;padding:16px"><button class="btn btn-primary" onclick="window.print()">Imprimir ou salvar em PDF</button></div>
<div class="print-doc">
  <div class="topo">
    <div><strong><?= e($escritorio) ?></strong><br><small>Proposta de serviços de arquitetura</small></div>
    <div style="text-align:right"><strong><?= e($o['numero']) ?></strong><br><small><?= date('d/m/Y') ?></small></div>
  </div>

  <h1 style="margin-top:24px"><?= e($o['titulo']) ?></h1>
  <div>Para: <strong><?= e($o['cliente_nome']) ?></strong><?= $o['area_m2'] ? ' · Área: ' . num($o['area_m2'], 2) . ' m²' : '' ?></div>

  <h2>Escopo</h2>
  <?php foreach ($etapas as $n => $et): ?>
    <div class="etapa">
      <strong><?= $n ?>. <?= e($et['nome']) ?></strong> <small>(prazo estimado: <?= (int) $et['dias'] ?> dias)</small>
      <ul><?php foreach ($et['tarefas'] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endforeach; ?>

  <h2>Investimento</h2>
  <div class="valor"><?= money($o['valor_total']) ?></div>
  <div><?= (int) $o['parcelas'] > 1 ? $o['parcelas'] . ' parcelas de ' . money($o['valor_total'] / $o['parcelas']) : 'Pagamento único' ?></div>

  <?php if ($o['observacoes']): ?>
    <h2>Observações</h2>
    <p><?= nl2br(e($o['observacoes'])) ?></p>
  <?php endif; ?>

  <p style="margin-top:40px"><small>Proposta válida por 15 dias a partir da data de emissão.</small></p>
</div>
</body>
</html>
