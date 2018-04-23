<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Yannick Warnier      <ywarnier@beeznest.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * 	    \file       htdocs/compta/tva/quadri.php
 *      \ingroup    tax
 *      \brief      Trimestrial page
 *      TODO 		Deal with recurrent invoices as well
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';

$langs->loadLangs(array("other","compta","banks","bills","companies","product","trips","admin"));

$year = GETPOST('year', 'int');
if ($year == 0 )
{
  $year_current = strftime("%Y",time());
  $year_start = $year_current;
} else {
  $year_current = $year;
  $year_start = $year;
}

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');


/**
 * Gets VAT to collect for the given month of the given year
 * The function gets the VAT in split results, as the VAT declaration asks
 * to report the amounts for different VAT rates as different lines.
 * This function also accounts recurrent invoices.
 *
 * @param	DoliDB	$db		Database handler
 * @param	int		$y		Year
 * @param	int		$q		Year quarter (1-4)
 * @return	array
 */
function tva_coll($db,$y,$q)
{
	global $conf;

    if ($conf->global->ACCOUNTING_MODE == "CREANCES-DETTES")
    {
        // if vat paid on due invoices
        $sql = "SELECT d.fk_facture as facid, f.facnumber as facnum, d.tva_tx as rate, d.total_ht as totalht, d.total_tva as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
        $sql.= ", ".MAIN_DB_PREFIX."facturedet as d" ;
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE f.fk_soc = s.rowid";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.fk_statut in (1,2)";
        $sql.= " AND f.rowid = d.fk_facture ";
        $sql.= " AND date_format(f.datef,'%Y') = '".$y."'";
        $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3);
        $sql.= " AND date_format(f.datef,'%m') <= ".($q*3).")";
        $sql.= " ORDER BY rate, facid";

    }
    else
    {
        // if vat paid on paiments
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
    			$list[$assoc['rate']]['facid'][] = $assoc['facid'];
    			$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
    		}else{
    			$list[$assoc['rate']]['totalht'] += $assoc['totalht'];
    			$list[$assoc['rate']]['vat'] += $assoc['amount'];
    			if(!in_array($assoc['facid'],$list[$assoc['rate']]['facid'])){
	    			$list[$assoc['rate']]['facid'][] = $assoc['facid'];
	    			$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
    			}
    		}
    		$rate = $assoc['rate'];
    	}
		return $list;
    }
    else
    {
        dol_print_error($db);
    }
}


/**
 * Gets VAT to pay for the given month of the given year
 * The function gets the VAT in split results, as the VAT declaration asks
 * to report the amounts for different VAT rates as different lines
 *
 * @param	DoliDB	$db			Database handler object
 * @param	int		$y			Year
 * @param	int		$q			Year quarter (1-4)
 * @return	array
 */
function tva_paye($db, $y,$q)
{
	global $conf;

    if ($conf->global->ACCOUNTING_MODE == "CREANCES-DETTES")
    {
        // Si on paye la tva sur les factures dues (non brouillon)
        $sql = "SELECT d.fk_facture_fourn as facid, f.ref_supplier as facnum, d.tva_tx as rate, d.total_ht as totalht, d.tva as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
        $sql.= ", ".MAIN_DB_PREFIX."facture_fourn_det as d" ;
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE f.fk_soc = s.rowid";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.fk_statut = 1 ";
        $sql.= " AND f.rowid = d.fk_facture_fourn ";
        $sql.= " AND date_format(f.datef,'%Y') = '".$y."'";
        $sql.= " AND (round(date_format(f.datef,'%m')) > ".(($q-1)*3);
        $sql.= " AND round(date_format(f.datef,'%m')) <= ".($q*3).")";
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
    			$list[$assoc['rate']]['facid'][] = $assoc['facid'];
    			$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
    		}else{
    			$list[$assoc['rate']]['totalht'] += $assoc['totalht'];
    			$list[$assoc['rate']]['vat'] += $assoc['amount'];
    			if(!in_array($assoc['facid'],$list[$assoc['rate']]['facid'])){
	    			$list[$assoc['rate']]['facid'][] = $assoc['facid'];
	    			$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
    			}
    		}
    		$rate = $assoc['rate'];
    	}
		return $list;

    }
    else
    {
        dol_print_error($db);
    }
}


/**
 * View
 */

llxHeader();

$textprevyear="<a href=\"quadri.php?year=" . ($year_current-1) . "\">".img_previous()."</a>";
$textnextyear=" <a href=\"quadri.php?year=" . ($year_current+1) . "\">".img_next()."</a>";

print load_fiche_titre($langs->trans("VAT"),"$textprevyear ".$langs->trans("Year")." $year_start $textnextyear");


echo '<table width="100%">';
echo '<tr><td>';
print load_fiche_titre($langs->trans("VATSummary"));
echo '</td></tr>';

echo '<tr>';

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"20%\">".$langs->trans("Year")." $year_current</td>";
print "<td align=\"right\">".$langs->trans("Income")."</td>";
print "<td align=\"right\">".$langs->trans("VATToPay")."</td>";
print "<td align=\"right\">".$langs->trans("Invoices")."</td>";
print "<td align=\"right\">".$langs->trans("Outcome")."</td>";
print "<td align=\"right\">".$langs->trans("VATToCollect")."</td>";
print "<td align=\"right\">".$langs->trans("Invoices")."</td>";
print "<td align=\"right\">".$langs->trans("TotalToPay")."</td>";
print "</tr>\n";

if ($conf->global->ACCOUNTING_MODE == "CREANCES-DETTES")
{
	$y = $year_current;

	$total = 0;  $subtotal = 0;
	$i=0;
	$subtot_coll_total = 0;
	$subtot_coll_vat = 0;
	$subtot_paye_total = 0;
	$subtot_paye_vat = 0;
	for ($q = 1 ; $q <= 4 ; $q++) {
		print "<tr class=\"liste_titre\"><td colspan=\"8\">".$langs->trans("Quadri")." $q (".dol_print_date(dol_mktime(0,0,0,(($q-1)*3)+1,1,$y),"%b %Y").' - '.dol_print_date(dol_mktime(0,0,0,($q*3),1,$y),"%b %Y").")</td></tr>";
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
			foreach($x_coll[$my_coll_rate]['facid'] as $id=>$dummy){
				$x_both[$my_coll_rate]['coll']['links'] .= '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$x_coll[$my_coll_rate]['facid'][$id].'" title="'.$x_coll[$my_coll_rate]['facnum'][$id].'">..'.substr($x_coll[$my_coll_rate]['facnum'][$id],-2).'</a> ';
			}
		}
		foreach(array_keys($x_paye) as $my_paye_rate){
			$x_both[$my_paye_rate]['paye']['totalht'] = $x_paye[$my_paye_rate]['totalht'];
			$x_both[$my_paye_rate]['paye']['vat'] = $x_paye[$my_paye_rate]['vat'];
			if(!isset($x_both[$my_paye_rate]['coll']['totalht'])){
				$x_both[$my_paye_rate]['coll']['total_ht'] = 0;
				$x_both[$my_paye_rate]['coll']['vat'] = 0;
			}
			$x_both[$my_paye_rate]['paye']['links'] = '';
			foreach($x_paye[$my_paye_rate]['facid'] as $id=>$dummy){
				$x_both[$my_paye_rate]['paye']['links'] .= '<a href="../../fourn/facture/card.php?facid='.$x_paye[$my_paye_rate]['facid'][$id].'" title="'.$x_paye[$my_paye_rate]['facnum'][$id].'">..'.substr($x_paye[$my_paye_rate]['facnum'][$id],-2).'</a> ';
			}
		}
		//now we have an array (x_both) indexed by rates for coll and paye

		$x_coll_sum = 0;
		$x_coll_ht = 0;
		$x_paye_sum = 0;
		$x_paye_ht = 0;
		foreach($x_both as $rate => $both){

			print '<tr class="oddeven">';
			print "<td>$rate%</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($both['coll']['totalht'])."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($both['coll']['vat'])."</td>";
			print "<td align=\"right\">".$both['coll']['links']."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($both['paye']['totalht'])."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($both['paye']['vat'])."</td>";
			print "<td align=\"right\">".$both['paye']['links']."</td>";
			print "<td></td>";
			print "</tr>";
			$x_coll_sum += $both['coll']['vat'];
			$x_paye_sum += $both['paye']['vat'];
			$subtot_coll_total 	+= $both['coll']['totalht'];
			$subtot_coll_vat 	+= $both['coll']['vat'];
			$subtot_paye_total 	+= $both['paye']['totalht'];
			$subtot_paye_vat 	+= $both['paye']['vat'];
		}

		$diff = $x_coll_sum - $x_paye_sum;
		$total = $total + $diff;
		$subtotal = $subtotal + $diff;


		print '<tr class="oddeven">';
		print '<td colspan="7"></td>';
		print "<td class=\"nowrap\" align=\"right\">".price($diff)."</td>\n";
		print "</tr>\n";

		$i++;
	}
	print '<tr class="liste_total">';
	print '<td align="right">'.$langs->trans("Total").':</td>';
	print '<td class="nowrap" align="right">'.price($subtot_coll_total).'</td>';
	print '<td class="nowrap" align="right">'.price($subtot_coll_vat).'</td>';
	print '<td></td>';
	print '<td class="nowrap" align="right">'.price($subtot_paye_total).'</td>';
	print '<td class="nowrap" align="right">'.price($subtot_paye_vat).'</td>';
	print '<td></td>';
	print '<td class="nowrap" align="right"><b>'.price($total).'</b>';
	print '</td>';
	print '</tr>';

}
else
{
	print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	print '<tr><td colspan="5">'.$langs->trans("FeatureIsSupportedInInOutModeOnly").'</td></tr>';
}

print '</table>';
echo '</td></tr>';
echo '</table>';

llxFooter();
$db->close();
