<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */


$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','askpricesupplier','commande'))) $usemargins=1;

global $forceall, $senderissupplier, $inputalsopricewithtax;
if (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 3;	// Col total ht + col edit + col delete
if (in_array($object->element,array('propal', 'askpricesupplier','facture','invoice','commande','order','order_supplier','invoice_supplier'))) $colspan++;	// With this, there is a column move button
//print $object->element;
?>

<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->

<tr class="liste_titre nodrag nodrop">
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>
	<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span><?php // echo $langs->trans("FreeZone"); ?>
	</td>
	<?php if ($object->element == 'askpricesupplier') { ?>
		<td align="right"><span id="title_fourn_ref"><?php echo $langs->trans('AskPriceSupplierRefFourn'); ?></span></td>
	<?php } ?>
	<td align="right"><span id="title_vat"><?php echo $langs->trans('VAT'); ?></span></td>
	<td align="right"><span id="title_up_ht"><?php echo $langs->trans('PriceUHT'); ?></span></td>
	<?php if (! empty($inputalsopricewithtax)) { ?>
	<td align="right"><span id="title_up_ttc"><?php echo $langs->trans('PriceUTTC'); ?></span></td>
	<?php } ?>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
	<?php
	if ($this->situation_cycle_ref) {
		print '<td align="right">' . $langs->trans('Progress') . '</td>';
	}
	if (! empty($usemargins))
	{
		?>
		<td align="right" class="margininfos">
		<?php
		if ($conf->global->MARGIN_TYPE == "1")
			echo $langs->trans('BuyingPrice');
		else
			echo $langs->trans('CostPrice');
		?>
		</td>
		<?php
		if ($user->rights->margins->creer && ! empty($conf->global->DISPLAY_MARGIN_RATES)) echo '<td align="right" class="margininfos"><span class="np_marginRate">'.$langs->trans('MarginRate').'</span></td>';
		if ($user->rights->margins->creer && ! empty($conf->global->DISPLAY_MARK_RATES)) 	echo '<td align="right" class="margininfos"><span class="np_markRate">'.$langs->trans('MarkRate').'</span></td>';
	}
	?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<tr <?php echo $bcnd[$var]; ?>>
<?php
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	$coldisplay=2; }
else {
	$coldisplay=0; }
?>

	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>

	<?php

	$forceall=1;	// We always force all type for free lines (module product or service means we use predefined product or service)
	if ($object->element == 'contrat')
	{
		if (empty($conf->product->enabled) && empty($conf->service->enabled) && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $forceall=-1;	// With contract, by default, no choice at all, except if CONTRACT_SUPPORT_PRODUCTS is set
		else $forceall=0;
	}

	// Free line
	echo '<span>';
	// Show radio free line
	if ($forceall >= 0 && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))
	{
		echo '<label for="prod_entry_mode_free">';
		echo '<input type="radio" name="prod_entry_mode" id="prod_entry_mode_free" value="free"';
		//echo (GETPOST('prod_entry_mode')=='free' ? ' checked="true"' : ((empty($forceall) && (empty($conf->product->enabled) || empty($conf->service->enabled)))?' checked="true"':'') );
		echo (GETPOST('prod_entry_mode')=='free' ? ' checked="true"' : '');
		echo '> ';
		// Show type selector
		echo $langs->trans("FreeLineOfType");
		echo '</label>';
		echo ' ';
	}
	else
	{
		echo '<input type="hidden" id="prod_entry_mode_free" name="prod_entry_mode" value="free">';
		// Show type selector
		if ($forceall >= 0)
		{
			echo $langs->trans("FreeLineOfType");
			echo ' ';
		}
	}

	echo $form->select_type_of_lines(isset($_POST["type"])?$_POST["type"]:-1,'type',1,1,$forceall);

	echo '</span>';

	// Predefined product/service
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		if ($forceall >= 0) echo '<br>';
		echo '<span>';
		echo '<label for="prod_entry_mode_predef">';
		echo '<input type="radio" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef"'.(GETPOST('prod_entry_mode')=='predef'?' checked="true"':'').'> ';
		if (empty($senderissupplier))
		{
			if (! empty($conf->product->enabled) && empty($conf->service->enabled)) echo $langs->trans('PredefinedProductsToSell');
			else if (empty($conf->product->enabled) && ! empty($conf->service->enabled)) echo $langs->trans('PredefinedServicesToSell');
			else echo $langs->trans('PredefinedProductsAndServicesToSell');
		}
		else
		{
			if (! empty($conf->product->enabled) && empty($conf->service->enabled)) echo $langs->trans('PredefinedProductsToPurchase');
			else if (empty($conf->product->enabled) && ! empty($conf->service->enabled)) echo $langs->trans('PredefinedServicesToPurchase');
			else echo $langs->trans('PredefinedProductsAndServicesToPurchase');
		}
		echo '</label>';
		echo ' ';

		$filtertype='';
		if (! empty($object->element) && $object->element == 'contrat' && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $filtertype='1';

		if (empty($senderissupplier))
		{
			$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, 1, 2, '', 1, array(),$buyer->id);
		}
		else
		{
			$ajaxoptions=array(
					'update' => array('qty'=>'qty','remise_percent' => 'discount'),	// html id tags that will be edited with which ajax json response key
					'option_disabled' => 'addPredefinedProductButton',	// html id to disable once select is done
					'warning' => $langs->trans("NoPriceDefinedForThisSupplier") // translation of an error saved into var 'error'
			);
			$form->select_produits_fournisseurs($object->socid, GETPOST('idprodfournprice'), 'idprodfournprice', '', '', $ajaxoptions, 1);
		}
		echo '</span>';
	}

	if (is_object($hookmanager) && empty($senderissupplier))
	{
        $parameters=array('fk_parent_line'=>GETPOST('fk_parent_line','int'));
		$reshook=$hookmanager->executeHooks('formCreateProductOptions',$parameters,$object,$action);
	}
	if (is_object($hookmanager) && ! empty($senderissupplier))
	{
		$parameters=array('htmlname'=>'addproduct');
		$reshook=$hookmanager->executeHooks('formCreateProductSupplierOptions',$parameters,$object,$action);
	}


	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) echo '<br>';

	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$nbrows=ROWS_2;
	$enabled=(! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
	if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
	$toolbarname='dolibarr_details';
	if (! empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname='dolibarr_notes';
	$doleditor=new DolEditor('dp_desc',GETPOST('dp_desc'),'',100,$toolbarname,'',false,true,$enabled,$nbrows,'98%');
	$doleditor->Create();
	?>
	</td>
	
	<?php if ($object->element == 'askpricesupplier') { ?>
		<td align="right"><input id="fourn_ref" name="fourn_ref" class="flat" value="" size="12"></td>
	<?php } ?>
	
	<td align="right"><?php
	if ($seller->tva_assuj == "0") echo '<input type="hidden" name="tva_tx" id="tva_tx" value="0">0';
	else echo $form->load_tva('tva_tx', (isset($_POST["tva_tx"])?$_POST["tva_tx"]:-1), $seller, $buyer);
	?>
	</td>
	<td align="right">
	<input type="text" size="5" name="price_ht" id="price_ht" class="flat" value="<?php echo (isset($_POST["price_ht"])?$_POST["price_ht"]:''); ?>">
	</td>
	<?php if (! empty($inputalsopricewithtax)) { ?>
	<td align="right">
	<input type="text" size="5" name="price_ttc" id="price_ttc" class="flat" value="<?php echo (isset($_POST["price_ttc"])?$_POST["price_ttc"]:''); ?>">
	</td>
	<?php } ?>
	<td align="right"><input type="text" size="2" name="qty" id="qty" class="flat" value="<?php echo (isset($_POST["qty"])?$_POST["qty"]:1); ?>">
	</td>
	<td align="right" class="nowrap"><input type="text" size="1" name="remise_percent" id="remise_percent" class="flat" value="<?php echo (isset($_POST["remise_percent"])?$_POST["remise_percent"]:$buyer->remise_percent); ?>"><span class="hideonsmartphone">%</span></td>
	<?php
	if ($this->situation_cycle_ref) {
		$coldisplay++;
		print '<td align="right" class="nowrap"><input type="text" size="1" value="0" name="progress">%</td>';
	}
	if (! empty($usemargins))
	{
		?>
		<td align="right" class="margininfos">
			<!-- For predef product -->
			<?php if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) { ?>
			<select id="fournprice_predef" name="fournprice_predef" class="flat" style="display: none;"></select>
			<?php } ?>
			<!-- For free product -->
			<input type="text" size="5" id="buying_price" name="buying_price" class="flat" value="<?php echo (isset($_POST["buying_price"])?$_POST["buying_price"]:''); ?>">
		</td>
		<?php

		$coldisplay++;
		if ($user->rights->margins->creer)
		{
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
			{
				echo '<td align="right" class="nowrap margininfos"><input type="text" size="2" id="np_marginRate" name="np_marginRate" value="'.(isset($_POST["np_marginRate"])?$_POST["np_marginRate"]:'').'"><span class="np_marginRate hideonsmartphone">%</span></td>';
				$coldisplay++;
			}
			if (! empty($conf->global->DISPLAY_MARK_RATES))
			{
				echo '<td align="right" class="nowrap margininfos"><input type="text" size="2" id="np_markRate" name="np_markRate" value="'.(isset($_POST["np_markRate"])?$_POST["np_markRate"]:'').'"><span class="np_markRate hideonsmartphone">%</span></td>';
				$coldisplay++;
			}
		}
		else
		{
			if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $coldisplay++;
			if (! empty($conf->global->DISPLAY_MARK_RATES))   $coldisplay++;
		}
	}
	?>
	<td align="center" valign="middle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
	<?php
	// Lines for extrafield
	if (!empty($extrafieldsline))
	{
		if ($this->table_element_line=='commandedet') {
			$newline = new OrderLine($this->db);
		}
		elseif ($this->table_element_line=='propaldet') {
			$newline = new PropaleLigne($this->db);
		}
		elseif ($this->table_element_line=='askpricesupplierdet') {
			$newline = new AskPriceSupplierLine($this->db);
		}
		elseif ($this->table_element_line=='facturedet') {
			$newline = new FactureLigne($this->db);
		}
		elseif ($this->table_element_line=='contratdet') {
			$newline = new ContratLigne($this->db);
		}
		elseif ($this->table_element_line=='commande_fournisseurdet') {
			$newline = new CommandeFournisseurLigne($this->db);
		}
		elseif ($this->table_element_line=='facture_fourn_det') {
			$newline = new SupplierInvoiceLine($this->db);
		}
		if (is_object($newline)) {
			print $newline->showOptionals($extrafieldsline, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay+8));
		}
	}
	?>
</tr>

<?php
if ((! empty($conf->service->enabled) || ($object->element == 'contrat')) && $dateSelector && GETPOST('type') != '0')	// We show date field if required
{
	if(! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) $colspan = 10;
	else $colspan = 9;
	if($this->situation_cycle_ref) $colspan++;
	if (! empty($inputalsopricewithtax)) $colspan++;	// We add 1 if col total ttc
	if (in_array($object->element,array('propal','askpricesupplier','facture','invoice','commande','order','order_supplier','invoice_supplier'))) $colspan++;	// With this, there is a column move button

	if (! empty($usemargins))
	{
		$colspan++; // For the buying price
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
		if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
	}
	?>

	<tr id="trlinefordates" <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	$date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	$date_end=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
	if (! empty($object->element) && $object->element == 'contrat')
	{
		print $langs->trans("DateStartPlanned").' ';
		$form->select_date($date_start,"date_start",$usehm,$usehm,1,"addproduct");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		$form->select_date($date_end,"date_end",$usehm,$usehm,1,"addproduct");
	}
	else
	{
		echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		echo $form->select_date($date_start,'date_start',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addproduct");
		echo ' '.$langs->trans('to').' ';
		echo $form->select_date($date_end,'date_end',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addproduct");
	}
	?>
	</td>
	</tr>
<?php
}
?>

<script type="text/javascript">

<?php
if (! empty($usemargins) && $user->rights->margins->creer)
{
?>

	/* Some js test when we click on button "Add" */
	jQuery(document).ready(function() {
		<?php
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) { ?>
			$('#addline').click(function (e) {
				return checkFreeLine(e, "np_marginRate");
			});
			$("input[name='np_marginRate']:first").blur(function(e) {
				return checkFreeLine(e, "np_marginRate");
			});
		<?php
		}
		if (! empty($conf->global->DISPLAY_MARK_RATES)) { ?>
			$('#addline').click(function (e) {
				return checkFreeLine(e, "np_markRate");
			});
			$("input[name='np_markRate']:first").blur(function(e) {
				return checkFreeLine(e, "np_markRate");
			});
		<?php
		}
		?>
	});

	/* TODO This does not work for number with thousand separator that is , */
	function checkFreeLine(e, npRate)
	{
		var buying_price = $("input[name='buying_price']:first");
		var remise = $("input[name='remise_percent']:first");

		var rate = $("input[name='"+npRate+"']:first");
		if (rate.val() == '')
			return true;

		if (! $.isNumeric(rate.val().replace(',','.')))
		{
			alert('<?php echo dol_escape_js($langs->trans("rateMustBeNumeric")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}
		if (npRate == "np_markRate" && rate.val() >= 100)
		{
			alert('<?php echo dol_escape_js($langs->trans("markRateShouldBeLesserThan100")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}

		var price = 0;
		remisejs=price2numjs(remise.val());

		if (remisejs != 100)	// If a discount not 100 or no discount
		{
			if (remisejs == '') remisejs=0;

			bpjs=price2numjs(buying_price.val());
			ratejs=price2numjs(rate.val());

			if (npRate == "np_marginRate")
				price = ((bpjs * (1 + ratejs / 100)) / (1 - remisejs / 100));
			else if (npRate == "np_markRate")
				price = ((bpjs / (1 - ratejs / 100)) / (1 - remisejs / 100));
		}
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

<?php
}
?>

/* JQuery for product free or predefined select */
jQuery(document).ready(function() {
	$("#prod_entry_mode_free").on( "click", function() {
		setforfree();
	});
	$("#select_type").change(function()
	{
		setforfree();
		if (jQuery('#select_type').val() >= 0) jQuery('#dp_desc').focus();
		if (jQuery('#select_type').val() == '0') jQuery('#trlinefordates').hide();
		else jQuery('#trlinefordates').show();
	});

	$("#prod_entry_mode_predef").on( "click", function() {
		setforpredef();
		jQuery('#trlinefordates').show();
	});

	/* Sur changemenr de produit, on recharge la liste des prix fournisseur */
	$("#idprod, #idprodfournprice").change(function()
	{
		setforpredef();
		jQuery('#trlinefordates').show();

		<?php
		if (! empty($usemargins) && $user->rights->margins->creer)
		{
			$langs->load('stocks');
			?>

		/* Code for margin */
  		$("#fournprice_predef options").remove();
		$("#fournprice_predef").hide();
		$("#buying_price").val("").show();
		/* Call post to load content of combo list fournprice_predef */
  		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', { 'idprod': $(this).val() }, function(data) {
	    	if (data && data.length > 0)
	    	{
    	  		var options = '';
    	  		var defaultkey = '';
    	  		var defaultprice = '';
	      		var i = 0;
	      		$(data).each(function() {
	      			if (this.id != 'pmpprice')
		      		{
		        		i++;
		        		options += '<option value="'+this.id+'" price="'+this.price+'"';
		        		if (this.price > 0 && i == 1) { defaultkey = this.id; defaultprice = this.price; }
		        		options += '>'+this.label+'</option>';
		      		}
	      			if (this.id == 'pmpprice')
	      			{
		      			var defaultbuyprice = <?php echo (isset($conf->global->MARGIN_PMP_AS_DEFAULT_BUY_PRICE)?int($conf->global->MARGIN_PMP_AS_DEFAULT_BUY_PRICE):1); ?>;
		      			if (this.price > 0 && 1 == defaultbuyprice) { defaultkey = this.id; defaultprice = this.price; }
	    	      		options += '<option value="'+this.id+'" price="'+this.price+'">'+this.label+'</option>';
	      			}
	      		});
	      		options += '<option value="inputprice" price="'+defaultprice+'"><?php echo $langs->trans("InputPrice"); ?></option>';

				/* alert(defaultkey+' '+defaultprice); */
	      		$("#fournprice_predef").html(options).show();
	      		$("#fournprice_predef").val(defaultkey);

	      		/* At loading, no product are yet selected, so we hide field of buying_price */
	      		$("#buying_price").hide();

	      		/* Define default price at loading */
	      		var defaultprice = $("#fournprice_predef").find('option:selected').attr("price");
	      		$("#buying_price").val(defaultprice);

	      		$("#fournprice_predef").change(function() {
	      			/* Hide field buying_price according to choice into list (if 'inputprice' or not) */
					var linevalue=$(this).find('option:selected').val();
	        		var pricevalue = $(this).find('option:selected').attr("price");
	        		if (linevalue != 'inputprice' && linevalue != 'pmpprice') {
	          			$("#buying_price").val(pricevalue).hide();	/* We set value then hide field */
	        		}
	        		if (linevalue == 'inputprice') {
		          		$('#buying_price').show();
	        		}
	        		if (linevalue == 'pmpprice') {
	        			$("#buying_price").val(pricevalue);
		          		$('#buying_price').hide();
	        		}
				});
	    	}
	  	},
	  	'json');

  		<?php } ?>

  		/* To set focus */
  		if (jQuery('#idprod').val() > 0) jQuery('#dp_desc').focus();
		if (jQuery('#idprodfournprice').val() > 0) jQuery('#dp_desc').focus();
	});

	<?php if (GETPOST('prod_entry_mode') == 'predef') { // When we submit with a predef product and it fails we must start with predef ?>
		setforpredef();
	<?php } ?>

});

/* Function to set fields from choice */
function setforfree() {
	jQuery("#search_idprod").val('');
	jQuery("#idprod").val('');
	jQuery("#idprodfournprice").val('0');	// Set cursor on not selected product
	jQuery("#search_idprodfournprice").val('');
	jQuery("#prod_entry_mode_free").attr('checked',true);
	jQuery("#prod_entry_mode_predef").attr('checked',false);
	jQuery("#price_ht").show();
	jQuery("#price_ttc").show();	// May no exists
	jQuery("#tva_tx").show();
	jQuery("#buying_price").val('').show();
	jQuery("#fournprice_predef").hide();
	jQuery("#title_vat").show();
	jQuery("#title_up_ht").show();
	jQuery("#title_up_ttc").show();
	jQuery("#np_marginRate").show();	// May no exists
	jQuery("#np_markRate").show();	// May no exists
	jQuery(".np_marginRate").show();	// May no exists
	jQuery(".np_markRate").show();	// May no exists
}
function setforpredef() {
	jQuery("#select_type").val(-1);
	jQuery("#prod_entry_mode_free").attr('checked',false);
	jQuery("#prod_entry_mode_predef").attr('checked',true);
	jQuery("#price_ht").hide();
	jQuery("#title_up_ht").hide();
	jQuery("#price_ttc").hide();	// May no exists
	jQuery("#tva_tx").hide();
	jQuery("#buying_price").show();
	jQuery("#title_vat").hide();
	jQuery("#title_up_ttc").hide();
	jQuery("#np_marginRate").hide();	// May no exists
	jQuery("#np_markRate").hide();	// May no exists
	jQuery(".np_marginRate").hide();	// May no exists
	jQuery(".np_markRate").hide();	// May no exists
}

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
