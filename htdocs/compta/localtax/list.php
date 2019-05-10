<?php
/* Copyright (C) 2011-2014		Juanjo Menent <jmenent@2byte.es>
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
 *	    \file       htdocs/compta/localtax/list.php
 *      \ingroup    tax
 *		\brief      List of IRPF payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

// Load translation files required by the page
$langs->load("compta");

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');
$ltt=GETPOST("localTaxType");


/*
 * View
 */

llxHeader();

$localtax_static = new Localtax($db);

$newcardbutton='';
if ($user->rights->tax->charges->creer)
{
    $newcardbutton.= dolGetButtonTitle($langs->trans('NewLocalTaxPayment', ($ltt+1)), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/localtax/card.php?action=create&localTaxType='.$ltt);
}

print load_fiche_titre($langs->transcountry($ltt==2?"LT2Payments":"LT1Payments", $mysoc->country_code), $newcardbutton);

$sql = "SELECT rowid, amount, label, f.datev, f.datep";
$sql.= " FROM ".MAIN_DB_PREFIX."localtax as f ";
$sql.= " WHERE f.entity = ".$conf->entity." AND localtaxtype=".$db->escape($ltt);
$sql.= " ORDER BY datev DESC";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0 ;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td class="nowrap" align="left">'.$langs->trans("Ref").'</td>';
    print "<td>".$langs->trans("Label")."</td>";
    print "<td>".$langs->trans("PeriodEndDate")."</td>";
    print '<td class="nowrap" align="left">'.$langs->trans("DatePayment").'</td>';
    print "<td align=\"right\">".$langs->trans("PayedByThisPayment")."</td>";
    print "</tr>\n";
    $var=1;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);

        print '<tr class="oddeven">';

		$localtax_static->id=$obj->rowid;
		$localtax_static->ref=$obj->rowid;
		print "<td>".$localtax_static->getNomUrl(1)."</td>\n";
        print "<td>".dol_trunc($obj->label, 40)."</td>\n";
        print '<td class="left">'.dol_print_date($db->jdate($obj->datev), 'day')."</td>\n";
        print '<td class="left">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>\n";
        $total = $total + $obj->amount;

        print "<td align=\"right\">".price($obj->amount)."</td>";
        print "</tr>\n";

        $i++;
    }
    print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Total").'</td>';
    print '<td class="right">'.price($total).'</td></tr>';

    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
