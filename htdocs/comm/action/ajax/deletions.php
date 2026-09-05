<?php
/* Copyright (C) 2026       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/comm/action/ajax/deletions.php
 *	\brief      Return the ids of agenda events deleted since a given time.
 *
 *	Meant to be polled by a page that keeps a partial view of the events (e.g. a
 *	calendar refreshed in ajax) so it can drop removed events from its display
 *	without reloading the whole set.
 *
 *	Input  (GET or POST):
 *	  - since   : unix timestamp, lower bound of the deletion date (optional,
 *	              defaults to now minus the retention delay)
 *	  - element : element type to look up (optional, defaults to "action")
 *	Output (application/json):
 *	  {"now": <server unix time>, "since": <effective since>, "element": "action", "deleted": [id, ...]}
 *	Poll again later passing the returned "now" as the next "since".
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
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
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/deletionlog.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

if (!isModEnabled('agenda')) {
	httponly_accessforbidden('Module Agenda not enabled', 403);
}
if (!$user->hasRight('agenda', 'myactions', 'read') && !$user->hasRight('agenda', 'allactions', 'read')) {
	httponly_accessforbidden('Not enough permissions to read agenda events', 403);
}

$element = GETPOST('element', 'aZ09') ? GETPOST('element', 'aZ09') : 'action';

$retentiondays = getDolGlobalInt('MAIN_DELETION_LOG_RETENTION_DAYS', DeletionLog::DEFAULT_RETENTION_DAYS);
if ($retentiondays <= 0) {
	$retentiondays = DeletionLog::DEFAULT_RETENTION_DAYS;
}

$now = dol_now();
$since = GETPOSTINT('since');
if ($since <= 0 || $since < ($now - $retentiondays * 24 * 3600)) {
	// No usable "since", or older than what the log can still hold: clamp to the retention window.
	$since = $now - $retentiondays * 24 * 3600;
}

$deleted = DeletionLog::getDeletionsSince($db, $element, $since, $conf->entity);


/*
 * View
 */

top_httphead('application/json');

echo json_encode(array(
	'now' => $now,
	'since' => $since,
	'element' => $element,
	'deleted' => $deleted,
));

$db->close();
