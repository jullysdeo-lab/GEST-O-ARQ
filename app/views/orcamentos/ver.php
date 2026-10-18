<?php
$grupos = [];
foreach ($o['itens'] as $i) {
    $grupos[$i['etapa_ordem']]['nome'] = $i['etapa_nome'];
    $grupos[$i['etapa_ordem']]['dias'] = $i['duracao_dias'];
    $grupos[$i['etapa_ordem']]['itens'][] = $i;
}
ksort($grupos);
$podeEditar = can('precificacao', NIVEL_EDITAR);
$acoes = [
    'rascunho' => ['enviado' => 'Marcar como enviado', 'arquivado' => 'Arquivar'],
    'enviado' => ['aprovado' => 'Aprovar', 'recusado' => 'Recusado pelo cliente', 'rascunho' => 'Voltar para rascunho'],
    'recusado' => ['rascunho' => 'Reabrir como rascunho', 'arquivado' => 'Arquivar'],
    'aprovado' => ['arquivado' => 'Arquivar'],
    'arquivado' => ['rascunho' => 'Restaurar'],
][$o['status']] ?? [];
?>
<div class="card">
  <div class="row">
    <div>
      <h2><?= e($o['titulo']) ?></h2>
      <div class="muted"><?= e($o['cliente_nome']) ?> · criado em <?= brdate($o['criado_em']) ?><?= $o['aprovado_em'] ? ' · aprovado em ' . brdate($o['aprovado_em']) : '' ?></div>
    </div>
    <span class="badge badge-<?= e($o['status']) ?>"><?= ORCAMENTO_STATUS[$o['status']] ?></span>
    <div class="spacer"></div>
    <a class="btn" href="<?= url('orcamentos/imprimir', ['id' => $o['id']]) ?>" target="_blank">Proposta para imprimir</a>
    <?php if (can('briefings', NIVEL_EDITAR)): ?><a class="btn" href="<?= url('briefings/novo', ['orcamento_id' => $o['id']]) ?>">Enviar briefing</a><?php endif; ?>
    <?php if ($o['status'] === 'aprovado' && can('documentos', NIVEL_EDITAR)): ?><a class="btn" href="<?= url('documentos/novo', ['tipo' => 'contrato', 'orcamento_id' => $o['id'], 'projeto_id' => $o['projeto']['id'] ?? '']) ?>">Gerar contrato</a><?php endif; ?>
    <?php if ($podeEditar) foreach ($acoes as $st => $label): ?>
      <form class="inline" method="post" action="<?= url('orcamentos/status') ?>" <?= $st === 'aprovado' ? 'data-confirm="Aprovar o orçamento? O prospecto vira cliente e o escopo fica travado."' : '' ?>>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>">
        <button class="btn <?= $st === 'aprovado' ? 'btn-primary' : '' ?>" type="submit"><?= $label ?></button>
      </form>
    <?php endforeach; ?>
    <?php if ($o['status'] === 'aprovado' && !$o['projeto'] && can('projetos', NIVEL_EDITAR)): ?>
      <a class="btn btn-primary" href="<?= url('projetos/novo', ['orcamento_id' => $o['id']]) ?>">Criar projeto →</a>
    <?php elseif ($o['projeto']): ?>
      <a class="btn" href="<?= url('projetos/ver', ['id' => $o['projeto']['id']]) ?>">Projeto <?= e($o['projeto']['codigo']) ?> →</a>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-side" style="margin-top:16px">
  <div class="card">
    <div class="card-head"><h2>Escopo e horas</h2></div>
    <?php if (!$grupos): ?>
      <div class="empty"><p class="muted">Nenhuma tarefa ainda. Adicione abaixo.</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tarefa</th><th class="num">Horas</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($grupos as $g): ?>
          <tr class="group-row"><td><?= e($g['nome']) ?> <small class="muted">· <?= (int) $g['dias'] ?> dias</small></td><td class="num"><?= num(array_sum(array_column($g['itens'], 'horas'))) ?> h</td><td></td></tr>
          <?php foreach ($g['itens'] as $i): ?>
            <tr>
              <td style="padding-left:32px"><?= e($i['tarefa_nome']) ?></td>
              <td class="num"><?= num($i['horas']) ?> h</td>
              <td class="num"><?php if ($editavel): ?>
                <form class="inline" method="post" action="<?= url('orcamentos/item/excluir') ?>">
                  <?= csrf_field() ?><input type="hidden" name="orcamento_id" value="<?= $o['id'] ?>"><input type="hidden" name="id" value="<?= $i['id'] ?>">
                  <button class="btn btn-sm btn-ghost btn-danger" type="submit" aria-label="Remover">×</button>
                </form><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td>Total estimado</td><td class="num"><?= num($calc['horas']) ?> h</td><td></td></tr></tfoot>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($editavel): ?>
      <form method="post" action="<?= url('orcamentos/item') ?>" class="form-grid" style="margin-top:16px; align-items:end; grid-template-columns: 2fr 2fr 1fr 1fr auto">
        <?= csrf_field() ?><input type="hidden" name="orcamento_id" value="<?= $o['id'] ?>">
        <label>Etapa<input name="etapa_nome" list="etapas-existentes" required></label>
        <label>Tarefa<input name="tarefa_nome" required></label>
        <label>Horas<input name="horas" inputmode="decimal" required></label>
        <label>Dias (etapa nova)<input type="number" name="duracao_dias" min="0" value="7"></label>
        <button class="btn" type="submit">Adicionar</button>
        <datalist id="etapas-existentes"><?php foreach ($grupos as $g): ?><option value="<?= e($g['nome']) ?>"><?php endforeach; ?></datalist>
      </form>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card">
      <h2>Cálculo</h2>
      <table class="summary-table" style="margin-top:10px">
        <tr><td>Custo da hora</td><td><?= money($o['custo_hora']) ?></td></tr>
        <tr><td>Horas estimadas</td><td><?= num($calc['horas']) ?> h</td></tr>
        <tr><td>Com margem de segurança</td><td><?= num($calc['horas_ajustadas']) ?> h</td></tr>
        <tr><td>Custo</td><td><?= money($calc['custo']) ?></td></tr>
        <tr><td>Impostos</td><td><?= money($calc['impostos']) ?></td></tr>
        <tr><td>Preço calculado</td><td><?= money($calc['valor']) ?></td></tr>
        <?php if ($o['valor_manual'] !== null): ?><tr><td>Valor ajustado manualmente</td><td><?= money($o['valor_manual']) ?></td></tr><?php endif; ?>
        <tr class="total"><td>Valor da proposta</td><td><?= money($o['valor_total']) ?></td></tr>
        <tr><td><?= (int) $o['parcelas'] ?>× de</td><td><?= money($o['valor_total'] / max(1, $o['parcelas'])) ?></td></tr>
        <?php if ($m2 > 0 && $o['area_m2']): ?><tr><td class="muted">Referência por m² (<?= num($o['area_m2']) ?> m²)</td><td class="muted"><?= money($m2 * $o['area_m2']) ?></td></tr><?php endif; ?>
        <?php if ($o['valor_total'] > 0): ?><tr><td class="muted">Margem líquida estimada</td><td class="muted"><?= num(($o['valor_total'] - $calc['impostos'] - $calc['custo']) / $o['valor_total'] * 100) ?>%</td></tr><?php endif; ?>
      </table>
    </div>

    <form class="card" method="post" action="<?= url('orcamentos/dados') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
      <h2>Dados</h2>
      <fieldset style="border:0;padding:0;margin:10px 0 0" <?= $editavel ? '' : 'disabled' ?>>
        <label>Título<input name="titulo" value="<?= e($o['titulo']) ?>"></label>
        <div class="form-grid" style="margin-top:12px">
          <label>Área (m²)<input name="area_m2" inputmode="decimal" value="<?= $o['area_m2'] ? e(num($o['area_m2'], 2)) : '' ?>"></label>
          <label>Parcelas<input type="number" name="parcelas" min="1" max="48" value="<?= (int) $o['parcelas'] ?>"></label>
        </div>
        <label style="margin-top:12px">Valor final manual (opcional)<input name="valor_manual" inputmode="decimal" value="<?= $o['valor_manual'] !== null ? e(num($o['valor_manual'], 2)) : '' ?>" placeholder="Deixe em branco para usar o calculado"></label>
        <label>Observações para a proposta<textarea name="observacoes"><?= e($o['observacoes']) ?></textarea></label>
        <label style="font-weight:400"><input type="checkbox" name="atualizar_hora" value="1">Atualizar para o custo da hora atual (<?= money(hora_tecnica()['custo_hora']) ?>)</label>
        <?php if ($editavel): ?><div class="form-actions"><button class="btn btn-primary" type="submit">Salvar</button></div><?php endif; ?>
      </fieldset>
    </form>
  </div>
</div>
