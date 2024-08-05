<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	Josep Lluís Amador	<joseplluis@lliuretic.cat>
 * Copyright (C) 2020	Thibault FOUCART	<support@ptibogxiv.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *    \file       htdocs/takepos/index.php
 *    \ingroup    takepos
 *    \brief      Main TakePOS screen
 */

// if (! defined('NOREQUIREUSER')) 		define('NOREQUIREUSER','1'); 		// Not disabled cause need to load personalized language
// if (! defined('NOREQUIREDB')) 		define('NOREQUIREDB','1'); 			// Not disabled cause need to load personalized language
// if (! defined('NOREQUIRESOC')) 		define('NOREQUIRESOC','1');
// if (! defined('NOREQUIRETRAN')) 		define('NOREQUIRETRAN','1');

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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0); // $place is id of table for Bar or Restaurant or multiple sales
$action = GETPOST('action', 'aZ09');
$setterminal = GETPOSTINT('setterminal');
$setcurrency = GETPOST('setcurrency', 'aZ09');

$hookmanager->initHooks(array('takeposfrontend'));
if (empty($_SESSION["takeposterminal"])) {
	if (getDolGlobalInt('TAKEPOS_NUM_TERMINALS') == 1) {
		$_SESSION["takeposterminal"] = 1; // Use terminal 1 if there is only 1 terminal
	} elseif (!empty($_COOKIE["takeposterminal"])) {
		$_SESSION["takeposterminal"] = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE["takeposterminal"]); // Restore takeposterminal from previous session
	}
}

if ($setterminal > 0) {
	$_SESSION["takeposterminal"] = $setterminal;
	setcookie("takeposterminal", (string) $setterminal, (time() + (86400 * 354)), '/', '', (empty($dolibarr_main_force_https) ? false : true), true); // Permanent takeposterminal var in a cookie
}

if ($setcurrency != "") {
	$_SESSION["takeposcustomercurrency"] = $setcurrency;
	// We will recalculate amount for foreign currency at next call of invoice.php when $_SESSION["takeposcustomercurrency"] differs from invoice->multicurrency_code.
}


$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk", "receiptprinter", "banks"));

$categorie = new Categorie($db);

$maxcategbydefaultforthisdevice = 12;
$maxproductbydefaultforthisdevice = 24;
if ($conf->browser->layout == 'phone') {
	$maxcategbydefaultforthisdevice = 8;
	$maxproductbydefaultforthisdevice = 16;
	//REDIRECT TO BASIC LAYOUT IF TERMINAL SELECTED AND BASIC MOBILE LAYOUT FORCED
	if (!empty($_SESSION["takeposterminal"]) && getDolGlobalString('TAKEPOS_BAR_RESTAURANT') && getDolGlobalInt('TAKEPOS_PHONE_BASIC_LAYOUT') == 1) {
		$_SESSION["basiclayout"] = 1;
		header("Location: phone.php?mobilepage=invoice");
		exit;
	}
} else {
	unset($_SESSION["basiclayout"]);
}
$MAXCATEG = (!getDolGlobalString('TAKEPOS_NB_MAXCATEG') ? $maxcategbydefaultforthisdevice : $conf->global->TAKEPOS_NB_MAXCATEG);
$MAXPRODUCT = (!getDolGlobalString('TAKEPOS_NB_MAXPRODUCT') ? $maxproductbydefaultforthisdevice : $conf->global->TAKEPOS_NB_MAXPRODUCT);

$term = empty($_SESSION['takeposterminal']) ? 1 : $_SESSION['takeposterminal'];

/*
 $constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
 $soc = new Societe($db);
 if ($invoice->socid > 0) $soc->fetch($invoice->socid);
 else $soc->fetch(getDolGlobalInt($constforcompanyid));
 */

// Security check
$result = restrictedArea($user, 'takepos', 0, '');



/*
 * View
 */

$form = new Form($db);

$disablejs = 0;
$disablehead = 0;
$arrayofjs = array('/takepos/js/jquery.colorbox-min.js'); // TODO It seems we don't need this
$arrayofcss = array('/takepos/css/pos.css.php', '/takepos/css/colorbox.css');

if (getDolGlobalInt('TAKEPOS_COLOR_THEME') == 1) {
	$arrayofcss[] =  '/takepos/css/colorful.css';
}


// Title
$title = 'TakePOS - Dolibarr '.DOL_VERSION;
if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
	$title = 'TakePOS - ' . getDolGlobalString('MAIN_APPLICATION_TITLE');
}
$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);


$categories = $categorie->get_full_arbo('product', ((getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID') > 0) ? getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID') : 0), 1);


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

$maincategories = dol_sort_array($maincategories, 'label');
$subcategories = dol_sort_array($subcategories, 'label');
?>

<body class="bodytakepos" style="overflow: hidden;">

<script>
var categories = <?php echo json_encode($maincategories); ?>;
var subcategories = <?php echo json_encode($subcategories); ?>;

var currentcat;
var pageproducts=0;
var pagecategories=0;
var pageactions=0;
var place="<?php echo $place; ?>";
var editaction="qty";
var editnumber="";
var invoiceid=0;
var search2_timer=null;

/*
var app = this;
app.hasKeyboard = false;
this.keyboardPress = function() {
	app.hasKeyboard = true;
	$(window).unbind("keyup", app.keyboardPress);
	localStorage.hasKeyboard = true;
	console.log("has keyboard!")
}
$(window).on("keyup", app.keyboardPress)
if(localStorage.hasKeyboard) {
	app.hasKeyboard = true;
	$(window).unbind("keyup", app.keyboardPress);
	console.log("has keyboard from localStorage")
}
*/

function ClearSearch(clearSearchResults) {
	console.log("ClearSearch");
	$("#search").val('');
	$("#qty").html("<?php echo $langs->trans("Qty"); ?>").removeClass('clicked');
	$("#price").html("<?php echo $langs->trans("Price"); ?>").removeClass('clicked');
	$("#reduction").html("<?php echo $langs->trans("LineDiscountShort"); ?>").removeClass('clicked');
	<?php if ($conf->browser->layout == 'classic') { ?>
	setFocusOnSearchField();
	<?php } ?>
	if (clearSearchResults) {
		$("#search").trigger('keyup');
	}
}

// Set the focus on search field but only on desktop. On tablet or smartphone, we don't to avoid to have the keyboard open automatically
function setFocusOnSearchField() {
	console.log("Call setFocusOnSearchField in page index.php");
	<?php if ($conf->browser->layout == 'classic') { ?>
		console.log("has keyboard from localStorage, so we can force focus on search field");
		$("#search").focus();
	<?php } ?>
}

function PrintCategories(first) {
	console.log("PrintCategories");
	for (i = 0; i < <?php echo($MAXCATEG - 2); ?>; i++) {
		if (typeof (categories[parseInt(i)+parseInt(first)]) == "undefined")
		{
			$("#catdivdesc"+i).hide();
			$("#catdesc"+i).text("");
			$("#catimg"+i).attr("src","genimg/empty.png");
			$("#catwatermark"+i).hide();
			$("#catdiv"+i).attr('class', 'wrapper divempty');
			continue;
		}
		$("#catdivdesc"+i).show();
		<?php
		if (getDolGlobalString('TAKEPOS_SHOW_CATEGORY_DESCRIPTION') == 1) { ?>
			$("#catdesc"+i).html(categories[parseInt(i)+parseInt(first)]['label'].bold() + ' - ' + categories[parseInt(i)+parseInt(first)]['description']);
		<?php } else { ?>
			$("#catdesc"+i).text(categories[parseInt(i)+parseInt(first)]['label']);
		<?php }	?>
		$("#catimg"+i).attr("src","genimg/index.php?query=cat&id="+categories[parseInt(i)+parseInt(first)]['rowid']);
		$("#catdiv"+i).data("rowid",categories[parseInt(i)+parseInt(first)]['rowid']);
		$("#catdiv"+i).attr("data-rowid",categories[parseInt(i)+parseInt(first)]['rowid']);
		$("#catdiv"+i).attr('class', 'wrapper');
		$("#catwatermark"+i).show();
	}
}

function MoreCategories(moreorless) {
	console.log("MoreCategories moreorless="+moreorless+" pagecategories="+pagecategories);
	if (moreorless == "more") {
		$('#catimg15').animate({opacity: '0.5'}, 1);
		$('#catimg15').animate({opacity: '1'}, 100);
		pagecategories=pagecategories+1;
	}
	if (moreorless == "less") {
		$('#catimg14').animate({opacity: '0.5'}, 1);
		$('#catimg14').animate({opacity: '1'}, 100);
		if (pagecategories==0) return; //Return if no less pages
		pagecategories=pagecategories-1;
	}
	if (typeof (categories[<?php echo($MAXCATEG - 2); ?> * pagecategories] && moreorless == "more") == "undefined") { // Return if no more pages
		pagecategories=pagecategories-1;
		return;
	}

	for (i = 0; i < <?php echo($MAXCATEG - 2); ?>; i++) {
		if (typeof (categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]) == "undefined") {
			// complete with empty record
			console.log("complete with empty record");
			$("#catdivdesc"+i).hide();
			$("#catdesc"+i).text("");
			$("#catimg"+i).attr("src","genimg/empty.png");
			$("#catwatermark"+i).hide();
			continue;
		}
		$("#catdivdesc"+i).show();
		<?php
		if (getDolGlobalString('TAKEPOS_SHOW_CATEGORY_DESCRIPTION') == 1) { ?>
			$("#catdesc"+i).html(categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]['label'].bold() + ' - ' + categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]['description']);
		<?php } else { ?>
			$("#catdesc"+i).text(categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]['label']);
		<?php } ?>
		$("#catimg"+i).attr("src","genimg/index.php?query=cat&id="+categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
		$("#catdiv"+i).data("rowid",categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
		$("#catdiv"+i).attr("data-rowid",categories[i+(<?php echo($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
		$("#catwatermark"+i).show();
	}

	ClearSearch(false);
}

// LoadProducts
function LoadProducts(position, issubcat) {
	console.log("LoadProducts position="+position+" issubcat="+issubcat);
	var maxproduct = <?php echo($MAXPRODUCT - 2); ?>;

	if (position=="supplements") {
		currentcat="supplements";
	} else {
		$('#catimg'+position).animate({opacity: '0.5'}, 1);
		$('#catimg'+position).animate({opacity: '1'}, 100);
		if (issubcat == true) {
			currentcat=$('#prodiv'+position).data('rowid');
		} else {
			console.log('#catdiv'+position);
			currentcat=$('#catdiv'+position).data('rowid');
			console.log("currentcat="+currentcat);
		}
	}
	if (currentcat == undefined) {
		return;
	}
	pageproducts=0;
	ishow=0; //product to show counter

	jQuery.each(subcategories, function(i, val) {
		if (currentcat==val.fk_parent) {
			$("#prodivdesc"+ishow).show();
			<?php if (getDolGlobalString('TAKEPOS_SHOW_CATEGORY_DESCRIPTION') == 1) { ?>
				$("#prodesc"+ishow).html(val.label.bold() + ' - ' + val.description);
			   $("#probutton"+ishow).html(val.label);
			<?php } else { ?>
				$("#prodesc"+ishow).text(val.label);
			  $("#probutton"+ishow).text(val.label);
			<?php } ?>
			$("#probutton"+ishow).show();
			$("#proprice"+ishow).attr("class", "hidden");
			$("#proprice"+ishow).html("");
			$("#proimg"+ishow).attr("src","genimg/index.php?query=cat&id="+val.rowid);
			$("#prodiv"+ishow).data("rowid",val.rowid);
			$("#prodiv"+ishow).attr("data-rowid",val.rowid);
			$("#prodiv"+ishow).data("iscat",1);
			$("#prodiv"+ishow).attr("data-iscat",1);
			$("#prowatermark"+ishow).show();
			ishow++;
		}
	});

	idata=0; //product data counter
	var limit = 0;
	if (maxproduct >= 1) {
		limit = maxproduct-1;
	}
	// Only show products for sale (tosell=1)
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getProducts&token=<?php echo newToken();?>&thirdpartyid=' + jQuery('#thirdpartyid').val() + '&category='+currentcat+'&tosell=1&limit='+limit+'&offset=0', function(data) {
		console.log("Call ajax.php (in LoadProducts) to get Products of category "+currentcat+" then loop on result to fill image thumbs");
		console.log(data);

		while (ishow < maxproduct) {
			console.log("ishow"+ishow+" idata="+idata);
			//console.log(data[idata]);

			if (typeof (data[idata]) == "undefined") {
				<?php if (!getDolGlobalString('TAKEPOS_HIDE_PRODUCT_IMAGES')) {
					echo '$("#prodivdesc"+ishow).hide();';
					echo '$("#prodesc"+ishow).text("");';
					echo '$("#proimg"+ishow).attr("title","");';
					echo '$("#proimg"+ishow).attr("src","genimg/empty.png");';
				} else {
					echo '$("#probutton"+ishow).hide();';
					echo '$("#probutton"+ishow).text("");';
				}?>
				$("#proprice"+ishow).attr("class", "hidden");
				$("#proprice"+ishow).html("");

				$("#prodiv"+ishow).data("rowid","");
				$("#prodiv"+ishow).attr("data-rowid","");

				$("#prodiv"+ishow).attr("class","wrapper2 divempty");
			} else  {
				<?php
					$titlestring = "'".dol_escape_js($langs->transnoentities('Ref').': ')."' + data[idata]['ref']";
				$titlestring .= " + ' - ".dol_escape_js($langs->trans("Barcode").': ')."' + data[idata]['barcode']";
				?>
				var titlestring = <?php echo $titlestring; ?>;
				<?php if (!getDolGlobalString('TAKEPOS_HIDE_PRODUCT_IMAGES')) {
					echo '$("#prodivdesc"+ishow).show();';
					if (getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE') == 1) {
						echo '$("#prodesc"+ishow).html(data[parseInt(idata)][\'ref\'].bold() + \' - \' + data[parseInt(idata)][\'label\']);';
					} elseif (getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE') == 2) {
						echo '$("#prodesc"+ishow).html(data[parseInt(idata)][\'ref\'].bold());';
					} else {
						echo '$("#prodesc"+ishow).html(data[parseInt(idata)][\'label\']);';
					}
					echo '$("#proimg"+ishow).attr("title", titlestring);';
					echo '$("#proimg"+ishow).attr("src", "genimg/index.php?query=pro&id="+data[idata][\'id\']);';
				} else {
					echo '$("#probutton"+ishow).show();';
					echo '$("#probutton"+ishow).html(data[parseInt(idata)][\'label\']);';
				}
				?>
				if (data[parseInt(idata)]['price_formated']) {
					$("#proprice" + ishow).attr("class", "productprice");
					<?php
					if (getDolGlobalInt('TAKEPOS_CHANGE_PRICE_HT')) {
						?>
						$("#proprice" + ishow).html(data[parseInt(idata)]['price_formated']);
						<?php
					} else {
						?>
						$("#proprice" + ishow).html(data[parseInt(idata)]['price_ttc_formated']);
						<?php
					}
					?>
				}
				console.log("#prodiv"+ishow+".data(rowid)="+data[idata]['id']);

				$("#prodiv"+ishow).data("rowid", data[idata]['id']);
				$("#prodiv"+ishow).attr("data-rowid", data[idata]['id']);
				console.log($('#prodiv4').data('rowid'));

				$("#prodiv"+ishow).data("iscat", 0);
				$("#prodiv"+ishow).attr("data-iscat", 0);

				$("#prodiv"+ishow).attr("class","wrapper2");

				<?php
				// Add js from hooks
				$parameters = array();
				$parameters['caller'] = 'loadProducts';
				$hookmanager->executeHooks('completeJSProductDisplay', $parameters);
				print $hookmanager->resPrint;
				?>
			}
			$("#prowatermark"+ishow).hide();
			ishow++; //Next product to show after print data product
			idata++; //Next data every time
		}
	});

	ClearSearch(false);
}

function MoreProducts(moreorless) {
	console.log("MoreProducts");

	if ($('#search_pagination').val() != '') {
		return Search2('<?php echo(isset($keyCodeForEnter) ? $keyCodeForEnter : ''); ?>', moreorless);
	}

	var maxproduct = <?php echo($MAXPRODUCT - 2); ?>;

	if (moreorless=="more"){
		$('#proimg31').animate({opacity: '0.5'}, 1);
		$('#proimg31').animate({opacity: '1'}, 100);
		pageproducts=pageproducts+1;
	}
	if (moreorless=="less"){
		$('#proimg30').animate({opacity: '0.5'}, 1);
		$('#proimg30').animate({opacity: '1'}, 100);
		if (pageproducts==0) return; //Return if no less pages
		pageproducts=pageproducts-1;
	}

	ishow=0; //product to show counter
	idata=0; //product data counter
	var limit = 0;
	if (maxproduct >= 1) {
		limit = maxproduct-1;
	}
	var offset = <?php echo($MAXPRODUCT - 2); ?> * pageproducts;
	// Only show products for sale (tosell=1)
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getProducts&token=<?php echo newToken();?>&category='+currentcat+'&tosell=1&limit='+limit+'&offset='+offset, function(data) {
		console.log("Call ajax.php (in MoreProducts) to get Products of category "+currentcat);

		if (typeof (data[0]) == "undefined" && moreorless=="more"){ // Return if no more pages
			pageproducts=pageproducts-1;
			return;
		}

		while (ishow < maxproduct) {
			if (typeof (data[idata]) == "undefined") {
				$("#prodivdesc"+ishow).hide();
				$("#prodesc"+ishow).text("");
				$("#probutton"+ishow).text("");
				$("#probutton"+ishow).hide();
				$("#proprice"+ishow).attr("class", "");
				$("#proprice"+ishow).html("");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				$("#prodiv"+ishow).attr("data-rowid","");
			} else {
				$("#prodivdesc"+ishow).show();
				<?php if (getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE') == 1) { ?>
					$("#prodesc"+ishow).html(data[parseInt(idata)]['ref'].bold() + ' - ' + data[parseInt(idata)]['label']);
				<?php } elseif (getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE') == 2) { ?>
					$("#prodesc"+ishow).html(data[parseInt(idata)]['ref'].bold());
				<?php } else { ?>
					$("#prodesc"+ishow).html(data[parseInt(idata)]['label']);
				<?php } ?>
				$("#probutton"+ishow).html(data[parseInt(idata)]['label']);
				$("#probutton"+ishow).show();
				if (data[parseInt(idata)]['price_formated']) {
					$("#proprice" + ishow).attr("class", "productprice");
					<?php
					if (getDolGlobalInt('TAKEPOS_CHANGE_PRICE_HT')) {
						?>
						$("#proprice" + ishow).html(data[parseInt(idata)]['price_formated']);
						<?php
					} else {
						?>
						$("#proprice" + ishow).html(data[parseInt(idata)]['price_ttc_formated']);
						<?php
					}
					?>
				}
				$("#proimg"+ishow).attr("src","genimg/index.php?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid",data[idata]['id']);
				$("#prodiv"+ishow).attr("data-rowid",data[idata]['id']);
				$("#prodiv"+ishow).data("iscat",0);
			}
			$("#prowatermark"+ishow).hide();
			ishow++; //Next product to show after print data product
			idata++; //Next data every time
		}
	});

	ClearSearch(false);
}

function ClickProduct(position, qty = 1) {
	console.log("ClickProduct at position"+position);
	$('#proimg'+position).animate({opacity: '0.5'}, 1);
	$('#proimg'+position).animate({opacity: '1'}, 100);
	if ($('#prodiv'+position).data('iscat')==1){
		console.log("Click on a category at position "+position);
		LoadProducts(position, true);
	}
	else{
		console.log($('#prodiv4').data('rowid'));
		invoiceid = $("#invoiceid").val();
		idproduct=$('#prodiv'+position).data('rowid');
		console.log("Click on product at position "+position+" for idproduct "+idproduct+", qty="+qty+" invoicdeid="+invoiceid);
		if (idproduct=="") return;
		// Call page invoice.php to generate the section with product lines
		if (invoiceid == "") {
				createNewInvoice(idproduct, qty);
			} else {
				$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getInvoice&token=<?php echo newToken(); ?>&id=' + invoiceid, function (data) {
					if (data['paye'] == 1 && data['status'] == <?php echo Facture::STATUS_CLOSED; ?>) {
						console.log("Creating new invoice");
						createNewInvoice(idproduct, qty);
					} else if (data['paye'] == 0 && data['status'] == <?php echo Facture::STATUS_DRAFT; ?>) {
						console.log("Adding product to invoice");
						addProductToInvoice(idproduct, qty, invoiceid);
					} else if (data['paye'] == 0 && data['status'] == <?php echo Facture::STATUS_VALIDATED; ?>) {
						console.log("Invoice not completely paid");
						alert('Invoice not completely paid !');
					}
				});
			}
	}

	ClearSearch(false);
}

function createNewInvoice(idproduct, qty) {
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=createNewInvoice&token=<?php echo newToken();?>', function (data) {
		invoiceid = data['invoiceid'];
		$("#invoiceid").val(invoiceid);
		addProductToInvoice(idproduct, qty, invoiceid);
	});
}

function addProductToInvoice(idproduct, qty, invoiceid) {
	$("#poslines").load("invoice.php?action=addline&token=<?php echo newToken() ?>&place="+place+"&idproduct="+idproduct+"&qty="+qty+"&invoiceid="+invoiceid, function() {
		<?php if (getDolGlobalString('TAKEPOS_CUSTOMER_DISPLAY')) {
			echo "CustomerDisplay();";
		}?>
	});
}

function ChangeThirdparty(idcustomer) {
	 console.log("ChangeThirdparty");
		// Call page list.php to change customer
		$("#poslines").load("<?php echo DOL_URL_ROOT ?>/societe/list.php?action=change&token=<?php echo newToken();?>&type=t&contextpage=poslist&idcustomer="+idcustomer+"&place="+place+"", function() {
		});

	ClearSearch(false);
}

function deleteline() {
	invoiceid = $("#invoiceid").val();
	console.log("Delete line invoiceid="+invoiceid);
	$("#poslines").load("invoice.php?action=deleteline&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&invoiceid="+invoiceid, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
	ClearSearch(false);
}

function Customer() {
	console.log("Open box to select the thirdparty place="+place);
	$.colorbox({href:"../societe/list.php?type=t&contextpage=poslist&nomassaction=1&place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Customer"); ?>"});
}

function Contact() {
	console.log("Open box to select the contact place="+place);
	$.colorbox({href:"../contact/list.php?type=c&contextpage=poslist&nomassaction=1&place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Contact"); ?>"});
}

function History()
{
	console.log("Open box to select the history");
	$.colorbox({href:"../compta/facture/list.php?contextpage=poslist", width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("History"); ?>"});
}

function Reduction() {
	invoiceid = $("#invoiceid").val();
	console.log("Open popup to enter reduction on invoiceid="+invoiceid);
	$.colorbox({href:"reduction.php?place="+place+"&invoiceid="+invoiceid, width:"80%", height:"90%", transition:"none", iframe:"true", title:""});
}

function CloseBill() {
	<?php
	if (!empty($conf->global->TAKEPOS_FORBID_SALES_TO_DEFAULT_CUSTOMER)) {
		echo "customerAnchorTag = document.querySelector('a[id=\"customer\"]'); ";
		echo "if (customerAnchorTag && customerAnchorTag.innerText.trim() === '".$langs->trans("Customer")."') { ";
		echo "alert('".$langs->trans("NoClientErrorMessage")."'); ";
		echo "return; } \n";
	}
	?>
	invoiceid = $("#invoiceid").val();
	console.log("Open popup to enter payment on invoiceid="+invoiceid);
	<?php if (getDolGlobalInt("TAKEPOS_NO_GENERIC_THIRDPARTY")) { ?>
		if ($("#idcustomer").val() == "") {
			alert("<?php echo $langs->trans('TakePosCustomerMandatory'); ?>");
			<?php if (getDolGlobalString('TAKEPOS_CHOOSE_CONTACT')) { ?>
				Contact();
			<?php } else { ?>
				Customer();
			<?php } ?>
			return;
		}
	<?php }	?>
	<?php
	$alternative_payurl = getDolGlobalString('TAKEPOS_ALTERNATIVE_PAYMENT_SCREEN');
	if (empty($alternative_payurl)) {
		$payurl = "pay.php";
	} else {
		$payurl = dol_buildpath($alternative_payurl, 1);
	}
	?>
	$.colorbox({href:"<?php echo $payurl; ?>?place="+place+"&invoiceid="+invoiceid, width:"80%", height:"90%", transition:"none", iframe:"true", title:""});
}

function Split() {
	invoiceid = $("#invoiceid").val();
	console.log("Open popup to split on invoiceid="+invoiceid);
	$.colorbox({href:"split.php?place="+place+"&invoiceid="+invoiceid, width:"80%", height:"90%", transition:"none", iframe:"true", title:""});
}

function Floors() {
	console.log("Open box to select floor place="+place);
	$.colorbox({href:"floors.php?place="+place, width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Floors"); ?>"});
}

function FreeZone() {
	invoiceid = $("#invoiceid").val();
	console.log("Open box to enter a free product on invoiceid="+invoiceid);
	$.colorbox({href:"freezone.php?action=freezone&token=<?php echo newToken(); ?>&place="+place+"&invoiceid="+invoiceid, width:"80%", height:"40%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("FreeZone"); ?>"});
}

function TakeposOrderNotes() {
	console.log("Open box to order notes");
	ModalBox('ModalNote');
	$("#textinput").focus();
}

function Refresh() {
	console.log("Refresh by reloading place="+place+" invoiceid="+invoiceid);
	$("#poslines").load("invoice.php?place="+place+"&invoiceid="+invoiceid, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function New() {
	// If we go here,it means $conf->global->TAKEPOS_BAR_RESTAURANT is not defined
	invoiceid = $("#invoiceid").val();		// This is a hidden field added by invoice.php

	console.log("New with place = <?php echo $place; ?>, js place="+place+", invoiceid="+invoiceid);

	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getInvoice&token=<?php echo newToken();?>&id='+invoiceid, function(data) {
		var r;

		if (parseInt(data['paye']) === 1) {
			r = true;
		} else {
			r = confirm('<?php echo($place > 0 ? $langs->transnoentitiesnoconv("ConfirmDeletionOfThisPOSSale") : $langs->transnoentitiesnoconv("ConfirmDiscardOfThisPOSSale")); ?>');
		}

		if (r == true) {
			// Reload section with invoice lines
			$("#poslines").load("invoice.php?action=delete&token=<?php echo newToken(); ?>&place=" + place, function () {
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});

			ClearSearch(false);
			$("#idcustomer").val("");
		}
	});
}

/**
 * Search products
 *
 * @param   keyCodeForEnter     Key code for "enter" or '' if not
 * @param   moreorless          "more" or "less"
 * return   void
 */
function Search2(keyCodeForEnter, moreorless) {
	var eventKeyCode = window.event.keyCode;

	console.log("Search2 Call ajax search to replace products keyCodeForEnter="+keyCodeForEnter+", eventKeyCode="+eventKeyCode);

	var search_term  = $('#search').val();
	var search_start = 0;
	var search_limit = <?php echo $MAXPRODUCT - 2; ?>;
	if (moreorless != null) {
		search_term = $('#search_pagination').val();
		search_start = $('#search_start_'+moreorless).val();
	}

	console.log("search_term="+search_term);

	if (search_term == '') {
		$("[id^=prowatermark]").html("");
		$("[id^=prodesc]").text("");
		$("[id^=probutton]").text("");
		$("[id^=probutton]").hide();
		$("[id^=proprice]").attr("class", "hidden");
		$("[id^=proprice]").html("");
		$("[id^=proimg]").attr("src", "genimg/empty.png");
		$("[id^=prodiv]").data("rowid", "");
		$("[id^=prodiv]").attr("data-rowid", "");
		return;
	}

	var search = false;
	if (keyCodeForEnter == '' || eventKeyCode == keyCodeForEnter) {
		search = true;
	}

	if (search === true) {
		// if a timer has been already started (search2_timer is a global js variable), we cancel it now
		// we click onto another key, we will restart another timer just after
		if (search2_timer) {
			clearTimeout(search2_timer);
		}

		// temporization time to give time to type
		search2_timer = setTimeout(function(){
			pageproducts = 0;
			jQuery(".wrapper2 .catwatermark").hide();
			var nbsearchresults = 0;
			$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=search&token=<?php echo newToken();?>&term=' + search_term + '&thirdpartyid=' + jQuery('#thirdpartyid').val() + '&search_start=' + search_start + '&search_limit=' + search_limit, function (data) {
				for (i = 0; i < <?php echo $MAXPRODUCT ?>; i++) {
					if (typeof (data[i]) == "undefined") {
						$("#prowatermark" + i).html("");
						$("#prodesc" + i).text("");
						$("#probutton" + i).text("");
						$("#probutton" + i).hide();
						$("#proprice" + i).attr("class", "hidden");
						$("#proprice" + i).html("");
						$("#proimg" + i).attr("src", "genimg/empty.png");
						$("#prodiv" + i).data("rowid", "");
						$("#prodiv" + i).attr("data-rowid", "");
						continue;
					}
					<?php
					$titlestring = "'".dol_escape_js($langs->transnoentities('Ref').': ')."' + data[i]['ref']";
					$titlestring .= " + ' - ".dol_escape_js($langs->trans("Barcode").': ')."' + data[i]['barcode']";
					?>
					var titlestring = <?php echo $titlestring; ?>;
					<?php if (getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE') == 1) { ?>
						$("#prodesc" + i).html(data[i]['ref'].bold() + ' - ' + data[i]['label']);
					<?php } elseif (getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE') == 2) { ?>
						$("#prodesc" + i).html(data[i]['ref'].bold());
					<?php } else { ?>
						$("#prodesc" + i).html(data[i]['label']);
					<?php } ?>
					$("#prodivdesc" + i).show();
					$("#probutton" + i).html(data[i]['label']);
					$("#probutton" + i).show();
					if (data[i]['price_formated']) {
						$("#proprice" + i).attr("class", "productprice");
						<?php
						if (getDolGlobalInt('TAKEPOS_CHANGE_PRICE_HT')) {
							?>
							$("#proprice" + i).html(data[i]['price_formated']);
							<?php
						} else {
							?>
							$("#proprice" + i).html(data[i]['price_ttc_formated']);
							<?php
						}
						?>
					}
					$("#proimg" + i).attr("title", titlestring);
					if( undefined !== data[i]['img']) {
						$("#proimg" + i).attr("src", data[i]['img']);
					}
					else {
						$("#proimg" + i).attr("src", "genimg/index.php?query=pro&id=" + data[i]['rowid']);
					}
					$("#prodiv" + i).data("rowid", data[i]['rowid']);
					$("#prodiv" + i).attr("data-rowid", data[i]['rowid']);
					$("#prodiv" + i).data("iscat", 0);
					$("#prodiv" + i).attr("data-iscat", 0);

					<?php
					// Add js from hooks
					$parameters = array();
					$parameters['caller'] = 'search2';
					$hookmanager->executeHooks('completeJSProductDisplay', $parameters);
					print $hookmanager->resPrint;
					?>

					nbsearchresults++;
				}
			}).always(function (data) {
				// If there is only 1 answer
				if ($('#search').val().length > 0 && data.length == 1) {
					console.log($('#search').val()+' - '+data[0]['barcode']);
					if ($('#search').val() == data[0]['barcode'] && 'thirdparty' == data[0]['object']) {
						console.log("There is only 1 answer with barcode matching the search, so we change the thirdparty "+data[0]['rowid']);
						ChangeThirdparty(data[0]['rowid']);
					}
					else if ($('#search').val() == data[0]['barcode'] && 'product' == data[0]['object']) {
						console.log("There is only 1 answer and we found search on a barcode, so we add the product in basket, qty="+data[0]['qty']);
						ClickProduct(0, data[0]['qty']);
					}
				}
				if (eventKeyCode == keyCodeForEnter){
					if (data.length == 0) {
						$('#search').val('<?php
						$langs->load('errors');
						echo dol_escape_js($langs->transnoentitiesnoconv("ErrorRecordNotFoundShort"));
						?> ('+search_term+')');
						$('#search').select();
					}
					else ClearSearch(false);
				}
				// memorize search_term and start for pagination
				$("#search_pagination").val($("#search").val());
				if (search_start == 0) {
					$("#prodiv<?php echo $MAXPRODUCT - 2; ?> span").hide();
				}
				else {
					$("#prodiv<?php echo $MAXPRODUCT - 2; ?> span").show();
					var search_start_less = Math.max(0, parseInt(search_start) - parseInt(<?php echo $MAXPRODUCT - 2;?>));
					$("#search_start_less").val(search_start_less);
				}
				if (nbsearchresults != <?php echo $MAXPRODUCT - 2; ?>) {
					$("#prodiv<?php echo $MAXPRODUCT - 1; ?> span").hide();
				}
				else {
					$("#prodiv<?php echo $MAXPRODUCT - 1; ?> span").show();
					var search_start_more = parseInt(search_start) + parseInt(<?php echo $MAXPRODUCT - 2;?>);
					$("#search_start_more").val(search_start_more);
				}
			});
		}, 500); // 500ms delay
	}

}

/* Function called on an action into the PAD */
function Edit(number) {
	console.log("We click on PAD on key="+number);

	if (typeof(selectedtext) == "undefined") {
		return;	// We click on an action on the number pad but there is no line selected
	}

	var text=selectedtext+"<br> ";


	if (number=='c') {
		editnumber='';
		Refresh();
		$("#qty").html("<?php echo $langs->trans("Qty"); ?>").removeClass('clicked');
		$("#price").html("<?php echo $langs->trans("Price"); ?>").removeClass('clicked');
		$("#reduction").html("<?php echo $langs->trans("LineDiscountShort"); ?>").removeClass('clicked');
		return;
	} else if (number=='qty') {
		if (editaction=='qty' && editnumber != '') {
			$("#poslines").load("invoice.php?action=updateqty&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
				$("#qty").html("<?php echo $langs->trans("Qty"); ?>").removeClass('clicked');
			});

			setFocusOnSearchField();
			return;
		}
		else {
			editaction="qty";
		}
	} else if (number=='p') {
		if (editaction=='p' && editnumber!="") {
			$("#poslines").load("invoice.php?action=updateprice&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
				$("#price").html("<?php echo $langs->trans("Price"); ?>").removeClass('clicked');
			});

			ClearSearch(false);
			return;
		}
		else {
			editaction="p";
		}
	} else if (number=='r') {
		if (editaction=='r' && editnumber!="") {
			$("#poslines").load("invoice.php?action=updatereduction&token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
				$("#reduction").html("<?php echo $langs->trans("LineDiscountShort"); ?>").removeClass('clicked');
			});

			ClearSearch(false);
			return;
		}
		else {
			editaction="r";
		}
	}
	else {
		editnumber=editnumber+number;
	}
	if (editaction=='qty'){
		text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("Qty").": "; ?>";
		$("#qty").html("OK").addClass("clicked");
		$("#price").html("<?php echo $langs->trans("Price"); ?>").removeClass('clicked');
		$("#reduction").html("<?php echo $langs->trans("LineDiscountShort"); ?>").removeClass('clicked');
	}
	if (editaction=='p'){
		text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("Price").": "; ?>";
		$("#qty").html("<?php echo $langs->trans("Qty"); ?>").removeClass('clicked');
		$("#price").html("OK").addClass("clicked");
		$("#reduction").html("<?php echo $langs->trans("LineDiscountShort"); ?>").removeClass('clicked');
	}
	if (editaction=='r'){
		text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("LineDiscountShort").": "; ?>";
		$("#qty").html("<?php echo $langs->trans("Qty"); ?>").removeClass('clicked');
		$("#price").html("<?php echo $langs->trans("Price"); ?>").removeClass('clicked');
		$("#reduction").html("OK").addClass("clicked");
	}
	$('#'+selectedline).find("td:first").html(text+editnumber);
}


function TakeposPrintingOrder(){
	console.log("TakeposPrintingOrder");
	$("#poslines").load("invoice.php?action=order&token=<?php echo newToken();?>&place="+place, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function TakeposPrintingTemp(){
	console.log("TakeposPrintingTemp");
	$("#poslines").load("invoice.php?action=temp&token=<?php echo newToken();?>&place="+place, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function OpenDrawer(){
	console.log("OpenDrawer call ajax url http://<?php print getDolGlobalString('TAKEPOS_PRINT_SERVER'); ?>:8111/print");
	$.ajax({
		type: "POST",
		data: { token: 'notrequired' },
		<?php
		if (getDolGlobalString('TAKEPOS_PRINT_SERVER') && filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) {
			echo "url: '".getDolGlobalString('TAKEPOS_PRINT_SERVER', 'localhost')."/printer/drawer.php',";
		} else {
			echo "url: 'http://".getDolGlobalString('TAKEPOS_PRINT_SERVER', 'localhost').":8111/print',";
		}
		?>
		data: "opendrawer"
	});
}

function DolibarrOpenDrawer() {
	console.log("DolibarrOpenDrawer call ajax url /takepos/ajax/ajax.php?action=opendrawer&token=<?php echo newToken();?>&term=<?php print urlencode(empty($_SESSION["takeposterminal"]) ? '' : $_SESSION["takeposterminal"]); ?>");
	$.ajax({
		type: "GET",
		data: { token: '<?php echo currentToken(); ?>' },
		url: "<?php print DOL_URL_ROOT.'/takepos/ajax/ajax.php?action=opendrawer&token='.newToken().'&term='.urlencode(empty($_SESSION["takeposterminal"]) ? '' : $_SESSION["takeposterminal"]); ?>",
	});
}

function MoreActions(totalactions){
	if (pageactions==0){
		pageactions=1;
		for (i = 0; i <= totalactions; i++){
			if (i<12) $("#action"+i).hide();
			else $("#action"+i).show();
		}
	}
	else if (pageactions==1){
		pageactions=0;
		for (i = 0; i <= totalactions; i++){
			if (i<12) $("#action"+i).show();
			else $("#action"+i).hide();
		}
	}

	return true;
}

function ControlCashOpening()
{
	$.colorbox({href:"../compta/cashcontrol/cashcontrol_card.php?action=create&contextpage=takepos", width:"90%", height:"60%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("NewCashFence"); ?>"});
}

function CloseCashFence(rowid)
{
	$.colorbox({href:"../compta/cashcontrol/cashcontrol_card.php?id="+rowid+"&contextpage=takepos", width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("NewCashFence"); ?>"});
}

function CashReport(rowid)
{
	$.colorbox({href:"../compta/cashcontrol/report.php?id="+rowid+"&contextpage=takepos", width:"60%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("CashReport"); ?>"});
}

// TakePOS Popup
function ModalBox(ModalID)
{
	var modal = document.getElementById(ModalID);
	modal.style.display = "block";
}

function DirectPayment(){
	console.log("DirectPayment");
	$("#poslines").load("invoice.php?place="+place+"&action=valid&token=<?php echo newToken(); ?>&pay=LIQ", function() {
	});
}

function FullScreen() {
	document.documentElement.requestFullscreen();
}

function WeighingScale(){
	console.log("Weighing Scale");
	$.ajax({
		type: "POST",
		data: { token: 'notrequired' },
		url: '<?php print getDolGlobalString('TAKEPOS_PRINT_SERVER'); ?>/scale/index.php',
	})
	.done(function( editnumber ) {
		$("#poslines").load("invoice.php?token=<?php echo newToken(); ?>&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
			});
	});
}

$( document ).ready(function() {
	PrintCategories(0);
	LoadProducts(0);
	Refresh();
	<?php
	// IF NO TERMINAL SELECTED
	if (empty($_SESSION["takeposterminal"]) || $_SESSION["takeposterminal"] == "") {
		print "ModalBox('ModalTerminal');";
	}

	if (getDolGlobalString('TAKEPOS_CONTROL_CASH_OPENING')) {
		$sql = "SELECT rowid, status FROM ".MAIN_DB_PREFIX."pos_cash_fence WHERE";
		$sql .= " entity = ".((int) $conf->entity)." AND ";
		$sql .= " posnumber = ".((int) $_SESSION["takeposterminal"])." AND ";
		$sql .= " date_creation > '".$db->idate(dol_get_first_hour(dol_now()))."'";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			// If there is no cash control from today open it
			if (!isset($obj->rowid) || is_null($obj->rowid)) {
				print "ControlCashOpening();";
			}
		}
	}
	?>

	/* For Header Scroll */
	var elem1 = $("#topnav-left")[0];
	var elem2 = $("#topnav-right")[0];
	var checkOverflow = function() {
		if (scrollBars().horizontal) $("#topnav").addClass("overflow");
		else  $("#topnav").removeClass("overflow");
	}

	var scrollBars = function(){
		var container= $('#topnav')[0];
		return {
			vertical:container.scrollHeight > container.clientHeight,
			horizontal:container.scrollWidth > container.clientWidth
		}
	}

	$(window).resize(function(){
		checkOverflow();
	});

	   let resizeObserver = new ResizeObserver(() => {
		   checkOverflow();
	   });
		  resizeObserver.observe(elem1);
	   resizeObserver.observe(elem2);
	checkOverflow();

	var pressTimer = [];
	var direction = 1;
	var step = 200;

	$(".indicator").mousedown(function(){
		direction = $(this).hasClass("left") ? -1 : 1;
		scrollTo();
		pressTimer.push(setInterval(scrollTo, 100));
	});

	$(".indicator").mouseup(function(){
		pressTimer.forEach(clearInterval);
	});

	$("body").mouseup(function(){
		pressTimer.forEach(clearInterval);
		console.log("body mouseup");
	});

	function scrollTo(){
		console.log("here");
		var pos = $("#topnav").scrollLeft();
		document.getElementById("topnav").scrollTo({ left: $("#topnav").scrollLeft() + direction * step, behavior: 'smooth' })
	}

	$("#topnav").scroll(function(){
		if (($("#topnav").offsetWidth + $("#topnav").scrollLeft >= $("#topnav").scrollWidth)) {
			console.log("end");
		}
	});
	/* End Header Scroll */
});
</script>

<?php
$keyCodeForEnter = '';
if (!empty($_SESSION['takeposterminal'])) {
	$keyCodeForEnter = getDolGlobalInt('CASHDESK_READER_KEYCODE_FOR_ENTER'.$_SESSION['takeposterminal']) > 0 ? getDolGlobalString('CASHDESK_READER_KEYCODE_FOR_ENTER'.$_SESSION['takeposterminal']) : '';
}
?>
<div class="container">

<?php
if (!getDolGlobalString('TAKEPOS_HIDE_HEAD_BAR')) {
	?>
	<div class="header">
		<div id="topnav" class="topnav">
			<div id="topnav-left" class="topnav-left">
				<div class="inline-block valignmiddle">
				<a class="topnav-terminalhour" onclick="ModalBox('ModalTerminal')">
				<span class="fa fa-cash-register"></span>
				<span class="hideonsmartphone">
				<?php
				if (!empty($_SESSION["takeposterminal"])) {
					echo getDolGlobalString("TAKEPOS_TERMINAL_NAME_".$_SESSION["takeposterminal"], $langs->trans("TerminalName", $_SESSION["takeposterminal"]));
				}
				?>
				</span>
				<?php
				echo '<span class="hideonsmartphone"> - '.dol_print_date(dol_now(), "day").'</span>'; ?>
				</a>
				<?php
				if (isModEnabled('multicurrency')) {
					print '<a class="valignmiddle tdoverflowmax100" id="multicurrency" onclick="ModalBox(\'ModalCurrency\')" title=""><span class="fas fa-coins paddingrightonly"></span>';
					print '<span class="hideonsmartphone">'.$langs->trans("Currency").'</span>';
					print '</a>';
				} ?>
				</div>
				<!-- section for customer -->
				<div class="inline-block valignmiddle" id="customerandsales"></div>
				<input type="hidden" id="idcustomer" value="">
				<!-- section for shopping carts -->
				<div class="inline-block valignmiddle" id="shoppingcart"></div>
				<!-- More info about customer -->
				<div class="inline-block valignmiddle tdoverflowmax150onsmartphone" id="moreinfo"></div>
				<?php
				if (isModEnabled('stock')) {
					?>
				<!-- More info about warehouse -->
				<div class="inline-block valignmiddle tdoverflowmax150onsmartphone" id="infowarehouse"></div>
					<?php
				} ?>
			</div>
			<div id="topnav-right" class="topnav-right">
				<?php
				$reshook = $hookmanager->executeHooks('takepos_login_block_other');
				if ($reshook == 0) {  //Search method
					?>
					<div class="login_block_other takepos">
					<input type="text" id="search" name="search" class="input-nobottom" onkeyup="Search2('<?php echo dol_escape_js($keyCodeForEnter); ?>', null);" placeholder="<?php echo dol_escape_htmltag($langs->trans("Search")); ?>" autofocus>
					<a onclick="ClearSearch(false);" class="nohover"><span class="fa fa-backspace"></span></a>
					<a href="<?php echo DOL_URL_ROOT.'/'; ?>" target="backoffice" rel="opener"><!-- we need rel="opener" here, we are on same domain and we need to be able to reuse this tab several times -->
					<span class="fas fa-home"></span></a>
					<?php if (empty($conf->dol_use_jmobile)) { ?>
					<a class="hideonsmartphone" onclick="FullScreen();" title="<?php echo dol_escape_htmltag($langs->trans("ClickFullScreenEscapeToLeave")); ?>"><span class="fa fa-expand-arrows-alt"></span></a>
					<?php } ?>
					</div>
					<?php
				}
				?>
				<div class="login_block_user">
				<?php
				print top_menu_user(1, DOL_URL_ROOT.'/user/logout.php?token='.newToken().'&urlfrom='.urlencode('/takepos/?setterminal='.((int) $term)));
				?>
				</div>
			</div>
			<div class="arrows">
				<span class="indicator left"><i class="fa fa-arrow-left"></i></span>
				<span class="indicator right"><i class="fa fa-arrow-right"></i></span>
			</div>
		</div>
	</div>
	<?php
}
?>

<!-- Modal terminal box -->
<div id="ModalTerminal" class="modal">
	<div class="modal-content">
		<div class="modal-header">
		<?php
		if (!getDolGlobalString('TAKEPOS_FORCE_TERMINAL_SELECT')) {
			?>
			<span class="close" href="#" onclick="document.getElementById('ModalTerminal').style.display = 'none';">&times;</span>
			<?php
		} ?>
		<h3><?php print $langs->trans("TerminalSelect"); ?></h3>
	</div>
	<div class="modal-body">
		<button type="button" class="block" onclick="location.href='index.php?setterminal=1'"><?php print getDolGlobalString("TAKEPOS_TERMINAL_NAME_1", $langs->trans("TerminalName", 1)); ?></button>
		<?php
		$nbloop = getDolGlobalInt('TAKEPOS_NUM_TERMINALS');
		for ($i = 2; $i <= $nbloop; $i++) {
			print '<button type="button" class="block" onclick="location.href=\'index.php?setterminal='.$i.'\'">'.getDolGlobalString("TAKEPOS_TERMINAL_NAME_".$i, $langs->trans("TerminalName", $i)).'</button>';
		}
		?>
	</div>
</div>
</div>

<!-- Modal multicurrency box -->
<?php if (isModEnabled('multicurrency')) { ?>
<div id="ModalCurrency" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<span class="close" href="#" onclick="document.getElementById('ModalCurrency').style.display = 'none';">&times;</span>
			<h3><?php print $langs->trans("SetMultiCurrencyCode"); ?></h3>
		</div>
		<div class="modal-body">
			<?php
			$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'multicurrency';
			$sql .= " WHERE entity IN ('".getEntity('multicurrency')."')";
			$resql = $db->query($sql);
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					print '<button type="button" class="block" onclick="location.href=\'index.php?setcurrency='.$obj->code.'\'">'.$obj->code.'</button>';
				}
			}
			?>
		</div>
	</div>
</div>
<?php } ?>

<!-- Modal terminal Credit Note -->
<div id="ModalCreditNote" class="modal">
	<div class="modal-content">
		<div class="modal-header">
		<span class="close" href="#" onclick="document.getElementById('ModalCreditNote').style.display = 'none';">&times;</span>
		<h3><?php print $langs->trans("invoiceAvoirWithLines"); ?></h3>
	</div>
	<div class="modal-body">
		<button type="button" class="block" onclick="CreditNote(); document.getElementById('ModalCreditNote').style.display = 'none';"><?php print $langs->trans("Yes"); ?></button>
		<button type="button" class="block" onclick="document.getElementById('ModalCreditNote').style.display = 'none';"><?php print $langs->trans("No"); ?></button>
	</div>
</div>
</div>

<!-- Modal Note -->
<div id="ModalNote" class="modal">
	<div class="modal-content">
		<div class="modal-header">
		<span class="close" href="#" onclick="document.getElementById('ModalNote').style.display = 'none';">&times;</span>
		<h3><?php print $langs->trans("Note"); ?></h3>
	</div>
	<div class="modal-body">
		<input type="text" class="block" id="textinput">
		<button type="button" class="block" onclick="SetNote(); document.getElementById('ModalNote').style.display = 'none';">OK</button>
	</div>
</div>
</div>

	<div class="row1<?php if (!getDolGlobalString('TAKEPOS_HIDE_HEAD_BAR')) {
		print 'withhead';
					} ?>">

		<div id="poslines" class="div1">
		</div>

		<div class="div2">
			<button type="button" class="calcbutton" onclick="Edit(7);">7</button>
			<button type="button" class="calcbutton" onclick="Edit(8);">8</button>
			<button type="button" class="calcbutton" onclick="Edit(9);">9</button>
			<button type="button" id="qty" class="calcbutton2" onclick="Edit('qty')"><?php echo $langs->trans("Qty"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(4);">4</button>
			<button type="button" class="calcbutton" onclick="Edit(5);">5</button>
			<button type="button" class="calcbutton" onclick="Edit(6);">6</button>
			<button type="button" id="price" class="calcbutton2" onclick="Edit('p')"><?php echo $langs->trans("Price"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(1);">1</button>
			<button type="button" class="calcbutton" onclick="Edit(2);">2</button>
			<button type="button" class="calcbutton" onclick="Edit(3);">3</button>
			<button type="button" id="reduction" class="calcbutton2" onclick="Edit('r')"><?php echo $langs->trans("LineDiscountShort"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(0);">0</button>
			<button type="button" class="calcbutton" onclick="Edit('.')">.</button>
			<button type="button" class="calcbutton poscolorblue" onclick="Edit('c')">C</button>
			<button type="button" class="calcbutton2 poscolordelete" id="delete" onclick="deleteline()"><span class="fa fa-trash"></span></button>
		</div>

<?php

// TakePOS setup check
if (isset($_SESSION["takeposterminal"]) && $_SESSION["takeposterminal"]) {
	$sql = "SELECT code, libelle FROM " . MAIN_DB_PREFIX . "c_paiement";
	$sql .= " WHERE entity IN (" . getEntity('c_paiement') . ")";
	$sql .= " AND active = 1";
	$sql .= " ORDER BY libelle";

	$resql          = $db->query($sql);
	$paiementsModes = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$paycode = $obj->code;
			if ($paycode == 'LIQ') {
				$paycode = 'CASH';
			}
			if ($paycode == 'CHQ') {
				$paycode = 'CHEQUE';
			}

			$constantforkey = "CASHDESK_ID_BANKACCOUNT_" . $paycode . $_SESSION["takeposterminal"];
			//var_dump($constantforkey.' '.getDolGlobalInt($constantforkey));
			if (getDolGlobalInt($constantforkey) > 0) {
				array_push($paiementsModes, $obj);
			}
		}
	}

	if (empty($paiementsModes) && isModEnabled("bank")) {
		$langs->load('errors');
		setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("TakePOS")), null, 'errors');
		setEventMessages($langs->trans("ProblemIsInSetupOfTerminal", $_SESSION["takeposterminal"]), null, 'errors');
	}
}

if (count($maincategories) == 0) {
	if (getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID') > 0) {
		$tmpcategory = new Categorie($db);
		$tmpcategory->fetch(getDolGlobalString('TAKEPOS_ROOT_CATEGORY_ID'));
		setEventMessages($langs->trans("TakeposNeedsAtLeastOnSubCategoryIntoParentCategory", $tmpcategory->label), null, 'errors');
	} else {
		setEventMessages($langs->trans("TakeposNeedsCategories"), null, 'errors');
	}
}
// User menu and external TakePOS modules
$menus = array();
$r = 0;

if (!getDolGlobalString('TAKEPOS_BAR_RESTAURANT')) {
	$menus[$r++] = array('title' => '<span class="fa fa-layer-group paddingrightonly"></span><div class="trunc">'.$langs->trans("New").'</div>', 'action' => 'New();');
} else {
	// BAR RESTAURANT specific menu
	$menus[$r++] = array('title' => '<span class="fa fa-layer-group paddingrightonly"></span><div class="trunc">'.$langs->trans("Place").'</div>', 'action' => 'Floors();');
}

if (getDolGlobalString('TAKEPOS_HIDE_HEAD_BAR')) {
	if (getDolGlobalString('TAKEPOS_CHOOSE_CONTACT')) {
		$menus[$r++] = array('title' => '<span class="far fa-building paddingrightonly"></span><div class="trunc">'.$langs->trans("Contact").'</div>', 'action' => 'Contact();');
	} else {
		$menus[$r++] = array('title' => '<span class="far fa-building paddingrightonly"></span><div class="trunc">'.$langs->trans("Customer").'</div>', 'action' => 'Customer();');
	}
}
if (! getDolGlobalString('TAKEPOS_HIDE_HISTORY')) {
	$menus[$r++] = array('title' => '<span class="fa fa-history paddingrightonly"></span><div class="trunc">'.$langs->trans("History").'</div>', 'action' => 'History();');
}
$menus[$r++] = array('title' => '<span class="fa fa-cube paddingrightonly"></span><div class="trunc">'.$langs->trans("FreeZone").'</div>', 'action' => 'FreeZone();');
$menus[$r++] = array('title' => '<span class="fa fa-percent paddingrightonly"></span><div class="trunc">'.$langs->trans("InvoiceDiscountShort").'</div>', 'action' => 'Reduction();');

if (!getDolGlobalString('TAKEPOS_NO_SPLIT_SALE')) {
	$menus[$r++] = array('title' => '<span class="fas fa-cut paddingrightonly"></span><div class="trunc">'.$langs->trans("SplitSale").'</div>', 'action' => 'Split();');
}

// BAR RESTAURANT specific menu
if (getDolGlobalString('TAKEPOS_BAR_RESTAURANT')) {
	if (getDolGlobalString('TAKEPOS_ORDER_PRINTERS')) {
		$menus[$r++] = array('title' => '<span class="fa fa-blender-phone paddingrightonly"></span><div class="trunc">'.$langs->trans("Order").'</span>', 'action' => 'TakeposPrintingOrder();');
	}
}

// Last action that close the sell (payments)
$menus[$r++] = array('title' => '<span class="far fa-money-bill-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("Payment").'</div>', 'action' => 'CloseBill();');
if (getDolGlobalString('TAKEPOS_DIRECT_PAYMENT')) {
	$menus[$r++] = array('title' => '<span class="far fa-money-bill-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("DirectPayment").' <span class="opacitymedium">('.$langs->trans("Cash").')</span></div>', 'action' => 'DirectPayment();');
}

// BAR RESTAURANT specific menu
if (getDolGlobalString('TAKEPOS_BAR_RESTAURANT')) {
	//Button to print receipt before payment
	if (getDolGlobalString('TAKEPOS_BAR_RESTAURANT')) {
		if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector") {
			if (getDolGlobalString('TAKEPOS_PRINT_SERVER') && filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) {
				$menus[$r++] = array('title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action' => 'TakeposConnector(placeid);');
			} else {
				$menus[$r++] = array('title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action' => 'TakeposPrinting(placeid);');
			}
		} elseif ((isModEnabled('receiptprinter') && getDolGlobalInt('TAKEPOS_PRINTER_TO_USE'.$term) > 0) || getDolGlobalString('TAKEPOS_PRINT_METHOD') == "receiptprinter") {
			$menus[$r++] = array('title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action' => 'DolibarrTakeposPrinting(placeid);');
		} else {
			$menus[$r++] = array('title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action' => 'Print(placeid);');
		}
	}
	if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector" && getDolGlobalString('TAKEPOS_ORDER_NOTES') == 1) {
		$menus[$r++] = array('title' => '<span class="fa fa-sticky-note paddingrightonly"></span><div class="trunc">'.$langs->trans("OrderNotes").'</div>', 'action' => 'TakeposOrderNotes();');
	}
	if (getDolGlobalString('TAKEPOS_SUPPLEMENTS')) {
		$menus[$r++] = array('title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("ProductSupplements").'</div>', 'action' => 'LoadProducts(\'supplements\');');
	}
}

if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector") {
	$menus[$r++] = array('title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("DOL_OPEN_DRAWER").'</div>', 'action' => 'OpenDrawer();');
}
if (getDolGlobalInt('TAKEPOS_PRINTER_TO_USE'.$term) > 0 || getDolGlobalString('TAKEPOS_PRINT_METHOD') == "receiptprinter") {
	$menus[$r++] = array(
		'title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("DOL_OPEN_DRAWER").'</div>',
		'action' => 'DolibarrOpenDrawer();',
	);
}

$sql = "SELECT rowid, status, entity FROM ".MAIN_DB_PREFIX."pos_cash_fence WHERE";
$sql .= " entity = ".((int) $conf->entity)." AND ";
$sql .= " posnumber = ".((int) empty($_SESSION["takeposterminal"]) ? 0 : $_SESSION["takeposterminal"])." AND ";
$sql .= " date_creation > '".$db->idate(dol_get_first_hour(dol_now()))."'";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	if ($num) {
		$obj = $db->fetch_object($resql);
		$menus[$r++] = array('title' => '<span class="fas fa-file-invoice-dollar paddingrightonly"></span><div class="trunc">'.$langs->trans("CashReport").'</div>', 'action' => 'CashReport('.$obj->rowid.');');
		if ($obj->status == 0) {
			$menus[$r++] = array('title' => '<span class="fas fa-cash-register paddingrightonly"></span><div class="trunc">'.$langs->trans("CloseCashFence").'</div>', 'action' => 'CloseCashFence('.$obj->rowid.');');
		}
	}
}

$parameters = array('menus' => $menus);
$reshook = $hookmanager->executeHooks('ActionButtons', $parameters);
if ($reshook == 0) {  //add buttons
	if (is_array($hookmanager->resArray)) {
		foreach ($hookmanager->resArray as $resArray) {
			foreach ($resArray as $butmenu) {
				$menus[$r++] = $butmenu;
			}
		}
	} elseif ($reshook == 1) {
		$r = 0; //replace buttons
		if (is_array($hookmanager->resArray)) {
			foreach ($hookmanager->resArray as $resArray) {
				foreach ($resArray as $butmenu) {
					$menus[$r++] = $butmenu;
				}
			}
		}
	}
}

if ($r % 3 == 2) {
	$menus[$r++] = array('title' => '', 'style' => 'visibility: hidden;');
}

if (getDolGlobalString('TAKEPOS_HIDE_HEAD_BAR')) {
	$menus[$r++] = array('title' => '<span class="fa fa-sign-out-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("Logout").'</div>', 'action' => 'window.location.href=\''.DOL_URL_ROOT.'/user/logout.php?token='.newToken().'\';');
}

if (getDolGlobalString('TAKEPOS_WEIGHING_SCALE')) {
	$menus[$r++] = array('title' => '<span class="fa fa-balance-scale paddingrightonly"></span><div class="trunc">'.$langs->trans("WeighingScale").'</div>', 'action' => 'WeighingScale();');
}

?>
		<!-- Show buttons -->
		<div class="div3">
		<?php
		$i = 0;
		foreach ($menus as $menu) {
			$i++;
			if (count($menus) > 12 and $i == 12) {
				echo '<button style="'.(empty($menu['style']) ? '' : $menu['style']).'" type="button" id="actionnext" class="actionbutton" onclick="MoreActions('.count($menus).')">'.$langs->trans("Next").'</button>';
				echo '<button style="display: none;" type="button" id="action'.$i.'" class="actionbutton" onclick="'.(empty($menu['action']) ? '' : $menu['action']).'">'.$menu['title'].'</button>';
			} elseif ($i > 12) {
				echo '<button style="display: none;" type="button" id="action'.$i.'" class="actionbutton" onclick="'.(empty($menu['action']) ? '' : $menu['action']).'">'.$menu['title'].'</button>';
			} else {
				echo '<button style="'.(empty($menu['style']) ? '' : $menu['style']).'" type="button" id="action'.$i.'" class="actionbutton" onclick="'.(empty($menu['action']) ? '' : $menu['action']).'">'.$menu['title'].'</button>';
			}
		}

		if (getDolGlobalString('TAKEPOS_HIDE_HEAD_BAR') && !getDolGlobalString('TAKEPOS_HIDE_SEARCH')) {
			print '<!-- Show the search input text -->'."\n";
			print '<div class="margintoponly">';
			print '<input type="text" id="search" class="input-search-takepos input-nobottom" name="search" onkeyup="Search2(\''.dol_escape_js($keyCodeForEnter).'\', null);" style="width: 80%; width:calc(100% - 51px); font-size: 150%;" placeholder="'.dol_escape_htmltag($langs->trans("Search")).'" autofocus> ';
			print '<a class="marginleftonly hideonsmartphone" onclick="ClearSearch(false);">'.img_picto('', 'searchclear').'</a>';
			print '</div>';
		}
		?>
		</div>
	</div>

	<div class="row2<?php if (!getDolGlobalString('TAKEPOS_HIDE_HEAD_BAR')) {
		print 'withhead';
					} ?>">

		<!--  Show categories -->
		<?php
		if (getDolGlobalInt('TAKEPOS_HIDE_CATEGORIES') == 1) {
			print '<div class="div4" style= "display: none;">';
		} else {
			print '<div class="div4">';
		}

		$count = 0;
		while ($count < $MAXCATEG) {
			?>
			<div class="wrapper" <?php if ($count == ($MAXCATEG - 2)) {
				echo 'onclick="MoreCategories(\'less\')"';
								 } elseif ($count == ($MAXCATEG - 1)) {
									 echo 'onclick="MoreCategories(\'more\')"';
								 } else {
									 echo 'onclick="LoadProducts('.$count.')"';
								 } ?> id="catdiv<?php echo $count; ?>">
				<?php
				if ($count == ($MAXCATEG - 2)) {
					//echo '<img class="imgwrapper" src="img/arrow-prev-top.png" height="100%" id="catimg'.$count.'" />';
					echo '<span class="fa fa-chevron-left centerinmiddle" style="font-size: 5em; cursor: pointer;"></span>';
				} elseif ($count == ($MAXCATEG - 1)) {
					//echo '<img class="imgwrapper" src="img/arrow-next-top.png" height="100%" id="catimg'.$count.'" />';
					echo '<span class="fa fa-chevron-right centerinmiddle" style="font-size: 5em; cursor: pointer;"></span>';
				} else {
					if (!getDolGlobalString('TAKEPOS_HIDE_CATEGORY_IMAGES')) {
						echo '<img class="imgwrapper" id="catimg'.$count.'" />';
					}
				} ?>
						<?php if ($count != $MAXCATEG - 2 && $count != $MAXCATEG - 1) { ?>
				<div class="description" id="catdivdesc<?php echo $count; ?>">
					<div class="description_content" id="catdesc<?php echo $count; ?>"></div>
				</div>
						<?php } ?>
				<div class="catwatermark" id='catwatermark<?php echo $count; ?>'>...</div>
			</div>
					<?php
					$count++;
		}
		?>
		</div>

		<!--  Show product -->
		<div class="div5<?php if (getDolGlobalInt('TAKEPOS_HIDE_CATEGORIES') == 1) {
			print ' centpercent';
						} ?>">
	<?php
	$count = 0;
	while ($count < $MAXPRODUCT) {
		print '<div class="wrapper2 arrow" id="prodiv'.$count.'"  '; ?>
								<?php if ($count == ($MAXPRODUCT - 2)) {
									?> onclick="MoreProducts('less')" <?php
								}
								if ($count == ($MAXPRODUCT - 1)) {
									?> onclick="MoreProducts('more')" <?php
								} else {
									echo 'onclick="ClickProduct('.$count.')"';
								} ?>>
					<?php
					if ($count == ($MAXPRODUCT - 2)) {
						//echo '<img class="imgwrapper" src="img/arrow-prev-top.png" height="100%" id="proimg'.$count.'" />';
						print '<span class="fa fa-chevron-left centerinmiddle" style="font-size: 5em; cursor: pointer;"></span>';
					} elseif ($count == ($MAXPRODUCT - 1)) {
						//echo '<img class="imgwrapper" src="img/arrow-next-top.png" height="100%" id="proimg'.$count.'" />';
						print '<span class="fa fa-chevron-right centerinmiddle" style="font-size: 5em; cursor: pointer;"></span>';
					} else {
						if (!getDolGlobalString('TAKEPOS_HIDE_PRODUCT_PRICES')) {
							print '<div class="" id="proprice'.$count.'"></div>';
						}
						if (getDolGlobalString('TAKEPOS_HIDE_PRODUCT_IMAGES')) {
							print '<button type="button" id="probutton'.$count.'" class="productbutton" style="display: none;"></button>';
						} else {
							print '<img class="imgwrapper" title="" id="proimg'.$count.'">';
						}
					} ?>
						<?php if ($count != $MAXPRODUCT - 2 && $count != $MAXPRODUCT - 1 && !getDolGlobalString('TAKEPOS_HIDE_PRODUCT_IMAGES')) { ?>
					<div class="description" id="prodivdesc<?php echo $count; ?>">
						<div class="description_content" id="prodesc<?php echo $count; ?>"></div>
					</div>
						<?php } ?>
					<div class="catwatermark" id='prowatermark<?php echo $count; ?>'>...</div>
				</div>
					<?php
					$count++;
	}
	?>
				<input type="hidden" id="search_start_less" value="0">
				<input type="hidden" id="search_start_more" value="0">
				<input type="hidden" id="search_pagination" value="">
		</div>
	</div>
</div>
</body>
<?php

llxFooter();

$db->close();
