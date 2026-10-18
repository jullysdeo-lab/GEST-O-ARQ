<?php require __DIR__ . '/_tabs.php'; ?>
<div class="card">
  <div class="card-head">
    <div>
      <h2>Membros da equipe</h2>
      <p class="muted">As horas e salários de quem está ativo entram no cálculo da hora técnica.</p>
    </div>
    <?php if (can('equipe', NIVEL_EDITAR)): ?>
      <a class="btn btn-primary" href="<?= url('equipe/form') ?>">+ Novo membro</a>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Nome</th><th>Perfil</th><th>Cargo</th><th class="num">Horas/mês</th><th class="num">Custo/hora</th><th>Último acesso</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($membros as $m):
        $h = $m['horas_dia'] * $m['dias_mes']; ?>
        <tr>
          <td><div class="row"><span class="avatar"><?= e(initials($m['nome'])) ?></span><div><strong><?= e($m['nome']) ?></strong><br><small class="muted"><?= e($m['email']) ?></small></div></div></td>
          <td><?= e(PERFIS[$m['perfil']] ?? $m['perfil']) ?></td>
          <td><?= e($m['cargo'] ?: '—') ?></td>
          <td class="num"><?= num($h) ?> h</td>
          <td class="num"><?= $h > 0 && $m['salario'] > 0 ? money($m['salario'] / $h) : '—' ?></td>
          <td><?= $m['ultimo_acesso'] ? e(date('d/m/Y H:i', strtotime($m['ultimo_acesso']))) : 'Nunca' ?></td>
          <td><span class="badge <?= $m['ativo'] ? 'badge-ok' : '' ?>"><?= $m['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="num"><?php if (can('equipe', NIVEL_EDITAR)): ?><a class="btn btn-sm" href="<?= url('equipe/form', ['id' => $m['id']]) ?>">Editar</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
