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

/**
 * Interface DaemonInterface
 */
interface DaemonInterface
{
    /** @return void */
    public function startDaemon();

    /** @return void */
    public function stopDaemon();
}