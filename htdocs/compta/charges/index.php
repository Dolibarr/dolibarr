<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2011-2014 Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/compta/charges/index.php
 *      \ingroup    compta
 *		\brief      Page to list payments of special expenses
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('specialexpensesindex'));

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax|salaries', '', '', 'charges|');

$mode = GETPOST("mode", 'alpha');
$year = GETPOST("year", 'int');
$filtre = GETPOST("filtre", 'alpha');
if (!$year) { $year = date("Y", time()); }

$search_account = GETPOST('search_account', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "cs.date_ech";
if (!$sortorder) $sortorder = "DESC";


/*
 * View
 */

$tva_static = new Tva($db);
$socialcontrib = new ChargeSociales($db);
$payment_sc_static = new PaymentSocialContribution($db);
$sal_static = new PaymentSalary($db);
$accountstatic = new Account($db);

llxHeader('', $langs->trans("SpecialExpensesArea"));

$title = $langs->trans("SpecialExpensesArea");

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
if ($sortfield) $param .= '&sortfield='.$sortfield;
if ($sortorder) $param .= '&sortorder='.$sortorder;

$totalnboflines = 0;
$num = 0;

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$nav = ($year ? '<a href="index.php?year='.($year - 1).$param.'">'.img_previous($langs->trans("Previous"), 'class="valignbottom"')."</a> ".$langs->trans("Year").' '.$year.' <a href="index.php?year='.($year + 1).$param.'">'.img_next($langs->trans("Next"), 'class="valignbottom"')."</a>" : "");
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'object_payment', 0, $nav, '', $limit, 1);

if ($year) $param .= '&year='.$year;

print '<span class="opacitymedium">'.$langs->trans("DescTaxAndDividendsArea").'</span><br>';
print "<br>";

if (!empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	// Social contributions only
	print load_fiche_titre($langs->trans("SocialContributionsPayments").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "cs.date_ech", "", $param, 'width="140px"', $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "c.libelle", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "cs.fk_type", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "cs.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pc.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pc.datep", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
	if (!empty($conf->banque->enabled)) print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print "</tr>\n";

	$sql = "SELECT c.id, c.libelle as label,";
	$sql .= " cs.rowid, cs.libelle, cs.fk_type as type, cs.periode, cs.date_ech, cs.amount as total,";
	$sql .= " pc.rowid as pid, pc.datep, pc.amount as totalpaye, pc.num_paiement as num_payment, pc.fk_bank,";
	$sql .= " pct.code as payment_code,";
	$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
	$sql .= " ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON pc.fk_typepaiement = pct.id";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON pc.fk_bank = b.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	$sql .= " WHERE cs.fk_type = c.id";
	$sql .= " AND cs.entity IN (".getEntity("tax").")";
	if ($year > 0)
	{
		$sql .= " AND (";
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql .= "   (cs.periode IS NOT NULL AND cs.periode between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= " OR (cs.periode IS NULL AND cs.date_ech between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= ")";
	}
	if (preg_match('/^cs\./', $sortfield) || preg_match('/^c\./', $sortfield) || preg_match('/^pc\./', $sortfield) || preg_match('/^pct\./', $sortfield)) $sql .= $db->order($sortfield, $sortorder);
	//$sql.= $db->plimit($limit+1,$offset);
	//print $sql;

	dol_syslog("compta/charges/index.php: select payment", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		$total = 0;
		$totalnb = 0;
		$totalpaye = 0;

		while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($resql);
			print '<tr class="oddeven">';
			// Date
			$date = $obj->periode;
			if (empty($date)) $date = $obj->date_ech;
			print '<td>'.dol_print_date($date, 'day').'</td>';
			// Label
			print '<td>';
			$socialcontrib->id = $obj->rowid;
			$socialcontrib->ref = $obj->label;
			$socialcontrib->label = $obj->label;
			print $socialcontrib->getNomUrl(1, '20');
			print '</td>';
			// Type
			print '<td><a href="../sociales/list.php?filtre=cs.fk_type:'.$obj->type.'">'.$obj->label.'</a></td>';
			// Expected to pay
			print '<td class="right">'.price($obj->total).'</td>';
			// Ref payment
			$payment_sc_static->id = $obj->pid;
			$payment_sc_static->ref = $obj->pid;
			print '<td>'.$payment_sc_static->getNomUrl(1)."</td>\n";
			// Date payment
			print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';
			// Type payment
			print '<td>';
			if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
			print $obj->num_payment.'</td>';
			// Account
			if (!empty($conf->banque->enabled))
			{
				print '<td>';
				if ($obj->fk_bank > 0)
				{
					//$accountstatic->fetch($obj->fk_bank);
					$accountstatic->id = $obj->bid;
					$accountstatic->ref = $obj->bref;
					$accountstatic->number = $obj->bnumber;
					$accountstatic->accountancy_number = $obj->account_number;
					$accountstatic->accountancy_journal = $obj->accountancy_journal;
					$accountstatic->label = $obj->blabel;
					print $accountstatic->getNomUrl(1);
				} else print '&nbsp;';
				print '</td>';
			}
			// Paid
			print '<td class="right">';
			if ($obj->totalpaye) print price($obj->totalpaye);
			print '</td>';
			print '</tr>';

			$total = $total + $obj->total;
			$totalnb = $totalnb + $obj->nb;
			$totalpaye = $totalpaye + $obj->totalpaye;
			$i++;
		}
		print '<tr class="liste_total"><td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';
		print '<td class="liste_total right"></td>'; // A total here has no sense
		print '<td align="center" class="liste_total">&nbsp;</td>';
		print '<td align="center" class="liste_total">&nbsp;</td>';
		print '<td align="center" class="liste_total">&nbsp;</td>';
		if (!empty($conf->banque->enabled)) print '<td></td>';
		print '<td class="liste_total right">'.price($totalpaye)."</td>";
		print "</tr>";
	} else {
		dol_print_error($db);
	}
	print '</table>';
}

// VAT
if (!empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	print "<br>";

	$tva = new Tva($db);

	print load_fiche_titre($langs->trans("VATPayments").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

	$sql = "SELECT pv.rowid, pv.amount, pv.label, pv.datev as dm, pv.fk_bank,";
	$sql .= " pct.code as payment_code,";
	$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel";
	$sql .= " FROM ".MAIN_DB_PREFIX."tva as pv";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON pv.fk_bank = b.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON pv.fk_typepayment = pct.id";
	$sql .= " WHERE pv.entity IN (".getEntity("tax").")";
	if ($year > 0)
	{
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql .= " AND pv.datev between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
	}
	if (preg_match('/^pv\./', $sortfield)) $sql .= $db->order($sortfield, $sortorder);

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;
		$total = 0;
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "pv.datev", "", $param, 'width="140px"', $sortfield, $sortorder);
		print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "pv.label", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "pv.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pv.rowid", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pv.datev", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
		if (!empty($conf->banque->enabled)) print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
		print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pv.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print "</tr>\n";
		$var = 1;
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);

			$total = $total + $obj->amount;


			print '<tr class="oddeven">';
			print '<td class="left">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>'."\n";

			print "<td>".$obj->label."</td>\n";

			print '<td class="right">'.price($obj->amount)."</td>";

			// Ref payment
			$tva_static->id = $obj->rowid;
			$tva_static->ref = $obj->rowid;
			print '<td class="left">'.$tva_static->getNomUrl(1)."</td>\n";

			// Date
			print '<td class="center">'.dol_print_date($db->jdate($obj->dm), 'day')."</td>\n";

			// Type payment
			print '<td>';
			if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
			print $obj->num_payment.'</td>';

			// Account
			if (!empty($conf->banque->enabled))
			{
				print '<td>';
				if ($obj->fk_bank > 0)
				{
					//$accountstatic->fetch($obj->fk_bank);
					$accountstatic->id = $obj->bid;
					$accountstatic->ref = $obj->bref;
					$accountstatic->number = $obj->bnumber;
					$accountstatic->accountancy_number = $obj->account_number;
					$accountstatic->accountancy_journal = $obj->accountancy_journal;
					$accountstatic->label = $obj->blabel;
					print $accountstatic->getNomUrl(1);
				} else print '&nbsp;';
				print '</td>';
			}

			// Paid
			print '<td class="right">'.price($obj->amount)."</td>";
			print "</tr>\n";

			$i++;
		}
		print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
		print '<td class="right">'.price($total).'</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td class="right">'.price($total)."</td>";
		print "</tr>";

		print "</table>";
		$db->free($result);
	} else {
		dol_print_error($db);
	}
}

// Localtax
if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1")
{
	$j = 1;
	$numlt = 3;
} elseif ($mysoc->localtax1_assuj == "1")
{
	$j = 1;
	$numlt = 2;
} elseif ($mysoc->localtax2_assuj == "1")
{
	$j = 2;
	$numlt = 3;
} else {
	$j = 0;
	$numlt = 0;
}

while ($j < $numlt)
{
	print "<br>";

	$tva = new Tva($db);

	print load_fiche_titre($langs->transcountry(($j == 1 ? "LT1Payments" : "LT2Payments"), $mysoc->country_code).($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');


	$sql = "SELECT pv.rowid, pv.amount, pv.label, pv.datev as dm, pv.datep as dp";
	$sql .= " FROM ".MAIN_DB_PREFIX."localtax as pv";
	$sql .= " WHERE pv.entity = ".$conf->entity." AND localtaxtype = ".$j;
	if ($year > 0)
	{
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql .= " AND pv.datev between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
	}
	if (preg_match('/^pv/', $sortfield)) $sql .= $db->order($sortfield, $sortorder);

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;
		$total = 0;
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "pv.datev", "", $param, 'width="120"', $sortfield, $sortorder);
		print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "pv.label", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "pv.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pv.rowid", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pv.datep", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pv.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print "</tr>\n";

		while ($i < $num)
		{
			$obj = $db->fetch_object($result);

			$total = $total + $obj->amount;

			print '<tr class="oddeven">';
			print '<td class="left">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>'."\n";

			print "<td>".$obj->label."</td>\n";

			print '<td class="right">'.price($obj->amount)."</td>";

			// Ref payment
			$tva_static->id = $obj->rowid;
			$tva_static->ref = $obj->rowid;
			print '<td class="left">'.$tva_static->getNomUrl(1)."</td>\n";

			print '<td class="center">'.dol_print_date($db->jdate($obj->dp), 'day')."</td>\n";
			print '<td class="right">'.price($obj->amount)."</td>";
			print "</tr>\n";

			$i++;
		}
		print '<tr class="liste_total"><td class="right" colspan="2">'.$langs->trans("Total").'</td>';
		print '<td class="right">'.price($total)."</td>";
		print '<td align="center">&nbsp;</td>';
		print '<td align="center">&nbsp;</td>';
		print '<td class="right">'.price($total)."</td>";
		print "</tr>";

		print "</table>";
		$db->free($result);
	} else {
		dol_print_error($db);
	}

	$j++;
}


// Payment Salary
/*
if (!empty($conf->salaries->enabled) && !empty($user->rights->salaries->read))
{
        $sal = new PaymentSalary($db);

        print "<br>";

        print load_fiche_titre($langs->trans("SalariesPayments").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

        $sql = "SELECT s.rowid, s.amount, s.label, s.datep as datep, s.datev as datev, s.datesp, s.dateep, s.salary, s.fk_bank, u.salary as current_salary,";
		$sql .= " pct.code as payment_code,";
		$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel";
        $sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON s.fk_typepayment = pct.id";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
        $sql .= " WHERE s.entity IN (".getEntity('user').")";
        $sql .= " AND u.rowid = s.fk_user";
        if ($year > 0)
        {
            $sql .= " AND (s.datesp between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
            $sql .= " OR s.dateep between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."')";
        }
        if (preg_match('/^s\./', $sortfield)) $sql .= $db->order($sortfield, $sortorder);

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;
            $total = 0;
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "s.dateep", "", $param, 'width="140px"', $sortfield, $sortorder);
            print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "s.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "s.rowid", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "s.datep", "", $param, 'align="center"', $sortfield, $sortorder);
			print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
			if (!empty($conf->banque->enabled)) print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
            print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "s.amount", "", $param, 'class="right"', $sortfield, $sortorder);
            print "</tr>\n";

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                $total = $total + $obj->amount;


                print '<tr class="oddeven">';

                print '<td class="left">'.dol_print_date($db->jdate($obj->dateep), 'day').'</td>'."\n";

                print "<td>".$obj->label."</td>\n";

                // Ref payment
                $sal_static->id = $obj->rowid;
                $sal_static->ref = $obj->rowid;
                print '<td class="left">'.$sal_static->getNomUrl(1)."</td>\n";

                // Date
                print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>\n";

            	// Type payment
	    	    print '<td>';
	    	    if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
	    	    print $obj->num_payment.'</td>';

		    	// Account
		    	if (!empty($conf->banque->enabled))
			    {
			        print '<td>';
			        if ($obj->fk_bank > 0)
			        {
			        	//$accountstatic->fetch($obj->fk_bank);
			            $accountstatic->id = $obj->bid;
			            $accountstatic->ref = $obj->bref;
			            $accountstatic->number = $obj->bnumber;
			            $accountstatic->accountancy_number = $obj->account_number;
			            $accountstatic->accountancy_journal = $obj->accountancy_journal;
			            $accountstatic->label = $obj->blabel;
			            print $accountstatic->getNomUrl(1);
			        } else print '&nbsp;';
			        print '</td>';
			    }

                // Paid
                print '<td class="right">'.price($obj->amount)."</td>";
                print "</tr>\n";

                $i++;
            }
            print '<tr class="liste_total"><td colspan="6">'.$langs->trans("Total").'</td>';
            print '<td class="right">'.price($total)."</td>";
            print "</tr>";

            print "</table>";
            $db->free($result);

            print "<br>";
        } else {
            dol_print_error($db);
        }
}
*/

print '</form>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardSpecialBills', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
