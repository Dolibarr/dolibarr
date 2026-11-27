<?php
/* Copyright (C) 2026		iProspective			<contact@iprospective.fr>
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
 *	\file		htdocs/core/lib/easteregg.lib.php
 *	\ingroup	core
 *	\brief		Small hidden surprises of Dolibarr (easter eggs)
 */


/**
 * Return true if one of the searched strings asks for the DoliBear.
 *
 * The DoliBear, official mascot of Dolibarr, walks on the page and makes elements vanish
 * with its magic wand when "dolibear" is searched in the list of thirdparties.
 * Can be disabled with the constant MAIN_DISABLE_EASTER_EGGS.
 *
 * @param	array<mixed>	$searches	Values typed by the user in search fields
 * @return	bool						True if the DoliBear must appear
 */
function dolibearIsTriggered($searches)
{
	if (getDolGlobalInt('MAIN_DISABLE_EASTER_EGGS')) {
		return false;
	}

	foreach ($searches as $search) {
		if (is_string($search) && dol_strtolower(trim($search)) === 'dolibear') {
			return true;
		}
	}

	return false;
}
