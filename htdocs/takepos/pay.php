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
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array("main", "bills", "cashdesk", "banks"));

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant

$invoiceid = GETPOST('invoiceid', 'int');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}


/*
 * View
 */

$invoice = new Facture($db);
if ($invoiceid > 0) {
	$invoice->fetch($invoiceid);
} else {
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	if ($obj) {
		$invoiceid = $obj->rowid;
	}
	if (!$invoiceid) {
		$invoiceid = 0; // Invoice does not exist yet
	} else {
		$invoice->fetch($invoiceid);
	}
}

$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs = array();

$head = '';
$title = '';
$disablejs = 0;
$disablehead = 0;

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

// Define list of possible payments
$arrayOfValidPaymentModes = array();
$arrayOfValidBankAccount = array();

$sql = "SELECT code, libelle as label FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);

if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$paycode = $obj->code;
		if ($paycode == 'LIQ') {
			$paycode = 'CASH';
		}
		if ($paycode == 'CB') {
			$paycode = 'CB';
		}
		if ($paycode == 'CHQ') {
			$paycode = 'CHEQUE';
		}

		$accountname = "CASHDESK_ID_BANKACCOUNT_".$paycode.$_SESSION["takeposterminal"];
		if (!empty($conf->global->$accountname) && $conf->global->$accountname > 0) {
			$arrayOfValidBankAccount[$conf->global->$accountname] = $conf->global->$accountname;
			$arrayOfValidPaymentModes[] = $obj;
		}
		if (!isModEnabled('banque')) {
			if ($paycode == 'CASH' || $paycode == 'CB') $arrayOfValidPaymentModes[] = $obj;
		}
	}
}
?>
<link rel="stylesheet" href="css/pos.css.php">
<?php
if ($conf->global->TAKEPOS_COLOR_THEME == 1) {
	print '<link rel="stylesheet" href="css/colorful.css">';
}
?>
</head>
<body>

<script>
<?php
$remaintopay = 0;
if ($invoice->id > 0) {
	$remaintopay = $invoice->getRemainToPay();
}
$alreadypayed = (is_object($invoice) ? ($invoice->total_ttc - $remaintopay) : 0);

if ($conf->global->TAKEPOS_NUMPAD == 0) {
	print "var received='';";
} else {
	print "var received=0;";
}

?>
	var alreadypayed = <?php echo $alreadypayed ?>;

	function addreceived(price)
	{
		<?php
		if (empty($conf->global->TAKEPOS_NUMPAD)) {
			print 'received+=String(price);'."\n";
		} else {
			print 'received+=parseFloat(price);'."\n";
		}
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
		var accountid = $("#selectaccountid").val();
		var amountpayed = $("#change1").val();
		var excess = $("#change2").val();
		if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
			amountpayed = <?php echo $invoice->total_ttc; ?>;
		}
		console.log("We click on the payment mode to pay amount = "+amountpayed);
		parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay="+payment+"&amount="+amountpayed+"&excess="+excess+"&invoiceid="+invoiceid+"&accountid="+accountid, function() {
			if (amountpayed > <?php echo $remaintopay; ?> || amountpayed == <?php echo $remaintopay; ?> || amountpayed==0 ) {
				console.log("Close popup");
				parent.$.colorbox.close();
			}
			else {
				console.log("Amount is not comple, so we do NOT close popup and reload it.");
				location.reload();
			}
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
			$.ajax({
				method: 'POST',
				data: { token: '<?php echo currentToken(); ?>' },
				url: '<?php echo DOL_URL_ROOT ?>/takepos/smpcb.php?status' }).done(function (data) {
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

<?php
if (!empty($conf->global->TAKEPOS_CUSTOMER_DISPLAY)) {
	echo "var line1='".$langs->trans('TotalTTC')."'.substring(0,20);";
	echo "line1=line1.padEnd(20);";
	echo "var line2='".price($invoice->total_ttc, 1, '', 1, -1, -1)."'.substring(0,20);";
	echo "line2=line2.padEnd(20);";
	echo "$.ajax({
		type: 'GET',
		data: { text: line1+line2 },
		url: '".getDolGlobalString('TAKEPOS_PRINT_SERVER')."/display/index.php',
	});";
}
?>
</script>

<div style="position:relative; padding-top: 20px; left:5%; height:150px; width:90%;">
	<div class="paymentbordline paymentbordlinetotal center">
		<span class="takepospay colorwhite"><?php echo $langs->trans('TotalTTC'); ?>: <span id="totaldisplay" class="colorwhite"><?php echo price($invoice->total_ttc, 1, '', 1, -1, -1, $invoice->multicurrency_code); ?></span></span>
	</div>
	<?php if ($remaintopay != $invoice->total_ttc) { ?>
		<div class="paymentbordline paymentbordlineremain center">
			<span class="takepospay colorwhite"><?php echo $langs->trans('RemainToPay'); ?>: <span id="remaintopaydisplay" class="colorwhite"><?php echo price($remaintopay, 1, '', 1, -1, -1, $invoice->multicurrency_code); ?></span></span>
		</div>
	<?php } ?>
	<div class="paymentbordline paymentbordlinereceived center">
		<span class="takepospay colorwhite"><?php echo $langs->trans("Received"); ?>: <span class="change1 colorred"><?php echo price(0, 1, '', 1, -1, -1, $invoice->multicurrency_code); ?></span><input type="hidden" id="change1" class="change1" value="0"></span>
	</div>
	<div class="paymentbordline paymentbordlinechange center">
		<span class="takepospay colorwhite"><?php echo $langs->trans("Change"); ?>: <span class="change2 colorwhite"><?php echo price(0, 1, '', 1, -1, -1, $invoice->multicurrency_code); ?></span><input type="hidden" id="change2" class="change2" value="0"></span>
	</div>
	<?php
	if (!empty($conf->global->TAKEPOS_CAN_FORCE_BANK_ACCOUNT_DURING_PAYMENT)) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		print '<div class="paymentbordline paddingtop paddingbottom center">';
		$filter = '';
		$form = new Form($db);
		print '<span class="takepospay colorwhite">'.$langs->trans("BankAccount").': </span>';
		$form->select_comptes(0, 'accountid', 0, $filter, 1, '');
		print ajax_combobox('selectaccountid');
		print '</div>';
	}
	?>
</div>

<div style="position:absolute; left:5%; height:52%; width:90%;">
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
<?php if (count($arrayOfValidPaymentModes) > 0) {
	$paycode = $arrayOfValidPaymentModes[0]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'coins';
		}
	} elseif ($paycode == 'CB') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'credit-card';
		}
	} elseif ($paycode == 'CHQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'money-check';
		}
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.dol_escape_js($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.' iconwithlabel"></span><span class="hideonsmartphone"><br>'.$langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[0]->code) : $langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[0]->code)).'</span></button>';
} else {
	print '<button type="button" class="calcbutton2">'.$langs->trans("NoPaimementModesDefined").'</button>';
}

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '4' : '1').');">'.($numpad == 0 ? '4' : '1').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '5' : '2').');">'.($numpad == 0 ? '5' : '2').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '6' : '5').');">'.($numpad == 0 ? '6' : '5').'</button>';
?>
<?php if (count($arrayOfValidPaymentModes) > 1) {
	$paycode = $arrayOfValidPaymentModes[1]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'coins';
		}
	} elseif ($paycode == 'CB') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'credit-card';
		}
	} elseif ($paycode == 'CHQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'money-check';
		}
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.dol_escape_js($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.' iconwithlabel"></span><br> '.$langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[1]->code) : $langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[1]->code)).'</button>';
} else {
	$button = array_pop($action_buttons);
	print '<button type="button" class="calcbutton2" onclick="'.$button["function"].'"><span '.$button["span"].'>'.$button["text"].'</span></button>';
}

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '1' : '0.10').');">'.($numpad == 0 ? '1' : '0.10').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '2' : '0.20').');">'.($numpad == 0 ? '2' : '0.20').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '3' : '0.50').');">'.($numpad == 0 ? '3' : '0.50').'</button>';
?>
<?php if (count($arrayOfValidPaymentModes) > 2) {
	$paycode = $arrayOfValidPaymentModes[2]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'coins';
		}
	} elseif ($paycode == 'CB') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'credit-card';
		}
	} elseif ($paycode == 'CHQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'money-check';
		}
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.dol_escape_js($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.' iconwithlabel"></span><br>'.$langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[2]->code) : $langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[2]->code)).'</button>';
} else {
	$button = array_pop($action_buttons);
	print '<button type="button" class="calcbutton2" onclick="'.$button["function"].'"><span '.$button["span"].'>'.$button["text"].'</span></button>';
}

print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '0' : '0.01').');">'.($numpad == 0 ? '0' : '0.01').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '\'000\'' : '0.02').');">'.($numpad == 0 ? '000' : '0.02').'</button>';
print '<button type="button" class="calcbutton" onclick="addreceived('.($numpad == 0 ? '\'.\'' : '0.05').');">'.($numpad == 0 ? '.' : '0.05').'</button>';

$i = 3;
while ($i < count($arrayOfValidPaymentModes)) {
	$paycode = $arrayOfValidPaymentModes[$i]->code;
	$payIcon = '';
	if ($paycode == 'LIQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'coins';
		}
	} elseif ($paycode == 'CB') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'credit-card';
		}
	} elseif ($paycode == 'CHQ') {
		if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || !empty($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON)) {
			$payIcon = 'money-check';
		}
	}

	print '<button type="button" class="calcbutton2" onclick="Validate(\''.dol_escape_js($paycode).'\');">'.(!empty($payIcon) ? '<span class="fa fa-2x fa-'.$payIcon.' iconwithlabel"></span><br>'.$langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[$i]->code) : $langs->trans("PaymentTypeShort".$arrayOfValidPaymentModes[$i]->code)).'</button>';
	$i = $i + 1;
}

$keyforsumupbank = "CASHDESK_ID_BANKACCOUNT_SUMUP".$_SESSION["takeposterminal"];
if (getDolGlobalInt('TAKEPOS_ENABLE_SUMUP')) {
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

if ($conf->global->TAKEPOS_DELAYED_PAYMENT) {
	print '<button type="button" class="calcbutton2" onclick="Validate(\'delayed\');">'.$langs->trans("Reported").'</button>';
}
?>
</div>

</body>
</html>
