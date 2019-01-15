<?php
/* Copyright (C) 2004		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004		Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2015	Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file 		htdocs/core/class/ldap.class.php
 *	\brief 		File of class to manage LDAP features
 */

/**
 *	Class to manage LDAP features
 */
class Ldap
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * Tableau des serveurs (IP addresses ou nom d'hotes)
	 */
	var $server=array();

	/**
	 * Base DN (e.g. "dc=foo,dc=com")
	 */
	var $dn;
	/**
	 * type de serveur, actuellement OpenLdap et Active Directory
	 */
	var $serverType;
	/**
	 * Version du protocole ldap
	 */
	var $domain;
	/**
	 * User administrateur Ldap
	 * Active Directory ne supporte pas les connexions anonymes
	 */
	var $searchUser;
	/**
	 * Mot de passe de l'administrateur
	 * Active Directory ne supporte pas les connexions anonymes
	 */
	var $searchPassword;
	/**
	 *  DN des utilisateurs
	 */
	var $people;
	/**
	 * DN des groupes
	 */
	var $groups;
	/**
	 * Code erreur retourne par le serveur Ldap
	 */
	var $ldapErrorCode;
	/**
	 * Message texte de l'erreur
	 */
	var $ldapErrorText;


	//Fetch user
	var $name;
	var $firstname;
	var $login;
	var $phone;
	var $skype;
	var $fax;
	var $mail;
	var $mobile;

	var $uacf;
	var $pwdlastset;

	var $ldapcharset='UTF-8';	// LDAP should be UTF-8 encoded


	/**
	 * The internal LDAP connection handle
	 */
	var $connection;
	/**
	 * Result of any connections etc.
	 */
	var $result;


	/**
	 *  Constructor
	 */
	function __construct()
	{
		global $conf;

		// Server
		if (! empty($conf->global->LDAP_SERVER_HOST))       $this->server[] = $conf->global->LDAP_SERVER_HOST;
		if (! empty($conf->global->LDAP_SERVER_HOST_SLAVE)) $this->server[] = $conf->global->LDAP_SERVER_HOST_SLAVE;
		$this->serverPort          = $conf->global->LDAP_SERVER_PORT;
		$this->ldapProtocolVersion = $conf->global->LDAP_SERVER_PROTOCOLVERSION;
		$this->dn                  = $conf->global->LDAP_SERVER_DN;
		$this->serverType          = $conf->global->LDAP_SERVER_TYPE;
		$this->domain              = $conf->global->LDAP_SERVER_DN;
		$this->searchUser          = $conf->global->LDAP_ADMIN_DN;
		$this->searchPassword      = $conf->global->LDAP_ADMIN_PASS;
		$this->people              = $conf->global->LDAP_USER_DN;
		$this->groups              = $conf->global->LDAP_GROUP_DN;

		$this->filter              = $conf->global->LDAP_FILTER_CONNECTION;	// Filter on user
		$this->filtermember        = $conf->global->LDAP_MEMBER_FILTER;		// Filter on member

		// Users
		$this->attr_login      = $conf->global->LDAP_FIELD_LOGIN; //unix
		$this->attr_sambalogin = $conf->global->LDAP_FIELD_LOGIN_SAMBA; //samba, activedirectory
		$this->attr_name       = $conf->global->LDAP_FIELD_NAME;
		$this->attr_firstname  = $conf->global->LDAP_FIELD_FIRSTNAME;
		$this->attr_mail       = $conf->global->LDAP_FIELD_MAIL;
		$this->attr_phone      = $conf->global->LDAP_FIELD_PHONE;
		$this->attr_skype      = $conf->global->LDAP_FIELD_SKYPE;
		$this->attr_fax        = $conf->global->LDAP_FIELD_FAX;
		$this->attr_mobile     = $conf->global->LDAP_FIELD_MOBILE;
	}



	// Connection handling methods -------------------------------------------

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Connect and bind
	 * 	Use this->server, this->serverPort, this->ldapProtocolVersion, this->serverType, this->searchUser, this->searchPassword
	 * 	After return, this->connection and $this->bind are defined
	 *
	 *	@return		int		<0 if KO, 1 if bind anonymous, 2 if bind auth
	 */
	function connect_bind()
	{
        // phpcs:enable
		global $langs, $conf;

		$connected=0;
		$this->bind=0;

		// Check parameters
		if (count($this->server) == 0 || empty($this->server[0]))
		{
			$this->error='LDAP setup (file conf.php) is not complete';
			dol_syslog(get_class($this)."::connect_bind ".$this->error, LOG_WARNING);
			return -1;
		}

		if (! function_exists("ldap_connect"))
		{
			$this->error='LDAPFunctionsNotAvailableOnPHP';
			dol_syslog(get_class($this)."::connect_bind ".$this->error, LOG_WARNING);
			$return=-1;
		}

		if (empty($this->error))
		{
			// Loop on each ldap server
			foreach ($this->server as $key => $host)
			{
				if ($connected) break;
				if (empty($host)) continue;

				if ($this->serverPing($host, $this->serverPort) === true) {
					$this->connection = ldap_connect($host, $this->serverPort);
				}
				else continue;

				if (is_resource($this->connection))
				{
					// Begin TLS if requested by the configuration
					if (! empty($conf->global->LDAP_SERVER_USE_TLS))
					{
						if (! ldap_start_tls($this->connection))
						{
							dol_syslog(get_class($this)."::connect_bind failed to start tls", LOG_WARNING);
							$connected = 0;
							$this->close();
						}
					}

					// Execute the ldap_set_option here (after connect and before bind)
					$this->setVersion();
					ldap_set_option($this->connection, LDAP_OPT_SIZELIMIT, 0); // no limit here. should return true.


					if ($this->serverType == "activedirectory")
					{
						$result=$this->setReferrals();
						dol_syslog(get_class($this)."::connect_bind try bindauth for activedirectory on ".$host." user=".$this->searchUser." password=".preg_replace('/./','*',$this->searchPassword),LOG_DEBUG);
						$this->result=$this->bindauth($this->searchUser,$this->searchPassword);
						if ($this->result)
						{
							$this->bind=$this->result;
							$connected=2;
							break;
						}
						else
						{
							$this->error=ldap_errno($this->connection).' '.ldap_error($this->connection);
						}
					}
					else
					{
						// Try in auth mode
						if ($this->searchUser && $this->searchPassword)
						{
							dol_syslog(get_class($this)."::connect_bind try bindauth on ".$host." user=".$this->searchUser." password=".preg_replace('/./','*',$this->searchPassword),LOG_DEBUG);
							$this->result=$this->bindauth($this->searchUser,$this->searchPassword);
							if ($this->result)
							{
								$this->bind=$this->result;
								$connected=2;
								break;
							}
							else
							{
								$this->error=ldap_errno($this->connection).' '.ldap_error($this->connection);
							}
						}
						// Try in anonymous
						if (! $this->bind)
						{
							dol_syslog(get_class($this)."::connect_bind try bind on ".$host,LOG_DEBUG);
							$result=$this->bind();
							if ($result)
							{
								$this->bind=$this->result;
								$connected=1;
								break;
							}
							else
							{
								$this->error=ldap_errno($this->connection).' '.ldap_error($this->connection);
							}
						}
					}
				}

				if (! $connected) $this->close();
			}
		}

		if ($connected)
		{
			$return=$connected;
			dol_syslog(get_class($this)."::connect_bind return=".$return, LOG_DEBUG);
		}
		else
		{
			$this->error='Failed to connect to LDAP'.($this->error?': '.$this->error:'');
			$return=-1;
			dol_syslog(get_class($this)."::connect_bind return=".$return.' - '.$this->error, LOG_WARNING);
		}
		return $return;
	}



	/**
	 * Simply closes the connection set up earlier.
	 * Returns true if OK, false if there was an error.
	 *
	 * @return	boolean			true or false
	 */
	function close()
	{
		if ($this->connection && ! @ldap_close($this->connection))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Anonymously binds to the connection. After this is done,
	 * queries and searches can be done - but read-only.
	 *
	 * @return	boolean			true or false
	 */
	function bind()
	{
		if (! $this->result=@ldap_bind($this->connection))
		{
			$this->ldapErrorCode = ldap_errno($this->connection);
			$this->ldapErrorText = ldap_error($this->connection);
			$this->error=$this->ldapErrorCode." ".$this->ldapErrorText;
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Binds as an authenticated user, which usually allows for write
	 * access. The FULL dn must be passed. For a directory manager, this is
	 * "cn=Directory Manager" under iPlanet. For a user, it will be something
	 * like "uid=jbloggs,ou=People,dc=foo,dc=com".
	 *
	 * @param	string	$bindDn			DN
	 * @param	string	$pass			Password
	 * @return	boolean					true or false
	 */
	function bindauth($bindDn,$pass)
	{
		if (! $this->result = @ldap_bind($this->connection, $bindDn, $pass))
		{
			$this->ldapErrorCode = ldap_errno($this->connection);
			$this->ldapErrorText = ldap_error($this->connection);
			$this->error=$this->ldapErrorCode." ".$this->ldapErrorText;
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Unbind du serveur ldap.
	 *
	 * @return	boolean					true or false
	 */
	function unbind()
	{
		if (!$this->result=@ldap_unbind($this->connection))
		{
			return false;
		} else {
			return true;
		}
	}


	/**
	 * Verification de la version du serveur ldap.
	 *
	 * @return	string					version
	 */
	function getVersion()
	{
		$version = 0;
		$version = @ldap_get_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $version);
		return $version;
	}

	/**
	 * Change ldap protocol version to use.
	 *
	 * @return	boolean					version
	 */
    function setVersion()
    {
		// LDAP_OPT_PROTOCOL_VERSION est une constante qui vaut 17
		$ldapsetversion = ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldapProtocolVersion);
		return $ldapsetversion;
	}

	/**
	 * changement du referrals.
	 *
	 * @return	boolean					referrals
	 */
    function setReferrals()
    {
		// LDAP_OPT_REFERRALS est une constante qui vaut ?
		$ldapreferrals = ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
		return $ldapreferrals;
	}


	/**
	 * 	Add a LDAP entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string	$dn			DN entry key
	 *	@param	array	$info		Attributes array
	 *	@param	User		$user		Objet user that create
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function add($dn, $info, $user)
	{
		global $conf;

		dol_syslog(get_class($this)."::add dn=".$dn." info=".join(',',$info));

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);
		foreach($info as $key => $val)
		{
			if (! is_array($val)) $info[$key]=$this->convFromOutputCharset($val,$this->ldapcharset);
		}

		$this->dump($dn,$info);

		//print_r($info);
		$result=@ldap_add($this->connection, $dn, $info);

		if ($result)
		{
			dol_syslog(get_class($this)."::add successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->ldapErrorCode = @ldap_errno($this->connection);
			$this->ldapErrorText = @ldap_error($this->connection);
			$this->error=$this->ldapErrorCode." ".$this->ldapErrorText;
			dol_syslog(get_class($this)."::add failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	Modify a LDAP entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string		$dn			DN entry key
	 *	@param	array		$info		Attributes array
	 *	@param	User			$user		Objet user that modify
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function modify($dn, $info, $user)
	{
		global $conf;

		dol_syslog(get_class($this)."::modify dn=".$dn." info=".join(',',$info));

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);
		foreach($info as $key => $val)
		{
			if (! is_array($val)) $info[$key]=$this->convFromOutputCharset($val,$this->ldapcharset);
		}

		$this->dump($dn,$info);

		//print_r($info);
		$result=@ldap_modify($this->connection, $dn, $info);

		if ($result)
		{
			dol_syslog(get_class($this)."::modify successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog(get_class($this)."::modify failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	Rename a LDAP entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string		$dn				Old DN entry key (uid=qqq,ou=xxx,dc=aaa,dc=bbb) (before update)
	 *	@param	string		$newrdn			New RDN entry key (uid=qqq)
	 *	@param	string		$newparent		New parent (ou=xxx,dc=aaa,dc=bbb)
	 *	@param	User			$user			Objet user that modify
	 *	@param	bool			$deleteoldrdn	If true the old RDN value(s) is removed, else the old RDN value(s) is retained as non-distinguished values of the entry.
	 *	@return	int							<0 if KO, >0 if OK
	 */
	function rename($dn, $newrdn, $newparent, $user, $deleteoldrdn = true)
	{
		global $conf;

		dol_syslog(get_class($this)."::modify dn=".$dn." newrdn=".$newrdn." newparent=".$newparent." deleteoldrdn=".($deleteoldrdn?1:0));

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);
		$newrdn=$this->convFromOutputCharset($newrdn,$this->ldapcharset);
		$newparent=$this->convFromOutputCharset($newparent,$this->ldapcharset);

		//print_r($info);
		$result=@ldap_rename($this->connection, $dn, $newrdn, $newparent, $deleteoldrdn);

		if ($result)
		{
			dol_syslog(get_class($this)."::rename successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog(get_class($this)."::rename failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Modify a LDAP entry (to use if dn != olddn)
	 *	Ldap object connect and bind must have been done
	 *
	 *  @param	string	$dn			DN entry key
	 *  @param	array	$info		Attributes array
	 *  @param	User		$user		Objet user that update
	 * 	@param	string	$olddn		Old DN entry key (before update)
	 * 	@param	string	$newrdn		New RDN entry key (uid=qqq) (for ldap_rename)
	 *	@param	string	$newparent	New parent (ou=xxx,dc=aaa,dc=bbb) (for ldap_rename)
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function update($dn, $info, $user, $olddn, $newrdn=false, $newparent=false)
	{
		global $conf;

		dol_syslog(get_class($this)."::update dn=".$dn." olddn=".$olddn);

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		if (! $olddn || $olddn != $dn)
		{
			if (! empty($olddn) && ! empty($newrdn) && ! empty($newparent) && $conf->global->LDAP_SERVER_PROTOCOLVERSION === '3')
			{
				// This function currently only works with LDAPv3
				$result = $this->rename($olddn, $newrdn, $newparent, $user, true);
			}
			else
			{
				// If change we make is rename the key of LDAP record, we create new one and if ok, we delete old one.
				$result = $this->add($dn, $info, $user);
				if ($result > 0 && $olddn && $olddn != $dn) $result = $this->delete($olddn);	// If add fails, we do not try to delete old one
			}
		}
		else
		{
			//$result = $this->delete($olddn);
			$result = $this->add($dn, $info, $user);	// If record has been deleted from LDAP, we recreate it. We ignore error if it already exists.
			$result = $this->modify($dn, $info, $user);	// We use add/modify instead of delete/add when olddn is received
		}
		if ($result <= 0)
		{
			$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection)." ".$this->error;
			dol_syslog(get_class($this)."::update ".$this->error,LOG_ERR);
			//print_r($info);
			return -1;
		}
		else
		{
			dol_syslog(get_class($this)."::update done successfully");
			return 1;
		}
	}


	/**
	 * 	Delete a LDAP entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string	$dn			DN entry key
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function delete($dn)
	{
		global $conf;

		dol_syslog(get_class($this)."::delete Delete LDAP entry dn=".$dn);

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);

		$result=@ldap_delete($this->connection, $dn);

		if ($result) return 1;
		return -1;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * 	Build a LDAP message
	 *
	 *	@param	string		$dn			DN entry key
	 *	@param	array		$info		Attributes array
	 *	@return	string					Content of file
	 */
	function dump_content($dn, $info)
	{
        // phpcs:enable
		$content='';

		// Create file content
		if (preg_match('/^ldap/',$this->server[0]))
		{
			$target="-H ".join(',',$this->server);
		}
		else
		{
			$target="-h ".join(',',$this->server)." -p ".$this->serverPort;
		}
		$content.="# ldapadd $target -c -v -D ".$this->searchUser." -W -f ldapinput.in\n";
		$content.="# ldapmodify $target -c -v -D ".$this->searchUser." -W -f ldapinput.in\n";
		$content.="# ldapdelete $target -c -v -D ".$this->searchUser." -W -f ldapinput.in\n";
		if (in_array('localhost',$this->server)) $content.="# If commands fails to connect, try without -h and -p\n";
		$content.="dn: ".$dn."\n";
		foreach($info as $key => $value)
		{
			if (! is_array($value))
			{
				$content.="$key: $value\n";
			}
			else
			{
				foreach($value as $valuekey => $valuevalue)
				{
					$content.="$key: $valuevalue\n";
				}
			}
		}
		return $content;
	}

	/**
	 * 	Dump a LDAP message to ldapinput.in file
	 *
	 *	@param	string		$dn			DN entry key
	 *	@param	array		$info		Attributes array
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function dump($dn, $info)
	{
		global $conf;

		// Create content
		$content=$this->dump_content($dn, $info);

		//Create file
		$result=dol_mkdir($conf->ldap->dir_temp);

		$outputfile=$conf->ldap->dir_temp.'/ldapinput.in';
		$fp=fopen($outputfile,"w");
		if ($fp)
		{
			fputs($fp, $content);
			fclose($fp);
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
			return 1;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Ping a server before ldap_connect for avoid waiting
	 *
	 * @param string		$host		Server host or address
	 * @param int		$port		Server port (default 389)
	 * @param int		$timeout		Timeout in second (default 1s)
	 * @return boolean				true or false
	 */
	function serverPing($host, $port=389, $timeout=1)
	{
		// Replace ldaps:// by ssl://
		if (preg_match('/^ldaps:\/\/([^\/]+)\/?$/',$host, $regs)) {
			$host = 'ssl://'.$regs[1];
		}
		// Remove ldap://
		if (preg_match('/^ldap:\/\/([^\/]+)\/?$/',$host, $regs)) {
			$host = $regs[1];
		}
		$op = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if (!$op) return false; //DC is N/A
		else {
			fclose($op); //explicitly close open socket connection
			return true; //DC is up & running, we can safely connect with ldap_connect
		}
	}


	// Attribute methods -----------------------------------------------------

	/**
	 * 	Add a LDAP attribute in entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string		$dn			DN entry key
	 *	@param	array		$info		Attributes array
	 *	@param	User		$user		Objet user that create
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function addAttribute($dn, $info, $user)
	{
		global $conf;

		dol_syslog(get_class($this)."::addAttribute dn=".$dn." info=".join(',',$info));

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);
		foreach($info as $key => $val)
		{
			if (! is_array($val)) $info[$key]=$this->convFromOutputCharset($val,$this->ldapcharset);
		}

		$this->dump($dn,$info);

		//print_r($info);
		$result=@ldap_mod_add($this->connection, $dn, $info);

		if ($result)
		{
			dol_syslog(get_class($this)."::add_attribute successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog(get_class($this)."::add_attribute failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	Update a LDAP attribute in entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string		$dn			DN entry key
	 *	@param	array		$info		Attributes array
	 *	@param	User		$user		Objet user that create
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function updateAttribute($dn, $info, $user)
	{
		global $conf;

		dol_syslog(get_class($this)."::updateAttribute dn=".$dn." info=".join(',',$info));

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);
		foreach($info as $key => $val)
		{
			if (! is_array($val)) $info[$key]=$this->convFromOutputCharset($val,$this->ldapcharset);
		}

		$this->dump($dn,$info);

		//print_r($info);
		$result=@ldap_mod_replace($this->connection, $dn, $info);

		if ($result)
		{
			dol_syslog(get_class($this)."::updateAttribute successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog(get_class($this)."::updateAttribute failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	Delete a LDAP attribute in entry
	 *	Ldap object connect and bind must have been done
	 *
	 *	@param	string		$dn			DN entry key
	 *	@param	array		$info		Attributes array
	 *	@param	User		$user		Objet user that create
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function deleteAttribute($dn, $info, $user)
	{
		global $conf;

		dol_syslog(get_class($this)."::deleteAttribute dn=".$dn." info=".join(',',$info));

		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		// Encode to LDAP page code
		$dn=$this->convFromOutputCharset($dn,$this->ldapcharset);
		foreach($info as $key => $val)
		{
			if (! is_array($val)) $info[$key]=$this->convFromOutputCharset($val,$this->ldapcharset);
		}

		$this->dump($dn,$info);

		//print_r($info);
		$result=@ldap_mod_del($this->connection, $dn, $info);

		if ($result)
		{
			dol_syslog(get_class($this)."::deleteAttribute successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog(get_class($this)."::deleteAttribute failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Returns an array containing attributes and values for first record
	 *
	 *	@param	string	$dn			DN entry key
	 *	@param	string	$filter		Filter
	 *	@return	int|array			<0 or false if KO, array if OK
	 */
	function getAttribute($dn,$filter)
	{
		// Check parameters
		if (! $this->connection)
		{
			$this->error="NotConnected";
			return -2;
		}
		if (! $this->bind)
		{
			$this->error="NotConnected";
			return -3;
		}

		$search = ldap_search($this->connection,$dn,$filter);

		// Only one entry should ever be returned
		$entry = ldap_first_entry($this->connection, $search);

		if (!$entry)
		{
			$this->ldapErrorCode = -1;
			$this->ldapErrorText = "Couldn't find entry";
			return 0;  // Couldn't find entry...
		}

		// Get values
		if (! $values = ldap_get_attributes($this->connection, $entry))
		{
			$this->ldapErrorCode = ldap_errno($this->connection);
			$this->ldapErrorText = ldap_error($this->connection);
			return 0; // No matching attributes
		}

		// Return an array containing the attributes.
		return $values;
	}

	/**
	 *  Returns an array containing values for an attribute and for first record matching filterrecord
	 *
	 * 	@param	string	$filterrecord		Record
	 * 	@param	string	$attribute			Attributes
	 * 	@return void
	 */
	function getAttributeValues($filterrecord,$attribute)
	{
		$attributes=array();
		$attributes[0] = $attribute;

		// We need to search for this user in order to get their entry.
		$this->result = @ldap_search($this->connection,$this->people,$filterrecord,$attributes);

		// Pourquoi cette ligne ?
		//$info = ldap_get_entries($this->connection, $this->result);

		// Only one entry should ever be returned (no user will have the same uid)
		$entry = ldap_first_entry($this->connection, $this->result);

		if (!$entry)
		{
			$this->ldapErrorCode = -1;
			$this->ldapErrorText = "Couldn't find user";
			return false;  // Couldn't find the user...
		}

		// Get values
		if (! $values = @ldap_get_values($this->connection, $entry, $attribute))
		{
			$this->ldapErrorCode = ldap_errno($this->connection);
			$this->ldapErrorText = ldap_error($this->connection);
			return false; // No matching attributes
		}

		// Return an array containing the attributes.
		return $values;
	}

	/**
	 * 	Returns an array containing a details or list of LDAP record(s)
	 * 	ldapsearch -LLLx -hlocalhost -Dcn=admin,dc=parinux,dc=org -w password -b "ou=adherents,ou=people,dc=parinux,dc=org" userPassword
	 *
	 *	@param	string	$search			 	Value of fiel to search, '*' for all. Not used if $activefilter is set.
	 *	@param	string	$userDn			 	DN (Ex: ou=adherents,ou=people,dc=parinux,dc=org)
	 *	@param	string	$useridentifier 	Name of key field (Ex: uid)
	 *	@param	array	$attributeArray 	Array of fields required. Note this array must also contains field $useridentifier (Ex: sn,userPassword)
	 *	@param	int		$activefilter		'1' or 'user'=use field this->filter as filter instead of parameter $search, 'member'=use field this->filtermember as filter
	 *	@param	array	$attributeAsArray 	Array of fields wanted as an array not a string
	 *	@return	array						Array of [id_record][ldap_field]=value
	 */
	function getRecords($search, $userDn, $useridentifier, $attributeArray, $activefilter=0, $attributeAsArray=array())
	{
		$fulllist=array();

		dol_syslog(get_class($this)."::getRecords search=".$search." userDn=".$userDn." useridentifier=".$useridentifier." attributeArray=array(".join(',',$attributeArray).") activefilter=".$activefilter);

		// if the directory is AD, then bind first with the search user first
		if ($this->serverType == "activedirectory")
		{
			$this->bindauth($this->searchUser, $this->searchPassword);
			dol_syslog(get_class($this)."::bindauth serverType=activedirectory searchUser=".$this->searchUser);
		}

		// Define filter
		if (! empty($activefilter))
		{
			if (((string) $activefilter == '1' || (string) $activefilter == 'user') && $this->filter)
			{
				$filter = '('.$this->filter.')';
			}
			elseif (((string) $activefilter == 'member') && $this->filter)
			{
				$filter = '('.$this->filtermember.')';
			}
			else	// If this->filter is empty, make fiter on * (all)
			{
				$filter = '('.$useridentifier.'=*)';
			}
		}
		else
		{
			$filter = '('.$useridentifier.'='.$search.')';
		}

		if (is_array($attributeArray))
		{
			// Return list with required fields
			$attributeArray=array_values($attributeArray);	// This is to force to have index reordered from 0 (not make ldap_search fails)
			dol_syslog(get_class($this)."::getRecords connection=".$this->connection." userDn=".$userDn." filter=".$filter. " attributeArray=(".join(',',$attributeArray).")");
			//var_dump($attributeArray);
			$this->result = @ldap_search($this->connection, $userDn, $filter, $attributeArray);
		}
		else
		{
			// Return list with fields selected by default
			dol_syslog(get_class($this)."::getRecords connection=".$this->connection." userDn=".$userDn." filter=".$filter);
			$this->result = @ldap_search($this->connection, $userDn, $filter);
		}
		if (!$this->result)
		{
			$this->error = 'LDAP search failed: '.ldap_errno($this->connection)." ".ldap_error($this->connection);
			return -1;
		}

		$info = @ldap_get_entries($this->connection, $this->result);

		// Warning: Dans info, les noms d'attributs sont en minuscule meme si passe
		// a ldap_search en majuscule !!!
		//print_r($info);

		for ($i = 0; $i < $info["count"]; $i++)
		{
			$recordid=$this->convToOutputCharset($info[$i][$useridentifier][0],$this->ldapcharset);
			if ($recordid)
			{
				//print "Found record with key $useridentifier=".$recordid."<br>\n";
				$fulllist[$recordid][$useridentifier]=$recordid;

				// Add to the array for each attribute in my list
				$num = count($attributeArray);
				for ($j = 0; $j < $num; $j++)
				{
					$keyattributelower=strtolower($attributeArray[$j]);
					//print " Param ".$attributeArray[$j]."=".$info[$i][$keyattributelower][0]."<br>\n";

					//permet de recuperer le SID avec Active Directory
					if ($this->serverType == "activedirectory" && $keyattributelower == "objectsid")
					{
						$objectsid = $this->getObjectSid($recordid);
						$fulllist[$recordid][$attributeArray[$j]]    = $objectsid;
					}
					else
					{
						if(in_array($attributeArray[$j], $attributeAsArray) && is_array($info[$i][$keyattributelower])) {
							$valueTab = array();
							foreach($info[$i][$keyattributelower] as $key => $value) {
								$valueTab[$key] = $this->convToOutputCharset($value,$this->ldapcharset);
							}
							$fulllist[$recordid][$attributeArray[$j]] = $valueTab;
						} else {
							$fulllist[$recordid][$attributeArray[$j]] = $this->convToOutputCharset($info[$i][$keyattributelower][0],$this->ldapcharset);
						}
					}
				}
			}
		}

		asort($fulllist);
		return $fulllist;
	}

	/**
	 *  Converts a little-endian hex-number to one, that 'hexdec' can convert
	 *	Required by Active Directory
	 *
	 *	@param	string		$hex			Hex value
	 *	@return	string						Little endian
	 */
	function littleEndian($hex)
	{
		for ($x=dol_strlen($hex)-2; $x >= 0; $x=$x-2) {
			$result .= substr($hex,$x,2);
		}
		return $result;
	}


	/**
	 *  Recupere le SID de l'utilisateur
	 *	Required by Active Directory
	 *
	 * 	@param	string		$ldapUser		Login de l'utilisateur
	 * 	@return	string						Sid
	 */
	function getObjectSid($ldapUser)
	{
		$criteria =  '('.$this->getUserIdentifier().'='.$ldapUser.')';
		$justthese = array("objectsid");

		// if the directory is AD, then bind first with the search user first
		if ($this->serverType == "activedirectory")
		{
			$this->bindauth($this->searchUser, $this->searchPassword);
		}

		$i = 0;
		$searchDN = $this->people;

		while ($i <= 2)
		{
			$ldapSearchResult = @ldap_search($this->connection, $searchDN, $criteria, $justthese);

			if (!$ldapSearchResult)
			{
				$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection);
				return -1;
			}

			$entry = ldap_first_entry($this->connection, $ldapSearchResult);

			if (!$entry)
			{
				// Si pas de resultat on cherche dans le domaine
				$searchDN = $this->domain;
				$i++;
			}
			else
			{
				$i++;
				$i++;
			}
		}

		if ($entry)
		{
			$ldapBinary = ldap_get_values_len($this->connection, $entry, "objectsid");
			$SIDText = $this->binSIDtoText($ldapBinary[0]);
			return $SIDText;
		}
		else
		{
			$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection);
			return '?';
		}
	}

	/**
	 * Returns the textual SID
	 * Indispensable pour Active Directory
	 *
	 * @param	string	$binsid		Binary SID
	 * @return	string				Textual SID
	 */
	function binSIDtoText($binsid)
	{
		$hex_sid=bin2hex($binsid);
		$rev = hexdec(substr($hex_sid,0,2));          // Get revision-part of SID
		$subcount = hexdec(substr($hex_sid,2,2));    // Get count of sub-auth entries
		$auth = hexdec(substr($hex_sid,4,12));      // SECURITY_NT_AUTHORITY
		$result = "$rev-$auth";
		for ($x=0;$x < $subcount; $x++)
		{
			$result .= "-".hexdec($this->littleEndian(substr($hex_sid,16+($x*8),8)));  // get all SECURITY_NT_AUTHORITY
		}
		return $result;
	}


	/**
	 * 	Fonction de recherche avec filtre
	 *	this->connection doit etre defini donc la methode bind ou bindauth doit avoir deja ete appelee
	 *	Ne pas utiliser pour recherche d'une liste donnee de proprietes
	 *	car conflit majuscule-minuscule. A n'utiliser que pour les pages
	 *	'Fiche LDAP' qui affiche champ lisibles par defaut.
	 *
	 * 	@param	string		$checkDn		DN de recherche (Ex: ou=users,cn=my-domain,cn=com)
	 * 	@param 	string		$filter			Search filter (ex: (sn=nom_personne) )
	 *	@return	array|int					Array with answers (key lowercased - value)
	 */
	function search($checkDn, $filter)
	{
		dol_syslog(get_class($this)."::search checkDn=".$checkDn." filter=".$filter);

		$checkDn=$this->convFromOutputCharset($checkDn,$this->ldapcharset);
		$filter=$this->convFromOutputCharset($filter,$this->ldapcharset);

		// if the directory is AD, then bind first with the search user first
		if ($this->serverType == "activedirectory") {
			$this->bindauth($this->searchUser, $this->searchPassword);
		}

		$this->result = @ldap_search($this->connection, $checkDn, $filter);

		$result = @ldap_get_entries($this->connection, $this->result);
		if (! $result)
		{
			$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection);
			return -1;
		}
		else
		{
			ldap_free_result($this->result);
			return $result;
		}
	}


	/**
	 * 		Load all attribute of a LDAP user
	 *
	 * 		@param	User	$user		User to search for. Not used if a filter is provided.
	 *      @param  string	$filter		Filter for search. Must start with &.
	 *                       	       	Examples: &(objectClass=inetOrgPerson) &(objectClass=user)(objectCategory=person) &(isMemberOf=cn=Sales,ou=Groups,dc=opencsi,dc=com)
	 *		@return	int					>0 if OK, <0 if KO
	 */
	function fetch($user,$filter)
	{
		// Perform the search and get the entry handles

		// if the directory is AD, then bind first with the search user first
		if ($this->serverType == "activedirectory") {
			$this->bindauth($this->searchUser, $this->searchPassword);
		}

		$searchDN = $this->people;    // TODO Why searching in people then domain ?

		$result = '';
		$i=0;
		while ($i <= 2)
		{
			dol_syslog(get_class($this)."::fetch search with searchDN=".$searchDN." filter=".$filter);
			$this->result = @ldap_search($this->connection, $searchDN, $filter);
			if ($this->result)
			{
				$result = @ldap_get_entries($this->connection, $this->result);
				if ($result['count'] > 0) dol_syslog('Ldap::fetch search found '.$result['count'].' records');
				else dol_syslog('Ldap::fetch search returns but found no records');
				//var_dump($result);exit;
			}
			else
			{
				$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection);
				dol_syslog(get_class($this)."::fetch search fails");
				return -1;
			}

			if (! $result)
			{
				// Si pas de resultat on cherche dans le domaine
				$searchDN = $this->domain;
				$i++;
			}
			else
			{
				break;
			}
		}

		if (! $result)
		{
			$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection);
			return -1;
		}
		else
		{
			$this->name       = $this->convToOutputCharset($result[0][$this->attr_name][0],$this->ldapcharset);
			$this->firstname  = $this->convToOutputCharset($result[0][$this->attr_firstname][0],$this->ldapcharset);
			$this->login      = $this->convToOutputCharset($result[0][$this->attr_login][0],$this->ldapcharset);
			$this->phone      = $this->convToOutputCharset($result[0][$this->attr_phone][0],$this->ldapcharset);
			$this->skype      = $this->convToOutputCharset($result[0][$this->attr_skype][0],$this->ldapcharset);
			$this->fax        = $this->convToOutputCharset($result[0][$this->attr_fax][0],$this->ldapcharset);
			$this->mail       = $this->convToOutputCharset($result[0][$this->attr_mail][0],$this->ldapcharset);
			$this->mobile     = $this->convToOutputCharset($result[0][$this->attr_mobile][0],$this->ldapcharset);

			$this->uacf       = $this->parseUACF($this->convToOutputCharset($result[0]["useraccountcontrol"][0],$this->ldapcharset));
			if (isset($result[0]["pwdlastset"][0]))	// If expiration on password exists
			{
				$this->pwdlastset = ($result[0]["pwdlastset"][0] != 0)?$this->convert_time($this->convToOutputCharset($result[0]["pwdlastset"][0],$this->ldapcharset)):0;
			}
			else
			{
				$this->pwdlastset = -1;
			}
			if (!$this->name && !$this->login) $this->pwdlastset = -1;
			$this->badpwdtime = $this->convert_time($this->convToOutputCharset($result[0]["badpasswordtime"][0],$this->ldapcharset));

			// FQDN domain
			$domain = str_replace('dc=','',$this->domain);
			$domain = str_replace(',','.',$domain);
			$this->domainFQDN = $domain;

			// Set ldapUserDn (each user can have a different dn)
			//var_dump($result[0]);exit;
			$this->ldapUserDN=$result[0]['dn'];

			ldap_free_result($this->result);
			return 1;
		}
	}


	// helper methods

	/**
	 * 	Returns the correct user identifier to use, based on the ldap server type
	 *
	 *	@return	string 				Login
	 */
	function getUserIdentifier()
	{
		if ($this->serverType == "activedirectory") {
			return $this->attr_sambalogin;
		} else {
			return $this->attr_login;
		}
	}

   /**
    * 	UserAccountControl Flgs to more human understandable form...
    *
    *	@param	string		$uacf		UACF
    *	@return	void
    */
	function parseUACF($uacf)
	{
		//All flags array
		$flags = array(
			"TRUSTED_TO_AUTH_FOR_DELEGATION"  =>    16777216,
			"PASSWORD_EXPIRED"                =>    8388608,
			"DONT_REQ_PREAUTH"                =>    4194304,
			"USE_DES_KEY_ONLY"                =>    2097152,
			"NOT_DELEGATED"                   =>    1048576,
			"TRUSTED_FOR_DELEGATION"          =>    524288,
			"SMARTCARD_REQUIRED"              =>    262144,
			"MNS_LOGON_ACCOUNT"               =>    131072,
			"DONT_EXPIRE_PASSWORD"            =>    65536,
			"SERVER_TRUST_ACCOUNT"            =>    8192,
			"WORKSTATION_TRUST_ACCOUNT"       =>    4096,
			"INTERDOMAIN_TRUST_ACCOUNT"       =>    2048,
			"NORMAL_ACCOUNT"                  =>    512,
			"TEMP_DUPLICATE_ACCOUNT"          =>    256,
			"ENCRYPTED_TEXT_PWD_ALLOWED"      =>    128,
			"PASSWD_CANT_CHANGE"              =>    64,
			"PASSWD_NOTREQD"                  =>    32,
			"LOCKOUT"                         =>    16,
			"HOMEDIR_REQUIRED"                =>    8,
			"ACCOUNTDISABLE"                  =>    2,
			"SCRIPT"                          =>    1
		);

		//Parse flags to text
		$retval = array();
		while (list($flag, $val) = each($flags)) {
			if ($uacf >= $val) {
				$uacf -= $val;
				$retval[$val] = $flag;
			}
		}

		//Return human friendly flags
		return($retval);
	}

   /**
    * 	SamAccountType value to text
    *
    *	@param	string	$samtype	SamType
    *	@return	string				Sam string
    */
	function parseSAT($samtype)
	{
		$stypes = array(
			805306368    =>    "NORMAL_ACCOUNT",
			805306369    =>    "WORKSTATION_TRUST",
			805306370    =>    "INTERDOMAIN_TRUST",
			268435456    =>    "SECURITY_GLOBAL_GROUP",
			268435457    =>    "DISTRIBUTION_GROUP",
			536870912    =>    "SECURITY_LOCAL_GROUP",
			536870913    =>    "DISTRIBUTION_LOCAL_GROUP"
		);

		$retval = "";
		while (list($sat, $val) = each($stypes)) {
			if ($samtype == $sat) {
				$retval = $val;
				break;
			}
		}
		if (empty($retval)) $retval = "UNKNOWN_TYPE_" . $samtype;

		return($retval);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Convertit le temps ActiveDirectory en Unix timestamp
	 *
	 *	@param	string	$value		AD time to convert
	 *	@return	integer				Unix timestamp
	 */
	function convert_time($value)
	{
        // phpcs:enable
		$dateLargeInt=$value; // nano secondes depuis 1601 !!!!
		$secsAfterADEpoch = $dateLargeInt / (10000000); // secondes depuis le 1 jan 1601
		$ADToUnixConvertor=((1970-1601) * 365.242190) * 86400; // UNIX start date - AD start date * jours * secondes
		$unixTimeStamp=intval($secsAfterADEpoch-$ADToUnixConvertor); // Unix time stamp
		return $unixTimeStamp;
	}


	/**
	 *  Convert a string into output/memory charset
	 *
	 *  @param	string	$str            String to convert
	 *  @param	string	$pagecodefrom	Page code of src string
	 *  @return string         			Converted string
	 */
	private function convToOutputCharset($str,$pagecodefrom='UTF-8')
	{
		global $conf;
		if ($pagecodefrom == 'ISO-8859-1' && $conf->file->character_set_client == 'UTF-8')  $str=utf8_encode($str);
		if ($pagecodefrom == 'UTF-8' && $conf->file->character_set_client == 'ISO-8859-1')  $str=utf8_decode($str);
		return $str;
	}

	/**
	 *  Convert a string from output/memory charset
	 *
	 *  @param	string	$str            String to convert
	 *  @param	string	$pagecodeto		Page code for result string
	 *  @return string         			Converted string
	 */
	function convFromOutputCharset($str,$pagecodeto='UTF-8')
	{
		global $conf;
		if ($pagecodeto == 'ISO-8859-1' && $conf->file->character_set_client == 'UTF-8') $str=utf8_decode($str);
		if ($pagecodeto == 'UTF-8' && $conf->file->character_set_client == 'ISO-8859-1') $str=utf8_encode($str);
		return $str;
	}


	/**
	 *	Return available value of group GID
	 *
	 *	@param	string	$keygroup	Key of group
	 *	@return	int					gid number
	 */
	function getNextGroupGid($keygroup='LDAP_KEY_GROUPS')
	{
		global $conf;

		if (empty($keygroup)) $keygroup='LDAP_KEY_GROUPS';

		$search='('.$conf->global->$keygroup.'=*)';
		$result = $this->search($this->groups,$search);
		if ($result)
		{
			$c = $result['count'];
			$gids = array();
			for($i=0;$i<$c;$i++)
			{
				$gids[] = $result[$i]['gidnumber'][0];
			}
			rsort($gids);

			return $gids[0]+1;
		}

		return 0;
	}
}
