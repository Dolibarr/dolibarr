<table class="border" width="100%">
	<tr>
		<td width="200"><?php echo $langs->trans("BankAccount") ?></td>
		<td><?php echo $import->account->getNomUrl(1) ?></td>
		<td width="200"><?php echo $langs->trans("DateStart") ?></td>
		<td><?php echo dol_print_date($import->dateStart, 'day') ?></td>
		<td><?php echo $langs->trans("AccountStatement") ?></td>
		<td><?php echo $import->numReleve ?></td>
	</tr>
	<tr>
		<td width="200"><?php echo $langs->trans("BankImportFile") ?></td>
		<td><?php echo basename($import->file) ?></td>
		<td width="200"><?php echo $langs->trans("DateEnd") ?></td>
		<td><?php echo dol_print_date($import->dateEnd, 'day') ?></td>
		<td><?php echo $langs->trans("FileHasHeader") ?></td>
		<td><?php echo $import->hasHeader == 1 ? $langs->trans('Yes') : $langs->trans('No') ?></td>
	</tr>
</table>
<br />

<form method="post" enctype="multipart/form-data" name="bankimport">
	<input type="hidden" name="accountid" value="<?php echo $import->account->id ?>" />
	<input type="hidden" name="filename" value="<?php echo $import->file ?>" />
	<input type="hidden" name="datestart" value="<?php echo $import->dateStart ?>" />
	<input type="hidden" name="dateend" value="<?php echo $import->dateEnd ?>" />
	<input type="hidden" name="numreleve" value="<?php echo $import->numReleve ?>" />
	<input type="hidden" name="hasheader" value="<?php echo $import->hasHeader ?>" />
	<input type="hidden" name="token" value=" <?php echo function_exists('newToken') ? newToken() : $_SESSION['newtoken']; ?> ">


	<input type="hidden" name="bankimportseparator" value="<?php echo GETPOST('bankimportseparator','alphanohtml') ?>" />
    <input type="hidden" name="bankimportdateformat" value="<?php echo GETPOST('bankimportdateformat','alphanohtml') ?>" />
    <input type="hidden" name="bankimportmapping" value="<?php echo GETPOST('bankimportmapping','alphanohtml') ?>" />


	<table id="bankimport_line_to_import" class="border" width="100%">
		<tr class="liste_titre">
			<td colspan="4" width="40%"><?php echo $langs->trans("FileTransactions") ?></td>
			<td colspan="7" width="60%"><?php echo $langs->trans("DolibarrTransactions") ?></td>
		</tr>
		<tr class="liste_titre">
			<td><?php echo $langs->trans("Line") ?></td>
			<td><?php echo $langs->trans("Date") ?></td>
			<td><?php echo $langs->trans("Description") ?></td>
			<td width="80"><?php echo $langs->trans("Amount") ?></td>
			<td><?php echo $langs->trans("Transaction") ?></td>
			<td><?php echo $langs->trans("Date") ?></td>
			<td><?php echo $langs->trans("Description") ?></td>
			<td><?php echo $langs->trans("RelatedItem") ?></td>
			<td width="80"><?php echo $langs->trans("Amount") ?></td>
			<td><label for="checkall"><?php echo $langs->trans("PlannedAction") ?></label></td>
			<td align="center"><input type="checkbox" <?php !getDolGlobalString('BANKIMPORT_UNCHECK_ALL_LINES') ? print 'checked="checked"' : ''; ?> id="checkall" name="checkall" value="1" onchange="checkAll()" /></td>
		</tr>

		<?php foreach($TTransactions as $i => $line) { ?>
		<tr <?php echo $bc[$var] ?>>
			<?php if(!empty($line['bankline'])) { ?>

				<td class="num_line" rowspan="<?php echo count($line['bankline']) ?>"><?php echo $i + 1 ?></td>
				<td rowspan="<?php echo count($line['bankline']) ?>"><?php echo $line['date'] ?></td>
				<td rowspan="<?php echo count($line['bankline']) ?>"><?php echo $line['label'] ?></td>
				<td rowspan="<?php echo count($line['bankline']) ?>" align="right"><?php echo price($line['amount']) ?></td>

				<?php foreach($line['bankline'] as $j => $bankline) { ?>
				<?php if($j > 0) echo '<tr>' ?>
				<td><?php echo $bankline['url'] ?></td>
				<td><?php echo $bankline['date'] ?></td>
				<td><?php echo $bankline['label'] ?></td>
				<td><?php echo $bankline['relateditem'] ?></td>
				<td align="right"><?php echo $bankline['amount'] ?></td>
				<td><?php echo $bankline['result'] ?></td>
				<td align="center">
				<?php if($bankline['autoaction']) { ?><input type="checkbox" rel="doImport" checked="checked" name="TLine[<?php echo $bankline['id'] ?>]" value="<?php echo $i ?>" /><?php } ?>
				</td>
				<?php if($j < count($line['bankline'])) echo '</tr>' ?>
				<?php } ?>

			<?php } else if(!empty($line['error'])) { ?>
				<td class="num_line"><?php echo $i + 1 ?></td>
				<td colspan="4"><?php echo $line['error'] ?></td>
				<td colspan="7">&nbsp;</td>

			<?php } else { ?>
				<td class="num_line"><?php echo $i + 1 ?></td>
				<td><?php echo $line['date'] ?></td>
				<td><?php echo $line['label'] ?></td>
				<td align="right"><?php echo price($line['amount']) ?></td>
				<td class="fields_required" colspan="5">
					<select class="flat" name="TLine[type][<?php echo $i ?>]" id="select_line_type_<?php echo $i ?>">
						<?php
							if(getDolGlobalString('BANKIMPORT_ALLOW_FREELINES')) {
								print '<option value="freeline">'.$langs->trans('bankImportCretaFreeLine').'</option>';
							}
							if(isModEnabled('facture')) {
								$sel = ($line['amount'] > 0 ? ' selected="selected"' : '');
								print '<option value="facture"'.$sel.'>'.$langs->trans('Invoices').'</option>';
							}
							if(isModEnabled('fournisseur')) {
								$sel = ($line['amount'] < 0 ? ' selected="selected"' : '');
								print '<option value="fournfacture"'.$sel.'>'.$langs->trans('SupplierInvoices').'</option>';
							}
							if(isModEnabled('tax')) {
								print '<option value="charge">'.$langs->trans('Charges').'</option>';
							}
						?>
					</select>&nbsp;<span class="fieldrequired">*</span>

					<?php

					$comboName = 'TLine[fk_soc]['.$i.']';
					$line['code_client'] = trim($line['code_client']);

					$res = $db->query("SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe
							WHERE code_compta='".$db->escape($line['code_client'])."' OR code_compta_fournisseur='".$db->escape($line['code_client'])."'
							LIMIT 1");
					$fk_soc = 0;
					$name = $langs->trans('bankimport_no_customer_selected_click_to_select_one');
					if($obj_soc = $db->fetch_object($res))
					{
						$fk_soc = $obj_soc->rowid;
						$name = $langs->trans('bankimport_customer_selected_click_to_select_another_one', $obj_soc->nom);
					}

					$select_company = $form->select_company($fk_soc, $comboName,'',1,0,1);

					echo '<br />';
					echo $line['code_client'].' <span onclick="$(\'#span_for_company_'.$i.'\').show(); $(this).hide();"><b>'.$name.'</b></span><span id="span_for_company_'.$i.'" style="display:none">'.$select_company.'</span>';
					echo '&nbsp;<span class="fieldrequired">*</span><br />';
					echo $form->select_types_paiements('', 'TLine[fk_payment]['.$i.']');
					echo '&nbsp;<span class="fieldrequired">*</span>';

				?>

				<div class="container">
					<div style="margin-top:5px;" id="line_pieces_<?php echo $i ?>"></div>
				</div>

				<script type="text/javascript">
					$("select[name=\"<?php echo $comboName ?>\"], #select_line_type_<?php echo $i ?>").change(function() {
						var container_td = $(this).parent(); // td

						var type = $('#select_line_type_<?php echo $i ?>').val();

						<?php if(getDolGlobalString('BANKIMPORT_ALLOW_INVOICE_FROM_SEVERAL_THIRD')) { ?>

							if(this.name.toString().indexOf('TLine[type]') !== -1) {
								$("#line_pieces_<?php echo $i; ?>").empty();
								$div = $(container_td).find('div.container');
								console.log($div);
								$div.find('div[rel=total]').remove();
							}

						<?php } ?>

						$fk_soc = $("select[name=\"<?php echo $comboName ?>\"]");
						var fk_soc = $fk_soc.val();

						if(type == 'charge')$fk_soc.hide();
						else $fk_soc.show();

						$.ajax({
							url:"<?php echo dol_buildpath('/bankimport/script/interface.php',1) ?>"
							,data: {
								get:'pieceList'
								,fk_soc:fk_soc
								,type:type
								,i:<?php echo $i ?>
							}
						}).done(function( data) {

							<?php if(!getDolGlobalString('BANKIMPORT_ALLOW_INVOICE_FROM_SEVERAL_THIRD')) { ?>

								$("#line_pieces_<?php echo $i ?>").html(data);

							<?php } else { ?>

								var told_input = $(container_td).find('input[name^="TLine[piece]"]');

								if(told_input.length == 0) {
									console.log($("#line_pieces_<?php echo $i ?>"));
									$("#line_pieces_<?php echo $i ?>").append(data);
								} else {

									told_input.each(function(i) {
										var line = $('input[name="'+$(this).attr('name')+'"]');

										if(line.val() <= 0){
											line.parent().remove();
										}
									});


									var input_tline = $(data).find('input[name^="TLine[piece]"]');
									input_tline.each(function(i, item) {

										if($(container_td).find('input[name="' + $(item).attr("name") + '"]').length > 0) {
											console.log(item);
										} else {
											$("#line_pieces_<?php echo $i ?>").append($(item).parent());
										}

									});
								}

							<?php } ?>

							$(".auto_price").click(function() {
								$input = $('input[name="'+$(this).attr('id')+'"]');
								$input.val($('[name="price_'+$(this).attr('id')+'"]').val());
								$input.change();
							});

							$('input[rel=priceToPaiment]').unbind().change(function() {

								$div = $(this).closest('div.container');

								$div.find('div[rel=total]').remove();

								var total = 0;

								$div.find('input[rel=priceToPaiment]').each(function(i,item) {
									//$(item).val($(item).val().replace(',', '.')); // If the number was entered with commas
									$item = $(item);
									var price = parseFloat($item.val().replace(',', '.').replace(' ', ''));

									if(!isNaN(price)) {
										total += price;
									}
								});

								$div.append('<div style="font-weight:bold;" rel="total" align="left">Total : '+total+'</div>');

							});

						});

					});

				</script></td>
				<td><?php echo $langs->trans('BankTransactionWillBeCreatedAndReconciled', $import->numReleve) ?></td>
				<td align="center"><input type="checkbox" rel="doImport" <?php !getDolGlobalString('BANKIMPORT_UNCHECK_ALL_LINES') ? print 'checked="checked"' : ''; ?> name="TLine[new][]" value="<?php echo $i ?>" /></td>
			<?php } ?>

			<?php $var = !$var ?>
		</tr>
		<?php } ?>
	</table>
	<br />
	<script type="text/javascript">

		$('select[name*="TLine[fk_soc]"] > option[value=-1]').text('<?php echo $langs->transnoentitiesnoconv('bankImport_selectCompanyPls'); ?>');
		$('select[name*="TLine[fk_payment]"] > option[value=0]').text('<?php echo $langs->transnoentitiesnoconv('bankImport_selectPaymentTypePls'); ?>');
		$('select[name*="TLine[fk_soc]"]').css('margin', '3px 0 2px');

		function checkAll() {
			if($('input[name=checkall]').is(':checked')) {
				$('input[rel=doImport]').prop('checked', true);
			} else {
				$('input[rel=doImport]').prop('checked', false);
			}

		}
	</script>
	<div class="center">
		<input type="submit" class="button" name="import" value="<?php echo dol_escape_htmltag($langs->transnoentities("BankImport")) ?>">
	</div>
</form>


<script type="text/javascript">
	$(function() {
		$('form[name=bankimport]').submit(function(event) {
			var TError = new Array;
			var TLigneToImport = $('#bankimport_line_to_import td input[rel=doImport]:checked');

			for (var i = 0; i < TLigneToImport.length; i++)
			{
				var td_required = $(TLigneToImport[i]).parent().parent().children('td.fields_required');

				if (td_required)
				{
					if ($(td_required).children('select[name*="TLine[fk_soc]"]').val() == -1 && $(td_required).children('select[name*="TLine[type]"]').val() != 'freeline')
					{console.log($(td_required).parent().children('td.num_line'));
						TError.push("["+($(td_required).parent().children('td.num_line').text())+"] <?php echo $langs->transnoentitiesnoconv('bankImportFieldCompanyRequired'); ?>");
						$(td_required).children('select[name*="TLine[fk_soc]"]').focus();
					}
					if ($(td_required).children('select[name*="TLine[fk_payment]"]').val() == 0 && $(td_required).children('select[name*="TLine[type]"]').val() != 'freeline')
					{
						TError.push("["+($(td_required).parent().children('td.num_line').text())+"] <?php echo $langs->transnoentitiesnoconv('bankImportFieldPaymentRequired'); ?>");
						if (TError.length == 1) $(td_required).children('select[name*="TLine[fk_payment]"]').focus();
					}
				}

				if (TError.length > 0)
				{
					for (var i=0; i < TError.length; i++)
					{
						$.jnotify(TError[i], 'error', true);
					}

					return false;
				}
			}

			return true;
		});
	});
</script>
