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
 *	\brief      Authorization server endpoint for MCP clients.
 *
 *  Routes, on PATH_INFO (htdocs/ai/oauth.php/token and so on):
 *    /.well-known/oauth-protected-resource   resource metadata (RFC 9728)
 *    /.well-known/oauth-authorization-server server metadata (RFC 8414)
 *    /register                               client registration (RFC 7591)
 *    /authorize                              consent screen, needs a signed-in user
 *    /token                                  code exchange and refresh rotation
 *
 *  Only /authorize involves a person, and it is the only route that runs with
 *  a session. The rest are machine-to-machine and are declared NOLOGIN before
 *  main.inc.php is included, which is why the route is resolved first.
 */

// Resolve the route before main.inc.php: what the constants below have to say
// depends on which route was asked for.
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
	header('Cache-Control: no-store');
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
 * Send the user back to the client with the outcome of the authorization.
 *
 * RFC 9207: the response always names its issuer, so a client talking to
 * several servers cannot be tricked into accepting one server's response as
 * another's.
 *
 * @param  string               $redirecturi Redirect URI, already checked against the client's
 * @param  array<string,string> $params      Response parameters
 * @param  string               $issuer      Issuer identifier
 * @return never
 */
function mcpOauthRedirect($redirecturi, array $params, $issuer)
{
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
	case '/.well-known/openid-configuration':
		// Some clients only know the OpenID discovery path. The document is the
		// same one: this is not an OpenID provider, it just answers there too.
		mcpOauthJson($oauth->metadataAuthorizationServer());
		// no break

	case '/register':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			mcpOauthError('invalid_request', 405, 'Use POST to register a client.');
		}
		if (!getDolGlobalString('AI_MCP_OAUTH_DYNAMIC_REGISTRATION')) {
			mcpOauthError('access_denied', 403, 'Self-registration is disabled. An administrator registers clients on this Dolibarr.');
		}

		$body = json_decode(file_get_contents('php://input'), true);
		if (!is_array($body)) {
			mcpOauthError('invalid_client_metadata', 400, 'The body must be a JSON object.');
		}

		$registration = $oauth->registerClient($body);
		if ($registration === null) {
			mcpOauthError($oauth->error, 400);
		}

		mcpOauthJson($registration, 201);
		// no break

	case '/token':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			mcpOauthError('invalid_request', 405, 'Use POST to exchange a grant.');
		}

		// Credentials travel either in the Authorization header
		// (client_secret_basic) or in the body (client_secret_post). Both are
		// allowed; a public client sends neither and relies on PKCE.
		$clientid = GETPOST('client_id', 'alphanohtml');
		$clientsecret = GETPOST('client_secret', 'alphanohtml');
		if (!empty($_SERVER['PHP_AUTH_USER'])) {
			$clientid = $_SERVER['PHP_AUTH_USER'];
			$clientsecret = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		}

		$client = $oauth->getClient($clientid);
		if ($client === null || !$oauth->authenticateClient($client, (string) $clientsecret)) {
			// 401 with the challenge, as RFC 6749 asks when credentials were
			// presented in the Authorization header.
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
		// From here on main.inc.php has done its work: $user is a signed-in
		// Dolibarr user, or the browser was already sent to the login page.
		break;

	default:
		mcpOauthError('invalid_request', 404, 'Unknown route.');
}


/*
 * /authorize — the only route a person ever sees
 */

$responsetype = GETPOST('response_type', 'alphanohtml');
$clientid = GETPOST('client_id', 'alphanohtml');
$redirecturi = GETPOST('redirect_uri', 'alphanohtml');
$state = GETPOST('state', 'alphanohtml');
$codechallenge = GETPOST('code_challenge', 'alphanohtml');
$codechallengemethod = GETPOST('code_challenge_method', 'alphanohtml');
$scope = GETPOST('scope', 'alphanohtml');
$resourceparam = GETPOST('resource', 'alphanohtml');

$client = $oauth->getClient($clientid);
if ($client === null) {
	// Nothing may be redirected before the client and its URI are known: that
	// check is what stops this endpoint being used as an open redirector.
	mcpOauthError('invalid_client', 400, 'Unknown client_id.');
}
if (!$oauth->isRegisteredRedirectUri($client, $redirecturi)) {
	mcpOauthError('invalid_request', 400, 'redirect_uri does not match a registered URI for this client.');
}

// Past this point the caller is known, so failures go back to it as OAuth asks
// rather than being shown to the user as a Dolibarr error.
if ($responsetype !== 'code') {
	mcpOauthRedirect($redirecturi, array('error' => 'unsupported_response_type', 'state' => $state), $issuer);
}
if ($codechallenge === '' || $codechallengemethod !== 'S256') {
	mcpOauthRedirect($redirecturi, array('error' => 'invalid_request', 'error_description' => 'PKCE with S256 is required', 'state' => $state), $issuer);
}

// The same right every other AI entry point checks. A user who may not talk to
// the assistant cannot grant a client the ability to do it for them.
if (!$user->hasRight('ai', 'assistant', 'use')) {
	mcpOauthRedirect($redirecturi, array('error' => 'access_denied', 'error_description' => 'This Dolibarr user is not allowed to use the AI assistant', 'state' => $state), $issuer);
}

$action = GETPOST('action', 'aZ09');

if ($action === 'grant' || $action === 'deny') {
	// main.inc.php has already checked the CSRF token on this POST.
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

$clientname = !empty($client->client_name) ? $client->client_name : $clientid;

llxHeader('', $langs->trans('AiMcpOauthConsentTitle'), '', '', 0, 0, '', '', '', 'mod-ai page-oauth');

print load_fiche_titre($langs->trans('AiMcpOauthConsentTitle'), '', 'ai');

print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF'].'/authorize').'">';
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
	print '<input type="hidden" name="'.$name.'" value="'.dol_escape_htmltag($value).'">';
}

print '<div class="center">';
print '<p>'.$langs->trans('AiMcpOauthConsentQuestion', dol_escape_htmltag($clientname), dol_escape_htmltag($user->login)).'</p>';
print '<p class="opacitymedium">'.$langs->trans('AiMcpOauthConsentScope').'</p>';
print '<p class="opacitymedium"><small>'.$langs->trans('AiMcpOauthConsentRedirect').' '.dol_escape_htmltag($redirecturi).'</small></p>';
print '<br>';
print '<button type="submit" class="button" name="action" value="grant">'.$langs->trans('AiMcpOauthAllow').'</button>';
print ' &nbsp; ';
print '<button type="submit" class="button button-cancel" name="action" value="deny">'.$langs->trans('AiMcpOauthDeny').'</button>';
print '</div>';

print '</form>';

llxFooter();
$db->close();
