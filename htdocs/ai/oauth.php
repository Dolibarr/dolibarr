<?php
/* Copyright (C) 2026	Morgan Demoulin			<morgan.demoulin@gmail.com>
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
 *	\file       htdocs/ai/oauth.php
 *	\ingroup    ai
 *	\brief      Authorization server endpoint for MCP clients, routed on PATH_INFO
 *              (/.well-known/..., /register, /authorize, /token). Only /authorize needs a signed-in user.
 */

// Route resolved before main.inc.php: the constants below depend on it
$mcp_route = '';
if (!empty($_SERVER['PATH_INFO'])) {
	$mcp_route = (string) $_SERVER['PATH_INFO'];
}

if ($mcp_route !== '/authorize') {
	if (!defined('NOLOGIN')) {
		define('NOLOGIN', '1');
	}
	if (!defined('NOCSRFCHECK')) {
		define('NOCSRFCHECK', '1');
	}
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', '1');
	}
	if (!defined('NOSESSION')) {
		define('NOSESSION', '1');
	}
}
if ($mcp_route === '/authorize') {
	// CSRF token always checked on the consent, as in user/perms.php
	define('CSRFCHECK_WITH_TOKEN', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

require '../main.inc.php';
/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcpoauth.class.php';

$langs->loadLangs(array('admin', 'other'));


/**
 * Emit a JSON document and stop.
 *
 * @param  array<string, mixed> $payload  Document to serve
 * @param  int                  $httpcode HTTP status
 * @return never
 */
function mcpOauthJson(array $payload, $httpcode = 200)
{
	http_response_code($httpcode);
	header('Content-Type: application/json');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: no-store');
	header('Pragma: no-cache');
	print json_encode($payload);
	exit;
}

/**
 * Emit an OAuth error document and stop (RFC 6749 section 5.2).
 *
 * @param  string $error       Error code in OAuth vocabulary
 * @param  int    $httpcode    HTTP status
 * @param  string $description Optional human-readable detail
 * @return never
 */
function mcpOauthError($error, $httpcode = 400, $description = '')
{
	$payload = array('error' => $error);
	if ($description !== '') {
		$payload['error_description'] = $description;
	}

	mcpOauthJson($payload, $httpcode);
}

/**
 * Send the user back to the client with the outcome of the authorization, naming the issuer (RFC 9207).
 *
 * @param  string               $redirecturi Redirect URI, already checked against the client's
 * @param  array<string,string> $params      Response parameters
 * @param  string               $issuer      Issuer identifier
 * @return never
 */
function mcpOauthRedirect($redirecturi, array $params, $issuer)
{
	// RFC 6749 section 4.1.2: state comes back only when it was sent.
	if (isset($params['state']) && $params['state'] === '') {
		unset($params['state']);
	}
	$params['iss'] = $issuer;
	$separator = (strpos($redirecturi, '?') === false) ? '?' : '&';

	header('Location: '.$redirecturi.$separator.http_build_query($params));
	exit;
}


if (!isModEnabled('ai') || !getDolGlobalString('AI_MCP_ENABLED')) {
	mcpOauthError('temporarily_unavailable', 503, 'The MCP server is not enabled on this Dolibarr.');
}

$issuer = DOL_MAIN_URL_ROOT.'/ai/oauth.php';
$resource = DOL_MAIN_URL_ROOT.'/ai/server/mcp_server.php';
$oauth = new McpOauth($db, $issuer, $resource);


/*
 * Actions
 */

switch ($mcp_route) {
	case '/.well-known/oauth-protected-resource':
		mcpOauthJson($oauth->metadataProtectedResource());
		// no break

	case '/.well-known/oauth-authorization-server':
		mcpOauthJson($oauth->metadataAuthorizationServer());
		// no break

	case '/.well-known/openid-configuration':
		// OpenID fields only here, where the client validates the document as OpenID Connect.
		// On the RFC 8414 document above they would make some connectors refuse it.
		mcpOauthJson(array_merge($oauth->metadataAuthorizationServer(), array(
			'jwks_uri' => $issuer.'/jwks',
			'subject_types_supported' => array('public'),
			'id_token_signing_alg_values_supported' => array('RS256'),
		)));
		// no break

	case '/jwks':
		// Empty: nothing is signed here
		mcpOauthJson(array('keys' => array()));
		// no break

	case '/register':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Allow: POST');
			mcpOauthError('invalid_request', 405, 'Use POST to register a client.');
		}
		if (!getDolGlobalString('AI_MCP_OAUTH_DYNAMIC_REGISTRATION')) {
			mcpOauthError('access_denied', 403, 'Self-registration is disabled. An administrator registers clients on this Dolibarr.');
		}

		// /register takes no credential: rate limit per address
		if ($oauth->countRecentRegistrations(getUserRemoteIP(1)) >= McpOauth::REGISTRATIONS_PER_HOUR) {
			dol_syslog('[MCP OAuth] Registration rate limit reached for '.getUserRemoteIP(1), LOG_WARNING);
			mcpOauthError('temporarily_unavailable', 429, 'Too many registrations from this address. Try again later.');
		}

		$body = json_decode(file_get_contents('php://input'), true);
		if (!is_array($body)) {
			mcpOauthError('invalid_client_metadata', 400, 'The body must be a JSON object.');
		}

		$registration = $oauth->registerClient($body);
		if ($registration === null) {
			mcpOauthError($oauth->error, 400);
		}

		$oauth->purgeExpired();

		mcpOauthJson($registration, 201);
		// no break

	case '/token':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Allow: POST');
			mcpOauthError('invalid_request', 405, 'Use POST to exchange a grant.');
		}

		// client_secret_basic or client_secret_post; a public client sends neither (PKCE)
		$clientid = GETPOST('client_id', 'alphanohtml');
		$clientsecret = GETPOST('client_secret', 'alphanohtml');
		if (!empty($_SERVER['PHP_AUTH_USER'])) {
			$clientid = $_SERVER['PHP_AUTH_USER'];
			$clientsecret = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		} else {
			// PHP_AUTH_* is not filled under PHP-FPM or CGI: read the header itself
			$requestheaders = array_change_key_case(dolGetRequestHeaders(), CASE_LOWER);
			$authheader = '';
			if (!empty($requestheaders['authorization'])) {
				$authheader = (string) $requestheaders['authorization'];
			} elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
				$authheader = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
			}
			$reg = array();
			if ($authheader !== '' && preg_match('/^Basic\s+(\S+)$/i', $authheader, $reg)) {
				$decoded = base64_decode($reg[1], true);
				if ($decoded !== false && strpos($decoded, ':') !== false) {
					list($basicid, $basicsecret) = explode(':', $decoded, 2);
					// RFC 6749 section 2.3.1 form-urlencodes both halves.
					$clientid = urldecode($basicid);
					$clientsecret = urldecode($basicsecret);
				}
			}
		}

		$client = $oauth->getClient($clientid);
		if ($client === null || !$oauth->authenticateClient($client, (string) $clientsecret)) {
			// 401 with a challenge (RFC 6749 section 5.2)
			header('WWW-Authenticate: Basic realm="Dolibarr MCP"');
			mcpOauthError('invalid_client', 401);
		}

		$granttype = GETPOST('grant_type', 'alphanohtml');
		$tokens = null;

		if ($granttype === 'authorization_code') {
			$tokens = $oauth->exchangeAuthorizationCode(
				$client,
				GETPOST('code', 'alphanohtml'),
				GETPOST('redirect_uri', 'alphanohtml'),
				GETPOST('code_verifier', 'alphanohtml')
			);
		} elseif ($granttype === 'refresh_token') {
			$tokens = $oauth->refreshTokens($client, GETPOST('refresh_token', 'alphanohtml'));
		} else {
			mcpOauthError('unsupported_grant_type', 400);
		}

		if ($tokens === null) {
			mcpOauthError($oauth->error, 400);
		}

		$oauth->purgeExpired();

		mcpOauthJson($tokens);
		// no break

	case '/authorize':
		// $user is signed in here, or main.inc.php sent the browser to the login page
		break;

	default:
		mcpOauthError('invalid_request', 404, 'Unknown route.');
}


/*
 * /authorize — the only route a person ever sees
 */

// A stale token empties $_POST: say the form expired rather than "unknown client"
if (GETPOST('errorcode', 'aZ09') === 'InvalidToken') {
	mcpOauthError('invalid_request', 400, 'The consent form expired. Start the authorization again from your client.');
}

$responsetype = GETPOST('response_type', 'alphanohtml');
// Opaque values the client compares on return: no __XXX__ substitution ($noreplace = 1)
$clientid = GETPOST('client_id', 'alphanohtml', 0, null, null, 1);
$redirecturi = GETPOST('redirect_uri', 'alphanohtml', 0, null, null, 1);
$state = GETPOST('state', 'alphanohtml', 0, null, null, 1);
$codechallenge = dol_trunc(GETPOST('code_challenge', 'alphanohtml', 0, null, null, 1), 128, 'right', 'UTF-8', 1);
$codechallengemethod = GETPOST('code_challenge_method', 'alphanohtml');
$scope = dol_trunc(GETPOST('scope', 'alphanohtml'), 255, 'right', 'UTF-8', 1);
$resourceparam = GETPOST('resource', 'alphanohtml', 0, null, null, 1);

// Checked before resolving the client, which may fetch a URL the caller chose
if (!$user->hasRight('ai', 'assistant', 'use')) {
	mcpOauthError('access_denied', 403, 'This Dolibarr user is not allowed to use the AI assistant.');
}

$client = $oauth->getClient($clientid, true);
if ($client === null) {
	// No redirect before the client and its URI are known (no open redirector)
	mcpOauthError($oauth->error !== '' ? $oauth->error : 'invalid_client', 400, 'No usable client for this client_id.');
}
if (!$oauth->isRegisteredRedirectUri($client, $redirecturi)) {
	mcpOauthError('invalid_request', 400, 'redirect_uri does not match a registered URI for this client.');
}

// From here, errors go back to the client as OAuth asks
if ($responsetype !== 'code') {
	mcpOauthRedirect($redirecturi, array('error' => 'unsupported_response_type', 'state' => $state), $issuer);
}
if ($codechallenge === '' || $codechallengemethod !== 'S256') {
	mcpOauthRedirect($redirecturi, array('error' => 'invalid_request', 'error_description' => 'PKCE with S256 is required', 'state' => $state), $issuer);
}

// Refused before the consent screen: a token for another audience would not work (RFC 8707)
if ($resourceparam !== '' && !$oauth->isOwnResource($resourceparam)) {
	mcpOauthRedirect($redirecturi, array('error' => 'invalid_target', 'error_description' => 'This authorization server issues tokens for its own MCP endpoint only', 'state' => $state), $issuer);
}

// POST only: following a link must never give a consent
$action = (string) GETPOST('action', 'aZ09', 2);

if ($action === 'grant' || $action === 'deny') {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		mcpOauthError('invalid_request', 405, 'Use POST to answer the consent screen.');
	}

	// main.inc.php checked the CSRF token on this POST.
	if ($action === 'deny') {
		mcpOauthRedirect($redirecturi, array('error' => 'access_denied', 'state' => $state), $issuer);
	}

	$code = $oauth->createAuthorizationCode($client, (int) $user->id, $redirecturi, $codechallenge, $scope, $resourceparam);
	if ($code === null) {
		mcpOauthRedirect($redirecturi, array('error' => $oauth->error, 'state' => $state), $issuer);
	}

	dol_syslog('[MCP OAuth] User '.$user->login.' granted client '.$clientid, LOG_INFO);

	mcpOauthRedirect($redirecturi, array('code' => $code, 'state' => $state), $issuer);
}


/*
 * View
 */

// client_name is chosen by the client: the host of the redirect URI is shown too
$clientname = !empty($client->client_name) ? $client->client_name : $clientid;
$clienthost = (string) parse_url($redirecturi, PHP_URL_HOST);
if ($clienthost === '') {
	$clienthost = (string) parse_url($redirecturi, PHP_URL_SCHEME);
}

// On the loopback, say it is a local application rather than "127.0.0.1"
$isloopback = in_array(dol_strtolower($clienthost), array('127.0.0.1', '[::1]', '::1', 'localhost'), true);
$hostline = $isloopback
	? $langs->trans('AiMcpOauthConsentLocalApp')
	: $langs->trans('AiMcpOauthConsentHost', dolPrintLabel($clienthost));

llxHeader('', $langs->trans('AiMcpOauthConsentTitle'), '', '', 0, 0, '', '', '', 'mod-ai page-oauth');

print load_fiche_titre($langs->trans('AiMcpOauthConsentTitle'), '', 'ai');

// SCRIPT_NAME, not PHP_SELF, which already carries the path info under mod_php
print '<form method="POST" action="'.dolPrintHTMLForAttributeUrl($_SERVER['SCRIPT_NAME'].'/authorize').'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
foreach (array(
	'response_type' => $responsetype,
	'client_id' => $clientid,
	'redirect_uri' => $redirecturi,
	'state' => $state,
	'code_challenge' => $codechallenge,
	'code_challenge_method' => $codechallengemethod,
	'scope' => $scope,
	'resource' => $resourceparam,
) as $name => $value) {
	print '<input type="hidden" name="'.$name.'" value="'.dolPrintLabelForAttribute($value).'">';
}

print '<div class="center">';
print '<p>'.$langs->trans('AiMcpOauthConsentQuestion', dolPrintLabel($clientname), dolPrintLabel($user->login)).'</p>';
print '<p>'.$hostline.'</p>';
print '<p class="opacitymedium">'.$langs->trans('AiMcpOauthConsentScope').'</p>';
print '<p class="opacitymedium"><small>'.$langs->trans('AiMcpOauthConsentExpiry').'</small></p>';
print '<p class="opacitymedium"><small>'.$langs->trans('AiMcpOauthConsentRedirect').' '.dolPrintLabel($redirecturi).'</small></p>';
print '<br>';
print '<button type="submit" class="button" name="action" value="grant">'.$langs->trans('AiMcpOauthAllow').'</button>';
print ' &nbsp; ';
print '<button type="submit" class="button button-cancel" name="action" value="deny">'.$langs->trans('AiMcpOauthDeny').'</button>';
print '</div>';

print '</form>';

llxFooter();
$db->close();
