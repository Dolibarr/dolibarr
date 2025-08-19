<?php
/* Copyright (C) 2025       Yannis Hoareau
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * @var CommonObject $object
 * @var CommonObject $this
 * @var CommonObjectLine $line
 */

$line_color = $object->getSubtotalColors($line->qty);

print '<!-- line for order line '.$line->id.' -->'."\n";
print '<tr style="background:#' . $line_color . '" id="row-'.$line->id.'">'."\n";


$selected = 1;
if (!empty($selectedLines) && !in_array($this->tpl['id'], $selectedLines)) {
	$selected = 0;
}
print "<td colspan='5'>";
print '<input id="cb'.$line->rowid.'" class="flat checkforselect" type="checkbox" name="subtotal_toselect[]" value="'.$line->rowid.'" ' . ($selected ? ' checked="checked"' : '') . ' >';
print $line->desc . "</td>\n";


print '</tr>';
