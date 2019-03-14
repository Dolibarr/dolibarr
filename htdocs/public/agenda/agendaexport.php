<?php
/* Copyright (C) 2008-2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 	\file       htdocs/public/agenda/agendaexport.php
 * 	\ingroup    agenda
 * 	\brief      Page to export agenda
 * 				http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=vcal&exportkey=cle
 * 				http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=ical&type=event&exportkey=cle
 * 				http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=rss&exportkey=cle
 *              Other parameters into url are:
 *              &notolderthan=99
 *              &year=2015
 *              &id=..., &idfrom=..., &idto=...
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOLOGIN'))        define("NOLOGIN",1);		// This means this output page does not require to be logged.
if (! defined('NOCSRFCHECK'))    define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// C'est un wrapper, donc header vierge

/**
 * Header function
 *
 * @return	void
 */
function llxHeaderVierge()
{
    print '<html><title>Export agenda cal</title><body>';
}
/**
 * Footer function
 *
 * @return	void
 */
function llxFooterVierge()
{
    print '</body></html>';
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

// Security check
if (empty($conf->agenda->enabled)) accessforbidden('',0,0,1);

// Not older than
if (! isset($conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY)) $conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY=100;	// default limit

// Define format, type and filter
$format='ical';
$type='event';
if (GETPOST("format",'alpha')) $format=GETPOST("format",'apha');
if (GETPOST("type",'apha'))   $type=GETPOST("type",'alpha');

$filters=array();
if (GETPOST("year",'int')) 	         $filters['year']=GETPOST("year",'int');
if (GETPOST("id",'int'))             $filters['id']=GETPOST("id",'int');
if (GETPOST("idfrom",'int'))         $filters['idfrom']=GETPOST("idfrom",'int');
if (GETPOST("idto",'int'))           $filters['idto']=GETPOST("idto",'int');
if (GETPOST("project",'apha'))       $filters['project']=GETPOST("project",'apha');
if (GETPOST("logina",'apha'))        $filters['logina']=GETPOST("logina",'apha');
if (GETPOST("logint",'apha'))        $filters['logint']=GETPOST("logint",'apha');
if (GETPOST("notactiontype",'apha')) $filters['notactiontype']=GETPOST("notactiontype",'apha');
if (GETPOST("actiontype",'apha'))    $filters['actiontype']=GETPOST("actiontype",'apha');
if (GETPOST("notolderthan",'int'))   $filters['notolderthan']=GETPOST("notolderthan","int");
else $filters['notolderthan']=$conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY;

// Check config
if (empty($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY))
{
	$user->getrights();

	llxHeaderVierge();
	print '<div class="error">Module Agenda was not configured properly.</div>';
	llxFooterVierge();
	exit;
}

// Check exportkey
if (empty($_GET["exportkey"]) || $conf->global->MAIN_AGENDA_XCAL_EXPORTKEY != $_GET["exportkey"])
{
	$user->getrights();

	llxHeaderVierge();
	print '<div class="error">Bad value for key.</div>';
	llxFooterVierge();
	exit;
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('agendaexport'));

// Define filename with prefix on filters predica (each predica set must have on cache file)
$shortfilename='dolibarrcalendar';
$filename=$shortfilename;
// Complete long filename
foreach ($filters as $key => $value)
{
    //if ($key == 'notolderthan')    $filename.='-notolderthan'.$value; This filter key is already added before and does not need to be in filename
	if ($key == 'year')            $filename.='-year'.$value;
    if ($key == 'id')              $filename.='-id'.$value;
    if ($key == 'idfrom')          $filename.='-idfrom'.$value;
    if ($key == 'idto')            $filename.='-idto'.$value;
    if ($key == 'project')         $filename.='-project'.$value;
	if ($key == 'logina')	       $filename.='-logina'.$value;	// Author
	if ($key == 'logint')	       $filename.='-logint'.$value;	// Assigned to
	if ($key == 'notactiontype')   $filename.='-notactiontype'.$value;
}
// Add extension
if ($format == 'vcal') { $shortfilename.='.vcs'; $filename.='.vcs'; }
if ($format == 'ical') { $shortfilename.='.ics'; $filename.='.ics'; }
if ($format == 'rss')  { $shortfilename.='.rss'; $filename.='.rss'; }

if ($shortfilename=='dolibarrcalendar')
{
	$langs->load("main");
	$langs->load("errors");
	llxHeaderVierge();
    print '<div class="error">'.$langs->trans("ErrorWrongValueForParameterX",'format').'</div>';
	llxFooterVierge();
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

		if ($contenttype)       header('Content-Type: '.$contenttype.($outputencoding?'; charset='.$outputencoding:''));
		if ($attachment) 		header('Content-Disposition: attachment; filename="'.$shortfilename.'"');

		if ($cachedelay) header('Cache-Control: max-age='.$cachedelay.', private, must-revalidate');
		else header('Cache-Control: private, must-revalidate');

		// Clean parameters
		$outputfile=$conf->agenda->dir_temp.'/'.$filename;
		$result=readfile($outputfile);
		if (! $result) print 'File '.$outputfile.' was empty.';

	    //header("Location: ".DOL_URL_ROOT.'/document.php?modulepart=agenda&file='.urlencode($filename));
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

		if ($contenttype)       header('Content-Type: '.$contenttype.($outputencoding?'; charset='.$outputencoding:''));
		if ($attachment) 		header('Content-Disposition: attachment; filename="'.$filename.'"');

		// Ajout directives pour resoudre bug IE
		//header('Cache-Control: Public, must-revalidate');
		//header('Pragma: public');
		if ($cachedelay) header('Cache-Control: max-age='.$cachedelay.', private, must-revalidate');
		else header('Cache-Control: private, must-revalidate');

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
llxFooterVierge();
