<?php
/* Copyright (C) 2008-2024 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Charlene Benke  		<charlene@patas-monkey.com>
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
 * 	\file       htdocs/public/fichinter/calendarexport.php
 * 	\ingroup    fichinter
 * 	\brief      Page to export fichinter agenda into a vcal, ical or rss
 * 				http://127.0.0.1/dolibarr/public/fichinter/calendarexport.php?format=vcal&exportkey=cle
 * 				http://127.0.0.1/dolibarr/public/fichinter/calendarexport.php?format=ical&type=event&exportkey=cle
 * 				http://127.0.0.1/dolibarr/public/fichinter/calendarexport.php?format=rss&exportkey=cle
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


// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

$fichinterStatic = new Fichinter($db);

// Not older than
if (!getDolGlobalString('MAIN_FICHINTER_EXPORT_PAST_DELAY')) {
	$conf->global->MAIN_FICHINTER_EXPORT_PAST_DELAY = 100; // default limit
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

if (GETPOSTINT("notolderthan")) {
	$filters['notolderthan'] = GETPOSTINT("notolderthan");
} else {
	$filters['notolderthan'] = getDolGlobalString('MAIN_FICHINTER_EXPORT_PAST_DELAY', 100);
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
if (!isModEnabled('intervention')) {
	httponly_accessforbidden('Module fichinter not enabled');
}


/*
 * View
 */

// Check config
if (!getDolGlobalString('MAIN_FICHINTER_XCAL_EXPORTKEY')) {
	top_httphead();

	print '<html><title>Export fichinter cal</title><body>';
	print '<div class="error">Module Agenda was not configured properly.</div>';
	print '</body></html>';
	exit;
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('fichinterexport'));

// Note that $action and $object may have been modified by some
$reshook = $hookmanager->executeHooks('doActions', $filters);
if ($reshook < 0) {
	top_httphead();

	print '<html><title>Export fichinter cal</title><body>';
	if (!empty($hookmanager->errors) && is_array($hookmanager->errors)) {
		print '<div class="error">'.implode('<br>', $hookmanager->errors).'</div>';
	} else {
		print '<div class="error">'.$hookmanager->error.'</div>';
	}
	print '</body></html>';
} elseif (empty($reshook)) {
	// Check exportkey
	if (!GETPOST("exportkey") || getDolGlobalString('MAIN_FICHINTER_XCAL_EXPORTKEY') != GETPOST("exportkey")) {
		top_httphead();

		print '<html><title>Export fichinter cal</title><body>';
		print '<div class="error">Bad value for key.</div>';
		print '</body></html>';
		exit;
	}
}

// Define filename with prefix on filters predica (each predica set must have on cache file)
$shortfilename = 'dolibarrcalendar';
$filename = $shortfilename;
// Complete long filename
foreach ($filters as $key => $value) {
	//if ($key == 'notolderthan')
	//    $filename.='-notolderthan'.$value; This filter key is already added before and does not need to be in filename
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

	print '<html><title>Export fichinter cal</title><body>';
	print '<div class="error">'.$langs->trans("ErrorWrongValueForParameterX", 'format').'</div>';
	print '</body></html>';
	exit;
}

$fichinter = new Fichinter($db);

$cachedelay = 0;
if (getDolGlobalString('MAIN_FICHINTER_EXPORT_CACHE')) {
	$cachedelay = getDolGlobalString('MAIN_FICHINTER_EXPORT_CACHE');
}

$exportholidays = GETPOSTINT('includeholidays');

// Build file
if ($format == 'ical' || $format == 'vcal') {
	$result = build_exportfile($format, $type, $cachedelay, $filename, $filters);
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

		// By default, frames allowed only if on same domain (stop some XSS attacks)
		header("X-Frame-Options: SAMEORIGIN");

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

		print 'Error '.$fichinterStatic->error;

		exit;
	}
}

if ($format == 'rss') {
	$result = build_exportfile($format, $type, $cachedelay, $filename, $filters);
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

		// By default, frames allowed only if on same domain (stop some XSS attacks)
		header("X-Frame-Options: SAMEORIGIN");

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

		print 'Error '.$fichinterStatic->error;

		exit;
	}
}


top_httphead();

print '<html><title>Export fichinter cal</title><body>';
print '<div class="error">'.$fichinterStatic->error.'</div>';
print '</body></html>';



// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Export events from database into a cal file.
 *
 * @param string    $format         			The format of the export 'vcal', 'ical/ics' or 'rss'
 * @param string    $type           			The type of the export 'event' or 'journal'
 * @param integer   $cachedelay     			Do not rebuild file if date older than cachedelay seconds
 * @param string    $filename       			The name for the exported file.
 * @param array<string,int|string>	$filters	Array of filters.
 * @return int<-1,1>                			-1 = error on build export file, 0 = export okay
 */
function build_exportfile($format, $type, $cachedelay, $filename, $filters)
{

	// quelques filtres possible au nivau du tableau $filters
	// logina : user login who is create interventional (author)
	// logini : user login who make the intenventional
	// loginr : user login who is responsible of interventional

	global $hookmanager;
	global $db;

	// phpcs:enable
	global $conf, $langs, $dolibarr_main_url_root, $mysoc;

	require_once DOL_DOCUMENT_ROOT."/core/lib/xcal.lib.php";
	require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
	require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

	dol_syslog("build_exportfile Build export file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".count($filters), LOG_DEBUG);

	// Check parameters
	if (empty($format)) {
		return -1;
	}

	// Clean parameters
	if (!$filename) {
		$extension = 'vcs';
		if ($format == 'ical') {
			$extension = 'ics';
		}
		$filename = $format.'.'.$extension;
	}

	// Create dir and define output file (definitive and temporary)
	$result = dol_mkdir($conf->agenda->dir_temp);
	$outputfile = $conf->agenda->dir_temp.'/'.$filename;

	$result = 0;

	$buildfile = true;
	$login = '';
	$logina = '';
	$logini = '';
	$loginr = '';

	$eventorganization = '';

	$now = dol_now();

	if ($cachedelay) {
		$nowgmt = dol_now();
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if (dol_filemtime($outputfile) > ($nowgmt - $cachedelay)) {
			dol_syslog("build_exportfile file ".$outputfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay."). Build is canceled");
			$buildfile = false;
		}
	}

	if ($buildfile) {
		// Build event array
		$eventarray = array();

		$sql = "SELECT f.rowid,";
		$sql .= " fd.date,"; // on récupère la date et la durée sur le détail d'inter pour avoir aussi l'heure
		$sql .= " f.datee,"; // End ne sera pas utilisée
		$sql .= " fd.duree,"; // durée de l'intervention
		$sql .= " f.datec, f.tms as datem,";
		$sql .= " f.ref, f.ref_client, fd.description, f.note_private, f.note_public,";
		$sql .= " f.fk_soc,";
		$sql .= " f.fk_user_author, f.fk_user_modif,";
		$sql .= " f.fk_user_valid,";
		$sql .= " u.firstname, u.lastname, u.email,";
		$sql .= " p.ref as ref_project, c.ref as ref_contract,";
		$sql .= " s.nom as socname, f.fk_statut";

		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON f.rowid = fd.fk_fichinter";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = f.fk_user_author";

		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = f.fk_soc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = f.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as c on c.rowid = f.fk_contrat";

		$parameters = array('filters' => $filters);
		// Note that $action and $object may have been modified by hook
		$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);
		$sql .= $hookmanager->resPrint;

		$sql .= " WHERE f.entity IN (".getEntity('fichinter').")";

		foreach ($filters as $key => $value) {
			if ($key == 'notolderthan' && $value != '') {
				$sql .= " AND fd.date >= '".$db->idate($now - ($value * 24 * 60 * 60))."'";
			}
			if ($key == 'year') {
				$sql .= " AND fd.date BETWEEN '".$db->idate(dol_get_first_day($value, 1))."'";
				$sql .= "     AND '".$db->idate(dol_get_last_day($value, 12))."'";
			}
			if ($key == 'id') {
				$sql .= " AND f.rowid = ".(is_numeric($value) ? $value : 0);
			}
			if ($key == 'idfrom') {
				$sql .= " AND f.rowid >= ".(is_numeric($value) ? $value : 0);
			}
			if ($key == 'idto') {
				$sql .= " AND f.rowid <= ".(is_numeric($value) ? $value : 0);
			}
			if ($key == 'project') {
				$sql .= " AND f.fk_project = ".(is_numeric($value) ? $value : 0);
			}
			if ($key == 'contract') {
				$sql .= " AND f.fk_contract = ".(is_numeric($value) ? $value : 0);
			}

			if ($key == 'logina') {
				$logina = $value;
				$condition = '=';
				if (preg_match('/^!/', $logina)) {
					$logina = preg_replace('/^!/', '', $logina);
					$condition = '<>';
				}
				$userforfilter = new User($db);
				$result = $userforfilter->fetch(0, $logina);
				if ($result > 0) {
					$sql .= " AND a.fk_user_author ".$condition." ".$userforfilter->id;
				} elseif ($result < 0 || $condition == '=') {
					$sql .= " AND a.fk_user_author = 0";
				}
			}
			if ($key == 'logini') {
				$logini = $value;
				$condition = '=';
				if (preg_match('/^!/', $logini)) {
					$logini = preg_replace('/^!/', '', $logini);
					$condition = '<>';
				}
				$userforfilter = new User($db);
				$result = $userforfilter->fetch(0, $logini);
				$sql .= " AND EXISTS (SELECT ec.rowid FROM ".MAIN_DB_PREFIX."element_contact as ec";
				$sql .= " WHERE ec.element_id = f.rowid";
				$sql .= " AND ec.fk_c_type_contact = 26";
				if ($result > 0) {
					$sql .= " AND ec.fk_socpeople = ".((int) $userforfilter->id);
				} elseif ($result < 0 || $condition == '=') {
					$sql .= " AND ec.fk_socpeople = 0";
				}
				$sql .= ")";
			}
			if ($key == 'loginr') {
				$loginr = $value;
				$condition = '=';
				if (preg_match('/^!/', $loginr)) {
					$loginr = preg_replace('/^!/', '', $loginr);
					$condition = '<>';
				}
				$userforfilter = new User($db);
				$result = $userforfilter->fetch(0, $loginr);
				$sql .= " AND EXISTS (SELECT ecr.rowid FROM ".MAIN_DB_PREFIX."element_contact as ecr";
				$sql .= " WHERE ecr.element_id = f.rowid";
				$sql .= " WHERE AND ecr.fk_c_type_contact = 27";
				if ($result > 0) {
					$sql .= " AND ecr.fk_socpeople = ".((int) $userforfilter->id);
				} elseif ($result < 0 || $condition == '=') {
					$sql .= " AND ecr.fk_socpeople = 0";
				}
				$sql .= ")";
			}

			if ($key == 'status') {
				$sql .= " AND f.fk_statut = ".((int) $value);
			}
		}

		// To exclude corrupted events and avoid errors in lightning/sunbird import
		$sql .= " AND f.dateo IS NOT NULL";

		$parameters = array('filters' => $filters);
		// Note that $action and $object may have been modified by hook
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
		$sql .= $hookmanager->resPrint;

		$sql .= " ORDER by fd.date";


		if (!empty($filters['limit'])) {
			$sql .= $db->plimit((int) $filters['limit']);
		}

		// print $sql;exit;

		dol_syslog("build_exportfile select event(s)", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$diff = 0;
			while ($obj = $db->fetch_object($resql)) {
				$qualified = true;

				// 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
				$event = array();
				$event['uid'] = 'dolibarragenda-'.$db->database_name.'-'.$obj->id."@".$_SERVER["SERVER_NAME"];
				$event['type'] = $type;

				$datestart = $db->jdate($obj->date) - ((int) getDolGlobalString('MAIN_FICHINTER_EXPORT_FIX_TZ', 0) * 3600);

				// fix for -> Warning: A non-numeric value encountered
				// if (is_numeric($db->jdate($obj->datee))) {
				// 	$dateend = $db->jdate($obj->datee) - ((int) getDolGlobalString('MAIN_FICHINTER_EXPORT_FIX_TZ', 0) * 3600);
				// } else {
				// 	// use start date as fall-back to avoid pb with empty end date on ICS readers
				// 	$dateend = $datestart;
				// }

				$duration = $obj->duree;
				$event['location'] = ($obj->socname ? $obj->socname : "");
				$event['summary'] = $obj->ref." - ". $obj->description;
				if ($obj->ref_client)
				$event['summary'].= " (".$obj->ref_client.")";

				$event['desc'] = $obj->description;

				if ($obj->ref_project)
					$event['desc'] .= " - ".$obj->ref_project;

				if ($obj->ref_contract)
					$event['desc'] .= " - ".$obj->ref_contract;

				if ($obj->note_public)
					$event['desc'].= " - ".$obj->note_public;

				$event['startdate'] = $datestart;
				$event['enddate'] = ''; // $dateend; // Not required with type 'journal'
				$event['duration'] = $duration; // Not required with type 'journal'
				$event['author'] = dolGetFirstLastname($obj->firstname, $obj->lastname);

				// OPAQUE (busy) or TRANSPARENT (not busy)
				//$event['transparency'] = (($obj->transparency > 0) ? 'OPAQUE' : 'TRANSPARENT');
				//$event['category'] = $obj->type_label;
				$event['email'] = $obj->email;

				// Public URL of event
				if ($eventorganization != '') {
					$link_subscription = $dolibarr_main_url_root.'/public/eventorganization/attendee_new.php?id='.((int) $obj->id).'&type=global&noregistration=1';
					$encodedsecurekey = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY').'conferenceorbooth'.((int) $obj->id), 'md5');
					$link_subscription .= '&securekey='.urlencode($encodedsecurekey);

					//$event['url'] = $link_subscription;
				}

				$event['created'] = $db->jdate($obj->datec) - ((int) getDolGlobalString('MAIN_FICHINTER_EXPORT_FIX_TZ', 0) * 3600);
				$event['modified'] = $db->jdate($obj->datem) - ((int) getDolGlobalString('MAIN_FICHINTER_EXPORT_FIX_TZ', 0) * 3600);
				// $event['num_vote'] = $this->num_vote;
				// $event['event_paid'] = $this->event_paid;
				$event['status'] = $obj->fk_statut;

				// // TODO: find a way to call "$this->fetch_userassigned();" without override "$this" properties
				// $this->id = $obj->rowid;
				// $this->fetch_userassigned(false);

				// $assignedUserArray = array();

				// foreach ($this->userassigned as $key => $value) {
				// 	$assignedUser = new User($db);
				// 	$assignedUser->fetch($value['id']);

				// 	$assignedUserArray[$key] = $assignedUser;
				// }

				// $event['assignedUsers'] = $assignedUserArray;

				if ($qualified && $datestart) {
					$eventarray[] = $event;
				}
				$diff++;
			}

			$parameters = array('filters' => $filters, 'eventarray' => &$eventarray);
			// Note that $action and $object may have been modified by hook
			$reshook = $hookmanager->executeHooks('addMoreEventsExport', $parameters);
			if ($reshook > 0) {
				$eventarray = $hookmanager->resArray;
			}
		} else {
			print $db->lasterror();
			return -1;
		}

		$langs->load("agenda");

		// Define title and desc
		$title = '';
		$more = '';
		if ($login) {
			$more = $langs->transnoentities("User").' '.$login;
		}
		if ($logina) {
			$more = $langs->transnoentities("ActionsAskedBy").' '.$logina;
		}
		if ($logini) {
			$more = $langs->transnoentities("ActionsToDoBy").' '.$logini;
		}
		if ($eventorganization) {
			$langs->load("eventorganization");
			$title = $langs->transnoentities("OrganizedEvent").(empty($eventarray[0]['label']) ? '' : ' '.$eventarray[0]['label']);
			$more = 'ICS file - '.$langs->transnoentities("OrganizedEvent").(empty($eventarray[0]['label']) ? '' : ' '.$eventarray[0]['label']);
		}
		if ($more) {
			if (empty($title)) {
				$title = 'Dolibarr actions '.$mysoc->name.' - '.$more;
			}
			$desc = $more;
			$desc .= ' ('.$mysoc->name.' - built by Dolibarr)';
		} else {
			if (empty($title)) {
				$title = 'Dolibarr actions '.$mysoc->name;
			}
			$desc = $langs->transnoentities('ListOfActions');
			$desc .= ' ('.$mysoc->name.' - built by Dolibarr)';
		}

		// Create temp file
		// Temporary file (allow call of function by different threads
		$outputfiletmp = tempnam($conf->fichinter->dir_temp, 'tmp');
		dolChmod($outputfiletmp);

		// Write file
		if ($format == 'vcal') {
			$result = build_calfile($format, $title, $desc, $eventarray, $outputfiletmp);
		} elseif ($format == 'ical') {
			$result = build_calfile($format, $title, $desc, $eventarray, $outputfiletmp);
		} elseif ($format == 'rss') {
			$result = build_rssfile($format, $title, $desc, $eventarray, $outputfiletmp);
		}

		if ($result >= 0) {
			if (dol_move($outputfiletmp, $outputfile, 0, 1, 0, 0)) {
				$result = 1;
			} else {
				$error = 'Failed to rename '.$outputfiletmp.' into '.$outputfile;
				dol_syslog("build_exportfile ".$error, LOG_ERR);
				dol_delete_file($outputfiletmp, 0, 1);
				$result = -1;
			}
		} else {
			dol_syslog("build_exportfile build_xxxfile function fails to for format=".$format." outputfiletmp=".$outputfile, LOG_ERR);
			dol_delete_file($outputfiletmp, 0, 1);
			$langs->load("errors");
			$error = $langs->trans("ErrorFailToCreateFile", $outputfile);
		}
	}
	return $result;
}
