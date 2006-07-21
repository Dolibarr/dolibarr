<?php
/* Copyright (C) 2004-2006 Laurent Destailleur       <eldy@users.sourceforge.net>
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
	    \file       htdocs/admin/fckeditor_cfg.php
		\ingroup    fckeditor
		\brief      Page de configuration du module FCKeditor
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("fckeditor");

if (!$user->admin)
  accessforbidden();



if ($_GET["action"] == 'activate_sending')
{
    dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1");
    Header("Location: confexped.php");
    exit;
}
else if ($_GET["action"] == 'disable_sending')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_EXPEDITION");
    Header("Location: confexped.php");
    exit;
}
else if ($_GET["action"] == 'activate_delivery')
{
			dolibarr_set_const($db, "MAIN_SUBMODULE_LIVRAISON", "1");
			Header("Location: confexped.php");
			exit;
}
else if ($_GET["action"] == 'disable_delivery')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_LIVRAISON");
    Header("Location: confexped.php");
    exit;
}


/*
 * Affiche page
 */

llxHeader("","");

$html=new Form($db);

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/fckeditor.php";
$head[$h][1] = $langs->trans("Activation");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/fckeditor_cfg.php";
$head[$h][1] = $langs->trans("Setup");
$hselected=$h;
$h++;


dolibarr_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Feature").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Action").'</td>';
print "</tr>\n";
print '</table>';



$db->close();

llxFooter();
?>
