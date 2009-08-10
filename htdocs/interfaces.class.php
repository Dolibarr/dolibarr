<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
   \file		htdocs/interfaces.class.php
   \ingroup		core
   \brief		Fichier de la classe de gestion des triggers
   \version		$Id$
*/


/**
   \class      Interfaces
   \brief      Classe de la gestion des triggers
*/

class Interfaces
{
	var $dir;				// Directory with all trigger files
	var $errors=array();	// Array for errors

	/**
	*   \brief      Constructeur.
	*   \param      DB      handler d'acces base
	*/
	function Interfaces($DB)
	{
		$this->db = $DB ;
		$this->dir = DOL_DOCUMENT_ROOT . "/includes/triggers";
	}

	/**
	*   \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
	*               Cette fonction declenche tous les triggers trouves actifs.
	*   \param      action      Code de l'evenement
	*   \param      object      Objet concern
	*   \param      user        Objet user
	*   \param      lang        Objet lang
	*   \param      conf        Objet conf
	*   \return     int         Nb triggers ayant agit si pas d'erreurs, -Nb en erreur sinon.
	*/
	function run_triggers($action,$object,$user,$langs,$conf)
	{
		// Check parameters
		if (! is_object($object) || ! is_object($user) || ! is_object($langs) || ! is_object($conf))
		{
   			dol_syslog('interface::run_triggers was called with wrong parameters object='.is_object($object).' user='.is_object($user).' langs='.is_object($langs).' conf='.is_object($conf), LOG_WARNING);
		}

		$handle=opendir($this->dir);
		$modules = array();
		$nbfile = $nbtotal = $nbok = $nbko = 0;

		while (($file = readdir($handle))!==false)
		{
			if (is_readable($this->dir."/".$file) && eregi('^interface_([^_]+)_(.+)\.class\.php$',$file,$reg))
			{
				$nbfile++;

				$modName = "Interface".ucfirst($reg[2]);
				//print "file=$file"; print "modName=$modName"; exit;
				if (in_array($modName,$modules))
				{
					$langs->load("errors");
					dol_syslog("Interface::run_triggers ".$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/includes/triggers/"),LOG_ERR);
					continue;
				}

				// Check if trigger file is disabled by name
				if (eregi('NORUN$',$file))
				{
					continue;
				}
				// Check if trigger file is for a particular module
				$qualified=true;
				if (strtolower($reg[1]) != 'all')
				{
					$module=eregi_replace('^mod','',$reg[1]);
					$constparam='MAIN_MODULE_'.strtoupper($module);
					if (empty($conf->global->$constparam)) $qualified=false;
				}

				if (! $qualified)
				{
					dol_syslog("Interfaces::run_triggers Triggers for file '".$file."' need module to be enabled",LOG_INFO);
					continue;
				}

				dol_syslog("Interfaces::run_triggers Launch triggers for file '".$file."'",LOG_INFO);
				include_once($this->dir."/".$file);
				$objMod = new $modName($this->db);
				$i=0;
				if ($objMod)
				{
					$modules[$i] = $modName;
					//dol_syslog("Interfaces::run_triggers Launch triggers for file '".$file."'",LOG_INFO);
					$result=$objMod->run_trigger($action,$object,$user,$langs,$conf);
					if ($result > 0)
					{
						// Action OK
						$nbtotal++;
						$nbok++;
					}
					if ($result == 0)
					{
						// Aucune action faite
						$nbtotal++;
					}
					if ($result < 0)
					{
						// Action KO
						$nbtotal++;
						$nbko++;
						$this->errors[]=$objMod->error;
					}
					$i++;
				}
			}
		}
		if ($nbko)
		{
			dol_syslog("Interfaces::run_triggers Files found: ".$nbfile.", Files launched: ".$nbtotal.", Done: ".$nbok.", Failed: ".$nbko, LOG_ERR);
			return -$nbko;
		}
		else
		{
			//dol_syslog("Interfaces::run_triggers Files found: ".$nbfile.", Files launched: ".$nbtotal.", Done: ".$nbok.", Failed: ".$nbko, LOG_DEBUG);
			return $nbok;
		}
	}
}
?>
