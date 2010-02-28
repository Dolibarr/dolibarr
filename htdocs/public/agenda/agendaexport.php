<?php
/* Copyright (C) 2008-2010 Laurent Destailleur <eldy@users.sourceforge.net>
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

/**	    \file       htdocs/public/agenda/agendaexport.php
 *      \ingroup    agenda
 *		\brief      Page to export agenda
 *					http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=rss&exportkey=cle&filter=mine
 *		\version    $Id$
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// C'est un wrapper, donc header vierge
function llxHeaderVierge() { print '<html><title>Export agenda cal</title><body>'; }
function llxFooterVierge() { print '</body></html>'; }

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');

// Security check
if (! $conf->agenda->enabled) accessforbidden('',1,1,1);

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


// Check config
if (empty($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY))
{
	$user->getrights();

	llxHeaderVierge();
	print '<div class="error">Module Agenda was not configured properly.</div>';
	llxFooterVierge('$Date$ - $Revision$');
	exit;
}

// Check exportkey
if (empty($_GET["exportkey"]) || $conf->global->MAIN_AGENDA_XCAL_EXPORTKEY != $_GET["exportkey"])
{
	$user->getrights();

	llxHeaderVierge();
	print '<div class="error">Bad value for key.</div>';
	llxFooterVierge('$Date$ - $Revision$');
	exit;
}

// Define filename with prefix on filters predica (each predica set must have on cache file)
$filename='';
$shortfilename='';
if ($format == 'vcal') $shortfilename='dolibarrcalendar.vcs';
if ($format == 'ical') $shortfilename='dolibarrcalendar.ics';
if ($format == 'rss')  $shortfilename='dolibarrcalendar.rss';
$filename=$shortfilename;
if (! $filename)
{
	$langs->load("main");
	$langs->load("errors");
	llxHeaderVierge();
    print '<div class="error">'.$langs->trans("ErrorWrongValueForParameterX",'format').'</div>';
	llxFooterVierge('$Date$ - $Revision$');
	exit;
}
foreach ($filters as $key => $value)
{
	if ($key == 'year')     $filename.='.year'.$value;
	if ($key == 'idaction') $filename.='.id'.$value;
	if ($key == 'login')	$filename.='.login'.$value;
	if ($key == 'logina')	$filename.='.logina'.$value;
	if ($key == 'logind')	$filename.='.logind'.$value;
	if ($key == 'logint')	$filename.='.logint'.$value;
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
		if ($attachment) 		header('Content-Disposition: attachment; filename="'.$shortfilename.'"');

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
	else
	{
		print 'Error '.$agenda->error;

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
	else
	{
		print 'Error '.$agenda->error;

		exit;
	}
}


llxHeaderVierge();
print '<div class="error">'.$agenda->error.'</div>';
llxFooterVierge('$Date$ - $Revision$');
?>
