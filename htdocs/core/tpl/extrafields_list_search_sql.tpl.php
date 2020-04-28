<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) $extrafieldsobjectkey=$object->table_element;

// Loop to complete the sql search criterias from extrafields
if (! empty($extrafieldsobjectkey) && ! empty($search_array_options) && is_array($search_array_options))	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
{
    if (empty($extrafieldsobjectprefix)) $extrafieldsobjectprefix = 'ef.';
    if (empty($search_options_pattern)) $search_options_pattern='search_options_';

    foreach ($search_array_options as $key => $val)
	{
		$crit=$val;
		$tmpkey=preg_replace('/'.$search_options_pattern.'/', '', $key);
		$typ=$extrafields->attributes[$extrafieldsobjectkey]['type'][$tmpkey];

		if ($crit != '' && in_array($typ, array('date', 'datetime', 'timestamp')))
		{
			$sql .= " AND ".$extrafieldsobjectprefix.$tmpkey." = '".$db->idate($crit)."'";
		}
		elseif (in_array($typ, array('boolean')))
		{
			if ($crit !== '-1' && $crit !== '') {
				$sql .= " AND (".$extrafieldsobjectprefix.$tmpkey." = '".$db->escape($crit)."'";
				if ($crit == '0') $sql.=" OR ".$extrafieldsobjectprefix.$tmpkey." IS NULL";
				$sql.= ")";
			}
		}
		elseif ($crit != '' && (! in_array($typ, array('select', 'sellist')) || $crit != '0') && (! in_array($typ, array('link')) || $crit != '-1'))
		{
			$mode_search=0;
			if (in_array($typ, array('int', 'double', 'real', 'price'))) $mode_search=1;						// Search on a numeric
			if (in_array($typ, array('sellist', 'link')) && $crit != '0' && $crit != '-1') $mode_search=2;	// Search on a foreign key int
			if (in_array($typ, array('chkbxlst', 'checkbox'))) $mode_search=4;	                            // Search on a multiselect field with sql type = text
			if (is_array($crit)) $crit = implode(' ', $crit); // natural_search() expects a string
			elseif ($typ === 'select' and is_string($crit) and strpos($crit, ' ') === false) {
				$sql .= ' AND (' . $extrafieldsobjectprefix.$tmpkey . ' = "' . $db->escape($crit) . '")';
				continue;
			}
			$sql .= natural_search($extrafieldsobjectprefix.$tmpkey, $crit, $mode_search);
		}
	}
}
