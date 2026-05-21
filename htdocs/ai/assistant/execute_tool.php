<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
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
if (!defined('NOCSRFCHECK')) {		// TODO Enable the CSRF check
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp.class.php';

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}

global $db, $user;

top_httphead('application/json');


try {
	$raw = file_get_contents('php://input');
	$input = json_decode($raw, true);

	if (!$input || empty($input['tool'])) {
		throw new Exception("Invalid Request: No tool specified.");
	}

	// Initialize Handler
	$mcp = new McpHandler($db, $user);

	$result = $mcp->executeTool($input['tool'], $input['arguments'] ?? []);

	echo json_encode($result);
} catch (Throwable $e) {
	// Set HTTP response code to error (400 Bad Request)
	http_response_code(400);
	echo json_encode(["error" => $e->getMessage()]);
}
