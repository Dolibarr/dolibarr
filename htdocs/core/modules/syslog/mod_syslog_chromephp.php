<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

class mod_syslog_chromephp extends LogHandler implements LogHandlerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'ChromePHP';
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

		return $this->isActive()?'':$langs->trans('ClassNotFoundIntoPathWarning','ChromePhp.class.php');
	}

	/**
	 * {@inheritDoc}
	 */
	public function isActive()
	{
		global $conf;
		try
		{
			if (empty($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH)) $conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH='/usr/share/php';
			set_include_path($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH);
		    $res = @include_once 'ChromePhp.class.php';
		    restore_include_path();
		    if ($res)
		    {
		        return 1;
		    }
		}
		catch(Exception $e)
		{
		    print '<!-- ChromePHP not available into PHP -->'."\n";
		}

		return -1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function configure()
	{
		global $langs;

		return array(
			array(
				'name' => $langs->trans('IncludePath'),
				'constant' => 'SYSLOG_CHROMEPHP_INCLUDEPATH',
				'default' => '/usr/share/php',
				'attr' => 'size="40"'
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

		$oldinclude = get_include_path();
		set_include_path(SYSLOG_CHROMEPHP_INCLUDEPATH);

		if (!file_exists('ChromePhp.class.php'))
		{
			$errors[] = $langs->trans("ErrorFailedToOpenFile", 'ChromePhp.class.php');
		}

		set_include_path($oldinclude);

		return $errors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function export($content)
	{
		//We check the configuration to avoid showing PHP warnings
		if (count($this->checkConfiguration())) return false;

		try
		{
			// Warning ChromePHP must be into PHP include path. It is not possible to use into require_once() a constant from
			// database or config file because we must be able to log data before database or config file read.
			$oldinclude=get_include_path();
			set_include_path(SYSLOG_CHROMEPHP_INCLUDEPATH);
			include_once 'ChromePhp.class.php';
			set_include_path($oldinclude);
			ob_start();	// To be sure headers are not flushed until all page is completely processed
			if ($level == LOG_ERR) ChromePhp::error($message);
			elseif ($level == LOG_WARNING) ChromePhp::warn($message);
			elseif ($level == LOG_INFO) ChromePhp::log($message);
			else ChromePhp::log($message);
		}
		catch (Exception $e)
		{
			// Do not use dol_syslog here to avoid infinite loop
		}
	}
}