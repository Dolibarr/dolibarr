<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/compta/facture/prelevement.php
 *	\ingroup    invoice
 *	\brief      Management of direct debit order or credit transfer of invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'withdrawals', 'companies'));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$type = GETPOST('type', 'aZ09');

$fieldid = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$moreparam = '';
if ($type == 'bank-transfer') {
	$object = new FactureFournisseur($db);
	$moreparam = '&type='.$type;
} else {
	$object = new Facture($db);
}

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	$isdraft = (($object->status == FactureFournisseur::STATUS_DRAFT) ? 1 : 0);
	if ($ret > 0) {
		$object->fetch_thirdparty();
	}
}

$hookmanager->initHooks(array('directdebitcard', 'globalcard'));

if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture', 'fk_soc', $fieldid, $isdraft);
	if (!$user->hasRight('fournisseur', 'facture', 'lire')) {
		accessforbidden();
	}
} else {
	$result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid, $isdraft);
	if (!$user->hasRight('facture', 'lire')) {
		accessforbidden();
	}
}

if ($type == 'bank-transfer') {
	$usercancreate = ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer);
} else {
	$usercancreate = $user->hasRight('facture', 'creer');
}


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == "new" && $usercancreate) {
		if ($object->id > 0) {
			$db->begin();

			$newtype = $type;
			$sourcetype = 'facture';
			if ($type == 'bank-transfer') {
				$sourcetype = 'supplier_invoice';
				$newtype = 'bank-transfer';
			}
			$paymentservice = GETPOST('paymentservice');

			$result = $object->demande_prelevement($user, price2num(GETPOST('withdraw_request_amount', 'alpha')), $newtype, $sourcetype);

			if ($result > 0) {
				$db->commit();

				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		$action = '';
	}

	if ($action == "delete" && $usercancreate) {
		if ($object->id > 0) {
			$result = $object->demande_prelevement_delete($user, GETPOSTINT('did'));
			if ($result == 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id.'&type='.$type);
				exit;
			}
		}
	}

	// Make payment with Direct Debit Stripe
	if ($action == 'sepastripedirectdebit' && $usercancreate) {
		$result = $object->makeStripeSepaRequest($user, GETPOSTINT('did'), 'direct-debit', 'facture');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			// We refresh object data
			$ret = $object->fetch($id, $ref);
			$isdraft = (($object->status == Facture::STATUS_DRAFT) ? 1 : 0);
			if ($ret > 0) {
				$object->fetch_thirdparty();
			}
		}
	}

	// Make payment with Direct Debit Stripe
	if ($action == 'sepastripecredittransfer' && $usercancreate) {
		$result = $object->makeStripeSepaRequest($user, GETPOSTINT('did'), 'bank-transfer', 'supplier_invoice');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			// We refresh object data
			$ret = $object->fetch($id, $ref);
			$isdraft = (($object->status == FactureFournisseur::STATUS_DRAFT) ? 1 : 0);
			if ($ret > 0) {
				$object->fetch_thirdparty();
			}
		}
	}

	// Set payments conditions
	if ($action == 'setconditions' && $usercancreate) {
		$object->fetch($id);
		$object->cond_reglement_code = 0; // To clean property
		$object->cond_reglement_id = 0; // To clean property

		$error = 0;

		$db->begin();

		if (!$error) {
			$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'));
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if (!$error) {
			$old_date_echeance = $object->date_echeance;
			$new_date_echeance = $object->calculate_date_lim_reglement();
			if ($new_date_echeance > $old_date_echeance) {
				$object->date_echeance = $new_date_echeance;
			}
			if ($object->date_echeance < $object->date) {
				$object->date_echeance = $object->date;
			}
			$result = $object->update($user);
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	} elseif ($action == 'setmode' && $usercancreate) {
		// payment mode
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
	} elseif ($action == 'setdatef' && $usercancreate) {
		$newdate = dol_mktime(0, 0, 0, GETPOSTINT('datefmonth'), GETPOSTINT('datefday'), GETPOSTINT('datefyear'), 'tzserver');
		if ($newdate > (dol_now('tzuserrel') + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
			if (!getDolGlobalString('INVOICE_MAX_FUTURE_DELAY')) {
				setEventMessages($langs->trans("WarningInvoiceDateInFuture"), null, 'warnings');
			} else {
				setEventMessages($langs->trans("WarningInvoiceDateTooFarInFuture"), null, 'warnings');
			}
		}

		$object->date = $newdate;
		$date_echence_calc = $object->calculate_date_lim_reglement();
		if (!empty($object->date_echeance) && $object->date_echeance < $date_echence_calc) {
			$object->date_echeance = $date_echence_calc;
		}
		if ($object->date_echeance && $object->date_echeance < $object->date) {
			$object->date_echeance = $object->date;
		}

		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setdate_lim_reglement' && $usercancreate) {
		$object->date_echeance = dol_mktime(12, 0, 0, GETPOSTINT('date_lim_reglementmonth'), GETPOSTINT('date_lim_reglementday'), GETPOSTINT('date_lim_reglementyear'));
		if (!empty($object->date_echeance) && $object->date_echeance < $object->date) {
			$object->date_echeance = $object->date;
			setEventMessages($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"), null, 'warnings');
		}
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

if ($type == 'bank-transfer') {
	$title = $langs->trans('SupplierInvoice')." - ".$langs->trans('CreditTransfer');
	$helpurl = "";
} else {
	$title = $langs->trans('InvoiceCustomer')." - ".$langs->trans('StandingOrders');
	$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
}

llxHeader('', $title, $helpurl);


if ($object->id > 0) {
	$selleruserevenustamp = $mysoc->useRevenueStamp();

	$totalpaid = $object->getSommePaiement();
	$totalcreditnotes = $object->getSumCreditNotesUsed();
	$totaldeposits = $object->getSumDepositsUsed();
	//print "totalpaid=".$totalpaid." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

	// We can also use bcadd to avoid pb with floating points
	// For example print 239.2 - 229.3 - 9.9; does not return 0.
	//$resteapayer=bcadd($object->total_ttc,$totalpaid,$conf->global->MAIN_MAX_DECIMALS_TOT);
	//$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
	$resteapayer = price2num($object->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');

	if ($object->paid) {
		$resteapayer = 0;
	}
	$resteapayeraffiche = $resteapayer;

	if ($type == 'bank-transfer') {
		if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {	// Not recommended
			$filterabsolutediscount = "fk_invoice_supplier_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_invoice_supplier_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS PAID)%')";
			$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS PAID)%')";
		}

		$absolute_discount = $object->thirdparty->getAvailableDiscounts('', $filterabsolutediscount, 0, 1);
		$absolute_creditnote = $object->thirdparty->getAvailableDiscounts('', $filtercreditnote, 0, 1);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');
	} else {
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {	// Not recommended
			$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
			$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
		}

		$absolute_discount = $object->thirdparty->getAvailableDiscounts('', $filterabsolutediscount);
		$absolute_creditnote = $object->thirdparty->getAvailableDiscounts('', $filtercreditnote);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');
	}

	$author = new User($db);
	if (!empty($object->user_creation_id)) {
		$author->fetch($object->user_creation_id);
	} elseif (!empty($object->fk_user_author)) {
		$author->fetch($object->fk_user_author);
	}

	if ($type == 'bank-transfer') {
		$head = facturefourn_prepare_head($object);
	} else {
		$head = facture_prepare_head($object);
	}

	$numopen = 0;
	$pending = 0;
	$numclosed = 0;

	// How many Direct debit or Credit transfer open requests ?

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
	$sql .= " , pfd.date_traite as date_traite";
	$sql .= " , pfd.amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
	if ($type == 'bank-transfer') {
		$sql .= " WHERE fk_facture_fourn = ".((int) $object->id);
	} else {
		$sql .= " WHERE fk_facture = ".((int) $object->id);
	}
	$sql .= " AND pfd.traite = 0";
	$sql .= " AND pfd.type = 'ban'";
	$sql .= " ORDER BY pfd.date_demande DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$numopen = $num;
	} else {
		dol_print_error($db);
	}


	print dol_get_fiche_head($head, 'standingorders', $title, -1, ($type == 'bank-transfer' ? 'supplier_invoice' : 'bill'));

	// Invoice content
	if ($type == 'bank-transfer') {
		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	} else {
		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	}

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	if ($type == 'bank-transfer') {
		$morehtmlref .= $form->editfieldkey("RefSupplierBill", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplierBill", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	} else {
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_customer, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_customer, $object, 0, 'string', '', null, null, '', 1);
	}
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	if ($type == 'bank-transfer') {
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
		}
	} else {
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
		}
	}
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

	$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $moreparam, 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Type
	print '<tr><td class="titlefield fieldname_type">'.$langs->trans('Type').'</td><td colspan="3">';
	print '<span class="badgeneutral">';
	print $object->getLibType();
	print '</span>';
	if (!empty($object->module_source)) {
		print ' <span class="opacitymediumbycolor paddingleft">('.$langs->trans("POS").' '.$object->module_source.' - '.$langs->trans("Terminal").' '.$object->pos_source.')</span>';
	}
	if ($object->type == $object::TYPE_REPLACEMENT) {
		if ($type == 'bank-transfer') {
			$facreplaced = new FactureFournisseur($db);
		} else {
			$facreplaced = new Facture($db);
		}
		$facreplaced->fetch($object->fk_facture_source);
		print ' <span class="opacitymediumbycolor paddingleft">'.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).'</span>';
	}
	if ($object->type == $object::TYPE_CREDIT_NOTE && !empty($object->fk_facture_source)) {
		if ($type == 'bank-transfer') {
			$facusing = new FactureFournisseur($db);
		} else {
			$facusing = new Facture($db);
		}
		$facusing->fetch($object->fk_facture_source);
		print ' <span class="opacitymediumbycolor paddingleft">'.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).'</span>';
	}

	$facidavoir = $object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0) {
		$invoicecredits = array();
		foreach ($facidavoir as $facid) {
			if ($type == 'bank-transfer') {
				$facavoir = new FactureFournisseur($db);
			} else {
				$facavoir = new Facture($db);
			}
			$facavoir->fetch($facid);
			$invoicecredits[] = $facavoir->getNomUrl(1);
		}
		print ' <span class="opacitymediumbycolor paddingleft">'.$langs->transnoentities("InvoiceHasAvoir");
		print ' '. (count($invoicecredits) ? ' ' : '') . implode(',', $invoicecredits);
		print '</span>';
	}
	/*
	if ($objectidnext > 0) {
		$facthatreplace=new Facture($db);
		$facthatreplace->fetch($objectidnext);
		print ' <span class="opacitymediumbycolor paddingleft">'.str_replace('{s1}', $facthatreplace->getNomUrl(1), $langs->transnoentities("ReplacedByInvoice", '{s1}')).'</span>';
	}
	*/
	print '</td></tr>';

	// Relative and absolute discounts
	print '<!-- Discounts -->'."\n";
	print '<tr><td>'.$langs->trans('DiscountStillRemaining').'</td><td colspan="3">';

	if ($type == 'bank-transfer') {
		//$societe = new Fournisseur($db);
		//$result = $societe->fetch($object->socid);
		$thirdparty = $object->thirdparty;
		$discount_type = 1;
	} else {
		$thirdparty = $object->thirdparty;
		$discount_type = 0;
	}
	$backtopage = urlencode($_SERVER["PHP_SELF"].'?facid='.$object->id);
	$cannotApplyDiscount = 1;
	include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

	print '</td></tr>';

	// Label
	if ($type == 'bank-transfer') {
		print '<tr>';
		print '<td>'.$form->editfieldkey("Label", 'label', $object->label, $object, 0).'</td>';
		print '<td>'.$form->editfieldval("Label", 'label', $object->label, $object, 0).'</td>';
		print '</tr>';
	}

	// Date invoice
	print '<tr><td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('DateInvoice');
	print '</td>';
	if ($object->type != $object::TYPE_CREDIT_NOTE && $action != 'editinvoicedate' && $object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&token='.newToken().'&id='.$object->id.'&type='.urlencode($type).'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="3">';

	if ($object->type != $object::TYPE_CREDIT_NOTE) {
		if ($action == 'editinvoicedate') {
			print $form->form_date($_SERVER['PHP_SELF'].'?id='.$object->id, $object->date, 'invoicedate', 0, 0, 1, $type);
		} else {
			print dol_print_date($object->date, 'day');
		}
	} else {
		print dol_print_date($object->date, 'day');
	}
	print '</td>';
	print '</tr>';

	// Payment condition
	print '<tr><td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($object->type != $object::TYPE_CREDIT_NOTE && $action != 'editconditions' && $object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&id='.$object->id.'&type='.urlencode($type).'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->type != $object::TYPE_CREDIT_NOTE) {
		if ($action == 'editconditions') {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 0, $type);
		} else {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none');
		}
	} else {
		print '&nbsp;';
	}
	print '</td></tr>';

	// Date payment term
	print '<tr><td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('DateMaxPayment');
	print '</td>';
	if ($object->type != $object::TYPE_CREDIT_NOTE && $action != 'editpaymentterm' && $object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&token='.newToken().'&id='.$object->id.'&type='.urlencode($type).'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->type != $object::TYPE_CREDIT_NOTE) {
		$duedate = $object->date_lim_reglement;
		if ($type == 'bank-transfer') {
			$duedate = $object->date_echeance;
		}

		if ($action == 'editpaymentterm') {
			print $form->form_date($_SERVER['PHP_SELF'].'?id='.$object->id, $duedate, 'paymentterm', 0, 0, 1, $type);
		} else {
			print dol_print_date($duedate, 'day');
			if ($object->hasDelay()) {
				print img_warning($langs->trans('Late'));
			}
		}
	} else {
		print '&nbsp;';
	}
	print '</td></tr>';

	// Payment mode
	print '<tr><td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && $object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'&type='.urlencode($type).'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="3">';
	$filtertype = 'CRDT';
	if ($type == 'bank-transfer') {
		$filtertype = 'DBIT';
	}
	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', $filtertype, 1, 0, $type);
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
	}
	print '</td></tr>';

	// Bank Account
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('BankAccount');
	print '<td>';
	if (($action != 'editbankaccount') && $user->hasRight('commande', 'creer') && $object->status == $object::STATUS_DRAFT) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'&type='.urlencode($type).'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editbankaccount') {
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
	} else {
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
	}
	print "</td>";
	print '</tr>';


	//	-------------------------------------------------------------
	//	MODIFICATIONS START
	//	-------------------------------------------------------------

	print '<tr><td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('CustomerIBAN');
	print '</td>';
	if ($action != 'editmode' && $object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editrib&token='.newToken().'&id='.$object->id.'&type='.urlencode($type).'">'.img_edit($langs->trans('SetRib'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="3">';

//	$bac = new CompanyBankAccount($db);
	$ribList = $object->thirdparty->get_all_rib();
	$ribForSelection = [];
	$default_rib = '';
	foreach($ribList as $rib) {
		$ribString = $rib->iban . (($rib->iban && $rib->bic) ? ' / ' : '') . $rib->bic;

		$ribForSelection[] = $ribString;
		if($rib->default_rib == 1){
			$default_rib = $ribString;
		}
	}

	if ($action == 'editrib') {
		$form->form_iban($_SERVER['PHP_SELF'].'?id='.$object->id, $default_rib, 'ribList', $filtertype, 1, 0, $type, 0, $ribForSelection);
	} else {
		$form->form_iban($_SERVER['PHP_SELF'].'?id='.$object->id, $default_rib, 'none');
	}
	if (!empty($rib->iban)) {
		if ($rib->verif() <= 0) {
			print img_warning('Error on default bank number for IBAN : '.$langs->trans($rib->error));
		}
	} else {
		if ($numopen || ($type != 'bank-transfer' && $object->mode_reglement_code == 'PRE') || ($type == 'bank-transfer' && $object->mode_reglement_code == 'VIR')) {
			print img_warning($langs->trans("NoDefaultIBANFound"));
		}
	}
	print '</td></tr>';

	//	-------------------------------------------------------------
	//	MODIFICATIONS END
	//	-------------------------------------------------------------

	// IBAN of seller or supplier
	$title = 'CustomerIBAN';
	if ($type == 'bank-transfer') {
		$title = 'SupplierIBAN';
	}
	print '<tr><td>'.$langs->trans($title).'</td><td colspan="3">';

	$bac = new CompanyBankAccount($db);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	$bac->fetch(0, '', $object->thirdparty->id);

	print $bac->iban.(($bac->iban && $bac->bic) ? ' / ' : '').$bac->bic;
	if (!empty($bac->iban)) {
		if ($bac->verif() <= 0) {
			print img_warning('Error on default bank number for IBAN : '.$langs->trans($bac->error));
		}
	} else {
		if ($numopen || ($type != 'bank-transfer' && $object->mode_reglement_code == 'PRE') || ($type == 'bank-transfer' && $object->mode_reglement_code == 'VIR')) {
			print img_warning($langs->trans("NoDefaultIBANFound"));
		}
	}

	print '</td></tr>';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
		// Multicurrency Amount HT
		print '<tr><td class="titlefieldmiddle">'.$form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0).'</td>';
		print '<td class="nowrap">'.price($object->multicurrency_total_ht, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';

		// Multicurrency Amount VAT
		print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0).'</td>';
		print '<td class="nowrap">'.price($object->multicurrency_total_tva, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';

		// Multicurrency Amount TTC
		print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0).'</td>';
		print '<td class="nowrap">'.price($object->multicurrency_total_ttc, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';
	}

	// Amount
	print '<tr><td class="titlefield">'.$langs->trans('AmountHT').'</td>';
	print '<td class="nowrap right">'.price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';

	// Vat
	print '<tr><td>'.$langs->trans('AmountVAT').'</td><td class="nowrap right">'.price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	print '</tr>';

	// Amount Local Taxes
	if (($mysoc->localtax1_assuj == "1" && $mysoc->useLocalTax(1)) || $object->total_localtax1 != 0) { 	// Localtax1
		print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
		print '<td class="nowrap right">'.price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	}
	if (($mysoc->localtax2_assuj == "1" && $mysoc->useLocalTax(2)) || $object->total_localtax2 != 0) { 	// Localtax2
		print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
		print '<td class=nowrap right">'.price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	}

	// Revenue stamp
	if ($selleruserevenustamp) { 	// Test company use revenue stamp
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('RevenueStamp');
		print '</td>';
		if ($action != 'editrevenuestamp' && $object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editrevenuestamp&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetRevenuStamp'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td class="nowrap right">';
		print price($object->revenuestamp, 1, '', 1, - 1, - 1, $conf->currency);
		print '</td></tr>';
	}

	// Total with tax
	print '<tr><td>'.$langs->trans('AmountTTC').'</td><td class="nowrap right">'.price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';

	$resteapayer = price2num($object->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');

	// Hook to change amount for other reasons, e.g. apply cash discount for payment before agreed date
	$parameters = array('remaintopay' => $resteapayer);
	$reshook = $hookmanager->executeHooks('finalizeAmountOfInvoice', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) {
		print $hookmanager->resPrint;
		if (!empty($remaintopay = $hookmanager->resArray['remaintopay'])) {
			$resteapayer = $remaintopay;
		}
	}

	// TODO Replace this by an include with same code to show already done payment visible in invoice card
	print '<tr><td>'.$langs->trans('RemainderToPay').'</td><td class="nowrap right">'.price($resteapayer, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';

	print '</table>';

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	print dol_get_fiche_end();


	// For which amount ?

	$sql = "SELECT SUM(pfd.amount) as amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
	if ($type == 'bank-transfer') {
		$sql .= " WHERE fk_facture_fourn = ".((int) $object->id);
	} else {
		$sql .= " WHERE fk_facture = ".((int) $object->id);
	}
	$sql .= " AND pfd.traite = 0";
	$sql .= " AND pfd.type = 'ban'";

	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$pending = $obj->amount;
		}
	} else {
		dol_print_error($db);
	}


	/*
	 * Buttons
	 */

	print "\n".'<div class="tabsAction">'."\n";

	$buttonlabel = $langs->trans("MakeWithdrawRequest");
	$user_perms = $user->hasRight('prelevement', 'bons', 'creer');
	if ($type == 'bank-transfer') {
		$buttonlabel = $langs->trans("MakeBankTransferOrder");
		$user_perms = $user->hasRight('paymentbybanktransfer', 'create');
	}

	// Add a transfer request
	if ($object->status > $object::STATUS_DRAFT && $object->paid == 0 && $num == 0) {
		if ($resteapayer > 0) {
			if ($user_perms) {
				$remaintopaylesspendingdebit = $resteapayer - $pending;

				print '<form method="POST" action="">';
				print '<input type="hidden" name="token" value="'.newToken().'" />';
				print '<input type="hidden" name="id" value="'.$object->id.'" />';
				print '<input type="hidden" name="type" value="'.$type.'" />';
				print '<input type="hidden" name="action" value="new" />';
				print '<label for="withdraw_request_amount">'.$langs->trans('BankTransferAmount').' </label>';
				print '<input type="text" id="withdraw_request_amount" name="withdraw_request_amount" value="'.$remaintopaylesspendingdebit.'" size="9" />';
				print '<input type="submit" class="butAction" value="'.$buttonlabel.'" />';
				print '</form>';

				if (getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT_SHOW_OLD_BUTTON')) {	// This is hidden, prefer to use mode enabled with STRIPE_SEPA_DIRECT_DEBIT
					// TODO Replace this with a checkbox for each payment mode: "Send request to XXX immediately..."
					print "<br>";
					//add stripe sepa button
					$buttonlabel = $langs->trans("MakeWithdrawRequestStripe");
					print '<form method="POST" action="">';
					print '<input type="hidden" name="token" value="'.newToken().'" />';
					print '<input type="hidden" name="id" value="'.$object->id.'" />';
					print '<input type="hidden" name="type" value="'.$type.'" />';
					print '<input type="hidden" name="action" value="new" />';
					print '<input type="hidden" name="paymenservice" value="stripesepa" />';
					print '<label for="withdraw_request_amount">'.$langs->trans('BankTransferAmount').' </label>';
					print '<input type="text" id="withdraw_request_amount" name="withdraw_request_amount" value="'.$remaintopaylesspendingdebit.'" size="9" />';
					print '<input type="submit" class="butAction" value="'.$buttonlabel.'" />';
					print '</form>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$buttonlabel.'</a>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("AmountMustBePositive")).'">'.$buttonlabel.'</a>';
		}
	} else {
		if ($num == 0) {
			if ($object->status > $object::STATUS_DRAFT) {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("AlreadyPaid")).'">'.$buttonlabel.'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("Draft")).'">'.$buttonlabel.'</a>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("RequestAlreadyDone")).'">'.$buttonlabel.'</a>';
		}
	}

	print "</div>\n";


	if ($type == 'bank-transfer') {
		print '<div class="opacitymedium justify">'.$langs->trans("DoCreditTransferBeforePayments");
		if (isModEnabled('stripe') && getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT')) {
			print ' '.$langs->trans("DoStandingOrdersBeforePayments2");
		}
		print ' '.$langs->trans("DoStandingOrdersBeforePayments3");
		print '</div><br>';
	} else {
		print '<div class="opacitymedium justify">'.$langs->trans("DoStandingOrdersBeforePayments");
		if (isModEnabled('stripe') && getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT')) {
			print ' '.$langs->trans("DoStandingOrdersBeforePayments2");
		}
		print ' '.$langs->trans("DoStandingOrdersBeforePayments3");
		print '</div><br>';
	}

	/*
	 * Withdrawals
	 */

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td>&nbsp;</td>';
	}
	print '<td class="left">'.$langs->trans("DateRequest").'</td>';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print '<td class="center">'.$langs->trans("DateProcess").'</td>';
	if ($type == 'bank-transfer') {
		print '<td class="center">'.$langs->trans("BankTransferReceipt").'</td>';
	} else {
		print '<td class="center">'.$langs->trans("WithdrawalReceipt").'</td>';
	}
	print '<td>&nbsp;</td>';
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td>&nbsp;</td>';
	}
	print '</tr>';

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande,";
	$sql .= " pfd.date_traite as date_traite, pfd.amount, pfd.fk_prelevement_bons,";
	$sql .= " pb.ref, pb.date_trans, pb.method_trans, pb.credite, pb.date_credit, pb.datec, pb.statut as status, pb.amount as pb_amount,";
	$sql .= " u.rowid as user_id, u.email, u.lastname, u.firstname, u.login, u.statut as user_status";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
	if ($type == 'bank-transfer') {
		$sql .= " WHERE fk_facture_fourn = ".((int) $object->id);
	} else {
		$sql .= " WHERE fk_facture = ".((int) $object->id);
	}
	$sql .= " AND pfd.traite = 0";
	$sql .= " AND pfd.type = 'ban'";
	$sql .= " ORDER BY pfd.date_demande DESC";

	$resql = $db->query($sql);

	$num = 0;
	if ($resql) {
		$i = 0;

		$tmpuser = new User($db);

		$num = $db->num_rows($result);
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$tmpuser->id = $obj->user_id;
			$tmpuser->login = $obj->login;
			$tmpuser->ref = $obj->login;
			$tmpuser->email = $obj->email;
			$tmpuser->lastname = $obj->lastname;
			$tmpuser->firstname = $obj->firstname;
			$tmpuser->statut = $obj->user_status;
			$tmpuser->status = $obj->user_status;

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'&did='.$obj->rowid.'&type='.urlencode($type).'">';
				print img_delete();
				print '</a>';
				print '</td>';
			}

			// Date
			print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'dayhour')."</td>\n";

			// User
			print '<td class="tdoverflowmax125">';
			print $tmpuser->getNomUrl(-1, '', 0, 0, 0, 0, 'login');
			print '</td>';

			// Amount
			print '<td class="center"><span class="amount">'.price($obj->amount).'</span></td>';

			// Date process
			print '<td class="center"><span class="opacitymedium">'.$langs->trans("OrderWaiting").'</span></td>';

			// Link to make payment now
			print '<td class="minwidth75">';
			if ($obj->fk_prelevement_bons > 0) {
				$withdrawreceipt = new BonPrelevement($db);
				$withdrawreceipt->id = $obj->fk_prelevement_bons;
				$withdrawreceipt->ref = $obj->ref;
				$withdrawreceipt->date_trans = $db->jdate($obj->date_trans);
				$withdrawreceipt->date_credit = $db->jdate($obj->date_credit);
				$withdrawreceipt->date_creation = $db->jdate($obj->datec);
				$withdrawreceipt->statut = $obj->status;
				$withdrawreceipt->status = $obj->status;
				$withdrawreceipt->amount = $obj->pb_amount;
				//$withdrawreceipt->credite = $db->jdate($obj->credite);

				print $withdrawreceipt->getNomUrl(1);
			}

			if ($type != 'bank-transfer') {
				if (getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT')) {
					$langs->load("stripe");
					if ($obj->fk_prelevement_bons > 0) {
						print ' &nbsp; ';
					}
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=sepastripedirectdebit&paymentservice=stripesepa&token='.newToken().'&did='.$obj->rowid.'&id='.$object->id.'&type='.urlencode($type).'">'.img_picto('', 'stripe', 'class="pictofixedwidth"').$langs->trans("RequestDirectDebitWithStripe").'</a>';
				}
			} else {
				if (getDolGlobalString('STRIPE_SEPA_CREDIT_TRANSFER')) {
					$langs->load("stripe");
					if ($obj->fk_prelevement_bons > 0) {
						print ' &nbsp; ';
					}
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=sepastripecredittransfer&paymentservice=stripesepa&token='.newToken().'&did='.$obj->rowid.'&id='.$object->id.'&type='.urlencode($type).'">'.img_picto('', 'stripe', 'class="pictofixedwidth"').$langs->trans("RequestDirectDebitWithStripe").'</a>';
				}
			}
			print '</td>';

			//
			print '<td class="center">-</td>';

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'&did='.$obj->rowid.'&type='.urlencode($type).'">';
				print img_delete();
				print '</a></td>';
			}

			print "</tr>\n";
			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}


	// Past requests

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande, pfd.date_traite, pfd.fk_prelevement_bons, pfd.amount,";
	$sql .= " pb.ref, pb.date_trans, pb.method_trans, pb.credite, pb.date_credit, pb.datec, pb.statut as status, pb.fk_bank_account, pb.amount as pb_amount,";
	$sql .= " u.rowid as user_id, u.email, u.lastname, u.firstname, u.login, u.statut as user_status, u.photo as user_photo";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
	if ($type == 'bank-transfer') {
		$sql .= " WHERE fk_facture_fourn = ".((int) $object->id);
	} else {
		$sql .= " WHERE fk_facture = ".((int) $object->id);
	}
	$sql .= " AND pfd.traite = 1";
	$sql .= " AND pfd.type = 'ban'";
	$sql .= " ORDER BY pfd.date_demande DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$numclosed = $num;
		$i = 0;

		$tmpuser = new User($db);

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$tmpuser->id = $obj->user_id;
			$tmpuser->login = $obj->login;
			$tmpuser->ref = $obj->login;
			$tmpuser->email = $obj->email;
			$tmpuser->lastname = $obj->lastname;
			$tmpuser->firstname = $obj->firstname;
			$tmpuser->statut = $obj->user_status;
			$tmpuser->status = $obj->user_status;
			$tmpuser->photo = $obj->user_photo;

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td>&nbsp;</td>';
			}

			// Date
			print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'day')."</td>\n";

			// User
			print '<td class="tdoverflowmax125">';
			print $tmpuser->getNomUrl(-1, '', 0, 0, 0, 0, 'login');
			print '</td>';

			// Amount
			print '<td class="center"><span class="amount">'.price($obj->amount).'</span></td>';

			// Date process
			print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_traite), 'dayhour', 'tzuserrel')."</td>\n";

			// Link to payment request done
			print '<td class="center minwidth75">';
			if ($obj->fk_prelevement_bons > 0) {
				$withdrawreceipt = new BonPrelevement($db);
				$withdrawreceipt->id = $obj->fk_prelevement_bons;
				$withdrawreceipt->ref = $obj->ref;
				$withdrawreceipt->date_trans = $db->jdate($obj->date_trans);
				$withdrawreceipt->date_credit = $db->jdate($obj->date_credit);
				$withdrawreceipt->date_creation = $db->jdate($obj->datec);
				$withdrawreceipt->statut = $obj->status;
				$withdrawreceipt->status = $obj->status;
				$withdrawreceipt->fk_bank_account = $obj->fk_bank_account;
				$withdrawreceipt->amount = $obj->pb_amount;
				//$withdrawreceipt->credite = $db->jdate($obj->credite);

				print $withdrawreceipt->getNomUrl(1);
				print ' ';
				print $withdrawreceipt->getLibStatut(2);

				// Show the bank account
				$fk_bank_account = $withdrawreceipt->fk_bank_account;
				if (empty($fk_bank_account)) {
					$fk_bank_account = ($object->type == 'bank-transfer' ? $conf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT : $conf->global->PRELEVEMENT_ID_BANKACCOUNT);
				}
				if ($fk_bank_account > 0) {
					$bankaccount = new Account($db);
					$result = $bankaccount->fetch($fk_bank_account);
					if ($result > 0) {
						print ' - ';
						print $bankaccount->getNomUrl(1);
					}
				}
			}
			print "</td>\n";

			//
			print '<td>&nbsp;</td>';

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td>&nbsp;</td>';
			}

			print "</tr>\n";
			$i++;
		}

		if (!$numopen && !$numclosed) {
			print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print "</table>";
	print '</div>';
}

// End of page
llxFooter();
$db->close();
