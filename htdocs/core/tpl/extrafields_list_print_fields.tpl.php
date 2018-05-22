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
				print '>';
				$tmpkey='options_'.$key;
				if (in_array($extrafields->attributes[$extrafieldsobjectkey]['type'][$key], array('date', 'datetime', 'timestamp')))
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
