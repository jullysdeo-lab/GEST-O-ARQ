<form class="card" method="post" action="<?= url('perfil') ?>" style="max-width:520px">
  <?= csrf_field() ?>
  <label>Nome<input name="nome" value="<?= e($u['nome']) ?>" required></label>
  <label>E-mail<input value="<?= e($u['email']) ?>" disabled></label>
  <h3 style="margin-top:22px">Trocar senha</h3>
  <label>Senha atual<input type="password" name="senha_atual" autocomplete="current-password"></label>
  <label>Nova senha<input type="password" name="senha_nova" minlength="8" autocomplete="new-password"></label>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Salvar</button></div>
</form>
<div class="card" style="max-width:520px;margin-top:16px">
  <h3>Agenda no celular</h3>
  <p class="help">Assine sua agenda no Google Agenda, iPhone ou Outlook.</p>
  <a class="btn" style="margin-top:8px" href="<?= url('agenda/sincronizar') ?>">Configurar sincronização</a>
</div>
