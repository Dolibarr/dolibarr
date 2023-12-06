<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2011      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *       \file       htdocs/compta/sociales/document.php
 *       \ingroup    tax
 *       \brief      Page with attached files on social contributions
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('other', 'companies', 'compta', 'bills'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}


$object = new ChargeSociales($db);
if ($id > 0) {
	$object->fetch($id);
}

$upload_dir = $conf->tax->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart = 'tax';

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', $object->id, 'chargesociales', 'charges');

$permissiontoadd = $user->rights->tax->charges->creer;	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

if ($action == 'setlib' && $user->hasRight('tax', 'charges', 'creer')) {
	$object->fetch($id);
	$result = $object->setValueFrom('libelle', GETPOST('lib'), '', '', 'text', '', $user, 'TAX_MODIFY');
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $langs->trans("SocialContribution").' - '.$langs->trans("Documents");
$help_url = 'EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("", $title, $help_url);

if ($object->id) {
	$alreadypayed = $object->getSommePaiement();

	$head = tax_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans("SocialContribution"), -1, 'bill');

	$morehtmlref = '<div class="refidno">';
	// Label of social contribution
	$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', null, null, '', 1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		if (!empty($object->fk_project)) {
			$morehtmlref .= '<br>';
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= $proj->getNomUrl(1);
			if ($proj->title) {
				$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
			}
		} else {
			$morehtmlref .= '';
		}
	}
	$morehtmlref .= '</div>';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$object->totalpaid = $alreadypayed; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	$morehtmlright = '';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	print '<table class="border tableforfield centpercent">';

	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print '</table>';

	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	$modulepart = 'tax';
	$permissiontoadd = $user->rights->tax->charges->creer;
	$permtoedit = $user->rights->tax->charges->creer;
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
