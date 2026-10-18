<div class="card">
  <h3>Campos automáticos</h3>
  <p class="help">Clique para copiar. No documento gerado, cada campo vira o dado real do escritório, cliente, projeto ou orçamento.</p>
  <?php foreach (CAMPOS_DOCUMENTO as $grupo => $campos): ?>
    <div style="margin-top:10px"><small class="muted"><?= e($grupo) ?></small><br>
      <?php foreach ($campos as $c): ?>
        <button type="button" class="badge" data-copiar="{{<?= e($c) ?>}}" style="border:0;cursor:pointer;margin:2px 2px 0 0">{{<?= e($c) ?>}}</button>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  <h3 style="margin-top:16px">Formatação</h3>
  <p class="help"><code># Título</code> · <code>## Seção</code> · <code>- item de lista</code> · <code>**negrito**</code> · linha em branco separa parágrafos · <code>[texto]</code> fica destacado para preencher.</p>
</div>
