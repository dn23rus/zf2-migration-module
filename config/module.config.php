<?php
return [
    'migrate_manager' => [
        'initial_migration_file' => __DIR__ . '/../data/migration/000000-000000-init.php',
        'db_table_name' => 'migration',
        'db_adapter_service' => 'Zend\Db\Adapter\Adapter',
    ],
    'service_manager' => [
        'factories' => [
            'DbuMigration\MigrateManager' => 'DbuMigration\MigrateManagerFactory',
        ],
    ],
    'controllers' => [
        'invokables' => [
            'DbuMigration\Controller\Console\Migrate' => 'DbuMigration\Controller\Console\MigrateController',
        ],
    ],
    'console' => [
        'router' => [
            'routes' => [
                'migrate-up|down' => [
                    'options' => [
                        'route' => 'migrate <actionType> [--verbose|-v]:v [--count=]',
                        'defaults' => [
                            'controller' => 'DbuMigration\Controller\Console\Migrate',
                            'action' => 'index'
                        ],
                    ],
                ],
                'migrate-create' => [
                    'options' => [
                        'route' => 'migrate create <name> <path> [--verbose|-v]:v',
                        'defaults' => [
                            'controller' => 'DbuMigration\Controller\Console\Migrate',
                            'action' => 'create'
                        ],
                    ],
                ],
            ],
        ],
    ],
];
