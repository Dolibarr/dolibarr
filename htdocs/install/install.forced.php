<?php
/* Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/** @var bool Hide PHP informations */
#$force_install_nophpinfo = true;
$force_install_nophpinfo = false;

/** @var int 1 = Lock and hide environment variables, 2 = Lock all set variables */
#$force_install_noedit = 2;

/** @var string Information message */
$force_install_message = 'Welcome to your Dolibarr install';

/** @var string Data root absolute path (documents folder) */
$force_install_main_data_root = null;

/** @var bool Force HTTPS */
#$force_install_mainforcehttps = true;
$force_install_mainforcehttps = false;

/** @var string Database name */
$force_install_database = 'test';

/** @var string Database driver (mysql|mysqli|pgsql|mssql|sqlite|sqlite3) */
$force_install_type = 'pgsql';

/** @var string Database server host */
$force_install_dbserver = 'localhost';

/** @var int Database server port */
$force_install_port = 5432;

/** @var string Database tables prefix */
$force_install_prefix = 'llx_';

/** @var bool Force database creation */
#$force_install_createdatabase = true;
$force_install_createdatabase = false;

/** @var string Database username */
$force_install_databaselogin = 'aikhan';

/** @var string Database password */
$force_install_databasepass = '';

/** @var bool Force database user creation */
$force_install_createuser = false;

/** @var string Database root username */
#$force_install_databaserootlogin = 'root';
#$force_install_databaserootlogin = 'root';

/** @var string Database root password */
#$force_install_databaserootpass = '';

/** @var string Dolibarr super-administrator username */
#$force_install_dolibarrlogin = 'admin';
$force_install_dolibarrlogin = 'a';

/** @var bool Force install locking */
#$force_install_lockinstall = true;
$force_install_lockinstall = false;

/** @var string Enable module(s) (Comma separated class names list) */
$force_install_module = 'modSociete,modFournisseur,modFacture';
