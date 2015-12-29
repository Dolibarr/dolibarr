<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015           Jean-François Ferry		<jfefe@aternatik.fr>
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
 *	\file       htdocs/fichinter/list.php
 *	\brief      List of all interventions
 *	\ingroup    ficheinter
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("interventions");

$socid=GETPOST('socid','int');

// Security check
$fichinterid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid,'fichinter');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield)
{
 	if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sortfield="fd.date";
 	else $sortfield="f.ref";
}

$search_ref=GETPOST('search_ref')?GETPOST('search_ref','alpha'):GETPOST('search_inter','alpha');
$search_company=GETPOST('search_company','alpha');
$search_desc=GETPOST('search_desc','alpha');
$search_status=GETPOST('search_status');
$sall=GETPOST('sall');
$optioncss = GETPOST('optioncss','alpha');

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_company="";
	$search_desc="";
	$search_status="";
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'f.ref'=>'Ref',
    's.nom'=>"ThirdParty",
    'f.description'=>'Description',
    'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";
if (! empty($conf->global->FICHINTER_DISABLE_DETAILS)) unset($fieldstosearchall['f.description']);


/*
 *	View
 */

$form = new Form($db);
$interventionstatic=new Fichinter($db);

llxHeader('', $langs->trans("Intervention"));


$sql = "SELECT";
$sql.= " f.ref, f.rowid as fichid, f.fk_statut, f.description,";
if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql.= " fd.description as descriptiondetail, fd.date as dp, fd.duree,";
$sql.= " s.nom as name, s.rowid as socid, s.client";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && empty($socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."fichinter as f)";
if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON fd.fk_fichinter = f.rowid";
$sql.= " WHERE f.fk_soc = s.rowid ";
$sql.= " AND f.entity = ".$conf->entity;
if ($search_ref) {
    $sql .= natural_search('f.ref', $search_ref);
}
if ($search_company) {
    $sql .= natural_search('s.nom', $search_company);
}
if ($search_desc) {
    if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql .= natural_search(array('f.description', 'fd.description'), $search_desc);
    else $sql .= natural_search(array('f.description'), $search_desc);
}
if ($search_status != '' && $search_status >= 0) {
    $sql .= ' AND f.fk_statut = '.$search_status;
}
if (! $user->rights->societe->client->voir && empty($socid))
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
	$sql.= " AND s.rowid = " . $socid;
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);
//print $sql;

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$urlparam='';
	if ($socid) $urlparam.="&socid=".$socid;
	if ($search_ref) $urlparam.="&search_ref=".urlencode($search_ref);
	if ($search_company) $urlparam.="&search_company=".urlencode($search_company);
	if ($search_desc) $urlparam.="&search_desc=".urlencode($search_desc);
	if ($search_status != '' && $search_status > -1) $urlparam.="&search_status=".urlencode($search_status);
	if ($optioncss != '') $urlparam.='&optioncss='.$optioncss;

	print_barre_liste($langs->trans("ListOfInterventions"), $page, $_SERVER['PHP_SELF'], $urlparam, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_commercial.png');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
    print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.ref","",$urlparam,'width="15%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$urlparam,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"f.description","",$urlparam,'',$sortfield,$sortorder);
	if (empty($conf->global->FICHINTER_DISABLE_DETAILS))
	{
		print_liste_field_titre('',$_SERVER["PHP_SELF"],'');
		print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"fd.date","",$urlparam,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Duration"),$_SERVER["PHP_SELF"],"fd.duree","",$urlparam,'align="right"',$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.fk_statut","",$urlparam,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company" value="'.$search_company.'" size="10">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_desc" value="'.$search_desc.'" size="12">';
	print '</td>';
    if (empty($conf->global->FICHINTER_DISABLE_DETAILS))
	{
		// Desc of line
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre">&nbsp;</td>';
	}
	print '<td class="liste_titre" align="right">';
	$liststatus=$interventionstatic->statuts_short;
	print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 1);
	print '</td>';
	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td></tr>\n";

	$companystatic=new Societe($db);

	$var=True;
	$total = 0;
	$i = 0;
	while ($i < min($num, $limit))
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td>";
		$interventionstatic->id=$objp->fichid;
		$interventionstatic->ref=$objp->ref;
		print $interventionstatic->getNomUrl(1);
		print "</td>\n";
		print '<td>';
		$companystatic->name=$objp->name;
		$companystatic->id=$objp->socid;
		$companystatic->client=$objp->client;
		print $companystatic->getNomUrl(1,'',44);
		print '</td>';
        print '<td>'.dol_htmlentitiesbr(dol_trunc($objp->description,20)).'</td>';
		if (empty($conf->global->FICHINTER_DISABLE_DETAILS))
		{
			print '<td>'.dol_htmlentitiesbr(dol_trunc($objp->descriptiondetail,20)).'</td>';
			print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'dayhour')."</td>\n";
			print '<td align="right">'.convertSecondToTime($objp->duree).'</td>';
		}
		print '<td align="right">'.$interventionstatic->LibStatut($objp->fk_statut,5).'</td>';

		print '<td>&nbsp;</td>';
		print "</tr>\n";

		$total += $objp->duree;
		$i++;
	}
	$rowspan=3;
	if (empty($conf->global->FICHINTER_DISABLE_DETAILS))
	{
		print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").'</td>';
		print '<td align="right" class="nowrap liste_total">'.convertSecondToTime($total).'</td><td>&nbsp;</td><td>&nbsp;</td>';
		print '</tr>';
	}

	print '</table>';
	print "</form>\n";
	$db->free($result);
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();