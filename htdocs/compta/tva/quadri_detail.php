<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

/**
	    \file       htdocs/compta/tva/quadri_detail.php
        \ingroup    compta
		\brief      Trimestrial page - detailed version
		\version    $Revision$
		@todo deal with recurrent invoices as well
*/

require("./pre.inc.php");
require("../../tva.class.php");
$langs->load("bills");

$year=$_GET["year"];
if ($year == 0 )
{
  $year_current = strftime("%Y",time());
  $year_start = $year_current;
} else {
  $year_current = $year;
  $year_start = $year;
}



/**
 * Gets VAT to collect for the given month of the given year
 * 
 * The function gets the VAT in split results, as the VAT declaration asks
 * to report the amounts for different VAT rates as different lines.
 * This function also accounts recurrent invoices 
 * @param		object		Database handler object
 * @param		integer		Year
 * @param		integer		Year quarter (1-4)
 */
function tva_coll($db,$y,$q)
{
	global $conf;
    if ($conf->compta->mode == "CREANCES-DETTES")
    {
        // if vat payed on due invoices
        $sql = "SELECT d.fk_facture as facid, f.facnumber as facnum, d.tva_taux as rate, d.total_ht as totalht, d.total_tva as amount, d.description as descr";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ";
        $sql.= MAIN_DB_PREFIX."facturedet as d " ;
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2) ";
        $sql.= " AND f.rowid = d.fk_facture ";
        $sql.= " AND date_format(f.datef,'%Y') = ".$y;
        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
        $sql.= " ORDER BY rate, facid";
        
    }
    else
    {
        // if vat payed on paiments
    }

    $resql = $db->query($sql);

    if ($resql)
    {
    	$list = array();
    	$rate = -1;
    	while($assoc = $db->fetch_array($resql))
    	{
    		if($assoc['rate'] != $rate){ //new rate
    			$list[$assoc['rate']]['totalht'] = $assoc['totalht'];
    			$list[$assoc['rate']]['vat'] = $assoc['amount'];
    		}else{
    			$list[$assoc['rate']]['totalht'] += $assoc['totalht'];
    			$list[$assoc['rate']]['vat'] += $assoc['amount'];
    		}
			$list[$assoc['rate']]['facid'][] = $assoc['facid'];
			$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];    			
			$list[$assoc['rate']]['descr'][] = $assoc['descr'];    			
			$list[$assoc['rate']]['totalht_list'][] = $assoc['totalht']; 			
			$list[$assoc['rate']]['vat_list'][] = $assoc['amount']; 			
    		$rate = $assoc['rate'];
    	}
		return $list;
    }
    else
    {
        dolibarr_print_error($db);
    }
}


/**
 * Gets VAT to pay for the given month of the given year
 * 
 * The function gets the VAT in split results, as the VAT declaration asks
 * to report the amounts for different VAT rates as different lines. 
 * @param		object		Database handler object
 * @param		integer		Year
 * @param		integer		Year quarter (1-4)
 */
function tva_paye($db, $y,$q)
{
	global $conf;

    if ($conf->compta->mode == "CREANCES-DETTES")
    {
        // Si on paye la tva sur les factures dues (non brouillon)
        $sql = "SELECT d.fk_facture_fourn as facid, f.facnumber as facnum, d.tva_taux as rate, d.total_ht as totalht, d.tva as amount, d.description as descr ";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ";
        $sql.= MAIN_DB_PREFIX."facture_fourn_det as d " ;
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2) ";
        $sql.= " AND f.rowid = d.fk_facture_fourn ";
        $sql.= " AND date_format(f.datef,'%Y') = ".$y;
        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
        $sql.= " ORDER BY rate, facid ";
    }
    else
    {
        // Si on paye la tva sur les payments
    }

    $resql = $db->query($sql);
    if ($resql)
    {
   	$list = array();
    	$rate = -1;
    	while($assoc = $db->fetch_array($resql))
    	{
    		if($assoc['rate'] != $rate){ //new rate
    			$list[$assoc['rate']]['totalht'] = $assoc['totalht'];
    			$list[$assoc['rate']]['vat'] = $assoc['amount'];
    		}else{
    			$list[$assoc['rate']]['totalht'] += $assoc['totalht'];
    			$list[$assoc['rate']]['vat'] += $assoc['amount'];
    		}
			$list[$assoc['rate']]['facid'][] = $assoc['facid'];
			$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];    			
			$list[$assoc['rate']]['descr'][] = $assoc['descr'];  
			$list[$assoc['rate']]['totalht_list'][] = $assoc['totalht']; 			
			$list[$assoc['rate']]['vat_list'][] = $assoc['amount']; 			
    		$rate = $assoc['rate'];
    	}
		return $list;

    }
    else
    {
        dolibarr_print_error($db);
    }
}

/**
 * Main script
 */

llxHeader();

$textprevyear="<a href=\"quadri_detail.php?year=" . ($year_current-1) . "\">".img_previous()."</a>";
$textnextyear=" <a href=\"quadri_detail.php?year=" . ($year_current+1) . "\">".img_next()."</a>";

print_fiche_titre($langs->trans("VAT"),"$textprevyear ".$langs->trans("Year")." $year_start $textnextyear");
print '<br>';

echo '<table class="noborder" width="100%">';

if ($conf->compta->mode == "CREANCES-DETTES")
{
	$y = $year_current ;
	
	
	for ($q = 1 ; $q <= 4 ; $q++ )
	{
		$total = 0;  $subtotal = 0;
		$i=0;
		$subtot_coll_total = 0;
		$subtot_coll_vat = 0;
		$subtot_paye_total = 0;
		$subtot_paye_vat = 0;
		$var=true;
		$x_coll = tva_coll($db, $y, $q);
		$x_paye = tva_paye($db, $y, $q);
		$x_both = array();
		//now, from these two arrays, get another array with one rate per line
		foreach(array_keys($x_coll) as $my_coll_rate){
			$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
			$x_both[$my_coll_rate]['coll']['vat'] = $x_coll[$my_coll_rate]['vat'];
			$x_both[$my_coll_rate]['paye']['totalht'] = 0;
			$x_both[$my_coll_rate]['paye']['vat'] = 0;
			$x_both[$my_coll_rate]['coll']['links'] = '';
			$x_both[$my_coll_rate]['coll']['detail'] = array();
			foreach($x_coll[$my_coll_rate]['facid'] as $id=>$dummy){
				$x_both[$my_coll_rate]['coll']['detail'][] = array(
					'id'=>$x_coll[$my_coll_rate]['facid'][$id],
					'descr'=>$x_coll[$my_coll_rate]['descr'][$id],
					'link'=>'<a href="../facture.php?facid='.$x_coll[$my_coll_rate]['facid'][$id].'" title="'.$langs->trans("Invoice").' '.$x_coll[$my_coll_rate]['facnum'][$id].'">'.$x_coll[$my_coll_rate]['facnum'][$id].'</a> ',
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
				$x_both[$my_paye_rate]['coll']['total_ht'] = 0;		
				$x_both[$my_paye_rate]['coll']['vat'] = 0;		
			}
			$x_both[$my_paye_rate]['paye']['links'] = '';
			$x_both[$my_coll_rate]['paye']['detail'] = array();
			foreach($x_paye[$my_paye_rate]['facid'] as $id=>$dummy){
				$x_both[$my_coll_rate]['paye']['detail'][] = array(
					'id'=>$x_paye[$my_paye_rate]['facid'][$id],
					'descr'=>$x_paye[$my_paye_rate]['descr'][$id],
					'link'=>'<a href="../facture.php?facid='.$x_paye[$my_paye_rate]['facid'][$id].'" title="'.$langs->trans("Invoice").' '.$x_paye[$my_paye_rate]['facnum'][$id].'">'.$x_paye[$my_paye_rate]['facnum'][$id].'</a> ',
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
		print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("Quadri")." $q (".strftime("%b %Y",mktime(0,0,0,(($q-1)*3)+1,1,$y)).' - '.strftime("%b %Y",mktime(0,0,0,($q*3),1,$y)).')</td></tr>';
		print '<tr class="liste_titre">';
		print '<td align="left">'.$langs->trans("CustomersInvoices").'</td>';
		print '<td align="left"> '.$langs->trans("Description").'</td>';
		print '<td align="right">'.$langs->trans("Income").'</td>';
		print '<td align="right">'.$langs->trans("VATToPay").'</td>';
		print '</tr>';
		//foreach($x_both as $rate => $both){
		foreach(array_keys($x_coll) as $rate){
			$var=!$var;
			if(is_array($x_both[$rate]['coll']['detail'])){
				print "<tr>";
				print '<td class="tax_rate">'.$langs->trans("Rate").': '.$rate.'%</td><td colspan="3"></td>';
				print '</tr>'."\n";
				foreach($x_both[$rate]['coll']['detail'] as $index=>$fields){
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td nowrap align="left">'.$fields['link'].'</td>';
					print '<td align="left">'.$fields['descr'].'</td>';
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
		print '<td align="left">'.$langs->trans("SuppliersInvoices").'</td>';
		print '<td align="left">'.$langs->trans("Description").'</td>';
		print '<td align="right">'.$langs->trans("Outcome").'</td>';
		print '<td align="right">'.$langs->trans("VATToCollect").'</td>';
		print '</tr>'."\n";
		foreach(array_keys($x_paye) as $rate){
			$var=!$var;
			if(is_array($x_both[$rate]['paye']['detail']))
			{
				print "<tr>";
				print '<td class="tax_rate">'.$langs->trans("Rate").': '.$rate.'%</td><td colspan="3"></td>';
				print '</tr>'."\n";
				foreach($x_both[$rate]['paye']['detail'] as $index=>$fields){
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td nowrap align="left">'.$fields['link'].'</td>';
					print '<td align="left">'.$fields['descr'].'</td>';
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

		print '<tr class="liste_titre">';
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

}
else
{
	print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	print '<tr><td colspan="5">'.$langs->trans("FeatureIsSupportedInInOutModeOnly").'</td></tr>';
}

echo '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
