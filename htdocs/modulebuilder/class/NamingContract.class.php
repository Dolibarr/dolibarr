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
 * \file    htdocs/modulebuilder/class/NamingContract.class.php
 * \ingroup modulebuilder
 * \brief   Immutable value object for module/object name substitutions.
 */

/**
 * Immutable value object holding all case variants of a module/object pair.
 *
 * Provides the canonical, ordered substitution map used by modulebuilder template generation.
 * The substitution order in getSubstitutionMap() is deterministic: uppercase and mixed-case
 * tokens always precede lowercase ones, preventing partial matches when str_replace processes
 * entries sequentially on the same string.
 *
 * applyTo() uses str_replace() directly — NOT make_substitutions() — to avoid unintended
 * processing of Dolibarr's __(key)__ and __[key]__ patterns present in raw template content.
 */
final class NamingContract
{
	/** @var string PascalCase module name, e.g. "MyModule" */
	public $moduleNameCase;

	/** @var string Lowercase module name, e.g. "mymodule" */
	public $moduleNameLower;

	/** @var string Uppercase module name, e.g. "MYMODULE" */
	public $moduleNameUpper;

	/** @var string PascalCase object name, e.g. "MyObject" (empty string for module-only contracts) */
	public $objectNameCase;

	/** @var string Lowercase object name, e.g. "myobject" (empty string for module-only contracts) */
	public $objectNameLower;

	/** @var string Uppercase object name, e.g. "MYOBJECT" (empty string for module-only contracts) */
	public $objectNameUpper;

	/**
	 * @param string $moduleName Raw module name — accepts any casing, ucfirst() is applied
	 * @param string $objectName Raw object name — empty string creates a module-only contract
	 * @throws \InvalidArgumentException If module and object names are identical (case-insensitive)
	 */
	public function __construct(string $moduleName, string $objectName = '')
	{
		$this->moduleNameCase  = ucfirst($moduleName);
		$this->moduleNameLower = strtolower($moduleName);
		$this->moduleNameUpper = strtoupper($moduleName);

		if ($objectName === '') {
			$this->objectNameCase  = '';
			$this->objectNameLower = '';
			$this->objectNameUpper = '';
			return;
		}

		if (strtolower($moduleName) === strtolower($objectName)) {
			throw new \InvalidArgumentException(
				'Module and object names cannot be identical (case-insensitive match): "'
				. $moduleName . '" vs "' . $objectName . '"'
			);
		}

		$this->objectNameCase  = ucfirst($objectName);
		$this->objectNameLower = strtolower($objectName);
		$this->objectNameUpper = strtoupper($objectName);
	}

	/**
	 * Returns the canonical, ordered substitution map.
	 *
	 * Order matters: uppercase and mixed-case tokens precede lowercase tokens so that
	 * str_replace sequential processing cannot partially consume a longer token variant.
	 * Object tokens (positions 8–12) are omitted for module-only contracts (objectName = '').
	 *
	 * @return array<string, string>
	 */
	public function getSubstitutionMap(): array
	{
		$map = [
			'MYMODULE'   => $this->moduleNameUpper,
			'MyModule'   => $this->moduleNameCase,
			'My module'  => $this->moduleNameCase,
			'my module'  => $this->moduleNameLower,
			'Mon module' => $this->moduleNameCase,
			'mon module' => $this->moduleNameLower,
			'mymodule'   => $this->moduleNameLower,
		];

		if ($this->objectNameLower !== '') {
			$map['MYOBJECT']  = $this->objectNameUpper;
			$map['MyObject']  = $this->objectNameCase;
			$map['My Object'] = $this->objectNameCase;
			$map['my object'] = $this->objectNameLower;
			$map['myobject']  = $this->objectNameLower;
		}

		return $map;
	}

	/**
	 * Apply the canonical substitution map to a string (file content).
	 *
	 * @param string $content File content to process
	 * @return string Content with substitutions applied
	 */
	public function applyTo(string $content): string
	{
		$map = $this->getSubstitutionMap();
		return str_replace(array_keys($map), array_values($map), $content);
	}

	/**
	 * Apply substitution to a filename (lowercase tokens only).
	 *
	 * Filenames in Dolibarr are always lowercase — only mymodule/myobject are substituted.
	 * For module-only contracts (objectNameLower = ''), myobject is not substituted.
	 *
	 * @param string $filename Template filename containing mymodule/myobject placeholders
	 * @return string File name with substitutions applied
	 */
	public function applyToFilename(string $filename): string
	{
		$search  = ['mymodule'];
		$replace = [$this->moduleNameLower];

		if ($this->objectNameLower !== '') {
			$search[]  = 'myobject';
			$replace[] = $this->objectNameLower;
		}

		return str_replace($search, $replace, $filename);
	}
}
