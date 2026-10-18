<div class="card" style="max-width:760px">
  <h2>Ver a agenda no celular</h2>
  <p class="muted">Assine o seu calendário pessoal no Google Agenda, no iPhone ou no Outlook. Aparecem os compromissos em que você participa e os prazos das suas tarefas. O calendário do celular atualiza sozinho, normalmente a cada poucas horas.</p>

  <?php if ($link): ?>
    <label style="margin-top:14px">Seu link pessoal</label>
    <div class="row" style="margin-top:4px;flex-wrap:nowrap">
      <input value="<?= e($link) ?>" readonly id="link-ics" style="margin:0">
      <button class="btn" type="button" data-copiar-de="link-ics">Copiar</button>
    </div>
    <p class="help">Não compartilhe este link: quem tiver ele vê a sua agenda.</p>

    <h3 style="margin-top:20px">Google Agenda</h3>
    <p class="help">No computador, abra o Google Agenda → "Outras agendas" → <strong>+</strong> → "Do URL" → cole o link → "Adicionar agenda".</p>
    <h3 style="margin-top:12px">iPhone</h3>
    <p class="help">Ajustes → Calendário → Contas → Adicionar conta → Outra → "Adicionar calendário assinado" → cole o link.</p>
    <h3 style="margin-top:12px">Outlook</h3>
    <p class="help">Calendário → "Adicionar calendário" → "Assinar da Web" → cole o link.</p>
    <p class="help" style="margin-top:12px">A sincronização só funciona quando o sistema está publicado na internet. No seu computador (localhost) o celular não alcança o link.</p>

    <form method="post" action="<?= url('agenda/token') ?>" data-confirm="Trocar o link? O link antigo para de funcionar e será preciso assinar de novo." style="margin-top:18px">
      <?= csrf_field() ?><button class="btn btn-ghost" type="submit">Trocar o link</button>
    </form>
  <?php else: ?>
    <form method="post" action="<?= url('agenda/token') ?>" style="margin-top:14px">
      <?= csrf_field() ?><button class="btn btn-primary" type="submit">Criar meu link de calendário</button>
    </form>
  <?php endif; ?>
</div>
