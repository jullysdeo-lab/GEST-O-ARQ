<div class="card">
  <div class="card-head">
    <form class="row" method="get" action="<?= url('clientes') ?>">
      <input name="busca" value="<?= e($busca) ?>" placeholder="Buscar por nome, e-mail ou telefone" style="width:280px">
      <select name="tipo" data-autosubmit style="width:auto">
        <option value="">Clientes e prospectos</option>
        <option value="cliente"<?= selected($tipo, 'cliente') ?>>Só clientes</option>
        <option value="prospecto"<?= selected($tipo, 'prospecto') ?>>Só prospectos</option>
      </select>
      <button class="btn" type="submit">Buscar</button>
    </form>
    <?php if (can('clientes', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('clientes/form') ?>">+ Novo cadastro</a><?php endif; ?>
  </div>
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum cadastro encontrado.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nome</th><th>Tipo</th><th>Contato</th><th>Origem</th><th class="num">Orçamentos</th><th class="num">Projetos</th><th class="num">Faturado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $c): ?>
        <tr>
          <td><?php if (can('crm')): ?><a href="<?= url('crm/lead', ['id' => $c['id']]) ?>"><strong><?= e($c['nome']) ?></strong></a><?php else: ?><strong><?= e($c['nome']) ?></strong><?php endif; ?></td>
          <td><span class="badge <?= $c['tipo'] === 'cliente' ? 'badge-ok' : 'badge-info' ?>"><?= $c['tipo'] === 'cliente' ? 'Cliente' : 'Prospecto' ?></span></td>
          <td><?= e($c['email'] ?: '') ?><?= $c['email'] && $c['telefone'] ? '<br>' : '' ?><small class="muted"><?= e($c['telefone']) ?></small></td>
          <td><?= e($c['origem'] ?: '—') ?></td>
          <td class="num"><?= (int) $c['orcamentos'] ?></td>
          <td class="num"><?= (int) $c['projetos'] ?></td>
          <td class="num"><?= money($c['faturado']) ?></td>
          <td class="num">
            <?php if (can('precificacao', NIVEL_EDITAR)): ?><a class="btn btn-sm" href="<?= url('orcamentos/novo', ['cliente_id' => $c['id']]) ?>">Orçar</a><?php endif; ?>
            <?php if (can('clientes', NIVEL_EDITAR)): ?><a class="btn btn-sm" href="<?= url('clientes/form', ['id' => $c['id']]) ?>">Editar</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
