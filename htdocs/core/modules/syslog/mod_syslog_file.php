<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to a file
 */
class mod_syslog_file extends LogHandler implements LogHandlerInterface
{
	var $code = 'file';
	var $lastTime = 0;

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
	 * Is the module active ?
	 *
	 * @return int
	 */
	public function isActive()
	{
	    global $conf;
		return empty($conf->global->SYSLOG_DISABLE_LOGHANDLER_FILE)?1:0;    // Set SYSLOG_DISABLE_LOGHANDLER_FILE to 1 to disable this loghandler
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
				'attr' => 'size="60"'
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
		global $langs;

		$errors = array();

		$filename = $this->getFilename();

		if (file_exists($filename) && is_writable($filename))
		{
			dol_syslog('admin/syslog: file '.$filename);
		}
		else $errors[] = $langs->trans("ErrorFailedToOpenFile", $filename);

		return $errors;
	}

	/**
	 * Return the parsed logfile path
	 *
	 * @param	string	$suffixinfilename	When output is a file, append this suffix into default log filename.
	 * @return	string
	 */
	private function getFilename($suffixinfilename='')
	{
	    global $conf;

	    if (empty($conf->global->SYSLOG_FILE)) $tmp=DOL_DATA_ROOT.'/dolibarr.log';
	    else $tmp=str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $conf->global->SYSLOG_FILE);

	    if (! empty($conf->global->SYSLOG_FILE_ONEPERSESSION))	// file depend on session name that is same for all user, not per user value of the session id
	    {
	        $suffixinfilename = '_'.session_name();
	    }

	    return $suffixinfilename?preg_replace('/\.log$/i', $suffixinfilename.'.log', $tmp):$tmp;
	}

	/**
	 * Export the message
	 *
	 * @param  	array 	$content 			Array containing the info about the message
	 * @param	string	$suffixinfilename	When output is a file, append this suffix into default log filename.
	 * @return	void
	 */
	public function export($content, $suffixinfilename='')
	{
		global $conf, $dolibarr_main_prod;

		if (! empty($conf->global->MAIN_SYSLOG_DISABLE_FILE)) return;	// Global option to disable output of this handler

		$logfile = $this->getFilename($suffixinfilename);

		// Test constant SYSLOG_FILE_NO_ERROR (should stay a constant defined with define('SYSLOG_FILE_NO_ERROR',1);
		if (defined('SYSLOG_FILE_NO_ERROR')) $filefd = @fopen($logfile, 'a+');
		else $filefd = fopen($logfile, 'a+');

		if (! $filefd)
		{
			if (! defined('SYSLOG_FILE_NO_ERROR') || ! constant('SYSLOG_FILE_NO_ERROR'))
			{
				// Do not break dolibarr usage if log fails
				//throw new Exception('Failed to open log file '.basename($logfile));
				print 'Failed to open log file '.($dolibarr_main_prod?basename($logfile):$logfile);
			}
		}
		else
		{
			$logLevels = array(
				LOG_EMERG => 'EMERG',
				LOG_ALERT => 'ALERT',
				LOG_CRIT => 'CRIT',
				LOG_ERR => 'ERR',
				LOG_WARNING => 'WARNING',
				LOG_NOTICE => 'NOTICE',
				LOG_INFO => 'INFO',
				LOG_DEBUG => 'DEBUG'
			);

			$delay = "";
			if (!empty($conf->global->MAIN_SYSLOG_SHOW_DELAY))
			{
				$now = microtime(true);
				$delay = " ".sprintf("%05.3f", $this->lastTime != 0 ? $now - $this->lastTime : 0);
				$this->lastTime = $now;
			}

			$message = dol_print_date(time(),"%Y-%m-%d %H:%M:%S").$delay." ".sprintf("%-7s", $logLevels[$content['level']])." ".sprintf("%-15s", $content['ip'])." ".($this->ident>0?str_pad('',$this->ident,' '):'').$content['message'];
			fwrite($filefd, $message."\n");
			fclose($filefd);
			@chmod($logfile, octdec(empty($conf->global->MAIN_UMASK)?'0664':$conf->global->MAIN_UMASK));
		}
	}
}
