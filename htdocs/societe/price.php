<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2023	    Alexandre Spangaro		<aspangaro@open-dsi.fr>
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
 * \file    htdocs/societe/price.php
 * \ingroup product
 * \brief   Page to show product prices by customer
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

	$prodcustprice = new ProductCustomerPrice($db);
}


// Load translation files required by the page
$langs->loadLangs(array("products", "companies", "bills"));


// Get parameters
$action 		= GETPOST('action', 'aZ09');
$search_prod 	= GETPOST('search_prod', 'alpha');
$cancel 		= GETPOST('cancel', 'alpha');
$search_label 	= GETPOST('search_label', 'alpha');
$search_price 	= GETPOST('search_price');
$search_price_ttc = GETPOST('search_price_ttc');

// Security check
$socid = GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize objects
$object = new Societe($db);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('thirdpartycustomerprice', 'globalcard'));

$error = 0;


/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
		$search_prod = $search_label = $search_price = $search_price_ttc = '';
	}

	if ($action == 'add_customer_price_confirm' && !$cancel && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
		if (!(GETPOSTINT('prodid') > 0)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Product")), null, 'errors');
			$action = 'add_customer_price';
		}

		if (!$error) {
			$update_child_soc = GETPOST('updatechildprice');

			// add price by customer
			$prodcustprice->fk_soc = $socid;
			$prodcustprice->ref_customer = GETPOST('ref_customer', 'alpha');
			$prodcustprice->fk_product = GETPOSTINT('prodid');
			$prodcustprice->price = price2num(GETPOST("price"), 'MU');
			$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
			$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');

			$tva_tx_txt = GETPOST('tva_tx', 'alpha'); // tva_tx can be '8.5'  or  '8.5*'  or  '8.5 (XXX)' or '8.5* (XXX)'

			// We must define tva_tx, npr and local taxes
			$vatratecode = '';
			$tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt); // keep remove all after the numbers and dot
			$npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
			$localtax1 = 0;
			$localtax2 = 0;
			$localtax1_type = '0';
			$localtax2_type = '0';
			// If value contains the unique code of vat line (new recommended method), we use it to find npr and local taxes
			if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg)) {
				// We look into database using code (we can't use get_localtax() because it depends on buyer that is not known). Same in update price.
				$vatratecode = $reg[1];
				// Get record from code
				$sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
				$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
				$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$db->escape($mysoc->country_code)."'";
				$sql .= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
				$sql .= " AND t.code = '".$db->escape($vatratecode)."'";
				$sql .= " AND t.entity IN (".getEntity('c_tva').")";
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					$npr = $obj->recuperableonly;
					$localtax1 = $obj->localtax1;
					$localtax2 = $obj->localtax2;
					$localtax1_type = $obj->localtax1_type;
					$localtax2_type = $obj->localtax2_type;
				}
			}

			$prodcustprice->default_vat_code = $vatratecode;
			$prodcustprice->tva_tx = $tva_tx;
			$prodcustprice->recuperableonly = $npr;
			$prodcustprice->localtax1_tx = $localtax1;
			$prodcustprice->localtax2_tx = $localtax2;
			$prodcustprice->localtax1_type = $localtax1_type;
			$prodcustprice->localtax2_type = $localtax2_type;

			$result = $prodcustprice->create($user, 0, $update_child_soc);

			if ($result < 0) {
				setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
			} else {
				setEventMessages($langs->trans("Save"), null, 'mesgs');
			}

			$action = '';
		}
	}

	if ($action == 'delete_customer_price' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
		// Delete price by customer
		$prodcustprice->id = GETPOSTINT('lineid');
		$result = $prodcustprice->delete($user);

		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'mesgs');
		} else {
			setEventMessages($langs->trans('RecordDeleted'), null, 'errors');
		}
		$action = '';
	}

	if ($action == 'update_customer_price_confirm' && !$cancel && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
		$prodcustprice->fetch(GETPOSTINT('lineid'));

		$update_child_soc = GETPOST('updatechildprice');

		// update price by customer
		$prodcustprice->ref_customer = GETPOST('ref_customer', 'alpha');
		$prodcustprice->price = price2num(GETPOST("price"), 'MU');
		$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
		$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');
		$prodcustprice->tva_tx = str_replace('*', '', GETPOST("tva_tx"));
		$prodcustprice->recuperableonly = (preg_match('/\*/', GETPOST("tva_tx")) ? 1 : 0);

		$result = $prodcustprice->update($user, 0, $update_child_soc);
		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		} else {
			setEventMessages($langs->trans("Save"), null, 'mesgs');
		}

		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);

$object = new Societe($db);

$result = $object->fetch($socid);
llxHeader("", $langs->trans("ThirdParty").'-'.$langs->trans('PriceByCustomer'));

$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'price', $langs->trans("ThirdParty"), -1, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Type Prospect/Customer/Supplier
print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
print $object->getTypeUrl(1);
print '</td></tr>';

if (getDolGlobalString('SOCIETE_USEPREFIX')) { // Old not used prefix field
	print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client) {
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	$tmpcheck = $object->check_codeclient();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
	}
	print '</td></tr>';
}

if ($object->fournisseur) {
	print '<tr><td class="titlefield">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	$tmpcheck = $object->check_codefournisseur();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
	}
	print '</td></tr>';
}

print '</table>';

print '</div>';

print dol_get_fiche_end();



if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
	$prodcustprice = new ProductCustomerPrice($db);

	$sortfield = GETPOST('sortfield', 'aZ09comma');
	$sortorder = GETPOST('sortorder', 'aZ09comma');
	$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
	$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
	if (empty($page) || $page == -1) {
		$page = 0;
	}     // If $page is not defined, or '' or -1
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (!$sortorder) {
		$sortorder = "ASC";
	}
	if (!$sortfield) {
		$sortfield = "soc.nom";
	}

	// Build filter to display only concerned lines
	$filter = array(
		't.fk_soc' => $object->id
	);

	if (!empty($search_prod)) {
		$filter ['prod.ref'] = $search_prod;
	}

	if (!empty($search_label)) {
		$filter ['prod.label'] = $search_label;
	}

	if (!empty($search_price)) {
		$filter ['t.price'] = $search_price;
	}

	if (!empty($search_price_ttc)) {
		$filter ['t.price_ttc'] = $search_price_ttc;
	}

	if ($action == 'add_customer_price') {
		// Create mode

		print '<br>';
		print '<!-- Price by customer -->'."\n";

		print load_fiche_titre($langs->trans('PriceByCustomer'));

		print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add_customer_price_confirm">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="socid" value="'.$object->id.'">';
		print '<table class="border centpercent">';
		print '<tr>';
		print '<td>'.$langs->trans('Product').'</td>';
		print '<td>';
		$form->select_produits('', 'prodid', '', 0);
		print '</td>';
		print '</tr>';

		// Ref. Customer
		print '<tr><td>'.$langs->trans('RefCustomer').'</td>';
		print '<td><input name="ref_customer" size="12"></td></tr>';

		// VAT
		print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
		print $form->load_tva("tva_tx", GETPOST("tva_tx", "alpha"), $mysoc, '', $object->id, 0, '', false, 1);
		print '</td></tr>';

		// Price base
		print '<tr><td width="15%">';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->selectPriceBaseType(GETPOST("price_base_type", "aZ09"), "price_base_type");
		print '</td>';
		print '</tr>';

		// Price
		print '<tr><td width="20%">';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", getDolGlobalString('MAIN_MAX_DECIMALS_UNIT')), 1, 1);
		print '</td><td>';
		print '<input name="price" size="10" value="'.GETPOSTINT('price').'">';
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", getDolGlobalString('MAIN_MAX_DECIMALS_UNIT')), 1, 1);
		print '<td><input name="price_min" size="10" value="'.GETPOSTINT('price_min').'">';
		print '</td></tr>';

		// Update all child soc
		print '<tr><td width="15%">';
		print $langs->trans('ForceUpdateChildPriceSoc');
		print '</td>';
		print '<td>';
		print '<input type="checkbox" name="updatechildprice" value="1"/>';
		print '</td>';
		print '</tr>';

		print '</table>';

		print $form->buttonsSaveCancel();

		print '</form>';
	} elseif ($action == 'edit_customer_price') {
		// Edit mode

		print load_fiche_titre($langs->trans('PriceByCustomer'));

		$result = $prodcustprice->fetch(GETPOSTINT('lineid'));

		if ($result <= 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		} else {
			print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update_customer_price_confirm">';
			print '<input type="hidden" name="lineid" value="'.$prodcustprice->id.'">';
			print '<table class="border centpercent">';
			print '<tr>';
			print '<td>'.$langs->trans('Product').'</td>';
			$staticprod = new Product($db);
			$staticprod->fetch($prodcustprice->fk_product);
			print "<td>".$staticprod->getNomUrl(1)."</td>";
			print '</tr>';

			// Ref. Customer
			print '<tr><td>'.$langs->trans('RefCustomer').'</td>';
			print '<td><input name="ref_customer" size="12" value="'.dol_escape_htmltag($prodcustprice->ref_customer).'"></td></tr>';

			// VAT
			print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
			print $form->load_tva("tva_tx", $prodcustprice->tva_tx, $mysoc, '', $staticprod->id, $prodcustprice->recuperableonly);
			print '</td></tr>';

			// Price base
			print '<tr><td width="15%">';
			print $langs->trans('PriceBase');
			print '</td>';
			print '<td>';
			print $form->selectPriceBaseType($prodcustprice->price_base_type, "price_base_type");
			print '</td>';
			print '</tr>';

			// Price
			print '<tr><td>';
			$text = $langs->trans('SellingPrice');
			print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", getDolGlobalString('MAIN_MAX_DECIMALS_UNIT')), 1, 1);
			print '</td><td>';
			if ($prodcustprice->price_base_type == 'TTC') {
				print '<input name="price" size="10" value="'.price($prodcustprice->price_ttc).'">';
			} else {
				print '<input name="price" size="10" value="'.price($prodcustprice->price).'">';
			}
			print '</td></tr>';

			// Price minimum
			print '<tr><td>';
			$text = $langs->trans('MinPrice');
			print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", getDolGlobalString('MAIN_MAX_DECIMALS_UNIT')), 1, 1);
			print '</td><td>';
			if ($prodcustprice->price_base_type == 'TTC') {
				print '<input name="price_min" size="10" value="'.price($prodcustprice->price_min_ttc).'">';
			} else {
				print '<input name="price_min" size="10" value="'.price($prodcustprice->price_min).'">';
			}
			print '</td></tr>';

			// Update all child soc
			print '<tr><td>';
			print $langs->trans('ForceUpdateChildPriceSoc');
			print '</td>';
			print '<td>';
			print '<input type="checkbox" name="updatechildprice" value="1">';
			print '</td>';
			print '</tr>';

			print '</table>';

			print $form->buttonsSaveCancel();

			print '</form>';
		}
	} elseif ($action == 'showlog_customer_price') {
		print '<br>';
		print '<!-- showlog_customer_price -->'."\n";

		$filter = array(
			't.fk_product' => GETPOSTINT('prodid'),
			't.fk_soc' => $socid
		);

		// Count total nb of records
		$nbtotalofrecords = '';
		$result = $prodcustprice->fetchAllLog($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		} else {
			if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
				$nbtotalofrecords = $result;
			}
		}

		$option = '&socid='.GETPOSTINT('socid').'&prodid='.GETPOSTINT('prodid');

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		print_barre_liste($langs->trans('PriceByCustomerLog'), $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords);

		if (count($prodcustprice->lines) > 0) {
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Product").'</td>';
			print '<td>'.$langs->trans('RefCustomer').'</td>';
			print '<td>'.$langs->trans("AppliedPricesFrom").'</td>';
			print '<td class="center">'.$langs->trans("PriceBase").'</td>';
			print '<td class="right">'.$langs->trans("VAT").'</td>';
			print '<td class="right">'.$langs->trans("HT").'</td>';
			print '<td class="right">'.$langs->trans("TTC").'</td>';
			print '<td class="right">'.$langs->trans("MinPrice").' '.$langs->trans("HT").'</td>';
			print '<td class="right">'.$langs->trans("MinPrice").' '.$langs->trans("TTC").'</td>';
			print '<td class="right">'.$langs->trans("ChangedBy").'</td>';
			print '<td></td>';
			print '</tr>';

			foreach ($prodcustprice->lines as $line) {
				$staticprod = new Product($db);
				$staticprod->fetch($line->fk_product);

				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);

				print '<tr class="oddeven">';

				print "<td>".$staticprod->getNomUrl(1)."</td>";
				print '<td>'.$line->ref_customer.'</td>';
				print "<td>".dol_print_date($line->datec, "dayhour")."</td>";

				print '<td class="center">'.$langs->trans($line->price_base_type)."</td>";
				print '<td class="right">'.vatrate($line->tva_tx, true, $line->recuperableonly)."</td>";
				print '<td class="right">'.price($line->price)."</td>";
				print '<td class="right">'.price($line->price_ttc)."</td>";
				print '<td class="right">'.price($line->price_min).'</td>';
				print '<td class="right">'.price($line->price_min_ttc).'</td>';

				// User
				print '<td class="right">';
				print $userstatic->getNomUrl(-1);
				print '</td>';
				print '<td></td>';
			}
			print "</table>";
		} else {
			print $langs->trans('None');
		}

		print "\n".'<div class="tabsAction">'."\n";
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'">'.$langs->trans("Ok").'</a></div>';
		print "\n</div><br>\n";
	} else {
		// View mode

		/*
		 * Action bar
		 */
		print "\n".'<div class="tabsAction">'."\n";

		if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=add_customer_price&token='.newToken().'&socid='.$object->id.'">'.$langs->trans("AddCustomerPrice").'</a></div>';
		}
		print "\n</div>\n";


		$arrayfields = array();
		foreach ($prodcustprice->fields as $key => $val) {
			// If $val['visible']==0, then we never show the field
			if (!empty($val['visible'])) {
				$visible = (int) dol_eval($val['visible'], 1, 1, '1');
				$arrayfields['t.'.$key] = array(
					'label' => $val['label'],
					'checked' => (($visible < 0) ? 0 : 1),
					'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
					'position' => $val['position'],
					'help' => isset($val['help']) ? $val['help'] : ''
				);
			}
		}
		$arrayfields = dol_sort_array($arrayfields, 'position');

		// Count total nb of records
		$nbtotalofrecords = '';
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			$nbtotalofrecords = $prodcustprice->fetchAll('', '', 0, 0, $filter);
		}

		$result = $prodcustprice->fetchAll($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		}

		$option = '&search_prod='.$search_prod.'&id='.$object->id.'&label='.$search_label.'&price='.$search_price.'&price_ttc='.$search_price_ttc;

		print '<!-- view specific price for each product -->'."\n";

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		print_barre_liste($langs->trans('PriceForEachProduct'), $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords, '');

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		if (!empty($sortfield)) {
			print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
		}
		if (!empty($sortorder)) {
			print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
		}
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent liste">';

		$param = 'socid='.$object->id.'&';
		if ($search_prod) {
			$param .= '&search_prod='.urlencode($search_prod);
		}
		if ($search_label) {
			$param .= '&search_label='.urlencode($search_label);
		}
		if ($search_price) {
			$param .= '&search_price='.urlencode($search_price);
		}
		if ($search_price) {
			$param .= '&search_price='.urlencode($search_price);
		}
		if ($search_price_ttc) {
			$param .= '&search_price_ttc='.urlencode($search_price_ttc);
		}

		print '<tr class="liste_titre">';
		foreach ($prodcustprice->fields as $key => $val) {
			if (!empty($arrayfields['t.'.$key]['checked'])) {
				print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], $key, '', $param, '', $sortfield, $sortorder)."\n";
			}
		}
		print '<td></td>';
		print '</tr>';

		if (count($prodcustprice->lines) > 0 || $search_prod) {
			print '<tr class="liste_titre">';
			print '<td class="liste_titre"><input type="text" class="flat width75" name="search_prod" value="'.$search_prod.'"></td>';
			print '<td class="liste_titre" ><input type="text" class="flat width75" name="search_label" value="'.$search_label.'"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre left"><input type="text" class="flat width75" name="search_price" value="'.$search_price.'"></td>';
			print '<td class="liste_titre left"><input type="text" class="flat width75" name="search_price_ttc" value="'.$search_price_ttc.'"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			// Print the search button
			print '<td class="liste_titre maxwidthsearch">';
			$searchpicto = $form->showFilterAndCheckAddButtons(0);
			print $searchpicto;
			print '</td>';
			print '</tr>';
		}

		if (count($prodcustprice->lines) > 0) {
			foreach ($prodcustprice->lines as $line) {
				$staticprod = new Product($db);
				$staticprod->fetch($line->fk_product);

				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);

				print '<tr class="oddeven">';

				print '<td class="left">'.$staticprod->getNomUrl(1)."</td>";
				print '<td class="left">'.$staticprod->label."</td>";
				print '<td class="left">'.$line->ref_customer.'</td>';
				print '<td class="left">'.dol_print_date($line->datec, "dayhour")."</td>";
				print '<td class="left">'.$langs->trans($line->price_base_type)."</td>";
				print '<td class="left">'.vatrate($line->tva_tx.($line->default_vat_code ? ' ('.$line->default_vat_code.')' : ''), true, $line->recuperableonly)."</td>";
				print '<td class="left">'.price($line->price)."</td>";
				print '<td class="left">'.price($line->price_ttc)."</td>";
				print '<td class="left">'.price($line->price_min).'</td>';
				print '<td class="left">'.price($line->price_min_ttc).'</td>';
				// User
				print '<td class="left">';
				print $userstatic->getNomUrl(-1);
				print '</td>';
				// Action
				if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
					print '<td class="right nowraponall">';
					print '<a class="paddingleftonly paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?action=showlog_customer_price&token='.newToken().'&socid='.$object->id.'&prodid='.$line->fk_product.'">';
					print img_info();
					print '</a>';
					print ' ';
					print '<a class="editfielda paddingleftonly paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?action=edit_customer_price&token='.newToken().'&socid='.$object->id.'&lineid='.$line->id.'">';
					print img_edit('default', 0, 'style="vertical-align: middle;"');
					print '</a>';
					print ' ';
					print '<a class="paddingleftonly paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?action=delete_customer_price&token='.newToken().'&socid='.$object->id.'&lineid='.$line->id.'">';
					print img_delete('default', 'style="vertical-align: middle;"');
					print '</a>';
					print '</td>';
				}

				print "</tr>\n";
			}
		} else {
			$colspan = 10;
			if ($user->hasRight('produit', 'supprimer') || $user->hasRight('service', 'supprimer')) {
				$colspan += 1;
			}
			print '<tr class="oddeven"><td colspan="'.$colspan.'">'.$langs->trans('None').'</td></tr>';
		}

		print "</table>";
		print '</div>';

		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
