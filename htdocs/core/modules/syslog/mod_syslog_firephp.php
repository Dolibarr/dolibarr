<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to a FirePHP
 */
class mod_syslog_firephp extends LogHandler implements LogHandlerInterface
{
	/**
	 * 	Return name of logger
	 *
	 * 	@return	string		Name of logger
	 */
	public function getName()
	{
		return 'FirePHP';
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

		return $this->isActive()?'':$langs->trans('ClassNotFoundIntoPathWarning','FirePHPCore/FirePHP.class.php');
	}

	/**
	 * Is the module active ?
	 *
	 * @return boolean
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

	///**
	// * 	Return array of configuration data
	// *
	// * 	@return	array		Return array of configuration data
	// */
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
	 * 	Return if configuration is valid
	 *
	 * 	@return	boolean		True if configuration ok
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
	 * 	Output log content
	 *
	 *	@param	string	$content	Content to log
	 * 	@return	void
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