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
<<<<<<< HEAD
		foreach($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val)
		{
			if (! empty($arrayfields["ef.".$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key, $extrafieldsobjectkey);
				print '<td';
				if ($align) print ' align="'.$align.'"';
				print '>';
				$tmpkey='options_'.$key;
=======
        if (empty($extrafieldsobjectprefix)) $extrafieldsobjectprefix = 'ef.';

        foreach($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val)
		{
			if (! empty($arrayfields[$extrafieldsobjectprefix.$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key, $extrafieldsobjectkey);
				print '<td';
                if ($align) print ' class="'.$align.'"';
                print ' data-key="'.$key.'"';
                print '>';
                $tmpkey='options_'.$key;

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD

				print $extrafields->showOutputField($key, $value, '', $extrafieldsobjectkey);
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
				if (! empty($val['isameasure']))
				{
					if (! $i) $totalarray['pos'][$totalarray['nbfield']]='ef.'.$tmpkey;
					$totalarray['val']['ef.'.$tmpkey] += $obj->$tmpkey;
=======
				// If field is a computed field, we make computation to get value
				if ($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key])
				{
					//global $obj, $object;
					//var_dump($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key]);
					//var_dump($obj);
					//var_dump($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key]);
					$value = dol_eval($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key], 1);
					//var_dump($value);
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
					if (! $i) $totalarray['pos'][$totalarray['nbfield']]=$extrafieldsobjectprefix.$tmpkey;
					$totalarray['val'][$extrafieldsobjectprefix.$tmpkey] += $obj->$tmpkey;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				}
			}
		}
	}
}
