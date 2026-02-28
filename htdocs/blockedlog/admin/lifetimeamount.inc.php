<?php
/* Copyright (C) 2026	Laurent Destailleur			<eldy@destailleur.fr>
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
 *    \file       htdocs/blockedlog/admin/lifetimeamount.inc.php
 *    \ingroup    blockedlog
 *    \brief      Code to calculate lifetime amount
 */

/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var CommonObject $object
 *
 * @var array<string,float> $totalamountlifetime
 * @var array<string,float> $totalhtamountlifetime
 * @var int $foundoldformat
 * @var int $firstrecorddate
 * @var int $error
 * @var ?int $search_end
 */
'@phan-var-force array<string,float> $totalamountlifetime';
'@phan-var-force array<string,float> $totalhtamountlifetime';

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page ".basename(__FILE__)." can't be called with no conf defined.";
	exit;
}

$firstrecorddatearray = array();
if (!empty($search_end) && $search_end > 0) {
	$dateend = $search_end;
} else {
	$dateend = dol_get_last_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') > 0 ? GETPOSTINT('monthtoexport') : 12);
}


// Calculate lifetime totals (with date of first record)
$sql = "SELECT action, module_source, object_format, MIN(date_creation) as datemin, SUM(amounts_taxexcl) as sumamounts_taxexcl, SUM(amounts) as sumamounts";
$sql .= " FROM ".MAIN_DB_PREFIX."blockedlog";
$sql .= " WHERE entity = ".((int) $conf->entity);
//$sql .= " AND action IN ('BILL_VALIDATE', 'BILL_SENTBYMAIL', 'PAYMENT_CUSTOMER_CREATE', 'CASHCONTROL_CLOSE', 'PAYMENT_CUSTOMER_DELETE', 'DOC_DOWNLOAD', 'DOC_PREVIEW')";
$sql .= " AND action IN ('BILL_VALIDATE', 'PAYMENT_CUSTOMER_CREATE', 'PAYMENT_CUSTOMER_DELETE')";	// Only event into lifetime total
$sql .= " AND date_creation < '".$db->idate($dateend)."'";
$sql .= " GROUP BY action, module_source, object_format";

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		// First record date per action code and module
		if (!empty($firstrecorddatearray[$obj->action][$obj->module_source])) {
			$firstrecorddatearray[$obj->action] = min($firstrecorddatearray[$obj->action][$obj->module_source], $db->jdate($obj->datemin, 'gmt'));
		} else {
			$firstrecorddatearray[$obj->action] = $db->jdate($obj->datemin, 'gmt');
		}
		// First record for all actions code
		if (!empty($firstrecorddate)) {
			$firstrecorddate = min($firstrecorddate, $db->jdate($obj->datemin, 'gmt'));
		} else {
			$firstrecorddate = $obj->datemin;
		}

		if (!isset($totalamountlifetime[$obj->action])) {
			$totalamountlifetime[$obj->action] = 0;
		}

		// Total per action code and module
		$totalamountlifetime[$obj->action] += $obj->sumamounts;

		// If format of line is old, the sumamounts_taxexcl was not recorded. So we flag this case.
		if (empty($obj->object_format) || $obj->object_format === 'V1') {
			$foundoldformat = 1;
		} else {
			$totalhtamountlifetime[$obj->action] += $obj->sumamounts_taxexcl;
		}
	}
} else {
	$error++;
	setEventMessages($db->lasterror, null, 'errors');
}
