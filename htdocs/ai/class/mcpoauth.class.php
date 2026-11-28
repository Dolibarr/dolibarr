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
 * OAuth 2.1 authorization server for MCP clients: authorization code with PKCE S256, refresh rotation, metadata.
 * Clients, codes and tokens are rows of llx_oauth_token told apart by service; only hashes of tokens are stored.
 */
class McpOauth
{
	/**
	 * Prefix of every value this server mints, followed by a letter telling what it is.
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
	 * How long a fetched metadata document is trusted, in seconds.
	 */
	const METADATA_DOCUMENT_TTL = 86400;

	/**
	 * Most registrations one address may make in an hour.
	 */
	const REGISTRATIONS_PER_HOUR = 20;

	/**
	 * Lifetime of an authorization code, in seconds (exchanged at once by the client).
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
	 * @var string Error code of the last failed call, in OAuth vocabulary (invalid_grant...)
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
	 * Authorization server metadata (RFC 8414), without OpenID fields: this server is not an OpenID provider.
	 *
	 * @return array<string, mixed> The document to serve
	 */
	public function metadataAuthorizationServer()
	{
		$metadata = array(
			'issuer' => $this->issuer,
			'authorization_endpoint' => $this->issuer.'/authorize',
			'token_endpoint' => $this->issuer.'/token',
			'response_types_supported' => array('code'),
			'grant_types_supported' => array('authorization_code', 'refresh_token'),
			// S256 only: 'plain' protects nothing on a public client
			'code_challenge_methods_supported' => array('S256'),
			'token_endpoint_auth_methods_supported' => array('none', 'client_secret_basic', 'client_secret_post'),
			'scopes_supported' => array('dolibarr'),
			// RFC 9207: the authorization response names its issuer
			'authorization_response_iss_parameter_supported' => true,
		);

		// Self-registration and metadata documents are advertised only when the administrator allows them
		if (getDolGlobalString('AI_MCP_OAUTH_DYNAMIC_REGISTRATION')) {
			$metadata['registration_endpoint'] = $this->issuer.'/register';
			$metadata['client_id_metadata_document_supported'] = true;
		}

		return $metadata;
	}

	/**
	 * Protected resource metadata (RFC 9728), pointed at by the WWW-Authenticate challenge.
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
	 * Register a client (RFC 7591), when AI_MCP_OAUTH_DYNAMIC_REGISTRATION allows it.
	 *
	 * @param  array<string, mixed>      $metadata Client metadata sent by the caller
	 * @return array<string, mixed>|null           Registration response, or null on error
	 */
	public function registerClient(array $metadata)
	{
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

		if (!$this->insertClientRow($clientid, ($secret === '' ? null : hash('sha256', $secret)), $name, $uris, $authmethod)) {
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
	 * @param  bool        $mayfetch Whether a client_id that is a metadata document URL may be fetched
	 * @return object|null           Client row, or null when unknown
	 */
	public function getClient($clientid, $mayfetch = false)
	{
		if (empty($clientid)) {
			return null;
		}

		// A client_id that is an HTTPS URL is a metadata document, fetched only where
		// $mayfetch allows it (/authorize, behind a signed-in user), never anonymously.
		if (self::isMetadataDocumentUrl($clientid)) {
			if (!$mayfetch) {
				return $this->fetchClientRow($clientid);
			}

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
		// Bounded by the column the URL is stored in
		return stripos($clientid, 'https://') === 0 && dol_strlen($clientid) <= 255;
	}

	/**
	 * Resolve a client_id that is the URL of a metadata document, stored and read again once a day.
	 * getURLContent() refuses private, loopback and link-local addresses, even after a redirect.
	 *
	 * @param  string      $url Absolute HTTPS URL of the document
	 * @return object|null      Client row, or null when the document is unusable
	 */
	private function resolveMetadataDocument($url)
	{
		$known = $this->fetchClientRow($url);

		// A metadata document is self-registration too: same administrator switch
		if (!getDolGlobalString('AI_MCP_OAUTH_DYNAMIC_REGISTRATION')) {
			if ($known === null) {
				$this->error = 'access_denied';
			}

			return $known;
		}
		if ($known !== null && $this->db->jdate($known->tms) > (dol_now() - self::METADATA_DOCUMENT_TTL)) {
			return $known;
		}

		// Rate limited before the outbound request, which costs a worker even when it fails
		if ($this->countRecentRegistrations(getUserRemoteIP(1)) >= self::REGISTRATIONS_PER_HOUR) {
			dol_syslog('[MCP OAuth] Metadata document refused, rate limit reached for '.getUserRemoteIP(1), LOG_WARNING);
			$this->error = 'temporarily_unavailable';
			return $known;
		}

		$document = $this->readMetadataDocument($url);
		if ($document === null) {
			// Keep a known client working while its document is unreachable
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
			$details = $this->clientDetails($url, null, $name, 'none', $known->registered_from);
			$sql = "UPDATE ".$this->db->prefix()."oauth_token";
			$sql .= " SET tokenstring = '".$this->db->escape($details)."'";
			$sql .= ", redirect_uri = '".$this->db->escape(implode("\n", $uris))."'";
			$sql .= ", tms = '".$this->db->idate(dol_now())."'";
			$sql .= " WHERE rowid = ".((int) $known->rowid);
			$stored = (bool) $this->db->query($sql);
		} else {
			$stored = $this->insertClientRow($url, null, $name, $uris, 'none');
		}

		if (!$stored) {
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

		// Certificate always verified: this document decides where a code may go
		$response = getURLContent($url, 'GET', '', 1, array('Accept: application/json'), array('https'), 0, 1);

		if (empty($response['content']) || !empty($response['curl_error_no'])) {
			dol_syslog('[MCP OAuth] Metadata document unreachable: '.$url.' '.(isset($response['curl_error_msg']) ? $response['curl_error_msg'] : ''), LOG_NOTICE);
			return null;
		}
		if (dol_strlen($response['content']) > 65536) {
			dol_syslog('[MCP OAuth] Metadata document too large: '.$url, LOG_WARNING);
			return null;
		}

		$document = json_decode($response['content'], true);
		if (!is_array($document)) {
			dol_syslog('[MCP OAuth] Metadata document is not a JSON object: '.$url, LOG_WARNING);
			return null;
		}

		// The document must claim the URL it was found at
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

		$sql = "SELECT rowid, tokenstring, redirect_uri, datec, tms";
		$sql .= " FROM ".$this->db->prefix()."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape(self::serviceFor('client'))."'";
		$sql .= " AND token_hash = '".$this->db->escape($this->clientKey($clientid))."'";
		$sql .= " AND entity = ".((int) $conf->entity);

		$resql = $this->db->query($sql);
		if (!$resql || $this->db->num_rows($resql) != 1) {
			return null;
		}
		$row = $this->db->fetch_object($resql);
		$details = json_decode((string) $row->tokenstring, true);
		// The identifier stored beside the hash must agree
		if (!is_array($details) || !isset($details['client_id']) || !hash_equals((string) $details['client_id'], (string) $clientid)) {
			return null;
		}

		return (object) array(
			'rowid' => (int) $row->rowid,
			'client_id' => (string) $details['client_id'],
			'client_secret_hash' => isset($details['client_secret_hash']) ? (string) $details['client_secret_hash'] : '',
			'client_name' => isset($details['client_name']) ? (string) $details['client_name'] : '',
			'token_endpoint_auth_method' => isset($details['token_endpoint_auth_method']) ? (string) $details['token_endpoint_auth_method'] : 'none',
			'registered_from' => isset($details['registered_from']) ? (string) $details['registered_from'] : '',
			'redirect_uris' => (string) $row->redirect_uri,
			'datec' => $row->datec,
			'tms' => $row->tms,
		);
	}

	/**
	 * Key a client row is found by: the hash of its entity and identifier (a URL may not fit token_hash).
	 *
	 * @param  string $clientid Client identifier
	 * @return string           SHA-256, hexadecimal
	 */
	private function clientKey($clientid)
	{
		global $conf;

		return hash('sha256', ((int) $conf->entity)."\n".$clientid);
	}

	/**
	 * What a client row keeps in tokenstring, as JSON. Nothing in it is secret.
	 *
	 * @param  string      $clientid   Client identifier
	 * @param  string|null $secrethash SHA-256 of the client secret, null for a public client
	 * @param  string      $name       Name shown on the consent screen
	 * @param  string      $authmethod none, client_secret_basic or client_secret_post
	 * @param  string      $from       Address the registration came from
	 * @return string                  JSON
	 */
	private function clientDetails($clientid, $secrethash, $name, $authmethod, $from)
	{
		$details = array(
			'client_id' => $clientid,
			'client_name' => $name,
			'token_endpoint_auth_method' => $authmethod,
			'registered_from' => $from,
		);
		if ($secrethash !== null) {
			$details['client_secret_hash'] = $secrethash;
		}

		return (string) json_encode($details);
	}

	/**
	 * Store a new client: a llx_oauth_token row with service 'mcp_client', redirect URIs one per line.
	 *
	 * @param  string      $clientid   Client identifier
	 * @param  string|null $secrethash SHA-256 of the client secret, null for a public client
	 * @param  string      $name       Name shown on the consent screen
	 * @param  string[]    $uris       Accepted redirect URIs
	 * @param  string      $authmethod none, client_secret_basic or client_secret_post
	 * @return bool                    True on success
	 */
	private function insertClientRow($clientid, $secrethash, $name, array $uris, $authmethod)
	{
		global $conf;

		$from = dol_trunc((string) getUserRemoteIP(1), 64, 'right', 'UTF-8', 1);

		$sql = "INSERT INTO ".$this->db->prefix()."oauth_token";
		$sql .= " (entity, service, token_hash, tokenstring, redirect_uri, datec)";
		$sql .= " VALUES (".((int) $conf->entity);
		$sql .= ", '".$this->db->escape(self::serviceFor('client'))."'";
		$sql .= ", '".$this->db->escape($this->clientKey($clientid))."'";
		$sql .= ", '".$this->db->escape($this->clientDetails($clientid, $secrethash, $name, $authmethod, $from))."'";
		$sql .= ", '".$this->db->escape(implode("\n", $uris))."'";
		$sql .= ", '".$this->db->idate(dol_now())."')";

		if (!$this->db->query($sql)) {
			dol_syslog('[MCP OAuth] Could not store client '.$clientid.': '.$this->db->lasterror(), LOG_ERR);
			return false;
		}

		return true;
	}

	/**
	 * Check the credentials of a client at the token endpoint (a public client relies on PKCE).
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
	 * Check a redirect URI against the registered ones, compared in full as OAuth 2.1 requires.
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
	 * Compare two loopback redirect URIs ignoring the port, picked at run time by a native client (RFC 8252 7.3).
	 *
	 * @param  string $known       Registered redirect URI
	 * @param  string $redirecturi URI presented in the request
	 * @return bool                True when they differ only by their port
	 */
	private function isSameLoopbackUri($known, $redirecturi)
	{
		// One component at a time: phan flags an array key named 'query' as SQL
		$hosta = dol_strtolower((string) parse_url($known, PHP_URL_HOST));
		$hostb = dol_strtolower((string) parse_url($redirecturi, PHP_URL_HOST));

		$loopback = array('127.0.0.1', '[::1]', '::1', 'localhost');
		if (!in_array($hosta, $loopback, true) || $hosta !== $hostb) {
			return false;
		}
		if (dol_strtolower((string) parse_url($known, PHP_URL_SCHEME)) !== 'http') {
			return false;
		}
		if (dol_strtolower((string) parse_url($redirecturi, PHP_URL_SCHEME)) !== 'http') {
			return false;
		}

		$patha = (string) parse_url($known, PHP_URL_PATH);
		$pathb = (string) parse_url($redirecturi, PHP_URL_PATH);
		$searcha = (string) parse_url($known, PHP_URL_QUERY);
		$searchb = (string) parse_url($redirecturi, PHP_URL_QUERY);

		return hash_equals($patha, $pathb) && hash_equals($searcha, $searchb);
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
		// RFC 8707: a resource naming another server is refused, none means this one
		if ($resource !== '' && !$this->isOwnResource($resource)) {
			$this->error = 'invalid_target';
			return null;
		}

		if (empty($codechallenge)) {
			// PKCE is mandatory
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
	 * Tell whether an audience names this MCP server (trailing slash ignored).
	 *
	 * @param  string $resource Audience presented by the caller
	 * @return bool             True when it is this server
	 */
	public function isOwnResource($resource)
	{
		return hash_equals(rtrim($this->resource, '/'), rtrim((string) $resource, '/'));
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
		if (!$row) {
			$this->revokeReplayedFamily('code', $code, (int) $client->rowid);
			$this->error = 'invalid_grant';
			return null;
		}
		if ((int) $row->fk_client !== (int) $client->rowid) {
			$this->error = 'invalid_grant';
			return null;
		}

		// One-shot: revoked first, so neither a failed nor a concurrent exchange can reuse it
		if (!$this->revokeToken((int) $row->rowid)) {
			$this->error = 'invalid_grant';
			return null;
		}

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
	 * Exchange a refresh token for a new pair; the presented one is revoked (rotation).
	 *
	 * @param  object                   $client  Client row, already authenticated
	 * @param  string                   $refresh Refresh token
	 * @return array<string, mixed>|null         Token response, or null on error
	 */
	public function refreshTokens($client, $refresh)
	{
		$row = $this->getValidToken('refresh', $refresh);
		if (!$row || (int) $row->fk_client !== (int) $client->rowid) {
			// A spent token presented again means a stolen copy: revoke the grant (OAuth 2.1 4.3.1)
			$this->revokeReplayedFamily('refresh', $refresh, (int) $client->rowid);

			$this->error = 'invalid_grant';
			return null;
		}

		if (!$this->revokeToken((int) $row->rowid)) {
			$this->error = 'invalid_grant';
			return null;
		}

		return $this->issueTokenPair((int) $client->rowid, (int) $row->fk_user, (string) $row->scope, (string) $row->resource);
	}

	/**
	 * Revoke what was issued to a client for a user, when a spent code or refresh token is presented again.
	 *
	 * @param  string $type        code or refresh
	 * @param  string $value       Value presented
	 * @param  int    $clientrowid Client that presented it
	 * @return void
	 */
	private function revokeReplayedFamily($type, $value, $clientrowid)
	{
		global $conf;

		if (empty($value)) {
			return;
		}

		$sql = "SELECT fk_user FROM ".$this->db->prefix()."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape(self::serviceFor($type))."'";
		$sql .= " AND token_hash = '".$this->db->escape(hash('sha256', $value))."'";
		$sql .= " AND revoked = 1";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " AND fk_oauth_client = ".((int) $clientrowid);

		$resql = $this->db->query($sql);
		if (!$resql || $this->db->num_rows($resql) != 1) {
			return;
		}
		$obj = $this->db->fetch_object($resql);

		$sql = "UPDATE ".$this->db->prefix()."oauth_token";
		$sql .= " SET revoked = 1";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		$sql .= " AND service IN (".$this->db->sanitize($this->ownServicesSql(), 1).")";
		$sql .= " AND fk_oauth_client = ".((int) $clientrowid);
		$sql .= " AND fk_user = ".((int) $obj->fk_user);
		$sql .= " AND revoked = 0";

		if ($this->db->query($sql)) {
			dol_syslog('[MCP OAuth] Replayed '.$type.': revoked every grant of client '.$clientrowid.' for user '.$obj->fk_user, LOG_WARNING);
		}
	}

	/**
	 * Resolve an access token to the consent it represents.
	 *
	 * @param  string      $token Access token presented by the caller
	 * @return object|null        Token row, or null when unknown, expired or revoked
	 */
	public function validateAccessToken($token)
	{
		$row = $this->getValidToken('access', $token);
		if ($row !== null) {
			$this->recordUse((int) $row->rowid);
		}

		return $row;
	}

	/**
	 * Count one use of a token. Assignments are ordered to read the old lastaccess on MySQL and PostgreSQL.
	 *
	 * @param  int  $rowid Token row
	 * @return void
	 */
	private function recordUse($rowid)
	{
		$tmpnow = dol_getdate(dol_now('gmt'), true, 'gmt');
		$monthstart = dol_mktime(0, 0, 0, $tmpnow['mon'], 1, $tmpnow['year'], 'gmt', 0);

		// Condition inline in ifsql(), as in api_access.class.php, for the CI SQL injection check
		$sql = "UPDATE ".$this->db->prefix()."oauth_token SET";
		$sql .= " apicount_total = apicount_total + 1,";
		$sql .= " apicount_previous_month = ".$this->db->ifsql("lastaccess < '".$this->db->idate($monthstart, 'gmt')."'", 'apicount_month', 'apicount_previous_month').",";
		$sql .= " apicount_month = ".$this->db->ifsql("lastaccess < '".$this->db->idate($monthstart, 'gmt')."'", '1', 'apicount_month + 1').",";
		$sql .= " lastaccess = '".$this->db->idate(dol_now('gmt'), 'gmt')."'";
		$sql .= " WHERE rowid = ".((int) $rowid);

		$this->db->query($sql);
	}

	/**
	 * The llx_oauth_token service of one kind of row (the table is shared: every query filters on it).
	 *
	 * @param  string $type client, code, access or refresh
	 * @return string       Service name
	 */
	public static function serviceFor($type)
	{
		return 'mcp_'.$type;
	}

	/**
	 * The three service names, quoted for an IN () list.
	 *
	 * @return string 'mcp_code', 'mcp_access', 'mcp_refresh'
	 */
	private function ownServicesSql()
	{
		$list = array();
		foreach (array('code', 'access', 'refresh') as $type) {
			$list[] = "'".$this->db->escape(self::serviceFor($type))."'";
		}

		return implode(',', $list);
	}

	/**
	 * Tell an access token minted here from any other credential, without a database query.
	 *
	 * @param  string $credential Value presented by the caller
	 * @return bool               True when it looks like one of our access tokens
	 */
	public static function looksLikeAccessToken($credential)
	{
		return strpos($credential, self::TOKEN_PREFIX.'a') === 0;
	}

	/**
	 * Delete the rows that can no longer authorise anything (called from the token endpoint).
	 *
	 * @return void
	 */
	public function purgeExpired()
	{
		global $conf;

		// Our services only: other modules keep expired tokens they still refresh
		$sql = "DELETE FROM ".$this->db->prefix()."oauth_token";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		$sql .= " AND service IN (".$this->db->sanitize($this->ownServicesSql(), 1).")";
		$sql .= " AND expire_at < '".$this->db->idate(dol_now() - 86400)."'";

		$this->db->query($sql);

		// Clients that never obtained a token. Selected first, then deleted by rowid:
		// MySQL refuses a DELETE whose subquery reads the same table.
		$sql = "SELECT c.rowid";
		$sql .= " FROM ".$this->db->prefix()."oauth_token as c";
		$sql .= " LEFT JOIN ".$this->db->prefix()."oauth_token as t ON t.fk_oauth_client = c.rowid";
		$sql .= " WHERE c.service = '".$this->db->escape(self::serviceFor('client'))."'";
		$sql .= " AND c.entity = ".((int) $conf->entity);
		$sql .= " AND c.datec < '".$this->db->idate(dol_now() - self::UNUSED_CLIENT_TTL)."'";
		$sql .= " AND t.rowid IS NULL";
		$sql .= $this->db->plimit(500);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return;
		}
		$unused = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$unused[] = (int) $obj->rowid;
		}
		if (empty($unused)) {
			return;
		}

		$sql = "DELETE FROM ".$this->db->prefix()."oauth_token";
		$sql .= " WHERE rowid IN (".$this->db->sanitize(implode(',', $unused)).")";

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

		// The exact text clientDetails() wrote for this address
		$needle = dol_substr((string) json_encode(array('registered_from' => $ip)), 1, -1);

		$sql = "SELECT COUNT(rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape(self::serviceFor('client'))."'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " AND datec > '".$this->db->idate(dol_now() - 3600)."'";
		$sql .= " AND tokenstring LIKE '%".$this->db->escape($this->db->escapeforlike($needle))."%'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return 0;
		}
		$obj = $this->db->fetch_object($resql);

		return $obj ? (int) $obj->nb : 0;
	}

	/**
	 * Accept a redirect URI: https, http on loopback only (RFC 8252), or a private scheme; never a fragment.
	 *
	 * @param  string $uri Candidate redirect URI
	 * @return bool        True when it may be registered
	 */
	private function isAcceptableRedirectUri($uri)
	{
		if ($uri === '' || dol_strlen($uri) > 2000) {
			return false;
		}

		$parts = parse_url($uri);
		if ($parts === false || empty($parts['scheme']) || !empty($parts['fragment'])) {
			return false;
		}

		$scheme = dol_strtolower($parts['scheme']);
		$host = isset($parts['host']) ? dol_strtolower($parts['host']) : '';

		if ($scheme === 'https') {
			return $host !== '';
		}
		if ($scheme === 'http') {
			return in_array($host, array('127.0.0.1', '[::1]', 'localhost'), true);
		}

		// A private-use scheme (com.example.app:/oauth) must look like a reversed domain
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

		// Only the SHA-256 is stored, in token_hash
		$sql = "INSERT INTO ".$this->db->prefix()."oauth_token";
		$sql .= " (entity, service, token_hash, fk_oauth_client, fk_user, state, resource, code_challenge, redirect_uri, expire_at, datec)";
		$sql .= " VALUES (".((int) $conf->entity);
		$sql .= ", '".$this->db->escape(self::serviceFor($type))."'";
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
	 * Load a code or token that is neither expired nor revoked (both checked in the lookup).
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

		$sql = "SELECT rowid, fk_oauth_client as fk_client, fk_user, state as scope, resource, code_challenge, redirect_uri";
		$sql .= " FROM ".$this->db->prefix()."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape(self::serviceFor($type))."'";
		$sql .= " AND token_hash = '".$this->db->escape(hash('sha256', $token))."'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " AND revoked = 0";
		$sql .= " AND expire_at > '".$this->db->idate(dol_now())."'";

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
		// "revoked = 0" and the affected row count let the database decide
		// which of two concurrent requests spends the grant.
		$sql = "UPDATE ".$this->db->prefix()."oauth_token";
		$sql .= " SET revoked = 1";
		$sql .= " WHERE rowid = ".((int) $rowid);
		$sql .= " AND revoked = 0";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}

		return $this->db->affected_rows($resql) == 1;
	}
}
