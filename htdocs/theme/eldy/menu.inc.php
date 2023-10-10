<?php
if (!defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > dont remove this line it's an ide hack */


/* ============================================================================== */
/* Dropdown Megamenu Menu                                                         */
/* ============================================================================== */

:root {
	--menu-bg-color: rgb(<?php echo colorStringToArray($colorbackhmenu1)[0]/2 .",".colorStringToArray($colorbackhmenu1)[1]/2 .",".colorStringToArray($colorbackhmenu1)[2]/2; ?>);
	--reduc1-width: <?php echo (empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC1) ? round($nbtopmenuentries * 90, 0) + 240 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC1); ?>px;
	--reduc2-width: <?php echo (empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2) ? round($nbtopmenuentries * 69, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2); ?>px;
	--reduc3-width: <?php echo (empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3) ? round($nbtopmenuentries * 47, 0) +  40 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3); ?>px;
}


/* hide menu by default */
div.submenuroot {
	visibility:hidden;
	opacity:0;
	display:none;
}


/* TRANSITION SETUP */
div.submenuroot {
	//transition: opacity linear 0.2s; // more usable and eyes friendly without transition
	margin-top: 10px;
}
/* END TRANSITION SETUP */


/* Reduc3-2 setup */
@media only screen
and
(min-width: <?php echo (empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3) ? round($nbtopmenuentries * 47, 0) +  40 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3); ?>px )
and
(max-width: <?php echo (empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2) ? round($nbtopmenuentries * 69, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2); ?>px )
{
	#tmenu_tooltip>.tmenudiv>.tmenu, #tmenu_tooltip>.tmenudiv{
		position:unset !important;
	}
	div.submenuroot div.tmenucenter {
		max-width: unset;
		text-overflow: unset;
	}
}

/* Reduc3-1 setup */
@media only screen
and
(min-width: <?php echo (empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3) ? round($nbtopmenuentries * 47, 0) +  40 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3); ?>px )
{

	li:not(.nohover):not(.menuhider).tmenu:not(.submenu), li.tmenusel:not(.submenu){
		border-left:solid 1px transparent;
		border-right:solid 1px transparent;
	}

	li:not(.nohover):not(.menuhider).tmenu::after{
		background:transparent;
		content:'';
		height:0px;
		display:block;
	}
	/* enable megamenu */
	li div.submenuroot{
		display:block;
		max-width: calc(100% - 2px);
	}
	li.tmenu:not(.nohover):not(.menuhider):not(.submenu):hover, li.tmenusel:not(.nohover):not(.menuhider):not(.submenu):hover{
		background-color: var(--menu-bg-color);
		border-left:solid 1px #aaa;
		border-right:solid 1px #aaa;
	}
	/* show megamenu on hover */
	li:not(.nohover):not(.menuhider):hover div.submenuroot{
		visibility:visible;
		opacity:1;
		/*transition-delay: 0.1s*/
	}
	/* to correct show megamenu items */
	div.submenuroot .tmenucenter{
		width:auto !important;
	}
	/* to allow megamenu to 'follow' menu items total width */
	#tmenu_tooltip>.tmenudiv>ul.tmenu {
		/*display: inline-block !important;*/
		position: relative;
	}
	/* overwrite origial value to allow correct megamenu absolute position  */
	li:not(.nohover).tmenu, li:not(.nohover).tmenusel{
		position:unset;
	}
	/* fix menu indicator (white arrow) after changing previous rule  */
	li:not(.nohover).tmenusel::after, li:not(.nohover).tmenu::after {
		left:auto;
		margin-left:-6px;
	}
	/* setup megamenu bgcolor, width, spacing, etc...  */
	div.submenuroot
	{
		position: absolute;
		text-align: center;
		left: 0px;
		margin-left: 0px;
		min-width: calc(100% - 2px);
		min-height: 250px;
		background-color: var(--menu-bg-color);
		border:solid 1px #aaa;
		box-shadow: 2px 2px 3px rgba(0,0,0,.3);
		padding:0px !important;
		padding-bottom:0px !important;
	}
	li:not(.nohover):not(.menuhider):hover div.submenuroot
	{
		margin-top: 2px;
	}
	div.submenuroot>.tmenudiv
	{
		margin:20px;
	}
	/* setup submenus column layout */
	div.submenuroot>.tmenudiv>ul{
		column-gap: 30px;
		display: inline-flex;
		flex-wrap: wrap;
		margin-bottom:20px !important;
	}
	/* setup submenus column layout for 1 child elements */
	.submenu0:only-child>div>div>ul{
		display: inline-flex;
		flex-wrap: wrap;
		column-gap: 30px;
	}
	.submenu0>div>div>ul{
		margin-bottom:20px;
	}
	/* style submenu1 labels */
	.submenu1>div>a{
		font-size: 110%;
	}
	/* setup main items to a bigger font */
	.submenu0>div>a{
		font-weight: bold;
		font-size:120%;
	}
	/* setup submenus 'line' to make it more readable */
	.submenu:not(.submenu0) ul{
		border-left:solid 1px #aaa;
		border-bottom-left-radius: 10px;
	}
	/* setup menu indicator (gray arrow) to highlight current hovered menu item */
	test li.tmenu:hover::after, test li.tmenusel:hover::after {
		border-color: #aaa transparent transparent transparent;
		border-width: 7px 7px 0px 7px;
		z-index:30;
		bottom:-2px;
	}
	/* setup menu indicator (gray arrow) to highlight current hovered menu item */
	li.tmenu:not(.nohover):hover::after, li.tmenusel:not(.nohover):hover::after {
		background-color: var(--menu-bg-color);;
		height:3px;
		border:0px;
		position: relative;
		display: block;
		width: 100%;
		z-index: 30;
		margin-left: 0px;
		margin-top: -3px;
		left:auto;
	}
	/* overwrite original value to correct display megamenu items */
	.tmenucenter ul.tmenu>li>div.tmenucenter {
		height: auto
	}
	/* setup megamenu items style */
	.tmenucenter ul.tmenu>li>div.tmenucenter a[id] {
		width: 100%;
		display: block;
		color: #fff;
		text-align:left;
	}
	/* setup items left padding/margin also to correct display rule*/
	ul.tmenu ul.tmenu {
		padding-left:7px;
		margin-left:7px;
	}
}
