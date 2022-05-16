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
 *       \file       htdocs/core/modules/product_batch/mod_lot_free.php
 *       \ingroup    productbatch
 *       \brief      File containing class for numbering model of Lot free
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';

/**
 *	\class 		mod_codeproduct_leopard
 *	\brief 		Classe permettant la gestion leopard des codes produits
 */
class mod_lot_free extends ModeleNumRefBatch
{
	/*
	 * Attention ce module est utilise par defaut si aucun module n'a
	 * ete definit dans la configuration
	 *
	 * Le fonctionnement de celui-ci doit donc rester le plus ouvert possible
	 */


	/**
	 * @var string model name
	 */
	public $name = 'lot_free';

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
	 *  @return string      		Description of module
	 */
	public function info()
	{
		global $langs;
		$langs->load("companies");
		return $langs->trans("LeopardNumRefModelDesc");
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Societe		$objsoc	    Object thirdparty
	 * @param   Object		$object		Object we need next value for
	 * @return	string					Return next value
	 */
	public function getNextValue($objsoc, $object)
	{
		global $langs;
		return '';
	}
}
