<?php
/* Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to syslog
 */
class mod_syslog_syslog extends LogHandler
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
	 * Is the logger active ?
	 *
	 * @return int<0,1>		1 if logger enabled
	 */
	public function isActive()
	{
		// This function does not exists on some ISP (Ex: Free in France)
		if (!function_exists('openlog')) {
			return 0;
		}

		return !getDolGlobalString('SYSLOG_DISABLE_LOGHANDLER_SYSLOG') ? 1 : 0; // Set SYSLOG_DISABLE_LOGHANDLER_SYSLOG to 1 to disable this loghandler
	}

	/**
	 * 	Return array of configuration data
	 *
	 * 	@return	array<array{name:string,constant:string,default:string,css?:string}>	Return array of configuration data
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
	 * 	@return	bool		True if ok.
	 */
	public function checkConfiguration()
	{
		global $langs;

		$facility = constant(getDolGlobalString('SYSLOG_FACILITY'));

		if ($facility) {
			// Only LOG_USER supported on Windows
			if (!empty($_SERVER["WINDIR"])) {
				$facility = constant('LOG_USER');
			}

			dol_syslog("admin/syslog: facility ".$facility);
			return true;
		} else {
			$this->errors[] = $langs->trans("ErrorUnknownSyslogConstant", getDolGlobalString('SYSLOG_FACILITY'));
			return false;
		}
	}

	/**
	 * Export the message
	 *
	 * @param	array{level:int,ip:string,ospid:string,osuser:string,message:string}	$content 	Array containing the info about the message
	 * @param   string  $suffixinfilename   When output is a file, append this suffix into default log filename.
	 * @return  void
	 */
	public function export($content, $suffixinfilename = '')
	{
		global $conf;

		if (getDolGlobalString('MAIN_SYSLOG_DISABLE_SYSLOG')) {
			return; // Global option to disable output of this handler
		}

		if (getDolGlobalString('SYSLOG_FACILITY')) {  // Example LOG_USER
			$facility = constant($conf->global->SYSLOG_FACILITY);
		} else {
			$facility = constant('LOG_USER');
		}

		// (int) is required to avoid error parameter 3 expected to be long
		openlog('dolibarr', LOG_PID | LOG_PERROR, (int) $facility);

		$message = sprintf("%6s", dol_trunc($content['osuser'], 6, 'right', 'UTF-8', 1));
		$message .= " ".$content['message'];

		syslog($content['level'], $message);
		closelog();
	}
}
