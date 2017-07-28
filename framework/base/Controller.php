<?php
namespace q\base;

use q;
use \q\base\Exception;
use \q\base\InlineAction;

class Controller extends Component implements ViewContextInterface {

	const EVENT_BEFORE_ACTION = 'beforeAction';
	
	const EVENT_AFTER_ACTION = 'afterAction';


	//controllerName
	public $id;

	public $module;

	public $defaultAction = 'index';

	public $layout = null;

	public $action;

	private $_view;

	private $_viewPath;


    public function __construct($id, $module, $config = []) {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }

	public function actions() {
		return [];
	}

	public function runAction($id, $params = []) {
		$action = $this->createAction($id);
		if ($action === null) {
			throw new Exception('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
		}

		if (Q::$app->requestedAction === null) {
			Q::$app->requestedAction = $action;
		}

		$oldAction = $this->action;
		$this->action = $action;

		$result = null;

		$this->beforeAction();
		Q::beginProfile( 'action' );
		$result = $action->runWithParams($params);
		Q::endProfile( 'action' );
		$this->afterAction();

		$this->action = $oldAction;

		return $result;

	}

	public function createAction($id) {
		if ($id === '') {
			$id = $this->defaultAction;
		}

		if (preg_match('/^[A-Za-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
			$methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
			if (method_exists($this, $methodName)) {
				$method = new \ReflectionMethod($this, $methodName);
				if ($method->isPublic() && $method->getName() === $methodName) {
					return new InlineAction($id, $this, $methodName);
				}
			}
		}

		return null;
	}

	public function getModules() {
		$modules = [$this->module];
		$module = $this->module;
		while ($module->module !== null) {
			array_unshift($modules, $module->module);
			$module = $module->module;
		}
		return $modules;
	}

	public function getUniqueId() {
		return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
	}

	public function bindActionParams($action, $params) {
		return [];
	}

	public function getRoute() {
		return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
	}

	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  render 渲染视图,包括layout
	*
	* @param $view
	* @param $params
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public function render($view, $params = []) {
		$content = $this->getView()->render($view, $params, $this);
		return $this->renderContent($content);
	}

	public function renderContent($content) {
		$layoutFile = $this->findLayoutFile($this->getView());
		if ($layoutFile !== false) {
			return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
		} else {
			return $content;
		}
	}

	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  renderPartial  渲染视图,不包括layout
	*
	* @param $view
	* @param $params
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public function renderPartial($view, $params = []) {
		return $this->getView()->render($view, $params, $this);
	}

	
	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  renderFile  渲染视图
	*
	* @param $file
	* @param $params
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public function renderFile($file, $params = []) {
		return $this->getView()->renderFile($file, $params, $this);
	}

	public function getView() {
		if ($this->_view === null) {
			$this->_view = Q::$app->getView();
		}
		return $this->_view;
	}

	public function setView($view) {
		$this->_view = $view;
	}

	public function getViewPath() {
		if ($this->_viewPath === null) {
			$this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
		}
		return $this->_viewPath;
	}

	public function setViewPath($path) {
		$this->_viewPath = Yii::getAlias($path);
	}

	public function findLayoutFile($view) {
		$module = $this->module;
		if (is_string($this->layout)) {
			$layout = $this->layout;
		}
		elseif ($this->layout === null) {
			while ($module !== null && $module->layout === null) {
				$module = $module->module;
			}
			if ($module !== null && is_string($module->layout)) {
				$layout = $module->layout;
			}
		}
		if (!isset($layout)) {
			return false;
		}
		if (strncmp($layout, '#', 1) === 0) {
			$file = Q::getAlias($layout);
		}
		elseif (strncmp($layout, '/', 1) === 0) {
			$file = Q::$app->getLayoutPath() . DIRECTORY_SEPARATOR . substr($layout, 1);
		}
		else {
			$file = $module->getLayoutPath() . DIRECTORY_SEPARATOR . $layout;
		}
		if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
			return $file;
		}
		$path = $file . '.' . $view->defaultExtension;
		if ($view->defaultExtension !== 'php' && !is_file($path)) {
			$path = $file . '.php';
		}
		return $path;
	}


	public function beforeAction() {
	
	}

	public function afterAction() {
	
	}


}


?>
