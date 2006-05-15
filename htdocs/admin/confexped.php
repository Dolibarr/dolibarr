<?php
/* Copyright (C) 2004-2005 Laurent Destailleur       <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani          <acianfa@free.fr>
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
	    \file       htdocs/admin/confexped.php
		\ingroup    produit
		\brief      Page d'administration/configuration du module Expedition
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("sendings");

if (!$user->admin)
  accessforbidden();



if ($_POST["action"] == 'activate_sending')
{
    dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", $_POST["value"]);
    Header("Location: produit.php");
    exit;
}
else if ($_GET["action"] == 'disable_sending')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_EXPEDITION");
    Header("Location: produit.php");
    exit;
}
else if ($_GET["action"] == 'activate_delivery')
{
			dolibarr_set_const($db, "MAIN_SUBMODULE_LIVRAISON", "1");
			Header("Location: produit.php");
			exit;
}
else if ($_GET["action"] == 'disable_delivery')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_LIVRAISON");
    Header("Location: produit.php");
    exit;
}


/*
 * Affiche page
 */

llxHeader("","");

$dir = DOL_DOCUMENT_ROOT."/expedition/mods/";
$html=new Form($db);

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$hselected=$h;
$h++;

if ($conf->global->MAIN_SUBMODULE_EXPEDITION)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
	$head[$h][1] = $langs->trans("Sending");
	$h++;
}

if ($conf->global->MAIN_SUBMODULE_LIVRAISON)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
	$head[$h][1] = $langs->trans("Delivery");
	$h++;
}

dolibarr_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 * Formulaire parametres divers
 */


// expedition activation/desactivation
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="center">'.$langs->trans("Active").'</td>';
print "</tr>\n";
print "<form method=\"post\" action=\"confexped.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"sending\">";
print "<tr ".$bc[false].">";
print '<td width="80%">'.$langs->trans("SendingsAbility").'</td>';
print '<td align="center">';

if($conf->global->MAIN_SUBMODULE_EXPEDITION == 1)
{
	print img_tick();
}

print '</td>';
print "<td align=\"center\">";

if($conf->global->MAIN_SUBMODULE_EXPEDITION == 0)
{
	print '<a href="confexped.php?action=activate_sending">'.$langs->trans("Activate").'</a>';
}
else if($conf->global->MAIN_SUBMODULE_EXPEDITION == 1)
{
	print '<a href="confexped.php?action=disable_sending">'.$langs->trans("Disable").'</a>';
}

print "</td>";
print '</tr>';
print '</table>';
print '</form>';

// Bon de livraison activation/desactivation
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="center">'.$langs->trans("Active").'</td>';
print "</tr>\n";
print "<form method=\"post\" action=\"confexped.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"delivery\">";
print "<tr ".$bc[false].">";
print '<td width="80%">'.$langs->trans("DeliveriesAbility").'</td>';
print '<td align="center">';

if($conf->global->MAIN_SUBMODULE_LIVRAISON == 1)
{
	print img_tick();
}

print '</td>';
print "<td align=\"center\">";

if($conf->global->MAIN_SUBMODULE_LIVRAISON == 0)
{
	print '<a href="confexped.php?action=activate_delivery">'.$langs->trans("Activate").'</a>';
}
else if($conf->global->MAIN_SUBMODULE_LIVRAISON == 1)
{
	print '<a href="confexped.php?action=disable_delivery">'.$langs->trans("Disable").'</a>';
}

print "</td>";
print '</tr>';
print '</table>';
print '</form>';


$db->close();

llxFooter();
?>
