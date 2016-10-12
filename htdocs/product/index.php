<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2016  Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
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
 *	\file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

$type=GETPOST("type",'int');
if ($type =='' && !$user->rights->produit->lire) $type='1';	// Force global page on service page only
if ($type =='' && !$user->rights->service->lire) $type='0';	// Force global page on product page only

// Security check
if ($type=='0') $result=restrictedArea($user,'produit');
else if ($type=='1') $result=restrictedArea($user,'service');
else $result=restrictedArea($user,'produit|service');

$langs->load("products");
$langs->load("stocks");

$product_static = new Product($db);


/*
 * View
 */

$transAreaType = $langs->trans("ProductsAndServicesArea");

$helpurl='';
if (! isset($_GET["type"]))
{
	$transAreaType = $langs->trans("ProductsAndServicesArea");
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 0) || empty($conf->service->enabled))
{
	$transAreaType = $langs->trans("ProductsArea");
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 1) || empty($conf->product->enabled))
{
	$transAreaType = $langs->trans("ServicesArea");
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader("", $langs->trans("ProductsAndServices"), $helpurl);

$linkback="";
print load_fiche_titre($transAreaType,$linkback,'title_products.png');


print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Search Area of product/service
 */
 
// Search contract
if ((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && ($user->rights->produit->lire || $user->rights->service->lire))
{
	$listofsearchfields['search_product']=array('text'=>'ProductOrService');
}

if (count($listofsearchfields))
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover centpercent">';
	$i=0;
	foreach($listofsearchfields as $key => $value)
	{
		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
		print '<tr '.$bc[false].'>';
		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
		if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
		print '</tr>';
		$i++;
	}
	print '</table>';	
	print '</form>';
	print '<br>';
}

/*
 * Number of products and/or services
 */
$prodser = array();
$prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

$sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.tosell, p.tobuy";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= ' WHERE p.entity IN ('.getEntity($product_static->element, 1).')';
$sql.= " GROUP BY p.fk_product_type, p.tosell, p.tobuy";
$result = $db->query($sql);
while ($objp = $db->fetch_object($result))
{
	$status=2;
	if (! $objp->tosell && ! $objp->tobuy) $status=0;	// To sell OR to buy
	if ((! $objp->tosell && $objp->tobuy) || ($objp->tosell && ! $objp->tobuy)) $status=1;
	$prodser[$objp->fk_product_type][$status]=$objp->total;
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if (! empty($conf->product->enabled))
{
	$statProducts = "<tr ".$bc[0].">";
	$statProducts.= '<td><a href="list.php?type=0&amp;tosell=0&amp;tobuy=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td align="right">'.round($prodser[0][0]).'</td>';
	$statProducts.= "</tr>";
	$statProducts.= "<tr ".$bc[1].">";
	$statProducts.= '<td><a href="list.php?type=0">'.$langs->trans("ProductsOnSell").'</a></td><td align="right">'.round($prodser[0][1]).'</td>';
	$statProducts.= "</tr>";
	$statProducts.= "<tr ".$bc[0].">";
	$statProducts.= '<td><a href="list.php?type=0&amp;tosell=1&amp;tobuy=1">'.$langs->trans("ProductsOnSellAndOnBuy").'</a></td><td align="right">'.round($prodser[0][2]).'</td>';
	$statProducts.= "</tr>";

}
if (! empty($conf->service->enabled))
{
	$statServices = "<tr ".$bc[1].">";
	$statServices.= '<td><a href="list.php?type=1&amp;tosell=0&amp;tobuy=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td align="right">'.round($prodser[1][0]).'</td>';
	$statServices.= "</tr>";
	$statServices.= "<tr ".$bc[0].">";
	$statServices.= '<td><a href="list.php?type=1">'.$langs->trans("ServicesOnSell").'</a></td><td align="right">'.round($prodser[1][1]).'</td>';
	$statServices.= "</tr>";
	$statServices.= "<tr ".$bc[1].">";
	$statServices.= '<td><a href="list.php?type=1&amp;tosell=1&amp;tobuy=1">'.$langs->trans("ServicesOnSellAndOnBuy").'</a></td><td align="right">'.round($prodser[1][2]).'</td>';
	$statServices.= "</tr>";

}
$total=0;
if ($type == '0')
{
	print $statProducts;
	$total=round($prodser[0][0])+round($prodser[0][1])+round($prodser[0][2]);
}
else if ($type == '1')
{
	print $statServices;
	$total=round($prodser[1][0])+round($prodser[1][1])+round($prodser[1][2]);
}
else
{
	print $statProducts.$statServices;
	$total=round($prodser[1][0])+round($prodser[1][1])+round($prodser[1][2])+round($prodser[0][0])+round($prodser[0][1])+round($prodser[0][2]);
}
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';

if (! empty($conf->categorie->enabled) && ! empty($conf->global->CATEGORY_GRAPHSTATS_ON_PRODUCTS))
{
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Categories").'</th></tr>';
	print '<tr '.$bc[0].'><td align="center" colspan="2">';
	$sql = "SELECT c.label, count(*) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."categorie_product as cs";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
	$sql.= " WHERE c.type = 0";
	$sql.= " AND c.entity IN (".getEntity('category',1).")";
	$sql.= " GROUP BY c.label";
	$total=0;
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i=0;
		if (! empty($conf->use_javascript_ajax))
		{
			$dataseries=array();
			$rest=0;
			$nbmax=10;
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				if ($i < $nbmax)
					$dataseries[]=array('label'=>$obj->label,'data'=>round($obj->nb));
				else
					$rest+=$obj->nb;
				$total+=$obj->nb;
				$i++;
			}
			if ($i > $nbmax)
				$dataseries[]=array('label'=>$langs->trans("Other"),'data'=>round($rest));
			$data=array('series'=>$dataseries);
			dol_print_graph('statscategproduct',300,180,$data,1,'pie',0);
		}
		else
		{
			$var=true;
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				$var=!$var;
				print '<tr $bc[$var]><td>'.$obj->label.'</td><td>'.$obj->nb.'</td></tr>';
				$total+=$obj->nb;
				$i++;
			}
		}
	}
	print '</td></tr>';
	print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
	print $total;
	print '</td></tr>';
	print '</table>';
}
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last modified products
 */
$max=15;
$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type, p.tosell, p.tobuy, p.fk_price_expression,";
$sql.= " p.entity,";
$sql.= " p.tms as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= " WHERE p.entity IN (".getEntity($product_static->element, 1).")";
if ($type != '') $sql.= " AND p.fk_product_type = ".$type;
$sql.= $db->order("p.tms","DESC");
$sql.= $db->plimit($max,0);

//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0)
	{
		$transRecordedType = $langs->trans("LastModifiedProductsAndServices",$max);
		if (isset($_GET["type"]) && $_GET["type"] == 0) $transRecordedType = $langs->trans("LastRecordedProducts",$max);
		if (isset($_GET["type"]) && $_GET["type"] == 1) $transRecordedType = $langs->trans("LastRecordedServices",$max);

		print '<table class="noborder" width="100%">';

		$colnb=5;
		if (empty($conf->global->PRODUIT_MULTIPRICES)) $colnb++;

		print '<tr class="liste_titre"><td colspan="'.$colnb.'">'.$transRecordedType.'</td></tr>';

		$var=True;

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);

			//Multilangs
			if (! empty($conf->global->MAIN_MULTILANGS))
			{
				$sql = "SELECT label";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
				$sql.= " WHERE fk_product=".$objp->rowid;
				$sql.= " AND lang='". $langs->getDefaultLang() ."'";

				$resultd = $db->query($sql);
				if ($resultd)
				{
					$objtp = $db->fetch_object($resultd);
					if ($objtp && $objtp->label != '') $objp->label = $objtp->label;
				}
			}

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			$product_static->id=$objp->rowid;
			$product_static->ref=$objp->ref;
			$product_static->label = $objp->label;
			$product_static->type=$objp->fk_product_type;
            $product_static->entity = $objp->entity;
			print $product_static->getNomUrl(1,'',16);
			print "</td>\n";
			print '<td>'.dol_trunc($objp->label,32).'</td>';
			print "<td>";
			print dol_print_date($db->jdate($objp->datem),'day');
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
				print '<td align="right">';
    			if (isset($objp->price_base_type) && $objp->price_base_type == 'TTC') print price($objp->price_ttc).' '.$langs->trans("TTC");
    			else print price($objp->price).' '.$langs->trans("HT");
    			print '</td>';
			}
			print '<td align="right" class="nowrap">';
			print $product_static->LibStatut($objp->tosell,5,0);
			print "</td>";
            print '<td align="right" class="nowrap">';
            print $product_static->LibStatut($objp->tobuy,5,1);
            print "</td>";
			print "</tr>\n";
			$i++;
		}

		$db->free($result);

		print "</table>";
		print '<br>';
	}
}
else
{
	dol_print_error($db);
}


// TODO Move this into a page that should be available into menu "accountancy - report - turnover - per quarter"
// Also method used for counting must provide the 2 possible methods like done by all other reports into menu "accountancy - report - turnover":
// "commitment engagment" method and "cash accounting" method
if (! empty($conf->global->MAIN_SHOW_PRODUCT_ACTIVITY_TRIM))
{
	if (! empty($conf->product->enabled)) activitytrim(0);
	if (! empty($conf->service->enabled)) activitytrim(1);
}


print '</div></div></div>';

llxFooter();

$db->close();


/*
 *  Print html activity for product type
 *
 *  @param      int $product_type   Type of product
 *  @return     void
 */
function activitytrim($product_type)
{
	global $conf,$langs,$db;
	global $bc;

	// We display the last 3 years
	$yearofbegindate=date('Y',dol_time_plus_duree(time(), -3, "y"));

	// breakdown by quarter
	$sql = "SELECT DATE_FORMAT(p.datep,'%Y') as annee, DATE_FORMAT(p.datep,'%m') as mois, SUM(fd.total_ht) as Mnttot";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd";
	$sql.= " , ".MAIN_DB_PREFIX."paiement as p,".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= " WHERE f.entity = " . $conf->entity;
	$sql.= " AND f.rowid = fd.fk_facture";
	$sql.= " AND pf.fk_facture = f.rowid";
	$sql.= " AND pf.fk_paiement= p.rowid";
	$sql.= " AND fd.product_type=".$product_type;
	$sql.= " AND p.datep >= '".$db->idate(dol_get_first_day($yearofbegindate),1)."'";
	$sql.= " GROUP BY annee, mois ";
	$sql.= " ORDER BY annee, mois ";

	$result = $db->query($sql);
	if ($result)
	{
		$tmpyear=0;
		$trim1=0;
		$trim2=0;
		$trim3=0;
		$trim4=0;
		$lgn = 0;
		$num = $db->num_rows($result);

		if ($num > 0 )
		{
			print '<table class="noborder" width="75%">';

			if ($product_type==0)
				print '<tr class="liste_titre"><td  align=left>'.$langs->trans("ProductSellByQuarterHT").'</td>';
			else
				print '<tr class="liste_titre"><td  align=left>'.$langs->trans("ServiceSellByQuarterHT").'</td>';
			print '<td align=right>'.$langs->trans("Quarter1").'</td>';
			print '<td align=right>'.$langs->trans("Quarter2").'</td>';
			print '<td align=right>'.$langs->trans("Quarter3").'</td>';
			print '<td align=right>'.$langs->trans("Quarter4").'</td>';
			print '<td align=right>'.$langs->trans("Total").'</td>';
			print '</tr>';
		}
		$i = 0;

		$var=true;
		
		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			if ($tmpyear != $objp->annee)
			{
				if ($trim1+$trim2+$trim3+$trim4 > 0)
				{
				    $var=!$var;
					print '<tr '.$bc[$var].'><td align=left>'.$tmpyear.'</td>';
					print '<td align=right>'.price($trim1).'</td>';
					print '<td align=right>'.price($trim2).'</td>';
					print '<td align=right>'.price($trim3).'</td>';
					print '<td align=right>'.price($trim4).'</td>';
					print '<td align=right>'.price($trim1+$trim2+$trim3+$trim4).'</td>';
					print '</tr>';
					$lgn++;
				}
				// We go to the following year
				$tmpyear = $objp->annee;
				$trim1=0;
				$trim2=0;
				$trim3=0;
				$trim4=0;
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
		if ($trim1+$trim2+$trim3+$trim4 > 0)
		{
		    $var=!$var;
			print '<tr '.$bc[$var].'><td align=left>'.$tmpyear.'</td>';
			print '<td align=right>'.price($trim1).'</td>';
			print '<td align=right>'.price($trim2).'</td>';
			print '<td align=right>'.price($trim3).'</td>';
			print '<td align=right>'.price($trim4).'</td>';
			print '<td align=right>'.price($trim1+$trim2+$trim3+$trim4).'</td>';
			print '</tr>';
		}
		if ($num > 0 )
			print '</table>';
	}
}

