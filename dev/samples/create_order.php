<?PHP
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       dev/samples/manage_order.php
 *      \brief      This file is an example for a command line script
 *      \version    $Id$
 *		\author		Put author name here
 *		\remarks	Put here some comments
 */

// Test if batch mode
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI/Web. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Global variables
$version='$Revision$';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
require_once($path."../../htdocs/commande/commande.class.php");
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened handler to database will be closed at end of file.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main");				// To load language file for default language
@set_time_limit(0);

// Load user and its permissions
$result=$user->fetch('admin');	// Load user for login 'admin'. Comment line to run as anonymous user.
if (! $result > 0) { dol_print_error('',$user->error); exit; } 
$user->getrights();

print "***** ".$script_file." (".$version.") *****\n";

// Check parameters
if (! isset($argv[1])) {
    print "Usage: ".$script_file." param1 param2 ...\n";
    exit;
}

// Show parameters
print 'Argument 1='.$argv[1]."\n";
print 'Argument 2='.$argv[2]."\n";

print '--- start'."\n";

// Start of transaction
$db->begin();

// Create order object
$com = new Commande($db);

$com->ref            = 'ABCDE';
$com->socid          = 4;	// Put id of third party (rowid in llx_societe table)
$com->date_commande  = mktime();
$com->note           = 'A comment';
$com->source         = 1;
$com->remise_percent = 0;

$orderline1=new CommandeLigne($db);
$orderline1->tva_tx=10.0;
$orderline1->remise_percent=0;
$orderline1->qty=1;
$com->lines[]=$orderline1;

// Create order
$result=$com->create($user);
if ($result >= 0)
{
	// Change status to validated
	$result=$com->valid($user);
	if ($result) print " OK";
	else
	{
		$error++;
		dol_print_error($db,$com->error);
	}
}
else
{
	$error++;
	dol_print_error($db,$com->error);
}

print "\n";

// -------------------- END OF YOUR CODE --------------------

if (! $error)
{
	$db->commit();
	print '--- end ok'."\n";
}
else
{
	print '--- end error code='.$error."\n";
	$db->rollback();
}

$db->close();

return $error;
?>
