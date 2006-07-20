<?php
//============================================================+
// File name   : image.php
// Begin       : 2002-07-31
// Last Update : 2005-01-08
// Author      : Karim Mribti [barcode@mribti.com]
//             : Nicola Asuni [info@tecnick.com]
// Version     : 0.0.8a  2001-04-01 (original code)
// License     : GNU LGPL (Lesser General Public License) 2.1
//               http://www.gnu.org/copyleft/lesser.txt
// Source Code : http://www.mribti.com/barcode/
//
// Description : Barcode Image Rendering.
//
// NOTE:
// This version contains changes by Nicola Asuni:
//  - porting to PHP5
//  - code style and formatting
//  - automatic php documentation in PhpDocumentor Style
//    (www.phpdoc.org)
//  - minor bug fixing
//============================================================+

/**
 * Barcode Image Rendering.
 * @author Karim Mribti, Nicola Asuni
 * @name BarcodeObject
 * @package com.tecnick.tcpdf
 * @version 0.0.8a  2001-04-01 (original code)
 * @since 2001-03-25
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 */

/**
 * 
 */

require("../../shared/barcode/barcode.php");
require("../../shared/barcode/i25object.php");
require("../../shared/barcode/c39object.php");
require("../../shared/barcode/c128aobject.php");
require("../../shared/barcode/c128bobject.php");
require("../../shared/barcode/c128cobject.php");

if (!isset($_REQUEST['style'])) $_REQUEST['style'] = BCD_DEFAULT_STYLE;
if (!isset($_REQUEST['width'])) $_REQUEST['width'] = BCD_DEFAULT_WIDTH;
if (!isset($_REQUEST['height'])) $_REQUEST['height'] = BCD_DEFAULT_HEIGHT;
if (!isset($_REQUEST['xres'])) $_REQUEST['xres'] = BCD_DEFAULT_XRES;
if (!isset($_REQUEST['font'])) $_REQUEST['font'] = BCD_DEFAULT_FONT;
if (!isset($_REQUEST['type'])) $_REQUEST['type'] = "C39";
if (!isset($_REQUEST['code'])) $_REQUEST['code'] = "";

switch (strtoupper($_REQUEST['type'])) {
	case "I25": {
		$obj = new I25Object($_REQUEST['width'], $_REQUEST['height'], $_REQUEST['style'], $_REQUEST['code']);
		break;
	}
	case "C128A": {
		$obj = new C128AObject($_REQUEST['width'], $_REQUEST['height'], $_REQUEST['style'], $_REQUEST['code']);
		break;
	}
	case "C128B": {
		$obj = new C128BObject($_REQUEST['width'], $_REQUEST['height'], $_REQUEST['style'], $_REQUEST['code']);
		break;
	}
	case "C128C": {
		$obj = new C128CObject($_REQUEST['width'], $_REQUEST['height'], $_REQUEST['style'], $_REQUEST['code']);
		break;
	}
	case "C39":
	default: {
		$obj = new C39Object($_REQUEST['width'], $_REQUEST['height'], $_REQUEST['style'], $_REQUEST['code']);
		break;
	}
}

if ($obj) {
	$obj->SetFont($_REQUEST['font']);   
	$obj->DrawObject($_REQUEST['xres']);
	$obj->FlushObject();
	$obj->DestroyObject();
	unset($obj);  /* clean */
}

//============================================================+
// END OF FILE                                                 
//============================================================+
?>