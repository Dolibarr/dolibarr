<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to ChromPHP
 */
class mod_syslog_chromephp extends LogHandler implements LogHandlerInterface
{
	var $code = 'chromephp';

	/**
	 * 	Return name of logger
	 *
	 * 	@return	string		Name of logger
	 */
	public function getName()
	{
		return 'ChromePHP';
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
	 * @return string
	 */
	public function getInfo()
	{
		return '';
	}

	/**
	 * Return warning if something is wrong with logger
	 *
	 * @return string
	 */
	public function getWarning()
	{
		global $langs;

		return ($this->isActive() == 1)?'':$langs->trans('ClassNotFoundIntoPathWarning','ChromePhp.class.php');
	}
	
	/**
	 * Is the module active ?
	 *
	 * @return int         -1 if not active, 0 if active but lib/path not found, 1 if OK
	 */
	public function isActive()
	{
		global $conf;
		try
		{
			if (empty($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH)) {
				$conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH = DOL_DOCUMENT_ROOT . '/includes/ccampbell/chromephp/';
			}
			set_include_path($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH);

		    $res = @include_once('ChromePhp.php');
		    if (! $res) $res=@include_once('ChromePhp.class.php');

		    restore_include_path();

		    if ($res)
		    {
        		return empty($conf->global->SYSLOG_DISABLE_LOGHANDLER_CHROMEPHP)?1:0;    // Set SYSLOG_DISABLE_LOGHANDLER_CHROMEPHP to 1 to disable this loghandler
		    }
		    else
		    {
		        return 0;
		    }
		}
		catch(Exception $e)
		{
		    print '<!-- ChromePHP not available into PHP -->'."\n";
		}

		return -1;
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
				'name' => $langs->trans('IncludePath','SYSLOG_CHROMEPHP_INCLUDEPATH'),
				'constant' => 'SYSLOG_CHROMEPHP_INCLUDEPATH',
				'default' => DOL_DOCUMENT_ROOT . '/includes/ccampbell/chromephp/',
				'attr' => 'size="60"',
			    'example' =>'/usr/share/php, '.DOL_DOCUMENT_ROOT . '/includes/ccampbell/chromephp/'
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
		global $langs,$conf;

		$errors = array();

		if (! file_exists($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH.'/ChromePhp.php') && ! file_exists($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH.'/ChromePhp.class.php'))
		{
			if (is_object($langs))   // $langs may not be defined yet.
			{
			    $errors[] = $langs->trans("ErrorFailedToOpenFile", 'ChromePhp.class.php or ChromePhp.php');
			}
			else
			{
		        $errors[] = "ErrorFailedToOpenFile ChromePhp.class.php or ChromePhp.php";
			}
		}

		return $errors;
	}

	/**
	 * 	Output log content. We also start output buffering at first log write.
	 *
	 *	@param	array	$content	Content to log
	 * 	@return	null|false
	 */
	public function export($content)
	{
		global $conf;

		if (! empty($conf->global->MAIN_SYSLOG_DISABLE_CHROMEPHP)) return;	// Global option to disable output of this handler

		//We check the configuration to avoid showing PHP warnings
		if (count($this->checkConfiguration()) > 0) return false;

		try
		{
			// Warning ChromePHP must be into PHP include path. It is not possible to use into require_once() a constant from
			// database or config file because we must be able to log data before database or config file read.
			$oldinclude=get_include_path();
			set_include_path($conf->global->SYSLOG_CHROMEPHP_INCLUDEPATH);
		    $res = @include_once('ChromePhp.php');
		    if (! $res) $res=@include_once('ChromePhp.class.php');
			set_include_path($oldinclude);
			
			ob_start();	// To be sure headers are not flushed until all page is completely processed
			if ($content['level'] == LOG_ERR) ChromePhp::error($content['message']);
			elseif ($content['level'] == LOG_WARNING) ChromePhp::warn($content['message']);
			elseif ($content['level'] == LOG_INFO) ChromePhp::log($content['message']);
			else ChromePhp::log($content['message']);
		}
		catch (Exception $e)
		{
			// Do not use dol_syslog here to avoid infinite loop
		}
	}
}
