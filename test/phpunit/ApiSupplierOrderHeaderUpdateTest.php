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

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__.'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/api_bridge.class.php';

/**
 * Exercise metadata, confirmation, precheck and dispatch with local fixtures.
 */
class ApiSupplierOrderHeaderUpdateTest extends PHPUnit\Framework\TestCase
{
	/** @var ApiSupplierOrderHeaderBridgeFixture Bridge fixture */
	private $bridge;
	/** @var mixed Previous translator */
	private $previousLangs;
	/** @var mixed Previous user */
	private $previousUser;

	/** @return void */
	protected function setUp(): void
	{
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
		global $langs, $user;
		$langs = $this->previousLangs;
		$user = $this->previousUser;
		ApiSupplierOrderHeaderEndpointFixture::$fail = false;
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
			$this->assertStringContainsString('array_options.options_reference: "old" → "new"', $preview);
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
			$this->assertStringContainsString($expected, $preview);
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
			$this->assertStringContainsString($expected, $preview);
		}
	}

	/** @return void */
	public function testPreviewShowsReadableDeliveryDatesAndFieldLabels()
	{
		$preview = $this->bridge->writeConfirmationPreview('api_supplier_orders_update', ['id' => 42, 'delivery_date' => 1735776000, 'note_public' => 'Visible note']);
		$this->assertStringContainsString('DeliveryDate: 2024-01-01 00:00:00 UTC → 2025-01-02 00:00:00 UTC', $preview);
		$this->assertStringContainsString('NotePublic: null → "Visible note"', $preview);
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
