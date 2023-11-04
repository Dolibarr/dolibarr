<?php
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
} ?>
/* <style type="text/css" > */

/*
 * Component: Info Box
 * -------------------
 */

.info-box-module.--external span.info-box-icon-version {
	background: #bbb;
}

.info-box-module.--external.--need-update span.info-box-icon-version{
	background: #bc9525;
}

.info-box {
	display: block;
	position: relative;
	min-height: 90px;
	/* background: #fff; */
	width: 100%;
	box-shadow: 1px 1px 15px rgba(192, 192, 192, 0.2);
	border-radius: 2px;
	border: 1px solid #e9e9e9;
	margin-bottom: 15px;
}
.info-box.info-box-sm {
	min-height: 80px;
	margin-bottom: 10px;
	/* background: #fff; */
}
.opened-dash-board-wrap .info-box.info-box-sm {
	border-radius: 0 0 0 20px;
}
.info-box-more {
	float: right;
	top: 5px;
	position: absolute;
	right: 8px;
}

.info-box small {
	font-size: 14px;
}
.info-box .progress {
	background: rgba(0, 0, 0, 0.2);
	margin: 5px -10px 5px -10px;
	height: 2px;
}
.info-box .progress,
.info-box .progress .progress-bar {
	border-radius: 0;
}

.info-box .progress .progress-bar {
		float: left;
		width: 0;
		height: 100%;
		font-size: 12px;
		line-height: 20px;
		color: #fff;
		text-align: center;
		background-color: #337ab7;
		-webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
		box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
		-webkit-transition: width .6s ease;
		-o-transition: width .6s ease;
		transition: width .6s ease;
}
.info-box-icon {
	border-top-left-radius: 2px;
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 2px;
	display: block;
	overflow: hidden;
	float: left;
	height: 90px;
	width: 90px;
	text-align: center;
	font-size: 45px;
	line-height: 90px;
	background: rgba(0, 0, 0, 0.08) !important;
}

.info-box-module .info-box-icon {
	padding-top: 5px;
	padding-bottom: 5px;
}
.info-box-sm .info-box-icon {
	height: 80px;
	width: 78px;
	font-size: 25px;
	line-height: 92px;
}
.opened-dash-board-wrap .info-box-sm .info-box-icon {
	border-radius: 0 0 0 20px;
}
.opened-dash-board-wrap .info-box-sm .info-box-icon {
	line-height: 80px;
}
.info-box-module .info-box-icon {
	height: 98px;
}
.info-box-icon > img {
	max-width: 100%;
}
.info-box-module .info-box-icon > img {
	max-width: 60%;
}

a.info-box-text.info-box-text-a {
	display: table-cell;
}
a.info-box-text-a i.fa.fa-exclamation-triangle {
	font-size: 0.9em;
}

.info-box-icon-text{
	box-sizing: border-box;
	display: block;
	position: absolute;
	width: 90px;
	bottom: 0px;
	color: #ffffff;
	background-color: rgba(0,0,0,0.1);
	cursor: default;

	font-size: 10px;
	line-height: 15px;
	padding: 0px 3px;
	text-align: center;
	opacity: 0;
	-webkit-transition: opacity 0.5s, visibility 0s 0.5s;
	transition: opacity 0.5s, visibility 0s 0.5s;
}

.info-box-icon-version {
	box-sizing: border-box;
	display: block;
	position: absolute;
	width: 90px;
	bottom: 0px;
	color: #ffffff;
	background-color: rgba(0,0,0,0.1);
	cursor: default;

	font-size: 10px;
	line-height: 22px;
	padding: 0px 3px;
	text-align: center;
	opacity: 1;
	-webkit-transition: opacity 0.5s, visibility 0s 0.5s;
	transition: opacity 0.5s, visibility 0s 0.5s;
}
.box-flex-item.info-box-module.--disabled {
	/* opacity: 0.6; */
}

.info-box-actions {
	position: absolute;
	right: 0;
	bottom: 0;
}

<?php if (empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS) && !empty($conf->global->MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD)) { ?>
.info-box-icon-text{
	opacity: 1;
}
<?php } ?>

.info-box-sm .info-box-icon-text, .info-box-sm .info-box-icon-version{
	overflow: hidden;
	width: 80px;
}
.info-box:hover .info-box-icon-text{
	opacity: 1;
}

.info-box-content {
	padding: 5px 10px;
	margin-left: 84px;
}
.info-box-sm .info-box-content{
	margin-left: 80px;
}
.info-box-sm .info-box-module-enabled {
	/* background: linear-gradient(0.35turn, #fff, #fff, #f6faf8, #e4efe8) */
	background: var(--infoboxmoduleenabledbgcolor);
}
.info-box-content-warning span.font-status4 {
	color: #bc9526 !important;
}
/*.info-box-sm .info-box-content-warning {
	background: #ffd7a3;
}*/
/*.info-box-icon.info-box-icon-module-enabled {
	background: #e4f0e4 !important;
}*/

.info-box-number {
	display: block;
	font-weight: bold;
	font-size: 18px;
}
.progress-description,
.info-box-text,
.info-box-title{
	display: block;
	font-size: 12px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.info-box-title{
	text-transform: uppercase;
	font-weight: bold;
	margin-bottom: 3px;	/* not too much space so we can add another lines */
	opacity: 0.6;
	/* color: var(--colortexttitlenotab); */
}
.info-box-text{
	font-size: 0.92em;
}
.info-box-text:first-letter{text-transform: uppercase}
a.info-box-text{ text-decoration: none;}


.info-box-more {
	display: block;
}
.progress-description {
	margin: 0;
}





/* ICONS INFO BOX */
<?php
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$prefix = '';
if (!empty($conf->global->THEME_INFOBOX_COLOR_ON_BACKGROUND)) {
	$prefix = 'background-';
}

if (!isset($conf->global->THEME_SATURATE_RATIO)) {
	$conf->global->THEME_SATURATE_RATIO = 0.7;
}
if (GETPOSTISSET('THEME_SATURATE_RATIO')) {
	$conf->global->THEME_SATURATE_RATIO = GETPOST('THEME_SATURATE_RATIO', 'int');
}

?>
.info-box-icon {
	<?php if ($prefix) { ?>
	color: #fff !important;
	<?php } ?>
	opacity: 0.95;
	<?php if (isset($conf->global->THEME_SATURATE_RATIO)) { ?>
		filter: saturate(<?php echo $conf->global->THEME_SATURATE_RATIO; ?>);
	<?php } ?>
}

.customer-back {
	background-color: #55955d !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.vendor-back {
	background-color: #599caf !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.user-back {
	background-color: #79633f !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}



.bg-infobox-project{
	<?php echo $prefix; ?>color: #6c6aa8 !important;
}
.bg-infobox-action{
	<?php echo $prefix; ?>color: #b06080 !important;
}
.bg-infobox-propal, .bg-infobox-facture, .bg-infobox-commande {
	<?php echo $prefix; ?>color: #65953d !important;
}
.bg-infobox-supplier_proposal, .bg-infobox-invoice_supplier, .bg-infobox-order_supplier {
	<?php echo $prefix; ?>color: #599caf !important;
}
.bg-infobox-contrat, .bg-infobox-ticket{
	<?php echo $prefix; ?>color: #3bbfa8 !important;
}
.bg-infobox-bank_account{
	<?php echo $prefix; ?>color: #b0bb39 !important;
}
.bg-infobox-adherent, .bg-infobox-member {
	<?php echo $prefix; ?>color: #79633f !important;
}
.bg-infobox-expensereport{
	<?php echo $prefix; ?>color: #79633f !important;
}
.bg-infobox-holiday{
	<?php echo $prefix; ?>color: #755114 !important;
}

.infobox-adherent, .infobox-member {
	color: #79633f !important;
}
.infobox-project{
	color: #6c6aa8 !important;
}
.infobox-action{
	color: #b06080 !important;
}
/* Color for customer object */
.infobox-propal:not(.pictotitle):not(.error),
.infobox-facture:not(.pictotitle):not(.error),
.infobox-commande:not(.pictotitle):not(.error) {
	color: #65953d !important;
}
/* Color for vendor object */
.infobox-supplier_proposal:not(.pictotitle):not(.error),
.infobox-invoice_supplier:not(.pictotitle):not(.error),
.infobox-order_supplier:not(.pictotitle):not(.error) {
	color: #599caf !important;
}
.infobox-contrat, .infobox-ticket{
	color: #3bbfa8 !important;
}
.infobox-bank_account{
	color: #b0bb39 !important;
}
.infobox-adherent, .infobox-member {
	color: #79633f !important;
}
.infobox-expensereport{
	color: #79633f !important;
}
.infobox-holiday{
	color: #755114 !important;
}


.fa-dol-action:before {
	content: "\f073";
}
.fa-dol-propal:before,
.fa-dol-supplier_proposal:before {
	content: "\f573";
}
.fa-dol-facture:before,
.fa-dol-invoice_supplier:before {
	content: "\f571";
}
.fa-dol-project:before {
	content: "\f542";
}
.fa-dol-commande:before,
.fa-dol-order_supplier:before {
	content: "\f570";
}
.fa-dol-contrat:before {
	content: "\f0f2";
}
.fa-dol-ticket:before {
	content: "\f3ff";
}
.fa-dol-bank_account:before {
	content: "\f19c";
}
.fa-dol-member:before {
	content: "\f007";
}
.fa-dol-expensereport:before {
	content: "\f555";
}
.fa-dol-holiday:before {
	content: "\f5ca";
}


/* USING FONTAWESOME FOR WEATHER */
.info-box-weather .info-box-icon{
	background: rgba(0, 0, 0, 0.08) !important;
}
.fa-weather-level0:before{
	content: "\f185";
	color : #cfbf00;
}
.fa-weather-level1:before{
	content: "\f6c4";
	color : #bc9526;
}
.fa-weather-level2:before{
	content: "\f743";
	color : #b16000;
}
.fa-weather-level3:before{
	content: "\f740";
	color : #b04000;
}
.fa-weather-level4:before{
	content: "\f0e7";
	color : #b01000;
}


.box-flex-container{
	display: flex; /* or inline-flex */
	flex-direction: row;
	flex-wrap: wrap;
	width: calc(100% + 14px);
	margin: 0 -8px 0 -8px;
	/*justify-content: space-between;*/
}

.box-flex-item{
	flex-grow : 1;
	flex-shrink: 1;
	flex-basis: auto;

	width: 280px;
	margin: 5px 8px 0px 8px;
}
.box-flex-item.filler{
	margin: 0px 0px 0px 15px !important;
	height: 0;
}

.info-box-title {
	width: calc(100% - 20px);
}
.info-box-module {
	min-width: 350px;
	max-width: 350px;
}

@media only screen and (max-width: 1740px) {
	.info-box-module {
		min-width: 315px;
		max-width: 315px;
	}
}

@media only screen and (max-width: 767px) {
	.info-box-module {
		min-width: 260px;
	}
}

.info-box-module .info-box-content {
	height: 98px;
}
/* Disabled. This break the responsive on smartphone
.box{
	overflow: visible;
}
*/

@media only screen and (max-width: 767px)
{
	.box-flex-container {
		margin: 0 0 0 0px !important;
		width: 100% !important;
	}
	.info-box-module {
		width: 100%;
		max-width: unset;
	}
	.info-box-sm .info-box-icon {
		width: 60px;
	}
	.info-box-sm .info-box-content {
		margin-left: 60px;
	}
	.info-box {
		border: 1px solid #e0e0e0;
	}
}
}
