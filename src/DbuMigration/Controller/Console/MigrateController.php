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

namespace DbuMigration\Controller\Console;

use DbuMigration\Migration;
use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;

/**
 * Class MigrateController
 *
 * @package DbuMigration\Controller\Console
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
class MigrateController extends  AbstractActionController
{
    protected $template = <<<'TEMPLATE'
<?php

use DbuMigration\MigrateManagerInterface;

/**
 * @var $this DbuMigration\Migration
 */

$this->on($this::ACTION_UP, function (MigrateManagerInterface $manager) {
    // do any stuff
});

$this->on($this::ACTION_DOWN, function (MigrateManagerInterface $manager) {
    // do any stuff
});

TEMPLATE;


    public function indexAction()
    {
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest){
            throw new RuntimeException('You can only use this action from a console.');
        }
        $action = strtolower($request->getParam('actionType'));
        $this->validateAction($action);

        $count = $request->getParam('count', 1);
        if (0 == strcasecmp($count, 'all')) {
            $count = -1;
        }

        $this->getMigrateManager()
            ->setVerbose($request->getParam('v'))
            ->setConsole($this->getServiceLocator()->get('console'))
            ->run($action, $count);

    }

    public function createAction()
    {
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest){
            throw new RuntimeException('You can only use this action from a console.');
        }

        $path = $request->getParam('path');
        $this->validatePath($path);
        $file = rtrim($path, '\\/') . '/' . $this->getMigrateManager()->generateFileName(strtolower($request->getParam('name')));

        file_put_contents($file, $this->template);
        if ($request->getParam('v')) {
            $this->getServiceLocator()->get('console')->writeLine(sprintf('  > Create file: %s', $file));
        }
    }

    /**
     * @param string $path
     * @throws RuntimeException
     */
    protected function validatePath($path)
    {
        if (!is_dir($path)) {
            throw new RuntimeException(sprintf('Given path "%s" is not directory', $path));
        }
        if (!is_writable($path)) {
            throw new RuntimeException(sprintf('Given directory "%s" is not writable', $path));
        }
    }

    /**
     * @param string $action
     * @throws RuntimeException
     */
    protected function validateAction($action)
    {
        if (!in_array($action, [
            Migration::ACTION_UP,
            Migration::ACTION_DOWN,
        ])) {
            throw new RuntimeException(sprintf('Illegal migration action type "%s"', $action));
        };
    }

    /**
     * @return \DbuMigration\MigrateManager
     */
    protected function getMigrateManager()
    {
        return $this->getServiceLocator()->get('DbuMigration\MigrateManager');
    }
}
