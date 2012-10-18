<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

class mod_syslog_syslog extends LogHandler implements LogHandlerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'Syslog';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion()
	{
		return self::STABLE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInfo()
	{
		global $langs;

		return $langs->trans('OnlyWindowsLOG_USER');
	}

	/**
	 * {@inheritDoc}
	 */
	public function isActive()
	{
		// This function does not exists on some ISP (Ex: Free in France)
		if (!function_exists('openlog'))
		{
			return 0;
		}

		return 1;
	}

	/**
	 * {@inheritDoc}
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
	 * {@inheritDoc}
	 */
	public function checkConfiguration()
	{
		global $langs;

		$errors = array();

	    $facility = SYSLOG_FACILITY;
	    if ($facility)
		{
			// Only LOG_USER supported on Windows
			if (! empty($_SERVER["WINDIR"])) $facility='LOG_USER';

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
	 * @param  array $content Array containing the info about the message
	 */
	public function export($content)
	{
		if (defined("SYSLOG_FACILITY") && constant("SYSLOG_FACILITY"))
		{
			if (constant(constant('SYSLOG_FACILITY')))
			{
				$facility = constant(constant("SYSLOG_FACILITY"));
			}
			else $facility = LOG_USER;
		}
		else $facility = LOG_USER;

		// (int) is required to avoid error parameter 3 expected to be long
		openlog('dolibarr', LOG_PID | LOG_PERROR, (int) $facility);
		syslog($content['level'], $content['message']);
		closelog();
	}
}