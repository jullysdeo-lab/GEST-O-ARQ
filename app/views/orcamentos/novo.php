<?php if ($ht['custo_hora'] <= 0): ?>
  <div class="flash flash-warn">O custo da hora técnica está zerado. Cadastre custos e a equipe em <a href="<?= url('precificacao') ?>">Precificação</a> antes de orçar.</div>
<?php endif; ?>
<form class="card" method="post" action="<?= url('orcamentos/criar') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <label>Título do orçamento<input name="titulo" placeholder="Ex.: Reforma apartamento 302" required></label>

  <h3 style="margin-top:20px">Cliente</h3>
  <div class="form-grid" style="margin-top:8px">
    <label>Cliente existente
      <select name="cliente_id">
        <option value="">— escolher —</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= $c['id'] ?>"<?= selected($cliente_id, $c['id']) ?>><?= e($c['nome']) ?><?= $c['tipo'] === 'prospecto' ? ' (prospecto)' : '' ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>…ou cadastre um novo prospecto<input name="novo_cliente" placeholder="Nome"></label>
    <label>E-mail<input type="email" name="novo_email"></label>
    <label>Telefone<input name="novo_telefone"></label>
    <label>Origem<input name="novo_origem" placeholder="Instagram, indicação…"></label>
  </div>

  <h3 style="margin-top:20px">Escopo</h3>
  <div class="form-grid" style="margin-top:8px">
    <label>Partir do template
      <select name="template_id">
        <option value="">— em branco —</option>
        <?php foreach ($templates as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Área (m²)<input name="area_m2" inputmode="decimal"></label>
    <label>Parcelas<input type="number" name="parcelas" min="1" max="48" value="1"></label>
  </div>
  <p class="help">Custo da hora técnica atual: <strong><?= money($ht['custo_hora']) ?></strong>. Ele fica gravado no orçamento.</p>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Criar orçamento</button>
    <a class="btn" href="<?= url('orcamentos') ?>">Cancelar</a>
  </div>
</form>
