<?php
/* Copyright (C) 2026	Jose MARTINEZ	<jose.martinez@pichinov.com>
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
 *  \file			htdocs/core/modules/inventory/modules_inventory.php
 *  \ingroup		stock
 *  \brief			File that contains parent class for inventory document models and parent class for inventory numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class for documents models
 */
abstract class ModelePDFInventory extends CommonDocGenerator
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB  	$db                 Database handler
	 *  @param  int<0,max>	$maxfilenamelength  Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$type = 'inventory';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build document onto disk
	 *
	 *	@param		Inventory		$object				Object Inventory to generate
	 *	@param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>		$hidedetails		Do not show line details
	 *  @param		int<0,1>		$hidedesc			Do not show desc
	 *  @param		int<0,1>		$hideref			Do not show ref
	 *  @return     int<-1,1>      	    				1=OK, 0=KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
	// phpcs:enable
}



/**
 *  Parent class to manage numbering of Inventory
 */
abstract class ModeleNumRefInventory extends CommonNumRefGenerator
{
	/**
	 * 	Return next free value
	 *
	 *  @param  Inventory	$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, 0 if KO
	 */
	abstract public function getNextValue($object);

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	abstract public function getExample();
}
