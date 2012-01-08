<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file        htdocs/compta/resultat/clientfourn.php
 *	\brief       Page reporting
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/compta/tva/class/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/sociales/class/chargesociales.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/tax.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");


$langs->load("bills");

// Security check
$socid = GETPOST("socid");
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->compta->resultat->lire && !$user->rights->accounting->comptarapport->lire) accessforbidden();

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
$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);	// Date for local PHP server
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
$modecompta = $conf->global->COMPTA_MODE;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];



/*
 * View
 */

llxHeader();

$form=new Form($db);

// Affiche en-tete de rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("AnnualByCompaniesDueDebtMode");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("AnnualByCompaniesInputOutputMode");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=CREANCES-DETTES">','</a>').')';
    //$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',1,1,0,'',1,0,1);
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesResultInOut");
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);

// Show report array
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="10%">&nbsp;</td><td>&nbsp;</td>';
if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".$langs->trans("AmountHT")."</td>";
print "<td align=\"right\">".$langs->trans("AmountTTC")."</td>";
print "</tr>\n";

/*
 * Factures clients
 */
print '<tr><td colspan="4">'.$langs->trans("CustomersInvoices").'</td></tr>';

if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT s.nom, s.rowid as socid, sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture as f";
    $sql.= " WHERE f.fk_soc = s.rowid";
    $sql.= " AND f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	else $sql.= " AND f.type IN (0,1,2,3)";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
else
{
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
     */
    $sql = "SELECT s.nom as nom, s.rowid as socid, sum(pf.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture as f";
    $sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " WHERE p.rowid = pf.fk_paiement";
    $sql.= " AND pf.fk_facture = f.rowid";
    $sql.= " AND f.fk_soc = s.rowid";
    if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY s.nom, s.rowid";
$sql.= " ORDER BY s.nom";

dol_syslog("get customer invoices sql=".$sql);
$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $i = 0;
    $var=true;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $var=!$var;

        print "<tr $bc[$var]><td>&nbsp;</td>";
        print "<td>".$langs->trans("Bills")." <a href=\"../facture.php?socid=".$objp->socid."\">$objp->nom</td>\n";

        if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
        print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";

        $total_ht = $total_ht + $objp->amount_ht;
        $total_ttc = $total_ttc + $objp->amount_ttc;
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
    $sql = "SELECT 'Autres' as nom, '0' as idp, sum(p.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
    $sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE pf.rowid IS NULL";
    $sql.= " AND p.fk_bank = b.rowid";
    $sql.= " AND b.fk_account = ba.rowid";
    $sql.= " AND ba.entity = ".$conf->entity;
    if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
    $sql.= " GROUP BY nom, idp";
    $sql.= " ORDER BY nom";

    dol_syslog("get old customer payments not linked to invoices sql=".$sql);
    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        if ($num) {
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);
                $var=!$var;

                print "<tr $bc[$var]><td>&nbsp;</td>";
                print "<td>".$langs->trans("Bills")." ".$langs->trans("Other")." (".$langs->trans("PaymentsNotLinkedToInvoice").")\n";

                if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
                print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";

                $total_ht = $total_ht + $objp->amount_ht;
                $total_ttc = $total_ttc + $objp->amount_ttc;
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
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print '<td colspan="3">'.$langs->trans("None").'</td>';
    print '</tr>';
}

print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($total_ht).'</td>';
print '<td colspan="3" align="right">'.price($total_ttc).'</td>';
print '</tr>';


/*
 * Frais, factures fournisseurs.
 */
if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT s.nom, s.rowid as socid, date_format(f.datef,'%Y-%m') as dm, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql.= " WHERE f.fk_soc = s.rowid";
    $sql.= " AND f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	else $sql.= " AND f.type IN (0,1,2,3)";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
else
{
    $sql = "SELECT s.nom, s.rowid as socid, date_format(p.datep,'%Y-%m') as dm, sum(pf.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
    $sql.= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql .= " ON pf.fk_facturefourn = f.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
    $sql .= " ON f.fk_soc = s.rowid";
    $sql .= " WHERE p.rowid = pf.fk_paiementfourn ";
    if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql .= " GROUP BY s.nom, s.rowid, dm";
$sql .= " ORDER BY s.nom, s.rowid";

print '<tr><td colspan="4">'.$langs->trans("SuppliersInvoices").'</td></tr>';

$subtotal_ht = 0;
$subtotal_ttc = 0;
dol_syslog("get suppliers invoices sql=".$sql);
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

            print "<tr $bc[$var]><td>&nbsp;</td>";
            print "<td>".$langs->trans("Bills")." <a href=\"".DOL_URL_ROOT."/fourn/facture/index.php?socid=".$objp->socid."\">".$objp->nom."</a></td>\n";

            if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price(-$objp->amount_ht)."</td>\n";
            print "<td align=\"right\">".price(-$objp->amount_ttc)."</td>\n";

            $total_ht = $total_ht - $objp->amount_ht;
            $total_ttc = $total_ttc - $objp->amount_ttc;
            $subtotal_ht = $subtotal_ht + $objp->amount_ht;
            $subtotal_ttc = $subtotal_ttc + $objp->amount_ttc;
            print "</tr>\n";
            $i++;
        }
    }
    else
    {
        $var=!$var;
        print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }

    $db->free($result);
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';



/*
 * Charges sociales non deductibles
 */

print '<tr><td colspan="4">'.$langs->trans("SocialContributions").'</td></tr>';

if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= " WHERE s.fk_type = c.id";
    $sql.= " AND c.deductible = 0";
    if ($date_start && $date_end) $sql.= " AND s.date_ech >= '".$db->idate($date_start)."' AND s.date_ech <= '".$db->idate($date_end)."'";
}
else
{
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql.= " WHERE p.fk_charge = s.rowid";
    $sql.= " AND s.fk_type = c.id";
    $sql.= " AND c.deductible = 0";
    if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " GROUP BY c.libelle";

dol_syslog("get social contributions deductible=0 sql=".$sql);
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

            $total_ht = $total_ht - $obj->amount;
            $total_ttc = $total_ttc - $obj->amount;
            $subtotal_ht = $subtotal_ht + $obj->amount;
            $subtotal_ttc = $subtotal_ttc + $obj->amount;

            $var = !$var;
            print "<tr $bc[$var]><td>&nbsp;</td>";
            print '<td>'.$obj->nom.'</td>';
            if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price(-$obj->amount).'</td>';
            print '<td align="right">'.price(-$obj->amount).'</td>';
            print '</tr>';
            $i++;
        }
    }
    else {
        $var = !$var;
        print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';


/*
 * Charges sociales deductibles
 */

print '<tr><td colspan="4">'.$langs->trans("SocialContributions").'</td></tr>';

if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= " WHERE s.fk_type = c.id";
    $sql.= " AND c.deductible = 1";
    if ($date_start && $date_end) $sql.= " AND s.date_ech >= '".$db->idate($date_start)."' AND s.date_ech <= '".$db->idate($date_end)."'";
    $sql.= " AND s.entity = ".$conf->entity;
    $sql.= " GROUP BY c.libelle DESC";
}
else
{
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid";
    $sql.= " AND s.fk_type = c.id";
    $sql.= " AND c.deductible = 1";
    if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
    $sql.= " AND s.entity = ".$conf->entity;
    $sql.= " GROUP BY c.libelle";
}

dol_syslog("get social contributions deductible=1 sql=".$sql);
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

            $total_ht = $total_ht - $obj->amount;
            $total_ttc = $total_ttc - $obj->amount;
            $subtotal_ht = $subtotal_ht + $obj->amount;
            $subtotal_ttc = $subtotal_ttc + $obj->amount;

            $var = !$var;
            print "<tr $bc[$var]><td>&nbsp;</td>";
            print '<td>'.$obj->nom.'</td>';
            if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price(-$obj->amount).'</td>';
            print '<td align="right">'.price(-$obj->amount).'</td>';
            print '</tr>';
            $i++;
        }
    }
    else {
        $var = !$var;
        print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';

if ($mysoc->tva_assuj == 'franchise')	// Non assujeti
{
    // Total
    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
    print '</tr>';

    print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Profit").'</td>';
    if ($modecompta == 'CREANCES-DETTES') print '<td class="border" align="right">'.price($total_ht).'</td>';
    print '<td align="right">'.price($total_ttc).'</td>';
    print '</tr>';

    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
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
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	else $sql.= " AND f.type IN (0,1,2,3)";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm";

    dol_syslog("get vat to pay sql=".$sql);
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $amount = $amount - $obj->amount;
                //$total_ht = $total_ht - $obj->amount;
                $total_ttc = $total_ttc - $obj->amount;
                //$subtotal_ht = $subtotal_ht - $obj->amount;
                $subtotal_ttc = $subtotal_ttc - $obj->amount;
                $i++;
            }
        }
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATToPay")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";

    // TVA a recuperer
    $amount=0;
    $sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.total_tva) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql.= " WHERE f.fk_statut IN (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	else $sql.= " AND f.type IN (0,1,2,3)";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm";

    dol_syslog("get vat received back sql=".$sql);
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=true;
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $amount = $amount + $obj->amount;
                //$total_ht = $total_ht + $obj->amount;
                $total_ttc = $total_ttc + $obj->amount;
                //$subtotal_ht = $subtotal_ht + $obj->amount;
                $subtotal_ttc = $subtotal_ttc + $obj->amount;

                $i++;
            }
        }
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATToCollect")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";
}
else
{
    // TVA reellement deja payee
    $amount=0;
    $sql = "SELECT date_format(t.datev,'%Y-%m') as dm, sum(t.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql.= " WHERE amount > 0";
    if ($date_start && $date_end) $sql.= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
    $sql.= " AND t.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm DESC";

    dol_syslog("get vat really paid sql=".$sql);
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $amount = $amount - $obj->amount;
                $total_ht = $total_ht - $obj->amount;
                $total_ttc = $total_ttc - $obj->amount;
                $subtotal_ht = $subtotal_ht - $obj->amount;
                $subtotal_ttc = $subtotal_ttc - $obj->amount;

                $i++;
            }
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATPaid")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";

    // TVA recuperee
    $amount=0;
    $sql = "SELECT date_format(t.datev,'%Y-%m') as dm, sum(t.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql.= " WHERE amount < 0";
    if ($date_start && $date_end) $sql.= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
    $sql.= " AND t.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm DESC";

    dol_syslog("get vat really received back sql=".$sql);
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=true;
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $amount = $amount + $obj->amount;
                $total_ht = $total_ht + $obj->amount;
                $total_ttc = $total_ttc + $obj->amount;
                $subtotal_ht = $subtotal_ht + $obj->amount;
                $subtotal_ttc = $subtotal_ttc + $obj->amount;

                $i++;
            }
        }
        $db->free($result);
    }
    else
    {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATCollected")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";
}


if ($mysoc->tva_assuj != 'franchise')	// Assujeti
{
    print '<tr class="liste_total">';
    if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">&nbsp;</td>';
    print '<td colspan="3" align="right">'.price(price2num($subtotal_ttc,'MT')).'</td>';
    print '</tr>';
}


if ($mysoc->tva_assuj != 'franchise')	// Assujeti
{
    // Total
    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
    print '</tr>';

    print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Profit").'</td>';
    if ($modecompta == 'CREANCES-DETTES') print '<td class="liste_total" align="right">'.price(price2num($total_ht,'MT')).'</td>';
    print '<td class="liste_total" align="right">'.price(price2num($total_ttc,'MT')).'</td>';
    print '</tr>';
}

print "</table>";
print '<br>';

llxFooter();

$db->close();
?>
