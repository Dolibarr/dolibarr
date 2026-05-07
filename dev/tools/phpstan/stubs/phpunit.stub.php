<?php

namespace PHPUnit\Framework;

/**
 * Minimal PHPUnit throwable contract for PHPStan symbol discovery.
 */
interface Throwable
{
}

/**
 * Minimal PHPUnit test case contract for PHPStan symbol discovery.
 */
abstract class TestCase
{
	/**
	 * @param string|null $name Name
	 * @param array<mixed> $data Data
	 * @param string $dataName Data name
	 */
	public function __construct($name = null, array $data = array(), $dataName = '')
	{
	}

	/**
	 * @param \PHPUnit\Framework\Throwable $t Throwable
	 * @return void
	 */
	public function onNotSuccessfulTest(Throwable $t): void
	{
	}
}

/**
 * Minimal PHPUnit test suite contract for PHPStan symbol discovery.
 */
class TestSuite
{
	/**
	 * @param string $name Name
	 */
	public function __construct($name = '')
	{
	}

	/**
	 * @param string $testSuite Test suite
	 * @return void
	 */
	public function addTestSuite($testSuite): void
	{
	}
}
