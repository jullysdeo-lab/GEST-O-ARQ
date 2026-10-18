<?php
declare(strict_types=1);

// No servidor embutido do PHP, deixa os arquivos estáticos (CSS, JS) passarem direto.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require dirname(__DIR__) . '/app/bootstrap.php';

start_session();
check_csrf();

$routes = [
    'login' => ['auth', 'login'],
    'logout' => ['auth', 'sair'],

    '' => ['central', 'index'],

    'clientes' => ['clientes', 'index'],
    'clientes/form' => ['clientes', 'form'],
    'clientes/salvar' => ['clientes', 'salvar'],
    'clientes/excluir' => ['clientes', 'excluir'],

    'precificacao' => ['precificacao', 'configuracoes'],
    'precificacao/parametros' => ['precificacao', 'salvarParametros'],
    'precificacao/custo' => ['precificacao', 'salvarCusto'],
    'precificacao/custo/excluir' => ['precificacao', 'excluirCusto'],
    'precificacao/templates' => ['precificacao', 'templates'],
    'precificacao/template' => ['precificacao', 'template'],
    'precificacao/template/salvar' => ['precificacao', 'salvarTemplate'],
    'precificacao/template/excluir' => ['precificacao', 'excluirTemplate'],

    'orcamentos' => ['orcamentos', 'index'],
    'orcamentos/novo' => ['orcamentos', 'novo'],
    'orcamentos/criar' => ['orcamentos', 'criar'],
    'orcamentos/ver' => ['orcamentos', 'ver'],
    'orcamentos/item' => ['orcamentos', 'salvarItem'],
    'orcamentos/item/excluir' => ['orcamentos', 'excluirItem'],
    'orcamentos/dados' => ['orcamentos', 'salvarDados'],
    'orcamentos/status' => ['orcamentos', 'mudarStatus'],
    'orcamentos/imprimir' => ['orcamentos', 'imprimir'],

    'projetos' => ['projetos', 'index'],
    'projetos/novo' => ['projetos', 'novo'],
    'projetos/criar' => ['projetos', 'criar'],
    'projetos/ver' => ['projetos', 'ver'],
    'projetos/status' => ['projetos', 'mudarStatus'],
    'projetos/excluir' => ['projetos', 'excluir'],

    'tarefas' => ['tarefas', 'index'],
    'tarefas/salvar' => ['tarefas', 'salvar'],
    'tarefas/status' => ['tarefas', 'mudarStatus'],
    'tarefas/excluir' => ['tarefas', 'excluir'],

    'financeiro' => ['financeiro', 'index'],
    'financeiro/lancamentos' => ['financeiro', 'lancamentos'],
    'financeiro/form' => ['financeiro', 'form'],
    'financeiro/salvar' => ['financeiro', 'salvar'],
    'financeiro/baixar' => ['financeiro', 'baixar'],
    'financeiro/estornar' => ['financeiro', 'estornar'],
    'financeiro/duplicar' => ['financeiro', 'duplicar'],
    'financeiro/excluir' => ['financeiro', 'excluir'],
    'financeiro/projetos-do-cliente' => ['financeiro', 'projetosDoCliente'],

    'equipe' => ['equipe', 'index'],
    'equipe/form' => ['equipe', 'form'],
    'equipe/salvar' => ['equipe', 'salvar'],
    'equipe/permissoes' => ['equipe', 'permissoes'],
    'equipe/permissoes/salvar' => ['equipe', 'salvarPermissoes'],

    'perfil' => ['equipe', 'perfil'],
    'escritorio' => ['escritorio', 'dados'],

    'clientes/link-dados' => ['clientes', 'linkDados'],

    'documentos' => ['documentos', 'index'],
    'documentos/novo' => ['documentos', 'novo'],
    'documentos/criar' => ['documentos', 'criar'],
    'documentos/editar' => ['documentos', 'editar'],
    'documentos/ver' => ['documentos', 'ver'],
    'documentos/enviar' => ['documentos', 'enviar'],
    'documentos/assinado' => ['documentos', 'assinadoManual'],
    'documentos/imprimir' => ['documentos', 'imprimir'],
    'documentos/excluir' => ['documentos', 'excluir'],
    'documentos/modelos' => ['documentos', 'modelos'],
    'documentos/modelo' => ['documentos', 'modelo'],

    'briefings' => ['briefings', 'index'],
    'briefings/novo' => ['briefings', 'novo'],
    'briefings/criar' => ['briefings', 'criar'],
    'briefings/ver' => ['briefings', 'ver'],
    'briefings/imprimir' => ['briefings', 'imprimir'],
    'briefings/reabrir' => ['briefings', 'reabrir'],
    'briefings/excluir' => ['briefings', 'excluir'],
    'briefings/modelos' => ['briefings', 'modelos'],
    'briefings/modelo' => ['briefings', 'modelo'],
    'briefings/modelo/salvar' => ['briefings', 'salvarModelo'],
    'briefings/modelo/duplicar' => ['briefings', 'duplicarModelo'],
    'briefings/modelo/excluir' => ['briefings', 'excluirModelo'],
    'briefings/pergunta/salvar' => ['briefings', 'salvarPergunta'],
    'briefings/pergunta/mover' => ['briefings', 'moverPergunta'],
    'briefings/pergunta/excluir' => ['briefings', 'excluirPergunta'],

    'crm' => ['crm', 'index'],
    'crm/relatorio' => ['crm', 'relatorio'],
    'crm/lead' => ['crm', 'lead'],
    'crm/form' => ['crm', 'form'],
    'crm/salvar' => ['crm', 'salvar'],
    'crm/mover' => ['crm', 'mover'],
    'crm/interacao' => ['crm', 'interacao'],
    'crm/interacao/excluir' => ['crm', 'excluirInteracao'],

    'agenda' => ['agenda', 'index'],
    'agenda/evento' => ['agenda', 'evento'],
    'agenda/form' => ['agenda', 'form'],
    'agenda/salvar' => ['agenda', 'salvar'],
    'agenda/excluir' => ['agenda', 'excluir'],
    'agenda/sincronizar' => ['agenda', 'sincronizar'],
    'agenda/token' => ['agenda', 'token'],

    'projetos/cronograma' => ['projetos', 'cronograma'],

    // Feed de calendário: o acesso é pelo token pessoal, sem login.
    'agenda.ics' => ['agenda', 'ics'],

    // Páginas públicas, abertas pelo cliente com o link (token) enviado pelo escritório.
    'assinar' => ['publico', 'assinar'],
    'cadastro' => ['publico', 'cadastro'],
    'briefing' => ['publico', 'briefing'],
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = trim(preg_replace('#/index\.php#', '', $path), '/');

if (!isset($routes[$path])) {
    not_found();
}

[$file, $fn] = $routes[$path];
require APP_ROOT . "/app/controllers/$file.php";

if (!in_array($file, ['auth', 'publico'], true) && $path !== 'agenda.ics') {
    require_login();
}

$fn();
