<?php $editar = can('documentos', NIVEL_EDITAR); ?>
<div class="grid grid-side">
  <form class="card" method="post" action="<?= url('documentos/modelo', ['tipo' => $tipo]) ?>">
    <?= csrf_field() ?>
    <label>Texto do modelo
      <textarea name="conteudo" rows="32" style="font-family:ui-monospace,Consolas,monospace;font-size:13px" <?= $editar ? '' : 'readonly' ?>><?= e($conteudo) ?></textarea>
    </label>
    <?php if ($editar): ?>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Salvar modelo</button>
        <button class="btn btn-ghost" type="submit" name="restaurar" value="1" data-confirm-btn="Voltar ao texto original? As suas alterações neste modelo serão perdidas.">Restaurar original</button>
        <a class="btn btn-ghost" href="<?= url('documentos/modelos') ?>">Voltar</a>
      </div>
    <?php endif; ?>
  </form>
  <div class="stack">
    <?php require __DIR__ . '/_campos.php'; ?>
    <div class="card">
      <h3>Pré-visualização do modelo</h3>
      <div class="doc-preview doc-mini"><?= doc_html($conteudo) ?></div>
    </div>
  </div>
</div>
