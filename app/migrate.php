<?php
declare(strict_types=1);

/**
 * Aplica as migrações pendentes de database/migrations.php.
 * Cada migração é uma lista de comandos SQL ou funções PHP; {PK} vira a chave primária do banco em uso.
 * Retorna as versões aplicadas.
 */
function run_migrations(): array
{
    $pdo = db();
    $isMysql = config('db_driver') === 'mysql';
    $pk = $isMysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

    $pdo->exec('CREATE TABLE IF NOT EXISTS migracao (versao INT NOT NULL PRIMARY KEY, aplicada_em DATETIME NOT NULL)');
    $feitas = array_map('intval', $pdo->query('SELECT versao FROM migracao')->fetchAll(PDO::FETCH_COLUMN));

    $aplicadas = [];
    $migracoes = require APP_ROOT . '/database/migrations.php';
    ksort($migracoes);
    foreach ($migracoes as $versao => $passos) {
        if (in_array($versao, $feitas, true)) {
            continue;
        }
        foreach ($passos as $passo) {
            if (is_callable($passo)) {
                $passo();
                continue;
            }
            $sql = str_replace('{PK}', $pk, $passo);
            if ($isMysql && str_starts_with($sql, 'CREATE TABLE')) {
                $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            }
            $pdo->exec($sql);
        }
        insert('migracao', ['versao' => $versao, 'aplicada_em' => now()]);
        $aplicadas[] = $versao;
    }
    return $aplicadas;
}
