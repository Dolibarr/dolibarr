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
// Base class for DB implementations.
//

/**
 * DB_common is a base class for DB implementations, and must be
 * inherited by all such.
 */

require_once DOL_DOCUMENT_ROOT."/includes/pear/PEAR.php";

class DB_common extends DOLIPEAR
{
    // {{{ properties
    /**
    * assoc of capabilities for this DB implementation
    * $features['limit'] =>  'emulate' => emulate with fetch row by number
    *                        'alter'   => alter the query
    *                        false     => skip rows
    * @var array
    */
    var $features;

    /**
    * assoc mapping native error codes to DB ones
    * @var array
    */
    var $errorcode_map;

    /**
    * DB type (mysql, oci8, odbc etc.)
    * @var string
    */
    var $type;

    /**
    * @var string
    */
    var $prepare_tokens;

    /**
    * @var string
    */
    var $prepare_types;

    /**
    * @var string
    */
    var $prepared_queries;

    /**
    * @var integer
    */
    var $prepare_maxstmt = 0;

    /**
    * @var string
    */
    var $last_query = '';

    /**
    * @var integer
    */
    var $fetchmode = DB_FETCHMODE_ORDERED;

    /**
    * @var string
    */
    var $fetchmode_object_class = 'stdClass';

    /**
    * $options["persistent"] -> boolean persistent connection true|false?
    * $options["optimize"] -> string 'performance' or 'portability'
    * $options["debug"] -> integer numeric debug level
    * @var array
    */
    var $options = array(
        'persistent' => false,
        'optimize' => 'performance',
        'debug' => 0,
        'seqname_format' => '%s_seq',
        'autofree' => false
    );

    /**
    * DB handle
    * @var resource
    */
    var $dbh;

    // }}}
    // {{{ toString()
    /**
    * String conversation
    *
    * @return string
    * @access private
    */
    function toString()
    {
        $info = get_class($this);
        $info .=  ": (phptype=" . $this->phptype .
                  ", dbsyntax=" . $this->dbsyntax .
                  ")";

        if ($this->connection) {
            $info .= " [connected]";
        }

        return $info;
    }

    // }}}
    // {{{ constructor
    /**
    * Constructor
    */
    function DB_common()
    {
        $this->DOLIPEAR('DB_Error');
        $this->features = array();
        $this->errorcode_map = array();
        $this->fetchmode = DB_FETCHMODE_ORDERED;
    }

    // }}}
    // {{{ quoteString()

    /**
     * Quotes a string so it can be safely used within string delimiters
     * in a query (preserved for compatibility issues, quote() is preffered).
     *
     * @return string quoted string
     * @access public
     * @see quote()
     */
    function quoteString($string)
    {
        $string = $this->quote($string);
        if ($string{0} == "'") {
            return substr($string, 1, -1);
        }
        return $string;
    }

    /**
     * Quotes a string so it can be safely used in a query. It will return
     * the string with single quotes around. Other backend quote styles
     * should override this method.
     *
     * @param string $string the input string to quote
     *
     * @return string The NULL string or the string quotes
     *                in magic_quote_sybase style
     */
    function quote($string)
    {
        return ($string === null) ? 'NULL' : "'".str_replace("'", "''", $string)."'";
    }

    // }}}
    // {{{ provides()

    /**
     * Tell whether a DB implementation or its backend extension
     * supports a given feature.
     *
     * @param array $feature name of the feature (see the DB class doc)
     * @return bool whether this DB implementation supports $feature
     * @access public
     */

    function provides($feature)
    {
        return $this->features[$feature];
    }

    // }}}
    // {{{ errorCode()

    /**
     * Map native error codes to DB's portable ones.  Requires that
     * the DB implementation's constructor fills in the $errorcode_map
     * property.
     *
     * @param mixed $nativecode the native error code, as returned by the backend
     * database extension (string or integer)
     *
     * @return int a portable DB error code, or FALSE if this DB
     * implementation has no mapping for the given error code.
     *
     * @access public
     */

    function errorCode($nativecode)
    {
        if (isset($this->errorcode_map[$nativecode])) {
            return $this->errorcode_map[$nativecode];
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Map a DB error code to a textual message.  This is actually
     * just a wrapper for DB::errorMessage().
     *
     * @param integer $dbcode the DB error code
     *
     * @return string the corresponding error message, of FALSE
     * if the error code was unknown
     *
     * @access public
     */

    function errorMessage($dbcode)
    {
        return DB::errorMessage($this->errorcode_map[$dbcode]);
    }

    // }}}
    // {{{ raiseError()

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for PEAR::raiseError
     * without the message string.
     *
     * @param mixed    integer error code, or a PEAR error object (all
     *                 other parameters are ignored if this parameter is
     *                 an object
     *
     * @param int      error mode, see PEAR_Error docs
     *
     * @param mixed    If error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     *
     * @param string   Extra debug information.  Defaults to the last
     *                 query and native error code.
     *
     * @param mixed    Native error code, integer or string depending the
     *                 backend.
     *
     * @return object  a PEAR error object
     *
     * @access public
     * @see PEAR_Error
     */
    function &raiseError($code = DB_ERROR, $mode = null, $options = null,
                         $userinfo = null, $nativecode = null)
    {
        // The error is yet a DB error object
        if (is_object($code)) {
            // because we the static PEAR::raiseError, our global
            // handler should be used if it is set
            if ($mode === null && !empty($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            }
            return DOLIPEAR::raiseError($code, null, $mode, $options, null, null, true);
        }

        if ($userinfo === null) {
            $userinfo = $this->last_query;
        }

        if ($nativecode) {
            $userinfo .= " [nativecode=$nativecode]";
        }

        return DOLIPEAR::raiseError(null, $code, $mode, $options, $userinfo,
                                  'DB_Error', true);
    }

    // }}}
    // {{{ setFetchMode()

    /**
     * Sets which fetch mode should be used by default on queries
     * on this connection.
     *
     * @param integer $fetchmode DB_FETCHMODE_ORDERED or
     *        DB_FETCHMODE_ASSOC, possibly bit-wise OR'ed with
     *        DB_FETCHMODE_FLIPPED.
     *
     * @param string $object_class The class of the object
     *                      to be returned by the fetch methods when
     *                      the DB_FETCHMODE_OBJECT mode is selected.
     *                      If no class is specified by default a cast
     *                      to object from the assoc array row will be done.
     *                      There is also the posibility to use and extend the
     *                      'DB_Row' class.
     *
     * @see DB_FETCHMODE_ORDERED
     * @see DB_FETCHMODE_ASSOC
     * @see DB_FETCHMODE_FLIPPED
     * @see DB_FETCHMODE_OBJECT
     * @see DB_Row::DB_Row()
     * @access public
     */

    function setFetchMode($fetchmode, $object_class = null)
    {
        switch ($fetchmode) {
            case DB_FETCHMODE_OBJECT:
                if ($object_class) {
                    $this->fetchmode_object_class = $object_class;
                }
            case DB_FETCHMODE_ORDERED:
            case DB_FETCHMODE_ASSOC:
                $this->fetchmode = $fetchmode;
                break;
            default:
                return $this->raiseError('invalid fetchmode mode');
        }
    }

    // }}}
    // {{{ setOption()
    /**
    * set the option for the db class
    *
    * @param string $option option name
    * @param mixed  $value value for the option
    *
    * @return mixed DB_OK or DB_Error
    */
    function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;
            return DB_OK;
        }
        return $this->raiseError("unknown option $option");
    }

    // }}}
    // {{{ getOption()
    /**
    * returns the value of an option
    *
    * @param string $option option name
    *
    * @return mixed the option value
    */
    function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return $this->raiseError("unknown option $option");
    }

    // }}}
    // {{{ prepare()

    /**
    * Prepares a query for multiple execution with execute().
    * With some database backends, this is emulated.
    * prepare() requires a generic query as string like
    * "INSERT INTO numbers VALUES(?,?,?)". The ? are wildcards.
    * Types of wildcards:
    *   ? - a quoted scalar value, i.e. strings, integers
    *   & - requires a file name, the content of the file
    *       insert into the query (i.e. saving binary data
    *       in a db)
    *   ! - value is inserted 'as is'
    *
    * @param string the query to prepare
    *
    * @return resource handle for the query
    *
    * @access public
    * @see execute
    */

    function prepare($query)
    {
        $tokens = split("[\&\?\!]", $query);
        $token = 0;
        $types = array();
        $qlen = strlen($query);
        for ($i = 0; $i < $qlen; $i++) {
            switch ($query[$i]) {
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
    }

    // }}}
    // {{{ autoPrepare()

    /**
    * Make automaticaly an insert or update query and call prepare() with it
    *
    * @param string $table name of the table
    * @param array $table_fields ordered array containing the fields names
    * @param int $mode type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE)
    * @param string $where in case of update queries, this string will be put after the sql WHERE statement
    * @return resource handle for the query
    * @see buildManipSQL
    * @access public
    */
    function autoPrepare($table, $table_fields, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
        return $this->prepare($query);
    }

    // {{{
    // }}} autoExecute()

    /**
    * Make automaticaly an insert or update query and call prepare() and execute() with it
    *
    * @param string $table name of the table
    * @param array $fields_values assoc ($key=>$value) where $key is a field name and $value its value
    * @param int $mode type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE)
    * @param string $where in case of update queries, this string will be put after the sql WHERE statement
    * @return mixed  a new DB_Result or a DB_Error when fail
    * @see buildManipSQL
    * @see autoPrepare
    * @access public
    */
    function autoExecute($table, $fields_values, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $sth = $this->autoPrepare($table, array_keys($fields_values), $mode, $where);
        $ret = $this->execute($sth, array_values($fields_values));
        $this->freePrepared($sth);
        return $ret;

    }

    // {{{
    // }}} buildManipSQL()

    /**
    * Make automaticaly an sql query for prepare()
    *
    * Example : buildManipSQL('table_sql', array('field1', 'field2', 'field3'), DB_AUTOQUERY_INSERT)
    *           will return the string : INSERT INTO table_sql (field1,field2,field3) VALUES (?,?,?)
    * NB : - This belongs more to a SQL Builder class, but this is a simple facility
    *      - Be carefull ! If you don't give a $where param with an UPDATE query, all
    *        the records of the table will be updated !
    *
    * @param string $table name of the table
    * @param array $table_fields ordered array containing the fields names
    * @param int $mode type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE)
    * @param string $where in case of update queries, this string will be put after the sql WHERE statement
    * @return string sql query for prepare()
    * @access public
    */
    function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
        if (count($table_fields) == 0) {
            $this->raiseError(DB_ERROR_NEED_MORE_DATA);
        }
        $first = true;
        switch ($mode) {
            case DB_AUTOQUERY_INSERT:
                $values = '';
                $names = '';
                while (list(, $value) = each($table_fields)) {
                    if ($first) {
                        $first = false;
                    } else {
                        $names .= ',';
                        $values .= ',';
                    }
                    $names .= $value;
                    $values .= '?';
                }
                return "INSERT INTO $table ($names) VALUES ($values)";
                break;
            case DB_AUTOQUERY_UPDATE:
                $set = '';
                while (list(, $value) = each($table_fields)) {
                    if ($first) {
                        $first = false;
                    } else {
                        $set .= ',';
                    }
                    $set .= "$value = ?";
                }
                $sql = "UPDATE $table SET $set";
                if ($where) {
                    $sql .= " WHERE $where";
                }
                return $sql;
                break;
            default:
                $this->raiseError(DB_ERROR_SYNTAX);
        }
    }

    // }}}
    // {{{ execute()
    /**
    * Executes a prepared SQL query
    * With execute() the generic query of prepare is
    * assigned with the given data array. The values
    * of the array inserted into the query in the same
    * order like the array order
    *
    * @param resource $stmt query handle from prepare()
    * @param array    $data numeric array containing the
    *                       data to insert into the query
    *
    * @return mixed  a new DB_Result or a DB_Error when fail
    *
    * @access public
    * @see prepare()
    */
    function &execute($stmt, $data = false)
    {
        $realquery = $this->executeEmulateQuery($stmt, $data);
        if (DB::isError($realquery)) {
            return $realquery;
        }
        $result = $this->simpleQuery($realquery);

        if (DB::isError($result) || $result === DB_OK) {
            return $result;
        } else {
            return new DB_result($this, $result);
        }
    }

    // }}}
    // {{{ executeEmulateQuery()

    /**
    * Emulates the execute statement, when not supported
    *
    * @param resource $stmt query handle from prepare()
    * @param array    $data numeric array containing the
    *                       data to insert into the query
    *
    * @return mixed a string containing the real query run when emulating
    * prepare/execute.  A DB error code is returned on failure.
    *
    * @access private
    * @see execute()
    */

    function executeEmulateQuery($stmt, $data = false)
    {
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

        $realquery = $qq[0];
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
                    fclose($fp);
                }
            } else {
                if (is_array($data)) {
                    $pdata = &$data[$i];
                } else {
                    $pdata = &$data;
                }
            }

            $realquery .= ($type != DB_PARAM_MISC) ? $this->quote($pdata) : $pdata;
            $realquery .= $qq[$i + 1];
        }

        return $realquery;
    }

    // }}}
    // {{{ executeMultiple()

    /**
    * This function does several execute() calls on the same
    * statement handle.  $data must be an array indexed numerically
    * from 0, one execute call is done for every "row" in the array.
    *
    * If an error occurs during execute(), executeMultiple() does not
    * execute the unfinished rows, but rather returns that error.
    *
    * @param resource $stmt query handle from prepare()
    * @param array    $data numeric array containing the
    *                       data to insert into the query
    *
    * @return mixed DB_OK or DB_Error
    *
    * @access public
    * @see prepare(), execute()
    */

    function executeMultiple( $stmt, &$data )
    {
        for($i = 0; $i < sizeof( $data ); $i++) {
            $res = $this->execute($stmt, $data[$i]);
            if (DB::isError($res)) {
                return $res;
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ freePrepared()

    /*
    * Free the resource used in a prepared query
    *
    * @param $stmt The resurce returned by the prepare() function
    * @see prepare()
    */
    function freePrepared($stmt)
    {
        // Free the internal prepared vars
        if (isset($this->prepare_tokens[$stmt])) {
            unset($this->prepare_tokens[$stmt]);
            unset($this->prepare_types[$stmt]);
            unset($this->prepared_queries[$stmt]);
            return true;
        }
        return false;
    }

    // }}}
    // {{{ modifyQuery()

    /**
     * This method is used by backends to alter queries for various
     * reasons.  It is defined here to assure that all implementations
     * have this method defined.
     *
     * @param string $query  query to modify
     *
     * @return the new (modified) query
     *
     * @access private
     */
    function modifyQuery($query) {
        return $query;
    }

    // }}}
    // {{{ modifyLimitQuery()
    /**
    * This method is used by backends to alter limited queries
    *
    * @param string  $query query to modify
    * @param integer $from  the row to start to fetching
    * @param integer $count the numbers of rows to fetch
    *
    * @return the new (modified) query
    *
    * @access private
    */

    function modifyLimitQuery($query, $from, $count)
    {
        return $query;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results with a
     * DB_result object.
     *
     * @access public
     *
     * @param string $query  the SQL query or the statement to prepare
     * @param string $params the data to be added to the query
     * @return mixed a DB_result object or DB_OK on success, a DB
     *                error on failure
     *
     * @see DB::isError
     * @see DB_common::prepare
     * @see DB_common::execute
     */
    function &query($query, $params = array()) {
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $ret = $this->execute($sth, $params);
            $this->freePrepared($sth);
            return $ret;
        } else {
            $result = $this->simpleQuery($query);
            if (DB::isError($result) || $result === DB_OK) {
                return $result;
            } else {
                return new DB_result($this, $result);
            }
        }
    }

    // }}}
    // {{{ limitQuery()
    /**
    * Generates a limited query
    *
    * @param string  $query query
    * @param integer $from  the row to start to fetching
    * @param integer $count the numbers of rows to fetch
    * @param array   $params required for a statement
    *
    * @return mixed a DB_Result object, DB_OK or a DB_Error
    *
    * @access public
    */
    function &limitQuery($query, $from, $count, $params = array())
    {
        $query  = $this->modifyLimitQuery($query, $from, $count);
        $result = $this->query($query, $params);
        if (get_class($result) == 'db_result') {
            $result->setOption('limit_from', $from);
            $result->setOption('limit_count', $count);
        }
        return $result;
    }

    // }}}
    // {{{ getOne()

    /**
     * Fetch the first column of the first row of data returned from
     * a query.  Takes care of doing the query and freeing the results
     * when finished.
     *
     * @param string $query the SQL query
     * @param array $params if supplied, prepare/execute will be used
     *        with this array as execute parameters
     *
     * @return mixed DB_Error or the returned value of the query
     *
     * @access public
     */

    function &getOne($query, $params = array())
    {
        settype($params, "array");
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $res = $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res = $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $err = $res->fetchInto($row, DB_FETCHMODE_ORDERED);

        $res->free();

        if ($err !== DB_OK) {
            return $err;
        }

        return $row[0];
    }

    // }}}
    // {{{ getRow()

    /**
     * Fetch the first row of data returned from a query.  Takes care
     * of doing the query and freeing the results when finished.
     *
     * @param string $query the SQL query
     * @param integer $fetchmode the fetch mode to use
     * @param array $params array if supplied, prepare/execute will be used
     *        with this array as execute parameters
     * @access public
     * @return array the first row of results as an array indexed from
     * 0, or a DB error code.
     */

    function &getRow($query,
                     $params = null,
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        // compat check, the params and fetchmode parameters used to
        // have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                $tmp = $params;
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = null;
            }
        }
        $params = (empty($params)) ? array() : $params;
        $fetchmode = (empty($fetchmode)) ? DB_FETCHMODE_DEFAULT : $fetchmode;
        settype($params, 'array');
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $res = $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res = $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $err = $res->fetchInto($row, $fetchmode);

        $res->free();

        if ($err !== DB_OK) {
            return $err;
        }

        return $row;
    }

    // }}}
    // {{{ getCol()

    /**
     * Fetch a single column from a result set and return it as an
     * indexed array.
     *
     * @param string $query the SQL query
     *
     * @param mixed $col which column to return (integer [column number,
     * starting at 0] or string [column name])
     *
     * @param array $params array if supplied, prepare/execute will be used
     *        with this array as execute parameters
     * @access public
     *
     * @return array an indexed array with the data from the first
     * row at index 0, or a DB error code.
     */

    function &getCol($query, $col = 0, $params = array())
    {
        settype($params, "array");
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res = $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res = $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $fetchmode = is_int($col) ? DB_FETCHMODE_ORDERED : DB_FETCHMODE_ASSOC;
        $ret = array();

        while (is_array($row = $res->fetchRow($fetchmode))) {
            $ret[] = $row[$col];
        }

        $res->free();

        if (DB::isError($row)) {
            $ret = $row;
        }

        return $ret;
    }

    // }}}
    // {{{ getAssoc()

    /**
     * Fetch the entire result set of a query and return it as an
     * associative array using the first column as the key.
     *
     * If the result set contains more than two columns, the value
     * will be an array of the values from column 2-n.  If the result
     * set contains only two columns, the returned value will be a
     * scalar with the value of the second column (unless forced to an
     * array with the $force_array parameter).  A DB error code is
     * returned on errors.  If the result set contains fewer than two
     * columns, a DB_ERROR_TRUNCATED error is returned.
     *
     * For example, if the table "mytable" contains:
     *
     *  ID      TEXT       DATE
     * --------------------------------
     *  1       'one'      944679408
     *  2       'two'      944679408
     *  3       'three'    944679408
     *
     * Then the call getAssoc('SELECT id,text FROM mytable') returns:
     *   array(
     *     '1' => 'one',
     *     '2' => 'two',
     *     '3' => 'three',
     *   )
     *
     * ...while the call getAssoc('SELECT id,text,date FROM mytable') returns:
     *   array(
     *     '1' => array('one', '944679408'),
     *     '2' => array('two', '944679408'),
     *     '3' => array('three', '944679408')
     *   )
     *
     * If the more than one row occurs with the same value in the
     * first column, the last row overwrites all previous ones by
     * default.  Use the $group parameter if you don't want to
     * overwrite like this.  Example:
     *
     * getAssoc('SELECT category,id,name FROM mytable', false, null,
     *          DB_FETCHMODE_ASSOC, true) returns:
     *   array(
     *     '1' => array(array('id' => '4', 'name' => 'number four'),
     *                  array('id' => '6', 'name' => 'number six')
     *            ),
     *     '9' => array(array('id' => '4', 'name' => 'number four'),
     *                  array('id' => '6', 'name' => 'number six')
     *            )
     *   )
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @param string $query the SQL query
     *
     * @param boolean $force_array used only when the query returns
     * exactly two columns.  If true, the values of the returned array
     * will be one-element arrays instead of scalars.
     *
     * @param array $params array if supplied, prepare/execute will be used
     *        with this array as execute parameters
     *
     * @param boolean $group if true, the values of the returned array
     *                       is wrapped in another array.  If the same
     *                       key value (in the first column) repeats
     *                       itself, the values will be appended to
     *                       this array instead of overwriting the
     *                       existing values.
     *
     * @access public
     *
     * @return array associative array with results from the query.
     */

    function &getAssoc($query, $force_array = false, $params = array(),
                       $fetchmode = DB_FETCHMODE_ORDERED, $group = false)
    {
        settype($params, "array");
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res = $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res = $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $cols = $res->numCols();

        if ($cols < 2) {
            return $this->raiseError(DB_ERROR_TRUNCATED);
        }

        $results = array();

        if ($cols > 2 || $force_array) {
            // return array values
            // XXX this part can be optimized
            if ($fetchmode == DB_FETCHMODE_ASSOC) {
                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ASSOC))) {
                    reset($row);
                    $key = current($row);
                    unset($row[key($row)]);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            } else {
                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
                    // we shift away the first element to get
                    // indices running from 0 again
                    $key = array_shift($row);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            }
            if (DB::isError($row)) {
                $results = $row;
            }
        } else {
            // return scalar values
            while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
                if ($group) {
                    $results[$row[0]][] = $row[1];
                } else {
                    $results[$row[0]] = $row[1];
                }
            }
            if (DB::isError($row)) {
                $results = $row;
            }
        }

        $res->free();

        return $results;
    }

    // }}}
    // {{{ getAll()

    /**
     * Fetch all the rows returned from a query.
     *
     * @param string $query the SQL query
     *
     * @param array $params array if supplied, prepare/execute will be used
     *        with this array as execute parameters
     * @param integer $fetchmode the fetch mode to use
     *
     * @access public
     * @return array an nested array, or a DB error
     */

    function &getAll($query,
                     $params = null,
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        // compat check, the params and fetchmode parameters used to
        // have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                $tmp = $params;
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = null;
            }
        }
        $params = (empty($params)) ? array() : $params;
        $fetchmode = (empty($fetchmode)) ? DB_FETCHMODE_DEFAULT : $fetchmode;
        settype($params, "array");
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res = $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res = $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $results = array();
        while (DB_OK === $res->fetchInto($row, $fetchmode)) {
            if ($fetchmode & DB_FETCHMODE_FLIPPED) {
                foreach ($row as $key => $val) {
                    $results[$key][] = $val;
                }
            } else {
                $results[] = $row;
            }
        }

        $res->free();

        if (DB::isError($row)) {
            return $this->raiseError($row);
        }
        return $results;
    }

    // }}}
    // {{{ autoCommit()
    /**
    * enable automatic Commit
    *
    * @param boolean $onoff
    * @return mixed DB_Error
    *
    * @access public
    */
    function autoCommit($onoff=false)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ commit()
    /**
    * starts a Commit
    *
    * @return mixed DB_Error
    *
    * @access public
    */
    function commit()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ rollback()
    /**
    * starts a rollback
    *
    * @return mixed DB_Error
    *
    * @access public
    */
    function rollback()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ numRows()
    /**
    * returns the number of rows in a result object
    *
    * @param object DB_Result the result object to check
    *
    * @return mixed DB_Error or the number of rows
    *
    * @access public
    */
    function numRows($result)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ affectedRows()
    /**
    * returns the affected rows of a query
    *
    * @return mixed DB_Error or number of rows
    *
    * @access public
    */
    function affectedRows()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ errorNative()
    /**
    * returns an errormessage, provides by the database
    *
    * @return mixed DB_Error or message
    *
    * @access public
    */
    function errorNative()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ nextId()
    /**
    * returns the next free id of a sequence
    *
    * @param string  $seq_name name of the sequence
    * @param boolean $ondemand when true the seqence is
    *                          automatic created, if it
    *                          not exists
    *
    * @return mixed DB_Error or id
    */
    function nextId($seq_name, $ondemand = true)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ createSequence()
    /**
    * creates a new sequence
    *
    * @param string $seq_name name of the new sequence
    *
    * @return mixed DB_Error
    *
    * @access public
    */
    function createSequence($seq_name)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ dropSequence()
    /**
    * deletes a sequence
    *
    * @param string $seq_name name of the sequence
    *
    * @return mixed DB_Error
    *
    * @access public
    */
    function dropSequence($seq_name)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ tableInfo()
    /**
    * returns meta data about the result set
    *
    * @param object DB_Result $result the result object to analyse
    * @param mixed $mode depends on implementation
    *
    * @return mixed DB_Error
    *
    * @access public
    */
    function tableInfo($result, $mode = null)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ getTables()
    /**
    * @deprecated
    */
    function getTables()
    {
        return $this->getListOf('tables');
    }

    // }}}
    // {{{ getListOf()
    /**
    * list internal DB info
    * valid values for $type are db dependent,
    * often: databases, users, view, functions
    *
    * @param string $type type of requested info
    *
    * @return mixed DB_Error or the requested data
    *
    * @access public
    */
    function getListOf($type)
    {
        $sql = $this->getSpecialQuery($type);
        if ($sql === null) {                                // No support
            return $this->raiseError(DB_ERROR_UNSUPPORTED);
        } elseif (is_int($sql) || DB::isError($sql)) {      // Previous error
            return $this->raiseError($sql);
        } elseif (is_array($sql)) {                         // Already the result
            return $sql;
        }
        return $this->getCol($sql);                         // Launch this query
    }
    // }}}
    // {{{ getSequenceName()

    function getSequenceName($sqn)
    {
        return sprintf($this->getOption("seqname_format"),
                       preg_replace('/[^a-z0-9_]/i', '_', $sqn));
    }

    // }}}
}

// Used by many drivers
if (!function_exists('array_change_key_case')) {
    define('CASE_UPPER', 1);
    define('CASE_LOWER', 0);
    function &array_change_key_case(&$array, $case) {
        $casefunc = ($case == CASE_LOWER) ? 'strtolower' : 'strtoupper';
        $ret = array();
        foreach ($array as $key => $value) {
            $ret[$casefunc($key)] = $value;
        }
        return $ret;
    }
}

?>
