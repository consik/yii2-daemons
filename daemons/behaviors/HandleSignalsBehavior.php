<?php
/**
 * @link https://github.com/consik/yii2-daemons
 * @category yii2-extension
 * @package consik\yii2daemons
 *
 * @author Sergey Poltaranin <consigliere.kz@gmail.com>
 * @copyright Copyright (c) 2017
 */
namespace consik\yii2daemons\daemons\behaviors;

use yii\base\Behavior;

/**
 * HandleSignalsBehavior provides common realization that handles signals by pcntl_signal()
 */
class HandleSignalsBehavior extends Behavior
{

    /**
     * Associative array, where key is signal number and value is handler
     * @var array
     */
    public $signalHandlers = [];

    /**
     * @see HandleSignalsBehavior::$signalHandlers
     * @return array
     */
    protected function getSignalHandlers()
    {
        return $this->signalHandlers;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach ($this->getSignalHandlers() as $signal => $handler) {
            $this->registerSignalHandler($signal, $handler);
        }
        parent::init();
    }

    /**
     * Registers signal handler
     * @throws \Exception
     * @return void
     */
    public function registerSignalHandler($signal, $handler)
    {
        pcntl_signal($signal, $handler);
    }

    /**
     * Calls pcntl_signal_dispatch
     * Use this method or call pcntl_signal_dispatch manually to dispatch signals handlers
     * @return void
     */
    public function callSignalDispatch()
    {
        pcntl_signal_dispatch();
    }
}