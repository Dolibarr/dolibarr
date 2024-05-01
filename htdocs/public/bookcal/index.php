<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2023		anthony Berton			<anthony.berton@bb2a.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *     	\file       htdocs/public/bookcal/index.php
 *		\ingroup    core
 *		\brief      File to offer a way to book a rendez-vous into a public calendar
 *					Example of URL: https://localhost/public/bookcal/index.php?id=...
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/class/calendar.class.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/class/availabilities.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

// Security check
if (!isModEnabled('bookcal')) {
	httponly_accessforbidden('Module Bookcal isn\'t enabled');
}

$langs->loadLangs(array("main", "other", "dict", "agenda", "errors", "companies"));

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$id_availability = GETPOSTINT('id_availability');

$year = GETPOSTINT("year") ? GETPOSTINT("year") : idate("Y");
$month = GETPOSTINT("month") ? GETPOSTINT("month") : idate("m");
$week = GETPOSTINT("week") ? GETPOSTINT("week") : idate("W");
$day = GETPOSTINT("day") ? GETPOSTINT("day") : idate("d");
$dateselect = dol_mktime(0, 0, 0, GETPOSTINT('dateselectmonth'), GETPOSTINT('dateselectday'), GETPOSTINT('dateselectyear'), 'tzuserrel');
if ($dateselect > 0) {
	$day = GETPOSTINT('dateselectday');
	$month = GETPOSTINT('dateselectmonth');
	$year = GETPOSTINT('dateselectyear');
}
$backtopage = GETPOST("backtopage", "alpha");

$object = new Calendar($db);
$result = $object->fetch($id);

$availability = new Availabilities($db);
if ($id_availability > 0) {
	$result = $availability->fetch($id_availability);
}

$now = dol_now();
$nowarray = dol_getdate($now);
$nowyear = $nowarray['year'];
$nowmonth = $nowarray['mon'];
$nowday = $nowarray['mday'];

$prev = dol_get_prev_month($month, $year);
$prev_year  = $prev['year'];
$prev_month = $prev['month'];
$next = dol_get_next_month($month, $year);
$next_year  = $next['year'];
$next_month = $next['month'];

$max_day_in_prev_month = idate("t", dol_mktime(0, 0, 0, $prev_month, 1, $prev_year, 'gmt')); // Nb of days in previous month
$max_day_in_month = idate("t", dol_mktime(0, 0, 0, $month, 1, $year)); // Nb of days in next month
// tmpday is a negative or null cursor to know how many days before the 1st to show on month view (if tmpday=0, 1st is monday)
$tmpday = - idate("w", dol_mktime(12, 0, 0, $month, 1, $year, 'gmt')) + 2; // idate('w') is 0 for sunday
$tmpday += ((isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1) - 1);
if ($tmpday >= 1) {
	$tmpday -= 7; // If tmpday is 0 we start with sunday, if -6, we start with monday of previous week.
}
// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow = dol_mktime(0, 0, 0, $prev_month, $max_day_in_prev_month + $tmpday, $prev_year, 'tzuserrel');
$next_day = 7 - ($max_day_in_month + 1 - $tmpday) % 7;
if ($next_day < 6) {
	$next_day += 7;
}
$lastdaytoshow = dol_mktime(0, 0, 0, $next_month, $next_day, $next_year, 'tzuserrel');

$datechosen = GETPOST('datechosen', 'alpha');
$datetimechosen = GETPOSTINT('datetimechosen');
$isdatechosen = false;
$timebooking = GETPOST("timebooking");
$datetimebooking = GETPOSTINT("datetimebooking");
$durationbooking = GETPOSTINT("durationbooking");
$errmsg = '';

/**
 * Show header for booking
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])
{
	global $user, $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	$urllogo = '';

	// Define urllogo
	if (getDolGlobalInt('BOOKCAL_SHOW_COMPANY_LOGO') || getDolGlobalString('BOOPKCAL_PUBLIC_INTERFACE_TOPIC')) {
		// Print logo
		if (getDolGlobalInt('BOOKCAL_SHOW_COMPANY_LOGO')) {
			$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

			if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
				$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
			} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
				$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$mysoc->logo);
			} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
				$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
			}
		}
	}

	print '<div class="center">';
	// Output html code for logo
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	if ($urllogo) {
		print '<a href="'.(getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') ? getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') : dol_buildpath('/public/ticket/index.php?entity='.$conf->entity, 1)).'">';
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
		print '</a>';
	}
	if (getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC')) {
		print '<div class="clearboth"></div><strong>'.(getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') ? getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') : $langs->trans("BookCalSystem")).'</strong>';
	}
	if (empty($urllogo) && ! getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC')) {
		print $mysoc->name;
	}
	print '</div>';
	if (!getDolGlobalInt('MAIN_HIDE_POWERED_BY')) {
		print '<div class="poweredbypublicpayment opacitymedium right hideonsmartphone"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';

	print '</div>';

	print '<div class="divmainbodylarge">';
}


/*
 * Actions
 */

if ($action == 'add') {
	$error = 0;
	$idcontact = 0;
	$calendar = $object;
	$contact = new Contact($db);
	$actioncomm = new ActionComm($db);

	if (!is_object($user)) {
		$user = new User($db);
	}

	$db->begin();

	if (!GETPOST("lastname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST("firstname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (!GETPOST("email")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}

	if (!$error) {
		$sql = "SELECT s.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as s";
		$sql .= " WHERE s.lastname = '".$db->escape(GETPOST("lastname"))."'";
		$sql .= " AND s.firstname = '".$db->escape(GETPOST("firstname"))."'";
		$sql .= " AND s.email = '".$db->escape(GETPOST("email"))."'";
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num > 0) {
				$obj = $db->fetch_object($resql);
				$idcontact = $obj->rowid;
				$contact->fetch($idcontact);
			} else {
				$contact->lastname = GETPOST("lastname");
				$contact->firstname = GETPOST("firstname");
				$contact->email = GETPOST("email");
				$result = $contact->create($user);
				if ($result < 0) {
					$error++;
					$errmsg .= $contact->error." ".implode(',', $contact->errors);
				}
			}
		} else {
			$error++;
			$errmsg .= $db->lasterror();
		}
	}

	if (!$error) {
		$dateend = dol_time_plus_duree(GETPOSTINT("datetimebooking"), GETPOST("durationbooking"), 'i');

		$actioncomm->label = $langs->trans("BookcalBookingTitle");
		$actioncomm->type = 'AC_RDV';
		$actioncomm->type_id = 5;
		$actioncomm->datep = GETPOSTINT("datetimebooking");
		$actioncomm->datef = $dateend;
		$actioncomm->note_private = GETPOST("description");
		$actioncomm->percentage = -1;
		$actioncomm->fk_bookcal_calendar = $id;
		$actioncomm->userownerid = $calendar->visibility;
		$actioncomm->contact_id = $contact->id;
		$actioncomm->socpeopleassigned = [
			$contact->id => [
				'id' => $contact->id,
				'mandatory' => 0,
				'answer_status' => 0,
				'transparency' =>0,
			]
		];

		$result = $actioncomm->create($user);
		if ($result < 0) {
			$error++;
			$errmsg .= $actioncomm->error." ".implode(',', $actioncomm->errors);
		}

		if (!$error) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources";
			$sql .= "(fk_actioncomm, element_type, fk_element, answer_status, mandatory, transparency";
			$sql .= ") VALUES (";
			$sql .= (int) $actioncomm->id;
			$sql .= ", 'socpeople'";
			$sql .= ", ". (int) $contact->id;
			$sql .= ", 0, 0, 0)";
			$resql = $db->query($sql);
			if (!$resql) {
				$error++;
				$errmsg .= $db->lasterror();
			}
		}
	}

	if (!$error) {
		$db->commit();
		$action = 'afteradd';
	} else {
		$db->rollback();
		$action = 'create';
	}
}


/*
 * View
 */

$form = new Form($db);

llxHeaderVierge('BookingCalendar');

print '<center><br><h2>'.(!empty($object->label) ? $object->label : $object->ref).'</h2></center>';

dol_htmloutput_errors($errmsg);

if ($action == 'create') {
	$backtopage = $_SERVER["PHP_SELF"].'?id='.$id.'&datechosen='.$datechosen;
} else {
	$backtopage = DOL_URL_ROOT.'/public/bookcal/index.php?id='.$id;
}

//print '<div class="">';

print '<div class="bookcalpublicarea centpercent center" style="min-width:30%;width:fit-content;height:70%;top:60%;left: 50%;">';
print '<div class="bookcalform" style="min-height:50%">';
if ($action == 'afteradd') {
	print '<h2>';
	print $langs->trans("BookingSuccessfullyBooked");
	print '</h2>';
	print $langs->trans("BookingReservationHourAfter", dol_print_date(GETPOSTINT("datetimebooking"), "dayhourtext"));
} else {
	$param = '';

	print '<table class="centpercent">';
	print '<tr>';
	print '<td>';
	if ($action != 'create') {
		print '<form name="formsearch" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="id" value="'.$id.'">';

		$nav = '<a href="?id='.$id."&year=".$prev_year."&month=".$prev_month.$param.'"><i class="fa fa-chevron-left"></i></a> &nbsp;'."\n";
		$nav .= ' <span id="month_name">'.dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%b %Y");
		$nav .= " </span>\n";
		$nav .= ' &nbsp; <a href="?id='.$id."&year=".$next_year."&month=".$next_month.$param.'"><i class="fa fa-chevron-right"></i></a>'."\n";
		if (empty($conf->dol_optimize_smallscreen)) {
			$nav .= ' &nbsp; <a href="?id='.$id."&year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param.'" class="datenowlink">'.$langs->trans("Today").'</a> ';
		}
		$nav .= $form->selectDate($dateselect, 'dateselect', 0, 0, 1, '', 1, 0);
		$nav .= '<button type="submit" class="liste_titre button_search valignmiddle" name="button_search_x" value="x"><span class="fa fa-search"></span></button>';

		print $nav;
		print '</form>';
	}
	print '</td>';
	print '<td>';
	print '<div class="bookingtab hidden" style="height:50%">';
	print '<div id="bookingtabspandate"></div>';
	print '</div>';
	print '</td>';
	print '</tr>';

	print '<tr>';
	if ($action == "create") {
		print '<td>';
		if (empty($datetimebooking)) {
			$timebookingarray = explode(" - ", $timebooking);
			$timestartarray = explode(":", $timebookingarray[0]);
			$timeendarray = explode(":", $timebookingarray[1]);
			$datetimebooking = dol_time_plus_duree($datetimechosen, intval($timestartarray[0]), "h");
			$datetimebooking = dol_time_plus_duree($datetimebooking, intval($timestartarray[1]), "i");
		}
		print '<span>'.img_picto("", "calendar")." ".dol_print_date($datetimebooking, 'dayhourtext').'</span>';
		print '<div class="center"><a href="'.$_SERVER["PHP_SELF"].'?id=1&year=2024&month=2" class="small">('.$langs->trans("SelectANewDate").')</a></div>';
		print '</td>';

		print '<td>';
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="datetimebooking" value="'.$datetimebooking.'">';
		print '<input type="hidden" name="datechosen" value="'.$datechosen.'">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="durationbooking" value="'.$durationbooking.'">';

		// Lastname
		print '<tr><td><input autofocus type="text" name="lastname" class="minwidth150" placeholder="'.dol_escape_htmltag($langs->trans("Lastname").'*').'" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";
		// Firstname
		print '<tr><td><input type="text" name="firstname" class="minwidth150" placeholder="'.dol_escape_htmltag($langs->trans("Firstname").'*').'" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";
		// EMail
		print '<tr><td><input type="email" name="email" maxlength="255" class="minwidth150" placeholder="'.dol_escape_htmltag($langs->trans("Email").'*').'" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";

		// Comments
		print '<tr>';
		print '<td class="tdtop">';
		print $langs->trans("Message");
		print '<textarea name="description" id="description" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_4.'">'.dol_escape_htmltag(GETPOST('description', 'restricthtml'), 0, 1).'</textarea></td>';
		print '</tr>'."\n";
		print '</table>'."\n";
		print '<div class="center">';
		print '<input type="submit" value="'.$langs->trans("Submit").'" id="submitsave" class="button">';
		print '</div>';
		print '</form>';
		print '</td>';
	} else {
		print '<td>';
		print '<table class="centpercent noborder nocellnopadd cal_pannel cal_month">';
		print ' <tr class="">';
		// Column title of weeks numbers
		print '  <td class="center hideonsmartphone">#</td>';
		$i = 0;
		while ($i < 7) {
			$numdayinweek = (($i + (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1)) % 7);
			if (!empty($conf->dol_optimize_smallscreen)) {
				print '  <td class="center bold uppercase tdfordaytitle'.($i == 0 ? ' borderleft' : '').'">';
				$labelshort = array(0 => 'SundayMin', 1 => 'MondayMin', 2 => 'TuesdayMin', 3 => 'WednesdayMin', 4 => 'ThursdayMin', 5 => 'FridayMin', 6 => 'SaturdayMin');
				print $langs->trans($labelshort[$numdayinweek]);
				print '  </td>'."\n";
			} else {
				print '  <td class="center minwidth75 bold uppercase small tdoverflowmax50 tdfordaytitle'.($i == 0 ? ' borderleft' : '').'">';
				//$labelshort = array(0=>'SundayMin', 1=>'MondayMin', 2=>'TuesdayMin', 3=>'WednesdayMin', 4=>'ThursdayMin', 5=>'FridayMin', 6=>'SaturdayMin');
				$labelshort = array(0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday');
				print $langs->trans($labelshort[$numdayinweek]);
				print '  </td>'."\n";
			}
			$i++;
		}
		print ' </tr>'."\n";

		$todayarray = dol_getdate($now, 'fast');
		$todaytms = dol_mktime(0, 0, 0, $todayarray['mon'], $todayarray['mday'], $todayarray['year']);

		// Load into an array all days with availabilities of the calendar for the current month $todayarray['mon'] and $todayarray['year']
		$arrayofavailabledays = array();

		$arrayofavailabilities = $availability->fetchAll('', '', 0, 0, '(status:=:1) AND (fk_bookcal_calendar:=:'.((int) $id).')');
		if ($arrayofavailabilities < 0) {
			setEventMessages($availability->error, $availability->errors, 'errors');
		} else {
			foreach ($arrayofavailabilities as $key => $value) {
				$startarray = dol_getdate($value->start);
				$endarray = dol_getdate($value->end);
				for ($i = $startarray['mday']; $i <= $endarray['mday']; $i++) {
					if ($todayarray['mon'] >= $startarray['mon'] && $todayarray['mon'] <= $endarray['mon']) {
						$arrayofavailabledays[dol_mktime(0, 0, 0, $todayarray['mon'], $i, $todayarray['year'])] = dol_mktime(0, 0, 0, $todayarray['mon'], $i, $todayarray['year']);
					}
				}
			}
		}

		for ($iter_week = 0; $iter_week < 6; $iter_week++) {
			echo " <tr>\n";
			// Get date of the current day, format 'yyyy-mm-dd'
			if ($tmpday <= 0) { // If number of the current day is in previous month
				$currdate0 = sprintf("%04d", $prev_year).sprintf("%02d", $prev_month).sprintf("%02d", $max_day_in_prev_month + $tmpday);
			} elseif ($tmpday <= $max_day_in_month) { // If number of the current day is in current month
				$currdate0 = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $tmpday);
			} else {// If number of the current day is in next month
				$currdate0 = sprintf("%04d", $next_year).sprintf("%02d", $next_month).sprintf("%02d", $tmpday - $max_day_in_month);
			}
			// Get week number for the targeted date '$currdate0'
			$numweek0 = idate("W", strtotime(date($currdate0)));
			// Show the week number, and define column width
			echo ' <td class="center weeknumber opacitymedium hideonsmartphone" style="min-width: 40px">'.$numweek0.'</td>';

			for ($iter_day = 0; $iter_day < 7; $iter_day++) {
				if ($tmpday <= 0) {
					/* Show days before the beginning of the current month (previous month)  */
					$style = 'cal_other_month cal_past';
					if ($iter_day == 6) {
						$style .= ' cal_other_month_right';
					}
					echo '  <td class="'.$style.' nowrap tdtop" width="14%">';
					show_bookcal_day_events($max_day_in_prev_month + $tmpday, $prev_month, $prev_year);
					echo "  </td>\n";
				} elseif ($tmpday <= $max_day_in_month) {
					/* Show days of the current month */
					$curtime = dol_mktime(0, 0, 0, $month, $tmpday, $year);
					$style = 'cal_current_month';
					if ($iter_day == 6) {
						$style .= ' cal_current_month_right';
					}
					$today = 0;
					if ($todayarray['mday'] == $tmpday && $todayarray['mon'] == $month && $todayarray['year'] == $year) {
						$today = 1;
					}
					//var_dump($curtime); var_dump($todaytms); var_dump($arrayofavailabledays);
					if ($curtime > $todaytms && in_array($curtime, $arrayofavailabledays)) {
						$style .= ' cal_available cursorpointer';
					}
					if ($curtime < $todaytms) {
						$style .= ' cal_past';
					}
					$dateint = sprintf("%04d", $year).'_'.sprintf("%02d", $month).'_'.sprintf("%02d", $tmpday);
					if (!empty(explode('dayevent_', $datechosen)[1]) && explode('dayevent_', $datechosen)[1] == $dateint) {
						$style .= ' cal_chosen';
						$isdatechosen = true;
					}
					echo '  <td class="'.$style.' nowrap tdtop" width="14%">';
					show_bookcal_day_events($tmpday, $month, $year, $today);
					echo "</td>\n";
				} else {
					/* Show days after the current month (next month) */
					$style = 'cal_other_month';
					if ($iter_day == 6) {
						$style .= ' cal_other_month_right';
					}
					echo '  <td class="'.$style.' nowrap tdtop" width="14%">';
					show_bookcal_day_events($tmpday - $max_day_in_month, $next_month, $next_year);
					echo "</td>\n";
				}
				$tmpday++;
			}
			echo " </tr>\n";
		}
		print '</table>';
		print '</td>';

		print '<td>'; // Column visible after selection of a day
		print '<div class="center bookingtab" style="height:50%">';
		print '<div style="height:100%">';
		print '<form id="formbooking" name="formbooking" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" id="datechosen" name="datechosen" value="">';
		print '<input type="hidden" id="datetimechosen" name="datetimechosen" value="">';
		print '<input type="hidden" id="durationbooking" name="durationbooking" value="">';

		print '<div id="bookinghoursection">';
		print '<br><br><br><br><br><br><div class="opacitymedium center">'.$langs->trans("SelectADay").'</div>';
		print '</div>';
		print '</form>';
		print '</div>';
		print '</div>';

		print '</td>';
	}
	print '</tr>';
	print '</table>';
	print '</div>';
	print '</div>';

	print '<script>';
	print '
	function generateBookingButtons(timearray, datestring){
		console.log("We generate all booking buttons of "+datestring);
		str = "";

		for (index in timearray){
			let hour = new Date("2000-01-01T" + index + ":00");
			duration = timearray[index];
			isalreadybooked = false;
			if (duration < 0) {
				duration *= -1;
				isalreadybooked = true;
			}
			hour.setMinutes(hour.getMinutes() + duration);

			let hours = hour.getHours().toString().padStart(2, "0"); // Formatter pour obtenir deux chiffres
			let mins = hour.getMinutes().toString().padStart(2, "0"); // Formatter pour obtenir deux chiffres

			timerange = index + " - " + `${hours}:${mins}`;
			str += \'<input class="button btnsubmitbooking \'+(isalreadybooked == true ? "btnbookcalbooked" : "")+\'" type="submit" name="timebooking" value="\'+timerange+\'" data-duration="\'+duration+\'"><br>\';
		}

		$("#bookinghoursection").html(str);
		$(".btnsubmitbooking").on("click", function(){
			duration = $(this).data("duration");
			$("#durationbooking").val(duration);
		})
	}';
	print '$(document).ready(function() {
		$(".cal_available").on("click", function(){
			console.log("We click on cal_available");
			$(".cal_chosen").removeClass("cal_chosen");
			$(this).addClass("cal_chosen");
			datestring = $(this).children("div").data("date");
			$.ajax({
				type: "POST",
				url: "'.DOL_URL_ROOT.'/public/bookcal/bookcalAjax.php",
				data: {
					action: "verifyavailability",
					id: '.$id.',
					datetocheck: $(this).children("div").data("datetime"),
					token: "'.currentToken().'",
				}
			}).done(function (data) {
				console.log("We show all booking");
				if (data["code"] == "SUCCESS") {
					/* TODO Replace this with a creating of allavailable hours button */
					console.log(data)
					timearray = data["availability"];
					console.log(timearray);
					generateBookingButtons(timearray, datestring);
					$(".btnbookcalbooked").prop("disabled", true);
				} else {
					if(data["code"] == "NO_DATA_FOUND"){
						console.log("No booking to hide");
					} else {
						console.log(data["message"]);
					}
				}
			});
			$(".bookingtab").removeClass("hidden");
			$("#bookingtabspandate").text($(this).children("div").data("date"));
			$("#datechosen").val($(this).children("div").attr("id"));
			$("#datetimechosen").val($(this).children("div").data("datetime"));
		});

		$("btnformbooking")

		'.($datechosen ? '$(".cal_chosen").trigger( "click" )' : '').'
	});';
	print '</script>';
}

llxFooter('', 'public');


/**
 * Show event of a particular day
 *
 * @param   int		$day             		Day
 * @param   int		$month					Month
 * @param   int		$year 					Year
 * @param   int		$today 					Today's day
 * @return	void
 */
function show_bookcal_day_events($day, $month, $year, $today = 0)
{
	global $conf;
	if ($conf->use_javascript_ajax) {	// Enable the "Show more button..."
		$conf->global->MAIN_JS_SWITCH_AGENDA = 1;
	}

	$dateint = sprintf("%04d", $year).'_'.sprintf("%02d", $month).'_'.sprintf("%02d", $day);
	$eventdatetime = dol_mktime(-1, -1, -1, $month, $day, $year);
	//print 'show_bookcal_day_events day='.$day.' month='.$month.' year='.$year.' dateint='.$dateint;

	print "\n";

	$curtime = dol_mktime(0, 0, 0, $month, $day, $year);
	// Line with title of day
	print '<div id="dayevent_'.$dateint.'" class="dayevent tagtable centpercent nobordernopadding" data-datetime="'.$eventdatetime.'" data-date="'.dol_print_date($eventdatetime, "daytext").'">'."\n";
	print dol_print_date($curtime, '%d');
	print '<br>';
	if ($today) {
		print img_picto('today', 'fontawesome_circle_fas_black_7px');
	} else {
		print '<br>';
	}
	print '</div>'; // table
	print "\n";
}
