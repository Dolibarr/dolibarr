<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/ApiSupplierOrderEmailsTest.php
 * \ingroup test
 * \brief Supplier order email history REST contract without a database or mail transport.
 */

use Luracast\Restler\RestException;

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once __DIR__.'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
$emailHistoryAutoloaders = spl_autoload_functions();
try {
	require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/api_agendaevents.class.php';
} finally {
	foreach (array_reverse($emailHistoryAutoloaders) as $emailHistoryAutoloader) {
		if (!in_array($emailHistoryAutoloader, spl_autoload_functions(), true)) {
			spl_autoload_register($emailHistoryAutoloader, true, true);
		}
	}
	unset($emailHistoryAutoloaders, $emailHistoryAutoloader);
}

/** Verify authorization, pagination and faithful treatment of incomplete history. */
class ApiSupplierOrderEmailsTest extends CommonClassTest
{
	/** @var mixed Previous API user */
	private $previousApiUser;
	/** @var SupplierOrderEmailHistoryFixture Endpoint */
	private $endpoint;

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		$this->previousApiUser = DolibarrApiAccess::$user;
		$actor = $this->createStub(User::class);
		$actor->id = 7;
		$actor->socid = 0;
		$actor->method('hasRight')->willReturn(true);
		DolibarrApiAccess::$user = $actor;
		$this->endpoint = new SupplierOrderEmailHistoryFixture();
		SupplierOrderEmailHistoryFixture::$access = true;
		SupplierOrderEmailHistoryFixture::$accessArgs = array();
		$this->endpoint->order = $this->createMock(CommandeFournisseur::class);
		$this->endpoint->order->id = 42;
		$this->endpoint->order->method('fetch')->with(42)->willReturn(1);
		$this->endpoint->reader = $this->createMock(AgendaEvents::class);
	}

	/** @return void */
	protected function tearDown(): void
	{
		DolibarrApiAccess::$user = $this->previousApiUser;
		parent::tearDown();
	}

	/** @return void */
	public function testHistoryUsesNativeAgendaFiltersAndStablePagination()
	{
		$properties = 'id,datep,label,email_msgid,email_subject,email_from,email_to,email_tocc,email_tobcc';
		$pagination = array('total' => 31, 'page' => 2, 'page_count' => 4, 'limit' => 10);
		$this->endpoint->reader->expects($this->once())->method('index')->with(
			't.datep,t.id', 'DESC,DESC', 10, 2, '',
			"(t.fk_element:=:42) and (t.elementtype:=:'order_supplier') and (t.code:=:'AC_ORDER_SUPPLIER_SENTBYMAIL')",
			$properties, true
		)->willReturn(array('data' => array(), 'pagination' => $pagination));
		$result = $this->endpoint->getEmails('42', '10', '2');
		$this->assertSame($pagination, $result['pagination']);
		$this->assertSame(array(), $result['data']);
		$this->assertFalse($result['history_complete']);
		$this->assertSame('agenda', $result['source']);
		$this->assertSame(array('fournisseur', 42, 'commande_fournisseur', 'commande'), SupplierOrderEmailHistoryFixture::$accessArgs);
	}

	/** @return void */
	public function testRecordedSubjectBodyAndAddressesAreReturnedWithoutRewriting()
	{
		$event = (object) array(
			'id' => '12', 'datep' => 1700000000, 'label' => 'Mail sent',
			'email_msgid' => '<original@example.org>', 'email_subject' => 'PO-42 delivery?',
			'email_from' => 'sender@example.org', 'email_to' => 'supplier@example.org',
			'email_tocc' => 'copy@example.org', 'email_tobcc' => 'archive@example.org',
			'note_private' => '<p>Please confirm delivery.</p>', 'unrelated_private_data' => 'secret'
		);
		$this->endpoint->reader->expects($this->atLeastOnce())->method('index')->willReturn(array('data' => array($event), 'pagination' => array()));
		$summary = $this->endpoint->getEmails(42)['data'][0];
		$this->assertArrayNotHasKey('body', $summary);
		$this->assertArrayNotHasKey('event_note', $summary);
		$email = $this->endpoint->getEmails(42, 10, 0, 1)['data'][0];
		$this->assertSame($event->email_subject, $email['subject']);
		$this->assertSame($event->note_private, $email['body']);
		$this->assertSame('agenda_note', $email['body_source']);
		$this->assertSame($event->email_msgid, $email['message_id']);
		foreach (array('from', 'to', 'cc', 'bcc') as $field) {
			$property = 'email_'.($field === 'cc' || $field === 'bcc' ? 'to'.$field : $field);
			$this->assertSame($event->$property, $email[$field]);
		}
		$this->assertSame('email', $email['record_type']);
		$this->assertNull($email['attachments']);
		$this->assertNull($email['delivery_status']);
		$this->assertArrayNotHasKey('unrelated_private_data', $email);
	}

	/** @return void */
	public function testGenericLogIsNotInventedEmailContent()
	{
		$event = (object) array('id' => 13, 'datep' => 1700000000, 'label' => 'Order sent by email', 'note_private' => 'Supplier order PO-42 sent by email');
		$this->endpoint->reader->expects($this->atLeastOnce())->method('index')->willReturn(array('data' => array($event), 'pagination' => array()));
		$email = $this->endpoint->getEmails(42, 10, 0, 1)['data'][0];
		$this->assertSame('event_only', $email['record_type']);
		$this->assertSame($event->note_private, $email['event_note']);
		foreach (array('subject', 'body', 'message_id', 'from', 'to', 'cc', 'bcc', 'attachments', 'delivery_status') as $field) {
			$this->assertNull($email[$field], $field);
		}
	}

	/** @return void */
	public function testBodyProjectionAndAgendaErrorsArePreserved()
	{
		$this->endpoint->reader->expects($this->once())->method('index')->with(
			't.datep,t.id', 'DESC,DESC', 10, 0, '',
			"(t.fk_element:=:42) and (t.elementtype:=:'order_supplier') and (t.code:=:'AC_ORDER_SUPPLIER_SENTBYMAIL')",
			'id,datep,label,email_msgid,email_subject,email_from,email_to,email_tocc,email_tobcc,note_private', true
		)->willThrowException(new RestException(503, 'Agenda unavailable'));
		$this->expectException(RestException::class);
		$this->expectExceptionCode(503);
		$this->endpoint->getEmails(42, 10, 0, 1);
	}

	/** @return void */
	public function testInvalidArgumentsNeverReadAnOrderOrAgenda()
	{
		$this->endpoint->order->expects($this->never())->method('fetch');
		$this->endpoint->reader->expects($this->never())->method('index');
		foreach (array(
			array(0), array(-1), array('42 OR 1=1'), array(42.5), array(true), array(array(42)),
			array(42, 0), array(42, 51), array(42, '1.5'), array(42, 10, -1),
			array(42, 10, PHP_INT_MAX), array(42, 10, 0, 2), array(42, 10, 0, 'true')
		) as $args) {
			try {
				$this->endpoint->getEmails(...$args);
				$this->fail('Invalid history parameters accepted');
			} catch (RestException $e) {
				$this->assertSame(400, $e->getCode());
			}
		}
	}

	/** @return void */
	public function testBothReadPermissionsAndInternalUserAreRequired()
	{
		$this->endpoint->order->expects($this->never())->method('fetch');
		$this->endpoint->reader->expects($this->never())->method('index');
		foreach (array('fournisseur', 'agenda', 'external') as $denied) {
			$actor = $this->createStub(User::class);
			$actor->socid = $denied === 'external' ? 3 : 0;
			$actor->method('hasRight')->willReturnMap(array(
				array('fournisseur', 'commande', 'lire', $denied !== 'fournisseur'),
				array('agenda', 'myactions', 'read', $denied !== 'agenda')
			));
			DolibarrApiAccess::$user = $actor;
			try {
				$this->endpoint->getEmails(42);
				$this->fail('Insufficient rights accepted');
			} catch (RestException $e) {
				$this->assertSame(403, $e->getCode());
			}
		}
	}

	/** @return void */
	public function testInaccessibleOrderIsRefusedBeforeReadingHistory()
	{
		SupplierOrderEmailHistoryFixture::$access = false;
		$this->endpoint->reader->expects($this->never())->method('index');
		$this->expectException(RestException::class);
		$this->expectExceptionCode(403);
		$this->endpoint->getEmails(42);
	}

	/** @return void */
	public function testMissingOrderAndDatabaseFailureAreDistinct()
	{
		$this->endpoint->reader->expects($this->never())->method('index');
		foreach (array(0 => 404, -1 => 503) as $fetchResult => $status) {
			$this->endpoint->order = $this->createMock(CommandeFournisseur::class);
			$this->endpoint->order->expects($this->once())->method('fetch')->willReturn($fetchResult);
			try {
				$this->endpoint->getEmails(42);
				$this->fail('Missing order accepted');
			} catch (RestException $e) {
				$this->assertSame($status, $e->getCode());
			}
		}
	}
}

/** Isolate persistence while executing the real REST method. */
class SupplierOrderEmailHistoryFixture extends SupplierOrders
{
	/** @var bool Resource access decision */
	public static $access = true;
	/** @var array Resource check arguments */
	public static $accessArgs = array();
	/** @var AgendaEvents Agenda reader */
	public $reader;

	/** Constructor without database access. */
	public function __construct()
	{
	}

	/** @return AgendaEvents Agenda reader */
	protected function supplierOrderEmailAgenda()
	{
		return $this->reader;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * @param string $resource Resource
	 * @param mixed $resource_id ID
	 * @param string $dbtablename Table
	 * @param string $feature2 Subfeature
	 * @param string $dbt_keyfield Third party column
	 * @param string $dbt_select Primary key
	 * @param string $parenttableforentity Parent table
	 * @return bool Allowed
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $parenttableforentity = '')
	{
		self::$accessArgs = array($resource, $resource_id, $dbtablename, $feature2);
		return self::$access;
	}
	// phpcs:enable
}
