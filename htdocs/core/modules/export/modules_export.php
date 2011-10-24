<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/modules/export/modules_export.php
 *	\ingroup    export
 *	\brief      File of parent class for export modules
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *	\class      ModeleExports
 *	\brief      Parent class for export modules
 */
class ModeleExports extends CommonDocGenerator    // This class can't be abstract as there is instance propreties loaded by liste_modeles
{
	var $error='';

	var $driverlabel=array();
	var $driverversion=array();

	var $liblabel=array();
	var $libversion=array();


	/**
	 *      \brief      Charge en memoire et renvoie la liste des modeles actifs
	 *      \param      db      Handler de base
	 */
	function liste_modeles($db)
	{
		dol_syslog("ModeleExport::liste_modeles");

		$dir=DOL_DOCUMENT_ROOT."/core/modules/export/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers exports disponibles
		$var=True;
		$i=0;
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
    			if (preg_match("/^export_(.*)\.modules\.php$/i",$file,$reg))
    			{
    				$moduleid=$reg[1];

    				// Chargement de la classe
    				$file = $dir."/export_".$moduleid.".modules.php";
    				$classname = "Export".ucfirst($moduleid);

    				require_once($file);
    				$module = new $classname($db);

    				// Picto
    				$this->picto[$module->id]=$module->picto;
    				// Driver properties
    				$this->driverlabel[$module->id]=$module->getDriverLabel();
    				$this->driverdesc[$module->id]=$module->getDriverDesc();
    				$this->driverversion[$module->id]=$module->getDriverVersion();
    				// If use an external lib
    				$this->liblabel[$module->id]=$module->getLibLabel();
    				$this->libversion[$module->id]=$module->getLibVersion();

    				$i++;
    			}
    		}
    		closedir($handle);
        }
		return $this->driverlabel;
	}


	/**
	 *      \brief      Return picto of export driver
	 */
	function getPicto($key)
	{
		return $this->picto[$key];
	}

	/**
	 *      \brief      Renvoi libelle d'un driver export
	 */
	function getDriverLabel($key)
	{
		return $this->driverlabel[$key];
	}

	/**
	 *      \brief      Renvoi le descriptif d'un driver export
	 */
	function getDriverDesc($key)
	{
		return $this->driverdesc[$key];
	}

	/**
	 *      \brief      Renvoi version d'un driver export
	 */
	function getDriverVersion($key)
	{
		return $this->driverversion[$key];
	}

	/**
	 *      \brief      Renvoi libelle de librairie externe du driver
	 */
	function getLibLabel($key)
	{
		return $this->liblabel[$key];
	}

	/**
	 *      \brief      Renvoi version de librairie externe du driver
	 */
	function getLibVersion($key)
	{
		return $this->libversion[$key];
	}

}


?>
