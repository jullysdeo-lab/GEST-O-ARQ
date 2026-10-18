<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($d['titulo']) ?></title>
<link rel="stylesheet" href="<?= asset('app.css') ?>">
</head>
<body style="background:#eee">
<div class="no-print" style="text-align:center;padding:16px"><button class="btn btn-primary" onclick="window.print()">Imprimir ou salvar em PDF</button></div>
<div class="print-doc doc-preview">
  <?= doc_html($d['conteudo']) ?>

  <?php if ($d['status'] === 'assinado' && $d['assinado_ip']): ?>
    <div class="assinatura-eletronica">
      <strong>Assinado eletronicamente</strong><br>
      Por <?= e($d['assinado_nome']) ?><?= $d['assinado_documento'] ? ', CPF/CNPJ ' . e($d['assinado_documento']) : '' ?>,
      em <?= e(date('d/m/Y \à\s H:i', strtotime($d['assinado_em']))) ?>, IP <?= e($d['assinado_ip']) ?>.<br>
      Código de integridade (SHA-256): <?= e($d['assinado_hash']) ?>
    </div>
  <?php elseif ($d['tipo'] !== 'ata'): ?>
    <div class="assinaturas">
      <div><span></span><?= e($esc) ?><?= $responsavel ? '<br>' . e($responsavel) : '' ?><?= $cau ? '<br>CAU ' . e($cau) : '' ?><br><small>CONTRATADA</small></div>
      <div><span></span><?= e($d['cliente_nome']) ?><br><small>CONTRATANTE</small></div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
