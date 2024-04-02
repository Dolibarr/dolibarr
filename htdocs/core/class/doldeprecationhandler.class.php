<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
		$deprecatedProperties = $this->deprecatedProperties();
		if (isset($deprecatedProperties[$name])) {
			$newProperty = $deprecatedProperties[$name];
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
		$deprecatedProperties = $this->deprecatedProperties();
		if (isset($deprecatedProperties[$name])) {
			$newProperty = $deprecatedProperties[$name];
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
		$deprecatedProperties = $this->deprecatedProperties();
		if (isset($deprecatedProperties[$name])) {
			$newProperty = $deprecatedProperties[$name];
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
		$deprecatedProperties = $this->deprecatedProperties();
		if (isset($deprecatedProperties[$name])) {
			$newProperty = $deprecatedProperties[$name];
			$msg = "DolDeprecationHandler: Accessing deprecated property '".$name."' on class ".get_class($this).". Use '".$newProperty."' instead.".self::getCallerInfoString();
			dol_syslog($msg);
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error($msg, E_USER_DEPRECATED);
			}
			return isset($newProperty);
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
		$deprecatedMethods = $this->deprecatedMethods();
		if (isset($deprecatedMethods[$name])) {
			$newMethod = $deprecatedMethods[$name];
			if ($this->isDeprecatedReportingEnabled()) {
				trigger_error("Calling deprecated method '".$name."' on class ".get_class($this).". Use '".$newMethod."' instead.".self::getCallerInfoString(), E_USER_DEPRECATED);
			}
			if (method_exists($this, $newMethod)) {
				return call_user_func_array([$this, $newMethod], $arguments);
			} else {
				trigger_error("Replacement method '".$newMethod."' not implemented.", E_USER_NOTICE);
			}
		}
		trigger_error("Call to undefined method '".$name."'.".self::getCallerInfoString(), E_USER_ERROR);
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

		if (property_exists($this, 'enableDynamicProperties')) {
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
			// Add  deprecated properties and their replacements in subclass implementation
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
}
