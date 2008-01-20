<?php
/* Copyright (C) 2008 Laurent Destailleur <eldy@users.sourceforge.net>
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

/**	    \file       htdocs/webcal/webcalexport.php
        \ingroup    webcalendar
		\brief      Page export webcalendar
		\version    $Id$
*/

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require("../master.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/webcal/webcal.class.php');


// C'est un wrapper, donc header vierge
function llxHeader() { print '<html><title>Export cal</title><body>'; }
function llxFooter() { print '</body></html>'; }


// Check config
if (empty($conf->global->PHPWEBCALENDAR_URL))
{
	$user->getrights();

	llxHeader();
	print '<div class="error">Module Webcalendar was not configured properly.</div>';
	llxFooter('$Date$ - $Revision$');
	exit;
}

// Connect to database
$webcal=new WebCal();
if (! $webcal->localdb->connected || ! $webcal->localdb->database_selected)
{
	$langs->load("admin");
	llxHeader();
	if ($webcal->localdb->connected == 1 && $webcal->localdb->database_selected != 1)
    {
        print '<div class="error">'.$langs->trans("WebCalTestKo1",$conf->webcal->db->host,$conf->webcal->db->name);
        print '<br>'.$webcal->localdb->error();
		print '<br>'.$langs->trans("WebCalCheckWebcalSetup");
        print "</div>";
        //$webcal->localdb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
    }
    else
    {
        print "<div class=\"error\">".$langs->trans("WebCalTestKo2",$conf->webcal->db->host,$conf->webcal->db->user);
        print "<br>".$webcal->localdb->error();
		print '<br>'.$langs->trans("WebCalCheckWebcalSetup");
        print "</div>";
    }
	llxFooter('$Date$ - $Revision$');
	exit;
}


$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
$leftmenu=isset($_GET["leftmenu"])?$_GET["leftmenu"]:"";

// Define format, type, filename and filter
$format='vcal';
$type='event';
$filename='';
if (! empty($_GET["format"])) $format=$_GET["format"];
if ($format == 'vcal') $filename='webcalendar.vcs';
if ($format == 'ical') $filename='webcalendar.ics';
if (! empty($_GET["type"]))   $type=$_GET["type"];
if (! $filename)
{
	$langs->load("main");
	$langs->load("errors");
	llxHeader();
    print '<div class="error">'.$langs->trans("ErrorWrongValueForParameterX",'format').'</div>';
	llxFooter('$Date$ - $Revision$');
	exit;
}
$filters=array();
if (! empty($_GET["year"])) $filters['year']=$_GET["year"];

// Build file
$result=$webcal->build_calfile($format,$type,0,$filename,$filters);
if ($result >= 0)
{
	header("Location: ".DOL_URL_ROOT.'/document.php?modulepart=webcal&file='.urlencode($filename));
	exit;
}

llxHeader();
print '<div class="error">'.$webcal->error.'</div>';
llxFooter('$Date$ - $Revision$');

?>
