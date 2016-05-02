<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García		<marcosgdf@gmail.com>
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
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande')))
{
    $usemargins=1;
}

global $forceall, $senderissupplier, $inputalsopricewithtax;
if (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 3;	// Col total ht + col edit + col delete
if (in_array($object->element,array('propal', 'supplier_proposal','facture','invoice','commande','order','order_supplier','invoice_supplier'))) $colspan++;	// With this, there is a column move button
//print $object->element;
?>

<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->

<tr class="liste_titre nodrag nodrop">
	<td class="linecoldescription" <?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>
	<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span><?php // echo $langs->trans("FreeZone"); ?>
	</td>
	<?php if ($object->element == 'supplier_proposal') { ?>
		<td class="linecolrefsupplier" align="right"><span id="title_fourn_ref"><?php echo $langs->trans('SupplierProposalRefFourn'); ?></span></td>
	<?php } ?>
	<td class="linecolvat" align="right"><span id="title_vat"><?php echo $langs->trans('VAT'); ?></span></td>
	<td class="linecoluht" align="right"><span id="title_up_ht"><?php echo $langs->trans('PriceUHT'); ?></span></td>
	<?php if (! empty($inputalsopricewithtax)) { ?>
	<td class="linecoluttc" align="right"><span id="title_up_ttc"><?php echo $langs->trans('PriceUTTC'); ?></span></td>
	<?php } ?>
	<td class="linecolqty" align="right"><?php echo $langs->trans('Qty'); ?></td>
	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
		print '<td class="linecoluseunit" align="left">';
		print '<span id="title_units">';
		print $langs->trans('Unit');
		print '</span></td>';
	}
	?>
	<td class="linecoldiscount" align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
	<?php
	if ($this->situation_cycle_ref) {
		print '<td class="linecolcycleref" align="right">' . $langs->trans('Progress') . '</td>';
	}
	if (! empty($usemargins))
	{
		?>
		<td align="right" class="margininfos linecolmargin1">
		<?php
		if ($conf->global->MARGIN_TYPE == "1")
			echo $langs->trans('BuyingPrice');
		else
			echo $langs->trans('CostPrice');
		?>
		</td>
		<?php
		if ($user->rights->margins->creer && ! empty($conf->global->DISPLAY_MARGIN_RATES)) echo '<td align="right" class="margininfos linecolmargin2"><span class="np_marginRate">'.$langs->trans('MarginRate').'</span></td>';
		if ($user->rights->margins->creer && ! empty($conf->global->DISPLAY_MARK_RATES)) 	echo '<td align="right" class="margininfos linecolmargin2"><span class="np_markRate">'.$langs->trans('MarkRate').'</span></td>';
	}
	?>
	<td class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<tr <?php echo $bcnd[$var]; ?>>
<?php
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	$coldisplay=2; }
else {
	$coldisplay=0; }
?>

	<td class="nobottom linecoldescription"<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>

	<?php

	$forceall=1;	// We always force all type for free lines (module product or service means we use predefined product or service)
	if ($object->element == 'contrat')
	{
		if (empty($conf->product->enabled) && empty($conf->service->enabled) && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $forceall=-1;	// With contract, by default, no choice at all, except if CONTRACT_SUPPORT_PRODUCTS is set
		else $forceall=0;
	}

	// Free line
	echo '<span class="prod_entry_mode_free">';
	// Show radio free line
	if ($forceall >= 0 && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))
	{
		echo '<label for="prod_entry_mode_free">';
		echo '<input type="radio" class="prod_entry_mode_free" name="prod_entry_mode" id="prod_entry_mode_free" value="free"';
		//echo (GETPOST('prod_entry_mode')=='free' ? ' checked' : ((empty($forceall) && (empty($conf->product->enabled) || empty($conf->service->enabled)))?' checked':'') );
		echo (GETPOST('prod_entry_mode')=='free' ? ' checked' : '');
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
		    if (empty($conf->product->enabled) || empty($conf->service->enabled)) echo $langs->trans("Type");
			else echo $langs->trans("FreeLineOfType");
			echo ' ';
		}
	}

	echo $form->select_type_of_lines(isset($_POST["type"])?$_POST["type"]:-1,'type',1,1,$forceall);

	echo '</span>';

	// Predefined product/service
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		if ($forceall >= 0) echo '<br>';
		echo '<span class="prod_entry_mode_predef">';
		echo '<label for="prod_entry_mode_predef">';
		echo '<input type="radio" class="prod_entry_mode_predef" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef"'.(GETPOST('prod_entry_mode')=='predef'?' checked':'').'> ';
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
		if (!empty($hookmanager->resPrint)) {
			print $hookmanager->resPrint;
		}
	}
	if (is_object($hookmanager) && ! empty($senderissupplier))
	{
		$parameters=array('htmlname'=>'addproduct');
		$reshook=$hookmanager->executeHooks('formCreateProductSupplierOptions',$parameters,$object,$action);
		if (!empty($hookmanager->resPrint)) {
			print $hookmanager->resPrint;
		}
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

	<?php if ($object->element == 'supplier_proposal') { ?>
		<td class="nobottom linecolresupplier" align="right"><input id="fourn_ref" name="fourn_ref" class="flat" value="" size="12"></td>
	<?php } ?>

	<td class="nobottom linecolvat" align="right"><?php
	if ($seller->tva_assuj == "0") echo '<input type="hidden" name="tva_tx" id="tva_tx" value="0">'.vatrate(0, true);
	else echo $form->load_tva('tva_tx', (isset($_POST["tva_tx"])?$_POST["tva_tx"]:-1), $seller, $buyer, 0, 0, '', false, 1);
	?>
	</td>
	<td class="nobottom linecoluht" align="right">
	<input type="text" size="5" name="price_ht" id="price_ht" class="flat" value="<?php echo (isset($_POST["price_ht"])?$_POST["price_ht"]:''); ?>">
	</td>
	<?php if (! empty($inputalsopricewithtax)) { ?>
	<td class="nobottom linecoluttc" align="right">
	<input type="text" size="5" name="price_ttc" id="price_ttc" class="flat" value="<?php echo (isset($_POST["price_ttc"])?$_POST["price_ttc"]:''); ?>">
	</td>
	<?php } ?>
	<td class="nobottom linecolqty" align="right"><input type="text" size="2" name="qty" id="qty" class="flat" value="<?php echo (isset($_POST["qty"])?$_POST["qty"]:1); ?>">
	</td>
	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
		print '<td class="nobottom linecoluseunit" align="left">';
		print $form->selectUnits($line->fk_unit, "units");
		print '</td>';
	}
	?>
	<td class="nobottom nowrap linecoldiscount" align="right"><input type="text" size="1" name="remise_percent" id="remise_percent" class="flat" value="<?php echo (isset($_POST["remise_percent"])?$_POST["remise_percent"]:$buyer->remise_percent); ?>"><span class="hideonsmartphone">%</span></td>
	<?php
	if ($this->situation_cycle_ref) {
		$coldisplay++;
		print '<td class="nobottom nowrap" align="right"><input type="text" size="1" value="0" name="progress">%</td>';
	}
	if (! empty($usemargins))
	{
		?>
		<td align="right" class="nobottom margininfos linecolmargin">
			<!-- For predef product -->
			<?php if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) { ?>
			<select id="fournprice_predef" name="fournprice_predef" class="flat" data-role="none" style="display: none;"></select>
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
				echo '<td align="right" class="nobottom nowrap margininfos"><input type="text" size="2" id="np_marginRate" name="np_marginRate" value="'.(isset($_POST["np_marginRate"])?$_POST["np_marginRate"]:'').'"><span class="np_marginRate hideonsmartphone">%</span></td>';
				$coldisplay++;
			}
			if (! empty($conf->global->DISPLAY_MARK_RATES))
			{
				echo '<td align="right" class="nobottom nowrap margininfos"><input type="text" size="2" id="np_markRate" name="np_markRate" value="'.(isset($_POST["np_markRate"])?$_POST["np_markRate"]:'').'"><span class="np_markRate hideonsmartphone">%</span></td>';
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
	<td class="nobottom linecoledit" align="center" valign="middle" colspan="<?php echo $colspan; ?>">
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
		elseif ($this->table_element_line=='supplier_proposaldet') {
			$newline = new SupplierProposalLine($this->db);
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
	$colspan = 6;

	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
		$colspan++;
	}
	if ($this->situation_cycle_ref) {
		$colspan++;
	}
	// We add 1 if col total ttc
	if (!empty($inputalsopricewithtax)) {
		$colspan++;
	}
	if ($conf->global->PRODUCT_USE_UNITS) {
		$colspan++;
	}
	if (count($object->lines)) {
		//There will be an edit and a delete button
		$colspan += 2;

		// With this, there is a column move button ONLY if lines > 1
		if (in_array($object->element, array(
			'propal',
			'supplier_proposal',
			'facture',
			'invoice',
			'commande',
			'order',
			'order_supplier',
			'invoice_supplier'
		))) {
			$colspan++;
		}
	}

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
		echo $form->select_date($date_start,'date_start',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addproduct",1,0,1);
		echo ' '.$langs->trans('to').' ';
		echo $form->select_date($date_end,'date_end',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addproduct",1,0,1);
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
			$("input[name='np_marginRate']:first").blur(function(e) {
				return checkFreeLine(e, "np_marginRate");
			});
		<?php
		}
		if (! empty($conf->global->DISPLAY_MARK_RATES)) { ?>
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
		if (jQuery('#select_type').val() >= 0)
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
		if (jQuery('#select_type').val() == '0') jQuery('#trlinefordates').hide();
		else jQuery('#trlinefordates').show();
	});

	$("#prod_entry_mode_predef").on( "click", function() {
		console.log("click prod_entry_mode_predef");
		setforpredef();
		jQuery('#trlinefordates').show();
	});

	/* When changing predefined product, we reload list of supplier prices */
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
  		$("#fournprice_predef").find("option").remove();
		$("#fournprice_predef").hide();
		$("#buying_price").val("").show();
		/* Call post to load content of combo list fournprice_predef */
  		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php?bestpricefirst=1', { 'idprod': $(this).val() }, function(data) {
	    	if (data && data.length > 0)
	    	{
    	  		var options = '';
    	  		var defaultkey = '';
    	  		var defaultprice = '';
	      		var bestpricefound = 0;
	      		var i = 0;
	      		$(data).each(function() {
	      			if (this.id != 'pmpprice')
		      		{
		        		i++;
                        this.price = parseFloat(this.price);//fix this.price >0

			      		// If margin is calculated on best supplier price, we set it by defaut (but only if value is not 0)
		      			var defaultbuyprice = '<?php echo ((isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == '1')?'bestsupplierprice':''); ?>';	// We set here default value to use
			      		console.log(this.id+" "+this.price+" "+defaultbuyprice+" "+(this.price > 0));
		      			if (bestpricefound == 0 && this.price > 0 && 'bestsupplierprice' == defaultbuyprice) { defaultkey = this.id; defaultprice = this.price; bestpricefound=1; }	// bestpricefound is used to take the first price > 0
		      		}
	      			if (this.id == 'pmpprice')
	      			{
	      				// If margin is calculated on PMP, we set it by defaut (but only if value is not 0)
		      			var defaultbuyprice = '<?php echo ((isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == 'pmp')?'pmp':''); ?>';
			      		console.log(this.id+" "+this.price+" "+defaultbuyprice);
		      			if (this.price > 0 && 'pmp' == defaultbuyprice) { defaultkey = this.id; defaultprice = this.price; }
	      			}
	        		options += '<option value="'+this.id+'" price="'+this.price+'">'+this.label+'</option>';
	      		});
	      		options += '<option value="inputprice" price="'+defaultprice+'"><?php echo $langs->trans("InputPrice"); ?></option>';

	      		console.log("defaultkey="+defaultkey);

	      		$("#fournprice_predef").html(options).show();
	      		if (defaultkey != '')
				{
		      		$("#fournprice_predef").val(defaultkey);
		      	}

	      		/* At loading, no product are yet selected, so we hide field of buying_price */
	      		$("#buying_price").hide();

	      		/* Define default price at loading */
	      		var defaultprice = $("#fournprice_predef").find('option:selected').attr("price");
			    $("#buying_price").val(defaultprice);

	      		$("#fournprice_predef").change(function() {
		      		console.log("change on fournprice_predef");
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
  		if (jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val() > 0)
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
	jQuery("#prod_entry_mode_free").prop('checked',true);
	jQuery("#prod_entry_mode_predef").prop('checked',false);
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
	jQuery("#units, #title_units").show();
}
function setforpredef() {
	jQuery("#select_type").val(-1);
	jQuery("#prod_entry_mode_free").prop('checked',false);
	jQuery("#prod_entry_mode_predef").prop('checked',true);
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
	jQuery(".np_markRate").hide();	// May no exists
	jQuery("#units, #title_units").hide();
}

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
