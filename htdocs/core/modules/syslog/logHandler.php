<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandlerInterface.php';

/**
 * Parent class for log handlers
 */
class LogHandler
{
	const STABLE = 'stable';
	const DEVELOPMENT = 'development';
	const EXPERIMENTAL = 'experimental';

	/**
	 * Content of the info tooltip.
	 *
	 * @return false|string
	 */
	public function getInfo()
	{
		return false;
	}

	/**
	 * Version of the module
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return self::EXPERIMENTAL;
	}

	/**
	 * ¿Is the module active?
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return false;
	}

	/**
	 * Configuration variables of the module
	 *
	 * @return array
	 */
	public function configure()
	{
		return array();
	}

	/**
	 * Function that checks if the configuration is valid.
	 * It will be called after setting the configuration.
	 * The function returns an array with error messages
	 *
	 * @return array
	 */
	public function checkConfiguration()
	{
		return array();
	}
}