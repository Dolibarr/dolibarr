<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       test/phpunit/AiMcpSchemaTest.php
 * \ingroup    test
 * \brief      PHPUnit tests for MCP schema normalization and const-to-enum conversion (Issue #41827).
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__) . '/../../htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', sys_get_temp_dir());
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/tools/api_bridge.class.php';
require_once dirname(__FILE__) . '/CommonClassTest.class.php';

/**
 * Class for AI MCP schema normalization tests.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpSchemaTest extends CommonClassTest
{
	/**
	 * Test that normalizeInputSchema converts string const to enum and preserves existing type.
	 *
	 * @return void
	 */
	public function testNormalizeInputSchemaConvertsConstToEnum()
	{
		$schema = [
			'type' => 'object',
			'properties' => [
				'action' => [
					'type' => 'string',
					'const' => 'create'
				]
			]
		];

		$normalized = McpTool::normalizeInputSchema($schema);

		$this->assertArrayNotHasKey('const', $normalized['properties']['action']);
		$this->assertArrayHasKey('enum', $normalized['properties']['action']);
		$this->assertEquals(['create'], $normalized['properties']['action']['enum']);
		$this->assertEquals('string', $normalized['properties']['action']['type']);
	}

	/**
	 * Test that normalizeInputSchema infers missing types from scalar and array const values.
	 *
	 * @return void
	 */
	public function testNormalizeInputSchemaInfersTypes()
	{
		$schema = [
			'properties' => [
				'int_val' => ['const' => 42],
				'float_val' => ['const' => 3.14],
				'bool_val' => ['const' => true],
				'str_val' => ['const' => 'hello'],
				'arr_val' => ['const' => ['item1', 'item2']]
			]
		];

		$normalized = McpTool::normalizeInputSchema($schema);

		$this->assertEquals('integer', $normalized['properties']['int_val']['type']);
		$this->assertEquals([42], $normalized['properties']['int_val']['enum']);

		$this->assertEquals('number', $normalized['properties']['float_val']['type']);
		$this->assertEquals([3.14], $normalized['properties']['float_val']['enum']);

		$this->assertEquals('boolean', $normalized['properties']['bool_val']['type']);
		$this->assertEquals([true], $normalized['properties']['bool_val']['enum']);

		$this->assertEquals('string', $normalized['properties']['str_val']['type']);
		$this->assertEquals(['hello'], $normalized['properties']['str_val']['enum']);

		$this->assertEquals('array', $normalized['properties']['arr_val']['type']);
		$this->assertEquals(['item1', 'item2'], $normalized['properties']['arr_val']['enum']);
	}

	/**
	 * Test that normalizeInputSchema strips unsupported keywords like $schema and $id.
	 *
	 * @return void
	 */
	public function testNormalizeInputSchemaRemovesUnsupportedKeywords()
	{
		$schema = [
			'$schema' => 'http://json-schema.org/draft-07/schema#',
			'$id' => 'https://example.com/schema.json',
			'type' => 'object',
			'properties' => [
				'name' => ['type' => 'string']
			]
		];

		$normalized = McpTool::normalizeInputSchema($schema);

		$this->assertArrayNotHasKey('$schema', $normalized);
		$this->assertArrayNotHasKey('$id', $normalized);
		$this->assertEquals('object', $normalized['type']);
	}

	/**
	 * Test that normalizeInputSchema works recursively on nested objects, arrays, and oneOf.
	 *
	 * @return void
	 */
	public function testNormalizeInputSchemaNestedStructures()
	{
		$schema = [
			'type' => 'object',
			'properties' => [
				'nested' => [
					'type' => 'object',
					'properties' => [
						'inner' => ['const' => 'val1']
					]
				],
				'list' => [
					'type' => 'array',
					'items' => [
						'properties' => [
							'tag' => ['const' => 'val2']
						]
					]
				]
			],
			'oneOf' => [
				[
					'properties' => [
						'branch' => ['const' => 'branch_a']
					]
				]
			]
		];

		$normalized = McpTool::normalizeInputSchema($schema);

		$this->assertArrayNotHasKey('const', $normalized['properties']['nested']['properties']['inner']);
		$this->assertEquals(['val1'], $normalized['properties']['nested']['properties']['inner']['enum']);

		$this->assertArrayNotHasKey('const', $normalized['properties']['list']['items']['properties']['tag']);
		$this->assertEquals(['val2'], $normalized['properties']['list']['items']['properties']['tag']['enum']);

		$this->assertArrayNotHasKey('const', $normalized['oneOf'][0]['properties']['branch']);
		$this->assertEquals(['branch_a'], $normalized['oneOf'][0]['properties']['branch']['enum']);
	}

	/**
	 * Test that McpHandler::normalizeInputSchema delegates properly to McpTool.
	 *
	 * @return void
	 */
	public function testMcpHandlerDelegation()
	{
		$schema = ['const' => 'fixed'];
		$normalized = McpHandler::normalizeInputSchema($schema);

		$this->assertArrayNotHasKey('const', $normalized);
		$this->assertEquals(['fixed'], $normalized['enum']);
		$this->assertEquals('string', $normalized['type']);
	}

	/**
	 * Test that ToolApiBridge::liftInlineTags converts {@const} tags into enum constraints.
	 *
	 * @return void
	 */
	public function testToolApiBridgeLiftsConstInlineTag()
	{
		$refl = new ReflectionClass('ToolApiBridge');
		$method = $refl->getMethod('liftInlineTags');
		$method->setAccessible(true);
		$bridge = $refl->newInstanceWithoutConstructor();

		$constraints = [];
		$cleanDesc = $method->invokeArgs($bridge, ['Filter by mode {@const draft} only', 'string', &$constraints]);

		$this->assertArrayNotHasKey('const', $constraints);
		$this->assertArrayHasKey('enum', $constraints);
		$this->assertEquals(['draft'], $constraints['enum']);
		$this->assertStringNotContainsString('{@const', $cleanDesc);
		$this->assertEquals('Filter by mode only', $cleanDesc);
	}
}
