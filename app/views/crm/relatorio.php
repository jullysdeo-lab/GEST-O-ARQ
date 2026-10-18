<?php require __DIR__ . '/_tabs.php';
$porEtapa = array_column($etapas, null, 'funil_etapa');
$totalLeads = array_sum(array_column($etapas, 'total'));
?>
<form class="row" method="get" action="<?= url('crm/relatorio') ?>" style="margin-bottom:16px">
  <label class="row" style="font-weight:400">Leads cadastrados desde<input type="date" name="desde" value="<?= e($desde) ?>" style="width:auto;margin:0"></label>
  <button class="btn" type="submit">Atualizar</button>
</form>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h2>Funil</h2><small class="muted"><?= $totalLeads ?> leads no período</small></div>
    <ul class="list">
      <?php foreach (FUNIL as $k => $nome): $n = (int) ($porEtapa[$k]['total'] ?? 0); $pct = $totalLeads ? $n / $totalLeads * 100 : 0; ?>
        <li>
          <span style="flex:1"><?= e($nome) ?><div class="progress" style="margin-top:4px"><span style="width:<?= round($pct) ?>%"></span></div></span>
          <span class="num" style="min-width:120px;text-align:right"><?= $n ?><br><small class="muted"><?= money($porEtapa[$k]['valor'] ?? 0) ?></small></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="card">
    <div class="card-head"><h2>De onde vêm os leads</h2></div>
    <?php if (!$origens): ?><p class="muted">Sem leads no período.</p><?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Origem</th><th class="num">Leads</th><th class="num">Ganhos</th><th class="num">Perdidos</th><th class="num">Conversão</th></tr></thead>
        <tbody>
        <?php foreach ($origens as $o): $dec = $o['ganhos'] + $o['perdidos']; ?>
          <tr><td><?= e($o['origem']) ?></td><td class="num"><?= (int) $o['total'] ?></td><td class="num"><?= (int) $o['ganhos'] ?></td><td class="num"><?= (int) $o['perdidos'] ?></td>
            <td class="num"><?= $dec ? round($o['ganhos'] / $dec * 100) . '%' : '—' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>Por que perdemos</h2></div>
    <?php if (!$motivos): ?><p class="muted">Nenhum lead perdido no período.</p><?php else: $tm = array_sum(array_column($motivos, 'total')); ?>
      <ul class="list">
        <?php foreach ($motivos as $m): ?>
          <li><span style="flex:1"><?= e($m['motivo']) ?><div class="progress" style="margin-top:4px"><span style="width:<?= round($m['total'] / $tm * 100) ?>%"></span></div></span><span class="num"><?= (int) $m['total'] ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>Leads esquecidos</h2><small class="muted">mais de <?= DIAS_LEAD_FRIO ?> dias sem contato</small></div>
    <?php if (!$esquecidos): ?><p class="muted">Nenhum. Todos os leads em aberto tiveram contato recente.</p><?php else: ?>
      <ul class="list">
        <?php foreach ($esquecidos as $l): ?>
          <li><a style="flex:1" href="<?= url('crm/lead', ['id' => $l['id']]) ?>"><?= e($l['nome']) ?></a>
            <small class="muted"><?= e(FUNIL[$l['funil_etapa']] ?? '') ?></small>
            <span class="badge badge-warn"><?= dias_desde($l['referencia']) ?> dias</span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
