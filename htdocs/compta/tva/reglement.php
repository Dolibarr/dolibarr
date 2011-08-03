<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/compta/tva/reglement.php
 *      \ingroup    tax
 *		\brief      List of VAT payments
 *		\version    $Id: reglement.php,v 1.33 2011/08/03 00:46:25 eldy Exp $
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/compta/tva/class/tva.class.php");

$langs->load("compta");
$langs->load("compta");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

llxHeader();

$tva_static = new Tva($db);

print_fiche_titre($langs->trans("VATPayments"));

$sql = "SELECT rowid, amount, label, f.datev as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."tva as f ";
$sql.= " WHERE f.entity = ".$conf->entity;
$sql.= " ORDER BY dm DESC";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0 ;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td nowrap align="left">'.$langs->trans("Ref").'</td>';
    print "<td>".$langs->trans("Label")."</td>";
    print '<td nowrap align="left">'.$langs->trans("DatePayment").'</td>';
    print "<td align=\"right\">".$langs->trans("PayedByThisPayment")."</td>";
    print "</tr>\n";
    $var=1;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;
        print "<tr $bc[$var]>";

		$tva_static->id=$obj->rowid;
		$tva_static->ref=$obj->rowid;
		print "<td>".$tva_static->getNomUrl(1)."</td>\n";
        print "<td>".dol_trunc($obj->label,40)."</td>\n";
        print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day')."</td>\n";
        $total = $total + $obj->amount;

        print "<td align=\"right\">".price($obj->amount)."</td>";
        print "</tr>\n";

        $i++;
    }
    print '<tr class="liste_total"><td colspan="3">'.$langs->trans("Total").'</td>';
    print "<td align=\"right\"><b>".price($total)."</b></td></tr>";

    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}


$db->close();

llxFooter('$Date: 2011/08/03 00:46:25 $ - $Revision: 1.33 $');
?>
