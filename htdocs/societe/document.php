<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/societe/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


// Load translation files required by the page
$langs->loadLangs(array("companies", "other"));


// Get parameters
$action 	= GETPOST('action', 'aZ09');
$confirm 	= GETPOST('confirm');
$id 		= (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref 		= GETPOST('ref', 'alpha');

$limit 		= GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield 	= GETPOST('sortfield', 'aZ09comma');
$sortorder 	= GETPOST('sortorder', 'aZ09comma');
$page 		= GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1

$offset 	= $limit * $page;
$pageprev 	= $page - 1;
$pagenext 	= $page + 1;

if (getDolGlobalString('MAIN_DOC_SORT_FIELD')) {
	$sortfield = $conf->global->MAIN_DOC_SORT_FIELD;
}
if (getDolGlobalString('MAIN_DOC_SORT_ORDER')) {
	$sortorder = $conf->global->MAIN_DOC_SORT_ORDER;
}

if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "position_name";
}

// Initialize objects
$object = new Societe($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);

	$upload_dir = $conf->societe->multidir_output[$object->entity]."/".$object->id;
	$courrier_dir = $conf->societe->multidir_output[$object->entity]."/courrier/".get_exdir($object->id, 0, 0, 0, $object, 'thirdparty');
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartydocument', 'globalcard'));

$permissiontoadd = $user->hasRight('societe', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

// Security check
if ($user->socid > 0) {
	unset($action);
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', $object->id, '&societe');

if (empty($object->id)) {
	accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Files");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Files");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

// Show tabs
if (isModEnabled('notification')) {
	$langs->load("mails");
}
$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'document', $langs->trans("ThirdParty"), -1, 'company');


// Build file list
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach ($filearray as $key => $file) {
	$totalsize += $file['size'];
}

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

// Type Prospect/Customer/Supplier
print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
print $object->getTypeUrl(1);
print '</td></tr>';

// Prefix
if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
	print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client) {
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
	$tmpcheck = $object->check_codeclient();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
	}
	print '</td></tr>';
}

if ($object->fournisseur) {
	print '<tr><td class="titlefield">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
	$tmpcheck = $object->check_codefournisseur();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
	}
	print '</td></tr>';
}

// Number of files
print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

// Total size
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';

print '</table>';

print '</div>';

print dol_get_fiche_end();

$modulepart = 'societe';
$permissiontoadd = $user->hasRight('societe', 'creer');
$permtoedit = $user->hasRight('societe', 'creer');
$param = '&id='.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';

// End of page
llxFooter();
$db->close();
