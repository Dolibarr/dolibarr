<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Stig Bakken <ssb@php.net>                                    |
// +----------------------------------------------------------------------+
//
// $Id$
//
// Database independent query interface definition for PHP's MySQL
// extension.
//

//
// XXX legend:
//
// XXX ERRORMSG: The error message from the mysql function should
//               be registered here.
//

require_once DOL_DOCUMENT_ROOT."/includes/pear/DB/common.php";

class DB_mysql extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $num_rows = array();
    var $transaction_opcount = 0;
    var $autocommit = true;
    var $fetchmode = DB_FETCHMODE_ORDERED; /* Default fetch mode */
    var $_db = false;

    // }}}
    // {{{ constructor

    /**
     * DB_mysql constructor.
     *
     * @access public
     */

    function DB_mysql()
    {
        $this->DB_common();
        $this->phptype = 'mysql';
        $this->dbsyntax = 'mysql';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'alter'
        );
        $this->errorcode_map = array(
            1004 => DB_ERROR_CANNOT_CREATE,
            1005 => DB_ERROR_CANNOT_CREATE,
            1006 => DB_ERROR_CANNOT_CREATE,
            1007 => DB_ERROR_ALREADY_EXISTS,
            1008 => DB_ERROR_CANNOT_DROP,
            1046 => DB_ERROR_NODBSELECTED,
            1050 => DB_ERROR_ALREADY_EXISTS,
            1051 => DB_ERROR_NOSUCHTABLE,
            1054 => DB_ERROR_NOSUCHFIELD,
            1062 => DB_ERROR_ALREADY_EXISTS,
            1064 => DB_ERROR_SYNTAX,
            1100 => DB_ERROR_NOT_LOCKED,
            1136 => DB_ERROR_VALUE_COUNT_ON_ROW,
            1146 => DB_ERROR_NOSUCHTABLE,
            1048 => DB_ERROR_CONSTRAINT,
        );
    }

    // }}}

    // {{{ connect()

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $persistent (optional) whether the connection should
     *        be persistent
     * @access public
     * @return int DB_OK on success, a DB error on failure
     */

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('mysql'))
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);

        $this->dsn = $dsninfo;
        if (isset($dsninfo['protocol']) && $dsninfo['protocol'] == 'unix') {
            $dbhost = ':' . $dsninfo['socket'];
        } else {
            $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
            if (!empty($dsninfo['port'])) {
                $dbhost .= ':' . $dsninfo['port'];
            }
        }
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];

        $connect_function = $persistent ? 'mysql_pconnect' : 'mysql_connect';

        if ($dbhost && $user && $pw) {
            $conn = @$connect_function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $conn = @$connect_function($dbhost, $user);
        } elseif ($dbhost) {
            $conn = @$connect_function($dbhost);
        } else {
            $conn = false;
        }
        if (empty($conn)) {
            if (($err = @mysql_error()) != '') {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, $err);
            } elseif (empty($php_errormsg)) {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED);
            } else {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, $php_errormsg);
            }
        }

        if ($dsninfo['database']) {
            if (!@mysql_select_db($dsninfo['database'], $conn)) {
               switch(mysql_errno($conn)) {
                        case 1049:
                            return $this->raiseError(DB_ERROR_NOSUCHDB, null, null,
                                                     null, mysql_error($conn));
                            break;
                        case 1044:
                             return $this->raiseError(DB_ERROR_ACCESS_VIOLATION, null, null,
                                                      null, mysql_error($conn));
                            break;
                        default:
                            return $this->raiseError(DB_ERROR, null, null,
                                                     null, mysql_error($conn));
                            break;

                    }
            }
            // fix to allow calls to different databases in the same script
            $this->_db = $dsninfo['database'];
        }

        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        $ret = mysql_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to MySQL and return the results as a MySQL resource
     * identifier.
     *
     * @param the SQL query
     *
     * @access public
     *
     * @return mixed returns a valid MySQL result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error is
     * returned on failure.
     */
    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        if ($this->_db) {
            if (!@mysql_select_db($this->_db, $this->connection)) {
                return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
            }
        }
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @mysql_query('SET AUTOCOMMIT=0', $this->connection);
                $result = @mysql_query('BEGIN', $this->connection);
                if (!$result) {
                    return $this->mysqlRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @mysql_query($query, $this->connection);
        if (!$result) {
            return $this->mysqlRaiseError();
        }
        if (is_resource($result)) {
            $numrows = $this->numrows($result);
            if (is_object($numrows)) {
                return $numrows;
            }
            $this->num_rows[$result] = $numrows;
            return $result;
        }
        return DB_OK;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal mysql result pointer to the next available result
     *
     * This method has not been implemented yet.
     *
     * @param a valid sql result resource
     *
     * @access public
     *
     * @return false
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result MySQL result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param   $rownum the row number to fetch
     * @access public
     *
     * @return int DB_OK on success, a DB error on failure
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum=null)
    {
        if ($rownum !== null) {
            if (!@mysql_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @mysql_fetch_array($result, MYSQL_ASSOC);
        } else {
            $arr = @mysql_fetch_row($result);
        }
        if (!$arr) {
            // See: http://bugs.php.net/bug.php?id=22328
            // for why we can't check errors on fetching
            return null;
            /*
            $errno = @mysql_errno($this->connection);
            if (!$errno) {
                return NULL;
            }
            return $this->mysqlRaiseError($errno);
            */
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result MySQL result identifier or DB statement identifier
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return mysql_free_result($result);
        }

        $result = (int)$result; // $result is a prepared query handle
        if (!isset($this->prepare_tokens[$result])) {
            return false;
        }


		// I fixed the unset thing.

        $this->prepare_types = array();
        $this->prepare_tokens = array();

        return true;
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result MySQL result identifier
     *
     * @access public
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @mysql_num_fields($result);

        if (!$cols) {
            return $this->mysqlRaiseError();
        }

        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result MySQL result identifier
     *
     * @access public
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @mysql_num_rows($result);
        if ($rows === null) {
            return $this->mysqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Enable/disable automatic commits
     */
    function autoCommit($onoff = false)
    {
        // XXX if $this->transaction_opcount > 0, we should probably
        // issue a warning here.
        $this->autocommit = $onoff ? true : false;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the current transaction.
     */
    function commit()
    {
        if ($this->transaction_opcount > 0) {
            if ($this->_db) {
                if (!@mysql_select_db($this->_db, $this->connection)) {
                    return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
                }
            }
            $result = @mysql_query('COMMIT', $this->connection);
            $result = @mysql_query('SET AUTOCOMMIT=1', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->mysqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Roll back (undo) the current transaction.
     */
    function rollback()
    {
        if ($this->transaction_opcount > 0) {
            if ($this->_db) {
                if (!@mysql_select_db($this->_db, $this->connection)) {
                    return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
                }
            }
            $result = @mysql_query('ROLLBACK', $this->connection);
            $result = @mysql_query('SET AUTOCOMMIT=1', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->mysqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the data manipulation
     * query.  For other queries, this function returns 0.
     *
     * @return number of rows affected by the last query
     */

    function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            $result = @mysql_affected_rows($this->connection);
        } else {
            $result = 0;
        }
        return $result;
     }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int native MySQL error code
     */

    function errorNative()
    {
        return mysql_errno($this->connection);
    }

    // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for MySQL.  Will create the sequence if it does not exist.
     *
     * @access public
     *
     * @param string $seq_name the name of the sequence
     *
     * @param bool $ondemand whether to create the sequence table on demand
     * (default is true)
     *
     * @return mixed a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        do {
            $repeat = 0;
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("UPDATE ${seqname} ".
                                   'SET id=LAST_INSERT_ID(id+1)');
            $this->popErrorHandling();
            if ($result == DB_OK) {
                /** COMMON CASE **/
                $id = mysql_insert_id($this->connection);
                if ($id != 0) {
                    return $id;
                }
                /** EMPTY SEQ TABLE **/
                // Sequence table must be empty for some reason, so fill it and return 1
                // Obtain a user-level lock
                $result = $this->getOne("SELECT GET_LOCK('${seqname}_lock',10)");
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
                if ($result == 0) {
                    // Failed to get the lock, bail with a DB_ERROR_NOT_LOCKED error
                    return $this->mysqlRaiseError(DB_ERROR_NOT_LOCKED);
                }

                // add the default value
                $result = $this->query("REPLACE INTO ${seqname} VALUES (0)");
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }

                // Release the lock
                $result = $this->getOne("SELECT RELEASE_LOCK('${seqname}_lock')");
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
                // We know what the result will be, so no need to try again
                return 1;

            /** ONDEMAND TABLE CREATION **/
            } elseif ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE)
            {
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                } else {
                    $repeat = 1;
                }

            /** BACKWARDS COMPAT **/
            } elseif (DB::isError($result) &&
                      $result->getCode() == DB_ERROR_ALREADY_EXISTS)
            {
                // see _BCsequence() comment
                $result = $this->_BCsequence($seqname);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
                $repeat = 1;
            }
        } while ($repeat);

        return $this->raiseError($result);
    }

    // }}}
    // {{{ createSequence()

    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        $res = $this->query("CREATE TABLE ${seqname} ".
                            '(id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,'.
                            ' PRIMARY KEY(id))');
        if (DB::isError($res)) {
            return $res;
        }
        // insert yields value 1, nextId call will generate ID 2
        $res = $this->query("INSERT INTO ${seqname} VALUES(0)");
        if (DB::isError($res)) {
            return $res;
        }
        // so reset to zero
        return $this->query("UPDATE ${seqname} SET id = 0;");
    }

    // }}}
    // {{{ dropSequence()

    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP TABLE ${seqname}");
    }

    // }}}
    // {{{ _BCsequence()

    /**
    * Backwards compatibility with old sequence emulation implementation
    * (clean up the dupes)
    *
    * @param string $seqname The sequence name to clean up
    * @return mixed DB_Error or true
    */
    function _BCsequence($seqname)
    {
        // Obtain a user-level lock... this will release any previous
        // application locks, but unlike LOCK TABLES, it does not abort
        // the current transaction and is much less frequently used.
        $result = $this->getOne("SELECT GET_LOCK('${seqname}_lock',10)");
        if (DB::isError($result)) {
            return $result;
        }
        if ($result == 0) {
            // Failed to get the lock, can't do the conversion, bail
            // with a DB_ERROR_NOT_LOCKED error
            return $this->mysqlRaiseError(DB_ERROR_NOT_LOCKED);
        }

        $highest_id = $this->getOne("SELECT MAX(id) FROM ${seqname}");
        if (DB::isError($highest_id)) {
            return $highest_id;
        }
        // This should kill all rows except the highest
        // We should probably do something if $highest_id isn't
        // numeric, but I'm at a loss as how to handle that...
        $result = $this->query("DELETE FROM ${seqname} WHERE id <> $highest_id");
        if (DB::isError($result)) {
            return $result;
        }

        // If another thread has been waiting for this lock,
        // it will go thru the above procedure, but will have no
        // real effect
        $result = $this->getOne("SELECT RELEASE_LOCK('${seqname}_lock')");
        if (DB::isError($result)) {
            return $result;
        }
        return true;
    }

    // }}}
    // {{{ quote()

    /**
    * Quote the given string so it can be safely used within string delimiters
    * in a query.
    * @param $string mixed Data to be quoted
    * @return mixed "NULL" string, quoted string or original data
    */
    function quote($str = null)
    {
        switch (strtolower(gettype($str))) {
            case 'null':
                return 'NULL';
            case 'integer':
            case 'double':
                return $str;
            case 'string':
            default:
                if(function_exists('mysql_real_escape_string')) {
                    return "'".mysql_real_escape_string($str, $this->connection)."'";
                } else {
                    return "'".mysql_escape_string($str)."'";
                }
        }
    }

    // }}}
    // {{{ modifyQuery()

    function modifyQuery($query, $subject = null)
    {
        if ($this->options['optimize'] == 'portability') {
            // "DELETE FROM table" gives 0 affected rows in MySQL.
            // This little hack lets you know how many rows were deleted.
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        return $query;
    }

    // }}}
    // {{{ modifyLimitQuery()

    function modifyLimitQuery($query, $from, $count)
    {
        if (DB::isManip($query)) {
            return $query . " LIMIT $count";
        } else {
            return $query . " LIMIT $from, $count";
        }
    }

    // }}}
    // {{{ mysqlRaiseError()

    function mysqlRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(mysql_errno($this->connection));
        }
        return $this->raiseError($errno, null, null, null,
                                 @mysql_errno($this->connection) . " ** " .
                                 @mysql_error($this->connection));
    }

    // }}}
    // {{{ tableInfo()

    function tableInfo($result, $mode = null) {
        $count = 0;
        $id    = 0;
        $res   = array();

        /*
         * depending on $mode, metadata returns the following values:
         *
         * - mode is null (default):
         * $result[]:
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags
         *
         * - mode is DB_TABLEINFO_ORDER
         * $result[]:
         *   ["num_fields"] number of metadata records
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags
         *   ["order"][field name]  index of field named "field name"
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) { ...
         *
         * - mode is DB_TABLEINFO_ORDERTABLE
         *    the same as above. but additionally
         *   ["ordertable"][table name][field name] index of field
         *      named "field name"
         *
         *      this is, because if you have fields from different
         *      tables with the same field name * they override each
         *      other with DB_TABLEINFO_ORDER
         *
         *      you can combine DB_TABLEINFO_ORDER and
         *      DB_TABLEINFO_ORDERTABLE with DB_TABLEINFO_ORDER |
         *      DB_TABLEINFO_ORDERTABLE * or with DB_TABLEINFO_FULL
         */

        // if $result is a string, then we want information about a
        // table without a resultset
        if (is_string($result)) {
            $id = @mysql_list_fields($this->dsn['database'],
                                     $result, $this->connection);
            if (empty($id)) {
                return $this->mysqlRaiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $this->mysqlRaiseError();
            }
        }

        $count = @mysql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {
            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = @mysql_field_table ($id, $i);
                $res[$i]['name']  = @mysql_field_name  ($id, $i);
                $res[$i]['type']  = @mysql_field_type  ($id, $i);
                $res[$i]['len']   = @mysql_field_len   ($id, $i);
                $res[$i]['flags'] = @mysql_field_flags ($id, $i);
            }
        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = @mysql_field_table ($id, $i);
                $res[$i]['name']  = @mysql_field_name  ($id, $i);
                $res[$i]['type']  = @mysql_field_type  ($id, $i);
                $res[$i]['len']   = @mysql_field_len   ($id, $i);
                $res[$i]['flags'] = @mysql_field_flags ($id, $i);
                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result)) {
            @mysql_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
    * Returns the query needed to get some backend info
    * @param string $type What kind of info you want to retrieve
    * @return string The SQL query string
    */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                $sql = "SHOW TABLES";
                break;
            case 'views':
                return DB_ERROR_NOT_CAPABLE;
            case 'users':
                $sql = "select distinct User from user";
                if($this->dsn['database'] != 'mysql') {
                    $dsn = $this->dsn;
                    $dsn['database'] = 'mysql';
                    if (DB::isError($db = DB::connect($dsn))) {
                        return $db;
                    }
                    $sql = $db->getCol($sql);
                    $db->disconnect();
                    // XXX Fixme the mysql driver should take care of this
                    if (!@mysql_select_db($this->dsn['database'], $this->connection)) {
                        return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
                    }
                }
                return $sql;
                break;
            case 'databases':
                $sql = "SHOW DATABASES";
                break;
            default:
                return null;
        }
        return $sql;
    }

    // }}}

    // TODO/wishlist:
    // longReadlen
    // binmode
}

?>
