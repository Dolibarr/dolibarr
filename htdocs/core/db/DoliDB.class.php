<?php
/*
 * Copyright (C) 2013 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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

/**
 * \file htdocs/core/db/dolidb.class.php
 * \brief Class file to manage Dolibarr database access
 */

/**
 * Class to manage Dolibarr database access
 */
abstract class DoliDB
{
    //! Database handler
    public $db;
    //! Database type
    public $type;
    //! Database label
    static $label;
    //! Charset used to force charset when creating database
    public $forcecharset;
    //! Collate used to force collate when creating database
    public $forcecollate;
    //! Min database version
    static $versionmin;
    //! Resultset of last query
    private $_results;
    //! 1 if connected, else 0
    public $connected;
    //! 1 if database selected, else 0
    public $database_selected;
    //! Selected database name
    public $database_name;
    //! Database username
    public $database_user;
    //! >=1 if a transaction is opened, 0 otherwise
    public $transaction_opened;
    //! Last successful query
    public $lastquery;
    //! Last failed query
    public $lastqueryerror;
    //! Last error message
    public $lasterror;
    //! Last error number
    public $lasterrno;

    public $ok;
    public $error;
}

