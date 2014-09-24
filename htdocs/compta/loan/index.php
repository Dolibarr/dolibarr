<?php
/* Copyright (C) 2014		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 *   	\file       htdocs/compta/loan/index.php
 *		\ingroup    loan
 *		\brief      Page to list all loans
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/loan/class/loan.class.php';

$langs->load("loan");
$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', '', '', '');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="l.rowid";
if (! $sortorder) $sortorder="DESC";

$filtre=$_GET["filtre"];

/*
 *	View
 */

$form = new Form($db);
$loan = new Loan($db);

llxHeader();

$sql = "SELECT l.rowid as id, l.label, l.capital, l.datestart, l.dateend,";
$sql.= " SUM(pl.amount) as alreadypayed";
$sql.= " FROM ".MAIN_DB_PREFIX."loan as l,";
$sql.= " ".MAIN_DB_PREFIX."payment_loan as pl";
$sql.= " WHERE pl.fk_loan = l.rowid";
$sql.= " AND l.entity = ".$conf->entity;
if (GETPOST("search_label")) $sql.=" AND l.label LIKE '%".$db->escape(GETPOST("search_label"))."%'";

if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1,$offset);

$resql=$db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;

	$param='';
	
	print_fiche_titre($langs->trans("Loans"));
	
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print "<table class=\"noborder\" width=\"100%\">";

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"id","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"l.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Capital"),$_SERVER["PHP_SELF"],"l.capital","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"l.datestart","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"l.paid","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_label" value="'.GETPOST("search_label").'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$var = !$var;
		print "<tr ".$bc[$var].">";

		// Ref
		print '<td width="60">';
		$loan->id=$obj->id;
		$loan->label=$obj->id;
		$loan->ref=$obj->id;
		print $loan->getNameUrl(1,'20');
		print '</td>';

		// Label
		print '<td>'.dol_trunc($obj->label,42).'</td>';

		// Capital
		print '<td align="right" width="100">'.price($obj->capital).'</td>';

		// Date start
		print '<td width="110" align="center">'.dol_print_date($db->jdate($obj->datestart), 'day').'</td>';

		print '<td align="right" class="nowrap">'.$loan->LibStatut($obj->paid,5,$obj->alreadypayed).'</a></td>';

		print '</tr>';
		$i++;
	}

	print '</table>';
	print '</form>';
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
