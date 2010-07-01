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

// TODO Use dolibarr database driver instead of hard coded mysql functions
$phone = $_GET['phone'];
include("../conf/conf.php");
$link = mysql_connect($dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_pass);
$base = mysql_select_db($dolibarr_main_db_name,$link);
$sql = "select nom from llx_societe s left join llx_socpeople sp on sp.fk_soc=s.rowid where s.tel='$phone' or phone='$phone' or phone_perso='$phone' or phone_mobile='$phone' limit 1";
$result = mysql_query($sql);
$row = mysql_fetch_assoc($result);
$found = $row['nom'];
mysql_free_result($result);
echo $found;

?>
