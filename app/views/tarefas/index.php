<div class="tabs">
  <?php foreach (FILTROS_TAREFA as $k => $v): ?>
    <a href="<?= url('tarefas', ['filtro' => $k]) ?>" class="<?= $filtro === $k ? 'active' : '' ?>"><?= $v ?></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nada por aqui.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Tarefa</th><th>Projeto</th><th>Responsável</th><th>Prazo</th><th>Prioridade</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $t):
        $atrasada = $t['status'] !== 'concluida' && $t['prazo'] && $t['prazo'] < today();
        $podeMudar = can('projetos', NIVEL_EDITAR) || (int) $t['responsavel_id'] === (int) current_user()['id']; ?>
        <tr>
          <td><strong><?= e($t['nome']) ?></strong><br><small class="muted"><?= e($t['etapa_nome']) ?></small></td>
          <td><a href="<?= url('projetos/ver', ['id' => $t['projeto_id']]) ?>"><?= e($t['codigo']) ?> · <?= e($t['projeto_nome']) ?></a><br><small class="muted"><?= e($t['cliente_nome']) ?></small></td>
          <td><?= e($t['responsavel_nome'] ?: '—') ?></td>
          <td><?= $atrasada ? '<span class="badge badge-atrasado">' . brdate($t['prazo']) . '</span>' : brdate($t['prazo']) ?></td>
          <td><span class="badge badge-<?= e($t['prioridade']) ?>"><?= PRIORIDADES[$t['prioridade']] ?? '' ?></span></td>
          <td>
            <?php if ($podeMudar): ?>
            <form method="post" action="<?= url('tarefas/status') ?>" class="inline">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="filtro" value="<?= e($filtro) ?>">
              <select name="status" data-autosubmit style="margin:0;min-width:130px">
                <?php foreach (TAREFA_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($t['status'], $k) ?>><?= $v ?></option><?php endforeach; ?>
              </select>
            </form>
            <?php else: ?><span class="badge badge-<?= e($t['status']) ?>"><?= TAREFA_STATUS[$t['status']] ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
