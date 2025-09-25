<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */

// This tpl file is included into the init part of pages, so before action.
// So no output must be done.

// TODO: Note, supposing $arrayfields is already set

/**
 * @var Conf	$conf
 *
 * @var string 	$extrafieldsobjectkey
 * @var string 	$extrafieldsobjectprefix
 * @var int		$extrafieldspositionoffset
 */

'
@phan-var-force array<string,array{label:string,checked?:string,position?:int,help?:string,enabled?:string}> $arrayfields
';

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (empty($extrafieldsobjectkey) && is_object($object)) {
	$extrafieldsobjectkey = $object->table_element;
}
if (empty($extrafieldspositionoffset)) {
	$extrafieldspositionoffset = 0;
}

// Loop to show all columns of extrafields from $obj, $extrafields and $db
if (!empty($extrafieldsobjectkey)) {	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
	if (isset($extrafields->attributes[$extrafieldsobjectkey]['label']) && is_array($extrafields->attributes[$extrafieldsobjectkey]['label']) && count($extrafields->attributes[$extrafieldsobjectkey]['label']) > 0) {
		if (empty($extrafieldsobjectprefix)) {
			$extrafieldsobjectprefix = 'ef.';
		}
		foreach ($extrafields->attributes[$extrafieldsobjectkey]['label'] as $key => $val) {
			$enabled = true;
			if (!empty($extrafields->attributes[$extrafieldsobjectkey]['enabled'][$key])) {
				// An enablement condition exist, it is evaluated.
				$enabled = dol_eval((string) $extrafields->attributes[$extrafieldsobjectkey]['enabled'][$key], 1);
			}
			if (!empty($extrafields->attributes[$extrafieldsobjectkey]['list'][$key]) && $enabled) {
				$arrayfields[$extrafieldsobjectprefix.$key] = array(
					'label'    => $extrafields->attributes[$extrafieldsobjectkey]['label'][$key],
					'type'     => $extrafields->attributes[$extrafieldsobjectkey]['type'][$key],
					'checked'  => (((int) dol_eval($extrafields->attributes[$extrafieldsobjectkey]['list'][$key], 1, 1, '1') <= 0) ? '0' : '1'),
					'position' => $extrafieldspositionoffset + $extrafields->attributes[$extrafieldsobjectkey]['pos'][$key],
					'perms'    => ((dol_eval($extrafields->attributes[$extrafieldsobjectkey]['perms'][$key], 1, 1, '1') <= 0) ? '0' : '1'),
					'enabled'  => (string) (int) (abs((int) dol_eval($extrafields->attributes[$extrafieldsobjectkey]['list'][$key], 1)) != 3),
					'langfile' => $extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key],
					'help'     => $extrafields->attributes[$extrafieldsobjectkey]['help'][$key],
				);
			}
		}
	}
}
