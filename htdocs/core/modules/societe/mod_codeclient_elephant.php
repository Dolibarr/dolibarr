<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2013-2018 Philippe Grand      	<philippe.grand@atoo-net.com>
 * Copyright (C) 2020		Frédéric France		<frederic.france@netlogic.fr>
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
 *       \file       htdocs/core/modules/societe/mod_codeclient_elephant.php
 *       \ingroup    societe
 *       \brief      File of class to manage third party code with elephant rule
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *	Class to manage third party code with elephant rule
 */
class mod_codeclient_elephant extends ModeleThirdPartyCode
{
	/**
	 * @var string model name
	 */
	public $name = 'Elephant';

	/**
	 * @var int Code modifiable
	 */
	public $code_modifiable;

	/**
	 * @var int Code modifiable si il est invalide
	 */
	public $code_modifiable_invalide;

	/**
	 * @var int Code modifiables si il est null
	 */
	public $code_modifiable_null;

	/**
	 * @var int Code facultatif
	 */
	public $code_null;

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var int Automatic numbering
	 */
	public $code_auto;

	/**
	 * @var string search string
	 */
	public $searchcode;

	/**
	 * @var int Nombre de chiffres du compteur
	 */
	public $numbitcounter;

	/**
	 * @var int thirdparty prefix is required when using {pre}
	 */
	public $prefixIsRequired;


	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_null = 0;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**
	 *  Return description of module
	 *
	 *  @param	Translate	$langs		Object langs
	 *  @return string      			Description of module
	 */
	public function info($langs)
	{
		global $conf, $mc;
		global $form;

		$langs->load("companies");

		$disabled = ((!empty($mc->sharings['referent']) && $mc->sharings['referent'] != $conf->entity) ? ' disabled' : '');

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="COMPANY_ELEPHANT_MASK_CUSTOMER">';
		$texte .= '<input type="hidden" name="param2" value="COMPANY_ELEPHANT_MASK_SUPPLIER">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("ThirdParty"), $langs->transnoentities("ThirdParty"));
		//$tooltip.=$langs->trans("GenericMaskCodes2");	Not required for third party numbering
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4b");
		$tooltip .= $langs->trans("GenericMaskCodes5");

		// Parametrage du prefix customers
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("CustomerCodeModel").'):</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value1" value="'.getDolGlobalString('COMPANY_ELEPHANT_MASK_CUSTOMER').'"'.$disabled.'>', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition" name="modify" value="'.$langs->trans("Modify").'"'.$disabled.'></td>';

		$texte .= '</tr>';

		// Parametrage du prefix suppliers
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("SupplierCodeModel").'):</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value2" value="'.getDolGlobalString('COMPANY_ELEPHANT_MASK_SUPPLIER').'"'.$disabled.'>', $tooltip, 1, 1).'</td>';
		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Translate	$langs		Object langs
	 * @param	societe		$objsoc		Object thirdparty
	 * @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string					Return string example
	 */
	public function getExample($langs, $objsoc = 0, $type = -1)
	{
		$error = 0;
		$examplecust = '';
		$examplesup = '';
		$errmsg = array(
			"ErrorBadMask",
			"ErrorCantUseRazIfNoYearInMask",
			"ErrorCantUseRazInStartedYearIfNoYearMonthInMask",
			"ErrorCounterMustHaveMoreThan3Digits",
			"ErrorBadMaskBadRazMonth",
			"ErrorCantUseRazWithYearOnOneDigit",
		);

		$cssforerror = (getDolGlobalString('SOCIETE_CODECLIENT_ADDON') == 'mod_codeclient_elephant' ? 'error' : 'opacitymedium');

		if ($type != 1) {
			$examplecust = $this->getNextValue($objsoc, 0);
			if (!$examplecust && ($cssforerror == 'error' || $this->error != 'NotConfigured')) {
				$langs->load("errors");
				$examplecust = '<span class="'.$cssforerror.'">'.$langs->trans('ErrorBadMask').'</span>';
				$error = 1;
			}
			if (in_array($examplecust, $errmsg)) {
				$langs->load("errors");
				$examplecust = '<span class="'.$cssforerror.'">'.$langs->trans($examplecust).'</span>';
				$error = 1;
			}
		}
		if ($type != 0) {
			$examplesup = $this->getNextValue($objsoc, 1);
			if (!$examplesup && ($cssforerror == 'error' || $this->error != 'NotConfigured')) {
				$langs->load("errors");
				$examplesup = '<span class="'.$cssforerror.'">'.$langs->trans('ErrorBadMask').'</span>';
				$error = 1;
			}
			if (in_array($examplesup, $errmsg)) {
				$langs->load("errors");
				$examplesup = '<span class="'.$cssforerror.'">'.$langs->trans($examplesup).'</span>';
				$error = 1;
			}
		}

		if ($type == 0) {
			return $examplecust;
		} elseif ($type == 1) {
			return $examplesup;
		} else {
			return $examplecust.'<br>'.$examplesup;
		}
	}

	/**
	 * Return next value
	 *
	 * @param	Societe		$objsoc     Object third party
	 * @param  	int		    $type       Client ou fournisseur (0:customer, 1:supplier)
	 * @return 	string      			Value if OK, '' if module not configured, <0 if KO
	 */
	public function getNextValue($objsoc = 0, $type = -1)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// Get Mask value
		$mask = '';
		if ($type == 0) {
			$mask = empty($conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER) ? '' : $conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER;
		}
		if ($type == 1) {
			$mask = empty($conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER) ? '' : $conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER;
		}
		if (!$mask) {
			$this->error = 'NotConfigured';
			return '';
		}

		$field = '';
		$where = '';
		if ($type == 0) {
			$field = 'code_client';
			//$where = ' AND client in (1,2)';
		} elseif ($type == 1) {
			$field = 'code_fournisseur';
			//$where = ' AND fournisseur = 1';
		} else {
			return -1;
		}

		$now = dol_now();

		$numFinal = get_next_value($db, $mask, 'societe', $field, $where, '', $now);

		return  $numFinal;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Check if mask/numbering use prefix
	 *
	 *   @return	int			0 or 1
	 */
	public function verif_prefixIsUsed()
	{
		// phpcs:enable
		global $conf;

		$mask = $conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER;
		if (preg_match('/\{pre\}/i', $mask)) {
			return 1;
		}

		$mask = $conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER;
		if (preg_match('/\{pre\}/i', $mask)) {
			return 1;
		}

		return 0;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db		Database handler
	 *	@param	string		$code	Code to check/correct
	 *	@param	Societe		$soc	Object third party
	 *  @param  int		  	$type   0 = customer/prospect , 1 = supplier
	 *  @return int					0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 * 								-5 NotConfigured - Setup empty so any value may be ok or not
	 * 								-6 Other (see this->error)
	 */
	public function verif($db, &$code, $soc, $type)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$result = 0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED)) {
			$result = 0;
		} elseif (empty($code) && (!$this->code_null || !empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED))) {
			$result = -2;
		} else {
			// Get Mask value
			$mask = '';
			if ($type == 0) {
				$mask = empty($conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER) ? '' : $conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER;
			}
			if ($type == 1) {
				$mask = empty($conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER) ? '' : $conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER;
			}
			if (!$mask) {
				$this->error = 'NotConfigured';
				return -5;
			}
			$result = check_value($mask, $code);
			if (is_string($result)) {
				$this->error = $result;
				return -6;
			} else {
				$is_dispo = $this->verif_dispo($db, $code, $soc, $type);
				if ($is_dispo <> 0) {
					$result = -3;
				}
			}
		}

		dol_syslog("mod_codeclient_elephant::verif type=".$type." result=".$result);
		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Renvoi si un code est pris ou non (par autre tiers)
	 *
	 *		@param	DoliDB		$db			Handler acces base
	 *		@param	string		$code		Code a verifier
	 *		@param	Societe		$soc		Objet societe
	 *		@param  int		  	$type   	0 = customer/prospect , 1 = supplier
	 *		@return	int						0 if available, <0 if KO
	 */
	public function verif_dispo($db, $code, $soc, $type = 0)
	{
		// phpcs:enable
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe";
		if ($type == 1) {
			$sql .= " WHERE code_fournisseur = '".$db->escape($code)."'";
		} else {
			$sql .= " WHERE code_client = '".$db->escape($code)."'";
		}
		if ($soc->id > 0) {
			$sql .= " AND rowid <> ".$soc->id;
		}
		$sql .= " AND entity IN (".getEntity('societe').")";

		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) == 0) {
				return 0;
			} else {
				return -1;
			}
		} else {
			return -2;
		}
	}
}
