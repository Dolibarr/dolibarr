<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/*! \file htdocs/compta/facture/info.php
        \ingroup    facture
		\brief      Page des informations d'une facture
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("bills");

llxHeader();


/*
 * Visualisation de la fiche
 *
 */

$facture = new Facture($db);
$facture->fetch($_GET["facid"]);
$facture->info($_GET["facid"]);
$soc = new Societe($db, $facture->socidp);
$soc->fetch($facture->socidp);

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$facture->id;
$head[$h][1] = $langs->trans("Bill")." : $facture->ref";
$h++;
$head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$facture->id;
$head[$h][1] = $langs->trans("Note");
$h++;      
$head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$facture->id;
$head[$h][1] = $langs->trans("Info");
$hselected = $h;
$h++;      

dolibarr_fiche_head($head, $hselected, $soc->nom);


print '<table width="100%"><tr><td>';
dolibarr_print_object_info($facture);
print '</td></tr></table>';

print "<br></div>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
