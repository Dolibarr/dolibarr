<?php
//
// Pear DB LDAP3 - Database independent query interface definition
// for PHP's LDAP extension with protocol version 3.
//
// Copyright (C) 2002-2003 Piotr Roszatycki <dexter@debian.org>
//
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
//
// $Id$
//

// require_once 'DB/common.php';
// require_once 'DB/ldap2.php';
require_once DOL_DOCUMENT_ROOT."/includes/pear/DB/common.php";
require_once DOL_DOCUMENT_ROOT."/includes/pear/DB/ldap2.php";

/**
 * LDAP3 DB interface class
 *
 * DB_ldap3 extends DB_ldap2 to provide DB compliant
 * access to LDAP servers with protocol version 3.
 *
 * @author Piotr Roszatycki <dexter@debian.org>
 * @version $Revision$
 * @package DB_ldap3
 */

class DB_ldap3 extends DB_ldap2
{
    // {{{ connect()

    /**
     * Connect and bind to LDAPv3 server with either anonymous
     * or authenticated bind depending on dsn info
     *
     * The format of the supplied DSN:
     *
     *  ldap3://binddn:bindpw@host:port/basedn
     *
     * I.e.:
     *
     *  ldap3://uid=dexter,ou=People,dc=example,dc=net:secret@127.0.0.1/dc=example,dc=net
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param boolean $persistent kept for interface compatibility
     * @return int DB_OK if successfully connected.
     * A DB error code is returned on failure.
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('ldap'))
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);

        $this->dsn = $dsninfo;
        $type   = $dsninfo['phptype'];
        $user   = $dsninfo['username'];
        $pw     = $dsninfo['password'];
        $host   = $dsninfo['hostspec'];
        $port   = empty($dsninfo['port']) ? 389 : $dsninfo['port'];

        $this->param = array(
            'action' =>     'search',
            'base_dn' =>    $this->base_dn = $dsninfo['database'],
            'attributes' => array(),
            'attrsonly' =>  0,
            'sizelimit' =>  0,
            'timelimit' =>  0,
            'deref' =>      LDAP_DEREF_NEVER,
            'attribute' =>  '',
            'value' =>      '',
            'newrdn' =>     '',
            'newparent' =>  '',
            'deleteoldrdn'=>false,
            'sort' =>       '',
        );
        $this->last_param = $this->param;
        $this->setOption("seqname_format", "sn=%s," . $dsninfo['database']);
        $this->fetchmode = DB_FETCHMODE_ASSOC;

        if ($host) {
            $conn = @ldap_connect($host, $port);
        } else {
            return $this->raiseError("unknown host $host");
        }
        if (!$conn) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }
        if (!@ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }
        if ($user && $pw) {
            $bind = @ldap_bind($conn, $user, $pw);
        } else {
            $bind = @ldap_bind($conn);
        }
        if (!$bind) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}

}

?>
