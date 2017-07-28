<?php
namespace app\components;

use \q\base\Component;
use \q\base\Exception;

class Redis extends Component {

	/**
	 * The redis client
	 * @var Redis
	 */
	protected $_client;

	/**
	 * The redis server name
	 * @var string
	 */
	public $hostname = "localhost";

    /**
     * Redis default prefix
     * @var string
     */
    public $prefix = "";

	/**
	 * The redis server port
	 * @var integer
	 */
	public $port=6379;

	/**
	 * The database to use, defaults to 1
	 * @var integer
	 */
	public $database=0;

    /**
     * The redis server password
     * @var password
     */
    public $password=null;

    public $timeout = false;


	public function setClient(Redis $client) {
		$this->_client = $client;
	}

	/**
	 * Gets the redis client
	 * @return Redis the redis client
	 */
	public function getClient() {

		if ($this->_client === null) {
			$this->_client = new \Redis;
			$this->_client->connect( $this->hostname, $this->port, $this->timeout );
			if (isset($this->password)) {
				if ($this->_client->auth($this->password) === false) {
					throw new CException( 'Redis authentication failed!' );
				}
			}
            $this->_client->setOption(\Redis::OPT_PREFIX, $this->prefix);
            $this->_client->select($this->database);
		}
		return $this;

	}

	public function __call($name, $parameters) {
		$call = call_user_func_array(array($this->_client,$name),$parameters);
		return $call;
	}


}
