<?php
/* Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/ajaxbox.php
 *       \brief      File to return Ajax response on Box move
 *       \version    $Id: ajaxbox.php,v 1.3 2011/07/31 23:45:15 eldy Exp $
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/boxes.php");


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Registering the location of boxes
if((isset($_GET['boxorder']) && !empty($_GET['boxorder'])) && (isset($_GET['userid']) && !empty($_GET['userid'])))
{
	// boxorder value is the target order: "A:idboxA1,idboxA2,A-B:idboxB1,idboxB2,B"
	dol_syslog("AjaxBox boxorder=".$_GET['boxorder']." userid=".$_GET['userid'], LOG_DEBUG);

	$infobox=new InfoBox($db);
	$result=$infobox->saveboxorder("0",$_GET['boxorder'],$_GET['userid']);
}

?>
