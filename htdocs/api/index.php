<?php
/* Copyright (C) 2015	Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2017	Regis Houssin			<regis.houssin@inodbox.com>
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
 * 	\defgroup   api     Module DolibarrApi
 *  \brief      API loader
 *				Search files htdocs/<module>/class/api_<module>.class.php
 *  \file       htdocs/api/index.php
 */

if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');		// If there is no need to load and show top and left menu
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');		// If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');       // Do not load ajax.lib.php library
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)


// Force entity if a value is provided into HTTP header. Otherwise, will use the entity of user of token used.
if (! empty($_SERVER['HTTP_DOLAPIENTITY'])) define("DOLENTITY", (int) $_SERVER['HTTP_DOLAPIENTITY']);


$res=0;
if (! $res && file_exists("../main.inc.php")) $res=include '../main.inc.php';
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/AutoLoader.php';

call_user_func(function () {
    $loader = Luracast\Restler\AutoLoader::instance();
    spl_autoload_register($loader);
    return $loader;
});

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';



// Enable and test if module Api is enabled
if (empty($conf->global->MAIN_MODULE_API))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr API interfaces with module REST disabled");
    print $langs->trans("WarningModuleNotActive",'Api').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Test if explorer is not disabled
if (preg_match('/api\/index\.php\/explorer/', $_SERVER["PHP_SELF"]) && ! empty($conf->global->API_EXPLORER_DISABLED))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr API interfaces with module REST disabled");
    print $langs->trans("WarningAPIExplorerDisabled").'.<br><br>';
    exit;
}


// This 2 lines are usefull only if we want to exclude some Urls from the explorer
//use Luracast\Restler\Explorer;
//Explorer::$excludedPaths = array('/categories');


// Analyze URLs
// index.php/explorer                           do a redirect to index.php/explorer/
// index.php/explorer/                          called by swagger to build explorer page
// index.php/explorer/.../....png|.css|.js      called by swagger for resources to build explorer page
// index.php/explorer/resources.json            called by swagger to get list of all services
// index.php/explorer/resources.json/xxx        called by swagger to get detail of services xxx
// index.php/xxx                                called by any REST client to run API


preg_match('/index\.php\/([^\/]+)(.*)$/', $_SERVER["PHP_SELF"], $reg);
// .../index.php/categories?sortfield=t.rowid&sortorder=ASC


// Set the flag to say to refresh (when we reload the explorer, production must be for API call only)
$refreshcache=false;
if (! empty($reg[1]) && $reg[1] == 'explorer' && ($reg[2] == '/swagger.json' || $reg[2] == '/swagger.json/root' || $reg[2] == '/resources.json' || $reg[2] == '/resources.json/root'))
{
    $refreshcache=true;
}


$api = new DolibarrApi($db, '', $refreshcache);
//var_dump($api->r->apiVersionMap);

// Enable the Restler API Explorer.
// See https://github.com/Luracast/Restler-API-Explorer for more info.
$api->r->addAPIClass('Luracast\\Restler\\Explorer');

$api->r->setSupportedFormats('JsonFormat', 'XmlFormat', 'UploadFormat');	// 'YamlFormat'
$api->r->addAuthenticationClass('DolibarrApiAccess','');

// Define accepted mime types
UploadFormat::$allowedMimeTypes = array('image/jpeg', 'image/png', 'text/plain', 'application/octet-stream');



// Call Explorer file for all APIs definitions
if (! empty($reg[1]) && $reg[1] == 'explorer' && ($reg[2] == '/swagger.json' || $reg[2] == '/swagger.json/root' || $reg[2] == '/resources.json' || $reg[2] == '/resources.json/root'))
{
    // Scan all API files to load them

    $listofapis = array();

    $modulesdir = dolGetModulesDirs();
    foreach ($modulesdir as $dir)
    {
        // Search available module
        dol_syslog("Scan directory ".$dir." for module descriptor files, then search for API files");

        $handle=@opendir(dol_osencode($dir));
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (is_readable($dir.$file) && preg_match("/^mod(.*)\.class\.php$/i",$file,$regmod))
                {
                    $module = strtolower($regmod[1]);
                    $moduledirforclass = getModuleDirForApiClass($module);
                    $modulenameforenabled = $module;
                    if ($module == 'propale') { $modulenameforenabled='propal'; }
                    if ($module == 'supplierproposal') { $modulenameforenabled='supplier_proposal'; }
                    if ($module == 'ficheinter') { $modulenameforenabled='ficheinter'; }

                    dol_syslog("Found module file ".$file." - module=".$module." - modulenameforenabled=".$modulenameforenabled." - moduledirforclass=".$moduledirforclass);

                    // Defined if module is enabled
                    $enabled=true;
                    if (empty($conf->$modulenameforenabled->enabled)) $enabled=false;

                    if ($enabled)
                    {
                        // If exists, load the API class for enable module
                        // Search files named api_<object>.class.php into /htdocs/<module>/class directory
                        // @todo : use getElementProperties() function ?
                        $dir_part = dol_buildpath('/'.$moduledirforclass.'/class/');

                        $handle_part=@opendir(dol_osencode($dir_part));
                        if (is_resource($handle_part))
                        {
                            while (($file_searched = readdir($handle_part))!==false)
                            {
                                if ($file_searched == 'api_access.class.php') continue;

                                if (is_readable($dir_part.$file_searched) && preg_match("/^api_(.*)\.class\.php$/i",$file_searched,$regapi))
                                {
                                    $classname = ucwords($regapi[1]);
                                    $classname = str_replace('_', '', $classname);
                                    require_once $dir_part.$file_searched;
                                    if (class_exists($classname.'Api'))
                                    {
                                        //dol_syslog("Found API by index.php: classname=".$classname."Api for module ".$dir." into ".$dir_part.$file_searched);
                                        $listofapis[strtolower($classname.'Api')] = $classname.'Api';
                                    }
                                    elseif (class_exists($classname))
                                    {
                                        //dol_syslog("Found API by index.php: classname=".$classname." for module ".$dir." into ".$dir_part.$file_searched);
                                        $listofapis[strtolower($classname)] = $classname;
                                    }
                                    else
                                    {
                                        dol_syslog("We found an api_xxx file (".$file_searched.") but class ".$classname." does not exists after loading file", LOG_WARNING);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Sort the classes before adding them to Restler.
    // The Restler API Explorer shows the classes in the order they are added and it's a mess if they are not sorted.
    asort($listofapis);
    foreach ($listofapis as $apiname => $classname)
    {
        $api->r->addAPIClass($classname, $apiname);
    }
    //var_dump($api->r);
}

// Call one APIs or one definition of an API
if (! empty($reg[1]) && ($reg[1] != 'explorer' || ($reg[2] != '/swagger.json' && $reg[2] != '/resources.json' && preg_match('/^\/(swagger|resources)\.json\/(.+)$/', $reg[2], $regbis) && $regbis[2] != 'root')))
{
    $module = $reg[1];
    if ($module == 'explorer')  // If we call page to explore details of a service
    {
        $module = $regbis[2];
    }

    $module=strtolower($module);
    $moduledirforclass = getModuleDirForApiClass($module);

    // Load a dedicated API file
    dol_syslog("Load a dedicated API file module=".$module." moduledirforclass=".$moduledirforclass);

	$tmpmodule = $module;
	if ($tmpmodule != 'api')
		$tmpmodule = preg_replace('/api$/i', '', $tmpmodule);
	$classfile = str_replace('_', '', $tmpmodule);
	if ($module == 'supplierproposals')
		$classfile = 'supplier_proposals';
	if ($module == 'supplierorders')
		$classfile = 'supplier_orders';
	if ($module == 'supplierinvoices')
		$classfile = 'supplier_invoices';
	if ($module == 'ficheinter')
		$classfile = 'interventions';
	if ($module == 'interventions')
		$classfile = 'interventions';

	$dir_part_file = dol_buildpath('/' . $moduledirforclass . '/class/api_' . $classfile . '.class.php', 0, 2);

	$classname = ucwords($module);

	dol_syslog('Search /' . $moduledirforclass . '/class/api_' . $classfile . '.class.php => dir_part_file=' . $dir_part_file . ' classname=' . $classname);

	$res = false;
	if ($dir_part_file)
		$res = include_once $dir_part_file;
	if (! $res) {
		print 'API not found (failed to include API file)';
		header('HTTP/1.1 501 API not found (failed to include API file)');
		exit(0);
	}

	if (class_exists($classname))
		$api->r->addAPIClass($classname);
}

// TODO If not found, redirect to explorer
//var_dump($api->r->apiVersionMap);
//exit;

// Call API (we suppose we found it)
$api->r->handle();
