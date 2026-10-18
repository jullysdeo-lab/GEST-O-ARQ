<?php
// Gráfico simples de barras receita × despesa. Espera $serie de serie_mensal().
$max = max(1, ...array_map(fn($m) => max($m['entrada'], $m['saida']), array_values($serie)));
?>
<div class="legend"><span><i style="background:var(--ok)"></i>Receita</span><span><i style="background:var(--accent)"></i>Despesa</span></div>
<div class="bars" role="img" aria-label="Receita e despesa realizadas por mês">
  <?php foreach ($serie as $m): ?>
    <div class="col">
      <div class="pair">
        <div class="bar in" style="height:<?= round($m['entrada'] / $max * 100) ?>%" title="Receita: <?= e(money($m['entrada'])) ?>"></div>
        <div class="bar out" style="height:<?= round($m['saida'] / $max * 100) ?>%" title="Despesa: <?= e(money($m['saida'])) ?>"></div>
      </div>
      <small class="muted"><?= e($m['label']) ?></small>
    </div>
  <?php endforeach; ?>
</div>
