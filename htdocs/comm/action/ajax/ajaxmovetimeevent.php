<?php
/* Copyright (C) 2026	Frédéric France			<frederic.france@free.fr>
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
 *  \file       htdocs/comm/action/ajax/ajaxmovetimeevent.php
 *  \brief      Persist, via Ajax, an agenda event moved to another day and/or time slot in the day/week-view hourly grids
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
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
require '../../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

$langs->loadLangs(array('errors', 'resource'));

$id = GETPOSTINT('id');
$newdatetime = GETPOST('newdatetime', 'alpha');
// Optional. Set by a resize of the box by its bottom edge: the start does not move and the end is
// given, instead of following the start by the same delta as it does for a move.
$newenddatetime = GETPOST('newenddatetime', 'alpha');

top_httphead('application/json');

$response = array('error' => 0, 'message' => '');

$object = new ActionComm($db);
$result = $object->fetch($id);

if ($result <= 0) {
	$response['error'] = 1;
	$response['message'] = $langs->trans('ErrorRecordNotFound');
} elseif (!is_string($newdatetime) || !preg_match('/^[0-9]{14}$/', $newdatetime)
	|| ($newenddatetime !== '' && (!is_string($newenddatetime) || !preg_match('/^[0-9]{14}$/', $newenddatetime)))) {
	$response['error'] = 1;
	$response['message'] = $langs->trans('ErrorBadParameters');
} else {
	$hookmanager->initHooks(array('actioncard'));
	$parameters = array('id' => $id, 'newdatetime' => $newdatetime, 'newenddatetime' => $newenddatetime);
	$actionmove = 'mupdatetime';
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $actionmove);

	if ($reshook < 0) {
		$response['error'] = 1;
		$response['message'] = $hookmanager->error;
	} elseif (!empty($reshook)) {
		// A hook fully handled the move; nothing more to do (mirrors card.php's behavior
		// when a module takes over the doActions hook — $response stays the default success)
	} else {
		$resultrestrictedarea = restrictedArea($user, 'agenda', $object, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id', 0, 1);

		$usercancreate = $user->hasRight('agenda', 'allactions', 'create')
			|| (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'));

		if (!$resultrestrictedarea || !$usercancreate) {
			$response['error'] = 1;
			$response['message'] = $langs->trans('NotEnoughPermissions');
		} else {
			$newdatep = dol_mktime(
				(int) substr($newdatetime, 8, 2),
				(int) substr($newdatetime, 10, 2),
				(int) substr($newdatetime, 12, 2),
				(int) substr($newdatetime, 4, 2),
				(int) substr($newdatetime, 6, 2),
				(int) substr($newdatetime, 0, 4),
				'tzuserrel'
			);

			$newdatef = $object->datef;
			if (!empty($newdatep)) {
				if ($newenddatetime !== '') {
					// Resize: the end is absolute, the start does not move. An event that had no end
					// gets one here, which is what gives the box its height from now on.
					$newdatef = dol_mktime(
						(int) substr($newenddatetime, 8, 2),
						(int) substr($newenddatetime, 10, 2),
						(int) substr($newenddatetime, 12, 2),
						(int) substr($newenddatetime, 4, 2),
						(int) substr($newenddatetime, 6, 2),
						(int) substr($newenddatetime, 0, 4),
						'tzuserrel'
					);
				} elseif (!empty($newdatef)) {
					$newdatef += $newdatep - $object->datep;
				}
			}

			// 15 minutes is the step the grid snaps a resize to, so a box can never ask for less.
			// A request that does did not come from the grid.
			if (empty($newdatep) || ($newenddatetime !== '' && empty($newdatef))) {
				$response['error'] = 1;
				$response['message'] = $langs->trans('ErrorBadParameters');
			} elseif ($newenddatetime !== '' && $newdatef - $newdatep < 15 * 60) {
				$response['error'] = 1;
				$response['message'] = $langs->trans('ErrorBadParameters');
			} elseif ($newdatep != $object->datep || $newdatef != $object->datef) {
				$conflicts = $object->checkResourceConflicts($newdatep, $newdatef);

				if ($conflicts === -1) {
					$response['error'] = 1;
					$response['message'] = $object->error;
				} elseif (!empty($conflicts)) {
					$response['error'] = 1;
					$response['message'] = $object->formatResourceConflicts($conflicts, $langs);
				} else {
					$object->datep = $newdatep;
					$object->datef = $newdatef;

					$db->begin();
					$result = $object->update($user);
					if ($result < 0) {
						$db->rollback();
						$response['error'] = 1;
						$response['message'] = $object->error;
					} else {
						$db->commit();
					}
				}
			}
		}
	}
}

print json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);

$db->close();
