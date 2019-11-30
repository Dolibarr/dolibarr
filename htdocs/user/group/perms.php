<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/user/group/perms.php
 *       \brief      Onglet user et permissions de la fiche utilisateur
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'admin'));

$id=GETPOST('id', 'int');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$module=GETPOST('module', 'alpha');
$rights=GETPOST('rights', 'int');
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'groupperms';   // To manage different context of search

// Defini si peux lire les permissions
$canreadperms=($user->admin || $user->rights->user->user->lire);
// Defini si peux modifier les permissions
$caneditperms=($user->admin || $user->rights->user->user->creer);
// Advanced permissions
$advancedpermsactive=false;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $advancedpermsactive=true;
    $canreadperms=($user->admin || ($user->rights->user->group_advance->read && $user->rights->user->group_advance->readperms));
    $caneditperms=($user->admin || $user->rights->user->group_advance->write);
}

if (! $canreadperms) accessforbidden();

$object = new Usergroup($db);
$object->fetch($id);

$entity=$conf->entity;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('groupperms','globalcard'));


/**
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'addrights' && $caneditperms)
	{
		$editgroup = new Usergroup($db);
		$result=$editgroup->fetch($id);
		if ($result > 0)
		{
			$editgroup->addrights($rights, $module, '', $entity);
		}
	}

	if ($action == 'delrights' && $caneditperms)
	{
		$editgroup = new Usergroup($db);
		$result=$editgroup->fetch($id);
		if ($result > 0)
		{
			$editgroup->delrights($rights, $module, '', $entity);
		}
	}
}


/**
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("Permissions"));

if ($object->id > 0)
{
	/*
     * Affichage onglets
     */
	$object->getrights();	// Reload permission

    $head = group_prepare_head($object);
    $title = $langs->trans("Group");
    dol_fiche_head($head, 'rights', $title, -1, 'group');

    // Charge les modules soumis a permissions
    $modules = array();
    $modulesdir = dolGetModulesDirs();

    $db->begin();

    foreach ($modulesdir as $dir)
    {
        // Load modules attributes in arrays (name, numero, orders) from dir directory
        //print $dir."\n<br>";
        $handle=@opendir(dol_osencode($dir));
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
                {
                    $modName = substr($file, 0, dol_strlen($file) - 10);

                    if ($modName)
                    {
                        include_once $dir."/".$file;
                        $objMod = new $modName($db);
                        // Load all lang files of module
                        if (isset($objMod->langfiles) && is_array($objMod->langfiles))
                        {
                            foreach($objMod->langfiles as $domain)
                            {
                                $langs->load($domain);
                            }
                        }
                        // Load all permissions
                        if ($objMod->rights_class)
                        {
                            $ret=$objMod->insert_permissions(0, $entity);
                            $modules[$objMod->rights_class]=$objMod;
                        }
                    }
                }
            }
        }
    }

    $db->commit();

    // Lecture des droits groupes
    $permsgroupbyentity = array();

    $sql = "SELECT DISTINCT r.id, r.libelle, r.module, gr.entity";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r,";
    $sql.= " ".MAIN_DB_PREFIX."usergroup_rights as gr";
    $sql.= " WHERE gr.fk_id = r.id";
    $sql.= " AND gr.entity = ".$entity;
    $sql.= " AND gr.fk_usergroup = ".$object->id;

    dol_syslog("get user perms", LOG_DEBUG);
    $result=$db->query($sql);
    if ($result)
    {
    	$num = $db->num_rows($result);
    	$i = 0;
    	while ($i < $num)
    	{
    		$obj = $db->fetch_object($result);
    		if (! isset($permsgroupbyentity[$obj->entity]))
    			$permsgroupbyentity[$obj->entity] = array();
    			array_push($permsgroupbyentity[$obj->entity], $obj->id);
    			$i++;
    	}
    	$db->free($result);
    }
    else
    {
    	dol_print_error($db);
    }

    $linkback = '<a href="'.DOL_URL_ROOT.'/user/group/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    /*
     * Ecran ajout/suppression permission
     */

    print '<table class="border centpercent tableforfield">';

    // Name (already in dol_banner, we keep it to have the GlobalGroup picto, but we should move it in dol_banner)
    if (! empty($conf->mutlicompany->enabled))
    {
        print '<tr><td class="titlefield">'.$langs->trans("Name").'</td>';
        print '<td colspan="2">'.$object->name.'';
        if (! $object->entity)
        {
            print img_picto($langs->trans("GlobalGroup"), 'redstar');
        }
        print "</td></tr>\n";
    }

    // Note
    print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td>';
    print '<td class="valeur">'.dol_htmlentitiesbr($object->note).'</td>';
    print "</tr>\n";

    print '</table><br>';

    if ($user->admin) print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));

    $parameters=array();
    $reshook=$hookmanager->executeHooks('insertExtraHeader', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Module").'</td>';
    if ($caneditperms)
    {
    	print '<td class="center nowrap">';
    	print '<a class="reposition" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;module=allmodules">'.$langs->trans("All")."</a>";
    	print '/';
    	print '<a class="reposition" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delrights&amp;entity='.$entity.'&amp;module=allmodules">'.$langs->trans("None")."</a>";
    	print '</td>';
    }
    print '<td class="center" width="24">&nbsp;</td>';
    print '<td>'.$langs->trans("Permissions").'</td>';
    print '</tr>';

    $sql = "SELECT r.id, r.libelle, r.module";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
    $sql.= " AND r.entity = " . $entity;
    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql.= " AND r.perms NOT LIKE '%_advance'";  // Hide advanced perms if option is disable
    $sql.= " ORDER BY r.module, r.id";

    $result=$db->query($sql);
    if ($result)
    {
        $i = 0;
        $oldmod = '';

        $num = $db->num_rows($result);

        while ($i < $num)
        {
            $obj = $db->fetch_object($result);

            // Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
            if (empty($modules[$obj->module]))
            {
                $i++;
                continue;
            }

            if ($oldmod <> $obj->module)
            {
                $oldmod = $obj->module;

                // Rupture detectee, on recupere objMod
                $objMod = $modules[$obj->module];
                $picto=($objMod->picto?$objMod->picto:'generic');

                print '<tr class="oddeven trforbreak">';
                print '<td class="nowrap">'.img_object('', $picto, 'class="inline-block pictoobjectwidth"').' '.$objMod->getName();
                print '<a name="'.$objMod->getName().'">&nbsp;</a></td>';
                print '<td class="center nowrap">';
                if ($caneditperms)
                {
                	print '<a title='.$langs->trans("All").' alt='.$langs->trans("All").' href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("All")."</a>";
                    print '/';
                    print '<a title='.$langs->trans("None").' alt='.$langs->trans("None").' href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delrights&amp;entity='.$entity.'&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("None")."</a>";
                }
                print '</td>';
                print '<td colspan="2">&nbsp;</td>';
                print '</tr>';
            }

            print '<tr class="oddeven">';

            // Module
            print '<td class="nowrap">'.img_object('', $picto, 'class="inline-block pictoobjectwidth"').' '.$objMod->getName().'</td>';

            if (is_array($permsgroupbyentity[$entity]))
            {
            	if (in_array($obj->id, $permsgroupbyentity[$entity]))
            	{
            		// Own permission by group
            		if ($caneditperms)
            		{
            			print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delrights&amp;entity='.$entity.'&amp;rights='.$obj->id.'">'.img_edit_remove($langs->trans("Remove")).'</a></td>';
            		}
            		print '<td class="center">';
            		print img_picto($langs->trans("Active"), 'tick');
            		print '</td>';
            	}
            	else
            	{
            		// Do not own permission
            		if ($caneditperms)
            		{
            			print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;rights='.$obj->id.'">'.img_edit_add($langs->trans("Add")).'</a></td>';
            		}
            		print '<td>&nbsp</td>';
            	}
            }
            else
            {
            	// Do not own permission
            	if ($caneditperms)
            	{
            		print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;rights='.$obj->id.'">'.img_edit_add($langs->trans("Add")).'</a></td>';
            	}
            	print '<td>&nbsp</td>';
            }

            $perm_libelle=($conf->global->MAIN_USE_ADVANCED_PERMS && ($langs->trans("PermissionAdvanced".$obj->id)!=("PermissionAdvanced".$obj->id))?$langs->trans("PermissionAdvanced".$obj->id):(($langs->trans("Permission".$obj->id)!=("Permission".$obj->id))?$langs->trans("Permission".$obj->id):$langs->trans($obj->libelle)));
            print '<td>'.$perm_libelle. '</td>';

            print '</tr>';

            $i++;
        }
    }
    print '</table>';

    print '</div>';

    $parameters=array();
    $reshook=$hookmanager->executeHooks('insertExtraFooter', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
