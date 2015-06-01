#!/usr/bin/php
<?php
/* Copyright (C) 2007-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Jean Heimburger  <http://tiaris.eu>
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
 *      \file       scripts/product/migrate_picture_path.php
 *		\ingroup    scripts
 *      \brief      migrate pictures from old system to 3.7 and more system
 *					
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

// Global variables
$version='1.0';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0);							// No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED',1);		// Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

// Include and load Dolibarr environment variables
require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
// After this $db, $mysoc, $langs, $conf and $hookmanager are defined (Opened $db handler to database will be closed at end of file).
// $user is created but empty.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main");				// To load language file for default language


print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";

print '--- start'."\n";

function migrate_product_photospath($product)
{
	global $conf;
	
	$dir = $conf->product->multidir_output[$product->entity];
	$origin = $dir .'/'. get_exdir($product->id,2) . $product->id ."/photos";
	$destin = $dir.'/'.dol_sanitizeFileName($product->ref);
	
	$error = 0;
	
	$origin_osencoded=dol_osencode($origin);
	$destin_osencoded=dol_osencode($destin);
	dol_mkdir($destin);
	
	if (dol_is_dir($origin))
	{
		$handle=opendir($origin_osencoded);
        if (is_resource($handle))
        {
        	while (($file = readdir($handle)) != false)
    		{
     			if ($file != '.' && $file != '..' && is_dir($origin_osencoded.'/'.$file))
    			{
    				$thumbs = opendir($origin_osencoded.'/'.$file);
    				if (is_resource($thumbs))
        			{
	     				dol_mkdir($destin.'/'.$file); 
	     				while (($thumb = readdir($thumbs)) != false)
		    			{
		    				dol_move($origin.'/'.$file.'/'.$thumb, $destin.'/'.$file.'/'.$thumb);	
		    			}
//		    			dol_delete_dir($origin.'/'.$file); 
        			}	
    			}
    			else 
    			{
    				if (dol_is_file($origin.'/'.$file) ) 
    				{
    					dol_move($origin.'/'.$file, $destin.'/'.$file);
    				}
    				
    			}	
    		}
        }
	}
}

$product = new Product($db);

$sql = "SELECT rowid as pid from ".MAIN_DB_PREFIX."product ";

$resql = $db->query($sql);

if (!resql )
{
	print "\n sql error ".$sql;
	exit;
}	

while ($obj = $db->fetch_object($resql))
{
	print "\n migrating ".$product->ref;
	$product->fetch($obj->pid);
	migrate_product_photospath($product);	
}		





// -------------------- END OF YOUR CODE --------------------


$db->close();	// Close $db database opened handler

exit($error);
