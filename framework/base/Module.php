<?php
namespace q\base;

use q;
use \q\base\Exception;
use \q\di\ServiceLocator;

class Module extends ServiceLocator {

	const EVENT_BEFORE_ACTION = 'beforeAction';

	const EVENT_AFTER_ACTION = 'afterAction';

	public $params = [];

	public $id;

	public $module;

	public $controllerMap = [];

	private $_basePath;

	public $layout;

	private $_viewPath;

	private $_layoutPath;

	private $_modules = [];

	public $defaultRoute = 'index';

    public function __construct($id, $parent = null, $config = []) {
        $this->id = $id;
        $this->module = $parent;
        parent::__construct($config);
    }

	public static function getInstance() {
		$class = get_called_class();
		return isset(Q::$app->loadedModules[$class]) ? Q::$app->loadedModules[$class] : null;
	}

	public static function setInstance($instance) {
		if ($instance === null) {
			unset(Q::$app->loadedModules[get_called_class()]);
		}
		else {
			Q::$app->loadedModules[get_class($instance)] = $instance;
		}
	}

	public function init() {

		if ($this->controllerNamespace === null) {
			$class = get_class($this);
			if (($pos = strrpos($class, '\\')) !== false) {
				$this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
			}
		}
	}

	public function getUniqueId() {
		return $this->module ? ltrim($this->module->getUniqueId() . '/' . $this->id, '/') : $this->id;
	}

	public function getBasePath() {
		if ($this->_basePath === null) {
			$class = new \ReflectionClass($this);
			$this->_basePath = dirname($class->getFileName());
		}
		return $this->_basePath;
	}

	public function setBasePath($path) {
		$path = Q::getAlias($path);
		$p = realpath($path);
		if ($p !== false && is_dir($p)) {
			$this->_basePath = $p;
		}
		else {
			throw new Exception("The directory does not exist: $path");
		}
	}

	public function setModule($id, $module) {
		if ($module === null) {
			unset($this->_modules[$id]);
		}
		else {
			$this->_modules[$id] = $module;
		}
	}

	public function setModules($modules) {
		foreach ($modules as $id => $module) {
			$this->_modules[$id] = $module;
		}
	}

    public function runAction($route, $params = []) {
        $parts = $this->createController($route);
        if (is_array($parts)) {
			list($controller, $actionID) = $parts;
            $oldController = Q::$app->controller;
            Q::$app->controller = $controller;
			$result = $controller->runAction($actionID, $params);
			Q::$app->controller = $oldController;
            return $result;
        }
		else {
            $id = $this->getUniqueId();
            throw new Exception('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
        }
    }

	public function createController($route) {
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        }
		else {
            $id = $route;
            $route = '';
        }

		/*
        // module and controller map take precedence
        if (isset($this->controllerMap[$id])) {
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }
		*/
        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }

        $controller = $this->createControllerByID($id);
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }

		return $controller === null ? false : [$controller, $route];
	}

	public function createControllerByID($id) {
		$className = $id;
		if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
			return null;
		}

		$className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
		$className = ltrim($this->controllerNamespace . '\\' . $className, '\\');
		if (strpos($className, '-') !== false || !class_exists($className)) {
			return null;
		}

		if (is_subclass_of($className, 'q\base\Controller')) {
			$controller = Q::createObject($className, [$id, $this]);
			return get_class($controller) === $className ? $controller : null;
		}
		elseif (Q_DEBUG) {
			throw new Exception("Controller class must extend from \\yii\\base\\Controller.");
		}
		else {
			return null;
		}
	}


	public function getModule($id, $load = true) {
		if (($pos = strpos($id, '/')) !== false) {
			// sub-module
			$module = $this->getModule(substr($id, 0, $pos));
			return $module === null ? null : $module->getModule(substr($id, $pos + 1), $load);
		}
		if (isset($this->_modules[$id])) {
			if ($this->_modules[$id] instanceof Module) {
				return $this->_modules[$id];
			}
			elseif ($load) {
				/* @var $module Module */
				$module = Q::createObject($this->_modules[$id], [$id, $this]);
				$module->setInstance($module);
				return $this->_modules[$id] = $module;
			}
		}
		return null;
	}

	public function getViewPath() {
		if ($this->_viewPath === null) {
			$this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
		}
		return $this->_viewPath;
	}

	public function setViewPath($path) {
		$this->_viewPath = Q::getAlias($path);
	}

	public function getLayoutPath() {
		if ($this->_layoutPath === null) {
			$this->_layoutPath = $this->getViewPath() . DIRECTORY_SEPARATOR . 'layouts';
		}
		return $this->_layoutPath;
	}

	public function setLayoutPath($path) {
		$this->_layoutPath = Q::getAlias($path);
	}


}

?>
