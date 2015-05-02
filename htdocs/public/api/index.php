<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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
 *
 *
 */
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';					
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // For custom directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/includes/restler/vendor/autoload.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Enable and test if module Api is enabled
if (empty($conf->global->MAIN_MODULE_API))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr API interfaces with module disabled");
    print $langs->trans("WarningModuleNotActive",'Api').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

use Luracast\Restler\Defaults;


$api = new DolibarrApi($db);

$api->r->addAPIClass('Luracast\\Restler\\Resources'); //this creates resources.json at API Root
$api->r->addAPIClass('DolibarrApiInit',''); // Just for url root page
$api->r->setSupportedFormats('JsonFormat', 'XmlFormat');
$api->r->addAuthenticationClass('DolibarrApiAccess','');

$modulesdir = dolGetModulesDirs();
foreach ($modulesdir as $dir)
{
    /*
     * Search available module
     */
    dol_syslog("Scan directory ".$dir." for API modules");
    
    $handle=@opendir(dol_osencode($dir));
    if (is_resource($handle))
    {
        while (($file = readdir($handle))!==false)
        {
            if (is_readable($dir.$file) && preg_match("/^(mod.*)\.class\.php$/i",$file,$reg))
            {
                $modulename=$reg[1];

                // Defined if module is enabled
                $enabled=true;
                $part=$obj=strtolower(preg_replace('/^mod/i','',$modulename));
                //if ($part == 'propale') $part='propal';
                if ($part == 'societe') {
					$obj = 'thirdparty';
				}
                if (empty($conf->$part->enabled)) $enabled=false;

                if ($enabled)
                {
                    /*
                     * If exists, load the API class for enable module
                     *
                     * Search a file api_<object>.class.php into /htdocs/<module>/class directory
                     *
                     * @todo : take care of externals module!
                     * @todo : use getElementProperties() function
                     */
                    $file = DOL_DOCUMENT_ROOT.'/'.$part."/class/api_".$obj.".class.php";

                    $classname = ucwords($obj).'Api';
                    if (file_exists($file))
                    {
                        require_once $file;
                        $api->r->addAPIClass($classname,'');
                    }
                }
            }
        }
    }
}

$api->r->handle(); //serve the response