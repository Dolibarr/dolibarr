<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2015       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016       Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/loan/index.php
 *  \ingroup    loan
 *  \brief      Page to list all loans
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';

// Load translation files required by the page
$langs->loadLangs(array("loan","compta","banks","bills"));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', '', '', '');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="l.rowid";
if (! $sortorder) $sortorder="DESC";

$search_ref=GETPOST('search_ref','int');
$search_label=GETPOST('search_label','alpha');
$search_amount=GETPOST('search_amount','alpha');
$filtre=GETPOST("filtre");
$optioncss = GETPOST('optioncss','alpha');

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha')) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_amount="";
}


/*
 *	View
 */

$loan_static = new Loan($db);

llxHeader();

$sql = "SELECT l.rowid, l.label, l.capital, l.datestart, l.dateend, l.paid,";
$sql.= " SUM(pl.amount_capital) as alreadypayed";
$sql.= " FROM ".MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl";
$sql.= " ON l.rowid = pl.fk_loan";
$sql.= " WHERE l.entity = ".$conf->entity;
if ($search_amount)	$sql.= natural_search("l.capital", $search_amount, 1);
if ($search_ref) 	$sql.= " AND l.rowid = ".$db->escape($search_ref);
if ($search_label)	$sql.= natural_search("l.label", $search_label);
if ($filtre) {
	$filtre=str_replace(":","=",$filtre);
	$sql .= " AND ".$filtre;
}
$sql.= " GROUP BY l.rowid, l.label, l.capital, l.paid, l.datestart, l.dateend";
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($search_ref) $param.="&amp;search_ref=".urlencode($search_ref);
	if ($search_label) $param.="&amp;search_label=".urlencode($search_user);
	if ($search_amount) $param.="&amp;search_amount=".urlencode($search_amount_ht);
	if ($optioncss != '') $param.='&amp;optioncss='.urlencode($optioncss);

	$newcardbutton='';
	if ($user->rights->loan->write)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/loan/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewLoan').'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	print_barre_liste($langs->trans("Loans"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy.png', 0, $newcardbutton, '', $limit);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Filters lines
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'"></td>';
	print '<td class="liste_titre"><input class="flat" size="12" type="text" name="search_label" value="'.$search_label.'"></td>';
	print '<td class="liste_titre" align="right" ><input class="flat" size="8" type="text" name="search_amount" value="'.$search_amount.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre"></td>';
	print '<td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"l.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre("Label",$_SERVER["PHP_SELF"],"l.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre("LoanCapital",$_SERVER["PHP_SELF"],"l.capital","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre("DateStart",$_SERVER["PHP_SELF"],"l.datestart","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("DateEnd",$_SERVER["PHP_SELF"],"l.dateend","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"l.paid","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);
		$loan_static->id = $obj->rowid;
		$loan_static->ref = $obj->rowid;
		$loan_static->label = $obj->label;

		$var = !$var;
		print '<tr class="oddeven">';

		// Ref
		print '<td>'.$loan_static->getNomUrl(1, 42).'</td>';

		// Label
		print '<td>'.dol_trunc($obj->label,42).'</td>';

		// Capital
		print '<td align="right" width="100">'.price($obj->capital).'</td>';

		// Date start
		print '<td width="110" align="center">'.dol_print_date($db->jdate($obj->datestart), 'day').'</td>';

		// Date end
		print '<td width="110" align="center">'.dol_print_date($db->jdate($obj->dateend), 'day').'</td>';

		print '<td align="right" class="nowrap">'.$loan_static->LibStatut($obj->paid,5,$obj->alreadypayed).'</a></td>';

		print '<td></td>';

		print "</tr>\n";

		$i++;
	}

	print "</table>";
	print '</div>';
	print "</form>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
