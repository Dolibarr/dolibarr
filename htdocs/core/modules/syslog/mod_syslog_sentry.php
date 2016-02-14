<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
if (file_exists(DOL_DOCUMENT_ROOT.'/includes/raven/raven/lib/Raven/Autoloader.php'))
{
    require_once DOL_DOCUMENT_ROOT.'/includes/raven/raven/lib/Raven/Autoloader.php';
    Raven_Autoloader::register();
}

/**
 * Class to manage logging to Sentry
 *
 * @see https://docs.getsentry.com/on-premise/clients/php/
 */
class mod_syslog_sentry extends LogHandler implements LogHandlerInterface
{
	/**
	 * @var string Log handler code
	 */
	public $code = 'sentry';

	/**
	 * Return name of logger
	 *
	 * @return string Name of logger
	 */
	public function getName()
	{
		return 'Sentry';
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
		return $langs->trans('SyslogSentryFromProject');
	}

	/**
	 * Is the module active ?
	 *
	 * @return int
	 */
	public function isActive()
	{
		return file_exists(DOL_DOCUMENT_ROOT.'/includes/raven/raven/lib/Raven/Autoloader.php');
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
				'constant' => 'SYSLOG_SENTRY_DSN',
				'name' => $langs->trans('SyslogSentryDSN'),
				'default' => '',
				'attr' => 'size="100" placeholder="https://<key>:<secret>@app.getsentry.com/<project>"'
			)
		);
	}

	/**
	 * Return if configuration is valid
	 *
	 * @return array Array of errors. Empty array if ok.
	 */
	public function checkConfiguration()
	{
		global $conf;

		$errors = array();

		$dsn = $conf->global->SYSLOG_SENTRY_DSN;

		try {
			$client = new Raven_Client(
				$dsn,
				array('curl_method' => 'sync')
			);
		} catch (InvalidArgumentException $ex) {
			$errors[] = "ERROR: There was an error parsing your DSN:\n  " . $ex->getMessage();
		}

		if (!$errors) {
			// Send test event and check for errors
			$client->captureMessage('TEST: Sentry syslog configuration check', null, Raven_Client::DEBUG);
			$last_error = $client->getLastError();
			if ($last_error) {
				$errors[] = $last_error;
			}
		}

		if (!$errors) {
			// Install handlers
			$error_handler = new Raven_ErrorHandler($client);
			$error_handler->registerExceptionHandler();
			$error_handler->registerErrorHandler();
			$error_handler->registerShutdownFunction();
		}

		return $errors;
	}

	/**
	 * Export the message
	 *
	 * @param array $content Array containing the info about the message
	 * @return void
	 */
	public function export($content)
	{
		global $conf;
		$dsn = $conf->global->SYSLOG_SENTRY_DSN;
		$client = new Raven_Client(
			$dsn,
			array('curl_method' => 'exec')
		);

		$client->user_context(array(
			'username' => ($content['user'] ? $content['user'] : ''),
			'ip_address' => $content['ip']
		));

		$client->tags_context(array(
			'version' => DOL_VERSION
		));

		$client->registerSeverityMap(array(
			LOG_EMERG => Raven_Client::FATAL,
			LOG_ALERT => Raven_Client::FATAL,
			LOG_CRIT => Raven_Client::ERROR,
			LOG_ERR => Raven_Client::ERROR,
			LOG_WARNING => Raven_Client::WARNING,
			LOG_NOTICE => Raven_Client::WARNING,
			LOG_INFO => Raven_Client::INFO,
			LOG_DEBUG => Raven_Client::DEBUG,
		));

		if (substr($content['message'], 0, 3) === 'sql') {
			global $db;
			$query = substr($content['message'], 4, strlen($content['message']));
			$client->captureQuery(
				$query,
				$client->translateSeverity($content['level']),
				$db->type
			);
		} else {
			$client->captureMessage(
				$content['message'],
				null,
				$client->translateSeverity($content['level'])
			);
		}
	}
}
