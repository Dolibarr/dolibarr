<?php

/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Open-DSI                <support@opendsi.fr>
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
 *      \file       htdocs/core/modules/societe/mod_codecompta_digitaria.php
 *      \ingroup    societe
 *      \brief      File of class to manage accountancy code of thirdparties with Digitaria rules
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *		Class to manage accountancy code of thirdparties with Digitaria rules
 */
class mod_codecompta_digitaria extends ModeleAccountancyCode
{
	/**
	 * @var string model name
	 */
	public $name = 'Digitaria';

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Prefix customer accountancy code
	 */
	public $prefixcustomeraccountancycode;

	/**
	 * @var string Prefix supplier accountancy code
	 */
	public $prefixsupplieraccountancycode;

	/**
	 * @var int
	 */
	public $position = 30;

	/**
	 * @var string
	 */
	public $code;
	/**
	 * @var string
	 */
	public $customeraccountancycodecharacternumber;
	/**
	 * @var string
	 */
	public $supplieraccountancycodecharacternumber;


	/**
	 * 	Constructor
	 */
	public function __construct()
	{
		global $conf, $langs;
		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER) || trim($conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER) == '') {
			$conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER = '411';
		}
		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER) || trim($conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER) == '') {
			$conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER = '401';
		}
		$this->prefixcustomeraccountancycode = getDolGlobalString('COMPANY_DIGITARIA_MASK_CUSTOMER');
		$this->prefixsupplieraccountancycode = getDolGlobalString('COMPANY_DIGITARIA_MASK_SUPPLIER');

		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER) || trim($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER) == '') {
			$conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER = '5';
		}
		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER) || trim($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER) == '') {
			$conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER = '5';
		}
		$this->customeraccountancycodecharacternumber = getDolGlobalString('COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER');
		$this->supplieraccountancycodecharacternumber = getDolGlobalString('COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER');
	}

	/**
	 * Return description of module
	 *
	 * @param	Translate	$langs	Object langs
	 * @return 	string      		Description of module
	 */
	public function info($langs)
	{
		global $conf, $form;

		$tooltip = '';
		$texte = '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="COMPANY_DIGITARIA_MASK_SUPPLIER">';
		$texte .= '<input type="hidden" name="param2" value="COMPANY_DIGITARIA_MASK_CUSTOMER">';
		$texte .= '<input type="hidden" name="param3" value="COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER">';
		$texte .= '<input type="hidden" name="param4" value="COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER">';
		$texte .= '<input type="hidden" name="param5" value="COMPANY_DIGITARIA_CLEAN_WORDS">';
		$texte .= '<table class="nobordernopadding centpercent">';
		$s1 = $form->textwithpicto('<input type="text" class="flat" size="4" name="value1" value="' . getDolGlobalString('COMPANY_DIGITARIA_MASK_SUPPLIER').'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name);
		$s2 = $form->textwithpicto('<input type="text" class="flat" size="4" name="value2" value="' . getDolGlobalString('COMPANY_DIGITARIA_MASK_CUSTOMER').'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name);
		$s3 = $form->textwithpicto('<input type="text" class="flat" size="2" name="value3" value="' . getDolGlobalString('COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER').'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name);
		$s4 = $form->textwithpicto('<input type="text" class="flat" size="2" name="value4" value="' . getDolGlobalString('COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER').'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name);
		$texte .= '<tr><td>';
		// trans remove html entities
		$texte .= $langs->trans("ModuleCompanyCodeCustomer".$this->name, '{s2}', '{s4}')."<br>\n";
		$texte .= $langs->trans("ModuleCompanyCodeSupplier".$this->name, '{s1}', '{s3}')."<br>\n";
		$texte = str_replace(array('{s1}', '{s2}', '{s3}', '{s4}'), array($s1, $s2, $s3, $s4), $texte);
		$texte .= "<br>\n";
		// Remove special char if COMPANY_DIGITARIA_REMOVE_SPECIAL is set to 1 or not set (default)
		if (!isset($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL) || getDolGlobalString('COMPANY_DIGITARIA_REMOVE_SPECIAL')) {
			$texte .= $langs->trans('RemoveSpecialChars').' = '.yn(1)."<br>\n";
		}
		// Apply a regex replacement pattern on code if COMPANY_DIGITARIA_CLEAN_REGEX is set. Value must be a regex with parenthesis. The part into parenthesis is kept, the rest removed.
		if (getDolGlobalString('COMPANY_DIGITARIA_CLEAN_REGEX')) {
			$texte .= $langs->trans('COMPANY_DIGITARIA_CLEAN_REGEX').' = ' . getDolGlobalString('COMPANY_DIGITARIA_CLEAN_REGEX')."<br>\n";
		}
		// If value is not unique (if COMPANY_DIGITARIA_UNIQUE_CODE is set to 0), we show this
		if (!getDolGlobalString('COMPANY_DIGITARIA_UNIQUE_CODE', '1')) {
			$texte .= $langs->trans('DuplicateForbidden').' = '.yn(0)."<br>\n";
		}
		$texte .= '</td>';
		$texte .= '<td class="right"><input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.$langs->trans("Modify").'"></td>';
		$texte .= '</tr>';

		$texte .= '<tr><td>';
		$texte .= "<br>\n";

		$texthelp  = $langs->trans("RemoveSpecialWordsHelp");
		$texttitle = $langs->trans("RemoveSpecialWords");

		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1);
		$texte .= "<br>\n";
		$texte .= '<textarea class="flat textareafordir" spellcheck="false" cols="60" name="value5">';
		if (getDolGlobalString('COMPANY_DIGITARIA_CLEAN_WORDS')) {
			$texte .= $conf->global->COMPANY_DIGITARIA_CLEAN_WORDS;
		}
		$texte .= '</textarea>';
		$texte .= '</tr></table>';

		// Warn if the generated code length (prefix + number of characters) does not match the auxiliary
		// account length: the general ledger would then pad the code with trailing zeros (length_accounta()).
		if ($this->codeLengthDiffersFromAuxiliaryLength($this->prefixcustomeraccountancycode, (int) $this->customeraccountancycodecharacternumber)
			|| $this->codeLengthDiffersFromAuxiliaryLength($this->prefixsupplieraccountancycode, (int) $this->supplieraccountancycodecharacternumber)) {
			$texte .= '<br><div class="warning">'.img_warning().' '.$langs->trans("WarningDigitariaCodeLengthNotMatchingAccountLength", getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')).'</div>';
		}

		$texte .= '</form>';

		return $texte;
	}

	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	?Translate		$langs		Object langs
	 * @param	Societe|string	$objsoc		Object thirdparty
	 * @param	int<-1,2>		$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string						Return string example
	 */
	public function getExample($langs = null, $objsoc = '', $type = -1)
	{
		global $conf, $mysoc;
		if (!$langs instanceof Translate) {
			$langs = $GLOBALS['langs'];
			'@phan-var-force Translate $langs';
		}

		$s = $langs->trans("ThirdPartyName").": ".$mysoc->name;
		$s .= "<br>\n";

		if (!isset($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL)) {
			$thirdpartylabelexample = (string) preg_replace('/([^a-z0-9])/i', '', $mysoc->name);
		} else {
			$thirdpartylabelexample = '';
		}
		$s .= "<br>\n";
		$s .= $this->prefixcustomeraccountancycode.strtoupper(substr($thirdpartylabelexample, 0, (int) $this->customeraccountancycodecharacternumber));
		$s .= "<br>\n";
		$s .= $this->prefixsupplieraccountancycode.strtoupper(substr($thirdpartylabelexample, 0, (int) $this->supplieraccountancycodecharacternumber));
		return $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set accountancy account code for a third party into this->code
	 *
	 *  The generated code has a fixed length (length of the prefix + configured width). A fixed
	 *  length keeps the auxiliary account unique once the general ledger pads it with trailing
	 *  zeros (length_accounta()) up to ACCOUNTING_LENGTH_AACCOUNT. The counter uses the whole
	 *  width (the root is shortened by one more character each time a depth is full) so the
	 *  capacity is about 10^(width-1) codes per root instead of ~1000. At least the first
	 *  character of the root is always kept, except when the name is emptied by the cleaning
	 *  rules (the code is purely numeric only for such an empty root, or for a numeric name).
	 *
	 *  @param	DoliDB		$db				Database handler
	 *  @param  ?Societe	$societe		Third party object
	 *  @param  'customer'|'supplier'|''	$type	'customer' or 'supplier'
	 *  @return	int							>=0 if OK, <0 if KO
	 */
	public function get_code($db, $societe, $type = '')
	{
		// phpcs:enable
		global $conf, $langs;

		$this->code = '';

		if (!is_object($societe)) {
			return 0;
		}

		dol_syslog("mod_codecompta_digitaria::get_code search code for type=".$type." & company=".(!empty($societe->name) ? $societe->name : ''));

		if ($type == 'supplier') {
			$codetouse = (string) $societe->name;
			$prefix = $this->prefixsupplieraccountancycode;
			$width = (int) $this->supplieraccountancycodecharacternumber;
		} elseif ($type == 'customer') {
			$codetouse = (string) $societe->name;
			$prefix = $this->prefixcustomeraccountancycode;
			$width = (int) $this->customeraccountancycodecharacternumber;
		} else {
			$this->error = 'Bad value for parameter type';
			return -1;
		}

		// Clean declared words
		if (getDolGlobalString('COMPANY_DIGITARIA_CLEAN_WORDS')) {
			$cleanWords = explode(";", getDolGlobalString('COMPANY_DIGITARIA_CLEAN_WORDS'));
			$codetouse = str_replace($cleanWords, "", $codetouse);
		}
		// Remove special char if COMPANY_DIGITARIA_REMOVE_SPECIAL is set to 1 or not set (default)
		if (!isset($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL) || getDolGlobalString('COMPANY_DIGITARIA_REMOVE_SPECIAL')) {
			$codetouse = (string) preg_replace('/([^a-z0-9])/i', '', $codetouse);
		}
		// Apply a regex replacement pattern on code if COMPANY_DIGITARIA_CLEAN_REGEX is set. Value must be a regex with parenthesis. The part into parenthesis is kept, the rest removed.
		if (getDolGlobalString('COMPANY_DIGITARIA_CLEAN_REGEX')) {	// Example: $conf->global->COMPANY_DIGITARIA_CLEAN_REGEX='^..(..)..';
			$codetouse = (string) preg_replace('/' . getDolGlobalString('COMPANY_DIGITARIA_CLEAN_REGEX').'/', '\1\2\3', $codetouse);
		}

		$root = strtoupper(substr($codetouse, 0, $width));
		$rootlen = strlen($root);
		$targetlen = strlen($prefix) + $width;

		// A fixed code length avoids collisions when the ledger pads the code to ACCOUNTING_LENGTH_AACCOUNT.
		// Warn if the width is not set so that "prefix length + width = ACCOUNTING_LENGTH_AACCOUNT".
		if ($this->codeLengthDiffersFromAuxiliaryLength($prefix, $width)) {
			dol_syslog("mod_codecompta_digitaria::get_code fixed code length (".$targetlen.") differs from ACCOUNTING_LENGTH_AACCOUNT (".getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')."). Set the Digitaria width so that prefix length + width equals it to avoid trailing zeros in the ledger.", LOG_WARNING);
		}

		// If uniqueness is not required, return the bare code padded to the fixed length.
		if (!getDolGlobalString('COMPANY_DIGITARIA_UNIQUE_CODE', '1')) {
			$this->code = str_pad($prefix.$root, $targetlen, '0', STR_PAD_RIGHT);
			dol_syslog("mod_codecompta_digitaria::get_code search code proposed=".$this->code, LOG_DEBUG);
			return 0;
		}

		if ($rootlen >= $width) {
			// Long root (name fills the whole width): keep the historical sequence (bare code, then a
			// counter shortening the root) and only extend the counter beyond 3 digits, keeping >= 1 root character.
			$candidate = $prefix.$root;
			$availability = $this->checkIfAccountancyCodeIsAlreadyUsed($db, $candidate, $type);
			if ($availability < -1) {
				return -1; // SQL error, $this->error already set (do not treat it as "not available")
			}
			if ($availability == 0) {
				$this->code = $candidate;
				dol_syslog("mod_codecompta_digitaria::get_code search code proposed=".$this->code, LOG_DEBUG);
				return 0;
			}
			for ($a = 1; $a <= $width - 1; $a++) {
				$familyprefix = $prefix.substr($root, 0, $width - $a);
				$minval = ($a == 1) ? 0 : (int) pow(10, $a - 1);
				$maxval = (int) pow(10, $a) - 1;
				$next = $this->findNextFreeCounter($db, $type, $familyprefix, $a, $minval, $maxval);
				if ($next === false) {
					return -1; // SQL error, $this->error already set
				}
				if ($next !== null) {
					if ($a == $width - 1) {
						dol_syslog("mod_codecompta_digitaria::get_code last root character reached for ".$familyprefix, LOG_WARNING);
					}
					$this->code = $familyprefix.((string) $next); // counter not zero-padded (historical sequence)
					dol_syslog("mod_codecompta_digitaria::get_code search code proposed=".$this->code, LOG_DEBUG);
					return 0;
				}
			}
		} elseif ($rootlen >= 1) {
			// Short root (name shorter than the width): fixed-length code = prefix + kept root characters +
			// zero-padded counter. Keep at least 1 character of the root (so the code is purely numeric
			// only for a numeric name, not for an alphabetic one).
			for ($keep = $rootlen; $keep >= 1; $keep--) {
				$familyprefix = $prefix.substr($root, 0, $keep);
				$counterlen = $targetlen - strlen($familyprefix); // = width - keep
				$maxval = (int) pow(10, $counterlen) - 1;
				$next = $this->findNextFreeCounter($db, $type, $familyprefix, $counterlen, 0, $maxval);
				if ($next === false) {
					return -1; // SQL error, $this->error already set
				}
				if ($next !== null) {
					if ($keep == 1) {
						dol_syslog("mod_codecompta_digitaria::get_code last root character reached for ".$familyprefix, LOG_WARNING);
					}
					$this->code = $familyprefix.str_pad((string) $next, $counterlen, '0', STR_PAD_LEFT);
					dol_syslog("mod_codecompta_digitaria::get_code search code proposed=".$this->code, LOG_DEBUG);
					return 0;
				}
			}
		} else {
			// Empty root (name entirely emptied by the cleaning rules): no letter can be kept.
			// Fall back to a purely numeric fixed-length code and warn.
			$counterlen = $width;
			$maxval = (int) pow(10, $counterlen) - 1;
			$next = $this->findNextFreeCounter($db, $type, $prefix, $counterlen, 0, $maxval);
			if ($next === false) {
				return -1; // SQL error, $this->error already set
			}
			if ($next !== null) {
				dol_syslog("mod_codecompta_digitaria::get_code empty root, using a purely numeric code for prefix ".$prefix, LOG_WARNING);
				$this->code = $prefix.str_pad((string) $next, $counterlen, '0', STR_PAD_LEFT);
				dol_syslog("mod_codecompta_digitaria::get_code search code proposed=".$this->code, LOG_DEBUG);
				return 0;
			}
		}

		// No free code found: the root family is exhausted.
		$langs->load("companies");
		$this->error = $langs->trans("ErrorDigitariaAccountancyCodeExhausted", $prefix.$root);
		dol_syslog("mod_codecompta_digitaria::get_code ".$this->error, LOG_ERR);
		return -1;
	}

	/**
	 *  Return the smallest free counter value for a family of codes sharing a prefix.
	 *
	 *  One query per depth (O(width) queries) replaces the historical ~1000 unitary SELECT.
	 *
	 *  @param	DoliDB	$db				Database handler
	 *  @param	string	$type			'customer' or 'supplier'
	 *  @param	string	$familyprefix	Prefix shared by the codes of the family (prefix + kept root letters)
	 *  @param	int		$counterlen		Exact number of digits of the counter part
	 *  @param	int		$minval			Lowest counter value allowed
	 *  @param	int		$maxval			Highest counter value allowed
	 *  @return	int|null|false			Smallest free value, null if the family is full, false on SQL error
	 */
	protected function findNextFreeCounter($db, $type, $familyprefix, $counterlen, $minval, $maxval)
	{
		$codelen = strlen($familyprefix) + (int) $counterlen;
		$used = $this->fetchUsedAccountancyCodes($db, $type, $familyprefix, $codelen);
		if ($used === false) {
			return false; // SQL error, $this->error already set
		}

		$taken = array();
		foreach ($used as $code) {
			$suffix = substr($code, strlen($familyprefix));
			if (strlen($suffix) !== (int) $counterlen || !ctype_digit($suffix)) {
				continue;
			}
			$val = (int) $suffix;
			if ($val >= $minval && $val <= $maxval) {
				$taken[$val] = true;
			}
		}

		for ($val = $minval; $val <= $maxval; $val++) {
			if (!isset($taken[$val])) {
				return $val; // smallest free value (fills holes, like the historical loop)
			}
		}

		return null; // family full
	}

	/**
	 *  Fetch the already used accountancy codes of a given length starting with a prefix.
	 *
	 *  @param	DoliDB	$db				Database handler
	 *  @param	string	$type			'customer' or 'supplier'
	 *  @param	string	$familyprefix	Prefix the codes must start with
	 *  @param	int		$codelen		Exact length of the codes to return
	 *  @return	string[]|false			Array of codes, or false on SQL error / bad type
	 */
	protected function fetchUsedAccountancyCodes($db, $type, $familyprefix, $codelen)
	{
		$storage = $this->getAccountancyCodeStorage($type);
		if (!is_array($storage)) {
			return false; // bad type, $this->error already set
		}
		list($table, $column) = $storage;

		$sql = "SELECT ".$db->sanitize($column)." as code";
		$sql .= " FROM ".$db->prefix().$db->sanitize($table);
		$sql .= " WHERE ".$db->sanitize($column)." LIKE '".$db->escape($db->escapeforlike($familyprefix))."%'";
		$sql .= " AND LENGTH(".$db->sanitize($column).") = ".((int) $codelen); // LENGTH is portable (mysql, pgsql, sqlite), unlike CHAR_LENGTH
		$sql .= " AND entity IN (".getEntity('societe').")";

		$resql = $db->query($sql);
		if (!$resql) {
			$this->error = $db->error()." sql=".$sql;
			return false;
		}
		$codes = array();
		while ($obj = $db->fetch_object($resql)) {
			$codes[] = $obj->code;
		}
		$db->free($resql);

		return $codes;
	}

	/**
	 *  Resolve the storage table (without prefix) and column holding the accountancy code of a type.
	 *
	 *  @param	string	$type	'customer' or 'supplier'
	 *  @return	string[]|int	array(table, column) or -1 if the type is invalid
	 */
	protected function getAccountancyCodeStorage($type)
	{
		$shared = getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED');
		if ($type == 'supplier') {
			return $shared ? array('societe_perentity', 'accountancy_code_supplier') : array('societe', 'code_compta_fournisseur');
		} elseif ($type == 'customer') {
			return $shared ? array('societe_perentity', 'accountancy_code_customer') : array('societe', 'code_compta');
		}
		$this->error = 'Bad value for parameter type';
		return -1;
	}

	/**
	 *  Whether the fixed code length (prefix + width) differs from the auxiliary account length,
	 *  which would make the general ledger pad the code with trailing zeros (length_accounta()).
	 *
	 *  @param	string	$prefix	Accountancy code prefix
	 *  @param	int		$width	Number of characters generated after the prefix
	 *  @return	bool			True if ACCOUNTING_LENGTH_AACCOUNT is set and differs from prefix+width
	 */
	protected function codeLengthDiffersFromAuxiliaryLength($prefix, $width)
	{
		$aaccountlen = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
		return $aaccountlen > 0 && (strlen($prefix) + (int) $width) != $aaccountlen;
	}

	/**
	 *  Check accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB	$db             Database handler
	 *  @param  string	$code           Code of third party
	 *  @param  string	$type			'customer' or 'supplier'
	 *  @return	int						>=0 if OK, <0 if KO
	 */
	public function checkIfAccountancyCodeIsAlreadyUsed($db, $code, $type = '')
	{
		$storage = $this->getAccountancyCodeStorage($type);
		if (!is_array($storage)) {
			return -1; // bad type, $this->error already set
		}
		list($table, $typethirdparty) = $storage;

		$sql = "SELECT " . $db->sanitize($typethirdparty) . " FROM " . $db->prefix() . $db->sanitize($table);
		$sql .= " WHERE " . $db->sanitize($typethirdparty) . " = '" . $db->escape($code) . "'";
		$sql .= " AND entity IN (".getEntity('societe').")";

		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) == 0) {
				dol_syslog("mod_codecompta_digitaria::checkIfAccountancyCodeIsAlreadyUsed '".$code."' available");
				return 0; // Available
			} else {
				dol_syslog("mod_codecompta_digitaria::checkIfAccountancyCodeIsAlreadyUsed '".$code."' not available");
				return -1; // Not available
			}
		} else {
			$this->error = $db->error()." sql=".$sql;
			return -2; // Error
		}
	}
}
