<?php
// Cartão de um lead no quadro. Espera $l.
$dias = dias_desde($l['ultimo_contato'] ?: $l['criado_em']);
$aberto = in_array($l['funil_etapa'], FUNIL_ABERTO, true);
$esquecido = $aberto && $dias !== null && $dias > DIAS_LEAD_FRIO;
$retornoVencido = $aberto && $l['proximo_contato'] && $l['proximo_contato'] <= today();
$valor = $l['valor_proposta'] ?: $l['valor_estimado'];
?>
<a class="lead-card temp-<?= e($l['temperatura'] ?: 'morno') ?>" href="<?= url('crm/lead', ['id' => $l['id']]) ?>"
   draggable="<?= can('crm', NIVEL_EDITAR) ? 'true' : 'false' ?>" data-lead="<?= (int) $l['id'] ?>">
  <strong><?= e($l['nome']) ?></strong>
  <?php if ($l['tipo_projeto']): ?><small class="muted"><?= e($l['tipo_projeto']) ?></small><?php endif; ?>
  <?php if ($valor): ?><span class="lead-valor"><?= money($valor) ?></span><?php endif; ?>
  <span class="lead-meta">
    <?php if ($retornoVencido): ?><span class="badge badge-atrasado">Retornar <?= $l['proximo_contato'] === today() ? 'hoje' : brdate($l['proximo_contato']) ?></span>
    <?php elseif ($aberto && $l['proximo_contato']): ?><span class="badge">Retorno <?= brdate($l['proximo_contato']) ?></span><?php endif; ?>
    <?php if ($esquecido): ?><span class="badge badge-warn"><?= $dias ?> dias sem contato</span><?php endif; ?>
    <?php if ($l['funil_etapa'] === 'perdido' && $l['motivo_perda']): ?><small class="muted"><?= e($l['motivo_perda']) ?></small><?php endif; ?>
  </span>
  <?php if ($l['responsavel_nome']): ?><span class="avatar avatar-sm" title="<?= e($l['responsavel_nome']) ?>"><?= e(initials($l['responsavel_nome'])) ?></span><?php endif; ?>
</a>
