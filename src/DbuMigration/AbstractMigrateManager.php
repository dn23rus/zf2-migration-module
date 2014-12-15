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

use RuntimeException;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterAwareTrait;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Ddl\AlterTable;
use Zend\Db\Sql\Ddl\Constraint;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Ddl\DropTable;

/**
 * Class AbstractMigrateManager
 *
 * @package DbuMigration
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 *
 * @method $this setDbAdapter(Adapter $adapter)
 */
abstract class AbstractMigrateManager implements MigrateManagerInterface
{
    use AdapterAwareTrait;

    /**
     * @var AdapterInterface
     */
    protected $console;

    /**
     * @var bool
     */
    protected $verbose = false;

    /**
     * @var Sql
     */
    protected $sql;

    /**
     * @param bool $flag
     * @return $this
     */
    public function setVerbose($flag)
    {
        $this->verbose = $flag;
        return $this;
    }

    /**
     * @return AdapterInterface
     */
    public function getConsole()
    {
        if (!$this->console) {
            throw new RuntimeException(sprintf('Require set console adapter before call %s', __METHOD__));
        }
        return $this->console;
    }

    /**
     * @param AdapterInterface $console
     * @return $this;
     */
    public function setConsole(AdapterInterface $console)
    {
        $this->console = $console;
        return $this;
    }

    /**
     * @param CreateTable $table
     * @return $this
     */
    public function createTable(CreateTable $table)
    {
        $time = microtime(true);
        $this->adapter->query($this->getSql()->getSqlStringForSqlObject($table), Adapter::QUERY_MODE_EXECUTE);
        if ($this->verbose) {
            $this->getConsole()->writeLine(sprintf(
                '    Create %stable: %s, time: %.3f',
                $table->isTemporary() ? 'temporary ' : '',
                $table->getRawState(CreateTable::TABLE),
                microtime(true) - $time
            ));
        }
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function dropTable($name)
    {
        $time = microtime(true);
        $table = new DropTable($name);
        $this->adapter->query($this->getSql()->getSqlStringForSqlObject($table), Adapter::QUERY_MODE_EXECUTE);
        if ($this->verbose) {
            $this->getConsole()->writeLine(sprintf(
                '    Drop table: %s, time %.3f',
                $name,
                microtime(true) - $time
            ));
        }
        return $this;
    }

    /**
     * @param AlterTable $table
     * @return $this
     */
    public function alterTable(AlterTable $table)
    {
        $time = microtime(true);
        $this->adapter->query($this->getSql()->getSqlStringForSqlObject($table), Adapter::QUERY_MODE_EXECUTE);
        if ($this->verbose) {
            $this->getConsole()->writeLine(sprintf(
                '    Alter table: %s, time: %.3f',
                $table->getRawState(AlterTable::TABLE),
                microtime(true) - $time
            ));
        }
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isTableExists($name)
    {
        $metadata = new Metadata($this->adapter);
        return in_array($name, $metadata->getTableNames());
    }

    /**
     * @return Sql
     */
    protected function getSql()
    {
        if (!$this->sql) {
            $this->sql = new Sql($this->adapter);
        }
        return $this->sql;
    }

    /**
     * @param string $table
     * @param string|array $column
     * @param string $referenceTable
     * @param string|array $referenceColumn
     * @return string
     */
    public function getForeignKeyName($table, $column, $referenceTable, $referenceColumn)
    {
        return strtoupper(sprintf('FK_%s_%s_%s', $table, $referenceTable, md5(
            $table . implode('', (array) $column) . $referenceTable . implode('', (array) $referenceColumn)
        )));
    }
}
