<form class="card" method="post" action="<?= url('crm/salvar') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
  <div class="form-grid">
    <label>Nome<input name="nome" value="<?= e($c['nome'] ?? '') ?>" required></label>
    <label>Telefone / WhatsApp<input name="telefone" value="<?= e($c['telefone'] ?? '') ?>"></label>
    <label>E-mail<input type="email" name="email" value="<?= e($c['email'] ?? '') ?>"></label>
    <label>Origem
      <input name="origem" list="origens" value="<?= e($c['origem'] ?? '') ?>">
      <datalist id="origens"><?php foreach (ORIGENS as $o): ?><option value="<?= e($o) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Tipo de projeto<input name="tipo_projeto" value="<?= e($c['tipo_projeto'] ?? '') ?>" placeholder="Reforma de apartamento, casa nova…"></label>
    <label>Valor estimado (R$)<input name="valor_estimado" inputmode="decimal" value="<?= isset($c['valor_estimado']) ? e(num($c['valor_estimado'], 2)) : '' ?>"></label>
    <label>Temperatura
      <select name="temperatura"><?php foreach (TEMPERATURAS as $k => $v): ?><option value="<?= $k ?>"<?= selected($c['temperatura'] ?? 'morno', $k) ?>><?= $v ?></option><?php endforeach; ?></select>
    </label>
    <label>Responsável
      <select name="responsavel_id">
        <option value="">—</option>
        <?php foreach ($equipe as $m): ?><option value="<?= $m['id'] ?>"<?= selected($c['responsavel_id'] ?? current_user()['id'], $m['id']) ?>><?= e($m['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Próximo contato<input type="date" name="proximo_contato" value="<?= e($c['proximo_contato'] ?? add_days(today(), 2)) ?>"></label>
    <?php if (!$c): ?>
      <label>Etapa inicial
        <select name="funil_etapa"><?php foreach (FUNIL_ABERTO as $k): ?><option value="<?= $k ?>"><?= e(FUNIL[$k]) ?></option><?php endforeach; ?></select>
      </label>
    <?php endif; ?>
  </div>
  <?php if (!$c): ?>
    <div class="form-grid" style="margin-top:12px;grid-template-columns:1fr 3fr">
      <label>Canal do primeiro contato
        <select name="canal"><?php foreach (TIPOS_INTERACAO as $k => $v): ?><option value="<?= $k ?>"<?= selected('whatsapp', $k) ?>><?= $v ?></option><?php endforeach; ?></select>
      </label>
      <label>O que o lead pediu<input name="primeiro_contato" placeholder="Ex.: quer reformar a cozinha, viu nosso perfil no Instagram"></label>
    </div>
  <?php endif; ?>
  <label style="margin-top:12px">Observações<textarea name="observacoes"><?= e($c['observacoes'] ?? '') ?></textarea></label>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Salvar</button>
    <a class="btn" href="<?= url($c ? 'crm/lead' : 'crm', $c ? ['id' => $c['id']] : []) ?>">Cancelar</a>
  </div>
</form>
