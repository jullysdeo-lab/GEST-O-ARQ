<form method="post" action="<?= url('documentos/editar', ['id' => $d['id']]) ?>" class="grid grid-side">
  <?= csrf_field() ?>
  <div class="card">
    <?php if ($d['status'] === 'enviado'): ?>
      <div class="flash flash-warn">Este documento já foi enviado. Se mudar o texto, ele volta para rascunho e o link antigo deixa de funcionar.</div>
    <?php endif; ?>
    <label>Título<input name="titulo" value="<?= e($d['titulo']) ?>" required></label>
    <label>Texto do documento
      <textarea name="conteudo" rows="30" style="font-family:ui-monospace,Consolas,monospace;font-size:13px"><?= e($d['conteudo']) ?></textarea>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit" name="ir_para" value="ver">Salvar e visualizar</button>
      <button class="btn" type="submit">Salvar</button>
      <button class="btn btn-ghost" type="submit" name="regerar" value="1" data-confirm-btn="Refazer o texto a partir do modelo? As edições feitas aqui serão perdidas.">Refazer a partir do modelo</button>
      <a class="btn btn-ghost" href="<?= url('documentos/ver', ['id' => $d['id']]) ?>">Voltar</a>
    </div>
  </div>
  <div class="stack">
    <div class="card">
      <h3>Vinculado a</h3>
      <table class="summary-table" style="margin-top:6px">
        <tr><td>Cliente</td><td><?= e($d['cliente_nome']) ?></td></tr>
        <tr><td>Projeto</td><td><?= e($d['projeto_codigo'] ?: '—') ?></td></tr>
        <tr><td>Orçamento</td><td><?= e($d['orcamento_numero'] ?: '—') ?></td></tr>
      </table>
      <p class="help">Faltou algum dado? Complete o cadastro do <a href="<?= url('clientes/form', ['id' => $d['cliente_id']]) ?>">cliente</a> ou os <a href="<?= url('escritorio') ?>">dados do escritório</a> e use "Refazer a partir do modelo".</p>
    </div>
    <?php require __DIR__ . '/_campos.php'; ?>
  </div>
</form>
