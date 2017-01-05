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

use consik\yii2daemons\daemons\DaemonInterface;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\console\Controller;

/**
 * Class ServiceController implements methods for easy creating systemd units from yii2 php scripts
 * @package consik\yii2daemons\service
 */
class ServiceController extends Controller
{
    /**
     * @var array
     * @see ServiceConfigInterface::getServiceConfig()
     */
    public $commonServiceConfig = [];

    /**
     * @var string Path to php
     */
    public $phpPath = '/usr/bin/php';
    /**
     * @var string Path where controller will save PID when starts daemon
     */
    public $pidsPath = '@runtime/daemons/pids';

    /**
     * @var array Configuration array of your daemons.
     * Daemon must implement DaemonInterface.
     * Configuration definition like for any yii2 components
     * @see http://www.yiiframework.com/doc-2.0/yii-baseyii.html#createObject()-detail
     */
    public $daemons = [];

    /**
     * Starts the daemon and saving PID at file
     * @param $daemonName
     * @return void
     */
    public function actionStart($daemonName)
    {
        $daemon = $this->getDaemon($daemonName);
        $this->writeDaemonPID($daemonName, getmypid());
        $daemon->startDaemon();
    }

    /**
     * Terminates the daemon if PID founded
     * @param $daemonName
     * @throws Exception
     * @return void
     */
    public function actionStop($daemonName)
    {
        posix_kill($this->getDaemonPID($daemonName), SIGTERM);
    }

    /**
     * Prints daemon status, and it's PID if daemon is active
     * @param $daemonName
     * @throws Exception
     * @return void
     */
    public function actionStatus($daemonName)
    {
        if ($this->isDaemonActive($daemonName)) {
            $this->stdout('The daemon is active. PID: ' . $this->getDaemonPID($daemonName));
        } else {
            $this->stdout('The daemon is stopped');
        }
    }

    /**
     * Prints systemd unit file
     * @param $daemonName
     * @return void
     */
    public function actionSystemdFile($daemonName)
    {
        $output = '';
        $config = $this->getFinalDaemonConfig($daemonName);
        foreach ($config as $section => $params) {
            $output .= '[' . $section . ']';
            foreach ($params as $param => $value) {
                $output .= PHP_EOL . $param . '=' . $value;
            }
            $output .= PHP_EOL;
        }
        $this->stdout($output);
    }

    /**
     * Returns daemon process status
     * @param $daemonName
     * @return bool
     * @throws Exception
     */
    protected function isDaemonActive($daemonName)
    {
        return posix_kill($this->getDaemonPID($daemonName), 0);
    }

    /**
     * Returns final config for the daemon systemd unit file
     * @param $daemonName
     * @return array
     */
    protected function getFinalDaemonConfig($daemonName)
    {
        $daemon = $this->getDaemon($daemonName);
        $daemonConfig = $daemon instanceof ServiceConfigInterface
            ? $daemon->getServiceConfig()
            : [];
        return array_replace_recursive(
            $this->getBasicServiceConfig($daemonName),
            $this->commonServiceConfig,
            $daemonConfig
        );
    }

    /**
     * Returns basic configuration for all daemons
     * @param $daemonName
     * @return array
     */
    protected function getBasicServiceConfig($daemonName)
    {
        return [
            'Unit' => [
                'Description' => $daemonName,
                'After' => 'mysql.service'
            ],
            'Service' => [
                'ExecStart' => $this->phpPath . ' ' . \Yii::getAlias('@app/yii') . ' ' . $this->id . '/start ' . $daemonName,
                'Type' => 'simple',
                'PIDFile' => \Yii::getAlias($this->pidsPath) . '/' . $daemonName . '.pid'
            ],
            'Install' => ['WantedBy' => 'multi-user.target']
        ];
    }

    /**
     * Returns daemon instance
     * @param string $daemonName
     * @return DaemonInterface
     * @throws \yii\base\InvalidConfigException
     */
    protected function getDaemon($daemonName)
    {
        if (!isset($this->daemons[$daemonName])) {
            throw new InvalidCallException('Invalid daemon name `' . $daemonName . '`');
        } else {
            $daemon = \Yii::createObject($this->daemons[$daemonName]);
            if (!$daemon instanceof DaemonInterface) {
                throw new InvalidCallException('Daemon `' . $daemonName . '` doesn\'t implement DaemonInterface');
            }
            return $daemon;
        }
    }

    /**
     * Returns the daemon PID
     * @param $daemonName
     * @return string
     * @throws Exception
     */
    protected function getDaemonPID($daemonName)
    {
        $daemonConfig = $this->getFinalDaemonConfig($daemonName);
        if (!isset($daemonConfig['Service']['PIDFile'])) {
            throw new Exception('PIDFile param doesn\'t declared for this daemon');
        }
        if (!file_exists($daemonConfig['Service']['PIDFile'])) {
            throw new Exception('PID file not found for this daemon');
        }
        $pid = file_get_contents($daemonConfig['Service']['PIDFile']);

        return $pid;
    }

    /**
     * Writes current process PID to the daemon PID file
     * @param $daemonName
     * @param $pid
     */
    protected function writeDaemonPID($daemonName, $pid)
    {
        $daemonConfig = $this->getFinalDaemonConfig($daemonName);
        if (isset($daemonConfig['Service']['PIDFile'])) {
            $pidDir = \Yii::getAlias($this->pidsPath);
            if (!is_dir($pidDir)) {
                mkdir($pidDir, 0775, true);
            };
            file_put_contents($daemonConfig['Service']['PIDFile'], $pid);
        }
    }
}