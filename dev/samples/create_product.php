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
 *      \file       dev/samples/create_product.php
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
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened handler to database will be closed at end of file.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main");				// To load language file for default language
@set_time_limit(0);

// Load user and its permissions
$result=$user->fetch('admin');	// Load user for login 'admin'. Comment line to run as anonymous user.
if (! $result > 0) { dol_print_error('',$user->error); exit; }
$user->getrights();


print "***** ".$script_file." (".$version.") *****\n";
if (! isset($argv[1])) {	// Check parameters
    print "Usage: ".$script_file." param1 param2 ...\n";
    exit;
}
print '--- start'."\n";
print 'Argument 1='.$argv[1]."\n";
print 'Argument 2='.$argv[2]."\n";


// Start of transaction
$db->begin();

require_once(DOL_DOCUMENT_ROOT."/product.class.php");

// Create instance of object
$myproduct=new Product($db);

// Définition des propriétés de l'instance product
$myproduct->ref                = '1234';
$myproduct->libelle            = 'libelle';
$myproduct->price              = '10';
$myproduct->price_base_type    = 'HT';
$myproduct->tva_tx             = '19.6';
$myproduct->type               = 0;
$myproduct->status             = 1;
$myproduct->description        = 'Description';
$myproduct->note               = 'Note';
$myproduct->weight             = 10;
$myproduct->weight_units       = 0;

// Create product in database
$idobject = $myproduct->create($user);
if ($idobject > 0)
{
	print "OK Object created with id ".$idobject."\n";
}
else
{
	$error++;
	dol_print_error($db,$myproduct->error);
}

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
