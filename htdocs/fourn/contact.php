<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/fourn/contact.php
 *      \ingroup    fournisseur
 *		\brief      Liste des contacts fournisseurs
 */

require '../main.inc.php';

$langs->load("companies");


/*
 * View
 */

llxHeader();

// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.name";
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;


/*
 * Mode liste
 */

$sql = "SELECT s.rowid as socid, s.nom as name, st.libelle as stcomm, p.rowid as cidp, p.lastname, p.firstname, p.email, p.phone";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."socpeople as p, ".MAIN_DB_PREFIX."c_stcomm as st";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.fournisseur = 1";
$sql.= " AND s.rowid = p.fk_soc";
$sql.= " AND s.entity IN (".getEntity('societe').")";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if (dol_strlen($stcomm)) {
    $sql .= " AND s.fk_stcomm=$stcomm";
}

if (dol_strlen($begin)) {
    $sql .= " AND p.name LIKE '$begin%'";
}

if ($contactname) {
    $sql .= " AND p.name LIKE '%".strtolower($contactname)."%'";
    $sortfield = "p.name";
    $sortorder = "ASC";
}

if ($socid) {
    $sql .= " AND s.rowid = ".$socid;
}

$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit, $offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);

    $title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ListOfContacts") : $langs->trans("ListOfContactsAddresses"));
    print_barre_liste($title." (".$langs->trans("Suppliers").")",$page, $_SERVER["PHP_SELF"], "",$sortfield,$sortorder,"",$num);

    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre("Lastname",$_SERVER["PHP_SELF"],"p.name", $begin, "", "", $sortfield,$sortorder);
    print_liste_field_titre("Firstname",$_SERVER["PHP_SELF"],"p.firstname", $begin, "", "", $sortfield,$sortorder);
    print_liste_field_titre("Company",$_SERVER["PHP_SELF"],"s.nom", $begin, "", "", $sortfield,$sortorder);
    print_liste_field_titre("Email");
    print_liste_field_titre("Phone");
    print "</tr>\n";

    $i = 0;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);

        print '<tr class="oddeven">';

        print '<td><a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$obj->cidp.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$obj->lastname.'</a></td>';
        print '<td>'.$obj->firstname.'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->name.'</a></td>';
        print '<td>'.$obj->email.'</td>';
        print '<td>'.$obj->phone.'</td>';

        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
