<?php
/* Copyright (C) 2012	Christophe Battarel	<christophe.battarel@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load("margins");

// Security check
$socid = GETPOST('socid','int');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');


$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$startdate=$enddate='';

if (!empty($_POST['startdatemonth']))
  $startdate  = date('Y-m-d', dol_mktime(12, 0, 0, $_POST['startdatemonth'],  $_POST['startdateday'],  $_POST['startdateyear']));
if (!empty($_POST['enddatemonth']))
  $enddate  = date('Y-m-d', dol_mktime(12, 0, 0, $_POST['enddatemonth'],  $_POST['enddateday'],  $_POST['enddateyear']));

/*
 * View
 */

$companystatic = new Societe($db);
$invoicestatic=new Facture($db);

$form = new Form($db);

llxHeader('',$langs->trans("Margins").' - '.$langs->trans("Clients"));

$text=$langs->trans("Margins");
print_fiche_titre($text);

// Show tabs
$head=marges_prepare_head($user);
$titre=$langs->trans("Margins");
$picto='margin';
dol_fiche_head($head, 'customerMargins', $titre, 0, $picto);

print '<form method="post" name="sel">';
print '<table class="border" width="100%">';

$client = false;
if ($socid > 0) {

	$soc = new Societe($db, $socid);
	$soc->fetch($socid);

	if ($soc->client)
	{
		print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
		print '<td colspan="4">';
		$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$socid,$socid,'socid','client=1',1,0,1);
		print '</td></tr>';

		$client = true;
		if (! $sortorder) $sortorder="DESC";
		if (! $sortfield) $sortfield="f.datef";
	}
}
else {
	print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
	print '<td colspan="4">';
	$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$socid,null,'socid','client=1',1,0,1);
	print '</td></tr>';
}

// Start date
print '<td>'.$langs->trans('StartDate').'</td>';
print '<td width="20%">';
$form->select_date($startdate,'startdate','','',1,"sel",1,1);
print '</td>';
print '<td width="20%">'.$langs->trans('EndDate').'</td>';
print '<td width="20%">';
$form->select_date($enddate,'enddate','','',1,"sel",1,1);
print '</td>';
print '<td style="text-align: center;">';
print '<input type="submit" value="'.$langs->trans('Launch').'" />';
print '</td></tr>';

// Total Margin
print '<tr style="font-weight: bold"><td>'.$langs->trans("TotalMargin").'</td><td colspan="4">';
print '<span id="totalMargin"></span>'; // set by jquery (see below)
print '</td></tr>';

// Margin Rate
if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
	print '<tr style="font-weight: bold"><td>'.$langs->trans("MarginRate").'</td><td colspan="4">';
	print '<span id="marginRate"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

// Mark Rate
if (! empty($conf->global->DISPLAY_MARK_RATES)) {
	print '<tr style="font-weight: bold"><td>'.$langs->trans("MarkRate").'</td><td colspan="4">';
	print '<span id="markRate"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

print "</table>";
print '</form>';

$sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client, s.client,";
$sql.= " f.facnumber, f.total as total_ht,";
$sql.= " sum(d.subprice * d.qty * (1 - d.remise_percent / 100)) as selling_price,";
$sql.= " sum(d.buy_price_ht * d.qty) as buying_price, sum(((d.subprice * (1 - d.remise_percent / 100)) - d.buy_price_ht) * d.qty) as marge," ;
$sql.= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."facture as f";
$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND f.fk_statut > 0";
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " AND d.fk_facture = f.rowid";
if ($client)
  $sql.= " AND f.fk_soc = $socid";
if (!empty($startdate))
  $sql.= " AND f.datef >= '".$startdate."'";
if (!empty($enddate))
  $sql.= " AND f.datef <= '".$enddate."'";
$sql .= " AND d.buy_price_ht IS NOT NULL";
if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)
	$sql .= " AND d.buy_price_ht <> 0";
if ($client)
  $sql.= " GROUP BY f.rowid";
else
  $sql.= " GROUP BY s.rowid";
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

  print '<br>';
	print_barre_liste($langs->trans("MarginDetails"),$page,$_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',$num,0,'');

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	if (! empty($client)) {
  	print_liste_field_titre($langs->trans("Invoice"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socid=".$socid,'',$sortfield,$sortorder);
  	print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socid=".$socid,'align="center"',$sortfield,$sortorder);
  }
  else
  	print_liste_field_titre($langs->trans("Customer"),$_SERVER["PHP_SELF"],"s.nom","","&amp;socid=".$socid,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("SellingPrice"),$_SERVER["PHP_SELF"],"selling_price","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("BuyingPrice"),$_SERVER["PHP_SELF"],"buying_price","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Margin"),$_SERVER["PHP_SELF"],"marge","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		print_liste_field_titre($langs->trans("MarginRate"),$_SERVER["PHP_SELF"],"","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	if (! empty($conf->global->DISPLAY_MARK_RATES))
		print_liste_field_titre($langs->trans("MarkRate"),$_SERVER["PHP_SELF"],"","","&amp;socid=".$socid,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$cumul_achat = 0;
	$cumul_vente = 0;
	$cumul_qty = 0;
	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);

	if ($num > 0)
	{
		$var=True;
		while ($i < $num && $i < $conf->liste_limit)
		{
			$objp = $db->fetch_object($result);

			$marginRate = ($objp->buying_price != 0)?(100 * round($objp->marge / $objp->buying_price, 5)):'' ;
			$markRate = ($objp->selling_price != 0)?(100 * round($objp->marge / $objp->selling_price, 5)):'' ;

			$var=!$var;

			print "<tr $bc[$var]>";
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
    		$companystatic->nom=$objp->nom;
    		$companystatic->client=$objp->client;
        print "<td>".$companystatic->getNomUrl(1,'customer')."</td>\n";
      }
			print "<td align=\"right\">".price($objp->selling_price)."</td>\n";
			print "<td align=\"right\">".price($objp->buying_price)."</td>\n";
			print "<td align=\"right\">".price($objp->marge)."</td>\n";
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate)."%")."</td>\n";
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate)."%")."</td>\n";
			print "</tr>\n";
			$i++;
			$cumul_achat += round($objp->buying_price, $rounding);
			$cumul_vente += round($objp->selling_price, $rounding);
		}
	}

	// affichage totaux marges
	$var=!$var;
	$totalMargin = $cumul_vente - $cumul_achat;
	$marginRate = ($cumul_achat != 0)?(100 * round($totalMargin / $cumul_achat, 5)):'' ;
	$markRate = ($cumul_vente != 0)?(100 * round($totalMargin / $cumul_vente, 5)):'' ;
	print '<tr '.$bc[$var].' style="border-top: 1px solid #ccc; font-weight: bold">';
	if ($client)
    print '<td colspan=2>';
  else
    print '<td>';
  print $langs->trans('TotalMargin')."</td>";
	print "<td align=\"right\">".price($cumul_vente)."</td>\n";
	print "<td align=\"right\">".price($cumul_achat)."</td>\n";
	print "<td align=\"right\">".price($totalMargin)."</td>\n";
	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate)."%")."</td>\n";
	if (! empty($conf->global->DISPLAY_MARK_RATES))
		print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate)."%")."</td>\n";
	print "</tr>\n";

  print "</table>";
}
else
{
	dol_print_error($db);
}
$db->free($result);


llxFooter();
$db->close();
?>
<script type="text/javascript">
$(document).ready(function() {

  $("div.fiche form input.button['type=submit']").hide();

  $("#socid").change(function() {
     $("div.fiche form").submit();
  });

	$("#totalMargin").html("<?php echo price($totalMargin); ?>");
	$("#marginRate").html("<?php echo (($marginRate === '')?'n/a':price($marginRate)."%"); ?>");
	$("#markRate").html("<?php echo (($markRate === '')?'n/a':price($markRate)."%"); ?>");

});
</script>