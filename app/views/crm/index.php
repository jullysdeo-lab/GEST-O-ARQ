<?php require __DIR__ . '/_tabs.php'; ?>
<div class="grid grid-4">
  <div class="card kpi"><div class="label">Leads em aberto</div><div class="value"><?= $kpi['ativos'] ?></div>
    <div class="hint <?= $kpi['hoje'] ? 'erro' : '' ?>"><?= $kpi['hoje'] ?> retorno(s) para hoje ou atrasados</div></div>
  <div class="card kpi"><div class="label">Valor em negociação</div><div class="value"><?= money($kpi['pipeline']) ?></div><div class="hint">soma dos valores estimados</div></div>
  <div class="card kpi"><div class="label">Conversão</div><div class="value"><?= $kpi['conversao'] === null ? '—' : $kpi['conversao'] . '%' ?></div><div class="hint">ganhos ÷ decididos, últimos 6 meses</div></div>
  <a class="card kpi" href="<?= url('crm/relatorio') ?>" style="color:inherit;text-decoration:none"><div class="label">Esquecidos</div>
    <div class="value" style="<?= $kpi['esquecidos'] ? 'color:var(--warn)' : '' ?>"><?= $kpi['esquecidos'] ?></div><div class="hint">mais de <?= DIAS_LEAD_FRIO ?> dias sem contato</div></a>
</div>

<div class="row" style="margin:16px 0">
  <form class="row" method="get" action="<?= url('crm') ?>">
    <select name="responsavel" data-autosubmit style="width:auto;margin:0">
      <option value="">Todos os responsáveis</option>
      <?php foreach ($equipe as $m): ?><option value="<?= $m['id'] ?>"<?= selected($resp, $m['id']) ?>><?= e($m['nome']) ?></option><?php endforeach; ?>
    </select>
  </form>
  <small class="muted">Arraste os cartões entre as colunas para mudar a etapa.</small>
  <div class="spacer"></div>
  <?php if (can('crm', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('crm/form') ?>">+ Novo lead</a><?php endif; ?>
</div>

<div class="kanban" data-mover-url="<?= url('crm/mover') ?>">
  <?php foreach (FUNIL as $etapa => $nome):
    $lista = $colunas[$etapa];
    $soma = array_sum(array_map(fn($l) => (float) ($l['valor_proposta'] ?: $l['valor_estimado']), $lista)); ?>
    <section class="kanban-col kanban-<?= $etapa ?>" data-etapa="<?= $etapa ?>">
      <header>
        <strong><?= e($nome) ?></strong> <span class="badge"><?= count($lista) ?></span>
        <?php if ($soma > 0): ?><br><small class="muted"><?= money($soma) ?></small><?php endif; ?>
        <?php if (in_array($etapa, ['ganho', 'perdido'], true)): ?><br><small class="muted">últimos 30 dias</small><?php endif; ?>
      </header>
      <div class="kanban-lista">
        <?php foreach ($lista as $l) require __DIR__ . '/_card.php'; ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>
