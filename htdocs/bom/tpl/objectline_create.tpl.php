<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $forceall (0 by default, 1 for supplier invoices/orders)
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object)) {
    print "Error: this template page cannot be called directly as an URL";
    exit;
}


global $forceall, $forcetoshowtitlelines;

if (empty($forceall)) $forceall=0;


// Define colspan for the button 'Add'
$colspan = 3;	// Columns: total ht + col edit + col delete
//print $object->element;

// Lines for extrafield
$objectline = new BOMLine($this->db);
?>

<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->
<?php
$nolinesbefore=(count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
?>
<tr class="liste_titre<?php echo ($nolinesbefore?'':' liste_titre_add_') ?> nodrag nodrop">
	<?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"></td>
	<?php } ?>
	<td class="linecoldescription minwidth500imp">
		<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span><?php // echo $langs->trans("FreeZone"); ?>
	</td>
	<td class="linecolqty right"><?php echo $langs->trans('Qty'); ?></td>
	<?php
	if ($conf->global->PRODUCT_USE_UNITS)
	{
		print '<td class="linecoluseunit left">';
		print '<span id="title_units">';
		print $langs->trans('Unit');
		print '</span></td>';
	}
	?>
	<td class="linecollost right"><?php echo $form->textwithpicto($langs->trans('ManufacturingEfficiency'), $langs->trans('ValueOfMeansLoss')); ?></td>
	<td class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>
<?php
}
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo ($nolinesbefore || $object->element=='contrat')?'':' liste_titre_create'; ?>">
<?php
    $coldisplay=0;

    // Adds a line numbering column
    if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
      $coldisplay++;
      echo '<td class="nobottom linecolnum center"></td>';
    }

    $coldisplay++;
    ?>
	<td class="nobottom linecoldescription minwidth500imp">

	<?php
	// Predefined product/service
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		if ($forceall >= 0 && $freelines) echo '<br>';
		echo '<span class="prod_entry_mode_predef">';
		$filtertype='';
		if (! empty($object->element) && $object->element == 'contrat' && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $filtertype='1';

		$statustoshow = -1;
		if (! empty($conf->global->ENTREPOT_EXTRA_STATUS))
		{
			// hide products in closed warehouse, but show products for internal transfer
			$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'));
		}
		else
		{
			$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, '', GETPOST('combinations', 'array'));
		}

		echo '</span>';
	}

	$coldisplay++;
	?>
	<td class="nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="<?php echo (isset($_POST["qty"])?GETPOST("qty", 'alpha', 2):1); ?>">
	</td>
	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
	    $coldisplay++;
		print '<td class="nobottom linecoluseunit left">';
		print $form->selectUnits($line->fk_unit, "units");
		print '</td>';
	}

	$coldisplay++;
	?>
	<td class="nobottom nowrap linecollost right"><input type="text" size="1" name="efficiency" id="efficiency" class="flat right" value="<?php echo (GETPOSTISSET("efficiency")?GETPOST("efficiency", 'alpha'):1); ?>"></td>
	<?php

	$coldisplay+=$colspan;
	?>
	<td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
</tr>

<?php
if (is_object($objectline)) {
	print $objectline->showOptionals($extrafieldsline, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD)?0:1);
}
?>

<script>

/* JQuery for product free or predefined select */
jQuery(document).ready(function() {
	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod").change(function()
	{
		console.log("#idprod change triggered");

  		/* To set focus */
  		if (jQuery('#idprod').val() > 0)
  	  	{
			/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#dp_desc').focus();
			/* focus if CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
   				if (editor) { editor.focus(); }
			}
  	  	}
	});
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
