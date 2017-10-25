<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 */
?>
<!-- BEGIN PHP TEMPLATE commonfields_edit.tpl.php -->
<?php

foreach($object->fields as $key => $val)
{
	if (abs($val['visible']) != 1) continue;	// Discard such field from form
	if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! $val['enabled']) continue;	// We don't want this field

	print '<tr><td';
	print ' class="titlefieldcreate';
	if ($val['notnull'] > 0) print ' fieldrequired';
	if ($val['type'] == 'text') print ' tdtop';
	print '"';
	print '>'.$langs->trans($val['label']).'</td>';
	print '<td>';
	$defaultcss='minwidth100';
	if ($val['type'] == 'text')
	{
		print '<textarea class="flat quatrevingtpercent" rows="'.ROWS_4.'" name="'.$key.'">';
		print GETPOST($key,'none')?GETPOST($key,'none'):$object->$key;
		print '</textarea>';
	}
	elseif (is_array($val['arrayofkeyval']))
	{
		print $form->selectarray($key, $val['arrayofkeyval'], GETPOST($key, 'int')!=''?GETPOST($key, 'int'):$object->$key);
	}
	else
	{
		$cssforinput = empty($val['css'])?$defaultcss:$val['css'];
		print '<input class="flat'.($cssforinput?' '.$cssforinput:'').'" type="text" name="'.$key.'" value="'.(GETPOST($key,'alpha')?GETPOST($key,'alpha'):$object->$key).'">';
	}
	print '</td>';
	print '</tr>';
}

?>
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->