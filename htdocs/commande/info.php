<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/commande/info.php
        \ingroup    commande
		\brief      Page des informations d'une commande
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");

$langs->load("orders");
$langs->load("sendings");

if (!$user->rights->commande->lire)
	accessforbidden();


/*
 * Visualisation de la fiche
 *
 */

llxHeader();

$commande = new Commande($db);
$commande->fetch($_GET["id"]);
$commande->info($_GET["id"]);
$soc = new Societe($db, $commande->socid);
$soc->fetch($commande->socid);

$head = commande_prepare_head($commande);
dol_fiche_head($head, 'info', $langs->trans("CustomerOrder"));


print '<table width="100%"><tr><td>';
dol_print_object_info($commande);
print '</td></tr></table>';

print '</div>';

// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
