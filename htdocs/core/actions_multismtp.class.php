<?php
/* Copyright (C) 2015-2016 Marcos García de La Fuente	<hola@marcosgdf.com>
 * Copyright (C) 2020      Alexandre Spangaro			<aspangaro@open-dsi.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file           htdocs/core/actions_multismtp.inc.php
 *  \brief          Code for actions on user/smtp.php
 */
class ActionsMultismtp
{

	/**
	 * Catches updateSession hook to replace SMTP configuration
	 *
	 * @return int
	 */
	public function updateSession()
	{
		global $conf;

		if (!$conf->global->MULTISMTP_SMTP_ENABLED) {
			return 0;
		}

		//We do not want to replace constants when navigating to admin/mails.php page
		if (strpos('/admin/mails.php', $_SERVER['PHP_SELF']) !== false) {
			return 0;
		}

		global $db, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/multismtp.class.php';

		if (!replaceConfiguration($db, $user, $conf)) {
			global $langs;

			$langs->load('errors');

			setEventMessage($langs->trans('ErrorSMTPInjectionError'), 'errors');
		}

		return 1;
	}
}
