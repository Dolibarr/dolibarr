<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2023  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2016       Meziane Sof             <virtualsof@yahoo.fr>
 * Copyright (C) 2017-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023       Nick Fragoulis
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
 *	\file       htdocs/compta/facture/card-rec.php
 *	\ingroup    invoice
 *	\brief      Page to show predefined invoice
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (isModEnabled('project')) {
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	//include_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'compta', 'admin', 'other', 'products', 'banks'));

$action     = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'invoicetemplatelist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used


$id = (GETPOSTINT('facid') ? GETPOSTINT('facid') : GETPOSTINT('id'));
$lineid = GETPOSTINT('lineid');
$ref = GETPOST('ref', 'alpha');
if ($user->socid) {
	$socid = $user->socid;
}
$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") {
	$objecttype = '';
}
$projectid = GETPOSTINT('projectid');

$year_date_when = GETPOST('year_date_when');
$month_date_when = GETPOST('month_date_when');
$selectedLines = GETPOST('toselect', 'array');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = 'DESC';
}
if (!$sortfield) {
	$sortfield = 'f.titre';
}
$pageprev = $page - 1;
$pagenext = $page + 1;

$object = new FactureRec($db);
if (($id > 0 || $ref) && $action != 'create' && $action != 'add') {
	$ret = $object->fetch($id, $ref);
	if (!$ret) {
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
	}
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('invoicereccard', 'globalcard'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$permissionnote = $user->hasRight('facture', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('facture', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->hasRight('facture', 'creer'); // Used by the include of actions_lineupdonw.inc.php

$usercanread = $user->hasRight('facture', 'lire');
$usercancreate = $user->hasRight('facture', 'creer');
$usercanissuepayment = $user->hasRight('facture', 'paiement');
$usercandelete = $user->hasRight('facture', 'supprimer');

// Advanced permissions
$usercanvalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('facture', 'invoice_advance', 'validate')));
$usercansend = (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->rights->facture->invoice_advance->send);
$usercanreopen = (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->rights->facture->invoice_advance->reopen);
$usercanunvalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !empty($usercancreate)) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('facture', 'invoice_advance', 'unvalidate')));

// Other permissions
$usercanproductignorepricemin = ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('produit', 'ignore_price_min_advance')) || !getDolGlobalString('MAIN_USE_ADVANCED_PERMS'));
$usercancreatemargin = $user->hasRight("margins", "creer");
$usercanreadallmargin = $user->hasRight("margins", "liretous");
$usercancreatewithdrarequest = $user->hasRight("prelevement", "bons", "creer");

$now = dol_now();

$error = 0;

// Security check
$result = restrictedArea($user, 'facture', $object->id, $objecttype);


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/compta/facture/invoicetemplate_list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/compta/facture/invoicetemplate_list.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	// include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
	if ($cancel) {
		/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Set note
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be 'include', not 'include_once'

	// Mass actions
	/*$objectclass='MyObject';
	$objectlabel='MyObject';
	$uploaddir = $conf->mymodule->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';*/

	// Create predefined invoice
	if ($action == 'add') {
		if (!GETPOST('title', 'alphanohtml')) {
			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("Title")), null, 'errors');
			$action = "create";
			$error++;
		}

		$frequency = GETPOSTINT('frequency');
		$reyear = GETPOSTINT('reyear');
		$remonth = GETPOSTINT('remonth');
		$reday = GETPOSTINT('reday');
		$rehour = GETPOSTINT('rehour');
		$remin = GETPOSTINT('remin');
		$nb_gen_max = GETPOSTINT('nb_gen_max');
		//if (empty($nb_gen_max)) $nb_gen_max =0;

		if (GETPOSTINT('frequency')) {
			if (empty($reyear) || empty($remonth) || empty($reday)) {
				setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("Date")), null, 'errors');
				$action = "create";
				$error++;
			}
			/*if ($nb_gen_max === '') {
				setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("MaxPeriodNumber")), null, 'errors');
				$action = "create";
				$error++;
			}*/
		}

		if (!$error) {
			$object->subtype               = GETPOSTINT('subtype');
			$object->title                 = GETPOST('title', 'alphanohtml');
			$object->note_private          = GETPOST('note_private', 'restricthtml');
			$object->note_public           = GETPOST('note_public', 'restricthtml');
			$object->model_pdf             = GETPOST('modelpdf', 'alphanohtml');
			$object->usenewprice           = GETPOST('usenewprice', 'alphanohtml');

			$object->mode_reglement_id     = GETPOSTINT('mode_reglement_id');
			$object->cond_reglement_id     = GETPOSTINT('cond_reglement_id');

			$object->frequency             = $frequency;
			$object->unit_frequency        = GETPOST('unit_frequency', 'alpha');
			$object->nb_gen_max            = $nb_gen_max;
			$object->auto_validate         = GETPOSTINT('auto_validate');
			$object->generate_pdf          = GETPOSTINT('generate_pdf');
			$object->fk_project            = $projectid;

			$date_next_execution = dol_mktime($rehour, $remin, 0, $remonth, $reday, $reyear);
			$object->date_when = $date_next_execution;

			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			// Get first contract linked to invoice used to generate template (facid is id of source invoice)
			if (GETPOSTINT('facid') > 0) {
				$srcObject = new Facture($db);
				$srcObject->fetch(GETPOSTINT('facid'));

				$srcObject->fetchObjectLinked();

				if (!empty($srcObject->linkedObjectsIds['contrat'])) {
					$contractidid = reset($srcObject->linkedObjectsIds['contrat']);

					$object->origin = 'contrat';
					$object->origin_id = $contractidid;
					$object->linked_objects[$object->origin] = $object->origin_id;
				}
			}

			$db->begin();

			$oldinvoice = new Facture($db);
			$oldinvoice->fetch(GETPOSTINT('facid'));

			$onlylines = GETPOST('toselect', 'array');

			$result = $object->create($user, $oldinvoice->id, 0, $onlylines);
			if ($result > 0) {
				$result = $oldinvoice->delete($user, 1);
				if ($result < 0) {
					$error++;
					setEventMessages($oldinvoice->error, $oldinvoice->errors, 'errors');
					$action = "create";
				}
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create";
			}

			if (!$error) {
				$db->commit();

				header("Location: ".$_SERVER['PHP_SELF'].'?facid='.$object->id);
				exit;
			} else {
				$db->rollback();

				$action = "create";
			}
		}
	}

	// Delete
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('facture', 'supprimer')) {
		$object->delete($user);

		header("Location: ".DOL_URL_ROOT.'/compta/facture/invoicetemplate_list.php');
		exit;
	}


	// Update field
	if ($action == 'setconditions' && $usercancreate) {
		// Set condition
		$object->context['actionmsg'] = $langs->trans("FieldXModified", $langs->transnoentitiesnoconv("PaymentTerm"));
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'));
	} elseif ($action == 'setmode' && $usercancreate) {
		// Set mode
		$object->context['actionmsg'] = $langs->trans("FieldXModified", $langs->transnoentitiesnoconv("PaymentMode"));
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
	} elseif ($action == 'classin' && $usercancreate) {
		// Set project
		$object->context['actionmsg'] = $langs->trans("FieldXModified", $langs->transnoentitiesnoconv("Project"));
		$object->setProject(GETPOSTINT('projectid'));
	} elseif ($action == 'setref' && $usercancreate) {
		// Set bank account
		$object->context['actionmsg'] = $langs->trans("FieldXModifiedFromYToZ", $langs->transnoentitiesnoconv("Title"), $object->title, $ref);
		$result = $object->setValueFrom('titre', $ref, '', null, 'text', '', $user, 'BILLREC_MODIFY');
		if ($result > 0) {
			$object->title = $ref;
			$object->ref = $object->title;
		} else {
			$error++;
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessages($langs->trans('ErrorRefAlreadyExists', $ref), null, 'errors');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'setbankaccount' && $usercancreate) {
		// Set bank account
		$object->context['actionmsg'] = $langs->trans("FieldXModified", $langs->transnoentitiesnoconv("Bank"));
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
	} elseif ($action == 'setfrequency' && $usercancreate) {
		// Set frequency and unit frequency
		$object->context['actionmsg'] = $langs->trans("FieldXModified", $langs->transnoentitiesnoconv("Frequency"));
		$object->setFrequencyAndUnit(GETPOSTINT('frequency'), GETPOST('unit_frequency'));
	} elseif ($action == 'setdate_when' && $usercancreate) {
		// Set next date of execution
		$date = dol_mktime(GETPOST('date_whenhour'), GETPOST('date_whenmin'), 0, GETPOST('date_whenmonth'), GETPOST('date_whenday'), GETPOST('date_whenyear'));
		if (!empty($date)) {
			$object->setNextDate($date);
		}
	} elseif ($action == 'setnb_gen_max' && $usercancreate) {
		// Set max period
		$object->setMaxPeriod(GETPOSTINT('nb_gen_max'));
	} elseif ($action == 'setauto_validate' && $usercancreate) {
		// Set auto validate
		$object->setAutoValidate(GETPOSTINT('auto_validate'));
	} elseif ($action == 'setgenerate_pdf' && $usercancreate) {
		// Set generate pdf
		$object->setGeneratepdf(GETPOSTINT('generate_pdf'));
	} elseif ($action == 'setmodelpdf' && $usercancreate) {
		// Set model pdf
		$object->setModelpdf(GETPOST('modelpdf', 'alpha'));
	} elseif ($action == 'disable' && $usercancreate) {
		// Set status disabled
		$db->begin();

		$object->context['actionmsg'] = $langs->trans("RecordDisabled");

		$res = $object->setValueFrom('suspended', 1, '', null, 'text', '', $user, 'BILLREC_MODIFY');
		if ($res <= 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'enable' && $usercancreate) {
		// Set status enabled
		$db->begin();

		$object->context['actionmsg'] = $langs->trans("RecordEnabled");

		$res = $object->setValueFrom('suspended', 0, '', null, 'text', '', $user, 'BILLREC_MODIFY');
		if ($res <= 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		// Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOSTINT('calculation_mode'));
	}

	// Delete line
	if ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		$object->fetch($id);
		$object->fetch_thirdparty();

		$db->begin();

		$line = new FactureLigneRec($db);

		// For triggers
		$line->id = $lineid;

		if ($line->delete($user) > 0) {
			$result = $object->update_price(1);

			if ($result > 0) {
				$db->commit();
				$object->fetch($object->id); // Reload lines
			} else {
				$db->rollback();
				setEventMessages($db->lasterror(), null, 'errors');
			}
		} else {
			$db->rollback();
			setEventMessages($line->error, $line->errors, 'errors');
		}
	} elseif ($action == 'update_extras' && $usercancreate) {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->insertExtraFields('BILLREC_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}
	}

	// Add a new line
	if ($action == 'addline' && $user->hasRight('facture', 'creer')) {
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');
		$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
		$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'), 'CU', 2);
		$prod_entry_mode = GETPOST('prod_entry_mode', 'alpha');
		if ($prod_entry_mode == 'free') {
			$idprod = 0;
		} else {
			$idprod = GETPOSTINT('idprod');

			if (getDolGlobalString('MAIN_DISABLE_FREE_LINES') && $idprod <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")), null, 'errors');
				$error++;
			}
		}

		$tva_tx = (GETPOST('tva_tx', 'alpha') ? GETPOST('tva_tx', 'alpha') : 0);

		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS', 2);
		$remise_percent = price2num(GETPOST('remise_percent'.$predef), '', 2);
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}

		// Extrafields
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line, $predef);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key.$predef]);
			}
		}

		if ((empty($idprod) || $idprod < 0) && ($price_ht < 0) && ($qty < 0)) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && GETPOST('type') < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && (!($price_ht >= 0) || $price_ht == '')) { 	// Unit price can be 0 but not ''
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
			$error++;
		}
		if ($qty == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && empty($product_desc)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
			$error++;
		}
		if ($qty < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), null, 'errors');
			$error++;
		}

		if (!$error && ($qty >= 0) && (!empty($product_desc) || (!empty($idprod) && $idprod > 0))) {
			$ret = $object->fetch($id);
			if ($ret < 0) {
				dol_print_error($db, $object->error);
				exit();
			}
			$ret = $object->fetch_thirdparty();

			// Clean parameters
			$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');
			$tva_npr = "";

			// Define special_code for special lines
			$special_code = 0;
			// if (!GETPOST('qty')) $special_code=3; // Options should not exists on invoices

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $base_price_type par celui du produit
			// Replaces $fk_unit with the product's
			if (!empty($idprod) && $idprod > 0) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Update if prices fields are defined
				//$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
				//$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
				//if (empty($tva_tx)) {
				//	$tva_npr = 0;
				//}

				// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
				$pqp = (GETPOSTINT('pbq') ? GETPOSTINT('pbq') : 0);

				$datapriceofproduct = $prod->getSellPrice($mysoc, $object->thirdparty, $pqp);

				$pu_ht = $datapriceofproduct['pu_ht'];
				$pu_ttc = $datapriceofproduct['pu_ttc'];
				$price_min = $datapriceofproduct['price_min'];
				$price_base_type = empty($datapriceofproduct['price_base_type']) ? 'HT' : $datapriceofproduct['price_base_type'];
				//$tva_tx = $datapriceofproduct['tva_tx'];
				//$tva_npr = $datapriceofproduct['tva_npr'];

				$tmpvat = (float) price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', (string) $prod->tva_tx));

				// if price ht was forced (ie: from gui when calculated by margin rate and cost price). TODO Why this ?
				if (!empty($price_ht)) {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num((float) $pu_ht * (1 + ($tmpvat / 100)), 'MU');
				} elseif ($tmpvat != $tmpprodvat) {
					// On reevalue prix selon taux tva car taux tva transaction peut etre different
					// de ceux du produit par default (par example si pays different entre vendeur et acheteur).
					if ($price_base_type != 'HT') {
						$pu_ht = price2num((float) $pu_ttc / (1 + ($tmpvat / 100)), 'MU');
					} else {
						$pu_ttc = price2num((float) $pu_ht * (1 + ($tmpvat / 100)), 'MU');
					}
				}

				$desc = '';

				// Define output language
				if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$desc = (!empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
				} else {
					$desc = $prod->description;
				}

				$desc = dol_concatdesc($desc, $product_desc);

				// Add custom code and origin country into description
				if (!getDolGlobalString('MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE') && (!empty($prod->customcode) || !empty($prod->country_code))) {
					$tmptxt = '(';
					// Define output language
					if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id', 'alpha')) {
							$newlang = GETPOST('lang_id', 'alpha');
						}
						if (empty($newlang)) {
							$newlang = $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						if (!empty($prod->customcode)) {
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
						}
						if (!empty($prod->customcode) && !empty($prod->country_code)) {
							$tmptxt .= ' - ';
						}
						if (!empty($prod->country_code)) {
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $outputlangs, 0);
						}
					} else {
						if (!empty($prod->customcode)) {
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
						}
						if (!empty($prod->customcode) && !empty($prod->country_code)) {
							$tmptxt .= ' - ';
						}
						if (!empty($prod->country_code)) {
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $langs, 0);
						}
					}
					$tmptxt .= ')';
					$desc = dol_concatdesc($desc, $tmptxt);
				}

				$type = $prod->type;
				$fk_unit = $prod->fk_unit;
			} else {
				$pu_ht = price2num($price_ht, 'MU');
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				$tva_tx = str_replace('*', '', $tva_tx);
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');
				$fk_unit = GETPOST('units', 'alpha');
			}

			$date_start_fill = GETPOSTINT('date_start_fill');
			$date_end_fill = GETPOSTINT('date_end_fill');

			// Margin
			$fournprice = price2num(GETPOST('fournprice'.$predef) ? GETPOST('fournprice'.$predef) : '');
			$buyingprice = price2num(GETPOST('buying_price'.$predef) != '' ? GETPOST('buying_price'.$predef) : ''); // If buying_price is '0', we must keep this value

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_npr);

			$info_bits = 0;
			if ($tva_npr) {
				$info_bits |= 0x01;
			}

			if ($usercanproductignorepricemin && (!empty($price_min) && ((float) price2num($pu_ht) * (1 - (float) price2num($remise_percent) / 100) < (float) price2num($price_min)))) {
				$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
				setEventMessages($mesg, null, 'errors');
			} else {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $price_base_type, $info_bits, '', $pu_ttc, $type, -1, $special_code, $label, $fk_unit, 0, $date_start_fill, $date_end_fill, $fournprice, $buyingprice);

				if ($result > 0) {
					// Define output language and generate document
					/*if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
					{
						// Define output language
						$outputlangs = $langs;
						$newlang = '';
						if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
						if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang))	$newlang = $object->thirdparty->default_lang;
						if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
						}
						$model=$object->model_pdf;
						$ret = $object->fetch($id); // Reload to get new records

						$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
						if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
					}*/
					$object->fetch($object->id); // Reload lines

					unset($_POST['prod_entry_mode']);

					unset($_POST['qty']);
					unset($_POST['type']);
					unset($_POST['remise_percent']);
					unset($_POST['price_ht']);
					unset($_POST['multicurrency_price_ht']);
					unset($_POST['price_ttc']);
					unset($_POST['tva_tx']);
					unset($_POST['product_ref']);
					unset($_POST['product_label']);
					unset($_POST['product_desc']);
					unset($_POST['fournprice']);
					unset($_POST['buying_price']);
					unset($_POST['np_marginRate']);
					unset($_POST['np_markRate']);
					unset($_POST['dp_desc']);
					unset($_POST['idprod']);
					unset($_POST['units']);

					unset($_POST['date_starthour']);
					unset($_POST['date_startmin']);
					unset($_POST['date_startsec']);
					unset($_POST['date_startday']);
					unset($_POST['date_startmonth']);
					unset($_POST['date_startyear']);
					unset($_POST['date_endhour']);
					unset($_POST['date_endmin']);
					unset($_POST['date_endsec']);
					unset($_POST['date_endday']);
					unset($_POST['date_endmonth']);
					unset($_POST['date_endyear']);

					unset($_POST['date_start_fill']);
					unset($_POST['date_end_fill']);

					unset($_POST['situations']);
					unset($_POST['progress']);
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}

				$action = '';
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && !GETPOST('cancel', 'alpha')) {
		if (!$object->fetch($id) > 0) {
			dol_print_error($db);
		}
		$object->fetch_thirdparty();

		// Clean parameters
		$date_start = '';
		$date_end = '';
		//$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		//$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
		$description = dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml') ? GETPOST('product_desc', 'restricthtml') : GETPOST('desc', 'restricthtml'));
		$pu_ht = price2num(GETPOST('price_ht'), '', 2);
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$qty = GETPOST('qty');
		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), '', 2);

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate)) {
			$info_bits |= 0x01;
		}

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty);

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we must keep this value

		// Extrafields
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);

		$objectline = new FactureLigneRec($db);
		if ($objectline->fetch(GETPOSTINT('lineid'))) {
			$objectline->array_options = $array_options;
			$result = $objectline->insertExtraFields();
			if ($result < 0) {
				setEventMessages($langs->trans('Error').$result, null, 'errors');
			}
		}

		$position = ($objectline->rang >= 0 ? $objectline->rang : 0);

		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}

		// Define special_code for special lines
		$special_code = GETPOSTINT('special_code');
		if ($special_code == 3) {
			$special_code = 0;	// Options should not exists on invoices
		}

		/*$line = new FactureLigne($db);
		$line->fetch(GETPOST('lineid', 'int'));
		$percent = $line->get_prev_progress($object->id);

		if (GETPOST('progress') < $percent)
		{
				$mesg = '<div class="warning">' . $langs->trans("CantBeLessThanMinPercent") . '</div>';
				setEventMessages($mesg, null, 'warnings');
				$error++;
				$result = -1;
		}*/

		$remise_percent = price2num(GETPOST('remise_percent'), '', 2);
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}

		// Check minimum price
		$productid = GETPOSTINT('productid');
		if (!empty($productid)) {
			$product = new Product($db);
			$product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if (getDolGlobalString('PRODUIT_MULTIPRICES') && !empty($object->thirdparty->price_level)) {
				$price_min = $product->multiprices_min[$object->thirdparty->price_level];
			}

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			$typeinvoice = Facture::TYPE_STANDARD;

			// Check price is not lower than minimum (check is done only for standard or replacement invoices)
			if (((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('produit', 'ignore_price_min_advance')) || !getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) && (($typeinvoice == Facture::TYPE_STANDARD || $typeinvoice == Facture::TYPE_REPLACEMENT) && $price_min && ((float) price2num($pu_ht) * (1 - (float) $remise_percent / 100) < (float) price2num($price_min)))) {
				setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
				$error++;
			}
		} else {
			$type = GETPOSTINT('type');
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');

			// Check parameters
			if (GETPOSTINT('type') < 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
			}
		}
		if ($qty < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), null, 'errors');
			$error++;
		}

		$date_start_fill = GETPOSTINT('date_start_fill');
		$date_end_fill = GETPOSTINT('date_end_fill');

		// Update line
		if (!$error) {
			$result = $object->updateline(
				GETPOSTINT('lineid'),
				$description,
				$pu_ht,
				$qty,
				$vat_rate,
				$localtax1_rate,
				$localtax1_rate,
				GETPOSTINT('productid'),
				$remise_percent,
				'HT',
				$info_bits,
				0,
				0,
				$type,
				$position,
				$special_code,
				$label,
				GETPOST('units'),
				$pu_ht_devise,
				0,
				$date_start_fill,
				$date_end_fill,
				$fournprice,
				$buyingprice
			);

			if ($result >= 0) {
				/*if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id','aZ09'))
						$newlang = GETPOST('lang_id','aZ09');
						if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang))
							$newlang = $object->thirdparty->default_lang;
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}

							$ret = $object->fetch($id); // Reload to get new records
							$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}*/

				$object->fetch($object->id); // Reload lines

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['productid']);
				unset($_POST['remise_percent']);
				unset($_POST['price_ht']);
				unset($_POST['multicurrency_price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($_POST['np_marginRate']);
				unset($_POST['np_markRate']);

				unset($_POST['dp_desc']);
				unset($_POST['idprod']);
				unset($_POST['units']);

				unset($_POST['date_starthour']);
				unset($_POST['date_startmin']);
				unset($_POST['date_startsec']);
				unset($_POST['date_startday']);
				unset($_POST['date_startmonth']);
				unset($_POST['date_startyear']);
				unset($_POST['date_endhour']);
				unset($_POST['date_endmin']);
				unset($_POST['date_endsec']);
				unset($_POST['date_endday']);
				unset($_POST['date_endmonth']);
				unset($_POST['date_endyear']);

				unset($_POST['situations']);
				unset($_POST['progress']);
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
}


/*
 *	View
 */

$title = $object->ref." - ".$langs->trans('Card');
$help_url = '';

llxHeader('', $title, $help_url);

$form = new Form($db);
$formother = new FormOther($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$companystatic = new Societe($db);
$invoicerectmp = new FactureRec($db);

$now = dol_now();
$nowlasthour = dol_get_last_hour($now);


/*
 * Create mode
 */
if ($action == 'create') {
	print load_fiche_titre($langs->trans("CreateRepeatableInvoice"), '', 'bill');

	$object = new Facture($db); // Source invoice
	$product_static = new Product($db);

	if ($object->fetch($id, $ref) > 0) {
		$result = $object->getLinesArray();

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$object->id.'">';

		print dol_get_fiche_head(null, '', '', 0);

		$rowspan = 4;
		if (isModEnabled('project')) {
			$rowspan++;
		}
		if ($object->fk_account > 0) {
			$rowspan++;
		}

		print '<table class="border centpercent">';

		$object->fetch_thirdparty();

		// Title
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Title").'</td><td>';
		print '<input class="flat quatrevingtpercent" type="text" name="title" value="'.dol_escape_htmltag(GETPOST("title", 'alphanohtml')).'" autofocus>';
		print '</td></tr>';

		// Third party
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Customer").'</td><td>'.$object->thirdparty->getNomUrl(1, 'customer').'</td>';
		print '</tr>';

		// Invoice subtype
		if (getDolGlobalInt('INVOICE_SUBTYPE_ENABLED')) {
			print "<tr><td>".$langs->trans("InvoiceSubtype")."</td><td>";
			print $form->getSelectInvoiceSubtype(GETPOSTISSET('subtype') ? GETPOST('subtype') : $object->subtype, 'subtype', 0, 0, '');
			print "</td></tr>";
		}

		$note_public = GETPOSTISSET('note_public') ? GETPOST('note_public', 'restricthtml') : $object->note_public;
		$note_private = GETPOSTISSET('note_private') ? GETPOST('note_private', 'restricthtml') : $object->note_private;

		// Help of substitution key
		$substitutionarray = getCommonSubstitutionArray($langs, 2, null, $object);

		$substitutionarray['__INVOICE_PREVIOUS_MONTH__'] = $langs->trans("PreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, -1, 'm'), '%m').')';
		$substitutionarray['__INVOICE_MONTH__'] = $langs->trans("MonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($object->date, '%m').')';
		$substitutionarray['__INVOICE_NEXT_MONTH__'] = $langs->trans("NextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'm'), '%m').')';
		$substitutionarray['__INVOICE_PREVIOUS_MONTH_TEXT__'] = $langs->trans("TextPreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, -1, 'm'), '%B').')';
		$substitutionarray['__INVOICE_MONTH_TEXT__'] = $langs->trans("TextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($object->date, '%B').')';
		$substitutionarray['__INVOICE_NEXT_MONTH_TEXT__'] = $langs->trans("TextNextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'm'), '%B').')';
		$substitutionarray['__INVOICE_PREVIOUS_YEAR__'] = $langs->trans("PreviousYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, -1, 'y'), '%Y').')';
		$substitutionarray['__INVOICE_YEAR__'] = $langs->trans("YearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($object->date, '%Y').')';
		$substitutionarray['__INVOICE_NEXT_YEAR__'] = $langs->trans("NextYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'y'), '%Y').')';
		// Only on template invoices
		$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__'] = $langs->trans("DateNextInvoiceBeforeGen").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'm'), 'dayhour').')';
		$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__'] = $langs->trans("DateNextInvoiceAfterGen").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 2, 'm'), 'dayhour').')';
		$substitutionarray['__INVOICE_COUNTER_CURRENT__'] = $langs->trans("Count");
		$substitutionarray['__INVOICE_COUNTER_MAX__'] = $langs->trans("MaxPeriodNumber");

		$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br>';
		foreach ($substitutionarray as $key => $val) {
			$htmltext .= $key.' = '.$langs->trans($val).'<br>';
		}
		$htmltext .= '</i>';

		// Author
		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->getFullName($langs)."</td></tr>";

		// Payment term
		print "<tr><td>".$langs->trans("PaymentConditions")."</td><td>";
		print $form->getSelectConditionsPaiements(GETPOSTISSET('cond_reglement_id') ? GETPOSTINT('cond_reglement_id') : $object->cond_reglement_id, 'cond_reglement_id', -1, 0, 0, '');
		//$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id');
		print "</td></tr>";

		// Payment mode
		print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
		print img_picto('', 'payment', 'class="pictofixedwidth"');
		print $form->select_types_paiements(GETPOSTISSET('mode_reglement_id') ? GETPOSTINT('mode_reglement_id') : $object->mode_reglement_id, 'mode_reglement_id', '', 0, 1, 0, 0, 1, '', 1);
		//$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', '', 1);
		print "</td></tr>";

		// Bank account
		if ($object->fk_account > 0) {
			print "<tr><td>".$langs->trans('BankAccount')."</td><td>";
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			print "</td></tr>";
		}

		//extrafields
		$draft = new Facture($db);
		$draft->fetch(GETPOSTINT('facid'));

		$extralabels = new ExtraFields($db);
		$extralabels = $extrafields->fetch_name_optionals_label($draft->table_element);
		if ($draft->fetch_optionals() > 0) {
			$object->array_options = array_merge($object->array_options, $draft->array_options);
		}

		print $object->showOptionals($extrafields, 'create', $parameters);

		// Project
		if (isModEnabled('project') && is_object($object->thirdparty) && $object->thirdparty->id > 0) {
			$projectid = GETPOST('projectid') ? GETPOST('projectid') : $object->fk_project;
			$langs->load('projects');
			print '<tr><td>'.$langs->trans('Project').'</td><td>';
			print img_picto('', 'project', 'class="pictofixedwidth"');
			$numprojet = $formproject->select_projects($object->thirdparty->id, $projectid, 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, '');
			print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$object->thirdparty->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$object->thirdparty->id.(!empty($id) ? '&id='.$id : '')).'">'.img_object($langs->trans("AddProject"), 'add').'</a>';
			print '</td></tr>';
		}

		// Model pdf
		print "<tr><td>".$langs->trans('Model')."</td><td>";
		include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
		$list = ModelePDFFactures::liste_modeles($db);
		print img_picto('', 'generic', 'class="pictofixedwidth"');
		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		print $form->selectarray('modelpdf', $list, $conf->global->FACTURE_ADDON_PDF);
		print "</td></tr>";

		// Public note
		print '<tr>';
		print '<td class="tdtop">';
		print $form->textwithpicto($langs->trans('NotePublic'), $htmltext, 1, 'help', '', 0, 2, 'notepublic');
		print '</td>';
		print '<td>';
		$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);

		// Private note
		if (empty($user->socid)) {
			print '<tr>';
			print '<td class="tdtop">';
			print $form->textwithpicto($langs->trans('NotePrivate'), $htmltext, 1, 'help', '', 0, 2, 'noteprivate');
			print '</td>';
			print '<td>';
			$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
			print '</td></tr>';
		}

		print "</table>";

		print dol_get_fiche_end();

		// Autogeneration
		$title = $langs->trans("Recurrence");
		print load_fiche_titre(img_picto('', 'recurring', 'class="pictofixedwidth"').$title, '', '');

		print '<span class="opacitymedium">'.$langs->trans("ToCreateARecurringInvoiceGeneAuto", $langs->transnoentitiesnoconv('Module2300Name')).'</span><br><br>';

		print dol_get_fiche_head(null, '', '', 0);

		print '<table class="border centpercent">';

		// Frequency + unit
		print '<tr><td class="titlefieldcreate">'.$form->textwithpicto($langs->trans("Frequency"), $langs->transnoentitiesnoconv('toolTipFrequency'))."</td><td>";
		print '<input type="text" class="width50" name="frequency" value="'.GETPOST('frequency', 'int').'">&nbsp;';
		print $form->selectarray('unit_frequency', array('d' => $langs->trans('Day'), 'm' => $langs->trans('Month'), 'y' => $langs->trans('Year')), (GETPOST('unit_frequency') ? GETPOST('unit_frequency') : 'm'));
		print "</td></tr>";

		// Date next run
		print "<tr><td>".$langs->trans('NextDateToExecution')."</td><td>";
		$date_next_execution = isset($date_next_execution) ? $date_next_execution : (GETPOSTINT('remonth') ? dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear')) : -1);
		print $form->selectDate($date_next_execution, '', 1, 1, 0, "add", 1, 1);
		print "</td></tr>";

		// Number max of generation
		print "<tr><td>".$langs->trans("MaxPeriodNumber")."</td><td>";
		print '<input type="text" class="width50" name="nb_gen_max" value="'.GETPOSTINT('nb_gen_max').'">';
		print "</td></tr>";

		// Auto validate the invoice
		print "<tr><td>".$langs->trans("StatusOfAutoGeneratedInvoices")."</td><td>";
		$select = array('0' => $langs->trans('BillStatusDraft'), '1' => $langs->trans('BillStatusValidated'));
		print $form->selectarray('auto_validate', $select, GETPOSTINT('auto_validate'));
		print "</td></tr>";

		// Auto generate document
		if (getDolGlobalString('INVOICE_REC_CAN_DISABLE_DOCUMENT_FILE_GENERATION')) {
			print "<tr><td>".$langs->trans("StatusOfGeneratedDocuments")."</td><td>";
			$select = array('0' => $langs->trans('DoNotGenerateDoc'), '1' => $langs->trans('AutoGenerateDoc'));
			print $form->selectarray('generate_pdf', $select, GETPOSTINT('generate_pdf'));
			print "</td></tr>";
		} else {
			print '<input type="hidden" name="generate_pdf" value="1">';
		}

		print "</table>";

		print dol_get_fiche_end();


		$title = $langs->trans("ProductsAndServices");
		if (!isModEnabled('service')) {
			$title = $langs->trans("Products");
		} elseif (!isModEnabled('product')) {
			$title = $langs->trans("Services");
		}

		print load_fiche_titre($title, '', '');

		/*
		 * Invoice lines
		 */
		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow centpercent">';

		// Show object lines
		if (!empty($object->lines)) {
			$object->printOriginLinesList('', $selectedLines);
		}

		print "</table>\n";
		print '<div>';

		print '</td></tr>';

		$flag_price_may_change = getDolGlobalString('INVOICE_REC_PRICE_MAY_CHANGE');
		if (!empty($flag_price_may_change)) {
			print '<tr><td colspan="3" class="left">';
			print '<select name="usenewprice" class="flat">';
			print '<option value="0">'.$langs->trans("AlwaysUseFixedPrice").'</option>';
			print '<option value="1" disabled>'.$langs->trans("AlwaysUseNewPrice").'</option>';
			print '</select>';
			print '</td></tr>';
		}
		print "</table>\n";

		print $form->buttonsSaveCancel("Create");

		print "</form>\n";
	} else {
		dol_print_error(null, "Error, no invoice ".$object->id);
	}
} else {
	/*
	 * View mode
	 */
	if ($object->id > 0) {
		$object->fetch_thirdparty();

		$formconfirm = '';
		// Confirmation of deletion of product line
		if ($action == 'ask_deleteline') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
		}
		// Confirm delete of repeatable invoice
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteRepeatableInvoice'), $langs->trans('ConfirmDeleteRepeatableInvoice'), 'confirm_delete', '', 'no', 1);
		}

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		print $formconfirm;

		$author = new User($db);
		$author->fetch($object->user_author);

		$head = invoice_rec_prepare_head($object);

		print dol_get_fiche_head($head, 'card', $langs->trans("RepeatableInvoice"), -1, 'bill'); // Add a div

		// Recurring invoice content

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/invoicetemplate_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '';
		if ($action != 'editref') {
			$morehtmlref .= $form->editfieldkey($object->ref, 'ref', $object->ref, $object, $user->hasRight('facture', 'creer'), '', '', 0, 2);
		} else {
			$morehtmlref .= $form->editfieldval('', 'ref', $object->ref, $object, $user->hasRight('facture', 'creer'), 'string');
		}

		$morehtmlref .= '<div class="refidno">';
		// Ref customer
		//$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_customer, $object, $user->hasRight('facture', 'creer'), 'string', '', 0, 1);
		//$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_customer, $object, $user->hasRight('facture', 'creer'), 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($user->hasRight('facture', 'creer')) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= ' : '.$proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= ' - '.$proj->title;
					}
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';

		$morehtmlstatus = '';

		dol_banner_tab($object, 'ref', $linkback, 1, 'title', 'none', $morehtmlref, '', 0, '', $morehtmlstatus);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Invoice subtype
		if (getDolGlobalInt('INVOICE_SUBTYPE_ENABLED')) {
			print "<tr><td>".$langs->trans("InvoiceSubtype")."</td><td>";
			if ($object->subtype > 0) {
				print $object->getSubtypeLabel('facture_rec');
			}
			print "</td></tr>";
		}

		// Author
		print '<tr><td class="titlefield">'.$langs->trans("Author").'</td><td>';
		print $author->getNomUrl(-1);
		print "</td></tr>";

		// Amount (excl. tax)
		print '<tr><td>'.$langs->trans("AmountHT").'</td>';
		print '<td>'.price($object->total_ht, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';

		// Amount tax
		print '<tr><td>'.$langs->trans("AmountVAT").'</td><td>'.price($object->total_tva, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';

		// Amount Local Taxes
		if (($mysoc->localtax1_assuj == "1" && $mysoc->useLocalTax(1)) || $object->total_localtax1 != 0) { 	// Localtax1
			print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
			print '<td class="nowrap">'.price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
		}
		if (($mysoc->localtax2_assuj == "1" && $mysoc->useLocalTax(2)) || $object->total_localtax2 != 0) { 	// Localtax2
			print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
			print '<td class=nowrap">'.price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
		}

		print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="3">'.price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';


		// Payment term
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';
		if ($action != 'editconditions' && $user->hasRight('facture', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE) {
			if ($action == 'editconditions') {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->cond_reglement_id, 'cond_reglement_id');
			} else {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->cond_reglement_id, 'none');
			}
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Payment mode
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && $user->hasRight('facture', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmode') {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
		} else {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'none');
		}
		print '</td></tr>';

		// Multicurrency
		if (isModEnabled('multicurrency')) {
			// Multicurrency code
			print '<tr>';
			print '<td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
			print '</td>';
			if ($usercancreate && $action != 'editmulticurrencycode' && $object->suspended == $object::STATUS_SUSPENDED) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			$htmlname = (($usercancreate && $action == 'editmulticurrencycode') ? 'multicurrency_code' : 'none');
			$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, $htmlname);
			print '</td></tr>';

			// Multicurrency rate
			if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
				print '<tr>';
				print '<td>';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
				print '</td>';
				if ($usercancreate && $action != 'editmulticurrencyrate' && $object->suspended == $object::STATUS_SUSPENDED && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
					print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
				}
				print '</tr></table>';
				print '</td><td>';
				if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
					if ($action == 'actualizemulticurrencyrate') {
						list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
					}
					$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, ($usercancreate ? 'multicurrency_tx' : 'none'), $object->multicurrency_code);
				} else {
					$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
					if ($object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
						print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate">'.$langs->trans("ActualizeCurrency").'</a>';
						print '</div>';
					}
				}
				print '</td></tr>';
			}
		}

		// Help of substitution key
		$dateexample = dol_now();
		if (!empty($object->frequency) && !empty($object->date_when)) {
			$dateexample = $object->date_when;
		}

		// Help of substitution key
		$substitutionarray = getCommonSubstitutionArray($langs, 2, null, $object);

		$substitutionarray['__INVOICE_PREVIOUS_MONTH__'] = $langs->trans("PreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'), '%m').')';
		$substitutionarray['__INVOICE_MONTH__'] = $langs->trans("MonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample, '%m').')';
		$substitutionarray['__INVOICE_NEXT_MONTH__'] = $langs->trans("NextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%m').')';
		$substitutionarray['__INVOICE_PREVIOUS_MONTH_TEXT__'] = $langs->trans("TextPreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'), '%B').')';
		$substitutionarray['__INVOICE_MONTH_TEXT__'] = $langs->trans("TextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample, '%B').')';
		$substitutionarray['__INVOICE_NEXT_MONTH_TEXT__'] = $langs->trans("TextNextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%B').')';
		$substitutionarray['__INVOICE_PREVIOUS_YEAR__'] = $langs->trans("PreviousYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'y'), '%Y').')';
		$substitutionarray['__INVOICE_YEAR__'] = $langs->trans("YearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample, '%Y').')';
		$substitutionarray['__INVOICE_NEXT_YEAR__'] = $langs->trans("NextYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'y'), '%Y').')';
		// Only on template invoices
		$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__'] = $langs->trans("DateNextInvoiceBeforeGen").' ('.$langs->trans("Example").': '.dol_print_date(($object->date_when ? $object->date_when : dol_now()), 'dayhour').')';
		$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__'] = $langs->trans("DateNextInvoiceAfterGen").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree(($object->date_when ? $object->date_when : dol_now()), $object->frequency, $object->unit_frequency), 'dayhour').')';
		$substitutionarray['__INVOICE_COUNTER_CURRENT__'] = $object->nb_gen_done;
		$substitutionarray['__INVOICE_COUNTER_MAX__'] = $object->nb_gen_max;

		$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br>';
		foreach ($substitutionarray as $key => $val) {
			$htmltext .= $key.' = '.$langs->trans($val).'<br>';
		}
		$htmltext .= '</i>';

		// Bank Account
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('BankAccount');
		print '<td>';
		if (($action != 'editbankaccount') && $user->hasRight('facture', 'creer') && $object->statut == FactureRec::STATUS_DRAFT) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbankaccount') {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
		} else {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
		}
		print "</td>";
		print '</tr>';

		// Extrafields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';


		// Note public
		print '<tr><td>';
		print $form->editfieldkey($form->textwithpicto($langs->trans('NotePublic'), $htmltext, 1, 'help', '', 0, 2, 'notepublic'), 'note_public', $object->note_public, $object, $user->hasRight('facture', 'creer'));
		print '</td><td class="wordbreak">';
		print $form->editfieldval($langs->trans("NotePublic"), 'note_public', $object->note_public, $object, $user->hasRight('facture', 'creer'), 'textarea:'.ROWS_4.':90%', '', null, null, '', 1);
		print '</td>';
		print '</tr>';

		// Note private
		print '<tr><td>';
		print $form->editfieldkey($form->textwithpicto($langs->trans("NotePrivate"), $htmltext, 1, 'help', '', 0, 2, 'noteprivate'), 'note_private', $object->note_private, $object, $user->hasRight('facture', 'creer'));
		print '</td><td class="wordbreak">';
		print $form->editfieldval($langs->trans("NotePrivate"), 'note_private', $object->note_private, $object, $user->hasRight('facture', 'creer'), 'textarea:'.ROWS_4.':90%', '', null, null, '', 1);
		print '</td>';
		print '</tr>';

		// Model pdf
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('Model');
		print '<td>';
		if (($action != 'editmodelpdf') && $user->hasRight('facture', 'creer') && $object->statut == FactureRec::STATUS_DRAFT) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmodelpdf&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetModel'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmodelpdf') {
			include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
			$list = array();
			$models = ModelePDFFactures::liste_modeles($db);
			foreach ($models as $k => $model) {
				$list[] = str_replace(':', '|', $k).':'.$model;
			}
			$select = 'select;'.implode(',', $list);
			print $form->editfieldval($langs->trans("Model"), 'modelpdf', $object->model_pdf, $object, $user->hasRight('facture', 'creer'), $select);
		} else {
			print $object->model_pdf;
		}
		print "</td>";
		print '</tr>';

		// Other attributes
		$cols = 2;


		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';


		/*
		 * Recurrence
		 */
		$title = $langs->trans("Recurrence");

		print '<table class="border centpercent tableforfield">';

		print '<tr><td colspan="2">'.img_picto('', 'recurring', 'class="pictofixedwidth"').$title.'</td></tr>';

		// if "frequency" is empty or = 0, the recurrence is disabled
		print '<tr><td style="width: 50%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Frequency');
		print '</td>';
		if ($action != 'editfrequency' && $user->hasRight('facture', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editfrequency&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editfrequency') {
			print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'">';
			print '<input type="hidden" name="action" value="setfrequency">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<table class="nobordernopadding">';
			print '<tr><td>';
			print '<input type="text" name="frequency" class="width50 marginrightonly right" value="'.$object->frequency.'">';
			print $form->selectarray('unit_frequency', array('d' => $langs->trans('Day'), 'm' => $langs->trans('Month'), 'y' => $langs->trans('Year')), ($object->unit_frequency ? $object->unit_frequency : 'm'));
			print '</td>';
			print '<td class="left"><input type="submit" class="button button-edit smallpaddingimp" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		} else {
			if ($object->frequency > 0) {
				print $langs->trans('FrequencyPer_'.$object->unit_frequency, $object->frequency);
			} else {
				print '<span class="opacitymedium">'.$langs->trans("NotARecurringInvoiceTemplate").'</span>';
			}
		}
		print '</td></tr>';

		// Date when (next invoice generation)
		print '<tr><td>';
		if ($action == 'date_when' || $object->frequency > 0) {
			print $form->editfieldkey($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->hasRight('facture', 'creer'), 'day');
		} else {
			print $langs->trans("NextDateToExecution");
		}
		print '</td><td>';
		if ($action == 'date_when' || $object->frequency > 0) {
			print $form->editfieldval($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->hasRight('facture', 'creer'), 'day', $object->date_when, null, '', '', 0, 'strikeIfMaxNbGenReached');
		}
		//var_dump(dol_print_date($object->date_when+60, 'dayhour').' - '.dol_print_date($now, 'dayhour'));
		if (!$object->isMaxNbGenReached()) {
			if (!$object->suspended && $action != 'editdate_when' && $object->frequency > 0 && $object->date_when && $object->date_when < $now) {
				print img_warning($langs->trans("Late"));
			}
		} else {
			print img_info($langs->trans("MaxNumberOfGenerationReached"));
		}
		print '</td>';
		print '</tr>';

		// Max period / Rest period
		print '<tr><td>';
		if ($action == 'nb_gen_max' || $object->frequency > 0) {
			print $form->editfieldkey($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max, $object, $user->hasRight('facture', 'creer'));
		} else {
			print $langs->trans("MaxPeriodNumber");
		}
		print '</td><td>';
		if ($action == 'nb_gen_max' || $object->frequency > 0) {
			print $form->editfieldval($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max ? $object->nb_gen_max : '', $object, $user->hasRight('facture', 'creer'));
		} else {
			print '';
		}
		print '</td>';
		print '</tr>';

		// Status of auto generated invoices
		print '<tr><td>';
		if ($action == 'auto_validate' || $object->frequency > 0) {
			print $form->editfieldkey($langs->trans("StatusOfAutoGeneratedInvoices"), 'auto_validate', $object->auto_validate, $object, $user->hasRight('facture', 'creer'));
		} else {
			print $langs->trans("StatusOfAutoGeneratedInvoices");
		}
		print '</td><td>';
		$select = 'select;0:'.$langs->trans('BillStatusDraft').',1:'.$langs->trans('BillStatusValidated');
		if ($action == 'auto_validate' || $object->frequency > 0) {
			print $form->editfieldval($langs->trans("StatusOfAutoGeneratedInvoices"), 'auto_validate', $object->auto_validate, $object, $user->hasRight('facture', 'creer'), $select);
		}
		print '</td>';
		// Auto generate documents
		if (getDolGlobalString('INVOICE_REC_CAN_DISABLE_DOCUMENT_FILE_GENERATION')) {
			print '<tr>';
			print '<td>';
			if ($action == 'generate_pdf' || $object->frequency > 0) {
				print $form->editfieldkey($langs->trans("StatusOfGeneratedDocuments"), 'generate_pdf', $object->generate_pdf, $object, $user->hasRight('facture', 'creer'));
			} else {
				print $langs->trans("StatusOfGeneratedDocuments");
			}
			print '</td>';
			print '<td>';
			$select = 'select;0:'.$langs->trans('DoNotGenerateDoc').',1:'.$langs->trans('AutogenerateDoc');
			if ($action == 'generate_pdf' || $object->frequency > 0) {
				print $form->editfieldval($langs->trans("StatusOfGeneratedDocuments"), 'generate_pdf', $object->generate_pdf, $object, $user->hasRight('facture', 'creer'), $select);
			}
			print '</td>';
			print '</tr>';
		} else {
			print '<input type="hidden" name="generate_pdf" value="1">';
		}

		print '</table>';

		// Frequencry/Recurring section
		if ($object->frequency > 0) {
			print '<br>';

			if (!isModEnabled('cron')) {
				print info_admin($langs->trans("EnableAndSetupModuleCron", $langs->transnoentitiesnoconv("Module2300Name")));
			}

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent tableforfield">';

			// Nb of generation already done
			print '<tr><td style="width: 50%">'.$langs->trans("NbOfGenerationDone").'</td>';
			print '<td>';
			print $object->nb_gen_done ? $object->nb_gen_done : '0';
			print '</td>';
			print '</tr>';

			// Date last
			print '<tr><td>';
			print $langs->trans("DateLastGeneration");
			print '</td><td>';
			print dol_print_date($object->date_last_gen, 'dayhour');
			print '</td>';
			print '</tr>';

			print '</table>';

			print '<br>';
		}

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';


		// Lines
		print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#add' : '#line_'.GETPOSTINT('lineid')).'" method="POST">';
		print '<input type="hidden" name="token" value="' . newToken().'">';
		print '<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">';
		print '<input type="hidden" name="mode" value="">';
		print '<input type="hidden" name="id" value="' . $object->id.'">';
		print '<input type="hidden" name="page_y" value="">';

		if (!empty($conf->use_javascript_ajax) && $object->statut == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow centpercent">';
		// Show object lines
		if (!empty($object->lines)) {
			$canchangeproduct = 1;
			$object->printObjectLines($action, $mysoc, $object->thirdparty, $lineid, 0); // No date selector for template invoice
		}

		// Form to add new line
		if ($object->status == $object::STATUS_DRAFT && $user->hasRight('facture', 'creer') && $action != 'valid' && $action != 'editline') {
			if ($action != 'editline') {
				// Add free products/services

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(0, $mysoc, $object->thirdparty);
				} // No date selector for template invoice
			}
		}

		print "</table>\n";
		print '</div>';

		print "</form>\n";

		print dol_get_fiche_end();


		/*
		 * Action bar
		 */
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$params = array(
				'attr' => array(
					'class' => 'classfortooltip',
				),
			);
			if (empty($object->suspended)) {
				if ($user->hasRight('facture', 'creer')) {
					if (!empty($object->frequency) && $object->nb_gen_max > 0 && ($object->nb_gen_done >= $object->nb_gen_max)) {
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("MaxGenerationReached")) . '">' . $langs->trans("CreateBill") . '</a></div>';
					} else {
						if (empty($object->frequency) || $object->date_when <= $nowlasthour) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture/card.php?action=create&socid=' . $object->thirdparty->id . '&fac_rec=' . $object->id . '">' . $langs->trans("CreateBill") . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("DateIsNotEnough")) . '">' . $langs->trans("CreateBill") . '</a></div>';
						}
					}
				} else {
					print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">' . $langs->trans("CreateBill") . '</a></div>';
				}
			}

			if ($user->hasRight('facture', 'creer')) {
				if (empty($object->suspended)) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=disable&id='.$object->id.'&token='.newToken().'">'.$langs->trans("Disable").'</a></div>';
				} else {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=enable&id='.$object->id.'&token='.newToken().'">'.$langs->trans("Enable").'</a></div>';
				}
			}

			// Delete
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token=' . newToken(), 'delete', $user->hasRight('facture', 'supprimer'));
		}
		print '</div>';



		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre


		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('invoice'));

		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div>';
		print '<div class="fichehalfright">';

		$MAXEVENT = 10;

		//$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_agenda.php', 1).'?id='.$object->id);
		$morehtmlcenter = '';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div>';
		print '</div>';
	} else {
		print $langs->trans("NoRecordFound");
	}
}

// End of page
llxFooter();
$db->close();
