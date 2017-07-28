<?php
namespace q\base;

use q;
use q\base\Exception;
use q\base\ErrorException;

abstract class ErrorHandler extends Component {


	//输出错误前是否抛弃现有的页面的输出
	public $discardExistingOutput = true;

	public $exception;

	//注册自定义错误处理
	public function register() {
		ini_set('display_errors', false);
		set_exception_handler([$this, 'handleException']);
		set_error_handler([$this, 'handleError']);

		register_shutdown_function([$this, 'handleFatalError']);
	}


	//注销错误处理程序,恢复PHP错误和异常处理程序。
	public function unregister() {
	    restore_error_handler();
	    restore_exception_handler();
	}

	public function handleException($exception) {
		if ($exception instanceof ExitException) {
			    return;
		}
		$this->exception = $exception;

		$this->unregister();

		if (PHP_SAPI !== 'cli') {
		    http_response_code(500);
		}

		try {

			$this->logException($exception);

			//discardExistingOutput===true,输出错误前抛弃现有的页面的输出
			if ($this->discardExistingOutput) {
				$this->clearOutput();
			}

			$this->renderException($exception);

			if (!Q_ENV_TEST) {
				\Q::getLogger()->flush(true);
			}
			exit(1);
		}
		catch (\Exception $e) {

		}

	
	}

	//呈现异常 接口
	abstract protected function renderException($exception);

	//错误处理程序
	public function handleError($code, $message, $file, $line) {

		if (error_reporting() & $code) {

			if (!class_exists('q\\base\\ErrorException', false)) {
				require_once(__DIR__ . '/ErrorException.php');
			}

			$exception = new ErrorException($message, $code, $code, $file, $line);

			// in case error appeared in __toString method we can't throw any exception
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			array_shift($trace);
			foreach ($trace as $frame) {
				if ($frame['function'] === '__toString') {
					$this->handleException($exception);
					exit(1);
				}
			}
			throw $exception;
		}
		return false;
	
	}

	//php致命错误处理
	public function handleFatalError() {

		if (!class_exists('q\\base\\ErrorException', false)) {
			require_once(__DIR__ . '/ErrorException.php');
		}

		$error = error_get_last();

		if (ErrorException::isFatalError($error)) {

			$exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);

			$this->exception = $exception;

			$this->logException($exception);

			if ($this->discardExistingOutput) {
				$this->clearOutput();
			}
			$this->renderException($exception);

			Q::getLogger()->flush(true);

			exit(1);
		}
	}


	//异常日志
	public function logException($exception) {
		$category = get_class($exception);
		if ($exception instanceof Exception) {
			$category = 'q\\base\\Exception:' . $exception->statusCode;
		}
		elseif ($exception instanceof \ErrorException) {
			$category .= ':' . $exception->getSeverity();
		}
		Q::error($exception, $category);
	}


	//删除之前的所有输出。
	public function clearOutput() {
		// the following manual level counting is to deal with zlib.output_compression set to On
		for ($level = ob_get_level(); $level > 0; --$level) {
			if (!@ob_end_clean()) {
				ob_clean();
			}
		}
	}


}
?>
