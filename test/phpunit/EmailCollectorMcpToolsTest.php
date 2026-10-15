<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/EmailCollectorMcpToolsTest.php
 * \ingroup test
 * \brief Check module ownership and compatibility of email MCP tools.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__.'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/emailcollector/class/emailcollectormcptools.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/email_cleaner.class.php';

/**
 * Tests preserving discovery and permission boundaries after moving the tools.
 */
class EmailCollectorMcpToolsTest extends PHPUnit\Framework\TestCase
{
	/** @var mixed Original configuration */
	private $previousConf;

	/** @return void */
	protected function setUp(): void
	{
		global $conf;
		$this->previousConf = $conf;
		$conf = (object) array('modules' => array('emailcollector' => 'emailcollector', 'ai' => 'ai'), 'global' => (object) array('AI_EMAILCLEANER_ENABLED' => 1));
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $conf;
		$conf = $this->previousConf;
	}

	/** @return void */
	public function testImplementationBelongsToEmailCollector()
	{
		$reflection = new ReflectionClass(ToolEmailCleaner::class);
		$this->assertSame(realpath(DOL_DOCUMENT_ROOT.'/emailcollector/class/emailcollectormcptools.class.php'), $reflection->getFileName());
	}

	/** @return void */
	public function testLegacyDiscoveryKeepsTheSameFourToolsAndRights()
	{
		global $conf;
		$tool = new ToolEmailCleaner(null, null, $conf);
		$names = array_column($tool->getDefinitions(), 'name');
		$this->assertSame(array('list_email_cleaner_runs', 'get_email_cleaner_run', 'get_email_thread_context', 'get_email_handoff_payload'), $names);
		foreach ($names as $name) {
			$this->assertSame(array(array('emailcollector', 'read')), $tool->getRequiredRights($name));
			$this->assertSame(McpTool::NO_WRITE, $tool->writeConfirmationPreview($name, array()));
		}
	}

	/** @return void */
	public function testDisabledCleanerExposesNoTools()
	{
		global $conf;
		$conf->global->AI_EMAILCLEANER_ENABLED = 0;
		$tool = new ToolEmailCleaner(null, null, $conf);
		$this->assertSame(array(), $tool->getDefinitions());
	}

	/** @return void */
	public function testDisabledCollectorExposesNoTools()
	{
		global $conf;
		$conf->modules = array('ai' => 'ai');
		$tool = new ToolEmailCleaner(null, null, $conf);
		$this->assertSame(array(), $tool->getDefinitions());
	}
}
