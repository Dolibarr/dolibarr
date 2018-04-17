<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) $extrafieldsobjectkey=$object->table_element;

// Loop to show all columns of extrafields for the search title line
if (is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label']))
{
	foreach($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val)
	{
		if (! empty($arrayfields["ef.".$key]['checked'])) {
			$align=$extrafields->getAlignFlag($key);
			$typeofextrafield=$extrafields->attributes[$extrafieldsobjectkey]['type'][$key];
			print '<td class="liste_titre'.($align?' '.$align:'').'">';
			if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')) && empty($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key]))
			{
				$crit=$val;
				$tmpkey=preg_replace('/search_options_/','',$key);
				$searchclass='';
				if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
				if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
				print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
			}
			elseif (! in_array($typeofextrafield, array('datetime','timestamp')))
			{
				// for the type as 'checkbox', 'chkbxlst', 'sellist' we should use code instead of id (example: I declare a 'chkbxlst' to have a link with dictionnairy, I have to extend it with the 'code' instead 'rowid')
				$morecss='';
				if ($typeofextrafield == 'sellist') $morecss='maxwidth200';
				echo $extrafields->showInputField($key, $search_array_options['search_options_'.$key], '', '', 'search_', $morecss);
			}
			elseif (in_array($typeofextrafield, array('datetime','timestamp')))
			{
				// TODO
				// Use showInputField in a particular manner to have input with a comparison operator, not input for a specific value date-hour-minutes
			}
			print '</td>';
		}
	}
}