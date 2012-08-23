<?php
/* Copyright (C) 2003      Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$langs->load("interventions");

$socid=GETPOST('socid', 'int');

// Security check
if (! empty($user->societe_id))	$socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', '', 'fichinter');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="f.datei";


/*
 * View
 */

llxHeader();

$now=dol_now();

$sql = "SELECT s.nom, s.rowid as socid, f.description, f.ref";
$sql.= ", f.datei as dp, f.rowid as fichid, f.fk_statut, f.duree";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."fichinter as f ";
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND f.entity = ".$conf->entity;
if (! empty($socid))
	$sql .= " AND s.rowid = " . $socid;


if (empty($MM))
	$MM=strftime("%m", $now);
if (empty($YY))
	$YY=strftime("%Y", $now);

echo "<div class='noprint'>";
echo '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo "<input type='hidden' name='socid' value='".$socid."'>";
echo $langs->trans("Month")." <input name='MM' size='2' value='$MM'>";
echo " Ann&eacute;e <input size='4' name='YY' value='$YY'>";
echo "<input type='submit' name='action' value='generate' />";
echo "<form>";
echo "</div>";

$start="$YY-$MM-01 00:00:00";
if ($MM ==12)
{
	$y = $YY+1;
	$end="$y-01-01 00:00:00";
}
else
{
	$m = $MM+1;
	$end="$YY-$m-01 00:00:00";
}
$sql .= " AND datei >= '".$start."' AND datei < '".$end."'" ;

$sql .= " ORDER BY $sortfield $sortorder ";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$title = $langs->trans("Report")." ".dol_print_date(strtotime($start),"%B %Y");
	print_barre_liste($title, $page, "rapport.php","&socid=".$socid,$sortfield,$sortorder,'',$num);

	$i = 0;
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
	print '<tr class="liste_titre">';
	print '<td>Num</td>';
	if (empty($socid))
	print '<td>'.$langs->trans("Customers").'</td>';
	print '<td align="center">'.$langs->trans("Description").'</td>';

	print '<td align="center">Date</td>';
	print '<td align="center">'.$langs->trans("Duration").'</td>';
	print "</tr>\n";
	$var=True;
	$DureeTotal = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><a href="fiche.php?id='.$objp->fichid.'">'.$objp->ref.'</a></td>\n';

		if (empty($socid))
		{
			if (!empty($MM)) $filter='&MM='.$MM.'&YY='.$YY;
			print '<td><a href="'.$_SERVER['PHP_SELF'].'?socid='.$objp->socid.$filter.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0"></a>&nbsp;';
			print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->rowid.$filter.'">'.$objp->nom.'</a></td>'."\n";
		}
		print '<td>'.nl2br($objp->description).'</td>';
		print "<td>".dol_print_date($db->jdate($objp->dp),"day")."</td>\n";
		print '<td align="center">'.sprintf("%.1f",$objp->duree).'</td>';
		$DureeTotal += $objp->duree;
		print "</tr>\n";

		$i++;
	}
	print "</table>";
	$db->free($resql);
	print "<br>".$langs->trans("Total")." $DureeTotal jour[s]";
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
?>
