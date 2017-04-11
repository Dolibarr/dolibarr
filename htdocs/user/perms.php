<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
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
 *       \file       htdocs/user/perms.php
 *       \brief      Onglet user et permissions de la fiche utilisateur
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("users");
$langs->load("admin");

$id=GETPOST('id', 'int');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$module=GETPOST('module', 'alpha');
$rights=GETPOST('rights', 'int');
$entity=(GETPOST('entity','int')?GETPOST('entity','int'):$conf->entity);

if (! isset($id) || empty($id)) accessforbidden();

// Defini si peux lire les permissions
$canreaduser=($user->admin || $user->rights->user->user->lire);
// Defini si peux modifier les autres utilisateurs et leurs permisssions
$caneditperms=($user->admin || $user->rights->user->user->creer);
// Advanced permissions
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$canreaduser=($user->admin || ($user->rights->user->user->lire && $user->rights->user->user_advance->readperms));
	$caneditselfperms=($user->id == $id && $user->rights->user->self_advance->writeperms);
	$caneditperms = (($caneditperms || $caneditselfperms) ? 1 : 0);
}

// Security check
$socid=0;
if (isset($user->societe_id) && $user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->user->self_advance->readperms))	// A user can always read its own card if not advanced perms enabled, or if he has advanced perms
{
	$feature2='';
	$canreaduser=1;
}

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$object = new User($db);
$object->fetch($id);
$object->getrights();

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard','globalcard'));


/**
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'addrights' && $caneditperms) {
		$edituser = new User($db);
		$edituser->fetch($id);
		//$edituser->addrights($rights, $module, '', $entity); // TODO unused for the moment
		$edituser->addrights($rights, $module);

		// Si on a touche a ses propres droits, on recharge
		if ($id == $user->id) {
			$user->clearrights();
			$user->getrights();
			$menumanager->loadMenu();
		}
	}

	if ($action == 'delrights' && $caneditperms) {
		$edituser = new User($db);
		$edituser->fetch($id);
		//$edituser->delrights($rights, $module, '', $entity); // TODO unused for the moment
		$edituser->delrights($rights, $module);

		// Si on a touche a ses propres droits, on recharge
		if ($id == $user->id) {
			$user->clearrights();
			$user->getrights();
			$menumanager->loadMenu();
		}
	}
}


/**
 *	View
 */

llxHeader('',$langs->trans("Permissions"));

$form=new Form($db);

$head = user_prepare_head($object);

$title = $langs->trans("User");
dol_fiche_head($head, 'rights', $title, 0, 'user');


$db->begin();

// Search all modules with permission and reload permissions def.
$modules = array();
$modulesdir = dolGetModulesDirs();

foreach($modulesdir as $dir)
{
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
					include_once $dir.$file;
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
    	            	$forceEntity=((! empty($conf->multicompany->enabled) && ! empty($object->entity)) ? $object->entity : null);
    	                $ret=$objMod->insert_permissions(0, $forceEntity);
    	                $modules[$objMod->rights_class]=$objMod;
    	                //print "modules[".$objMod->rights_class."]=$objMod;";
    	            }
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
if (! empty($conf->multicompany->enabled))
{
	if (1==2 && ! empty($conf->multicompany->transverse_mode)) {
		$sql.= " AND r.entity = ".(GETPOST('entity','int')?GETPOST('entity','int'):$conf->entity); // TODO unused for the moment
	} else {
		$sql.= " AND r.entity = ".(! empty($object->entity) ? $object->entity : $conf->entity);
	}
}
else
{
	$sql.= " AND r.entity = ".$conf->entity;
}
$sql.= " AND ur.fk_user = ".$object->id;

dol_syslog("get user perms", LOG_DEBUG);
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
$permsgroupbyentity = array();
$aEntities = array();

$sql = "SELECT r.id, r.libelle, r.module, gu.entity";
$sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r,";
$sql.= " ".MAIN_DB_PREFIX."usergroup_rights as gr,";
$sql.= " ".MAIN_DB_PREFIX."usergroup_user as gu";
$sql.= " WHERE gr.fk_id = r.id";
if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)) {
	$sql.= " AND gu.entity IS NOT NULL";
} else {
	$sql.= " AND r.entity = ".((! empty($conf->multicompany->enabled) && ! empty($object->entity)) ? $object->entity : $conf->entity);
}
$sql.= " AND gr.fk_usergroup = gu.fk_usergroup";
$sql.= " AND gu.fk_user = ".$object->id;

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


/*
 * Ecran ajout/suppression permission
 */

$linkback = '';

if ($user->rights->user->user->lire || $user->admin) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
}

dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);


//print '<div class="underbanner clearboth"></div>';

if ($user->admin) print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));
// Show warning about external users
if (empty($user->societe_id)) print info_admin(showModulesExludedForExternal($modules))."\n";

// For multicompany transversal mode
// TODO Place a hook here
if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode))
{
	$aEntities=array_keys($permsgroupbyentity);
	sort($aEntities);
	$entity = (GETPOST('entity', 'int')?GETPOST('entity', 'int'):$aEntities[0]);
	$head = entity_prepare_head($object, $aEntities);
	$title = $langs->trans("Entities");
	dol_fiche_head($head, $entity, $title, 1, 'multicompany@multicompany');
}

print "\n";
print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
if ($caneditperms) print '<td>&nbsp</td>';
print '<td align="center" width="24">&nbsp;</td>';
print '<td>'.$langs->trans("Permissions").'</td>';
print '</tr>'."\n";

//print "xx".$conf->global->MAIN_USE_ADVANCED_PERMS;
$sql = "SELECT r.id, r.libelle, r.module";
$sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
$sql.= " AND r.entity = ".((! empty($conf->multicompany->enabled) && ! empty($object->entity)) ? $object->entity : $conf->entity);
if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql.= " AND r.perms NOT LIKE '%_advance'";  // Hide advanced perms if option is disable
$sql.= " ORDER BY r.module, r.id";

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	$var = True;
	$oldmod='';

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);

		// Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
		if (empty($modules[$obj->module]))
		{
			$i++;
			continue;
		}

		if (isset($obj->module) && ($oldmod <> $obj->module))
		{
			$oldmod = $obj->module;
			$var = !$var;

			// Rupture detectee, on recupere objMod
			$objMod=$modules[$obj->module];
			$picto=($objMod->picto?$objMod->picto:'generic');

        	if ($caneditperms && (empty($objMod->rights_admin_allowed) || empty($object->admin)))
        	{
        		// On affiche ligne pour modifier droits
        		print '<tr '. $bc[$var].'>';
        		print '<td class="maxwidthonsmartphone tdoverflowonsmartphone">'.img_object('',$picto).' '.$objMod->getName();
        		print '<a name="'.$objMod->getName().'"></a></td>';
        		print '<td align="center" class="nowrap">';
        		print '<a class="reposition" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="perms.php?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;module='.$obj->module.'">'.$langs->trans("All")."</a>";
        		print '/';
        		print '<a class="reposition" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="perms.php?id='.$object->id.'&amp;action=delrights&amp;entity='.$entity.'&amp;module='.$obj->module.'">'.$langs->trans("None")."</a>";
        		print '</td>';
        		print '<td colspan="2">&nbsp;</td>';
        		print '</tr>'."\n";
        	}
        }

		print '<tr '. $bc[$var].'>';

		// Picto and label of permission
		print '<td class="maxwidthonsmartphone tdoverflowonsmartphone">'.img_object('',$picto).' '.$objMod->getName().'</td>';

        // Permission and tick
        if (! empty($object->admin) && ! empty($objMod->rights_admin_allowed))    // Permission own because admin
        {
        	if ($caneditperms)
        	{
        		print '<td align="center">'.img_picto($langs->trans("Administrator"),'star').'</td>';
        	}
        	print '<td align="center" class="nowrap">';
        	print img_picto($langs->trans("Active"),'tick');
        	print '</td>';
        }
        else if (in_array($obj->id, $permsuser))					// Permission own by user
        {
        	if ($caneditperms)
        	{
        		print '<td align="center"><a class="reposition" href="perms.php?id='.$object->id.'&amp;action=delrights&amp;rights='.$obj->id.'">'.img_edit_remove($langs->trans("Remove")).'</a></td>';
        	}
        	print '<td align="center" class="nowrap">';
        	print img_picto($langs->trans("Active"),'tick');
        	print '</td>';
        }

        else if (is_array($permsgroupbyentity[$entity]))
        {
	        if (in_array($obj->id, $permsgroupbyentity[$entity]))	// Permission own by group
	        {
	        	if ($caneditperms)
	        	{
	        		print '<td align="center">';
	        		print $form->textwithtooltip($langs->trans("Inherited"),$langs->trans("PermissionInheritedFromAGroup"));
	        		print '</td>';
	        	}
	        	print '<td align="center" class="nowrap">';
	        	print img_picto($langs->trans("Active"),'tick');
	        	print '</td>';
	        }
	        else
	        {
	        	// Do not own permission
	        	if ($caneditperms)
	        	{
	        		print '<td align="center"><a class="reposition" href="perms.php?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;rights='.$obj->id.'">'.img_edit_add($langs->trans("Add")).'</a></td>';
	        	}
	        	print '<td>&nbsp</td>';
	        }
        }
        else
        {
        	// Do not own permission
        	if ($caneditperms)
        	{
        		print '<td align="center"><a class="reposition" href="perms.php?id='.$object->id.'&amp;action=addrights&amp;entity='.$entity.'&amp;rights='.$obj->id.'">'.img_edit_add($langs->trans("Add")).'</a></td>';
        	}
        	print '<td>&nbsp</td>';
        }

		$permlabel=($conf->global->MAIN_USE_ADVANCED_PERMS && ($langs->trans("PermissionAdvanced".$obj->id)!=("PermissionAdvanced".$obj->id))?$langs->trans("PermissionAdvanced".$obj->id):(($langs->trans("Permission".$obj->id)!=("Permission".$obj->id))?$langs->trans("Permission".$obj->id):$langs->trans($obj->libelle)));
		print '<td class="maxwidthonsmartphone">'.$permlabel.'</td>';

		print '</tr>'."\n";

		$i++;
	}
}
else dol_print_error($db);
print '</table>';


// For multicompany transversal mode
// TODO Place a hook here
if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode))
{
	dol_fiche_end();
}

dol_fiche_end();

llxFooter();

$db->close();
