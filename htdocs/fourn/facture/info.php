<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/fourn/facture/info.php
        \ingroup    facture, fournisseur
		\brief      Page des informations d'une facture fournisseur
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');

$langs->load("bills");

llxHeader();


/*
 * Visualisation de la fiche
 *
 */

$fac = new FactureFournisseur($db);
$fac->fetch($_GET["facid"]);
$fac->info($_GET["facid"]);
$soc = new Societe($db, $fac->socidp);
$soc->fetch($fac->socidp);

$h=0;

$head[$h][0] = 'fiche.php?facid='.$fac->id;
$head[$h][1] = $langs->trans('Card');
$h++;

$head[$h][0] = 'info.php?facid='.$fac->id;
$head[$h][1] = $langs->trans('Info');
$hselected = $h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("SupplierInvoice"));


print '<table width="100%"><tr><td>';
dolibarr_print_object_info($fac);
print '</td></tr></table>';

print '</div>';

// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
