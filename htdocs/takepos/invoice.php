<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
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
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX','1');

require '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

$langs->loadLangs(array("companies","commercial","bills", "cashdesk"));

$id = GETPOST('id','int');
$action = GETPOST('action','alpha');
$idproduct = GETPOST('idproduct','int');
$place = GETPOST('place','int');
$number = GETPOST('number');
$idline = GETPOST('idline');
$desc = GETPOST('desc','alpha');
$pay = GETPOST('pay');

$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facture where facnumber='(PROV-POS-".$place.")'";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$placeid=$row[0];
if (! $placeid) $placeid=0; // not necesary
else{
	$invoice = new Facture($db);
	$invoice->fetch($placeid);
}

/*
 * Actions
 */

if ($action == 'valid' && $user->rights->facture->creer){
	if ($pay=="cash") $bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
	else if ($pay=="card") $bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CB;
	else if ($pay=="cheque") $bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
	$now=dol_now();
	$invoice = new Facture($db);
	$invoice->fetch($placeid);
	if (! empty($conf->stock->enabled) and $conf->global->CASHDESK_NO_DECREASE_STOCK!="1") $invoice->validate($user, '', $conf->global->CASHDESK_ID_WAREHOUSE);
	else $invoice->validate($user);
	// Add the payment
	$payment=new Paiement($db);
	$payment->datepaye=$now;
	$payment->bank_account=$bankaccount;
	$payment->amounts[$invoice->id]=$invoice->total_ttc;
	if ($pay=="cash") $payment->paiementid=4;
	else if ($pay=="card") $payment->paiementid=6;
	else if ($pay=="cheque") $payment->paiementid=7;
	$payment->num_paiement=$invoice->facnumber;
	$payment->create($user);
	$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccount, '', '');
	$invoice->set_paid($user);
}

if (($action=="addline" || $action=="freezone") and $placeid==0)
{
	// $place is id of POS, $placeid is id of invoice
	if ($placeid==0) {
		$invoice = new Facture($db);
		$invoice->socid=$conf->global->CASHDESK_ID_THIRDPARTY;
		$invoice->date=dol_now();
		$invoice->ref="(PROV-POS)";
		$invoice->module_source = 'takepos';
		$invoice->pos_source = (string) (empty($place)?'0':$place);

		$placeid=$invoice->create($user);
		$sql="UPDATE ".MAIN_DB_PREFIX."facture set facnumber='(PROV-POS-".$place.")' where rowid=".$placeid;
		$db->query($sql);
	}
}

if ($action=="addline"){
	$prod = new Product($db);
	$prod->fetch($idproduct);
	$invoice->addline($prod->description, $prod->price, 1, $prod->tva_tx, $prod->localtax1_tx, $prod->localtax2_tx, $idproduct, $prod->remise_percent, '', 0, 0, 0, '', $prod->price_base_type, $prod->price_ttc, $prod->type, - 1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);
	$invoice->fetch($placeid);
}

if ($action=="freezone"){
	$invoice->addline($desc, $number, 1, $conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS, 0, 0, 0, 0, '', 0, 0, 0, '', 'TTC', $number, 0, - 1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);
	$invoice->fetch($placeid);
}

if ($action=="deleteline"){
    if ($idline>0 and $placeid>0){ //If exist invoice and line, to avoid errors if deleted from other device or no line selected
        $invoice->deleteline($idline);
        $invoice->fetch($placeid);
    }
    else if ($placeid>0){ //If exist invoice, but no line selected, proced to delete last line
        $sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facturedet where fk_facture='$placeid' order by rowid DESC";
        $resql = $db->query($sql);
        $row = $db->fetch_array ($resql);
        $deletelineid=$row[0];
        $invoice->deleteline($deletelineid);
        $invoice->fetch($placeid);
    }
}

if ($action=="updateqty"){
    foreach ($invoice->lines as $line){
        if ($line->id==$idline) $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $number, $line->remise_percent,
			$line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type,
			$line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent,
			$line->fk_unit);
    }
	$invoice->fetch($placeid);
}

if ($action=="updateprice"){
    foreach ($invoice->lines as $line){
        if ($line->id==$idline) $result = $invoice->updateline($line->id, $line->desc, $number, $line->qty, $line->remise_percent,
			$line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type,
			$line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent,
			$line->fk_unit);
    }
	$invoice->fetch($placeid);
}

if ($action=="updatereduction"){
    foreach ($invoice->lines as $line){
        if ($line->id==$idline) $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $number,
			$line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type,
			$line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent,
			$line->fk_unit);
    }
	$invoice->fetch($placeid);
}

if ($action=="order" and $placeid!=0){
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$headerorder='<html><br><b>'.$langs->trans('Place').' '.$place.'<br><table width="65%"><thead><tr><th align="left">'.$langs->trans("Label").'</th><th align="right">'.$langs->trans("Qty").'</th></tr></thead><tbody>';
	$footerorder='</tbody></table>'.dol_print_date(dol_now(), 'dayhour').'<br></html>';
	$order_receipt_printer1="";
	$order_receipt_printer2="";
	$catsprinter1 = explode(';',$conf->global->TAKEPOS_PRINTED_CATEGORIES_1);
	$catsprinter2 = explode(';',$conf->global->TAKEPOS_PRINTED_CATEGORIES_2);
	foreach ($invoice->lines as $line){
		if ($line->special_code=="3") continue;
		$c = new Categorie($db);
		$existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
		$result = array_intersect($catsprinter1, $existing);
		$count=count($result);
		if ($count>0){
			$sql="UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='3' where rowid=$line->rowid";
			$db->query($sql);
			$order_receipt_printer1.='<tr>'.$line->product_label.'<td align="right">'.$line->qty.'</td></tr>';
		}
    }
	foreach ($invoice->lines as $line){
		if ($line->special_code=="3") continue;
		$c = new Categorie($db);
		$existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
		$result = array_intersect($catsprinter2, $existing);
		$count=count($result);
		if ($count>0){
			$sql="UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='3' where rowid=$line->rowid";
			$db->query($sql);
			$order_receipt_printer2.='<tr>'.$line->product_label.'<td align="right">'.$line->qty.'</td></tr>';
		}
    }
	$invoice->fetch($placeid);
}

?>
<style>
.selected {
	font-weight: bold;
}
.order {
	color: limegreen;
}
</style>
<script language="javascript">
var selectedline=0;
var selectedtext="";
var placeid=<?php echo $placeid;?>;
$(document).ready(function(){
    $('table tbody tr').click(function(){
		$('table tbody tr').removeClass("selected");
        $(this).addClass("selected");
		if (selectedline==this.id) return; // If is already selected
        else selectedline=this.id;
        selectedtext=$('#'+selectedline).find("td:first").html();
    });
<?php if ($action=="order" and $order_receipt_printer1!=""){
	?>
	$.ajax({
		type: "POST",
		url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER;?>:8111/print',
		data: '<?php print $headerorder.$order_receipt_printer1.$footerorder; ?>'
	});
<?php
}
if ($action=="order" and $order_receipt_printer2!=""){
	?>
	$.ajax({
		type: "POST",
		url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER;?>:8111/print2',
		data: '<?php print $headerorder.$order_receipt_printer2.$footerorder; ?>'
	});
<?php
}
if ($action == "search") {
    ?>
    $('#search').focus();
    <?php
}

?>
});

$(document).ready(function(){
    $('table tbody tr').click(function(){
        $('table tbody tr').removeClass("selected");
        $(this).addClass("selected");
        if (selectedline==this.id) return; // If is already selected
          else selectedline=this.id;
        selectedtext=$('#'+selectedline).find("td:first").html();
    });
<?php

if ($action == "temp" and $ticket_printer1 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
        data: '<?php
        print $header_soc . $header_ticket . $body_ticket . $ticket_printer1 . $ticket_total . $footer_ticket; ?>'
    });
    <?php
}

?>
});

function Print(id){
	$.colorbox({href:"receipt.php?facid="+id, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("PrintTicket");?>"});
}

function TakeposPrinting(id){
	var receipt;
	$.get("receipt.php?facid="+id, function(data, status){
        receipt=data.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '');
		$.ajax({
			type: "POST",
			url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER;?>:8111/print',
			data: receipt
		});
    });
}
</script>
<?php
print '<div class="div-table-responsive-no-min invoice">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';
print '<td class="linecoldescription">'.$langs->trans('Description').'</td>';
print '<td class="linecolqty" align="right">'.$langs->trans('Qty').'</td>';
print '<td class="linecolht" align="right">'.$langs->trans('TotalHTShort').'</td>';
print "</tr>\n";
if ($placeid>0) foreach ($invoice->lines as $line)
{
	print '<tr class="drag drop oddeven';
	if ($line->special_code=="3") print ' order';
	print '" id="'.$line->rowid.'">';
	print '<td align="left">'.$line->product_label.$line->desc.'</td>';
	print '<td align="right">'.$line->qty.'</td>';
	print '<td align="right">'.price($line->total_ttc).'</td>';
	print '</tr>';
}
print '</table>';
print '<p style="font-size:120%;" align="right"><b>'.$langs->trans('TotalTTC');
if($conf->global->TAKEPOS_BAR_RESTAURANT) print " ".$langs->trans('Place')." ".$place;
print ': '.price($invoice->total_ttc, 1, '', 1, - 1, - 1, $conf->currency).'&nbsp;</b></p>';

if ($invoice->socid != $conf->global->CASHDESK_ID_THIRDPARTY){
    $soc = new Societe($db);
    if ($invoice->socid > 0) $soc->fetch($invoice->socid);
    else $soc->fetch($conf->global->CASHDESK_ID_THIRDPARTY);
    print '<p style="font-size:120%;" align="right">';
    print $langs->trans("Customer").': '.$soc->name;
    print '</p>';
}
if ($action=="valid"){
	print '<p style="font-size:120%;" align="center"><b>'.$invoice->facnumber." ".$langs->trans('BillShortStatusValidated').'</b></p>';
	if ($conf->global->TAKEPOSCONNECTOR) print '<center><button type="button" onclick="TakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button><center>';
	else print '<center><button type="button" onclick="Print('.$placeid.');">'.$langs->trans('PrintTicket').'</button><center>';
}
if ($action=="search"){
	print '<center>
	<input type="text" id="search" name="search" onkeyup="Search2();" name="search" style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Search').'
	</center>';
}
print '</div>';
