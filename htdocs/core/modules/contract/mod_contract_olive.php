<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Floran Henry  <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/contract/mod_contract_olive.php
 *  \ingroup    contract
 *  \brief      File of class to manage contract numbering rules Olive
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/contract/modules_contract.php';


/**
 * 	Class to manage contract numbering rules Olive
 */
class mod_contract_olive extends ModelNumRefContracts
{
<<<<<<< HEAD


	var $nom='Olive';					// Nom du modele
	var $code_modifiable = 1;				// Code modifiable
	var $code_modifiable_invalide = 1;		// Code modifiable si il est invalide
	var $code_modifiable_null = 1;			// Code modifiables si il est null
	var $code_null = 1;						// Code facultatif
	var $version='dolibarr';    		// 'development', 'experimental', 'dolibarr'
	var $code_auto = 0; 	                // Numerotation automatique
=======
    /**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom='Olive';

	/**
	 * @var string model name
	 */
	public $name='Olive';

	public $code_modifiable = 1;				// Code modifiable

	public $code_modifiable_invalide = 1;		// Code modifiable si il est invalide

	public $code_modifiable_null = 1;			// Code modifiables si il est null

	public $code_null = 1;						// Code facultatif

	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';    		// 'development', 'experimental', 'dolibarr'

	public $code_auto = 0; 	                // Numerotation automatique
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *	Return description of module
	 *
	 *	@return string      		Description of module
	 */
<<<<<<< HEAD
	function info()
=======
	public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;

		$langs->load("companies");
		return $langs->trans("LeopardNumRefModelDesc");
	}

	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Societe		$objsoc		Object thirdparty
	 * @param	Contrat		$contract	Object contract
	 * @return	string					Return next value
	 */
<<<<<<< HEAD
	function getNextValue($objsoc,$contract)
=======
	public function getNextValue($objsoc, $contract)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return '';
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db		Database handler
	 *	@param	string		$code	Code to check/correct
	 *	@param	Product		$product	Object product
	 *  @param  int		  	$type   0 = product , 1 = service
	 *  @return int					0 if OK
	 * 								-1 ErrorBadProductCodeSyntax
	 * 								-2 ErrorProductCodeRequired
	 * 								-3 ErrorProductCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 */
<<<<<<< HEAD
	function verif($db, &$code, $product, $type)
=======
	public function verif($db, &$code, $product, $type)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf;

		$result=0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && empty($conf->global->MAIN_CONTARCT_CODE_ALWAYS_REQUIRED))
		{
			$result=0;
		}
<<<<<<< HEAD
		else if (empty($code) && (! $this->code_null || ! empty($conf->global->MAIN_CONTARCT_CODE_ALWAYS_REQUIRED)) )
=======
		elseif (empty($code) && (! $this->code_null || ! empty($conf->global->MAIN_CONTARCT_CODE_ALWAYS_REQUIRED)) )
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			$result=-2;
		}

		dol_syslog("mod_contract_olive::verif type=".$type." result=".$result);
		return $result;
	}
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
