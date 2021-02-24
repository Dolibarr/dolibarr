<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * Prefix customer accountancy code
	 * @var string
	 */
	public $prefixcustomeraccountancycode;

	/**
	 * Prefix supplier accountancy code
	 * @var string
	 */
	public $prefixsupplieraccountancycode;

	public $position = 30;


	/**
	 * 	Constructor
	 */
	public function __construct()
	{
		global $conf, $langs;
		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER) || trim($conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER) == '') $conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER = '411';
		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER) || trim($conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER) == '') $conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER = '401';
		$this->prefixcustomeraccountancycode = $conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER;
		$this->prefixsupplieraccountancycode = $conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER;

		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER) || trim($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER) == '') $conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER = '5';
		if (!isset($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER) || trim($conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER) == '') $conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER = '5';
		$this->customeraccountancycodecharacternumber = $conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER;
		$this->supplieraccountancycodecharacternumber = $conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER;
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
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="COMPANY_DIGITARIA_MASK_SUPPLIER">';
		$texte .= '<input type="hidden" name="param2" value="COMPANY_DIGITARIA_MASK_CUSTOMER">';
		$texte .= '<input type="hidden" name="param3" value="COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER">';
		$texte .= '<input type="hidden" name="param4" value="COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER">';
		$texte .= '<table class="nobordernopadding" width="100%">';
		$s1 = $form->textwithpicto('<input type="text" class="flat" size="4" name="value1" value="'.$conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER.'">', $tooltip, 1, 1);
		$s2 = $form->textwithpicto('<input type="text" class="flat" size="4" name="value2" value="'.$conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER.'">', $tooltip, 1, 1);
		$s3 = $form->textwithpicto('<input type="text" class="flat" size="2" name="value3" value="'.$conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER.'">', $tooltip, 1, 1);
		$s4 = $form->textwithpicto('<input type="text" class="flat" size="2" name="value4" value="'.$conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER.'">', $tooltip, 1, 1);
		$texte .= '<tr><td>';
		// trans remove html entities
		$texte .= $langs->trans("ModuleCompanyCodeCustomer".$this->name, '{s2}', '{s4}')."<br>\n";
		$texte .= $langs->trans("ModuleCompanyCodeSupplier".$this->name, '{s1}', '{s3}')."<br>\n";
		$texte = str_replace(array('{s1}', '{s2}', '{s3}', '{s4}'), array($s1, $s2, $s3, $s4), $texte);
		$texte .= "<br>\n";
		// Remove special char if COMPANY_DIGITARIA_REMOVE_SPECIAL is set to 1 or not set (default)
		if (!isset($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL) || !empty($conf->global->$conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL)) $texte .= $langs->trans('RemoveSpecialChars').' = '.yn(1)."<br>\n";
		// Apply a regex replacement pattern on code if COMPANY_DIGITARIA_CLEAN_REGEX is set. Value must be a regex with parenthesis. The part into parenthesis is kept, the rest removed.
		if (!empty($conf->global->COMPANY_DIGITARIA_CLEAN_REGEX))  $texte .= $langs->trans('COMPANY_DIGITARIA_CLEAN_REGEX').' = '.$conf->global->COMPANY_DIGITARIA_CLEAN_REGEX."<br>\n";
		// Unique index on code if COMPANY_DIGITARIA_UNIQUE_CODE is set to 1 or not set (default)
		if (!isset($conf->global->COMPANY_DIGITARIA_UNIQUE_CODE) || !empty($conf->global->COMPANY_DIGITARIA_UNIQUE_CODE)) $texte .= $langs->trans('COMPANY_DIGITARIA_UNIQUE_CODE').' = '.yn(1)."<br>\n";
		$texte .= '</td>';
		$texte .= '<td class="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
		$texte .= '</tr></table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of result returned by getNextValue
	 *
	 *  @param	Translate	$langs		Object langs
	 *  @param	Societe		$objsoc		Object thirdparty
	 *  @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 *  @return	string					Example
	 */
	public function getExample($langs, $objsoc = 0, $type = -1)
	{
		global $conf, $mysoc;

		$s = $langs->trans("ThirdPartyName").": ".$mysoc->name;
		$s .= "<br>\n";

		if (!isset($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL)) $thirdpartylabelexample = preg_replace('/([^a-z0-9])/i', '', $mysoc->name);
		$s .= "<br>\n";
		$s .= $this->prefixcustomeraccountancycode.strtoupper(substr($thirdpartylabelexample, 0, $this->customeraccountancycodecharacternumber));
		$s .= "<br>\n";
		$s .= $this->prefixsupplieraccountancycode.strtoupper(substr($thirdpartylabelexample, 0, $this->supplieraccountancycodecharacternumber));
		return $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB	$db              Database handler
	 *  @param  Societe	$societe         Third party object
	 *  @param  int		$type			'customer' or 'supplier'
	 *  @return	int						>=0 if OK, <0 if KO
	 */
	public function get_code($db, $societe, $type = '')
	{
		// phpcs:enable
		global $conf;
		$i = 0;
		$this->code = '';

		$disponibility = 0;

		if (is_object($societe))
		{
			dol_syslog("mod_codecompta_digitaria::get_code search code for type=".$type." & company=".(!empty($societe->name) ? $societe->name : ''));

			if ($type == 'supplier') {
				$codetouse = $societe->name;
				$prefix = $this->prefixsupplieraccountancycode;
				$width = $this->supplieraccountancycodecharacternumber;
			} elseif ($type == 'customer')
			{
				$codetouse = $societe->name;
				$prefix = $this->prefixcustomeraccountancycode;
				$width = $this->customeraccountancycodecharacternumber;
			} else {
				$this->error = 'Bad value for parameter type';
				return -1;
			}

			// Remove special char if COMPANY_DIGITARIA_REMOVE_SPECIAL is set to 1 or not set (default)
			if (!isset($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL) || !empty($conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL)) $codetouse = preg_replace('/([^a-z0-9])/i', '', $codetouse);
			// Apply a regex replacement pattern on code if COMPANY_DIGITARIA_CLEAN_REGEX is set. Value must be a regex with parenthesis. The part into parenthesis is kept, the rest removed.
			if (!empty($conf->global->COMPANY_DIGITARIA_CLEAN_REGEX))	// Example: $conf->global->COMPANY_DIGITARIA_CLEAN_REGEX='^..(..)..';
			{
				$codetouse = preg_replace('/'.$conf->global->COMPANY_DIGITARIA_CLEAN_REGEX.'/', '\1\2\3', $codetouse);
			}

			$this->code = $prefix.strtoupper(substr($codetouse, 0, $width));
			dol_syslog("mod_codecompta_digitaria::get_code search code proposed=".$this->code);

			// Unique index on code if COMPANY_DIGITARIA_UNIQUE_CODE is set to 1 or not set (default)
			if (!isset($conf->global->COMPANY_DIGITARIA_UNIQUE_CODE) || !empty($conf->global->COMPANY_DIGITARIA_UNIQUE_CODE))
			{
				$disponibility = $this->checkIfAccountancyCodeIsAlreadyUsed($db, $this->code, $type);

				while ($disponibility <> 0 && $i < 100) {
					$widthsupplier = $this->supplieraccountancycodecharacternumber;
					$widthcustomer = $this->customeraccountancycodecharacternumber;

					if ($i <= 9) {
						$a = 1;
					}
					if ($i >= 10 && $i <= 99) {
						$a = 2;
					}

					if ($type == 'supplier') {
						$this->code = $prefix.strtoupper(substr($codetouse, 0, $widthsupplier - $a)).$i;
					} elseif ($type == 'customer') {
						$this->code = $prefix.strtoupper(substr($codetouse, 0, $widthcustomer - $a)).$i;
					}
					$disponibility = $this->checkIfAccountancyCodeIsAlreadyUsed($db, $this->code, $type);

					$i++;
				}
			} else {
				$disponibility == 0;
			}
		}

		if ($disponibility == 0) {
			return 0; // return ok
		} else {
			return -1; // return ko
		}
	}

	/**
	 *  Check accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB	$db             Database handler
	 *  @param  string	$code           Code of third party
	 *  @param  int		$type			'customer' or 'supplier'
	 *  @return	int						>=0 if OK, <0 if KO
	 */
	public function checkIfAccountancyCodeIsAlreadyUsed($db, $code, $type = '')
	{
		if ($type == 'supplier')
		{
			$typethirdparty = 'code_compta_fournisseur';
		} elseif ($type == 'customer')
		{
			$typethirdparty = 'code_compta';
		} else {
			$this->error = 'Bad value for parameter type';
			return -1;
		}

		$sql = "SELECT ".$typethirdparty." FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE ".$typethirdparty." = '".$db->escape($code)."'";

		$resql = $db->query($sql);
		if ($resql)
		{
			if ($db->num_rows($resql) == 0)
			{
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
