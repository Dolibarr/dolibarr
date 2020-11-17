<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier     <jeremie.o@laposte.net>
 * Copyright (C) 2009-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Juanjo Menent 		<jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// This file initializes more variables to already initialized variables with main.inc.php
// So include of this file must be always done after include to main.inc.php

$conf_db_type = $dolibarr_main_db_type;

// Parametres de connexion a la base
$conf_db_host = $dolibarr_main_db_host;
$conf_db_user = $dolibarr_main_db_user;
$conf_db_pass = $dolibarr_main_db_pass;
$conf_db_base = $dolibarr_main_db_name;

// Identifiant unique correspondant au tiers generique pour la vente
$conf_fksoc = (!empty($_SESSION["CASHDESK_ID_THIRDPARTY"])) ? $_SESSION["CASHDESK_ID_THIRDPARTY"] : ($conf->global->CASHDESK_ID_THIRDPARTY > 0 ? $conf->global->CASHDESK_ID_THIRDPARTY : 0);
// Identifiant unique correspondant a l'entrepot a utiliser
$conf_fkentrepot = (!empty($_SESSION["CASHDESK_ID_WAREHOUSE"])) ? $_SESSION["CASHDESK_ID_WAREHOUSE"] : ($conf->global->CASHDESK_ID_WAREHOUSE > 0 ? $conf->global->CASHDESK_ID_WAREHOUSE : 0);
if (!empty($conf->global->CASHDESK_NO_DECREASE_STOCK)) $conf_fkentrepot = 0; // If option to disable the stock decrease is on, we set warehouse id to 0.

// Identifiant unique correspondant au compte caisse / liquide
$conf_fkaccount_cash = (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"])) ? $_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"] : ($conf->global->CASHDESK_ID_BANKACCOUNT_CASH > 0 ? $conf->global->CASHDESK_ID_BANKACCOUNT_CASH : 0);
// Identifiant unique correspondant au compte cheque
$conf_fkaccount_cheque = (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"])) ? $_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"] : ($conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE > 0 ? $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE : 0);
// Identifiant unique correspondant au compte cb
$conf_fkaccount_cb = (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"])) ? $_SESSION["CASHDESK_ID_BANKACCOUNT_CB"] : ($conf->global->CASHDESK_ID_BANKACCOUNT_CB > 0 ? $conf->global->CASHDESK_ID_BANKACCOUNT_CB : 0);
//var_dump($_SESSION);


// View parameters
$conf_taille_listes = (empty($conf->global->PRODUIT_LIMIT_SIZE) ? 1000 : $conf->global->PRODUIT_LIMIT_SIZE); // Number max of lines to show in lists
$conf_nbr_car_listes = 60; // Nombre max de caracteres par ligne dans les listes
