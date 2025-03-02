<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/ecm/file_note.php
 *  \ingroup    ecm
 *  \brief      Tab for notes on an ECM file
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('ecm'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');

// Get parameters
$socid = GETPOSTINT("socid");
// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$backtopage = GETPOST('backtopage', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "label";
}

$section = GETPOST("section", 'alpha');
if (!$section) {
	dol_print_error(null, 'Error, section parameter missing');
	exit;
}
$urlfile = (string) dol_sanitizePathName(GETPOST("urlfile"));
if (!$urlfile) {
	dol_print_error(null, "ErrorParamNotDefined");
	exit;
}

// Load ecm object
$ecmdir = new EcmDirectory($db);
$result = $ecmdir->fetch(GETPOST("section", 'alpha'));
if (!($result > 0)) {
	dol_print_error($db, $ecmdir->error);
	exit;
}
$relativepath = $ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

$fullpath = $conf->ecm->dir_output.'/'.$relativepath.$urlfile;

$relativetodocument = 'ecm/'.$relativepath; // $relativepath is relative to ECM dir, we need relative to document
$filepath = $relativepath.$urlfile;
$filepathtodocument = $relativetodocument.$urlfile;

// Try to load object from index
$object = new EcmFiles($db);
$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$result = $object->fetch(0, '', $filepathtodocument);
if ($result < 0) {
	dol_print_error($db, $object->error, $object->errors);
	exit;
}

$permissionnote = $user->hasRight('ecm', 'setup'); // Used by the include of actions_setnotes.inc.php

$permissiontoread = $user->hasRight('ecm', 'read');

if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'


/*
 * View
 */

llxHeader('', $langs->trans('EcmFiles'), '', '', 0, 0, '', '', '', 'mod-ecm page-file_note');

$form = new Form($db);

$object->section_id = $ecmdir->id;
$object->label = $urlfile;
$head = ecm_file_prepare_head($object);

print dol_get_fiche_head($head, 'note', $langs->trans("File"), -1, 'generic');

$s = '';
$tmpecmdir = new EcmDirectory($db); // Need to create a new one
$tmpecmdir->fetch($ecmdir->id);
$result = 1;
$i = 0;
while ($tmpecmdir && $result > 0) {
	$tmpecmdir->ref = $tmpecmdir->label;
	$s = $tmpecmdir->getNomUrl(1).$s;
	if ($tmpecmdir->fk_parent) {
		$s = ' -> '.$s;
		$result = $tmpecmdir->fetch($tmpecmdir->fk_parent);
	} else {
		$tmpecmdir = 0;
	}
	$i++;
}

$urlfiletoshow = preg_replace('/\.noexe$/', '', $urlfile);

$s = img_picto('', 'object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> '.$s.' -> ';
if ($action == 'edit') {
	$s .= '<input type="text" name="label" class="quatrevingtpercent" value="'.$urlfiletoshow.'">';
} else {
	$s .= $urlfiletoshow;
}

$linkback = '';
if ($backtopage) {
	$linkback = '<a href="'.$backtopage.'">'.$langs->trans("BackToTree").'</a>';
}

$object->ref = ''; // Force to hide ref
dol_banner_tab($object, '', $linkback, 0, '', '', $s);



print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';


$cssclass = "titlefield";
$moreparam = '&amp;section='.$section.'&amp;urlfile='.$urlfile;
include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

print '</div>';

print dol_get_fiche_end();


// End of page
llxFooter();
$db->close();
