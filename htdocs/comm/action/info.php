<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/comm/action/info.php
        \ingroup    agenda
		\brief      Page des informations d'une action
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$langs->load("commercial");

// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}


/*
 * Visualisation de la fiche
 *
 */

llxHeader();

$act = new ActionComm($db);
$act->fetch($_GET["id"]);
$act->info($_GET["id"]);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/comm/action/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("CardAction");
$hselected=$h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/comm/action/document.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans('Documents');
$h++;

$head[$h][0] = DOL_URL_ROOT.'/comm/action/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans('Info');
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("Action"));


print '<table width="100%"><tr><td>';
dol_print_object_info($act);
print '</td></tr></table>';

print '</div>';

// Juste pour �viter bug IE qui r�organise mal div pr�c�dents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
