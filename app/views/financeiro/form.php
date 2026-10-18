<form class="card" method="post" action="<?= url('financeiro/salvar') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($l['id'] ?? '') ?>">
  <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
  <div class="form-grid">
    <label style="grid-column:1/-1">Descrição<input name="descricao" value="<?= e($l['descricao'] ?? '') ?>" required></label>
    <label>Valor (R$)<input name="valor" inputmode="decimal" value="<?= isset($l['valor']) ? e(num($l['valor'], 2)) : '' ?>" required></label>
    <label>Vencimento<input type="date" name="vencimento" value="<?= e($l['vencimento'] ?? today()) ?>" required></label>
    <label>Competência<input type="month" name="competencia" value="<?= e($l['competencia'] ?? date('Y-m')) ?>"></label>
    <label>Categoria
      <input name="categoria" list="categorias" value="<?= e($l['categoria'] ?? '') ?>">
      <datalist id="categorias"><?php foreach (CATEGORIAS[$tipo] as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Forma de pagamento
      <select name="forma_pagamento">
        <option value="">—</option>
        <?php foreach (FORMAS_PAGAMENTO as $f): ?><option<?= selected($l['forma_pagamento'] ?? '', $f) ?>><?= $f ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Cliente
      <select name="cliente_id" data-cliente-projetos="projeto_id" data-url="<?= url('financeiro/projetos-do-cliente') ?>">
        <option value="">— nenhum —</option>
        <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"<?= selected($l['cliente_id'] ?? '', $c['id']) ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Projeto
      <select name="projeto_id" id="projeto_id">
        <option value="">— nenhum —</option>
        <?php foreach ($projetos as $p): ?><option value="<?= $p['id'] ?>"<?= selected($l['projeto_id'] ?? '', $p['id']) ?>><?= e($p['codigo']) ?> · <?= e($p['nome']) ?></option><?php endforeach; ?>
      </select>
      <div class="help">Mostra só os projetos do cliente escolhido.</div>
    </label>
  </div>
  <?php if (!$l): ?>
    <label style="margin-top:14px;font-weight:400"><input type="checkbox" name="ja_realizado" value="1">Já foi <?= $tipo === 'entrada' ? 'recebido' : 'pago' ?> (na data do vencimento)</label>
  <?php endif; ?>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Salvar</button>
    <a class="btn" href="<?= url('financeiro/lancamentos', ['tipo' => $tipo]) ?>">Cancelar</a>
  </div>
</form>
<?php if ($l): ?>
  <form method="post" action="<?= url('financeiro/excluir') ?>" data-confirm="Excluir este lançamento?" style="margin-top:16px">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>">
    <button class="btn btn-ghost btn-danger" type="submit">Excluir lançamento</button>
  </form>
<?php endif; ?>
