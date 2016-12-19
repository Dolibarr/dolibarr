<?php
/* Copyright (C) 2009-2015  Regis Houssin  <regis.houssin@capnetworks.com>
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
 */

/**
 *	\file       htdocs/core/class/cookie.class.php
 *	\ingroup    core
 *	\brief      File of class to manage cookies
 */


/**
 *	Class to manage cookies.
 *  This class is used by external module multicompany but will be removed soon only and must not be used by
 *
 *  @deprecated PHP already provide function to read/store a cookie. No need to use a dedicated class. Also storing sensitive information into cookie is forbidden, so encryption is useless.
 *  If a data is sensitive, it must be stored into database (if we need a long term retention) or into session.
 */
class DolCookie
{
	private $_myKey;
	private $_iv;

	var $myCookie;
	var $myValue;
	var $myExpire;
	var $myPath;
	var $myDomain;
	var	$mySecure;
	var $cookie;

	/**
	 * Constructor
	 *
	 * @param string $key Personnal key
	 * @deprecated
	 */
	function __construct($key = '')
	{
		$this->_myKey = hash('sha256', $key, TRUE);
		$this->_iv = md5(md5($this->_myKey));
		$this->cookie = "";
		$this->myCookie = "";
		$this->myValue = "";
	}


	/**
	 * Encrypt en create the cookie
	 *
	 * @return	void
	 */
	private function _cryptCookie()
	{
		if (!empty($this->_myKey) && !empty($this->_iv))
		{
			$valuecrypt = base64_encode($this->myValue);
			$this->cookie = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->_myKey, $valuecrypt, MCRYPT_MODE_CBC, $this->_iv));
		}
		else
		{
			$this->cookie = $this->myValue;
		}

		setcookie($this->myCookie, $this->cookie, $this->myExpire, $this->myPath, $this->myDomain, $this->mySecure);
	}

	/**
	 * Decrypt the cookie
	 *
	 * @return	string
	 */
	private function _decryptCookie()
	{
		if (!empty($this->_myKey) && !empty($this->_iv))
		{
			$this->cookie = $_COOKIE[$this->myCookie];
			$this->myValue = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->_myKey, base64_decode($this->cookie), MCRYPT_MODE_CBC, $this->_iv));

			return(base64_decode($this->myValue));
		}
		else
		{
			return($_COOKIE[$this->myCookie]);
		}
	}

	/**
	 * Set and create the cookie
	 *
	 * @param  	string		$cookie  	Cookie name
	 * @param  	string		$value   	Cookie value
	 * @param	integer		$expire		Expiration
	 * @param	string		$path		Path of cookie
	 * @param	string		$domain		Domain name
	 * @param	int			$secure		0 or 1
	 * @return	void
	 */
	public function setCookie($cookie, $value, $expire=0, $path="/", $domain="", $secure=0)
	{
		$this->myCookie = $cookie;
		$this->myValue = $value;
		$this->myExpire = $expire;
		$this->myPath = $path;
		$this->myDomain = $domain;
		$this->mySecure = $secure;

		//print 'key='.$this->myKey.' name='.$this->myCookie.' value='.$this->myValue.' expire='.$this->myExpire;

		$this->_cryptCookie();
	}

	/**
	 *  Get the cookie
	 *
	 *  @param   	string		$cookie         Cookie name
	 *  @return  	string						Decrypted value
	 */
	public function getCookie($cookie)
	{
		$this->myCookie = $cookie;

		$decryptValue = $this->_decryptCookie();

		return $decryptValue;
	}

}

