<?PHP
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * $Source$
 */

/** 
    \file       htdocs/fourn/commande/liste.php
    \ingroup    fournisseur
    \brief      Liste des commandes fournisseurs
    \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("orders");

$page  = ( is_numeric($_GET["page"]) ?  $_GET["page"] : 0 );
$socid = ( is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

$title = $langs->trans("SuppliersOrders");

if (!$user->rights->fournisseur->commande->lire) accessforbidden();

// Sécurité accés client/fournisseur
if ($user->societe_id > 0) $socid = $user->societe_id;


if ($socid > 0)
{
  $fourn = new Fournisseur($db);
  $fourn->fetch($socid);
  $title .= ' (<a href="liste.php">'.$fourn->nom.'</a>)';
}

/*
 * Affichage
 */

llxHeader('',$title);

$commandestatic=new CommandeFournisseur($db);


if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="cf.date_creation";
$offset = $conf->liste_limit * $page ;


/*
 * Mode Liste
 */

$sql = "SELECT s.rowid as socid, s.nom, ".$db->pdate("cf.date_commande")." as dc,";
$sql .= " cf.rowid,cf.ref, cf.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,";
$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
$sql .= " WHERE cf.fk_soc = s.rowid ";

if ($socid)
{
    $sql .= " AND s.rowid = ".$socid;
}

if (strlen($_GET["statut"]))
{
    $sql .= " AND fk_statut =".$_GET["statut"];
}

if (strlen($_GET["search_ref"]))
{
    $sql .= " AND cf.ref LIKE '%".$_GET["search_ref"]."%'";
}

if (strlen($_GET["search_nom"]))
{
    $sql .= " AND s.nom LIKE '%".$_GET["search_nom"]."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
   
    $num = $db->num_rows($resql);
    $i = 0;


    print_barre_liste($title, $page, "liste.php", "", $sortfield, $sortorder, '', $num);
    print '<form action="liste.php" method="GET">';
    print '<table class="liste">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"cf.ref","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("OrderDate"),$_SERVER["PHP_SELF"],"dc","","",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"cf.fk_statut","","",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

    print '<tr class="liste_titre">';

    print '<td><input type="text" class="flat" name="search_ref" value="'.$_GET["search_ref"].'"></td>';
    print '<td><input type="text" class="flat" name="search_nom" value="'.$_GET["search_nom"].'"></td>';
    print '<td colspan="2" class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print '</td>';
    print '</tr>';

    $var=true;

    while ($i < min($num,$conf->liste_limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr $bc[$var]>";

        // Ref
        print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>'."\n";

        // Société
        print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' ';
        print $obj->nom.'</a></td>'."\n";

        // Date
        print "<td align=\"center\" width=\"100\">";
        if ($obj->dc)
        {
            print dolibarr_print_date($obj->dc,"day");
        }
        else
        {
            print "-";
        }
        print '</td>';

        // Statut
        print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut, 5).'</td>';

        print "</tr>\n";
        $i++;
    }
    print "</table>\n";
    print "</form>\n";

    $db->free($resql);
}
else
{
    dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
