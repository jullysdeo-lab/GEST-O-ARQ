<?php require __DIR__ . '/_tabs.php'; ?>
<div class="card">
  <div class="card-head">
    <form class="row" method="get" action="<?= url('documentos') ?>">
      <select name="tipo" data-autosubmit style="width:auto;margin:0">
        <option value="">Todos os tipos</option>
        <?php foreach (TIPOS_DOCUMENTO as $k => $v): ?><option value="<?= $k ?>"<?= selected($tipo, $k) ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
      <select name="status" data-autosubmit style="width:auto;margin:0">
        <option value="">Todas as situações</option>
        <?php foreach (DOC_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= selected($status, $k) ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
    </form>
    <?php if (can('documentos', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('documentos/novo') ?>">+ Novo documento</a><?php endif; ?>
  </div>
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum documento ainda. Gere um contrato a partir de um orçamento aprovado ou de um projeto.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Documento</th><th>Tipo</th><th>Cliente</th><th>Projeto</th><th>Situação</th><th>Criado</th><th>Assinado</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $d): ?>
        <tr>
          <td><a href="<?= url('documentos/ver', ['id' => $d['id']]) ?>"><strong><?= e($d['titulo']) ?></strong></a></td>
          <td><?= e(TIPOS_DOCUMENTO[$d['tipo']] ?? $d['tipo']) ?></td>
          <td><?= e($d['cliente_nome']) ?></td>
          <td><?= e($d['projeto_codigo'] ?: '—') ?></td>
          <td><span class="badge badge-<?= $d['status'] === 'assinado' ? 'ok' : ($d['status'] === 'enviado' ? 'warn' : '') ?>"><?= DOC_STATUS[$d['status']] ?></span></td>
          <td><?= brdate($d['criado_em']) ?></td>
          <td><?= brdate($d['assinado_em']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
