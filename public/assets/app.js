(function () {
  // Tema claro/escuro
  document.querySelectorAll('[data-toggle-theme]').forEach(function (b) {
    b.addEventListener('click', function () {
      var root = document.documentElement;
      var escuro = root.dataset.theme !== 'dark';
      if (escuro) root.dataset.theme = 'dark'; else delete root.dataset.theme;
      try { localStorage.setItem('tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
    });
  });

  // Menu lateral no celular
  var sidebar = document.getElementById('sidebar');
  document.querySelectorAll('[data-toggle-sidebar]').forEach(function (b) {
    b.addEventListener('click', function (ev) { ev.stopPropagation(); sidebar.classList.toggle('open'); });
  });
  document.addEventListener('click', function (ev) {
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(ev.target)) sidebar.classList.remove('open');
  });

  // Confirmação antes de ações destrutivas
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.dataset.confirm)) ev.preventDefault();
    });
  });

  // Financeiro: lista só os projetos do cliente escolhido
  var cli = document.querySelector('[data-cliente-projetos]');
  if (cli) {
    var proj = document.getElementById(cli.dataset.clienteProjetos);
    cli.addEventListener('change', function () {
      var atual = proj.value;
      proj.innerHTML = '<option value="">— nenhum —</option>';
      if (!cli.value) return;
      fetch(cli.dataset.url + '?cliente_id=' + encodeURIComponent(cli.value), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (lista) {
          lista.forEach(function (p) {
            var o = document.createElement('option');
            o.value = p.id; o.textContent = p.codigo + ' · ' + p.nome;
            if (String(p.id) === atual) o.selected = true;
            proj.appendChild(o);
          });
        });
    });
  }

  // Confirmação em botões específicos (quando o formulário tem vários botões)
  document.querySelectorAll('[data-confirm-btn]').forEach(function (b) {
    b.addEventListener('click', function (ev) {
      if (!window.confirm(b.dataset.confirmBtn)) ev.preventDefault();
    });
  });

  // Copiar texto
  function copiar(texto, botao) {
    var ok = function () {
      var antes = botao.textContent;
      botao.textContent = 'Copiado!';
      setTimeout(function () { botao.textContent = antes; }, 1200);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(texto).then(ok, function () {});
    } else {
      var t = document.createElement('textarea');
      t.value = texto; document.body.appendChild(t); t.select();
      try { document.execCommand('copy'); ok(); } catch (e) {}
      document.body.removeChild(t);
    }
  }
  document.querySelectorAll('[data-copiar]').forEach(function (b) {
    b.addEventListener('click', function () { copiar(b.dataset.copiar, b); });
  });
  document.querySelectorAll('[data-copiar-de]').forEach(function (b) {
    b.addEventListener('click', function () { copiar(document.getElementById(b.dataset.copiarDe).value, b); });
  });

  // Editor de perguntas: só mostra o campo de opções nos tipos de escolha
  document.querySelectorAll('[data-tipo-pergunta]').forEach(function (sel) {
    var campo = sel.form.querySelector('[data-opcoes]');
    var ajustar = function () {
      campo.style.display = (sel.value === 'opcao_unica' || sel.value === 'multipla') ? '' : 'none';
    };
    sel.addEventListener('change', ajustar);
    ajustar();
  });

  // CRM: arrastar cartões entre as etapas do funil
  var kanban = document.querySelector('.kanban[data-mover-url]');
  if (kanban) {
    var csrf = (document.querySelector('meta[name="csrf"]') || {}).content || '';
    var arrastado = null;
    kanban.addEventListener('dragstart', function (ev) {
      var card = ev.target.closest('.lead-card[draggable="true"]');
      if (!card) return;
      arrastado = card;
      card.classList.add('arrastando');
      ev.dataTransfer.effectAllowed = 'move';
      ev.dataTransfer.setData('text/plain', card.dataset.lead);
    });
    kanban.addEventListener('dragend', function () {
      if (arrastado) arrastado.classList.remove('arrastando');
      kanban.querySelectorAll('.alvo').forEach(function (c) { c.classList.remove('alvo'); });
    });
    kanban.querySelectorAll('.kanban-col').forEach(function (col) {
      col.addEventListener('dragover', function (ev) { if (arrastado) { ev.preventDefault(); col.classList.add('alvo'); } });
      col.addEventListener('dragleave', function (ev) { if (!col.contains(ev.relatedTarget)) col.classList.remove('alvo'); });
      col.addEventListener('drop', function (ev) {
        ev.preventDefault();
        col.classList.remove('alvo');
        if (!arrastado || arrastado.closest('.kanban-col') === col) return;
        var card = arrastado;
        var origem = card.parentNode;
        col.querySelector('.kanban-lista').prepend(card);
        var corpo = new URLSearchParams({ id: card.dataset.lead, etapa: col.dataset.etapa, _csrf: csrf });
        fetch(kanban.dataset.moverUrl, {
          method: 'POST', credentials: 'same-origin', body: corpo,
          headers: { 'X-Requested-With': 'fetch', 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(function (r) { return r.json(); }).then(function (res) {
          if (res.abrir) { window.location = res.abrir; return; }
          if (!res.ok) { origem.prepend(card); alert(res.erro || 'Não foi possível mudar a etapa.'); return; }
          // Atualiza as contagens das colunas
          kanban.querySelectorAll('.kanban-col').forEach(function (c) {
            var b = c.querySelector('header .badge');
            if (b) b.textContent = c.querySelectorAll('.lead-card').length;
          });
        }).catch(function () { origem.prepend(card); });
      });
    });
  }

  // Agenda: esconde os horários quando é "dia todo"
  document.querySelectorAll('[data-dia-todo]').forEach(function (chk) {
    var ajustar = function () {
      chk.form.querySelectorAll('[data-hora]').forEach(function (l) { l.style.display = chk.checked ? 'none' : ''; });
    };
    chk.addEventListener('change', ajustar);
    ajustar();
  });

  // Envia o formulário ao trocar um select marcado com data-autosubmit
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
})();
