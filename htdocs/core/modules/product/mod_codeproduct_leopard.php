<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/modules/product/mod_codeproduct_leopard.php
 *       \ingroup    product
 *       \brief      Fichier de la class des gestion leopard des codes produits
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';


/**
 *	\class 		mod_codeproduct_leopard
 *	\brief 		Class permettant la gestion leopard des codes produits
 */
class mod_codeproduct_leopard extends ModeleProductCode
{
	/*
	 * Please note this module is used by default if no module has been defined in the configuration
	 *
	 * Its operation must therefore remain as open as possible
	 */

	// variables inherited from ModelProductCode class
	public $name = 'Leopard';
	public $version = 'dolibarr';

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
	 * @param	?Translate		$langs		Object langs
	 * @param	Product|string	$objproduct	Object product
	 * @param	int<-1,2>		$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string						Return string example
	 */
	public function getExample($langs = null, $objproduct = '', $type = -1)
	{
		return '';
	}

	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Product|string	$objproduct	Object product
	 * @param	int				$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string						Return next value
	 */
	public function getNextValue($objproduct = '', $type = -1)
	{
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

		$result = 0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && !getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED')) {
			$result = 0;
		} elseif (empty($code) && (!$this->code_null || getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED'))) {
			$result = -2;
		}

		dol_syslog("mod_codeproduct_leopard::verif type=".$type." result=".$result);
		return $result;
	}
}
