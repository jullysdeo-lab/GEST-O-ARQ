<?php
$editar = can('documentos', NIVEL_EDITAR);
$pendentes = count(pendencias_documento($d['conteudo']));
?>
<div class="grid grid-side">
  <div class="card doc-preview">
    <?= doc_html($d['conteudo']) ?>
  </div>

  <div class="stack">
    <div class="card">
      <div class="row"><h2>Situação</h2><div class="spacer"></div>
        <span class="badge badge-<?= $d['status'] === 'assinado' ? 'ok' : ($d['status'] === 'enviado' ? 'warn' : '') ?>"><?= DOC_STATUS[$d['status']] ?></span></div>
      <table class="summary-table" style="margin-top:8px">
        <tr><td>Cliente</td><td><?= e($d['cliente_nome']) ?></td></tr>
        <?php if ($d['projeto_codigo']): ?><tr><td>Projeto</td><td><a href="<?= url('projetos/ver', ['id' => $d['projeto_id']]) ?>"><?= e($d['projeto_codigo']) ?></a></td></tr><?php endif; ?>
        <?php if ($d['orcamento_numero']): ?><tr><td>Orçamento</td><td><a href="<?= url('orcamentos/ver', ['id' => $d['orcamento_id']]) ?>"><?= e($d['orcamento_numero']) ?></a></td></tr><?php endif; ?>
        <tr><td>Criado</td><td><?= brdate($d['criado_em']) ?></td></tr>
        <?php if ($d['enviado_em']): ?><tr><td>Enviado</td><td><?= brdate($d['enviado_em']) ?></td></tr><?php endif; ?>
      </table>
      <?php if ($pendentes && $d['status'] !== 'assinado'): ?>
        <div class="flash flash-warn" style="margin:12px 0 0"><?= $pendentes ?> trecho(s) <mark>[entre colchetes]</mark> ainda para preencher.</div>
      <?php endif; ?>
      <div class="row" style="margin-top:14px">
        <a class="btn" href="<?= url('documentos/imprimir', ['id' => $d['id']]) ?>" target="_blank">Imprimir / PDF</a>
        <?php if ($editar && $d['status'] !== 'assinado'): ?><a class="btn" href="<?= url('documentos/editar', ['id' => $d['id']]) ?>">Editar texto</a><?php endif; ?>
      </div>
    </div>

    <?php if ($d['status'] === 'assinado'): ?>
      <div class="card">
        <h2>Assinatura</h2>
        <table class="summary-table" style="margin-top:8px">
          <tr><td>Assinado por</td><td><?= e($d['assinado_nome']) ?></td></tr>
          <?php if ($d['assinado_documento']): ?><tr><td>CPF/CNPJ</td><td><?= e($d['assinado_documento']) ?></td></tr><?php endif; ?>
          <tr><td>Data e hora</td><td><?= e(date('d/m/Y H:i', strtotime($d['assinado_em']))) ?></td></tr>
          <?php if ($d['assinado_ip']): ?><tr><td>IP</td><td><?= e($d['assinado_ip']) ?></td></tr><?php endif; ?>
        </table>
        <p class="help" style="word-break:break-all">Registro: <?= e($d['assinado_agente']) ?><br>Código de integridade (SHA-256): <code><?= e($d['assinado_hash']) ?></code></p>
        <div class="flash <?= $integro ? 'flash-ok' : 'flash-erro' ?>" style="margin:10px 0 0">
          <?= $integro ? 'O texto é idêntico ao que foi assinado.' : 'Atenção: o texto não confere com o código registrado na assinatura.' ?>
        </div>
      </div>
    <?php elseif ($editar): ?>
      <div class="card">
        <h2>Assinatura eletrônica</h2>
        <?php if ($link): ?>
          <p class="help">Envie este link ao cliente. Ele lê o documento, informa nome e CPF e confirma o aceite. O sistema registra data, hora, IP e o código de integridade do texto.</p>
          <div class="row" style="margin-top:8px;flex-wrap:nowrap">
            <input value="<?= e($link) ?>" readonly id="link-assinatura" style="margin:0">
            <button class="btn" type="button" data-copiar-de="link-assinatura">Copiar</button>
          </div>
          <?php if ($d['cliente_email']): ?>
            <a class="btn btn-ghost btn-sm" style="margin-top:8px" href="mailto:<?= e($d['cliente_email']) ?>?subject=<?= rawurlencode($d['titulo']) ?>&body=<?= rawurlencode("Olá! Segue o link para leitura e assinatura do documento:\n\n$link") ?>">Abrir no e-mail</a>
          <?php endif; ?>
        <?php else: ?>
          <p class="help">Gere um link para o cliente assinar pelo celular ou computador, sem precisar criar conta.</p>
          <form method="post" action="<?= url('documentos/enviar') ?>" style="margin-top:10px">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>">
            <?php if (input('pendente')): ?><input type="hidden" name="mesmo_assim" value="1"><?php endif; ?>
            <button class="btn btn-primary" type="submit"><?= input('pendente') ? 'Enviar mesmo assim' : 'Gerar link de assinatura' ?></button>
          </form>
        <?php endif; ?>

        <details style="margin-top:16px">
          <summary class="muted" style="cursor:pointer">Foi assinado em papel ou em outra plataforma?</summary>
          <form method="post" action="<?= url('documentos/assinado') ?>" style="margin-top:10px">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>">
            <label>Assinado por<input name="nome" value="<?= e($d['cliente_nome']) ?>"></label>
            <label>Data<input type="date" name="data" value="<?= today() ?>"></label>
            <button class="btn" type="submit" style="margin-top:10px">Registrar como assinado</button>
          </form>
        </details>
      </div>
      <form method="post" action="<?= url('documentos/excluir') ?>" data-confirm="Excluir este documento?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>">
        <button class="btn btn-ghost btn-danger" type="submit">Excluir documento</button>
      </form>
    <?php endif; ?>
  </div>
</div>
