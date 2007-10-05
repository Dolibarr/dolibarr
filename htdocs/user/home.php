<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/user/home.php
        \brief      Page acceuil de la zone utilisateurs et groupes
        \version    $Revision$
*/

require("./pre.inc.php");

if (! $user->rights->user->user->lire && !$user->admin)
{
  // Redirection vers la page de l'utilisateur
  Header("Location: fiche.php?id=".$user->id);
}

$langs->load("users");



llxHeader();


print_fiche_titre($langs->trans("MenuUsersAndGroups"));


print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

// Recherche User
$var=false;
print '<form method="post" action="'.DOL_URL_ROOT.'/user/index.php">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAUser").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").'</td><td><input class="flat" type="text" name="search_user" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td></tr>';
print "</table><br>\n";
print '</form>';

// Recherche Group
$var=false;
print '<form method="post" action="'.DOL_URL_ROOT.'/user/group/index.php">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAGroup").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input class="flat" type="text" name="search_group" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td></tr>';
print "</table><br>\n";
print '</form>';

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Derniers utilisateurs créés
 */
$max=10;

$sql = "SELECT u.rowid, u.name, u.firstname, u.admin, u.login, u.fk_societe, ".$db->pdate("u.datec")." as datec,";
$sql.= " u.ldap_sid, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_societe = s.rowid";
$sql.= " ORDER BY u.datec";
$sql.= " DESC limit $max";

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastUsersCreated",min($num,$max)).'</td></tr>';
    $var = true;
    $i = 0;

    while ($i < $num && $i < $max)
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr $bc[$var]>";
        print "<td><a href=\"".DOL_URL_ROOT."/user/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowUser"),"user")." ".$obj->firstname." ".$obj->name."</a>";
        if ($obj->admin) print img_picto($langs->trans("Administrator"),'star');
        print "</td>";
        print "<td align=\"left\">".$obj->login.'</td>';
        print "<td>";
        if ($obj->fk_societe)
        {
            print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_societe.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a>';
        }
        else if ($obj->ldap_sid)
        {
        	print $langs->trans("DomainUser");
        }
        else print $langs->trans("InternalUser");
        print '</td>';
        print "<td width=\"80\" align=\"center\">".dolibarr_print_date($obj->datec)."</td>";
        print '</tr>';
        $i++;
    }
    print "</table><br>";

    $db->free($resql);
}
else
{
    dolibarr_print_error($db);
}


/*
 * Derniers groupes créés
 */
$max=5;

$sql = "SELECT g.rowid, g.nom, g.note, ".$db->pdate("g.datec")." as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
$sql .= " ORDER BY g.datec DESC";
if ($max) $sql .= " LIMIT $max";

if ( $db->query($sql) )
{
    $num = $db->num_rows();
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastGroupsCreated",$max).'</td></tr>';
    $var = true;
    $i = 0;

    while ($i < $num && (! $max || $i < $max))
    {
        $obj = $db->fetch_object();
        $var=!$var;

        print "<tr $bc[$var]>";
        print "<td><a href=\"".DOL_URL_ROOT."/user/group/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowGroup"),"group")." ".$obj->nom."</a></td>";
        print "<td width=\"80\" align=\"center\">".dolibarr_print_date($obj->datec)."</td>";
        print '</tr>';
        $i++;
    }
    print "</table><br>";

    $db->free();
}
else
{
    dolibarr_print_error($db);
}


print '</td></tr>';
print '</table>';

$db->close();


llxFooter('$Date$ - $Revision$');
?>
