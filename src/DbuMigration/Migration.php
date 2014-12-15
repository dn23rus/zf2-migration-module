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

/**
 * Class Migration
 *
 * @package DbuMigration
 * @author  Dmitriy Buryak <dmitriy.buryak.w@gmail.com>
 */
class Migration
{
    const ACTION_UP     = 'up';
    const ACTION_DOWN   = 'down';

    /**
     * @var array
     */
    protected $actions = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $file
     */
    public function __construct($file, $name)
    {
        if (!is_file($file)) {
            throw new RuntimeException(sprintf(
                '%s::__construct() require existing file, %s given',
                __CLASS__,
                is_object($file) ? get_class($file) : gettype($file)
            ));
        }
        if (!is_readable($file)) {
            throw new RuntimeException('%s::__construct() require readable file');
        }
        $this->name = $name;
        include $file;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Use ACTION_DOWN or ACTION_DOWN constant
     *
     * @param string $actionType
     * @param $callback
     */
    public function on($actionType, $callback)
    {
        $this->actions[$actionType] = $callback;
    }

    /**
     * @param string $actionType
     * @param MigrateManagerInterface $manager
     * @return bool
     */
    public function run($actionType, MigrateManagerInterface $manager)
    {
        if (isset($this->actions[$actionType]) && is_callable($this->actions[$actionType])) {
            return call_user_func_array($this->actions[$actionType], [$manager]);
        }
        return false;
    }
}
