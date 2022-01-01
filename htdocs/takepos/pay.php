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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/pay.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter payments
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER', '1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB', '1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant

$invoiceid = GETPOST('invoiceid', 'int');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}


/*
 * View
 */

$invoice = new Facture($db);
if ($invoiceid > 0)
{
    $invoice->fetch($invoiceid);
}
else
{
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $invoiceid = $obj->rowid;
    }
    if (!$invoiceid)
    {
        $invoiceid = 0; // Invoice does not exist yet
    }
    else
    {
        $invoice->fetch($invoiceid);
    }
}

$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs = array();

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

$langs->loadLangs(array("main", "bills", "cashdesk"));

$sql = "SELECT code, libelle as label FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
        $paycode = $obj->code;
        if ($paycode == 'LIQ') $paycode = 'CASH';
        if ($paycode == 'CB')  $paycode = 'CB';
        if ($paycode == 'CHQ') $paycode = 'CHEQUE';

        $accountname = "CASHDESK_ID_BANKACCOUNT_".$paycode.$_SESSION["takeposterminal"];
		if (!empty($conf->global->$accountname) && $conf->global->$accountname > 0) array_push($paiements, $obj);
	}
}
?>
<link rel="stylesheet" href="css/pos.css.php">
<?php
if ($conf->global->TAKEPOS_COLOR_THEME == 1) print '<link rel="stylesheet" href="css/colorful.css">';
?>
</head>
<body>

<script>
<?php
$remaintopay = 0;
if ($invoice->id > 0)
{
    $remaintopay = $invoice->getRemainToPay();
}
$alreadypayed = (is_object($invoice) ? ($invoice->total_ttc - $remaintopay) : 0);

if ($conf->global->TAKEPOS_NUMPAD == 0) print "var received='';";
else print "var received=0;";

?>
	var alreadypayed = <?php echo $alreadypayed ?>;

	function addreceived(price)
	{
    	<?php
    	if (empty($conf->global->TAKEPOS_NUMPAD)) print 'received+=String(price);'."\n";
    	else print 'received+=parseFloat(price);'."\n";
    	?>
    	$('.change1').html(pricejs(parseFloat(received), 'MT'));
    	$('.change1').val(parseFloat(received));
		alreadypaydplusreceived=price2numjs(alreadypayed + parseFloat(received));
    	//console.log("already+received = "+alreadypaydplusreceived);
    	//console.log("total_ttc = "+<?php echo $invoice->total_ttc; ?>);
    	if (alreadypaydplusreceived > <?php echo $invoice->total_ttc; ?>)
   		{
			var change=parseFloat(alreadypayed + parseFloat(received) - <?php echo $invoice->total_ttc; ?>);
			$('.change2').html(pricejs(change, 'MT'));
	    	$('.change2').val(change);
	    	$('.change1').removeClass('colorred');
	    	$('.change1').addClass('colorgreen');
	    	$('.change2').removeClass('colorwhite');
	    	$('.change2').addClass('colorred');
		}
    	else
    	{
			$('.change2').html(pricejs(0, 'MT'));
	    	$('.change2').val(0);
	    	if (alreadypaydplusreceived == <?php echo $invoice->total_ttc; ?>)
	    	{
		    	$('.change1').removeClass('colorred');
		    	$('.change1').addClass('colorgreen');
	    		$('.change2').removeClass('colorred');
	    		$('.change2').addClass('colorwhite');
	    	}
	    	else
	    	{
		    	$('.change1').removeClass('colorgreen');
		    	$('.change1').addClass('colorred');
	    		$('.change2').removeClass('colorred');
	    		$('.change2').addClass('colorwhite');
	    	}
    	}
	}

	function reset()
	{
		received=0;
		$('.change1').html(pricejs(received, 'MT'));
		$('.change1').val(price2numjs(received));
		$('.change2').html(pricejs(received, 'MT'));
		$('.change2').val(price2numjs(received));
    	$('.change1').removeClass('colorgreen');
    	$('.change1').addClass('colorred');
    	$('.change2').removeClass('colorred');
    	$('.change2').addClass('colorwhite');
	}

	function Validate(payment)
	{
		var invoiceid = <?php echo ($invoiceid > 0 ? $invoiceid : 0); ?>;
		var amountpayed = $("#change1").val();
		if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
			amountpayed = <?php echo $invoice->total_ttc; ?>;
		}
		console.log("We click on the payment mode to pay amount = "+amountpayed);
		parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay="+payment+"&amount="+amountpayed+"&invoiceid="+invoiceid, function() {
		    if (amountpayed > <?php echo $remaintopay; ?> || amountpayed == <?php echo $remaintopay; ?> || amountpayed==0 ) parent.$.colorbox.close();
			else location.reload();
		});
	}

	function ValidateSumup() {
		console.log("Launch ValidateSumup");
		<?php $_SESSION['SMP_CURRENT_PAYMENT'] = "NEW" ?>
        var invoiceid = <?php echo($invoiceid > 0 ? $invoiceid : 0); ?>;
        var amountpayed = $("#change1").val();
        if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
            amountpayed = <?php echo $invoice->total_ttc; ?>;
        }

        // Starting sumup app
        window.open('sumupmerchant://pay/1.0?affiliate-key=<?php echo $conf->global->TAKEPOS_SUMUP_AFFILIATE ?>&app-id=<?php echo $conf->global->TAKEPOS_SUMUP_APPID ?>&total=' + amountpayed + '&currency=EUR&title=' + invoiceid + '&callback=<?php echo DOL_MAIN_URL_ROOT ?>/takepos/smpcb.php');

        var loop = window.setInterval(function () {
            $.ajax('/takepos/smpcb.php?status').done(function (data) {
                console.log(data);
                if (data === "SUCCESS") {
                    parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay=CB&amount=" + amountpayed + "&invoiceid=" + invoiceid, function () {
                        //parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
                        parent.$.colorbox.close();
                        //parent.setFocusOnSearchField();	// This does not have effect
                    });
                    clearInterval(loop);
                } else if (data === "FAILED") {
                    parent.$.colorbox.close();
                    clearInterval(loop);
                }
            });
        }, 2500);
    }
</script>

<div style="position:relative; padding-top: 10px; left:5%; height:150px; width:91%;">
<center>
<div class="paymentbordline paymentbordlinetotal">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans('TotalTTC'); ?>: </font><span id="totaldisplay" class="colorwhite"><?php echo price($invoice->total_ttc, 1, '', 1, -1, -1) ?></span></font></span></center>
</div>
<?php if ($remaintopay != $invoice->total_ttc) { ?>
<div class="paymentbordline paymentbordlineremain">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans('RemainToPay'); ?>: </font><span id="remaintopaydisplay" class="colorwhite"><?php echo price($remaintopay, 1, '', 1, -1, -1) ?></span></font></span></center>
</div>
<?php } ?>
<div class="paymentbordline paymentbordlinereceived">
    <center><span class="takepospay"><font color="white"><?php echo $langs->trans("Received"); ?>: </font><span class="change1 colorred"><?php echo price(0) ?></span><input type="hidden" id="change1" class="change1" value="0"></font></span></center>
</div>
<div class="paymentbordline paymentbordlinechange">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans("Change"); ?>: </font><span class="change2 colorwhite"><?php echo price(0) ?></span><input type="hidden" id="change2" class="change2" value="0"></font></span></center>
</div>
</center>
</div>

<div style="position:absolute; left:5%; height:52%; width:92%;">
<?php
$action_buttons = array(
	array(
		"function" =>"reset()",
		"span" => "style='font-size: 150%;'",
		"text" => "C",
	    "class" => "poscolorblue"
	),
	array(
		"function" => "parent.$.colorbox.close();",
		"span" => "id='printtext' style='font-weight: bold; font-size: 18pt;'",
		"text" => "X",
	    "class" => "poscolordelete"
	),
);
$numpad = $conf->global->TAKEPOS_NUMPAD;

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '7' : '10').');">'.($numpad == 0 ? '7' : '10').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '8' : '20').');">'.($numpad == 0 ? '8' : '20').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '9' : '50').');">'.($numpad == 0 ? '9' : '50').'</button>';
?>
<?php if (count($paiements) > 0) {
    $paycode = $paiements[0]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		$paycode = 'cash';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'coins';
	} elseif ($paycode == 'CB') {
		$paycode = 'card';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'credit-card';
	} elseif ($paycode == 'CHQ') {
		$paycode = 'cheque';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'money-check';
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.$langs->trans($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.'"></span>' : $langs->trans("PaymentTypeShort".$paiements[0]->code)).'</button>';
} else {
	print '<button type="button" class="calcbutton2">'.$langs->trans("NoPaimementModesDefined").'</button>';
}

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '4' : '1').');">'.($numpad == 0 ? '4' : '1').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '5' : '2').');">'.($numpad == 0 ? '5' : '2').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '6' : '5').');">'.($numpad == 0 ? '6' : '5').'</button>';
?>
<?php if (count($paiements) > 1) {
    $paycode = $paiements[1]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		$paycode = 'cash';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'coins';
	} elseif ($paycode == 'CB') {
		$paycode = 'card';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'credit-card';
	} elseif ($paycode == 'CHQ') {
		$paycode = 'cheque';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'money-check';
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.$langs->trans($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.'"></span>' : $langs->trans("PaymentTypeShort".$paiements[1]->code)).'</button>';
} else {
	$button = array_pop($action_buttons);
	print '<button type="button" class="calcbutton2" onclick="'.$button["function"].'"><span '.$button["span"].'>'.$button["text"].'</span></button>';
}

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '1' : '0.10').');">'.($numpad == 0 ? '1' : '0.10').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '2' : '0.20').');">'.($numpad == 0 ? '2' : '0.20').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '3' : '0.50').');">'.($numpad == 0 ? '3' : '0.50').'</button>';
?>
<?php if (count($paiements) > 2) {
    $paycode = $paiements[2]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		$paycode = 'cash';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'coins';
	} elseif ($paycode == 'CB') {
		$paycode = 'card';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'credit-card';
	} elseif ($paycode == 'CHQ') {
		$paycode = 'cheque';
		if (!empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON))	$payIcon = 'money-check';
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.$langs->trans($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.'"></span>' : $langs->trans("PaymentTypeShort".$paiements[2]->code)).'</button>';
} else {
    $button = array_pop($action_buttons);
	print '<button type="button" class="calcbutton2" onclick="'.$button["function"].'"><span '.$button["span"].'>'.$button["text"].'</span></button>';
}

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '0' : '0.01').');">'.($numpad == 0 ? '0' : '0.01').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '\'000\'' : '0.02').');">'.($numpad == 0 ? '000' : '0.02').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '\'.\'' : '0.05').');">'.($numpad == 0 ? '.' : '0.05').'</button>';

$i = 3;
while ($i < count($paiements)) {
	print '<button type="button" class="calcbutton2" onclick="Validate(\''.$langs->trans($paiements[$i]->code).'\');">'.$langs->trans("PaymentTypeShort".$paiements[$i]->code).'</button>';
	$i = $i + 1;
}

$keyforsumupbank = "CASHDESK_ID_BANKACCOUNT_SUMUP".$_SESSION["takeposterminal"];
if ($conf->global->TAKEPOS_ENABLE_SUMUP) {
	if (!empty($conf->global->$keyforsumupbank)) {
		print '<button type="button" class="calcbutton2" onclick="ValidateSumup();">Sumup</button>';
	} else {
		$langs->loadLangs(array("errors", "admin"));
		print '<button type="button" class="calcbutton2 disabled" title="'.$langs->trans("SetupNotComplete").'">Sumup</button>';
	}
}

$class = ($i == 3) ? "calcbutton3" : "calcbutton2";
foreach ($action_buttons as $button) {
    $newclass = $class.($button["class"] ? " ".$button["class"] : "");
	print '<button type="button" class="'.$newclass.'" onclick="'.$button["function"].'"><span '.$button["span"].'>'.$button["text"].'</span></button>';
}
?>
</div>

</body>
</html>
