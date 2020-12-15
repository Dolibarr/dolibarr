<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016      Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019      Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Tim Otte			    <otte@meuser.it>
 * Copyright (C) 2020      Pierre Ardoin        <mapiolca@me.com>
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
 *  \file       htdocs/product/fournisseurs.php
 *  \ingroup    product
 *  \brief      Page of tab suppliers for products
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_expression.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
if (!empty($conf->barcode->enabled)) dol_include_once('/core/class/html.formbarcode.class.php');
// Load translation files required by the page
$langs->loadLangs(array('products', 'suppliers', 'bills', 'margins', 'stocks'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$rowid = GETPOST('rowid', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'pricesuppliercard';

$socid = GETPOST('socid', 'int');
$cost_price = GETPOST('cost_price', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$error = 0;

$extrafields = new ExtraFields($db);

// If socid provided by ajax company selector
if (!empty($_REQUEST['search_fourn_id']))
{
	$_GET['id_fourn'] = $_GET['search_fourn_id'];
	$_POST['id_fourn'] = $_POST['search_fourn_id'];
	$_REQUEST['id_fourn'] = $_REQUEST['search_fourn_id'];
}

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

if (empty($user->rights->fournisseur->lire)) accessforbidden();

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = (GETPOST("page", 'int') ?GETPOST("page", 'int') : 0);
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "s.nom";
if (!$sortorder) $sortorder = "ASC";

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('pricesuppliercard', 'globalcard'));

$object = new ProductFournisseur($db);
if ($id > 0 || $ref)
{
    $object->fetch($id, $ref);
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

if (!$sortfield) $sortfield = "s.nom";
if (!$sortorder) $sortorder = "ASC";


/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->lire) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->lire));
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->creer) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->creer));

$parameters = array('socid'=>$socid, 'id_prod'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'setcost_price')
	{
		if ($id)
		{
			$result = $object->fetch($id);
			$object->cost_price = price2num($cost_price);
			$result = $object->update($object->id, $user);
			if ($result > 0)
			{
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		        $action = '';
			}
			else
			{
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'confirm_remove_pf')
	{
		if ($rowid)	// id of product supplier price to remove
		{
			$action = '';
			$result = $object->remove_product_fournisseur_price($rowid);
			if ($result > 0) {
				$db->query("DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields WHERE fk_object = $rowid");
				setEventMessages($langs->trans("PriceRemoved"), null, 'mesgs');
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'save_price')
	{
		$id_fourn = GETPOST("id_fourn");
		if (empty($id_fourn)) $id_fourn = GETPOST("search_id_fourn");
		$ref_fourn = GETPOST("ref_fourn");
		if (empty($ref_fourn)) $ref_fourn = GETPOST("search_ref_fourn");
		$ref_fourn_old = GETPOST("ref_fourn_old");
		if (empty($ref_fourn_old)) $ref_fourn_old = $ref_fourn;
		$quantity = price2num(GETPOST("qty", 'nohtml'), 'MS');
		$remise_percent = price2num(GETPOST('remise_percent', 'alpha'));
		$npr = preg_match('/\*/', $_POST['tva_tx']) ? 1 : 0;
		$tva_tx = str_replace('*', '', GETPOST('tva_tx', 'alpha'));
		$tva_tx = price2num($tva_tx);
		$price_expression = GETPOST('eid', 'int') ? GETPOST('eid', 'int') : ''; // Discard expression if not in expression mode
		$delivery_time_days = GETPOST('delivery_time_days', 'int') ? GETPOST('delivery_time_days', 'int') : '';
		$supplier_reputation = GETPOST('supplier_reputation');
		$supplier_description = GETPOST('supplier_description', 'alpha');
        $barcode = GETPOST('barcode', 'alpha');
        $fk_barcode_type = GETPOST('fk_barcode_type', 'int');
		$packaging = GETPOST('packaging', 'int');

		if ($tva_tx == '')
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("VATRateForSupplierProduct")), null, 'errors');
		}
		if (!is_numeric($tva_tx))
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("VATRateForSupplierProduct")), null, 'errors');
		}
		if (empty($quantity))
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Qty")), null, 'errors');
		}
		if (empty($ref_fourn))
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefSupplier")), null, 'errors');
		}
		if ($id_fourn <= 0)
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Supplier")), null, 'errors');
		}
		if (price2num($_POST["price"]) < 0 || $_POST["price"] == '')
		{
			if ($price_expression === '')	// Return error of missing price only if price_expression not set
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Price")), null, 'errors');
			}
			else
			{
				$_POST["price"] = 0;
			}
		}
        if ($conf->multicurrency->enabled) {
            if (empty($_POST["multicurrency_code"])) {
                $error++;
                $langs->load("errors");
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Currency")), null, 'errors');
            }
            if (price2num($_POST["multicurrency_tx"]) <= 0 || $_POST["multicurrency_tx"] == '') {
                $error++;
                $langs->load("errors");
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("CurrencyRate")), null, 'errors');
            }
            if (price2num($_POST["multicurrency_price"]) < 0 || $_POST["multicurrency_price"] == '') {
                $error++;
                $langs->load("errors");
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PriceCurrency")), null, 'errors');
            }
        }

		if (empty($packaging)) $packaging = 1;

		if ($packaging < $quantity) $packaging = $quantity;

		$object->packaging = $packaging;

		if (!$error)
		{
			$db->begin();

			if (!$error)
			{
				$ret = $object->add_fournisseur($user, $id_fourn, $ref_fourn_old, $quantity); // This insert record with no value for price. Values are update later with update_buyprice
				if ($ret == -3)
				{
					$error++;

					$object->fetch($object->product_id_already_linked);
					$productLink = $object->getNomUrl(1, 'supplier');

					setEventMessages($langs->trans("ReferenceSupplierIsAlreadyAssociatedWithAProduct", $productLink), null, 'errors');
				}
				elseif ($ret < 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			if (!$error)
			{
				$supplier = new Fournisseur($db);
				$result = $supplier->fetch($id_fourn);
				if (GETPOSTISSET('ref_fourn_price_id')) {
					$object->fetch_product_fournisseur_price(GETPOST('ref_fourn_price_id', 'int'));
				}

				$extralabels = $extrafields->fetch_name_optionals_label("product_fournisseur_price");
				$extrafield_values = $extrafields->getOptionalsFromPost("product_fournisseur_price");
				if (!empty($extrafield_values)) {
                    $resql = $db->query("SELECT fk_object FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields WHERE fk_object = ".$object->product_fourn_price_id);
                    // Insert a new extrafields row, if none exists
                    if ($db->num_rows($resql) != 1) {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields (fk_object, ";
                        foreach ($extrafield_values as $key => $value) {
                            $sql .= str_replace('options_', '', $key).', ';
                        }
                        $sql = substr($sql, 0, strlen($sql) - 2).") VALUES (".$object->product_fourn_price_id.", ";
                        foreach ($extrafield_values as $key => $value) {
                            $sql .= '"'.$value.'", ';
                        }
                        $sql = substr($sql, 0, strlen($sql) - 2).')';
                    } // else update the existing one
                    else {
                        $sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields SET ";
                        foreach ($extrafield_values as $key => $value) {
                            $sql .= str_replace('options_', '', $key).' = "'.$value.'", ';
                        }
                        $sql = substr($sql, 0, strlen($sql) - 2).' WHERE fk_object = '.$object->product_fourn_price_id;
                    }

                    // Execute the sql command from above
                    $db->query($sql);
                }

				$newprice = price2num(GETPOST("price", "alpha"));

                if ($conf->multicurrency->enabled)
                {
                	$multicurrency_tx = price2num(GETPOST("multicurrency_tx", 'alpha'));
                	$multicurrency_price = price2num(GETPOST("multicurrency_price", 'alpha'));
                	$multicurrency_code = GETPOST("multicurrency_code", 'alpha');

                    $ret = $object->update_buyprice($quantity, $newprice, $user, $_POST["price_base_type"], $supplier, $_POST["oselDispo"], $ref_fourn, $tva_tx, $_POST["charges"], $remise_percent, 0, $npr, $delivery_time_days, $supplier_reputation, array(), '', $multicurrency_price, $_POST["multicurrency_price_base_type"], $multicurrency_tx, $multicurrency_code, $supplier_description, $barcode, $fk_barcode_type);
                } else {
                    $ret = $object->update_buyprice($quantity, $newprice, $user, $_POST["price_base_type"], $supplier, $_POST["oselDispo"], $ref_fourn, $tva_tx, $_POST["charges"], $remise_percent, 0, $npr, $delivery_time_days, $supplier_reputation, array(), '', 0, 'HT', 1, '', $supplier_description, $barcode, $fk_barcode_type);
                }
				if ($ret < 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
				else
				{
					if (!empty($conf->dynamicprices->enabled) && $price_expression !== '')
					{
						//Check the expression validity by parsing it
                        $priceparser = new PriceParser($db);
                        $object->fk_supplier_price_expression = $price_expression;
                        $price_result = $priceparser->parseProductSupplier($object);
						if ($price_result < 0) { //Expression is not valid
							$error++;
							setEventMessages($priceparser->translatedError(), null, 'errors');
						}
					}
					if (!$error && !empty($conf->dynamicprices->enabled))
					{
						//Set the price expression for this supplier price
						$ret = $object->setSupplierPriceExpression($price_expression);
						if ($ret < 0)
						{
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				}
			}

			if (!$error)
			{
				$db->commit();
				$action = '';
			}
			else
			{
				$db->rollback();
			}
		}
		else
		{
			$action = 'add_price';
		}
	}
}


/*
 * view
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('BuyingPrices');
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('BuyingPrices');
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($id > 0 || $ref)
{
	if ($result)
	{
		if ($action == 'ask_remove_pf') {
			$form = new Form($db);
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&rowid='.$rowid, $langs->trans('DeleteProductBuyPrice'), $langs->trans('ConfirmDeleteProductBuyPrice'), 'confirm_remove_pf', '', 0, 1);
			echo $formconfirm;
		}

		if ($action <> 'edit' && $action <> 're-edit')
		{
			$head = product_prepare_head($object);
			$titre = $langs->trans("CardProduct".$object->type);
			$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

			dol_fiche_head($head, 'suppliers', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		    $object->next_prev_filter = " fk_product_type = ".$object->type;

            $shownav = 1;
            if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

            print '<div class="fichecenter">';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">';

			// Cost price. Can be used for margin module for option "calculate margin on explicit cost price
            print '<tr><td>';
			$textdesc = $langs->trans("CostPriceDescription");
			$textdesc .= "<br>".$langs->trans("CostPriceUsage");
			$text = $form->textwithpicto($langs->trans("CostPrice"), $textdesc, 1, 'help', '');
			print $form->editfieldkey($text, 'cost_price', $object->cost_price, $object, $usercancreate, 'amount:6');
            print '</td><td colspan="2">';
            print $form->editfieldval($text, 'cost_price', $object->cost_price, $object, $usercancreate, 'amount:6');
            print '</td></tr>';

            // PMP
            print '<tr><td class="titlefield">'.$form->textwithpicto($langs->trans("AverageUnitPricePMPShort"), $langs->trans("AverageUnitPricePMPDesc")).'</td>';
            print '<td>';
            if ($object->pmp > 0) print price($object->pmp).' '.$langs->trans("HT");
            print '</td>';
            print '</tr>';

            // Best buying Price
            print '<tr><td class="titlefield">'.$langs->trans("BuyingPriceMin").'</td>';
            print '<td colspan="2">';
            $product_fourn = new ProductFournisseur($db);
            if ($product_fourn->find_min_price_product_fournisseur($object->id) > 0)
            {
            	if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur();
            	else print $langs->trans("NotDefined");
            }
            print '</td></tr>';

            print '</table>';

            print '</div>';
            print '<div style="clear:both"></div>';

			dol_fiche_end();


			// Form to add or update a price
			if (($action == 'add_price' || $action == 'update_price') && $usercancreate)
			{
				$langs->load("suppliers");

				if ($rowid)
				{
					$object->fetch_product_fournisseur_price($rowid, 1); //Ignore the math expression when getting the price
					print load_fiche_titre($langs->trans("ChangeSupplierPrice"));
				}
				else
				{
					print load_fiche_titre($langs->trans("AddSupplierPrice"));
				}

				print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="save_price">';

				dol_fiche_head();

				print '<table class="border centpercent">';

				// Supplier
				print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Supplier").'</td><td>';
				if ($rowid)
				{
					$supplier = new Fournisseur($db);
					$supplier->fetch($socid);
					print $supplier->getNomUrl(1);
					print '<input type="hidden" name="id_fourn" value="'.$socid.'">';
					print '<input type="hidden" name="ref_fourn_price_id" value="'.$rowid.'">';
					print '<input type="hidden" name="rowid" value="'.$rowid.'">';
					print '<input type="hidden" name="socid" value="'.$socid.'">';
				}
				else
				{
					$events = array();
					$events[] = array('method' => 'getVatRates', 'url' => dol_buildpath('/core/ajax/vatrates.php', 1), 'htmlname' => 'tva_tx', 'params' => array());
					print $form->select_company(GETPOST("id_fourn", 'alpha'), 'id_fourn', 'fournisseur=1', 'SelectThirdParty', 0, 0, $events);

					$parameters = array('filtre'=>"fournisseur=1", 'html_name'=>'id_fourn', 'selected'=>GETPOST("id_fourn"), 'showempty'=>1, 'prod_id'=>$object->id);
				    $reshook = $hookmanager->executeHooks('formCreateThirdpartyOptions', $parameters, $object, $action);
					if (empty($reshook))
					{
						if (empty($form->result))
						{
							print ' - <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&type=f&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.'&action='.$action).'">'.$langs->trans("CreateDolibarrThirdPartySupplier").'</a>';
						}
					}
				}
				print '</td></tr>';

				// Ref supplier
				print '<tr><td class="fieldrequired">'.$langs->trans("SupplierRef").'</td><td>';
				if ($rowid)
				{
                    print '<input type="hidden" name="ref_fourn_old" value="'.$object->ref_supplier.'">';
                    print '<input class="flat" name="ref_fourn" size="12" value="'.$object->ref_supplier.'">';
				}
				else
				{
					print '<input class="flat" name="ref_fourn" size="12" value="'.(GETPOST("ref_fourn") ?GETPOST("ref_fourn") : '').'">';
				}
				print '</td>';
				print '</tr>';

				// Availability
				if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
				{
					$langs->load("propal");
					print '<tr><td>'.$langs->trans("Availability").'</td><td>';
					$form->selectAvailabilityDelay($object->fk_availability, "oselDispo", 1);
					print '</td></tr>'."\n";
				}

				// Qty min
				print '<tr>';
				print '<td class="fieldrequired">'.$langs->trans("QtyMin").'</td>';
				print '<td>';
				$quantity = GETPOSTISSET('qty') ? price2num(GETPOST('qty', 'nohtml'), 'MS') : "1";
				if ($rowid)
				{
					print '<input type="hidden" name="qty" value="'.$object->fourn_qty.'">';
					print $object->fourn_qty;
				}
				else
				{
					print '<input class="flat" name="qty" size="5" value="'.$quantity.'">';
				}
                // Units
                if ($conf->global->PRODUCT_USE_UNITS) {
                    $unit = $object->getLabelOfUnit();
                    if ($unit !== '') {
                        print '&nbsp;&nbsp;'.$langs->trans($unit);
                    }
                }
				print '</td></tr>';

				// Vat rate
				$default_vat = '';

				// We don't have supplier, so we try to guess.
				// For this we build a fictive supplier with same properties than user but using vat)
				$mysoc2 = clone $mysoc;
				$mysoc2->name = 'Fictive seller with same country';
				$mysoc2->tva_assuj = 1;
				$default_vat = get_default_tva($mysoc2, $mysoc, $object->id, 0);
				$default_npr = get_default_npr($mysoc2, $mysoc, $object->id, 0);
				if (empty($default_vat)) $default_npr = $default_vat;

				print '<tr><td class="fieldrequired">'.$langs->trans("VATRateForSupplierProduct").'</td>';
				print '<td>';
				//print $form->load_tva('tva_tx',$object->tva_tx,$supplier,$mysoc);    // Do not use list here as it may be any vat rates for any country
				if (!empty($rowid))	// If we have a supplier, it is an update, we must show the vat of current supplier price
				{
				    $tmpproductsupplier = new ProductFournisseur($db);
				    $tmpproductsupplier->fetch_product_fournisseur_price($rowid, 1);
					$default_vat = $tmpproductsupplier->fourn_tva_tx;
					$default_npr = $tmpproductsupplier->fourn_tva_npr;
				}
				else
				{
                    if (empty($default_vat))
                    {
                        $default_vat = $object->tva_tx;
                    }
				}
				$vattosuggest = (GETPOST("tva_tx") ?vatrate(GETPOST("tva_tx")) : ($default_vat != '' ?vatrate($default_vat) : ''));
				$vattosuggest = preg_replace('/\s*\(.*\)$/', '', $vattosuggest);
				print '<input type="text" class="flat" size="5" name="tva_tx" value="'.$vattosuggest.'">';
				print '</td></tr>';

				if (!empty($conf->dynamicprices->enabled)) //Only show price mode and expression selector if module is enabled
				{
					// Price mode selector
					print '<tr><td class="fieldrequired">'.$langs->trans("PriceMode").'</td><td>';
					$price_expression = new PriceExpression($db);
					$price_expression_list = array(0 => $langs->trans("PriceNumeric")); //Put the numeric mode as first option
					foreach ($price_expression->list_price_expression() as $entry) {
						$price_expression_list[$entry->id] = $entry->title;
					}
					$price_expression_preselection = GETPOST('eid') ? GETPOST('eid') : ($object->fk_supplier_price_expression ? $object->fk_supplier_price_expression : '0');
					print $form->selectarray('eid', $price_expression_list, $price_expression_preselection);
					print '&nbsp; <div id="expression_editor" class="button">'.$langs->trans("PriceExpressionEditor").'</div>';
					print '</td></tr>';
					// This code hides the numeric price input if is not selected, loads the editor page if editor button is pressed
					print '<script type="text/javascript">
						jQuery(document).ready(run);
						function run() {
							jQuery("#expression_editor").click(on_click);
							jQuery("#eid").change(on_change);
							on_change();
						}
						function on_click() {
							window.location = "'.DOL_URL_ROOT.'/product/dynamic_price/editor.php?id='.$id.'&tab=fournisseurs&eid=" + $("#eid").val();
						}
						function on_change() {
							if ($("#eid").val() == 0) {
								jQuery("#price_numeric").show();
							} else {
								jQuery("#price_numeric").hide();
							}
						}
					</script>';
				}

                if ($conf->multicurrency->enabled) {
                    // Currency
                    print '<tr><td class="fieldrequired">'.$langs->trans("Currency").'</td>';
                    print '<td>';
                    $currencycodetouse = GETPOST('multicurrency_code') ?GETPOST('multicurrency_code') : (isset($object->fourn_multicurrency_code) ? $object->fourn_multicurrency_code : '');
                    if (empty($currencycodetouse) && $object->fourn_multicurrency_tx == 1) $currencycodetouse = $conf->currency;
                    print $form->selectMultiCurrency($currencycodetouse, "multicurrency_code", 1);
                    print ' &nbsp; '.$langs->trans("CurrencyRate").' ';
                    print '<input class="flat" name="multicurrency_tx" size="4" value="'.vatrate(GETPOST('multicurrency_tx') ?GETPOST('multicurrency_tx') : (isset($object->fourn_multicurrency_tx) ? $object->fourn_multicurrency_tx : '')).'">';
                    print '</td>';
                    print '</tr>';

                    // Currency price qty min
                    print '<tr><td class="fieldrequired">'.$langs->trans("PriceQtyMinCurrency").'</td>';
                    $pricesupplierincurrencytouse = (GETPOST('multicurrency_price') ?GETPOST('multicurrency_price') : (isset($object->fourn_multicurrency_price) ? $object->fourn_multicurrency_price : ''));
                    print '<td><input class="flat" name="multicurrency_price" size="8" value="'.price($pricesupplierincurrencytouse).'">';
                    print '&nbsp;';
                    print $form->selectPriceBaseType((GETPOST('multicurrency_price_base_type') ?GETPOST('multicurrency_price_base_type') : 'HT'), "multicurrency_price_base_type"); // We keep 'HT' here, multicurrency_price_base_type is not yet supported for supplier prices
                    print '</td></tr>';

                    // Price qty min
                    print '<tr><td class="fieldrequired">'.$langs->trans("PriceQtyMin").'</td>';
                    print '<td><input class="flat" name="disabled_price" size="8" value="">';
                    print '<input type="hidden" name="price" value="">';
                    print '<input type="hidden" name="price_base_type" value="">';
                    print '&nbsp;';
                    print $form->selectPriceBaseType('', "disabled_price_base_type");
                    print '</td></tr>';

                    $currencies = array();
                    $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'multicurrency WHERE entity = '.$conf->entity;
                    $resql = $db->query($sql);
                    if ($resql) {
                        $currency = new MultiCurrency($db);
                        while ($obj = $db->fetch_object($resql)) {
                            $currency->fetch($obj->rowid);
                            $currencies[$currency->code] = $currency->rate->rate;
                        }
                    }
                    $currencies = json_encode($currencies);

                    print <<<SCRIPT
    <script type="text/javascript">
        function update_price_from_multicurrency() {
            var multicurrency_price = $('input[name="multicurrency_price"]').val();
            var multicurrency_tx = $('input[name="multicurrency_tx"]').val();
            $('input[name="price"]').val(multicurrency_price / multicurrency_tx);
            $('input[name="disabled_price"]').val(multicurrency_price / multicurrency_tx);
        }
        jQuery(document).ready(function () {
            $('input[name="disabled_price"]').prop('disabled', true);
            $('select[name="disabled_price_base_type"]').prop('disabled', true);
            update_price_from_multicurrency();

            $('input[name="multicurrency_price"]').keyup(function () {
                update_price_from_multicurrency();
            }).change(function () {
                update_price_from_multicurrency();
            }).on('paste', function () {
                update_price_from_multicurrency();
            });

            $('input[name="multicurrency_tx"]').keyup(function () {
                update_price_from_multicurrency();
            }).change(function () {
                update_price_from_multicurrency();
            }).on('paste', function () {
                update_price_from_multicurrency();
            });

            $('select[name="multicurrency_price_base_type"]').change(function () {
                $('input[name="price_base_type"]').val($(this).val());
                $('select[name="disabled_price_base_type"]').val($(this).val());
            });

            var currencies_array = $currencies;
            $('select[name="multicurrency_code"]').change(function () {
                $('input[name="multicurrency_tx"]').val(currencies_array[$(this).val()]);
            });
        });
    </script>
SCRIPT;
                } else {
                    // Price qty min
                    print '<tr><td class="fieldrequired">'.$langs->trans("PriceQtyMin").'</td>';
                    print '<td><input class="flat" name="price" size="8" value="'.(GETPOST('price') ? price(GETPOST('price')) : (isset($object->fourn_price) ? price($object->fourn_price) : '')).'">';
                    print '&nbsp;';
                    print $form->selectPriceBaseType((GETPOST('price_base_type') ? GETPOST('price_base_type') : 'HT'), "price_base_type"); // We keep 'HT' here, price_base_type is not yet supported for supplier prices
                    print '</td></tr>';
                }


				// Discount qty min
				print '<tr><td>'.$langs->trans("DiscountQtyMin").'</td>';
				print '<td><input class="flat" name="remise_percent" size="4" value="'.(GETPOST('remise_percent') ?vatrate(GETPOST('remise_percent')) : (isset($object->fourn_remise_percent) ?vatrate($object->fourn_remise_percent) : '')).'"> %';
				print '</td>';
				print '</tr>';

				// Delivery delay in days
				print '<tr>';
				print '<td>'.$langs->trans('NbDaysToDelivery').'</td>';
				print '<td><input class="flat" name="delivery_time_days" size="4" value="'.($rowid ? $object->delivery_time_days : '').'">&nbsp;'.$langs->trans('days').'</td>';
				print '</tr>';

				// Reputation
				print '<tr><td>'.$langs->trans("ReferenceReputation").'</td><td>';
				echo $form->selectarray('supplier_reputation', $object->reputations, $supplier_reputation ? $supplier_reputation : $object->supplier_reputation);
				print '</td></tr>';

				// Barcode
                if (!empty($conf->barcode->enabled))
                {
				    // Option to define a transport cost on supplier price
                    print '<tr>';
                    print '<td>'.$langs->trans('BarcodeValue').'</td>';
                    print '<td><input class="flat" name="barcode"  value="'.($rowid ? $object->fourn_barcode : '').'"></td>';
                    print '</tr>';
                    $formbarcode = new FormBarCode($db);

                    // Barcode type
                    print '<tr>';
                    print '<td>'.$langs->trans('BarcodeType').'</td>';
                    print '<td>';
                    print $formbarcode->selectBarcodeType(($rowid ? $object->fourn_fk_barcode_type : $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE), 'fk_barcode_type', 1);
                    print '</td>';
                    print '</tr>';
                }

				// Option to define a transport cost on supplier price
				if ($conf->global->PRODUCT_CHARGES)
				{
					if (!empty($conf->margin->enabled))
					{
						print '<tr>';
						print '<td>'.$langs->trans("Charges").'</td>';
						print '<td><input class="flat" name="charges" size="8" value="'.(GETPOST('charges') ?price(GETPOST('charges')) : (isset($object->fourn_charges) ?price($object->fourn_charges) : '')).'">';
		        		print '</td>';
						print '</tr>';
					}
				}

				// Product description of the supplier
				if (!empty($conf->global->PRODUIT_FOURN_TEXTS))
				{
				    //WYSIWYG Editor
				    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

    				print '<tr>';
    				print '<td>'.$langs->trans('ProductSupplierDescription').'</td>';
    				print '<td>';

    				$doleditor = new DolEditor('supplier_description', $object->desc_supplier, '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '90%');
    				$doleditor->Create();

    				print '</td>';
    				print '</tr>';
				}

                // Extrafields
				$extrafields->fetch_name_optionals_label("product_fournisseur_price");
				$extralabels = $extrafields->attributes["product_fournisseur_price"]['label'];
                $extrafield_values = $extrafields->getOptionalsFromPost("product_fournisseur_price");
				if (!empty($extralabels)) {
                    if (empty($rowid)) {
                        foreach ($extralabels as $key => $value) {
                            if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && ($extrafields->attributes["product_fournisseur_price"]['list'][$key] == 1 || $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 3 || ($action == "update_price" && $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 4))) {
                                if (!empty($extrafields->attributes["product_fournisseur_price"]['langfile'][$key])) $langs->load($extrafields->attributes["product_fournisseur_price"]['langfile'][$key]);

                                print '<tr><td'.($extrafields->attributes["product_fournisseur_price"]['required'][$key] ? ' class="fieldrequired"' : '').'>';
                                if (!empty($extrafields->attributes["product_fournisseur_price"]['help'][$key])) print $form->textwithpicto($langs->trans($value), $langs->trans($extrafields->attributes["product_fournisseur_price"]['help'][$key]));
                                else print $langs->trans($value);
                                print '</td><td>'.$extrafields->showInputField($key, GETPOSTISSET('options_'.$key) ? $extrafield_values['options_'.$key] : '', '', '', '', '', 0, 'product_fournisseur_price').'</td></tr>';
                            }
                        }
                    } else {
                        $sql  = "SELECT";
                        $sql .= " fk_object";
                        foreach ($extralabels as $key => $value) {
                            $sql .= ", ".$key;
                        }
                        $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields";
                        $sql .= " WHERE fk_object = ".$rowid;
                        $resql = $db->query($sql);
                        if ($resql) {
                            $obj = $db->fetch_object($resql);
                            foreach ($extralabels as $key => $value) {
                                if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && ($extrafields->attributes["product_fournisseur_price"]['list'][$key] == 1 || $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 3 || ($action == "update_price" && $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 4))) {
                                    if (!empty($extrafields->attributes["product_fournisseur_price"]['langfile'][$key])) $langs->load($extrafields->attributes["product_fournisseur_price"]['langfile'][$key]);

                                    print '<tr><td'.($extrafields->attributes["product_fournisseur_price"]['required'][$key] ? ' class="fieldrequired"' : '').'>';
                                    if (!empty($extrafields->attributes["product_fournisseur_price"]['help'][$key])) print $form->textwithpicto($langs->trans($value), $langs->trans($extrafields->attributes["product_fournisseur_price"]['help'][$key]));
                                    else print $langs->trans($value);
                                    print '</td><td>'.$extrafields->showInputField($key, GETPOSTISSET('options_'.$key) ? $extrafield_values['options_'.$key] : $obj->{$key}, '', '', '', '', 0, 'product_fournisseur_price');

                                    print '</td></tr>';
                                }
                            }
                            $db->free($resql);
                        }
                    }
				}

				if (is_object($hookmanager))
				{
					$parameters = array('id_fourn'=>$id_fourn, 'prod_id'=>$object->id);
				    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
                    print $hookmanager->resPrint;
				}

				print '</table>';

				dol_fiche_end();

				print '<div class="center">';
				print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';

				print '</form>';
			}

			// Actions buttons

			print "\n<div class=\"tabsAction\">\n";

			if ($action != 'add_price' && $action != 'update_price')
			{
				$parameters = array();
				$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if (empty($reshook))
				{
					if ($usercancreate)
					{
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$object->id.'&amp;action=add_price">';
						print $langs->trans("AddSupplierPrice").'</a>';
					}
				}
			}

			print "\n</div>\n";
			print '<br>';

			if ($user->rights->fournisseur->lire) // Duplicate ? this check is already in the head of this file
			{
				$param = '';
				if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
				if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
				$param .= '&ref='.urlencode($object->ref);

				$product_fourn = new ProductFournisseur($db);
				$product_fourn_list = $product_fourn->list_product_fournisseur_price($object->id, $sortfield, $sortorder, $limit, $offset);
				$product_fourn_list_all = $product_fourn->list_product_fournisseur_price($object->id, $sortfield, $sortorder, 0, 0);
				$nbtotalofrecords = count($product_fourn_list_all);
				$num = count($product_fourn_list);
				if (($num + ($offset * $limit)) < $nbtotalofrecords) $num++;

				print_barre_liste($langs->trans('SupplierPrices'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit, 1);

				// Suppliers list title
				print '<div class="div-table-responsive">';
				print '<table class="noborder centpercent">';

				$param = "&id=".$object->id;

				print '<tr class="liste_titre">';
				print_liste_field_titre("AppliedPricesFrom", $_SERVER["PHP_SELF"], "pfp.datec", "", $param, "", $sortfield, $sortorder);
				print_liste_field_titre("Suppliers", $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
				print_liste_field_titre("SupplierRef", $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
				if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY)) print_liste_field_titre("Availability", $_SERVER["PHP_SELF"], "pfp.fk_availability", "", $param, "", $sortfield, $sortorder);
				print_liste_field_titre("QtyMin", $_SERVER["PHP_SELF"], "pfp.quantity", "", $param, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("PriceQtyMinHT", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
                if ($conf->multicurrency->enabled) {
                    print_liste_field_titre("PriceQtyMinHTCurrency", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
                }
                print_liste_field_titre("UnitPriceHT", $_SERVER["PHP_SELF"], "pfp.unitprice", "", $param, '', $sortfield, $sortorder, 'right ');
                if ($conf->multicurrency->enabled) {
                    print_liste_field_titre("UnitPriceHTCurrency", $_SERVER["PHP_SELF"], "pfp.multicurrency_unitprice", "", $param, '', $sortfield, $sortorder, 'right ');
                    print_liste_field_titre("Currency", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
                }
				print_liste_field_titre("DiscountQtyMin", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("NbDaysToDelivery", $_SERVER["PHP_SELF"], "pfp.delivery_time_days", "", $param, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("ReputationForThisProduct", $_SERVER["PHP_SELF"], "pfp.supplier_reputation", "", $param, '', $sortfield, $sortorder, 'center ');
				if ($conf->barcode->enabled) {
                    print_liste_field_titre("BarcodeValue", $_SERVER["PHP_SELF"], "pfp.barcode", "", $param, '', $sortfield, $sortorder, 'center ');
                    print_liste_field_titre("BarcodeType", $_SERVER["PHP_SELF"], "pfp.fk_barcode_type", "", $param, '', $sortfield, $sortorder, 'center ');
                }
				if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) print_liste_field_titre("PackagingForThisProduct", $_SERVER["PHP_SELF"], "pfp.packaging", "", $param, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("DateModification", $_SERVER["PHP_SELF"], "pfp.tms", "", $param, '', $sortfield, $sortorder, 'right ');

				// fetch optionals attributes and labels
				$extrafields->fetch_name_optionals_label("product_fournisseur_price");
				$extralabels = $extrafields->attributes["product_fournisseur_price"]['label'];
				if (!empty($extralabels)) {
					foreach ($extralabels as $key => $value) {
						// Show field if not hidden
						if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
                            if (!empty($extrafields->attributes["product_fournisseur_price"]['langfile'][$key])) $langs->load($extrafields->attributes["product_fournisseur_price"]['langfile'][$key]);
                            if (!empty($extrafields->attributes["product_fournisseur_price"]['help'][$key])) $extratitle = $form->textwithpicto($langs->trans($value), $langs->trans($extrafields->attributes["product_fournisseur_price"]['help'][$key]));
                            else $extratitle = $langs->trans($value);
							print_liste_field_titre($extratitle, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
						}
					}
				}

				if (is_object($hookmanager))
				{
				    $parameters = array('id_fourn'=>$id_fourn, 'prod_id'=>$object->id);
				    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
				}
				print_liste_field_titre('');
				print "</tr>\n";

				if (is_array($product_fourn_list))
				{
					foreach ($product_fourn_list as $productfourn)
					{
						print '<tr class="oddeven">';

						// Date from
						print '<td>'.dol_print_date(($productfourn->fourn_date_creation ? $productfourn->fourn_date_creation : $productfourn->date_creation), 'dayhour').'</td>';

						// Supplier
						print '<td>'.$productfourn->getSocNomUrl(1, 'supplier').'</td>';

						// Supplier ref
						if ($usercancreate) // change required right here
						{
							print '<td class="left">'.$productfourn->getNomUrl().'</td>';
						}
						else
						{
							print '<td class="left">'.$productfourn->fourn_ref.'</td>';
						}

						// Availability
						if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
						{
							$form->load_cache_availability();
                			$availability = $form->cache_availability[$productfourn->fk_availability]['label'];
							print '<td class="left">'.$availability.'</td>';
						}

						// Quantity
						print '<td class="right">';
						print $productfourn->fourn_qty;
                        // Units
                        if ($conf->global->PRODUCT_USE_UNITS) {
                            $unit = $object->getLabelOfUnit();
                            if ($unit !== '') {
                                print '&nbsp;&nbsp;'.$langs->trans($unit);
                            }
                        }
						print '</td>';

						// VAT rate
						print '<td class="right">';
						print vatrate($productfourn->fourn_tva_tx, true);
						print '</td>';

						// Price for the quantity
						print '<td class="right">';
						print $productfourn->fourn_price ?price($productfourn->fourn_price) : "";
						print '</td>';

                        if ($conf->multicurrency->enabled) {
                            // Price for the quantity in currency
                            print '<td class="right">';
                            print $productfourn->fourn_multicurrency_price ? price($productfourn->fourn_multicurrency_price) : "";
                            print '</td>';
                        }

						// Unit price
						print '<td class="right">';
						print price($productfourn->fourn_unitprice);
						//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
						print '</td>';

                        if ($conf->multicurrency->enabled) {
                            // Unit price in currency
                            print '<td class="right">';
                            print price($productfourn->fourn_multicurrency_unitprice);
                            print '</td>';

                            // Currency
                            print '<td class="right">';
                            print $productfourn->fourn_multicurrency_code ? currency_name($productfourn->fourn_multicurrency_code) : '';
                            print '</td>';
                        }

						// Discount
						print '<td class="right">';
						print price2num($productfourn->fourn_remise_percent).'%';
						print '</td>';

						// Delivery delay
						print '<td class="right">';
						print $productfourn->delivery_time_days;
						print '</td>';

						// Reputation
						print '<td class="center">';
						if (!empty($productfourn->supplier_reputation) && !empty($object->reputations[$productfourn->supplier_reputation])) {
							print $object->reputations[$productfourn->supplier_reputation];
						}
						print'</td>';

						if ($conf->barcode->enabled) {
                            // Barcode
                            print '<td align="right">';
                            print $productfourn->barcode;
                            print '</td>';

                            // Barcode type
                            print '<td class="center">';
                            $productfourn->barcode_type = !empty($productfourn->fk_barcode_type) ? $productfourn->fk_barcode_type : 0;
                            $productfourn->fetch_barcode();
                            print $productfourn->barcode_type_label ? $productfourn->barcode_type_label : ($productfourn->barcode ? '<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>' : '');
                            print '</td>';
						}

						// Packaging
						if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING))
						{
							print '<td align="center">';
							print price2num($productfourn->packaging);
							print '</td>';
						}

						// Date
						print '<td align="right">';
						print dol_print_date(($productfourn->fourn_date_modification ? $productfourn->fourn_date_modification : $productfourn->date_modification), "dayhour");
						print '</td>';

						// Extrafields
						if (!empty($extralabels)) {
                            $sql  = "SELECT";
                            $sql .= " fk_object";
                            foreach ($extralabels as $key => $value) {
                                $sql .= ", ".$key;
                            }
                            $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields";
                            $sql .= " WHERE fk_object = ".$productfourn->product_fourn_price_id;
                            $resql = $db->query($sql);
                            if ($resql) {
                                if ($db->num_rows($resql) != 1) {
                                    foreach ($extralabels as $key => $value) {
                                        if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
                                            print "<td></td>";
                                        }
                                    }
                                } else {
                                    $obj = $db->fetch_object($resql);
                                    foreach ($extralabels as $key => $value) {
                                        if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
                                            print '<td align="right">'.$extrafields->showOutputField($key, $obj->{$key})."</td>";
                                        }
                                    }
                                }
                                $db->free($resql);
                            }
						}

						if (is_object($hookmanager))
						{
							$parameters = array('id_pfp'=>$productfourn->product_fourn_price_id, 'id_fourn'=>$id_fourn, 'prod_id'=>$object->id);
						    $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action);
						}

						// Modify-Remove
						print '<td class="center nowraponall">';

						if ($usercancreate)
						{
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$productfourn->fourn_id.'&amp;action=update_price&amp;rowid='.$productfourn->product_fourn_price_id.'">'.img_edit()."</a>";
							print ' &nbsp; ';
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$productfourn->fourn_id.'&amp;action=ask_remove_pf&amp;rowid='.$productfourn->product_fourn_price_id.'">'.img_picto($langs->trans("Remove"), 'delete').'</a>';
						}

						print '</td>';

						print '</tr>';
					}
				}
				else
				{
				    dol_print_error($db);
				}

				print '</table>';
				print '</div>';
			}
		}
	}
}
else
{
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
