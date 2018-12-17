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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$place = GETPOST('place','int');
if ($place=="") $place="0";
$action = GETPOST('action','alpha');

$langs->loadLangs(array("bills","orders","commercial","cashdesk","receiptprinter"));


/*
 * View
 */

// Title
$title='TakePOS - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title='TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
$head='<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

?>
<link rel="stylesheet" href="css/pos.css?a=xxx">
<script type="text/javascript" src="js/takepos.js" ></script>
<link rel="stylesheet" href="css/colorbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>
<script language="javascript">
<?php
$categorie = new Categorie($db);
$categories = $categorie->get_full_arbo('product');
?>
var categories = JSON.parse( '<?php echo json_encode($categories);?>' );
var currentcat;
var pageproducts=0;
var pagecategories=0;
var place="<?php echo $place;?>";
var editaction="qty";
var editnumber="";
function PrintCategories(first){
	for (i = 0; i < 14; i++) {
		if (typeof (categories[parseInt(i)+parseInt(first)]) == "undefined") break;
		$("#catdesc"+i).text(categories[parseInt(i)+parseInt(first)]['label']);
        $("#catimg"+i).attr("src","genimg/?query=cat&w=55&h=50&id="+categories[parseInt(i)+parseInt(first)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[parseInt(i)+parseInt(first)]['rowid']);
	}
}

function MoreCategories(moreorless){
	if (moreorless=="more"){
		$('#catimg15').animate({opacity: '0.5'}, 1);
		$('#catimg15').animate({opacity: '1'}, 100);
		pagecategories=pagecategories+1;
	}
	if (moreorless=="less"){
		$('#catimg14').animate({opacity: '0.5'}, 1);
		$('#catimg14').animate({opacity: '1'}, 100);
		if (pagecategories==0) return; //Return if no less pages
		pagecategories=pagecategories-1;
	}
	if (typeof (categories[14*pagecategories] && moreorless=="more") == "undefined"){ // Return if no more pages
		pagecategories=pagecategories-1;
		return;
	}
	for (i = 0; i < 14; i++) {
		if (typeof (categories[i+(14*pagecategories)]) == "undefined"){
				$("#catdesc"+i).text("");
				$("#catimg"+i).attr("src","");
				continue;
			}
		$("#catdesc"+i).text(categories[i+(14*pagecategories)]['label']);
        $("#catimg"+i).attr("src","genimg/?query=cat&w=55&h=50&id="+categories[i+(14*pagecategories)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[i+(14*pagecategories)]['rowid']);
	}
}

function LoadProducts(position){
    $('#catimg'+position).animate({opacity: '0.5'}, 1);
	$('#catimg'+position).animate({opacity: '1'}, 100);
	currentcat=$('#catdiv'+position).data('rowid');
    if (currentcat=="") return;
	pageproducts=0;
	$.getJSON('./ajax.php?action=getProducts&category='+currentcat, function(data) {
		for (i = 0; i < 30; i++) {
			if (typeof (data[i]) == "undefined"){
				$("#prodesc"+i).text("");
				$("#proimg"+i).attr("src","");
                $("#prodiv"+i).data("rowid","");
				continue;
			}
			$("#prodesc"+i).text(data[parseInt(i)]['label']);
			$("#proimg"+i).attr("src","genimg/?query=pro&w=55&h=50&id="+data[i]['id']);
			$("#prodiv"+i).data("rowid",data[i]['id']);
		}
	});
}

function MoreProducts(moreorless){
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
	$.getJSON('./ajax.php?action=getProducts&category='+currentcat, function(data) {
		if (typeof (data[(30*pageproducts)]) == "undefined" && moreorless=="more"){ // Return if no more pages
			pageproducts=pageproducts-1;
			return;
		}
		for (i = 0; i < 30; i++) {
			if (typeof (data[i+(30*pageproducts)]) == "undefined"){
				$("#prodesc"+i).text("");
				$("#proimg"+i).attr("src","");
                $("#prodiv"+i).data("rowid","");
				continue;
			}
			$("#prodesc"+i).text(data[parseInt(i+(30*pageproducts))]['label']);
			$("#proimg"+i).attr("src","genimg/?query=pro&w=55&h=50&id="+data[i+(30*pageproducts)]['id']);
			$("#prodiv"+i).data("rowid",data[i+(30*pageproducts)]['id']);
		}
	});
}

function ClickProduct(position){
    $('#proimg'+position).animate({opacity: '0.5'}, 1);
	$('#proimg'+position).animate({opacity: '1'}, 100);
	idproduct=$('#prodiv'+position).data('rowid');
    if (idproduct=="") return;
	$("#poslines").load("invoice.php?action=addline&place="+place+"&idproduct="+idproduct, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});

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
	$.colorbox({href:"freezone.php?place="+place, onClosed: function () { Refresh(); },width:"80%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("FreeZone");?>"});
}

function Refresh(){
	$("#poslines").load("invoice.php?place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function Search(){
	$("#poslines").load("invoice.php?action=search&place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function Search2(){
	pageproducts=0;
	$.getJSON('./ajax.php?action=search&term='+$('#search').val(), function(data) {
		for (i = 0; i < 30; i++) {
			if (typeof (data[i]) == "undefined"){
				$("#prodesc"+i).text("");
				$("#proimg"+i).attr("src","");
                $("#prodiv"+i).data("rowid","");
				continue;
			}
			$("#prodesc"+i).text(data[parseInt(i)]['label']);
			$("#proimg"+i).attr("src","genimg/?query=pro&w=55&h=50&id="+data[i]['rowid']);
			$("#prodiv"+i).data("rowid",data[i]['rowid']);
		}
	});
}

function Edit(number){
    var text=selectedtext+"<br> ";
    if (number=='c'){
        editnumber="";
        Refresh();
        return;
    }
    else if (number=='qty'){
        if (editaction=='qty' && editnumber!=""){
            $("#poslines").load("invoice.php?action=updateqty&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
                editnumber="";
                $('#poslines').scrollTop($('#poslines')[0].scrollHeight);
                $("#qty").html("<?php echo $langs->trans("Qty"); ?>");
            });
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
            });
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
            });
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
	$("#poslines").load("invoice.php?action=order&place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function TakeposPrintingTemp(){
	$("#poslines").load("invoice.php?action=temp&place="+place, function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function OpenDrawer(){
	$.ajax({
			type: "POST",
			url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER;?>:8111/print',
			data: "opendrawer"
		});
}

$( document ).ready(function() {
    PrintCategories(0);
	LoadProducts(0);
	Refresh();
});
</script>

<body style="overflow: hidden; background-color:#E8E8E8;">

<div id="poslines" style="position:absolute; top:2%; left:0.5%; height:36%; width:31%; overflow: auto;">
</div>

<div style="position:absolute; top:1%; left:32.5%; height:37%; width:32.5%; font-size: 0;">
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
    <button type="button" class="calcbutton" onclick="Edit('c');">C</button>
    <button type="button" class="calcbutton2" id="delete" style="color: red;" onclick="deleteline();"><b>X</b></button>
</div>

<?php
// User menu and external TakePOS modules
$menus = array();
$r=0;
$menus[$r++]=array('title'=>$langs->trans("SearchProduct"),
					'action'=>'Search();');
$menus[$r++]=array('title'=>$langs->trans("FreeZone"),
                   'action'=>'FreeZone();');
$menus[$r++]=array('title'=>$langs->trans("Customer"),
					'action'=>'Customer();');
$menus[$r++]=array('title'=>$langs->trans("BackOffice"),
                   'action'=>'window.open(\''.DOL_URL_ROOT.'\', \'backoffice\');');
$menus[$r++]=array('title'=>$langs->trans("ValidateBill"),
					'action'=>'CloseBill();');
$menus[$r++]=array('title'=>$langs->trans("Logout"),
                   'action'=>'window.location.href=\''.DOL_URL_ROOT.'/user/logout.php\';');

//BAR RESTAURANT specified menu
if($conf->global->TAKEPOS_BAR_RESTAURANT){
	$menus[$r++]=array('title'=>$langs->trans("Floors"),
					'action'=>'Floors();');
	if ($conf->global->TAKEPOS_ORDER_PRINTERS){
		$menus[$r++]=array('title'=>$langs->trans("Order"),
		'action'=>'TakeposPrintingOrder();');
	}
	//add temp ticket button
	if ($conf->global->TAKEPOS_ORDER_PRINTERS){
		$menus[$r++]=array('title'=>$langs->trans("Temporary ticket"),
		'action'=>'TakeposPrintingTemp();');
	}
}

if ($conf->global->TAKEPOSCONNECTOR){
	$menus[$r++]=array('title'=>$langs->trans("DOL_OPEN_DRAWER"),
					'action'=>'OpenDrawer();');
}
?>
<div style="position:absolute; top:1%; left:65.5%; height:37%; width:32.5%;">
<?php
foreach($menus as $menu) {
    echo '<button type="button" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
}
?>
</div>

<div style="position:absolute; top:39%; left:0.3%; height:59%; width:32%;">
	<?php
	$count=0;
	while ($count<16)
	{
	?>
	<div class='wrapper' <?php if ($count==14) echo 'onclick="MoreCategories(\'less\');"'; else if ($count==15) echo 'onclick="MoreCategories(\'more\');"'; else echo 'onclick="LoadProducts('.$count.');"';?> id='catdiv<?php echo $count;?>'>
		<img class='imgwrapper' <?php if ($count==14) echo 'src="img/arrow-prev-top.png"'; if ($count==15) echo 'src="img/arrow-next-top.png"';?> width="98%" id='catimg<?php echo $count;?>'/>
		<div class='description'>
			<div class='description_content' id='catdesc<?php echo $count;?>'></div>
		</div>
	</div>
	<?php
    $count++;
	}
	?>
</div>

<div style="position:absolute; top:39%; left:32%; height:58%; width:72%;">
<?php
$count=0;
while ($count<32)
	{
	?>
	<div class='wrapper2' id='prodiv<?php echo $count;?>' <?php if ($count==30) {?> onclick="MoreProducts('less');" <?php } if ($count==31) {?> onclick="MoreProducts('more');" <?php } else echo 'onclick="ClickProduct('.$count.');"';?>>
		<img class='imgwrapper' <?php if ($count==30) echo 'src="img/arrow-prev-top.png"'; if ($count==31) echo 'src="img/arrow-next-top.png"';?> width="95%" id='proimg<?php echo $count;?>'/>
		<div class='description'>
			<div class='description_content' id='prodesc<?php echo $count;?>'></div>
		</div>
	</div>
	<?php
	$count++;
	}
?>
</div>

</body>
<?php

llxFooter();

$db->close();
