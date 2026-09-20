<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
 * Copyright (C) 2026	Nick Fragoulis
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
 * \file    htdocs/ai/ajax/chat_history.php
 * \ingroup ai
 * \brief   Conversation persistence for the AI chat (list/load/save/pin/delete).
 *
 * Storage is strictly per user and separate from the model context: reopening
 * a conversation restores the bubbles, but only the messages the user PINNED
 * are ever sent back to the model (see the context pins in ai_assistant.js).
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', 1);
}
// The payload is read from the raw php://input body, so the CSRF token cannot be checked by
// main.inc.php. It is checked explicitly below by aiCheckCsrfToken().
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';

if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}

global $db, $user, $conf;

if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}

aiCheckCsrfToken('ai/ajax/chat_history.php');

top_httphead('application/json');

/**
 * Ownership guard: the conversation must exist and belong to the current user.
 *
 * @param DoliDB $db     Database handler
 * @param User   $user   Current user
 * @param int    $convid Conversation rowid
 * @return bool          True when the user owns it
 */
function aiChatOwnsConversation($db, $user, int $convid): bool
{
	$sql = "SELECT rowid FROM ".$db->prefix()."ai_chat_conversation WHERE rowid = ".((int) $convid)." AND fk_user = ".((int) $user->id)." AND entity = ".((int) getEntity('ai'));
	$resql = $db->query($sql);
	return $resql && $db->num_rows($resql) > 0;
}

try {
	$input = json_decode((string) file_get_contents('php://input'), true);
	if (!is_array($input) || empty($input['action'])) {
		throw new Exception('Invalid request');
	}
	$action = (string) $input['action'];
	$out = array();

	if ($action === 'list') {
		// Opportunistic retention purge: no cron to register, the cost is one
		// indexed DELETE per listing. 0 disables the purge (keep forever).
		$days = getDolGlobalInt('AI_CHAT_HISTORY_RETENTION_DAYS', 90);
		if ($days > 0) {
			$limitdate = $db->idate(dol_now() - $days * 86400);
			$sqlold = "SELECT rowid FROM ".$db->prefix()."ai_chat_conversation WHERE fk_user = ".((int) $user->id)." AND tms < '".$db->escape($limitdate)."'";
			$resold = $db->query($sqlold);
			while ($resold && ($objold = $db->fetch_object($resold))) {
				$db->query("DELETE FROM ".$db->prefix()."ai_chat_message WHERE fk_conversation = ".((int) $objold->rowid));
				$db->query("DELETE FROM ".$db->prefix()."ai_chat_conversation WHERE rowid = ".((int) $objold->rowid));
			}
		}

		$sql = "SELECT c.rowid, c.title, c.tms, COUNT(m.rowid) as nbmsg";
		$sql .= " FROM ".$db->prefix()."ai_chat_conversation as c";
		$sql .= " LEFT JOIN ".$db->prefix()."ai_chat_message as m ON m.fk_conversation = c.rowid";
		$sql .= " WHERE c.fk_user = ".((int) $user->id)." AND c.entity = ".((int) getEntity('ai'));
		$sql .= " GROUP BY c.rowid, c.title, c.tms ORDER BY c.tms DESC";
		$sql .= $db->plimit(30);
		$resql = $db->query($sql);
		$out['conversations'] = array();
		while ($resql && ($obj = $db->fetch_object($resql))) {
			$out['conversations'][] = array('id' => (int) $obj->rowid, 'title' => (string) $obj->title, 'date' => dol_print_date($db->jdate($obj->tms), 'dayhour'), 'nb' => (int) $obj->nbmsg);
		}
	} elseif ($action === 'load') {
		$convid = (int) ($input['id'] ?? 0);
		if (!aiChatOwnsConversation($db, $user, $convid)) {
			throw new Exception('Conversation not found');
		}
		$sql = "SELECT rowid, role, content_raw, content_html, tool_name, pinned FROM ".$db->prefix()."ai_chat_message";
		$sql .= " WHERE fk_conversation = ".((int) $convid)." ORDER BY position, rowid";
		$resql = $db->query($sql);
		$out['id'] = $convid;
		$out['messages'] = array();
		while ($resql && ($obj = $db->fetch_object($resql))) {
			$out['messages'][] = array(
				'id' => (int) $obj->rowid,
				'role' => (string) $obj->role,
				'raw' => (string) $obj->content_raw,
				'html' => (string) $obj->content_html,
				'tool' => (string) $obj->tool_name,
				'pinned' => (int) $obj->pinned,
			);
		}
	} elseif ($action === 'save') {
		$convid = (int) ($input['id'] ?? 0);
		$role = (($input['role'] ?? '') === 'assistant') ? 'assistant' : 'user';
		$raw = (string) ($input['raw'] ?? '');
		$html = (string) ($input['html'] ?? '');
		$toolname = dol_trunc((string) ($input['tool'] ?? ''), 250, 'right', 'UTF-8', 1);
		$pinned = empty($input['pinned']) ? 0 : 1;
		if ($raw === '' && $html === '') {
			throw new Exception('Empty message');
		}

		// The stored HTML is re-injected on reopen: sanitize it at SAVE time with
		// the core whitelist, so nothing executable can ever be persisted. Action
		// buttons (download, open record...) are stripped on purpose: they carry
		// one-shot client state that would be dead on reopen anyway.
		// ($cleanalsosomestyles=1, keep class attributes=0 - the chat tables and
		// badges style through classes -, $cleanalsojavascript=1, no iframe,
		// explicit tag whitelist, links allowed, no script/style/php)
		$html = dol_string_onlythesehtmltags($html, 1, 0, 1, 0, array('a', 'b', 'strong', 'em', 'i', 'u', 'br', 'p', 'span', 'div', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'code', 'pre', 'hr'), 1, 0, 0, 0);

		if ($convid > 0 && !aiChatOwnsConversation($db, $user, $convid)) {
			throw new Exception('Conversation not found');
		}
		if ($convid <= 0) {
			$title = dol_trunc(($role === 'user' && $raw !== '') ? $raw : 'Conversation', 60, 'right', 'UTF-8', 1);
			$sql = "INSERT INTO ".$db->prefix()."ai_chat_conversation (entity, fk_user, title, date_creation)";
			$sql .= " VALUES (".((int) $conf->entity).", ".((int) $user->id).", '".$db->escape($title)."', '".$db->idate(dol_now())."')";
			if (!$db->query($sql)) {
				throw new Exception($db->lasterror());
			}
			$convid = (int) $db->last_insert_id($db->prefix()."ai_chat_conversation");
		}

		$sql = "SELECT MAX(position) as maxpos FROM ".$db->prefix()."ai_chat_message WHERE fk_conversation = ".((int) $convid);
		$resql = $db->query($sql);
		$objpos = $resql ? $db->fetch_object($resql) : null;
		$position = $objpos ? ((int) $objpos->maxpos + 1) : 1;

		$sql = "INSERT INTO ".$db->prefix()."ai_chat_message (fk_conversation, role, content_raw, content_html, tool_name, pinned, position, datec)";
		$sql .= " VALUES (".((int) $convid).", '".$db->escape($role)."', '".$db->escape($raw)."', '".$db->escape($html)."', '".$db->escape($toolname)."', ".((int) $pinned).", ".((int) $position).", '".$db->idate(dol_now())."')";
		if (!$db->query($sql)) {
			throw new Exception($db->lasterror());
		}
		// Touch the conversation so the list sorts by real activity
		$db->query("UPDATE ".$db->prefix()."ai_chat_conversation SET tms = tms WHERE rowid = ".((int) $convid));
		$out['id'] = $convid;
		$out['message_id'] = (int) $db->last_insert_id($db->prefix()."ai_chat_message");
	} elseif ($action === 'pin') {
		$msgid = (int) ($input['message_id'] ?? 0);
		$pinned = empty($input['pinned']) ? 0 : 1;
		// Ownership travels through the conversation
		$sql = "UPDATE ".$db->prefix()."ai_chat_message as m";
		$sql .= " INNER JOIN ".$db->prefix()."ai_chat_conversation as c ON c.rowid = m.fk_conversation AND c.fk_user = ".((int) $user->id);
		$sql .= " SET m.pinned = ".((int) $pinned)." WHERE m.rowid = ".((int) $msgid);
		if (!$db->query($sql)) {
			throw new Exception($db->lasterror());
		}
		$out['ok'] = 1;
	} elseif ($action === 'delete') {
		$convid = (int) ($input['id'] ?? 0);
		if (!aiChatOwnsConversation($db, $user, $convid)) {
			throw new Exception('Conversation not found');
		}
		$db->query("DELETE FROM ".$db->prefix()."ai_chat_message WHERE fk_conversation = ".((int) $convid));
		$db->query("DELETE FROM ".$db->prefix()."ai_chat_conversation WHERE rowid = ".((int) $convid));
		$out['ok'] = 1;
	} else {
		throw new Exception('Unknown action');
	}

	echo json_encode($out);
} catch (Throwable $e) {
	http_response_code(400);
	echo json_encode(array('error' => $e->getMessage()));
}
