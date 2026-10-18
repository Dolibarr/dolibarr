<?php
/* Copyright (C) 2026 Carlos Herrera <cehojac@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiMcpApiBridgeExternalEnrichmentsTest.php
 * \ingroup test
 * \brief Tests for the 'addMcpApiBridgeEnrichments' hook of the AI MCP API bridge.
 */

require_once __DIR__.'/../../htdocs/ai/class/mcptool.class.php';
require_once __DIR__.'/../../htdocs/ai/tools/api_bridge.class.php';

/**
 * Stand-in HookManager: executes no module, answers 'addMcpApiBridgeEnrichments'
 * with the resArray the test injected.
 */
class AiMcpExternalHookStub
{
	/** @var array<string,mixed> */
	public $resArray = array();

	/**
	 * @param array<int,string> $contexts Hook contexts (ignored)
	 * @return int
	 */
	public function initHooks($contexts)
	{
		return 0;
	}
	/**
	 * @param string $method     Hook name (ignored)
	 * @param mixed  $parameters Parameters (ignored)
	 * @param mixed  $object     Context object (ignored)
	 * @param string $action     Action (ignored)
	 * @return int
	 */
	public function executeHooks($method, $parameters, &$object, $action)
	{
		return 0;
	}
}

/**
 * External-module enrichment tests requiring neither a database nor the HookManager.
 */
class AiMcpApiBridgeExternalEnrichmentsTest extends PHPUnit\Framework\TestCase
{
	/**
	 * @param  ToolApiBridge $bridge Bridge instance under test.
	 * @return array<string, array{label?:string, methods?:array<string, mixed>}> Core enrichments plus the hook ones
	 */
	private function enrichmentsOf(ToolApiBridge $bridge)
	{
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$property->setAccessible(true);
		return $property->getValue($bridge);
	}

	/**
	 * A module declaring its endpoint through the hook gets it appended.
	 *
	 * @return void
	 */
	public function testModuleEnrichmentIsAppended()
	{
		$GLOBALS['hookmanager'] = new AiMcpExternalHookStub();
		$GLOBALS['hookmanager']->resArray = array(
			array(
				'projectbytag' => array(
					'label' => 'projects by tag',
					'methods' => array(
						'getProjectsByTagId' => array('description' => 'Projects carrying one tag id.'),
						'getProjectTags' => array('description' => 'Tags usable on projects.'),
					),
				),
			),
		);

		$enrichments = $this->enrichmentsOf(new ToolApiBridge(null));

		$this->assertArrayHasKey('projectbytag', $enrichments);
		$this->assertSame('projects by tag', $enrichments['projectbytag']['label']);
		$this->assertSame(array('getProjectsByTagId', 'getProjectTags'), array_keys($enrichments['projectbytag']['methods']));
	}

	/**
	 * A module can never shadow a core endpoint entry: core wins.
	 *
	 * @return void
	 */
	public function testModuleCannotShadowCoreEnrichment()
	{
		$GLOBALS['hookmanager'] = new AiMcpExternalHookStub();
		$GLOBALS['hookmanager']->resArray = array(
			array(
				'tickets' => array(
					'label' => 'overridden by a module',
					'methods' => array('index' => array()),
				),
			),
		);

		$enrichments = $this->enrichmentsOf(new ToolApiBridge(null));

		$this->assertSame('support tickets', $enrichments['tickets']['label']);
		// A method the module never declared but core has proves the merge kept core's.
		$this->assertArrayHasKey('getByTrackId', $enrichments['tickets']['methods']);
	}

	/**
	 * A module entry without 'methods' falls back to the read-only pair.
	 *
	 * @return void
	 */
	public function testModuleEntryWithoutMethodsGetsReadOnlyDefaults()
	{
		$GLOBALS['hookmanager'] = new AiMcpExternalHookStub();
		$GLOBALS['hookmanager']->resArray = array(
			array('mymodule' => array('label' => 'my things')),
		);

		$enrichments = $this->enrichmentsOf(new ToolApiBridge(null));

		$this->assertSame(array('index', 'get'), array_keys($enrichments['mymodule']['methods']));
	}

	/**
	 * Garbage from a module (scalar resArray entries, scalar keys) is ignored.
	 *
	 * @return void
	 */
	public function testGarbageFromModulesIsIgnored()
	{
		$GLOBALS['hookmanager'] = new AiMcpExternalHookStub();
		$GLOBALS['hookmanager']->resArray = array('not-an-array', 42);

		$enrichments = $this->enrichmentsOf(new ToolApiBridge(null));

		$this->assertArrayNotHasKey('0', $enrichments);
		$this->assertArrayHasKey('tickets', $enrichments);	// core intact
	}

	/**
	 * Without a HookManager (no Dolibarr bootstrap) the constructor stays quiet.
	 *
	 * @return void
	 */
	public function testNoHookManagerMeansNoExternalEntries()
	{
		unset($GLOBALS['hookmanager']);

		$enrichments = $this->enrichmentsOf(new ToolApiBridge(null));

		$this->assertArrayHasKey('tickets', $enrichments);
		$this->assertArrayNotHasKey('projectbytag', $enrichments);
	}
}
