<?php
$u = current_user();
$atual = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$atual = trim(substr($atual, strlen(trim(base_path(), '/'))), '/');
$menu = [
    ['', 'Painel', 'central', 'M3 13h8V3H3zm10 8h8V11h-8zM3 21h8v-6H3zm10-18v6h8V3z'],
    ['agenda', 'Agenda', 'agenda', 'M4 5h16v16H4zM4 10h16M9 3v4m6-4v4'],
    ['crm', 'CRM', 'crm', 'M3 4h18l-7 8v6l-4 2v-8z'],
    ['clientes', 'Clientes', 'clientes', 'M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 21a8 8 0 0 1 16 0'],
    ['orcamentos', 'Orçamentos', 'precificacao', 'M7 3h10l4 4v14H3V3zm2 6h6m-6 4h8m-8 4h5'],
    ['projetos', 'Projetos', 'projetos', 'M3 7h18v13H3zM8 7V4h8v3'],
    ['tarefas', 'Tarefas', 'projetos', 'M9 11l3 3 8-8M4 12v7h16'],
    ['projetos/cronograma', 'Cronograma', 'projetos', 'M4 6h9M8 12h10M6 18h7M3 3v18'],
    ['briefings', 'Briefings', 'briefings', 'M9 4h6l1 2h3v15H5V6h3zm0 7h6m-6 4h4'],
    ['documentos', 'Documentos', 'documentos', 'M14 3H6v18h12V7zm0 0v4h4M9 13h6m-6 4h6'],
    ['financeiro', 'Financeiro', 'financeiro', 'M12 3v18m5-14H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
    ['precificacao', 'Precificação', 'precificacao', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
    ['equipe', 'Equipe', 'equipe', 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm11 10v-2a4 4 0 0 0-3-3.9'],
];
// Item ativo do menu: o mais específico que casa com a página atual.
$ativoMenu = null;
foreach ($menu as [$href]) {
    $casa = $href === '' ? $atual === '' : ($atual === $href || str_starts_with($atual, $href . '/'));
    if ($casa && ($ativoMenu === null || strlen($href) > strlen($ativoMenu))) {
        $ativoMenu = $href;
    }
}
if ($ativoMenu === null && str_starts_with($atual, 'escritorio')) {
    $ativoMenu = 'equipe';
}
$flash = flash();
$escritorio = val('SELECT nome FROM escritorio WHERE id = ?', [$u['escritorio_id']]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title><?= e(($titulo ?? '') ? $titulo . ' · ' : '') ?><?= e(config('app_name')) ?></title>
<link rel="stylesheet" href="<?= asset('app.css') ?>">
<script>try{if(localStorage.getItem('tema')==='escuro')document.documentElement.dataset.theme='dark'}catch(e){}</script>
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark"><?= e(mb_substr(config('app_name'), 0, 1)) ?></span>
      <div>
        <strong><?= e(config('app_name')) ?></strong>
        <small><?= e($escritorio) ?></small>
      </div>
    </div>
    <nav>
      <?php foreach ($menu as [$href, $label, $mod, $icon]): if (!can($mod)) continue;
        $ativo = $href === $ativoMenu; ?>
        <a href="<?= url($href) ?>" class="<?= $ativo ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= $icon ?>"/></svg>
          <span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= url('perfil') ?>" class="me">
        <span class="avatar"><?= e(initials($u['nome'])) ?></span>
        <span><strong><?= e($u['nome']) ?></strong><small><?= e(PERFIS[$u['perfil']] ?? $u['perfil']) ?></small></span>
      </a>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn only-mobile" type="button" data-toggle-sidebar aria-label="Abrir menu">
        <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <h1><?= e($titulo ?? '') ?></h1>
      <div class="topbar-actions">
        <button class="icon-btn" type="button" data-toggle-theme aria-label="Alternar tema">
          <svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </button>
        <form method="post" action="<?= url('logout') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-ghost btn-sm" type="submit">Sair</button>
        </form>
      </div>
    </header>

    <main class="content">
      <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= asset('app.js') ?>"></script>
</body>
</html>
