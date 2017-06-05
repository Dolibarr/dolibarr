<?php
/* Copyright (C) 2010-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
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

?>

<!-- BEGIN PHP TEMPLATE admin_extrafields_add.tpl.php -->
<script type="text/javascript">
    jQuery(document).ready(function() {
    	function init_typeoffields(type)
    	{
        	console.log("selected type is "+type);
    		var size = jQuery("#size");
    		var computed_value = jQuery("#computed_value");
    		var default_value = jQuery("#default_value");
    		var unique = jQuery("#unique");
    		var required = jQuery("#required");
    		var alwayseditable = jQuery("#alwayseditable");
    		var list = jQuery("#list");
    		<?php
    		if ((GETPOST('type') != "select") && (GETPOST('type') != "sellist"))
    		{
    			print 'jQuery("#value_choice").hide();';
    		}

    		if (GETPOST('type') == "separate")
    		{
				print "jQuery('#size, #default_value').val('').prop('disabled', true);";
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
        		/* jQuery("#unique, #required, #alwayseditable, #ishidden, #list").removeAttr('checked'); */
        		jQuery("#default_value, #unique, #required, #alwayseditable, #ishidden, #list").attr('disabled', true);
        		jQuery("tr.extra_default_value, tr.extra_unique, tr.extra_required, tr.extra_alwayseditable, tr.extra_ishidden, tr.extra_list").hide();
    		}
    		else
    		{
        		console.log("No computed formula");
        		jQuery("#default_value, #unique, #required, #alwayseditable, #ishidden, #list").attr('disabled', false);
        		jQuery("tr.extra_default_value, tr.extra_unique, tr.extra_required, tr.extra_alwayseditable, tr.extra_ishidden, tr.extra_list").show();
    		}
    		
			if (type == 'date')          { size.val('').prop('disabled', true); unique.removeAttr('disabled'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
			else if (type == 'datetime') { size.val('').prop('disabled', true); unique.removeAttr('disabled'); jQuery("#value_choice").hide(); jQuery("#helpchkbxlst").hide();}
    		else if (type == 'double')   { size.val('24,8').removeAttr('disabled'); unique.removeAttr('disabled'); jQuery("#value_choice").hide(); jQuery("#helpchkbxlst").hide();}
			else if (type == 'int')      { size.val('10').removeAttr('disabled'); unique.removeAttr('disabled'); jQuery("#value_choice").hide(); jQuery("#helpchkbxlst").hide();}
			else if (type == 'text')     { size.val('2000').removeAttr('disabled'); unique.prop('disabled', true).removeAttr('checked'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
    		else if (type == 'varchar')  { size.val('255').removeAttr('disabled'); unique.removeAttr('disabled'); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide(); }
			else if (type == 'boolean')  { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide();}
			else if (type == 'price')    { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").hide();jQuery("#helpchkbxlst").hide();}
			else if (type == 'select')   { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").show();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();}
			else if (type == 'sellist')  { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").show();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();}
			else if (type == 'radio')    { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").show();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();}
			else if (type == 'checkbox') { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").show();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();}
			else if (type == 'chkbxlst') { size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").show();jQuery("#helplink").hide();}
			else if (type == 'link')     { size.val('').prop('disabled', true); unique.removeAttr('disabled'); jQuery("#value_choice").show();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").show();}
			else if (type == 'separate') { 
				size.val('').prop('disabled', true); unique.removeAttr('checked').prop('disabled', true); required.val('').prop('disabled', true); 
				jQuery("#value_choice").hide();jQuery("#helpselect").hide();jQuery("#helpsellist").hide();jQuery("#helpchkbxlst").hide();jQuery("#helplink").hide();
			}
			else {	// type = string
				size.val('').prop('disabled', true);
				unique.removeAttr('disabled');
			}

			if (type == 'separate')
			{
				required.removeAttr('checked').prop('disabled', true); alwayseditable.removeAttr('checked').prop('disabled', true); list.val('').prop('disabled', true);
				jQuery('#size, #default_value').val('').prop('disabled', true); 
			}
			else
			{
				default_value.removeAttr('disabled');
				required.removeAttr('disabled'); alwayseditable.removeAttr('disabled'); list.val('').removeAttr('disabled'); 
			}
    	}
    	init_typeoffields('<?php echo GETPOST('type'); ?>');
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
<tr><td class="titlefield fieldrequired"><?php echo $langs->trans("Label"); ?></td><td class="valeur"><input type="text" name="label" size="40" value="<?php echo GETPOST('label'); ?>"></td></tr>
<!-- Code -->
<tr><td class="fieldrequired"><?php echo $langs->trans("AttributeCode"); ?></td><td class="valeur"><input type="text" name="attrname" id="attrname"  size="10" value="<?php echo GETPOST('attrname'); ?>"> (<?php echo $langs->trans("AlphaNumOnlyLowerCharsAndNoSpace"); ?>)</td></tr>
<!-- Type -->
<tr><td class="fieldrequired"><?php echo $langs->trans("Type"); ?></td><td class="valeur">
<?php print $form->selectarray('type',$type2label,GETPOST('type')); ?>
</td></tr>
<!-- Size -->
<tr class="extra_size"><td class="fieldrequired"><?php echo $langs->trans("Size"); ?></td><td class="valeur"><input id="size" type="text" name="size" size="5" value="<?php echo (GETPOST('size')?GETPOST('size'):''); ?>"></td></tr>
<!-- Default Value (for select list / radio/ checkbox) -->
<tr id="value_choice">
<td>
	<?php echo $langs->trans("Value"); ?>
</td>
<td>
    <table class="nobordernopadding">
    <tr><td>
    	<textarea name="param" id="param" cols="80" rows="<?php echo ROWS_4 ?>"><?php echo GETPOST('param'); ?></textarea>
    </td><td>
    <span id="helpselect"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpselect"),1,0)?></span>
    <span id="helpsellist"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpsellist"),1,0)?></span>
    <span id="helpchkbxlst"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelpchkbxlst"),1,0)?></span>
    <span id="helplink"><?php print $form->textwithpicto('', $langs->trans("ExtrafieldParamHelplink"),1,0)?></span>
    </td></tr>
    </table>
</td>
</tr>
<!-- Position -->
<tr><td class="titlefield"><?php echo $langs->trans("Position"); ?></td><td class="valeur"><input type="text" name="pos" size="5" value="<?php echo GETPOST('pos'); ?>"></td></tr>
<!-- Computed Value -->
<tr class="extra_computed_value"><td><?php echo $form->textwithpicto($langs->trans("ComputedFormula"), $langs->trans("ComputedFormulaDesc"), 1, 'help', '', 0, 2, 'tooltipcompute'); ?></td><td class="valeur"><input id="computed_value" type="text" name="computed_value" class="quatrevingtpercent" value="<?php echo (GETPOST('"computed_value"')?GETPOST('"computed_value"'):''); ?>"></td></tr>
<!-- Default Value (at sql setup level) -->
<tr class="extra_default_value"><td><?php echo $langs->trans("DefaultValue").' ('.$langs->trans("Database").')'; ?></td><td class="valeur"><input id="default_value" type="text" name="default_value" size="5" value="<?php echo (GETPOST('"default_value"')?GETPOST('"default_value"'):''); ?>"></td></tr>
<!-- Unique -->
<tr class="extra_unique"><td><?php echo $langs->trans("Unique"); ?></td><td class="valeur"><input id="unique" type="checkbox" name="unique"<?php echo (GETPOST('unique')?' checked':''); ?>></td></tr>
<!-- Required -->
<tr class="extra_required"><td><?php echo $langs->trans("Required"); ?></td><td class="valeur"><input id="required" type="checkbox" name="required"<?php echo (GETPOST('required')?' checked':''); ?>></td></tr>
<!-- Always editable -->
<tr class="extra_alwayseditable"><td><?php echo $langs->trans("AlwaysEditable"); ?></td><td class="valeur"><input id="alwayseditable" type="checkbox" name="alwayseditable"<?php echo ((GETPOST('alwayseditable') || ! GETPOST('button'))?' checked':''); ?>></td></tr>
<!-- Is visible or not -->
<?php if (! empty($conf->global->MAIN_CAN_HIDE_EXTRAFIELDS)) { ?>
<tr class="extra_ishidden"><td><?php echo $langs->trans("Hidden"); ?></td><td class="valeur"><input id="ishidden" type="checkbox" name="ishidden"<?php echo (GETPOST('ishidden') ?' checked' : ''); ?>></td></tr>
<?php } ?>
<?php if ($conf->global->MAIN_FEATURES_LEVEL >= 2) { ?>
<!-- By default visible into list -->
<tr><td><?php echo $langs->trans("ByDefaultInList"); ?>
<?php echo img_info($langs->trans("FeatureNotYetSupported")); ?>
</td><td class="valeur"><input id="list" type="checkbox" name="list" <?php echo (GETPOST('list')?' checked':''); ?>></td></tr>
<?php } ?>
</table>

<?php dol_fiche_end(); ?>

<div align="center"><input type="submit" name="button" class="button" value="<?php echo $langs->trans("Save"); ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="button" class="button" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE admin_extrafields_add.tpl.php -->
