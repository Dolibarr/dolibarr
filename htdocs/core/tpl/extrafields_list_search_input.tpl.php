<?php

print '<!-- extrafields_list_search_input.tpl.php -->'."\n";

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) {
	$extrafieldsobjectkey = $object->table_element;
}

// Loop to show all columns of extrafields for the search title line
if (!empty($extrafieldsobjectkey)) {	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (!empty($extrafields->attributes[$extrafieldsobjectkey]['label']) && is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label'])) {
		if (empty($extrafieldsobjectprefix)) {
			$extrafieldsobjectprefix = 'ef.';
		}
		if (empty($search_options_pattern)) {
			$search_options_pattern = 'search_options_';
		}

		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val) {
			if (!empty($arrayfields[$extrafieldsobjectprefix.$key]['checked'])) {
				$cssclass = $extrafields->getAlignFlag($key, $extrafieldsobjectkey);
				$typeofextrafield = $extrafields->attributes[$extrafieldsobjectkey]['type'][$key];

				print '<td class="liste_titre'.($cssclass ? ' '.$cssclass : '').'">';
				$tmpkey = preg_replace('/'.$search_options_pattern.'/', '', $key);
				if (in_array($typeofextrafield, array('varchar', 'mail', 'ip', 'url', 'int', 'double')) && empty($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key])) {
					$searchclass = '';
					if (in_array($typeofextrafield, array('varchar', 'mail', 'ip', 'url'))) {
						$searchclass = 'searchstring';
					}
					if (in_array($typeofextrafield, array('int', 'double'))) {
						$searchclass = 'searchnum';
					}
					print '<input class="flat'.($searchclass ? ' '.$searchclass : '').'" size="4" type="text" name="'.$search_options_pattern.$tmpkey.'" value="'.dol_escape_htmltag((empty($search_array_options[$search_options_pattern.$tmpkey]) ? '' : $search_array_options[$search_options_pattern.$tmpkey])).'">';
				} elseif (in_array($typeofextrafield, array('datetime', 'timestamp'))) {
					$morecss = '';
					echo $extrafields->showInputField($key, (empty($search_array_options[$search_options_pattern.$tmpkey]) ? '' : $search_array_options[$search_options_pattern.$tmpkey]), '', '', $search_options_pattern, $morecss, 0, $extrafieldsobjectkey, 1);
				} else {
					// for the type as 'checkbox', 'chkbxlst', 'sellist' we should use code instead of id (example: I declare a 'chkbxlst' to have a link with dictionnairy, I have to extend it with the 'code' instead 'rowid')
					$morecss = '';
					if (in_array($typeofextrafield, array('link', 'sellist', 'text', 'html'))) {
						$morecss = 'maxwidth200';
					}
					echo $extrafields->showInputField($key, (empty($search_array_options[$search_options_pattern.$tmpkey]) ? '' : $search_array_options[$search_options_pattern.$tmpkey]), '', '', $search_options_pattern, $morecss, 0, $extrafieldsobjectkey, 1);
				}
				print '</td>';
			}
		}
	}
}
