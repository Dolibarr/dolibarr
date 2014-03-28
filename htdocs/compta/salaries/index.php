<?php
/* Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 *	    \file       htdocs/compta/salaries/index.php
 *      \ingroup    salaries
 *		\brief     	List of salaries payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';

$langs->load("compta");
$langs->load("salaries");
$langs->load("bills");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

llxHeader();

$salstatic = new PaymentSalary($db);
$userstatic = new User($db);


print_fiche_titre($langs->trans("SalariesPayments"));

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, s.rowid, s.fk_user, s.amount, s.label, s.datev as dm, s.num_payment,";
$sql.= " pst.code as payment_code";
$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON s.fk_typepayment = pst.id,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE u.rowid = s.fk_user";
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " ORDER BY dm DESC";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0 ;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td class="nowrap" align="left">'.$langs->trans("Ref").'</td>';
    print "<td>".$langs->trans("Person")."</td>";
    print "<td>".$langs->trans("Label")."</td>";
    print '<td class="nowrap" align="left">'.$langs->trans("DatePayment").'</td>';
    print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"c.libelle","",$paramlist,"",$sortfield,$sortorder);
    print "<td align=\"right\">".$langs->trans("PayedByThisPayment")."</td>";
    print "</tr>\n";
    $var=1;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;
        print "<tr ".$bc[$var].">";

        $userstatic->id=$obj->uid;
        $userstatic->lastname=$obj->lastname;
        $userstatic->firstname=$obj->firstname;
        $salstatic->id=$obj->rowid;
		$salstatic->ref=$obj->rowid;
        print "<td>".$salstatic->getNomUrl(1)."</td>\n";
		print "<td>".$userstatic->getNomUrl(1)."</td>\n";
        print "<td>".dol_trunc($obj->label,40)."</td>\n";
        print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day')."</td>\n";
        // Type
        print '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';
		// Amount
        print "<td align=\"right\">".price($obj->amount,0,$outputlangs,1,-1,-1,$conf->currency)."</td>";
        print "</tr>\n";

        $total = $total + $obj->amount;
        
        $i++;
    }
    print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").'</td>';
    print '<td  class="liste_total" align="right">'.price($total,0,$outputlangs,1,-1,-1,$conf->currency)."</td></tr>";

    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}


$db->close();

llxFooter();
?>
