<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *   	\file       htdocs/compta/paiement/info.php
 *		\ingroup    facture
 *		\brief      Onglet info d'un paiement
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

$langs->load("bills");
$langs->load("companies");


/*
 * View
 */

llxHeader();

$paiement = new Paiement($db);
$paiement->fetch($_GET["id"], $user);
$paiement->info($_GET["id"]);


$h=0;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$hselected = $h;
$h++;


dol_fiche_head($head, $hselected, $langs->trans("PaymentCustomerInvoice"), 0, 'payment');

print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="140">'.$langs->trans('Ref').'</td><td colspan="3">'.$paiement->id.'</td></tr>';

print '</table>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($paiement);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter();
?>
