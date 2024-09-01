<?php

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
					// The key may be in $obj->array_options if not in $obj
					$value = (isset($obj->$tmpkey) ? $obj->$tmpkey :
						(isset($obj->array_options[$tmpkey]) ? $obj->array_options[$tmpkey] : '') );
				}
				// If field is a computed field, we make computation to get value
				if ($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key]) {
					$objectoffield = $object; //For compatibility with the computed formula
					$value = dol_eval((string) $extrafields->attributes[$extrafieldsobjectkey]['computed'][$key], 1, 1, '2');
					if (is_numeric(price2num($value)) && $extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key]) {
						$obj->$tmpkey = price2num($value);
					}
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
