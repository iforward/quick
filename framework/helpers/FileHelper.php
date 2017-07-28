<?php

namespace q\helpers;

use q;

use \q\base\Exception;

class FileHelper {

	const PATTERN_NODIR = 1;
	const PATTERN_ENDSWITH = 4;
	const PATTERN_MUSTBEDIR = 8;
	const PATTERN_NEGATIVE = 16;
	const PATTERN_CASE_INSENSITIVE = 32;


	public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR) {
		$path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
		if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
			return $path;
		}
		// the path may contain ".", ".." or double slashes, need to clean them up
		$parts = [];
		foreach (explode($ds, $path) as $part) {
			if ($part === '..' && !empty($parts) && end($parts) !== '..') {
				array_pop($parts);
			}
			elseif ($part === '.' || $part === '' && !empty($parts)) {
				continue;
			}
			else {
				$parts[] = $part;
			}
		}
		$path = implode($ds, $parts);
		return $path === '' ? '.' : $path;
	}


	//创建一个新目录
	public static function createDirectory($path, $mode = 0775, $recursive = true) {
		if (is_dir($path)) {
			return true;
		}
		$parentDir = dirname($path);
		// recurse if parent dir does not exist and we are not at the root of the file system.
		if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
			static::createDirectory($parentDir, $mode, true);
		}
		try {
			if (!mkdir($path, $mode)) {
				return false;
			}
		}
		catch (\Exception $e) {
			if (!is_dir($path)) {
				throw new \q\base\Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
			}
		}
		try {
			return chmod($path, $mode);
		}
		catch (\Exception $e) {
			throw new \q\base\Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
		}
	}

	//复制一个目录
	public static function copyDirectory($src, $dst, $options = []) {
		if (!is_dir($dst)) {
			static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
		}

		$handle = opendir($src);
		if ($handle === false) {
			throw new InvalidParamException("Unable to open directory: $src");
		}
		if (!isset($options['basePath'])) {
			// this should be done only once
			$options['basePath'] = realpath($src);
			$options = self::normalizeOptions($options);
		}
		while (($file = readdir($handle)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}
			$from = $src . DIRECTORY_SEPARATOR . $file;
			$to = $dst . DIRECTORY_SEPARATOR . $file;
			if (static::filterPath($from, $options)) {
				if (isset($options['beforeCopy']) && !call_user_func($options['beforeCopy'], $from, $to)) {
					continue;
				}
				if (is_file($from)) {
					copy($from, $to);
					if (isset($options['fileMode'])) {
						@chmod($to, $options['fileMode']);
					}
				} else {
					static::copyDirectory($from, $to, $options);
				}
				if (isset($options['afterCopy'])) {
					call_user_func($options['afterCopy'], $from, $to);
				}
			}
		}
		closedir($handle);
	}

	public static function removeDirectory($dir, $options = []) {
		if (!is_dir($dir)) {
			return;
		}
		if (isset($options['traverseSymlinks']) && $options['traverseSymlinks'] || !is_link($dir)) {
			if (!($handle = opendir($dir))) {
				return;
			}
			while (($file = readdir($handle)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				$path = $dir . DIRECTORY_SEPARATOR . $file;
				if (is_dir($path)) {
					static::removeDirectory($path, $options);
				}
				else {
					try {
						unlink($path);
					}
					catch (ErrorException $e) {
						if (DIRECTORY_SEPARATOR === '\\') {
							// last resort measure for Windows
							$lines = [];
							exec("DEL /F/Q \"$path\"", $lines, $deleteError);
						}
						else {
							throw $e;
						}
					}
				}
			}
			closedir($handle);
		}
		if (is_link($dir)) {
			unlink($dir);
		}
		else {
			rmdir($dir);
		}
	}


	public static function findFiles($dir, $options = []) {
		if (!is_dir($dir)) {
			throw new Exception("The dir argument must be a directory: $dir");
		}
		$dir = rtrim($dir, DIRECTORY_SEPARATOR);
		if (!isset($options['basePath'])) {
			// this should be done only once
			$options['basePath'] = realpath($dir);
			$options = self::normalizeOptions($options);
		}
		$list = [];
		$handle = opendir($dir);
		if ($handle === false) {
			throw new Exception("Unable to open directory: $dir");
		}
		while (($file = readdir($handle)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			if (static::filterPath($path, $options)) {
				if (is_file($path)) {
					$list[] = $path;
				} elseif (!isset($options['recursive']) || $options['recursive']) {
					$list = array_merge($list, static::findFiles($path, $options));
				}
			}
		}
		closedir($handle);

		return $list;
	}



}


?>
