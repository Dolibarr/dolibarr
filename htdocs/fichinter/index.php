<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
     	\file       htdocs/fichinter/index.php
		\brief      Page accueil espace fiches interventions
		\ingroup    ficheinter
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/fichinter.class.php");

$langs->load("companies");
$langs->load("interventions");

$sortorder=$_GET["sortorder"]?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=$_GET["sortfield"]?$_GET["sortfield"]:$_POST["sortfield"];
$socid=$_GET["socid"]?$_GET["socid"]:$_POST["socid"];
$page=$_GET["page"]?$_GET["page"]:$_POST["page"];

// Security check
$fichinterid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid,'',1);

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datei";
if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
*	View
*/

llxHeader();


$sql = "SELECT s.nom,s.rowid as socid, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut, f.description, f.duree";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f ";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE f.fk_soc = s.rowid ";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid > 0)
{
	$sql .= " AND s.rowid = " . $socid;
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit( $limit + 1 ,$offset);

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);

    $fichinter_static=new Fichinter($db);
	
	$urlparam="&amp;socid=$socid";
    print_barre_liste($langs->trans("ListOfInterventions"), $page, "index.php",$urlparam,$sortfield,$sortorder,'',$num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Ref"),"index.php","f.ref","",$urlparam,'width="15%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","",$urlparam,'',$sortfield,$sortorder);
    print '<td>'.$langs->trans("Description").'</td>';
    print_liste_field_titre($langs->trans("Date"),"index.php","f.datei","",$urlparam,'align="center"',$sortfield);
    print '<td align="right">'.$langs->trans("Duration").'</td>';
    print '<td align="right">'.$langs->trans("Status").'</td>';
    print "</tr>\n";
    $var=True;
    $total = 0;
    while ($i < min($num, $limit))
    {
        $objp = $db->fetch_object($result);
        $var=!$var;
        print "<tr $bc[$var]>";
        print "<td><a href=\"fiche.php?id=".$objp->fichid."\">".img_object($langs->trans("Show"),"task").' '.$objp->ref."</a></td>\n";
        print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44)."</a></td>\n";
        print '<td>'.nl2br($objp->description).'</td>';
        print '<td align="center">'.dolibarr_print_date($objp->dp)."</td>\n";
        print '<td align="right">'.ConvertSecondToTime($objp->duree).'</td>';
        print '<td align="right">'.$fichinter_static->LibStatut($objp->fk_statut,5).'</td>';

        print "</tr>\n";
        $total += $objp->duree;
        $i++;
    }
    print '<tr class="liste_total"><td colspan="3"></td><td>'.$langs->trans("Total").'</td>';
    print '<td align="right" nowrap>'.ConvertSecondToTime($total).'</td><td></td>';
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
