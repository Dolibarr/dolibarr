<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/paypal/transaction.php
 *  \ingroup    paypal
 *  \brief      Page to list transactions in paypal account
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/date.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");

$langs->load("paypal");
$langs->load("paybox");
$langs->load("companies");
$langs->load("orders");
$langs->load("bills");

if (! $conf->global->PAYPAL_ENABLE_TRANSACTION_MANAGEMENT) accessforbidden();

// Security check
$result=restrictedArea($user,'paypal','','','transaction');

$action 		= GETPOST('action');
$id 			= GETPOST('id');
$page 			= GETPOST("page",'int');
$startDateStr	= GETPOST('startDateStr');
$endDateStr		= GETPOST('endDateStr');
$transactionID	= urlencode(GETPOST('transactionID'));

if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$errors='';

/*
 * Actions
 */



/*
 * View
 */

$nvpStr='';

$now=dol_now();

if(isset($startDateStr) && ! empty($startDateStr)) {
	$start_date_str = $startDateStr;
	$start_time 	= dol_stringtotime($start_date_str);
} else {
	$start_time 	= dol_time_plus_duree($now,-1,'m'); // 30 days
	$start_date_str = dol_print_date($start_time,'day');
}

$iso_start = dol_print_date($start_time,'dayhourrfc');
$nvpStr.="&STARTDATE=$iso_start";

if(isset($endDateStr) && ! empty($endDateStr)) {
	$end_date_str 	= $endDateStr;
	$end_time 		= dol_stringtotime($end_date_str)+86400; // For search in current day
} else {
	$end_time 		= $now;
	$end_date_str 	= dol_print_date($end_time,'day');
}

$iso_end = dol_print_date($end_time,'dayhourrfc');
$nvpStr.="&ENDDATE=".$iso_end;

if(isset($transactionID) && ! empty($transactionID)) {
	$nvpStr.="&TRANSACTIONID=$transactionID";
}
print 'iso_start='.$iso_start.' iso_end='.$iso_end;
// Call Paypal API
if (! empty($nvpStr))
{
	$resArray=hash_call("TransactionSearch",$nvpStr);
	//var_dump($resArray);

	if (is_array($resArray))
	{
		$reqArray=$_SESSION['nvpReqArray'];

		$ack = strtoupper($resArray["ACK"]);
		if($ack!="SUCCESS" && $ack!="SUCCESSWITHWARNING")
		{
			$_SESSION['reshash']=$resArray;
			$errors = GetApiError();
		}
	}
}

llxHeader();

dol_htmloutput_errors('',$errors);

print_fiche_titre(' - '.$langs->trans('PaypalTransaction'), '', 'paypal_logo@paypal');

print '<br />';

if (empty($conf->global->PAYPAL_API_USER) || empty($conf->global->PAYPAL_API_PASSWORD)
    || empty($conf->global->PAYPAL_API_SIGNATURE))
{
    $langs->load("errors");
    print $langs->trans("ErrorModuleSetupNotComplete");

    llxFooter();
    exit;
}


?>

<script>
	$(function() {
		var dates = $( "#startDateStr, #endDateStr" ).datepicker({
			defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 3,
			monthNames: tradMonths,
			monthNamesShort: tradMonthsMin,
			dayNames: tradDays,
			dayNamesMin: tradDaysMin,
			dateFormat: '<?php echo $langs->trans("FormatDateShortJQuery"); ?>',
			onSelect: function( selectedDate ) {
				var option = this.id == "startDateStr" ? "minDate" : "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				dates.not( this ).datepicker( "option", option, date );
			}
		});
		$( "div.paypal_link" ).click(function() {
			var id_value = $(this).attr("id");
			$.jnotify("<?php echo $langs->trans('PleaseBePatient'); ?>", 1500);
			$.getJSON( "<?php echo DOL_URL_ROOT; ?>/paypal/ajaxtransaction.php", {
				action: 'showdetails',
				transaction_id: id_value
			},
			function(details) {
				var $order_enabled = <?php echo (($conf->commande->enabled && $conf->global->PAYPAL_CREATE_ORDER_ENABLED) ? 'true' : 'false'); ?>;
				var $invoice_enabled = <?php echo (($conf->facture->enabled && $conf->global->PAYPAL_CREATE_INVOICE_ENABLED) ? 'true' : 'false'); ?>;
				var $element_created = false;
				
				$.each(details, function(key,value) {
					if (key == 'contents') {
						$( "div #paypal_detail_content" ).html(value);
					}
					if (key == 'element_created' && value == true) {
						$element_created = true;
					}
				});
				$( "div #paypal-details" ).dialog({
					modal: true,
					width: 500,
					buttons: {
						'<?php echo $langs->transnoentities('CreateOrder'); ?>': function() {
							$.getJSON( "<?php echo DOL_URL_ROOT; ?>/paypal/ajaxtransaction.php", {
								action: 'add',
								element: 'order',
								transaction_id: id_value
							},
							function(response) {
								$.each(response, function(key,value) {
									if (key == 'error') {
										$.jnotify(value, "error", true);
									} else {
										$.jnotify("<?php echo $langs->trans('PleaseBePatient'); ?>", 500);
										$( "div #paypal-details" ).dialog( "close" );
										location.href=value;
									}
								});
							});
						},
						'<?php echo $langs->transnoentities('CreateBill'); ?>': function() {
							$.getJSON( "<?php echo DOL_URL_ROOT; ?>/paypal/ajaxtransaction.php", {
								action: 'add',
								element: 'invoice',
								transaction_id: id_value
							},
							function(response) {
								$.each(response, function(key,value) {
									if (key == 'error') {
										$.jnotify(value, "error", true);
									} else {
										$.jnotify("<?php echo $langs->trans('PleaseBePatient'); ?>", 500);
										$( "div #paypal-details" ).dialog( "close" );
										location.href=value;
									}
								});
							});
						},
						'<?php echo $langs->transnoentities('Cancel'); ?>': function() {
							$( this ).dialog( "close" );
						}
					}
				});
				if (! $order_enabled) {
					$('.ui-dialog-buttonpane button').eq(0).hide();
				}
				if ($order_enabled && $element_created) {
					$('.ui-dialog-buttonpane button').eq(0).button('disable');
				}
				if (! $invoice_enabled) {
					$('.ui-dialog-buttonpane button').eq(1).hide();
				}
				if ($invoice_enabled && $element_created) {
					$('.ui-dialog-buttonpane button').eq(1).button('disable');
				}
			});
		});
	});
</script>

<div id="paypal-details" title="<?php echo $langs->trans('PaypalTransactionDetails'); ?>" style="display: none;">
	<div id="paypal_detail_content"></div>
</div>

<?php

// Search parameters
print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table>';

print '<tr><td>';
print $langs->trans('DateStart').': ';
print '<input type="text" id="startDateStr" name="startDateStr" maxlength="20" size="10" value="'.$start_date_str.'" />&nbsp;';

print $langs->trans('DateEnd').': ';
print '<input type="text" id="endDateStr" name="endDateStr" maxlength="20" size="10"  value="'.$end_date_str.'" />&nbsp;';

print $langs->trans('Ref').': ';
print '<input type="text" name="transactionID" />&nbsp;';

print '<input type="submit" class="button" value="'.$langs->trans('Send').'" />';
print '</td></tr>';

print '</table>';
print '</form>';


// Transactions list
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'','',''.$socid.'&amp;viewstatut='.$viewstatut,'width="20%"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('ThirdPartyName'),$_SERVER['PHP_SELF'],'','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="left"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'','',''.$socid.'&amp;viewstatut='.$viewstatut,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('GrossAmount'),$_SERVER['PHP_SELF'],'','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('FeeAmount'),$_SERVER['PHP_SELF'],'','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('NetAmount'),$_SERVER['PHP_SELF'],'','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield,$sortorder);
print_liste_field_titre(img_object($langs->trans('Paypal'), 'paypal@paypal'),$_SERVER['PHP_SELF'],'','',''.$socid.'&amp;viewstatut='.$viewstatut,'width="30" align="right"',$sortfield,$sortorder);
if ($conf->commande->enabled && $conf->global->PAYPAL_CREATE_ORDER_ENABLED)
{
	print_liste_field_titre(img_object($langs->trans('Order'), 'order'),$_SERVER['PHP_SELF'],'','',''.$socid.'&amp;viewstatut='.$viewstatut,'width="15" align="center"',$sortfield,$sortorder);
}
if ($conf->facture->enabled && $conf->global->PAYPAL_CREATE_INVOICE_ENABLED)
{
	print_liste_field_titre(img_object($langs->trans('Bill'), 'bill'),$_SERVER['PHP_SELF'],'','',''.$socid.'&amp;viewstatut='.$viewstatut,'width="15" align="center"',$sortfield,$sortorder);
}
print '</tr>';

$var=true;

if(! isset($resArray["L_TRANSACTIONID0"]))
{
	print '<tr '.$bc[$var].'>';
	print '<td colspan="6">'.$langs->trans("NoTransactionSelected").'</td>';
	print '</tr>';
}
else
{
	$i=0;

	while (isset($resArray["L_TRANSACTIONID".$i]))
	{
		$var=!$var;
		
		$objects = getLinkedObjects($resArray["L_TRANSACTIONID".$i]);

		$transactionID 	= $resArray["L_TRANSACTIONID".$i];
		$timeStamp		= dol_stringtotime($resArray["L_TIMESTAMP".$i]);
		$payerName		= $resArray["L_NAME".$i];
		$amount			= $resArray["L_AMT".$i];
		$feeamount		= $resArray["L_FEEAMT".$i];
		$netamount		= $resArray["L_NETAMT".$i];
		$currency 		= $resArray["L_CURRENCYCODE".$i];
		
		$status=0; $url='';
		if ($resArray["L_STATUS".$i]=='Completed') $status=1;

		print '<tr '.$bc[$var].'>';
		print '<td><div id="'.$transactionID.'" class="paypal_link" style="font-weight:bold;cursor:pointer;">'.$transactionID.'</div></td>';
		print '<td align="left">'.$payerName.'</td>';
		print '<td align="center">'.dol_print_date($timeStamp,'dayhour').'</td>';
		print '<td align="right">'.$amount.' '.$currency.'</td>';
		print '<td align="right">'.$feeamount.' '.$currency.'</td>';
		print '<td align="right">'.$netamount.' '.$currency.'</td>';
		print '<td align="right">'.getLibStatut($status, 1, $url).'</td>';
		if ($conf->commande->enabled && $conf->global->PAYPAL_CREATE_ORDER_ENABLED)
		{
			print '<td align="center">';
			if (! empty($objects['order']))	print '<a href="'.$objects['order']->getNomUrl(0,'',0,1).'">'.$objects['order']->getLibStatut(3).'</a>';
			else print '-';
			print '</td>';
		}
		if ($conf->facture->enabled && $conf->global->PAYPAL_CREATE_INVOICE_ENABLED)
		{
			print '<td align="center">';
			if (! empty($objects['invoice'])) print '<a href="'.$objects['invoice']->getNomUrl(0,'',0,1).'">'.$objects['invoice']->getLibStatut(3).'</a>';
			else print '-';
			print '</td>';
		}
		print '</tr>';

		$i++;
	}
}

print '</table>';

llxFooter('$Date$ - $Revision$');

?>