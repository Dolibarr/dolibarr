<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 *	    \file       htdocs/expensereport/index.php
 *		\brief      Page liste des expensereports
 */

require "../main.inc.php";
dol_include_once("/expensereport/class/expensereport.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
$socid = $_GET["socid"]?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport','','');

$search_ref=GETPOST('search_ref');


/*
 * View
 */

$html = new Form($db);
$formother = new FormOther($db);
$expensereporttmp=new ExpenseReport($db);

llxHeader();

$max_year = 5;
$min_year = 5;

$sortorder     = $_GET["sortorder"];
$sortfield     = $_GET["sortfield"];
$page          = $_GET["page"];

$search_ref = $_GET['search_ref'];

$month_start   = $_GET['month_start'];
$year_start    = $_GET['year_start'];
$month_end     = $_GET['month_end'];
$year_end      = $_GET['year_end'];

$search_user = $_GET['search_user'];

$search_state = $_GET['search_state'];


if (!$sortorder) $sortorder="DESC";
if (!$sortfield) $sortfield="d.date_debut";

if ($page == -1) {
	$page = 0 ;
}

$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT d.rowid, d.ref_number, d.total_ht, d.total_tva, d.total_ttc, d.fk_c_expensereport_statuts,";
$sql.= " d.date_debut, d.date_fin,";
$sql.= " u.rowid as id_user, u.firstname, u.lastname";
$sql.= " FROM ".MAIN_DB_PREFIX."expensereport d\n";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user u ON d.fk_user_author = u.rowid\n";

// WHERE
if(!empty($search_ref)){
	$sql.= " WHERE d.ref_number LIKE '%".$db->escape($search_ref)."%'\n";
}else{
	$sql.= " WHERE 1 = 1\n";
}

// DATE START
if ($month_start > 0) {
	if ($year_start > 0) {
		if($month_end > 0) {
			if($year_end > 0) {
				$sql.= " AND date_format(d.date_debut, '%Y-%m') >= '$year_start-$month_start'";
				$sql.= " AND date_format(d.date_fin, '%Y-%m') <= '$year_end-$month_end'";
			} else {
				$sql.= " AND date_format(d.date_debut, '%Y-%m') >= '$year_start-$month_start'";
				$sql.= " AND date_format(d.date_fin, '%m') <= '$month_end'";
			}
		} else {
			if($year_end > 0) {
				$sql.= " AND date_format(d.date_debut, '%Y-%m') >= '$year_start-$month_start'";
				$sql.= " AND date_format(d.date_fin, '%Y') <= '$year_end'";
			} else {
				$sql.= " AND date_format(d.date_debut, '%Y-%m') >= '$year_start-$month_start'";
			}
		}
	} else {
		$sql.= " AND date_format(d.date_debut, '%m') >= '$month_start'";
	}
} else {
	if ($year_start > 0) {
		if($month_end > 0) {
			if($year_end > 0) {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
				$sql.= " AND date_format(d.date_fin, '%Y-%m') <= '$year_end-$month_end'";
			} else {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
				$sql.= " AND date_format(d.date_fin, '%m') <= '$month_end'";
			}
		} else {
			if($year_end > 0) {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
				$sql.= " AND date_format(d.date_fin, '%Y') <= '$year_end'";
			} else {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
			}
		}
	} else {
		if($month_end > 0) {
			if($year_end > 0) {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
				$sql.= " AND date_format(d.date_fin, '%Y-%m') <= '$year_end-$month_end'";
			} else {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
				$sql.= " AND date_format(d.date_fin, '%m') <= '$month_end'";
			}
		} else {
			if($year_end > 0) {
				$sql.= " AND date_format(d.date_debut, '%Y') >= '$year_start'";
				$sql.= " AND date_format(d.date_fin, '%Y') <= '$year_end'";
			}
		}
	}
}

if(!empty($search_user) && $search_user != -1) {
	$sql.= " AND d.fk_user_author = '$search_user'\n";
}

if(!empty($search_state)) {
	$sql.= " AND d.fk_c_expensereport_statuts = '$search_state'\n";
}

// RESTRICT RIGHTS
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)){
	$sql.= " AND d.fk_user_author = '{$user->id}'\n";
}

// ORDER
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

if($_GET['debug']=='ok'){
	var_dump("<pre>",$sql,"</pre>"); exit();
}

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	print_barre_liste($langs->trans("ListTripsAndExpenses"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"d.rowid","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"d.date_debut","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"d.date_fin","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"u.lastname","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("TotalHT"),$_SERVER["PHP_SELF"],"d.total_ht","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("TotalVAT"),$_SERVER["PHP_SELF"],"d.total_tva","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("TotalTTC"),$_SERVER["PHP_SELF"],"d.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Statut"),$_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre();
	print "</tr>\n";

	// FILTRES
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
		$html->select_users($search_user,"search_user",1,"",0,'');
		print '</td>';
	} else {
		print '<td class="liste_titre">&nbsp;</td>';
	}


	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre" align="right">';
	print "</td>";

	// Status
	print '<td class="liste_titre" align="right">';
	select_expensereport_statut($search_state,'search_state');
	print '</td>';
	print '<td class="liste_titre" align="right" width="20px">';
	print ' <input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
	print "</td>";

	print "</tr>\n";

	$var=true;

	$total_total_ht = 0;
	$total_total_ttc = 0;
	$total_total_tva = 0;

	if($num>0)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td><a href="card.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowTrip"),"trip").' '.$objp->ref_number.'</a></td>';
			print '<td align="center">'.($objp->date_debut > 0 ? dol_print_date($objp->date_debut, 'day') : '').'</td>';
			print '<td align="center">'.($objp->date_fin > 0 ? dol_print_date($objp->date_fin, 'day') : '').'</td>';
			print '<td align="left"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$objp->id_user.'">'.img_object($langs->trans("ShowUser"),"user").' '.dolGetFirstLastname($objp->firstname, $objp->lastname).'</a></td>';
			/*print '<td align="right">'.price($objp->total_tva, '', $langs, 0, 'MT', 0, $conf->currency).'</td>';
			print '<td align="right">'.price($objp->total_ht, '', $langs, 0, 'MT', 0, $conf->currency).'</td>';
			print '<td align="right">'.price($objp->total_ttc, '', $langs, 0, 'MT', 0, $conf->currency).'</td>';
			*/
			print '<td align="right">'.price($objp->total_ht).'</td>';
			print '<td align="right">'.price($objp->total_tva).'</td>';
			print '<td align="right">'.price($objp->total_ttc).'</td>';

			$expensereporttmp->status=$objp->statut;
			print '<td align="right" colspan="2">'.$expensereporttmp->getLibStatut(5).'</td>';
			print "</tr>\n";

			$total_total_ht = $total_total_ht + $objp->total_ht;
			$total_total_tva = $total_total_tva + $objp->total_tva;
			$total_total_ttc = $total_total_ttc + $objp->total_ttc;

			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="4">'.$langs->trans("Total").'</td>';
		/*
		print '<td style="text-align:right;">'.price($total_total_tva, '', $langs, 0, 'MT', 0, $conf->currency).'</td>';
		print '<td style="text-align:right;">'.price($total_total_ht, '', $langs, 0, 'MT', 0, $conf->currency).'</td>';
		print '<td style="text-align:right;">'.price($total_total_ttc, '', $langs, 0, 'MT', 0, $conf->currency).'</td>';
		*/
		print '<td style="text-align:right;">'.$total_total_ht.'</td>';
		print '<td style="text-align:right;">'.$total_total_tva.'</td>';
		print '<td style="text-align:right;">'.$total_total_ttc.'</td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';

		}
	else
	{
		print '<td colspan="9">'.$langs->trans("NoRecordFound").'</td>';
	}
	print "</table>";

	print "</form>";

	print '<div class="tabsAction">';
	print '<a href="'.dol_buildpath('/expensereport/card.php',1).'?action=create" class="butAction">Ajouter une note de frais</a>';
	print '</div>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
