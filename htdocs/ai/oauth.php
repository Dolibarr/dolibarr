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
if ($mcp_route === '/authorize') {
	// Forces the CSRF token check on this route whatever
	// MAIN_SECURITY_CSRF_WITH_TOKEN is set to, and even when the install sets
	// $dolibarr_nocsrfcheck: a consent minted by a page the user merely
	// visited is an account handed over silently. Same guard as user/perms.php
	// and admin/modules.php.
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
		// A client that asks here is asking for OpenID Connect, and validates
		// what it gets as such: without these three it refuses the document.
		// They are added only on this route. On the RFC 8414 document above
		// they would be a claim to be an OpenID provider, which this is not —
		// opaque access tokens, never an id_token, nothing signed, no openid
		// scope — and the claude.ai and ChatGPT connectors both refuse a
		// document that makes it. Same server, two callers, two questions.
		mcpOauthJson(array_merge($oauth->metadataAuthorizationServer(), array(
			'jwks_uri' => $issuer.'/jwks',
			'subject_types_supported' => array('public'),
			'id_token_signing_alg_values_supported' => array('RS256'),
		)));
		// no break

	case '/jwks':
		// Advertised by the OpenID document above, and empty on purpose:
		// nothing here is signed, so there is no key to publish.
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

		// /register takes no credential by design, so the only thing standing
		// between it and an unbounded table is this.
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

		// Credentials travel either in the Authorization header
		// (client_secret_basic) or in the body (client_secret_post). Both are
		// allowed; a public client sends neither and relies on PKCE.
		$clientid = GETPOST('client_id', 'alphanohtml');
		$clientsecret = GETPOST('client_secret', 'alphanohtml');
		if (!empty($_SERVER['PHP_AUTH_USER'])) {
			$clientid = $_SERVER['PHP_AUTH_USER'];
			$clientsecret = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		} else {
			// PHP_AUTH_* is never filled under PHP-FPM or CGI, which is where
			// the .htaccess next to this file republishes the header. Without
			// reading it, client_secret_basic is advertised and dead on
			// exactly the setups that rule exists for.
			$authheader = '';
			if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
				$authheader = (string) $_SERVER['HTTP_AUTHORIZATION'];
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

// main.inc.php empties $_POST on a stale token and lets the script continue,
// so without this the next checks would read nothing and answer "unknown
// client" — sending the administrator after a client problem that is really
// an expired form.
if (GETPOST('errorcode', 'aZ09') === 'InvalidToken') {
	mcpOauthError('invalid_request', 400, 'The consent form expired. Start the authorization again from your client.');
}

$responsetype = GETPOST('response_type', 'alphanohtml');
$clientid = GETPOST('client_id', 'alphanohtml');
$redirecturi = GETPOST('redirect_uri', 'alphanohtml');
// RFC 6749 section 4.1.2 returns state to the client byte for byte, so it is
// read raw: alphanohtml drops quotes and angle brackets, which mangles the
// JSON or signed blob a client may legitimately put there. It is never
// interpreted here, only echoed, and every echo escapes it.
$state = isset($_GET['state']) ? (string) $_GET['state'] : (isset($_POST['state']) ? (string) $_POST['state'] : '');
$codechallenge = dol_trunc((string) GETPOST('code_challenge', 'alphanohtml'), 128, 'right', 'UTF-8', 1);
$codechallengemethod = GETPOST('code_challenge_method', 'alphanohtml');
$scope = dol_trunc((string) GETPOST('scope', 'alphanohtml'), 255, 'right', 'UTF-8', 1);
$resourceparam = isset($_GET['resource']) ? (string) $_GET['resource'] : (isset($_POST['resource']) ? (string) $_POST['resource'] : '');

// Before resolving the client: resolving a metadata document means an
// outbound request on a URL the caller chose, and a user who may not use the
// assistant has no business making this server issue one.
if (!$user->hasRight('ai', 'assistant', 'use')) {
	mcpOauthError('access_denied', 403, 'This Dolibarr user is not allowed to use the AI assistant.');
}

$client = $oauth->getClient($clientid, true);
if ($client === null) {
	// Nothing may be redirected before the client and its URI are known: that
	// check is what stops this endpoint being used as an open redirector.
	// The reason comes from the resolver when it has one, so a client whose
	// metadata document is unusable is not told its identifier is unknown.
	mcpOauthError($oauth->error !== '' ? $oauth->error : 'invalid_client', 400, 'No usable client for this client_id.');
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

// Checked before the consent screen rather than after it: a token for another
// audience will be refused anyway (RFC 8707), and asking someone to approve
// something that cannot work is worse than refusing it early.
if ($resourceparam !== '' && !$oauth->isOwnResource($resourceparam)) {
	mcpOauthRedirect($redirecturi, array('error' => 'invalid_target', 'error_description' => 'This authorization server issues tokens for its own MCP endpoint only', 'state' => $state), $issuer);
}

// Read from POST only. GETPOST would also read the query string, and a
// consent given by following a link is a consent the user did not give:
// main.inc.php only treats a GET action as sensitive at the highest
// MAIN_SECURITY_CSRF_WITH_TOKEN setting, which an administrator may lower.
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

// A client picks its own client_name, so it is the one thing on this page an
// attacker controls freely. The host the user will be sent back to is not,
// and it is what tells them who they are really authorising.
$clientname = !empty($client->client_name) ? $client->client_name : $clientid;
$clienthost = (string) parse_url($redirecturi, PHP_URL_HOST);
if ($clienthost === '') {
	$clienthost = (string) parse_url($redirecturi, PHP_URL_SCHEME);
}

// A native client is called back on the loopback, so naming the host would
// only show "127.0.0.1", which tells the user nothing. What matters to them
// there is that the answer never leaves their machine.
$isloopback = in_array(strtolower($clienthost), array('127.0.0.1', '[::1]', '::1', 'localhost'), true);
$hostline = $isloopback
	? $langs->trans('AiMcpOauthConsentLocalApp')
	: $langs->trans('AiMcpOauthConsentHost', $clienthost);

llxHeader('', $langs->trans('AiMcpOauthConsentTitle'), '', '', 0, 0, '', '', '', 'mod-ai page-oauth');

print load_fiche_titre($langs->trans('AiMcpOauthConsentTitle'), '', 'ai');

// SCRIPT_NAME, not PHP_SELF: under Apache with mod_php the latter already
// carries the path info, so appending /authorize to it posts the form to
// /ai/oauth.php/authorize/authorize and the route is not found.
print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['SCRIPT_NAME'].'/authorize').'">';
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
	// htmlspecialchars, not dol_escape_htmltag: the latter strips anything that
	// looks like a tag and turns a newline into a literal \n, which would send
	// back a state the client did not issue and fail its own CSRF check.
	print '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'">';
}

print '<div class="center">';
print '<p>'.$langs->trans('AiMcpOauthConsentQuestion', $clientname, $user->login).'</p>';
print '<p>'.$hostline.'</p>';
print '<p class="opacitymedium">'.$langs->trans('AiMcpOauthConsentScope').'</p>';
print '<p class="opacitymedium"><small>'.$langs->trans('AiMcpOauthConsentExpiry').'</small></p>';
print '<p class="opacitymedium"><small>'.$langs->trans('AiMcpOauthConsentRedirect').' '.dol_escape_htmltag($redirecturi).'</small></p>';
print '<br>';
print '<button type="submit" class="button" name="action" value="grant">'.$langs->trans('AiMcpOauthAllow').'</button>';
print ' &nbsp; ';
print '<button type="submit" class="button button-cancel" name="action" value="deny">'.$langs->trans('AiMcpOauthDeny').'</button>';
print '</div>';

print '</form>';

llxFooter();
$db->close();
