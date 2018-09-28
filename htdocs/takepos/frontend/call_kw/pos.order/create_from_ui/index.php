<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
//print_r($json_obj);
$db->begin();
foreach ($json_obj->params->args[0] as &$invoice) {
	$obj = new Facture($db);
	$obj->ref_client     = $invoice->id;
	$obj->socid          = $conf->global->CASHDESK_ID_THIRDPARTY;
	$obj->date           = mktime();
	$obj->cond_reglement_id = 1;
	
	foreach ($invoice->data->lines as &$line) {
		$prod = new Product($db);
		$prod->fetch($line[2]->product_id);
		$line1=new FactureLigne($db);
		$line1->fk_product=$line[2]->product_id;
		$line1->tva_tx=$prod->tva_tx;
		$line1->remise_percent=$line[2]->discount;
		$line1->qty=$line[2]->qty;
		$line1->total_ht=$prod->tva_tx/100;
		$line1->total_ht=$line1->total_ht+1;
		$line1->subprice=$line[2]->price_unit/$line1->total_ht;
		$line1->total_ht=round($line1->subprice*$line[2]->qty,2);
		$line1->total_tva=($line[2]->price_unit*$line[2]->qty)-($line1->subprice*$line[2]->qty);
		$line1->total_ttc=$line[2]->price_unit*$line[2]->qty;
		$obj->lines[]=$line1;
	}
	
	// Create invoice
	$idobject=$obj->create($user);
	if ($idobject > 0)
	{
		// Change status to validated
		$result=$obj->validate($user);
		if ($result > 0){
			if ($invoice->data->statement_ids[0][2]->account_id==1) $bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
			else $bankaccount=$conf->global->CASHDESK_ID_BANKACCOUNT_CB;
			$payment=new Paiement($db);
			$payment->datepaye=$now;
			$payment->bank_account=$bankaccount;
			$payment->amounts[$obj->id]=$obj->total_ttc;
			if ($invoice->data->statement_ids[0][2]->account_id==1) $payment->paiementid=4;
			else $payment->paiementid=6;
			$payment->num_paiement=$obj->facnumber;
			$payment->create($user);
			$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccount, '', '');
			$obj->set_paid($user);
		}
		else
		{
			$error++;
			dol_print_error($db,$obj->error);
		}
	}
	else
	{
		$error++;
		dol_print_error($db,$obj->error);
	}


// -------------------- END OF YOUR CODE --------------------


}
if (! $error)
{
	$db->commit();
	echo '{"result": [1], "jsonrpc": "2.0", "id": 780044868}';
}
else
{
	print '--- end error code='.$error."\n";
	$db->rollback();
}
$db->close();
?>