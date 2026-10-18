<?php
$editar = can('projetos', NIVEL_EDITAR);
$pct = $p['total_tarefas'] ? round($p['concluidas'] / $p['total_tarefas'] * 100) : 0;
$grupos = [];
foreach ($tarefas as $t) {
    $grupos[$t['etapa_ordem'] . '|' . $t['etapa_nome']][] = $t;
}
?>
<div class="grid grid-4">
  <div class="card kpi"><div class="label">Progresso</div><div class="value"><?= $pct ?>%</div><div class="progress" style="margin-top:8px"><span style="width:<?= $pct ?>%"></span></div></div>
  <div class="card kpi"><div class="label">Tarefas</div><div class="value"><?= (int) $p['concluidas'] ?>/<?= (int) $p['total_tarefas'] ?></div><div class="hint <?= $p['atrasadas'] ? 'erro' : '' ?>"><?= (int) $p['atrasadas'] ?> atrasada(s)</div></div>
  <div class="card kpi"><div class="label">Prazo</div><div class="value" style="font-size:18px"><?= brdate($p['inicio']) ?> → <?= brdate($p['entrega']) ?></div><div class="hint"><?= num($p['horas']) ?> h previstas</div></div>
  <?php if ($financeiro): ?>
    <div class="card kpi"><div class="label">Resultado projetado</div>
      <div class="value"><?= money($financeiro['recebido'] + $financeiro['a_receber'] - $financeiro['pago'] - $financeiro['a_pagar']) ?></div>
      <div class="hint">recebido <?= money($financeiro['recebido']) ?> · a receber <?= money($financeiro['a_receber']) ?></div></div>
  <?php else: ?>
    <div class="card kpi"><div class="label">Cliente</div><div class="value" style="font-size:18px"><?= e($p['cliente_nome']) ?></div></div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-head">
    <div class="row">
      <span class="muted">Cliente: <strong><?= e($p['cliente_nome']) ?></strong></span>
      <?php if ($p['orcamento_numero']): ?><a href="<?= url('orcamentos/ver', ['id' => $p['orcamento_id']]) ?>" class="badge">Orçamento <?= e($p['orcamento_numero']) ?></a><?php endif; ?>
      <a class="btn btn-sm" href="<?= url('projetos/cronograma', ['projeto' => $p['id']]) ?>">Cronograma</a>
      <?php if (can('agenda', NIVEL_EDITAR)): ?><a class="btn btn-sm" href="<?= url('agenda/form', ['projeto_id' => $p['id'], 'titulo' => 'Reunião · ' . $p['nome']]) ?>">Agendar</a><?php endif; ?>
    </div>
    <?php if ($editar): ?>
      <form class="row" method="post" action="<?= url('projetos/status') ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
        <label class="row" style="font-weight:400">Status
          <select name="status" data-autosubmit style="width:auto;margin:0">
            <?php foreach (PROJETO_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($p['status'], $k) ?>><?= $v ?></option><?php endforeach; ?>
          </select>
        </label>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$tarefas): ?>
    <div class="empty"><p class="muted">Nenhuma tarefa ainda.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Tarefa</th><th>Responsável</th><th>Prazo</th><th>Prioridade</th><th class="num">Horas</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($grupos as $chave => $lista):
        [$ord, $etapa] = explode('|', $chave, 2);
        $feitas = count(array_filter($lista, fn($t) => $t['status'] === 'concluida')); ?>
        <tr class="group-row"><td colspan="6"><?= e($ord) ?>. <?= e($etapa) ?> <small class="muted">· <?= $feitas ?>/<?= count($lista) ?></small></td></tr>
        <?php foreach ($lista as $t):
          $atrasada = $t['status'] !== 'concluida' && $t['prazo'] && $t['prazo'] < today(); ?>
          <tr>
            <td style="padding-left:32px"><?= $t['status'] === 'concluida' ? '<s class="muted">' . e($t['nome']) . '</s>' : e($t['nome']) ?></td>
            <td>
              <?php if ($editar): ?>
              <form method="post" action="<?= url('tarefas/salvar') ?>" class="inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="campo" value="responsavel_id"><input type="hidden" name="voltar" value="projeto">
                <select name="valor" data-autosubmit style="margin:0;min-width:140px">
                  <option value="">—</option>
                  <?php foreach ($equipe as $m): ?><option value="<?= $m['id'] ?>"<?= selected($t['responsavel_id'], $m['id']) ?>><?= e($m['nome']) ?></option><?php endforeach; ?>
                </select>
              </form>
              <?php else: ?><?= e($t['responsavel_nome'] ?: '—') ?><?php endif; ?>
            </td>
            <td>
              <?php if ($editar): ?>
              <form method="post" action="<?= url('tarefas/salvar') ?>" class="inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="campo" value="prazo"><input type="hidden" name="voltar" value="projeto">
                <input type="date" name="valor" value="<?= e($t['prazo']) ?>" data-autosubmit style="margin:0;width:150px<?= $atrasada ? ';border-color:var(--erro);color:var(--erro)' : '' ?>">
              </form>
              <?php else: ?><?= brdate($t['prazo']) ?><?php endif; ?>
            </td>
            <td><span class="badge badge-<?= e($t['prioridade']) ?>"><?= PRIORIDADES[$t['prioridade']] ?? '' ?></span></td>
            <td class="num"><?= num($t['horas_previstas']) ?> h</td>
            <td>
              <?php if ($editar || (int) $t['responsavel_id'] === (int) current_user()['id']): ?>
              <form method="post" action="<?= url('tarefas/status') ?>" class="inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="voltar" value="projeto">
                <select name="status" data-autosubmit style="margin:0;min-width:130px">
                  <?php foreach (TAREFA_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($t['status'], $k) ?>><?= $v ?></option><?php endforeach; ?>
                </select>
              </form>
              <?php else: ?><span class="badge badge-<?= $atrasada ? 'atrasado' : e($t['status']) ?>"><?= $atrasada ? 'Atrasada' : TAREFA_STATUS[$t['status']] ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if ($editar): ?>
    <form method="post" action="<?= url('tarefas/salvar') ?>" class="form-grid" style="margin-top:16px; align-items:end">
      <?= csrf_field() ?><input type="hidden" name="projeto_id" value="<?= $p['id'] ?>"><input type="hidden" name="voltar" value="projeto">
      <label>Nova tarefa<input name="nome" required></label>
      <label>Etapa<input name="etapa_nome" list="etapas" value="Geral"></label>
      <label>Prazo<input type="date" name="prazo"></label>
      <label>Prioridade<select name="prioridade"><?php foreach (PRIORIDADES as $k => $v): ?><option value="<?= $k ?>"<?= selected('media', $k) ?>><?= $v ?></option><?php endforeach; ?></select></label>
      <label>Horas<input name="horas_previstas" inputmode="decimal" value="0"></label>
      <button class="btn" type="submit">Adicionar tarefa</button>
      <datalist id="etapas"><?php foreach (array_keys($grupos) as $k): ?><option value="<?= e(explode('|', $k, 2)[1]) ?>"><?php endforeach; ?></datalist>
    </form>
  <?php endif; ?>
</div>

<?php if (can('documentos') || can('briefings')): ?>
<div class="grid grid-2" style="margin-top:16px">
  <?php if (can('documentos')): ?>
  <div class="card">
    <div class="card-head"><h2>Documentos</h2>
      <?php if (can('documentos', NIVEL_EDITAR)): ?>
        <div class="row">
          <a class="btn btn-sm" href="<?= url('documentos/novo', ['tipo' => 'contrato', 'projeto_id' => $p['id']]) ?>">+ Contrato</a>
          <a class="btn btn-sm" href="<?= url('documentos/novo', ['tipo' => 'ata', 'projeto_id' => $p['id']]) ?>">+ Ata</a>
          <a class="btn btn-sm" href="<?= url('documentos/novo', ['projeto_id' => $p['id']]) ?>">+ Outro</a>
        </div>
      <?php endif; ?>
    </div>
    <?php if (!$documentos): ?><p class="muted">Nenhum documento deste projeto.</p><?php else: ?>
      <ul class="list">
        <?php foreach ($documentos as $doc): ?>
          <li><a style="flex:1" href="<?= url('documentos/ver', ['id' => $doc['id']]) ?>"><?= e($doc['titulo']) ?></a>
            <span class="badge badge-<?= $doc['status'] === 'assinado' ? 'ok' : ($doc['status'] === 'enviado' ? 'warn' : '') ?>"><?= ['rascunho' => 'Rascunho', 'enviado' => 'Aguardando assinatura', 'assinado' => 'Assinado'][$doc['status']] ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if (can('briefings')): ?>
  <div class="card">
    <div class="card-head"><h2>Briefings</h2>
      <?php if (can('briefings', NIVEL_EDITAR)): ?><a class="btn btn-sm" href="<?= url('briefings/novo', ['projeto_id' => $p['id']]) ?>">+ Enviar briefing</a><?php endif; ?>
    </div>
    <?php if (!$briefings): ?><p class="muted">Nenhum briefing deste projeto.</p><?php else: ?>
      <ul class="list">
        <?php foreach ($briefings as $br): ?>
          <li><a style="flex:1" href="<?= url('briefings/ver', ['id' => $br['id']]) ?>"><?= e($br['titulo']) ?></a>
            <span class="badge badge-<?= $br['status'] === 'respondido' ? 'ok' : 'warn' ?>"><?= $br['status'] === 'respondido' ? 'Respondido' : 'Aguardando' ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($editar): ?>
  <form method="post" action="<?= url('projetos/excluir') ?>" data-confirm="Excluir o projeto e todas as tarefas? Esta ação não pode ser desfeita." style="margin-top:16px">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
    <button class="btn btn-ghost btn-danger" type="submit">Excluir projeto</button>
  </form>
<?php endif; ?>
