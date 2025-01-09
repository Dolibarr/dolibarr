<?php
/* Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 */

/**
 * @var Form $form
 * @var Translate $langs
 *
 * @var string $note_public
 * @var string $note_private
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (!empty($object->table_element_line)) {
	// Show object lines
	$result = $object->getLinesArray();
}

// Supposed provided before includion of the tpl
'
@phan-var-force string $note_public
@phan-var-force string $note_private
@phan-var-force int $colwidth
@phan-var-force string $moreparam
@phan-var-force bool $permission
@phan-var-force string $typeofdata
@phan-var-force string $value_public
@phan-var-force string $value_private
';


print '<!-- BEGIN PHP TEMPLATE hrm/core/tpl/skilldet.fiche.tpl.php -->'."\n";

print '<div class="tagtable border table-border tableforfield centpercent">'."\n";
print '<div class="tagtr table-border-row">'."\n";
$editmode = (GETPOST('action', 'aZ09') == 'edit'.$note_public);
print '<div class="tagtd tagtdnote tdtop'.($editmode ? '' : ' sensiblehtmlcontent').' table-key-border-col'.(empty($cssclass) ? '' : ' '.$cssclass).'"'.($colwidth ? ' style="width: '.$colwidth.'%"' : '').'>'."\n";
print $form->editfieldkey("NotePublic", $note_public, $value_public, $object, $permission, $typeofdata, $moreparam, 0, 0);
print '</div>'."\n";
print '<div class="tagtd wordbreak table-val-border-col'.($editmode ? '' : ' sensiblehtmlcontent').'">'."\n";
print $form->editfieldval("NotePublic", $note_public, $value_public, $object, $permission, $typeofdata, '', null, null, $moreparam, 1)."\n";
print '</div>'."\n";
print '</div>'."\n";
if (empty($user->socid)) {
	// Private notes (always hidden to external users)
	print '<div class="tagtr table-border-row">'."\n";
	$editmode = (GETPOST('action', 'aZ09') == 'edit'.$note_private);
	print '<div class="tagtd tagtdnote tdtop'.($editmode ? '' : ' sensiblehtmlcontent').' table-key-border-col'.(empty($cssclass) ? '' : ' '.$cssclass).'"'.($colwidth ? ' style="width: '.$colwidth.'%"' : '').'>'."\n";
	print $form->editfieldkey("NotePrivate", $note_private, $value_private, $object, $permission, $typeofdata, $moreparam, 0, 0);
	print '</div>'."\n";
	print '<div class="tagtd wordbreak table-val-border-col'.($editmode ? '' : ' sensiblehtmlcontent').'">'."\n";
	print $form->editfieldval("NotePrivate", $note_private, $value_private, $object, $permission, $typeofdata, '', null, null, $moreparam, 1);
	print '</div>'."\n";
	print '</div>'."\n";
}
print '</div>'."\n";
?>
<!-- END PHP TEMPLATE NOTES-->
