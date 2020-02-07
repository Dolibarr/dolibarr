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
	font-size: 1.5em;
    -webkit-font-smoothing: antialiased;
    text-align:center;
	text-decoration:none;
	color: #<?php echo $colortextbackhmenu; ?>;
}



div.mainmenu.ticket::before {
    content: "\f3ff";
}
div.mainmenu.ticket {
    background-image: none !important;
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
