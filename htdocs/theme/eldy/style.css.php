<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C)      2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C)	  2011 Philippe Grand       <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *		\file       htdocs/theme/eldy/style.css.php
 *		\brief      File for CSS style sheet Eldy
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

require_once("../../main.inc.php");

// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));	// If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');
$fontsize=empty($conf->browser->phone)?'12':'14';
$fontsizesmaller=empty($conf->browser->phone)?'11':'14';

$path='';    // This value may be used in future for external module to overwrite theme

// Define image path files
$fontlist='arial,tahoma,verdana,helvetica';    //$fontlist='Verdana,Helvetica,Arial,sans-serif';
$img_head=dol_buildpath($path.'/theme/eldy/img/headbg2.jpg',1);
$img_button=dol_buildpath($path.'/theme/eldy/img/button_bg.png',1);

// Reference colors
$colred=235;$colgreen=235;$colblue=235;         // 235 is standard. Must be higher than 50

// Generic colors
$isred=max(0,(2*$colred-$colgreen-$colblue)/2);        // 0 - 255
$isgreen=max(0,(2*$colgreen-$colred-$colblue)/2);      // 0 - 255
$isblue=max(0,(2*$colblue-$colred-$colgreen)/2);       // 0 - 255
$colorback1=($colred-3).','.($colgreen-3).','.($colblue-3);         // topmenu
$colorback2=($colred+5).','.($colgreen+5).','.($colblue+5);
$colorbacktab1=($colred+15).','.($colgreen+16).','.($colblue+17);      // vmenu
$colorbacktab2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktitle1=($colred-5).','.($colgreen-5).','.($colblue-5);    // title of array
$colorbacktitle2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktabcard1=($colred+15).','.($colgreen+16).','.($colblue+17);  // card
$colorbacktabcard2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktabactive=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacklineimpair1=(244+round($isred/3)).','.(244+round($isgreen/3)).','.(244+round($isblue/3));    // line impair
$colorbacklineimpair2=(250+round($isred/3)).','.(250+round($isgreen/3)).','.(250+round($isblue/3));    // line impair
$colorbacklinepair1='255,255,255';    // line pair
$colorbacklinepair2='255,255,255';    // line pair
$colorbackbody='#ffffff url('.$img_head.') 0 0 no-repeat;';

// Eldy colors
$useeldyskin=1;
if ($useeldyskin)
{
	$conf->global->THEME_ELDY_TOPMENU_BACK1='180,196,216';    // topmenu
    $conf->global->THEME_ELDY_TOPMENU_BACK2='190,206,226';
    $conf->global->THEME_ELDY_VERMENU_BACKTAB1='246,248,250'; // vmenu
    $conf->global->THEME_ELDY_VERMENU_BACKTAB2='220,225,230';
    $conf->global->THEME_ELDY_BACKTITLE1='140,160,185';       // title of arrays
    $conf->global->THEME_ELDY_BACKTITLE2='210,220,235';
    $conf->global->THEME_ELDY_BACKTABCARD1='242,244,248';     // card
    $conf->global->THEME_ELDY_BACKTABCARD2='220,225,230';
    $conf->global->THEME_ELDY_BACKTABACTIVE='220,225,230';
    $conf->global->THEME_ELDY_BACKBODY='#ffffff url('.$img_head.') 0 0 no-repeat;';
}

if (! empty($conf->global->THEME_ELDY_TOPMENU_BACK1))    $colorback1=$conf->global->THEME_ELDY_TOPMENU_BACK1;
if (! empty($conf->global->THEME_ELDY_TOPMENU_BACK2))    $colorback2=$conf->global->THEME_ELDY_TOPMENU_BACK2;
if (! empty($conf->global->THEME_ELDY_VERMENU_BACKTAB1)) $colorbacktab1=$conf->global->THEME_ELDY_VERMENU_BACKTAB1;
if (! empty($conf->global->THEME_ELDY_VERMENU_BACKTAB2)) $colorbacktab2=$conf->global->THEME_ELDY_VERMENU_BACKTAB2;
if (! empty($conf->global->THEME_ELDY_BACKTITLE1))       $colorbacktitle1=$conf->global->THEME_ELDY_BACKTITLE1;
if (! empty($conf->global->THEME_ELDY_BACKTITLE2))       $colorbacktitle2=$conf->global->THEME_ELDY_BACKTITLE2;
if (! empty($conf->global->THEME_ELDY_BACKTABCARD1))     $colorbacktabcard1=$conf->global->THEME_ELDY_BACKTABCARD1;
if (! empty($conf->global->THEME_ELDY_BACKTABCARD2))     $colorbacktabcard2=$conf->global->THEME_ELDY_BACKTABCARD2;
if (! empty($conf->global->THEME_ELDY_BACKTABACTIVE))    $colorbacktabactive=$conf->global->THEME_ELDY_BACKTABACTIVE;
if (! empty($conf->global->THEME_ELDY_LINEIMPAIR1))      $colorbacklineimpair1=$conf->global->THEME_ELDY_LINEIMPAIR1;
if (! empty($conf->global->THEME_ELDY_LINEIMPAIR2))      $colorbacklineimpair2=$conf->global->THEME_ELDY_LINEIMPAIR2;
if (! empty($conf->global->THEME_ELDY_LINEPAIR1))        $colorbacklinepair1=$conf->global->THEME_ELDY_LINEPAIR1;
if (! empty($conf->global->THEME_ELDY_LINEPAIR2))        $colorbacklinepair2=$conf->global->THEME_ELDY_LINEPAIR2;
if (! empty($conf->global->THEME_ELDY_BACKBODY))         $colorbackbody=$conf->global->THEME_ELDY_BACKBODY;

print '/*'."\n";
print 'isred='.$isred.' isgreen='.$isgreen.' isblue='.$isblue."\n";
print 'colorbacklineimpair1='.$colorbacklineimpair1."\n";
print 'colorbacklineimpair2='.$colorbacklineimpair2."\n";
print '*/'."\n";

?>

/* ============================================================================== */
/* Styles par defaut                                                              */
/* ============================================================================== */

body {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background: <?php print $colorbackbody; ?>;
<?php } ?>
	color: #101010;
	font-size: <?php print $fontsize ?>px;
    font-family: <?php print $fontlist ?>;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
    <?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
}

a:link, a:visited, a:hover, a:active { font-family: <?php print $fontlist ?>; font-weight: bold; color: #000000; text-decoration: none; }

input:focus, textarea:focus, button:focus, select:focus {
    box-shadow: 0 0 4px #8091BF;
}
input, input.flat, textarea, textarea.flat, form.flat select, select.flat {
    font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
	background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 1px 1px 1px 1px;
    margin: 0px 0px 0px 0px;
}
select.flat, form.flat select {
	font-weight: normal;
}
input:disabled {
	background:#ddd;
}
input:-webkit-autofill { background-color: #FCFEFA !important; background-image:none !important; }
textarea:disabled {
	background:#ddd;
}
.button {
    font-family: <?php print $fontlist ?>;
	background-image: url(<?php echo $img_button ?>);
	background-position: bottom;
    border: 1px solid #ACBCBB;
	padding: 0.1em 0.7em;
	margin: 0em 0.5em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}
.button:focus  {
    font-family: <?php print $fontlist ?>;
	color: #222244;
	background-image: url(<?php echo $img_button ?>);
	background-position: bottom;
    border: 1px solid #ACBCBB;
}
.button:hover   {
	background: #dee7ec;
}
.button:disabled {
	background: #ddd;
}
.buttonajax {
    font-family: <?php print $fontlist ?>;
	border: 0px;
	background-image: url(<?php echo $img_button ?>);
	background-position: bottom;
	padding: 0.1em 0.7em;
	margin: 0em 0.5em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}
form {
    padding: 0em 0em 0em 0em;
    margin: 0em 0em 0em 0em;
}
div.float
{
    float:<?php print $left; ?>;
}
th .button {
    -moz-box-shadow: none !important;
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
	-moz-border-radius:0px !important;
	-webkit-border-radius:0px !important;
	border-radius:0px !important;
}

/* ============================================================================== */
/* Styles to hide objects                                                         */
/* ============================================================================== */

.hideobject { display: none; }
<?php if (! empty($conf->browser->phone)) { ?>
.hideonsmartphone { display: none; }
<?php } ?>
.linkobject { cursor: pointer; }


/* ============================================================================== */
/* Styles for dragging lines                                                      */
/* ============================================================================== */

.dragClass {
	color: #002255;
}
td.showDragHandle {
	cursor: move;
}
.tdlineupdown {
	white-space: nowrap;
}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

div.fiche {
	margin-<?php print $left; ?>: <?php print (empty($conf->browser->phone) || empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))?'20':'24'; ?>px;
	margin-<?php print $right; ?>: <?php print empty($conf->browser->phone)?'12':'6'; ?>px;
}

div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichethirdleft {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 35%;\n"; } ?>
	<?php if (! empty($conf->browser->phone)) { print "padding-bottom: 6px;\n"; } ?>
}
div.fichetwothirdright {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 65%;\n"; } ?>
	<?php if (! empty($conf->browser->phone)) { print "padding-bottom: 6px\n"; } ?>
}
div.fichehalfleft {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if (empty($conf->browser->phone))   { print "padding-left: 16px;\n"; } ?>
}


/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

<?php
if (! empty($conf->browser->phone))
{
	$minwidthtmenu=0;
	$heightmenu=19;
}
else
{
	$minwidthtmenu=64;
	$heightmenu=48;
}
?>

div.tmenu {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
<?php } else { ?>
    position: relative;
    display: block;
    white-space: nowrap;
    border-top: 0px solid #D3E5EC;
    border-<?php print $left; ?>: 0px;
    border-<?php print $right; ?>: 0px solid #555555;
    border-bottom: 1px solid #FFFFFF;
    padding: 0px 0px 0px 0px;	/* t r b l */
    margin: 0px 0px 7px 0px;	/* t r b l */
    font-weight: normal;
    height: <?php print $heightmenu; ?>px;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 75%, rgb(<?php echo $colorback2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 75%, rgb(<?php echo $colorback2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 75%, rgb(<?php echo $colorback2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 40%, rgb(<?php echo $colorback2; ?>) 60%, rgb(<?php echo $colorback1; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 75%, rgb(<?php echo $colorback2; ?>) 100%);

    color: #000000;
    text-decoration: none;
<?php } ?>
}

table.tmenu {
    padding: 0px 0px 10px 0px;	/* t r b l */
    margin: 0px 0px 0px 0px;	/* t r b l */
	text-align: center;
}
td.tmenu {
	<?php print $minwidthtmenu?'width: '.$minwidthtmenu.'px;':''; ?>
	text-align: center;
	vertical-align: bottom;
	white-space: nowrap;
}

a.tmenudisabled:link, a.tmenudisabled:visited, a.tmenudisabled:hover, a.tmenudisabled:active {
	color: #757575;
    font-weight: normal;
	padding: 0px 5px 0px 5px;
	margin: 0px 1px 2px 1px;
	cursor: not-allowed;
    font-weight: normal;
	white-space: nowrap;
}

a.tmenu:link, a.tmenu:visited, a.tmenu:hover, a.tmenu:active {
    font-weight: normal;
	color: #234046;
	padding: 0px 5px 0px 5px;
	margin: 0px 1px 2px 1px;
	white-space: nowrap;
	text-shadow: 1px 2px 3px #AFAFAF;
}
a.tmenu:hover, a.tmenu:active {
	margin: 0px 0px 0px 0px;
	border-<?php print $right; ?>: 1px solid #555555;
	border-<?php print $left; ?>: 1px solid #D8D8D8;
	border-top: 1px solid #D8D8D8;
	border-bottom: 2px solid #dee7ec;
}
a.tmenu:hover, a.tmenu:active {
	background: #F4F4F4;
	text-shadow: 1px 2px 3px #AFAFAF;
}

a.tmenusel:link, a.tmenusel:visited, a.tmenusel:hover, a.tmenusel:active {
    font-weight: normal;
	color: #234046;
	padding: 0px 5px 0px 5px;
	margin: 0px 0px 0px 0px;
	background: #F4F4F4;
	border-<?php print $right; ?>: 1px solid #555555;
	border-top: 1px solid #D8D8D8;
	border-<?php print $left; ?>: 1px solid #D8D8D8;
	border-bottom: 2px solid #F4F4F4;
	white-space: nowrap;
	text-shadow: 1px 2px 3px #AFAFAF;
}


* html li.tmenu a {
	min-width:60px;
}

ul.tmenu {	/* t r b l */
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
	list-style: none;
}
li.tmenu {
	<?php print $minwidthtmenu?'min-width: '.$minwidthtmenu.'px;':''; ?>
	text-align: center;
	vertical-align: bottom;
	float: <?php print $left; ?>;
    height: <?php print $heightmenu; ?>px;
	position:relative;
	display: block;
	padding: 2px 5px 0px 5px;
    margin: 0px 0px 0px 0px;
	font-weight: normal;
}


div.mainmenu {
	position : relative;
	color: white;
	background-repeat:no-repeat;
	background-position:center top;
	height: <?php echo ($heightmenu-19); ?>px;
	margin-left: 0px;
}

<?php if (empty($conf->browser->phone)) { ?>

div.mainmenu.home{
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/home.png',1) ?>);
}

div.mainmenu.accountancy {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/money.png',1) ?>);
}

div.mainmenu.agenda {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/agenda.png',1) ?>);
}

div.mainmenu.bank {
    background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/bank.png',1) ?>);
}

div.mainmenu.bookmark {
}

div.mainmenu.cashdesk {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/pointofsale.png',1) ?>);
}

div.mainmenu.click2dial {
}

div.mainmenu.companies {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/members.png',1) ?>);
}

div.mainmenu.contacts {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/stethoscope.png',1) ?>);
}

div.mainmenu.commercial {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/commercial.png',1) ?>);
}

div.mainmenu.ecm {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/ecm.png',1) ?>);
}

div.mainmenu.externalsite {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/externalsite.png',1) ?>);
}

div.mainmenu.ftp {
    background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/tools.png',1) ?>);
}

div.mainmenu.gravatar {
}

div.mainmenu.geopipmaxmind {
}

div.mainmenu.members {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/members.png',1) ?>);
}

div.mainmenu.paypal {
}

div.mainmenu.products {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/products.png',1) ?>);
	margin-left: 10px;
}

div.mainmenu.project {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/project.png',1) ?>);
}

div.mainmenu.tools {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/tools.png',1) ?>);
}

div.mainmenu.shop {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/shop.png',1) ?>);
}

div.mainmenu.webservices {
}

div.mainmenu.google {
	background-image: url(<?php echo dol_buildpath($path.'/theme/eldy/img/menus/globe.png',1) ?>);
}


<?php
// Add here more div for other menu entries. moduletomainmenu=array('module name'=>'name of class for div')

$moduletomainmenu=array('user'=>'','syslog'=>'','societe'=>'companies','projet'=>'project','propale'=>'commercial','commande'=>'commercial',
	'produit'=>'products','service'=>'products','stock'=>'products',
	'don'=>'accountancy','tax'=>'accountancy','banque'=>'accountancy','facture'=>'accountancy','compta'=>'accountancy','accounting'=>'accountancy','adherent'=>'members','import'=>'tools','export'=>'tools','mailing'=>'tools',
	'contrat'=>'commercial','ficheinter'=>'commercial','deplacement'=>'commercial',
	'fournisseur'=>'companies',
	'barcode'=>'','fckeditor'=>'','categorie'=>'',
);
$mainmenuused='home';
foreach($conf->modules as $key => $val)
{
	$mainmenuused.=','.(isset($moduletomainmenu[$val])?$moduletomainmenu[$val]:$val);
}
//var_dump($mainmenuused);
$mainmenuusedarray=array_unique(explode(',',$mainmenuused));

$generic=1;
$divalreadydefined=array('home','companies','products','commercial','accountancy','project','tools','members','shop','agenda','bookmark','cashdesk','geoipmaxmind','gravatar','clicktodial','paypal','webservices');
foreach($mainmenuusedarray as $key => $val)
{
	if (empty($val) || in_array($val,$divalreadydefined)) continue;
	//print "XXX".$val;

	// Search img file in module dir
	$found=0; $url='';
	foreach($conf->file->dol_document_root as $dirroot)
	{
		if (file_exists($dirroot."/".$val."/img/".$val.".png"))
		{
			$url=dol_buildpath($path.'/'.$val.'/img/'.$val.'.png', 1);
			$found=1;
			break;
		}
	}
	// Img file not found
	if (! $found && $generic <= 4)
	{
		$url=dol_buildpath($path.'/theme/eldy/img/menus/generic'.$generic.".png",1);
		$found=1;
		$generic++;
	}
	if ($found)
	{
		print "/* A mainmenu entry but img file ".$val.".png not found, so we use a generic one */\n";
		print "div.mainmenu.".$val." {\n";
		print "	background-image: url(".$url.");\n";
		print "	height:28px;\n";
		print "}\n";
	}
}
// End of part to add more div class css
?>

<?php
}	// End test if not phone
?>

.tmenuimage {
    padding:0 0 0 0 !important;
    margin:0 0px 0 0 !important;
}



/* Login */

table.login {
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}

div.login_block {
	position: absolute;
	<?php print $right; ?>: 5px;
	top: 3px;
	font-weight: bold;
	<?php if (GETPOST("optioncss") == 'print') { ?>
	display: none;
	<?php } ?>
}

div.login_block table {
	display: inline;
}

div.login {
	white-space:nowrap;
    padding: <?php echo ($conf->browser->phone?'0':'8')?>px 0px 0px 0px;
    margin: 0px 0px 0px 8px;
	font-weight: bold;
}
div.login a {
	color: #234046;
}
div.login a:hover {
	color: black;
	text-decoration:underline;
}

img.login, img.printer, img.entity {
	padding: <?php echo ($conf->browser->phone?'0':'8')?>px 0px 0px 0px;
	margin: 0px 0px 0px 8px;
	text-decoration: none;
	color: white;
	font-weight: bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

td.vmenu {
    margin-<?php print $right; ?>: 2px;
    padding: 0px;
    padding-bottom: 0px;
    padding-top: 1px;
    width: 174px;
}

.vmenu {
	margin-left: 4px;
<?php if (GETPOST("optioncss") == 'print') { ?>
    display: none;
<?php } ?>
}

a.vmenu:link, a.vmenu:visited, a.vmenu:hover, a.vmenu:active { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; }
font.vmenudisabled  { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; color: #93a5aa; }

a.vsmenu:link, a.vsmenu:visited, a.vsmenu:hover, a.vsmenu:active { font-size:<?php print ($fontsize-1) ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
font.vsmenudisabled { font-size:<?php print ($fontsize-1) ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #93a5aa; margin: 1px 1px 1px 6px; }

a.help:link, a.help:visited, a.help:hover, a.help:active { font-size:<?php print $fontsizesmaller ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #666666; }


div.blockvmenupair, div.blockvmenuimpair
{
    width:174px;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding-left: 5px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 8px 2px;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);

    border-left: 1px solid #CCCCCC;
    border-right: 1px solid #BBBBBB;
    border-bottom: 1px solid #BBBBBB;
	border-radius: 5px;
	-moz-border-radius: 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;

}

div.blockvmenusearch
{
    width:174px;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding-left: 5px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 6px 0px 8px 2px;
	background: #E3E6E8;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);

    border-left: 1px solid #CCCCCC;
    border-right: 1px solid #BBBBBB;
    border-bottom: 1px solid #BBBBBB;
	border-radius: 5px;
	-moz-border-radius: 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}

div.blockvmenubookmarks
{
    width:174px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
    font-family: <?php print $fontlist ?>;
    color: #000000;
    text-align: <?php print $left; ?>;
    text-decoration: none;
    padding-left: 5px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 6px 0px 8px 2px;
    background: #E3E6E8;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 85%, rgb(<?php echo $colorbacktab2; ?>) 100%);

    border-left: 1px solid #CCCCCC;
    border-right: 1px solid #BBBBBB;
    border-bottom: 1px solid #BBBBBB;
	border-radius: 5px;
	-moz-border-radius: 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}

div.blockvmenuhelp
{
<?php if (empty($conf->browser->phone)) { ?>
    width:174px;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: center;
	text-decoration: none;
    padding-left: 0px;
    padding-right: 3px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 4px 0px 0px 0px;
<?php } else { ?>
    display: none;
<?php } ?>
}


td.barre {
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #b3c5cc;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
}

td.barre_select {
	background: #b3c5cc;
	color: #000000;
}

td.photo {
	background: #F4F4F4;
	color: #000000;
    border: 1px solid #b3c5cc;
}

/* ============================================================================== */
/* Panes for Main                                                   */
/* ============================================================================== */

/*
 *  PANES and CONTENT-DIVs
 */

#mainContent, #leftContent .ui-layout-pane {
    padding:    0px;
    overflow:	auto;
}

#mainContent, #leftContent .ui-layout-center {
	padding:    0px;
	position:   relative; /* contain floated or positioned elements */
    overflow:   auto;  /* add scrolling to content-div */
}


/* ============================================================================== */
/* Toolbar for ECM or Filemanager                                                 */
/* ============================================================================== */

.toolbar {
/*    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$conf->theme.'/img/tmenu2.jpg',1) ?>) !important;
    background-repeat: repeat-x !important;
*/
/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 15%, rgb(<?php echo $colorback2; ?>) 100%) !important;
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 15%, rgb(<?php echo $colorback2; ?>) 100%) !important;
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 15%, rgb(<?php echo $colorback2; ?>) 100%) !important;
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 15%, rgb(<?php echo $colorback2; ?>) 100%) !important;
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorback1; ?>) 15%, rgb(<?php echo $colorback2; ?>) 100%) !important;

    border: 1px solid #BBB !important;
}

.toolbarbutton {
    margin-top: 2px;
    margin-left: 4px;
/*    border: solid 1px #AAAAAA;
    width: 34px;*/
    height: 34px;
/*    background: #FFFFFF;*/
}


/* ============================================================================== */
/* Panes for ECM or Filemanager                                                   */
/* ============================================================================== */

#containerlayout .layout-with-no-border {
    border: 0 !important;
    border-width: 0 !important;
}

#containerlayout .layout-padding {
    padding: 2px !important;
}

/*
 *  PANES and CONTENT-DIVs
 */
#containerlayout .ui-layout-pane { /* all 'panes' */
    background: #FFF;
    border:     1px solid #BBB;
    /* DO NOT add scrolling (or padding) to 'panes' that have a content-div,
       otherwise you may get double-scrollbars - on the pane AND on the content-div
    */
    padding:    0px;
    overflow:   auto;
}
/* (scrolling) content-div inside pane allows for fixed header(s) and/or footer(s) */
#containerlayout .ui-layout-content {
	padding:    10px;
	position:   relative; /* contain floated or positioned elements */
	overflow:   auto; /* add scrolling to content-div */
}

/*
 *  RESIZER-BARS
 */
.ui-layout-resizer  { /* all 'resizer-bars' */
	width: <?php echo (empty($conf->browser->phone)?'8':'24'); ?>px !important;
}
.ui-layout-resizer-hover    {   /* affects both open and closed states */
}
/* NOTE: It looks best when 'hover' and 'dragging' are set to the same color,
    otherwise color shifts while dragging when bar can't keep up with mouse */
/*.ui-layout-resizer-open-hover ,*/ /* hover-color to 'resize' */
.ui-layout-resizer-dragging {   /* resizer beging 'dragging' */
    background: #DDD;
    width: <?php echo (empty($conf->browser->phone)?'8':'24'); ?>px;
}
.ui-layout-resizer-dragging {   /* CLONED resizer being dragged */
    border-left:  1px solid #BBB;
    border-right: 1px solid #BBB;
}
/* NOTE: Add a 'dragging-limit' color to provide visual feedback when resizer hits min/max size limits */
.ui-layout-resizer-dragging-limit { /* CLONED resizer at min or max size-limit */
    background: #E1A4A4; /* red */
}
.ui-layout-resizer-closed {
    background-color: #DDDDDD;
}
.ui-layout-resizer-closed:hover {
    background-color: #EEDDDD;
}
.ui-layout-resizer-sliding {    /* resizer when pane is 'slid open' */
    opacity: .10; /* show only a slight shadow */
    filter:  alpha(opacity=10);
}
.ui-layout-resizer-sliding-hover {  /* sliding resizer - hover */
    opacity: 1.00; /* on-hover, show the resizer-bar normally */
    filter:  alpha(opacity=100);
}
/* sliding resizer - add 'outside-border' to resizer on-hover */
/* this sample illustrates how to target specific panes and states */
/*.ui-layout-resizer-north-sliding-hover  { border-bottom-width:  1px; }
.ui-layout-resizer-south-sliding-hover  { border-top-width:     1px; }
.ui-layout-resizer-west-sliding-hover   { border-right-width:   1px; }
.ui-layout-resizer-east-sliding-hover   { border-left-width:    1px; }
*/

/*
 *  TOGGLER-BUTTONS
 */
.ui-layout-toggler {
    <?php if (empty($conf->browser->phone)) { ?>
    border-top: 1px solid #AAA; /* match pane-border */
    border-right: 1px solid #AAA; /* match pane-border */
    border-bottom: 1px solid #AAA; /* match pane-border */
    background-color: #DDD;
    top: 5px !important;
	<?php } else { ?>
	diplay: none;
	<?php } ?>
}
.ui-layout-toggler-open {
	height: 54px !important;
	width: <?php echo (empty($conf->browser->phone)?'7':'22'); ?>px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ui-layout-toggler-closed {
	height: <?php echo (empty($conf->browser->phone)?'54':'2'); ?>px !important;
	width: <?php echo (empty($conf->browser->phone)?'7':'22'); ?>px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ui-layout-toggler .content {	/* style the text we put INSIDE the togglers */
    color:          #666;
    font-size:      12px;
    font-weight:    bold;
    width:          100%;
    padding-bottom: 0.35ex; /* to 'vertically center' text inside text-span */
}

/* hide the toggler-button when the pane is 'slid open' */
.ui-layout-resizer-sliding  ui-layout-toggler {
    display: none;
}

.ui-layout-north {
	height: <?php print (empty($conf->browser->phone)?'54':'21'); ?>px !important;
}

/* ECM */

#containerlayout .ecm-layout-pane { /* all 'panes' */
    background: #FFF;
    border:     1px solid #BBB;
    /* DO NOT add scrolling (or padding) to 'panes' that have a content-div,
       otherwise you may get double-scrollbars - on the pane AND on the content-div
    */
    padding:    0px;
    overflow:   auto;
}
/* (scrolling) content-div inside pane allows for fixed header(s) and/or footer(s) */
#containerlayout .ecm-layout-content {
	padding:    10px;
	position:   relative; /* contain floated or positioned elements */
	overflow:   auto; /* add scrolling to content-div */
}

.ecm-layout-toggler {
    border-top: 1px solid #AAA; /* match pane-border */
    border-right: 1px solid #AAA; /* match pane-border */
    border-bottom: 1px solid #AAA; /* match pane-border */
    background-color: #CCC;
    }
.ecm-layout-toggler-open {
	height: 48px !important;
	width: 6px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ecm-layout-toggler-closed {
	height: 48px !important;
	width: 6px !important;
}

.ecm-layout-toggler .content {	/* style the text we put INSIDE the togglers */
    color:          #666;
    font-size:      12px;
    font-weight:    bold;
    width:          100%;
    padding-bottom: 0.35ex; /* to 'vertically center' text inside text-span */
}
#ecm-layout-west-resizer {
	width: 6px !important;
}

.ecm-layout-resizer  { /* all 'resizer-bars' */
    background:     #EEEEEE;
    border:         1px solid #BBB;
    border-width:   0;
    }
.ecm-layout-resizer-closed {
    background-color: #EEEEEE;
}

.ecm-in-layout-center {
    border-left: 1px !important;
    border-right: 0px !important;
    border-top: 0px !important;
}

.ecm-in-layout-south {
    border-left: 0px !important;
    border-right: 0px !important;
    border-bottom: 0px !important;
    padding: 4px 0 4px 4px !important;
}



/* ============================================================================== */
/* Onglets                                                                        */
/* ============================================================================== */

div.tabs {
    top: 20px;
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: <?php print $left; ?>;
}

div.tabBar {
    color: #234046;
    padding-top: 9px;
    padding-left: 8px;
    padding-right: 8px;
    padding-bottom: 8px;
    margin: 0px 0px 14px 0px;
    -moz-border-radius:6px;
    -webkit-border-radius: 6px;
	border-radius: 6px;
    border-right: 1px solid #CCCCCC;
    border-bottom: 1px solid #CCCCCC;
    border-left: 1px solid #D0D0D0;
    border-top: 1px solid #D8D8D8;
/*    background: #dee7ec url(<?php echo dol_buildpath($path.'/theme/eldy/img/tab_background.png',1) ?>) repeat-x; */

	/* DYN */
	background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktabcard1; ?>) 25%, rgb(<?php echo $colorbacktabcard2; ?>) 100%);
	background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktabcard1; ?>) 25%, rgb(<?php echo $colorbacktabcard2; ?>) 100%);
	background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktabcard1; ?>) 25%, rgb(<?php echo $colorbacktabcard2; ?>) 100%);
	background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktabcard1; ?>) 25%, rgb(<?php echo $colorbacktabcard2; ?>) 100%);
	background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktabcard1; ?>) 25%, rgb(<?php echo $colorbacktabcard2; ?>) 100%);

    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}

div.tabsAction {
    margin: 20px 0em 10px 0em;
    padding: 0em 0em;
    text-align: right;
}


a.tabTitle {
    background: #657090;
    color: white;
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
    padding: 0px 6px;
    margin: 0px 6px;
    text-decoration: none;
    white-space: nowrap;
}

a.tab:link {
    background: #dee7ec;
    color: #434956;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius:6px 6px 0px 0px;
	-webkit-border-radius:6px 6px 0px 0px;
	border-radius:6px 6px 0px 0px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);

}
a.tab:visited {
    background: #dee7ec;
    color: #434956;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius:6px 6px 0px 0px;
	-webkit-border-radius:6px 6px 0px 0px;
	border-radius:6px 6px 0px 0px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);

}
a.tab#active {
    background: white;
	font-family: <?php print $fontlist ?>;
    color: #434956;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    -moz-border-radius:6px 6px 0px 0px;
	-webkit-border-radius:6px 6px 0px 0px;
	border-radius:6px 6px 0px 0px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
/*    border-bottom: 1px solid white; */

/* DYN */
/*background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
*/
    border-bottom: 1px solid rgb(<?php echo $colorbacktabactive; ?>);
	background-color: rgb(<?php echo $colorbacktabactive; ?>);
}
a.tab:hover {
    background: white;
    color: #434956;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    -moz-border-radius:6px 6px 0px 0px;
	-webkit-border-radius:6px 6px 0px 0px;
	border-radius:6px 6px 0px 0px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktab1; ?>) 35%, rgb(<?php echo $colorbacktab2; ?>) 100%);
}

a.tabimage {
    color: #434956;
	font-family: <?php print $fontlist ?>;
    text-decoration: none;
    white-space: nowrap;
}

td.tab {
    background: #dee7ec;
}

span.tabspan {
    background: #dee7ec;
    color: #434956;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius:6px 6px 0px 0px;
	-webkit-border-radius:6px 6px 0px 0px;
	border-radius:6px 6px 0px 0px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

.butAction, .butAction:link, .butAction:visited, .butAction:hover, .butAction:active, .butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
	font-family: <?php print $fontlist ?>;
	font-weight: bold;
	background: white;
	border: 1px solid #8CACBB;
	color: #434956;
	text-decoration: none;
	white-space: nowrap;
	padding: 0.4em 0.7em;
	margin: 0em 0.7em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}

.butAction:hover   {
	background: #dee7ec;
}

.butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
	border: 1px solid #997777;
}

.butActionDelete:hover {
	background: #FFe7ec;
}

.butActionRefused {
	font-family: <?php print $fontlist ?> !important;
	font-weight: bold !important;
	background: white !important;
	border: 1px solid #AAAAAA !important;
	color: #AAAAAA !important;
	text-decoration: none !important;
	white-space: nowrap !important;
	cursor: not-allowed;
	padding: 0.4em 0.7em;
	margin: 0em 0.7em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}

span.butAction, span.butActionDelete {
	cursor: pointer;
}


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */


#undertopmenu {
/*	background-image: url("<?php echo dol_buildpath($path.'/theme/eldy/img/gradient.gif',1) ?>"); */
	background-repeat: repeat-x;
	margin-top: 8px;
}


.paddingrightonly {
	border-collapse: collapse;
	border: 0px;
	margin-left: 0px;
	spacing-left: 0px;
	padding-left: 0px;
	padding-right: 4px;
}
.nocellnopadd {
list-style-type:none;
margin: 0px;
padding: 0px;
}

.notopnoleft {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-<?php print $left; ?>: 0px;
padding-<?php print $right; ?>: 16px;
padding-bottom: 4px;
margin-right: 0px 0px;
}
.notopnoleftnoright {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-left: 0px;
padding-right: 0px;
padding-bottom: 4px;
margin: 0px 0px 0px 0px;
}


table.border {
border: 1px solid #9CACBB;
border-collapse: collapse;
}

table.border td {
padding: 1px 2px 1px 1px;
border: 1px solid #9CACBB;
border-collapse: collapse;
}

td.border {
border-top: 1px solid #000000;
border-right: 1px solid #000000;
border-bottom: 1px solid #000000;
border-left: 1px solid #000000;
}

/* Main boxes */

table.noborder, table.formdoc {
width: 100%;

border-collapse: separate !important;
border-spacing: 0px;

border-right-width: 0px;
border-right-color: #DDDDDD;
border-right-style: solid;

border-left-width: 1px;
border-left-color: #DDDDDD;
border-left-style: solid;

border-bottom-width: 1px;
border-bottom-color: #DDDDDD;
border-bottom-style: solid;

margin: 0px 0px 2px 0px;
/*padding: 1px 2px 1px 2px;*/

-moz-box-shadow: 4px 4px 4px #CCC;
-webkit-box-shadow: 4px 4px 4px #CCC;
box-shadow: 4px 4px 4px #CCC;

-moz-border-radius: 0.2em;
-webkit-border-radius: 0.2em;
border-radius: 0.2em;
}

table.noborder tr {
border-top-color: #FEFEFE;

border-right-width: 1px;
border-right-color: #BBBBBB;
border-right-style: solid;

border-left-width: 1px;
border-left-color: #BBBBBB;
border-left-style: solid;
height: 18px;
}

table.noborder th, table.noborder td {
padding: 1px 2px 1px 3px;			/* t r b l */
}

table.nobordernopadding {
border-collapse: collapse !important;
border: 0px;
}
table.nobordernopadding tr {
border: 0px !important;
padding: 0px 0px;
}
table.nobordernopadding td {
border: 0px !important;
padding: 0px 0px;
}

/* For lists */

table.liste {
    width: 100%;
    border-collapse: collapse;
    border-top-color: #FEFEFE;

    border-right-width: 1px;
    border-right-color: #BBBBBB;
    border-right-style: solid;

    border-left-width: 1px;
    border-left-color: #CCCCCC;
    border-left-style: solid;

    border-bottom-width: 1px;
    border-bottom-color: #BBBBBB;
    border-bottom-style: solid;

    margin-bottom: 2px;
    margin-top: 0px;

    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;
}
table.liste td {
	padding-right: 2px;
}

tr.liste_titre, tr.liste_titre_sel
{
    height: 20px !important;
    background-repeat: repeat-x;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);

    color: #234046;
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    border-bottom: 1px solid #FDFFFF;
    white-space: nowrap;
    text-align: <?php echo $left; ?>;
}
tr.liste_titre th, th.liste_titre, tr.liste_titre td, td.liste_titre
{
	/*border-top: 1px solid #DDDDDD;*/
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    border-bottom: 1px solid #FDFFFF;
    white-space: nowrap;
}
tr.liste_titre_sel th, th.liste_titre_sel, tr.liste_titre_sel td, td.liste_titre_sel
{
/*    color: #FFFFFF !important; */
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    border-bottom: 1px solid #FDFFFF;
    white-space: nowrap;
    text-decoration: underline;
}
input.liste_titre {
    /*background: #7699A9;*/
    /*background-repeat: repeat-x;*/
    background: transparent;
    border: 0px;
}

tr.liste_total {
	background: #F0F0F0;
}
tr.liste_total td {
    border-top: 1px solid #DDDDDD;
    color: #332266;
    font-weight: normal;
    white-space: nowrap;
}

.impair {
/* background: #d0d4d7; */
/* background: #f6f6f6; */

/* DYN */
background: linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);

font-family: <?php print $fontlist ?>;
border: 0px;
margin-bottom: 1px;
}
/*
.impair:hover {
background: #c0c4c7;
border: 0px;
}
*/

.pair	{
/* background: #e6ebed; */
/* background: #ffffff; */

/* DYN */
background: linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);

font-family: <?php print $fontlist ?>;
border: 0px;
margin-bottom: 1px;
}
/*
.pair:hover {
background: #c0c4c7;
border: 0px;
}
*/


/*
 *  Boxes
 */

.boxtable {
-moz-box-shadow: 4px 4px 4px #CCC;
-webkit-box-shadow: 4px 4px 4px #CCC;
box-shadow: 4px 4px 4px #CCC;
}


.box {
padding-right: 0px;
padding-left: 0px;
padding-bottom: 4px;
}

tr.box_titre {
height: 20px;
background: #7699A9;

/* DYN */
background-image: linear-gradient(bottom, rgb(<?php echo $colorbackttitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -o-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);
background-image: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacktitle1; ?>) 15%, rgb(<?php echo $colorbacktitle2; ?>) 100%);

background-repeat: repeat-x;
color: #334444;
font-family: <?php print $fontlist ?>, sans-serif;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}

tr.box_impair {
/* background: #e6ebed; */
/* background: #f6f6f6; */

/* DYN */
background: linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);

font-family: <?php print $fontlist ?>;
}

tr.box_pair {
/* background: #d0d4d7; */
/* background: #ffffff; */

/* DYN */
background: linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);

font-family: <?php print $fontlist ?>;
}

tr.fiche {
font-family: <?php print $fontlist ?>;
}




/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #887711; }
.error   { color: #550000; font-weight: bold; }

td.highlights { background: #f9c5c6; }

div.ok {
  color: #114466;
}

div.warning {
  color: #997711;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #e0e0d0;
  -moz-border-radius:6px;
  -webkit-border-radius: 6px;
  border-radius: 6px;
  background: #efefd4;
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #DC9CAB;
  -moz-border-radius:6px;
  -webkit-border-radius: 6px;
  border-radius: 6px;
  background: #EFCFCF;
}

/* Info admin */
div.info {
  color: #807050;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #DFDFA0;
  -moz-border-radius:6px;
  -webkit-border-radius: 6px;
  border-radius:6px;
  background: #EFEFDA;
}


/*
 *   Liens Payes/Non payes
 */

a.normal:link { font-weight: normal }
a.normal:visited { font-weight: normal }
a.normal:active { font-weight: normal }
a.normal:hover { font-weight: normal }

a.impayee:link { font-weight: bold; color: #550000; }
a.impayee:visited { font-weight: bold; color: #550000; }
a.impayee:active { font-weight: bold; color: #550000; }
a.impayee:hover { font-weight: bold; color: #550000; }



/*
 *  Other
 */

.fieldrequired { font-weight: bold; color: #000055; }

.photo {
border: 0px;
/* filter:alpha(opacity=55); */
/* opacity:.55; */
}

div.titre {
	font-family: <?php print $fontlist ?>;
	font-weight: bold;
	color: #336666;
	text-decoration: none;
}


/* ============================================================================== */
/* Formulaire confirmation (When Ajax JQuery is used)                             */
/* ============================================================================== */

.ui-dialog-titlebar {
}
.ui-dialog-content {
    font-size: <?php print $fontsize; ?>px !important;
}

/* ============================================================================== */
/* Formulaire confirmation (When HTML is used)                                    */
/* ============================================================================== */

table.valid {
    border-top: solid 1px #E6E6E6;
    border-<?php print $left; ?>: solid 1px #E6E6E6;
    border-<?php print $right; ?>: solid 1px #444444;
    border-bottom: solid 1px #555555;
	padding-top: 0px;
	padding-left: 0px;
	padding-right: 0px;
	padding-bottom: 0px;
	margin: 0px 0px;
    background: #D5BAA8;
}

.validtitre {
    background: #D5BAA8;
	font-weight: bold;
}


/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

#tooltip {
position: absolute;
width: <?php print dol_size(450,'width'); ?>px;
border-top: solid 1px #BBBBBB;
border-<?php print $left; ?>: solid 1px #BBBBBB;
border-<?php print $right; ?>: solid 1px #444444;
border-bottom: solid 1px #444444;
padding: 2px;
z-index: 3000;
background-color: #FFFFF0;
opacity: 1;
-moz-border-radius:6px;
-webkit-border-radius: 6px;
border-radius: 6px;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */

.ui-datepicker-trigger {
	vertical-align: middle;
	cursor: pointer;
}

.bodyline {
	-moz-border-radius: 8px;
	-webkit-border-radius: 8px;
	border-radius: 8px;
	border: 1px #E4ECEC outset;
	padding: 0px;
	margin-bottom: 5px;
}
table.dp {
    width: 180px;
    background-color: #FFFFFF;
    border-top: solid 2px #DDDDDD;
    border-<?php print $left; ?>: solid 2px #DDDDDD;
    border-<?php print $right; ?>: solid 1px #222222;
    border-bottom: solid 1px #222222;
    padding: 0px;
	border-spacing: 0px;
	border-collapse: collapse;
}
.dp td, .tpHour td, .tpMinute td{padding:2px; font-size:10px;}
/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color:#b3c5cc;
	color:white;
	font-size:11px;
	cursor:auto;
}
/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#617389;
	color:#FFFFFF;
	font-weight:bold;
	cursor:pointer;
}
.dpButtons:Active,.tpButtons:Active{border: 1px outset black;}
.dpDayNames td,.dpExplanation {background-color:#D9DBE1; font-weight:bold; text-align:center; font-size:11px;}
.dpExplanation{ font-weight:normal; font-size:11px;}
.dpWeek td{text-align:center}

.dpToday,.dpReg,.dpSelected{
	cursor:pointer;
}
.dpToday{font-weight:bold; color:black; background-color:#DDDDDD;}
.dpReg:Hover,.dpToday:Hover{background-color:black;color:white}

/* Jour courant */
.dpSelected{background-color:#0B63A2;color:white;font-weight:bold; }

.tpHour{border-top:1px solid #DDDDDD; border-right:1px solid #DDDDDD;}
.tpHour td {border-left:1px solid #DDDDDD; border-bottom:1px solid #DDDDDD; cursor:pointer;}
.tpHour td:Hover {background-color:black;color:white;}

.tpMinute {margin-top:5px;}
.tpMinute td:Hover {background-color:black; color:white; }
.tpMinute td {background-color:#D9DBE1; text-align:center; cursor:pointer;}

/* Bouton X fermer */
.dpInvisibleButtons
{
border-style:none;
background-color:transparent;
padding:0px;
font-size:9px;
border-width:0px;
color:#0B63A2;
vertical-align:middle;
cursor: pointer;
}


/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

div.visible {
    display: block;
}

div.hidden {
    display: none;
}

tr.visible {
    display: block;
}

td.hidden {
    display: none;
}


/* ============================================================================== */
/*  Module agenda                                                                 */
/* ============================================================================== */

.cal_other_month   { background: #DDDDDD; border: solid 1px #ACBCBB; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { background: #EEEEEE; border: solid 1px #ACBCBB; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border: solid 1px #ACBCBB; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #6C7C7B; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event    { border-collapse: collapse; margin-bottom: 1px; -webkit-border-radius: 6px; border-radius: 6px; }
table.cal_event td { border: 0px; padding-<?php print $left; ?>: 0px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; }



/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

#evolForm input.error {
                        font-weight: bold;
                        border: solid 1px #FF0000;
                        padding: 1px 1px 1px 1px;
                        margin: 1px 1px 1px 1px;
              }

#evolForm input.focuserr {
                        font-weight: bold;
                        background: #FAF8E8;
                        color: black;
                        border: solid 1px #FF0000;
                        padding: 1px 1px 1px 1px;
                        margin: 1px 1px 1px 1px;
              }


#evolForm input.focus {	/*** Mise en avant des champs en cours d'utilisation ***/
                        background: #FAF8E8;
                        color: black;
                        border: solid 1px #000000;
                        padding: 1px 1px 1px 1px;
                        margin: 1px 1px 1px 1px;
              }

#evolForm input.normal {	/*** Retour a l'etat normal apres l'utilisation ***/
                         background: white;
                         color: black;
                         border: solid 1px white;
                         padding: 1px 1px 1px 1px;
                         margin: 1px 1px 1px 1px;
               }



/* ============================================================================== */
/*  Ajax - Liste deroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-autocomplete-loading { background: white url(<?php echo dol_buildpath($path.'/theme/eldy/img/working.gif',1) ?>) right center no-repeat; }
.ui-autocomplete {
	       position:absolute;
	       width:auto;
	       font-size: 1.0em;
	       background-color:white;
	       border:1px solid #888;
	       margin:0px;
	       padding:0px;
	     }
.ui-autocomplete ul {
	       list-style-type:none;
	       margin:0px;
	       padding:0px;
	     }
.ui-autocomplete ul li.selected { background-color: #D3E5EC;}
.ui-autocomplete ul li {
	       list-style-type:none;
	       display:block;
	       margin:0;
	       padding:2px;
	       height:18px;
	       cursor:pointer;
	     }

/* ============================================================================== */
/*  jQuery - jeditable                                                            */
/* ============================================================================== */

.editkey_textarea, .editkey_ckeditor, .editkey_text, .editkey_numeric, .editkey_select {
	background: url(<?php echo dol_buildpath($path.'/theme/eldy/img/edit.png',1) ?>) right top no-repeat;
	cursor: pointer;
}

.editkey_datepicker {
	background: url(<?php echo dol_buildpath($path.'/theme/eldy/img/calendar.png',1) ?>) right center no-repeat;
	cursor: pointer;
}

.editval_textarea:hover, .editval_ckeditor:hover, .editval_text:hover, .editval_numeric:hover, .editval_select:hover, .editval_datepicker:hover {
	background: white;
	cursor: pointer;
}

.editval_hover {
	background: white;
}


/* ============================================================================== */
/* Admin Menu                                                                     */
/* ============================================================================== */

/* CSS for treeview */

/* Lien plier /deplier tout */
.arbre-switch {
    text-align: right;
    padding: 0 5px;
    margin: 0 0 -18px 0;
}

/* Arbre */
ul.arbre {
    padding: 5px 10px;
}
/* strong : A modifier en fonction de la balise choisie */
ul.arbre strong {
    font-weight: normal;
    padding: 0 0 0 20px;
    margin: 0 0 0 -7px;
    background-image: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/branch.gif',1) ?>);
    background-repeat: no-repeat;
    background-position: 1px 50%;
}
ul.arbre strong.arbre-plier {
    background-image: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/plus.gif',1) ?>);
    cursor: pointer;
}
ul.arbre strong.arbre-deplier {
    background-image: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/minus.gif',1) ?>);
    cursor: pointer;
}
ul.arbre ul {
    padding: 0;
    margin: 0;
}
ul.arbre li {
    padding: 0;
    margin: 0;
    list-style: none;
}
/* This is to create an indent */
ul.arbre li li {
    margin: 0 0 0 16px;
}
/* Classe pour masquer */
.hide {
    display: none;
}

img.menuNew
{
	display:block;
	border:0px;
}

img.menuEdit
{
	border: 0px;
	display: block;
}

img.menuDel
{
	display:none;
	border: 0px;
}

div.menuNew
{
	margin-top:-20px;
	margin-<?php print $left; ?>:270px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuEdit
{
	margin-top:-15px;
	margin-<?php print $left; ?>:250px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuDel
{
	margin-top:-20px;
	margin-<?php print $left; ?>:290px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuFleche
{
	margin-top:-16px;
	margin-<?php print $left; ?>:320px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}


/* ============================================================================== */
/*  Show Excel tabs                                                               */
/* ============================================================================== */

.table_data
{
	border-style:ridge;
	border:1px solid;
}
.tab_base
{
	background:#C5D0DD;
	font-weight:bold;
	border-style:ridge;
	border: 1px solid;
	cursor:pointer;
}
.table_sub_heading
{
	background:#CCCCCC;
	font-weight:bold;
	border-style:ridge;
	border: 1px solid;
}
.table_body
{
	background:#F0F0F0;
	font-weight:normal;
	font-family:sans-serif;
	border-style:ridge;
	border: 1px solid;
	border-spacing: 0px;
	border-collapse: collapse;
}
.tab_loaded
{
	background:#222222;
	color:white;
	font-weight:bold;
	border-style:groove;
	border: 1px solid;
	cursor:pointer;
}


/* ============================================================================== */
/*  CSS for color picker                                                          */
/* ============================================================================== */

A.color, A.color:active, A.color:visited {
 position : relative;
 display : block;
 text-decoration : none;
 width : 10px;
 height : 10px;
 line-height : 10px;
 margin : 0px;
 padding : 0px;
 border : 1px inset white;
}
A.color:hover {
 border : 1px outset white;
}
A.none, A.none:active, A.none:visited, A.none:hover {
 position : relative;
 display : block;
 text-decoration : none;
 width : 10px;
 height : 10px;
 line-height : 10px;
 margin : 0px;
 padding : 0px;
 cursor : default;
 border : 1px solid #b3c5cc;
}
.tblColor {
 display : none;
}
.tdColor {
 padding : 1px;
}
.tblContainer {
 background-color : #b3c5cc;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : #b3c5cc;
 border : 2px outset;
}
.tdContainer {
 padding : 5px;
}
.tdDisplay {
 width : 50%;
 height : 20px;
 line-height : 20px;
 border : 1px outset white;
}
.tdDisplayTxt {
 width : 50%;
 height : 24px;
 line-height : 12px;
 font-family : <?php print $fontlist ?>;
 font-size : 8pt;
 color : black;
 text-align : center;
}
.btnColor {
 width : 100%;
 font-family : <?php print $fontlist ?>;
 font-size : 10pt;
 padding : 0px;
 margin : 0px;
}
.btnPalette {
 width : 100%;
 font-family : <?php print $fontlist ?>;
 font-size : 8pt;
 padding : 0px;
 margin : 0px;
}


/* Style to overwrites JQuery styles */
.ui-menu .ui-menu-item a {
    text-decoration:none;
    display:block;
    padding:.2em .4em;
    line-height:1.5;
    zoom:1;
    font-weight: normal;
    font-family:<?php echo $fontlist; ?>;
    font-size:1em;
}
.ui-widget {
    font-family:<?php echo $fontlist; ?>;
    font-size:<?php echo $fontsize; ?>px;
}
.ui-button { margin-left: -1px; }
.ui-button-icon-only .ui-button-text { height: 8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding: 2px 0px 6px 0px; }
.ui-button-text
{
    line-height: 1em !important;
}
.ui-autocomplete-input { margin: 0; padding: 1px; }


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */

.cke_editor table, .cke_editor tr, .cke_editor td
{
    border: 0px solid #FF0000 !important;
}
span.cke_skin_kama { padding: 0 !important; }
.cke_wrapper { padding: 4px !important; }
a.cke_dialog_ui_button
{
    font-family: <?php print $fontlist ?> !important;
	background-image: url(<?php echo $img_button ?>) !important;
	background-position: bottom !important;
    border: 1px solid #ACBCBB !important;
	padding: 0.1em 0.7em !important;
	margin: 0em 0.5em !important;
    -moz-border-radius:0px 5px 0px 5px !important;
	-webkit-border-radius:0px 5px 0px 5px !important;
	border-radius:0px 5px 0px 5px !important;
    -moz-box-shadow: 4px 4px 4px #CCC !important;
    -webkit-box-shadow: 4px 4px 4px #CCC !important;
    box-shadow: 4px 4px 4px #CCC !important;
}
.cke_dialog_ui_hbox_last
{
	vertical-align: bottom ! important;
}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {
    height: 72px !important;
}

<?php
if (is_object($db)) $db->close();
?>
