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
 *	\file       htdocs/takepos/reduction.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter reductions
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER', '1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB', '1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN', '1');
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0); // $place is id of table for Ba or Restaurant

$invoiceid = GETPOST('invoiceid', 'int');

if (!$user->hasRight('takepos', 'run')) {
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

$head = '';
$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs  = array();

top_htmlhead($head, '', 0, 0, $arrayofjs, $arrayofcss);

$langs->loadLangs(array('main', 'bills', 'cashdesk'));

if (!isset($conf->global->TAKEPOS_NUMPAD_USE_PAYMENT_ICON) || getDolGlobalString('TAKEPOS_NUMPAD_USE_PAYMENT_ICON')) {
	$htmlReductionPercent = '<span class="fa fa-2x fa-percent"></span>';
	$htmlReductionAmount = '<span class="fa fa-2x fa-money"></span><br>'.$langs->trans('Amount');
} else {
	$htmlReductionPercent = $langs->trans('ReductionShort').'<br>%';
	$htmlReductionAmount = $langs->trans('ReductionShort').'<br>'.$langs->trans('Amount');
}
?>
<link rel="stylesheet" href="css/pos.css.php">
</head>
<body>

<script>
	var reductionType ='';
	var reductionTotal = '';
	var editAction = '';
	var editNumber = '';
	var htmlBtnOK = '<span style="font-size: 14pt;">OK</span>';
	var htmlReductionPercent = '<?php echo dol_escape_js($htmlReductionPercent); ?>';
	var htmlReductionAmount = '<?php echo dol_escape_js($htmlReductionAmount); ?>';

	/**
	 * Reset values
	 */
	function Reset()
	{
		reductionType = '';
		reductionTotal = '';
		editAction = '';
		editNumber = '';
		jQuery('#reduction_total').val(reductionTotal);
		jQuery("#reduction_type_percent").html(htmlReductionPercent);
		jQuery('#reduction_type_amount').html(htmlReductionAmount);
	}

	/**
	 * Edit action
	 *
	 * @param   {string}  number    Number pressed
	 */
	function Edit(number)
	{
		console.log('Edit ' + number);

		if (number === 'p') {
			if (editAction === 'p' && reductionType === 'percent'){
				ValidateReduction();
			} else {
				editAction = 'p';
			}
			reductionType = 'percent';
		} else if (number === 'a') {
			if (editAction === 'a' && reductionType === 'amount'){
				ValidateReduction();
			} else {
				editAction = 'a';
			}
			reductionType = 'amount';
		}

		if (editAction === 'p'){
			jQuery('#reduction_type_percent').html(htmlBtnOK);
			jQuery('#reduction_type_amount').html(htmlReductionAmount);
		} else if (editAction === 'a'){
			jQuery('#reduction_type_amount').html(htmlBtnOK);
			jQuery("#reduction_type_percent").html(htmlReductionPercent);
		} else {
			jQuery('#reduction_type_percent').html(htmlReductionPercent);
			jQuery('#reduction_type_amount').html(htmlReductionAmount);
		}
	}

	/**
	 * Add a number in reduction input
	 *
	 * @param   {string}    reductionNumber     Number pressed
	 */
	function AddReduction(reductionNumber)
	{
		console.log('AddReduction ' + reductionNumber);

		reductionTotal += String(reductionNumber);
		jQuery('#reduction_total').val(reductionTotal);
	}

	/**
	 * Validate a reduction
	 */
	function ValidateReduction()
	{
		console.log('ValidateReduction');
		reductionTotal = jQuery('#reduction_total').val();

		if (reductionTotal.length <= 0) {
			console.error('Error no reduction');
			return;
		}

		var reductionNumber = parseFloat(reductionTotal);
		if (isNaN(reductionNumber)) {
			console.error('Error not a valid number :', reductionNumber);
			return;
		}

		if (reductionType === 'percent') {
			var invoiceid = <?php echo($invoiceid > 0 ? $invoiceid : 0); ?>;
			parent.$("#poslines").load("invoice.php?action=update_reduction_global&token=<?php echo newToken(); ?>&place=<?php echo $place; ?>&number="+reductionNumber+"&invoiceid="+invoiceid, function() {
				Reset();
				parent.$.colorbox.close();
			});
		} else if (reductionType === 'amount') {
			var desc = "<?php echo dol_escape_js($langs->transnoentities('Reduction')); ?>";
			parent.$("#poslines").load("invoice.php?action=freezone&token=<?php echo newToken(); ?>&place=<?php echo $place; ?>&number=-"+reductionNumber+"&desc="+desc, function() {
				Reset();
				parent.$.colorbox.close();
			});
		} else {
			console.error('Error bad reduction type :', reductionType);
		}
	}
</script>

<div style="position:absolute; top:2%; left:5%; width:91%;">
<center>
<?php
	print '<input type="text" class="takepospay" id="reduction_total" name="reduction_total" style="width: 50%;" placeholder="'.$langs->trans('Reduction').'">';
?>
</center>
</div>

<div style="position:absolute; top:33%; left:5%; height:52%; width:92%;">
<?php

print '<button type="button" class="calcbutton" onclick="AddReduction(\'7\');">7</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'8\');">8</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'9\');">9</button>';
print '<button type="button" class="calcbutton2" id="reduction_type_percent" onclick="Edit(\'p\');">'.$htmlReductionPercent.'</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'4\');">4</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'5\');">5</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'6\');">6</button>';
print '<button type="button" class="calcbutton2" id="reduction_type_amount" onclick="Edit(\'a\');">'.$htmlReductionAmount.'</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'1\');">1</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'2\');">2</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'3\');">3</button>';
print '<button type="button" class="calcbutton3 poscolorblue" onclick="Reset();"><span id="printtext" style="font-weight: bold; font-size: 18pt;">C</span></button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'0\');">0</button>';
print '<button type="button" class="calcbutton" onclick="AddReduction(\'.\');">.</button>';
print '<button type="button" class="calcbutton">&nbsp;</button>';
print '<button type="button" class="calcbutton3 poscolordelete" onclick="parent.$.colorbox.close();"><span id="printtext" style="font-weight: bold; font-size: 18pt;">X</span></button>';

?>
</div>

</body>
</html>
