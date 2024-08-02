<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to a file
 */
class mod_syslog_file extends LogHandler
{
	public $code = 'file';
	public $lastTime = 0;

	/**
	 * 	Return name of logger
	 *
	 * 	@return	string		Name of logger
	 */
	public function getName()
	{
		global $langs;

		return $langs->trans('File');
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

		return $langs->trans('YouCanUseDOL_DATA_ROOT');
	}

	/**
	 * Is the logger active ?
	 *
	 * @return int		1 if logger enabled
	 */
	public function isActive()
	{
		return !getDolGlobalString('SYSLOG_DISABLE_LOGHANDLER_FILE') ? 1 : 0; // Set SYSLOG_DISABLE_LOGHANDLER_FILE to 1 to disable this loghandler
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
				'name' => $langs->trans('SyslogFilename'),
				'constant' => 'SYSLOG_FILE',
				'default' => 'DOL_DATA_ROOT/dolibarr.log',
				'css' => 'minwidth300 maxwidth500'
			)
		);
	}

	/**
	 * 	Return if configuration is valid
	 *
	 * 	@return	bool		true if ok
	 */
	public function checkConfiguration()
	{
		global $langs;

		$filename = $this->getFilename();

		if (file_exists($filename) && is_writable($filename)) {
			dol_syslog('admin/syslog: file '.$filename);
			return true;
		} else {
			$this->errors[] = $langs->trans("ErrorFailedToOpenFile", $filename);
			return false;
		}
	}

	/**
	 * Return the parsed logfile path
	 *
	 * @param	string	$suffixinfilename	When output is a file, append this suffix into default log filename.
	 * @return	string
	 */
	private function getFilename($suffixinfilename = '')
	{
		global $conf;

		if (!getDolGlobalString('SYSLOG_FILE')) {
			$tmp = DOL_DATA_ROOT.'/dolibarr.log';
		} else {
			$tmp = str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $conf->global->SYSLOG_FILE);
		}

		if (getDolGlobalString('SYSLOG_FILE_ONEPERSESSION')) {
			if (is_numeric(getDolGlobalString('SYSLOG_FILE_ONEPERSESSION'))) {
				if (getDolGlobalInt('SYSLOG_FILE_ONEPERSESSION') == 1) {	// file depend on instance session key name (Note that session name is same for the instance so for all users and is not a per user value)
					$suffixinfilename .= '_'.session_name();
				}
				if (getDolGlobalInt('SYSLOG_FILE_ONEPERSESSION') == 2) {	// file depend on instance session key name + ip so nearly per user
					$suffixinfilename .= '_'.session_name().'_'.$_SERVER["REMOTE_ADDR"];
				}
			} else {
				$suffixinfilename .= '_' . getDolGlobalString('SYSLOG_FILE_ONEPERSESSION');
			}
		}

		return $suffixinfilename ? preg_replace('/\.log$/i', $suffixinfilename.'.log', $tmp) : $tmp;
	}

	/**
	 * Export the message
	 *
	 * @param  	array 	$content 			Array containing the info about the message
	 * @param	string	$suffixinfilename	When output is a file, append this suffix into default log filename.
	 * @return	void
	 * @phan-suppress PhanPluginDuplicateArrayKey
	 */
	public function export($content, $suffixinfilename = '')
	{
		if (getDolGlobalString('MAIN_SYSLOG_DISABLE_FILE')) {
			return; // Global option to disable output of this handler
		}

		$logfile = $this->getFilename($suffixinfilename);

		// Test constant SYSLOG_FILE_NO_ERROR (should stay a constant defined with define('SYSLOG_FILE_NO_ERROR',1);
		if (defined('SYSLOG_FILE_NO_ERROR')) {
			$filefd = @fopen($logfile, 'a+');
		} else {
			$filefd = fopen($logfile, 'a+');
		}

		if (!$filefd) {
			if (!defined('SYSLOG_FILE_NO_ERROR') || !constant('SYSLOG_FILE_NO_ERROR')) {
				global $dolibarr_main_prod;
				// Do not break dolibarr usage if log fails
				//throw new Exception('Failed to open log file '.basename($logfile));
				print 'Failed to open log file '.($dolibarr_main_prod ? basename($logfile) : $logfile);
			}
		} else {
			$logLevels = array(
				LOG_EMERG => 'EMERG',
				LOG_ALERT => 'ALERT',
				LOG_CRIT => 'CRIT',
				LOG_ERR => 'ERR',
				LOG_WARNING => 'WARNING',
				LOG_NOTICE => 'NOTICE',
				LOG_INFO => 'INFO',
			) + array(
				LOG_DEBUG => 'DEBUG'
			);

			$delay = "";
			if (getDolGlobalString('MAIN_SYSLOG_SHOW_DELAY')) {
				$now = microtime(true);
				$delay = " ".sprintf("%05.3f", $this->lastTime != 0 ? $now - $this->lastTime : 0);
				$this->lastTime = $now;
			}

			// @phan-suppress-next-line PhanParamSuspiciousOrder
			$message = dol_print_date(dol_now('gmt'), 'standard', 'gmt').$delay." ".sprintf("%-7s", $logLevels[$content['level']])." ".sprintf("%-15s", $content['ip']);
			$message .= " ".sprintf("%7s", dol_trunc($content['ospid'], 7, 'right', 'UTF-8', 1));
			$message .= " ".sprintf("%6s", dol_trunc($content['osuser'], 6, 'right', 'UTF-8', 1));
			$message .= " ".($this->ident > 0 ? str_pad(((string) ''), ((int) $this->ident), ((string) ' ')) : '').$content['message'];
			fwrite($filefd, $message."\n");
			fclose($filefd);
			dolChmod($logfile);
		}
	}
}
