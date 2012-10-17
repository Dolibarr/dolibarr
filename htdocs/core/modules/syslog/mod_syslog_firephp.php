<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

class mod_syslog_firephp extends LogHandler implements LogHandlerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'FirePHP';
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

		return $this->isActive()?'':$langs->trans('ClassNotFoundIntoPathWarning','FirePHPCore/FirePHP.class.php');
	}

	/**
	 * {@inheritDoc}
	 */
	public function isActive()
	{
		try
		{
		    set_include_path('/usr/share/php/');
		    $res = @include_once 'FirePHPCore/FirePHP.class.php';
		    restore_include_path();
		    if ($res)
		    {
		        return 1;
		    }
		}
		catch(Exception $e)
		{
		    print '<!-- FirePHP not available into PHP -->'."\n";
		}

		return -1;
	}

	// /**
	//  * {@inheritDoc}
	//  */
	// public function configure()
	// {
	// 	global $langs;

	// 	return array(
	// 		array(
	// 			'name' => $langs->trans('IncludePath'),
	// 			'constant' => 'SYSLOG_FIREPHP_INCLUDEPATH',
	// 			'default' => '/usr/share/php',
	// 			'attr' => 'size="40"'
	// 		)
	// 	);
	// }

	/**
	 * {@inheritDoc}
	 */
	public function checkConfiguration()
	{
		global $langs;

		$errors = array();

		$oldinclude = get_include_path();
		set_include_path('/usr/share/php/');

		if (!file_exists('FirePHPCore/FirePHP.class.php'))
		{
			$errors[] = $langs->trans("ErrorFailedToOpenFile", 'FirePhp.php');
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
			// Warning FirePHPCore must be into PHP include path. It is not possible to use into require_once() a constant from
			// database or config file because we must be able to log data before database or config file read.
			$oldinclude=get_include_path();
			set_include_path('/usr/share/php/');
			include_once 'FirePHPCore/FirePHP.class.php';
			set_include_path($oldinclude);
			ob_start();	// To be sure headers are not flushed until all page is completely processed
			$firephp = FirePHP::getInstance(true);
			if ($level == LOG_ERR) $firephp->error($message);
			elseif ($level == LOG_WARNING) $firephp->warn($message);
			elseif ($level == LOG_INFO) $firephp->log($message);
			else $firephp->log($message);
		}
		catch (Exception $e)
		{
			// Do not use dol_syslog here to avoid infinite loop
			return false;
		}
	}
}