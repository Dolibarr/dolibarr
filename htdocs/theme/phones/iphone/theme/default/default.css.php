<?php
/* Copyright (C) 2009 Regis Houssin		<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       htdocs/theme/phones/iphone/default.css.php
 *		\brief      Fichier de style CSS du theme Iphone default
 *		\version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1'); // We need to use translation files to know direction
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');

require_once("../../../../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


if (! empty($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);	// If language was forced on URL by the main.inc.php
$langs->load("main",0,1);
$right=($langs->direction=='rtl'?'left':'right');
$left=($langs->direction=='rtl'?'right':'left');
?>

body {
    color: #000000;
}

li > a[selected], li > a:active {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/listArrowSel.png'; ?>),
    	url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/selection.png'; ?>) !important;
}

li > a[selected="progress"] {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/loading.gif'; ?>),
    	url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/selection.png'; ?>) !important;
}

/************************************************************************************************/

body > .toolbar {
    background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/toolbar.png'; ?>) #6d84a2 repeat-x;
}

.button {
    -webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/toolButton.png'; ?>) 0 5 0 5;
    -moz-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/toolButton.png'; ?>) 0 5 0 5;
}

.blueButton {
    -webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/blueButton.png'; ?>) 0 5 0 5;
    -moz-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/blueButton.png'; ?>) 0 5 0 5;
}

#backButton {
    -webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/backButtonBrdr.png'; ?>) 0 8 0 14;
    -moz-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/backButtonBrdr.png'; ?>) 0 8 0 14;
    background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/backButtonBack.png'; ?>) repeat-x;
}


.whiteButton {
    -webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/whiteButton.png'; ?>) 0 12 0 12;
    -moz-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/whiteButton.png'; ?>) 0 12 0 12;
    text-shadow: rgba(255, 255, 255, 0.7) 0 1px 0;
}

.redButton {
    -webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/redButton.png'; ?>) 0 12 0 12;
    -moz-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/redButton.png'; ?>') 0 12 0 12;
}

.grayButton {
    -webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/grayButton.png'; ?>) 0 12 0 12;
    -moz-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/grayButton.png'; ?>') 0 12 0 12;
    color: #FFFFFF;
}

/************************************************************************************************/

body > ul > li.group {
	opacity:0.7;
    background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/listGroup.png'; ?>) repeat-x;
}

body > ul > li > a {
    background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/listArrow.png'; ?>) no-repeat right center;
}

/************************************************************************************************/
    
.dialog > fieldset {
    background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/toolbar.png'; ?>) #7388a5 repeat-x;
}

/************************************************************************************************/

body > .panel {
    background: #c8c8c8 url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/pinstripes.png'; ?>);
}

.toggle {
    border: 1px solid #888888;
    background: #FFFFFF url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/toggle.png'; ?>) repeat-x;
}

.toggle[toggled="true"] {
    background: #194fdb url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/toggleOn.png'; ?>) repeat-x;
}

.thumb {
    background: #ffffff url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/thumb.png'; ?>) repeat-x;
}

/************************************************************************************************/
#preloader {
    display: none;
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/loading.gif'; ?>),
    	url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/selection.png'; ?>),
        url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/blueButton.png'; ?>),
        url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/listArrowSel.png'; ?>),
        url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/listGroup.png'; ?>);
}

.toolbar > h1.titleImg {
  background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/title-img.png'; ?>) no-repeat top center;
  color: rgba(0,0,0,0);
}

.backButtonImg {
  width: 50px;
  background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/back-img.png'; ?>) no-repeat center left,
              url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/backButtonBack.png'; ?>) repeat-x top left !important;
  color: rgba(0,0,0,0);
}
