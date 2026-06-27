<?php
/* Copyright (C) 2024 John BOTELLA
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

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');                // Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');                // If there is no need to load and show top and left menu
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');                // If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification


// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var DoliDB $db
 * @var User $user
 * @var HookManager $hookmanager
 * @var Translate $langs
 */
// Load required classes
require_once DOL_DOCUMENT_ROOT . '/core/class/jsonResponse.class.php';
require_once __DIR__ . '/class/memo.class.php';
if (!class_exists('Validate')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/validate.class.php';
}

// Initialize hooks for the interface
$hookmanager->initHooks(['quickmemoInterface']);

// Load translation files required by the page
$langs->loadLangs(["quickmemo", "other", 'main']);

$action = GETPOST('action');

// Security check: check if the module is enabled
if (!isModEnabled('quickmemo')) accessforbidden('Module not enabled');

$jsonResponse = new JsonResponse();

// Security check: basic read permission
if (!$user || !$user->hasRight('quickmemo', 'memo', 'read')) {
	$jsonResponse->msg = $langs->trans('NotEnoughRights');
	$jsonResponse->result = 0;
	print $jsonResponse->getResponse();
	$db->close();    // Close $db database opened handler
	exit;
}

// Execute hooks before standard actions
$reshook = $hookmanager->executeHooks('quickMemoInterface', [], $jsonResponse, $action);
if ($reshook < 0) {
	$jsonResponse->msg = $hookmanager->error;
	if (!empty($hookmanager->errors)) {
		$jsonResponse->msg = (!empty($hookmanager->error) ? '<br>' : '') . implode('<br>', $hookmanager->errors);
	}
	$jsonResponse->result = 0;
	print $jsonResponse->getResponse();
	$db->close();    // Close $db database opened handler
	exit;
}

// Action Dispatcher: Routes the request to the appropriate function based on the 'action' parameter
if ($reshook > 0) {
	// Action handled by hook
} elseif ($action === 'update_position') {
	quickMemoIntefaceActionUpdatePosition($jsonResponse);
} elseif ($action === 'update_all_positions') {
	quickMemoIntefaceActionUpdateAllPositions($jsonResponse);
} elseif ($action === 'list') {
	quickMemoIntefaceActionList($jsonResponse);
} elseif ($action === 'list_models') {
	quickMemoIntefaceActionListModels($jsonResponse);
} elseif ($action === 'update-color') {
	quickMemoIntefaceActionUpdateColor($jsonResponse);
} elseif ($action === 'update-shared-on-element') {
	quickMemoIntefaceActionUpdateSharedOnElement($jsonResponse);
} elseif ($action === 'update-private') {
	quickMemoIntefaceActionUpdatePrivate($jsonResponse);
} elseif ($action === 'archive') {
	quickMemoIntefaceActionArchiveNote($jsonResponse);
} elseif ($action === 'delete') {
	quickMemoIntefaceActionDeleteNote($jsonResponse);
} elseif ($action === 'create') {
	quickMemoIntefaceActionCreate($jsonResponse);
} elseif ($action === 'create_model') {
	quickMemoIntefaceActionCreateModel($jsonResponse);
} elseif ($action === 'delete_model') {
	quickMemoIntefaceActionDeleteModel($jsonResponse);
} elseif ($action === 'update_model_rank') {
	quickMemoIntefaceActionUpdateModelRank($jsonResponse);
} elseif ($action === 'update_note') {
	quickMemoIntefaceActionUpdateNote($jsonResponse);
} else {
	$jsonResponse->msg = 'Action not found';
}

print $jsonResponse->getResponse();

$db->close();    // Close $db database opened handler

/**
 * Update the coordinates (X, Y), dimensions (W, H), and Z-index of a single memo.
 * @param JsonResponse $jsonResponse The response object to populate.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionUpdatePosition($jsonResponse)
{
	global $user, $langs, $db;

	// Read permission is sufficient as this only affects the current user's view
	if (!$user->hasRight('quickmemo', 'memo', 'read')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	// Prevent moving a private note belonging to another user
	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantMoveThisPrivateNote');
		return false;
	}

	$z = GETPOST("z", "int");
	$x = GETPOST("x", "int");
	$y = GETPOST("y", "int");
	$w = GETPOST("w", "int");
	$h = GETPOST("h", "int");

	if (!$memo->updatePosition($user, (int) $x, (int) $y, (int) $w, (int) $h, (int) $z)) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = null;
		return true;
	}
}

/**
 * Batch update coordinates and Z-index for multiple memos.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool|void
 */
function quickMemoIntefaceActionUpdateAllPositions($jsonResponse)
{
	global $user, $langs, $db;

	// In case of position, read permission is enough because changing position will affect only this user not others.
	// So he can't modify the content but can move it for himself.
	if (!$user->hasRight('quickmemo', 'memo', 'read')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$json = file_get_contents('php://input');
	$data = json_decode($json, true);

	if (!is_array($data) || empty($data['memos'])) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = 'No data received';
		return;
	}

	$db->begin();

	foreach ($data['memos'] as $item) {
		$memo = new Memo($db);
		if ($memo->fetch((int) $item['id']) > 0) {
			// Security check for private notes
			if ($user->id != $memo->fk_user_creat && $memo->private) continue;

			$memo->updatePosition(
				$user,
				(int) $item['x'],
				(int) $item['y'],
				(int) $item['w'],
				(int) $item['h'],
				(int) $item['z']
			);
		}
	}

	$db->commit();
	$jsonResponse->result = 1;

	return true;
}

/**
 * Create a new memo linked to an element or context.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionCreate($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$element_id = GETPOST("element_id", "int");
	$element_type = GETPOST("element_type");


	if (empty($element_id) && !is_numeric($element_id) && !empty($element_type)) {
		$jsonResponse->msg = 'Need memo element_id';
		return false;
	}

	$memo = new Memo($db);
	$context_tab = GETPOST('context');
	if (!in_array($context_tab, $memo->getAvailableMemoContext())) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : Context unknown';
		return false;
	}

	// Handle color selection with preset fallback
	$colorPresets = Memo::getColorPreset();
	$firstColor = !empty($colorPresets) ? $colorPresets[0] : null;
	$memo->color = GETPOST("color");
	if (!Memo::checkColor($memo->color)) {
		$memo->color = $firstColor;
	}

	$memo->quick_note = GETPOST("note", "alphanohtml");
	$memo->fk_element = $element_id;
	$memo->element_type = $element_type;
	$jsonResponse->debug = GETPOST("color", $firstColor);
	$memo->context_tab = $context_tab;
	$memo->shared_on_element = GETPOST("shared_on_element", "int") ? 1 : 0;
	$memo->private = GETPOST("private", "int") ? 1 : 0;

	$memo->pos_x = GETPOST("x", "int");
	$memo->pos_y = GETPOST("y", "int");
	$memo->pos_w = GETPOST("w", "int");
	$memo->pos_h = GETPOST("h", "int");
	$memo->pos_z = GETPOST("z", "int");
	$memo->status = Memo::STATUS_VALIDATED;

	$resCre = $memo->create($user);
	if ($resCre <= 0 ) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = new stdClass();
		$jsonResponse->data->id = (int) $resCre;

		return true;
	}
}


/**
 * Set a memo status to archived.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionArchiveNote($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantArchiveThisPrivateNote');
		return false;
	}

	if ($memo->setArchived($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = null;
		return true;
	}
}

/**
 * Transform an existing note into a template (model) for future use.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionCreateModel($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantChangeThisPrivateNote');
		return false;
	}

	$element_type = GETPOST("element_type");
	if (empty($element_type)) {
		$memo->element_type = '';
	}

	$memo->status = Memo::STATUS_TPL;
	$memo->private_tpl = GETPOST("tpl_private", "int");
	$memo->name_tpl = GETPOST("tpl_name");
	$memo->fk_user_creat = $user->id;
	$memo->date_creation = dol_now();
	$memo->fk_user_modif = null;
	$memo->date_modification = null;
	$memo->fk_user_archived = null;
	$memo->date_archived = null;

	if ($memo->update($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = null;
		return true;
	}
}


/**
 * Delete a memo template.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionDeleteModel($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'delete')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need model Id';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($memo->status != Memo::STATUS_TPL) {
		$jsonResponse->msg = 'Not a model';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private_tpl) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantDeleteThisPrivateModel');
		return false;
	}

	if ($memo->delete($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = null;
		return true;
	}
}

/**
 * Update template ranking after a drag & drop operation.
 *
 * This is intentionally NOT implemented as a simple single UPDATE
 * on the moved row.
 *
 * The ranking system is based on a continuous and ordered sequence
 * (rank_tpl) shared by all compatible templates (same context_tab
 * and element_type). When one element moves, the relative position
 * of the entire set may change.
 *
 * For this reason we:
 *  - Reload the full compatible dataset from database
 *  - Rebuild the ordered list in memory
 *  - Reinsert the moved element at its new visual position
 *  - Recalculate a clean, continuous ranking sequence
 *  - Persist the full sequence in a transaction
 *
 * This guarantees:
 *  - No duplicated ranks
 *  - No gaps in ranking
 *  - Deterministic ordering
 *  - Proper handling of private templates
 *  - Consistency in concurrent environments
 *
 * A single UPDATE on the moved row would inevitably produce
 * rank collisions or inconsistent ordering over time.
 *
 * @param JsonResponse $jsonResponse The response object
 *
 * @return bool
 */
function quickMemoIntefaceActionUpdateModelRank($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$json = file_get_contents('php://input');
	$data = json_decode($json, true);

	if (!is_array($data) || empty($data['moved']['id']) || !isset($data['moved']['newPos'])) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = 'INVALID MOVED DATA';
		return false;
	}



	$movedId = (int) $data['moved']['id'];
	$newPos  = max(1, (int) $data['moved']['newPos']);

	$tmpMemo = new Memo($db);

	// 1 Retrieve the moved model
	$sql = 'SELECT rowid, element_type, context_tab
	        FROM '.MAIN_DB_PREFIX.$tmpMemo->table_element.'
	        WHERE rowid = '. (int) $movedId.'
	        AND status = '. (int) Memo::STATUS_TPL;

	$res = $db->query($sql);
	if (!$res || !$db->num_rows($res)) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = 'MODEL NOT FOUND';
		return false;
	}

	$movedModel = $db->fetch_object($res);

	// 2 Retrieve ALL compatible models
	$sqlAll = 'SELECT rowid
	           FROM '.MAIN_DB_PREFIX.$tmpMemo->table_element.'
	           WHERE status = '. (int) Memo::STATUS_TPL.'
	           AND element_type IN (\''.$db->escape($movedModel->element_type).'\', \'\')
	           ORDER BY rank_tpl DESC';

	//  AND context_tab = \''.$db->escape($movedModel->context_tab).'\'

	$resAll = $db->query($sqlAll);
	if (!$resAll) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $db->error();
		return false;
	}

	$orderedIds = [];
	while ($obj = $db->fetch_object($resAll)) {
		$orderedIds[] = (int) $obj->rowid;
	}

	// 3 Remove the moved ID
	$orderedIds = array_values(array_diff($orderedIds, [$movedId]));

	// 4 Calculate actual index (0 based)
	$newIndex = min(count($orderedIds), $newPos - 1);

	// 5 Insert at new position
	array_splice($orderedIds, $newIndex, 0, [$movedId]);

	// 6 Clean re-indexing
	$total = count($orderedIds);
	$currentRank = $total;

	$db->begin();

	foreach ($orderedIds as $id) {
		$sqlUp = 'UPDATE '.MAIN_DB_PREFIX.$tmpMemo->table_element.'
		          SET rank_tpl = '.(int) $currentRank.'
		          WHERE rowid = '.(int) $id.'
		          AND status = '. (int) Memo::STATUS_TPL.'
		          AND (private_tpl = 0 OR fk_user_creat = '.(int) $user->id.')';

		if (!$db->query($sqlUp)) {
			$db->rollback();
			$jsonResponse->result = 0;
			$jsonResponse->msg = $db->error();
			return false;
		}

		$currentRank--;
	}

	$db->commit();

	$jsonResponse->result = 1;
	$jsonResponse->data = null;
	return true;
}
/**
 * Permanently delete a note record from the database.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionDeleteNote($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($memo->status == Memo::STATUS_TPL) { // In case of models need check other stuff
		$jsonResponse->msg = 'Can\'t delete model';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantDeleteThisPrivateNote');
		return false;
	}

	if ($memo->delete($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = null;
		return true;
	}
}


/**
 * Update the text content of an existing note.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionUpdateNote($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	$memo->quick_note = GETPOST("note", "alphanohtml");
	$memo->tms = dol_now();
	$memo->fk_user_modif = $user->id;

	if ($memo->update($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = new stdClass();

		// return new memo
		$jsonResponse->data->memo = $memo->getJsMemo($user);
		return true;
	}
}

/**
 * Update the background color of a memo.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionUpdateColor($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$color = GETPOST("color");
	if (!Memo::checkColor($color)) {
		$jsonResponse->msg = 'Need valide Color';
		return false;
	}

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantChangeThisPrivateNote');
		return false;
	}

	$memo->color = $color;

	if (!$memo->update($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = new stdClass();

		// return new memo
		$jsonResponse->data->memo = $memo->getJsMemo($user);
		return true;
	}
}

/**
 * Toggle visibility: Shared globally on the context or restricted to current element.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionUpdateSharedOnElement($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$shared_on_element = GETPOST("shared_on_element", "int");

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantChangeThisPrivateNote');
		return false;
	}

	$memo->shared_on_element = $shared_on_element ? 1 : 0;

	if (!$memo->update($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = null;
		$jsonResponse->data = new stdClass();

		// return new memo
		$jsonResponse->data->memo = $memo->getJsMemo($user);
		return true;
	}
}


/**
 * Toggle private status (visible only to creator vs shared).
 * @param JsonResponse $jsonResponse The response object.
 * @return bool Returns false on failure, true on success.
 */
function quickMemoIntefaceActionUpdatePrivate($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'write')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$id = GETPOSTINT("id");

	if (empty($id) && !is_numeric($id)) {
		$jsonResponse->msg = 'Need memo Id';
		return false;
	}

	$private = GETPOST("private", "int");

	$memo = new Memo($db);
	if ($memo->fetch($id) <= 0) {
		$jsonResponse->msg = 'Memo not found';
		return false;
	}

	if ($user->id != $memo->fk_user_creat && $memo->private) {
		$jsonResponse->msg = $langs->trans('QuickMemoCantChangeThisPrivateNote');
		return false;
	}

	$memo->private = $private ? 1 : 0;

	if (!$memo->update($user) < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $langs->trans('UpdateError') . ' : ' . $memo->errorsToString();
		return false;
	} else {
		$jsonResponse->result = 1;
		$jsonResponse->data = new stdClass();

		// return new memo
		$jsonResponse->data->memo = $memo->getJsMemo($user);
		return true;
	}
}

/**
 * List available template models and standard color presets.
 * @param JsonResponse $jsonResponse The response object.
 * @return bool|void Returns false on failure.
 */
function quickMemoIntefaceActionListModels($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'read')) {
		$jsonResponse->msg = $langs->trans('NotEnoughRights');
		$jsonResponse->result = 0;
		return false;
	}

	$element_type = GETPOST('element_type', 'alpha');
	$context = GETPOST('context', 'alpha');

	$jsonResponse->data = new stdClass();
	$jsonResponse->data->modelTemplate = [];
	$jsonResponse->data->presetTemplate = [];

	// Get standard color list presets
	$colorList = Memo::getColorPreset();

	foreach ($colorList as $color) {
		$default = new stdClass();
		$default->name = '';
		$default->color = $color;
		$default->note = '';
		$default->pos_w = 0;
		$default->pos_h = 0;
		$default->pos_x = 0;
		$default->pos_y = 0;
		$default->shared_on_element = 0;
		$default->private = 0;
		$default->user_name =  $user->getFullName($langs);
		$default->date_creation =  dol_print_date(dol_now(), '', 'tzuserrel');
		$default->date_change =  '';
		$default->user_change_name = '';


		$jsonResponse->data->presetTemplate[] = $default;
	}

	// GET all memo templates (tpl)
	$memoStatic = new Memo($db);
	$sql = $memoStatic->getTemplateMemosQuery($element_type, $context);
	$resql = $db->query($sql);
	if (!$resql) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $db->lasterror();
		return;
	}

	while ($obj = $db->fetch_object($resql)) {
		// fallback user-specific position
		$jsonResponse->data->modelTemplate[]  = quickMemoInterfacePopulateMemoTplFromQueryObj($obj);
	}

	$jsonResponse->result = 1;
}

/**
 * List active notes for a specific object (element_id) or global context.
 * @param JsonResponse $jsonResponse The response object.
 * @return void
 */
function quickMemoIntefaceActionList($jsonResponse)
{
	global $user, $langs, $db;

	if (!$user->hasRight('quickmemo', 'memo', 'read')) {
		$jsonResponse->msg = $langs->trans('NotEnoughPermissions');
		$jsonResponse->result = 0;
		return;
	}

	$element_id = GETPOSTINT('element_id');
	$element_type = GETPOST('element_type', 'alpha');
	$context = GETPOST('context', 'alpha');
	$jsonResponse->data = new stdClass();
	$jsonResponse->data->memos = [];
	$jsonResponse->data->nbArchives = 0;

	if (empty($element_id) && !empty($element_type)) {
		$jsonResponse->msg = 'Need element_id ';
		$jsonResponse->result = 0;
		return;
	}

	$staticMemo = new Memo($db);

	// GET all active memos
	$sql = $staticMemo->getMemosQuery($element_type, $element_id, $context);
	$resql = $db->query($sql);
	if (!$resql) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = $db->lasterror();
		return;
	}


	$memos = [];
	while ($obj = $db->fetch_object($resql)) {
		// fallback user-specific position
		$memos[] = quickMemoInterfacePopulateMemoFromQueryObj($obj);
	}

	$jsonResponse->data->memos = $memos;

	// Count archived notes for display
	$nbArchives = $staticMemo->countArchivedMemoQuery($element_type, $element_id, $context);
	if ($nbArchives === false) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = 'Error count archive';
		return;
	}

	$jsonResponse->data->nbArchives = $nbArchives;

	$jsonResponse->result = 1;
}


/**
 * Helper to populate a JS-friendly memo object from a database record.
 * @param object $obj Database record object.
 * @return stdClass The populated JS-ready memo object.
 */
function quickMemoInterfacePopulateMemoFromQueryObj($obj)
{
	global $db, $langs;
	$obj->date_creation = $db->jdate($obj->date_creation);
	$obj->tms = $db->jdate($obj->tms);

	$memo = Memo::getJsMemoDefault();
	$memo->id = $obj->rowid;
	$memo->color = $obj->color;
	$memo->note = $obj->quick_note;
	// Use user-specific position if available, otherwise fallback to default
	$memo->pos_x = $obj->user_pos_x !== null ? (int) $obj->user_pos_x : (int) $obj->pos_x;
	$memo->pos_y = $obj->user_pos_y !== null ? (int) $obj->user_pos_y : (int) $obj->pos_y;
	$memo->pos_w = $obj->user_pos_w !== null ? (int) $obj->user_pos_w : (int) $obj->pos_w;
	$memo->pos_h = $obj->user_pos_h !== null ? (int) $obj->user_pos_h : (int) $obj->pos_h;
	$memo->pos_z = $obj->user_pos_z !== null ? (int) $obj->user_pos_z : (int) $obj->pos_z;

	$memo->shared_on_element = $obj->shared_on_element;
	$memo->private = (int) $obj->private;
	$memo->date_creation = dol_print_date($obj->date_creation, '%d/%m/%Y %H:%M', 'tzuserrel');
	$memo->date_change =  '';
	if (!empty($obj->tms) && ((int) $obj->date_creation !== (int) $obj->tms || (int) $obj->fk_user_modif > 0)) {
		$memo->date_change = dol_print_date($obj->tms, '%d/%m/%Y %H:%M', 'tzuserrel');
	}

	$memo->fk_user_creat = $obj->fk_user_creat;
	$memo->user_name = '';
	if ((int) $obj->fk_user_creat > 0) {
		$userCreate = new User($db);
		if ($userCreate->fetch((int) $obj->fk_user_creat) > 0) {
			$memo->user_name = $userCreate->getFullName($langs);
		}
	}

	$memo->fk_user_modif = $obj->fk_user_modif;
	$memo->user_change_name = '';
	if ((int) $obj->fk_user_modif > 0) {
		$userMod = new User($db);
		if ($userMod->fetch((int) $obj->fk_user_modif) > 0) {
			$memo->user_change_name = $userMod->getFullName($langs);
		}
	}

	return $memo;
}

/**
 * Helper to populate a JS-friendly template (model) object from a database record.
 * @param object $obj Database record object.
 * @return stdClass The populated JS-ready template object.
 */
function quickMemoInterfacePopulateMemoTplFromQueryObj($obj)
{
	global $db, $langs, $user;
	$obj->date_creation = $db->jdate($obj->date_creation);
	$obj->tms = $db->jdate($obj->tms);


	$memo = new stdClass();
	$memo->name = $obj->name_tpl;
	$memo->id = $obj->rowid;
	$memo->color = $obj->color;
	$memo->note = $obj->quick_note;
	$memo->pos_x = $obj->user_pos_x !== null ? (int) $obj->user_pos_x : (int) $obj->pos_x;
	$memo->pos_y = $obj->user_pos_y !== null ? (int) $obj->user_pos_y : (int) $obj->pos_y;
	$memo->pos_w = $obj->user_pos_w !== null ? (int) $obj->user_pos_w : (int) $obj->pos_w;
	$memo->pos_h = $obj->user_pos_h !== null ? (int) $obj->user_pos_h : (int) $obj->pos_h;
	$memo->pos_z = $obj->user_pos_z !== null ? (int) $obj->user_pos_z : (int) $obj->pos_z;
	$memo->rank_tpl = (int) $obj->rank_tpl;

	$memo->shared_on_element = $obj->shared_on_element;
	$memo->private = (int) $obj->private;
	$memo->date_creation = dol_print_date($obj->date_creation, '%d/%m/%Y %H:%M', 'tzuserrel');
	$memo->fk_user_creat = $user->id;
	$memo->user_name =  $user->getFullName($langs);
	$memo->date_change =  '';
	$memo->user_change_name = '';

	return $memo;
}
