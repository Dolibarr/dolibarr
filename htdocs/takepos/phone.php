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
 *	\file       htdocs/takepos/phone.php
 *	\ingroup    takepos
 *	\brief      TakePOS Phone screen
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Ba or Restaurant
$action = GETPOST('action', 'alpha');
$setterminal = GETPOST('setterminal', 'int');

if ($setterminal > 0)
{
	$_SESSION["takeposterminal"] = $setterminal;
}

$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk", "receiptprinter"));

/*
 * View
 */

// Title
$title = 'TakePOS - Dolibarr '.DOL_VERSION;
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = 'TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

?>
<link rel="stylesheet" href="css/phone.css">
<script language="javascript">
<?php
$categorie = new Categorie($db);
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
    $("#phonediv1").load("invoice.php?mobilepage=places", function() {
    });
    $("#phonediv2").load("invoice.php?mobilepage=invoice&place="+place, function() {
    });
});

function LoadPlace(placeid){
    place=placeid;
    $("#phonediv2").load("invoice.php?mobilepage=invoice&place="+place, function() {
    });
    LoadCats();
}

function AddProduct(placeid, productid){
    place=placeid;
    $("#phonediv2").load("invoice.php?mobilepage=invoice&action=addline&place="+place+"&idproduct="+productid, function() {
    });
}

function LoadCats(){
    $("#phonediv1").load("invoice.php?mobilepage=cats&place="+place, function() {
    });
}

function LoadProducts(idcat){
    $("#phonediv1").load("invoice.php?mobilepage=products&catid="+idcat+"&place="+place, function() {
    });
}

function LoadPlacesList(){
    $("#phonediv1").load("invoice.php?mobilepage=places", function() {
    });
}

function TakeposPrintingOrder(){
    console.log("TakeposPrintingOrder");
    $("#phonediv2").load("invoice.php?action=order&place="+place, function() {
    });
}

function Exit(){
    window.location.href='../user/logout.php';
}

</script>

<body style="overflow: hidden; background-color:#D1D1D1;">
<?php
if ($conf->global->TAKEPOS_NUM_TERMINALS != "1" && $_SESSION["takeposterminal"] == "") print '<div id="dialog-info" title="TakePOS">'.$langs->trans('TerminalSelect').'</div>';
?>
<div class="container">
	<div class="phonebuttonsrow">
		<button type="button" class="phonebutton" onclick="LoadPlacesList();"><?php echo strtoupper(substr($langs->trans('Floors'), 0, 3)); ?></button>
		<button type="button" class="phonebutton" onclick="LoadCats();"><?php echo strtoupper(substr($langs->trans('Categories'), 0, 3)); ?></button>
		<button type="button" class="phonebutton" onclick="TakeposPrintingOrder();"><?php echo strtoupper(substr($langs->trans('Order'), 0, 3)); ?></button>
		<button type="button" class="phonebutton" onclick="Exit();"><?php echo strtoupper(substr($langs->trans('Logout'), 0, 3)); ?></button>
	</div>
	<div class="row1">
		<div id="phonediv1" class="phonediv1"></div>
	</div>
	<div class="row2">
		<div id="phonediv2" class="phonediv2"></div>
	</div>
</div>
</body>
<?php

llxFooter();

$db->close();
