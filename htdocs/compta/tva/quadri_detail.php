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
require_once(DOL_DOCUMENT_ROOT."/tva.class.php");

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");

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

print_fiche_titre($langs->trans("VAT"),"");

// Affiche en-tête du rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("ReportByQuarter");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("VATReportDesc");
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("ReportByQuarter");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("VATReportDesc");
    if ($conf->global->MAIN_MODULE_COMPTABILITE) $description.='<br>'.img_warning().' '.$langs->trans('OptionModeTrueInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
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
	$var=true;
	$x_coll = tva_coll($db, $y, $q);
	$x_paye = tva_paye($db, $y, $q);
	
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
			$x_both[$my_paye_rate]['coll']['totalht'] = 0;		
			$x_both[$my_paye_rate]['coll']['vat'] = 0;		
		}
		$x_both[$my_paye_rate]['paye']['links'] = '';
		$x_both[$my_paye_rate]['paye']['detail'] = array();
		foreach($x_paye[$my_paye_rate]['facid'] as $id=>$dummy){
			$x_both[$my_paye_rate]['paye']['detail'][] = array(
				'id'=>$x_paye[$my_paye_rate]['facid'][$id],
				'descr'=>$x_paye[$my_paye_rate]['descr'][$id],
				'link'=>'<a href="../../fourn/facture/fiche.php?facid='.$x_paye[$my_paye_rate]['facid'][$id].'" title="'.$langs->trans("Invoice").' '.$x_paye[$my_paye_rate]['facnum'][$id].'">'.$x_paye[$my_paye_rate]['facnum'][$id].'</a> ',
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
	
	print '<tr><td colspan="4">'.$langs->trans("Quadri")." $q (".strftime("%b %Y",dolibarr_mktime(0,0,0,(($q-1)*3)+1,1,$y)).' - '.strftime("%b %Y",mktime(0,0,0,($q*3),1,$y)).')</td></tr>';
	
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
	global $conf, $modecompta;
	
    // Define sql request
	$sql='';
	if ($modecompta == "CREANCES-DETTES")
    {
        // If vat payed on due invoices (non draft)
        $sql = "SELECT d.rowid, d.fk_facture as facid, f.facnumber as facnum, d.tva_taux as rate, d.total_ht as totalht, d.total_tva as amount, d.description as descr";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
        $sql.= " ".MAIN_DB_PREFIX."facturedet as d" ;
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2)";	// Validated or payed (partially or completely)
        $sql.= " AND f.rowid = d.fk_facture";
        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
        $sql.= " ORDER BY rate, facid, d.rowid";
    }
    else
    {
        // If vat payed on payments
		if ($conf->global->MAIN_MODULE_COMPTABILITEEXPERT)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocké dans la table des payments.
	        // Seul le module compta expert peut résoudre ce problème.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{		
	        // Tva sur factures payés (should be on payment)
	        $sql = "SELECT d.rowid, d.fk_facture as facid, f.facnumber as facnum, d.tva_taux as rate, d.total_ht as totalht, d.total_tva as amount, d.description as descr";
	        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
	        $sql.= " ".MAIN_DB_PREFIX."facturedet as d" ;
	        $sql.= " WHERE ";
			$sql.= " f.fk_statut in (2)";	// Payed (partially or completely)
	        $sql.= " AND f.rowid = d.fk_facture";
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
	        $sql.= " ORDER BY d.rowid, rate, facid";
		}
    }

	if ($sql)
	{
		dolibarr_syslog("Client::tva_coll sql=".$sql);
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
			return -2;
	    }
	}
	else
	{
			return -1;
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
	global $conf, $modecompta;

    // Define sql request
   	$sql='';
	if ($modecompta == "CREANCES-DETTES")
    {
        // Si on paye la tva sur les factures dues (non brouillon)
        $sql = "SELECT d.rowid, d.fk_facture_fourn as facid, f.facnumber as facnum, d.tva_taux as rate, d.total_ht as totalht, d.tva as amount, d.description as descr ";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ";
        $sql.= " ".MAIN_DB_PREFIX."facture_fourn_det as d " ;
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2)";	// Validated or payed (partially or completely)
        $sql.= " AND f.rowid = d.fk_facture_fourn ";
        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
        $sql.= " ORDER BY d.rowid, rate, facid ";
    }
    else
    {
        // Si on paye la tva sur les payments

		if ($conf->global->MAIN_MODULE_COMPTABILITEEXPERT)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocké dans la table des payments.
	        // Seul le module compta expert peut résoudre ce problème.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
	        // Tva sur factures payés
	        $sql = "SELECT d.rowid, d.fk_facture_fourn as facid, f.facnumber as facnum, d.tva_taux as rate, d.total_ht as totalht, d.tva as amount, d.description as descr ";
	        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ";
	        $sql.= " ".MAIN_DB_PREFIX."facture_fourn_det as d " ;
	        $sql.= " WHERE ";
	        //$sql.= " f.fk_statut in (1,2)";	// Validated or payed (partially or completely)
	        $sql.= " f.paye in (1)";		// Payed (completely)
	        $sql.= " AND f.rowid = d.fk_facture_fourn ";
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
	        $sql.= " ORDER BY d.rowid, rate, facid ";
		}
	}

	if ($sql)
	{
		dolibarr_syslog("Client::tva_paye sql=".$sql);
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
			return -2;
	    }
	}
	else
	{
		return -1;
	}		
}

?>
