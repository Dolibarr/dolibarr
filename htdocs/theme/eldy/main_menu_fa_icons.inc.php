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


div.mainmenu.home::before{
	content: "\f015";
}

div.mainmenu.billing::before {
    content: "\f3d1";
}

div.mainmenu.accountancy::before {
    content: "\f53d";
}

div.mainmenu.agenda::before {
    content: "\f073";
}

div.mainmenu.bank::before {
    content: "\f19c";
}

<?php if ($conf->global->MAIN_FEATURES_LEVEL == 2) { ?>
/* TESTING USAGE OF SVG WITHOUT FONT */
div.mainmenu.cashdesk {
    line-height: 26px;
}

div.mainmenu.cashdesk .tmenuimage {
    line-height: 26px;
    display: inline-block;
    vertical-align: middle;
    height: <?php echo $topMenuFontSize; ?>;
	background-color: #<?php echo $colortextbackhmenu; ?>;
    width: 100%;
    -webkit-mask: url(<?php echo DOL_URL_ROOT.'/theme/common/fontawesome-5/svgs/solid/cash-register.svg' ?>) no-repeat 50% 50%; /* for old webkit browser */
    mask: url(<?php echo DOL_URL_ROOT.'/theme/common/fontawesome-5/svgs/solid/cash-register.svg' ?>) no-repeat 50% 50%;
}

<?php } else { ?>

div.mainmenu.cashdesk::before {
    content: "\f788";
}

<?php } ?>


div.mainmenu.takepos::before {
    content: "\f788";
}

div.mainmenu.companies::before {
    content: "\f1ad";
}

div.mainmenu.commercial::before {
    content: "\f508";
}

div.mainmenu.ecm::before {
    content: "\f07c";
}

div.mainmenu.externalsite::before {
    content: "\f360";
}

div.mainmenu.ftp::before {
    content: "\f362";
}

div.mainmenu.hrm::before {
    content: "\f5ca";
}

div.mainmenu.members::before {
    content: "\f0c0";
}

div.mainmenu.products::before {
    content: "\f468";
}

div.mainmenu.mrp::before {
    content: "\f474";
}

div.mainmenu.project::before {
    content: "\f0e8";
}

div.mainmenu.ticket::before {
    content: "\f3ff";
}

div.mainmenu.tools::before {
    content: "\f0ad";
}

div.mainmenu.website::before {
    content: "\f542";
}
