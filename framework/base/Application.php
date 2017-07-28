<?php
namespace q\base;

use q;
use \q\base\Exception;
use \q\base\ExitException;

abstract class Application extends Module {

	const EVENT_BEFORE_REQUEST = 'beforeRequest';

	const EVENT_AFTER_REQUEST = 'afterRequest';

	public $controllerNamespace = 'app\\controllers';

	public $controller;

	public $requestedRoute;

	public $name;

	public $requestedAction;

	public $requestedParams;

	public $loadedModules = [];


	public function __construct($config = []) {
		Q::$app = $this;
		$this->setInstance($this);

		//$this->state = self::STATE_BEGIN;

		$this->preInit($config);


		$this->registerErrorHandler($config);

		Component::__construct($config);
	}

	public function init() {
		$this->bootstrap();
	}

    protected function bootstrap() {
		$this->getLog();
	}

	public function preInit(&$config) {
		/*
		if (!isset($config['id'])) {
			throw new Exception('The "id" configuration for the Application is required.');
		}
		*/
		if (isset($config['basePath'])) {
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		}
		else {
			throw new Exception('The "basePath" configuration for the Application is required.');
		}

		if (isset($config['runtimePath'])) {
			$this->setRuntimePath($config['runtimePath']);
			unset($config['runtimePath']);
		}
		else {
			// set "@runtime"
			$this->getRuntimePath();
		}

		if (isset($config['timeZone'])) {
			$this->setTimeZone($config['timeZone']);
			unset($config['timeZone']);
		}
		elseif (!ini_get('date.timezone')) {
			$this->setTimeZone('Asia/Shanghai');
		}

		// merge core components with custom components
		foreach ($this->coreComponents() as $id => $component) {
			if (!isset($config['components'][$id])) {
				$config['components'][$id] = $component;
			}
			elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
				$config['components'][$id]['class'] = $component['class'];
			}
		}
	}


	public function run() {
		try {
			$content = $this->handleRequest($this->getRequest());
			echo $content;
		}
		catch (ExitException $e) {
			/*
			if (Q_ENV_TEST) {
				@header('http/1.1 404 '.$e->getMessage()); 
				@header('status: 404 '.$e->getMessage()); 
				$previous = $e->getPrevious();
				echo $previous->getMessage()."<br>\n";
				echo $previous->getTraceAsString();
			}
			else {
				@header('http/1.1 404 '.$e->getMessage()); 
				@header('status: 404 '.$e->getMessage()); 
				echo $e->getMessage();
			}
			exit;
			*/
		}
	}

	private $_runtimePath;

	public function getRuntimePath() {
		if ($this->_runtimePath === null) {
			$this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
		}
		return $this->_runtimePath;
	}

	public function setRuntimePath($path) {
		$this->_runtimePath = Q::getAlias($path);
		Q::setAlias('#runtime', $this->_runtimePath);
	}

	public function getRequest() {
		return $this->get('request');
	}

    public function getUrlManager() {
        return $this->get('urlManager');
    }

	public function setTimeZone($value) {
		date_default_timezone_set($value);
	}

    public function getView() {
        return $this->get('view');
    }

	public function setBasePath($path) {
		parent::setBasePath($path);
		Q::setAlias('#app', $this->getBasePath());
	}

	public function getLog() {
	    return $this->get('log');
	}

	public function getErrorHandler() {
		return $this->get('errorHandler');
	}

	public function coreComponents() {
		return [
			'urlManager' => ['class' => 'q\web\UrlManager'],
			'view' => ['class' => 'q\web\View'],
			'log' => ['class'=>'q\log\Dispatcher'],
			'errorHandler' => ['class' => 'q\base\ErrorHandler'],
		];
	}

	protected function registerErrorHandler(&$config) {
		if (Q_ENABLE_ERROR_HANDLER) {
			if (!isset($config['components']['errorHandler']['class'])) {
				echo "Error: no errorHandler component is configured.\n";
				exit(1);
			}
			$this->set('errorHandler', $config['components']['errorHandler']);
			unset($config['components']['errorHandler']);
			$this->getErrorHandler()->register();
		}
	}
	


}


?>
