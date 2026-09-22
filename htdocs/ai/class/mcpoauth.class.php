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
 * \file htdocs/ai/class/mcpoauth.class.php
 * \ingroup ai
 * \brief Authorization server backing the MCP endpoint.
 * \see https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization
 */


/**
 * McpOauth Class
 *
 * OAuth 2.1 authorization server for MCP clients: authorization code with
 * PKCE S256, refresh rotation, and the two metadata documents clients use to
 * discover all of it. Access tokens are opaque; McpAuth exchanges them for the
 * Dolibarr user who granted the consent, so an MCP request authenticated by
 * token runs with exactly the permissions of that user.
 *
 * Nothing here writes to the HTTP response. The endpoint (htdocs/ai/oauth.php)
 * decides what to answer, this class decides what is true.
 *
 * Token values are never stored: a row holds the sha256 of the value, so a
 * database dump grants nothing. Authorization codes are one-shot and revoked
 * the moment they are presented, before the request can even fail.
 */
class McpOauth
{
	/**
	 * Prefix of every value this server mints. The letter that follows says
	 * what it is, which lets an operator recognise a leaked value on sight
	 * and lets McpAuth tell an access token from a user API key without a
	 * database round trip.
	 */
	const TOKEN_PREFIX = 'dolmcp_';

	/**
	 * Lifetime of an access token, in seconds.
	 */
	const ACCESS_TOKEN_TTL = 3600;

	/**
	 * Lifetime of a refresh token, in seconds.
	 */
	const REFRESH_TOKEN_TTL = 2592000;

	/**
	 * How long a client that never obtained a token is kept, in seconds.
	 */
	const UNUSED_CLIENT_TTL = 86400;

	/**
	 * How long a fetched metadata document is trusted before being read again,
	 * in seconds.
	 */
	const METADATA_DOCUMENT_TTL = 86400;

	/**
	 * Most registrations one address may make in an hour.
	 */
	const REGISTRATIONS_PER_HOUR = 20;

	/**
	 * Lifetime of an authorization code, in seconds. Short on purpose: it is
	 * exchanged immediately by a client that already holds the verifier.
	 */
	const CODE_TTL = 120;

	/**
	 * @var DoliDB Database handler
	 */
	private $db;

	/**
	 * @var string Absolute URL of this authorization server (no trailing slash)
	 */
	private $issuer;

	/**
	 * @var string Absolute URL of the protected resource (the MCP endpoint)
	 */
	private $resource;

	/**
	 * @var string Error code from the last failed call, in OAuth vocabulary
	 *             (invalid_grant, invalid_client...) so the endpoint can
	 *             return it verbatim
	 */
	public $error = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db       Database handler
	 * @param string $issuer   Absolute URL of the authorization server
	 * @param string $resource Absolute URL of the MCP endpoint
	 */
	public function __construct($db, $issuer, $resource)
	{
		$this->db = $db;
		$this->issuer = rtrim($issuer, '/');
		$this->resource = $resource;
	}

	/**
	 * Authorization server metadata (RFC 8414).
	 *
	 * @return array<string, mixed> The document to serve
	 */
	public function metadataAuthorizationServer()
	{
		return array(
			'issuer' => $this->issuer,
			'authorization_endpoint' => $this->issuer.'/authorize',
			'token_endpoint' => $this->issuer.'/token',
			'registration_endpoint' => $this->issuer.'/register',
			'response_types_supported' => array('code'),
			'grant_types_supported' => array('authorization_code', 'refresh_token'),
			// S256 only. RFC 7636 still allows 'plain'; it protects nothing on
			// a public client, which is the only kind that needs PKCE most.
			'code_challenge_methods_supported' => array('S256'),
			'token_endpoint_auth_methods_supported' => array('none', 'client_secret_basic', 'client_secret_post'),
			'scopes_supported' => array('dolibarr'),
			// RFC 9207: the authorization response names its issuer, so a client
			// talking to several servers cannot be made to mix up two responses.
			'authorization_response_iss_parameter_supported' => true,
			// A client_id may be the HTTPS URL of a metadata document the server
			// fetches, instead of a registration (spec 2026-07-28, which
			// deprecates RFC 7591 in favour of this).
			'client_id_metadata_document_supported' => true,
			// RFC 8414 puts the metadata of an issuer that has a path at
			// /.well-known/oauth-authorization-server/<path>, which is at the
			// domain root and not something Dolibarr can serve. What is
			// reachable is the OpenID form, <issuer>/.well-known/openid-
			// configuration, and clients built on the MCP TypeScript SDK
			// validate whatever answers there as OpenID Connect. These three
			// are what that validation requires. They are honest rather than
			// aspirational: the key set is empty because nothing is signed,
			// and the openid scope is deliberately not offered - this is an
			// authorization server, not an OpenID provider.
			'jwks_uri' => $this->issuer.'/jwks',
			'subject_types_supported' => array('public'),
			'id_token_signing_alg_values_supported' => array('RS256'),
		);
	}

	/**
	 * Protected resource metadata (RFC 9728).
	 *
	 * This is the document the WWW-Authenticate challenge points at, and the
	 * first thing a client reads when it meets a 401.
	 *
	 * @return array<string, mixed> The document to serve
	 */
	public function metadataProtectedResource()
	{
		return array(
			'resource' => $this->resource,
			'authorization_servers' => array($this->issuer),
			'scopes_supported' => array('dolibarr'),
			'bearer_methods_supported' => array('header'),
			'resource_name' => 'Dolibarr MCP server',
		);
	}

	/**
	 * Register a client (RFC 7591).
	 *
	 * Kept behind AI_MCP_OAUTH_DYNAMIC_REGISTRATION because self-registration
	 * means anyone who can reach the endpoint can create a client row. That is
	 * how MCP connectors are expected to arrive, and it grants nothing on its
	 * own — a client is only ever useful once a user has signed in and
	 * consented — but it is an administrator's call, not ours.
	 *
	 * @param  array<string, mixed>      $metadata Client metadata sent by the caller
	 * @return array<string, mixed>|null           Registration response, or null on error
	 */
	public function registerClient(array $metadata)
	{
		global $conf;

		$uris = array();
		if (!empty($metadata['redirect_uris']) && is_array($metadata['redirect_uris'])) {
			foreach ($metadata['redirect_uris'] as $uri) {
				$uri = trim((string) $uri);
				if ($this->isAcceptableRedirectUri($uri)) {
					$uris[] = $uri;
				}
			}
		}
		if (empty($uris)) {
			$this->error = 'invalid_redirect_uri';
			return null;
		}

		$authmethod = isset($metadata['token_endpoint_auth_method']) ? (string) $metadata['token_endpoint_auth_method'] : 'none';
		if (!in_array($authmethod, array('none', 'client_secret_basic', 'client_secret_post'), true)) {
			$this->error = 'invalid_client_metadata';
			return null;
		}

		$clientid = self::TOKEN_PREFIX.'c'.bin2hex(random_bytes(16));
		$secret = ($authmethod === 'none') ? '' : bin2hex(random_bytes(32));
		$name = isset($metadata['client_name']) ? dol_trunc((string) $metadata['client_name'], 255, 'right', 'UTF-8', 1) : '';

		$sql = "INSERT INTO ".$this->db->prefix()."ai_oauth_client";
		$sql .= " (entity, client_id, client_secret_hash, client_name, redirect_uris, token_endpoint_auth_method, registered_from, datec)";
		$sql .= " VALUES (".((int) $conf->entity);
		$sql .= ", '".$this->db->escape($clientid)."'";
		$sql .= ", ".($secret === '' ? "NULL" : "'".$this->db->escape(hash('sha256', $secret))."'");
		$sql .= ", '".$this->db->escape($name)."'";
		$sql .= ", '".$this->db->escape(implode("\n", $uris))."'";
		$sql .= ", '".$this->db->escape($authmethod)."'";
		$sql .= ", '".$this->db->escape(dol_trunc((string) getUserRemoteIP(), 64, 'right', 'UTF-8', 1))."'";
		$sql .= ", '".$this->db->idate(dol_now())."')";

		if (!$this->db->query($sql)) {
			dol_syslog('[MCP OAuth] Client registration failed: '.$this->db->lasterror(), LOG_ERR);
			$this->error = 'server_error';
			return null;
		}

		dol_syslog('[MCP OAuth] Registered client '.$clientid.' ('.$name.')', LOG_INFO);

		$response = array(
			'client_id' => $clientid,
			'client_id_issued_at' => dol_now(),
			'redirect_uris' => $uris,
			'token_endpoint_auth_method' => $authmethod,
			'grant_types' => array('authorization_code', 'refresh_token'),
			'response_types' => array('code'),
		);
		if ($name !== '') {
			$response['client_name'] = $name;
		}
		if ($secret !== '') {
			// Returned once and never again: only its hash is kept.
			$response['client_secret'] = $secret;
		}

		return $response;
	}

	/**
	 * Load a client by its public identifier.
	 *
	 * @param  string      $clientid Client identifier
	 * @return object|null           Client row, or null when unknown
	 */
	public function getClient($clientid)
	{
		if (empty($clientid)) {
			return null;
		}

		// A client_id that is an HTTPS URL is a metadata document to fetch, not
		// a registration to look up. The row it produces is keyed on that URL,
		// so a client that comes back tomorrow reuses it instead of creating
		// another one.
		if (self::isMetadataDocumentUrl($clientid)) {
			return $this->resolveMetadataDocument($clientid);
		}

		return $this->fetchClientRow($clientid);
	}

	/**
	 * Tell a metadata document URL from a registered client identifier.
	 *
	 * @param  string $clientid Value presented as client_id
	 * @return bool             True when it is an HTTPS URL to fetch
	 */
	public static function isMetadataDocumentUrl($clientid)
	{
		return stripos($clientid, 'https://') === 0;
	}

	/**
	 * Resolve a client_id that is the URL of a metadata document.
	 *
	 * The document is fetched through getURLContent(), which is what keeps this
	 * from being a way to make Dolibarr probe its own network: that function
	 * refuses private, loopback and link-local addresses, and keeps refusing
	 * them after a redirect.
	 *
	 * The result is stored, keyed on the URL, so the fetch happens once a day
	 * per client rather than once per sign-in, and so a client that has been
	 * used keeps working if its document briefly goes missing.
	 *
	 * @param  string      $url Absolute HTTPS URL of the document
	 * @return object|null      Client row, or null when the document is unusable
	 */
	private function resolveMetadataDocument($url)
	{
		global $conf;

		$known = $this->fetchClientRow($url);
		if ($known !== null && $this->db->jdate($known->tms) > (dol_now() - self::METADATA_DOCUMENT_TTL)) {
			return $known;
		}

		$document = $this->readMetadataDocument($url);
		if ($document === null) {
			// Keep serving a client that already worked: a document that is
			// momentarily unreachable should not sign everybody out.
			return $known;
		}

		$uris = array();
		if (!empty($document['redirect_uris']) && is_array($document['redirect_uris'])) {
			foreach ($document['redirect_uris'] as $uri) {
				$uri = trim((string) $uri);
				if ($this->isAcceptableRedirectUri($uri)) {
					$uris[] = $uri;
				}
			}
		}
		if (empty($uris)) {
			$this->error = 'invalid_client_metadata';
			return null;
		}

		$name = isset($document['client_name']) ? dol_trunc((string) $document['client_name'], 255, 'right', 'UTF-8', 1) : '';

		if ($known !== null) {
			$sql = "UPDATE ".$this->db->prefix()."ai_oauth_client";
			$sql .= " SET client_name = '".$this->db->escape($name)."'";
			$sql .= ", redirect_uris = '".$this->db->escape(implode("\n", $uris))."'";
			$sql .= ", tms = '".$this->db->idate(dol_now())."'";
			$sql .= " WHERE rowid = ".((int) $known->rowid);
		} else {
			$sql = "INSERT INTO ".$this->db->prefix()."ai_oauth_client";
			$sql .= " (entity, client_id, client_secret_hash, client_name, redirect_uris, token_endpoint_auth_method, datec)";
			$sql .= " VALUES (".((int) $conf->entity);
			$sql .= ", '".$this->db->escape($url)."'";
			$sql .= ", NULL";
			$sql .= ", '".$this->db->escape($name)."'";
			$sql .= ", '".$this->db->escape(implode("\n", $uris))."'";
			$sql .= ", 'none'";
			$sql .= ", '".$this->db->idate(dol_now())."')";
		}

		if (!$this->db->query($sql)) {
			dol_syslog('[MCP OAuth] Could not store the metadata document of '.$url.': '.$this->db->lasterror(), LOG_ERR);
			$this->error = 'server_error';
			return null;
		}

		return $this->fetchClientRow($url);
	}

	/**
	 * Fetch and parse a client metadata document.
	 *
	 * @param  string                   $url Absolute HTTPS URL
	 * @return array<string, mixed>|null     Decoded document, or null
	 */
	private function readMetadataDocument($url)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$response = getURLContent($url, 'GET', '', 1, array('Accept: application/json'), array('https'), 0);

		if (empty($response['content']) || (!empty($response['curl_error_no']) && $response['curl_error_no'] !== 0)) {
			dol_syslog('[MCP OAuth] Metadata document unreachable: '.$url.' '.(isset($response['curl_error_msg']) ? $response['curl_error_msg'] : ''), LOG_NOTICE);
			return null;
		}
		if (strlen($response['content']) > 65536) {
			dol_syslog('[MCP OAuth] Metadata document too large: '.$url, LOG_WARNING);
			return null;
		}

		$document = json_decode($response['content'], true);
		if (!is_array($document)) {
			dol_syslog('[MCP OAuth] Metadata document is not a JSON object: '.$url, LOG_WARNING);
			return null;
		}

		// The document has to claim the URL it was found at. Without this check
		// a document copied to another address would authorise its original.
		if (empty($document['client_id']) || !hash_equals((string) $document['client_id'], $url)) {
			dol_syslog('[MCP OAuth] Metadata document does not identify itself as '.$url, LOG_WARNING);
			return null;
		}

		return $document;
	}

	/**
	 * Read one client row by its identifier.
	 *
	 * @param  string      $clientid Client identifier
	 * @return object|null           Row, or null
	 */
	private function fetchClientRow($clientid)
	{
		global $conf;

		$sql = "SELECT rowid, client_id, client_secret_hash, client_name, redirect_uris, token_endpoint_auth_method, tms";
		$sql .= " FROM ".$this->db->prefix()."ai_oauth_client";
		$sql .= " WHERE client_id = '".$this->db->escape($clientid)."'";
		$sql .= " AND entity = ".((int) $conf->entity);

		$resql = $this->db->query($sql);
		if (!$resql || $this->db->num_rows($resql) != 1) {
			return null;
		}

		return $this->db->fetch_object($resql);
	}

	/**
	 * Check the credentials a client presented at the token endpoint.
	 *
	 * A public client (auth method 'none') has no secret to check: PKCE is what
	 * protects it, and that is verified on the code itself.
	 *
	 * @param  object $client Client row from getClient()
	 * @param  string $secret Secret presented, empty for a public client
	 * @return bool           True when the client may proceed
	 */
	public function authenticateClient($client, $secret)
	{
		if (empty($client->client_secret_hash)) {
			return true;
		}
		if ($secret === '') {
			return false;
		}

		return hash_equals((string) $client->client_secret_hash, hash('sha256', $secret));
	}

	/**
	 * Check a redirect URI against the ones the client registered.
	 *
	 * Compared in full and byte for byte, as OAuth 2.1 requires: prefix or
	 * wildcard matching is how authorization codes end up on someone else's
	 * server.
	 *
	 * @param  object $client      Client row
	 * @param  string $redirecturi URI presented in the request
	 * @return bool                True when it is one of the registered URIs
	 */
	public function isRegisteredRedirectUri($client, $redirecturi)
	{
		if ($redirecturi === '') {
			return false;
		}
		foreach (explode("\n", (string) $client->redirect_uris) as $known) {
			$known = trim($known);
			if ($known === '') {
				continue;
			}
			if (hash_equals($known, $redirecturi)) {
				return true;
			}
			if ($this->isSameLoopbackUri($known, $redirecturi)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compare two loopback redirect URIs, ignoring the port.
	 *
	 * RFC 8252 section 7.3 requires the port to be free at request time: a
	 * native client asks the operating system for a spare port when it starts
	 * listening, so it cannot know it when it publishes its metadata. Codex
	 * registers http://127.0.0.1/callback and then asks for
	 * http://127.0.0.1:1455/callback.
	 *
	 * Only the port is allowed to differ. Scheme, host and path must still
	 * match exactly, and this applies to loopback only — relaxing a port on a
	 * public host would let a code be delivered to another service there.
	 *
	 * @param  string $known       Registered redirect URI
	 * @param  string $redirecturi URI presented in the request
	 * @return bool                True when they differ only by their port
	 */
	private function isSameLoopbackUri($known, $redirecturi)
	{
		$a = parse_url($known);
		$b = parse_url($redirecturi);

		if (!is_array($a) || !is_array($b)) {
			return false;
		}

		$loopback = array('127.0.0.1', '[::1]', '::1', 'localhost');
		$hosta = isset($a['host']) ? strtolower($a['host']) : '';
		$hostb = isset($b['host']) ? strtolower($b['host']) : '';

		if (!in_array($hosta, $loopback, true) || $hosta !== $hostb) {
			return false;
		}
		if (empty($a['scheme']) || strtolower($a['scheme']) !== 'http') {
			return false;
		}
		if (empty($b['scheme']) || strtolower($b['scheme']) !== 'http') {
			return false;
		}

		$patha = isset($a['path']) ? $a['path'] : '';
		$pathb = isset($b['path']) ? $b['path'] : '';
		$querya = isset($a['query']) ? $a['query'] : '';
		$queryb = isset($b['query']) ? $b['query'] : '';

		return hash_equals($patha, $pathb) && hash_equals($querya, $queryb);
	}

	/**
	 * Mint an authorization code for a consent the user has just given.
	 *
	 * @param  object      $client        Client row
	 * @param  int         $userid        User who consented
	 * @param  string      $redirecturi   Redirect URI the code is bound to
	 * @param  string      $codechallenge PKCE S256 challenge
	 * @param  string      $scope         Granted scope
	 * @param  string      $resource      Audience requested (RFC 8707)
	 * @return string|null                The code, or null on error
	 */
	public function createAuthorizationCode($client, $userid, $redirecturi, $codechallenge, $scope, $resource)
	{
		if (empty($codechallenge)) {
			// PKCE is not optional here. A code that can be exchanged without a
			// verifier is a code an interceptor can exchange.
			$this->error = 'invalid_request';
			return null;
		}

		$code = self::TOKEN_PREFIX.'g'.bin2hex(random_bytes(32));

		if (!$this->insertToken('code', $code, (int) $client->rowid, (int) $userid, $scope, $resource, $codechallenge, $redirecturi, self::CODE_TTL)) {
			$this->error = 'server_error';
			return null;
		}

		return $code;
	}

	/**
	 * Exchange an authorization code for tokens.
	 *
	 * @param  object                   $client       Client row, already authenticated
	 * @param  string                   $code         Authorization code
	 * @param  string                   $redirecturi  Redirect URI, must match the one bound to the code
	 * @param  string                   $codeverifier PKCE verifier
	 * @return array<string, mixed>|null              Token response, or null on error
	 */
	public function exchangeAuthorizationCode($client, $code, $redirecturi, $codeverifier)
	{
		$row = $this->getValidToken('code', $code);
		if (!$row || (int) $row->fk_client !== (int) $client->rowid) {
			$this->error = 'invalid_grant';
			return null;
		}

		// One-shot, revoked before anything else can go wrong. A code that
		// survives a failed exchange is a code that can be replayed.
		$this->revokeToken((int) $row->rowid);

		if (!empty($row->redirect_uri) && !hash_equals((string) $row->redirect_uri, $redirecturi)) {
			$this->error = 'invalid_grant';
			return null;
		}

		if (empty($row->code_challenge) || $codeverifier === '') {
			$this->error = 'invalid_grant';
			return null;
		}
		$computed = rtrim(strtr(base64_encode(hash('sha256', $codeverifier, true)), '+/', '-_'), '=');
		if (!hash_equals((string) $row->code_challenge, $computed)) {
			$this->error = 'invalid_grant';
			return null;
		}

		return $this->issueTokenPair((int) $client->rowid, (int) $row->fk_user, (string) $row->scope, (string) $row->resource);
	}

	/**
	 * Exchange a refresh token for a new pair.
	 *
	 * The presented refresh token is revoked whatever happens next: rotation is
	 * what makes a stolen refresh token detectable and short-lived.
	 *
	 * @param  object                   $client  Client row, already authenticated
	 * @param  string                   $refresh Refresh token
	 * @return array<string, mixed>|null         Token response, or null on error
	 */
	public function refreshTokens($client, $refresh)
	{
		$row = $this->getValidToken('refresh', $refresh);
		if (!$row || (int) $row->fk_client !== (int) $client->rowid) {
			$this->error = 'invalid_grant';
			return null;
		}

		$this->revokeToken((int) $row->rowid);

		return $this->issueTokenPair((int) $client->rowid, (int) $row->fk_user, (string) $row->scope, (string) $row->resource);
	}

	/**
	 * Resolve an access token to the consent it represents.
	 *
	 * @param  string      $token Access token presented by the caller
	 * @return object|null        Token row, or null when unknown, expired or revoked
	 */
	public function validateAccessToken($token)
	{
		return $this->getValidToken('access', $token);
	}

	/**
	 * Tell an access token minted here from any other credential.
	 *
	 * Lets the caller skip the database when a value cannot possibly be one.
	 *
	 * @param  string $credential Value presented by the caller
	 * @return bool               True when it looks like one of our access tokens
	 */
	public static function looksLikeAccessToken($credential)
	{
		return strpos($credential, self::TOKEN_PREFIX.'a') === 0;
	}

	/**
	 * Delete the rows that can no longer authorise anything.
	 *
	 * Called from the token endpoint rather than from cron: the table only
	 * grows when tokens are issued, so the work lands where it is created.
	 *
	 * @return void
	 */
	public function purgeExpired()
	{
		$sql = "DELETE FROM ".$this->db->prefix()."ai_oauth_token";
		$sql .= " WHERE expires_at < '".$this->db->idate(dol_now() - 86400)."'";

		$this->db->query($sql);

		// A registration that never led to a token is a row nobody asked for:
		// /register takes no credential, so without this the table only grows,
		// one row per connection attempt. Anything that did obtain a token is
		// left alone whatever its age.
		$sql = "DELETE FROM ".$this->db->prefix()."ai_oauth_client";
		$sql .= " WHERE datec < '".$this->db->idate(dol_now() - self::UNUSED_CLIENT_TTL)."'";
		$sql .= " AND rowid NOT IN (SELECT fk_client FROM ".$this->db->prefix()."ai_oauth_token)";

		$this->db->query($sql);
	}

	/**
	 * Count the clients registered from one address over the last hour.
	 *
	 * @param  string $ip Caller address
	 * @return int        Number of registrations
	 */
	public function countRecentRegistrations($ip)
	{
		global $conf;

		if ($ip === '') {
			return 0;
		}

		$sql = "SELECT COUNT(rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."ai_oauth_client";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		$sql .= " AND registered_from = '".$this->db->escape($ip)."'";
		$sql .= " AND datec > '".$this->db->idate(dol_now() - 3600)."'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return 0;
		}
		$obj = $this->db->fetch_object($resql);

		return $obj ? (int) $obj->nb : 0;
	}

	/**
	 * Accept a redirect URI only in a shape that cannot be turned against us.
	 *
	 * https is required, except on loopback, which is how a desktop or CLI
	 * client receives its code and never leaves the machine (RFC 8252). A
	 * fragment is refused outright: it would silently drop the query the
	 * response is carried in.
	 *
	 * @param  string $uri Candidate redirect URI
	 * @return bool        True when it may be registered
	 */
	private function isAcceptableRedirectUri($uri)
	{
		if ($uri === '' || strlen($uri) > 2000) {
			return false;
		}

		$parts = parse_url($uri);
		if ($parts === false || empty($parts['scheme']) || !empty($parts['fragment'])) {
			return false;
		}

		$scheme = strtolower($parts['scheme']);
		$host = isset($parts['host']) ? strtolower($parts['host']) : '';

		if ($scheme === 'https') {
			return $host !== '';
		}
		if ($scheme === 'http') {
			return in_array($host, array('127.0.0.1', '[::1]', 'localhost'), true);
		}

		// A private-use scheme (com.example.app:/oauth) is how a native client
		// is called back. It has no host to check, so the scheme itself has to
		// look like a reversed domain rather than a word anyone could claim.
		return (bool) preg_match('/^[a-z0-9]+(\.[a-z0-9-]+){2,}$/', $scheme);
	}

	/**
	 * Mint an access and refresh token for a consent.
	 *
	 * @param  int                      $clientrowid Client rowid
	 * @param  int                      $userid      User who consented
	 * @param  string                   $scope       Granted scope
	 * @param  string                   $resource    Audience
	 * @return array<string, mixed>|null             Token response, or null on error
	 */
	private function issueTokenPair($clientrowid, $userid, $scope, $resource)
	{
		$access = self::TOKEN_PREFIX.'a'.bin2hex(random_bytes(32));
		$refresh = self::TOKEN_PREFIX.'r'.bin2hex(random_bytes(32));

		if (!$this->insertToken('access', $access, $clientrowid, $userid, $scope, $resource, null, null, self::ACCESS_TOKEN_TTL)
			|| !$this->insertToken('refresh', $refresh, $clientrowid, $userid, $scope, $resource, null, null, self::REFRESH_TOKEN_TTL)) {
			$this->error = 'server_error';
			return null;
		}

		$response = array(
			'access_token' => $access,
			'token_type' => 'Bearer',
			'expires_in' => self::ACCESS_TOKEN_TTL,
			'refresh_token' => $refresh,
		);
		if ($scope !== '') {
			$response['scope'] = $scope;
		}

		return $response;
	}

	/**
	 * Store one minted value.
	 *
	 * @param  string      $type          code, access or refresh
	 * @param  string      $token         The value; only its hash is written
	 * @param  int         $clientrowid   Client rowid
	 * @param  int         $userid        User who consented
	 * @param  string      $scope         Granted scope
	 * @param  string      $resource      Audience
	 * @param  string|null $codechallenge PKCE challenge, on codes only
	 * @param  string|null $redirecturi   Redirect URI, on codes only
	 * @param  int         $ttl           Lifetime in seconds
	 * @return bool                       True on success
	 */
	private function insertToken($type, $token, $clientrowid, $userid, $scope, $resource, $codechallenge, $redirecturi, $ttl)
	{
		global $conf;

		$sql = "INSERT INTO ".$this->db->prefix()."ai_oauth_token";
		$sql .= " (entity, token_type, token_hash, fk_client, fk_user, scope, resource, code_challenge, redirect_uri, expires_at, datec)";
		$sql .= " VALUES (".((int) $conf->entity);
		$sql .= ", '".$this->db->escape($type)."'";
		$sql .= ", '".$this->db->escape(hash('sha256', $token))."'";
		$sql .= ", ".((int) $clientrowid);
		$sql .= ", ".((int) $userid);
		$sql .= ", '".$this->db->escape($scope)."'";
		$sql .= ", '".$this->db->escape($resource)."'";
		$sql .= ", ".($codechallenge === null ? "NULL" : "'".$this->db->escape($codechallenge)."'");
		$sql .= ", ".($redirecturi === null ? "NULL" : "'".$this->db->escape($redirecturi)."'");
		$sql .= ", '".$this->db->idate(dol_now() + $ttl)."'";
		$sql .= ", '".$this->db->idate(dol_now())."')";

		if (!$this->db->query($sql)) {
			dol_syslog('[MCP OAuth] Could not store a '.$type.': '.$this->db->lasterror(), LOG_ERR);
			return false;
		}

		return true;
	}

	/**
	 * Load a value that is still good for something.
	 *
	 * Expiry and revocation are part of the lookup rather than checked after
	 * it, so there is no window in which a caller reads a row it may not use.
	 *
	 * @param  string      $type  code, access or refresh
	 * @param  string      $token The value presented
	 * @return object|null        The row, or null
	 */
	private function getValidToken($type, $token)
	{
		global $conf;

		if (empty($token)) {
			return null;
		}

		$sql = "SELECT rowid, token_type, fk_client, fk_user, scope, resource, code_challenge, redirect_uri";
		$sql .= " FROM ".$this->db->prefix()."ai_oauth_token";
		$sql .= " WHERE token_type = '".$this->db->escape($type)."'";
		$sql .= " AND token_hash = '".$this->db->escape(hash('sha256', $token))."'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " AND revoked = 0";
		$sql .= " AND expires_at > '".$this->db->idate(dol_now())."'";

		$resql = $this->db->query($sql);
		if (!$resql || $this->db->num_rows($resql) != 1) {
			return null;
		}

		return $this->db->fetch_object($resql);
	}

	/**
	 * Revoke one row.
	 *
	 * @param  int  $rowid Row to revoke
	 * @return bool        True on success
	 */
	private function revokeToken($rowid)
	{
		$sql = "UPDATE ".$this->db->prefix()."ai_oauth_token";
		$sql .= " SET revoked = 1";
		$sql .= " WHERE rowid = ".((int) $rowid);

		return (bool) $this->db->query($sql);
	}
}
