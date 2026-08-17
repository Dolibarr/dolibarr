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

	/**
	 * @param mixed $expected Expected value
	 * @param mixed $actual Actual value
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertSame($expected, $actual, string $message = ''): void
	{
	}

	/**
	 * @param mixed $actual Actual value
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertNull($actual, string $message = ''): void
	{
	}

	/**
	 * @param bool $condition Asserted condition
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertTrue(bool $condition, string $message = ''): void
	{
	}

	/**
	 * @param bool $condition Asserted condition
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertFalse(bool $condition, string $message = ''): void
	{
	}

	/**
	 * @param mixed $expected Expected value
	 * @param mixed $actual Actual value
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertNotSame($expected, $actual, string $message = ''): void
	{
	}

	/**
	 * @param int|float $expected Expected lower bound
	 * @param mixed $actual Actual value
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertGreaterThan($expected, $actual, string $message = ''): void
	{
	}

	/**
	 * @param string $needle Expected substring
	 * @param string $haystack Inspected string
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
	{
	}

	/**
	 * @param string $needle Unexpected substring
	 * @param string $haystack Inspected string
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
	{
	}

	/**
	 * @param string $filename File path
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertFileNotExists(string $filename, string $message = ''): void
	{
	}

	/**
	 * @param string $filename File path
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertFileDoesNotExist(string $filename, string $message = ''): void
	{
	}

	/**
	 * @param string $expected Expected class name
	 * @param mixed $actual Actual value
	 * @param string $message Failure message
	 * @return void
	 */
	public static function assertInstanceOf(string $expected, $actual, string $message = ''): void
	{
	}

	/**
	 * @param class-string<\Throwable> $exception Expected exception class
	 * @return void
	 */
	public function expectException(string $exception): void
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
