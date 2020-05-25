<?php
if (!defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */



/* ============================================================================== */
/* Buttons for actions                                                            */
/* ============================================================================== */


div.divButAction {
	margin-bottom: 1.4em;
	vertical-align: top;
}
div.tabsAction > a.butAction, div.tabsAction > a.butActionRefused, div.tabsAction > a.butActionDelete,
div.tabsAction > span.butAction, div.tabsAction > span.butActionRefused, div.tabsAction > span.butActionDelete {
	margin-bottom: 1.4em !important;
}

span.butAction, span.butActionDelete {
	cursor: pointer;
}


.button, .buttonDelete, .butAction, .butActionDelete, .butActionRefused, .butActionNewRefused {
	border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);
	display: inline-block;
	padding: 0.4em <?php echo ($dol_optimize_smallscreen ? '0.4' : '0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen ? '0.7' : '0.9'); ?>em;
	line-height: 20px;
	text-align: center;
	vertical-align: middle;
	cursor: pointer;
	color: #333333 !important;
	text-decoration: none !important;
	text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x;
	border-color: #e6e6e6 #e6e6e6 #bfbfbf;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	border: 1px solid #bbbbbb;
	border-bottom-color: #a2a2a2;
	-webkit-border-radius: 2px;
	border-radius: 2px;
	-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
}
.butActionNew, .butActionNewRefused, .butActionNew:link, .butActionNew:visited, .butActionNew:hover, .butActionNew:active {
	text-decoration: none;
	/* border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25); */
	display: inline-block;
	padding: 0.2em <?php echo ($dol_optimize_smallscreen ? '0.4' : '0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen ? '0.7' : '0.9'); ?>em;
	line-height: 20px;
	/* text-align: center;  New button are on right of screen */
	vertical-align: middle;
	cursor: pointer;
	/* color: #ffffff !important; */
	/* text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25); */
	-webkit-border-radius: 2px;
	border-radius: 2px;
	/* -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05); */
	/* background-color: #006dcc;
	background-image: -moz-linear-gradient(top, #0088cc, #0044cc);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#0088cc), to(#0044cc));
	background-image: -webkit-linear-gradient(top, #0088cc, #0044cc);
	background-image: -o-linear-gradient(top, #0088cc, #0044cc);
	background-image: linear-gradient(to bottom, #0088cc, #0044cc);
	background-repeat: repeat-x; */
}
a.butActionNew>span.fa-plus-circle { padding-left: 6px; font-size: 1.5em; }
a.butActionNewRefused>span.fa-plus-circle { padding-left: 6px; font-size: 1.5em; }

.tableforfieldcreate a.butActionNew>span.fa-plus-circle, .tableforfieldcreate a.butActionNew>span.fa-plus-circle:hover,
.tableforfieldedit a.butActionNew>span.fa-plus-circle, .tableforfieldedit a.butActionNew>span.fa-plus-circle:hover,
span.butActionNew>span.fa-plus-circle, span.butActionNew>span.fa-plus-circle:hover,
a.butActionNewRefused>span.fa-plus-circle, a.butActionNewRefused>span.fa-plus-circle:hover,
span.butActionNewRefused>span.fa-plus-circle, span.butActionNewRefused>span.fa-plus-circle:hover,
a.butActionNew>span.fa-list-alt, a.butActionNew>span.fa-list-alt:hover,
span.butActionNew>span.fa-list-alt, span.butActionNew>span.fa-list-alt:hover,
a.butActionNewRefused>span.fa-list-alt, a.butActionNewRefused>span.fa-list-alt:hover,
span.butActionNewRefused>span.fa-list-alt, span.butActionNewRefused>span.fa-list-alt:hover
{
	font-size: 1em;
	padding-left: 0px;
}

.button, .butAction {
	color: #ffffff !important;
	text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
	background-color: #006dcc;
	background-image: -moz-linear-gradient(top, #0088cc, #0044cc);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#0088cc), to(#0044cc));
	background-image: -webkit-linear-gradient(top, #0088cc, #0044cc);
	background-image: -o-linear-gradient(top, #0088cc, #0044cc);
	background-image: linear-gradient(to bottom, #0088cc, #0044cc);
	background-repeat: repeat-x;
	border-color: #0044cc #0044cc #002a80;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
}
.button:disabled, .butAction:disabled {
	color: #666 !important;
	text-shadow: none;
	border-color: #555;
	cursor: not-allowed;

	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x
}

button.ui-button {
	padding-top: 5px;
}

.butActionDelete, .buttonDelete {
	color: #ffffff !important;
	text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
	background-color: #cc6d00;
	background-image: -moz-linear-gradient(top, #cc8800, #cc4400);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#cc8800), to(#cc4400));
	background-image: -webkit-linear-gradient(top, #cc8800, #cc4400);
	background-image: -o-linear-gradient(top, #cc8800, #cc4400);
	background-image: linear-gradient(to bottom, #cc8800, #cc4400);
	background-repeat: repeat-x;
	border-color: #cc4400 #cc4400 #802a00;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
}
a.butAction:link, a.butAction:visited, a.butAction:hover, a.butAction:active {
	color: #FFFFFF;
}

.butActionRefused, .butActionNewRefused {
	color: #AAAAAA !important;
	cursor: not-allowed !important;
}

a.butAction:hover, a.butActionDelete:hover, a.butActionRefused:hover {
	text-decoration: none;
}
a.butActionNewRefused:hover {
	border-color: unset !important;
	border: 1px solid #bbbbbb;
}
a.butAction:hover, a.butActionNew:hover, a.butActionDelete:hover {
	opacity: 0.9;
}

.butActionTransparent {
	color: #222 ! important;
	background-color: transparent ! important;
}


/*
TITLE BUTTON
 */

.btnTitle, a.btnTitle {
    display: inline-block;
    padding: 6px 12px;
    font-size: 14px
    font-weight: 400;
    line-height: 1.4;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    box-shadow: none;
    text-decoration: none;
    position: relative;
    margin: 0 0 0 10px;
    min-width: 80px;
    text-align: center;
    color: rgb(<?php print $colortextlink; ?>);
    border: none;
    font-size: 12px;
    font-weight: 300;
    background-color: #fbfbfb;
}

.btnTitle > .btnTitle-icon{

}

.btnTitle > .btnTitle-label{
    color: #666666;
}

.btnTitle:hover, a.btnTitle:hover {
    border-radius: 3px;
    position: relative;
    margin: 0 0 0 10px;
    text-align: center;
    color: #ffffff;
    background-color: rgb(<?php print $colortextlink; ?>);
    font-size: 12px;
    text-decoration: none;
    box-shadow: none;
}

.btnTitle.refused, a.btnTitle.refused, .btnTitle.refused:hover, a.btnTitle.refused:hover {
        color: #8a8a8a;
        cursor: not-allowed;
        background-color: #fbfbfb;
        background: repeating-linear-gradient( 45deg, #ffffff, #f1f1f1 4px, #f1f1f1 4px, #f1f1f1 4px );
}

.btnTitle:hover .btnTitle-label{
    color: #ffffff;
}
div.pagination .btnTitle:hover .btnTitle-label{
	color: rgb(<?php print $colortextlink; ?>);
}

.btnTitle.refused .btnTitle-label, .btnTitle.refused:hover .btnTitle-label{
    color: #8a8a8a;
}

.btnTitle>.fa {
    font-size: 20px;
    display: block;
}





<?php if (!empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED) && (!$user->admin)) { ?>
.butActionRefused, .butActionNewRefused, .btnTitle.refused {
    display: none !important;
}
<?php }
