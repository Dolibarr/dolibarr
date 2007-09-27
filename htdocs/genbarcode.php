<?php
/* Copyright (C) 2005-2007 Regis Houssin      <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

/**
   \file       htdocs/genbarcode.php
   \brief      Générateur de codes barres
   \ingroup    barcode
   \version    $Revision$
*/

require_once('master.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/barcode/php-barcode/php-barcode.php');

function getvar($name){
    global $_GET, $_POST;
    if (isset($_GET[$name])) return $_GET[$name];
    else if (isset($_POST[$name])) return $_POST[$name];
    else return false;
}

if (get_magic_quotes_gpc()){
    $code=stripslashes(getvar('code'));
} else {
    $code=getvar('code');
}
if (!$code) $code='123456789012';

barcode_print($code,getvar('encoding'),getvar('scale'),getvar('mode'));

/*
 * call
 * http://........./barcode.php?code=012345678901
 *   or
 * http://........./barcode.php?code=012345678901&encoding=EAN&scale=4&mode=png
 *
 */

?>