<?php
/* Copyright (C) 2022 Florian HENRY <florian.henry@scopen.fr>
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
 *       \file       htdocs/core/ajax/ajaxinvoiceline.php
 *       \brief      File to load contacts combobox
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

$invoice_id = GETPOSTINT('id'); // id of thirdparty
$action = GETPOST('action', 'aZ09');
$htmlname = GETPOST('htmlname', 'alpha');


// Security check
restrictedArea($user, 'facture', $invoice_id, '', '', 'fk_soc', 'rowid');


/*
 * View
 */

top_httphead('application/json');

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

$return = array();

// Load original field value
if (!empty($invoice_id) && !empty($action) && !empty($htmlname)) {
	$formProject = new FormProjets($db);


	$return['value']	= $formProject->selectInvoiceAndLine($invoice_id, 0, 'invoiceid', 'invoicelineid', 'maxwidth500', array(), 1);
	//$return['num'] = $form->num;
	//$return['error']	= $form->error;
}

echo json_encode($return);
