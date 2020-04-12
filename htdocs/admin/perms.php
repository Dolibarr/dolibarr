<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/admin/perms.php
 *      \ingroup    core
 *		\brief      Page d'administration/configuration des permissions par defaut
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'users', 'other'));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) accessforbidden();

$entity = $conf->entity;


/*
 * Actions
 */

if ($action == 'add')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=1";
    $sql .= " WHERE id = ".GETPOST("pid", 'int');
    $sql .= " AND entity = ".$conf->entity;
    $db->query($sql);
}

if ($action == 'remove')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=0";
    $sql .= " WHERE id = ".GETPOST('pid', 'int');
    $sql .= " AND entity = ".$conf->entity;
    $db->query($sql);
}


/*
 * View
 */

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("DefaultRights"), $wikihelp);

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("DefaultRightsDesc")." ".$langs->trans("OnlyActiveElementsAreShown")."</span><br><br>\n";

$db->begin();

// Search all modules with permission and reload permissions def.
$modules = array();
$modulesdir = dolGetModulesDirs();

foreach ($modulesdir as $dir)
{
	$handle = @opendir(dol_osencode($dir));
	if (is_resource($handle))
	{
		while (($file = readdir($handle)) !== false)
		{
			if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php')
			{
				$modName = substr($file, 0, dol_strlen($file) - 10);
				if ($modName)
				{
					include_once $dir.$file;
					$objMod = new $modName($db);

					// Load all lang files of module
					if (isset($objMod->langfiles) && is_array($objMod->langfiles))
					{
						foreach ($objMod->langfiles as $domain)
						{
							$langs->load($domain);
						}
					}
					// Load all permissions
					if ($objMod->rights_class)
					{
						$ret = $objMod->insert_permissions(0, $entity);
						$modules[$objMod->rights_class] = $objMod;
						//print "modules[".$objMod->rights_class."]=$objMod;";
					}
				}
			}
		}
	}
}

$db->commit();

$head = security_prepare_head();

dol_fiche_head($head, 'default', $langs->trans("Security"), -1);


// Show warning about external users
print info_admin(showModulesExludedForExternal($modules)).'<br>'."\n";

print "\n";
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td class="center">&nbsp;</td>';
print '<td class="center">'.$langs->trans("Default").'</td>';
print '<td>'.$langs->trans("Permissions").'</td>';
print '</tr>'."\n";

//print "xx".$conf->global->MAIN_USE_ADVANCED_PERMS;
$sql = "SELECT r.id, r.libelle as label, r.module, r.module_position, r.perms, r.subperms, r.bydefault";
$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql .= " WHERE r.libelle NOT LIKE 'tou%'"; // On ignore droits "tous"
$sql .= " AND r.entity = ".$entity;
if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql .= " AND r.perms NOT LIKE '%_advance'"; // Hide advanced perms if option is not enabled
$sql .= " ORDER BY r.family_position, r.module_position, r.module, r.id";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $oldmod = '';

    while ($i < $num)
    {
        $obj = $db->fetch_object($result);

        // If line is for a module that doe snot existe anymore (absent of includes/module), we ignore it
        if (empty($modules[$obj->module]))
        {
            $i++;
            continue;
        }

        // Save field module_position in database if value is still zero
        if (empty($obj->module_position))
        {
        	if (is_object($modules[$obj->module]) && ($modules[$obj->module]->module_position > 0))
        	{
        		// TODO Define familyposition
        		$family = $modules[$obj->module]->family_position;
        		$familyposition = 0;
        		$sqlupdate = 'UPDATE '.MAIN_DB_PREFIX."rights_def SET module_position = ".$modules[$obj->module]->module_position.",";
        		$sqlupdate .= " family_position = ".$familyposition;
        		$sqlupdate .= " WHERE module_position = 0 AND module = '".$db->escape($obj->module)."'";
        		$db->query($sqlupdate);
        	}
        }

        // Check if permission we found is inside a module definition. If not, we discard it.
        $found = false;
        foreach ($modules[$obj->module]->rights as $key => $val)
        {
        	$rights_class = $objMod->rights_class;
        	if ($val[4] == $obj->perms && (empty($val[5]) || $val[5] == $obj->subperms))
        	{
        		$found = true;
        		break;
        	}
        }
		if (!$found)
		{
			$i++;
			continue;
		}

        // Break found, it's a new module to catch
		if (isset($obj->module) && ($oldmod <> $obj->module))
        {
        	$oldmod = $obj->module;

        	// Break detected, we get objMod
            $objMod = $modules[$obj->module];
            $picto = ($objMod->picto ? $objMod->picto : 'generic');

            // Show break line
            print '<tr class="oddeven trforbreak">';
            print '<td class="maxwidthonsmartphone tdoverflowonsmartphone">';
            print img_object('', $picto, 'class="pictoobjectwidth paddingright"').' '.$objMod->getName();
            print '<a name="'.$objMod->getName().'"></a>';
            print '</td>';
           	print '<td>&nbsp;</td>';
            print '<td>&nbsp;</td>';
            print '<td>&nbsp;</td>';
            print '</tr>'."\n";
        }

        $perm_libelle = ($conf->global->MAIN_USE_ADVANCED_PERMS && ($langs->trans("PermissionAdvanced".$obj->id) != ("PermissionAdvanced".$obj->id)) ? $langs->trans("PermissionAdvanced".$obj->id) : (($langs->trans("Permission".$obj->id) != ("Permission".$obj->id)) ? $langs->trans("Permission".$obj->id) : $obj->label));

        print '<tr class="oddeven">';

        // Picto and label of module
        print '<td class="maxwidthonsmartphone tdoverflowonsmartphone">';
		print '</td>';

		// Tick
		if ($obj->bydefault == 1)
		{
			print '<td>';
			print '<a class="reposition" href="perms.php?pid='.$obj->id.'&amp;action=remove">'.img_edit_remove().'</a>';
			print '</td>';
			print '<td class="center">';
			print img_picto($langs->trans("Active"), 'tick');
			print '</td>';
		}
		else
		{
			print '<td>';
			print '<a class="reposition" href="perms.php?pid='.$obj->id.'&amp;action=add">'.img_edit_add().'</a>';
			print '</td>';
			print '<td class="center">';
			print '&nbsp;';
			print '</td>';
		}

		// Permission and tick
        print '<td>'.$perm_libelle.'</td>';

        print '</tr>'."\n";

        $i++;
    }
}
else dol_print_error($db);
print '</table>';
print '</div>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
