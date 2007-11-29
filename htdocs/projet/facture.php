<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/projet/facture.php
        \ingroup    projet facture
		\brief      Page des factures par projet
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("bills");

// Sécurité accés client
$projetid='';
if ($_GET["id"]) { $projetid=$_GET["id"]; }

if ($projetid == '') accessforbidden();

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'projet', $projetid);

llxHeader("","../");

$projet = new Project($db);
$projet->fetch($_GET["id"]);
$projet->societe->fetch($projet->societe->id);

$head=project_prepare_head($projet);
dolibarr_fiche_head($head, 'invoice', $langs->trans("Project"));


print '<table class="border" width="100%">';

print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';      

print '<tr><td>'.$langs->trans("Company").'</td><td>'.$projet->societe->getNomUrl(1,'compta').'</td></tr>';

print '</table>';

print '</div>';

/*
 * Barre d'action
 *
 */
 print '<div class="tabsAction">';

 if ($conf->facture->enabled && $user->rights->facture->creer)
 {
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddBill").'</a>';
 }
 print '</div>';

/*
 * Factures
 */
$factures = $projet->get_facture_list();
$total = 0;
if (sizeof($factures)>0 && is_array($factures))
{
    print '<br>';

    print_titre($langs->trans("ListInvoicesAssociatedProject"));
    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    print '<td width="15%">'.$langs->trans("Ref").'</td><td width="25%">'.$langs->trans("Date").'</td><td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';

    for ($i = 0; $i<sizeof($factures);$i++)
    {
        $facture = new Facture($db);
        $facture->fetch($factures[$i]);

        $var=!$var;
        print "<tr $bc[$var]>";
        print "<td><a href=\"../compta/facture.php?facid=$facture->id\">$facture->ref</a></td>\n";
        print '<td>'.dolibarr_print_date($facture->date,'day').'</td>';
        print '<td align="right">'.price($facture->total_ht).'</td><td>&nbsp;</td></tr>';

        $total = $total + $facture->total_ht;
    }

    print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Bills").'</td>';
    print '<td align="right">'.$langs->trans("TotalHT").' : '.price($total).'</td>';
    print '<td align="left">'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print "</table>";
}


// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
