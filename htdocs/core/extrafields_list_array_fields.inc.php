<?php
/*
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/extrafields_list_array_fields.inc.php
 *  \brief			Code for actions
 */

if (empty($extrafieldsobjectkey) && is_object($object)) {
	$extrafieldsobjectkey = $object->table_element;
}

// Loop to show all columns of extrafields from $obj, $extrafields and $db
if (!empty($extrafieldsobjectkey)) {
	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label']) > 0) {
		if (empty($extrafieldsobjectprefix)) {
			$extrafieldsobjectprefix = 'ef.';
		}

		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$extrafieldsobjectkey]['list'][$key])) {
				$arrayfields[$extrafieldsobjectprefix . $key] = array(
					'label' => $extrafields->attributes[$extrafieldsobjectkey]['label'][$key],
					'checked' => (($extrafields->attributes[$extrafieldsobjectkey]['list'][$key] < 0) ? 0 : 1),
					'position' => $extrafields->attributes[$extrafieldsobjectkey]['pos'][$key],
					'enabled' => (abs((int) $extrafields->attributes[$extrafieldsobjectkey]['list'][$key]) != 3 && $extrafields->attributes[$extrafieldsobjectkey]['perms'][$key]),
					'langfile' => $extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key],
					'help' => $extrafields->attributes[$extrafieldsobjectkey]['help'][$key],
				);
			}
		}
	}
}
