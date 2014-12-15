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

use DirectoryIterator;
use InvalidArgumentException;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class MigrateManager
 *
 * @package DbuMigration
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
class MigrateManager extends AbstractMigrateManager
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    protected $initialMigrationFile;

    /**
     * @var bool
     */
    protected $isInited = false;

    /**
     * @var string
     */
    protected $fileSuffix = '.php';

    /**
     * @var ModuleManager\Feature\MigrateProviderInterface[]
     */
    protected $supportedModules = [];

    /**
     * @return string|null
     */
    public function getInitialMigrationFile()
    {
        return $this->initialMigrationFile;
    }

    /**
     * @param string $initialMigrationFile
     * @return MigrateManager
     */
    public function setInitialMigrationFile($initialMigrationFile)
    {
        $this->initialMigrationFile = $initialMigrationFile;
        return $this;
    }

    /**
     * @param array $modules
     * @return $this
     */
    public function setSupportedModules(array $modules)
    {
        $this->supportedModules = $modules;
        return $this;
    }

    /**
     * Run all migrations according action type.
     *
     * @param string $actionType
     * @param int $depth
     * @return $this
     */
    public function run($actionType, $depth = 1)
    {
        $this->init();
        switch ($actionType) {
            case Migration::ACTION_UP:
                $this->runUp((int) $depth);
                break;
            case Migration::ACTION_DOWN:
                $this->runDown((int) $depth);
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    'Unsupported action type given: "%s"; please, use Migration::ACTION_* constants only',
                    $actionType
                ));
                break;
        }
        return $this;
    }

    /**
     * Initialize migration history table
     *
     * @return $this
     */
    protected function init()
    {
        if (!$this->isInited) {
            $metadata = new Metadata($this->adapter);
            if (!in_array($this->getStoreTableName(), $metadata->getTableNames())) {
                $this->runMigration($this->getInitialMigrationFile(), Migration::ACTION_UP);
            }
            $this->isInited = true;
        }
        return $this;
    }

    /**
     * Apply all necessary migrations
     *
     * @param int|null $count
     * @return $this
     */
    protected function runUp($count = null)
    {
        $files      = $this->getMigrationFileList();
        $migrations = array_flip($this->getMigrationHistoryList());
        $toApply    = array_diff_key($files, $migrations);

        if ($count > 0) {
            $toApply = array_slice($toApply, 0, $count);
        }

        if (!$toApply) {
            if ($this->verbose) {
                $this->getConsole()->writeLine('There are no migrations to apply.');
            }
            return $this;
        }

        foreach ($toApply as $file) {
            $this->runMigration($file, Migration::ACTION_UP);
        }

        return $this;
    }

    /**
     * Cancel migrations
     *
     * @param int $count
     * @return $this
     */
    public function runDown($count)
    {
        $files      = $this->getMigrationFileList(false);
        $migrations = array_flip($this->getMigrationHistoryList($count > 0 ? $count : null));
        $toCancel   = array_intersect_key($files, $migrations);

        if (!$toCancel) {
            if ($this->verbose) {
                $this->getConsole()->writeLine('There are no migrations to cancel.');
            }
            return $this;
        }

        foreach ($toCancel as $file) {
            $this->runMigration($file, Migration::ACTION_DOWN);
        }

        return $this;
    }

    /**
     * Get migration file list
     *
     * @param bool $isDirectSort
     * @return array
     */
    protected function getMigrationFileList($isDirectSort = true)
    {
        $files = [];
        foreach ($this->supportedModules as $module) {
            $directoryIterator = new DirectoryIterator($module->getMigrationDataPath());
            foreach ($directoryIterator as $file) {
                if (!$file->isDot() && $file->isFile() && $file->isReadable()) {
                    $files[$file->getBasename($this->fileSuffix)] = $file->getRealPath();
                }
            }
        }
        $isDirectSort ? ksort($files) : krsort($files);
        return $files;
    }

    /**
     * Get migration history list from database
     *
     * @param null|int $limit
     * @return array
     */
    protected function getMigrationHistoryList($limit = null)
    {
        $adapter = $this->adapter;
        $select = (new Select())
            ->from($this->getStoreTableName())
            ->columns(['version'])
            ->order(['version' => Select::ORDER_DESCENDING]);

        if ($limit) {
            $select->limit($limit);
        }

        $data = $adapter->query(
            $this->getSql()->getSqlStringForSqlObject($select),
            $adapter::QUERY_MODE_EXECUTE
        )->toArray();

        return array_map(function ($row) {
            return $row['version'];
        }, $data);
    }

    /**
     * Create and execute Migration
     *
     * @param string $file
     * @param string $actionType
     * @return $this
     */
    protected function runMigration($file, $actionType)
    {
        $migration = new Migration($file, $this->getMigrationMame($file));

        switch ($actionType) {
            case Migration::ACTION_UP:
                if ($this->verbose) {
                    $this->getConsole()->writeLine(sprintf('==> Applying migration: %s', $migration->getName()));
                }
                $time = microtime(true);
                $migration->run($actionType, $this);
                $this->addMigrationHistory($migration->getName(), time());
                break;
            case Migration::ACTION_DOWN:
                if ($this->verbose) {
                    $this->getConsole()->writeLine(sprintf('==> Canceling migration: %s', $migration->getName()));
                }
                $time = microtime(true);
                $migration->run($actionType, $this);
                if (strcasecmp($this->getInitialMigrationName(), $migration->getName()) != 0) {
                    $this->removeMigrationHistory($migration->getName());
                }
                break;
            default:
                break;
        }

        if ($this->verbose && isset($time)) {
            $this->getConsole()->writeLine(sprintf('  > Total time: %.3f', microtime(true) - $time));
        }

        return $this;
    }

    /**
     * @param string $version
     * @param string $time
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    protected function addMigrationHistory($version, $time)
    {
        return $this->getSql()->prepareStatementForSqlObject($this->getSql()
            ->insert($this->getStoreTableName())
            ->values(['version' => $version, 'created_at' => $time])
        )->execute();
    }

    /**
     * @param string $version
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    protected function removeMigrationHistory($version)
    {
        return $this->getSql()->prepareStatementForSqlObject($this->getSql()
            ->delete($this->getStoreTableName())
            ->where(['version' => $version])
        )->execute();
    }

    /**
     * @return string
     */
    public function getInitialMigrationName()
    {
        return $this->getMigrationMame($this->getInitialMigrationFile());
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getMigrationMame($file)
    {
        return basename($file, $this->fileSuffix);
    }

    /**
     * @return string
     */
    public function getStoreTableName()
    {
        return (string) $this->getServiceLocator()->get('Config')['migrate_manager']['db_table_name'];
    }

    /**
     * @param string $baseName
     * @return string
     */
    public function generateFileName($baseName)
    {
        return gmdate('ymd-His') . '-' . $baseName . $this->fileSuffix;
    }

    /**
     * @return \Zend\ModuleManager\ModuleManager
     */
    protected function getModuleManager()
    {
        return $this->getServiceLocator()->get('Zend\ModuleManager\ModuleManager');
    }
}
