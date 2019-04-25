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
