<?php
namespace q;

use \q\base\Exception;
use \q\log\Logger;

defined('Q_BEGIN_TIME') or define('Q_BEGIN_TIME', microtime(true));

defined('Q_PATH') or define('Q_PATH', __DIR__);

defined('Q_DEBUG') or define('Q_DEBUG', true);

defined('Q_ENV') or define('Q_ENV', 'prod');

defined('Q_ENV_PROD') or define('Q_ENV_PROD', Q_ENV === 'prod');

defined('Q_ENV_DEV') or define('Q_ENV_DEV', Q_ENV === 'dev');

defined('Q_ENV_TEST') or define('Q_ENV_TEST', Q_ENV === 'test');

//定义错误处理是否启动
defined('Q_ENABLE_ERROR_HANDLER') or define('Q_ENABLE_ERROR_HANDLER', true);

class QBase {
	public static $classMap = [];
	public static $app;
	public static $aliases = ['#q' => __DIR__];
	public static $container;

	public static function getVersion() {
			return '0.0.1';
	}

	public static function getAlias($alias, $throwException = true) {
		if (strncmp($alias, '#', 1)) {
			// not an alias
			return $alias;
		}

		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);

		if (isset(static::$aliases[$root])) {
			if (is_string(static::$aliases[$root])) {
				return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
			} else {
				foreach (static::$aliases[$root] as $name => $path) {
					if (strpos($alias . '/', $name . '/') === 0) {
						return $path . substr($alias, strlen($name));
					}
				}
			}
		}

		if ($throwException) {
			throw new \q\base\Exception("Invalid path alias: $alias");
		} else {
			return false;
		}
	}

	public static function getRootAlias($alias) {
		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);

		if (isset(static::$aliases[$root])) {
			if (is_string(static::$aliases[$root])) {
				return $root;
			}
			else {
				foreach (static::$aliases[$root] as $name => $path) {
					if (strpos($alias . '/', $name . '/') === 0) {
						return $name;
					}
				}
			}
		}

		return false;
	}

	public static function setAlias($alias, $path) {
		if (strncmp($alias, '#', 1)) {
			$alias = '#' . $alias;
		}
		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);
		if ($path !== null) {
			$path = strncmp($path, '#', 1) ? rtrim($path, '\\/') : static::getAlias($path);
			if (!isset(static::$aliases[$root])) {
				if ($pos === false) {
					static::$aliases[$root] = $path;
				} else {
					static::$aliases[$root] = [$alias => $path];
				}
			} elseif (is_string(static::$aliases[$root])) {
				if ($pos === false) {
					static::$aliases[$root] = $path;
				} else {
					static::$aliases[$root] = [
						$alias => $path,
						$root => static::$aliases[$root],
					];
				}
			} else {
				static::$aliases[$root][$alias] = $path;
				krsort(static::$aliases[$root]);
			}
		} elseif (isset(static::$aliases[$root])) {
			if (is_array(static::$aliases[$root])) {
				unset(static::$aliases[$root][$alias]);
			} elseif ($pos === false) {
				unset(static::$aliases[$root]);
			}
		}
	}


	public static function autoload($className) {
		if (isset(static::$classMap[$className])) {
			$classFile = static::$classMap[$className];
			if ($classFile[0] === '#') {
				$classFile = static::getAlias($classFile);
			}
		} elseif (strpos($className, '\\') !== false) {
			$classFile = static::getAlias('#' . str_replace('\\', '/', $className) . '.php', false);
			if ($classFile === false || !is_file($classFile)) {
				return;
			}
		} else {
			return;
		}

		include($classFile);

		if (Q_DEBUG && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
			throw new Exception("Unable to find '$className' in file: $classFile. Namespace missing?");
		}
	}


	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  createObject 创建对象,处理依赖
	*
	* @param $type
	* @param $params
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public static function createObject($type, array $params = []) {
		if (is_string($type)) {
		    return static::$container->get($type, $params);
		}
		elseif (is_array($type) && isset($type['class'])) {
		    $class = $type['class'];
		    unset($type['class']);
		    return static::$container->get($class, $params, $type);
		}
		elseif (is_callable($type, true)) {
		    return call_user_func($type, $params);
		}
		elseif (is_array($type)) {
			throw new Exception('Object configuration must be an array containing a "class" element.');
		}
		else {
			throw new Exception('Unsupported configuration type: ' . gettype($type));
		}
	}

	private static $_logger;

	/**
	 * @return Logger message logger
	 */
	public static function getLogger() {
		if (self::$_logger !== null) {
			return self::$_logger;
		}
		else {
			return self::$_logger = static::createObject('q\log\Logger');
		}
	}


	/**
	 * Sets the logger object.
	 * @param Logger $logger the logger object.
	 */
	public static function setLogger($logger) {
		self::$_logger = $logger;
	}

	/*
	主要是用于开发目的，用以标明某些代码的运作流程
	在开发模式下才起效,Q_DEBUG 是 true 的时候
	*/
	public static function trace($message, $category = 'application') {
		if (Q_DEBUG) {
			static::getLogger()->log($message, Logger::LEVEL_TRACE, $category);
		}
	}

	//记录调试信息
	public static function debug($message, $category = 'ad') {
		static::getLogger()->log($message, Logger::LEVEL_DEBUG, $category);
	}

	//用以记录那些不可恢复的错误
	public static function error($message, $category = 'application') {
		static::getLogger()->log($message, Logger::LEVEL_ERROR, $category);
	}

	//在错误发生后，运行仍可继续执行时记录
	public static function warning($message, $category = 'application') {
		static::getLogger()->log($message, Logger::LEVEL_WARNING, $category);
	}

	//用以在重要事件执行时保存记录，比如管理员的登陆
	public static function info($message, $category = 'application') {
		static::getLogger()->log($message, Logger::LEVEL_INFO, $category);
	}

	public static function beginProfile($token, $category = 'profile') {
		static::getLogger()->log($token, Logger::LEVEL_PROFILE_BEGIN, $category);
	}

	public static function endProfile($token, $category = 'profile') {
		static::getLogger()->log($token, Logger::LEVEL_PROFILE_END, $category);
	}


	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  configure  配置对象初始属性
	*
	* @param $object
	* @param $properties
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public static function configure($object, $properties) {
		//print_r($object);
		//print_r($properties);
		foreach ($properties as $name => $value) {
			$object->$name = $value;
		}
		return $object;
	}


	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  getObjectVars 返回由 obj 指定的对象中定义的属性组成的关联数组。
	*
	* @param $object
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	public static function getObjectVars($object) {
		return get_object_vars($object);
	}


}

?>
