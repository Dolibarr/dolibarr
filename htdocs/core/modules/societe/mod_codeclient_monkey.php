<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *       \brief      Fichier de la classe des gestion lion des codes clients
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *	Classe permettant la gestion monkey des codes tiers
 */
class mod_codeclient_monkey extends ModeleThirdPartyCode
{
	/**
	 * @var string model name
	 */
	public $name = 'Monkey';

	public $code_modifiable; // Code modifiable

	public $code_modifiable_invalide; // Code modifiable si il est invalide

	public $code_modifiable_null; // Code modifiables si il est null

	public $code_null; // Code facultatif

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var int Automatic numbering
	 */
	public $code_auto;

	public $prefixcustomer = 'CU';

	public $prefixsupplier = 'SU';

	public $prefixIsRequired; // Le champ prefix du tiers doit etre renseigne quand on utilise {pre}


	/**
	 * 	Constructor
	 */
	public function __construct()
	{
		$this->nom = "Monkey";
		$this->name = "Monkey";
		$this->version = "dolibarr";
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
	 * @param	Translate	$langs		Object langs
	 * @param	societe		$objsoc		Object thirdparty
	 * @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string					Return string example
	 */
	public function getExample($langs, $objsoc = 0, $type = -1)
	{
		return $this->prefixcustomer.'0901-00001<br>'.$this->prefixsupplier.'0901-00001';
	}


	/**
	 *  Return next value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 *  @param  int			$type       Client ou fournisseur (1:client, 2:fournisseur)
	 *  @return string      			Value if OK, '' if module not configured, <0 if KO
	 */
	public function getNextValue($objsoc = 0, $type = -1)
	{
		global $db, $conf, $mc;

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

		// First, we get the max value (reponse immediate car champ indexe)
		$posindice = strlen($prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(".$field." FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE ".$field." LIKE '".$db->escape($prefix)."____-%'";
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
		$yymm	= strftime("%y%m", $date);

		if ($max >= (pow(10, 5) - 1)) {
			$num = $max + 1; // If counter > 99999, we do not format on 5 chars, we take number as it is
		} else {
			$num = sprintf("%05s", $max + 1);
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
	 *  @param  int		  	$type   0 = customer/prospect , 1 = supplier
	 *  @return int					0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 */
	public function verif($db, &$code, $soc, $type)
	{
		global $conf;

		$result = 0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED)) {
			$result = 0;
		} elseif (empty($code) && (!$this->code_null || !empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED))) {
			$result = -2;
		} else {
			if ($this->verif_syntax($code) >= 0) {
				$is_dispo = $this->verif_dispo($db, $code, $soc, $type);
				if ($is_dispo <> 0) {
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
		global $conf, $mc;

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
	 *  Renvoi si un code respecte la syntaxe
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
