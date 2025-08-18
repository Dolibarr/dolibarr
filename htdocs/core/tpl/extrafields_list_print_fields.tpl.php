<?php
/* Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
 *
 */

/**
 * @var Conf $conf
 * @var CommonObject $object
 * @var ExtraFields $extrafields
 *
 * @var string	$extrafieldsobjectkey
 */

'
@phan-var-force CommonObject $object
';

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (empty($extrafieldsobjectkey) && is_object($object)) {
	$extrafieldsobjectkey = $object->table_element;
}

// Loop to show all columns of extrafields from $obj, $extrafields and $db
if (!empty($extrafieldsobjectkey) && !empty($extrafields->attributes[$extrafieldsobjectkey])) {	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (array_key_exists('label', $extrafields->attributes[$extrafieldsobjectkey]) && is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label'])) {
		if (empty($extrafieldsobjectprefix)) {
			$extrafieldsobjectprefix = 'ef.';
		}

		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val) {
			if (!empty($arrayfields[$extrafieldsobjectprefix.$key]['checked'])) {
				if ($extrafields->attributes[$extrafieldsobjectkey]['type'][$key] == 'separate') {
					continue;
				}

				$cssclasstd = $extrafields->getCSSClass($key, $extrafieldsobjectkey, 'csslist');
				$cssclassview = $extrafields->getCSSClass($key, $extrafieldsobjectkey, 'cssview');

				$tmpkey = 'options_'.$key;

				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('date', 'datetime', 'timestamp')) && isset($obj->$tmpkey) && !is_numeric($obj->$tmpkey)) {
					$datenotinstring = $obj->$tmpkey;
					if (!is_numeric($obj->$tmpkey)) {	// For backward compatibility
						$datenotinstring = $db->jdate($datenotinstring);
					}
					$value = $datenotinstring;
				} elseif (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('int'))) {
					$value = $obj->$tmpkey ?? (isset($obj->array_options[$tmpkey]) ? $obj->array_options[$tmpkey] : null) ?? '';
				} else {
					// The key may be in $obj->array_options if not in $obj
					$value = (isset($obj->$tmpkey) ? $obj->$tmpkey :
						(isset($obj->array_options[$tmpkey]) ? $obj->array_options[$tmpkey] : ''));
				}
				// If field is a computed field, we make computation to get value
				if ($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key]) {
					$objectoffield = $object; // For compatibility with the computed formula. $objectoffield is exported by dol_eval().
					$value = dol_eval((string) $extrafields->attributes[$extrafieldsobjectkey]['computed'][$key], 1, 1, '2');
					if (is_numeric(price2num($value)) && $extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key]) {
						$obj->$tmpkey = price2num($value);
					}
				}

				$valuetoshow = $extrafields->showOutputField($key, $value, '', $extrafieldsobjectkey, null, $object ?? null);
				$title = dol_string_nohtmltag($valuetoshow);

				print '<td'.($cssclasstd ? ' class="'.$cssclasstd.'"' : '');
				print ' data-key="'.$extrafieldsobjectkey.'.'.$key.'"';
				print ($title && !in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('stars')) ? ' title="'.dol_escape_htmltag($title).'"' : '');
				print '>';
				print $cssclassview ? '<span class="'.$cssclassview.'">' : '';
				print $valuetoshow;
				print $cssclassview ? '</span>' : '';
				print '</td>';

				if (!$i) {
					if (empty($totalarray)) {
						$totalarray['nbfield'] = 0;
					}
					$totalarray['nbfield']++;
				}

				if (!empty($extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key])) {
					if (!$i) {
						// we keep position for the first line
						$totalarray['totalizable'][$key]['pos'] = $totalarray['nbfield'];
					}
					if (isset($obj->$tmpkey) && is_numeric($obj->$tmpkey)) {
						if (!isset($totalarray['totalizable'][$key]['total'])) {
							$totalarray['totalizable'][$key]['total'] = 0;
						}
						$totalarray['totalizable'][$key]['total'] += $obj->$tmpkey;
					}
				}
				// The key 'totalizable' on extrafields, is the same as 'isameasure' into ->fields
				if (!empty($extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key]) && $extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key] == 1) {
					if (!$i) {
						$totalarray['pos'][$totalarray['nbfield']] = $extrafieldsobjectprefix.$tmpkey;
					}
					if (!isset($totalarray['val'])) {
						$totalarray['val'] = array();
					}
					if (!isset($totalarray['val'][$extrafieldsobjectprefix.$tmpkey])) {
						$totalarray['val'][$extrafieldsobjectprefix.$tmpkey] = 0;
					}
					if (isset($obj->$tmpkey) && is_numeric($obj->$tmpkey)) {
						if (!isset($totalarray['val'][$extrafieldsobjectprefix.$tmpkey])) {
							$totalarray['val'][$extrafieldsobjectprefix.$tmpkey] = 0;
						}
						$totalarray['val'][$extrafieldsobjectprefix.$tmpkey] += $obj->$tmpkey;
					}
				}
			}
		}
	}
}
