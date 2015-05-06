<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014 Raphaël Dourseanud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014	   Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2014	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014	   Florian Henry        <florian.henry@open-concept.pro>
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
 *  \file        htdocs/compta/resultat/clientfourn.php
 *	\brief       Page reporting
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


$langs->load("bills");
$langs->load("donation");
$langs->load("salaries");

$date_startmonth=GETPOST('date_startmonth');
$date_startday=GETPOST('date_startday');
$date_startyear=GETPOST('date_startyear');
$date_endmonth=GETPOST('date_endmonth');
$date_endday=GETPOST('date_endday');
$date_endyear=GETPOST('date_endyear');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');

// Date range
$year=GETPOST("year");
if (empty($year))
{
    $year_current = strftime("%Y",dol_now());
    $month_current = strftime("%m",dol_now());
    $year_start = $year_current;
} else {
    $year_current = $year;
    $month_current = strftime("%m",dol_now());
    $year_start = $year;
}
$date_start=dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end=dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
    $q=GETPOST("q")?GETPOST("q"):0;
    if ($q==0)
    {
        // We define date_start and date_end
        $year_end=$year_start;
        $month_start=GETPOST("month")?GETPOST("month"):($conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START):1);
        if (! GETPOST('month'))
        {
            if (! GETPOST("year") &&  $month_start > $month_current)
            {
                $year_start--;
                $year_end--;
            }
            $month_end=$month_start-1;
            if ($month_end < 1) $month_end=12;
            else $year_end++;
        }
        else $month_end=$month_start;
        $date_start=dol_get_first_day($year_start,$month_start,false); $date_end=dol_get_last_day($year_end,$month_end,false);
    }
    if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
    if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
    if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
    if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
}
else
{
    // TODO We define q

}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta=(GETPOST("modecompta")?GETPOST("modecompta"):$conf->global->ACCOUNTING_MODE);


/*
 * View
 */

llxHeader();

$form=new Form($db);

$nomlink='';
$periodlink='';
$exportlink='';

$total_ht=0;
$total_ttc=0;

// Affiche en-tete de rapport
if ($modecompta=="CREANCES-DETTES")
{
    $name=$langs->trans("AnnualByCompaniesDueDebtMode");
	$calcmode=$langs->trans("CalcModeDebt");
    $calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");
}
else {
    $name=$langs->trans("AnnualByCompaniesInputOutputMode");
	$calcmode=$langs->trans("CalcModeEngagement");
    $calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=CREANCES-DETTES">','</a>').')';
    //$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',1,1,0,'',1,0,1);
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesResultInOut");
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");
}
report_header($name,$nomlink,$period,$periodlink,$description,$builddate,$exportlink,array('modecompta'=>$modecompta),$calcmode);

// Show report array
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="10%">&nbsp;</td><td>&nbsp;</td>';
if ($modecompta == 'CREANCES-DETTES')
	print "<td align=\"right\">".$langs->trans("AmountHT")."</td>";
print "<td align=\"right\">".$langs->trans("AmountTTC")."</td>";
print "</tr>\n";

/*
 * Factures clients
 */
print '<tr><td colspan="4">'.$langs->trans("CustomersInvoices").'</td></tr>';

if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT s.nom as name, s.rowid as socid, sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture as f";
    $sql.= " WHERE f.fk_soc = s.rowid";
    $sql.= " AND f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
    	$sql.= " AND f.type IN (0,1,2,5)";
	else
		$sql.= " AND f.type IN (0,1,2,3,5)";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
else
{
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
     */
    $sql = "SELECT s.nom as name, s.rowid as socid, sum(pf.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture as f";
    $sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " WHERE p.rowid = pf.fk_paiement";
    $sql.= " AND pf.fk_facture = f.rowid";
    $sql.= " AND f.fk_soc = s.rowid";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY s.nom, s.rowid";
$sql.= " ORDER BY s.nom, s.rowid";

dol_syslog("get customer invoices", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $i = 0;
    $var=true;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $var=!$var;

        print "<tr ".$bc[$var]."><td>&nbsp;</td>";
        print "<td>".$langs->trans("Bills").' <a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'">'.$objp->name."</td>\n";

        if ($modecompta == 'CREANCES-DETTES')
        	print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
        print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";

        $total_ht += (isset($objp->amount_ht)?$objp->amount_ht:0);
        $total_ttc += $objp->amount_ttc;
        print "</tr>\n";
        $i++;
    }
    $db->free($result);
} else {
    dol_print_error($db);
}

// On ajoute les paiements clients anciennes version, non lie par paiement_facture
if ($modecompta != 'CREANCES-DETTES')
{
    $sql = "SELECT 'Autres' as name, '0' as idp, sum(p.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
    $sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE pf.rowid IS NULL";
    $sql.= " AND p.fk_bank = b.rowid";
    $sql.= " AND b.fk_account = ba.rowid";
    $sql.= " AND ba.entity = ".$conf->entity;
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
    $sql.= " GROUP BY name, idp";
    $sql.= " ORDER BY name";

    dol_syslog("get old customer payments not linked to invoices", LOG_DEBUG);
    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        if ($num) {
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);
                $var=!$var;

                print "<tr ".$bc[$var]."><td>&nbsp;</td>";
                print "<td>".$langs->trans("Bills")." ".$langs->trans("Other")." (".$langs->trans("PaymentsNotLinkedToInvoice").")\n";

                if ($modecompta == 'CREANCES-DETTES')
                	print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
                print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";

                $total_ht += (isset($objp->amount_ht)?$objp->amount_ht:0);
                $total_ttc += $objp->amount_ttc;

                print "</tr>\n";
                $i++;
            }
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }
}

if ($total_ttc == 0)
{
    $var=!$var;
    print "<tr ".$bc[$var]."><td>&nbsp;</td>";
    print '<td colspan="3">'.$langs->trans("None").'</td>';
    print '</tr>';
}

print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES')
	print '<td colspan="3" align="right">'.price($total_ht).'</td>';
print '<td colspan="3" align="right">'.price($total_ttc).'</td>';
print '</tr>';


/*
 * Suppliers invoices
 */
if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT s.nom as name, s.rowid as socid, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql.= " WHERE f.fk_soc = s.rowid";
    $sql.= " AND f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
    	$sql.= " AND f.type IN (0,1,2)";
	else
		$sql.= " AND f.type IN (0,1,2,3)";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
else
{
    $sql = "SELECT s.nom, s.rowid as socid, sum(pf.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
    $sql.= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql.= " ON pf.fk_facturefourn = f.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
    $sql.= " ON f.fk_soc = s.rowid";
    $sql.= " WHERE p.rowid = pf.fk_paiementfourn ";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql .= " GROUP BY s.nom, s.rowid";
$sql .= " ORDER BY s.nom, s.rowid";

print '<tr><td colspan="4">'.$langs->trans("SuppliersInvoices").'</td></tr>';

$subtotal_ht = 0;
$subtotal_ttc = 0;
dol_syslog("get suppliers invoices", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $i = 0;
    $var=true;
    if ($num > 0)
    {
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;

            print "<tr ".$bc[$var]."><td>&nbsp;</td>";
            print "<td>".$langs->trans("Bills")." <a href=\"".DOL_URL_ROOT."/fourn/facture/list.php?socid=".$objp->socid."\">".$objp->name."</a></td>\n";

            if ($modecompta == 'CREANCES-DETTES')
            	print "<td align=\"right\">".price(-$objp->amount_ht)."</td>\n";
            print "<td align=\"right\">".price(-$objp->amount_ttc)."</td>\n";

            $total_ht -= (isset($objp->amount_ht)?$objp->amount_ht:0);
            $total_ttc -= $objp->amount_ttc;
            $subtotal_ht += (isset($objp->amount_ht)?$objp->amount_ht:0);
            $subtotal_ttc += $objp->amount_ttc;

            print "</tr>\n";
            $i++;
        }
    }
    else
    {
        $var=!$var;
        print "<tr ".$bc[$var]."><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }

    $db->free($result);
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES')
	print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';



/*
 * Charges sociales non deductibles
 */

print '<tr><td colspan="4">'.$langs->trans("SocialContributions").' ('.$langs->trans("Type").' 0)</td></tr>';

if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT c.id, c.libelle as label, sum(cs.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
    $sql.= " WHERE cs.fk_type = c.id";
    $sql.= " AND c.deductible = 0";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND cs.date_ech >= '".$db->idate($date_start)."' AND cs.date_ech <= '".$db->idate($date_end)."'";
}
else
{
    $sql = "SELECT c.id, c.libelle as label, sum(p.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
    $sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql.= " WHERE p.fk_charge = cs.rowid";
    $sql.= " AND cs.fk_type = c.id";
    $sql.= " AND c.deductible = 0";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND cs.entity = ".$conf->entity;
$sql.= " GROUP BY c.libelle, c.id";
$sql.= " ORDER BY c.libelle, c.id";

dol_syslog("get social contributions deductible=0", LOG_DEBUG);
$result=$db->query($sql);
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($result) {
    $num = $db->num_rows($result);
    $var=true;
    $i = 0;
    if ($num) {
        while ($i < $num) {
            $obj = $db->fetch_object($result);

            $total_ht -= $obj->amount;
            $total_ttc -= $obj->amount;
            $subtotal_ht += $obj->amount;
            $subtotal_ttc += $obj->amount;

            $var = !$var;
            print "<tr ".$bc[$var]."><td>&nbsp;</td>";
            print '<td>'.$obj->label.'</td>';
            if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price(-$obj->amount).'</td>';
            print '<td align="right">'.price(-$obj->amount).'</td>';
            print '</tr>';
            $i++;
        }
    }
    else {
        $var = !$var;
        print "<tr ".$bc[$var]."><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES')
	print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';


/*
 * Charges sociales deductibles
 */

print '<tr><td colspan="4">'.$langs->trans("SocialContributions").' ('.$langs->trans("Type").' 1)</td></tr>';

if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT c.id, c.libelle as label, sum(cs.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
    $sql.= " WHERE cs.fk_type = c.id";
    $sql.= " AND c.deductible = 1";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND cs.date_ech >= '".$db->idate($date_start)."' AND cs.date_ech <= '".$db->idate($date_end)."'";
    $sql.= " AND cs.entity = ".$conf->entity;
    $sql.= " GROUP BY c.libelle, c.id";
    $sql.= " ORDER BY c.libelle, c.id";
}
else
{
    $sql = "SELECT c.id, c.libelle as label, sum(p.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
    $sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql.= " WHERE p.fk_charge = cs.rowid";
    $sql.= " AND cs.fk_type = c.id";
    $sql.= " AND c.deductible = 1";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
    $sql.= " AND cs.entity = ".$conf->entity;
    $sql.= " GROUP BY c.libelle, c.id";
    $sql.= " ORDER BY c.libelle, c.id";
}

dol_syslog("get social contributions deductible=1", LOG_DEBUG);
$result=$db->query($sql);
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($result) {
    $num = $db->num_rows($result);
    $var=true;
    $i = 0;
    if ($num) {
        while ($i < $num) {
            $obj = $db->fetch_object($result);

            $total_ht -= $obj->amount;
            $total_ttc -= $obj->amount;
            $subtotal_ht += $obj->amount;
            $subtotal_ttc += $obj->amount;

            $var = !$var;
            print "<tr ".$bc[$var]."><td>&nbsp;</td>";
            print '<td>'.$obj->label.'</td>';
            if ($modecompta == 'CREANCES-DETTES')
            	print '<td align="right">'.price(-$obj->amount).'</td>';
            print '<td align="right">'.price(-$obj->amount).'</td>';
            print '</tr>';
            $i++;
        }
    }
    else {
        $var = !$var;
        print "<tr ".$bc[$var]."><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES')
	print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';

if ($mysoc->tva_assuj == 'franchise')	// Non assujeti
{
    // Total
    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
    print '</tr>';

    print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Profit").'</td>';
    if ($modecompta == 'CREANCES-DETTES')
    	print '<td class="border" align="right">'.price($total_ht).'</td>';
    print '<td align="right">'.price($total_ttc).'</td>';
    print '</tr>';

    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
    print '</tr>';
}


/*
 * Salaries
 */

if ($conf->salaries->enabled)
{
	print '<tr><td colspan="4">'.$langs->trans("Salaries").'</td></tr>';
	$sql = "SELECT u.rowid, u.firstname, u.lastname, p.fk_user, p.label as label, date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user";
	$sql.= " WHERE p.entity = ".$conf->entity;
	if (! empty($date_start) && ! empty($date_end))
		$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	$sql.= " GROUP BY u.rowid, u.firstname, u.lastname, p.fk_user, p.label, dm";
	$sql.= " ORDER BY u.firstname";

	dol_syslog("get payment salaries");
	$result=$db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result)
	{
	    $num = $db->num_rows($result);
	    $var=true;
	    $i = 0;
	    if ($num)
	    {
	        while ($i < $num)
	        {
	            $obj = $db->fetch_object($result);

	            $total_ht -= $obj->amount;
	            $total_ttc -= $obj->amount;
	            $subtotal_ht += $obj->amount;
	            $subtotal_ttc += $obj->amount;

	            $var = !$var;
	            print "<tr ".$bc[$var]."><td>&nbsp;</td>";

	            print "<td>".$langs->trans("Salaries")." <a href=\"".DOL_URL_ROOT."/compta/salaries/index.php?filtre=s.fk_user=".$obj->fk_user."\">".$obj->firstname." ".$obj->lastname."</a></td>\n";

	            if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price(-$obj->amount).'</td>';
	            print '<td align="right">'.price(-$obj->amount).'</td>';
	            print '</tr>';
	            $i++;
	        }
	    }
	    else
	    {
	        $var = !$var;
	        print "<tr ".$bc[$var]."><td>&nbsp;</td>";
	        print '<td colspan="3">'.$langs->trans("None").'</td>';
	        print '</tr>';
	    }
	}
	else
	{
	    dol_print_error($db);
	}
	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES')
		print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
	print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';
}


/*
 * Donation
 */

if ($conf->donation->enabled)
{
	print '<tr><td colspan="4">'.$langs->trans("Donation").'</td></tr>';
	$sql = "SELECT p.societe as name, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."don as p";
	$sql.= " WHERE p.entity = ".$conf->entity;
	$sql.= " AND fk_statut=2";
	if (! empty($date_start) && ! empty($date_end))
		$sql.= " AND p.datedon >= '".$db->idate($date_start)."' AND p.datedon <= '".$db->idate($date_end)."'";
	$sql.= " GROUP BY p.societe, p.firstname, p.lastname, dm";
	$sql.= " ORDER BY p.societe, p.firstname, p.lastname, dm";

	dol_syslog("get dunning");
	$result=$db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result)
	{
		$num = $db->num_rows($result);
		$var=true;
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);

				$total_ht += $obj->amount;
				$total_ttc += $obj->amount;
				$subtotal_ht += $obj->amount;
				$subtotal_ttc += $obj->amount;

				$var = !$var;
				print "<tr ".$bc[$var]."><td>&nbsp;</td>";

				print "<td>".$langs->trans("Donation")." <a href=\"".DOL_URL_ROOT."/don/list.php?search_company=".$obj->name."&search_name=".$obj->firstname." ".$obj->lastname."\">".$obj->name. " ".$obj->firstname." ".$obj->lastname."</a></td>\n";

				if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price($obj->amount).'</td>';
				print '<td align="right">'.price($obj->amount).'</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			$var = !$var;
			print "<tr ".$bc[$var]."><td>&nbsp;</td>";
			print '<td colspan="3">'.$langs->trans("None").'</td>';
			print '</tr>';
		}
	}
	else
	{
		dol_print_error($db);
	}
	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES')
		print '<td colspan="3" align="right">'.price($subtotal_ht).'</td>';
	print '<td colspan="3" align="right">'.price($subtotal_ttc).'</td>';
	print '</tr>';
}

/*
 * VAT
 */

print '<tr><td colspan="4">'.$langs->trans("VAT").'</td></tr>';
$subtotal_ht = 0;
$subtotal_ttc = 0;

if ($modecompta == 'CREANCES-DETTES')
{
    // TVA a payer
    $amount=0;
    $sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.tva) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql.= " WHERE f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
    	$sql.= " AND f.type IN (0,1,2,5)";
	else
		$sql.= " AND f.type IN (0,1,2,3,5)";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm";

    dol_syslog("get vat to pay", LOG_DEBUG);
    $result=$db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                $amount -= $obj->amount;
                //$total_ht -= $obj->amount;
                $total_ttc -= $obj->amount;
                //$subtotal_ht -= $obj->amount;
                $subtotal_ttc -= $obj->amount;
                $i++;
            }
        }
    } else {
        dol_print_error($db);
    }
    print "<tr ".$bc[$var]."><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATToPay")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";

    // TVA a recuperer
    $amount=0;
    $sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.total_tva) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql.= " WHERE f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
    	$sql.= " AND f.type IN (0,1,2)";
	else
		$sql.= " AND f.type IN (0,1,2,3)";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm";

    dol_syslog("get vat received back", LOG_DEBUG);
    $result=$db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $var=true;
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                $amount += $obj->amount;
                //$total_ht += $obj->amount;
                $total_ttc += $obj->amount;
                //$subtotal_ht += $obj->amount;
                $subtotal_ttc += $obj->amount;

                $i++;
            }
        }
    } else {
        dol_print_error($db);
    }
    print "<tr ".$bc[$var]."><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATToCollect")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";
}
else
{
    // VAT really already paid
    $amount=0;
    $sql = "SELECT date_format(t.datev,'%Y-%m') as dm, sum(t.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql.= " WHERE amount > 0";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
    $sql.= " AND t.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm";

    dol_syslog("get vat really paid", LOG_DEBUG);
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $amount -= $obj->amount;
                $total_ht -= $obj->amount;
                $total_ttc -= $obj->amount;
                $subtotal_ht -= $obj->amount;
                $subtotal_ttc -= $obj->amount;

                $i++;
            }
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }
    print "<tr ".$bc[$var]."><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATPaid")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES')
    	print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";

    // VAT really received
    $amount=0;
    $sql = "SELECT date_format(t.datev,'%Y-%m') as dm, sum(t.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql.= " WHERE amount < 0";
    if (! empty($date_start) && ! empty($date_end))
    	$sql.= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
    $sql.= " AND t.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm";

    dol_syslog("get vat really received back", LOG_DEBUG);
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=true;
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $amount += $obj->amount;
                $total_ht += $obj->amount;
                $total_ttc += $obj->amount;
                $subtotal_ht += $obj->amount;
                $subtotal_ttc += $obj->amount;

                $i++;
            }
        }
        $db->free($result);
    }
    else
    {
        dol_print_error($db);
    }
    print "<tr ".$bc[$var]."><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATCollected")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES')
    	print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";
}


if ($mysoc->tva_assuj != 'franchise')	// Assujeti
{
    print '<tr class="liste_total">';
    if ($modecompta == 'CREANCES-DETTES')
    	print '<td colspan="3" align="right">&nbsp;</td>';
    print '<td colspan="3" align="right">'.price(price2num($subtotal_ttc,'MT')).'</td>';
    print '</tr>';
}

$action = "balanceclient";
$object = array(&$total_ht, &$total_ttc);
$parameters["mode"] = $modecompta;
$parameters["date_start"] = $date_start;
$parameters["date_end"] = $date_end;
$parameters["bc"] = $bc;
// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook=$hookmanager->executeHooks('addStatisticLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
print $hookmanager->resPrint;

if ($mysoc->tva_assuj != 'franchise')	// Assujeti
{
    // Total
    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
    print '</tr>';

    print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Profit").'</td>';
    if ($modecompta == 'CREANCES-DETTES')
    	print '<td class="liste_total" align="right">'.price(price2num($total_ht,'MT')).'</td>';
    print '<td class="liste_total" align="right">'.price(price2num($total_ttc,'MT')).'</td>';
    print '</tr>';
}

print "</table>";
print '<br>';

llxFooter();

$db->close();
