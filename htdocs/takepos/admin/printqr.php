<?php
/* Copyright (C) 2020       Andreu Bisquerra Gaya  <jove@bisquerra.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/admin/printqr.php
 *	\ingroup    takepos
 *	\brief      Print QR Menu
 */

require '../../main.inc.php';

// Security check
if (!$user->admin) accessforbidden();

$langs->load("cashdesk");

$id = GETPOST('id', 'int');

$_GET['optioncss'] = "print";

print '<center>';

if (GETPOSTISSET("id")) {
	print '<h1><b>'.$langs->trans("ScanToOrder").'</b></h1>';
	print "<img src='".DOL_URL_ROOT."/takepos/genimg/qr.php?key=".dol_encode($id)."' width='30%'>";
}
else {
	print '<h1><b>'.$langs->trans("ScanToMenu").'</b></h1>';
	print "<img src='".DOL_URL_ROOT."/takepos/genimg/qr.php' width='30%'>";
}

print '<h1><b>'.$mysoc->name.'</b></h1>';

print '</center>';

llxFooter();

$db->close();
