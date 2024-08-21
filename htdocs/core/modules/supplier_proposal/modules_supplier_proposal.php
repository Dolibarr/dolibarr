<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/modules/propale/modules_propale.php
 *  \ingroup    propale
 *  \brief      File for the parent class of PDF proposal generation
 *  			 and parent class of proposals and proposal numbering
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php'; // Requis car utilise dans les classes qui heritent


/**
 *	Perent class of the Proposal models
 */
abstract class ModelePDFSupplierProposal extends CommonDocGenerator
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
		$type = 'supplier_proposal';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param	SupplierProposal	$object				Object source to build document
	 *	@param	Translate			$outputlangs		Lang output object
	 *	@param	string				$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	int<0,1>			$hidedetails		Do not show line details
	 *	@param	int<0,1>			$hidedesc			Do not show desc
	 *	@param	int<0,1>			$hideref			Do not show ref
	 *	@return	int<-1,1>								1 if OK, <=0 if KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
	// phpcs:enable
}


/**
 *	Parent class of the Proposal numbering model classes
 */
abstract class ModeleNumRefSupplierProposal extends CommonNumRefGenerator
{
	/**
	 *  Return next value
	 *
	 *  @param	Societe				$objsoc					Object third party
	 * 	@param	SupplierProposal	$supplier_proposal		Object commercial proposal
	 *  @return string|int<-1,0>							Next value if OK, -1 if KO
	 */
	abstract public function getNextValue($objsoc, $supplier_proposal);
}
