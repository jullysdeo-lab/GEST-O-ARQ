<?php
$ids = array_map('intval', array_column($e['participantes'] ?? [], 'id'));
$dia = substr($e['inicio'], 0, 10);
$diaFim = substr($e['fim'], 0, 10);
?>
<form class="card" method="post" action="<?= url('agenda/salvar') ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($e['id']) ?>">
  <div class="form-grid">
    <label style="grid-column:1/-1">Título<input name="titulo" value="<?= e($e['titulo']) ?>" required maxlength="160" placeholder="Ex.: Apresentação do anteprojeto"></label>
    <label>Tipo
      <select name="tipo"><?php foreach (TIPOS_EVENTO as $k => [$nome]): ?><option value="<?= $k ?>"<?= selected($e['tipo'], $k) ?>><?= e($nome) ?></option><?php endforeach; ?></select>
    </label>
    <label>Data<input type="date" name="data" value="<?= e($dia) ?>" required></label>
    <label data-hora>Início<input type="time" name="hora_inicio" value="<?= e(substr($e['inicio'], 11, 5)) ?>"></label>
    <label data-hora>Fim<input type="time" name="hora_fim" value="<?= e(substr($e['fim'], 11, 5)) ?>"></label>
    <label>Termina em<input type="date" name="data_fim" value="<?= e($diaFim) ?>"><div class="help">Para compromissos de mais de um dia.</div></label>
  </div>
  <label style="margin-top:12px;font-weight:400"><input type="checkbox" name="dia_todo" value="1" data-dia-todo <?= $e['dia_todo'] ? 'checked' : '' ?>>Dia todo</label>

  <div class="form-grid" style="margin-top:12px">
    <label style="grid-column:1/-1">Local<input name="local" value="<?= e($e['local']) ?>" placeholder="Endereço, sala ou link da videochamada"></label>
    <label>Cliente
      <select name="cliente_id">
        <option value="">—</option>
        <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"<?= selected($e['cliente_id'], $c['id']) ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Projeto
      <select name="projeto_id">
        <option value="">—</option>
        <?php foreach ($projetos as $p): ?><option value="<?= $p['id'] ?>"<?= selected($e['projeto_id'], $p['id']) ?>><?= e($p['codigo']) ?> · <?= e($p['nome']) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>

  <fieldset style="border:1px solid var(--border);border-radius:8px;margin:14px 0 0;padding:10px 14px">
    <legend class="muted" style="padding:0 6px;font-size:13px">Participantes da equipe</legend>
    <div class="opcoes">
      <?php foreach ($equipe as $m): ?>
        <label><input type="checkbox" name="participantes[]" value="<?= $m['id'] ?>" <?= in_array((int) $m['id'], $ids, true) ? 'checked' : '' ?>><?= e($m['nome']) ?></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <label style="margin-top:12px">Descrição / pauta<textarea name="descricao"><?= e($e['descricao']) ?></textarea></label>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Salvar</button>
    <a class="btn" href="<?= $e['id'] ? url('agenda/evento', ['id' => $e['id']]) : url('agenda') ?>">Cancelar</a>
  </div>
</form>
