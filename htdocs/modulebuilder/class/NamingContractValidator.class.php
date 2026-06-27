<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 * Copyright (C) 2026		MDW				<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    htdocs/modulebuilder/class/NamingContractValidator.class.php
 * \ingroup modulebuilder
 * \brief   Validator for generated file content — detects residual naming tokens.
 */

/**
 * Validates that generated modulebuilder files contain no residual myobject/mymodule tokens.
 */
interface NamingContractValidator
{
	/**
	 * Scan file content for residual naming tokens after substitution.
	 *
	 * Lines containing non-renamable MODULEBUILDER structural markers are excluded from validation.
	 *
	 * @param string $content  Full file content to validate
	 * @param string $filePath Used in error messages only
	 * @return string[]        List of human-readable errors — empty array means valid
	 */
	public function validateContent(string $content, string $filePath): array;

	/**
	 * Validate that a PHP class name matches the contract's objectNameCase.
	 *
	 * @param string $className Class name to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if className matches object naming
	 */
	public function validateClassName(string $className, NamingContract $nc): bool;

	/**
	 * Validate that a trigger filename matches the expected pattern.
	 *
	 * Expected pattern: interface_NN_mod{ModuleNameCase}_{ModuleNameCase}Triggers.class.php
	 *
	 * @param string $filename Trigger filename to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if filename matches pattern
	 */
	public function validateTriggerFilename(string $filename, NamingContract $nc): bool;

	/**
	 * Validate that a URL path contains objectNameLower and no residual 'myobject'.
	 *
	 * @param string $url URL path to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if url path is cleaned from myobject and has objectNameLower
	 */
	public function validateUrl(string $url, NamingContract $nc): bool;

	/**
	 * Validate a rights key matches format "moduleNameLower.objectNameLower.perms".
	 *
	 * @param string $rightsKey Rights key to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if the rightsKey matches expected format
	 */
	public function validateRightsKey(string $rightsKey, NamingContract $nc): bool;
}

/**
 * Strict implementation — reports any residual myobject/mymodule token as a warning.
 *
 * Lines containing MODULEBUILDER structural markers (/* BEGIN MODULEBUILDER ... *‌/) are
 * excluded from validation because those markers are intentional template anchors that must
 * remain as-is (e.g. /* BEGIN MODULEBUILDER API MYOBJECT *‌/ is used by addObjectsToApiFile).
 */
final class StrictNamingContractValidator implements NamingContractValidator
{
	/**
	 * Substrings that identify a MODULEBUILDER structural marker line.
	 * Lines containing any of these are excluded from residual-token validation.
	 *
	 * @var string[]
	 */
	private const NON_RENAMABLE_MARKERS = [
		'/* BEGIN MODULEBUILDER ',
		'/* END MODULEBUILDER ',
	];

	/**
	 * @param string $content  Full file content to validate
	 * @param string $filePath File path used in error messages
	 * @return string[]
	 */
	public function validateContent(string $content, string $filePath): array
	{
		$errors = [];
		$lines  = explode("\n", $content);

		foreach ($lines as $lineIndex => $line) {
			if ($this->lineContainsNonRenamableMarker($line)) {
				continue;
			}
			if (preg_match('/\bmyobject\b/i', $line)) {
				$errors[] = $filePath . ':' . ($lineIndex + 1) . " — residual 'myobject' token detected";
			}
			if (preg_match('/\bmymodule\b/i', $line)) {
				$errors[] = $filePath . ':' . ($lineIndex + 1) . " — residual 'mymodule' token detected";
			}
		}

		return $errors;
	}

	/**
	 * @param string $line Line content to check
	 * @return bool True if there is a NON_RENAMABLE_MARKER in the line
	 */
	private function lineContainsNonRenamableMarker(string $line): bool
	{
		foreach (self::NON_RENAMABLE_MARKERS as $marker) {
			if (strpos($line, $marker) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $className Class name to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if class name is valid
	 */
	public function validateClassName(string $className, NamingContract $nc): bool
	{
		return $className === $nc->objectNameCase;
	}

	/**
	 * @param string $filename Trigger filename to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if filename for Trigger is valid
	 */
	public function validateTriggerFilename(string $filename, NamingContract $nc): bool
	{
		$pattern = '/^interface_\d{2}_mod'
			. preg_quote($nc->moduleNameCase, '/')
			. '_'
			. preg_quote($nc->moduleNameCase, '/')
			. 'Triggers\.class\.php$/';
		return (bool) preg_match($pattern, $filename);
	}

	/**
	 * @param string $url URL path to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if urlpath for module item is valid
	 */
	public function validateUrl(string $url, NamingContract $nc): bool
	{
		return $nc->objectNameLower !== ''
			&& strpos($url, $nc->objectNameLower) !== false
			&& strpos($url, 'myobject') === false;
	}

	/**
	 * @param string $rightsKey Rights key to validate
	 * @param NamingContract $nc Naming contract to compare against
	 * @return bool True if $rightskey is valid for this module
	 */
	public function validateRightsKey(string $rightsKey, NamingContract $nc): bool
	{
		$pattern = '/^'
			. preg_quote($nc->moduleNameLower, '/')
			. '\.'
			. preg_quote($nc->objectNameLower, '/')
			. '\.\w+$/';
		return (bool) preg_match($pattern, $rightsKey);
	}
}
