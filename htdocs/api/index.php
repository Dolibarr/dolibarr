<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *  \file       htdocs/api/indexphp
 *
 *	@todo	User authentication with api_key
 */

if (! defined("NOLOGIN"))        define("NOLOGIN",'1');
if (! defined("NOCSRFCHECK"))    define("NOCSRFCHECK",'1');

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



$api = new DolibarrApi($db);

// Enable the Restler API Explorer.
// See https://github.com/Luracast/Restler-API-Explorer for more info.
$api->r->addAPIClass('Luracast\\Restler\\Explorer');

$api->r->setSupportedFormats('JsonFormat', 'XmlFormat');
$api->r->addAuthenticationClass('DolibarrApiAccess','');

$listofapis = array();

$modulesdir = dolGetModulesDirs();
foreach ($modulesdir as $dir)
{
    /*
     * Search available module
     */
    //dol_syslog("Scan directory ".$dir." for API modules");

    $handle=@opendir(dol_osencode($dir));
    if (is_resource($handle))
    {
        while (($file = readdir($handle))!==false)
        {
            if (is_readable($dir.$file) && preg_match("/^mod(.*)\.class\.php$/i",$file,$reg))
            {
                $module = strtolower($reg[1]);
                $moduledirforclass = $module;
                $moduleforperm = $module;
                
                if ($module == 'propale') {
                    $moduledirforclass = 'comm/propal';
                    $moduleforperm='propal';
                }
                elseif ($module == 'agenda') {
                    $moduledirforclass = 'comm/action';
                }
                elseif ($module == 'adherent') {
                    $moduledirforclass = 'adherents';
                }
                elseif ($module == 'banque') {
                    $moduledirforclass = 'compta/bank';
                }
                elseif ($module == 'categorie') {
                    $moduledirforclass = 'categories';
                }
                elseif ($module == 'facture') {
                    $moduledirforclass = 'compta/facture';
                }
                elseif ($module == 'project') {
                    $moduledirforclass = 'projet';
                }
                elseif ($module == 'task') {
                    $moduledirforclass = 'projet';
                }
                elseif ($module == 'stock') {
                    $moduledirforclass = 'product/stock';
                }
                elseif ($module == 'fournisseur') {
                    $moduledirforclass = 'fourn';
                }
                //dol_syslog("Found module file ".$file." - module=".$module." - moduledirforclass=".$moduledirforclass);
                
                // Defined if module is enabled
                $enabled=true;
                if (empty($conf->$moduleforperm->enabled)) $enabled=false;

                if ($enabled)
                {
                    /*
                     * If exists, load the API class for enable module
                     *
                     * Search files named api_<object>.class.php into /htdocs/<module>/class directory
                     *
                     * @todo : take care of externals module!
                     * @todo : use getElementProperties() function ?
                     */
                    $dir_part = DOL_DOCUMENT_ROOT.'/'.$moduledirforclass.'/class/';

                    $handle_part=@opendir(dol_osencode($dir_part));
                    if (is_resource($handle_part))
                    {
                        while (($file_searched = readdir($handle_part))!==false)
                        {
                            if ($file_searched == 'api_access.class.php') continue;
                            
                            // Support of the deprecated API.
                            if (is_readable($dir_part.$file_searched) && preg_match("/^api_deprecated_(.*)\.class\.php$/i",$file_searched,$reg))
                            {
                                $classname = ucwords($reg[1]).'Api';
                                require_once $dir_part.$file_searched;
                                if (class_exists($classname))
                                {
                                    //dol_syslog("Found deprecated API by index.php: classname=".$classname." for module ".$dir." into ".$dir_part.$file_searched);
                                    $api->r->addAPIClass($classname, '/');
                                }
                                else
                                {
                                    dol_syslog("We found an api_xxx file (".$file_searched.") but class ".$classname." does not exists after loading file", LOG_WARNING);
                                }
                            }
                            elseif (is_readable($dir_part.$file_searched) && preg_match("/^api_(.*)\.class\.php$/i",$file_searched,$reg))
                            {
                                $classname = ucwords($reg[1]);
                                $classname = str_replace('_', '', $classname);
                                require_once $dir_part.$file_searched;
                                if (class_exists($classname))
                                {
                                    //dol_syslog("Found API by index.php: classname=".$classname." for module ".$dir." into ".$dir_part.$file_searched);
                                    $listofapis[] = $classname;
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

// Sort the classes before adding them to Restler. The Restler API Explorer
// shows the classes in the order they are added and it's a mess if they are
// not sorted.
sort($listofapis);
//var_dump($listofapis);
foreach ($listofapis as $classname)
{
    $api->r->addAPIClass($classname);
}

// TODO If not found, redirect to explorer
//var_dump($api);

// Call API (we suppose we found it)
$api->r->handle();
