<?php
/* Copyright (C) 2021	Regis Houssin	<regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/class/html.formldap.class.php
 *       \ingroup    core
 *       \brief      File of class with ldap html predefined components
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

/**
 *      Class to manage generation of HTML components for ldap module
 */
class FormLdap
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $form;

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		$langs->loadLangs(array("admin", "ldap"));

		$this->db = $db;
	}

	/**
	 *  Return list of types of hash
	 *
	 *  @param	string		$selected		Preselected type
	 *	@param  string		$htmlname		Name of field in form
	 * 	@param	int			$showempty		Add an empty field
	 *  @return	string						HTML select string
	 */
	public function selectLdapPasswordHashType($selected = 'md5', $htmlname = 'ldaphashtype', $showempty = 0)
	{
		global $form;

		if (empty($selected)) {
			$selected = 'md5';
		}
		if (empty($htmlname)) {
			$htmlname = 'ldaphashtype';
		}

		$arraylist = array(
			//"pbkdf2sha256"	=> "PBKDF2_SHA256",
			"ssha512"		=> "SSHA-512",
			"ssha384"		=> "SSHA-384",
			"ssha256"		=> "SSHA-256",
			"ssha" 			=> "SSHA",
			"sha512"		=> "SHA-512",
			"sha384"		=> "SHA-384",
			"sha256"		=> "SHA-256",
			"sha"			=> "SHA",
			"md5"			=> "MD5",
			"smd5"			=> "SMD5",
			//"cryptmd5"	=> "CRYPT-MD5",
			//"cryptsha512"	=> "CRYPT-SHA512",
			//"cryptsha384"	=> "CRYPT-SHA384",
			//"cryptsha256"	=> "CRYPT-SHA256",
			"crypt"			=> "CRYPT",
			"clear"			=> "CLEAR"
		);

		return $form->selectarray($htmlname, $arraylist, $selected, $showempty);
	}

	/**
	 *	Return list of type of synchronization
	 *
	 *	@param	int			$selected		Preselected type
	 *	@param  string		$htmlname		Name of field in form
	 *	@param	array		$exclude		Exclude values from the list
	 *	@param	int			$scriptonly		Add warning if synchro only work with a script (0 = disable, 1 = Dolibarr2ldap, 2 = ldap2dolibarr, 3 = all)
	 * 	@param	int			$showempty		Add an empty field
	 *  @return	string						HTML select string
	 */
	public function selectLdapDnSynchroActive($selected = 0, $htmlname = 'activesynchro', $exclude = array(), $scriptonly = 0, $showempty = 0)
	{
		global $langs, $form;

		if (empty($selected)) {
			$selected = Ldap::SYNCHRO_NONE;
		}
		if (empty($htmlname)) {
			$htmlname = 'activesynchro';
		}

		$dolibarr2ldaplabel = $langs->trans("DolibarrToLDAP") . (($scriptonly == 1 || $scriptonly == 3) ? " (".$langs->trans("SupportedForLDAPExportScriptOnly").")" : "");
		$ldap2dolibarrlabel = $langs->trans("LDAPToDolibarr") . (($scriptonly == 2 || $scriptonly == 3) ? " (".$langs->trans("SupportedForLDAPImportScriptOnly").")" : "");

		$arraylist = array(
			Ldap::SYNCHRO_NONE				=> $langs->trans("No"),
			Ldap::SYNCHRO_DOLIBARR_TO_LDAP	=> $dolibarr2ldaplabel,
			Ldap::SYNCHRO_LDAP_TO_DOLIBARR	=> $ldap2dolibarrlabel
		);

		if (is_array($exclude) && !empty($exclude)) {
			foreach ($exclude as $value) {
				if (array_key_exists($value, $arraylist)) {
					unset($arraylist[$value]);
				}
			}
		}

		return $form->selectarray($htmlname, $arraylist, $selected, $showempty);
	}

	/**
	 *  Return list of ldap server types
	 *
	 *  @param	string		$selected		Preselected type
	 *	@param  string		$htmlname		Name of field in form
	 * 	@param	int			$showempty		Add an empty field
	 *  @return	string						HTML select string
	 */
	public function selectLdapServerType($selected = 'openldap', $htmlname = 'type', $showempty = 0)
	{
		global $form;

		if (empty($selected)) {
			$selected = 'openldap';
		}
		if (empty($htmlname)) {
			$htmlname = 'type';
		}

		$arraylist = array(
			'activedirectory'	=> 'Active Directory',
			'openldap'			=> 'OpenLdap',
			'egroupware'		=> 'Egroupware'
		);

		return $form->selectarray($htmlname, $arraylist, $selected, $showempty);
	}

	/**
	 *  Return list of ldap server protocol version
	 *
	 *  @param	string		$selected		Preselected type
	 *	@param  string		$htmlname		Name of field in form
	 * 	@param	int			$showempty		Add an empty field
	 *  @return	string						HTML select string
	 */
	public function selectLdapServerProtocolVersion($selected = '3', $htmlname = 'ldapprotocolversion', $showempty = 0)
	{
		global $form;

		if (empty($selected)) {
			$selected = '3';
		}
		if (empty($htmlname)) {
			$htmlname = 'ldapprotocolversion';
		}

		$arraylist = array(
			'3'	=> 'Version 3',
			'2'	=> 'Version 2'
		);

		return $form->selectarray($htmlname, $arraylist, $selected, $showempty);
	}
}
