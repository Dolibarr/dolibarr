<?php
/**
 * Copyright (C) 2018    Andreu Bisquerra    <jove@bisquerra.com>
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

/**
 *	\file       htdocs/takepos/invoice.php
 *	\ingroup    takepos
 *	\brief      Page to generate section with list of lines
 */

// if (! defined('NOREQUIREUSER'))    define('NOREQUIREUSER', '1');    // Not disabled cause need to load personalized language
// if (! defined('NOREQUIREDB'))        define('NOREQUIREDB', '1');        // Not disabled cause need to load personalized language
// if (! defined('NOREQUIRESOC'))        define('NOREQUIRESOC', '1');
// if (! defined('NOREQUIRETRAN'))        define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))    { define('NOCSRFCHECK', '1'); }
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', '1'); }
if (!defined('NOREQUIREMENU'))  { define('NOREQUIREMENU', '1'); }
if (!defined('NOREQUIREHTML'))  { define('NOREQUIREHTML', '1'); }
if (!defined('NOREQUIREAJAX'))  { define('NOREQUIREAJAX', '1'); }

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

$langs->loadLangs(array("companies", "commercial", "bills", "cashdesk", "stocks"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$idproduct = GETPOST('idproduct', 'int');
$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0);   // $place is id of table for Bar or Restaurant

/**
 * Abort invoice creationg with a given error message
 *
 * @param   string  $message        Message explaining the error to the user
 * @return	void
 */
function fail($message)
{
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	die($message);
}



$placeid = 0;	// $placeid is id of invoice

$number = GETPOST('number', 'alpha');
$idline = GETPOST('idline', 'int');
$desc = GETPOST('desc', 'alpha');
$pay = GETPOST('pay', 'alpha');
$amountofpayment = price2num(GETPOST('amount', 'alpha'));

$invoiceid = GETPOST('invoiceid', 'int');

$paycode = $pay;
if ($pay == 'cash')   $paycode = 'LIQ';     // For backward compatibility
if ($pay == 'card')   $paycode = 'CB';      // For backward compatibility
if ($pay == 'cheque') $paycode = 'CHQ';     // For backward compatibility

// Retrieve paiementid
$sql = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement";
$sql.= " WHERE entity IN (".getEntity('c_paiement').")";
$sql.= " AND code = '".$db->escape($paycode)."'";
$resql = $db->query($sql);
$codes = $db->fetch_array($resql);
$paiementid=$codes[0];


$invoice = new Facture($db);
if ($invoiceid > 0)
{
    $ret = $invoice->fetch($invoiceid);
}
else
{
    $ret = $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')');
}
if ($ret > 0)
{
    $placeid = $invoice->id;
}


/*
 * Actions
 */

if ($action == 'valid' && $user->rights->facture->creer)
{
    if ($pay == "cash") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH'.$_SESSION["takeposterminal"]};            // For backward compatibility
    elseif ($pay == "card") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CB'.$_SESSION["takeposterminal"]};          // For backward compatibility
    elseif ($pay == "cheque") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$_SESSION["takeposterminal"]};    // For backward compatibility
    else
    {
        $accountname="CASHDESK_ID_BANKACCOUNT_".$pay.$_SESSION["takeposterminal"];
    	$bankaccount=$conf->global->$accountname;
    }
	$now=dol_now();

	$invoice = new Facture($db);
	$invoice->fetch($placeid);
	if ($invoice->total_ttc < 0) {
		$invoice->type= $invoice::TYPE_CREDIT_NOTE;
		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE ";
		$sql.="fk_soc = '".$invoice->socid."' ";
		$sql.="AND type <> ".Facture::TYPE_CREDIT_NOTE." ";
		$sql.="AND fk_statut >= ".$invoice::STATUS_VALIDATED." ";
		$sql.="ORDER BY rowid DESC";
		$resql = $db->query($sql);
		if($resql){
			$obj = $db->fetch_object($resql);
			$fk_source=$obj->rowid;
			if($fk_source == null){
				fail($langs->transnoentitiesnoconv("NoPreviousBillForCustomer"));
			}
		}else{
			fail($langs->transnoentitiesnoconv("NoPreviousBillForCustomer"));
		}
		$invoice->fk_facture_source=$fk_source;
		$invoice->update($user);
	}

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK'.$_SESSION["takeposterminal"];
	if (! empty($conf->stock->enabled) && $conf->global->$constantforkey != "1")
	{
		$savconst = $conf->global->STOCK_CALCULATE_ON_BILL;
		$conf->global->STOCK_CALCULATE_ON_BILL=1;

		$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
		dol_syslog("Validate invoice with stock change into warehouse defined into constant ".$constantforkey." = ".$conf->global->$constantforkey);
		$res = $invoice->validate($user, '', $conf->global->$constantforkey);

		$conf->global->STOCK_CALCULATE_ON_BILL = $savconst;
	}
	else
	{
	    $res = $invoice->validate($user);
	}

	$remaintopay = $invoice->getRemainToPay();

	// Add the payment
	if ($res >= 0 && $remaintopay > 0) {
		$payment = new Paiement($db);
		$payment->datepaye = $now;
		$payment->fk_account = $bankaccount;
		$payment->amounts[$invoice->id] = $amountofpayment;

		$payment->paiementid = $paiementid;
		$payment->num_payment = $invoice->ref;

		$payment->create($user);
		$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccount, '', '');

		$remaintopay = $invoice->getRemainToPay();	// Recalculate remain to pay after the payment is recorded
		if ($remaintopay == 0) {
			dol_syslog("Invoice is paid, so we set it to status Paid");
			$result = $invoice->set_paid($user);
			if ($result > 0) $invoice->paye = 1;
		} else {
			dol_syslog("Invoice is not paid, remain to pay = " . $remaintopay);
		}
	} else {
		dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
    }
}

if ($action == 'history')
{
    $placeid = GETPOST('placeid', 'int');
    $invoice = new Facture($db);
    $invoice->fetch($placeid);
}

if (($action=="addline" || $action=="freezone") && $placeid == 0)
{
	$invoice->socid = $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]};
	$invoice->date = dol_now();
	$invoice->module_source = 'takepos';
	$invoice->pos_source = $_SESSION["takeposterminal"];

	if ($invoice->socid <= 0)
	{
		$langs->load('errors');
		dol_htmloutput_errors($langs->trans("ErrorModuleSetupNotComplete", "TakePos"), null, 1);
	}
	else
	{
		$placeid = $invoice->create($user);
		if ($placeid < 0)
		{
			dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
		}
		$sql="UPDATE ".MAIN_DB_PREFIX."facture set ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' where rowid=".$placeid;
		$db->query($sql);
	}
}

if ($action == "addline")
{
	$prod = new Product($db);
    $prod->fetch($idproduct);

    $price = $prod->price;
    $tva_tx = $prod->tva_tx;
    $price_ttc = $prod->price_ttc;
    $price_base_type = $prod->price_base_type;

    if (! empty($conf->global->PRODUIT_MULTIPRICES))
    {
    	$customer = new Societe($db);
    	$customer->fetch($invoice->socid);

    	$price = $prod->multiprices[$customer->price_level];
    	$tva_tx = $prod->multiprices_tva_tx[$customer->price_level];
    	$price_ttc = $prod->multiprices_ttc[$customer->price_level];
    	$price_base_type = $prod->multiprices_base_type[$customer->price_level];
    }

    $idoflineadded = $invoice->addline($prod->description, $price, 1, $tva_tx, $prod->localtax1_tx, $prod->localtax2_tx, $idproduct, $prod->remise_percent, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);
    $invoice->fetch($placeid);
}

if ($action == "freezone") {
    $invoice->addline($desc, $number, 1, $conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS, 0, 0, 0, 0, '', 0, 0, 0, '', 'TTC', $number, 0, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);
    $invoice->fetch($placeid);
}

if ($action == "addnote") {
    foreach($invoice->lines as $line)
    {
        if ($line->id == $number)
		{
			$line->array_options['order_notes'] = $desc;
			$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
        }
    }
    $invoice->fetch($placeid);
}

if ($action == "deleteline") {
    if ($idline > 0 and $placeid > 0) { // If invoice exists and line selected. To avoid errors if deleted from another device or no line selected.
        $invoice->deleteline($idline);
        $invoice->fetch($placeid);
    }
    elseif ($placeid > 0) {             // If invoice exists but no line selected, proceed to delete last line.
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facturedet where fk_facture='".$placeid."' order by rowid DESC";
        $resql = $db->query($sql);
        $row = $db->fetch_array($resql);
        $deletelineid = $row[0];
        $invoice->deleteline($deletelineid);
        $invoice->fetch($placeid);
    }
}

if ($action == "delete") {
	// $placeid is the invoice id (it differs from place) and is defined if the place is set and the ref of invoice is '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')', so the fetch at begining of page works.
	if ($placeid > 0) {
        $result = $invoice->fetch($placeid);

        if ($result > 0 && $invoice->statut == Facture::STATUS_DRAFT)
        {
        	$db->begin();

        	// We delete the lines
        	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "facturedet_extrafields where fk_object = ".$placeid;
        	$resql1 = $db->query($sql);
        	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "facturedet where fk_facture = ".$placeid;
            $resql2 = $db->query($sql);
			$sql="UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".$conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]}." where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
			$resql3 = $db->query($sql);

            if ($resql1 && $resql2 && $resql3)
            {
            	$db->commit();
            }
            else
            {
            	$db->rollback();
            }

            $invoice->fetch($placeid);
        }
    }
}

if ($action == "updateqty")
{
    foreach($invoice->lines as $line)
    {
        if ($line->id == $idline)
        {
            $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $number, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
        }
    }

    $invoice->fetch($placeid);
}

if ($action == "updateprice")
{
    foreach($invoice->lines as $line)
    {
        if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $number, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
        }
    }

    $invoice->fetch($placeid);
}

if ($action == "updatereduction")
{
    foreach($invoice->lines as $line)
    {
        if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $number, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
        }
    }

    $invoice->fetch($placeid);
}

if ($action == "order" and $placeid != 0)
{
    include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    $headerorder = '<html><br><b>' . $langs->trans('Place') . ' ' . $place . '<br><table width="65%"><thead><tr><th class="left">' . $langs->trans("Label") . '</th><th class="right">' . $langs->trans("Qty") . '</th></tr></thead><tbody>';
    $footerorder = '</tbody></table>' . dol_print_date(dol_now(), 'dayhour') . '<br></html>';
    $order_receipt_printer1 = "";
    $order_receipt_printer2 = "";
    $catsprinter1 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_1);
    $catsprinter2 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_2);
    foreach($invoice->lines as $line)
    {
        if ($line->special_code == "4") { continue;
        }
        $c = new Categorie($db);
        $existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
        $result = array_intersect($catsprinter1, $existing);
        $count = count($result);
        if ($count > 0) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "facturedet set special_code='4' where rowid=$line->rowid";
            $db->query($sql);
            $order_receipt_printer1.= '<tr>' . $line->product_label . '<td class="right">' . $line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer1.="<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer1.='</td></tr>';
        }
    }

    foreach($invoice->lines as $line)
    {
        if ($line->special_code == "4") { continue;
        }
        $c = new Categorie($db);
        $existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
        $result = array_intersect($catsprinter2, $existing);
        $count = count($result);
        if ($count > 0) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "facturedet set special_code='4' where rowid=$line->rowid";
            $db->query($sql);
            $order_receipt_printer2.= '<tr>' . $line->product_label . '<td class="right">' . $line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer2.="<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer2.='</td></tr>';
        }
    }

    $invoice->fetch($placeid);
}

$sectionwithinvoicelink='';
if ($action=="valid" || $action=="history")
{
    $sectionwithinvoicelink.='<!-- Section with invoice link -->'."\n";
    $sectionwithinvoicelink.='<input type="hidden" name="invoiceid" id="invoiceid" value="'.$invoice->id.'">';
    $sectionwithinvoicelink.='<span style="font-size:120%;" class="center">';
    $sectionwithinvoicelink.=$invoice->getNomUrl(1, '', 0, 0, '', 0, 0, -1, '_backoffice')." - ";
    $remaintopay = $invoice->getRemainToPay();
    if ($remaintopay > 0)
    {
        $sectionwithinvoicelink.=$langs->trans('RemainToPay').': <span class="amountremaintopay" style="font-size: unset">'.price($remaintopay, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
    }
    else
    {
        if ($invoice->paye) $sectionwithinvoicelink.='<span class="amountpaymentcomplete" style="font-size: unset">'.$langs->trans("Paid").'</span>';
        else $sectionwithinvoicelink.=$langs->trans('BillShortStatusValidated');
    }
    $sectionwithinvoicelink.='</span>';
    if ($conf->global->TAKEPOSCONNECTOR) $sectionwithinvoicelink.=' <button id="buttonprint" type="button" onclick="TakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
    else $sectionwithinvoicelink.=' <button id="buttonprint" type="button" onclick="Print('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
    if ($conf->global->TAKEPOS_AUTO_PRINT_TICKETS) $sectionwithinvoicelink.='<script language="javascript">$("#buttonprint").click();</script>';
}


/*
 * View
 */

$form = new Form($db);

?>
<script language="javascript">
var selectedline=0;
var selectedtext="";
var placeid=<?php echo ($placeid > 0 ? $placeid : 0);?>;
$(document).ready(function() {
	var idoflineadded = <?php echo ($idoflineadded?$idoflineadded:0); ?>;

    $('.posinvoiceline').click(function(){
    	console.log("Click done on "+this.id);
        $('.posinvoiceline').removeClass("selected");
        $(this).addClass("selected");
        if (selectedline==this.id) return; // If is already selected
        else selectedline=this.id;
        selectedtext=$('#'+selectedline).find("td:first").html();
    });

    /* Autoselect the line */
    if (idoflineadded > 0)
    {
        console.log("Auto select "+idoflineadded);
        $('.posinvoiceline#'+idoflineadded).click();
    }
<?php

if ($action == "order" and $order_receipt_printer1 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
        data: '<?php
        print $headerorder . $order_receipt_printer1 . $footerorder; ?>'
    });
    <?php
}

if ($action == "order" and $order_receipt_printer2 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print2',
        data: '<?php
        print $headerorder . $order_receipt_printer2 . $footerorder; ?>'
    });
    <?php
}

// Set focus to search field
if ($action == "search" || $action == "valid") {
    ?>
	parent.setFocusOnSearchField();
    <?php
}


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

if ($action == "search") {
    ?>
    $('#search').focus();
    <?php
}

?>

});

function Print(id){
    $.colorbox({href:"receipt.php?facid="+id, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php
    echo $langs->trans("PrintTicket"); ?>"});
}

function TakeposPrinting(id){
    var receipt;
    $.get("receipt.php?facid="+id, function(data, status){
        receipt=data.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '');
        $.ajax({
            type: "POST",
            url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
            data: receipt
        });
    });
}
</script>

<?php
// Add again js for footer because this content is injected into takepos.php page so all init
// for tooltip and other js beautifiers must be reexecuted too.
if (! empty($conf->use_javascript_ajax))
{
    print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
    print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext?'&'.$ext:'').'"></script>'."\n";
}


print '<div class="div-table-responsive-no-min invoice">';
print '<table id="tablelines" class="noborder noshadow postablelines" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';
print '<td class="linecoldescription">';
print '<span style="font-size:120%;" class="right">';
if ($conf->global->TAKEPOS_BAR_RESTAURANT)
{
    $sql="SELECT floor, label FROM ".MAIN_DB_PREFIX."takepos_floor_tables where rowid=".((int) $place);
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $label = $obj->label;
        $floor = $obj->floor;
    }
    print $langs->trans('Place')." <b>".$label."</b> - ";
    print $langs->trans('Floor')." <b>".$floor."</b> - ";
}
print $langs->trans('TotalTTC');
print ' : <b>'.price($invoice->total_ttc, 1, '', 1, - 1, - 1, $conf->currency).'</b></span>';
print '<br>'.$sectionwithinvoicelink;
print '</td>';
print '<td class="linecolqty right">' . $langs->trans('ReductionShort') . '</td>';
print '<td class="linecolqty right">' . $langs->trans('Qty') . '</td>';
print '<td class="linecolht right nowraponall">' . $langs->trans('TotalTTCShort') . '</td>';
print "</tr>\n";

if ($placeid > 0)
{
    if (is_array($invoice->lines) && count($invoice->lines))
    {
        $tmplines = array_reverse($invoice->lines);
        foreach($tmplines as $line)
        {
            $htmlforlines = '';

            $htmlforlines.= '<tr class="drag drop oddeven posinvoiceline';
            if ($line->special_code == "4") {
                $htmlforlines.= ' order';
            }
            $htmlforlines.= '" id="' . $line->id . '">';
            $htmlforlines.= '<td class="left">';
            //if ($line->product_label) $htmlforlines.= '<b>'.$line->product_label.'</b>';
            if (isset($line->product_type))
            {
                if (empty($line->product_type)) $htmlforlines.=img_object('', 'product').' ';
                else $htmlforlines.=img_object('', 'service').' ';
            }
            if ($line->product_label) $htmlforlines.= $line->product_label;
            if ($line->product_label && $line->desc) $htmlforlines.= '<br>';
            if ($line->product_label != $line->desc)
            {
                $firstline = dolGetFirstLineOfText($line->desc);
                if ($firstline != $line->desc)
                {
                    $htmlforlines.= $form->textwithpicto(dolGetFirstLineOfText($line->desc), $line->desc);
                }
                else
                {
                    $htmlforlines.= $line->desc;
                }
            }
            if (!empty($line->array_options['options_order_notes'])) $htmlforlines.= "<br>(".$line->array_options['options_order_notes'].")";
            $htmlforlines.= '</td>';
            $htmlforlines.= '<td class="right">' . vatrate($line->remise_percent, true) . '</td>';
            $htmlforlines.= '<td class="right">' . $line->qty . '</td>';
            $htmlforlines.= '<td class="right">' . price($line->total_ttc) . '</td>';
            $htmlforlines.= '</tr>'."\n";

            print $htmlforlines;
        }
    }
    else
    {
        print '<tr class="drag drop oddeven"><td class="left"><span class="opacitymedium">'.$langs->trans("Empty").'</span></td><td></td><td></td><td></td></tr>';
    }
}
else {      // No invoice generated yet
    print '<tr class="drag drop oddeven"><td class="left"><span class="opacitymedium">'.$langs->trans("Empty").'</span></td><td></td><td></td><td></td></tr>';
}

print '</table>';

if ($invoice->socid != $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]})
{
    $soc = new Societe($db);
    if ($invoice->socid > 0) $soc->fetch($invoice->socid);
    else $soc->fetch($conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]});
    print '<!-- Show customer --><p style="font-size:120%;" class="right">';
    print $langs->trans("Customer").': '.$soc->name;

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK'.$_SESSION["takeposterminal"];
	if (! empty($conf->stock->enabled) && $conf->global->$constantforkey != "1")
	{
		$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
		$warehouse = new Entrepot($db);
		$warehouse->fetch($conf->global->$constantforkey);
		print '<br>'.$langs->trans("Warehouse").': '.$warehouse->ref;
	}
    print '</p>';
}

if ($action == "search")
{
    print '<center>
	<input type="text" id="search" name="search" onkeyup="Search2();" name="search" style="width:80%;font-size: 150%;" placeholder=' . $langs->trans('Search') . '
	</center>';
}

print '</div>';
