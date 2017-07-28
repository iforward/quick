<?php
namespace q\web;

use q;
use \q\base\Exception;

class Request extends \q\base\Request {

	public $_requestUri;

    public function resolve() {
        $result = Q::$app->getUrlManager()->parseRequest($this);
        if ($result !== false) {
            list ($route, $params) = $result;
            if ($this->_queryParams === null) {
                $_GET = $params + $_GET; // preserve numeric keys
            } else {
                $this->_queryParams = $params + $this->_queryParams;
            }
            return [$route, $this->getQueryParams()];
        }
		else {
            throw new Exception( 'Page not found.' );
        }
    }


    public function parseRoute( $route ) {

        $result = Q::$app->getUrlManager()->parseRoute( $route, $this );

        if ($result !== false) {

			list ( $controller, $action, $params) = $result;

			if ($this->_queryParams === null) {
				$_GET = $params + $_GET; // preserve numeric keys
			}
			else {
				$this->_queryParams = $params + $this->_queryParams;
			}

			return [$controller, $action, $this->getQueryParams()];
        }
		else {
            throw new Exception('Page not found.');
        }

    }


	private $_queryParams;

	public function getQueryParams()
	{
		if ($this->_queryParams === null) {
			return $_GET;
		}

		return $this->_queryParams;
	}

	public function setQueryParams($values)
	{
		$this->_queryParams = $values;
	}

	public function get($name = null, $defaultValue = null)
	{
		if ($name === null) {
			return $this->getQueryParams();
		} else {
			return $this->getQueryParam($name, $defaultValue);
		}
	}

	public function getQueryParam($name, $defaultValue = null)
	{
		$params = $this->getQueryParams();

		return isset($params[$name]) ? $params[$name] : $defaultValue;
	}

	private $_hostInfo;

	public function getHostInfo()
	{
		if ($this->_hostInfo === null) {
			$secure = $this->getIsSecureConnection();
			$http = $secure ? 'https' : 'http';
			if (isset($_SERVER['HTTP_HOST'])) {
				$this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
			} else {
				$this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
				$port = $secure ? $this->getSecurePort() : $this->getPort();
				if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
					$this->_hostInfo .= ':' . $port;
				}
			}
		}

		return $this->_hostInfo;
	}

	public function setHostInfo($value)
	{
		$this->_hostInfo = rtrim($value, '/');
	}

	private $_baseUrl;

	public function getBaseUrl()
	{
		if ($this->_baseUrl === null) {
			$this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
		}

		return $this->_baseUrl;
	}

	public function setBaseUrl($value)
	{
		$this->_baseUrl = $value;
	}

	private $_scriptUrl;

	public function getScriptUrl()
	{
		if ($this->_scriptUrl === null) {
			$scriptFile = $this->getScriptFile();
			$scriptName = basename($scriptFile);
			if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
			} elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['PHP_SELF'];
			} elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
			} elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
				$this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
			} elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
				$this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
			} else {
				throw new InvalidConfigException('Unable to determine the entry script URL.');
			}
		}

		return $this->_scriptUrl;
	}

	public function setScriptUrl($value)
	{
		$this->_scriptUrl = '/' . trim($value, '/');
	}

	private $_scriptFile;

	public function getScriptFile()
	{
		return isset($this->_scriptFile) ? $this->_scriptFile : $_SERVER['SCRIPT_FILENAME'];
	}

	public function setScriptFile($value)
	{
		$this->_scriptFile = $value;
	}

	public $_pathInfo;

    public function getPathInfo() {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }

        return $this->_pathInfo;
    }

    public function setPathInfo($value) {
        $this->_pathInfo = ltrim($value, '/');
    }


	protected function resolvePathInfo() {

		$pathInfo = $this->getUrl();

		if (($pos = strpos($pathInfo, '?')) !== false) {
			$pathInfo = substr($pathInfo, 0, $pos);
		}

		$pathInfo = urldecode($pathInfo);

		// try to encode in UTF8 if not so
		// http://w3.org/International/questions/qa-forms-utf-8.html
		if (!preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]              # ASCII
			| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
			)*$%xs', $pathInfo)
		) {
			$pathInfo = utf8_encode($pathInfo);
		}

		$scriptUrl = $this->getScriptUrl();
		$baseUrl = $this->getBaseUrl();
		if (strpos($pathInfo, $scriptUrl) === 0) {
			$pathInfo = substr($pathInfo, strlen($scriptUrl));
		} elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
			$pathInfo = substr($pathInfo, strlen($baseUrl));
		} elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
			$pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
		} else {
			throw new Exception('Unable to determine the path info of the current request.');
		}

		if (substr($pathInfo, 0, 1) === '/') {
			$pathInfo = substr($pathInfo, 1);
		}

		return (string) $pathInfo;
	}


	private $_url;

	public function getUrl() {
		if ($this->_url === null) {
			$this->_url = $this->resolveRequestUri();
		}

		return $this->_url;
	}

	public function setUrl($value) {
		$this->_url = $value;
	}


	protected function resolveRequestUri() {
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
			$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif (isset($_SERVER['REQUEST_URI'])) {
			$requestUri = $_SERVER['REQUEST_URI'];
			if ($requestUri !== '' && $requestUri[0] !== '/') {
				$requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
			}
		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
			$requestUri = $_SERVER['ORIG_PATH_INFO'];
			if (!empty($_SERVER['QUERY_STRING'])) {
				$requestUri .= '?' . $_SERVER['QUERY_STRING'];
			}
		} else {
			throw new Exception('Unable to determine the request URI.');
		}

		return $requestUri;
	}

	public function getQueryString()
	{
		return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	}

	public function getIsSecureConnection()
	{
		return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
			|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
	}

	public function getServerName()
	{
		return $_SERVER['SERVER_NAME'];
	}

	public function getServerPort()
	{
		return (int) $_SERVER['SERVER_PORT'];
	}

	public function getReferrer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}

	public function getUserAgent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

	public function getUserIP()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	public function getUserHost()
	{
		return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
	}

	public function getAuthUser()
	{
		return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
	}

	public function getAuthPassword()
	{
		return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
	}

	private $_port;

	public function getPort()
	{
		if ($this->_port === null) {
			$this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
		}

		return $this->_port;
	}

	public function setPort($value)
	{
		if ($value != $this->_port) {
			$this->_port = (int) $value;
			$this->_hostInfo = null;
		}
	}



}
