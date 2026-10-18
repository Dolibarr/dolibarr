<?php

/* Copyright (C) 2024       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 * @param	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string}>		$arrayofmesures	Array of measures already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string}>		Array of measures
 */
function fillArrayOfMeasures($object, $tablealias, $labelofobject, &$arrayofmesures, $level = 0, &$count = 0, &$tablepath = '')
{
	global $langs, $extrafields, $db;

	if (empty($object)) {	// Protection against bad use of method
		return array();
	}
	if ($level >= 3) {	// Limit scan on 2 levels max
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
		// Discard the fields declared with attribute 'bi' set to 0 (field hidden on BI tool)
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (!empty($val['isameasure']) && (!isset($val['enabled']) || (int) dol_eval((string) $val['enabled'], 1, 1, '1'))) {
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
			$arrayofmesures[$tablealias.'.'.$key.'-stddevpop'] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("StandardDeviationPop").')</span>',
				'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
				'position' => ($position + ($count * 100000)).'.5',
				'table' => $object->table_element,
				'tablefromt' => $tablepath
			);
		}
	}
	// Add extrafields to Measures
	if (!empty($object->isextrafieldmanaged) && isset($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key]) && (!isset($extrafields->attributes[$object->table_element]['enabled'][$key]) || (int) dol_eval((string) $extrafields->attributes[$object->table_element]['enabled'][$key], 1, 1, '1'))) {
				if (isset($extrafields->attributes[$object->table_element]['pos'][$key])) {
					$position = $extrafields->attributes[$object->table_element]['pos'][$key];
				} else {
					$position = 0;
				}
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
	// Add fields for parent objects
	foreach ($object->fields as $key => $val) {
		// Discard the linked objects of fields declared with attribute 'bi' set to 0 (field hidden on BI tool),
		// so the sub element (the linked table) and all its sub fields are not added into the list
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && !empty($tmptype[1]) && !empty($tmptype[2])) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					//var_dump($key); var_dump($tmpobject->element); var_dump($val['label']); var_dump($tmptype); var_dump('t-'.$key);
					$count++;
					$tmptablepath = $tablepath;	// Use a copy so the path of a sibling link is not appended to the path of the next one
					$arrayofmesures = fillArrayOfMeasures($tmpobject, $tablealias.'__'.$key, ($level >= 1 ? $labelofobject.' - ' : '').$langs->trans($val['label']), $arrayofmesures, $level + 1, $count, $tmptablepath);
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
 * @param	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string}>		$arrayofxaxis	Array of xaxis already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string}>		Array of xaxis
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
		// Discard the fields declared with attribute 'bi' set to 0 (field hidden on BI tool)
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (empty($val['measure'])) {
			if (in_array($key, array(
				'id', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
				'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) {
				continue;
			}
			if (isset($val['enabled']) && ! (int) dol_eval((string) $val['enabled'], 1, 1, '1')) {
				continue;
			}
			if (isset($val['visible']) && ! (int) dol_eval((string) $val['visible'], 1, 1, '1')) {
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
		// Discard the linked objects of fields declared with attribute 'bi' set to 0 (field hidden on BI tool),
		// so the sub element (the linked table) and all its sub fields are not added into the list
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && $tmptype[1] && $tmptype[2]) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					//var_dump($key); var_dump($tmpobject->element); var_dump($val['label']); var_dump($tmptype); var_dump('t-'.$key);
					$count++;
					$tmptablepath = $tablepath;	// Use a copy so the path of a sibling link is not appended to the path of the next one
					$arrayofxaxis = fillArrayOfXAxis($tmpobject, $tablealias.'__'.$key, ($level >= 1 ? $labelofobject.' - ' : '').$langs->trans($val['label']), $arrayofxaxis, $level + 1, $count, $tmptablepath);
				} else {
					print 'For property '.$object->element.'->'.$key.', type="'.$val['type'].'": Failed to find class '.$newobject." in file ".$tmptype[2]."<br>\n";
				}
			}
		}
	}

	return $arrayofxaxis;
}


/**
 * Fill arrayofgroupby for an object
 *
 * @param 	CommonObject	$object			Any object
 * @param	string		$tablealias		Alias of table
 * @param	string		$labelofobject	Label of object
 * @param	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string}>	$arrayofgroupby	Array of groupby already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string}>		Array of groupby
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
		// Discard the fields declared with attribute 'bi' set to 0 (field hidden on BI tool)
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (empty($val['isameasure'])) {
			if (in_array($key, array(
				'id', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
				'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) {
				continue;
			}
			if (isset($val['enabled']) && ! (int) dol_eval((string) $val['enabled'], 1, 1, '1')) {
				continue;
			}
			if (isset($val['visible']) && ! (int) dol_eval((string) $val['visible'], 1, 1, '1')) {
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
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.')</span>',  // @phan-suppress-current-line PhanUndeclaredProperty
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[$tablealias.'.'.$key.'-month'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.'-'.$MM.')</span>',  // @phan-suppress-current-line PhanUndeclaredProperty
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[$tablealias.'.'.$key.'-day'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']).' <span class="opacitymedium">('.$YYYY.'-'.$MM.'-'.$DD.')</span>',  // @phan-suppress-current-line PhanUndeclaredProperty
					'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			} else {
				$position = (empty($val['position']) ? 0 : intval($val['position']));
				$arrayofgroupby[$tablealias.'.'.$key] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']),  // @phan-suppress-current-line PhanUndeclaredProperty
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
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.')</span>',  // @phan-suppress-current-line PhanUndeclaredProperty
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.1',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-month'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.'-'.$MM.')</span>',  // @phan-suppress-current-line PhanUndeclaredProperty
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.2',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key.'-day'] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val).' <span class="opacitymedium">('.$YYYY.'-'.$MM.'-'.$DD.')</span>',  // @phan-suppress-current-line PhanUndeclaredProperty
					'labelnohtml' => $labelofobject.': '.$langs->trans($val),
					'position' => ($position + ($count * 100000)).'.3',
					'table' => $object->table_element,
					'tablefromt' => $tablepath
				);
			} else {
				$arrayofgroupby[preg_replace('/^t/', 'te', $tablealias).'.'.$key] = array(
					'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val),  // @phan-suppress-current-line PhanUndeclaredProperty
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
		// Discard the linked objects of fields declared with attribute 'bi' set to 0 (field hidden on BI tool),
		// so the sub element (the linked table) and all its sub fields are not added into the list
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && $tmptype[1] && $tmptype[2]) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					//var_dump($key); var_dump($tmpobject->element); var_dump($val['label']); var_dump($tmptype); var_dump('t-'.$key);
					$count++;
					$tmptablepath = $tablepath;	// Use a copy so the path of a sibling link is not appended to the path of the next one
					$arrayofgroupby = fillArrayOfGroupBy($tmpobject, $tablealias.'__'.$key, ($level >= 1 ? $labelofobject.' - ' : '').$langs->trans($val['label']), $arrayofgroupby, $level + 1, $count, $tmptablepath);
				} else {
					print 'For property '.$object->element.'->'.$key.', type="'.$val['type'].'": Failed to find class '.$newobject." in file ".$tmptype[2]."<br>\n";
				}
			}
		}
	}

	return $arrayofgroupby;
}


/**
 * Fill array of possible filter fields for an object
 *
 * @param 	CommonObject	$object			Any object
 * @param	string		$tablealias		Alias of table ('t' for example)
 * @param	string		$labelofobject	Label of object
 * @param	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string,type:string,bi?:int<0,1>}>	$arrayoffields	Array of fields already filled
 * @param	int			$level 			Level
 * @param	int			$count			Count
 * @param	string		$tablepath		Path of all tables ('t' or 't,contract' or 't,contract,societe'...)
 * @return	array<string,array{label:string,labelnohtml:string,position:int,table:string,tablefromt:string,type:string,bi?:int<0,1>}>	Array of fields
 */
function fillArrayOfFilterFields($object, $tablealias, $labelofobject, &$arrayoffields, $level = 0, &$count = 0, &$tablepath = '')
{
	global $langs, $extrafields, $db;

	$MAXLEVEL = 2;

	if (empty($object)) {	// Protection against bad use of method
		return array();
	}
	if ($level > $MAXLEVEL) {	// Limit scan on 2 levels max
		return $arrayoffields;
	}

	if (empty($tablepath)) {
		$tablepath = $object->table_element.'='.$tablealias;
	} else {
		$tablepath .= ','.$object->table_element.'='.$tablealias;
	}

	// Note: here $tablealias can be 't' or 't__fk_contract' or 't_fk_contract_fk_soc'

	// Add main fields of object
	foreach ($object->fields as $key => $val) {
		if (empty($val['measure'])) {
			// Exclude some fields
			if (in_array($key, array(
				'id', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
				'parent', 'pass', 'pass_crypted', 'pass_temp', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) {
				continue;
			}
			if (isset($val['enabled']) && ! (int) dol_eval((string) $val['enabled'], 1, 1, '1')) {
				continue;
			}
			if (isset($val['visible']) && ! (int) dol_eval((string) $val['visible'], 1, 1, '1')) {
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

			$position = (empty($val['position']) ? 0 : intval($val['position']));
			$arrayoffields[$tablealias.'.'.$key] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val['label']),  // @phan-suppress-current-line PhanUndeclaredProperty
				'labelnohtml' => $labelofobject.': '.$langs->trans($val['label']),
				'position' => ($position + ($count * 100000)),
				'table' => $object->table_element,
				'tablefromt' => $tablepath,
				'type' => $val['type']
			);
			if (!empty($val['arrayofkeyval'])) {
				$arrayoffields[$tablealias.'.'.$key]['arrayofkeyval'] = $val['arrayofkeyval'];
			}
			if ((!isset($val['isamesaure']) || $val['isamesaure'] != 1) && (!isset($val['notnull']) || $val['notnull'] != '1')) {
				$arrayoffields[$tablealias.'.'.$key]['maybenull'] = 1;
			}
			// Keep the attribute 'bi' of the field so the search component can discard the fields hidden on BI tool
			if (isset($val['bi'])) {
				$arrayoffields[$tablealias.'.'.$key]['bi'] = (int) $val['bi'];
			}
		}
	}

	// Add extrafields to fields
	if (!empty($object->isextrafieldmanaged) && isset($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') {
				continue;
			}
			if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) {
				continue;
			}

			$arrayoffields[preg_replace('/^t/', 'te', $tablealias).'.'.$key] = array(
				'label' => img_picto('', (empty($object->picto) ? 'generic' : $object->picto), 'class="pictofixedwidth"').' '.$labelofobject.': '.$langs->trans($val),  // @phan-suppress-current-line PhanUndeclaredProperty
				'labelnohtml' => $labelofobject.': '.$langs->trans($val),
				'position' => 1000 + (int) $extrafields->attributes[$object->table_element]['pos'][$key] + ($count * 100000),
				'table' => $object->table_element,
				'tablefromt' => $tablepath,
				'type' => $extrafields->attributes[$object->table_element]['type'][$key],
			);
		}
	}

	// Add fields for parent objects
	foreach ($object->fields as $key => $val) {
		// Discard the linked objects of fields declared with attribute 'bi' set to 0 (field hidden on BI tool),
		// so the sub element (the linked table) and all its sub fields are not added into the list
		if (isset($val['bi']) && (int) $val['bi'] == 0) {
			continue;
		}
		if (preg_match('/^[^:]+:[^:]+:/', $val['type'])) {
			$tmptype = explode(':', $val['type'], 4);
			if ($tmptype[0] == 'integer' && $tmptype[1] && $tmptype[2]) {
				$newobject = $tmptype[1];
				dol_include_once($tmptype[2]);
				if (class_exists($newobject)) {
					$tmpobject = new $newobject($db);
					$count++;
					if (!empty($val['nodepth'])) {
						$tmptablepath = $tablepath;	// Use a copy so the path of a sibling link is not appended to the path of the next one
						$arrayoffields = fillArrayOfFilterFields($tmpobject, $tablealias.'__'.$key, ($level >= 1 ? $labelofobject.' - ' : '').$langs->trans($val['label']), $arrayoffields, $MAXLEVEL, $count, $tmptablepath);
					} else {
						$tmptablepath = $tablepath;	// Use a copy so the path of a sibling link is not appended to the path of the next one
						$arrayoffields = fillArrayOfFilterFields($tmpobject, $tablealias.'__'.$key, ($level >= 1 ? $labelofobject.' - ' : '').$langs->trans($val['label']), $arrayoffields, $level + 1, $count, $tmptablepath);
					}
				} else {
					print 'For property '.$object->element.'->'.$key.', type="'.$val['type'].'": Failed to find class '.$newobject." in file ".$tmptype[2]."<br>\n";
				}
			}
		}
	}

	return $arrayoffields;
}


/**
 * Return HTML of the component to select measures. Fields are listed first and, for fields with variants
 * (sum, average, min, max, standard deviation), variants are shown in an expandable sub list.
 * Behaviour is managed by core/js/customreports.js. Selection is posted with a hidden multiple select.
 *
 * @param	string		$htmlname			Name of the HTML field (posted as $htmlname[])
 * @param	array<string,array{label:string,labelnohtml:string,position:int|string,table:string,tablefromt:string}>	$arrayofmesures		Array of measures (from fillArrayOfMeasures())
 * @param	string[]	$selected			Array of selected keys
 * @param	string		$placeholder		Placeholder
 * @return	string							HTML string of component
 */
function customReportsSelectMeasures($htmlname, $arrayofmesures, $selected, $placeholder = '')
{
	global $conf, $langs;

	$variantlabels = array(
		'sum' => $langs->trans("Sum"),
		'average' => $langs->trans("Average"),
		'min' => $langs->trans("Minimum"),
		'max' => $langs->trans("Maximum"),
		'stddevpop' => $langs->trans("StandardDeviationPop"),
	);

	// Group measures by field. Keys of variants are 'alias.field-variant'.
	$groups = array();
	foreach ($arrayofmesures as $key => $val) {
		$reg = array();
		if (preg_match('/^(.+)-(sum|average|min|max|stddevpop)$/', $key, $reg)) {
			$groupkey = $reg[1];
			$variant = $reg[2];
		} else {
			$groupkey = $key;
			$variant = '';
		}
		if (!isset($groups[$groupkey])) {
			// The label is the picto of the object followed by the labelnohtml (and the variant suffix), so we extract the picto
			$picto = '';
			$pos = strpos($val['label'], $val['labelnohtml']);
			if ($pos !== false) {
				$picto = substr($val['label'], 0, $pos);
			}
			$groups[$groupkey] = array(
				'picto' => $picto,
				'labelnohtml' => $val['labelnohtml'],
				'variants' => array()
			);
		}
		$groups[$groupkey]['variants'][$key] = ($variant ? $variantlabels[$variant] : '');
	}

	$out = '<div class="customreportsmeasures divadvancedsearchfieldcompinput inline-block minwidth300 maxwidth500" id="'.dol_escape_htmltag($htmlname).'_component" data-htmlname="'.dol_escape_htmltag($htmlname).'">';

	// Hidden select used to post values
	$out .= '<select id="'.dol_escape_htmltag($htmlname).'" name="'.dol_escape_htmltag($htmlname).'[]" multiple class="hideobject">';
	foreach ($arrayofmesures as $key => $val) {
		$out .= '<option value="'.dol_escape_htmltag($key).'"'.(in_array($key, $selected) ? ' selected' : '').'>'.dol_escape_htmltag($val['labelnohtml']).'</option>';
	}
	$out .= '</select>';

	// Visible area with tags of selected measures
	$out .= '<div class="customreportsmeasures-control noborderbottom search_component_params inline-block" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">';
	$out .= '<span class="customreportsmeasures-tags"></span>';
	$out .= '<span class="customreportsmeasures-placeholder opacitymedium">'.dol_escape_htmltag($placeholder).'</span>';
	$out .= '<span class="fas fa-caret-down customreportsmeasures-caret"></span>';
	$out .= '</div>';

	// Dropdown with list of fields then variants
	$out .= '<div class="customreportsmeasures-dropdown hidden">';
	$out .= '<div class="liinputsearch"><input type="text" class="customreportsmeasures-search noborderfocus" placeholder="'.dol_escape_htmltag($langs->trans("Search")).'" autocomplete="off"></div>';
	$out .= '<ul class="customreportsmeasures-list" role="listbox">';
	foreach ($groups as $groupkey => $group) {
		$hasvariants = (count($group['variants']) > 1 || key($group['variants']) != $groupkey);
		$out .= '<li class="customreportsmeasures-field'.($hasvariants ? ' customreportsmeasures-hasvariants' : '').'" data-search="'.dol_escape_htmltag(dol_strtolower($group['labelnohtml'])).'">';
		if ($hasvariants) {
			$out .= '<div class="customreportsmeasures-fieldlabel">';
			$out .= '<span class="customreportsmeasures-text">'.$group['picto'].dol_escape_htmltag($group['labelnohtml']).'</span>';
			$out .= '<span class="customreportsmeasures-count badge badge-secondary hidden"></span>';
			$out .= '<span class="fas fa-caret-right customreportsmeasures-expand"></span>';
			$out .= '</div>';
			$out .= '<ul class="customreportsmeasures-variants hidden">';
			foreach ($group['variants'] as $key => $variantlabel) {
				$out .= '<li class="customreportsmeasures-item" role="option" data-value="'.dol_escape_htmltag($key).'" data-tag="'.dol_escape_htmltag($group['labelnohtml'].' ('.$variantlabel.')').'">';
				$out .= '<span class="customreportsmeasures-text">'.dol_escape_htmltag($variantlabel).'</span>';
				$out .= '<span class="fas fa-check customreportsmeasures-check"></span>';
				$out .= '</li>';
			}
			$out .= '</ul>';
		} else {
			$out .= '<div class="customreportsmeasures-fieldlabel customreportsmeasures-item" role="option" data-value="'.dol_escape_htmltag($groupkey).'" data-tag="'.dol_escape_htmltag($group['labelnohtml']).'">';
			$out .= '<span class="customreportsmeasures-text">'.$group['picto'].dol_escape_htmltag($group['labelnohtml']).'</span>';
			$out .= '<span class="fas fa-check customreportsmeasures-check"></span>';
			$out .= '</div>';
		}
		$out .= '</li>';
	}
	$out .= '</ul>';
	$out .= '</div>';

	$out .= '</div>';

	if (empty($conf->cache['customreportsjsloaded'])) {
		$out .= '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/core/js/customreports.js"></script>';
		$conf->cache['customreportsjsloaded'] = 1;
	}
	$out .= '<script nonce="'.getNonce().'">jQuery(document).ready(function() { initCustomReportsMeasures(\''.dol_escape_js($htmlname).'_component\'); });</script>';

	return $out;
}


/**
 * Return HTML of a combo to select one field (X axis, group by...). Same look and principle than the combo of fields of the filter
 * component: the main element is open with its fields, the sub-elements are listed after its fields and are collapsed.
 * Behaviour is managed by core/js/customreports.js. Selection is posted with a hidden input.
 *
 * @param	string		$htmlname			Name of the HTML field
 * @param	array<string,array{label:string,labelnohtml:string,position:int|string,table:string,tablefromt:string}>	$arrayoffields		Array of fields (from fillArrayOfXAxis() or fillArrayOfGroupBy())
 * @param	string[]	$selected			Array of selected keys (only first one is used)
 * @param	string		$placeholder		Placeholder
 * @param	int<0,1>	$allowclear			1 to show a button to clear the selection
 * @return	string							HTML string of component
 */
function customReportsSelectFieldCombo($htmlname, $arrayoffields, $selected, $placeholder = '', $allowclear = 0)
{
	global $conf, $langs;

	$selectedkey = (is_array($selected) && count($selected)) ? (string) reset($selected) : '';
	$selectedlabel = '';
	$selectedtitle = '';	// Full label of the selected field, shown as a tooltip because the label may be truncated

	// Group fields by element. The element is the part of the key before the last dot ('t.ref' -> 't'), extrafields 'te...' are mapped onto 't...'.
	$elements = array();
	foreach ($arrayoffields as $key => $val) {
		$elementkey = preg_replace('/^te(__|$)/', 't$1', preg_replace('/\.[^.]*$/', '', $key));
		// The labelnohtml is 'LabelOfElement: LabelOfField' and the label is 'picto LabelOfElement: LabelOfField <span>(variant)</span>'
		$elementlabel = $elementkey;
		$reg = array();
		if (preg_match('/^(.+?): /', $val['labelnohtml'], $reg)) {
			$elementlabel = $reg[1];
		}
		$picto = '';
		$fieldlabel = $val['labelnohtml'];
		$pos = strpos($val['label'], $val['labelnohtml']);
		if ($pos !== false) {
			$picto = trim(substr($val['label'], 0, $pos));
			$fieldlabel = trim(dol_string_nohtmltag(substr($val['label'], $pos + ($elementlabel != $elementkey ? strlen($elementlabel) + 2 : 0))));
		}
		if (!isset($elements[$elementkey])) {
			$elements[$elementkey] = array('picto' => $picto, 'label' => $elementlabel, 'fields' => array());
		}
		$elements[$elementkey]['fields'][$key] = $fieldlabel;
		if ($key === $selectedkey) {
			$selectedlabel = $picto.' '.dol_escape_htmltag($elementlabel.': '.$fieldlabel);
			$selectedtitle = $elementlabel.': '.$fieldlabel;
		}
	}

	$out = '<div class="customreportscombo divadvancedsearchfieldcompinput" id="'.dol_escape_htmltag($htmlname).'_component">';
	$out .= '<input type="hidden" id="'.dol_escape_htmltag($htmlname).'" name="'.dol_escape_htmltag($htmlname).'" value="'.dol_escape_htmltag($selectedlabel ? $selectedkey : '').'">';
	$out .= '<button type="button" class="customreportscombo-toggle noborderbottom search_component_params minwidth300">';
	$out .= '<span class="customreportscombo-label'.($selectedlabel ? '' : ' opacitymedium').'"'.($selectedtitle ? ' title="'.dolPrintHTMLForAttribute($selectedtitle).'"' : '').' data-placeholder="'.dol_escape_htmltag($placeholder).'">'.($selectedlabel ? $selectedlabel : dol_escape_htmltag($placeholder)).'</span>';
	if ($allowclear) {
		$out .= '<span class="fas fa-times customreportscombo-clear'.($selectedlabel ? '' : ' hideobject').'" title="'.dol_escape_htmltag($langs->trans('Reset')).'"></span>';
	}
	$out .= '<span class="fas fa-caret-down customreportscombo-caret"></span>';
	$out .= '</button>';
	$out .= '<div class="customreportscombo-panel" style="display:none">';
	$out .= '<div class="liinputsearch"><input type="text" class="customreportscombo-search noborderfocus" placeholder="'.dol_escape_htmltag($langs->trans('Search')).'" autocomplete="off"></div>';
	$out .= '<div class="customreportscombo-list">';

	// Recursive rendering of an element: its fields first, then its sub-elements (a direct sub-element of 't' is 't__xxx' without other '__' after)
	/**
	 * @param	string		$elementkey		Key of the element to render
	 * @return	string						HTML of the element and of its sub-elements
	 */
	$renderElement = function (string $elementkey) use ($elements, $selectedkey, &$renderElement): string {
		$element = $elements[$elementkey];
		$out = '<div class="customreportscombo-element" data-element="'.dol_escape_htmltag($elementkey).'">';
		$out .= '<div class="customreportscombo-elementheader" tabindex="0"><span class="customreportscombo-elementlabel">'.$element['picto'].' '.dol_escape_htmltag($element['label']).'</span><span class="fas fa-caret-right customreportscombo-elementcaret"></span></div>';
		$out .= '<div class="customreportscombo-fields" style="display:none">';
		foreach ($element['fields'] as $fieldkey => $fieldlabel) {
			$out .= '<div class="customreportscombo-field'.($fieldkey === $selectedkey ? ' selected' : '').'" tabindex="0" data-field="'.dol_escape_htmltag($fieldkey).'">'.dol_escape_htmltag($fieldlabel).'</div>';
		}
		$out .= '</div>';
		$children = '';
		foreach (array_keys($elements) as $childkey) {
			if (strpos($childkey, $elementkey.'__') === 0 && strpos(substr($childkey, strlen($elementkey) + 2), '__') === false) {
				$children .= $renderElement($childkey);
			}
		}
		if ($children) {
			$out .= '<div class="customreportscombo-children" style="display:none">'.$children.'</div>';
		}
		$out .= '</div>';
		return $out;
	};
	// At root level, we render only the elements without parent element (or with a parent not found)
	foreach (array_keys($elements) as $elementkey) {
		$parentkey = (strpos($elementkey, '__') !== false) ? substr($elementkey, 0, strrpos($elementkey, '__')) : '';
		if ($parentkey == '' || !isset($elements[$parentkey])) {
			$out .= $renderElement($elementkey);
		}
	}

	$out .= '</div>';
	$out .= '</div>';
	$out .= '</div>';

	if (empty($conf->cache['customreportsjsloaded'])) {
		$out .= '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/core/js/customreports.js"></script>';
		$conf->cache['customreportsjsloaded'] = 1;
	}
	$out .= '<script nonce="'.getNonce().'">jQuery(document).ready(function() { initCustomReportsCombo(\''.dol_escape_js($htmlname).'_component\'); });</script>';

	return $out;
}


/**
 * Return HTML of the combo to select the X axis (see customReportsSelectFieldCombo())
 *
 * @param	string		$htmlname			Name of the HTML field
 * @param	array<string,array{label:string,labelnohtml:string,position:int|string,table:string,tablefromt:string}>	$arrayofxaxis		Array of X axis (from fillArrayOfXAxis())
 * @param	string[]	$selected			Array of selected keys (only first one is used)
 * @param	string		$placeholder		Placeholder
 * @return	string							HTML string of component
 */
function customReportsSelectXAxis($htmlname, $arrayofxaxis, $selected, $placeholder = '')
{
	return customReportsSelectFieldCombo($htmlname, $arrayofxaxis, $selected, $placeholder, 0);
}


/**
 * Return HTML of the combo to select the group by field (see customReportsSelectFieldCombo()). Selection can be cleared.
 *
 * @param	string		$htmlname			Name of the HTML field
 * @param	array<string,array{label:string,labelnohtml:string,position:int|string,table:string,tablefromt:string}>	$arrayofgroupby		Array of group by (from fillArrayOfGroupBy())
 * @param	string[]	$selected			Array of selected keys (only first one is used)
 * @param	string		$placeholder		Placeholder
 * @return	string							HTML string of component
 */
function customReportsSelectGroupBy($htmlname, $arrayofgroupby, $selected, $placeholder = '')
{
	return customReportsSelectFieldCombo($htmlname, $arrayofgroupby, $selected, $placeholder, 1);
}
