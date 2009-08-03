<?PHP
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
    \file       htdocs/fourn/commande/liste.php
    \ingroup    fournisseur
    \brief      Liste des commandes fournisseurs
    \version    $Id$
*/

require("./pre.inc.php");

$langs->load("orders");

$sref=isset($_GET['search_ref'])?$_GET['search_ref']:$_POST['search_ref'];
$snom=isset($_GET['search_nom'])?$_GET['search_nom']:$_POST['search_nom'];
$suser=isset($_GET['search_user'])?$_GET['search_user']:$_POST['search_user'];
$sttc=isset($_GET['search_ttc'])?$_GET['search_ttc']:$_POST['search_ttc'];
$sall=isset($_GET['search_all'])?$_GET['search_all']:$_POST['search_all'];

$page  = ( is_numeric($_GET["page"]) ?  $_GET["page"] : 0 );
$socid = ( is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

// Security check
$orderid = isset($_GET["orderid"])?$_GET["orderid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $orderid,'');


/*
*	View
*/

$title = $langs->trans("SuppliersOrders");
if ($socid > 0)
{
  $fourn = new Fournisseur($db);
  $fourn->fetch($socid);
  $title .= ' (<a href="liste.php">'.$fourn->nom.'</a>)';
}

llxHeader('',$title);

$commandestatic=new CommandeFournisseur($db);


if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="cf.date_creation";
$offset = $conf->liste_limit * $page ;


/*
 * Mode Liste
 */

$sql = "SELECT s.rowid as socid, s.nom, ".$db->pdate("cf.date_commande")." as dc";
$sql.= ", cf.rowid,cf.ref, cf.fk_statut, cf.total_ttc, cf.fk_user_author";
$sql.= ", u.login";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= "  LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql.= " WHERE cf.fk_soc = s.rowid ";
$sql.= " AND s.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($sref)
{
	$sql.= " AND cf.ref LIKE '%".addslashes($sref)."%'";
}
if ($snom)
{
	$sql.= " AND s.nom LIKE '%".addslashes($snom)."%'";
}
if ($suser)
{
	$sql.= " AND u.login LIKE '%".addslashes($suser)."%'";
}
if ($sttc)
{
    $sql .= " AND ROUND(total_ttc) = ROUND(".price2num($sttc).")";
}
if ($sall)
{
	$sql.= " AND (cf.ref like '%".addslashes($sall)."%' OR cf.note like '%".addslashes($sall)."%')";
}
if ($socid) $sql.= " AND s.rowid = ".$socid;

if (strlen($_GET["statut"]))
{
    $sql .= " AND fk_statut =".$_GET["statut"];
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
    print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.login","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"total_ttc","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("OrderDate"),$_SERVER["PHP_SELF"],"dc","","",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"cf.fk_statut","","",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

    print '<tr class="liste_titre">';

    print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$sref.'"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_nom" value="'.$snom.'"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_user" value="'.$suser.'"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_ttc" value="'.$sttc.'"></td>';
    print '<td colspan="2" class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print '</td>';
    print '</tr>';

    $var=true;
    
    $userstatic = new User($db);

    while ($i < min($num,$conf->liste_limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr $bc[$var]>";

        // Ref
        print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>'."\n";

        // Company
        print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' ';
        print $obj->nom.'</a></td>'."\n";
        
        // Author
        $userstatic->id=$obj->fk_user_author;
        $userstatic->login=$obj->login;
        print "<td>";
        if ($userstatic->id) print $userstatic->getLoginUrl(1);
        else print "&nbsp;";
        print "</td>";
        
        // Amount
        print '<td align="right" width="100">'.price($obj->total_ttc)."</td>";

        // Date
        print "<td align=\"center\" width=\"100\">";
        if ($obj->dc)
        {
            print dol_print_date($obj->dc,"day");
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
    dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
