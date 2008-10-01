<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */
 
/**
        \file       htdocs/admin/triggers.php
        \brief      Page de configuration et activation des triggers
        \version    $Revision$
*/

require("./pre.inc.php");
include_once(DOL_DOCUMENT_ROOT ."/interfaces.class.php");

if (!$user->admin)
    accessforbidden();



if ($_GET["action"] == 'set' && $user->admin)
{
    Activate($_GET["value"]);

    Header("Location: modules.php?spe=".$_GET["spe"]);
	exit;
}

if ($_GET["action"] == 'reset' && $user->admin)
{
    UnActivate($_GET["value"]);

    Header("Location: modules.php?spe=".$_GET["spe"]);
	exit;
}


/**     \brief      Active un module
        \param      value   Nom du module a activer
*/
function Activate($value)
{
    global $db, $modules;

    $modName = $value;

    // Activation du module
    if ($modName)
    {
        $file = $modName . ".class.php";
        include_once("../includes/modules/".$file);
        $objMod = new $modName($db);
        $objMod->init();
    }

    // Activation des modules dont le module dépend
    for ($i = 0; $i < sizeof($objMod->depends); $i++)
    {
        Activate($objMod->depends[$i]);
    }

}


/**     \brief      Désactive un module
        \param      value   Nom du module a désactiver
*/
function UnActivate($value)
{
    global $db, $modules;

    $modName = $value;

    // Desactivation du module
    if ($modName)
    {
        $file = $modName . ".class.php";
        include_once("../includes/modules/".$file);
        $objMod = new $modName($db);
        $objMod->remove();
    }

    // Desactivation des modules qui dependent de lui
    for ($i = 0; $i < sizeof($objMod->requiredby); $i++)
    {
        UnActivate($objMod->requiredby[$i]);
    }

    Header("Location: modules.php");
	exit;
}



llxHeader("","");

$html = new Form($db);

print_fiche_titre($langs->trans("TriggersAvailable"),'','setup');

print "<br>\n";
print $langs->trans("TriggersDesc")."<br>";
print "<br>\n";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td colspan=\"2\">".$langs->trans("File")."</td>\n";
//print "  <td>".$langs->trans("Description")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Version")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Active")."</td>\n";
print "  <td align=\"center\">&nbsp;</td>\n";
print "</tr>\n";

// Define dir directory
$interfaces=new Interfaces($db);
$dir = $interfaces->dir;

$handle=opendir($dir);
$files = array();
$modules = array();
$orders = array();
$i = 0;
while (($file = readdir($handle))!==false)
{
    if (is_readable($dir.'/'.$file) && ereg('^interface_([^_]+)_(.+)\.class\.php',$file,$reg))
    {
        $modName = 'Interface'.ucfirst($reg[2]);
		//print "file=$file"; print "modName=$modName"; exit;
		if (in_array($modName,$modules))
		{
			$langs->load("errors");
			print '<div class="error">'.$langs->trans("Error").' : '.$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/includes/triggers/").'</div>';
			$objMod = new $modName($db);

			$modules[$i] = $modName;
			$files[$i] = $file;
			$orders[$i] = $objMod->family;   // Tri par famille
			$i++;
		}
		else
		{
			include_once($dir.'/'.$file);
			$objMod = new $modName($db);

			$modules[$i] = $modName;
			$files[$i] = $file;
			$orders[$i] = $objMod->family;   // Tri par famille
			$i++;
		}
    }
}

asort($orders);
$var=True;

// Loop on each trigger
foreach ($orders as $key => $value)
{
    $tab=split('_',$value);
    $family=$tab[0]; $numero=$tab[1];

    $modName = $modules[$key];
    if ($modName)
    {
        $objMod = new $modName($db);
    }

    $var=!$var;

	// Define disabledbyname and disabledbymodule
    $disabledbyname=0;
    $disabledbymodule=1;
	$module='';
    if (eregi('NORUN$',$files[$key])) $disabledbyname=1;
    if (eregi('^interface_([^_]+)_(.+)\.class\.php',$files[$key],$reg))
	{
		// Check if trigger file is for a particular module
		$module=eregi_replace('^mod','',$reg[1]);
		$constparam='MAIN_MODULE_'.strtoupper($module);
		if (strtolower($reg[1]) == 'all') $disabledbymodule=0;
		else if (empty($conf->global->$constparam)) $disabledbymodule=2;
	}
    
	// Show line for trigger file
    print "<tr $bc[$var]>\n";
    
    print '<td valign="top" width="14" align="center">';
    print $objMod->picto?img_object('',$objMod->picto):img_object('','generic');
    print '</td>';
    print '<td valign="top">'.$files[$key]."</td>\n";
    //print '<td valign="top">'.$objMod->getDesc()."</td>\n";
    print "<td valign=\"top\" align=\"center\">".$objMod->getVersion()."</td>\n";

    // Etat trigger
    print "<td valign=\"top\" align=\"center\">";
    if ($disabledbyname > 0 || $disabledbymodule > 1)
    {
        print "&nbsp;";
    }
    else
    {
        print img_tick();
    }
    print "</td>\n";
	
    print '<td valign="top">';
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
	print $html->textwithhelp('',$text);
	print "</td>\n";

    print "</tr>\n";
    
}
print "</table>\n";


llxFooter('$Date$ - $Revision$');
?>
