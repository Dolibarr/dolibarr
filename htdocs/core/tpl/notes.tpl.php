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

$hide = $object->extraparams['notes']['showhide'];
$module = $object->element;
$note_public = 'note_public';
$note_private = 'note';
if ($module == 'propal') $module = 'propale';
else if ($module == 'fichinter') { $module = 'ficheinter'; $note_private = 'note_private'; }

?>

<!-- BEGIN PHP TEMPLATE -->

<script type="text/javascript">
$(document).ready(function() {
	$("#hide-notes").click(function(){
		setShowHide(1);
		$("#notes_bloc").hide("blind", {direction: "vertical"}, 800).removeClass("nohideobject");
		$(this).hide();
		$("#show-notes").show();
	});
	$("#show-notes").click(function(){
		setShowHide(0);
		$("#notes_bloc").show("blind", {direction: "vertical"}, 800).addClass("nohideobject");
		$(this).hide();
		$("#hide-notes").show();
	});
	function setShowHide(status) {
		var id			= <?php echo $object->id; ?>;
		var element		= '<?php echo $object->element; ?>';
		var htmlelement	= 'notes';
		var type		= 'showhide';
		
		$.get("<?php echo dol_buildpath('/core/ajax/extraparams.php', 1); ?>?id="+id+"&element="+element+"&htmlelement="+htmlelement+"&type="+type+"&value="+status);
	}
});
</script>

<div style="float:right; position: relative; top: 3px; right:5px;" id="hide-notes" class="linkobject<?php echo ($hide ? ' hideobject' : ''); ?>"><?php echo img_picto('', '1uparrow.png'); ?></div>
<div style="float:right; position: relative; top: 3px; right:5px;" id="show-notes" class="linkobject<?php echo ($hide ? '' : ' hideobject'); ?>"><?php echo img_picto('', '1downarrow.png'); ?></div>
<div class="liste_titre"><?php echo $langs->trans('Notes'); ?></div>

<div id="notes_bloc" class="<?php echo ($hide ? 'hideobject' : 'nohideobject'); ?>">
<table class="border allwidth">		
	<tr>
		<td width="25%" valign="top"><?php echo $form->editfieldkey("NotePublic",$note_public,$object->note_public,$object,$user->rights->$module->creer,'textarea'); ?></td>
		<td><?php echo $form->editfieldval("NotePublic",$note_public,$object->note_public,$object,$user->rights->$module->creer,'textarea'); ?></td>
	</tr>
	
	<?php if (! $user->societe_id) { ?>
	<tr>
		<td width="25%" valign="top"><?php echo $form->editfieldkey("NotePrivate",$note_private,$object->note_private,$object,$user->rights->$module->creer,'textarea'); ?></td>
		<td><?php echo $form->editfieldval("NotePrivate",$note_private,$object->note_private,$object,$user->rights->$module->creer,'textarea'); ?></td>
	</tr>
	<?php } ?>
	
</table>
</div>
<br>

<!-- END PHP TEMPLATE -->