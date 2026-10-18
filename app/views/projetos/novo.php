<form class="card" method="post" action="<?= url('projetos/criar') ?>" style="max-width:720px">
  <?= csrf_field() ?>
  <?php if ($o): ?>
    <input type="hidden" name="orcamento_id" value="<?= $o['id'] ?>">
    <div class="flash flash-ok">
      A partir do orçamento <strong><?= e($o['numero']) ?></strong> (<?= e($o['cliente_nome']) ?>, <?= money($o['valor_total']) ?>).
      As tarefas orçadas viram tarefas do projeto, com prazos calculados em sequência pela duração de cada etapa (<?= (int) $o['dias'] ?> dias no total).
    </div>
  <?php endif; ?>

  <div class="form-grid">
    <label>Nome do projeto<input name="nome" value="<?= e($o['titulo'] ?? '') ?>" required></label>
    <?php if (!$o): ?>
      <label>Cliente
        <select name="cliente_id" required>
          <option value="">— escolher —</option>
          <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <label>Data de início<input type="date" name="inicio" value="<?= today() ?>" required></label>
    <label>Entrega prevista<input type="date" name="entrega">
      <div class="help"><?= $o ? 'Em branco: calculada pelas etapas.' : 'Opcional.' ?></div>
    </label>
  </div>

  <?php if ($o && $podeFinanceiro): ?>
    <div class="card" style="margin-top:16px; background:var(--surface-2); box-shadow:none">
      <label style="font-weight:600"><input type="checkbox" name="gerar_parcelas" value="1" checked>Lançar as <?= (int) $o['parcelas'] ?> parcela(s) no Financeiro</label>
      <div class="help"><?= (int) $o['parcelas'] ?>× de <?= money($o['valor_total'] / max(1, $o['parcelas'])) ?>, uma por mês, já vinculadas ao cliente e ao projeto.</div>
      <label style="margin-top:10px; max-width:240px">Primeiro vencimento<input type="date" name="primeiro_vencimento" value="<?= today() ?>"></label>
    </div>
  <?php endif; ?>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Criar projeto</button>
    <a class="btn" href="<?= url($o ? 'orcamentos/ver' : 'projetos', $o ? ['id' => $o['id']] : []) ?>">Cancelar</a>
  </div>
</form>
