<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2016 Ferran Marcet        <fmarcet@2byte.es>
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
 *       \file        htdocs/compta/resultat/index.php
 *       \brief       Page reporting result
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';


$year_start=GETPOST('year_start');
$year_current = strftime("%Y",time());
$nbofyear=4;
if (! $year_start) {
	$year_start = $year_current - ($nbofyear-1);
	$year_end = $year_current;
}
else {
	$year_end=$year_start + ($nbofyear-1);
}

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');


// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta=(GETPOST("modecompta")?GETPOST("modecompta"):$conf->global->ACCOUNTING_MODE);


/*
 * View
 */

llxHeader();

$form=new Form($db);

$nomlink='';
$exportlink='';

// Affiche en-tete du rapport
if ($modecompta == 'CREANCES-DETTES')
{
	$nom=$langs->trans("AnnualSummaryDueDebtMode");
	$calcmode=$langs->trans("CalcModeDebt");
	$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period="$year_start - $year_end";
	$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
	$description=$langs->trans("RulesAmountWithTaxIncluded");
	$description.='<br>'.$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.="<br>".$langs->trans("DepositsAreNotIncluded");
	else  $description.="<br>".$langs->trans("DepositsAreIncluded");
	$builddate=time();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else {
	$nom=$langs->trans("AnnualSummaryInputOutputMode");
	$calcmode=$langs->trans("CalcModeEngagement");
	$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$period="$year_start - $year_end";
	$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
	$description=$langs->trans("RulesAmountWithTaxIncluded");
	$description.='<br>'.$langs->trans("RulesResultInOut");
	$builddate=time();
	//$exportlink=$langs->trans("NotYetAvailable");
}

$hselected='report';

report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink,array('modecompta'=>$modecompta),$calcmode);

if (! empty($conf->accounting->enabled))
{
    print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}



/*
 * Factures clients
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES')
{
	$sql  = "SELECT sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."facture as f";
	$sql.= " WHERE f.fk_soc = s.rowid";
	$sql.= " AND f.fk_statut IN (1,2)";
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2,5)";
	else $sql.= " AND f.type IN (0,1,2,3,5)";
}
else
{
	/*
	 * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
	 * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
	 */
	$sql  = "SELECT sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " WHERE p.rowid = pf.fk_paiement";
	$sql.= " AND pf.fk_facture = f.rowid";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = $socid";
$sql.= " GROUP BY dm";
$sql.= " ORDER BY dm";

//print $sql;
dol_syslog("get customers invoices", LOG_DEBUG);
$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_object($result);
		$encaiss[$row->dm] = (isset($row->amount_ht)?$row->amount_ht:0);
		$encaiss_ttc[$row->dm] = $row->amount_ttc;
		$i++;
	}
	$db->free($result);
}
else {
	dol_print_error($db);
}

// On ajoute les paiements clients anciennes version, non lies par paiement_facture
if ($modecompta != 'CREANCES-DETTES')
{
	$sql = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql.= " WHERE pf.rowid IS NULL";
	$sql.= " AND p.fk_bank = b.rowid";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity IN (".getEntity('bank_account', 1).")";
	$sql.= " GROUP BY dm";
	$sql.= " ORDER BY dm";

	dol_syslog("get old customers payments not linked to invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num)
		{
			$row = $db->fetch_object($result);

			if (! isset($encaiss[$row->dm])) $encaiss[$row->dm]=0;
			$encaiss[$row->dm] += (isset($row->amount_ht)?$row->amount_ht:0);

			if (! isset($encaiss_ttc[$row->dm])) $encaiss_ttc[$row->dm]=0;
			$encaiss_ttc[$row->dm] += $row->amount_ttc;

			$i++;
		}
	}
	else {
		dol_print_error($db);
	}
}


/*
 * Frais, factures fournisseurs.
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;

if ($modecompta == 'CREANCES-DETTES')
{
	$sql  = "SELECT sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql.= " WHERE f.fk_statut IN (1,2)";
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	else $sql.= " AND f.type IN (0,1,2,3)";
}
else
{
	$sql = "SELECT sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql.= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
	$sql.= " WHERE f.rowid = pf.fk_facturefourn";
	$sql.= " AND p.rowid = pf.fk_paiementfourn";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY dm";

dol_syslog("get suppliers invoices", LOG_DEBUG);
$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_object($result);

		if (! isset($decaiss[$row->dm])) $decaiss[$row->dm]=0;
		$decaiss[$row->dm] = (isset($row->amount_ht)?$row->amount_ht:0);

		if (! isset($decaiss_ttc[$row->dm])) $decaiss_ttc[$row->dm]=0;
		$decaiss_ttc[$row->dm] = $row->amount_ttc;

		$i++;
	}
	$db->free($result);
}
else {
	dol_print_error($db);
}


/*
 * TVA
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES')
{
	// TVA a payer
	$sql = "SELECT sum(f.tva) as amount, date_format(f.datef,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= " WHERE f.fk_statut IN (1,2)";
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2,5)";
	else $sql.= " AND f.type IN (0,1,2,3,5)";
	$sql.= " AND f.entity = ".$conf->entity;
	$sql.= " GROUP BY dm";

	dol_syslog("get vat to pay", LOG_DEBUG);
	$result=$db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$var=false;
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (! isset($decaiss[$obj->dm])) $decaiss[$obj->dm]=0;
				$decaiss[$obj->dm] += $obj->amount;

				if (! isset($decaiss_ttc[$obj->dm])) $decaiss_ttc[$obj->dm]=0;
				$decaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
	// TVA a recuperer
	$sql = "SELECT sum(f.total_tva) as amount, date_format(f.datef,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql.= " WHERE f.fk_statut IN (1,2)";
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	else $sql.= " AND f.type IN (0,1,2,3)";
	$sql.= " AND f.entity = ".$conf->entity;
	$sql.= " GROUP BY dm";

	dol_syslog("get vat to receive back", LOG_DEBUG);
	$result=$db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$var=false;
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (! isset($encaiss[$obj->dm])) $encaiss[$obj->dm]=0;
				$encaiss[$obj->dm] += $obj->amount;

				if (! isset($encaiss_ttc[$obj->dm])) $encaiss_ttc[$obj->dm]=0;
				$encaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
}
else {
	// TVA reellement deja payee
	$sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
	$sql.= " WHERE amount > 0";
	$sql.= " AND t.entity = ".$conf->entity;
	$sql.= " GROUP BY dm";

	dol_syslog("get vat really paid", LOG_DEBUG);
	$result=$db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$var=false;
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (! isset($decaiss[$obj->dm])) $decaiss[$obj->dm]=0;
				$decaiss[$obj->dm] += $obj->amount;

				if (! isset($decaiss_ttc[$obj->dm])) $decaiss_ttc[$obj->dm]=0;
				$decaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
	// TVA recuperee
	$sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
	$sql.= " WHERE amount < 0";
	$sql.= " AND t.entity = ".$conf->entity;
	$sql.= " GROUP BY dm";

	dol_syslog("get vat really received back", LOG_DEBUG);
	$result=$db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$var=false;
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (! isset($encaiss[$obj->dm])) $encaiss[$obj->dm]=0;
				$encaiss[$obj->dm] += $obj->amount;

				if (! isset($encaiss_ttc[$obj->dm])) $encaiss_ttc[$obj->dm]=0;
				$encaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
}

/*
 * Charges sociales non deductibles
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES')
{
	$sql = "SELECT c.libelle as nom, date_format(cs.date_ech,'%Y-%m') as dm, sum(cs.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
	$sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql.= " WHERE cs.fk_type = c.id";
	$sql.= " AND c.deductible = 0";
}
else
{
	$sql = "SELECT c.libelle as nom, date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
	$sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
	$sql.= " WHERE p.fk_charge = cs.rowid";
	$sql.= " AND cs.fk_type = c.id";
	$sql.= " AND c.deductible = 0";
}
$sql.= " AND cs.entity = ".$conf->entity;
$sql.= " GROUP BY c.libelle, dm";

dol_syslog("get social contributions deductible=0 ", LOG_DEBUG);
$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$var=false;
	$i = 0;
	if ($num) {
		while ($i < $num) {
			$obj = $db->fetch_object($result);

			if (! isset($decaiss[$obj->dm])) $decaiss[$obj->dm]=0;
			$decaiss[$obj->dm] += $obj->amount;

			if (! isset($decaiss_ttc[$obj->dm])) $decaiss_ttc[$obj->dm]=0;
			$decaiss_ttc[$obj->dm] += $obj->amount;

			$i++;
		}
	}
} else {
	dol_print_error($db);
}

/*
 * Charges sociales deductibles
 */

$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES')
{
	$sql = "SELECT c.libelle as nom, date_format(cs.date_ech,'%Y-%m') as dm, sum(cs.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
	$sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql.= " WHERE cs.fk_type = c.id";
	$sql.= " AND c.deductible = 1";
}
else
{
	$sql = "SELECT c.libelle as nom, date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
	$sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
	$sql.= " WHERE p.fk_charge = cs.rowid";
	$sql.= " AND cs.fk_type = c.id";
	$sql.= " AND c.deductible = 1";
}
$sql.= " AND cs.entity = ".$conf->entity;
$sql.= " GROUP BY c.libelle, dm";

dol_syslog("get social contributions paid deductible=1", LOG_DEBUG);
$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$var=false;
	$i = 0;
	if ($num) {
		while ($i < $num) {
			$obj = $db->fetch_object($result);

			if (! isset($decaiss[$obj->dm])) $decaiss[$obj->dm]=0;
			$decaiss[$obj->dm] += $obj->amount;

			if (! isset($decaiss_ttc[$obj->dm])) $decaiss_ttc[$obj->dm]=0;
			$decaiss_ttc[$obj->dm] += $obj->amount;

			$i++;
		}
	}
} else {
	dol_print_error($db);
}
$action = "balance";
$object = array(&$encaiss, &$encaiss_ttc, &$decaiss, &$decaiss_ttc);
$parameters["mode"] = $modecompta;
// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook=$hookmanager->executeHooks('addReportInfo',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks


/*
 * Salaries
 */

if (! empty($conf->salaries->enabled))
{
    if ($modecompta == 'CREANCES-DETTES') {
    	$column = 'p.datev';
    } else {
    	$column = 'p.datep';
    }
    
    $subtotal_ht = 0;
    $subtotal_ttc = 0;
    $sql = "SELECT p.label as nom, date_format($column,'%Y-%m') as dm, sum(p.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
    $sql.= " WHERE p.entity = ".$conf->entity;
    $sql.= " GROUP BY p.label, dm";
    
    dol_syslog("get social salaries payments");
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
    
    			if (! isset($decaiss[$obj->dm])) $decaiss[$obj->dm]=0;
    			$decaiss[$obj->dm] += $obj->amount;
    
    			if (! isset($decaiss_ttc[$obj->dm])) $decaiss_ttc[$obj->dm]=0;
    			$decaiss_ttc[$obj->dm] += $obj->amount;
    
    			$i++;
    		}
    	}
    }
    else
    {
    	dol_print_error($db);
    }
}

if (! empty($conf->expensereport->enabled))
{
	$langs->load('trips');
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT date_format(date_valid,'%Y-%m') as dm, sum(p.total_ht) as amount_ht,sum(p.total_ttc) as amount_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as p";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
		$sql.= " WHERE p.entity = ".getEntity('expensereport',1);
		$sql.= " AND p.fk_statut>=5";

		$column='p.date_valid';

	} else {
		$sql = "SELECT date_format(pe.datep,'%Y-%m') as dm, sum(p.total_ht) as amount_ht,sum(p.total_ttc) as amount_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as p";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pe ON pe.fk_expensereport = p.rowid";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
		$sql.= " WHERE p.entity = ".getEntity('expensereport',1);
		$sql.= " AND p.fk_statut>=5";

		$column='pe.datep';
	}

	$sql.= " GROUP BY dm";

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
				if (! isset($decaiss[$obj->dm])) $decaiss[$obj->dm]=0;
				$decaiss[$obj->dm] += $obj->amount_ht;
				
				if (! isset($decaiss_ttc[$obj->dm])) $decaiss_ttc[$obj->dm]=0;
				$decaiss_ttc[$obj->dm] += $obj->amount_ttc;

			}
		}
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Donation get dunning paiement
 */
if (! empty($conf->don->enabled))
{
    $subtotal_ht = 0;
    $subtotal_ttc = 0;
    
    if ($modecompta == 'CREANCES-DETTES') {
        $sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."don as p";
        $sql.= " WHERE p.entity = ".$conf->entity;
        $sql.= " AND fk_statut in (1,2)";
    }
    else {
        $sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."don as p";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."payment_donation as pe ON pe.fk_donation = p.rowid";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
		$sql.= " WHERE p.entity = ".getEntity('donation',1);
   	    $sql.= " AND fk_statut >= 2";
    }
    $sql.= " GROUP BY p.societe, p.firstname, p.lastname, dm";

    dol_syslog("get donation payments");
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
    
    			if (! isset($encaiss[$obj->dm])) $encaiss[$obj->dm]=0;
    			$encaiss[$obj->dm] += $obj->amount;
    
    			if (! isset($encaiss_ttc[$obj->dm])) $encaiss_ttc[$obj->dm]=0;
    			$encaiss_ttc[$obj->dm] += $obj->amount;
    
    			$i++;
    		}
    	}
    }
    else
    {
    	dol_print_error($db);
    }
}

/*
 * Show result array
 */

$totentrees=array();
$totsorties=array();

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">'."\n";

print '<tr class="liste_titre"><td class="liste_titre">&nbsp;</td>';

for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="center" colspan="2" class="liste_titre borderrightlight">';
	print '<a href="clientfourn.php?year='.$annee.'">';
	print $annee;
	if ($conf->global->SOCIETE_FISCAL_MONTH_START > 1) print '-'.($annee+1);
	print '</a></td>';
}
print '</tr>';
print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td class="liste_titre" align="center">'.$langs->trans("Outcome").'</td>';
	print '<td class="liste_titre" align="center" class="borderrightlight">'.$langs->trans("Income").'</td>';
}
print '</tr>';

$var=True;

// Loop on each month
$nb_mois_decalage = $conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START-1):0;
for ($mois = 1+$nb_mois_decalage ; $mois <= 12+$nb_mois_decalage ; $mois++)
{
	$mois_modulo = $mois;
	if($mois>12) {$mois_modulo = $mois-12;}
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print "<td>".dol_print_date(dol_mktime(12,0,0,$mois_modulo,1,$annee),"%B")."</td>";
	for ($annee = $year_start ; $annee <= $year_end ; $annee++)
	{
		$annee_decalage=$annee;
		if($mois>12) {$annee_decalage=$annee+1;}
		$case = strftime("%Y-%m",dol_mktime(12,0,0,$mois_modulo,1,$annee_decalage));

		print '<td class="liste_titre" align="right">&nbsp;';
		if (isset($decaiss_ttc[$case]) && $decaiss_ttc[$case] != 0)
		{
			print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta?'&modecompta='.$modecompta:'').'">'.price(price2num($decaiss_ttc[$case],'MT')).'</a>';
			if (! isset($totsorties[$annee])) $totsorties[$annee]=0;
			$totsorties[$annee]+=$decaiss_ttc[$case];
		}
		print "</td>";

		print '<td align="right" class="liste_titre borderrightlight">&nbsp;';
		//if (isset($encaiss_ttc[$case]) && $encaiss_ttc[$case] != 0)
		if (isset($encaiss_ttc[$case]))
		{
			print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta?'&modecompta='.$modecompta:'').'">'.price(price2num($encaiss_ttc[$case],'MT')).'</a>';
			if (! isset($totentrees[$annee])) $totentrees[$annee]=0;
			$totentrees[$annee]+=$encaiss_ttc[$case];
		}
		print "</td>";
	}

	print '</tr>';
}

// Total
$var=!$var;
$nbcols=0;
print '<tr class="liste_total impair"><td>'.$langs->trans("TotalTTC").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	$nbcols+=2;
	print '<td align="right">'.(isset($totsorties[$annee])?price(price2num($totsorties[$annee],'MT')):'&nbsp;').'</td>';
	print '<td align="right" style="border-right: 1px solid #DDD">'.(isset($totentrees[$annee])?price(price2num($totentrees[$annee],'MT')):'&nbsp;').'</td>';
}
print "</tr>\n";

// Empty line
print '<tr class="impair"><td>&nbsp;</td>';
print '<td colspan="'.$nbcols.'">&nbsp;</td>';
print "</tr>\n";

// Balance
$var=!$var;
print '<tr class="liste_total"><td>'.$langs->trans("AccountingResult").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="right" colspan="2" class="borderrightlight"> ';
	if (isset($totentrees[$annee]) || isset($totsorties[$annee]))
	{
		$in=(isset($totentrees[$annee])?price2num($totentrees[$annee], 'MT'):0);
		$out=(isset($totsorties[$annee])?price2num($totsorties[$annee],'MT'):0);
		print price($in-$out).'</td>';
		//  print '<td>&nbsp;</td>';
	}
}
print "</tr>\n";

print "</table>";
print '</div>';

llxFooter();
$db->close();
