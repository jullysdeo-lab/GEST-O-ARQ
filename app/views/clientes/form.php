<form class="card" method="post" action="<?= url('clientes/salvar') ?>" style="max-width:720px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
  <div class="form-grid">
    <label>Nome<input name="nome" value="<?= e($c['nome'] ?? '') ?>" required></label>
    <label>Tipo
      <select name="tipo">
        <option value="prospecto"<?= selected($c['tipo'] ?? 'prospecto', 'prospecto') ?>>Prospecto</option>
        <option value="cliente"<?= selected($c['tipo'] ?? '', 'cliente') ?>>Cliente</option>
      </select>
    </label>
    <label>E-mail<input type="email" name="email" value="<?= e($c['email'] ?? '') ?>"></label>
    <label>Telefone / WhatsApp<input name="telefone" value="<?= e($c['telefone'] ?? '') ?>"></label>
    <label>CPF / CNPJ<input name="documento" value="<?= e($c['documento'] ?? '') ?>"></label>
    <label>Origem<input name="origem" value="<?= e($c['origem'] ?? '') ?>" placeholder="Instagram, indicação…"></label>
    <label style="grid-column:1/-1">Endereço completo<input name="endereco" value="<?= e($c['endereco'] ?? '') ?>" placeholder="Rua, número, complemento, bairro, cidade/UF, CEP"></label>
  </div>
  <label style="margin-top:12px">Observações<textarea name="observacoes"><?= e($c['observacoes'] ?? '') ?></textarea></label>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Salvar</button>
    <a class="btn" href="<?= url('clientes') ?>">Cancelar</a>
  </div>
</form>
<?php if ($c): ?>
  <div class="card" style="max-width:720px;margin-top:16px">
    <h2>Pedir os dados ao cliente</h2>
    <?php if ($c['dados_atualizados_em']): ?>
      <p><span class="badge badge-ok">Atualizado pelo cliente em <?= e(date('d/m/Y H:i', strtotime($c['dados_atualizados_em']))) ?></span></p>
    <?php endif; ?>
    <?php if ($c['token_dados']): $link = url_absoluta('cadastro', ['t' => $c['token_dados']]); ?>
      <p class="help">O cliente abre o link, confere e completa nome, CPF/CNPJ, contato e endereço. O link vale para um único envio.</p>
      <div class="row" style="margin-top:8px;flex-wrap:nowrap">
        <input value="<?= e($link) ?>" readonly id="link-dados" style="margin:0">
        <button class="btn" type="button" data-copiar-de="link-dados">Copiar</button>
      </div>
      <?php if ($c['telefone']): ?>
        <a class="btn btn-ghost btn-sm" style="margin-top:8px" target="_blank" rel="noopener"
           href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) $c['telefone'])) ?>?text=<?= rawurlencode("Olá! Para prepararmos o contrato, confira e complete seus dados neste link: $link") ?>">Enviar pelo WhatsApp</a>
      <?php endif; ?>
    <?php else: ?>
      <p class="help">Gere um link para o próprio cliente preencher os dados que vão no contrato.</p>
      <form method="post" action="<?= url('clientes/link-dados') ?>" style="margin-top:10px">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="btn" type="submit">Gerar link para o cliente</button>
      </form>
    <?php endif; ?>
  </div>

  <form method="post" action="<?= url('clientes/excluir') ?>" data-confirm="Excluir este cadastro?" style="margin-top:16px">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
    <button class="btn btn-ghost btn-danger" type="submit">Excluir cadastro</button>
  </form>
<?php endif; ?>
