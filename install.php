<?php
/**
 * Instalação pelo terminal:
 *   php install.php --escritorio="Meu Escritório" --nome="Seu Nome" --email=voce@exemplo.com --senha=suaSenha
 * Use --exemplo para já criar custos e um template de projeto de exemplo.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Rode este arquivo pelo terminal.');
}

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/migrate.php';

$opts = getopt('', ['escritorio:', 'nome:', 'email:', 'senha:', 'exemplo']);
foreach (['escritorio', 'nome', 'email', 'senha'] as $k) {
    if (empty($opts[$k])) {
        fwrite(STDERR, "Falta --$k\n\nUso: php install.php --escritorio=\"Meu Escritório\" --nome=\"Seu Nome\" --email=voce@exemplo.com --senha=suaSenha [--exemplo]\n");
        exit(1);
    }
}
if (strlen($opts['senha']) < 8) {
    fwrite(STDERR, "A senha precisa ter pelo menos 8 caracteres.\n");
    exit(1);
}

$pdo = db();
$isMysql = config('db_driver') === 'mysql';
$pk = $isMysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

$jaExiste = $isMysql
    ? $pdo->query("SHOW TABLES LIKE 'escritorio'")->fetchColumn()
    : $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='escritorio'")->fetchColumn();
if ($jaExiste) {
    fwrite(STDERR, "O banco já está instalado. Para recomeçar, apague o banco e rode de novo.\n");
    exit(1);
}

$pdo->beginTransaction();
foreach (require __DIR__ . '/database/schema.php' as $sql) {
    $sql = str_replace('{PK}', $pk, $sql);
    if ($isMysql && str_starts_with($sql, 'CREATE TABLE')) {
        $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }
    $pdo->exec($sql);
}
// MySQL faz commit implícito em CREATE TABLE; só confirma se ainda houver transação aberta.
if ($pdo->inTransaction()) {
    $pdo->commit();
}
run_migrations();

$eid = insert('escritorio', ['nome' => $opts['escritorio'], 'criado_em' => now()]);
insert('usuario', [
    'escritorio_id' => $eid,
    'nome' => $opts['nome'],
    'email' => mb_strtolower($opts['email']),
    'senha_hash' => password_hash($opts['senha'], PASSWORD_DEFAULT),
    'perfil' => 'senior',
    'cargo' => 'Sócio(a)',
    'horas_dia' => 8,
    'dias_mes' => 22,
    'salario' => 0,
    'ativo' => 1,
    'criado_em' => now(),
]);

// Template padrão de projeto de arquitetura.
$etapas = [
    ['Estudo preliminar', 10, [['Reunião de briefing', 2], ['Levantamento do local', 4], ['Programa de necessidades', 3], ['Estudo de layout', 8]]],
    ['Anteprojeto', 15, [['Plantas e cortes', 12], ['Modelagem 3D', 10], ['Apresentação ao cliente', 3], ['Ajustes do anteprojeto', 5]]],
    ['Projeto legal', 10, [['Pranchas para prefeitura', 10], ['Memorial descritivo', 4], ['Protocolo e acompanhamento', 3]]],
    ['Projeto executivo', 20, [['Detalhamento de plantas', 16], ['Pontos elétricos e hidráulicos', 8], ['Forro e iluminação', 6], ['Paginação de pisos', 5], ['Marcenaria', 8]]],
    ['Entrega e acompanhamento', 10, [['Caderno de especificações', 4], ['Visitas técnicas', 6], ['Entrega final', 2]]],
];
$tid = insert('template', ['escritorio_id' => $eid, 'nome' => 'Projeto residencial completo']);
foreach ($etapas as $i => [$nome, $dias, $tarefas]) {
    $etid = insert('template_etapa', ['template_id' => $tid, 'ordem' => $i + 1, 'nome' => $nome, 'duracao_dias' => $dias]);
    foreach ($tarefas as $j => [$tnome, $horas]) {
        insert('template_tarefa', ['etapa_id' => $etid, 'ordem' => $j + 1, 'nome' => $tnome, 'horas' => $horas]);
    }
}

seed_documentos($eid);
seed_briefings($eid);

if (isset($opts['exemplo'])) {
    foreach ([['Aluguel', 2500], ['Contador', 450], ['Softwares (CAD/BIM)', 600], ['Internet e telefone', 200], ['Pró-labore', 5000]] as [$n, $v]) {
        insert('custo', ['escritorio_id' => $eid, 'tipo' => 'fixo', 'nome' => $n, 'valor_mensal' => $v]);
    }
    insert('custo', ['escritorio_id' => $eid, 'tipo' => 'variavel', 'nome' => 'Impressões e plotagens', 'valor_mensal' => 150]);
}

echo "Instalado com sucesso.\nEntre com: {$opts['email']}\n";
