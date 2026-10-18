<?php require __DIR__ . '/_tabs.php';
$editar = can('precificacao', NIVEL_EDITAR); ?>

<div class="grid grid-4">
  <div class="card kpi"><div class="label">Custo mensal</div><div class="value"><?= money($ht['custo_mensal']) ?></div><div class="hint">custos + salários</div></div>
  <div class="card kpi"><div class="label">Horas da equipe</div><div class="value"><?= num($ht['horas_mes'], 0) ?> h</div><div class="hint">por mês, equipe ativa</div></div>
  <div class="card kpi"><div class="label">Custo da hora técnica</div><div class="value"><?= money($ht['custo_hora']) ?></div><div class="hint">custo mensal ÷ horas</div></div>
  <div class="card kpi"><div class="label">Exemplo: 100 h de projeto</div><div class="value"><?= money($exemplo['valor']) ?></div><div class="hint">preço sugerido com os parâmetros abaixo</div></div>
</div>

<div class="grid grid-2" style="margin-top:16px">
  <div class="card">
    <div class="card-head"><h2>Custos mensais do escritório</h2></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Custo</th><th>Tipo</th><th class="num">Valor/mês</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($custos as $c): ?>
          <tr>
            <td><?= e($c['nome']) ?></td>
            <td><span class="badge"><?= $c['tipo'] === 'fixo' ? 'Fixo' : 'Variável' ?></span></td>
            <td class="num"><?= money($c['valor_mensal']) ?></td>
            <td class="num"><?php if ($editar): ?>
              <form class="inline" method="post" action="<?= url('precificacao/custo/excluir') ?>" data-confirm="Remover este custo?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="btn btn-sm btn-ghost btn-danger" type="submit">Remover</button>
              </form><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        <tr><td>Salários da equipe <small class="muted">(de Equipe)</small></td><td><span class="badge">Fixo</span></td><td class="num"><?= money($ht['salarios']) ?></td><td></td></tr>
        </tbody>
        <tfoot><tr><td colspan="2">Total</td><td class="num"><?= money($ht['custo_mensal']) ?></td><td></td></tr></tfoot>
      </table>
    </div>
    <?php if ($editar): ?>
      <form method="post" action="<?= url('precificacao/custo') ?>" class="row" style="margin-top:14px; align-items:flex-end">
        <?= csrf_field() ?>
        <label style="flex:2">Novo custo<input name="nome" placeholder="Ex.: Aluguel" required></label>
        <label style="flex:1">Tipo<select name="tipo"><option value="fixo">Fixo</option><option value="variavel">Variável</option></select></label>
        <label style="flex:1">Valor/mês<input name="valor_mensal" inputmode="decimal" placeholder="0,00" required></label>
        <button class="btn" type="submit">Adicionar</button>
      </form>
    <?php endif; ?>
  </div>

  <form class="card" method="post" action="<?= url('precificacao/parametros') ?>">
    <?= csrf_field() ?>
    <div class="card-head"><h2>Parâmetros de cálculo</h2></div>
    <div class="form-grid">
      <label>Impostos (%)<input name="impostos_pct" inputmode="decimal" value="<?= num($params['impostos_pct']) ?>" <?= $editar ? '' : 'disabled' ?>></label>
      <label>Lucro desejado (%)<input name="lucro_pct" inputmode="decimal" value="<?= num($params['lucro_pct']) ?>" <?= $editar ? '' : 'disabled' ?>></label>
      <label>Reserva técnica (%)<input name="reserva_pct" inputmode="decimal" value="<?= num($params['reserva_pct']) ?>" <?= $editar ? '' : 'disabled' ?>></label>
      <label>Margem de segurança nas horas (%)<input name="margem_horas_pct" inputmode="decimal" value="<?= num($params['margem_horas_pct']) ?>" <?= $editar ? '' : 'disabled' ?>></label>
      <label>Honorário de referência por m² (R$)<input name="honorario_m2" inputmode="decimal" value="<?= num($params['honorario_m2'], 2) ?>" <?= $editar ? '' : 'disabled' ?>></label>
      <label>Meta de margem (%)<input name="meta_margem_pct" inputmode="decimal" value="<?= num($params['meta_margem_pct']) ?>" <?= $editar ? '' : 'disabled' ?>></label>
    </div>
    <p class="help" style="margin-top:14px">
      <strong>Como o preço é calculado:</strong> horas ajustadas = horas × (1 + margem de segurança);
      custo = horas ajustadas × custo da hora; preço = custo × (1 + lucro) × (1 + reserva) ÷ (1 − impostos).
    </p>
    <?php if ($editar): ?><div class="form-actions"><button class="btn btn-primary" type="submit">Salvar parâmetros</button></div><?php endif; ?>
  </form>
</div>
