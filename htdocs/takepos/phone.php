<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2024   Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2025	MDW					<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/takepos/phone.php
 *	\ingroup    takepos
 *	\brief      TakePOS page for smartphone to allow a customer to take an order himself.
 *              This page is called by takepos/public/auto_order.php that set INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE when a customer access to the auto_order page.
 */

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
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

if (!defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
	print 'This page must be called by takepos/public/auto_order.php';
	exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

// Decode place if it is an order from a customer phone
$place = GETPOSTISSET("key") ? dol_decode(GETPOST('key')) : GETPOST('place', 'aZ09');

$action = GETPOST('action', 'aZ09');
$setterminal = GETPOSTINT('setterminal');
$idproduct = GETPOSTINT('idproduct');
$mobilepage = GETPOST('mobilepage', 'alphanohtml');	// Set when page is loaded by a js .load()

if ($setterminal > 0) {
	$_SESSION["takeposterminal"] = $setterminal;
}

$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk", "receiptprinter"));


/*
 * View
 */

$title = 'TakePOS - Dolibarr '.DOL_VERSION;
if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
	$title = 'TakePOS - ' . getDolGlobalString('MAIN_APPLICATION_TITLE');
}

// llxHeader
if (empty($mobilepage)) {
	$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
	$arrayofcss = array(
		'/takepos/css/phone.css',
	);
	$arrayofjs = array('/takepos/js/jquery.colorbox-min.js');
	$disablejs = 0;
	$disablehead = 0;
	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

	print '<body style="background-color:#D1D1D1;">'."\n";
} else {
	top_htmlhead('', $title);

	print '<body>'."\n";
}


if ($action == "productinfo" && $user->hasRight('takepos', 'run')) {
	$prod = new Product($db);
	$prod->fetch($idproduct);
	print '<button type="button" class="publicphonebutton2 phoneblue total" onclick="AddProductConfirm(place, '.$idproduct.')">'.$langs->trans('Add').'</button>';
	print "<br><b>".$prod->label."</b><br>";
	print '<img class="imgwrapper" width="60%" src="'.DOL_URL_ROOT.'/takepos/public/auto_order.php?genimg=pro&query=pro&id='.$idproduct.'">';
	print "<br>".$prod->description;
	print "<br><b>".price($prod->price_ttc, 1, $langs, 1, -1, -1, getDolCurrency())."</b>";
	print '<br>';
} elseif ($action == "editline" && $user->hasRight('takepos', 'run')) {
	$placeid = GETPOSTINT('placeid');
	$selectedline = GETPOSTINT('selectedline');
	$invoice = new Facture($db);
	$invoice->fetch($placeid);
	foreach ($invoice->lines as $line) {
		if ($line->id == $selectedline) {
			$prod = new Product($db);
			$prod->fetch($line->fk_product);
			print "<b>".$prod->label."</b><br>";
			print '<img class="imgwrapper" width="60%" src="'.DOL_URL_ROOT.'/takepos/public/auto_order.php?genimg=pro&query=pro&id='.$line->fk_product.'">';
			print "<br>".$prod->description;
			print "<br><b>".price($prod->price_ttc, 1, $langs, 1, -1, -1, getDolCurrency())."</b>";
			print '<br>';
			print '<button type="button" class="publicphonebutton2 phonered width24" onclick="SetQty(place, '.$selectedline.', '.($line->qty - 1).')">-</button>';
			print '<button type="button" class="publicphonebutton2 phonegreen width24" onclick="SetQty(place, '.$selectedline.', '.($line->qty + 1).')">+</button>';
			print '<button type="button" class="publicphonebutton2 phoneblue width24" onclick="SetNote(place, '.$selectedline.')">'.$langs->trans('Note').'</button>';
		}
	}
} else {
	?>
	<div class="container">
		<div class="phonebuttonsrow center">
		<?php
			print '<button type="button" class="publicphonebutton phoneblue uppercase" onclick="LoadCats();">'.dol_trunc($langs->trans('Categories'), 8, 'right', 'UTF-8').'</button>';
			print '<button type="button" class="publicphonebutton phoneorange uppercase" onclick="PublicPreOrder();">'.dol_trunc($langs->trans('Order'), 8, 'right', 'UTF-8').'</button>';
			// Do not show the payment link when order done from public page (a customer must not be able to set its order to paid himself)
			//print '<button type="button" class="publicphonebutton phonegreen uppercase" onclick="CheckPlease();">'.dol_trunc($langs->trans('Payment'), 8, 'right', 'UTF-8').'</button>';
		?>
		</div>
		<div class="phonerow2">
			<div id="phonediv2" class="phonediv2"></div>
		</div>
		<div class="phonerow1">
			<div id="phonediv1" class="phonediv1"></div>
		</div>
	</div>

	<script type="text/javascript">
	<?php
	$categorie = new Categorie($db);
	$categories = $categorie->get_full_arbo('product', getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID'), 1);

	// Search root category to know its level
	//$conf->global->TAKEPOS_ROOT_CATEGORY_ID=0;
	$levelofrootcategory = 0;
	if (getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID') > 0) {
		foreach ($categories as $key => $categorycursor) {
			if ($categorycursor['id'] == getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID')) {
				$levelofrootcategory = $categorycursor['level'];
				break;
			}
		}
	}
	$levelofmaincategories = $levelofrootcategory + 1;

	$maincategories = array();
	$subcategories = array();
	foreach ($categories as $key => $categorycursor) {
		if ($categorycursor['level'] == $levelofmaincategories) {
			$maincategories[$key] = $categorycursor;
		} else {
			$subcategories[$key] = $categorycursor;
		}
	}

	sort($maincategories);
	sort($subcategories);
	?>

	var categories = <?php echo json_encode($maincategories); ?>;
	var subcategories = <?php echo json_encode($subcategories); ?>;

	var currentcat;
	var pageproducts=0;
	var pagecategories=0;
	var pageactions=0;
	var place="<?php echo $place; ?>";
	var editaction="qty";
	var editnumber="";


	$( document ).ready(function() {
		console.log("Refresh");
		LoadPlace(place);
	});

	function LoadPlace(placeid){
		place=placeid;
		<?php
		echo '$("#phonediv2").load("'.DOL_URL_ROOT.'/takepos/public/auto_order.php?mobilepage=invoice&place="+place+" #tablelines", function() {
			});';
		?>
		LoadCats();
	}

	function AddProduct(placeid, productid){
		<?php
		// If is a public terminal first show product information
		print 'place=placeid;
			$("#phonediv1").load("'.DOL_URL_ROOT.'/takepos/public/auto_order.php?action=productinfo&token='.newToken().'&place="+place+"&idproduct="+productid, function() {
			});';
		?>
	}

	function PublicPreOrder(){
		$("#phonediv1").load("<?php echo DOL_URL_ROOT.'/takepos/public/auto_order.php?action=publicpreorder&token='.newToken(); ?>&place="+place, function() {
		});
	}

	function AddProductConfirm(placeid, productid){
		place=placeid;
		<?php
			echo '$("#phonediv2").load("'.DOL_URL_ROOT.'/takepos/public/auto_order.php?mobilepage=invoice&action=addline&token='.newToken().'&place="+place+"&idproduct="+productid, function() {
			});';
		?>

		return true;
	}

	function SetQty(place, selectedline, qty){
		console.log("We click on SetQty()");
		if (qty==0){
			$("#phonediv2").load("<?php echo DOL_URL_ROOT ?>/takepos/public/auto_order.php?mobilepage=invoice&action=deleteline&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline, function() {
			});
		}
		else{
			$("#phonediv2").load("<?php echo DOL_URL_ROOT ?>/takepos/public/auto_order.php?mobilepage=invoice&action=updateqty&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&number="+qty, function() {
			});
		}
		LoadCats();

		return true;
	}

	function SetNote(place, selectedline){
		console.log("We click on SetNote()");
		var note = prompt("<?php dol_escape_js($langs->trans('Note')); ?>", "");
		$("#phonediv2").load("<?php echo DOL_URL_ROOT ?>/takepos/public/auto_order.php?mobilepage=invoice&action=updateqty&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&number="+qty, function() {
		});
		LoadCats();
	}

	function LoadCats(){
		console.log("We click on LoadCats()");
		<?php
			// Load invoice.php to get categories by using auto_order so it will define INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE
			echo '$("#phonediv1").load("'.DOL_URL_ROOT.'/takepos/public/auto_order.php?format=div&mobilepage=cats&place="+place+" #tablelines", function() {
			});';
		?>
	}

	function LoadProducts(idcat) {
		console.log("We click on LoadProducts()");
		<?php
			echo '$("#phonediv1").load("'.DOL_URL_ROOT.'/takepos/public/auto_order.php?format=div&mobilepage=products&catid="+idcat+"&place="+place+"", function() {
			});';
		?>
	}

	function LoadPlacesList(){
		$("#phonediv1").load("invoice.php?mobilepage=places", function() {
		});
	}

	function TakeposPrintingOrder(){
		console.log("TakeposPrintingOrder");
		<?php
		echo '$("#phonediv2").load("'.DOL_URL_ROOT.'/takepos/public/auto_order.php?action=order&token='.newToken().'&mobilepage=order&place="+place, function() {
			});';
		?>
	}

	function Exit(){
		console.log("Click on Exit");
		window.location.href='<?php echo DOL_URL_ROOT ?>/user/logout.php?token=<?php echo newToken(); ?>';
	}

	function CheckPlease(payment){
		console.log("Click on CheckPlease");
		if (payment==undefined){
			$("#phonediv1").load("<?php echo DOL_URL_ROOT ?>/takepos/public/auto_order.php?action=checkplease&token=<?php echo newToken(); ?>&place="+place, function() {
			});
		}
		else{
			console.log("Request the check to the waiter");
			$("#phonediv1").load("<?php echo DOL_URL_ROOT ?>/takepos/public/auto_order.php?action=checkplease&token=<?php echo newToken(); ?>&place=<?php echo urlencode($place); ?>&payment="+payment, function() {
			});
		}
	}

	</script>


	<?php
	if (getDolGlobalString('TAKEPOS_NUM_TERMINALS') != "1" && $_SESSION["takeposterminal"] == "") {
		print '<div class="dialog-info-takepos-terminal" id="dialog-info" title="TakePOS">'.$langs->trans('TerminalSelect').'</div>';
	}
}

print '</body>';
print '</html>';


$db->close();
