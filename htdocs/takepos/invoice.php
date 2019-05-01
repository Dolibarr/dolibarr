<?php
/**
 * Copyright (C) 2018    Andreu Bisquerra    <jove@bisquerra.com>
 * Copyright (C) 2019	JC Prieto			<jcprieto@virtual20.com>
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

// if (! defined('NOREQUIREUSER'))    define('NOREQUIREUSER','1');    // Not disabled cause need to load personalized language
// if (! defined('NOREQUIREDB'))        define('NOREQUIREDB','1');        // Not disabled cause need to load personalized language
// if (! defined('NOREQUIRESOC'))        define('NOREQUIRESOC','1');
// if (! defined('NOREQUIRETRAN'))        define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))    { define('NOCSRFCHECK', '1'); }
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', '1'); }
if (!defined('NOREQUIREMENU'))  { define('NOREQUIREMENU', '1'); }
if (!defined('NOREQUIREHTML'))  { define('NOREQUIREHTML', '1'); }
if (!defined('NOREQUIREAJAX'))  { define('NOREQUIREAJAX', '1'); }

require '../main.inc.php';

 // Load $user and permissions

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';

require_once DOL_DOCUMENT_ROOT.'/takepos/lib/takepos.lib.php';	//V20

$langs->load('takepos@takepos');	//V20
$langs->loadLangs(array("bills","cashdesk","orders","companies"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$idproduct = GETPOST('idproduct', 'int');
$place = GETPOST('place', 'int');
$number = GETPOST('number');

$idline = GETPOST('idline');
$desc = GETPOST('desc', 'alpha');
$pay = GETPOST('pay');

//****************** V20
$nb = GETPOST('nb', 'int');			//V20: quantity of products
if(empty($nb))	$nb=1;



//V20: Terminal
$term=$_SESSION['term'];

$ticket=array();	//V20
$ticket=json_decode($_SESSION['ticket'],true);

$diners=$ticket['diners'];	//V20
$facid=$placeid=$ticket['facid'];
$place=$ticket['place'];
$placelabel=$ticket['placelabel'];

$invoice = new Facture($db);
$invoice->fetch($placeid);
$n_lines=count($invoice->lines);
//**************************************

$x=('TAKEPOS_PRINT_SERVER'.$term);
$print_server=$conf->global->$x;

/*
* Actions
*/

if(($invoice->statut==Facture::STATUS_CLOSED || $invoice->statut==Facture::STATUS_VALIDATED) && strpos($invoice->ref,'POS')!==false && $action=='customer')		//V20: To do an invoice from older ticket.
{
	if($conf->global->POS_REFNUM)	$newref=POS_getNextValue('next');		//v20: POS number for invoices.	
	else 							$newref=$invoice->getNextNumRef($invoice->thirdparty);
	
	$invoice->ref=$newref;
	$invoice->update($user);
}
	
if($invoice->statut==Facture::STATUS_DRAFT)		//V20: Only no paid
{	 
	if ($action == 'valid' && $user->rights->facture->creer && $term>0) //Not exist account_cash0
	{
				
		//Update
		$paycode = $pay;
		if ($pay == 'cash' || $pay=='LIQ') $paycode = 'LIQ';
		if ($pay == 'card' || $pay=='CB') $paycode = 'CB';
		if ($pay == 'cheque' || $pay=='CHQ') $paycode = 'CHQ';
		$codes=getPaiementMode(1,$paycode);
		$paiementid=$codes[0];
		
		
		
		if ($paycode=="LIQ"){
			$x=('CASHDESK_ID_BANKACCOUNT_CASH'.$term);	//V20
			$bankaccount= $conf->global->$x;
		}
		elseif ($paycode=="CB"){
			$bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CB;
		}
		elseif ($paycode=="CHQ"){
			$bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
		}
	    else{
	    	$accountname="CASHDESK_ID_BANKACCOUNT_".$paycode;
	    	$bankaccount=$conf->global->$accountname;
	    }
		$now=dol_now();
		
		
		//V20: Validate  and create movemnet even with batch. POS should not use products with batch. 
		$warehouseidtodecrease=(isset($conf->global->CASHDESK_ID_WAREHOUSE) ? $conf->global->CASHDESK_ID_WAREHOUSE : 0); 
		if (! empty($conf->global->CASHDESK_NO_DECREASE_STOCK)) $warehouseidtodecrease=0;
		
		//V20: Pay mode
		$sql="UPDATE ".MAIN_DB_PREFIX."facture SET fk_mode_reglement=".$paiementid.", datef='".$db->idate($now)."', date_lim_reglement='".$db->idate($now)."', pos_source=".$term;
		$sql.=" WHERE rowid=".$invoice->id;
    	if($resql=$db->query($sql))    	dol_syslog("TakePos::update_ticket before validate. Place=".$place."(".$placelabel."), term=".$term, LOG_DEBUG);
    	else{
    		setEventMessage('Error actualizando ticket: '.$resql,'errors');
    	   	dol_syslog("TakePos::update_ticket before validate. Place=".$place."(".$placelabel."), term=".$term, LOG_ERR);
    	   	exit;
    	}
		
		if($invoice->socid==$conf->global->CASHDESK_ID_THIRDPARTY) $invoice->validate($user,'POS'.$invoice->id);    //V20: Default customer= Es solo ticket.
		elseif($conf->global->POS_REFNUM)						   $invoice->validate($user,POS_getNextValue('next'));		//v20: POS number for invoices.			
		else													   $invoice->validate($user);		//V20: Standard invoice, default counter
			
		if ($warehouseidtodecrease > 0)
		{
			// Decrease
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
			$langs->load("agenda");
			// Loop on each line
			$cpt=count($invoice->lines);
			for ($i = 0; $i < $cpt; $i++)
			{
				if ($invoice->lines[$i]->fk_product > 0)
				{
					$mouvP = new MouvementStock($db);
					$mouvP->origin = &$invoice;
					
					// We decrease stock for product
					//$result=$mouvP->livraison($user, $invoice->lines[$i]->fk_product, $warehouseidtodecrease, $invoice->lines[$i]->qty, $invoice->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarrFromPos",$invoice->newref));
					$result=$mouvP->_create($user, $invoice->lines[$i]->fk_product, $warehouseidtodecrease, -1*$invoice->lines[$i]->qty, 2, $invoice->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarrFromPos",$invoice->newref),'TPV','','','','',true);
											
					if ($result < 0) {
					    setEventMessages($mouvP->error, $mouvP->errors, 'errors');
					    $error++;
					}
				}
			}
		}
		
		
		// Add the payment
		$payment=new Paiement($db);
		$payment->datepaye=$now;
		$payment->bank_account=$bankaccount;
		$payment->amounts[$invoice->id]=$invoice->total_ttc;

		$payment->paiementid=$paiementid;	//Update
		$payment->num_paiement=$invoice->ref;
	
	  	$payment->create($user);
		$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccount, '', '');
		
	  	$invoice->set_paid($user);
		
	}
	
	if ($action=="addline" || $action=="addline_barcode" ||$action=="freezone")
	{
		// $place is id of POS, $placeid is id of invoice
		//TODO: Must be clear with variables !!! :  $place is id of PLACE and $term is id of POS
		if ($placeid==0)
	    {
	    	
	    	$facid=$placeid=create_ticket($place,$term,$placelabel);
	    	$invoice->fetch($placeid);
	    	//Ticket updated. TODO: Make a class for ticket, now using functions.
			$ticket['facid']=$invoice->id;
			$_SESSION['ticket']=json_encode($ticket);
		}
		//V20: Empty Tickets are not deleted, so with first line is like opening new ticket and date is now(). 
		elseif($n_lines==0){
			$invoice->date_creation=dol_now();
			$invoice->date=dol_now();
			$invoice->update($user);
		}
	}
	
	if ($action == "addline" || $action=="addline_barcode") {
	    $prod = new Product($db);
	    $prod->fetch($idproduct);
	   
	    //V20: Using multiprices level
	    
	    $f=('POS_FLOOR_PRICELEVEL'.$ticket['floor']);
		$price_level=$conf->global->$f;
	    if($action=="addline_barcode" || $ticket['floor']==0)	$price_level=$conf->global->POS_ID_PRICELEVEL;	//V20: Main price level (shop)
	    
		if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($price_level))
		{
			$pu_ht = $prod->multiprices[$price_level];
			$pu_ttc = $prod->multiprices_ttc[$price_level];
			$price_base_type = $prod->multiprices_base_type[$price_level];
			$tva_tx=$prod->multiprices_tva_tx[$price_level];
		}else{
			$pu_ht = $prod->price;
			$pu_ttc = $prod->price_ttc;
			
			$price_base_type = $prod->price_base_type;
			$tva_tx=$prod->tva_tx;
		}
		
		$result=-1;
		if ($number>0)		$note=array('options_order_notes'=>'#'.$number.': ');	//V20: Diner number.
		else{
			foreach($invoice->lines as $line)
			{
				if ($line->fk_product == $idproduct && $line->array_options['options_order_notes']=='')
					$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty+$nb, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
			}
		}
		if($result<0){
			$parentid=$invoice->addline($prod->description, $pu_ht, $nb, $tva_tx, $prod->localtax1_tx, $prod->localtax2_tx, $idproduct, $prod->remise_percent, '', 0, 0, 0, '', $price_base_type, $pu_ttc, $prod->type, -1, 0, '', 0, 0, null, $prod->pa_ht, '', $note, 100, '', null, 0);
/*		
			//V20: Composite product? = Menu
			$childs=$prod->getChildsArbo($prod->id);
			if(count($childs)>0){
				$prodchild=new Product($db);
				foreach($childs as $keyChild => $valueChild)	
				{
					$prodchild->fetch($keyChild);
					$qtychild=$valueChild[1];
					$qtyline=$qtychild*$nb;
					if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($price_level))
					{
						$pu_ht = $prodchild->multiprices[$price_level];
						$pu_ttc = $prodchild->multiprices_ttc[$price_level];
						//$price_min = $prodchild->multiprices_min[$price_level];
						$price_base_type = $prodchild->multiprices_base_type[$price_level];
						$tva_tx=$prodchild->multiprices_tva_tx[$price_level];
						//$tva_npr=$prodchild->multiprices_recuperableonly[$price_level];
						
					}else{
						$pu_ht = $prodchild->price;
						$pu_ttc = $prodchild->price_ttc;
						
						$price_base_type = $prodchild->price_base_type;
						$tva_tx=$prodchild->tva_tx;
					}
								
					//$invoice->addline($prodchild->description, $pu_ht, $nb, $tva_tx, $prodchild->localtax1_tx, $prodchild->localtax2_tx, $keyChild, $prodchild->remise_percent, '', 0, 0, 0, '', $price_base_type, $pu_ttc, $prodchild->type, -1, 0, '', 0, 0, null, $prodchild->pa_ht, '', $note, 100, '', null, 0);
					//V20: child lines as services and special_code=9. This is to filter later: not print child lines without price (default).
					$childdesc=' - '.(empty($prodchild->description) ? $prodchild->label : $prodchild->description);
					$invoice->addline($childdesc, 0, $nb, $tva_tx, $prodchild->localtax1_tx, $prodchild->localtax2_tx,$keyChild, $prodchild->remise_percent, '', 0, 0, 0, '', $price_base_type, 0, 1, -1, 9, '', 0, $parentid, null, 0, '', $note, 100, '', null, 0);
				}
			}
*/
		}
		
	    $invoice->fetch($placeid);
	}
	
	if ($action == "freezone") {
		
	    $invoice->addline($desc, $number, 1, GETPOST('vat','int'), 0, 0, 0, 0, '', 0, 0, 0, '', 'TTC', $number, 0, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);
	    $invoice->fetch($placeid);
	}
	//V20
	if ($action == "diners") {
		if($facid==0){
			$facid=$placeid=create_ticket($place,$term,$placelabel);
			$invoice->fetch($facid);
			$ticket['facid']=$invoice->id;;
		}
		$invoice->array_options['options_diner']=$number;
		$invoice->update($user);	//$newinvoice->fetch($newticket['facid']);
	
		$diners=$ticket['diners']=$number;
		$_SESSION['ticket']=json_encode($ticket);
	}
	
	//V20: Move and add,  current place to new place
	if ($action == "movetable") {
		$newplace=GETPOST('newplace','int');
		
		$newticket=array();	//V20
		$newticket=load_ticket($newplace);
		$newticket['diners']+=$diners;	//Sum new diners
		
		$newinvoice = new Facture($db);
		if($newticket['facid']>0){
			$newinvoice->fetch($newticket['facid']);
		}else{
	
			$facid=$placeid=create_ticket($newplace,$term,$newticket['placelabel']);
			$newinvoice->fetch($facid);
			$newticket['facid']=$newinvoice->id;
			$newinvoice->socid=$invoice->socid;	 //Old customer
		}
		
		foreach($invoice->lines as $line)
		{
			$newinvoice->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, 
					$line->remise_percent, $line->date_start,$line->date_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->product_type,
					-1, $line->special_code, $line->origin, $line->origin_id, $line->fk_parent_line, $line->fk_fournprice, $line->pa_ht,
					'', $line->array_options, 100, $line->fk_prev_id, $line->fk_unit, 0);
			if($invoice->statut==0)		$line->delete();	//Old ticket. Only if draft
		}
		if($invoice->statut==0)	$invoice->delete($user);	//Old ticket. Only if draft
		
		$diners=$newticket['diners'];	
		$facid=$placeid=$newticket['facid'];
		$place=$newticket['place'];
		$placelabel=$newticket['placelabel'];
		
		$_SESSION['ticket']=json_encode($newticket);
		
		$newinvoice->array_options['options_diner']=$diners;
		$newinvoice->update($user);
		//load_ticket($place,$facid);
	}
	
	//V20: Add diner number in notes
	if ($action == "addnote2" || $action=='adddiner') {
		
	    foreach($invoice->lines as $line)
	    {
	        if ($line->id == $idline)
			{
				$note=$line->array_options['options_order_notes'];
				//Search the old diner
				if(strpos($note,'#')===0){
					$old_diner=substr($note,1,2);
					$note=substr($note,strpos($note,' ')+1);
				}
				
				if ($action=='adddiner'){
					$line->array_options['options_order_notes'] = '#'.($diners<$number ? '' : $number).': '.$note;	//Max number of diners
				}
				else if($action == "addnote2"){
					$prod = new Product($db);
		    		$prod->fetch($idproduct);
		    		$line->array_options['options_order_notes'] = ($old_diner ? '#'.$old_diner.': ' : '').$prod->label;
				}
				$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
			  }
	    }
	    $invoice->fetch($placeid);
	}
	
	if ($action == "addnote") {
		//print $desc;
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
	
	if ($action == "deleteline" && $placeid > 0) {

		//V20: Re-done
		if (!$idline) { //If exist invoice, but no line selected, proced to delete last line
	        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facturedet where fk_facture='$placeid' order by rowid DESC";
	        $resql = $db->query($sql);
	        $row = $db->fetch_array($resql);
	        $idline=$row[0];	//V20
		}else{			
			$linestatic=new FactureLigne($db);
			$linestatic->fetch($idline);
		    
		    //V20: Delete all child products.
		    if($linestatic->fk_product>0){
				$prod = new Product($db);
		    	$childs=$prod->getChildsArbo($linestatic->fk_product);
				if(count($childs)>0){		//V20: Is parent
					foreach($invoice->lines as $line){
						if($line->fk_parent_line==$linestatic->id)		$invoice->deleteline($line->id);
					}
				}
		    }
		}
		$invoice->deleteline($idline);
		$invoice->fetch($placeid);
	}
	
	if ($action == "updateqty") {
	    foreach($invoice->lines as $line)
	    {
	        if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $number, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
	        }
	    }
	
	    $invoice->fetch($placeid);
	}
	//V20: Note: prices with vat (TTC)
	if ($action == "updateprice") {
	    foreach($invoice->lines as $line)
	    {
	        if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $number, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'TTC', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
	        }
	    }
	
	    $invoice->fetch($placeid);
	}
	
	if ($action == "updatereduction") {
	    foreach($invoice->lines as $line)
	    {
	        if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $number, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
	        }
	    }
	
	    $invoice->fetch($placeid);
	}

	if ($action == "order" and $placeid != 0) {
		include DOL_DOCUMENT_ROOT . '/takepos/receipt_order.php';
	    $invoice->fetch($placeid);
	}
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
<?php
//	V20: Kitchen/Order printer always number 2
if ($action == "order" and $order_receipt_printer1 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER_ORDER; ?>:8111/print2',
        data: '<?php
        print $headerorder . $order_receipt_printer1 . $footerorder; ?>'
    });
    <?php
}else{
	?>
	$.colorbox({href:"receipt_order.php?facid="+id+"&place="+place, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php
    echo $langs->trans("PrintOrder"); ?>"});
    <?php
}

/*
if ($action == "order" and $order_receipt_printer2 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER_ORDER; ?>:8111/print2',
        data: '<?php
        print $headerorder . $order_receipt_printer2 . $footerorder; ?>'
    });
    <?php
}
*/
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
        url: 'http://<?php print $print_server; ?>:8111/print',
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

function PrintOrder(id,place){
    $.colorbox({href:"receipt_order.php?facid="+id+"&place="+place, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php
    echo $langs->trans("PrintOrder"); ?>"});
}

function Print(id,place){
    $.colorbox({href:"receipt.php?facid="+id+"&place="+place, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php
    echo $langs->trans("PrintTicket"); ?>"});
}

function TakeposPrinting(id,place){
    var receipt;
    $.get("receipt.php?facid="+id+"&place="+place, function(data, status){
        receipt=data.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '');
        $.ajax({
            type: "POST",
            url: 'http://<?php print $print_server; ?>:8111/print',
            data: receipt
        });
    });
}
</script>


<?php
/*
 * View
 */
print '<div class="div-table-responsive-no-min invoice">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';
print '<td class="linecoldescription">' . $langs->trans('Description') . '</td>';
print '<td class="linecolqty" align="right">' . $langs->trans('Qty') . '</td>';
print '<td class="linecolht" align="right">' . $langs->trans('TotalHTShort') . '</td>';
print "</tr>\n";

if ($placeid > 0) {
    foreach($invoice->lines as $line)
    {
        print '<tr class="drag drop oddeven';
        if ($line->special_code == "3") { 
        	print ' order';
        }
        print '" id="' . $line->rowid . '">';
       
        print '<td align="left">';
        //V20: Is plate of menu?
        if ($line->special_code == "9" || $line->product_label=='')	print $line->desc; 
        else 							print $line->product_label;	
        
		if (!empty($line->array_options['options_order_notes'])) echo "<br><b>(".$line->array_options['options_order_notes'].")</b>";
		print '</td>';
        print '<td align="right">' . $line->qty . '</td>';
        print '<td align="right">' . price($line->total_ttc,0,'',1,2,2) . '</td>';	//V20: 2 decimals
        print '</tr>';
    }
}

print '</table>';
/*
print '<p style="font-size:120%;" align="right"><b>'.$langs->trans('TotalTTC');
//print '<br>'.$term;
                                            
if($conf->global->TAKEPOS_BAR_RESTAURANT) print " ".$langs->trans('Place')." ".$placelabel;
*/
//V20: Better
print '<p onclick="Refresh()" style="font-size:190%;" align="right"><b>';
if($conf->global->TAKEPOS_BAR_RESTAURANT){
	print '('.$diners.') '.$placelabel.' &nbsp--&nbsp ';
}
print $langs->trans('TotalTTC');
print '<input type="hidden" id="facid" name="facid" value="'.$facid.'">';	//V20: To use next time by script.

print ': '.price($invoice->total_ttc, 1, '', 1, 2, 2, $conf->currency).'&nbsp;</b></p>';	//V20: 2 decimals


//V20: Status Paid
if($invoice->statut==Facture::STATUS_CLOSED){
	print '<p style="font-size:120%;" align="right"><b>'.$langs->trans('Invoice').': '.$invoice->ref.' &nbsp&nbsp&nbsp<span style="font-size:190%; color:red" >'.$langs->trans("BillShortStatusPaid").'</span></b></p>';
}
//V20: Customer
print '<p style="font-size:120%;" align="right">';
print $langs->trans("Customer").': '.$ticket['customer'];
print '</p>';

if ($action=="valid" && $term>0)
{
	print '<p style="font-size:120%;" align="center"><b>'.$invoice->ref." ".$langs->trans('BillShortStatusValidated').'</b></p>';
	if ($conf->global->TAKEPOSCONNECTOR) print '<center><button type="button" onclick="TakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button><center>';
	else print '<center><button type="button" onclick="Print('.$placeid.');">'.$langs->trans('PrintTicket').'</button><center>';
	echo '<script>OpenDrawer();</script>';		//V20: 
}
if ($action=="valid" && !$term>0)	print '<p style="font-size:120%;" align="center"><b>'.$langs->trans('TerminalMissing').'</b></p>';

if ($action == "search")
{
    print '<center>
	<input type="text" id="search" name="search" onkeyup="Search2();" name="search" style="width:80%;font-size: 150%;" placeholder=' . $langs->trans('Search') . '
	</center>';
}

print '</div>';
