<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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

include('../master.inc.php');
require ('include/environnement.php');
require ('classes/Auth.class.php');

$username = $_POST['txtUsername'];
$password = $_POST['pwdPassword'];


$auth = new Auth ($conf_db_host,$conf_db_user, $conf_db_pass, $conf_db_base );

$retour = $auth->verif ($username, $password);

if ( $retour >= 0 ) {

	//		$db = mysql_connect ($conf_db_host,$conf_db_user, $conf_db_pass);
	//		mysql_select_db ($conf_db_base, $db);

	$res=$sql->query (
	"SELECT rowid, name, firstname
			FROM ".MAIN_DB_PREFIX."user
			WHERE login = '".$username."'");

	$ret=array();
	$tab = mysql_fetch_array($res);
	foreach ( $tab as $cle => $valeur )
	{
		$ret[$cle] = $valeur;
	}

	$tab = $ret;

	$_SESSION['uid'] = $tab['rowid'];
	$_SESSION['uname'] = $username;
	$_SESSION['nom'] = $tab['name'];
	$_SESSION['prenom'] = $tab['firstname'];

	header ('Location: affIndex.php?menu=facturation&id=NOUV');

} else {

	header ('Location: index.php?err='.$retour.'&user='.$username);

}

?>
