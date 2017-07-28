<?php

namespace FastRoute\Dispatcher;

class GroupCountBased extends RegexBasedAbstract {
    public function __construct($data) {
        list($this->staticRouteMap, $this->variableRouteData) = $data;
    }

    protected function dispatchVariableRoute($routeData, $uri) {
        foreach ($routeData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            list($handler, $varNames) = $data['routeMap'][count($matches)];


            $vars = [];
            $i = 0;
            foreach ($varNames as $varName) {
                $vars[$varName] = $matches[++$i];
				if( strstr( $handler, "<{$varName}>", false ) == true ) {
					$handler = str_replace( "<{$varName}>", $vars[$varName], $handler );
				}
            }
            return [self::FOUND, $handler, $vars];
        }

        return [self::NOT_FOUND];
    }
}
