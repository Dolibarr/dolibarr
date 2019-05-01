<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019		JC Prieto			<jcprieto@virtual20.com>
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
 *	\file       htdocs/takepos/cash_fence.php
 *	\brief      Cash fence
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php';	// Load $user and permissions

$langs->load('takepos@takepos');
$langs->loadLangs(array("bills", "cashdesk"));

$action = GETPOST('action', 'alpha');


//V20: Terminal
$terminalid=$_SESSION['term'];
$x=('TAKEPOS_PRINT_SERVER'.$terminalid);
$print_server=$conf->global->$x;

$posmodule='takepos';
$arrayofpaymentmode=array('cash'=>'Cash', 'cheque'=>'Cheque', 'card'=>'CreditCard');
$smonth=date('m');
$sday=date('d');
$syear=date('Y');

/*
 * Actions
 */
if($action=='OK' || $action=='closing' || $action=='opening'){
	$error=0;
	if (! $error)
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
		$object= new CashControl($db);
		
		$cash=price2num(GETPOST('cash','alpha'),'MT');
		$cashleft=price2num(GETPOST('cashleft','alpha'),'MT');
		$opening=price2num(GETPOST('opening','alpha'),'MT');
		$cashfault=$opening-$cash-$cashleft;
		
		$object->cash = $cash+$cashleft;		//V20 Always writes all chash amount.
		if($object->cash==0)	exit;
		
		$object->day_close = $sday;
		$object->month_close = $smonth;
		$object->year_close = $syear;

	    $object->opening=$opening;
	    $object->posmodule=$posmodule;
		$object->posnumber=$terminalid;

		$db->begin();

		$id=$object->create($user);

		if ($id > 0)
		{
			$db->commit();
			
			if(($action=='closing' || $action=='opening') && $conf->global->CASHDESK_ID_BANKACCOUNT_CLOSING>0)		//V20: Closing, transfer to account.
			{
				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		
				$x=('CASHDESK_ID_BANKACCOUNT_CASH'.$terminalid);	//V20
				$accountfrom=new Account($db);
				$accountfrom->fetch($conf->global->$x);
		
				$accountto=new Account($db);
				$accountto->fetch($conf->global->CASHDESK_ID_BANKACCOUNT_CLOSING);
		
				if($cash>0){
					$amount= $cash; 
					
					if($cashfault>0)	$text='. Falta '.price(abs($cashfault), 1, '', 1, 2, 2, $conf->currency);
					if($cashfault<0)	$text='. Sobra '.price(abs($cashfault), 1, '', 1, 2, 2, $conf->currency);
					$label=$langs->trans("LabelOfClosing",$accountfrom->label).$text;
				}else{
					$amount=  -1*$cashleft;
					$label=$langs->trans("LabelOfOpening",$accountfrom->label);
				}
				
				if ($accountto->currency_code == $accountfrom->currency_code)
				{
					$amountto=$amount;
				}
				else
				{
					if (! $amountto)
					{
						$error++;
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AmountTo")), null, 'errors');
					}
				}
		
				if (($accountto->id != $accountfrom->id) && empty($error))
				{
					$db->begin();
		
					$bank_line_id_from=0;
					$bank_line_id_to=0;
					$result=0;
		
					// By default, electronic transfert from bank to bank
					$typefrom='PRE';
					$typeto='VIR';
					if ($accountto->courant == Account::TYPE_CASH || $accountfrom->courant == Account::TYPE_CASH)
					{
						// This is transfer of change
						$typefrom='LIQ';
						$typeto='LIQ';
					}
					//V20		
					//$label=$langs->trans("LabelOfClosing",$accountfrom->label);
					$dateo=time();
					// TODO: Add const. Now we supouse label='POS'
					$sql = "SELECT";
					$sql .= " t.rowid,";
					$sql .= " t.label";
					$sql .= " FROM ".MAIN_DB_PREFIX."bank_categ as t";
					$sql .= " WHERE t.label = 'POS'";
					$sql .= " AND t.entity = ".$conf->entity;
					$resql = $db->query($sql);
					$cat = $db->fetch_array($resql);
					
					
					if (! $error) $bank_line_id_from = $accountfrom->addline($dateo, $typefrom, $label, -1*price2num($amount), '', $cat['rowid'], $user);
					if (! ($bank_line_id_from > 0)) $error++;
					if (! $error) $bank_line_id_to = $accountto->addline($dateo, $typeto, $label, price2num($amountto), '', $cat['rowid'], $user);
					if (! ($bank_line_id_to > 0)) $error++;
		
				    if (! $error) $result=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
					if (! ($result > 0)) $error++;
				    if (! $error) $result=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
					if (! ($result > 0)) $error++;
		
					if (! $error)
					{
						$mesgs = $langs->trans("TransferFromToDone", '<a href="bankentries_list.php?id='.$accountfrom->id.'&sortfield=b.datev,b.dateo,b.rowid&sortorder=desc">'.$accountfrom->label."</a>", '<a href="bankentries_list.php?id='.$accountto->id.'">'.$accountto->label."</a>", $amount, $langs->transnoentities("Currency".$conf->currency));
						setEventMessages($mesgs, null, 'mesgs');
						$db->commit();
					}
					else
					{
						setEventMessages($accountfrom->error.' '.$accountto->error, null, 'errors');
						$db->rollback();
					}
				}
				
				
			}
			
			//V20: Print closing
			$header = '<html><body><center><font size="4"><b>' . $langs->trans(($action=='OK' ? 'CashFence' : $action)) . '</b></font></center><br>'.
					dol_print_date(dol_now(), 'dayhour') .'<br>'. $langs->trans('User').': '.$user->login.'<br>'.
					$langs->trans('Terminal').': '.$terminalid.'<br><br>';
			
    		$footer = '<table width="65%"><tr><td><b>' . $langs->trans('OpeningAmount'). ': </b></td><td align="right"><b>' . price($object->opening) . '</b></td></tr>'.
    				'<tr><td>' . $langs->trans('CashAmount'). ': </td><td align="right">' . price($cash) . '</td></tr>'.
    				'<tr><td>' . $langs->trans('CashLeft'). ': </td><td align="right">' . price($cashleft) . '</td></tr>'.
    				'<tr><td>' . $langs->trans('CashFault'). ': </td><td align="right">' . price($cashfault) . '</td></tr></table>';
    		
    		if($print_server=='' && ($action=='closing' || $action=='opening'))				$footer.='<script> window.print();  </script>';
			$footer.='</body></html>';

			//top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
			print $header.$footer;
			exit;
		}
		else
		{
			$db->rollback;
			$action="view";
		}
	}
}



/*
 * View
 */
	
//top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

// Calculate $initialbalanceforterminal for terminal 0
foreach($arrayofpaymentmode as $key => $val)
{
	if ($key != 'cash')
	{
		$initialbalanceforterminal[$terminalid][$key] = 0;
		continue;
	}

	// Get the bank account dedicated to this point of sale module/terminal
	//$bankid = $conf->global->CASHDESK_ID_BANKACCOUNT_CASH;			// This value is ok for 'Terminal 0' for module 'CashDesk' and 'TakePos' (they manage only 1 terminal)
	$x=('CASHDESK_ID_BANKACCOUNT_CASH'.$terminalid);	//V20
	$bankid = $conf->global->$x;
	if(!$bankid>0)	exit;
	// Hook to get the good bank id according to posmodule and posnumber.
	// @TODO add hook here

	$sql = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."bank";
	$sql.= " WHERE fk_account = ".$bankid;
	if ($syear && ! $smonth)              $sql.= " AND dateo < '".$db->idate(dol_get_first_day($syear, 1))."'";
	elseif ($syear && $smonth && ! $sday) $sql.= " AND dateo < '".$db->idate(dol_get_first_day($syear, $smonth))."'";
	//elseif ($syear && $smonth && $sday)   $sql.= " AND dateo < '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."'";
	elseif ($sday)	$sql.= " AND dateo < '".$db->idate(time())."'";	//v20: Now
	else dol_print_error('', 'Year not defined');

	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj) $initialbalanceforterminal[$terminalid][$key] = $obj->total;
	}
	else dol_print_error($db);
}

//TODO: Need improve
$cash=$theoricalamountforterminal[$terminalid]['cash'] = price2num($initialbalanceforterminal[$terminalid]['cash'],3);


top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

?>
<script language="javascript">
var modified=0;

function update(type){
	var cash=$('#cash').val();
	var cashleft=$('#cashleft').val();
	var opening=$('#opening').val();
	var result=0;

	
	if(type=='cash'){
		result=opening-cash;
		if(result>=0){
			$('#cashleft').attr("style","width:45%;font-size: 100%;");
			$('#cashleft').val(result.toFixed(3));
			$('#cashfault').attr("style","width:45%;font-size: 100%;");
			$('#cashfault').val(0);
		}
		else{
			$('#cashfault').attr("style","width:45%;font-size: 100%; background-color: red;");
			$('#cashfault').val(result.toFixed(3));
			$('#cashleft').val(0);
		}
		
		modified=1;
	}else{
		
		if(modified==0){
			result=opening-cashleft;
			if(result>=0){
				$('#cash').attr("style","width:45%;font-size: 100%;");
				$('#cash').val(result.toFixed(3));
			}else{
				$('#cashfault').attr("style","width:45%;font-size: 100%; background-color: red;");
				$('#cashfault').val(result.toFixed(3));
				$('#cash').val(0);
				modified=1;
			}
			
		}
		else{
			result=opening-cash-cashleft;
			$('#cashfault').attr("style","width:45%;font-size: 100%; background-color: red;");
			$('#cashfault').val(result.toFixed(3));
		}	
		
		
	}
}

function Save(action){
	
	var cash=$('#cash').val();
	var cashleft=$('#cashleft').val();
	var opening=$('#opening').val();
	
	<?php 
	if($print_server>''){
		?>

	    var receipt;
	    $.get("cash_fence.php?action="+action+"&cash="+cash+"&cashleft="+cashleft+"&opening="+opening, function(data, status){
			if(action=="closing")
			{
		        receipt=data.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '');
		        $.ajax({
		            type: "POST",
		            url: 'http://<?php print $print_server; ?>:8111/print',
		            data: receipt
		        });
			}
	    });
	<?php     
	}else{
		?>
		$("#fence").load("cash_fence.php?action="+action+"&cash="+cash+"&cashleft="+cashleft+"&opening="+opening);
	 <?php
	}
	?>
	parent.$.colorbox.close();
}

function Closing(){
	$.get( "cash_fence.php", { action: "closing", cash:$('#cash').val(), opening:$('#opening').val()} );
	parent.$.colorbox.close();
}
</script>
<body>
<div id="fence">
<p>Terminal: <?php echo $terminalid?></p>
<p>Usuario: <?php echo $user->login?></p>
<center>
<?php print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formcashfence">';?>

	<p style="font-size: 200%;"><?php echo '<b>'.$langs->trans('OpeningAmount').'</b>';?> </p>
	<p style="font-size: 200%;"><?php echo $langs->trans('Cash').': '.price($cash, 1, '', 1, 3, 3, $conf->currency);?> </p>
	
	<?php if($cash>0){	?>
	<span style="width:20%;font-size: 200%;">Sale: <input type="text" id="cash" name="cash" onkeyup="update('cash')" value="<?php echo $cash;?>" style="width:65%;font-size: 100%;" placeholder="<?php echo $langs->trans('CashAmount');?>"></span><br>
	<?php }?>
	<span style="width:20%;font-size: 200%;">Queda: <input type="text" id="cashleft" name="cashleft" onkeyup="update('cashleft')" value="0" style="width:65%;font-size: 100%;" placeholder="<?php echo $langs->trans('CashLeft');?>"></span><br>
	<span style="width:20%;font-size: 200%;">Desfase: <input type="text" id="cashfault" name="cashfault" disabled value="0" style="width:65%;font-size: 100%;" placeholder="<?php echo $langs->trans('CashFault');?>"></span>
	
	<input type="hidden" id="opening" name="opening" value="<?php echo $cash;?>"><br><br><br>
	<?php if($cash>0){	?>
	<input type="button" style="width:25%;font-size: 200%;background-color: lightgray;" value="Arqueo" name="action" onclick="Save('OK');">
	<input type="button" style="width:25%;font-size: 200%;background-color: lightgray;" value="Cierre" name="action" onclick="Save('closing');">
	<?php }else{?>
	<input type="button" style="width:30%;font-size: 200%;background-color: lightgray;" value="Apertura" name="action" onclick="Save('opening');">
	<?php }?>
</form>
</center>
</div>
</body>
</html>