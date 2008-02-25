<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/contrat/info.php
        \ingroup    contrat
		\brief      Page des informations d'un contrat
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("contracts");

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
$result = restrictedArea($user, 'contrat',$contratid,'',1);


/*
* View
*/

llxHeader();

$contrat = new Contrat($db);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);

$hselected = 3;

dolibarr_fiche_head($head, $hselected, $langs->trans("Contract"));


print '<table width="100%"><tr><td>';
dolibarr_print_object_info($contrat);
print '</td></tr></table>';

print '</div>';

// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
