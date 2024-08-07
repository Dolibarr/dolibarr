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
 *       \file       htdocs/core/modules/product_batch/mod_sn_free.php
 *       \ingroup    productbatch
 *       \brief      File containing class for numbering model of SN free
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';

/**
 *	\class 		mod_codeproduct_leopard
 *	\brief 		Class permettant la gestion leopard des codes produits
 */
class mod_sn_free extends ModeleNumRefBatch
{
	/*
	 * :warning:
	 *    This module is used by default if none was set in the configuration.
	 *    Therefore, the implementation must remain as open as possible.
	 */

	// variables inherited from ModeleNumRefBatch class
	public $name = 'sn_free';
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
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs;
		$langs->load("companies");
		return $langs->trans("LeopardNumRefModelDesc");
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$thirdparty = new Societe($db);
		$thirdparty->initAsSpecimen();

		return $this->getNextValue($thirdparty, null);
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc		Object thirdparty
	 *  @param  Productlot	$object		Object we need next value for
	 *  @return string|<-1,0>			Value if OK, <=0
	 */
	public function getNextValue($objsoc, $object)
	{
		return '';
	}
}
