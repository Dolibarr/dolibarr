<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	Josep Lluís Amador	<joseplluis@lliuretic.cat>
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
 *	\file       htdocs/takepos/index.php
 *	\ingroup    takepos
 *	\brief      Main TakePOS screen
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0); // $place is id of table for Bar or Restaurant or multiple sales
$action = GETPOST('action', 'alpha');
$setterminal = GETPOST('setterminal', 'int');

if ($setterminal > 0)
{
	$_SESSION["takeposterminal"] = $setterminal;
}

$_SESSION["urlfrom"] = '/takepos/index.php';

$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk", "receiptprinter", "banks"));

$categorie = new Categorie($db);

$maxcategbydefaultforthisdevice = 12;
$maxproductbydefaultforthisdevice = 24;
if ($conf->browser->layout == 'phone')
{
    $maxcategbydefaultforthisdevice = 8;
    $maxproductbydefaultforthisdevice = 16;
	//REDIRECT TO BASIC LAYOUT IF TERMINAL SELECTED AND BASIC MOBILE LAYOUT ENABLED
	if ($_SESSION["takeposterminal"] != "" && $conf->global->TAKEPOS_PHONE_BASIC_LAYOUT == 1)
	{
		$_SESSION["basiclayout"] = 1;
		header("Location: phone.php?mobilepage=invoice");
		exit;
	}
}
$MAXCATEG = (empty($conf->global->TAKEPOS_NB_MAXCATEG) ? $maxcategbydefaultforthisdevice : $conf->global->TAKEPOS_NB_MAXCATEG);
$MAXPRODUCT = (empty($conf->global->TAKEPOS_NB_MAXPRODUCT) ? $maxproductbydefaultforthisdevice : $conf->global->TAKEPOS_NB_MAXPRODUCT);

/*
$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
$soc = new Societe($db);
if ($invoice->socid > 0) $soc->fetch($invoice->socid);
else $soc->fetch($conf->global->$constforcompanyid);
*/

// Security check
$result = restrictedArea($user, 'takepos', 0, '');



/*
 * View
 */

$form = new Form($db);

// Title
$title = 'TakePOS - Dolibarr '.DOL_VERSION;
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = 'TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

?>
<link rel="stylesheet" href="css/pos.css.php">
<link rel="stylesheet" href="css/colorbox.css" type="text/css" media="screen" />
<?php
if ($conf->global->TAKEPOS_COLOR_THEME == 1) print '<link rel="stylesheet" href="css/colorful.css">';
?>
<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>	<!-- TODO It seems we don't need this -->
<script language="javascript">
<?php
$categories = $categorie->get_full_arbo('product', (($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0) ? $conf->global->TAKEPOS_ROOT_CATEGORY_ID : 0), 1);


// Search root category to know its level
//$conf->global->TAKEPOS_ROOT_CATEGORY_ID=0;
$levelofrootcategory = 0;
if ($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0)
{
    foreach ($categories as $key => $categorycursor)
    {
        if ($categorycursor['id'] == $conf->global->TAKEPOS_ROOT_CATEGORY_ID)
        {
            $levelofrootcategory = $categorycursor['level'];
            break;
        }
    }
}
$levelofmaincategories = $levelofrootcategory + 1;

$maincategories = array();
$subcategories = array();
foreach ($categories as $key => $categorycursor)
{
    if ($categorycursor['level'] == $levelofmaincategories)
    {
        $maincategories[$key] = $categorycursor;
    }
    else
    {
        $subcategories[$key] = $categorycursor;
    }
}

$maincategories = dol_sort_array($maincategories, 'label');
$subcategories = dol_sort_array($subcategories, 'label');
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
var invoiceid=0;

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

function ClearSearch() {
	console.log("ClearSearch");
	$("#search").val('');
	<?php if ($conf->browser->layout == 'classic') { ?>
	setFocusOnSearchField();
	<?php } ?>
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
	for (i = 0; i < <?php echo ($MAXCATEG - 2); ?>; i++) {
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
		$("#catdesc"+i).text(categories[parseInt(i)+parseInt(first)]['label']);
        $("#catimg"+i).attr("src","genimg/index.php?query=cat&id="+categories[parseInt(i)+parseInt(first)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[parseInt(i)+parseInt(first)]['rowid']);
		$("#catdiv"+i).attr('class', 'wrapper');
		$("#catwatermark"+i).show();
	}
}

function MoreCategories(moreorless) {
	console.log("MoreCategories moreorless="+moreorless+" pagecategories="+pagecategories);
	if (moreorless=="more") {
		$('#catimg15').animate({opacity: '0.5'}, 1);
		$('#catimg15').animate({opacity: '1'}, 100);
		pagecategories=pagecategories+1;
	}
	if (moreorless=="less") {
		$('#catimg14').animate({opacity: '0.5'}, 1);
		$('#catimg14').animate({opacity: '1'}, 100);
		if (pagecategories==0) return; //Return if no less pages
		pagecategories=pagecategories-1;
	}
	if (typeof (categories[<?php echo ($MAXCATEG - 2); ?> * pagecategories] && moreorless=="more") == "undefined"){ // Return if no more pages
		pagecategories=pagecategories-1;
		return;
	}
	for (i = 0; i < <?php echo ($MAXCATEG - 2); ?>; i++) {
		if (typeof (categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]) == "undefined") {
			$("#catdivdesc"+i).hide();
			$("#catdesc"+i).text("");
			$("#catimg"+i).attr("src","genimg/empty.png");
			$("#catwatermark"+i).hide();
			continue;
		}
		$("#catdivdesc"+i).show();
		$("#catdesc"+i).text(categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]['label']);
        $("#catimg"+i).attr("src","genimg/index.php?query=cat&id="+categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
		$("#catwatermark"+i).show();
	}

	ClearSearch();
}

// LoadProducts
function LoadProducts(position, issubcat) {
	console.log("LoadProducts");
	var maxproduct = <?php echo ($MAXPRODUCT - 2); ?>;

	if (position=="supplements") currentcat="supplements";
	else
	{
		$('#catimg'+position).animate({opacity: '0.5'}, 1);
		$('#catimg'+position).animate({opacity: '1'}, 100);
		if (issubcat==true) currentcat=$('#prodiv'+position).data('rowid');
		else currentcat=$('#catdiv'+position).data('rowid');
	}
    if (currentcat == undefined) return;
	pageproducts=0;
	ishow=0; //product to show counter

	jQuery.each(subcategories, function(i, val) {
		if (currentcat==val.fk_parent) {
			$("#prodivdesc"+ishow).show();
			$("#prodesc"+ishow).text(val.label);
			$("#proprice"+ishow).attr("class", "hidden");
			$("#proprice"+ishow).html("");
			$("#proimg"+ishow).attr("src","genimg/index.php?query=cat&id="+val.rowid);
			$("#prodiv"+ishow).data("rowid",val.rowid);
			$("#prodiv"+ishow).data("iscat",1);
			$("#prowatermark"+ishow).show();
			ishow++;
		}
	});

	idata=0; //product data counter
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getProducts&category='+currentcat, function(data) {
		console.log("Call ajax.php (in LoadProducts) to get Products of category "+currentcat+" then loop on result to fill image thumbs");
		console.log(data);
		while (ishow < maxproduct) {
			//console.log("ishow"+ishow+" idata="+idata);
			console.log(data[idata]);
			if (typeof (data[idata]) == "undefined") {
				$("#prodivdesc"+ishow).hide();
				$("#prodesc"+ishow).text("");
				$("#proprice"+ishow).attr("class", "hidden");
				$("#proprice"+ishow).html("");
				$("#proimg"+ishow).attr("title","");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				$("#prodiv"+ishow).attr("class","wrapper2 divempty");
				$("#prowatermark"+ishow).hide();
				ishow++; //Next product to show after print data product
			}
			else if ((data[idata]['status']) == "1") {		// Only show products with status=1 (for sell)
				<?php
					$titlestring = "'".dol_escape_js($langs->transnoentities('Ref').': ')."' + data[idata]['ref']";
					$titlestring .= " + ' - ".dol_escape_js($langs->trans("Barcode").': ')."' + data[idata]['barcode']";
				?>
				var titlestring = <?php echo $titlestring; ?>;
				$("#prodivdesc"+ishow).show();
				$("#prodesc"+ishow).text(data[parseInt(idata)]['label']);
				if (data[parseInt(idata)]['price_formated']) {
					$("#proprice"+ishow).attr("class", "productprice");
					$("#proprice"+ishow).html(data[parseInt(idata)]['price_formated']);
				}
				$("#proimg"+ishow).attr("title", titlestring);
				$("#proimg"+ishow).attr("src", "genimg/index.php?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid", data[idata]['id']);
				$("#prodiv"+ishow).data("iscat", 0);
				$("#prodiv"+ishow).attr("class","wrapper2");
				$("#prowatermark"+ishow).hide();
				ishow++; //Next product to show after print data product
			}
			//console.log("Hide the prowatermark for ishow="+ishow);
			idata++; //Next data everytime
		}
	});

	ClearSearch();
}

function MoreProducts(moreorless) {
	console.log("MoreProducts");
	var maxproduct = <?php echo ($MAXPRODUCT - 2); ?>;

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
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getProducts&category='+currentcat, function(data) {
		console.log("Call ajax.php (in MoreProducts) to get Products of category "+currentcat);

		if (typeof (data[(maxproduct * pageproducts)]) == "undefined" && moreorless=="more"){ // Return if no more pages
			pageproducts=pageproducts-1;
			return;
		}
		idata=<?php echo ($MAXPRODUCT - 2); ?> * pageproducts; //product data counter
		ishow=0; //product to show counter

		while (ishow < maxproduct) {
			if (typeof (data[idata]) == "undefined") {
				$("#prodivdesc"+ishow).hide();
				$("#prodesc"+ishow).text("");
				$("#proprice"+ishow).attr("class", "");
				$("#proprice"+ishow).html("");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				ishow++; //Next product to show after print data product
			}
			else if ((data[idata]['status']) == "1") {
				//Only show products with status=1 (for sell)
				$("#prodivdesc"+ishow).show();
				$("#prodesc"+ishow).text(data[parseInt(idata)]['label']);
				if (data[parseInt(idata)]['price_formated']) {
					$("#proprice"+ishow).attr("class", "productprice");
					$("#proprice"+ishow).html(data[parseInt(idata)]['price_formated']);
				}
				$("#proimg"+ishow).attr("src","genimg/index.php?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid",data[idata]['id']);
				$("#prodiv"+ishow).data("iscat",0);
				ishow++; //Next product to show after print data product
			}
			$("#prowatermark"+ishow).hide();
			idata++; //Next data everytime
		}
	});

	ClearSearch();
}

function ClickProduct(position) {
	console.log("ClickProduct");
    $('#proimg'+position).animate({opacity: '0.5'}, 1);
	$('#proimg'+position).animate({opacity: '1'}, 100);
	if ($('#prodiv'+position).data('iscat')==1){
		console.log("Click on a category at position "+position);
		LoadProducts(position, true);
	}
	else{
		idproduct=$('#prodiv'+position).data('rowid');
		console.log("Click on product at position "+position+" for idproduct "+idproduct);
		if (idproduct=="") return;
		// Call page invoice.php to generate the section with product lines
		$("#poslines").load("invoice.php?action=addline&place="+place+"&idproduct="+idproduct+"&selectedline="+selectedline, function() {
			//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
		});
	}

	ClearSearch();
}

function deleteline() {
	console.log("Delete line");
	$("#poslines").load("invoice.php?action=deleteline&place="+place+"&idline="+selectedline, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
	ClearSearch();
}

function Customer() {
	console.log("Open box to select the thirdparty place="+place);
	$.colorbox({href:"../societe/list.php?contextpage=poslist&nomassaction=1&place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Customer"); ?>"});
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
	invoiceid = $("#invoiceid").val();
	console.log("Open popup to enter payment on invoiceid="+invoiceid);
	$.colorbox({href:"pay.php?place="+place+"&invoiceid="+invoiceid, width:"80%", height:"90%", transition:"none", iframe:"true", title:""});
}

function Floors() {
	console.log("Open box to select floor place="+place);
	$.colorbox({href:"floors.php?place="+place, width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Floors"); ?>"});
}

function FreeZone() {
	console.log("Open box to enter a free product");
	$.colorbox({href:"freezone.php?action=freezone&place="+place, onClosed: function () { Refresh(); },width:"80%", height:"200px", transition:"none", iframe:"true", title:"<?php echo $langs->trans("FreeZone"); ?>"});
}

function TakeposOrderNotes() {
	console.log("Open box to order notes");
	$.colorbox({href:"freezone.php?action=addnote&place="+place+"&idline="+selectedline, onClosed: function () { Refresh(); },width:"80%", height:"250px", transition:"none", iframe:"true", title:"<?php echo $langs->trans("OrderNotes"); ?>"});
}

function Refresh() {
	console.log("Refresh by reloading place="+place+" invoiceid="+invoiceid);
	$("#poslines").load("invoice.php?place="+place+"&invoiceid="+invoiceid, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function New() {
	// If we go here,it means $conf->global->TAKEPOS_BAR_RESTAURANT is not defined
	invoiceid = $("#invoiceid").val();

	console.log("New with place = <?php echo $place; ?>, js place="+place+", invoiceid="+invoiceid);

	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getInvoice&id='+invoiceid, function(data) {
		var r;

		if (parseInt(data['paye']) === 1) {
			r = true;
		} else {
			r = confirm('<?php echo ($place > 0 ? $langs->transnoentitiesnoconv("ConfirmDeletionOfThisPOSSale") : $langs->transnoentitiesnoconv("ConfirmDiscardOfThisPOSSale")); ?>');
		}

		if (r == true) {
			$("#poslines").load("invoice.php?action=delete&place=" + place, function () {
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});
			ClearSearch();
		}
	});
}

/**
 * Search products
 *
 * @param   {int}			keyCodeForEnter     Key code for "enter"
 * return   {void}
 */
function Search2(keyCodeForEnter) {
	console.log("Search2 Call ajax search to replace products keyCodeForEnter="+keyCodeForEnter);

	var search = false;
	var eventKeyCode = window.event.keyCode;
	if (typeof keyCodeForEnter === 'undefined' || eventKeyCode == keyCodeForEnter) {
		search = true;
	}

	if (search === true) {
		pageproducts = 0;
		jQuery(".wrapper2 .catwatermark").hide();
		$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=search&term=' + $('#search').val(), function (data) {
			for (i = 0; i < <?php echo $MAXPRODUCT ?>; i++) {
				if (typeof (data[i]) == "undefined") {
					$("#prodesc" + i).text("");
					$("#proprice" + i).attr("class", "hidden");
					$("#proprice" + i).html("");
					$("#proimg" + i).attr("src", "genimg/empty.png");
					$("#prodiv" + i).data("rowid", "");
					continue;
				}
				<?php
				$titlestring = "'".dol_escape_js($langs->transnoentities('Ref').': ')."' + data[i]['ref']";
				$titlestring .= " + ' - ".dol_escape_js($langs->trans("Barcode").': ')."' + data[i]['barcode']";
				?>
				var titlestring = <?php echo $titlestring; ?>;
				$("#prodesc" + i).text(data[i]['label']);
				$("#prodivdesc" + i).show();
				if (data[i]['price_formated']) {
					$("#proprice" + i).attr("class", "productprice");
					$("#proprice" + i).html(data[i]['price_formated']);
				}
				$("#proimg" + i).attr("title", titlestring);
				$("#proimg" + i).attr("src", "genimg/index.php?query=pro&id=" + data[i]['rowid']);
				$("#prodiv" + i).data("rowid", data[i]['rowid']);
				$("#prodiv" + i).data("iscat", 0);
			}
		}).always(function (data) {
			// If there is only 1 answer
			if ($('#search').val().length > 0 && data.length == 1) {
				console.log($('#search').val()+' - '+data[0]['barcode']);
				if ($('#search').val() == data[0]['barcode']) {
					console.log("There is only 1 answer with barcode matching the search, so we add the product in basket");
					ClickProduct(0);
				}
			}
		});
	}
}

function Edit(number) {

	if (typeof(selectedtext) == "undefined") return;	// We click on an action on the number pad but there is no line selected

    var text=selectedtext+"<br> ";

    if (number=='c'){
        editnumber="";
        Refresh();
        return;
    }
    else if (number=='qty'){
    	console.log("Edit "+number);
        if (editaction=='qty' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updateqty&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                //$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
            });

            setFocusOnSearchField();
            return;
        }
        else {
            editaction="qty";
        }
    }
    else if (number=='p'){
    	console.log("Edit "+number);
        if (editaction=='p' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updateprice&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                //$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#price").html("<?php echo $langs->trans("Price"); ?>");
            });

            ClearSearch();
            return;
        }
        else {
            editaction="p";
        }
    }
    else if (number=='r'){
    	console.log("Edit "+number);
        if (editaction=='r' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updatereduction&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                //$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
            });

            ClearSearch();
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
        $("#qty").html("OK");
        $("#price").html("<?php echo $langs->trans("Price"); ?>");
        $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
    }
    if (editaction=='p'){
        text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("Price").": "; ?>";
        $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
        $("#price").html("OK");
        $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
    }
    if (editaction=='r'){
        text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("ReductionShort").": "; ?>";
        $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
        $("#price").html("<?php echo $langs->trans("Price"); ?>");
        $("#reduction").html("OK");
    }
    $('#'+selectedline).find("td:first").html(text+editnumber);
}

function TakeposPrintingOrder(){
	console.log("TakeposPrintingOrder");
	$("#poslines").load("invoice.php?action=order&place="+place, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function TakeposPrintingTemp(){
	console.log("TakeposPrintingTemp");
	$("#poslines").load("invoice.php?action=temp&place="+place, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function OpenDrawer(){
	console.log("OpenDrawer call ajax url http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print");
	$.ajax({
		type: "POST",
		url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
		data: "opendrawer"
	});
}

function DolibarrOpenDrawer() {
	console.log("DolibarrOpenDrawer call ajax url /takepos/ajax/ajax.php?action=opendrawer&term=<?php print $_SESSION["takeposterminal"] ?>");
	$.ajax({
		type: "GET",
		url: "<?php print dol_buildpath('/takepos/ajax/ajax.php', 1).'?action=opendrawer&term='.$_SESSION["takeposterminal"]; ?>",
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

// Popup to select the terminal to use
function TerminalsDialog()
{
    jQuery("#dialog-info").dialog({
	    resizable: false,
	    height: <?php echo 180 + round($conf->global->TAKEPOS_NUM_TERMINALS / 3 * 20); ?>,
	    width: <?php echo ($conf->dol_optimize_smallscreen ? 316 : 400); ?>,
	    modal: true,
	    buttons: {
			'<?php echo dol_escape_js($langs->trans("Terminal")) ?> 1': function() {
				location.href='index.php?setterminal=1';
			}
			<?php
			for ($i = 2; $i <= $conf->global->TAKEPOS_NUM_TERMINALS; $i++)
			{
				print ",
				'".dol_escape_js($langs->trans("Terminal"))." ".$i."': function() {
					location.href='index.php?setterminal=".$i."';
				}
				";
			}
			?>
	    }
	});
}

function DirectPayment(){
	console.log("DirectPayment");
	$("#poslines").load("invoice.php?place="+place+"&action=valid&pay=<?php echo $langs->trans("cash"); ?>", function() {
	});
}

function FullScreen() {
	document.documentElement.requestFullscreen();
}

$( document ).ready(function() {
    PrintCategories(0);
	LoadProducts(0);
	Refresh();
	<?php
	//IF NO TERMINAL SELECTED
	if ($_SESSION["takeposterminal"] == "")
	{
		if ($conf->global->TAKEPOS_NUM_TERMINALS == "1") $_SESSION["takeposterminal"] = 1;
		else print "TerminalsDialog();";
	}
	if ($conf->global->TAKEPOS_CONTROL_CASH_OPENING)
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash_fence WHERE ";
		$sql .= "date(date_creation) = CURDATE() ";
		$sql .= "";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			// If there is no cash control from today open it
			if ($obj->rowid == null) print "ControlCashOpening();";
		}
	}
	?>
});
</script>

<body class="bodytakepos" style="overflow: hidden;">
<?php
print '<div class="hidden dialog-info-takepos-terminal" id="dialog-info" title="TakePOS">'.$langs->trans('TerminalSelect').'</div>';
$keyCodeForEnter = $conf->global->{'CASHDESK_READER_KEYCODE_FOR_ENTER'.$_SESSION['takeposterminal']} > 0 ? $conf->global->{'CASHDESK_READER_KEYCODE_FOR_ENTER'.$_SESSION['takeposterminal']} : '';
?>
<div class="container">

<?php
if (empty($conf->global->TAKEPOS_HIDE_HEAD_BAR)) {
	?>
	<div class="header">
		<div class="topnav">
			<div class="topnav-left">
			<div class="inline-block valignmiddle">
			<a class="topnav-terminalhour" onclick="TerminalsDialog();">
			<span class="fa fa-cash-register"></span>
			<span class="hideonsmartphone">
			<?php echo $langs->trans("Terminal"); ?>
			</span>
			<?php echo " ";
			if ($_SESSION["takeposterminal"] == "") echo "1";
			else echo $_SESSION["takeposterminal"];
			echo '<span class="hideonsmartphone"> - '.dol_print_date(dol_now(), "day").'</span>';
			?>
			</a></div>
			<!-- section for customer and open sales -->
			<div class="inline-block valignmiddle" id="customerandsales">
			</div>
			<!-- More info about customer -->
			<div class="inline-block valignmiddle tdoverflowmax150onsmartphone" id="moreinfo"></div>
			<div class="inline-block valignmiddle tdoverflowmax150onsmartphone" id="infowarehouse"></div>
			</div>
			<div class="topnav-right">
				<div class="login_block_other">
				<input type="text" id="search" name="search" onkeyup="Search2(<?php echo $keyCodeForEnter; ?>);" placeholder="<?php echo $langs->trans("Search"); ?>" autofocus>
				<a onclick="ClearSearch();"><span class="fa fa-backspace"></span></a>
				<a onclick="window.location.href='<?php echo DOL_URL_ROOT; ?>';"><span class="fas fa-home"></span></a>
				<?php if (empty($conf->dol_use_jmobile)) { ?>
				<a onclick="FullScreen();"><span class="fa fa-expand-arrows-alt"></span></a>
				<?php } ?>
				</div>
				<div class="login_block_user">
				<?php
				print top_menu_user(1, DOL_URL_ROOT.'/user/logout.php');
				?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>

	<div class="row1<?php if (empty($conf->global->TAKEPOS_HIDE_HEAD_BAR)) print 'withhead'; ?>">

		<div id="poslines" class="div1">
		</div>

		<div class="div2">
			<button type="button" class="calcbutton" onclick="Edit(7);">7</button>
			<button type="button" class="calcbutton" onclick="Edit(8);">8</button>
			<button type="button" class="calcbutton" onclick="Edit(9);">9</button>
			<button type="button" id="qty" class="calcbutton2" onclick="Edit('qty');"><?php echo $langs->trans("Qty"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(4);">4</button>
			<button type="button" class="calcbutton" onclick="Edit(5);">5</button>
			<button type="button" class="calcbutton" onclick="Edit(6);">6</button>
			<button type="button" id="price" class="calcbutton2" onclick="Edit('p');"><?php echo $langs->trans("Price"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(1);">1</button>
			<button type="button" class="calcbutton" onclick="Edit(2);">2</button>
			<button type="button" class="calcbutton" onclick="Edit(3);">3</button>
			<button type="button" id="reduction" class="calcbutton2" onclick="Edit('r');"><?php echo $langs->trans("ReductionShort"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(0);">0</button>
			<button type="button" class="calcbutton" onclick="Edit('.');">.</button>
			<button type="button" class="calcbutton poscolorblue" onclick="Edit('c');">C</button>
			<button type="button" class="calcbutton2 poscolordelete" id="delete" onclick="deleteline();"><span class="fa fa-trash"></span></button>
		</div>

<?php

// TakePOS setup check
$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";

$resql = $db->query($sql);
$paiementsModes = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
        $paycode = $obj->code;
        if ($paycode == 'LIQ') $paycode = 'CASH';
        if ($paycode == 'CHQ') $paycode = 'CHEQUE';

		$constantforkey = "CASHDESK_ID_BANKACCOUNT_".$paycode.$_SESSION["takeposterminal"];
		//var_dump($constantforkey.' '.$conf->global->$constantforkey);
		if (!empty($conf->global->$constantforkey) && $conf->global->$constantforkey > 0) array_push($paiementsModes, $obj);
	}
}

if (empty($paiementsModes)) {
	$langs->load('errors');
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("TakePOS")), null, 'errors');
	setEventMessages($langs->trans("ProblemIsInSetupOfTerminal", $_SESSION["takeposterminal"]), null, 'errors');
}
if (count($maincategories) == 0) {
	setEventMessages($langs->trans("TakeposNeedsCategories"), null, 'errors');
}
// User menu and external TakePOS modules
$menus = array();
$r = 0;

if (empty($conf->global->TAKEPOS_BAR_RESTAURANT))
{
    $menus[$r++] = array('title'=>'<span class="fa fa-layer-group paddingrightonly"></span><div class="trunc">'.$langs->trans("New").'</div>', 'action'=>'New();');
}
else
{
    // BAR RESTAURANT specific menu
    $menus[$r++] = array('title'=>'<span class="fa fa-layer-group paddingrightonly"></span><div class="trunc">'.$langs->trans("Place").'</div>', 'action'=>'Floors();');
}

if (!empty($conf->global->TAKEPOS_HIDE_HEAD_BAR)) {
	$menus[$r++] = array('title'=>'<span class="far fa-building paddingrightonly"></span><div class="trunc">'.$langs->trans("Customer").'</div>', 'action'=>'Customer();');
}
$menus[$r++] = array('title'=>'<span class="fa fa-history paddingrightonly"></span><div class="trunc">'.$langs->trans("History").'</div>', 'action'=>'History();');
$menus[$r++] = array('title'=>'<span class="fa fa-cube paddingrightonly"></span><div class="trunc">'.$langs->trans("FreeZone").'</div>', 'action'=>'FreeZone();');
$menus[$r++] = array('title'=>'<span class="fa fa-percent paddingrightonly"></span><div class="trunc">'.$langs->trans("Reduction").'</div>', 'action'=>'Reduction();');
$menus[$r++] = array('title'=>'<span class="far fa-money-bill-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("Payment").'</div>', 'action'=>'CloseBill();');

if ($conf->global->TAKEPOS_DIRECT_PAYMENT) {
	$menus[$r++] = array('title'=>'<span class="far fa-money-bill-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("DirectPayment").' <span class="opacitymedium">('.$langs->trans("Cash").')</span></div>', 'action'=>'DirectPayment();');
}

// BAR RESTAURANT specific menu
if ($conf->global->TAKEPOS_BAR_RESTAURANT)
{
	if ($conf->global->TAKEPOS_ORDER_PRINTERS)
	{
		$menus[$r++] = array('title'=>$langs->trans("Order"), 'action'=>'TakeposPrintingOrder();');
	}
	//Button to print receipt before payment
	if ($conf->global->TAKEPOS_BAR_RESTAURANT)
	{
	    if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
			if (filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) $menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action'=>'TakeposConnector(placeid);');
			else $menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action'=>'TakeposPrinting(placeid);');
		} elseif ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter") {
			$menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action'=>'DolibarrTakeposPrinting(placeid);');
		} else {
			$menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("Receipt").'</div>', 'action'=>'Print(placeid);');
		}
	}
	if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector" && $conf->global->TAKEPOS_ORDER_NOTES == 1)
	{
	    $menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("OrderNotes").'</div>', 'action'=>'TakeposOrderNotes();');
	}
	if ($conf->global->TAKEPOS_SUPPLEMENTS)
	{
	    $menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("ProductSupplements").'</div>', 'action'=>'LoadProducts(\'supplements\');');
	}
}

if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
    $menus[$r++] = array('title'=>'<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("DOL_OPEN_DRAWER").'</div>', 'action'=>'OpenDrawer();');
}
if ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter") {
    $menus[$r++] = array(
		'title' => '<span class="fa fa-receipt paddingrightonly"></span><div class="trunc">'.$langs->trans("DOL_OPEN_DRAWER").'</div>',
		'action' => 'DolibarrOpenDrawer();',
	);
}

$sql = "SELECT rowid, status FROM ".MAIN_DB_PREFIX."pos_cash_fence WHERE ";
$sql .= "date(date_creation) = CURDATE() ";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		$obj = $db->fetch_object($resql);
		$menus[$r++] = array('title'=>'<span class="fas fa-file-invoice-dollar paddingrightonly"></span><div class="trunc">'.$langs->trans("CashReport").'</div>', 'action'=>'CashReport('.$obj->rowid.');');
		if ($obj->status == 0) $menus[$r++] = array('title'=>'<span class="fas fa-cash-register paddingrightonly"></span><div class="trunc">'.$langs->trans("CloseCashFence").'</div>', 'action'=>'CloseCashFence('.$obj->rowid.');');
	}
}

$hookmanager->initHooks(array('takeposfrontend'));
$reshook = $hookmanager->executeHooks('ActionButtons');
if (!empty($reshook)) {
	if (is_array($reshook) && !isset($reshook['title'])) {
		foreach ($reshook as $reshook) {
			$menus[$r++] = $reshook;
		}
	} else {
		$menus[$r++] = $reshook;
	}
}

if ($r % 3 == 2) $menus[$r++] = array('title'=>'', 'style'=>'visibility: hidden;');

if (!empty($conf->global->TAKEPOS_HIDE_HEAD_BAR)) {
	$menus[$r++] = array('title'=>'<span class="fa fa-sign-out-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("Logout").'</div>', 'action'=>'window.location.href=\''.DOL_URL_ROOT.'/user/logout.php\';');
}

?>
		<!-- Show buttons -->
		<div class="div3">
		<?php
        $i = 0;
        foreach ($menus as $menu)
        {
        	$i++;
        	if (count($menus) > 12 and $i == 12)
        	{
        		echo '<button style="'.$menu['style'].'" type="button" id="actionnext" class="actionbutton" onclick="MoreActions('.count($menus).');">'.$langs->trans("Next").'</button>';
        		echo '<button style="display: none;" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
        	}
            elseif ($i > 12) echo '<button style="display: none;" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
            else echo '<button style="'.$menu['style'].'" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
        }

		if (!empty($conf->global->TAKEPOS_HIDE_HEAD_BAR)) {
			print '<!-- Show the search input text -->'."\n";
			print '<div class="margintoponly">';
			print '<input type="text" id="search" name="search" onkeyup="Search2('.$keyCodeForEnter.');" style="width:80%;width:calc(100% - 51px);font-size: 150%;" placeholder="'.$langs->trans("Search").'" autofocus> ';
			print '<a class="marginleftonly hideonsmartphone" onclick="ClearSearch();">'.img_picto('', 'searchclear').'</a>';
			print '</div>';
		}
        ?>
		</div>
	</div>

	<div class="row2<?php if (empty($conf->global->TAKEPOS_HIDE_HEAD_BAR)) print 'withhead'; ?>">

		<!--  Show categories -->
		<div class="div4">
	<?php
	$count = 0;
	while ($count < $MAXCATEG)
	{
	    ?>
			<div class="wrapper" <?php if ($count == ($MAXCATEG - 2)) echo 'onclick="MoreCategories(\'less\');"'; elseif ($count == ($MAXCATEG - 1)) echo 'onclick="MoreCategories(\'more\');"'; else echo 'onclick="LoadProducts('.$count.');"'; ?> id="catdiv<?php echo $count; ?>">
				<?php
				if ($count == ($MAXCATEG - 2)) {
				    //echo '<img class="imgwrapper" src="img/arrow-prev-top.png" height="100%" id="catimg'.$count.'" />';
				    echo '<span class="fa fa-chevron-left centerinmiddle" style="font-size: 5em;"></span>';
				}
				elseif ($count == ($MAXCATEG - 1)) {
				    //echo '<img class="imgwrapper" src="img/arrow-next-top.png" height="100%" id="catimg'.$count.'" />';
				    echo '<span class="fa fa-chevron-right centerinmiddle" style="font-size: 5em;"></span>';
				}
				else
				{
				    echo '<img class="imgwrapper" height="100%" id="catimg'.$count.'" />';
				}
				?>
				<?php if ($count != ($MAXCATEG - 2) && $count != ($MAXCATEG - 1)) { ?>
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
		<div class="div5">
    <?php
    $count = 0;
    while ($count < $MAXPRODUCT)
    {
        ?>
    			<div class="wrapper2" id='prodiv<?php echo $count; ?>' <?php if ($count == ($MAXPRODUCT - 2)) {?> onclick="MoreProducts('less');" <?php } if ($count == ($MAXPRODUCT - 1)) {?> onclick="MoreProducts('more');" <?php } else echo 'onclick="ClickProduct('.$count.');"'; ?>>
    				<?php
    				if ($count == ($MAXPRODUCT - 2)) {
    				    //echo '<img class="imgwrapper" src="img/arrow-prev-top.png" height="100%" id="proimg'.$count.'" />';
    					print '<span class="fa fa-chevron-left centerinmiddle" style="font-size: 5em;"></span>';
    				}
    				elseif ($count == ($MAXPRODUCT - 1)) {
    				    //echo '<img class="imgwrapper" src="img/arrow-next-top.png" height="100%" id="proimg'.$count.'" />';
    					print '<span class="fa fa-chevron-right centerinmiddle" style="font-size: 5em;"></span>';
    				}
    				else
    				{
    					print '<div class="" id="proprice'.$count.'"></div>';
    					print '<img class="imgwrapper" height="100%" title="" id="proimg'.$count.'">';
    				}
    				?>
					<?php if ($count != ($MAXPRODUCT - 2) && $count != ($MAXPRODUCT - 1)) { ?>
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
		</div>
	</div>
</div>
</body>
<?php

llxFooter();

$db->close();
