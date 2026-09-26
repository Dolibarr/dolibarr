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
 *      \brief      PHPUnit tests for the MCP authorization server
 *                  (ai/class/mcpoauth.class.php): PKCE, one-shot authorization
 *                  codes, refresh rotation, redirect URI matching and the rules
 *                  that decide which redirect URIs may be registered at all.
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
	 * Make sure the ai module tables exist, creating them if they do not.
	 *
	 * The CI database enables a fixed list of modules and ai is not among
	 * them, so these tables are absent there. Skipping would leave every
	 * security property of this server — PKCE, one-shot codes, refresh
	 * rotation, client binding — unverified on the only run that gates a
	 * merge, which is worse than the small cost of creating two tables from
	 * the very files the module installs.
	 *
	 * @return void
	 */
	private function requireSchema()
	{
		global $db;

		if ($db->query("SELECT 1 FROM ".$db->prefix()."ai_oauth_client WHERE 1 = 0")) {
			return;
		}

		foreach (array('llx_ai_oauth_client-ai', 'llx_ai_oauth_token-ai') as $name) {
			foreach (array($name.'.sql', $name.'.key.sql') as $file) {
				$path = dirname(__FILE__).'/../../htdocs/install/mysql/tables/'.$file;
				if (!file_exists($path)) {
					// The schema lives in its own pull request (#40762), as the
					// rule here is that a pull request adding tables is reviewed
					// on its own. Until it is merged and this branch rebased,
					// everything below the schema line cannot run.
					$this->markTestSkipped('Needs the schema of PR #40762: missing '.$file);
				}
				// Comments first, then split: the licence header these files carry
				// contains semicolons of its own.
				$sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($path));
				foreach (explode(';', (string) $sql) as $statement) {
					$statement = trim($statement);
					if ($statement !== '') {
						$db->query(str_replace('llx_', $db->prefix(), $statement));
					}
				}
			}
		}

		if (!$db->query("SELECT 1 FROM ".$db->prefix()."ai_oauth_client WHERE 1 = 0")) {
			$this->markTestSkipped('Could not create the ai module tables on this database');
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
		$this->assertNotNull($registration, 'Registration should have succeeded');

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

		// This document is the RFC 8414 one, and it must stay free of the
		// OpenID fields: carrying them is a claim to be an OpenID provider,
		// which this is not — opaque access tokens, never an id_token,
		// nothing signed, no openid scope — and the claude.ai and ChatGPT
		// connectors refuse a document that makes it. The three are served on
		// the openid-configuration route instead, where the caller asked for
		// OpenID and validates what it gets as such (htdocs/ai/oauth.php).
		$this->assertArrayNotHasKey('jwks_uri', $as);
		$this->assertArrayNotHasKey('subject_types_supported', $as);
		$this->assertArrayNotHasKey('id_token_signing_alg_values_supported', $as);
		$this->assertNotContains('openid', $as['scopes_supported'], 'The openid scope is not offered');

		$prm = $server->metadataProtectedResource();
		$this->assertSame('https://example.org/ai/server/mcp_server.php', $prm['resource']);
		$this->assertSame(array('https://example.org/ai/oauth.php'), $prm['authorization_servers']);

		// With self-onboarding off, an unknown client must not be invited to
		// try either of the two ways in. Advertising them anyway sends every
		// client down a path that answers 403, and an administrator who
		// turned the setting off would reasonably expect neither to be
		// offered.
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
	 * Which redirect URIs may be registered at all. The ones refused here are
	 * the ones that would let an authorization code land somewhere else.
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
	 * Redirect URIs are compared whole. Prefix matching is how a code ends up
	 * on someone else's server.
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
		$this->assertNotNull($code);

		$tokens = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);
		$this->assertNotNull($tokens, 'A correct exchange should return tokens');
		$this->assertArrayHasKey('access_token', $tokens);
		$this->assertArrayHasKey('refresh_token', $tokens);
		$this->assertSame('Bearer', $tokens['token_type']);

		$replay = $server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier);
		$this->assertNull($replay, 'The same code must not be exchangeable twice');
		$this->assertSame('invalid_grant', $server->error);
	}

	/**
	 * A wrong verifier is refused, and burns the code on the way: the attacker
	 * who guessed wrong does not get a second try, and neither does anyone else.
	 *
	 * @return void
	 */
	public function testWrongPkceVerifierBurnsTheCode()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$code = $server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', $this->challenge(), 'dolibarr', '');

		$this->assertNull($server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', 'a-wrong-verifier-that-is-long-enough'));
		$this->assertNull(
			$server->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $this->verifier),
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

		$this->assertNull($server->exchangeAuthorizationCode($client, $code, 'https://example.org/elsewhere', $this->verifier));
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

		$this->assertNull($server->exchangeAuthorizationCode($other, $code, 'https://example.org/callback', $this->verifier));
	}

	/**
	 * No PKCE challenge, no code. A code exchangeable without a verifier is a
	 * code an interceptor can exchange.
	 *
	 * @return void
	 */
	public function testCodeRequiresAPkceChallenge()
	{
		global $user;

		$server = $this->getServer();
		$client = $this->makeClient();

		$this->assertNull($server->createAuthorizationCode($client, (int) $user->id, 'https://example.org/callback', '', 'dolibarr', ''));
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
		$this->assertNotNull($second);
		$this->assertNotSame($first['access_token'], $second['access_token']);

		$this->assertNull($server->refreshTokens($client, $first['refresh_token']), 'A spent refresh token must not work again');
	}

	/**
	 * An access token resolves to the user who consented, and only while it is
	 * meant to.
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
		$this->assertNotNull($row);
		$this->assertSame((int) $user->id, (int) $row->fk_user);

		$this->assertNull($server->validateAccessToken('dolmcp_a'.str_repeat('0', 64)), 'An unknown token resolves to nothing');

		// Expire it in place rather than waiting an hour.
		$sql = "UPDATE ".$db->prefix()."ai_oauth_token SET expires_at = '".$db->idate(dol_now() - 60)."'";
		$sql .= " WHERE token_hash = '".$db->escape(hash('sha256', $tokens['access_token']))."'";
		$db->query($sql);

		$this->assertNull($server->validateAccessToken($tokens['access_token']), 'An expired token must stop working');
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

		$this->assertNull($server->validateAccessToken($tokens['refresh_token']));
	}

	/**
	 * What the prefix test is for: recognising our own access tokens without
	 * asking the database about every credential that comes in.
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
	 * A confidential client must prove itself; a public one has nothing to prove
	 * and is protected by PKCE instead.
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
	 * An unknown client_id resolves to nothing, which is what keeps the
	 * authorize endpoint from being used as an open redirector.
	 *
	 * @return void
	 */
	public function testUnknownClientIsNotFound()
	{
		$this->requireSchema();

		$server = $this->getServer();

		$this->assertNull($server->getClient('dolmcp_cdoesnotexist'));
		$this->assertNull($server->getClient(''));
	}
	/**
	 * A client_id that is an HTTPS URL is a document to fetch; anything else is
	 * a registration to look up. Getting this wrong either breaks metadata
	 * document clients or turns every unknown id into an outbound request.
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
	 * /register takes no credential, so a registration that never led to a
	 * token is collected. One that did is kept whatever its age.
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
		$db->query("UPDATE ".$db->prefix()."ai_oauth_client SET datec = '".$old."' WHERE rowid IN (".((int) $unused->rowid).", ".((int) $used->rowid).")");

		$server->purgeExpired();

		$this->assertNull($server->getClient($unused->client_id), 'A client that never obtained a token should be gone');
		$this->assertNotNull($server->getClient($used->client_id), 'A client that obtained a token should be kept');
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

		$sql = "INSERT INTO ".$db->prefix()."ai_oauth_client";
		$sql .= " (entity, client_id, client_name, redirect_uris, token_endpoint_auth_method, registered_from, datec)";
		$sql .= " VALUES (1, 'dolmcp_ctest".dol_now()."', '', 'https://example.org/cb', 'none', '203.0.113.7', '".$db->idate(dol_now())."')";
		$db->query($sql);

		$this->assertSame($before + 1, $server->countRecentRegistrations('203.0.113.7'));
		$this->assertSame(0, $server->countRecentRegistrations(''), 'No address, nothing to count');
	}
	/**
	 * A native client listens on a port the operating system hands it when it
	 * starts, so it cannot publish that port in its metadata. RFC 8252 section
	 * 7.3 requires the port to be free at request time on loopback — Codex
	 * registers http://127.0.0.1/callback and asks for
	 * http://127.0.0.1:1455/callback.
	 *
	 * Only the port may differ, and only on loopback.
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
}
