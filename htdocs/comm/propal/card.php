<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2016 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2018 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020	    Nicolas ZABOURI         <info@inovea-conseil.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

if (!empty($conf->variants->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'sendings', 'other'));
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');
if (!empty($conf->margin->enabled))
	$langs->load('margins');

$error = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOST('lineid', 'int');
$contactid = GETPOST('contactid', 'int');
$projectid = GETPOST('projectid', 'int');

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES = 4;

// Security check
if (!empty($user->socid)) $socid = $user->socid;
$result = restrictedArea($user, 'propal', $id);

$object = new Propal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret <= 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('propalcard', 'globalcard'));

$usercanread = $user->rights->propal->lire;
$usercancreate = $user->rights->propal->creer;
$usercandelete = $user->rights->propal->supprimer;

$usercanclose = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $usercancreate) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->propal_advance->close)));
$usercanvalidate = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $usercancreate) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->propal_advance->validate)));
$usercansend = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send);

$usercancreateorder = $user->rights->commande->creer;
$usercancreateinvoice = $user->rights->facture->creer;
$usercancreatecontract = $user->rights->contrat->creer;
$usercancreateintervention = $user->rights->ficheinter->creer;
$usercancreatepurchaseorder = $user->rights->fournisseur->commande->creer;

$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (!empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate)
	{
		if (!GETPOST('socid', 3))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY)) {
					//Get difference between old and new delivery date and change lines according to difference
					$date_delivery = dol_mktime(12, 0, 0,
						GETPOST('date_deliverymonth', 'int'),
						GETPOST('date_deliveryday', 'int'),
						GETPOST('date_deliveryyear', 'int')
					);
					$date_delivery_old = (empty($object->delivery_date) ? $object->date_livraison : $object->delivery_date);
					if (!empty($date_delivery_old) && !empty($date_delivery))
					{
						//Attempt to get the date without possible hour rounding errors
						$old_date_delivery = dol_mktime(12, 0, 0,
							dol_print_date($date_delivery_old, '%m'),
							dol_print_date($date_delivery_old, '%d'),
							dol_print_date($date_delivery_old, '%Y')
						);
						//Calculate the difference and apply if necessary
						$difference = $date_delivery - $old_date_delivery;
						if ($difference != 0)
						{
							$object->date_livraison = $date_delivery;
							$object->delivery_date = $date_delivery;
							foreach ($object->lines as $line)
							{
								if (isset($line->date_start)) $line->date_start = $line->date_start + $difference;
								if (isset($line->date_end)) $line->date_end = $line->date_end + $difference;
							}
						}
					}
				}

				$result = $object->createFromClone($user, $socid, (GETPOSTISSET('entity') ? GETPOST('entity', 'int') : null));
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit();
				} else {
					if (count($object->errors) > 0) setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	} // Delete proposal
	elseif ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete)
	{
		$result = $object->delete($user);
		if ($result > 0) {
			header('Location: '.DOL_URL_ROOT.'/comm/propal/list.php?restore_lastsearch_values=1');
			exit();
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} // Remove line
	elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate)
	{
		$result = $object->deleteline($lineid);
		// reorder lines
		if ($result)
			$object->line_order(true);

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			// Define output language
			$outputlangs = $langs;
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			if ($ret > 0) $object->fetch_thirdparty();
			$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit();
	} // Validation
	elseif ($action == 'confirm_validate' && $confirm == 'yes' && $usercanvalidate)
	{
		$result = $object->valid($user);
		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
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
			if (count($object->errors) > 0) setEventMessages($object->error, $object->errors, 'errors');
			else setEventMessages($langs->trans($object->error), null, 'errors');
		}
	} elseif ($action == 'setdate' && $usercancreate)
	{
		$datep = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

		if (empty($datep)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		}

		if (!$error) {
			$result = $object->set_date($user, $datep);
			if ($result < 0)
				dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setecheance' && $usercancreate)
	{
		$result = $object->set_echeance($user, dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']));
		if ($result < 0)
			dol_print_error($db, $object->error);
	} elseif ($action == 'setdate_livraison' && $usercancreate)
	{
		$result = $object->setDeliveryDate($user, dol_mktime(12, 0, 0, $_POST['date_livraisonmonth'], $_POST['date_livraisonday'], $_POST['date_livraisonyear']));
		if ($result < 0)
			dol_print_error($db, $object->error);
	} // Positionne ref client
	elseif ($action == 'setref_client' && $usercancreate)
	{
		$result = $object->set_ref_client($user, GETPOST('ref_client'));
		if ($result < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} // Set incoterm
	elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled) && $usercancreate)
	{
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	} // Create proposal
	elseif ($action == 'add' && $usercancreate)
	{
		$object->socid = $socid;
		$object->fetch_thirdparty();

		$datep = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
		$date_delivery = dol_mktime(12, 0, 0, GETPOST('date_livraisonmonth'), GETPOST('date_livraisonday'), GETPOST('date_livraisonyear'));
		$duration = GETPOST('duree_validite', 'int');

		if (empty($datep)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
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

		if (!$error)
		{
			$db->begin();

			// If we select proposal to clone during creation (when option PROPAL_CLONE_ON_CREATE_PAGE is on)
			if (GETPOST('createmode') == 'copy' && GETPOST('copie_propal'))
			{
				if ($object->fetch(GETPOST('copie_propal', 'int')) > 0) {
					$object->ref = GETPOST('ref');
					$object->datep = $datep;
					$object->date = $datep;
					$object->date_livraison = $date_delivery; // deprecated
					$object->delivery_date = $date_delivery;
					$object->availability_id = GETPOST('availability_id');
					$object->demand_reason_id = GETPOST('demand_reason_id');
					$object->fk_delivery_address = GETPOST('fk_address', 'int');
					$object->shipping_method_id = GETPOST('shipping_method_id', 'int');
					$object->duree_validite = $duration;
					$object->cond_reglement_id = GETPOST('cond_reglement_id');
					$object->mode_reglement_id = GETPOST('mode_reglement_id');
					$object->fk_account = GETPOST('fk_account', 'int');
					$object->remise_percent = GETPOST('remise_percent');
					$object->remise_absolue = GETPOST('remise_absolue');
					$object->socid = GETPOST('socid', 'int');
					$object->contact_id = GETPOST('contactid', 'int');
					$object->fk_project = GETPOST('projectid', 'int');
					$object->model_pdf = GETPOST('model', 'alphanohtml');
					$object->author = $user->id; // deprecated
					$object->user_author_id = $user->id;
					$object->note_private = GETPOST('note_private', 'restricthtml');
					$object->note_public = GETPOST('note_public', 'restricthtml');
					$object->statut = Propal::STATUS_DRAFT;
					$object->fk_incoterms = GETPOST('incoterm_id', 'int');
					$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				} else {
					setEventMessages($langs->trans("ErrorFailedToCopyProposal", GETPOST('copie_propal')), null, 'errors');
				}
			} else {
				$object->ref = GETPOST('ref');
				$object->ref_client = GETPOST('ref_client');
				$object->datep = $datep;
				$object->date = $datep;
				$object->date_livraison = $date_delivery;
				$object->delivery_date = $date_delivery;
				$object->availability_id = GETPOST('availability_id', 'int');
				$object->demand_reason_id = GETPOST('demand_reason_id', 'int');
				$object->fk_delivery_address = GETPOST('fk_address', 'int');
				$object->shipping_method_id = GETPOST('shipping_method_id', 'int');
				$object->duree_validite = price2num(GETPOST('duree_validite', 'alpha'));
				$object->cond_reglement_id = GETPOST('cond_reglement_id', 'int');
				$object->mode_reglement_id = GETPOST('mode_reglement_id', 'int');
				$object->fk_account = GETPOST('fk_account', 'int');
				$object->contact_id = GETPOST('contactid', 'int');
				$object->fk_project = GETPOST('projectid', 'int');
				$object->model_pdf = GETPOST('model');
				$object->author = $user->id; // deprecated
				$object->note_private = GETPOST('note_private', 'restricthtml');
				$object->note_public = GETPOST('note_public', 'restricthtml');
				$object->fk_incoterms = GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

				$object->origin = GETPOST('origin');
				$object->origin_id = GETPOST('originid');

				// Multicurrency
				if (!empty($conf->multicurrency->enabled))
				{
					$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				}

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
					$action = 'create';
				}
			}

			if (!$error)
			{
				if ($origin && $originid)
				{
					// Parse element/subelement (ex: project_task)
					$element = $subelement = $origin;
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
					if ($element == 'inter') {
						$element = $subelement = 'ficheinter';
					}
					if ($element == 'shipping') {
						$element = $subelement = 'expedition';
					}

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects [$object->origin] = $object->origin_id;
					if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects'])) {
						$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
					}

					$id = $object->create($user);
					if ($id > 0)
					{
						dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

						$classname = ucfirst($subelement);
						$srcobject = new $classname($db);

						dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
						$result = $srcobject->fetch($object->origin_id);

						if ($result > 0)
						{
							$lines = $srcobject->lines;
							if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
							{
								$srcobject->fetch_lines();
								$lines = $srcobject->lines;
							}

							$fk_parent_line = 0;
							$num = count($lines);
							for ($i = 0; $i < $num; $i++)
							{
								$label = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);

								// Positive line
								$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

								// Date start
								$date_start = false;
								if ($lines[$i]->date_debut_prevue)
									$date_start = $lines[$i]->date_debut_prevue;
								if ($lines[$i]->date_debut_reel)
									$date_start = $lines[$i]->date_debut_reel;
								if ($lines[$i]->date_start)
									$date_start = $lines[$i]->date_start;

								// Date end
								$date_end = false;
								if ($lines[$i]->date_fin_prevue)
									$date_end = $lines[$i]->date_fin_prevue;
								if ($lines[$i]->date_fin_reel)
									$date_end = $lines[$i]->date_fin_reel;
								if ($lines[$i]->date_end)
									$date_end = $lines[$i]->date_end;

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
								if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

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
							if ($reshook < 0)
								$error++;
						} else {
							setEventMessages($srcobject->error, $srcobject->errors, 'errors');
							$error++;
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				} // Standard creation
				else {
					$id = $object->create($user);
				}

				if ($id > 0)
				{
					// Insert default contacts if defined
					if (GETPOST('contactid') > 0) {
						$result = $object->add_contact(GETPOST('contactid'), 'CUSTOMER', 'external');
						if ($result < 0)
						{
							$error++;
							setEventMessages($langs->trans("ErrorFailedToAddContact"), null, 'errors');
						}
					}

					if (!empty($conf->global->PROPOSAL_AUTO_ADD_AUTHOR_AS_CONTACT))
					{
						$result = $object->add_contact($user->id, 'SALESREPFOLL', 'internal');
						if ($result < 0)
						{
							$error++;
							setEventMessages($langs->trans("ErrorFailedToAddUserAsContact"), null, 'errors');
						}
					}

					if (!$error)
					{
						$db->commit();

						// Define output language
						if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
						{
							$outputlangs = $langs;
							$newlang = '';
							if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
							if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}
							$model = $object->model_pdf;

							$ret = $object->fetch($id); // Reload to get new records
							$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
							if ($result < 0) dol_print_error($db, $result);
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
	} // Classify billed
	elseif ($action == 'classifybilled' && $usercanclose)
	{
		$db->begin();

		$result = $object->cloture($user, $object::STATUS_BILLED, '');
		if ($result < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}

		if (!$error)
		{
			$db->commit();
		} else {
			$db->rollback();
		}
	} // Close proposal
	elseif ($action == 'confirm_closeas' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		if (!(GETPOST('statut', 'int') > 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CloseAs")), null, 'errors');
			$action = 'closeas';
		} else {
			// prevent browser refresh from closing proposal several times
			if ($object->statut == $object::STATUS_VALIDATED)
			{
				$db->begin();

				$result = $object->cloture($user, GETPOST('statut', 'int'), GETPOST('note_private', 'restricthtml'));
				if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				if (!$error)
				{
					$db->commit();
				} else {
					$db->rollback();
				}
			}
		}
	} // Reopen proposal
	elseif ($action == 'confirm_reopen' && $usercanclose && !GETPOST('cancel', 'alpha')) {
		// prevent browser refresh from reopening proposal several times
		if ($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED)
		{
			$db->begin();

			$result = $object->reopen($user, 1);
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}

			if (!$error)
			{
				$db->commit();
			} else {
				$db->rollback();
			}
		}
	} // add lines from objectlinked
	elseif ($action == 'import_lines_from_object'
		&& $user->rights->propal->creer
		&& $object->statut == Propal::STATUS_DRAFT
		)
	{
		$fromElement = GETPOST('fromelement');
		$fromElementid = GETPOST('fromelementid');
		$importLines = GETPOST('line_checkbox');

		if (!empty($importLines) && is_array($importLines) && !empty($fromElement) && ctype_alpha($fromElement) && !empty($fromElementid))
		{
			if ($fromElement == 'commande')
			{
				dol_include_once('/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'OrderLine';
			} elseif ($fromElement == 'propal')
			{
				dol_include_once('/comm/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'PropaleLigne';
			}
			$nextRang = count($object->lines) + 1;
			$importCount = 0;
			$error = 0;
			foreach ($importLines as $lineId)
			{
				$lineId = intval($lineId);
				$originLine = new $lineClassName($db);
				if (intval($fromElementid) > 0 && $originLine->fetch($lineId) > 0)
				{
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
					$ventil = 0;
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

			if ($error)
			{
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
	if ($action == 'modif' && $usercancreate)
	{
		$object->setDraft($user);

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			// Define output language
			$outputlangs = $langs;
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			if ($ret > 0) $object->fetch_thirdparty();
			$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	} elseif ($action == "setabsolutediscount" && $usercancreate) {
		if ($_POST["remise_id"]) {
			if ($object->id > 0) {
				$result = $object->insert_discount($_POST["remise_id"]);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'aZ09') && GETPOST('vatforalllines', 'alpha')) {
		// Define vat_rate
		$vat_rate = (GETPOST('vatforalllines') ? GETPOST('vatforalllines') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		foreach ($object->lines as $line) {
			$result = $object->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $vat_rate, $localtax1_rate, $localtax2_rate, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
		}
	} elseif ($action == 'addline' && $usercancreate) {		// Add line
		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ?GETPOST('dp_desc', 'restricthtml') : '');
		$price_ht = price2num(GETPOST('price_ht'));
		$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'));
		$prod_entry_mode = GETPOST('prod_entry_mode');
		if ($prod_entry_mode == 'free')
		{
			$idprod = 0;
			$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		} else {
			$idprod = GETPOST('idprod', 'int');
			$tva_tx = '';
		}

		$qty = price2num(GETPOST('qty'.$predef), 'MS');
		$remise_percent = GETPOST('remise_percent'.$predef);
		if (empty($remise_percent)) $remise_percent = 0;

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

		if ($prod_entry_mode == 'free' && empty($idprod) && GETPOST('type') < 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
			$error++;
		}

		if ($prod_entry_mode == 'free' && empty($idprod) && $price_ht === '' && $price_ht_devise === '') 	// Unit price can be 0 but not ''. Also price can be negative for proposal.
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && empty($product_desc)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")), null, 'errors');
			$error++;
		}

		if (!$error && !empty($conf->variants->enabled) && $prod_entry_mode != 'free') {
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

		if (!$error && ($qty >= 0) && (!empty($product_desc) || !empty($idprod))) {
			$pu_ht = 0;
			$pu_ttc = 0;
			$price_min = 0;
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

			$db->begin();

			// $tva_tx can be 'x.x (XXX)'

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $tva_tx par celui du produit
			// Replaces $fk_unit with the product unit
			if (!empty($idprod)) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Update if prices fields are defined
				$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
				$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
				if (empty($tva_tx)) $tva_npr = 0;

				// Price unique per product
				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_min = $prod->price_min;
				$price_base_type = $prod->price_base_type;

				// If price per segment
				if (!empty($conf->global->PRODUIT_MULTIPRICES) && $object->thirdparty->price_level)
				{
					$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
					$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
					$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
					$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
					if (!empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
					{
						if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx = $prod->multiprices_tva_tx[$object->thirdparty->price_level];
						if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr = $prod->multiprices_recuperableonly[$object->thirdparty->price_level];
					}
				} // If price per customer
				elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES))
				{
					require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

					$prodcustprice = new Productcustomerprice($db);

					$filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

					$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
					if ($result) {
						// If there is some prices specific to the customer
						if (count($prodcustprice->lines) > 0) {
							$pu_ht = price($prodcustprice->lines[0]->price);
							$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
							$price_min =  price($prodcustprice->lines[0]->price_min);
							$price_base_type = $prodcustprice->lines[0]->price_base_type;
							$tva_tx = ($prodcustprice->lines[0]->default_vat_code ? $prodcustprice->lines[0]->tva_tx.' ('.$prodcustprice->lines[0]->default_vat_code.' )' : $prodcustprice->lines[0]->tva_tx);
							if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) $tva_tx .= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
							$tva_npr = $prodcustprice->lines[0]->recuperableonly;
							if (empty($tva_tx)) $tva_npr = 0;
						}
					}
				} // If price per quantity
				elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
				{
					if ($prod->prices_by_qty[0])	// yes, this product has some prices per quantity
					{
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOST('pbq', 'int');

						// Search price into product_price_by_qty from $prod->id
						foreach ($prod->prices_by_qty_list[0] as $priceforthequantityarray)
						{
							if ($priceforthequantityarray['rowid'] != $pqp) continue;
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT')
							{
								$pu_ht = $priceforthequantityarray['unitprice'];
							} else {
								$pu_ttc = $priceforthequantityarray['unitprice'];
							}
							// Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
							break;
						}
					}
				} // If price per quantity and customer
				elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
				{
					if ($prod->prices_by_qty[$object->thirdparty->price_level]) // yes, this product has some prices per quantity
					{
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOST('pbq', 'int');

						// Search price into product_price_by_qty from $prod->id
						foreach ($prod->prices_by_qty_list[$object->thirdparty->price_level] as $priceforthequantityarray)
						{
							if ($priceforthequantityarray['rowid'] != $pqp) continue;
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT')
							{
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
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

				// if price ht is forced (ie: calculated by margin rate and cost price). TODO Why this ?
				if (!empty($price_ht) || $price_ht === '0') {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
				} // On reevalue prix selon taux tva car taux tva transaction peut etre different
				// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
				elseif ($tmpvat != $tmpprodvat) {
					if ($price_base_type != 'HT') {
						$pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
					} else {
						$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
					}
				}

				$desc = '';

				// Define output language
				if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id', 'aZ09'))
						$newlang = GETPOST('lang_id', 'aZ09');
					if (empty($newlang))
						$newlang = $object->thirdparty->default_lang;
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$desc = (!empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
				} else {
					$desc = $prod->description;
				}

				if (!empty($product_desc) && !empty($conf->global->MAIN_NO_CONCAT_DESCRIPTION)) $desc = $product_desc;
				else $desc = dol_concatdesc($desc, $product_desc, '', !empty($conf->global->MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION));

				// Add dimensions into product description
				/*if (empty($conf->global->MAIN_PRODUCT_DISABLE_AUTOADD_DIM))
				{
					$text='';
					if ($prod->weight) $text.=($text?"\n":"").$outputlangs->trans("Weight").': '.$prod->weight.' '.$prod->weight_units;
					if ($prod->length) $text.=($text?"\n":"").$outputlangs->trans("Length").': '.$prod->length.' '.$prod->length_units;
					if ($prod->surface) $text.=($text?"\n":"").$outputlangs->trans("Surface").': '.$prod->surface.' '.$prod->surface_units;
					if ($prod->volume) $text.=($text?"\n":"").$outputlangs->trans("Volume").': '.$prod->volume.' '.$prod->volume_units;

					$desc = dol_concatdesc($desc, $text);
				}*/

				// Add custom code and origin country into description
				if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (!empty($prod->customcode) || !empty($prod->country_code)))
				{
					$tmptxt = '(';
					// Define output language
					if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id', 'alpha'))
							$newlang = GETPOST('lang_id', 'alpha');
						if (empty($newlang))
							$newlang = $object->thirdparty->default_lang;
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						if (!empty($prod->customcode))
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
						if (!empty($prod->customcode) && !empty($prod->country_code))
							$tmptxt .= ' - ';
						if (!empty($prod->country_code))
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $outputlangs, 0);
					} else {
						if (!empty($prod->customcode))
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
						if (!empty($prod->customcode) && !empty($prod->country_code))
							$tmptxt .= ' - ';
						if (!empty($prod->country_code))
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $langs, 0);
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
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');

				$fk_unit = GETPOST('units', 'alpha');
				$pu_ht_devise = price2num($price_ht_devise, 'MU');
			}

			// Margin
			$fournprice = price2num(GETPOST('fournprice'.$predef) ? GETPOST('fournprice'.$predef) : '');
			$buyingprice = price2num(GETPOST('buying_price'.$predef) != '' ? GETPOST('buying_price'.$predef) : ''); // If buying_price is '0', we muste keep this value

			$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $tva_npr);

			$info_bits = 0;
			if ($tva_npr)
				$info_bits |= 0x01;

			if (((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS)) && (!empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min)))) {
				$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
				setEventMessages($mesg, null, 'errors');
			} else {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $price_base_type, $pu_ttc, $info_bits, $type, - 1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $date_start, $date_end, $array_options, $fk_unit, '', 0, $pu_ht_devise);

				if ($result > 0) {
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
	} // Update a line within proposal
	elseif ($action == 'updateline' && $usercancreate && GETPOST('save'))
	{
		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', GETPOST('tva_tx')))
			$info_bits |= 0x01;

			// Clean parameters
		$description = dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml'));

		// Define vat_rate
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		$pu_ht = GETPOST('price_ht');

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we muste keep this value

		$pu_ht_devise = GETPOST('multicurrency_subprice');

		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

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
		$special_code = GETPOST('special_code');
		if (!GETPOST('qty')) $special_code = 3;

		// Check minimum price
		$productid = GETPOST('productid', 'int');
		if (!empty($productid)) {
			$product = new Product($db);
			$res = $product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->thirdparty->price_level))
				$price_min = $product->multiprices_min [$object->thirdparty->price_level];

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');
			if (((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS)) && ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min)))) {
				setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
				$error++;
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

			if (empty($user->rights->margins->creer))
			{
				foreach ($object->lines as &$line)
				{
					if ($line->id == GETPOST('lineid'))
					{
						$fournprice = $line->fk_fournprice;
						$buyingprice = $line->pa_ht;
						break;
					}
				}
			}

			$qty = price2num(GETPOST('qty'), 'MS');

			$result = $object->updateline(GETPOST('lineid', 'int'), $pu_ht, $qty, GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, $description, 'HT', $info_bits, $special_code, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $type, $date_start, $date_end, $array_options, $_POST["units"], $pu_ht_devise);

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
					if ($ret > 0) $object->fetch_thirdparty();
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
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	} elseif ($action == 'classin' && $usercancreate) {
		// Set project
		$object->setProject(GETPOST('projectid', 'int'));
	} // Delivery time
	elseif ($action == 'setavailability' && $usercancreate) {
		$result = $object->set_availability($user, GETPOST('availability_id', 'int'));
	} // Origin of the commercial proposal
	elseif ($action == 'setdemandreason' && $usercancreate) {
		$result = $object->set_demand_reason($user, GETPOST('demand_reason_id', 'int'));
	} // Terms of payment
	elseif ($action == 'setconditions' && $usercancreate) {
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
	} elseif ($action == 'setremisepercent' && $usercancreate) {
		$result = $object->set_remise_percent($user, $_POST['remise_percent']);
	} elseif ($action == 'setremiseabsolue' && $usercancreate) {
		$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
	} // Payment choice
	elseif ($action == 'setmode' && $usercancreate) {
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	} // Multicurrency Code
	elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} // Multicurrency rate
	elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')));
	} // bank account
	elseif ($action == 'setbankaccount' && $usercancreate) {
		$result = $object->setBankAccount(GETPOST('fk_account', 'int'));
	} // shipping method
	elseif ($action == 'setshippingmethod' && $usercancreate) {
		$result = $object->setShippingMethod(GETPOST('shipping_method_id', 'int'));
	} elseif ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) $error++;
		if (!$error)
		{
			$result = $object->updateExtraField(GETPOST('attribute', 'restricthtml'), 'PROPAL_MODIFY', $user);
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}
		if ($error) $action = 'edit_extras';
	}

	if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $usercancreate)
	{
		if ($action == 'addcontact')
		{
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
		} // Toggle the status of a contact
		elseif ($action == 'swapstatut') {
			if ($object->fetch($id) > 0) {
				$result = $object->swapContactStatus(GETPOST('ligne'));
			} else {
				dol_print_error($db);
			}
		} // Delete a contact
		elseif ($action == 'deletecontact') {
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
	$upload_dir = $conf->propal->multidir_output[$object->entity];
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$formmargin = new FormMargin($db);
$companystatic = new Societe($db);
if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
llxHeader('', $langs->trans('Proposal'), $help_url);

$now = dol_now();

// Add new proposal
if ($action == 'create')
{
	$currency_code = $conf->currency;

	print load_fiche_titre($langs->trans("NewProp"), '', 'propal');

	$soc = new Societe($db);
	if ($socid > 0)
		$res = $soc->fetch($socid);

	// Load objectsrc
	if (!empty($origin) && !empty($originid))
	{
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
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
			{
				$objectsrc->fetch_lines();
			}
			$objectsrc->fetch_thirdparty();

			$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : 0);
			$ref_client = (!empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

			$soc = $objectsrc->thirdparty;

			$cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0)); // TODO maybe add default value option
			$mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
			$remise_percent 	= (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_percent) ? $soc->remise_percent : 0));
			$remise_absolue 	= (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));
			$dateinvoice = (empty($dateinvoice) ? (empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : '') : $dateinvoice);

			// Replicate extrafields
			$objectsrc->fetch_optionals();
			$object->array_options = $objectsrc->array_options;

			if (!empty($conf->multicurrency->enabled))
			{
				if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
				if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
			}
		}
	} else {
		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;
	}

	$object = new Propal($db);

	print '<form name="addprop" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($origin != 'project' && $originid) {
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid" value="'.$originid.'">';
	} elseif ($origin == 'project' && !empty($projectid)) {
		print '<input type="hidden" name="projectid" value="'.$projectid.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';

	// Ref customer
	print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
	print '<input type="text" name="ref_client" value="'.GETPOST('ref_client').'"></td>';
	print '</tr>';

	// Third party
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('Customer').'</td>';
	if ($socid > 0) {
		print '<td>';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		print '</td>';
	} else {
		print '<td>';
		print $form->select_company('', 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300 maxwidth500');
		// reload page to retrieve customer informations
		if (empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE_DISABLED))
		{
			print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					console.log("We have changed the company - Reload page");
					var socid = $(this).val();
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&ref_client="+$("input[name=ref_client]").val();
				});
			});
			</script>';
		}
		print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
		print '</td>';
	}
	print '</tr>'."\n";

	if ($socid > 0)
	{
		// Contacts (ask contact only if thirdparty already defined).
		print "<tr><td>".$langs->trans("DefaultContact").'</td><td>';
		$form->select_contacts($soc->id, $contactid, 'contactid', 1, $srccontactslist);
		print '</td></tr>';

		// Third party discounts info line
		print '<tr><td>'.$langs->trans('Discounts').'</td><td>';

		$absolute_discount = $soc->getAvailableDiscounts();

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = urlencode($_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid'));
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
		print '</td></tr>';
	}

	// Date
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
	print $form->selectDate('', '', '', '', '', "addprop", 1, 1);
	print '</td></tr>';

	// Validaty duration
	print '<tr><td class="fieldrequired">'.$langs->trans("ValidityDuration").'</td><td><input name="duree_validite" class="width50" value="'.(GETPOSTISSET('duree_validite') ? GETPOST('duree_validite', 'alphanohtml') : $conf->global->PROPALE_VALIDITY_DURATION).'"> '.$langs->trans("days").'</td></tr>';

	// Terms of payment
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
	$form->select_conditions_paiements((GETPOSTISSET('cond_reglement_id') ? GETPOST('cond_reglement_id', 'int') : $soc->cond_reglement_id), 'cond_reglement_id', -1, 1);
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
	$form->select_types_paiements((GETPOSTISSET('mode_reglement_id') ? GETPOST('mode_reglement_id', 'int') : $soc->mode_reglement_id), 'mode_reglement_id');
	print '</td></tr>';

	// Bank Account
	if (!empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && !empty($conf->banque->enabled)) {
		print '<tr><td>'.$langs->trans('BankAccount').'</td><td>';
		$form->select_comptes($soc->fk_account, 'fk_account', 0, '', 1);
		print '</td></tr>';
	}

	// What trigger creation
	print '<tr><td>'.$langs->trans('Source').'</td><td>';
	$form->selectInputReason('', 'demand_reason_id', "SRC_PROP", 1);
	print '</td></tr>';

	// Delivery delay
	print '<tr class="fielddeliverydelay"><td>'.$langs->trans('AvailabilityPeriod');
	if (!empty($conf->commande->enabled))
		print ' ('.$langs->trans('AfterOrder').')';
	print '</td><td>';
	$form->selectAvailabilityDelay('', 'availability_id', '', 1);
	print '</td></tr>';

	// Shipping Method
	if (!empty($conf->expedition->enabled)) {
		if (!empty($conf->global->SOCIETE_ASK_FOR_SHIPPING_METHOD) && !empty($soc->shipping_method_id)) {
			$shipping_method_id = $soc->shipping_method_id;
		}
		print '<tr><td>'.$langs->trans('SendingMethod').'</td><td>';
		print $form->selectShippingMethod($shipping_method_id, 'shipping_method_id', '', 1);
		print '</td></tr>';
	}

	// Delivery date (or manufacturing)
	print '<tr><td>'.$langs->trans("DeliveryDate").'</td>';
	print '<td>';
	if ($conf->global->DATE_LIVRAISON_WEEK_DELAY != "") {
		$tmpdte = time() + ((7 * $conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		$syear = date("Y", $tmpdte);
		$smonth = date("m", $tmpdte);
		$sday = date("d", $tmpdte);
		print $form->selectDate($syear."-".$smonth."-".$sday, 'date_livraison', '', '', '', "addprop");
	} else {
		print $form->selectDate(-1, 'date_livraison', '', '', '', "addprop", 1, 1);
	}
	print '</td></tr>';

	// Project
	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		print '<tr>';
		print '<td>'.$langs->trans("Project").'</td><td>';
		$numprojet = $formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 0, 0, 'maxwidth500');
		print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
		print '</td>';
		print '</tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $soc->label_incoterms, 1).'</label></td>';
		print '<td class="maxwidthonsmartphone">';
		print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : ''), (!empty($soc->location_incoterms) ? $soc->location_incoterms : ''));
		print '</td></tr>';
	}

	// Template to use by default
	print '<tr>';
	print '<td>'.$langs->trans("DefaultModel").'</td>';
	print '<td>';
	$liste = ModelePDFPropales::liste_modeles($db);
	print $form->selectarray('model', $liste, ($conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT ? $conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT : $conf->global->PROPALE_ADDON_PDF));
	print "</td></tr>";

	// Multicurrency
	if (!empty($conf->multicurrency->enabled))
	{
		print '<tr>';
		print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
		print '<td class="maxwidthonsmartphone">';
		print $form->selectMultiCurrency($currency_code, 'multicurrency_code', 0);
		print '</td></tr>';
	}

	// Public note
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top">';
	$note_public = $object->getDefaultCreateValueFor('note_public', (is_object($objectsrc) ? $objectsrc->note_public : null));
	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);

	// Private note
	if (empty($user->socid))
	{
		print '<tr>';
		print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
		print '<td valign="top">';
		$note_private = $object->getDefaultCreateValueFor('note_private', ((!empty($origin) && !empty($originid) && is_object($objectsrc)) ? $objectsrc->note_private : null));
		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
		print '</td></tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	// Lines from source
	if (!empty($origin) && !empty($originid) && is_object($objectsrc))
	{
		// TODO for compatibility
		if ($origin == 'contrat') {
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue = $remise_absolue;
			$objectsrc->remise_percent = $remise_percent;
			$objectsrc->update_price(1, - 1, 1);
		}

		print "\n<!-- ".$classname." info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
		print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
		print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

		$newclassname = $classname;
		if ($newclassname == 'Propal')
			$newclassname = 'CommercialProposal';
		elseif ($newclassname == 'Commande')
			$newclassname = 'Order';
		elseif ($newclassname == 'Expedition')
			$newclassname = 'Sending';
		elseif ($newclassname == 'Fichinter')
			$newclassname = 'Intervention';

		print '<tr><td>'.$langs->trans($newclassname).'</td><td>'.$objectsrc->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($objectsrc->total_ht, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($objectsrc->total_tva, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1
		{
			print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax1, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2
		{
			print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax2, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
		}
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($objectsrc->total_ttc, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";

		if (!empty($conf->multicurrency->enabled))
		{
			print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td>'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
			print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td>'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
			print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td>'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
		}
	}

	print "</table>\n";


	/*
	 * Combobox for copy function
 	 */

	if (empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE)) print '<input type="hidden" name="createmode" value="empty">';

	if (!empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
	{
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
				$propalRefAndSocName = $row [1]." - ".$row [2];
				$liste_propal [$row [0]] = $propalRefAndSocName;
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

	print dol_get_fiche_end();

	$langs->load("bills");
	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateDraft").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>";


	// Show origin lines
	if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<table class="noborder centpercent">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}
} elseif ($object->id > 0) {
	/*
	 * Show object in view mode
	 */

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$head = propal_prepare_head($object);
	print dol_get_fiche_head($head, 'comm', $langs->trans('Proposal'), -1, 'propal');

	$formconfirm = '';

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '(s.client=1 OR s.client=2 OR s.client=3)'))
		);
		if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY) && !empty($object->delivery_date)) {
			$formquestion[] = array('type' => 'date', 'name' => 'date_delivery', 'label' => $langs->trans("DeliveryDate"), 'value' => $object->delivery_date);
		}
		// Incomplete payment. We ask if reason = discount or other
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePropal', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	if ($action == 'closeas')
	{
		//Form to close proposal (signed or not)
		$formquestion = array(
			array('type' => 'select', 'name' => 'statut', 'label' => '<span class="fieldrequired">'.$langs->trans("CloseAs").'</span>', 'values' => array($object::STATUS_SIGNED => $object->LibStatut($object::STATUS_SIGNED), $object::STATUS_NOTSIGNED => $object->LibStatut($object::STATUS_NOTSIGNED))),
			array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"), 'value' => '')				// Field to complete private note (not replace)
		);

		if (!empty($conf->notification->enabled))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$formquestion = array_merge($formquestion, array(
				array('type' => 'onecolumn', 'value' => $notify->confirmMessage('PROPAL_CLOSE_SIGNED', $object->socid, $object)),
			));
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('SetAcceptedRefused'), $text, 'confirm_closeas', $formquestion, '', 1, 250);
	} // Confirm delete
	elseif ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp', $object->ref), 'confirm_delete', '', 0, 1);
	} // Confirm reopen
	elseif ($action == 'reopen') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenProp', $object->ref), 'confirm_reopen', '', 0, 1);
	} // Confirmation delete product/service line
	elseif ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	} // Confirm validate proposal
	elseif ($action == 'validate') {
		$error = 0;

		// We verify whether the object is provisionally numbering
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

		$text = $langs->trans('ConfirmValidateProp', $numref);
		if (!empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('PROPAL_VALIDATE', $object->socid, $object);
		}

		if (!$error)
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProp'), $text, 'confirm_validate', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Proposal card

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'customer');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherProposals").'</a>)';
	// Project
	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($usercancreate)
		{
			if ($action != 'classify')
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				$morehtmlref .= $proj->ref;
				$morehtmlref .= '</a>';
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

	// Link for thirdparty discounts
	if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	} else {
		$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
		$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
	}

	print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td>';

	$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
	$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
	$absolute_discount = price2num($absolute_discount, 'MT');
	$absolute_creditnote = price2num($absolute_creditnote, 'MT');

	$thirdparty = $soc;
	$discount_type = 0;
	$backtopage = urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
	include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

	print '</td></tr>';

	// Date of proposal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
	print '</td>';
	if ($action != 'editdate' && $object->statut == Propal::STATUS_DRAFT && $usercancreate)
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->statut == Propal::STATUS_DRAFT && $action == 'editdate' && $usercancreate) {
		print '<form name="editdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setdate">';
		print $form->selectDate($object->date, 're', '', '', 0, "editdate");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
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
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateEndPropal');
	print '</td>';
	if ($action != 'editecheance' && $object->statut == Propal::STATUS_DRAFT && $usercancreate)
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editecheance&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->statut == Propal::STATUS_DRAFT && $action == 'editecheance' && $usercancreate) {
		print '<form name="editecheance" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setecheance">';
		print $form->selectDate($object->fin_validite, 'ech', '', '', '', "editecheance");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		if (!empty($object->fin_validite)) {
			print dol_print_date($object->fin_validite, 'day');
			if ($object->statut == Propal::STATUS_VALIDATED && $object->fin_validite < ($now - $conf->propal->cloture->warning_delay))
				print img_warning($langs->trans("Late"));
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
	if ($action != 'editconditions' && $object->statut == Propal::STATUS_DRAFT && $usercancreate)
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetConditions'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->statut == Propal::STATUS_DRAFT && $action == 'editconditions' && $usercancreate) {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id');
	} else {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none');
	}
	print '</td>';
	print '</tr>';

	// Delivery date
	$langs->load('deliveries');
	print '<tr><td>';
	print $form->editfieldkey($langs->trans('DeliveryDate'), 'date_livraison', $object->delivery_date, $object, $usercancreate, 'datepicker');
	print '</td><td>';
	print $form->editfieldval($langs->trans('DeliveryDate'), 'date_livraison', $object->delivery_date, $object, $usercancreate, 'datepicker');
	print '</td>';
	print '</tr>';

	// Delivery delay
	print '<tr class="fielddeliverydelay"><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('AvailabilityPeriod');
	if (!empty($conf->commande->enabled))
		print ' ('.$langs->trans('AfterOrder').')';
	print '</td>';
	if ($action != 'editavailability' && $object->statut == Propal::STATUS_DRAFT && $usercancreate)
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editavailability&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetAvailability'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->statut == Propal::STATUS_DRAFT && $action == 'editavailability' && $usercancreate) {
		$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'availability_id', 1);
	} else {
		$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'none', 1);
	}

	print '</td>';
	print '</tr>';

	// Shipping Method
	if (!empty($conf->expedition->enabled)) {
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('SendingMethod');
		print '</td>';
		if ($action != 'editshippingmethod' && $usercancreate)
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshippingmethod&amp;id='.$object->id.'">'.img_edit($langs->trans('SetShippingMode'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editshippingmethod' && $usercancreate) {
			$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
		} else {
			$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'none');
		}
		print '</td>';
		print '</tr>';
	}

	// Origin of demand
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Source');
	print '</td>';
	if ($action != 'editdemandreason' && $object->statut == Propal::STATUS_DRAFT && $usercancreate)
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdemandreason&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDemandReason'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->statut == Propal::STATUS_DRAFT && $action == 'editdemandreason' && $usercancreate) {
		$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'demand_reason_id', 1);
	} else {
		$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'none');
	}
	print '</td>';
	print '</tr>';

	// Payment mode
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && $object->statut == Propal::STATUS_DRAFT && $usercancreate)
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMode'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->statut == Propal::STATUS_DRAFT && $action == 'editmode' && $usercancreate) {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
	}
	print '</td></tr>';

	// Multicurrency
	if (!empty($conf->multicurrency->enabled))
	{
		// Multicurrency code
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
		print '</td>';
		if ($action != 'editmulticurrencycode' && $object->statut == $object::STATUS_DRAFT && $usercancreate)
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($object->statut == $object::STATUS_DRAFT && $action == 'editmulticurrencycode' && $usercancreate) {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'multicurrency_code');
		} else {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'none');
		}
		print '</td></tr>';

		// Multicurrency rate
		if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1)
		{
			print '<tr>';
			print '<td>';
			print '<table class="nobordernopadding" width="100%"><tr>';
			print '<td>';
			print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
			print '</td>';
			if ($action != 'editmulticurrencyrate' && $object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency && $usercancreate)
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
			print '</tr></table>';
			print '</td><td>';
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

	if ($soc->outstanding_limit)
	{
		// Outstanding Bill
		print '<tr><td>';
		print $langs->trans('OutstandingBill');
		print '</td><td class="right">';
		$arrayoutstandingbills = $soc->getOutstandingBills();
		print price($arrayoutstandingbills['opened']).' / ';
		print price($soc->outstanding_limit, 0, $langs, 1, - 1, - 1, $conf->currency);
		print '</td>';
		print '</tr>';
	}

	if (!empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && !empty($conf->banque->enabled))
	{
		// Bank Account
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('BankAccount');
		print '</td>';
		if ($action != 'editbankaccount' && $usercancreate)
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbankaccount') {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
		} else {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
		}
		print '</td>';
		print '</tr>';
	}

	$tmparray = $object->getTotalWeightVolume();
	$totalWeight = $tmparray['weight'];
	$totalVolume = $tmparray['volume'];
	if ($totalWeight) {
		print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
		print '<td>';
		print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
		print '</td></tr>';
	}
	if ($totalVolume) {
		print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
		print '<td>';
		print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
		print '</td></tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('IncotermLabel');
		print '<td><td class="right">';
		if ($usercancreate) print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
		else print '&nbsp;';
		print '</td></tr></table>';
		print '</td>';
		print '<td>';
		if ($action != 'editincoterm')
		{
			print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
		} else {
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		}
		print '</td></tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
	{
		// Multicurrency Amount HT
		print '<tr><td class="titlefieldmiddle">'.$form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0).'</td>';
		print '<td class="nowrap">'.price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';

		// Multicurrency Amount VAT
		print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0).'</td>';
		print '<td class="nowrap">'.price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';

		// Multicurrency Amount TTC
		print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0).'</td>';
		print '<td class="nowrap">'.price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
		print '</tr>';
	}

	// Amount HT
	print '<tr><td class="titlefieldmiddle">'.$langs->trans('AmountHT').'</td>';
	print '<td class="nowrap">'.price($object->total_ht, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
	print '</tr>';

	// Amount VAT
	print '<tr><td>'.$langs->trans('AmountVAT').'</td>';
	print '<td class="nowrap">'.price($object->total_tva, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
	print '</tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) 	// Localtax1
	{
		print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
		print '<td class="nowrap">'.price($object->total_localtax1, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
		print '</tr>';
	}
	if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) 	// Localtax2
	{
		print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
		print '<td class="nowrap">'.price($object->total_localtax2, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
		print '</tr>';
	}

	// Amount TTC
	print '<tr><td>'.$langs->trans('AmountTTC').'</td>';
	print '<td class="nowrap">'.price($object->total_ttc, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
	print '</tr>';

	// Statut
	//print '<tr><td height="10">' . $langs->trans('Status') . '</td><td class="left" colspan="2">' . $object->getLibStatut(4) . '</td></tr>';

	print '</table>';

	// Margin Infos
	if (!empty($conf->margin->enabled))
	{
		$formmargin->displayMarginInfos($object);
	}

	print '</div>';
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

	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid')).'" method="POST">
	<input type="hidden" name="token" value="' . newToken().'">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="' . $object->id.'">
	';

	if (!empty($conf->use_javascript_ajax) && $object->statut == Propal::STATUS_DRAFT) {
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '<div class="div-table-responsive-no-min">';
	if (!empty($object->lines) || ($object->statut == Propal::STATUS_DRAFT && $usercancreate && $action != 'selectlines' && $action != 'editline'))
	{
		print '<table id="tablelines" class="noborder noshadow" width="100%">';
	}

	if (!empty($object->lines))
	{
		$ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1);
	}

	// Form to add new line
	if ($object->statut == Propal::STATUS_DRAFT && $usercancreate && $action != 'selectlines')
	{
		if ($action != 'editline') {
			// Add products/services form
			$object->formAddObjectLine(1, $mysoc, $soc);

			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		} else {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formEditObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		}
	}

	if (!empty($object->lines) || ($object->statut == Propal::STATUS_DRAFT && $usercancreate && $action != 'selectlines' && $action != 'editline'))
	{
		print '</table>';
	}
	print '</div>';

	print "</form>\n";

	print dol_get_fiche_end();


	/*
	 * Button Actions
	 */

	if ($action != 'presend') {
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
																									   // modified by hook
		if (empty($reshook))
		{
			if ($action != 'editline')
			{
				// Validate
				if (($object->statut == Propal::STATUS_DRAFT && $object->total_ttc >= 0 && count($object->lines) > 0)
					|| ($object->statut == Propal::STATUS_DRAFT && !empty($conf->global->PROPAL_ENABLE_NEGATIVE) && count($object->lines) > 0))
				{
					if ($usercanvalidate)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a>';
					} else print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans('Validate').'</a>';
				}
				// Create event
				/*if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
				}*/
				// Edit
				if ($object->statut == Propal::STATUS_VALIDATED && $usercancreate) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
				}

				// ReOpen
				if (($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED) && $usercanclose) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen'.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#reopen').'"';
					print '>'.$langs->trans('ReOpen').'</a>';
				}

				// Send
				if (empty($user->socid)) {
					if ($object->statut == Propal::STATUS_VALIDATED || $object->statut == Propal::STATUS_SIGNED || !empty($conf->global->PROPOSAL_SENDBYEMAIL_FOR_ALL_STATUS)) {
						if ($usercansend) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>';
						} else print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('SendMail').'</a>';
					}
				}

				// Create a sale order
				if (!empty($conf->commande->enabled) && $object->statut == Propal::STATUS_SIGNED) {
					if ($usercancreateorder) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddOrder").'</a>';
					}
				}

				// Create a purchase order
				if (!empty($conf->global->WORKFLOW_CAN_CREATE_PURCHASE_ORDER_FROM_PROPOSAL))
				{
					if (!empty($conf->fournisseur->enabled) && $object->statut == Propal::STATUS_SIGNED) {
						if ($usercancreatepurchaseorder) {
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddPurchaseOrder").'</a>';
						}
					}
				}

				// Create an intervention
				if (!empty($conf->service->enabled) && !empty($conf->ficheinter->enabled) && $object->statut == Propal::STATUS_SIGNED) {
					if ($usercancreateintervention) {
						$langs->load("interventions");
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddIntervention").'</a>';
					}
				}

				// Create contract
				if ($conf->contrat->enabled && $object->statut == Propal::STATUS_SIGNED) {
					$langs->load("contracts");

					if ($usercancreatecontract) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans('AddContract').'</a>';
					}
				}

				// Create an invoice and classify billed
				if ($object->statut == Propal::STATUS_SIGNED)
				{
					if (!empty($conf->facture->enabled) && $usercancreateinvoice)
					{
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddBill").'</a>';
					}

					$arrayofinvoiceforpropal = $object->getInvoiceArrayList();
					if ((is_array($arrayofinvoiceforpropal) && count($arrayofinvoiceforpropal) > 0) || empty($conf->global->WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED))
					{
						if ($usercanclose)
						{
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled&amp;socid='.$object->socid.'">'.$langs->trans("ClassifyBilled").'</a>';
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("ClassifyBilled").'</a>';
						}
					}
				}

				// Close as accepted/refused
				if ($object->statut == Propal::STATUS_VALIDATED && $usercanclose) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=closeas'.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#close').'"';
					print '>'.$langs->trans('SetAcceptedRefused').'</a>';
				}

				// Clone
				if ($usercancreate) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;token='.newToken().'&amp;object='.$object->element.'">'.$langs->trans("ToClone").'</a>';
				}

				// Delete
				if ($usercandelete) {
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;token='.newToken().'"';
					print '>'.$langs->trans('Delete').'</a>';
				}
			}
		}

		print '</div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) $action = 'presend';

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre
		/*
		 * Documents generes
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
		if ($user->rights->propal->creer && $object->statut == Propal::STATUS_DRAFT)
		{
			$compatibleImportElementsList = array('commande', 'propal'); // import from linked elements
		}
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

		// Show online signature link
		$useonlinesignature = $conf->global->MAIN_FEATURES_LEVEL; // Replace this with 1 when feature to make online signature is ok

		if ($object->statut != Propal::STATUS_DRAFT && $useonlinesignature)
		{
			print '<br><!-- Link to sign -->';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
			print showOnlineSignatureUrl('proposal', $object->ref).'<br>';
		}

		// Show direct download link
		if ($object->statut != Propal::STATUS_DRAFT && !empty($conf->global->PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD))
		{
			print '<br><!-- Link to download main doc -->'."\n";
			print showDirectDownloadLink($object).'<br>';
		}

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'propal', $socid, 1);

		print '</div></div></div>';
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
