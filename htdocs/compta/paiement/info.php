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

/*!	\file htdocs/compta/paiement/info.php
		\ingroup    facture
		\brief      Onglet info d'un paiement
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../paiement.class.php");

$langs->load("bills");
$langs->load("companies");

llxHeader();

print '<div class="tabs">';
print '<a class="tab" href="fiche.php?id='.$_GET["id"].'">'.$langs->trans("Payment").'</a>';
print '<a class="tab" href="info.php?id='.$_GET["id"].'" id="active">'.$langs->trans("Info").'</a>';
print '</div>';
print '<div class="tabBar">';

/*
 * Visualisation de la fiche
 *
 */

$paiement = new Paiement($db);
$paiement->fetch($_GET["id"], $user);
$paiement->info($_GET["id"]);

print '<table width="100%"><tr><td>';
dolibarr_print_object_info($paiement);
print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
