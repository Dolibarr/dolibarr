<?php
/* Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024       Yann Le Doaré      <services@linuxconsole.org>
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

/** @var boolean	$force_install_nophpinfo 		Hide PHP information */
$force_install_nophpinfo = true;

/** @var int	$force_install_noedit 				1 = Lock and hide environment variables, 2 = Lock all set variables */
$force_install_noedit = 3;

/** @var string	$force_install_message	 			Information message */
$force_install_message = 'Welcome to your Dolibarr Docker install';

/** @var string	$force_install_main_data_root 		Data root absolute path (documents folder) */
$force_install_main_data_root = null;

/** @var boolean	$force_install_mainforcehttps	Force HTTPS */
$force_install_mainforcehttps = true;

/** @var string	$force_install_database				Database name */
$force_install_database = getenv('DOLI_DATABASE', true) ?: getenv('DOLI_DATABASE');

/** @var string $force_install_type					Database driver (mysql|mysqli|pgsql|mssql|sqlite|sqlite3) */
$force_install_type = 'mysqli';

/** @var string $force_install_dbserver				Database server host */
$force_install_dbserver = getenv('DOLI_DB_SERVER', true) ?: getenv('DOLI_DB_SERVER');

/** @var int $force_install_port					Database server port */
$force_install_port = 3306;

/** @var string $force_install_prefix				Database tables prefix */
$force_install_prefix = 'llx_';

/** @var bool $force_install_createdatabase			Force database creation */
$force_install_createdatabase = false;

/** @var string $force_install_databaselogin		Database username */
$force_install_databaselogin = 'root';

/** @var string $force_install_databasepass			Database password */
$force_install_databasepass = getenv('DOLI_ROOT_PASSWORD', true) ?: getenv('DOLI_ROOT_PASSWORD');

/** @var bool $force_install_createuser				Force database user creation */
$force_install_createuser = false;

/** @var string $force_install_databaserootlogin	Database root username */
$force_install_databaserootlogin = 'root';

/** @var string $force_install_databaserootpass		Database root password */
$force_install_databaserootpass = getenv('DOLI_ROOT_PASSWORD', true) ?: getenv('DOLI_ROOT_PASSWORD');

/** @var string $force_install_dolibarrlogin		Dolibarr super-administrator username */
$force_install_dolibarrlogin = 'admin';

/** @var bool $force_install_lockinstall			Force install locking */
$force_install_lockinstall = true;

/** @var string $force_install_module				Enable module(s) (Comma separated class names list) */
$force_install_module = '';
