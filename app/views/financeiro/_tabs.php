<div class="tabs">
  <a href="<?= url('financeiro') ?>" class="<?= $aba === 'dashboard' ? 'active' : '' ?>">Resumo</a>
  <a href="<?= url('financeiro/lancamentos', ['tipo' => 'entrada']) ?>" class="<?= $aba === 'entrada' ? 'active' : '' ?>">Entradas</a>
  <a href="<?= url('financeiro/lancamentos', ['tipo' => 'saida']) ?>" class="<?= $aba === 'saida' ? 'active' : '' ?>">Saídas</a>
</div>
