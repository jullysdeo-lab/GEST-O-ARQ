<form class="card" method="post" action="<?= url('cadastro', ['t' => $t]) ?>">
  <?= csrf_field() ?>
  <h2>Seus dados</h2>
  <p class="muted"><?= e($escritorio) ?> pediu que você confira e complete seus dados. Eles serão usados no contrato e nos documentos do seu projeto.</p>
  <?php if ($erro): ?><div class="flash flash-erro" style="margin-top:10px"><?= e($erro) ?></div><?php endif; ?>
  <div class="form-grid" style="margin-top:12px">
    <label>Nome completo ou razão social<input name="nome" value="<?= e($c['nome']) ?>" required autocomplete="name"></label>
    <label>CPF ou CNPJ<input name="documento" value="<?= e($c['documento']) ?>" required inputmode="numeric"></label>
    <label>E-mail<input type="email" name="email" value="<?= e($c['email']) ?>" autocomplete="email"></label>
    <label>Telefone / WhatsApp<input name="telefone" value="<?= e($c['telefone']) ?>" autocomplete="tel"></label>
    <label style="grid-column:1/-1">Endereço completo<input name="endereco" value="<?= e($c['endereco']) ?>" required placeholder="Rua, número, complemento, bairro, cidade/UF, CEP" autocomplete="street-address"></label>
  </div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Enviar meus dados</button></div>
</form>
