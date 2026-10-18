<?php if ($d['status'] === 'assinado'): ?>
  <div class="flash flash-ok">
    Documento assinado por <?= e($d['assinado_nome']) ?> em <?= e(date('d/m/Y \à\s H:i', strtotime($d['assinado_em']))) ?>.
    Guarde este link: ele continua mostrando o documento assinado.
  </div>
<?php else: ?>
  <p class="muted">Leia o documento até o fim. Para assinar, informe seus dados e confirme no final da página.</p>
<?php endif; ?>

<div class="card doc-preview" style="margin-top:12px">
  <?= doc_html($d['conteudo']) ?>
</div>

<?php if ($d['status'] === 'assinado'): ?>
  <div class="card" style="margin-top:16px">
    <h3>Registro da assinatura</h3>
    <p class="help" style="word-break:break-all">
      Nome: <?= e($d['assinado_nome']) ?> · CPF/CNPJ: <?= e($d['assinado_documento']) ?><br>
      Data e hora: <?= e(date('d/m/Y H:i:s', strtotime($d['assinado_em']))) ?> · IP: <?= e($d['assinado_ip']) ?><br>
      Código de integridade (SHA-256): <?= e($d['assinado_hash']) ?>
    </p>
    <button class="btn" type="button" onclick="window.print()" style="margin-top:8px">Imprimir ou salvar em PDF</button>
  </div>
<?php else: ?>
  <form class="card" method="post" action="<?= url('assinar', ['t' => $t]) ?>" style="margin-top:16px">
    <?= csrf_field() ?>
    <input type="hidden" name="versao" value="<?= e(hash('sha256', $d['conteudo'])) ?>">
    <h2>Assinar</h2>
    <?php if ($erro): ?><div class="flash flash-erro" style="margin-top:10px"><?= e($erro) ?></div><?php endif; ?>
    <div class="form-grid" style="margin-top:10px">
      <label>Nome completo<input name="nome" value="<?= e(input('nome', $d['cliente_nome'])) ?>" required autocomplete="name"></label>
      <label>CPF ou CNPJ<input name="documento" value="<?= e(input('documento', '')) ?>" required inputmode="numeric"></label>
    </div>
    <label style="margin-top:14px;font-weight:400;display:flex;gap:8px;align-items:flex-start">
      <input type="checkbox" name="aceite" value="1" required style="margin-top:3px">
      <span>Li o documento acima, concordo com o seu conteúdo e assino eletronicamente. Entendo que serão registrados meu nome, CPF/CNPJ, data, hora e endereço IP.</span>
    </label>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Assinar documento</button></div>
  </form>
<?php endif; ?>
