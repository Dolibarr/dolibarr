<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiProjectUpdateTest.php
 * \ingroup test
 * \brief Bounded project writes through the AI bridge, without persistence.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/api_bridge.class.php';

/** Exercise update validation, permissions, confirmation and endpoint dispatch. */
class AiProjectUpdateTest extends CommonClassTest
{
	/** @var AiProjectUpdateBridgeFixture Bridge fixture */
	private $bridge;
	/** @var mixed Previous translator */
	private $previousLangs;
	/** @var mixed Previous user */
	private $previousUser;
	/** @var mixed Previous configuration */
	private $previousConf;
	/** @var string Original timezone */
	private $previousTimezone;

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();

		global $langs, $user, $conf;
		$this->previousLangs = $langs;
		$this->previousUser = $user;
		$this->previousConf = $conf;
		$this->previousTimezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$langs = new AiProjectUpdateLanguageFixture();
		$conf = (object) ['global' => new stdClass(), 'cache' => [], 'modules_parts' => ['hooks' => []]];
		$this->bridge = new AiProjectUpdateBridgeFixture(null, new AiProjectUpdateUserFixture());
		AiProjectUpdateEndpointFixture::$received = [];
		AiProjectUpdateEndpointFixture::$customResult = false;
		AiProjectUpdateEndpointFixture::$result = null;
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $langs, $user, $conf;
		$langs = $this->previousLangs;
		$user = $this->previousUser;
		$conf = $this->previousConf;
		date_default_timezone_set($this->previousTimezone);

		parent::tearDown();
	}

	/** @return void */
	public function testFlatObjectAndJsonProjectInputsProduceTheSamePartialBody()
	{
		$changes = ['title' => '  Revised project  ', 'note_private' => '', 'date_end' => null];
		$expected = ['title' => 'Revised project', 'note_private' => '', 'date_end' => null];
		foreach ([array_merge(['id' => 7], $changes), ['id' => 7, 'request_data' => $changes], ['ref' => 'PJ-7', 'request_data' => json_encode($changes)]] as $args) {
			$this->assertSame('', $this->bridge->writePrecheck('api_projects_update', $args));
			$this->assertSame(['success' => true, 'id' => 7, 'ref' => 'PJ-7'], $this->bridge->execute('api_projects_update', $args));
			$this->assertSame(['id' => 7, 'body' => $expected], AiProjectUpdateEndpointFixture::$received);
			$this->assertArrayNotHasKey('status', AiProjectUpdateEndpointFixture::$received['body']);
			$this->assertArrayNotHasKey('fk_soc', AiProjectUpdateEndpointFixture::$received['body']);
		}
	}

	/** @return void */
	public function testTextIsTrimmedBeforeConfirmationAndDispatch()
	{
		foreach (['api_projects_update' => 7] as $tool => $id) {
			$args = ['id' => $id, 'description' => '  Updated description  ', 'note_public' => "\n Public note \n", 'note_private' => '   '];
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$preview = $this->bridge->writeConfirmationPreview($tool, $args);
			$this->assertStringContainsString('"Updated description"', $preview);
			$this->assertStringNotContainsString('  Updated description  ', $preview);
			$this->bridge->execute($tool, $args);
			$this->assertSame(['description' => 'Updated description', 'note_public' => 'Public note', 'note_private' => ''], AiProjectUpdateEndpointFixture::$received['body']);
		}
	}

	/** @return void */
	public function testInvalidProjectFieldsAndBodiesAreRejectedBeforeExecution()
	{
		foreach ([
			[], ['request_data' => '{}'], ['request_data' => '[]'], ['request_data' => '{invalid'], ['request_data' => 'null'],
			['request_data' => []], ['request_data' => null], ['request_data' => false], ['request_data' => 0],
			['request_data' => '[{"title":"New"}]'], ['title' => ''], ['title' => '   '], ['title' => null], ['title' => false],
			['title' => str_repeat('x', 256)], ['description' => null], ['note_public' => false], ['note_private' => 0],
			['date_start' => 0], ['date_end' => -1], ['date_start' => '1735689600'], ['date_end' => 1.5], ['date_end' => false],
			['status' => 1], ['statut' => 1], ['fk_statut' => 1], ['public' => 1], ['fk_soc' => 2], ['socid' => 2],
			['ref' => 'RENAMED'], ['array_options' => ['options_test' => 'new']], ['lines' => []],
			['request_data' => ['title' => 'Body'], 'title' => 'Flat'], ['request_data' => ['id' => 7, 'title' => 'New']]
		] as $changes) {
			$args = array_merge(['id' => 7], $changes);
			$this->assertNotSame('', $this->bridge->writePrecheck('api_projects_update', $args), json_encode($args));
			$this->assertArrayHasKey('error', $this->bridge->execute('api_projects_update', $args));
			$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testExactlyOneExistingProjectIdentifierIsRequired()
	{
		foreach (['api_projects_update' => 7] as $tool => $id) {
			$ref = 'PJ-7';
			foreach ([[], ['id' => 0], ['id' => (string) $id], ['id' => 7.5], ['ref' => ''], ['ref' => '   '], ['id' => $id, 'ref' => $ref], ['id' => 99], ['ref' => 'UNKNOWN']] as $identifier) {
				$args = array_merge($identifier, ['note_private' => 'New']);
				$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args), json_encode($args));
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			}
		}
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testExistingDatesParticipateInValidation()
	{
		foreach (['api_projects_update' => 7] as $tool => $id) {
			foreach ([['date_start' => 1735862400], ['date_end' => 1703980800], ['date_start' => 1735862400, 'date_end' => 1735776000]] as $changes) {
				$args = array_merge(['id' => $id], $changes);
				$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			}
			$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
			$args = ['id' => $id, 'date_start' => 1735776000, 'date_end' => 1735776000];
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$this->bridge->execute($tool, $args);
			$this->assertSame(['date_start' => 1735776000, 'date_end' => 1735776000], AiProjectUpdateEndpointFixture::$received['body']);
			AiProjectUpdateEndpointFixture::$received = [];
		}
	}

	/** @return void */
	public function testNullDatesArePreservedWithoutCreationDefaults()
	{
		$metadata = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$metadata->setAccessible(true);
		}
		$enrichments = $metadata->getValue($this->bridge);
		foreach (['projects'] as $endpoint) {
			$enrichments[$endpoint]['methods']['put']['write']['defaults'] = ['status' => 1, 'note_public' => 'Default'];
			$enrichments[$endpoint]['methods']['put']['write']['rename'] = ['note_private' => 'note_public'];
		}
		$metadata->setValue($this->bridge, $enrichments);
		foreach (['api_projects_update' => 7] as $tool => $id) {
			$changes = ['date_start' => null, 'date_end' => null, 'note_private' => ''];
			$args = array_merge(['id' => $id, 'requestState' => 'confirmation-state'], $changes);
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$this->bridge->execute($tool, $args);
			$this->assertSame(['id' => $id, 'body' => $changes], AiProjectUpdateEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testReadWriteAndRecordVisibilityAreAllRequired()
	{
		foreach (['api_projects_update' => 7] as $tool => $id) {
			foreach (['lire', 'creer'] as $denied) {
				$actingUser = new AiProjectUpdateUserFixture();
				$actingUser->denied = $denied;
				$bridge = new AiProjectUpdateBridgeFixture(null, $actingUser);
				$args = ['id' => $id, 'note_private' => 'New'];
				$this->assertNotSame('', $bridge->writePrecheck($tool, $args));
				$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
			}
			$this->bridge->allowAccess = false;
			$args = ['id' => $id, 'note_private' => 'New'];
			$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
			$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			$this->bridge->allowAccess = true;
		}
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testReadAllDoesNotGrantProjectWriteAccess()
	{
		$this->bridge->allowProjectWrite = false;
		foreach (['api_projects_update' => 7] as $tool => $id) {
			$args = ['id' => $id, 'note_private' => 'New'];
			$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
			$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
		}
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testClosedOrUnknownProjectStatusIsRejected()
	{
		foreach ([2, -1, 99, null, 'oops', false, '00'] as $status) {
			$this->bridge->projectStatus = $status;
			foreach (['api_projects_update' => 7] as $tool => $id) {
				$args = ['id' => $id, 'note_private' => 'New'];
				$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			}
		}
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
		foreach ([0, 1] as $status) {
			$this->bridge->projectStatus = $status;
			$this->assertSame('', $this->bridge->writePrecheck('api_projects_update', ['id' => 7, 'note_private' => 'New']));
		}
	}

	/** @return void */
	public function testMaximumTitleLengthIsAccepted()
	{
		foreach (['api_projects_update' => ['id' => 7, 'title' => str_repeat('x', 255)]] as $tool => $args) {
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$this->assertArrayNotHasKey('error', $this->bridge->execute($tool, $args));
		}
	}

	/** @return void */
	public function testPermissionsAndVisibilityAreRevalidatedAfterConfirmationPreview()
	{
		foreach (['api_projects_update' => 7] as $tool => $id) {
			$args = ['id' => $id, 'note_private' => 'New'];
			foreach (['lire', 'creer'] as $denied) {
				$actingUser = new AiProjectUpdateUserFixture();
				$bridge = new AiProjectUpdateBridgeFixture(null, $actingUser);
				$this->assertNotSame('', $bridge->writeConfirmationPreview($tool, $args));
				$actingUser->denied = $denied;
				$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
			}
			foreach (['allowAccess', 'allowProjectAccess', 'allowProjectWrite', 'projectExists'] as $flag) {
				$bridge = new AiProjectUpdateBridgeFixture(null, new AiProjectUpdateUserFixture());
				$this->assertNotSame('', $bridge->writeConfirmationPreview($tool, $args));
				$bridge->$flag = false;
				$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
			}
			$bridge = new AiProjectUpdateBridgeFixture(null, new AiProjectUpdateUserFixture());
			$this->assertNotSame('', $bridge->writeConfirmationPreview($tool, $args));
			$bridge->projectStatus = 2;
			$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
		}
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testNativeProjectWriteAuthorizationUsesTheActingUserAndWriteMode()
	{
		$actingUser = new AiProjectUpdateUserFixture();
		$bridge = new AiProjectUpdateNativeWriteFixture(null, $actingUser);
		$project = new AiProjectUpdateProjectPermissionFixture();
		foreach ([0, -1, 1, 2] as $permissionResult) {
			$project->result = $permissionResult;
			$this->assertSame($permissionResult > 0, $bridge->checkProjectWrite($project));
			$this->assertSame([$actingUser, 'write'], $project->received);
		}
	}

	/** @return void */
	public function testPreviewNamesProjectAndChangesWithoutLoadingSupplier()
	{
		$project = $this->bridge->writeConfirmationPreview('api_projects_update', ['ref' => 'PJ-7', 'title' => 'Revised project', 'date_end' => 1735862400]);
		foreach (['PJ-7', 'Example project', 'Revised project', '2025-01-02', '2025-01-03', '→'] as $expected) {
			$this->assertStringContainsString($expected, $project);
		}
		$this->assertStringNotContainsString('00:00:00 UTC', $project);
		$this->assertNotContains('Societe', $this->bridge->fetchedClasses);
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testOnlyCompactRecordIdentityIsReturnedAfterASuccessfulWrite()
	{
		foreach (['api_projects_update' => 7] as $tool => $id) {
			$ref = 'PJ-7';
			$this->assertSame(['success' => true, 'id' => $id, 'ref' => $ref], $this->bridge->execute($tool, ['id' => $id, 'note_private' => 'New']));
		}
	}

	/** @return void */
	public function testFalseNullAndNonpositiveApiResultsNeverReportSuccess()
	{
		AiProjectUpdateEndpointFixture::$customResult = true;
		foreach ([false, null, 0, -1, '', [], ['id' => 99, 'ref' => 'PJ-7'], ['id' => 7], ['id' => 7, 'ref' => '']] as $result) {
			AiProjectUpdateEndpointFixture::$result = $result;
			foreach (['api_projects_update' => 7] as $tool => $id) {
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, ['id' => $id, 'note_private' => 'New']));
			}
		}
	}


	/** @return void */
	public function testApiSanitizationCannotChangeApprovedText()
	{
		foreach (['title' => ['<b></b>', '../', '"'], 'note_private' => ['<script>alert(1)</script>']] as $field => $values) {
			foreach ($values as $value) {
				$args = ['id' => 7, $field => $value];
				$this->assertNotSame('', $this->bridge->writePrecheck('api_projects_update', $args), $value);
				$this->assertArrayHasKey('error', $this->bridge->execute('api_projects_update', $args));
			}
		}
		$this->assertSame([], AiProjectUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testProjectDatePreviewUsesServerDayPrecision()
	{
		date_default_timezone_set('America/Los_Angeles');
		$preview = $this->bridge->writeConfirmationPreview('api_projects_update', ['id' => 7, 'date_end' => 1735862400]);
		$this->assertStringContainsString('2025-01-01 → 2025-01-02', $preview);
		$this->assertStringNotContainsString('UTC', $preview);
	}

	/** @return void */
	public function testDispatchRestoresLoadersAfterSuccessAndFailure()
	{
		$autoloaders = spl_autoload_functions();
		$includePath = get_include_path();
		foreach ([false, true, false] as $failure) {
			AiProjectUpdateEndpointFixture::$customResult = $failure;
			$result = $this->bridge->execute('api_projects_update', ['id' => 7, 'title' => 'Updated']);
			$this->assertSame($autoloaders, spl_autoload_functions());
			$this->assertSame($includePath, get_include_path());
			$this->assertSame($failure, array_key_exists('error', $result));
		}
	}

	/** @return void */
	public function testSchemasDeclareOnlyPermittedChangesAndBothRequiredRights()
	{
		$metadata = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$metadata->setAccessible(true);
		}
		$enrichments = $metadata->getValue($this->bridge);
		$schemaMethod = new ReflectionMethod(ToolApiBridge::class, 'updateInputSchema');
		if (PHP_VERSION_ID < 80100) {
			$schemaMethod->setAccessible(true);
		}
		foreach (['projects' => ['title', 'description', 'note_public', 'note_private', 'date_start', 'date_end']] as $endpoint => $fields) {
			$write = $enrichments[$endpoint]['methods']['put']['write'];
			$this->assertEqualsCanonicalizing($fields, array_keys($write['update']));
			$schema = $schemaMethod->invoke($this->bridge, $write);
			$this->assertFalse($schema['additionalProperties']);
			$this->assertSame([['required' => ['id']], ['required' => ['ref']]], $schema['oneOf']);
			$this->assertFalse($schema['properties']['request_data']['oneOf'][0]['additionalProperties']);
			$this->assertSame($write['update'], $schema['properties']['request_data']['oneOf'][0]['properties']);
			$this->assertSame(1, $schema['properties']['date_start']['minimum']);
			$this->assertSame(1, $schema['properties']['date_end']['minimum']);
			$this->assertSame(['integer', 'null'], $schema['properties']['date_start']['type']);
			$this->assertSame(255, $schema['properties']['title']['maxLength']);
			$this->assertSame([['projet', 'creer'], ['projet', 'lire']], $this->bridge->getRequiredRights('api_'.$endpoint.'_update'));
		}
	}
}

/** User fixture granting read-all but allowing an individual right to be denied. */
class AiProjectUpdateUserFixture
{
	/** @var string Permission denied by this fixture */
	public $denied = '';

	/** @param string $module Module
	 * @param string $right Permission
	 * @param string $subright Optional subpermission
	 * @return bool Whether the permission is granted
	 */
	public function hasRight($module, $right, $subright = '')
	{
		return $right !== $this->denied;
	}
}

/** Translator fixture keeping confirmation values visible. */
class AiProjectUpdateLanguageFixture
{
	/** @param string $file Language file
	 * @return void
	 */
	public function load($file)
	{
	}

	/** @param string $key Translation key
	 * @param mixed ...$values Values
	 * @return string Readable confirmation
	 */
	public function trans($key, ...$values)
	{
		return $key.($values ? ' '.implode(' ', $values) : '');
	}
}

/** Bridge fixture replacing only storage, resource visibility and endpoint discovery. */
class AiProjectUpdateBridgeFixture extends ToolApiBridge
{
	/**
	 * Preserve PHPUnit's loaders while exercising the real API dispatch.
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

	/** @var bool Target record visibility */
	public $allowAccess = true;
	/** @var bool Task parent project visibility */
	public $allowProjectAccess = true;
	/** @var bool Project write membership */
	public $allowProjectWrite = true;
	/** @var bool Project existence */
	public $projectExists = true;
	/** @var mixed Parent project status, including invalid values for fail-closed tests */
	public $projectStatus = 1;
	/** @var string[] Business classes requested */
	public $fetchedClasses = [];

	/** @param string $class Business class
	 * @param int $id Object ID
	 * @param string $ref Exact reference
	 * @return object|null Matching record
	 */
	protected function fetchBusinessObject($class, $id, $ref = '')
	{
		$this->fetchedClasses[] = $class;
		if ($class === 'Project' && $this->projectExists && ($id === 7 || $ref === 'PJ-7')) {
			return (object) ['id' => 7, 'ref' => 'PJ-7', 'title' => 'Example project', 'statut' => $this->projectStatus, 'status' => $this->projectStatus, 'public' => 0, 'date_start' => 1704067200, 'date_end' => 1735776000, 'note_private' => 'Old note', 'description' => 'Old description'];
		}

		return null;
	}

	/** @param array<int,string> $access Resource arguments
	 * @param int $id Object ID
	 * @return bool Whether the fixture allows access
	 */
	protected function canAccessWriteObject(array $access, $id)
	{
		return $this->allowAccess && ($access[0] !== 'project' || $this->allowProjectAccess);
	}

	/** @param object $project Project being written
	 * @return bool Whether the user may write this project
	 */
	protected function canWriteProject($project)
	{
		return $this->allowProjectWrite;
	}

	/** @return array<int,array<string,mixed>> No external definitions */
	public function getDefinitions(): array
	{
		$routes = new ReflectionProperty(ToolApiBridge::class, 'routes');
		if (PHP_VERSION_ID < 80100) {
			$routes->setAccessible(true);
		}
		$routes->setValue($this, ['api_projects_update' => ['projects', 'put']]);
		$endpoints = new ReflectionProperty(ToolApiBridge::class, 'endpoints');
		if (PHP_VERSION_ID < 80100) {
			$endpoints->setAccessible(true);
		}
		$fixture = ['path' => __FILE__, 'class' => AiProjectUpdateEndpointFixture::class];
		$endpoints->setValue($this, ['projects' => $fixture]);
		return [];
	}
}

/** Endpoint fixture capturing REST writes without a database connection. */
class AiProjectUpdateEndpointFixture
{
	/** @var array<string,mixed> Last received request */
	public static $received = [];
	/** @var bool Whether to return the configured failure */
	public static $customResult = false;
	/** @var mixed Configured API result */
	public static $result = null;

	/** @param int $id Record ID
	 * @param array<string,mixed> $request_data Permitted changes
	 * @return mixed API result
	 */
	public function put($id, $request_data = null)
	{
		self::$received = ['id' => $id, 'body' => $request_data];
		if (self::$customResult) {
			return self::$result;
		}
		return ['id' => $id, 'ref' => 'PJ-7', 'private_payload' => 'Do not return the full object', 'description' => 'Full description', 'array_options' => ['options_private' => 'Do not return extrafields']];
	}
}

/** Expose the native authorization adapter without replacing its implementation. */
class AiProjectUpdateNativeWriteFixture extends ToolApiBridge
{
	/** @param object $project Project fixture
	 * @return bool Whether native project write authorization permits the edit
	 */
	public function checkProjectWrite($project)
	{
		return $this->canWriteProject($project);
	}
}

/** Project fixture capturing the native access check without database access. */
class AiProjectUpdateProjectPermissionFixture
{
	/** @var int Native access result */
	public $result = 0;
	/** @var array<int,mixed> Arguments received by the access check */
	public $received = [];

	/** @param object $user Acting user
	 * @param string $mode Native access mode
	 * @return int Configured access result
	 */
	public function restrictedProjectArea($user, $mode = 'read')
	{
		$this->received = [$user, $mode];
		return $this->result;
	}
}
