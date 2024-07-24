#!/usr/bin/env php
<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       dev/examples/create_product.php
 *      \brief      This file is an example for a command line script
 *		\author		Put author's name here
 *		\remarks	Put here some comments
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$version = '1.10';
$error = 0;


// -------------------- START OF YOUR CODE HERE --------------------
// Include Dolibarr environment
require_once $path."../../../htdocs/master.inc.php";
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened handler to database will be closed at end of file.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main");				// To load language file for default language
@set_time_limit(0);

// Load user and its permissions
$result = $user->fetch('', 'admin');	// Load user for login 'admin'. Comment line to run as anonymous user.
if (!$result > 0) {
	dol_print_error('', $user->error);
	exit;
}
$user->getrights();


print "***** ".$script_file." (".$version.") *****\n";


// Start of transaction
$db->begin();

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";

// Create instance of object
$myproduct = new Product($db);

// Definition of product instance properties
$myproduct->ref                = '1234';
$myproduct->label              = 'label';
$myproduct->price              = '10';
$myproduct->price_base_type    = 'HT';
$myproduct->tva_tx             = '19.6';
$myproduct->type               = Product::TYPE_PRODUCT;
$myproduct->status             = 1;
$myproduct->description        = 'Description';
$myproduct->note               = 'Note';
$myproduct->weight             = 10;
$myproduct->weight_units       = 0;

// Create product in database
$idobject = $myproduct->create($user);
if ($idobject > 0) {
	print "OK Object created with id ".$idobject."\n";
} else {
	$error++;
	dol_print_error($db, $myproduct->error);
}

// -------------------- END OF YOUR CODE --------------------

if (!$error) {
	$db->commit();
	print '--- end ok'."\n";
} else {
	print '--- end error code='.$error."\n";
	$db->rollback();
}

$db->close();

return $error;
