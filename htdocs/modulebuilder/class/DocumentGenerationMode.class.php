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
 * \file    htdocs/modulebuilder/class/DocumentGenerationMode.class.php
 * \ingroup modulebuilder
 * \brief   Document generation mode of a ModuleBuilder object.
 */

/**
 * Document generation mode of a ModuleBuilder object, chosen with the "includedocgeneration" option.
 */
final class DocumentGenerationMode
{
	const ENABLED = 'enabled';
	const DISABLED = 'disabled';

	/**
	 * @param bool $includeDocGeneration Value of the "includedocgeneration" option
	 * @return string One of ENABLED, DISABLED
	 */
	public static function fromFlag(bool $includeDocGeneration): string
	{
		return $includeDocGeneration ? self::ENABLED : self::DISABLED;
	}

	/**
	 * @param string $mode One of ENABLED, DISABLED
	 * @return bool
	 * @throws \InvalidArgumentException If the mode is unknown
	 */
	public static function isEnabled(string $mode): bool
	{
		if ($mode !== self::ENABLED && $mode !== self::DISABLED) {
			throw new \InvalidArgumentException('Unknown document generation mode "'.$mode.'"');
		}

		return $mode === self::ENABLED;
	}
}
