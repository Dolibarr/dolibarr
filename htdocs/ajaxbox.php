<?php
/* Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/ajaxbox.php
        \brief      Fichier de reponse sur evenement Ajax
        \version    $Revision$
*/

require('master.inc.php');
require_once(DOL_DOCUMENT_ROOT."/boxes.php");

// Enregistrement de la position des boxes
if((isset($_GET['boxorder']) && !empty($_GET['boxorder'])) && (isset($_GET['boxid']) && !empty($_GET['boxid'])) && (isset($_GET['userid']) && !empty($_GET['userid'])))
{
	$infobox=new InfoBox($db);
	
	dolibarr_syslog("InfoBox::Ajax.Request list=".$_GET['boxorder']." boxid=".$_GET['boxid']." userid=".$_GET['userid'], LOG_DEBUG);
	
	$boxid = explode(',',$_GET['boxid']);
	$boxorder = explode(',',$_GET['boxorder']);
	
	$infobox->saveboxorder("0",$boxid,$boxorder,$_GET['userid']);

}


?>
