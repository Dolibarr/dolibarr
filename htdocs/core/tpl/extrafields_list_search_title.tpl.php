<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Loop to show all columns of extrafields for the title line
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']))
{
	foreach($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (! empty($arrayfields["ef.".$key]['checked']))
		{
			$align=$extrafields->getAlignFlag($key);
			$sortonfield = "ef.".$key;
			if (! empty($extrafields->attributes[$object->table_element]['computed'][$key])) $sortonfield='';
			if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') print '<th class="liste_titre thseparator"></th>';
			else print getTitleFieldOfList($langs->trans($extralabels[$key]), 0, $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align?'align="'.$align.'"':''), $sortfield, $sortorder)."\n";
		}
	}
}