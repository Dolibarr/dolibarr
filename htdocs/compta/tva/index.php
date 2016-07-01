<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014	   Ferran Marcet        <fmarcet@2byte.es>
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
 *	    \file       htdocs/compta/tva/index.php
 *      \ingroup    tax
 *		\brief      Index page of VAT reports
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("other");
$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

$year=GETPOST("year","int");
if ($year == 0)
{
    $year_current = strftime("%Y",time());
    $year_start = $year_current;
} else {
    $year_current = $year;
    $year_start = $year;
}

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit
$modetax = $conf->global->TAX_MODE;
if (isset($_GET["modetax"])) $modetax=$_GET["modetax"];



/**
 * vat_payed
 *
 * @param 	DoliDB	$db		Database handler
 * @param 	string	$year	year to search
 * @return	total_vat_payed
 */
function vat_payed ($db, $year)
{
    global $conf, $bc,$langs;

    $sql = '';
    $sql = "SELECT SUM(amount) as mm, date_format(f.datev,'%Y-%m') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as f";
    $sql.= " WHERE f.entity = ".$conf->entity;
    $sql.= " AND f.datev >= '".$db->idate(dol_get_first_day($year,1,false))."'";
    $sql.= " AND f.datev <= '".$db->idate(dol_get_last_day($year,12,false))."'";
    $sql.= " GROUP BY dm ORDER BY dm ASC";

    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        $total_payed_last_year = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $total_payed_last_year = $total_payed_last_year + $obj->mm;
            $i++;
        }
        
        return $total_payed_last_year;
        $db->free($result);
    }
    else {
        dol_print_error($db);
    }
}



/**
 * vat_to_pay
 *
 * @param 	DoliDB	$db		Database handler
 * @param 	string	$year	year to search
 * @return	total_vat_to_payed
 */
function vat_to_pay ($db, $year)
{

  $diff=0; $x_coll=0; $x_paye=0; $total_last_year=0;   
  $i=0;
  for ($m = 1 ; $m < 13 ; $m++ )
  {
      $coll_listsell = vat_by_date($db, $year, 0, 0, 0, $modetax, 'sell', $m);
      $coll_listbuy = vat_by_date($db, $year, 0, 0, 0, $modetax, 'buy', $m);
    
      if (! is_array($coll_listbuy) && $coll_listbuy == -1)
      {
          $langs->load("errors");
          print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
          break;
      }
      if (! is_array($coll_listbuy) && $coll_listbuy == -2)
      {
          print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
          break;
      }
      $x_coll = 0;
      foreach($coll_listsell as $vatrate=>$val)
      {
          $x_coll+=$val['vat'];
      }
  
      $x_paye = 0;
      foreach($coll_listbuy as $vatrate=>$val)
      {
          $x_paye+=$val['vat'];
      }
  
      $diff = $x_coll - $x_paye;
      $total_last_year = $total_last_year + $diff;
  
  }
  
  return $total_last_year;
}




/**
 * print function
 *
 * @param 	DoliDB	$db		Database handler
 * @param 	string	$sql	SQL Request
 * @param 	string	$date	Date
 * @return	void
 */
function pt ($db, $sql, $date)
{
    global $conf, $bc,$langs;

    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        $total = 0;
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td class="nowrap" width="60%">'.$date.'</td>';
        print '<td align="right">'.$langs->trans("Amount").'</td>';
        print '<td>&nbsp;</td>'."\n";
        print "</tr>\n";
        $var=True;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $var=!$var;
            print '<tr '.$bc[$var].'>';
            print '<td class="nowrap">'.$obj->dm."</td>\n";
            $total = $total + $obj->mm;

            print '<td class="nowrap" align="right">'.price($obj->mm)."</td><td >&nbsp;</td>\n";
            print "</tr>\n";

            $i++;
        }
        print '<tr class="liste_total"><td align="right">'.$langs->trans("Total")." :</td><td class=\"nowrap\" align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>";

        print "</table>";
        $db->free($result);
    }
    else {
        dol_print_error($db);
    }
}


/*
 * View
 */

llxHeader();

$tva = new Tva($db);


$textprevyear="<a href=\"index.php?year=" . ($year_current-1) . "\">".img_previous($langs->trans("Previous"), 'class="valignbottom"')."</a>";
$textnextyear=" <a href=\"index.php?year=" . ($year_current+1) . "\">".img_next($langs->trans("Next"), 'class="valignbottom"')."</a>";

print $conf->dol_optimize_smallscreen;
print load_fiche_titre($langs->trans("VAT"), $textprevyear." ".$langs->trans("Year")." ".$year_start." ".$textnextyear, 'title_accountancy.png');

print $langs->trans("VATReportBuildWithOptionDefinedInModule").'<br>';
print '('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')<br>';
print '<br>';

print '<table width="100%" class="notopnoleftnoright">';
print '<tr><td class="notopnoleft" width="50%">';
print load_fiche_titre($langs->trans("VATSummary"), '', '');
// The report mode is the one defined by defaut in tax module setup
//print $modetax;
//print '('.$langs->trans("SeeVATReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modetax=0">','</a>').')';
print '</td><td>';
print load_fiche_titre($langs->trans("VATPaid"), '', '');
print '</td></tr>';

print '<tr><td class="notopnoleft" width="50%" valign="top">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Year")." ".$y.'</td>';
print '<td align="right">'.$langs->trans("VATToPay").'</td>';
print '<td align="right">'.$langs->trans("VATToCollect").'</td>';
print '<td align="right">'.$langs->trans("TotalToPay").'</td>';
print '<td>&nbsp;</td>'."\n";
print '</tr>'."\n";


$y = $year_current ;


$var=True;



/* Récupération de la TVA reprise des A nouveaux */
/* Recherche l'historique des années tant qu'aucune TVA n'a été payée */
$i=1; $total_payed_last_year=0; $total_last_year=0; $report_a_nouveau=0;
while (vat_to_pay($db, $y-$i) != 0) {
  $total_payed_last_year = $total_payed_last_year+vat_payed($db, $y-$i); 
  $total_last_year=$total_last_year+vat_to_pay($db, $y-$i); 
  $i++;
}
$report_a_nouveau = $total_last_year-$total_payed_last_year;
 





// TVA current year
$total_payed = vat_payed($db, $y); $total=0; $subtotalcoll=0; $subtotalpaye=0; $subtotal=0; $diff=0; $x_coll=0; $x_paye=0;  $vat_sell=0; $vat_buy=0;
$i=0;
for ($m = 1 ; $m < 13 ; $m++ )
{
    $coll_listsell = vat_by_date($db, $y, 0, 0, 0, $modetax, 'sell', $m);
    $coll_listbuy = vat_by_date($db, $y, 0, 0, 0, $modetax, 'buy', $m);
    
    $action = "tva";
    $object = array(&$coll_listsell, &$coll_listbuy);
    $parameters["mode"] = $modetax;
    $parameters["year"] = $y;
    $parameters["month"] = $m;
    $parameters["type"] = 'vat';
    
    // Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
    $hookmanager->initHooks(array('externalbalance'));
    $reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

    if (! is_array($coll_listbuy) && $coll_listbuy == -1)
    {
        $langs->load("errors");
        print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
        break;
    }
    if (! is_array($coll_listbuy) && $coll_listbuy == -2)
    {
        print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
        break;
    }

    $var=!$var;
    print "<tr ".$bc[$var].">";
    print '<td class="nowrap"><a href="quadri_detail.php?leftmenu=tax_vat&month='.$m.'&year='.$y.'">'.dol_print_date(dol_mktime(0,0,0,$m,1,$y),"%b %Y").'</a></td>';

    $x_coll = 0;
    foreach($coll_listsell as $vatrate=>$val)
    {
        $x_coll+=$val['vat'];
    }
    $vat_sell = $vat_sell+$x_coll;
    $subtotalcoll = $subtotalcoll + $x_coll;
    print "<td class=\"nowrap\" align=\"right\">".price($x_coll)."</td>";

    $x_paye = 0;
    foreach($coll_listbuy as $vatrate=>$val)
    {
        $x_paye+=$val['vat'];
    }
    $vat_buy = $vat_buy+$x_paye;
    $subtotalpaye = $subtotalpaye + $x_paye;
    print "<td class=\"nowrap\" align=\"right\">".price($x_paye)."</td>";

    $diff = $x_coll - $x_paye;
    $total = $total + $diff;
    $subtotal = $subtotal + $diff;

    print "<td class=\"nowrap\" align=\"right\">".price($diff)."</td>\n";
    print "<td>&nbsp;</td>\n";
    print "</tr>\n";

    $i++;
    if ($i > 2) {
        print '<tr class="liste_total">';
        print '<td align="right"><a href="quadri_detail.php?leftmenu=tax_vat&q='.($m/3).'&year='.$y.'">'.$langs->trans("SubTotal").'</a>:</td>';
        print '<td class="nowrap" align="right">'.price($subtotalcoll).'</td>';
        print '<td class="nowrap" align="right">'.price($subtotalpaye).'</td>';
        print '<td class="nowrap" align="right">'.price($subtotal).'</td>';
        print '<td>&nbsp;</td></tr>';
        $i = 0;
        $subtotalcoll=0; $subtotalpaye=0; $subtotal=0;
    }
}

//Grand Total
print '<tr class="liste_total">';
print '<td align="right">'.$langs->trans("Grand Total").':</td>';
print '<td class="nowrap" align="right">'.price($vat_sell).'</td>';
print '<td class="nowrap" align="right">'.price($vat_buy).'</td>';
print '<td class="nowrap" align="right">'.price($total).'</td>';
print '<td>&nbsp;</td></tr>';

//Repport des A nouveaux
print '<tr class="liste_total"><td align="right" colspan="3">'.utf8_encode($langs->trans("Repport")).' '.($y-1).':</td><td class="nowrap" align="right">'.price($report_a_nouveau).'</td>';
print "<td>&nbsp;</td>\n";
print '</tr>';

//D‚ductible ann‚e en cours
print '<tr class="liste_total"><td align="right" colspan="3">'.utf8_encode($langs->trans("TVA déductible")).' '.($y).':</td><td class="nowrap" align="right">'.price(-$vat_buy).'</td>';
print "<td>&nbsp;</td>\n";
print '</tr>';
  
//Total d‚ductible
print '<tr class="liste_total">';
print '<td align="right">'.$langs->trans("SubTotal").':</td>';
print '<td class="nowrap" align="right">&nbsp;</td>';
print '<td class="nowrap" align="right">&nbsp;</td>';
print '<td class="nowrap" align="right">'.price($report_a_nouveau-$vat_buy).'</td>';
print '<td>&nbsp;</td></tr>';

//Deja payé
print '<tr class="liste_total"><td align="right" colspan="3">'.utf8_encode($langs->trans("Payé en")).' '.$y.':</td><td class="nowrap" align="right">'.price(-$total_payed).'</td>';
print "<td>&nbsp;</td>\n";
print '</tr>';

//Reste a payer
print '<tr class="liste_total">';
print '<td align="right">'.$langs->trans("TotalToPay").':</td>';
print '<td class="nowrap" align="right">&nbsp;</td>';
print '<td class="nowrap" align="right">&nbsp;</td>';
print '<td class="nowrap" align="right">'.price($total+$total_last_year-$total_payed-$total_payed_last_year).'</td>';
print '<td>&nbsp;</td></tr>';




print '</table>';


print '</td>';
print '<td class="notopnoleftnoright" valign="top" width="50%">';

/*
 * Payed
 */
$sql = '';
$sql = "SELECT SUM(amount) as mm, date_format(f.datev,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."tva as f";
$sql.= " WHERE f.entity = ".$conf->entity;
$sql.= " AND f.datev >= '".$db->idate(dol_get_first_day($y,1,false))."'";
$sql.= " AND f.datev <= '".$db->idate(dol_get_last_day($y,12,false))."'";
$sql.= " GROUP BY dm ORDER BY dm ASC";

pt($db, $sql,$langs->trans("Year")." $y");


print "</td></tr></table>";

print '</td></tr>';
print '</table>';

llxFooter();
$db->close();
