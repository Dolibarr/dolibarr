<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 *   \file		    htdocs/core/class/interfaces.class.php
 *   \ingroup		core
 *   \brief			Fichier de la classe de gestion des triggers
 *   \version		$Id: interfaces.class.php,v 1.9 2011/07/31 23:45:13 eldy Exp $
 */


/**
 *    \class      Interfaces
 *    \brief      Classe de la gestion des triggers
 */

class Interfaces
{
	var $dir;				// Directory with all core and external triggers files
	var $errors	= array();	// Array for errors

   /**
	*   \brief      Constructor.
	*   \param      DB      Database handler
	*/
	function Interfaces($DB)
	{
		$this->db = $DB ;
	}

   /**
	*   \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
	*               Cette fonction declenche tous les triggers trouves actifs.
	*   \param      action      Trigger event code
	*   \param      object      Objet concern
	*   \param      user        Objet user
	*   \param      lang        Objet lang
	*   \param      conf        Objet conf
	*   \return     int         Nb of triggers ran if no error, -Nb of triggers with errors otherwise.
	*/
	function run_triggers($action,$object,$user,$langs,$conf)
	{
		// Check parameters
		if (! is_object($object) || ! is_object($conf))	// Error
		{
   			dol_syslog('interface::run_triggers was called with wrong parameters action='.$action.' object='.is_object($object).' user='.is_object($user).' langs='.is_object($langs).' conf='.is_object($conf), LOG_ERROR);
   			return -1;
		}
		if (! is_object($user) || ! is_object($langs))	// Warning
		{
   			dol_syslog('interface::run_triggers was called with wrong parameters action='.$action.' object='.is_object($object).' user='.is_object($user).' langs='.is_object($langs).' conf='.is_object($conf), LOG_WARNING);
		}

		foreach($conf->triggers_modules as $reldir)
		{
			$dir=dol_buildpath($reldir,0);
			//print "xx".$dir;exit;
						
			// Check if directory exists
			if (!is_dir($dir)) continue;

			$handle=opendir($dir);
			$modules = array();
			$nbfile = $nbtotal = $nbok = $nbko = 0;
            if (is_resource($handle))
            {
    			while (($file = readdir($handle))!==false)
    			{
    				if (is_readable($dir."/".$file) && preg_match('/^interface_([^_]+)_(.+)\.class\.php$/i',$file,$reg))
    				{
    					$nbfile++;

    					$modName = "Interface".ucfirst($reg[2]);
    					//print "file=$file"; print "modName=$modName"; exit;
    					if (in_array($modName,$modules))
    					{
    						$langs->load("errors");
    						dol_syslog("Interface::run_triggers action=".$action." ".$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/includes/triggers/"),LOG_ERR);
    						continue;
    					}

    					// Check if trigger file is disabled by name
    					if (preg_match('/NORUN$/i',$file))
    					{
    						continue;
    					}
    					// Check if trigger file is for a particular module
    					$qualified=true;
    					if (strtolower($reg[1]) != 'all')
    					{
    						$module=preg_replace('/^mod/i','',$reg[1]);
    						$constparam='MAIN_MODULE_'.strtoupper($module);
    						if (empty($conf->global->$constparam)) $qualified=false;
    					}

    					if (! $qualified)
    					{
    						dol_syslog("Interfaces::run_triggers action=".$action." Triggers for file '".$file."' need module to be enabled",LOG_INFO);
    						continue;
    					}

    					include_once($dir."/".$file);
    					$objMod = new $modName($this->db);
    					$i=0;
    					if ($objMod)
    					{
    						// Bypass if workflow module is enabled and if the trigger asked to be disable in such case
    						if (! empty($conf->workflow->enabled) && ! empty($objMod->disabled_if_workflow))
    						{
    							dol_syslog("Interfaces::run_triggers action=".$action." Bypass triggers for file '".$file."'",LOG_INFO);
    							continue;
    						}

    						dol_syslog("Interfaces::run_triggers action=".$action." Launch triggers for file '".$file."'",LOG_INFO);

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
    					else
    					{
    						dol_syslog("Interfaces::run_triggers action=".$action." Failed to instantiate trigger for file '".$file."'",LOG_ERROR);
    					}
    				}
    			}
    			closedir($handle);
            }
		}

		if ($nbko)
		{
			dol_syslog("Interfaces::run_triggers action=".$action." Files found: ".$nbfile.", Files launched: ".$nbtotal.", Done: ".$nbok.", Failed: ".$nbko, LOG_ERR);
			return -$nbko;
		}
		else
		{
			//dol_syslog("Interfaces::run_triggers Files found: ".$nbfile.", Files launched: ".$nbtotal.", Done: ".$nbok.", Failed: ".$nbko, LOG_DEBUG);
			return $nbok;
		}
	}

   /**
	*   Return list of triggers. Function used by admin page htdoc/admin/triggers
	* 	@param		workflow		0=Return all triggers, 1=Return only triggers not disabled if workflow module activated
	* 	@return		array			Array list of triggers
	*/
	function getTriggersList($workflow=0)
	{
		global $conf, $langs;

		$html = new Form($this->db);

		$files = array();
		$modules = array();
		$orders = array();
		$i = 0;

		foreach($conf->triggers_modules as $reldir)
		{
			$dir=dol_buildpath($reldir,0);
			//print "xx".$dir;exit;
			
			// Check if directory exists
			if (!is_dir($dir)) continue;

			$handle=opendir($dir);
            if (is_resource($handle))
            {
    			while (($file = readdir($handle))!==false)
    			{
    				if (is_readable($dir.'/'.$file) && preg_match('/^interface_([^_]+)_(.+)\.class\.php/',$file,$reg))
    				{
    					$modName = 'Interface'.ucfirst($reg[2]);
    					//print "file=$file"; print "modName=$modName"; exit;
    					if (in_array($modName,$modules))
    					{
    						$langs->load("errors");
    						print '<div class="error">'.$langs->trans("Error").' : '.$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/includes/triggers/").'</div>';
    						$objMod = new $modName($this->db);

    						$modules[$i] = $modName;
    						$files[$i] = $file;
    						$orders[$i] = $objMod->family;   // Tri par famille
    						$i++;
    					}
    					else
    					{
    						include_once($dir.'/'.$file);
    						$objMod = new $modName($this->db);

    						$modules[$i] = $modName;
    						$files[$i] = $file;
    						$orders[$i] = $objMod->family;   // Tri par famille
    						$i++;
    					}
    				}
    			}
    			closedir($handle);
            }
		}

		asort($orders);

		$triggers = array();
		$j = 0;

		// Loop on each trigger
		foreach ($orders as $key => $value)
		{
			$modName = $modules[$key];
			if ($modName)
			{
				$objMod = new $modName($this->db);
				// Bypass if workflow module is enabled and if the trigger is compatible
				if ($workflow && ! empty($objMod->disabled_if_workflow)) continue;
			}

			// Define disabledbyname and disabledbymodule
			$disabledbyname=0;
			$disabledbymodule=1;
			$module='';
			if (preg_match('/NORUN$/i',$files[$key])) $disabledbyname=1;
			if (preg_match('/^interface_([^_]+)_(.+)\.class\.php/i',$files[$key],$reg))
			{
				// Check if trigger file is for a particular module
				$module=preg_replace('/^mod/i','',$reg[1]);
				$constparam='MAIN_MODULE_'.strtoupper($module);
				if (strtolower($reg[1]) == 'all') $disabledbymodule=0;
				else if (empty($conf->global->$constparam)) $disabledbymodule=2;
			}

			$triggers[$j]['picto'] = $objMod->picto?img_object('',$objMod->picto):img_object('','generic');
			$triggers[$j]['file'] = $files[$key];
			$triggers[$j]['version'] = $objMod->getVersion();
			$triggers[$j]['status'] = img_tick();
			if ($disabledbyname > 0 || $disabledbymodule > 1) $triggers[$j]['status'] = "&nbsp;";

			$text ='<b>'.$langs->trans("Description").':</b><br>';
			$text.=$objMod->getDesc().'<br>';
			$text.='<br><b>'.$langs->trans("Status").':</b><br>';
			if ($disabledbyname == 1)
			{
				$text.=$langs->trans("TriggerDisabledByName").'<br>';
				if ($disabledbymodule == 2) $text.=$langs->trans("TriggerDisabledAsModuleDisabled",$module).'<br>';
			}
			else
			{
				if ($disabledbymodule == 0) $text.=$langs->trans("TriggerAlwaysActive").'<br>';
				if ($disabledbymodule == 1) $text.=$langs->trans("TriggerActiveAsModuleActive",$module).'<br>';
				if ($disabledbymodule == 2) $text.=$langs->trans("TriggerDisabledAsModuleDisabled",$module).'<br>';
			}

			$triggers[$j]['info'] = $html->textwithpicto('',$text);
			$j++;
		}
		return $triggers;
	}

}
?>
