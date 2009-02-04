<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
	    \file       htdocs/compta/tva/reglement.php
        \ingroup    tax
		\brief      Liste des règlements de TVA effectués
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");

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

$sql = "SELECT rowid, amount, label, ".$db->pdate("f.datev")." as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."tva as f ";
$sql .= " ORDER BY dm DESC";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0 ;
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td nowrap align="left">'.$langs->trans("Ref").'</td>';
    print '<td nowrap align="left">'.$langs->trans("Date").'</td>';
    print "<td>".$langs->trans("Label")."</td>";
    print "<td align=\"right\">".$langs->trans("Amount")."</td>";
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
        print '<td align="left">'.dolibarr_print_date($obj->dm,'day')."</td>\n";
        print "<td>".$obj->label."</td>\n";
        $total = $total + $obj->amount;

        print "<td align=\"right\">".price($obj->amount)."</td>";
        print "</tr>\n";

        $i++;
    }
    print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("Total").'</td>';
    print "<td align=\"right\"><b>".price($total)."</b></td></tr>";

    print "</table>";
    $db->free($result);
}
else
{
    dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
