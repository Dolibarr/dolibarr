<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
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
 * $conf
 * $langs
 * $dateSelector
 * $this (invoice, order, ...)
 * $line defined
 */

$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande'))) $usemargins=1;

?>

<!-- BEGIN PHP TEMPLATE predefinedproductline_create.tpl.php -->

<tr class="liste_titre nodrag nodrop">
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="4"' : ' colspan="3"'); ?>>
	<?php
	echo '<span class="hideonsmartphone">'.$langs->trans("AddNewLine").' - </span>';
	if (! empty($conf->product->enabled) && empty($conf->service->enabled)) echo $langs->trans('RecordedProducts');
	else if (empty($conf->product->enabled) && ! empty($conf->service->enabled)) echo $langs->trans('RecordedServices');
	else echo $langs->trans('RecordedProductsAndServices');
	?>
	</td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
	<?php
	$colspan = 4;
	if (! empty($usemargins))
	{
		?>
		<td align="right">
		<?php
		if ($conf->global->MARGIN_TYPE == "1")
			echo $langs->trans('BuyingPrice');
		else
			echo $langs->trans('CostPrice');
		?>
		</td>
		<?php
		if ($user->rights->margins->creer)
		{
			if(! empty($conf->global->DISPLAY_MARGIN_RATES))
			{
				echo '<td align="right">'.$langs->trans('MarginRate').'</td>';
			}
			if(! empty($conf->global->DISPLAY_MARK_RATES))
			{
				echo '<td align="right">'.$langs->trans('MarkRate').'</td>';
			}
		}
		else
		{
			if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
			if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
		}
	}
	?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<tr <?php echo $bcnd[$var]; ?>>
<?php
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	$coldisplay=4; }
else {
	$coldisplay=3; }
?>

	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="4"' : ' colspan="3"'); ?>>

	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#idprod').change(function() {
			  if (jQuery('#idprod').val() > 0) jQuery('#np_desc').focus();
		});
	});
	</script>

	<?php

	echo '<span>';
	$filtertype='';
	if (! empty($object->element) && $object->element == 'contrat') $filtertype='1';
	$form->select_produits('','idprod',$filtertype,$conf->product->limit_size,$buyer->price_level);
	echo '</span>';

	if (is_object($hookmanager))
	{
        $parameters=array('fk_parent_line'=>GETPOST('fk_parent_line','int'));
	    $reshook=$hookmanager->executeHooks('formCreateProductOptions',$parameters,$object,$action);
	}

	echo '<br>';

	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $nbrows=ROWS_2;
    $enabled=(! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    $doleditor=new DolEditor('np_desc',GETPOST('np_desc"'),'',100,'dolibarr_details','',false,true,$enabled,$nbrows,70);
	$doleditor->Create();
	?>
	</td>

	<td align="right"><input type="hidden" name="price_ht_predef"><input type="text" size="2" name="qty_predef" class="flat" value="1"></td>
	<td align="right" class="nowrap"><input type="text" size="1" class="flat" name="remise_percent_predef" value="<?php echo $buyer->remise_percent; ?>"><span class="hideonsmartphone">%</span></td>
	<?php
	$colspan = 4;
	if (! empty($usemargins))
	{
		?>
		<td align="right">
			<select id="fournprice_predef" name="fournprice_predef" class="flat" style="display: none;"></select>
			<input type="text" size="5" id="buying_price_predef" name="buying_price_predef" class="flat" value="<?php echo (isset($_POST["buying_price_predef"])?$_POST["buying_price_predef"]:''); ?>">
		</td>
		<?php
		$colspan++;
		$coldisplay++;

		if ($user->rights->margins->creer)
		{
			if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
				echo '<td align="right"><input type="text" size="2" name="np_marginRate_predef" value="'.(isset($_POST["np_marginRate_predef"])?$_POST["np_marginRate_predef"]:'').'">%</td>';
				$colspan++;
				$coldisplay++;
			}
			if (! empty($conf->global->DISPLAY_MARK_RATES)) {
				echo '<td align="right"><input type="text" size="2" name="np_markRate_predef" value="'.(isset($_POST["np_markRate_predef"])?$_POST["np_markRate_predef"]:'').'">%</td>';
				$colspan++;
				$coldisplay++;
			}
		}
		else
		{
			if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
				$colspan++;
				$coldisplay++;
			}
			if (! empty($conf->global->DISPLAY_MARK_RATES)) {
				$colspan++;
				$coldisplay++;
			}
		}
	}
	?>
	<td align="center" valign="middle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addline_predefined" id="addline_predefined">
	</td>
	<?php
	//Line extrafield
	if (!empty($extrafieldsline)) {
		if ($this->table_element_line=='commandedet') {
			$newline = new OrderLine($this->db);
		}
		elseif ($this->table_element_line=='propaldet') {
			$newline = new PropaleLigne($this->db);
		}
		elseif ($this->table_element_line=='facturedet') {
			$newline = new FactureLigne($this->db);
		}
		if (is_object($newline)) {
			print $newline->showOptionals($extrafieldsline, 'edit', array('style'=>$bcnd[$var],'colspan'=>$coldisplay+5), '_predef');
		}
	}
	?>
</tr>

<?php
if (! empty($conf->service->enabled) && $dateSelector)
{
	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) $colspan = 10;
	else $colspan = 9;
	if (! empty($usemargins))
	{
		$colspan++; // For the buying price
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
		if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
	}
?>

<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	if (! empty($object->element) && $object->element == 'contrat')
	{
		print $langs->trans("DateStartPlanned").' ';
		$form->select_date('',"date_start_predef",$usehm,$usehm,1,"addline");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		$form->select_date('',"date_end_predef",$usehm,$usehm,1,"addline");
	}
	else
	{
		echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		echo $form->select_date('','date_start_predef',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addpredefinedproduct");
		echo ' '.$langs->trans('to').' ';
		echo $form->select_date('','date_end_predef',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addpredefinedproduct");
	}
	?>
	</td>
</tr>
<?php
}
?>


<?php
if (! empty($usemargins) && $user->rights->margins->creer)
{
?>
	<script type="text/javascript">

	jQuery(document).ready(function() {
		<?php
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) { ?>
			$('#addline_predefined').click(function (e) {
				return checkLine(e, "np_marginRate_predef");
			});
			$("input[name='np_marginRate_predef']:last").blur(function(e) {
				return checkLine(e, "np_marginRate_predef");
			});
		<?php
		}
		if (! empty($conf->global->DISPLAY_MARK_RATES)) {
		?>
			$('#addline_predefined').click(function (e) {
				return checkLine(e, "np_markRate_predef");
			});
			$("input[name='np_marginRate_predef']:last").blur(function(e) {
				return checkLine(e, "np_markRate_predef");
			});
		<?php
		}
		?>
	});

	// TODO This works for french numbers only
	function checkLine(e, npRate)
	{
		var buying_price = $("input[name='buying_price_predef']:last");
		var remise = $("input[name='remise_percent_predef']:last");

		var rate = $("input[name='"+npRate+"']:last");
		if (rate.val() == '')
			return true;
		if (! $.isNumeric(rate.val().replace(',','.')))
		{
			alert('<?php echo dol_escape_js($langs->trans("rateMustBeNumeric")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}
		if (npRate == "np_markRate_predef" && rate.val() >= 100)
		{
			alert('<?php echo dol_escape_js($langs->trans("markRateShouldBeLesserThan100")); ?>');
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

			if (npRate == "np_marginRate_predef")
				price = ((bpjs * (1 + ratejs / 100)) / (1 - remisejs / 100));
			else if (npRate == "np_markRate_predef")
				price = ((bpjs / (1 - ratejs / 100)) / (1 - remisejs / 100));
		}
		$("input[name='price_ht_predef']:last").val(price);	// TODO Must use a function like php price to have here a formated value

		return true;
	}

	/* Function similar to price2num in PHP */
	function price2numjs(num)
	{
		<?php
		$dec=','; $thousand=' ';
		if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec=$langs->transnoentitiesnoconv("SeparatorDecimal");
		if ($langs->transnoentitiesnoconv("SeparatorThousand")!= "SeparatorThousand") $thousand=$langs->transnoentitiesnoconv("SeparatorThousand");
		if ($thousand == 'None') $thousand='';
		print "var dec='".$dec."'; var thousand='".$thousand."';\n";
		?>

		var main_max_dec_shown = <?php echo $conf->global->MAIN_MAX_DECIMALS_SHOWN; ?>;
		var main_rounding_unit = <?php echo $conf->global->MAIN_MAX_DECIMALS_UNIT; ?>;
		var main_rounding_tot = <?php echo $conf->global->MAIN_MAX_DECIMALS_TOT; ?>;

		var amount = num.toString();

		// rounding for unit price
		var rounding = main_rounding_unit;
		var pos = amount.indexOf(dec);
		var decpart = '';
		if (pos >= 0) decpart = amount.substr(pos+1).replace('/0+$/i','');	// Supprime les 0 de fin de partie decimale
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

	jQuery(document).ready(function() {
		$("#idprod").change(function()
		{
	  		$("#fournprice_predef options").remove();
			$("#fournprice_predef").hide();
			$("#buying_price_predef").val("").show();
	  		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', { 'idprod': $(this).val() }, function(data) {
		    	if (data && data.length > 0)
		    	{
	    	  		var options = '';
		      		var i = 0;
		      		$(data).each(function() {
		        		i++;
		        		options += '<option value="'+this.id+'" price="'+this.price+'"';
		        		if (i == 1) {
		          			options += ' selected';
		          			$("#buying_price_predef").val(this.price);
		        		}
		        		options += '>'+this.label+'</option>';
		      		});
		      		options += '<option value=""><?php echo $langs->trans("InputPrice"); ?></option>';
		      		$("#buying_price_predef").hide();
		      		$("#fournprice_predef").html(options).show();
		      		$("#fournprice_predef").change(function() {
		        		var selval = $(this).find('option:selected').attr("price");
		        		if (selval)
		          			$("#buying_price_predef").val(selval).hide();
		        		else
		          			$('#buying_price_predef').show();
		      		});
		    	}
		  	},
		  	'json');
		});
	});

	</script>
<?php
}
?>
<!-- END PHP TEMPLATE predefinedproductline_create.tpl.php -->
