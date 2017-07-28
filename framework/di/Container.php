<?php
namespace q\di;

use ReflectionClass;
use q\base\Component;
use q\base\Exception;

class Container extends Component {

	//用于保存单例Singleton对象，以对象类型为键 
	private $_singletons = [];
	//用于保存依赖的定义，以对象类型为键
	private $_definitions = [];
	//用于保存构造函数的参数，以对象类型为键
	private $_params = [];
	//用于缓存ReflectionClass对象，以类名或接口名为键
	private $_reflections = [];
	//用于缓存依赖信息，以类名或接口名为键
	private $_dependencies = [];

	/* --------------------------------------------------------------------------*/
	/**
		* @synopsis  get  返回一个对象或一个别名所代表的对象
		*
		* @param $class 将要创建或者获取的对象。可以是一个类名、接口名、别名
		* @param $params 要创建的对象的构造函数的参数
		* @param $config 配置获取的对象
		*
		* @returns   
	 */
	/* ----------------------------------------------------------------------------*/
	public function get($class, $params = [], $config = []) {
		// 已经存在完成实例化的单例，直接返回单例
		if (isset($this->_singletons[$class])) {
			return $this->_singletons[$class];
		}
		// 尚未注册过的依赖，说明它不依赖其他单元，或者依赖信息不用定义，
		// 则根据传入的参数创建一个实例
		elseif (!isset($this->_definitions[$class])) {
			return $this->build($class, $params, $config);
		}

        $definition = $this->_definitions[$class];

		//依赖的定义是个 PHP callable，调用
        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            $object = call_user_func($definition, $this, $params, $config);
        }
		elseif (is_array($definition)) { // 依赖的定义是个数组，合并相关的配置和参数，创建
            $concrete = $definition['class'];
            unset($definition['class']);

			// 将依赖定义中配置数组和参数数组与传入的配置数组和参数数组合并
            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
				//创建类,递归结束条件
                $object = $this->build($class, $params, $config);
            }
			else {
				// 递归解析
                $object = $this->get($concrete, $params, $config);
            }
        }
		elseif (is_object($definition)) { // 依赖的定义是个对象则应当保存为单例
            return $this->_singletons[$class] = $definition;
        }
		else {
            throw new Exception('Unexpected object definition type: ' . gettype($definition));
        }


		// 依赖的定义已经定义为单例的，应当实例化该对象
        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }

        return $object;
	}


	//注册依赖
	public function set($class, $definition = [], array $params = []) {
		$this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
		$this->_params[$class] = $params;
		unset($this->_singletons[$class]);
		return $this;
	}

	//注册依赖为单例类
	public function setSingleton($class, $definition = [], array $params = []) {
		$this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
		$this->_params[$class] = $params;
		$this->_singletons[$class] = null;
		return $this;
	}

	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  has  检查依赖是否注册到容器
	*
	* @param $class className
	*
	* @returns bool
	*/
	/* ----------------------------------------------------------------------------*/
	public function has($class) {
		return isset($this->_definitions[$class]);
	}

	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  hasSingleton  检查依赖是否注册到容器(单例)
	*
	* @param $class className 
	* @param $checkInstance 检查实例是否单例化
	*
	* @returns bool
	*/
	/* ----------------------------------------------------------------------------*/
	public function hasSingleton($class, $checkInstance = false) {
		return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
	}

	/**
	 * 删除容器内指定的依赖
	 */
	public function clear($class) {
		unset($this->_definitions[$class], $this->_singletons[$class]);
	}


	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  normalizeDefinition  对依赖的定义进行规范化处理
	* 如果 $definition 是空的，直接返回数组['class' => $class]
	* 如果 $definition 是字符串，那么认为这个字符串就是所依赖的类名、接口名或别名，那么直接返回数组 ['class' => $definition]
	* 如果 $definition 是一个PHP callable，或是一个对象，那么直接返回该 $definition
	* 如果 $definition 是一个数组，那么其应当是一个包含了元素 $definition['class'] 的配置数组。如果该数组未定义 $definition['class'] 那么，将传入的 $class 作为该元素的值，最后返回该数组。
	* @param $class className
	* @param $definition 依赖的定义 (类名、配置数组、PHP callable)
	*
	* @returns   
	*/
	/* ----------------------------------------------------------------------------*/
	protected function normalizeDefinition($class, $definition) {
		if (empty($definition)) {
			return ['class' => $class];
		}
		elseif (is_string($definition)) {
			return ['class' => $definition];
		}
		elseif (is_callable($definition, true) || is_object($definition)) {
			return $definition;
		}
		elseif (is_array($definition)) {
			if (!isset($definition['class'])) {
				if (strpos($class, '\\') !== false) {
					$definition['class'] = $class;
				}
				else {
					throw new Exception("A class definition requires a \"class\" member.");
				}
			}
			return $definition;
		}
		else {
			throw new Exception("Unsupported definition type for \"$class\": " . gettype($definition));
		}
	}


	protected function build($class, $params, $config) {

		list ($reflection, $dependencies) = $this->getDependencies($class);

		// 用传入的 $params 的内容补充、覆盖到依赖信息中
		foreach ($params as $index => $param) {
			$dependencies[$index] = $param;
		}

		$dependencies = $this->resolveDependencies($dependencies, $reflection);


		//当config为空实例化这个对象
		if (empty($config)) {
			return $reflection->newInstanceArgs($dependencies);
		}

		//当依赖性$dependencies不为空,并且要创建的类实现了q\base\Configurable接口
		if ( !empty($dependencies) && $reflection->implementsInterface('q\base\Configurable') ) {
			// 设置构造函数的最后一个参数为 $config 数组
			$dependencies[count($dependencies) - 1] = $config;
			return $reflection->newInstanceArgs($dependencies);
		}
		else {
			//创建类,并给类参数赋值
			$object = $reflection->newInstanceArgs($dependencies);
			foreach ($config as $name => $value) {
				$object->$name = $value;
			}
			return $object;
		}

	}


    protected function mergeParams($class, $params) {
        if (empty($this->_params[$class])) {
            return $params;
        }
		elseif (empty($params)) {
            return $this->_params[$class];
        }
		else {
            $ps = $this->_params[$class];
            foreach ($params as $index => $value) {
                $ps[$index] = $value;
            }
            return $ps;
        }
    }

	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  getDependencies 获取依赖信息
	*
	* @param $class className
	*
	* @returns  
	*/
	/* ----------------------------------------------------------------------------*/
	protected function getDependencies($class) {
		// 如果已经缓存了其依赖信息，直接返回依赖信息
		if (isset($this->_reflections[$class])) {
			return [$this->_reflections[$class], $this->_dependencies[$class]];
		}

		//使用反射机制来获取类的相关信息
		$dependencies = [];
		$reflection = new ReflectionClass($class);

		//取得该类的构造函数信息
		$constructor = $reflection->getConstructor();
		if ($constructor !== null) {
			//取得该方法所需的参数
			foreach ($constructor->getParameters() as $param) {
				if ($param->isDefaultValueAvailable()) {
					// 构造函数如果有默认值，将默认值作为依赖。
					$dependencies[] = $param->getDefaultValue();
				}
				else {
					// 构造函数没有默认值，则为其创建一个引用。
					$c = $param->getClass();
					$dependencies[] = Instance::of($c === null ? null : $c->getName());
				}
			}
		}

		$this->_reflections[$class] = $reflection;
		$this->_dependencies[$class] = $dependencies;

		return [$reflection, $dependencies];
	}


		
	/* --------------------------------------------------------------------------*/
	/**
	* @synopsis  resolveDependencies 将依赖信息中保存的Istance实例所引用的类或接口进行实例化
	*
	* @param $dependencies array 依赖信息
	* @param $reflection 反射类反射相关的依赖关系
	*
	* @returns  obj
	*/
	/* ----------------------------------------------------------------------------*/
	protected function resolveDependencies($dependencies, $reflection = null) {
		foreach ($dependencies as $index => $dependency) {
			// 前面getDependencies() 函数往 $_dependencies[] 中写入的是一个 Instance 数组
			if ($dependency instanceof Instance) {
				if ($dependency->id !== null) {
					//向容器索要所依赖的实例，递归调用 q\di\Container::get() 
					$dependencies[$index] = $this->get($dependency->id);
				}
				elseif ($reflection !== null) {
					$name = $reflection->getConstructor()->getParameters()[$index]->getName();
					$class = $reflection->getName();
					throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
				}
			}
		}
		return $dependencies;
	}


}

?>
