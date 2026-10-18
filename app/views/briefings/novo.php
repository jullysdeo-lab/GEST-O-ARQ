<form class="card" method="post" action="<?= url('briefings/criar') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <h2>1. Modelo</h2>
  <div class="stack" style="margin-top:12px">
    <?php foreach ($modelos as $i => $m): ?>
      <label class="card" style="margin:0;cursor:pointer;display:flex;gap:10px;align-items:flex-start;box-shadow:none">
        <input type="radio" name="modelo_id" value="<?= $m['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> required style="width:auto;margin:3px 0 0">
        <span><strong><?= e($m['nome']) ?></strong> <small class="muted">· <?= e($m['categoria']) ?> · <?= (int) $m['n'] ?> perguntas</small><br>
          <span class="muted" style="font-weight:400"><?= e($m['descricao']) ?></span></span>
      </label>
    <?php endforeach; ?>
  </div>
  <p class="help"><a href="<?= url('briefings/modelos') ?>">Editar modelos e perguntas</a></p>

  <h2 style="margin-top:22px">2. Para quem</h2>
  <div class="form-grid" style="margin-top:10px">
    <label>Cliente
      <select name="cliente_id">
        <option value="">—</option>
        <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"<?= selected($cliente_id, $c['id']) ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>…ou do orçamento
      <select name="orcamento_id">
        <option value="">—</option>
        <?php foreach ($orcamentos as $o): ?><option value="<?= $o['id'] ?>"<?= selected($orcamento_id, $o['id']) ?>><?= e($o['numero']) ?> · <?= e($o['titulo']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>…ou do projeto
      <select name="projeto_id">
        <option value="">—</option>
        <?php foreach ($projetos as $p): ?><option value="<?= $p['id'] ?>"<?= selected($projeto_id, $p['id']) ?>><?= e($p['codigo']) ?> · <?= e($p['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>
  <label style="margin-top:12px">Título (opcional)<input name="titulo" placeholder="Em branco: nome do modelo + cliente"></label>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Criar e gerar link</button>
    <a class="btn" href="<?= url('briefings') ?>">Cancelar</a>
  </div>
</form>
