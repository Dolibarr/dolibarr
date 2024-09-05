<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2023	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2023	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador      	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022	    Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023-2024	Benjamin Falière		<benjamin.faliere@altairis.fr>
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
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   \file      htdocs/commande/card.php
 *   \ingroup   commande
 *   \brief     Page to show sales order
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}

if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

if (isModEnabled('variants')) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}


// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings', 'companies', 'bills', 'propal', 'deliveries', 'products', 'other'));

if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}
if (isModEnabled('margin')) {
	$langs->load('margins');
}
if (isModEnabled('productbatch')) {
	$langs->load('productbatch');
}


$id        = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('orderid'));
$ref       =  GETPOST('ref', 'alpha');
$socid     =  GETPOSTINT('socid');
$action    =  GETPOST('action', 'aZ09');
$cancel    =  GETPOST('cancel', 'alpha');
$confirm   =  GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$lineid    =  GETPOSTINT('lineid');
$contactid =  GETPOSTINT('contactid');
$projectid =  GETPOSTINT('projectid');
$origin    =  GETPOST('origin', 'alpha');
$originid  = (GETPOSTINT('originid') ? GETPOSTINT('originid') : GETPOSTINT('origin_id'));    // For backward compatibility
$rank      = (GETPOSTINT('rank') > 0) ? GETPOSTINT('rank') : -1;

// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ordercard', 'globalcard'));

$result = restrictedArea($user, 'commande', $id);

$object = new Commande($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';     // Must be 'include', not 'include_once'

// Permissions / Rights
$usercanread    =  $user->hasRight("commande", "lire");
$usercancreate  =  $user->hasRight("commande", "creer");
$usercandelete  =  $user->hasRight("commande", "supprimer");

// Advanced permissions
$usercanclose       =  ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !empty($usercancreate)) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'order_advance', 'close')));
$usercanvalidate    =  ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'order_advance', 'validate')));
$usercancancel      =  ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'order_advance', 'annuler')));
$usercansend        =   (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->hasRight('commande', 'order_advance', 'send'));
$usercangeneretedoc =   (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->hasRight('commande', 'order_advance', 'generetedoc'));

$usermustrespectpricemin    = ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('produit', 'ignore_price_min_advance')) || !getDolGlobalString('MAIN_USE_ADVANCED_PERMS'));
$usercancreatepurchaseorder = ($user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer'));

$permissionnote    = $usercancreate;     //  Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate;     //  Used by the include of actions_dellink.inc.php
$permissiontoadd   = $usercancreate;     //  Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php


$error = 0;

$date_delivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));


/*
 * Actions
 */

$parameters = array('socid' => $socid);
// Note that $action and $object may be modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/commande/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/commande/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$selectedLines = GETPOST('toselect', 'array');

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

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';    // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';     // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';  // Must be 'include', not 'include_once'

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate) {
		if (!($socid > 0)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('IdThirdParty')), null, 'errors');
		} else {
			if ($object->id > 0) {
				// Because createFromClone modifies the object, we must clone it so that we can restore it later
				$orig = clone $object;

				$result = $object->createFromClone($user, $socid);
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
					exit;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$object = $orig;
					$action = '';
				}
			}
		}
	} elseif ($action == 'reopen' && $usercancreate) {
		// Reopen a closed order
		if ($object->statut == Commande::STATUS_CANCELED || $object->statut == Commande::STATUS_CLOSED) {
			if (getDolGlobalInt('ORDER_REOPEN_TO_DRAFT')) {
				$result = $object->setDraft($user, $idwarehouse);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$result = $object->set_reopen($user);
				if ($result > 0) {
					setEventMessages($langs->trans('OrderReopened', $object->ref), null);
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete) {
		// Remove order
		$result = $object->delete($user);
		if ($result > 0) {
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		// Remove a product line
		$result = $object->deleteLine($user, $lineid);
		if ($result > 0) {
			// reorder lines
			$object->line_order(true);
			// Define output language
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
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'classin' && $usercancreate) {
		// Link to a project
		$object->setProject(GETPOSTINT('projectid'));
	} elseif ($action == 'add' && $usercancreate) {
		// Add order
		$datecommande = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
		$date_delivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));

		if ($datecommande == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Date')), null, 'errors');
			$action = 'create';
			$error++;
		}

		if ($socid < 1) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error) {
			$object->socid = $socid;
			$object->fetch_thirdparty();

			$db->begin();

			$object->date_commande = $datecommande;
			$object->note_private = GETPOST('note_private', 'restricthtml');
			$object->note_public = GETPOST('note_public', 'restricthtml');
			$object->source = GETPOSTINT('source_id');
			$object->fk_project = GETPOSTINT('projectid');
			$object->ref_client = GETPOST('ref_client', 'alpha');
			$object->model_pdf = GETPOST('model');
			$object->cond_reglement_id = GETPOSTINT('cond_reglement_id');
			$object->deposit_percent = GETPOSTFLOAT('cond_reglement_id_deposit_percent');
			$object->mode_reglement_id = GETPOSTINT('mode_reglement_id');
			$object->fk_account = GETPOSTINT('fk_account');
			$object->availability_id = GETPOSTINT('availability_id');
			$object->demand_reason_id = GETPOSTINT('demand_reason_id');
			$object->delivery_date = $date_delivery;
			$object->shipping_method_id = GETPOSTINT('shipping_method_id');
			$object->warehouse_id = GETPOSTINT('warehouse_id');
			$object->fk_delivery_address = GETPOSTINT('fk_address');
			$object->contact_id = GETPOSTINT('contactid');
			$object->fk_incoterms = GETPOSTINT('incoterm_id');
			$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
			$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
			$object->multicurrency_tx = (float) price2num(GETPOST('originmulticurrency_tx'));
			// Fill array 'array_options' with data from add form
			if (!$error) {
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
				}
			}

			// If creation from another object of another module (Example: origin=propal, originid=1)
			if (!empty($origin) && !empty($originid)) {
				// Parse element/subelement (ex: project_task)
				$element = $subelement = $origin;
				$regs = array();
				if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
					$element = $regs [1];
					$subelement = $regs [2];
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

				$object->origin = $origin;
				$object->origin_id = $originid;

				// Possibility to add external linked objects with hooks
				$object->linked_objects [$object->origin] = $object->origin_id;
				$other_linked_objects = GETPOST('other_linked_objects', 'array');
				if (!empty($other_linked_objects)) {
					$object->linked_objects = array_merge($object->linked_objects, $other_linked_objects);
				}

				if (!$error) {
					$object_id = $object->create($user);

					if ($object_id > 0) {
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
								if (!in_array($lines[$i]->id, $selectedLines)) {
									continue; // Skip unselected lines
								}

								$label = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : '');
								$product_type = (!empty($lines[$i]->product_type) ? $lines[$i]->product_type : 0);

								// Dates
								// TODO mutualiser
								$date_start = $lines[$i]->date_debut_prevue;
								if ($lines[$i]->date_debut_reel) {
									$date_start = $lines[$i]->date_debut_reel;
								}
								if ($lines[$i]->date_start) {
									$date_start = $lines[$i]->date_start;
								}
								$date_end = $lines[$i]->date_fin_prevue;
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
								if (method_exists($lines[$i], 'fetch_optionals')) { // For avoid conflicts if trigger used
									$lines[$i]->fetch_optionals();
									$array_options = $lines[$i]->array_options;
								}

								$tva_tx = $lines[$i]->tva_tx;
								if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) {
									$tva_tx .= ' ('.$lines[$i]->vat_src_code.')';
								}

								$result = $object->addline(
									$desc,
									$lines[$i]->subprice,
									$lines[$i]->qty,
									$tva_tx,
									$lines[$i]->localtax1_tx,
									$lines[$i]->localtax2_tx,
									$lines[$i]->fk_product,
									$lines[$i]->remise_percent,
									$lines[$i]->info_bits,
									$lines[$i]->fk_remise_except,
									'HT',
									0,
									$date_start,
									$date_end,
									$product_type,
									$lines[$i]->rang,
									$lines[$i]->special_code,
									$fk_parent_line,
									$lines[$i]->fk_fournprice,
									$lines[$i]->pa_ht,
									$label,
									$array_options,
									$lines[$i]->fk_unit,
									$object->origin,
									$lines[$i]->rowid
								);

								if ($result < 0) {
									$error++;
									break;
								}

								// Defined the new fk_parent_line
								if ($result > 0 && $lines[$i]->product_type == 9) {
									$fk_parent_line = $result;
								}
							}
						} else {
							setEventMessages($srcobject->error, $srcobject->errors, 'errors');
							$error++;
						}

						// Now we create same links to contact than the ones found on origin object
						/* Useless, already into the create
						if (!empty($conf->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN))
						{
							$originforcontact = $object->origin;
							$originidforcontact = $object->origin_id;
							if ($originforcontact == 'shipping')     // shipment and order share the same contacts. If creating from shipment we take data of order
							{
								$originforcontact=$srcobject->origin;
								$originidforcontact=$srcobject->origin_id;
							}
							$sqlcontact = "SELECT code, fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
							$sqlcontact.= " WHERE element_id = ".((int) $originidforcontact)." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$db->escape($originforcontact)."'";

							$resqlcontact = $db->query($sqlcontact);
							if ($resqlcontact)
							{
								while($objcontact = $db->fetch_object($resqlcontact))
								{
									//print $objcontact->code.'-'.$objcontact->fk_socpeople."\n";
									$object->add_contact($objcontact->fk_socpeople, $objcontact->code);
								}
							}
							else dol_print_error($resqlcontact);
						}*/

						// Hooks
						$parameters = array('objFrom' => $srcobject);
						// Note that $action and $object may be modified by hook
						$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action);
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
							$error++;
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				} else {
					// Required extrafield left blank, error message already defined by setOptionalsFromPost()
					$action = 'create';
				}
			} else {
				if (!$error) {
					$object_id = $object->create($user);
				}
			}

			// Insert default contacts if defined
			if ($object_id > 0) {
				if (GETPOSTINT('contactid')) {
					$result = $object->add_contact(GETPOSTINT('contactid'), 'CUSTOMER', 'external');
					if ($result < 0) {
						setEventMessages($langs->trans("ErrorFailedToAddContact"), null, 'errors');
						$error++;
					}
				}

				$id = $object_id;
				$action = '';
			}

			// End of object creation, we show it
			if ($object_id > 0 && !$error) {
				$db->commit();
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object_id);
				exit();
			} else {
				$db->rollback();
				$action = 'create';
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'classifybilled' && $usercancreate) {
		$ret = $object->classifyBilled($user);

		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'classifyunbilled' && $usercancreate) {
		$ret = $object->classifyUnBilled($user);
		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setref_client' && $usercancreate) {
		// Positionne ref commande client
		$result = $object->set_ref_client($user, GETPOST('ref_client'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setremise' && $usercancreate) {
		$result = $object->setDiscount($user, price2num(GETPOST('remise'), 2));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setabsolutediscount' && $usercancreate) {
		if (GETPOST('remise_id')) {
			if ($object->id > 0) {
				$object->insert_discount(GETPOST('remise_id'));
			} else {
				dol_print_error($db, $object->error);
			}
		}
	} elseif ($action == 'setdate' && $usercancreate) {
		$date = dol_mktime(0, 0, 0, GETPOSTINT('order_month'), GETPOSTINT('order_day'), GETPOSTINT('order_year'));

		$result = $object->set_date($user, $date);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setdate_livraison' && $usercancreate) {
		$date_delivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));

		$object->fetch($id);
		$result = $object->setDeliveryDate($user, $date_delivery);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setmode' && $usercancreate) {
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		// Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOSTINT('calculation_mode'));
	} elseif ($action == 'setavailability' && $usercancreate) {
		$result = $object->availability(GETPOST('availability_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setdemandreason' && $usercancreate) {
		$result = $object->demand_reason(GETPOST('demand_reason_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setconditions' && $usercancreate) {
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'), GETPOSTFLOAT('cond_reglement_id_deposit_percent'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		} else {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	} elseif ($action == 'set_incoterms' && isModEnabled('incoterm') && $usercancreate) {
		// Set incoterm
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTFLOAT('location_incoterms'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setbankaccount' && $usercancreate) {
		// bank account
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setshippingmethod' && $usercancreate) {
		// shipping method
		$result = $object->setShippingMethod(GETPOSTINT('shipping_method_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setwarehouse' && $usercancreate) {
		// warehouse
		$result = $object->setWarehouse(GETPOSTINT('warehouse_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		//} elseif ($action == 'setremisepercent' && $usercancreate) {
		//	$result = $object->setDiscount($user, price2num(GETPOST('remise_percent'), '', 2));
		//} elseif ($action == 'setremiseabsolue' && $usercancreate) {
		//	$result = $object->set_remise_absolue($user, price2num(GETPOST('remise_absolue'), 'MU', 2));
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'aZ09') && (GETPOST('alldate_start', 'alpha') || GETPOST('alldate_end', 'alpha')) && $usercancreate) {
		// Define date start and date end for all line
		$alldate_start = dol_mktime(GETPOST('alldate_starthour'), GETPOST('alldate_startmin'), 0, GETPOST('alldate_startmonth'), GETPOST('alldate_startday'), GETPOST('alldate_startyear'));
		$alldate_end = dol_mktime(GETPOST('alldate_endhour'), GETPOST('alldate_endmin'), 0, GETPOST('alldate_endmonth'), GETPOST('alldate_endday'), GETPOST('alldate_endyear'));
		foreach ($object->lines as $line) {
			if ($line->product_type == 1) { // only service line
				$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $alldate_start, $alldate_end, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
			}
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'alpha') && GETPOST('vatforalllines', 'alpha') !== '' && $usercancreate) {
		// Define vat_rate
		$vat_rate = (GETPOST('vatforalllines') ? GETPOST('vatforalllines') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		foreach ($object->lines as $line) {
			$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'alpha') && GETPOST('remiseforalllines', 'alpha') !== '' && $usercancreate) {
		// Define remise_percent
		$remise_percent = (GETPOST('remiseforalllines') ? GETPOST('remiseforalllines') : 0);
		$remise_percent = str_replace('*', '', $remise_percent);
		foreach ($object->lines as $line) {
			$tvatx = $line->tva_tx;
			if (!empty($line->vat_src_code)) {
				$tvatx .= ' ('.$line->vat_src_code.')';
			}
			$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $remise_percent, $tvatx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
		}
	}  elseif (	$action == 'addline' && (GETPOST('submitforallmargins', 'alpha')
				&& GETPOST('marginforalllines') !== '' && $usercancreate) ||
				(GETPOST('submitforallmark', 'alpha')
				&& GETPOST('markforalllines') !== '' && $usercancreate)) {
		$outlangs = $langs;
		// Define margin
		$margin_rate = GETPOSTISSET('marginforalllines') ? GETPOST('marginforalllines', 'int') : '';
		$mark_rate = GETPOSTISSET('markforalllines') ? GETPOST('markforalllines', 'int') : '';
		foreach ($object->lines as &$line) {
			$subprice_multicurrency = $line->subprice;
			if (is_numeric($margin_rate) && $margin_rate > 0) {
				$line->subprice = price2num($line->pa_ht * (1 + $margin_rate / 100), 'MU');
			} elseif (is_numeric($mark_rate) && $mark_rate > 0) {
				$line->subprice = $line->pa_ht / (1 - ($mark_rate / 100));
			} else {
				$line->subprice = $line->pa_ht;
			}

			$prod = new Product($db);
			$res = $prod->fetch($line->fk_product);
			if ($res > 0 && $prod->price_min > $line->subprice) {
				$price_subprice  = price($line->subprice, 0, $outlangs, 1, -1, -1, 'auto');
				$price_price_min = price($prod->price_min, 0, $outlangs, 1, -1, -1, 'auto');
				setEventMessages($prod->ref.' - '.$prod->label.' ('.$price_subprice.' < '.$price_price_min.' '.strtolower($langs->trans("MinPrice")).')'."\n", null, 'warnings');
			}else{
				setEventMessages($prod->error, $prod->errors, 'errors');
			}
			// Manage $line->subprice and $line->multicurrency_subprice
			$multicurrency_subprice = (float) $line->subprice * $line->multicurrency_subprice / $subprice_multicurrency;
			// Update DB
			$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_rate, $line->localtax2_rate, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->product_ref, $line->special_code, $line->array_options, $line->fk_unit, $multicurrency_subprice);
			// Update $object with new margin info
			if ($result > 0) {
				if (is_numeric($margin_rate) && empty($mark_rate)) {
					$line->marge_tx = $margin_rate;
				}elseif (is_numeric($mark_rate) && empty($margin_rate)) {
					$line->marque_tx = $mark_rate;
				}
				$line->total_ht = $line->qty * (float) $line->subprice;
				$line->total_tva = $line->tva_tx * $line->qty * (float) $line->subprice;
				$line->total_ttc = (1 + $line->tva_tx) * $line->qty * (float) $line->subprice;
				// Manage $line->subprice and $line->multicurrency_subprice
				$line->multicurrency_total_ht = $line->qty * (float) $subprice_multicurrency* $line->multicurrency_subprice / $line->subprice;
				$line->multicurrency_total_tva = $line->tva_tx * $line->qty * (float) $subprice_multicurrency * $line->multicurrency_subprice / $line->subprice;
				$line->multicurrency_total_ttc = (1 + $line->tva_tx) * $line->qty * (float) $subprice_multicurrency * $line->multicurrency_subprice / $line->subprice;
				// Used previous $line->subprice and $line->multicurrency_subprice above, now they can be set to their new values
				$line->multicurrency_subprice = $multicurrency_subprice;
			}else{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

	} elseif ($action == 'addline' && !GETPOST('submitforalllines', 'alpha') && $usercancreate) {		// Add a new line
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');

		$price_ht = '';
		$price_ht_devise = '';
		$price_ttc = '';
		$price_ttc_devise = '';
		$pu_ht = '';
		$pu_ttc = '';
		$pu_ht_devise = '';
		$pu_ttc_devise  = '';

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

		if ((empty($idprod) || $idprod < 0) && ($price_ht < 0) && ($qty < 0)) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && GETPOST('type') < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && $price_ht === '' && $price_ht_devise === '' && $price_ttc === '' && $price_ttc_devise === '') { 	// Unit price can be 0 but not ''. Also price can be negative for order.
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
			$error++;
		}
		if ($qty == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($qty < 0) {
			setEventMessages($langs->trans('FieldCannotBeNegative', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && empty($product_desc)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
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

		if (!$error && ($qty >= 0) && (!empty($product_desc) || (!empty($idprod) && $idprod > 0))) {
			// Clean parameters
			$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

			$price_min = $price_min_ttc = 0;
			$tva_npr = 0;

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $base_price_type par celui du produit
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

				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_min = $prod->price_min;
				$price_min_ttc = $prod->price_min_ttc;
				$price_base_type = $prod->price_base_type;

				// If price per segment
				if (getDolGlobalString('PRODUIT_MULTIPRICES') && !empty($object->thirdparty->price_level)) {
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
					if ($result >= 0) {
						if (count($prodcustprice->lines) > 0) {
							$pu_ht = price($prodcustprice->lines[0]->price);
							$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
							$price_min =  price($prodcustprice->lines[0]->price_min);
							$price_min_ttc =  price($prodcustprice->lines[0]->price_min_ttc);
							$price_base_type = $prodcustprice->lines[0]->price_base_type;
							$tva_tx = $prodcustprice->lines[0]->tva_tx;
							if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) {
								$tva_tx .= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
							}
							$tva_npr = $prodcustprice->lines[0]->recuperableonly;
							if (empty($tva_tx)) {
								$tva_npr = 0;
							}
						}
					} else {
						setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
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
					if ($prod->prices_by_qty[$object->thirdparty->price_level]) {	// yes, this product has some prices per quantity
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

				$tmpvat = (float) price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = (float) price2num(preg_replace('/\s*\(.*\)/', '', (string) $prod->tva_tx));

				// Set unit price to use
				if (!empty($price_ht) || $price_ht === '0') {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num((float) $pu_ht * (1 + ($tmpvat / 100)), 'MU');
				} elseif (!empty($price_ttc) || $price_ttc === '0') {
					$pu_ttc = price2num($price_ttc, 'MU');
					$pu_ht = price2num((float) $pu_ttc / (1 + ($tmpvat / 100)), 'MU');
				} elseif ($tmpvat != $tmpprodvat) {
					// Is this still used ?
					if ($price_base_type != 'HT') {
						$pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
					} else {
						$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
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

				//If text set in desc is the same as product descpription (as now it's preloaded) we add it only one time
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
				$tva_tx = str_replace('*', '', $tva_tx);
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}
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
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

			// Margin
			$fournprice = price2num(GETPOST('fournprice'.$predef) ? GETPOST('fournprice'.$predef) : '');
			$buyingprice = price2num(GETPOST('buying_price'.$predef) != '' ? GETPOST('buying_price'.$predef) : ''); // If buying_price is '0', we must keep this value

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

			// TODO $pu_equivalent or $pu_equivalent_ttc must be calculated from the one defined
			/*
			if ($pu_equivalent) {
				$tmp = calcul_price_total(1, $pu_equivalent, 0, $tva_tx, -1, -1, 0, 'HT', $info_bits, $type);
				$pu_equivalent_ttc = ...
			} else {
				$tmp = calcul_price_total(1, $pu_equivalent_ttc, 0, $tva_tx, -1, -1, 0, 'TTC', $info_bits, $type);
				$pu_equivalent_ht = ...
			}
			*/

			$desc = dol_htmlcleanlastbr($desc);

			// Check price is not lower than minimum
			if ($usermustrespectpricemin) {
				if ($pu_equivalent && $price_min && (((float) price2num($pu_equivalent) * (1 - $remise_percent / 100)) < (float) price2num($price_min)) && $price_base_type == 'HT') {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
				} elseif ($pu_equivalent_ttc && $price_min_ttc && (((float) price2num($pu_equivalent_ttc) * (1 - $remise_percent / 100)) < (float) price2num($price_min_ttc)) && $price_base_type == 'TTC') {
					$mesg = $langs->trans("CantBeLessThanMinPriceInclTax", price(price2num($price_min_ttc, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
				}
			}

			if (!$error) {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $info_bits, 0, $price_base_type, $pu_ttc, $date_start, $date_end, $type, min($rank, count($object->lines) + 1), 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $fk_unit, '', 0, $pu_ht_devise);

				if ($result > 0) {
					$ret = $object->fetch($object->id); // Reload to get new records
					$object->fetch_thirdparty();

					if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
						// Define output language
						$outputlangs = $langs;
						$newlang = GETPOST('lang_id', 'alpha');
						if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
							$newlang = $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
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
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && GETPOST('save')) {
		// Update a line
		// Clean parameters
		$date_start = '';
		$date_end = '';
		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

		$description = dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml'));

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', GETPOST('tva_tx'))) {
			$info_bits |= 0x01;
		}

		// Define vat_rate
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx', 'alpha') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		$pu_ht = price2num(GETPOST('price_ht'), '', 2);
		$pu_ttc = price2num(GETPOST('price_ttc'), '', 2);

		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), '', 2);
		$pu_ttc_devise = price2num(GETPOST('multicurrency_subprice_ttc'), '', 2);

		$qty = price2num(GETPOST('qty', 'alpha'), 'MS');

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

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we must keep this value

		// Extrafields Lines
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		// Unset extrafield POST Data
		if (is_array($extralabelsline)) {
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}

		// Define special_code for special lines
		$special_code = GETPOST('special_code');
		if (!GETPOST('qty')) {
			$special_code = 3;
		}

		$remise_percent = GETPOST('remise_percent') != '' ? price2num(GETPOST('remise_percent'), '', 2) : 0;

		// Check minimum price
		$productid = GETPOSTINT('productid');
		if (!empty($productid)) {
			$product = new Product($db);
			$product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if ((getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) && !empty($object->thirdparty->price_level)) {
				$price_min = $product->multiprices_min[$object->thirdparty->price_level];
			}
			$price_min_ttc = $product->price_min_ttc;
			if ((getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) && !empty($object->thirdparty->price_level)) {
				$price_min_ttc = $product->multiprices_min_ttc[$object->thirdparty->price_level];
			}

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			// Check price is not lower than minimum
			if ($usermustrespectpricemin) {
				if ($pu_equivalent && $price_min && (((float) price2num($pu_equivalent) * (1 - $remise_percent / 100)) < (float) price2num($price_min)) && $price_base_type == 'HT') {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
					$action = 'editline';
				} elseif ($pu_equivalent_ttc && $price_min_ttc && (((float) price2num($pu_equivalent_ttc) * (1 - $remise_percent / 100)) < (float) price2num($price_min_ttc)) && $price_base_type == 'TTC') {
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
				$action = 'editline';
			}
		}

		if ($qty < 0) {
			setEventMessages($langs->trans('FieldCannotBeNegative', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
			$action = 'editline';
		}

		if (!$error) {
			if (!$user->hasRight('margins', 'creer')) {
				foreach ($object->lines as &$line) {
					if ($line->id == GETPOSTINT('lineid')) {
						$fournprice = $line->fk_fournprice;
						$buyingprice = $line->pa_ht;
						break;
					}
				}
			}

			$price_base_type = 'HT';
			$pu = $pu_ht;
			if (empty($pu) && !empty($pu_ttc)) {
				$pu = $pu_ttc;
				$price_base_type = 'TTC';
			}

			$result = $object->updateline(GETPOSTINT('lineid'), $description, $pu, $qty, $remise_percent, $vat_rate, $localtax1_rate, $localtax2_rate, $price_base_type, $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('units'), $pu_ht_devise);

			if ($result >= 0) {
				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
					// Define output language
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

					$ret = $object->fetch($object->id); // Reload to get new records
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
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && GETPOST('cancel', 'alpha')) {
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); //  To re-display card in edit mode
		exit();
	} elseif ($action == 'confirm_validate' && $confirm == 'yes' && $usercanvalidate) {
		$idwarehouse = GETPOSTINT('idwarehouse');

		$qualified_for_stock_change = 0;
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') && $qualified_for_stock_change) {
			if (!$idwarehouse || $idwarehouse == -1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (!$error) {
			$locationTarget = '';

			$db->begin();

			$result = $object->valid($user, $idwarehouse);
			if ($result >= 0) {
				$error = 0;
				$deposit = null;

				$deposit_percent_from_payment_terms = (float) getDictionaryValue('c_payment_term', 'deposit_percent', $object->cond_reglement_id);

				if (
					GETPOST('generate_deposit', 'alpha') == 'on' && !empty($deposit_percent_from_payment_terms)
					&& isModEnabled('invoice') && $user->hasRight('facture', 'creer')
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
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				// Define output language
				if (! $error) {
					$db->commit();

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

						if ($deposit) {
							$deposit->fetch($deposit->id); // Reload to get new records
							$deposit->generateDocument($deposit->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
						}
					}

					if ($locationTarget) {
						header('Location: ' . $locationTarget);
						exit;
					}
				} else {
					$db->rollback();
				}
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'confirm_modif' && $usercancreate) {
		// Go back to draft status
		$idwarehouse = GETPOST('idwarehouse');

		$qualified_for_stock_change = 0;
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') && $qualified_for_stock_change) {
			if (!$idwarehouse || $idwarehouse == -1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (!$error) {
			$result = $object->setDraft($user, $idwarehouse);
			if ($result >= 0) {
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
		}
	} elseif ($action == 'confirm_shipped' && $confirm == 'yes' && $usercanclose) {
		$result = $object->cloture($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_cancel' && $confirm == 'yes' && $usercanvalidate) {
		$idwarehouse = GETPOSTINT('idwarehouse');

		$qualified_for_stock_change = 0;
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') && $qualified_for_stock_change) {
			if (!$idwarehouse || $idwarehouse == -1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (!$error) {
			$result = $object->cancel($idwarehouse);

			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'update_extras' && $usercancreate) {
		$object->oldcopy = dol_clone($object, 2);
		$attribute_name = GETPOST('attribute', 'restricthtml');

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute_name);
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->updateExtraField($attribute_name, 'ORDER_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// add lines from objectlinked
	if ($action == 'import_lines_from_object'
		&& $usercancreate
		&& $object->statut == Commande::STATUS_DRAFT
	) {
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

					$res = $object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $date_start, $date_end, $type, $rang, $special_code, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $fk_unit, $origin, $origin_id, $pu_ht_devise);

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

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to build doc
	$upload_dir = !empty($conf->commande->multidir_output[$object->entity]) ? $conf->commande->multidir_output[$object->entity] : $conf->commande->dir_output;
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'ORDER_SENTBYMAIL';
	$paramname = 'id';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_ORDER_TO'; // used to know the automatic BCC to add
	$trackid = 'ord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	if (!$error && getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && $usercancreate) {
		if ($action == 'addcontact' && $usercancreate) {
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
		} elseif ($action == 'swapstatut' && $usercancreate) {
			// bascule du statut d'un contact
			if ($object->id > 0) {
				$result = $object->swapContactStatus(GETPOSTINT('ligne'));
			} else {
				dol_print_error($db);
			}
		} elseif ($action == 'deletecontact' && $usercancreate) {
			// Efface un contact
			$result = $object->delete_contact($lineid);

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit();
			} else {
				dol_print_error($db);
			}
		}
	}
}


/*
 *	View
 */

$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewOrder");
}
$help_url = 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes|DE:Modul_Kundenaufträge';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-order page-card');

$form = new Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$formmargin = new FormMargin($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

// Mode creation
if ($action == 'create' && $usercancreate) {
	print load_fiche_titre($langs->trans('CreateOrder'), '', 'order');

	$soc = new Societe($db);
	if ($socid > 0) {
		$res = $soc->fetch($socid);
	}

	//$remise_absolue = 0;

	$currency_code = $conf->currency;

	$cond_reglement_id = GETPOSTINT('cond_reglement_id');
	$deposit_percent = GETPOSTFLOAT('cond_reglement_id_deposit_percent');
	$mode_reglement_id = GETPOSTINT('mode_reglement_id');
	$fk_account = GETPOSTINT('fk_account');

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

			if (!$cond_reglement_id) {
				$cond_reglement_id = $soc->cond_reglement_id;
			}
			if (!$deposit_percent) {
				$deposit_percent = $soc->deposit_percent;
			}
			if (!$mode_reglement_id) {
				$mode_reglement_id = $soc->mode_reglement_id;
			}
			if (!$remise_percent) {
				$remise_percent = $soc->remise_percent;
			}
			/*if (!$dateorder) {
				// Do not set 0 here (0 for a date is 1970)
				$dateorder = (empty($dateinvoice) ? (empty($conf->global->MAIN_AUTOFILL_DATE_ORDER) ?-1 : '') : $dateorder);
			}*/
		} else {
			// For compatibility
			if ($element == 'order' || $element == 'commande') {
				$element = $subelement = 'commande';
			} elseif ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			} elseif ($element == 'contract') {
				$element = $subelement = 'contrat';
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
			$ref_client = (!empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

			$soc = $objectsrc->thirdparty;
			$cond_reglement_id	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0));
			$deposit_percent	= (!empty($objectsrc->deposit_percent) ? $objectsrc->deposit_percent : (!empty($soc->deposit_percent) ? $soc->deposit_percent : null));
			$mode_reglement_id	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
			$fk_account         = (!empty($objectsrc->fk_account) ? $objectsrc->fk_account : (!empty($soc->fk_account) ? $soc->fk_account : 0));
			$availability_id = (!empty($objectsrc->availability_id) ? $objectsrc->availability_id : 0);
			$shipping_method_id = (!empty($objectsrc->shipping_method_id) ? $objectsrc->shipping_method_id : (!empty($soc->shipping_method_id) ? $soc->shipping_method_id : 0));
			$warehouse_id       = (!empty($objectsrc->warehouse_id) ? $objectsrc->warehouse_id : (!empty($soc->warehouse_id) ? $soc->warehouse_id : 0));
			$demand_reason_id = (!empty($objectsrc->demand_reason_id) ? $objectsrc->demand_reason_id : (!empty($soc->demand_reason_id) ? $soc->demand_reason_id : 0));
			//$remise_percent		= (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_percent) ? $soc->remise_percent : 0));
			//$remise_absolue		= (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));
			$dateorder = !getDolGlobalString('MAIN_AUTOFILL_DATE_ORDER') ? -1 : '';

			$date_delivery = (!empty($objectsrc->delivery_date) ? $objectsrc->delivery_date : '');

			if (isModEnabled("multicurrency")) {
				if (!empty($objectsrc->multicurrency_code)) {
					$currency_code = $objectsrc->multicurrency_code;
				}
				if (getDolGlobalString('MULTICURRENCY_USE_ORIGIN_TX') && !empty($objectsrc->multicurrency_tx)) {
					$currency_tx = $objectsrc->multicurrency_tx;
				}
			}

			$note_private = $object->getDefaultCreateValueFor('note_private', (!empty($objectsrc->note_private) ? $objectsrc->note_private : null));
			$note_public = $object->getDefaultCreateValueFor('note_public', (!empty($objectsrc->note_public) ? $objectsrc->note_public : null));

			// Object source contacts list
			$srccontactslist = $objectsrc->liste_contact(-1, 'external', 1);
		}
	} else {
		$cond_reglement_id  = empty($soc->cond_reglement_id) ? $cond_reglement_id : $soc->cond_reglement_id;
		$deposit_percent    = empty($soc->deposit_percent) ? $deposit_percent : $soc->deposit_percent;
		$mode_reglement_id  = empty($soc->mode_reglement_id) ? $mode_reglement_id : $soc->mode_reglement_id;
		$fk_account         = empty($soc->mode_reglement_id) ? $fk_account : $soc->fk_account;
		$availability_id    = 0;
		$shipping_method_id = $soc->shipping_method_id;
		$warehouse_id       = $soc->fk_warehouse;
		$demand_reason_id   = $soc->demand_reason_id;
		//$remise_percent     = $soc->remise_percent;
		//$remise_absolue     = 0;
		$dateorder          = !getDolGlobalString('MAIN_AUTOFILL_DATE_ORDER') ? -1 : '';

		if (isModEnabled("multicurrency") && !empty($soc->multicurrency_code)) {
			$currency_code = $soc->multicurrency_code;
		}

		$note_private = $object->getDefaultCreateValueFor('note_private');
		$note_public = $object->getDefaultCreateValueFor('note_public');
	}

	// If form was posted (but error returned), we must reuse the value posted in priority (standard Dolibarr behaviour)
	if (!GETPOST('changecompany')) {
		if (GETPOSTISSET('cond_reglement_id')) {
			$cond_reglement_id = GETPOSTINT('cond_reglement_id');
		}
		if (GETPOSTISSET('deposit_percent')) {
			$deposit_percent = GETPOSTFLOAT('deposit_percent');
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

	print '<form name="crea_commande" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="changecompany" value="0">';	// will be set to 1 by javascript so we know post is done after a company change
	print '<input type="hidden" name="remise_percent" value="'.$soc->remise_percent.'">';
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if (!empty($currency_tx)) {
		print '<input type="hidden" name="originmulticurrency_tx" value="'.$currency_tx.'">';
	}

	print dol_get_fiche_head('');

	// Call Hook tabContentCreateOrder
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentCreateOrder', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<table class="border centpercent">';

		// Reference
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';

		// Reference client
		print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
		if (getDolGlobalString('MAIN_USE_PROPAL_REFCLIENT_FOR_ORDER') && !empty($origin) && !empty($originid)) {
			print '<input type="text" name="ref_client" value="'.$ref_client.'"></td>';
		} else {
			print '<input type="text" name="ref_client" value="'.GETPOST('ref_client').'"></td>';
		}
		print '</tr>';

		// Thirdparty
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans('Customer').'</td>';
		if ($socid > 0) {
			print '<td>';
			print $soc->getNomUrl(1, 'customer');
			print '<input type="hidden" name="socid" value="'.$soc->id.'">';
			print '</td>';
		} else {
			print '<td class="valuefieldcreate">';
			$filter = '((s.client:IN:1,2,3) AND (s.status:=:1))';
			print img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company('', 'socid', $filter, 'SelectThirdParty', 1, 0, null, 0, 'minwidth175 maxwidth500 widthcentpercentminusxx');
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
						$("form[name=crea_commande]").submit();
					});
				});
				</script>';
			}
			print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
			print '</td>';
		}
		print '</tr>'."\n";

		// Contact of order
		if ($socid > 0) {
			// Contacts (ask contact only if thirdparty already defined).
			print "<tr><td>".$langs->trans("DefaultContact").'</td><td>';
			print img_picto('', 'contact', 'class="pictofixedwidth"');
			//print $form->selectcontacts($soc->id, $contactid, 'contactid', 1, empty($srccontactslist) ? "" : $srccontactslist, '', 1, 'maxwidth300 widthcentpercentminusx');
			print $form->select_contact($soc->id, $contactid, 'contactid', 1, empty($srccontactslist) ? "" : $srccontactslist, '', 1, 'maxwidth300 widthcentpercentminusx', true);
			print '</td></tr>';

			// Ligne info remises tiers
			print '<tr><td>'.$langs->trans('Discounts').'</td><td>';

			$absolute_discount = $soc->getAvailableDiscounts();

			$thirdparty = $soc;
			$discount_type = 0;
			$backtopage = $_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.urlencode((string) (GETPOST('origin'))).'&originid='.urlencode((string) (GETPOSTINT('originid')));
			include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

			print '</td></tr>';
		}

		// Date
		print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
		print img_picto('', 'action', 'class="pictofixedwidth"');
		print $form->selectDate('', 're', 0, 0, 0, "crea_commande", 1, 1); // Always autofill date with current date
		print '</td></tr>';

		// Date delivery planned
		print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
		print '<td colspan="3">';
		$date_delivery = ($date_delivery ? $date_delivery : $object->delivery_date);
		print img_picto('', 'action', 'class="pictofixedwidth"');
		print $form->selectDate($date_delivery ? $date_delivery : -1, 'liv_', 1, 1, 1);
		print "</td>\n";
		print '</tr>';

		// Delivery delay
		print '<tr class="fielddeliverydelay"><td>'.$langs->trans('AvailabilityPeriod').'</td><td>';
		print img_picto('', 'clock', 'class="pictofixedwidth"');
		$form->selectAvailabilityDelay((GETPOSTISSET('availability_id') ? GETPOST('availability_id') : $availability_id), 'availability_id', '', 1, 'maxwidth200 widthcentpercentminusx');
		print '</td></tr>';

		// Terms of payment
		print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
		print img_picto('', 'payment', 'class="pictofixedwidth"');
		print $form->getSelectConditionsPaiements($cond_reglement_id, 'cond_reglement_id', 1, 1, 0, 'maxwidth200 widthcentpercentminusx', $deposit_percent);
		print '</td></tr>';

		// Payment mode
		print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
		print img_picto('', 'bank', 'class="pictofixedwidth"');
		print $form->select_types_paiements($mode_reglement_id, 'mode_reglement_id', 'CRDT', 0, 1, 0, 0, 1, 'maxwidth200 widthcentpercentminusx', 1);
		print '</td></tr>';

		// Bank Account
		if (getDolGlobalString('BANK_ASK_PAYMENT_BANK_DURING_ORDER') && isModEnabled("bank")) {
			print '<tr><td>'.$langs->trans('BankAccount').'</td><td>';
			print img_picto('', 'bank_account', 'class="pictofixedwidth"').$form->select_comptes($fk_account, 'fk_account', 0, '', 1, '', 0, 'maxwidth200 widthcentpercentminusx', 1);
			print '</td></tr>';
		}

		// Shipping Method
		if (isModEnabled('shipping')) {
			print '<tr><td>'.$langs->trans('SendingMethod').'</td><td>';
			print img_picto('', 'object_dolly', 'class="pictofixedwidth"');
			$form->selectShippingMethod(((GETPOSTISSET('shipping_method_id') && GETPOSTINT('shipping_method_id') != 0) ? GETPOST('shipping_method_id') : $shipping_method_id), 'shipping_method_id', '', 1, '', 0, 'maxwidth200 widthcentpercentminusx');
			print '</td></tr>';
		}

		// Warehouse
		if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER')) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			print '<tr><td>'.$langs->trans('Warehouse').'</td><td>';
			print img_picto('', 'stock', 'class="pictofixedwidth"').$formproduct->selectWarehouses((GETPOSTISSET('warehouse_id') ? GETPOST('warehouse_id') : $warehouse_id), 'warehouse_id', '', 1, 0, 0, '', 0, 0, array(), 'maxwidth500 widthcentpercentminusxx');
			print '</td></tr>';
		}

		// Source / Channel - What trigger creation
		print '<tr><td>'.$langs->trans('Source').'</td><td>';
		print img_picto('', 'question', 'class="pictofixedwidth"');
		$form->selectInputReason((GETPOSTISSET('demand_reason_id') ? GETPOST('demand_reason_id') : $demand_reason_id), 'demand_reason_id', '', 1, 'maxwidth200 widthcentpercentminusx');
		print '</td></tr>';

		// TODO How record was recorded OrderMode (llx_c_input_method)

		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			print '<tr>';
			print '<td>'.$langs->trans("Project").'</td><td>';
			print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), (GETPOSTISSET('projectid') ? GETPOST('projectid') : $projectid), 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
			print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
			print '</td>';
			print '</tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr>';
			print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), !empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : $soc->fk_incoterms, 1).'</label></td>';
			print '<td class="maxwidthonsmartphone">';
			$incoterm_id = GETPOST('incoterm_id');
			$location_incoterms = GETPOST('location_incoterms');
			if (empty($incoterm_id)) {
				$incoterm_id = (!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : $soc->fk_incoterms);
				$location_incoterms = (!empty($objectsrc->location_incoterms) ? $objectsrc->location_incoterms : $soc->location_incoterms);
			}
			print img_picto('', 'incoterm', 'class="pictofixedwidth"');
			print $form->select_incoterms($incoterm_id, $location_incoterms);
			print '</td></tr>';
		}

		// Other attributes
		$parameters = array();
		if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
			$parameters['objectsrc'] =  $objectsrc;
		}
		$parameters['socid'] = $socid;

		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			if (getDolGlobalString('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_ORDER') && !empty($soc->id)) {
				// copy from thirdparty
				$tpExtrafields = new ExtraFields($db);
				$tpExtrafieldLabels = $tpExtrafields->fetch_name_optionals_label($soc->table_element);
				if ($soc->fetch_optionals() > 0) {
					$object->array_options = array_merge($object->array_options, $soc->array_options);
				}
			}

			print $object->showOptionals($extrafields, 'create', $parameters);
		}

		// Template to use by default
		print '<tr><td>'.$langs->trans('DefaultModel').'</td>';
		print '<td>';
		include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
		$liste = ModelePDFCommandes::liste_modeles($db);
		$preselected = getDolGlobalString('COMMANDE_ADDON_PDF');
		print img_picto('', 'pdf', 'class="pictofixedwidth"');
		print $form->selectarray('model', $liste, $preselected, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200 widthcentpercentminusx', 1);
		print "</td></tr>";

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			print '<tr>';
			print '<td>'.$form->editfieldkey("Currency", 'multicurrency_code', '', $object, 0).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print img_picto('', 'currency', 'class="pictofixedwidth"').$form->selectMultiCurrency(((GETPOSTISSET('multicurrency_code') && !GETPOST('changecompany')) ? GETPOST('multicurrency_code') : $currency_code), 'multicurrency_code', 0, '', false, 'maxwidth200 widthcentpercentminusx');
			print '</td></tr>';
		}

		// Note public
		print '<tr>';
		print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
		print '<td>';

		$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);
		// print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_public.'</textarea>';
		print '</td></tr>';

		// Note private
		if (empty($user->socid)) {
			print '<tr>';
			print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
			print '<td>';

			$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			// print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'</textarea>';
			print '</td></tr>';
		}

		if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
			// TODO for compatibility
			if ($origin == 'contrat') {
				// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
				//$objectsrc->remise_absolue = $remise_absolue;
				//$objectsrc->remise_percent = $remise_percent;
				$objectsrc->update_price(1);
			}

			print "\n<!-- ".$classname." info -->";
			print "\n";
			print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
			print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
			print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
			print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
			print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

			switch ($classname) {
				case 'Propal':
					$newclassname = 'CommercialProposal';
					break;
				case 'Commande':
					$newclassname = 'Order';
					break;
				case 'Expedition':
					$newclassname = 'Sending';
					break;
				case 'Contrat':
					$newclassname = 'Contract';
					break;
				default:
					$newclassname = $classname;
			}

			print '<tr><td>'.$langs->trans($newclassname).'</td><td>'.$objectsrc->getNomUrl(1).'</td></tr>';

			// Amount
			print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($objectsrc->total_ht).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($objectsrc->total_tva)."</td></tr>";
			if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) { 		// Localtax1 RE
				print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax1)."</td></tr>";
			}

			if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) { 		// Localtax2 IRPF
				print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax2)."</td></tr>";
			}

			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($objectsrc->total_ttc)."</td></tr>";

			if (isModEnabled("multicurrency")) {
				print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td>'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
				print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td>'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
				print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td>'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
			}
		}

		print "\n";

		print '</table>';
	}

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateDraft");

	// Show origin lines
	if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		$objectsrc->printOriginLinesList('', $selectedLines);

		print '</table>';
		print '</div>';
	}

	print '</form>';
} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) {
		$product_static = new Product($db);

		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$author = new User($db);
		$author->fetch($object->user_author_id);

		$object->fetch_thirdparty();
		$res = $object->fetch_optionals();

		$head = commande_prepare_head($object);
		print dol_get_fiche_head($head, 'order', $langs->trans("CustomerOrder"), -1, 'order');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
		}

		// Confirmation of validation
		if ($action == 'validate') {
			// We check that object has a temporary ref
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

			$text = $langs->trans('ConfirmValidateOrder', $numref);
			if (isModEnabled('notification')) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
				$notify = new Notify($db);
				$text .= '<br>';
				$text .= $notify->confirmMessage('ORDER_VALIDATE', $object->socid, $object);
			}

			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			$formquestion = array();
			if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') && $qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$forcecombo = 0;
				if ($conf->browser->name == 'ie') {
					$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
				}
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other', 'name' => 'idwarehouse', 'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOSTINT('idwarehouse') ? GETPOSTINT('idwarehouse') : 'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				);
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

			if (getDolGlobalInt('SALE_ORDER_SUGGEST_DOWN_PAYMENT_INVOICE_CREATION')) {
				// This is a hidden option:
				// Suggestion to create invoice during order validation is not enabled by default.
				// Such choice should be managed by the workflow module and trigger. This option generates conflicts with some setup.
				// It may also break step of creating an order when invoicing must be done from proposals and not from orders
				$deposit_percent_from_payment_terms = (float) getDictionaryValue('c_payment_term', 'deposit_percent', $object->cond_reglement_id);

				if (!empty($deposit_percent_from_payment_terms) && isModEnabled('invoice') && $user->hasRight('facture', 'creer')) {
					require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

					$object->fetchObjectLinked();

					$eligibleForDepositGeneration = true;

					if (array_key_exists('facture', $object->linkedObjects)) {
						foreach ($object->linkedObjects['facture'] as $invoice) {
							'@phan-var-force Facture $invoice';
							if ($invoice->type == Facture::TYPE_DEPOSIT) {
								$eligibleForDepositGeneration = false;
								break;
							}
						}
					}

					if ($eligibleForDepositGeneration && array_key_exists('propal', $object->linkedObjects)) {
						foreach ($object->linkedObjects['propal'] as $proposal) {
							$proposal->fetchObjectLinked();

							if (array_key_exists('facture', $proposal->linkedObjects)) {
								foreach ($proposal->linkedObjects['facture'] as $invoice) {
									'@phan-var-force Facture $invoice';
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
							'tdclass' => '',
							'name' => 'generate_deposit',
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


						$paymentTermsSelect = $form->getSelectConditionsPaiements(0, 'cond_reglement_id', -1, 0, 0, 'minwidth200');

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
							'label' => $langs->trans('ValidateGeneratedDeposit')
						);

						$formquestion[] = array(
							'type' => 'onecolumn',
							'value' => '
								<script>
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
									});
								</script>
							'
						);
					}
				}
			}

			if (!$error) {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_validate', $formquestion, 0, 1, 240);
			}
		}

		// Confirm back to draft status
		if ($action == 'modif') {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			$text = $langs->trans('ConfirmUnvalidateOrder', $object->ref);
			$formquestion = array();
			if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') && $qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$forcecombo = 0;
				if ($conf->browser->name == 'ie') {
					$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
				}
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other', 'name' => 'idwarehouse', 'label' => $langs->trans("SelectWarehouseForStockIncrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse') ? GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('UnvalidateOrder'), $text, 'confirm_modif', $formquestion, "yes", 1, 220);
		}

		/*
		 * Confirmation de la cloture
		*/
		if ($action == 'shipped') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_shipped', '', 0, 1);
		}

		/*
		 * Confirmation de l'annulation
		 */
		if ($action == 'cancel') {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			$text = $langs->trans('ConfirmCancelOrder', $object->ref);
			$formquestion = array();
			if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') && $qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$forcecombo = 0;
				if ($conf->browser->name == 'ie') {
					$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
				}
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other', 'name' => 'idwarehouse', 'label' => $langs->trans("SelectWarehouseForStockIncrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse') ? GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("Cancel"), $text, 'confirm_cancel', $formquestion, 0, 1);
		}

		// Confirmation to delete line
		if ($action == 'ask_deleteline') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
		}

		// Clone confirmation
		if ($action == 'clone') {
			$filter = '(s.client:IN:1,2,3)';
			// Create an array for form
			$formquestion = array(
				array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOSTINT('socid'), 'socid', $filter, '', 0, 0, null, 0, 'maxwidth300'))
			);
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneOrder', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
		}

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action);
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;


		// Order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$soc->getNomUrl(1, 'customer');
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
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

		// Call Hook tabContentViewOrder
		$parameters = array();
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('tabContentViewOrder', $parameters, $object, $action);
		if (empty($reshook)) {
			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			if ($soc->outstanding_limit) {
				// Outstanding Bill
				print '<tr><td class="titlefield">';
				print $langs->trans('OutstandingBill');
				print '</td><td class="valuefield">';
				$arrayoutstandingbills = $soc->getOutstandingBills();
				print price($arrayoutstandingbills['opened']).' / ';
				print price($soc->outstanding_limit, 0, '', 1, - 1, - 1, $conf->currency);
				print '</td>';
				print '</tr>';
			}

			// Relative and absolute discounts
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
				$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			} else {
				$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
				$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
			}

			$addrelativediscount = '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("EditRelativeDiscounts").'</a>';
			$addabsolutediscount = '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("EditGlobalDiscounts").'</a>';
			$addcreditnote = '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&socid='.$soc->id.'&type=2&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddCreditNote").'</a>';

			print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td class="valuefield">';

			$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
			$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
			$absolute_discount = price2num($absolute_discount, 'MT');
			$absolute_creditnote = price2num($absolute_creditnote, 'MT');

			$thirdparty = $soc;
			$discount_type = 0;
			$backtopage = $_SERVER["PHP_SELF"].'?id='.$object->id;
			include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

			print '</td></tr>';

			// Date
			print '<tr><td>';
			$editenable = $usercancreate && $object->statut == Commande::STATUS_DRAFT;
			print $form->editfieldkey("Date", 'date', '', $object, $editenable);
			print '</td><td class="valuefield">';
			if ($action == 'editdate') {
				print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="setdate">';
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
				print $form->selectDate($object->date, 'order_', 0, 0, 0, "setdate");
				print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
				print '</form>';
			} else {
				print $object->date ? dol_print_date($object->date, 'day') : '&nbsp;';
				if ($object->hasDelay() && empty($object->delivery_date)) {	// If there is a delivery date planned, warning should be on this date
					print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
				}
			}
			print '</td>';
			print '</tr>';

			// Delivery date planned
			print '<tr><td>';
			$editenable = $usercancreate;
			print $form->editfieldkey("DateDeliveryPlanned", 'date_livraison', '', $object, $editenable);
			print '</td><td class="valuefield">';
			if ($action == 'editdate_livraison') {
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
				print $form->selectDate($object->delivery_date ? $object->delivery_date : -1, 'liv_', 1, 1, 0, "setdate_livraison", 1, 0);
				print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
				print '</form>';
			} else {
				print $object->delivery_date ? dol_print_date($object->delivery_date, 'dayhour') : '&nbsp;';
				if ($object->hasDelay() && !empty($object->delivery_date)) {
					print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
				}
			}
			print '</td>';
			print '</tr>';

			// Delivery delay
			print '<tr class="fielddeliverydelay"><td>';
			$editenable = $usercancreate;
			print $form->editfieldkey("AvailabilityPeriod", 'availability', '', $object, $editenable);
			print '</td><td class="valuefield">';
			if ($action == 'editavailability') {
				$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'availability_id', 1);
			} else {
				$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'none', 1);
			}
			print '</td></tr>';

			// Shipping Method
			if (isModEnabled('shipping')) {
				print '<tr><td>';
				$editenable = $usercancreate;
				print $form->editfieldkey("SendingMethod", 'shippingmethod', '', $object, $editenable);
				print '</td><td class="valuefield">';
				if ($action == 'editshippingmethod') {
					$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
				} else {
					$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'none');
				}
				print '</td>';
				print '</tr>';
			}

			// Warehouse
			if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER')) {
				$langs->load('stocks');
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				print '<tr><td>';
				$editenable = $usercancreate;
				print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $editenable);
				print '</td><td class="valuefield">';
				if ($action == 'editwarehouse') {
					$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'warehouse_id', 1);
				} else {
					$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'none');
				}
				print '</td>';
				print '</tr>';
			}

			// Source reason (why we have an order)
			print '<tr><td>';
			$editenable = $usercancreate;
			print $form->editfieldkey("Source", 'demandreason', '', $object, $editenable);
			print '</td><td class="valuefield">';
			if ($action == 'editdemandreason') {
				$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'demand_reason_id', 1);
			} else {
				$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'none');
			}
			print '</td></tr>';

			// Terms of payment
			print '<tr><td>';
			$editenable = $usercancreate;
			print $form->editfieldkey("PaymentConditionsShort", 'conditions', '', $object, $editenable);
			print '</td><td class="valuefield">';
			if ($action == 'editconditions') {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 1, '', 1, $object->deposit_percent);
			} else {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none', 1, '', 1, $object->deposit_percent);
			}
			print '</td>';

			print '</tr>';

			// Mode of payment
			print '<tr><td>';
			$editenable = $usercancreate;
			print $form->editfieldkey("PaymentMode", 'mode', '', $object, $editenable);
			print '</td><td class="valuefield">';
			if ($action == 'editmode') {
				$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
			} else {
				$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
			}
			print '</td></tr>';

			// Multicurrency
			if (isModEnabled("multicurrency")) {
				// Multicurrency code
				print '<tr>';
				print '<td>';
				$editenable = $usercancreate && $object->statut == Commande::STATUS_DRAFT;
				print $form->editfieldkey("Currency", 'multicurrencycode', '', $object, $editenable);
				print '</td><td class="valuefield">';
				if ($action == 'editmulticurrencycode') {
					$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'multicurrency_code');
				} else {
					$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'none');
				}
				print '</td></tr>';

				// Multicurrency rate
				if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
					print '<tr>';
					print '<td>';
					$editenable = $usercancreate && $object->multicurrency_code && $object->multicurrency_code != $conf->currency && $object->statut == $object::STATUS_DRAFT;
					print $form->editfieldkey("CurrencyRate", 'multicurrencyrate', '', $object, $editenable);
					print '</td><td class="valuefield">';
					if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
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

			// TODO Order mode (how we receive order). Not yet implemented
			/*
			print '<tr><td>';
			$editenable = $usercancreate;
			print $form->editfieldkey("SourceMode", 'inputmode', '', $object, $editenable);
			print '</td><td>';
			if ($action == 'editinputmode') {
				$form->formInputMode($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->source, 'input_mode_id', 1);
			} else {
				$form->formInputMode($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->source, 'none');
			}
			print '</td></tr>';
			*/

			$tmparray = $object->getTotalWeightVolume();
			$totalWeight = $tmparray['weight'];
			$totalVolume = $tmparray['volume'];
			if ($totalWeight) {
				print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
				print '<td class="valuefield">';
				print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
				print '</td></tr>';
			}
			if ($totalVolume) {
				print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
				print '<td class="valuefield">';
				print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
				print '</td></tr>';
			}

			// TODO How record was recorded OrderMode (llx_c_input_method)

			// Incoterms
			if (isModEnabled('incoterm')) {
				print '<tr><td>';
				$editenable = $usercancreate;
				print $form->editfieldkey("IncotermLabel", 'incoterm', '', $object, $editenable);
				print '</td>';
				print '<td class="valuefield">';
				if ($action != 'editincoterm') {
					print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
				} else {
					print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
				}
				print '</td></tr>';
			}

			// Bank Account
			if (getDolGlobalString('BANK_ASK_PAYMENT_BANK_DURING_ORDER') && isModEnabled("bank")) {
				print '<tr><td>';
				$editenable = $usercancreate;
				print $form->editfieldkey("BankAccount", 'bankaccount', '', $object, $editenable);
				print '</td><td class="valuefield">';
				if ($action == 'editbankaccount') {
					$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
				} else {
					$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
				}
				print '</td>';
				print '</tr>';
			}

			// Other attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			$alert = '';
			if (getDolGlobalString('ORDER_MANAGE_MIN_AMOUNT') && $object->total_ht < $object->thirdparty->order_min_amount) {
				$alert = ' ' . img_warning($langs->trans('OrderMinAmount') . ': ' . price($object->thirdparty->order_min_amount));
			}

			print '<tr>';
			print '<td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
			print '<td class="nowrap amountcard right">' . price($object->total_ht, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				// Multicurrency Amount HT
				print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_ht, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';

			print '<tr>';
			print '<td class="titlefieldmiddle">' . $langs->trans('AmountVAT') . '</td>';
			print '<td class="nowrap amountcard right">' . price($object->total_tva, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				// Multicurrency Amount VAT
				print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_tva, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';

			// Amount Local Taxes
			if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) {
				print '<tr>';
				print '<td class="titlefieldmiddle">' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
				print '<td class="nowrap amountcard right">' . price($object->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
				if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
					$object->multicurrency_total_localtax1 = price2num($object->total_localtax1 * $object->multicurrency_tx, 'MT');

					print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_localtax1, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
				}
				print '</tr>';
			}

			// Amount Local Taxes
			if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) {
				print '<tr>';
				print '<td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
				print '<td class="nowrap amountcard right">' . price($object->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
				if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
					$object->multicurrency_total_localtax2 = price2num($object->total_localtax2 * $object->multicurrency_tx, 'MT');

					print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_localtax2, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
				}
				print '</tr>';
			}

			print '<tr>';
			print '<td>' . $langs->trans('AmountTTC') . '</td>';
			print '<td class="valuefield nowrap right amountcard">' . price($object->total_ttc, 1, '', 1, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				// Multicurrency Amount TTC
				print '<td class="valuefield nowrap right amountcard">' . price($object->multicurrency_total_ttc, 1, '', 1, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>'."\n";

			print '</table>';

			// Statut
			//print '<tr><td>' . $langs->trans('Status') . '</td><td>' . $object->getLibStatut(4) . '</td></tr>';

			// Margin Infos
			if (isModEnabled('margin')) {
				$formmargin->displayMarginInfos($object);
			}


			print '</div>';
			print '</div>'; // Close fichecenter

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

			print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
			<input type="hidden" name="token" value="' . newToken().'">
			<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="page_y" value="">
			<input type="hidden" name="id" value="' . $object->id.'">
			<input type="hidden" name="backtopage" value="'.$backtopage.'">
				';

			if (!empty($conf->use_javascript_ajax) && $object->statut == Commande::STATUS_DRAFT) {
				include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
			}

			print '<div class="div-table-responsive-no-min">';
			print '<table id="tablelines" class="noborder noshadow" width="100%">';

			// Show object lines
			if (!empty($object->lines)) {
				$object->printObjectLines($action, $mysoc, $soc, $lineid, 1);
			}

			/*
			 * Form to add new line
			 */
			if ($object->statut == Commande::STATUS_DRAFT && $usercancreate && $action != 'selectlines') {
				if ($action != 'editline') {
					// Add free products/services

					$parameters = array();
					// Note that $action and $object may be modified by hook
					$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);
					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
					}
					if (empty($reshook)) {
						$object->formAddObjectLine(1, $mysoc, $soc);
					}
				} else {
					$parameters = array();
					$reshook = $hookmanager->executeHooks('formEditObjectLine', $parameters, $object, $action);
				}
			}
			print '</table>';
			print '</div>';

			print "</form>\n";
		}

		print dol_get_fiche_end();

		/*
		 * Buttons for actions
		 */
		if ($action != 'presend' && $action != 'editline') {
			print '<div class="tabsAction">';

			$parameters = array();
			// Note that $action and $object may be modified by hook
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
			if (empty($reshook)) {
				$numlines = count($object->lines);

				// Reopen a closed order
				if (($object->statut == Commande::STATUS_CLOSED || $object->statut == Commande::STATUS_CANCELED) && $usercancreate && (!$object->billed || !getDolGlobalInt('ORDER_DONT_REOPEN_BILLED'))) {
					print dolGetButtonAction('', $langs->trans('ReOpen'), 'default', $_SERVER["PHP_SELF"].'?action=reopen&amp;token='.newToken().'&amp;id='.$object->id, '');
				}

				// Send
				if (empty($user->socid)) {
					if ($object->statut > Commande::STATUS_DRAFT || getDolGlobalString('COMMANDE_SENDBYEMAIL_FOR_ALL_STATUS')) {
						if ($usercansend) {
							print dolGetButtonAction('', $langs->trans('SendMail'), 'email', $_SERVER["PHP_SELF"].'?action=presend&token='.newToken().'&id='.$object->id.'&mode=init#formmailbeforetitle', '');
						} else {
							print dolGetButtonAction('', $langs->trans('SendMail'), 'email', $_SERVER['PHP_SELF']. '#', '', false);
						}
					}
				}

				// Valid
				if ($object->statut == Commande::STATUS_DRAFT && ($object->total_ttc >= 0 || getDolGlobalString('ORDER_ENABLE_NEGATIVE')) && $usercanvalidate) {
					if ($numlines > 0) {
						print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?action=validate&amp;token='.newToken().'&amp;id='.$object->id, $object->id, 1);
					} else {
						print dolGetButtonAction($langs->trans("ErrorObjectMustHaveLinesToBeValidated", $object->ref), $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?action=validate&amp;token='.newToken().'&amp;id='.$object->id, $object->id, 0);
					}
				}
				// Edit
				if ($object->statut == Commande::STATUS_VALIDATED && $usercancreate) {
					print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?action=modif&amp;token='.newToken().'&amp;id='.$object->id, '');
				}

				$arrayforbutaction = array();
				// Create a purchase order

				if (!getDolGlobalInt('COMMANDE_DISABLE_ADD_PURCHASE_ORDER')) {
					$arrayforbutaction[] = array('lang' => 'orders', 'enabled' => (isModEnabled("supplier_order") && $object->statut > Commande::STATUS_DRAFT), 'perm' => $usercancreatepurchaseorder, 'label' => 'AddPurchaseOrder', 'url' => '/fourn/commande/card.php?action=create&amp;origin='.urlencode($object->element).'&amp;originid='.((int) $object->id));
				}

				/*if (isModEnabled("supplier_order") && $object->statut > Commande::STATUS_DRAFT && $object->getNbOfServicesLines() > 0) {
					if ($usercancreatepurchaseorder) { isModEnabled("supplier_order") && $object->statut > Commande::STATUS_DRAFT && $object->getNbOfServicesLines() > 0
						print dolGetButtonAction('', $langs->trans('AddPurchaseOrder'), 'default', DOL_URL_ROOT.'/fourn/commande/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id, '');
					}
				}*/

				// Create intervention
				$arrayforbutaction[] = array('lang' => 'interventions', 'enabled' => (isModEnabled("intervention") && $object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED && $object->getNbOfServicesLines() > 0), 'perm' => $user->hasRight('ficheinter', 'creer'), 'label' => 'AddIntervention', 'url' => '/fichinter/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid);
				/*if (isModEnabled('ficheinter')) {
					$langs->load("interventions");

					if ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED && $object->getNbOfServicesLines() > 0) {
						if ($user->hasRight('ficheinter', 'creer')) {
							print dolGetButtonAction('', $langs->trans('AddIntervention'), 'default', DOL_URL_ROOT.'/fichinter/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid, '');
						} else {
							print dolGetButtonAction($langs->trans('NotAllowed'), $langs->trans('AddIntervention'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
						}
					}
				}*/

				// Create contract
				$arrayforbutaction[] = array('lang' => 'contracts', 'enabled' => (isModEnabled("contract") && ($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_SHIPMENTONPROCESS || $object->statut == Commande::STATUS_CLOSED)), 'perm' => $user->hasRight('contrat', 'creer'), 'label' => 'AddContract', 'url' => '/contrat/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid);
				/*if (isModEnabled('contrat') && ($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_SHIPMENTONPROCESS || $object->statut == Commande::STATUS_CLOSED)) {
					$langs->load("contracts");

					if ($user->hasRight('contrat', 'creer')) {
						print dolGetButtonAction('', $langs->trans('AddContract'), 'default', DOL_URL_ROOT.'/contrat/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid, '');
					}
				}*/

				$numshipping = 0;
				if (isModEnabled('shipping')) {
					$numshipping = $object->countNbOfShipments();
				}

				// Create shipment
				if ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED && ($object->getNbOfProductsLines() > 0 || getDolGlobalString('STOCK_SUPPORTS_SERVICES'))) {
					if ((getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && $user->hasRight('expedition', 'creer')) || (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && $user->hasRight('expedition', 'delivery', 'creer'))) {
						$arrayforbutaction[] = array('lang' => 'sendings', 'enabled' => (isModEnabled("shipping") && ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED && ($object->getNbOfProductsLines() > 0 || getDolGlobalString('STOCK_SUPPORTS_SERVICES')))), 'perm' => $user->hasRight('expedition', 'creer'), 'label' => 'CreateShipment', 'url' => '/expedition/shipment.php?id='.$object->id);
						/*
						if ($user->hasRight('expedition', 'creer')) {
						print dolGetButtonAction('', $langs->trans('CreateShipment'), 'default', DOL_URL_ROOT.'/expedition/shipment.php?id='.$object->id, '');
						} else {
						print dolGetButtonAction($langs->trans('NotAllowed'), $langs->trans('CreateShipment'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
						}*/
					} else {
						$langs->load("errors");
						print dolGetButtonAction($langs->trans('ErrorModuleSetupNotComplete'), $langs->trans('CreateShipment'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
					}
				}

				// Create bill
				$arrayforbutaction[] = array(
					'lang' => 'bills',
					'enabled' => (isModEnabled('invoice') && $object->statut > Commande::STATUS_DRAFT && !$object->billed && $object->total_ttc >= 0),
					'perm' => ($user->hasRight('facture', 'creer') && !getDolGlobalInt('WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER')),
					'label' => 'CreateBill',
					'url' => '/compta/facture/card.php?action=create&amp;token='.newToken().'&amp;origin='.urlencode($object->element).'&amp;originid='.$object->id.'&amp;socid='.$object->socid
				);
				/*
				 if (isModEnabled('facture') && $object->statut > Commande::STATUS_DRAFT && !$object->billed && $object->total_ttc >= 0) {
				 if (isModEnabled('facture') && $user->hasRight('facture', 'creer') && empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER)) {
				 print dolGetButtonAction('', $langs->trans('CreateBill'), 'default', DOL_URL_ROOT.'/compta/facture/card.php?action=create&amp;token='.newToken().'&amp;origin='.urlencode($object->element).'&amp;originid='.$object->id.'&amp;socid='.$object->socid, '');
				 }
				 }*/

				$actionButtonsParameters = [
					"areDropdownButtons"	=> !getDolGlobalInt("MAIN_REMOVE_DROPDOWN_CREATE_BUTTONS_ON_ORDER")
				];

				if ($numlines > 0) {
					print dolGetButtonAction('', $langs->trans("Create"), 'default', $arrayforbutaction, $object->id, 1, $actionButtonsParameters);
				} else {
					print dolGetButtonAction($langs->trans("ErrorObjectMustHaveLinesToBeValidated", $object->ref), $langs->trans("Create"), 'default', $arrayforbutaction, $object->id, 0, $actionButtonsParameters);
				}

				// Set to shipped
				if (($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_SHIPMENTONPROCESS) && $usercanclose) {
					print dolGetButtonAction('', $langs->trans('ClassifyShipped'), 'default', $_SERVER["PHP_SELF"].'?action=shipped&amp;token='.newToken().'&amp;id='.$object->id, '');
				}

				// Set billed or unbilled
				// Note: Even if module invoice is not enabled, we should be able to use button "Classified billed"
				if ($object->statut > Commande::STATUS_DRAFT && !$object->billed && $object->total_ttc >= 0) {
					if ($usercancreate && $object->statut >= Commande::STATUS_VALIDATED && !getDolGlobalString('ORDER_DISABLE_CLASSIFY_BILLED_FROM_ORDER') && !getDolGlobalString('WORKFLOW_BILL_ON_SHIPMENT')) {
						print dolGetButtonAction('', $langs->trans('ClassifyBilled'), 'default', $_SERVER["PHP_SELF"].'?action=classifybilled&amp;token='.newToken().'&amp;id='.$object->id, '');
					}
				}
				if ($object->statut > Commande::STATUS_DRAFT && $object->billed) {
					if ($usercancreate && $object->statut >= Commande::STATUS_VALIDATED && !getDolGlobalString('ORDER_DISABLE_CLASSIFY_BILLED_FROM_ORDER') && !getDolGlobalString('WORKFLOW_BILL_ON_SHIPMENT')) {
						print dolGetButtonAction('', $langs->trans('ClassifyUnBilled'), 'delete', $_SERVER["PHP_SELF"].'?action=classifyunbilled&amp;token='.newToken().'&amp;id='.$object->id, '');
					}
				}

				// Clone
				if ($usercancreate) {
					print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER["PHP_SELF"].'?action=clone&token='.newToken().'&id='.$object->id.'&socid='.$object->socid, '');
				}

				// Cancel order
				if ($object->statut == Commande::STATUS_VALIDATED && !empty($usercancancel)) {
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'">'.$langs->trans("CancelOrder").'</a>';
				}

				// Delete order
				if ($usercandelete) {
					if ($numshipping == 0) {
						print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id, '');
					} else {
						print dolGetButtonAction($langs->trans('ShippingExist'), $langs->trans('Delete'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
					}
				}
			}
			print '</div>';
		}

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre
			// Documents
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->commande->multidir_output[$object->entity].'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $usercanread;
			$delallowed = $usercancreate;
			print $formfile->showdocuments('commande', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, '', $object);


			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, array('order'));

			$compatibleImportElementsList = false;
			if ($usercancreate
				&& $object->statut == Commande::STATUS_DRAFT) {
				$compatibleImportElementsList = array('commande', 'propal', 'facture'); // import from linked elements
			}
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

			// Show online payment link
			// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
			include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
			$validpaymentmethod = getValidOnlinePaymentMethods('');
			$useonlinepayment = count($validpaymentmethod);

			if (getDolGlobalString('ORDER_HIDE_ONLINE_PAYMENT_ON_ORDER')) {
				$useonlinepayment = 0;
			}
			if ($object->statut != Commande::STATUS_DRAFT && $useonlinepayment) {
				print '<br><!-- Link to pay -->';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				print showOnlinePaymentUrl('order', $object->ref).'<br>';
			}

			print '</div><div class="fichehalfright">';

			$MAXEVENT = 10;

			$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/commande/agenda.php?id='.$object->id);

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'order', $socid, 1, '', $MAXEVENT, '', $morehtmlcenter); // Show all action for thirdparty

			print '</div></div>';
		}

		// Presend form
		$modelmail = 'order_send';
		$defaulttopic = 'SendOrderRef';
		$diroutput = getMultidirOutput($object);
		$trackid = 'ord'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
