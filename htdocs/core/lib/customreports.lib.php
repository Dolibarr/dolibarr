<?php
/* Copyright (C) 2024 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/customreports.lib.php
 *  \brief		Set of function to manipulate custom reports
 */

/**
 * Fill arrayofmesures for an object
 *
 * @param 	mixed		$object			Any object
 * @param	string		$tablealias		Alias of table
 * @param	string		$labelofobject	Label of object
 * @param	array		$arrayofmesures	Array of measures already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return 	array						Array of measures
 */
function fillArrayOfMeasures($object, $tablealias, $labelofobject, &$arrayofmesures, $level = 0, &$count = 0, &$tablepath = '')
{
	global $langs, $extrafields, $db;

	if (empty($object)) {	// Protection against bad use of method
		return array();
	}
	if ($level > 10) {	// Protection against infinite loop
		return $arrayofmesures;
	}

	if (empty($tablepath)) {
		$tablepath = $object->table_element.'='.$tablealias;
	} else {
		$tablepath .= ','.$object->table_element.'='.$tablealias;
	}

	if ($level == 0) {
		// Add the count of record only for the main/first level object. Parents are necessarily unique for each record.
		$arrayofmesures[$tablealias.'.count'] = array(
			'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans("Number"),
			'labelnohtml' => $labelofobject.': '.$langs->trans("Number"),
			'position' => 0,
			'table' => $object->table_element,
			'tablefromt' => $tablepath
		);
	}

	// Note: here $tablealias can be 't' or 't__fk_contract' or 't_fk_contract_fk_soc'

	// Add main fields of object
	foreach ($object->fields as $key => $val) {
		if (!empty($val['isameasure']) && (!isset($val['enabled']) || (int) dol_eval($val['enabled'], 1, 1, '1'))) {
			$position = (empty($val['position']) ? 0 : intval($val['position']));
			$arrayofmesures[$tablealias.'.'.$key.'-sum'] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Sum").')</span>',
				'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
				'position' => ($position + ($count * 100000)).'.1',
				'table' => $object->table_element,
				'tablefromt' => $tablepath
			);
			$arrayofmesures[$tablealias.'.'.$key.'-average'] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Average").')</span>',
				'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
				'position' => ($position + ($count * 100000)).'.2',
				'table' => $object->table_element,
				'tablefromt' => $tablepath
			);
			$arrayofmesures[$tablealias.'.'.$key.'-min'] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Minimum").')</span>',
				'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
				'position' => ($position + ($count * 100000)).'.3',
				'table' => $object->table_element,
				'tablefromt' => $tablepath
			);
			$arrayofmesures[$tablealias.'.'.$key.'-max'] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Maximum").')</span>',
				'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
				'position' => ($position + ($count * 100000)).'.4',
				'table' => $object->table_element,
				'tablefromt' => $tablepath
			);
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
				$arrayofmesures[$tablealias.'.'.$key.'-stddevpop'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("StandardDeviationPop").')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.5',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			}
		}
	}
	// Add extrafields to Measures
	if (!empty($object->isextrafieldmanaged) && isset($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key]) && (!isset($extrafields->attributes[$object->table_element]['enabled'][$key]) || (int) dol_eval($extrafields->attributes[$object->table_element]['enabled'][$key], 1, 1, '1'))) {
				// @phan-suppress-next-line PhanTypeMismatchDimAssignment
				$position = (!empty($val['position']) ? $val['position'] : 0);
				$arrayofmesures[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-sum'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Sum").')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofmesures[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-average'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Average").')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofmesures[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-min'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Minimum").')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofmesures[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-max'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Maximum").')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.4',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
					$arrayofmesures[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-stddevpop'] = array(
						'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("StandardDeviationPop").')</span>',
						'labelnohtml' => $labelofobject.': '.$langs->trans($val),
						'position' => ($position + ($count * 100000)).'.5',
						'table' => $object->table_element,
						'tablefromt' => $tablepath
					);
				}
			}
		}
	}
	// Add fields for parent objects
	foreach ($object->fields as $key => $val) {
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && !empty($tmptype[1]) && !empty($tmptype[2])) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					//var_dump($key); var_dump($tmpobject->element); var_dump($val['label']); var_dump($tmptype); var_dump('t-'.$key);
					$count++;
					$arrayofmesures = fillArrayOfMeasures($tmpobject, $tablealias.'__'.$key, $langs->trans($val['label']), $arrayofmesures, $level + 1, $count, $tablepath);
				} else {
					print 'For property '.$object->element.'->'.$key.', type="'.$val['type'].'": Failed to find class '.$newobject." in file ".$tmptype[2]."<br>\n";
				}
			}
		}
	}

	return $arrayofmesures;
}


/**
 * Fill arrayofmesures for an object
 *
 * @param 	mixed		$object			Any object
 * @param	string		$tablealias		Alias of table ('t' for example)
 * @param	string		$labelofobject	Label of object
 * @param	array		$arrayofxaxis	Array of xaxis already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return 	array						Array of xaxis
 */
function fillArrayOfXAxis($object, $tablealias, $labelofobject, &$arrayofxaxis, $level = 0, &$count = 0, &$tablepath = '')
{
	global $langs, $extrafields, $db;

	if (empty($object)) {	// Protection against bad use of method
		return array();
	}
	if ($level >= 3) {	// Limit scan on 2 levels max
		return $arrayofxaxis;
	}

	if (empty($tablepath)) {
		$tablepath = $object->table_element.'='.$tablealias;
	} else {
		$tablepath .= ','.$object->table_element.'='.$tablealias;
	}

	$YYYY = substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1);
	$MM = substr($langs->trans("Month"), 0, 1).substr($langs->trans("Month"), 0, 1);
	$DD = substr($langs->trans("Day"), 0, 1).substr($langs->trans("Day"), 0, 1);
	$HH = substr($langs->trans("Hour"), 0, 1).substr($langs->trans("Hour"), 0, 1);
	$MI = substr($langs->trans("Minute"), 0, 1).substr($langs->trans("Minute"), 0, 1);
	$SS = substr($langs->trans("Second"), 0, 1).substr($langs->trans("Second"), 0, 1);

	/*if ($level > 0) {
	 var_dump($object->element.' '.$object->isextrafieldmanaged);
	 }*/

	// Note: here $tablealias can be 't' or 't__fk_contract' or 't_fk_contract_fk_soc'

	// Add main fields of object
	foreach ($object->fields as $key => $val) {
		if (empty($val['measure'])) {
			if (in_array($key, array(
				'id', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
				'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) {
				continue;
			}
			if (isset($val['enabled']) && ! (int) dol_eval($val['enabled'], 1, 1, '1')) {
				continue;
			}
			if (isset($val['visible']) && ! (int) dol_eval($val['visible'], 1, 1, '1')) {
				continue;
			}
			if (preg_match('/^fk_/', $key) && !preg_match('/^fk_statu/', $key)) {
				continue;
			}
			if (preg_match('/^pass/', $key)) {
				continue;
			}
			if (in_array($val['type'], array('html', 'text'))) {
				continue;
			}
			if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
				$position = (empty($val['position']) ? 0 : intval($val['position']));
				$arrayofxaxis[$tablealias.'.'.$key.'-year'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofxaxis[$tablealias.'.'.$key.'-month'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.'-'.$MM.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofxaxis[$tablealias.'.'.$key.'-day'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.'-'.$MM.'-'.$DD.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			} else {
				$position = (empty($val['position']) ? 0 : intval($val['position']));
				$arrayofxaxis[$tablealias.'.'.$key] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']),
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)),
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			}
		}
	}

	// Add extrafields to X-Axis
	if (!empty($object->isextrafieldmanaged) && isset($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') {
				continue;
			}
			if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) {
				continue;
			}

			if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('timestamp', 'date', 'datetime'))) {
				$position = (empty($extrafields->attributes[$object->table_element]['pos'][$key]) ? 0 : intval($extrafields->attributes[$object->table_element]['pos'][$key]));
				$arrayofxaxis[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-year'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofxaxis[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-month'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.'-'.$MM.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofxaxis[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-day'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.'-'.$MM.'-'.$DD.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			} else {
				$arrayofxaxis[preg_replace('/^t/', 'te', $tablealias).'.'.$key] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val),
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => 1000 + (int) $extrafields->attributes[$object->table_element]['pos'][$key] + ($count * 100000),
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			}
		}
	}

	// Add fields for parent objects
	foreach ($object->fields as $key => $val) {
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && $tmptype[1] && $tmptype[2]) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					//var_dump($key); var_dump($tmpobject->element); var_dump($val['label']); var_dump($tmptype); var_dump('t-'.$key);
					$count++;
					$arrayofxaxis = fillArrayOfXAxis($tmpobject, $tablealias.'__'.$key, $langs->trans($val['label']), $arrayofxaxis, $level + 1, $count, $tablepath);
				} else {
					print 'For property '.$object->element.'->'.$key.', type="'.$val['type'].'": Failed to find class '.$newobject." in file ".$tmptype[2]."<br>\n";
				}
			}
		}
	}

	return $arrayofxaxis;
}


/**
 * Fill arrayofgrupby for an object
 *
 * @param 	mixed		$object			Any object
 * @param	string		$tablealias		Alias of table
 * @param	string		$labelofobject	Label of object
 * @param	array		$arrayofgroupby	Array of groupby already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return 	array						Array of groupby
 */
function fillArrayOfGroupBy($object, $tablealias, $labelofobject, &$arrayofgroupby, $level = 0, &$count = 0, &$tablepath = '')
{
	global $langs, $extrafields, $db;

	if (empty($object)) {	// Protection against bad use of method
		return array();
	}
	if ($level >= 3) {
		return $arrayofgroupby;
	}

	if (empty($tablepath)) {
		$tablepath = $object->table_element.'='.$tablealias;
	} else {
		$tablepath .= ','.$object->table_element.'='.$tablealias;
	}

	$YYYY = substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1);
	$MM = substr($langs->trans("Month"), 0, 1).substr($langs->trans("Month"), 0, 1);
	$DD = substr($langs->trans("Day"), 0, 1).substr($langs->trans("Day"), 0, 1);
	$HH = substr($langs->trans("Hour"), 0, 1).substr($langs->trans("Hour"), 0, 1);
	$MI = substr($langs->trans("Minute"), 0, 1).substr($langs->trans("Minute"), 0, 1);
	$SS = substr($langs->trans("Second"), 0, 1).substr($langs->trans("Second"), 0, 1);

	// Note: here $tablealias can be 't' or 't__fk_contract' or 't_fk_contract_fk_soc'

	// Add main fields of object
	foreach ($object->fields as $key => $val) {
		if (empty($val['isameasure'])) {
			if (in_array($key, array(
				'id', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
				'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) {
				continue;
			}
			if (isset($val['enabled']) && ! (int) dol_eval($val['enabled'], 1, 1, '1')) {
				continue;
			}
			if (isset($val['visible']) && ! (int) dol_eval($val['visible'], 1, 1, '1')) {
				continue;
			}
			if (preg_match('/^fk_/', $key) && !preg_match('/^fk_statu/', $key)) {
				continue;
			}
			if (preg_match('/^pass/', $key)) {
				continue;
			}
			if (in_array($val['type'], array('html', 'text'))) {
				continue;
			}
			if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
				$position = (empty($val['position']) ? 0 : intval($val['position']));
				$arrayofgroupby[$tablealias.'.'.$key.'-year'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[$tablealias.'.'.$key.'-month'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.'-'.$MM.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[$tablealias.'.'.$key.'-day'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.'-'.$MM.'-'.$DD.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			} else {
				$position = (empty($val['position']) ? 0 : intval($val['position']));
				$arrayofgroupby[$tablealias.'.'.$key] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']),
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)),
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			}
		}
	}

	// Add extrafields to Group by
	if (!empty($object->isextrafieldmanaged) && isset($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') {
				continue;
			}
			if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) {
				continue;
			}

			if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('timestamp', 'date', 'datetime'))) {
				$position = (empty($extrafields->attributes[$object->table_element]['pos'][$key]) ? 0 : intval($extrafields->attributes[$object->table_element]['pos'][$key]));
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-year'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-month'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.'-'.$MM.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-day'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.'-'.$MM.'-'.$DD.')</span>',
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			} else {
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val),
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => 1000 + (int) $extrafields->attributes[$object->table_element]['pos'][$key] + ($count * 100000),
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			}
		}
	}

	// Add fields for parent objects
	foreach ($object->fields as $key => $val) {
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && $tmptype[1] && $tmptype[2]) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					//var_dump($key); var_dump($tmpobject->element); var_dump($val['label']); var_dump($tmptype); var_dump('t-'.$key);
					$count++;
					$arrayofgroupby = fillArrayOfGroupBy($tmpobject, $tablealias.'__'.$key, $langs->trans($val['label']), $arrayofgroupby, $level + 1, $count, $tablepath);
				} else {
					print 'For property '.$object->element.'->'.$key.', type="'.$val['type'].'": Failed to find class '.$newobject." in file ".$tmptype[2]."<br>\n";
				}
			}
		}
	}

	return $arrayofgroupby;
}
