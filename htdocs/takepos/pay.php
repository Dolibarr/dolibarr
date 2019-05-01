<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
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

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

$_GET['theme']="md"; // Force theme. MD theme provides better look and feel to TakePOS

require '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$place = GETPOST('place', 'int');


/*
 * View
 */


//***********************V20: Terminal
$term=$_SESSION['term'];

$ticket=array();	//V20
$ticket=json_decode($_SESSION['ticket'],true);

$diners=$ticket['diners'];	//V20
$facid=$placeid=$ticket['facid'];
$place=$ticket['place'];
$placelabel=$ticket['placelabel'];

$invoice = new Facture($db);
$invoice->fetch($placeid);
if($invoice->statut==Facture::STATUS_CLOSED){
	echo '<script>parent.$.colorbox.close();</script>';		//V20: Close windows colorbox. Ticket paid.
	exit;
}
//***********************************

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

$langs->loadLangs(array("main", "bills", "cashdesk"));

//Update
$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql.= " WHERE entity IN (".getEntity('c_paiement').")";
$sql.= " AND active = 1";
$sql.= " ORDER BY libelle";
$resql = $db->query($sql);

$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
        $paycode = $obj->code;
        if ($paycode == 'LIQ') 		$accountname="CASHDESK_ID_BANKACCOUNT_CASH".$term;
        elseif ($paycode == 'CB') 	$accountname="CASHDESK_ID_BANKACCOUNT_CB";
        elseif ($paycode == 'CHQ')	$accountname="CASHDESK_ID_BANKACCOUNT_CHEQUE";
        else 						$accountname="CASHDESK_ID_BANKACCOUNT_".$paycode;
        
		if (! empty($conf->global->$accountname) && $conf->global->$accountname > 0) array_push($paiements, $obj);
	}
}
?>
<link rel="stylesheet" href="css/pos.css">
	<script>
	<?php
	if ($conf->global->TAKEPOS_NUMPAD==0) print "var received='';";
	else print "var received=0;";
	?>
	
	function addreceived(price)
	{
		<?php
		if ($conf->global->TAKEPOS_NUMPAD==0) print 'received+=String(price);';
		else print 'received+=parseFloat(price);';
		?>
		$('#change1').html(parseFloat(received).toFixed(2));
		if (parseFloat(received)><?php echo $invoice->total_ttc;?>)
		{
		var change=parseFloat(parseFloat(received)-<?php echo $invoice->total_ttc;?>);
		$('#change2').html(change.toFixed(2));
		}
	}

	function reset()
	{
		received=0;
		addreceived(0);
		$('#change2').html(received.toFixed(2));
	}

	function Validate(payment){
        parent.$("#poslines").load("invoice.php?place=<?php echo $place;?>&action=valid&pay="+payment, function() {
            parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
            parent.$.colorbox.close();
        });

	}
</script>
</head>
<body>

<div style="position:absolute; top:2%; left:5%; height:36%; width:91%;">
<center>
<div style="width:40%; background-color:#222222; border-radius:8px; margin-bottom: 4px;">
<center><span style='font-family: digital; font-size: 280%;'><font color="white"><?php echo $langs->trans('TotalTTC');?>: </font><font color="red"><span id="totaldisplay"><?php echo price($invoice->total_ttc, 1, '', 1, - 1, - 1, $conf->currency) ?></span></span></center>
</div>
<div style="width:40%; background-color:#333333; border-radius:8px; margin-bottom: 4px;">
<center><span style='font-family: digital; font-size: 250%;'><font color="white"><?php echo $langs->trans("AlreadyPaid"); ?>: </font><font color="red"><span id="change1"><?php echo price(0) ?></span></center>
</div>
<div style="width:40%; background-color:#333333; border-radius:8px; margin-bottom: 4px;">
<center><span style='font-family: digital; font-size: 250%;'><font color="white"><?php echo $langs->trans("Change"); ?>: </font><font color="red"><span id="change2"><?php echo price(0) ?></span></span></center>
</div>
</center>
</div>

<div style="position:absolute; top:40%; left:5%; height:55%; width:91%;">
<?php
$action_buttons = array(
	array(
		"function" =>"reset()",
		"span" => "style='font-size: 150%;'",
		"text" => "C",
	),
	array(
		"function" => "parent.$.colorbox.close();",
		"span" => "id='printtext'",
		"text" => $langs->trans("GoBack"),
	),
);

$numpad=$conf->global->TAKEPOS_NUMPAD;
/*<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "7"; else print "10";?>);"><?php if ($numpad==0) print "7"; else print img_picto('10', 'gfdl.png@takepos');	?></button>*/
?>

<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "7"; else print "10";?>);"><?php if ($numpad==0) print "7"; else print "10";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "8"; else print "20";?>);"><?php if ($numpad==0) print "8"; else print "20";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "9"; else print "50";?>);"><?php if ($numpad==0) print "9"; else print "50";?></button>
<?php
/*<button type="button" <?php if ($placeid==0) echo "disabled";?> class="calcbutton2" onclick="Validate('cash');"><?php echo $langs->trans("Cash"); ?></button>*/
 if (count($paiements) >0){ ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paiements[0]->code); ?>');"><?php echo $langs->trans('PaymentType'.$paiements[0]->code); ?></button>
<?php }else{ ?>
<button type="button" class="calcbutton2"><?php echo $langs->trans("NoPaimementModesDefined");?></button>
<?php } ?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "4"; else print "1";?>);"><?php if ($numpad==0) print "4"; else print "1";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "5"; else print "2";?>);"><?php if ($numpad==0) print "5"; else print "2";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "6"; else print "5";?>);"><?php if ($numpad==0) print "6"; else print "5";?></button>
<?php 
/*<button type="button" <?php if ($placeid==0) echo "disabled";?> class="calcbutton2" onclick="Validate('card');"><?php echo $langs->trans("PaymentTypeCB"); ?></button>*/
if (count($paiements) >1){ ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paiements[1]->code); ?>');"><?php echo $langs->trans('PaymentType'.$paiements[1]->code); ?></button>
<?php }else{ 
	$button = array_pop($action_buttons);
?>
	<button type="button" class="calcbutton2" onclick="<?php echo $button["function"];?>"><span <?php echo $button["span"];?>><?php echo $button["text"];?></span></button>
<?php } ?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "1"; else print "0.10";?>);"><?php if ($numpad==0) print "1"; else print "0.10";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "2"; else print "0.20";?>);"><?php if ($numpad==0) print "2"; else print "0.20";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "3"; else print "0.50";?>);"><?php if ($numpad==0) print "3"; else print "0.50";?></button>
<?php
/*<button type="button" <?php if ($placeid==0) echo "disabled";?> class="calcbutton2" onclick="Validate('cheque');"><?php echo $langs->trans("Cheque"); ?></button>*/
 if (count($paiements) >2){ ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paiements[2]->code); ?>');"><?php echo $langs->trans('PaymentType'.$paiements[2]->code); ?></button>
<?php }else{ 
	$button = array_pop($action_buttons);
?>
	<button type="button" class="calcbutton2" onclick="<?php echo $button["function"];?>"><span <?php echo $button["span"];?>><?php echo $button["text"];?></span></button>
<?php } ?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "0"; else print "0.01";?>);"><?php if ($numpad==0) print "0"; else print "0.01";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "'000'"; else print "0.02";?>);"><?php if ($numpad==0) print "000"; else print "0.02";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "'.'"; else print "0.05";?>);"><?php if ($numpad==0) print "."; else print "0.05";?></button>
<?php
/*<button type="button" class="calcbutton3" onclick="reset();"><span style='font-size: 150%;'>C</span></button>
<button type="button" class="calcbutton3" onclick="parent.$.colorbox.close();"><span id="printtext"><?php echo $langs->trans("GoBack"); ?></span></button>
*/
$i=3;
while($i < count($paiements)){
?>
	<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paiements[$i]->code); ?>');"><?php echo $langs->trans('PaymentType'.$paiements[$i]->code); ?></button>
<?php
	$i=$i+1;
}
$class=($i==3)?"calcbutton3":"calcbutton2";
foreach($action_buttons as $button){
?>
	<button type="button" class="<?php echo $class;?>" onclick="<?php echo $button["function"];?>"><span <?php echo $button["span"];?>><?php echo $button["text"];?></span></button>
<?php
}
?>
</div>

</body>
</html>
