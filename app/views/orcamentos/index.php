<div class="grid grid-4">
  <?php foreach (['rascunho' => 'Rascunhos', 'enviado' => 'Enviados', 'aprovado' => 'Aprovados', 'recusado' => 'Recusados'] as $k => $label): ?>
    <a class="card kpi" href="<?= url('orcamentos', ['status' => $k]) ?>" style="color:inherit;text-decoration:none">
      <div class="label"><?= $label ?></div>
      <div class="value"><?= (int) ($resumo[$k]['n'] ?? 0) ?></div>
      <div class="hint"><?= money($resumo[$k]['v'] ?? 0) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-head">
    <form class="row" method="get" action="<?= url('orcamentos') ?>">
      <select name="status" data-autosubmit style="width:auto">
        <option value="">Todos (menos arquivados)</option>
        <?php foreach (ORCAMENTO_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($status, $k) ?>><?= $v ?></option><?php endforeach; ?>
      </select>
    </form>
    <?php if (can('precificacao', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('orcamentos/novo') ?>">+ Novo orçamento</a><?php endif; ?>
  </div>
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum orçamento por aqui.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Número</th><th>Título</th><th>Cliente</th><th>Status</th><th class="num">Horas</th><th class="num">Valor</th><th>Criado</th><th>Aprovado</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $o): ?>
        <tr>
          <td><a href="<?= url('orcamentos/ver', ['id' => $o['id']]) ?>"><strong><?= e($o['numero']) ?></strong></a></td>
          <td><?= e($o['titulo']) ?></td>
          <td><?= e($o['cliente_nome']) ?></td>
          <td><span class="badge badge-<?= e($o['status']) ?>"><?= ORCAMENTO_STATUS[$o['status']] ?></span></td>
          <td class="num"><?= num($o['horas_total']) ?> h</td>
          <td class="num"><?= money($o['valor_total']) ?></td>
          <td><?= brdate($o['criado_em']) ?></td>
          <td><?= brdate($o['aprovado_em']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
