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
 *	\file       htdocs/commissions/index.php
 *	\ingroup    commissions
 *	\brief      Page des commissions par agent commercial
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
if (! empty($conf->margin->enabled))
	require_once(DOL_DOCUMENT_ROOT."/margin/lib/margins.lib.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load("commissions");
if (! empty($conf->margin->enabled))
	$langs->load("margins");

// Security check
$agentid = GETPOST('agentid','int');

$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$startdate=$enddate='';

if (!empty($_POST['startdatemonth']))
  $startdate = date('Y-m-d', dol_mktime(12, 0, 0, $_POST['startdatemonth'],  $_POST['startdateday'],  $_POST['startdateyear']));
if (!empty($_POST['enddatemonth']))
  $enddate = date('Y-m-d', dol_mktime(12, 0, 0, $_POST['enddatemonth'],  $_POST['enddateday'],  $_POST['enddateyear']));

/*
 * View
 */

$userstatic = new User($db);
$companystatic = new Societe($db);
$invoicestatic=new Facture($db);

$form = new Form($db);

llxHeader('',$langs->trans("Commissions"));

$text=$langs->trans("Commissions");
print_fiche_titre($text);

print '<form method="post" name="sel">';
print '<table class="border" width="100%">';

if ($agentid > 0) {

      print '<tr><td width="20%">'.$langs->trans('CommercialAgent').'</td>';
      print '<td colspan="4">';
      print $form->select_dolusers($selected=$agentid,$htmlname='agentid',$show_empty=1,$exclude='',$disabled=0,$include='',$enableonly='');
      print '</td></tr>';

      if (! $sortorder) $sortorder="ASC";
      if (! $sortfield) $sortfield="s.nom";
}
else {
  print '<tr><td width="20%">'.$langs->trans('CommercialAgent').'</td>';
  print '<td colspan="4">';
  print $form->select_dolusers($selected='',$htmlname='agentid',$show_empty=1,$exclude='',$disabled=0,$include='',$enableonly='');
   print '</td></tr>';
  if (! $sortorder) $sortorder="ASC";
  if (! $sortfield) $sortfield="u.login";
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

// Include unpayed invoices
print '<tr><td>'.$langs->trans("IncludeUnpayedInvoices").'</td><td colspan="4">';
print '<input id="selIncluded" type="checkbox" name="unpayed" ';
if (GETPOST('unpayed') == 'on')
  print 'checked ';
print '/>';
print '</td></tr>';


// Total Margin
if ($conf->global->COMMISSION_BASE == "MARGIN") {
	print '<tr style="font-weight: bold"><td>'.$langs->trans("TotalMargin").'</td><td colspan="4">';
	print '<span id="totalBase"></span>'; // set by jquery (see below)
	print '</td></tr>';
}
elseif ($conf->global->COMMISSION_BASE == "TURNOVER") {
	print '<tr style="font-weight: bold"><td>'.$langs->trans("TurnoverTotal").'</td><td colspan="4">';
	print '<span id="totalBase"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

// Total Commission
print '<tr style="font-weight: bold"><td>'.$langs->trans("TotalCommission").'</td><td colspan="4">';
print '<span id="totalCommission"></span>'; // set by jquery (see below)
print '</td></tr>';

print "</table>";
print '</form>';

$sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client, s.client, sc.fk_user as agent,";
$sql.= " u.login,";
$sql.= " f.facnumber, f.total as total_ht,";
if ($conf->global->COMMISSION_BASE == "MARGIN") {
	$sql.= " sum(case d.product_type when 1 then 0 else (((d.subprice * (1 - d.remise_percent / 100)) - d.buy_price_ht) * d.qty) end)  as productBase," ;
	$sql.= " sum(case d.product_type when 1 then (((d.subprice * (1 - d.remise_percent / 100)) - d.buy_price_ht) * d.qty) else 0 end) as serviceBase," ;
}
elseif ($conf->global->COMMISSION_BASE == "TURNOVER") {
	$sql.= " sum(case d.product_type when 1 then 0 else (((d.subprice * (1 - d.remise_percent / 100))) * d.qty) end)  as productBase," ;
	$sql.= " sum(case d.product_type when 1 then (((d.subprice * (1 - d.remise_percent / 100))) * d.qty) else 0 end) as serviceBase," ;
}
$sql.= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."facture as f";
$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND sc.fk_soc = f.fk_soc";
$sql.= " AND sc.fk_user = u.rowid";
if (GETPOST('unpayed') == 'on')
  $sql.= " AND f.fk_statut > 0";
else
  $sql.= " AND f.fk_statut > 1";
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " AND d.fk_facture = f.rowid";
if ($conf->global->COMMISSION_BASE == "MARGIN")
  $sql.= " AND d.buy_price_ht IS NOT NULL AND d.buy_price_ht <> 0";
if ($agentid > 0)
  $sql.= " AND sc.fk_user = $agentid";
if (!empty($startdate))
  $sql.= " AND f.datef >= '".$startdate."'";
if (!empty($enddate))
  $sql.= " AND f.datef <= '".$enddate."'";
if ($agentid > 0)
  $sql.= " GROUP BY s.rowid";
else
  $sql.= " GROUP BY sc.fk_user";
$sql.= " ORDER BY $sortfield $sortorder ";
//$sql.= $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	print '<br>';
	print_barre_liste($langs->trans("CommissionDetails"),$page,$_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',$num,0,'');

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	if ($agentid > 0)
		print_liste_field_titre($langs->trans("Customer"),$_SERVER["PHP_SELF"],"s.nom","","&amp;agentid=".$agentid,'align="center"',$sortfield,$sortorder);
	else
		print_liste_field_titre($langs->trans("CommercialAgent"),$_SERVER["PHP_SELF"],"u.login","","&amp;agentid=".$agentid,'align="center"',$sortfield,$sortorder);

	// product commission
	if ($conf->global->COMMISSION_BASE == "MARGIN")
		print_liste_field_titre($langs->trans("ProductMargin"),$_SERVER["PHP_SELF"],"productBase","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	elseif ($conf->global->COMMISSION_BASE == "TURNOVER")
		print_liste_field_titre($langs->trans("ProductTurnover"),$_SERVER["PHP_SELF"],"productBase","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);

	print_liste_field_titre($langs->trans("CommissionRate"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ProductCommission"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);

	// service commission
	if ($conf->global->COMMISSION_BASE == "MARGIN")
		print_liste_field_titre($langs->trans("ServiceMargin"),$_SERVER["PHP_SELF"],"serviceBase","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	elseif ($conf->global->COMMISSION_BASE == "TURNOVER")
		print_liste_field_titre($langs->trans("ServiceTurnover"),$_SERVER["PHP_SELF"],"serviceBase","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);

	print_liste_field_titre($langs->trans("CommissionRate"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ServiceCommission"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);

	// total commission
	print_liste_field_titre($langs->trans("TotalCommission"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);

	print "</tr>\n";

	$cumul_base_produit = 0;
	$cumul_base_service = 0;
	$cumul_commission_produit = 0;
	$cumul_commission_service = 0;
	if ($num > 0)
	{
		$var=True;
		while ($i < $num && $i < $conf->liste_limit)
		{
			$objp = $db->fetch_object($result);

			$var=!$var;

			print "<tr $bc[$var]>";
			if ($agentid > 0) {
				$companystatic->id=$objp->socid;
				$companystatic->nom=$objp->nom;
				$companystatic->client=$objp->client;
				print "<td>".$companystatic->getNomUrl(1,'customer')."</td>\n";
			}
			else {
				$userstatic->id=$objp->agent;
				$userstatic->login=$objp->login;
				print "<td>".$userstatic->getLoginUrl(1)."</td>\n";
			}

			// product commission
			$productCommissionRate=(! empty($conf->global->PRODUCT_COMMISSION_RATE)?$conf->global->PRODUCT_COMMISSION_RATE:0);
			$productBase=(! empty($objp->productBase)?$objp->productBase:0);
			$productCommission = (! empty($productBase)?($productCommissionRate * $productBase / 100):0);
			print "<td align=\"right\">".price($productBase)."</td>\n";
			print "<td align=\"right\">".price($productCommissionRate)."</td>\n";
			print "<td align=\"right\">".price($productCommission)."</td>\n";

			// service commission
			$serviceCommissionRate=(! empty($conf->global->SERVICE_COMMISSION_RATE)?$conf->global->SERVICE_COMMISSION_RATE:0);
			$serviceBase=(! empty($objp->serviceBase)?$objp->serviceBase:0);
			$serviceCommission = (! empty($serviceBase)?($serviceCommissionRate * $serviceBase / 100):0);
			print "<td align=\"right\">".price($serviceBase)."</td>\n";
			print "<td align=\"right\">".price($serviceCommissionRate)."</td>\n";
			print "<td align=\"right\">".price($serviceCommission)."</td>\n";

			// total commission
			print "<td align=\"right\">".price($productCommission + $serviceCommission)."</td>\n";
			print "</tr>\n";
			$i++;
			$cumul_base_produit += $productBase;
			$cumul_base_service += $serviceBase;
			$cumul_commission_produit += $productCommission;
			$cumul_commission_service += $serviceCommission;
		}
	}

	// affichage totaux commission
	$var=!$var;
	print '<tr '.$bc[$var].' style="border-top: 1px solid #ccc; font-weight: bold">';
	if (! empty($client))
		print '<td colspan=2>';
	else
		print '<td>';
	print $langs->trans('TotalCommission')."</td>";
	// product commission
	print "<td align=\"right\">".price($cumul_base_produit)."</td>\n";
	print "<td align=\"right\">".price((! empty($conf->global->PRODUCT_COMMISSION_RATE)?$conf->global->PRODUCT_COMMISSION_RATE:0))."</td>\n";
	print "<td align=\"right\">".price($cumul_commission_produit)."</td>\n";
	// service commission
	print "<td align=\"right\">".price($cumul_base_service)."</td>\n";
	print "<td align=\"right\">".price((! empty($conf->global->SERVICE_COMMISSION_RATE)?$conf->global->SERVICE_COMMISSION_RATE:0))."</td>\n";
	print "<td align=\"right\">".price($cumul_commission_service)."</td>\n";
	// total commission
	print "<td align=\"right\">".price($cumul_commission_produit + $cumul_commission_service)."</td>\n";

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

  $("#agentid").change(function() {
     $("div.fiche form").submit();
  });

  $("#selIncluded").change(function() {
     $("div.fiche form").submit();
  });

	$("#totalBase").html("<?php echo price($cumul_base_produit + $cumul_base_service); ?>");
	$("#totalCommission").html("<?php echo price($cumul_commission_produit + $cumul_commission_service); ?>");

});
</script>