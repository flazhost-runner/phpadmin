<?php

declare(strict_types=1);

// Load .env for CLI usage (phinx is invoked outside the web request lifecycle)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

$driver   = $_ENV['DB_DRIVER']   ?? 'mysql';
$host     = $_ENV['DB_HOST']     ?? '127.0.0.1';
$port     = (int)($_ENV['DB_PORT'] ?? 3306);
$name     = $_ENV['DB_DATABASE'] ?? 'phpadmin';
$user     = $_ENV['DB_USERNAME'] ?? 'root';
$pass     = $_ENV['DB_PASSWORD'] ?? '';
$charset  = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

// Map our driver names to Phinx adapter names
$adapterMap = [
    'mysql'  => 'mysql',
    'pgsql'  => 'pgsql',
    'sqlite' => 'sqlite',
];
$adapter = $adapterMap[$driver] ?? 'mysql';

$connection = match ($adapter) {
    'pgsql' => [
        'adapter' => 'pgsql',
        'host'    => $host,
        'port'    => $port,
        'name'    => $name,
        'user'    => $user,
        'pass'    => $pass,
        'charset' => $charset ?: 'utf8',
    ],
    'sqlite' => [
        'adapter' => 'sqlite',
        // Phinx SQLite appends .sqlite3 automatically — strip it if already present
        'name'    => preg_replace('/\.sqlite3?$/', '', $name),
    ],
    default => [ // mysql / mariadb
        'adapter' => 'mysql',
        'host'    => $host,
        'port'    => $port,
        'name'    => $name,
        'user'    => $user,
        'pass'    => $pass,
        'charset' => $charset ?: 'utf8mb4',
    ],
};

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds'      => __DIR__ . '/db/seeds',
    ],

    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'development',

        'development' => $connection,

        'production' => $connection,

        'testing' => [
            'adapter' => 'sqlite',
            'name'    => ':memory:',
        ],
    ],

    'version_order' => 'creation',
];
