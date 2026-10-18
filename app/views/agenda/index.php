<?php
$qs = function (array $mudar) use ($modo, $mes, $uid, $camadas, $inicio) {
    // c[] vazio precisa ir na URL, senão a agenda volta às camadas padrão.
    $base = ['modo' => $modo, 'mes' => $mes, 'pessoa' => $uid ?? 0, 'c' => $camadas ?: ['']];
    if ($modo === 'lista') {
        $base['de'] = $inicio;
    }
    return array_merge($base, $mudar);
};
$mesAnt = date('Y-m', strtotime("$mes-01 -1 month"));
$mesProx = date('Y-m', strtotime("$mes-01 +1 month"));
$nomeMes = MESES_EXTENSO[(int) substr($mes, 5, 2)] . ' de ' . substr($mes, 0, 4);
$hoje = today();
?>
<div class="row" style="margin-bottom:14px">
  <?php if ($modo === 'mes'): ?>
    <a class="btn" href="<?= url('agenda', $qs(['mes' => $mesAnt])) ?>" aria-label="Mês anterior">‹</a>
    <h2 style="font-size:20px;min-width:200px;text-align:center;text-transform:capitalize"><?= e($nomeMes) ?></h2>
    <a class="btn" href="<?= url('agenda', $qs(['mes' => $mesProx])) ?>" aria-label="Próximo mês">›</a>
    <a class="btn btn-ghost" href="<?= url('agenda', $qs(['mes' => date('Y-m')])) ?>">Hoje</a>
  <?php else: ?>
    <a class="btn" href="<?= url('agenda', $qs(['de' => add_days($inicio, -30)])) ?>">‹</a>
    <h2 style="font-size:18px">De <?= brdate($inicio) ?> a <?= brdate($fim) ?></h2>
    <a class="btn" href="<?= url('agenda', $qs(['de' => add_days($inicio, 30)])) ?>">›</a>
    <a class="btn btn-ghost" href="<?= url('agenda', $qs(['de' => today()])) ?>">Hoje</a>
  <?php endif; ?>
  <div class="spacer"></div>
  <div class="segmented">
    <a href="<?= url('agenda', $qs(['modo' => 'mes'])) ?>" class="<?= $modo === 'mes' ? 'active' : '' ?>">Mês</a>
    <a href="<?= url('agenda', $qs(['modo' => 'lista', 'de' => today()])) ?>" class="<?= $modo === 'lista' ? 'active' : '' ?>">Lista</a>
  </div>
  <a class="btn" href="<?= url('agenda/sincronizar') ?>">Sincronizar</a>
  <?php if (can('agenda', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('agenda/form') ?>">+ Compromisso</a><?php endif; ?>
</div>

<form class="card row agenda-filtros" method="get" action="<?= url('agenda') ?>" style="margin-bottom:14px;padding:10px 14px">
  <input type="hidden" name="modo" value="<?= e($modo) ?>"><input type="hidden" name="mes" value="<?= e($mes) ?>"><input type="hidden" name="c[]" value="">
  <?php if ($modo === 'lista'): ?><input type="hidden" name="de" value="<?= e($inicio) ?>"><?php endif; ?>
  <select name="pessoa" data-autosubmit style="width:auto;margin:0">
    <option value="0"<?= $uid === null ? ' selected' : '' ?>>Equipe toda</option>
    <?php foreach ($equipe as $m): ?><option value="<?= $m['id'] ?>"<?= selected($uid, $m['id']) ?>><?= (int) $m['id'] === (int) current_user()['id'] ? 'Minha agenda' : e($m['nome']) ?></option><?php endforeach; ?>
  </select>
  <?php foreach (CAMADAS_AGENDA as $k => $nome):
    if (($k === 'financeiro' && !can('financeiro')) || ($k === 'crm' && !can('crm')) || ($k === 'tarefas' && !can('projetos'))) continue; ?>
    <label style="font-weight:400;margin:0"><input type="checkbox" name="c[]" value="<?= $k ?>" data-autosubmit <?= in_array($k, $camadas, true) ? 'checked' : '' ?>><?= e($nome) ?></label>
  <?php endforeach; ?>
</form>

<?php if ($modo === 'mes'): ?>
  <div class="cal">
    <?php foreach (DIAS_SEMANA as $d): ?><div class="cal-cab"><?= $d ?></div><?php endforeach; ?>
    <?php for ($d = $inicio; $d <= $fim; $d = add_days($d, 1)):
      $foraMes = substr($d, 0, 7) !== $mes;
      $lista = $itens[$d] ?? []; ?>
      <div class="cal-dia<?= $foraMes ? ' fora' : '' ?><?= $d === $hoje ? ' hoje' : '' ?>">
        <div class="cal-num">
          <?php if (can('agenda', NIVEL_EDITAR)): ?><a href="<?= url('agenda/form', ['data' => $d]) ?>" title="Novo compromisso em <?= brdate($d) ?>"><?= (int) substr($d, 8, 2) ?></a>
          <?php else: ?><?= (int) substr($d, 8, 2) ?><?php endif; ?>
        </div>
        <?php foreach (array_slice($lista, 0, 4) as $it): ?>
          <a class="cal-item cal-<?= e($it['tipo']) ?>" href="<?= e($it['url']) ?>" style="--cor:<?= e($it['cor']) ?>" title="<?= e($it['titulo'] . ($it['sub'] ? ' — ' . $it['sub'] : '')) ?>">
            <?= $it['hora'] ? '<b>' . e($it['hora']) . '</b> ' : '' ?><?= e($it['titulo']) ?>
          </a>
        <?php endforeach; ?>
        <?php if (count($lista) > 4): ?>
          <a class="cal-mais" href="<?= url('agenda', $qs(['modo' => 'lista', 'de' => $d])) ?>">+<?= count($lista) - 4 ?> mais</a>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
<?php else: ?>
  <div class="card">
    <?php if (!$itens): ?>
      <div class="empty"><p class="muted">Nada nos próximos 30 dias com esses filtros.</p></div>
    <?php endif; ?>
    <?php foreach ($itens as $dia => $lista):
      $t = strtotime($dia); ?>
      <div class="agenda-dia<?= $dia === $hoje ? ' hoje' : '' ?>">
        <div class="agenda-data">
          <strong><?= date('d', $t) ?></strong>
          <small><?= DIAS_SEMANA[(int) date('N', $t) - 1] ?> · <?= MESES_CURTOS[(int) date('n', $t)] ?></small>
        </div>
        <ul class="list" style="flex:1">
          <?php foreach ($lista as $it): ?>
            <li>
              <span class="ponto" style="background:<?= e($it['cor']) ?>"></span>
              <span style="min-width:48px" class="muted"><?= e($it['hora'] ?? 'dia') ?></span>
              <span style="flex:1"><a href="<?= e($it['url']) ?>"><?= e($it['titulo']) ?></a><?php if ($it['sub']): ?><br><small class="muted"><?= e($it['sub']) ?></small><?php endif; ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="legend" style="margin-top:12px;flex-wrap:wrap">
  <?php foreach (TIPOS_EVENTO as [$nome, $cor]): ?><span><i style="background:<?= $cor ?>"></i><?= e($nome) ?></span><?php endforeach; ?>
  <span><i style="background:#a39b8f"></i>Prazo de tarefa</span><span><i style="background:#c2410c"></i>Retorno do CRM</span>
</div>
