<?php
/* Copyright (C) 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 * Copyright (C) 2020      Alexandre Spangaro         <aspangaro@open-dsi.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/multismtp.lib.php
 *	\ingroup	multismtp
 *	\brief		Basic set of functions for the multismtp module
 *
 */

/**
 * Replaces Dolibarr email configuration with the provided one
 *
 * @param DoliDB $db Database handler
 * @param User $user Logged user
 * @param Conf $conf Dolibarr configuration
 * @return bool
 * @throws Exception It will be logged to Syslog
 */
function replaceConfiguration(DoliDB $db, User $user, Conf $conf)
{
	require_once DOL_DOCUMENT_ROOT."/core/class/multismtp.class.php";

	$multismtp = new Multismtp($db, $conf);

	try {
		if ($multismtp->fetch($user) && $multismtp->checkSmtpConfig()) {
			$credentials = $multismtp->getSmtpCredentials();

			$conf->global->MAIN_MAIL_SMTP_SERVER = $credentials['server'];
			$conf->global->MAIN_MAIL_SMTP_PORT = $credentials['port'];
			$conf->global->MAIN_MAIL_EMAIL_TLS = $credentials['tls'];
			$conf->global->MAIN_MAIL_EMAIL_STARTTLS = $credentials['starttls'];
			$conf->global->MAIN_MAIL_SMTPS_ID = $credentials['id'];
			$conf->global->MAIN_MAIL_SMTPS_PW = $credentials['pw'];
		}
	} catch (Exception $e) {
		dol_syslog('[multismtp] '.$e->getMessage(), LOG_ERR);
		return false;
	}

	return true;
}

/**
 * Checks if the IMAP function is enabled
 *
 * @param Conf $conf Dolibarr configuration
 * @return bool
 */
function imapEnabled(Conf $conf)
{
	return function_exists('imap_open') && $conf->global->MULTISMTP_IMAP_ENABLED;
}
