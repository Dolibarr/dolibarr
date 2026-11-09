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
 * \file    htdocs/modulebuilder/class/RightsGenerationMode.class.php
 * \ingroup modulebuilder
 * \brief   How ModuleBuilder generates the permissions of an object.
 */

/**
 * How ModuleBuilder generates the permissions of an object.
 */
final class RightsGenerationMode
{
	/** No permission is declared; generated pages and menus check none. */
	const NONE = 'none';

	/** Read, write and delete permissions, each checked by its own operation. */
	const AUTO = 'auto';

	/** Each operation is checked by a permission chosen among read, write and delete. */
	const CUSTOM = 'custom';

	/**
	 * Not instantiable.
	 */
	private function __construct()
	{
	}

	/**
	 * @return string[] Every mode, in display order
	 */
	public static function all(): array
	{
		return array(self::NONE, self::AUTO, self::CUSTOM);
	}

	/**
	 * @param string $mode Candidate mode
	 * @return bool True when $mode is one of the modes
	 */
	public static function isValid(string $mode): bool
	{
		return in_array($mode, self::all(), true);
	}
}
