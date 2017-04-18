<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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


// Load Dolibarr environment
if (false === (@include '../main.inc.php')) {  // From htdocs directory
	require '../../main.inc.php'; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

// Load traductions files requiredby by page
$langs->load("mymodule");
$langs->load("other");

// Get parameters
$id = GETPOST('id', 'int'); // For backward compatibility

$object = new Facture($db);
// Load object
if ($id > 0 || ! empty($ref)) {
	$ret = $object->fetch($id, $ref, '', '', $conf->global->INVOICE_USE_SITUATION);
}




/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','StripePaymentLink','');


// Part to show record
if ($id)
{
	print load_fiche_titre($langs->trans("StripePaymentLink"));
    
	dol_fiche_head();
	
	$link = $dolibarr_main_url_root . '/custom/stripe/checkout.php?source=invoice&ref=' . $object->ref;
	print '<table class="border centpercent">'."\n";
	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentLink").'</td><td><input class="flat" type="text" size="100%" name="label" value="'.$link.'"></td></tr>';
	// LIST_OF_TD_LABEL_FIELDS_VIEW
	print '</table>';
	
	dol_fiche_end();




}

llxFooter();
$db->close();
