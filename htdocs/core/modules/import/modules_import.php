<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/import/modules_import.php
 *	\ingroup    export
 *	\brief      File of parent class for import file readers
 */
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');


/**
 *	\class      ModeleImports
 *	\brief      Parent class for import file readers
 */
class ModeleImports
{
	var $error='';

	var $driverlabel=array();
	var $driverdesc=array();
	var $driverversion=array();

	var $liblabel=array();
	var $libversion=array();


	/**
	 *      \brief      Constructeur
	 */
	function ModeleImports()
	{
	}

	/**
	 *      \brief      Charge en memoire et renvoie la liste des modeles actifs
	 *      \param      db      Handler de base
	 */
	function liste_modeles($db)
	{
		dol_syslog("ModeleImport::liste_modeles");

		$dir=DOL_DOCUMENT_ROOT."/core/modules/import/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers imports disponibles
		$var=True;
		$i=0;
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
    			if (preg_match("/^import_(.*)\.modules\.php/i",$file,$reg))
    			{
    				$moduleid=$reg[1];

    				// Chargement de la classe
    				$file = $dir."/import_".$moduleid.".modules.php";
    				$classname = "Import".ucfirst($moduleid);

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
        }

		return array_keys($this->driverlabel);
	}


	/**
	 *      \brief      Return picto of import driver
	 */
	function getPicto($key)
	{
		return $this->picto[$key];
	}

	/**
	 *      \brief      Renvoi libelle d'un driver import
	 */
	function getDriverLabel($key)
	{
		return $this->driverlabel[$key];
	}

	/**
	 *      \brief      Renvoi la description d'un driver import
	 */
	function getDriverDesc($key)
	{
		return $this->driverdesc[$key];
	}

	/**
	 *      \brief      Renvoi version d'un driver import
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
