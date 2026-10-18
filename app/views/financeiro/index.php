<?php require __DIR__ . '/_tabs.php'; ?>
<form class="row" method="get" action="<?= url('financeiro') ?>" style="margin-bottom:16px">
  <label class="row" style="font-weight:400">De<input type="date" name="de" value="<?= e($de) ?>" style="width:auto;margin:0"></label>
  <label class="row" style="font-weight:400;margin-top:0">Até<input type="date" name="ate" value="<?= e($ate) ?>" style="width:auto;margin:0"></label>
  <button class="btn" type="submit">Filtrar</button>
  <div class="spacer"></div>
  <?php if (can('financeiro', NIVEL_EDITAR)): ?>
    <a class="btn" href="<?= url('financeiro/form', ['tipo' => 'saida']) ?>">+ Saída</a>
    <a class="btn btn-primary" href="<?= url('financeiro/form', ['tipo' => 'entrada']) ?>">+ Entrada</a>
  <?php endif; ?>
</form>

<div class="grid grid-3">
  <div class="card kpi"><div class="label">Saldo em caixa</div><div class="value"><?= money($saldo) ?></div><div class="hint">tudo que já entrou − tudo que já saiu</div></div>
  <div class="card kpi"><div class="label">Entradas no período</div><div class="value"><?= money($k['recebido']) ?></div><div class="hint">recebido · <?= money($k['a_receber']) ?> a receber</div></div>
  <div class="card kpi"><div class="label">Saídas no período</div><div class="value"><?= money($k['pago']) ?></div><div class="hint">pago · <?= money($k['a_pagar']) ?> a pagar</div></div>
</div>
<?php if ($k['receber_atrasado'] > 0 || $k['pagar_atrasado'] > 0): ?>
  <div class="grid grid-2" style="margin-top:16px">
    <a class="card kpi" href="<?= url('financeiro/lancamentos', ['tipo' => 'entrada', 'situacao' => 'atrasado']) ?>" style="color:inherit;text-decoration:none">
      <div class="label">A receber em atraso</div><div class="value" style="color:var(--erro)"><?= money($k['receber_atrasado']) ?></div></a>
    <a class="card kpi" href="<?= url('financeiro/lancamentos', ['tipo' => 'saida', 'situacao' => 'atrasado']) ?>" style="color:inherit;text-decoration:none">
      <div class="label">A pagar em atraso</div><div class="value" style="color:var(--erro)"><?= money($k['pagar_atrasado']) ?></div></a>
  </div>
<?php endif; ?>

<div class="grid grid-2" style="margin-top:16px">
  <div class="card">
    <div class="card-head"><h2>Evolução mensal</h2><small class="muted">últimos 6 meses, valores realizados</small></div>
    <?php require APP_ROOT . '/app/views/_barras.php'; ?>
  </div>
  <div class="card">
    <div class="card-head"><h2>Despesas por categoria</h2><small class="muted">no período</small></div>
    <?php if (!$categorias): ?>
      <p class="muted">Sem despesas no período.</p>
    <?php else: $totalCat = array_sum(array_column($categorias, 'total')); ?>
      <ul class="list">
        <?php foreach ($categorias as $c): $pct = $totalCat > 0 ? $c['total'] / $totalCat * 100 : 0; ?>
          <li>
            <span style="flex:1"><?= e($c['categoria'] ?: 'Sem categoria') ?><div class="progress" style="margin-top:4px"><span style="width:<?= round($pct) ?>%"></span></div></span>
            <span class="num" style="min-width:110px;text-align:right"><?= money($c['total']) ?><br><small class="muted"><?= num($pct) ?>%</small></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
