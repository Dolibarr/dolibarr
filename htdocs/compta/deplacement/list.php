<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/compta/deplacement/list.php
 *  \brief      Page to list trips and expenses
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/class/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement','','');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.dated";
$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref');


/*
 * View
 */

$tripandexpense_static=new Deplacement($db);

llxHeader();

$sql = "SELECT s.nom, s.rowid as socid,";				// Ou
$sql.= " d.rowid, d.type, d.dated as dd, d.km,";		// Comment
$sql.= " d.fk_statut,";
$sql.= " u.name, u.firstname";							// Qui
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= ", ".MAIN_DB_PREFIX."deplacement as d";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON d.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
$sql.= " WHERE d.fk_user = u.rowid";
$sql.= " AND d.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if (trim($search_ref) != '')
{
    $sql.= ' AND d.rowid LIKE \'%'.$db->escape(trim($search_ref)) . '%\'';
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1 ,$offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    print_barre_liste($langs->trans("ListOfFees"), $page, $_SERVER["PHP_SELF"],"&socid=$socid",$sortfield,$sortorder,'',$num);

    $i = 0;
    print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"d.rowid","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"d.type","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"d.dated","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"u.name","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("FeesKilometersOrAmout"),$_SERVER["PHP_SELF"],"d.km","","&socid=$socid",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"], '');
    print "</tr>\n";

    // Filters lines
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="10" type="text" name="search_company" value="'.$search_company.'">';
    print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="10" type="text" name="search_name" value="'.$search_name.'">';
    print '</td>';
    print '<td class="liste_titre" align="left">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print "</td></tr>\n";

    $var=true;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);

        $soc = new Societe($db);
        if ($obj->socid) $soc->fetch($obj->socid);

        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td><a href="fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowTrip"),"trip").' '.$obj->rowid.'</a></td>';
        print '<td>'.$langs->trans($obj->type).'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->dd),'day').'</td>';
        print '<td align="left"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->firstname.' '.$obj->name.'</a></td>';
        if ($obj->socid) print '<td>'.$soc->getNomUrl(1).'</td>';
        else print '<td>&nbsp;</td>';
        print '<td align="right">'.$obj->km.'</td>';

        $tripandexpense_static->statut=$obj->fk_statut;
        print '<td align="right">'.$tripandexpense_static->getLibStatut(5).'</td>';
        print "</tr>\n";

        $i++;
    }

    print "</table>";
    print "</form>\n";
    $db->free($resql);
}
else
{
    dol_print_error($db);
}
$db->close();

llxFooter();
?>
