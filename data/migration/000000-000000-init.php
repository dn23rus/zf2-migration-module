<?php

use DbuMigration\MigrateManagerInterface;
use DbuMigration\MigrateManager;
use Zend\Db\Sql\Ddl\Column;
use Zend\Db\Sql\Ddl\Constraint;
use Zend\Db\Sql\Ddl\CreateTable;

/**
 * @var $this DbuMigration\Migration
 */

$this->on($this::ACTION_UP, function (MigrateManagerInterface $manager) {

    $tableName = ($manager instanceof MigrateManager) ?
        $manager->getStoreTableName() :
        $manager->getServiceLocator()->get('Config')['migrate_manager']['db_table_name'];

    if ($manager->isTableExists($tableName)) {
        throw new Exception(sprintf('Migration history store table "%s" already exists.', $tableName));
    }

    $table = new CreateTable($tableName);
    $table->addColumn(new Column\Varchar('version', 255));
    $table->addColumn(new Column\Integer('created_at'));
    $table->addConstraint(new Constraint\PrimaryKey('version'));

    $manager->createTable($table);

    return true;
});

$this->on($this::ACTION_DOWN, function (MigrateManagerInterface $manager) {

    $tableName = ($manager instanceof MigrateManager) ?
        $manager->getStoreTableName() :
        $manager->getServiceLocator()->get('Config')['migrate_manager']['db_table_name'];

    $manager->dropTable($tableName);

    return true;
});
