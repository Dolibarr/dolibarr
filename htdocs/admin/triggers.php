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
        include_once("../includes/modules/$file");
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
        include_once("../includes/modules/$file");
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


print_fiche_titre($langs->trans("TriggersAvailable"),'','setup');

print $langs->trans("TriggersDesc")."<br>";
print "<br>\n";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td colspan=\"2\">".$langs->trans("File")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Version")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Activated")."</td>\n";
//print "  <td align=\"center\">".$langs->trans("Action")."</td>\n";
print "  <td align=\"center\">&nbsp;</td>\n";
print "</tr>\n";


$dir = DOL_DOCUMENT_ROOT . "/includes/triggers/";

$handle=opendir($dir);
$files = array();
$modules = array();
$orders = array();
$i = 0;
while (($file = readdir($handle))!==false)
{
    if (is_readable($dir.$file) && ereg('^interface_(.*)\.class\.php',$file,$reg))
    {
        $modName = 'Interface'.ucfirst($reg[1]);
        if ($modName)
        {
	        if (in_array($modName,$modules))
			{
				$langs->load("errors");
        		print '<div class="error">'.$langs->trans("Error").' : '.$langs->trans("ErrorDuplicateTrigger",$modName,"/htdocs/includes/triggers/").'</div>';
	            $objMod = new $modName($db);
	
	            $modules[$i] = $modName;
	            $files[$i] = $file;
	            $orders[$i] = "$objMod->family";   // Tri par famille
	            $i++;
			}
			else
			{
	            include_once($dir.$file);
	            $objMod = new $modName($db);
	
	            $modules[$i] = $modName;
	            $files[$i] = $file;
	            $orders[$i] = "$objMod->family";   // Tri par famille
	            $i++;
			}
        }
    }
}

asort($orders);
$var=True;

foreach ($orders as $key => $value)
{
    $tab=split('_',$value);
    $family=$tab[0]; $numero=$tab[1];

    $modName = $modules[$key];
    if ($modName)
    {
        $objMod = new $modName($db);
    }

    $const_name = $objMod->const_name;

    $var=!$var;
    
    print "<tr $bc[$var]>\n";
    
    print '<td valign="top" width="14" align="center">';
    print $objMod->picto?img_object('',$objMod->picto):img_object('','generic');
    print '</td>';
    print '<td valign="top">'.$files[$key]."</td>\n";
    print '<td valign="top">'.$objMod->getDesc()."</td>\n";
    print "<td valign=\"top\" align=\"center\">".$objMod->getVersion()."</td>\n";

    // \todo Activation trigger
    print "<td valign=\"top\" align=\"center\">";
    $statut_trigger=1;
    if (eregi('NORUN$',$files[$key])) $statut_trigger=0;
    
    if ($statut_trigger == 1)
    {
        print img_tick();
    }
    else
    {
        print "&nbsp;";
    }
    
    print "</td>\n";
	
/*
        print "<td valign=\"top\" align=\"center\">";
        if ($const_value == 1)
	  {
            // Module actif
            print "<a href=\"modules.php?id=".$objMod->numero."&amp;action=reset&amp;value=" . $modName . "&amp;spe=" . $_GET["spe"] . "\">" . $langs->trans("Disable") . "</a></td>\n";
        }
        else
	  {
            // Module non actif
            print "<a href=\"modules.php?id=".$objMod->numero."&amp;action=set&amp;value=" . $modName . "&amp;spe=" . $_GET["spe"] . "\">" . $langs->trans("Activate") . "</a></td>\n";
	  }
*/
    print "<td>&nbsp;</td>\n";	
    print "</tr>\n";
    
}
print "</table>\n";


llxFooter('$Date$ - $Revision$');
?>
