<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *       \file       htdocs/mrp/index.php
 *       \ingroup    bom, mrp
 *       \brief      Home page for BOM and MRP modules
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","mrp"));

// Security check
$result=restrictedArea($user, 'bom|mrp');


/*
 * View
 */

$staticbom = new BOM($db);

llxHeader('', $langs->trans("MRP"), '');

print load_fiche_titre($langs->trans("MRPArea"));


print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Statistics
 */

if ($conf->use_javascript_ajax)
{
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';
    print '<tr><td class="center" colspan="2">';




    print '</table>';
    print '</div>';
}

print '<br>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

/*
 * Last modified BOM
 */
$max=5;

$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."bom_bom as a";
$sql.= " WHERE a.entity IN (".getEntity('bom').")";
$sql.= $db->order("a.tms", "DESC");
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LatestBOMModified", $max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			$staticbom->id=$obj->rowid;
			$staticbom->ref=$obj->ref;
			$staticbom->date_modification=$obj->datem;

			print '<tr class="oddeven">';
			print '<td>'.$staticbom->getNomUrl(1, 32).'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem), 'dayhour').'</td>';
			print '<td class="right">'.$staticbom->getLibStatut(5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table></div>";
	print "<br>";
}
else
{
	dol_print_error($db);
}



print '</div></div></div>';

// End of page
llxFooter();
$db->close();
