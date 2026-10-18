<?php require __DIR__ . '/_tabs.php'; ?>
<div class="card">
  <div class="card-head">
    <div>
      <h2>Templates de projeto</h2>
      <p class="muted">Etapas, tarefas e horas padrão. O orçamento parte de um template, e o projeto herda as tarefas com os prazos calculados pela duração de cada etapa.</p>
    </div>
    <?php if (can('precificacao', NIVEL_EDITAR)): ?><a class="btn btn-primary" href="<?= url('precificacao/template') ?>">+ Novo template</a><?php endif; ?>
  </div>
  <?php if (!$lista): ?>
    <div class="empty"><p class="muted">Nenhum template ainda.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Template</th><th class="num">Etapas</th><th class="num">Horas</th><th class="num">Duração</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $t): ?>
        <tr>
          <td><a href="<?= url('precificacao/template', ['id' => $t['id']]) ?>"><strong><?= e($t['nome']) ?></strong></a></td>
          <td class="num"><?= (int) $t['etapas'] ?></td>
          <td class="num"><?= num($t['horas']) ?> h</td>
          <td class="num"><?= (int) $t['dias'] ?> dias</td>
          <td class="num"><a class="btn btn-sm" href="<?= url('precificacao/template', ['id' => $t['id']]) ?>">Abrir</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
