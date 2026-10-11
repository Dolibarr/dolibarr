<?php
/* Copyright (C) 2026		John BOTELLA
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
 *       \file      htdocs/core/ajax/objectline.php
 *       \brief     Return the html of document lines as printed in the card (CommonObject::getObjectLineHtml()),
 *                  to reload some lines after an ajax edition without loading the whole card.
 *                  GET with parameters element, id, lineids (list of line ids separated by a comma) and page (path of the card,
 *                  like /comm/propal/card.php, so the links of the lines are the ones of the card).
 *                  Answer is a JsonResponse, data contains the html of each line (null if the line does not exist anymore)
 *                  and the ids of all the lines of the object in the order of the card, to insert or remove lines.
 *                  With action=editline (one line, write permission, object in draft), the html is the edit form of the line
 *                  (rows of objectline_edit.tpl.php), to edit the line in ajax (Dolibarr.tools.card.lines).
 *                  The event messages kept in session by a card loaded with noflusheventmessages=1 are returned in the answer.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal, the page is called several times in a row
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREDIRECTBYMAINTOLOGIN')) {
	define('NOREDIRECTBYMAINTOLOGIN', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/jsonResponse.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php'; // Lines and edit forms use the ajax combo boxes, not loaded with NOREQUIREAJAX

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$element = GETPOST('element', 'aZ09');
$id = GETPOSTINT('id');
$lineids = GETPOST('lineids', 'intcomma');
$page = GETPOST('page', 'alphanohtml');
$action = GETPOST('action', 'aZ09');
$editline = ($action == 'editline'); // Test on permission done later, write permission checked by restrictedArea()

$langs->loadLangs(array('errors', 'companies', 'bills', 'products', 'orders', 'propal', 'compta', 'other'));

top_httphead('application/json');

$jsonResponse = new JsonResponse();

// Read only, lines are updated by the actions of the card
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_METHOD_NOT_ALLOWED);
	$jsonResponse->output();
}
if (empty($element) || empty($id) || $lineids === '' || (!empty($action) && !$editline)) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}

// Load object
$object = fetchObjectByElement($id, $element);
if (!is_object($object) || $object->id <= 0) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorRecordNotFound'), JsonResponse::HTTP_NOT_FOUND);
	$jsonResponse->output();
}

// Security check, restrictedArea() tests the read permission (write permission for the edit form) and the access of external users
$result = restrictedArea($user, empty($object->module) ? $object->element : $object->module, $object, $object->table_element, '', 'fk_soc', 'rowid', 0, 1, $editline ? 'write' : 'read');
if (!$result) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('NotEnoughPermissions'), JsonResponse::HTTP_FORBIDDEN);
	$jsonResponse->output();
}

// Lines are loaded like in the card, all the lines are needed to print one (number and position of the line)
if (method_exists($object, 'getLinesArray')) {
	$object->getLinesArray();
} elseif (empty($object->lines) && method_exists($object, 'fetch_lines')) {
	$object->fetch_lines(); // Supplier orders and invoices: lines are loaded by fetch()
}

// Only objects with lines printed by printObjectLines()
if (empty($object->table_element_line) || !is_array($object->lines)) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}

$object->fetch_thirdparty();

// The templates of the lines build their links with PHP_SELF, they must point to the card, not to this page.
// Only a path of a php page of this instance is accepted.
if (preg_match('/^[a-zA-Z0-9_\-\/\.]+\.php$/', $page) && strpos($page, '..') === false && strpos($page, DOL_URL_ROOT.'/') === 0) {
	$_SERVER['PHP_SELF'] = $page;
}

// Modules hooking the lines of the card render them as in the card
$context = $object->getObjectLinesDisplayContext();
$hookmanager->initHooks(array($context['hookcontext'], 'globalcard'));

$lines = array();
if ($editline) {
	// Edit form of one line, only on a draft like in the card
	$lineid = (int) $lineids;
	if (strpos($lineids, ',') !== false || $lineid <= 0 || !$object->isAjaxLineEdit()) {
		$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_BAD_REQUEST);
		$jsonResponse->output();
	}
	$html = $object->getObjectLineHtml($lineid, 'editline');
	$lines[$lineid] = ($html === '' ? null : $html);
} else {
	foreach (array_unique(explode(',', $lineids)) as $lineid) {
		if ((int) $lineid > 0) {
			$html = $object->getObjectLineHtml((int) $lineid);
			$lines[(int) $lineid] = ($html === '' ? null : $html);
		}
	}
}

$alllineids = array();
foreach ($object->lines as $line) {
	$alllineids[] = (int) $line->id;
}

$jsonResponse->data = array(
	'element' => $element,
	'id' => $id,
	'lines' => (object) $lines, // Always an object, keys are the ids of the lines
	'lineids' => $alllineids,
	'tms' => empty($object->date_modification) ? null : (int) $object->date_modification,
);

// Messages of the actions of a card loaded in background (noflusheventmessages=1), ex: error when a line is saved
$jsonResponse->addSessionEventMessages();

$jsonResponse->setSuccess();
$jsonResponse->output();
