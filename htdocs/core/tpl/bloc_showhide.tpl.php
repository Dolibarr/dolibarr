<?php
/* Copyright (C) 2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2013 Laurent Destailleur <eldy@users.sourceforge.net>
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
 */

$hide = true;	// Hide by default
if (isset($parameters['showblocbydefault'])) $hide=(empty($parameters['showblocbydefault']) ? true : false);
if (isset($object->extraparams[$blocname]['showhide'])) $hide = (empty($object->extraparams[$blocname]['showhide']) ? true : false);

?>

<!-- BEGIN PHP TEMPLATE BLOC SHOW/HIDE -->

<script type="text/javascript">
$(document).ready(function() {
	$("#hide-<?php echo $blocname ?>").click(function(){
		setShowHide(0);
		$("#<?php echo $blocname ?>_bloc").hide("blind", {direction: "vertical"}, 300).removeClass("nohideobject");
		$(this).hide();
		$("#show-<?php echo $blocname ?>").show();
	});
	$("#show-<?php echo $blocname ?>").click(function(){
		setShowHide(1);
		$("#<?php echo $blocname ?>_bloc").show("blind", {direction: "vertical"}, 300).addClass("nohideobject");
		$(this).hide();
		$("#hide-<?php echo $blocname ?>").show();
	});
	function setShowHide(status) {
		var id			= <?php echo $object->id; ?>;
		var element		= '<?php echo $object->element; ?>';
		var htmlelement	= '<?php echo $blocname ?>';
		var type		= 'showhide';

		$.get("<?php echo dol_buildpath('/core/ajax/extraparams.php', 1); ?>?id="+id+"&element="+element+"&htmlelement="+htmlelement+"&type="+type+"&value="+status);
	}
});
</script>

<div style="float:right; position: relative; top: 3px; right:5px;" id="hide-<?php echo $blocname ?>" class="linkobject<?php echo ($hide ? ' hideobject' : ''); ?>"><?php echo img_picto('', '1uparrow.png'); ?></div>
<div style="float:right; position: relative; top: 3px; right:5px;" id="show-<?php echo $blocname ?>" class="linkobject<?php echo ($hide ? '' : ' hideobject'); ?>"><?php echo img_picto('', '1downarrow.png'); ?></div>
<div id="<?php echo $blocname ?>_title" class="liste_titre"><?php echo $title; ?></div>

<div id="<?php echo $blocname ?>_bloc" class="<?php echo ($hide ? 'hideobject' : 'nohideobject'); ?>">

<?php include DOL_DOCUMENT_ROOT.'/core/tpl/'.$blocname.'.tpl.php'; ?>

</div>
<br>

<!-- END PHP TEMPLATE BLOC SHOW/HIDE -->