<?php

require(__DIR__ . '/QBase.php');

class Q extends \q\QBase {

}

spl_autoload_register(['Q', 'autoload'], true, true);
Q::$classMap = [
	'q\base\Configurable' => Q_PATH . '/base/Configurable.php',
	'q\base\Action' => Q_PATH . '/base/Action.php',
	'q\base\Application' => Q_PATH . '/base/Application.php',
	'q\base\Component' => Q_PATH . '/base/Component.php',
	'q\base\Controller' => Q_PATH . '/base/Controller.php',
	'q\base\Exception' => Q_PATH . '/base/Exception.php',
	'q\base\ExitException' => Q_PATH . '/base/ExitException.php',
	'q\base\Module' => Q_PATH . '/base/Module.php',
	'q\base\Action' => Q_PATH . '/base/Action.php',
	'q\base\InlineAction' => Q_PATH . '/base/InlineAction.php',
	'q\base\ErrorHandler' => Q_PATH . '/base/ErrorHandler.php',
	'q\base\ErrorException' => Q_PATH . '/base/ErrorException.php',
	'q\web\Application' => Q_PATH . '/web/Application.php',
	'q\web\Controller' => Q_PATH . '/web/Controller.php',
	'q\web\Request' => Q_PATH . '/web/Request.php',
	'q\web\UrlManager' => Q_PATH . '/web/UrlManager.php',
	'q\web\ErrorHandler' => Q_PATH . '/web/ErrorHandler.php',
	'q\log\Logger' => Q_PATH . '/log/Logger.php',
	'q\log\Dispatcher' => Q_PATH . '/log/Dispatcher.php',
	'q\log\FileTarget' => Q_PATH . '/log/FileTarget.php',
	'q\log\Logger' => Q_PATH . '/log/Logger.php',
	'q\log\Target' => Q_PATH . '/log/Target.php',
	'q\helpers\FileHelper' => Q_PATH . '/helpers/FileHelper.php',
	'q\di\Instance' => Q_PATH . '/di/Instance.php',
	'q\di\Container' => Q_PATH . '/di/Container.php',
	'q\di\ServiceLocator' => Q_PATH . '/di/ServiceLocator.php',
];

Q::$container = new \q\di\Container();
?>
