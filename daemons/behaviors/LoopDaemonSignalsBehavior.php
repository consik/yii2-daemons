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

use consik\yii2daemons\daemons\AbstractLoopDaemon;

/**
 * Class LoopDaemonSignalsBehavior implements using HandleSignalBehavior in AbstractLoopDaemon instances
 * @package consik\yii2daemons\daemons\traits
 */
class LoopDaemonSignalsBehavior extends HandleSignalsBehavior
{
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            AbstractLoopDaemon::EVENT_ITERATION_COMPLETE => 'callSignalDispatch'
        ];
    }
}