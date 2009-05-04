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
					http://127.0.0.1/dolibarr/comm/action/agendaexport.php?format=rss&exportkey=cle&filter=mine
		\version    $Id$
*/

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');


$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
$leftmenu=isset($_GET["leftmenu"])?$_GET["leftmenu"]:"";

// Define format, type and filter
$format='ical';
$type='event';
if (! empty($_GET["format"])) $format=$_GET["format"];
if (! empty($_GET["type"]))   $type=$_GET["type"];
$filters=array();
if (! empty($_GET["year"])) 	$filters['year']=$_GET["year"];
if (! empty($_GET["idaction"])) $filters['idaction']=$_GET["idaction"];
if (! empty($_GET["login"]))    $filters['login']=$_GET["login"];
if (! empty($_GET["logina"]))   $filters['logina']=$_GET["logina"];
if (! empty($_GET["logint"]))   $filters['logint']=$_GET["logint"];
if (! empty($_GET["logind"]))   $filters['logind']=$_GET["logind"];


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

// Check exportkey
if (empty($_GET["exportkey"]) || $conf->global->MAIN_AGENDA_XCAL_EXPORTKEY != $_GET["exportkey"])
{
	$user->getrights();

	llxHeader();
	print '<div class="error">Bad value for key.</div>';
	llxFooter('$Date$ - $Revision$');
	exit;
}

// Define filename
$filename='';
if ($format == 'vcal') $filename='dolibarrcalendar.vcs';
if ($format == 'ical') $filename='dolibarrcalendar.ics';
if ($format == 'rss')  $filename='dolibarrcalendar.rss';
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


$agenda=new ActionComm($db);

$cachedelay=0;
if (! empty($conf->global->MAIN_AGENDA_EXPORT_CACHE)) $cachedelay=$conf->global->MAIN_AGENDA_EXPORT_CACHE;

// Build file
if ($format == 'ical' || $format == 'vcal')
{
	$result=$agenda->build_exportfile($format,$type,$cachedelay,$filename,$filters);
	if ($result >= 0)
	{
		$attachment = true;
		if (isset($_GET["attachment"])) $attachment=$_GET["attachment"];
		//$attachment = false;
		$contenttype='text/calendar';
		if (isset($_GET["contenttype"])) $contenttype=$_GET["contenttype"];
		//$contenttype='text/plain';
		$outputencoding='UTF-8';
		//$contenttype='ISO-8859-1';

		if ($contenttype)       header('Content-Type: '.$contenttype.($outputencoding?'; charset='.$outputencoding:''));
		if ($attachment) 		header('Content-Disposition: attachment; filename="'.$filename.'"');

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
}

if ($format == 'rss')
{
	$result=$agenda->build_exportfile($format,$type,$cachedelay,$filename,$filters);
	if ($result >= 0)
	{
		$attachment = false;
		if (isset($_GET["attachment"])) $attachment=$_GET["attachment"];
		//$attachment = false;
		$contenttype='application/rss+xml';
		if (isset($_GET["contenttype"])) $contenttype=$_GET["contenttype"];
		//$contenttype='text/plain';
		$outputencoding='UTF-8';
		//$contenttype='ISO-8859-1';

		if ($contenttype)       header('Content-Type: '.$contenttype.($outputencoding?'; charset='.$outputencoding:''));
		if ($attachment) 		header('Content-Disposition: attachment; filename="'.$filename.'"');

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
}


llxHeader();
print '<div class="error">'.$agenda->error.'</div>';
llxFooter('$Date$ - $Revision$');
?>
