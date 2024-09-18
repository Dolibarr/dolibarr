<?php
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
} ?>
/* <style type="text/css" > */

:root {
			--btncolortext: rgb(<?php print $colortextlink; ?>);
			--btncolorbg: #fbfbfb;
			--btncolorborderhover: none;
			--btncolorborder: #FFF;
			--butactiondeletebg: rgb(234,228,225);
			--butactionbg: rgb(<?php print $butactionbg; ?>);
			--textbutaction: rgb(<?php print $textbutaction; ?>);
}

<?php
if (getDolGlobalString('THEME_DARKMODEENABLED')) {
	print "/* For dark mode */\n";
	if (getDolGlobalInt('THEME_DARKMODEENABLED') != 2) {
		print "@media (prefers-color-scheme: dark) {";	// To test, click on the 3 dots menu, then Other options then Display then emulate prefer-color-schemes
	} else {
		print "@media not print {";
	}
	print "
      :root {

            --btncolortext: ;
            --btncolorbg: rgb(26,27,27);
            --btncolorborderhover: #ffffff;
            --btncolorborder: #2b2c2e;
            --butactiondeletebg: rgb(252,84,91);
			--butactionbg: rgb(173,140,79);
			--textbutaction: rgb(255,255,255);

      }\n";
	print "}";
}
?>


/* ============================================================================== */
/* Buttons for actions                                                            */
/* ============================================================================== */

/*div.divButAction {
	margin-bottom: 1.4em;
}*/
div.tabsAction > a.butAction, div.tabsAction > a.butActionRefused, div.tabsAction > a.butActionDelete,
div.tabsAction > span.butAction, div.tabsAction > span.butActionRefused, div.tabsAction > span.butActionDelete,
div.tabsAction > div.divButAction > span.butAction,
div.tabsAction > div.divButAction > span.butActionDelete,
div.tabsAction > div.divButAction > span.butActionRefused,
div.tabsAction > div.divButAction > a.butAction,
div.tabsAction > div.divButAction > a.butActionDelete,
div.tabsAction > div.divButAction > a.butActionRefused {
	margin-bottom: 1.4em !important;
	margin-right: 0px !important;
}
.divButAction {
	padding: 0 !important;
	margin: 0 !important;
}
div.tabsActionNoBottom > a.butAction, div.tabsActionNoBottom > a.butActionRefused {
	margin-bottom: 0 !important;
}

span.butAction, span.butActionDelete {
	cursor: pointer;
}
.paginationafterarrows .butAction {
	font-size: 0.9em;
}
.butAction {
	background: var(--butactionbg);
	color: var(--textbutaction) !important;
	/* background: rgb(230, 232, 239); */
}
.butActionRefused, .butAction, .butActionDelete {
	border-radius: 3px;
}
:not(.center) > .butActionRefused:last-child, :not(.center) > .butAction:last-child, :not(.center) > .butActionDelete:last-child {
	margin-<?php echo $right; ?>: 0px !important;
}
.butActionRefused, .butAction, .butAction:link, .butAction:visited, .butAction:hover, .butAction:active, .butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active,
.divButAction
 {
	text-decoration: none;
	text-transform: uppercase;
	font-weight: bold;

	margin: 0em <?php echo($dol_optimize_smallscreen ? '0.6' : '1'); ?>em;
	padding: 0.6em <?php echo($dol_optimize_smallscreen ? '0.6' : '0.7'); ?>em;
	display: inline-block;
	text-align: center;
	cursor: pointer;
	color: #444;
	border: 1px solid transparent;	/* So for buttonRefused with a border, it will not have any flash effect */

	/* border: 1px solid #aaa; */
	/* border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25); */

	/*border-top-right-radius: 0 !important;
	border-bottom-right-radius: 0 !important;
	border-top-left-radius: 0 !important;
	border-bottom-left-radius: 0 !important;*/
}
.butActionNew, .butActionNewRefused, .butActionNew:link, .butActionNew:visited, .butActionNew:hover, .butActionNew:active {
	text-decoration: none;
	text-transform: uppercase;
	font-weight: normal;

	margin: 0em 0.3em 0 0.3em !important;
	padding: 0.2em <?php echo($dol_optimize_smallscreen ? '0.4' : '0.7'); ?>em 0.3em;
	font-family: <?php print $fontlist ?>;
	display: inline-block;
	/* text-align: center; New button are on right of screen */
	cursor: pointer;
}

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

a.butActionNew>span.fa, a.butActionNew>span.fa:hover,
span.butActionNew>span.fa, span.butActionNew>span.fa:hover,
a.butActionNewRefused>span.fa, a.butActionNewRefused>span.fa:hover,
span.butActionNewRefused>span.fa, span.butActionNewRefused>span.fa:hover
{
	padding-<?php echo $left; ?>: 6px;
	font-size: 1.5em;
	border: none;
	box-shadow: none; webkit-box-shadow: none;
}

.butAction:hover, .dropdown-holder.open > .butAction   {
	/** TODO use css var with hsl from --colortextlink to allow create darken or lighten color */
	-webkit-box-shadow: 0px 0px 6px rgba(50,50,50,0.4), inset 0px 0px 200px rgba(0,0,0,0.3); /* fix hover feedback : use "inset" background to easily darken background */
	box-shadow: 0px 0px 6px rgba(50,50,50,0.4), inset 0px 0px 200px rgba(0,0,0,0.3); /* fix hover feedback : use "inset" background to easily darken background */
}
.butActionNew:hover   {
	text-decoration: underline;
	box-shadow: unset !important;
}

.butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active, .buttonDelete {
	background: var(--butactiondeletebg) !important;
	/* border: 1px solid #633; */
	color: #633 !important;
}

.butActionDelete:hover {
	-webkit-box-shadow: 0px 0px 6px 1px rgba(50, 50, 50, 0.4), 0px 0px 0px rgba(60,60,60,0.1);
	box-shadow: 0px 0px 6px 1px rgba(50, 50, 50, 0.4), 0px 0px 0px rgba(60,60,60,0.1);
}

.butActionRefused {
	text-decoration: none !important;
	text-transform: uppercase;
	font-weight: bold !important;

	white-space: nowrap !important;
	cursor: not-allowed !important;
	font-family: <?php print $fontlist ?> !important;
	display: inline-block;
	text-align: center;
	cursor: pointer;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	color: #999 !important;

	border: 1px solid #ccc;
}
.butActionNewRefused, .butActionNewRefused:link, .butActionNewRefused:visited, .butActionNewRefused:hover, .butActionNewRefused:active {
	text-decoration: none !important;
	text-transform: uppercase;
	font-weight: normal !important;

	white-space: nowrap !important;
	cursor: not-allowed !important;
	margin: 0em <?php echo($dol_optimize_smallscreen ? '0.7' : '0.9'); ?>em;
	padding: 0.2em <?php echo($dol_optimize_smallscreen ? '0.4' : '0.7'); ?>em;
	font-family: <?php print $fontlist ?> !important;
	display: inline-block;
	/* text-align: center;  New button are on right of screen */
	cursor: pointer;
	color: #999 !important;
	padding-top: 0.2em;
	box-shadow: none !important;
	-webkit-box-shadow: none !important;
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
	padding: 4px 4px 4px 4px;
	font-weight: 400;
	/* line-height: 1; */
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
	box-shadow: var(--btncolorbg);
	text-decoration: none;
	position: relative;
	/* margin: 0 0 0 8px; */
	min-width: 72px;
	text-align: center;
	color: var(--btncolortext);
	border: none;
	font-size: 12px;
	font-weight: 300;
	background-color: var(--btncolorbg);
	border: 1px solid var(--btncolorborder);
}

a.btnTitle.btnTitleSelected {
	border: 1px solid #ccc;
	border-radius: 3px;
}

.btnTitle > .btnTitle-icon{

}

.btnTitle > .btnTitle-label{
	color: #666666;
}

.btnTitle:hover, a.btnTitle:hover {
	border: 1px solid #bbb;
	border-radius: 3px;
	position: relative;
	text-align: center;
	font-size: 12px;
	text-decoration: none;
	box-shadow: none;
}
/* The buttonplus isgrowing on hover (don't know why). This is to avoid to have the cellegrowing too */
.btnTitlePlus:hover {
	max-width: 24px;
	max-height: 40px;
}

.btnTitle.refused, a.btnTitle.refused, .btnTitle.refused:hover, a.btnTitle.refused:hover {
	color: #8a8a8a;
	cursor: not-allowed;
	background-color: #fbfbfb;
	background: repeating-linear-gradient( 45deg, #ffffff, #f1f1f1 4px, #f1f1f1 4px, #f1f1f1 4px );
}

.btnTitle:hover .btnTitle-label {
	color: var(--btncolorborderhover);
}
.btnTitle.reposition:not(.btnTitleSelected) {
	background-color: unset;
	border: unset;
}

.btnTitle.refused .btnTitle-label, .btnTitle.refused:hover .btnTitle-label {
	color: #8a8a8a;
}

.btnTitle>.fa {
	font-size: 2em;
	display: block;
}

div.pagination li:first-child a.btnTitle, div.pagination li.paginationafterarrows a.btnTitle {
	margin-<?php echo $left; ?>: 10px;
}

.button-title-separator{
	display: inline-block;
	clear: both;
	width: 20px;
}

.imgforviewmode {
	color: #aaa;
}

/* rule to reduce top menu - 2nd reduction: Reduce width of top menu icons again */
@media only screen and (max-width: <?php echo !getDolGlobalString('THEME_ELDY_WITDHOFFSET_FOR_REDUC2') ? round($nbtopmenuentries * 69, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2; ?>px)	/* reduction 2 */
{
	.butAction, .butActionRefused, .butActionDelete {
		font-size: 0.95em;
	}
	.btnTitle, a.btnTitle {
		display: inline-block;
		padding: 4px 4px 4px 4px;
		min-width: unset;
	}
}

/* rule to reduce top menu - 3rd reduction: The menu for user is on left */
@media only screen and (max-width: <?php echo !getDolGlobalString('THEME_ELDY_WITDHOFFSET_FOR_REDUC3') ? round($nbtopmenuentries * 47, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3; ?>px)	/* reduction 3 */
{
	.butAction, .butActionRefused, .butActionDelete {
		font-size: 0.9em;
	}
}

/* smartphone */
@media only screen and (max-width: 767px)
{
	.butAction, .butActionRefused, .butActionDelete {
		font-size: 0.85em;
	}
}


<?php if (getDolGlobalString('MAIN_BUTTON_HIDE_UNAUTHORIZED') && (!$user->admin)) { ?>
.butActionRefused, .butActionNewRefused, .btnTitle.refused {
	display: none !important;
}
<?php } ?>


/*
 * BTN LINK (used with <a/> tag in general but not with <button/>)
 */

.btn-link{
	margin-right: 5px;
	border: 1px solid #ddd;
	color: #333;
	padding: 5px 10px;
	border-radius:1em;
	text-decoration: none !important;
}

.btn-link:hover{
	background-color: #ddd;
	border: 1px solid #ddd;
}


/*
 * BUTTON With Low emphasis
 */

button.btn-low-emphasis{
	text-align: center;
	display: inline-block;
	border: none;
	outline: none;
	cursor: pointer;
	margin: 0;
	padding: 0;
	width: auto;
	min-width: 1.5em;
	min-height: 1.5em;
	line-height: 1.5em;

	overflow: visible;
	background: transparent;
	background-position: center; /* used for hover ripple effect */
	background-size: 0%;
	color: var(--colortextlink, inherit);
	font: inherit;
	line-height: normal;

	/* Corrects font smoothing for webkit */
	-webkit-font-smoothing: inherit;
	-moz-osx-font-smoothing: inherit;

	/* Corrects inability to style clickable input types in iOS */
	-webkit-appearance: none;


	transition: background 0.8s;/* used for hover ripple effect */
	background: transparent radial-gradient(circle, transparent 1%, hsla(var(--colortextlink-h),var(--colortextlink-s) ,var(--colortextlink-l) , 0.1) 1%, transparent 10%) center/15000%;
}

button.btn-low-emphasis.--btn-icon{
	border-radius: 100%;
}

button.btn-low-emphasis :is(.fa, .fas){
	color: var(--colortextlink, inherit);
	opacity: 0.4;
}

button.btn-low-emphasis:is(:focus,:hover) :is(.fa, .fas){
	opacity: 0.8;
}

button.btn-low-emphasis.--btn-icon:active {
	background-color:  hsla(var(--colortextlink-h),var(--colortextlink-s) ,var(--colortextlink-l) , 0.1);
	background-size: 100%;
	transition: background 0s;/* used for hover ripple effect */
}
