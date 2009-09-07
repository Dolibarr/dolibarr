<?php
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
 *      \file       htdocs/imports/emptyexample.php
 *      \ingroup    import
 *      \brief      Show example of import file
 *      \version    $Id$
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

$datatoimport=isset($_GET["datatoimport"])? $_GET["datatoimport"] : (isset($_POST["datatoimport"])?$_POST["datatoimport"]:'');
$format=isset($_GET["format"])? $_GET["format"] : (isset($_POST["format"])?$_POST["format"]:'');

require("../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/imports/import.class.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/import/modules_import.php');

// C'est un wrapper, donc header vierge
function llxHeader() { print '<html><title>Export agenda cal</title><body>'; }
function llxFooter() { print '</body></html>'; }

// Check exportkey
if (empty($datatoimport))
{
	$user->getrights();

	llxHeader();
	print '<div class="error">Bad value for datatoimport.</div>';
	llxFooter('$Date$ - $Revision$');
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
//$contenttype='ISO-8859-1';

if ($contenttype)       header('Content-Type: '.$contenttype.($outputencoding?'; charset='.$outputencoding:''));
if ($attachment) 		header('Content-Disposition: attachment; filename="'.$filename.'"');


// List of targets fields
$headerlinefields=array();
$contentlinevalues=array();
$i = 0;
foreach($fieldstarget as $code=>$label)
{
	$headerlinefields[]=$fieldstarget[$code].' ('.$code.')';
	$contentlinevalues[]=$valuestarget[$code];
}
//var_dump($headerlinefields);
//var_dump($contentlinevalues);

print $objimport->build_example_file($user,$format,$headerlinefields,$contentlinevalues);

?>
