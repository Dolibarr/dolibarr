<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/RestlerAutoLoaderTest.php
 * \ingroup test
 * \brief Regression test for stateful autoloaders used alongside Restler.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../htdocs/includes/restler/framework/Luracast/Restler/AutoLoader.php';

/**
 * Restler must keep registered object loaders usable as fallbacks.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RestlerAutoLoaderTest extends TestCase
{
	/** @var int Calls made on the original loader instance. */
	private $loadCalls = 0;

	/**
	 * Stateful SPL loader with the usual void return value.
	 *
	 * @param string $className Class to load
	 * @return void
	 */
	public function loadFixture($className)
	{
		if ($className === 'DolibarrRestlerAutoloadFixture') {
			$this->loadCalls++;
			class_alias(__CLASS__, $className);
		}
	}

	/**
	 * A loader registered as an object method must retain its instance state.
	 *
	 * @return void
	 */
	public function testStatefulObjectLoaderIsCalled()
	{
		$originalLoaders = spl_autoload_functions();
		$originalIncludePath = get_include_path();
		$reflection = new ReflectionClass(\Luracast\Restler\AutoLoader::class);
		$originalState = $reflection->getStaticProperties();
		$loaded = false;
		try {
			// Isolate the singleton when an earlier test already bootstrapped Restler.
			$defaults = $reflection->getDefaultProperties();
			foreach ($originalState as $name => $value) {
				$property = $reflection->getProperty($name);
				$property->setAccessible(true);
				$property->setValue(null, $defaults[$name]);
			}
			spl_autoload_register(array($this, 'loadFixture'));
			spl_autoload_register(\Luracast\Restler\AutoLoader::instance());
			$loaded = class_exists('DolibarrRestlerAutoloadFixture');
		} finally {
			foreach (spl_autoload_functions() as $loader) {
				spl_autoload_unregister($loader);
			}
			foreach ($originalLoaders as $loader) {
				spl_autoload_register($loader);
			}
			set_include_path($originalIncludePath);
			foreach ($originalState as $name => $value) {
				$property = $reflection->getProperty($name);
				$property->setAccessible(true);
				$property->setValue(null, $value);
			}
		}

		$this->assertTrue($loaded, 'Restler must delegate to the registered object loader.');
		$this->assertSame(1, $this->loadCalls, 'The original loader instance must be called.');
	}
}
