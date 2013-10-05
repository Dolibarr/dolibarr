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
 *	\file       htdocs/margin/tabs/thirdpartyMargins.php
 *	\ingroup    product margins
 *	\brief      Page des marges des factures clients pour un tiers
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

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
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datef";


/*
 * View
 */

$invoicestatic=new Facture($db);
$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty").' - '.$langs->trans("Margins"),$help_url);

if ($socid > 0)
{
    $societe = new Societe($db, $socid);
    $societe->fetch($socid);

    /*
     * Affichage onglets
     */

    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'margin', $langs->trans("ThirdParty"),0,'company');

    print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
    print '<td colspan="3">';
    print $form->showrefnav($societe,'socid','',($user->societe_id?0:1),'rowid','nom');
    print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';
    }

    if ($societe->client)
    {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $societe->code_client;
        if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($societe->fournisseur)
    {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $societe->code_fournisseur;
        if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

		// Total Margin
		print '<tr><td>'.$langs->trans("TotalMargin").'</td><td colspan="3">';
		print '<span id="totalMargin"></span>'; // set by jquery (see below)
		print '</td></tr>';

		// Margin Rate
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
			print '<tr><td>'.$langs->trans("MarginRate").'</td><td colspan="3">';
			print '<span id="marginRate"></span>'; // set by jquery (see below)
			print '</td></tr>';
		}

		// Mark Rate
		if (! empty($conf->global->DISPLAY_MARK_RATES)) {
			print '<tr><td>'.$langs->trans("MarkRate").'</td><td colspan="3">';
			print '<span id="markRate"></span>'; // set by jquery (see below)
			print '</td></tr>';
		}

		print "</table>";
		print '</div>';


		$sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client,";
		$sql.= " f.facnumber, f.total as total_ht,";
		$sql.= " sum(d.total_ht) as selling_price,";

		$sql.= $db->ifsql('f.type =2','sum(d.qty * d.buy_price_ht *-1)','sum(d.qty * d.buy_price_ht)')." as buying_price,";
        $sql.= $db->ifsql('f.type =2','sum(-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty)))','sum(d.total_ht - (d.buy_price_ht * d.qty))')." as marge," ;
		$sql.= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= ", ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.fk_statut > 0";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND d.fk_facture = f.rowid";
		$sql.= " AND f.fk_soc = $socid";
		$sql .= " AND d.buy_price_ht IS NOT NULL";
		if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)
			$sql .= " AND d.buy_price_ht <> 0";
		$sql.= " GROUP BY f.rowid";
		$sql.= " ORDER BY $sortfield $sortorder ";
		// TODO: calculate total to display then restore pagination
		//$sql.= $db->plimit($conf->liste_limit +1, $offset);

		dol_syslog('margin:tabs:thirdpartyMargins.php sql='.$sql,LOG_DEBUG);
		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);

			print_barre_liste($langs->trans("MarginDetails"),$page,$_SERVER["PHP_SELF"],"&amp;socid=$societe->id",$sortfield,$sortorder,'',0,0,'');

			$i = 0;
			print "<table class=\"noborder\" width=\"100%\">";

			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Invoice"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socid=".$_REQUEST["socid"],'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socid=".$_REQUEST["socid"],'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("SellingPrice"),$_SERVER["PHP_SELF"],"selling_price","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("BuyingPrice"),$_SERVER["PHP_SELF"],"buying_price","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Margin"),$_SERVER["PHP_SELF"],"marge","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print_liste_field_titre($langs->trans("MarginRate"),$_SERVER["PHP_SELF"],"","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print_liste_field_titre($langs->trans("MarkRate"),$_SERVER["PHP_SELF"],"","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.paye,f.fk_statut","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
			print "</tr>\n";

			$cumul_achat = 0;
			$cumul_vente = 0;
			$cumul_qty = 0;
			$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);

			if ($num > 0)
			{
				$var=True;
				while ($i < $num /*&& $i < $conf->liste_limit*/)
				{
					$objp = $db->fetch_object($result);

					if ($objp->marge < 0)
					{
						$marginRate = ($objp->buying_price != 0)?-1*(100 * round($objp->marge / $objp->buying_price, 5)):'' ;
						$markRate = ($objp->selling_price != 0)?-1*(100 * round($objp->marge / $objp->selling_price, 5)):'' ;
					}
					else
					{
						$marginRate = ($objp->buying_price != 0)?(100 * round($objp->marge / $objp->buying_price, 5)):'' ;
						$markRate = ($objp->selling_price != 0)?(100 * round($objp->marge / $objp->selling_price, 5)):'' ;
					}
					
					$var=!$var;

					print "<tr ".$bc[$var].">";
					print '<td>';
					$invoicestatic->id=$objp->facid;
					$invoicestatic->ref=$objp->facnumber;
					print $invoicestatic->getNomUrl(1);
					print "</td>\n";
					print "<td align=\"center\">";
					print dol_print_date($db->jdate($objp->datef),'day')."</td>";
					print "<td align=\"right\">".price($objp->selling_price)."</td>\n";
					print "<td align=\"right\">".price($objp->buying_price)."</td>\n";
					print "<td align=\"right\">".price($objp->marge)."</td>\n";
					if (! empty($conf->global->DISPLAY_MARGIN_RATES))
						print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate)."%")."</td>\n";
					if (! empty($conf->global->DISPLAY_MARK_RATES))
						print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate)."%")."</td>\n";
					print '<td align="right">'.$invoicestatic->LibStatut($objp->paye,$objp->statut,5).'</td>';
					print "</tr>\n";
					$i++;
					$cumul_achat += round($objp->buying_price, $rounding);
					$cumul_vente += round($objp->selling_price, $rounding);
				}
			}

			// affichage totaux marges
			$var=!$var;
			$totalMargin = $cumul_vente - $cumul_achat;
			if ($totalMargin < 0)
			{
				$marginRate = ($cumul_achat != 0)?-1*(100 * round($totalMargin / $cumul_achat, 5)):'';
				$markRate = ($cumul_vente != 0)?-1*(100 * round($totalMargin / $cumul_vente, 5)):'';
			}
			else
			{
				$marginRate = ($cumul_achat != 0)?(100 * round($totalMargin / $cumul_achat, 5)):'';
				$markRate = ($cumul_vente != 0)?(100 * round($totalMargin / $cumul_vente, 5)):'';
			}
			print '<tr '.$bc[$var].' style="border-top: 1px solid #ccc; font-weight: bold">';
			print '<td colspan=2>'.$langs->trans('TotalMargin')."</td>";
			print "<td align=\"right\">".price($cumul_vente)."</td>\n";
			print "<td align=\"right\">".price($cumul_achat)."</td>\n";
			print "<td align=\"right\">".price($totalMargin)."</td>\n";
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate)."%")."</td>\n";
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate)."%")."</td>\n";
			print '<td align="right">&nbsp;</td>';
			print "</tr>\n";
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
		print '<br>';
		$db->free($result);
}
else
{
	dol_print_error();
}


llxFooter();
$db->close();
?>
<script type="text/javascript">
$(document).ready(function() {
	$("#totalMargin").html("<?php echo price($totalMargin); ?>");
	$("#marginRate").html("<?php echo (($marginRate === '')?'n/a':price($marginRate)."%"); ?>");
	$("#markRate").html("<?php echo (($markRate === '')?'n/a':price($markRate)."%"); ?>");
});
</script>