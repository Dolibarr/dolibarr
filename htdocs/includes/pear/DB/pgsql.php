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
// | Authors: Rui Hirokawa <rui_hirokawa@ybb.ne.jp>                       |
// |          Stig Bakken <ssb@php.net>                                   |
// +----------------------------------------------------------------------+
//
// $Id$
//
// Database independent query interface definition for PHP's PostgreSQL
// extension.
//

require_once 'DB/common.php';

class DB_pgsql extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $transaction_opcount = 0;
    var $dsn = array();
    var $row = array();
    var $num_rows = array();
    var $affected = 0;
    var $autocommit = true;
    var $fetchmode = DB_FETCHMODE_ORDERED;

    // }}}
    // {{{ constructor

    function DB_pgsql()
    {
        $this->DB_common();
        $this->phptype = 'pgsql';
        $this->dbsyntax = 'pgsql';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'alter'
        );
        $this->errorcode_map = array(
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
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('pgsql'))
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);

        $this->dsn = $dsninfo;
        $protocol = (isset($dsninfo['protocol'])) ? $dsninfo['protocol'] : 'tcp';
        $connstr = '';

        if ($protocol == 'tcp') {
            if (!empty($dsninfo['hostspec'])) {
                $connstr = 'host=' . $dsninfo['hostspec'];
            }
            if (!empty($dsninfo['port'])) {
                $connstr .= ' port=' . $dsninfo['port'];
            }
        }

        if (isset($dsninfo['database'])) {
            $connstr .= ' dbname=\'' . addslashes($dsninfo['database']) . '\'';
        }
        if (!empty($dsninfo['username'])) {
            $connstr .= ' user=\'' . addslashes($dsninfo['username']) . '\'';
        }
        if (!empty($dsninfo['password'])) {
            $connstr .= ' password=\'' . addslashes($dsninfo['password']) . '\'';
        }
        if (!empty($dsninfo['options'])) {
            $connstr .= ' options=' . $dsninfo['options'];
        }
        if (!empty($dsninfo['tty'])) {
            $connstr .= ' tty=' . $dsninfo['tty'];
        }

        $connect_function = $persistent ? 'pg_pconnect' : 'pg_connect';
        // catch error
        ob_start();
        $conn = $connect_function($connstr);
        $error = ob_get_contents();
        ob_end_clean();
        if ($conn == false) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null,
                                     null, null, strip_tags($error));
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        $ret = @pg_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to PostgreSQL and return the results as a
     * PostgreSQL resource identifier.
     *
     * @param $query the SQL query
     *
     * @return int returns a valid PostgreSQL result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error code
     * is returned on failure.
     */
    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @pg_exec($this->connection, "begin;");
                if (!$result) {
                    return $this->pgsqlRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @pg_exec($this->connection, $query);
        if (!$result) {
            return $this->pgsqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if ($ismanip) {
            $this->affected = @pg_cmdtuples($result);
            return DB_OK;
        } elseif (preg_match('/^\s*\(?\s*SELECT\s+/si', $query) &&
                  !preg_match('/^\s*\(?\s*SELECT\s+INTO\s/si', $query)) {
            /* PostgreSQL commands:
               ABORT, ALTER, BEGIN, CLOSE, CLUSTER, COMMIT, COPY,
               CREATE, DECLARE, DELETE, DROP TABLE, EXPLAIN, FETCH,
               GRANT, INSERT, LISTEN, LOAD, LOCK, MOVE, NOTIFY, RESET,
               REVOKE, ROLLBACK, SELECT, SELECT INTO, SET, SHOW,
               UNLISTEN, UPDATE, VACUUM
            */
            $this->row[$result] = 0; // reset the row counter.
            $numrows = $this->numrows($result);
            if (is_object($numrows)) {
                return $numrows;
            }
            $this->num_rows[$result] = $numrows;
            $this->affected = 0;
            return $result;
        } else {
            $this->affected = 0;
            return DB_OK;
        }
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal pgsql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ errorCode()

    /**
     * Map native error codes to DB's portable ones.  Requires that
     * the DB implementation's constructor fills in the $errorcode_map
     * property.
     *
     * @param $nativecode the native error code, as returned by the backend
     * database extension (string or integer)
     *
     * @return int a portable DB error code, or FALSE if this DB
     * implementation has no mapping for the given error code.
     */

    function errorCode($errormsg)
    {
        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/(Table does not exist\.|Relation [\"\'].*[\"\'] does not exist|sequence does not exist|class ".+" not found)$/' => DB_ERROR_NOSUCHTABLE,
                '/table [\"\'].*[\"\'] does not exist/' => DB_ERROR_NOSUCHTABLE,
                '/Relation [\"\'].*[\"\'] already exists|Cannot insert a duplicate key into (a )?unique index.*/'      => DB_ERROR_ALREADY_EXISTS,
                '/divide by zero$/'                     => DB_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /' => DB_ERROR_INVALID_NUMBER,
                '/ttribute [\"\'].*[\"\'] not found$|Relation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => DB_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near \"/'   => DB_ERROR_SYNTAX,
                '/referential integrity violation/'     => DB_ERROR_CONSTRAINT
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result PostgreSQL result identifier
     * @param $row (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param $rownum the row number to fetch
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function fetchInto($result, &$row, $fetchmode, $rownum=null)
    {
        $rownum = ($rownum !== null) ? $rownum : $this->row[$result];
        if ($rownum >= $this->num_rows[$result]) {
            return null;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $row = @pg_fetch_array($result, $rownum, PGSQL_ASSOC);
        } else {
            $row = @pg_fetch_row($result, $rownum);
        }
        if (!$row) {
            $err = pg_errormessage($this->connection);
            if (!$err) {
                return null;
            }
            return $this->pgsqlRaiseError();
        }
        $this->row[$result] = ++$rownum;
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result int PostgreSQL result identifier or DB statement identifier
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return @pg_freeresult($result);
        }
        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }
        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);
        unset($this->row[(int)$result]);
        unset($this->num_rows[(int)$result]);
        $this->affected = 0;
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
            case 'double' :
                return $str;
            case 'boolean':
                return $str ? 'TRUE' : 'FALSE';
            case 'string':
            default:
                $str = str_replace("'", "''", $str);
                //PostgreSQL treats a backslash as an escape character.
                $str = str_replace('\\', '\\\\', $str);
                return "'$str'";
        }
    }
    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result resource PostgreSQL result identifier
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @pg_numfields($result);
        if (!$cols) {
            return $this->pgsqlRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result resource PostgreSQL result identifier
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @pg_numrows($result);
        if ($rows === null) {
            return $this->pgsqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @return int native PostgreSQL error code
     */
    function errorNative()
    {
        return pg_errormessage($this->connection);
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
            // (disabled) hack to shut up error messages from libpq.a
            //@fclose(@fopen("php://stderr", "w"));
            $result = @pg_exec($this->connection, "end;");
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->pgsqlRaiseError();
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
            $result = @pg_exec($this->connection, "abort;");
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->pgsqlRaiseError();
            }
        }
        return DB_OK;
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
    // {{{ nextId()

    /**
     * Get the next value in a sequence.
     *
     * We are using native PostgreSQL sequences. If a sequence does
     * not exist, it will be created, unless $ondemand is false.
     *
     * @access public
     * @param string $seq_name the name of the sequence
     * @param bool $ondemand whether to create the sequence on demand
     * @return a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        $repeat = false;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("SELECT NEXTVAL('${seqname}')");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = true;
                $this->pushErrorHandling(PEAR_ERROR_RETURN);
                $result = $this->createSequence($seq_name);
                $this->popErrorHandling();
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } else {
                $repeat = false;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        $result->free();
        return $arr[0];
    }

    // }}}
    // {{{ createSequence()

    /**
     * Create the sequence
     *
     * @param string $seq_name the name of the sequence
     * @return mixed DB_OK on success or DB error on error
     * @access public
     */
    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        $result = $this->query("CREATE SEQUENCE ${seqname}");
        return $result;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Drop a sequence
     *
     * @param string $seq_name the name of the sequence
     * @return mixed DB_OK on success or DB error on error
     * @access public
     */
    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP SEQUENCE ${seqname}");
    }

    // }}}
    // {{{ modifyLimitQuery()

    function modifyLimitQuery($query, $from, $count)
    {
        $query = $query . " LIMIT $count OFFSET $from";
        return $query;
    }

    // }}}
    // {{{ pgsqlRaiseError()

    function pgsqlRaiseError($errno = null)
    {
        $native = $this->errorNative();
        if ($errno === null) {
            $err = $this->errorCode($native);
        } else {
            $err = $errno;
        }
        return $this->raiseError($err, null, null, null, $native);
    }

    // }}}
    // {{{ _pgFieldFlags()

    /**
     * Flags of a Field
     *
     * @param int $resource PostgreSQL result identifier
     * @param int $num_field the field number
     *
     * @return string The flags of the field ("not_null", "default_xx", "primary_key",
     *                "unique" and "multiple_key" are supported)
     * @access private
     */
    function _pgFieldFlags($resource, $num_field, $table_name)
    {
        $field_name = @pg_fieldname($resource, $num_field);

        $result = @pg_exec($this->connection, "SELECT f.attnotnull, f.atthasdef
                                FROM pg_attribute f, pg_class tab, pg_type typ
                                WHERE tab.relname = typ.typname
                                AND typ.typrelid = f.attrelid
                                AND f.attname = '$field_name'
                                AND tab.relname = '$table_name'");
        if (@pg_numrows($result) > 0) {
            $row = @pg_fetch_row($result, 0);
            $flags  = ($row[0] == 't') ? 'not_null ' : '';

            if ($row[1] == 't') {
                $result = @pg_exec($this->connection, "SELECT a.adsrc
                                    FROM pg_attribute f, pg_class tab, pg_type typ, pg_attrdef a
                                    WHERE tab.relname = typ.typname AND typ.typrelid = f.attrelid
                                    AND f.attrelid = a.adrelid AND f.attname = '$field_name'
                                    AND tab.relname = '$table_name' AND f.attnum = a.adnum");
                $row = @pg_fetch_row($result, 0);
                $num = str_replace('\'', '', $row[0]);

                $flags .= "default_$num ";
            }
        }
        $result = @pg_exec($this->connection, "SELECT i.indisunique, i.indisprimary, i.indkey
                                FROM pg_attribute f, pg_class tab, pg_type typ, pg_index i
                                WHERE tab.relname = typ.typname
                                AND typ.typrelid = f.attrelid
                                AND f.attrelid = i.indrelid
                                AND f.attname = '$field_name'
                                AND tab.relname = '$table_name'");
        $count = @pg_numrows($result);

        for ($i = 0; $i < $count ; $i++) {
            $row = @pg_fetch_row($result, $i);
            $keys = explode(" ", $row[2]);

            if (in_array($num_field + 1, $keys)) {
                $flags .= ($row[0] == 't') ? 'unique ' : '';
                $flags .= ($row[1] == 't') ? 'primary ' : '';
                if (count($keys) > 1)
                    $flags .= 'multiple_key ';
            }
        }

        return trim($flags);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: doesn't support table name and flags if called from a db_result
     *
     * @param  mixed $resource PostgreSQL result identifier or table name
     * @param  int $mode A valid tableInfo mode (DB_TABLEINFO_ORDERTABLE or
     *                   DB_TABLEINFO_ORDER)
     *
     * @return array An array with all the information
     */
    function tableInfo($result, $mode = null)
    {
        $count = 0;
        $id    = 0;
        $res   = array();

        /*
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
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
            $id = @pg_exec($this->connection,"SELECT * FROM $result LIMIT 0");
            if (empty($id)) {
                return $this->pgsqlRaiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $this->pgsqlRaiseError();
            }
        }

        $count = @pg_numfields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @pg_fieldname ($id, $i);
                $res[$i]['type']  = @pg_fieldtype ($id, $i);
                $res[$i]['len']   = @pg_fieldsize ($id, $i);
                $res[$i]['flags'] = (is_string($result)) ? $this->_pgFieldflags($id, $i, $result) : '';
            }

        } else { // full
            $res["num_fields"]= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @pg_fieldname ($id, $i);
                $res[$i]['type']  = @pg_fieldtype ($id, $i);
                $res[$i]['len']   = @pg_fieldsize ($id, $i);
                $res[$i]['flags'] = (is_string($result)) ? $this->_pgFieldFlags($id, $i, $result) : '';
                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result) && is_resource($id)) {
            @pg_freeresult($id);
        }
        return $res;
    }

    // }}}
    // {{{ getTablesQuery()

    /**
    * Returns the query needed to get some backend info
    * @param string $type What kind of info you want to retrieve
    * @return string The SQL query string
    */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables': {
                $sql = "SELECT c.relname as \"Name\"
                        FROM pg_class c, pg_user u
                        WHERE c.relowner = u.usesysid AND c.relkind = 'r'
                        AND not exists (select 1 from pg_views where viewname = c.relname)
                        AND c.relname !~ '^pg_'
                        UNION
                        SELECT c.relname as \"Name\"
                        FROM pg_class c
                        WHERE c.relkind = 'r'
                        AND not exists (select 1 from pg_views where viewname = c.relname)
                        AND not exists (select 1 from pg_user where usesysid = c.relowner)
                        AND c.relname !~ '^pg_'";
                break;
            }
            case 'views': {
                // Table cols: viewname | viewowner | definition
                $sql = "SELECT viewname FROM pg_views";
                break;
            }
            case 'users': {
                // cols: usename |usesysid|usecreatedb|usetrace|usesuper|usecatupd|passwd  |valuntil
                $sql = 'SELECT usename FROM pg_user';
                break;
            }
            case 'databases': {
                $sql = 'SELECT datname FROM pg_database';
                break;
            }
            case 'functions': {
                $sql = 'SELECT proname FROM pg_proc';
                break;
            }
            default:
                return null;
        }
        return $sql;
    }

    // }}}

}

// Local variables:
// tab-width: 4
// c-basic-offset: 4
// End:
?>
