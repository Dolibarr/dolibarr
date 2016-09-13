<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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

$langs->load("users");
$langs->load("admin");

$id=GETPOST('id','int');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$module=GETPOST('module', 'alpha');
$rights=GETPOST('rights', 'int');

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


/**
 * Actions
 */
if ($action == 'addrights' && $caneditperms)
{
    $editgroup = new Usergroup($db);
    $result=$editgroup->fetch($id);
    if ($result > 0) $editgroup->addrights($rights, $module);
}

if ($action == 'delrights' && $caneditperms)
{
    $editgroup = new Usergroup($db);
    $result=$editgroup->fetch($id);
    if ($result > 0) $editgroup->delrights($rights, $module);
}


/**
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("Permissions"));

if ($id)
{
    $fgroup = new Usergroup($db);
    $fgroup->fetch($id);
    $fgroup->getrights();

    /*
     * Affichage onglets
     */
    $head = group_prepare_head($fgroup);
    $title = $langs->trans("Group");
    dol_fiche_head($head, 'rights', $title, 0, 'group');

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
                        	$entity=((! empty($conf->multicompany->enabled) && ! empty($fgroup->entity)) ? $fgroup->entity : null);
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
    $permsgroup = array();

    $sql = "SELECT r.id, r.libelle, r.module ";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= ", ".MAIN_DB_PREFIX."usergroup_rights as ugr";
    $sql.= " WHERE ugr.fk_id = r.id";
    if(! empty($conf->multicompany->enabled))
    {
        if (empty($conf->multicompany->transverse_mode))
        {
        	$sql.= " AND r.entity = ".$fgroup->entity;
        }
        else
        {
        	$sql.= " AND r.entity IN (0,1)";
        }
    }
    else
    {
    	$sql.= " AND r.entity IN (0,".$conf->entity.")";
    }

    $sql.= " AND ugr.fk_usergroup = ".$fgroup->id;

    $result=$db->query($sql);

    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            array_push($permsgroup,$obj->id);
            $i++;
        }
        $db->free($result);
    }
    else
    {
        dol_print_error($db);
    }


    /*
     * Ecran ajout/suppression permission
     */

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
    print '<td colspan="2">';
    print $form->showrefnav($fgroup,'id','',$user->rights->user->user->lire || $user->admin);
    print '</td>';
    print '</tr>';

    // Nom
    print '<tr><td width="25%">'.$langs->trans("Name").'</td>';
    print '<td colspan="2">'.$fgroup->name.'';
    if (! $fgroup->entity)
    {
        print img_picto($langs->trans("GlobalGroup"),'redstar');
    }
    print "</td></tr>\n";

    // Note
    print '<tr><td width="25%" class="tdtop">'.$langs->trans("Note").'</td>';
    print '<td class="valeur">'.dol_htmlentitiesbr($fgroup->note).'</td>';
    print "</tr>\n";

    print '</table><br>';

    if ($user->admin) print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));

    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Module").'</td>';
    if ($caneditperms) print '<td width="24">&nbsp</td>';
    print '<td align="center" width="24">&nbsp;</td>';
    print '<td>'.$langs->trans("Permissions").'</td>';
    print '</tr>';

    $sql = "SELECT r.id, r.libelle, r.module";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
    if(! empty($conf->multicompany->enabled))
    {
        if (empty($conf->multicompany->transverse_mode))
        {
        	$sql.= " AND r.entity = ".$fgroup->entity;
        }
        else
        {
        	$sql.= " AND r.entity IN (0,1)";
        }
    }
    else
    {
    	$sql.= " AND r.entity = ".$conf->entity;
    }

    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql.= " AND r.perms NOT LIKE '%_advance'";  // Hide advanced perms if option is disable
    $sql.= " ORDER BY r.module, r.id";

    $result=$db->query($sql);
    if ($result)
    {
        $i = 0;
        $var = true;
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
                $var = !$var;

                // Rupture detectee, on recupere objMod
                $objMod = $modules[$obj->module];
                $picto=($objMod->picto?$objMod->picto:'generic');

                if ($caneditperms)
                {
                    print '<tr '. $bc[$var].'>';
                    print '<td class="nowrap">'.img_object('',$picto).' '.$objMod->getName();
                    print '<a name="'.$objMod->getName().'">&nbsp;</a></td>';
                    print '<td align="center" class="nowrap">';
                    print '<a title='.$langs->trans("All").' alt='.$langs->trans("All").' href="perms.php?id='.$fgroup->id.'&amp;action=addrights&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("All")."</a>";
                    print '/';
                    print '<a title='.$langs->trans("None").' alt='.$langs->trans("None").' href="perms.php?id='.$fgroup->id.'&amp;action=delrights&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("None")."</a>";
                    print '</td>';
                    print '<td colspan="2">&nbsp;</td>';
                    print '</tr>';
                }
            }

            print '<tr '. $bc[$var].'>';

            // Module
            print '<td class="nowrap">'.img_object('',$picto).' '.$objMod->getName().'</td>';

            if (in_array($obj->id, $permsgroup))
            {
                // Own permission by group
                if ($caneditperms)
                {
                    print '<td align="center"><a class="reposition" href="perms.php?id='.$fgroup->id.'&amp;action=delrights&amp;rights='.$obj->id.'">'.img_edit_remove($langs->trans("Remove")).'</a></td>';
                }
                print '<td align="center">';
                print img_picto($langs->trans("Active"),'tick');
                print '</td>';
            }
            else
            {
                // Do not own permission
                if ($caneditperms)
                {
                    print '<td align="center"><a class="reposition" href="perms.php?id='.$fgroup->id.'&amp;action=addrights&amp;rights='.$obj->id.'">'.img_edit_add($langs->trans("Add")).'</a></td>';
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
}

llxFooter();
$db->close();
