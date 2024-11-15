<?php
/* Copyright (C) 2008-2024  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * 	\file       htdocs/public/agenda/agendaexport.php
 * 	\ingroup    agenda
 * 	\brief      Page to export agenda into a vcal, ical or rss
 * 				http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=vcal&exportkey=cle
 * 				http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=ical&type=event&exportkey=cle
 * 				http://127.0.0.1/dolibarr/public/agenda/agendaexport.php?format=rss&exportkey=cle
 *              Other parameters into url are:
 *              &notolderthan=99
 *              &year=2015
 *              &limit=1000
 *              &id=..., &idfrom=..., &idto=...
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}


// It's a wrapper, so empty header

/**
 * Header function
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	string[]|string	$arrayofjs			Array of complementary js files
 * @param 	string[]|string	$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])
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

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
$object = new ActionComm($db);

// Not older than
if (!getDolGlobalString('MAIN_AGENDA_EXPORT_PAST_DELAY')) {
	$conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY = 100; // default limit
}

// Define format, type and filter
$format = 'ical';
$type = 'event';
if (GETPOST("format", 'alpha')) {
	$format = GETPOST("format", 'alpha');
}
if (GETPOST("type", 'alpha')) {
	$type = GETPOST("type", 'alpha');
}

$filters = array();
if (GETPOSTINT("year")) {
	$filters['year'] = GETPOSTINT("year");
}
if (GETPOSTINT("id")) {
	$filters['id'] = GETPOSTINT("id");
}
if (GETPOSTINT("idfrom")) {
	$filters['idfrom'] = GETPOSTINT("idfrom");
}
if (GETPOSTINT("idto")) {
	$filters['idto'] = GETPOSTINT("idto");
}
if (GETPOST("project", 'alpha')) {
	$filters['project'] = GETPOST("project", 'alpha');
}
if (GETPOST("logina", 'alpha')) {
	$filters['logina'] = GETPOST("logina", 'alpha');
}
if (GETPOST("logint", 'alpha')) {
	$filters['logint'] = GETPOST("logint", 'alpha');
}
if (GETPOST("notactiontype", 'alpha')) {	// deprecated
	$filters['notactiontype'] = GETPOST("notactiontype", 'alpha');
}
if (GETPOST("actiontype", 'alpha')) {
	$filters['actiontype'] = GETPOST("actiontype", 'alpha');
}
if (GETPOST("actioncode", 'alpha')) {
	$filters['actioncode'] = GETPOST("actioncode", 'alpha');
}
if (GETPOSTINT("notolderthan")) {
	$filters['notolderthan'] = GETPOSTINT("notolderthan");
} else {
	$filters['notolderthan'] = getDolGlobalString('MAIN_AGENDA_EXPORT_PAST_DELAY', 100);
}
if (GETPOSTINT("limit")) {
	$filters['limit'] = GETPOSTINT("limit");
} else {
	$filters['limit'] = 1000;
}
if (GETPOST("module", 'alpha')) {
	$filters['module'] = GETPOST("module", 'alpha');
}
if (GETPOST("status", "intcomma")) {
	$filters['status'] = GETPOST("status", "intcomma");
}

// Security check
if (!isModEnabled('agenda')) {
	httponly_accessforbidden('Module Agenda not enabled');
}


/*
 * View
 */

// Check config
if (!getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) {
	$user->loadRights();

	top_httphead();

	llxHeaderVierge("");
	print '<div class="error">Module Agenda was not configured properly.</div>';
	llxFooterVierge();
	exit;
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('agendaexport'));

$reshook = $hookmanager->executeHooks('doActions', $filters); // Note that $action and $object may have been modified by some
if ($reshook < 0) {
	top_httphead();

	llxHeaderVierge("");
	if (!empty($hookmanager->errors) && is_array($hookmanager->errors)) {
		print '<div class="error">'.implode('<br>', $hookmanager->errors).'</div>';
	} else {
		print '<div class="error">'.$hookmanager->error.'</div>';
	}
	llxFooterVierge();
} elseif (empty($reshook)) {
	// Check exportkey
	if (!GETPOST("exportkey") || getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY') != GETPOST("exportkey")) {
		$user->loadRights();

		top_httphead();

		llxHeaderVierge("");
		print '<div class="error">Bad value for key.</div>';
		llxFooterVierge();
		exit;
	}
}


// Define filename with prefix on filters predica (each predica set must have on cache file)
$shortfilename = 'calendar';
$filename = $shortfilename;
// Complete long filename
foreach ($filters as $key => $value) {
	//if ($key == 'notolderthan')    $filename.='-notolderthan'.$value; This filter key is already added before and does not need to be in filename
	if ($key == 'year') {
		$filename .= '-year'.$value;
	}
	if ($key == 'id') {
		$filename .= '-id'.$value;
	}
	if ($key == 'idfrom') {
		$filename .= '-idfrom'.$value;
	}
	if ($key == 'idto') {
		$filename .= '-idto'.$value;
	}
	if ($key == 'project') {
		$filename .= '-project'.$value;
		$shortfilename .= '-project'.$value;
	}
	if ($key == 'logina') {
		$filename .= '-logina'.$value; // Author
	}
	if ($key == 'logint') {
		$filename .= '-logint'.$value; // Assigned to
	}
	if ($key == 'notactiontype') {	// deprecated
		$filename .= '-notactiontype'.$value;
	}
	if ($key == 'actiontype') {
		$filename .= '-actiontype'.$value;
	}
	if ($key == 'actioncode') {
		$filename .= '-actioncode'.$value;
	}
	if ($key == 'module') {
		$filename .= '-module'.$value;
		if ($value == 'project@eventorganization') {
			$shortfilename .= '-project';
		} elseif ($value == 'conforbooth@eventorganization') {
			$shortfilename .= '-conforbooth';
		}
	}
	if ($key == 'status') {
		$filename .= '-status'.$value;
	}
}
// Add extension
if ($format == 'vcal') {
	$shortfilename .= '.vcs';
	$filename .= '.vcs';
}
if ($format == 'ical') {
	$shortfilename .= '.ics';
	$filename .= '.ics';
}
if ($format == 'rss') {
	$shortfilename .= '.rss';
	$filename .= '.rss';
}
if ($shortfilename == 'dolibarrcalendar') {
	$langs->load("errors");

	top_httphead();

	llxHeaderVierge("");
	print '<div class="error">'.$langs->trans("ErrorWrongValueForParameterX", 'format').'</div>';
	llxFooterVierge();
	exit;
}

$agenda = new ActionComm($db);

$cachedelay = 0;
if (getDolGlobalString('MAIN_AGENDA_EXPORT_CACHE')) {
	$cachedelay = getDolGlobalString('MAIN_AGENDA_EXPORT_CACHE');
}

$exportholidays = GETPOSTINT('includeholidays');

// Build file
if ($format == 'ical' || $format == 'vcal') {
	// For export of conforbooth, we disable the filter 'notolderthan'
	if (!empty($filters['project']) && !empty($filters['module']) && ($filters['module'] == 'project@eventorganization' || $filters['module'] == 'conforbooth@eventorganization')) {
		$filters['notolderthan'] = null;
	}

	$result = $agenda->build_exportfile($format, $type, $cachedelay, $filename, $filters, $exportholidays);
	if ($result >= 0) {
		$attachment = true;
		if (GETPOSTISSET("attachment")) {
			$attachment = GETPOST("attachment");
		}
		//$attachment = false;
		$contenttype = 'text/calendar';
		if (GETPOSTISSET("contenttype")) {
			$contenttype = GETPOST("contenttype");
		}
		//$contenttype='text/plain';
		$outputencoding = 'UTF-8';

		if ($contenttype) {
			header('Content-Type: '.$contenttype.($outputencoding ? '; charset='.$outputencoding : ''));
		}
		if ($attachment) {
			header('Content-Disposition: attachment; filename="'.$shortfilename.'"');
		}

		if ($cachedelay) {
			header('Cache-Control: max-age='.$cachedelay.', private, must-revalidate');
		} else {
			header('Cache-Control: private, must-revalidate');
		}

		header("X-Frame-Options: SAMEORIGIN"); // By default, frames allowed only if on same domain (stop some XSS attacks)

		// Clean parameters
		$outputfile = $conf->agenda->dir_temp.'/'.$filename;
		$result = readfile($outputfile);
		if (!$result) {
			print 'File '.$outputfile.' was empty.';
		}

		//header("Location: ".DOL_URL_ROOT.'/document.php?modulepart=agenda&file='.urlencode($filename));
		exit;
	} else {
		top_httphead();

		print 'Error '.$agenda->error;

		exit;
	}
}

if ($format == 'rss') {
	$result = $agenda->build_exportfile($format, $type, $cachedelay, $filename, $filters, $exportholidays);
	if ($result >= 0) {
		$attachment = false;
		if (GETPOSTISSET("attachment")) {
			$attachment = GETPOST("attachment");
		}
		//$attachment = false;
		$contenttype = 'application/rss+xml';
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
		} else {
			header('Content-Disposition: inline; filename="'.$filename.'"');
		}

		// Ajout directives pour resoudre bug IE
		//header('Cache-Control: Public, must-revalidate');
		//header('Pragma: public');
		if ($cachedelay) {
			header('Cache-Control: max-age='.$cachedelay.', private, must-revalidate');
		} else {
			header('Cache-Control: private, must-revalidate');
		}

		header("X-Frame-Options: SAMEORIGIN"); // By default, frames allowed only if on same domain (stop some XSS attacks)

		// Clean parameters
		$outputfile = $conf->agenda->dir_temp.'/'.$filename;
		$result = readfile($outputfile);
		if (!$result) {
			print 'File '.$outputfile.' was empty.';
		}

		// header("Location: ".DOL_URL_ROOT.'/document.php?modulepart=agenda&file='.urlencode($filename));
		exit;
	} else {
		top_httphead();

		print 'Error '.$agenda->error;

		exit;
	}
}


top_httphead();

llxHeaderVierge("");
print '<div class="error">'.$agenda->error.'</div>';
llxFooterVierge();
