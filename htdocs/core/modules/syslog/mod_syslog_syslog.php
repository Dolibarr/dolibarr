<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to syslog
 */
class mod_syslog_syslog extends LogHandler implements LogHandlerInterface
{
    public $code = 'syslog';

	/**
	 * 	Return name of logger
	 *
	 * 	@return	string		Name of logger
	 */
	public function getName()
	{
		return 'Syslog';
	}

	/**
	 * Version of the module ('x.y.z' or 'dolibarr' or 'experimental' or 'development')
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return 'dolibarr';
	}

	/**
	 * Content of the info tooltip.
	 *
	 * @return false|string
	 */
	public function getInfo()
	{
		global $langs;

		return $langs->trans('OnlyWindowsLOG_USER');
	}

	/**
	 * Is the module active ?
	 *
	 * @return int
	 */
	public function isActive()
	{
	    global $conf;

		// This function does not exists on some ISP (Ex: Free in France)
		if (!function_exists('openlog')) return 0;

		return empty($conf->global->SYSLOG_DISABLE_LOGHANDLER_SYSLOG)?1:0;    // Set SYSLOG_DISABLE_LOGHANDLER_SYSLOG to 1 to disable this loghandler
	}

	/**
	 * 	Return array of configuration data
	 *
	 * 	@return	array		Return array of configuration data
	 */
	public function configure()
	{
		global $langs;

		return array(
			array(
				'constant' => 'SYSLOG_FACILITY',
				'name' => $langs->trans('SyslogFacility'),
				'default' => 'LOG_USER'
			)
		);
	}

	/**
	 * 	Return if configuration is valid
	 *
	 * 	@return	array		Array of errors. Empty array if ok.
	 */
	public function checkConfiguration()
	{
		global $conf, $langs;

		$errors = array();

	    $facility = constant($conf->global->SYSLOG_FACILITY);
	    if ($facility)
		{
			// Only LOG_USER supported on Windows
			if (! empty($_SERVER["WINDIR"])) $facility=constant('LOG_USER');

			dol_syslog("admin/syslog: facility ".$facility);
		}
		else
		{
		    $errors[] = $langs->trans("ErrorUnknownSyslogConstant", $facility);
		}

		return $errors;
	}

	/**
	 * Export the message
	 *
	 * @param  	array 	$content 	Array containing the info about the message
	 * @return	void
	 */
	public function export($content)
	{
		global $conf;

		if (! empty($conf->global->MAIN_SYSLOG_DISABLE_SYSLOG)) return;	// Global option to disable output of this handler

		if (! empty($conf->global->SYSLOG_FACILITY))  // Example LOG_USER
		{
			$facility = constant($conf->global->SYSLOG_FACILITY);
		}
		else $facility = constant('LOG_USER');

		// (int) is required to avoid error parameter 3 expected to be long
		openlog('dolibarr', LOG_PID | LOG_PERROR, (int) $facility);
		syslog($content['level'], $content['message']);
		closelog();
	}
}
