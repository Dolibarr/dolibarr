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
    		if (type == 'date') { size.attr('disabled','disabled'); }
    		else if (type == 'datetime') { size.attr('disabled','disabled'); }
    		else if (type == 'double') { size.removeAttr('disabled'); }
    		else if (type == 'int') { size.removeAttr('disabled'); }
    		else if (type == 'text') { size.removeAttr('disabled'); unique.attr('disabled','disabled').removeAttr('checked'); }
    		else if (type == 'varchar') { size.removeAttr('disabled'); }
    		else size.val('').attr('disabled','disabled');
    	}
    	init_typeoffields(jQuery("#type").val());
    });
</script>


<form action="<?php echo $_SERVER["PHP_SELF"]; ?>?attrname=<?php echo $attrname; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="attrname" value="<?php echo $attrname; ?>">
<input type="hidden" name="action" value="update">

<table summary="listofattributes" class="border centpercent">

<!-- Label -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td><td class="valeur"><input type="text" name="label" size="40" value="<?php echo $extrafields->attribute_label[$attrname]; ?>"></td></tr>
<!-- Code -->
<tr><td class="fieldrequired"><?php echo $langs->trans("AttributeCode"); ?></td><td class="valeur"><?php echo $attrname; ?></td></tr>
<!-- Type -->
<?php
$type=$extrafields->attribute_type[$attrname];
$size=$extrafields->attribute_size[$attrname];
$unique=$extrafields->attribute_unique[$attrname];
$required=$extrafields->attribute_required[$attrname];
?>
<tr><td class="fieldrequired"><?php echo $langs->trans("Type"); ?></td><td class="valeur">
<?php print $type2label[$type]; ?>
<input type="hidden" name="type" id="type" value="<?php print $type; ?>">
</td></tr>
<!-- Size -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Size"); ?></td><td><input id="size" type="text" name="size" size="5" value="<?php echo $size; ?>"></td></tr>
<!-- Unique -->
<tr><td><?php echo $langs->trans("Unique"); ?></td><td class="valeur"><input id="unique" type="checkbox" name="unique" <?php echo ($unique?' checked="true"':''); ?>></td></tr>
<!-- Required -->
<tr><td><?php echo $langs->trans("Required"); ?></td><td class="valeur"><input id="required" type="checkbox" name="required" <?php echo ($required?' checked="true"':''); ?>></td></tr>

</table>

<div align="center"><br><input type="submit" name="button" class="button" value="<?php echo $langs->trans("Save"); ?>"> &nbsp;
<input type="submit" name="button" class="button" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE admin_extrafields.tpl.php -->
