<?php
/**
* @file ServiceLocator.php
* @synopsis  依赖注入
* @author Iforward - 87281405@qq.com
* @version 1.0.0
* @date 2016-01-26
 */
namespace q\di;

use q;
use Closure;
use q\base\Component;
use q\base\Exception;

class ServiceLocator extends Component {
	
	//缓存服务、组件等的实例
	private $_components = [];
	
	//定义服务和组件，通常为配置数组，可以用来创建具体的实例
	private $_definitions = [];

	//重载了 getter 方法，使得访问服务和组件就跟访问类的属性一样。
    public function __get($name) {
        if ($this->has($name)) {
			//返回服务或组件的实例
            return $this->get($name);
        }
		else {
            return parent::__get($name);
        }
    }

     function __isset($name) {
        if ($this->has($name, true)) {
            return true;
        }
		else {
            return parent::__isset($name);
        }
    }

	//判断是否已经定义了某个服务或组件或已有服务或组件实例
    public function has($id, $checkInstance = false) {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

	//根据 $id 获取对应的服务或组件的实例
    public function get($id, $throwException = true) {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

		//检查服务或组件是否定义
        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
			//如果定义的是obj并且不是Closure对象,直接返回定义的对象
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            }
			else { //调用createObject创建实例,并保存到缓存_components
                return $this->_components[$id] = Q::createObject($definition);
            }
        }
		elseif ($throwException) {
            throw new Exception("Unknown component ID: $id");
        }
		else {
            return null;
        }
    }

	/* --------------------------------------------------------------------------*/
	/**
		* @synopsis  set 用于注册一个组件或服务
		*
		* @param $id 用于标识服务或组件。
		* @param $definition $definition 可以是一个类名，一个配置数组，一个PHP callable，或者一个对象
		*
		* @returns   
	 */
	/* ----------------------------------------------------------------------------*/
    public function set($id, $definition) {
		//$definition === null 删除一个服务或组件
        if ($definition === null) {
            unset($this->_components[$id], $this->_definitions[$id]);
            return;
        }

		// 确保服务或组件ID的唯一性
        unset($this->_components[$id]);

		//定义如果是个对象或PHP callable，或类名，直接作为定义保存
        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;
        }
		elseif (is_array($definition)) { //如果是数组，确保数组中具有 class 元素
            // a configuration array
            if (isset($definition['class'])) {
				// 定义的过程
                $this->_definitions[$id] = $definition;
            }
			else {
                throw new Exception("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        }
		else {
            throw new Exception("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

	// 删除一个服务或组件
	public function clear($id) {
		unset($this->_definitions[$id], $this->_components[$id]);
	}

	// 用于返回Service Locator的 $_components 数组或 $_definitions 数组，
	// 同时也是 components 属性的getter函数
	public function getComponents($returnDefinitions = true) {
		return $returnDefinitions ? $this->_definitions : $this->_components;
	}

	// 批量方式注册服务或组件，同时也是 components 属性的setter函数
	public function setComponents($components) {
		foreach ($components as $id => $component) {
			$this->set($id, $component);
		}
	}

}
?>
