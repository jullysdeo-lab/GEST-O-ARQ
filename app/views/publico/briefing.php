<?php $secaoAtual = null; ?>
<h1 style="font-size:24px"><?= e($b['titulo']) ?></h1>
<p class="muted">Olá, <?= e(explode(' ', $b['cliente_nome'])[0]) ?>! Suas respostas ajudam a entender o que você precisa. Leva uns minutos. Se quiser, salve e continue depois pelo mesmo link.</p>

<?php if ($salvo): ?><div class="flash flash-ok" style="margin-top:12px">Respostas salvas. Você pode continuar depois pelo mesmo link.</div><?php endif; ?>
<?php if ($faltando): ?><div class="flash flash-erro" style="margin-top:12px">Falta responder <?= count($faltando) ?> pergunta(s) obrigatória(s), marcadas abaixo.</div><?php endif; ?>

<form method="post" action="<?= url('briefing', ['t' => $t]) ?>" class="card" style="margin-top:12px">
  <?= csrf_field() ?>
  <?php foreach ($perguntas as $i => $p):
    $k = $p['k'];
    $r = $respostas[$k] ?? ($p['tipo'] === 'multipla' ? [] : '');
    $nome = 'r[' . e($k) . ']';
    $erro = in_array($k, $faltando, true);
    if ($p['secao'] !== $secaoAtual): $secaoAtual = $p['secao']; ?>
      <div class="secao-titulo"><?= e($secaoAtual) ?></div>
    <?php endif; ?>
    <div class="pergunta" id="<?= e($k) ?>" <?= $erro ? 'style="background:var(--erro-soft);margin:0 -18px;padding:14px 18px"' : '' ?>>
      <label class="enunciado" for="in-<?= e($k) ?>"><?= e($p['texto']) ?><?= $p['obrigatoria'] ? ' <span class="obrig">*</span>' : '' ?></label>
      <?php switch ($p['tipo']):
        case 'texto_longo': ?>
          <textarea id="in-<?= e($k) ?>" name="<?= $nome ?>" rows="4"><?= e($r) ?></textarea>
          <?php break;
        case 'numero': ?>
          <input id="in-<?= e($k) ?>" name="<?= $nome ?>" value="<?= e($r === '' ? '' : num($r, (float) $r == (int) $r ? 0 : 2)) ?>" inputmode="decimal" style="max-width:220px">
          <?php break;
        case 'data': ?>
          <input id="in-<?= e($k) ?>" type="date" name="<?= $nome ?>" value="<?= e($r) ?>" style="max-width:220px">
          <?php break;
        case 'sim_nao': ?>
          <div class="opcoes">
            <label><input type="radio" name="<?= $nome ?>" value="sim" <?= $r === 'sim' ? 'checked' : '' ?>>Sim</label>
            <label><input type="radio" name="<?= $nome ?>" value="nao" <?= $r === 'nao' ? 'checked' : '' ?>>Não</label>
          </div>
          <?php break;
        case 'escala': ?>
          <div class="opcoes">
            <?php for ($n = 1; $n <= 5; $n++): ?><label><input type="radio" name="<?= $nome ?>" value="<?= $n ?>" <?= (string) $r === (string) $n ? 'checked' : '' ?>><?= $n ?></label><?php endfor; ?>
            <small class="muted" style="align-self:center">1 = pouco importante · 5 = essencial</small>
          </div>
          <?php break;
        case 'opcao_unica': ?>
          <div class="opcoes">
            <?php foreach ($p['opcoes'] as $op): ?><label><input type="radio" name="<?= $nome ?>" value="<?= e($op) ?>" <?= $r === $op ? 'checked' : '' ?>><?= e($op) ?></label><?php endforeach; ?>
          </div>
          <?php break;
        case 'multipla': ?>
          <div class="opcoes">
            <?php foreach ($p['opcoes'] as $op): ?><label><input type="checkbox" name="<?= $nome ?>[]" value="<?= e($op) ?>" <?= in_array($op, (array) $r, true) ? 'checked' : '' ?>><?= e($op) ?></label><?php endforeach; ?>
          </div>
          <?php break;
        default: ?>
          <input id="in-<?= e($k) ?>" name="<?= $nome ?>" value="<?= e($r) ?>" maxlength="500">
      <?php endswitch; ?>
    </div>
  <?php endforeach; ?>

  <div class="form-actions" style="flex-wrap:wrap">
    <button class="btn btn-primary" type="submit" name="acao" value="enviar">Enviar respostas</button>
    <button class="btn" type="submit" name="acao" value="salvar">Salvar e continuar depois</button>
  </div>
</form>
