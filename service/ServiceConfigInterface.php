<?php
/**
 * @link https://github.com/consik/yii2-daemons
 * @category yii2-extension
 * @package consik\yii2daemons
 *
 * @author Sergey Poltaranin <consigliere.kz@gmail.com>
 * @copyright Copyright (c) 2017
 */
namespace consik\yii2daemons\service;

/**
 * Interface ServiceConfigInterface
 * @package consik\yii2daemons\service
 */
interface ServiceConfigInterface
{
    /**
     * Returns associative array, where keys is section name and value is array that contains pairs key => value with the section params
     * Example:
     * ```
     * return ['Unit' => ['Description' => 'YourServiceDescription']];
     * ```
     *
     * All available params:
     * @link http://0pointer.de/public/systemd-man/systemd.service.html
     * @return array
     */
    public function getServiceConfig();
}
