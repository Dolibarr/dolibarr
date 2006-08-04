<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/webservices.php
        \brief      Fichier point entrée des WebServices Dolibarr
        \version    $Revision$
*/

require_once("./master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP



dolibarr_syslog("Call Dolibarr webservices interfaces");

// Create the soap Object
$s = new soap_server;

// Register a method available for clients
$s->register('getVersions');

// Return the results.
$s->service($HTTP_RAW_POST_DATA);




function getVersions()
{
	dolibarr_syslog("Function: getVersions");
	
	$versions_array=array();
	
	$versions_array['dolibarr']=DOL_VERSION;
	$versions_array['mysql']='NA';
	$versions_array['apache']='NA';
		
	return $versions_array;
}


?>
