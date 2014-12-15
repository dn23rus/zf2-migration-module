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

namespace DbuMigration\ModuleManager\Feature;

/**
 * Interface MigrateProviderInterface
 *
 * @package DbuMigration\ModuleManager\Feature
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
interface MigrateProviderInterface
{
    /**
     * @return string
     */
    public function getMigrationDataPath();
}
