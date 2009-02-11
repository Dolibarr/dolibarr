<?php
/* Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 \file       htdocs/docs/index.php
 \ingroup    document
 \brief      Page d'accueil module document
 \version    $Id$
 */

require("./pre.inc.php");



/*
 * 	Actions
 */

if ($_GET["id"])
{
	require_once(DOL_DOCUMENT_ROOT.'/docs/document.class.php');
	$doc = new Document($db);
	if ($doc->Generate($_GET["id"]) == 0)
	{
		Header("Location: index.php");
		exit;
	}

}


/*
 * View
 */

llxHeader();


print_titre($langs->trans("DocumentsBuilder"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print "</tr>\n";

// TODO: Scan class that are in docs/class directory to find generator availables
$listofmodules=array('pdf_courrier_droit_editeur');

$var=true;
foreach ($listofmodules as $val)
{
	$var=!$var;

	print "<tr $bc[$var]>";
	print '<td><a href="generate.php?id='.urlencode($val).'">'.$val.'</a></td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";
}


print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
