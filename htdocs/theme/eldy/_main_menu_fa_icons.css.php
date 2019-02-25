<?php if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */

.mainmenu::before{  
    /* font part */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    font-style: normal;
    font-variant: normal;
    text-rendering: auto;
    line-height: 26px;
	font-size: <?php echo $topMenuFontSize; ?>;
    -webkit-font-smoothing: antialiased;
    text-align:center;
	text-decoration:none;
	color: #<?php echo $colortextbackhmenu; ?>;
}


div.mainmenu.menu {
	background-image: none;
}

div.mainmenu.menu::before {	
content: "\f0c9";
}


.mainmenu.home::before{
	content: "\f015";
} 

.mainmenu.billing::before {
    content: "\f0d6";
}

.mainmenu.accountancy::before {
    content: "\f0d6";
}

.mainmenu.agenda::before {
    content: "\f073";
}

.mainmenu.bank::before {
    content: "\f19c";
}

<?php if($conf->global->MAIN_FEATURES_LEVEL == 2){ ?>
/* TESTING USAGE OF SVG WITHOUT FONT */
.mainmenu.cashdesk{
    line-height: 26px;
}

.mainmenu.cashdesk .tmenuimage{

    line-height: 26px;
    display: inline-block;
    vertical-align: middle;
    height: <?php echo $topMenuFontSize; ?>;
	background-color: #<?php echo $colortextbackhmenu; ?>;
    width: 100%;
	
    -webkit-mask: url(./img/fontawesome/cash-register-solid.svg) no-repeat 50% 50%; /* for old webkit browser */
    mask: url(./img/fontawesome/cash-register-solid.svg) no-repeat 50% 50%;
    
}

<?php }else{ ?>

.mainmenu.cashdesk::before {
    content: "\f788";
}

<?php } ?>



.mainmenu.takepos::before {
    content: "\f217";
}

.mainmenu.companies::before {
    content: "\f1ad";
}

.mainmenu.commercial::before {
    content: "\f508";
}

.mainmenu.ecm::before {
    content: "\f07c";
}

.mainmenu.externalsite::before {
    content: "\f360";
}

.mainmenu.ftp::before {
    content: "\f362";
}

.mainmenu.hrm::before {
    content: "\f5ca";
}

.mainmenu.members::before {
    content: "\f0c0";
}

.mainmenu.products::before {
    content: "\f468";
}

.mainmenu.project::before {
    content: "\f0e8";
}

.mainmenu.ticket::before {
    content: "\f3ff";
}

.mainmenu.tools::before {
    content: "\f0ad";
}

.mainmenu.website::before {
    content: "\f542";
}