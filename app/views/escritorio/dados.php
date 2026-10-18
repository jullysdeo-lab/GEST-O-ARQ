<?php require APP_ROOT . '/app/views/equipe/_tabs.php';
$editar = can('equipe', NIVEL_EDITAR); ?>
<form class="card" method="post" action="<?= url('escritorio') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <h2>Dados do escritório</h2>
  <p class="muted">Aparecem nos contratos, termos e propostas. O que ficar em branco sai como <mark>[preencher]</mark> no documento.</p>
  <fieldset style="border:0;padding:0;margin:14px 0 0" <?= $editar ? '' : 'disabled' ?>>
    <label>Nome do escritório no sistema<input name="nome" value="<?= e($nome) ?>" required></label>
    <div class="form-grid" style="margin-top:12px">
      <?php foreach (DADOS_ESCRITORIO as $k => $rotulo): ?>
        <label<?= $k === 'esc_endereco' ? ' style="grid-column:1/-1"' : '' ?>><?= e($rotulo) ?><input name="<?= $k ?>" value="<?= e($valores[$k]) ?>" maxlength="255"></label>
      <?php endforeach; ?>
    </div>
    <?php if ($editar): ?><div class="form-actions"><button class="btn btn-primary" type="submit">Salvar</button></div><?php endif; ?>
  </fieldset>
</form>
