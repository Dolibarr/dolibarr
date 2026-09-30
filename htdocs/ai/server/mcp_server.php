<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
 * \brief   File of the MCP Server service
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
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcpauth.class.php';

while (ob_get_level()) {
	ob_end_clean();
}

// Security check (a test on api_key is also done later)
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

// Request headers, used by the transport-header validation further down.
$headers = function_exists('getallheaders') ? getallheaders() : [];
$headers = array_change_key_case($headers, CASE_LOWER);

$mcpAuth = new McpAuth($db);

if ($mcpAuth->authenticate() < 0) {
	if ($mcpAuth->httpcode == 401) {
		// RFC 6750 section 3: a rejected Bearer request must say what it wanted.
		header('WWW-Authenticate: ' . $mcpAuth->getWwwAuthenticateHeader());
	}

	http_response_code($mcpAuth->httpcode);
	echo json_encode([
		"jsonrpc" => "2.0",
		"error" => ["code" => -32000, "message" => $mcpAuth->error]
	]);
	exit;
}


// Determine the user the request runs as. An individual API key names its own
// owner, already loaded and checked by McpAuth; the shared server key falls
// back to the AI_MCP_USER_ID service user.
$serviceUser = $mcpAuth->user;

if ($serviceUser === null) {
	$userId = getDolGlobalInt('AI_MCP_USER_ID');

	if ($userId <= 0) {
		http_response_code(503);
		echo json_encode([
			"jsonrpc" => "2.0",
			"error" => ["code" => -32000, "message" => "MCP Server Misconfigured: authenticate with a user API key, or set AI_MCP_USER_ID for the shared server key"]
		]);
		exit;
	}

	$serviceUser = new User($db);
	if ($serviceUser->fetch($userId) <= 0) {
		http_response_code(500);
		echo json_encode([
			"jsonrpc" => "2.0",
			"error" => ["code" => -32000, "message" => "MCP Service User not found"]
		]);
		exit;
	}
	$serviceUser->loadRights();
}

// Promote the user to the global $user so MCP tools that legitimately rely on
// the `global $user` pattern (Dolibarr core convention) see an authenticated
// user. Without this, there is no PHP web session in HTTP MCP context and any
// tool reading `global $user` would treat the request as unauthenticated even
// though authentication succeeded above.
global $user;
$user = $serviceUser;

// Load the AI request log helper so we can persist tools/call invocations to
// llx_ai_request_log (same table the AI Assistant web UI logs to). This gives
// administrators a single place to audit external MCP client activity.
require_once DOL_DOCUMENT_ROOT . '/ai/lib/ai.lib.php';

/**
 * Persist an MCP tools/call invocation to the llx_ai_request_log table.
 * Mirrors what parse_intent.php does for the web AI Assistant.
 * Only tools/call is logged -- lifecycle methods (initialize, ping,
 * notifications/initialized, tools/list, ...) are skipped to avoid noise.
 *
 * @param array<string,mixed>	$req		The JSON-RPC request
 * @param ?mixed				$resp		The JSON-RPC response (null for notifications)
 * @param float					$tStart		microtime(true) captured before processing
 * @param string				$rawInput	Raw request body
 * @return void								No return value, only logs the request
 */
function mcp_log_request(array $req, $resp, float $tStart, string $rawInput): void
{
	global $db, $serviceUser;

	$method = isset($req['method']) ? (string) $req['method'] : '';
	if ($method !== 'tools/call' || !function_exists('ai_log_request')) {
		return;
	}

	$params   = isset($req['params']) && is_array($req['params']) ? $req['params'] : [];
	$toolName = isset($params['name']) ? (string) $params['name'] : '';
	$toolArgs = isset($params['arguments']) ? $params['arguments'] : [];

	$argsJson = is_string($toolArgs) ? $toolArgs : (string) json_encode($toolArgs);
	$query    = '[MCP] ' . $toolName . ' ' . dol_substr($argsJson, 0, 1000);

	$responseShape = ['tool' => $toolName, 'arguments' => $toolArgs];

	$status   = 'Success';
	$errorMsg = '';
	if (is_array($resp) && isset($resp['error'])) {
		$status   = 'Error';
		$errorMsg = is_array($resp['error']) && isset($resp['error']['message'])
			? (string) $resp['error']['message']
			: (string) json_encode($resp['error']);
	} elseif (is_array($resp) && isset($resp['result']['isError']) && $resp['result']['isError']) {
		$status   = 'Error';
		$errorMsg = is_array($resp['result']['content'] ?? null) ? (string) json_encode($resp['result']['content']) : '';
	}

	$rawResStr = is_string($resp) ? $resp : (string) json_encode($resp);

	ai_log_request(
		$db,
		$serviceUser,
		$query,
		$responseShape,
		'mcp',
		microtime(true) - $tStart,
		1.0,
		$status,
		$errorMsg,
		$rawInput,
		$rawResStr
	);
}

// Request handling
try {
	$tStart = microtime(true);

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

		// Transport headers describe the HTTP request, not individual batch
		// items: if a Mcp-* / MCP-Protocol-Version header disagrees with ANY
		// item, the header lies about the request and the WHOLE batch fails
		// with a single error and HTTP 400 (review finding on #40356). This
		// also keeps the rate-limiting contract simple: a proxy trusting the
		// headers never lets a mismatching batch through as 200.
		foreach ($request as $precheck) {
			if (!is_array($precheck)) {
				continue;
			}
			$headerError = $server->validateTransportHeaders($headers, $precheck);
			if ($headerError !== null || $server->getHttpStatus() !== 200) {
				http_response_code($server->getHttpStatus());
				if ($headerError === null) {
					// The offending item was a notification (no id): the error
					// response was suppressed per JSON-RPC, but the transport
					// status must still tell the truth.
					$headerError = ["jsonrpc" => "2.0", "id" => null, "error" => ["code" => -32020, "message" => "Transport header does not match a batch item"]];
				}
				echo json_encode($headerError);
				exit;
			}
		}

		// Answer to all MCP requests following the MCP protocol
		foreach ($request as $req) {
			if (!is_array($req)) {
				continue;
			}

			$reqStart = microtime(true);
			$res = $server->handleRequest($req);

			if ($res !== null) {
				$responses[] = $res;
			}

			// Log each tools/call separately so the admin log viewer shows them individually.
			mcp_log_request($req, $res, $reqStart, (string) json_encode($req));
		}

		echo json_encode($responses);
	} else {
		// Single request
		if (!is_array($request)) {
			throw new Exception("Invalid request format");
		}

		$response = $server->validateTransportHeaders($headers, $request);
		if ($response === null && $server->getHttpStatus() === 200) {
			$response = $server->handleRequest($request);
		}

		if ($server->getHttpStatus() !== 200) {
			http_response_code($server->getHttpStatus());
			if ($response === null) {
				// The offending request was a notification (no id): the error
				// response body was suppressed per JSON-RPC, but a 400 must
				// not go out empty - same handling as the batch path.
				$response = ["jsonrpc" => "2.0", "id" => null, "error" => ["code" => -32020, "message" => "Transport header does not match the request"]];
			}
		}

		if ($response !== null) {
			echo json_encode($response);
		}

		// Log this tools/call to llx_ai_request_log (no-op unless AI_LOG_REQUESTS is enabled
		// and the method is tools/call).
		mcp_log_request($request, $response, $tStart, $rawInput);
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
