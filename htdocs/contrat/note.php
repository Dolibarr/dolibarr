<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *      \file       htdocs/contrat/note.php
 *      \ingroup    contrat
 *      \brief      Fiche de notes sur un contrat
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'contracts'));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOSTINT('socid');
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
//$hookmanager->initHooks(array('contractcard', 'globalcard'));  -> Conflict with contrat\card.php
$hookmanager->initHooks(array('contractnote'));

$object = new Contrat($db);
$object->fetch($id, $ref);

$permissiontoadd   = $user->hasRight('contrat', 'creer');     //  Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissionnote = $user->hasRight('contrat', 'creer'); // Used by the include of actions_setnotes.inc.php

$result = restrictedArea($user, 'contrat', $object->id);


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'
}



/*
 * View
 */

$title = $langs->trans("Contract");
$help_url = 'EN:Module_Contracts|FR:Module_Contrat';

llxHeader('', $title, $help_url);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = contract_prepare_head($object);

	$hselected = 2;

	print dol_get_fiche_head($head, 'note', $langs->trans("Contract"), -1, 'contract');

	// Contract card

	$linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


	$morehtmlref = '';
	//if (!empty($modCodeContract->code_auto)) {
	$morehtmlref .= $object->ref;
	/*} else {
	 $morehtmlref.=$form->editfieldkey("",'ref',$object->ref,0,'string','',0,3);
	$morehtmlref.=$form->editfieldval("",'ref',$object->ref,0,'string','',0,2);
	}*/

	$morehtmlref .= '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, 0, 'string', '', null, null, '', 1, 'getFormatedCustomerRef');
	// Ref supplier
	$morehtmlref .= '<br>';
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'none', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';


	// Ligne info remises tiers
	print '<tr><td class="titlefield">'.$langs->trans('Discount').'</td><td colspan="3">';
	if ($object->thirdparty->remise_percent) {
		print $langs->trans("CompanyHasRelativeDiscount", $object->thirdparty->remise_percent);
	} else {
		print $langs->trans("CompanyHasNoRelativeDiscount");
	}
	$absolute_discount = $object->thirdparty->getAvailableDiscounts();
	print '. ';
	if ($absolute_discount) {
		print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->trans("Currency".$conf->currency));
	} else {
		print $langs->trans("CompanyHasNoAbsoluteDiscount");
	}
	print '.';
	print '</td></tr>';

	// Date
	print '<tr>';
	print '<td class="titlefield">';
	print $form->editfieldkey("Date", 'date_contrat', $object->date_contrat, $object, 0);
	print '</td><td>';
	print $form->editfieldval("Date", 'date_contrat', $object->date_contrat, $object, 0, 'datehourpicker');
	print '</td>';
	print '</tr>';

	print "</table>";

	print '</div>';

	//print '<br>';

	$cssclass='titlefield';
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print dol_get_fiche_end();
}


llxFooter();
$db->close();
