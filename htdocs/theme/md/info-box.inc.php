<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */

/*
 * Component: Info Box
 * -------------------
 */

span.info-box-icon-text {	/* hide box text number due to problems */
    display: none;
}

.info-box {
	display: block;
	min-height: 90px;
	background: #fff;
	width: 100%;
	margin-bottom: 15px;
	border: 1px solid #ddd;
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
	display: block;
	float: left;
	height: 90px;
	width: 90px;
	text-align: center;
	font-size: 45px;
	line-height: 90px;
	background: #eee;
}
.info-box-icon > img {
	max-width: 100%;
}
.info-box-content {
	padding: 5px 10px;
	margin-left: 90px;
}
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
}
.info-box-text{
	font-size: 12px;
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
.info-box-icon {
	color: #000 !important;
}
<?php
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (! isset($conf->global->THEME_AGRESSIVENESS_RATIO)) $conf->global->THEME_AGRESSIVENESS_RATIO=-100;
if (GETPOSTISSET('THEME_AGRESSIVENESS_RATIO')) $conf->global->THEME_AGRESSIVENESS_RATIO=GETPOST('THEME_AGRESSIVENESS_RATIO', 'int');
?>
.bg-infoxbox-project i.fa{
	color: <?php print colorAgressiveness('#605ca8', $conf->global->THEME_AGRESSIVENESS_RATIO); ?> !important;
}
.bg-infoxbox-action i.fa{
	color: <?php print colorAgressiveness('#d84b80', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-propal i.fa,
.bg-infoxbox-facture i.fa,
.bg-infoxbox-commande i.fa{
	color: <?php print colorAgressiveness('#abb87b', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-supplier_proposal i.fa,
.bg-infoxbox-invoice_supplier i.fa,
.bg-infoxbox-order_supplier i.fa{
	color: <?php print colorAgressiveness('#40b0cf', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-contrat i.fa{
	color: <?php print colorAgressiveness('#20a68a', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-bank_account i.fa{
	color: <?php print colorAgressiveness('#e39c42', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-adherent i.fa{
	color: <?php print colorAgressiveness('#f39c12', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-expensereport i.fa{
	color: <?php print colorAgressiveness('#755114', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-holiday i.fa{
	color: <?php print colorAgressiveness('#755114', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}


.fa-dol-action:before {
	content: "\f073";
}
.fa-dol-propal:before,
.fa-dol-supplier_proposal:before {
	content: "\f2b5";
}
.fa-dol-facture:before,
.fa-dol-invoice_supplier:before {
	content: "\f571";
}
.fa-dol-project:before {
	content: "\f0e8";
}
.fa-dol-commande:before,
.fa-dol-order_supplier:before {
	content: "\f570";
}
.fa-dol-contrat:before {
	content: "\f1e6";
}
.fa-dol-bank_account:before {
	content: "\f19c";
}
.fa-dol-adherent:before {
	content: "\f0c0";
}
.fa-dol-expensereport:before {
	content: "\f555";
}
.fa-dol-holiday:before {
	content: "\f5ca";
}


/* USING FONTAWESOME FOR WEATHER */
.info-box-weather .info-box-icon{
	background: #eee !important;
}
.fa-weather-level0:before{
	content: "\f185";
	color : #000000;
}
.fa-weather-level1:before{
	content: "\f6c4";
	color : #000000;
}
.fa-weather-level2:before{
	content: "\f0c2";
	color : #000000;
}
.fa-weather-level3:before{
	content: "\f740";
	color : #000000;
}
.fa-weather-level4:before{
	content: "\f0e7";
	color : #000000;
}

/* USING IMAGES FOR WEATHER INTEAD OF FONT AWESOME */
/* For other themes just uncomment this part */
/*.info-box-weather-level0,
.info-box-weather-level1,
.info-box-weather-level2,
.info-box-weather-level3,
.info-box-weather-level4 {
	background-position: 15px 50%;
	background-repeat: no-repeat;
}

.info-box-weather .info-box-icon{
	display: none !important;
}
.info-box-weather-level0 {
	background-image: url("img/weather/weather-clear.png");
}
.info-box-weather-level1 {
	background-image: url("img/weather/weather-few-clouds.png");
}
.info-box-weather-level2 {
	background-image: url("img/weather/weather-clouds.png");
}
.info-box-weather-level3 {
	background-image: url("img/weather/weather-many-clouds.png");
}
.info-box-weather-level4 {
	background-image: url("img/weather/weather-storm.png");
}*/



.box-flex-container{
	display: flex; /* or inline-flex */
	flex-direction: row;
	flex-wrap: wrap;
	width: 100%;
	margin: 0 0 0 -15px;
	/*justify-content: space-between;*/
}

.box-flex-item{
	flex-grow : 1;
	flex-shrink: 1;
	flex-basis: auto;

	width: 280px;
	margin: 5px 0px 0px 15px;
}
.box-flex-item.filler{
	margin: 0px 0px 0px 15px !important;
	height: 0;
}

