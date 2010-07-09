<?
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
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

$sql = "select nom from llx_societe s ";
$sql.= "left join llx_socpeople sp on sp.fk_soc=s.rowid";
$sql.= " where s.tel='".addslashes($phone)."' or sp.phone='".addslashes($phone)."' or sp.phone_perso='".addslashes($phone)."' or sp.phone_mobile='".addslashes($phone)."'";
//$sql.= " AND entity=".$conf->entity;
$sql.= $db->plimit(1);

dol_syslog('cidlookup search information with phone '.$phone, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$row = $db->fetch_object($resql);
	if ($row)
	{
		$found = $row->nom;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db,'Error');
}

echo $found;

?>
