<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Dourseanud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2106  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/compta/resultat/clientfourn.php
 * 	\ingroup	compta, accountancy
 *	\brief      Page reporting
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta','bills','donation','salaries','accountancy'));

$date_startmonth=GETPOST('date_startmonth', 'int');
$date_startday=GETPOST('date_startday', 'int');
$date_startyear=GETPOST('date_startyear', 'int');
$date_endmonth=GETPOST('date_endmonth', 'int');
$date_endday=GETPOST('date_endday', 'int');
$date_endyear=GETPOST('date_endyear', 'int');
$showaccountdetail = GETPOST('showaccountdetail', 'aZ09')?GETPOST('showaccountdetail', 'aZ09'):'no';

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user, 'compta', '', '', 'resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user, 'accounting', '', '', 'comptarapport');

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield='s.nom, s.rowid';
if (! $sortorder) $sortorder='ASC';

// Date range
$year=GETPOST('year', 'int');
if (empty($year))
{
    $year_current = strftime("%Y", dol_now());
    $month_current = strftime("%m", dol_now());
    $year_start = $year_current;
} else {
    $year_current = $year;
    $month_current = strftime("%m", dol_now());
    $year_start = $year;
}
$date_start=dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end=dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// We define date_start and date_end
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
        $date_start=dol_get_first_day($year_start, $month_start, false); $date_end=dol_get_last_day($year_end, $month_end, false);
    }
    if ($q==1) { $date_start=dol_get_first_day($year_start, 1, false); $date_end=dol_get_last_day($year_start, 3, false); }
    if ($q==2) { $date_start=dol_get_first_day($year_start, 4, false); $date_end=dol_get_last_day($year_start, 6, false); }
    if ($q==3) { $date_start=dol_get_first_day($year_start, 7, false); $date_end=dol_get_last_day($year_start, 9, false); }
    if ($q==4) { $date_start=dol_get_first_day($year_start, 10, false); $date_end=dol_get_last_day($year_start, 12, false); }
}

// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps=dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe=dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $start_year) + 1;
//var_dump("year_start=".$year_start." year_end=".$year_end." nbofyear=".$nbofyear." date_start=".dol_print_date($date_start, 'dayhour')." date_end=".dol_print_date($date_end, 'dayhour'));

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
$modecompta = $conf->global->ACCOUNTING_MODE;
if (! empty($conf->accounting->enabled)) $modecompta='BOOKKEEPING';
if (GETPOST("modecompta", 'alpha')) $modecompta=GETPOST("modecompta", 'alpha');

$AccCat = new AccountancyCategory($db);



/*
 * View
 */

llxHeader();

$form=new Form($db);

$periodlink='';
$exportlink='';

$total_ht=0;
$total_ttc=0;

// Affiche en-tete de rapport
if ($modecompta=="CREANCES-DETTES")
{
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPredefinedAccountGroups");
	$calcmode=$langs->trans("CalcModeDebt");
    $calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode", '<a href="'.$_SERVER["PHP_SELF"].'?date_startyear='.$tmps['year'].'&date_startmonth='.$tmps['mon'].'&date_startday='.$tmps['mday'].'&date_endyear='.$tmpe['year'].'&date_endmonth='.$tmpe['mon'].'&date_endday='.$tmpe['mday'].'&modecompta=RECETTES-DEPENSES">', '</a>').')';
    if (! empty($conf->accounting->enabled)) $calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode", '<a href="'.$_SERVER["PHP_SELF"].'?date_startyear='.$tmps['year'].'&date_startmonth='.$tmps['mon'].'&date_startday='.$tmps['mday'].'&date_endyear='.$tmpe['year'].'&date_endmonth='.$tmpe['mon'].'&date_endday='.$tmpe['mday'].'&modecompta=BOOKKEEPING">', '</a>').')';
    $period=$form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year']-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year']+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
    $builddate=dol_now();
    //$exportlink=$langs->trans("NotYetAvailable");
}
elseif ($modecompta=="RECETTES-DEPENSES")
{
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPredefinedAccountGroups");
	$calcmode=$langs->trans("CalcModeEngagement");
    $calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode", '<a href="'.$_SERVER["PHP_SELF"].'?date_startyear='.$tmps['year'].'&date_startmonth='.$tmps['mon'].'&date_startday='.$tmps['mday'].'&date_endyear='.$tmpe['year'].'&date_endmonth='.$tmpe['mon'].'&date_endday='.$tmpe['mday'].'&modecompta=CREANCES-DETTES">', '</a>').')';
    if (! empty($conf->accounting->enabled)) $calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode", '<a href="'.$_SERVER["PHP_SELF"].'?date_startyear='.$tmps['year'].'&date_startmonth='.$tmps['mon'].'&date_startday='.$tmps['mday'].'&date_endyear='.$tmpe['year'].'&date_endmonth='.$tmpe['mon'].'&date_endday='.$tmpe['mday'].'&modecompta=BOOKKEEPING">', '</a>').')';
    $period=$form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year']-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year']+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesResultInOut");
    $builddate=dol_now();
    //$exportlink=$langs->trans("NotYetAvailable");
}
elseif ($modecompta=="BOOKKEEPING")
{
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPredefinedAccountGroups");
	$calcmode=$langs->trans("CalcModeBookkeeping");
    $calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode", '<a href="'.$_SERVER["PHP_SELF"].'?date_startyear='.$tmps['year'].'&date_startmonth='.$tmps['mon'].'&date_startday='.$tmps['mday'].'&date_endyear='.$tmpe['year'].'&date_endmonth='.$tmpe['mon'].'&date_endday='.$tmpe['mday'].'&modecompta=RECETTES-DEPENSES">', '</a>').')';
	$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode", '<a href="'.$_SERVER["PHP_SELF"].'?date_startyear='.$tmps['year'].'&date_startmonth='.$tmps['mon'].'&date_startday='.$tmps['mday'].'&date_endyear='.$tmpe['year'].'&date_endmonth='.$tmpe['mon'].'&date_endday='.$tmpe['mday'].'&modecompta=CREANCES-DETTES">', '</a>').')';
	$period=$form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$arraylist=array('no'=>$langs->trans("No"), 'yes'=>$langs->trans("AccountWithNonZeroValues"), 'all'=>$langs->trans("All"));
	$period.=' &nbsp; &nbsp; '.$langs->trans("DetailByAccount").' '. $form->selectarray('showaccountdetail', $arraylist, $showaccountdetail, 0);
	$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year']-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year']+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
	$description=$langs->trans("RulesResultBookkeepingPredefined");
	$description.=' ('.$langs->trans("SeePageForSetup", DOL_URL_ROOT.'/accountancy/admin/account.php?mainmenu=accountancy&leftmenu=accountancy_admin', $langs->transnoentitiesnoconv("Accountancy").' / '.$langs->transnoentitiesnoconv("Setup").' / '.$langs->transnoentitiesnoconv("Chartofaccounts")).')';
	$builddate=dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}

$hselected = 'report';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array('modecompta'=>$modecompta), $calcmode);

if (! empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING')
{
    print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}

// Show report array
$param='&modecompta='.$modecompta;
if ($date_startday) $param.='&date_startday='.$date_startday;
if ($date_startmonth) $param.='&date_startmonth='.$date_startmonth;
if ($date_startyear) $param.='&date_startyear='.$date_startyear;
if ($date_endday) $param.='&date_endday='.$date_endday;
if ($date_endmonth) $param.='&date_endmonth='.$date_endmonth;
if ($date_endyear) $param.='&date_endyear='.$date_startyear;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre("PredefinedGroups", $_SERVER["PHP_SELF"], 'f.thirdparty_code,f.rowid', '', $param, '', $sortfield, $sortorder, 'width200 ');
print_liste_field_titre('');
if ($modecompta == 'BOOKKEEPING')
{
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], 'amount', '', $param, 'class="right"', $sortfield, $sortorder);
}
else
{
	if ($modecompta == 'CREANCES-DETTES')
	{
		print_liste_field_titre("AmountHT", $_SERVER["PHP_SELF"], 'amount_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	}
	print_liste_field_titre("AmountTTC", $_SERVER["PHP_SELF"], 'amount_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
}
print "</tr>\n";



if ($modecompta == 'BOOKKEEPING')
{
	$predefinedgroupwhere = "(";
	//$predefinedgroupwhere.= " (pcg_type = 'EXPENSE' and pcg_subtype in ('PRODUCT','SERVICE'))";
	$predefinedgroupwhere.= " (pcg_type = 'EXPENSE')";
	$predefinedgroupwhere.= " OR ";
	//$predefinedgroupwhere.= " (pcg_type = 'INCOME' and pcg_subtype in ('PRODUCT','SERVICE'))";
	$predefinedgroupwhere.= " (pcg_type = 'INCOME')";
	$predefinedgroupwhere.= ")";

	$charofaccountstring = $conf->global->CHARTOFACCOUNTS;
	$charofaccountstring=dol_getIdFromCode($db, $conf->global->CHARTOFACCOUNTS, 'accounting_system', 'rowid', 'pcg_version');

	$sql = "SELECT f.thirdparty_code as name, -1 as socid, aa.pcg_type, aa.pcg_subtype, sum(f.credit - f.debit) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as f";
	$sql.= ", ".MAIN_DB_PREFIX."accounting_account as aa";
	$sql.= " WHERE f.numero_compte = aa.account_number";
	$sql.= " AND ".$predefinedgroupwhere;
	$sql.= " AND fk_pcg_version = '".$db->escape($charofaccountstring)."'";
	$sql.= " AND f.entity = ".$conf->entity;
	if (! empty($date_start) && ! empty($date_end))
		$sql.= " AND f.doc_date >= '".$db->idate($date_start)."' AND f.doc_date <= '".$db->idate($date_end)."'";
	$sql.= " GROUP BY pcg_type, pcg_subtype, name, socid";
	$sql.= $db->order($sortfield, $sortorder);

	$oldpcgtype = '';
	$oldpcgsubtype = '';

	dol_syslog("get bookkeeping entries", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				if ($objp->pcg_type != $oldpcgtype)
				{
					print '<tr><td colspan="4">'.$objp->pcg_type.'</td></tr>';
					$oldpcgtype = $objp->pcg_type;
				}

				print '<tr class="oddeven">';
				print '<td>&nbsp;</td>';
				print '<td>'.$objp->pcg_type.($objp->pcg_subtype != 'XXXXXX'?' - '.$objp->pcg_subtype:'').($objp->name?' ('.$objp->name.')':'')."</td>\n";
				print '<td class="right">'.price($objp->amount)."</td>\n";
				print "</tr>\n";

				$total_ht += (isset($objp->amount)?$objp->amount:0);
				$total_ttc +=  (isset($objp->amount)?$objp->amount:0);

				// Loop on detail of all accounts
				// This make 14 calls for each detail of account (NP, N and month m)
				if ($showaccountdetail != 'no')
				{
					$tmppredefinedgroupwhere="pcg_type = '".$db->escape($objp->pcg_type)."' AND pcg_subtype = '".$db->escape($objp->pcg_subtype)."'";
					$tmppredefinedgroupwhere.= " AND fk_pcg_version = '".$db->escape($charofaccountstring)."'";
					//$tmppredefinedgroupwhere.= " AND thirdparty_code = '".$db->escape($objp->name)."'";

					// Get cpts of category/group
					$cpts = $AccCat->getCptsCat(0, $tmppredefinedgroupwhere);

					foreach($cpts as $j => $cpt)
					{
						$return = $AccCat->getSumDebitCredit($cpt['account_number'], $date_start, $date_end, $cpt['dc']);
						if ($return < 0) {
							setEventMessages(null, $AccCat->errors, 'errors');
							$resultN=0;
						} else {
							$resultN=$AccCat->sdc;
						}


						if ($showaccountdetail == 'all' || $resultN <> 0)
						{
							print '<tr>';
							print '<td></td>';
							print '<td class="tdoverflowmax200"> &nbsp; &nbsp; ' . length_accountg($cpt['account_number']) . ' - ' . $cpt['account_label'] . '</td>';
							print '<td class="right">' . price($resultN) . '</td>';
							print "</tr>\n";
						}
					}
				}

				$i++;
			}
		}
		else
		{
			print '<tr><td colspan="4" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
		}
	}
	else dol_print_error($db);
}
else
{
	/*
	 * Factures clients
	 */
	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("CustomersInvoices").'</td></tr>';

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
	elseif ($modecompta == 'RECETTES-DEPENSES')
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
	$sql.= " AND f.entity IN (".getEntity('invoice').")";
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	$sql.= " GROUP BY name, socid";
	$sql.= $db->order($sortfield, $sortorder);

	dol_syslog("get customer invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
	    $num = $db->num_rows($result);
	    $i = 0;
	    while ($i < $num)
	    {
	        $objp = $db->fetch_object($result);

	        print '<tr class="oddeven"><td>&nbsp;</td>';
	        print "<td>".$langs->trans("Bills").' <a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'">'.$objp->name."</td>\n";

	        if ($modecompta == 'CREANCES-DETTES')
	        	print '<td class="right">'.price($objp->amount_ht)."</td>\n";
	        print '<td class="right">'.price($objp->amount_ttc)."</td>\n";

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
	if ($modecompta == 'RECETTES-DEPENSES')
	{
	    $sql = "SELECT 'Autres' as name, '0' as idp, sum(p.amount) as amount_ttc";
	    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	    $sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	    $sql.= " WHERE pf.rowid IS NULL";
	    $sql.= " AND p.fk_bank = b.rowid";
	    $sql.= " AND b.fk_account = ba.rowid";
	    $sql.= " AND ba.entity IN (".getEntity('bank_account').")";
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


	                print '<tr class="oddeven"><td>&nbsp;</td>';
	                print "<td>".$langs->trans("Bills")." ".$langs->trans("Other")." (".$langs->trans("PaymentsNotLinkedToInvoice").")\n";

	                if ($modecompta == 'CREANCES-DETTES')
	                	print '<td class="right">'.price($objp->amount_ht)."</td>\n";
	                print '<td class="right">'.price($objp->amount_ttc)."</td>\n";

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
	    print '<tr class="oddeven"><td>&nbsp;</td>';
	    print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
	    print '</tr>';
	}

	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES')
		print '<td colspan="3" class="right">'.price($total_ht).'</td>';
	print '<td colspan="3" class="right">'.price($total_ttc).'</td>';
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
	elseif ($modecompta == 'RECETTES-DEPENSES')
	{
	    $sql = "SELECT s.nom as name, s.rowid as socid, sum(pf.amount) as amount_ttc";
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
	$sql .= " GROUP BY name, socid";
	$sql.= $db->order($sortfield, $sortorder);

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SuppliersInvoices").'</td></tr>';

	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	dol_syslog("get suppliers invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
	    $num = $db->num_rows($result);
	    $i = 0;
	    if ($num > 0)
	    {
	        while ($i < $num)
	        {
	            $objp = $db->fetch_object($result);

	            print '<tr class="oddeven"><td>&nbsp;</td>';
	            print "<td>".$langs->trans("Bills")." <a href=\"".DOL_URL_ROOT."/fourn/facture/list.php?socid=".$objp->socid."\">".$objp->name."</a></td>\n";

	            if ($modecompta == 'CREANCES-DETTES')
	            	print '<td class="right">'.price(-$objp->amount_ht)."</td>\n";
	            print '<td class="right">'.price(-$objp->amount_ttc)."</td>\n";

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
	        print '<tr class="oddeven"><td>&nbsp;</td>';
	        print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
	        print '</tr>';
	    }

	    $db->free($result);
	} else {
	    dol_print_error($db);
	}
	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES')
		print '<td colspan="3" class="right">'.price(-$subtotal_ht).'</td>';
	print '<td colspan="3" class="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';



	/*
	 * Charges sociales non deductibles
	 */

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SocialContributionsNondeductibles").'</td></tr>';

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
	elseif ($modecompta == 'RECETTES-DEPENSES')
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
	$newsortfield = $sortfield;
	if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'c.libelle, c.id';
	if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
	if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';

	$sql.= $db->order($newsortfield, $sortorder);

	dol_syslog("get social contributions deductible=0", LOG_DEBUG);
	$result=$db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result) {
		$num = $db->num_rows($result);
	    $i = 0;
	    if ($num) {
	        while ($i < $num) {
	            $obj = $db->fetch_object($result);

	            $total_ht -= $obj->amount;
	            $total_ttc -= $obj->amount;
	            $subtotal_ht += $obj->amount;
	            $subtotal_ttc += $obj->amount;

	            print '<tr class="oddeven"><td>&nbsp;</td>';
	            print '<td>'.$obj->label.'</td>';
	            if ($modecompta == 'CREANCES-DETTES') print '<td class="right">'.price(-$obj->amount).'</td>';
	            print '<td class="right">'.price(-$obj->amount).'</td>';
	            print '</tr>';
	            $i++;
	        }
	    }
	    else {
	        print '<tr class="oddeven"><td>&nbsp;</td>';
	        print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
	        print '</tr>';
	    }
	} else {
	    dol_print_error($db);
	}
	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES')
		print '<td colspan="3" class="right">'.price(-$subtotal_ht).'</td>';
	print '<td colspan="3" class="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';


	/*
	 * Charges sociales deductibles
	 */

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SocialContributionsDeductibles").'</td></tr>';

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
	}
	elseif ($modecompta == 'RECETTES-DEPENSES')
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
	}
	$sql.= " GROUP BY c.libelle, c.id";
	$newsortfield = $sortfield;
	if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'c.libelle, c.id';
	if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
	if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
	$sql.= $db->order($newsortfield, $sortorder);

	dol_syslog("get social contributions deductible=1", LOG_DEBUG);
	$result=$db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result) {
	    $num = $db->num_rows($result);
	    $i = 0;
	    if ($num) {
	        while ($i < $num) {
	            $obj = $db->fetch_object($result);

	            $total_ht -= $obj->amount;
	            $total_ttc -= $obj->amount;
	            $subtotal_ht += $obj->amount;
	            $subtotal_ttc += $obj->amount;

	            print '<tr class="oddeven"><td>&nbsp;</td>';
	            print '<td>'.$obj->label.'</td>';
	            if ($modecompta == 'CREANCES-DETTES')
	            	print '<td class="right">'.price(-$obj->amount).'</td>';
	            print '<td class="right">'.price(-$obj->amount).'</td>';
	            print '</tr>';
	            $i++;
	        }
	    }
	    else {
	        print '<tr class="oddeven"><td>&nbsp;</td>';
	        print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
	        print '</tr>';
	    }
	} else {
	    dol_print_error($db);
	}
	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES')
		print '<td colspan="3" class="right">'.price(-$subtotal_ht).'</td>';
	print '<td colspan="3" class="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';

	if ($mysoc->tva_assuj == 'franchise')	// Non assujetti
	{
	    // Total
	    print '<tr>';
	    print '<td colspan="4">&nbsp;</td>';
	    print '</tr>';

	    print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Profit").'</td>';
	    if ($modecompta == 'CREANCES-DETTES')
	    	print '<td class="border right">'.price($total_ht).'</td>';
	    print '<td class="right">'.price($total_ttc).'</td>';
	    print '</tr>';

	    print '<tr>';
	    print '<td colspan="4">&nbsp;</td>';
	    print '</tr>';
	}


	/*
	 * Salaries
	 */

	if (! empty($conf->salaries->enabled))
	{
		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("Salaries").'</td></tr>';

	 	if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES')
		{
			if ($modecompta == 'CREANCES-DETTES') {
			    $column = 'p.datev';
			} else {
			    $column = 'p.datep';
			}

			$sql = "SELECT u.rowid, u.firstname, u.lastname, p.fk_user, p.label as label, date_format($column,'%Y-%m') as dm, sum(p.amount) as amount";
			$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user";
			$sql.= " WHERE p.entity IN (".getEntity('payment_salary').")";
			if (! empty($date_start) && ! empty($date_end))
				$sql.= " AND $column >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";

			$sql.= " GROUP BY u.rowid, u.firstname, u.lastname, p.fk_user, p.label, dm";
			$newsortfield = $sortfield;
		    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'u.firstname, u.lastname';
		    if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
		    if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
			$sql.= $db->order($newsortfield, $sortorder);
		}

		dol_syslog("get payment salaries");
		$result=$db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result)
		{
		    $num = $db->num_rows($result);
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

		            print '<tr class="oddeven"><td>&nbsp;</td>';

		            print "<td>".$langs->trans("Salary")." <a href=\"".DOL_URL_ROOT."/compta/salaries/list.php?filtre=s.fk_user=".$obj->fk_user."\">".$obj->firstname." ".$obj->lastname."</a></td>\n";

		            if ($modecompta == 'CREANCES-DETTES') print '<td class="right">'.price(-$obj->amount).'</td>';
		            print '<td class="right">'.price(-$obj->amount).'</td>';
		            print '</tr>';
		            $i++;
		        }
		    }
		    else
		    {
		        print '<tr class="oddeven"><td>&nbsp;</td>';
		        print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
		        print '</tr>';
		    }
		}
		else
		{
		    dol_print_error($db);
		}
		print '<tr class="liste_total">';
		if ($modecompta == 'CREANCES-DETTES')
			print '<td colspan="3" class="right">'.price(-$subtotal_ht).'</td>';
		print '<td colspan="3" class="right">'.price(-$subtotal_ttc).'</td>';
		print '</tr>';
	}


	/*
	 * Expense
	 */

	if (! empty($conf->expensereport->enabled))
	{
		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES')
		{
			$langs->load('trips');
			if ($modecompta == 'CREANCES-DETTES') {
				$sql = "SELECT p.rowid, p.ref, u.rowid as userid, u.firstname, u.lastname, date_format(date_valid,'%Y-%m') as dm, sum(p.total_ht) as amount_ht,sum(p.total_ttc) as amount_ttc";
				$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as p";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
				$sql.= " WHERE p.entity IN (".getEntity('expensereport').")";
				$sql.= " AND p.fk_statut>=5";

				$column='p.date_valid';
			} else {
				$sql = "SELECT p.rowid, p.ref, u.rowid as userid, u.firstname, u.lastname, date_format(pe.datep,'%Y-%m') as dm, sum(p.total_ht) as amount_ht, sum(p.total_ttc) as amount_ttc";
				$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as p";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pe ON pe.fk_expensereport = p.rowid";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
				$sql.= " WHERE p.entity IN (".getEntity('expensereport').")";
				$sql.= " AND p.fk_statut>=5";

				$column='pe.datep';
			}

			if (! empty($date_start) && ! empty($date_end))
			{
				$sql.= " AND $column >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
			}

			$sql.= " GROUP BY u.rowid, p.rowid, p.ref, u.firstname, u.lastname, dm";
		    $newsortfield = $sortfield;
		    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'p.ref';
		    $sql.= $db->order($newsortfield, $sortorder);
		}

		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("ExpenseReport").'</td></tr>';

		dol_syslog("get expense report outcome");
		$result=$db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result)
		{
			$num = $db->num_rows($result);
			if ($num)
			{
				while ($obj = $db->fetch_object($result))
				{
					$total_ht -= $obj->amount_ht;
					$total_ttc -= $obj->amount_ttc;
					$subtotal_ht += $obj->amount_ht;
					$subtotal_ttc += $obj->amount_ttc;

					print '<tr class="oddeven"><td>&nbsp;</td>';

					print "<td>".$langs->trans("ExpenseReport")." <a href=\"".DOL_URL_ROOT."/expensereport/list.php?search_user=".$obj->userid."\">".$obj->firstname." ".$obj->lastname."</a></td>\n";

					if ($modecompta == 'CREANCES-DETTES') print '<td class="right">'.price(-$obj->amount_ht).'</td>';
					print '<td class="right">'.price(-$obj->amount_ttc).'</td>';
					print '</tr>';
				}
			}
			else
			{
				print '<tr class="oddeven"><td>&nbsp;</td>';
				print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
				print '</tr>';
			}
		}
		else
		{
			dol_print_error($db);
		}
		print '<tr class="liste_total">';
		if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" class="right">'.price(-$subtotal_ht).'</td>';
		print '<td colspan="3" class="right">'.price(-$subtotal_ttc).'</td>';
		print '</tr>';
	}

	/*
	 * Donations
	 */

	if (! empty($conf->don->enabled))
	{
		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("Donations").'</td></tr>';

		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES')
		{
			if ($modecompta == 'CREANCES-DETTES')
			{
		    	$sql = "SELECT p.societe as name, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
		    	$sql.= " FROM ".MAIN_DB_PREFIX."don as p";
		    	$sql.= " WHERE p.entity IN (".getEntity('donation').")";
		    	$sql.= " AND fk_statut in (1,2)";
			}
			else
			{
			    $sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
			    $sql.= " FROM ".MAIN_DB_PREFIX."don as p";
			    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."payment_donation as pe ON pe.fk_donation = p.rowid";
			    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
			    $sql.= " WHERE p.entity IN (".getEntity('donation').")";
			    $sql.= " AND fk_statut >= 2";
			}
			if (! empty($date_start) && ! empty($date_end))
				$sql.= " AND p.datedon >= '".$db->idate($date_start)."' AND p.datedon <= '".$db->idate($date_end)."'";
		}
		$sql.= " GROUP BY p.societe, p.firstname, p.lastname, dm";
	    $newsortfield = $sortfield;
	    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'p.societe, p.firstname, p.lastname, dm';
	    if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
	    if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
	    $sql.= $db->order($newsortfield, $sortorder);

		dol_syslog("get dunning");
		$result=$db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result)
		{
			$num = $db->num_rows($result);
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

					print '<tr class="oddeven"><td>&nbsp;</td>';

					print "<td>".$langs->trans("Donation")." <a href=\"".DOL_URL_ROOT."/don/list.php?search_company=".$obj->name."&search_name=".$obj->firstname." ".$obj->lastname."\">".$obj->name. " ".$obj->firstname." ".$obj->lastname."</a></td>\n";

					if ($modecompta == 'CREANCES-DETTES') print '<td class="right">'.price($obj->amount).'</td>';
					print '<td class="right">'.price($obj->amount).'</td>';
					print '</tr>';
					$i++;
				}
			}
			else
			{
				print '<tr class="oddeven"><td>&nbsp;</td>';
				print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
				print '</tr>';
			}
		}
		else
		{
			dol_print_error($db);
		}
		print '<tr class="liste_total">';
		if ($modecompta == 'CREANCES-DETTES')
			print '<td colspan="3" class="right">'.price($subtotal_ht).'</td>';
		print '<td colspan="3" class="right">'.price($subtotal_ttc).'</td>';
		print '</tr>';
	}


	/*
	 * VAT
	 */

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("VAT").'</td></tr>';
	$subtotal_ht = 0;
	$subtotal_ttc = 0;

	if ($conf->tax->enabled && ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES'))
	{
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
		    $sql.= " AND f.entity IN (".getEntity('invoice').")";
		    $sql.= " GROUP BY dm";
		    $newsortfield = $sortfield;
		    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'dm';
		    if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
		    if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
		    $sql.= $db->order($newsortfield, $sortorder);

		    dol_syslog("get vat to pay", LOG_DEBUG);
		    $result=$db->query($sql);
		    if ($result)
		    {
		        $num = $db->num_rows($result);
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
		    print '<tr class="oddeven"><td>&nbsp;</td>';
		    print "<td>".$langs->trans("VATToPay")."</td>\n";
		    print '<td class="right">&nbsp;</td>'."\n";
		    print '<td class="right">'.price($amount)."</td>\n";
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
		    $newsortfield = $sortfield;
		    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'dm';
		    if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
		    if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
		    $sql.= $db->order($newsortfield, $sortorder);

		    dol_syslog("get vat received back", LOG_DEBUG);
		    $result=$db->query($sql);
		    if ($result)
		    {
		        $num = $db->num_rows($result);
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
		    print '<tr class="oddeven"><td>&nbsp;</td>';
		    print '<td>'.$langs->trans("VATToCollect")."</td>\n";
		    print '<td class="right">&nbsp;</td>'."\n";
		    print '<td class="right">'.price($amount)."</td>\n";
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
		    $newsortfield = $sortfield;
		    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'dm';
		    if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
		    if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
		    $sql.= $db->order($newsortfield, $sortorder);

		    dol_syslog("get vat really paid", LOG_DEBUG);
		    $result=$db->query($sql);
		    if ($result) {
		        $num = $db->num_rows($result);
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
		    print '<tr class="oddeven"><td>&nbsp;</td>';
		    print "<td>".$langs->trans("VATPaid")."</td>\n";
		    if ($modecompta == 'CREANCES-DETTES')
		    	print '<td <class="right">'.price($amount)."</td>\n";
		    print '<td class="right">'.price($amount)."</td>\n";
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
		    $newsortfield = $sortfield;
		    if ($newsortfield == 's.nom, s.rowid') $newsortfield = 'dm';
		    if ($newsortfield == 'amount_ht') $newsortfield = 'amount';
		    if ($newsortfield == 'amount_ttc') $newsortfield = 'amount';
		    $sql.= $db->order($newsortfield, $sortorder);

		    dol_syslog("get vat really received back", LOG_DEBUG);
		    $result=$db->query($sql);
		    if ($result) {
		        $num = $db->num_rows($result);
		        $i = 0;
		        if ($num) {
		            while ($i < $num) {
		                $obj = $db->fetch_object($result);

		                $amount += -$obj->amount;
		                $total_ht += -$obj->amount;
		                $total_ttc += -$obj->amount;
		                $subtotal_ht += -$obj->amount;
		                $subtotal_ttc += -$obj->amount;

		                $i++;
		            }
		        }
		        $db->free($result);
		    }
		    else
		    {
		        dol_print_error($db);
		    }
		    print '<tr class="oddeven"><td>&nbsp;</td>';
		    print "<td>".$langs->trans("VATCollected")."</td>\n";
		    if ($modecompta == 'CREANCES-DETTES')
		    	print '<td class="right">'.price($amount)."</td>\n";
		    print '<td class="right">'.price($amount)."</td>\n";
		    print "</tr>\n";
		}
	}

	if ($mysoc->tva_assuj != 'franchise')	// Assujetti
	{
	    print '<tr class="liste_total">';
	    if ($modecompta == 'CREANCES-DETTES')
	    	print '<td colspan="3" class="right">&nbsp;</td>';
	    print '<td colspan="3" class="right">'.price(price2num($subtotal_ttc, 'MT')).'</td>';
	    print '</tr>';
	}
}

$action = "balanceclient";
$object = array(&$total_ht, &$total_ttc);
$parameters["mode"] = $modecompta;
$parameters["date_start"] = $date_start;
$parameters["date_end"] = $date_end;
$parameters["bc"] = $bc;
// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook=$hookmanager->executeHooks('addBalanceLine', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
print $hookmanager->resPrint;

if ($mysoc->tva_assuj != 'franchise')	// Assujetti
{
    // Total
    print '<tr>';
    print '<td colspan="4">&nbsp;</td>';
    print '</tr>';

    print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Profit").'</td>';
    if ($modecompta == 'CREANCES-DETTES')
    	print '<td class="liste_total right">'.price(price2num($total_ht, 'MT')).'</td>';
    print '<td class="liste_total right">'.price(price2num($total_ttc, 'MT')).'</td>';
    print '</tr>';
}

print "</table>";
print '<br>';

// End of page
llxFooter();
$db->close();
