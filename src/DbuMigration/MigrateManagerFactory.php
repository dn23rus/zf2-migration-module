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
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class MigrateManagerFactory
 *
 * @package DbuMigration
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
class MigrateManagerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return MigrateManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $cnf = $serviceLocator->get('Config')['migrate_manager'];

        $modules = array_filter($this->getModuleManager($serviceLocator)->getLoadedModules(true), function ($module) {
            return $module instanceof MigrateProviderInterface || method_exists($module, 'getMigrationDataPath');
        });

        $migrateManager = new MigrateManager();
        $migrateManager
            ->setDbAdapter($serviceLocator->get($cnf['db_adapter_service']))
            ->setInitialMigrationFile($cnf['initial_migration_file'])
            ->setSupportedModules($modules)
            ;

        return $migrateManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Zend\ModuleManager\ModuleManager
     */
    protected function getModuleManager(ServiceLocatorInterface $serviceLocator)
    {
        return $serviceLocator->get('ModuleManager');
    }
}
