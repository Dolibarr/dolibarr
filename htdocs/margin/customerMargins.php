<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2014		Ferran Marcet		<fmarcet@2byte.es>
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
 *	\file       htdocs/margin/customerMargins.php
 *	\ingroup    margin
 *	\brief      Page des marges par client
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'products', 'margins'));

// Security check
$socid = GETPOST('socid','int');
$TSelectedProducts = GETPOST('products', 'array');
$TSelectedCats = GETPOST('categories', 'array');

if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');
$result = restrictedArea($user,'margins');


$mesg = '';

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="s.nom"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

$startdate=$enddate='';

if (!empty($_POST['startdatemonth']))
  $startdate  = dol_mktime(0, 0, 0, $_POST['startdatemonth'],  $_POST['startdateday'],  $_POST['startdateyear']);
if (!empty($_POST['enddatemonth']))
  $enddate  = dol_mktime(23, 59, 59, $_POST['enddatemonth'],  $_POST['enddateday'],  $_POST['enddateyear']);


/*
 * View
 */

$companystatic = new Societe($db);
$invoicestatic=new Facture($db);

$form = new Form($db);

llxHeader('',$langs->trans("Margins").' - '.$langs->trans("Clients"));

$text=$langs->trans("Margins");
//print load_fiche_titre($text);

// Show tabs
$head=marges_prepare_head($user);
$titre=$langs->trans("Margins");
$picto='margin';


print '<form method="post" name="sel" action="'.$_SERVER['PHP_SELF'].'">';

dol_fiche_head($head, 'customerMargins', $titre, 0, $picto);

print '<table class="border" width="100%">';

$client = false;
if ($socid > 0) {

	$soc = new Societe($db);
	$soc->fetch($socid);

	if ($soc->client)
	{
		print '<tr><td class="titlefield">'.$langs->trans('ThirdPartyName').'</td>';
		print '<td class="maxwidthonsmartphone" colspan="4">';
		print $form->select_company($socid, 'socid', 'client=1 OR client=3', 1, 0, 0);
		//$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$socid,$socid,'socid','client=1 OR client=3',1,0,1);
		print '</td></tr>';

		$client = true;
		if (! $sortorder) $sortorder="DESC";
		if (! $sortfield) $sortfield="f.datef";
	}
}
else {
	print '<tr><td class="titlefield">'.$langs->trans('ThirdPartyName').'</td>';
	print '<td class="maxwidthonsmartphone" colspan="4">';
	print $form->select_company(null, 'socid', 'client=1 OR client=3', 1, 0, 0);
	//$form->form_thirdparty($_SERVER['PHP_SELF'],null,'socid','client=1 OR client=3',1,0,1);
	print '</td></tr>';
}

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield)
{
	if ($client)
	{
		$sortfield="f.datef";
		$sortorder="DESC";
	}
	else
	{
	    $sortfield="s.nom";
	    $sortorder="ASC";
	}
}

// Products
$TRes = $form->select_produits_list('','','',20,0,'',1,2,1,0,'', 1);

$TProducts = array();
foreach($TRes as $prod) {
	$TProducts[$prod['key']] = $prod['label'];
}

print '<tr><td class="titlefield">'.$langs->trans('ChooseProduct/Service').'</td>';
print '<td class="maxwidthonsmartpone" colspan="4">';
print $form->multiselectarray('products', $TProducts, $TSelectedProducts, 0, 0, 'minwidth500');
print '</td></tr>';

// Categories
$TCats = $form->select_all_categories(0, array(), '', 64, 0, 1);

print '<tr>';
print '<td class="titlefield">'.$langs->trans('ChooseCategory').'</td>';
print '<td class="maxwidthonsmartphone" colspan="4">';
print $form->multiselectarray('categories', $TCats, $TSelectedCats, 0, 0, 'minwidth500');
print '</td>';
print '</tr>';

// Start date
print '<td>'.$langs->trans('DateStart').' ('.$langs->trans("DateValidation").')</td>';
print '<td>';
print $form->selectDate($startdate, 'startdate', '', '', 1, "sel", 1, 1);
print '</td>';
print '<td>'.$langs->trans('DateEnd').' ('.$langs->trans("DateValidation").')</td>';
print '<td>';
print $form->selectDate($enddate, 'enddate', '', '', 1, "sel", 1, 1);
print '</td>';
print '<td style="text-align: center;">';
print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Refresh')).'" />';
print '</td></tr>';

print "</table>";

print '<br>';

print '<table class="border" width="100%">';

// Total Margin
print '<tr><td class="titlefield">'.$langs->trans("TotalMargin").'</td><td colspan="4">';
print '<span id="totalMargin"></span>'; // set by jquery (see below)
print '</td></tr>';

// Margin Rate
if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
	print '<tr><td>'.$langs->trans("MarginRate").'</td><td colspan="4">';
	print '<span id="marginRate"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

// Mark Rate
if (! empty($conf->global->DISPLAY_MARK_RATES)) {
	print '<tr><td>'.$langs->trans("MarkRate").'</td><td colspan="4">';
	print '<span id="markRate"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

print "</table>";

dol_fiche_end();

print '</form>';

$sql = "SELECT";
$sql.= " s.rowid as socid, s.nom as name, s.code_client, s.client,";
if ($client) $sql.= " f.rowid as facid, f.facnumber, f.total as total_ht, f.datef, f.paye, f.fk_statut as statut,";
$sql.= " sum(d.total_ht) as selling_price,";
// Note: qty and buy_price_ht is always positive (if not, your database may be corrupted, you can update this)
$sql.= " sum(".$db->ifsql('d.total_ht < 0','d.qty * d.buy_price_ht * -1','d.qty * d.buy_price_ht').") as buying_price,";
$sql.= " sum(".$db->ifsql('d.total_ht < 0','-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty))','d.total_ht - (d.buy_price_ht * d.qty)').") as marge";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."facture as f";
$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
if(! empty($TSelectedCats)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=d.fk_product';
}

if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE f.fk_soc = s.rowid";
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " AND f.fk_statut > 0";
$sql.= ' AND s.entity IN ('.getEntity('societe').')';
$sql.= " AND d.fk_facture = f.rowid";
$sql.= " AND (d.product_type = 0 OR d.product_type = 1)";
if(! empty($TSelectedProducts)) {
	$sql .= ' AND d.fk_product IN ('.implode(',', $TSelectedProducts) . ')';
}
if(! empty($TSelectedCats)) {
	$sql .= ' AND cp.fk_categorie IN ('.implode(',', $TSelectedCats) . ')';
}
if (!empty($startdate))
$sql.= " AND f.datef >= '".$db->idate($startdate)."'";
if (!empty($enddate))
$sql.= " AND f.datef <= '".$db->idate($enddate)."'";
$sql .= " AND d.buy_price_ht IS NOT NULL";
if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)
$sql .= " AND d.buy_price_ht <> 0";
if ($client) $sql.= " GROUP BY s.rowid, s.nom, s.code_client, s.client, f.rowid, f.facnumber, f.total, f.datef, f.paye, f.fk_statut";
else $sql.= " GROUP BY s.rowid, s.nom, s.code_client, s.client";
$sql.=$db->order($sortfield,$sortorder);
// TODO: calculate total to display then restore pagination
//$sql.= $db->plimit($conf->liste_limit +1, $offset);

dol_syslog('margin::customerMargins.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

  	print '<br>';
	print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num, $num, '', 0, '', '', 0, 1);

	if ($conf->global->MARGIN_TYPE == "1")
	    $labelcostprice='BuyingPrice';
	else   // value is 'costprice' or 'pmp'
	    $labelcostprice='CostPrice';

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	if (! empty($client)) {
  		print_liste_field_titre("Invoice",$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socid=".$socid,'',$sortfield,$sortorder);
  		print_liste_field_titre("DateInvoice",$_SERVER["PHP_SELF"],"f.datef","","&amp;socid=".$socid,'align="center"',$sortfield,$sortorder);
	}
	else
  		print_liste_field_titre("Customer",$_SERVER["PHP_SELF"],"s.nom","","&amp;socid=".$socid,'',$sortfield,$sortorder);
	print_liste_field_titre("SellingPrice",$_SERVER["PHP_SELF"],"selling_price","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($labelcostprice,$_SERVER["PHP_SELF"],"buying_price","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre("Margin",$_SERVER["PHP_SELF"],"marge","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		print_liste_field_titre("MarginRate",$_SERVER["PHP_SELF"],"","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	if (! empty($conf->global->DISPLAY_MARK_RATES))
		print_liste_field_titre("MarkRate",$_SERVER["PHP_SELF"],"","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$cumul_achat = 0;
	$cumul_vente = 0;

	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);

	if ($num > 0)
	{
		while ($i < $num /*&& $i < $conf->liste_limit*/)
		{
			$objp = $db->fetch_object($result);

			$pa = $objp->buying_price;
			$pv = $objp->selling_price;
			$marge = $objp->marge;

			if ($marge < 0)
			{
				$marginRate = ($pa != 0)?-1*(100 * $marge / $pa):'' ;
				$markRate = ($pv != 0)?-1*(100 * $marge / $pv):'' ;
			}
			else
			{
				$marginRate = ($pa != 0)?(100 * $marge / $pa):'' ;
				$markRate = ($pv != 0)?(100 * $marge / $pv):'' ;
			}

			print '<tr class="oddeven">';
			if ($client) {
		        print '<td>';
				$invoicestatic->id=$objp->facid;
				$invoicestatic->ref=$objp->facnumber;
				print $invoicestatic->getNomUrl(1);
				print "</td>\n";
				print "<td align=\"center\">";
				print dol_print_date($db->jdate($objp->datef),'day')."</td>";
		  	}
		  	else {
				$companystatic->id=$objp->socid;
				$companystatic->name=$objp->name;
				$companystatic->client=$objp->client;
		   		print "<td>".$companystatic->getNomUrl(1,'margin')."</td>\n";
		  	}

			print "<td align=\"right\">".price($pv, null, null, null, null, $rounding)."</td>\n";
			print "<td align=\"right\">".price($pa, null, null, null, null, $rounding)."</td>\n";
			print "<td align=\"right\">".price($marge, null, null, null, null, $rounding)."</td>\n";
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate, null, null, null, null, $rounding)."%")."</td>\n";
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate, null, null, null, null, $rounding)."%")."</td>\n";
			print "</tr>\n";

			$i++;
			$cumul_achat += $objp->buying_price;
			$cumul_vente += $objp->selling_price;
		}
	}

	// affichage totaux marges

	$totalMargin = $cumul_vente - $cumul_achat;
	/*if ($totalMargin < 0)
	{
		$marginRate = ($cumul_achat != 0)?-1*(100 * $totalMargin / $cumul_achat):'';
		$markRate = ($cumul_vente != 0)?-1*(100 * $totalMargin / $cumul_vente):'';
	}
	else
	{*/
		$marginRate = ($cumul_achat != 0)?(100 * $totalMargin / $cumul_achat):'';
		$markRate = ($cumul_vente != 0)?(100 * $totalMargin / $cumul_vente):'';
	//}

	print '<tr class="liste_total">';
	if ($client)
	    print '<td colspan=2>';
  	else
    	print '<td>';
  	print $langs->trans('TotalMargin')."</td>";
	print "<td align=\"right\">".price($cumul_vente, null, null, null, null, $rounding)."</td>\n";
	print "<td align=\"right\">".price($cumul_achat, null, null, null, null, $rounding)."</td>\n";
	print "<td align=\"right\">".price($totalMargin, null, null, null, null, $rounding)."</td>\n";
	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate, null, null, null, null, $rounding)."%")."</td>\n";
	if (! empty($conf->global->DISPLAY_MARK_RATES))
		print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate, null, null, null, null, $rounding)."%")."</td>\n";
	print "</tr>\n";

  print "</table>";
}
else
{
	dol_print_error($db);
}
$db->free($result);

print '<script type="text/javascript">
$(document).ready(function() {
	/*
	$("#socid").change(function() {
    	$("div.fiche form").submit();
	});*/

	$("#totalMargin").html("'.price($totalMargin, null, null, null, null, $rounding).'");
	$("#marginRate").html("'.(($marginRate === '')?'n/a':price($marginRate, null, null, null, null, $rounding)."%").'");
	$("#markRate").html("'.(($markRate === '')?'n/a':price($markRate, null, null, null, null, $rounding)."%").'");
});
</script>
';

// End of page
llxFooter();
$db->close();
