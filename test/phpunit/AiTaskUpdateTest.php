<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiTaskUpdateTest.php
 * \ingroup test
 * \brief Bounded task planning writes through the AI bridge, without persistence.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__.'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/api_bridge.class.php';

/** Exercise update validation, permissions, confirmation and endpoint dispatch. */
class AiTaskUpdateTest extends PHPUnit\Framework\TestCase
{
	/** @var AiTaskUpdateBridgeFixture Bridge fixture */
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
		global $langs, $user, $conf;
		$this->previousLangs = $langs;
		$this->previousUser = $user;
		$this->previousConf = $conf;
		$this->previousTimezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$langs = new AiTaskUpdateLanguageFixture();
		$conf = (object) ['global' => new stdClass(), 'cache' => []];
		$this->bridge = new AiTaskUpdateBridgeFixture(null, new AiTaskUpdateUserFixture());
		AiTaskUpdateEndpointFixture::$received = [];
		AiTaskUpdateEndpointFixture::$customResult = false;
		AiTaskUpdateEndpointFixture::$result = null;
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $langs, $user, $conf;
		$langs = $this->previousLangs;
		$user = $this->previousUser;
		$conf = $this->previousConf;
		date_default_timezone_set($this->previousTimezone);
	}

	/** @return void */
	public function testFlatObjectAndJsonTaskInputsProduceTheSamePartialBody()
	{
		$changes = ['label' => '  Revised task  ', 'note_private' => '', 'date_end' => null];
		$expected = ['label' => 'Revised task', 'note_private' => '', 'date_end' => null];
		foreach ([array_merge(['id' => 42], $changes), ['id' => 42, 'request_data' => $changes], ['ref' => 'TK-42', 'request_data' => json_encode($changes)]] as $args) {
			$this->assertSame('', $this->bridge->writePrecheck('api_tasks_update', $args));
			$this->assertSame(['success' => true, 'id' => 42, 'ref' => 'TK-42'], $this->bridge->execute('api_tasks_update', $args));
			$this->assertSame(['id' => 42, 'body' => $expected], AiTaskUpdateEndpointFixture::$received);
			$this->assertArrayNotHasKey('status', AiTaskUpdateEndpointFixture::$received['body']);
			$this->assertArrayNotHasKey('fk_soc', AiTaskUpdateEndpointFixture::$received['body']);
		}
	}

	/** @return void */
	public function testTextIsTrimmedBeforeConfirmationAndDispatch()
	{
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			$args = ['id' => $id, 'description' => '  Updated description  ', 'note_public' => "\n Public note \n", 'note_private' => '   '];
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$preview = $this->bridge->writeConfirmationPreview($tool, $args);
			$this->assertStringContainsString('"Updated description"', $preview);
			$this->assertStringNotContainsString('  Updated description  ', $preview);
			$this->bridge->execute($tool, $args);
			$this->assertSame(['description' => 'Updated description', 'note_public' => 'Public note', 'note_private' => ''], AiTaskUpdateEndpointFixture::$received['body']);
		}
	}

	/** @return void */
	public function testInvalidTaskFieldsAndBodiesAreRejectedBeforeExecution()
	{
		foreach ([
			[], ['request_data' => '{}'], ['request_data' => '[]'], ['request_data' => '{invalid'], ['request_data' => 'null'],
			['request_data' => []], ['request_data' => null], ['request_data' => false], ['request_data' => 0],
			['request_data' => '[{"title":"New"}]'], ['label' => ''], ['label' => '   '], ['label' => null], ['label' => false],
			['label' => str_repeat('x', 256)], ['description' => null], ['note_public' => false], ['note_private' => 0],
			['date_start' => 0], ['date_end' => -1], ['date_start' => '1735689600'], ['date_end' => 1.5], ['date_end' => false],
			['status' => 1], ['statut' => 1], ['fk_statut' => 1], ['public' => 1], ['progress' => 100], ['fk_task_parent' => 10], ['fk_project' => 8], ['duration_effective' => 60], ['planned_workload' => -1], ['planned_workload' => '3600'], ['planned_workload' => 1.5], ['planned_workload' => 2147483648], ['fk_soc' => 2], ['socid' => 2],
			['ref' => 'RENAMED'], ['array_options' => ['options_test' => 'new']], ['lines' => []],
			['request_data' => ['label' => 'Body'], 'label' => 'Flat'], ['request_data' => ['id' => 42, 'label' => 'New']]
		] as $changes) {
			$args = array_merge(['id' => 42], $changes);
			$this->assertNotSame('', $this->bridge->writePrecheck('api_tasks_update', $args), json_encode($args));
			$this->assertArrayHasKey('error', $this->bridge->execute('api_tasks_update', $args));
			$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testExactlyOneExistingTaskIdentifierIsRequired()
	{
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			$ref = 'TK-42';
			foreach ([[], ['id' => 0], ['id' => (string) $id], ['id' => 42.5], ['ref' => ''], ['ref' => '   '], ['id' => $id, 'ref' => $ref], ['id' => 99], ['ref' => 'UNKNOWN']] as $identifier) {
				$args = array_merge($identifier, ['note_private' => 'New']);
				$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args), json_encode($args));
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			}
		}
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testExistingDatesParticipateInValidation()
	{
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			foreach ([['date_start' => 1735862400], ['date_end' => 1703980800], ['date_start' => 1735862400, 'date_end' => 1735776000]] as $changes) {
				$args = array_merge(['id' => $id], $changes);
				$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			}
			$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
			$args = ['id' => $id, 'date_start' => 1735776000, 'date_end' => 1735776000];
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$this->bridge->execute($tool, $args);
			$this->assertSame(['date_start' => 1735776000, 'date_end' => 1735776000], AiTaskUpdateEndpointFixture::$received['body']);
			AiTaskUpdateEndpointFixture::$received = [];
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
		foreach (['tasks'] as $endpoint) {
			$enrichments[$endpoint]['methods']['put']['write']['defaults'] = ['status' => 1, 'note_public' => 'Default'];
			$enrichments[$endpoint]['methods']['put']['write']['rename'] = ['note_private' => 'note_public'];
		}
		$metadata->setValue($this->bridge, $enrichments);
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			$changes = ['date_start' => null, 'date_end' => null, 'note_private' => ''];
			$args = array_merge(['id' => $id, 'requestState' => 'confirmation-state'], $changes);
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$this->bridge->execute($tool, $args);
			$this->assertSame(['id' => $id, 'body' => $changes], AiTaskUpdateEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testReadWriteAndRecordVisibilityAreAllRequired()
	{
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			foreach (['lire', 'creer'] as $denied) {
				$actingUser = new AiTaskUpdateUserFixture();
				$actingUser->denied = $denied;
				$bridge = new AiTaskUpdateBridgeFixture(null, $actingUser);
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
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testReadAllDoesNotGrantProjectWriteAccess()
	{
		$this->bridge->allowProjectWrite = false;
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			$args = ['id' => $id, 'note_private' => 'New'];
			$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
			$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
		}
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testClosedOrUnknownProjectStatusIsRejected()
	{
		foreach ([2, -1, 99, null, 'oops', false, '00'] as $status) {
			$this->bridge->projectStatus = $status;
			foreach (['api_tasks_update' => 42] as $tool => $id) {
				$args = ['id' => $id, 'note_private' => 'New'];
				$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
			}
		}
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
		foreach ([0, 1] as $status) {
			$this->bridge->projectStatus = $status;
			$this->assertSame('', $this->bridge->writePrecheck('api_tasks_update', ['id' => 42, 'note_private' => 'New']));
		}
	}

	/** @return void */
	public function testMaximumLabelLengthIsAccepted()
	{
		foreach (['api_tasks_update' => ['id' => 42, 'label' => str_repeat('x', 255)]] as $tool => $args) {
			$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
			$this->assertArrayNotHasKey('error', $this->bridge->execute($tool, $args));
		}
	}

	/** @return void */
	public function testPermissionsAndVisibilityAreRevalidatedAfterConfirmationPreview()
	{
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			$args = ['id' => $id, 'note_private' => 'New'];
			foreach (['lire', 'creer'] as $denied) {
				$actingUser = new AiTaskUpdateUserFixture();
				$bridge = new AiTaskUpdateBridgeFixture(null, $actingUser);
				$this->assertNotSame('', $bridge->writeConfirmationPreview($tool, $args));
				$actingUser->denied = $denied;
				$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
			}
			foreach (['allowAccess', 'allowProjectAccess', 'allowProjectWrite', 'projectExists'] as $flag) {
				$bridge = new AiTaskUpdateBridgeFixture(null, new AiTaskUpdateUserFixture());
				$this->assertNotSame('', $bridge->writeConfirmationPreview($tool, $args));
				$bridge->$flag = false;
				$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
			}
			$bridge = new AiTaskUpdateBridgeFixture(null, new AiTaskUpdateUserFixture());
			$this->assertNotSame('', $bridge->writeConfirmationPreview($tool, $args));
			$bridge->projectStatus = 2;
			$this->assertArrayHasKey('error', $bridge->execute($tool, $args));
		}
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testNativeProjectWriteAuthorizationUsesTheActingUserAndWriteMode()
	{
		$actingUser = new AiTaskUpdateUserFixture();
		$bridge = new AiTaskUpdateNativeWriteFixture(null, $actingUser);
		$project = new AiTaskUpdateProjectPermissionFixture();
		foreach ([0, -1, 1, 2] as $permissionResult) {
			$project->result = $permissionResult;
			$this->assertSame($permissionResult > 0, $bridge->checkProjectWrite($project));
			$this->assertSame([$actingUser, 'write'], $project->received);
		}
	}

	/** @return void */
	public function testPreviewNamesTaskProjectAndChangesWithoutLoadingSupplier()
	{
		$project = $this->bridge->writeConfirmationPreview('api_tasks_update', ['ref' => 'TK-42', 'label' => 'Revised task', 'date_end' => 1735862400]);
		foreach (['TK-42', 'Example task', 'PJ-7', 'Example project', 'Revised task', '2025-01-02', '2025-01-03', '→'] as $expected) {
			$this->assertStringContainsString($expected, $project);
		}
		$this->assertStringContainsString('00:00:00 UTC', $project);
		$this->assertNotContains('Societe', $this->bridge->fetchedClasses);
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testOnlyCompactRecordIdentityIsReturnedAfterASuccessfulWrite()
	{
		foreach (['api_tasks_update' => 42] as $tool => $id) {
			$ref = 'TK-42';
			$this->assertSame(['success' => true, 'id' => $id, 'ref' => $ref], $this->bridge->execute($tool, ['id' => $id, 'note_private' => 'New']));
		}
	}

	/** @return void */
	public function testFalseNullAndNonpositiveApiResultsNeverReportSuccess()
	{
		AiTaskUpdateEndpointFixture::$customResult = true;
		foreach ([false, null, 0, -1, '', [], ['id' => 99, 'ref' => 'TK-42'], ['id' => 42], ['id' => 42, 'ref' => '']] as $result) {
			AiTaskUpdateEndpointFixture::$result = $result;
			foreach (['api_tasks_update' => 42] as $tool => $id) {
				$this->assertArrayHasKey('error', $this->bridge->execute($tool, ['id' => $id, 'note_private' => 'New']));
			}
		}
	}


	/** @return void */
	public function testApiSanitizationCannotChangeApprovedText()
	{
		foreach (['label' => ['<b></b>', '../', '"'], 'note_private' => ['<script>alert(1)</script>']] as $field => $values) {
			foreach ($values as $value) {
				$args = ['id' => 42, $field => $value];
				$this->assertNotSame('', $this->bridge->writePrecheck('api_tasks_update', $args), $value);
				$this->assertArrayHasKey('error', $this->bridge->execute('api_tasks_update', $args));
			}
		}
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testTaskDatePreviewKeepsTimeInUtc()
	{
		date_default_timezone_set('America/Los_Angeles');
		$preview = $this->bridge->writeConfirmationPreview('api_tasks_update', ['id' => 42, 'date_end' => 1735866000]);
		$this->assertStringContainsString('2025-01-02 00:00:00 UTC → 2025-01-03 01:00:00 UTC', $preview);
	}

	/** @return void */
	public function testDispatchRestoresLoadersAfterSuccessAndFailure()
	{
		$autoloaders = spl_autoload_functions();
		$includePath = get_include_path();
		foreach ([false, true, false] as $failure) {
			AiTaskUpdateEndpointFixture::$customResult = $failure;
			$result = $this->bridge->execute('api_tasks_update', ['id' => 42, 'label' => 'Updated']);
			$this->assertSame($autoloaders, spl_autoload_functions());
			$this->assertSame($includePath, get_include_path());
			$this->assertSame($failure, array_key_exists('error', $result));
		}
	}

	/** @return void */
	public function testWorkloadUsesSecondsAndAllowsZeroOrClearing()
	{
		foreach ([null, 0, 3600, 2147483647] as $workload) {
			$args = ['ref' => 'TK-42', 'request_data' => ['planned_workload' => $workload]];
			$this->assertSame('', $this->bridge->writePrecheck('api_tasks_update', $args));
			$preview = $this->bridge->writeConfirmationPreview('api_tasks_update', $args);
			$this->assertStringContainsString('AIPlannedWorkloadSeconds: 3600 → '.json_encode($workload), $preview);
			$this->assertSame(['success' => true, 'id' => 42, 'ref' => 'TK-42'], $this->bridge->execute('api_tasks_update', $args));
			$this->assertSame(['id' => 42, 'body' => ['planned_workload' => $workload]], AiTaskUpdateEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testAutomaticProjectClosureIsRejectedBeforeAndAfterConfirmation()
	{
		global $conf;
		$args = ['id' => 42, 'note_private' => 'New'];
		foreach ([1, '1', 'yes'] as $enabled) {
			$conf->global->PROJECT_CLASSIFY_CLOSED_WHEN_ALL_TASKS_DONE = $enabled;
			$this->assertNotSame('', $this->bridge->writePrecheck('api_tasks_update', $args));
			$this->assertStringContainsString('automatic project closure', $this->bridge->writeConfirmationPreview('api_tasks_update', $args));
			$this->assertArrayHasKey('error', $this->bridge->execute('api_tasks_update', $args));
		}
		$conf->global->PROJECT_CLASSIFY_CLOSED_WHEN_ALL_TASKS_DONE = 0;
		$this->assertSame('', $this->bridge->writePrecheck('api_tasks_update', $args));
		$this->assertStringContainsString('TK-42', $this->bridge->writeConfirmationPreview('api_tasks_update', $args));
		$conf->global->PROJECT_CLASSIFY_CLOSED_WHEN_ALL_TASKS_DONE = 1;
		$this->assertArrayHasKey('error', $this->bridge->execute('api_tasks_update', $args));
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
	}

	/** @return void */
	public function testMissingOrHiddenParentProjectNeverPermitsTaskWrites()
	{
		foreach (['projectExists', 'allowProjectAccess', 'allowProjectWrite'] as $flag) {
			$bridge = new AiTaskUpdateBridgeFixture(null, new AiTaskUpdateUserFixture());
			$bridge->$flag = false;
			$args = ['id' => 42, 'label' => 'New'];
			$this->assertNotSame('', $bridge->writePrecheck('api_tasks_update', $args));
			$this->assertArrayHasKey('error', $bridge->execute('api_tasks_update', $args));
		}
		$this->assertSame([], AiTaskUpdateEndpointFixture::$received);
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
		foreach (['tasks' => ['label', 'description', 'note_public', 'note_private', 'date_start', 'date_end', 'planned_workload']] as $endpoint => $fields) {
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
			$this->assertSame(255, $schema['properties']['label']['maxLength']);
			$this->assertSame([['projet', 'creer'], ['projet', 'lire']], $this->bridge->getRequiredRights('api_'.$endpoint.'_update'));
		}
	}
}

/** User fixture granting read-all but allowing an individual right to be denied. */
class AiTaskUpdateUserFixture
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
class AiTaskUpdateLanguageFixture
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
class AiTaskUpdateBridgeFixture extends ToolApiBridge
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
		if ($class === 'Task' && ($id === 42 || $ref === 'TK-42')) {
			return (object) ['id' => 42, 'ref' => 'TK-42', 'label' => 'Example task', 'fk_project' => 7, 'date_start' => 1704067200, 'date_end' => 1735776000, 'note_private' => 'Old note', 'description' => 'Old description', 'planned_workload' => 3600];
		}
		if ($class === 'Project' && $this->projectExists && $id === 7) {
			return (object) ['id' => 7, 'ref' => 'PJ-7', 'title' => 'Example project', 'status' => $this->projectStatus, 'public' => 0];
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
		$routes->setValue($this, ['api_tasks_update' => ['tasks', 'put']]);
		$endpoints = new ReflectionProperty(ToolApiBridge::class, 'endpoints');
		if (PHP_VERSION_ID < 80100) {
			$endpoints->setAccessible(true);
		}
		$fixture = ['path' => __FILE__, 'class' => AiTaskUpdateEndpointFixture::class];
		$endpoints->setValue($this, ['tasks' => $fixture]);
		return [];
	}
}

/** Endpoint fixture capturing REST writes without a database connection. */
class AiTaskUpdateEndpointFixture
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
		return ['id' => $id, 'ref' => 'TK-42', 'private_payload' => 'Do not return the full object', 'description' => 'Full description', 'array_options' => ['options_private' => 'Do not return extrafields']];
	}
}

/** Expose the native authorization adapter without replacing its implementation. */
class AiTaskUpdateNativeWriteFixture extends ToolApiBridge
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
class AiTaskUpdateProjectPermissionFixture
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
