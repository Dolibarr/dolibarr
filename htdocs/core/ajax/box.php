<?php
/* Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2007-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/ajax/box.php
 *       \brief      File to return Ajax response on Box move or close
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOREQUIREHOOK'))  define('NOREQUIREHOOK','1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

$boxid=GETPOST('boxid','int');
$boxorder=GETPOST('boxorder');
$userid=GETPOST('userid');
$zone=GETPOST('zone','int');
$userid=GETPOST('userid','int');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Add a box
if ($boxid > 0 && $zone !='' && $userid > 0)
{
	$tmp=explode('-',$boxorder);
	$nbboxonleft=substr_count($tmp[0],',');
	$nbboxonright=substr_count($tmp[1],',');
	print $nbboxonleft.'-'.$nbboxonright;
	if ($nbboxonleft > $nbboxonright) $boxorder=preg_replace('/B:/','B:'.$boxid.',',$boxorder);    // Insert id of new box into list
    else $boxorder=preg_replace('/^A:/','A:'.$boxid.',',$boxorder);    // Insert id of new box into list
}

// Registering the location of boxes after a move
if ($boxorder && $zone != '' &&  $userid > 0)
{
	// boxorder value is the target order: "A:idboxA1,idboxA2,A-B:idboxB1,idboxB2,B"
	dol_syslog("AjaxBox boxorder=".$boxorder." zone=".$zone." userid=".$userid, LOG_DEBUG);

	$result=InfoBox::saveboxorder($db,$zone,$boxorder,$userid);
}

