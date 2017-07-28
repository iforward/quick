<?php
namespace q\web;

use q;
use \q\base\InlineAction;
use \q\base\Exception;

class Controller extends \q\base\Controller {

	public $actionParams = [];

    public function redirect($url, $statusCode = 302) {
    }

	public function goHome() {
	}

	public function goBack($defaultUrl = null) {
	}

	public function refresh($anchor = '') {
	}

	public function bindActionParams($action, $params) {

		if ($action instanceof InlineAction) {
			$method = new \ReflectionMethod($this, $action->actionMethod);
		} else {
			$method = new \ReflectionMethod($action, 'run');
		}

		$args = [];
		$missing = [];
		$actionParams = [];


		foreach ($method->getParameters() as $param) {
			$name = $param->getName();
			if (array_key_exists($name, $params)) {
				if ($param->isArray()) {
					$args[] = $actionParams[$name] = (array) $params[$name];
				}
				elseif (!is_array($params[$name])) {
					$args[] = $actionParams[$name] = $params[$name];
				}
				else {
					throw new Exception("Invalid data received for parameter '{$param}'.");
				}
				unset($params[$name]);
			}
			elseif ($param->isDefaultValueAvailable()) {
				$args[] = $actionParams[$name] = $param->getDefaultValue();
			}
			else {
				$missing[] = $name;
			}
		}



        if (!empty($missing)) {
            throw new Exception( 'Missing required parameters: '. implode(', ', $missing) );
        }

        $this->actionParams = $actionParams;

        return $args;
	}

}


?>
