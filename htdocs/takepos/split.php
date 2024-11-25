<?php
/* Copyright (C) 2021		Andreu Bisquerra		<jove@bisquerra.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/takepos/split.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to split sale
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */
$langs->loadLangs(array("main", "bills", "cashdesk", "banks"));

$action = GETPOST('action', 'aZ09');
$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0);

if (!$user->hasRight('takepos', 'run')) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == "split" && $user->hasRight('takepos', 'run')) {
	$line = GETPOSTINT('line');
	$split = GETPOSTINT('split');
	if ($split==1) { // Split line
		$invoice = new Facture($db);
		$ret = $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-SPLIT)');
		if ($ret > 0) {
			$placeid = $invoice->id;
		} else {
			$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
			$invoice->socid =getDolGlobalInt($constforcompanyid);
			$invoice->date = dol_now();
			$invoice->module_source = 'takepos';
			$invoice->pos_source = $_SESSION["takeposterminal"];
			$invoice->entity = !empty($_SESSION["takeposinvoiceentity"]) ? $_SESSION["takeposinvoiceentity"] : $conf->entity;
			if ($invoice->socid <= 0) {
				$langs->load('errors');
				dol_htmloutput_errors($langs->trans("ErrorModuleSetupNotComplete", "TakePos"), [], 1);
			} else {
				$placeid = $invoice->create($user);
				if ($placeid < 0) {
					dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
				}
				$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET ref='(PROV-POS".$_SESSION["takeposterminal"]."-SPLIT)'";
				$sql .= " WHERE rowid = ".((int) $placeid);
				$db->query($sql);
			}
		}
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET fk_facture = ".((int) $placeid)." WHERE rowid = ".((int) $line);
		$db->query($sql);
	} elseif ($split==0) { // Unsplit line
		$invoice = new Facture($db);
		if ($place == "SPLIT") {
			$place = "0";
		} // Avoid move line to the same place (from SPLIT to SPLIT place)
		$ret = $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')');
		if ($ret > 0) {
			$placeid = $invoice->id;
		} else {
			$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
			$invoice->socid = getDolGlobalInt($constforcompanyid);
			$invoice->date = dol_now();
			$invoice->module_source = 'takepos';
			$invoice->pos_source = $_SESSION["takeposterminal"];
			$invoice->entity = !empty($_SESSION["takeposinvoiceentity"]) ? $_SESSION["takeposinvoiceentity"] : $conf->entity;
			if ($invoice->socid <= 0) {
				$langs->load('errors');
				dol_htmloutput_errors($langs->trans("ErrorModuleSetupNotComplete", "TakePos"), [], 1);
			} else {
				$placeid = $invoice->create($user);
				if ($placeid < 0) {
					dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
				}

				$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
				$sql .= " WHERE rowid = ".((int) $placeid);
				$db->query($sql);
			}
		}
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set fk_facture=".$placeid." where rowid=".$line;
		$db->query($sql);
	}
	$invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-SPLIT)');
	$invoice->update_price();

	$invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')');
	$invoice->update_price();
}


/*
 * View
 */

$invoice = new Facture($db);
if (isset($invoiceid) && $invoiceid > 0) {
	$invoice->fetch($invoiceid);
} else {
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	if ($obj) {
		$invoiceid = $obj->rowid;
	}
	if (!isset($invoiceid)) {
		$invoiceid = 0; // Invoice does not exist yet
	} else {
		$invoice->fetch($invoiceid);
	}
}

$arrayofcss = array('/takepos/css/pos.css.php');
if (getDolGlobalInt('TAKEPOS_COLOR_THEME') == 1) {
	$arrayofcss[] = '/takepos/css/colorful.css';
}
$arrayofjs = array();

$head = '';
$title = '';
$disablejs = 0;
$disablehead = 0;

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

// Define list of possible payments
$arrayOfValidPaymentModes = array();
$arrayOfValidBankAccount = array();

?>
<body class="takepossplitphp">

<script>
function Split(selectedline, split) {
	$.ajax({
		url: "split.php?action=split&token=<?php echo newToken(); ?>&line="+selectedline+"&split="+split+"&place=<?php echo $place;?>",
		context: document.body
	}).done(function() {
		$("#currentplace").load("invoice.php?place="+parent.place+"&invoiceid="+parent.invoiceid, function() {
			$('#currentplace').find('.posinvoiceline').click(function(){
				Split(this.id, 1);
			});
		});
		$("#splitplace").load("invoice.php?place=SPLIT", function() {
			$('#splitplace').find('.posinvoiceline').click(function(){
				Split(this.id, 0);
			});
		});
	});
}

$( document ).ready(function() {
	if (parent.place=='SPLIT') {
		parent.place=0;
		parent.invoiceid=0;
		parent.Refresh();
	}
	$("#currentplace").load("invoice.php?place="+parent.place+"&invoiceid="+parent.invoiceid, function() {
		$('#currentplace').find('.posinvoiceline')
		.click(function(){
			Split(this.id, 1);
		});
	});

	$("#splitplace").load("invoice.php?place=SPLIT", function() {
		$('#splitplace').find('.posinvoiceline').click(function(){
			Split(this.id, 0);
		});
	});



	$("#headersplit1").html("<?php echo $langs->trans("Place");?> "+parent.place);
	$("#headersplit2").html("<?php echo $langs->trans("SplitSale");?>");

});
</script>

<div class="headersplit">
  <a href="#" onclick="top.location.href='index.php?place='+parent.place"><div class="headercontent" id="headersplit1"></div></a>
</div>

<div class="rowsplit">
  <div class="splitsale" id="currentplace"></div>
</div>

<div class="headersplit">
  <a href="#" onclick="top.location.href='index.php?place=SPLIT'"><div class="headercontent" id="headersplit2"></div></a>
</div>

<div class="rowsplit">
  <div class="splitsale" id="splitplace"></div>
</div>

</body>
</html>
