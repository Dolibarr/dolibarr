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
 */

/**
     	\file       htdocs/fichinter/index.php
		\brief      Page accueil espace fiches interventions
		\ingroup    ficheinter
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/fichinter.class.php");

$langs->load("interventions");

if ($user->societe_id > 0)
{
  $socid = $user->societe_id ;
}


llxHeader();

$sortorder=$_GET["sortorder"]?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=$_GET["sortfield"]?$_GET["sortfield"]:$_POST["sortfield"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datei";
if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



$sql = "SELECT s.nom,s.idp, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut, f.note, f.duree";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f ";
$sql .= " WHERE f.fk_soc = s.idp ";


if ($socid > 0)
{
  $sql .= " AND s.idp = " . $socid;
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);

$result=$db->query($sql);
if ($result)
{
    $fichinter_static=new Fichinter($db);

    $num = $db->num_rows($result);
    print_barre_liste($langs->trans("ListOfInterventions"), $page, "index.php","&amp;socid=$socid",$sortfield,$sortorder,'',$num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Ref"),"index.php","f.ref","","&amp;socid=$socid",'width="15%"',$sortfield);
    print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","&amp;socid=$socid",'',$sortfield);
    print '<td>'.$langs->trans("Description").'</td>';
    print_liste_field_titre($langs->trans("Date"),"index.php","f.datei","","&amp;socid=$socid",'align="center"',$sortfield);
    print '<td align="right">'.$langs->trans("Duration").'</td>';
    print '<td align="center">'.$langs->trans("Status").'</td>';
    print "</tr>\n";
    $var=True;
    $total = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $var=!$var;
        print "<tr $bc[$var]>";
        print "<td><a href=\"fiche.php?id=$objp->fichid\">".img_object($langs->trans("Show"),"task").' '.$objp->ref."</a></td>\n";
        print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44)."</a></td>\n";
        print '<td>'.nl2br($objp->note).'</td>';
        print '<td align="center">'.dolibarr_print_date($objp->dp)."</td>\n";
        print '<td align="right">'.price($objp->duree).'</td>';
        print '<td align="center">'.$fichinter_static->LibStatut($objp->fk_statut).'</td>';

        print "</tr>\n";
        $total += $objp->duree;
        $i++;
    }
    print '<tr class="liste_total"><td colspan="3"></td><td>'.$langs->trans("Total").'</td>';
    print '<td align="right" nowrap>'.price($total).'</td><td></td>';
    print '</tr>';

    print '</table>';
    $db->free($result);
}
else
{
    dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
