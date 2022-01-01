<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/societe/price.php
 * \ingroup product
 * \brief   Page to show product prices by customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

	$prodcustprice = new Productcustomerprice($db);
}

$langs->loadLangs(array("products", "companies", "bills"));

$action = GETPOST('action', 'alpha');
$search_prod = GETPOST('search_prod', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid)
	$socid = $user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycustomerprice', 'globalcard'));

$error = 0;


/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
    {
        $search_prod = '';
    }

    if ($action == 'add_customer_price_confirm' && !$cancel && ($user->rights->produit->creer || $user->rights->service->creer)) {
    	if (! (GETPOST('prodid', 'int') > 0)) {
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Product")), null, 'errors');
    		$action = 'add_customer_price';
    	}

    	if (! $error) {
	    	$update_child_soc = GETPOST('updatechildprice');

	    	// add price by customer
	    	$prodcustprice->fk_soc = $socid;
	    	$prodcustprice->fk_product = GETPOST('prodid', 'int');
	    	$prodcustprice->price = price2num(GETPOST("price"), 'MU');
	    	$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
	    	$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');

	    	$tva_tx_txt = GETPOST('tva_tx', 'alpha'); // tva_tx can be '8.5'  or  '8.5*'  or  '8.5 (XXX)' or '8.5* (XXX)'

	    	// We must define tva_tx, npr and local taxes
	    	$vatratecode = '';
	    	$tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt); // keep remove all after the numbers and dot
	    	$npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
	    	$localtax1 = 0; $localtax2 = 0; $localtax1_type = '0'; $localtax2_type = '0';
	    	// If value contains the unique code of vat line (new recommended method), we use it to find npr and local taxes
	    	if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
	    	{
	    	    // We look into database using code (we can't use get_localtax() because it depends on buyer that is not known). Same in update price.
	    	    $vatratecode = $reg[1];
	    	    // Get record from code
	    	    $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
	    	    $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
	    	    $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
	    	    $sql .= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
	    	    $sql .= " AND t.code ='".$vatratecode."'";
	    	    $resql = $db->query($sql);
	    	    if ($resql)
	    	    {
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
	    		setEventMessages($langs->trans('Save'), null, 'mesgs');
	    	}

	    	$action = '';
    	}
    }

    if ($action == 'delete_customer_price' && ($user->rights->produit->creer || $user->rights->service->creer)) {
    	// Delete price by customer
    	$prodcustprice->id = GETPOST('lineid');
    	$result = $prodcustprice->delete($user);

    	if ($result < 0) {
    		setEventMessages($prodcustprice->error, $prodcustprice->errors, 'mesgs');
    	} else {
    		setEventMessages($langs->trans('Delete'), null, 'errors');
    	}
    	$action = '';
    }

    if ($action == 'update_customer_price_confirm' && !$_POST ["cancel"] && ($user->rights->produit->creer || $user->rights->service->creer)) {
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
    		setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
    	} else {
    		setEventMessages($langs->trans('Save'), null, 'mesgs');
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

if (!empty($conf->notification->enabled))
	$langs->load("mails");
$head = societe_prepare_head($object);

dol_fiche_head($head, 'price', $langs->trans("ThirdParty"), -1, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent">';

if (!empty($conf->global->SOCIETE_USEPREFIX)) // Old not used prefix field
{
	print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client) {
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	$tmpcheck = $object->check_codeclient();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
	}
	print '</td></tr>';
}

if ($object->fournisseur) {
	print '<tr><td class="titlefield">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	$tmpcheck = $object->check_codefournisseur();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
	}
	print '</td></tr>';
}

print '</table>';

print '</div>';

dol_fiche_end();



if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
	$prodcustprice = new Productcustomerprice($db);

	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
    $limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
	$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
	if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (!$sortorder)
		$sortorder = "ASC";
	if (!$sortfield)
		$sortfield = "soc.nom";

		// Build filter to display only concerned lines
	$filter = array(
		't.fk_soc' => $object->id
	);

	if (!empty($search_prod)) {
		$filter ['prod.ref'] = $search_prod;
	}

	if ($action == 'add_customer_price') {
		// Create mode

		print '<br>';
		print '<!-- Price by customer -->'."\n";

		print load_fiche_titre($langs->trans('PriceByCustomer'));

		print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add_customer_price_confirm">';
		print '<input type="hidden" name="socid" value="'.$object->id.'">';
		print '<table class="border centpercent">';
		print '<tr>';
		print '<td>'.$langs->trans('Product').'</td>';
		print '<td>';
		$form->select_produits('', 'prodid', '', 0);
		print '</td>';
		print '</tr>';

		// VAT
		print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
		print $form->load_tva("tva_tx", $object->tva_tx, $mysoc, '', $object->id, $object->tva_npr, '', false, 1);
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
			print '<input name="price" size="10" value="'.price($object->price_ttc).'">';
		} else {
			print '<input name="price" size="10" value="'.price($object->price).'">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		if ($object->price_base_type == 'TTC') {
			print '<td><input name="price_min" size="10" value="'.price($object->price_min_ttc).'">';
		} else {
			print '<td><input name="price_min" size="10" value="'.price($object->price_min).'">';
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

		print '<br><div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print '<br></form>';
	} elseif ($action == 'edit_customer_price') {
		// Edit mode

		print load_fiche_titre($langs->trans('PriceByCustomer'));

		$result = $prodcustprice->fetch(GETPOST('lineid', 'int'));
		if ($result < 0)
		{
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		}

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
		print '<tr><td width="20%">';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
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
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($prodcustprice->price_base_type == 'TTC') {
			print '<input name="price_min" size="10" value="'.price($prodcustprice->price_min_ttc).'">';
		} else {
			print '<input name="price_min" size="10" value="'.price($prodcustprice->price_min).'">';
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
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print '<br></form>';
	} elseif ($action == 'showlog_customer_price') {
	    print '<br>';
		print '<!-- showlog_customer_price -->'."\n";

		$filter = array(
			't.fk_product' => GETPOST('prodid', 'int'), 't.fk_soc' => $socid
		);

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$nbtotalofrecords = $prodcustprice->fetch_all_log($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		}

		$result = $prodcustprice->fetch_all_log($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0)
		{
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		}

		$option = '&socid='.GETPOST('socid', 'int').'&prodid='.GETPOST('prodid', 'int');

		print_barre_liste($langs->trans('PriceByCustomerLog'), $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords);

		if (count($prodcustprice->lines) > 0) {
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Product").'</td>';
			print '<td>'.$langs->trans("AppliedPricesFrom").'</td>';
			print '<td class="center">'.$langs->trans("PriceBase").'</td>';
			print '<td class="right">'.$langs->trans("VAT").'</td>';
			print '<td class="right">'.$langs->trans("HT").'</td>';
			print '<td class="right">'.$langs->trans("TTC").'</td>';
			print '<td class="right">'.$langs->trans("MinPrice").' '.$langs->trans("HT").'</td>';
			print '<td class="right">'.$langs->trans("MinPrice").' '.$langs->trans("TTC").'</td>';
			print '<td class="right">'.$langs->trans("ChangedBy").'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';

			foreach ($prodcustprice->lines as $line) {
				print '<tr class="oddeven">';
				$staticprod = new Product($db);
				$staticprod->fetch($line->fk_product);

				print "<td>".$staticprod->getNomUrl(1)."</td>";
				print "<td>".dol_print_date($line->datec, "dayhour")."</td>";

				print '<td class="center">'.$langs->trans($line->price_base_type)."</td>";
				print '<td class="right">'.vatrate($line->tva_tx, true, $line->recuperableonly)."</td>";
				print '<td class="right">'.price($line->price)."</td>";
				print '<td class="right">'.price($line->price_ttc)."</td>";
				print '<td class="right">'.price($line->price_min).'</td>';
				print '<td class="right">'.price($line->price_min_ttc).'</td>';

				// User
				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);
				print '<td class="right">';
				print $userstatic->getLoginUrl(1);
				print '</td>';
			}
			print "</table>";
		}
		else
		{
			print $langs->trans('None');
		}

		print "\n".'<div class="tabsAction">'."\n";
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'">'.$langs->trans("Ok").'</a></div>';
		print "\n</div><br>\n";
	}
	else
	{
        // View mode

		/* ************************************************************************** */
		/*                                                                            */
		/* Barre d'action                                                             */
		/*                                                                            */
		/* ************************************************************************** */

		print "\n".'<div class="tabsAction">'."\n";

		if ($user->rights->produit->creer || $user->rights->service->creer) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=add_customer_price&amp;socid='.$object->id.'">'.$langs->trans("AddCustomerPrice").'</a></div>';
		}
		print "\n</div>\n";


        // Count total nb of records
        $nbtotalofrecords = '';
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $nbtotalofrecords = $prodcustprice->fetch_all('', '', 0, 0, $filter);
        }

        $result = $prodcustprice->fetch_all($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
        if ($result < 0)
        {
            setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
        }

        $option = '&search_prod='.$search_prod.'&id='.$object->id;

	    print '<!-- view specific price for each product -->'."\n";

	    print_barre_liste($langs->trans('PriceForEachProduct'), $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords, '');

        print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="id" value="'.$object->id.'">';

        print '<table class="noborder centpercent">';

        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Product").'</td>';
        print '<td>'.$langs->trans("AppliedPricesFrom").'</td>';
        print '<td class="center">'.$langs->trans("PriceBase").'</td>';
        print '<td class="right">'.$langs->trans("VAT").'</td>';
        print '<td class="right">'.$langs->trans("HT").'</td>';
        print '<td class="right">'.$langs->trans("TTC").'</td>';
        print '<td class="right">'.$langs->trans("MinPrice").' '.$langs->trans("HT").'</td>';
        print '<td class="right">'.$langs->trans("MinPrice").' '.$langs->trans("TTC").'</td>';
        print '<td class="right">'.$langs->trans("ChangedBy").'</td>';
        print '<td>&nbsp;</td>';
        print '</tr>';

        if (count($prodcustprice->lines) > 0 || $search_prod)
        {
            print '<tr class="liste_titre">';
			print '<td class="liste_titre"><input type="text" class="flat" name="search_prod" value="'.$search_prod.'" size="20"></td>';
            print '<td class="liste_titre" colspan="8">&nbsp;</td>';
            // Print the search button
            print '<td class="liste_titre maxwidthsearch">';
            $searchpicto = $form->showFilterAndCheckAddButtons(0);
            print $searchpicto;
            print '</td>';
            print '</tr>';
        }

        if (count($prodcustprice->lines) > 0)
        {
            foreach ($prodcustprice->lines as $line)
            {
                print '<tr class="oddeven">';

                $staticprod = new Product($db);
                $staticprod->fetch($line->fk_product);

                print "<td>".$staticprod->getNomUrl(1)."</td>";
                print "<td>".dol_print_date($line->datec, "dayhour")."</td>";

                print '<td class="center">'.$langs->trans($line->price_base_type)."</td>";
                print '<td class="right">'.vatrate($line->tva_tx.($line->default_vat_code ? ' ('.$line->default_vat_code.')' : ''), true, $line->recuperableonly)."</td>";
                print '<td class="right">'.price($line->price)."</td>";
                print '<td class="right">'.price($line->price_ttc)."</td>";
                print '<td class="right">'.price($line->price_min).'</td>';
                print '<td class="right">'.price($line->price_min_ttc).'</td>';

                // User
                $userstatic = new User($db);
                $userstatic->fetch($line->fk_user);
                print '<td class="right">';
                print $userstatic->getLoginUrl(1);
                print '</td>';

                // Action
                if ($user->rights->produit->creer || $user->rights->service->creer)
                {
                    print '<td class="right nowraponall">';
                    print '<a class="paddingleftonly paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?action=showlog_customer_price&amp;socid='.$object->id.'&amp;prodid='.$line->fk_product.'">';
                    print img_info();
                    print '</a>';
                    print ' ';
                    print '<a class="editfielda paddingleftonly paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?action=edit_customer_price&amp;socid='.$object->id.'&amp;lineid='.$line->id.'">';
                    print img_edit('default', 0, 'style="vertical-align: middle;"');
                    print '</a>';
                    print ' ';
                    print '<a class="paddingleftonly paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?action=delete_customer_price&amp;socid='.$object->id.'&amp;lineid='.$line->id.'">';
                    print img_delete('default', 'style="vertical-align: middle;"');
                    print '</a>';
                    print '</td>';
                }

                print "</tr>\n";
            }
        }
        else
        {
            $colspan = 9;
            if ($user->rights->produit->supprimer || $user->rights->service->supprimer) $colspan += 1;
            print '<tr class="oddeven"><td colspan="'.$colspan.'">'.$langs->trans('None').'</td></tr>';
        }

        print "</table>";

        print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
