<?php
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@capnetworks.com>
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
	var $error='';


	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='project_task';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}
}



/**
 *  Classe mere des modeles de numerotation des references de projets
 */
abstract class ModeleNumRefTask
{
	var $error='';

	/**
	 *  Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *  Renvoi la description par defaut du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
	function info()
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
	function getExample()
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
	function canBeActivated()
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
	function getNextValue($objsoc, $project)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  Renvoi version du module numerotation
	 *
	 *  @return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}


/**
 *  Create an intervention document on disk using template defined into PROJECT_TASK_ADDON_PDF
 *
 *  @param	DoliDB		$db  			objet base de donnee
 *  @param	Task		$object			Object fichinter
 *  @param	string		$modele			force le modele a utiliser ('' par defaut)
 *  @param	Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @param  int			$hidedetails    Hide details of lines
 *  @param  int			$hidedesc       Hide description
 *  @param  int			$hideref        Hide ref
 *  @param  HookManager	$hookmanager	Hook manager instance
 *  @return int         				0 if KO, 1 if OK
 * @deprecated Use the new function generateDocument of Task class
 * @see Task::generateDocument()
 */
function task_pdf_create(DoliDB $db, Task $object, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $hookmanager=false)
{
	dol_syslog(__METHOD__ . " is deprecated", LOG_WARNING);

	return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
}

