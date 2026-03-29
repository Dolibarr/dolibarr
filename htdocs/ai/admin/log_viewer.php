<?php
/* Copyright (C) 2004-2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/admin/log_viewer.php
 * \ingroup ai
 * \brief AI Request Log Viewer with Payload Inspection
 */

require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var Form $form
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load translations
$langs->loadLangs(array("admin", "other"));

// Parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'ailoglist';
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');

// Search parameters for all columns
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));
$search_user = GETPOST('search_user', 'alpha');
$search_query = GETPOST('search_query', 'alpha');
$search_tool = GETPOST('search_tool', 'alpha');
$search_provider = GETPOST('search_provider', 'alpha');
$search_time_min = GETPOST('search_time_min', 'alpha');
$search_time_max = GETPOST('search_time_max', 'alpha');
$search_status = GETPOST('search_status', 'alpha');

// Pagination parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
if (!$sortfield) $sortfield = "l.date_request";
if (!$sortorder) $sortorder = "DESC";

// Initialize array of search criteria
$search_array = array(
	'search_date_start' => $search_date_start,
	'search_date_end' => $search_date_end,
	'search_user' => $search_user,
	'search_query' => $search_query,
	'search_tool' => $search_tool,
	'search_provider' => $search_provider,
	'search_time_min' => $search_time_min,
	'search_time_max' => $search_time_max,
	'search_status' => $search_status
);

// Access Control
if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

$error = '';
if ($action == 'purge' && $confirm == 'yes') {
	$db->begin();

	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "ai_request_log";
	$sql .= " WHERE entity IN (" . getEntity('airequestlog') . ")";

	$resql = $db->query($sql);

	if ($resql) {
		$nbDeleted = $db->affected_rows($resql);
		$db->commit();
		setEventMessages($langs->trans("LogsCleared") . " (" . $nbDeleted . ")", null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($db->lasterror(), null, 'errors');
	}

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

// Purge selection
if ($massaction == 'purge' && !empty($toselect) && is_array($toselect)) {
	$db->begin();

	foreach ($toselect as $id) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "ai_request_log";
		$sql .= " WHERE rowid = " . ((int) $id);
		$sql .= " AND entity IN (" . getEntity('airequestlog') . ")";

		$resql = $db->query($sql);
		if (!$resql) {
			$error++;
			$db->rollback();
			setEventMessages($db->lasterror(), null, 'errors');
			break;
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SelectedLogsDeleted"), null, 'mesgs');
	} else {
		$db->rollback();
	}

	$action = 'list';
	$massaction = '';
}

// Clear filter action
if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
	$search_date_start = '';
	$search_date_end = '';
	$search_user = '';
	$search_query = '';
	$search_tool = '';
	$search_provider = '';
	$search_time_min = '';
	$search_time_max = '';
	$search_status = '';
	// Reset page
	$page = 0;
}


/*
 * View
 */

// Initialize array of search criteria for the view
$param = '';
if ($contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
foreach ($search_array as $key => $val) {
	if (!empty($val) || $val === '0') {
		$param .= '&' . urlencode($key) . '=' . urlencode($val);
	}
}

llxHeader('', $langs->trans("AIRequestLogs"), '');


// Build WHERE clause
$where = array();

$where[] = "l.entity IN (" . getEntity('airequestlog') . ")";

if ($search_date_start) {
	$where[] = "l.date_request >= '" . $db->escape(date('Y-m-d H:i:s', $search_date_start)) . "'";
}
if ($search_date_end) {
	$where[] = "l.date_request <= '" . $db->escape(date('Y-m-d H:i:s', $search_date_end)) . "'";
}
if ($search_user) {
	$where[] = "u.login LIKE '%" . $db->escape($search_user) . "%'";
}
if ($search_query) {
	$where[] = "l.query_text LIKE '%" . $db->escape($search_query) . "%'";
}
if ($search_tool) {
	$where[] = "l.tool_name LIKE '%" . $db->escape($search_tool) . "%'";
}
if ($search_provider) {
	$where[] = "l.provider LIKE '%" . $db->escape($search_provider) . "%'";
}
if ($search_time_min) {
	$where[] = "l.execution_time >= " . floatval($search_time_min);
}
if ($search_time_max) {
	$where[] = "l.execution_time <= " . floatval($search_time_max);
}
if ($search_status) {
	$where[] = "l.status = '" . $db->escape($search_status) . "'";
}

$whereSQL = '';
if (!empty($where)) {
	$whereSQL = ' WHERE ' . implode(' AND ', $where);
}

// Get total count for pagination
$sqlCount = "SELECT COUNT(l.rowid) as total
             FROM " . MAIN_DB_PREFIX . "ai_request_log as l
             LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON l.fk_user = u.rowid";
$sqlCount .= $whereSQL;
$resqlCount = $db->query($sqlCount);
$totalRecords = $resqlCount ? $db->fetch_object($resqlCount)->total : 0;


$sql = "SELECT l.rowid, u.login
        FROM " . MAIN_DB_PREFIX . "ai_request_log as l
        LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON l.fk_user = u.rowid";
$sql .= $whereSQL;
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit, $offset);

$resql = $db->query($sql);
$num = $db->num_rows($resql);

// Create object for list
$object = new stdClass();
$object->total = $totalRecords;

$title = $langs->trans("AIRequestLogs");
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalRecords, 'title_ai', 0, '', '', $limit, 1, 0, 0, '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="limitform">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="list">';

// Add all search parameters to preserve them when changing the limit
foreach ($search_array as $key => $val) {
	if (!empty($val) || $val === '0') {
		print '<input type="hidden" name="' . $key . '" value="' . dol_escape_htmltag($val) . '">';
	}
}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr>';
print '<td class="right">';
print $langs->trans("Show") . ': ';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

// Create array of options for limit
 $arrayoflimit = array(5, 10, 20, 50, 100, 500, 1000);
print '<select class="flat" name="limit" onchange="this.form.submit()">';
foreach ($arrayoflimit as $val) {
	print '<option value="'.$val.'"';
	if ($limit == $val) print ' selected';
	print '>'.$val.'</option>';
}

print '</select>';
print ' ' . $langs->trans("Entries");
print '</td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

// Display form for filters
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="search_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste listwithfilterbefore">'."\n";

// Fields title
print '<tr class="liste_titre">';
print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "l.date_request", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("User", $_SERVER["PHP_SELF"], "u.login", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Query", $_SERVER["PHP_SELF"], "l.query_text", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("MCPTool", $_SERVER["PHP_SELF"], "l.tool_name", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Provider", $_SERVER["PHP_SELF"], "l.provider", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Time", $_SERVER["PHP_SELF"], "l.execution_time", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "l.status", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", $param, 'align="center"');
print '</tr>';
// Search row
print '<tr class="liste_titre_filter">';
// Date search
print '<td class="liste_titre">';
print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
print ' - ';
print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("To"));
print '</td>';
// User search
print '<td class="liste_titre"><input type="text" name="search_user" value="' . dol_escape_htmltag($search_user) . '" class="maxwidth100"></td>';
// Query search
print '<td class="liste_titre"><input type="text" name="search_query" value="' . dol_escape_htmltag($search_query) . '" class="maxwidth150"></td>';
// Tool search
print '<td class="liste_titre"><input type="text" name="search_tool" value="' . dol_escape_htmltag($search_tool) . '" class="maxwidth100"></td>';
// Provider search
print '<td class="liste_titre"><input type="text" name="search_provider" value="' . dol_escape_htmltag($search_provider) . '" class="maxwidth100"></td>';
// Time search
print '<td class="liste_titre center">';
print '<input type="text" name="search_time_min" value="' . dol_escape_htmltag($search_time_min) . '" size="3" placeholder="' . dol_escape_htmltag($langs->trans('Min')) . '">';
print '<input type="text" name="search_time_max" value="' . dol_escape_htmltag($search_time_max) . '" size="3" placeholder="' . dol_escape_htmltag($langs->trans('Max')) . '">';
// Status search
print '<td class="liste_titre center">';
$status_options = array('' => $langs->trans("All"), 'success' => $langs->trans("Success"), 'confirm' => $langs->trans("Confirm"), 'error' => $langs->trans("Error"));
print $form->selectarray('search_status', $status_options, $search_status, 0, 0, 0, '', 1);  // @phan-suppress-current-line PhanPluginSuspiciousParamOrder
print '</td>';
// Search buttons
print '<td class="liste_titre center">';
$searchpicto = img_picto($langs->trans("Search"), 'search.png', '', 0, 1);
print '<input type="image" class="liste_titre" name="button_search" src="' . $searchpicto . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
$clearpicto = img_picto($langs->trans("RemoveFilter"), 'searchclear.png', '', 0, 1);
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . $clearpicto . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</td>';
print '</tr>';

// Mass action buttons
print '<tr class="liste_titre">';
print '<td class="liste_titre" colspan="8">';
print '<div class="center">';
print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=purge&token='.newToken().'" onclick="return confirm(\''.$langs->trans("ConfirmDeleteAllLogs").'\');">'.$langs->trans("ClearAllLogs").'</a></div>';
print '</div>';
print '</td>';
print '</tr>';

if ($resql && $num > 0) {
	$i = 0;
	while ($obj = $db->fetch_object($resql)) {
		print '<tr class="oddeven">';

		// Date
		print '<td>' . dol_print_date($db->jdate($obj->date_request), 'dayhour') . '</td>';

		// User
		print '<td>' . ($obj->login ? dol_escape_htmltag($obj->login) : $langs->trans("Unknown")) . '</td>';

		// Query - properly escaped
		$shortQuery = dol_trunc($obj->query_text, 60);
		print '<td title="' . dol_escape_htmltag($obj->query_text) . '">' . dol_escape_htmltag($shortQuery) . '</td>';

		// Tool
		print '<td>' . dol_escape_htmltag($obj->tool_name) . '</td>';

		// Provider
		print '<td>' . dol_escape_htmltag($obj->provider) . '</td>';

		// Time
		$timeColor = ($obj->execution_time > 5) ? 'color:red;' : '';
		print '<td style="' . $timeColor . '" align="center">' . round($obj->execution_time, 2) . 's</td>';

		// Status
		$badge = 'badge-status0';
		if ($obj->status == $langs->transnoentitiesnoconv("Success")) {
			$badge = 'badge-status4'; // Green
		}
		if ($obj->status == $langs->transnoentitiesnoconv("Confirm")) {
			$badge = 'badge-status3'; // Yellow
		}
		if ($obj->status == $langs->transnoentitiesnoconv('Error')) {
			$badge = 'badge-status8'; // Red
		}
		print '<td align="center"><span class="badge ' . $badge . '">' . dol_escape_htmltag($obj->status) . '</span></td>';

		// Details Button (Triggers Modal)
		// We embed data attributes securely with proper UTF-8 handling
		$reqSafe = base64_encode($obj->raw_request_payload);
		$resSafe = base64_encode($obj->raw_response_payload);
		$errSafe = base64_encode($obj->error_msg);

		print '<td align="center">';
		print '<a href="#" class="button button-small" onclick="openLogModal(this)"
				data-req="' . dol_escape_htmltag($reqSafe) . '"
				data-res="' . dol_escape_htmltag($resSafe) . '"
				data-err="' . dol_escape_htmltag($errSafe) . '">';
		print '<span class="fa fa-search-plus"></span> ' . $langs->trans("View");
		print '</a>';
		print '</td>';

		print '</tr>';
		$i++;
	}
} else {
	$colspan = 8;
	print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoLogsFound");
	if (!empty($where)) {
		print ' ' . $langs->trans("MatchingSearchCriteria");
	}
	print '. ' . $langs->trans("TryAskingAI") . '.</td></tr>';
}
print '</table></div>';

print '</form>';

// --- MODAL HTML & JS ---
?>
<div id="logModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
	<div style="background-color:#fff; margin:5% auto; padding:20px; border:1px solid #888; width:80%; max-width:900px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
		<span style="float:right; font-size:28px; font-weight:bold; cursor:pointer;" onclick="document.getElementById('logModal').style.display='none'">&times;</span>
		<h2><?php echo $langs->trans("LogDetails"); ?></h2>

		<h3><?php echo $langs->trans("ErrorWarning"); ?></h3>
		<div id="modalError" style="background:#fff0f0; border:1px solid #ffcdd2; color:#d32f2f; padding:10px; border-radius:4px; display:none;"></div>

		<div style="display:flex; gap:20px; margin-top:15px;">
			<div style="flex:1;">
				<h3><?php echo $langs->trans("RequestPayload"); ?></h3>
				<textarea id="modalReq" style="width:100%; height:300px; font-family:monospace; font-size:12px; border:1px solid #ccc;" readonly></textarea>
			</div>
			<div style="flex:1;">
				<h3><?php echo $langs->trans("ResponsePayload"); ?></h3>
				<textarea id="modalRes" style="width:100%; height:300px; font-family:monospace; font-size:12px; border:1px solid #ccc;" readonly></textarea>
			</div>
		</div>

		<div style="text-align:right; margin-top:15px;">
			<button class="button" onclick="document.getElementById('logModal').style.display='none'"><?php echo $langs->trans("Close"); ?></button>
		</div>
	</div>
</div>

<script>
// UTF-8 safe base64 decoding function
function base64ToUtf8(str) {
	// Going backwards: from bytestream, to percent-encoding, to original string.
	return decodeURIComponent(atob(str).split('').map(function(c) {
		return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
	}).join(''));
}

// Function to decode Unicode escape sequences in JSON strings
function decodeUnicodeEscapes(str) {
	// First, try to parse as JSON to handle escaped Unicode properly
	try {
		// If it's a JSON string, parse and stringify to decode escapes
		const parsed = JSON.parse(str);
		return JSON.stringify(parsed, null, 2);
	} catch (e) {
		// If not valid JSON, try to decode Unicode escapes in the string
		return str.replace(/\\u([0-9a-fA-F]{4})/g, function(match, p1) {
			return String.fromCharCode(parseInt(p1, 16));
		});
	}
}

// Sanitize HTML to prevent XSS
function escapeHtml(text) {
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

function openLogModal(btn) {
	// Decode Base64 safely with UTF-8 support
	const reqBase64 = btn.getAttribute('data-req') || '';
	const resBase64 = btn.getAttribute('data-res') || '';
	const errBase64 = btn.getAttribute('data-err') || '';

	let req = '';
	let res = '';
	let err = '';

	try {
		// Decode Unicode escape sequences
		if (reqBase64) {
			req = base64ToUtf8(reqBase64);
			req = decodeUnicodeEscapes(req);
		}
		if (resBase64) {
			res = base64ToUtf8(resBase64);
			res = decodeUnicodeEscapes(res);
		}
		if (errBase64) {
			err = base64ToUtf8(errBase64);
			err = decodeUnicodeEscapes(err);
		}
	} catch (e) {
		console.error('Error decoding base64:', e);
		// Fallback to regular atob if UTF-8 decoding fails
		req = reqBase64 ? atob(reqBase64) : '';
		res = resBase64 ? atob(resBase64) : '';
		err = errBase64 ? atob(errBase64) : '';
	}

	// Sanitize content before setting it
	document.getElementById('modalReq').value = req || '(<?php echo $langs->trans("NoRequestPayload"); ?>)';
	document.getElementById('modalRes').value = res || '(<?php echo $langs->trans("NoResponsePayload"); ?>)';

	const errDiv = document.getElementById('modalError');
	if (err) {
		errDiv.innerText = err;
		errDiv.style.display = 'block';
	} else {
		errDiv.style.display = 'none';
	}

	document.getElementById('logModal').style.display = 'block';
}
</script>

<?php
llxFooter();
