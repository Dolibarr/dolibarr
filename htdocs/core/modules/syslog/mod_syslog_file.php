<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

class mod_syslog_file extends LogHandler implements LogHandlerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		global $langs;

		return $langs->trans('File');
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

		return $langs->trans('YouCanUseDOL_DATA_ROOT');
	}

	/**
	 * {@inheritDoc}
	 */
	public function isActive()
	{
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
				'name' => $langs->trans('SyslogFilename'),
				'constant' => 'SYSLOG_FILE',
				'default' => 'DOL_DATA_ROOT/dolibarr.log',
				'attr' => 'size="60"'
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
	 * @return string
	 */
	private function getFilename()
	{
		return str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, SYSLOG_FILE);
	}

	/**
	 * Export the message
	 * @param  array $content Array containing the info about the message
	 */
	public function export($content)
	{
		$logfile = $this->getFilename();

		if (defined("SYSLOG_FILE_NO_ERROR")) $filefd = @fopen($logfile, 'a+');
		else $filefd = fopen($logfile, 'a+');

		if (!$filefd && ! defined("SYSLOG_FILE_NO_ERROR"))
		{
			throw new Exception('Failed to open log file '.$logfile);
		}

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

		$message = dol_print_date(time(),"%Y-%m-%d %H:%M:%S")." ".sprintf("%-5s", $logLevels[$content['level']])." ".sprintf("%-15s", $content['ip'])." ".$content['message'];

		fwrite($filefd, $message."\n");
		fclose($filefd);
	}
}