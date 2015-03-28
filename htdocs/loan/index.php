<?php
/* Copyright (C) 2014       Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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
 *   	\file       htdocs/loan/index.php
 *		\ingroup    loan
 *		\brief      Page to list all loans
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';

$langs->load("loan");
$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = GETPOST('socid', int);
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', '', '', '');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="l.rowid";
if (! $sortorder) $sortorder="DESC";
$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref','int');
$search_label=GETPOST('search_label','alpha');
$search_amount=GETPOST('search_amount','alpha');
$filtre=GETPOST("filtre");

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
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

$sql = "SELECT l.rowid, l.label, l.capital, l.datestart, l.dateend,";
$sql.= " SUM(pl.amount_capital) as alreadypayed";
$sql.= " FROM ".MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl";
$sql.= " ON l.rowid = pl.fk_loan";
$sql.= " WHERE l.entity = ".$conf->entity;
if ($search_amount)	$sql.=" AND l.capital='".$db->escape(price2num(trim($search_amount)))."'";
if ($search_ref) 	$sql.=" AND l.rowid = ".$db->escape($search_ref);
if ($search_label)	$sql.=" AND l.label LIKE '%".$db->escape($search_label)."%'";
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
$sql.= " GROUP BY l.rowid, l.label, l.capital, l.datestart, l.dateend";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;

	print_fiche_titre($langs->trans("Loans"));

    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"l.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"l.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Capital"),$_SERVER["PHP_SELF"],"l.capital","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"l.datestart","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"l.paid","",$param,'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

	// Filters lines
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'"></td>';
	print '<td class="liste_titre"><input class="flat" size="12" type="text" name="search_label" value="'.$search_label.'"></td>';
	print '<td class="liste_titre" align="right" ><input class="flat" size="8" type="text" name="search_amount" value="'.$search_amount.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print '</tr>';

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);
        $loan_static->id = $obj->rowid;
        $loan_static->ref = $obj->rowid;
        $loan_static->label = $obj->label;

		$var = !$var;
		print "<tr ".$bc[$var].">";

		// Ref
		print '<td>'.$loan_static->getLinkUrl(1, 42).'</td>';

		// Label
		print '<td>'.dol_trunc($obj->label,42).'</td>';

		// Capital
		print '<td align="right" width="100">'.price($obj->capital).'</td>';

		// Date start
		print '<td width="110" align="center">'.dol_print_date($db->jdate($obj->datestart), 'day').'</td>';

		print '<td align="right" class="nowrap">'.$loan_static->LibStatut($obj->paid,5,$obj->alreadypayed).'</a></td>';

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
llxFooter();

$db->close();
