<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       htdocs/user/group/index.php
        \brief      Page d'accueil de la gestion des groupes
        \version    $Revision$
*/

require("./pre.inc.php");


if (! $user->rights->user->user->lire && ! $user->admin) accessforbidden();

$langs->load("users");

$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page < 0) $page = 0;

$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (! $sortfield) $sortfield="g.nom";
if (! $sortorder) $sortorder="ASC";


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("ListOfGroups"));

$sql = "SELECT g.rowid, g.nom, g.entity, ".$db->pdate("g.datec")." as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
$sql .= " WHERE g.entity IN (0,".$conf->entity.")";
if ($_POST["search_group"])
{
    $sql .= " AND (g.nom like '%".$_POST["search_group"]."%' OR g.note like '%".$_POST["search_group"]."%')";
}
if ($sall) $sql.= " AND (g.nom like '%".$sall."%' OR g.note like '%".$sall."%')";
if ($sortfield)
{
    $sql .= " ORDER BY ".$sortfield." ".$sortorder;
}
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print "<br>";

    $param="search_group=$search_group&amp;sall=$sall";
    print "<table class=\"noborder\" width=\"100%\">";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Group"),"index.php","g.nom",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateCreation"),"index.php","g.datec",$param,"","",$sortfield,$sortorder);
    print "</tr>\n";
    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr $bc[$var]>";
        print '<td><a href="fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$obj->nom.'</a>';
        if (!$obj->entity)
        {
        	print img_redstar($langs->trans("GlobalGroup"));
        }
        print "</td>";
        print '<td width="100" align="center">'.dol_print_date($obj->datec,"day").'</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free();
}
else
{
    dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
