<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2013      Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/modules/contract/modules_contract.php
 *  \ingroup    contract
 *  \brief      File with parent class for generating contracts to PDF and File of class to manage contract numbering
 */

 require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class to manage intervention document templates
 */
abstract class ModelePDFContract extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'contract';
		$liste = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste = getListOfModels($db, $type, $maxfilenamelength);

		return $liste;
	}
}


/**
 * Parent class for all contract numbering modules
 */
class ModelNumRefContracts
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 *	Return if a module can be used or not
	 *
	 * 	@return		boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
	public function info()
	{
		global $langs;
		$langs->load("contracts");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("contracts");
		return $langs->trans("NoExample");
	}

	/**
	 *	Test if existing numbers make problems with numbering
	 *
	 *	@return		boolean		false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		return true;
	}

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     third party object
	 *	@param	Object		$contract	contract object
	 *	@return	string					Value
	 */
	public function getNextValue($objsoc, $contract)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Return numbering version module
	 *
	 *	@return     string      Value
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
