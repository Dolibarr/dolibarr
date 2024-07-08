<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2010-2017	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2014	Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2020	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2014-2016	Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2018-2021	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2023		Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2023		Nick Fragoulis
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
 *       \file       htdocs/contrat/card.php
 *       \ingroup    contrat
 *       \brief      Page of a contract
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array("contracts", "orders", "companies", "bills", "products", 'compta'));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$socid = GETPOSTINT('socid');
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOSTINT('originid');
$idline = GETPOSTINT('elrowid') ? GETPOSTINT('elrowid') : GETPOSTINT('rowid');

// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));


$datecontrat = '';
$usehm = (getDolGlobalString('MAIN_USE_HOURMIN_IN_DATE_RANGE') ? $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE : 0);

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('contractcard', 'globalcard'));

$object = new Contrat($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0 || !empty($ref) && $action != 'add') {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$ret = $object->fetch_thirdparty();
	}
	if ($ret < 0) {
		dol_print_error(null, $object->error);
	}
}

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// fetch optionals attributes lines and labels
$extralabelslines = $extrafields->fetch_name_optionals_label($object->table_element_line);

$permissionnote = $user->hasRight('contrat', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('contrat', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontodelete = ($user->hasRight('contrat', 'creer') && $object->statut == $object::STATUS_DRAFT) || $user->hasRight('contrat', 'supprimer');
$permissiontoadd   = $user->hasRight('contrat', 'creer');     //  Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontoedit = $permissiontoadd;
$permissiontoactivate = $user->hasRight('contrat', 'activer');
$error = 0;

$result = restrictedArea($user, 'contrat', $object->id);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/contrat/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/contrat/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
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

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';  // Must be 'include', not 'include_once'

	if ($action == 'confirm_active' && $confirm == 'yes' && $permissiontoactivate) {
		$date_start = '';
		$date_end = '';
		if (GETPOST('startmonth') && GETPOST('startday') && GETPOST('startyear')) {
			$date_start = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));
		}
		if (GETPOST('endmonth') && GETPOST('endday') && GETPOST('endyear')) {
			$date_end = dol_mktime(GETPOST('endhour'), GETPOST('endmin'), 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
		}

		$result = $object->active_line($user, GETPOSTINT('ligne'), $date_start, $date_end, GETPOST('comment'));

		if ($result > 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_closeline' && $confirm == 'yes' && $permissiontoactivate) {
		$date_end = '';
		if (GETPOST('endmonth') && GETPOST('endday') && GETPOST('endyear')) {
			$date_end = dol_mktime(GETPOST('endhour'), GETPOST('endmin'), 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
		}
		if (!$date_end) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
		}
		if (!$error) {
			$result = $object->close_line($user, GETPOSTINT('ligne'), $date_end, urldecode(GETPOST('comment')));
			if ($result > 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if (GETPOST('mode') == 'predefined') {
		$date_start = '';
		$date_end = '';
		if (GETPOST('date_startmonth') && GETPOST('date_startday') && GETPOST('date_startyear')) {
			$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		}
		if (GETPOST('date_endmonth') && GETPOST('date_endday') && GETPOST('date_endyear')) {
			$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
		}
	}

	// Param dates
	$date_start_update = '';
	$date_end_update = '';
	$date_start_real_update = '';
	$date_end_real_update = '';
	if (GETPOST('date_start_updatemonth') && GETPOST('date_start_updateday') && GETPOST('date_start_updateyear')) {
		$date_start_update = dol_mktime(GETPOST('date_start_updatehour'), GETPOST('date_start_updatemin'), 0, GETPOST('date_start_updatemonth'), GETPOST('date_start_updateday'), GETPOST('date_start_updateyear'));
	}
	if (GETPOST('date_end_updatemonth') && GETPOST('date_end_updateday') && GETPOST('date_end_updateyear')) {
		$date_end_update = dol_mktime(GETPOST('date_end_updatehour'), GETPOST('date_end_updatemin'), 0, GETPOST('date_end_updatemonth'), GETPOST('date_end_updateday'), GETPOST('date_end_updateyear'));
	}
	if (GETPOST('date_start_real_updatemonth') && GETPOST('date_start_real_updateday') && GETPOST('date_start_real_updateyear')) {
		$date_start_real_update = dol_mktime(GETPOST('date_start_real_updatehour'), GETPOST('date_start_real_updatemin'), 0, GETPOST('date_start_real_updatemonth'), GETPOST('date_start_real_updateday'), GETPOST('date_start_real_updateyear'));
	}
	if (GETPOST('date_end_real_updatemonth') && GETPOST('date_end_real_updateday') && GETPOST('date_end_real_updateyear')) {
		$date_end_real_update = dol_mktime(GETPOST('date_end_real_updatehour'), GETPOST('date_end_real_updatemin'), 0, GETPOST('date_end_real_updatemonth'), GETPOST('date_end_real_updateday'), GETPOST('date_end_real_updateyear'));
	}
	if (GETPOST('remonth') && GETPOST('reday') && GETPOST('reyear')) {
		$datecontrat = dol_mktime(GETPOST('rehour'), GETPOST('remin'), 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	}

	// Add contract
	if ($action == 'add' && $user->hasRight('contrat', 'creer')) {
		// Check
		if (empty($datecontrat)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$action = 'create';
		}

		if ($socid < 1) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdParty")), null, 'errors');
			$action = 'create';
			$error++;
		}

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
			$action = 'create';
		}

		if (!$error) {
			$object->socid = $socid;
			$object->date_contrat = $datecontrat;

			$object->commercial_suivi_id = GETPOSTINT('commercial_suivi_id');
			$object->commercial_signature_id = GETPOSTINT('commercial_signature_id');

			$object->note_private = GETPOST('note_private', 'alpha');
			$object->note_public				= GETPOST('note_public', 'alpha');
			$object->fk_project					= GETPOSTINT('projectid');
			$object->remise_percent = price2num(GETPOST('remise_percent'), '', 2);
			$object->ref = GETPOST('ref', 'alpha');
			$object->ref_customer				= GETPOST('ref_customer', 'alpha');
			$object->ref_supplier				= GETPOST('ref_supplier', 'alpha');

			// If creation from another object of another module (Example: origin=propal, originid=1)
			if (!empty($origin) && !empty($originid)) {
				// Parse element/subelement (ex: project_task)
				$element = $subelement = $origin;
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
				if ($element == 'invoice' || $element == 'facture') {
					$element = 'compta/facture';
					$subelement = 'facture';
				}

				$object->origin    = $origin;
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
						$srcobject->fetch_thirdparty();
						$lines = $srcobject->lines;
						if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
							$srcobject->fetch_lines();
							$lines = $srcobject->lines;
						}

						$fk_parent_line = 0;
						$num = count($lines);

						for ($i = 0; $i < $num; $i++) {
							$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

							if ($product_type == 1 || (getDolGlobalString('CONTRACT_SUPPORT_PRODUCTS') && in_array($product_type, array(0, 1)))) { 	// TODO Exclude also deee
								// service prédéfini
								if ($lines[$i]->fk_product > 0) {
									$product_static = new Product($db);

									// Define output language
									if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
										$prod = new Product($db);
										$prod->id = $lines[$i]->fk_product;
										$prod->getMultiLangs();

										$outputlangs = $langs;
										$newlang = '';
										if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
											$newlang = GETPOST('lang_id', 'aZ09');
										}
										if (empty($newlang)) {
											$newlang = $srcobject->thirdparty->default_lang;
										}
										if (!empty($newlang)) {
											$outputlangs = new Translate("", $conf);
											$outputlangs->setDefaultLang($newlang);
										}

										$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["libelle"])) ? $prod->multilangs[$outputlangs->defaultlang]["libelle"] : $lines[$i]->product_label;
									} else {
										$label = $lines[$i]->product_label;
									}
									$desc = ($lines[$i]->desc && $lines[$i]->desc != $lines[$i]->label) ? dol_htmlentitiesbr($lines[$i]->desc) : '';
								} else {
									$desc = dol_htmlentitiesbr($lines[$i]->desc);
								}

								// Extrafields
								$array_options = array();
								// For avoid conflicts if trigger used
								if (method_exists($lines[$i], 'fetch_optionals')) {
									$lines[$i]->fetch_optionals();
									$array_options = $lines[$i]->array_options;
								}

								$txtva = $lines[$i]->vat_src_code ? $lines[$i]->tva_tx.' ('.$lines[$i]->vat_src_code.')' : $lines[$i]->tva_tx;

								// View third's localtaxes for now
								$localtax1_tx = get_localtax($txtva, 1, $object->thirdparty);
								$localtax2_tx = get_localtax($txtva, 2, $object->thirdparty);

								$result = $object->addline(
									$desc,
									$lines[$i]->subprice,
									$lines[$i]->qty,
									$txtva,
									$localtax1_tx,
									$localtax2_tx,
									$lines[$i]->fk_product,
									$lines[$i]->remise_percent,
									$lines[$i]->date_start,
									$lines[$i]->date_end,
									'HT',
									0,
									$lines[$i]->info_bits,
									$lines[$i]->fk_fournprice,
									$lines[$i]->pa_ht,
									$array_options,
									$lines[$i]->fk_unit,
									$num + 1
								);

								if ($result < 0) {
									$error++;
									break;
								}
							}
						}
					} else {
						setEventMessages($srcobject->error, $srcobject->errors, 'errors');
						$error++;
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
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
				if ($error) {
					$action = 'create';
				}
			} else {
				$result = $object->create($user);
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
				$action = 'create';
			}
		}
	} elseif ($action == 'classin' && $user->hasRight('contrat', 'creer')) {
		$object->setProject(GETPOST('projectid'));
	} elseif ($action == 'addline' && $user->hasRight('contrat', 'creer')) {
		// Add a new line
		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');

		$price_ht = '';
		$price_ht_devise = '';
		$price_ttc = '';
		$price_ttc_devise = '';

		$rang = count($object->lines) + 1;

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

		if (GETPOST('prod_entry_mode', 'alpha') == 'free') {
			$idprod = 0;
		} else {
			$idprod = GETPOSTINT('idprod');

			if (getDolGlobalString('MAIN_DISABLE_FREE_LINES') && $idprod <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")), null, 'errors');
				$error++;
			}
		}

		$tva_tx = GETPOST('tva_tx', 'alpha');

		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS');
		$remise_percent = (GETPOSTISSET('remise_percent'.$predef) ? price2num(GETPOST('remise_percent'.$predef), '', 2) : 0);
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}

		if ($qty == '') {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
			$error++;
		}
		if (GETPOST('prod_entry_mode', 'alpha') == 'free' && (empty($idprod) || $idprod < 0) && empty($product_desc)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")), null, 'errors');
			$error++;
		}

		$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
		if (!empty($date_start) && !empty($date_end) && $date_start > $date_end) {
			setEventMessages($langs->trans("Error").': '.$langs->trans("DateStartPlanned").' > '.$langs->trans("DateEndPlanned"), null, 'errors');
			$error++;
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

		if (!$error) {
			// Clean parameters
			$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

			// Ecrase $tva_tx par celui du produit. TODO Remove this once vat selection is open
			// Get and check minimum price
			if ($idprod > 0) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				// Update if prices fields are defined
				/*$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
				$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
				if (empty($tva_tx)) {
				}*/
				$tva_npr = 0;

				$price_min = $prod->price_min;
				$price_min_ttc = $prod->price_min_ttc;

				// On defini prix unitaire
				if (getDolGlobalString('PRODUIT_MULTIPRICES') && $object->thirdparty->price_level) {
					$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
					$price_min_ttc = $prod->multiprices_min_ttc[$object->thirdparty->price_level];
				} elseif (getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
					// If price per customer
					require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

					$prodcustprice = new ProductCustomerPrice($db);

					$filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

					$result = $prodcustprice->fetchAll('', '', 0, 0, $filter);
					if ($result) {
						if (count($prodcustprice->lines) > 0) {
							$price_min =  price($prodcustprice->lines[0]->price_min);
							$price_min_ttc =  price($prodcustprice->lines[0]->price_min_ttc);
							/*$tva_tx = $prodcustprice->lines[0]->tva_tx;
							if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) {
								$tva_tx .= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
							}
							$tva_npr = $prodcustprice->lines[0]->recuperableonly;
							if (empty($tva_tx)) {
								$tva_npr = 0;
							}*/
						}
					}
				}

				$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', (string) $prod->tva_tx));

				// Set unit price to use
				if (!empty($price_ht) || $price_ht === '0') {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num((float) $pu_ht * (1 + ((float) $tmpvat / 100)), 'MU');
					$price_base_type = 'HT';
				} elseif (!empty($price_ttc) || $price_ttc === '0') {
					$pu_ttc = price2num($price_ttc, 'MU');
					$pu_ht = price2num((float) $pu_ttc / (1 + ((float) $tmpvat / 100)), 'MU');
					$price_base_type = 'TTC';
				}

				$desc = $prod->description;

				//If text set in desc is the same as product descpription (as now it's preloaded) we add it only one time
				if ($product_desc == $desc && getDolGlobalString('PRODUIT_AUTOFILL_DESC')) {
					$product_desc = '';
				}

				if (!empty($product_desc) && getDolGlobalString('MAIN_NO_CONCAT_DESCRIPTION')) {
					$desc = $product_desc;
				} else {
					$desc = dol_concatdesc($desc, $product_desc, '', getDolGlobalString('MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION'));
				}

				$fk_unit = $prod->fk_unit;
			} else {
				$pu_ht = price2num($price_ht, 'MU');
				$pu_ttc = price2num($price_ttc, 'MU');
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}
				$tva_tx = str_replace('*', '', $tva_tx);
				$desc = $product_desc;
				$fk_unit = GETPOST('units', 'alpha');
				$pu_ht_devise = price2num($price_ht_devise, 'MU');
				$pu_ttc_devise = price2num($price_ttc_devise, 'MU');

				$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));

				// Set unit price to use
				if (!empty($price_ht) || $price_ht === '0') {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num((float) $pu_ht * (1 + ((float) $tmpvat / 100)), 'MU');
					$price_base_type = 'HT';
				} elseif (!empty($price_ttc) || $price_ttc === '0') {
					$pu_ttc = price2num($price_ttc, 'MU');
					$pu_ht = price2num((float) $pu_ttc / (1 + ((float) $tmpvat / 100)), 'MU');
					$price_base_type = 'TTC';
				}
			}

			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_npr);

			// ajout prix achat
			$fk_fournprice = GETPOST('fournprice');
			if (GETPOST('buying_price')) {
				$pa_ht = GETPOST('buying_price');
			} else {
				$pa_ht = null;
			}

			$info_bits = 0;
			if ($tva_npr) {
				$info_bits |= 0x01;
			}

			if (((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('produit', 'ignore_price_min_advance'))
				|| !getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) && ($price_min && ((float) price2num($pu_ht) * (1 - (float) price2num($remise_percent) / 100) < (float) price2num($price_min)))) {
				$object->error = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
				$result = -1;
			} else {
				// Insert line
				$result = $object->addline(
					$desc,
					$pu_ht,
					$qty,
					$tva_tx,
					$localtax1_tx,
					$localtax2_tx,
					$idprod,
					$remise_percent,
					$date_start,
					$date_end,
					$price_base_type,
					$pu_ttc,
					$info_bits,
					$fk_fournprice,
					$pa_ht,
					$array_options,
					$fk_unit,
					$rang
				);
			}

			if ($result > 0) {
				// Define output language
				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE') && getDolGlobalString('CONTRACT_ADDON_PDF')) {    // No generation if default type not defined
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

					$ret = $object->fetch($id); // Reload to get new records

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
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'updateline' && $user->hasRight('contrat', 'creer') && !GETPOST('cancel', 'alpha')) {
		$error = 0;
		$predef = '';

		if (!empty($date_start_update) && !empty($date_end_update) && $date_start_update > $date_end_update) {
			setEventMessages($langs->trans("Error").': '.$langs->trans("DateStartPlanned").' > '.$langs->trans("DateEndPlanned"), null, 'errors');
			$action = 'editline';
			$error++;
		}

		if (!$error) {
			$objectline = new ContratLigne($db);
			if ($objectline->fetch($idline) < 0) {
				setEventMessages($objectline->error, $objectline->errors, 'errors');
				$error++;
			}
			$objectline->fetch_optionals();

			$objectline->oldcopy = dol_clone($objectline, 2);
		}

		$db->begin();

		if (!$error) {
			if ($date_start_real_update == '') {
				$date_start_real_update = $objectline->date_start_real;
			}
			if ($date_end_real_update == '') {
				$date_end_real_update = $objectline->date_end_real;
			}

			$vat_rate = GETPOST('eltva_tx', 'alpha');
			// Define info_bits
			$info_bits = 0;
			if (preg_match('/\*/', $vat_rate)) {
				$info_bits |= 0x01;
			}

			// Define vat_rate
			$vat_rate = str_replace('*', '', $vat_rate);
			$localtax1_tx = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
			$localtax2_tx = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);

			$txtva = $vat_rate;

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			// ajout prix d'achat
			if (GETPOST('buying_price')) {
				$pa_ht = price2num(GETPOST('buying_price'), '', 2);
			} else {
				$pa_ht = null;
			}

			$fk_unit = GETPOST('unit', 'alpha');

			// update price_ht with discount
			// TODO Use object->updateline instead objectline->update

			$price_ht =  price2num(GETPOST('elprice'), 'MU');
			$remise_percent = price2num(GETPOST('elremise_percent'), '', 2);
			if ($remise_percent > 0) {
				$remise = round(((float) $price_ht * (float) $remise_percent / 100), 2);
			}

			$objectline->fk_product = GETPOSTINT('idprod');
			$objectline->description = GETPOST('product_desc', 'restricthtml');
			$objectline->price_ht = $price_ht;
			$objectline->subprice = price2num(GETPOST('elprice'), 'MU');
			$objectline->qty = price2num(GETPOST('elqty'), 'MS');
			$objectline->remise_percent = $remise_percent;
			$objectline->tva_tx = ($txtva ? $txtva : 0); // Field may be disabled, so we use vat rate 0
			$objectline->vat_src_code = $vat_src_code;
			$objectline->localtax1_tx = is_numeric($localtax1_tx) ? $localtax1_tx : 0;
			$objectline->localtax2_tx = is_numeric($localtax2_tx) ? $localtax2_tx : 0;
			$objectline->date_start = $date_start_update;
			$objectline->date_start_real = $date_start_real_update;
			$objectline->date_end = $date_end_update;
			$objectline->date_end_real = $date_end_real_update;
			$objectline->user_closing_id = $user->id;
			//$objectline->fk_fournprice = $fk_fournprice;
			$objectline->pa_ht = $pa_ht;
			// $objectline->rang = $objectline->rang;

			if ($fk_unit > 0) {
				$objectline->fk_unit = GETPOST('unit');
			} else {
				$objectline->fk_unit = null;
			}

			// Extrafields
			$extralabelsline = $extrafields->fetch_name_optionals_label($objectline->table_element);
			$array_options = $extrafields->getOptionalsFromPost($object->table_element_line, $predef);

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$objectline->array_options[$key] = $array_options[$key];
				}
			}

			// TODO verifier price_min si fk_product et multiprix

			$result = $objectline->update($user);
			if ($result < 0) {
				$error++;
				setEventMessages($objectline->error, $objectline->errors, 'errors');
			}
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		$result = $object->deleteLine(GETPOSTINT('lineid'), $user);

		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_valid' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		$result = $object->validate($user);

		if ($result > 0) {
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

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'reopen' && $user->hasRight('contrat', 'creer')) {
		$result = $object->reopen($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_close' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		// Close all lines
		$result = $object->closeAll($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_activate' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		$date_start = dol_mktime(12, 0, 0, GETPOST('d_startmonth'), GETPOST('d_startday'), GETPOST('d_startyear'));
		$date_end   = dol_mktime(12, 0, 0, GETPOST('d_endmonth'), GETPOST('d_endday'), GETPOST('d_endyear'));
		$comment      = GETPOST('comment', 'alpha');
		$result = $object->activateAll($user, $date_start, 0, $comment, $date_end);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('contrat', 'supprimer')) {
		$result = $object->delete($user);
		if ($result >= 0) {
			header("Location: list.php?restore_lastsearch_values=1");
			return;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_move' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		if (GETPOST('newcid') > 0) {
			$contractline = new ContratLigne($db);
			$result = $contractline->fetch(GETPOSTINT('lineid'));
			$contractline->fk_contrat = GETPOSTINT('newcid');
			$result = $contractline->update($user, 1);
			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
				return;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefNewContract")), null, 'errors');
		}
	} elseif ($action == 'update_extras' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);

		$attribute = GETPOST('attribute', 'alphanohtml');

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute);
		if ($ret < 0) {
			setEventMessages($extrafields->error, $object->errors, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $object->updateExtraField($attribute, 'CONTRACT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	} elseif ($action == 'setref_supplier' && $permissiontoadd) {
		if (!$cancel) {
			$object->oldcopy = dol_clone($object, 2);

			$result = $object->setValueFrom('ref_supplier', GETPOST('ref_supplier', 'alpha'), '', null, 'text', '', $user, 'CONTRACT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'editref_supplier';
			} else {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
	} elseif ($action == 'setref_customer' && $permissiontoadd) {
		if (!$cancel) {
			$object->oldcopy = dol_clone($object, 2);

			$result = $object->setValueFrom('ref_customer', GETPOST('ref_customer', 'alpha'), '', null, 'text', '', $user, 'CONTRACT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'editref_customer';
			} else {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
	} elseif ($action == 'setref' && $permissiontoadd) {
		if (!$cancel) {
			$result = $object->fetch($id);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}

			$old_ref = $object->ref;

			$result = $object->setValueFrom('ref', GETPOST('ref', 'alpha'), '', null, 'text', '', $user, 'CONTRACT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'editref';
			} else {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				$old_filedir = $conf->contrat->multidir_output[$object->entity].'/'.dol_sanitizeFileName($old_ref);
				$new_filedir = $conf->contrat->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);

				// Rename directory of contract with new name
				dol_move_dir($old_filedir, $new_filedir);

				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
	} elseif ($action == 'setdate_contrat' && $permissiontoadd) {
		if (!$cancel) {
			$result = $object->fetch($id);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			$datacontrat = dol_mktime(GETPOST('date_contrathour'), GETPOST('date_contratmin'), 0, GETPOST('date_contratmonth'), GETPOST('date_contratday'), GETPOST('date_contratyear'));
			$result = $object->setValueFrom('date_contrat', $datacontrat, '', null, 'date', '', $user, 'CONTRACT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'editdate_contrat';
			} else {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to build doc
	$upload_dir = $conf->contrat->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity];
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'CONTRACT_SENTBYMAIL';
	$paramname = 'id';
	$mode = 'emailfromcontract';
	$trackid = 'con'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && $user->hasRight('contrat', 'creer')) {
		if ($action == 'addcontact') {
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
			$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} elseif ($action == 'swapstatut') {
			// bascule du statut d'un contact
			$result = $object->swapContactStatus(GETPOSTINT('ligne'));
		} elseif ($action == 'deletecontact') {
			// Efface un contact
			$result = $object->delete_contact(GETPOSTINT('lineid'));

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
		if (!GETPOSTINT('socid', 3)) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				$result = $object->createFromClone($user, $socid);
				if ($result > 0) {
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
	}
}


/*
 * View
 */

$title = $object->ref." - ".$langs->trans('Contract');
if ($action == 'create') {
	$title = $langs->trans("NewContract");
}
$help_url = 'EN:Module_Contracts|FR:Module_Contrat|ES:Contratos_de_servicio';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-contrat page-card');

$form = new Form($db);
$formfile = new FormFile($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

// Load object modContract
$module = (getDolGlobalString('CONTRACT_ADDON') ? $conf->global->CONTRACT_ADDON : 'mod_contract_serpis');
if (substr($module, 0, 13) == 'mod_contract_' && substr($module, -3) == 'php') {
	$module = substr($module, 0, dol_strlen($module) - 4);
}
$result = dol_include_once('/core/modules/contract/'.$module.'.php');
if ($result > 0) {
	$modCodeContract = new $module();
}

// Create
if ($action == 'create') {
	$objectsrc = null;
	print load_fiche_titre($langs->trans('NewContract'), '', 'contract');

	$soc = new Societe($db);
	if ($socid > 0) {
		$soc->fetch($socid);
	}

	if (GETPOST('origin') && GETPOSTINT('originid')) {
		// Parse element/subelement (ex: project_task)
		$regs = array();
		$element = $subelement = GETPOST('origin');
		if (preg_match('/^([^_]+)_([^_]+)/i', GETPOST('origin'), $regs)) {
			$element = $regs[1];
			$subelement = $regs[2];
		}

		if ($element == 'project') {
			$projectid = GETPOSTINT('originid');
		} else {
			// For compatibility
			if ($element == 'order' || $element == 'commande') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'invoice' || $element == 'facture') {
				$element = 'compta/facture';
				$subelement = 'facture';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
				$objectsrc->fetch_lines();
			}
			$objectsrc->fetch_thirdparty();

			// Replicate extrafields
			$objectsrc->fetch_optionals();
			$object->array_options = $objectsrc->array_options;

			$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');

			$soc = $objectsrc->thirdparty;

			$note_private = (!empty($objectsrc->note_private) ? $objectsrc->note_private : '');
			$note_public = (!empty($objectsrc->note_public) ? $objectsrc->note_public : '');

			// Object source contacts list
			$srccontactslist = $objectsrc->liste_contact(-1, 'external', 1);
		}
	} else {
		$projectid = GETPOSTINT('projectid');
		$note_private = GETPOST("note_private");
		$note_public = GETPOST("note_public");
	}

	$object->date_contrat = dol_now();

	print '<form name="form_contract" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">'."\n";
	print '<input type="hidden" name="remise_percent" value="0">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td>';
	if (!empty($modCodeContract->code_auto)) {
		$tmpcode = $langs->trans("Draft");
	} else {
		$tmpcode = '<input name="ref" class="maxwidth100" maxlength="128" value="'.dol_escape_htmltag(GETPOST('ref') ? GETPOST('ref') : $tmpcode).'">';
	}
	print $tmpcode;
	print '</td></tr>';

	// Ref customer
	print '<tr><td>'.$langs->trans('RefCustomer').'</td>';
	print '<td><input type="text" class="maxwidth150" name="ref_customer" id="ref_customer" value="'.dol_escape_htmltag(GETPOST('ref_customer', 'alpha')).'"></td></tr>';

	// Ref supplier
	print '<tr><td>'.$langs->trans('RefSupplier').'</td>';
	print '<td><input type="text" class="maxwidth150" name="ref_supplier" id="ref_supplier" value="'.dol_escape_htmltag(GETPOST('ref_supplier', 'alpha')).'"></td></tr>';

	// Thirdparty
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('ThirdParty').'</td>';
	if ($socid > 0) {
		print '<td>';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		print '</td>';
	} else {
		print '<td>';
		print img_picto('', 'company', 'class="pictofixedwidth"');
		print $form->select_company('', 'socid', '', 'SelectThirdParty', 1, 0, null, 0, 'minwidth300 widthcentpercentminusxx maxwidth500');
		print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
		print '</td>';
	}
	print '</tr>'."\n";

	if ($socid > 0) {
		// Ligne info remises tiers
		print '<tr><td>'.$langs->trans('Discounts').'</td><td>';
		if ($soc->remise_percent) {
			print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent).' ';
		} else {
			print '<span class="hideonsmartphone">'.$langs->trans("CompanyHasNoRelativeDiscount").'. </span>';
		}
		$absolute_discount = $soc->getAvailableDiscounts();
		if ($absolute_discount) {
			print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->trans("Currency".$conf->currency)).'.';
		} else {
			print '<span class="hideonsmartphone">'.$langs->trans("CompanyHasNoAbsoluteDiscount").'.</span>';
		}
		print '</td></tr>';
	}

	// Commercial suivi
	print '<tr><td class="nowrap"><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPFOLL").'</span></td><td>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers(GETPOST("commercial_suivi_id") ? GETPOST("commercial_suivi_id") : $user->id, 'commercial_suivi_id', 1, '');
	print '</td></tr>';

	// Commercial signature
	print '<tr><td class="nowrap"><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPSIGN").'</span></td><td>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers(GETPOST("commercial_signature_id") ? GETPOST("commercial_signature_id") : $user->id, 'commercial_signature_id', 1, '');
	print '</td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td>';
	print img_picto('', 'action', 'class="pictofixedwidth"');
	print $form->selectDate($datecontrat, '', 0, 0, 0, "contrat");
	print "</td></tr>";

	// Project
	if (isModEnabled('project')) {
		$langs->load('projects');

		$formproject = new FormProjets($db);

		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		print img_picto('', 'project', 'class="pictofixedwidth"');
		$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $projectid, "projectid", 0, 0, 1, 1);
		print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
		print "</td></tr>";
	}

	print '<tr><td>'.$langs->trans("NotePublic").'</td><td class="tdtop">';
	$doleditor = new DolEditor('note_public', $note_public, '', '100', 'dolibarr_notes', 'In', 1, true, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	if (empty($user->socid)) {
		print '<tr><td>'.$langs->trans("NotePrivate").'</td><td class="tdtop">';
		$doleditor = new DolEditor('note_private', $note_private, '', '100', 'dolibarr_notes', 'In', 1, true, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"', 'cols' => '3');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Other attributes
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}

	print "</table>\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	if (is_object($objectsrc)) {
		print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
		print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

		if (!getDolGlobalString('CONTRACT_SUPPORT_PRODUCTS')) {
			print '<br>'.$langs->trans("Note").': '.$langs->trans("OnlyLinesWithTypeServiceAreUsed");
		}
	}

	print "</form>\n";
} else {
	// View and edit mode
	$now = dol_now();

	if ($object->id > 0) {
		$object->fetch_thirdparty();

		$soc = $object->thirdparty; // $soc is used later

		$result = $object->fetch_lines(); // This also init $this->nbofserviceswait, $this->nbofservicesopened, $this->nbofservicesexpired=, $this->nbofservicesclosed
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}

		$nbofservices = count($object->lines);

		$author = new User($db);
		$author->fetch($object->user_author_id);

		$commercial_signature = new User($db);
		$commercial_signature->fetch($object->commercial_signature_id);

		$commercial_suivi = new User($db);
		$commercial_suivi->fetch($object->commercial_suivi_id);

		$head = contract_prepare_head($object);

		$hselected = 0;
		$formconfirm = '';

		print dol_get_fiche_head($head, $hselected, $langs->trans("Contract"), -1, 'contract');


		if ($action == 'delete') {
			//Confirmation de la suppression du contrat
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("DeleteAContract"), $langs->trans("ConfirmDeleteAContract"), "confirm_delete", '', 0, 1);
		} elseif ($action == 'valid') {
			//Confirmation de la validation
			$ref = substr($object->ref, 1, 4);
			if ($ref == 'PROV' && !empty($modCodeContract->code_auto)) {
				$numref = $object->getNextNumRef($object->thirdparty);
			} else {
				$numref = $object->ref;
			}
			$text = $langs->trans('ConfirmValidateContract', $numref);
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("ValidateAContract"), $text, "confirm_valid", '', 0, 1);
		} elseif ($action == 'close') {
			// Confirmation de la fermeture
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("CloseAContract"), $langs->trans("ConfirmCloseContract"), "confirm_close", '', 0, 1);
		} elseif ($action == 'activate') {
			$formquestion = array(
				array('type' => 'date', 'name' => 'd_start', 'label' => $langs->trans("DateServiceActivate"), 'value' => dol_now()),
				array('type' => 'date', 'name' => 'd_end', 'label' => $langs->trans("DateEndPlanned"), /*'value' => $form->selectDate('', "end", $usehm, $usehm, '', "active", 1, 0),*/ 0 => '', 1 => ''),
				array('type' => 'text', 'name' => 'comment', 'label' => $langs->trans("Comment"), 'value' => '', 0 => '', 1 => '', 'class' => 'minwidth300', 'moreattr' => 'autofocus')
			);
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("ActivateAllOnContract"), $langs->trans("ConfirmActivateAllOnContract"), "confirm_activate", $formquestion, 'yes', 1, 280);
		} elseif ($action == 'clone') {
			$filter = '(s.client:IN:1,2,3)';
			// Clone confirmation
			$formquestion = array(array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOSTINT('socid'), 'socid', $filter)));
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneContract', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
		}


		// Call Hook formConfirm
		$parameters = array(
			'formConfirm' => $formconfirm,
			'id' => $id,
			//'lineid' => $lineid,
		);
		// Note that $action and $object may have been modified by hook
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action);
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;


		// Contract
		if ($object->status == $object::STATUS_DRAFT && $user->hasRight('contrat', 'creer')) {
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setremise">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		}

		// Contract card

		$linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


		$morehtmlref = '';
		if (!empty($modCodeContract->code_auto)) {
			$morehtmlref .= $object->ref;
		} else {
			$morehtmlref .= $form->editfieldkey("", 'ref', $object->ref, $object, $user->hasRight('contrat', 'creer'), 'string', '', 0, 3);
			$morehtmlref .= $form->editfieldval("", 'ref', $object->ref, $object, $user->hasRight('contrat', 'creer'), 'string', '', 0, 2);
		}

		$morehtmlref .= '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->hasRight('contrat', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->hasRight('contrat', 'creer'), 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1, 'getFormatedCustomerRef');
		// Ref supplier
		$morehtmlref .= '<br>';
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->hasRight('contrat', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->hasRight('contrat', 'creer'), 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->thirdparty->id.'&search_name='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherContracts").'</a>)';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
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


		print '<table class="border tableforfield" width="100%">';

		// Line info of thirdparty discounts
		print '<tr><td class="titlefield">'.$langs->trans('Discount').'</td><td colspan="3">';
		if ($object->thirdparty->remise_percent) {
			print $langs->trans("CompanyHasRelativeDiscount", $object->thirdparty->remise_percent).'. ';
		} else {
			print '<span class="hideonsmartphone">'.$langs->trans("CompanyHasNoRelativeDiscount").'. </span>';
		}
		$absolute_discount = $object->thirdparty->getAvailableDiscounts();
		if ($absolute_discount) {
			print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->trans("Currency".$conf->currency)).'.';
		} else {
			print '<span class="hideonsmartphone">'.$langs->trans("CompanyHasNoAbsoluteDiscount").'.</span>';
		}
		print '</td></tr>';

		// Date
		print '<tr>';
		print '<td class="titlefield">';
		print $form->editfieldkey("Date", 'date_contrat', $object->date_contrat, $object, $user->hasRight('contrat', 'creer'));
		print '</td><td>';
		print $form->editfieldval("Date", 'date_contrat', $object->date_contrat, $object, $user->hasRight('contrat', 'creer'), 'datehourpicker');
		print '</td>';
		print '</tr>';

		// Other attributes
		$cols = 3;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print "</table>";

		print '</div>';

		if ($object->status == $object::STATUS_DRAFT && $user->hasRight('contrat', 'creer')) {
			print '</form>';
		}

		echo '<br>';

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


		$arrayothercontracts = $object->getListOfContracts('others');	// array or -1 if technical error

		/*
		 * Lines of contracts
		 */

		// Add products/services form
		//$forceall = 1;
		global $inputalsopricewithtax;
		$inputalsopricewithtax = 1;

		$productstatic = new Product($db);

		$usemargins = 0;
		if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'propal', 'commande'))) {
			$usemargins = 1;
		}

		// Title line for service
		$cursorline = 1;


		print '<div id="contrat-lines-container"  id="contractlines" data-contractid="'.$object->id.'"  data-element="'.$object->element.'" >';
		while ($cursorline <= $nbofservices) {
			print '<div id="contrat-line-container'.$object->lines[$cursorline - 1]->id.'" data-contratlineid = "'.$object->lines[$cursorline - 1]->id.'" data-element="'.$object->lines[$cursorline - 1]->element.'" >';
			print '<form name="update" id="addproduct" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="updateline">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			print '<input type="hidden" name="elrowid" value="'.$object->lines[$cursorline - 1]->id.'">';
			print '<input type="hidden" name="fournprice" value="'.(!empty($object->lines[$cursorline - 1]->fk_fournprice) ? $object->lines[$cursorline - 1]->fk_fournprice : 0).'">';

			// Area with common detail of line
			print '<div class="div-table-responsive-no-min">';
			print '<table class="notopnoleftnoright allwidth tableforservicepart1 centpercent">';

			$sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.product_type, cd.description, cd.price_ht, cd.qty,";
			$sql .= " cd.tva_tx, cd.vat_src_code, cd.remise_percent, cd.info_bits, cd.subprice, cd.multicurrency_subprice,";
			$sql .= " cd.date_ouverture_prevue as date_start, cd.date_ouverture as date_start_real,";
			$sql .= " cd.date_fin_validite as date_end, cd.date_cloture as date_end_real,";
			$sql .= " cd.commentaire as comment, cd.fk_product_fournisseur_price as fk_fournprice, cd.buy_price_ht as pa_ht,";
			$sql .= " cd.fk_unit,";
			$sql .= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity, p.tosell, p.tobuy, p.tobatch";
			$sql .= " ,cd.rang";
			$sql .= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
			$sql .= " WHERE cd.rowid = ".((int) $object->lines[$cursorline - 1]->id);

			$result = $db->query($sql);
			if ($result) {
				$total = 0;

				$objp = $db->fetch_object($result);

				// Line title
				print '<tr class="liste_titre'.($cursorline ? ' liste_titre_add' : '').'">';
				print '<td>'.$langs->trans("ServiceNb", $cursorline).'</td>';
				print '<td width="80" class="center">'.$langs->trans("VAT").'</td>';
				print '<td width="80" class="right">'.$langs->trans("PriceUHT").'</td>';
				//if (isModEnabled("multicurrency")) {
				//	print '<td width="80" class="right">'.$langs->trans("PriceUHTCurrency").'</td>';
				//}
				print '<td width="30" class="center">'.$langs->trans("Qty").'</td>';
				if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
					print '<td width="30" class="left">'.$langs->trans("Unit").'</td>';
				}
				print '<td width="50" class="right">'.$langs->trans("ReductionShort").'</td>';
				if (isModEnabled('margin') && getDolGlobalString('MARGIN_SHOW_ON_CONTRACT')) {
					print '<td width="50" class="right">'.$langs->trans("BuyingPrice").'</td>';
				}
				//

				if ($nbofservices > 1 && $conf->browser->layout != 'phone' && $user->hasRight('contrat', 'creer')) {
					print '<td width="30" class="linecolmove tdlineupdown center">';
					if ($cursorline > 1) {
						print '<a class="lineupdown reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=up&token='.newToken().'&rowid='.$objp->rowid.'">';
						echo img_up('default', 0, 'imgupforline');
						print '</a>';
					}
					if ($cursorline < $nbofservices) {
						print '<a class="lineupdown reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=down&token='.newToken().'&rowid='.$objp->rowid.'">';
						echo img_down('default', 0, 'imgdownforline');
						print '</a>';
					}
					print '</td>';
				} else {
					print '<td width="30">&nbsp;</td>';
				}

				print "</tr>\n";



				// Line in view mode
				if ($action != 'editline' || $idline != $objp->rowid) {
					$moreparam = '';
					if (getDolGlobalString('CONTRACT_HIDE_CLOSED_SERVICES_BY_DEFAULT') && $objp->statut == ContratLigne::STATUS_CLOSED && $action != 'showclosedlines') {
						$moreparam = 'style="display: none;"';
					}

					print '<tr class="tdtop oddeven" '.$moreparam.'>';

					// Label
					if ($objp->fk_product > 0) {
						$productstatic->id = $objp->fk_product;
						$productstatic->type = $objp->ptype;
						$productstatic->ref = $objp->pref;
						$productstatic->entity = $objp->pentity;
						$productstatic->label = $objp->plabel;
						$productstatic->status = $objp->tosell;
						$productstatic->status_buy = $objp->tobuy;
						$productstatic->status_batch = $objp->tobatch;

						print '<td>';
						$text = $productstatic->getNomUrl(1, '', 32);
						if ($objp->plabel) {
							$text .= ' - ';
							$text .= $objp->plabel;
						}
						$description = $objp->description;

						// Add description in form
						if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
							$text .= (!empty($objp->description) && $objp->description != $objp->plabel) ? '<br>'.dol_htmlentitiesbr($objp->description) : '';
							$description = ''; // Already added into main visible desc
						}

						print $form->textwithtooltip($text, $description, 3, '', '', $cursorline, 3, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));

						print '</td>';
					} else {
						print '<td>'.img_object($langs->trans("ShowProductOrService"), ($objp->product_type ? 'service' : 'product')).' '.dol_htmlentitiesbr($objp->description)."</td>\n";
					}
					// VAT
					print '<td class="center">';
					print vatrate($objp->tva_tx.($objp->vat_src_code ? (' ('.$objp->vat_src_code.')') : ''), '%', $objp->info_bits);
					print '</td>';
					// Price
					print '<td class="right">'.($objp->subprice != '' ? price($objp->subprice) : '')."</td>\n";
					// Price multicurrency
					/*if (isModEnabled("multicurrency")) {
						print '<td class="linecoluht_currency nowrap right">'.price($objp->multicurrency_subprice).'</td>';
					}*/
					// Quantity
					print '<td class="center">'.$objp->qty.'</td>';
					// Unit
					if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
						print '<td class="left">'.$langs->trans($object->lines[$cursorline - 1]->getLabelOfUnit()).'</td>';
					}
					// Discount
					if ($objp->remise_percent > 0) {
						print '<td class="right">'.$objp->remise_percent."%</td>\n";
					} else {
						print '<td>&nbsp;</td>';
					}

					// Margin
					if (isModEnabled('margin') && getDolGlobalString('MARGIN_SHOW_ON_CONTRACT')) {
						print '<td class="right nowraponall">'.price($objp->pa_ht).'</td>';
					}

					// Icon move, update et delete (status contract 0=draft,1=validated,2=closed)
					print '<td class="nowraponall right">';
					if ($user->hasRight('contrat', 'creer') && is_array($arrayothercontracts) && count($arrayothercontracts) && ($object->status >= 0)) {
						print '<!-- link to move service line into another contract -->';
						print '<a class="reposition marginrightonly" style="padding-left: 5px;" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=move&token='.newToken().'&rowid='.$objp->rowid.'">';
						print img_picto($langs->trans("MoveToAnotherContract"), 'uparrow');
						print '</a>';
					}
					if ($user->hasRight('contrat', 'creer') && ($object->status >= 0)) {
						print '<a class="reposition marginrightonly editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=editline&token='.newToken().'&rowid='.$objp->rowid.'">';
						print img_edit();
						print '</a>';
					}
					if ($user->hasRight('contrat', 'creer') && ($object->status >= 0)) {
						print '<a class="reposition marginrightonly" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=deleteline&token='.newToken().'&rowid='.$objp->rowid.'">';
						print img_delete();
						print '</a>';
					}
					print '</td>';

					print "</tr>\n";

					$colspan = 6;
					if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
						$colspan++;
					}
					if (isModEnabled('margin') && getDolGlobalString('MARGIN_SHOW_ON_CONTRACT')) {
						$colspan++;
					}

					// Dates of service planned and real
					if ($objp->subprice >= 0) {
						print '<tr class="oddeven" '.$moreparam.'>';
						print '<td colspan="'.$colspan.'">';

						// Date planned
						print $langs->trans("DateStartPlanned").': ';
						if ($objp->date_start) {
							print dol_print_date($db->jdate($objp->date_start), 'day');
							// Warning si date prevu passee et pas en service
							if ($objp->statut == 0 && $db->jdate($objp->date_start) < ($now - $conf->contrat->services->inactifs->warning_delay)) {
								$warning_delay = $conf->contrat->services->inactifs->warning_delay / 3600 / 24;
								$textlate = $langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($warning_delay) >= 0 ? '+' : '').ceil($warning_delay).' '.$langs->trans("days");
								print " ".img_warning($textlate);
							}
						} else {
							print $langs->trans("Unknown");
						}
						print ' &nbsp;-&nbsp; ';
						print $langs->trans("DateEndPlanned").': ';
						if ($objp->date_end) {
							print dol_print_date($db->jdate($objp->date_end), 'day');
							if ($objp->statut == 4 && $db->jdate($objp->date_end) < ($now - $conf->contrat->services->expires->warning_delay)) {
								$warning_delay = $conf->contrat->services->expires->warning_delay / 3600 / 24;
								$textlate = $langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($warning_delay) >= 0 ? '+' : '').ceil($warning_delay).' '.$langs->trans("days");
								print " ".img_warning($textlate);
							}
						} else {
							print $langs->trans("Unknown");
						}

						print '</td>';
						print '</tr>';
					}

					// Display lines extrafields
					if (is_array($extralabelslines) && count($extralabelslines) > 0) {
						$line = new ContratLigne($db);
						$line->id = $objp->rowid;
						$line->fetch_optionals();
						print $line->showOptionals($extrafields, 'view', array('class' => 'oddeven', 'style' => $moreparam, 'colspan' => $colspan), '', '', 1);
					}
				} else {
					// Line in mode update
					// Ligne carac
					print '<tr class="oddeven">';
					print '<td>';
					if ($objp->fk_product > 0) {
						$canchangeproduct = 1;
						if (empty($canchangeproduct)) {
							$productstatic->id = $objp->fk_product;
							$productstatic->type = $objp->ptype;
							$productstatic->ref = $objp->pref;
							$productstatic->entity = $objp->pentity;
							print $productstatic->getNomUrl(1, '', 32);
							print $objp->label ? ' - '.dol_trunc($objp->label, 32) : '';
							print '<input type="hidden" name="idprod" value="'.(!empty($object->lines[$cursorline - 1]->fk_product) ? $object->lines[$cursorline - 1]->fk_product : 0).'">';
						} else {
							$senderissupplier = 0;
							if (empty($senderissupplier)) {
								print $form->select_produits((!empty($object->lines[$cursorline - 1]->fk_product) ? $object->lines[$cursorline - 1]->fk_product : 0), 'idprod');
							} else {
								$form->select_produits_fournisseurs((!empty($object->lines[$cursorline - 1]->fk_product) ? $object->lines[$cursorline - 1]->fk_product : 0), 'idprod');
							}
						}
						print '<br>';
					} else {
						print $objp->label ? $objp->label.'<br>' : '';
						print '<input type="hidden" name="idprod" value="'.(!empty($object->lines[$cursorline - 1]->fk_product) ? $object->lines[$cursorline - 1]->fk_product : 0).'">';
					}

					// editeur wysiwyg
					require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
					$nbrows = ROWS_2;
					if (getDolGlobalString('MAIN_INPUT_DESC_HEIGHT')) {
						$nbrows = getDolGlobalString('MAIN_INPUT_DESC_HEIGHT');
					}
					$enable = (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
					$doleditor = new DolEditor('product_desc', $objp->description, '', 92, 'dolibarr_details', '', false, true, $enable, $nbrows, '90%');
					$doleditor->Create();

					print '</td>';

					// VAT
					print '<td class="right">';
					print $form->load_tva("eltva_tx", $objp->tva_tx.($objp->vat_src_code ? (' ('.$objp->vat_src_code.')') : ''), $mysoc, $object->thirdparty, $objp->fk_product, $objp->info_bits, $objp->product_type, 0, 1);
					print '</td>';

					// Price
					print '<td class="right"><input class="width50" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';

					// Price multicurrency
					/*if (isModEnabled("multicurrency")) {
					 print '<td class="linecoluht_currency nowrap right">'.price($objp->multicurrency_subprice).'</td>';
					 }*/

					// Quantity
					print '<td class="center"><input size="2" type="text" name="elqty" value="'.$objp->qty.'"></td>';

					// Unit
					if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
						print '<td class="left">';
						print $form->selectUnits($objp->fk_unit, "unit");
						print '</td>';
					}

					// Discount
					print '<td class="nowrap right"><input size="1" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';

					if (!empty($usemargins)) {
						print '<td class="right">';
						if ($objp->fk_product) {
							print '<select id="fournprice" name="fournprice"></select>';
						}
						print '<input id="buying_price" type="text" class="width50" name="buying_price" value="'.price($objp->pa_ht, 0, '', 0).'"></td>';
					}
					print '<td class="center">';
					print '<input type="submit" class="button margintoponly marginbottomonly" name="save" value="'.$langs->trans("Modify").'">';
					print '<br><input type="submit" class="button margintoponly marginbottomonly button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					print '</td>';
					print '</tr>';

					$colspan = 6;
					if (isModEnabled('margin') && getDolGlobalString('MARGIN_SHOW_ON_CONTRACT')) {
						$colspan++;
					}
					if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
						$colspan++;
					}

					// Line dates planned
					print '<tr class="oddeven">';
					print '<td colspan="'.$colspan.'">';
					print $langs->trans("DateStartPlanned").' ';
					print $form->selectDate($db->jdate($objp->date_start), "date_start_update", $usehm, $usehm, ($db->jdate($objp->date_start) > 0 ? 0 : 1), "update");
					print ' &nbsp;&nbsp;'.$langs->trans("DateEndPlanned").' ';
					print $form->selectDate($db->jdate($objp->date_end), "date_end_update", $usehm, $usehm, ($db->jdate($objp->date_end) > 0 ? 0 : 1), "update");
					print '</td>';
					print '</tr>';

					if (is_array($extralabelslines) && count($extralabelslines) > 0) {
						$line = new ContratLigne($db);
						$line->id = $objp->rowid;
						$line->fetch_optionals();
						print $line->showOptionals($extrafields, 'edit', array('style' => 'class="oddeven"', 'colspan' => $colspan), '', '', 1);
					}
				}

				$db->free($result);
			} else {
				dol_print_error($db);
			}

			if ($object->statut > 0) {
				$moreparam = '';
				if (getDolGlobalString('CONTRACT_HIDE_CLOSED_SERVICES_BY_DEFAULT') && $object->lines[$cursorline - 1]->statut == ContratLigne::STATUS_CLOSED && $action != 'showclosedlines') {
					$moreparam = 'style="display: none;"';
				}

				$colspan = 6;
				if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
					$colspan++;
				}
				if (isModEnabled('margin') && getDolGlobalString('MARGIN_SHOW_ON_CONTRACT')) {
					$colspan++;
				}

				print '<tr class="oddeven" '.$moreparam.'>';
				print '<td class="tdhrthin" colspan="'.$colspan.'"><hr class="opacitymedium tdhrthin"></td>';
				print "</tr>\n";
			}

			print "</table>";
			print '</div>';

			print "</form>\n";


			/*
			 * Confirmation to delete service line of contract
			 */
			if ($action == 'deleteline' && !$cancel && $user->hasRight('contrat', 'creer') && $object->lines[$cursorline - 1]->id == $idline) {
				print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".((int) $object->id)."&lineid=".((int) $idline), $langs->trans("DeleteContractLine"), $langs->trans("ConfirmDeleteContractLine"), "confirm_deleteline", '', 0, 1);
				if ($ret == 'html') {
					print '<table class="notopnoleftnoright centpercent"><tr class="oddeven" height="6"><td></td></tr></table>';
				}
			}

			/*
			 * Confirmation to move service toward another contract
			 */
			if ($action == 'move' && !$cancel && $user->hasRight('contrat', 'creer') && $object->lines[$cursorline - 1]->id == $idline) {
				$arraycontractid = array();
				foreach ($arrayothercontracts as $contractcursor) {
					$arraycontractid[$contractcursor->id] = $contractcursor->ref;
				}
				//var_dump($arraycontractid);
				// Cree un tableau formulaire
				$formquestion = array(
				'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
				0 => array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

				print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".((int) $object->id)."&lineid=".((int) $idline), $langs->trans("MoveToAnotherContract"), $langs->trans("ConfirmMoveToAnotherContract"), "confirm_move", $formquestion, 'yes');
				print '<table class="notopnoleftnoright centpercent"><tr class="oddeven" height="6"><td></td></tr></table>';
			}

			// Area with status and activation info of line
			if ($object->statut > 0) {
				print '<table class="notopnoleftnoright tableforservicepart2'.($cursorline < $nbofservices ? ' boxtablenobottom' : '').' centpercent">';

				print '<tr class="oddeven" '.$moreparam.'>';
				print '<td><span class="valignmiddle hideonsmartphone">'.$langs->trans("ServiceStatus").':</span> '.$object->lines[$cursorline - 1]->getLibStatut(4).'</td>';
				print '<td width="30" class="right">';
				if ($user->socid == 0) {
					if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline') {
						$tmpaction = 'activateline';
						$tmpactionpicto = 'play';
						$tmpactiontext = $langs->trans("Activate");
						if ($objp->statut == 4) {
							$tmpaction = 'unactivateline';
							$tmpactionpicto = 'playstop';
							$tmpactiontext = $langs->trans("Disable");
						}
						if (($tmpaction == 'activateline' && $user->hasRight('contrat', 'activer')) || ($tmpaction == 'unactivateline' && $user->hasRight('contrat', 'desactiver'))) {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline - 1]->id.'&amp;action='.$tmpaction.'">';
							print img_picto($tmpactiontext, $tmpactionpicto);
							print '</a>';
						}
					}
				}
				print '</td>';
				print "</tr>\n";

				print '<tr class="oddeven" '.$moreparam.'>';

				print '<td>';
				// Si pas encore active
				if (!$objp->date_start_real) {
					print $langs->trans("DateStartReal").': ';
					if ($objp->date_start_real) {
						print dol_print_date($db->jdate($objp->date_start_real), 'day');
					} else {
						print $langs->trans("ContractStatusNotRunning");
					}
				}
				// Si active et en cours
				if ($objp->date_start_real && !$objp->date_end_real) {
					print $langs->trans("DateStartReal").': ';
					print dol_print_date($db->jdate($objp->date_start_real), 'day');
				}
				// Si desactive
				if ($objp->date_start_real && $objp->date_end_real) {
					print $langs->trans("DateStartReal").': ';
					print dol_print_date($db->jdate($objp->date_start_real), 'day');
					print ' &nbsp;-&nbsp; ';
					print $langs->trans("DateEndReal").': ';
					print dol_print_date($db->jdate($objp->date_end_real), 'day');
				}
				if (!empty($objp->comment)) {
					print " &nbsp;-&nbsp; ".$objp->comment;
				}
				print '</td>';

				print '<td class="center">&nbsp;</td>';

				print '</tr>';
				print '</table>';
			}

			// Form to activate line
			if ($user->hasRight('contrat', 'activer') && $action == 'activateline' && $object->lines[$cursorline - 1]->id == GETPOSTINT('ligne')) {
				print '<form name="active" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="confirm_active">';
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="ligne" value="'.GETPOSTINT('ligne').'">';
				print '<input type="hidden" name="confirm" value="yes">';

				print '<table class="noborder tableforservicepart2'.($cursorline < $nbofservices ? ' boxtablenobottom' : '').' centpercent">';

				// Definie date debut et fin par default
				$dateactstart = $objp->date_start;
				if (GETPOST('remonth')) {
					$dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
				} elseif (!$dateactstart) {
					$dateactstart = time();
				}

				$dateactend = $objp->date_end;
				if (GETPOST('endmonth')) {
					$dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
				} elseif (!$dateactend) {
					if ($objp->fk_product > 0) {
						$product = new Product($db);
						$product->fetch($objp->fk_product);
						$dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
					}
				}

				print '<tr class="oddeven">';
				print '<td class="nohover">'.$langs->trans("DateServiceActivate").'</td><td class="nohover">';
				print $form->selectDate($dateactstart, 'start', $usehm, $usehm, 0, "active", 1, 0);
				print '</td>';
				print '<td class="nohover">'.$langs->trans("DateEndPlanned").'</td><td class="nohover">';
				print $form->selectDate($dateactend, "end", $usehm, $usehm, 0, "active", 1, 0);
				print '</td>';
				print '<td class="center nohover">';
				print '</td>';

				print '</tr>';

				print '<tr class="oddeven">';
				print '<td class="nohover">'.$langs->trans("Comment").'</td><td colspan="3" class="nohover" colspan="'.(isModEnabled('margin') ? 4 : 3).'"><input type="text" class="minwidth300" name="comment" value="'.dol_escape_htmltag(GETPOST("comment", 'alphanohtml')).'"></td>';
				print '<td class="nohover right">';
				print '<input type="submit" class="button" name="activate" value="'.$langs->trans("Activate").'"> &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
				print '</tr>';

				print '</table>';

				print '</form>';
			}

			if ($user->hasRight('contrat', 'activer') && $action == 'unactivateline' && $object->lines[$cursorline - 1]->id == GETPOSTINT('ligne')) {
				/**
				 * Disable a contract line
				 */
				print '<!-- Form to disabled a line -->'."\n";
				print '<form name="confirm_closeline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline - 1]->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="confirm" value="yes">';
				print '<input type="hidden" name="action" value="confirm_closeline">';
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

				print '<table class="noborder tableforservicepart2'.($cursorline < $nbofservices ? ' boxtablenobottom' : '').' centpercent">';

				// Definie date debut et fin par default
				$dateactstart = $objp->date_start_real;
				if (GETPOST('remonth')) {
					$dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
				} elseif (!$dateactstart) {
					$dateactstart = time();
				}

				$dateactend = $objp->date_end_real;
				if (GETPOST('endmonth')) {
					$dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
				} elseif (!$dateactend) {
					if ($objp->fk_product > 0) {
						$product = new Product($db);
						$product->fetch($objp->fk_product);
						$dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
					}
				}
				$now = dol_now();
				if ($dateactend > $now) {
					$dateactend = $now;
				}

				print '<tr class="oddeven"><td colspan="2" class="nohover">';
				if ($objp->statut >= 4) {
					if ($objp->statut == 4) {
						print $langs->trans("DateEndReal").' ';
						print $form->selectDate($dateactend, "end", $usehm, $usehm, ($objp->date_end_real > 0 ? 0 : 1), "closeline", 1, 1);
					}
				}
				print '</td>';
				print '<td class="center nohover">';
				print '</td></tr>';

				print '<tr class="oddeven">';
				print '<td class="nohover">'.$langs->trans("Comment").'</td><td class="nohover"><input class="quatrevingtpercent" type="text" class="flat" name="comment" value="'.dol_escape_htmltag(GETPOST('comment', 'alpha')).'"></td>';
				print '<td class="nohover right">';
				print '<input type="submit" class="button" name="close" value="'.$langs->trans("Disable").'"> &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
				print '</tr>';

				print '</table>';

				print '</form>';
			}
			print '</div>';
			$cursorline++;
		}
		print '</div>';

		// Form to add new line
		if ($user->hasRight('contrat', 'creer') && ($object->statut == 0)) {
			$dateSelector = 1;

			print "\n";
			print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
			<input type="hidden" name="token" value="'.newToken().'">
			<input type="hidden" name="action" value="'.(($action != 'editline') ? 'addline' : 'updateline').'">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="id" value="'.$object->id.'">
			<input type="hidden" name="page_y" value="">
			<input type="hidden" name="backtopage" value="'.$backtopage.'">
			';

			print '<div class="div-table-responsive-no-min">';
			print '<table id="tablelines" class="noborder noshadow" width="100%">'; // Array with (n*2)+1 lines

			// Form to add new line
			if ($action != 'editline') {
				$forcetoshowtitlelines = 1;
				if (empty($object->multicurrency_code)) {
					$object->multicurrency_code = $conf->currency; // TODO Remove this when multicurrency supported on contracts
				}

				// Add free products/services

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, $soc);
				}
			}

			print '</table>';
			print '</div>';
			print '</form>';
		}

		print dol_get_fiche_end();

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		/*
		 * Buttons
		 */
		if ($user->socid == 0 && $action != 'presend' && $action != 'editline') {
			print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

			if (empty($reshook)) {
				$params = array(
					'attr' => array(
						'title' => '',
						'class' => 'classfortooltip'
					)
				);

				// Send
				if (empty($user->socid)) {
					if ($object->status == $object::STATUS_VALIDATED) {
						if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->hasRight('contrat', 'creer'))) {
							print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle', '', true, $params);
						} else {
							print dolGetButtonAction('', $langs->trans('SendMail'), 'default', '#', '', false, $params);
						}
					}
				}

				if ($object->status == $object::STATUS_DRAFT && $nbofservices) {
					if ($user->hasRight('contrat', 'creer')) {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid&token='.newToken(), '', true, $params);
					} else {
						$params['attr']['title'] = $langs->trans("NotEnoughPermissions");
						print dolGetButtonAction($langs->trans('Validate'), '', 'default', '#', '', false, $params);
					}
				}
				if ($object->status == $object::STATUS_VALIDATED) {
					if ($user->hasRight('contrat', 'creer')) {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken(), '', true, $params);
					} else {
						$params['attr']['title'] = $langs->trans("NotEnoughPermissions");
						print dolGetButtonAction($langs->trans('Modify'), '', 'default', '#', '', false, $params);
					}
				}

				// Create ... buttons
				$arrayofcreatebutton = array();
				if (isModEnabled('order') && $object->status > 0 && $object->nbofservicesclosed < $nbofservices) {
					$arrayofcreatebutton[] = array(
						'url' => '/commande/card.php?action=create&token='.newToken().'&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->thirdparty->id,
						'label' => $langs->trans('AddOrder'),
						'lang' => 'orders',
						'perm' => $user->hasRight('commande', 'creer')
					);
				}
				if (isModEnabled('invoice') && $object->status > 0 && $soc->client > 0) {
					$arrayofcreatebutton[] = array(
						'url' => '/compta/facture/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->thirdparty->id,
						'label' => $langs->trans('CreateBill'),
						'lang' => 'bills',
						'perm' => $user->hasRight('facture', 'creer')
					);
				}
				if (isModEnabled('supplier_invoice') && $object->status > 0 && $soc->fournisseur == 1) {
					$arrayofcreatebutton[] = array(
						'url' => '/fourn/facture/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->thirdparty->id,
						'label' => $langs->trans('AddSupplierInvoice'),
						'lang' => 'bills',
						'perm' => $user->hasRight('fournisseur', 'facture', 'creer')
					);
				}
				if (count($arrayofcreatebutton)) {
					unset($params['attr']['title']);
					print dolGetButtonAction('', $langs->trans("Create"), 'default', $arrayofcreatebutton, '', true, $params);
				}

				if ($object->nbofservicesclosed > 0 || $object->nbofserviceswait > 0) {
					if ($user->hasRight('contrat', 'activer')) {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('ActivateAllContracts'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=activate&token='.newToken(), '', true, $params);
					} else {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('ActivateAllContracts'), '', 'default', '#', '', false, $params);
					}
				}
				if ($object->nbofservicesclosed < $nbofservices) {
					if ($user->hasRight('contrat', 'desactiver')) {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('CloseAllContracts'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=close&token='.newToken(), '', true, $params);
					} else {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('CloseAllContracts'), '', 'default', '#', '', false, $params);
					}

					//if (! $numactive)
					//{
					//}
					//else
					//{
					//	print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("CloseRefusedBecauseOneServiceActive").'">'.$langs->trans("Close").'</a></div>';
					//}
				}

				if (getDolGlobalString('CONTRACT_HIDE_CLOSED_SERVICES_BY_DEFAULT') && $object->nbofservicesclosed > 0) {
					if ($action == 'showclosedlines') {
						print '<div class="inline-block divButAction"><a class="butAction" id="btnhideclosedlines" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=hideclosedlines">'.$langs->trans("HideClosedServices").'</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butAction" id="btnshowclosedlines" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=showclosedlines">'.$langs->trans("ShowClosedServices").'</a></div>';
					}
				}

				// Clone
				if ($user->hasRight('contrat', 'creer')) {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken(), '', true, $params);
				}

				// Delete
				unset($params['attr']['title']);
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete, $params);
			}

			print "</div>";
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';

			/*
			 * Generated documents
			 */
			$filename = dol_sanitizeFileName($object->ref);
			$filedir = $conf->contrat->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->hasRight('contrat', 'lire');
			$delallowed = $user->hasRight('contrat', 'creer');


			print $formfile->showdocuments('contract', $filename, $filedir, $urlsource, $genallowed, $delallowed, ($object->model_pdf ? $object->model_pdf : getDolGlobalString('CONTRACT_ADDON_PDF')), 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang, '', $object);


			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, array('contrat'));
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			// Show online signature link
			if ($object->statut != Contrat::STATUS_DRAFT && getDolGlobalString('CONTRACT_ALLOW_ONLINESIGN')) {
				print '<br><!-- Link to sign -->';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';

				print showOnlineSignatureUrl('contract', $object->ref).'<br>';
			}

			print '</div><div class="fichehalfright">';

			$MAXEVENT = 10;

			$morehtmlcenter = '<div class="nowraponall">';
			$morehtmlcenter .= dolGetButtonTitle($langs->trans('FullConversation'), '', 'fa fa-comments imgforviewmode', DOL_URL_ROOT.'/contrat/messaging.php?id='.$object->id);
			$morehtmlcenter .= dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/contrat/agenda.php?id='.$object->id);
			$morehtmlcenter .= '</div>';


			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'contract', $socid, 1, 'listactions', $MAXEVENT, '', $morehtmlcenter);

			print '</div></div>';
		}

		// Presend form
		$modelmail = 'contract';
		$defaulttopic = 'SendContractRef';
		$diroutput = $conf->contrat->multidir_output[$object->entity];
		$trackid = 'con'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}


llxFooter();

$db->close();

if (isModEnabled('margin') && $action == 'editline') {
	// TODO Why this ? To manage margin on contracts ?
	print "\n".'<script type="text/javascript">'."\n";
	?>
	$(document).ready(function() {
	  var idprod = $("input[name='idprod']").val();
	  var fournprice = $("input[name='fournprice']").val();
	  var token = '<?php echo currentToken(); ?>';		// For AJAX Call we use old 'token' and not 'newtoken'
	  if (idprod > 0) {
		  $.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {
			  'idprod': idprod,
			  'token': token
			  }, function(data) {
			if (data.length > 0) {
			  var options = '';
			  var trouve=false;
			  $(data).each(function() {
				options += '<option value="'+this.id+'" price="'+this.price+'"';
				if (fournprice > 0) {
					if (this.id == fournprice) {
					  options += ' selected';
					  $("#buying_price").val(this.price);
					  trouve = true;
					}
				}
				options += '>'+this.label+'</option>';
			  });
			  options += '<option value=null'+(trouve?'':' selected')+'><?php echo $langs->trans("InputPrice"); ?></option>';
			  $("#fournprice").html(options);
			  if (trouve) {
				$("#buying_price").hide();
				$("#fournprice").show();
			  }
			  else {
				$("#buying_price").show();
			  }
			  $("#fournprice").change(function() {
				var selval = $(this).find('option:selected').attr("price");
				if (selval)
				  $("#buying_price").val(selval).hide();
				else
				  $('#buying_price').show();
			  });
			}
			else {
			  $("#fournprice").hide();
			  $('#buying_price').show();
			}
		  },
		  'json');
		}
		else {
		  $("#fournprice").hide();
		  $('#buying_price').show();
		}
	});
<?php
	print "\n".'<script type="text/javascript">'."\n";
}
