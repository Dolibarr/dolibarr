<?php
/* Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/localtax/index.php
 *      \ingroup    tax
 *      \brief      Index page of IRPF reports
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';

$langs->loadLangs(array("other","compta","banks","bills","companies","product","trips","admin"));

$localTaxType=GETPOST('localTaxType', 'int');

// Date range
$year=GETPOST("year","int");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,GETPOST("date_startmonth"),GETPOST("date_startday"),GETPOST("date_startyear"));
$date_end=dol_mktime(23,59,59,GETPOST("date_endmonth"),GETPOST("date_endday"),GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q");
	if (empty($q))
	{
		if (GETPOST("month")) { $date_start=dol_get_first_day($year_start,GETPOST("month"),false); $date_end=dol_get_last_day($year_start,GETPOST("month"),false); }
		else
		{
			$date_start=dol_get_first_day($year_start, $conf->global->SOCIETE_FISCAL_MONTH_START,false);
			$date_end=dol_time_plus_duree($date_start, 1, 'y') - 1;
		}
	}
	else
	{
		if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
		if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
		if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
		if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
	}
}

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = $conf->global->TAX_MODE;
if (GETPOSTISSET("modetax")) $modetax=GETPOST("modetax",'int');
if (empty($modetax)) $modetax=0;

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');


/**
 * print function
 *
 * @param   DoliDB $db          Database handler
 * @param   string $sql     	SQL Request
 * @param   string $date    	Date
 * @return  void
 */
function pt ($db, $sql, $date)
{
    global $conf, $bc,$langs;

    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        $total = 0;
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td class="nowrap" width="60%">'.$date.'</td>';
        print '<td align="right">'.$langs->trans("Amount").'</td>';
        print '<td>&nbsp;</td>'."\n";
        print "</tr>\n";

        while ($i < $num) {
            $obj = $db->fetch_object($result);

            print '<tr class="oddeven">';
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

$form=new Form($db);
$company_static=new Societe($db);
$tva = new Tva($db);

if($localTaxType==1) {
	$LT='LT1';
	$LTSummary='LT1Summary';
	$LTPaid='LT1Paid';
	$LTCustomer='LT1Customer';
	$LTSupplier='LT1Supplier';
	$CalcLT= $conf->global->MAIN_INFO_LOCALTAX_CALC1;
} else {
	$LT='LT2';
	$LTSummary='LT2Summary';
	$LTPaid='LT2Paid';
	$LTCustomer='LT2Customer';
	$LTSupplier='LT2Supplier';
	$CalcLT= $conf->global->MAIN_INFO_LOCALTAX_CALC2;
}

$description = '';

// Show report header
$name = $langs->trans("ReportByMonth");
$description = $langs->trans($LT);
$calcmode = $langs->trans("LTReportBuildWithOptionDefinedInModule").' ';
$calcmode.= '('.$langs->trans("TaxModuleSetupToModifyRulesLT",DOL_URL_ROOT.'/admin/company.php').')<br>';

//if (! empty($conf->global->MAIN_MODULE_ACCOUNTING)) $description.='<br>'.$langs->trans("ThisIsAnEstimatedValue");

$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);

$builddate=dol_now();


llxHeader('', $name);

//$textprevyear="<a href=\"index.php?localTaxType=".$localTaxType."&year=" . ($year_current-1) . "\">".img_previous()."</a>";
//$textnextyear=" <a href=\"index.php?localTaxType=".$localTaxType."&year=" . ($year_current+1) . "\">".img_next()."</a>";
//print load_fiche_titre($langs->transcountry($LT,$mysoc->country_code),"$textprevyear ".$langs->trans("Year")." $year_start $textnextyear", 'title_accountancy.png');

report_header($name,'',$period,$periodlink,$description,$builddate,$exportlink,array(),$calcmode);
//report_header($name,'',$textprevyear.$langs->trans("Year")." ".$year_start.$textnextyear,'',$description,$builddate,$exportlink,array(),$calcmode);

print '<br>';

print '<div class="fichecenter"><div class="fichethirdleft">';

print load_fiche_titre($langs->transcountry($LTSummary,$mysoc->country_code), '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Year")." ".$y."</td>";
if($CalcLT==0) {
    print "<td align=\"right\">".$langs->transcountry($LTCustomer,$mysoc->country_code)."</td>";
    print "<td align=\"right\">".$langs->transcountry($LTSupplier,$mysoc->country_code)."</td>";
}
if($CalcLT==1) {
    print "<td align=\"right\">".$langs->transcountry($LTSupplier,$mysoc->country_code)."</td><td></td>";
}
if($CalcLT==2) {
    print "<td align=\"right\">".$langs->transcountry($LTCustomer,$mysoc->country_code)."</td><td></td>";
}
print "<td align=\"right\">".$langs->trans("TotalToPay")."</td>";
print "<td>&nbsp;</td>\n";
print "</tr>\n";

$tmp=dol_getdate($date_start);
$y = $tmp['year'];
$m = $tmp['mon'];
$tmp=dol_getdate($date_end);
$yend = $tmp['year'];
$mend = $tmp['mon'];

$total=0; $subtotalcoll=0; $subtotalpaye=0; $subtotal=0;
$i=0; $mcursor=0;
while ((($y < $yend) || ($y == $yend && $m < $mend)) && $mcursor < 1000)	// $mcursor is to avoid too large loop
{
	$m = $conf->global->SOCIETE_FISCAL_MONTH_START + ($mcursor % 12);
	if ($m == 13) $y++;
	if ($m > 12) $m -= 12;
	$mcursor++;

	$coll_listsell = tax_by_date('vat', $db, $y, 0, 0, 0, $modetax, 'sell', $m);
	$coll_listbuy = tax_by_date('vat', $db, $y, 0, 0, 0, $modetax, 'buy', $m);

    $action = "tva";
    $object = array(&$coll_listsell, &$coll_listbuy);
    $parameters["mode"] = $modetax;
    $parameters["year"] = $y;
    $parameters["month"] = $m;
    $parameters["type"] = 'localtax'.$localTaxType;

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


    print '<tr class="oddeven">';
    print '<td class="nowrap"><a href="quadri_detail.php?leftmenu=tax_vat&month='.$m.'&year='.$y.'">'.dol_print_date(dol_mktime(0,0,0,$m,1,$y),"%b %Y").'</a></td>';
    
    if ($CalcLT==0) {
        $x_coll = 0;
        foreach($coll_listsell as $vatrate=>$val) {
	        $x_coll+=$val[$localTaxType==1?'localtax1':'localtax2'];
	    }
	    $subtotalcoll = $subtotalcoll + $x_coll;
	    print "<td class=\"nowrap\" align=\"right\">".price($x_coll)."</td>";

	    $x_paye = 0;
	    foreach($coll_listbuy as $vatrate=>$val) {
	        $x_paye+=$val[$localTaxType==1?'localtax1':'localtax2'];
	    }
	    $subtotalpaye = $subtotalpaye + $x_paye;
	    print "<td class=\"nowrap\" align=\"right\">".price($x_paye)."</td>";
    } elseif($CalcLT==1) {
    	$x_paye = 0;
    	foreach($coll_listbuy as $vatrate=>$val) {
    		$x_paye+=$val[$localTaxType==1?'localtax1':'localtax2'];
    	}
    	$subtotalpaye = $subtotalpaye + $x_paye;
    	print "<td class=\"nowrap\" align=\"right\">".price($x_paye)."</td><td></td>";
    } elseif($CalcLT==2) {
    	$x_coll = 0;
    	foreach($coll_listsell as $vatrate=>$val) {
    		$x_coll+=$val[$localTaxType==1?'localtax1':'localtax2'];
    	}
    	$subtotalcoll = $subtotalcoll + $x_coll;
    	print "<td class=\"nowrap\" align=\"right\">".price($x_coll)."</td><td></td>";

    }

    if($CalcLT==0) {
        $diff= $x_coll - $x_paye;
    } elseif($CalcLT==1) {
        $diff= $x_paye;
    } elseif($CalcLT==2) {
        $diff= $x_coll;
    }

    $total = $total + $diff;
    $subtotal = $subtotal + $diff;

    print "<td class=\"nowrap\" align=\"right\">".price($diff)."</td>\n";
    print "<td>&nbsp;</td>\n";
    print "</tr>\n";

    $i++;
    if ($i > 2)
    {
        print '<tr class="liste_total">';
        print '<td align="right">'.$langs->trans("SubTotal").':</td>';
        if($CalcLT==0) {
        	print '<td class="nowrap" align="right">'.price($subtotalcoll).'</td>';
        	print '<td class="nowrap" align="right">'.price($subtotalpaye).'</td>';
        	print '<td class="nowrap" align="right">'.price($subtotal).'</td>';
        } elseif($CalcLT==1) {
        	print '<td class="nowrap" align="right">'.price($subtotalpaye).'</td><td></td>';
        	print '<td class="nowrap" align="right">'.price($subtotal).'</td>';
        } elseif($CalcLT==2) {
        	print '<td class="nowrap" align="right">'.price($subtotalcoll).'</td><td></td>';
        	print '<td class="nowrap" align="right">'.price($subtotal).'</td>';
        }
        print '<td>&nbsp;</td></tr>';
        $i = 0;
        $subtotalcoll=0; $subtotalpaye=0; $subtotal=0;
    }
}
print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("TotalToPay").':</td><td class="nowrap" align="right">'.price($total).'</td>';
print "<td>&nbsp;</td>\n";
print '</tr>';

print '</table>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

print load_fiche_titre($langs->transcountry($LTPaid,$mysoc->country_code), '', '');

/*
 * Payed
 */

$sql = "SELECT SUM(amount) as mm, date_format(f.datev,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."localtax as f";
$sql.= " WHERE f.entity = ".$conf->entity;
$sql.= " AND f.datev >= '".$db->idate($date_start)."'";
$sql.= " AND f.datev <= '".$db->idate($date_end)."'";
$sql.= " AND localtaxtype=".$localTaxType;
$sql.= " GROUP BY dm";
$sql.= " ORDER BY dm ASC";

pt($db, $sql,$langs->trans("Year")." $y");

print '<br>';

print '</div></div>';


llxFooter();
$db->close();
