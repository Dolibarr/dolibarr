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
 * \file htdocs/ai/class/mcpauth.class.php
 * \ingroup ai
 * \brief Authentication of MCP clients on the Streamable HTTP endpoint.
 * \see https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization
 */


/**
 * McpAuth Class
 *
 * Resolves the credential presented by an MCP client and turns it into a
 * Dolibarr identity. Two credentials are accepted:
 *
 * - a user API key (the same key the REST API takes), which authenticates the
 *   request as that user, so tools run with that user's permissions and the
 *   request log names who actually called;
 * - the shared server key AI_MCP_API_KEY, kept for backward compatibility,
 *   which authenticates the request as the AI_MCP_USER_ID service user.
 *
 * The class deliberately holds no state beyond the result of the last call and
 * touches neither the HTTP response nor the session: it reads a request and
 * answers a question. That is what lets the MCP entry point stay a thin script
 * today, and lets the same logic be handed to an AuthorizationMiddleware later
 * without being rewritten.
 */
class McpAuth
{
	/**
	 * Credential mode: an individual user API key was presented.
	 */
	const MODE_USER = 'user';

	/**
	 * Credential mode: the shared AI_MCP_API_KEY was presented.
	 */
	const MODE_SHARED = 'shared';

	/**
	 * @var DoliDB Database handler
	 */
	private $db;

	/**
	 * @var string Error message, set when authenticate() returns -1
	 */
	public $error = '';

	/**
	 * @var int HTTP status code to answer with, set when authenticate() returns -1
	 */
	public $httpcode = 401;

	/**
	 * @var int Rowid of the authenticated user. 0 when the shared key was used,
	 *          in which case the caller falls back to AI_MCP_USER_ID.
	 */
	public $userid = 0;

	/**
	 * @var string Which credential matched, one of the MODE_* constants
	 */
	public $mode = '';

	/**
	 * @var User|null The authenticated user, already loaded with its rights,
	 *                when an individual API key was presented. Null on the
	 *                shared-key path, where the caller falls back to
	 *                AI_MCP_USER_ID.
	 */
	public $user = null;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Extract the credential presented by the caller.
	 *
	 * Accepted forms, in order of precedence, mirroring the REST API
	 * (see Api Access class) so a key that works on /api/index.php works here:
	 *   - "DOLAPIKEY: <key>" header
	 *   - "Authorization: Bearer <key>" header
	 *   - "X-API-Key: <key>" header
	 *   - "?DOLAPIKEY=", "?api_key=" or "?key=" query parameter
	 *
	 * The Authorization header is read from several places on purpose: Apache
	 * in CGI/FastCGI mode does not expose it unless CGIPassAuth is on, and the
	 * usual workaround republishes it as REDIRECT_HTTP_AUTHORIZATION. Reading
	 * $_SERVER alone makes Bearer authentication fail on those setups with no
	 * diagnostic other than a 401.
	 *
	 * @param 	array<string,mixed>|null 	$server 	Server variables, defaults to $_SERVER
	 * @param 	array<string,mixed>|null 	$get 		Query parameters, defaults to $_GET
	 * @return 	string 									The credential, or '' when none was presented
	 */
	public function getCredential($server = null, $get = null)
	{
		if ($server === null) {
			$server = $_SERVER;
		}
		if ($get === null) {
			$get = $_GET;	// Keep $_GET here: the key is read before any Dolibarr context exists.
		}

		$credential = '';

		if (!empty($server['HTTP_DOLAPIKEY'])) {
			$credential = $server['HTTP_DOLAPIKEY'];
		}

		if ($credential === '') {
			$authheader = '';
			if (!empty($server['HTTP_AUTHORIZATION'])) {
				$authheader = $server['HTTP_AUTHORIZATION'];
			} elseif (!empty($server['REDIRECT_HTTP_AUTHORIZATION'])) {
				$authheader = $server['REDIRECT_HTTP_AUTHORIZATION'];
			} elseif (function_exists('getallheaders')) {
				$headers = array_change_key_case(getallheaders(), CASE_LOWER);
				$authheader = isset($headers['authorization']) ? $headers['authorization'] : '';
			}
			$reg = array();
			if ($authheader !== '' && preg_match('/^Bearer\s+(\S+)$/i', $authheader, $reg)) {
				$credential = $reg[1];
			}
		}

		if ($credential === '' && !empty($server['HTTP_X_API_KEY'])) {
			$credential = $server['HTTP_X_API_KEY'];
		}

		// Query-string fallback, required by MCP clients that cannot send a
		// custom auth header from their connector UI (Claude Desktop "Custom
		// Connectors" exposes OAuth fields only). Keys sent this way end up in
		// webserver access logs and possibly in Referer headers, so it is tried
		// last; administrators relying on it should restrict access at the
		// webserver level and rotate the key regularly.
		if ($credential === '') {
			foreach (array('DOLAPIKEY', 'api_key', 'key') as $param) {
				if (!empty($get[$param])) {
					$credential = $get[$param];
					break;
				}
			}
		}

		return dol_string_nounprintableascii((string) $credential, 1);
	}

	/**
	 * Authenticate the caller.
	 *
	 * On success, $this->userid holds the user to run the request as (0 when
	 * the shared key was used) and $this->mode says which credential matched.
	 * On failure, $this->error and $this->httpcode hold what to answer.
	 *
	 * @param 	array<string,mixed>|null 	$server 	Server variables, defaults to $_SERVER
	 * @param 	array<string,mixed>|null 	$get 		Query parameters, defaults to $_GET
	 * @return 	int 									1 if authenticated, -1 otherwise
	 */
	public function authenticate($server = null, $get = null)
	{
		$this->error = '';
		$this->httpcode = 401;
		$this->userid = 0;
		$this->mode = '';
		$this->user = null;

		$credential = $this->getCredential($server, $get);

		if ($credential === '') {
			$this->error = 'Missing credentials. Provide a Dolibarr API key with an "Authorization: Bearer <key>" or "DOLAPIKEY: <key>" header.';
			return -1;
		}

		// An encrypted key read straight out of the database is not a credential.
		// Saying so explicitly saves the administrator a long hunt, and the value
		// is useless to an attacker anyway.
		if (preg_match('/^dolcrypt:/i', $credential)) {
			$this->httpcode = 503;
			$this->error = 'Bad value for the API key. An API key should not start with dolcrypt:';
			return -1;
		}

		// Shared server key, kept for setups configured before per-user keys
		// were accepted. Compared first because it is a single cheap test.
		$sharedkey = getDolGlobalString('AI_MCP_API_KEY');
		if ($sharedkey !== '' && hash_equals($sharedkey, $credential)) {
			$this->mode = self::MODE_SHARED;
			return 1;
		}

		$userid = $this->fetchUserIdFromApiKey($credential);
		if ($userid > 0) {
			require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

			$tmpuser = new User($this->db);
			if ($tmpuser->fetch($userid) <= 0) {
				dol_syslog('[MCP Server] Authentication KO: cannot load user '.$userid, LOG_ERR);
				$this->error = 'Unauthorized';
				return -1;
			}
			$tmpuser->loadRights();

			// Same gate as every other AI entry point (assistant/index.php,
			// parse_intent.php, execute_tool.php...). The right is not granted
			// by default, so enabling the MCP server does not silently turn
			// every REST API key into an MCP credential: an administrator
			// still decides who may talk to the assistant.
			if (!$tmpuser->hasRight('ai', 'assistant', 'use')) {
				dol_syslog('[MCP Server] Authentication KO: user '.$tmpuser->login.' has no ai/assistant/use permission', LOG_NOTICE);
				$this->httpcode = 403;
				$this->error = 'The user owning this API key is not allowed to use the AI assistant';
				return -1;
			}

			$this->userid = $userid;
			$this->user = $tmpuser;
			$this->mode = self::MODE_USER;
			return 1;
		}

		dol_syslog('[MCP Server] Unauthorized access attempt. IP='.(empty($_SERVER['REMOTE_ADDR']) ? 'unknown' : $_SERVER['REMOTE_ADDR']), LOG_WARNING);
		sleep(1);	// Anti brute force protection. Same delay as the REST API uses on a bad key.

		$this->error = 'Unauthorized';
		return -1;
	}

	/**
	 * Value of the WWW-Authenticate header to send with a 401.
	 *
	 * RFC 6750 section 3 requires the challenge on a rejected Bearer request;
	 * without it a client cannot tell "this endpoint wants a token" from "this
	 * endpoint is broken". When an authorization server is added, the URL of
	 * the Protected Resource Metadata document is passed here and clients
	 * discover it as described by RFC 9728 section 5.1.
	 *
	 * @param 	string 	$resourcemetadataurl 	Absolute URL of the Protected Resource Metadata document, if any
	 * @return 	string 							Header value, without the header name
	 */
	public function getWwwAuthenticateHeader($resourcemetadataurl = '')
	{
		$challenge = 'Bearer realm="Dolibarr MCP"';
		if ($resourcemetadataurl !== '') {
			$challenge .= ', resource_metadata="'.$resourcemetadataurl.'"';
		}

		return $challenge;
	}

	/**
	 * Look up the active user owning this API key.
	 *
	 * The lookup matches the REST API: the key is stored either plain or
	 * encrypted, and moves to the token table when API_IN_TOKEN_TABLE is set.
	 * Entity selection is not handled here; MCP requests run in the entity of
	 * the endpoint, and multicompany switching is a separate piece of work.
	 *
	 * @param 	string 	$credential 	Credential presented by the caller
	 * @return 	int 					User rowid, or 0 when no single active user owns this key
	 */
	private function fetchUserIdFromApiKey($credential)
	{
		if (getDolGlobalString('API_IN_TOKEN_TABLE')) {
			$sql = "SELECT u.rowid, u.login, u.statut, oat.tokenstring as storedkey";
			$sql .= " FROM ".$this->db->prefix()."oauth_token as oat";
			$sql .= " INNER JOIN ".$this->db->prefix()."user as u ON u.rowid = oat.fk_user";
			$sql .= " WHERE (oat.tokenstring = '".$this->db->escape($credential)."'";
			$sql .= " OR oat.tokenstring = '".$this->db->escape(dolEncrypt($credential, '', '', 'dolibarr'))."')";
			$sql .= " AND oat.service = 'dolibarr_rest_api'";
		} else {
			$sql = "SELECT u.rowid, u.login, u.statut, u.api_key as storedkey";
			$sql .= " FROM ".$this->db->prefix()."user as u";
			$sql .= " WHERE u.api_key = '".$this->db->escape($credential)."'";
			$sql .= " OR u.api_key = '".$this->db->escape(dolEncrypt($credential, '', '', 'dolibarr'))."'";
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog('[MCP Server] Authentication query failed: '.$this->db->lasterror(), LOG_ERR);
			return 0;
		}
		if ($this->db->num_rows($resql) != 1) {
			// Zero rows is a bad key. More than one means two users share a key,
			// so there is no single identity to run as and the request is refused.
			return 0;
		}

		$obj = $this->db->fetch_object($resql);
		if (empty($obj)) {
			return 0;
		}

		// The SQL already matched the key, so this only guards against a match
		// that did not come from the value presented (a collation that ignores
		// case or trailing spaces, for instance).
		if (!hash_equals((string) dolDecrypt($obj->storedkey), $credential)) {
			dol_syslog('[MCP Server] Authentication KO: key matched user '.$obj->login.' but differs from the stored value', LOG_WARNING);
			return 0;
		}

		if (empty($obj->statut)) {
			dol_syslog('[MCP Server] Authentication KO: user '.$obj->login.' is disabled', LOG_NOTICE);
			return 0;
		}

		dol_syslog('[MCP Server] Request authenticated for user '.$obj->login, LOG_DEBUG);

		return (int) $obj->rowid;
	}
}
