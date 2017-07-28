<?php
namespace q\di;

use q;
use q\base\Exception;

class Instance {
	/**
	* * @var string the component ID, class name, interface name or alias name
	*/
	public $id;

	protected function __construct($id) {
		$this->id = $id;
	}

	// 静态方法创建一个Instance实例
	public static function of($id) {
	    return new static($id);
	}


	public static function ensure($reference, $type = null, $container = null) {
		if ($reference instanceof $type) {
			return $reference;
		}
		elseif (is_array($reference)) {
			$class = isset($reference['class']) ? $reference['class'] : $type;
			if (!$container instanceof Container) {
				$container = Q::$container;
			}
			unset($reference['class']);
			return $container->get($class, [], $reference);
		}
		elseif (empty($reference)) {
			throw new Exception('The required component is not specified.');
		}

		if (is_string($reference)) {
			$reference = new static($reference);
		}

		if ($reference instanceof self) {
			$component = $reference->get($container);
			if ($component instanceof $type || $type === null) {
				return $component;
			}
			else {
				throw new Exception('"' . $reference->id . '" refers to a ' . get_class($component) . " component. $type is expected.");
			}
		}

		$valueType = is_object($reference) ? get_class($reference) : gettype($reference);
		throw new Exception("Invalid data type: $valueType. $type is expected.");
	}



	// 获取这个实例所引用的实际对象，它调用的是 q\di\Container::get()来获取实际对象
	public function get($container = null) {
		if ($container) {
			return $container->get($this->id);
		}
		if (Q::$app && Q::$app->has($this->id)) {
			return Q::$app->get($this->id);
		}
		else {
			return Q::$container->get($this->id);
		}
	}



}

?>
