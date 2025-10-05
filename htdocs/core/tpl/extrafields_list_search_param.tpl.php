<?php
/* Copyright (C) 2025		Frédéric France			<frederic.france@free.fr>
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
 *  \brief      Include file extrafields columns as parameters in $param
 */

/**
 * @var Conf $conf
 * @var CommonObject $object
 * @var ExtraFields $extrafields
 *
 * @var array<string,mixed>	$search_array_options
 * @var string				$search_options_pattern
 * @var string 				$param
 * @var string				$extrafieldsobjectkey
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

// Loop to complete $param for extrafields
if (!empty($search_array_options) && is_array($search_array_options)) {	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (empty($search_options_pattern)) {
		$search_options_pattern = 'search_options_';
	}
	if (empty($extrafieldsobjectkey) && is_object($object)) {
		$extrafieldsobjectkey = $object->table_element;
	}

	foreach ($search_array_options as $key => $val) {
		$tmpkey = preg_replace('/'.$search_options_pattern.'/', '', $key);
		// date range from list filters is stored as array('start' => <timestamp>, 'end' => <timestamp>)
		if (is_array($val) && array_key_exists('start', $val)) {
			// start date
			$param .= '&'.$search_options_pattern.$tmpkey.'_startyear='.dol_print_date($val['start'], '%Y');
			$param .= '&'.$search_options_pattern.$tmpkey.'_startmonth='.dol_print_date($val['start'], '%m');
			$param .= '&'.$search_options_pattern.$tmpkey.'_startday='.dol_print_date($val['start'], '%d');
			$param .= '&'.$search_options_pattern.$tmpkey.'_starthour='.dol_print_date($val['start'], '%H');
			$param .= '&'.$search_options_pattern.$tmpkey.'_startmin='.dol_print_date($val['start'], '%M');
		} elseif (is_array($val) && array_key_exists('end', $val)) {
			// end date
			$param .= '&'.$search_options_pattern.$tmpkey.'_endyear='.dol_print_date($val['end'], '%Y');
			$param .= '&'.$search_options_pattern.$tmpkey.'_endmonth='.dol_print_date($val['end'], '%m');
			$param .= '&'.$search_options_pattern.$tmpkey.'_endday='.dol_print_date($val['end'], '%d');
			$param .= '&'.$search_options_pattern.$tmpkey.'_endhour='.dol_print_date($val['end'], '%H');
			$param .= '&'.$search_options_pattern.$tmpkey.'_endmin='.dol_print_date($val['end'], '%M');
			$val = '';
		} elseif ($val !== '' && $val !== null && $val !== []) {
			if (is_array($val)) {
				foreach ($val as $val2) {
					$param .= '&'.$search_options_pattern.$tmpkey.'[]='.urlencode($val2);
				}
			} else {
				// test if we have checkbox type, we add the _multiselect needed into param
				$tmpkey = preg_replace('/'.$search_options_pattern.'/', '', $key);
				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$tmpkey], array('checkbox', 'chkbxlst'))) {
					$param .= '&'.$search_options_pattern.$tmpkey.'_multiselect='.urlencode($val);
				}
				// test if we have boolean type, we add the _booleand needed into param
				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$tmpkey], array('boolean'))) {
					$param .= '&'.$search_options_pattern.$tmpkey.'_boolean='.urlencode($val);
				}

				$param .= '&'.$search_options_pattern.$tmpkey.'='.urlencode($val);
			}
		}
	}
}
