<?php
declare(strict_types=1);

function index(): void
{
    require_perm('equipe');
    $membros = all('SELECT * FROM usuario WHERE escritorio_id = ? ORDER BY ativo DESC, nome', [eid()]);
    view('equipe/index', ['titulo' => 'Equipe', 'membros' => $membros, 'aba' => 'membros']);
}

function form(): void
{
    require_perm('equipe', NIVEL_EDITAR);
    $id = input_int('id');
    $m = $id ? one('SELECT * FROM usuario WHERE id = ? AND escritorio_id = ?', [$id, eid()]) : null;
    if ($id && !$m) {
        not_found();
    }
    view('equipe/form', ['titulo' => $m ? 'Editar membro' : 'Novo membro', 'm' => $m]);
}

function salvar(): void
{
    require_perm('equipe', NIVEL_EDITAR);
    $eu = current_user();
    $id = input_int('id');
    $m = $id ? one('SELECT * FROM usuario WHERE id = ? AND escritorio_id = ?', [$id, eid()]) : null;
    if ($id && !$m) {
        not_found();
    }

    $perfil = (string) input('perfil');
    if (!isset(PERFIS[$perfil])) {
        $perfil = 'colaborador';
    }
    // Só um Sênior pode criar ou mexer em outro Sênior.
    if ($eu['perfil'] !== 'senior' && ($perfil === 'senior' || ($m['perfil'] ?? '') === 'senior')) {
        flash('Só um Sênior pode definir ou alterar o perfil Sênior.', 'erro');
        redirect('equipe');
    }

    $email = mb_strtolower((string) input('email'));
    $dados = [
        'nome' => (string) input('nome'),
        'email' => $email,
        'perfil' => $perfil,
        'cargo' => (string) input('cargo'),
        'horas_dia' => parse_num(input('horas_dia')),
        'dias_mes' => (int) input('dias_mes', 22),
        'salario' => parse_num(input('salario')),
        'ativo' => input('ativo') ? 1 : 0,
    ];
    if ($dados['nome'] === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Preencha nome e um e-mail válido.', 'erro');
        redirect('equipe/form', $id ? ['id' => $id] : []);
    }
    $outro = val('SELECT id FROM usuario WHERE email = ? AND id <> ?', [$email, $id]);
    if ($outro) {
        flash('Já existe um usuário com esse e-mail.', 'erro');
        redirect('equipe/form', $id ? ['id' => $id] : []);
    }
    if ($m && (int) $m['id'] === (int) $eu['id']) {
        $dados['ativo'] = 1;            // ninguém se desativa sem querer
        $dados['perfil'] = $m['perfil']; // nem muda o próprio perfil
    }

    $senha = (string) ($_POST['senha'] ?? '');
    if ($senha !== '') {
        if (strlen($senha) < 8) {
            flash('A senha precisa ter pelo menos 8 caracteres.', 'erro');
            redirect('equipe/form', $id ? ['id' => $id] : []);
        }
        $dados['senha_hash'] = password_hash($senha, PASSWORD_DEFAULT);
    } elseif (!$m) {
        flash('Defina uma senha inicial para o novo membro.', 'erro');
        redirect('equipe/form');
    }

    if ($m) {
        update('usuario', $dados, 'id = :id', ['id' => $id]);
        log_atividade("Editou o membro {$dados['nome']}");
    } else {
        $dados['escritorio_id'] = eid();
        $dados['criado_em'] = now();
        insert('usuario', $dados);
        log_atividade("Adicionou {$dados['nome']} à equipe");
    }
    flash('Membro salvo.');
    redirect('equipe');
}

function permissoes(): void
{
    require_perm('equipe');
    $membros = all('SELECT * FROM usuario WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome', [eid()]);
    view('equipe/permissoes', ['titulo' => 'Equipe', 'membros' => $membros, 'aba' => 'permissoes']);
}

function salvarPermissoes(): void
{
    require_perm('equipe', NIVEL_EDITAR);
    $niveis = $_POST['nivel'] ?? [];
    if (!is_array($niveis)) {
        $niveis = [];
    }
    foreach ($niveis as $uid => $mods) {
        $m = one("SELECT id, perfil FROM usuario WHERE id = ? AND escritorio_id = ?", [(int) $uid, eid()]);
        if (!$m || $m['perfil'] !== 'colaborador' || !is_array($mods)) {
            continue;
        }
        foreach ($mods as $mod => $nivel) {
            if (!isset(MODULOS[$mod])) {
                continue;
            }
            $nivel = max(NIVEL_SEM, min(NIVEL_EDITAR, (int) $nivel));
            $existe = val('SELECT id FROM permissao WHERE usuario_id = ? AND modulo = ?', [$m['id'], $mod]);
            if ($existe) {
                update('permissao', ['nivel' => $nivel], 'id = :id', ['id' => $existe]);
            } else {
                insert('permissao', ['usuario_id' => $m['id'], 'modulo' => $mod, 'nivel' => $nivel]);
            }
        }
    }
    log_atividade('Atualizou as permissões da equipe');
    flash('Permissões salvas.');
    redirect('equipe/permissoes');
}

function perfil(): void
{
    $u = current_user();
    if (is_post()) {
        $nome = (string) input('nome');
        $dados = ['nome' => $nome !== '' ? $nome : $u['nome']];
        $atual = (string) ($_POST['senha_atual'] ?? '');
        $nova = (string) ($_POST['senha_nova'] ?? '');
        if ($nova !== '') {
            if (!password_verify($atual, $u['senha_hash'])) {
                flash('A senha atual não confere.', 'erro');
                redirect('perfil');
            }
            if (strlen($nova) < 8) {
                flash('A nova senha precisa ter pelo menos 8 caracteres.', 'erro');
                redirect('perfil');
            }
            $dados['senha_hash'] = password_hash($nova, PASSWORD_DEFAULT);
        }
        update('usuario', $dados, 'id = :id', ['id' => $u['id']]);
        flash('Perfil atualizado.');
        redirect('perfil');
    }
    view('equipe/perfil', ['titulo' => 'Meu perfil', 'u' => $u]);
}
