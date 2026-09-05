<?php
/* Copyright (C) 2024-2026  Frédéric France     <frederic.france@free.fr>
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
 *       \file       htdocs/user/ajax/users.php
 *       \brief      File to return Ajax response on a user list request.
 *       			 Used by the autocomplete combo of users when USER_USE_SEARCH_TO_SELECT is set.
 *       			 Search is done on firstname, lastname and login.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
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

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$htmlname = (string) GETPOST('htmlname', 'aZ09');
$outjson = (GETPOSTINT('outjson') ? GETPOSTINT('outjson') : 0);
$excludeids = GETPOST('exclude', 'intcomma');
$include = GETPOST('include', 'alphanohtml');
$forceentity = GETPOST('force_entity', 'alphanohtml');
$showstatus = GETPOSTINT('showstatus');
$notdisabled = GETPOSTINT('notdisabled');
$maxlength = GETPOSTINT('maxlength');
// Note: we do NOT read a 'morefilter' from the request. select_dolusers()'s $morefilter is a raw
// Universal Search Filter forwarded to forgeSQLFromUniversalSearchCriteria(); accepting it from the
// client would let any logged-in user run arbitrary WHERE clauses on llx_user (a boolean-oracle
// information disclosure on user columns). It must stay server-controlled only.

// Security check: a logged-in internal user is enough (select_dolusers() itself is not permission gated
// on purpose, so any internal user allowed to open a form with a "assigned to" field can search the list).
// External users (portal contacts, $user->socid > 0) have no legitimate use case for browsing/searching
// the internal user directory, so they are denied here the same way ajaxcompanies.php/contact.php restrict
// their own directories for external users.
if (empty($user->id)) {
	httponly_accessforbidden('Not logged', 403);
}
if ($user->socid > 0) {
	httponly_accessforbidden('Not allowed for external users', 403);
}

/*
 * View
 */

top_httphead('application/json');

if ($htmlname === '') {
	print json_encode(array());
	$db->close();
	return;
}

$minlength = getDolGlobalInt('USER_USE_SEARCH_TO_SELECT');

// The typed term is sent by jQuery UI as a GET param named like $htmlname.
$searchkey = (string) GETPOST($htmlname, 'alphanohtml');
if ($searchkey === '' && $minlength >= 1) {
	// Not in "infinite list" mode (USER_USE_SEARCH_TO_SELECT is not numeric): an empty term returns nothing.
	print json_encode(array());
	$db->close();
	return;
}

// Anti-DoS protection: require at least USER_USE_SEARCH_TO_SELECT chars (no minimum in "infinite list" mode).
if ($minlength >= 1 && dol_strlen($searchkey) < $minlength) {
	httponly_accessforbidden('Call of user/ajax/users.php with a too short search string', 400);
}

$exclude = null;
if ($excludeids !== '') {
	$exclude = array_map('intval', explode(',', $excludeids));
}

// $include is either 'hierarchy', 'hierarchyme' or a comma separated list of user ids
if ($include !== '' && $include !== 'hierarchy' && $include !== 'hierarchyme') {
	$include = array_map('intval', explode(',', $include));
}

$form = new Form($db);

// Cap the number of rows (mostly useful in "infinite list" mode where the term can be empty)
$limit = getDolGlobalInt('USER_LIMIT_SIZE', 100);

$arrayresult = $form->select_dolusers('', $htmlname, 0, $exclude, 0, $include, '', $forceentity, $maxlength, $showstatus, '', 0, '', '', $notdisabled, 2, false, 0, $searchkey, $limit);

$outarray = array();
if (is_array($arrayresult)) {
	foreach ($arrayresult as $id => $val) {
		if ((int) $id <= 0) {
			continue;
		}
		// Keep the dropdown a plain single line (like the thirdparty autocomplete). We deliberately do not send
		// 'labelhtml' here: select_dolusers() builds it with getNomUrl() which renders a block photo thumbnail
		// and would break the layout of the jQuery UI autocomplete list.
		$label = dol_string_nohtmltag($val['label']);
		$outarray[] = array(
			'key' => (int) $id,
			'value' => $label,
			'label' => $label,
		);
	}
}

print json_encode($outarray);

$db->close();
