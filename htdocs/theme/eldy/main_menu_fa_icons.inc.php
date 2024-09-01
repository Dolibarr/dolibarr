<?php
/*
/* Copyright (C) 2004-2017	Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/eldy/global.inc.php
 *		\brief      File for CSS style sheet Eldy
 */
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by stylesheet');
}
?>
/* <style type="text/css" > */

.mainmenu::before{
	/* font part */
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>";
	font-weight: 900;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	line-height: 28px;
	-webkit-font-smoothing: antialiased;
	text-align:center;
	text-decoration:none;
	color: var(--colortextbackhmenu);
	/* font-size: <?php echo $topMenuFontSize; ?>; */
}

div.mainmenu.menu {
	background-image: none;
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

/* Define color of some picto */

.fa-phone, .fa-mobile-alt, .fa-fax {
	opacity: 0.7;
	color: #440;
}
.fa-at, .fa-external-link-alt, .fa-share-alt {
	opacity: 0.7;
	color: #304;
}
.fa-trash {
	color: #666;
}
.fa-trash:hover:before {
	color: #800;
}
.fa-play {
	color: #444;
}
.fa-link, .fa-unlink {
	color: #555;
}

/* Define square Dolibarr logo in pure CSS */

.fa-dolibarr-css{
	color: #235481;
	background: currentColor;
	height: 150px;
	width: 150px;
	position: relative;
}
.fa-dolibarr-css:before{
	content: '';
	position: absolute;
	left: 19%;
	top: 17%;
	width: 25%;
	height: 25%;
	border: solid 30px white;
	border-radius: 0% 200% 200% 0% / 0% 180% 180% 0%;
}
.fa-dolibarr-css:after{
	content: '';
	position: absolute;
	left: 19%;
	top: 17%;
	width: 5px;
	height: 25%;
	border-bottom: solid 60px currentColor;
	margin-left: 30px;
}
.tmenu span.fas, .tmenu span.far {
	<?php
	if (!getDolGlobalString('THEME_MENU_COLORLOGO')) {
		echo "color: unset !important;";
	}
	?>;
	line-height: 28px;
	text-align: center;
}

.em092 {
	font-size: 0.92em;
}

.em088 {
	font-size: 0.88em;
}

.em080 {
	font-size: 0.80em;
}
