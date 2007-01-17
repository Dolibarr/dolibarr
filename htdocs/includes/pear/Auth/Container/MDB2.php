<?php

/**
 * Storage driver for use against PEAR MDB2
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Authentication
 * @package    Auth
 * @author     Lorenzo Alberton <l.alberton@quipo.it> 
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Auth
 * @since      File available since Release 1.3.0
 */

/**
 * Include Auth_Container base class
 */
//require_once 'Auth/Container.php';
require_once PEAR_PATH."/Auth/Container.php";

/**
 * Include PEAR MDB2 package
 */
//require_once 'MDB2.php';
require_once PEAR_PATH."/MDB2.php";

/**
 * Storage driver for fetching login data from a database
 *
 * This storage driver can use all databases which are supported
 * by the PEAR MDB2 abstraction layer to fetch login data.
 *
 * @category   Authentication
 * @package    Auth
 * @author     Lorenzo Alberton <l.alberton@quipo.it>
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@  File: $Revision$
 * @link       http://pear.php.net/package/Auth
 * @since      Class available since Release 1.3.0
 */
class Auth_Container_MDB2 extends Auth_Container
{

    // {{{ properties

    /**
     * Additional options for the storage container
     * @var array
     */
    var $options = array();

    /**
     * MDB object
     * @var object
     */
    var $db = null;
    var $dsn = '';

    /**
     * User that is currently selected from the DB.
     * @var string
     */
    var $activeUser = '';

    // }}}
    // {{{ Auth_Container_MDB2() [constructor]

    /**
     * Constructor of the container class
     *
     * Initate connection to the database via PEAR::MDB2
     *
     * @param  string Connection data or MDB2 object
     * @return object Returns an error object if something went wrong
     */
    function Auth_Container_MDB2($dsn)
    {
        $this->_setDefaults();

        if (is_array($dsn)) {
            $this->_parseOptions($dsn);
            if (empty($this->options['dsn'])) {
                DOLIPEAR::raiseError('No connection parameters specified!');
            }
        } else {
            $this->options['dsn'] = $dsn;
        }
    }

    // }}}
    // {{{ _connect()

    /**
     * Connect to database by using the given DSN string
     *
     * @access private
     * @param  mixed DSN string | array | mdb object
     * @return mixed  Object on error, otherwise bool
     */
    function _connect($dsn)
    {
        if (is_string($dsn) || is_array($dsn)) {
            $this->db =& MDB2::connect($dsn, $this->options['db_options']);
        } elseif (is_subclass_of($dsn, 'MDB2_Driver_Common')) {
            $this->db = $dsn;
        } elseif (is_object($dsn) && MDB2::isError($dsn)) {
            return DOLIPEAR::raiseError($dsn->getMessage(), $dsn->code);
        } else {
            return DOLIPEAR::raiseError('The given dsn was not valid in file ' . __FILE__ . ' at line ' . __LINE__,
                                    41,
                                    PEAR_ERROR_RETURN,
                                    null,
                                    null
                                    );

        }

        if (MDB2::isError($this->db) || DOLIPEAR::isError($this->db)) {
            return DOLIPEAR::raiseError($this->db->getMessage(), $this->db->code);
        }
        return true;
    }

    // }}}
    // {{{ _prepare()

    /**
     * Prepare database connection
     *
     * This function checks if we have already opened a connection to
     * the database. If that's not the case, a new connection is opened.
     *
     * @access private
     * @return mixed True or a MDB error object.
     */
    function _prepare()
    {
        if (is_subclass_of($this->db, 'MDB2_Driver_Common')) {
            return true;
        }
        return $this->_connect($this->options['dsn']);
    }

    // }}}
    // {{{ query()

    /**
     * Prepare query to the database
     *
     * This function checks if we have already opened a connection to
     * the database. If that's not the case, a new connection is opened.
     * After that the query is passed to the database.
     *
     * @access public
     * @param  string Query string
     * @return mixed  a MDB_result object or MDB_OK on success, a MDB
     *                or PEAR error on failure
     */
    function query($query)
    {
        $err = $this->_prepare();
        if ($err !== true) {
            return $err;
        }
        return $this->db->exec($query);
    }

    // }}}
    // {{{ _setDefaults()

    /**
     * Set some default options
     *
     * @access private
     * @return void
     */
    function _setDefaults()
    {
        $this->options['table']       = 'auth';
        $this->options['usernamecol'] = 'username';
        $this->options['passwordcol'] = 'password';
        $this->options['dsn']         = '';
        $this->options['db_fields']   = '';
        $this->options['cryptType']   = 'md5';
        $this->options['db_options']  = array();
    }

    // }}}
    // {{{ _parseOptions()

    /**
     * Parse options passed to the container class
     *
     * @access private
     * @param  array
     */
    function _parseOptions($array)
    {
        foreach ($array as $key => $value) {
            if (isset($this->options[$key])) {
                $this->options[$key] = $value;
            }
        }
    }

    // }}}
    // {{{ _quoteDBFields()

    /**
     * Quote the db_fields option to avoid the possibility of SQL injection.
     *
     * @access private
     * @return string A properly quoted string that can be concatenated into a
     * SELECT clause.
     */
    function _quoteDBFields()
    {
        if (isset($this->options['db_fields'])) {
            if (is_array($this->options['db_fields'])) {
                $fields = array();
                foreach ($this->options['db_fields'] as $field) {
                    $fields[] = $this->db->quoteIdentifier($field, true);
                }
                return implode(', ', $fields);
            } else {
                if (strlen($this->options['db_fields']) > 0) {
                    return $this->db->quoteIdentifier($this->options['db_fields'], true);
                }
            }
        }

        return '';
    }
    
    // }}}
    // {{{ fetchData()

    /**
     * Get user information from database
     *
     * This function uses the given username to fetch
     * the corresponding login data from the database
     * table. If an account that matches the passed username
     * and password is found, the function returns true.
     * Otherwise it returns false.
     *
     * @param   string Username
     * @param   string Password
     * @param   boolean If true password is secured using a md5 hash
     *                  the frontend and auth are responsible for making sure the container supports
     *                  challenge response password authentication
     * @return  mixed  Error object or boolean
     */
    function fetchData($username, $password, $isChallengeResponse=false)
    {
        // Prepare for a database query
        $err = $this->_prepare();
        if ($err !== true) {
            return DOLIPEAR::raiseError($err->getMessage(), $err->getCode());
        }

        //Check if db_fields contains a *, if so assume all columns are selected
        if (is_string($this->options['db_fields'])
            && strstr($this->options['db_fields'], '*')) {
            $sql_from = '*';
        } else {
            $sql_from = $this->db->quoteIdentifier($this->options['usernamecol'], true).
                ", ".$this->db->quoteIdentifier($this->options['passwordcol'], true);

            if (strlen($fields = $this->_quoteDBFields()) > 0) {
                $sql_from .= ', '.$fields;
            }
        }
        $query = sprintf("SELECT %s FROM %s WHERE %s = %s",
                         $sql_from,
                         $this->db->quoteIdentifier($this->options['table'], true),
                         $this->db->quoteIdentifier($this->options['usernamecol'], true),
                         $this->db->quote($username, 'text')
                         );

        $res = $this->db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($res) || DOLIPEAR::isError($res)) {
            return DOLIPEAR::raiseError($res->getMessage(), $res->getCode());
        }
        if (!is_array($res)) {
            $this->activeUser = '';
            return false;
        }

        // Perform trimming here before the hashing
        $password = trim($password, "\r\n");
        $res[$this->options['passwordcol']] = trim($res[$this->options['passwordcol']], "\r\n");
        // If using Challenge Response md5 the pass with the secret
        if ($isChallengeResponse) {
            $res[$this->options['passwordcol']] =
                md5($res[$this->options['passwordcol']].$this->_auth_obj->session['loginchallenege']);
            // UGLY cannot avoid without modifying verifyPassword
            if ($this->options['cryptType'] == 'md5') {
                $res[$this->options['passwordcol']] = md5($res[$this->options['passwordcol']]);
            }
        }
        if ($this->verifyPassword($password,
                                  $res[$this->options['passwordcol']],
                                  $this->options['cryptType'])) {
            // Store additional field values in the session
            foreach ($res as $key => $value) {
                if ($key == $this->options['passwordcol'] ||
                    $key == $this->options['usernamecol']) {
                    continue;
                }
                // Use reference to the auth object if exists
                // This is because the auth session variable can change so a static call to setAuthData does not make sense
                $this->_auth_obj->setAuthData($key, $value);
            }
            return true;
        }

        $this->activeUser = $res[$this->options['usernamecol']];
        return false;
    }

    // }}}
    // {{{ listUsers()

    /**
     * Returns a list of users from the container
     *
     * @return mixed array|PEAR_Error
     * @access public
     */
    function listUsers()
    {
        $err = $this->_prepare();
        if ($err !== true) {
            return DOLIPEAR::raiseError($err->getMessage(), $err->getCode());
        }

        $retVal = array();

        //Check if db_fields contains a *, if so assume all columns are selected
        if (strstr($this->options['db_fields'], '*')) {
            $sql_from = '*';
        } else {
            $sql_from = $this->db->quoteIdentifier($this->options['usernamecol'], true).
                ", ".$this->db->quoteIdentifier($this->options['passwordcol'], true);

            if (strlen($fields = $this->_quoteDBFields()) > 0) {
                $sql_from .= ', '.$fields;
            }
        }

        $query = sprintf('SELECT %s FROM %s',
                         $sql_from,
                         $this->db->quoteIdentifier($this->options['table'], true)
                         );

        $res = $this->db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($res)) {
            return DOLIPEAR::raiseError($res->getMessage(), $res->getCode());
        } else {
            foreach ($res as $user) {
                $user['username'] = $user[$this->options['usernamecol']];
                $retVal[] = $user;
            }
        }
        return $retVal;
    }

    // }}}
    // {{{ addUser()

    /**
     * Add user to the storage container
     *
     * @access public
     * @param  string Username
     * @param  string Password
     * @param  mixed  Additional information that are stored in the DB
     *
     * @return mixed True on success, otherwise error object
     */
    function addUser($username, $password, $additional = "")
    {

        // Prepare for a database query
        $err = $this->_prepare();
        if ($err !== true) {
            return DOLIPEAR::raiseError($err->getMessage(), $err->getCode());
        }

        if (isset($this->options['cryptType']) && $this->options['cryptType'] == 'none') {
            $cryptFunction = 'strval';
        } elseif (isset($this->options['cryptType']) && function_exists($this->options['cryptType'])) {
            $cryptFunction = $this->options['cryptType'];
        } else {
            $cryptFunction = 'md5';
        }

        $password = $cryptFunction($password);

        $additional_key   = '';
        $additional_value = '';

        if (is_array($additional)) {
            foreach ($additional as $key => $value) {
                $additional_key   .= ', ' . $this->db->quoteIdentifier($key, true);
                $additional_value .= ', ' . $this->db->quote($value, 'text');
            }
        }

        $query = sprintf("INSERT INTO %s (%s, %s%s) VALUES (%s, %s%s)",
                         $this->db->quoteIdentifier($this->options['table'], true),
                         $this->db->quoteIdentifier($this->options['usernamecol'], true),
                         $this->db->quoteIdentifier($this->options['passwordcol'], true),
                         $additional_key,
                         $this->db->quote($username, 'text'),
                         $this->db->quote($password, 'text'),
                         $additional_value
                         );

        $res = $this->query($query);

        if (MDB2::isError($res)) {
            return DOLIPEAR::raiseError($res->getMessage(), $res->code);
        }
        return true;
    }

    // }}}
    // {{{ removeUser()

    /**
     * Remove user from the storage container
     *
     * @access public
     * @param  string Username
     *
     * @return mixed True on success, otherwise error object
     */
    function removeUser($username)
    {
        // Prepare for a database query
        $err = $this->_prepare();
        if ($err !== true) {
            return DOLIPEAR::raiseError($err->getMessage(), $err->getCode());
        }

        $query = sprintf("DELETE FROM %s WHERE %s = %s",
                         $this->db->quoteIdentifier($this->options['table'], true),
                         $this->db->quoteIdentifier($this->options['usernamecol'], true),
                         $this->db->quote($username, 'text')
                         );

        $res = $this->query($query);

        if (MDB2::isError($res)) {
            return DOLIPEAR::raiseError($res->getMessage(), $res->code);
        }
        return true;
    }

    // }}}
    // {{{ changePassword()

    /**
     * Change password for user in the storage container
     *
     * @param string Username
     * @param string The new password (plain text)
     */
    function changePassword($username, $password)
    {
        // Prepare for a database query
        $err = $this->_prepare();
        if ($err !== true) {
            return DOLIPEAR::raiseError($err->getMessage(), $err->getCode());
        }

        if (isset($this->options['cryptType']) && $this->options['cryptType'] == 'none') {
            $cryptFunction = 'strval';
        } elseif (isset($this->options['cryptType']) && function_exists($this->options['cryptType'])) {
            $cryptFunction = $this->options['cryptType'];
        } else {
            $cryptFunction = 'md5';
        }

        $password = $cryptFunction($password);

        $query = sprintf("UPDATE %s SET %s = %s WHERE %s = %s",
                         $this->db->quoteIdentifier($this->options['table'], true),
                         $this->db->quoteIdentifier($this->options['passwordcol'], true),
                         $this->db->quote($password, 'text'),
                         $this->db->quoteIdentifier($this->options['usernamecol'], true),
                         $this->db->quote($username, 'text')
                         );

        $res = $this->query($query);

        if (MDB2::isError($res)) {
            return DOLIPEAR::raiseError($res->getMessage(), $res->code);
        }
        return true;
    }

    // }}}
    // {{{ supportsChallengeResponse()

    /**
     * Determine if this container supports
     * password authentication with challenge response
     *
     * @return bool
     * @access public
     */
    function supportsChallengeResponse()
    {
        return in_array($this->options['cryptType'], array('md5', 'none', ''));
    }

    // }}}
    // {{{ getCryptType()

    /**
     * Returns the selected crypt type for this container
     *
     * @return string Function used to crypt the password
     */
    function getCryptType()
    {
        return $this->options['cryptType'];
    }

    // }}}

}
?>