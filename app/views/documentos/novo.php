<form class="card" method="post" action="<?= url('documentos/criar') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <h2>1. Tipo de documento</h2>
  <div class="grid grid-2" style="margin-top:12px">
    <?php foreach (TIPOS_DOCUMENTO as $k => $v): ?>
      <label class="card" style="margin:0;cursor:pointer;display:flex;gap:8px;align-items:center;box-shadow:none">
        <input type="radio" name="tipo" value="<?= $k ?>" <?= $tipo === $k ? 'checked' : '' ?> required style="width:auto;margin:0"><?= e($v) ?>
      </label>
    <?php endforeach; ?>
  </div>

  <h2 style="margin-top:24px">2. Para quem</h2>
  <p class="help">Escolha um projeto ou orçamento para preencher escopo, valores e prazos automaticamente. O cliente sai deles.</p>
  <div class="form-grid" style="margin-top:10px">
    <label>Projeto
      <select name="projeto_id">
        <option value="">—</option>
        <?php foreach ($projetos as $p): ?><option value="<?= $p['id'] ?>"<?= selected($projeto_id, $p['id']) ?>><?= e($p['codigo']) ?> · <?= e($p['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Orçamento
      <select name="orcamento_id">
        <option value="">—</option>
        <?php foreach ($orcamentos as $o): ?><option value="<?= $o['id'] ?>"<?= selected($orcamento_id, $o['id']) ?>><?= e($o['numero']) ?> · <?= e($o['titulo']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>…ou só o cliente
      <select name="cliente_id">
        <option value="">—</option>
        <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"<?= selected($cliente_id, $c['id']) ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Gerar documento</button>
    <a class="btn" href="<?= url('documentos') ?>">Cancelar</a>
  </div>
</form>
