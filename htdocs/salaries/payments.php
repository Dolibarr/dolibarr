<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2011-2014 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *      \file       htdocs/compta/sociales/payments.php
 *      \ingroup    compta
 *		\brief      Page to list payments of special expenses
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
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
if (!$year && $mode != 'sconly') { $year = date("Y", time()); }

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "pc.datep";
if (!$sortorder) $sortorder = "DESC";


/*
 * View
 */

$payment_salary_static = new PaymentSalary($db);
$sal_static = new Salary($db);

llxHeader('', $langs->trans("SalariesArea"));

$title = $langs->trans("SalariesPayments");
if ($mode == 'sconly') $title = $langs->trans("PaymentsSalaries");

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
if ($mode == 'sconly') $param = '&mode=sconly';
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

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $totalnboflines, 'title_accountancy', 0, '', '', $limit);

if ($year) $param .= '&year='.$year;

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

// Payment Salary
if (!empty($conf->salaries->enabled) && !empty($user->rights->salaries->read))
{
    if (!$mode || $mode != 'sconly')
    {
        $sal = new Salary($db);

        $sql = "SELECT ps.rowid as payment_id, ps.amount, s.rowid as salary_id, s.label, ps.datep as datep, s.datesp, s.dateep, s.amount as salary, u.salary as current_salary, pct.code as payment_code";
        $sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as ps";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."salary as s ON (s.rowid = ps.fk_salary)";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON (u.rowid = s.fk_user)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON ps.fk_typepayment = pct.id";
        $sql .= " WHERE s.entity IN (".getEntity('user').")";
       /* if ($year > 0)
        {
            $sql .= " AND (s.datesp between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
            $sql .= " OR s.dateep between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."')";
        }*/
        if (preg_match('/^s\./', $sortfield)
			|| preg_match('/^pct\./', $sortfield)
			|| preg_match('/^ps\./', $sortfield)) $sql .= $db->order($sortfield, $sortorder);

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;
            $total = 0;
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
			print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "s.rowid", "", $param, '', $sortfield, $sortorder);
			print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "ps.datep", "", $param, 'align="center"', $sortfield, $sortorder);
			print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
			print_liste_field_titre("Salary", $_SERVER["PHP_SELF"], "s.rowid", "", $param, '', $sortfield, $sortorder);
			print_liste_field_titre("DateStart", $_SERVER["PHP_SELF"], "s.datesp", "", $param, 'width="140px"', $sortfield, $sortorder);
			print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "s.dateep", "", $param, 'width="140px"', $sortfield, $sortorder);
            print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "s.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "s.amount", "", $param, 'class="right"', $sortfield, $sortorder);
            print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "ps.amount", "", $param, 'class="right"', $sortfield, $sortorder);
            print "</tr>\n";

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                $total = $total + $obj->amount;

                print '<tr class="oddeven">';

				// Ref payment
				$payment_salary_static->id = $obj->payment_id;
				$payment_salary_static->ref = $obj->payment_id;
				print '<td class="left">'.$payment_salary_static->getNomUrl(1)."</td>\n";

				print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>\n";

				// Type payment
				print '<td>';
				if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
				print $obj->num_payment.'</td>';

				print '<td>';
				$sal_static->id = $obj->salary_id;
				$sal_static->ref = $obj->salary_id;
				$sal_static->label = $obj->label;
				print $sal_static->getNomUrl(1, '20');
				print '</td>';

				// Date début salaire
				print '<td class="left">'.dol_print_date($db->jdate($obj->datesp), 'day').'</td>'."\n";

				// Date fin salaire
				print '<td class="left">'.dol_print_date($db->jdate($obj->dateep), 'day').'</td>'."\n";

                print "<td>".$obj->label."</td>\n";

                print '<td class="right">'.($obj->salary ?price($obj->salary) : '')."</td>";
                print '<td class="right">'.price($obj->amount)."</td>";
                print "</tr>\n";

                $i++;
            }
            print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
            print '<td class="right"></td>'; // A total here has no sense
			print '<td align="center">&nbsp;</td>';
			print '<td align="center">&nbsp;</td>';
			print '<td align="center">&nbsp;</td>';
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
