<?php
declare(strict_types=1);

function dados(): void
{
    require_perm('equipe');
    if (is_post()) {
        require_perm('equipe', NIVEL_EDITAR);
        $nome = (string) input('nome');
        if ($nome !== '') {
            update('escritorio', ['nome' => $nome], 'id = :id', ['id' => eid()]);
        }
        foreach (array_keys(DADOS_ESCRITORIO) as $k) {
            set_conf_texto($k, (string) input($k, ''));
        }
        log_atividade('Atualizou os dados do escritório');
        flash('Dados do escritório salvos.');
        redirect('escritorio');
    }
    $valores = [];
    foreach (array_keys(DADOS_ESCRITORIO) as $k) {
        $valores[$k] = conf_texto($k);
    }
    view('escritorio/dados', [
        'titulo' => 'Equipe',
        'aba' => 'escritorio',
        'nome' => val('SELECT nome FROM escritorio WHERE id = ?', [eid()]),
        'valores' => $valores,
    ]);
}
