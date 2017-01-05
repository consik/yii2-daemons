<?php
/**
 * @link https://github.com/consik/yii2-daemons
 * @category yii2-extension
 * @package consik\yii2daemons
 *
 * @author Sergey Poltaranin <consigliere.kz@gmail.com>
 * @copyright Copyright (c) 2017
 */
namespace consik\yii2daemons\daemons;

use yii\base\Component;

/**
 * Class AbstractLoopDaemon implements simple looped daemon with timeout after each iteration
 * @package consik\yii2websocket\daemons
 */
abstract class AbstractLoopDaemon extends Component implements DaemonInterface
{
    const EVENT_DAEMON_START = 1;
    const EVENT_DAEMON_STOP = 2;
    const EVENT_ITERATION_START = 3;
    const EVENT_ITERATION_COMPLETE = 4;

    /** @var bool */
    protected $stop = false;
    /** @var int Timeout in seconds after each iteration */
    protected $iterationTimeout = 10;

    /**
     * @inheritdoc
     * @event yii\base\Event EVENT_DAEMON_START
     */
    public function startDaemon()
    {
        $this->trigger(self::EVENT_DAEMON_START);
        $this->loop();
    }

    /**
     * @inheritdoc
     * @event yii\base\Event EVENT_DAEMON_STOP
     */
    public function stopDaemon()
    {
        $this->trigger(self::EVENT_DAEMON_STOP);
        $this->stop = true;
    }

    /**
     * Doing iteration while !$this->stop
     * @event yii\base\Event EVENT_ITERATION_START
     * @event yii\base\Event EVENT_ITERATION_COMPLETE
     * @return void
     */
    protected function loop()
    {
        while(!$this->stop) {
            $this->trigger(self::EVENT_ITERATION_START);
            $this->iterate();
            $this->trigger(self::EVENT_ITERATION_COMPLETE);
            if ($this->iterationTimeout) {
                sleep($this->iterationTimeout);
            }
        }
    }

    /**
     * Your daemon job
     * @return mixed
     */
    abstract protected function iterate();
}