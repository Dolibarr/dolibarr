<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Loop to show all columns of extrafields from $obj, $extrafields and $db
if (! empty($extrafieldsobjectkey))	// New method: $extrafieldsobject can be 'societe', 'socpeople', ...
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
				print '>';
				$tmpkey='options_'.$key;
				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('date', 'datetime', 'timestamp')) && !is_numeric($obj->$tmpkey))
				{
					$value = $db->jdate($obj->$tmpkey);
				}
				else
				{
					$value = $obj->$tmpkey;
				}

				print $extrafields->showOutputField($key, $value, '', $extrafieldsobjectkey);
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
				if (! empty($val['isameasure']))
				{
					if (! $i) $totalarray['pos'][$totalarray['nbfield']]='ef.'.$tmpkey;
					$totalarray['val']['ef.'.$tmpkey] += $obj->$tmpkey;
				}
			}
		}
	}
}
else								// Old method
{
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
		foreach($extrafields->attribute_label as $key => $val)
		{
			if (! empty($arrayfields["ef.".$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key);
				print '<td';
				if ($align) print ' align="'.$align.'"';
				print '>';
				$tmpkey='options_'.$key;
				if (in_array($extrafields->attribute_type[$key], array('date', 'datetime', 'timestamp')))
				{
					$value = $db->jdate($obj->$tmpkey);
                                        if (is_array($obj->array_options) && isset($obj->array_options[$tmpkey])){
                                            $value = $db->jdate($obj->array_options[$tmpkey]);
                                        }
				}
				else
				{
					$value = $obj->$tmpkey;
                                        if (is_array($obj->array_options) && isset($obj->array_options[$tmpkey])){
                                            $value = $obj->array_options[$tmpkey];
                                        }
				}
				print $extrafields->showOutputField($key, $value, '');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
				if (! empty($val['isameasure']))
				{
					if (! $i) $totalarray['pos'][$totalarray['nbfield']]='ef.'.$tmpkey;
					$totalarray['val']['ef.'.$tmpkey] += $obj->$tmpkey;
				}
			}
		}
	}
}
