<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 \file       htdocs/fichinter/index.php
 \brief      Page accueil espace fiches interventions
 \ingroup    ficheinter
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

$langs->load("companies");
$langs->load("interventions");

$sortorder=$_GET["sortorder"]?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=$_GET["sortfield"]?$_GET["sortfield"]:$_POST["sortfield"];
$socid=$_GET["socid"]?$_GET["socid"]:$_POST["socid"];
$page=$_GET["page"]?$_GET["page"]:$_POST["page"];

// Security check
$fichinterid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid,'');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="fd.date";
if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_ref=isset($_GET["search_ref"])?$_GET["search_ref"]:$_POST["search_ref"];
$search_company=isset($_GET["search_company"])?$_GET["search_company"]:$_POST["search_company"];
$search_desc=isset($_GET["search_desc"])?$_GET["search_desc"]:$_POST["search_desc"];


/*
 *	View
 */

llxHeader();


$sql = "SELECT";
$sql.= " f.ref, f.rowid as fichid, f.fk_statut, f.description,";
$sql.= " fd.description as descriptiondetail, ".$db->pdate("fd.date")." as dp, fd.duree,";
$sql.= " s.nom, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."fichinter as f)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON fd.fk_fichinter = f.rowid";
$sql.= " WHERE f.fk_soc = s.rowid ";
$sql.= " AND s.entity = ".$conf->entity;
if ($search_ref)     $sql .= " AND f.ref like '%".addslashes($search_ref)."%'";
if ($search_company) $sql .= " AND s.nom like '%".addslashes($search_company)."%'";
if ($search_desc)    $sql .= " AND (f.description like '%".addslashes($search_desc)."%' OR fd.description like '%".addslashes($search_desc)."%')";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = " . $socid;
$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit( $limit + 1 ,$offset);

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$fichinter_static=new Fichinter($db);

	$urlparam="&amp;socid=$socid";
	print_barre_liste($langs->trans("ListOfInterventions"), $page, "index.php",$urlparam,$sortfield,$sortorder,'',$num);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),"index.php","f.ref","",$urlparam,'width="15%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","",$urlparam,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),"index.php","f.description","",$urlparam,'',$sortfield,$sortorder);
	print '<td>&nbsp;</td>';
	print_liste_field_titre($langs->trans("Date"),"index.php","fd.date","",$urlparam,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Duration"),"index.php","fd.duree","",$urlparam,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"index.php","f.fk_statut","",$urlparam,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company" value="'.$search_company.'" size="10">';
	print '</td><td class="liste_titre" colspan="2">';
	print '<input type="text" class="flat" name="search_desc" value="'.$search_desc.'" size="24">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'"></td>';
	print "</tr>\n";


	$var=True;
	$total = 0;
	$i = 0;
	while ($i < min($num, $limit))
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td><a href=\"fiche.php?id=".$objp->fichid."\">".img_object($langs->trans("Show"),"task").' '.$objp->ref."</a></td>\n";
		print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,44)."</a></td>\n";
		print '<td>'.dol_htmlentitiesbr(dol_trunc($objp->description,20)).'</td>';
		print '<td>'.dol_htmlentitiesbr(dol_trunc($objp->descriptiondetail,20)).'</td>';
		print '<td align="center">'.dol_print_date($objp->dp,'dayhour')."</td>\n";
		print '<td align="right">'.ConvertSecondToTime($objp->duree).'</td>';
		print '<td align="right">'.$fichinter_static->LibStatut($objp->fk_statut,5).'</td>';

		print "</tr>\n";

		$total += $objp->duree;
		$i++;
	}
	print '<tr class="liste_total"><td colspan="4"></td><td align="center">'.$langs->trans("Total").'</td>';
	print '<td align="right" nowrap>'.ConvertSecondToTime($total).'</td><td></td>';
	print '</tr>';

	print '</table>';
	print "</form>\n";
	$db->free($result);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
