<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) $extrafieldsobjectkey=$object->table_element;

// Loop to show all columns of extrafields from $obj, $extrafields and $db
if (! empty($extrafieldsobjectkey))	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
{
	if (is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label']))
	{
		foreach($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val)
		{
			if (! empty($arrayfields["ef.".$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key, $extrafieldsobjectkey);
				print '<td';
                if ($align) print ' align="'.$align.'"';
                print ' data-key="'.$key.'"';
                print '>';
                $tmpkey='options_'.$key;
				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('date', 'datetime', 'timestamp')) && !is_numeric($obj->$tmpkey))
				{
					$datenotinstring = $obj->$tmpkey;
					if (! is_numeric($obj->$tmpkey))	// For backward compatibility
					{
						$datenotinstring = $db->jdate($datenotinstring);
					}
					$value = $datenotinstring;
				}
				else
				{
					$value = $obj->$tmpkey;
				}

				print $extrafields->showOutputField($key, $value, '', $extrafieldsobjectkey);
				print '</td>';
				if (! $i) $totalarray['nbfield']++;

                if ($extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key]) {
                    if (! $i) {
                        // we keep position for the first line
                        $totalarray['totalizable'][$key]['pos'] = $totalarray['nbfield'];
                    }
                    $totalarray['totalizable'][$key]['total'] += $obj->$tmpkey;
                }
				if (! empty($val['isameasure']))
				{
					if (! $i) $totalarray['pos'][$totalarray['nbfield']]='ef.'.$tmpkey;
					$totalarray['val']['ef.'.$tmpkey] += $obj->$tmpkey;
				}
			}
		}
	}
}
