<?php
[$nomeTipo, $cor] = TIPOS_EVENTO[$e['tipo']] ?? ['Compromisso', '#736c62'];
$mesmoDia = substr($e['inicio'], 0, 10) === substr($e['fim'], 0, 10);
if ($e['dia_todo']) {
    $quando = brdate($e['inicio']) . ($mesmoDia ? '' : ' a ' . brdate($e['fim'])) . ' · dia todo';
} else {
    $quando = date('d/m/Y H:i', strtotime($e['inicio'])) . ' – ' . ($mesmoDia ? date('H:i', strtotime($e['fim'])) : date('d/m/Y H:i', strtotime($e['fim'])));
}
$mapa = $e['local'] ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($e['local']) : null;
?>
<div class="card" style="max-width:760px;border-left:4px solid <?= e($cor) ?>">
  <div class="row">
    <span class="badge" style="background:<?= e($cor) ?>;color:#fff"><?= e($nomeTipo) ?></span>
    <div class="spacer"></div>
    <a class="btn" href="<?= url('agenda', ['mes' => substr($e['inicio'], 0, 7)]) ?>">Ver no calendário</a>
    <?php if ($podeEditar): ?><a class="btn btn-primary" href="<?= url('agenda/form', ['id' => $e['id']]) ?>">Editar</a><?php endif; ?>
  </div>
  <h2 style="font-size:22px;margin-top:12px"><?= e($e['titulo']) ?></h2>
  <p style="font-size:16px;margin:6px 0 14px"><?= e($quando) ?></p>
  <table class="summary-table">
    <?php if ($e['local']): ?><tr><td>Local</td><td><a href="<?= e($mapa) ?>" target="_blank" rel="noopener"><?= e($e['local']) ?></a></td></tr><?php endif; ?>
    <?php if ($e['cliente_nome']): ?><tr><td>Cliente</td><td><a href="<?= url(can('crm') ? 'crm/lead' : 'clientes/form', ['id' => $e['cliente_id']]) ?>"><?= e($e['cliente_nome']) ?></a></td></tr><?php endif; ?>
    <?php if ($e['projeto_codigo']): ?><tr><td>Projeto</td><td><a href="<?= url('projetos/ver', ['id' => $e['projeto_id']]) ?>"><?= e($e['projeto_codigo']) ?> · <?= e($e['projeto_nome']) ?></a></td></tr><?php endif; ?>
    <tr><td>Participantes</td><td><?= $e['participantes'] ? e(implode(', ', array_column($e['participantes'], 'nome'))) : '—' ?></td></tr>
    <tr><td>Criado por</td><td><?= e($e['criador_nome'] ?: '—') ?></td></tr>
  </table>
  <?php if ($e['descricao']): ?><div class="resposta" style="margin-top:14px"><?= e($e['descricao']) ?></div><?php endif; ?>

  <?php if ($podeEditar): ?>
    <form method="post" action="<?= url('agenda/excluir') ?>" data-confirm="Excluir este compromisso?" style="margin-top:18px">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $e['id'] ?>">
      <button class="btn btn-ghost btn-danger" type="submit">Excluir compromisso</button>
    </form>
  <?php endif; ?>
</div>
