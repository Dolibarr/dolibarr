<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 */

/**
 *	\file       htdocs/debugbar/class/DataCollector/DolibarrCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * DolibarrCollector class
 */

class DolibarrCollector extends DataCollector implements Renderable, AssetProvider
{
	/**
	 *	Return collector name
	 *
	 *  @return string     Name
	 */
	public function getName()
	{
		return 'dolibarr';
	}

	/**
	 * Return collected data
	 *
	 * @return array       Array of collected data
	 */
	public function collect()
	{
		return array();
	}

	/**
	 *	Return database info as an HTML string
	 *
	 *  @return string         HTML string
	 */
	protected function getDatabaseInfo()
	{
		global $conf, $langs;

		$info  = $langs->trans('Host').': <strong>'.$conf->db->host.'</strong><br>';
		$info .= $langs->trans('Port').': <strong>'.$conf->db->port.'</strong><br>';
		$info .= $langs->trans('Name').': <strong>'.$conf->db->name.'</strong><br>';
		// @phan-suppress-next-line PhanTypeSuspiciousStringExpression
		$info .= $langs->trans('User').': <strong>'.$conf->db->user.'</strong><br>';
		$info .= $langs->trans('Type').': <strong>'.$conf->db->type.'</strong><br>';
		$info .= $langs->trans('Prefix').': <strong>'.$conf->db->prefix.'</strong><br>';
		$info .= $langs->trans('Charset').': <strong>'.$conf->db->character_set.'</strong>';

		return $info;
	}

	/**
	 *	Return dolibarr info as an HTML string
	 *
	 * @return string      HTML string
	 */
	protected function getDolibarrInfo()
	{
		global $conf, $langs;
		global $dolibarr_main_prod, $dolibarr_nocsrfcheck;

		$info  = $langs->trans('Version').': <strong>'.DOL_VERSION.'</strong><br>';
		$info .= $langs->trans('Theme').': <strong>'.$conf->theme.'</strong><br>';
		$info .= $langs->trans('Locale').': <strong>' . getDolGlobalString('MAIN_LANG_DEFAULT').'</strong><br>';
		$info .= $langs->trans('Currency').': <strong>'.$conf->currency.'</strong><br>';
		$info .= $langs->trans('Entity').': <strong>'.$conf->entity.'</strong><br>';
		$info .= $langs->trans('MaxSizeList').': <strong>'.($conf->liste_limit ?: getDolGlobalString('MAIN_SIZE_LISTE_LIMIT')).'</strong><br>';
		$info .= $langs->trans('MaxSizeForUploadedFiles').': <strong>' . getDolGlobalString('MAIN_UPLOAD_DOC').'</strong><br>';
		$info .= '$dolibarr_main_prod = <strong>'.$dolibarr_main_prod.'</strong><br>';
		$info .= '$dolibarr_nocsrfcheck = <strong>'.$dolibarr_nocsrfcheck.'</strong><br>';
		$info .= 'MAIN_SECURITY_CSRF_WITH_TOKEN = <strong>' . getDolGlobalString('MAIN_SECURITY_CSRF_WITH_TOKEN').'</strong><br>';
		$info .= 'MAIN_FEATURES_LEVEL = <strong>' . getDolGlobalString('MAIN_FEATURES_LEVEL').'</strong><br>';

		return $info;
	}

	/**
	 *	Return mail info as an HTML string
	 *
	 * @return string      HTML string
	 */
	protected function getMailInfo()
	{
		global $conf, $langs;
		global $dolibarr_mailing_limit_sendbyweb, $dolibarr_mailing_limit_sendbycli, $dolibarr_mailing_limit_sendbyday;

		$info  = $langs->trans('Method').': <strong>'.getDolGlobalString("MAIN_MAIL_SENDMODE").'</strong><br>';
		$info .= $langs->trans('Server').': <strong>'.getDolGlobalString("MAIN_MAIL_SMTP_SERVER").'</strong><br>';
		$info .= $langs->trans('Port').': <strong>'.getDolGlobalString("MAIN_MAIL_SMTP_PORT").'</strong><br>';
		$info .= $langs->trans('ID').': <strong>'.getDolGlobalString("MAIN_MAIL_SMTPS_IDT").'</strong><br>';
		$info .= $langs->trans('Pwd').': <strong>'.preg_replace('/./', '*', getDolGlobalString("MAIN_MAIL_SMTPS_PW")).'</strong><br>';
		$info .= $langs->trans('TLS/STARTTLS').': <strong>'.getDolGlobalString("MAIN_MAIL_EMAIL_TLS").'</strong> / <strong>'.getDolGlobalString("MAIN_MAIL_EMAIL_STARTTLS").'</strong><br>';
		$info .= $langs->trans('MAIN_DISABLE_ALL_MAILS').': <strong>'.(!getDolGlobalString('MAIN_DISABLE_ALL_MAILS') ? $langs->trans('No') : $langs->trans('Yes')).'</strong><br>';
		$info .= 'dolibarr_mailing_limit_sendbyweb = <strong>'.$dolibarr_mailing_limit_sendbyweb.'</strong><br>';
		$info .= 'dolibarr_mailing_limit_sendbycli = <strong>'.$dolibarr_mailing_limit_sendbycli.'</strong><br>';
		$info .= 'dolibarr_mailing_limit_sendbyday = <strong>'.$dolibarr_mailing_limit_sendbyday.'</strong><br>';

		return $info;
	}

	/**
	 *	Return widget settings
	 *
	 * 	@return array       Array
	 */
	public function getWidgets()
	{
		return array(
			"database_info" => array(
				"icon" => "database",
				"indicator" => "PhpDebugBar.DebugBar.TooltipIndicator",
				"tooltip" => array(
					"html" => $this->getDatabaseInfo(),
					"class" => "tooltip-wide"
				),
				"map" => "",
				"default" => ""
			),
			"dolibarr_info" => array(
				"icon" => "desktop",
				"indicator" => "PhpDebugBar.DebugBar.TooltipIndicator",
				"tooltip" => array(
					"html" => $this->getDolibarrInfo(),
					"class" => "tooltip-wide"
				),
				"map" => "",
				"default" => ""
			),
			"mail_info" => array(
				"icon" => "envelope",
				"indicator" => "PhpDebugBar.DebugBar.TooltipIndicator",
				"tooltip" => array(
					"html" => $this->getMailInfo(),
					"class" => "tooltip-extra-wide"
				),
				"map" => "",
				"default" => ""
			)
		);
	}

	/**
	 *	Return collector assests
	 *
	 * @return array       Array
	 */
	public function getAssets()
	{
		return array(
			'base_url' => dol_buildpath('/debugbar', 1),
			'js' => 'js/widgets.js',
			'css' => 'css/widgets.css'
		);
	}
}
