<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *       \file       htdocs/core/modules/societe/mod_codeclient_monkey.php
 *       \ingroup    societe
 *       \brief      Fichier de la class des gestion lion des codes clients
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *	Class permettant la gestion monkey des codes tiers
 */
class mod_codeclient_monkey extends ModeleThirdPartyCode
{
	// variables inherited from ModeleThirdPartyCode class
	public $name = 'Monkey';
	public $version = 'dolibarr';

	// variables not inherited
	public $prefixcustomer = 'CU';
	public $prefixsupplier = 'SU';


	/**
	 * 	Constructor
	 *
	 *	@param DoliDB		$db		Database object
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->code_null = 1;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**
	 *  Return description of module
	 *
	 *  @param	Translate	$langs	Object langs
	 *  @return string      		Description of module
	 */
	public function info($langs)
	{
		return $langs->trans("MonkeyNumRefModelDesc", $this->prefixcustomer, $this->prefixsupplier);
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
		return $this->prefixcustomer.'0901-00001<br>'.$this->prefixsupplier.'0901-00001';
	}


	/**
	 *  Return next value
	 *
	 *  @param	Societe|string	$objsoc     Object third party
	 *  @param  int				$type       Client ou fournisseur (1:client, 2:fournisseur)
	 *  @return string|-1      				Value if OK, '' if module not configured, -1 if KO
	 */
	public function getNextValue($objsoc = '', $type = -1)
	{
		global $db;

		$field = '';
		$prefix = '';
		if ($type == 0) {
			$field = 'code_client';
			$prefix = $this->prefixcustomer;
		} elseif ($type == 1) {
			$field = 'code_fournisseur';
			$prefix = $this->prefixsupplier;
		} else {
			return -1;
		}

		// First, we get the max value (response immediate car champ indexe)
		$posindice = strlen($prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(".$db->sanitize($field)." FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE ".$db->sanitize($field)." LIKE '".$db->escape($prefix)."____-%'";
		$sql .= " AND entity IN (".getEntity('societe').")";

		dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			return -1;
		}

		$date	= dol_now();
		$yymm	= dol_print_date($date, "%y%m", 'tzuserrel');

		if ($max >= (pow(10, 5) - 1)) {
			$num = $max + 1; // If counter > 99999, we do not format on 5 chars, we take number as it is
		} else {
			$num = sprintf("%05d", $max + 1);
		}

		dol_syslog(get_class($this)."::getNextValue return ".$prefix.$yymm."-".$num);
		return $prefix.$yymm."-".$num;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db		Database handler
	 *	@param	string		$code	Code to check/correct
	 *	@param	Societe		$soc	Object third party
	 *  @param  int<0,1>  	$type   0 = customer/prospect , 1 = supplier
	 *  @return int<-6,0>			0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 * 								-5 NotConfigured - Setup empty so any value may be ok or not
	 * 								-6 Other (see this->error)
	 */
	public function verif($db, &$code, $soc, $type)
	{
		$result = 0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && !getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED')) {
			$result = 0;
		} elseif (empty($code) && (!$this->code_null || getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED'))) {
			$result = -2;
		} else {
			if ($this->verif_syntax($code) >= 0) {
				$is_dispo = $this->verif_dispo($db, $code, $soc, $type);
				if ($is_dispo != 0) {
					$result = -3;
				} else {
					$result = 0;
				}
			} else {
				if (dol_strlen($code) == 0) {
					$result = -2;
				} else {
					$result = -1;
				}
			}
		}

		dol_syslog(get_class($this)."::verif code=".$code." type=".$type." result=".$result);
		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Indicates if the code is available or not (by another third party)
	 *
	 *		@param	DoliDB		$db			Handler access base
	 *		@param	string		$code		Code a verifier
	 *		@param	Societe		$soc		Object societe
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
		$sql .= " AND entity IN (".getEntity('societe').")";
		if ($soc->id > 0) {
			$sql .= " AND rowid <> ".$soc->id;
		}

		dol_syslog(get_class($this)."::verif_dispo", LOG_DEBUG);
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


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi si un code respecte la syntax
	 *
	 *  @param  string      $code       Code a verifier
	 *  @return int                     0 si OK, <0 si KO
	 */
	public function verif_syntax($code)
	{
		// phpcs:enable
		$res = 0;

		if (dol_strlen($code) < 11) {
			$res = -1;
		} else {
			$res = 0;
		}
		return $res;
	}
}
