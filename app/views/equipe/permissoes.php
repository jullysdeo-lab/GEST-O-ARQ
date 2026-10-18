<?php require __DIR__ . '/_tabs.php';
$editar = can('equipe', NIVEL_EDITAR); ?>
<form class="card" method="post" action="<?= url('equipe/permissoes/salvar') ?>">
  <?= csrf_field() ?>
  <div class="card-head">
    <div>
      <h2>Níveis de permissão</h2>
      <p class="muted">Para cada pessoa e módulo: <strong>Sem acesso</strong>, <strong>Ver</strong> (consulta sem alterar) ou <strong>Editar</strong> (libera tudo). Perfis Sênior, Administrativo e Gestor de Projetos têm regras fixas.</p>
    </div>
    <?php if ($editar): ?><button class="btn btn-primary" type="submit">Salvar permissões</button><?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Pessoa</th><?php foreach (MODULOS as $mod => $nome): ?><th><?= e($nome) ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
      <?php foreach ($membros as $m): $fixo = $m['perfil'] !== 'colaborador'; ?>
        <tr>
          <td><strong><?= e($m['nome']) ?></strong><br><small class="muted"><?= e(PERFIS[$m['perfil']]) ?></small></td>
          <?php foreach (MODULOS as $mod => $nome): $n = nivel($mod, $m); ?>
            <td>
              <?php if ($fixo || !$editar): ?>
                <span class="badge <?= $n === NIVEL_EDITAR ? 'badge-ok' : ($n === NIVEL_VER ? 'badge-info' : '') ?>"><?= NIVEIS[$n] ?></span>
              <?php else: ?>
                <select name="nivel[<?= (int) $m['id'] ?>][<?= $mod ?>]" style="min-width:110px">
                  <?php foreach (NIVEIS as $k => $v): ?><option value="<?= $k ?>"<?= selected($n, $k) ?>><?= $v ?></option><?php endforeach; ?>
                </select>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</form>
