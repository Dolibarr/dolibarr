<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/user/perms.php
 *       \brief      Onglet user et permissions de la fiche utilisateur
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("users");
$langs->load("admin");

$form = new Form($db);

$module=isset($_GET["module"])?$_GET["module"]:$_POST["module"];

if (! isset($_GET["id"])) accessforbidden();


// Defini si peux modifier utilisateurs et permisssions
$caneditperms=($user->admin || $user->rights->user->user->creer);



/**
 * Actions
 */
if ($_GET["action"] == 'addrights' && $caneditperms)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->addrights($_GET["rights"],$module);

	// Si on a touche a ses propres droits, on recharge
	if ($_GET["id"] == $user->id)
	{
		$user->clearrights();
		$user->getrights();
	}
}

if ($_GET["action"] == 'delrights' && $caneditperms)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->delrights($_GET["rights"],$module);

	// Si on a touche a ses propres droits, on recharge
	if ($_GET["id"] == $user->id)
	{
		$user->clearrights();
		$user->getrights();
	}
}



/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */

llxHeader('',$langs->trans("Permissions"));

$form=new Form($db);

$fuser = new User($db, $_GET["id"]);
$fuser->fetch();
$fuser->getrights();

/*
 * Affichage onglets
 */
$head = user_prepare_head($fuser);

$title = $langs->trans("User");
dol_fiche_head($head, 'rights', $title);


$db->begin();

// Charge les modules soumis a permissions
$modules = array();

$listdir=$conf->dol_document_root;
foreach($listdir as $dirroot)
{
	$dir=$dirroot."/includes/modules/";

	$handle=opendir($dir);
	while (($file = readdir($handle))!==false)
	{
	    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, strlen($file) - 10) == '.class.php')
	    {
	        $modName = substr($file, 0, strlen($file) - 10);

	        if ($modName)
	        {
	            include_once($dir.$file);
	            $objMod = new $modName($db);
	            if ($objMod->rights_class) {

	                $ret=$objMod->insert_permissions();

	                $modules[$objMod->rights_class]=$objMod;
	                //print "modules[".$objMod->rights_class."]=$objMod;";
	            }
	        }
	    }
	}
}

$db->commit();

// Lecture des droits utilisateurs
$permsuser = array();

$sql = "SELECT r.id, r.libelle, r.module";
$sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r,";
$sql.= " ".MAIN_DB_PREFIX."user_rights as ur";
$sql.= " WHERE ur.fk_id = r.id";
$sql.= " AND r.entity = ".$conf->entity;
$sql.= " AND ur.fk_user = ".$fuser->id;

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        array_push($permsuser,$obj->id);
        $i++;
    }
    $db->free($result);
}
else
{
    dol_print_error($db);
}

// Lecture des droits groupes
$permsgroup = array();

$sql = "SELECT r.id, r.libelle, r.module";
$sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r,";
$sql.= " ".MAIN_DB_PREFIX."usergroup_rights as gr,";
$sql.= " ".MAIN_DB_PREFIX."usergroup_user as gu";
$sql.= " WHERE gr.fk_id = r.id";
$sql.= " AND r.entity = ".$conf->entity;
$sql.= " AND gr.fk_usergroup = gu.fk_usergroup";
$sql.= " AND gu.fk_user = ".$fuser->id;

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
print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
print '<td colspan="2">';
print $form->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
print '</td>';
print '</tr>';

// Nom
print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
print '<td colspan="2">'.$fuser->nom.'</td>';
print "</tr>\n";

// Prenom
print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
print '<td colspan="2">'.$fuser->prenom.'</td>';
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
$sql.= " AND r.entity = ".$conf->entity;
$sql.= " ORDER BY r.module, r.id";

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $var = True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);

        // Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
        if (! $modules[$obj->module])
        {
            $i++;
            continue;
        }

        if ($oldmod <> $obj->module)
        {
            $oldmod = $obj->module;
            $var = !$var;

            // Rupture detectee, on recupere objMod
            $objMod=$modules[$obj->module];
            $picto=($objMod->picto?$objMod->picto:'generic');

            if ($caneditperms && (! $objMod->rights_admin_allowed || ! $fuser->admin))
            {
                // On affiche ligne pour modifier droits
                print '<tr '. $bc[$var].'>';
                print '<td nowrap="nowrap">'.img_object('',$picto).' '.$objMod->getName();
                print '<a name="'.$objMod->getName().'">&nbsp;</a></td>';
                print '<td align="center" nowrap>';
                print '<a title='.$langs->trans("All").' alt='.$langs->trans("All").' href="perms.php?id='.$fuser->id.'&amp;action=addrights&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("All")."</a>";
                print '/';
                print '<a title='.$langs->trans("None").' alt='.$langs->trans("None").' href="perms.php?id='.$fuser->id.'&amp;action=delrights&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("None")."</a>";
                print '</td>';
                print '<td colspan="2">&nbsp;</td>';
                print '</tr>';
            }
        }

        print '<tr '. $bc[$var].'>';

        print '<td>'.img_object('',$picto).' '.$objMod->getName();
        print '</td>';

        if ($fuser->admin && $objMod->rights_admin_allowed)
        {
            // Permission own because admin
            if ($caneditperms)
            {
                print '<td align="center">'.img_picto($langs->trans("Administrator"),'star').'</td>';
            }
            print '<td align="center" nowrap>';
            print img_tick();
            print '</td>';
        }
        else if (in_array($obj->id, $permsuser))
        {
            // Permission own by user
            if ($caneditperms)
            {
                print '<td align="center"><a href="perms.php?id='.$fuser->id.'&amp;action=delrights&amp;rights='.$obj->id.'#'.$objMod->getName().'">'.img_edit_remove($langs->trans("Remove")).'</a></td>';
            }
            print '<td align="center">';
            print img_tick();
            print '</td>';
        }
        else if (in_array($obj->id, $permsgroup)) {
            // Permission own by group
            if ($caneditperms)
            {
                print '<td align="center">';
				print $form->textwithtooltip($langs->trans("Group"),$langs->trans("PermissionInheritedFromAGroup"));
				//print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$fuser->id.'" title="'.$langs->trans("PermissionInheritedFromAGroup").'">';
				print '</td>';
            }
            print '<td align="center" nowrap>';
            print img_tick();
            print '</td>';
        }
        else
        {
            // Do not own permission
            if ($caneditperms)
            {
                print '<td align="center"><a href="perms.php?id='.$fuser->id.'&amp;action=addrights&amp;rights='.$obj->id.'#'.$objMod->getName().'">'.img_edit_add($langs->trans("Add")).'</a></td>';
            }
            print '<td>&nbsp</td>';
        }

        $perm_libelle=(($langs->trans("Permission".$obj->id)!=("Permission".$obj->id))?$langs->trans("Permission".$obj->id):$obj->libelle);
        print '<td>'.$perm_libelle. '</td>';

        print '</tr>';

        $i++;
    }
}
print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');

?>
