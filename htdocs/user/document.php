<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
 *  \file       htdocs/user/document.php
 *  \brief      Tab for documents linked to user
 *  \ingroup    user
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'other'));

$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm');
$id=(GETPOST('userid','int') ? GETPOST('userid','int') : GETPOST('id','int'));
$ref = GETPOST('ref', 'alpha');
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'userdoc';   // To manage different context of search

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
$candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
    $caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
    $caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
    || (($user->id != $id) && $user->rights->user->user->password));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2='user';
if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
if (!$canreaduser) {
	$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="position_name";

$object = new User($db);
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref, '', 1);
	$object->getrights();
	//$upload_dir = $conf->user->multidir_output[$object->entity] . "/" . $object->id ;
	// For users, the upload_dir is always $conf->user->entity for the moment
	$upload_dir = $conf->user->dir_output. "/" . $object->id ;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('usercard','userdoc','globalcard'));


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty").' - '.$langs->trans("Files"),$help_url);

if ($object->id)
{
	/*
	 * Affichage onglets
	 */
	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = user_prepare_head($object);

	$form=new Form($db);

	dol_fiche_head($head, 'document', $langs->trans("User"), -1, 'user');

	$linkback = '';
	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	print '<table class="border" width="100%">';

    // Login
    print '<tr><td class="titlefield">'.$langs->trans("Login").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';

	// Nbre files
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';

	//Total taille
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.dol_print_size($totalsize,1,1).'</td></tr>';

	print '</table>';
    print '</div>';

	dol_fiche_end();


	$modulepart = 'user';
	$permission = $user->rights->user->user->creer;
	$permtoedit = $user->rights->user->user->creer;
	$param = '&id=' . $object->id;
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	accessforbidden('',0,0);
}


llxFooter();
$db->close();
