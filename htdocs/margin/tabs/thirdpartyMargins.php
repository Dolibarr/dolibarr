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

$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartymargins','globalcard'));


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$invoicestatic=new Facture($db);
$form = new Form($db);

$title=$langs->trans("ThirdParty").' - '.$langs->trans("Margins");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name.' - '.$langs->trans("Files");
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

if ($socid > 0)
{
    $object = new Societe($db);
    $object->fetch($socid);

    /*
     * Affichage onglets
     */

    $head = societe_prepare_head($object);

    dol_fiche_head($head, 'margin', $langs->trans("ThirdParty"),0,'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';
    
    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');
    
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';
    
    if ($object->client)
    {
        print '<tr><td class="titlefield">';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($object->fournisseur)
    {
        print '<tr><td class="titlefield">';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

    // Total Margin
    print '<tr><td class="titlefield">'.$langs->trans("TotalMargin").'</td><td colspan="3">';
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
    print '<div style="clear:both"></div>';

    dol_fiche_end();
    
    print '<br>';
    
    $sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client,";
    $sql.= " f.rowid as facid, f.facnumber, f.total as total_ht,";
    $sql.= " f.datef, f.paye, f.fk_statut as statut, f.type,";
    $sql.= " sum(d.total_ht) as selling_price,";						// may be negative or positive
    $sql.= " sum(d.qty * d.buy_price_ht) as buying_price,";				// always positive
    $sql.= " sum(abs(d.total_ht) - (d.buy_price_ht * d.qty)) as marge";	// always positive
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture as f";
    $sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
    $sql.= " WHERE f.fk_soc = s.rowid";
    $sql.= " AND f.fk_statut > 0";
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " AND d.fk_facture = f.rowid";
    $sql.= " AND f.fk_soc = $socid";
    $sql.= " AND d.buy_price_ht IS NOT NULL";
    if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1) $sql .= " AND d.buy_price_ht <> 0";
    $sql.= " GROUP BY s.nom, s.rowid, s.code_client, f.rowid, f.facnumber, f.total, f.datef, f.paye, f.fk_statut, f.type";
    $sql.= $db->order($sortfield,$sortorder);
    // TODO: calculate total to display then restore pagination
    //$sql.= $db->plimit($conf->liste_limit +1, $offset);

    dol_syslog('margin:tabs:thirdpartyMargins.php', LOG_DEBUG);
    $result = $db->query($sql);
    if ($result)
    {
    	$num = $db->num_rows($result);

    	print_barre_liste($langs->trans("MarginDetails"),$page,$_SERVER["PHP_SELF"],"&amp;socid=".$object->id,$sortfield,$sortorder,'',0,0,'');

    	$i = 0;
    	print "<table class=\"noborder\" width=\"100%\">";

    	print '<tr class="liste_titre">';
    	print_liste_field_titre($langs->trans("Invoice"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socid=".$_REQUEST["socid"],'',$sortfield,$sortorder);
    	print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socid=".$_REQUEST["socid"],'align="center"',$sortfield,$sortorder);
    	print_liste_field_titre($langs->trans("SoldAmount"),$_SERVER["PHP_SELF"],"selling_price","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
    	print_liste_field_titre($langs->trans("PurchasedAmount"),$_SERVER["PHP_SELF"],"buying_price","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
    	print_liste_field_titre($langs->trans("Margin"),$_SERVER["PHP_SELF"],"marge","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
    	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
    		print_liste_field_titre($langs->trans("MarginRate"),$_SERVER["PHP_SELF"],"","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
    	if (! empty($conf->global->DISPLAY_MARK_RATES))
    		print_liste_field_titre($langs->trans("MarkRate"),$_SERVER["PHP_SELF"],"","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
    	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.paye,f.fk_statut","","&amp;socid=".$_REQUEST["socid"],'align="right"',$sortfield,$sortorder);
    	print "</tr>\n";

    	$cumul_achat = 0;
    	$cumul_vente = 0;

    	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);

    	if ($num > 0)
    	{
    		$var=True;
    		while ($i < $num /*&& $i < $conf->liste_limit*/)
    		{
    			$objp = $db->fetch_object($result);

    			$marginRate = ($objp->buying_price != 0)?(100 * $objp->marge / $objp->buying_price):'' ;
    			$markRate = ($objp->selling_price != 0)?(100 * $objp->marge / $objp->selling_price):'' ;

    			$var=!$var;

    			print "<tr ".$bc[$var].">";
    			print '<td>';
    			$invoicestatic->id=$objp->facid;
    			$invoicestatic->ref=$objp->facnumber;
    			print $invoicestatic->getNomUrl(1);
    			print "</td>\n";
    			print "<td align=\"center\">";
    			print dol_print_date($db->jdate($objp->datef),'day')."</td>";
    			print "<td align=\"right\">".price($objp->selling_price, null, null, null, null, $rounding)."</td>\n";
    			print "<td align=\"right\">".price(($objp->type == 2 ? -1 : 1) * $objp->buying_price, null, null, null, null, $rounding)."</td>\n";
    			print "<td align=\"right\">".price($objp->marge, null, null, null, null, $rounding)."</td>\n";
    			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
    				print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate, null, null, null, null, $rounding)."%")."</td>\n";
    			if (! empty($conf->global->DISPLAY_MARK_RATES))
    				print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate, null, null, null, null, $rounding)."%")."</td>\n";
    			print '<td align="right">'.$invoicestatic->LibStatut($objp->paye,$objp->statut,5).'</td>';
    			print "</tr>\n";
    			$i++;
    			$cumul_vente += $objp->selling_price;
    			$cumul_achat += ($objp->type == 2 ? -1 : 1) * $objp->buying_price;
    		}
    	}

    	// affichage totaux marges
    	$var=!$var;
    	$totalMargin = $cumul_vente - $cumul_achat;
    	if ($totalMargin < 0)
    	{
    		$marginRate = ($cumul_achat != 0)?-1*(100 * $totalMargin / $cumul_achat):'';
    		$markRate = ($cumul_vente != 0)?-1*(100 * $totalMargin / $cumul_vente):'';
    	}
    	else
    	{
    		$marginRate = ($cumul_achat != 0)?(100 * $totalMargin / $cumul_achat):'';
    		$markRate = ($cumul_vente != 0)?(100 * $totalMargin / $cumul_vente):'';
    	}
    	
    	// Total
    	print '<tr class="liste_total">';
    	print '<td colspan=2>'.$langs->trans('TotalMargin')."</td>";
    	print "<td align=\"right\">".price($cumul_vente, null, null, null, null, $rounding)."</td>\n";
    	print "<td align=\"right\">".price($cumul_achat, null, null, null, null, $rounding)."</td>\n";
    	print "<td align=\"right\">".price($totalMargin, null, null, null, null, $rounding)."</td>\n";
    	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
    		print "<td align=\"right\">".(($marginRate === '')?'n/a':price($marginRate, null, null, null, null, $rounding)."%")."</td>\n";
    	if (! empty($conf->global->DISPLAY_MARK_RATES))
    		print "<td align=\"right\">".(($markRate === '')?'n/a':price($markRate, null, null, null, null, $rounding)."%")."</td>\n";
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
	dol_print_error('', 'Parameter socid not defined');
}


print '
    <script type="text/javascript">
    $(document).ready(function() {
        $("#totalMargin").html("'. price($totalMargin, null, null, null, null, $rounding).'");
        $("#marginRate").html("'.(($marginRate === '')?'n/a':price($marginRate, null, null, null, null, $rounding)."%").'");
        $("#markRate").html("'.(($markRate === '')?'n/a':price($markRate, null, null, null, null, $rounding)."%").'");
    });
    </script>
';

llxFooter();
$db->close();
