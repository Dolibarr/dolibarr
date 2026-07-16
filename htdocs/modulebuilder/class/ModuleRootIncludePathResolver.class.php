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
 * \file    htdocs/modulebuilder/class/ModuleRootIncludePathResolver.class.php
 * \ingroup modulebuilder
 * \brief   Resolver computing __DIR__-relative include paths inside a generated module tree.
 */

require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/IncludePathResolver.interface.php';

/**
 * Resolver computing __DIR__-relative include paths inside a generated module tree.
 */
final class ModuleRootIncludePathResolver implements IncludePathResolver
{
	/** @var string Absolute module root directory, without trailing slash */
	private string $moduleRoot;

	/**
	 * Constructor
	 *
	 * @param string $moduleRoot Absolute module root directory
	 */
	public function __construct(string $moduleRoot)
	{
		$this->moduleRoot = rtrim($moduleRoot, '/');
	}

	/**
	 * Return the absolute filesystem path of a module-root-relative include target.
	 *
	 * @param string $relative Path relative to the module root
	 * @return string
	 */
	public function resolveAbsoluteFromModuleRoot(string $relative): string
	{
		return $this->moduleRoot.'/'.ltrim($relative, '/');
	}

	/**
	 * Return the shortest relative path (for use after __DIR__) from dirname($file) to the target.
	 *
	 * @param string $file     Absolute path of the file containing the include
	 * @param string $relative Target path relative to the module root
	 * @return string
	 */
	public function resolveFromFileDir(string $file, string $relative): string
	{
		$target = $this->resolveAbsoluteFromModuleRoot($relative);
		$fromParts = explode('/', trim(dirname($file), '/'));
		$toParts = explode('/', trim($target, '/'));

		$i = 0;
		$max = min(count($fromParts), count($toParts));
		while ($i < $max && $fromParts[$i] === $toParts[$i]) {
			$i++;
		}
		$up = count($fromParts) - $i;
		$down = array_slice($toParts, $i);
		return str_repeat('../', $up).implode('/', $down);
	}
}
