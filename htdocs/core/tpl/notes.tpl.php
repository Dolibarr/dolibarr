<?php
/* Copyright (C) 2012 Regis Houssin <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 */

$module = $object->element;
$note_public = 'note_public';
$note_private = 'note';
if ($module == 'propal') $module = 'propale';
else if ($module == 'fichinter') { $module = 'ficheinter'; $note_private = 'note_private'; }

if (! empty($conf->global->FCKEDITOR_ENABLE_SOCIETE)) $typeofdata='ckeditor:dolibarr_notes:100%:200:1:12:100';
else $typeofdata='textarea:12:100';
?>

<!-- BEGIN PHP TEMPLATE NOTES -->
<div class="table-border">
	<div class="table-border-row">
		<div class="table-key-border-col"><?php echo $form->editfieldkey("NotePublic",$note_public,$object->note_public,$object,$user->rights->$module->creer,$typeofdata); ?></div>
		<div class="table-val-border-col"><?php echo $form->editfieldval("NotePublic",$note_public,$object->note_public,$object,$user->rights->$module->creer,$typeofdata); ?></div>
	</div>
<?php if (! $user->societe_id) { ?>
	<div class="table-border-row">
		<div class="table-key-border-col"><?php echo $form->editfieldkey("NotePrivate",$note_private,$object->note_private,$object,$user->rights->$module->creer,$typeofdata); ?></div>
		<div class="table-val-border-col"><?php echo $form->editfieldval("NotePrivate",$note_private,$object->note_private,$object,$user->rights->$module->creer,$typeofdata); ?></div>
	</div>
<?php } ?>
</div>
<!-- END PHP TEMPLATE NOTES-->
