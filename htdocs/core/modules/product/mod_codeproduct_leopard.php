<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/modules/product/mod_codeproduct_leopard.php
 *       \ingroup    product
 *       \brief      Fichier de la classe des gestion leopard des codes produits
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';


/**
 *	\class 		mod_codeproduct_leopard
 *	\brief 		Classe permettant la gestion leopard des codes produits
 */
class mod_codeproduct_leopard extends ModeleProductCode
{
	/*
	 * Attention ce module est utilise par defaut si aucun module n'a
	 * ete definit dans la configuration
	 *
	 * Le fonctionnement de celui-ci doit donc rester le plus ouvert possible
	 */

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom='Leopard';

	/**
	 * @var string model name
	 */
	public $name='Leopard';

	public $code_modifiable;				// Code modifiable

	public $code_modifiable_invalide;		// Code modifiable si il est invalide

	public $code_modifiable_null;			// Code modifiables si il est null

	public $code_null;						// Code facultatif

	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';    		// 'development', 'experimental', 'dolibarr'

	/**
	 * @var int Automatic numbering
	 */
	public $code_auto;


	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_null = 1;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 0;
	}


	/**
     *  Return description of module
	 *
	 *  @param	Translate	$langs	Object langs
	 *  @return string      		Description of module
	 */
	public function info($langs)
	{
		$langs->load("companies");
		return $langs->trans("LeopardNumRefModelDesc");
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	product		$objproduct		Object product
	 * @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string					Return next value
	 */
	public function getNextValue($objproduct = 0, $type = -1)
	{
		global $langs;
		return '';
	}


	/**
	 *  Check validity of code according to its rules
	 *
	 *  @param	DoliDB		$db			Database handler
	 *  @param	string		$code		Code to check/correct
	 *  @param	Product		$product	Object product
	 *  @param  int		  	$type   	0 = product , 1 = service
	 *  @return int                 	0 if OK
	 *                              	-1 ErrorBadProductCodeSyntax
	 *                              	-2 ErrorProductCodeRequired
	 *                              	-3 ErrorProductCodeAlreadyUsed
	 *                              	-4 ErrorPrefixRequired
	 */
	public function verif($db, &$code, $product, $type)
	{
		global $conf;

		$result=0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED))
		{
			$result=0;
		}
		elseif (empty($code) && (! $this->code_null || ! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED)))
		{
			$result=-2;
		}

		dol_syslog("mod_codeproduct_leopard::verif type=".$type." result=".$result);
		return $result;
	}
}
