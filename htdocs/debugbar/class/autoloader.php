<?php

/**
 * Simple autoloader, so we don't need Composer just for this.
 */

spl_autoload_register(function ($class) {
	if (preg_match('/^DebugBar/', $class)) {
		$file = DOL_DOCUMENT_ROOT.'/includes/maximebf/debugbar/src/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		//var_dump($class.' - '.file_exists($file).' - '.$file);
		if (file_exists($file)) {
			require_once $file;
			return true;
		}
		return false;
	}
	if (preg_match('/^'.preg_quote('Psr\Log', '/').'/', $class)) {
		$file = DOL_DOCUMENT_ROOT.'/includes/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		//var_dump($class.' - '.file_exists($file).' - '.$file);
		if (file_exists($file)) {
			require_once $file;
			return true;
		}
		return false;
	}
	if (preg_match('/^'.preg_quote('Symfony\Component\VarDumper', '/').'/', $class)) {
		$class = preg_replace('/'.preg_quote('Symfony\Component\VarDumper', '/').'/', '', $class);
		$file = DOL_DOCUMENT_ROOT.'/includes/symfony/var-dumper/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		if (file_exists($file)) {
			require_once $file;
			return true;
		}
		return false;
	}
	return true;
});
