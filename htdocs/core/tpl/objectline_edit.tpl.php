<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
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
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */


$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal', 'askpricesupplier','commande'))) $usemargins=1;

global $forceall, $senderissupplier, $inputalsopricewithtax;
if (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 3;	// Col total ht + col edit + col delete
if (! empty($inputalsopricewithtax)) $colspan++;	// We add 1 if col total ttc
if (in_array($object->element,array('propal','askpricesupplier','facture','invoice','commande','order','order_supplier','invoice_supplier'))) $colspan++;	// With this, there is a column move button
?>

<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->

<?php
$coldisplay=-1; // We remove first td
?>
<tr <?php echo $bc[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>><?php $coldisplay+=(! empty($conf->global->MAIN_VIEW_LINE_NUMBER))?2:1; ?>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="product_id" name="productid" value="<?php echo (! empty($line->fk_product)?$line->fk_product:0); ?>" />
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">

	<?php if ($line->fk_product > 0) { ?>

		<a href="<?php echo DOL_URL_ROOT.'/product/card.php?id='.$line->fk_product; ?>">
		<?php
		if ($line->product_type==1) echo img_object($langs->trans('ShowService'),'service');
		else print img_object($langs->trans('ShowProduct'),'product');
		echo ' '.$line->ref;
		?>
		</a>
		<?php
		echo ' - '.nl2br($line->product_label);
		?>

		<br>

	<?php }	?>

	<?php
	if (is_object($hookmanager))
	{
		$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
	    $parameters=array('line'=>$line,'fk_parent_line'=>$fk_parent_line,'var'=>$var,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer);
	    $reshook=$hookmanager->executeHooks('formEditProductOptions',$parameters,$this,$action);
	}

	// Do not allow editing during a situation cycle
	if (empty($this->situation_cycle_ref) || $this->situation_counter == 1)
	{
		// editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows=ROWS_2;
		if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
		$enable=(isset($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
		$toolbarname='dolibarr_details';
		if (! empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname='dolibarr_notes';
		$doleditor=new DolEditor('product_desc',$line->description,'',164,$toolbarname,'',false,true,$enable,$nbrows,'98%');
		$doleditor->Create();
	} else {
		print '<textarea id="desc" class="flat" name="desc" readonly style="width: 200px; height:80px;">' . $line->description . '</textarea>';
	}
	?>
	</td>

	<?php if ($object->element == 'askpricesupplier') { ?>
		<td align="right"><input id="fourn_ref" name="fourn_ref" class="flat" value="<?php echo $line->ref_fourn; ?>" size="12"></td>
	<?php } ?>

	<?php
	$coldisplay++;
	if ($this->situation_counter == 1 || !$this->situation_cycle_ref) {
		print '<td align="right">' . $form->load_tva('tva_tx',$line->tva_tx,$seller,$buyer,0,$line->info_bits,$line->product_type) . '</td>';
	} else {
		print '<td align="right"><input size="1" type="text" class="flat" name="tva_tx" value="' . price($line->tva_tx) . '" readonly />%</td>';
	}

	$coldisplay++;
	print '<td align="right"><input type="text" class="flat" size="8" id="price_ht" name="price_ht" value="' . (isset($line->pu_ht)?price($line->pu_ht,0,'',0):price($line->subprice,0,'',0)) . '"';
	if ($this->situation_counter > 1) print ' readonly';
	print '></td>';

	if ($inputalsopricewithtax)
	{
		$coldisplay++;
		print '<td align="right"><input type="text" class="flat" size="8" id="price_ttc" name="price_ttc" value="'.(isset($line->pu_ttc)?price($line->pu_ttc,0,'',0):'').'"';
		if ($this->situation_counter > 1) print ' readonly';
		print '></td>';
	}
	?>
	<td align="right"><?php $coldisplay++; ?>
	<?php if (($line->info_bits & 2) != 2) {
		// I comment this because it shows info even when not required
		// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
		// must also not be output for most entities (proposal, intervention, ...)
		//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
		print '<input size="3" type="text" class="flat" name="qty" id="qty" value="' . $line->qty . '"';
		if ($this->situation_counter > 1) print ' readonly';
		print '>';
	} else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
		print '<td align="left">';
		print $form->selectUnits($line->fk_unit, "units");
		print '</td>';
	}
	?>

	<td align="right" class="nowrap"><?php $coldisplay++; ?>
	<?php if (($line->info_bits & 2) != 2) {
		print '<input size="1" type="text" class="flat" name="remise_percent" id="remise_percent" value="' . $line->remise_percent . '"';
		if ($this->situation_counter > 1) print ' readonly';
		print '>%';
	} else { ?>
		&nbsp;
	<?php } ?>
	</td>
	<?php
	if ($this->situation_cycle_ref) {
		$coldisplay++;
		print '<td align="right" class="nowrap"><input type="text" size="1" value="' . $line->situation_percent . '" name="progress">%</td>';
	}
	if (! empty($usemargins))
	{
	?>
		<td align="right" class="margininfos"><?php $coldisplay++; ?>
			<!-- For predef product -->
			<?php if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) { ?>
			<select id="fournprice_predef" name="fournprice_predef" class="flat" data-role="none" style="display: none;"></select>
			<?php } ?>
			<!-- For free product -->
			<input type="text" size="5" id="buying_price" name="buying_price" class="hideobject" value="<?php echo price($line->pa_ht,0,'',0); ?>">
		</td>
	    <?php if ($user->rights->margins->creer) {
				if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				  {
				    $margin_rate = (isset($_POST["np_marginRate"])?$_POST["np_marginRate"]:(($line->pa_ht == 0)?'':price($line->marge_tx)));
				    // if credit note, dont allow to modify margin
					if ($line->subprice < 0)
						echo '<td align="right" class="nowrap margininfos">'.$margin_rate.'<span class="hideonsmartphone">%</span></td>';
					else
						echo '<td align="right" class="nowrap margininfos"><input type="text" size="2" name="np_marginRate" value="'.$margin_rate.'"><span class="hideonsmartphone">%</span></td>';
					$coldisplay++;
				  }
				elseif (! empty($conf->global->DISPLAY_MARK_RATES))
				  {
				    $mark_rate = (isset($_POST["np_markRate"])?$_POST["np_markRate"]:price($line->marque_tx));
				    // if credit note, dont allow to modify margin
					if ($line->subprice < 0)
						echo '<td align="right" class="nowrap margininfos">'.$mark_rate.'<span class="hideonsmartphone">%</span></td>';
					else
						echo '<td align="right" class="nowrap margininfos"><input type="text" size="2" name="np_markRate" value="'.$mark_rate.'"><span class="hideonsmartphone">%</span></td>';
					$coldisplay++;
				  }
			  }
	}
	?>

	<!-- colspan=4 for this td because it replace total_ht+3 td for buttons -->
	<td align="center" colspan="<?php echo $colspan; ?>" valign="middle"><?php $coldisplay+=4; ?>
		<input type="submit" class="button" id="savelinebutton" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>

	<?php
	//Line extrafield
	if (!empty($extrafieldsline))
	{
		print $line->showOptionals($extrafieldsline,'edit',array('style'=>$bc[$var],'colspan'=>$coldisplay));
	}
	?>
</tr>

<?php if (! empty($conf->service->enabled) && $line->product_type == 1 && $dateSelector)	 { ?>
<tr id="service_duration_area" <?php echo $bc[$var]; ?>>
	<td colspan="11"><?php echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' '; ?>
	<?php
	$hourmin=(isset($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE:'');
	echo $form->select_date($line->date_start,'date_start',$hourmin,$hourmin,$line->date_start?0:1,"updateligne",1,0,1);
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date($line->date_end,'date_end',$hourmin,$hourmin,$line->date_end?0:1,"updateligne",1,0,1);
	?>
	</td>
</tr>
<?php } ?>


<script type="text/javascript">

<?php
if (! empty($conf->margin->enabled))
{
?>
	jQuery(document).ready(function()
	{
		/* Add rule to clear margin when we change some data, so when we change sell or buy price, margin will be recalculated after submitting form */
		jQuery("#tva_tx").click(function() {						/* somtimes field is a text, sometimes a combo */
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#tva_tx").keyup(function() {						/* somtimes field is a text, sometimes a combo */
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#price_ht").keyup(function() {
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#buying_price").keyup(function() {
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});

		/* Init field buying_price and fournprice */
		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': <?php echo $line->fk_product?$line->fk_product:0; ?>}, function(data) {
          if (data && data.length > 0) {
			var options = '';
			var trouve=false;
			$(data).each(function() {
				options += '<option value="'+this.id+'" price="'+this.price+'"';
				<?php if ($line->fk_fournprice > 0) { ?>
				if (this.id == <?php echo $line->fk_fournprice; ?>) {
					options += ' selected';
					$("#buying_price").val(this.price);
					trouve = true;
				}
				<?php } ?>
				options += '>'+this.label+'</option>';
			});
			options += '<option value=null'+(trouve?'':' selected')+'><?php echo $langs->trans("InputPrice"); ?></option>';
			$("#fournprice").html(options);
			if (trouve) {
				$("#buying_price").hide();
				$("#fournprice").show();
			} else {
				$("#buying_price").show();
			}
			$("#fournprice").change(function() {
				var selval = $(this).find('option:selected').attr("price");
				if (selval)
					$("#buying_price").val(selval).hide();
				else
					$('#buying_price').show();
			});
		} else {
			$("#fournprice").hide();
			$('#buying_price').show();
		}
		}, 'json');

		/* Add rules to reset price_ht from margin info */
		<?php
		if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		{
		?>
			$('#savelinebutton').click(function (e) {
				return checkEditLine(e, "np_marginRate");
			});
			/* Disabled. We must be able to click on button 'cancel'. Check must be done only on button 'save'.
			$("input[name='np_marginRate']:first").blur(function(e) {
				return checkEditLine(e, "np_marginRate");
			});*/
		<?php
		}
		if (! empty($conf->global->DISPLAY_MARK_RATES))
		{
		?>
			$('#savelinebutton').click(function (e) {
				return checkEditLine(e, "np_markRate");
			});
			/* Disabled. We must be able to click on button 'cancel'. Check must be done only on button 'save'.
			$("input[name='np_markRate']:first").blur(function(e) {
				return checkEditLine(e, "np_markRate");
			});*/
		<?php
		}
	?>
	});


	/* If margin rate field empty, do nothing. */
	/* Force content of price_ht to 0 or if a discount is set, recalculate it from margin rate */
	function checkEditLine(e, npRate)
	{
		var buying_price = $("input[name='buying_price']:first");
		var remise = $("input[name='remise_percent']:first");

		var rate = $("input[name='"+npRate+"']:first");
		if (rate.val() == '' || (typeof rate.val()) == 'undefined' ) return true;

		if (! $.isNumeric(rate.val().replace(' ','').replace(',','.')))
		{
			alert('<?php echo $langs->transnoentitiesnoconv("rateMustBeNumeric"); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}
		if (npRate == "np_markRate" && rate.val() >= 100)
		{
			alert('<?php echo $langs->transnoentitiesnoconv("markRateShouldBeLesserThan100"); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}

		var price = 0;
		remisejs=price2numjs(remise.val());

		if (remisejs != 100)
		{
			bpjs=price2numjs(buying_price.val());
			ratejs=price2numjs(rate.val());
			/* console.log(npRate+" - "+bpjs+" - "+ratejs); */

			if (npRate == "np_marginRate")
				price = ((bpjs * (1 + (ratejs / 100))) / (1 - remisejs / 100));
			else if (npRate == "np_markRate")
			{
				if (ratejs != 100)
				{
					price = ((bpjs / (1 - (ratejs / 100))) / (1 - remisejs / 100));
				}
				else price=$("input[name='price_ht']:first").val();
			}
		}
		/* console.log("new price ht = "+price); */
		$("input[name='price_ht']:first").val(price);	// TODO Must use a function like php price to have here a formated value

		return true;
	}

	/* Function similar to price2num in PHP */
	function price2numjs(num)
	{
		if (num == '') return '';

		<?php
		$dec=','; $thousand=' ';
		if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec=$langs->transnoentitiesnoconv("SeparatorDecimal");
		if ($langs->transnoentitiesnoconv("SeparatorThousand")!= "SeparatorThousand") $thousand=$langs->transnoentitiesnoconv("SeparatorThousand");
		print "var dec='".$dec."'; var thousand='".$thousand."';\n";	// Set var in javascript
		?>

		var main_max_dec_shown = <?php echo $conf->global->MAIN_MAX_DECIMALS_SHOWN; ?>;
		var main_rounding_unit = <?php echo $conf->global->MAIN_MAX_DECIMALS_UNIT; ?>;
		var main_rounding_tot = <?php echo $conf->global->MAIN_MAX_DECIMALS_TOT; ?>;

		var amount = num.toString();

		// rounding for unit price
		var rounding = main_rounding_unit;
		var pos = amount.indexOf(dec);
		var decpart = '';
		if (pos >= 0) decpart = amount.substr(pos+1).replace('/0+$/i','');	// Remove 0 for decimal part
		var nbdec = decpart.length;
		if (nbdec > rounding) rounding = nbdec;
	    // If rounding higher than max shown
	    if (rounding > main_max_dec_shown) rounding = main_max_dec_shown;

		if (thousand != ',' && thousand != '.') amount=amount.replace(',','.');
		amount=amount.replace(' ','');			// To avoid spaces
		amount=amount.replace(thousand,'');		// Replace of thousand before replace of dec to avoid pb if thousand is .
		amount=amount.replace(dec,'.');

		return parseFloat(amount).toFixed(rounding);
	}

<?php
}
?>

</script>
<!-- END PHP TEMPLATE objectline_edit.tpl.php -->
