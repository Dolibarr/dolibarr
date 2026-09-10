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

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/emailcleaner.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class for EmailCleaner context tests.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class EmailCleanerTest extends CommonClassTest
{
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
		$body = "Current answer\n\n"
			."----- Original Message -----\n"
			."From: Agency <agency@example.test>\n"
			."Sent: Wednesday, 22 July 2026 10:00\n"
			."Subject: Re: Request 123\n"
			."Message-ID: <agency-2@example.test>\n"
			."In-Reply-To: <supplier-1@example.test>\n"
			."References: <root@example.test> <supplier-1@example.test>\n\n"
			."Please proceed.\n\n"
			."----- Original Message -----\n"
			."From: Supplier <supplier@example.test>\n"
			."Sent: Tuesday, 21 July 2026 09:00\n"
			."Subject: Re: Request 123\n"
			."Message-ID: <supplier-1@example.test>\n"
			."References: <root@example.test>\n\n"
			."Previous response.";

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
