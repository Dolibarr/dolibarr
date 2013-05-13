<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to a file
 */
class mod_syslog_file extends LogHandler implements LogHandlerInterface
{

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
	 * @return boolean
	 */
	public function isActive()
	{
		return 1;
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
	 * 	@return	boolean		True if configuration ok
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
		$tmp=str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, SYSLOG_FILE);
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
		global $conf;

		$logfile = $this->getFilename($suffixinfilename);

		if (defined("SYSLOG_FILE_NO_ERROR")) $filefd = @fopen($logfile, 'a+');
		else $filefd = fopen($logfile, 'a+');

		if (! $filefd)
		{
			if (! defined("SYSLOG_FILE_NO_ERROR"))
			{
				// Do not break dolibarr usage if log fails
				//throw new Exception('Failed to open log file '.basename($logfile));
				print 'Failed to open log file '.basename($logfile);
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

			$message = dol_print_date(time(),"%Y-%m-%d %H:%M:%S")." ".sprintf("%-7s", $logLevels[$content['level']])." ".sprintf("%-15s", $content['ip'])." ".($this->ident>0?str_pad('',$this->ident,' '):'').$content['message'];

			fwrite($filefd, $message."\n");
			fclose($filefd);
			@chmod($logfile, octdec(empty($conf->global->MAIN_UMASK)?'0664':$conf->global->MAIN_UMASK));
		}
	}
}
