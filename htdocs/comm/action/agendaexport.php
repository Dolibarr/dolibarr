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

/**	    \file       htdocs/comm/action/agendaexport.php
        \ingroup    agenda
		\brief      Page export agenda
		\version    $Id$
*/

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');


// C'est un wrapper, donc header vierge
function llxHeader() { print '<html><title>Export agenda cal</title><body>'; }
function llxFooter() { print '</body></html>'; }

// Security check
if (! $conf->agenda->enabled)
	accessforbidden();

// Check config
if (empty($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY))
{
	$user->getrights();

	llxHeader();
	print '<div class="error">Module Agenda was not configured properly.</div>';
	llxFooter('$Date$ - $Revision$');
	exit;
}


$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
$leftmenu=isset($_GET["leftmenu"])?$_GET["leftmenu"]:"";

// Define format, type, filename and filter
$format='ical';
$type='event';
$filename='';
if (! empty($_GET["format"])) $format=$_GET["format"];
if ($format == 'vcal') $filename='dolibarrcalendar.vcs';
if ($format == 'ical') $filename='dolibarrcalendar.ics';
if (! empty($_GET["type"]))   $type=$_GET["type"];
// Check filename
if (! $filename)
{
	$langs->load("main");
	$langs->load("errors");
	llxHeader();
    print '<div class="error">'.$langs->trans("ErrorWrongValueForParameterX",'format').'</div>';
	llxFooter('$Date$ - $Revision$');
	exit;
}
// Check exportkey
// \TODO
$filters=array();
if (! empty($_GET["year"])) 	$filters['year']=$_GET["year"];
if (! empty($_GET["idaction"])) $filters['idaction']=$_GET["idaction"];

$agenda=new ActionComm($db);

// Build file
$result=$agenda->build_calfile($format,$type,0,$filename,$filters);
if ($result >= 0)
{
	$encoding='UTF-8';
	$attachment = true;
	$type='text/calendar';
	//$type='text/plain';		// OK
	//$attachment = false;		// OK
	
	if ($encoding)   header('Content-Encoding: '.$encoding);
	if ($type)       header('Content-Type: '.$type);
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	
	// Ajout directives pour resoudre bug IE
	//header('Cache-Control: Public, must-revalidate');
	//header('Pragma: public');
	 
	// Clean parameters
	$outputfile=$conf->agenda->dir_temp.'/'.$filename;
	$result=readfile($outputfile);
	if (! $result) print 'File '.$outputfile.' was empty.';

//	header("Location: ".DOL_URL_ROOT.'/document.php?modulepart=agenda&file='.urlencode($filename));
	exit;
}

llxHeader();
print '<div class="error">'.$agenda->error.'</div>';
llxFooter('$Date$ - $Revision$');
?>
