<?php

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DebugBarException;

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
	 *	Return collected data
	 *
	 * @return array       Array
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
		$info .= $langs->trans('Locale').': <strong>'.$conf->global->MAIN_LANG_DEFAULT.'</strong><br>';
		$info .= $langs->trans('Currency').': <strong>'.$conf->currency.'</strong><br>';
		$info .= $langs->trans('Entity').': <strong>'.$conf->entity.'</strong><br>';
		$info .= $langs->trans('MaxSizeList').': <strong>'.($conf->liste_limit ?: $conf->global->MAIN_SIZE_LISTE_LIMIT).'</strong><br>';
		$info .= $langs->trans('MaxSizeForUploadedFiles').': <strong>'.$conf->global->MAIN_UPLOAD_DOC.'</strong><br>';
		$info .= '$dolibarr_main_prod = <strong>'.$dolibarr_main_prod.'</strong><br>';
		$info .= '$dolibarr_nocsrfcheck = <strong>'.$dolibarr_nocsrfcheck.'</strong><br>';
		$info .= 'MAIN_SECURITY_CSRF_WITH_TOKEN = <strong>'.$conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN.'</strong><br>';
		$info .= 'MAIN_FEATURES_LEVEL = <strong>'.$conf->global->MAIN_FEATURES_LEVEL.'</strong><br>';

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
		global $dolibarr_mailing_limit_sendbyweb;

		$info  = $langs->trans('Method').': <strong>'.$conf->global->MAIN_MAIL_SENDMODE.'</strong><br>';
		$info .= $langs->trans('Server').': <strong>'.$conf->global->MAIN_MAIL_SMTP_SERVER.'</strong><br>';
		$info .= $langs->trans('Port').': <strong>'.$conf->global->MAIN_MAIL_SMTP_PORT.'</strong><br>';
		$info .= $langs->trans('ID').': <strong>'.$conf->global->MAIN_MAIL_SMTPS_ID.'</strong><br>';
		$info .= $langs->trans('Pwd').': <strong>'.preg_replace('/./', '*', $conf->global->MAIN_MAIL_SMTPS_PW).'</strong><br>';
		$info .= $langs->trans('TLS/STARTTLS').': <strong>'.(empty($conf->global->MAIN_MAIL_EMAIL_TLS) ? '' : $conf->global->MAIN_MAIL_EMAIL_TLS).'</strong> / <strong>'.(empty($conf->global->MAIN_MAIL_EMAIL_STARTTLS) ? '' : $conf->global->MAIN_MAIL_EMAIL_STARTTLS).'</strong><br>';
		$info .= $langs->trans('MAIN_DISABLE_ALL_MAILS').': <strong>'.(empty($conf->global->MAIN_DISABLE_ALL_MAILS) ? $langs->trans('No') : $langs->trans('Yes')).'</strong><br>';
		$info .= 'dolibarr_mailing_limit_sendbyweb = <strong>'.$dolibarr_mailing_limit_sendbyweb.'</strong><br>';

		return $info;
	}

	/**
	 *	Return widget settings
	 *
	 * @return array       Array
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
			'js' => 'js/widgets.js'
		);
	}
}
