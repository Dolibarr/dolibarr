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

$_GET['theme']="md"; // Force theme. MD theme provides better look and feel to TakePOS

require '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$place = GETPOST('place','int');


/*
 * View
 */

$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facture where facnumber='(PROV-POS-".$place.")'";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$placeid=$row[0];
if (! $placeid) $placeid=0; // Invoice not exist
else{
	$invoice = new Facture($db);
	$invoice->fetch($placeid);
}

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

$langs->loadLangs(array("main", "bills", "cashdesk"));
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
$numpad=$conf->global->TAKEPOS_NUMPAD;
?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "7"; else print "10";?>);"><?php if ($numpad==0) print "7"; else print "10";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "8"; else print "20";?>);"><?php if ($numpad==0) print "8"; else print "20";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "9"; else print "50";?>);"><?php if ($numpad==0) print "9"; else print "50";?></button>
<button type="button" <?php if ($placeid==0) echo "disabled";?> class="calcbutton2" onclick="Validate('cash');"><?php echo $langs->trans("Cash"); ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "4"; else print "1";?>);"><?php if ($numpad==0) print "4"; else print "1";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "5"; else print "2";?>);"><?php if ($numpad==0) print "5"; else print "2";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "6"; else print "5";?>);"><?php if ($numpad==0) print "6"; else print "5";?></button>
<button type="button" <?php if ($placeid==0) echo "disabled";?> class="calcbutton2" onclick="Validate('card');"><?php echo $langs->trans("PaymentTypeCB"); ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "1"; else print "0.10";?>);"><?php if ($numpad==0) print "1"; else print "0.10";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "2"; else print "0.20";?>);"><?php if ($numpad==0) print "2"; else print "0.20";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "3"; else print "0.50";?>);"><?php if ($numpad==0) print "3"; else print "0.50";?></button>
<button type="button" <?php if ($placeid==0) echo "disabled";?> class="calcbutton2" onclick="Validate('cheque');"><?php echo $langs->trans("Cheque"); ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "0"; else print "0.01";?>);"><?php if ($numpad==0) print "0"; else print "0.01";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "'000'"; else print "0.02";?>);"><?php if ($numpad==0) print "000"; else print "0.02";?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad==0) print "'.'"; else print "0.05";?>);"><?php if ($numpad==0) print "."; else print "0.05";?></button>
<button type="button" class="calcbutton3" onclick="reset();"><span style='font-size: 150%;'>C</span></button>
<button type="button" class="calcbutton3" onclick="parent.$.colorbox.close();"><span id="printtext"><?php echo $langs->trans("GoBack"); ?></span></button>
</div>

</body>
</html>
