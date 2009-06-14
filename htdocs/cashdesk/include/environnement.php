<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
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

// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();
dol_syslog("Start session name=".$sessionname." Session id()=".session_id().", _SESSION['dol_login']=".$_SESSION["dol_login"].", ".ini_get("session.gc_maxlifetime"));


$conf_db_type = $dolibarr_main_db_type;

// Parametres de connexion a la base
$conf_db_host = $dolibarr_main_db_host;
$conf_db_user = $dolibarr_main_db_user;
$conf_db_pass = $dolibarr_main_db_pass;
$conf_db_base = $dolibarr_main_db_name;

// Parametres generaux
$conf_url_racine = $dolibarr_main_url_root.'/cashdesk';

// Identifiant unique correspondant au tiers generique pour la vente
$conf_fksoc = $conf->global->CASHDESK_ID_THIRDPARTY;
// Identifiant unique correspondant au compte caisse / liquide
$conf_fkaccount = $conf->global->CASHDESK_ID_BANKACCOUNT > 0?$conf->global->CASHDESK_ID_BANKACCOUNT:$_SESSION["CASHDESK_ID_BANKACCOUNT"];
// Identifiant unique correspondant a l'entrepot associe a la caisse
$conf_fkentrepot = $conf->global->CASHDESK_ID_WAREHOUSE > 0?$conf->global->CASHDESK_ID_WAREHOUSE:$_SESSION["CASHDESK_ID_WAREHOUSE"];

// Check if setup ok
if (empty($conf_fksoc))      dol_print_error("Setup of CashDesk module not complete. Third party not defined.");
if ($conf->bank->enabled && empty($conf_fkaccount))  dol_print_error("Setup of CashDesk module not complete. Bank account not defined.");
if ($conf->stock->enabled && empty($conf_fkentrepot)) dol_print_error("Setup of CashDesk module not complete. Warehous not defined.");

// Parametres d'affichage
$conf_taille_listes = 200;	// Nombre max de lignes a afficher dans les listes
$conf_nbr_car_listes = 60;	// Nombre max de caracteres par ligne dans les listes

$new_conf_db_type=$conf_db_type;
if (eregi('mysql',$new_conf_db_type)) $new_conf_db_type='Mysql';

require ('classes/'.$new_conf_db_type.'.class.php');
//$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);
$sql=$db;
?>
