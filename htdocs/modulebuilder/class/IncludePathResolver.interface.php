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
 * \file    htdocs/modulebuilder/class/IncludePathResolver.interface.php
 * \ingroup modulebuilder
 * \brief   Resolves include target paths for the modulebuilder include rewrite.
 */

/**
 * Resolves include target paths for the modulebuilder include rewrite.
 */
interface IncludePathResolver
{
	/**
	 * Return the absolute filesystem path of a module-root-relative include target.
	 *
	 * @param string $relative Path relative to the module root (e.g. "class/myobject.class.php")
	 * @return string
	 */
	public function resolveAbsoluteFromModuleRoot(string $relative): string;

	/**
	 * Return the shortest relative path (for use after __DIR__) from the directory of $file
	 * to a module-root-relative include target.
	 *
	 * @param string $file     Absolute path of the file that contains the include
	 * @param string $relative Target path relative to the module root
	 * @return string
	 */
	public function resolveFromFileDir(string $file, string $relative): string;
}
