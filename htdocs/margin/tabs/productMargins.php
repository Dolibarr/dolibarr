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
 *	\file       htdocs/margin/tabs/productMargins.php
 *	\ingroup    product margins
 *	\brief      Page des marges des factures clients pour un produit
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load("margins");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$object = new Product($db);

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


if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);

	llxHeader("","",$langs->trans("CardProduct".$object->type));

	/*
	 *  En mode visu
	 */
	if ($result > 0)
	{
		$head=product_prepare_head($object, $user);
		$titre=$langs->trans("CardProduct".$object->type);
		$picto=($object->type==1?'service':'product');
		dol_fiche_head($head, 'margin', $titre, 0, $picto);

		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->libelle.'</td>';
		print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
		print $object->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
		print $object->getLibStatut(2,1);
		print '</td></tr>';

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


		$sql = "SELECT s.nom, s.rowid as socid, s.code_client,";
		$sql.= " f.facnumber, f.total as total_ht,";
		$sql.= " sum(d.total_ht) as selling_price,";
        $sql.= $db->ifsql('f.type =2','sum(d.qty *-1)','sum(d.qty)')." as qty,";
		$sql.= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid,";
		$sql.= $db->ifsql('f.type =2','sum(d.qty * d.buy_price_ht *-1)','sum(d.qty * d.buy_price_ht)')." as buying_price,";
        $sql.= $db->ifsql('f.type =2','sum(-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty)))','sum(d.total_ht - (d.buy_price_ht * d.qty))')." as marge" ;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= ", ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.fk_statut > 0";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND d.fk_facture = f.rowid";
		$sql.= " AND d.fk_product =".$object->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if (! empty($socid)) $sql.= " AND f.fk_soc = $socid";
		$sql .= " AND d.buy_price_ht IS NOT NULL";
		if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)
			$sql .= " AND d.buy_price_ht <> 0";
		$sql.= " GROUP BY f.rowid";
		$sql.= " ORDER BY $sortfield $sortorder ";
		// TODO: calculate total to display then restore pagination
		//$sql.= $db->plimit($conf->liste_limit +1, $offset);
		dol_syslog('margin:tabs:productMargins.php sql='.$sql,LOG_DEBUG);
		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);

			print_barre_liste($langs->trans("MarginDetails"),$page,$_SERVER["PHP_SELF"],"&amp;id=$object->id",$sortfield,$sortorder,'',0,0,'');

			$i = 0;
			print "<table class=\"noborder\" width=\"100%\">";

			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Invoice"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;id=".$object->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$object->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$object->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","&amp;id=".$object->id,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("SellingPrice"),$_SERVER["PHP_SELF"],"selling_price","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("BuyingPrice"),$_SERVER["PHP_SELF"],"buying_price","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Qty"),$_SERVER["PHP_SELF"],"d.qty","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Margin"),$_SERVER["PHP_SELF"],"marge","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print_liste_field_titre($langs->trans("MarginRate"),$_SERVER["PHP_SELF"],"","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print_liste_field_titre($langs->trans("MarkRate"),$_SERVER["PHP_SELF"],"","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.paye,f.fk_statut","","&amp;id=".$object->id,'align="right"',$sortfield,$sortorder);
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
					$var=!$var;

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

					print "<tr ".$bc[$var].">";
					print '<td>';
					$invoicestatic->id=$objp->facid;
					$invoicestatic->ref=$objp->facnumber;
					print $invoicestatic->getNomUrl(1);
					print "</td>\n";
					print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,44).'</a></td>';
					print "<td>".$objp->code_client."</td>\n";
					print "<td align=\"center\">";
					print dol_print_date($db->jdate($objp->datef),'day')."</td>";
					print "<td align=\"right\">".price($objp->selling_price)."</td>\n";
					print "<td align=\"right\">".price($objp->buying_price)."</td>\n";
					print "<td align=\"right\">".price($objp->qty)."</td>\n";
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
					$cumul_qty += $objp->qty;
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
			print '<td colspan=4>'.$langs->trans('TotalMargin')."</td>";
			print "<td align=\"right\">".price($cumul_vente)."</td>\n";
			print "<td align=\"right\">".price($cumul_achat)."</td>\n";
			print "<td align=\"right\">".price($cumul_qty)."</td>\n";
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
