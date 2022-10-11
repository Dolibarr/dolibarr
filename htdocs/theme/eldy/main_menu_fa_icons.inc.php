<?php if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
} ?>
/* <style type="text/css" > */

.mainmenu::before{
	/* font part */
	font-family: "Font Awesome 5 Free";
	font-weight: 900;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	line-height: 28px;
	-webkit-font-smoothing: antialiased;
	text-align:center;
	text-decoration:none;
	color: var(--colortextbackhmenu);
	/* font-size: <?php echo $topMenuFontSize; ?>; */
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
	content: "\f51e";
}

div.mainmenu.accountancy::before {
	/* content: "\f53d"; */
	content: "\f688";
	font-size: 1.2em;
}

div.mainmenu.agenda::before {
	content: "\f073";
}

div.mainmenu.bank::before {
	content: "\f19c";
}

<?php if (getDolGlobalInt('MAIN_FEATURES_LEVEL') == 2) { ?>
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
	content: "\f0f2";
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
	content: "\f508";
}

div.mainmenu.members::before {
	content: "\f007";
}

div.mainmenu.products::before {
	content: "\f1b2";
}

div.mainmenu.mrp::before {
	content: "\f1b3";
}

div.mainmenu.project::before {
	content: "\f542";
}

div.mainmenu.ticket::before {
	content: "\f3ff";
}

div.mainmenu.tools::before {
	content: "\f0ad";
}

div.mainmenu.website::before {
	content: "\f57d";
}

div.mainmenu.generic1::before {
	content: "\f249";
}

div.mainmenu.generic2::before {
	content: "\f249";
}

div.mainmenu.generic3::before {
	content: "\f249";
}

div.mainmenu.generic4::before {
	content: "\f249";
}

/* Define color of some picto */

.fa-phone, .fa-mobile-alt, .fa-fax {
	opacity: 0.7;
	color: #440;
}
.fa-at, .fa-external-link-alt, .fa-share-alt {
	opacity: 0.7;
	color: #304;
}
.fa-trash {
	color: #666;
}
.fa-trash:hover:before {
	color: #800;
}
.fa-play {
	color: #444;
}
.fa-link, .fa-unlink {
	color: #555;
}

/* Define square Dolibarr logo in pure CSS */

.fa-dolibarr-css{
	color: #235481;
	background: currentColor;
	height: 150px;
	width: 150px;
	position: relative;
}
.fa-dolibarr-css:before{
	content: '';
	position: absolute;
	left: 19%;
	top: 17%;
	width: 25%;
	height: 25%;
	border: solid 30px white;
	border-radius: 0% 200% 200% 0% / 0% 180% 180% 0%;
}
.fa-dolibarr-css:after{
	content: '';
	position: absolute;
	left: 19%;
	top: 17%;
	width: 5px;
	height: 25%;
	border-bottom: solid 60px currentColor;
	margin-left: 30px;
}


.em092 {
	font-size: 0.92em;
}

.em088 {
	font-size: 0.88em;
}

.em080 {
	font-size: 0.80em;
}
