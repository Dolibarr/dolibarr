<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/compta/commande/liste.php
        \ingroup    commande
        \brief      Page liste des commandes
        \version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->commande->lire) accessforbidden();

$begin=$_GET["begin"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="DESC";

$limit = $conf->liste_limit;
$offset = $limit * $_GET["page"] ;

// Sécurité accés client
$socid = $_GET["socid"];
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$langs->load('companies');

$html = new Form($db);

llxHeader();

$sql = "SELECT s.nom, s.rowid as socid,";
$sql.= " c.rowid, c.ref, c.total_ht,".$db->pdate("c.date_commande")." as date_commande,";
$sql.= " c.fk_statut, c.facture";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE c.fk_soc = s.rowid";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
{
    $sql .= " AND s.rowid = ".$socid;
}
if ($_GET["month"] > 0)
{
    $sql .= " AND date_format(c.date_commande, '%Y-%m') = '".$_GET["year"]."-".$_GET["month"]."'";
}
if ($_GET["year"] > 0)
{
    $sql .= " AND date_format(c.date_commande, '%Y') = '".$_GET["year"]."'";
}
if (isset($_GET["status"]))
{
    $sql .= " AND fk_statut = ".$_GET["status"];
}
if (isset($_GET["afacturer"]) && $_GET['afacturer'] == 1)
{
    $sql .= " AND fk_statut >=1	AND c.facture = 0";
}
if (strlen($_POST["sf_ref"]) > 0)
{
    $sql .= " AND c.ref like '%".$_POST["sf_ref"] . "%'";
}
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($limit + 1,$offset);
$resql = $db->query($sql);

if ($resql)
{
    if ($socid)
    {
        $soc = new Societe($db);
        $soc->fetch($socid);
        $title = $langs->trans("ListOfOrders") . " - ".$soc->nom;
    }
    else
    {
        $title = $langs->trans("ListOfOrders");
    }
    // Si page des commandes à facturer
    $link=DOL_URL_ROOT."/compta/commande/fiche.php";
    $title.=" - ".$langs->trans("StatusOrderToBill");
    $param="&amp;socid=".$socid."&amp;year=".$_GET["year"]."&amp;month=".$_GET["month"];

    $num = $db->num_rows($resql);
    print_barre_liste($title, $_GET["page"], "liste.php",$param,$sortfield,$sortorder,'',$num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),"liste.php","c.ref","",$param,'',$sortfield);
    print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom","",$param,'',$sortfield);
    print_liste_field_titre($langs->trans("Date"),"liste.php","c.date_commande","",$param, 'align="center"',$sortfield);
    print_liste_field_titre($langs->trans("Status"),"liste.php","c.fk_statut","",$param,'align="right"',$sortfield);
    print "</tr>\n";
    $var=True;

    $generic_commande = new Commande($db);

    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);

        $var=!$var;
        print "<tr $bc[$var]>";
        
        print '<td width="20%" nowrap="nowrap">';
        
        $generic_commande->id=$objp->rowid;
        $generic_commande->ref=$objp->ref;
        
        print '<table class="nobordernopadding"><tr class="nocellnopadd">';
        print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
        print $generic_commande->getNomUrl(1);
        print '</td>';
        
        print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
        if (($objp->date_commande < (time() - $conf->commande->traitement->warning_delay)) && $objp->statutid == 1 ) print img_picto($langs->trans("Late"),"warning");
        print '</td>';
        
        print '<td width="16" align="right" class="nobordernopadding">';
        $filename=sanitize_string($objp->ref);
        $filedir=$conf->commande->dir_output . '/' . sanitize_string($objp->ref);
        $urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
        $html->show_documents('commande',$filename,$filedir,$urlsource,'','','','','',1);
        print '</td></tr></table>';
        
        print '</td>';

        print "<td><a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid."\">".img_object($langs->trans("ShowCompany"),"company")." ".$objp->nom."</a>";
        print "</td>";

        print "<td align=\"center\">";
        $y = strftime("%Y",$objp->date_commande);
        $m = strftime("%m",$objp->date_commande);

        print strftime("%d",$objp->date_commande)."\n";
        print " <a href=\"liste.php?year=$y&amp;month=$m\">";
        print strftime("%B",$objp->date_commande)."</a>\n";
        print " <a href=\"liste.php?year=$y\">";
        print strftime("%Y",$objp->date_commande)."</a></td>\n";

		print '<td align="right">'.$generic_commande->LibStatut($objp->fk_statut,$objp->facture,5).'</td>';
        print "</tr>\n";

        $total = $total + $objp->price;
        $subtotal = $subtotal + $objp->price;

        $i++;
    }

    print "</table>";
    $db->free($resql);
}
else
{
    print dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
