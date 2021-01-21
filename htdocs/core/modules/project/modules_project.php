<?php
/* Copyright (C) 2010-2014	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2014       Marcos García   <marcosgdf@gmail.com>
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
 *		\file       htdocs/core/modules/project/modules_project.php
 *      \ingroup    project
 *      \brief      File that contain parent class for projects models
 *                  and parent class for projects numbering models
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class for projects models
 */
abstract class ModelePDFProjects extends CommonDocGenerator
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'project';
		$liste = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste = getListOfModels($db, $type, $maxfilenamelength);

		return $liste;
	}
}



/**
 *  Classe mere des modeles de numerotation des references de projets
 */
abstract class ModeleNumRefProjects
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 *  Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *  Renvoi la description par defaut du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("projects");
		return $langs->trans("NoDescription");
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("projects");
		return $langs->trans("NoExample");
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @return     boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
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
	public function getNextValue($objsoc, $project)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  Renvoi version du module numerotation
	 *
	 *  @return     string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		elseif ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("NotAvailable");
	}
}
