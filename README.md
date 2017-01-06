# Yii2 daemons by systemd

[![Latest Stable Version](https://poser.pugx.org/consik/yii2-daemons/v/stable)](https://packagist.org/packages/consik/yii2-daemons)
[![Total Downloads](https://poser.pugx.org/consik/yii2-daemons/downloads)](https://packagist.org/packages/consik/yii2-daemons)
[![License](https://poser.pugx.org/consik/yii2-daemons/license)](https://packagist.org/packages/consik/yii2-daemons)

## Introduction

There is no concrete realization for daemonizing PHP scripts by using pcntl_fork() or something else.
We don't need it, when we have package like systemd that can make service from our PHP script.
So, there is DaemonInterface in package that has only two methods startDaemon() and stopDaemon(), and one AbstractLoopDaemon that implements this interface.

You can use your own script, that working in background, just implement there DaemonInterface.

All functions that makes daemon from your PHP script will do systemd and ServiceController.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require consik/yii2-daemons
```

or add

```json
"consik/yii2-daemons": "^1.0"
```

## Creating daemon.
As I say above all that you need is to implement DaemonInterface.

Simple looped daemon:
```php
<?php
class TestDaemon implements DaemonInterface
{
	private $stop = false;
	private $counter = 0;

	public function startDaemon()
	{
		while (!$this->stop) {
			$counter++;
			sleep(1)
		}
	}

	public function stopDaemon()
	{
		$this->stop = true;
	}
}
```

In this realizaton stopDaemon() never called and we lost result when daemon will be terminated;

Now let's make same daemon by extending AbstractLoopDaemon with extensions from this package as ServiceConfigInterface and LoopDaemonSignalsBehavior.

```php
<?php
namespace app\daemons;

use consik\yii2daemons\daemons\AbstractLoopDaemon;
use consik\yii2daemons\daemons\behaviors\LoopDaemonSignalsBehavior;
use consik\yii2daemons\service\ServiceConfigInterface;

class TestDaemon extends AbstractLoopDaemon implements ServiceConfigInterface
{
    public $serviceConfig = [];
	protected $i = 1;
    protected $iterationTimeout = 10;

    public function getServiceConfig()
    {
        return $this->serviceConfig;
    }

	public function behaviors()
    {
        return[
            [
                'class' => LoopDaemonSignalsBehavior::className(),
                'signalHandlers' => [SIGTERM => [$this, 'stopDaemon']]
            ]
        ];
    }

    public function stopDaemon()
    {
        echo $this->i;
        parent::stopDaemon();
    }

    protected function iterate()
    {
        $this->i++;
    }
}
```
This daemon increment $i each 10 seconds by 1.
Also there used LoopDaemonSignalsBehavior and we handle SIGTERM signal, that dispatch 'stopDaemon' method.
When this daemon stopped it will output current value of $i.

For more information about handling signals see docs about [pcntl_signal()](http://php.net/manual/ru/function.pcntl-signal.php)
For easy realization you can use HandleSignalsBehavior or LoopDaemonSignalsBehavior if you extends AbstractLoopDaemon.
If you will use HandleSignalsBehavior don't forget calling ```$this->callSignalDispatch()``` or [pcntl_signal_dispatch()](http://php.net/manual/ru/function.pcntl-signal-dispatch.php).

ServiceConfigInterface has one method getServiceConfig(). This method will be used by ServiceController for getting current daemon systemd unit params.

## Using ServiceController

### Use ServiceController in your app for easy making systemd units and controlling your daemons.
config\console.php:
```php
<?php
...
'controllerMap' => [
        'service' => [
            'class' => '\consik\yii2daemons\service\ServiceController',
			'daemons' => [
                'testDaemon' => [
                    'class' => 'app\daemons\TestDaemon',
                ]
            ]
		]
	]
...
```

### Generating systemd units.

For generating systemd unit files use this command: ```php yii service/systemd-file DaemonName```


### Registering service.
ServiceController can only generate systemd unit(*.service) file by action systemd-file
There is a bash script in package for fast registering systemd units in system.
Using example, after service controller is configured:

``` sudo bash vendor\consik\yii2-daemons\service\systemd.register ServiceName "$(php yii service/systemd-file DaemonName)" ```

```Note: use ServiceName same as DaemonName if you don't want to be tangled.```

### Controlling your daemon:

Checking your daemon status:
```sudo systemctl status ServiceName```

Starting service:
```sudo systemctl start ServiceName```

Restarting service
```sudo systemctl restart ServiceName```

Stopping service:
```sudo systemctl stop ServiceName```



also you can use ServiceController methods like `status` and `stop`, but better use systemctl functions:

Checking status by ServiceController:

```sudo php yii service/status DaemonName```

Stopping daemon by sending SIGTERM signal for daemon process:

```sudo php yii service/stop DaemonName```

@see ```man systemd``` for more information about unit files configuration and controlling your daemons.

### Configuring systemd units

There are three sources with params that used for generating systemd unit.
Array structure for all sources below is:
```php
...
[
	'SectionName' => [
		'ParamName' => 'ParamValue'
	]
]
...
```
It equals service unit file:
```
[SectionName]
ParamName=ParamValue
```

See available options in [official docs](http://0pointer.de/public/systemd-man/systemd.service.html)

All of configuration sources below sorted by priority:

#### Concrete daemon service configuration. Override common services configuration.

It will be used if the daemon implements ServiceConfigInterface. ServiceController calls ```getServiceConfig()``` method for getting configuration.
Example setting systemd unit params for each daemon

Implement ServiceConfigInterface in your daemon and declare function ```getServiceConfig()```
	
Defining your daemon config params:
	
* Easy way is to declare public variable, that you can change in controller daemons definition and return this variable in getServiceConfig();

	Daemon code:
	```
	public $serviceConfig = [];
	public function getServiceConfig()
	{
		return $this->serviceConfig;
	}
	```
	Yii2 console config file:
	```php
		...
		'controllerMap' => [
		'service' => [
		    'class' => '\consik\yii2daemons\service\ServiceController',
				'daemons' => [
					'testDaemon' => [
						'class' => 'app\daemons\TestDaemon',
						'serviceConfig' => [
							'Service' => ['Type' => 'forking']
						],
					]
				]
			]
		]
		...
	```

* Or implement your own function getServiceConfig in the daemon that will returns config params

	```php
		public function getServiceConfig()
		{
			return [
				'Service' => ['Type' => 'forking']
			];
		}
	```
	
#### Common services configuration. Override basic service configuration. Var ServiceController::$commonServiceConfig.

	It can be changed in your controller configuration. Yii2 console config file:
	```php
		...
		'controllerMap' => [
			'service' => [
			    'class' => '\consik\yii2daemons\service\ServiceController',
			    'commonServiceConfig' => [
						'Service' => ['Type' => 'forking']
					],
			],
		]
		...
	```

#### Basic service configuration. Lowest priority params. Returns by method ```ServiceController::getBasicServiceConfig(string $daemonName)```.
This method returns basic systemd unit configuration for all daemons(Service: ExecStart, Type; Unit: Description, After; Install: WantedBy).
As default all generated services starts after mysql.service. Override param `After` in section `Unit` if you don't need it or if your daemon have other dependencies(mongodb.service for example).

All of these configurations will be merged by array_replace_recursive() before generating each daemon config file;
