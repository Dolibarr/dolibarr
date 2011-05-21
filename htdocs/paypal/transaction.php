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
require_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");

$langs->load("paypal");

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

if(isset($startDateStr) && ! empty($startDateStr)) {
	$start_date_str = $startDateStr;
	$start_time 	= dol_stringtotime($start_date_str);
} else {
	$start_time 	= dol_now()-2592000; // 30 days
	$start_date_str = dol_print_date($start_time,'day');
}

$iso_start = dol_print_date($start_time,'dayhourrfc');
$nvpStr.="&STARTDATE=$iso_start";

if(isset($endDateStr) && ! empty($endDateStr)) {
	$end_date_str 	= $endDateStr;
	$end_time 		= dol_stringtotime($end_date_str);   
} else {
	$end_time 		= dol_now();
	$end_date_str 	= dol_print_date($end_time,'day');
}

$iso_end = dol_print_date($end_time,'dayhourrfc');
$nvpStr.="&ENDDATE=$iso_end"; 

if(isset($transactionID) && ! empty($transactionID)) {
	$nvpStr.="&TRANSACTIONID=$transactionID";
}


llxHeader();

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
			if ($.jnotify) {
				$.jnotify("<?php echo $langs->trans('PleaseBePatient'); ?>", 1500);
			}
			$.get( "<?php echo DOL_URL_ROOT; ?>/paypal/ajaxtransaction.php", {
				action: 'showdetails',
				transaction_id: id_value
			},
			function(details) {
				$( "div #paypal_detail_content" ).html(details);
				$( "div #paypal-details" ).dialog({
					modal: true,
					width: 500,
					buttons: {
						'<?php echo $langs->transnoentities('Create'); ?>': function() {
							$.get( "<?php echo DOL_URL_ROOT; ?>/paypal/ajaxtransaction.php", {
								action: 'add',
								element: 'order',
								transaction_id: id_value
							},
							function() {
								$( "div #paypal-details" ).dialog( "close" );
							});
						},
						'<?php echo $langs->transnoentities('Cancel'); ?>': function() {
							$( this ).dialog( "close" );
						}
					}
				});
			});
		});
	});
</script>

<div id="paypal-details" title="<?php echo $langs->trans('PaypalTransactionDetails'); ?>" style="display: none;">
	<div id="paypal_detail_content"></div>
</div>

<?php

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

dol_htmloutput_errors('',$errors);

print_fiche_titre(' - '.$langs->trans('PaypalTransaction'), '', 'paypal_logo@paypal');

print '<br />';

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

print '<input type="submit" value="'.$langs->trans('Send').'" />';
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
print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'','',''.$socid.'&amp;viewstatut='.$viewstatut,'align="right"',$sortfield,$sortorder);
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
		
		$transactionID 	= $resArray["L_TRANSACTIONID".$i];
		$timeStamp		= dol_stringtotime($resArray["L_TIMESTAMP".$i]);
		$payerName		= $resArray["L_NAME".$i];
		$amount			= $resArray["L_AMT".$i];
		$feeamount		= $resArray["L_FEEAMT".$i];
		$netamount		= $resArray["L_NETAMT".$i];
		$currency 		= $resArray["L_CURRENCYCODE".$i];
		$status			= $resArray["L_STATUS".$i];
		
		print '<tr '.$bc[$var].'>';
		print '<td><div id="'.$transactionID.'" class="paypal_link" style="font-weight:bold;cursor:pointer;">'.$transactionID.'</div></td>';
		print '<td align="left">'.$payerName.'</td>';
		print '<td align="center">'.dol_print_date($timeStamp,'dayhour').'</td>';
		print '<td align="right">'.$amount.' '.$currency.'</td>';
		print '<td align="right">'.$feeamount.' '.$currency.'</td>';
		print '<td align="right">'.$netamount.' '.$currency.'</td>';
		print '<td align="right">'.$status.'</td>';
		print '</tr>';
		
		$i++;
	}
}
	
print '</table>';

llxFooter('$Date$ - $Revision$');

?>