<?php
$totalDias = (int) ((strtotime($ate) - strtotime($de)) / 86400) + 1;
$pos = function (string $d) use ($de, $totalDias): float {
    $dias = (strtotime($d) - strtotime($de)) / 86400;
    return max(0, min(100, $dias / $totalDias * 100));
};
$hojeDentro = today() >= $de && today() <= $ate;
$ant = date('Y-m-01', strtotime("$de -$meses months"));
$prox = date('Y-m-01', strtotime("$de +$meses months"));
$cabMeses = [];
for ($m = $de; $m <= $ate; $m = date('Y-m-01', strtotime("$m +1 month"))) {
    $fimMes = min($ate, date('Y-m-t', strtotime($m)));
    $cabMeses[] = [MESES_EXTENSO[(int) date('n', strtotime($m))] . ' ' . date('Y', strtotime($m)), $pos($m), $pos(add_days($fimMes, 1)) - $pos($m)];
}
$marcosPorProjeto = [];
foreach ($marcos as $mk) {
    $marcosPorProjeto[(int) $mk['projeto_id']][] = $mk;
}
$rotulos = ['feita' => 'Concluída', 'atrasada' => 'Atrasada', 'andamento' => 'Em andamento', 'futura' => 'A começar'];
?>
<div class="row" style="margin-bottom:14px">
  <a class="btn" href="<?= url('projetos/cronograma', ['de' => $ant, 'meses' => $meses, 'projeto' => $pid ?: null]) ?>">‹</a>
  <a class="btn btn-ghost" href="<?= url('projetos/cronograma', ['meses' => $meses, 'projeto' => $pid ?: null]) ?>">Hoje</a>
  <a class="btn" href="<?= url('projetos/cronograma', ['de' => $prox, 'meses' => $meses, 'projeto' => $pid ?: null]) ?>">›</a>
  <form class="row" method="get" action="<?= url('projetos/cronograma') ?>">
    <input type="hidden" name="de" value="<?= e($de) ?>">
    <select name="meses" data-autosubmit style="width:auto;margin:0">
      <?php foreach ([1, 3, 6, 12] as $n): ?><option value="<?= $n ?>"<?= selected($meses, $n) ?>><?= $n ?> <?= $n === 1 ? 'mês' : 'meses' ?></option><?php endforeach; ?>
    </select>
    <select name="projeto" data-autosubmit style="width:auto;margin:0">
      <option value="">Projetos ativos</option>
      <?php foreach ($todos as $t): ?><option value="<?= $t['id'] ?>"<?= selected($pid, $t['id']) ?>><?= e($t['codigo']) ?> · <?= e($t['nome']) ?></option><?php endforeach; ?>
    </select>
  </form>
  <div class="spacer"></div>
  <div class="legend">
    <span><i class="gantt-feita"></i>Concluída</span><span><i class="gantt-andamento"></i>Em andamento</span>
    <span><i class="gantt-atrasada"></i>Atrasada</span><span><i class="gantt-futura"></i>A começar</span>
  </div>
</div>

<div class="card gantt-wrap">
  <?php if (!$linhas): ?>
    <div class="empty"><p class="muted">Nenhum projeto com etapas neste período.</p></div>
  <?php else: ?>
  <div class="gantt">
    <div class="gantt-linha gantt-cab">
      <div class="gantt-nome"></div>
      <div class="gantt-trilho">
        <?php foreach ($cabMeses as [$nome, $esq, $larg]): ?>
          <span class="gantt-mes" style="left:<?= $esq ?>%;width:<?= $larg ?>%"><?= e($nome) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php foreach ($linhas as $l): $p = $l['p']; ?>
      <div class="gantt-linha gantt-projeto">
        <div class="gantt-nome"><a href="<?= url('projetos/ver', ['id' => $p['id']]) ?>"><strong><?= e($p['codigo']) ?></strong> <?= e($p['nome']) ?></a><br><small class="muted"><?= e($p['cliente_nome']) ?> · entrega <?= brdate($p['entrega']) ?></small></div>
        <div class="gantt-trilho">
          <?php if ($hojeDentro): ?><span class="gantt-hoje" style="left:<?= $pos(today()) ?>%"></span><?php endif; ?>
          <?php if ($p['entrega'] && $p['entrega'] >= $de && $p['entrega'] <= $ate): ?><span class="gantt-marco" style="left:<?= $pos($p['entrega']) ?>%" title="Entrega prevista: <?= brdate($p['entrega']) ?>">◆</span><?php endif; ?>
          <?php foreach ($marcosPorProjeto[(int) $p['id']] ?? [] as $mk): ?>
            <span class="gantt-marco gantt-marco-evento" style="left:<?= $pos(substr($mk['inicio'], 0, 10)) ?>%" title="<?= e($mk['titulo']) ?> · <?= brdate($mk['inicio']) ?>">◆</span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php foreach ($l['etapas'] as $et):
        if ($et['fim'] < $de || $et['inicio'] > $ate) continue;
        $esq = $pos($et['inicio']);
        $larg = max(0.8, $pos(add_days($et['fim'], 1)) - $esq); ?>
        <div class="gantt-linha">
          <div class="gantt-nome gantt-etapa"><?= e($et['nome']) ?> <small class="muted"><?= $et['pct'] ?>%</small></div>
          <div class="gantt-trilho">
            <?php if ($hojeDentro): ?><span class="gantt-hoje" style="left:<?= $pos(today()) ?>%"></span><?php endif; ?>
            <span class="gantt-barra gantt-<?= $et['situacao'] ?>" style="left:<?= $esq ?>%;width:<?= $larg ?>%"
                  title="<?= e($et['nome']) ?>: <?= brdate($et['inicio']) ?> a <?= brdate($et['fim']) ?> · <?= $rotulos[$et['situacao']] ?> · <?= $et['pct'] ?>% concluída">
              <span class="gantt-progresso" style="width:<?= $et['pct'] ?>%"></span>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
