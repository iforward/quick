<?php

namespace q\web;

use Q;
use FastRoute;
use q\base\Component;

class UrlManager extends Component {

	public $routeParam = 'r';

	public $rules;

    private $_baseUrl;
    private $_scriptUrl;
    private $_hostInfo;
    private $_ruleCache;

	//@var string the URL suffix used when in 'path' format.
	public $suffix;

	public $enablePrettyUrl = false;
	public $enableStrictParsing = false;

	public function parseRequest ( $request ) {

		if ($this->enablePrettyUrl) {
			$pathInfo = $request->getPathInfo();

			if (($result = $this->FastRoute( $pathInfo )) !== false) {
				return $result;
			}

			if ($this->enableStrictParsing) {
				return false;
			}

			$suffix = (string) $this->suffix;
			if ($suffix !== '' && $pathInfo !== '') {
				$n = strlen($this->suffix);
				if (substr_compare($pathInfo, $this->suffix, -$n, $n) === 0) {
					$pathInfo = substr($pathInfo, 0, -$n);
					if ($pathInfo === '') {
						return false;
					}
				}
				else {
					return false;
				}
			}

			return [$pathInfo, []];
		}
		else {
			$route = $request->getQueryParam($this->routeParam, '');
			if (is_array($route)) {
				$route = '';
			}

			return [(string) $route, []];
		}

	}


	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  FastRoute  解析路由
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public function FastRoute( $uri ) {
		//加载fastRoute
		require Q::getAlias('#q').'/FastRoute/bootstrap.php';

		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
			foreach ( $this->rules as $key => $val ) {
				$r->addRoute(['GET', 'POST'], $key, $val);
			}
			unset( $key, $val );
		});

		$httpMethod = $_SERVER['REQUEST_METHOD'];

		$route_ary = $dispatcher->dispatch($httpMethod, $uri);

		switch ($route_ary[0]) {
			case FastRoute\Dispatcher::FOUND:
				return [ $route_ary[1], isset( $route_ary[2]['params'] ) ? $this->parseParams( $route_ary[2]['params'] ) : [] ];
			default:
				return false;
		}
	}
 

	public function parseParams( $pathInfo ) {
		$data = [];
		if($pathInfo==='') return;
		$segs = explode( '/' , $pathInfo . '/' );
		$n = count( $segs );
		for( $i=0; $i<$n-1; $i+=2 ) {
			$key = $segs[$i];
			if( $key==='' ) continue;
			$value = $segs[$i+1];
			if(($pos=strpos($key,'['))!==false && ($m=preg_match_all('/\[(.*?)\]/',$key,$matches))>0) {
				$name=substr($key,0,$pos);
				for($j=$m-1;$j>=0;--$j) {
					if($matches[1][$j]==='') $value=array($value);
					else $value=array($matches[1][$j]=>$value);
				}
				$data[$name]=$value;
			}
			else {
				$data[$key]=$value;
			}
		}
		return $data;
	}



}


?>
