<?php
/* Copyright (C) 2017		ATM Consulting				<contact@atm-consulting.fr>
 * Copyright (C) 2017-2018	Laurent Destailleur			<eldy@destailleur.fr>
 * Copyright (C) 2018-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *    \file       htdocs/blockedlog/admin/blockedlog_archives.php
 *    \ingroup    blockedlog
 *    \brief      Page to view/export and check unalterable logs
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_db_name
 */
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'banks', 'bills', 'blockedlog', 'other'));

// Get Parameters
$action      = GETPOST('action', 'aZ09');
$confirm     = GETPOST('confirm', 'aZ09');	// Used by the actions_linkedfiles.inc.php
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__); // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss   = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

//$hmacexportkey = GETPOST('hmacexportkey', 'password');
$withtab    = GETPOSTINT('withtab');

$search_showonlyerrors = GETPOSTINT('search_showonlyerrors');
if ($search_showonlyerrors < 0) {
	$search_showonlyerrors = 0;
}

$search_startyear = GETPOSTINT('search_startyear');
$search_startmonth = GETPOSTINT('search_startmonth');
$search_startday = GETPOSTINT('search_startday');
$search_endyear = GETPOSTINT('search_endyear');
$search_endmonth = GETPOSTINT('search_endmonth');
$search_endday = GETPOSTINT('search_endday');
$search_id = GETPOST('search_id', 'alpha');
$search_fk_user = GETPOST('search_fk_user', 'intcomma');
$search_start = -1;
if (GETPOST('search_startyear') != '') {
	$search_start = dol_mktime(0, 0, 0, $search_startmonth, $search_startday, $search_startyear);
}
$search_end = -1;
if (GETPOST('search_endyear') != '') {
	$search_end = dol_mktime(23, 59, 59, $search_endmonth, $search_endday, $search_endyear);
}
$search_code = GETPOST('search_code', 'array:alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_signature = GETPOST('search_signature', 'alpha');

if (($search_start == -1 || empty($search_start)) && !GETPOSTISSET('search_startmonth') && !GETPOSTISSET('begin')) {
	$search_start = dol_time_plus_duree(dol_now(), -1, 'w');
	$tmparray = dol_getdate($search_start);
	$search_startday = $tmparray['mday'];
	$search_startmonth = $tmparray['mon'];
	$search_startyear = $tmparray['year'];
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortfield)) {
	$sortfield = 'rowid';
}
if (empty($sortorder)) {
	$sortorder = 'DESC';
}

$block_static = new BlockedLog($db);
$block_static->loadTrackedEvents();

// Access Control
if (((!$user->admin && !$user->hasRight('blockedlog', 'read')) || !isModEnabled('blockedlog')) && !userIsTaxAuditor()) {
	accessforbidden();
}

// We force also permission to write because it does not exists and we need it to upload a file
$user->rights->blockedlog->create = 1;

$result = restrictedArea($user, 'blockedlog', 0, '');

// Execution Time
$max_execution_time_for_importexport = getDolGlobalInt('EXPORT_MAX_EXECUTION_TIME', 300); // 5mn if not defined
$max_time = @ini_get("max_execution_time");
if ($max_time && $max_time < $max_execution_time_for_importexport) {
	dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
	@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
}

$MAXLINES = getDolGlobalInt('BLOCKEDLOG_MAX_LINES', 10000);

$permission = $user->hasRight('blockedlog', 'read');
$permissiontoadd = $user->hasRight('blockedlog', 'read');	// Permission is to upload new files to scan them
$permtoedit = $permissiontoadd;

$upload_dir = getMultidirOutput($block_static, 'blockedlog').'/archives';

dol_mkdir($upload_dir);

$fh = null;


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_id = '';
	$search_fk_user = '';
	$search_start = -1;
	$search_end = -1;
	$search_code = array();
	$search_ref = '';
	$search_amount = '';
	$search_signature = '';
	$search_showonlyerrors = 0;
	$search_startyear = '';
	$search_startmonth = '';
	$search_startday = '';
	$search_endyear = '';
	$search_endmonth = '';
	$search_endday = '';
	$toselect = array();
	$search_array_options = array();
}

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

if ($action == 'export' && $user->hasRight('blockedlog', 'read')) {		// read is read/export for blockedlog
	$error = 0;

	$previoushash = '';
	$firstid = '';
	$periodnotcomplete = 0;

	if (! (GETPOSTINT('yeartoexport') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Year")), null, "errors");
		$action = '';
		$error++;
	}
	/*
	if (empty($hmacexportkey)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Password")), null, "errors");
		$error++;
	}
	*/

	$dates = dol_get_first_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') > 0 ? GETPOSTINT('monthtoexport') : 1);
	$datee = dol_get_last_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') > 0 ? GETPOSTINT('monthtoexport') : 12);

	if ($datee >= dol_now()) {
		$periodnotcomplete = 1;
	}

	$suffixperiod = ($periodnotcomplete ? 'INCOMPLETE' : 'DONOTMODIFY');

	if (!$error) {
		// Get the ID of the first line qualified
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		// For unalterable log, we are using the date of creation of the log. Note that a bookkeeper may decide to dispatch an invoice
		// on different periods for example to manage depreciation.
		$sql .= " AND date_creation BETWEEN '".$db->idate($dates, 'gmt')."' AND '".$db->idate($datee, 'gmt')."'";
		$sql .= " ORDER BY date_creation ASC, rowid ASC"; // Required so we get the first one
		$sql .= $db->plimit(1);

		$res = $db->query($sql);
		if ($res) {
			// Make the first fetch to get first line and then get the previous hash.
			$obj = $db->fetch_object($res);
			if ($obj) {
				$firstid = $obj->rowid;
				$tmparray = $block_static->getPreviousHash(0, $firstid);
				$previoushash = $tmparray['previoushash'];
			} else {	// If not data found for filter, we do not need previoushash neither firstid
				$firstid = '';
				$previoushash = 'nodata';
			}
		} else {
			$error++;
			setEventMessages($db->lasterror, null, 'errors');
		}
	}

	// Define file name
	$registrationnumber = getHashUniqueIdOfRegistration();
	$secretkey = $registrationnumber;

	$yearmonthtoexport = GETPOSTINT('yeartoexport').'-'.(GETPOSTINT('monthtoexport') > 0 ? sprintf("%02d", GETPOSTINT('monthtoexport')) : '');
	$yearmonthdateofexport = dol_print_date(dol_now(), 'dayhourrfc', 'gmt');
	$yearmonthdateofexportstandard = dol_print_date(dol_now(), 'dayhourlog', 'gmt');

	$nameofdownoadedfile = "unalterable-log-archive-".$dolibarr_main_db_name."-".str_replace('-', '', $yearmonthtoexport).'-'.$yearmonthdateofexportstandard.'UTC-'.$suffixperiod.'.csv';

	//$tmpfile = $conf->admin->dir_temp.'/unalterable-log-archive-tmp-'.$user->id.'.csv';
	$tmpfileshort = 'blockedlog/archives/'.$nameofdownoadedfile;
	$tmpfile = getMultidirOutput($block_static, 'blockedlog').'/archives/'.$nameofdownoadedfile;

	$formatexport = 'VE2';

	if (!$error) {
		$fh = fopen($tmpfile, 'w');
		if (empty($fh)) {
			$error++;
			setEventMessages('Failed to open file '.$tmpfileshort.' for writing.', null, 'errors');
		}
	}

	if (!$error && $fh) {
		$refinvoicefound = array();
		$totalhtamount = array();
		$totalvatamount = array();
		$totalamount = array();

		// Now restart request with all data, so without the limit(1) in sql request
		$sql = "SELECT rowid, entity, date_creation, tms, user_fullname, action, module_source, pos_source, amounts_taxexcl, amounts, element, fk_object, date_object, ref_object,";
		$sql .= " linktoref, linktype, signature, fk_user, object_data, object_version, object_format, debuginfo";
		$sql .= " FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		// For unalterable log, we are using the date of creation of the log. Note that a bookkeeper may decide to dispatch an invoice
		// or payment on different periods for example to manage depreciation, but we want here is not accountancy but payment data.
		$sql .= " AND date_creation BETWEEN '".$db->idate($dates, 'gmt')."' AND '".$db->idate($datee, 'gmt')."'";
		$sql .= " ORDER BY date_creation ASC, rowid ASC"; // Required so later we can use the parameter $previoushash of checkSignature()

		$resql = $db->query($sql);
		if ($resql) {
			// Print line with title
			fwrite($fh, "BEGIN - regnumber=".dol_trunc($registrationnumber, 10)." - date=".$yearmonthdateofexport." - period=".$yearmonthtoexport.($periodnotcomplete ? '-'.$suffixperiod : '')." - entity=".((int) $conf->entity)." - formatexport=".$formatexport." - user=".$user->getFullName($langs)
				.';'.$langs->transnoentities('Id')
				.';'.$langs->transnoentities('DateCreation')
				.';'.$langs->transnoentities('Action')
				.';'.$langs->transnoentities('Origin')
				.';'.$langs->transnoentities('Terminal')
				.';'.$langs->transnoentities('AmountHT')
				.';'.$langs->transnoentities('AmountTTC')
				.';'.$langs->transnoentities('Ref')
				.';'.$langs->transnoentities('Date')
				.';'.$langs->transnoentities('User')
				.';'.$langs->transnoentities('LinkTo')
				.';'.$langs->transnoentities('LinkType')
				.';'.$langs->transnoentities('FullData')
				.';'.$langs->transnoentities('Version')				// Version Dolibarr, example 22.0.0
				.';'.$langs->transnoentities('VersionSignature')	// Rule used for fingerprint calculation
				.';'.$langs->transnoentities('FingerprintDatabase')			// Signature
				.';'.$langs->transnoentities('Status')
				//.';'.$langs->transnoentities('FingerprintExport')
				);

			$loweridinerror = 0;
			$i = 0;

			while ($obj = $db->fetch_object($resql)) {
				// We set here all data used into signature calculation (see checkSignature method) and more

				// IMPORTANT: We must have here, the same rule for transformation of data than into
				// the blockedlog->fetch() method (db->jdate for date, ...)

				$block_static->id = $obj->rowid;
				$block_static->entity = $obj->entity;

				if ($i == 0) {
					$tmparray = $block_static->getPreviousHash(0, $block_static->id);
					$previoushash = $tmparray['previoushash'];

					fwrite($fh, ";NOTE - previoushash=".$previoushash."\n");
				}

				$tz = 'gmt';
				if (empty($obj->object_format) || $obj->object_format == 'V1') {
					$tz = 'tzserver';
				}

				$block_static->date_creation = $db->jdate($obj->date_creation, $tz);		// jdate(date_creation) is UTC
				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$block_static->date_modification = $db->jdate($obj->tms, $tz);			// jdate(tms) is UTC

				$block_static->action = $obj->action;
				$block_static->module_source = $obj->module_source;
				$block_static->pos_source = $obj->pos_source;

				$block_static->amounts_taxexcl = is_null($obj->amounts_taxexcl) ? null : (float) $obj->amounts_taxexcl;	// Database store value with 8 digits, we cut ending 0 them with (flow)
				$block_static->amounts = (float) $obj->amounts;															// Database store value with 8 digits, we cut ending 0 them with (flow)

				$block_static->fk_object = $obj->fk_object;							// Not in signature
				$block_static->date_object = $db->jdate($obj->date_object, $tz);	// jdate(date_object) is UTC
				$block_static->ref_object = $obj->ref_object;

				$block_static->linktoref = $obj->linktoref;
				$block_static->linktype = $obj->linktype;

				$block_static->fk_user = $obj->fk_user;								// Not in signature
				$block_static->user_fullname = $obj->user_fullname;

				$block_static->object_data = $block_static->dolDecodeBlockedData($obj->object_data);

				// Old hash + Previous fields concatenated = signature
				$block_static->signature = $obj->signature;

				$block_static->element = $obj->element;								// Not in signature

				$block_static->object_version = $obj->object_version;				// Not in signature
				$block_static->object_format = $obj->object_format;					// Not in signature

				$block_static->certified = ($obj->certified == 1);					// Not in signature

				//$block_static->debuginfo = $obj->debuginfo;

				//var_dump($block->id.' '.$block->signature, $block->object_data);

				$checksignature = $block_static->checkSignature($previoushash); 	// If $previoushash is not defined, checkSignature will search it from $block_static->id

				// To see detail to get signature
				/*
				if ($block_static->id == 411) {
					$concatenateddata = $block_static->buildKeyForSignature($block_static->object_format);

					$tmparray = $block_static->checkSignature($previoushash, 2);
					var_dump($previoushash, $concatenateddata, $tmparray);
					exit;
				}
				*/

				if ($checksignature) {
					$statusofrecord = 'Valid';
					if ($loweridinerror > 0) {
						$statusofrecordnote = 'ValidButFoundAPreviousKO';
					} else {
						$statusofrecordnote = '';
					}
				} else {
					$statusofrecord = 'KO';
					$statusofrecordnote = 'LineCorruptedOrNotMatchingPreviousOne';
					$loweridinerror = $obj->rowid;
				}

				if ($i == 0) {
					$statusofrecordnote = $langs->trans("PreviousFingerprint").': '.$previoushash.($statusofrecordnote ? ' - '.$statusofrecordnote : '');
				}

				//$concatenateddata = $block_static->buildKeyForSignature();

				// Define $totalhtamount, $totalvatamount, $totalamount for $block->action event / $block->module_source
				$total_ht = $total_vat = $total_ttc = 0;
				sumAmountsForUnalterableEvent($block_static, $refinvoicefound, $totalhtamount, $totalvatamount, $totalamount, $total_ht, $total_vat, $total_ttc);

				fwrite($fh, ";"
					.csvClean($block_static->id).';'
					.csvClean(dol_print_date($block_static->date_creation, 'standard', 'gmt')).';'
					.csvClean($block_static->action).';'
					.csvClean($block_static->module_source).';'
					.csvClean($block_static->pos_source).';'
					.csvClean($block_static->amounts_taxexcl).';'	// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
					.csvClean($block_static->amounts).';'			// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
					.csvClean($block_static->ref_object).';'
					.csvClean(dol_print_date($block_static->date_object, 'standard', 'gmt')).';'
					.csvClean($block_static->user_fullname).';'
					.csvClean($block_static->linktoref).';'
					.csvClean($block_static->linktype).';'
					.csvClean($obj->object_data).';'				// We must use the string (so $obj->object_data) and not the array decoded with dolDecodeBlockedData
					.csvClean($block_static->object_version).';'
					.csvClean($block_static->object_format).';'
					.csvClean($block_static->signature).';'
					.csvClean($statusofrecord).';'
					."\n");

				// Set new previous hash for next fetch
				$previoushash = $obj->signature;

				$i++;
			}

			if ($i == 0) {
				fwrite($fh, ";\n");
			}
		} else {
			$error++;
			setEventMessages($db->lasterror, null, 'errors');
		}

		// Now calculate cumulative total of all invoices validated
		$totalhtamountalllines = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER' => 0);
		$totalvatamountalllines = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER' => 0);
		$totalamountalllines = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER' => 0);
		if (array_key_exists('BILL_VALIDATE', $totalhtamount)) {
			foreach ($totalhtamount['BILL_VALIDATE'] as $val) {	// Loop on each module
				$totalhtamountalllines['BILL_VALIDATE'] += $val;
			}
			foreach ($totalvatamount['BILL_VALIDATE'] as $val) {
				$totalvatamountalllines['BILL_VALIDATE'] += $val;
			}
			foreach ($totalamount['BILL_VALIDATE'] as $val) {
				$totalamountalllines['BILL_VALIDATE'] += $val;
			}
		}
		if (array_key_exists('PAYMENT_CUSTOMER', $totalhtamount)) {
			foreach ($totalhtamount['PAYMENT_CUSTOMER'] as $val) {
				$totalhtamountalllines['PAYMENT_CUSTOMER'] += $val;
			}
			foreach ($totalvatamount['PAYMENT_CUSTOMER'] as $val) {
				$totalvatamountalllines['PAYMENT_CUSTOMER'] += $val;
			}
			foreach ($totalamount['PAYMENT_CUSTOMER'] as $val) {
				$totalamountalllines['PAYMENT_CUSTOMER'] += $val;
			}
		}

		// Add a final line with cumulative total of invoices validated (BILL_VALIDATE)
		$block_static->id = 0;
		$block_static->date_creation = '';
		$block_static->action = '';
		$block_static->module_source = '*';
		$block_static->pos_source = '*';
		$block_static->amounts_taxexcl = '';
		$block_static->amounts = '';
		$block_static->ref_object = '';
		$block_static->date_object = 0;
		$block_static->user_fullname = '';
		$block_static->linktoref = '';
		$block_static->linktype = '';
		$block_static->object_version = '';
		$block_static->object_format = '';
		$block_static->signature = '';

		$statusofrecord = '';

		fwrite($fh, 'SUMMARY TURNOVER BILLED - '.$langs->transnoentitiesnoconv("Bills").' : '.$totalhtamountalllines['BILL_VALIDATE'].' '.$langs->trans("HT").' - '.$totalvatamountalllines['BILL_VALIDATE'].' '.$langs->trans("VAT").' - '.$totalamountalllines['BILL_VALIDATE'].' '.$langs->trans("HT").';'
			.csvClean('').';'
			.csvClean($block_static->date_creation).';'
			.csvClean($block_static->action).';'
			.csvClean($block_static->module_source).';'
			.csvClean($block_static->pos_source).';'
			.csvClean($block_static->amounts_taxexcl).';'	// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
			.csvClean($block_static->amounts).';'			// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
			.csvClean($block_static->ref_object).';'
			.csvClean('').';'
			.csvClean($block_static->user_fullname).';'
			.csvClean($block_static->linktoref).';'
			.csvClean($block_static->linktype).';'
			.csvClean('').';'				// We must use the string (so $obj->object_data) and not the array decoded with dolDecodeBlockedData
			.csvClean($block_static->object_version).';'
			.csvClean($block_static->object_format).';'
			.csvClean($block_static->signature).';'
			.csvClean($statusofrecord).';'."\n");


		// Add a final line with cumulative total of invoices validated (PAYMENT_CUSTOMER_CREATE)
		$block_static->id = 0;
		$block_static->date_creation = '';
		$block_static->action = '';
		$block_static->module_source = '*';
		$block_static->pos_source = '*';
		$block_static->amounts_taxexcl = '';
		$block_static->amounts = '';
		$block_static->ref_object = '';
		$block_static->date_object = 0;
		$block_static->user_fullname = '';
		$block_static->linktoref = '';
		$block_static->linktype = '';
		$block_static->object_version = '';
		$block_static->object_format = '';
		$block_static->signature = '';
		$statusofrecord = '';

		fwrite($fh, 'SUMMARY TURNOVER PAID - '.$langs->transnoentitiesnoconv("Payments").' : '.$totalamountalllines['PAYMENT_CUSTOMER'].';'
			.csvClean('').';'
			.csvClean($block_static->date_creation).';'
			.csvClean($block_static->action).';'
			.csvClean($block_static->module_source).';'
			.csvClean($block_static->pos_source).';'
			.csvClean($block_static->amounts_taxexcl).';'	// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
			.csvClean($block_static->amounts).';'			// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
			.csvClean($block_static->ref_object).';'
			.csvClean('').';'
			.csvClean($block_static->user_fullname).';'
			.csvClean($block_static->linktoref).';'
			.csvClean($block_static->linktype).';'
			.csvClean('').';'				// We must use the string (so $obj->object_data) and not the array decoded with dolDecodeBlockedData
			.csvClean($block_static->object_version).';'
			.csvClean($block_static->object_format).';'
			.csvClean($block_static->signature).';'
			.csvClean($statusofrecord).';'."\n");


		// Get lifetime amount of all invoices validated and payments created/deleted.
		// We do not use $totalamountalllines because it is only for the period, but we want lifetime amount since the first record to now.
		$totalamountlifetime = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER_CREATE' => 0, 'PAYMENT_CUSTOMER_DELETE' => 0);
		$totalhtamountlifetime = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER_CREATE' => 0, 'PAYMENT_CUSTOMER_DELETE' => 0);
		$foundoldformat = 0;
		$firstrecorddate = 0;
		include_once DOL_DOCUMENT_ROOT.'/blockedlog/admin/lifetimeamount.inc.php';


		// Add a final line with perpetual total for invoice validations
		fwrite($fh, 'SUMMARY LIFETIME BILLED - '.$langs->transnoentitiesnoconv("Invoices").' : '.$totalhtamountlifetime['BILL_VALIDATE'].' '.$langs->trans("HT")." - ".($foundoldformat ? '' : ($totalamountlifetime['BILL_VALIDATE'] - $totalhtamountlifetime['BILL_VALIDATE']).' '.$langs->transnoentitiesnoconv("VAT")).' - '.$totalamountlifetime['BILL_VALIDATE'].' '.$langs->trans("TTC").";"
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('>= '.dol_print_date($firstrecorddate, 'standard')).";\n");

		// Add a final line with perpetual total for customer payments
		fwrite($fh, 'SUMMARY LIFETIME PAID - '.$langs->transnoentitiesnoconv("Payments").' : '.($totalamountlifetime['PAYMENT_CUSTOMER_CREATE'] + $totalamountlifetime['PAYMENT_CUSTOMER_DELETE']).";"
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('').';'
			.csvClean('>= '.dol_print_date($firstrecorddate, 'standard')).";\n");

		// End of file, we will calculate global signature on it now, before adding last line.
		fclose($fh);

		// Calculate the signature of the file (the last line has a return line)
		$algo = 'sha256';
		$sha256 = hash_file($algo, $tmpfile);						// For integrity only
		$hmacsha256 = hash_hmac_file($algo, $tmpfile, $secretkey);	// For integrity + authenticity

		// Now add a signature to check integrity at end of file
		file_put_contents($tmpfile, 'END - sha256='.$sha256.' - hmac_sha256='.$hmacsha256, FILE_APPEND);
		dolChmod($tmpfile);


		if (!$error) {
			if ($periodnotcomplete) {
				setEventMessages($langs->trans("ErrorPeriodMustBePastToAllowExport"), null, "warnings");
			} else {
				// We record the export as a new line into the unalterable logs
				require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
				$b = new BlockedLog($db);

				$object = new stdClass();
				$object->id = 0;
				$object->element = 'module';
				$object->ref = 'systemevent';
				$object->entity = $conf->entity;
				$object->date = dol_now();
				$object->fullname = $user->getFullName($langs);

				$object->label = 'Export unalterable logs';

				$object->total_billed = $totalhtamountalllines['BILL_VALIDATE'].' '.$langs->trans("HT").' - '.$totalvatamountalllines['BILL_VALIDATE'].' '.$langs->trans("VAT").' - '.$totalamountalllines['BILL_VALIDATE'].' '.$langs->trans("HT");
				$object->total_collected = $totalamountalllines['PAYMENT_CUSTOMER'];
				$object->totallifetime_billed = $totalhtamountlifetime['BILL_VALIDATE'].' '.$langs->trans("HT")." - ".($foundoldformat ? '' : ($totalamountlifetime['BILL_VALIDATE'] - $totalhtamountlifetime['BILL_VALIDATE']).' '.$langs->transnoentitiesnoconv("VAT")).' - '.$totalamountlifetime['BILL_VALIDATE'].' '.$langs->trans("HT");
				$object->totallifetime_collected = ($totalamountlifetime['PAYMENT_CUSTOMER_CREATE'] + $totalamountlifetime['PAYMENT_CUSTOMER_DELETE']);

				$object->period = 'year='.GETPOSTINT('yeartoexport').(GETPOSTINT('monthtoexport') ? ' month='.GETPOSTINT('monthtoexport') : '');

				$action = 'BLOCKEDLOG_EXPORT';

				$result = $b->setObjectData($object, $action, 0, $user, 0);

				if ($result < 0) {
					setEventMessages('Failed to insert the export into the unalterable log. Export canceled: '.$b->error, null, 'errors');
					dol_delete_file($tmpfile);
					$error++;
				}

				$res = $b->create($user);

				if ($res < 0) {
					setEventMessages('Failed to insert the export into the unalterable log. Export canceled: '.$b->error, null, 'errors');
					dol_delete_file($tmpfile);
					$error++;
				}
			}
		}
	}

	if (!$error && $fh) {
		setEventMessages($langs->trans("FileGenerated"), null);
	}

	$action = '';
}


/*
 *	View
 */

$form = new Form($db);
$formother = new FormOther($db);

if ($withtab) {
	$title = $langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog');
} else {
	$title = $langs->trans("BrowseBlockedLog");
}
$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-blockedlog page-admin_blockedlog_list');

$blocks = $block_static->getLog('all', (int) $search_id, $MAXLINES, $sortfield, $sortorder, (int) $search_fk_user, $search_start, $search_end, $search_ref, $search_amount, $search_code, $search_signature);
if (!is_array($blocks)) {
	if ($blocks == -2) {
		setEventMessages($langs->trans("TooManyRecordToScanRestrictFilters", $MAXLINES), null, 'errors');
	} else {
		dol_print_error($block_static->db, $block_static->error, $block_static->errors);
		exit;
	}
}

$linkback = '';
if ($withtab) {
	$linkback = '<a href="'.dolBuildUrl($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
}

$morehtmlcenter = '';
$texttop = '';

$registrationnumber = getHashUniqueIdOfRegistration();
if (!userIsTaxAuditor()) {
	$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';
	if (!isRegistrationDataSavedAndPushed()) {
		$texttop = '';
	}
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));

print dol_get_fiche_head($head, 'archives', '', -1);

//print $texttop;
//print '<br><br>';

print '<div class="opacitymedium hideonsmartphone justify">';
if (!userIsTaxAuditor()) {
	print $langs->trans("ArchivesDesc")."<br>";
} else {
	print $langs->trans("ArchivesAuditorDesc")."<br>";
}
print "</div>\n";


if ($action == 'check' || $action == 'checkconfirmed') {
	print '<br>';
	print '<div class="formconsumeproduce">';

	print '<b>'.$langs->trans("File").'</b> : '.GETPOST('urlfile').'<br>';

	$fullpath = $upload_dir.'/'.GETPOST('urlfile');

	$handle = fopen($fullpath, "r");
	$line = fgets($handle);
	fclose($handle);

	$reg = array();
	$period = '';
	$regnumber = '';
	$formatexport = '';
	$fileentity = 1;
	if (preg_match('/\speriod=([^\s]+)/', $line, $reg)) {
		$period = $reg[1];	// Get period on first line
	}
	if (preg_match('/\sregnumber=([^\s]+)/', $line, $reg)) {
		$regnumber = str_replace(array('.', '…'), '', $reg[1]);	// Get period on first line
	}
	if (preg_match('/\sformatexport=([^\s]+)/', $line, $reg)) {
		$formatexport = $reg[1];	// Get export format (VE1, VE2...)
	}
	if (preg_match('/\sentity=([^\s]+)/', $line, $reg)) {
		$fileentity = $reg[1];		// Get entity of file (usually 1)
	}
	print '<b>'.$langs->trans("Period").'</b> : '.$period.'<br>';

	print '<br>';


	$registrationnumber = getHashUniqueIdOfRegistration();
	$secretkey = $registrationnumber;
	$inputregistrationnumber = '';

	// Prepare to create a temporary file
	$fullpathtmp = $upload_dir.'/temp/'.GETPOST('urlfile').'.tmp';

	dol_mkdir($upload_dir.'/temp');
	$result = dol_copy($fullpath, $fullpathtmp);

	// Remove the last line of text file
	removeLastLine($fullpathtmp);

	print $langs->trans("FileHasBeenEncodedWithASecretKeyStartingWith").' : '.$regnumber.'...<br>';

	if (preg_match('/^'.$regnumber.'/', $secretkey) && !userIsTaxAuditor()) {
		print 'As this matches the 10 first characters of the full registration number of this instance, we will use the full registration number to control the archive file...';
	} else {
		if (!userIsTaxAuditor() || !isModEnabled('captureserver')) {   // @phan-suppress-current-line UnknownModuleName
			print 'This archive file was not generated by this instance.';
			print 'The control of authenticity is possible only if you know the full registration number.';
			$inputregistrationnumber = '';

			print $langs->trans("PleaseEnterFullRegistrationNumber").' ';
		} else {
			// Here module "captureserver" is on. We can search the full registration number and prefill value
			$sql = "SELECT registerid from ".MAIN_DB_PREFIX."captureserver_captureserver";
			$sql .= " WHERE registerid LIKE '".$db->escape($regnumber)."%'";
			$sql .= " AND type = 'dolibarrregistration'";
			$sql .= " LIMIT 1";

			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$inputregistrationnumber = $obj->registerid;
				}
			}

			print $langs->trans("WeFoundThisFullRegistrationNumberForThisKey").' ';
		}
		print '<input type="text" name="inputregistrationnumber" class="width300" placeholder="'.$langs->trans("FullRegistrationNumber").'"value="'.$inputregistrationnumber.'">';
	}
	print '<br><br>';
	print '<center><a class="button small nomarginleft" href="'.$_SERVER["PHP_SELF"].'?action=checkconfirmed&urlfile='.urlencode(GETPOST('urlfile')).'">'.$langs->trans("ControlFile").'</a></center>';


	//<input type="text" name="inputregistrationnumber" placeholder="'.$langs->trans("RegistrationNumber").'">';

	print '</div>';

	if ($action == 'checkconfirmed') {
		$totalhtamountforaction = $totalvatamountforaction = $totalamountforaction = array(
			'BILL_VALIDATE' => 0,
			'PAYMENT_CUSTOMER' => 0		// PAYMENT_CUSTOMER_CREATE + PAYMENT_CUSTOMER_DELETE
		);
		$reg = array();
		$refinvoicefound = array();
		$recalculatedhashsign = '';
		$recalculatedhashauth = '';
		$hashsign = '';
		$hashauth = '';
		$algosign = '';
		$algoauth = '';
		$previoushash = '';
		$nbLinesModifiedInExportButKo = 0;
		$nbLinesModifiedBeforeExport = 0;

		$amounthtlifetime = array();
		$amountvatlifetime = array();
		$amountttclifetime = array();
		$amounthtlifetime['BILL_VALIDATE'] = $amounthtlifetime['PAYMENT_CUSTOMER'] = null;
		$amountvatlifetime['BILL_VALIDATE'] = $amountvatlifetime['PAYMENT_CUSTOMER'] = null;
		$amountttclifetime['BILL_VALIDATE'] = $amountttclifetime['PAYMENT_CUSTOMER'] = null;

		$handle = fopen($fullpath, "r");
		if ($handle) {
			$numline = 0;

			$block_static = new BlockedLog($db);

			while ($line = fgetcsv($handle, 100000, ';', '"', '')) {
				$numline++;
				$lineanalyzed = 0;

				$linetech = $lineactioncode = '';
				$lineamountht = $lineamountttc = 0;
				$lineref = '';
				$statusline = '';

				if ($numline < 2) {
					// First line, we continue
					continue;
				}

				$block_static->id = 0;	// reset tmp record

				if ($formatexport == 'VE1' && !empty($line[1])) {	// Format V23
					$lineanalyzed = 1;
					$linetech = $line[0];

					$block_static->id = (int) $line[1];
					$block_static->entity = (int) $fileentity;
					$block_static->date_creation = (string) $line[2];
					$block_static->action = $lineactioncode = (string) $line[3];
					$block_static->module_source = (string) $line[4];
					$block_static->pos_source = '';
					$block_static->amounts_taxexcl = $lineamountht = ($line[5] === '' ? null : (float) $line[5]);
					$block_static->amounts = $lineamountttc = (float) $line[6];
					$block_static->ref_object = $lineref = (string) $line[7];
					$block_static->date_object = (int) $line[8];
					$block_static->user_fullname = (string) $line[9];
					$block_static->linktoref = (string) $line[10];
					$block_static->linktype = (string) $line[11];
					$block_static->object_data = json_decode((string) $line[12]);
					$block_static->object_version = (string) $line[13];
					$block_static->object_format = (string) $line[14];
					$block_static->signature = (string) $line[15];

					// Status from file: 'Valid' or 'KO'
					$statusline = (string) $line[16];
				}

				if ($formatexport == 'VE2' && !empty($line[1])) {	// Format V24+
					$lineanalyzed = 1;
					$linetech = $line[0];

					$block_static->id = (int) $line[1];
					$block_static->entity = (int) $fileentity;
					$block_static->date_creation = (string) $line[2];
					$block_static->action = $lineactioncode = (string) $line[3];
					$block_static->module_source = (string) $line[4];
					$block_static->pos_source = (string) $line[5];
					$block_static->amounts_taxexcl = $lineamountht = ($line[6] === '' ? null : (float) $line[6]);
					$block_static->amounts = $lineamountttc = (float) $line[7];
					$block_static->ref_object = $lineref = (string) $line[8];
					$block_static->date_object = (int) $line[9];
					$block_static->user_fullname = (string) $line[10];
					$block_static->linktoref = (string) $line[11];
					$block_static->linktype = (string) $line[12];
					$block_static->object_data = json_decode((string) $line[13]);
					$block_static->object_version = (string) $line[14];
					$block_static->object_format = (string) $line[15];
					$block_static->signature = (string) $line[16];

					// Status from file: 'Valid' or 'KO'
					$statusline = (string) $line[17];
				}


				if ($block_static->id > 0) {
					// Status revalidated from calculation using the HMAC secret key (possible only when we are on the same instance than
					// the one hosting the initial database of the archive)
					$tmp = $block_static->checkSignature($previoushash, 2);

					// To see the detail of line to to test signature calculation
					/*
					if ($block_static->id == 411) {
						$concatenateddata = $block_static->buildKeyForSignature($block_static->object_format);
						if (empty($previoushash)) {
							$tmparray = $block_static->getPreviousHash(0, $block_static->id);
							$previoushash = $tmparray['previoushash'];
						}
						var_dump($block_static->id, $previoushash, $concatenateddata, $tmp);
						exit;
					}*/

					$signature = $tmp['calculatedsignature'];
					$previoushash = $block_static->signature;

					//print 'Line '.$numline.': Recalculate from file: '.$signature.', in file '.$block_static->signature."<br>\n";
					if ($statusline == 'Valid') {
						// The signature calculated must match the recorded signature
						if ($signature != $block_static->signature) {
							$nbLinesModifiedInExportButKo++;
							//print 'Error: Line '.$numline.' reports that signature is ok but it seems to not match the one recalculated.';
						}
					}
					if ($statusline == 'KO') {
						$nbLinesModifiedBeforeExport++;
						//print 'The line '.$numline.' was modified into the Unalterable Log before being exported.';
					}

					// Note: this field is not more used, it has been replacedwith a global signature on all the file
					/*
					try {
						$concatenateddata = $block_static->buildKeyForSignature();
						$signatureexport = dol_hash($previoushashexport.$concatenateddata, 'sha256');
						$previoushashexport = $signatureexport;
					} catch(Exception $e) {
						setEventMessages('Bad format for line '.$numline.'. '.$e->getMessage(), null, 'errors');
						break;
					}
					*/
				}

				if ($lineanalyzed && ($lineactioncode == 'BILL_VALIDATE' || $lineactioncode == 'PAYMENT_CUSTOMER_CREATE' || $lineactioncode == 'PAYMENT_CUSTOMER_DELETE')) {
					// For action = BILL_VALIDATE, we keep only first invoice found, but this should not happen because edition of invoice is never possible on
					// certified version (locked) and very difficult on other version.
					if ($lineactioncode != 'BILL_VALIDATE' || empty($refinvoicefound[$lineref])) {
						if ($lineactioncode == 'PAYMENT_CUSTOMER_CREATE' || $lineactioncode == 'PAYMENT_CUSTOMER_DELETE') {
							$lineactioncode = 'PAYMENT_CUSTOMER';
						}

						$totalhtamountforaction[$lineactioncode] += $lineamountht;
						$totalvatamountforaction[$lineactioncode] += ($lineamountttc - $lineamountht);
						$totalamountforaction[$lineactioncode] += $lineamountttc;
					}
					if ($lineactioncode == 'BILL_VALIDATE') {
						$refinvoicefound[$lineref] = 1;
					}
				}

				// Test if line is a summary line
				if (preg_match('/^SUMMARY /', (string) $line[0])) {
					// We are on a line for summary information
					$lineanalyzed = 1;
					/*
					if (preg_match('/^SUMMARY TURNOVER BILLED/', (string) $line[0])) {
						// Do nothing, we recalculate amount from previous lines
					}
					if (preg_match('/^SUMMARY TURNOVER PAID/', (string) $line[0])) {
						// Do nothing, we recalculate amount from previous lines
					}
					*/
					if (preg_match('/^SUMMARY LIFETIME BILLED/', (string) $line[0])) {
						// We load data from line
						$amountstring = (string) $line[0];
						if (preg_match('/^SUMMARY LIFETIME BILLED[^\d]*\s:\s([\d\.]+)\s[^\d]+\s([\d\.]+)\s[^\d]+\s([\d\.]+)\s[^\d]+/', $amountstring, $reg)) {
							$amounthtlifetime['BILL_VALIDATE'] = (float) $reg[1];
							$amountvatlifetime['BILL_VALIDATE'] = (float) $reg[2];
							$amountttclifetime['BILL_VALIDATE'] = (float) $reg[3];
						}
					}
					if (preg_match('/^SUMMARY LIFETIME PAID/', (string) $line[0])) {
						$amountstring = (string) $line[0];
						if (preg_match('/^SUMMARY LIFETIME PAID[^\d]*\s:\s([\d\.]+)/', $amountstring, $reg)) {
							$amounthtlifetime['PAYMENT_CUSTOMER'] = (float) $reg[1];
							$amountvatlifetime['PAYMENT_CUSTOMER'] = 0.0;
							$amountttclifetime['PAYMENT_CUSTOMER'] = (float) $reg[1];
						}
					}
				}

				if (preg_match('/END - ([a-z0-9_]+)=([a-z0-9]+) - ([a-z0-9_]+)=([a-z0-9]+)$/', (string) $line[0], $reg)) {
					$lineanalyzed = 1;
					$algosign=$reg[1];
					$hashsign=$reg[2];
					$algoauth=$reg[3];
					$hashauth=$reg[4];

					if ($algosign == 'sha256') {
						$algo = 'sha256';
						$recalculatedhashsign = hash_file($algo, $fullpathtmp);
					}
					if ($algoauth == 'hmac_sha256') {
						$algo = 'sha256';
						$recalculatedhashauth = hash_hmac_file($algo, $fullpathtmp, $secretkey);
					}
				}

				if (!$lineanalyzed) {
					print 'Line '.$numline.' has format '.$formatexport.' that is not supported';
				}
			}
			fclose($handle);
		} else {
			setEventMessages('Failed to open file '.GETPOST('urlfile'), null, 'errors');
		}

		print '<br><br>';

		if ($recalculatedhashsign && hash_equals($recalculatedhashsign, $hashsign)) {
			print img_picto('', 'tick', 'class="valignmiddle pictofixedwidth"');
			print '<b>'.$langs->trans("FileIntegrity").'</b> ';
			print ' '.$form->textwithpicto('', $langs->trans("FileContentMatchSignature").'<br><br>'.$algosign.' = '.$recalculatedhashsign);
		} else {
			print img_picto('', 'cross', 'class="error valignmiddle pictofixedwidth"');
			print '<b>'.$langs->trans("FileIntegrity").'</b> ';
			print ' '.$form->textwithpicto('', $langs->trans("FileHasBeenCorrupted").'<br><br>Recalculated '.$recalculatedhashsign.' != Found in file '.$hashsign);
		}
		print '<br><br>';

		if ($recalculatedhashauth && hash_equals($recalculatedhashauth, $hashauth)) {
			print img_picto('', 'tick', 'class="valignmiddle pictofixedwidth"');
			print '<b>'.$langs->trans("FileAuthenticity").'</b> ';
			print ' - <span class="opacitymedium">'.$langs->trans("FileWasGeneratedByThisInstance").'</span>';
			print ' '.$form->textwithpicto('', $langs->trans("FileContentMatchSignature").'<br><br>'.$algoauth.' = '.$recalculatedhashauth);
		} elseif ($recalculatedhashsign && hash_equals($recalculatedhashsign, $hashsign)) {
			print img_picto('', 'cross', 'class="error valignmiddle pictofixedwidth"');
			print '<b>'.$langs->trans("FileAuthenticity").'</b> ';
			print ' '.$form->textwithpicto('', $langs->trans("FileNotFromInstance").'<br><br>Recalculated '.$recalculatedhashauth.' != Found in file '.$hashauth);
		} else {
			print img_picto('', 'cross', 'class="error valignmiddle pictofixedwidth"');
			print '<b>'.$langs->trans("FileAuthenticity").'</b> ';
			print ' '.$form->textwithpicto('', $langs->trans("FileHasBeenCorruptedOrNotFromInstance").'<br><br>Recalculated '.$recalculatedhashauth.' != Found in file '.$hashauth);
		}
		print '<br><br>';

		/*
		if (!userIsTaxAuditor()) {
			if ($nbLinesModifiedInExportButKo) {
				print img_picto('', 'cross', 'class="error valignmiddle pictofixedwidth"');
				print '<b>'.$langs->trans("nbLinesModifiedInExportButKo").'</b>: ';
				print '<br><br>';
			}
		}
		*/

		if ($nbLinesModifiedBeforeExport) {
			print img_picto('', 'warning', 'class="error valignmiddle pictofixedwidth"');
			print '<b>'.$langs->trans("nbLinesModifiedBeforeExport").'</b>';
			print '<br><br>';
		}

		/*
		print img_picto('', 'minus', 'class="valignmiddle pictofixedwidth"');
		print '<b>'.$langs->trans("DetectionOfSystemRestoration").'</b>: ';
		print '<span class="opacitymedium">';
		print $langs->trans("FeatureOnlyWhenArchiveAnalyzedFrom", "https://www.dolibarr.org/onlinecheckarchive.php");
		print '</span><br>';
		print '<br>';
		*/

		print '<hr>';

		$arraykeys = array('BILL_VALIDATE', 'PAYMENT_CUSTOMER');
		foreach ($arraykeys as $key) {
			$totalhttoshow = $totalhtamountforaction[$key];
			$totalvattoshow = $totalvatamountforaction[$key];
			$totaltoshow = $totalamountforaction[$key];

			print '<b>'.dolPrintHTML($langs->trans("TotalForAction").' '.$langs->trans('log'.$key)).'</b>';
			if ($key == 'BILL_VALIDATE') {
				print ' <span class="opacitymedium">('.$langs->trans("Turnover").')</span>';
			} elseif ($key == 'PAYMENT_CUSTOMER') {
				print ' <span class="opacitymedium">('.$langs->trans("TurnoverCollected").')</span>';
			}
			print ': ';

			if ($key == 'PAYMENT_CUSTOMER') {
				print '<span class="amount">'.price($totaltoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';
			} else {
				print $langs->trans("HT").': ';
				print '<span class="amount">'.price($totalhttoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';

				print ' - ';

				print $langs->trans("VAT").': ';
				print '<span class="amount">'.price($totalvattoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';

				print ' - ';

				print $langs->trans("TTC").': ';
				print '<span class="amount">'.price($totaltoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';
			}
			print '<br>';
		}

		// Now print the value for lifetime amounts.
		$arraykeys = array('BILL_VALIDATE', 'PAYMENT_CUSTOMER');
		foreach ($arraykeys as $key) {
			if (is_null($amounthtlifetime[$key])) {		// If not entry found, we discard
				continue;
			}
			$totalhttoshow = $amounthtlifetime[$key];
			$totalvattoshow = $amountvatlifetime[$key];
			$totaltoshow = $amountttclifetime[$key];

			print '<b>'.dolPrintHTML($langs->trans("LifetimeAmountShort").' '.$langs->trans('log'.$key)).'</b>';
			if ($key == 'BILL_VALIDATE') {
				print ' <span class="opacitymedium">('.$langs->trans("Turnover").')</span>';
			} elseif ($key == 'PAYMENT_CUSTOMER') {
				print ' <span class="opacitymedium">('.$langs->trans("TurnoverCollected").')</span>';
			}
			print ': ';

			if ($key == 'PAYMENT_CUSTOMER') {
				print '<span class="amount">'.price($totaltoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';
			} else {
				print $langs->trans("HT").': ';
				print '<span class="amount">'.price($totalhttoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';

				print ' - ';

				print $langs->trans("VAT").': ';
				print '<span class="amount">'.price($totalvattoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';

				print ' - ';

				print $langs->trans("TTC").': ';
				print '<span class="amount">'.price($totaltoshow, 0, $langs, 1, -1, -1, getDolCurrency()).'</span>';
			}
			print '<br>';
		}


		print '<br>';

		$text = $langs->trans("IfIntegrityAuthenticityIsOkYouCanGetdetailByOpeningTheFile");
		$text .= '<br>';
		$text .= $langs->trans("IfIntegrityAuthenticityIsOkYouCanGetdetailByOpeningTheFile2");
		//$langs->transnoentitiesnoconv("logBILL_VALIDATE"), $langs->transnoentitiesnoconv("logPAYMENT_CUSTOMER_CREATE"), $langs->transnoentitiesnoconv("logDOC_DOWNLOAD"), $langs->transnoentitiesnoconv("logCASHCONTROL_CLOSE")).'...');
		print info_admin($text);
	}


	print '<br><br>';
	print '<center><a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("BackToList").'</a></center>';
}

if ($action != 'check' && $action != 'checkconfirmed') {
	$htmltext = '';

	if (!userIsTaxAuditor()) {
		$htmltext .= $langs->trans("UnalterableLogTool2", $langs->transnoentities("Archives"))."<br>";
		if ($mysoc->country_code == 'FR') {
			$htmltext .= '<br>'.$langs->trans("UnalterableLogTool1FR").'<br>';
		}
		//$htmltext .= $langs->trans("UnalterableLogTool1");
		//$htmltext .= $langs->trans("UnalterableLogTool3")."<br>";

		print info_admin($htmltext, 0, 0, 'warning');

		print '<br>';
	} else {
		print '<br>';
	}

	$param = '';
	if ($contextpage != getDolDefaultContextPage(__FILE__)) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($search_id != '') {
		$param .= '&search_id='.urlencode($search_id);
	}
	if ($search_fk_user > 0) {
		$param .= '&search_fk_user='.urlencode($search_fk_user);
	}
	if ($search_startyear > 0) {
		$param .= '&search_startyear='.((int) $search_startyear);
	}
	if ($search_startmonth > 0) {
		$param .= '&search_startmonth='.((int) $search_startmonth);
	}
	if ($search_startday > 0) {
		$param .= '&search_startday='.((int) $search_startday);
	}
	if ($search_endyear > 0) {
		$param .= '&search_endyear='.((int) $search_endyear);
	}
	if ($search_endmonth > 0) {
		$param .= '&search_endmonth='.((int) $search_endmonth);
	}
	if ($search_endday > 0) {
		$param .= '&search_endday='.((int) $search_endday);
	}
	if ($search_amount) {
		$param .= '&search_amount='.urlencode($search_amount);
	}
	if ($search_signature) {
		$param .= '&search_signature='.urlencode($search_signature);
	}
	if ($search_showonlyerrors > 0) {
		$param .= '&search_showonlyerrors='.((int) $search_showonlyerrors);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}
	if (GETPOST('withtab', 'alpha')) {
		$param .= '&withtab='.urlencode(GETPOST('withtab', 'alpha'));
	}

	// Add $param from extra fields
	//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	if ($action == 'deletefile') {
		$langs->load("companies"); // Need for string DeleteFile+ConfirmDeleteFiles
		print $form->formconfirm(
			$_SERVER["PHP_SELF"].'?urlfile='.urlencode(GETPOST("urlfile")).'&linkid='.GETPOSTINT('linkid').(empty($param) ? '' : $param),
			$langs->trans('DeleteFile'),
			$langs->trans('ConfirmDeleteFile'),
			'confirm_deletefile',
			'',
			'',
			1
		);
	}


	if (!userIsTaxAuditor()) {
		print '<form method="POST" id="exportArchives" action="'.$_SERVER["PHP_SELF"].'?output=file">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="export">';

		print '<div class="right">';

		print '<span class="hideonsmartphone">'.$langs->trans("RestrictYearToExport").': </span>';
		// Month
		print $formother->select_month((string) GETPOSTINT('monthtoexport'), 'monthtoexport', $langs->trans("Month"), 0, 'minwidth50 maxwidth75imp valignmiddle', true);
		print '<input type="text" name="yeartoexport" class="valignmiddle maxwidth75imp" value="'.GETPOST('yeartoexport').'" placeholder="'.$langs->trans("Year").'">';

		print ' ';

		// Disabled, we will use the getHashUniqueIdOfRegistration() as secret HMAC
		//print '<input type="text" name="hmacexportkey" class="valignmiddle minwidth150imp maxwidth300imp" required value="'.GETPOST('hmacexportkey').'" placeholder="'.$langs->trans("Password").'">';

		print ' ';

		print '<input type="hidden" name="withtab" value="'.GETPOST('withtab', 'alpha').'">';
		print '<input type="submit" name="downloadcsv" class="button" value="'.$langs->trans('DownloadLogCSV').'">';
		/*if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY')) {
			print ' | <a href="?action=downloadblockchain'.(GETPOST('withtab', 'alpha') ? '&withtab='.GETPOST('withtab', 'alpha') : '').'">'.$langs->trans('DownloadBlockChain').'</a>';
		}*/
		print ' </div><br>';

		print '</form>';
	}

	/*
	print '<form method="POST" id="searchFormList" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';

	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="withtab" value="'.GETPOST('withtab', 'alpha').'">';

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	*/

	$filearray = dol_dir_list($upload_dir, 'files', 0, '', null, 'name', SORT_ASC, 1);

	$modulepart = 'blockedlog';
	$relativepathwithnofile = 'archives/';
	$disablemove = 1;
	/*
	$param = '&id='.$object->id.'&entity='.(empty($object->entity) ? getDolEntity() : $object->entity);
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	*/

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	$savingdocmask = '';

	$object = $block_static;

	// Get the form to add files (upload and links)
	$tmparray = $formfile->form_attach_new_file(
		$_SERVER["PHP_SELF"],
		'',
		0,
		0,
		$permission,
		$conf->browser->layout == 'phone' ? 40 : 60,
		$object,
		'',
		1,
		$savingdocmask,
		1,
		'formuserfile',
		'',
		'',
		0,
		0,
		0,
		2
	);

	$formToUploadAFile = '';

	if (is_array($tmparray) && !empty($tmparray)) {
		$formToUploadAFile = $tmparray['formToUploadAFile'];
	}

	// List of document
	$formfile->list_of_documents(
		$filearray,
		null,
		$modulepart,
		$param,
		0,
		$relativepathwithnofile, // relative path with no file. For example "0/1"
		$permission,
		0,
		'',
		0,
		$langs->transnoentitiesnoconv('Archives'),
		'',
		0,
		$permtoedit,
		$upload_dir,
		$sortfield,
		$sortorder,
		$disablemove,
		0,
		-1,
		'',
		array('afteruploadtitle' => $formToUploadAFile, 'showhideaddbutton' => 1, 'hideshared' => 1, 'buttons' => array(0 => array('picto' => img_picto($langs->trans("ControlFile"), 'question'), 'url' => $_SERVER["PHP_SELF"].'?action=check'.$param)))
	);
}


if (GETPOST('withtab', 'alpha')) {
	print dol_get_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
$db->close();
