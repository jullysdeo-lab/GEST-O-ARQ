<?php
// Mostra perguntas e respostas agrupadas por seção. Espera $b com 'perguntas' e 'respostas'.
$secaoAtual = null;
foreach ($b['perguntas'] as $p):
    $r = $b['respostas'][$p['k']] ?? null;
    if ($p['secao'] !== $secaoAtual):
        $secaoAtual = $p['secao']; ?>
    <div class="secao-titulo"><?= e($secaoAtual) ?></div>
  <?php endif; ?>
  <div class="pergunta">
    <span class="enunciado"><?= e($p['texto']) ?></span>
    <?php if ($r === null || $r === '' || $r === []): ?>
      <span class="sem-resposta">Sem resposta</span>
    <?php elseif (is_array($r)): ?>
      <div class="opcoes"><?php foreach ($r as $op): ?><span class="badge badge-info"><?= e($op) ?></span><?php endforeach; ?></div>
    <?php elseif ($p['tipo'] === 'escala'): $nota = max(0, min(5, (int) $r)); ?>
      <span class="resposta"><?= str_repeat('●', $nota) . str_repeat('○', 5 - $nota) ?> <small class="muted"><?= $nota ?>/5</small></span>
    <?php elseif ($p['tipo'] === 'data'): ?>
      <span class="resposta"><?= brdate($r) ?></span>
    <?php elseif ($p['tipo'] === 'sim_nao'): ?>
      <span class="resposta"><?= $r === 'sim' ? 'Sim' : 'Não' ?></span>
    <?php else: ?>
      <span class="resposta"><?= e($r) ?></span>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
