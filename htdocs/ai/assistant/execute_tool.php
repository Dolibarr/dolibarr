<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026	Anthony Damhet			<a.damhet@progiseize.fr>
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
 * \file    htdocs/ai/assistant/execute_tool.php
 * \ingroup ai
 * \brief   API endpoint for executing tools via the MCP (Model Context Protocol)
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
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/lib/ai.lib.php';

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}

global $db, $user, $conf;

// Per-user gate: same right as the assistant page and parse_intent.php
if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}

// This endpoint creates, updates and deletes documents, so it must not be reachable from
// another site. Must stay after the login is done by main.inc.php (the session is needed).
aiCheckCsrfToken('ai/assistant/execute_tool.php');

top_httphead('application/json');


try {
	$raw = file_get_contents('php://input');
	$input = json_decode($raw, true);

	if (!$input || empty($input['tool'])) {
		throw new Exception("Invalid Request: No tool specified.");
	}

	// Initialize Handler with the private assistant context so that the correct
	// allow-list (AI_ASSISTANT_ALLOWED_TOOLS) is enforced on both schema and execution.
	$mcp = new McpHandler($db, $user, $conf, McpHandler::CTX_ASSISTANT);

	$result = $mcp->executeTool($input['tool'], $input['arguments'] ?? []);

	echo json_encode($result);
} catch (Throwable $e) {
	// Set HTTP response code to error (400 Bad Request)
	http_response_code(400);
	echo json_encode(["error" => $e->getMessage()]);
}
