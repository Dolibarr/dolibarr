<?php

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
