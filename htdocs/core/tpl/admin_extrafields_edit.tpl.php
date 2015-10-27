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

<!-- BEGIN PHP TEMPLATE admin_extrafields_edit.tpl.php -->
<script type="text/javascript">
    jQuery(document).ready(function() {
    	function init_typeoffields(type)
    	{
    		var size = jQuery("#size");
    		var unique = jQuery("#unique");
    		var required = jQuery("#required");
			if (type == 'date') { size.prop('disabled', true); }
			else if (type == 'datetime') { size.prop('disabled', true); }
    		else if (type == 'double') { size.removeAttr('disabled'); }
    		else if (type == 'int') { size.removeAttr('disabled'); }
			else if (type == 'text') { size.removeAttr('disabled'); unique.prop('disabled', true).removeAttr('checked'); }
    		else if (type == 'varchar') { size.removeAttr('disabled'); }
			else if (type == 'boolean') { size.val('').prop('disabled', true); unique.prop('disabled', true);}
			else if (type == 'price') { size.val('').prop('disabled', true); unique.prop('disabled', true);}
			else size.val('').prop('disabled', true);
    	}
    	init_typeoffields(jQuery("#type").val());
    });
</script>


<form action="<?php echo $_SERVER["PHP_SELF"]; ?>?attrname=<?php echo $attrname; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="attrname" value="<?php echo $attrname; ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="rowid" value="<?php echo $rowid ?>">

<?php dol_fiche_head(); ?>

<table summary="listofattributes" class="border centpercent">

<?php
$type=$extrafields->attribute_type[$attrname];
$size=$extrafields->attribute_size[$attrname];
$unique=$extrafields->attribute_unique[$attrname];
$required=$extrafields->attribute_required[$attrname];
$pos=$extrafields->attribute_pos[$attrname];
$alwayseditable=$extrafields->attribute_alwayseditable[$attrname];
$param=$extrafields->attribute_param[$attrname];
$perms=$extrafields->attribute_perms[$attrname];
$list=$extrafields->attribute_list[$attrname];

if((($type == 'select') || ($type == 'checkbox') || ($type == 'radio')) && is_array($param))
{
	$param_chain = '';
	foreach ($param['options'] as $key => $value)
	{
		if(strlen($key))
		{
			$param_chain .= $key.','.$value."\n";
		}
	}
}
elseif (($type== 'sellist') || ($type == 'chkbxlst') || ($type == 'link') )
{
	$paramlist=array_keys($param['options']);
	$param_chain = $paramlist[0];
}
?>
<!-- Label -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td><td class="valeur"><input type="text" name="label" size="40" value="<?php echo $extrafields->attribute_label[$attrname]; ?>"></td></tr>
<!-- Code -->
<tr><td class="fieldrequired"><?php echo $langs->trans("AttributeCode"); ?></td><td class="valeur"><?php echo $attrname; ?></td></tr>
<!-- Type -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Type"); ?></td><td class="valeur">
<?php print $type2label[$type]; ?>
<input type="hidden" name="type" id="type" value="<?php print $type; ?>">
</td></tr>
<!-- Size -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Size"); ?></td><td><input id="size" type="text" name="size" size="5" value="<?php echo $size; ?>"></td></tr>
<!-- Position -->
<tr><td><?php echo $langs->trans("Position"); ?></td><td class="valeur"><input type="text" name="pos" size="5" value="<?php  echo $extrafields->attribute_pos[$attrname];  ?>"></td></tr>
<!--  Value (for select list / radio) -->
<?php
if(($type == 'select') || ($type == 'sellist') || ($type == 'checkbox') || ($type == 'chkbxlst') || ($type == 'radio') || ($type == 'link'))
{
?>
<tr id="value_choice">
<td>
	<?php echo $langs->trans("Value"); ?>
</td>
<td>
<table class="nobordernopadding">
<tr><td>
	<textarea name="param" id="param" cols="80" rows="<?php echo ROWS_4 ?>"><?php echo dol_htmlcleanlastbr($param_chain); ?></textarea>
</td><td><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelp".$type),1,0)?></td></tr>
</table>
</td>
</tr>
<?php
}
?>
<!-- Unique -->
<tr><td><?php echo $langs->trans("Unique"); ?></td><td class="valeur"><input id="unique" type="checkbox" name="unique" <?php echo ($unique?' checked':''); ?>></td></tr>
<!-- Required -->
<tr><td><?php echo $langs->trans("Required"); ?></td><td class="valeur"><input id="required" type="checkbox" name="required" <?php echo ($required?' checked':''); ?>></td></tr>
<!-- Always editable -->
<tr><td><?php echo $langs->trans("AlwaysEditable"); ?></td><td class="valeur"><input id="alwayseditable" type="checkbox" name="alwayseditable" <?php echo ($alwayseditable?' checked':''); ?>></td></tr>
<!-- By default visible into list -->
<?php if ($conf->global->MAIN_FEATURES_LEVEL >= 2) { ?>
<tr><td><?php echo $langs->trans("ByDefaultInList"); ?>
<?php echo img_info($langs->trans("FeatureNotYetSupported")); ?>
</td><td class="valeur"><input id="list" type="checkbox" name="list" <?php echo ($list?' checked':''); ?>></td></tr>
<?php } ?>
</table>

<?php dol_fiche_end(); ?>

<div align="center"><input type="submit" name="button" class="button" value="<?php echo $langs->trans("Save"); ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="button" class="button" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE admin_extrafields_edit.tpl.php -->
