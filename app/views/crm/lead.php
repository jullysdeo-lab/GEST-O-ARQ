<?php
$editar = can('crm', NIVEL_EDITAR);
$etapa = $c['funil_etapa'] ?: 'novo';
$aberto = in_array($etapa, FUNIL_ABERTO, true);
$dias = dias_desde($c['ultimo_contato'] ?: $c['criado_em']);
$tel = preg_replace('/\D/', '', (string) $c['telefone']);
if ($tel !== '' && strlen($tel) <= 11) {
    $tel = '55' . $tel; // DDI do Brasil
}
?>
<div class="card">
  <div class="row">
    <div>
      <h2 style="font-size:20px"><?= e($c['nome']) ?></h2>
      <div class="muted"><?= e($c['tipo_projeto'] ?: 'Tipo de projeto não informado') ?> · origem: <?= e($c['origem'] ?: '—') ?> · desde <?= brdate($c['criado_em']) ?></div>
    </div>
    <span class="badge temp-badge-<?= e($c['temperatura'] ?: 'morno') ?>"><?= TEMPERATURAS[$c['temperatura'] ?: 'morno'] ?? '' ?></span>
    <div class="spacer"></div>
    <?php if ($tel !== ''): ?><a class="btn" target="_blank" rel="noopener" href="https://wa.me/<?= e($tel) ?>">WhatsApp</a><?php endif; ?>
    <?php if ($c['email']): ?><a class="btn" href="mailto:<?= e($c['email']) ?>">E-mail</a><?php endif; ?>
    <?php if ($editar): ?><a class="btn" href="<?= url('crm/form', ['id' => $c['id']]) ?>">Editar</a><?php endif; ?>
  </div>

  <!-- Etapas do funil -->
  <div class="funil-etapas">
    <?php foreach (FUNIL as $k => $nome): ?>
      <?php if ($editar && $k !== $etapa && $k !== 'perdido'): ?>
        <form class="inline" method="post" action="<?= url('crm/mover') ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="etapa" value="<?= $k ?>">
          <button type="submit" class="etapa"><?= e($nome) ?></button>
        </form>
      <?php else: ?>
        <span class="etapa <?= $k === $etapa ? 'atual etapa-' . $k : '' ?>"><?= e($nome) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php if ($etapa === 'perdido'): ?>
    <p class="muted" style="margin:10px 0 0">Motivo da perda: <strong><?= e($c['motivo_perda'] ?: 'não informado') ?></strong></p>
  <?php endif; ?>
</div>

<?php if ($editar && $aberto): ?>
  <details class="card" style="margin-top:16px" <?= input('perder') ? 'open' : '' ?>>
    <summary style="cursor:pointer;font-weight:600">Marcar como perdido</summary>
    <form method="post" action="<?= url('crm/mover') ?>" class="row" style="margin-top:12px;align-items:flex-end">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="etapa" value="perdido">
      <label style="flex:1">Motivo
        <select name="motivo_perda" required>
          <option value="">— escolher —</option>
          <?php foreach (MOTIVOS_PERDA as $m): ?><option><?= e($m) ?></option><?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn-danger" type="submit">Marcar como perdido</button>
    </form>
  </details>
<?php endif; ?>

<div class="grid grid-side" style="margin-top:16px">
  <div class="card">
    <div class="card-head"><h2>Histórico de contatos</h2>
      <small class="muted"><?= $dias === null ? '' : ($dias === 0 ? 'último contato hoje' : "último contato há $dias dia(s)") ?></small></div>

    <?php if ($editar): ?>
      <form method="post" action="<?= url('crm/interacao') ?>" class="card" style="background:var(--surface-2);box-shadow:none;margin-bottom:16px">
        <?= csrf_field() ?><input type="hidden" name="cliente_id" value="<?= $c['id'] ?>">
        <div class="form-grid">
          <label>Canal<select name="tipo"><?php foreach (TIPOS_INTERACAO as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></label>
          <label>Quando<input type="datetime-local" name="data" value="<?= date('Y-m-d\TH:i') ?>"></label>
          <label>Próximo contato<input type="date" name="proximo_contato" value="<?= e($c['proximo_contato'] ?? '') ?>"></label>
          <label>Temperatura<select name="temperatura"><?php foreach (TEMPERATURAS as $k => $v): ?><option value="<?= $k ?>"<?= selected($c['temperatura'] ?: 'morno', $k) ?>><?= $v ?></option><?php endforeach; ?></select></label>
        </div>
        <label style="margin-top:10px">O que foi conversado<textarea name="descricao" rows="3" required placeholder="Ex.: enviou fotos do apartamento, quer começar em março"></textarea></label>
        <div class="row" style="margin-top:10px">
          <button class="btn btn-primary" type="submit">Registrar contato</button>
          <?php if ($c['proximo_contato']): ?><label style="font-weight:400;margin:0"><input type="checkbox" name="limpar_proximo" value="1">Sem próximo contato</label><?php endif; ?>
        </div>
      </form>
    <?php endif; ?>

    <?php if (!$interacoes): ?>
      <p class="muted">Nenhum contato registrado.</p>
    <?php else: ?>
      <ul class="timeline">
        <?php foreach ($interacoes as $i): ?>
          <li class="tl-<?= e($i['tipo']) ?>">
            <div class="row" style="gap:6px">
              <strong><?= e(TIPOS_INTERACAO[$i['tipo']] ?? $i['tipo']) ?></strong>
              <small class="muted"><?= e(date('d/m/Y H:i', strtotime($i['data']))) ?><?= $i['usuario_nome'] ? ' · ' . e($i['usuario_nome']) : '' ?></small>
              <div class="spacer"></div>
              <?php if ($editar): ?>
                <form class="inline" method="post" action="<?= url('crm/interacao/excluir') ?>" data-confirm="Excluir este registro?">
                  <?= csrf_field() ?><input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"><input type="hidden" name="id" value="<?= $i['id'] ?>">
                  <button class="btn btn-sm btn-ghost" type="submit" aria-label="Excluir">×</button>
                </form>
              <?php endif; ?>
            </div>
            <div class="resposta"><?= e($i['descricao']) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card">
      <h2>Dados</h2>
      <table class="summary-table" style="margin-top:8px">
        <tr><td>Telefone</td><td><?= e($c['telefone'] ?: '—') ?></td></tr>
        <tr><td>E-mail</td><td><?= e($c['email'] ?: '—') ?></td></tr>
        <tr><td>Valor estimado</td><td><?= $c['valor_estimado'] ? money($c['valor_estimado']) : '—' ?></td></tr>
        <tr><td>Responsável</td><td><?= e($c['responsavel_nome'] ?: '—') ?></td></tr>
        <tr><td>Próximo contato</td><td><?= $c['proximo_contato'] ? '<span class="badge ' . ($c['proximo_contato'] <= today() ? 'badge-atrasado' : '') . '">' . brdate($c['proximo_contato']) . '</span>' : '—' ?></td></tr>
      </table>
      <?php if ($c['observacoes']): ?><p class="help resposta" style="margin-top:10px"><?= e($c['observacoes']) ?></p><?php endif; ?>
    </div>

    <div class="card">
      <h2>Próximos passos</h2>
      <div class="stack" style="margin-top:10px">
        <?php if (can('briefings', NIVEL_EDITAR)): ?><a class="btn btn-block" href="<?= url('briefings/novo', ['cliente_id' => $c['id']]) ?>">Enviar briefing</a><?php endif; ?>
        <?php if (can('agenda', NIVEL_EDITAR)): ?><a class="btn btn-block" href="<?= url('agenda/form', ['cliente_id' => $c['id'], 'titulo' => 'Reunião com ' . $c['nome']]) ?>">Agendar reunião</a><?php endif; ?>
        <?php if (can('precificacao', NIVEL_EDITAR)): ?><a class="btn btn-block btn-primary" href="<?= url('orcamentos/novo', ['cliente_id' => $c['id']]) ?>">Criar orçamento</a><?php endif; ?>
      </div>
    </div>

    <?php if ($orcamentos || $briefings || $eventos): ?>
    <div class="card">
      <h2>Vinculados</h2>
      <ul class="list" style="margin-top:6px">
        <?php foreach ($eventos as $ev): ?>
          <li><a style="flex:1" href="<?= url('agenda/evento', ['id' => $ev['id']]) ?>"><?= e($ev['titulo']) ?></a><small class="muted"><?= e(date('d/m H:i', strtotime($ev['inicio']))) ?></small></li>
        <?php endforeach; ?>
        <?php foreach ($orcamentos as $o): ?>
          <li><a style="flex:1" href="<?= url('orcamentos/ver', ['id' => $o['id']]) ?>"><?= e($o['numero']) ?> · <?= e($o['titulo']) ?></a><span class="badge badge-<?= e($o['status']) ?>"><?= ORCAMENTO_STATUS[$o['status']] ?></span></li>
        <?php endforeach; ?>
        <?php foreach ($briefings as $b): ?>
          <li><a style="flex:1" href="<?= url('briefings/ver', ['id' => $b['id']]) ?>"><?= e($b['titulo']) ?></a><span class="badge badge-<?= $b['status'] === 'respondido' ? 'ok' : 'warn' ?>"><?= $b['status'] === 'respondido' ? 'Respondido' : 'Aguardando' ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>
