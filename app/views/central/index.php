<?php
$hora = (int) date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$feitos = count(array_filter($primeirosPassos));
$totalProj = array_sum($projetos);
?>
<div class="row" style="margin-bottom:18px">
  <div>
    <h2 style="font-size:22px"><?= $saudacao ?>, <?= e(explode(' ', $u['nome'])[0]) ?></h2>
    <div class="muted">Panorama do escritório em <?= date('d/m/Y') ?>.</div>
  </div>
</div>

<?php if ($feitos < count($primeirosPassos)): ?>
  <div class="card" style="margin-bottom:16px">
    <div class="card-head">
      <h2>Primeiros passos</h2>
      <small class="muted"><?= $feitos ?>/<?= count($primeirosPassos) ?> concluídos</small>
    </div>
    <div class="progress" style="margin-bottom:12px"><span style="width:<?= round($feitos / count($primeirosPassos) * 100) ?>%"></span></div>
    <ul class="list">
      <?php foreach ($primeirosPassos as $passo => $ok): ?>
        <li><span class="badge <?= $ok ? 'badge-ok' : '' ?>"><?= $ok ? '✓' : '·' ?></span><span class="<?= $ok ? 'muted' : '' ?>"><?= e($passo) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($fin): ?>
<div class="grid grid-4">
  <div class="card kpi"><div class="label">Faturamento no mês</div><div class="value"><?= money($fin['faturamento']) ?></div><div class="hint">recebido</div></div>
  <div class="card kpi"><div class="label">A receber no mês</div><div class="value"><?= money($fin['a_receber']) ?></div>
    <div class="hint <?= $fin['receber_atrasado'] > 0 ? 'erro' : '' ?>"><?= $fin['receber_atrasado'] > 0 ? money($fin['receber_atrasado']) . ' em atraso' : 'nada em atraso' ?></div></div>
  <div class="card kpi"><div class="label">A pagar no mês</div><div class="value"><?= money($fin['a_pagar']) ?></div>
    <div class="hint <?= $fin['pagar_atrasado'] > 0 ? 'erro' : '' ?>"><?= $fin['pagar_atrasado'] > 0 ? money($fin['pagar_atrasado']) . ' em atraso' : 'nada em atraso' ?></div></div>
  <div class="card kpi"><div class="label">Margem operacional</div><div class="value"><?= $fin['margem'] === null ? '—' : num($fin['margem']) . '%' ?></div><div class="hint">meta: <?= num($fin['meta'], 0) ?>%</div></div>
</div>
<?php endif; ?>

<div class="grid grid-2" style="margin-top:16px">
  <div class="card">
    <div class="card-head"><h2>Próximos compromissos</h2><a href="<?= url('agenda') ?>">Agenda →</a></div>
    <?php if (!$compromissos): ?>
      <p class="muted">Nada marcado nos próximos 7 dias.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($compromissos as $c): ?>
          <li><span class="ponto" style="background:<?= e($c['cor']) ?>"></span>
            <span style="flex:1"><a href="<?= e($c['url']) ?>"><?= e($c['titulo']) ?></a><?php if ($c['sub']): ?><br><small class="muted"><?= e($c['sub']) ?></small><?php endif; ?></span>
            <small class="muted" style="text-align:right"><?= $c['dia'] === today() ? 'hoje' : e(date('d/m', strtotime($c['dia']))) ?><?= $c['hora'] ? '<br>' . e($c['hora']) : '' ?></small></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>Meu foco</h2><a href="<?= url('tarefas', ['filtro' => 'minhas']) ?>">Ver todas →</a></div>
    <?php if (!$minhas): ?>
      <p class="muted">Nenhuma tarefa sua vencendo nos próximos dias.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($minhas as $t): $atr = $t['prazo'] < today(); ?>
          <li>
            <span style="flex:1"><a href="<?= url('projetos/ver', ['id' => $t['projeto_id']]) ?>"><?= e($t['nome']) ?></a><br><small class="muted"><?= e($t['codigo']) ?> · <?= e($t['etapa_nome']) ?></small></span>
            <span class="badge <?= $atr ? 'badge-atrasado' : 'badge-hoje' ?>"><?= brdate($t['prazo']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>Alertas</h2></div>
    <ul class="list">
      <?php foreach ($retornos as $r): ?>
        <li><span class="badge badge-hoje">☎</span><a href="<?= url('crm/lead', ['id' => $r['id']]) ?>" style="flex:1">Retornar para <?= e($r['nome']) ?></a>
          <small class="muted"><?= $r['proximo_contato'] === today() ? 'hoje' : 'desde ' . brdate($r['proximo_contato']) ?></small></li>
      <?php endforeach; ?>
      <?php if ($esquecidos): ?>
        <li><span class="badge badge-warn">!</span><a href="<?= url('crm/relatorio') ?>" style="flex:1"><?= $esquecidos ?> lead(s) sem contato há mais de <?= DIAS_LEAD_FRIO ?> dias</a></li>
      <?php endif; ?>
      <?php if ($atrasadas): ?>
        <li><span class="badge badge-atrasado">!</span><a href="<?= url('tarefas', ['filtro' => 'atrasadas']) ?>" style="flex:1"><?= $atrasadas ?> tarefa(s) com prazo vencido</a></li>
      <?php endif; ?>
      <?php foreach ($alertasFin as $l): [$cls, $rot] = status_vencimento($l); ?>
        <li>
          <span class="badge badge-<?= $cls ?>"><?= $l['tipo'] === 'entrada' ? '↓' : '↑' ?></span>
          <span style="flex:1"><a href="<?= url('financeiro/lancamentos', ['tipo' => $l['tipo'], 'situacao' => $cls === 'atrasado' ? 'atrasado' : '']) ?>"><?= e($l['descricao']) ?></a><br>
            <small class="muted"><?= $l['tipo'] === 'entrada' ? 'Receber' : 'Pagar' ?> · <?= $rot ?> · <?= brdate($l['vencimento']) ?></small></span>
          <span class="num"><?= money($l['valor']) ?></span>
        </li>
      <?php endforeach; ?>
      <?php if (!$atrasadas && !$alertasFin && !$retornos && !$esquecidos): ?><li class="muted">Tudo em dia.</li><?php endif; ?>
    </ul>
  </div>

  <div class="card">
    <div class="card-head"><h2>Saúde do escritório</h2></div>
    <div class="score">
      <div class="score-ring" style="--v:<?= $saude['total'] ?>"><span><?= $saude['total'] ?></span></div>
      <div>
        <strong><?= e($saude['rotulo']) ?></strong>
        <table class="summary-table" style="margin-top:4px">
          <tr><td>Financeiro</td><td><?= $saude['financeiro'] ?></td></tr>
          <tr><td>Operacional</td><td><?= $saude['operacional'] ?></td></tr>
          <tr><td>Comercial</td><td><?= $saude['comercial'] ?></td></tr>
        </table>
      </div>
    </div>
    <p class="help">Financeiro: contas em aberto que não estão atrasadas. Operacional: tarefas em aberto no prazo. Comercial: taxa de aprovação dos orçamentos nos últimos 6 meses.</p>
  </div>
</div>

<div class="grid grid-2" style="margin-top:16px">
  <?php if ($serie): ?>
  <div class="card">
    <div class="card-head"><h2>Fluxo de caixa</h2><small class="muted">receita × despesa realizadas</small></div>
    <?php require APP_ROOT . '/app/views/_barras.php'; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><h2>Projetos</h2><a href="<?= url('projetos') ?>">Abrir →</a></div>
    <?php if (!$totalProj): ?>
      <p class="muted">Nenhum projeto ainda.</p>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach (['em_andamento' => 'Em andamento', 'atrasado' => 'Atrasados', 'planejamento' => 'Planejamento', 'concluido' => 'Concluídos'] as $k => $v): ?>
          <div class="kpi" style="padding:0"><div class="label"><?= $v ?></div><div class="value" style="<?= $k === 'atrasado' && !empty($projetos[$k]) ? 'color:var(--erro)' : '' ?>"><?= (int) ($projetos[$k] ?? 0) ?></div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($orcamentos): ?>
  <div class="card">
    <div class="card-head"><h2>Orçamentos em aberto</h2><a href="<?= url('orcamentos') ?>">Abrir →</a></div>
    <ul class="list">
      <?php foreach ($orcamentos as $o): ?>
        <li>
          <span style="flex:1"><a href="<?= url('orcamentos/ver', ['id' => $o['id']]) ?>"><?= e($o['titulo']) ?></a><br><small class="muted"><?= e($o['numero']) ?> · <?= e($o['cliente_nome']) ?></small></span>
          <span class="badge badge-<?= e($o['status']) ?>"><?= ORCAMENTO_STATUS[$o['status']] ?></span>
          <span class="num"><?= money($o['valor_total']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><h2>Atividade recente</h2></div>
    <?php if (!$atividades): ?>
      <p class="muted">Nada registrado ainda.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($atividades as $a): ?>
          <li><span class="avatar"><?= e(initials($a['usuario_nome'] ?? '?')) ?></span>
            <span style="flex:1"><?= e($a['acao']) ?><br><small class="muted"><?= e($a['usuario_nome'] ?? '') ?> · <?= e(date('d/m H:i', strtotime($a['criado_em']))) ?></small></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
