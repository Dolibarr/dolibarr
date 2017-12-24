<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Loop to show all columns of extrafields from $obj, $extrafields and $db
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
			}
			else
			{
				$value = $obj->$tmpkey;
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