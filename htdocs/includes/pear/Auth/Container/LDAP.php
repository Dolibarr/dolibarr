<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,       |
// | that is bundled with this package in the file LICENSE,  and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web,  please send a note to          |
// | license <at> php <dot> net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jan Wagner <wagner <at> netsols <dot> de>                              |
// +----------------------------------------------------------------------+
//
// $Id$
//

// require_once 'PEAR.php';
// require_once 'Auth/Container.php';
require_once PEAR_PATH."/PEAR.php";
require_once PEAR_PATH."/Auth/Container.php";

/**
 * Storage driver for fetching login data from LDAP
 *
 * This class is heavily based on the DB and File containers. By default it
 * connects to localhost:389 and searches for uid=$username with the scope
 * "sub". If no search base is specified,  it will try to determine it via
 * the namingContexts attribute. It takes its parameters in a hash,  connects
 * to the ldap server,  binds anonymously,  searches for the user,  and tries
 * to bind as the user with the supplied password. When a group was set,  it
 * will look for group membership of the authenticated user. If all goes
 * well the authentication was successful.
 *
 * Parameters:
 *
 * host:        localhost (default),  ldap.netsols.de or 127.0.0.1
 * port:        389 (default) or 636 or whereever your server runs
 * url:         ldap://localhost:389/
 *              useful for ldaps://,  works only with openldap2 ?
 *              it will be preferred over host and port
 * version:     LDAP version to use,  ususally 2 (default) or 3,
 *              must be an integer!
 * binddn:      If set,  searching for user will be done after binding
 *              as this user,  if not set the bind will be anonymous.
 *              This is reported to make the container work with MS
 *              Active Directory,  but should work with any server that
 *              is configured this way.
 *              This has to be a complete dn for now (basedn and
 *              userdn will not be appended).
 * bindpw:      The password to use for binding with binddn
 * basedn:      the base dn of your server
 * userdn:      gets prepended to basedn when searching for user
 * userscope:   Scope for user searching: one,  sub (default),  or base
 * userattr:    the user attribute to search for (default: uid)
 * userfilter:  filter that will be added to the search filter
 *              this way: (&(userattr=username)(userfilter))
 *              default: (objectClass=posixAccount)
 * attributes:  array of additional attributes to fetch from entry.
 *              these will added to auth data and can be retrieved via
 *              Auth::getAuthData(). An empty array will fetch all attributes,
 *              array('') will fetch no attributes at all (default)
 * groupdn:     gets prepended to basedn when searching for group
 * groupattr:   the group attribute to search for (default: cn)
 * groupfilter: filter that will be added to the search filter when
 *              searching for a group:
 *              (&(groupattr=group)(memberattr=username)(groupfilter))
 *              default: (objectClass=groupOfUniqueNames)
 * memberattr : the attribute of the group object where the user dn
 *              may be found (default: uniqueMember)
 * memberisdn:  whether the memberattr is the dn of the user (default)
 *              or the value of userattr (usually uid)
 * group:       the name of group to search for
 * groupscope:  Scope for group searching: one,  sub (default),  or base
 * debug:       Enable/Disable debugging output (default: false)
 *
 * To use this storage container,  you have to use the following syntax:
 *
 * <?php
 * ...
 *
 * $a = new Auth("LDAP",  array(
 *       'host' => 'localhost',
 *       'port' => '389',
 *       'version' => 3,
 *       'basedn' => 'o=netsols, c=de',
 *       'userattr' => 'uid'
 *       'binddn' => 'cn=admin, o=netsols, c=de',
 *       'bindpw' => 'password'));
 *
 * $a2 = new Auth('LDAP',  array(
 *       'url' => 'ldaps://ldap.netsols.de',
 *       'basedn' => 'o=netsols, c=de',
 *       'userscope' => 'one',
 *       'userdn' => 'ou=People',
 *       'groupdn' => 'ou=Groups',
 *       'groupfilter' => '(objectClass=posixGroup)',
 *       'memberattr' => 'memberUid',
 *       'memberisdn' => false,
 *       'group' => 'admin'
 *       ));
 *
 * This is a full blown example with user/group checking to an Active Directory
 *
 * $a3 = new Auth('LDAP',  array(
 *       'host' => 'ldap.netsols.de',
 *       'port' => 389,
 *       'version' => 3,
 *       'basedn' => 'dc=netsols, dc=de',
 *       'binddn' => 'cn=Jan Wagner, cn=Users, dc=netsols, dc=de',
 *       'bindpw' => 'password',
 *       'userattr' => 'samAccountName',
 *       'userfilter' => '(objectClass=user)',
 *       'attributes' => array(''),
 *       'group' => 'testing',
 *       'groupattr' => 'samAccountName',
 *       'groupfilter' => '(objectClass=group)',
 *       'memberattr' => 'member',
 *       'memberisdn' => true,
 *       'groupdn' => 'cn=Users',
 *       'groupscope' => 'one',
 *       'debug' => true);
 *
 * The parameter values have to correspond
 * to the ones for your LDAP server of course.
 *
 * When talking to a Microsoft ActiveDirectory server you have to
 * use 'samaccountname' as the 'userattr' and follow special rules
 * to translate the ActiveDirectory directory names into 'basedn'.
 * The 'basedn' for the default 'Users' folder on an ActiveDirectory
 * server for the ActiveDirectory Domain (which is not related to
 * its DNS name) "win2000.example.org" would be:
 * "CN=Users,  DC=win2000,  DC=example,  DC=org'
 * where every component of the domain name becomes a DC attribute
 * of its own. If you want to use a custom users folder you have to
 * replace "CN=Users" with a sequence of "OU" attributes that specify
 * the path to your custom folder in reverse order.
 * So the ActiveDirectory folder
 *   "win2000.example.org\Custom\Accounts"
 * would become
 *   "OU=Accounts,  OU=Custom,  DC=win2000,  DC=example,  DC=org'
 *
 * It seems that binding anonymously to an Active Directory
 * is not allowed,  so you have to set binddn and bindpw for
 * user searching,
 *
 * Example a3 shows a tested example for connenction to Windows 2000
 * Active Directory
 *
 * @author   Jan Wagner <wagner <at> netsols <dot> de>
 * @package  Auth
 * @version  $Revision$
 */
class Auth_Container_LDAP extends Auth_Container
{
    /**
     * Options for the class
     * @var array
     */
    var $options = array();

    /**
     * Connection ID of LDAP Link
     * @var string
     */
    var $conn_id = false;

    /**
     * Constructor of the container class
     *
     * @param  $params,  associative hash with host, port, basedn and userattr key
     * @return object Returns an error object if something went wrong
     */
    function Auth_Container_LDAP($params)
    {
        if (false === extension_loaded('ldap')) {
            return DOLIPEAR::raiseError('Auth_Container_LDAP: LDAP Extension not loaded',  41,  PEAR_ERROR_DIE);
        }
        
        $this->_setDefaults();

        if (is_array($params)) {
            $this->_parseOptions($params);
        }
    }

    // }}}
    // {{{ _connect()

    /**
     * Connect to the LDAP server using the global options
     *
     * @access private
     * @return object  Returns a PEAR error object if an error occurs.
     */
    function _connect()
    {
        // connect
        if (isset($this->options['url']) && $this->options['url'] != '') {
            $this->_debug('Connecting with URL',  __LINE__);
            $conn_params = array($this->options['url']);
        } else {
            $this->_debug('Connecting with host:port',  __LINE__);
            $conn_params = array($this->options['host'],  $this->options['port']);
        }

        if (($this->conn_id = @call_user_func_array('ldap_connect',  $conn_params)) === false) {
            return DOLIPEAR::raiseError('Auth_Container_LDAP: Could not connect to server.',  41,  PEAR_ERROR_DIE);
        }
        $this->_debug('Successfully connected to server',  __LINE__);

        // switch LDAP version
        if (is_int($this->options['version']) && $this->options['version'] > 2) {           
            $this->_debug("Switching to LDAP version {$this->options['version']}",  __LINE__);
            @ldap_set_option($this->conn_id,  LDAP_OPT_PROTOCOL_VERSION,  $this->options['version']);
        }

        // bind with credentials or anonymously
        if ($this->options['binddn'] && $this->options['bindpw']) {
            $this->_debug('Binding with credentials',  __LINE__);
            $bind_params = array($this->conn_id,  $this->options['binddn'],  $this->options['bindpw']);
        } else {
            $this->_debug('Binding anonymously',  __LINE__);
            $bind_params = array($this->conn_id);
        }        
        // bind for searching
        if ((@call_user_func_array('ldap_bind',  $bind_params)) == false) {
            $this->_debug();
            $this->_disconnect();
            return DOLIPEAR::raiseError("Auth_Container_LDAP: Could not bind to LDAP server.",  41,  PEAR_ERROR_DIE);
        }
        $this->_debug('Binding was successful',  __LINE__);
    }

    /**
     * Disconnects (unbinds) from ldap server
     *
     * @access private
     */
    function _disconnect() 
    {
        if ($this->_isValidLink()) {
            $this->_debug('disconnecting from server');
            @ldap_unbind($this->conn_id);
        }
    }

    /**
     * Tries to find Basedn via namingContext Attribute
     *
     * @access private
     */
    function _getBaseDN()
    {
        if ($this->options['basedn'] == "" && $this->_isValidLink()) {           
            $this->_debug("basedn not set,  searching via namingContexts.",  __LINE__);

            $result_id = @ldap_read($this->conn_id,  "",  "(objectclass=*)",  array("namingContexts"));
            
            if (@ldap_count_entries($this->conn_id,  $result_id) == 1) {
                
                $this->_debug("got result for namingContexts",  __LINE__);
                
                $entry_id = @ldap_first_entry($this->conn_id,  $result_id);
                $attrs = @ldap_get_attributes($this->conn_id,  $entry_id);
                $basedn = $attrs['namingContexts'][0];

                if ($basedn != "") {
                    $this->_debug("result for namingContexts was $basedn",  __LINE__);
                    $this->options['basedn'] = $basedn;
                }
            }
            @ldap_free_result($result_id);
        }

        // if base ist still not set,  raise error
        if ($this->options['basedn'] == "") {
            return DOLIPEAR::raiseError("Auth_Container_LDAP: LDAP search base not specified!",  41,  PEAR_ERROR_DIE);
        }        
        return true;
    }

    /**
     * determines whether there is a valid ldap conenction or not
     *
     * @accessd private
     * @return boolean
     */
    function _isValidLink() 
    {
        if (is_resource($this->conn_id)) {
            if (get_resource_type($this->conn_id) == 'ldap link') {
                return true;
            }
        }
        return false;
    }

    /**
     * Set some default options
     *
     * @access private
     */
    function _setDefaults()
    {        
        $this->options['url']         = '';
        $this->options['host']        = 'localhost';
        $this->options['port']        = '389';
        $this->options['version']     = 2;
        $this->options['binddn']      = '';
        $this->options['bindpw']      = '';        
        $this->options['basedn']      = '';
        $this->options['userdn']      = '';
        $this->options['userscope']   = 'sub';
        $this->options['userattr']    = "uid";
        $this->options['userfilter']  = '(objectClass=posixAccount)';
        $this->options['attributes']  = array(''); // no attributes
        $this->options['group']       = '';
        $this->options['groupdn']     = '';
        $this->options['groupscope']  = 'sub';
        $this->options['groupattr']   = 'cn';
        $this->options['groupfilter'] = '(objectClass=groupOfUniqueNames)';
        $this->options['memberattr']  = 'uniqueMember';
        $this->options['memberisdn']  = true;
        $this->options['debug']       = false;
    }

    /**
     * Parse options passed to the container class
     *
     * @access private
     * @param  array
     */
    function _parseOptions($array)
    {
        foreach ($array as $key => $value) {
            if (array_key_exists($key,  $this->options)) {
                $this->options[$key] = $value;
            }
        }
    }
    
    /**
     * Get search function for scope
     *
     * @param  string scope
     * @return string ldap search function
     */
    function _scope2function($scope)
    {
        switch($scope) {
        case 'one':
            $function = 'ldap_list';
            break;
        case 'base':
            $function = 'ldap_read';
            break;
        default:
            $function = 'ldap_search';
            break;
        }
        return $function;
    }

    /**
     * Fetch data from LDAP server
     *
     * Searches the LDAP server for the given username/password
     * combination.
     *
     * @param  string Username
     * @param  string Password
     * @return boolean
     */
    function fetchData($username,  $password)
    {                
        $this->_connect();
        $this->_getBaseDN();

        // UTF8 Encode username for LDAPv3
        if (@ldap_get_option($this->conn_id,  LDAP_OPT_PROTOCOL_VERSION,  $ver) && $ver == 3) {
            $this->_debug('UTF8 encoding username for LDAPv3',  __LINE__);
            $username = utf8_encode($username);
        }
        // make search filter
        $filter = sprintf('(&(%s=%s)%s)',
                          $this->options['userattr'],
                          $username,
                          $this->options['userfilter']);
        // make search base dn
        $search_basedn = $this->options['userdn'];
        if ($search_basedn != '' && substr($search_basedn,  -1) != ', ') {
            $search_basedn .= ', ';
        }
        $search_basedn .= $this->options['basedn'];

        // attributes
        $attributes = $this->options['attributes'];

        // make functions params array
        $func_params = array($this->conn_id,  $search_basedn,  $filter,  $attributes);

        // search function to use
        $func_name = $this->_scope2function($this->options['userscope']);
        
        $this->_debug("Searching with $func_name and filter $filter in $search_basedn",  __LINE__);

        // search
        if (($result_id = @call_user_func_array($func_name,  $func_params)) == false) {
            $this->_debug('User not found',  __LINE__);
        } elseif (@ldap_count_entries($this->conn_id,  $result_id) == 1) { // did we get just one entry?

            $this->_debug('User was found',  __LINE__);
            
            // then get the user dn
            $entry_id = @ldap_first_entry($this->conn_id,  $result_id);
            $user_dn  = @ldap_get_dn($this->conn_id,  $entry_id);

            // fetch attributes
            if ($attributes = @ldap_get_attributes($this->conn_id,  $entry_id)) {
                if (is_array($attributes) && isset($attributes['count']) &&
                     $attributes['count'] > 0)
                {
                    $this->_debug('Saving attributes to Auth data',  __LINE__);
                    DOLIAUTH::setAuthData('attributes',  $attributes);
                }
            }
            @ldap_free_result($result_id);

            // need to catch an empty password as openldap seems to return TRUE
            // if anonymous binding is allowed
            if ($password != "") {
                $this->_debug("Bind as $user_dn",  __LINE__);                

                // try binding as this user with the supplied password
                if (@ldap_bind($this->conn_id,  $user_dn,  $password)) {
                    $this->_debug('Bind successful',  __LINE__);

                    // check group if appropiate
                    if (strlen($this->options['group'])) {
                        // decide whether memberattr value is a dn or the username
                        $this->_debug('Checking group membership',  __LINE__);
                        return $this->checkGroup(($this->options['memberisdn']) ? $user_dn : $username);
                    } else {
                        $this->_debug('Authenticated',  __LINE__);
                        $this->_disconnect();
                        return true; // user authenticated
                    } // checkGroup
                } // bind
            } // non-empty password
        } // one entry
        // default
        $this->_debug('NOT authenticated!',  __LINE__);
        $this->_disconnect();
        return false;
    }

    /**
     * Validate group membership
     *
     * Searches the LDAP server for group membership of the
     * authenticated user
     *
     * @param  string Distinguished Name of the authenticated User
     * @return boolean
     */
    function checkGroup($user) 
    {
        // make filter
        $filter = sprintf('(&(%s=%s)(%s=%s)%s)',
                          $this->options['groupattr'],
                          $this->options['group'],
                          $this->options['memberattr'],
                          $user,
                          $this->options['groupfilter']);

        // make search base dn
        $search_basedn = $this->options['groupdn'];
        if ($search_basedn != '' && substr($search_basedn,  -1) != ', ') {
            $search_basedn .= ', ';
        }
        $search_basedn .= $this->options['basedn'];
        
        $func_params = array($this->conn_id,  $search_basedn,  $filter,
                             array($this->options['memberattr']));
        $func_name = $this->_scope2function($this->options['groupscope']);

        $this->_debug("Searching with $func_name and filter $filter in $search_basedn",  __LINE__);
        
        // search
        if (($result_id = @call_user_func_array($func_name,  $func_params)) != false) {
            if (@ldap_count_entries($this->conn_id,  $result_id) == 1) {                
                @ldap_free_result($result_id);
                $this->_debug('User is member of group',  __LINE__);
                $this->_disconnect();
                return true;
            }
        }
        // default
        $this->_debug('User is NOT member of group',  __LINE__);
        $this->_disconnect();
        return false;
    }

    /**
     * Outputs debugging messages
     *
     * @access private
     * @param string Debugging Message
     * @param integer Line number
     */
    function _debug($msg = '',  $line = 0)
    {
        if ($this->options['debug'] === true) {
            if ($msg == '' && $this->_isValidLink()) {
                $msg = 'LDAP_Error: ' . @ldap_err2str(@ldap_errno($this->_conn_id));
            }
            print("$line: $msg <br />");
        }
    }
}

?>