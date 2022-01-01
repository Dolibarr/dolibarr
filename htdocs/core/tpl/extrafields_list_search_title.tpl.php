<?php

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) $extrafieldsobjectkey = $object->table_element;
if (!isset($disablesortlink)) $disablesortlink = 0;

// Loop to show all columns of extrafields for the title line
if (!empty($extrafieldsobjectkey))	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
{
	if (is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label']))
	{
        if (empty($extrafieldsobjectprefix)) $extrafieldsobjectprefix = 'ef.';

		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val)
		{
			if (!empty($arrayfields[$extrafieldsobjectprefix.$key]['checked']))
			{
				$align = $extrafields->getAlignFlag($key);
				$sortonfield = $extrafieldsobjectprefix.$key;
				if (!empty($extrafields->attributes[$extrafieldsobjectkey]['computed'][$key])) $sortonfield = '';
				if ($extrafields->attributes[$extrafieldsobjectkey]['type'][$key] == 'separate') {
					print '<th class="liste_titre thseparator"></th>';
				}
				else
				{
					if (! empty($extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key]) && is_object($langs)) {
						$langs->load($extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key]);
					}

					$tooltip = empty($extrafields->attributes[$extrafieldsobjectkey]['help'][$key]) ? '' : $extrafields->attributes[$extrafieldsobjectkey]['help'][$key];

					print getTitleFieldOfList($extrafields->attributes[$extrafieldsobjectkey]['label'][$key], 0, $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align ? 'align="'.$align.'" data-titlekey="'.$key.'"' : 'data-titlekey="'.$key.'"'), $sortfield, $sortorder, '', $disablesortlink, $tooltip)."\n";
				}
			}
		}
	}
}
