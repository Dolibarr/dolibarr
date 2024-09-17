<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
}
?>
/* <style type="text/css" > */
/*
 Badge style is based on bootstrap framework
 */

.badge {
	display: inline-block;
	padding: .1em .35em;
	font-size: 80%;
	font-weight: 700 !important;
	line-height: 1;
	text-align: center;
	white-space: nowrap;
	vertical-align: baseline;
	border-radius: .25rem;
	transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
	border-width: 2px;
	border-style: solid;
	border-color: rgba(255,255,255,0);
	box-sizing: border-box;
}

.badge-status {
	font-size: 1em;
	padding: .19em .35em;			/* more than 0.19 generate a change into height of lines */
}
.tabBar .arearef .statusref .badge-status, .tabBar .arearefnobottom .statusref .badge-status {
	font-size: 1.1em;
	padding: .4em .4em;
}
/* Force values for small screen 767 */
@media only screen and (max-width: 767px)
{
	.tabBar .arearef .statusref .badge-status, .tabBar .arearefnobottom .statusref .badge-status {
		font-size: 0.95em;
		padding: .3em .2em;
	}
}

.badge-pill, .tabs .badge {
	padding-right: .5em;
	padding-left: .5em;
	border-radius: 0.25rem;
}

.badge-dot {
	padding: 0;
	border-radius: 50%;
	padding: 0.45em;
	vertical-align: unset;
}

a.badge:focus, a.badge:hover {
	text-decoration: none;
}

.liste_titre .badge:not(.nochangebackground) {
	background-color: <?php print $badgeSecondary; ?>;
	color: #fff;
}

span.badgeneutral {
	padding: 2px 7px 2px 7px;
	background-color: #e4e4e4;
	color: #666;
	border-radius: 10px;
	white-space: nowrap;
}


/* PRIMARY */
.badge-primary{
	color: #fff !important;
	background-color: <?php print $badgePrimary; ?>;
}
a.badge-primary.focus, a.badge-primary:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgePrimary, 0.5); ?>;
}
a.badge-primary:focus, a.badge-primary:hover {
	color: #fff !important;
	background-color: <?php print colorDarker($badgePrimary, 10); ?>;
}

/* SECONDARY */
.badge-secondary, .tabs .badge {
	color: #fff !important;
	background-color: <?php print $badgeSecondary; ?>;
}
a.badge-secondary.focus, a.badge-secondary:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeSecondary, 0.5); ?>;
}
a.badge-secondary:focus, a.badge-secondary:hover {
	color: #fff !important;
	background-color: <?php print colorDarker($badgeSecondary, 10); ?>;
}

/* SUCCESS */
.badge-success {
	color: #fff !important;
	background-color: <?php print $badgeSuccess; ?>;
}
a.badge-success.focus, a.badge-success:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeSuccess, 0.5); ?>;
}
a.badge-success:focus, a.badge-success:hover {
	color: #fff !important;
	background-color: <?php print colorDarker($badgeSuccess, 10); ?>;
}

/* DANGER */
.badge-danger {
	color: #fff !important;
	background-color:  <?php print $badgeDanger; ?>;
}
a.badge-danger.focus, a.badge-danger:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeDanger, 0.5); ?>;
}
a.badge-danger:focus, a.badge-danger:hover {
	color: #fff !important;
	background-color: <?php print colorDarker($badgeDanger, 10); ?>;
}

/* WARNING */
.badge-warning {
	color: #fff !important;
	background-color: <?php print $badgeWarning; ?>;
}
a.badge-warning.focus, a.badge-warning:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeWarning, 0.5); ?>;
}
a.badge-warning:focus, a.badge-warning:hover {
	color: #212529 !important;
	background-color: <?php print colorDarker($badgeWarning, 10); ?>;
}

/* WARNING colorblind */
body[class*="colorblind-"] .badge-warning {
	  background-color: <?php print $colorblind_deuteranopes_badgeWarning; ?>;
  }
body[class*="colorblind-"] a.badge-warning.focus,body[class^="colorblind-"] a.badge-warning:focus {
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($colorblind_deuteranopes_badgeWarning, 0.5); ?>;
}
body[class*="colorblind-"] a.badge-warning:focus, a.badge-warning:hover {
	background-color: <?php print colorDarker($colorblind_deuteranopes_badgeWarning, 10); ?>;
}

/* INFO */
.badge-info {
	color: #fff !important;
	background-color: <?php print $badgeInfo; ?>;
}
a.badge-info.focus, a.badge-info:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeInfo, 0.5); ?>;
}
a.badge-info:focus, a.badge-info:hover {
	color: #fff !important;
	background-color: <?php print colorDarker($badgeInfo, 10); ?>;
}

/* LIGHT */
.badge-light {
	color: #212529 !important;
	background-color: <?php print $badgeLight; ?>;
}
a.badge-light.focus, a.badge-light:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeLight, 0.5); ?>;
}
a.badge-light:focus, a.badge-light:hover {
	color: #212529 !important;
	background-color: <?php print colorDarker($badgeLight, 10); ?>;
}

/* DARK */
.badge-dark {
	color: #fff !important;
	background-color: <?php print $badgeDark; ?>;
}
a.badge-dark.focus, a.badge-dark:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeDark, 0.5); ?>;
}
a.badge-dark:focus, a.badge-dark:hover {
	color: #fff !important;
	background-color: <?php print colorDarker($badgeDark, 10); ?>;
}


@media only screen and (max-width: 570px)
{
	span.badge.badge-status {
		overflow: hidden;
		max-width: 130px;
		text-overflow: ellipsis;
	}
}


/* STATUS BADGES */
<?php
for ($i = 0; $i <= 10; $i++) {
	/* Default Status */
	_createStatusBadgeCss((string) $i, '', "STATUS".$i);

	// create status for accessibility
	_createStatusBadgeCss((string) $i, 'colorblind_deuteranopes_', "COLORBLIND STATUS".$i, 'body[class*="colorblind-"] ');
}

_createStatusBadgeCss('1b', '', "STATUS1b");
_createStatusBadgeCss('4b', '', "STATUS4b");

/**
 * Create status badge
 *
 * @param string $statusName name of status
 * @param string $statusVarNamePrefix a prefix for var ${$statusVarNamePrefix.'badgeStatus'.$statusName}
 * @param string $commentLabel a comment label
 * @param string $cssPrefix a css prefix
 * @return void
 */
function _createStatusBadgeCss($statusName, $statusVarNamePrefix = '', $commentLabel = '', $cssPrefix = '')
{
	global ${$statusVarNamePrefix.'badgeStatus'.$statusName}, ${$statusVarNamePrefix.'badgeStatus_textColor'.$statusName};

	if (!empty(${$statusVarNamePrefix.'badgeStatus'.$statusName})) {
		print "\n/* ".strtoupper($commentLabel)." */\n";

		$thisBadgeBackgroundColor = $thisBadgeBorderColor = ${$statusVarNamePrefix.'badgeStatus'.$statusName};


		$TBadgeBorderOnly = array('0', '1b', '3', '4b', '5', '7', '10');
		$thisBadgeTextColor = colorIsLight(${$statusVarNamePrefix.'badgeStatus'.$statusName}) ? '#212529' : '#ffffff';

		if (!empty(${$statusVarNamePrefix.'badgeStatus_textColor'.$statusName})) {
			$thisBadgeTextColor = ${$statusVarNamePrefix.'badgeStatus_textColor'.$statusName};
		}

		if (in_array((string) $statusName, $TBadgeBorderOnly)) {
			$thisBadgeTextColor = '#212529';
			$thisBadgeBackgroundColor = "#fff";
		}

		if (in_array((string) $statusName, array('0', '5', '9'))) {
			$thisBadgeTextColor = '#999999';
		}
		if (in_array((string) $statusName, array('6'))) {
			$thisBadgeTextColor = '#777777';
		}

		// badge-statusX
		print $cssPrefix.".badge-status".$statusName." {\n";
		print "        color: ".$thisBadgeTextColor." !important;\n";
		if (in_array((string) $statusName, $TBadgeBorderOnly)) {
			print "        border-color: ".$thisBadgeBorderColor." !important;\n";
		}
		if ($thisBadgeBackgroundColor != '') {
			print "        background-color: ".$thisBadgeBackgroundColor." !important;\n";
		}
		print "}\n";

		print $cssPrefix.".font-status".$statusName." {\n";
		if ($thisBadgeBackgroundColor != '') {
			print "        color: ".$thisBadgeBackgroundColor." !important;\n";
		}
		print "}\n";

		// badge-statusX:focus
		print $cssPrefix.".badge-status".$statusName.".focus, ".$cssPrefix.".badge-status".$statusName.":focus {\n";
		print "    outline: 0;\n";
		print "    box-shadow: 0 0 0 0.2rem ".colorHexToRgb($thisBadgeBackgroundColor, 0.5)." !important;\n";
		print "}\n";

		print $cssPrefix.".badge-status".$statusName.":focus, ".$cssPrefix.".badge-status".$statusName.":hover {\n";
		print "    color: ".$thisBadgeTextColor." !important;\n";
		//print "    background-color: ".colorDarker($thisBadgeBackgroundColor, 10).";\n";
		if (in_array((string) $statusName, $TBadgeBorderOnly)) {
			print "        border-color: ".colorDarker($thisBadgeBorderColor, 10)." !important;\n";
		}
		print "}\n";
	}
}
