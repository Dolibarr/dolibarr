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

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';


/*
 * View
 */

// Enable and test if module Api is enabled
if (empty($conf->global->MAIN_MODULE_API))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr API interfaces with module REST disabled");
    print $langs->trans("WarningModuleNotActive",'Api').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

use \Luracast\Restler\Defaults;

$api = new DolibarrApi($db);

$api->r->addAPIClass('Luracast\\Restler\\Resources'); //this creates resources.json at API Root
$api->r->addAPIClass('DolibarrApiInit',''); // Just for url root page
$api->r->setSupportedFormats('JsonFormat', 'XmlFormat');
$api->r->addAuthenticationClass('DolibarrApiAccess','');

$listofapis = array();

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
                $module=$part=$obj=strtolower(preg_replace('/^mod/i','',$modulename));
                //if ($part == 'propale') $part='propal';
                if ($module == 'societe') {
					$obj = 'thirdparty';
				}
                if ($module == 'categorie') {
                    $part = 'categories';
					$obj = 'category';
				}
                if ($module == 'facture') {
                    $part = 'compta/facture';
					$obj = 'facture';
				}
                if (empty($conf->$module->enabled)) $enabled=false;

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
                    $dir_part = DOL_DOCUMENT_ROOT.'/'.$part.'/class/';

                    $handle_part=@opendir(dol_osencode($dir_part));
                    if (is_resource($handle_part))
                    {
                        while (($file_searched = readdir($handle_part))!==false)
                        {
                            if (is_readable($dir_part.$file_searched) && preg_match("/^(api_.*)\.class\.php$/i",$file_searched,$reg))
                            {
                                $classname=$reg[1];
                                $classname = str_replace('Api_','',ucwords($reg[1])).'Api';
                                $classname = ucfirst($classname);
                                require_once $dir_part.$file_searched;
                                if (class_exists($classname)) 
                                {
                                    dol_syslog("Found API classname=".$classname);    
                                    $api->r->addAPIClass($classname,'');
                                    
                                    /*
                                    require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/Routes.php';
                                    $tmpclass = new ReflectionClass($classname);
                                    try {
                                        $classMetadata = CommentParser::parse($tmpclass->getDocComment());
                                    } catch (Exception $e) {
                                        throw new RestException(500, "Error while parsing comments of `$classname` class. " . $e->getMessage());
                                    }*/
                                    
                                    //$listofapis[]=array('classname'=>$classname, 'fullpath'=>$file_searched);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


$listofapis=Routes::toArray();
//var_dump($listofapis);


llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ApiSetup"),$linkback,'title_setup');


foreach($listofapis['v1'] as $key => $val)
{
    if ($key)
    {
        //print $key.' - '.$val['classname'].' - '.$val['fullpath']." - ".DOL_MAIN_URL_ROOT.'/api/index.php/'.strtolower(preg_replace('/Api$/','',$val['classname']))."/xxx<br>\n";
        $url=DOL_MAIN_URL_ROOT.'/api/index.php/'.$key;
        print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
        
    }
}

llxFooter();
$db->close();

