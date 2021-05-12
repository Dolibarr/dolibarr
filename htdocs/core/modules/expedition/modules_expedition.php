<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
<<<<<<< HEAD
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2013 Philippe Grand       <philippe.grand@atoo-net.com>
=======
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2019 Philippe Grand       <philippe.grand@atoo-net.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/expedition/modules_expedition.php
 *  \ingroup    expedition
 *  \brief      File that contains parent class for sending receipts models
 *              and parent class for sending receipts numbering models
 */
 require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 *	Parent class of sending receipts models
 */
abstract class ModelePdfExpedition extends CommonDocGenerator
{
<<<<<<< HEAD
    var $error='';


	/**
	 *  Return list of active generation modules
=======
    /**
	 * @var string Error code (or message)
	 */
	public $error='';


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation models
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
<<<<<<< HEAD
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='shipping';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
=======
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
		global $conf;

		$type='shipping';
		$list=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}


/**
 *  Parent Class of numbering models of sending receipts references
 */
abstract class ModelNumRefExpedition
{
<<<<<<< HEAD
	var $error='';
=======
	/**
	 * @var string Error code (or message)
	 */
	public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/** Return if a model can be used or not
	 *
	 *  @return		boolean     true if model can be used
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
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
<<<<<<< HEAD
	function info()
=======
    public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("sendings");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Returns numbering example
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
		$langs->load("sendings");
		return $langs->trans("NoExample");
	}

	/**
	 *	Test if existing numbers make problems with numbering
	 *
<<<<<<< HEAD
	 *	@return     boolean     false if conflit, true if ok
	 */
	function canBeActivated()
=======
	 *	@return     boolean     false if conflict, true if ok
	 */
    public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function getNextValue($objsoc, $shipment)
=======
    public function getNextValue($objsoc, $shipment)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns version of the numbering model
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
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
