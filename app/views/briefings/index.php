<?php require __DIR__ . '/_tabs.php'; ?>
<div class="card">
  <div class="card-head">
    <form class="row" method="get" action="<?= url('briefings') ?>">
      <select name="status" data-autosubmit style="width:auto;margin:0">
        <option value="">Todas as situações</option>
        <?php foreach (BRIEFING_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($status, $k) ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
    </form>
    <?php if (can('briefings', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('briefings/novo') ?>">+ Enviar briefing</a><?php endif; ?>
  </div>
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum briefing enviado ainda. Escolha um modelo e mande o link para o cliente responder.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Briefing</th><th>Cliente</th><th>Situação</th><th>Respostas</th><th>Enviado</th><th>Respondido</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $b): $pct = $b['total'] ? round($b['respondidas'] / $b['total'] * 100) : 0; ?>
        <tr>
          <td><a href="<?= url('briefings/ver', ['id' => $b['id']]) ?>"><strong><?= e($b['titulo']) ?></strong></a></td>
          <td><?= e($b['cliente_nome']) ?></td>
          <td><span class="badge badge-<?= $b['status'] === 'respondido' ? 'ok' : 'warn' ?>"><?= BRIEFING_STATUS[$b['status']] ?></span></td>
          <td><div class="row" style="flex-wrap:nowrap"><div class="progress" style="flex:1"><span style="width:<?= $pct ?>%"></span></div><small><?= $b['respondidas'] ?>/<?= $b['total'] ?></small></div></td>
          <td><?= brdate($b['enviado_em']) ?></td>
          <td><?= brdate($b['respondido_em']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
