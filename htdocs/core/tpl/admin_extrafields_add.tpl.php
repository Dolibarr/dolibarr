<?php
/* Copyright (C) 2010-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
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

/**
 * The following vars must be defined:
 * $type2label
 * $form
 * $conf, $lang,
 * The following vars may also be defined:
 * $elementtype
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


$langs->load("modulebuilder");

?>

<!-- BEGIN PHP TEMPLATE admin_extrafields_add.tpl.php -->
<script type="text/javascript">
    jQuery(document).ready(function() {
    	function init_typeoffields(type)
    	{
        	console.log("We select a new type = "+type);
        	var size = jQuery("#size");
    		var computed_value = jQuery("#computed_value");
    		var langfile = jQuery("#langfile");
    		var default_value = jQuery("#default_value");
    		var unique = jQuery("#unique");
    		var required = jQuery("#required");
    		var alwayseditable = jQuery("#alwayseditable");
    		var list = jQuery("#list");
    		<?php
    		if ((GETPOST('type','alpha') != "select") && (GETPOST('type','alpha') != "sellist"))
    		{
    			print 'jQuery("#value_choice").hide();';
    		}

    		if (GETPOST('type','alpha') == "separate")
    		{
				print "jQuery('#size, #default_value, #langfile').val('').prop('disabled', true);";
    			print 'jQuery("#value_choice").hide();';
    		}
    		?>

    		// Case of computed field
    		console.log(type);
    		if (type == '' || type == 'varchar' || type == 'int' || type == 'double' || type == 'price') {
    			jQuery("tr.extra_computed_value").show();
    		} else {
    			computed_value.val(''); jQuery("tr.extra_computed_value").hide();
    		}
    		if (computed_value.val())
    		{
        		console.log("We enter a computed formula");
        		jQuery("#default_value").val('');
        		/* jQuery("#unique, #required, #alwayseditable, #list").removeAttr('checked'); */
        		jQuery("#default_value, #unique, #required, #alwayseditable, #list").attr('disabled', true);
        		jQuery("tr.extra_default_value, tr.extra_unique, tr.extra_required, tr.extra_alwayseditable, tr.extra_list").hide();
    		}
    		else
    		{
        		console.log("No computed formula");
        		jQuery("#default_value, #unique, #required, #alwayseditable, #list").attr('disabled', false);
        		jQuery("tr.extra_default_value, tr.extra_unique, tr.extra_required, tr.extra_alwayseditable, tr.extra_list").show();
    		}

			if (type == 'date')          { size.val('').prop('disabled', true); unique.removeAttr('disabled'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
			else if (type == 'datetime') { size.val('').prop('disabled', true); unique.removeAttr('disabled'); jQuery("#value_choice").hide(); jQuery("#helpchkbxlst").hide();}
    		else if (type == 'double')   { size.val('24,8').removeAttr('disabled'); unique.removeAttr('disabled'); jQuery("#value_choice").hide(); jQuery("#helpchkbxlst").hide();}
			else if (type == 'int')      { size.val('10').removeAttr('disabled'); unique.removeAttr('disabled'); jQuery("#value_choice").hide(); jQuery("#helpchkbxlst").hide();}
			else if (type == 'text')     { size.val('2000').removeAttr('disabled'); unique.prop('disabled', true).removeAttr('checked'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
			else if (type == 'html')     { size.val('2000').removeAttr('disabled'); unique.prop('disabled', true).removeAttr('checked'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
    		else if (type == 'varchar')  { size.val('255').removeAttr('disabled'); unique.removeAttr('disabled'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
			else if (type == 'password') { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); required.val('').prop('disabled', true); default_value.val('').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();jQuery("#helppassword").show();}
			else if (type == 'boolean')  { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide();}
			else if (type == 'price')    { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide();}
			else if (type == 'select')   { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").show();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();jQuery("#helppassword").hide();}
			else if (type == 'sellist')  { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").show();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();jQuery("#helppassword").hide();}
			else if (type == 'radio')    { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").show();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();jQuery("#helppassword").hide();}
			else if (type == 'checkbox') { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").show();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();jQuery("#helppassword").hide();}
			else if (type == 'chkbxlst') { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").show();jQuery("#helplink").hide();jQuery("#helppassword").hide();}
			else if (type == 'link')     { size.val('').prop('disabled', true); unique.removeAttr('disabled'); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").show();jQuery("#helppassword").hide();}
			else if (type == 'separate') {
				langfile.val('').prop('disabled',true);size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); required.val('').prop('disabled', true);
				jQuery("#value_choice").hide();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();
			}
			else {	// type = string
				size.val('').prop('disabled', true);
				unique.removeAttr('disabled');
			}

			if (type == 'separate')
			{
				required.removeAttr('checked').prop('disabled', true); alwayseditable.removeAttr('checked').prop('disabled', true); list.removeAttr('checked').prop('disabled', true);
				jQuery('#size, #default_value, #langfile').val('').prop('disabled', true);
				jQuery('#list').val(3);	// visible on create/update/view form only
			}
			else
			{
				default_value.removeAttr('disabled');
				langfile.removeAttr('disabled');required.removeAttr('disabled'); alwayseditable.removeAttr('disabled'); list.removeAttr('disabled');
			}
    	}
    	init_typeoffields('<?php echo GETPOST('type','alpha'); ?>');
    	jQuery("#type").change(function() {
    		init_typeoffields($(this).val());
    	});

    	// If we enter a formula, we disable other fields
    	jQuery("#computed_value").keyup(function() {
    		init_typeoffields(jQuery('#type').val());
    	});
    });
</script>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="add">

<?php dol_fiche_head(); ?>

<table summary="listofattributes" class="border centpercent">
<!-- Label -->
<tr><td class="titlefield fieldrequired"><?php echo $langs->trans("Label"); ?></td><td class="valeur"><input type="text" name="label" size="40" value="<?php echo GETPOST('label','alpha'); ?>"></td></tr>
<!-- Code -->
<tr><td class="fieldrequired"><?php echo $langs->trans("AttributeCode"); ?></td><td class="valeur"><input type="text" name="attrname" id="attrname"  size="10" value="<?php echo GETPOST('attrname','alpha'); ?>"> (<?php echo $langs->trans("AlphaNumOnlyLowerCharsAndNoSpace"); ?>)</td></tr>
<!-- Type -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Type"); ?></td><td class="valeur">
<?php print $form->selectarray('type',$type2label,GETPOST('type','alpha')); ?>
</td></tr>
<!-- Size -->
<tr class="extra_size"><td class="fieldrequired"><?php echo $langs->trans("Size"); ?></td><td class="valeur"><input id="size" type="text" name="size" size="5" value="<?php echo (GETPOST('size','alpha')?GETPOST('size','alpha'):''); ?>"></td></tr>
<!-- Default Value (for select list / radio/ checkbox) -->
<tr id="value_choice">
<td>
	<?php echo $langs->trans("Value"); ?>
</td>
<td>
    <table class="nobordernopadding">
    <tr><td>
    	<textarea name="param" id="param" cols="80" rows="<?php echo ROWS_4 ?>"><?php echo GETPOST('param','alpha'); ?></textarea>
    </td><td>
    <span id="helpselect"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpselect"),1,0,'', 0, 2, 'helpvalue1')?></span>
    <span id="helpsellist"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpsellist"),1,0,'', 0, 2, 'helpvalue2')?></span>
    <span id="helpchkbxlst"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpchkbxlst"),1,0,'', 0, 2, 'helpvalue3')?></span>
    <span id="helplink"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelplink"),1,0,'', 0, 2, 'helpvalue4')?></span>
    <span id="helppassword"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpPassword"),1,0,'', 0, 2, 'helpvalue5')?></span>
    </td></tr>
    </table>
</td>
</tr>
<!-- Position -->
<tr><td class="titlefield"><?php echo $langs->trans("Position"); ?></td><td class="valeur"><input type="text" name="pos" size="5" value="<?php echo GETPOST('pos','int'); ?>"></td></tr>
<!-- Language file -->
<tr><td class="titlefield"><?php echo $langs->trans("LanguageFile"); ?></td><td class="valeur"><input type="text" id="langfile" name="langfile" class="minwidth200" value="<?php echo dol_escape_htmltag(GETPOST('langfile','alpha')); ?>"></td></tr>
<!-- Computed Value -->
<tr class="extra_computed_value"><td><?php echo $form->textwithpicto($langs->trans("ComputedFormula"), $langs->trans("ComputedFormulaDesc"), 1, 'help', '', 0, 2, 'tooltipcompute'); ?></td><td class="valeur"><input id="computed_value" type="text" name="computed_value" class="quatrevingtpercent" value="<?php echo (GETPOST('computed_value','none')?GETPOST('computed_value','none'):''); ?>"></td></tr>
<!-- Default Value (at sql setup level) -->
<tr class="extra_default_value"><td><?php echo $langs->trans("DefaultValue").' ('.$langs->trans("Database").')'; ?></td><td class="valeur"><input id="default_value" type="text" name="default_value" size="5" value="<?php echo (GETPOST('default_value','alpha')?GETPOST('default_value','alpha'):''); ?>"></td></tr>
<!-- Unique -->
<tr class="extra_unique"><td><?php echo $langs->trans("Unique"); ?></td><td class="valeur"><input id="unique" type="checkbox" name="unique"<?php echo (GETPOST('unique','alpha')?' checked':''); ?>></td></tr>
<!-- Required -->
<tr class="extra_required"><td><?php echo $langs->trans("Required"); ?></td><td class="valeur"><input id="required" type="checkbox" name="required"<?php echo (GETPOST('required','alpha')?' checked':''); ?>></td></tr>
<!-- Always editable -->
<tr class="extra_alwayseditable"><td><?php echo $langs->trans("AlwaysEditable"); ?></td><td class="valeur"><input id="alwayseditable" type="checkbox" name="alwayseditable"<?php echo ((GETPOST('alwayseditable','alpha') || ! GETPOST('button','alpha'))?' checked':''); ?>></td></tr>
<?php if ($conf->multicompany->enabled) { ?>
    <tr><td><?php echo $langs->trans("AllEntities"); ?></td><td class="valeur"><input id="entitycurrentorall" type="checkbox" name="entitycurrentorall"<?php echo (GETPOST('entitycurrentorall','alpha') ? '':' checked'); ?>></td></tr>
<?php } ?>
<!-- Visibility -->
<tr><td class="extra_list"><?php echo $form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc")); ?>
</td><td class="valeur"><input id="list" size="1" type="text" name="list" value="<?php echo GETPOST('list','int')!='' ? GETPOST('list','int') : '1'; ?>"></td></tr>
</table>

<?php dol_fiche_end(); ?>

<div align="center"><input type="submit" name="button" class="button" value="<?php echo $langs->trans("Save"); ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="button" class="button" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE admin_extrafields_add.tpl.php -->
