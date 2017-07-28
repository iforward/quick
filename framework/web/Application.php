<?php
namespace q\web;

use q;
use \q\base\Exception;
use \q\web\NotFoundHttpException;

class Application extends \q\base\Application {
	
	public $defaultController = 'index';

	public $controller;

	public $catchAll;


	public function handleRequest($request) {
        if (empty($this->catchAll)) {
            list ($route, $params) = $request->resolve();
        }
		else {
            $route = $this->catchAll[0];
            $params = $this->catchAll;
            unset($params[0]);
        }

		try {
            $this->requestedRoute = $route;
            $result = $this->runAction($route, $params);
			return $result;
		}
		catch ( Exception $e ) {
			throw new NotFoundHttpException('Page not found.', $e->getCode(), $e);
		}
	}


	public function coreComponents() {
		return array_merge(parent::coreComponents(), [
			'request' => ['class' => 'q\web\Request'],
			'session' => ['class' => 'q\web\Session'],
			'user' => ['class' => 'q\web\User'],
			'errorHandler' => ['class' => 'q\web\ErrorHandler'],
		]);
	}

}

?>
