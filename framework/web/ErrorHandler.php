<?php
namespace q\web;

use Q;
use q\base\Exception;


class ErrorHandler extends \q\base\ErrorHandler {

	protected function renderException($exception) {
		if ( PHP_SAPI !== 'cli' && isset($exception->statusCode) ) {
		    http_response_code($exception->statusCode);
		}

		if ( Q_ENV_PROD === true ) {
			echo htmlspecialchars($exception->getMessage());
		}
		else {
			do {
				printf("file:%s line:%d message:%s (%d) [%s]\n\n<br/><br/>", $exception->getFile(), $exception->getLine(), htmlspecialchars($exception->getMessage()), $exception->getCode(), get_class($exception));
			}
			while($exception = $exception->getPrevious());
		}
	}


}
?>
