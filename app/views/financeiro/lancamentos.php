<?php require __DIR__ . '/_tabs.php';
$editar = can('financeiro', NIVEL_EDITAR);
$total = array_sum(array_map(fn($l) => (float) $l['valor'], $lista));
$filtrosVolta = ['situacao' => $situacao, 'de' => $de, 'ate' => $ate];
?>
<form class="row" method="get" action="<?= url('financeiro/lancamentos') ?>" style="margin-bottom:16px">
  <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
  <select name="situacao" style="width:auto;margin:0">
    <option value="">Todas as situações</option>
    <option value="atrasado"<?= selected($situacao, 'atrasado') ?>>Atrasados (qualquer data)</option>
    <option value="hoje"<?= selected($situacao, 'hoje') ?>>Vence hoje</option>
    <option value="avencer"<?= selected($situacao, 'avencer') ?>>A vencer</option>
    <option value="realizado"<?= selected($situacao, 'realizado') ?>><?= $tipo === 'entrada' ? 'Recebidos' : 'Pagos' ?></option>
  </select>
  <input type="date" name="de" value="<?= e($de) ?>" style="width:auto;margin:0">
  <input type="date" name="ate" value="<?= e($ate) ?>" style="width:auto;margin:0">
  <input name="busca" value="<?= e($busca) ?>" placeholder="Buscar descrição, cliente ou projeto" style="width:240px;margin:0">
  <button class="btn" type="submit">Filtrar</button>
  <div class="spacer"></div>
  <?php if ($editar): ?><a class="btn btn-primary" href="<?= url('financeiro/form', ['tipo' => $tipo]) ?>">+ Nova <?= $tipo === 'entrada' ? 'entrada' : 'saída' ?></a><?php endif; ?>
</form>

<div class="card">
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum lançamento com esses filtros.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Vencimento</th><th>Descrição</th><th>Cliente / Projeto</th><th>Categoria</th><th>Situação</th><th class="num">Valor</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $l): [$cls, $rot] = status_vencimento($l); ?>
        <tr>
          <td><?= brdate($l['vencimento']) ?></td>
          <td><strong><?= e($l['descricao']) ?></strong><?php if ($l['forma_pagamento']): ?><br><small class="muted"><?= e($l['forma_pagamento']) ?></small><?php endif; ?></td>
          <td><?= e($l['cliente_nome'] ?: '—') ?><?php if ($l['projeto_codigo']): ?><br><small class="muted"><?= e($l['projeto_codigo']) ?> · <?= e($l['projeto_nome']) ?></small><?php endif; ?></td>
          <td><?= e($l['categoria'] ?: '—') ?></td>
          <td><span class="badge badge-<?= $cls ?>"><?= $rot ?></span><?php if ($l['realizado_em']): ?><br><small class="muted">em <?= brdate($l['realizado_em']) ?></small><?php endif; ?></td>
          <td class="num"><?= money($l['valor']) ?></td>
          <td class="num">
            <?php if ($editar): ?>
              <div class="row" style="justify-content:flex-end;flex-wrap:nowrap">
              <?php if ($l['status'] !== 'realizado'): ?>
                <form class="inline" method="post" action="<?= url('financeiro/baixar') ?>">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <?php foreach ($filtrosVolta as $fk => $fv): ?><input type="hidden" name="<?= $fk ?>" value="<?= e($fv) ?>"><?php endforeach; ?>
                  <button class="btn btn-sm btn-primary" type="submit"><?= $tipo === 'entrada' ? 'Recebido' : 'Pago' ?></button>
                </form>
              <?php else: ?>
                <form class="inline" method="post" action="<?= url('financeiro/estornar') ?>" data-confirm="Voltar este lançamento para aguardando?">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <?php foreach ($filtrosVolta as $fk => $fv): ?><input type="hidden" name="<?= $fk ?>" value="<?= e($fv) ?>"><?php endforeach; ?>
                  <button class="btn btn-sm" type="submit">Desfazer baixa</button>
                </form>
              <?php endif; ?>
              <a class="btn btn-sm" href="<?= url('financeiro/form', ['id' => $l['id']]) ?>">Editar</a>
              <form class="inline" method="post" action="<?= url('financeiro/duplicar') ?>">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>">
                <?php foreach ($filtrosVolta as $fk => $fv): ?><input type="hidden" name="<?= $fk ?>" value="<?= e($fv) ?>"><?php endforeach; ?>
                <button class="btn btn-sm btn-ghost" type="submit" title="Duplicar para o mês seguinte">Duplicar</button>
              </form>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="5"><?= count($lista) ?> lançamento(s)</td><td class="num"><?= money($total) ?></td><td></td></tr></tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>
