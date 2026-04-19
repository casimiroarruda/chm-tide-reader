<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    (new Dotenv())->load(__DIR__ . '/.env');
}

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/resources/db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/resources/db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'production',
            'production' => [
                'adapter' => 'pgsql',
                'dsn' => $_ENV['PHINX_DBDSN'] ?? null,
                'schema' => $_ENV['PHINX_DBSCHEMA'] ?? 'public'
            ],
        ],
        'version_order' => 'creation'
    ];
