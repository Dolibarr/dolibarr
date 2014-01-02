<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory

if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
dol_include_once('/resource/class/resource.class.php');
dol_include_once('/resource/class/html.formresource.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
$id				= GETPOST('id','int');
$action			= GETPOST('action','alpha');

$start			= GETPOST('start','int');
$end			= GETPOST('end','int');
$fk_resource 	= GETPOST('fk_resource','int');


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morecss=array("/resource/js/fullcalendar/fullcalendar.css");

$morejs=array("http://api.simile-widgets.org/timeline/2.3.1/timeline-api.js?bundle=true");
llxHeader('','ResourcePlaning','','','','',$morejs,$morecss,0,0);

$form=new Form($db);


// Put here content of your page

// Example 1 : Adding jquery code
print '<script type="text/javascript" language="javascript">


var tl;
 function onLoad() {
   var bandInfos = [
     Timeline.createBandInfo({
         width:          "70%",
         intervalUnit:   Timeline.DateTime.MONTH,
         intervalPixels: 100
     }),
     Timeline.createBandInfo({
         width:          "30%",
         intervalUnit:   Timeline.DateTime.YEAR,
         intervalPixels: 200
     })
   ];
   tl = Timeline.create(document.getElementById("my-timeline"), bandInfos);
 }

 var resizeTimerID = null;
 function onResize() {
     if (resizeTimerID == null) {
         resizeTimerID = window.setTimeout(function() {
             resizeTimerID = null;
             tl.layout();
         }, 500);
     }
 }


jQuery(document).ready(function() {

		$("body").load( function() { onLoad(); } );



});
</script>';

$formresource = new FormResource($db);

print $formresource->select_resource_list();



print '<div id="my-timeline" style="height: 350px; border: 1px solid #aaa"></div>
<noscript>
This page uses Javascript to show you a Timeline. Please enable Javascript in your browser to see the full page. Thank you.
</noscript>';




// End of page
llxFooter();
$db->close();
?>
