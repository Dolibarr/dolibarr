<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 */
 
/**
		\file       htdocs/compta/export/liste.php
		\ingroup    compta
		\brief      Page export ventilations
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("compta");

$dir = $conf->compta->dir_output."/export/";


// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

$offset = $conf->liste_limit * $page ;
if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="ec.date_export";


/*
 * Mode Liste
 *
 */

llxHeader('','Compta - Export');


$sql = "SELECT ec.rowid,".$db->pdate("ec.date_export")." as date_export, ec.ref";
$sql .= " FROM ".MAIN_DB_PREFIX."export_compta as ec";

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print_barre_liste($langs->trans("Exports"), $page, "liste.php", $urladd, $sortfield, $sortorder, '', $num);

	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Ref"),"liste.php","ec.ref");
	print_liste_field_titre($langs->trans("Date"),"liste.php","ec.date_export");

	print "<td>-</td></tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print "<tr $bc[$var]>";

		print '<td>'.$obj->ref.'</td>';
		print '<td>'.dol_print_date($obj->date_export,"dayhour").'</td>';
		print '<td><a href="index.php?action=export&amp;id='.$obj->rowid.'">'.$langs->trans("ReBuild").'</a></td>';
		print "</tr>\n";
		$i++;
	}
	print "</table>";
	$db->free($result);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
