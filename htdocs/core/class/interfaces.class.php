<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@capnetworks.com>
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
 */

/**
 *   \file		    htdocs/core/class/interfaces.class.php
 *   \ingroup		core
 *   \brief			Fichier de la classe de gestion des triggers
 */


/**
 *   Class to manage triggers
 */

class Interfaces
{
    var $dir;				// Directory with all core and external triggers files
    var $errors	= array();	// Array for errors

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *   Function called when a Dolibarr business event occurs
     *   This function call all qualified triggers.
     *
     *   @param		string		$action     Trigger event code
     *   @param     Object		$object     Objet concern
     *   @param     User		$user       Objet user
     *   @param     Lang		$langs      Objet lang
     *   @param     Conf		$conf       Objet conf
     *   @return    int         			Nb of triggers ran if no error, -Nb of triggers with errors otherwise.
     */
    function run_triggers($action,$object,$user,$langs,$conf)
    {
        // Check parameters
        if (! is_object($object) || ! is_object($conf))	// Error
        {
            dol_syslog(get_class($this).'::run_triggers was called with wrong parameters action='.$action.' object='.is_object($object).' user='.is_object($user).' langs='.is_object($langs).' conf='.is_object($conf), LOG_ERR);
            return -1;
        }
        if (! is_object($user) || ! is_object($langs))	// Warning
        {
            dol_syslog(get_class($this).'::run_triggers was called with wrong parameters action='.$action.' object='.is_object($object).' user='.is_object($user).' langs='.is_object($langs).' conf='.is_object($conf), LOG_WARNING);
        }

        $nbfile = $nbtotal = $nbok = $nbko = 0;

        $files = array();
        $modules = array();
        $orders = array();
		$i=0;

		$dirtriggers=array_merge(array('/core/triggers'),$conf->modules_parts['triggers']);
        foreach($dirtriggers as $reldir)
        {
            $dir=dol_buildpath($reldir,0);
            $newdir=dol_osencode($dir);
            //print "xx".$dir;exit;

            // Check if directory exists (we do not use dol_is_dir to avoir loading files.lib.php at each call)
            if (! is_dir($newdir)) continue;

            $handle=opendir($newdir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    if (is_readable($newdir."/".$file) && preg_match('/^interface_([0-9]+)_([^_]+)_(.+)\.class\.php$/i',$file,$reg))
                    {
						$part1=$reg[1];
						$part2=$reg[2];
						$part3=$reg[3];

                        $nbfile++;

                        $modName = "Interface".ucfirst($reg[3]);
                        //print "file=$file"; print "modName=$modName"; exit;
                        if (in_array($modName,$modules))
                        {
                            $langs->load("errors");
                            dol_syslog(get_class($this)."::run_triggers action=".$action." ".$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/core/triggers/"),LOG_ERR);
                            continue;
                        }
                        else
                        {
                            include_once $newdir.'/'.$file;
                        }

                        // Check if trigger file is disabled by name
                        if (preg_match('/NORUN$/i',$file)) continue;
                        // Check if trigger file is for a particular module
                        $qualified=true;
                        if (strtolower($reg[2]) != 'all')
                        {
                            $module=preg_replace('/^mod/i','',$reg[2]);
                            $constparam='MAIN_MODULE_'.strtoupper($module);
                            if (empty($conf->global->$constparam)) $qualified=false;
                        }

                        if (! $qualified)
                        {
                            dol_syslog(get_class($this)."::run_triggers action=".$action." Triggers for file '".$file."' need module to be enabled",LOG_INFO);
                            continue;
                        }

                        $modules[$i] = $modName;
                        $files[$i] = $file;
                        $orders[$i] = $part1.'_'.$part2.'_'.$part3;   // Set sort criteria value

                        $i++;
                    }
                }
            }
        }

        asort($orders);

        // Loop on each trigger
        foreach ($orders as $key => $value)
        {
            $modName = $modules[$key];
            if (empty($modName)) continue;

            $objMod = new $modName($this->db);
            if ($objMod)
            {
                dol_syslog(get_class($this)."::run_triggers action=".$action." Launch triggers for file '".$files[$key]."'",LOG_INFO);

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
            }
            else
            {
                dol_syslog(get_class($this)."::run_triggers action=".$action." Failed to instantiate trigger for file '".$files[$key]."'",LOG_ERR);
            }
        }

        if ($nbko)
        {
            dol_syslog(get_class($this)."::run_triggers action=".$action." Files found: ".$nbfile.", Files launched: ".$nbtotal.", Done: ".$nbok.", Failed: ".$nbko, LOG_ERR);
            return -$nbko;
        }
        else
        {
            //dol_syslog(get_class($this)."::run_triggers Files found: ".$nbfile.", Files launched: ".$nbtotal.", Done: ".$nbok.", Failed: ".$nbko, LOG_DEBUG);
            return $nbok;
        }
    }

    /**
     *  Return list of triggers. Function used by admin page htdoc/admin/triggers.
     *  List is sorted by trigger filename so by priority to run.
     *
     * 	@return	array					Array list of triggers
     */
    function getTriggersList()
    {
        global $conf, $langs;

        $files = array();
        $modules = array();
        $orders = array();
        $i = 0;

        $dirtriggers=array_merge(array('/core/triggers/'),$conf->modules_parts['triggers']);
        foreach($dirtriggers as $reldir)
        {
            $dir=dol_buildpath($reldir,0);
            $newdir=dol_osencode($dir);
            //print "xx".$dir;exit;

            // Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php at each call)
            if (! is_dir($newdir)) continue;

            $handle=opendir($newdir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    if (is_readable($newdir.'/'.$file) && preg_match('/^interface_([0-9]+)_([^_]+)_(.+)\.class\.php/',$file,$reg))
                    {
						$part1=$reg[1];
						$part2=$reg[2];
						$part3=$reg[3];

                        $modName = 'Interface'.ucfirst($reg[3]);
                        //print "file=$file"; print "modName=$modName"; exit;
                        if (in_array($modName,$modules))
                        {
                            $langs->load("errors");
                            print '<div class="error">'.$langs->trans("Error").' : '.$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/core/triggers/").'</div>';
                        }
                        else
                        {
                            include_once $newdir.'/'.$file;
                        }

                        $files[$i] = $file;
                        $modules[$i] = $modName;
                        $orders[$i] = $part1.'_'.$part2.'_'.$part3;   // Set sort criteria value

                        $i++;
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
            if (empty($modName)) continue;

            $objMod = new $modName($this->db);

            // Define disabledbyname and disabledbymodule
            $disabledbyname=0;
            $disabledbymodule=1;
            $module='';

            // Check if trigger file is disabled by name
            if (preg_match('/NORUN$/i',$files[$key])) $disabledbyname=1;
            // Check if trigger file is for a particular module
            if (preg_match('/^interface_([0-9]+)_([^_]+)_(.+)\.class\.php/i',$files[$key],$reg))
            {
                $module=preg_replace('/^mod/i','',$reg[2]);
                $constparam='MAIN_MODULE_'.strtoupper($module);
                if (strtolower($reg[2]) == 'all') $disabledbymodule=0;
                else if (empty($conf->global->$constparam)) $disabledbymodule=2;
            }

			// We set info of modules
            $triggers[$j]['picto'] = $objMod->picto?img_object('',$objMod->picto):img_object('','generic');
            $triggers[$j]['file'] = $files[$key];
            $triggers[$j]['version'] = $objMod->getVersion();
            $triggers[$j]['status'] = img_picto($langs->trans("Active"),'tick');
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

            $triggers[$j]['info'] = $text;
            $j++;
        }
        return $triggers;
    }

}
?>
