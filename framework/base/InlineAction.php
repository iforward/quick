<?php
namespace q\base;

use q;
use q\base\Action;

class InlineAction extends Action {

	public $actionMethod;

	public function __construct($id, $controller, $actionMethod, $config = []) {
		$this->actionMethod = $actionMethod;
		parent::__construct($id, $controller, $config);
	}

	public function runWithParams($params) {
		$args = $this->controller->bindActionParams($this, $params);
		if (Q::$app->requestedParams === null) {
			Q::$app->requestedParams = $args;
		}
		return call_user_func_array([$this->controller, $this->actionMethod], $args);
	}
}


?>
