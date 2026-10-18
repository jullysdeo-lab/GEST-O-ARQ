<?php
$editar = can('precificacao', NIVEL_EDITAR);
// Sempre mostra duas etapas vazias a mais para facilitar a inclusão.
$etapas = $t['etapas'];
$etapas[] = ['nome' => '', 'duracao_dias' => 7, 'tarefas' => []];
$etapas[] = ['nome' => '', 'duracao_dias' => 7, 'tarefas' => []];
?>
<form method="post" action="<?= url('precificacao/template/salvar') ?>" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($t['id']) ?>">
  <div class="card">
    <div class="row">
      <label style="flex:1">Nome do template<input name="nome" value="<?= e($t['nome']) ?>" required <?= $editar ? '' : 'disabled' ?>></label>
    </div>
    <p class="help">Em cada etapa, escreva uma tarefa por linha no formato <code>Nome da tarefa; horas</code>. Etapas sem nome são ignoradas.</p>
  </div>

  <?php foreach ($etapas as $i => $et):
    $linhas = implode("\n", array_map(fn($tt) => $tt['nome'] . '; ' . num($tt['horas']), $et['tarefas'])); ?>
    <div class="card">
      <div class="form-grid" style="grid-template-columns: 3fr 1fr">
        <label>Etapa <?= $i + 1 ?><input name="etapa_nome[]" value="<?= e($et['nome']) ?>" placeholder="Ex.: Anteprojeto" <?= $editar ? '' : 'disabled' ?>></label>
        <label>Duração (dias)<input type="number" min="0" name="etapa_dias[]" value="<?= (int) $et['duracao_dias'] ?>" <?= $editar ? '' : 'disabled' ?>></label>
      </div>
      <label style="margin-top:10px">Tarefas
        <textarea name="etapa_tarefas[]" rows="<?= max(3, count($et['tarefas']) + 1) ?>" placeholder="Planta baixa; 8&#10;Cortes e fachadas; 6" <?= $editar ? '' : 'disabled' ?>><?= e($linhas) ?></textarea>
      </label>
    </div>
  <?php endforeach; ?>

  <?php if ($editar): ?>
  <div class="row">
    <button class="btn btn-primary" type="submit">Salvar template</button>
    <a class="btn" href="<?= url('precificacao/templates') ?>">Voltar</a>
  </div>
  <?php endif; ?>
</form>
<?php if ($editar && $t['id']): ?>
  <form method="post" action="<?= url('precificacao/template/excluir') ?>" data-confirm="Excluir este template? Orçamentos já criados não mudam." style="margin-top:16px">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($t['id']) ?>">
    <button class="btn btn-ghost btn-danger" type="submit">Excluir template</button>
  </form>
<?php endif; ?>
