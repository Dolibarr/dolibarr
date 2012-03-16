<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010-2012 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *
 * Need to have following variables defined:
 * $conf
 * $langs
 * $dateSelector
 * $this (invoice, order, ...)
 * $line defined
 */
?>

<!-- BEGIN PHP TEMPLATE freeproductline_edit.tpl.php -->
<form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'#'.$line->id; ?>" method="POST">
<input type="hidden" name="token" value="<?php  echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="updateligne">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">
<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
<input type="hidden" name="type" value="<?php echo $line->product_type; ?>">

<tr <?php echo $bc[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>
	<a name="<?php echo $line->id; ?>"></a>

	<?php
	if (is_object($hookmanager))
	{
	    $parameters=array('fk_parent_line'=>$line->fk_parent_line);
	    echo $hookmanager->executeHooks('formEditProductOptions',$parameters,$this,$action);
	}

	// editeur wysiwyg
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
	$doleditor=new DolEditor('desc',$line->description,'',164,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>

	<td align="right"><?php echo $form->load_tva('tva_tx',$line->tva_tx,$seller,$buyer,0,$line->info_bits,$line->product_type); ?></td>

	<td align="right"><input size="6" type="text" class="flat" name="subprice" value="<?php echo price($line->subprice,0,'',0); ?>"></td>

	<td align="right">
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="2" type="text" class="flat" name="qty" value="<?php echo $line->qty; ?>">
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<td align="right" nowrap>
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="1" type="text" class="flat" name="remise_percent" value="<?php echo $line->remise_percent; ?>">%
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<td align="center" colspan="5" valign="middle"><input type="submit" class="button" name="save" value="<?php echo $langs->trans("Save"); ?>">
	<br><input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>"></td>
</tr>

<?php if ($conf->service->enabled && $dateSelector && $line->product_type == 1)	{ ?>
<tr <?php echo $bc[$var]; ?>>
	<td colspan="9"><?php echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' '; ?>
	<?php
	echo $form->select_date($line->date_start,'date_start',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_start?0:1,"updateligne");
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date($line->date_end,'date_end',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_end?0:1,"updateligne");
	?>
	</td>
</tr>
<?php } ?>

</form>
<!-- END PHP TEMPLATE freeproductline_edit.tpl.php -->
