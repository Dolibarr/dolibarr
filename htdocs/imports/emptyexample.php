<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/imports/emptyexample.php
 *      \ingroup    import
 *      \brief      Show example of import file
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
}


/**
 * This file is a wrapper, so empty header
 *
 * @param 	string 			$head				Optional head lines
 * @param 	string 			$title				HTML title
 * @param	string			$help_url			Url links to help page
 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 *                                  			For other external page: http://server/url
 * @param	string			$target				Target to use on links
 * @param 	int    			$disablejs			More content into html header
 * @param 	int    			$disablehead		More content into html header
 * @param 	array|string  	$arrayofjs			Array of complementary js files
 * @param 	array|string  	$arrayofcss			Array of complementary css files
 * @param	string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param   string  		$morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * @param	string			$replacemainareaby	Replace call to main_area() by a print of this string
 * @param	int				$disablenofollow	Disable the "nofollow" on meta robot header
 * @param	int				$disablenoindex		Disable the "noindex" on meta robot header
 * @return	void
 */
function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
{
	print '<html><title>Build an import example file</title><body>';
}

/**
 * This file is a wrapper, so empty footer
 *
 * @ignore
 * @return	void
 */
function llxFooter()
{
	print '</body></html>';
}

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php';

$datatoimport = GETPOST('datatoimport');
$format = GETPOST('format');

// Load translation files required by the page
$langs->load("exports");

// Check exportkey
if (empty($datatoimport)) {
	$user->getrights();

	llxHeader();
	print '<div class="error">Bad value for datatoimport.</div>';
	llxFooter();
	exit;
}


$filename = $langs->transnoentitiesnoconv("ExampleOfImportFile").'_'.$datatoimport.'.'.$format;

$objimport = new Import($db);
$objimport->load_arrays($user, $datatoimport);
// Load arrays from descriptor module
$fieldstarget = $objimport->array_import_fields[0];
$valuestarget = $objimport->array_import_examplevalues[0];

$attachment = true;
if (GETPOSTISSET("attachment")) {
	$attachment = GETPOST("attachment");
}
//$attachment = false;
$contenttype = dol_mimetype($format);
if (GETPOSTISSET("contenttype")) {
	$contenttype = GETPOST("contenttype");
}
//$contenttype='text/plain';
$outputencoding = 'UTF-8';

if ($contenttype) {
	header('Content-Type: '.$contenttype.($outputencoding ? '; charset='.$outputencoding : ''));
}
if ($attachment) {
	header('Content-Disposition: attachment; filename="'.$filename.'"');
}


// List of targets fields
$headerlinefields = array();	// Array of fields (label to show)
$contentlinevalues = array();	// Array of example values
$i = 0;
foreach ($fieldstarget as $code => $label) {
	$withoutstar = preg_replace('/\*/', '', $fieldstarget[$code]);
	$headerlinefields[] = $langs->transnoentities($withoutstar).($withoutstar != $fieldstarget[$code] ? '*' : '').' ('.$code.')';
	$contentlinevalues[] = (isset($valuestarget[$code]) ? $valuestarget[$code] : '');
}
//var_dump($headerlinefields);
//var_dump($contentlinevalues);

print $objimport->build_example_file($format, $headerlinefields, $contentlinevalues, $datatoimport);
