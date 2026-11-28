<?php
/* Copyright (C) 2026 Morgan Demoulin <morgan.demoulin@gmail.com>
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
 *      \file       test/phpunit/AiMcpOauthTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the MCP authorization server (ai/class/mcpoauth.class.php).
 */

global $conf,$user,$langs,$db;
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcpoauth.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpOauthTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpOauthTest extends CommonClassTest
{
	/**
	 * @var string Verifier used across the PKCE tests
	 */
	private $verifier = 'jTMZAFeqqAgVv8LRiVUq1IhZTvRIzqQ-3DHsxHJfNyc';

	/**
	 * A server bound to a fixed issuer, so the metadata assertions can be exact.
	 *
	 * @return McpOauth
	 */
	private function getServer()
	{
		global $db;

		return new McpOauth($db, 'https://example.org/ai/oauth.php', 'https://example.org/ai/server/mcp_server.php');
	}

	/**
	 * Skip the test when the llx_oauth_token columns used by the server are missing.
	 *
	 * @return void
	 */
	private function requireSchema()
	{
		global $db;

		// Columns added to the core llx_oauth_token by #40762.
		if (!$db->query("SELECT token_hash, fk_oauth_client, revoked FROM ".$db->prefix()."oauth_token WHERE 1 = 0")) {
			$this->markTestSkipped('Needs the llx_oauth_token columns added by PR #40762');
		}
	}

	/**
	 * Call the private redirect URI filter, which needs no storage.
	 *
	 * @param  string $uri Candidate redirect URI
	 * @return bool        Whether it may be registered
	 */
	private function acceptsRedirectUri($uri)
	{
		$method = new ReflectionMethod('McpOauth', 'isAcceptableRedirectUri');
		$method->setAccessible(true);

		return $method->invoke($this->getServer(), $uri);
	}

	/**
	 * Register a throwaway client and return its row.
	 *
	 * @param  string      $authmethod Token endpoint auth method
	 * @return object|null             Client row
	 */
	private function makeClient($authmethod = 'none')
	{
		$this->requireSchema();

		$server = $this->getServer();

		$registration = $server->registerClient(array(
			'client_name' => 'PHPUnit client',
			'redirect_uris' => array('https://example.org/callback'),
			'token_endpoint_auth_method' => $authmethod,
		));
		$this->assertTrue($registration !== null, 'Registration should have succeeded');

		return $server->getClient($registration['client_id']);
	}

	/**
	 * The PKCE challenge matching $this->verifier.
	 *
	 * @return string
	 */
	private function challenge()
	{
		return rtrim(strtr(base64_encode(hash('sha256', $this->verifier, true)), '+/', '-_'), '=');
	}

	/**
	 * The documents a client reads to find its way in.
	 *
	 * @return void
	 */
	public function testMetadataDocuments()
	{
		global $conf;

		$server = $this->getServer();

		$old = getDolGlobalString('AI_MCP_OAUTH_DYNAMIC_REGISTRATION');
		$conf->global->AI_MCP_OAUTH_DYNAMIC_REGISTRATION = '1';

		$as = $server->metadataAuthorizationServer();
		$this->assertSame('https://example.org/ai/oauth.php', $as['issuer']);
		$this->assertSame(array('S256'), $as['code_challenge_methods_supported'], 'plain must not be offered');
		$this->assertTrue($as['authorization_response_iss_parameter_supported'], 'RFC 9207 must be advertised');
		$this->assertTrue($as['client_id_metadata_document_supported'], 'Clients must be told they may use a metadata document');

		// The RFC 8414 document must not claim to be an OpenID provider: some connectors refuse it.
		$this->assertArrayNotHasKey('jwks_uri', $as);
		$this->assertArrayNotHasKey('subject_types_supported', $as);
		$this->assertArrayNotHasKey('id_token_signing_alg_values_supported', $as);
		$this->assertNotContains('openid', $as['scopes_supported'], 'The openid scope is not offered');

		$prm = $server->metadataProtectedResource();
		$this->assertSame('https://example.org/ai/server/mcp_server.php', $prm['resource']);
		$this->assertSame(array('https://example.org/ai/oauth.php'), $prm['authorization_servers']);

		// With self-onboarding off, neither way for an unknown client is advertised.
		$conf->global->AI_MCP_OAUTH_DYNAMIC_REGISTRATION = '0';

		$closed = $server->metadataAuthorizationServer();
		$this->assertArrayNotHasKey('registration_endpoint', $closed);
		$this->assertArrayNotHasKey('client_id_metadata_document_supported', $closed);
		$this->assertSame('https://example.org/ai/oauth.php', $closed['issuer'], 'The rest of the document is unchanged');

		$conf->global->AI_MCP_OAUTH_DYNAMIC_REGISTRATION = $old;
	}

	/**
	 * A public client gets no secret, and a confidential one gets it once.
	 *
	 * @return void
	 */
	public function testRegistrationSecretDependsOnAuthMethod()
	{
		$this->requireSchema();

		$server = $this->getServer();

		$public = $server->registerClient(array('redirect_uris' => array('https://example.org/cb'), 'token_endpoint_auth_method' => 'none'));
		$this->assertArrayNotHasKey('client_secret', $public, 'A public client must not be handed a secret');

		$confidential = $server->registerClient(array('redirect_uris' => array('https://example.org/cb'), 'token_endpoint_auth_method' => 'client_secret_basic'));
		$this->assertArrayHasKey('client_secret', $confidential);
		$this->assertNotEmpty($confidential['client_secret']);
	}

	/**
	 * Which redirect URIs may be registered, so a code cannot land elsewhere.
	 *
	 * @return void
	 */
	public function testRedirectUriRules()
	{
		$accepted = array(
			'https://example.org/callback',
			'http://127.0.0.1:53682/callback',
			'http://localhost:1410/',
			'com.example.app:/oauth2redirect',
		);
		foreach ($accepted as $uri) {
			$this->assertTrue($this->acceptsRedirectUri($uri), $uri.' should be accepted');
		}

		$refused = array(
			'http://evil.example/callback',		// plain http off the loopback
			'https://example.org/cb#fragment',	// a fragment swallows the response
			'ftp://example.org/cb',				// not a callback scheme
			'notadomain:/cb',					// a scheme anyone could claim
			'https:///nohost',					// no host to compare
			'',
		);
		foreach ($refused as $uri) {
			$this->assertFalse($this->acceptsRedirectUri($uri), $uri.' should be refused');
		}
	}

	/**
	 * Redirect URIs are compared whole, never by prefix.
	 *
	 * @return void
	 */
	public function testRedirectUriIsMatchedInFull()
	{
		$server = $this->getServer();
		$client = $this->makeClient();

		$this->assertTrue($server->isRegisteredRedirectUri($client, 'https://example.org/callback'));
		$this->assertFalse($server->isRegisteredRedirectUri($client, 'https://example.org/callback/../evil'));
		$this->assertFalse($server->isRegisteredRedirectUri($client, 'https://example.org/callbackevil'));
		$this->assertFalse($server->isRegisteredRedirectUri($client, 'https://example.org/'));
		$this->assertFalse($server->isRegisteredRedirectUri($client, ''));
	}

	/**
	 * The happy path, and the fact that the code only works once.
	 *
	 * @return void
	 */
	public function testAuthorizationCodeIsSingleUse()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$this->assertTrue($code !== null);

		$tokens = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);
		$this->assertTrue($tokens !== null, 'A correct exchange should return tokens');
		$this->assertArrayHasKey('access_token', $tokens);
		$this->assertArrayHasKey('refresh_token', $tokens);
		$this->assertSame('Bearer', $tokens['token_type']);

		$replay = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);
		$this->assertTrue($replay === null, 'The same code must not be exchangeable twice');
		$this->assertSame('invalid_grant', $server->error);
	}

	/**
	 * A wrong PKCE verifier is refused and burns the code.
	 *
	 * @return void
	 */
	public function testWrongPkceVerifierBurnsTheCode()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');

		$this->assertTrue($server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', 'a-wrong-verifier-that-is-long-enough') === null);
		$this->assertTrue(
			$server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier) === null,
			'The code must be spent even though the exchange failed'
		);
	}

	/**
	 * A code minted for one redirect URI cannot be redeemed against another.
	 *
	 * @return void
	 */
	public function testRedirectUriIsBoundToTheCode()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');

		$this->assertTrue($server->exchangeAuthorizationCode($client, $code, 'https://example.org/elsewhere', $this->verifier) === null);
	}

	/**
	 * A code belonging to one client is worthless to another.
	 *
	 * @return void
	 */
	public function testCodeIsBoundToItsClient()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();
		$other = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');

		$this->assertTrue($server->exchangeAuthorizationCode($other, $code, 'https://example.org/callback', $this->verifier) === null);
	}

	/**
	 * No PKCE challenge, no code.
	 *
	 * @return void
	 */
	public function testCodeRequiresAPkceChallenge()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$this->assertTrue($server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', '', 'dolibarr', '') === null);
		$this->assertSame('invalid_request', $server->error);
	}

	/**
	 * Refreshing rotates: the token just used stops working.
	 *
	 * @return void
	 */
	public function testRefreshTokenRotates()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$first = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);

		$second = $server->refreshTokens($client, $first['refresh_token']);
		$this->assertTrue($second !== null);
		$this->assertNotSame($first['access_token'], $second['access_token']);

		$this->assertTrue($server->refreshTokens($client, $first['refresh_token']) === null, 'A spent refresh token must not work again');
	}

	/**
	 * An access token resolves to the consenting user until it expires.
	 *
	 * @return void
	 */
	public function testAccessTokenResolvesToTheConsentingUser()
	{
		global $db, $user;

		$this->requireSchema();

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$tokens = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);

		$row = $server->validateAccessToken($tokens['access_token']);
		$this->assertTrue($row !== null);
		$this->assertSame((int) $user->id, (int) $row->fk_user);

		$this->assertTrue($server->validateAccessToken('dolmcp_a'.str_repeat('0', 64)) === null, 'An unknown token resolves to nothing');

		// Expire it in place rather than waiting an hour.
		$sql = "UPDATE ".$db->prefix()."oauth_token SET expire_at = '".$db->idate(dol_now() - 60)."'";
		$sql .= " WHERE token_hash = '".$db->escape(hash('sha256', $tokens['access_token']))."'";
		$db->query($sql);

		$this->assertTrue($server->validateAccessToken($tokens['access_token']) === null, 'An expired token must stop working');
	}

	/**
	 * A refresh token is not an access token, whatever it is presented as.
	 *
	 * @return void
	 */
	public function testRefreshTokenIsNotAnAccessToken()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$tokens = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);

		$this->assertTrue($server->validateAccessToken($tokens['refresh_token']) === null);
	}

	/**
	 * Our access tokens are recognised by prefix, without a database lookup.
	 *
	 * @return void
	 */
	public function testLooksLikeAccessToken()
	{
		$this->assertTrue(McpOauth::looksLikeAccessToken('dolmcp_aabcdef'));
		$this->assertFalse(McpOauth::looksLikeAccessToken('dolmcp_rabcdef'), 'A refresh token is not one');
		$this->assertFalse(McpOauth::looksLikeAccessToken('dolmcp_gabcdef'), 'An authorization code is not one');
		$this->assertFalse(McpOauth::looksLikeAccessToken('a-plain-user-api-key'));
		$this->assertFalse(McpOauth::looksLikeAccessToken(''));
	}

	/**
	 * A confidential client must prove itself; a public one relies on PKCE.
	 *
	 * @return void
	 */
	public function testClientAuthentication()
	{
		$this->requireSchema();

		$server = $this->getServer();

		$registration = $server->registerClient(array(
			'redirect_uris' => array('https://example.org/callback'),
			'token_endpoint_auth_method' => 'client_secret_basic',
		));
		$client = $server->getClient($registration['client_id']);

		$this->assertTrue($server->authenticateClient($client, $registration['client_secret']));
		$this->assertFalse($server->authenticateClient($client, 'wrong-secret'));
		$this->assertFalse($server->authenticateClient($client, ''), 'An empty secret is not a free pass');

		$public = $this->makeClient();
		$this->assertTrue($server->authenticateClient($public, ''), 'A public client has no secret to present');
	}

	/**
	 * An unknown client_id resolves to nothing (no open redirector).
	 *
	 * @return void
	 */
	public function testUnknownClientIsNotFound()
	{
		$this->requireSchema();

		$server = $this->getServer();

		$this->assertTrue($server->getClient('dolmcp_cdoesnotexist') === null);
		$this->assertTrue($server->getClient('') === null);
	}
	/**
	 * An HTTPS client_id is a metadata document URL; anything else is a registration id.
	 *
	 * @return void
	 */
	public function testMetadataDocumentUrlIsRecognised()
	{
		$this->assertTrue(McpOauth::isMetadataDocumentUrl('https://example.org/client.json'));
		$this->assertFalse(McpOauth::isMetadataDocumentUrl('http://example.org/client.json'), 'Plain http is not a document URL');
		$this->assertFalse(McpOauth::isMetadataDocumentUrl('dolmcp_cabc123'));
		$this->assertFalse(McpOauth::isMetadataDocumentUrl(''));
	}

	/**
	 * Old clients that never obtained a token are purged; the others are kept.
	 *
	 * @return void
	 */
	public function testUnusedClientsArePurgedAndUsedOnesAreNot()
	{
		global $db, $user;

		$this->requireSchema();

		$server = $this->getServer();
		$unused = $this->makeClient();
		$used = $this->makeClient();

		// Give one of them a token, then age both past the cutoff.
		$code = $server->createAuthorizationCode($used, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$server->exchangeAuthorizationCode($used, $code, 'https://example.org/callback', $this->verifier);

		$old = $db->idate(dol_now() - (McpOauth::UNUSED_CLIENT_TTL * 2));
		$db->query("UPDATE ".$db->prefix()."oauth_token SET datec = '".$old."' WHERE service = 'mcp_client' AND rowid IN (".((int) $unused->rowid).", ".((int) $used->rowid).")");

		$server->purgeExpired();

		$this->assertTrue($server->getClient($unused->client_id) === null, 'A client that never obtained a token should be gone');
		$this->assertTrue($server->getClient($used->client_id) !== null, 'A client that obtained a token should be kept');
	}

	/**
	 * Registrations are counted per address so the endpoint can be bounded.
	 *
	 * @return void
	 */
	public function testRegistrationsAreCountedPerAddress()
	{
		global $db;

		$this->requireSchema();

		$server = $this->getServer();
		$before = $server->countRecentRegistrations('203.0.113.7');

		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		$this->makeClient();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.8';
		$this->makeClient();
		unset($_SERVER['REMOTE_ADDR']);

		$this->assertSame($before + 1, $server->countRecentRegistrations('203.0.113.7'), 'Only the registration from that address counts');
		$this->assertSame(0, $server->countRecentRegistrations('203.0.113.'), 'An address is matched whole, not as a prefix');
		$this->assertSame(0, $server->countRecentRegistrations(''), 'No address, nothing to count');
	}
	/**
	 * On loopback only the redirect URI port may differ (RFC 8252 section 7.3).
	 *
	 * @return void
	 */
	public function testLoopbackRedirectUriIgnoresThePort()
	{
		$server = $this->getServer();
		$client = (object) array('redirect_uris' => "http://127.0.0.1/callback\nhttp://localhost/callback");

		$this->assertTrue($server->isRegisteredRedirectUri($client, 'http://127.0.0.1:1455/callback'));
		$this->assertTrue($server->isRegisteredRedirectUri($client, 'http://localhost:64213/callback'));
		$this->assertTrue($server->isRegisteredRedirectUri($client, 'http://127.0.0.1/callback'));

		$this->assertFalse($server->isRegisteredRedirectUri($client, 'http://127.0.0.1:1455/elsewhere'), 'The path still has to match');
		$this->assertFalse($server->isRegisteredRedirectUri($client, 'http://127.0.0.2:1455/callback'), 'Another host is another host');
		$this->assertFalse($server->isRegisteredRedirectUri($client, 'https://127.0.0.1:1455/callback'), 'The scheme still has to match');

		$public = (object) array('redirect_uris' => 'https://example.org/callback');
		$this->assertFalse(
			$server->isRegisteredRedirectUri($public, 'https://example.org:8443/callback'),
			'Off loopback the port is part of the identity'
		);
	}

	/**
	 * The purge never touches rows of other services in the shared llx_oauth_token.
	 *
	 * @return void
	 */
	public function testPurgeLeavesOtherServicesAlone()
	{
		global $conf, $db;

		$this->requireSchema();

		$old = $db->idate(dol_now() - 10 * 86400);
		$sql = "INSERT INTO ".$db->prefix()."oauth_token (service, tokenstring, expire_at, entity, datec)";
		$sql .= " VALUES ('PHPUnitOtherService', 'kept', '".$old."', ".((int) $conf->entity).", '".$old."')";
		$this->assertTrue((bool) $db->query($sql));
		$otherid = $db->last_insert_id($db->prefix().'oauth_token');

		$server = $this->getServer();
		$server->purgeExpired();

		$resql = $db->query("SELECT rowid FROM ".$db->prefix()."oauth_token WHERE rowid = ".((int) $otherid));
		$this->assertSame(1, $db->num_rows($resql), 'A foreign expired token must survive our purge');

		$db->query("DELETE FROM ".$db->prefix()."oauth_token WHERE rowid = ".((int) $otherid));
	}

	/**
	 * Access token uses are counted, as the REST API does for its keys.
	 *
	 * @return void
	 */
	public function testAccessTokenUseIsCounted()
	{
		global $db, $user;

		$this->requireSchema();

		$server = $this->getServer();
		$client = $this->makeClient();
		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$tokens = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);

		$server->validateAccessToken($tokens['access_token']);
		$server->validateAccessToken($tokens['access_token']);

		$resql = $db->query("SELECT apicount_total, apicount_month, lastaccess FROM ".$db->prefix()."oauth_token WHERE token_hash = '".$db->escape(hash('sha256', $tokens['access_token']))."'");
		$obj = $db->fetch_object($resql);
		$this->assertSame(2, (int) $obj->apicount_total);
		$this->assertSame(2, (int) $obj->apicount_month);
		$this->assertNotEmpty($obj->lastaccess);
	}

	/**
	 * A replayed code revokes the tokens already issued from it (RFC 6749 4.1.2).
	 *
	 * @return void
	 */
	public function testReplayedCodeRevokesTheTokensItIssued()
	{
		global $user;

		$this->requireSchema();

		$server = $this->getServer();
		$client = $this->makeClient();
		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');
		$tokens = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);
		$this->assertTrue($server->validateAccessToken($tokens['access_token']) !== null);

		$this->assertTrue($server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier) === null, 'The replay itself is refused');
		$this->assertTrue($server->validateAccessToken($tokens['access_token']) === null, 'Tokens issued from a replayed code must be revoked');
		$this->assertTrue($server->refreshTokens($client, $tokens['refresh_token']) === null, 'Their refresh token too');
	}

	/**
	 * Opaque values read by /authorize with GETPOST() come back intact through the consent form.
	 *
	 * @return void
	 */
	public function testOpaqueParametersComeBackIntact()
	{
		$values = array(
			'state' => array('Xq3_v-9kT0aZ1bC2dE3fG4hI5jK6lM7nO8pQ9rS0tUw', 'aGVsbG8+d29ybGQ/Zm9v/YmFy+cXV4==', '3f2504e0-4f89-11d3-9a0c-0305e82c3301', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.abc_DEF-123'),
			'redirect_uri' => array('https://claude.ai/api/mcp/auth_callback', 'http://127.0.0.1:33418/oauth/callback?x=1&y=2', 'https://x.example/cb?next=%2Fhome%3Fa%3D1'),
			'code_challenge' => array('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM'),
			'resource' => array('https://example.org/ai/server/mcp_server.php'),
		);
		foreach ($values as $name => $list) {
			foreach ($list as $value) {
				$_GET[$name] = $value;
				$read = GETPOST($name, 'alphanohtml', 0, null, null, 1);
				$this->assertSame($value, $read, 'GETPOST must not alter '.$name);
				$posted = html_entity_decode(dolPrintLabelForAttribute($read), ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$this->assertSame($value, $posted, 'The hidden field must give back '.$name);
				unset($_GET[$name]);
			}
		}
	}
}
