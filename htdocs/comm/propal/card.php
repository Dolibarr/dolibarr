<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2016 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2015 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2014 Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2014	   Ferran Marcet		 <fmarcet@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * \file 		htdocs/comm/propal/card.php
 * \ingroup 	propale
 * \brief 		Page of commercial proposals card and list
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');
$langs->load("deliveries");
$langs->load('sendings');
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');
if (! empty($conf->margin->enabled))
	$langs->load('margins');

$error = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOST('lineid', 'int');
$contactid = GETPOST('contactid','int');

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES = 4;

// Security check
if (! empty($user->societe_id)) $socid = $user->societe_id;
$result = restrictedArea($user, 'propal', $id);

$object = new Propal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || ! empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret < 0)
		dol_print_error('', $object->error);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('propalcard','globalcard'));

$permissionnote = $user->rights->propale->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->propale->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->rights->propale->creer; // Used by the include of actions_lineupdown.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel) $action = '';

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes')
	{
		if (! GETPOST('socid', 3))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		}
		else
		{
			if ($object->id > 0) {
				if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY)) {
					//Get difference between old and new delivery date and change lines according to difference
					$date_delivery = dol_mktime(12, 0, 0,
						GETPOST('date_deliverymonth', 'int'),
						GETPOST('date_deliveryday', 'int'),
						GETPOST('date_deliveryyear', 'int')
					);
					if (!empty($object->date_livraison) && !empty($date_delivery))
					{
						//Attempt to get the date without possible hour rounding errors
						$old_date_delivery = dol_mktime(12, 0, 0,
							dol_print_date($object->date_livraison, '%m'),
							dol_print_date($object->date_livraison, '%d'),
							dol_print_date($object->date_livraison, '%Y')
						);
						//Calculate the difference and apply if necessary
						$difference = $date_delivery - $old_date_delivery;
						if ($difference != 0)
						{
							$object->date_livraison = $date_delivery;
							foreach ($object->lines as $line)
							{
								if (isset($line->date_start)) $line->date_start = $line->date_start + $difference;
								if (isset($line->date_end)) $line->date_end = $line->date_end + $difference;
							}
						}
					}
				}

				$result = $object->createFromClone($socid);
				if ($result > 0) {
					header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
					exit();
				} else {
					if (count($object->errors) > 0) setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	}

	// Delete proposal
	else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->propal->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0) {
			header('Location: ' . DOL_URL_ROOT . '/comm/propal/list.php');
			exit();
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Remove line
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->propal->creer)
	{
		$result = $object->deleteline($lineid);
		// reorder lines
		if ($result)
			$object->line_order(true);

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			// Define output language
			$outputlangs = $langs;
			if (! empty($conf->global->MAIN_MULTILANGS)) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
		exit();
	}

	// Validation
	else if ($action == 'confirm_validate' && $confirm == 'yes' &&
        ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->propal_advance->validate)))
	)
	{
		$result = $object->valid($user);
		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model=$object->modelpdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			$langs->load("errors");
			if (count($object->errors) > 0) setEventMessages($object->error, $object->errors, 'errors');
			else setEventMessages($langs->trans($object->error), null, 'errors');
		}
	}

	else if ($action == 'setdate' && $user->rights->propal->creer)
	{
		$datep = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

		if (empty($datep)) {
			$error ++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		}

		if (! $error) {
			$result = $object->set_date($user, $datep);
			if ($result < 0)
				dol_print_error($db, $object->error);
		}
	}
	else if ($action == 'setecheance' && $user->rights->propal->creer)
	{
		$result = $object->set_echeance($user, dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']));
		if ($result < 0)
			dol_print_error($db, $object->error);
	}
	else if ($action == 'setdate_livraison' && $user->rights->propal->creer)
	{
		$result = $object->set_date_livraison($user, dol_mktime(12, 0, 0, $_POST['date_livraisonmonth'], $_POST['date_livraisonday'], $_POST['date_livraisonyear']));
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	// Positionne ref client
	else if ($action == 'setref_client' && $user->rights->propal->creer)
	{
		$result = $object->set_ref_client($user, GETPOST('ref_client'));
		if ($result < 0)
		{
		    setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Set incoterm
	elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
    {
    	$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
    }

	// Create proposal
	else if ($action == 'add' && $user->rights->propal->creer)
	{
		$object->socid = $socid;
		$object->fetch_thirdparty();

		$datep = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
		$date_delivery = dol_mktime(12, 0, 0, GETPOST('date_livraisonmonth'), GETPOST('date_livraisonday'), GETPOST('date_livraisonyear'));
		$duration = GETPOST('duree_validite');

		if (empty($datep)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$action = 'create';
			$error ++;
		}
		if (empty($duration)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ValidityDuration")), null, 'errors');
			$action = 'create';
			$error ++;
		}

		if ($socid < 1) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');

			$action = 'create';
			$error ++;
		}

		if (! $error)
		{
			$db->begin();

			// If we select proposal to clone during creation (when option PROPAL_CLONE_ON_CREATE_PAGE is on)
			if (GETPOST('createmode') == 'copy' && GETPOST('copie_propal'))
			{
				if ($object->fetch(GETPOST('copie_propal')) > 0) {
					$object->ref = GETPOST('ref');
					$object->datep = $datep;
					$object->date_livraison = $date_delivery;
					$object->availability_id = GETPOST('availability_id');
					$object->demand_reason_id = GETPOST('demand_reason_id');
					$object->fk_delivery_address = GETPOST('fk_address');
	                $object->shipping_method_id = GETPOST('shipping_method_id', 'int');
					$object->duree_validite = $duration;
					$object->cond_reglement_id = GETPOST('cond_reglement_id');
					$object->mode_reglement_id = GETPOST('mode_reglement_id');
	                $object->fk_account = GETPOST('fk_account', 'int');
					$object->remise_percent = GETPOST('remise_percent');
					$object->remise_absolue = GETPOST('remise_absolue');
					$object->socid = GETPOST('socid');
					$object->contactid = GETPOST('contactid');
					$object->fk_project = GETPOST('projectid');
					$object->modelpdf = GETPOST('model');
					$object->author = $user->id; // deprecated
					$object->note_private = GETPOST('note_private');
					$object->note_public = GETPOST('note_public');
					$object->statut = Propal::STATUS_DRAFT;
					$object->fk_incoterms = GETPOST('incoterm_id', 'int');
					$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

					// the create is done below and further more the existing create_from function is quite hilarating
					//$id = $object->create_from($user);
				} else {
					setEventMessages($langs->trans("ErrorFailedToCopyProposal", GETPOST('copie_propal')), null, 'errors');
				}
			} else {
				$object->ref = GETPOST('ref');
				$object->ref_client = GETPOST('ref_client');
				$object->datep = $datep;
				$object->date_livraison = $date_delivery;
				$object->availability_id = GETPOST('availability_id');
				$object->demand_reason_id = GETPOST('demand_reason_id');
				$object->fk_delivery_address = GETPOST('fk_address');
	            $object->shipping_method_id = GETPOST('shipping_method_id', 'int');
				$object->duree_validite = GETPOST('duree_validite');
				$object->cond_reglement_id = GETPOST('cond_reglement_id');
				$object->mode_reglement_id = GETPOST('mode_reglement_id');
	            $object->fk_account = GETPOST('fk_account', 'int');
				$object->contactid = GETPOST('contactid');
				$object->fk_project = GETPOST('projectid');
				$object->modelpdf = GETPOST('model');
				$object->author = $user->id; // deprecated
				$object->note_private = GETPOST('note_private');
				$object->note_public = GETPOST('note_public');
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
				$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
				if ($ret < 0) {
					$error ++;
					$action = 'create';
				}
			}

			if (! $error)
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
					if (is_array($_POST['other_linked_objects']) && ! empty($_POST['other_linked_objects'])) {
						$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
					}

					$id = $object->create($user);
					if ($id > 0)
					{
							dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

							$classname = ucfirst($subelement);
							$srcobject = new $classname($db);

							dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines");
							$result = $srcobject->fetch($object->origin_id);

							if ($result > 0)
							{
								$lines = $srcobject->lines;
								if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
								{
									$srcobject->fetch_lines();
									$lines = $srcobject->lines;
								}

								$fk_parent_line=0;
								$num=count($lines);
								for ($i=0;$i<$num;$i++)
								{
									$label=(! empty($lines[$i]->label)?$lines[$i]->label:'');
									$desc=(! empty($lines[$i]->desc)?$lines[$i]->desc:$lines[$i]->libelle);

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
										if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
											$lines[$i]->fetch_optionals($lines[$i]->rowid);
											$array_options = $lines[$i]->array_options;
										}

										$tva_tx = $lines[$i]->tva_tx;
										if (! empty($lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

										$result = $object->addline($desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent, 'HT', 0, $lines[$i]->info_bits, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $date_start, $date_end, $array_options, $lines[$i]->fk_unit);

										if ($result > 0) {
											$lineid = $result;
										} else {
											$lineid = 0;
											$error ++;
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
									$error ++;
							} else {
								setEventMessages($srcobject->error, $srcobject->errors, 'errors');
								$error ++;
							}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error ++;
					}
				} 			// Standard creation
				else
				{
					$id = $object->create($user);
				}

				if ($id > 0)
				{
					// Insertion contact par defaut si defini
					if (GETPOST('contactid') > 0)
					{
						$result = $object->add_contact(GETPOST('contactid'), 'CUSTOMER', 'external');
						if ($result < 0)
						{
							$error++;
							setEventMessages($langs->trans("ErrorFailedToAddContact"), null, 'errors');
						}
					}

					if (! $error)
					{
						$db->commit();

				        // Define output language
				    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				    	{
				    		$outputlangs = $langs;
				    		$newlang = '';
				    		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
				    		if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
				    		if (! empty($newlang)) {
				    			$outputlangs = new Translate("", $conf);
				    			$outputlangs->setDefaultLang($newlang);
				    		}
				    		$model=$object->modelpdf;

				    		$ret = $object->fetch($id); // Reload to get new records
				    		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				    		if ($result < 0) dol_print_error($db,$result);
				    	}

						header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
						exit();
					}
					else
					{
						$db->rollback();
						$action='create';
					}
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$db->rollback();
					$action='create';
				}
			}
		}
	}

	// Classify billed
	else if ($action == 'classifybilled' && $user->rights->propal->cloturer)
	{
		$result=$object->cloture($user, 4, '');
		if ($result < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}

	// Close proposal
	else if ($action == 'setstatut' && $user->rights->propal->cloturer && ! GETPOST('cancel'))
	{
		if (! GETPOST('statut')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CloseAs")), null, 'errors');
			$action = 'statut';
		} else {
			// prevent browser refresh from closing proposal several times
			if ($object->statut == Propal::STATUS_VALIDATED)
			{
				$result=$object->cloture($user, GETPOST('statut'), GETPOST('note'));
				if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}
		}
	}

	// Reopen proposal
	else if ($action == 'confirm_reopen' && $user->rights->propal->cloturer && ! GETPOST('cancel'))
	{
		// prevent browser refresh from reopening proposal several times
		if ($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED)
		{
			$result=$object->reopen($user, 1);
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


	/*
	 * Send mail
	 */

	// Actions to send emails
	$actiontypecode='AC_PROP';
	$trigger_name='PROPAL_SENTBYMAIL';
	$paramname='id';
	$mode='emailfromproposal';
	$trackid='pro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	// Go back to draft
	if ($action == 'modif' && $user->rights->propal->creer)
	{
		$object->set_draft($user);

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			// Define output language
			$outputlangs = $langs;
			if (! empty($conf->global->MAIN_MULTILANGS)) {
				$outputlangs = new Translate("", $conf);
				$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records
			$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}

	else if ($action == "setabsolutediscount" && $user->rights->propal->creer) {
		if ($_POST["remise_id"]) {
			if ($object->id > 0) {
				$result = $object->insert_discount($_POST["remise_id"]);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	}

	// Add line
	else if ($action == 'addline' && $user->rights->propal->creer) {

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		$price_ht = GETPOST('price_ht');
		$price_ht_devise = GETPOST('multicurrency_price_ht');
		if (GETPOST('prod_entry_mode') == 'free')
		{
			$idprod=0;
			$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		}
		else
		{
			$idprod=GETPOST('idprod', 'int');
			$tva_tx = '';
		}

		$qty = GETPOST('qty' . $predef);
		$remise_percent = GETPOST('remise_percent' . $predef);

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && GETPOST('type') < 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
			$error ++;
		}

		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && $price_ht == '' && $price_ht_devise == '') 	// Unit price can be 0 but not ''. Also price can be negative for proposal.
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
			$error ++;
		}
		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && empty($product_desc)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")), null, 'errors');
			$error ++;
		}

		if (! $error && ($qty >= 0) && (! empty($product_desc) || ! empty($idprod))) {
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
			if (! empty($idprod)) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Update if prices fields are defined
				$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
				$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
				if (empty($tva_tx)) $tva_npr=0;

				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_min = $prod->price_min;
				$price_base_type = $prod->price_base_type;

				// On defini prix unitaire
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && $object->thirdparty->price_level)
				{
				    $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
					$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
					$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
					$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
					if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
					{
					  if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
					  if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];
					}
				}
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
				{
					require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

					$prodcustprice = new Productcustomerprice($db);

					$filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

					$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
					if ($result) {
					    // If there is some prices specific to the customer
						if (count($prodcustprice->lines) > 0) {
							$pu_ht = price($prodcustprice->lines[0]->price);
							$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
							$price_base_type = $prodcustprice->lines[0]->price_base_type;
							$tva_tx = ($prodcustprice->lines[0]->default_vat_code ? $prodcustprice->lines[0]->tva_tx . ' ('.$prodcustprice->lines[0]->default_vat_code.' )' : $prodcustprice->lines[0]->tva_tx);
						}
					}
				}

				$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

				// if price ht is forced (ie: calculated by margin rate and cost price). TODO Why this ?
				if (! empty($price_ht)) {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
				}
				// On reevalue prix selon taux tva car taux tva transaction peut etre different
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
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id'))
						$newlang = GETPOST('lang_id');
					if (empty($newlang))
						$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$desc = (! empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
				} else {
					$desc = $prod->description;
				}

				$desc = dol_concatdesc($desc, $product_desc);

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
				if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code)))
				{
					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id','alpha'))
							$newlang = GETPOST('lang_id','alpha');
						if (empty($newlang))
							$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						if (! empty($prod->customcode))
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code))
							$tmptxt .= ' - ';
						if (! empty($prod->country_code))
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $outputlangs, 0);
					} else {
						if (! empty($prod->customcode))
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code))
							$tmptxt .= ' - ';
						if (! empty($prod->country_code))
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
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
			$fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
			$buyingprice = price2num(GETPOST('buying_price' . $predef) != '' ? GETPOST('buying_price' . $predef) : '');    // If buying_price is '0', we muste keep this value

			$date_start = dol_mktime(GETPOST('date_start' . $predef . 'hour'), GETPOST('date_start' . $predef . 'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
			$date_end = dol_mktime(GETPOST('date_end' . $predef . 'hour'), GETPOST('date_end' . $predef . 'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $tva_npr);

			$info_bits = 0;
			if ($tva_npr)
				$info_bits |= 0x01;

			if (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min))) {
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
						if (! empty($conf->global->MAIN_MULTILANGS)) {
							$outputlangs = new Translate("", $conf);
							$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
							$outputlangs->setDefaultLang($newlang);
						}
						$ret = $object->fetch($id); // Reload to get new records
						$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
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
	}

	// Update a line within proposal
	else if ($action == 'updateligne' && $user->rights->propal->creer && GETPOST('save'))
	{
		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', GETPOST('tva_tx')))
			$info_bits |= 0x01;

			// Clean parameters
		$description = dol_htmlcleanlastbr(GETPOST('product_desc'));

		// Define vat_rate
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		$pu_ht = GETPOST('price_ht');

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');    // If buying_price is '0', we muste keep this value

		$pu_ht_devise = GETPOST('multicurrency_subprice');

		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

		// Define special_code for special lines
		$special_code=GETPOST('special_code');
		if (! GETPOST('qty')) $special_code=3;

		// Check minimum price
		$productid = GETPOST('productid', 'int');
		if (! empty($productid)) {
			$product = new Product($db);
			$res = $product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
				$price_min = $product->multiprices_min [$object->thirdparty->price_level];

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			if ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
				setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
				$error ++;
			}
		} else {
			$type = GETPOST('type');
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');

			// Check parameters
			if (GETPOST('type') < 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error ++;
			}
		}

		if (! $error) {
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
			$result = $object->updateline(GETPOST('lineid'), $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, $description, 'HT', $info_bits, $special_code, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $type, $date_start, $date_end, $array_options, $_POST["units"], $pu_ht_devise);

			if ($result >= 0) {
				$db->commit();

				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					if (! empty($conf->global->MAIN_MULTILANGS)) {
						$outputlangs = new Translate("", $conf);
						$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					$ret = $object->fetch($id); // Reload to get new records
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
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
	}

	else if ($action == 'updateligne' && $user->rights->propal->creer && GETPOST('cancel'))
	{
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	}

	// Set project
	else if ($action == 'classin' && $user->rights->propal->creer) {
		$object->setProject(GETPOST('projectid','int'));
	}

	// Delai de livraison
	else if ($action == 'setavailability' && $user->rights->propal->creer) {
		$result = $object->set_availability($user, GETPOST('availability_id','int'));
	}

	// Origine de la propale
	else if ($action == 'setdemandreason' && $user->rights->propal->creer) {
		$result = $object->set_demand_reason($user, GETPOST('demand_reason_id','int'));
	}

	// Conditions de reglement
	else if ($action == 'setconditions' && $user->rights->propal->creer) {
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
	}

	else if ($action == 'setremisepercent' && $user->rights->propal->creer) {
		$result = $object->set_remise_percent($user, $_POST['remise_percent']);
	}

	else if ($action == 'setremiseabsolue' && $user->rights->propal->creer) {
		$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
	}

	// Mode de reglement
	else if ($action == 'setmode' && $user->rights->propal->creer) {
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	}

	// Multicurrency Code
	else if ($action == 'setmulticurrencycode' && $user->rights->propal->creer) {
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	}

	// Multicurrency rate
	else if ($action == 'setmulticurrencyrate' && $user->rights->propal->creer) {
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')));
	}

	// bank account
	else if ($action == 'setbankaccount' && $user->rights->propal->creer) {
	    $result=$object->setBankAccount(GETPOST('fk_account', 'int'));
	}

	// shipping method
	else if ($action == 'setshippingmethod' && $user->rights->propal->creer) {
	    $result=$object->setShippingMethod(GETPOST('shipping_method_id', 'int'));
	}

	else if ($action == 'update_extras') {
		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields (by external module or standard code)
			// TODO le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('propaldao'));
			$parameters = array('id' => $object->id);
			$reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object, $action); // Note that $action and $object may have been
			                                                                                           // modified by
			                                                                                           // some hooks
			if (empty($reshook)) {
				$result = $object->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			} else if ($reshook < 0)
				$error ++;
		}

		if ($error)
			$action = 'edit_extras';
	}

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->propal->creer)
	{
		if ($action == 'addcontact')
		{
			if ($object->id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
			}

			if ($result >= 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
				exit();
			} else {
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		// Bascule du statut d'un contact
		else if ($action == 'swapstatut') {
			if ($object->fetch($id) > 0) {
				$result = $object->swapContactStatus(GETPOST('ligne'));
			} else {
				dol_print_error($db);
			}
		}

		// Efface un contact
		else if ($action == 'deletecontact') {
			$object->fetch($id);
			$result = $object->delete_contact($lineid);

			if ($result >= 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
				exit();
			} else {
				dol_print_error($db);
			}
		}
	}

    // Actions to build doc
    $upload_dir = $conf->propal->dir_output;
    $permissioncreate=$user->rights->propal->creer;
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

}


/*
 * View
 */

llxHeader('', $langs->trans('Proposal'), 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$formmargin = new FormMargin($db);
$companystatic = new Societe($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$now = dol_now();

// Add new proposal
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewProp"));

	$soc = new Societe($db);
	if ($socid > 0)
		$res = $soc->fetch($socid);

	// Load objectsrc
	if (! empty($origin) && ! empty($originid))
	{
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs [1];
			$subelement = $regs [2];
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

			dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
			{
				$objectsrc->fetch_lines();
			}
			$objectsrc->fetch_thirdparty();

			$projectid = (! empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
			$ref_client = (! empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');
			$ref_int = (! empty($objectsrc->ref_int) ? $objectsrc->ref_int : '');

			$soc = $objectsrc->thirdparty;

			$cond_reglement_id 	= (! empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(! empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
			$mode_reglement_id 	= (! empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(! empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
			$remise_percent 	= (! empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(! empty($soc->remise_percent)?$soc->remise_percent:0));
			$remise_absolue 	= (! empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(! empty($soc->remise_absolue)?$soc->remise_absolue:0));
			$dateinvoice		= (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$dateinvoice);

			// Replicate extrafields
			$objectsrc->fetch_optionals($originid);
			$object->array_options = $objectsrc->array_options;

			if (!empty($conf->multicurrency->enabled))
			{
			    if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
			    if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
			}
		}
	}
	else
	{
	    if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;
	}

	$object = new Propal($db);

	print '<form name="addprop" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($origin != 'project' && $originid) {
		print '<input type="hidden" name="origin" value="' . $origin . '">';
		print '<input type="hidden" name="originid" value="' . $originid . '">';
	} elseif ($origin == 'project' && !empty($projectid)) {
		print '<input type="hidden" name="projectid" value="' . $projectid . '">';
	}

	dol_fiche_head();

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans("Draft") . '</td></tr>';

	// Ref customer
	print '<tr><td>' . $langs->trans('RefCustomer') . '</td><td colspan="2">';
	print '<input type="text" name="ref_client" value="'.GETPOST('ref_client').'"></td>';
	print '</tr>';

	// Third party
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans('Customer') . '</td>';
	if ($socid > 0) {
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="' . $soc->id . '">';
		print '</td>';
        if (! empty($conf->global->SOCIETE_ASK_FOR_SHIPPING_METHOD) && ! empty($soc->shipping_method_id)) {
            $shipping_method_id = $soc->shipping_method_id;
        }
	} else {
		print '<td colspan="2">';
		print $form->select_company('', 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty');
		// reload page to retrieve customer informations
		if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
		{
			print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&ref_client="+$("input[name=ref_client]").val();
				});
			});
			</script>';
		}
		print '</td>';
	}
	print '</tr>' . "\n";

	// Contacts (ask contact only if thirdparty already defined). TODO do this also into order and invoice.
	if ($socid > 0)
	{
		print "<tr><td>" . $langs->trans("DefaultContact") . '</td><td colspan="2">';
		$form->select_contacts($soc->id, $contactid, 'contactid', 1, $srccontactslist);
		print '</td></tr>';
	}

	if ($socid > 0)
	{
		// Ligne info remises tiers
		print '<tr><td>' . $langs->trans('Discounts') . '</td><td colspan="2">';
		if ($soc->remise_percent)
			print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent);
		else
			print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount = $soc->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount)
			print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount, 0, $langs, 1, -1, -1, $conf->currency));
		else
			print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';
	}

	// Date
	print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td colspan="2">';
	$form->select_date('', '', '', '', '', "addprop", 1, 1);
	print '</td></tr>';

	// Validaty duration
	print '<tr><td class="fieldrequired">' . $langs->trans("ValidityDuration") . '</td><td colspan="2"><input name="duree_validite" size="5" value="' . $conf->global->PROPALE_VALIDITY_DURATION . '"> ' . $langs->trans("days") . '</td></tr>';

	// Terms of payment
	print '<tr><td class="nowrap fieldrequired">' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
	$form->select_conditions_paiements($soc->cond_reglement_id, 'cond_reglement_id');
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
	$form->select_types_paiements($soc->mode_reglement_id, 'mode_reglement_id');
	print '</td></tr>';

    // Bank Account
    if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && ! empty($conf->banque->enabled)) {
        print '<tr><td>' . $langs->trans('BankAccount') . '</td><td colspan="2">';
        $form->select_comptes($fk_account, 'fk_account', 0, '', 1);
        print '</td></tr>';
    }

	// What trigger creation
	print '<tr><td>' . $langs->trans('Source') . '</td><td>';
	$form->selectInputReason('', 'demand_reason_id', "SRC_PROP", 1);
	print '</td></tr>';

	// Delivery delay
	print '<tr class="fielddeliverydelay"><td>' . $langs->trans('AvailabilityPeriod') . '</td><td colspan="2">';
	$form->selectAvailabilityDelay('', 'availability_id', '', 1);
	print '</td></tr>';

    // Shipping Method
    if (! empty($conf->expedition->enabled)) {
        print '<tr><td>' . $langs->trans('SendingMethod') . '</td><td colspan="2">';
        print $form->selectShippingMethod($shipping_method_id, 'shipping_method_id', '', 1);
        print '</td></tr>';
    }

	// Delivery date (or manufacturing)
	print '<tr><td>' . $langs->trans("DeliveryDate") . '</td>';
	print '<td colspan="2">';
	if ($conf->global->DATE_LIVRAISON_WEEK_DELAY != "") {
		$tmpdte = time() + ((7 * $conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		$syear = date("Y", $tmpdte);
		$smonth = date("m", $tmpdte);
		$sday = date("d", $tmpdte);
		$form->select_date($syear."-".$smonth."-".$sday, 'date_livraison', '', '', '', "addprop");
	} else {
		$form->select_date(-1, 'date_livraison', '', '', '', "addprop", 1, 1);
	}
	print '</td></tr>';

	// Project
	if (! empty($conf->projet->enabled) && $socid > 0)
	{
		$projectid = GETPOST('projectid')?GETPOST('projectid'):0;
		if ($origin == 'project') $projectid = ($originid ? $originid : 0);

		$langs->load("projects");
		print '<tr>';
		print '<td>' . $langs->trans("Project") . '</td><td colspan="2">';
		$numprojet = $formproject->select_projects($soc->id, $projectid, 'projectid', 0);
		print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'">' . $langs->trans("AddProject") . '</a>';
		print '</td>';
		print '</tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $soc->libelle_incoterms, 1).'</label></td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
        print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : ''), (!empty($soc->location_incoterms)?$soc->location_incoterms:''));
		print '</td></tr>';
	}

	// Template to use by default
	print '<tr>';
	print '<td>' . $langs->trans("DefaultModel") . '</td>';
	print '<td colspan="2">';
	$liste = ModelePDFPropales::liste_modeles($db);
	print $form->selectarray('model', $liste, ($conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT ? $conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT : $conf->global->PROPALE_ADDON_PDF));
	print "</td></tr>";

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		print '<tr>';
		print '<td>'.fieldLabel('Currency','multicurrency_code').'</td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
	    print $form->selectMultiCurrency($currency_code, 'multicurrency_code', 0);
		print '</td></tr>';
	}

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
	print '<td valign="top" colspan="2">';
	$note_public = $object->getDefaultCreateValueFor('note_public', (is_object($objectsrc)?$objectsrc->note_public:null));
	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);

	// Private note
	if (empty($user->societe_id))
	{
		print '<tr>';
		print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
		print '<td valign="top" colspan="2">';
        $note_private = $object->getDefaultCreateValueFor('note_private', ((! empty($origin) && ! empty($originid) && is_object($objectsrc))?$objectsrc->note_private:null));
		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('colspan' => ' colspan="3"');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified
	                                                                                           // by
	                                                                                           // hook
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
		print $object->showOptionals($extrafields, 'edit');
	}


	// Lines from source
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		// TODO for compatibility
		if ($origin == 'contrat') {
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue = $remise_absolue;
			$objectsrc->remise_percent = $remise_percent;
			$objectsrc->update_price(1, - 1, 1);
		}

		print "\n<!-- " . $classname . " info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
		print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
		print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
		print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
		print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

		$newclassname = $classname;
		if ($newclassname == 'Propal')
			$newclassname = 'CommercialProposal';
		elseif ($newclassname == 'Commande')
			$newclassname = 'Order';
		elseif ($newclassname == 'Expedition')
			$newclassname = 'Sending';
		elseif ($newclassname == 'Fichinter')
			$newclassname = 'Intervention';

		print '<tr><td>' . $langs->trans($newclassname) . '</td><td colspan="2">' . $objectsrc->getNomUrl(1) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalHT') . '</td><td colspan="2">' . price($objectsrc->total_ht, 0, $langs, 1, -1, -1, $conf->currency) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalVAT') . '</td><td colspan="2">' . price($objectsrc->total_tva, 0, $langs, 1, -1, -1, $conf->currency) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0 ) 		// Localtax1
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax1, 0, $langs, 1, -1, -1, $conf->currency) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax2, 0, $langs, 1, -1, -1, $conf->currency) . "</td></tr>";
		}
		print '<tr><td>' . $langs->trans('TotalTTC') . '</td><td colspan="2">' . price($objectsrc->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . "</td></tr>";
	}

	print "</table>\n";


	/*
	 * Combobox pour la fonction de copie
 	 */

	if (empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE)) print '<input type="hidden" name="createmode" value="empty">';

	if (! empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
	{
	    print '<br><table>';

		// For backward compatibility
		print '<tr>';
		print '<td><input type="radio" name="createmode" value="copy"></td>';
		print '<td>' . $langs->trans("CopyPropalFrom") . ' </td>';
		print '<td>';
		$liste_propal = array();
		$liste_propal [0] = '';

		$sql = "SELECT p.rowid as id, p.ref, s.nom";
		$sql .= " FROM " . MAIN_DB_PREFIX . "propal p";
		$sql .= ", " . MAIN_DB_PREFIX . "societe s";
		$sql .= " WHERE s.rowid = p.fk_soc";
		$sql .= " AND p.entity IN (".getEntity('propal', 1).")";
		$sql .= " AND p.fk_statut <> 0";
		$sql .= " ORDER BY Id";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$propalRefAndSocName = $row [1] . " - " . $row [2];
				$liste_propal [$row [0]] = $propalRefAndSocName;
				$i ++;
			}
			print $form->selectarray("copie_propal", $liste_propal, 0);
		} else {
			dol_print_error($db);
		}
		print '</td></tr>';

		print '<tr><td class="tdtop"><input type="radio" name="createmode" value="empty" checked></td>';
		print '<td valign="top" colspan="2">' . $langs->trans("CreateEmptyPropal") . '</td></tr>';
	}

	if (! empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE)) print '</table>';

	dol_fiche_end();

	$langs->load("bills");
	print '<div class="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("CreateDraft") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>";


	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}

} else {
	/*
	 * Show object in view mode
	 */

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$head = propal_prepare_head($object);
	dol_fiche_head($head, 'comm', $langs->trans('Proposal'), 0, 'propal');

	$formconfirm = '';

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array(
							// 'text' => $langs->trans("ConfirmClone"),
							// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
							// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' =>
							// 1),
							array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '(s.client=1 OR s.client=2 OR s.client=3)')));
		if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY) && !empty($object->date_livraison)) {
			$formquestion[] = array('type' => 'date','name' => 'date_delivery','label' => $langs->trans("DeliveryDate"),'value' => $object->date_livraison);
		}
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ClonePropal'), $langs->trans('ConfirmClonePropal', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirm delete
	else if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp', $object->ref), 'confirm_delete', '', 0, 1);
	}

	// Confirm reopen
	else if ($action == 'reopen') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenProp', $object->ref), 'confirm_reopen', '', 0, 1);
	}

	// Confirmation delete product/service line
	else if ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirm validate proposal
	else if ($action == 'validate') {
		$error = 0;

		// We verifie whether the object is provisionally numbering
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$numref = $object->getNextNumRef($soc);
			if (empty($numref)) {
				$error ++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateProp', $numref);
		if (! empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('PROPAL_VALIDATE', $object->socid, $object);
		}

		if (! $error)
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateProp'), $text, 'confirm_validate', '', 0, 1);
	}

	if (! $formconfirm) {
		$parameters = array('lineid' => $lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Proposal card

	$linkback = '<a href="' . DOL_URL_ROOT . '/comm/propal/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->propal->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->propal->creer, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
    // Project
    if (! empty($conf->projet->enabled))
    {
        $langs->load("projects");
        $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
        if ($user->rights->propal->creer)
        {
            if ($action != 'classify')
                $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            if (! empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
                $morehtmlref.=$proj->ref;
                $morehtmlref.='</a>';
            } else {
                $morehtmlref.='';
            }
        }
    }
    $morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

    // Ref
    /*
	print '<tr><td>' . $langs->trans('Ref') . '</td><td colspan="5">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
	print '</td></tr>';
	*/

	// Ref customer
	/*
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
	print $langs->trans('RefCustomer') . '</td>';
	if ($action != 'refclient' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=refclient&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify')) . '</a></td>';
	print '</td></tr></table>';
	print '</td><td colspan="5">';
	if ($user->rights->propal->creer && $action == 'refclient') {
		print '<form action="'.$_SERVER["PHP_SELF"].'?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_ref_client">';
		print '<input type="text" class="flat" size="20" name="ref_client" value="' . $object->ref_client . '">';
		print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		print $object->ref_client;
	}
	print '</td>';
	print '</tr>';
    */

	// Company
	/*
	print '<tr><td>' . $langs->trans('Company') . '</td><td colspan="5">' . $soc->getNomUrl(1) . '</td>';
	print '</tr>';*/

	// Lin for thirdparty discounts
	print '<tr><td class="titlefield">' . $langs->trans('Discounts') . '</td><td>';
	if ($soc->remise_percent)
		print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent);
	else
		print $langs->trans("CompanyHasNoRelativeDiscount");
	print '. ';
	$absolute_discount = $soc->getAvailableDiscounts('', 'fk_facture_source IS NULL');
	$absolute_creditnote = $soc->getAvailableDiscounts('', 'fk_facture_source IS NOT NULL');
	$absolute_discount = price2num($absolute_discount, 'MT');
	$absolute_creditnote = price2num($absolute_creditnote, 'MT');
	if ($absolute_discount) {
		if ($object->statut > Propal::STATUS_DRAFT) {
			print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount, 0, $langs, 0, 0, -1, $conf->currency));
		} else {
			// Remise dispo de type non avoir
			$filter = 'fk_facture_source IS NULL';
			print '<br>';
			$form->form_remise_dispo($_SERVER["PHP_SELF"] . '?id=' . $object->id, 0, 'remise_id', $soc->id, $absolute_discount, $filter, 0, '', 1);
		}
	}
	if ($absolute_creditnote) {
		print $langs->trans("CompanyHasCreditNote", price($absolute_creditnote, 0, $langs, 0, 0, -1, $conf->currency)) . '. ';
	}
	if (! $absolute_discount && ! $absolute_creditnote)
		print $langs->trans("CompanyHasNoAbsoluteDiscount") . '.';
	print '</td></tr>';

	// Date of proposal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
	print '</td>';
	if ($action != 'editdate' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdate&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetDate'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if (! empty($object->brouillon) && $action == 'editdate') {
		print '<form name="editdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setdate">';
		$form->select_date($object->date, 're', '', '', 0, "editdate");
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if ($object->date) {
			print dol_print_date($object->date, 'daytext');
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
	if ($action != 'editecheance' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editecheance&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetConditions'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if (! empty($object->brouillon) && $action == 'editecheance') {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setecheance">';
		$form->select_date($object->fin_validite, 'ech', '', '', '', "editecheance");
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if (! empty($object->fin_validite)) {
			print dol_print_date($object->fin_validite, 'daytext');
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
	if ($action != 'editconditions' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetConditions'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editconditions') {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id');
	} else {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'none');
	}
	print '</td>';
	print '</tr>';

	// Delivery date
	$langs->load('deliveries');
	print '<tr><td>';
	print $form->editfieldkey($langs->trans('DeliveryDate'), 'date_livraison', $object->date_livraison, $object, $user->rights->propal->creer, 'datepicker');
	print '</td><td>';
	print $form->editfieldval($langs->trans('DeliveryDate'), 'date_livraison', $object->date_livraison, $object, $user->rights->propal->creer, 'datepicker');
	print '</td>';
	print '</tr>';

	// Delivery delay
	print '<tr class="fielddeliverydelay"><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('AvailabilityPeriod');
	if (! empty($conf->commande->enabled))
		print ' (' . $langs->trans('AfterOrder') . ')';
	print '</td>';
	if ($action != 'editavailability' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editavailability&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetAvailability'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editavailability') {
		$form->form_availability($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->availability_id, 'availability_id', 1);
	} else {
		$form->form_availability($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->availability_id, 'none', 1);
	}

	print '</td>';
	print '</tr>';

    // Shipping Method
    if (! empty($conf->expedition->enabled)) {
        print '<tr><td>';
        print '<table width="100%" class="nobordernopadding"><tr><td>';
        print $langs->trans('SendingMethod');
        print '</td>';
        if ($action != 'editshippingmethod' && $user->rights->propal->creer)
            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editshippingmethod&amp;id='.$object->id.'">'.img_edit($langs->trans('SetShippingMode'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editshippingmethod') {
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
	if ($action != 'editdemandreason' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdemandreason&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetDemandReason'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editdemandreason') {
		$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'demand_reason_id', 1);
	} else {
		$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'none');
	}
	print '</td>';
	print '</tr>';

	// Payment mode
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmode&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMode'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->mode_reglement_id, 'mode_reglement_id');
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->mode_reglement_id, 'none');
	}
	print '</td></tr>';

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		// Multicurrency code
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print fieldLabel('Currency','multicurrency_code');
		print '</td>';
		if ($action != 'editmulticurrencycode' && ! empty($object->brouillon))
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmulticurrencycode&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmulticurrencycode') {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_code, 'multicurrency_code');
		} else {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_code, 'none');
		}
		print '</td></tr>';

		// Multicurrency rate
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print fieldLabel('CurrencyRate','multicurrency_tx');
		print '</td>';
		if ($action != 'editmulticurrencyrate' && ! empty($object->brouillon) && $object->multicurrency_code && $object->multicurrency_code != $conf->currency)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmulticurrencyrate&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
			if($action == 'actualizemulticurrencyrate') {
				list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
			}
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
		} else {
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
			if ($object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
				print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate">'.$langs->trans("ActualizeCurrency").'</a>';
				print '</div>';
			}
		}
		print '</td></tr>';
	}

	// Project
	/*
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project') . '</td>';
		if ($user->rights->propal->creer)
		{
			if ($action != 'classify')
				print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a></td>';
			print '</tr></table>';
			print '</td><td colspan="5">';
			if ($action == 'classify') {
				$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1);
			} else {
				$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0);
			}
			print '</td></tr>';
		} else {
			print '</td></tr></table>';
			if (! empty($object->fk_project)) {
				print '<td colspan="3">';
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				print '<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				print $proj->ref;
				print '</a>';
				print '</td>';
			} else {
				print '<td colspan="3">&nbsp;</td>';
			}
		}
		print '</tr>';
	}*/

	if ($soc->outstanding_limit)
	{
		// Outstanding Bill
		print '<tr><td>';
		print $langs->trans('OutstandingBill');
		print '</td><td align="right">';
		print price($soc->get_OutstandingBill()) . ' / ';
		print price($soc->outstanding_limit, 0, $langs, 1, - 1, - 1, $conf->currency);
		print '</td>';
		print '</tr>';
	}

	if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && ! empty($conf->banque->enabled))
	{
	    // Bank Account
	    print '<tr><td>';
	    print '<table width="100%" class="nobordernopadding"><tr><td>';
	    print $langs->trans('BankAccount');
	    print '</td>';
	    if ($action != 'editbankaccount' && $user->rights->propal->creer)
	        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
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

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr><td>';
        print '<table width="100%" class="nobordernopadding"><tr><td>';
        print $langs->trans('IncotermLabel');
        print '<td><td align="right">';
        if ($user->rights->propal->creer) print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
        else print '&nbsp;';
        print '</td></tr></table>';
        print '</td>';
        print '<td>';
		if ($action != 'editincoterm')
		{
			print $form->textwithpicto($object->display_incoterms(), $object->libelle_incoterms, 1);
		}
		else
		{
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		}
        print '</td></tr>';
	}

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">';

    if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
    {
        // Multicurrency Amount HT
        print '<tr><td class="titlefieldmiddle">' . fieldLabel('MulticurrencyAmountHT','multicurrency_total_ht') . '</td>';
        print '<td class="nowrap">' . price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
        print '</tr>';

        // Multicurrency Amount VAT
        print '<tr><td>' . fieldLabel('MulticurrencyAmountVAT','multicurrency_total_tva') . '</td>';
        print '<td class="nowrap">' . price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
        print '</tr>';

        // Multicurrency Amount TTC
        print '<tr><td>' . fieldLabel('MulticurrencyAmountTTC','multicurrency_total_ttc') . '</td>';
        print '<td class="nowrap">' . price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
        print '</tr>';
    }

	// Amount HT
	print '<tr><td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
	print '<td class="nowrap">' . price($object->total_ht, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	print '</tr>';

	// Amount VAT
	print '<tr><td>' . $langs->trans('AmountVAT') . '</td>';
	print '<td class="nowrap">' . price($object->total_tva, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	print '</tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) 	// Localtax1
	{
	    print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
	    print '<td class="nowrap">' . price($object->total_localtax1, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	    print '</tr>';
	}
	if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) 	// Localtax2
	{
	    print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
	    print '<td class="nowrap">' . price($object->total_localtax2, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	    print '</tr>';
	}

	// Amount TTC
	print '<tr><td>' . $langs->trans('AmountTTC') . '</td>';
	print '<td class="nowrap">' . price($object->total_ttc, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	print '</tr>';

	// Statut
	//print '<tr><td height="10">' . $langs->trans('Status') . '</td><td align="left" colspan="2">' . $object->getLibStatut(4) . '</td></tr>';

	print '</table>';

	// Margin Infos
	if (! empty($conf->margin->enabled))
	{
	    $formmargin->displayMarginInfos($object);
	}

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div><br>';

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
	}

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
	}

	/*
	 * Lines
	 */

	// Show object lines
	$result = $object->getLinesArray();

	print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid')) . '" method="POST">
	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="' . $object->id . '">
	';

	if (! empty($conf->use_javascript_ajax) && $object->statut == Propal::STATUS_DRAFT) {
		include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
	}

    print '<div class="div-table-responsive">';
	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	if (! empty($object->lines))
		$ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1);

	// Form to add new line
	if ($object->statut == Propal::STATUS_DRAFT && $user->rights->propal->creer)
	{
		if ($action != 'editline')
		{
			$var = true;

			// Add products/services form
			$object->formAddObjectLine(1, $mysoc, $soc);

			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		}
	}

	print '</table>';
    print '</div>';

	print "</form>\n";

	dol_fiche_end();

	if ($action == 'statut')
	{
		/*
		 * Form to close proposal (signed or not)
		 */
		$form_close = '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		$form_close .= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		$form_close .= '<table class="border" width="100%">';
		$form_close .= '<tr><td width="150"  align="left">' . $langs->trans("CloseAs") . '</td><td align="left">';
		$form_close .= '<input type="hidden" name="action" value="setstatut">';
		$form_close .= '<select id="statut" name="statut" class="flat">';
		$form_close .= '<option value="0">&nbsp;</option>';
		$form_close .= '<option value="2">' . $object->labelstatut [2] . '</option>';
		$form_close .= '<option value="3">' . $object->labelstatut [3] . '</option>';
		$form_close .= '</select>';
		$form_close .= '</td></tr>';
		$form_close .= '<tr><td width="150" align="left">' . $langs->trans('Note') . '</td><td align="left"><textarea cols="70" rows="' . ROWS_3 . '" wrap="soft" name="note">';
		$form_close .= $object->note;
		$form_close .= '</textarea></td></tr>';
		$form_close .= '<tr><td align="center" colspan="2">';
		$form_close .= '<input type="submit" class="button" name="validate" value="' . $langs->trans('Save') . '">';
		$form_close .= ' &nbsp; <input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '">';
		$form_close .= '<a name="close">&nbsp;</a>';
		$form_close .= '</td>';
		$form_close .= '</tr></table></form>';

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
		if (empty($reshook))
		{
			if ($action != 'statut' && $action != 'editline')
			{
				// Validate
				if ($object->statut == Propal::STATUS_DRAFT && $object->total_ttc >= 0 && count($object->lines) > 0)
				{
			        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->creer))
       				|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->propal_advance->validate)))
				    {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=validate">' . $langs->trans('Validate') . '</a></div>';
				    }
				    else
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('Validate') . '</a></div>';
				}
				// Create event
				if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
				}
				// Edit
				if ($object->statut == Propal::STATUS_VALIDATED && $user->rights->propal->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a></div>';
				}

				// ReOpen
				if (($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED) && $user->rights->propal->cloturer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=reopen' . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#reopen') . '"';
					print '>' . $langs->trans('ReOpen') . '</a></div>';
				}

				// Send
				if ($object->statut == Propal::STATUS_VALIDATED || $object->statut == Propal::STATUS_SIGNED) {
					if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=presend&amp;mode=init">' . $langs->trans('SendByMail') . '</a></div>';
					} else
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMail') . '</a></div>';
				}

				// Create an order
				if (! empty($conf->commande->enabled) && $object->statut == Propal::STATUS_SIGNED) {
					if ($user->rights->commande->creer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/commande/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddOrder") . '</a></div>';
					}
				}

				// Create contract
				if ($conf->contrat->enabled && $object->statut == Propal::STATUS_SIGNED) {
					$langs->load("contracts");

					if ($user->rights->contrat->creer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans('AddContract') . '</a></div>';
					}
				}

				// Create an invoice and classify billed
				if ($object->statut == Propal::STATUS_SIGNED)
				{
					if (! empty($conf->facture->enabled) && $user->rights->facture->creer)
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddBill") . '</a></div>';
					}

					$arrayofinvoiceforpropal = $object->getInvoiceArrayList();
					if ((is_array($arrayofinvoiceforpropal) && count($arrayofinvoiceforpropal) > 0) || empty($conf->global->WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED))
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifybilled&amp;socid=' . $object->socid . '">' . $langs->trans("ClassifyBilled") . '</a></div>';
					}
				}

				// Set accepted/refused
				if ($object->statut == Propal::STATUS_VALIDATED && $user->rights->propal->cloturer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=statut' . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#close') . '"';
					print '>' . $langs->trans('SetAcceptedRefused') . '</a></div>';
				}

				// Clone
				if ($user->rights->propal->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=' . $object->element . '">' . $langs->trans("ToClone") . '</a></div>';
				}

				// Delete
				if ($user->rights->propal->supprimer) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete"';
					print '>' . $langs->trans('Delete') . '</a></div>';
				}
			}
		}

		print '</div>';
	}
	print "<br>\n";

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) $action = 'presend';

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';

		/*
		 * Documents generes
		 */
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
		$genallowed = $user->rights->propal->creer;
		$delallowed = $user->rights->propal->supprimer;

		$var = true;

		print $formfile->showdocuments('propal', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang, '', $object);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('propal'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'propal', $socid);

		print '</div></div></div>';
	}

	/*
	 * Action presend
 	 */
	if ($action == 'presend')
	{
		$object->fetch_projet();

		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file = $fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file)) {
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}

		print '<div class="clearboth"></div>';
		print '<br>';
		print load_fiche_titre($langs->trans('SendPropalByMail'));

		dol_fiche_head('');

		// Create form object
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
        $formmail->fromtype = (GETPOST('fromtype')?GETPOST('fromtype'):(!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE)?$conf->global->MAIN_MAIL_DEFAULT_FROMTYPE:'user'));

        if($formmail->fromtype === 'user'){
            $formmail->fromid = $user->id;

        }
		$formmail->trackid='pro'.$object->id;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'pro'.$object->id);
		}
		$formmail->withfrom = 1;
		$liste = array();
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value)
			$liste [$key] = $value;
		$formmail->withto = GETPOST("sendto") ? GETPOST("sendto") : $liste;
		$formmail->withtocc = $liste;
		$formmail->withtoccc = (! empty($conf->global->MAIN_EMAIL_USECCC) ? $conf->global->MAIN_EMAIL_USECCC : false);
		if (empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendPropalRef', '__PROPREF__');
		} else if (! empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendPropalRef', '__PROPREF__ (__REFCLIENT__)');
		}
		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;

		// Tableau des substitutions
		$formmail->setSubstitFromObject($object);
		$formmail->substit['__PROPREF__'] = $object->ref; // For backward compatibility

		// Find the good contact adress
		$custcontact = '';
		$contactarr = array();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ($contactarr as $contact) {
				if ($contact ['libelle'] == $langs->trans('TypeContact_propal_external_CUSTOMER')) {	// TODO Use code and not label
					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact ['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);
				}
			}

			if (! empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action'] = 'send';
		$formmail->param['models'] = 'propal_send';
		$formmail->param['models_id']=GETPOST('modelmailselected','int');
		$formmail->param['id'] = $object->id;
		$formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
		// Init list of files
		if (GETPOST("mode") == 'init') {
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
		}

		print $formmail->get_form();

		dol_fiche_end();
	}
}

// End of page
llxFooter();
$db->close();
