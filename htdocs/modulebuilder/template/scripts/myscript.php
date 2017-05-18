#!/usr/bin/env php
<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    scripts/myscript.php
 * \ingroup mymodule
 * \brief   Example command line script.
 *
 * Put detailed description here.
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ";
	echo $script_file;
	echo " from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$version = '1.0.0';
$error = 0;


/*
 * -------------------- YOUR CODE STARTS HERE --------------------
 */
/* Set this define to 0 if you want to allow execution of your script
 * even if dolibarr setup is "locked to admin user only". */
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 0);

/* Include Dolibarr environment
 * Customize to your needs
 */
require_once $path . '../../../master.inc.php';
/* After this $db, $conf, $langs, $mysoc, $user and other Dolibarr utility variables should be defined.
 * Warning: this still requires a valid htdocs/conf.php file
 */

global $conf, $db, $langs, $mysoc, $user;

// No timeout for this script
@set_time_limit(0);

// Set the default language
//$langs->setDefaultLang('en_US');

// Load translations for the default language
$langs->load("main");

/* User and permissions loading
 * Loads user for login 'admin'.
 * Comment out to run as anonymous user. */
$result = $user->fetch('', 'admin');
if (! $result > 0) {
	dol_print_error('', $user->error);
	exit;
}
$user->getrights();

// Display banner and help
echo "***** " . $script_file . " (" . $version . ") pid=" . getmypid() . " *****\n";
dol_syslog($script_file . " launched with arg " . join(',', $argv));
if (! isset($argv[1])) {
	// Check parameters
	echo "Usage: " . $script_file . " param1 param2 ...\n";
	exit;
}
echo '--- start' . "\n";
echo 'Argument 1=' . $argv[1] . "\n";
echo 'Argument 2=' . $argv[2] . "\n";

// Start database transaction
$db->begin();

// Examples for manipulating a class
require_once '../class/myclass.class.php';
$myobject = new MyClass($db);

// Example for inserting creating object in database
/*
	dol_syslog($script_file . " CREATE", LOG_DEBUG);
	$myobject->prop1 = 'value_prop1';
	$myobject->prop2 = 'value_prop2';
	$id = $myobject->create($user);
	if ($id < 0) {
		$error++;
		dol_print_error($db, $myobject->error);
	} else {
		 echo "Object created with id=" . $id . "\n";
	}
 */

// Example for reading object from database
/*
	dol_syslog($script_file . " FETCH", LOG_DEBUG);
	$result = $myobject->fetch($id);
	if ($result < 0) {
		$error;
		dol_print_error($db, $myobject->error);
	} else {
		echo "Object with id=" . $id . " loaded\n";
	}
 */

// Example for updating object in database
// ($myobject must have been loaded by a fetch before)
/*
	dol_syslog($script_file . " UPDATE", LOG_DEBUG);
	$myobject->prop1 = 'newvalue_prop1';
	$myobject->prop2 = 'newvalue_prop2';
	$result = $myobject->update($user);
	if ($result < 0) {
		$error++;
		dol_print_error($db, $myobject->error);
	} else {
		echo "Object with id " . $myobject->id . " updated\n";
	}
 */

// Example for deleting object in database
// ($myobject must have been loaded by a fetch before)
/*
	dol_syslog($script_file . " DELETE", LOG_DEBUG);
	$result = $myobject->delete($user);
	if ($result < 0) {
		$error++;
		dol_print_error($db, $myobject->error);
	} else {
		echo "Object with id " . $myobject->id . " deleted\n";
	}
 */

// An example of a direct SQL read without using the fetch method
/*
	$sql = "SELECT field1, field2";
	$sql.= " FROM " . MAIN_DB_PREFIX . "c_pays";
	$sql.= " WHERE field3 = 'xxx'";
	$sql.= " ORDER BY field1 ASC";

	dol_syslog($script_file . " sql=" . $sql, LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// You can use here results
					echo $obj->field1;
					echo $obj->field2;
				}
				$i++;
			}
		}
	} else {
		$error++;
		dol_print_error($db);
	}
 */


/*
 * --------------------- YOUR CODE ENDS HERE ----------------------
 */

// Error management
if (! $error) {
	$db->commit();
	echo '--- end ok' . "\n";
	$exit_status = 0; // UNIX no errors exit status
} else {
	echo '--- end error code=' . $error . "\n";
	$db->rollback();
	$exit_status = 1; // UNIX general error exit status
}

// Close database handler
$db->close();

// Return exit status code
return $exit_status;
