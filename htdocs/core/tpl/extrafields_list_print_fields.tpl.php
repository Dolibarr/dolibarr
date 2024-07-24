<?php

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) {
	$extrafieldsobjectkey = $object->table_element;
}

// Loop to show all columns of extrafields from $obj, $extrafields and $db
if (!empty($extrafieldsobjectkey) && !empty($extrafields->attributes[$extrafieldsobjectkey])) {	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (key_exists('label', $extrafields->attributes[$extrafieldsobjectkey]) && is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label'])) {
		if (empty($extrafieldsobjectprefix)) {
			$extrafieldsobjectprefix = 'ef.';
		}

		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val) {
			if (!empty($arrayfields[$extrafieldsobjectprefix.$key]['checked'])) {
				$cssclass = $extrafields->getAlignFlag($key, $extrafieldsobjectkey);

				$tmpkey = 'options_'.$key;

				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('date', 'datetime', 'timestamp')) && isset($obj->$tmpkey) && !is_numeric($obj->$tmpkey)) {
					$datenotinstring = $obj->$tmpkey;
					if (!is_numeric($obj->$tmpkey)) {	// For backward compatibility
						$datenotinstring = $db->jdate($datenotinstring);
					}
					$value = $datenotinstring;
				} elseif (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('int'))) {
					$value = (!empty($obj->$tmpkey) || $obj->$tmpkey === '0'  ? $obj->$tmpkey : '');
				} else {
					$value = (!empty($obj->$tmpkey) ? $obj->$tmpkey : '');
				}
				// If field is a computed field, we make computation to get value
				if ($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key]) {
					$objectoffield = $object; //For compatibily with the computed formula
					$value = dol_eval($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key], 1, 1, '2');
					if (is_numeric(price2num($value)) && $extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key]) {
						$obj->$tmpkey = price2num($value);
					}
					//var_dump($value);
				}

				$valuetoshow = $extrafields->showOutputField($key, $value, '', $extrafieldsobjectkey);
				$title = dol_string_nohtmltag($valuetoshow);

				print '<td'.($cssclass ? ' class="'.$cssclass.'"' : '');	// TODO Add 'css' and 'cssview' and 'csslist' for extrafields and use here 'csslist'
				print ' data-key="'.$extrafieldsobjectkey.'.'.$key.'"';
				print($title ? ' title="'.dol_escape_htmltag($title).'"' : '');
				print '>';
				print $valuetoshow;
				print '</td>';

				if (!$i) {
					if (empty($totalarray)) {
						$totalarray['nbfield'] = 0;
					}
					$totalarray['nbfield']++;
				}

				if ($extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key]) {
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
				if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
					if (!$i) {
						$totalarray['pos'][$totalarray['nbfield']] = $extrafieldsobjectprefix.$tmpkey;
					}
					if (!isset($totalarray['val'])) {
						$totalarray['val'] = array();
					}
					if (!isset($totalarray['val'][$extrafieldsobjectprefix.$tmpkey])) {
						$totalarray['val'][$extrafieldsobjectprefix.$tmpkey] = 0;
					}
					$totalarray['val'][$extrafieldsobjectprefix.$tmpkey] += $obj->$tmpkey;
				}
			}
		}
	}
}
