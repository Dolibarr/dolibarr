<?php
/* Copyright (C) 2018	   Quentin Vial-Gouteyron    <quentin.vial-gouteyron@atm-consulting.fr>
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
 *  \file       htdocs/core/modules/reception/modules_reception.php
 *  \ingroup    reception
 *  \brief      File that contains parent class for sending receipts models
 *              and parent class for sending receipts numbering models
 */
 require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 *	Parent class of sending receipts models
 */
abstract class ModelePdfReception extends CommonDocGenerator
{
	public $error = '';


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'reception';
		$liste = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste = getListOfModels($db, $type, $maxfilenamelength);

		return $liste;
	}
}


/**
 *  Parent Class of numbering models of sending receipts references
 */
abstract class ModelNumRefReception
{
	public $error = '';

	/** Return if a model can be used or not
	 *
	 *  @return		boolean     true if model can be used
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
		$langs->load("reception");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Returns numbering example
	 *
	 *	@return     string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("reception");
		return $langs->trans("NoExample");
	}

	/**
	 *	Test if existing numbers make problems with numbering
	 *
	 *	@return     boolean     false if conflit, true if ok
	 */
	public function canBeActivated()
	{
		return true;
	}

	/**
	 *	Returns next value assigned
	 *
	 *	@param	Societe		$objsoc     Third party object
	 *	@param	Object		$shipment	Shipment object
	 *	@return	string					Value
	 */
	public function getNextValue($objsoc, $shipment)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns version of the numbering model
	 *
	 *	@return     string      Value
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		elseif ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
