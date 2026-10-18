<?php
$secaoAtual = null;
$n = count($m['perguntas']);
$secoes = array_values(array_unique(array_filter(array_column($m['perguntas'], 'secao'))));
// Formulário de uma pergunta (edição ou nova)
$formPergunta = function (?array $p) use ($m, $secoes) { ?>
  <form method="post" action="<?= url('briefings/pergunta/salvar') ?>" class="form-grid" style="margin-top:10px">
    <?= csrf_field() ?>
    <input type="hidden" name="modelo_id" value="<?= $m['id'] ?>">
    <input type="hidden" name="id" value="<?= e($p['id'] ?? '') ?>">
    <label style="grid-column:1/-1">Pergunta<input name="texto" value="<?= e($p['texto'] ?? '') ?>" required maxlength="255"></label>
    <label>Seção<input name="secao" value="<?= e($p['secao'] ?? ($secoes ? end($secoes) : 'Geral')) ?>" list="secoes-modelo"></label>
    <label>Tipo de resposta
      <select name="tipo" data-tipo-pergunta>
        <?php foreach (TIPOS_PERGUNTA as $k => $v): ?><option value="<?= $k ?>"<?= selected($p['tipo'] ?? 'texto', $k) ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label data-opcoes style="grid-column:1/-1">Opções (uma por linha)<textarea name="opcoes" rows="4"><?= e($p['opcoes'] ?? '') ?></textarea></label>
    <label style="font-weight:400"><input type="checkbox" name="obrigatoria" value="1" <?= !empty($p['obrigatoria']) ? 'checked' : '' ?>>Obrigatória</label>
    <div><button class="btn <?= $p ? '' : 'btn-primary' ?>" type="submit"><?= $p ? 'Salvar pergunta' : 'Adicionar pergunta' ?></button></div>
  </form>
<?php };
?>
<datalist id="secoes-modelo"><?php foreach ($secoes as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>

<div class="grid grid-side">
  <div class="card">
    <div class="card-head"><h2>Perguntas</h2><small class="muted"><?= $n ?> no total</small></div>
    <?php if (!$m['perguntas']): ?>
      <p class="muted">Nenhuma pergunta ainda.</p>
    <?php endif; ?>
    <?php foreach ($m['perguntas'] as $i => $p):
      if ($p['secao'] !== $secaoAtual): $secaoAtual = $p['secao']; ?>
        <div class="secao-titulo"><?= e($secaoAtual ?: 'Geral') ?></div>
      <?php endif; ?>
      <div class="pergunta">
        <div class="row" style="flex-wrap:nowrap;align-items:flex-start">
          <div style="flex:1">
            <span class="enunciado"><?= $i + 1 ?>. <?= e($p['texto']) ?><?= $p['obrigatoria'] ? ' <span class="obrig">*</span>' : '' ?></span>
            <small class="muted"><?= e(TIPOS_PERGUNTA[$p['tipo']] ?? $p['tipo']) ?><?= $p['opcoes'] ? ': ' . e(implode(' · ', preg_split('/\R/', trim($p['opcoes'])))) : '' ?></small>
          </div>
          <?php if ($editar): ?>
            <div class="row" style="flex-wrap:nowrap;gap:4px">
              <?php foreach (['cima' => '↑', 'baixo' => '↓'] as $dir => $seta):
                if (($dir === 'cima' && $i === 0) || ($dir === 'baixo' && $i === $n - 1)) continue; ?>
                <form class="inline" method="post" action="<?= url('briefings/pergunta/mover') ?>">
                  <?= csrf_field() ?><input type="hidden" name="modelo_id" value="<?= $m['id'] ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="direcao" value="<?= $dir ?>">
                  <button class="btn btn-sm btn-ghost" type="submit" aria-label="Mover para <?= $dir ?>"><?= $seta ?></button>
                </form>
              <?php endforeach; ?>
              <form class="inline" method="post" action="<?= url('briefings/pergunta/excluir') ?>" data-confirm="Excluir esta pergunta?">
                <?= csrf_field() ?><input type="hidden" name="modelo_id" value="<?= $m['id'] ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-ghost btn-danger" type="submit" aria-label="Excluir">×</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($editar): ?>
          <details><summary class="muted" style="cursor:pointer;font-size:13px;margin-top:6px">Editar</summary><?php $formPergunta($p); ?></details>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($editar): ?>
      <div class="card" style="margin-top:18px;background:var(--surface-2);box-shadow:none">
        <h3>Nova pergunta</h3>
        <?php $formPergunta(null); ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <form class="card" method="post" action="<?= url('briefings/modelo/salvar') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
      <h2>Modelo</h2>
      <fieldset style="border:0;padding:0;margin:10px 0 0" <?= $editar ? '' : 'disabled' ?>>
        <label>Nome<input name="nome" value="<?= e($m['nome']) ?>" required></label>
        <label>Categoria<input name="categoria" value="<?= e($m['categoria']) ?>"></label>
        <label>Descrição<input name="descricao" value="<?= e($m['descricao']) ?>" maxlength="255"></label>
        <label style="font-weight:400"><input type="checkbox" name="ativo" value="1" <?= $m['ativo'] ? 'checked' : '' ?>>Ativo (aparece ao enviar briefing)</label>
        <?php if ($editar): ?><div class="form-actions"><button class="btn btn-primary" type="submit">Salvar</button></div><?php endif; ?>
      </fieldset>
    </form>
    <?php if ($editar): ?>
      <div class="card">
        <a class="btn btn-primary" href="<?= url('briefings/novo') ?>">Enviar este modelo a um cliente</a>
        <form method="post" action="<?= url('briefings/modelo/excluir') ?>" data-confirm="Excluir este modelo? Briefings já enviados continuam funcionando." style="margin-top:12px">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
          <button class="btn btn-ghost btn-danger" type="submit">Excluir modelo</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
