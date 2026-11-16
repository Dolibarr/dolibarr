<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiMcpApiBridgeProjectsTest.php
 * \ingroup test
 * \brief Regression tests for discovery of project API tools.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once __DIR__.'/../../htdocs/ai/class/mcptool.class.php';
require_once __DIR__.'/../../htdocs/ai/tools/api_bridge.class.php';

/**
 * Project metadata tests requiring neither a database nor an API credential.
 */
class AiMcpApiBridgeProjectsTest extends CommonClassTest
{
	/**
	 * The discovery key comes from api_projects.class.php, not the module name.
	 *
	 * @return void
	 */
	public function testProjectWhitelistMatchesDiscoveredEndpoint()
	{
		$bridge = new ToolApiBridge(null);
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}
		$endpoints = $property->getValue($bridge);

		$this->assertArrayHasKey('projects', $endpoints);
		// The 'project' group only names the api_project_tasks_timespent_create tool: it calls the tasks endpoint
		$this->assertSame('tasks', $endpoints['project']['endpoint']);
		$this->assertSame(array('index', 'get', 'put', 'getContacts', 'post'), array_keys($endpoints['projects']['methods']));
		$this->assertSame(array('project'), ToolApiBridge::ENDPOINT_CATEGORIES['projects']);
	}

	/**
	 * The project get tool keeps the REST parameter names and also accepts an exact ref.
	 *
	 * @return void
	 */
	public function testProjectReferenceLookupPreservesItsSchema()
	{
		$bridge = new ToolApiBridge(null);
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}
		$endpoints = $property->getValue($bridge);
		$method = new ReflectionMethod(ToolApiBridge::class, 'buildToolDefinition');
		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}
		$definition = $method->invoke($bridge, array('class' => AiMcpProjectReadApiFixture::class, 'label' => 'projects'), 'projects', 'get', 'api_projects_get', $endpoints['projects']['methods']['get']);

		$this->assertSame('api_projects_get', $definition['name']);
		$this->assertArrayNotHasKey('required', $definition['inputSchema']);
		$this->assertSame(0, $definition['inputSchema']['properties']['id']['default']);
		$this->assertSame('string', $definition['inputSchema']['properties']['ref']['type']);
		$this->assertSame('Exact project reference, alternative to id.', $definition['inputSchema']['properties']['ref']['description']);
	}
}

/**
 * Reflection fixture matching Projects::get and Projects::getByRef without loading the ERP.
 */
class AiMcpProjectReadApiFixture
{
	/**
	 * Get one project.
	 *
	 * @param int $id Rowid of the project
	 * @return array<string,mixed> Project data
	 */
	public function get($id)
	{
		return array('id' => $id);
	}

	/**
	 * Get one project.
	 *
	 * @param string $ref Exact reference
	 * @return array<string,mixed> Project data
	 */
	public function getByRef($ref)
	{
		return array('ref' => $ref);
	}
}
