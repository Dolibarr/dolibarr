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
 *      \file       test/phpunit/AiMcpAuthTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for ai/class/mcpauth.class.php, which decides
 *                  which Dolibarr user an MCP request runs as: where the
 *                  credential is read from, which credentials are accepted,
 *                  and the permission and audience checks that stand between
 *                  a token and the data.
 */

global $conf,$user,$langs,$db;
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcpauth.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcpoauth.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpAuthTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpAuthTest extends CommonClassTest
{
	/**
	 * Where a credential may be presented.
	 *
	 * The Authorization header is read from several places because Apache in
	 * CGI or FPM mode does not expose it, and the usual workaround republishes
	 * it under another name. Losing that is a 401 with no diagnostic.
	 *
	 * @return void
	 */
	public function testCredentialIsReadFromEveryAcceptedPlace()
	{
		global $db;

		$auth = new McpAuth($db);

		$this->assertSame('k1', $auth->getCredential(array('HTTP_AUTHORIZATION' => 'Bearer k1'), array()));
		$this->assertSame('k2', $auth->getCredential(array('REDIRECT_HTTP_AUTHORIZATION' => 'Bearer k2'), array()));
		$this->assertSame('k3', $auth->getCredential(array('HTTP_DOLAPIKEY' => 'k3'), array()));
		$this->assertSame('k4', $auth->getCredential(array('HTTP_X_API_KEY' => 'k4'), array()));
		$this->assertSame('k5', $auth->getCredential(array(), array('DOLAPIKEY' => 'k5')));
		$this->assertSame('k6', $auth->getCredential(array(), array('api_key' => 'k6')));
		$this->assertSame('', $auth->getCredential(array(), array()));

		// The header wins over the query string, which only exists for clients
		// whose connector UI has nowhere to put one.
		$this->assertSame(
			'header',
			$auth->getCredential(array('HTTP_DOLAPIKEY' => 'header'), array('api_key' => 'query'))
		);
	}

	/**
	 * No credential, and an encrypted value read straight out of the database,
	 * are answered differently: the second is an administrator's mistake worth
	 * naming rather than a silent 401.
	 *
	 * @return void
	 */
	public function testMissingAndEncryptedCredentials()
	{
		global $db;

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array(), array()));
		$this->assertSame(401, $auth->httpcode);

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array('HTTP_DOLAPIKEY' => 'dolcrypt:abcdef'), array()));
		$this->assertSame(503, $auth->httpcode);
	}

	/**
	 * The shared server key still works, and runs as nobody in particular —
	 * the caller falls back to the service user.
	 *
	 * @return void
	 */
	public function testSharedKeyIsAcceptedAndNamesNoUser()
	{
		global $conf, $db;

		$old = getDolGlobalString('AI_MCP_API_KEY');
		$conf->global->AI_MCP_API_KEY = 'shared-key-for-tests';

		$auth = new McpAuth($db);
		$this->assertSame(1, $auth->authenticate(array('HTTP_DOLAPIKEY' => 'shared-key-for-tests'), array()));
		$this->assertSame(McpAuth::MODE_SHARED, $auth->mode);
		$this->assertSame(0, $auth->userid);
		$this->assertNull($auth->user);

		$conf->global->AI_MCP_API_KEY = $old;
	}

	/**
	 * An access token names the user who consented, and that user's rights are
	 * what the request will run with.
	 *
	 * @return void
	 */
	public function testAccessTokenRunsAsTheConsentingUser()
	{
		global $conf, $db, $user;

		$oauth = $this->getOauthServer();
		if ($oauth === null) {
			$this->markTestSkipped('The ai module tables are not installed on this database');
		}

		$tokens = $this->grantTo((int) $user->id, $oauth);

		$auth = new McpAuth($db);
		$this->assertSame(1, $auth->authenticate(array('HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']), array()));
		$this->assertSame(McpAuth::MODE_OAUTH, $auth->mode);
		$this->assertSame((int) $user->id, $auth->userid);
		$this->assertNotNull($auth->user);
	}

	/**
	 * A refresh token is not an access token, and an unknown value is nothing.
	 *
	 * @return void
	 */
	public function testOnlyAccessTokensOpenTheEndpoint()
	{
		global $db, $user;

		$oauth = $this->getOauthServer();
		if ($oauth === null) {
			$this->markTestSkipped('The ai module tables are not installed on this database');
		}

		$tokens = $this->grantTo((int) $user->id, $oauth);

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array('HTTP_AUTHORIZATION' => 'Bearer '.$tokens['refresh_token']), array()));

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array('HTTP_AUTHORIZATION' => 'Bearer dolmcp_a'.str_repeat('0', 64)), array()));
	}

	/**
	 * A token issued for another audience does not open this endpoint, even
	 * though this server minted it. Without the check, a token obtained for
	 * one resource is replayable against another.
	 *
	 * @return void
	 */
	public function testTokenIssuedForAnotherResourceIsRefused()
	{
		global $conf, $db, $user;

		$oauth = $this->getOauthServer();
		if ($oauth === null) {
			$this->markTestSkipped('The ai module tables are not installed on this database');
		}

		$tokens = $this->grantTo((int) $user->id, $oauth);

		// Repoint the token at another audience, which is what a server that
		// did not check the resource at /authorize would have stored.
		$sql = "UPDATE ".$db->prefix()."ai_oauth_token SET resource = 'https://elsewhere.example/mcp'";
		$sql .= " WHERE token_hash = '".$db->escape(hash('sha256', $tokens['access_token']))."'";
		$db->query($sql);

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array('HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']), array()));
		$this->assertStringContainsString('not issued for this server', $auth->error);
	}

	/**
	 * The right every other AI entry point checks also gates this one: a user
	 * who may not talk to the assistant cannot reach it through a token.
	 *
	 * @return void
	 */
	public function testUserWithoutTheAssistantRightIsRefused()
	{
		global $db;

		$oauth = $this->getOauthServer();
		if ($oauth === null) {
			$this->markTestSkipped('The ai module tables are not installed on this database');
		}

		$userid = $this->makeUserWithoutTheRight();
		if ($userid <= 0) {
			$this->markTestSkipped('Could not create a test user');
		}

		$tokens = $this->grantTo($userid, $oauth);

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array('HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']), array()));
		$this->assertSame(403, $auth->httpcode);

		$db->query("DELETE FROM ".$db->prefix()."user WHERE rowid = ".((int) $userid));
	}

	/**
	 * The challenge a 401 carries, which is what turns a refusal into a
	 * sign-in for a client that can do OAuth.
	 *
	 * @return void
	 */
	public function testWwwAuthenticateChallenge()
	{
		global $db;

		$auth = new McpAuth($db);

		$challenge = $auth->getWwwAuthenticateHeader('https://example.org/prm');
		$this->assertStringStartsWith('Bearer ', $challenge);
		$this->assertStringContainsString('resource_metadata="https://example.org/prm"', $challenge);

		// Without a metadata URL the parameters must still follow the scheme
		// properly: "Bearer, scope=..." is malformed (RFC 7235 section 4.1).
		$this->assertStringStartsWith('Bearer scope=', $auth->getWwwAuthenticateHeader(''));
	}

	/**
	 * An authorization server bound to this install, or null when the tables
	 * are absent.
	 *
	 * @return McpOauth|null
	 */
	private function getOauthServer()
	{
		global $db;

		if (!$db->query("SELECT 1 FROM ".$db->prefix()."ai_oauth_client WHERE 1 = 0")) {
			return null;
		}

		return new McpOauth($db, DOL_MAIN_URL_ROOT.'/ai/oauth.php', DOL_MAIN_URL_ROOT.'/ai/server/mcp_server.php');
	}

	/**
	 * Take a user through a full grant and return the tokens.
	 *
	 * @param  int      $userid User consenting
	 * @param  McpOauth $oauth  Authorization server
	 * @return array<string, mixed>
	 */
	private function grantTo($userid, $oauth)
	{
		global $conf;

		$old = getDolGlobalString('AI_MCP_OAUTH_DYNAMIC_REGISTRATION');
		$conf->global->AI_MCP_OAUTH_DYNAMIC_REGISTRATION = '1';

		$registration = $oauth->registerClient(array('redirect_uris' => array('https://example.org/callback')));
		$this->assertNotNull($registration);
		$client = $oauth->getClient($registration['client_id']);

		$verifier = 'jTMZAFeqqAgVv8LRiVUq1IhZTvRIzqQ-3DHsxHJfNyc';
		$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

		$code = $oauth->createAuthorizationCode($client, $userid, 'https://example.org/callback', $challenge, 'dolibarr', '');
		$this->assertNotNull($code);

		$tokens = $oauth->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $verifier);
		$this->assertNotNull($tokens);

		$conf->global->AI_MCP_OAUTH_DYNAMIC_REGISTRATION = $old;

		return $tokens;
	}

	/**
	 * A live user holding no AI right.
	 *
	 * @return int Rowid, or 0
	 */
	private function makeUserWithoutTheRight()
	{
		global $db;

		$login = 'phpunitmcp'.dol_now();

		$sql = "INSERT INTO ".$db->prefix()."user (entity, login, lastname, admin, statut, datec)";
		$sql .= " VALUES (".((int) $GLOBALS['conf']->entity).", '".$db->escape($login)."', 'PHPUnit', 0, 1, '".$db->idate(dol_now())."')";
		if (!$db->query($sql)) {
			return 0;
		}

		return (int) $db->last_insert_id($db->prefix().'user');
	}
}
