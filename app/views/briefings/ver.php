<?php
$editar = can('briefings', NIVEL_EDITAR);
$total = count($b['perguntas']);
$resp = count(array_filter($b['respostas'], fn($v) => $v !== '' && $v !== []));
?>
<div class="grid grid-side">
  <div class="card">
    <div class="card-head">
      <h2><?= $b['status'] === 'respondido' ? 'Respostas' : 'Perguntas enviadas' ?></h2>
      <small class="muted"><?= $resp ?>/<?= $total ?> respondidas</small>
    </div>
    <?php require __DIR__ . '/_respostas.php'; ?>
  </div>

  <div class="stack">
    <div class="card">
      <div class="row"><h2>Situação</h2><div class="spacer"></div>
        <span class="badge badge-<?= $b['status'] === 'respondido' ? 'ok' : 'warn' ?>"><?= BRIEFING_STATUS[$b['status']] ?></span></div>
      <table class="summary-table" style="margin-top:8px">
        <tr><td>Cliente</td><td><a href="<?= url('clientes/form', ['id' => $b['cliente_id']]) ?>"><?= e($b['cliente_nome']) ?></a></td></tr>
        <?php if ($b['projeto_codigo']): ?><tr><td>Projeto</td><td><a href="<?= url('projetos/ver', ['id' => $b['projeto_id']]) ?>"><?= e($b['projeto_codigo']) ?></a></td></tr><?php endif; ?>
        <?php if ($b['orcamento_numero']): ?><tr><td>Orçamento</td><td><a href="<?= url('orcamentos/ver', ['id' => $b['orcamento_id']]) ?>"><?= e($b['orcamento_numero']) ?></a></td></tr><?php endif; ?>
        <tr><td>Enviado</td><td><?= brdate($b['enviado_em']) ?></td></tr>
        <tr><td>Respondido</td><td><?= $b['respondido_em'] ? e(date('d/m/Y H:i', strtotime($b['respondido_em']))) : '—' ?></td></tr>
      </table>
      <div class="row" style="margin-top:14px">
        <a class="btn" href="<?= url('briefings/imprimir', ['id' => $b['id']]) ?>" target="_blank">Relatório / PDF</a>
        <?php if ($editar && $b['status'] === 'respondido'): ?>
          <form class="inline" method="post" action="<?= url('briefings/reabrir') ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button class="btn btn-ghost" type="submit">Reabrir para o cliente</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($b['status'] === 'aguardando'): ?>
      <div class="card">
        <h2>Link para o cliente</h2>
        <p class="help">O cliente responde pelo celular ou computador, sem criar conta. Pode salvar e continuar depois pelo mesmo link.</p>
        <div class="row" style="margin-top:8px;flex-wrap:nowrap">
          <input value="<?= e($link) ?>" readonly id="link-briefing" style="margin:0">
          <button class="btn" type="button" data-copiar-de="link-briefing">Copiar</button>
        </div>
        <div class="row" style="margin-top:8px">
          <?php if ($b['cliente_telefone']): ?>
            <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) $b['cliente_telefone'])) ?>?text=<?= rawurlencode("Olá! Para começarmos seu projeto, responda o briefing neste link: $link") ?>">Enviar pelo WhatsApp</a>
          <?php endif; ?>
          <?php if ($b['cliente_email']): ?>
            <a class="btn btn-ghost btn-sm" href="mailto:<?= e($b['cliente_email']) ?>?subject=<?= rawurlencode('Briefing do seu projeto') ?>&body=<?= rawurlencode("Olá! Para começarmos seu projeto, responda o briefing neste link:\n\n$link") ?>">Enviar por e-mail</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($editar): ?>
      <form method="post" action="<?= url('briefings/excluir') ?>" data-confirm="Excluir este briefing e as respostas?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
        <button class="btn btn-ghost btn-danger" type="submit">Excluir briefing</button>
      </form>
    <?php endif; ?>
  </div>
</div>
