<?php
/* Copyright (C) 2010 Servitux Servicios Informaticos <info@servitux.es>
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
 *	\file       htdocs/asterisk/cidlookup.php
 *  \brief      Script to search companies names based on incoming calls
 *	\remarks    To use this script, your Asterisk must be compiled with CURL,
 *	            and your dialplan must be something like this:
 *
 * exten => s,1,Set(CALLERID(name)=${CURL(http://IP-DOLIBARR:80/asterisk/cidlookup.php?phone=${CALLERID(num)})})
 *
 *			Change IP-DOLIBARR to the IP address of your dolibarr
 *			server
 *
 */

$phone = $_GET['phone'];

include("../master.inc.php");


// Check parameters
if (empty($phone))
{
	print "Error: Url must be called with parameter phone=phone to search\n";
	exit;
}

$sql = "SELECT nom as name FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.fk_soc = s.rowid";
$sql.= " WHERE s.entity=".$conf->entity;
$sql.= " AND (s.tel='".$db->escape($phone)."'";
$sql.= " OR sp.phone='".$db->escape($phone)."'";
$sql.= " OR sp.phone_perso='".$db->escape($phone)."'";
$sql.= " OR sp.phone_mobile='".$db->escape($phone)."')";
$sql.= $db->plimit(1);

dol_syslog('cidlookup search information with phone '.$phone, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$obj = $db->fetch_object($resql);
	if ($obj)
	{
		$found = $obj->name;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db,'Error');
}

echo $found;

?>
