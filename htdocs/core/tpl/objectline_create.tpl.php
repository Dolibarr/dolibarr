<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande')))
{
    $usemargins=1;
}

if (! isset($dateSelector)) global $dateSelector;	// Take global var only if not already defined into function calling (for example formAddObjectLine)
global $forceall, $forcetoshowtitlelines, $senderissupplier, $inputalsopricewithtax;

if (! isset($dateSelector)) $dateSelector=1;    // For backward compatibility
elseif (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 3;	// Col total ht + col edit + col delete
if (in_array($object->element,array('propal','commande','order','facture','facturerec','invoice','supplier_proposal','order_supplier','invoice_supplier'))) $colspan++;	// With this, there is a column move button
//print $object->element;

// Lines for extrafield
$objectline = null;
if (!empty($extrafieldsline))
{
	if ($this->table_element_line=='commandedet') {
		$objectline = new OrderLine($this->db);
	}
	elseif ($this->table_element_line=='propaldet') {
		$objectline = new PropaleLigne($this->db);
	}
	elseif ($this->table_element_line=='supplier_proposaldet') {
		$objectline = new SupplierProposalLine($this->db);
	}
	elseif ($this->table_element_line=='facturedet') {
		$objectline = new FactureLigne($this->db);
	}
	elseif ($this->table_element_line=='contratdet') {
		$objectline = new ContratLigne($this->db);
	}
	elseif ($this->table_element_line=='commande_fournisseurdet') {
		$objectline = new CommandeFournisseurLigne($this->db);
	}
	elseif ($this->table_element_line=='facture_fourn_det') {
		$objectline = new SupplierInvoiceLine($this->db);
	}
	elseif ($this->table_element_line=='facturedet_rec') {
		$objectline = new FactureLigneRec($this->db);
	}
}

?>

<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->
<?php
$nolinesbefore=(count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
?>
<tr class="liste_titre<?php echo (($nolinesbefore || $object->element=='contrat')?'':' liste_titre_add_') ?> nodrag nodrop">
	<?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum" align="center"></td>
	<?php } ?>
	<td class="linecoldescription minwidth500imp">
	<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span><?php // echo $langs->trans("FreeZone"); ?>
	</td>
	<?php
	if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier')	// We must have same test in printObjectLines
	{
	?>
		<td class="linecolrefsupplier"><span id="title_fourn_ref"><?php echo $langs->trans('SupplierRef'); ?></span></td>
	<?php } ?>
	<td class="linecolvat" align="right"><span id="title_vat"><?php echo $langs->trans('VAT'); ?></span></td>
	<td class="linecoluht" align="right"><span id="title_up_ht"><?php echo $langs->trans('PriceUHT'); ?></span></td>
	<?php if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) { $colspan++;?>
	<td class="linecoluht_currency" align="right"><span id="title_up_ht_currency"><?php echo $langs->trans('PriceUHTCurrency'); ?></span></td>
	<?php } ?>
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
		if (!empty($user->rights->margins->creer)) {
		?>
		<td align="right" class="margininfos linecolmargin1">
		<?php
		}
		else $colspan++;

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
<?php
}
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo ($nolinesbefore || $object->element=='contrat')?'':' liste_titre_create'; ?>">
<?php
// Adds a line numbering column
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	$coldisplay=2;
	?>
	<td class="nobottom linecolnum" align="center" width="5"></td>
	<?php
}
else {
	$coldisplay=0;
}
?>

	<td class="nobottom linecoldescription minwidth500imp">

	<?php

	$freelines = false;
	if (empty($conf->global->MAIN_DISABLE_FREE_LINES))
	{
		$freelines = true;
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

		echo $form->select_type_of_lines(isset($_POST["type"])?GETPOST("type",'alpha',2):-1,'type',1,1,$forceall);

		echo '</span>';
	}

	// Predefined product/service
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		if ($forceall >= 0 && $freelines) echo '<br>';
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
			if (! empty($conf->global->ENTREPOT_EXTRA_STATUS))
			{
				// hide products in closed warehouse, but show products for internal transfer
				$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, 1, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth300', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'));
			}
			else
			{
				$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, 1, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth300', 0, '', GETPOST('combinations', 'array'));
			}
		}
		else
		{
			// $senderissupplier=2 is same than 1 but disable test on minimum qty and disable autofill qty with minimum
		    if ($senderissupplier != 2)
		    {
    			$ajaxoptions=array(
    					'update' => array('qty'=>'qty','remise_percent' => 'discount','idprod' => 'idprod'),	// html id tags that will be edited with each ajax json response key
    					'option_disabled' => 'idthatdoesnotexists',						// html id to disable once select is done
    					'warning' => $langs->trans("NoPriceDefinedForThisSupplier") 				// translation of an error saved into var 'warning' (for exemple shown we select a disabled option into combo)
    			);
    			$alsoproductwithnosupplierprice=0;
		    }
		    else
		    {
		        $ajaxoptions = array(
				'update' => array('remise_percent' => 'discount')				// html id tags that will be edited with each ajax json response key
			);
		        $alsoproductwithnosupplierprice=1;
		    }

		    $form->select_produits_fournisseurs($object->socid, GETPOST('idprodfournprice'), 'idprodfournprice', '', '', $ajaxoptions, 1, $alsoproductwithnosupplierprice, 'maxwidth300');
		}
		echo '<input type="hidden" name="pbq" id="pbq" value="">';
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


	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) {

		if (!empty($conf->variants->enabled)) {
			echo '<div id="attributes_box"></div>';
		}

		echo '<br>';
	}

	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$nbrows=ROWS_2;
	$enabled=(! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
	if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
	$toolbarname='dolibarr_details';
	if (! empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname='dolibarr_notes';
	$doleditor=new DolEditor('dp_desc',GETPOST('dp_desc'),'',100,$toolbarname,'',false,true,$enabled,$nbrows,'98%');
	$doleditor->Create();

	// Show autofill date for recuring invoices
	if (! empty($conf->service->enabled) && $object->element == 'facturerec')
	{
		echo '<div class="divlinefordates"><br>';
		echo $langs->trans('AutoFillDateFrom').' ';
		echo $form->selectyesno('date_start_fill', $line->date_start_fill, 1);
		echo ' - ';
		echo $langs->trans('AutoFillDateTo').' ';
		echo $form->selectyesno('date_end_fill', $line->date_end_fill, 1);
		echo '</div>';
	}
	?>
	</td>

	<?php
	if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier')	// We must have same test in printObjectLines
	{
	?>
		<td class="nobottom linecolresupplier"><input id="fourn_ref" name="fourn_ref" class="flat maxwidth75" value="<?php echo (isset($_POST["fourn_ref"])?GETPOST("fourn_ref",'alpha',2):''); ?>"></td>
	<?php } ?>

	<td class="nobottom linecolvat" align="right"><?php
	if ($seller->tva_assuj == "0") echo '<input type="hidden" name="tva_tx" id="tva_tx" value="0">'.vatrate(0, true);
	else echo $form->load_tva('tva_tx', (isset($_POST["tva_tx"])?GETPOST("tva_tx",'alpha',2):-1), $seller, $buyer, 0, 0, '', false, 1);
	?>
	</td>
	<td class="nobottom linecoluht" align="right">
	<input type="text" size="5" name="price_ht" id="price_ht" class="flat right" value="<?php echo (isset($_POST["price_ht"])?GETPOST("price_ht",'alpha',2):''); ?>">
	</td>

	<?php if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) { $colspan++;?>
	<td class="nobottom linecoluht_currency" align="right">
	<input type="text" size="5" name="multicurrency_price_ht" id="multicurrency_price_ht" class="flat right" value="<?php echo (isset($_POST["multicurrency_price_ht"])?GETPOST("multicurrency_price_ht",'alpha',2):''); ?>">
	</td>
	<?php } ?>

	<?php if (! empty($inputalsopricewithtax)) { ?>
	<td class="nobottom linecoluttc" align="right">
	<input type="text" size="5" name="price_ttc" id="price_ttc" class="flat" value="<?php echo (isset($_POST["price_ttc"])?GETPOST("price_ttc",'alpha',2):''); ?>">
	</td>
	<?php } ?>
	<td class="nobottom linecolqty" align="right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="<?php echo (isset($_POST["qty"])?GETPOST("qty",'alpha',2):1); ?>">
	</td>
	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
		print '<td class="nobottom linecoluseunit" align="left">';
		print $form->selectUnits($line->fk_unit, "units");
		print '</td>';
	}
	$remise_percent = $buyer->remise_percent;
	if($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier') {
		$remise_percent = $seller->remise_supplier_percent;
	}
	?>
	<td class="nobottom nowrap linecoldiscount" align="right"><input type="text" size="1" name="remise_percent" id="remise_percent" class="flat right" value="<?php echo (isset($_POST["remise_percent"])?GETPOST("remise_percent",'alpha',2):$remise_percent); ?>"><span class="hideonsmartphone">%</span></td>
	<?php
	if ($this->situation_cycle_ref) {
		$coldisplay++;
		print '<td class="nobottom nowrap" align="right"><input class="falt right" type="text" size="1" value="0" name="progress">%</td>';
	}
	if (! empty($usemargins))
	{
		if (!empty($user->rights->margins->creer)) {
		?>
		<td align="right" class="nobottom margininfos linecolmargin">
			<!-- For predef product -->
			<?php if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) { ?>
			<select id="fournprice_predef" name="fournprice_predef" class="flat" style="display: none;"></select>
			<?php } ?>
			<!-- For free product -->
			<input type="text" size="5" id="buying_price" name="buying_price" class="flat right" value="<?php echo (isset($_POST["buying_price"])?GETPOST("buying_price",'alpha',2):''); ?>">
		</td>
		<?php
		$coldisplay++;
		}

		if ($user->rights->margins->creer)
		{
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
			{
				echo '<td align="right" class="nobottom nowrap margininfos"><input class="flat right" type="text" size="2" id="np_marginRate" name="np_marginRate" value="'.(isset($_POST["np_marginRate"])?GETPOST("np_marginRate",'alpha',2):'').'"><span class="np_marginRate hideonsmartphone">%</span></td>';
				$coldisplay++;
			}
			if (! empty($conf->global->DISPLAY_MARK_RATES))
			{
				echo '<td align="right" class="nobottom nowrap margininfos"><input class="flat right" type="text" size="2" id="np_markRate" name="np_markRate" value="'.(isset($_POST["np_markRate"])?GETPOST("np_markRate",'alpha',2):'').'"><span class="np_markRate hideonsmartphone">%</span></td>';
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
</tr>

<?php
if (is_object($objectline)) {
	print $objectline->showOptionals($extrafieldsline, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay+8), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD)?0:1);
}
?>

<?php
if ((! empty($conf->service->enabled) || ($object->element == 'contrat')) && $dateSelector && GETPOST('type') != '0')	// We show date field if required
{
	$colspan = 6;

	if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier')	// We must have same test in printObjectLines
	{
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
		    'facturerec',
			'invoice',
			'commande',
			'order',
			'order_supplier',
			'invoice_supplier'
		))) {
			$colspan++;
		}
	}

	if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) $colspan+=2;

	if (! empty($usemargins))
	{
		if (!empty($user->rights->margins->creer)) $colspan++; // For the buying price
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
		if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
	}
	?>

	<tr id="trlinefordates" <?php echo $bcnd[$var]; ?>>
	<?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { print '<td></td>'; } ?>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	$date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	$date_end=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
	if (! empty($object->element) && $object->element == 'contrat')
	{
		print $langs->trans("DateStartPlanned").' ';
		print $form->selectDate($date_start,"date_start",$usehm,$usehm,1,"addproduct");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		print $form->selectDate($date_end,"date_end",$usehm,$usehm,1,"addproduct");
	}
	else
	{
		echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		print $form->selectDate($date_start, 'date_start', empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1, empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1, 1, "addproduct", 1, 0);
		echo ' '.$langs->trans('to').' ';
		print $form->selectDate($date_end, 'date_end', empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1, empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1, 1, "addproduct", 1, 0);
	};
	print '<script type="text/javascript">';
	if (!$date_start) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_HOUR)) {
			print 'jQuery("#date_starthour").val("'.$conf->global->MAIN_DEFAULT_DATE_START_HOUR.'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_MIN)) {
			print 'jQuery("#date_startmin").val("'.$conf->global->MAIN_DEFAULT_DATE_START_MIN.'");';
		}
	}
	if (!$date_end) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_HOUR)) {
			print 'jQuery("#date_endhour").val("'.$conf->global->MAIN_DEFAULT_DATE_END_HOUR.'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_MIN)) {
			print 'jQuery("#date_endmin").val("'.$conf->global->MAIN_DEFAULT_DATE_END_MIN.'");';
		}
	}
	print '</script>'
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
		console.log("Hide/show date according to product type");
		if (jQuery('#select_type').val() == '0')
		{
			jQuery('#trlinefordates').hide();
			jQuery('.divlinefordates').hide();
		}
		else
		{
			jQuery('#trlinefordates').show();
			jQuery('.divlinefordates').show();
		}
	});

	$("#prod_entry_mode_predef").on( "click", function() {
		console.log("click prod_entry_mode_predef");
		setforpredef();
		jQuery('#trlinefordates').show();
	});

	<?php
	if(!$freelines) { ?>
		$("#prod_entry_mode_predef").click();
	<?php
	}
	?>

	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod, #idprodfournprice").change(function()
	{
		console.log("#idprod, #idprodfournprice change triggered");

		setforpredef();		// TODO Keep vat combo visible and set it to first entry into list that match result of get_default_tva

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

    	      		var bestpriceid = 0; var bestpricevalue = 0;
    	      		var pmppriceid = 0; var pmppricevalue = 0;
    	      		var costpriceid = 0; var costpricevalue = 0;

    				/* setup of margin calculation */
    	      		var defaultbuyprice = '<?php
    	      		if (isset($conf->global->MARGIN_TYPE))
    	      		{
    	      		    if ($conf->global->MARGIN_TYPE == '1')   print 'bestsupplierprice';
    	      		    if ($conf->global->MARGIN_TYPE == 'pmp') print 'pmp';
    	      		    if ($conf->global->MARGIN_TYPE == 'costprice') print 'costprice';
    	      		} ?>';
    	      		console.log("we will set the field for margin. defaultbuyprice="+defaultbuyprice);

    	      		var i = 0;
    	      		$(data).each(function() {
    	      			if (this.id != 'pmpprice' && this.id != 'costprice')
    		      		{
    		        		i++;
                            this.price = parseFloat(this.price); // to fix when this.price >0
    			      		// If margin is calculated on best supplier price, we set it by defaut (but only if value is not 0)
    			      		//console.log("id="+this.id+"-price="+this.price+"-"+(this.price > 0));
    		      			if (bestpricefound == 0 && this.price > 0) { defaultkey = this.id; defaultprice = this.price; bestpriceid = this.id; bestpricevalue = this.price; bestpricefound=1; }	// bestpricefound is used to take the first price > 0
    		      		}
    	      			if (this.id == 'pmpprice')
    	      			{
    	      				// If margin is calculated on PMP, we set it by defaut (but only if value is not 0)
    			      		//console.log("id="+this.id+"-price="+this.price);
    			      		if ('pmp' == defaultbuyprice || 'costprice' == defaultbuyprice)
    			      		{
    			      			if (this.price > 0) {
    				      			defaultkey = this.id; defaultprice = this.price; pmppriceid = this.id; pmppricevalue = this.price;
    			      				//console.log("pmppricevalue="+pmppricevalue);
    			      			}
    			      		}
    	      			}
    	      			if (this.id == 'costprice')
    	      			{
    	      				// If margin is calculated on Cost price, we set it by defaut (but only if value is not 0)
    			      		//console.log("id="+this.id+"-price="+this.price+"-pmppricevalue="+pmppricevalue);
    			      		if ('costprice' == defaultbuyprice)
    			      		{
    		      				if (this.price > 0) { defaultkey = this.id; defaultprice = this.price; costpriceid = this.id; costpricevalue = this.price; }
    		      				else if (pmppricevalue > 0) { defaultkey = pmppriceid; defaultprice = pmppricevalue; }
    			      		}
    	      			}
    	        		options += '<option value="'+this.id+'" price="'+this.price+'">'+this.label+'</option>';
    	      		});
    	      		options += '<option value="inputprice" price="'+defaultprice+'"><?php echo $langs->trans("InputPrice"); ?></option>';

    	      		console.log("finally selected defaultkey="+defaultkey+" defaultprice="+defaultprice);

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

  		<?php
        }
        ?>

        /* To process customer price per quantity */
        var pbq = parseInt($('option:selected', this).attr('data-pbq'));
        var pbqqty = parseFloat($('option:selected', this).attr('data-pbqqty'));
        var pbqpercent = parseFloat($('option:selected', this).attr('data-pbqpercent'));

        if ((jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val()) && typeof pbq !== "undefined")
        {
            console.log("We choose a price by quanty price_by_qty id = "+pbq+" price_by_qty qty = "+pbqqty+" price_by_qty percent = "+pbqpercent);
            jQuery("#pbq").val(pbq);
            if (jQuery("#qty").val() < pbqqty)
            {
                    jQuery("#qty").val(pbqqty);
            }
            if (jQuery("#remise_percent").val() < pbqpercent)
            {
                    jQuery("#remise_percent").val(pbqpercent);
            }
        }
        else
        {
            jQuery("#pbq").val('');
        }

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
	console.log("Call setforfree. We show most fields");
	jQuery("#search_idprod").val('');
	jQuery("#idprod").val('');
	jQuery("#idprodfournprice").val('0');	// Set cursor on not selected product
	jQuery("#search_idprodfournprice").val('');
	jQuery("#prod_entry_mode_free").prop('checked',true).change();
	jQuery("#prod_entry_mode_predef").prop('checked',false).change();
	jQuery("#price_ht").show();
	jQuery("#multicurrency_price_ht").show();
	jQuery("#price_ttc").show();	// May no exists
	jQuery("#fourn_ref").show();
	jQuery("#tva_tx").show();
	jQuery("#buying_price").val('').show();
	jQuery("#fournprice_predef").hide();
	jQuery("#title_vat").show();
	jQuery("#title_up_ht").show();
	jQuery("#title_up_ht_currency").show();
	jQuery("#title_up_ttc").show();
	jQuery("#title_up_ttc_currency").show();
	jQuery("#np_marginRate").show();	// May no exists
	jQuery("#np_markRate").show();	// May no exists
	jQuery(".np_marginRate").show();	// May no exists
	jQuery(".np_markRate").show();	// May no exists
	jQuery("#units, #title_units").show();
}
function setforpredef() {
	console.log("Call setforpredef. We hide some fields and show dates");
	jQuery("#select_type").val(-1);

	jQuery("#prod_entry_mode_free").prop('checked',false).change();
	jQuery("#prod_entry_mode_predef").prop('checked',true).change();
	jQuery("#price_ht").val('').hide();
	jQuery("#multicurrency_price_ht").hide();
	jQuery("#price_ttc").hide();	// May no exists
	jQuery("#fourn_ref").hide();
	jQuery("#tva_tx").hide();
	jQuery("#buying_price").show();
	jQuery("#title_vat").hide();
	jQuery("#title_up_ht").hide();
	jQuery("#title_up_ht_currency").hide();
	jQuery("#title_up_ttc").hide();
	jQuery("#title_up_ttc_currency").hide();
	jQuery("#np_marginRate").hide();	// May no exists
	jQuery("#np_markRate").hide();	// May no exists
	jQuery(".np_marginRate").hide();	// May no exists
	jQuery(".np_markRate").hide();	// May no exists
	jQuery("#units, #title_units").hide();

	jQuery('#trlinefordates').show();
	jQuery('.divlinefordates').show();
}

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
