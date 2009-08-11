<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file 		htdocs/lib/ldap.class.php
 *	\brief 		Classe de gestion d'annuaire LDAP
 *	\author 	Rodolphe Quiedeville
 *	\author		Benoit Mortier
 *	\author		Regis Houssin
 *	\author		Laurent Destailleur
 *	\version 	$Id$
 */
class Ldap
{

	/**
	 * Tableau des serveurs (IP addresses ou nom d'hôtes)
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
	 * Code erreur retourné par le serveur Ldap
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
	var $fax;
	var $mail;
	var $mobile;

	var $uacf;
	var $pwdlastset;

	var $ldapcharset='UTF-8';	// LDAP should be UTF-8 encoded


	// 1.2 Private properties ----------------------------------------------------
	/**
	* The internal LDAP connection handle
	*/
	var $connection;
	/**
	 * Result of any connections etc.
	 */
	var $result;

	/**
	 * Constructor- creates a new instance of the authentication class
	 *
	 */
	function Ldap ()
	{
		global $conf;

		//Server
		if ($conf->global->LDAP_SERVER_HOST)       $this->server[] = $conf->global->LDAP_SERVER_HOST;
		if ($conf->global->LDAP_SERVER_HOST_SLAVE) $this->server[] = $conf->global->LDAP_SERVER_HOST_SLAVE;
		$this->serverPort          = $conf->global->LDAP_SERVER_PORT;
		$this->ldapProtocolVersion = $conf->global->LDAP_SERVER_PROTOCOLVERSION;
		$this->dn                  = $conf->global->LDAP_SERVER_DN;
		$this->serverType          = $conf->global->LDAP_SERVER_TYPE;
		$this->domain              = $conf->global->LDAP_SERVER_DN;
		$this->searchUser          = $conf->global->LDAP_ADMIN_DN;
		$this->searchPassword      = $conf->global->LDAP_ADMIN_PASS;
		$this->people              = $conf->global->LDAP_USER_DN;
		$this->groups              = $conf->global->LDAP_GROUP_DN;
		$this->filter              = $conf->global->LDAP_FILTER_CONNECTION;

		//Users
		$this->attr_login      = $conf->global->LDAP_FIELD_LOGIN; //unix
		$this->attr_sambalogin = $conf->global->LDAP_FIELD_LOGIN_SAMBA; //samba, activedirectory
		$this->attr_name       = $conf->global->LDAP_FIELD_NAME;
		$this->attr_firstname  = $conf->global->LDAP_FIELD_FIRSTNAME;
		$this->attr_mail       = $conf->global->LDAP_FIELD_MAIL;
		$this->attr_phone      = $conf->global->LDAP_FIELD_PHONE;
		$this->attr_fax        = $conf->global->LDAP_FIELD_FAX;
		$this->attr_mobile     = $conf->global->LDAP_FIELD_MOBILE;
	}



	// 2.1 Connection handling methods -------------------------------------------

	/**
	 * 2.1.1 : Connects to the server. Just creates a connection which is used
	 * in all later access to the LDAP server. If it can't connect and bind
	 * anonymously, it creates an error code of -1. Returns true if connected,
	 * false if failed. Takes an array of possible servers - if one doesn't work,
	 * it tries the next and so on.
	 *		\deprecated		Utiliser connect_bind a la place
	 */
	function connect()
	{
		foreach ($this->server as $key => $host)
		{
			if (ereg('^ldap',$host))
			{
				$this->connection = ldap_connect($host);
			}
			else
			{
				$this->connection = ldap_connect($host,$this->serverPort);
			}
			if ($this->connection)
			{
				$this->setVersion();
				if ($this->serverType == "activedirectory")
				{
					$this->setReferrals();
					return true;
				}
				else
				{
					// Connected, now try binding anonymously
					$this->result=@ldap_bind( $this->connection);
				}
				return true;
			}
		}

		$this->ldapErrorCode = -1;
		$this->ldapErrorText = "Unable to connect to any server";
		return false;
	}


	/**
	 *		\brief		Connect and bind
	 *		\return		<0 si KO, 1 si bind anonymous, 2 si bind auth
	 * 		\remarks	Use this->server, this->serverPort, this->ldapProtocolVersion, this->serverType, this->searchUser, this->searchPassword
	 * 					After return, this->connection and $this->bind are defined
	 */
	function connect_bind()
	{
		global $langs;

		$connected=0;
		$this->bind=0;

		foreach ($this->server as $key => $host)
		{
			if ($connected) break;

			if (ereg('^ldap',$host))
			{
				$this->connection = ldap_connect($host);
			}
			else
			{
				$this->connection = ldap_connect($host,$this->serverPort);
			}

			if ($this->connection)
			{
				$this->setVersion();

				if ($this->serverType == "activedirectory")
				{
					$result=$this->setReferrals();
					dol_syslog("Ldap::connect_bind try bindauth for activedirectory on ".$host." user=".$this->searchUser,LOG_DEBUG);
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
						dol_syslog("Ldap::connect_bind try bindauth on ".$host." user=".$this->searchUser,LOG_DEBUG);
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
						dol_syslog("Ldap::connect_bind try bind on ".$host,LOG_DEBUG);
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

		if ($connected)
		{
			$return=$connected;
			dol_syslog("Ldap::connect_bind return=".$return, LOG_DEBUG);
		}
		else
		{
			$this->error='Failed to connect to LDAP';
			$return=-1;
			dol_syslog("Ldap::connect_bind return=".$return, LOG_WARNING);
		}
		return $return;
	}



	/**
	 * 2.1.2 : Simply closes the connection set up earlier.
	 * Returns true if OK, false if there was an error.
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
	 * 2.1.3 : Anonymously binds to the connection. After this is done,
	 * queries and searches can be done - but read-only.
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
	 * 2.1.4 : Binds as an authenticated user, which usually allows for write
	 * access. The FULL dn must be passed. For a directory manager, this is
	 * "cn=Directory Manager" under iPlanet. For a user, it will be something
	 * like "uid=jbloggs,ou=People,dc=foo,dc=com".
	 */
	function bindauth($bindDn,$pass)
	{
		if (! $this->result = @ldap_bind( $this->connection,$bindDn,$pass))
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
	 * 	\brief 		Unbind du serveur ldap.
	 * 	\param		ds
	 * 	\return		bool
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
	 * \brief verification de la version du serveur ldap.
	 * \param	ds
	 * \return	version
	 */
	function getVersion()
	{
		$version = 0;
		$version = @ldap_get_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $version);
		return $version;
	}

	/**
	 * \brief changement de la version du serveur ldap.
	 * \return	version
	 */
	function setVersion() {
		// LDAP_OPT_PROTOCOL_VERSION est une constante qui vaut 17
		$ldapsetversion = ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldapProtocolVersion);
		return $ldapsetversion;
	}

	/**
	 * \brief changement du referrals.
	 * \return	referrals
	 */
	function setReferrals() {
		// LDAP_OPT_REFERRALS est une constante qui vaut ?
		$ldapreferrals = ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
		return $ldapreferrals;
	}



	/**
	 * 		\brief		Checks a username and password - does this by logging on to the
	 * 					server as a user - specified in the DN. There are several reasons why
	 * 					this login could fail - these are listed below.
	 *		\return		uname		Username to check
	 *		\return		pass		Password to check
	 *		\return		boolean		true=check pass ok, falses=check pass failed
	 */
	function checkPass($uname,$pass)
	{
		/* Construct the full DN, eg:-
		 ** "uid=username, ou=People, dc=orgname,dc=com"
		 */
		if ($this->serverType == "activedirectory") {
			// FQDN domain
			$domain = eregi_replace('dc=','',$this->domain);
			$domain = eregi_replace(',','.',$domain);
			$checkDn = "$uname@$domain";
		} else {
			$checkDn = $this->getUserIdentifier()."=".$uname.", ".$this->setDn(true);
		}
		// Try and connect...
		$this->result = @ldap_bind( $this->connection,$checkDn,$pass);
		if ( $this->result) {
			// Connected OK - login credentials are fine!
			$this->ldapUserDN = $checkDn;
			return true;
		} else {
			/* Login failed. Return false, together with the error code and text from
			 ** the LDAP server. The common error codes and reasons are listed below :
			 ** (for iPlanet, other servers may differ)
			 ** 19 - Account locked out (too many invalid login attempts)
			 ** 32 - User does not exist
			 ** 49 - Wrong password
			 ** 53 - Account inactive (manually locked out by administrator)
			 */
			$this->ldapErrorCode = ldap_errno( $this->connection);
			$this->ldapErrorText = ldap_error( $this->connection);
			$this->ldapDebugDomain = $domain;
			$this->ldapDebugDN = $checkDn;
			return false;
		}
	}


	/**
	 * 	\brief		Add a LDAP entry
	 *	\param		dn			DN entry key
	 *	\param		info		Attributes array
	 *	\param		user		Objet user that create
	 *	\return		int			<0 if KO, >0 if OK
	 *	\remarks	Ldap object connect and bind must have been done
	 */
	function add($dn, $info, $user)
	{
		global $conf;

		dol_syslog("Ldap::add dn=".$dn." info=".join(',',$info));

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
			dol_syslog("Ldap::add successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog("Ldap::add failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	\brief		Modify a LDAP entry
	 *	\param		dn			DN entry key
	 *	\param		info		Attributes array
	 *	\param		user		Objet user that modify
	 *	\return		int			<0 if KO, >0 if OK
	 *	\remarks	Ldap object connect and bind must have been done
	 */
	function modify($dn, $info, $user)
	{
		global $conf;

		dol_syslog("Ldap::modify dn=".$dn." info=".join(',',$info));

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
			dol_syslog("Ldap::modify successfull", LOG_DEBUG);
			return 1;
		}
		else
		{
			$this->error=@ldap_error($this->connection);
			dol_syslog("Ldap::modify failed: ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  \brief      Modify a LDAP entry (to use if dn != olddn)
	 *  \param      dn			DN entry key
	 *  \param      info		Attributes array
	 *  \param    	user		Objet user that delete
	 * 	\param		olddn		Old DN entry key (before update)
	 *	\return		int			<0 if KO, >0 if OK
	 *	\remarks	Ldap object connect and bind must have been done
	 */
	function update($dn,$info,$user,$olddn)
	{
		global $conf;

		dol_syslog("Ldap::update dn=".$dn." olddn=".$olddn);

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
			// This case is not used for the moment
			$result = $this->add($dn, $info, $user);
			if ($result > 0 && $olddn && $olddn != $dn) $result = $this->delete($olddn);	// If add fails, we do not try to delete old one
		}
		else
		{
			$result = $this->delete($olddn);
			$result = $this->add($dn, $info, $user);
			//$result = $this->modify($dn, $info, $user);	// TODO Must use modify instead of delete/add when olddn is received (for the moment olddn is dn)
		}
		if ($result <= 0)
		{
			$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection)." ".$this->error;
			dol_syslog("Ldap::update ".$this->error,LOG_ERR);
			//print_r($info);
			return -1;
		}
		else
		{
			dol_syslog("Ldap::update done successfully");
			return 1;
		}
	}


	/**
	 * 	\brief		Delete a LDAP entry
	 *	\param		dn			DN entry key
	 *	\return		int			<0 si KO, >0 si OK
	 *	\remarks	Ldap object connect and bind must have been done
	 */
	function delete($dn)
	{
		global $conf;

		dol_syslog("Ldap::delete Delete LDAP entry dn=".$dn);

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


	/**
	 * 	\brief		Build a LDAP message
	 *	\param		dn			DN entry key
	 *	\param		info		Attributes array
	 *	\return		string		Content of file
	 */
	function dump_content($dn, $info)
	{
		$content='';

		// Create file content
		if (ereg('^ldap',$this->server[0]))
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
	 * 	\brief		Dump a LDAP message to ldapinput.in file
	 *	\param		dn			DN entry key
	 *	\param		info		Attributes array
	 *	\return		int			<0 if KO, >0 if OK
	 */
	function dump($dn, $info)
	{
		global $conf;

		// Create content
		$content=$this->dump_content($dn, $info);

		//Create file
		$result=create_exdir($conf->ldap->dir_temp);

		$file=$conf->ldap->dir_temp.'/ldapinput.in';
		$fp=fopen($file,"w");
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


	// 2.4 Attribute methods -----------------------------------------------------
	/**
	* 2.4.1 : Returns an array containing values for an attribute and for first record matching filterrecord
	*/
	function getAttribute($filterrecord,$attribute)
	{
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
		if (! $values = @ldap_get_values( $this->connection, $entry, $attribute))
		{
			$this->ldapErrorCode = ldap_errno( $this->connection);
			$this->ldapErrorText = ldap_error( $this->connection);
			return false; // No matching attributes
		}

		// Return an array containing the attributes.
		return $values;
	}


	/**
	 * 		\brief		Returns an array containing a details of elements
	 *		\param		$search			 	Valeur champ clé recherché, sinon '*' pour tous.
	 *		\param		$userDn			 	DN (Ex: ou=adherents,ou=people,dc=parinux,dc=org)
	 *		\param		$useridentifier 	Nom du champ clé (Ex: uid)
	 *		\param		$attributeArray 	Array of fields required (Ex: sn,userPassword)
	 *		\param		$activefilter		1=utilise le champ this->filter comme filtre
	 *		\return		array				Array of [id_record][ldap_field]=value
	 * 		\remarks	ldapsearch -LLLx -hlocalhost -Dcn=admin,dc=parinux,dc=org -w password -b "ou=adherents,ou=people,dc=parinux,dc=org" userPassword
	 */
	function getRecords($search, $userDn, $useridentifier, $attributeArray, $activefilter=0)
	{
		$fulllist=array();

		dol_syslog("Ldap::getRecords search=".$search." userDn=".$userDn." useridentifier=".$useridentifier." attributeArray=array(".join(',',$attributeArray).")");

		// if the directory is AD, then bind first with the search user first
		if ($this->serverType == "activedirectory")
		{
			$this->bindauth($this->searchUser, $this->searchPassword);
			dol_syslog("Ldap::bindauth serverType=activedirectory searchUser=".$this->searchUser);
		}

		// Define filter
		if ($activefilter == 1)
		{
			if ($this->filter)
			{
				$filter = '('.$this->filter.')';
			}
			else
			{
				$filter='('.$useridentifier.'=*)';
			}
		}
		else
		{
			$filter = '('.$useridentifier.'='.$search.')';
		}

		if (is_array($attributeArray))
		{
			// Return list with required fields
			dol_syslog("Ldap::getRecords connection=".$this->connection." userDn=".$userDn." filter=".$filter. " attributeArray=(".join(',',$attributeArray).")");
			$this->result = @ldap_search($this->connection, $userDn, $filter, $attributeArray);
		}
		else
		{
			// Return list with fields selected by default
			dol_syslog("Ldap::getRecords connection=".$this->connection." userDn=".$userDn." filter=".$filter);
			$this->result = @ldap_search($this->connection, $userDn, $filter);
		}
		if (!$this->result)
		{
			$this->error = 'LDAP search failed: '.ldap_errno($this->connection)." ".ldap_error($this->connection);
			return -1;
		}

		$info = @ldap_get_entries($this->connection, $this->result);

		// Warning: Dans info, les noms d'attributs sont en minuscule meme si passé
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
				for ($j = 0; $j < count($attributeArray); $j++)
				{
					$keyattributelower=strtolower($attributeArray[$j]);
					//print " Param ".$attributeArray[$j]."=".$info[$i][$keyattributelower][0]."<br>\n";

					//permet de récupérer le SID avec Active Directory
					if ($this->serverType == "activedirectory" && $keyattributelower == "objectsid")
					{
						$objectsid = $this->getObjectSid($recordid);
						$fulllist[$recordid][$attributeArray[$j]]    = $objectsid;
					}
					else
					{
						$fulllist[$recordid][$attributeArray[$j]] = $this->convToOutputCharset($info[$i][$keyattributelower][0],$this->ldapcharset);
					}
				}
			}
		}

		asort($fulllist);
		return $fulllist;
	}

	/**
	 *  Converts a little-endian hex-number to one, that 'hexdec' can convert
	 *	Indispensable pour Active Directory
	 */
	function littleEndian($hex) {
		for ($x=strlen($hex)-2; $x >= 0; $x=$x-2) {
			$result .= substr($hex,$x,2);
		}
		return $result;
	}


	/**
	 * Récupère le SID de l'utilisateur
	 * ldapuser. le login de l'utilisateur
	 * Indispensable pour Active Directory
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
				// Si pas de résultat on cherche dans le domaine
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
			$ldapBinary = ldap_get_values_len ($this->connection, $entry, "objectsid");
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
	 */
	function binSIDtoText($binsid) {
		$hex_sid=bin2hex($binsid);
		$rev = hexdec(substr($hex_sid,0,2));          // Get revision-part of SID
		$subcount = hexdec(substr($hex_sid,2,2));    // Get count of sub-auth entries
		$auth = hexdec(substr($hex_sid,4,12));      // SECURITY_NT_AUTHORITY
		$result = "$rev-$auth";
		for ($x=0;$x < $subcount; $x++) {
			$subauth[$x] = hexdec($this->littleEndian(substr($hex_sid,16+($x*8),8)));  // get all SECURITY_NT_AUTHORITY
			$result .= "-".$subauth[$x];
		}
		return $result;
	}


	/**
	 * 	\brief 		Fonction de recherche avec filtre
	 *	\remarks	this->connection doit etre défini donc la methode bind ou bindauth doit avoir deja été appelée
	 * 	\param 		checkDn			DN de recherche (Ex: ou=users,cn=my-domain,cn=com)
	 * 	\param 		filter			Filtre de recherche (ex: (sn=nom_personne) )
	 *	\return		array			Tableau des reponses (clé en minuscule-valeur)
	 *	\remarks	Ne pas utiliser pour recherche d'une liste donnée de propriétés
	 *				car conflit majuscule-minuscule. A n'utiliser que pour les pages
	 *				'Fiche LDAP' qui affiche champ lisibles par defaut.
	 */
	function search($checkDn, $filter)
	{
		dol_syslog("Ldap::search checkDn=".$checkDn." filter=".$filter);

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
	 * 		\brief 		Récupère les attributs de l'utilisateur
	 * 		\param 		$user		Utilisateur ldap à lire
	 *		\return		int			>0 if ok, <0 if ko
	 */
	function fetch($user)
	{
		// Perform the search and get the entry handles

		// if the directory is AD, then bind first with the search user first
		if ($this->serverType == "activedirectory") {
			$this->bindauth($this->searchUser, $this->searchPassword);
		}
		$userIdentifier = $this->getUserIdentifier();

		$filter = '('.$this->filter.'('.$userIdentifier.'='.$user.'))';

		$i = 0;
		$searchDN = $this->people;

		$result = '';

		while ($i <= 2)
		{
			$this->result = @ldap_search($this->connection, $searchDN, $filter);

			if ($this->result)
			{
				$result = @ldap_get_entries($this->connection, $this->result);
				//var_dump($result);
			}
			else
			{
				$this->error = ldap_errno($this->connection)." ".ldap_error($this->connection);
				return -1;
			}

			if (!$result)
			{
				// Si pas de résultat on cherche dans le domaine
				$searchDN = $this->domain;
				$i++;
			}
			else
			{
				$i++;
				$i++;
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
			$this->login      = $this->convToOutputCharset($result[0][$userIdentifier][0],$this->ldapcharset);
			$this->phone      = $this->convToOutputCharset($result[0][$this->attr_phone][0],$this->ldapcharset);
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
			$domain = eregi_replace('dc=','',$this->domain);
			$domain = eregi_replace(',','.',$domain);
			$this->domainFQDN = $domain;

			ldap_free_result($this->result);
			return 1;
		}
	}


	// 2.6 helper methods

	/**
	 * Sets and returns the appropriate dn, based on whether there
	 * are values in $this->people and $this->groups.
	 *
	 * @param boolean specifies whether to build a groups dn or a people dn
	 * @return string if true ou=$this->people,$this->dn, else ou=$this->groups,$this->dn
	 */
	function setDn($peopleOrGroups) {

		if ($peopleOrGroups) {
			if ( isset($this->people) && (strlen($this->people) > 0) ) {
				$checkDn = "ou=" .$this->people. ", " .$this->dn;
			}
		} else {
			if ( isset($this->groups) && (strlen($this->groups) > 0) ) {
				$checkDn = "ou=" .$this->groups. ", " .$this->dn;
			}
		}

		if ( !isset($checkDn) ) {
			$checkDn = $this->dn;
		}
		return $checkDn;
	}

	/**
	 * Returns the correct user identifier to use, based on the ldap server type
	 */
	function getUserIdentifier() {
		if ($this->serverType == "activedirectory") {
			return $this->attr_sambalogin;
		} else {
			return $this->attr_login;
		}
	}

	/**
		* \brief UserAccountControl Flgs to more human understandable form...
		*
		*/
	function parseUACF($uacf) {
		//All flags array
		$flags = array( "TRUSTED_TO_AUTH_FOR_DELEGATION"  =>    16777216,
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
                    "SCRIPT"                          =>    1);

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
		* \brief SamAccountType value to text
		*
		*/
	function parseSAT($samtype) {
		$stypes = array(    805306368    =>    "NORMAL_ACCOUNT",
		805306369    =>    "WORKSTATION_TRUST",
		805306370    =>    "INTERDOMAIN_TRUST",
		268435456    =>    "SECURITY_GLOBAL_GROUP",
		268435457    =>    "DISTRIBUTION_GROUP",
		536870912    =>    "SECURITY_LOCAL_GROUP",
		536870913    =>    "DISTRIBUTION_LOCAL_GROUP");

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

	/**
		* \Parse GroupType value to text
		*
		*/
	function parseGT($grouptype) {
		$gtypes = array(    -2147483643    =>    "SECURITY_BUILTIN_LOCAL_GROUP",
		-2147483644    =>    "SECURITY_DOMAIN_LOCAL_GROUP",
		-2147483646    =>    "SECURITY_GLOBAL_GROUP",
		2              =>    "DISTRIBUTION_GLOBAL_GROUP",
		4              =>    "DISTRIBUTION_DOMAIN_LOCAL_GROUP",
		8              =>    "DISTRIBUTION_UNIVERSAL_GROUP");

		$retval = "";
		while (list($gt, $val) = each($gtypes)) {
			if ($grouptype == $gt) {
				$retval = $val;
				break;
			}
		}
		if (empty($retval)) $retval = "UNKNOWN_TYPE_" . $grouptype;

		return($retval);
	}


	/*
	 *	\brief		Convertit le temps ActiveDirectory en Unix timestamp
	 *	\param		string		AD time to convert
	 *	\return		string		Unix timestamp
	 */
	function convert_time($value)
	{
		$dateLargeInt=$value; // nano secondes depuis 1601 !!!!
		$secsAfterADEpoch = $dateLargeInt / (10000000); // secondes depuis le 1 jan 1601
		$ADToUnixConvertor=((1970-1601) * 365.242190) * 86400; // UNIX start date - AD start date * jours * secondes
		$unixTimeStamp=intval($secsAfterADEpoch-$ADToUnixConvertor); // Unix time stamp
		return $unixTimeStamp;
	}


	/**
	 *  \brief      Convert a string into output/memory charset
	 *  \param      str            	String to convert
	 *  \param		pagecodefrom	Page code of src string
	 *  \return     string         	Converted string
	 */
	function convToOutputCharset($str,$pagecodefrom='UTF-8')
	{
		global $conf;
		if ($pagecodefrom == 'ISO-8859-1' && $conf->file->character_set_client == 'UTF-8')  $str=utf8_encode($str);
		if ($pagecodefrom == 'UTF-8' && $conf->file->character_set_client == 'ISO-8859-1')  $str=utf8_decode($str);
		return $str;
	}

	/**
	 *  \brief      Convert a string from output/memory charset
	 *  \param      str            	String to convert
	 *  \param		pagecodeto		Page code for result string
	 *  \return     string         	Converted string
	 */
	function convFromOutputCharset($str,$pagecodeto='UTF-8')
	{
		global $conf;
		if ($pagecodeto == 'ISO-8859-1' && $conf->file->character_set_client == 'UTF-8')  $str=utf8_decode($str);
		if ($pagecodeto == 'UTF-8' && $conf->file->character_set_client == 'ISO-8859-1')	$str=utf8_encode($str);
		return $str;
	}
}


?>