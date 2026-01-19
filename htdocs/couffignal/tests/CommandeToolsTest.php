<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__ . '/../..');
}

require_once DOL_DOCUMENT_ROOT . '/couffignal/CommandeTools.php';

/**
 * Class CommandeToolsTest
 *
 * Tests for the CommandeTools class.
 */
class CommandeToolsTest extends TestCase
{
	/**
	 * It should sort orders by date.
	 *
	 * @return void
	 */
	public function testItShouldSortByDate(): void
	{
		$obj = new stdClass();
		$obj->date = 10;
		$obj2 = new stdClass();
		$obj2->date = 20;
		$list = [$obj2, $obj];
		$sorted = CommandeTools::sortOrdersByDateAndRef($list);
		$this->assertEquals($obj, $sorted[0]);
		$this->assertEquals($obj2, $sorted[1]);
	}

	/**
	 * It should sort orders by reference.
	 *
	 * @return void
	 */
	public function testItShouldSortByRef(): void
	{
		$obj1 = new stdClass(); // 2
		$obj1->date = 10;
		$obj1->ref = 'A';
		$obj2 = new stdClass(); // 3
		$obj2->date = 10;
		$obj2->ref = 'B';
		$obj3 = new stdClass(); // 1
		$obj3->date = 4;
		$obj3->ref = 'C';
		$obj4 = new stdClass(); // 4
		$obj4->date = 20;
		$obj4->ref = 'D';
		$list = [$obj2, $obj4, $obj1, $obj3];
		$sorted = CommandeTools::sortOrdersByDateAndRef($list);
		$this->assertEquals($obj3, $sorted[0]);
		$this->assertEquals($obj1, $sorted[1]);
		$this->assertEquals($obj2, $sorted[2]);
		$this->assertEquals($obj4, $sorted[3]);
	}

	/**
	 * It should return an empty array when no orders are provided.
	 *
	 * @return void
	 */
	public function testItShouldReturnEmptyArray(): void
	{
		$list = [];
		$sorted = CommandeTools::sortOrdersByDateAndRef($list);
		$this->assertEquals([], $sorted);
	}
}
