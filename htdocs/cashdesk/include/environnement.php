<?php
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
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
	ini_set('session.gc_maxlifetime', 3600);
	session_start ();
	
	$conf_db_type = $dolibarr_main_db_type;

	// Paramètres de connexion à la base
	$conf_db_host = $dolibarr_main_db_host;
	$conf_db_user = $dolibarr_main_db_user;
	$conf_db_pass = $dolibarr_main_db_pass;
	$conf_db_base = $dolibarr_main_db_name;

	// Paramètres généraux
	$conf_url_racine = $dolibarr_main_url_root.'/cashdesk';

	// Identifiant unique correspondant au tiers generique pour la vente
	$conf_fksoc = empty($conf->global->CASHDESK_ID_THIRDPARTY)?1:$conf->global->CASHDESK_ID_THIRDPARTY;

	// Identifiant unique correspondant au compte caisse / liquide
	$conf_fkaccount = $conf->global->CASHDESK_ID_BANKACCOUNT > 0?$conf->global->CASHDESK_ID_BANKACCOUNT:$_SESSION["CASHDESK_ID_BANKACCOUNT"];
	// Identifiant unique correspondant à l'entrepôt associé à la caisse
	$conf_fkentrepot = $conf->global->CASHDESK_ID_WAREHOUSE > 0?$conf->global->CASHDESK_ID_WAREHOUSE:$_SESSION["CASHDESK_ID_WAREHOUSE"];

	$conf_fk_account = 2;
	$conf_fkentrepot = 1;
	
	// Paramètres d'affichage
	$conf_taille_listes = 200;	// Nombre max de lignes à afficher dans les listes
	$conf_nbr_car_listes = 60;	// Nombre max de caractères par ligne dans les listes	
	

	require ('classes/'.$conf_db_type.'.class.php');
	$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);
?>
