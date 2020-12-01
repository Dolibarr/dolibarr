<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */

/*
 * Component: Info Box
 * -------------------
 */
.info-box {
	display: block;
    position: relative;
	min-height: 90px;
	background: #fff;
	width: 100%;
	box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2), 0px 0px 2px rgba(0, 0, 0, 0.1);
	border-radius: 2px;
	margin-bottom: 15px;
}
.info-box.info-box-sm{
    min-height: 80px;
    margin-bottom: 10px;
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
	background: rgba(0, 0, 0, 0.2);
}
.info-box-sm .info-box-icon{
    height: 80px;
    width: 80px;
    font-size: 25px;
    line-height: 80px;
}
.info-box-icon > img {
	max-width: 100%;
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

<?php if(empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS) && !empty($conf->global->MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD)){ ?>
.info-box-icon-text{
    opacity: 1;
}
<?php } ?>

.info-box-sm .info-box-icon-text{
    overflow: hidden;
    width: 80px;
}
.info-box:hover .info-box-icon-text{
    opacity: 1;
}

.info-box-content {
	padding: 5px 10px;
	margin-left: 90px;
}

.info-box-sm .info-box-content{
    margin-left: 80px;
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

$prefix='';
if (! empty($conf->global->THEME_INFOBOX_COLOR_ON_BACKGROUND)) $prefix = 'background-';

if (! isset($conf->global->THEME_AGRESSIVENESS_RATIO)) {
	if ($prefix) {
		$conf->global->THEME_AGRESSIVENESS_RATIO = -50;
	} else {
		$conf->global->THEME_AGRESSIVENESS_RATIO = 0;
	}
}
if (GETPOSTISSET('THEME_AGRESSIVENESS_RATIO')) $conf->global->THEME_AGRESSIVENESS_RATIO=GETPOST('THEME_AGRESSIVENESS_RATIO', 'int');
//var_dump($conf->global->THEME_AGRESSIVENESS_RATIO);
?>
.info-box-icon {
	<?php if ($prefix) { ?>
	color: #fff !important;
	<?php } else { ?>
	background-color: #eee !important;
	<?php } ?>
    opacity: 0.95;
}

.bg-infoxbox-project{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#6c6a98', $conf->global->THEME_AGRESSIVENESS_RATIO); ?> !important;
}
.bg-infoxbox-action{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#b46080', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-propal,
.bg-infoxbox-facture,
.bg-infoxbox-commande{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#99a17d', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-supplier_proposal,
.bg-infoxbox-invoice_supplier,
.bg-infoxbox-order_supplier{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#599caf', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-contrat{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#469686', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-bank_account{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#c5903e', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-adherent{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#79633f', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-expensereport{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#79633f', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
}
.bg-infoxbox-holiday{
	<?php echo $prefix; ?>color: <?php print colorAgressiveness('#755114', $conf->global->THEME_AGRESSIVENESS_RATIO); ?>  !important;
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
	background: rgba(0, 0, 0, 0.08) !important;
}
.fa-weather-level0:before{
	content: "\f185";
	color : #cccccc;
}
.fa-weather-level1:before{
	content: "\f6c4";
	color : #cccccc;
}
.fa-weather-level2:before{
	content: "\f0c2";
	color : #cccccc;
}
.fa-weather-level3:before{
	content: "\f740";
	color : #cccccc;
}
.fa-weather-level4:before{
	content: "\f0e7";
	color : #b91f1f;
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

/* Disabled. This break the responsive on smartphone
.box{
	overflow: visible;
}
*/
