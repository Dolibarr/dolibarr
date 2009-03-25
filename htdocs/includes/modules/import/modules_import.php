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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/import/modules_import.php
 *	\ingroup    export
 *	\brief      File of parent class for import file readers
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');


/**
 *	\class      ModeleImports
 *	\brief      Parent class for import file readers
 */
class ModeleImports
{
	var $error='';

	var $driverlabel=array();
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
		dol_syslog("ModeleImport::loadFormat");

		$dir=DOL_DOCUMENT_ROOT."/includes/modules/import/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers imports disponibles
		$var=True;
		$i=0;
		while (($file = readdir($handle))!==false)
		{
			if (eregi("^import_(.*)\.modules\.php",$file,$reg))
			{
				$moduleid=$reg[1];

				// Chargement de la classe
				$file = $dir."/import_".$moduleid.".modules.php";
				$classname = "Import".ucfirst($moduleid);

				require_once($file);
				$module = new $classname($db);

				// Driver properties
				$this->driverlabel[$module->id]=$module->getDriverLabel();
				$this->driverversion[$module->id]=$module->getDriverVersion();
				// If use an external lib
				$this->liblabel[$module->id]=$module->getLibLabel();
				$this->libversion[$module->id]=$module->getLibVersion();

				$i++;
			}
		}

		return array_keys($this->driverlabel);
	}


	/**
	 *      \brief      Renvoi libelle d'un driver export
	 */
	function getDriverLabel($key)
	{
		return $this->driverlabel[$key];
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



	/**
	 *      \brief      Lance lecture fichier
	 *      \remarks    Les tableaux array_import_xxx sont deja chargees pour le bon datatoexport
	 */
	function load_file($model, $array_selected)
	{
		global $langs;

		dol_syslog("Import::load_file $model, $array_selected");

		// Creation de la classe d'export du model ImportXXX
		$dir = DOL_DOCUMENT_ROOT . "/includes/modules/import/";
		$file = "import_".$model.".modules.php";
		$classname = "Import".$model;
		require_once($dir.$file);
		$obj = new $classname($db);

		// Execute requete import
		$sql=$this->array_export_sql[0];
		$resql = $this->db->query($sql);
		if ($resql)
		{

		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Error: sql=$sql ".$this->error, LOG_ERR);
			return -1;
		}
	}

}


?>
