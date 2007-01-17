<?php
//
// Pear DB LDAP2 - Database independent query interface definition
// for PHP's LDAP extension with protocol version 2.
//
// Copyright (C) 2002-2003 Piotr Roszatycki <dexter@debian.org>
//
// Based on ldap.php
// Copyright (C) 2002 Ludovico Magnocavallo <ludo@sumatrasolutions.com>
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
require_once PEAR_PATH."/DB/common.php";

/**
 * LDAP2 DB interface class
 *
 * DB_ldap2 extends DB_common to provide DB compliant
 * access to LDAP servers with protocol version 2.
 *
 * @author Piotr Roszatycki <dexter@debian.org>
 * @version $Revision$
 * @package DB_ldap2
 */

class DB_ldap2 extends DB_common
{
    // {{{ properties

    /**
     * LDAP connection handler
     * @access private
     */
    var $connection;

    /**
     * list of actions which manipulate data
     * @access private
     */
    var $action_manip = array(
        'add', 'compare', 'delete', 'modify', 'mod_add', 'mod_del',
        'mod_replace', 'rename');

    /**
     * list of parameters for search actions
     * @access private
     */
    var	$param_search = array(
        'action', 'base_dn', 'attributes', 'attrsonly', 'sizelimit',
        'timelimit', 'deref','sort');

    /**
     * list of parameters for modify actions
     * @access private
     */
    var	$param_modify = array(
        'action', 'attribute', 'value', 'newrdn', 'newparent',
        'deleteoldrdn');

    /**
     * default parameters for query
     * @access private
     */
    var $param = array();

    /**
     * parameters for last query
     * @access private
     */
    var $last_param = array();

    /**
     * array contained row counters for last query
     * @access private
     */
    var $row = array();

    /**
     * array contained number of rows for last query
     * @access private
     */
    var $num_rows = array();

    /**
     * array contained entry handlers for last query
     * @access private
     */
    var $entry = array();

    /**
     * array contained number of rows affected by last query
     * @access private
     */
    var $affected = 0;

    // }}}
    // {{{ constructor

    /**
     * Constructor, calls DB_common constructor
     *
     * @see DB_common::DB_common()
     */
    function DB_ldap2()
    {
        $this->DB_common();
        $this->phptype = 'ldap2';
        $this->dbsyntax = 'ldap2';
        $this->features = array(
            'prepare'       => false,
            'pconnect'      => false,
            'transactions'  => false,
            'limit'         => false
        );
        $this->errorcode_map = array(
            0x10 => DB_ERROR_NOSUCHFIELD,               // LDAP_NO_SUCH_ATTRIBUTE
            0x11 => DB_ERROR_NOSUCHFIELD,               // LDAP_UNDEFINED_TYPE
            0x12 => DB_ERROR_CONSTRAINT,                // LDAP_INAPPROPRIATE_MATCHING
            0x13 => DB_ERROR_CONSTRAINT,                // LDAP_CONSTRAINT_VIOLATION
            0x14 => DB_ERROR_ALREADY_EXISTS,            // LDAP_TYPE_OR_VALUE_EXISTS
            0x15 => DB_ERROR_INVALID,                   // LDAP_INVALID_SYNTAX
            0x20 => DB_ERROR_NOSUCHTABLE,               // LDAP_NO_SUCH_OBJECT
            0x21 => DB_ERROR_NOSUCHTABLE,               // LDAP_ALIAS_PROBLEM
            0x22 => DB_ERROR_INVALID,                   // LDAP_INVALID_DN_SYNTAX
            0x23 => DB_ERROR_INVALID,                   // LDAP_IS_LEAF
            0x24 => DB_ERROR_INVALID,                   // LDAP_ALIAS_DEREF_PROBLEM
            0x30 => DB_ERROR_ACCESS_VIOLATION,          // LDAP_INAPPROPRIATE_AUTH
            0x31 => DB_ERROR_ACCESS_VIOLATION,          // LDAP_INVALID_CREDENTIALS
            0x32 => DB_ERROR_ACCESS_VIOLATION,          // LDAP_INSUFFICIENT_ACCESS
            0x40 => DB_ERROR_MISMATCH,                  // LDAP_NAMING_VIOLATION
            0x41 => DB_ERROR_CONSTRAINT,                // LDAP_OBJECT_CLASS_VIOLATION
            0x44 => DB_ERROR_ALREADY_EXISTS,            // LDAP_ALREADY_EXISTS
            0x51 => DB_ERROR_CONNECT_FAILED,            // LDAP_SERVER_DOWN
            0x57 => DB_ERROR_SYNTAX                     // LDAP_FILTER_ERROR
        );
    }
    
    // }}}
    // {{{ connect()

    /**
     * Connect and bind to LDAPv2 server with either anonymous
     * or authenticated bind depending on dsn info
     *
     * The format of the supplied DSN:
     *
     *  ldap2://binddn:bindpw@host:port/basedn
     *
     * I.e.:
     *
     *  ldap2://uid=dexter,ou=People,dc=example,dc=net:secret@127.0.0.1/dc=example,dc=net
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param boolean $persistent kept for interface compatibility
     * @return int DB_OK if successfully connected.
     * A DB error code is returned on failure.
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('ldap')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

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
            'sort' =>       ''
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
    // {{{ disconnect()

    /**
     * Unbinds from LDAP server
     *
     * @return int ldap_unbind() return value
     */
    function disconnect()
    {
        $ret = @ldap_unbind($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Performs a request against the LDAP server
     *
     * The type of request depend on $query parameter.  If $query is string,
     * perform simple searching query with filter in $query parameter.
     * If $query is array, the first element of array is filter string
     * (for reading operations) or data array (for writing operations).
     * Another elements of $query array are query parameters which overrides
     * the default parameters.
     * 
     * The following parameters can be passed for search queries:<br />
     * <li />base_dn
     * <li />attributes - array, the attributes that shall be returned
     * <li />attrsonly
     * <li />sizelimit - integer, the max number of results to be returned
     * <li />timelimit - integer, the timelimit after which to stop searching
     * <li />deref - 
     * <li/>sort - string, which tells the attribute name by which to sort
     *  
     *
     * I.e.:
     * <code>
     * // search queries 
     * // 'base_dn' is not given, so the one passed to connect() will be used
     * $db->simpleQuery("uid=dexter");
     *
     * // base_dn is given
     * // the 'attributes' key defines the attributes that shall be returned
     * // 'sort' defines the sort order of the data
     * $db->simpleQuery(array(
     *      'uid=dexter',
     *      'base_dn' => 'ou=People,dc=example,dc=net',
     *      'attributes'=>array('dn','o','l'),
     *      'sort'=>'o'
     * ));
     *
     * // use this kind of query for adding data
     * $db->simpleQuery(
     *      array(
     *          array(
     *              'dn' => 'cn=Piotr Roszatycki,dc=example,dc=com',
     *              'objectClass' => array('top', 'person'),
     *              'cn' => 'Piotr Roszatycki',
     *              'sn' => 'Roszatycki'),
     *          'action' => 'add'
     * ));
     *
     * @param mixed $query the ldap query
     * @return int result from LDAP function for failure queries,
     * DB_OK for successful queries or DB Error object if wrong syntax
     */
    function simpleQuery( $query)
    {
        if (is_array($query)) {
            $last_param = $query;
            $query = (isset($query[0]) ? $query[0] : 'objectClass=*');
            unset($last_param[0]);
        } else {
            $last_param = array();
        }
        $action = (isset($last_param['action']) ? $last_param['action'] : $this->param['action']);
        // check if the given action is a valid modifier action, i.e. 'search'
        if (!$this->isManip($action)) {
            $this->last_param = $this->param;
            foreach($this->param_search as $k) {
                if (isset($last_param[$k])) {
                    $this->last_param[$k] = $last_param[$k];
                }
            }
            extract($this->last_param);
            // double escape char for filter: '(o=Przedsi\C4\99biorstwo)' => '(o=Przedsi\\C4\\99biorstwo)'
            $this->last_query = $query;
            $filter = str_replace('\\', '\\\\', $query);
            switch ($action) {
                // ldap_search, *list, *read have the same arguments
                case 'search':
                case 'list':
                case 'read':
                    $ldap_action = "ldap_$action";
                    $result = @$ldap_action($this->connection, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
                    break;
                default:
                    return $this->ldapRaiseError(DB_ERROR_SYNTAX);
            }
            if (!$result) {
                return $this->ldapRaiseError();
            }
            $this->row[$result] = 0; // reset the row counter.
            $numrows = $this->numrows($result);
            if (is_object($numrows)) {
                return $numrows;
            }
            $this->num_rows[$result] = $numrows;
            $this->affected = 0;
            if ($sort) {
                ldap_sort($this->connection,$result,$sort);
            }
            return $result;
        } else {
            // If first argument is an array, it contains the entry with DN.
            if (is_array($query)) {
                $entry = $query;
                $dn = $entry['dn'];
                unset($entry['dn']);
            } else {
                $entry = array();
                $dn = $query;
            }
            $this->last_param = $this->param;
            foreach($this->param_modify as $k) {
                if (isset($last_param[$k])) {
                    $this->last_param[$k] = $last_param[$k];
                }
            }
            extract($this->last_param);
            $this->last_query = $query;
            switch ($action) {
                case 'add':
                case 'modify':
                case 'mod_add':
                case 'mod_del':
                case 'mod_replace':
                    $ldap_action = "ldap_$action";
                    $result = @$ldap_action($this->connection, $dn, $entry);
                    break;
                case 'compare':
                    $result = @ldap_compare($this->connection, $dn, $attribute, $value);
                    break;
                case 'delete':
                    $result = @ldap_delete($this->connection, $dn);
                    break;
                case 'rename':
                    $result = @ldap_rename($this->connection, $dn, $newrdn, $newparent, $deleteoldrdn);
                    break;
                default:
                    return $this->ldapRaiseError(DB_ERROR_SYNTAX);
            }
            if (!$result) {
                return $this->ldapRaiseError();
            }
            $this->affected = 1;
            return DB_OK;
        }
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal ldap result pointer to the next available result
     *
     * @param a valid ldap result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return @ldap_next_entry($result);
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch and return a row of data (it uses fetchInto for that)
     * @param $result LDAP result identifier
     * @param   $fetchmode  format of fetched row array
     * @param   $rownum     the absolute row number to fetch
     *
     * @return  array   a row of data, or false on error
     */
    function fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum=null)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $res = $this->fetchInto($result, $arr, $fetchmode, $rownum);
        if ($res !== DB_OK) {
            return $res;
        }
        return $arr;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * DB_FETCHMODE_ORDERED returns a flat array of values
     * ("value", "val1", "val2").
     *
     * DB_FETCHMODE_ASSOC returns an array of structuralized data
     * ("field_name1" => "value", "field_name2" => array("val1", "val2")).
     *
     * @param $result PostgreSQL result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param $rownum the row number to fetch
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum=null)
    {
	if ($rownum !== null) {
	    // $rownum is unimplemented, yet
	    return null;
	}
        $rownum = $this->row[$result];
        if ($rownum >= $this->num_rows[$result]) {
            return null;
        }
	if ($rownum == 0) {
	    $entry = @ldap_first_entry($this->connection, $result);
	} else {
	    $entry = @ldap_next_entry($this->connection, $this->entry[$result]);
	}
	$this->entry[$result] = $entry;
        if (!$entry) {
            $errno = ldap_errno($this->connection);
            if (!$err) {
                return null;
            }
            return $this->ldapRaiseError();
        }

	switch ($fetchmode) {
	case DB_FETCHMODE_ORDERED:
    	    $arr = array();
	    if (!($attr = @ldap_get_attributes($this->connection, $entry))) {
        	$errno = ldap_errno($this->connection);
        	if (!$err) {
            	    return null;
        	}
        	return $this->ldapRaiseError();
    	    }
	    if ($attr["count"] == 0) {
		if (!($arr[] = @ldap_get_dn($this->connection, $entry))) {
        	    $errno = ldap_errno($this->connection);
        	    if (!$err) {
            		return null;
        	    }
        	    return $this->ldapRaiseError();
		}
    	    } else {
		while (list($attr_name, $attr_val) = each($attr)) {
	    	    if ($attr_val["count"] == 1) {
	    	        $arr[] = $attr_val[0];
		    } elseif ($attr_val["count"] > 1) {
			for ($i=0; $i<$attr_val["count"]; $i++) {
		    	    $arr[] = $attr_val[$i];
			}
		    }
		}
	    }
	    break;
	case DB_FETCHMODE_ASSOC:
    	    $arr = array();
	    if (!($arr["dn"] = @ldap_get_dn($this->connection, $entry))) {
        	$errno = ldap_errno($this->connection);
        	if (!$err) {
            	    return null;
        	}
        	return $this->ldapRaiseError();
    	    }
	    if (!($attr = @ldap_get_attributes($this->connection, $entry))) {
        	$errno = ldap_errno($this->connection);
        	if (!$err) {
            	    return null;
        	}
        	return $this->ldapRaiseError();
    	    }
	    while (list($attr_name, $attr_val) = each($attr)) {
	        if ($attr_val["count"] == 1) {
	    	    $arr[strtolower($attr_name)] = $attr_val[0];
		} elseif ($attr_val["count"] > 1) {
		    for ($i=0; $i<$attr_val["count"]; $i++) {
		        $arr[strtolower($attr_name)][$i] = $attr_val[$i];
		    }
		}
	    }
	    break;
	}

        $this->row[$result] = ++$rownum;
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result int LDAP result identifier or DB statement identifier
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return @ldap_free_result($result);
        }
        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }
        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);
        unset($this->prepared_queries[(int)$result]);
        unset($this->row[(int)$result]);
        unset($this->num_rows[(int)$result]);
        unset($this->entry[(int)$result]);
        $this->affected = 0;
        $this->last_param = $this->param;
        $this->attributes = null;
        $this->sorting = '';
        return true;
    }

    // }}}
    // {{{ quote()

    /**
    * Quote the given string so it can be safely used within string delimiters
    * in a query.
    *
    * @param $string mixed Data to be quoted
    *
    * @return mixed "NULL" string, quoted string or original data
    */
    function quote($str = null)
    {
        $str = str_replace(array('\\', '"'), array('\\\\', '\\"'), $str);
        return $str;
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set. This function
     * is used only for compatibility reasons.
     *
     * @param $result resource LDAP result identifier
     *
     * @return int DB_ERROR_NOT_CAPABLE error code
     */
    function numCols($result)
    {
        return $this->ldapRaiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result resource LDAP result identifier
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @ldap_count_entries($this->connection, $result);
        if ($rows === null) {
            return $this->ldapRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @return int native LDAP error code
     */
    function errorNative()
    {
        return ldap_error($this->connection);
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the last query.
     * if the last query was a select, returns 0.
     *
     * @return int number of rows affected by the last query or DB_ERROR
     */
    function affectedRows()
    {
        return $this->affected;
    }

    // }}}
    // {{{ getTables()

    /**
     * @deprecated
     */
    function getTables()
    {
        return $this->ldapRaiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ getListOf()

    /**
     * Returns the query needed to get some backend info. This function is
     * used only for compatibility reasons.
     *
     * @return int DB_ERROR_NOT_CAPABLE error code
     */
    function getListOf($type)
    {
        return $this->ldapRaiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ isManip()

    /**
     * Tell whether an action is a data manipulation action (add, compare,
     * delete, modify, mod_add, mod_del, mod_replace, rename)
     *
     * @param string $action the query
     *
     * @return boolean whether $query is a data manipulation action
     */
    function isManip($action)
    {
        return(in_array($action, $this->action_manip));
    }

    // }}}
    // {{{ base()

    /**
     * @deprecated
     */
    function base($base_dn = null)
    {
        $this->q_base_dn = ($base_dn !== null) ? $base_dn : null;
        return true;
    }

    // }}}
    // {{{ ldapSetBaseDN()

    /**
     * @deprecated
     */
    function ldapSetBaseDN($base_dn = null)
    {
        $this->base_dn = ($base_dn !== null) ? $base_dn : $this->d_base_dn;
        $this->q_base_dn = '';
        return true;
    }

    // }}}
    // {{{ ldapSetAction()

    /**
     * @deprecated
     */
    function ldapSetAction($action = 'search')
    {
        $this->action = $action;
        $this->q_action = '';
        return true;
    }

    // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.
     *
     * LDAP provides transactions for only one entry and we need to
     * prevent race condition. If unique value before and after modify
     * aren't equal then wait and try again.
     *
     * @param string $seq_name the sequence name
     * @param bool $ondemand whether to create the sequence on demand
     *
     * @return a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
	$seq_dn = $this->getSequenceName($seq_name);
        $repeat = 0;
        do {
            // Get the sequence entry
	    $this->expectError(DB_ERROR_NOSUCHTABLE);
            $data = $this->getRow(array('objectClass=*', 'action'=>'read', 'base_dn'=>$seq_dn));
	    $this->popExpect();

            if (DB::isError($data)) {
                if ($ondemand && $repeat == 0
                && $data->getCode() == DB_ERROR_NOSUCHTABLE) {
                // Try to create sequence and repeat
                    $repeat = 1;
                    $data = $this->createSequence($seq_name);
                    if (DB::isError($data)) {
                        return $this->ldapRaiseError($data);
                    }
                } else {
                    // Other error
                    return $this->ldapRaiseError($data);
                }
            } else {
                // Increment sequence value
                $data['cn']++;
                // Unique identificator of transaction
                $seq_unique = mt_rand();
                $data['uid'] = $seq_unique;
                // Modify the LDAP entry
                $data = $this->simpleQuery(array($data, 'action'=>'modify'));
                if (DB::isError($data)) {
                    return $this->ldapRaiseError($data);
                }
                // Get the entry and check if it contains our unique value
        	$data = $this->getRow(array('objectClass=*', 'action'=>'read', 'base_dn'=>$seq_dn));
                if (DB::isError($data)) {
                    return $this->ldapRaiseError($data);
                }
                if ($data['uid'] != $seq_unique) {
                    // It is not our entry. Wait a little time and repeat
                    sleep(1);
                    $repeat = 1;
                } else {
                    $repeat = 0;
                }
            }
        } while ($repeat);

        if (DB::isError($data)) {
            return $data;
        }
        return $data['cn'];
    }

    // }}}
    // {{{ createSequence()

    /**
     * Create the sequence
     *
     * The sequence entry is based on core schema with extensibleObject,
     * so it should work with any LDAP server which doesn't check schema
     * or supports extensibleObject object class.
     *
     * Format of the entry:
     *
     *  dn: $seq_dn
     *  objectClass: top
     *  objectClass: extensibleObject
     *  sn: $seq_id
     *  cn: $seq_value
     *  uid: $seq_uniq
     *
     * @param string $seq_name the sequence name
     *
     * @return mixed DB_OK on success or DB error on error
     */
    function createSequence($seq_name)
    {
	$seq_dn = $this->getSequenceName($seq_name);

        // Create the sequence entry
        $data = array(
            'dn' => $seq_dn,
            'objectclass' => array('top', 'extensibleObject'),
            'sn' => $seq_name,
            'cn' => 0,
            'uid' => 0
        );

        // Add the LDAP entry
        $data = $this->simpleQuery(array($data, 'action'=>'add'));
        return $data;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Drop a sequence
     *
     * @param string $seq_name the sequence name
     *
     * @return mixed DB_OK on success or DB error on error
     */
    function dropSequence($seq_name)
    {
        $seq_dn = $this->getSequenceName($seq_name);

        // Delete the sequence entry
        $data = array(
            'dn' => $seq_dn,
        );
        $data = $this->simpleQuery(array($data, 'action'=>'delete'));
        return $data;
    }

    // }}}
    // {{{ ldapRaiseError()

    /**
     * Generate error message for LDAP errors.
     *
     * @param int $errno error number
     *
     * @return mixed DB_OK on success or DB error on error
     */
    function ldapRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(ldap_errno($this->connection));
        }
        if ($this->last_param['action'] !== null) {
            return $this->raiseError($errno, null, null,
                        sprintf('%s base="%s" filter="%s"',
                            $this->last_param['action'] ? $this->last_param['action'] : $this->param['action'], 
                            $this->last_param['base_dn'] ? $this->last_param['base_dn'] : $this->param['base_dn'], 
                            is_array($this->last_query) ? "" : $this->last_query
                        ),
                        $errno == @ldap_error($this->connection)
                    );
        } else {
            return $this->raiseError($errno, null, null, "???",
                        @ldap_error($this->connection));
        }
    }

    // }}}
    // {{{ prepare()


    /**
     * Prepares a query for multiple execution with execute().
     * This behaviour is emulated for LDAP backend.
     * prepare() requires a generic query as an array with special
     * characters (wildcards) as values.
     *
     * Types of wildcards:
     *   ? - a quoted scalar value, i.e. strings, integers
     *   & - requires a file name, the content of the file
     *       insert into the query (i.e. saving binary data
     *       in a db)
     *   ! - value is inserted 'as is'
     *
     * Example:
     *
     *  $sth = $dbh->prepare(
     *      array(
     *  	       array(
     *  	           'dn' => '?',
     *  	           'objectClass' => '?',
     *  	           'cn' => '?',
     *  	           'sn' => '?',
     *  	           'description' => '&'
     *  	       ),
     *          'action' => 'add'
     *      );
     *  );
     *
     *  $sigfile = "/home/dexter/.signature";
     *  $res = $dbh->execute($sth, array(
     *      'cn=Piotr Roszatycki,dc=example,dc=com',
     *      array('top', 'person'),
     *      'Piotr Roszatycki', 'Roszatycki', $sigfile
     *  ));
     *
     * @param mixed the query to prepare
     *
     * @return resource handle for the query
     *
     * @see execute
     */
    function prepare($query)
    {
	if (!is_array($query)) {
	    return parent::prepare($query);
	} elseif (is_array($query) && isset($query[0]) && 
	    !$this->isManip(isset($query['action']) ? $query['action'] : $this->param['action'])
	) {
	    $filter = $query[0];
            $tokens = split("[\&\?\!]", $filter);
            $token = 0;
            $types = array();

            for ($i = 0; $i < strlen($filter); $i++) {
                switch ($filter[$i]) {
                    case '?':
                        $types[$token++] = DB_PARAM_SCALAR;
                        break;
                    case '&':
                        $types[$token++] = DB_PARAM_OPAQUE;
                        break;
                    case '!':
                        $types[$token++] = DB_PARAM_MISC;
                        break;
                }
            }

            $this->prepare_tokens[] = &$tokens;
            end($this->prepare_tokens);

            $k = key($this->prepare_tokens);
            $this->prepare_types[$k] = $types;
            $this->prepared_queries[$k] = &$query;

            return $k;
        } elseif(is_array($query) && isset($query[0]) && is_array($query[0])) {
            $tokens = array();
            $types = array();

	    foreach ($query[0] as $k=>$v) {
		$tokens[$k] = $v;
                switch ($v) {
                    case '?':
                        $types[$k] = DB_PARAM_SCALAR;
                        break;
                    case '&':
                        $types[$k] = DB_PARAM_OPAQUE;
                        break;
                    case '!':
                        $types[$k] = DB_PARAM_MISC;
                        break;
		    default:
			$types[$k] = null;
                }
	    }

            $this->prepare_tokens[] = &$tokens;
            end($this->prepare_tokens);

            $k = key($this->prepare_tokens);
            $this->prepare_types[$k] = $types;
            $this->prepared_queries[$k] = &$query;

            return $k;
	} else {
	    return parent::prepare($query);
	}
    }

    // }}}
    // {{{ executeEmulateQuery()

    /**
     * Emulates the execute statement.
     *
     * @param resource $stmt query handle from prepare()
     * @param array    $data numeric array containing the
     *                       data to insert into the query
     *
     * @return mixed an array containing the real query run when emulating
     * prepare/execute.  A DB error code is returned on failure.
     *
     * @see execute()
     */
    function executeEmulateQuery($stmt, $data = false)
    {
	$query = &$this->prepared_queries[$stmt];

	if (!is_array($query)) {
	    return parent::executeEmulateQuery($stmt, $data);
	} elseif (is_array($query) && isset($query[0]) && 
	    !$this->isManip(isset($query['action']) ? $query['action'] : $this->param['action'])
	) {
	    $p = &$this->prepare_tokens;

    	    if (!isset($this->prepare_tokens[$stmt]) ||
        	!is_array($this->prepare_tokens[$stmt]) ||
        	!sizeof($this->prepare_tokens[$stmt]))
    	    {
        	return $this->raiseError(DB_ERROR_INVALID);
    	    }

            $qq = &$this->prepare_tokens[$stmt];
            $qp = sizeof($qq) - 1;

            if ((!$data && $qp > 0) ||
                (!is_array($data) && $qp > 1) ||
                (is_array($data) && $qp > sizeof($data)))
            {
                $this->last_query = $this->prepared_queries[$stmt];
                return $this->raiseError(DB_ERROR_NEED_MORE_DATA);
            }

	    $realquery = $query;
            $realquery[0] = $qq[0];
            for ($i = 0; $i < $qp; $i++) {
                $type = $this->prepare_types[$stmt][$i];
                if ($type == DB_PARAM_OPAQUE) {
                    if (is_array($data)) {
                        $fp = fopen($data[$i], 'r');
                    } else {
                        $fp = fopen($data, 'r');
                    }

                    $pdata = '';

                    if ($fp) {
                        while (($buf = fread($fp, 4096)) != false) {
                            $pdata .= $buf;
                        }
                    }
                } else {
                    if (is_array($data)) {
                        $pdata = &$data[$i];
                    } else {
                        $pdata = &$data;
                    }
                }

                $realquery[0] .= ($type != DB_PARAM_MISC) ? $this->quote($pdata) : $pdata;
                $realquery[0] .= $qq[$i + 1];
            }

            return $realquery;

        } elseif(is_array($query) && isset($query[0]) && is_array($query[0])) {

	    $p = &$this->prepare_tokens;

    	    if (!isset($this->prepare_tokens[$stmt]) ||
        	!is_array($this->prepare_tokens[$stmt]) ||
        	!sizeof($this->prepare_tokens[$stmt]))
    	    {
        	return $this->raiseError(DB_ERROR_INVALID);
    	    }

            $qq = &$this->prepare_tokens[$stmt];
	    $realquery = $query;

	    $i = 0;
	    foreach ($qq as $k=>$v) {
                $type = $this->prepare_types[$stmt][$k];
		    
                if ($type !== null) {

        	    if (!isset($data) ||
            		(is_array($data) && !isset($data[$i]))
		    ) {
            		$this->last_query = $this->prepared_queries[$stmt];
            		return $this->raiseError(DB_ERROR_NEED_MORE_DATA);
        	    }

        	    if ($type == DB_PARAM_OPAQUE) {
                	if (is_array($data)) {
                    	    $fp = fopen($data[$i++], 'r');
                	} else {
                    	    $fp = fopen($data, 'r');
                	}

                	$pdata = '';

                	if ($fp) {
                    	    while (($buf = fread($fp, 4096)) != false) {
                        	$pdata .= $buf;
                    	    }
                	}
            	    } elseif ($type !== null) {
                	if (is_array($data)) {
                    	    $pdata = &$data[$i++];
                	} else {
                    	    $pdata = &$data;
                	}
            	    }
		
		    $realquery[0][$k] = $pdata;
		}
	    }

            return $realquery;

	} else {
	    return parent::executeEmulateQuery($stmt, $data);
	}
    }

    // }}}
    // {{{ ldapSetParam()

    /**
     * Sets the default parameters for query.
     *
     * @param string $param the name of parameter for search actions (action,
     * base_dn, attributes, attrsonly, sizelimit, timelimit, deref) or
     * modify actions (action, attribute, value, newrdn, newparent,
     * deleteoldrdn).
     * @param string $value the value of parameter
     *
     * @return mixed DB_OK on success or DB error on error
     *
     * @see ldapGetParam()
     */
    function ldapSetParam($param, $value)
    {
        if (isset($this->param[$param])) {
            $this->param[$param] = $value;
            return DB_OK;
        }
        return $this->raiseError("unknown LDAP parameter $param");
    }

    // }}}
    // {{{ ldapGetParam()

    /**
     * Gets the default parameters for query.
     *
     * @param string $param the name of parameter for search or modify
     * actions.
     *
     * @return mixed value of parameter on success or DB error on error
     *
     * @see ldapSetParam()
     */
    function ldapGetParam($param)
    {
        if (isset($this->param[$param])) {
            return $this->param[$param];
        }
        return $this->raiseError("unknown LDAP parameter $param");
    }

    // }}}
    // {{{ ldapSetOption()

    /**
     * Sets the value of the given option.
     *
     * @param int $option the specified option
     * @param mixed $newval the value of specified option
     *
     * @return bool DB_OK on success or DB error on error
     *
     * @see ldapGetOption()
     */
    function ldapSetOption($option, $newval)
    {
        if (@ldap_set_option($this->connection, $option, $newval)) {
            return DB_OK;
        }
        return $this->raiseError("failed to set LDAP option");
    }

    // }}}
    // {{{ ldapGetOption()

    /**
     * Gets the current value for given option.
     *
     * @param int $option the specified option
     * @param mixed $retval (reference) the new value of specified option
     *
     * @return bool DB_OK on success or DB error on error
     *
     * @see ldapSetOption()
     */
    function ldapGetOption($option, &$retval)
    {
        if (@ldap_get_option($this->connection, $option, $retval)) {
            return DB_OK;
        }
        return $this->raiseError("failed to get LDAP option");
    }

    // }}}
    // {{{ ldapExplodeDN()

    /**
     * Splits the DN and breaks it up into its component parts.
     * Each part is known as Relative Distinguished Name, or RDN.
     *
     * @param string $dn the DN to split
     * @param int $with_attrib 0 to get RDNs with the attributes
     * or 1 to get only values.
     *
     * @return array an array of all those components
     */
    function ldapExplodeDN($dn, $with_attrib = 0)
    {
        $arr = ldap_explode_dn($dn, $with_attrib ? 1 : 0);
        unset($arr['count']);
        return $arr;
    }

}

?>
