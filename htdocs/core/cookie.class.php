<?php
/* Copyright (C) 2009  Regis Houssin  <regis@dolibarr.fr>
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
 */

/**
 \file       htdocs/core/cookie.class.php
 \ingroup    core
 \version	$Id$
 \brief      File of class to manage cookies
 */


  class DolCookie
  {
  	var $myKey; 
    var $myCookie; 
    var $myValue; 
    var $myExpire;
    var $myPath;
    var $myDomain;
    var	$mySsecure;
    var $cookiearray;
    var $cookie;

    /**
	   *      \brief      Constructor
	   *      \param      key      Personnal key
	   */
    function DolCookie($key = '')
    {
    	$this->myKey = $key;
      $this->cookiearray = array();
      $this->cookie = "";
      $this->myCookie = "";
      $this->myValue = "";
    }
  
    
    /**
	   *      \brief      Encrypt en create the cookie
	   */
    function cryptCookie()
    {
    	if (!empty($this->myKey))
    	{
    		$valuecrypt = base64_encode($this->myValue);
    		for ($f=0 ; $f<=strlen($valuecrypt)-1; $f++)
    		{
    			$this->cookie .= intval(ord($valuecrypt[$f]))*$this->myKey."|";
    		}
      }
      else
      {
      	$this->cookie = $this->myValue;
      }
      
      setcookie($this->myCookie, $this->cookie, $this->myExpire, $this->myPath, $this->myDomain, $this->mySecure);
    }

    /**
	   *      \brief      Decrypt the cookie
	   */
    function decryptCookie()
    {
    	if (!empty($this->myKey))
    	{
    		$this->cookiearray = explode("|",$_COOKIE[$this->myCookie]);
    		$this->myValue = "" ;
    		for ($f=0 ; $f<=count($this->cookiearray)-2; $f++)
    		{
    			$this->myValue .= strval(chr($this->cookiearray[$f]/$this->myKey));
    		}
    		
    		return(base64_decode($this->myValue)) ;
    	}
    	else
    	{
    		return($_COOKIE[$this->myCookie]);
    	}
    }

    /**
	   *      \brief  Set and create the cookie
	   *      \param  cookie  Cookie name
	   *      \param  value   Cookie value
	   */
    function _setCookie($cookie, $value, $expire=0, $path="/", $domain="", $secure=0)
    {
    	$this->myCookie = $cookie;
    	$this->myValue = $value;
    	$this->myExpire = $expire;
    	$this->myPath = $path;
    	$this->myDomain = $domain;
    	$this->mySsecure = $secure;
    	
    	//print 'key='.$this->myKey.' name='.$this->myCookie.' value='.$this->myValue.' expire='.$this->myExpire;
    	
    	$this->cryptCookie();
    }
    
    /**
	   *      \brief   Get the cookie
	   *      \param   cookie         Cookie name
	   *      \param   value          Cookie value
	   *      \return  decryptValue   Decrypted value
	   */
    function _getCookie($cookie)
    {
    	$this->myCookie = $cookie;
    	
    	$decryptValue = $this->decryptCookie();
    	
    	return $decryptValue;
    }
    
    /**
     *  \brief   	Add cookie cryptkey in config file
     *	\return		int		<0 if KO, >0 if OK
     */
    function add_cookiecryptkeyconf()
    {
    	dol_syslog("cookie.class::add_cookiecryptkeyconf", LOG_DEBUG);
    	$config = '';
    	$added=0;
    	
    	if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','r'))
    	{
    		while(!feof($fp))
    		{
    			$buffer = fgets($fp,4096);
    			
    			if (strstr($buffer,"\$dolibarr_main_cookie_cryptkey"))
    			{
    				$config .= "\$dolibarr_main_cookie_cryptkey=\"$this->myKey\";\n";
    				$added++;
    			}
    			else
    			{
    				$config .= $buffer;
    			}
    		}
    		fclose($fp);

    		if (!$added)
    		{
    			$config = '';
    			
    			if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','r'))
    			{
    				while(!feof($fp))
    				{
    					$buffer = fgets($fp,4096);
    					
    					if (strstr($buffer,"\$dolibarr_main_authentication"))
    					{
    						$config .= $buffer;
    						$config .= "\$dolibarr_main_cookie_cryptkey=\"$this->myKey\";\n";
    					}
    					else
    					{
    						$config .= $buffer;
    					}
    				}
    				fclose($fp);
    			}
    			else
    			{
    				dol_syslog("cookie.class::add_cookiecryptkeyconf Failed to read conf.php", LOG_ERR);
    				return -2;
    			}
    		}

    		$file=DOL_DOCUMENT_ROOT.'/conf/conf.php';
    		if ($fp = @fopen($file,'w'))
    		{
    			fputs($fp, $config, strlen($config));
    			fclose($fp);
    			// It's config file, so we set permission for creator only
    			// @chmod($file, octdec('0600'));
    			
    			return 1;
    		}
    		else
    		{
    			dol_syslog("cookie.class::add_cookiecryptkeyconf Failed to open conf.php file for writing", LOG_WARNING);
    			return -1;
    		}
    	}
    	else
    	{
    		dol_syslog("cookie.class::add_cookiecryptkeyconf Failed to read conf.php", LOG_ERR);
    		return -2;
    	}
    }

  }
  
?>