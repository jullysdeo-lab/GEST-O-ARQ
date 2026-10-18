<?php require __DIR__ . '/_tabs.php'; ?>
<div class="card">
  <div class="card-head">
    <div>
      <h2>Modelos e cláusulas</h2>
      <p class="muted">Ajuste o texto de cada tipo de documento ao jeito do seu escritório. Todo documento novo daquele tipo usa o texto editado. Os textos padrão são um ponto de partida: revise com sua assessoria jurídica.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Tipo</th><th>Situação</th><th>Atualizado</th><th></th></tr></thead>
      <tbody>
      <?php foreach (TIPOS_DOCUMENTO as $tipo => $nome): ?>
        <tr>
          <td><strong><?= e($nome) ?></strong></td>
          <td><span class="badge <?= $alterados[$tipo] ? 'badge-info' : '' ?>"><?= $alterados[$tipo] ? 'Personalizado' : 'Texto padrão' ?></span></td>
          <td><?= brdate($mods[$tipo]['atualizado_em'] ?? null) ?></td>
          <td class="num">
            <a class="btn btn-sm" href="<?= url('documentos/modelo', ['tipo' => $tipo]) ?>"><?= can('documentos', NIVEL_EDITAR) ? 'Editar cláusulas' : 'Ver' ?></a>
            <?php if (can('documentos', NIVEL_EDITAR)): ?><a class="btn btn-sm btn-primary" href="<?= url('documentos/novo', ['tipo' => $tipo]) ?>">Gerar</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
