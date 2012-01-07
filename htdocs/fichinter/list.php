<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/fichinter/list.php
 *	\brief      List of all interventions
 *	\ingroup    ficheinter
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load("companies");
$langs->load("interventions");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$socid=GETPOST("socid");
$page=GETPOST("page");

// Security check
$fichinterid = GETPOST("id");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid,'fichinter');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="fd.date";
if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_ref=GETPOST("search_ref");
$search_company=GETPOST("search_company");
$search_desc=GETPOST("search_desc");


/*
 *	View
 */

llxHeader();


$sql = "SELECT";
$sql.= " f.ref, f.rowid as fichid, f.fk_statut, f.description,";
$sql.= " fd.description as descriptiondetail, fd.date as dp, fd.duree,";
$sql.= " s.nom, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."fichinter as f)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON fd.fk_fichinter = f.rowid";
$sql.= " WHERE f.fk_soc = s.rowid ";
$sql.= " AND f.entity = ".$conf->entity;
if ($search_ref)     $sql .= " AND f.ref like '%".$db->escape($search_ref)."%'";
if ($search_company) $sql .= " AND s.nom like '%".$db->escape($search_company)."%'";
if ($search_desc)    $sql .= " AND (f.description like '%".$db->escape($search_desc)."%' OR fd.description like '%".$db->escape($search_desc)."%')";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = " . $socid;
$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$interventionstatic=new Fichinter($db);

	$urlparam="&amp;socid=$socid";
	print_barre_liste($langs->trans("ListOfInterventions"), $page, "index.php",$urlparam,$sortfield,$sortorder,'',$num);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.ref","",$urlparam,'width="15%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$urlparam,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"f.description","",$urlparam,'',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],'');
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"fd.date","",$urlparam,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Duration"),$_SERVER["PHP_SELF"],"fd.duree","",$urlparam,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.fk_statut","",$urlparam,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company" value="'.$search_company.'" size="10">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_desc" value="'.$search_desc.'" size="12">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";

	$companystatic=new Societe($db);

	$var=True;
	$total = 0;
	$i = 0;
	while ($i < min($num, $limit))
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td>";
		$interventionstatic->id=$objp->fichid;
		$interventionstatic->ref=$objp->ref;
		print $interventionstatic->getNomUrl(1);
		print "</td>\n";
		print '<td>';
		$companystatic->nom=$objp->nom;
		$companystatic->id=$objp->socid;
		$companystatic->client=$objp->client;
		print $companystatic->getNomUrl(1,'',44);
		print '</td>';
        print '<td>'.dol_htmlentitiesbr(dol_trunc($objp->description,20)).'</td>';
		print '<td>'.dol_htmlentitiesbr(dol_trunc($objp->descriptiondetail,20)).'</td>';
		print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'dayhour')."</td>\n";
		print '<td align="right">'.ConvertSecondToTime($objp->duree).'</td>';
		print '<td align="right">'.$interventionstatic->LibStatut($objp->fk_statut,5).'</td>';

		print "</tr>\n";

		$total += $objp->duree;
		$i++;
	}
	print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").'</td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.ConvertSecondToTime($total).'</td><td>&nbsp;</td>';
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

llxFooter();
?>
