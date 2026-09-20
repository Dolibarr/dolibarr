<?php
/* Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * @file          DeprecationHandler.php
 * @ingroup       core
 * @brief         trait for handling deprecated properties and methods
 */

/**
 * Class for handling deprecated properties and methods
 */
trait DolDeprecationHandler
{
	// Define the following in the class using the trait
	// to allow properties to not be defined when referenced.
	// So only deprecated value generate exceptions.
	//
	// protected $enableDynamicProperties = true;

	// Define the following in the class using the trait
	// to disallow Dolibarr deprecation warnings.
	//
	// protected $enableDeprecatedReporting = false;

	/**
	 * Get deprecated property
	 *
	 * @param string 	$name	Name of property
	 * @return mixed	Value for replacement property
	 */
	public function __get($name)
	{
		$newProperty = $this->getReplacementProperty($name);
		if ($newProperty !== null) {
			$msg = "DolDeprecationHandler: Accessing deprecated property '".$name."' on class ".get_class($this).". Use '".$newProperty."' instead.".self::getCallerInfoString();
			dol_syslog($msg);
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error($msg, E_USER_DEPRECATED);
			}
			return $this->$newProperty;
		}
		if ($this->isDynamicPropertiesEnabled()) {
			return null;  // If the property is set, then __get is not called.
		}
		$msg = "DolDeprecationHandler: Undefined property '".$name."'".self::getCallerInfoString();
		dol_syslog($msg);
		trigger_error($msg, E_USER_NOTICE);
		return $this->$name;  // Returning value anyway (graceful degradation)
	}

	/**
	 * Set deprecated property
	 *
	 * @param string 	$name	Name of property
	 * @param mixed		$value	Value of property
	 * @return void
	 */
	public function __set($name, $value)
	{
		$newProperty = $this->getReplacementProperty($name);
		if ($newProperty !== null) {
			// Setting is for compatibility, should not be a problem and should be reported only in paranoid mode
			/*
			$msg = "DolDeprecationHandler: Setting value to the deprecated property '".$name."'. Use '".$newProperty."' instead.".self::getCallerInfoString();
			dol_syslog($msg);
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error($msg, E_USER_DEPRECATED);
			}
			*/

			$this->$newProperty = $value;
			return;
		}
		if (!$this->isDynamicPropertiesEnabled()) {
			$msg = "DolDeprecationHandler: Undefined property '".$name."'".self::getCallerInfoString();
			trigger_error($msg, E_USER_NOTICE);
			$this->$name = $value;  // Setting anyway for graceful degradation
		} else {
			$this->$name = $value;
		}
	}

	/**
	 * Unset deprecated property
	 *
	 * @param string 	$name	Name of property
	 * @return void
	 */
	public function __unset($name)
	{
		$newProperty = $this->getReplacementProperty($name);
		if ($newProperty !== null) {
			// Unsetting is for compatibility, should not be a problem and should be reported only in paranoid mode
			/*
			$msg = "DolDeprecationHandler: Unsetting deprecated property '".$name."'. Use '".$newProperty."' instead.".self::getCallerInfoString();
			dol_syslog($msg);
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error($msg, E_USER_DEPRECATED);
			}
			*/
			unset($this->$newProperty);
			return;
		}
		if (!$this->isDynamicPropertiesEnabled()) {
			$msg = "DolDeprecationHandler: Undefined property '".$name."'.".self::getCallerInfoString();
			dol_syslog($msg);
			trigger_error($msg, E_USER_NOTICE);
		}
	}

	/**
	 * Test if deprecated property is set
	 *
	 * @param string 	$name	Name of property
	 * @return void
	 */
	public function __isset($name)
	{
		$newProperty = $this->getReplacementProperty($name);
		if ($newProperty !== null) {
			$msg = "DolDeprecationHandler: Accessing deprecated property '".$name."' on class ".get_class($this).". Use '".$newProperty."' instead.".self::getCallerInfoString();
			dol_syslog($msg);
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error($msg, E_USER_DEPRECATED);
			}
			return isset($this->$newProperty);
		} elseif ($this->isDynamicPropertiesEnabled()) {
			return isset($this->$name);
		}
		$msg = "DolDeprecationHandler: Undefined property '".$name."'.".self::getCallerInfoString();
		dol_syslog($msg);
		// trigger_error("Undefined property '$name'.".self::getCallerInfoString(), E_USER_NOTICE);
		return isset($this->$name);
	}

	/**
	 * Call deprecated method
	 *
	 * @param string 	$name		Name of method
	 * @param mixed[]	$arguments	Method arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		$newMethod = $this->getReplacementMethod($name);
		if ($newMethod !== null) {
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error("Calling deprecated method '".$name."' on class ".get_class($this).". Use '".$newMethod."' instead.".self::getCallerInfoString(), E_USER_DEPRECATED);
			}
			if (method_exists($this, $newMethod)) {
				return call_user_func_array([$this, $newMethod], $arguments);
			} else {
				trigger_error("Replacement method '".$newMethod."' not implemented.", E_USER_NOTICE);
			}
		}
		// Use Exception instead of trigger_error with E_USER_ERROR (deprecated in PHP 8.4)
		throw new Exception("Call to undefined method '".$name."'.".self::getCallerInfoString());
	}


	/**
	 * Indicate if deprecations should be reported. Depends on ->enableDeprecatedReporting. If not set, depends on PHP setup.
	 *
	 * @return bool
	 */
	private function isDeprecatedReportingEnabled()
	{
		// By default, if enableDeprecatedReporting is set, use that value.

		if (property_exists($this, 'enableDeprecatedReporting')) {
			// If the property exists, then we use it.
			return (bool) $this->enableDeprecatedReporting;
		}

		return (error_reporting() & E_DEPRECATED) === E_DEPRECATED;
	}

	/**
	 * Indicate if dynamic properties are accepted
	 *
	 * @return bool
	 */
	private function isDynamicPropertiesEnabled()
	{
		// By default, if enableDynamicProperties is set, use that value.

		if (property_exists($this, 'enableDynamicProperties')) { // @phpstan-ignore-line
			// If the property exists, then we use it.
			return (bool) $this->enableDynamicProperties;
		}

		// Otherwise it depends on a choice

		// 1. Return true to accept DynamicProperties in all cases.
		return true;
		// 2. Accept dynamic properties only when not testing
		// return !class_exists('PHPUnit\Framework\TestSuite')
		// 3. Accept dynamic properties only when deprecation notifications are disabled
		// return $this->isDeprecatedReportingEnabled();
		// 4. Do not accept dynamic properties (should be the default eventually).
		// return false;
	}

	/**
	 * Provide list of deprecated properties
	 *
	 * Override this method in subclasses
	 *
	 * @return array<string,string>	Mapping of deprecated properties
	 */
	protected function deprecatedProperties()
	{
		// Define deprecated properties and their replacements
		return array(
			// 'oldProperty' => 'newProperty',
			// Add deprecated properties and their replacements in subclass implementation
		);
	}

	/**
	 * Provide list of deprecated methods
	 *
	 * Override this method in subclasses
	 *
	 * @return array<string,string>	Mapping of deprecated methods
	 */
	protected function deprecatedMethods()
	{
		// Define deprecated methods and their replacements
		return array(
			// 'oldMethod' => 'newMethod',
			// Add  deprecated methods and their replacements in subclass implementation
		);
	}

	/**
	 * Get replacement property name for a deprecated property
	 *
	 * @param string $oldProperty Name of the deprecated property
	 * @return string|null Name of the replacement property, or null if not deprecated
	 * @throws Exception If the replacement property name is the same as the old property name
	 */
	private function getReplacementProperty(string $oldProperty): ?string
	{
		$deprecatedProperties = $this->deprecatedProperties();
		if (!isset($deprecatedProperties[$oldProperty])) {
			return null;
		}

		$newProperty = $deprecatedProperties[$oldProperty];

		// Validate that the new property name is different from the old one
		if ($newProperty === $oldProperty) {
			throw new Exception("DolDeprecationHandler: Configuration error - replacement property name for '$oldProperty' is the same as the old property name on class " . get_class($this) . ".");
		}

		return $newProperty;
	}

	/**
	 * Get replacement method name for a deprecated method
	 *
	 * @param string $oldMethod Name of the deprecated method
	 * @return string|null Name of the replacement method, or null if not deprecated
	 * @throws Exception If the replacement method name is the same as the old method name
	 */
	private function getReplacementMethod(string $oldMethod): ?string
	{
		$deprecatedMethods = $this->deprecatedMethods();
		if (!isset($deprecatedMethods[$oldMethod])) {
			return null;
		}

		$newMethod = $deprecatedMethods[$oldMethod];

		// Validate that the new method name is different from the old one
		if ($newMethod === $oldMethod) {
			throw new Exception("DolDeprecationHandler: Configuration error - replacement method name for '$oldMethod' is the same as the old method name on class " . get_class($this) . ".");
		}

		return $newMethod;
	}


	/**
	 * Get caller info
	 *
	 * @return string
	 */
	final protected static function getCallerInfoString()
	{
		$backtrace = debug_backtrace();
		$msg = "";
		if (count($backtrace) >= 2) {
			$trace = $backtrace[1];
			if (isset($trace['file'], $trace['line'])) {
				$msg = " From {$trace['file']}:{$trace['line']}.";
			}
		}
		return $msg;
	}

	/**
	 * Verify that deprecated properties and methods have been removed
	 * This is called automatically in test environments via __destruct
	 *
	 * @return void
	 */
	protected function verifyDeprecatedItemsRemoved()
	{
		// Check deprecated properties
		$deprecatedProperties = $this->deprecatedProperties();
		foreach (array_keys($deprecatedProperties) as $oldProperty) {
			// This will also validate that newProperty != oldProperty via getReplacementProperty
			$replacementProperty = $this->getReplacementProperty($oldProperty);
			if (property_exists($this, $oldProperty)) {
				// Use Exception instead of trigger_error with E_USER_ERROR (deprecated in PHP 8.4)
				throw new Exception("DolDeprecationHandler: Old property '$oldProperty' still exists on class " . get_class($this) . ". It should be commented out or removed since it is mapped as deprecated.");
			}
		}

		// Check deprecated methods
		$deprecatedMethods = $this->deprecatedMethods();
		foreach (array_keys($deprecatedMethods) as $oldMethod) {
			// This will also validate that newMethod != oldMethod via getReplacementMethod
			$replacementMethod = $this->getReplacementMethod($oldMethod);
			if (method_exists($this, $oldMethod)) {
				// Use Exception instead of trigger_error with E_USER_ERROR (deprecated in PHP 8.4)
				throw new Exception("DolDeprecationHandler: Old method '$oldMethod' still exists on class " . get_class($this) . ". It should be commented out or removed since it is mapped as deprecated.");
			}
		}
	}

	/**
	 * Destructor that verifies deprecated properties and methods have been removed
	 * This verification only runs in test environments
	 *
	 * @return void
	 */
	public function __destruct()
	{
		// Only verify in test environment
		if (class_exists('PHPUnit\Framework\TestSuite')) {
			$this->verifyDeprecatedItemsRemoved();
		}
	}
}
