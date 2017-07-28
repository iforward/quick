<?php
namespace q\base;

use q;
use \q\base\Exception;

class Component extends Object {

	//private $_events = [];
	
	//private $_behaviors;

	public function __get($name) {
		$getter = 'get' . $name;
		if (method_exists($this, $getter)) {
			// read property, e.g. getName()
			return $this->$getter();
		}
		if (method_exists($this, 'set' . $name)) {
			throw new Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
		}
		else {
			throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
		}
	}

	public function __set($name, $value) {
		$setter = 'set' . $name;
		if (method_exists($this, $setter)) {
			// set property
			$this->$setter($value);
			return;
		}
		/*
		elseif (strncmp($name, 'on ', 3) === 0) {
			// on event: attach event handler
			$this->on(trim(substr($name, 3)), $value);
			return;
		}
		elseif (strncmp($name, 'as ', 3) === 0) {
			// as behavior: attach behavior
			$name = trim(substr($name, 3));
			$this->attachBehavior($name, $value instanceof Behavior ? $value : Q::createObject($value));
			return;
		}
		*/
		if (method_exists($this, 'get' . $name)) {
			throw new Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
		}
		else {
			throw new Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
		}
	}

	public function __isset($name) {
		$getter = 'get' . $name;
		if (method_exists($this, $getter)) {
			return $this->$getter() !== null;
		}
		return false;
	}

	public function __unset($name) {
		$setter = 'set' . $name;
		if (method_exists($this, $setter)) {
			$this->$setter(null);
			return;
		}
		throw new Exception('Unsetting an unknown or read-only property: ' . get_class($this) . '::' . $name);
	}


}


?>
