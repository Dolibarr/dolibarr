<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/societe/info.php
        \ingroup    societe
		\brief      Page des informations d'une societe
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");

$langs->load("companies");
$langs->load("other");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$result = restrictedArea($user, 'societe','','',1);


/*
*	View
*/

llxHeader();

$soc = new Societe($db);
$soc->id = $socid;
$soc->fetch($socid);
$soc->info($socid);

/*
 * Affichage onglets
 */
$head = societe_prepare_head($soc);

dolibarr_fiche_head($head, 'info', $langs->trans("ThirdParty"));



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
