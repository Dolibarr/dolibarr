<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
   \file       htdocs/product/stock/index.php
   \ingroup    stock
   \brief      Page accueil stocks produits
   \version    $Revision$
*/

require_once("./pre.inc.php");
require_once("./entrepot.class.php");

$user->getrights();

$langs->load("stocks");

if (!$user->rights->stock->lire)
  accessforbidden();

llxHeader("","",$langs->trans("Stocks"));

print_fiche_titre($langs->trans("StocksArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche entrepot
 */
print '<form method="post" action="'.DOL_URL_ROOT.'/product/stock/liste.php">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").':</td><td><input class="flat" type="text" size="18" name="sf_ref"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "<tr $bc[0]><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
print "</table></form><br>";

$sql = "SELECT e.label, e.rowid, e.statut FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql .= " WHERE statut in (0,1) ORDER BY e.statut DESC ";
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
            print '<td align="right">'.$entrepot->LibStatut($objp->statut,3).'</td>';
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

print '</td><td valign="top" width="70%" class="notopnoleft">';


print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
