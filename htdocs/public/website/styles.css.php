<?php
/* Copyright (C) 2016-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/website/styles.css.php
 *		\ingroup    website
 *		\brief      Page to output style page. Called with <link rel="stylesheet" href="styles.css.php?websiteid=123" type="text/css" />
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOLOGIN'))        define("NOLOGIN",1);
if (! defined('NOCSRFCHECK'))    define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');

/**
 * Header empty
 *
 * @return	void
 */
function llxHeader() { }
/**
 * Footer empty
 *
 * @return	void
 */
function llxFooter() { }

require '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


$error=0;
$website=GETPOST('website', 'alpha');
$websiteid=GETPOST('websiteid', 'int');
$pageid=GETPOST('page', 'alpha')?GETPOST('page', 'alpha'):GETPOST('pageid', 'alpha');

$accessallowed = 1;
$type='';


/*
 * View
 */

$appli=constant('DOL_APPLICATION_TITLE');
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

//print 'Directory with '.$appli.' websites.<br>';

if (empty($pageid))
{
    require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
    require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

    $object=new Website($db);
    if ($websiteid)
    {
        $object->fetch($websiteid);
        $website=$object->ref;
    }
    else
    {
        $object->fetch(0, $website);
    }
    $objectpage=new WebsitePage($db);
    $array=$objectpage->fetchAll($object->id);

    if (count($array) > 0)
    {
        $firstrep=reset($array);
        $pageid=$firstrep->id;
    }
}
if (empty($pageid))
{
    $langs->load("website");
    print $langs->trans("PreviewOfSiteNotYetAvailable");
    exit;
}

// Security: Delete string ../ into $original_file
global $dolibarr_main_data_root;

$original_file=$dolibarr_main_data_root.'/website/'.$website.'/styles.css.php';

// Find the subdirectory name as the reference
$refname=basename(dirname($original_file)."/");

// Security:
// Limite acces si droits non corrects
if (! $accessallowed)
{
    accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
{
    dol_syslog("Refused to deliver file ".$original_file);
    $file=basename($original_file);		// Do no show plain path of original_file in shown error message
    dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$file));
    exit;
}

clearstatcache();

$filename = basename($original_file);

// Output file on browser
dol_syslog("styles.css.php include $original_file $filename content-type=$type");
$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

// This test if file exists should be useless. We keep it to find bug more easily
if (! file_exists($original_file_osencoded))
{
    $langs->load("website");
    print $langs->trans("RequestedPageHasNoContentYet", $pageid);
    //dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
    exit;
}


// Output page content
define('USEDOLIBARRSERVER', 1);
print '/* Page content '.$original_file.' : CSS content that was saved into tpl dir */'."\n";
require_once $original_file_osencoded;


if (is_object($db)) $db->close();

