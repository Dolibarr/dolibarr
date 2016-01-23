<?php
/* Copyright (C) 2003     	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004     	Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009	Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *	    \file       htdocs/expensereport/index.php
 *      \ingroup    expensereport
 *		\brief      list of expense reports
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
$socid = $_GET["socid"]?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport','','');

$sall         = GETPOST('sall');
$search_ref   = GETPOST('search_ref');
$search_user  = GETPOST('search_user','int');
$search_amount_ht = GETPOST('search_amount_ht','alpha');
$search_amount_ttc = GETPOST('search_amount_ttc','alpha');
$search_status = (GETPOST('search_status','alpha')!=''?GETPOST('search_status','alpha'):GETPOST('statut','alpha'));
$month_start  = GETPOST("month_start","int");
$year_start   = GETPOST("year_start","int");
$month_end    = GETPOST("month_end","int");
$year_end     = GETPOST("year_end","int");
$optioncss = GETPOST('optioncss','alpha');

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter"))		// Both test must be present to be compatible with all browsers
{
	$search_ref="";
	$search_user="";
	$search_amount_ht="";
	$search_amount_ttc="";
	$search_status="";
	$month_start="";
	$year_start="";
	$month_end="";
	$year_end="";
}

if ($search_status == '') $search_status=-1;
if ($search_user == '') $search_user=-1;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'd.ref'=>'Ref',
    'u.lastname'=>'Lastname',
    'u.firstname'=>"Firstname",
);


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$expensereporttmp=new ExpenseReport($db);

llxHeader('', $langs->trans("ListOfTrips"));

$max_year = 5;
$min_year = 5;

$sortorder     = GETPOST("sortorder");
$sortfield     = GETPOST("sortfield");
$page          = GETPOST("page");
if (!$sortorder) $sortorder="DESC";
if (!$sortfield) $sortfield="d.date_debut";
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;

if ($page == -1) {
	$page = 0 ;
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT d.rowid, d.ref, d.fk_user_author, d.total_ht, d.total_tva, d.total_ttc, d.fk_statut as status,";
$sql.= " d.date_debut, d.date_fin,";
$sql.= " u.rowid as id_user, u.firstname, u.lastname";
$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as d";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON d.fk_user_author = u.rowid";
$sql.= " WHERE d.entity = ".$conf->entity;

// Search all
if (!empty($sall))
{
	$sql.= natural_search(array_keys($fieldstosearchall), $sall);
}
// Ref
if(!empty($search_ref)){
	$sql.= " AND d.ref LIKE '%".$db->escape($search_ref)."%'";
}
// Date Start
if ($month_start > 0)
{
    if ($year_start > 0 && empty($day))
    $sql.= " AND d.date_debut BETWEEN '".$db->idate(dol_get_first_day($year_start,$month_start,false))."' AND '".$db->idate(dol_get_last_day($year_start,$month_start,false))."'";
    else if ($year_start > 0 && ! empty($day))
    $sql.= " AND d.date_debut BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_start, $day, $year_start))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_start, $day, $year_start))."'";
    else
    $sql.= " AND date_format(d.date_debut, '%m') = '".$month_start."'";
}
else if ($year_start > 0)
{
	$sql.= " AND d.date_debut BETWEEN '".$db->idate(dol_get_first_day($year_start,1,false))."' AND '".$db->idate(dol_get_last_day($year_start,12,false))."'";
}
// Date Start
if ($month_end > 0)
{
    if ($year_end > 0 && empty($day))
    $sql.= " AND d.date_fin BETWEEN '".$db->idate(dol_get_first_day($year_end,$month_end,false))."' AND '".$db->idate(dol_get_last_day($year_end,$month_end,false))."'";
    else if ($year_end > 0 && ! empty($day))
    $sql.= " AND d.date_fin BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_end, $day, $year_end))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_end, $day, $year_end))."'";
    else
    $sql.= " AND date_format(d.date_fin, '%m') = '".$month_end."'";
}
else if ($year_end > 0)
{
	$sql.= " AND d.date_fin BETWEEN '".$db->idate(dol_get_first_day($year_end,1,false))."' AND '".$db->idate(dol_get_last_day($year_end,12,false))."'";
}
// Amount
if ($search_amount_ht != '')
{
    $sql.= natural_search('d.total_ht', $search_amount_ht, 1);
}
if ($search_amount_ttc != '')
{
    $sql.= natural_search('d.total_ttc', $search_amount_ttc, 1);
}
// User
if ($search_user != '' && $search_user >= 0)
{
	$sql.= " AND u.rowid = '".$db->escape($search_user)."'";
}
// Status
if ($search_status != '' && $search_status >= 0)
{
	if (strstr($search_status, ',')) $sql.=" AND d.fk_statut IN (".$db->escape($search_status).")";
	else $sql.=" AND d.fk_statut = ".$search_status;
}

// RESTRICT RIGHTS
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous))
{
	$childids = $user->getAllChildIds();
	$childids[]=$user->id;
	$sql.= " AND d.fk_user_author IN (".join(',',$childids).")\n";
}

$sql.= $db->order($sortfield,$sortorder);
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}
$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param="";
	if ($search_ref)			$param.="&search_ref=".$search_ref;
	if ($search_user)			$param.="&search_user=".$search_user;
	if ($search_amount_ht)		$param.="&search_amount_ht=".$search_amount_ht;
	if ($search_amount_ttc)		$param.="&search_amount_ttc=".$search_amount_ttc;
	if ($search_status >= 0)  	$param.="&search_status=".$search_status;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;

	print_barre_liste($langs->trans("ListTripsAndExpenses"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    }

	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"d.rowid","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"d.date_debut","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"d.date_fin","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"u.lastname","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("TotalHT"),$_SERVER["PHP_SELF"],"d.total_ht","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("TotalVAT"),$_SERVER["PHP_SELF"],"d.total_tva","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("TotalTTC"),$_SERVER["PHP_SELF"],"d.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Filters
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" size="15" type="text" name="search_ref" value="'.$search_ref.'">';

	// Date start
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
	$formother->select_year($year_start,'year_start',1, $min_year, $max_year);
	print '</td>';

	// Date end
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
	$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
	print '</td>';

	// User
	if ($user->rights->expensereport->readall || $user->rights->expensereport->lire_tous){
		print '<td class="liste_titre" align="left">';
		print $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
		print '</td>';
	} else {
		print '<td class="liste_titre">&nbsp;</td>';
	}

	// Amount with no tax
	print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_amount_ht" value="'.$search_amount_ht.'"></td>';

	print '<td class="liste_titre">&nbsp;</td>';

	// Amount with all taxes
	print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_amount_ttc" value="'.$search_amount_ttc.'"></td>';

	// Status
	print '<td class="liste_titre" align="right">';
	select_expensereport_statut($search_status,'search_status',1,1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

	print "</tr>\n";

	$var=true;

	$total_total_ht = 0;
	$total_total_ttc = 0;
	$total_total_tva = 0;
	
	$expensereportstatic=new ExpenseReport($db);

	if($num > 0)
	{
		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($resql);
			
			$expensereportstatic->id=$objp->rowid;
			$expensereportstatic->ref=$objp->ref;

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td>'.$expensereportstatic->getNomUrl(1).'</td>';
			print '<td align="center">'.($objp->date_debut > 0 ? dol_print_date($objp->date_debut, 'day') : '').'</td>';
			print '<td align="center">'.($objp->date_fin > 0 ? dol_print_date($objp->date_fin, 'day') : '').'</td>';
			print '<td align="left"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$objp->id_user.'">'.img_object($langs->trans("ShowUser"),"user").' '.dolGetFirstLastname($objp->firstname, $objp->lastname).'</a></td>';
			print '<td align="right">'.price($objp->total_ht).'</td>';
			print '<td align="right">'.price($objp->total_tva).'</td>';
			print '<td align="right">'.price($objp->total_ttc).'</td>';

			$expensereporttmp->status=$objp->status;
			print '<td align="right">';
			//print $objp->status;
			print $expensereporttmp->getLibStatut(5);
			print '</td>';

			print '<td></td>';

			print "</tr>\n";

			$total_total_ht = $total_total_ht + $objp->total_ht;
			$total_total_tva = $total_total_tva + $objp->total_tva;
			$total_total_ttc = $total_total_ttc + $objp->total_ttc;

			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="4">'.$langs->trans("Total").'</td>';

		print '<td style="text-align:right;">'.price($total_total_ht).'</td>';
		print '<td style="text-align:right;">'.price($total_total_tva).'</td>';
		print '<td style="text-align:right;">'.price($total_total_ttc).'</td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';

		}
	else
	{
		print '<tr '.$bc[false].'>'.'<td colspan="9">'.$langs->trans("NoRecordFound").'</td></tr>';
	}
	print "</table>";

	print "</form>";

	/*
	print '<div class="tabsAction">';
	print '<a href="'.dol_buildpath('/expensereport/card.php',1).'?action=create" class="butAction">'.$langs->trans("NewTrip").'</a>';
	print '</div>';
	*/
	
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
