<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Bootstraps the Illuminate/Database Capsule.
 */
class Database
{
    private static bool $initialized = false;

    /**
     * Initialize the database connection using the application config.
     *
     * @throws \RuntimeException
     */
    public static function initialize(AppConfig $config): void
    {
        if (self::$initialized) {
            return;
        }

        $capsule = new Capsule();

        $connection = self::buildConnectionConfig($config);
        $capsule->addConnection($connection);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$initialized = true;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildConnectionConfig(AppConfig $config): array
    {
        return match ($config->dbDriver) {
            'pgsql' => [
                'driver'   => 'pgsql',
                'host'     => $config->dbHost,
                'port'     => $config->dbPort,
                'database' => $config->dbDatabase,
                'username' => $config->dbUsername,
                'password' => $config->dbPassword,
                'charset'  => $config->dbCharset ?: 'utf8',
                'schema'   => 'public',
            ],
            'sqlite' => [
                'driver'   => 'sqlite',
                'database' => $config->dbDatabase === ':memory:'
                    ? ':memory:'
                    : $config->appRoot . '/' . ltrim($config->dbDatabase, '/'),
                'prefix'   => '',
            ],
            default => [ // mysql / mariadb
                'driver'    => 'mysql',
                'host'      => $config->dbHost,
                'port'      => $config->dbPort,
                'database'  => $config->dbDatabase,
                'username'  => $config->dbUsername,
                'password'  => $config->dbPassword,
                'charset'   => $config->dbCharset ?: 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => true,
                'engine'    => null,
            ],
        };
    }

    /**
     * Get a raw PDO instance for the default connection (useful in tests).
     */
    public static function pdo(): \PDO
    {
        return Capsule::connection()->getPdo();
    }
}
