<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/user/index.php
        \brief      Page d'accueil de la gestion des utilisateurs
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("users");


llxHeader();


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if (! $sortfield) {
    $sortfield="u.name";
}

print_titre($langs->trans("ListOfUsers"));

$sql = "SELECT u.rowid, u.name, u.firstname, u.code, u.login, ".$db->pdate("u.datec")." as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
if ($sortfield) { $sql.=" ORDER BY $sortfield $sortorder"; }

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows();
    $i = 0;

    print "<br>";

    print "<table class=\"noborder\" width=\"100%\">";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Lastname"),"index.php","u.name","","","",$sortfield);
    print_liste_field_titre($langs->trans("Firstname"),"index.php","u.firstname","","","",$sortfield);
    print_liste_field_titre($langs->trans("Login"),"index.php","u.login","","","",$sortfield);
    print_liste_field_titre($langs->trans("Code"),"index.php","u.code","","","",$sortfield);
    print_liste_field_titre($langs->trans("DateCreation"),"index.php","u.datec","","","",$sortfield);
    print "</tr>\n";
    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object( $i);
        $var=!$var;

        print "<tr $bc[$var]>";
        print '<td>'.ucfirst($obj->name).'</td>';
        print '<td>'.ucfirst($obj->firstname).'</td>';
        if ($obj->login)
        {
            print '<td><a href="fiche.php?id='.$obj->rowid.'">'.img_file().' '.$obj->login.'</a></td>';
        }
        else
        {
            print '<td><a class="impayee" href="fiche.php?id='.$obj->rowid.'">'.img_file().' Inactif</a></td>';
        }
        print '<td>'.$obj->code.'</td>';
        print '<td width="100" align="center">'.dolibarr_print_date($obj->datec,"%d %b %Y").'</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free();
}
else
{
    dolibarr_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
