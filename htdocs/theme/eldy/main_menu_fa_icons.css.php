<?php if (! defined('NOREQUIRESOC')) die('Must be call by steelsheet'); ?>


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

.mainmenu.cashdesk::before {
    content: "\f788";
}

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