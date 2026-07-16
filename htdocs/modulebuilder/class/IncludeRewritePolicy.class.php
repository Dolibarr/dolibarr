<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 * \file    htdocs/modulebuilder/class/IncludeRewritePolicy.class.php
 * \ingroup modulebuilder
 * \brief   Policy deciding which generated files may have their includes rewritten to __DIR__.
 */

/**
 * Policy deciding which generated files may have their includes rewritten to __DIR__.
 *
 * The ModuleBuilder engine files are never rewritten. The generated API file keeps the
 * dol_include_once() format because its includes are injected/removed by the legacy regex
 * of addObjectsToApiFile()/removeObjectFromApiFile().
 */
final class IncludeRewritePolicy
{
	/** @var bool Only generated runtime files are rewritten (never engine files) */
	public $generatedRuntimeOnly = true;

	/** @var string[] Engine files never rewritten */
	public $excludedPaths = array('core/lib/modulebuilder.lib.php', 'modulebuilder/index.php');

	/** @var string[] Files whose include format is driven by legacy regex and must stay dol_include_once */
	public $legacyRegexCompatPaths = array('core/lib/modulebuilder.lib.php');

	/**
	 * Tell whether a module-relative path is an excluded engine file.
	 *
	 * @param string $relPath Path relative to the module root
	 * @return bool
	 */
	public function isExcluded(string $relPath): bool
	{
		return in_array($relPath, $this->excludedPaths, true);
	}

	/**
	 * Tell whether a module-relative path is bound to legacy regex compatibility.
	 *
	 * @param string $relPath Path relative to the module root
	 * @return bool
	 */
	public function isLegacyRegexCompat(string $relPath): bool
	{
		return in_array($relPath, $this->legacyRegexCompatPaths, true);
	}

	/**
	 * Tell whether a module-relative path is the generated API file.
	 *
	 * The API file includes are injected/removed by the legacy regex of addObjectsToApiFile()
	 * so they must keep the dol_include_once() format.
	 *
	 * @param string $relPath    Path relative to the module root
	 * @param string $moduleName Module directory name
	 * @return bool
	 */
	public function isApiFile(string $relPath, string $moduleName): bool
	{
		return $relPath === 'class/api_'.strtolower($moduleName).'.class.php';
	}

	/**
	 * Tell whether a generated file may be rewritten.
	 *
	 * @param string $relPath    Path relative to the module root
	 * @param string $moduleName Module directory name
	 * @return bool True if the file may be rewritten
	 */
	public function shouldProcess(string $relPath, string $moduleName): bool
	{
		if (!preg_match('/\.php$/', $relPath)) {
			return false;
		}
		if ($this->isExcluded($relPath) || $this->isLegacyRegexCompat($relPath)) {
			return false;
		}
		if ($this->isApiFile($relPath, $moduleName)) {
			return false;
		}
		return true;
	}
}
