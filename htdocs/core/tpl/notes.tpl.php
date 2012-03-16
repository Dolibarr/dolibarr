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
if ($module == 'propal') $module = 'propale';

?>

<!-- BEGIN PHP TEMPLATE -->

<script type="text/javascript">
$(document).ready(function() {
	$("#hide-notes").click(function(){
		setShowHide(1);
		$(".notes_line").hide().removeClass("nohideobject");
		$(this).hide();
		$("#show-notes").show();
	});
	$("#show-notes").click(function(){
		setShowHide(0);
		$(".notes_line").show().addClass("nohideobject");
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

<table class="border allwidth">

	<tr class="liste_titre">
		<td colspan="2">
			<div style="float:right;" id="hide-notes" class="linkobject<?php echo ($hide ? ' hideobject' : ''); ?>"><?php echo img_picto('', '1uparrow.png'); ?></div>
			<div style="float:right;" id="show-notes" class="linkobject<?php echo ($hide ? '' : ' hideobject'); ?>"><?php echo img_picto('', '1downarrow.png'); ?></div>
			<?php echo $langs->trans('Notes'); ?>
		</td>
	</tr>
		
	<tr id="note_public_line" class="notes_line <?php echo ($hide ? 'hideobject' : 'nohideobject'); ?>">
		<td width="25%" valign="top"><?php echo $form->editfieldkey("NotePublic",'note_public',$object->note_public,$object,$user->rights->$module->creer,'textarea'); ?></td>
		<td><?php echo $form->editfieldval("NotePublic",'note_public',$object->note_public,$object,$user->rights->$module->creer,'textarea'); ?></td>
	</tr>
	
	<?php if (! $user->societe_id) { ?>
	<tr id="note_private_line"  class="notes_line <?php echo ($hide ? 'hideobject' : 'nohideobject'); ?>">
		<td width="25%" valign="top"><?php echo $form->editfieldkey("NotePrivate",'note',$object->note_private,$object,$user->rights->$module->creer,'textarea'); ?></td>
		<td><?php echo $form->editfieldval("NotePrivate",'note',$object->note_private,$object,$user->rights->$module->creer,'textarea'); ?></td>
	</tr>
	<?php } ?>
	
</table><br>

<!-- END PHP TEMPLATE -->