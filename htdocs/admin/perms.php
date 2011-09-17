<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *   	\file       htdocs/admin/perms.php
 *      \ingroup    core
 *		\brief      Page d'administration/configuration des permissions par defaut
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("users");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


if ($_GET["action"] == 'add')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=1";
    $sql.= " WHERE id = ".$_GET["pid"];
    $sql.= " AND entity = ".$conf->entity;
    $db->query($sql);
}

if ($_GET["action"] == 'remove')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=0";
    $sql.= " WHERE id = ".$_GET["pid"];
    $sql.= " AND entity = ".$conf->entity;
    $db->query($sql);
}


/*
 * View
 */

llxHeader('',$langs->trans("DefaultRights"));

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("DefaultRightsDesc");
print " ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";
print "<br>\n";

$head=security_prepare_head();

dol_fiche_head($head, 'default', $langs->trans("Security"));


print '<table class="noborder" width="100%">';


$db->begin();

// Charge les modules soumis a permissions
$modules = array();
$modulesdir = array();

foreach ($conf->file->dol_document_root as $type => $dirroot)
{
	$modulesdir[] = $dirroot . "/includes/modules/";

	if ($type == 'alt')
	{
		$handle=@opendir($dirroot);
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
			    if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
			    {
			    	if (is_dir($dirroot . '/' . $file . '/includes/modules/'))
			    	{
			    		$modulesdir[] = $dirroot . '/' . $file . '/includes/modules/';
			    	}
			    }
			}
			closedir($handle);
		}
	}
}

foreach ($modulesdir as $dir)
{
	// Load modules attributes in arrays (name, numero, orders) from dir directory
	//print $dir."\n<br>";
	$handle=@opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
		    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php')
		    {
		        $modName = substr($file, 0, dol_strlen($file) - 10);

		        if ($modName)
		        {
		            include_once($dir."/".$file);
		            $objMod = new $modName($db);
		            if ($objMod->rights_class) {

		                $ret=$objMod->insert_permissions(0);

		                $modules[$objMod->rights_class]=$objMod;
		                //print "modules[".$objMod->rights_class."]=$objMod;";
		            }
		        }
		    }
		}
	}
}

$db->commit();


// Affiche lignes des permissions
$sql = "SELECT r.id, r.libelle, r.module, r.perms, r.subperms, r.bydefault";
$sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
$sql.= " AND entity = ".$conf->entity;
if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql.= " AND r.perms NOT LIKE '%_advance'";  // Hide advanced perms if option is disable
$sql.= " ORDER BY r.module, r.id";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $var=True;
    $old = "";
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);

        // Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
        if (! $modules[$obj->module])
        {
            $i++;
            continue;
        }

        // Check if permission is inside module definition
        // TODO If not, we remove it
        /*foreach($objMod->rights as $key => $val)
        {
        }*/

        // Break found, it's a new module to catch
        if ($old <> $obj->module)
        {
            $objMod=$modules[$obj->module];
            $picto=($objMod->picto?$objMod->picto:'generic');

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Module").'</td>';
            print '<td>'.$langs->trans("Permission").'</td>';
            print '<td align="center">'.$langs->trans("Default").'</td>';
            print '<td align="center">&nbsp;</td>';
            print "</tr>\n";
            $old = $obj->module;
        }

        $var=!$var;
        print '<tr '. $bc[$var].'>';

        print '<td>'.img_object('',$picto).' '.$objMod->getName();
        print '<a name="'.$objMod->getName().'">&nbsp;</a>';

        $perm_libelle=($conf->global->MAIN_USE_ADVANCED_PERMS && ($langs->trans("PermissionAdvanced".$obj->id)!=("PermissionAdvanced".$obj->id))?$langs->trans("PermissionAdvanced".$obj->id):(($langs->trans("Permission".$obj->id)!=("Permission".$obj->id))?$langs->trans("Permission".$obj->id):$obj->libelle));
        print '<td>'.$perm_libelle. '</td>';

        print '<td align="center">';
        if ($obj->bydefault == 1)
        {
            print img_picto($langs->trans("Active"),'tick');
            print '</td><td>';
            print '<a href="perms.php?pid='.$obj->id.'&amp;action=remove#'.$objMod->getName().'">'.img_edit_remove().'</a>';
        }
        else
        {
            print '&nbsp;';
            print '</td><td>';
            print '<a href="perms.php?pid='.$obj->id.'&amp;action=add#'.$objMod->getName().'">'.img_edit_add().'</a>';
        }

        print '</td></tr>';
        $i++;
    }
}

print '</table>';

print '</div>';


$db->close();

llxFooter();
?>
