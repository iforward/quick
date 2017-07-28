<?php
namespace q\base;

use q;
use q\base\Exception;

class Action extends Component {

	public $id;

	public $controller;

	public function __construct($id, $controller, $config = []) {
		$this->id = $id;
		$this->controller = $controller;
		parent::__construct($config);
	}

	public function getUniqueId() {
		return $this->controller->getUniqueId() . '/' . $this->id;
	}

	public function runWithParams($params) {
		if (!method_exists($this, 'run')) {
			throw new Exception(get_class($this) . ' must define a "run()" method.');
		}
		$args = $this->controller->bindActionParams($this, $params);
		if (Q::$app->requestedParams === null) {
			Q::$app->requestedParams = $args;
		}
		if ($this->beforeRun()) {
			$result = call_user_func_array([$this, 'run'], $args);
			$this->afterRun();
			return $result;
		}
		else {
			return null;
		}
	}

	protected function beforeRun() {
		return true;
	}

	protected function afterRun() {
	}


}

?>
