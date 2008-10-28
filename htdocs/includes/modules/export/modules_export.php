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
 \file       htdocs/includes/modules/export/modules_export.php
 \ingroup    export
 \brief      Fichier contenant la classe mère de generation des exports
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');


/**
 \class      ModeleExports
 \brief      Classe mère des modèles de format d'export
 */

class ModeleExports
{
	var $error='';

	var $driverlabel;
	var $driverversion;

	var $libabel;
	var $libversion;


	/**
	 *      \brief      Constructeur
	 */
	function ModeleExports()
	{
	}

	/**
	 *      \brief      Charge en memoire et renvoie la liste des modèles actifs
	 *      \param      db      Handler de base
	 */
	function liste_modeles($db)
	{
		dolibarr_syslog("ModeleExport::loadFormat");

		$dir=DOL_DOCUMENT_ROOT."/includes/modules/export/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers exports disponibles
		$var=True;
		$i=0;
		while (($file = readdir($handle))!==false)
		{
			if (eregi("^export_(.*)\.modules\.php",$file,$reg))
			{
				$moduleid=$reg[1];

				// Chargement de la classe
				$file = $dir."/export_".$moduleid.".modules.php";
				$classname = "Export".ucfirst($moduleid);

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
	 *      \brief      Lance la generation du fichier
	 *      \remarks    Les tableaux array_export_xxx sont déjà chargées pour le bon datatoexport
	 *                  aussi le parametre datatoexport est inutilisé
	 */
	function build_file($model, $datatoexport, $array_selected)
	{
		global $langs;

		dolibarr_syslog("Export::build_file $model, $datatoexport, $array_selected");

		// Creation de la classe d'export du model ExportXXX
		$dir = DOL_DOCUMENT_ROOT . "/includes/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once($dir.$file);
		$obj = new $classname($db);

		// Execute requete export
		$sql=$this->array_export_sql[0];
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// Genere en-tete
			$obj->write_header();

			// Genere ligne de titre
			$obj->write_title();

			while ($objp = $this->db->fetch_object($resql))
			{
				$var=!$var;
				$obj->write_record($objp,$array_selected);
			}

			// Genere en-tete
			$obj->write_footer();
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Error: sql=$sql ".$this->error);
			return -1;
		}
	}

}


?>
