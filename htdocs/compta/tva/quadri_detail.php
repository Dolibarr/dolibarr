<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2007 Yannick Warnier      <ywarnier@beeznest.org>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
	    \file       htdocs/compta/tva/quadri_detail.php
        \ingroup    tax
		\brief      Trimestrial page - detailed version
		\version    $Id$
		\todo 		Deal with recurrent invoices as well
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/tax.lib.php");
require_once(DOL_DOCUMENT_ROOT."/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");

$year=$_GET["year"];
if ($year == 0 )
{
  $year_current = strftime("%Y",time());
  $year_start = $year_current;
} else {
  $year_current = $year;
  $year_start = $year;
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];



/**
 * Affichage page
 */

llxHeader();

$company_static=new Societe($db);
$invoice_customer=new Facture($db);
$invoice_supplier=new FactureFournisseur($db);
$product_static=new Product($db);

print_fiche_titre($langs->trans("VAT"),"");

// Affiche en-tête du rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("ReportByQuarter");
    //$nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("VATReportDesc");
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
	
	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatcust.=' ('.$langs->trans("VATToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPayed");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatsup.=' ('.$langs->trans("VATToCollect").')';
}
else {
    $nom=$langs->trans("ReportByQuarter");
    //$nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("VATReportDesc");
    if ($conf->global->MAIN_MODULE_COMPTABILITE) $description.='<br>'.img_warning().' '.$langs->trans('OptionModeTrueInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatcust.=' ('.$langs->trans("VATToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPayed");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatsup.=' ('.$langs->trans("VATToCollect").')';
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// VAT Received and payed

echo '<table class="noborder" width="100%">';

$y = $year_current;
for ($q = 1 ; $q <= 4 ; $q++ )
{
	$total = 0;  $subtotal = 0;
	$i=0;
	$subtot_coll_total = 0;
	$subtot_coll_vat = 0;
	$subtot_paye_total = 0;
	$subtot_paye_vat = 0;
	
	$x_coll = vat_received_by_quarter($db, $y, $q);
	$x_paye = vat_payed_by_quarter($db, $y, $q);
	
	if (! is_array($x_coll))
	{
		print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
		print '<tr><td colspan="5">'.$langs->trans("FeatureIsSupportedInInOutModeOnly").'</td></tr>';
		break;
	}
	
	$x_both = array();
	//now, from these two arrays, get another array with one rate per line
	foreach(array_keys($x_coll) as $my_coll_rate){
		$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
		$x_both[$my_coll_rate]['coll']['vat'] = $x_coll[$my_coll_rate]['vat'];
		$x_both[$my_coll_rate]['paye']['totalht'] = 0;
		$x_both[$my_coll_rate]['paye']['vat'] = 0;
		$x_both[$my_coll_rate]['coll']['links'] = '';
		$x_both[$my_coll_rate]['coll']['detail'] = array();
		foreach($x_coll[$my_coll_rate]['facid'] as $id=>$dummy)
		{
			$invoice_customer->id=$x_coll[$my_coll_rate]['facid'][$id];
			$invoice_customer->ref=$x_coll[$my_coll_rate]['facnum'][$id];
			$x_both[$my_coll_rate]['coll']['detail'][] = array(
				'id'=>$x_coll[$my_coll_rate]['facid'][$id],
				'descr'=>$x_coll[$my_coll_rate]['descr'][$id],
				'pid'=>$x_coll[$my_coll_rate]['pid'][$id],
				'pref'=>$x_coll[$my_coll_rate]['pref'][$id],
				'ptype'=>$x_coll[$my_coll_rate]['ptype'][$id],
				'link'=>$invoice_customer->getNomUrl(1),
				'totalht'=>$x_coll[$my_coll_rate]['totalht_list'][$id],
				'vat'=>$x_coll[$my_coll_rate]['vat_list'][$id]);				
			//$x_both[$my_coll_rate]['coll']['links'] .= '<a href="../facture.php?facid='.$x_coll[$my_coll_rate]['facid'][$id].'" title="'.$x_coll[$my_coll_rate]['facnum'][$id].'">..'.substr($x_coll[$my_coll_rate]['facnum'][$id],-2).'</a> ';
		}
	}
	// tva payed
	foreach(array_keys($x_paye) as $my_paye_rate){
		$x_both[$my_paye_rate]['paye']['totalht'] = $x_paye[$my_paye_rate]['totalht'];
		$x_both[$my_paye_rate]['paye']['vat'] = $x_paye[$my_paye_rate]['vat'];
		if(!isset($x_both[$my_paye_rate]['coll']['totalht'])){
			$x_both[$my_paye_rate]['coll']['totalht'] = 0;		
			$x_both[$my_paye_rate]['coll']['vat'] = 0;		
		}
		$x_both[$my_paye_rate]['paye']['links'] = '';
		$x_both[$my_paye_rate]['paye']['detail'] = array();

		foreach($x_paye[$my_paye_rate]['facid'] as $id=>$dummy)
		{
			$invoice_supplier->id=$x_paye[$my_paye_rate]['facid'][$id];
			$invoice_supplier->ref=$x_paye[$my_paye_rate]['facnum'][$id];
			$x_both[$my_paye_rate]['paye']['detail'][] = array(
				'id'=>$x_paye[$my_paye_rate]['facid'][$id],
				'descr'=>$x_paye[$my_paye_rate]['descr'][$id],
				'pid'=>$x_paye[$my_paye_rate]['pid'][$id],
				'pref'=>$x_coll[$my_coll_rate]['pref'][$id],
				'ptype'=>$x_coll[$my_coll_rate]['ptype'][$id],
				'link'=>$invoice_supplier->getNomUrl(1),
				'totalht'=>$x_paye[$my_paye_rate]['totalht_list'][$id],
				'vat'=>$x_paye[$my_paye_rate]['vat_list'][$id]);				
			//$x_both[$my_paye_rate]['paye']['links'] .= '<a href="../../fourn/facture/fiche.php?facid='.$x_paye[$my_paye_rate]['facid'][$id].'" title="'.$x_paye[$my_paye_rate]['facnum'][$id].'">..'.substr($x_paye[$my_paye_rate]['facnum'][$id],-2).'</a> ';
		}
	}
	//now we have an array (x_both) indexed by rates for coll and paye

	//print table headers for this quadri - incomes first
	//imprime les en-tete de tables pour ce quadri - d'abord les revenus
	
	$x_coll_sum = 0;
	$x_coll_ht = 0;
	$x_paye_sum = 0;
	$x_paye_ht = 0;
	
	print '<tr><td colspan="4">'.$langs->trans("Quadri")." $q (".strftime("%b %Y",dolibarr_mktime(12,0,0,(($q-1)*3)+1,1,$y)).' - '.strftime("%b %Y",dolibarr_mktime(12,0,0,($q*3),1,$y)).')</td></tr>';
	
	print '<tr class="liste_titre">';
	print '<td align="left">'.$elementcust.'</td>';
	print '<td align="left">'.$productcust.'</td>';
	print '<td align="right">'.$amountcust.'</td>';
	print '<td align="right">'.$vatcust.'</td>';
	print '</tr>';
	$var=true;
	foreach(array_keys($x_coll) as $rate)
	{
		if (is_array($x_both[$rate]['coll']['detail']))
		{
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="3"></td>';
			print '</tr>'."\n";
			foreach($x_both[$rate]['coll']['detail'] as $index => $fields)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td nowrap align="left">'.$fields['link'].'</td>';
				print '<td align="left">';
				if ($fields['pid']) {
					$product_static->id=$fields['pid'];
					$product_static->ref=$fields['pref'];
					$product_static->fk_product_type=$fields['ptype'];
					print $product_static->getNomUrl(1);
					if ($fields['descr']) print ' - ';
				}
				print dolibarr_trunc($fields['descr'],24).'</td>';
				print '<td nowrap align="right">'.price($fields['totalht']).'</td>';
				print '<td nowrap align="right">'.price($fields['vat']).'</td>';
				print '</tr>';
			}
		}
		$x_coll_sum += $x_both[$rate]['coll']['vat'];
		$subtot_coll_total 	+= $x_both[$rate]['coll']['totalht'];
		$subtot_coll_vat 	+= $x_both[$rate]['coll']['vat'];
	}
	print '<tr class="liste_total">' .
			'<td></td>' .
			'<td align="right">'.$langs->trans("Total").':</td>' .
			'<td nowrap align="right">'.price($subtot_coll_total).'</td>' .
			'<td nowrap align="right">'.price($subtot_coll_vat).'</td>' .
			'</tr>' ;

	//print table headers for this quadri - expenses now
	//imprime les en-tete de tables pour ce quadri - maintenant les dépenses
	print '<tr class="liste_titre">';
	print '<td align="left">'.$elementsup.'</td>';
	print '<td align="left">'.$productsup.'</td>';
	print '<td align="right">'.$amountsup.'</td>';
	print '<td align="right">'.$vatsup.'</td>';
	print '</tr>'."\n";
	$var=true;
	foreach(array_keys($x_paye) as $rate)
	{
		if(is_array($x_both[$rate]['paye']['detail']))
		{
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="3"></td>';
			print '</tr>'."\n";
			foreach($x_both[$rate]['paye']['detail'] as $index=>$fields){
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td nowrap align="left">'.$fields['link'].'</td>';
				print '<td align="left">';
				print $fields['pid'];
				print $fields['descr'].'</td>';
				print '<td nowrap align="right">'.price($fields['totalht']).'</td>';
				print '<td nowrap align="right">'.price($fields['vat']).'</td>';
				print '</tr>';
			}
		}
		$x_paye_sum += $x_both[$rate]['paye']['vat'];
		$subtot_paye_total 	+= $x_both[$rate]['paye']['totalht'];
		$subtot_paye_vat 	+= $x_both[$rate]['paye']['vat'];
	}		
	print '<tr class="liste_total">' .
			'<td></td>' .
			'<td align="right">'.$langs->trans("Total").':</td>' .
			'<td nowrap align="right">'.price($subtot_paye_total).'</td>' .
			'<td nowrap align="right">'.price($subtot_paye_vat).'</td>' .
		  '</tr>';

	print '<tr>';
	print '<td colspan="3"></td><td align="right">'.$langs->trans("TotalToPay").' - '.$langs->trans("Quadri").$q.'</td>';
	print '</tr>'."\n";

	$diff = $x_coll_sum - $x_paye_sum;
	//$total = $total + $diff;
	//$subtotal = $subtotal + $diff;

	print "<tr>";
	print '<td colspan="3"></td>';
	//print '<td nowrap align="right"><b>'.price($total).'</b></td>' .
	print '<td nowrap align="right"><b>'.price($diff)."</b></td>\n";
	print "</tr>\n";

	print '</tr><tr><td colspan="4">&nbsp;</td></tr>'."\n";

	$i++;
}

echo '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
