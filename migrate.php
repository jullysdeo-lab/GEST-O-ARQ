<?php
/**
 * Atualiza o banco depois de baixar uma versão nova do sistema:
 *   php migrate.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Rode este arquivo pelo terminal.');
}

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/migrate.php';

$feitas = run_migrations();
echo $feitas ? 'Migrações aplicadas: ' . implode(', ', $feitas) . "\n" : "Banco já está atualizado.\n";
