<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       test/phpunit/EmailCleanerTest.php
 * \ingroup    test
 * \brief      PHPUnit tests for deterministic email conversation context.
 */

// These unit tests need no configured Dolibarr installation or database connection.
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', sys_get_temp_dir());
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/emailcleaner.class.php';

/**
 * Class for EmailCleaner context tests.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class EmailCleanerTest extends \PHPUnit\Framework\TestCase
{
	/** @var Conf|null Original configuration */
	private $savedConf;

	/** @var User|null Original user */
	private $savedUser;

	/**
	 * Isolate the logging configuration from the rest of the suite.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $conf, $user;
		$this->savedConf = $conf;
		$this->savedUser = $user;
		$conf = new Conf();
		$conf->entity = 2;
		$conf->global->AI_LOG_REQUESTS = 1;
		$user = (object) array('id' => 7);
	}

	/**
	 * Restore the suite configuration.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf, $user;
		$conf = $this->savedConf;
		$user = $this->savedUser;
	}

	/**
	 * Build a database double without opening a connection.
	 *
	 * @return Database|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function createLogDatabase()
	{
		$db = $this->getMockBuilder(Database::class)->setMethods(array_merge(get_class_methods(Database::class), array('prefix')))->getMock();
		$db->method('prefix')->willReturn('llx_');
		$db->method('idate')->willReturn('2026-09-17 12:00:00');
		$db->method('escape')->willReturnCallback(function ($value) {
			return str_replace("'", "''", $value);
		});
		return $db;
	}

	/**
	 * Existing callers keep their return value and the normal payload limit.
	 *
	 * @return void
	 */
	public function testLegacyLoggerCallStillTruncatesPayloads()
	{
		global $user;
		$db = $this->createLogDatabase();
		$raw = 'START'.str_repeat('x', 61000).'END';
		$db->expects($this->once())->method('query')->willReturnCallback(function ($sql) {
			$this->assertStringNotContainsString('fk_actioncomm', $sql);
			$this->assertStringContainsString('[Truncated ', $sql);
			$this->assertStringContainsString('START', $sql);
			$this->assertStringContainsString('END', $sql);
			return true;
		});
		$this->assertSame(0, ai_log_request($db, $user, 'query', array('tool' => 'legacy'), 'provider', 0.0, 0.8, 'Success', '', $raw, $raw));
	}

	/**
	 * Linked output keeps complete JSON, escaped metadata and the inserted id.
	 *
	 * @return void
	 */
	public function testLoggerPreservesLinkedPayloadAndAuditMetadata()
	{
		global $user;
		$db = $this->createLogDatabase();
		$raw = json_encode(array('clean_body' => str_repeat('Résumé ', 10000)), JSON_UNESCAPED_UNICODE);
		$context = array(
			'fk_actioncomm' => 42,
			'input_hash' => "input'quoted",
			'output_hash' => hash('sha256', $raw),
			'security_hash' => 'security',
			'preserve_payloads' => true,
		);
		$db->expects($this->once())->method('query')->willReturnCallback(function ($sql) use ($raw, $context) {
			$this->assertStringContainsString('fk_actioncomm, input_hash, output_hash, security_hash', $sql);
			$this->assertStringContainsString("VALUES (2, '2026-09-17 12:00:00', 7,", $sql);
			$this->assertStringContainsString($raw, $sql);
			$this->assertStringNotContainsString('[Truncated', $sql);
			$this->assertStringContainsString(", 42, 'input''quoted', '".$context['output_hash']."', 'security'", $sql);
			return true;
		});
		$db->expects($this->once())->method('last_insert_id')->with('llx_ai_request_log')->willReturn(123);
		$logId = 0;
		$this->assertSame(0, ai_log_request($db, $user, 'query', array('tool' => 'email_cleaner'), 'fallback', 0.0, 0.8, 'Fallback', '', '{}', $raw, $context, $logId));
		$this->assertSame(123, $logId);
	}

	/**
	 * Disabled logging must never write, even when a caller supplies metadata.
	 *
	 * @return void
	 */
	public function testDisabledLoggerDoesNotWrite()
	{
		global $conf, $user;
		$conf->global->AI_LOG_REQUESTS = 0;
		$db = $this->createLogDatabase();
		$db->expects($this->never())->method('query');
		$db->expects($this->never())->method('last_insert_id');
		$logId = 99;
		$this->assertSame(0, ai_log_request($db, $user, 'query', array(), 'fallback', 0.0, 0.0, 'Fallback', '', '', '', array('fk_actioncomm' => 42), $logId));
		$this->assertSame(0, $logId);
	}

	/**
	 * A failed insert must not expose a previous insert id or render an HTML error.
	 *
	 * @return void
	 */
	public function testFailedLoggerDoesNotReturnStaleId()
	{
		global $user;
		$db = $this->createLogDatabase();
		$db->expects($this->once())->method('query')->willReturn(false);
		$db->method('lasterror')->willReturn('Storage unavailable');
		$db->expects($this->never())->method('last_insert_id');
		$logId = 99;
		$this->expectOutputString('');
		$this->assertSame(0, ai_log_request($db, $user, 'query', array(), 'fallback', 0.0, 0.0, 'Fallback', '', '', '', array(), $logId));
		$this->assertSame(0, $logId);
	}

	/**
	 * Optional metadata without an event must keep the foreign key nullable.
	 *
	 * @return void
	 */
	public function testLoggerAllowsMetadataWithoutEvent()
	{
		global $user;
		$db = $this->createLogDatabase();
		$db->expects($this->once())->method('query')->willReturnCallback(function ($sql) {
			$this->assertStringContainsString(", NULL, 'input', '', ''", $sql);
			return true;
		});
		$this->assertSame(0, ai_log_request($db, $user, 'query', array(), 'fallback', 0.0, 0.0, 'Fallback', '', '', '', array('input_hash' => 'input')));
	}

	/**
	 * Cleaner persistence follows the shared policy and stays within the entity.
	 *
	 * @param bool $loggingEnabled Logging policy
	 * @param int $eventId Matched event id
	 * @param string $expectedStatus Expected fallback status
	 * @return void
	 * @dataProvider cleanerPersistenceCases
	 */
	public function testCleanerUsesSharedLogger($loggingEnabled, $eventId, $expectedStatus)
	{
		global $conf;
		$conf->global->AI_LOG_REQUESTS = (int) $loggingEnabled;
		$conf->ai = (object) array('dir_output' => sys_get_temp_dir().'/dolibarr-emailcleaner-'.uniqid());
		$db = $this->createLogDatabase();
		$db->method('DDLInfoTable')->willReturn(array(array('name' => 'rowid')));
		$db->method('plimit')->willReturn(' LIMIT 1');
		$db->method('fetch_object')->willReturn($eventId ? (object) array('id' => $eventId) : false);
		$shouldWrite = $loggingEnabled && $eventId > 0;
		$db->expects($this->exactly($shouldWrite ? 2 : 1))->method('query')->willReturnCallback(function ($sql) {
			if (strpos($sql, 'SELECT') === 0) {
				$this->assertStringContainsString('entity = 2', $sql);
				$this->assertStringContainsString("email_msgid = 'current@example.test'", $sql);
				$this->assertStringContainsString('LIMIT 1', $sql);
			} else {
				$this->assertStringContainsString('INSERT INTO llx_ai_request_log', $sql);
				$this->assertStringContainsString('fk_actioncomm, input_hash, output_hash, security_hash', $sql);
				$this->assertStringContainsString("'email_cleaner'", $sql);
				$this->assertStringContainsString('"clean_body":"Current answer"', $sql);
			}
			return true;
		});
		$db->expects($shouldWrite ? $this->once() : $this->never())->method('last_insert_id')->willReturn(123);
		$cleaner = $this->getMockBuilder(EmailCleaner::class)->setConstructorArgs(array($db))->setMethods(array('runEmailCleaner'))->getMock();
		$cleaner->method('runEmailCleaner')->willReturn(array('clean_body' => 'Current answer', 'confidence' => 0.8, 'engine' => 'fallback', 'fallback_used' => 1));
		$collector = (object) array('id' => 5);
		try {
			$result = $cleaner->processEmailCollectorMessage(array('messagetext' => 'Current answer', 'header' => 'Message-ID: <current@example.test>'), $collector);
			$this->assertSame($eventId > 0 ? $eventId : null, $result['fk_actioncomm']);
			if ($shouldWrite) {
				$this->assertSame(123, $result['ai_request_log_id']);
				$this->assertSame(123, $result['handoff_payload_json']['ai_request_log_id']);
			} else {
				$this->assertSame($expectedStatus, $result['ai_request_log_status']);
				$this->assertArrayNotHasKey('ai_request_log_id', $result);
			}
			$this->assertFileExists($result['file']);
		} finally {
			dol_delete_dir_recursive($conf->ai->dir_output);
		}
	}

	/**
	 * Cases covering successful persistence, disabled logging and absent events.
	 *
	 * @return array<string,array{bool,int,string}>
	 */
	public function cleanerPersistenceCases()
	{
		return array(
			'linked' => array(true, 42, ''),
			'logging disabled' => array(false, 42, 'not_persisted_logging_disabled'),
			'no event' => array(true, 0, 'not_persisted_no_event'),
		);
	}

	/**
	 * Regex offsets remain byte offsets when messages contain UTF-8 characters.
	 *
	 * @return void
	 */
	public function testUtf8TextBeforeQuotedMessage()
	{
		$body = "Réponse reçue\n\n----- Original Message -----\nFrom: José <sender@example.test>\nSubject: État\n\nDéjà envoyé.";
		$this->assertSame('Réponse reçue', EmailCleaner::extractReplyOnlyTextBasic($body));
		$this->assertStringContainsString('Déjà envoyé.', EmailCleaner::extractQuotedThreadSnippetBasic($body));
		$this->assertSame('État reçu', EmailCleaner::extractHeaderFieldValue("From: José\nSubject: État reçu\nDate: Thursday", 'Subject'));
	}

	/**
	 * A direct parent already present in References must only count once.
	 *
	 * @return void
	 */
	public function testMessageIdLineageIsUnique()
	{
		$references = EmailCleaner::normalizeMessageIdList(array('<root@example.test>', '<parent@example.test>'));
		$ancestors = EmailCleaner::normalizeMessageIdList(array_merge($references, array('<parent@example.test>')));

		$this->assertSame(array('root@example.test', 'parent@example.test'), $ancestors);
		$this->assertSame(
			EmailCleaner::buildThreadKey('first@example.test', '', array('root@example.test')),
			EmailCleaner::buildThreadKey('later@example.test', 'parent@example.test', array('root@example.test', 'parent@example.test'))
		);
	}

	/**
	 * Quoted messages must remain distinct timeline entries.
	 *
	 * @return void
	 */
	public function testQuotedMessagesBuildTechnicalTimeline()
	{
		$body = <<<'EMAIL'
Current answer

----- Original Message -----
From: Agency <agency@example.test>
Sent: Wednesday, 22 July 2026 10:00
Subject: Re: Request 123
Message-ID: <agency-2@example.test>
In-Reply-To: <supplier-1@example.test>
References: <root@example.test> <supplier-1@example.test>

Please proceed.

----- Original Message -----
From: Supplier <supplier@example.test>
Sent: Tuesday, 21 July 2026 09:00
Subject: Re: Request 123
Message-ID: <supplier-1@example.test>
References: <root@example.test>

Previous response.
EMAIL;

		$scope = EmailCleaner::buildMessageScopesBasic(
			$body,
			'Re: Request 123',
			'Supplier <supplier@example.test>',
			'Thu, 23 Jul 2026 08:00:00 +0200',
			'current@example.test',
			'agency-2@example.test',
			array('root@example.test', 'supplier-1@example.test', 'agency-2@example.test')
		);

		$this->assertSame('Current answer', $scope['reply_only']);
		$this->assertCount(2, $scope['quoted_blocks']);
		$this->assertCount(3, $scope['timeline']);
		$this->assertSame('agency-2@example.test', $scope['timeline'][1]['message_id']);
		$this->assertSame('supplier-1@example.test', $scope['timeline'][2]['message_id']);
	}
}
