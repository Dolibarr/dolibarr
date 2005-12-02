<?php
/* Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/societe/info.php
        \ingroup    societe
		\brief      Page des informations d'une societe
		\version    $Revision$
*/

require("./pre.inc.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");

$langs->load("companies");
$langs->load("other");

llxHeader();


/*
 * Visualisation de la fiche
 *
 */

$soc = new Societe($db);
$soc->id = $_GET["socid"];
$soc->fetch($_GET["socid"]);
$soc->info($_GET["socid"]);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$soc->id;
$head[$h][1] = $langs->trans("Company");
$h++;

if ($soc->client==1)
{
    $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Customer");
    $h++;
}
if ($soc->client==2)
{
    $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$soc->id;
    $head[$h][1] = $langs->trans("Prospect");
    $h++;
}
if ($soc->fournisseur)
{
    $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Supplier");;
    $h++;
}

if ($conf->compta->enabled) {
    $langs->load("compta");
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Accountancy");
    $h++;
}

$head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$soc->id;
$head[$h][1] = $langs->trans("Note");
$h++;

if ($user->societe_id == 0)
{
    $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Documents");
    $h++;
}

$head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$soc->id;
$head[$h][1] = $langs->trans("Notifications");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$soc->id;
$head[$h][1] = $langs->trans("Info");
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $soc->nom);


print '<table width="100%"><tr><td>';
dolibarr_print_object_info($soc);
print '</td></tr></table>';

print '</div>';

// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
