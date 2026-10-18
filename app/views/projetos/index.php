<div class="card">
  <div class="card-head">
    <form class="row" method="get" action="<?= url('projetos') ?>">
      <select name="status" data-autosubmit style="width:auto">
        <option value="">Todos os status</option>
        <?php foreach (PROJETO_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($status, $k) ?>><?= $v ?></option><?php endforeach; ?>
      </select>
    </form>
    <div class="row">
      <a class="btn" href="<?= url('projetos/cronograma') ?>">Cronograma</a>
      <?php if (can('projetos', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('projetos/novo') ?>">+ Novo projeto</a><?php endif; ?>
    </div>
  </div>
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum projeto. O caminho mais rápido é aprovar um orçamento e criar o projeto a partir dele.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Projeto</th><th>Cliente</th><th>Status</th><th>Progresso</th><th class="num">Atrasadas</th><th>Início</th><th>Entrega</th><th class="num">Horas</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $p):
        $pct = $p['total_tarefas'] ? round($p['concluidas'] / $p['total_tarefas'] * 100) : 0;
        $atrasado = $p['status'] !== 'concluido' && $p['entrega'] && $p['entrega'] < today(); ?>
        <tr>
          <td><a href="<?= url('projetos/ver', ['id' => $p['id']]) ?>"><strong><?= e($p['codigo']) ?> · <?= e($p['nome']) ?></strong></a>
            <?php if ($p['orcamento_numero']): ?><br><small class="muted">do orçamento <?= e($p['orcamento_numero']) ?></small><?php endif; ?></td>
          <td><?= e($p['cliente_nome']) ?></td>
          <td><span class="badge badge-<?= $atrasado ? 'atrasado' : e($p['status']) ?>"><?= $atrasado ? 'Atrasado' : PROJETO_STATUS[$p['status']] ?></span></td>
          <td><div class="row" style="flex-wrap:nowrap"><div class="progress" style="flex:1"><span style="width:<?= $pct ?>%"></span></div><small><?= $pct ?>%</small></div>
            <small class="muted"><?= (int) $p['concluidas'] ?>/<?= (int) $p['total_tarefas'] ?> tarefas</small></td>
          <td class="num"><?= $p['atrasadas'] ? '<span class="badge badge-atrasado">' . (int) $p['atrasadas'] . '</span>' : '—' ?></td>
          <td><?= brdate($p['inicio']) ?></td>
          <td><?= brdate($p['entrega']) ?></td>
          <td class="num"><?= num($p['horas']) ?> h</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
