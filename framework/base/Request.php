<?php
namespace q\base;

use q;

abstract class Request extends Component {

    private $_scriptFile;
    private $_isConsoleRequest;

    abstract public function resolve();


    public function getIsConsoleRequest() {
        return $this->_isConsoleRequest !== null ? $this->_isConsoleRequest : PHP_SAPI === 'cli';
    }

    public function setIsConsoleRequest($value) {
        $this->_isConsoleRequest = $value;
    }

    public function getScriptFile() {
        if ($this->_scriptFile === null) {
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $this->setScriptFile($_SERVER['SCRIPT_FILENAME']);
            }
			else {
                throw new Exception('Unable to determine the entry script file path.');
            }
        }

        return $this->_scriptFile;
    }

    public function setScriptFile($value) {
        $scriptFile = realpath(Q::getAlias($value));
        if ($scriptFile !== false && is_file($scriptFile)) {
            $this->_scriptFile = $scriptFile;
        } else {
            throw new Exception('Unable to determine the entry script file path.');
        }
    }

}
