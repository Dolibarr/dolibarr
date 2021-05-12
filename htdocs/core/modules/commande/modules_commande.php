<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2012      Juanjo Menent	    <jmenent@2byte.es>
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
 *  \file			htdocs/core/modules/commande/modules_commande.php
 *  \ingroup		commande
 *  \brief			File that contains parent class for orders models
 *                  and parent class for orders numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';	// required for use by classes that inherit
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';


/**
 *	Parent class for orders models
 */
abstract class ModelePDFCommandes extends CommonDocGenerator
{
<<<<<<< HEAD
	var $error='';

	/**
	 *  Return list of active generation modules
	 *
=======

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return list of active generation modules
     *
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
<<<<<<< HEAD
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='order';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
=======
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
		global $conf;

		$type = 'order';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}



/**
<<<<<<< HEAD
 *  \class      ModeleNumRefCommandes
 *  \brief      Classe mere des modeles de numerotation des references de commandes
 */

abstract class ModeleNumRefCommandes
{
	var $error='';
=======
 *  Parent class to manage numbering of Sale Orders
 */
abstract class ModeleNumRefCommandes
{
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
	 *	Renvoie la description par defaut du modele de numerotation
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
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Renvoie un exemple de numerotation
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
		$langs->load("orders");
		return $langs->trans("NoExample");
	}

	/**
	 *	Test si les numeros deja en vigueur dans la base ne provoquent pas de conflits qui empecheraient cette numerotation de fonctionner.
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
	 *	Renvoie prochaine valeur attribuee
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
	 *	Renvoie version du module numerotation
	 *
	 *	@return     string      Valeur
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
<<<<<<< HEAD
}
=======
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
