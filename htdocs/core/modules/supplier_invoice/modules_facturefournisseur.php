<?php
/* Copyright (C) 2010       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2016  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
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
 *		\file       htdocs/core/modules/supplier_invoice/modules_facturefournisseur.php
 *      \ingroup    facture fournisseur
 *      \brief      File that contains parent class for supplier invoices models
 *					and parent class for supplier invoices numbering models
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php'; // required for use by classes that inherit


/**
 *	Parent class for supplier invoices models
 */
abstract class ModelePDFSuppliersInvoices extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation models
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of numbers
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'invoice_supplier';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}
}

/**
 *	Parent Class of numbering models of suppliers invoices references
 */
abstract class ModeleNumRefSuppliersInvoices
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**  Return if a model can be used or not
	 *
	 *   @return	boolean     true if model can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**  Returns the default description of the model numbering
	 *
	 *   @return    string      Description Text
	 */
	public function info()
	{
		global $langs;
		$langs->load("invoices");
		return $langs->trans("NoDescription");
	}

	/**   Returns a numbering example
	 *
	 *    @return   string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("invoices");
		return $langs->trans("NoExample");
	}

	/**  Tests if the numbers already in force in the database do not cause conflicts that would prevent this numbering.
	 *
	 *   @return	boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		return true;
	}

	/**  Returns next value assigned
	 *
	 * @param	Societe		$objsoc     Object third party
	 * @param  	Object	    $object		Object
	 * @param	string		$mode       'next' for next value or 'last' for last value
	 * @return 	string      			Value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $object, $mode)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**   Returns version of the model numbering
	 *
	 *    @return     string      Value
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') {
			return $langs->trans("VersionDevelopment");
		}
		if ($this->version == 'experimental') {
			return $langs->trans("VersionExperimental");
		}
		if ($this->version == 'dolibarr') {
			return DOL_VERSION;
		}
		if ($this->version) {
			return $this->version;
		}
		return $langs->trans("NotAvailable");
	}
}
