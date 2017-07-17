<?php
/* Copyright (C) 2012   Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015   Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandler.php';

/**
 * Class to manage logging to a FirePHP
 */
class mod_syslog_firephp extends LogHandler implements LogHandlerInterface
{
	public $code = 'firephp';
	private static $firephp_include_path = '/includes/firephp/firephp-core/lib/';
	private static $firephp_class_path = 'FirePHPCore/FirePHP.class.php';

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

		return ($this->isActive() == 1)?'':$langs->trans('ClassNotFoundIntoPathWarning', self::$firephp_class_path);
	}

	/**
	 * Is the module active ?
	 *
	 * @return int
	 */
	public function isActive()
	{
		global $conf;
		try
		{
			if (empty($conf->global->SYSLOG_FIREPHP_INCLUDEPATH)) {
				$conf->global->SYSLOG_FIREPHP_INCLUDEPATH = DOL_DOCUMENT_ROOT . self::$firephp_include_path;
			}
			set_include_path($conf->global->SYSLOG_FIREPHP_INCLUDEPATH);
			$res = @include_once self::$firephp_class_path;
			restore_include_path();
			if ($res) {
        		return empty($conf->global->SYSLOG_DISABLE_LOGHANDLER_FIREPHP)?1:0;    // Set SYSLOG_DISABLE_LOGHANDLER_FIREPHP to 1 to disable this loghandler
			} else {
				return 0;
			}
		}
		catch(Exception $e)
		{
			print '<!-- FirePHP not available into PHP -->'."\n";
		}

		return -1;
	}

	/**
	 * Return array of configuration data
	 *
	 * @return array Return array of configuration data
	 */
	public function configure()
	{
		global $langs;

		return array(
			array(
				'name' => $langs->trans('IncludePath', 'SYSLOG_FIREPHP_INCLUDEPATH'),
				'constant' => 'SYSLOG_FIREPHP_INCLUDEPATH',
				'default' => DOL_DOCUMENT_ROOT . self::$firephp_include_path,
				'attr' => 'size="60"',
				'example' => '/usr/share/php, ' . DOL_DOCUMENT_ROOT . self::$firephp_include_path
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
		global $conf, $langs;

		$errors = array();

		if (!file_exists($conf->global->SYSLOG_FIREPHP_INCLUDEPATH . self::$firephp_class_path))
		{
			$errors[] = $langs->trans("ErrorFailedToOpenFile", self::$firephp_class_path);
		}

		return $errors;
	}

	/**
	 * 	Output log content
	 *
	 *	@param	array	$content	Content to log
	 * 	@return	null|false
	 */
	public function export($content)
	{
		global $conf;

		if (! empty($conf->global->MAIN_SYSLOG_DISABLE_FIREPHP)) return;	// Global option to disable output of this handler

		//We check the configuration to avoid showing PHP warnings
		if (count($this->checkConfiguration())) return false;

		try
		{
			// Warning FirePHPCore must be into PHP include path. It is not possible to use into require_once() a constant from
			// database or config file because we must be able to log data before database or config file read.
			$oldinclude=get_include_path();
			set_include_path($conf->global->SYSLOG_FIREPHP_INCLUDEPATH);
			include_once self::$firephp_class_path;
			set_include_path($oldinclude);
			ob_start();	// To be sure headers are not flushed until all page is completely processed
			$firephp = FirePHP::getInstance(true);
			if ($content['level'] == LOG_ERR) $firephp->error($content['message']);
			elseif ($content['level'] == LOG_WARNING) $firephp->warn($content['message']);
			elseif ($content['level'] == LOG_INFO) $firephp->log($content['message']);
			else $firephp->log($content['message']);
		}
		catch (Exception $e)
		{
			// Do not use dol_syslog here to avoid infinite loop
			return false;
		}
	}
}
