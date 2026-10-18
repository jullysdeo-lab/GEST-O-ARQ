<?php require __DIR__ . '/_tabs.php';
$editar = can('briefings', NIVEL_EDITAR); ?>
<div class="card">
  <div class="card-head">
    <div>
      <h2>Modelos de briefing</h2>
      <p class="muted">Monte as perguntas de cada tipo de briefing. Mudar um modelo não altera briefings já enviados.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Modelo</th><th>Categoria</th><th class="num">Perguntas</th><th class="num">Enviados</th><th>Situação</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $m): ?>
        <tr>
          <td><a href="<?= url('briefings/modelo', ['id' => $m['id']]) ?>"><strong><?= e($m['nome']) ?></strong></a><br><small class="muted"><?= e($m['descricao']) ?></small></td>
          <td><?= e($m['categoria'] ?: '—') ?></td>
          <td class="num"><?= (int) $m['n'] ?></td>
          <td class="num"><?= (int) $m['usos'] ?></td>
          <td><span class="badge <?= $m['ativo'] ? 'badge-ok' : '' ?>"><?= $m['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="num">
            <a class="btn btn-sm" href="<?= url('briefings/modelo', ['id' => $m['id']]) ?>"><?= $editar ? 'Editar perguntas' : 'Ver' ?></a>
            <?php if ($editar): ?>
              <form class="inline" method="post" action="<?= url('briefings/modelo/duplicar') ?>">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button class="btn btn-sm btn-ghost" type="submit">Duplicar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($editar): ?>
    <form method="post" action="<?= url('briefings/modelo/salvar') ?>" class="form-grid" style="margin-top:16px;align-items:end">
      <?= csrf_field() ?>
      <label>Novo modelo<input name="nome" required placeholder="Ex.: Briefing de cozinha"></label>
      <label>Categoria<input name="categoria" placeholder="Residencial, Comercial…"></label>
      <label>Descrição<input name="descricao"></label>
      <button class="btn" type="submit">Criar modelo</button>
    </form>
  <?php endif; ?>
</div>
