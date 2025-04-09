<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 * Copyright (C) 2021      Jesus Jerez       	 <jesusballesteros@protonmail.com>
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
 *    \file       htdocs/fourn/paiement/document.php
 *    \ingroup    invoice, fournisseur
 *    \brief      Management page of attached documents to a payment
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('banks', 'bills', 'companies', 'suppliers', 'other'));


// Get Parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');


// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, $object->element, $object->id, 'paiementfourn', '');

// Get parameters
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
	$sortfield = "name";
}

// Load object
$object = new PaiementFourn($db);
if ($object->fetch($id, $ref)) {
	$object->fetch_thirdparty();
	$ref = dol_sanitizeFileName($object->ref);
	$upload_dir = $conf->fournisseur->payment->dir_output.'/'.dol_sanitizeFileName($object->ref);
}

$permissiontoadd = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")); // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new	Form($db);

$title = $langs->trans('Payment')." - ".$langs->trans('Documents');
llxHeader('', $title);

if ($object->id > 0) {
	$head = payment_supplier_prepare_head($object);
	print dol_get_fiche_head($head, 'documents', $langs->trans("SupplierPayment"), -1, 'payment');

	// Supplier order card
	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	// Date of payment
	$morehtmlref .= $form->editfieldkey("Date", 'datep', $object->date, $object, $object->statut == 0 && ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")), 'datehourpicker', '', null, 3).': ';
	$morehtmlref .= $form->editfieldval("Date", 'datep', $object->date, $object, $object->statut == 0 && ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")), 'datehourpicker', '', null, $langs->trans('PaymentDateUpdateSucceeded'));

	// Payment mode
	$morehtmlref .= '<br>'.$langs->trans('PaymentMode').' : ';
	$morehtmlref .= $langs->trans("PaymentType".$object->type_code) != "PaymentType".$object->type_code ? $langs->trans("PaymentType".$object->type_code) : $object->type_label;
	$morehtmlref .= $object->num_payment ? ' - '.$object->num_payment : '';

	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);

	// Amount
	$morehtmlref .= '<br>'.$langs->trans('Amount').' : '. price($object->amount, 0, $langs, 0, 0, -1, $conf->currency);

	$allow_delete = 1;
	// Bank account
	if (isModEnabled("bank")) {
		if ($object->fk_account) {
			$bankline = new AccountLine($db);
			$bankline->fetch($object->bank_line);
			if ($bankline->rappro) {
				$allow_delete = 0;
				$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
			}

			$morehtmlref .= '<br>'.$langs->trans('BankAccount').' : ';
			$accountstatic = new Account($db);
			$accountstatic->fetch($bankline->fk_account);
			$morehtmlref .= $accountstatic->getNomUrl(1);

			$morehtmlref .= '<br>'.$langs->trans('BankTransactionLine').' : ';
			$morehtmlref .= $bankline->getNomUrl(1, 0, 'showconciliated');
		}
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print "</table>\n";

	print "</div>\n";

	print dol_get_fiche_end();

	print '<br>';


	$modulepart = 'supplier_payment';
	// TODO: get the appropriate permission
	$permissiontoadd = true;
	$permtoedit = true;
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	header('Location: index.php');
	exit;
}

// End of page
llxFooter();
$db->close();
