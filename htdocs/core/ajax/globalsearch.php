<?php
/* Copyright (C) 2026		Michael Wallis			<mh.wallis@gmail.com>
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
 *      \file       htdocs/core/ajax/globalsearch.php
 *      \ingroup    core
 *      \brief      Return json with the records matching the string typed into the global search box
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
if (!defined('NOREDIRECTBYMAINTOLOGIN')) {
	define('NOREDIRECTBYMAINTOLOGIN', '1');
}

$res = @include '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/globalsearch.class.php';

top_httphead('application/json');

// Security check
if ($res == 'ERROR_NOT_LOGGED') {
	print json_encode(array('error' => 'ERROR_NOT_LOGGED', 'url' => DOL_URL_ROOT.'/index.php'));
	exit;
}
if (!getDolGlobalString('MAIN_SEARCHBOX_SHOW_RECORDS')) {
	httponly_accessforbidden('Feature not enabled');
}

$search = trim(GETPOST('q', 'restricthtml'));
$minchars = max(1, getDolGlobalInt('MAIN_SEARCHBOX_MIN_CHARS', 3));
$limit = max(1, getDolGlobalInt('MAIN_SEARCHBOX_NB_RECORDS', 5));

$result = array('sections' => array());

if (dol_strlen($search) >= $minchars) {
	// Get the sections of the search box, with the permissions, hidden sections and hooks already applied
	$langs->loadLangs(array('main', 'other', 'companies', 'products', 'propal', 'orders', 'bills', 'sendings', 'receptions', 'contracts', 'interventions', 'supplier_proposal', 'projects', 'members'));
	$usedbyinclude = 1;
	$arrayresult = array();
	include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php';

	$globalsearch = new GlobalSearch($db);
	$result = $globalsearch->search($search, $arrayresult, $limit);
}

print json_encode($result);

$db->close();
