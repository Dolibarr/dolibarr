<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/imports/emptyexample.php
 *      \ingroup    import
 *      \brief      Show example of import file
 */

// This file is a wrapper, so empty header
function llxHeader() { print '<html><title>Build an import example file</title><body>'; }
// This file is a wrapper, so empty footer
function llxFooter() { print '</body></html>'; }

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php';

$datatoimport=GETPOST('datatoimport');
$format=GETPOST('format');

// Load translation files required by the page
$langs->load("exports");

// Check exportkey
if (empty($datatoimport))
{
	$user->getrights();

	llxHeader();
	print '<div class="error">Bad value for datatoimport.</div>';
	llxFooter();
	exit;
}


$filename=$langs->trans("ExampleOfImportFile").'_'.$datatoimport.'.'.$format;

$objimport=new Import($db);
$objimport->load_arrays($user,$datatoimport);
// Load arrays from descriptor module
$entity=$objimport->array_import_entities[0][$code];
$entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
$entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;
$fieldstarget=$objimport->array_import_fields[0];
$valuestarget=$objimport->array_import_examplevalues[0];

$attachment = true;
if (isset($_GET["attachment"])) $attachment=$_GET["attachment"];
//$attachment = false;
$contenttype=dol_mimetype($format);
if (isset($_GET["contenttype"])) $contenttype=$_GET["contenttype"];
//$contenttype='text/plain';
$outputencoding='UTF-8';

if ($contenttype)       header('Content-Type: '.$contenttype.($outputencoding?'; charset='.$outputencoding:''));
if ($attachment) 		header('Content-Disposition: attachment; filename="'.$filename.'"');


// List of targets fields
$headerlinefields=array();
$contentlinevalues=array();
$i = 0;
foreach($fieldstarget as $code=>$label)
{
	$withoutstar=preg_replace('/\*/','',$fieldstarget[$code]);
	$headerlinefields[]=$langs->transnoentities($withoutstar).($withoutstar != $fieldstarget[$code]?'*':'').' ('.$code.')';
	$contentlinevalues[]=$valuestarget[$code];
}
//var_dump($headerlinefields);
//var_dump($contentlinevalues);

print $objimport->build_example_file($format,$headerlinefields,$contentlinevalues,$datatoimport);

