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
 *      \file       htdocs/compta/facture/note-rec.php
 *      \ingroup    invoice
 *      \brief      Fiche de notes sur une facture recurrent
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';

if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills'));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');

$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") {
	$objecttype = '';
}

$object = new FactureRec($db);
// Load object
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

$permissionnote = $user->hasRight('facture', 'creer'); // Used by the include of actions_setnotes.inc.php

// Security check
$socid = 0;
if ($user->socid) {
	$socid = $user->socid;
}
$hookmanager->initHooks(array('invoicenote'));

$result = restrictedArea($user, 'facture', $object->id, $objecttype);

$usercancreate = $user->hasRight("facture", "creer");


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

$form = new Form($db);

if (empty($object->id)) {
	$title = $object->ref." - ".$langs->trans('Notes');
} else {
	$title = $langs->trans('Notes');
}
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";

llxHeader('', $title, $helpurl);

if (empty($object->id)) {
	$langs->load('errors');
	echo '<div class="error">'.$langs->trans("ErrorRecordNotFound").'</div>';
	llxFooter();
	exit;
}


if ($id > 0 ) {
	$object = new FactureRec($db);
	$object->fetch($id);

	$object->fetch_thirdparty();

	$head = invoice_rec_prepare_head($object);

	$totalpaid = $object->getSommePaiement();

	print dol_get_fiche_head($head, 'note', $langs->trans("InvoiceCustomer"), -1, 'bill');

		// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '';
	if ($action != 'editref') {
		$morehtmlref .= $form->editfieldkey($object->ref, 'ref', $object->ref, $object, 0, '', '', 0, 2);
	} else {
		$morehtmlref .= $form->editfieldval('', 'ref', $object->ref, $object, 0, 'string');
	}

	$morehtmlref .= '<div class="refidno">';
	// Thirdparty
	$morehtmlref .= $object->thirdparty->getNomUrl(1);
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

	$morehtmlstatus = '';

	dol_banner_tab($object, 'ref', $linkback, 1, 'title', 'none', $morehtmlref, '', 0, '', $morehtmlstatus);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


	$cssclass = "titlefield";

	// Help of substitution key
	$dateexample = dol_now();
	if (!empty($object->frequency) && !empty($object->date_when)) {
		$dateexample = $object->date_when;
	}

	$substitutionarray = getCommonSubstitutionArray($langs, 2, null, $object);

	$substitutionarray['__INVOICE_PREVIOUS_MONTH__'] = $langs->trans("PreviousMonthOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'), '%m') . ')';
	$substitutionarray['__INVOICE_MONTH__'] = $langs->trans("MonthOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date($dateexample, '%m') . ')';
	$substitutionarray['__INVOICE_NEXT_MONTH__'] = $langs->trans("NextMonthOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%m') . ')';
	$substitutionarray['__INVOICE_PREVIOUS_MONTH_TEXT__'] = $langs->trans("TextPreviousMonthOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'), '%B') . ')';
	$substitutionarray['__INVOICE_MONTH_TEXT__'] = $langs->trans("TextMonthOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date($dateexample, '%B') . ')';
	$substitutionarray['__INVOICE_NEXT_MONTH_TEXT__'] = $langs->trans("TextNextMonthOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%B') . ')';
	$substitutionarray['__INVOICE_PREVIOUS_YEAR__'] = $langs->trans("PreviousYearOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree($dateexample, -1, 'y'), '%Y') . ')';
	$substitutionarray['__INVOICE_YEAR__'] = $langs->trans("YearOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date($dateexample, '%Y') . ')';
	$substitutionarray['__INVOICE_NEXT_YEAR__'] = $langs->trans("NextYearOfInvoice") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree($dateexample, 1, 'y'), '%Y') . ')';
	// Only on template invoices
	$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__'] = $langs->trans("DateNextInvoiceBeforeGen") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(($object->date_when ? $object->date_when : dol_now()), 'dayhour') . ')';
	$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__'] = $langs->trans("DateNextInvoiceAfterGen") . ' (' . $langs->trans("Example") . ': ' . dol_print_date(dol_time_plus_duree(($object->date_when ? $object->date_when : dol_now()), $object->frequency, $object->unit_frequency), 'dayhour') . ')';
	$substitutionarray['__INVOICE_COUNTER_CURRENT__'] = $object->nb_gen_done;
	$substitutionarray['__INVOICE_COUNTER_MAX__'] = $object->nb_gen_max;

	$htmltext = '<i>' . $langs->trans("FollowingConstantsWillBeSubstituted") . ':<br>';
	foreach ($substitutionarray as $key => $val) {
		$htmltext .= $key . ' = ' . $langs->trans($val) . '<br>';
	}
	$htmltext .= '</i>';

	$textNotePub = $form->textwithpicto($langs->trans('NotePublic'), $htmltext, 1, 'help', '', 0, 2, 'notepublic', 2);
	$textNotePrive = $form->textwithpicto($langs->trans("NotePrivate"), $htmltext, 1, 'help', '', 0, 2, 'noteprivate');
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
