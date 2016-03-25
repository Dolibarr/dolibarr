<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
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
 * \file htdocs/societe/price.php
 * \ingroup product
 * \brief Page to show product prices by customer
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
	require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

	$prodcustprice = new Productcustomerprice($db);
}

$langs->load("products");
$langs->load("companies");
$langs->load("bills");

$action = GETPOST('action', 'alpha');

// Security check
$socid = GETPOST('socid', 'int')?GETPOST('socid', 'int'):GETPOST('id', 'int');
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycustomerprice','globalcard'));



/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($action == 'add_customer_price_confirm' && ! $_POST ["cancel"] && ($user->rights->produit->creer || $user->rights->service->creer)) {
    
    	$update_child_soc = GETPOST('updatechildprice');
    
    	// add price by customer
    	$prodcustprice->fk_soc = $socid;
    	$prodcustprice->fk_product = GETPOST('prodid', 'int');
    	$prodcustprice->price = price2num(GETPOST("price"), 'MU');
    	$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
    	$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');
    	$prodcustprice->tva_tx = str_replace('*', '', GETPOST("tva_tx"));
    	$prodcustprice->recuperableonly = (preg_match('/\*/', GETPOST("tva_tx")) ? 1 : 0);
    
    	$result = $prodcustprice->create($user, 0, $update_child_soc);
    
    	if ($result < 0) {
    		setEventMessage($prodcustprice->error, 'errors');
    	} else {
    		setEventMessage($langs->trans('Save'), 'mesgs');
    	}
    
    	$action = '';
    }
    
    if ($action == 'delete_customer_price' && ($user->rights->produit->creer || $user->rights->service->creer)) {
    	// Delete price by customer
    	$prodcustprice->id = GETPOST('lineid');
    	$result = $prodcustprice->delete($user);
    
    	if ($result < 0) {
    		setEventMessage($prodcustprice->error, 'mesgs');
    	} else {
    		setEventMessage($langs->trans('Delete'), 'errors');
    	}
    	$action = '';
    }
    
    if ($action == 'update_customer_price_confirm' && ! $_POST ["cancel"] && ($user->rights->produit->creer || $user->rights->service->creer)) {
    
    	$prodcustprice->fetch(GETPOST('lineid', 'int'));
    
    	$update_child_soc = GETPOST('updatechildprice');
    
    	// update price by customer
    	$prodcustprice->price = price2num(GETPOST("price"), 'MU');
    	$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
    	$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');
    	$prodcustprice->tva_tx = str_replace('*', '', GETPOST("tva_tx"));
    	$prodcustprice->recuperableonly = (preg_match('/\*/', GETPOST("tva_tx")) ? 1 : 0);
    
    	$result = $prodcustprice->update($user, 0, $update_child_soc);
    	if ($result < 0) {
    		setEventMessage($prodcustprice->error, 'errors');
    	} else {
    		setEventMessage($langs->trans('Save'), 'mesgs');
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
llxHeader("", $langs->trans("ThirdParty") . '-' . $langs->trans('PriceByCustomer'));

if (! empty($conf->notification->enabled))
	$langs->load("mails");
$head = societe_prepare_head($object);

dol_fiche_head($head, 'price', $langs->trans("ThirdParty"), 0, 'company');

print '<table class="border" width="100%">';

print '<tr><td width="25%">' . $langs->trans("ThirdPartyName") . '</td><td colspan="3">';
print $form->showrefnav($object, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
print '</td></tr>';

// Alias names (commercial, trademark or alias names)
print '<tr><td>'.$langs->trans('AliasNames').'</td><td colspan="3">';
print $object->name_alias;
print "</td></tr>";

if (! empty($conf->global->SOCIETE_USEPREFIX)) // Old not used prefix field
{
	print '<tr><td>' . $langs->trans('Prefix') . '</td><td colspan="3">' . $object->prefix_comm . '</td></tr>';
}

if ($object->client) {
	print '<tr><td>';
	print $langs->trans('CustomerCode') . '</td><td colspan="3">';
	print $object->code_client;
	if ($object->check_codeclient() != 0)
		print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
	print '</td></tr>';
}

if ($object->fournisseur) {
	print '<tr><td>';
	print $langs->trans('SupplierCode') . '</td><td colspan="3">';
	print $object->code_fournisseur;
	if ($object->check_codefournisseur() != 0)
		print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
	print '</td></tr>';
}

if (! empty($conf->barcode->enabled)) {
	print '<tr><td>' . $langs->trans('Gencod') . '</td><td colspan="3">' . $object->barcode . '</td></tr>';
}

print "<tr><td>" . $langs->trans('Address') . "</td><td colspan=\"3\">";
dol_print_address($object->address, 'gmap', 'thirdparty', $object->id);
print "</td></tr>";

// Zip / Town
print '<tr><td width="25%">' . $langs->trans('Zip') . '</td><td width="25%">' . $object->zip . "</td>";
print '<td width="25%">' . $langs->trans('Town') . '</td><td width="25%">' . $object->town . "</td></tr>";

// Country
if ($object->country) {
	print '<tr><td>' . $langs->trans('Country') . '</td><td colspan="3">';
	$img = picto_from_langcode($object->country_code);
	print($img ? $img . ' ' : '');
	print $object->country;
	print '</td></tr>';
}

// EMail
print '<tr><td>' . $langs->trans('EMail') . '</td><td colspan="3">';
print dol_print_email($object->email, 0, $object->id, 'AC_EMAIL');
print '</td></tr>';

// Web
print '<tr><td>' . $langs->trans('Web') . '</td><td colspan="3">';
print dol_print_url($object->url);
print '</td></tr>';

// Phone / Fax
print '<tr><td>' . $langs->trans('Phone') . '</td><td>' . dol_print_phone($object->tel, $object->country_code, 0, $object->id, 'AC_TEL') . '</td>';
print '<td>' . $langs->trans('Fax') . '</td><td>' . dol_print_phone($object->fax, $object->country_code, 0, $object->id, 'AC_FAX') . '</td></tr>';

print '</table>';

print '</div>';

if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {

	$prodcustprice = new Productcustomerprice($db);

	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	$page = GETPOST("page", 'int');
	if ($page == - 1) {
		$page = 0;
	}
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (! $sortorder)
		$sortorder = "ASC";
	if (! $sortfield)
		$sortfield = "soc.nom";

		// Build filter to diplay only concerned lines
	$filter = array (
		't.fk_soc' => $object->id
	);

	$search_prod = GETPOST('search_prod');
	if (! empty($search_prod)) {
		$filter ['prod.ref'] = $search_prod;
	}

	if ($action == 'add_customer_price') {

		// Create mode

		print_fiche_titre($langs->trans('PriceByCustomer'));

		print '<form action="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="add_customer_price_confirm">';
		print '<input type="hidden" name="socid" value="' . $object->id . '">';
		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td>' . $langs->trans('Product') . '</td>';
		print '<td>';
		print $form->select_produits('', 'prodid', '', 0);
		print '</td>';
		print '</tr>';

		// VAT
		print '<tr><td>' . $langs->trans("VATRate") . '</td><td>';
		print $form->load_tva("tva_tx", $object->tva_tx, $mysoc, '', $object->id, $object->tva_npr);
		print '</td></tr>';

		// Price base
		print '<tr><td width="15%">';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->selectPriceBaseType($object->price_base_type, "price_base_type");
		print '</td>';
		print '</tr>';

		// Price
		print '<tr><td width="20%">';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($object->price_base_type == 'TTC') {
			print '<input name="price" size="10" value="' . price($object->price_ttc) . '">';
		} else {
			print '<input name="price" size="10" value="' . price($object->price) . '">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		if ($object->price_base_type == 'TTC') {
			print '<td><input name="price_min" size="10" value="' . price($object->price_min_ttc) . '">';
		} else {
			print '<td><input name="price_min" size="10" value="' . price($object->price_min) . '">';
		}
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

		print '<br><div align="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
		print '</div>';

		print '<br></form>';
	} elseif ($action == 'edit_customer_price') {

		// Edit mode

		print_fiche_titre($langs->trans('PriceByCustomer'));

		$result = $prodcustprice->fetch(GETPOST('lineid', 'int'));
		if ($result < 0) {
			setEventMessage($prodcustprice->error, 'errors');
		}

		print '<form action="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="update_customer_price_confirm">';
		print '<input type="hidden" name="lineid" value="' . $prodcustprice->id . '">';
		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td>' . $langs->trans('Product') . '</td>';
		$staticprod = new Product($db);
		$staticprod->fetch($prodcustprice->fk_product);
		print "<td>" . $staticprod->getNomUrl(1) . "</td>";
		print '</tr>';

		// VAT
		print '<tr><td>' . $langs->trans("VATRate") . '</td><td>';
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
		print '<tr><td width="20%">';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($prodcustprice->price_base_type == 'TTC') {
			print '<input name="price" size="10" value="' . price($prodcustprice->price_ttc) . '">';
		} else {
			print '<input name="price" size="10" value="' . price($prodcustprice->price) . '">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($prodcustprice->price_base_type == 'TTC') {
			print '<input name="price_min" size="10" value="' . price($prodcustprice->price_min_ttc) . '">';
		} else {
			print '<input name="price_min" size="10" value="' . price($prodcustprice->price_min) . '">';
		}
		print '</td></tr>';

		// Update all child soc
		print '<tr><td width="15%">';
		print $langs->trans('ForceUpdateChildPriceSoc');
		print '</td>';
		print '<td>';
		print '<input type="checkbox" name="updatechildprice" value="1">';
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<br><div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
		print '</div>';

		print '<br></form>';
	} elseif ($action == 'showlog_customer_price') {

		$filter = array (
			't.fk_product' => GETPOST('prodid', 'int'),'t.fk_soc' => $socid
		);

		// Count total nb of records
		$nbtotalofrecords = 0;
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$nbtotalofrecords = $prodcustprice->fetch_all_log($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		}

		$result = $prodcustprice->fetch_all_log($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0) {
			setEventMessage($prodcustprice->error, 'errors');
		}

		$option = '&socid=' . GETPOST('socid', 'int') . '&prodid=' . GETPOST('prodid', 'int');

		print_barre_liste($langs->trans('PriceByCustomerLog'), $page, $_SERVEUR ['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords);

		if (count($prodcustprice->lines) > 0) {

			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
			print '<input type="hidden" name="id" value="' . $object->id . '">';

			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("Product") . '</td>';
			print '<td>' . $langs->trans("AppliedPricesFrom") . '</td>';
			print '<td align="center">' . $langs->trans("PriceBase") . '</td>';
			print '<td align="right">' . $langs->trans("VAT") . '</td>';
			print '<td align="right">' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("TTC") . '</td>';
			print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("TTC") . '</td>';
			print '<td align="right">' . $langs->trans("ChangedBy") . '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';

			$var = True;

			foreach ( $prodcustprice->lines as $line ) {

				print "<tr $bc[$var]>";
				$staticprod = new Product($db);
				$staticprod->fetch($line->fk_product);

				print "<td>" . $staticprod->getNomUrl(1) . "</td>";
				print "<td>" . dol_print_date($line->datec, "dayhour") . "</td>";

				print '<td align="center">' . $langs->trans($line->price_base_type) . "</td>";
				print '<td align="right">' . vatrate($line->tva_tx, true, $line->recuperableonly) . "</td>";
				print '<td align="right">' . price($line->price) . "</td>";
				print '<td align="right">' . price($line->price_ttc) . "</td>";
				print '<td align="right">' . price($line->price_min) . '</td>';
				print '<td align="right">' . price($line->price_min_ttc) . '</td>';

				// User
				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);
				print '<td align="right">';
				print $userstatic->getLoginUrl(1);
				print '</td>';
			}
			print "</table>";
		} else {
			print $langs->trans('None');
		}

		print "\n" . '<div class="tabsAction">' . "\n";
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '">' . $langs->trans("Ok") . '</a></div>';
		print "\n</div><br>\n";
	} else {

		// View mode

		// Count total nb of records
		$nbtotalofrecords = 0;
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$nbtotalofrecords = $prodcustprice->fetch_all('', '', 0, 0, $filter);
		}

		$result = $prodcustprice->fetch_all($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0) {
			setEventMessage($prodcustprice->error, 'errors');
		}

		$option = '&search_prod=' . $search_prod . '&id=' . $object->id;

		print_barre_liste($langs->trans('PriceByCustomer'), $page, $_SERVEUR ['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords);

		if (count($prodcustprice->lines) > 0) {

			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
			print '<input type="hidden" name="id" value="' . $object->id . '">';

			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("Product") . '</td>';
			print '<td>' . $langs->trans("AppliedPricesFrom") . '</td>';
			print '<td align="center">' . $langs->trans("PriceBase") . '</td>';
			print '<td align="right">' . $langs->trans("VAT") . '</td>';
			print '<td align="right">' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("TTC") . '</td>';
			print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("TTC") . '</td>';
			print '<td align="right">' . $langs->trans("ChangedBy") . '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';

			print '<tr class="liste_titre">';
			print '<td><input type="text" class="flat" name="search_prod" value="' . $search_prod . '" size="20"></td>';
			print '<td colspan="8">&nbsp;</td>';
			// Print the search button
			print '<td class="liste_titre" align="right">';
			print '<input class="liste_titre" name="button_search" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
			print '</td>';
			print '</tr>';

			$var = False;

			foreach($prodcustprice->lines as $line)
			{
				print "<tr ".$bc[$var].">";

				$staticprod = new Product($db);
				$staticprod->fetch($line->fk_product);

				print "<td>" . $staticprod->getNomUrl(1) . "</td>";
				print "<td>" . dol_print_date($line->datec, "dayhour") . "</td>";

				print '<td align="center">' . $langs->trans($line->price_base_type) . "</td>";
				print '<td align="right">' . vatrate($line->tva_tx, true, $line->recuperableonly) . "</td>";
				print '<td align="right">' . price($line->price) . "</td>";
				print '<td align="right">' . price($line->price_ttc) . "</td>";
				print '<td align="right">' . price($line->price_min) . '</td>';
				print '<td align="right">' . price($line->price_min_ttc) . '</td>';

				// User
				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);
				print '<td align="right">';
				print $userstatic->getLoginUrl(1);
				print '</td>';

				// Todo Edit or delete button
				// Action
				if ($user->rights->produit->creer || $user->rights->service->creer) {
					print '<td align="right">';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=showlog_customer_price&amp;socid=' . $object->id . '&amp;prodid=' . $line->fk_product . '">';
					print img_info();
					print '</a>';
					print ' ';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=edit_customer_price&amp;socid=' . $object->id . '&amp;lineid=' . $line->id . '">';
					print img_edit('default', 0, 'style="vertical-align: middle;"');
					print '</a>';
					print ' ';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete_customer_price&amp;socid=' . $object->id . '&amp;lineid=' . $line->id . '">';
					print img_delete('default', 'style="vertical-align: middle;"');
					print '</a>';
					print '</td>';
				}

				print "</tr>\n";
			}
			print "</table>";

			print "</form>";
		} else {
			print $langs->trans('None');
		}

		/* ************************************************************************** */
		/*                                                                            */
		/* Barre d'action                                                             */
		/*                                                                            */
		/* ************************************************************************** */

		print "\n" . '<div class="tabsAction">' . "\n";

		if ($user->rights->produit->creer || $user->rights->service->creer) {
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=add_customer_price&amp;socid=' . $object->id . '">' . $langs->trans("AddCustomerPrice") . '</a></div>';
		}
		print "\n</div><br>\n";
	}
}

llxFooter();

$db->close();
