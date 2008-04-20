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
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/tax.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/facture/paiementfourn.class.php");

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");

$year=$_GET["year"];
if (empty($year))
{
	$year_current = strftime("%Y",time());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$q=(! empty($_GET["q"]))?$_GET["q"]:1;

// Define modetax (0 or 1)
$modetax = $conf->global->TAX_MODE;
if ($_GET["modetax"]) $modetax=$_GET["modetax"];



/**
 * Affichage page
 */

llxHeader();

$company_static=new Societe($db);
$invoice_customer=new Facture($db);
$invoice_supplier=new FactureFournisseur($db);
$product_static=new Product($db);
$payment_static=new Paiement($db);
$paymentfourn_static=new PaiementFourn($db);

//print_fiche_titre($langs->trans("VAT"),"");

// Affiche en-tête du rapport
if ($modetax==1)	// Caluclate on invoice for goods and services
{
    $nom=$langs->trans("VATReportByQuartersInDueDebtMode");
    $nom.='<br>('.$langs->trans("SeeVATReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&q='.$q.'&modetax=0">','</a>').')';
    $period=$year_start.' - '.$langs->trans("Quadri")." $q (".strftime("%b %Y",dolibarr_mktime(12,0,0,(($q-1)*3)+1,1,$year_start)).' - '.strftime("%b %Y",dolibarr_mktime(12,0,0,($q*3),1,$year_start)).")";
	$prevyear=$year_start; $prevquarter=$q;
	if ($prevquarter > 1) $prevquarter--;
	else { $prevquarter=4; $prevyear--; }
	$nextyear=$year_start; $nextquarter=$q;
	if ($nextquarter < 4) $nextquarter++;
	else { $nextquarter=1; $nextyear++; }
	$periodlink=($prevyear?"<a href='".$_SERVER["PHP_SELF"]."?year=".$prevyear."&q=".$prevquarter."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".$nextyear."&q=".$nextquarter."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATDue");
    if ($conf->global->MAIN_MODULE_COMPTABILITE) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
	
	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatcust.=' ('.$langs->trans("ToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPayed");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatsup.=' ('.$langs->trans("ToGetBack").')';
}
if ($modetax==0) 	// Invoice for goods, payment for services
{
    $nom=$langs->trans("VATReportByQuartersInInputOutputMode");
    $nom.='<br>('.$langs->trans("SeeVATReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&q='.$q.'&modetax=1">','</a>').')';
    $period=$year_start.' - '.$langs->trans("Quadri")." $q (".strftime("%b %Y",dolibarr_mktime(12,0,0,(($q-1)*3)+1,1,$year_start)).' - '.strftime("%b %Y",dolibarr_mktime(12,0,0,($q*3),1,$year_start)).")";
	$prevyear=$year_start; $prevquarter=$q;
	if ($prevquarter > 1) $prevquarter--;
	else { $prevquarter=4; $prevyear--; }
	$nextyear=$year_start; $nextquarter=$q;
	if ($nextquarter < 4) $nextquarter++;
	else { $nextquarter=1; $nextyear++; }
	$periodlink=($prevyear?"<a href='".$_SERVER["PHP_SELF"]."?year=".$prevyear."&q=".$prevquarter."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".$nextyear."&q=".$nextquarter."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATIn");
    if ($conf->global->MAIN_MODULE_COMPTABILITE) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatcust.=' ('.$langs->trans("ToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPayed");
	if ($conf->global->FACTURE_TVAOPTION != 'franchise') $vatsup.=' ('.$langs->trans("ToGetBack").')';
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// VAT Received and payed

echo '<table class="noborder" width="100%">';

$y = $year_current;
$total = 0;
$subtotal = 0;
$i=0;

// Load arrays of datas
$x_coll = vat_by_quarter($db, $y, $q, $modetax, 'sell');
$x_paye = vat_by_quarter($db, $y, $q, $modetax, 'buy');

if (! is_array($x_coll) || ! is_array($x_paye))
{
	if ($x_coll == -1)
		print '<tr><td colspan="5">'.$langs->trans("NoAccountancyModuleLoaded").'</td></tr>';
	else if ($x_coll == -2)
		print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	else
		print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
}
else
{
	$x_both = array();
	//now, from these two arrays, get another array with one rate per line
	foreach(array_keys($x_coll) as $my_coll_rate)
	{
		$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
		$x_both[$my_coll_rate]['coll']['vat']     = $x_coll[$my_coll_rate]['vat'];
		$x_both[$my_coll_rate]['paye']['totalht'] = 0;
		$x_both[$my_coll_rate]['paye']['vat'] = 0;
		$x_both[$my_coll_rate]['coll']['links'] = '';
		$x_both[$my_coll_rate]['coll']['detail'] = array();
		foreach($x_coll[$my_coll_rate]['facid'] as $id=>$dummy)
		{
			$invoice_customer->id=$x_coll[$my_coll_rate]['facid'][$id];
			$invoice_customer->ref=$x_coll[$my_coll_rate]['facnum'][$id];
			$x_both[$my_coll_rate]['coll']['detail'][] = array(
				'id'        =>$x_coll[$my_coll_rate]['facid'][$id],
				'descr'     =>$x_coll[$my_coll_rate]['descr'][$id],
				'pid'       =>$x_coll[$my_coll_rate]['pid'][$id],
				'pref'      =>$x_coll[$my_coll_rate]['pref'][$id],
				'ptype'     =>$x_coll[$my_coll_rate]['ptype'][$id],
				'payment_id'=>$x_coll[$my_coll_rate]['payment_id'][$id],
				'payment_amount'=>$x_coll[$my_coll_rate]['payment_amount'][$id],
				'ftotal_ttc'=>$x_coll[$my_coll_rate]['ftotal_ttc'][$id],
				'dtotal_ttc'=>$x_coll[$my_coll_rate]['dtotal_ttc'][$id],
				'dtype'     =>$x_coll[$my_coll_rate]['dtype'][$id],
				'totalht'   =>$x_coll[$my_coll_rate]['totalht_list'][$id],
				'vat'       =>$x_coll[$my_coll_rate]['vat_list'][$id],
				'link'      =>$invoice_customer->getNomUrl(1));
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
				'id'        =>$x_paye[$my_paye_rate]['facid'][$id],
				'descr'     =>$x_paye[$my_paye_rate]['descr'][$id],
				'pid'       =>$x_paye[$my_paye_rate]['pid'][$id],
				'pref'      =>$x_paye[$my_paye_rate]['pref'][$id],
				'ptype'     =>$x_paye[$my_paye_rate]['ptype'][$id],
				'payment_id'=>$x_paye[$my_paye_rate]['payment_id'][$id],
				'payment_amount'=>$x_paye[$my_paye_rate]['payment_amount'][$id],
				'ftotal_ttc'=>$x_paye[$my_paye_rate]['ftotal_ttc'][$id],
				'dtotal_ttc'=>$x_paye[$my_paye_rate]['dtotal_ttc'][$id],
				'dtype'     =>$x_paye[$my_paye_rate]['dtype'][$id],
				'totalht'   =>$x_paye[$my_paye_rate]['totalht_list'][$id],
				'vat'       =>$x_paye[$my_paye_rate]['vat_list'][$id],
				'link'      =>$invoice_supplier->getNomUrl(1));
			//$x_both[$my_paye_rate]['paye']['links'] .= '<a href="../../fourn/facture/fiche.php?facid='.$x_paye[$my_paye_rate]['facid'][$id].'" title="'.$x_paye[$my_paye_rate]['facnum'][$id].'">..'.substr($x_paye[$my_paye_rate]['facnum'][$id],-2).'</a> ';
		}
	}
	//now we have an array (x_both) indexed by rates for coll and paye


	//print table headers for this quadri - incomes first

	$x_coll_sum = 0;
	$x_coll_ht = 0;
	$x_paye_sum = 0;
	$x_paye_ht = 0;

	$span=3;
	if ($modetax == 0) $span+=2;

	//print '<tr><td colspan="'.($span+1).'">'..')</td></tr>';

	print '<tr class="liste_titre">';
	print '<td align="left">'.$elementcust.'</td>';
	print '<td align="left">'.$productcust.'</td>';
	if ($modetax == 0) 
	{
		print '<td align="right">'.$amountcust.'</td>';
		print '<td align="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
	}
	print '<td align="right">'.$langs->trans("AmountHTVATRealReceived").'</td>';
	print '<td align="right">'.$vatcust.'</td>';
	print '</tr>';
	foreach(array_keys($x_coll) as $rate)
	{
		$subtot_coll_total_ht = 0;
		$subtot_coll_vat = 0;

		if (is_array($x_both[$rate]['coll']['detail']))
		{
			$var=true;
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="'.$span.'"></td>';
			print '</tr>'."\n";
			foreach($x_both[$rate]['coll']['detail'] as $index => $fields)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				// Ref
				print '<td nowrap align="left">'.$fields['link'].'</td>';
				//Description
				print '<td align="left">';
				if ($fields['pid'])
				{
					$product_static->id=$fields['pid'];
					$product_static->ref=$fields['pref'];
					$product_static->type=$fields['ptype'];
					print $product_static->getNomUrl(1);
					if ($fields['descr']) print ' - ';
				}
				else
				{
					if ($fields['dtype']==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' ';
				}
				print dolibarr_trunc($fields['descr'],16).'</td>';
				// Amount line
				if ($modetax == 0)
				{
					print '<td nowrap align="right">';
					print price($fields['totalht']);
					if ($fields['ftotal_ttc'])
					{
						//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
						$ratiolineinvoice=($fields['dtotal_ttc']/$fields['ftotal_ttc']);
						//print ' ('.round($ratiolineinvoice*100,2).'%)';
					}
					print '</td>';
				}
				// Payment
				$ratiopaymentinvoice=1;
				if ($modetax == 0)
				{
					if ($fields['payment_amount'] && $fields['ftotal_ttc']) $ratiopaymentinvoice=($fields['payment_amount']/$fields['ftotal_ttc']);
					print '<td nowrap align="right">';
					if ($fields['payment_amount'] && $fields['ftotal_ttc']) 
					{
						$payment_static->rowid=$fields['payment_id'];
						print $payment_static->getNomUrl(2);
					}
					print $fields['payment_amount'];
					if ($fields['payment_amount'] && $ratiopaymentinvoice) print ' ('.round($ratiopaymentinvoice*100,2).'%)';
					print '</td>';
				}
				print '<td nowrap align="right">';
				$temp_ht=$fields['totalht'];
				if ($ratiopaymentinvoice) $temp_ht=$fields['totalht']*$ratiopaymentinvoice;
				print price(price2num($temp_ht,'MT'));
				print '</td>';
				// VAT
				print '<td nowrap align="right">';
				$temp_vat=$fields['vat']*$ratiopaymentinvoice;
				print price(price2num($temp_vat,'MT'));
				//print price($fields['vat']);
				print '</td>';
				print '</tr>';
				
				$subtot_coll_total_ht += $temp_ht;
				$subtot_coll_vat      += $temp_vat;
				$x_coll_sum           += $temp_vat;
			}
		}
		print '<tr class="liste_total">';
		print '<td></td>';
		print '<td align="right">'.$langs->trans("Total").':</td>';
		if ($modetax == 0)
		{
			print '<td nowrap align="right">&nbsp;</td>';
			print '<td align="right">&nbsp;</td>';
		}
		print '<td align="right">'.price(price2num($subtot_coll_total_ht,'MT')).'</td>';
		print '<td nowrap align="right">'.price(price2num($subtot_coll_vat,'MT')).'</td>';
		print '</tr>';
	}


	print '<tr><td colspan="'.($span+1).'">&nbsp;</td></tr>';

	//print table headers for this quadri - expenses now
	//imprime les en-tete de tables pour ce quadri - maintenant les dépenses
	print '<tr class="liste_titre">';
	print '<td align="left">'.$elementsup.'</td>';
	print '<td align="left">'.$productsup.'</td>';
	if ($modetax == 0) 
	{
		print '<td align="right">'.$amountsup.'</td>';
		print '<td align="right">'.$langs->trans("Payment").' (% of invoice)</td>';
	}
	print '<td align="right">'.$langs->trans("AmountHTVATRealPayed").'</td>';
	print '<td align="right">'.$vatsup.'</td>';
	print '</tr>'."\n";
	foreach(array_keys($x_paye) as $rate)
	{
		$subtot_paye_total_ht = 0;
		$subtot_paye_vat = 0;

		if(is_array($x_both[$rate]['paye']['detail']))
		{
			$var=true;
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="'.$span.'"></td>';
			print '</tr>'."\n";
			foreach($x_both[$rate]['paye']['detail'] as $index=>$fields)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td nowrap align="left">'.$fields['link'].'</td>';
				print '<td align="left">';
				if ($fields['pid'])
				{
					$product_static->id=$fields['pid'];
					$product_static->ref=$fields['pref'];
					$product_static->type=$fields['ptype'];
					print $product_static->getNomUrl(1);
					if ($fields['descr']) print ' - ';
				}
				else
				{
					if ($fields['dtype']==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' ';
				}
				print dolibarr_trunc($fields['descr'],24).'</td>';
				// Amount line
				if ($modetax == 0)
				{
					print '<td nowrap align="right">';
					print price($fields['totalht']);
					if ($fields['ftotal_ttc'])
					{
						//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
						$ratiolineinvoice=($fields['dtotal_ttc']/$fields['ftotal_ttc']);
						//print ' ('.round($ratiolineinvoice*100,2).'%)';
					}
					print '</td>';
				}
				// Payment
				$ratiopaymentinvoice=1;
				if ($modetax == 0)
				{
					if ($fields['payment_amount'] && $fields['ftotal_ttc']) $ratiopaymentinvoice=($fields['payment_amount']/$fields['ftotal_ttc']);
					print '<td nowrap align="right">';
					if ($fields['payment_amount'] && $fields['ftotal_ttc']) 
					{
						$paymentfourn_static->rowid=$fields['payment_id'];
						print $paymentfourn_static->getNomUrl(2);
					}
					print $fields['payment_amount'];
					if ($fields['payment_amount'] && $ratiopaymentinvoice) print ' ('.round($ratiopaymentinvoice*100,2).'%)';
					print '</td>';
				}
				print '<td nowrap align="right">';
				$temp_ht=$fields['totalht'];
				if ($ratiopaymentinvoice) $temp_ht=$fields['totalht']*$ratiopaymentinvoice;
				print price(price2num($temp_ht,'MT'));
				print '</td>';
				// VAT
				print '<td nowrap align="right">';
				$temp_vat=$fields['vat']*$ratiopaymentinvoice;
				print price(price2num($temp_vat,'MT'));
				//print price($fields['vat']);
				print '</td>';
				print '</tr>';
				
				$subtot_paye_total_ht += $temp_ht;
				$subtot_paye_vat      += $temp_vat;
				$x_paye_sum           += $temp_vat;
			}
		}
		
		print '<tr class="liste_total">';
		print '<td>&nbsp;</td>';
		print '<td align="right">'.$langs->trans("Total").':</td>';
		if ($modetax == 0)
		{
			print '<td nowrap align="right">&nbsp;</td>';
			print '<td align="right">&nbsp;</td>';
		}
		print '<td align="right">'.price(price2num($subtot_paye_total_ht,'MT')).'</td>';
		print '<td nowrap align="right">'.price(price2num($subtot_paye_vat,'MT')).'</td>';
		print '</tr>';
	}		

	print '<tr><td colspan="'.($span+1).'">&nbsp;</td></tr>';

	print '<tr>';
	print '<td colspan="'.$span.'"></td><td align="right">'.$langs->trans("TotalToPay").', '.$langs->trans("Quadri").' '.$q.':</td>';
	print '</tr>'."\n";

	$diff = $x_coll_sum - $x_paye_sum;
	print "<tr>";
	print '<td colspan="'.$span.'"></td>';
	print '<td nowrap align="right"><b>'.price(price2num($diff,'MT'))."</b></td>\n";
	print "</tr>\n";

	print '<tr><td colspan="'.($span+1).'">&nbsp;</td></tr>'."\n";

	$i++;
}
echo '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
