<?php
/* Copyright (C) 2019	Thibault FOUCART <support@ptibogxiv.net>
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
 *	\file       htdocs/takepos/send.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$invoiceid = GETPOST('facid', 'int');

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

$langs->loadLangs(array("main", "bills", "cashdesk"));

?>
<link rel="stylesheet" href="css/pos.css">
</head>
<body class="center">

<script>
<?php
$remaintopay = 0;
$invoice->fetch_thirdparty($invoice->socid);
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

function Print(id){
    $.colorbox.close();
    $.colorbox({href:"receipt.php?facid="+id, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php
    echo $langs->trans("PrintTicket"); ?>"});
}

</script>

<div class="center">
<center>
<center><input type="email" id="email" name="email" style="width:60%;font-size: 200%;" value="<?php echo $invoice->thirdparty->email; ?>"></center>
</center>
</div>
<br>
<div class="center">

<button type="button" class="calcbutton" onclick="addreceived();"><?php print $langs->trans("SendInvoice"); ?></button>

</div>

</body>
</html>
