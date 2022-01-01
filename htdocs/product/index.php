<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2014-2016  Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019       Pierre Ardoin           <mapiolca@me.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
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
 *  \file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

$type = GETPOST("type", 'int');
if ($type == '' && !$user->rights->produit->lire) $type = '1'; // Force global page on service page only
if ($type == '' && !$user->rights->service->lire) $type = '0'; // Force global page on product page only

// Security check
if ($type == '0') $result = restrictedArea($user, 'produit');
elseif ($type == '1') $result = restrictedArea($user, 'service');
else $result = restrictedArea($user, 'produit|service|expedition');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks'));

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('productindex'));

$product_static = new Product($db);


/*
 * View
 */

$transAreaType = $langs->trans("ProductsAndServicesArea");

$helpurl = '';
if (!isset($_GET["type"]))
{
	$transAreaType = $langs->trans("ProductsAndServicesArea");
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 0) || empty($conf->service->enabled))
{
	$transAreaType = $langs->trans("ProductsArea");
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 1) || empty($conf->product->enabled))
{
	$transAreaType = $langs->trans("ServicesArea");
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader("", $langs->trans("ProductsAndServices"), $helpurl);

$linkback = "";
print load_fiche_titre($transAreaType, $linkback, 'product');


print '<div class="fichecenter"><div class="fichethirdleft">';


if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search contract
    if ((!empty($conf->product->enabled) || !empty($conf->service->enabled)) && ($user->rights->produit->lire || $user->rights->service->lire))
    {
    	$listofsearchfields['search_product'] = array('text'=>'ProductOrService');
    }

    if (count($listofsearchfields))
    {
    	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
    	print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<div class="div-table-responsive-no-min">';
    	print '<table class="noborder nohover centpercent">';
    	$i = 0;
    	foreach ($listofsearchfields as $key => $value)
    	{
    		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    		print '<tr class="oddeven">';
    		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
    		if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    		print '</tr>';
    		$i++;
    	}
    	print '</table>';
        print '</div>';
    	print '</form>';
    	print '<br>';
    }
}

/*
 * Number of products and/or services
 */
if ((!empty($conf->product->enabled) || !empty($conf->service->enabled)) && ($user->rights->produit->lire || $user->rights->service->lire))
{
	$prodser = array();
	$prodser[0][0] = $prodser[0][1] = $prodser[0][2] = $prodser[0][3] = 0;
	$prodser[1][0] = $prodser[1][1] = $prodser[1][2] = $prodser[1][3] = 0;

	$sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.tosell, p.tobuy";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
	$sql .= ' WHERE p.entity IN ('.getEntity($product_static->element, 1).')';
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= " GROUP BY p.fk_product_type, p.tosell, p.tobuy";
	$result = $db->query($sql);
	while ($objp = $db->fetch_object($result))
	{
		$status = 3; // On sale + On purchase
		if (!$objp->tosell && !$objp->tobuy) $status = 0; // Not on sale, not on purchase
		if ($objp->tosell && !$objp->tobuy) $status = 1; // On sale only
		if (!$objp->tosell && $objp->tobuy) $status = 2; // On purchase only
		$prodser[$objp->fk_product_type][$status] = $objp->total;
		if ($objp->tosell) $prodser[$objp->fk_product_type]['sell'] += $objp->total;
		if ($objp->tobuy)  $prodser[$objp->fk_product_type]['buy'] += $objp->total;
		if (!$objp->tosell && !$objp->tobuy)  $prodser[$objp->fk_product_type]['none'] += $objp->total;
	}

	if ($conf->use_javascript_ajax)
	{
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th>'.$langs->trans("Statistics").'</th></tr>';
		print '<tr><td class="center nopaddingleftimp nopaddingrightimp">';

		$SommeA = $prodser[0]['sell'];
		$SommeB = $prodser[0]['buy'];
		$SommeC = $prodser[0]['none'];
		$SommeD = $prodser[1]['sell'];
		$SommeE = $prodser[1]['buy'];
		$SommeF = $prodser[1]['none'];
		$total = 0;
		$dataval = array();
		$datalabels = array();
		$i = 0;

		$total = $SommeA + $SommeB + $SommeC + $SommeD + $SommeE + $SommeF;
		$dataseries = array();
		if (!empty($conf->product->enabled))
		{
			$dataseries[] = array($langs->transnoentitiesnoconv("ProductsOnSale"), round($SommeA));
			$dataseries[] = array($langs->transnoentitiesnoconv("ProductsOnPurchase"), round($SommeB));
			$dataseries[] = array($langs->transnoentitiesnoconv("ProductsNotOnSell"), round($SommeC));
		}
		if (!empty($conf->service->enabled))
		{
			$dataseries[] = array($langs->transnoentitiesnoconv("ServicesOnSale"), round($SommeD));
			$dataseries[] = array($langs->transnoentitiesnoconv("ServicesOnPurchase"), round($SommeE));
			$dataseries[] = array($langs->transnoentitiesnoconv("ServicesNotOnSell"), round($SommeF));
		}
		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent(0);
		$dolgraph->SetType(array('pie'));
		$dolgraph->setHeight('200');
		$dolgraph->draw('idgraphstatus');
		print $dolgraph->show($total ? 0 : 1);

		print '</td></tr>';
		print '</table>';
		print '</div>';
	}
}


if (!empty($conf->categorie->enabled) && !empty($conf->global->CATEGORY_GRAPHSTATS_ON_PRODUCTS))
{
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Categories").'</th></tr>';
	print '<tr class="oddeven"><td class="center" colspan="2">';
	$sql = "SELECT c.label, count(*) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."categorie_product as cs";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
	$sql .= " WHERE c.type = 0";
	$sql .= " AND c.entity IN (".getEntity('category').")";
	$sql .= " GROUP BY c.label";
	$total = 0;
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;
		if (!empty($conf->use_javascript_ajax))
		{
			$dataseries = array();
			$rest = 0;
			$nbmax = 10;
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				if ($i < $nbmax)
				{
					$dataseries[] = array($obj->label, round($obj->nb));
				}
				else
				{
					$rest += $obj->nb;
				}
				$total += $obj->nb;
				$i++;
			}
			if ($i > $nbmax)
			{
				$dataseries[] = array($langs->trans("Other"), round($rest));
			}

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->setHeight('200');
			$dolgraph->draw('idstatscategproduct');
			print $dolgraph->show($total ? 0 : 1);
		}
		else
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);

				print '<tr><td>'.$obj->label.'</td><td>'.$obj->nb.'</td></tr>';
				$total += $obj->nb;
				$i++;
			}
		}
	}
	print '</td></tr>';
	print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
	print $total;
	print '</td></tr>';
	print '</table>';
	print '</div>';
}
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Latest modified products
 */
if ((!empty($conf->product->enabled) || !empty($conf->service->enabled)) && ($user->rights->produit->lire || $user->rights->service->lire))
{
	$max = 15;
	$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type, p.tosell, p.tobuy, p.tobatch, p.fk_price_expression,";
	$sql .= " p.entity,";
	$sql .= " p.tms as datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
	$sql .= " WHERE p.entity IN (".getEntity($product_static->element, 1).")";
	if ($type != '') $sql .= " AND p.fk_product_type = ".$type;
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= $db->order("p.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	//print $sql;
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);

		$i = 0;

		if ($num > 0)
		{
			$transRecordedType = $langs->trans("LastModifiedProductsAndServices", $max);
			if (isset($_GET["type"]) && $_GET["type"] == 0) $transRecordedType = $langs->trans("LastRecordedProducts", $max);
			if (isset($_GET["type"]) && $_GET["type"] == 1) $transRecordedType = $langs->trans("LastRecordedServices", $max);

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			$colnb = 2;
			if (empty($conf->global->PRODUIT_MULTIPRICES)) $colnb++;

			print '<tr class="liste_titre"><th colspan="'.$colnb.'">'.$transRecordedType.'</th>';
			print '<th class="right" colspan="3"><a href="'.DOL_URL_ROOT.'/product/list.php?sortfield=p.tms&sortorder=DESC">'.$langs->trans("FullList").'</td>';
			print '</tr>';

			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				$product_static->id = $objp->rowid;
				$product_static->ref = $objp->ref;
				$product_static->label = $objp->label;
				$product_static->type = $objp->fk_product_type;
				$product_static->entity = $objp->entity;
				$product_static->status = $objp->tosell;
				$product_static->status_buy = $objp->tobuy;
				$product_static->status_batch = $objp->tobatch;

				//Multilangs
				if (!empty($conf->global->MAIN_MULTILANGS))
				{
					$sql = "SELECT label";
					$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
					$sql .= " WHERE fk_product=".$objp->rowid;
					$sql .= " AND lang='".$langs->getDefaultLang()."'";

					$resultd = $db->query($sql);
					if ($resultd)
					{
						$objtp = $db->fetch_object($resultd);
						if ($objtp && $objtp->label != '') $objp->label = $objtp->label;
					}
				}


				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $product_static->getNomUrl(1, '', 16);
				print "</td>\n";
				print '<td>'.dol_trunc($objp->label, 32).'</td>';
				print "<td>";
				print dol_print_date($db->jdate($objp->datem), 'day');
				print "</td>";
				// Sell price
				if (empty($conf->global->PRODUIT_MULTIPRICES))
				{
	                if (!empty($conf->dynamicprices->enabled) && !empty($objp->fk_price_expression))
	                {
	                	$product = new Product($db);
	                	$product->fetch($objp->rowid);
	                    $priceparser = new PriceParser($db);
	                    $price_result = $priceparser->parseProduct($product);
	                    if ($price_result >= 0) {
	                        $objp->price = $price_result;
	                    }
	                }
					print '<td class="nowrap right">';
	    			if (isset($objp->price_base_type) && $objp->price_base_type == 'TTC') print price($objp->price_ttc).' '.$langs->trans("TTC");
	    			else print price($objp->price).' '.$langs->trans("HT");
	    			print '</td>';
				}
				print '<td class="right nowrap width25"><span class="statusrefsell">';
				print $product_static->LibStatut($objp->tosell, 3, 0);
				print "</span></td>";
	            print '<td class="right nowrap width25"><span class="statusrefbuy">';
	            print $product_static->LibStatut($objp->tobuy, 3, 1);
	            print "</span></td>";
				print "</tr>\n";
				$i++;
			}

			$db->free($result);

			print "</table>";
			print '</div>';
			print '<br>';
		}
	}
	else
	{
		dol_print_error($db);
	}
}


// TODO Move this into a page that should be available into menu "accountancy - report - turnover - per quarter"
// Also method used for counting must provide the 2 possible methods like done by all other reports into menu "accountancy - report - turnover":
// "commitment engagment" method and "cash accounting" method
if (!empty($conf->global->MAIN_SHOW_PRODUCT_ACTIVITY_TRIM))
{
	if (!empty($conf->product->enabled)) activitytrim(0);
	if (!empty($conf->service->enabled)) activitytrim(1);
}


print '</div></div></div>';

$parameters = array('type' => $type, 'user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProductsServices', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();


/**
 *  Print html activity for product type
 *
 *  @param      int $product_type   Type of product
 *  @return     void
 */
function activitytrim($product_type)
{
	global $conf, $langs, $db;

	// We display the last 3 years
	$yearofbegindate = date('Y', dol_time_plus_duree(time(), -3, "y"));

	// breakdown by quarter
	$sql = "SELECT DATE_FORMAT(p.datep,'%Y') as annee, DATE_FORMAT(p.datep,'%m') as mois, SUM(fd.total_ht) as Mnttot";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " , ".MAIN_DB_PREFIX."paiement as p,".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
	$sql .= " AND f.rowid = fd.fk_facture";
	$sql .= " AND pf.fk_facture = f.rowid";
	$sql .= " AND pf.fk_paiement= p.rowid";
	$sql .= " AND fd.product_type=".$product_type;
	$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($yearofbegindate), 1)."'";
	$sql .= " GROUP BY annee, mois ";
	$sql .= " ORDER BY annee, mois ";

	$result = $db->query($sql);
	if ($result)
	{
		$tmpyear = 0;
		$trim1 = 0;
		$trim2 = 0;
		$trim3 = 0;
		$trim4 = 0;
		$lgn = 0;
		$num = $db->num_rows($result);

		if ($num > 0)
		{
            print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder" width="75%">';

			if ($product_type == 0)
				print '<tr class="liste_titre"><td class=left>'.$langs->trans("ProductSellByQuarterHT").'</td>';
			else
				print '<tr class="liste_titre"><td class=left>'.$langs->trans("ServiceSellByQuarterHT").'</td>';
			print '<td class=right>'.$langs->trans("Quarter1").'</td>';
			print '<td class=right>'.$langs->trans("Quarter2").'</td>';
			print '<td class=right>'.$langs->trans("Quarter3").'</td>';
			print '<td class=right>'.$langs->trans("Quarter4").'</td>';
			print '<td class=right>'.$langs->trans("Total").'</td>';
			print '</tr>';
		}
		$i = 0;

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			if ($tmpyear != $objp->annee)
			{
				if ($trim1 + $trim2 + $trim3 + $trim4 > 0)
				{
					print '<tr class="oddeven"><td class=left>'.$tmpyear.'</td>';
					print '<td class="nowrap right">'.price($trim1).'</td>';
					print '<td class="nowrap right">'.price($trim2).'</td>';
					print '<td class="nowrap right">'.price($trim3).'</td>';
					print '<td class="nowrap right">'.price($trim4).'</td>';
					print '<td class="nowrap right">'.price($trim1 + $trim2 + $trim3 + $trim4).'</td>';
					print '</tr>';
					$lgn++;
				}
				// We go to the following year
				$tmpyear = $objp->annee;
				$trim1 = 0;
				$trim2 = 0;
				$trim3 = 0;
				$trim4 = 0;
			}

			if ($objp->mois == "01" || $objp->mois == "02" || $objp->mois == "03")
				$trim1 += $objp->Mnttot;

			if ($objp->mois == "04" || $objp->mois == "05" || $objp->mois == "06")
				$trim2 += $objp->Mnttot;

			if ($objp->mois == "07" || $objp->mois == "08" || $objp->mois == "09")
				$trim3 += $objp->Mnttot;

			if ($objp->mois == "10" || $objp->mois == "11" || $objp->mois == "12")
				$trim4 += $objp->Mnttot;

			$i++;
		}
		if ($trim1 + $trim2 + $trim3 + $trim4 > 0)
		{
			print '<tr class="oddeven"><td class=left>'.$tmpyear.'</td>';
			print '<td class="nowrap right">'.price($trim1).'</td>';
			print '<td class="nowrap right">'.price($trim2).'</td>';
			print '<td class="nowrap right">'.price($trim3).'</td>';
			print '<td class="nowrap right">'.price($trim4).'</td>';
			print '<td class="nowrap right">'.price($trim1 + $trim2 + $trim3 + $trim4).'</td>';
			print '</tr>';
		}
		if ($num > 0)
			print '</table></div>';
	}
}
