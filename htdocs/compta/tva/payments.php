<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2011-2014 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *      \file       htdocs/compta/tva/payments.php
 *      \ingroup    compta
 *		\brief      Page to list payments of special expenses
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax|salaries', '', '', 'charges|');

$mode = GETPOST("mode", 'alpha');
$year = GETPOST("year", 'int');
$filtre = GETPOST("filtre", 'alpha');
if (!$year && $mode != 'tvaonly') { $year = date("Y", time()); }

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "ptva.datep";
if (!$sortorder) $sortorder = "DESC";


/*
 * View
 */

$tva_static = new Tva($db);
$tva = new Tva($db);
$payment_vat_static = new PaymentVAT($db);
$sal_static = new PaymentSalary($db);

llxHeader('', $langs->trans("VATExpensesArea"));

$title = $langs->trans("VATArea");
if ($mode == 'tvaonly') $title = $langs->trans("VATPayments");

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
if ($mode == 'tvaonly') $param = '&mode=tvaonly';
if ($sortfield) $param .= '&sortfield='.$sortfield;
if ($sortorder) $param .= '&sortorder='.$sortorder;


print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

if ($mode != 'tvaonly')
{
    $center = ($year ? '<a href="list.php?year='.($year - 1).$param.'">'.img_previous($langs->trans("Previous"), 'class="valignbottom"')."</a> ".$langs->trans("Year").' '.$year.' <a href="list.php?year='.($year + 1).$param.'">'.img_next($langs->trans("Next"), 'class="valignbottom"')."</a>" : "");
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $totalnboflines, 'title_accountancy', 0, '', '', $limit, 1);
}
else
{
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $totalnboflines, 'title_accountancy', 0, '', '', $limit);
}

if ($year) $param .= '&year='.$year;

if ($mode != 'tvaonly')
{
	print $langs->trans("DescTaxAndDividendsArea").'<br>';
	print "<br>";
}

if (!empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	// Social contributions only
	if ($mode != 'tvaonly')
	{
		print load_fiche_titre($langs->trans("VATPayments").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');
	}

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "ptva.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "ptva.datep", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("BankAccount", $_SERVER["PHP_SELF"], "bank.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("LabelContrib", $_SERVER["PHP_SELF"], "tva.label", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("TypeContrib", $_SERVER["PHP_SELF"], "tva.fk_type", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "tva.datev", "", $param, 'width="140px"', $sortfield, $sortorder);
	print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "tva.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "ptva.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print "</tr>\n";

	$sql = "SELECT tva.rowid, tva.label as label, b.fk_account";
	$sql .= ", tva.datev";
	$sql .= ", tva.amount as total,";
	$sql .= " ptva.rowid as pid, ptva.datep, ptva.amount as totalpaye, ptva.num_paiement as num_payment,";
	$sql .= " pct.code as payment_code";
	$sql .= " FROM ".MAIN_DB_PREFIX."tva as tva,";
	$sql .= " ".MAIN_DB_PREFIX."paiementtva as ptva";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bank as b ON (b.rowid = ptva.fk_bank)";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bank_account as bank ON (bank.rowid = b.fk_account)";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON ptva.fk_typepaiement = pct.id";
	$sql .= " WHERE ptva.fk_tva = tva.rowid";
	$sql .= " AND tva.entity = ".$conf->entity;
	if ($year > 0)
	{
		$sql .= " AND (";
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql .= "   (tva.datev IS NOT NULL AND tva.datev between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= " OR (tva.datev IS NULL AND tva.datev between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= ")";
	}
	if (preg_match('/^cs\./', $sortfield)
		|| preg_match('/^tva\./', $sortfield)
		|| preg_match('/^ptva\./', $sortfield)
		|| preg_match('/^pct\./', $sortfield)
		|| preg_match('/^bank\./', $sortfield)) $sql .= $db->order($sortfield, $sortorder);
	//$sql.= $db->plimit($limit+1,$offset);
	//print $sql;

	dol_syslog("compta/tva/payments.php: select payment", LOG_DEBUG);
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

			$payment_vat_static->id = $obj->pid;
			$payment_vat_static->ref = $obj->pid;

			print '<tr class="oddeven">';
			// Ref payment
			print '<td>'.$payment_vat_static->getNomUrl(1)."</td>\n";
			// Date payment
			print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';
	        // Type payment
    	    print '<td>';
    	    if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
    	    print $obj->num_payment.'</td>';
    	    // Account
			print '<td>';
			$account = new Account($db);
			$account->fetch($obj->fk_account);
			print $account->getNomUrl(1);
			print '</td>';
			// Label
			print '<td>';
			$tva->id = $obj->rowid;
			$tva->ref = $obj->rowid;
			$tva->label = $obj->label;
			print $tva->getNomUrl(1, '20');
			print '</td>';
			// Type
			//print '<td><a href="../tva/list.php?filtre=tva.fk_type:'.$obj->type.'">'.$obj->type_label.'</a></td>';
			// Date
			$date = $obj->datev;
			print '<td>'.dol_print_date($date, 'day').'</td>';
			// Expected to pay
			print '<td class="right">'.price($obj->total).'</td>';
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
	    //print '<td align="center" class="liste_total">&nbsp;</td>';
	    print '<td align="center" class="liste_total">&nbsp;</td>';
		print '<td align="center" class="liste_total">&nbsp;</td>';
		print '<td align="center" class="liste_total">&nbsp;</td>';
	    print '<td class="liste_total right">'.price($totalpaye)."</td>";
		print "</tr>";
	}
	else
	{
		dol_print_error($db);
	}
	print '</table>';
}

// VAT
if (!empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	if (!$mode || $mode != 'tvaonly')
	{
		print "<br>";

		$tva = new Tva($db);

		print load_fiche_titre($langs->trans("VATPayments").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

		$sql = "SELECT pv.rowid, pv.amount, pv.label, pv.datev as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as pv";
		$sql .= " WHERE pv.entity = ".$conf->entity;
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

		        print '<td class="center">'.dol_print_date($db->jdate($obj->dm), 'day')."</td>\n";
		        print '<td class="right">'.price($obj->amount)."</td>";
		        print "</tr>\n";

		        $i++;
		    }
		    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
		    print '<td class="right">'.price($total).'</td>';
		    print '<td align="center">&nbsp;</td>';
		    print '<td align="center">&nbsp;</td>';
		    print '<td class="right">'.price($total)."</td>";
		    print "</tr>";

		    print "</table>";
		    $db->free($result);
		}
		else
		{
		    dol_print_error($db);
		}
	}
}

// Localtax
if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1")
{
	$j = 1;
	$numlt = 3;
}
elseif ($mysoc->localtax1_assuj == "1")
{
	$j = 1;
	$numlt = 2;
}
elseif ($mysoc->localtax2_assuj == "1")
{
	$j = 2;
	$numlt = 3;
}
else
{
	$j = 0;
	$numlt = 0;
}

while ($j < $numlt)
{
	if (!$mode || $mode != 'tvaonly')
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
		}
		else
		{
			dol_print_error($db);
		}
	}
	$j++;
}


// Payment Salary
if (!empty($conf->salaries->enabled) && !empty($user->rights->salaries->read))
{
    if (!$mode || $mode != 'tvaonly')
    {
        $sal = new PaymentSalary($db);

        print "<br>";

        print load_fiche_titre($langs->trans("SalariesPayments").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

        $sql = "SELECT s.rowid, s.amount, s.label, s.datep as datep, s.datev as datev, s.datesp, s.dateep, s.salary, u.salary as current_salary";
        $sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as s, ".MAIN_DB_PREFIX."user as u";
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
            print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "s.amount", "", $param, 'class="right"', $sortfield, $sortorder);
            print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "s.rowid", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "s.datep", "", $param, 'align="center"', $sortfield, $sortorder);
            print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "s.amount", "", $param, 'class="right"', $sortfield, $sortorder);
            print "</tr>\n";

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                $total = $total + $obj->amount;

                print '<tr class="oddeven">';

                print '<td class="left">'.dol_print_date($db->jdate($obj->dateep), 'day').'</td>'."\n";

                print "<td>".$obj->label."</td>\n";

                print '<td class="right">'.($obj->salary ?price($obj->salary) : '')."</td>";

                // Ref payment
                $sal_static->id = $obj->rowid;
                $sal_static->ref = $obj->rowid;
                print '<td class="left">'.$sal_static->getNomUrl(1)."</td>\n";

                print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>\n";
                print '<td class="right">'.price($obj->amount)."</td>";
                print "</tr>\n";

                $i++;
            }
            print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
            print '<td class="right"></td>'; // A total here has no sense
            print '<td align="center">&nbsp;</td>';
            print '<td align="center">&nbsp;</td>';
            print '<td class="right">'.price($total)."</td>";
            print "</tr>";

            print "</table>";
            $db->free($result);

            print "<br>";
        }
        else
        {
            dol_print_error($db);
        }
    }
}

print '</form>';

// End of page
llxFooter();
$db->close();
