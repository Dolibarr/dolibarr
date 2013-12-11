<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
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
 *	\file       htdocs/margin/agentMargins.php
 *	\ingroup    margin
 *	\brief      Page des marges par agent commercial
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
$agentid = GETPOST('agentid','int');

$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield)
{
	if ($agentid > 0)
		$sortfield="s.nom";
	else
	    $sortfield="u.lastname";
}
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

$userstatic = new User($db);
$companystatic = new Societe($db);
$invoicestatic=new Facture($db);

$form = new Form($db);

llxHeader('',$langs->trans("Margins").' - '.$langs->trans("Agents"));

$text=$langs->trans("Margins");
print_fiche_titre($text);

// Show tabs
$head=marges_prepare_head($user);
$titre=$langs->trans("Margins");
$picto='margin';
dol_fiche_head($head, 'agentMargins', $titre, 0, $picto);

print '<form method="post" name="sel" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="border" width="100%">';

print '<tr><td width="20%">'.$langs->trans('CommercialAgent').'</td>';
print '<td colspan="4">';
print $form->select_dolusers($agentid,'agentid',1);
print '</td></tr>';

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
print '<input type="submit" class="button" value="'.$langs->trans('Launch').'" />';
print '</td></tr>';
print "</table>";
print '</form>';

$sql = "SELECT s.rowid as socid, s.nom, s.code_client, s.client, ";
$sql.= " u.rowid as agent, u.login, u.lastname, u.firstname,";
$sql.= " sum(d.total_ht) as selling_price,";
$sql.= " sum(".$db->ifsql('d.total_ht <=0','d.qty * d.buy_price_ht * -1','d.qty * d.buy_price_ht').") as buying_price,";
$sql.= " sum(".$db->ifsql('d.total_ht <=0','-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty))','d.total_ht - (d.buy_price_ht * d.qty)').") as marge" ;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."facture as f";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact e ON e.element_id = f.rowid and e.statut = 4 and e.fk_c_type_contact = ".(empty($conf->global->AGENT_CONTACT_TYPE)?-1:$conf->global->AGENT_CONTACT_TYPE);
$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND sc.fk_soc = f.fk_soc";
if (! empty($conf->global->AGENT_CONTACT_TYPE))
	$sql.= " AND ((e.fk_socpeople IS NULL AND sc.fk_user = u.rowid) OR (e.fk_socpeople IS NOT NULL AND e.fk_socpeople = u.rowid))";
else
	$sql .= " AND sc.fk_user = u.rowid";
$sql.= " AND f.fk_statut > 0";
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " AND d.fk_facture = f.rowid";
if ($agentid > 0) {
	if (! empty($conf->global->AGENT_CONTACT_TYPE))
  		$sql.= " AND ((e.fk_socpeople IS NULL AND sc.fk_user = ".$agentid.") OR (e.fk_socpeople IS NOT NULL AND e.fk_socpeople = ".$agentid."))";
	else
	    $sql .= " AND sc.fk_user = ".$agentid;
}
if (!empty($startdate))
  $sql.= " AND f.datef >= '".$startdate."'";
if (!empty($enddate))
  $sql.= " AND f.datef <= '".$enddate."'";
$sql .= " AND d.buy_price_ht IS NOT NULL";
if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1) $sql .= " AND d.buy_price_ht <> 0";
$sql.= " GROUP BY s.rowid, s.nom, s.code_client, s.client, u.rowid, u.login, u.lastname, u.firstname";
$sql.= " ORDER BY ".$sortfield." ".$sortorder;
// TODO: calculate total to display then restore pagination
//$sql.= $db->plimit($conf->liste_limit +1, $offset);

dol_syslog('margin::agentMargins.php sql='.$sql,LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	print '<br>';
	print_barre_liste($langs->trans("MarginDetails"),$page,$_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',0,0,'');

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	if ($agentid > 0)
		print_liste_field_titre($langs->trans("Customer"),$_SERVER["PHP_SELF"],"s.nom","","&amp;agentid=".$agentid,'',$sortfield,$sortorder);
	else
		print_liste_field_titre($langs->trans("CommercialAgent"),$_SERVER["PHP_SELF"],"u.lastname","","&amp;agentid=".$agentid,'',$sortfield,$sortorder);

	print_liste_field_titre($langs->trans("SellingPrice"),$_SERVER["PHP_SELF"],"selling_price","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("BuyingPrice"),$_SERVER["PHP_SELF"],"buying_price","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Margin"),$_SERVER["PHP_SELF"],"marge","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		print_liste_field_titre($langs->trans("MarginRate"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	if (! empty($conf->global->DISPLAY_MARK_RATES))
		print_liste_field_titre($langs->trans("MarkRate"),$_SERVER["PHP_SELF"],"","","&amp;agentid=".$agentid,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);

	if ($num > 0)
	{
		$var=true;

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

			$var=!$var;

			print "<tr ".$bc[$var].">";
			if ($agentid > 0) {
				$companystatic->id=$objp->socid;
				$companystatic->nom=$objp->nom;
				$companystatic->client=$objp->client;
				print "<td>".$companystatic->getNomUrl(1,'customer')."</td>\n";
			}
			else {
				$userstatic->fetch($objp->agent);
				print "<td>".$userstatic->getFullName($langs,0,0,0)."</td>\n";
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
		}
	}
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
});
</script>
