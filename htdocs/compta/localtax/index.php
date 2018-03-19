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
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->loadLangs(array("other","compta","banks","bills","companies"));

$localTaxType=GETPOST('localTaxType', 'int');

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
if (isset($_GET["modetax"])) $modetax=GETPOST("modetax",'alpha');


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


$name = $langs->trans("ReportByMonth");
$description = $langs->trans($LT);
$calcmode = $langs->trans("LTReportBuildWithOptionDefinedInModule").' ';
$calcmode.= '('.$langs->trans("TaxModuleSetupToModifyRulesLT",DOL_URL_ROOT.'/admin/company.php').')<br>';
$builddate=dol_now();

llxHeader('', $name);

$textprevyear="<a href=\"index.php?localTaxType=".$localTaxType."&year=" . ($year_current-1) . "\">".img_previous()."</a>";
$textnextyear=" <a href=\"index.php?localTaxType=".$localTaxType."&year=" . ($year_current+1) . "\">".img_next()."</a>";

//print load_fiche_titre($langs->transcountry($LT,$mysoc->country_code),"$textprevyear ".$langs->trans("Year")." $year_start $textnextyear", 'title_accountancy.png');

report_header($name,'',$textprevyear.$langs->trans("Year")." ".$year_start.$textnextyear,'',$description,$builddate,$exportlink,array(),$calcmode);

print '<br>';

//print load_fiche_titre($langs->trans("Summary"), '', '');

print '<table width="100%" class="notopnoleftnoright">';
print '<tr><td class="notopnoleft width="50%">';
print load_fiche_titre($langs->transcountry($LTSummary,$mysoc->country_code), '', '');
print '</td><td>&nbsp;</td><td>';
print load_fiche_titre($langs->transcountry($LTPaid,$mysoc->country_code), '', '');
print '</td></tr>';

print '<tr><td class="notopnoleft" width="50%" valign="top">';

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

$y = $year_current ;

$total=0; $subtotalcoll=0; $subtotalpaye=0; $subtotal=0;
$i=0;
for ($m = 1 ; $m < 13 ; $m++ ) {
    $coll_listsell = vat_by_date($db, $y, 0, 0, 0, $modetax, 'sell', $m);
    $coll_listbuy = vat_by_date($db, $y, 0, 0, 0, $modetax, 'buy', $m);
    
    $action = "tva";
    $object = array(&$coll_listsell, &$coll_listbuy);
    $parameters["mode"] = $modetax;
    $parameters["year"] = $y;
    $parameters["month"] = $m;
    $parameters["type"] = 'localtax'.$localTaxType;
    
    // Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
    $hookmanager->initHooks(array('externalbalance'));
    $reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

    if (! is_array($coll_listbuy) && $coll_listbuy == -1) {
        $langs->load("errors");
        print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
        break;
    }
    if (! is_array($coll_listbuy) && $coll_listbuy == -2) {
        print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
        break;
    }

    
    print '<tr class="oddeven">';
    print '<td class="nowrap">'.dol_print_date(dol_mktime(0,0,0,$m,1,$y),"%b %Y").'</td>';
    if($CalcLT==0) {
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
    if ($i > 2) {
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

print '</td><td>&nbsp;</td><td valign="top" width="50%">';

/*
 * Payed
 */

$sql = "SELECT SUM(amount) as mm, date_format(f.datev,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."localtax as f";
$sql.= " WHERE f.entity = ".$conf->entity;
$sql.= " AND f.datev >= '".$db->idate(dol_get_first_day($y,1,false))."'";
$sql.= " AND f.datev <= '".$db->idate(dol_get_last_day($y,12,false))."'";
$sql.= " AND localtaxtype=".$localTaxType;
$sql.= " GROUP BY dm";
$sql.= " ORDER BY dm ASC";

pt($db, $sql,$langs->trans("Year")." $y");

print '</td></tr></table>';

print '</td></tr>';
print '</table>';

llxFooter();
$db->close();
