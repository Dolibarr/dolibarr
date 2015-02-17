<?php
	
/**
 * Copyright (C) 2013	Marcos García	<marcosgdf@gmail.com>
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
 * or see http://www.gnu.org/
 */

/**
 * Returns an array with the tabs for the "Payment" section
 * It loads tabs from modules looking for the entity payment
 *  
 * @param Paiement $object Current payment object
 * @return array Tabs for the payment section
 */
function payment_prepare_head(Paiement $object) {
	
	global $langs, $conf;
	
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'payment';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'payment');

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'payment', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Supplier payment" section
 * It loads tabs from modules looking for the entity payment_supplier
 *  
 * @param Paiement $object Current payment object
 * @return array Tabs for the payment section
 */
function payment_supplier_prepare_head(Paiement $object) {
	
	global $langs, $conf;
	
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'payment';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'payment_supplier');
	
	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'payment_supplier', 'remove');

	return $head;
}