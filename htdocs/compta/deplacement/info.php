<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *    \file       htdocs/compta/deplacement/info.php
 *    \ingroup    facture
 *		\brief      Page to show a trip information
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");

$langs->load("trips");

/*
 * View
 */
llxHeader();

if ($id)
{
	$deplacement = new Deplacement($db);
	$deplacement->fetch($_GET["id"], $user);
  $deplacement->info($_GET["id"]);
	if ($deplacement > 0)
	{
		if ($mesg) print $mesg."<br>";

		$h=0;
  
    $head[$h][0] = DOL_URL_ROOT.'/compta/deplacement/fiche.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/compta/deplacement/note.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("Note");
    $head[$h][2] = 'note';
    $h++;
	
    $head[$h][0] = DOL_URL_ROOT.'/compta/deplacement/info.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
	
    dol_fiche_head($head, 'info', $langs->trans("TripCard"), 0, 'trip');

    print '<table width="100%"><tr><td>';
    dol_print_object_info($deplacement);
    print '</td></tr></table>';
      
    print '</div>';
  }
  else
	{
		dol_print_error($db);
	}
}

$db->close();

llxFooter();
?>
