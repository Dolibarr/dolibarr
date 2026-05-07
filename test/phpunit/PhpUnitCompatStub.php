<?php
/* Copyright (C) 2026 OpenAI
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace PHPUnit\Framework;

if (!class_exists(TestCase::class, false)) {
	/**
	 * This file exists only for static analysis.
	 * Dolibarr core tests extend PHPUnit classes, but PHPUnit is not a runtime dependency
	 * of the main application tree, so PHPStan needs these minimal symbols to analyze
	 * test/phpunit without flooding the report with class-not-found errors.
	 *
	 * Minimal PHPUnit TestCase stub for static analysis environments without PHPUnit.
	 */
	abstract class TestCase
	{
		/**
		 * @param string|null $name Test name
		 * @param array<mixed> $data Test data
		 * @param string $dataName Test data name
		 */
		public function __construct($name = null, array $data = array(), $dataName = '')
		{
			unset($name, $data, $dataName);
		}

		/**
		 * @param mixed $expected Expected value
		 * @param mixed $actual Actual value
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertSame($expected, $actual, string $message = ''): void
		{
		}

		/**
		 * @param mixed $actual Actual value
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertNull($actual, string $message = ''): void
		{
		}

		/**
		 * @param bool $condition Asserted condition
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertTrue(bool $condition, string $message = ''): void
		{
		}

		/**
		 * @param bool $condition Asserted condition
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertFalse(bool $condition, string $message = ''): void
		{
		}

		/**
		 * @param string $needle Expected substring
		 * @param string $haystack Inspected string
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
		{
		}

		/**
		 * @param string $needle Unexpected substring
		 * @param string $haystack Inspected string
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
		{
		}

		/**
		 * @param mixed $expected Expected value
		 * @param mixed $actual Actual value
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertNotSame($expected, $actual, string $message = ''): void
		{
		}

		/**
		 * @param int|float $expected Expected lower bound
		 * @param mixed $actual Actual value
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertGreaterThan($expected, $actual, string $message = ''): void
		{
		}

		/**
		 * @param class-string<\Throwable> $exception Expected exception class
		 * @return void
		 */
		public function expectException(string $exception): void
		{
		}

		/**
		 * @param string $filename File path
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertFileNotExists(string $filename, string $message = ''): void
		{
		}

		/**
		 * @param string $filename File path
		 * @param string $message Failure message
		 * @return void
		 */
		public function assertFileDoesNotExist(string $filename, string $message = ''): void
		{
		}
	}
}

if (!class_exists(TestSuite::class, false)) {
	/**
	 * This file exists only for static analysis.
	 *
	 * Minimal PHPUnit TestSuite stub for static analysis environments without PHPUnit.
	 */
	class TestSuite
	{
		/**
		 * @param string $name Suite name
		 */
		public function __construct(string $name = '')
		{
			unset($name);
		}

		/**
		 * @param string $testSuite Test suite class name
		 * @return void
		 */
		public function addTestSuite($testSuite): void
		{
		}
	}
}
