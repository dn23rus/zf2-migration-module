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

use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Sql\Ddl\AlterTable;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Interface MigrateManagerInterface
 *
 * @package DbuMigration
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
interface MigrateManagerInterface extends AdapterAwareInterface, ServiceLocatorAwareInterface
{
    /**
     * @param string $name
     * @return bool
     */
    public function isTableExists($name);

    /**
     * @param CreateTable $table
     * @return $this
     */
    public function createTable(CreateTable $table);

    /**
     * @param string $name
     * @return $this
     */
    public function dropTable($name);

    /**
     * @param AlterTable $table
     * @return $this
     */
    public function alterTable(AlterTable $table);
}
