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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       dev/examples/get_contracts.php
 *      \brief      This file is an example for a command line script
 *		\author		Put author's name here
 *		\remarks	Put here some comments
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Global variables
$version='1.7';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened handler to database will be closed at end of file.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main");				// To load language file for default language
@set_time_limit(0);

// Load user and its permissions
$result=$user->fetch('','admin');	// Load user for login 'admin'. Comment line to run as anonymous user.
if (! $result > 0) { dol_print_error('',$user->error); exit; }
$user->getrights();


print "***** ".$script_file." (".$version.") *****\n";
if (! isset($argv[1])) {	// Check parameters
    print "Usage: ".$script_file." id_thirdparty ...\n";
    exit;
}
print '--- start'."\n";
print 'Argument id_thirdparty='.$argv[1]."\n";


// Start of transaction
$db->begin();

require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

// Create invoice object
$obj = new Contrat($db);
$obj->socid=$argv[1];

$listofcontractsforcompany=$obj->getListOfContracts('all');

print $listofcontractsforcompany;


// -------------------- END OF YOUR CODE --------------------

$db->close();

return $error;
