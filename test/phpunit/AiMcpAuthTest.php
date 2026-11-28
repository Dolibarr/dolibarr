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
 *      \brief      PHPUnit tests for ai/class/mcpauth.class.php (MCP request authentication).
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
	 * The credential is read from every accepted place (Apache CGI/FPM hides Authorization).
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

		// The header wins over the query string.
		$this->assertSame(
			'header',
			$auth->getCredential(array('HTTP_DOLAPIKEY' => 'header'), array('api_key' => 'query'))
		);
	}

	/**
	 * The admin page probe sees the Authorization header wherever getCredential() reads it.
	 *
	 * @return void
	 */
	public function testProbeSeesTheAuthorizationHeaderWhereverItIsRead()
	{
		$this->assertTrue(McpAuth::seesAuthorizationHeader(array('HTTP_AUTHORIZATION' => 'Bearer x')));
		$this->assertTrue(McpAuth::seesAuthorizationHeader(array('REDIRECT_HTTP_AUTHORIZATION' => 'Bearer x')));
		$this->assertFalse(McpAuth::seesAuthorizationHeader(array()));
	}

	/**
	 * A missing credential gives 401, an encrypted value copied from the database gives 503.
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
	 * The shared server key is accepted and names no user.
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
		$this->assertTrue($auth->user === null);

		$conf->global->AI_MCP_API_KEY = $old;
	}

	/**
	 * An access token runs as the user who consented.
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
		$this->assertTrue($auth->user !== null);
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
	 * A token issued for another audience is refused, so it cannot be replayed here.
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

		// Repoint the token at another audience.
		$sql = "UPDATE ".$db->prefix()."oauth_token SET resource = 'https://elsewhere.example/mcp'";
		$sql .= " WHERE token_hash = '".$db->escape(hash('sha256', $tokens['access_token']))."'";
		$db->query($sql);

		$auth = new McpAuth($db);
		$this->assertSame(-1, $auth->authenticate(array('HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']), array()));
		$this->assertStringContainsString('not issued for this server', $auth->error);
	}

	/**
	 * A user without the AI assistant right is refused, even with a valid token.
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
	 * The WWW-Authenticate challenge sent with a 401.
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

		// Without metadata URL, "Bearer, scope=..." would be malformed (RFC 7235 4.1).
		$this->assertStringStartsWith('Bearer scope=', $auth->getWwwAuthenticateHeader(''));
	}

	/**
	 * An authorization server bound to this install, or null when tables are absent.
	 *
	 * @return McpOauth|null
	 */
	private function getOauthServer()
	{
		global $db;

		if (!$db->query("SELECT token_hash FROM ".$db->prefix()."oauth_token WHERE 1 = 0")) {
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
		$this->assertTrue($registration !== null);
		$client = $oauth->getClient($registration['client_id']);

		$verifier = 'jTMZAFeqqAgVv8LRiVUq1IhZTvRIzqQ-3DHsxHJfNyc';
		$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

		$code = $oauth->createAuthorizationCode($client, $userid, 'https://example.org/callback', $challenge, 'dolibarr', '');
		$this->assertTrue($code !== null);

		$tokens = $oauth->exchangeAuthorizationCode($client, $code, 'https://example.org/callback', $verifier);
		$this->assertTrue($tokens !== null);

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
