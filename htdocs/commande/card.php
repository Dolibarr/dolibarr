<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador      	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/commande/card.php
 * \ingroup commande
 * \brief 	Page to show customer order
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (! empty($conf->propal->enabled))
	require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}

require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

if (!empty($conf->variants->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('orders','sendings','companies','bills','propal','deliveries','products','other'));
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');
if (! empty($conf->margin->enabled)) $langs->load('margins');
if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('orderid', 'int'));
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOST('lineid', 'int');
$projectid = GETPOST('projectid', 'int');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
if (! empty($user->societe_id))
	$socid = $user->societe_id;
$result = restrictedArea($user, 'commande', $id);

$object = new Commande($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('ordercard','globalcard'));

$permissionnote = $user->rights->commande->creer; 		// Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->commande->creer; 	// Used by the include of actions_dellink.inc.php
$permissionedit = $user->rights->commande->creer; 		// Used by the include of actions_lineupdown.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
// Note that $action and $object may be modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action='';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; 	// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->commande->creer)
	{
		if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		}
		else
		{
			if ($object->id > 0)
			{
				// Because createFromClone modifies the object, we must clone it so that we can restore it later
				$orig = clone $object;

				$result=$object->createFromClone($socid);
				if ($result > 0)
				{
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit;
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$object = $orig;
					$action='';
				}
			}
		}
	}

	// Reopen a closed order
	elseif ($action == 'reopen' && $user->rights->commande->creer)
	{
		if ($object->statut == Commande::STATUS_CANCELED || $object->statut == Commande::STATUS_CLOSED)
		{
			$result = $object->set_reopen($user);
			if ($result > 0)
			{
				setEventMessages($langs->trans('OrderReopened', $object->ref), null);
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Remove order
	elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->commande->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Remove a product line
	elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->commande->creer)
	{
		$result = $object->deleteline($user, $lineid);
		if ($result > 0)
		{
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
				$newlang = GETPOST('lang_id', 'aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Link to a project
	elseif ($action == 'classin' && $user->rights->commande->creer)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Add order
	elseif ($action == 'add' && $user->rights->commande->creer)
	{
		$datecommande = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
		$datelivraison = dol_mktime(12, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));

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

		if (! $error) {
			$object->socid = $socid;
			$object->fetch_thirdparty();

			$db->begin();

			$object->date_commande = $datecommande;
			$object->note_private = GETPOST('note_private', 'none');
			$object->note_public = GETPOST('note_public', 'none');
			$object->source = GETPOST('source_id');
			$object->fk_project = GETPOST('projectid', 'int');
			$object->ref_client = GETPOST('ref_client', 'alpha');
			$object->modelpdf = GETPOST('model');
			$object->cond_reglement_id = GETPOST('cond_reglement_id');
			$object->mode_reglement_id = GETPOST('mode_reglement_id');
			$object->fk_account = GETPOST('fk_account', 'int');
			$object->availability_id = GETPOST('availability_id');
			$object->demand_reason_id = GETPOST('demand_reason_id');
			$object->date_livraison = $datelivraison;
			$object->shipping_method_id = GETPOST('shipping_method_id', 'int');
			$object->warehouse_id = GETPOST('warehouse_id', 'int');
			$object->fk_delivery_address = GETPOST('fk_address');
			$object->contactid = GETPOST('contactid');
			$object->fk_incoterms = GETPOST('incoterm_id', 'int');
			$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
			$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
			$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');
			// Fill array 'array_options' with data from add form
			if (! $error)
			{
				$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
				if ($ret < 0) $error++;
			}

			// If creation from another object of another module (Example: origin=propal, originid=1)
			if (! empty($origin) && ! empty($originid))
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

				$object->origin = $origin;
				$object->origin_id = $originid;

				// Possibility to add external linked objects with hooks
				$object->linked_objects [$object->origin] = $object->origin_id;
				$other_linked_objects = GETPOST('other_linked_objects', 'array');
				if (! empty($other_linked_objects)) {
					$object->linked_objects = array_merge($object->linked_objects, $other_linked_objects);
				}

				if (! $error)
				{
					$object_id = $object->create($user);

					if ($object_id > 0)
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

							$fk_parent_line = 0;
							$num = count($lines);

							for($i = 0; $i < $num; $i ++)
							{
								$label = (! empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc = (! empty($lines[$i]->desc) ? $lines[$i]->desc : '');
								$product_type = (! empty($lines[$i]->product_type) ? $lines[$i]->product_type : 0);

								// Dates
								// TODO mutualiser
								$date_start = $lines[$i]->date_debut_prevue;
								if ($lines[$i]->date_debut_reel)
									$date_start = $lines[$i]->date_debut_reel;
								if ($lines[$i]->date_start)
									$date_start = $lines[$i]->date_start;
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
								if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) // For avoid conflicts if trigger used
								{
									$lines[$i]->fetch_optionals($lines[$i]->rowid);
									$array_options = $lines[$i]->array_options;
								}

								$tva_tx = $lines[$i]->tva_tx;
								if (! empty($lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

								$result = $object->addline(
									$desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product,
									$lines[$i]->remise_percent, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $date_start, $date_end, $product_type,
									$lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options,
									$lines[$i]->fk_unit, $object->origin, $lines[$i]->rowid
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
						if (! empty($conf->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN))
						{
						    $originforcontact = $object->origin;
						    $originidforcontact = $object->origin_id;
						    if ($originforcontact == 'shipping')     // shipment and order share the same contacts. If creating from shipment we take data of order
						    {
						        $originforcontact=$srcobject->origin;
						        $originidforcontact=$srcobject->origin_id;
						    }
						    $sqlcontact = "SELECT code, fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
						    $sqlcontact.= " WHERE element_id = ".$originidforcontact." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$originforcontact."'";

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
				if (! $error)
				{
					$object_id = $object->create($user);

					// If some invoice's lines already known
					$NBLINES = 8;
					for($i = 1; $i <= $NBLINES; $i ++) {
						if ($_POST['idprod' . $i]) {
							$xid = 'idprod' . $i;
							$xqty = 'qty' . $i;
							$xremise = 'remise_percent' . $i;
							$object->add_product($_POST[$xid], $_POST[$xqty], $_POST[$xremise]);
						}
					}
				}
			}

			// Insert default contacts if defined
			if ($object_id > 0)
			{
				if (GETPOST('contactid'))
				{
					$result = $object->add_contact(GETPOST('contactid'), 'CUSTOMER', 'external');
					if ($result < 0) {
						setEventMessages($langs->trans("ErrorFailedToAddContact"), null, 'errors');
						$error++;
					}
				}

				$id = $object_id;
				$action = '';
			}

			// End of object creation, we show it
			if ($object_id > 0 && ! $error)
			{
				$db->commit();
				header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object_id);
				exit();
			} else {
				$db->rollback();
				$action = 'create';
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	elseif ($action == 'classifybilled' && $user->rights->commande->creer)
	{
		$ret=$object->classifyBilled($user);

		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	elseif ($action == 'classifyunbilled' && $user->rights->commande->creer)
	{
		$ret=$object->classifyUnBilled();
		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Positionne ref commande client
	elseif ($action == 'setref_client' && $user->rights->commande->creer) {
		$result = $object->set_ref_client($user, GETPOST('ref_client'));
		if ($result < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	elseif ($action == 'setremise' && $user->rights->commande->creer) {
		$result = $object->set_remise($user, GETPOST('remise'));
		if ($result < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	elseif ($action == 'setabsolutediscount' && $user->rights->commande->creer) {
		if (GETPOST('remise_id')) {
			if ($object->id > 0) {
				$object->insert_discount(GETPOST('remise_id'));
			} else {
				dol_print_error($db, $object->error);
			}
		}
	}

	elseif ($action == 'setdate' && $user->rights->commande->creer) {
		// print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
		$date = dol_mktime(0, 0, 0, GETPOST('order_month'), GETPOST('order_day'), GETPOST('order_year'));

		$result = $object->set_date($user, $date);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	elseif ($action == 'setdate_livraison' && $user->rights->commande->creer) {
		// print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
		$datelivraison = dol_mktime(0, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));

		$result = $object->set_date_livraison($user, $datelivraison);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	elseif ($action == 'setmode' && $user->rights->commande->creer) {
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
		if ($result < 0)
			setEventMessages($object->error, $object->errors, 'errors');
	}

	// Multicurrency Code
	elseif ($action == 'setmulticurrencycode' && $user->rights->commande->creer) {
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	}

	// Multicurrency rate
	elseif ($action == 'setmulticurrencyrate' && $user->rights->commande->creer) {
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')));
	}

	elseif ($action == 'setavailability' && $user->rights->commande->creer) {
		$result = $object->availability(GETPOST('availability_id'));
		if ($result < 0)
			setEventMessages($object->error, $object->errors, 'errors');
	}

	elseif ($action == 'setdemandreason' && $user->rights->commande->creer) {
		$result = $object->demand_reason(GETPOST('demand_reason_id'));
		if ($result < 0)
			setEventMessages($object->error, $object->errors, 'errors');
	}

	elseif ($action == 'setconditions' && $user->rights->commande->creer) {
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		} else {
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))
					$newlang = $object->thirdparty->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	}

	// Set incoterm
	elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
	{
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// bank account
	elseif ($action == 'setbankaccount' && $user->rights->commande->creer) {
		$result=$object->setBankAccount(GETPOST('fk_account', 'int'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// shipping method
	elseif ($action == 'setshippingmethod' && $user->rights->commande->creer) {
		$result = $object->setShippingMethod(GETPOST('shipping_method_id', 'int'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// warehouse
	elseif ($action == 'setwarehouse' && $user->rights->commande->creer) {
		$result = $object->setWarehouse(GETPOST('warehouse_id', 'int'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	elseif ($action == 'setremisepercent' && $user->rights->commande->creer) {
		$result = $object->set_remise($user, GETPOST('remise_percent'));
	}

	elseif ($action == 'setremiseabsolue' && $user->rights->commande->creer) {
		$result = $object->set_remise_absolue($user, GETPOST('remise_absolue'));
	}

	// Add a new line
	elseif ($action == 'addline' && $user->rights->commande->creer)
	{
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		$price_ht = GETPOST('price_ht');
		$price_ht_devise = GETPOST('multicurrency_price_ht');
		$prod_entry_mode = GETPOST('prod_entry_mode');
		if ($prod_entry_mode == 'free')
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
		$array_options = $extrafieldsline->getOptionalsFromPost($object->table_element_line, $predef);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

		if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && GETPOST('type') < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && $price_ht == '' && $price_ht_devise == '') 	// Unit price can be 0 but not ''. Also price can be negative for order.
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
			$error++;
		}
		if ($qty == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && empty($product_desc)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
			$error++;
		}

		if (!$error && !empty($conf->variants->enabled) && $prod_entry_mode != 'free') {
			if ($combinations = GETPOST('combinations', 'array')) {
				//Check if there is a product with the given combination
				$prodcomb = new ProductCombination($db);

				if ($res = $prodcomb->fetchByProductCombination2ValuePairs($idprod, $combinations)) {
					$idprod = $res->fk_product_child;
				}
				else
				{
					setEventMessages($langs->trans('ErrorProductCombinationNotFound'), null, 'errors');
					$error ++;
				}
			}
		}

		if (! $error && ($qty >= 0) && (! empty($product_desc) || ! empty($idprod))) {
			// Clean parameters
			$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha')?GETPOST('price_base_type', 'alpha'):'HT');

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $tva_tx par celui du produit
			// Ecrase $base_price_type par celui du produit
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

				// If price per segment
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
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
				// If price per customer
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
				{
					require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

					$prodcustprice = new Productcustomerprice($db);

					$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);

					$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
					if ($result >= 0)
					{
						if (count($prodcustprice->lines) > 0)
						{
							$pu_ht = price($prodcustprice->lines[0]->price);
							$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
							$price_base_type = $prodcustprice->lines[0]->price_base_type;
							$tva_tx = $prodcustprice->lines[0]->tva_tx;
							if ($prodcustprice->lines[0]->default_vat_code && ! preg_match('/\(.*\)/', $tva_tx)) $tva_tx.= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
							$tva_npr = $prodcustprice->lines[0]->recuperableonly;
							if (empty($tva_tx)) $tva_npr=0;
						}
					}
					else
					{
						setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
					}
				}
				// If price per quantity
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
				{
					if ($prod->prices_by_qty[0])	// yes, this product has some prices per quantity
					{
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOST('pbq', 'int');

						// Search price into product_price_by_qty from $prod->id
						foreach($prod->prices_by_qty_list[0] as $priceforthequantityarray)
						{
							if ($priceforthequantityarray['rowid'] != $pqp) continue;
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT')
							{
								$pu_ht = $priceforthequantityarray['unitprice'];
							}
							else
							{
								$pu_ttc = $priceforthequantityarray['unitprice'];
							}
							// Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
							break;
						}
					}
				}
				// If price per quantity and customer
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
				{
					if ($prod->prices_by_qty[$object->thirdparty->price_level])	// yes, this product has some prices per quantity
					{
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOST('pbq', 'int');
						// Search price into product_price_by_qty from $prod->id
						foreach($prod->prices_by_qty_list[$object->thirdparty->price_level] as $priceforthequantityarray)
						{
							if ($priceforthequantityarray['rowid'] != $pqp) continue;
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT')
							{
								$pu_ht = $priceforthequantityarray['unitprice'];
							}
							else
							{
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
					if (empty($newlang) && GETPOST('lang_id', 'aZ09'))
						$newlang = GETPOST('lang_id', 'aZ09');
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

				$desc = dol_concatdesc($desc, $product_desc, '', !empty($conf->global->CHANGE_ORDER_CONCAT_DESCRIPTION));

				// Add custom code and origin country into description
				if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code))) {
					$tmptxt = '(';
					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id', 'alpha'))
							$newlang = GETPOST('lang_id', 'alpha');
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
				$fk_unit=GETPOST('units', 'alpha');
				$pu_ht_devise = price2num($price_ht_devise, 'MU');
			}

			// Margin
			$fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
			$buyingprice = price2num(GETPOST('buying_price' . $predef) != '' ? GETPOST('buying_price' . $predef) : '');    // If buying_price is '0', we muste keep this value

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

			$desc = dol_htmlcleanlastbr($desc);

			$info_bits = 0;
			if ($tva_npr)
				$info_bits |= 0x01;

			if (((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS) )&& (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min)))) {
				$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
				setEventMessages($mesg, null, 'errors');
			} else {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $info_bits, 0, $price_base_type, $pu_ttc, $date_start, $date_end, $type, - 1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $fk_unit, '', 0, $pu_ht_devise);

				if ($result > 0) {
					$ret = $object->fetch($object->id); // Reload to get new records

					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						// Define output language
						$outputlangs = $langs;
						$newlang = GETPOST('lang_id', 'alpha');
						if (! empty($conf->global->MAIN_MULTILANGS) && empty($newlang))
							$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}

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
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	}

	/*
	 *  Update a line
	 */
	elseif ($action == 'updateline' && $user->rights->commande->creer && GETPOST('save'))
	{
		// Clean parameters
		$date_start='';
		$date_end='';
		$date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
		$description=dol_htmlcleanlastbr(GETPOST('product_desc', 'none'));
		$pu_ht=GETPOST('price_ht');
		$vat_rate=(GETPOST('tva_tx')?GETPOST('tva_tx'):0);
		$pu_ht_devise = GETPOST('multicurrency_subprice');

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate))
			$info_bits |= 0x01;

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');    // If buying_price is '0', we muste keep this value

		// Extrafields Lines
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($object->table_element_line);
		// Unset extrafield POST Data
		if (is_array($extralabelsline)) {
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
			$product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if ((! empty($conf->global->PRODUIT_MULTIPRICES) || ! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) && ! empty($object->thirdparty->price_level))
				$price_min = $product->multiprices_min[$object->thirdparty->price_level];

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			if (((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS) )&& ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min)))) {
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

		if (! $error) {

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
			$result = $object->updateline(GETPOST('lineid'), $description, $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('units'), $pu_ht_devise);

			if ($result >= 0) {
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
						$newlang = GETPOST('lang_id', 'aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))
						$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$ret = $object->fetch($object->id); // Reload to get new records
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
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'updateline' && $user->rights->commande->creer && GETPOST('cancel', 'alpha') == $langs->trans('Cancel')) {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	}

	elseif ($action == 'confirm_validate' && $confirm == 'yes' &&
		((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
	   	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate)))
	)
	{
		$idwarehouse = GETPOST('idwarehouse');

		$qualified_for_stock_change=0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
		}
		else
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
		}

		// Check parameters
		if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == -1)
			{
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action='';
			}
		}

		if (! $error) {
			$result = $object->valid($user, $idwarehouse);
			if ($result >= 0)
			{
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model=$object->modelpdf;
					$ret = $object->fetch($id); // Reload to get new records

					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Go back to draft status
	elseif ($action == 'confirm_modif' && $user->rights->commande->creer) {
		$idwarehouse = GETPOST('idwarehouse');

		$qualified_for_stock_change=0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
		}
		else
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
		}

		// Check parameters
		if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == -1)
			{
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action='';
			}
		}

		if (! $error) {
			$result = $object->set_draft($user, $idwarehouse);
			if ($result >= 0)
			{
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model=$object->modelpdf;
					$ret = $object->fetch($id); // Reload to get new records

					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}
		}
	}

	elseif ($action == 'confirm_shipped' && $confirm == 'yes' && $user->rights->commande->cloturer) {
		$result = $object->cloture($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	elseif ($action == 'confirm_cancel' && $confirm == 'yes' &&
		((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
	   	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate)))
	)
	{
		$idwarehouse = GETPOST('idwarehouse');

		$qualified_for_stock_change=0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
		}
		else
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
		}

		// Check parameters
		if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == -1)
			{
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action='';
			}
		}

		if (! $error) {
			$result = $object->cancel($idwarehouse);

			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('ORDER_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	if ($action == 'set_thirdparty' && $user->rights->commande->creer)
	{
		$object->fetch($id);
		$object->setValueFrom('fk_soc', $socid, '', '', 'date', '', $user, 'ORDER_MODIFY');

		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
		exit();
	}

	// add lines from objectlinked
	if($action == 'import_lines_from_object'
	    && $user->rights->commande->creer
	    && $object->statut == Commande::STATUS_DRAFT
	  )
	{
	    $fromElement = GETPOST('fromelement');
	    $fromElementid = GETPOST('fromelementid');
	    $importLines = GETPOST('line_checkbox');

	    if(!empty($importLines) && is_array($importLines) && !empty($fromElement) && ctype_alpha($fromElement) && !empty($fromElementid))
	    {
	        if($fromElement == 'commande')
	        {
	            dol_include_once('/'.$fromElement.'/class/'.$fromElement.'.class.php');
	            $lineClassName = 'OrderLine';
	        }
	        elseif($fromElement == 'propal')
	        {
	            dol_include_once('/comm/'.$fromElement.'/class/'.$fromElement.'.class.php');
	            $lineClassName = 'PropaleLigne';
	        }
	        $nextRang = count($object->lines) + 1;
	        $importCount = 0;
	        $error = 0;
	        foreach($importLines as $lineId)
	        {
	            $lineId = intval($lineId);
	            $originLine = new $lineClassName($db);
	            if(intval($fromElementid) > 0 && $originLine->fetch($lineId) > 0)
	            {
	                $originLine->fetch_optionals($lineId);
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
	                $price_base_type='HT';
	                $pu_ttc=0;
	                $type = $originLine->product_type;
	                $rang=$nextRang++;
	                $special_code = $originLine->special_code;
	                $origin = $originLine->element;
	                $origin_id = $originLine->id;
	                $fk_parent_line=0;
	                $fk_fournprice=$originLine->fk_fournprice;
	                $pa_ht = $originLine->pa_ht;
	                $label = $originLine->label;
	                $array_options = $originLine->array_options;
	                $situation_percent = 100;
	                $fk_prev_id = '';
	                $fk_unit = $originLine->fk_unit;
	                $pu_ht_devise = $originLine->multicurrency_subprice;

	                $res = $object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $date_start, $date_end, $type, $rang, $special_code, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $fk_unit, $origin, $origin_id, $pu_ht_devise);

	                if($res > 0){
	                    $importCount++;
	                }else{
	                    $error++;
	                }
	            }
	            else{
	                $error++;
	            }
	        }

	        if($error)
	        {
	            setEventMessages($langs->trans('ErrorsOnXLines', $error), null, 'errors');
	        }
	    }
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to build doc
	$upload_dir = $conf->commande->dir_output;
	$permissioncreate = $user->rights->commande->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions to send emails
	$trigger_name='ORDER_SENTBYMAIL';
	$paramname='id';
	$autocopy='MAIN_MAIL_AUTOCOPY_ORDER_TO';		// used to know the automatic BCC to add
	$trackid='ord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	if (! $error && ! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->commande->creer)
	{
		if ($action == 'addcontact')
		{
			if ($object->id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$result = $object->add_contact($contactid, GETPOST('type'), GETPOST('source'));
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

		// bascule du statut d'un contact
		elseif ($action == 'swapstatut')
		{
			if ($object->id > 0) {
				$result = $object->swapContactStatus(GETPOST('ligne'));
			} else {
				dol_print_error($db);
			}
		}

		// Efface un contact
		elseif ($action == 'deletecontact')
		{
			$result = $object->delete_contact($lineid);

			if ($result >= 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
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

llxHeader('', $langs->trans('Order'), 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$form = new Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$formmargin = new FormMargin($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

// Mode creation
if ($action == 'create' && $user->rights->commande->creer)
{
	print load_fiche_titre($langs->trans('CreateOrder'), '', 'title_commercial.png');

	$soc = new Societe($db);
	if ($socid > 0)
		$res = $soc->fetch($socid);

	$remise_absolue = 0;

	$currency_code = $conf->currency;

	if (! empty($origin) && ! empty($originid)) {
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs [1];
			$subelement = $regs [2];
		}

		if ($element == 'project') {
			$projectid = $originid;

			if (!$cond_reglement_id) {
				$cond_reglement_id = $soc->cond_reglement_id;
			}
			if (!$mode_reglement_id) {
				$mode_reglement_id = $soc->mode_reglement_id;
			}
			if (!$remise_percent) {
				$remise_percent = $soc->remise_percent;
			}
			if (!$dateorder) {
				// Do not set 0 here (0 for a date is 1970)
				$dateorder = (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE_ODER)?-1:''):$dateorder);
			}
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

			dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
				$objectsrc->fetch_lines();
			$objectsrc->fetch_thirdparty();

			// Replicate extrafields
			$objectsrc->fetch_optionals($originid);
			$object->array_options = $objectsrc->array_options;

			$projectid = (! empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
			$ref_client = (! empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

			$soc = $objectsrc->thirdparty;
			$cond_reglement_id	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:0)); // TODO maybe add default value option
			$mode_reglement_id	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
			$fk_account         = (! empty($objectsrc->fk_account)?$objectsrc->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
			$availability_id	= (!empty($objectsrc->availability_id)?$objectsrc->availability_id:(!empty($soc->availability_id)?$soc->availability_id:0));
			$shipping_method_id = (! empty($objectsrc->shipping_method_id)?$objectsrc->shipping_method_id:(! empty($soc->shipping_method_id)?$soc->shipping_method_id:0));
			$warehouse_id       = (! empty($objectsrc->warehouse_id)?$objectsrc->warehouse_id:(! empty($soc->warehouse_id)?$soc->warehouse_id:0));
			$demand_reason_id	= (!empty($objectsrc->demand_reason_id)?$objectsrc->demand_reason_id:(!empty($soc->demand_reason_id)?$soc->demand_reason_id:0));
			$remise_percent		= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
			$remise_absolue		= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
			$dateorder		    = empty($conf->global->MAIN_AUTOFILL_DATE_ORDER)?-1:'';

			$datedelivery = (! empty($objectsrc->date_livraison) ? $objectsrc->date_livraison : '');

			if (!empty($conf->multicurrency->enabled))
			{
				if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
				if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
			}

			$note_private = $object->getDefaultCreateValueFor('note_private', (! empty($objectsrc->note_private) ? $objectsrc->note_private : null));
			$note_public = $object->getDefaultCreateValueFor('note_public', (! empty($objectsrc->note_public) ? $objectsrc->note_public : null));

			// Object source contacts list
			$srccontactslist = $objectsrc->liste_contact(- 1, 'external', 1);
		}
	}
	else
	{
		$cond_reglement_id  = $soc->cond_reglement_id;
		$mode_reglement_id  = $soc->mode_reglement_id;
		$fk_account         = $soc->fk_account;
		$availability_id    = $soc->availability_id;
		$shipping_method_id = $soc->shipping_method_id;
		$warehouse_id       = $soc->warehouse_id;
		$demand_reason_id   = $soc->demand_reason_id;
		$remise_percent     = $soc->remise_percent;
		$remise_absolue     = 0;
		$dateorder          = empty($conf->global->MAIN_AUTOFILL_DATE_ORDER)?-1:'';

		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;

		$note_private = $object->getDefaultCreateValueFor('note_private');
		$note_public = $object->getDefaultCreateValueFor('note_public');
	}

	print '<form name="crea_commande" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input type="hidden" name="remise_percent" value="' . $soc->remise_percent . '">';
	print '<input type="hidden" name="origin" value="' . $origin . '">';
	print '<input type="hidden" name="originid" value="' . $originid . '">';
	if (!empty($currency_tx)) print '<input type="hidden" name="originmulticurrency_tx" value="' . $currency_tx . '">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>' . $langs->trans("Draft") . '</td></tr>';

	// Reference client
	print '<tr><td>' . $langs->trans('RefCustomer') . '</td><td>';
	if (!empty($conf->global->MAIN_USE_PROPAL_REFCLIENT_FOR_ORDER) && ! empty($origin) && ! empty($originid))
		print '<input type="text" name="ref_client" value="'.$ref_client.'"></td>';
	else
		print '<input type="text" name="ref_client" value="'.GETPOST('ref_client').'"></td>';
	print '</tr>';

	// Thirdparty
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans('Customer') . '</td>';
	if ($socid > 0) {
		print '<td>';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="' . $soc->id . '">';
		print '</td>';
	} else {
		print '<td>';
		print $form->select_company('', 'socid', '(s.client = 1 OR s.client = 3)', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
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
		print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'">'.$langs->trans("AddThirdParty").' <span class="fa fa-plus-circle valignmiddle"></span></a>';
		print '</td>';
	}
	print '</tr>' . "\n";

	// Contact of order
	if ($socid > 0) {
		print "<tr><td>" . $langs->trans("DefaultContact") . '</td><td>';
		$form->select_contacts($soc->id, $setcontact, 'contactid', 1, $srccontactslist);
		print '</td></tr>';

		// Ligne info remises tiers
		print '<tr><td>' . $langs->trans('Discounts') . '</td><td>';

		$absolute_discount = $soc->getAvailableDiscounts();

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = urlencode($_SERVER["PHP_SELF"] . '?socid=' . $thirdparty->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid'));
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';
	}
	// Date
	print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td>';
	print $form->selectDate('', 're', '', '', '', "crea_commande", 1, 1);			// Always autofill date with current date
	print '</td></tr>';

	// Delivery date planed
	print "<tr><td>".$langs->trans("DateDeliveryPlanned").'</td><td>';
	if (empty($datedelivery))
	{
		if (! empty($conf->global->DATE_LIVRAISON_WEEK_DELAY)) $datedelivery = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		else $datedelivery=empty($conf->global->MAIN_AUTOFILL_DATE_DELIVERY)?-1:'';
	}
	print $form->selectDate($datedelivery, 'liv_', '', '', '', "crea_commande", 1, 1);
	print "</td></tr>";

	// Conditions de reglement
	print '<tr><td class="nowrap">' . $langs->trans('PaymentConditionsShort') . '</td><td>';
	$form->select_conditions_paiements($cond_reglement_id, 'cond_reglement_id', - 1, 1);
	print '</td></tr>';

	// Mode de reglement
	print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td>';
	$form->select_types_paiements($mode_reglement_id, 'mode_reglement_id');
	print '</td></tr>';

	// Bank Account
	if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER) && ! empty($conf->banque->enabled))
	{
		print '<tr><td>' . $langs->trans('BankAccount') . '</td><td>';
		$form->select_comptes($fk_account, 'fk_account', 0, '', 1);
		print '</td></tr>';
	}

	// Delivery delay
	print '<tr class="fielddeliverydelay"><td>' . $langs->trans('AvailabilityPeriod') . '</td><td>';
	$form->selectAvailabilityDelay($availability_id, 'availability_id', '', 1);
	print '</td></tr>';

	// Shipping Method
	if (! empty($conf->expedition->enabled)) {
		print '<tr><td>' . $langs->trans('SendingMethod') . '</td><td>';
		print $form->selectShippingMethod($shipping_method_id, 'shipping_method_id', '', 1);
		print '</td></tr>';
	}

	// Warehouse
	if (! empty($conf->expedition->enabled) && ! empty($conf->global->WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER)) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
		$formproduct=new FormProduct($db);
		print '<tr><td>' . $langs->trans('Warehouse') . '</td><td>';
		print $formproduct->selectWarehouses($warehouse_id, 'warehouse_id', '', 1);
		print '</td></tr>';
	}

	// What trigger creation
	print '<tr><td>' . $langs->trans('Channel') . '</td><td>';
	$form->selectInputReason($demand_reason_id, 'demand_reason_id', '', 1);
	print '</td></tr>';

	// TODO How record was recorded OrderMode (llx_c_input_method)

	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		print '<tr>';
		print '<td>' . $langs->trans("Project") . '</td><td>';
		$numprojet = $formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0);
		print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'">' . $langs->trans("AddProject") . ' <span class="fa fa-plus-circle valignmiddle"></span></a>';
		print '</td>';
		print '</tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $objectsrc->libelle_incoterms, 1).'</label></td>';
		print '<td class="maxwidthonsmartphone">';
		$incoterm_id = GETPOST('incoterm_id');
		$incoterm_location = GETPOST('location_incoterms');
		if (empty($incoterm_id))
		{
			$incoterm_id = (!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : $soc->fk_incoterms);
			$incoterm_location = (!empty($objectsrc->location_incoterms) ? $objectsrc->location_incoterms : $soc->location_incoterms);
		}
		print $form->select_incoterms($incoterm_id, $incoterm_location);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('objectsrc' => $objectsrc, 'socid'=>$socid);
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	// Template to use by default
	print '<tr><td>' . $langs->trans('DefaultModel') . '</td>';
	print '<td>';
	include_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
	$liste = ModelePDFCommandes::liste_modeles($db);
	print $form->selectarray('model', $liste, $conf->global->COMMANDE_ADDON_PDF);
	print "</td></tr>";

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		print '<tr>';
		print '<td>'.$form->editfieldkey("Currency", 'multicurrency_code', '', $object, 0).'</td>';
		print '<td class="maxwidthonsmartphone">';
		print $form->selectMultiCurrency($currency_code, 'multicurrency_code');
		print '</td></tr>';
	}

	// Note public
	print '<tr>';
	print '<td class="tdtop">' . $langs->trans('NotePublic') . '</td>';
	print '<td>';

	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);
	// print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_public.'</textarea>';
	print '</td></tr>';

	// Note private
	if (empty($user->societe_id)) {
		print '<tr>';
		print '<td class="tdtop">' . $langs->trans('NotePrivate') . '</td>';
		print '<td>';

		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		// print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'</textarea>';
		print '</td></tr>';
	}

	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		// TODO for compatibility
		if ($origin == 'contrat') {
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue = $remise_absolue;
			$objectsrc->remise_percent = $remise_percent;
			$objectsrc->update_price(1);
		}

		print "\n<!-- " . $classname . " info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
		print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
		print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
		print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
		print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

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

		print '<tr><td>' . $langs->trans($newclassname) . '</td><td>' . $objectsrc->getNomUrl(1) . '</td></tr>';

		// Amount
		print '<tr><td>' . $langs->trans('AmountHT') . '</td><td>' . price($objectsrc->total_ht) . '</td></tr>';
		print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td>' . price($objectsrc->total_tva) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1 RE
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td>' . price($objectsrc->total_localtax1) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2 IRPF
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td>' . price($objectsrc->total_localtax2) . "</td></tr>";
		}

		print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td>' . price($objectsrc->total_ttc) . "</td></tr>";

		if (!empty($conf->multicurrency->enabled))
		{
			print '<tr><td>' . $langs->trans('MulticurrencyAmountHT') . '</td><td>' . price($objectsrc->multicurrency_total_ht) . '</td></tr>';
			print '<tr><td>' . $langs->trans('MulticurrencyAmountVAT') . '</td><td>' . price($objectsrc->multicurrency_total_tva) . "</td></tr>";
			print '<tr><td>' . $langs->trans('MulticurrencyAmountTTC') . '</td><td>' . price($objectsrc->multicurrency_total_ttc) . "</td></tr>";
		}
	}

	print '</table>';

	dol_fiche_end();

	// Button "Create Draft"
	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {
		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}
} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) {
		$product_static = new Product($db);

		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals();

		$head = commande_prepare_head($object);
		dol_fiche_head($head, 'order', $langs->trans("CustomerOrder"), -1, 'order');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
		}

		// Confirmation of validation
		if ($action == 'validate')
		{
			// on verifie si l'objet est en numerotation provisoire
			$ref = substr($object->ref, 1, 4);
			if ($ref == 'PROV') {
				$numref = $object->getNextNumRef($soc);
			} else {
				$numref = $object->ref;
			}

			$text = $langs->trans('ConfirmValidateOrder', $numref);
			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
				$notify = new Notify($db);
				$text .= '<br>';
				$text .= $notify->confirmMessage('ORDER_VALIDATE', $object->socid, $object);
			}

			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
			   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
			   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}

			$formquestion=array();
			if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $qualified_for_stock_change)
			{
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$forcecombo=0;
				if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other','name' => 'idwarehouse','label' => $langs->trans("SelectWarehouseForStockDecrease"),'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse', 'int')?GETPOST('idwarehouse', 'int'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateOrder'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
		}

		// Confirm back to draft status
		if ($action == 'modif')
		{
			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
			   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
			   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}

			$text=$langs->trans('ConfirmUnvalidateOrder', $object->ref);
			$formquestion=array();
			if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $qualified_for_stock_change)
			{
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$forcecombo=0;
				if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other','name' => 'idwarehouse','label' => $langs->trans("SelectWarehouseForStockIncrease"),'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('UnvalidateOrder'), $text, 'confirm_modif', $formquestion, "yes", 1, 220);
		}

		/*
		 * Confirmation de la cloture
		*/
		if ($action == 'shipped') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_shipped', '', 0, 1);
		}

		/*
		 * Confirmation de l'annulation
		 */
		if ($action == 'cancel')
		{
			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
			   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
			   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}

			$text=$langs->trans('ConfirmCancelOrder', $object->ref);
			$formquestion=array();
			if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $qualified_for_stock_change)
			{
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$forcecombo=0;
				if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other','name' => 'idwarehouse','label' => $langs->trans("SelectWarehouseForStockIncrease"),'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
				);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Cancel'), $text, 'confirm_cancel', $formquestion, 0, 1);
		}

		// Confirmation to delete line
		if ($action == 'ask_deleteline')
		{
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
		}

		// Clone confirmation
		if ($action == 'clone') {
			// Create an array for form
			$formquestion = array(
								// 'text' => $langs->trans("ConfirmClone"),
								// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
								// 1),
								// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
								// => 1),
								array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '(s.client=1 OR s.client=3)')));
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneOrder', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
		}

		// Call Hook formConfirm
		$parameters = array('lineid' => $lineid);
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action);
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;


		// Order card

		$linkback = '<a href="' . DOL_URL_ROOT . '/commande/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


		$morehtmlref='<div class="refidno">';
		// Ref customer
		$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->commande->creer, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->commande->creer, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
		if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref.=' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		// Project
		if (! empty($conf->projet->enabled))
		{
			$langs->load("projects");
			$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
			if ($user->rights->commande->creer)
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

		print '<table class="border tableforfield" width="100%">';

		if ($soc->outstanding_limit)
		{
			// Outstanding Bill
			print '<tr><td class="titlefield">';
			print $langs->trans('OutstandingBill');
			print '</td><td>';
			print price($soc->get_OutstandingBill()) . ' / ';
			print price($soc->outstanding_limit, 0, '', 1, - 1, - 1, $conf->currency);
			print '</td>';
			print '</tr>';
		}

		// Relative and absolute discounts
		if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
			$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
			$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
		}

		$addrelativediscount = '<a href="' . DOL_URL_ROOT . '/comm/remise.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("EditRelativeDiscounts") . '</a>';
		$addabsolutediscount = '<a href="' . DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("EditGlobalDiscounts") . '</a>';
		$addcreditnote = '<a href="' . DOL_URL_ROOT . '/compta/facture/card.php?action=create&socid=' . $soc->id . '&type=2&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("AddCreditNote") . '</a>';

		print '<tr><td class="titlefield">' . $langs->trans('Discounts') . '</td><td>';

		$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
		$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = urlencode($_SERVER["PHP_SELF"] . '?id=' . $object->id);
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';

		// Date
		print '<tr><td>';
		$editenable = $user->rights->commande->creer && $object->statut == Commande::STATUS_DRAFT;
		print $form->editfieldkey("Date", 'date', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editdate') {
			print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setdate">';
			print $form->selectDate($object->date, 'order_', '', '', '', "setdate");
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->date ? dol_print_date($object->date, 'day') : '&nbsp;';
			if ($object->hasDelay() && ! empty($object->date_livraison)) {
				print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
			}
		}
		print '</td>';
		print '</tr>';

		// Delivery date planed
		print '<tr><td>';
		$editenable = $user->rights->commande->creer;
		print $form->editfieldkey("DateDeliveryPlanned", 'date_livraison', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editdate_livraison') {
			print '<form name="setdate_livraison" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setdate_livraison">';
			print $form->selectDate($object->date_livraison ? $object->date_livraison : - 1, 'liv_', '', '', '', "setdate_livraison");
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->date_livraison ? dol_print_date($object->date_livraison, 'daytext') : '&nbsp;';
			if ($object->hasDelay() && ! empty($object->date_livraison)) {
				print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
			}
		}
		print '</td>';
		print '</tr>';

		// Shipping Method
		if (! empty($conf->expedition->enabled)) {
			print '<tr><td>';
			$editenable = $user->rights->commande->creer;
			print $form->editfieldkey("SendingMethod", 'shippingmethod', '', $object, $editenable);
			print '</td><td>';
			if ($action == 'editshippingmethod') {
				$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
			} else {
				$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Warehouse
		if (! empty($conf->expedition->enabled) && ! empty($conf->global->WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER)) {
			$langs->load('stocks');
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct=new FormProduct($db);
			print '<tr><td>';
			$editenable = $user->rights->commande->creer;
			print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $editenable);
			print '</td><td>';
			if ($action == 'editwarehouse') {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'warehouse_id', 1);
			} else {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Terms of payment
		print '<tr><td>';
		$editenable = $user->rights->commande->creer;
		print $form->editfieldkey("PaymentConditionsShort", 'conditions', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editconditions') {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
		} else {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'none', 1);
		}
		print '</td>';

		print '</tr>';

		// Mode of payment
		print '<tr><td>';
		$editenable = $user->rights->commande->creer;
		print $form->editfieldkey("PaymentMode", 'mode', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editmode') {
			$form->form_modes_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
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
			$editenable = $user->rights->commande->creer && $object->statut == Commande::STATUS_DRAFT;
			print $form->editfieldkey("Currency", 'multicurrencycode', '', $object, $editenable);
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
			$editenable = $user->rights->commande->creer && $object->multicurrency_code && $object->multicurrency_code != $conf->currency && $object->statut == Commande::STATUS_DRAFT;
			print $form->editfieldkey("CurrencyRate", 'multicurrencyrate', '', $object, $editenable);
			print '</td><td>';
			if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
				if($action == 'actualizemulticurrencyrate') {
					list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
				}
				$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
			} else {
				$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
				if($object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
					print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate">'.$langs->trans("ActualizeCurrency").'</a>';
					print '</div>';
				}
			}
			print '</td></tr>';
		}

		// Delivery delay
		print '<tr class="fielddeliverydelay"><td>';
		$editenable = $user->rights->commande->creer;
		print $form->editfieldkey("AvailabilityPeriod", 'availability', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editavailability') {
			$form->form_availability($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->availability_id, 'availability_id', 1);
		} else {
			$form->form_availability($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->availability_id, 'none', 1);
		}
		print '</td></tr>';

		// Source reason (why we have an ordrer)
		print '<tr><td>';
		$editenable = $user->rights->commande->creer;
		print $form->editfieldkey("Channel", 'demandreason', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editdemandreason') {
			$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'demand_reason_id', 1);
		} else {
			$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'none');
		}
		print '</td></tr>';

		// TODO Order mode (how we receive order). Not yet implemented
		/*
		print '<tr><td>';
		$editenable = $user->rights->commande->creer;
		print $form->editfieldkey("SourceMode", 'inputmode', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editinputmode') {
			$form->formInputMode($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->source, 'input_mode_id', 1);
		} else {
			$form->formInputMode($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->source, 'none');
		}
		print '</td></tr>';
		*/

		$tmparray=$object->getTotalWeightVolume();
		$totalWeight=$tmparray['weight'];
		$totalVolume=$tmparray['volume'];
		if ($totalWeight) {
			print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
			print '<td>';
			print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND)?$conf->global->MAIN_WEIGHT_DEFAULT_ROUND:-1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT)?$conf->global->MAIN_WEIGHT_DEFAULT_UNIT:'no');
			print '</td></tr>';
		}
		if ($totalVolume) {
			print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
			print '<td>';
			print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND)?$conf->global->MAIN_VOLUME_DEFAULT_ROUND:-1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT)?$conf->global->MAIN_VOLUME_DEFAULT_UNIT:'no');
			print '</td></tr>';
		}

		// TODO How record was recorded OrderMode (llx_c_input_method)

		// Incoterms
		if (!empty($conf->incoterm->enabled)) {
			print '<tr><td>';
			$editenable = $user->rights->commande->creer;
			print $form->editfieldkey("IncotermLabel", 'incoterm', '', $object, $editenable);
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

		// Bank Account
		if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER) && ! empty($conf->banque->enabled)) {
			print '<tr><td>';
			$editenable = $user->rights->commande->creer;
			print $form->editfieldkey("BankAccount", 'bankaccount', '', $object, $editenable);
			print '</td><td>';
			if ($action == 'editbankaccount') {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
			} else {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
		{
			// Multicurrency Amount HT
			print '<tr><td class="titlefieldmiddle">' . $form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0) . '</td>';
			print '<td class="nowrap">' . price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
			print '</tr>';

			// Multicurrency Amount VAT
			print '<tr><td>' . $form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0) . '</td>';
			print '<td class="nowrap">' . price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
			print '</tr>';

			// Multicurrency Amount TTC
			print '<tr><td>' . $form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0) . '</td>';
			print '<td class="nowrap">' . price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
			print '</tr>';
		}

		// Total HT
		$alert = '';
		if (! empty($conf->global->ORDER_MANAGE_MIN_AMOUNT) && $object->total_ht < $object->thirdparty->order_min_amount) {
			$alert = ' ' . img_warning($langs->trans('OrderMinAmount').': '.price($object->thirdparty->order_min_amount));
		}
		print '<tr><td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
		print '<td>' . price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency) . $alert . '</td>';

		// Total VAT
		print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td>' . price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

		// Amount Local Taxes
		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) 		// Localtax1
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
			print '<td>' . price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
		}
		if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) 		// Localtax2 IRPF
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
			print '<td>' . price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
		}

		// Total TTC
		print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td>' . price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

		// Statut
		//print '<tr><td>' . $langs->trans('Status') . '</td><td>' . $object->getLibStatut(4) . '</td></tr>';

		print '</table>';

		// Margin Infos
		if (! empty($conf->margin->enabled)) {
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
		$result = $object->getLinesArray();

		print '<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid')) . '" method="POST">
		<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id . '">';

		if (! empty($conf->use_javascript_ajax) && $object->statut == Commande::STATUS_DRAFT) {
			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		// Show object lines
		if (! empty($object->lines))
			$ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1);

		$numlines = count($object->lines);

		/*
		 * Form to add new line
		 */
		if ($object->statut == Commande::STATUS_DRAFT && $user->rights->commande->creer && $action != 'selectlines')
		{
			if ($action != 'editline')
			{
				// Add free products/services
				$object->formAddObjectLine(1, $mysoc, $soc);

				$parameters = array();
				// Note that $action and $object may be modified by hook
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);
			}
		}
		print '</table>';
		print '</div>';

		print "</form>\n";

		dol_fiche_end();

		/*
		 * Buttons for actions
		 */
		if ($action != 'presend' && $action != 'editline') {
			print '<div class="tabsAction">';

			$parameters = array();
			// Note that $action and $object may be modified by hook
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
			if (empty($reshook)) {
				// Send
				if ($object->statut > Commande::STATUS_DRAFT) {
					if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->commande->order_advance->send)) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a></div>';
					} else
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">' . $langs->trans('SendMail') . '</a></div>';
				}

				// Valid
				if ($object->statut == Commande::STATUS_DRAFT && $object->total_ttc >= 0 && $numlines > 0 &&
					((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
				   	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate)))
				)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=validate">' . $langs->trans('Validate') . '</a></div>';
				}
				// Edit
				if ($object->statut == Commande::STATUS_VALIDATED && $user->rights->commande->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a></div>';
				}
				// Create event
				/*if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD))
				{
					// Add hidden condition because this is not a
					// "workflow" action so should appears somewhere else on
					// page.
					print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a>';
				}*/

				// Create intervention
				if ($conf->ficheinter->enabled) {
					$langs->load("interventions");

					if ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED && $object->getNbOfServicesLines() > 0) {
						if ($user->rights->ficheinter->creer) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fichinter/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans('AddIntervention') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AddIntervention') . '</a></div>';
						}
					}
				}

				// Create contract
				if ($conf->contrat->enabled && ($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_SHIPMENTONPROCESS || $object->statut == Commande::STATUS_CLOSED)) {
					$langs->load("contracts");

					if ($user->rights->contrat->creer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans('AddContract') . '</a></div>';
					}
				}

				// Ship
				$numshipping = 0;
				if (! empty($conf->expedition->enabled)) {
					$numshipping = $object->nb_expedition();

					if ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED && ($object->getNbOfProductsLines() > 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES))) {
						if (($conf->expedition_bon->enabled && $user->rights->expedition->creer) || ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)) {
							if ($user->rights->expedition->creer) {
								print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/expedition/shipment.php?id=' . $object->id . '">' . $langs->trans('CreateShipment') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('CreateShipment') . '</a></div>';
							}
						} else {
							$langs->load("errors");
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")) . '">' . $langs->trans('CreateShipment') . '</a></div>';
						}
					}
				}

				// Reopen a closed order
				if (($object->statut == Commande::STATUS_CLOSED || $object->statut == Commande::STATUS_CANCELED) && $user->rights->commande->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=reopen">' . $langs->trans('ReOpen') . '</a></div>';
				}

				// Set to shipped
				if (($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_SHIPMENTONPROCESS) && $user->rights->commande->cloturer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=shipped">' . $langs->trans('ClassifyShipped') . '</a></div>';
				}

				// Create bill and Classify billed
				// Note: Even if module invoice is not enabled, we should be able to use button "Classified billed"
				if ($object->statut > Commande::STATUS_DRAFT && ! $object->billed) {
					if (! empty($conf->facture->enabled) && $user->rights->facture->creer && empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER)) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("CreateBill") . '</a></div>';
					}
					if ($user->rights->commande->creer && $object->statut >= Commande::STATUS_VALIDATED && empty($conf->global->WORKFLOW_DISABLE_CLASSIFY_BILLED_FROM_ORDER) && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifybilled">' . $langs->trans("ClassifyBilled") . '</a></div>';
					}
				}
				if ($object->statut > Commande::STATUS_DRAFT && $object->billed) {
					if ($user->rights->commande->creer && $object->statut >= Commande::STATUS_VALIDATED && empty($conf->global->WORKFLOW_DISABLE_CLASSIFY_BILLED_FROM_ORDER) && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifyunbilled">' . $langs->trans("ClassifyUnBilled") . '</a></div>';
					}
				}
				// Clone
				if ($user->rights->commande->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=order">' . $langs->trans("ToClone") . '</a></div>';
				}

				// Cancel order
				if ($object->statut == Commande::STATUS_VALIDATED &&
					((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->cloturer))
				   	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->annuler)))
				)
				{
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=cancel">' . $langs->trans('Cancel') . '</a></div>';
				}

				// Delete order
				if ($user->rights->commande->supprimer) {
					if ($numshipping == 0) {
						print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . $langs->trans("ShippingExist") . '">' . $langs->trans("Delete") . '</a></div>';
					}
				}
			}
			print '</div>';
		}

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend')
		{
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre
			// Documents
			$comref = dol_sanitizeFileName($object->ref);
			$relativepath = $comref . '/' . $comref . '.pdf';
			$filedir = $conf->commande->dir_output . '/' . $comref;
			$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
			$genallowed = $user->rights->commande->lire;
			$delallowed = $user->rights->commande->creer;
			print $formfile->showdocuments('commande', $comref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);


			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, array('order'));

			$compatibleImportElementsList = false;
			if($user->rights->commande->creer
			    && $object->statut == Commande::STATUS_DRAFT)
			{
			    $compatibleImportElementsList = array('commande','propal'); // import from linked elements
			}
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

			// Show online payment link
			$useonlinepayment = (! empty($conf->paypal->enabled) || ! empty($conf->stripe->enabled) || ! empty($conf->paybox->enabled));
			if (! empty($conf->global->ORDER_HIDE_ONLINE_PAYMENT_ON_ORDER)) $useonlinepayment = 0;
			if ($object->statut != Commande::STATUS_DRAFT && $useonlinepayment)
			{
				print '<br><!-- Link to pay -->';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				print showOnlinePaymentUrl('order', $object->ref).'<br>';
			}

			// Show direct download link
			if ($object->statut != Commande::STATUS_DRAFT && ! empty($conf->global->ORDER_ALLOW_EXTERNAL_DOWNLOAD))
			{
				print '<br><!-- Link to download main doc -->'."\n";
				print showDirectDownloadLink($object).'<br>';
			}

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'order', $socid, 1);

			print '</div></div></div>';
		}

		// Presend form
		$modelmail='order_send';
		$defaulttopic='SendOrderRef';
		$diroutput = $conf->commande->dir_output;
		$trackid = 'ord'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
