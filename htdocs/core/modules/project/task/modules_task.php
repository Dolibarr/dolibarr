<?php
<<<<<<< HEAD
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@capnetworks.com>
=======
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2010 Florian Henry  <florian.henry<àopen-concept.pro>
 * Copyright (C) 2014 Marcos García  <marcosgdf@gmail.com>
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
 *		\file       htdocs/core/modules/project/task/modules_task.php
 *      \ingroup    project
 *      \brief      File that contain parent class for task models
 *                  and parent class for task numbering models
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class for projects models
 */
abstract class ModelePDFTask extends CommonDocGenerator
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
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
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

		$type='project_task';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
<<<<<<< HEAD
		$liste=getListOfModels($db,$type,$maxfilenamelength);
=======
		$liste=getListOfModels($db, $type, $maxfilenamelength);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return $liste;
	}
}



/**
 *  Classe mere des modeles de numerotation des references de projets
 */
abstract class ModeleNumRefTask
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
	 *  Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
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
	 *  Renvoi la description par defaut du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
<<<<<<< HEAD
	function info()
=======
    public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("projects");
		return $langs->trans("NoDescription");
	}

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
<<<<<<< HEAD
	function getExample()
=======
    public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("projects");
		return $langs->trans("NoExample");
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
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
	 *  Renvoi prochaine valeur attribuee
	 *
	 *	@param	Societe		$objsoc		Object third party
	 *	@param	Project		$project	Object project
	 *	@return	string					Valeur
	 */
<<<<<<< HEAD
	function getNextValue($objsoc, $project)
=======
    public function getNextValue($objsoc, $project)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  Renvoi version du module numerotation
	 *
	 *  @return     string      Valeur
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
		return $langs->trans("NotAvailable");
	}
}
