<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
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
// | Authors: Martin Jansen <mj@php.net>                                  |
// +----------------------------------------------------------------------+
//
// $Id$
//
require_once DOL_DOCUMENT_ROOT."/includes/pear/PEAR.php";
//require_once "PEAR.php";

define("AUTH_IDLED",       -1);
define("AUTH_EXPIRED",     -2);
define("AUTH_WRONG_LOGIN", -3);

/**
 * PEAR::Auth
 *
 * The PEAR::Auth class provides methods for creating an
 * authentication system using PHP.
 *
 * @author  Martin Jansen <mj@php.net>
 * @package Auth
 * @version $Revision$
 */
class DOLIAuth {

    /**
     * Auth lifetime in seconds
     *
     * If this variable is set to 0, auth never expires
     *
     * @var  integer
     * @see  setExpire(), checkAuth()
     */
    var $expire = 0;

    /**
     * Has the auth session expired?
     *
     * @var   bool
     * @see   checkAuth(), drawLogin()
     */
    var $expired = false;

    /**
     * Maximum time of idleness in seconds
     *
     * The difference to $expire is, that the idletime gets
     * refreshed each time, checkAuth() is called. If this
     * variable is set to 0, idle time is never checked.
     *
     * @var integer
     * @see setIdle(), checkAuth()
     */
    var $idle = 0;

    /**
     * Is the maximum idletime over?
     *
     * @var boolean
     * @see checkAuth(), drawLogin();
     */
    var $idled = false;

    /**
     * Storage object
     *
     * @var object
     * @see Auth(), validateLogin()
     */
    var $storage = "";

    /**
     * Function defined by the user, that creates the login screen
     *
     * @var string
     */
    var $loginFunction = "";

    /**
     * Should the login form be displayed?
     *
     * @var   bool
     * @see   setShowlogin()
     */
    var $showLogin = true;

    /**
     * Current authentication status
     *
     * @var string
     */
    var $status = "";

    /**
     * Username
     *
     * @var string
     */
    var $username = "";

    /**
     * Password
     *
     * @var string
     */
    var $password = "";

    /**
     * Login callback function name
     *
     * @var string
     * @see setLoginCallback()
     */
    var $loginCallback = "";

    /**
     * Logout callback function name
     *
     * @var string
     * @see setLogoutCallback()
     */
    var $logoutCallback = "";
        
    // {{{ Constructor

    /**
     * Constructor
     *
     * Set up the storage driver.
     *
     * @param string    Type of the storage driver
     * @param mixed     Additional options for the storage driver
     *                  (example: if you are using DB as the storage
     *                   driver, you have to pass the dsn string here)
     *
     * @param string    Name of the function that creates the login form
     * @param boolean   Should the login form be displayed if neccessary?
     * @return void
     */
    function DOLIAuth($storageDriver, $options = "", $loginFunction = "", $showLogin = true)
    {
        if ($loginFunction != "" && function_exists($loginFunction)) {
            $this->loginFunction = $loginFunction;
        }

        if (is_bool($showLogin)) {
            $this->showLogin = $showLogin;
        }

        if (is_object($storageDriver)) {
            $this->storage =& $storageDriver;
        }
        else{
            $this->storage = $this->_factory($storageDriver, $options);
        }
    }

    // }}}
    // {{{ _factory()

    /**
     * Return a storage driver based on $driver and $options
     *
     * @access private
     * @static
     * @param  string $driver  Type of storage class to return
     * @param  string $options Optional parameters for the storage class
     * @return object Object   Storage object
     */
    function _factory($driver, $options = "")
    {
        $storage_path = "Auth/Container/" . $driver . ".php";
        $storage_class = "Auth_Container_" . $driver;

	require_once DOL_DOCUMENT_ROOT."/includes/pear/".$storage_path;

        return new $storage_class($options);
    }

    // }}}
    // {{{ assignData()

    /**
     * Assign data from login form to internal values
     *
     * This function takes the values for username and password
     * from $HTTP_POST_VARS and assigns them to internal variables.
     * If you wish to use another source apart from $HTTP_POST_VARS,
     * you have to derive this function.
     *
     * @access private
     * @global $HTTP_POST_VARS
     * @see    Auth
     * @return void
     */
    function assignData()
    {
        $post = &$this->_importGlobalVariable("post");

        if (isset($post['username']) && $post['username'] != "") {
            $this->username = (get_magic_quotes_gpc() == 1 ? stripslashes($post['username']) : $post['username']);
        }

        if (isset($post['password']) && $post['password'] != "") {
            $this->password = (get_magic_quotes_gpc() == 1 ? stripslashes($post['password']) : $post['password'] );
        }

    }

    // }}}
    // {{{ start()

    /**
     * Start new auth session
     *
     * @access public
     * @return void
     */
    function start()
    {
        $this->assignData();

        session_start();

        if (!$this->checkAuth()) {
            $this->login();
        }
    }

    // }}}
    // {{{ login()

    /**
     * Login function
     *
     * @access private
     * @return void
     */
    function login()
    {
        $login_ok = false;

        /**
         * When the user has already entered a username,
         * we have to validate it.
         */
        if (!empty($this->username)) {
            if (true === $this->storage->fetchData($this->username, $this->password)) {
                $login_ok = true;
            }
        }

        if (!empty($this->username) && $login_ok) {
            $this->setAuth($this->username);
            if (!empty($this->loginCallback)) {
                call_user_func($this->loginCallback,$this->username);
            }
        }

        /**
         * If the login failed or the user entered no username,
         * output the login screen again.
         */
        if (!empty($this->username) && !$login_ok) {
            $this->status = AUTH_WRONG_LOGIN;
        }

        if ((empty($this->username) || !$login_ok) && $this->showLogin) {
            $this->drawLogin($this->storage->activeUser);
            return;
        }
    }

    // }}}
    // {{{ setExpire()

    /**
     * Set the maximum expire time
     * 
     * @access public
     * @param  integer time in seconds
     * @param  bool    add time to current expire time or not
     * @return void
     */
    function setExpire($time, $add = false)
    {
        if ($add) {
            $this->expire += $time;
        } else {
            $this->expire = $time;
        }
    }

    // }}}
    // {{{ setIdle()

    /**
     * Set the maximum idle time
     * 
     * @access public
     * @param  integer time in seconds
     * @param  bool    add time to current maximum idle time or not
     * @return void
     */
    function setIdle($time, $add = false)
    {
        if ($add) {
            $this->idle += $time;
        } else {
            $this->idle = $time;
        }
    }
    
    // }}}
    // {{{ setSessionname()

    /**
     * Set name of the session to a customized value.
     *
     * If you are using multiple instances of PEAR::Auth
     * on the same domain, you can change the name of
     * session per application via this function.
     *
     * @access public
     * @param  string New name for the session
     * @return void
     */
    function setSessionname($name = "PHPSESSID")
    {
        @session_name($name);
    }

    // }}}
    // {{{ setShowLogin()

    /**
     * Should the login form be displayed if neccessary?
     *
     * @access public
     * @param  bool    show login form or not
     * @return void
     */
    function setShowLogin($showLogin = true)
    {
        $this->showLogin = $showLogin;
    }

    /**
     * Register a callback function to be called on user login.
     * The function will receive a single parameter, the username.
     *
     * @access public
     * @param  string  callback function name
     * @return void
     * @see    setLogoutCallback()
     */
    function setLoginCallback($loginCallback)
    {
        $this->loginCallback = $loginCallback;
    }

    /**
     * Register a callback function to be called on user logout.
     * The function will receive a single parameter, the username.
     *
     * @access public
     * @param  string  callback function name
     * @return void
     * @see    setLoginCallback()
     */
    function setLogoutCallback($logoutCallback)
    {
        $this->logoutCallback = $logoutCallback;
    }
        
    // }}}
    // {{{ setAuthData()
    
    /**
     * Register additional information that is to be stored
     * in the session.
     *
     * @access public
     * @param  string  Name of the data field
     * @param  mixed   Value of the data field
     * @param  boolean Should existing data be overwritten? (default 
     *                 is true)
     * @return void
     */
    function setAuthData($name, $value, $overwrite = true)
    {
        $session = &DOLIAuth::_importGlobalVariable("session");

        if (!empty($session['auth']['data'][$name]) && $overwrite == false) {
            return;
        }
        $session['auth']['data'][$name] = $value;
    }
    
    // }}}
    // {{{ getAuthData()
    
    /**
     * Get additional information that is stored in the session.
     *
     * If no value for the first parameter is passed, the method will
     * return all data that is currently stored.
     *
     * @access public
     * @param  string Name of the data field
     * @return mixed  Value of the data field.
     */
    function getAuthData($name = null)
    {
        $session = &DOLIAuth::_importGlobalVariable("session");

        if (is_null($name)) {
            return $session['auth']['data'];
        }
        if (isset($session['auth']['data'][$name])) {
            return $session['auth']['data'][$name];
        } else {
            return null;
        }        
    }
    
    // }}}
    // {{{ setAuth()

    /**
     * Register variable in a session telling that the user
     * has logged in successfully
     *
     * @access public
     * @param  string Username
     * @return void
     */
    function setAuth($username)
    {
        $session = &DOLIAuth::_importGlobalVariable("session");

        if (!isset($session['auth']) && !isset($_SESSION)) {
            session_register("auth");
        }

        if (!isset($session['auth']) || !is_array($session['auth'])) {
            $session['auth'] = array();
        }

        if(!isset($session['auth']['data'])){
            $session['auth']['data']       = array();
        }
        $session['auth']['registered'] = true;
        $session['auth']['username']   = $username;
        $session['auth']['timestamp']  = time();
        $session['auth']['idle']       = time();
    }
    
    // }}}
    // {{{ checkAuth()

    /**
     * Checks if there is a session with valid auth information.
     *
     * @access private
     * @return boolean  Whether or not the user is authenticated.
     */
    function checkAuth()
    {
        $session = &$this->_importGlobalVariable("session");

        if (isset($session['auth'])) {
            /** Check if authentication session is expired */
            if ($this->expire > 0 &&
                isset($session['auth']['timestamp']) &&
                ($session['auth']['timestamp'] + $this->expire) < time()) {

                $this->logout();
                $this->expired = true;
                $this->status = AUTH_EXPIRED;

                return false;
            }

            /** Check if maximum idle time is reached */
            if ($this->idle > 0 &&
                isset($session['auth']['idle']) &&
                ($session['auth']['idle'] + $this->idle) < time()) {

                $this->logout();
                $this->idled = true;
                $this->status = AUTH_IDLED;

                return false;
            }

            if (isset($session['auth']['registered']) &&
                isset($session['auth']['username']) &&
                $session['auth']['registered'] == true &&
                $session['auth']['username'] != "") {

	      DOLIAuth::updateIdle();

                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ getAuth()

    /**
     * Has the user been authenticated?
     *
     * @access public
     * @return bool  True if the user is logged in, otherwise false.
     */
    function getAuth()
    {
        $session = &$this->_importGlobalVariable("session");

        if (!empty($session) && 
            (isset($session['auth']['registered']) && 
             $session['auth']['registered'] === true))
        {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ drawLogin()

    /**
     * Draw the login form
     *
     * Normally you will not use this output in your application,
     * because you can pass a different function name to the
     * constructor. For more information on this, please
     * consult the documentation.
     *
     * @access private
     * @param  string  Username if already entered
     * @return void
     */
    function drawLogin($username = "")
    {
        if ($this->loginFunction != "") {
            call_user_func($this->loginFunction, $username, $this->status);
        } else {
            $server = &$this->_importGlobalVariable("server");

            echo "<center>\n";

            if (!empty($this->status) && $this->status == AUTH_EXPIRED) {
                echo "<i>Your session expired. Please login again!</i>\n";
            } else if (!empty($this->status) && $this->status == AUTH_IDLED) {
                echo "<i>You have been idle for too long. Please login again!</i>\n";
            } else if (!empty ($this->status) && $this->status == AUTH_WRONG_LOGIN) {
                echo "<i>Wrong login data!</i>\n";
            }

            DOLIPEAR::raiseError("You are using the built-in login screen of PEAR::Auth.<br/>See the <a href=\"http://pear.php.net/manual/\">manual</a> for details on how to create your own login function.", null);
                    
            echo "<form method=\"post\" action=\"" . $server['PHP_SELF'] . "\">\n";
            echo "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
            echo "<tr>\n";
            echo "    <td colspan=\"2\" bgcolor=\"#eeeeee\"><b>Login:</b></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "    <td>Username:</td>\n";
            echo "    <td><input type=\"text\" name=\"username\" value=\"" . $username . "\"></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "    <td>Password:</td>\n";
            echo "    <td><input type=\"password\" name=\"password\"></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "    <td colspan=\"2\" bgcolor=\"#eeeeee\"><input type=\"submit\"></td>\n";
            echo "</tr>\n";
            echo "</table>\n";
            echo "</form>\n";
            echo "</center>\n\n";
        }
    }

    // }}}
    // {{{ logout()

    /**
     * Logout function
     *
     * This function clears any auth tokens in the currently
     * active session and executes the logout callback function,
     * if any
     *
     * @access public
     * @return void
     */
    function logout()
    {
        $session = &$this->_importGlobalVariable("session");
 
        if (!empty($this->logoutCallback)) {
            call_user_func($this->logoutCallback, $session['auth']['username']);
        }
        
        $this->username = "";
        $this->password = "";

        $session['auth'] = array();
        if (isset($_SESSION)) {
            unset($session['auth']);
        } else {
            session_unregister("auth");
        }        
    }

    // }}}
    // {{{ updateIdle()

    /**
     * Update the idletime
     *
     * @access private
     * @return void
     */
    function updateIdle()
    {
        $session = &$this->_importGlobalVariable("session");
        $session['auth']['idle'] = time();
    }

    // }}}
    // {{{ getUsername()

    /**
     * Get the username
     *
     * @access public
     * @return string
     */
    function getUsername()
    {
        $session = &$this->_importGlobalVariable("session");
        if (!isset($session['auth']['username'])) {
            return "";
        }
        return $session['auth']['username'];
    }

    // }}}
    // {{{ getStatus()

    /**
     * Get the current status
     *
     * @access public
     * @return string
     */
    function getStatus()
    {
        return $this->status;
    }

    // }}}
    // {{{ sessionValidThru()

    /**
     * Returns the time up to the session is valid
     *
     * @access public
     * @return integer
     */
    function sessionValidThru()
    {
        $session = &$this->_importGlobalVariable("session");
        if (!isset($session['auth']['idle'])) {
            return 0;
        }
        return ($session['auth']['idle'] + $this->idle);
    }

    // }}}
    // {{{ listUsers()

    /**
     * List all users that are currently available in the storage
     * container
     *
     * @access public
     * @return array
     */
    function listUsers()
    {
        return $this->storage->listUsers();
    }

    // }}}
    // {{{ addUser()

    /**
     * Add user to the storage container
     *
     * @access public
     * @param  string Username
     * @param  string Password
     * @param  mixed  Additional parameters
     * @return mixed  True on success, PEAR error object on error
     *                and AUTH_METHOD_NOT_SUPPORTED otherwise.
     */    
    function addUser($username, $password, $additional = "")
    {
        return $this->storage->addUser($username, $password, $additional);
    }

    // }}}
    // {{{ removeUser()

    /**
     * Remove user from the storage container
     *
     * @access public
     * @param string Username
     * @return mixed  True on success, PEAR error object on error
     *                and AUTH_METHOD_NOT_SUPPORTED otherwise.
     */
    function removeUser($username)
    {
        return $this->storage->removeUser($username);
    }

    // }}}
    // {{{ _importGlobalVariable()

    /**
     * Import variables from special namespaces.
     *
     * @access private
     * @param string Type of variable (server, session, post)
     * @return array
     */
    function &_importGlobalVariable($variable) 
    {
        $var = null;

        switch (strtolower($variable)) {

            case "server" :
                if (isset($_SERVER)) {
                    $var = &$_SERVER;
                } else {
                    $var = &$GLOBALS['HTTP_SERVER_VARS'];
                }
                break;

            case "session" :
                if (isset($_SESSION)) {
                    $var = &$_SESSION;
                } else {
                    $var = &$GLOBALS['HTTP_SESSION_VARS'];
                }
                break;

            case "post" :
                if (isset($_POST)) {
                    $var = &$_POST;
                } else {
                    $var = &$GLOBALS['HTTP_POST_VARS'];
                }
                break;

            case "cookie" :
                if (isset($_COOKIE)) {
                    $var = &$_COOKIE;
                } else {
                    $var = &$GLOBALS['HTTP_COOKIE_VARS'];
                }
                break;

            case "get" :
                if (isset($_GET)) {
                    $var = &$_GET;
                } else {
                    $var = &$GLOBALS['HTTP_GET_VARS'];
                }
                break;

            default:
                break;

        }

        return $var;
    } 

    // }}}
}
?>
