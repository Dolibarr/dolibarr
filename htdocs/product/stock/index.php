<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
        \file       htdocs/product/stock/index.php
        \ingroup    stock
        \brief      Page accueil stocks produits
        \version    $Revision$
*/

require_once("./pre.inc.php");
require_once("./entrepot.class.php");

$user->getrights("stocks");
$langs->load("stocks");

if (!$user->rights->stock->lire)
  accessforbidden();


llxHeader("","",$langs->trans("Stocks"));

print_titre($langs->trans("StocksArea"));
print '<br>';


print '<table class="noborder" width="100%">';
print '<tr><td valign="top" width="30%">';


/*
 * Zone recherche entrepot
 */
print '<form method="post" action="liste.php">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").' :</td><td><input class="flat" type="text" size="20" name="sf_ref"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "</table></form><br>";




$sql = "SELECT e.label, e.rowid, e.statut FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql .= " ORDER BY e.statut DESC ";
$sql .= $db->plimit(15 ,0);
$result = $db->query($sql) ;

if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Warehouses").'</td></tr>';

    if ($num)
    {
        $entrepot=new Entrepot($db);

        $var=True;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            print "<tr $bc[$var]>";
            print "<td><a href=\"fiche.php?id=$objp->rowid\">".img_object($langs->trans("ShowStock"),"stock")." ".$objp->label."</a></td>\n";
            print '<td align="right">'.$entrepot->LibStatut($objp->statut).'</td>';
            print "</tr>\n";
            $i++;
        }
        $db->free($result);

    }
    print "</table>";
}
else
{
    dolibarr_print_error($db);
}

print '</td><td valign="top" width="70%">';


print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
