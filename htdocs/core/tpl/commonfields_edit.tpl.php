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
	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1) continue;

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! $val['enabled']) continue;	// We don't want this field

	print '<tr><td';
	print ' class="titlefieldcreate';
	if ($val['notnull'] > 0) print ' fieldrequired';
	if ($val['type'] == 'text') print ' tdtop';
	print '"';
	print '>'.$langs->trans($val['label']).'</td>';
	print '<td>';
	if (in_array($val['type'], array('int', 'integer'))) $value = GETPOSTISSET($key)?GETPOST($key, 'int'):$object->$key;
	elseif ($val['type'] == 'text') $value = GETPOSTISSET($key)?GETPOST($key,'none'):$object->$key;
	else $value = GETPOSTISSET($key)?GETPOST($key, 'alpha'):$object->$key;
	print $object->showInputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';
}

?>
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->