<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2022	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005		Marc Barilley / Ocebo		<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2010-2023	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2010-2022	Philippe Grand				<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2023	Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2012		Cedric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2014	Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2016		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2018-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2020		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2022		Gauthier VERDOL				<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		Lenin Rivas					<lenin.rivas777@gmail.com>
 * Copyright (C) 2023		William Mead				<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 * \file 		htdocs/comm/propal/card.php
 * \ingroup 	propale
 * \brief 		Page of commercial proposals card and list
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

if (isModEnabled('variants')) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'sendings', 'other'));
if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}
if (isModEnabled('margin')) {
	$langs->load('margins');
}

$error = 0;

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOSTINT('originid');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$lineid = GETPOSTINT('lineid');
$contactid = GETPOSTINT('contactid');
$projectid = GETPOSTINT('projectid');
$rank = (GETPOSTINT('rank') > 0) ? GETPOSTINT('rank') : -1;

// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

$object = new Propal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$ret = $object->fetch_thirdparty();
		if ($ret > 0 && isset($object->fk_project)) {
			$ret = $object->fetchProject();
		}
	}
	if ($ret <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('propalcard', 'globalcard'));

$usercanread = $user->hasRight("propal", "lire");
$usercancreate = $user->hasRight("propal", "creer");
$usercandelete = $user->hasRight("propal", "supprimer");

$usercanclose = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('propal', 'propal_advance', 'close')));
$usercanvalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('propal', 'propal_advance', 'validate')));
$usercansend = (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('propal', 'propal_advance', 'send')));

$usermustrespectpricemin = ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('produit', 'ignore_price_min_advance')) || !getDolGlobalString('MAIN_USE_ADVANCED_PERMS'));
$usercancreateorder = $user->hasRight('commande', 'creer');
$usercancreateinvoice = $user->hasRight('facture', 'creer');
$usercancreatecontract = $user->hasRight('contrat', 'creer');
$usercancreateintervention = $user->hasRight('ficheinter', 'creer');
$usercancreatepurchaseorder = ($user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer'));

$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}
restrictedArea($user, 'propal', $object->id);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/comm/propal/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/comm/propal/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be 'include', not 'include_once'

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate) {
		if (!($socid > 0)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('IdThirdParty')), null, 'errors');
		} else {
			if ($object->id > 0) {
				if (getDolGlobalString('PROPAL_CLONE_DATE_DELIVERY')) {
					//Get difference between old and new delivery date and change lines according to difference
					$date_delivery = dol_mktime(
						12,
						0,
						0,
						GETPOSTINT('date_deliverymonth'),
						GETPOSTINT('date_deliveryday'),
						GETPOSTINT('date_deliveryyear')
					);
					$date_delivery_old = $object->delivery_date;
					if (!empty($date_delivery_old) && !empty($date_delivery)) {
						//Attempt to get the date without possible hour rounding errors
						$old_date_delivery = dol_mktime(
							12,
							0,
							0,
							dol_print_date($date_delivery_old, '%m'),
							dol_print_date($date_delivery_old, '%d'),
							dol_print_date($date_delivery_old, '%Y')
						);
						//Calculate the difference and apply if necessary
						$difference = $date_delivery - $old_date_delivery;
						if ($difference != 0) {
							$object->delivery_date = $date_delivery;
							foreach ($object->lines as $line) {
								if (isset($line->date_start)) {
									$line->date_start +=  $difference;
								}
								if (isset($line->date_end)) {
									$line->date_end += $difference;
								}
							}
						}
					}
				}

				$result = $object->createFromClone($user, $socid, (GETPOSTISSET('entity') ? GETPOSTINT('entity') : null), (GETPOSTINT('update_prices') ? true : false), (GETPOSTINT('update_desc') ? true : false));
				if ($result > 0) {
					$warningMsgLineList = array();
					// check all product lines are to sell otherwise add a warning message for each product line is not to sell
					foreach ($object->lines as $line) {
						if (!is_object($line->product)) {
							$line->fetch_product();
						}
						if (is_object($line->product) && $line->product->id > 0) {
							if (empty($line->product->status)) {
								$warningMsgLineList[$line->id] = $langs->trans('WarningLineProductNotToSell', $line->product->ref);
							}
						}
					}
					if (!empty($warningMsgLineList)) {
						setEventMessages('', $warningMsgLineList, 'warnings');
					}

					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit();
				} else {
					if (count($object->errors) > 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
					$action = '';
				}
			}
		}
	} elseif ($action == 'confirm_cancel' && $confirm == 'yes' && $usercanclose) {
		// Cancel proposal
		$result = $object->setCancel($user);
		if ($result > 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete) {
		// Delete proposal
		$result = $object->delete($user);
		if ($result > 0) {
			header('Location: '.DOL_URL_ROOT.'/comm/propal/list.php?restore_lastsearch_values=1');
			exit();
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		// Remove line
		$result = $object->deleteLine($lineid);
		// reorder lines
		if ($result > 0) {
			$object->line_order(true);
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
		}

		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			// Define output language
			$outputlangs = $langs;
			if (getDolGlobalInt('MAIN_MULTILANGS')) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			if ($ret > 0) {
				$object->fetch_thirdparty();
			}
			$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit();
	} elseif ($action == 'confirm_validate' && $confirm == 'yes' && $usercanvalidate) {
		// Validation
		$idwarehouse = GETPOSTINT('idwarehouse');
		$result = $object->valid($user);
		if ($result > 0 && getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE')) {
			$result = $object->closeProposal($user, $object::STATUS_SIGNED);
		}
		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records
				if ($ret > 0) {
					$object->fetch_thirdparty();
				}

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			$langs->load("errors");
			if (count($object->errors) > 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans($object->error), null, 'errors');
			}
		}
	} elseif ($action == 'setdate' && $usercancreate) {
		$datep = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));

		if (empty($datep)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		}

		if (!$error) {
			$result = $object->set_date($user, $datep);
			if ($result > 0 && !empty($object->duree_validite) && !empty($object->fin_validite)) {
				$datev = $datep + ($object->duree_validite * 24 * 3600);
				$result = $object->set_echeance($user, $datev, 1);
			}
			if ($result < 0) {
				dol_print_error($db, $object->error);
			} elseif (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records
				if ($ret > 0) {
					$object->fetch_thirdparty();
				}

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	} elseif ($action == 'setecheance' && $usercancreate) {
		$result = $object->set_echeance($user, dol_mktime(12, 0, 0, GETPOSTINT('echmonth'), GETPOSTINT('echday'), GETPOSTINT('echyear')));
		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records
				if ($ret > 0) {
					$object->fetch_thirdparty();
				}

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setdate_livraison' && $usercancreate) {
		$result = $object->setDeliveryDate($user, dol_mktime(12, 0, 0, GETPOSTINT('date_livraisonmonth'), GETPOSTINT('date_livraisonday'), GETPOSTINT('date_livraisonyear')));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setref_client' && $usercancreate) {
		// Positionne ref client
		$result = $object->set_ref_client($user, GETPOST('ref_client'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'set_incoterms' && isModEnabled('incoterm') && $usercancreate) {
		// Set incoterm
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
	} elseif ($action == 'add' && $usercancreate) {
		// Create proposal
		$object->socid = $socid;
		$object->fetch_thirdparty();

		$datep = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
		$date_delivery = dol_mktime(12, 0, 0, GETPOST('date_livraisonmonth'), GETPOST('date_livraisonday'), GETPOST('date_livraisonyear'));
		$duration = GETPOSTINT('duree_validite');

		if (empty($datep)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePropal")), null, 'errors');
			$action = 'create';
			$error++;
		}
		if (empty($duration)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ValidityDuration")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if ($socid < 1) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');

			$action = 'create';
			$error++;
		}

		if (!$error) {
			$db->begin();

			// If we select proposal to clone during creation (when option PROPAL_CLONE_ON_CREATE_PAGE is on)
			if (GETPOST('createmode') == 'copy' && GETPOST('copie_propal')) {
				if ($object->fetch(GETPOSTINT('copie_propal')) > 0) {
					$object->ref = GETPOST('ref');
					$object->datep = $datep;
					$object->date = $datep;
					$object->delivery_date = $date_delivery;
					$object->availability_id = GETPOSTINT('availability_id');
					$object->demand_reason_id = GETPOSTINT('demand_reason_id');
					$object->fk_delivery_address = GETPOSTINT('fk_address');
					$object->shipping_method_id = GETPOSTINT('shipping_method_id');
					$object->warehouse_id = GETPOSTINT('warehouse_id');
					$object->duree_validite = $duration;
					$object->cond_reglement_id = GETPOSTINT('cond_reglement_id');
					$object->deposit_percent = GETPOSTFLOAT('cond_reglement_id_deposit_percent');
					$object->mode_reglement_id = GETPOSTINT('mode_reglement_id');
					$object->fk_account = GETPOSTINT('fk_account');
					$object->socid = GETPOSTINT('socid');
					$object->contact_id = GETPOSTINT('contactid');
					$object->fk_project = GETPOSTINT('projectid');
					$object->model_pdf = GETPOST('model', 'alphanohtml');
					$object->author = $user->id; // deprecated
					$object->user_author_id = $user->id;
					$object->note_private = GETPOST('note_private', 'restricthtml');
					$object->note_public = GETPOST('note_public', 'restricthtml');
					$object->statut = Propal::STATUS_DRAFT;
					$object->status = Propal::STATUS_DRAFT;
					$object->fk_incoterms = GETPOSTINT('incoterm_id');
					$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				} else {
					setEventMessages($langs->trans("ErrorFailedToCopyProposal", GETPOST('copie_propal')), null, 'errors');
				}
			} else {
				$object->ref = GETPOST('ref');
				$object->ref_client = GETPOST('ref_client');
				$object->datep = $datep;
				$object->date = $datep;
				$object->delivery_date = $date_delivery;
				$object->availability_id = GETPOSTINT('availability_id');
				$object->demand_reason_id = GETPOSTINT('demand_reason_id');
				$object->fk_delivery_address = GETPOSTINT('fk_address');
				$object->shipping_method_id = GETPOSTINT('shipping_method_id');
				$object->warehouse_id = GETPOSTINT('warehouse_id');
				$object->duree_validite = price2num(GETPOST('duree_validite', 'alpha'));
				$object->cond_reglement_id = GETPOSTINT('cond_reglement_id');
				$object->deposit_percent = GETPOSTFLOAT('cond_reglement_id_deposit_percent');
				$object->mode_reglement_id = GETPOSTINT('mode_reglement_id');
				$object->fk_account = GETPOSTINT('fk_account');
				$object->contact_id = GETPOSTINT('contactid');
				$object->fk_project = GETPOSTINT('projectid');
				$object->model_pdf = GETPOST('model');
				$object->author = $user->id; // deprecated
				$object->note_private = GETPOST('note_private', 'restricthtml');
				$object->note_public = GETPOST('note_public', 'restricthtml');
				$object->fk_incoterms = GETPOSTINT('incoterm_id');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

				$object->origin = GETPOST('origin');
				$object->origin_id = GETPOSTINT('originid');

				// Multicurrency
				if (isModEnabled("multicurrency")) {
					$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				}

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
					$action = 'create';
				}
			}

			if (!$error) {
				if ($origin && $originid) {
					// Parse element/subelement (ex: project_task)
					$element = $subelement = $origin;
					$regs = array();
					if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
						$element = $regs[1];
						$subelement = $regs[2];
					}

					// For compatibility
					if ($element == 'order') {
						$element = $subelement = 'commande';
					}
					if ($element == 'propal') {
						$element = 'comm/propal';
						$subelement = 'propal';
					}
					if ($element == 'contract') {
						$element = $subelement = 'contrat';
					}
					if ($element == 'inter') {
						$element = $subelement = 'ficheinter';
					}
					if ($element == 'shipping') {
						$element = $subelement = 'expedition';
					}

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects[$object->origin] = $object->origin_id;
					if (GETPOSTISARRAY('other_linked_objects')) {
						$object->linked_objects = array_merge($object->linked_objects, GETPOST('other_linked_objects', 'array:int'));
					}

					$id = $object->create($user);
					if ($id > 0) {
						dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

						$classname = ucfirst($subelement);
						$srcobject = new $classname($db);

						dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
						$result = $srcobject->fetch($object->origin_id);

						if ($result > 0) {
							$lines = $srcobject->lines;
							if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
								$srcobject->fetch_lines();
								$lines = $srcobject->lines;
							}

							$fk_parent_line = 0;
							$num = count($lines);
							for ($i = 0; $i < $num; $i++) {
								$label = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : '');

								// Positive line
								$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

								// Date start
								$date_start = false;
								if ($lines[$i]->date_debut_prevue) {
									$date_start = $lines[$i]->date_debut_prevue;
								}
								if ($lines[$i]->date_debut_reel) {
									$date_start = $lines[$i]->date_debut_reel;
								}
								if ($lines[$i]->date_start) {
									$date_start = $lines[$i]->date_start;
								}

								// Date end
								$date_end = false;
								if ($lines[$i]->date_fin_prevue) {
									$date_end = $lines[$i]->date_fin_prevue;
								}
								if ($lines[$i]->date_fin_reel) {
									$date_end = $lines[$i]->date_fin_reel;
								}
								if ($lines[$i]->date_end) {
									$date_end = $lines[$i]->date_end;
								}

								// Reset fk_parent_line for no child products and special product
								if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
									$fk_parent_line = 0;
								}

								// Extrafields
								if (method_exists($lines[$i], 'fetch_optionals')) {
									$lines[$i]->fetch_optionals();
									$array_options = $lines[$i]->array_options;
								}

								$tva_tx = $lines[$i]->tva_tx;
								if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) {
									$tva_tx .= ' ('.$lines[$i]->vat_src_code.')';
								}

								$result = $object->addline($desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent, 'HT', 0, $lines[$i]->info_bits, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $date_start, $date_end, $array_options, $lines[$i]->fk_unit);

								if ($result > 0) {
									$lineid = $result;
								} else {
									$lineid = 0;
									$error++;
									break;
								}

								// Defined the new fk_parent_line
								if ($result > 0 && $lines[$i]->product_type == 9) {
									$fk_parent_line = $result;
								}
							}

							// Hooks
							$parameters = array('objFrom' => $srcobject);
							$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been
							// modified by hook
							if ($reshook < 0) {
								setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
								$error++;
							}
						} else {
							setEventMessages($srcobject->error, $srcobject->errors, 'errors');
							$error++;
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				} else {
					// Standard creation
					$id = $object->create($user);
				}

				if ($id > 0) {
					// Insert default contacts if defined
					if (GETPOST('contactid') > 0) {
						$result = $object->add_contact(GETPOST('contactid'), 'CUSTOMER', 'external');
						if ($result < 0) {
							$error++;
							setEventMessages($langs->trans("ErrorFailedToAddContact"), null, 'errors');
						}
					}

					if (getDolGlobalString('PROPOSAL_AUTO_ADD_AUTHOR_AS_CONTACT')) {
						$result = $object->add_contact($user->id, 'SALESREPFOLL', 'internal');
						if ($result < 0) {
							$error++;
							setEventMessages($langs->trans("ErrorFailedToAddUserAsContact"), null, 'errors');
						}
					}

					if (!$error) {
						$db->commit();

						// Define output language
						if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
							$outputlangs = $langs;
							$newlang = '';
							if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
								$newlang = GETPOST('lang_id', 'aZ09');
							}
							if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
								$newlang = $object->thirdparty->default_lang;
							}
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}
							$model = $object->model_pdf;

							$ret = $object->fetch($id); // Reload to get new records
							$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
							if ($result < 0) {
								dol_print_error($db, $result);
							}
						}

						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
						exit();
					} else {
						$db->rollback();
						$action = 'create';
					}
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$db->rollback();
					$action = 'create';
				}
			}
		}
	} elseif ($action == 'classifybilled' && $usercanclose) {
		// Classify billed
		$db->begin();

		$result = $object->classifyBilled($user, 0, '');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} elseif ($action == 'confirm_closeas' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		// Close proposal
		if (!(GETPOSTINT('statut') > 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CloseAs")), null, 'errors');
			$action = 'closeas';
		} elseif (GETPOSTINT('statut') == $object::STATUS_SIGNED || GETPOSTINT('statut') == $object::STATUS_NOTSIGNED) {
			$locationTarget = '';
			// prevent browser refresh from closing proposal several times
			if ($object->statut == $object::STATUS_VALIDATED || (getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE') && $object->statut == $object::STATUS_DRAFT)) {
				$db->begin();

				$oldstatus = $object->status;

				$result = $object->closeProposal($user, GETPOSTINT('statut'), GETPOST('note_private'));

				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				} else {
					// Needed if object linked modified by trigger (because linked objects can't be fetched two times : linkedObjectsFullLoaded)
					$locationTarget = DOL_URL_ROOT . '/comm/propal/card.php?id=' . $object->id;
				}

				$deposit = null;

				$deposit_percent_from_payment_terms = getDictionaryValue('c_payment_term', 'deposit_percent', $object->cond_reglement_id);

				if (
					!$error && GETPOSTINT('statut') == $object::STATUS_SIGNED && GETPOST('generate_deposit') == 'on'
					&& !empty($deposit_percent_from_payment_terms) && isModEnabled('invoice') && $user->hasRight('facture', 'creer')
				) {
					require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

					$date = dol_mktime(0, 0, 0, GETPOSTINT('datefmonth'), GETPOSTINT('datefday'), GETPOSTINT('datefyear'));
					$forceFields = array();

					if (GETPOSTISSET('date_pointoftax')) {
						$forceFields['date_pointoftax'] = dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'));
					}

					$deposit = Facture::createDepositFromOrigin($object, $date, GETPOSTINT('cond_reglement_id'), $user, 0, GETPOSTINT('validate_generated_deposit') == 'on', $forceFields);

					if ($deposit) {
						setEventMessage('DepositGenerated');
						$locationTarget = DOL_URL_ROOT . '/compta/facture/card.php?id=' . $deposit->id;
					} else {
						$error++;
						setEventMessages("Failed to create down payment - ".$object->error, $object->errors, 'errors');
					}
				}

				if (!$error) {
					$db->commit();

					if ($deposit && !getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
						$ret = $deposit->fetch($deposit->id); // Reload to get new records
						$outputlangs = $langs;

						if (getDolGlobalInt('MAIN_MULTILANGS')) {
							$outputlangs = new Translate('', $conf);
							$outputlangs->setDefaultLang($deposit->thirdparty->default_lang);
							$outputlangs->load('products');
						}

						$result = $deposit->generateDocument($deposit->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

						if ($result < 0) {
							setEventMessages($deposit->error, $deposit->errors, 'errors');
						}
					}

					if ($locationTarget) {
						header('Location: ' . $locationTarget);
						exit;
					}
				} else {
					$object->status = $oldstatus;
					$object->statut = $oldstatus;

					$db->rollback();
					$action = '';
				}
			}
		}
	} elseif ($action == 'confirm_reopen' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		// Reopen proposal
		// prevent browser refresh from reopening proposal several times
		if ($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED || $object->statut == Propal::STATUS_CANCELED) {
			$db->begin();

			$newstatus = (getDolGlobalInt('PROPAL_SKIP_ACCEPT_REFUSE') ? Propal::STATUS_DRAFT : Propal::STATUS_VALIDATED);
			$result = $object->reopen($user, $newstatus);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			} else {
				$object->statut = $newstatus;
				$object->status = $newstatus;
			}

			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
			}
		}
	} elseif ($action == 'import_lines_from_object'
		&& $user->hasRight('propal', 'creer')
		&& $object->statut == Propal::STATUS_DRAFT
	) {
		// add lines from objectlinked
		$fromElement = GETPOST('fromelement');
		$fromElementid = GETPOST('fromelementid');
		$importLines = GETPOST('line_checkbox');

		if (!empty($importLines) && is_array($importLines) && !empty($fromElement) && ctype_alpha($fromElement) && !empty($fromElementid)) {
			if ($fromElement == 'commande') {
				dol_include_once('/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'OrderLine';
			} elseif ($fromElement == 'propal') {
				dol_include_once('/comm/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'PropaleLigne';
			} elseif ($fromElement == 'facture') {
				dol_include_once('/compta/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'FactureLigne';
			}
			$nextRang = count($object->lines) + 1;
			$importCount = 0;
			$error = 0;
			foreach ($importLines as $lineId) {
				$lineId = intval($lineId);
				$originLine = new $lineClassName($db);
				if (intval($fromElementid) > 0 && $originLine->fetch($lineId) > 0) {
					$originLine->fetch_optionals();
					$desc = $originLine->desc;
					$pu_ht = $originLine->subprice;
					$qty = $originLine->qty;
					$txtva = $originLine->tva_tx;
					$txlocaltax1 = $originLine->localtax1_tx;
					$txlocaltax2 = $originLine->localtax2_tx;
					$fk_product = $originLine->fk_product;
					$remise_percent = $originLine->remise_percent;
					$date_start = $originLine->date_start;
					$date_end = $originLine->date_end;
					$fk_code_ventilation = 0;
					$info_bits = $originLine->info_bits;
					$fk_remise_except = $originLine->fk_remise_except;
					$price_base_type = 'HT';
					$pu_ttc = 0;
					$type = $originLine->product_type;
					$rang = $nextRang++;
					$special_code = $originLine->special_code;
					$origin = $originLine->element;
					$origin_id = $originLine->id;
					$fk_parent_line = 0;
					$fk_fournprice = $originLine->fk_fournprice;
					$pa_ht = $originLine->pa_ht;
					$label = $originLine->label;
					$array_options = $originLine->array_options;
					$situation_percent = 100;
					$fk_prev_id = '';
					$fk_unit = $originLine->fk_unit;
					$pu_ht_devise = $originLine->multicurrency_subprice;

					$res = $object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $price_base_type, $pu_ttc, $info_bits, $type, $rang, $special_code, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $date_start, $date_end, $array_options, $fk_unit, $origin, $origin_id, $pu_ht_devise, $fk_remise_except);

					if ($res > 0) {
						$importCount++;
					} else {
						$error++;
					}
				} else {
					$error++;
				}
			}

			if ($error) {
				setEventMessages($langs->trans('ErrorsOnXLines', $error), null, 'errors');
			}
		}
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$actiontypecode = 'AC_OTH_AUTO';
	$triggersendname = 'PROPAL_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_PROPOSAL_TO';
	$trackid = 'pro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	// Go back to draft
	if ($action == 'modif' && $usercancreate) {
		$object->setDraft($user);

		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			// Define output language
			$outputlangs = $langs;
			if (getDolGlobalInt('MAIN_MULTILANGS')) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			if ($ret > 0) {
				$object->fetch_thirdparty();
			}
			$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	} elseif ($action == "setabsolutediscount" && $usercancreate) {
		if (GETPOSTINT("remise_id")) {
			if ($object->id > 0) {
				$result = $object->insert_discount(GETPOSTINT("remise_id"));
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'aZ09') && (GETPOST('alldate_start', 'alpha') || GETPOST('alldate_end', 'alpha')) && $usercancreate) {
		// Define date start and date end for all line
		$alldate_start = dol_mktime(GETPOST('alldate_starthour'), GETPOST('alldate_startmin'), 0, GETPOST('alldate_startmonth'), GETPOST('alldate_startday'), GETPOST('alldate_startyear'));
		$alldate_end = dol_mktime(GETPOST('alldate_endhour'), GETPOST('alldate_endmin'), 0, GETPOST('alldate_endmonth'), GETPOST('alldate_endday'), GETPOST('alldate_endyear'));
		foreach ($object->lines as $line) {
			if ($line->product_type == 1) { // only service line
				$result = $object->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $alldate_start, $alldate_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
			}
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'alpha') && GETPOST('vatforalllines', 'alpha') !== '' && $usercancreate) {
		// Define a vat_rate for all lines
		$vat_rate = (GETPOST('vatforalllines') ? GETPOST('vatforalllines') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		foreach ($object->lines as $line) {
			$result = $object->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $vat_rate, $localtax1_rate, $localtax2_rate, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'alpha') && GETPOST('remiseforalllines', 'alpha') !== '' && $usercancreate) {
		// Define a discount for all lines
		$remise_percent = (GETPOST('remiseforalllines') ? GETPOST('remiseforalllines') : 0);
		$remise_percent = str_replace('*', '', $remise_percent);
		foreach ($object->lines as $line) {
			$tvatx = $line->tva_tx;
			if (!empty($line->vat_src_code)) {
				$tvatx .= ' ('.$line->vat_src_code.')';
			}
			$result = $object->updateline($line->id, $line->subprice, $line->qty, $remise_percent, $tvatx, $line->localtax1_tx, $line->localtax2_tx, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
		}
	} elseif ($action == 'addline' && GETPOST('submitforallmargins', 'alpha') && GETPOST('marginforalllines') !== '' && $usercancreate) {
		// Define margin
		$margin_rate = (GETPOST('marginforalllines') ? GETPOST('marginforalllines') : 0);
		foreach ($object->lines as &$line) {
			$subprice = price2num($line->pa_ht * (1 + $margin_rate / 100), 'MU');
			$prod = new Product($db);
			$prod->fetch($line->fk_product);
			if ($prod->price_min > $subprice) {
				$price_subprice  = price($subprice, 0, $outlangs, 1, -1, -1, 'auto');
				$price_price_min = price($prod->price_min, 0, $outlangs, 1, -1, -1, 'auto');
				setEventMessages($prod->ref.' - '.$prod->label.' ('.$price_subprice.' < '.$price_price_min.' '.strtolower($langs->trans("MinPrice")).')'."\n", null, 'warnings');
			}
			// Manage $line->subprice and $line->multicurrency_subprice
			$multicurrency_subprice = (float) $subprice * $line->multicurrency_subprice / $line->subprice;
			// Update DB
			$result = $object->updateline($line->id, $subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_rate, $line->localtax2_rate, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $multicurrency_subprice);
			// Update $object with new margin info
			$line->price = $subprice;
			$line->marge_tx = $margin_rate;
			$line->marque_tx = $margin_rate * $line->pa_ht / (float) $subprice;
			$line->total_ht = $line->qty * (float) $subprice;
			$line->total_tva = $line->tva_tx * $line->qty * (float) $subprice;
			$line->total_ttc = (1 + $line->tva_tx) * $line->qty * (float) $subprice;
			// Manage $line->subprice and $line->multicurrency_subprice
			$line->multicurrency_total_ht = $line->qty * (float) $subprice * $line->multicurrency_subprice / $line->subprice;
			$line->multicurrency_total_tva = $line->tva_tx * $line->qty * (float) $subprice * $line->multicurrency_subprice / $line->subprice;
			$line->multicurrency_total_ttc = (1 + $line->tva_tx) * $line->qty * (float) $subprice * $line->multicurrency_subprice / $line->subprice;
			// Used previous $line->subprice and $line->multicurrency_subprice above, now they can be set to their new values
			$line->subprice = $subprice;
			$line->multicurrency_subprice = $multicurrency_subprice;
		}
	} elseif ($action == 'addline' && !GETPOST('submitforalllines', 'alpha') && !GETPOST('submitforallmargins', 'alpha') && $usercancreate) {		// Add line
		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');

		$price_ht = '';
		$price_ht_devise = '';
		$price_ttc = '';
		$price_ttc_devise = '';

		// TODO Implement  if (getDolGlobalInt('MAIN_UNIT_PRICE_WITH_TAX_IS_FOR_ALL_TAXES'))

		if (GETPOST('price_ht') !== '') {
			$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
		}
		if (GETPOST('multicurrency_price_ht') !== '') {
			$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'), 'CU', 2);
		}
		if (GETPOST('price_ttc') !== '') {
			$price_ttc = price2num(GETPOST('price_ttc'), 'MU', 2);
		}
		if (GETPOST('multicurrency_price_ttc') !== '') {
			$price_ttc_devise = price2num(GETPOST('multicurrency_price_ttc'), 'CU', 2);
		}

		$prod_entry_mode = GETPOST('prod_entry_mode', 'aZ09');
		if ($prod_entry_mode == 'free') {
			$idprod = 0;
		} else {
			$idprod = GETPOSTINT('idprod');

			if (getDolGlobalString('MAIN_DISABLE_FREE_LINES') && $idprod <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")), null, 'errors');
				$error++;
			}
		}

		$tva_tx = GETPOST('tva_tx', 'alpha');

		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS', 2);
		$remise_percent = (GETPOSTISSET('remise_percent'.$predef) ? price2num(GETPOST('remise_percent'.$predef, 'alpha'), '', 2) : 0);
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
				unset($_POST["options_".$key]);
			}
		}

		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && GETPOST('type') < 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
			$error++;
		}

		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && $price_ht === '' && $price_ht_devise === '' && $price_ttc === '' && $price_ttc_devise === '') { 	// Unit price can be 0 but not ''. Also price can be negative for proposal.
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && empty($product_desc)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")), null, 'errors');
			$error++;
		}

		if (!$error && isModEnabled('variants') && $prod_entry_mode != 'free') {
			if ($combinations = GETPOST('combinations', 'array')) {
				//Check if there is a product with the given combination
				$prodcomb = new ProductCombination($db);

				if ($res = $prodcomb->fetchByProductCombination2ValuePairs($idprod, $combinations)) {
					$idprod = $res->fk_product_child;
				} else {
					setEventMessages($langs->trans('ErrorProductCombinationNotFound'), null, 'errors');
					$error++;
				}
			}
		}

		$propal_qty_requirement = (getDolGlobalString('PROPAL_ENABLE_NEGATIVE_QTY') ? ($qty >= 0 || $qty <= 0) : $qty >= 0);
		if (!$error && $propal_qty_requirement && (!empty($product_desc) || (!empty($idprod) && $idprod > 0))) {
			$pu_ht = 0;
			$pu_ttc = 0;
			$pu_ht_devise = 0;
			$pu_ttc_devise = 0;
			$price_min = 0;
			$price_min_ttc = 0;
			$tva_npr = 0;
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

			$db->begin();

			// $tva_tx can be 'x.x (XXX)'

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Replaces $fk_unit with the product unit
			if (!empty($idprod) && $idprod > 0) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Update if prices fields are defined
				/*$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
				$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}*/

				// Price unique per product
				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_min = $prod->price_min;
				$price_min_ttc = $prod->price_min_ttc;
				$price_base_type = $prod->price_base_type;

				// If price per segment
				if (getDolGlobalString('PRODUIT_MULTIPRICES') && $object->thirdparty->price_level) {
					$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
					$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
					$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
					$price_min_ttc = $prod->multiprices_min_ttc[$object->thirdparty->price_level];
					$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
					if (getDolGlobalString('PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL')) {  // using this option is a bug. kept for backward compatibility
						if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) {
							$tva_tx = $prod->multiprices_tva_tx[$object->thirdparty->price_level];
						}
						if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) {
							$tva_npr = $prod->multiprices_recuperableonly[$object->thirdparty->price_level];
						}
					}
				} elseif (getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
					// If price per customer
					require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

					$prodcustprice = new ProductCustomerPrice($db);

					$filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

					$result = $prodcustprice->fetchAll('', '', 0, 0, $filter);
					if ($result) {
						// If there is some prices specific to the customer
						if (count($prodcustprice->lines) > 0) {
							$pu_ht = price($prodcustprice->lines[0]->price);
							$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
							$price_min =  price($prodcustprice->lines[0]->price_min);
							$price_min_ttc =  price($prodcustprice->lines[0]->price_min_ttc);
							$price_base_type = $prodcustprice->lines[0]->price_base_type;
							/*$tva_tx = ($prodcustprice->lines[0]->default_vat_code ? $prodcustprice->lines[0]->tva_tx.' ('.$prodcustprice->lines[0]->default_vat_code.' )' : $prodcustprice->lines[0]->tva_tx);
							if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) {
								$tva_tx .= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
							}
							$tva_npr = $prodcustprice->lines[0]->recuperableonly;
							if (empty($tva_tx)) {
								$tva_npr = 0;
							}*/
						}
					}
				} elseif (getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY')) {
					// If price per quantity
					if ($prod->prices_by_qty[0]) {	// yes, this product has some prices per quantity
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOSTINT('pbq');

						// Search price into product_price_by_qty from $prod->id
						foreach ($prod->prices_by_qty_list[0] as $priceforthequantityarray) {
							if ($priceforthequantityarray['rowid'] != $pqp) {
								continue;
							}
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT') {
								$pu_ht = $priceforthequantityarray['unitprice'];
							} else {
								$pu_ttc = $priceforthequantityarray['unitprice'];
							}
							// Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
							break;
						}
					}
				} elseif (getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) {
					// If price per quantity and customer
					if ($prod->prices_by_qty[$object->thirdparty->price_level]) { // yes, this product has some prices per quantity
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOSTINT('pbq');

						// Search price into product_price_by_qty from $prod->id
						foreach ($prod->prices_by_qty_list[$object->thirdparty->price_level] as $priceforthequantityarray) {
							if ($priceforthequantityarray['rowid'] != $pqp) {
								continue;
							}
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT') {
								$pu_ht = $priceforthequantityarray['unitprice'];
							} else {
								$pu_ttc = $priceforthequantityarray['unitprice'];
							}
							// Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
							break;
						}
					}
				}

				$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', (string) $prod->tva_tx));

				// Set unit price to use
				if (!empty($price_ht) || (string) $price_ht === '0') {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num((float) $pu_ht * (1 + ((float) $tmpvat / 100)), 'MU');
				} elseif (!empty($price_ht_devise) || (string) $price_ht_devise === '0') {
					$pu_ht_devise = price2num($price_ht_devise, 'MU');
					$pu_ht = '';
					$pu_ttc = '';
				} elseif (!empty($price_ttc) || (string) $price_ttc === '0') {
					$pu_ttc = price2num($price_ttc, 'MU');
					$pu_ht = price2num((float) $pu_ttc / (1 + ((float) $tmpvat / 100)), 'MU');
				} elseif ($tmpvat != $tmpprodvat) {
					// Is this still used ?
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

					$desc = (!empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? $prod->multilangs[$outputlangs->defaultlang]["description"] : $prod->description;
				} else {
					$desc = $prod->description;
				}

				//If text set in desc is the same as product description (as now it's preloaded) we add it only one time
				if ($product_desc == $desc && getDolGlobalString('PRODUIT_AUTOFILL_DESC')) {
					$product_desc = '';
				}

				if (!empty($product_desc) && getDolGlobalString('MAIN_NO_CONCAT_DESCRIPTION')) {
					$desc = $product_desc;
				} else {
					$desc = dol_concatdesc($desc, $product_desc, '', getDolGlobalString('MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION'));
				}

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
				$pu_ttc = price2num($price_ttc, 'MU');
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}
				$tva_tx = str_replace('*', '', $tva_tx);
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');
				$fk_unit = GETPOST('units', 'alpha');
				$pu_ht_devise = price2num($price_ht_devise, 'MU');
				$pu_ttc_devise = price2num($price_ttc_devise, 'MU');

				if ($pu_ttc && !$pu_ht) {
					$price_base_type = 'TTC';
				}
			}

			$info_bits = 0;
			if ($tva_npr) {
				$info_bits |= 0x01;
			}

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $tva_npr);

			// Margin
			$fournprice = price2num(GETPOST('fournprice'.$predef) ? GETPOST('fournprice'.$predef) : '');
			$buyingprice = price2num(GETPOST('buying_price'.$predef) != '' ? GETPOST('buying_price'.$predef) : ''); // If buying_price is '0', we must keep this value

			$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

			// Prepare a price equivalent for minimum price check
			$pu_equivalent = $pu_ht;
			$pu_equivalent_ttc = $pu_ttc;
			$currency_tx = $object->multicurrency_tx;

			// Check if we have a foreign currency
			// If so, we update the pu_equiv as the equivalent price in base currency
			if ($pu_ht == '' && $pu_ht_devise != '' && $currency_tx != '') {
				$pu_equivalent = (float) $pu_ht_devise * (float) $currency_tx;
			}
			if ($pu_ttc == '' && $pu_ttc_devise != '' && $currency_tx != '') {
				$pu_equivalent_ttc = (float) $pu_ttc_devise * (float) $currency_tx;
			}

			// TODO $pu_equivalent or $pu_equivalent_ttc must be calculated from the one not null taking into account all taxes
			/*
			 if ($pu_equivalent) {
			 $tmp = calcul_price_total(1, $pu_equivalent, 0, $tva_tx, -1, -1, 0, 'HT', $info_bits, $type);
			 $pu_equivalent_ttc = ...
			 } else {
			 $tmp = calcul_price_total(1, $pu_equivalent_ttc, 0, $tva_tx, -1, -1, 0, 'TTC', $info_bits, $type);
			 $pu_equivalent_ht = ...
			 }
			 */

			//var_dump(price2num($price_min)); var_dump(price2num($pu_ht)); var_dump($remise_percent);
			//var_dump(price2num($price_min_ttc)); var_dump(price2num($pu_ttc)); var_dump($remise_percent);exit;

			// Check price is not lower than minimum
			if ($usermustrespectpricemin) {
				if ($pu_equivalent && $price_min && (((float) price2num($pu_equivalent) * (1 - $remise_percent / 100)) < price2num($price_min)) && $price_base_type == 'HT') {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
				} elseif ($pu_equivalent_ttc && $price_min_ttc && (((float) price2num($pu_equivalent_ttc) * (1 - $remise_percent / 100)) < price2num($price_min_ttc)) && $price_base_type == 'TTC') {
					$mesg = $langs->trans("CantBeLessThanMinPriceInclTax", price(price2num($price_min_ttc, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
				}
			}

			if (!$error) {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $price_base_type, $pu_ttc, $info_bits, $type, min($rank, count($object->lines) + 1), 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $date_start, $date_end, $array_options, $fk_unit, '', 0, $pu_ht_devise);

				if ($result > 0) {
					$db->commit();

					if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
						// Define output language
						$outputlangs = $langs;
						if (getDolGlobalInt('MAIN_MULTILANGS')) {
							$outputlangs = new Translate("", $conf);
							$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
							$outputlangs->setDefaultLang($newlang);
						}
						$ret = $object->fetch($id); // Reload to get new records
						if ($ret > 0) {
							$object->fetch_thirdparty();
						}
						$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}

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
				} else {
					$db->rollback();

					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && GETPOST('save')) {
		// Update a line within proposal

		// Clean parameters
		$description = dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml'));

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', GETPOST('tva_tx'))) {
			$info_bits |= 0x01;
		}

		// Define vat_rate
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		$pu_ht = price2num(GETPOST('price_ht'), '', 2);
		$pu_ttc = price2num(GETPOST('price_ttc'), '', 2);

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we must keep this value

		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), '', 2);
		$pu_ttc_devise = price2num(GETPOST('multicurrency_subprice_ttc'), '', 2);

		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

		$remise_percent = price2num(GETPOST('remise_percent'), '', 2);
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}

		// Prepare a price equivalent for minimum price check
		$pu_equivalent = $pu_ht;
		$pu_equivalent_ttc = $pu_ttc;

		$currency_tx = $object->multicurrency_tx;

		// Check if we have a foreign currency
		// If so, we update the pu_equiv as the equivalent price in base currency
		if ($pu_ht == '' && $pu_ht_devise != '' && $currency_tx != '') {
			$pu_equivalent = (float) $pu_ht_devise * (float) $currency_tx;
		}
		if ($pu_ttc == '' && $pu_ttc_devise != '' && $currency_tx != '') {
			$pu_equivalent_ttc = (float) $pu_ttc_devise * (float) $currency_tx;
		}

		// TODO $pu_equivalent or $pu_equivalent_ttc must be calculated from the one not null taking into account all taxes
		/*
		 if ($pu_equivalent) {
		 $tmp = calcul_price_total(1, $pu_equivalent, 0, $vat_rate, -1, -1, 0, 'HT', $info_bits, $type);
		 $pu_equivalent_ttc = ...
		 } else {
		 $tmp = calcul_price_total(1, $pu_equivalent_ttc, 0, $vat_rate, -1, -1, 0, 'TTC', $info_bits, $type);
		 $pu_equivalent_ht = ...
		 }
		 */

		// Extrafields
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}

		// Define special_code for special lines
		$special_code = GETPOSTINT('special_code');
		if (!GETPOST('qty')) {
			$special_code = 3;
		}

		// Check minimum price
		$productid = GETPOSTINT('productid');
		if (!empty($productid)) {
			$product = new Product($db);
			$res = $product->fetch($productid);

			$type = $product->type;
			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			$price_min = $product->price_min;
			if (getDolGlobalString('PRODUIT_MULTIPRICES') && !empty($object->thirdparty->price_level)) {
				$price_min = $product->multiprices_min[$object->thirdparty->price_level];
			}
			$price_min_ttc = $product->price_min_ttc;
			if (getDolGlobalString('PRODUIT_MULTIPRICES') && !empty($object->thirdparty->price_level)) {
				$price_min_ttc = $product->multiprices_min_ttc[$object->thirdparty->price_level];
			}

			//var_dump(price2num($price_min)); var_dump(price2num($pu_ht)); var_dump($remise_percent);
			//var_dump(price2num($price_min_ttc)); var_dump(price2num($pu_ttc)); var_dump($remise_percent);exit;

			// Check price is not lower than minimum
			if ($usermustrespectpricemin) {
				if ($pu_equivalent && $price_min && (((float) price2num($pu_equivalent) * (1 - (float) $remise_percent / 100)) < (float) price2num($price_min)) && $price_base_type == 'HT') {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
					$action = 'editline';
				} elseif ($pu_equivalent_ttc && $price_min_ttc && (((float) price2num($pu_equivalent_ttc) * (1 - (float) $remise_percent / 100)) < (float) price2num($price_min_ttc)) && $price_base_type == 'TTC') {
					$mesg = $langs->trans("CantBeLessThanMinPriceInclTax", price(price2num($price_min_ttc, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
					$action = 'editline';
				}
			}
		} else {
			$type = GETPOST('type');
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');

			// Check parameters
			if (GETPOST('type') < 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
			}
		}

		if (!$error) {
			$db->begin();

			if (!$user->hasRight('margins', 'creer')) {
				foreach ($object->lines as &$line) {
					if ($line->id == GETPOSTINT('lineid')) {
						$fournprice = $line->fk_fournprice;
						$buyingprice = $line->pa_ht;
						break;
					}
				}
			}

			$qty = price2num(GETPOST('qty', 'alpha'), 'MS');

			$pu = $pu_ht;
			$price_base_type = 'HT';
			if (empty($pu) && !empty($pu_ttc)) {
				$pu = $pu_ttc;
				$price_base_type = 'TTC';
			}

			$result = $object->updateline(GETPOSTINT('lineid'), $pu, $qty, $remise_percent, $vat_rate, $localtax1_rate, $localtax2_rate, $description, $price_base_type, $info_bits, $special_code, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $type, $date_start, $date_end, $array_options, GETPOST("units"), $pu_ht_devise);

			if ($result >= 0) {
				$db->commit();

				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
					// Define output language
					$outputlangs = $langs;
					if (getDolGlobalInt('MAIN_MULTILANGS')) {
						$outputlangs = new Translate("", $conf);
						$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					$ret = $object->fetch($id); // Reload to get new records
					if ($ret > 0) {
						$object->fetch_thirdparty();
					}
					$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}

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
			} else {
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && GETPOST('cancel', 'alpha')) {
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); //  To re-display card in edit mode
		exit();
	} elseif ($action == 'classin' && $usercancreate) {
		// Set project
		$object->setProject(GETPOSTINT('projectid'));
	} elseif ($action == 'setavailability' && $usercancreate) {
		// Delivery time
		$result = $object->set_availability($user, GETPOSTINT('availability_id'));
	} elseif ($action == 'setdemandreason' && $usercancreate) {
		// Origin of the commercial proposal
		$result = $object->set_demand_reason($user, GETPOSTINT('demand_reason_id'));
	} elseif ($action == 'setconditions' && $usercancreate) {
		// Terms of payment
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'), GETPOSTINT('cond_reglement_id_deposit_percent'));
		//} elseif ($action == 'setremisepercent' && $usercancreate) {
		//	$result = $object->set_remise_percent($user, price2num(GETPOST('remise_percent'), '', 2));
		//} elseif ($action == 'setremiseabsolue' && $usercancreate) {
		//	$result = $object->set_remise_absolue($user, price2num(GETPOST('remise_absolue'), 'MU', 2));
	} elseif ($action == 'setmode' && $usercancreate) {
		// Payment choice
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		// Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOSTINT('calculation_mode'));
	} elseif ($action == 'setbankaccount' && $usercancreate) {
		// bank account
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
	} elseif ($action == 'setshippingmethod' && $usercancreate) {
		// shipping method
		$result = $object->setShippingMethod(GETPOSTINT('shipping_method_id'));
	} elseif ($action == 'setwarehouse' && $usercancreate) {
		// warehouse
		$result = $object->setWarehouse(GETPOSTINT('warehouse_id'));
	} elseif ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object, 2);
		$attribute_name = GETPOST('attribute', 'restricthtml');

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute_name);
		if ($ret < 0) {
			$error++;
		}
		if (!$error) {
			$result = $object->updateExtraField($attribute_name, 'PROPAL_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}
		if ($error) {
			$action = 'edit_extras';
		}
	}

	if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && $usercancreate) {
		if ($action == 'addcontact') {
			if ($object->id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
				$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
			}

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit();
			} else {
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} elseif ($action == 'swapstatut') {
			// Toggle the status of a contact
			if ($object->fetch($id) > 0) {
				$result = $object->swapContactStatus(GETPOSTINT('ligne'));
			} else {
				dol_print_error($db);
			}
		} elseif ($action == 'deletecontact') {
			// Delete a contact
			$object->fetch($id);
			$result = $object->delete_contact($lineid);

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit();
			} else {
				dol_print_error($db);
			}
		}
	}

	// Actions to build doc
	$upload_dir = !empty($conf->propal->multidir_output[$object->entity]) ? $conf->propal->multidir_output[$object->entity] : $conf->propal->dir_output;
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$formmargin = new FormMargin($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewPropal");
}
$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos|DE:Modul_Angebote';

llxHeader('', $title, $help_url);

$now = dol_now();

// Add new proposal
if ($action == 'create') {
	$currency_code = $conf->currency;

	print load_fiche_titre($langs->trans("NewProp"), '', 'propal');

	$soc = new Societe($db);
	if ($socid > 0) {
		$res = $soc->fetch($socid);
	}

	$currency_code = $conf->currency;

	$cond_reglement_id = GETPOSTINT('cond_reglement_id');
	$deposit_percent = GETPOST('cond_reglement_id_deposit_percent', 'alpha');
	$mode_reglement_id = GETPOSTINT('mode_reglement_id');
	$fk_account = GETPOSTINT('fk_account');
	$datepropal = (empty($datepropal) ? (!getDolGlobalString('MAIN_AUTOFILL_DATE_PROPOSAL') ? -1 : '') : $datepropal);

	// Load objectsrc
	if (!empty($origin) && !empty($originid)) {
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		$regs = array();
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs[1];
			$subelement = $regs[2];
		}

		if ($element == 'project') {
			$projectid = $originid;
		} else {
			// For compatibility
			if ($element == 'order' || $element == 'commande') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
				$objectsrc->fetch_lines();
			}
			$objectsrc->fetch_thirdparty();

			$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : 0);
			$ref_client = (!empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

			$soc = $objectsrc->thirdparty;

			$cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0));
			$mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
			$warehouse_id       = (!empty($objectsrc->warehouse_id) ? $objectsrc->warehouse_id : (!empty($soc->warehouse_id) ? $soc->warehouse_id : 0));

			// Replicate extrafields
			$objectsrc->fetch_optionals();
			$object->array_options = $objectsrc->array_options;

			if (isModEnabled("multicurrency")) {
				if (!empty($objectsrc->multicurrency_code)) {
					$currency_code = $objectsrc->multicurrency_code;
				}
				if (getDolGlobalString('MULTICURRENCY_USE_ORIGIN_TX') && !empty($objectsrc->multicurrency_tx)) {
					$currency_tx = $objectsrc->multicurrency_tx;
				}
			}
		}
	} else {
		$cond_reglement_id  = empty($soc->cond_reglement_id) ? $cond_reglement_id : $soc->cond_reglement_id;
		$deposit_percent    = empty($soc->deposit_percent) ? $deposit_percent : $soc->deposit_percent;
		$mode_reglement_id  = empty($soc->mode_reglement_id) ? $mode_reglement_id : $soc->mode_reglement_id;
		$fk_account         = empty($soc->fk_account) ? $fk_account : $soc->fk_account;
		$shipping_method_id = $soc->shipping_method_id;
		$warehouse_id       = $soc->fk_warehouse;
		$remise_percent     = $soc->remise_percent;

		if (isModEnabled("multicurrency") && !empty($soc->multicurrency_code)) {
			$currency_code = $soc->multicurrency_code;
		}
	}

	// If form was posted (but error returned), we must reuse the value posted in priority (standard Dolibarr behaviour)
	if (!GETPOST('changecompany')) {
		if (GETPOSTISSET('cond_reglement_id')) {
			$cond_reglement_id = GETPOSTINT('cond_reglement_id');
		}
		if (GETPOSTISSET('deposit_percent')) {
			$deposit_percent = price2num(GETPOST('deposit_percent', 'alpha'));
		}
		if (GETPOSTISSET('mode_reglement_id')) {
			$mode_reglement_id = GETPOSTINT('mode_reglement_id');
		}
		if (GETPOSTISSET('cond_reglement_id')) {
			$fk_account = GETPOSTINT('fk_account');
		}
	}

	// Warehouse default if null
	if ($soc->fk_warehouse > 0) {
		$warehouse_id = $soc->fk_warehouse;
	}
	if (isModEnabled('stock') && empty($warehouse_id) && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER')) {
		if (empty($object->warehouse_id) && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE')) {
			$warehouse_id = getDolGlobalString('MAIN_DEFAULT_WAREHOUSE');
		}
		if (empty($object->warehouse_id) && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE_USER') && !empty($user->warehouse_id)) {
			$warehouse_id = $user->fk_warehouse;
		}
	}

	print '<form name="addprop" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="changecompany" value="0">';	// will be set to 1 by javascript so we know post is done after a company change
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($origin != 'project' && $originid) {
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid" value="'.$originid.'">';
	} elseif ($origin == 'project' && !empty($projectid)) {
		print '<input type="hidden" name="projectid" value="'.$projectid.'">';
	}

	print dol_get_fiche_head();

	// Call Hook tabContentCreateProposal
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentCreateProposal', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<table class="border centpercent">';

		// Reference
		print '<tr class="field_ref"><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td class="valuefieldcreate">'.$langs->trans("Draft").'</td></tr>';

		// Ref customer
		print '<tr class="field_ref_client"><td class="titlefieldcreate">'.$langs->trans('RefCustomer').'</td><td class="valuefieldcreate">';
		print '<input type="text" name="ref_client" value="'.(!empty($ref_client) ? $ref_client : GETPOST('ref_client')).'"></td>';
		print '</tr>';

		// Third party
		print '<tr class="field_socid">';
		print '<td class="titlefieldcreate fieldrequired">'.$langs->trans('Customer').'</td>';
		$shipping_method_id = 0;
		if ($socid > 0) {
			print '<td class="valuefieldcreate">';
			print $soc->getNomUrl(1, 'customer');
			print '<input type="hidden" name="socid" value="'.$soc->id.'">';
			print '</td>';
			if (getDolGlobalString('SOCIETE_ASK_FOR_SHIPPING_METHOD') && !empty($soc->shipping_method_id)) {
				$shipping_method_id = $soc->shipping_method_id;
			}
			//$warehouse_id       = $soc->warehouse_id;
		} else {
			print '<td class="valuefieldcreate">';
			$filter = '((s.client:IN:1,2,3) AND (s.status:=:1))';
			print img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company('', 'socid', $filter, 'SelectThirdParty', 1, 0, null, 0, 'minwidth300 maxwidth500 widthcentpercentminusxx');
			// reload page to retrieve customer information
			if (!getDolGlobalString('RELOAD_PAGE_ON_CUSTOMER_CHANGE_DISABLED')) {
				print '<script>
				$(document).ready(function() {
					$("#socid").change(function() {
						console.log("We have changed the company - Reload page");
						var socid = $(this).val();
						// reload page
						$("input[name=action]").val("create");
						$("input[name=changecompany]").val("1");
						$("form[name=addprop]").submit();
					});
				});
				</script>';
			}
			print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
			print '</td>';
		}
		print '</tr>'."\n";

		if ($socid > 0) {
			// Contacts (ask contact only if thirdparty already defined).
			print '<tr class="field_contactid"><td class="titlefieldcreate">'.$langs->trans("DefaultContact").'</td><td class="valuefieldcreate">';
			print img_picto('', 'contact', 'class="pictofixedwidth"');
			//print $form->selectcontacts($soc->id, $contactid, 'contactid', 1, '', '', 0, 'minwidth300 widthcentpercentminusx');
			print $form->select_contact($soc->id, $contactid, 'contactid', 1, '', '', 1, 'maxwidth300 widthcentpercentminusx', true);
			print '</td></tr>';

			// Third party discounts info line
			print '<tr class="field_discount_info"><td class="titlefieldcreate">'.$langs->trans('Discounts').'</td><td class="valuefieldcreate">';

			$absolute_discount = $soc->getAvailableDiscounts();

			$thirdparty = $soc;
			$discount_type = 0;
			$backtopage = $_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.urlencode((string) (GETPOST('origin'))).'&originid='.urlencode((string) (GETPOSTINT('originid')));
			include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
			print '</td></tr>';
		}

		$newdatepropal = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'), 'tzserver');
		// Date
		print '<tr class="field_addprop"><td class="titlefieldcreate fieldrequired">'.$langs->trans('DatePropal').'</td><td class="valuefieldcreate">';
		print img_picto('', 'action', 'class="pictofixedwidth"');
		print $form->selectDate($newdatepropal ? $newdatepropal : $datepropal, '', 0, 0, 0, "addprop", 1, 1);
		print '</td></tr>';

		// Validaty duration
		print '<tr class="field_duree_validitee"><td class="titlefieldcreate fieldrequired">'.$langs->trans("ValidityDuration").'</td><td class="valuefieldcreate">'.img_picto('', 'clock', 'class="pictofixedwidth"').'<input name="duree_validite" class="width50" value="'.(GETPOSTISSET('duree_validite') ? GETPOST('duree_validite', 'alphanohtml') : $conf->global->PROPALE_VALIDITY_DURATION).'"> '.$langs->trans("days").'</td></tr>';

		// Terms of payment
		print '<tr class="field_cond_reglement_id"><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
		print img_picto('', 'payment', 'class="pictofixedwidth"');
		// at last resort we take the payment term id which may be filled by default values set (if not getpostisset)
		print $form->getSelectConditionsPaiements($cond_reglement_id, 'cond_reglement_id', 1, 1, 0, '', $deposit_percent);
		print '</td></tr>';

		// Mode of payment
		print '<tr class="field_mode_reglement_id"><td class="titlefieldcreate">'.$langs->trans('PaymentMode').'</td><td class="valuefieldcreate">';
		print img_picto('', 'bank', 'class="pictofixedwidth"');
		print $form->select_types_paiements($mode_reglement_id, 'mode_reglement_id', 'CRDT', 0, 1, 0, 0, 1, 'maxwidth200 widthcentpercentminusx', 1);
		print '</td></tr>';

		// Bank Account
		if (getDolGlobalString('BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL') && isModEnabled("bank")) {
			print '<tr class="field_fk_account"><td class="titlefieldcreate">'.$langs->trans('BankAccount').'</td><td class="valuefieldcreate">';
			print img_picto('', 'bank_account', 'class="pictofixedwidth"').$form->select_comptes($fk_account, 'fk_account', 0, '', 1, '', 0, 'maxwidth200 widthcentpercentminusx', 1);
			print '</td></tr>';
		}

		// Source / Channel - What trigger creation
		print '<tr class="field_demand_reason_id"><td class="titlefieldcreate">'.$langs->trans('Source').'</td><td class="valuefieldcreate">';
		print img_picto('', 'question', 'class="pictofixedwidth"');
		$form->selectInputReason((GETPOSTISSET('demand_reason_id') ? GETPOSTINT('demand_reason_id') : ''), 'demand_reason_id', "SRC_PROP", 1, 'maxwidth200 widthcentpercentminusx');
		print '</td></tr>';

		// Shipping Method
		if (isModEnabled("shipping")) {
			if (getDolGlobalString('SOCIETE_ASK_FOR_SHIPPING_METHOD') && !empty($soc->shipping_method_id)) {
				$shipping_method_id = $soc->shipping_method_id;
			}
			print '<tr class="field_shipping_method_id"><td class="titlefieldcreate">'.$langs->trans('SendingMethod').'</td><td class="valuefieldcreate">';
			print img_picto('', 'dolly', 'class="pictofixedwidth"');
			$form->selectShippingMethod((GETPOSTISSET('shipping_method_id') ? GETPOSTINT('shipping_method_id') : $shipping_method_id), 'shipping_method_id', '', 1, '', 0, 'maxwidth200 widthcentpercentminusx');
			print '</td></tr>';
		}

		// Warehouse
		if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROPAL')) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			print '<tr class="field_warehouse_id"><td class="titlefieldcreate">'.$langs->trans('Warehouse').'</td><td class="valuefieldcreate">';
			print img_picto('', 'stock', 'class="pictofixedwidth"').$formproduct->selectWarehouses($warehouse_id, 'warehouse_id', '', 1, 0, 0, '', 0, 0, array(), 'maxwidth500 widthcentpercentminusxx');
			print '</td></tr>';
		}

		// Delivery delay
		print '<tr class="field_availability_id"><td class="titlefieldcreate">'.$langs->trans('AvailabilityPeriod');
		if (isModEnabled('order')) {
			print ' ('.$langs->trans('AfterOrder').')';
		}
		print '</td><td class="valuefieldcreate">';
		print img_picto('', 'clock', 'class="pictofixedwidth"');
		$form->selectAvailabilityDelay((GETPOSTISSET('availability_id') ? GETPOSTINT('availability_id') : ''), 'availability_id', '', 1, 'maxwidth200 widthcentpercentminusx');
		print '</td></tr>';

		// Delivery date (or manufacturing)
		print '<tr class="field_date_livraison"><td class="titlefieldcreate">'.$langs->trans("DeliveryDate").'</td>';
		print '<td class="valuefieldcreate">';
		print img_picto('', 'action', 'class="pictofixedwidth"');
		if (is_numeric(getDolGlobalString('DATE_LIVRAISON_WEEK_DELAY'))) {	// If value set to 0 or a num, not empty
			$tmpdte = time() + (7 * getDolGlobalInt('DATE_LIVRAISON_WEEK_DELAY') * 24 * 60 * 60);
			$syear = date("Y", $tmpdte);
			$smonth = date("m", $tmpdte);
			$sday = date("d", $tmpdte);
			print $form->selectDate($syear."-".$smonth."-".$sday, 'date_livraison', 0, 0, 0, "addprop");
		} else {
			print $form->selectDate(-1, 'date_livraison', 0, 0, 0, "addprop", 1, 1);
		}
		print '</td></tr>';

		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			print '<tr class="field_projectid">';
			print '<td class="titlefieldcreate">'.$langs->trans("Project").'</td><td class="valuefieldcreate">';
			print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
			print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
			print '</td>';
			print '</tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr class="field_incoterm_id">';
			print '<td class="titlefieldcreate"><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $soc->label_incoterms, 1).'</label></td>';
			print '<td  class="valuefieldcreate maxwidthonsmartphone">';
			print img_picto('', 'incoterm', 'class="pictofixedwidth"');
			print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : ''), (!empty($soc->location_incoterms) ? $soc->location_incoterms : ''));
			print '</td></tr>';
		}

		// Template to use by default
		print '<tr class="field_model">';
		print '<td class="titlefieldcreate">'.$langs->trans("DefaultModel").'</td>';
		print '<td class="valuefieldcreate">';
		print img_picto('', 'pdf', 'class="pictofixedwidth"');
		$liste = ModelePDFPropales::liste_modeles($db);
		$preselected = (getDolGlobalString('PROPALE_ADDON_PDF_ODT_DEFAULT') ? $conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT : getDolGlobalString("PROPALE_ADDON_PDF"));
		print $form->selectarray('model', $liste, $preselected, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200 widthcentpercentminusx', 1);
		print "</td></tr>";

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			print '<tr class="field_currency">';
			print '<td class="titlefieldcreate">'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
			print '<td class="valuefieldcreate maxwidthonsmartphone">';
			print img_picto('', 'currency', 'class="pictofixedwidth"').$form->selectMultiCurrency(((GETPOSTISSET('multicurrency_code') && !GETPOST('changecompany')) ? GETPOST('multicurrency_code') : $currency_code), 'multicurrency_code', 0);
			print '</td></tr>';
		}

		// Public note
		print '<tr class="field_note_public">';
		print '<td class="titlefieldcreate tdtop">'.$langs->trans('NotePublic').'</td>';
		print '<td class="valuefieldcreate">';
		$note_public = $object->getDefaultCreateValueFor('note_public', (!empty($objectsrc) ? $objectsrc->note_public : (getDolGlobalString('PROPALE_ADDON_NOTE_PUBLIC_DEFAULT') ? $conf->global->PROPALE_ADDON_NOTE_PUBLIC_DEFAULT : null)), 'restricthtml');
		$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);

		// Private note
		if (empty($user->socid)) {
			print '<tr class="field_note_private">';
			print '<td class="titlefieldcreate tdtop">'.$langs->trans('NotePrivate').'</td>';
			print '<td class="valuefieldcreate">';
			$note_private = $object->getDefaultCreateValueFor('note_private', ((!empty($origin) && !empty($originid) && is_object($objectsrc)) ? $objectsrc->note_private : null));
			$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
			print '</td></tr>';
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		// Lines from source
		if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
			// TODO for compatibility
			if ($origin == 'contrat') {
				// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
				//$objectsrc->remise_absolue = $remise_absolue;	// deprecated
				//$objectsrc->remise_percent = $remise_percent;
				$objectsrc->update_price(1, 'auto', 1);
			}

			print "\n<!-- ".$classname." info -->";
			print "\n";
			print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
			print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
			print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
			print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
			print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

			$newclassname = $classname;
			if ($newclassname == 'Propal') {
				$newclassname = 'CommercialProposal';
			} elseif ($newclassname == 'Commande') {
				$newclassname = 'Order';
			} elseif ($newclassname == 'Expedition') {
				$newclassname = 'Sending';
			} elseif ($newclassname == 'Fichinter') {
				$newclassname = 'Intervention';
			}

			print '<tr><td>'.$langs->trans($newclassname).'</td><td>'.$objectsrc->getNomUrl(1).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($objectsrc->total_ht, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($objectsrc->total_tva, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
			if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) { 		// Localtax1
				print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax1, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
			}

			if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) { 		// Localtax2
				print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax2, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
			}
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($objectsrc->total_ttc, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";

			if (isModEnabled("multicurrency")) {
				print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td>'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
				print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td>'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
				print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td>'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
			}
		}

		print "</table>\n";


		/*
		 * Combobox for copy function
		 */

		if (!getDolGlobalString('PROPAL_CLONE_ON_CREATE_PAGE')) {
			print '<input type="hidden" name="createmode" value="empty">';
		}

		if (getDolGlobalString('PROPAL_CLONE_ON_CREATE_PAGE')) {
			print '<br><table>';

			// For backward compatibility
			print '<tr>';
			print '<td><input type="radio" name="createmode" value="copy"></td>';
			print '<td>'.$langs->trans("CopyPropalFrom").' </td>';
			print '<td>';
			$liste_propal = array();
			$liste_propal [0] = '';

			$sql = "SELECT p.rowid as id, p.ref, s.nom";
			$sql .= " FROM ".MAIN_DB_PREFIX."propal p";
			$sql .= ", ".MAIN_DB_PREFIX."societe s";
			$sql .= " WHERE s.rowid = p.fk_soc";
			$sql .= " AND p.entity IN (".getEntity('propal').")";
			$sql .= " AND p.fk_statut <> 0";
			$sql .= " ORDER BY Id";

			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$row = $db->fetch_row($resql);
					$propalRefAndSocName = $row[1]." - ".$row[2];
					$liste_propal[$row[0]] = $propalRefAndSocName;
					$i++;
				}
				print $form->selectarray("copie_propal", $liste_propal, 0);
			} else {
				dol_print_error($db);
			}
			print '</td></tr>';

			print '<tr><td class="tdtop"><input type="radio" name="createmode" value="empty" checked></td>';
			print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyPropal").'</td></tr>';
			print '</table>';
		}
	}

	print dol_get_fiche_end();

	$langs->load("bills");

	print $form->buttonsSaveCancel("CreateDraft");

	print "</form>";


	// Show origin lines
	if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		$objectsrc->printOriginLinesList();

		print '</table>';
		print '</div>';
	}
} elseif ($object->id > 0) {
	/*
	 * Show object in view mode
	 */
	$object->fetch_thirdparty();
	if ($object->thirdparty) {
		$soc = $object->thirdparty;
	} else {
		$soc = new Societe($db);
	}

	$head = propal_prepare_head($object);
	print dol_get_fiche_head($head, 'comm', $langs->trans('Proposal'), -1, 'propal', 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$filter = '(s.client:IN:1,2,3)';
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOSTINT('socid'), 'socid', $filter, '', 0, 0, null, 0, 'maxwidth300')),
			array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans('PuttingPricesUpToDate'), 'value' => 0),
			array('type' => 'checkbox', 'name' => 'update_desc', 'label' => $langs->trans('PuttingDescUpToDate'), 'value' => 0),
		);
		if (getDolGlobalString('PROPAL_CLONE_DATE_DELIVERY') && !empty($object->delivery_date)) {
			$formquestion[] = array('type' => 'date', 'name' => 'date_delivery', 'label' => $langs->trans("DeliveryDate"), 'value' => $object->delivery_date);
		}
		// Incomplete payment. We ask if reason = discount or other
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePropal', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	if ($action == 'closeas') {
		//Form to close proposal (signed or not)
		$formquestion = array();
		if (!getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE')) {
			$formquestion[] = array('type' => 'select', 'name' => 'statut', 'label' => '<span class="fieldrequired">'.$langs->trans("CloseAs").'</span>', 'values' => array($object::STATUS_SIGNED => $object->LibStatut($object::STATUS_SIGNED), $object::STATUS_NOTSIGNED => $object->LibStatut($object::STATUS_NOTSIGNED)));
		}
		$formquestion[] = array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"), 'value' => '');				// Field to complete private note (not replace)

		if (getDolGlobalInt('PROPOSAL_SUGGEST_DOWN_PAYMENT_INVOICE_CREATION')) {
			// This is a hidden option:
			// Suggestion to create invoice during proposal signature is not enabled by default.
			// Such choice should be managed by the workflow module and trigger. This option generates conflicts with some setup.
			// It may also break step of creating an order when invoicing must be done from orders and not from proposal
			$deposit_percent_from_payment_terms = getDictionaryValue('c_payment_term', 'deposit_percent', $object->cond_reglement_id);

			if (!empty($deposit_percent_from_payment_terms) && isModEnabled('invoice') && $user->hasRight('facture', 'creer')) {
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

				$object->fetchObjectLinked();

				$eligibleForDepositGeneration = true;

				if (array_key_exists('facture', $object->linkedObjects)) {
					foreach ($object->linkedObjects['facture'] as $invoice) {
						if ($invoice->type == Facture::TYPE_DEPOSIT) {
							$eligibleForDepositGeneration = false;
							break;
						}
					}
				}

				if ($eligibleForDepositGeneration && array_key_exists('commande', $object->linkedObjects)) {
					foreach ($object->linkedObjects['commande'] as $order) {
						$order->fetchObjectLinked();

						if (array_key_exists('facture', $order->linkedObjects)) {
							foreach ($order->linkedObjects['facture'] as $invoice) {
								if ($invoice->type == Facture::TYPE_DEPOSIT) {
									$eligibleForDepositGeneration = false;
									break 2;
								}
							}
						}
					}
				}


				if ($eligibleForDepositGeneration) {
					$formquestion[] = array(
						'type' => 'checkbox',
						'tdclass' => 'showonlyifsigned',
						'name' => 'generate_deposit',
						'morecss' => 'margintoponly marginbottomonly',
						'label' => $form->textwithpicto($langs->trans('GenerateDeposit', $object->deposit_percent), $langs->trans('DepositGenerationPermittedByThePaymentTermsSelected'))
					);

					$formquestion[] = array(
						'type' => 'date',
						'tdclass' => 'fieldrequired showonlyifgeneratedeposit',
						'name' => 'datef',
						'label' => $langs->trans('DateInvoice'),
						'value' => dol_now(),
						'datenow' => true
					);

					if (getDolGlobalString('INVOICE_POINTOFTAX_DATE')) {
						$formquestion[] = array(
							'type' => 'date',
							'tdclass' => 'fieldrequired showonlyifgeneratedeposit',
							'name' => 'date_pointoftax',
							'label' => $langs->trans('DatePointOfTax'),
							'value' => dol_now(),
							'datenow' => true
						);
					}

					$paymentTermsSelect = $form->getSelectConditionsPaiements(0, 'cond_reglement_id', -1, 0, 1, 'minwidth200');

					$formquestion[] = array(
						'type' => 'other',
						'tdclass' => 'fieldrequired showonlyifgeneratedeposit',
						'name' => 'cond_reglement_id',
						'label' => $langs->trans('PaymentTerm'),
						'value' => $paymentTermsSelect
					);

					$formquestion[] = array(
						'type' => 'checkbox',
						'tdclass' => 'showonlyifgeneratedeposit',
						'name' => 'validate_generated_deposit',
						'morecss' => 'margintoponly marginbottomonly',
						'label' => $langs->trans('ValidateGeneratedDeposit')
					);

					$formquestion[] = array(
						'type' => 'onecolumn',
						'value' => '
							<script>
								let signedValue = ' . $object::STATUS_SIGNED . ';

								$(document).ready(function() {
									$("[name=generate_deposit]").change(function () {
										let $self = $(this);
										let $target = $(".showonlyifgeneratedeposit").parent(".tagtr");

										if (! $self.parents(".tagtr").is(":hidden") && $self.is(":checked")) {
											$target.show();
										} else {
											$target.hide();
										}

										return true;
									});

									$("#statut").change(function() {
										let $target = $(".showonlyifsigned").parent(".tagtr");

										if ($(this).val() == signedValue) {
											$target.show();
										} else {
											$target.hide();
										}

										$("[name=generate_deposit]").trigger("change");

										return true;
									});

									$("#statut").trigger("change");
								});
							</script>
						'
					);
				}
			}
		}

		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$formquestion = array_merge($formquestion, array(
				array('type' => 'onecolumn', 'value' => $notify->confirmMessage('PROPAL_CLOSE_SIGNED', $object->socid, $object)),
			));
		}

		if (!getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE')) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('SetAcceptedRefused'), '', 'confirm_closeas', $formquestion, '', 1, 250);
		} else {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?statut=3&id=' . $object->id, $langs->trans('Close'), '', 'confirm_closeas', $formquestion, '', 1, 250);
		}
	} elseif ($action == 'cancel') {
		// Confirm cancel
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("CancelPropal"), $langs->trans('ConfirmCancelPropal', $object->ref), 'confirm_cancel', '', 0, 1);
	} elseif ($action == 'delete') {
		// Confirm delete
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp', $object->ref), 'confirm_delete', '', 0, 1);
	} elseif ($action == 'reopen') {
		// Confirm reopen
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenProp', $object->ref), 'confirm_reopen', '', 0, 1);
	} elseif ($action == 'ask_deleteline') {
		// Confirmation delete product/service line
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	} elseif ($action == 'validate') {
		// Confirm validate proposal
		$error = 0;

		// We verify whether the object is provisionally numbering
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV' || $ref == '') {
			$numref = $object->getNextNumRef($soc);
			if (empty($numref)) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateProp', $numref);
		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('PROPAL_VALIDATE', $object->socid, $object);
		}

		// mandatoryPeriod
		$nbMandated = 0;
		foreach ($object->lines as $line) {
			$res = $line->fetch_product();
			if ($res  > 0) {
				if ($line->product->isService() && $line->product->isMandatoryPeriod() && (empty($line->date_start) || empty($line->date_end))) {
					$nbMandated++;
					break;
				}
			}
		}
		if ($nbMandated > 0) {
			if (getDolGlobalString('SERVICE_STRICT_MANDATORY_PERIOD')) {
				setEventMessages($langs->trans("mandatoryPeriodNeedTobeSetMsgValidate"), null, 'errors');
				$error++;
			} else {
				$text .= '<div><span class="clearboth nowraponall warning">'.img_warning().$langs->trans("mandatoryPeriodNeedTobeSetMsgValidate").'</span></div>';
			}
		}

		if (!$error) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProp'), $text, 'confirm_validate', '', 0, 1, 240);
		}
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Proposal card

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$soc->getNomUrl(1, 'customer');
	if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $soc->id > 0) {
		$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/comm/propal/list.php?socid='.$soc->id.'&search_societe='.urlencode($soc->name).'">'.$langs->trans("OtherProposals").'</a>)';
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if ($usercancreate) {
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


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	// Call Hook tabContentViewProposal
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentViewProposal', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Link for thirdparty discounts
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
			$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
		}

		print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td>';

		$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
		$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');

		$caneditfield = ($object->statut != Propal::STATUS_SIGNED && $object->statut != Propal::STATUS_BILLED);

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = $_SERVER["PHP_SELF"].'?id='.$object->id;
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';

		// Date of proposal
		print '<tr>';
		print '<td>';
		// print '<table class="nobordernopadding" width="100%"><tr><td>';
		// print $langs->trans('DatePropal');
		// print '</td>';
		// if ($action != 'editdate' && $usercancreate && $caneditfield) {
		// 	print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
		// }

		// print '</tr></table>';
		$editenable = $usercancreate && $caneditfield && $object->statut == Propal::STATUS_DRAFT;
		print $form->editfieldkey("DatePropal", 'date', '', $object, $editenable);
		print '</td><td class="valuefield">';
		if ($action == 'editdate' && $usercancreate && $caneditfield) {
			print '<form name="editdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdate">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			print $form->selectDate($object->date, 're', 0, 0, 0, "editdate");
			print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			if ($object->date) {
				print dol_print_date($object->date, 'day');
			} else {
				print '&nbsp;';
			}
		}
		print '</td>';

		// Date end proposal
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('DateEndPropal');
		print '</td>';
		if ($action != 'editecheance' && $usercancreate && $caneditfield) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editecheance&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td class="valuefield">';
		if ($action == 'editecheance' && $usercancreate && $caneditfield) {
			print '<form name="editecheance" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setecheance">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			print $form->selectDate($object->fin_validite, 'ech', 0, 0, 0, "editecheance");
			print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			if (!empty($object->fin_validite)) {
				print dol_print_date($object->fin_validite, 'day');
				if ($object->statut == Propal::STATUS_VALIDATED && $object->fin_validite < ($now - $conf->propal->cloture->warning_delay)) {
					print img_warning($langs->trans("Late"));
				}
			} else {
				print '&nbsp;';
			}
		}
		print '</td>';
		print '</tr>';

		// Payment term
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';
		if ($action != 'editconditions' && $usercancreate && $caneditfield) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetConditions'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td class="valuefield">';
		if ($action == 'editconditions' && $usercancreate && $caneditfield) {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 0, '', 1, $object->deposit_percent);
		} else {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none', 0, '', 1, $object->deposit_percent);
		}
		print '</td>';
		print '</tr>';

		// Payment mode
		print '<tr class="field_mode_reglement_id">';
		print '<td class="titlefieldcreate">';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && $usercancreate && $caneditfield) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td class="valuefieldcreate">';
		if ($action == 'editmode' && $usercancreate && $caneditfield) {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
		} else {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		}
		print '</td></tr>';

		// Delivery date
		$langs->load('deliveries');
		print '<tr><td>';
		print $form->editfieldkey($langs->trans('DeliveryDate'), 'date_livraison', $object->delivery_date, $object, $usercancreate && $caneditfield, 'datepicker');
		print '</td><td class="valuefieldedit">';
		print $form->editfieldval($langs->trans('DeliveryDate'), 'date_livraison', $object->delivery_date, $object, $usercancreate && $caneditfield, 'datepicker');
		print '</td>';
		print '</tr>';

		// Delivery delay
		print '<tr class="fielddeliverydelay"><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		if (isModEnabled('order')) {
			print $form->textwithpicto($langs->trans('AvailabilityPeriod'), $langs->trans('AvailabilityPeriod').' ('.$langs->trans('AfterOrder').')');
		} else {
			print $langs->trans('AvailabilityPeriod');
		}
		print '</td>';
		if ($action != 'editavailability' && $usercancreate && $caneditfield) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editavailability&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetAvailability'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td class="valuefield">';
		if ($action == 'editavailability' && $usercancreate && $caneditfield) {
			$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'availability_id', 1);
		} else {
			$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'none', 1);
		}

		print '</td>';
		print '</tr>';

		// Shipping Method
		if (isModEnabled("shipping")) {
			print '<tr><td>';
			print '<table class="nobordernopadding centpercent"><tr><td>';
			print $langs->trans('SendingMethod');
			print '</td>';
			if ($action != 'editshippingmethod' && $usercancreate && $caneditfield) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshippingmethod&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetShippingMode'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td class="valuefield">';
			if ($action == 'editshippingmethod' && $usercancreate && $caneditfield) {
				$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
			} else {
				$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Warehouse
		if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROPAL')) {
			$langs->load('stocks');
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			print '<tr class="field_warehouse_id"><td class="titlefieldcreate">';
			$editenable = $usercancreate;
			print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $editenable);
			print '</td><td class="valuefieldcreate">';
			if ($action == 'editwarehouse') {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'warehouse_id', 1);
			} else {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Origin of demand
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('Source');
		print '</td>';
		if ($action != 'editdemandreason' && $usercancreate) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdemandreason&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDemandReason'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td class="valuefield">';
		if ($action == 'editdemandreason' && $usercancreate) {
			$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'demand_reason_id', 1);
		} else {
			$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'none');
		}
		print '</td>';
		print '</tr>';

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			// Multicurrency code
			print '<tr>';
			print '<td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
			print '</td>';
			if ($action != 'editmulticurrencycode' && $object->statut == $object::STATUS_DRAFT && $usercancreate) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td class="valuefield">';
			if ($object->statut == $object::STATUS_DRAFT && $action == 'editmulticurrencycode' && $usercancreate) {
				$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'multicurrency_code');
			} else {
				$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'none');
			}
			print '</td></tr>';

			// Multicurrency rate
			if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
				print '<tr>';
				print '<td>';
				print '<table class="nobordernopadding" width="100%"><tr>';
				print '<td>';
				print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
				print '</td>';
				if ($action != 'editmulticurrencyrate' && $object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency && $usercancreate) {
					print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
				}
				print '</tr></table>';
				print '</td><td class="valuefield">';
				if ($object->statut == $object::STATUS_DRAFT && ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') && $usercancreate) {
					if ($action == 'actualizemulticurrencyrate') {
						list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
					}
					$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
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

		if ($soc->outstanding_limit) {
			// Outstanding Bill
			print '<tr><td>';
			print $langs->trans('OutstandingBill');
			print '</td><td class="valuefield">';
			$arrayoutstandingbills = $soc->getOutstandingBills();
			print($arrayoutstandingbills['opened'] > $soc->outstanding_limit ? img_warning() : '');
			print price($arrayoutstandingbills['opened']).' / ';
			print price($soc->outstanding_limit, 0, $langs, 1, - 1, - 1, $conf->currency);
			print '</td>';
			print '</tr>';
		}

		if (getDolGlobalString('BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL') && isModEnabled("bank")) {
			// Bank Account
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('BankAccount');
			print '</td>';
			if ($action != 'editbankaccount' && $usercancreate) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td class="valuefield">';
			if ($action == 'editbankaccount') {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
			} else {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		$tmparray = $object->getTotalWeightVolume();
		$totalWeight = isset($tmparray['weight']) ? $tmparray['weight'] : 0;
		$totalVolume = isset($tmparray['volume']) ? $tmparray['volume'] : 0;
		if ($totalWeight) {
			print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
			print '<td class="valuefield">';
			print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no', 0);
			print '</td></tr>';
		}
		if ($totalVolume) {
			print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
			print '<td class="valuefield">';
			print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no', 0);
			print '</td></tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('IncotermLabel');
			print '<td><td class="right">';
			if ($action != 'editincoterm' && $usercancreate && $caneditfield) {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
			} else {
				print '&nbsp;';
			}
			print '</td></tr></table>';
			print '</td>';
			print '<td class="valuefield">';
			if ($action == 'editincoterm' && $usercancreate && $caneditfield) {
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
			} else {
				print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
			}
			print '</td></tr>';
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		print '<tr>';
		print '<td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
		print '<td class="nowrap amountcard right">' . price($object->total_ht, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_ht, 0, $langs, 1, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		print '<tr>';
		print '<td class="titlefieldmiddle">' . $langs->trans('AmountVAT') . '</td>';
		print '<td class="nowrap amountcard right">' . price($object->total_tva, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_tva, 0, $langs, 1, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) {
			print '<tr>';
			print '<td class="titlefieldmiddle">' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
			print '<td class="nowrap amountcard right">' . price($object->total_localtax1, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				$object->multicurrency_total_localtax1 = price2num($object->total_localtax1 * $object->multicurrency_tx, 'MT');

				print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_localtax1, 0, $langs, 1, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';
		}

		if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) {
			print '<tr>';
			print '<td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
			print '<td class="nowrap amountcard right">' . price($object->total_localtax2, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				$object->multicurrency_total_localtax2 = price2num($object->total_localtax2 * $object->multicurrency_tx, 'MT');

				print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_localtax2, 0, $langs, 1, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';
		}

		print '<tr>';
		print '<td>' . $langs->trans('AmountTTC') . '</td>';
		print '<td class="nowrap amountcard right">' . price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_ttc, 0, $langs, 1, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		print '</table>';

		// Margin Infos
		if (isModEnabled('margin')) {
			$formmargin->displayMarginInfos($object);
		}

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';

		if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
			$blocname = 'contacts';
			$title = $langs->trans('ContactsAddresses');
			include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
		}

		if (getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
			$blocname = 'notes';
			$title = $langs->trans('Notes');
			include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
		}

		/*
		 * Lines
		 */

		// Get object lines
		$result = $object->getLinesArray();

		// Add products/services form
		//$forceall = 1;
		global $inputalsopricewithtax;
		$inputalsopricewithtax = 1;

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="backtopage" value="'.$backtopage.'">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->statut == Propal::STATUS_DRAFT) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->statut == Propal::STATUS_DRAFT && $usercancreate && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow centpercent">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, $object->thirdparty, $lineid, 1);
		}

		// Form to add new line
		if ($object->statut == Propal::STATUS_DRAFT && $usercancreate && $action != 'selectlines') {
			if ($action != 'editline') {
				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, $soc);
				}
			} else {
				$parameters = array();
				$reshook = $hookmanager->executeHooks('formEditObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}
		}

		if (!empty($object->lines) || ($object->statut == Propal::STATUS_DRAFT && $usercancreate && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}

	print dol_get_fiche_end();


	/*
	 * Button Actions
	 */

	if ($action != 'presend') {
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		// modified by hook
		if (empty($reshook)) {
			if ($action != 'editline') {
				// Validate
				if (($object->statut == Propal::STATUS_DRAFT && $object->total_ttc >= 0 && count($object->lines) > 0)
					|| ($object->statut == Propal::STATUS_DRAFT && getDolGlobalString('PROPAL_ENABLE_NEGATIVE') && count($object->lines) > 0)) {
					if ($usercanvalidate) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validate&token='.newToken().'">'.(!getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE') ? $langs->trans('Validate') : $langs->trans('ValidateAndSign')).'</a>';
					} else {
						print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans('Validate').'</a>';
					}
				}
				// Create event
				/*if (isModEnabled('agenda') && !empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
				}*/
				// Edit
				if ($object->statut == Propal::STATUS_VALIDATED && $usercancreate) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=modif&token='.newToken().'">'.$langs->trans('Modify').'</a>';
				}

				// ReOpen
				if (((getDolGlobalString('PROPAL_REOPEN_UNSIGNED_ONLY') && $object->statut == Propal::STATUS_NOTSIGNED) || (!getDolGlobalString('PROPAL_REOPEN_UNSIGNED_ONLY') && ($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED || $object->statut == Propal::STATUS_CANCELED))) && $usercanclose) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().(!getDolGlobalString('MAIN_JUMP_TAG') ? '' : '#reopen').'"';
					print '>'.$langs->trans('ReOpen').'</a>';
				}

				// Send
				if (empty($user->socid)) {
					if ($object->statut == Propal::STATUS_VALIDATED || $object->statut == Propal::STATUS_SIGNED || getDolGlobalString('PROPOSAL_SENDBYEMAIL_FOR_ALL_STATUS')) {
						print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?action=presend&token='.newToken().'&id='.$object->id.'&mode=init#formmailbeforetitle', '', $usercansend);
					}
				}

				// Create a sale order
				if (isModEnabled('order') && $object->statut == Propal::STATUS_SIGNED) {
					if ($usercancreateorder) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans("AddOrder").'</a>';
					}
				}

				// Create a purchase order
				if (getDolGlobalString('WORKFLOW_CAN_CREATE_PURCHASE_ORDER_FROM_PROPOSAL')) {
					if ($object->statut == Propal::STATUS_SIGNED && isModEnabled("supplier_order")) {
						if ($usercancreatepurchaseorder) {
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans("AddPurchaseOrder").'</a>';
						}
					}
				}

				// Create an intervention
				if (isModEnabled("service") && isModEnabled('intervention') && $object->statut == Propal::STATUS_SIGNED) {
					if ($usercancreateintervention) {
						$langs->load("interventions");
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans("AddIntervention").'</a>';
					}
				}

				// Create contract
				if (isModEnabled('contract') && $object->statut == Propal::STATUS_SIGNED) {
					$langs->load("contracts");

					if ($usercancreatecontract) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans('AddContract').'</a>';
					}
				}

				// Create an invoice and classify billed
				if ($object->statut == Propal::STATUS_SIGNED && !getDolGlobalString('PROPOSAL_ARE_NOT_BILLABLE')) {
					if (isModEnabled('invoice') && $usercancreateinvoice) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
					}

					$arrayofinvoiceforpropal = $object->getInvoiceArrayList();
					if ((is_array($arrayofinvoiceforpropal) && count($arrayofinvoiceforpropal) > 0) || !getDolGlobalString('WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED')) {
						if ($usercanclose) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=classifybilled&token='.newToken().'&socid='.$object->socid.'">'.$langs->trans("ClassifyBilled").'</a>';
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("ClassifyBilled").'</a>';
						}
					}
				}

				if (!getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE')) {
					// Close as accepted/refused
					if ($object->statut == Propal::STATUS_VALIDATED) {
						if ($usercanclose) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=closeas&token='.newToken().(!getDolGlobalString('MAIN_JUMP_TAG') ? '' : '#close').'"';
							print '>'.$langs->trans('SetAcceptedRefused').'</a>';
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'"';
							print '>'.$langs->trans('SetAcceptedRefused').'</a>';
						}
					}
				} else {
					// Set not signed (close)
					if ($object->statut == Propal::STATUS_DRAFT && $usercanclose) {
						print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&token='.newToken().'&action=closeas&token='.newToken() . (!getDolGlobalString('MAIN_JUMP_TAG') ? '' : '#close') . '"';
						print '>' . $langs->trans('SetRefusedAndClose') . '</a>';
					}
				}

				// Cancel propal
				if ($object->status > Propal::STATUS_DRAFT && $usercanclose) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'">'.$langs->trans("CancelPropal").'</a>';
				}

				// Clone
				if ($usercancreate) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken().'&object='.$object->element.'">'.$langs->trans("ToClone").'</a>';
				}

				// Delete
				print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $usercandelete);
			}
		}

		print '</div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre
		/*
		 * Generated documents
		 */
		$objref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->propal->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $usercanread;
		$delallowed = $usercancreate;

		print $formfile->showdocuments('propal', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang, '', $object);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('propal'));

		$compatibleImportElementsList = false;
		if ($user->hasRight('propal', 'creer') && $object->statut == Propal::STATUS_DRAFT) {
			$compatibleImportElementsList = array('commande', 'propal', 'facture'); // import from linked elements
		}
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

		// Show online signature link
		$useonlinesignature = getDolGlobalInt('PROPOSAL_ALLOW_ONLINESIGN');

		if ($object->statut != Propal::STATUS_DRAFT && $useonlinesignature) {
			print '<br><!-- Link to sign -->';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';
			print showOnlineSignatureUrl('proposal', $object->ref, $object).'<br>';
		}

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/comm/propal/agenda.php?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'propal', $socid, 1, '', $MAXEVENT, '', $morehtmlcenter); // Show all action for thirdparty

		print '</div></div>';
	}

	// Presend form
	$modelmail = 'propal_send';
	$defaulttopic = 'SendPropalRef';
	$diroutput = $conf->propal->multidir_output[$object->entity];
	$trackid = 'pro'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
