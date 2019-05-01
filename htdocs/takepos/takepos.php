<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	Josep Lluís Amador	<joseplluis@lliuretic.cat>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';	//V20
require_once DOL_DOCUMENT_ROOT.'/takepos/lib/takepos.lib.php';	//V20

$langs->load('takepos@takepos');
$langs->loadLangs(array("bills","orders","commercial","cashdesk","receiptprinter"));



$action = GETPOST('action', 'alpha');
$place= GETPOST('place', 'int');
if ($place=="") $place="0";

//****************** V20
$facid= GETPOST('facid', 'int');	//V20
if ($facid=="") $facid="0";
$nb = GETPOST('nb', 'int');			//V20: quantity of products
if(empty($nb))	$nb=1;


$ticket=array();	//V20
$ticket=load_ticket($place, $facid);
$diners=$ticket['diners'];	
$facid=$ticket['facid'];	//Reload, may be empty
$place=$ticket['place'];
$placelabel=$ticket['placelabel'];



if(GETPOST('mainmenu')=='takepos'){		//V20: Viene directamente de Dolibarr si pasar por index.
	$term=0;	//Evitamos cobros e impresiones.							
	header('Location: index.php?term=0');	//V20
	exit;
}
//V20: Terminal
$term=$_SESSION['term'];


$x=('TAKEPOS_PRINT_SERVER'.$term);
$print_server=$conf->global->$x;
//V20: Constants
const BUT_PRODUCTS=22;	//Default=30
const BUT_CATEGORIES=10;//Default=14
const BUT_MENU=12;		//Default=9

/*
 * View
 */

$categorie = new Categorie($db);
$categories = $categorie->get_full_arbo('product');

//V20: Category for POS. Show subcategories and products below this one.
if($conf->global->POS_ID_CATEGORY>0){
	$catid=$conf->global->POS_ID_CATEGORY;
	$maincategories = array_filter($categories, function ($item) use ($catid) {
	    if ($item['fk_parent']==$catid)    	return true;
	    
	    return false;
	});
	
	$subcategories = array_filter($categories, function ($item) {
	    if (($item['level']>2) !== false)    {
	    	return true;
	    }
	    
	    return false;
	});
}else{
	$maincategories = array_filter($categories, function ($item) {
	    if (($item['level']==1) !== false)    {
	    	return true;
	    }
	    
	    return false;
	});
	
	$subcategories = array_filter($categories, function ($item) {
	    if (($item['level']!=1) !== false)    {
	    	return true;
	    }
	    
	    return false;
	});
}
$maincategories=array_values($maincategories);	//V20: Sorting keys

//V20: Notes category
if($conf->global->POS_NOTE_CATEGORY>0){
	$catid=$conf->global->POS_NOTE_CATEGORY;
	$notecategories = array_filter($categories, function ($item) use ($catid) {
	    if ($item['fk_parent']==$catid)    	return true;
	    
	    return false;
	});
	$subcategories+=$notecategories;
}

//$x=json_encode($maincategories);

?>
<script language="javascript">
var categories = <?php echo json_encode($maincategories); ?>;
var subcategories = <?php echo json_encode($subcategories); ?>;

var BUT_CATEGORIES="<?php echo BUT_CATEGORIES;?>";	//V20
var BUT_PRODUCTS="<?php echo BUT_PRODUCTS;?>";	//V20
var BUT_MENU="<?php echo BUT_MENU;?>";	//V20

//var fullscreen=0;	//V20
var currentcat;
var pageproducts=0;
var pagecategories=0;
var pageactions=0;
var place="<?php echo $place;?>";
var facid="<?php echo $facid;?>";	//V20
var editaction="";	//V20
var editnumber="";
function PrintCategories(first){

	
	for (i = 0; i < BUT_CATEGORIES; i++) {
		
		if (typeof (categories[parseInt(i)+parseInt(first)]) == "undefined") break;
		$("#catdesc"+i).text(categories[parseInt(i)+parseInt(first)]['label']);
        $("#catimg"+i).attr("src","genimg/?query=cat&id="+categories[parseInt(i)+parseInt(first)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[parseInt(i)+parseInt(first)]['rowid']);
        $("#catwatermark"+i).show();
	}
}

function MoreCategories(moreorless){
	if (moreorless=="more"){
		$('#catimg'+BUT_CATEGORIES+1).animate({opacity: '0.5'}, 1);
		$('#catimg'+BUT_CATEGORIES+1).animate({opacity: '1'}, 100);
		pagecategories=pagecategories+1;
	}
	if (moreorless=="less"){
		$('#catimg'+BUT_CATEGORIES).animate({opacity: '0.5'}, 1);
		$('#catimg'+BUT_CATEGORIES).animate({opacity: '1'}, 100);
		if (pagecategories==0) return; //Return if no less pages
		pagecategories=pagecategories-1;
	}
	if (typeof (categories[BUT_CATEGORIES*pagecategories] && moreorless=="more") == "undefined"){ // Return if no more pages
		pagecategories=pagecategories-1;
		return;
	}
	for (i = 0; i < BUT_CATEGORIES; i++) {
		if (typeof (categories[i+(BUT_CATEGORIES*pagecategories)]) == "undefined"){
				$("#catdesc"+i).text("");
				$("#catimg"+i).attr("src","genimg/empty.png");
				$("#catwatermark"+i).hide();
				continue;
			}
		$("#catdesc"+i).text(categories[i+(BUT_CATEGORIES*pagecategories)]['label']);
        $("#catimg"+i).attr("src","genimg/?query=cat&id="+categories[i+(BUT_CATEGORIES*pagecategories)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[i+(BUT_CATEGORIES*pagecategories)]['rowid']);
        $("#catwatermark"+i).show();
	}
}

function LoadProducts(position, issubcat=false){
	if(position!='notes')	
	{
	    $('#catimg'+position).animate({opacity: '0.5'}, 1);
		$('#catimg'+position).animate({opacity: '1'}, 100);
		if (issubcat==true) currentcat=$('#prodiv'+position).data('rowid');
		else currentcat=$('#catdiv'+position).data('rowid');
	    if (currentcat=="undefined") return;
		pageproducts=0;
	}
	ishow=0; //product to show counter

	jQuery.each(subcategories, function(i, val) {
		if (currentcat==val.fk_parent){
			$("#subcat"+ishow).attr("style","background-color: darkgreen; font-size: medium;");	//V20: Subcategory, change color
			$("#prodesc"+ishow).text(val.label);
			$("#proimg"+ishow).attr("src","genimg/?query=cat&id="+val.rowid);
			$("#prodiv"+ishow).data("rowid",val.rowid);
			$("#prodiv"+ishow).data("iscat",1);
			ishow++;
		}else{
			
			
		}
		
	});

	idata=0; //product data counter
	$.getJSON('./ajax.php?action=getProducts&category='+currentcat, function(data) {
		
		while (ishow < BUT_PRODUCTS) {
			$("#subcat"+ishow).attr("style","");	//V20: No subcategory
			if (typeof (data[idata]) == "undefined") {
				$("#prodesc"+ishow).text("");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				ishow++; //Next product to show after print data product
			}
			else if ((data[idata]['status']) == "1") {
				//Only show products with status=1 (for sell)
				$("#prodesc"+ishow).text(data[parseInt(idata)]['label']);
				
				$("#proimg"+ishow).attr("src","genimg/?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid",data[idata]['id']);
				$("#prodiv"+ishow).data("iscat",0);
				ishow++; //Next product to show after print data product
			}
			$("#prowatermark"+ishow).hide();
			idata++; //Next data everytime
			
		}
	});
}

function MoreProducts(moreorless){
	if (moreorless=="more"){
		$('#proimg'+BUT_PRODUCTS+1).animate({opacity: '0.5'}, 1);
		$('#proimg'+BUT_PRODUCTS+1).animate({opacity: '1'}, 100);
		pageproducts=pageproducts+1;
	}
	if (moreorless=="less"){
		$('#proimg'+BUT_PRODUCTS).animate({opacity: '0.5'}, 1);
		$('#proimg'+BUT_PRODUCTS).animate({opacity: '1'}, 100);
		if (pageproducts==0) return; //Return if no less pages
		pageproducts=pageproducts-1;
	}
	$.getJSON('./ajax.php?action=getProducts&category='+currentcat, function(data) {
		if (typeof (data[(BUT_PRODUCTS*pageproducts)]) == "undefined" && moreorless=="more"){ // Return if no more pages
			pageproducts=pageproducts-1;
			return;
		}
		idata=BUT_PRODUCTS*pageproducts; //product data counter
		ishow=0; //product to show counter

		//V20: TODO: This while block is repeted in function LoadProducts. Please rebuild and compact.
		while (ishow < BUT_PRODUCTS) {
			if (typeof (data[idata]) == "undefined") {
				
				$("#prodesc"+ishow).text("");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				ishow++; //Next product to show after print data product
			}
			else if ((data[idata]['status']) == "1") {
				//Only show products with status=1 (for sell)
				$("#prodesc"+ishow).text(data[parseInt(idata)]['label']);
				
				$("#proimg"+ishow).attr("src","genimg/?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid",data[idata]['id']);
				$("#prodiv"+ishow).data("iscat",0);
				ishow++; //Next product to show after print data product
			}
			$("#prowatermark"+ishow).hide();
			idata++; //Next data everytime
		}
	});
}

function ClickProduct(position){
    $('#proimg'+position).animate({opacity: '0.5'}, 1);
	$('#proimg'+position).animate({opacity: '1'}, 100);
	if ($('#prodiv'+position).data('iscat')==1){
		LoadProducts(position, true);
	}
	else{
		idproduct=$('#prodiv'+position).data('rowid');
		desc=$('#prodesc'+position).text();
		if (idproduct=="") return;
		if (selectedline>0){
			//url="invoice.php?action=addnote&place="+place+"&number="+selectedline+"&desc="+desc+"&idproduct="+idproduct";
			url="invoice.php?action=addnote2&place="+place+"&idline="+selectedline+"&idproduct="+idproduct;

			$("#poslines").load(url, function() {
				$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});
		}else{
			$("#poslines").load("invoice.php?action=addline&place="+place+"&idproduct="+idproduct+"&nb="+$('#keybuffer').val(), function() {
				$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});
		}
		Edit('clear');	//V20: Clear keybuffer
	}
}

function deleteline(){
	$("#poslines").load("invoice.php?action=deleteline&place="+place+"&idline="+selectedline, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function Customer(){
	$.colorbox({href:"customers.php?nomassaction=1&place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Customer");?>"});
}


function CloseBill(){
	$.colorbox({href:"pay.php?place="+place, width:"80%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("CloseBill");?>"});
}

function Floors(){
	$.colorbox({href:"floors.php?place="+place, width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Floors");?>"});
}

function FreeZone(){
	//$.colorbox({href:"freezone.php?place="+place, onClosed: function () { Refresh(); },width:"80%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("FreeZone");?>"});
	$.colorbox({href:"freezone.php?action=freezone&place="+place, onClosed: function () { Refresh(); },width:"80%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("FreeZone");?>"});
}

function TakeposOrderNotes(){
	$.colorbox({href:"freezone.php?action=addnote&place="+place+"&idline="+selectedline, onClosed: function () { Refresh(); },width:"80%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("OrderNotes");?>"});
	
}



function Refresh(){
	$("#poslines").load("invoice.php?place="+place+"&refresh=1", function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}
//*******************************************************
//V20: Begin

function LoadNotes(notescat)
{
	currentcat=notescat;
	LoadProducts('notes',false);
}

	
function CashFence(){
	$.colorbox({href:"cash_fence.php?place="+place, onClosed: function () { Refresh(); },width:"40%", height:"65%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("CashFence");?>"});
}

function MoveTable(){
	$.colorbox({href:"floors.php?place="+place+'&action=movetable', width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Floors");?>"});
}

function Diners(){
	if (selectedline>0){
		url="invoice.php?action=adddiner&place="+place+"&idline="+selectedline+"&number="+$('#keybuffer').val();
		$("#poslines").load(url, function() {
			$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
		});
	}else{
		//Sin usar searchbox
		$.getJSON('./ajax.php?action=diners&place='+place+'&term='+$('#keybuffer').val()+'&facid='+facid, function(data) {
				$("#poslines").load("invoice.php?action=diners&facid="+facid+"&number="+data[0], function() {
			        $("#poslines").scrollTop($("#poslines")[0].scrollHeight);
			    });
			});
	}
	SearchBarcode(0);
	Edit('clear');	//Clear keybuffer

}

function SearchTicket(){
	$("#searchbox").load("search.php?action=ticket&place="+place, function() {
		//$('#searchbox').scrollTop($('#searchbox')[0].scrollHeight);
		$('#keyvalue').focus();
	});
}

function SearchCustomer(val){
	$("#searchbox").load("search.php?action=customer"+val+"&place="+place, function() {
		//$('#searchbox').scrollTop($('#searchbox')[0].scrollHeight);
		$('#keyvalue').focus();
	});
}

function SearchProduct(){
	$("#searchbox").load("search.php?action=product&place="+place, function() {
		//$('#searchbox').scrollTop($('#searchbox')[0].scrollHeight);
		$('#search').focus();
	});
}

function SearchBarcode(){
	$("#searchbox").load("search.php?action=barcode&place="+place, function() {
		//$('#searchbox').scrollTop($('#searchbox')[0].scrollHeight);
		$('#barcode').focus();
	});
}

function InputBarcode(){
	pageproducts=0;
	
	$.getJSON('./ajax.php?action=search&term='+$('#barcode').val(), function(data) {
		$('#barcode').val('');
		$("#poslines").load("invoice.php?action=addline_barcode&place="+place+"&idproduct="+data[0]['rowid'], function() {
			$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
		});
	});
}

function Search2Ticket(){
	pageproducts=0;

		$.getJSON('./ajax.php?action=ticket&place='+place+'&term='+$('#keyvalue').val(), function(data) {

			parent.$("#takepos").load("takepos.php?facid="+data['rowid']);
		});
		SearchBarcode(0);
		Edit('clear');	//Clear keybuffer
}

function Search2Customer(val){
	pageproducts=0;
	
		$.getJSON('./ajax.php?action=customer'+val+'&place='+place+'&term='+$('#keyvalue').val()+'&facid='+$('#facid').val(), function(data) {
			$("#poslines").load("invoice.php?action=customer&place="+place+'&facid='+$('#facid').val(), function() {
		        $("#poslines").scrollTop($("#poslines")[0].scrollHeight);
		    });
		});
		SearchBarcode(0);
		Edit('clear');	//Clear keybuffer
}

function Search2Barcode(event){
	pageproducts=0;
	var k=event.keyCode;
	
	if(k==13){	//INTRO
		$.getJSON('./ajax.php?action=search&term='+$('#barcode').val(), function(data) {
			//if (data.length)>1) return;
			$('#barcode').val('');
			$("#poslines").load("invoice.php?action=addline_barcode&place="+place+"&idproduct="+data[0]['rowid'], function() {
				$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});
		});
		
	}
}


function NewCustomer(){
	$.colorbox({href:"soc.php?place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Customer");?>"});
}

function TicketList(){
	$.colorbox({href:"ticket_list.php?place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Tickets");?>"});
}

//V20: End
//****************************************************************************

function Search(){
	$("#poslines").load("invoice.php?action=search&place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function Search2(event){
	pageproducts=0;
	
	$.getJSON('./ajax.php?action=search&term='+$('#search').val(), function(data) {
		for (i = 0; i < BUT_PRODUCTS; i++) {
			if (typeof (data[i]) == "undefined"){
				$("#prodesc"+i).text("");
				$("#proimg"+i).attr("src","genimg/empty.png");
                $("#prodiv"+i).data("rowid","");
				continue;
			}
			$("#prodesc"+i).text(data[parseInt(i)]['label']);
			$("#proimg"+i).attr("src","genimg/?query=pro&id="+data[i]['rowid']);
			$("#prodiv"+i).data("rowid",data[i]['rowid']);
			$("#prodiv"+i).data("iscat",0);
		}
	});
	
}

function Edit(number){
    var text=selectedtext+"<br> ";

    if (number=='clear'){	//V20
    	editnumber="";
    	$('#keybuffer').val('');	//V20
    	return;
    }
    else if (number=='c'){
        oldqty=$('#'+selectedline).find("td:nth-child(2)").html();
        
        if(selectedline>0 && editaction=='qty' && editnumber>0){
        	 editnumber=-1*editnumber;
             //editaction='c';
        }
        else{
        	$("#qty").html("<?php echo $langs->trans("Qty"); ?>");
        	$("#qty").attr("style","background-color:");
        	$("#price").html("<?php echo $langs->trans("Price"); ?>");
        	$("#price").attr("style","background-color:");
            $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
            $("#reduction").attr("style","background-color:");
            editaction='';	//V20
	        editnumber="";
	        $('#keyvalue').val('');	//V20
	        $('#keybuffer').val('');	//V20
	        Refresh();
	        return;
        }
        
    }
    else if (number=='qty'){
        if (editaction=='qty' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updateqty&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                $('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
                $("#qty").attr("style","background-color:");
                $('#keyvalue').val('');	//V20
                $('#keybuffer').val('');	//V20
            });
            editaction='';	//V20
            return;
        }
        else {
            editaction="qty";
        }
    }
    else if (number=='p'){
        if (editaction=='p' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updateprice&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                $('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#price").html("<?php echo $langs->trans("Price"); ?>");
                $("#price").attr("style","background-color:");
                $('#keyvalue').val('');	//V20
                $('#keybuffer').val('');	//V20
            });
            editaction='';	//V20
            return;
        }
        else {
            editaction="p";
        }
    }
    else if (number=='r'){
        if (editaction=='r' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updatereduction&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                $('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
                $("#reduction").attr("style","background-color:");
                $('#keyvalue').val('');	//V20
                $('#keybuffer').val('');	//V20
            });
            editaction='';	//V20
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
        $("#qty").attr("style","background-color:lightcyan");
        $("#price").html("<?php echo $langs->trans("Price"); ?>");
        $("#price").attr("style","background-color:");
        $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
        $("#reduction").attr("style","background-color:");
    }
    if (editaction=='p'){
        text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("Price").": "; ?>";
        $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
        $("#qty").attr("style","background-color:");
        $("#price").html("OK");
        $("#price").attr("style","background-color:lightcyan");
        $("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
        $("#reduction").attr("style","background-color:");
    }
    if (editaction=='r'){
        text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("ReductionShort").": "; ?>";
        $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
        $("#qty").attr("style","background-color:");
        $("#price").html("<?php echo $langs->trans("Price"); ?>");
        $("#price").attr("style","background-color:");
        $("#reduction").html("OK");
        $("#reduction").attr("style","background-color:lightcyan");
    }
    if(editaction>'')	$('#'+selectedline).find("td:first").html(text+editnumber);
    $('#keyvalue').val(editnumber);	//V20
    $('#keybuffer').val(editnumber);	//V20
}

function TakeposPrintingOrder(){
	$("#poslines").load("invoice.php?action=order&place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function TakeposPrintingTemp(){
	$("#poslines").load("invoice.php?action=temp&place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

//V20: TODO: This way of open drawer always print a piece of paper. This is not nice.
function OpenDrawer(){
	$.ajax({
			type: "POST",
			url: 'http://<?php print $print_server;?>:8111/print',
			data: "opendrawer"
		});
}

function MoreActions(totalactions){
	if (pageactions==0){
		pageactions=1;
		for (i = 0; i <= totalactions; i++){
			if (i<BUT_MENU) $("#action"+i).hide();
			else $("#action"+i).show();
		}
	}
	else if (pageactions==1){
		pageactions=0;
		for (i = 0; i <= totalactions; i++){
			if (i<BUT_MENU) $("#action"+i).show();
			else $("#action"+i).hide();
		}
	}
}

$( document ).ready(function() {
	
	//if(fullscreen==0)	FullScreen(1);	V20: Moved to index.php, better speed
    PrintCategories(0);
	LoadProducts(0);
	
	SearchBarcode(0);	//V20
	Refresh();
	
});
</script>

<?php 
print '<body style="overflow: hidden; background-color:#D1D1D1;">';
?>
<div class="container">
<?php 

?>	
	<div class="row1">
		
		<div id="poslines" class="div1" >
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
			<button type="button" class="calcbutton" onclick="Edit('c');">-/C</button>
			<button type="button" class="calcbutton2" id="delete" style="color: red;" onclick="deleteline();"><b>X</b></button>
			
			<div id="searchbox" class="div2_search"><?php //echo date('dd/mm/yyyy'); ?></div>
		</div>
		
		
<?php
// TakePOS setup check
if (empty($conf->global->CASHDESK_ID_THIRDPARTY) or empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH1) or empty($conf->global->CASHDESK_ID_BANKACCOUNT_CB)) {
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete"), null, 'errors');
}
//Update
$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql.= " WHERE entity IN (".getEntity('c_paiement').")";
$sql.= " AND active = 1";
$sql.= " ORDER BY libelle";
$resql = $db->query($sql);

$paiementsModes = array();
if ($resql){
	while ($obj = $db->fetch_object($resql)){
        $paycode = $obj->code;
        if ($paycode == 'LIQ') $paycode = 'CASH';
        if ($paycode == 'CB')  $paycode = 'CARD';
        if ($paycode == 'CHQ') $paycode = 'CHEQUE';
        
		$accountname="CASHDESK_ID_BANKACCOUNT_".$paycode;
		if (! empty($conf->global->$accountname) && $conf->global->$accountname > 0) array_push($paiementsModes, $obj);
	}
}
if (empty($paiementsModes)) {
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete"), null, 'errors');
}

if (count($maincategories)==0) {
	setEventMessages($langs->trans("TakeposNeedsCategories"), null, 'errors');
}
// User menu and external TakePOS modules
$menus = array();
$r=0;
//V20: Buttons sorted by priority
if ($conf->global->TAKEPOSCONNECTOR){
    $menus[$r++]=array('title'=>$langs->trans("DOL_OPEN_DRAWER"),	'color'=>'lightcyan','action'=>'OpenDrawer();');
}

$menus[$r++]=array('title'=>$langs->trans("ValidateBill"),	'color'=>'red','action'=>'CloseBill();');

//$menus[$r++]=array('title'=>$langs->trans("FullScreen"),     'color'=>'','action'=>'FullScreen();');	//V20
//$menus[$r++]=array('title'=>$langs->trans("Customer"),		'action'=>'Customer();');
//$menus[$r++]=array('title'=>$langs->trans("NewCustomer"),   'action'=>'NewCustomer();');	//V20

//$menus[$r++]=array('title'=>$langs->trans("Logout"),        'color'=>'lightcyan','action'=>'window.location.href=\''.DOL_URL_ROOT.'/user/logout.php\';');

//BAR RESTAURANT specified menu
if($conf->global->TAKEPOS_BAR_RESTAURANT){
	$menus[$r++]=array('title'=>$langs->trans("Floors"),	'color'=>'lightyellow','action'=>'Floors();');
	$menus[$r++]=array('title'=>$langs->trans("Diners"),	'color'=>'lightblue','action'=>'Diners();');	//V20
	$menus[$r++]=array('title'=>$langs->trans("MoveTable"),	'color'=>'bisque','action'=>'MoveTable();');	//V20
	$menus[$r++]=array('title'=>$langs->trans("Booking"),	'color'=>'lightsalmon','action'=>'Booking();');	//V20
	
	if ($conf->global->TAKEPOS_ORDER_PRINTERS){
		$menus[$r++]=array('title'=>$langs->trans("Order"),	'color'=>'greenyellow','action'=>'TakeposPrintingOrder();');
	}else $menus[$r++]=array('title'=>$langs->trans("Order"),	'color'=>'greenyellow','action'=>'PrintOrder(placeid,place);');
	//add temp ticket button
	if ($conf->global->TAKEPOS_BAR_RESTAURANT){
		if ($conf->global->TAKEPOSCONNECTOR) $menus[$r++]=array('title'=>$langs->trans("Receipt"),'color'=>'pink','action'=>'TakeposPrinting(placeid);');
		else $menus[$r++]=array('title'=>$langs->trans("Receipt"),'color'=>'pink','action'=>'Print(placeid);');
	}
	
	
}

$menus[$r++]=array('title'=>$langs->trans("Customer"),		'color'=>'','action'=>"SearchCustomer('VAT');");	//V20
$menus[$r++]=array('title'=>$langs->trans("SearchTicket"),	'color'=>'','action'=>'SearchTicket();');
$menus[$r++]=array('title'=>$langs->trans("Notes"),			'color'=>'khaki','action'=>'LoadNotes('.$conf->global->POS_NOTE_CATEGORY.');');
$menus[$r++]=array('title'=>$langs->trans("FreeZone"),      'color'=>'','action'=>'FreeZone();');

if ($conf->global->TAKEPOS_BAR_RESTAURANT && $conf->global->TAKEPOSCONNECTOR && $conf->global->TAKEPOS_ORDER_NOTES==1){
		$menus[$r++]=array('title'=>$langs->trans("OrderNotes"),'action'=>'TakeposOrderNotes();');
	}

//V20: To here are 11 buttons, then next page....
$menus[$r++]=array('title'=>$langs->trans("SearchProduct"),	'color'=>'','action'=>'SearchBarcode();');

if($user->rights->takepos->backoffice || $user->admin)
	$menus[$r++]=array('title'=>$langs->trans("BackOffice"),'color'=>'lightcyan','action'=>'window.open(\''.DOL_URL_ROOT.'\', \'backoffice\');');
$menus[$r++]=array('title'=>$langs->trans("CashFence"),     'color'=>'','action'=>'CashFence();');


$hookmanager->initHooks(array('takeposfrontend'));
$reshook=$hookmanager->executeHooks('ActionButtons');
if (!empty($reshook)) {
    $menus[$r++]=$reshook;
}

?>
		<div class="div3">
<?php
$i = 0;
foreach($menus as $menu) {
	$i++;
	if (count($menus)>BUT_MENU and $i==BUT_MENU)
	{
		//echo '<button type="button" id="actionnext" class="actionbutton" onclick="MoreActions('.count($menus).');"><big'.$langs->trans("Next").'</button>';
		echo '<button type="button" id="actionnext" class="actionbutton" onclick="MoreActions('.count($menus).');" style="font-size: xx-large">...</button>';	//v20
		//echo '<button type="button" id="actionnext" class="actionbutton" onclick="MoreActions('.count($menus).');"><img class="imgwrapper" width="80%" height="80%" src="img/arrow-next.png"></button>';
		echo '<button style="display: none; background-color: '.$menu['color'].';" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
	}
	elseif ($i>BUT_MENU) echo '<button style="display: none; background-color: '.$menu['color'].';" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
	else 				 echo '<button style="background-color: '.$menu['color'].';"               type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
}
?>
	</div>
	</div>
	<div class="row2">
	<div class="div4">
	<?php
	$count=0;
	while ($count<BUT_CATEGORIES+2)
	{
	?>
			<div class='wrapper' <?php 
			if ($count==BUT_CATEGORIES) 		echo 'onclick="MoreCategories(\'less\');"'; 
			elseif ($count==BUT_CATEGORIES+1) 	echo 'onclick="MoreCategories(\'more\');"'; 
			else echo 'onclick="LoadProducts('.$count.');"';?> 
			id='catdiv<?php echo $count;?>'>
			
			<img class='imgwrapper' <?php 
			if ($count==BUT_CATEGORIES) echo 'src="img/arrow-prev-top.png"'; 
			if ($count==BUT_CATEGORIES+1) echo 'src="img/arrow-next-top.png"';?> 
			width="100%" height="85%"  id='catimg<?php echo $count;?>'/>
				
				<div class='description' style="background-color: darkgreen; font-size: medium;">	<!--V20-->
					<div class='description_content' id='catdesc<?php echo $count;?>'></div>
				</div>
				<div class="catwatermark" id='catwatermark<?php echo $count;?>'>+</div>
			</div>
			
	<?php
    $count++;
	}
	?>
		</div>

		<div class="div5">
<?php
$count=0;
while ($count<BUT_PRODUCTS+2)
{
?>
			<div class='wrapper2' id='prodiv<?php echo $count;?>' <?php
			if ($count==BUT_PRODUCTS) 				{?> onclick="MoreProducts('less');" <?php } 
			elseif ($count==BUT_PRODUCTS+1) 			{?> onclick="MoreProducts('more');" <?php } 
			else echo 'onclick="ClickProduct('.$count.');"';?>>
			
				<img class='imgwrapper' <?php 
				if ($count==BUT_PRODUCTS) echo 'src="img/arrow-prev-top.png"'; 
				if ($count==BUT_PRODUCTS+1) echo 'src="img/arrow-next-top.png"';?>
				 height="85%"  id='proimg<?php echo $count;?>'/>
				
				<div class='description' id='subcat<?php echo $count;?>'>	<!--V20-->
					<div class='description_content' id='prodesc<?php echo $count;?>'></div>
				</div>
				<div class="catwatermark" id='prowatermark<?php echo $count;?>'>+</div>
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
