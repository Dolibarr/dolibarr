<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
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
?>

<!-- BEGIN PHP TEMPLATE admin_extrafields.tpl.php -->
<script type="text/javascript">
    jQuery(document).ready(function() {
    	function init_typeoffields(type)
    	{
    		var size = jQuery("#size");
    		var unique = jQuery("#unique");
    		var required = jQuery("#required");
    		if (type == 'date') { size.val('').attr('disabled','disabled'); unique.removeAttr('disabled','disabled'); }
    		else if (type == 'datetime') { size.val('').attr('disabled','disabled'); unique.removeAttr('disabled','disabled'); }
    		else if (type == 'double') { size.val('24,8').removeAttr('disabled'); unique.removeAttr('disabled','disabled'); }
    		else if (type == 'int') { size.val('10').removeAttr('disabled'); unique.removeAttr('disabled','disabled'); }
    		else if (type == 'text') { size.val('2000').removeAttr('disabled'); unique.attr('disabled','disabled').removeAttr('checked'); }
    		else if (type == 'varchar') { size.val('255').removeAttr('disabled'); unique.removeAttr('disabled','disabled'); }
    		else size.val('').attr('disabled','disabled');
    	}
    	init_typeoffields('<?php echo GETPOST('type'); ?>');
    	jQuery("#type").change(function() {
    		init_typeoffields($(this).val());
    	});
    });
</script>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="add">

<table summary="listofattributes" class="border centpercent">

<!-- Label -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td><td class="valeur"><input type="text" name="label" size="40" value="<?php echo GETPOST('label'); ?>"></td></tr>
<!-- Code -->
<tr><td class="fieldrequired"><?php echo $langs->trans("AttributeCode"); ?> (<?php echo $langs->trans("AlphaNumOnlyCharsAndNoSpace"); ?>)</td><td class="valeur"><input type="text" name="attrname" size="10" value="<?php echo GETPOST('attrname'); ?>"></td></tr>
<!-- Type -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Type"); ?></td><td class="valeur">
<?php print $form->selectarray('type',$type2label,GETPOST('type')); ?>
</td></tr>
<!-- Size -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Size"); ?></td><td class="valeur"><input id="size" type="text" name="size" size="5" value="<?php echo (GETPOST('size')?GETPOST('size'):''); ?>"></td></tr>
<!-- Unique -->
<tr><td><?php echo $langs->trans("Unique"); ?></td><td class="valeur"><input id="unique" type="checkbox" name="unique" <?php echo (GETPOST('unique')?' checked="true"':''); ?>></td></tr>
<!-- Required -->
<tr><td><?php echo $langs->trans("Required"); ?></td><td class="valeur"><input id="required" type="checkbox" name="required" <?php echo (GETPOST('required')?' checked="true"':''); ?>></td></tr>
</table>

<div align="center"><br><input type="submit" name="button" class="button" value="<?php echo $langs->trans("Save"); ?>"> &nbsp;
<input type="submit" name="button" class="button" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE admin_extrafields.tpl.php -->
