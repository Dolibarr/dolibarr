<?php
/* Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/tpl/extrafields_list_search_sql.tpl.php
 *  \brief      Include file extrafields columns on the line for title of fields
 */

/**
 * @var Conf $conf
 * @var CommonObject $object
 * @var ?Translate $langs
 *
 * @var ?string $extrafieldsobjectkey
 * @var ?int   $disablesortlink
 * @var string $sortfield
 * @var string $sortorder
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'
@phan-var-force ?int<0,1> $disablesortlink
';

if (empty($extrafieldsobjectkey) && is_object($object)) {
	$extrafieldsobjectkey = $object->table_element;
}
if (!isset($disablesortlink)) {
	$disablesortlink = 0;
}

// Loop to show all columns of extrafields for the title line
if (!empty($extrafieldsobjectkey)) {	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (!empty($extrafields->attributes[$extrafieldsobjectkey]['label']) && is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label'])) {
		if (empty($extrafieldsobjectprefix)) {
			$extrafieldsobjectprefix = 'ef.';
		}

		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val) {
			if (!empty($arrayfields[$extrafieldsobjectprefix.$key]['checked'])) {
				if ($extrafields->attributes[$extrafieldsobjectkey]['type'][$key] == 'separate') {
					continue;
				}

				$cssclasstd = $extrafields->getCSSClass($key, $extrafieldsobjectkey, 'csslist');

				$sortonfield = $extrafieldsobjectprefix.$key;
				if (!empty($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key])) {
					$sortonfield = '';
				}

				if (!empty($extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key]) && is_object($langs)) {
					$langs->load($extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key]);
				}

				$tooltip = empty($extrafields->attributes[$extrafieldsobjectkey]['help'][$key]) ? '' : $extrafields->attributes[$extrafieldsobjectkey]['help'][$key];

				// Show cell
				print getTitleFieldOfList($extrafields->attributes[$extrafieldsobjectkey]['label'][$key], 0, $_SERVER["PHP_SELF"], $sortonfield, "", $param, 'data-titlekey="'.$key.'"', $sortfield, $sortorder, $cssclasstd.' ', $disablesortlink, $tooltip)."\n";
				if (isset($totalarray) && isset($totalarray['nbfield'])) {
					$totalarray['nbfield']++;
				}
			}
		}
	}
}
