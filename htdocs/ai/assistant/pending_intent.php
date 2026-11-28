<?php
/* Copyright (C) 2026	Nicolas Vidal	<nicolas.vidal@atm-consulting.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    htdocs/ai/assistant/pending_intent.php
 * \ingroup ai
 * \brief   Give the browser the answer of an assistant question asked from a page it has left since
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
// The payload is read from the raw php://input body: the CSRF token is checked below by aiCheckCsrfToken().
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';

/**
 * @var User $user
 */

// JSON even when refused: the chat reads an HTML answer as an expired session.
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED') || !$user->hasRight('ai', 'assistant', 'use')) {
	http_response_code(403);
	top_httphead('application/json');
	echo json_encode(array('status' => 'unknown'));
	exit;
}

aiCheckCsrfToken('ai/assistant/pending_intent.php');

// The browser polls while parse_intent.php may still run: never hold the session lock.
session_write_close();

$data = json_decode((string) file_get_contents('php://input'), true);
$requestKey = (is_array($data) && isset($data['request_key']) && is_string($data['request_key'])) ? $data['request_key'] : '';

top_httphead('application/json');

echo json_encode(aiPendingIntentTake((int) $user->id, $requestKey));
