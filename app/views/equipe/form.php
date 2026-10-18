<form class="card" method="post" action="<?= url('equipe/salvar') ?>" style="max-width:720px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($m['id'] ?? '') ?>">
  <div class="form-grid">
    <label>Nome<input name="nome" value="<?= e($m['nome'] ?? '') ?>" required></label>
    <label>E-mail (login)<input type="email" name="email" value="<?= e($m['email'] ?? '') ?>" required></label>
    <label>Perfil
      <select name="perfil">
        <?php foreach (PERFIS as $k => $v): ?>
          <option value="<?= $k ?>"<?= selected($m['perfil'] ?? 'colaborador', $k) ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="help">Sênior e Administrativo: acesso total. Gestor de Projetos: tudo menos Financeiro. Colaborador: definido em Permissões.</div>
    </label>
    <label>Cargo<input name="cargo" value="<?= e($m['cargo'] ?? '') ?>" placeholder="Arquiteto(a), Estagiário(a)…"></label>
    <label>Horas por dia<input name="horas_dia" inputmode="decimal" value="<?= e(num($m['horas_dia'] ?? 8)) ?>"></label>
    <label>Dias por mês<input type="number" name="dias_mes" min="0" max="31" value="<?= e($m['dias_mes'] ?? 22) ?>"></label>
    <label>Salário / custo mensal (R$)<input name="salario" inputmode="decimal" value="<?= e(num($m['salario'] ?? 0, 2)) ?>">
      <div class="help">Entra nos custos do escritório para o cálculo da hora técnica.</div>
    </label>
    <label><?= $m ? 'Nova senha (deixe em branco para manter)' : 'Senha inicial' ?>
      <input type="password" name="senha" minlength="8" autocomplete="new-password" <?= $m ? '' : 'required' ?>>
    </label>
  </div>
  <label style="margin-top:14px"><input type="checkbox" name="ativo" value="1" <?= ($m['ativo'] ?? 1) ? 'checked' : '' ?>>Ativo</label>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Salvar</button>
    <a class="btn" href="<?= url('equipe') ?>">Cancelar</a>
  </div>
</form>
