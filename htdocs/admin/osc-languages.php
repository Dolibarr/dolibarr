<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * \file 		htdocs/admin/osc-languages.php
 * \ingroup    	boutique
 * \brief      	Page d'administration/configuration du module Boutique
 */

require '../main.inc.php';

$langs->load("admin");

if (!$user->admin)
accessforbidden();


llxHeader();


if (! dol_strlen(OSC_DB_NAME))
{
	print "Non dispo";
	llxFooter();
}

if ($sortfield == "") {
	$sortfield="lower(p.label),p.price";
}
if ($sortorder == "") {
	$sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


print_barre_liste("Liste des langues oscommerce", $page, "osc-languages.php");

$sql = "SELECT l.languages_id, l.name, l.code FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."languages as l";
$sql.= $db->plimit($limit, $offset);

print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print "<tr class=\"liste_titre\">";
print "<td>id</td>";
print "<td>Name</td>";
print "<td>Code</td>";
print "</TR>\n";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$var=True;
	while ($i < $num) {
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<TR $bc[$var]>";
		print "<TD>$objp->languages_id</TD>\n";
		print "<TD>$objp->name</TD>\n";
		print "<TD>$objp->code</TD>\n";
		print "</TR>\n";
		$i++;
	}
	$db->free();
}

print "</TABLE>";


$db->close();

llxFooter();
?>
