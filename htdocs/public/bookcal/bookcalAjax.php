<?php
/*
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
 *	\file       /htdocs/public/bookcal/bookcalAjax.php
 *	\brief      File to make Ajax action on Book cal
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
// If there is no need to load and show top and left menu
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$datetocheckbooking = GETPOSTINT('datetocheck');
$error = 0;

// Security check
/*if (!defined("NOLOGIN")) {	// No need of restrictedArea if not logged: Later the select will filter on public articles only if not logged.
	restrictedArea($user, 'knowledgemanagement', 0, 'knowledgemanagement_knowledgerecord', 'knowledgerecord');
}*/

$result = "{}";


/*
 * Actions
 */

top_httphead('application/json');


if ($action == 'verifyavailability') {
	$response = array();
	if (empty($id)) {
		$error++;
		$response["code"] = "MISSING_ID";
		$response["message"] = "Missing parameter id";
		header('HTTP/1.0 400 Bad Request');
		echo json_encode($response);
		exit;
	}
	if (empty($datetocheckbooking)) {
		$error++;
		$response["code"] = "MISSING_DATE_AVAILABILITY";
		$response["message"] = "Missing parameter datetocheck";
		header('HTTP/1.0 400 Bad Request');
		echo json_encode($response);
		exit;
	}

	// First get all ranges for the calendar
	if (!$error) {
		// Select in database all availabilities
		$availabilitytab = array();
		$sql = "SELECT ba.rowid as id, ba.duration, ba.startHour, ba.endHour, ba.start, ba.end";
		$sql .= " FROM ".MAIN_DB_PREFIX."bookcal_availabilities as ba";
		$sql .= " WHERE ba.fk_bookcal_calendar = ".((int) $id);
		$sql .= " AND ba.status = 1";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$starttime = $db->jdate($obj->start);
				$endtime = $db->jdate($obj->end);
				$offsetmin = $obj->duration % 60;
				if ($offsetmin == 0) {
					$offsetmin = 60;
				}
				$startHourstring = $obj->startHour;
				$endHourstring = $obj->endHour;
				if ($startHourstring <= 0) {
					$startHourstring = 0;
				}
				if ($endHourstring >= 24) {
					$endHourstring = 24;
				}
				$offsethour = round($obj->duration / 60);
				// Creation of array of availabilties range
				if ($datetocheckbooking >= $starttime && $datetocheckbooking <= $endtime) {
					for ($hour=$startHourstring; $hour < $endHourstring; $hour+= $offsethour) {
						for ($min=0; $min < 60; $min += $offsetmin) {
							$hourstring = $hour;
							$minstring = $min;
							if ($hour < 10) {
								$hourstring = "0".$hourstring;
							}
							if ($min < 10) {
								$minstring = "0".$minstring;
							}
							$response["availability"][$hourstring.":".$minstring] = intval($obj->duration);
						}
					}
				}
				$i++;
			}
			if ($i == $num) {
				$response["code"] = "SUCCESS";
			} else {
				$response["code"] = "ERROR";
				$error ++;
			}
		}

		// Select also all not available ranges
		if (!$error) {
			$datetocheckbooking_end = dol_time_plus_duree($datetocheckbooking, 1, 'd');

			$sql = "SELECT b.datep, b.id";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as b";
			$sql .= " WHERE b.datep >= '".$db->idate($datetocheckbooking)."'";
			$sql .= " AND b.datep < '".$db->idate($datetocheckbooking_end)."'";
			$sql .= " AND b.code = 'AC_RDV'";
			$sql .= " AND b.status = 0";
			$sql .= " AND b.fk_bookcal_calendar = ".((int) $id);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					$datebooking = $db->jdate($obj->datep);
					$datebookingarray = dol_getdate($datebooking);
					$hourstring = $datebookingarray["hours"];
					$minstring = $datebookingarray["minutes"];
					if ($hourstring < 10) {
						$hourstring = "0".$hourstring;
					}
					if ($minstring < 10) {
						$minstring = "0".$minstring;
					}
					$response["availability"][$hourstring.":".$minstring] *= -1;
					$i++;
				}
			}
		}
	}
	$result = $response;
}


/*
 * View
 */

echo json_encode($result);
