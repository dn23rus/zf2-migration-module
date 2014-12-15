<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/MIT
 *
 * @copyright Copyright (C) 2014 Dmitriy Buryak (dmitriy.buryak.w@gmail.com)
 */

namespace DbuMigration;

use DbuMigration\ModuleManager\Feature\MigrateProviderInterface;
use Zend\Console\Adapter\AdapterInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;

/**
 * Class Module
 *
 * @package DbuMigration
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
class Module implements
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    ConsoleUsageProviderInterface,
    MigrateProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/../../src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * {@inheritdoc}
     */
    public function getConsoleUsage(AdapterInterface $console)
    {
        return [
            'migrate <actionType> [--verbose|-v] [--count]' => 'Run migration',
            ['actionType', 'Available values (up|down)'],
            ['  up', 'Run migrations'],
            ['  down', 'Revert migrations'],
            ['--verbose|-v', '(optional) Turn on verbose mode'],
            ['--count', 'Count of migration to apply or cancel. Default value is 1. Can accept value "all"'],

            'migrate create <name> <path> [--verbose|-v]' => 'Create new migration',
            ['name', 'Base file name'],
            ['path', 'Where to place file'],
            ['--verbose|-v', '(optional) Turn on verbose mode'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationDataPath()
    {
        return __DIR__ . '/../../data/migration';
    }
}
