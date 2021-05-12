<?php
/* Copyright (C) 2015      Juanjo Menent	    <jmenent@2byte.es>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
/**
 *	Parent class for supplier invoices models
 */
abstract class ModelePDFSuppliersPayments extends CommonDocGenerator
{
<<<<<<< HEAD
	var $error='';


=======
	/**
	 * @var string Error code (or message)
	 */
	public $error='';


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  Return list of active generation models
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of numbers
<<<<<<< HEAD
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
=======
     */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf;

		$type='supplier_payment';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
<<<<<<< HEAD
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}

=======
		$liste=getListOfModels($db, $type, $maxfilenamelength);

		return $liste;
	}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

/**
 *  \class      ModeleNumRefSupplierPayments
 *  \brief      Payment numbering references mother class
 */

abstract class ModeleNumRefSupplierPayments
{
<<<<<<< HEAD
	var $error='';
=======
	/**
	 * @var string Error code (or message)
	 */
	public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean     true if module can be used
	 */
<<<<<<< HEAD
	function isEnabled()
=======
	public function isEnabled()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
	 *	Return the default description of numbering module
	 *
	 *	@return     string      Texte descripif
	 */
<<<<<<< HEAD
	function info()
=======
	public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
<<<<<<< HEAD
	function getExample()
=======
	public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoExample");
	}

	/**
	 *  Test if the existing numbers in the database do not cause conflicts that would prevent this numbering run.
	 *
	 *	@return     boolean     false si conflit, true si ok
	 */
<<<<<<< HEAD
	function canBeActivated()
=======
	public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
	 *	Returns the next value
	 *
	 *	@param	Societe		$objsoc     Object thirdparty
	 *	@param	Object		$object		Object we need next value for
	 *	@return	string      Valeur
	 */
<<<<<<< HEAD
	function getNextValue($objsoc,$object)
=======
	public function getNextValue($objsoc, $object)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns the module numbering version
	 *
	 *	@return     string      Value
	 */
<<<<<<< HEAD
	function getVersion()
=======
	public function getVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
<<<<<<< HEAD
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
=======
		elseif ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		return $langs->trans("NotAvailable");
	}
}
