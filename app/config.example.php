<?php
// Copie este arquivo para app/config.php e ajuste os valores.
return [
    'app_name' => 'GestãoArq',

    // 'sqlite' para testar no PC, 'mysql' para a hospedagem.
    'db_driver' => 'sqlite',

    // SQLite: caminho do arquivo do banco.
    'sqlite_path' => __DIR__ . '/../storage/database.sqlite',

    // MySQL
    'mysql_host' => 'localhost',
    'mysql_port' => 3306,
    'mysql_db'   => 'gestaoarq',
    'mysql_user' => 'root',
    'mysql_pass' => '',

    // Tempo sem uso até a sessão expirar, em segundos (2 horas).
    'session_timeout' => 7200,

    'timezone' => 'America/Bahia',
];
