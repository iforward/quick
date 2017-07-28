<?php

namespace q\base;

use q;
use \q\base\Exception;

class View extends Component {

	public $context;

	public $params = [];

	private $_viewFiles = [];

	public $defaultExtension = 'php';

    public function render($view, $params = [], $context = null) {
        $viewFile = $this->findViewFile($view, $context);
        return $this->renderFile($viewFile, $params, $context);
    }


	protected function findViewFile($view, $context = null) {
		if (strncmp($view, '#', 1) === 0) {
			// e.g. "#app/views/main"
			$file = Q::getAlias($view);
		}
		elseif (strncmp($view, '//', 2) === 0) {
			// e.g. "//layouts/main"
			$file = Q::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
		}
		elseif (strncmp($view, '/', 1) === 0) {
			// e.g. "/site/index"
			if (Q::$app->controller !== null) {
				$file = Q::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
			}
			else {
				throw new Exception("Unable to locate view file for view '$view': no active controller.");
			}
		}
		elseif ($context instanceof ViewContextInterface) {
			$file = $context->getViewPath() . DIRECTORY_SEPARATOR . $view;
		}
		elseif (($currentViewFile = $this->getViewFile()) !== false) {
			$file = dirname($currentViewFile) . DIRECTORY_SEPARATOR . $view;
		}
		else {
			throw new Exception("Unable to resolve view file for view '$view': no active view context.");
		}

		if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
			return $file;
		}
		$path = $file . '.' . $this->defaultExtension;
		if ($this->defaultExtension !== 'php' && !is_file($path)) {
			$path = $file . '.php';
		}

		return $path;
	}

	public function renderFile($viewFile, $params = [], $context = null) {
		$viewFile = Q::getAlias($viewFile);

		if ( is_file($viewFile) === false ) {
			throw new Exception("The view file does not exist: $viewFile");
		}

		$oldContext = $this->context;
		if ($context !== null) {
			$this->context = $context;
		}
		$output = '';
		$this->_viewFiles[] = $viewFile;

		$output = $this->renderPhpFile($viewFile, $params);

		array_pop($this->_viewFiles);
		$this->context = $oldContext;

		return $output;
	}

    public function renderPhpFile($_file_, $_params_ = []) {
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        require($_file_);
        return ob_get_clean();
    }

}

?>
