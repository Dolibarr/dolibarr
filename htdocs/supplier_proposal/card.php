<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2014 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsupplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_proposal/modules_supplier_proposal.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/supplier_proposal.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}

$langs->load('companies');
$langs->load('supplier_proposal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');
$langs->load("deliveries");
$langs->load('sendings');
if (! empty($conf->margin->enabled))
	$langs->load('margins');

$error = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');
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
$result = restrictedArea($user, 'supplier_proposal', $id);

$object = new SupplierProposal($db);
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
$hookmanager->initHooks(array('supplier_proposalcard','globalcard'));

$permissionnote = $user->rights->supplier_proposal->creer; // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not includ_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes')
	{
		if (1 == 0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		}
		else
		{
			if ($object->id > 0) {
				$result = $object->createFromClone($socid);
				if ($result > 0) {
					header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
					exit();
				} 
				else 
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	}

	// Delete askprice
	else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->supplier_proposal->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0) {
			header('Location: ' . DOL_URL_ROOT . '/supplier_proposal/list.php');
			exit();
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans($object->error), null, 'errors');
		}
	}

	// Remove line
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->supplier_proposal->creer)
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
        ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->supplier_proposal->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->supplier_proposal->validate)))
	)
	{
		$result = $object->valid($user);
		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
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

					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}
		} else {
			$langs->load("errors");
			if (count($object->errors) > 0) setEventMessages($object->error, $object->errors, 'errors');
			else setEventMessages($langs->trans($object->error), null, 'errors');
		}
	}

	else if ($action == 'setdate_livraison' && $user->rights->supplier_proposal->creer)
	{
		$result = $object->set_date_livraison($user, dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	// Create askprice
	else if ($action == 'add' && $user->rights->supplier_proposal->creer)
	{
		$object->socid = $socid;
		$object->fetch_thirdparty();

		$date_delivery = dol_mktime(12, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));

		if ($socid < 1) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');
			$action = 'create';
			$error ++;
		}

		if (! $error)
		{
			$db->begin();

			// Si on a selectionne une demande a copier, on realise la copie
			if (GETPOST('createmode') == 'copy' && GETPOST('copie_supplier_proposal'))
			{
				if ($object->fetch(GETPOST('copie_supplier_proposal')) > 0) {
					$object->ref = GETPOST('ref');
					$object->date_livraison = $date_delivery;
	                $object->shipping_method_id = GETPOST('shipping_method_id', 'int');
					$object->cond_reglement_id = GETPOST('cond_reglement_id');
					$object->mode_reglement_id = GETPOST('mode_reglement_id');
					$object->fk_account = GETPOST('fk_account', 'int');
					$object->remise_percent = GETPOST('remise_percent');
					$object->remise_absolue = GETPOST('remise_absolue');
					$object->socid = GETPOST('socid');
					$object->fk_project = GETPOST('projectid');
					$object->modelpdf = GETPOST('model');
					$object->author = $user->id; // deprecated
					$object->note = GETPOST('note');
					$object->statut = 0;

					$id = $object->create_from($user);
				} else {
					setEventMessages($langs->trans("ErrorFailedToCopyProposal", GETPOST('copie_supplier_proposal')), null, 'errors');
				}
			} else {
				$object->ref = GETPOST('ref');
				$object->date_livraison = $date_delivery;
				$object->demand_reason_id = GETPOST('demand_reason_id');
	            $object->shipping_method_id = GETPOST('shipping_method_id', 'int');
				$object->cond_reglement_id = GETPOST('cond_reglement_id');
				$object->mode_reglement_id = GETPOST('mode_reglement_id');
				$object->fk_account = GETPOST('fk_account', 'int');
				$object->fk_project = GETPOST('projectid');
				$object->modelpdf = GETPOST('model');
				$object->author = $user->id; // deprecated
				$object->note = GETPOST('note');

				$object->origin = GETPOST('origin');
				$object->origin_id = GETPOST('originid');

				for($i = 1; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i ++)
				{
					if ($_POST['idprod' . $i]) {
						$xid = 'idprod' . $i;
						$xqty = 'qty' . $i;
						$xremise = 'remise' . $i;
						$object->add_product($_POST[$xid], $_POST[$xqty], $_POST[$xremise]);
					}
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
					$element = 'supplier_proposal';
					$subelement = 'supplier_proposal';

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

									// Reset fk_parent_line for no child products and special product
									if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
										$fk_parent_line = 0;
									}

									// Extrafields
									if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
										$lines[$i]->fetch_optionals($lines[$i]->rowid);
										$array_option = $lines[$i]->array_options;
									}

									$result = $object->addline($desc, $lines[$i]->subprice, $lines[$i]->qty, $lines[$i]->tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent, 'HT', 0, $lines[$i]->info_bits, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_option);

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

	// Reopen proposal
	else if ($action == 'confirm_reopen' && $user->rights->supplier_proposal->cloturer && ! GETPOST('cancel')) {
		// prevent browser refresh from reopening proposal several times
		if ($object->statut == 2 || $object->statut == 3 || $object->statut == 4) {
			$object->reopen($user, 1);
		}
	}

	// Close proposal
	else if ($action == 'setstatut' && $user->rights->supplier_proposal->cloturer && ! GETPOST('cancel')) {
		if (! GETPOST('statut')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("CloseAs")), null, 'errors');
			$action = 'statut';
		} else {
			// prevent browser refresh from closing proposal several times
			if ($object->statut == 1) {
				$object->cloture($user, GETPOST('statut'), GETPOST('note'));
			}
		}
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


	/*
	 * Send mail
	 */

	// Actions to send emails
	$actiontypecode='AC_ASKPRICE';
	$trigger_name='SUPPLIER_PROPOSAL_SENTBYMAIL';
	$paramname='id';
	$mode='emailfromsupplier_proposal';
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';



	// Go back to draft
	if ($action == 'modif' && $user->rights->supplier_proposal->creer)
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

	else if ($action == "setabsolutediscount" && $user->rights->supplier_proposal->creer) {
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
	else if ($action == 'addline' && $user->rights->supplier_proposal->creer) {

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		$price_ht = GETPOST('price_ht');

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
		$array_option = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
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

		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && $price_ht == '') 	// Unit price can be 0 but not ''. Also price can be negative for proposal.
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

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $txtva par celui du produit
			if (! empty($idprod)) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// If prices fields are update
					$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
					$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
					if (empty($tva_tx)) $tva_npr=0;
					
					//On garde le prix indiqué dans l'input pour la demande de prix fournisseur
					//$pu_ht = $prod->price;
					$pu_ht = price2num($price_ht, 'MU');
					//$pu_ttc = $prod->price_ttc;
					$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');

					$price_min = $prod->price_min;
					$price_base_type = $prod->price_base_type;

					// On defini prix unitaire
					if (! empty($conf->global->PRODUIT_MULTIPRICES) && $object->thirdparty->price_level)
					{
						$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
						$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
						$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
						$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
						if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
						if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];
					}
					elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
					{
						require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

						$prodcustprice = new Productcustomerprice($db);

						$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);

						$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
						if ($result) {
							if (count($prodcustprice->lines) > 0) {
								$pu_ht = price($prodcustprice->lines [0]->price);
								$pu_ttc = price($prodcustprice->lines [0]->price_ttc);
								$price_base_type = $prodcustprice->lines [0]->price_base_type;
								$prod->tva_tx = $prodcustprice->lines [0]->tva_tx;
							}
						}
					}

					// if price ht is forced (ie: calculated by margin rate and cost price)
					if (! empty($price_ht)) {
						$pu_ht = price2num($price_ht, 'MU');
						$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
					}

					// On reevalue prix selon taux tva car taux tva transaction peut etre different
					// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
					elseif ($tva_tx != $prod->tva_tx) {
						if ($price_base_type != 'HT') {
							$pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
						} else {
							$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
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

					// Add custom code and origin country into description
					if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code))) {
						$tmptxt = '(';
						if (! empty($prod->customcode))
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code))
							$tmptxt .= ' - ';
						if (! empty($prod->country_code))
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
						$tmptxt .= ')';
						$desc = dol_concatdesc($desc, $tmptxt);
					}

				$type = $prod->type;
			} else {
				$pu_ht = price2num($price_ht, 'MU');
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				$tva_tx = str_replace('*', '', $tva_tx);
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');
			}

			// Margin
			$fournprice = (GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
			$buyingprice = (GETPOST('buying_price' . $predef) ? GETPOST('buying_price' . $predef) : '');

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

			$info_bits = 0;
			if ($tva_npr)
				$info_bits |= 0x01;

			if (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min))) {
				$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
				setEventMessages($mesg, null, 'errors');
			} else {
				// Insert line
				$ref_fourn = GETPOST('fourn_ref');
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $price_base_type, $pu_ttc, $info_bits, $type, - 1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_option, $ref_fourn);

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

				} else {
					$db->rollback();

					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	}

	// Mise a jour d'une ligne dans la demande de prix
	else if ($action == 'updateligne' && $user->rights->supplier_proposal->creer && GETPOST('save') == $langs->trans("Save")) {
		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', GETPOST('tva_tx')))
			$info_bits |= 0x01;

			// Clean parameters
		$description = dol_htmlcleanlastbr(GETPOST('product_desc'));

		// Define vat_rate
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty);
		$pu_ht = GETPOST('price_ht') ? GETPOST('price_ht') : 0;

		// Add buying price
		$fournprice = (GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = (GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');    // If buying_price is '0', we muste keep this value 

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_option = $extrafieldsline->getOptionalsFromPost($extralabelsline);
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
			$ref_fourn = GETPOST('fourn_ref');
			$result = $object->updateline(GETPOST('lineid'), $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, $description, 'HT', $info_bits, $special_code, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $type, $array_option, $ref_fourn);

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
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
			} else {
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	else if ($action == 'updateligne' && $user->rights->supplier_proposal->creer && GETPOST('cancel') == $langs->trans('Cancel')) {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	}

	// Generation doc (depuis lien ou depuis cartouche doc)
	else if ($action == 'builddoc' && $user->rights->supplier_proposal->creer) {
		if (GETPOST('model')) {
			$object->setDocModel($user, GETPOST('model'));
		}

		// Define output language
		$outputlangs = $langs;
		if (! empty($conf->global->MAIN_MULTILANGS)) {
			$outputlangs = new Translate("", $conf);
			$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
			$outputlangs->setDefaultLang($newlang);
		}
		$ret = $object->fetch($id); // Reload to get new records
		$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		if ($result <= 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
	        $action='';
		}
	}

	// Remove file in doc form
	else if ($action == 'remove_file' && $user->rights->supplier_proposal->creer) {
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

			$langs->load("other");
			$upload_dir = $conf->supplier_proposal->dir_output;
			$file = $upload_dir . '/' . GETPOST('file');
			$ret = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret)
				setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
			else
				setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
		}
	}

	// Set project
	else if ($action == 'classin' && $user->rights->supplier_proposal->creer) {
		$object->setProject($_POST['projectid']);
	}

	// Delai de livraison
	else if ($action == 'setavailability' && $user->rights->supplier_proposal->creer) {
		$result = $object->availability($_POST['availability_id']);
	}

	// Conditions de reglement
	else if ($action == 'setconditions' && $user->rights->supplier_proposal->creer) {
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
	}

	else if ($action == 'setremisepercent' && $user->rights->supplier_proposal->creer) {
		$result = $object->set_remise_percent($user, $_POST['remise_percent']);
	}

	else if ($action == 'setremiseabsolue' && $user->rights->supplier_proposal->creer) {
		$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
	}

	// Mode de reglement
	else if ($action == 'setmode' && $user->rights->supplier_proposal->creer) {
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	}

	/*
	 * Ordonnancement des lignes
	*/

	else if ($action == 'up' && $user->rights->supplier_proposal->creer) {
		$object->line_up(GETPOST('rowid'));

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

		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id . '#' . GETPOST('rowid'));
		exit();
	}

	else if ($action == 'down' && $user->rights->supplier_proposal->creer) {
		$object->line_down(GETPOST('rowid'));

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

		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id . '#' . GETPOST('rowid'));
		exit();
	} else if ($action == 'update_extras') {
		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
		if ($ret < 0)
			$error ++;

		if (! $error) {
			// Actions on extra fields (by external module or standard code)
			$hookmanager->initHooks(array('supplier_proposaldao'));
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
}


/*
 * View
 */

llxHeader('', $langs->trans('CommRequests'), 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formsupplier_proposal = new FormSupplierProposal($db);
$formmargin = new FormMargin($db);
$companystatic = new Societe($db);

$now = dol_now();

// Add new askprice
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewAskPrice"));

	$soc = new Societe($db);
	if ($socid > 0)
		$res = $soc->fetch($socid);

	// Load objectsrc
	if (! empty($origin) && ! empty($originid))
	{
		$element = 'supplier_proposal';
		$subelement = 'supplier_proposal';

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
		$soc = $objectsrc->thirdparty;

		$cond_reglement_id 	= (! empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(! empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
		$mode_reglement_id 	= (! empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(! empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
		$remise_percent 	= (! empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(! empty($soc->remise_percent)?$soc->remise_percent:0));
		$remise_absolue 	= (! empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(! empty($soc->remise_absolue)?$soc->remise_absolue:0));

		// Replicate extrafields
		$objectsrc->fetch_optionals($originid);
		$object->array_options = $objectsrc->array_options;

	}

	$object = new SupplierProposal($db);

	print '<form name="addprop" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($origin != 'project' && $originid) {
		print '<input type="hidden" name="origin" value="' . $origin . '">';
		print '<input type="hidden" name="originid" value="' . $originid . '">';
	}

	dol_fiche_head();

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans("Draft") . '</td></tr>';

	// Third party
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans('Supplier') . '</td>';
	if ($socid > 0) {
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="' . $soc->id . '">';
		print '</td>';
	} else {
		print '<td colspan="2">';
		print $form->select_company('', 'socid', 's.fournisseur = 1', 1);
		print '</td>';
	}
	print '</tr>' . "\n";

	// Terms of payment
	print '<tr><td class="nowrap">' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
	$form->select_conditions_paiements($soc->cond_reglement_id, 'cond_reglement_id', -1, 1);
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
	$form->select_types_paiements($soc->mode_reglement_id, 'mode_reglement_id');
	print '</td></tr>';

    // Bank Account
    if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && $conf->banque->enabled) {
        print '<tr><td>' . $langs->trans('BankAccount') . '</td><td colspan="2">';
        $form->select_comptes($fk_account, 'fk_account', 0, '', 1);
        print '</td></tr>';
    }

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
		$form->select_date($syear."-".$smonth."-".$sday, 'liv_', '', '', '', "addask");
	} else {
		$form->select_date(-1, 'liv_', '', '', '', "addask", 1, 1);
	}
	print '</td></tr>';


	// Model
	print '<tr>';
	print '<td>' . $langs->trans("DefaultModel") . '</td>';
	print '<td colspan="2">';
	$liste = ModelePDFSupplierProposal::liste_modeles($db);
	print $form->selectarray('model', $liste, ($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_DEFAULT ? $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_DEFAULT : $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF));
	print "</td></tr>";

	// Project
	if (! empty($conf->projet->enabled) && $socid > 0) {

		$formproject = new FormProjets($db);

		$projectid = 0;
		if ($origin == 'project')
			$projectid = ($originid ? $originid : 0);

		print '<tr>';
		print '<td valign="top">' . $langs->trans("Project") . '</td><td colspan="2">';

		$numprojet = $formproject->select_projects($soc->id, $projectid);
		if ($numprojet == 0) {
			$langs->load("projects");
			print ' &nbsp; <a href="../projet/card.php?socid=' . $soc->id . '&action=create">' . $langs->trans("AddProject") . '</a>';
		}
		print '</td>';
		print '</tr>';
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

		print '<tr><td>' . $langs->trans('CommRequest') . '</td><td colspan="2">' . $objectsrc->getNomUrl(1) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalHT') . '</td><td colspan="2">' . price($objectsrc->total_ht) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalVAT') . '</td><td colspan="2">' . price($objectsrc->total_tva) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0 ) 		// Localtax1
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax1) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax2) . "</td></tr>";
		}
		print '<tr><td>' . $langs->trans('TotalTTC') . '</td><td colspan="2">' . price($objectsrc->total_ttc) . "</td></tr>";
	}

	print "</table>\n";


	/*
	 * Combobox pour la fonction de copie
 	 */

	if (empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE)) print '<input type="hidden" name="createmode" value="empty">';

	if (! empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE) || ! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE)) print '<br><table>';
	if (! empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE))
	{
		// For backward compatibility
		print '<tr>';
		print '<td><input type="radio" name="createmode" value="copy"></td>';
		print '<td>' . $langs->trans("CopyAskFrom") . ' </td>';
		print '<td>';
		$liste_ask = array();
		$liste_ask [0] = '';

		$sql = "SELECT p.rowid as id, p.ref, s.nom";
		$sql .= " FROM " . MAIN_DB_PREFIX . "supplier_proposal p";
		$sql .= ", " . MAIN_DB_PREFIX . "societe s";
		$sql .= " WHERE s.rowid = p.fk_soc";
		$sql .= " AND p.entity = " . $conf->entity;
		$sql .= " AND p.fk_statut <> 0";
		$sql .= " ORDER BY Id";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$askPriceSupplierRefAndSocName = $row [1] . " - " . $row [2];
				$liste_ask [$row [0]] = $askPriceSupplierRefAndSocName;
				$i ++;
			}
			print $form->selectarray("copie_supplier_proposal", $liste_ask, 0);
		} else {
			dol_print_error($db);
		}
		print '</td></tr>';

		if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
			print '<tr><td colspan="3">&nbsp;</td></tr>';

		print '<tr><td valign="top"><input type="radio" name="createmode" value="empty" checked></td>';
		print '<td valign="top" colspan="2">' . $langs->trans("CreateEmptyAsk") . '</td></tr>';
	}

	if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
	{
		print '<tr><td colspan="3">';
		if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) {
			$lib = $langs->trans("ProductsAndServices");

			print '<table class="border" width="100%">';
			print '<tr>';
			print '<td>' . $lib . '</td>';
			print '<td>' . $langs->trans("Qty") . '</td>';
			print '<td>' . $langs->trans("ReductionShort") . '</td>';
			print '</tr>';
			for($i = 1; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i ++) {
				print '<tr><td>';
				// multiprix
				if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
					$form->select_produits('', "idprod" . $i, '', $conf->product->limit_size, $soc->price_level);
				else
					$form->select_produits('', "idprod" . $i, '', $conf->product->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty' . $i . '" value="1"></td>';
				print '<td><input type="text" size="2" name="remise' . $i . '" value="' . $soc->remise_percent . '">%</td>';
				print '</tr>';
			}
			print "</table>";
		}
		print '</td></tr>';
	}
	if (! empty($conf->global->SUPPLIER_PROPOSAL_CLONE_ON_CREATE_PAGE) || ! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE)) print '</table>';

	dol_fiche_end();

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

	$head = supplier_proposal_prepare_head($object);
	dol_fiche_head($head, 'comm', $langs->trans('CommRequest'), 0, 'supplier_proposal');

	$formconfirm = '';

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array(
							// 'text' => $langs->trans("ConfirmClone"),
							// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
							// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' =>
							// 1),
							array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', 's.fournisseur=1')));
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneAsk'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirm delete
	else if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteAsk'), $langs->trans('ConfirmDeleteAsk', $object->ref), 'confirm_delete', '', 0, 1);
	}

	// Confirm reopen
	else if ($action == 'reopen') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenAsk', $object->ref), 'confirm_reopen', '', 0, 1);
	}

	// Confirmation delete product/service line
	else if ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirm validate askprice
	else if ($action == 'validate') {
		$error = 0;

		// on verifie si l'objet est en numerotation provisoire
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

		$text = $langs->trans('ConfirmValidateAsk', $numref);
		if (! empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('SUPPLIER_PROPOSAL_VALIDATE', $object->socid, $object);
		}

		if (! $error)
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateAsk'), $text, 'confirm_validate', '', 0, 1);
	}

	if (! $formconfirm) {
		$parameters = array('lineid' => $lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	print '<table class="border" width="100%">';

	$linkback = '<a href="' . DOL_URL_ROOT . '/supplier_proposal/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	// Ref
	print '<tr><td>' . $langs->trans('Ref') . '</td><td colspan="5">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
	print '</td></tr>';

	// Company
	print '<tr><td>' . $langs->trans('Supplier') . '</td><td colspan="5">' . $soc->getNomUrl(1) . '</td>';
	print '</tr>';

	// Payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($action != 'editconditions' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetConditions'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editconditions') {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
	} else {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'none', 1);
	}
	print '</td>';
	print '</tr>';

	// Delivery date
	$langs->load('deliveries');
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DeliveryDate');
	print '</td>';
	if ($action != 'editdate_livraison' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdate_livraison&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetDeliveryDate'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editdate_livraison') {
		print '<form name="editdate_livraison" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		$form->select_date($object->date_livraison, 'liv_', '', '', '', "editdate_livraison");
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		print dol_print_date($object->date_livraison, 'daytext');
	}
	print '</td>';
	print '</tr>';

	// Payment mode
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && ! empty($object->brouillon))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmode&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMode'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->mode_reglement_id, 'mode_reglement_id');
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->mode_reglement_id, 'none');
	}
	print '</td></tr>';

	// Project
	if (! empty($conf->projet->enabled)) {
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project') . '</td>';
		if ($user->rights->supplier_proposal->creer) {
			if ($action != 'classify')
				print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($action == 'classify') {
				$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid');
			} else {
				$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none');
			}
			print '</td></tr>';
		} else {
			print '</td></tr></table>';
			if (! empty($object->fk_project)) {
				print '<td colspan="3">';
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				print '<a href="../projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				print $proj->ref;
				print '</a>';
				print '</td>';
			} else {
				print '<td colspan="3">&nbsp;</td>';
			}
		}
		print '</tr>';
	}


	if ($soc->outstanding_limit)
	{
		// Outstanding Bill
		print '<tr><td>';
		print $langs->trans('OutstandingBill');
		print '</td><td align=right colspan=3>';
		print price($soc->get_OutstandingBill()) . ' / ';
		print price($soc->outstanding_limit, 0, '', 1, - 1, - 1, $conf->currency);
		print '</td>';
		print '</tr>';
	}

	if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && $conf->banque->enabled)
	{
	    // Bank Account
	    print '<tr><td>';
	    print '<table width="100%" class="nobordernopadding"><tr><td>';
	    print $langs->trans('BankAccount');
	    print '</td>';
	    if ($action != 'editbankaccount' && $user->rights->supplier_proposal->creer)
	        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
	    print '</tr></table>';
	    print '</td><td colspan="3">';
	    if ($action == 'editbankaccount') {
	        $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
	    } else {
	        $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
	    }
	    print '</td>';
	    print '</tr>';
	}

	// Other attributes
	$cols = 3;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	// Amount HT
	print '<tr><td height="10" width="25%">' . $langs->trans('AmountHT') . '</td>';
	print '<td align="right" class="nowrap"><b>' . price($object->total_ht, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
	print '<td></td>';

	// Margin Infos
	if (! empty($conf->margin->enabled)) {
		print '<td valign="top" width="50%" rowspan="4">';
		$formmargin->displayMarginInfos($object);
		print '</td>';
	}
	print '</tr>';

	// Amount VAT
	print '<tr><td height="10">' . $langs->trans('AmountVAT') . '</td>';
	print '<td align="right" class="nowrap">' . price($object->total_tva, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	print '<td></td></tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) 	// Localtax1
	{
		print '<tr><td height="10">' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
		print '<td align="right" class="nowrap">' . price($object->total_localtax1, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
		print '<td></td></tr>';
	}
	if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) 	// Localtax2
	{
		print '<tr><td height="10">' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
		print '<td align="right" class="nowrap">' . price($object->total_localtax2, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
		print '<td></td></tr>';
	}

	// Amount TTC
	print '<tr><td height="10">' . $langs->trans('AmountTTC') . '</td>';
	print '<td align="right" class="nowrap">' . price($object->total_ttc, '', $langs, 0, - 1, - 1, $conf->currency) . '</td>';
	print '<td></td></tr>';

	// Statut
	print '<tr><td height="10">' . $langs->trans('Status') . '</td><td align="left" colspan="2">' . $object->getLibStatut(4) . '</td></tr>';

	print '</table><br>';

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

	print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#add' : '#line_' . GETPOST('lineid')) . '" method="POST">
	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="' . $object->id . '">
	';

	if (! empty($conf->use_javascript_ajax) && $object->statut == 0) {
		include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
	}

	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	if (! empty($object->lines))
		$ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1);

	// Form to add new line
	if ($object->statut == 0 && $user->rights->supplier_proposal->creer)
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

	print "</form>\n";

	dol_fiche_end();

	if ($action == 'statut')
	{
		/*
		 * Form to close proposal (signed or not)
		 */
		$form_close = '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		$form_close .= '<p class="notice">'.$langs->trans('SupplierProposalRefFournNotice').'</p>';
		$form_close .= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		$form_close .= '<table class="border" width="100%">';
		$form_close .= '<tr><td width="150"  align="left">' . $langs->trans("CloseAs") . '</td><td align="left">';
		$form_close .= '<input type="hidden" name="action" value="setstatut">';
		$form_close .= '<select id="statut" name="statut" class="flat">';
		$form_close .= '<option value="0">&nbsp;</option>';
		$form_close .= '<option value="2">' . $langs->trans('SupplierProposalStatusSigned') . '</option>';
		$form_close .= '<option value="3">' . $langs->trans('SupplierProposalStatusNotSigned') . '</option>';
		$form_close .= '</select>';
		$form_close .= '</td></tr>';
		$form_close .= '<tr><td width="150" align="left">' . $langs->trans('Note') . '</td><td align="left"><textarea cols="70" rows="' . ROWS_3 . '" wrap="soft" name="note">';
		$form_close .= $object->note;
		$form_close .= '</textarea></td></tr>';
		$form_close .= '<tr><td align="center" colspan="2">';
		$form_close .= '<input type="submit" class="button" name="validate" value="' . $langs->trans('Validate') . '">';
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
				if ($object->statut == 0 && $object->total_ttc >= 0 && count($object->lines) > 0 &&
			        ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->supplier_proposal->creer))
       				|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->supplier_proposal->validate)))
				) {
					if (count($object->lines) > 0)
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=validate">' . $langs->trans('Validate') . '</a></div>';
					// else print '<a class="butActionRefused" href="#">'.$langs->trans('Validate').'</a>';
				}

				// Edit
				if ($object->statut == 1 && $user->rights->supplier_proposal->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a></div>';
				}

				// ReOpen
				if (($object->statut == 2 || $object->statut == 3 || $object->statut == 4) && $user->rights->supplier_proposal->cloturer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=reopen' . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#reopen') . '"';
					print '>' . $langs->trans('ReOpen') . '</a></div>';
				}

				// Send
				if ($object->statut == 1 || $object->statut == 2) {
					if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->supplier_proposal->send) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=presend&amp;mode=init">' . $langs->trans('SendByMail') . '</a></div>';
					} else
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMail') . '</a></div>';
				}

				// Create an order
				if (! empty($conf->commande->enabled) && $object->statut == 2) {
					if ($user->rights->fournisseur->commande->creer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/commande/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddOrder") . '</a></div>';
					}
				}

				// Close
				if ($object->statut == 1 && $user->rights->supplier_proposal->cloturer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=statut' . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#close') . '"';
					print '>' . $langs->trans('Close') . '</a></div>';
				}

				// Clone
				if ($user->rights->supplier_proposal->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=' . $object->element . '">' . $langs->trans("ToClone") . '</a></div>';
				}

				// Delete
				if ($user->rights->supplier_proposal->supprimer) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete"';
					print '>' . $langs->trans('Delete') . '</a></div>';
				}
			}
		}

		print '</div>';
	}
	print "<br>\n";

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';

		/*
		 * Documents generes
		 */
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->supplier_proposal->dir_output . "/" . dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
		$genallowed = $user->rights->supplier_proposal->creer;
		$delallowed = $user->rights->supplier_proposal->supprimer;

		$var = true;

		$somethingshown = $formfile->show_documents('supplier_proposal', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang);

		// Linked object block
		$somethingshown = $form->showLinkedObjectBlock($object);

		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object);
		//if ($linktoelem) print '<br>'.$linktoelem;

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'supplier_proposal', $socid);

		print '</div></div></div>';
	}

	/*
	 * Action presend
 	 */
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}
	if ($action == 'presend')
	{
		$object->fetch_projet();

		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->supplier_proposal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
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
			if ($result <= 0)
			{
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($conf->supplier_proposal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}

		print '<div class="clearboth"></div>';
		print '<br>';
		print load_fiche_titre($langs->trans('SendAskByMail'));

		dol_fiche_head('');

		// Create form object
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='spr'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'spr'.$object->id);
		}
		
		$formmail->withfrom = 1;
		$liste = array();
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value)
			$liste [$key] = $value;
		$formmail->withto = GETPOST("sendto") ? GETPOST("sendto") : $liste;
		$formmail->withtocc = $liste;
		$formmail->withtoccc = (! empty($conf->global->MAIN_EMAIL_USECCC) ? $conf->global->MAIN_EMAIL_USECCC : false);

		$formmail->withtopic = $outputlangs->trans('SendAskRef', '__ASKREF__');

		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;

		// Tableau des substitutions
		$formmail->substit['__ASKREF__'] = $object->ref;
		$formmail->substit['__SIGNATURE__'] = $user->signature;
		$formmail->substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit['__CONTACTCIVNAME__'] = '';
		$formmail->substit['__PERSONALIZED__'] = '';

		// Tableau des parametres complementaires
		$formmail->param['action'] = 'send';
		$formmail->param['models'] = 'supplier_proposal_send';
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
