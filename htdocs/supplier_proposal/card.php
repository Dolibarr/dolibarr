<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2019  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 * Copyright (C) 2022       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *	\file		htdocs/supplier_proposal/card.php
 *	\ingroup	supplier_proposal
 *	\brief		Card supplier proposal
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/modules_supplier_proposal.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (!empty($conf->project->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'supplier_proposal', 'compta', 'bills', 'propal', 'orders', 'products', 'deliveries', 'sendings'));
if (!empty($conf->margin->enabled)) {
	$langs->load('margins');
}

$error = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$projectid = GETPOST('projectid', 'int');
$lineid = GETPOST('lineid', 'int');
$contactid = GETPOST('contactid', 'int');
$rank = (GETPOST('rank', 'int') > 0) ? GETPOST('rank', 'int') : -1;

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES = 4;

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'supplier_proposal', $id);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('supplier_proposalcard', 'globalcard'));

$object = new SupplierProposal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$ret = $object->fetch_thirdparty();
	}
	if ($ret < 0) {
		dol_print_error('', $object->error);
	}
}

// Common permissions
$usercanread = $user->rights->supplier_proposal->lire;
$usercancreate		= $user->rights->supplier_proposal->creer;
$usercandelete		= $user->rights->supplier_proposal->supprimer;

// Advanced permissions
$usercanvalidate = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($usercancreate)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->supplier_proposal->validate_advance)));
$usercansend = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->supplier_proposal->send_advance);

// Additional area permissions
$usercanclose = $user->rights->supplier_proposal->cloturer;
$usercancreateorder = ($user->rights->fournisseur->commande->creer || $user->rights->supplier_order->creer);

// Permissions for includes
$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php
$permissiontoadd = $usercancreate;


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/supplier_proposal/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/supplier_proposal/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
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

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
		if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				$result = $object->createFromClone($user, $socid);
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit();
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete) {
		// Delete askprice
		$result = $object->delete($user);
		if ($result > 0) {
			header('Location: '.DOL_URL_ROOT.'/supplier_proposal/list.php');
			exit();
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans($object->error), null, 'errors');
		}
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		// Remove line
		$result = $object->deleteline($lineid);
		// reorder lines
		if ($result) {
			$object->line_order(true);
		}

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			// Define output language
			$outputlangs = $langs;
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit();
	} elseif ($action == 'confirm_validate' && $confirm == 'yes' && $usercanvalidate) {
		// Validation
		$result = $object->valid($user);
		if ($result >= 0) {
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
			}
		} else {
			$langs->load("errors");
			if (count($object->errors) > 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans($object->error), null, 'errors');
			}
		}
	} elseif ($action == 'setdate_livraison' && $usercancreate) {
		$result = $object->setDeliveryDate($user, dol_mktime(12, 0, 0, GETPOST('liv_month', 'int'), GETPOST('liv_day', 'int'), GETPOST('liv_year', 'int')));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'add' && $usercancreate) {
		// Create supplier proposal
		$object->socid = $socid;
		$object->fetch_thirdparty();

		$date_delivery = dol_mktime(12, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));

		if ($socid < 1) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Supplier")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error) {
			$db->begin();

			// Si on a selectionne une demande a copier, on realise la copie
			if (GETPOST('createmode') == 'copy' && GETPOST('copie_supplier_proposal')) {
				if ($object->fetch(GETPOST('copie_supplier_proposal')) > 0) {
					$object->ref = GETPOST('ref');
					$object->date_livraison = $date_delivery; // deprecated
					$object->delivery_date = $date_delivery;
					$object->shipping_method_id = GETPOST('shipping_method_id', 'int');
					$object->cond_reglement_id = GETPOST('cond_reglement_id');
					$object->mode_reglement_id = GETPOST('mode_reglement_id');
					$object->fk_account = GETPOST('fk_account', 'int');
					$object->remise_percent = price2num(GETPOST('remise_percent'), '', 2);
					$object->remise_absolue = price2num(GETPOST('remise_absolue'), 'MU', 2);
					$object->socid = GETPOST('socid');
					$object->fk_project = GETPOST('projectid', 'int');
					$object->model_pdf = GETPOST('model');
					$object->author = $user->id; // deprecated
					$object->note = GETPOST('note', 'restricthtml');
					$object->note_private = GETPOST('note', 'restricthtml');
					$object->statut = SupplierProposal::STATUS_DRAFT;
				} else {
					setEventMessages($langs->trans("ErrorFailedToCopyProposal", GETPOST('copie_supplier_proposal')), null, 'errors');
				}
			} else {
				$object->ref = GETPOST('ref');
				$object->date_livraison = $date_delivery;
				$object->delivery_date = $date_delivery;
				$object->demand_reason_id = GETPOST('demand_reason_id');
				$object->shipping_method_id = GETPOST('shipping_method_id', 'int');
				$object->cond_reglement_id = GETPOST('cond_reglement_id');
				$object->mode_reglement_id = GETPOST('mode_reglement_id');
				$object->fk_account = GETPOST('fk_account', 'int');
				$object->fk_project = GETPOST('projectid', 'int');
				$object->model_pdf = GETPOST('model');
				$object->author = $user->id; // deprecated
				$object->note = GETPOST('note', 'restricthtml');
				$object->note_private = GETPOST('note', 'restricthtml');

				$object->origin = GETPOST('origin');
				$object->origin_id = GETPOST('originid');

				// Multicurrency
				if (!empty($conf->multicurrency->enabled)) {
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

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects [$object->origin] = $object->origin_id;
					if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects'])) {
						$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
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
								$desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);

								// Positive line
								$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

								// Reset fk_parent_line for no child products and special product
								if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
									$fk_parent_line = 0;
								}

								// Extrafields
								if (method_exists($lines[$i], 'fetch_optionals')) {
									$lines[$i]->fetch_optionals();
									$array_options = $lines[$i]->array_options;
								}

								$result = $object->addline(
									$desc,
									$lines[$i]->subprice,
									$lines[$i]->qty,
									$lines[$i]->tva_tx,
									$lines[$i]->localtax1_tx,
									$lines[$i]->localtax2_tx,
									$lines[$i]->fk_product,
									$lines[$i]->remise_percent,
									'HT',
									0,
									$lines[$i]->info_bits,
									$product_type,
									$lines[$i]->rang,
									$lines[$i]->special_code,
									$fk_parent_line,
									$lines[$i]->fk_fournprice,
									$lines[$i]->pa_ht,
									$label,
									$array_options,
									$lines[$i]->ref_supplier,
									$lines[$i]->fk_unit
								);

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
					if (!$error) {
						$db->commit();

						// Define output language
						if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
							$outputlangs = $langs;
							$newlang = '';
							if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
								$newlang = GETPOST('lang_id', 'aZ09');
							}
							if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
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
	} elseif ($action == 'confirm_reopen' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		// Reopen proposal
		// prevent browser refresh from reopening proposal several times
		if ($object->statut == SupplierProposal::STATUS_SIGNED || $object->statut == SupplierProposal::STATUS_NOTSIGNED || $object->statut == SupplierProposal::STATUS_CLOSE) {
			$object->reopen($user, SupplierProposal::STATUS_VALIDATED);
		}
	} elseif ($action == 'close' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		// Close proposal
		// prevent browser refresh from reopening proposal several times
		if ($object->statut == SupplierProposal::STATUS_SIGNED) {
			$object->setStatut(SupplierProposal::STATUS_CLOSE);
		}
	} elseif ($action == 'setstatut' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		// Set accepted/refused
		if (!GETPOST('statut')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("CloseAs")), null, 'errors');
			$action = 'statut';
		} else {
			// prevent browser refresh from closing proposal several times
			if ($object->statut == SupplierProposal::STATUS_VALIDATED) {
				$object->cloture($user, GETPOST('statut'), GETPOST('note', 'restricthtml'));
			}
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'PROPOSAL_SUPPLIER_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO';
	$trackid = 'spro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->supplier_proposal->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';


	// Go back to draft
	if ($action == 'modif' && $usercancreate) {
		$object->setDraft($user);

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			// Define output language
			$outputlangs = $langs;
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	} elseif ($action == "setabsolutediscount" && $usercancreate) {
		if (GETPOST("remise_id", 'int')) {
			if ($object->id > 0) {
				$result = $object->insert_discount(GETPOST("remise_id", 'int'));
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	}

	// Add a product line
	if ($action == 'addline' && $usercancreate) {
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');
		$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

		$ref_supplier = GETPOST('fourn_ref', 'alpha');

		$prod_entry_mode = GETPOST('prod_entry_mode');
		if ($prod_entry_mode == 'free')	{
			$idprod = 0;
		} else {
			$idprod = GETPOST('idprod', 'int');
		}

		$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);		// Can be '1.2' or '1.2 (CODE)'

		$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
		$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'), 'CU', 2);
		$price_ttc = price2num(GETPOST('price_ttc'), 'MU', 2);
		$price_ttc_devise = price2num(GETPOST('multicurrency_price_ttc'), 'CU', 2);
		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS');

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

		if ($prod_entry_mode == 'free' && GETPOST('price_ht') < 0 && $qty < 0) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPrice'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && GETPOST('type') < 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
			$error++;
		}

		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && GETPOST('price_ht') === '' && GETPOST('price_ttc') === '' && $price_ht_devise === '') { 	// Unit price can be 0 but not ''. Also price can be negative for proposal.
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPrice")), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && empty($product_desc)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")), null, 'errors');
			$error++;
		}
		if (!$error && ($qty >= 0)) {
			$pu_ht = price2num($price_ht, 'MU');
			$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
			$price_min = 0;
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

			$db->begin();

			if ($prod_entry_mode != 'free' && empty($error)) {	// With combolist mode idprodfournprice is > 0 or -1. With autocomplete, idprodfournprice is > 0 or ''
				$productsupplier = new ProductFournisseur($db);

				$idprod = 0;
				if (GETPOST('idprodfournprice', 'alpha') == -1 || GETPOST('idprodfournprice', 'alpha') == '') {
					$idprod = -99; // Same behaviour than with combolist. When not select idprodfournprice is now -99 (to avoid conflict with next action that may return -1, -2, ...)
				}

				$reg = array();
				if (preg_match('/^idprod_([0-9]+)$/', GETPOST('idprodfournprice', 'alpha'), $reg)) {
					$idprod = $reg[1];
					$res = $productsupplier->fetch($idprod); // Load product from its id
					// Call to init some price properties of $productsupplier
					// So if a supplier price already exists for another thirdparty (first one found), we use it as reference price
					if (!empty($conf->global->SUPPLIER_TAKE_FIRST_PRICE_IF_NO_PRICE_FOR_CURRENT_SUPPLIER)) {
						$fksoctosearch = 0;
						$productsupplier->get_buyprice(0, -1, $idprod, 'none', $fksoctosearch); // We force qty to -1 to be sure to find if a supplier price exist
						if ($productsupplier->fourn_socid != $socid) {	// The price we found is for another supplier, so we clear supplier price
							$productsupplier->ref_supplier = '';
						}
					} else {
						$fksoctosearch = $object->thirdparty->id;
						$productsupplier->get_buyprice(0, -1, $idprod, 'none', $fksoctosearch); // We force qty to -1 to be sure to find if a supplier price exist
					}
				} elseif (GETPOST('idprodfournprice', 'alpha') > 0) {
					//$qtytosearch=$qty; 	   // Just to see if a price exists for the quantity. Not used to found vat.
					$qtytosearch = -1; // We force qty to -1 to be sure to find if the supplier price that exists
					$idprod = $productsupplier->get_buyprice(GETPOST('idprodfournprice', 'alpha'), $qtytosearch);
					$res = $productsupplier->fetch($idprod);
				}

				if ($idprod > 0) {
					$label = $productsupplier->label;

					// Define output language
					if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
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
						$desc = (!empty($productsupplier->multilangs[$outputlangs->defaultlang]["description"])) ? $productsupplier->multilangs[$outputlangs->defaultlang]["description"] : $productsupplier->description;
					} else {
						$desc = $productsupplier->description;
					}
					// if we use supplier description of the products
					if (!empty($productsupplier->desc_supplier) && !empty($conf->global->PRODUIT_FOURN_TEXTS)) {
						$desc = $productsupplier->desc_supplier;
					}

					//If text set in desc is the same as product descpription (as now it's preloaded) whe add it only one time
					if (trim($product_desc) == trim($desc) && !empty($conf->global->PRODUIT_AUTOFILL_DESC)) {
						$product_desc='';
					}

					if (!empty($product_desc) && !empty($conf->global->MAIN_NO_CONCAT_DESCRIPTION)) {
						$desc = $product_desc;
					}
					if (!empty($product_desc) && trim($product_desc) != trim($desc)) {
						$desc = dol_concatdesc($desc, $product_desc, '', !empty($conf->global->MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION));
					}

					$ref_supplier = $productsupplier->ref_supplier;

					// Get vat rate
					$tva_npr = 0;
					if (!GETPOSTISSET('tva_tx')) {	// If vat rate not provided from the form (the form has the priority)
						$tva_tx = get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice', 'alpha'));
						$tva_npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice', 'alpha'));
					}
					if (empty($tva_tx)) {
						$tva_npr = 0;
					}
					$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $object->thirdparty, $tva_npr);
					$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $object->thirdparty, $tva_npr);

					$type = $productsupplier->type;
					if (GETPOST('price_ht') != '' || GETPOST('price_ht_devise') != '') {
						$price_base_type = 'HT';
						$pu = price2num($price_ht, 'MU');
						$pu_devise = price2num($price_ht_devise, 'CU');
					} elseif (GETPOST('price_ttc') != '' || GETPOST('price_ttc_devise') != '') {
						$price_base_type = 'TTC';
						$pu = price2num($price_ttc, 'MU');
						$pu_devise = price2num($price_ttc_devise, 'CU');
					} else {
						$price_base_type = ($productsupplier->fourn_price_base_type ? $productsupplier->fourn_price_base_type : 'HT');
						if (empty($object->multicurrency_code) || ($productsupplier->fourn_multicurrency_code != $object->multicurrency_code)) {	// If object is in a different currency and price not in this currency
							$pu = $productsupplier->fourn_pu;
							$pu_devise = 0;
						} else {
							$pu = $productsupplier->fourn_pu;
							$pu_devise = $productsupplier->fourn_multicurrency_unitprice;
						}
					}

					if (empty($pu)) {
						$pu = 0; // If pu is '' or null, we force to have a numeric value
					}

					// If GETPOST('idprodfournprice') is a numeric, we can use it. If it is empty or if it is 'idprod_123', we should use -1 (not used)
					$fournprice = (is_numeric(GETPOST('idprodfournprice', 'alpha')) ? GETPOST('idprodfournprice', 'alpha') : -1);
					$buyingprice = 0;

					$result = $object->addline(
						$desc,
						($price_base_type == 'HT' ? $pu : 0),
						$qty,
						$tva_tx,
						$localtax1_tx,
						$localtax2_tx,
						$productsupplier->id,
						$remise_percent,
						$price_base_type,
						($price_base_type == 'TTC' ? $pu : 0),
						$tva_npr,
						$type,
						min($rank, count($object->lines) + 1),
						0,
						GETPOST('fk_parent_line'),
						$fournprice,
						$buyingprice,
						$label,
						$array_options,
						$ref_supplier,
						$productsupplier->fk_unit,
						'',
						0,
						$pu_devise,
						$date_start,
						$date_end
					);

					//var_dump($tva_tx);
					//var_dump($productsupplier->fourn_pu);
					//var_dump($price_base_type);exit;
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
				if ($idprod == -99 || $idprod == 0) {
					// Product not selected
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")).' '.$langs->trans("or").' '.$langs->trans("NoPriceDefinedForThisSupplier"), null, 'errors');
				}
				if ($idprod == -1) {
					// Quantity too low
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'errors');
				}
			} elseif ((GETPOST('price_ht') !== '' || GETPOST('price_ttc') !== '' || GETPOST('multicurrency_price_ht') != '') && empty($error)) {    // Free product.  // $price_ht is already set
				$pu_ht = price2num($price_ht, 'MU');
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');

				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				$tva_tx = str_replace('*', '', $tva_tx);
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');

				$fk_unit = GETPOST('units', 'alpha');

				if (!preg_match('/\((.*)\)/', $tva_tx)) {
					$tva_tx = price2num($tva_tx); // $txtva can have format '5,1' or '5.1' or '5.1(XXX)', we must clean only if '5,1'
				}

				// Local Taxes
				$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $object->thirdparty);
				$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $object->thirdparty);

				if (GETPOST('price_ht') != '' || GETPOST('price_ht_devise') != '') {
					$pu_ht = price2num($price_ht, 'MU'); // $pu_ht must be rounded according to settings
				} else {
					$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
					$pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU'); // $pu_ht must be rounded according to settings
				}
				$price_base_type = 'HT';
				$pu_ht_devise = price2num($price_ht_devise, 'CU');
				$info_bits = 0;

				$result = $object->addline(
					$desc,
					$pu_ht,
					$qty,
					$tva_tx,
					$localtax1_tx,
					$localtax2_tx,
					$idprod,
					$remise_percent,
					$price_base_type,
					$pu_ttc,
					$info_bits,
					$type,
					-1, // rang
					0, // special_code
					GETPOST('fk_parent_line'),
					$fournprice,
					$buyingprice,
					$label,
					$array_options,
					$ref_supplier,
					$fk_unit,
					'', // origin
					0, // origin_id
					$pu_ht_devise
				);
			}


			if (!$error && $result > 0) {
				$db->commit();

				$ret = $object->fetch($object->id); // Reload to get new records

				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$outputlangs = $langs;
					$newlang = '';
					if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
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

				unset($_POST['prod_entry_mode']);

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['remise_percent']);
				unset($_POST['pu']);
				unset($_POST['price_ht']);
				unset($_POST['multicurrency_price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['fourn_ref']);
				unset($_POST['tva_tx']);
				unset($_POST['label']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($localtax1_tx);
				unset($localtax2_tx);
				unset($_POST['np_marginRate']);
				unset($_POST['np_markRate']);
				unset($_POST['dp_desc']);
				unset($_POST['idprodfournprice']);
				unset($_POST['units']);

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
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && GETPOST('save') == $langs->trans("Save")) {
		// Mise a jour d'une ligne dans la demande de prix
		$vat_rate = (GETPOST('tva_tx') ?GETPOST('tva_tx') : 0);

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate)) {
			$info_bits |= 0x01;
		}

		// Clean parameters
		$description = dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml'));

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $mysoc, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $mysoc, $object->thirdparty);

		if (GETPOST('price_ht') != '') {
			$price_base_type = 'HT';
			$ht = price2num(GETPOST('price_ht'), '', 2);
		} else {
			$reg = array();
			$vatratecleaned = $vat_rate;
			if (preg_match('/^(.*)\s*\((.*)\)$/', $vat_rate, $reg)) {      // If vat is "xx (yy)"
				$vatratecleaned = trim($reg[1]);
				$vatratecode = $reg[2];
			}

			$ttc = price2num(GETPOST('price_ttc'), '', 2);
			$ht = $ttc / (1 + ($vatratecleaned / 100));
			$price_base_type = 'HT';
		}

		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), 'CU', 2);

		// Add buying price
		$fournprice = (GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = (GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we muste keep this value

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

		// Check minimum price
		$productid = GETPOST('productid', 'int');
		if (!empty($productid)) {
			$productsupplier = new ProductFournisseur($db);
			if (!empty($conf->global->SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY)) {
				if ($productid > 0 && $productsupplier->get_buyprice(0, price2num(GETPOST('qty')), $productid, 'none', GETPOST('socid', 'int')) < 0) {
					setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'warnings');
				}
			}

			$product = new Product($db);
			$res = $product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->thirdparty->price_level)) {
				$price_min = $product->multiprices_min [$object->thirdparty->price_level];
			}

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');
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

			$ref_supplier = GETPOST('fourn_ref', 'alpha');
			$fk_unit = GETPOST('units');

			$result = $object->updateline(
				GETPOST('lineid', 'int'),
				$ht,
				price2num(GETPOST('qty'), 'MS', 2),
				price2num(GETPOST('remise_percent'), '', 2),
				$vat_rate,
				$localtax1_rate,
				$localtax2_rate,
				$description,
				$price_base_type,
				$info_bits,
				$special_code,
				GETPOST('fk_parent_line', 'int'),
				0,
				$fournprice,
				$buyingprice,
				$label,
				$type,
				$array_options,
				$ref_supplier,
				$fk_unit,
				$pu_ht_devise
			);

			if ($result >= 0) {
				$db->commit();

				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					if (!empty($conf->global->MAIN_MULTILANGS)) {
						$outputlangs = new Translate("", $conf);
						$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					$ret = $object->fetch($id); // Reload to get new records
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
	} elseif ($action == 'updateline' && $usercancreate && GETPOST('cancel', 'alpha') == $langs->trans("Cancel")) {
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	} elseif ($action == 'classin' && $usercancreate) {
		// Set project
		$object->setProject(GETPOST('projectid'), 'int');
	} elseif ($action == 'setavailability' && $usercancreate) {
		// Delivery delay
		$result = $object->availability(GETPOST('availability_id'));
	} elseif ($action == 'setconditions' && $usercancreate) {
		// Terms of payments
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
	} elseif ($action == 'setremisepercent' && $usercancreate) {
		$result = $object->set_remise_percent($user, price2num(GETPOST('remise_percent'), '', 2));
	} elseif ($action == 'setremiseabsolue' && $usercancreate) {
		$result = $object->set_remise_absolue($user, price2num(GETPOST('remise_absolue'), 'MU', 2));
	} elseif ($action == 'setmode' && $usercancreate) {
		// Payment mode
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		// Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOST('calculation_mode', 'int'));
	} elseif ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->insertExtraFields('PROPOSAL_SUPPLIER_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}
}


/*
 * View
 */
$title = $langs->trans('CommRequest')." - ".$langs->trans('Card');
$help_url = 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur';
llxHeader('', $title, $help_url);

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = new FormMargin($db);
$companystatic = new Societe($db);
if (!empty($conf->project->enabled)) {
	$formproject = new FormProjets($db);
}

$now = dol_now();

// Add new askprice
if ($action == 'create') {
	$currency_code = $conf->currency;

	print load_fiche_titre($langs->trans("NewAskPrice"), '', 'supplier_proposal');

	$soc = new Societe($db);
	if ($socid > 0) {
		$res = $soc->fetch($socid);
	}

	// Load objectsrc
	if (!empty($origin) && !empty($originid)) {
		$element = $subelement = GETPOST('origin');
		if (preg_match('/^([^_]+)_([^_]+)/i', GETPOST('origin'), $regs)) {
			$element = $regs[1];
			$subelement = $regs[2];
		}

		// For compatibility
		if ($element == 'order' || $element == 'commande') {
			$element = $subelement = 'commande';
		}
		if ($element == 'propal') {
			$element = 'comm/propal';
			$subelement = 'propal';
		}

		dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

		$classname = ucfirst($subelement);
		$objectsrc = new $classname($db);
		$objectsrc->fetch($originid);
		if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
			$objectsrc->fetch_lines();
		}
		$objectsrc->fetch_thirdparty();

		$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
		$soc = $objectsrc->thirdparty;

		$cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0)); // TODO maybe add default value option
		$mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
		$remise_percent 	= (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_supplier_percent) ? $soc->remise_supplier_percent : 0));
		$remise_absolue 	= (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));

		// Replicate extrafields
		$objectsrc->fetch_optionals();
		$object->array_options = $objectsrc->array_options;

		if (!empty($conf->multicurrency->enabled)) {
			if (!empty($objectsrc->multicurrency_code)) {
				$currency_code = $objectsrc->multicurrency_code;
			}
			if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx)) {
				$currency_tx = $objectsrc->multicurrency_tx;
			}
		}
	} else {
		$cond_reglement_id 	= $soc->cond_reglement_supplier_id;
		$mode_reglement_id 	= $soc->mode_reglement_supplier_id;
		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) {
			$currency_code = $soc->multicurrency_code;
		}
	}

	$object = new SupplierProposal($db);

	print '<form name="addprop" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($origin != 'project' && $originid) {
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid" value="'.$originid.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans("Draft").'</td></tr>';

	// Third party
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
	if ($socid > 0) {
		print '<td colspan="2">';
		print $soc->getNomUrl(1, 'supplier');
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		print '</td>';
	} else {
		print '<td colspan="2">';
		print img_picto('', 'company').$form->select_company('', 'socid', 's.fournisseur=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
		// reload page to retrieve customer informations
		if (!empty($conf->global->RELOAD_PAGE_ON_SUPPLIER_CHANGE)) {
			print '<script>
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid;
				});
			});
			</script>';
		}
		print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=0&fournisseur=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
		print '</td>';
	}
	print '</tr>'."\n";

	if ($soc->id > 0) {
		// Discounts for third party
		print '<tr><td>'.$langs->trans('Discounts').'</td><td>';

		$absolute_discount = $soc->getAvailableDiscounts('', '', 0, 1);

		$thirdparty = $soc;
		$discount_type = 1;
		$backtopage = urlencode($_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid'));
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';
	}

	// Terms of payment
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$form->select_conditions_paiements(GETPOST('cond_reglement_id') > 0 ? GETPOST('cond_reglement_id') : $cond_reglement_id, 'cond_reglement_id', -1, 1);
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements(GETPOST('mode_reglement_id') > 0 ? GETPOST('mode_reglement_id') : $mode_reglement_id, 'mode_reglement_id');
	print '</td></tr>';

	// Bank Account
	if (!empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && !empty($conf->banque->enabled)) {
		print '<tr><td>'.$langs->trans('BankAccount').'</td><td colspan="2">';
		$form->select_comptes(GETPOST('fk_account') > 0 ? GETPOST('fk_account', 'int') : $fk_account, 'fk_account', 0, '', 1);
		print '</td></tr>';
	}

	// Shipping Method
	if (!empty($conf->expedition->enabled)) {
		print '<tr><td>'.$langs->trans('SendingMethod').'</td><td colspan="2">';
		print $form->selectShippingMethod(GETPOST('shipping_method_id') > 0 ? GETPOST('shipping_method_id', 'int') : "", 'shipping_method_id', '', 1);
		print '</td></tr>';
	}

	// Delivery date (or manufacturing)
	print '<tr><td>'.$langs->trans("DeliveryDate").'</td>';
	print '<td colspan="2">';
	$datedelivery = dol_mktime(0, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));
	if (!empty($conf->global->DATE_LIVRAISON_WEEK_DELAY)) {
		$tmpdte = time() + ((7 * $conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		$syear = date("Y", $tmpdte);
		$smonth = date("m", $tmpdte);
		$sday = date("d", $tmpdte);
		print $form->selectDate($syear."-".$smonth."-".$sday, 'liv_', '', '', '', "addask");
	} else {
		print $form->selectDate($datedelivery ? $datedelivery : -1, 'liv_', '', '', '', "addask", 1, 1);
	}
	print '</td></tr>';


	// Model
	print '<tr>';
	print '<td>'.$langs->trans("DefaultModel").'</td>';
	print '<td colspan="2">';
	$list = ModelePDFSupplierProposal::liste_modeles($db);
	$preselected = (!empty($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_DEFAULT) ? $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_DEFAULT : $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF);
	print $form->selectarray('model', $list, $preselected, 0, 0, 0, '', 0, 0, 0, '', '', 1);
	print "</td></tr>";

	// Project
	if (!empty($conf->project->enabled)) {
		$langs->load("projects");

		$formproject = new FormProjets($db);

		if ($origin == 'project') {
			$projectid = ($originid ? $originid : 0);
		}

		print '<tr>';
		print '<td>'.$langs->trans("Project").'</td><td colspan="2">';
		print img_picto('', 'project').$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
		print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';

		print '</td>';
		print '</tr>';
	}

	// Multicurrency
	if (!empty($conf->multicurrency->enabled)) {
		print '<tr>';
		print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		print $form->selectMultiCurrency($currency_code, 'multicurrency_code');
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('colspan' => ' colspan="3"', 'cols' => 3);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}


	// Lines from source
	if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
		// TODO for compatibility
		if ($origin == 'contrat') {
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue = $remise_absolue;
			$objectsrc->remise_percent = $remise_percent;
			$objectsrc->update_price(1, - 1, 1);
		}

		print "\n<!-- ".$classname." info -->";
		print "\n";
		print '<input type="hidden" name="amount"   value="'.$objectsrc->total_ht.'">'."\n";
		print '<input type="hidden" name="total"    value="'.$objectsrc->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"      value="'.$objectsrc->total_tva.'">'."\n";
		print '<input type="hidden" name="origin"   value="'.$objectsrc->element.'">';
		print '<input type="hidden" name="originid" value="'.$objectsrc->id.'">';

		print '<tr><td>'.$langs->trans('CommRequest').'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) { 		// Localtax1
			print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) { 		// Localtax2
			print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
		}
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";

		if (!empty($conf->multicurrency->enabled)) {
			print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td colspan="2">'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
			print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td colspan="2">'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
			print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td colspan="2">'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
		}
	}

	print "</table>\n";


	/*
	 * Combobox pour la fonction de copie
	  */

	if (empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE)) {
		print '<input type="hidden" name="createmode" value="empty">';
	}

	if (!empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE)) {
		print '<br><table>';

		// For backward compatibility
		print '<tr>';
		print '<td><input type="radio" name="createmode" value="copy"></td>';
		print '<td>'.$langs->trans("CopyAskFrom").' </td>';
		print '<td>';
		$liste_ask = array();
		$liste_ask [0] = '';

		$sql = "SELECT p.rowid as id, p.ref, s.nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal p";
		$sql .= ", ".MAIN_DB_PREFIX."societe s";
		$sql .= " WHERE s.rowid = p.fk_soc";
		$sql .= " AND p.entity = ".$conf->entity;
		$sql .= " AND p.fk_statut <> ".SupplierProposal::STATUS_DRAFT;
		$sql .= " ORDER BY Id";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$askPriceSupplierRefAndSocName = $row [1]." - ".$row [2];
				$liste_ask [$row [0]] = $askPriceSupplierRefAndSocName;
				$i++;
			}
			print $form->selectarray("copie_supplier_proposal", $liste_ask, 0);
		} else {
			dol_print_error($db);
		}
		print '</td></tr>';

		print '<tr><td class="tdtop"><input type="radio" name="createmode" value="empty" checked></td>';
		print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyAsk").'</td></tr>';
	}

	if (!empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE)) {
		print '</table>';
	}

	print dol_get_fiche_end();

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
} else {
	/*
	 * Show object in view mode
	 */

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$head = supplier_proposal_prepare_head($object);
	print dol_get_fiche_head($head, 'comm', $langs->trans('CommRequest'), -1, 'supplier_proposal');

	$formconfirm = '';

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' =>
			// 1),
			array(
				'type' => 'other',
				'name' => 'socid',
				'label' => $langs->trans("SelectThirdParty"),
				'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', 's.fournisseur=1'))
			);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	} elseif ($action == 'delete') {
		// Confirm delete
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteAsk'), $langs->trans('ConfirmDeleteAsk', $object->ref), 'confirm_delete', '', 0, 1);
	} elseif ($action == 'reopen') {
		// Confirm reopen
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenAsk', $object->ref), 'confirm_reopen', '', 0, 1);
	} elseif ($action == 'ask_deleteline') {
		// Confirmation delete product/service line
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	} elseif ($action == 'validate') {
		// Confirm validate askprice
		$error = 0;

		// on verifie si l'objet est en numerotation provisoire
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$numref = $object->getNextNumRef($soc);
			if (empty($numref)) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateAsk', $numref);
		if (!empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('PROPOSAL_SUPPLIER_VALIDATE', $object->socid, $object);
		}

		if (!$error) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateAsk'), $text, 'confirm_validate', '', 0, 1);
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


	// Supplier proposal card
	$linkback = '<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	//$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreateorder, 'string', '', 0, 1);
	//$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreateorder, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'supplier');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
		$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherProposals").'</a>)';
	}
	// Project
	if (!empty($conf->project->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($usercancreate) {
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $object->socid : -1), $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
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


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Relative and absolute discounts
	if (!empty($conf->global->FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS)) {
		$filterabsolutediscount = "fk_invoice_supplier_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	} else {
		$filterabsolutediscount = "fk_invoice_supplier_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS PAID)%')";
		$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS PAID)%')";
	}

	print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td>';

	$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount, 0, 1);
	$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote, 0, 1);
	$absolute_discount = price2num($absolute_discount, 'MT');
	$absolute_creditnote = price2num($absolute_creditnote, 'MT');

	$thirdparty = $soc;
	$discount_type = 1;
	$backtopage = urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
	include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

	print '</td></tr>';

	// Payment term
	print '<tr><td class="titlefield">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($action != 'editconditions' && $object->statut != SupplierProposal::STATUS_NOTSIGNED) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetConditions'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td class="valuefield">';
	if ($action == 'editconditions') {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
	} else {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none', 1);
	}
	print '</td>';
	print '</tr>';

	// Delivery date
	$langs->load('deliveries');
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DeliveryDate');
	print '</td>';
	if ($action != 'editdate_livraison' && $object->statut == SupplierProposal::STATUS_VALIDATED) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDeliveryDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td class="valuefield">';
	if ($action == 'editdate_livraison') {
		print '<form name="editdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post" class="formconsumeproduce">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		print $form->selectDate($object->delivery_date, 'liv_', '', '', '', "editdate_livraison");
		print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		print dol_print_date($object->delivery_date, 'daytext');
	}
	print '</td>';
	print '</tr>';

	// Payment mode
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && $object->statut != SupplierProposal::STATUS_NOTSIGNED) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMode'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td class="valuefield">';
	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'DBIT', 1, 1);
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
	}
	print '</td></tr>';

	// Multicurrency
	if (!empty($conf->multicurrency->enabled)) {
		// Multicurrency code
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
		print '</td>';
		if ($action != 'editmulticurrencycode' && $object->statut == $object::STATUS_VALIDATED) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
		}
		print '</tr></table>';
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
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
			print '</td>';
			if ($action != 'editmulticurrencyrate' && $object->statut == $object::STATUS_VALIDATED && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
			}
			print '</tr></table>';
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

	/* Not for supplier proposals
	if ($soc->outstanding_limit)
	{
		// Outstanding Bill
		print '<tr><td>';
		print $langs->trans('OutstandingBill');
		print '</td><td class="valuefield">';
		$arrayoutstandingbills = $soc->getOutstandingBills('supplier');
		$outstandingBills = $arrayoutstandingbills['opened'];
		print price($soc->outstanding_limit, 0, '', 1, - 1, - 1, $conf->currency);
		print '</td>';
		print '</tr>';
	}*/

	if (!empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && !empty($conf->banque->enabled)) {
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

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency)) {
		// Multicurrency Amount HT
		print '<tr><td class="titlefieldmiddle">'.$form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0).'</td>';
		print '<td class="valuefield nowrap right amountcard">'.price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';

		// Multicurrency Amount VAT
		print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0).'</td>';
		print '<td class="valuefield nowrap right amountcard">'.price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';

		// Multicurrency Amount TTC
		print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0).'</td>';
		print '<td class="valuefield nowrap right amountcard">'.price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';
	}

	// Amount HT
	print '<tr><td class="titlefieldmiddle">'.$langs->trans('AmountHT').'</td>';
	print '<td class="valuefield nowrap right amountcard">'.price($object->total_ht, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
	print '</tr>';

	// Amount VAT
	print '<tr><td>'.$langs->trans('AmountVAT').'</td>';
	print '<td class="valuefield nowrap right amountcard">'.price($object->total_tva, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
	print '</tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) { 	// Localtax1
		print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
		print '<td class="valuefield nowrap right amountcard">'.price($object->total_localtax1, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
		print '</tr>';
	}
	if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) { 	// Localtax2
		print '<tr><td height="10">'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
		print '<td class="valuefield nowrap right amountcard">'.price($object->total_localtax2, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
		print '</tr>';
	}

	// Amount TTC
	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td>';
	print '<td class="valuefield nowrap right amountcard">'.price($object->total_ttc, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
	print '</tr>';

	print '</table>';

	// Margin Infos
	/*if (! empty($conf->margin->enabled)) {
	   $formmargin->displayMarginInfos($object);
	}*/

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div><br>';

	if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	/*
	 * Lines
	 */

	// Show object lines
	$result = $object->getLinesArray();

	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#add' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
	<input type="hidden" name="token" value="' . newToken().'">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="' . $object->id.'">
	';

	if (!empty($conf->use_javascript_ajax) && $object->statut == SupplierProposal::STATUS_DRAFT) {
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	// Add free products/services form
	global $forceall, $senderissupplier, $dateSelector, $inputalsopricewithtax;
	$forceall = 1; $dateSelector = 0; $inputalsopricewithtax = 1;
	$senderissupplier = 2; // $senderissupplier=2 is same than 1 but disable test on minimum qty.
	if (!empty($conf->global->SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY)) {
		$senderissupplier = 1;
	}

	if (!empty($object->lines)) {
		$ret = $object->printObjectLines($action, $soc, $mysoc, $lineid, $dateSelector);
	}

	// Form to add new line
	if ($object->statut == SupplierProposal::STATUS_DRAFT && $usercancreate) {
		if ($action != 'editline') {
			// Add products/services form

			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			if (empty($reshook))
				$object->formAddObjectLine($dateSelector, $soc, $mysoc);
		}
	}

	print '</table>';
	print '</div>';
	print "</form>\n";

	print dol_get_fiche_end();

	if ($action == 'statut') {
		// Form to set proposal accepted/refused
		$form_close = '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST" id="formacceptrefuse" class="formconsumeproduce paddingbottom paddingleft paddingright">';
		$form_close .= '<input type="hidden" name="token" value="'.newToken().'">';
		$form_close .= '<input type="hidden" name="action" value="setstatut">';

		if (!empty($conf->global->SUPPLIER_PROPOSAL_UPDATE_PRICE_ON_SUPPlIER_PROPOSAL)) {
			$form_close .= '<p class="notice">'.$langs->trans('SupplierProposalRefFournNotice').'</p>'; // TODO Suggest a permanent checkbox instead of option
		}
		$form_close .= '<table class="border centpercent marginleftonly marginrightonly">';
		$form_close .= '<tr><td>'.$langs->trans("CloseAs").'</td><td class="left">';
		$form_close .= '<select id="statut" name="statut" class="flat">';
		$form_close .= '<option value="0">&nbsp;</option>';
		$form_close .= '<option value="2">'.$langs->trans('SupplierProposalStatusSigned').'</option>';
		$form_close .= '<option value="3">'.$langs->trans('SupplierProposalStatusNotSigned').'</option>';
		$form_close .= '</select>';
		$form_close .= '</td></tr>';
		$form_close .= '<tr><td class="left">'.$langs->trans('Note').'</td><td class="left"><textarea cols="70" rows="'.ROWS_3.'" wrap="soft" name="note">';
		$form_close .= $object->note_private;
		$form_close .= '</textarea></td></tr>';
		$form_close .= '</table>';
		$form_close .= $form->buttonsSaveCancel();
		$form_close .= '<a id="acceptedrefused">&nbsp;</a>';
		$form_close .= '</form>';

		print $form_close;
	}

	/*
	 * Boutons Actions
	 */
	if ($action != 'presend') {
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
																									   // modified by hook
		if (empty($reshook)) {
			if ($action != 'statut' && $action != 'editline') {
				// Validate
				if ($object->statut == SupplierProposal::STATUS_DRAFT && $object->total_ttc >= 0 && count($object->lines) > 0 && $usercanvalidate) {
					if (count($object->lines) > 0) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a></div>';
					}
					// else print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans('Validate').'</a>';
				}

				// Edit
				if ($object->statut == SupplierProposal::STATUS_VALIDATED && $usercancreate) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=modif&token='.newToken().'">'.$langs->trans('Modify').'</a></div>';
				}

				// ReOpen
				if (($object->statut == SupplierProposal::STATUS_SIGNED || $object->statut == SupplierProposal::STATUS_NOTSIGNED || $object->statut == SupplierProposal::STATUS_CLOSE) && $usercanclose) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#reopen').'"';
					print '>'.$langs->trans('ReOpen').'</a></div>';
				}

				// Send
				if (empty($user->socid)) {
					if ($object->statut == SupplierProposal::STATUS_VALIDATED || $object->statut == SupplierProposal::STATUS_SIGNED) {
						if ($usercansend) {
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">'.$langs->trans('SendMail').'</a></div>';
						}
					}
				}

				// Create an order
				if (((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) && $object->statut == SupplierProposal::STATUS_SIGNED) {
					if ($usercancreateorder) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddSupplierOrderShort").'</a></div>';
					}
				}

				// Set accepted/refused
				if ($object->statut == SupplierProposal::STATUS_VALIDATED && $usercanclose) {
					print '<div class="inline-block divButAction"><a class="butAction reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=statut'.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#acceptedrefused').'"';
					print '>'.$langs->trans('SetAcceptedRefused').'</a></div>';
				}

				// Close
				if ($object->statut == SupplierProposal::STATUS_SIGNED && $usercanclose) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close'.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#close').'"';
					print '>'.$langs->trans('Close').'</a></div>';
				}

				// Clone
				if ($usercancreate) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&object='.$object->element.'">'.$langs->trans("ToClone").'</a></div>';
				}

				// Delete
				if (($object->statut == SupplierProposal::STATUS_DRAFT && $usercancreate) || $usercandelete) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'"';
					print '>'.$langs->trans('Delete').'</a></div>';
				}
			}
		}

		print '</div>';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';

		/*
		 * Generated documents
		 */
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->supplier_proposal->dir_output."/".dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $usercanread;
		$delallowed = $usercancreate;

		print $formfile->showdocuments('supplier_proposal', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang);


		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('supplier_proposal'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'supplier_proposal', $socid, 1);

		print '</div></div>';
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'supplier_proposal_send';
	$defaulttopic = 'SendAskRef';
	$diroutput = $conf->supplier_proposal->dir_output;
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO';
	$trackid = 'spro'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
