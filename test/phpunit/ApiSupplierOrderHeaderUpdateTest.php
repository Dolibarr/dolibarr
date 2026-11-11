<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/ApiSupplierOrderHeaderUpdateTest.php
 * \ingroup test
 * \brief Partial header updates through the AI bridge, without database writes.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/api_bridge.class.php';

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
$emailSendLoaders = spl_autoload_functions();
try {
	require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';
} finally {
	foreach (array_reverse($emailSendLoaders) as $emailSendLoader) {
		if (!in_array($emailSendLoader, spl_autoload_functions(), true)) {
			spl_autoload_register($emailSendLoader, true, true);
		}
	}
	unset($emailSendLoaders, $emailSendLoader);
}


/**
 * Exercise metadata, confirmation, precheck and dispatch with local fixtures.
 */
class ApiSupplierOrderHeaderUpdateTest extends CommonClassTest
{
	/** @var SupplierOrderMailSendFixture Email service */
	private $service;
	/** @var CommandeFournisseur Email order */
	private $order;
	/** @var User Email actor */
	private $actor;
	/** @var mixed Previous API user */
	private $previousApiUser;
	/** @var mixed Previous root URL */
	private $previousRootUrl;
	/** @return void */
	private function setUpEmail(): void
	{
		global $conf, $dolibarr_main_url_root;
		$this->previousRootUrl = $dolibarr_main_url_root;
		$dolibarr_main_url_root = 'http://localhost';
		$this->previousApiUser = DolibarrApiAccess::$user;
		$conf = clone $conf;
		$conf->global = clone $conf->global;
		$conf->entity = 1;
		$conf->modules = array('supplier_order' => 1);
		$conf->global->MAIN_DISABLE_ALL_MAILS = 0;
		$conf->global->MAIN_MAIL_FORCE_CONTENT_TYPE_TO_HTML = 0;
		$conf->global->SUPPLIER_ORDER_SENDBYEMAIL_FOR_ALL_STATUS = 0;
		$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
		$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO = '';
		$conf->global->MAIN_MAIL_FORCE_SENDTO = '';
		$conf->global->MAIN_MAIL_FORCE_FROM = '';
		$conf->global->MAIN_MAIL_FORCE_NOT_SENDING_TO = '';
		$this->actor = $this->createStub(User::class);
		$this->actor->id = 7;
		$this->actor->socid = 0;
		$this->actor->email = 'buyer@example.org';
		$this->actor->method('hasRight')->willReturn(true);
		DolibarrApiAccess::$user = $this->actor;
		$this->order = new CommandeFournisseur(null);
		$this->order->id = 42;
		$this->order->ref = 'PO-42';
		$this->order->entity = 1;
		$this->order->socid = 3;
		$this->order->status = 2;
		$this->order->lines = array();
		$this->service = new SupplierOrderMailSendFixture(null);
		SupplierOrderMailTransportFixture::$attempts = 0;
		SupplierOrderMailTransportFixture::$succeed = true;
		SupplierOrderMailTransportFixture::$throw = false;
	}

	/** @return array<string,mixed> Message input */
	private function message()
	{
		return array('subject' => 'Delivery of PO-42', 'body' => "Please confirm delivery.\nThank you.", 'to' => array('supplier@example.org'));
	}

	/** @return void */
	public function testEmailTemplateDefaultSelectionRenderingAndExplicitChoice()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$this->order = $this->createStub(CommandeFournisseur::class);
		$this->order->id = 42;
		$this->order->ref = 'PO-42';
		$this->order->lines = array();
		$this->order->thirdparty = (object) array('email' => 'supplier@example.org');
		$this->order->method('fetch_thirdparty')->willReturn(1);
		$this->actor->signature = 'Buyer';
		foreach (array(0, 8) as $id) {
			$form = $this->createMock(FormMail::class);
			$form->substit = array('__REF__' => 'PO-42');
			$form->substit_lines = array();
			$template = (object) array('id' => 8, 'label' => 'Purchase order', 'topic' => 'Order __REF__',
				'content' => 'Hello<br>Order __REF__<br>__SENDEREMAIL_SIGNATURE__', 'content_lines' => '',
				'email_from' => '', 'email_tocc' => 'copy@example.org', 'email_tobcc' => '', 'joinfiles' => 1);
			$form->expects($this->once())->method('getEMailTemplate')->with(null, 'order_supplier_send', $this->actor,
				$this->anything(), $id, 1, '', $id ? -1 : 1)->willReturn($template);
			$this->service->form = $form;
			$prepared = $this->service->prepare($this->order, $this->actor, array('template_id' => $id));
			$this->assertSame('Order PO-42', $prepared['preview']['subject']);
			$this->assertStringContainsString("Order PO-42\nBuyer", $prepared['preview']['body']);
			$this->assertSame('copy@example.org', $prepared['preview']['cc']);
			$this->assertSame(array(), $prepared['preview']['attachments']);
			$this->assertTrue($prepared['preview']['template']['attachment_suggested']);
		}
		$form = $this->createStub(FormMail::class);
		$form->method('getEMailTemplate')->willReturn((object) array('id' => 0));
		$this->service->form = $form;
		$this->expectException(\Luracast\Restler\RestException::class);
		$this->expectExceptionCode(404);
		$this->service->prepare($this->order, $this->actor, array('template_id' => 99));
	}

	/** @return void */
	public function testEmailUnsafeInputAndNativeAttachmentValidation()
	{
		$this->service->realFiles = true;
		foreach (array(
			array('subject' => "Hello\r\nBcc: someone@example.org"), array('body' => ''), array('from_type' => 'arbitrary'),
			array('to' => array("a@example.org\nBcc: b@example.org")), array('bcc' => 'secret@example.org'),
			array('attachments' => array('../secret.pdf')), array('attachments' => array('/tmp/secret.pdf')),
			array('attachments' => array('secret.txt')), array('unknown' => 1), array('template_id' => -1)
		) as $changes) {
			try {
				$this->service->prepare($this->order, $this->actor, array_replace($this->message(), $changes));
				$this->fail('Unsafe input accepted');
			} catch (\Luracast\Restler\RestException $e) {
				$this->assertSame(400, $e->getCode());
			}
		}
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
	}

	/** @return void */
	public function testEmailNativePdfConstructionUsesExistingFileOnly()
	{
		global $conf;
		$this->service->realFiles = true;
		$this->service->native = true;
		$args = $this->message();
		$args['attachments'] = array('Expense report - Bill Restaurant.pdf');
		foreach (array('mail', 'smtps', 'swiftmailer') as $mode) {
			$conf->global->MAIN_MAIL_SENDMODE = $mode;
			$prepared = $this->service->prepare($this->order, $this->actor, $args);
			$this->assertSame(hash_file('sha256', __DIR__.'/../fixtures/'.$args['attachments'][0]), $prepared['preview']['attachments'][0]['sha256']);
			$this->assertSame('text', $prepared['preview']['format']);
			if ($mode === 'swiftmailer') {
				$cache = new ReflectionProperty(Swift_Mime_SimpleMimeEntity::class, 'cache');
				if (PHP_VERSION_ID < 80100) {
					$cache->setAccessible(true);
				}
				$this->assertInstanceOf(Swift_KeyCache_ArrayKeyCache::class, $cache->getValue($this->service->lastMail->message));
				$attachments = 0;
				foreach ($this->service->lastMail->message->getChildren() as $child) {
					if ($child instanceof Swift_Mime_Attachment) {
						$attachments++;
						$this->assertSame(file_get_contents(__DIR__.'/../fixtures/'.$args['attachments'][0]), $child->getBody());
						$this->assertInstanceOf(Swift_KeyCache_ArrayKeyCache::class, $cache->getValue($child));
					}
				}
				$this->assertSame(1, $attachments);
			}
		}
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
	}

	/** @return void */
	public function testEmailDisabledTransportAndHistoryFailure()
	{
		global $conf;
		$args = $this->message();
		$prepared = $this->service->prepare($this->order, $this->actor, $args);
		$conf->global->MAIN_DISABLE_ALL_MAILS = 1;
		$this->assertEmailRefused($args, $prepared['expected_hash'], 409);
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
		$conf->global->MAIN_DISABLE_ALL_MAILS = 0;
		$this->service->recordSuccess = false;
		$result = $this->service->send($this->order, $this->actor, $args, $prepared['expected_hash']);
		$this->assertFalse($result['history_recorded']);
		$this->assertSame(1, SupplierOrderMailTransportFixture::$attempts);
	}

	/** @return void */
	public function testEmailAgendaAcceptsContextAndPreservesLegacyCallers()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modAgenda_ActionsAuto.class.php';
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$trigger = new InterfaceActionsAuto(null);
		$copy = new ReflectionMethod($trigger, 'setEmailFields');
		if (PHP_VERSION_ID < 80100) {
			$copy->setAccessible(true);
		}
		$event = $this->createStub(ActionComm::class);
		$fields = array('email_msgid' => 'message@example.org', 'email_subject' => 'Purchase order',
			'email_from' => 'buyer@example.org', 'email_reply_to' => 'reply@example.org',
			'email_to' => 'supplier@example.org', 'email_tocc' => 'copy@example.org',
			'email_tobcc' => 'archive@example.org', 'errors_to' => 'errors@example.org');
		$this->order->context = $fields;
		$copy->invoke($trigger, $event, $this->order);
		foreach ($fields as $field => $value) {
			$this->assertSame($value, $event->$field);
			$this->assertFalse(property_exists($this->order, $field));
		}
		$legacy = $this->createStub(ActionComm::class);
		foreach ($fields as $field => $value) {
			$legacy->$field = $value;
		}
		$copy->invoke($trigger, $event, $legacy);
		foreach ($fields as $field => $value) {
			$this->assertSame($value, $event->$field);
		}
		$legacy->context = array('email_subject' => 'New subject', 'email_tobcc' => '');
		$copy->invoke($trigger, $event, $legacy);
		$this->assertSame('New subject', $event->email_subject);
		$this->assertNull($event->email_tobcc);
		$this->assertSame($fields['email_to'], $event->email_to);
	}

	/** @return void */
	public function testEmailStatelessPreparationAndExplicitRepeat()
	{
		$args = $this->message();
		$prepared = $this->service->prepare($this->order, $this->actor, $args);
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
		$this->assertSame($args['body'], $prepared['preview']['body']);
		$this->assertArrayNotHasKey('preparation_token', $prepared);
		foreach (array(1, 2) as $attempt) {
			$result = $this->service->send($this->order, $this->actor, $args, $prepared['expected_hash']);
			$this->assertSame('sent', $result['status']);
			$this->assertSame('unknown', $result['delivery_status']);
			$this->assertSame($attempt, SupplierOrderMailTransportFixture::$attempts);
		}
	}

	/** @return void */
	public function testEmailChangesRequireReviewAgain()
	{
		global $conf;
		$args = $this->message();
		$args['attachments'] = array('PO-42.pdf');
		foreach (array('order', 'recipient', 'attachment', 'sender', 'body', 'actor', 'entity') as $change) {
			$prepared = $this->service->prepare($this->order, $this->actor, $args);
			if ($change === 'order') {
				$this->order->ref .= '-changed';
			} elseif ($change === 'recipient') {
				$this->service->forcedRecipient = 'other@example.org';
			} elseif ($change === 'attachment') {
				$this->service->content .= 'changed';
			} elseif ($change === 'sender') {
				$this->actor->email = 'other-buyer@example.org';
			} elseif ($change === 'actor') {
				$this->actor->id++;
			} elseif ($change === 'entity') {
				$conf->entity++;
			} else {
				$args['body'] .= ' changed';
			}
			$this->assertEmailRefused($args, $prepared['expected_hash'], 409);
		}
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
	}

	/** @return void */
	public function testEmailTransportFailuresAreNeverRetriedInternally()
	{
		$args = $this->message();
		$prepared = $this->service->prepare($this->order, $this->actor, $args);
		SupplierOrderMailTransportFixture::$succeed = false;
		$this->assertEmailRefused($args, $prepared['expected_hash'], 502);
		$this->assertSame(1, SupplierOrderMailTransportFixture::$attempts);
		SupplierOrderMailTransportFixture::$throw = true;
		$this->assertEmailRefused($args, $prepared['expected_hash'], 502);
		$this->assertSame(2, SupplierOrderMailTransportFixture::$attempts);
	}

	/** @return void */
	public function testEmailReplyAccessAndOwnershipAreRechecked()
	{
		$args = $this->message();
		$args['reply_to_event_id'] = 12;
		$this->service->reply = (object) array('elementid' => 42, 'elementtype' => 'order_supplier',
			'code' => 'AC_ORDER_SUPPLIER_SENTBYMAIL', 'email_msgid' => 'previous@example.org');
		$prepared = $this->service->prepare($this->order, $this->actor, $args);
		$this->assertSame('<previous@example.org>', $prepared['preview']['in_reply_to']);
		$this->service->reply->elementid = 43;
		$this->assertEmailRefused($args, $prepared['expected_hash'], 400);
		$this->service->reply->elementid = 42;
		$this->service->reply->email_msgid = 'changed@example.org';
		$this->assertEmailRefused($args, $prepared['expected_hash'], 409);
		$this->service->denyReply = true;
		$this->assertEmailRefused($args, $prepared['expected_hash'], 403);
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
	}

	/** @param array<string,mixed> $args Message
	 * @param string $hash Fingerprint
	 * @param int $code Expected status
	 * @return void
	 */
	private function assertEmailRefused(array $args, $hash, $code)
	{
		try {
			$this->service->send($this->order, $this->actor, $args, $hash);
			$this->fail('Send should be refused');
		} catch (\Luracast\Restler\RestException $e) {
			$this->assertSame($code, $e->getCode());
		}
	}

	/** @return void */
	public function testEmailNativeLoggingRecordsActualMessageAndRollsBackFailures()
	{
		global $conf;
		$conf->modules['agenda'] = 1;
		$conf->global->MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_SENTBYMAIL = 1;
		$database = new SupplierOrderMailLogDatabaseFixture();
		$service = new SupplierOrderMailSendFixture($database);
		$mail = new SupplierOrderMailTransportFixture();
		$mail->addr_from = 'sender@example.org';
		$mail->addr_to = 'supplier@example.org';
		$mail->addr_cc = 'cc@example.org';
		$mail->addr_bcc = 'bcc@example.org';
		foreach (array(1, -1) as $triggerResult) {
			$order = $this->createMock(CommandeFournisseur::class);
			$order->context = array('existing' => 'preserved');
			$order->expects($this->once())->method('call_trigger')->with('ORDER_SUPPLIER_SENTBYMAIL', $this->actor)->willReturnCallback(function () use ($order, $triggerResult) {
				$this->assertSame('Delivery of PO-42', $order->context['email_subject']);
				$this->assertSame('supplier@example.org', $order->context['email_to']);
				$this->assertSame('bcc@example.org', $order->context['email_tobcc']);
				return $triggerResult;
			});
			$this->assertSame($triggerResult > 0, $service->logNative($order, $this->actor, $this->message(), $mail));
			$this->assertSame(array('existing' => 'preserved'), $order->context);
			$this->assertFalse(property_exists($order, 'email_subject'));
			$this->assertStringContainsString('Please confirm delivery.', $order->actionmsg);
		}
		$this->assertSame(1, $database->commits);
		$this->assertSame(1, $database->rollbacks);
	}


	/** @return void */
	public function testEmailNativeMailPreviewIncludesGlobalAndSupplierCopiesAndOverrides()
	{
		global $conf;
		$conf->global->MAIN_MAIL_SENDMODE = 'mail';
		$conf->global->MAIN_MAIL_AUTOCOPY_TO = 'global@example.org';
		$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO = 'supplier-archive@example.org';
		$this->service->native = true;
		$prepared = $this->service->prepare($this->order, $this->actor, $this->message());
		$this->assertStringContainsString('global@example.org', $prepared['preview']['bcc']);
		$this->assertStringContainsString('supplier-archive@example.org', $prepared['preview']['bcc']);
		$conf->global->MAIN_MAIL_FORCE_SENDTO = 'sandbox@example.org';
		$prepared = $this->service->prepare($this->order, $this->actor, $this->message());
		$this->assertSame('sandbox@example.org', $prepared['preview']['to']);
		$this->assertSame('', $prepared['preview']['bcc']);
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
	}


	/** @return void */
	public function testEmailRestRechecksStatusAndRightsAndForbidsEditingTheSendBody()
	{
		$endpoint = new SupplierOrderEmailEndpointFixture();
		$endpoint->service = $this->service;
		$endpoint->order = $this->createMock(CommandeFournisseur::class);
		$endpoint->order->id = 42;
		$endpoint->order->ref = 'PO-42';
		$endpoint->order->lines = array();
		$endpoint->order->status = 2;
		$endpoint->order->method('fetch')->with(42)->willReturn(1);
		$prepared = $endpoint->prepareEmail(42, $this->message());
		foreach (array('status', 'rights', 'access', 'external', 'body') as $denial) {
			$endpoint->order->status = 2;
			DolibarrApiAccess::$user = $this->actor;
			$send = array('message' => $this->message(), 'expected_hash' => $prepared['expected_hash']);
			if ($denial === 'status') {
				$endpoint->order->status = 0;
			} elseif ($denial === 'access') {
				SupplierOrderEmailEndpointFixture::$allowed = false;
			} elseif ($denial === 'body') {
				$send['body'] = 'Unconfirmed change';
			} else {
				$actor = $this->createStub(User::class);
				$actor->id = 7;
				$actor->socid = $denial === 'external' ? 3 : 0;
				$actor->method('hasRight')->willReturn($denial === 'external');
				DolibarrApiAccess::$user = $actor;
			}
			try {
				$endpoint->sendEmail(42, $send);
				$this->fail('Send should be refused');
			} catch (\Luracast\Restler\RestException $e) {
				$this->assertContains($e->getCode(), array(400, 403, 409));
			}
			SupplierOrderEmailEndpointFixture::$allowed = true;
		}
		$this->assertSame(0, SupplierOrderMailTransportFixture::$attempts);
	}


	/** @var ApiSupplierOrderHeaderBridgeFixture Bridge fixture */
	private $bridge;
	/** @var mixed Previous translator */
	private $previousLangs;
	/** @var mixed Previous user */
	private $previousUser;

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		if (strpos(method_exists($this, 'name') ? $this->name() : $this->getName(), 'testEmail') === 0) {
			$this->setUpEmail();
			return;
		}

		global $langs, $user;
		$this->previousLangs = $langs;
		$this->previousUser = $user;
		$langs = new ApiSupplierOrderHeaderLanguageFixture();
		$this->bridge = new ApiSupplierOrderHeaderBridgeFixture(null, new ApiSupplierOrderHeaderUserFixture());
		ApiSupplierOrderHeaderEndpointFixture::$received = [];
		ApiSupplierOrderHeaderEndpointFixture::$fail = false;
	}

	/** @return void */
	protected function tearDown(): void
	{
		if (strpos(method_exists($this, 'name') ? $this->name() : $this->getName(), 'testEmail') === 0) {
			global $dolibarr_main_url_root;
			$dolibarr_main_url_root = $this->previousRootUrl;
			DolibarrApiAccess::$user = $this->previousApiUser;
			parent::tearDown();
			return;
		}

		global $langs, $user;
		$langs = $this->previousLangs;
		$user = $this->previousUser;
		ApiSupplierOrderHeaderEndpointFixture::$fail = false;

		parent::tearDown();
	}

	/** @return void */
	public function testExecutionRestoresAutoloadersAfterSuccessAndFailure()
	{
		$autoloaders = spl_autoload_functions();
		$includePath = get_include_path();
		foreach ([false, true, false] as $fail) {
			ApiSupplierOrderHeaderEndpointFixture::$fail = $fail;
			$result = $this->bridge->execute('api_supplier_orders_update', ['id' => 42, 'note_private' => 'new']);
			$this->assertSame($autoloaders, spl_autoload_functions());
			$this->assertSame($includePath, get_include_path());
			if ($fail) {
				$this->assertSame('Endpoint fixture failed', $result['error']);
			} else {
				$this->assertSame(['id' => 42], $result);
			}
		}
	}

	/** @return void */
	public function testFlatObjectAndJsonInputsProduceTheSamePartialBody()
	{
		$changes = ['note_private' => '', 'delivery_date' => null, 'array_options' => ['options_reference' => 'new']];
		foreach ([array_merge(['id' => 42], $changes), ['id' => 42, 'request_data' => $changes], ['ref' => 'PO-42', 'request_data' => json_encode($changes)]] as $args) {
			$this->assertSame('', $this->bridge->writePrecheck('api_supplier_orders_update', $args));
			$result = $this->bridge->execute('api_supplier_orders_update', $args);
			$this->assertSame(['id' => 42], $result);
			$this->assertSame(['id' => 42, 'body' => $changes], ApiSupplierOrderHeaderEndpointFixture::$received);
			$this->assertArrayNotHasKey('qty', ApiSupplierOrderHeaderEndpointFixture::$received['body']);
			$this->assertArrayNotHasKey('subprice', ApiSupplierOrderHeaderEndpointFixture::$received['body']);
		}
	}

	/** @return void */
	public function testExtrafieldsOnlyBodyIsNotUnwrapped()
	{
		$changes = ['array_options' => ['options_reference' => 'new']];
		foreach ([array_merge(['id' => 42], $changes), ['id' => 42, 'request_data' => $changes], ['id' => 42, 'request_data' => json_encode($changes)]] as $args) {
			$this->assertSame('', $this->bridge->writePrecheck('api_supplier_orders_update', $args));
			$this->bridge->execute('api_supplier_orders_update', $args);
			$this->assertSame($changes, ApiSupplierOrderHeaderEndpointFixture::$received['body']);
			$preview = $this->bridge->writeConfirmationPreview('api_supplier_orders_update', $args);
			$this->assertStringContainsString(dol_escape_htmltag('array_options.options_reference: "old" → "new"'), $preview);
			$this->assertStringNotContainsString('options_other', $preview);
			$this->assertStringNotContainsString('{', $preview);
		}
	}

	/** @return void */
	public function testZeroAndNullArePreservedAndConfirmationStateIsNotPartOfTheBody()
	{
		$changes = ['delivery_date' => 0, 'fk_project' => null, 'note_private' => '', 'array_options' => ['options_zero' => 0, 'options_null' => null, 'options_empty' => '']];
		$args = array_merge(['id' => 42, 'requestState' => 'confirmation-state'], $changes);
		$this->assertSame('', $this->bridge->writePrecheck('api_supplier_orders_update', $args));
		$this->bridge->execute('api_supplier_orders_update', $args);
		$this->assertSame($changes, ApiSupplierOrderHeaderEndpointFixture::$received['body']);
		$preview = $this->bridge->writeConfirmationPreview('api_supplier_orders_update', $args);
		foreach (['1970-01-01 00:00:00 UTC', 'Project: null → null', 'NotePrivate: null → ""', 'options_zero: null → 0', 'options_null: null → null', 'options_empty: null → ""'] as $expected) {
			$this->assertStringContainsString(dol_escape_htmltag($expected), $preview);
		}
	}

	/** @return void */
	public function testCreationDefaultsAndRenamesCannotAlterAnUpdateBody()
	{
		$metadata = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$metadata->setAccessible(true);
		}
		$enrichments = $metadata->getValue($this->bridge);
		$enrichments['supplier_orders']['methods']['put']['write']['defaults'] = ['qty' => 1, 'note_public' => 'default'];
		$enrichments['supplier_orders']['methods']['put']['write']['rename'] = ['note_private' => 'note_public'];
		$metadata->setValue($this->bridge, $enrichments);
		$this->bridge->execute('api_supplier_orders_update', ['id' => 42, 'note_private' => '']);
		$this->assertSame(['note_private' => ''], ApiSupplierOrderHeaderEndpointFixture::$received['body']);
	}

	/** @return void */
	public function testInvalidBodiesAndFieldsAreRejectedBeforeExecution()
	{
		$invalid = [
			['id' => 42], ['id' => 42, 'request_data' => '{}'], ['id' => 42, 'request_data' => '{invalid'],
			['id' => 42, 'request_data' => '[]'], ['id' => 42, 'request_data' => 'null'], ['id' => 42, 'request_data' => '[{"note_private":"new"}]'],
			['id' => 42, 'request_data' => ['status' => 0]], ['id' => 42, 'total_ht' => 10], ['id' => 42, 'socid' => 2],
			['id' => 42, 'lines' => []], ['id' => 42, 'fk_product' => 8], ['id' => 42, 'note_private' => null],
			['id' => 42, 'request_data' => null], ['id' => 42, 'request_data' => 0], ['id' => 42, 'request_data' => false],
			['id' => 42, 'note_public' => 0], ['id' => 42, 'note_public' => false], ['id' => 42, 'ref_supplier' => null],
			['id' => 42, 'delivery_date' => '1800000000'], ['id' => 42, 'delivery_date' => -1], ['id' => 42, 'fk_project' => 0],
			['id' => 42, 'delivery_date' => false], ['id' => 42, 'fk_project' => '7'], ['id' => 42, 'fk_project' => -1],
			['id' => 42, 'array_options' => null], ['id' => 42, 'array_options' => 0], ['id' => 42, 'array_options' => ['unexpected']],
			['id' => 42, 'request_data' => ['id' => 42, 'note_private' => 'new']],
			['id' => 42, 'note_private' => 'flat', 'request_data' => ['note_private' => 'body']]
		];
		foreach ($invalid as $args) {
			$this->assertNotSame('', $this->bridge->writePrecheck('api_supplier_orders_update', $args), json_encode($args));
			$this->assertArrayHasKey('error', $this->bridge->execute('api_supplier_orders_update', $args));
			$this->assertSame([], ApiSupplierOrderHeaderEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testExactlyOneExistingIdentifierIsRequired()
	{
		foreach ([[], ['id' => 0], ['id' => '42'], ['id' => 42.5], ['ref' => ''], ['id' => 42, 'ref' => 'PO-42'], ['id' => 99], ['ref' => 'UNKNOWN']] as $identifier) {
			$args = array_merge($identifier, ['note_private' => 'new']);
			$this->assertNotSame('', $this->bridge->writePrecheck('api_supplier_orders_update', $args));
			$this->assertArrayHasKey('error', $this->bridge->execute('api_supplier_orders_update', $args));
		}
		$this->assertSame([], ApiSupplierOrderHeaderEndpointFixture::$received);
	}

	/** @return void */
	public function testReadWriteAndResourcePermissionsAreRequired()
	{
		foreach (['lire', 'creer'] as $denied) {
			$actingUser = new ApiSupplierOrderHeaderUserFixture();
			$actingUser->denied = $denied;
			$bridge = new ApiSupplierOrderHeaderBridgeFixture(null, $actingUser);
			$this->assertNotSame('', $bridge->writePrecheck('api_supplier_orders_update', ['id' => 42, 'note_private' => 'new']));
		}
		$this->bridge->allowAccess = false;
		$this->assertNotSame('', $this->bridge->writePrecheck('api_supplier_orders_update', ['id' => 42, 'note_private' => 'new']));
		$this->assertArrayHasKey('error', $this->bridge->execute('api_supplier_orders_update', ['id' => 42, 'note_private' => 'new']));
		$this->bridge->allowAccess = true;
		$this->bridge->allowProject = false;
		$this->assertNotSame('', $this->bridge->writePrecheck('api_supplier_orders_update', ['id' => 42, 'fk_project' => 7]));
		$this->assertSame([], ApiSupplierOrderHeaderEndpointFixture::$received);
	}

	/** @return void */
	public function testPreviewNamesTheOrderSupplierAndOldAndNewValues()
	{
		$preview = $this->bridge->writeConfirmationPreview('api_supplier_orders_update', ['ref' => 'PO-42', 'ref_supplier' => 'NEW']);
		foreach (['PO-42', 'Example supplier', 'OLD', 'NEW', '→'] as $expected) {
			$this->assertStringContainsString(dol_escape_htmltag($expected), $preview);
		}
	}

	/** @return void */
	public function testPreviewShowsReadableDeliveryDatesAndFieldLabels()
	{
		$preview = $this->bridge->writeConfirmationPreview('api_supplier_orders_update', ['id' => 42, 'delivery_date' => 1735776000, 'note_public' => 'Visible note']);
		$this->assertStringContainsString(dol_escape_htmltag('DeliveryDate: 2024-01-01 00:00:00 UTC → 2025-01-02 00:00:00 UTC'), $preview);
		$this->assertStringContainsString(dol_escape_htmltag('NotePublic: null → "Visible note"'), $preview);
		$this->assertStringNotContainsString('1704067200', $preview);
		$this->assertStringNotContainsString('1735776000', $preview);
	}

	/** @return void */
	public function testPreviewHandlesAnOrderThatDisappearsAfterValidation()
	{
		$this->bridge->failParentAfter = 1;
		$this->assertSame('Record not found', $this->bridge->writeConfirmationPreview('api_supplier_orders_update', ['id' => 42, 'note_private' => 'new']));
		$this->assertSame([], ApiSupplierOrderHeaderEndpointFixture::$received);
	}

	/** @return void */
	public function testSchemaAndRightsComeFromTheWriteMetadata()
	{
		$metadata = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$metadata->setAccessible(true);
		}
		$write = $metadata->getValue($this->bridge)['supplier_orders']['methods']['put']['write'];
		$schemaMethod = new ReflectionMethod(ToolApiBridge::class, 'updateInputSchema');
		if (PHP_VERSION_ID < 80100) {
			$schemaMethod->setAccessible(true);
		}
		$schema = $schemaMethod->invoke($this->bridge, $write);
		$this->assertFalse($schema['additionalProperties']);
		$this->assertArrayHasKey('id', $schema['properties']);
		$this->assertArrayHasKey('ref', $schema['properties']);
		$this->assertArrayNotHasKey('status', $schema['properties']);
		$this->assertArrayNotHasKey('fk_product', $schema['properties']);
		$this->assertCount(2, $schema['oneOf']);
		$this->assertSame([['required' => ['id']], ['required' => ['ref']]], $schema['oneOf']);
		$bodySchemas = $schema['properties']['request_data']['oneOf'];
		$this->assertSame('object', $bodySchemas[0]['type']);
		$this->assertFalse($bodySchemas[0]['additionalProperties']);
		$this->assertSame($write['update'], $bodySchemas[0]['properties']);
		$this->assertSame(1, $bodySchemas[0]['minProperties']);
		$this->assertSame('string', $bodySchemas[1]['type']);
		$this->assertContains(['required' => ['request_data']], $schema['anyOf']);
		foreach (array_keys($write['update']) as $field) {
			$this->assertContains(['required' => [$field]], $schema['anyOf']);
		}
		$this->assertSame([['fournisseur', 'commande', 'creer'], ['fournisseur', 'commande', 'lire']], $this->bridge->getRequiredRights('api_supplier_orders_update'));
	}
}

/** Fixture for deterministic user permissions. */
class ApiSupplierOrderHeaderUserFixture
{
	/** @var string Permission denied by this fixture */
	public $denied = '';
	/** @param string $module Module
	 * @param string $object Object
	 * @param string $right Permission
	 * @return bool Whether the permission is granted
	 */
	public function hasRight($module, $object, $right)
	{
		return $right !== $this->denied;
	}
}

/** Translator fixture that keeps all preview values visible. */
class ApiSupplierOrderHeaderLanguageFixture
{
	/** @param string $file Language file
	 * @return void
	 */
	public function load($file)
	{
	}
	/** @param string $key Translation key
	 * @param mixed ...$values Substitution values
	 * @return string Preview text
	 */
	public function trans($key, ...$values)
	{
		return $key.($values ? ' '.implode(' ', $values) : '');
	}
	/** @param string $key Translation key
	 * @param mixed ...$values Substitution values
	 * @return string Preview text
	 */
	public function transnoentitiesnoconv($key, ...$values)
	{
		return $this->trans($key, ...$values);
	}
}

/** Bridge fixture replacing database reads and endpoint discovery, with isolated loaders. */
class ApiSupplierOrderHeaderBridgeFixture extends ToolApiBridge
{
	/**
	 * Keep the real dispatch while isolating Restler's process-wide loader changes.
	 *
	 * @param string $name Tool name
	 * @param array<string,mixed> $args Tool arguments
	 * @return mixed Tool result
	 */
	public function execute(string $name, array $args)
	{
		$autoloaders = spl_autoload_functions();
		$includePath = get_include_path();
		try {
			return parent::execute($name, $args);
		} finally {
			foreach (spl_autoload_functions() as $autoloader) {
				spl_autoload_unregister($autoloader);
			}
			foreach ($autoloaders as $autoloader) {
				spl_autoload_register($autoloader);
			}
			set_include_path($includePath);
		}
	}

	/** @var bool Parent visibility */
	public $allowAccess = true;
	/** @var bool Project visibility */
	public $allowProject = true;
	/** @var int Number of successful parent fetches before the record disappears, or 0 */
	public $failParentAfter = 0;
	/** @var int Number of parent fetches */
	private $parentFetches = 0;

	/** @param string $class Business class
	 * @param int $id Object ID
	 * @param string $ref Object reference
	 * @return object|null Matching fixture
	 */
	protected function fetchBusinessObject($class, $id, $ref = '')
	{
		if ($class === 'Societe') {
			return (object) ['id' => 8, 'name' => 'Example supplier'];
		}
		$this->parentFetches++;
		if ($this->failParentAfter && $this->parentFetches > $this->failParentAfter) {
			return null;
		}
		if ($id !== 42 && $ref !== 'PO-42') {
			return null;
		}
		return (object) ['id' => 42, 'ref' => 'PO-42', 'socid' => 8, 'status' => 3, 'ref_supplier' => 'OLD', 'delivery_date' => 1704067200, 'array_options' => ['options_reference' => 'old', 'options_other' => 'keep']];
	}

	/** @param array<int,string> $access Resource arguments
	 * @param int $id Object ID
	 * @return bool Visibility granted by the fixture
	 */
	protected function canAccessWriteObject(array $access, $id)
	{
		return $access[0] === 'projet' ? $this->allowProject : $this->allowAccess;
	}

	/** @return array<int,array<string,mixed>> No external definitions */
	public function getDefinitions(): array
	{
		$routes = new ReflectionProperty(ToolApiBridge::class, 'routes');
		if (PHP_VERSION_ID < 80100) {
			$routes->setAccessible(true);
		}
		$routes->setValue($this, ['api_supplier_orders_update' => ['supplier_orders', 'put']]);
		$endpoints = new ReflectionProperty(ToolApiBridge::class, 'endpoints');
		if (PHP_VERSION_ID < 80100) {
			$endpoints->setAccessible(true);
		}
		$endpoints->setValue($this, ['supplier_orders' => ['path' => __FILE__, 'class' => ApiSupplierOrderHeaderEndpointFixture::class]]);
		return [];
	}
}

/** Endpoint fixture capturing a REST call without persistence. */
class ApiSupplierOrderHeaderEndpointFixture
{
	/** @var array<string,mixed> Last request received */
	public static $received = [];
	/** @var bool Simulate an endpoint failure */
	public static $fail = false;
	/** @param int $id Parent ID
	 * @param array<string,mixed> $request_data Changes
	 * @return array<string,int> Updated record identity
	 */
	public function put($id, $request_data = null)
	{
		if (self::$fail) {
			throw new RuntimeException('Endpoint fixture failed');
		}
		self::$received = ['id' => $id, 'body' => $request_data];
		return ['id' => $id];
	}
}

/** Transport seam: no test can deliver real mail or create temporary files. */
class SupplierOrderMailSendFixture extends SupplierOrderMail
{
	public $forcedRecipient = '';
	public $records = 0;
	public $recordSuccess = true;
	public $native = false;
	public $reply;
	public $denyReply = false;
	public $content = '%PDF-1.4 original';
	public $form;
	public $realFiles = false;
	public $lastMail;

	/** @return FormMail Native form fixture */
	protected function mailForm()
	{
		return $this->form ?: parent::mailForm();
	}

	/** @param CommandeFournisseur $order Order
	 * @return string Existing fixtures directory
	 */
	protected function attachmentDirectory($order)
	{
		return __DIR__.'/../fixtures';
	}

	/** @param int $eventId Event ID
	 * @return object Accessible event
	 */
	protected function replyEvent($eventId)
	{
		if ($this->denyReply) {
			throw new \Luracast\Restler\RestException(403, 'Agenda permission revoked');
		}
		return $this->reply;
	}

	/** @param CommandeFournisseur $order Order
	 * @param mixed[] $names Names
	 * @return array<array{name:string,content:string,path:string}> In-memory documents
	 */
	protected function readAttachments($order, array $names)
	{
		if ($this->realFiles) {
			return parent::readAttachments($order, $names);
		}
		return $names ? array(array('name' => $names[0], 'content' => $this->content, 'path' => '/unused.pdf')) : array();
	}

	/** @param CMailFile $mail Test transport
	 * @param User $actor User
	 * @return bool Acceptance
	 */
	protected function transmit($mail, $actor)
	{
		if (!($mail instanceof SupplierOrderMailTransportFixture)) {
			throw new RuntimeException('A real transport is forbidden in this test');
		}
		return parent::transmit($mail, $actor);
	}

	/** @param CommandeFournisseur $order Order
	 * @param User $actor User
	 * @param array<string,mixed> $message Content
	 * @param CMailFile $mail Transport
	 * @return bool Native log result
	 */
	public function logNative($order, $actor, array $message, $mail)
	{
		return parent::recordSent($order, $actor, $message, $mail);
	}

	/** @param CommandeFournisseur $order Order
	 * @param User $actor User
	 * @param array<string,mixed> $message Content
	 * @param string[] $paths Paths
	 * @param string[] $names Names
	 * @return CMailFile Test transport
	 */
	protected function buildMail($order, $actor, array $message, array $paths, array $names)
	{
		if ($this->native) {
			$this->lastMail = parent::buildMail($order, $actor, $message, $paths, $names);
			return $this->lastMail;
		}
		$mail = new SupplierOrderMailTransportFixture();
		$mail->addr_from = $message['from'];
		$mail->reply_to = $message['from'];
		$mail->addr_to = $this->forcedRecipient ?: $message['to'];
		$mail->addr_cc = $message['cc'];
		$mail->addr_bcc = $message['bcc'];
		return $mail;
	}

	/** @param CommandeFournisseur $order Order
	 * @param User $actor User
	 * @param array<string,mixed> $message Content
	 * @param CMailFile $mail Transport
	 * @return bool Recording result
	 */
	protected function recordSent($order, $actor, array $message, $mail)
	{
		$this->records++;
		return $this->recordSuccess;
	}
}

/** Never calls a real transport. */
class SupplierOrderMailTransportFixture extends CMailFile
{
	public static $attempts = 0;
	public static $succeed = true;
	public static $throw = false;

	/** Constructor without native mail initialization. */
	public function __construct()
	{
		$this->msgid = 'fixture@example.org';
	}

	/** @return bool Simulated transport result */
	public function sendfile()
	{
		self::$attempts++;
		if (self::$throw) {
			throw new RuntimeException('Simulated interruption');
		}
		return self::$succeed;
	}
}

/** Execute the real REST checks with a resource decision fixture. */
class SupplierOrderEmailEndpointFixture extends SupplierOrders
{
	public $service;
	public static $allowed = true;

	/** No database constructor. */
	public function __construct()
	{
	}

	/**
	 * @return SupplierOrderMailSendFixture Test service
	 */
	protected function emailService()
	{
		return $this->service;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * @param string $resource Resource
	 * @param mixed $resource_id Record ID
	 * @param string $dbtablename Table
	 * @param string $feature2 Subfeature
	 * @param string $dbt_keyfield Third party column
	 * @param string $dbt_select Primary key
	 * @param string $parenttableforentity Parent table
	 * @return bool Access decision
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $parenttableforentity = '')
	{
		return self::$allowed;
	}
	// phpcs:enable
}

/** Transaction recorder; never connects to a database. */
class SupplierOrderMailLogDatabaseFixture
{
	public $commits = 0;
	public $rollbacks = 0;

	/** @return int Success */
	public function begin()
	{
		return 1;
	}

	/** @return int Success */
	public function commit()
	{
		$this->commits++;
		return 1;
	}

	/** @return int Success */
	public function rollback()
	{
		$this->rollbacks++;
		return 1;
	}
}
