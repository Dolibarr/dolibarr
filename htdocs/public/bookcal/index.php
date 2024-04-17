<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2023		anthony Berton			<anthony.berton@bb2a.fr>
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
 *		\brief      File to offer a way to make an online signature for a particular Dolibarr entity
 *					Example of URL: https://localhost/public/bookcal/booking.php?ref=PR...
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
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

$langs->loadLangs(array("main", "other", "dict", "agenda", "errors", "bookcal"));

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

$object = new Calendar($db);
$result = $object->fetch($id);

$availabilities = new Availabilities($db);

$year = GETPOST("year", "int") ?GETPOST("year", "int") : date("Y");
$month = GETPOST("month", "int") ?GETPOST("month", "int") : date("m");
$week = GETPOST("week", "int") ?GETPOST("week", "int") : date("W");
$day = GETPOST("day", "int") ?GETPOST("day", "int") : date("d");
$dateselect = dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'int'), GETPOST('dateselectday', 'int'), GETPOST('dateselectyear', 'int'), 'tzuserrel');
if ($dateselect > 0) {
	$day = GETPOST('dateselectday', 'int');
	$month = GETPOST('dateselectmonth', 'int');
	$year = GETPOST('dateselectyear', 'int');
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

$max_day_in_prev_month = date("t", dol_mktime(0, 0, 0, $prev_month, 1, $prev_year, 'gmt')); // Nb of days in previous month
$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year)); // Nb of days in next month
// tmpday is a negative or null cursor to know how many days before the 1st to show on month view (if tmpday=0, 1st is monday)
$tmpday = -date("w", dol_mktime(12, 0, 0, $month, 1, $year, 'gmt')) + 2; // date('w') is 0 fo sunday
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

$daychosen = GETPOST('daychosen', 'alpha');
$isdaychosen = false;

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
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
{
	global $user, $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

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
	if ($urllogo || getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC')) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		if ($urllogo) {
			print '<a href="'.(getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') ? getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') : dol_buildpath('/public/ticket/index.php?entity='.$conf->entity, 1)).'">';
			print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
			print '>';
			print '</a>';
		}
		if (getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC')) {
			print '<div class="clearboth"></div><strong>'.(getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') ? getDolGlobalString('BOOKCAL_PUBLIC_INTERFACE_TOPIC') : $langs->trans("BookCalSystem")).'</strong>';
		}
		print '</div>';
		if (!getDolGlobalInt('MAIN_HIDE_POWERED_BY')) {
			print '<div class="poweredbypublicpayment opacitymedium right hideonsmartphone"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	print '</div>';

	print '<div class="divmainbodylarge">';
}


/*
 * Actions
 */

 // None

/*
 * View
 */
$form = new Form($db);

llxHeaderVierge('BookingCalendar');


print '<div class="bookcalpublicarea centpercent" style="position:absolute;top:50%;transform: translateY(-50%)">';
print '<div class="bookcalform center" style="width:70%;padding:5px;max-height:360px">';
print '<h2>'.(!empty($object->label) ? $object->label : $object->ref).'</h2>';

$sql = "SELECT b.rowid, b.label, b.start, b.end, b.duration, b.startHour, b.endHour";
$sql .= " FROM ".MAIN_DB_PREFIX."bookcal_availabilities as b";
$sql .= " WHERE b.status = ".(int) $availabilities::STATUS_VALIDATED;
$sql .= " AND b.fk_bookcal_calendar = ".(int) $id;

$resql = $db->query($sql);

if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$i++;
		$obj = $db->fetch_object($resql);
		print '<a href="'.DOL_URL_ROOT.'/public/bookcal/booking.php?id='.$obj->rowid.'&fk_calendar='.$id.'"><button type="button">'.(!empty($obj->label) ? $obj->label : $obj->id).'</button>';
	}
}
print '</div>';
print '</div>';

print '<script>';

print '</script>';

llxFooter('', 'public');
