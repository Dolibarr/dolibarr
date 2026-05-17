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
 * or see https://www.gnu.org/
 */

/**
 * \file    htdocs/ai/server/mcp_server.php
 * \ingroup ai
 * \brief   File of class to manage MCP Server
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
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
define('NOLOGIN', 1);

require '../../main.inc.php';
/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp_protocol.class.php';

while (ob_get_level()) {
	ob_end_clean();
}

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_MCP_ENABLED')) {
	http_response_code(503);
	echo json_encode([
		"jsonrpc" => "2.0",
		"error" => ["code" => -32000, "message" => "MCP Server Disabled"]
	]);
	exit;
}


/*
 * View
 */

// Headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$headers = function_exists('getallheaders') ? getallheaders() : [];
$headers = array_change_key_case($headers, CASE_LOWER);

$authHeader   = $headers['authorization'] ?? '';
$apiKeyHeader = $headers['x-api-key'] ?? '';
// Fallback: also accept the key in a query string parameter (?api_key=XXX or ?key=XXX).
// Required for MCP clients that don't support custom auth headers in their connector UI
// (e.g. Claude Desktop "Custom Connectors" in beta only exposes OAuth fields).
// SECURITY NOTE: query-string keys appear in webserver access logs and possibly in Referer
// headers. Header-based auth (X-API-Key / Authorization) remains preferred and is tried first.
// Administrators relying on the fallback should restrict access at the webserver level
// and/or rotate AI_MCP_API_KEY regularly.
$apiKeyQuery  = $_GET['api_key'] ?? $_GET['key'] ?? '';
$storedKey    = getDolGlobalString('AI_MCP_API_KEY');

$valid = false;

if (!empty($storedKey)) {
	// X-API-Key header (preferred)
	if (!empty($apiKeyHeader)) {
		$valid = hash_equals($storedKey, $apiKeyHeader);
	}

	// Authorization: Bearer <token>
	if (!$valid && !empty($authHeader)) {
		$matches = array();
		if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
			$token = trim($matches[1]);
			$valid = hash_equals($storedKey, $token);
		}
	}

	// Query-string fallback (last resort for header-less clients)
	if (!$valid && !empty($apiKeyQuery)) {
		$valid = hash_equals($storedKey, $apiKeyQuery);
	}
}

if (!$valid) {
	dol_syslog('[MCP Server] Unauthorized access attempt. IP=' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), LOG_WARNING);

	http_response_code(401);
	echo json_encode([
		"jsonrpc" => "2.0",
		"error" => ["code" => -32000, "message" => "Unauthorized"]
	]);
	exit;
}

// Load service user
$userId = getDolGlobalInt('AI_MCP_USER_ID');
$serviceUser = new User($db);

if ($userId > 0) {
	$result = $serviceUser->fetch($userId);

	if ($result > 0) {
		$serviceUser->loadRights();
	} else {
		http_response_code(500);
		echo json_encode([
			"jsonrpc" => "2.0",
			"error" => ["code" => -32000, "message" => "MCP Service User not found"]
		]);
		exit;
	}
} else {
	http_response_code(503);
	echo json_encode([
		"jsonrpc" => "2.0",
		"error" => ["code" => -32000, "message" => "MCP Server Misconfigured: AI_MCP_USER_ID not set"]
	]);
	exit;
}

// Request handling
try {
	// Basic payload size limit
	$rawInput = file_get_contents('php://input');
	if ($rawInput === false || strlen($rawInput) > 1024 * 1024) {
		throw new Exception("Invalid or too large request");
	}

	$request = json_decode($rawInput, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception("Parse Error");
	}

	$server = new MCPServer($db, $conf, $serviceUser);

	// Batch request handling
	if (is_array($request) && array_keys($request) === range(0, count($request) - 1)) {
		// Limit batch size
		if (count($request) > 20) {
			http_response_code(413);
			echo json_encode([
				"jsonrpc" => "2.0",
				"error" => ["code" => -32000, "message" => "Batch too large"]
			]);
			exit;
		}

		$responses = [];

		foreach ($request as $req) {
			if (!is_array($req)) {
				continue;
			}

			$res = $server->handleRequest($req);

			if ($res !== null) {
				$responses[] = $res;
			}
		}

		echo json_encode($responses);
	} else {
		// Single request
		if (!is_array($request)) {
			throw new Exception("Invalid request format");
		}

		$response = $server->handleRequest($request);

		if ($response !== null) {
			echo json_encode($response);
		}
	}
} catch (Exception $e) {
	dol_syslog(
		'[MCP Server] Fatal error: ' . $e->getMessage(),
		LOG_ERR
	);

	echo json_encode([
		"jsonrpc" => "2.0",
		"id" => null,
		"error" => [
			"code" => -32700,
			"message" => "Parse error"
		]
	]);
}
