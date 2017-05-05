<?php
/* Copyright (C) 2000-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2013	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Christophe Combelles		<ccomb@free.fr>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2016	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2017	Alexandre Spangaro			<aspangaro@zendsi.com>
 * Copyright (C) 2014		Cédric GROSS				<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
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
 *	\file			htdocs/core/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */

include_once DOL_DOCUMENT_ROOT .'/core/lib/json.lib.php';

/**
 * Function to return value of a static property when class
 * name is dynamically defined (not hard coded).
 * This is because $myclass::$myvar works from PHP 5.3.0+ only
 *
 * @param	string 	$class		Class name
 * @param 	string 	$member		Name of property
 * @return 	mixed				Return value of static property
 * @deprecated Dolibarr now requires 5.3.0+, use $class::$property syntax
 * @see https://php.net/manual/language.oop5.static.php
 */
function getStaticMember($class, $member)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	// This part is deprecated. Uncomment if for php 5.2.*, and comment next isset class::member
	/*if (version_compare(phpversion(), '5.3.0', '<'))
	{
		if (is_object($class)) $class = get_class($class);
		$classObj = new ReflectionClass($class);
		$result = null;

		$found=0;
		foreach($classObj->getStaticProperties() as $prop => $value)
		{
			if ($prop == $member)
			{
				$result = $value;
				$found++;
				break;
			}
		}

		if ($found) return $result;
	}*/

	if (isset($class::$member)) return $class::$member;
	dol_print_error('','Try to get a static member "'.$member.'" in class "'.$class.'" that does not exists or is not static.');
	return null;
}


/**
 * Return a DoliDB instance (database handler).
 *
 * @param   string	$type		Type of database (mysql, pgsql...)
 * @param	string	$host		Address of database server
 * @param	string	$user		Nom de l'utilisateur autorise
 * @param	string	$pass		Mot de passe
 * @param	string	$name		Nom de la database
 * @param	int		$port		Port of database server
 * @return	DoliDB				A DoliDB instance
 */
function getDoliDBInstance($type, $host, $user, $pass, $name, $port)
{
	require_once DOL_DOCUMENT_ROOT ."/core/db/".$type.'.class.php';

	$class='DoliDB'.ucfirst($type);
	$dolidb=new $class($type, $host, $user, $pass, $name, $port);
	return $dolidb;
}

/**
 * 	Get list of entity id to use
 *
 * 	@param	string	$element	Current element 
 *                              'societe', 'socpeople', 'actioncomm', 'agenda', 'resource', 
 *                              'product', 'productprice', 'stock',
 *                              'propal', 'facture', 'facture_fourn',
 *                              'categorie', 'bank_account', 'bank_account', 'adherent', 'user',  
 *                              'commande', 'commande_fournisseur', 'expedition', 'intervention', 'survey',
 *                              'contract', 'tax', 'expensereport', 'holiday', 'multicurrency', 'project',
 *                              'email_template', 'event',  
 * 	@param	int		$shared		0=Return id of entity, 1=Return id entity + shared entities
 * 	@return	mixed				Entity id(s) to use
 */
function getEntity($element=false, $shared=0)
{
	global $conf, $mc;

	// For backward compatibilty
	if ($element == 'actioncomm') $element='agenda';
	if ($element == 'fichinter')  $element='intervention';
	if ($element == 'categorie')  $element='category';
	
	if (is_object($mc))
	{
		return $mc->getEntity($element, $shared);
	}
	else
	{
		$out='';
		$addzero = array('user', 'usergroup');
		if (in_array($element, $addzero)) $out.= '0,';
		$out.= $conf->entity;
		return $out;
	}
}

/**
 * Return information about user browser
 *
 * Returns array with the following format:
 * array(
 *  'browsername' => Browser name (firefox|chrome|iceweasel|epiphany|safari|opera|ie|unknown)
 *  'browserversion' => Browser version. Empty if unknown
 *  'browseros' => Set with mobile OS (android|blackberry|ios|palm|symbian|webos|maemo|windows|unknown)
 *  'layout' => (tablet|phone|classic)
 *  'phone' => empty if not mobile, (android|blackberry|ios|palm|unknown) if mobile
 *  'tablet' => true/false
 * )
 *
 * @param string $user_agent Content of $_SERVER["HTTP_USER_AGENT"] variable
 * @return	array Check function documentation
 */
function getBrowserInfo($user_agent)
{
	include_once DOL_DOCUMENT_ROOT.'/includes/mobiledetect/mobiledetectlib/Mobile_Detect.php';

	$name='unknown';
	$version='';
	$os='unknown';
	$phone = '';

	$detectmobile = new Mobile_Detect(null, $user_agent);
	$tablet = $detectmobile->isTablet();

	if ($detectmobile->isMobile()) {

		$phone = 'unknown';

		// If phone/smartphone, we set phone os name.
		if ($detectmobile->is('AndroidOS')) {
			$os = $phone = 'android';
		} elseif ($detectmobile->is('BlackBerryOS')) {
			$os = $phone = 'blackberry';
		} elseif ($detectmobile->is('iOS')) {
			$os = 'ios';
			$phone = 'iphone';
		} elseif ($detectmobile->is('PalmOS')) {
			$os = $phone = 'palm';
		} elseif ($detectmobile->is('SymbianOS')) {
			$os = 'symbian';
		} elseif ($detectmobile->is('webOS')) {
			$os = 'webos';
		} elseif ($detectmobile->is('MaemoOS')) {
			$os = 'maemo';
		} elseif ($detectmobile->is('WindowsMobileOS') || $detectmobile->is('WindowsPhoneOS')) {
			$os = 'windows';
		}
	}

	// OS
	if (preg_match('/linux/i', $user_agent))	{ $os='linux'; }
	elseif (preg_match('/macintosh/i', $user_agent))	{ $os='macintosh'; }

	// Name
	if (preg_match('/firefox(\/|\s)([\d\.]*)/i', $user_agent, $reg))      { $name='firefox';   $version=$reg[2]; }
	elseif (preg_match('/chrome(\/|\s)([\d\.]+)/i', $user_agent, $reg))   { $name='chrome';    $version=$reg[2]; }    // we can have 'chrome (Mozilla...) chrome x.y' in one string
	elseif (preg_match('/chrome/i', $user_agent, $reg))                   { $name='chrome'; }
	elseif (preg_match('/iceweasel/i', $user_agent))                      { $name='iceweasel'; }
	elseif (preg_match('/epiphany/i', $user_agent))                       { $name='epiphany';  }
	elseif (preg_match('/safari(\/|\s)([\d\.]*)/i', $user_agent, $reg))   { $name='safari';    $version=$reg[2]; }	// Safari is often present in string for mobile but its not.
	elseif (preg_match('/opera(\/|\s)([\d\.]*)/i', $user_agent, $reg))    { $name='opera';     $version=$reg[2]; }
	elseif (preg_match('/(MSIE\s([0-9]+\.[0-9]))|.*(Trident\/[0-9]+.[0-9];\srv:([0-9]+\.[0-9]+))/i', $user_agent, $reg))  { $name='ie'; $version=end($reg); }    // MS products at end
	elseif (preg_match('/l(i|y)n(x|ks)(\(|\/|\s)*([\d\.]+)/i', $user_agent, $reg)) { $name='lynxlinks'; $version=$reg[4]; }
	
	if ($tablet) {
		$layout = 'tablet';
	} elseif ($phone) {
		$layout = 'phone';
	} else {
		$layout = 'classic';
	}

	return array(
		'browsername' => $name,
		'browserversion' => $version,
		'browseros' => $os,
		'layout' => $layout,
		'phone' => $phone,
		'tablet' => $tablet
	);
}

/**
 *  Function called at end of web php process
 *
 *  @return	void
 */
function dol_shutdown()
{
	global $conf,$user,$langs,$db;
	$disconnectdone=false; $depth=0;
	if (is_object($db) && ! empty($db->connected)) { $depth=$db->transaction_opened; $disconnectdone=$db->close(); }
	dol_syslog("--- End access to ".$_SERVER["PHP_SELF"].(($disconnectdone && $depth)?' (Warn: db disconnection forced, transaction depth was '.$depth.')':''), (($disconnectdone && $depth)?LOG_WARNING:LOG_INFO));
}


/**
 *  Return value of a param into GET or POST supervariable
 *
 *  @param	string	$paramname   Name of parameter to found
 *  @param	string	$check	     Type of check (''=no check,  'int'=check it's numeric, 'alpha'=check it's text and sign, 'aZ'=check it's a-z only, 'array'=check it's array, 'san_alpha'=Use filter_var with FILTER_SANITIZE_STRING (do not use this for free text string), 'day', 'month', 'year', 'custom'= custom filter specify $filter and $options)
 *  @param	int		$method	     Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get, 4 = post then get then cookie)
 *  @param  int     $filter      Filter to apply when $check is set to custom. (See http://php.net/manual/en/filter.filters.php for détails)
 *  @param  mixed   $options     Options to pass to filter_var when $check is set to custom
 *  @return string|string[]      Value found (string or array), or '' if check fails
 */
function GETPOST($paramname,$check='',$method=0,$filter=NULL,$options=NULL)
{
	if (empty($method)) $out = isset($_GET[$paramname])?$_GET[$paramname]:(isset($_POST[$paramname])?$_POST[$paramname]:'');
	elseif ($method==1) $out = isset($_GET[$paramname])?$_GET[$paramname]:'';
	elseif ($method==2) $out = isset($_POST[$paramname])?$_POST[$paramname]:'';
	elseif ($method==3) $out = isset($_POST[$paramname])?$_POST[$paramname]:(isset($_GET[$paramname])?$_GET[$paramname]:'');
	elseif ($method==4) $out = isset($_POST[$paramname])?$_POST[$paramname]:(isset($_GET[$paramname])?$_GET[$paramname]:(isset($_COOKIE[$paramname])?$_COOKIE[$paramname]:''));
	else return 'BadThirdParameterForGETPOST';

	if (! empty($check))
	{
	    if (! is_array($out) && preg_match('/^__([a-z0-9]+)__$/i', $out, $reg))
	    {
	        if ($reg[1] == 'DAY')
	        {
    	        $tmp=dol_getdate(dol_now(), true);
    	        $out = $tmp['mday'];
	        }
	        elseif ($reg[1] == 'MONTH')
	        {
    	        $tmp=dol_getdate(dol_now(), true);
    	        $out = $tmp['mon'];
	        }	         
	        elseif ($reg[1] == 'YEAR')
	        {
	            $tmp=dol_getdate(dol_now(), true);
	            $out = $tmp['year'];
	        }
	        elseif ($reg[1] == 'MYCOUNTRYID')
	        {
	            global $mysoc;
	            $out = $mysoc->country_id;
	        }
	    }
	     
	    switch ($check)
	    {
	        case 'int':
	            if (! is_numeric($out)) { $out=''; }
	            break;
	        case 'alpha':
	            $out=trim($out);
	            // '"' is dangerous because param in url can close the href= or src= and add javascript functions.
	            // '../' is dangerous because it allows dir transversals
	            if (preg_match('/"/',$out)) $out='';
	            else if (preg_match('/\.\.\//',$out)) $out='';
	            break;
	        case 'san_alpha':
	            $out=filter_var($out,FILTER_SANITIZE_STRING);
	            break;
	        case 'aZ':
	            $out=trim($out);
	            if (preg_match('/[^a-z]+/i',$out)) $out='';
	            break;
	        case 'aZ09':
	            $out=trim($out);
	            if (preg_match('/[^a-z0-9]+/i',$out)) $out='';
	            break;
	        case 'array':
	            if (! is_array($out) || empty($out)) $out=array();
	            break;
			case 'nohtml':
				$out=dol_string_nohtmltag($out);
				break;
	        case 'custom':
	            if (empty($filter)) return 'BadFourthParameterForGETPOST';
	            $out=filter_var($out, $filter, $options);
	            break;
	    }
	}

	return $out;
}


/**
 *  Return a prefix to use for this Dolibarr instance for session or cookie names.
 *  This prefix is unique for instance and avoid conflict between multi-instances,
 *  even when having two instances with one root dir or two instances in virtual servers
 *
 *  @param  string  $mode       '' or 'email'              
 *  @return	string      		A calculated prefix
 */
function dol_getprefix($mode='')
{
    global $conf;
    
    // If MAIL_PREFIX_FOR_EMAIL_ID is set and prefix is for email
    if ($mode == 'email' && ! empty($conf->global->MAIL_PREFIX_FOR_EMAIL_ID))
    {
        if ($conf->global->MAIL_PREFIX_FOR_EMAIL_ID != 'SERVER_NAME') return $conf->global->MAIL_PREFIX_FOR_EMAIL_ID;
        else if (isset($_SERVER["SERVER_NAME"])) return $_SERVER["SERVER_NAME"];
    }

	if (isset($_SERVER["SERVER_NAME"]) && isset($_SERVER["DOCUMENT_ROOT"]))
	{
		return dol_hash($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT);
		// Use this for a "clear" cookie name
		//return dol_sanitizeFileName($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT);
	}
	else return dol_hash(DOL_DOCUMENT_ROOT.DOL_URL_ROOT);
}

/**
 *	Make an include_once using default root and alternate root if it fails.
 *  To link to a core file, use include(DOL_DOCUMENT_ROOT.'/pathtofile')
 *  To link to a module file from a module file, use include './mymodulefile';
 *  To link to a module file from a core file, then this function can be used (call by hook / trigger / speciales pages)
 *
 * 	@param	string	$relpath	Relative path to file (Ie: mydir/myfile, ../myfile, ...)
 * 	@param	string	$classname	Class name (deprecated)
 *  @return bool                True if load is a success, False if it fails
 */
function dol_include_once($relpath, $classname='')
{
	global $conf,$langs,$user,$mysoc;   // Do not remove this. They must be defined for files we include. Other globals var must be retreived with $GLOBALS['var']

	$fullpath = dol_buildpath($relpath);

	if (!file_exists($fullpath)) {
		dol_syslog('functions::dol_include_once Tried to load unexisting file: '.$relpath, LOG_ERR);
		return false;
	}

	if (! empty($classname) && ! class_exists($classname)) {
		return include $fullpath;
	} else {
		return include_once $fullpath;
	}
}


/**
 *	Return path of url or filesystem. Return alternate root if exists.
 *
 * 	@param	string	$path		Relative path to file (if mode=0) or relative url (if mode=1). Ie: mydir/myfile, ../myfile
 *  @param	int		$type		0=Used for a Filesystem path, 1=Used for an URL path (output relative), 2=Used for an URL path (output full path using same host that current url), 3=Used for an URL path (output full path using host defined into $dolibarr_main_url_root of conf file)
 *  @return string				Full filesystem path (if mode=0), Full url path (if mode=1)
 */
function dol_buildpath($path, $type=0)
{
	global $conf;

	$path=preg_replace('/^\//','',$path);

	if (empty($type))	// For a filesystem path
	{
		$res = DOL_DOCUMENT_ROOT.'/'.$path;	// Standard value
		foreach ($conf->file->dol_document_root as $key => $dirroot)	// ex: array(["main"]=>"/home/main/htdocs", ["alt0"]=>"/home/dirmod/htdocs", ...)
		{
			if ($key == 'main') continue;
			if (file_exists($dirroot.'/'.$path))
			{
				$res=$dirroot.'/'.$path;
				break;
			}
		}
	}
	else				// For an url path
	{
		// We try to get local path of file on filesystem from url
		// Note that trying to know if a file on disk exist by forging path on disk from url
		// works only for some web server and some setup. This is bugged when
		// using proxy, rewriting, virtual path, etc...
		$res='';
		if ($type == 1) $res = DOL_URL_ROOT.'/'.$path;			// Standard value
		if ($type == 2) $res = DOL_MAIN_URL_ROOT.'/'.$path;		// Standard value
		if ($type == 3) $res = DOL_URL_ROOT.'/'.$path;
		
		foreach ($conf->file->dol_document_root as $key => $dirroot)	// ex: array(["main"]=>"/home/main/htdocs", ["alt0"]=>"/home/dirmod/htdocs", ...)
		{
			if ($key == 'main') 
			{
			    if ($type == 3)
			    {
			        global $dolibarr_main_url_root;
			        	
			        // Define $urlwithroot
			        $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
			        $urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
			        //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

			        $res=(preg_match('/^http/i',$conf->file->dol_url_root[$key])?'':$urlwithroot).'/'.$path;     // Test on start with http is for old conf syntax
			    }
			    continue;
			}
			preg_match('/^([^\?]+(\.css\.php|\.css|\.js\.php|\.js|\.png|\.jpg|\.php)?)/i',$path,$regs);    // Take part before '?'
			if (! empty($regs[1]))
			{
				//print $key.'-'.$dirroot.'/'.$path.'-'.$conf->file->dol_url_root[$type].'<br>'."\n";
				if (file_exists($dirroot.'/'.$regs[1]))
				{
					if ($type == 1)
					{
						$res=(preg_match('/^http/i',$conf->file->dol_url_root[$key])?'':DOL_URL_ROOT).$conf->file->dol_url_root[$key].'/'.$path;
					}
					if ($type == 2)
					{
					    $res=(preg_match('/^http/i',$conf->file->dol_url_root[$key])?'':DOL_MAIN_URL_ROOT).$conf->file->dol_url_root[$key].'/'.$path;
					}
					if ($type == 3)
					{
					    global $dolibarr_main_url_root;
					    
					    // Define $urlwithroot
					    $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
					    $urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
					    //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
					    					
					    $res=(preg_match('/^http/i',$conf->file->dol_url_root[$key])?'':$urlwithroot).$conf->file->dol_url_root[$key].'/'.$path;     // Test on start with http is for old conf syntax
					}
					break;
				}
			}
		}
	}

	return $res;
}

/**
 *	Create a clone of instance of object (new instance with same properties)
 * 	This function works for both PHP4 and PHP5
 *
 * 	@param	object	$object		Object to clone
 *	@return object				Object clone
 *  @deprecated Dolibarr no longer supports PHP4, use PHP5 native clone construct
 *  @see https://php.net/manual/language.oop5.cloning.php
 */
function dol_clone($object)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	$myclone = clone $object;
	return $myclone;
}

/**
 *	Optimize a size for some browsers (phone, smarphone, ...)
 *
 * 	@param	int		$size		Size we want
 * 	@param	string	$type		Type of optimizing:
 * 								'' = function used to define a size for truncation
 * 								'width' = function is used to define a width
 *	@return int					New size after optimizing
 */
function dol_size($size,$type='')
{
	global $conf;
	if (empty($conf->dol_optimize_smallscreen)) return $size;
	if ($type == 'width' && $size > 250) return 250;
	else return 10;
}


/**
 *	Clean a string to use it as a file name
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace bad chars with
 *  @param	int	    $unaccent		1=Remove also accent (default), 0 do not remove them
 *	@return string          		String cleaned (a-zA-Z_)
 *
 * 	@see        	dol_string_nospecial, dol_string_unaccent, dol_sanitizePathName
 */
function dol_sanitizeFileName($str,$newstr='_',$unaccent=1)
{
	$filesystem_forbidden_chars = array('<','>',':','/','\\','?','*','|','"','°');
	return dol_string_nospecial($unaccent?dol_string_unaccent($str):$str, $newstr, $filesystem_forbidden_chars);
}

/**
 *	Clean a string to use it as a path name
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace bad chars with
 *  @param	int	    $unaccent		1=Remove also accent (default), 0 do not remove them
 *	@return string          		String cleaned (a-zA-Z_)
 *
 * 	@see        	dol_string_nospecial, dol_string_unaccent, dol_sanitizeFileName
 */
function dol_sanitizePathName($str,$newstr='_',$unaccent=1)
{
    $filesystem_forbidden_chars = array('<','>','?','*','|','"','°');
    return dol_string_nospecial($unaccent?dol_string_unaccent($str):$str, $newstr, $filesystem_forbidden_chars);
}

/**
 *	Clean a string from all accent characters to be used as ref, login or by dol_sanitizeFileName
 *
 *	@param	string	$str			String to clean
 *	@return string   	       		Cleaned string
 *
 * 	@see    		dol_sanitizeFilename, dol_string_nospecial
 */
function dol_string_unaccent($str)
{
	if (utf8_check($str))
	{
		// See http://www.utf8-chartable.de/
		$string = rawurlencode($str);
		$replacements = array(
		'%C3%80' => 'A','%C3%81' => 'A','%C3%82' => 'A','%C3%83' => 'A','%C3%84' => 'A','%C3%85' => 'A',
		'%C3%88' => 'E','%C3%89' => 'E','%C3%8A' => 'E','%C3%8B' => 'E',
		'%C3%8C' => 'I','%C3%8D' => 'I','%C3%8E' => 'I','%C3%8F' => 'I',
		'%C3%92' => 'O','%C3%93' => 'O','%C3%94' => 'O','%C3%95' => 'O','%C3%96' => 'O',
		'%C3%99' => 'U','%C3%9A' => 'U','%C3%9B' => 'U','%C3%9C' => 'U',
		'%C3%A0' => 'a','%C3%A1' => 'a','%C3%A2' => 'a','%C3%A3' => 'a','%C3%A4' => 'a','%C3%A5' => 'a',
		'%C3%A7' => 'c',
		'%C3%A8' => 'e','%C3%A9' => 'e','%C3%AA' => 'e','%C3%AB' => 'e',
		'%C3%AC' => 'i','%C3%AD' => 'i','%C3%AE' => 'i','%C3%AF' => 'i',
		'%C3%B1' => 'n',
		'%C3%B2' => 'o','%C3%B3' => 'o','%C3%B4' => 'o','%C3%B5' => 'o','%C3%B6' => 'o',
		'%C3%B9' => 'u','%C3%BA' => 'u','%C3%BB' => 'u','%C3%BC' => 'u',
		'%C3%BF' => 'y'
		);
		$string=strtr($string, $replacements);
		return rawurldecode($string);
	}
	else
	{
		// See http://www.ascii-code.com/
		$string = strtr(
			$str,
			"\xC0\xC1\xC2\xC3\xC4\xC5\xC7
			\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
			\xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD
			\xE0\xE1\xE2\xE3\xE4\xE5\xE7\xE8\xE9\xEA\xEB
			\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8
			\xF9\xFA\xFB\xFC\xFD\xFF",
			"AAAAAAC
			EEEEIIIIDN
			OOOOOUUUY
			aaaaaaceeee
			iiiidnooooo
			uuuuyy"
		);
		$string = strtr($string, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH", "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));
		return $string;
	}
}

/**
 *	Clean a string from all punctuation characters to use it as a ref or login
 *
 *	@param	string	$str            	String to clean
 * 	@param	string	$newstr				String to replace forbidden chars with
 *  @param  array	$badcharstoreplace  List of forbidden characters
 * 	@return string          			Cleaned string
 *
 * 	@see    		dol_sanitizeFilename, dol_string_unaccent
 */
function dol_string_nospecial($str,$newstr='_',$badcharstoreplace='')
{
	$forbidden_chars_to_replace=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
	$forbidden_chars_to_remove=array();
	if (is_array($badcharstoreplace)) $forbidden_chars_to_replace=$badcharstoreplace;
	//$forbidden_chars_to_remove=array("(",")");

	return str_replace($forbidden_chars_to_replace,$newstr,str_replace($forbidden_chars_to_remove,"",$str));
}


/**
 * Encode string for xml usage
 *
 * @param 	string	$string		String to encode
 * @return	string				String encoded
 */
function dolEscapeXML($string)
{
	return strtr($string, array('\''=>'&apos;','"'=>'&quot;','&'=>'&amp;','<'=>'&lt;','>'=>'&gt;'));
}

/**
 *  Returns text escaped for inclusion into javascript code
 *
 *  @param      string		$stringtoescape		String to escape
 *  @param		int		$mode				0=Escape also ' and " into ', 1=Escape ' but not " for usage into 'string', 2=Escape " but not ' for usage into "string", 3=Escape ' and " with \
 *  @param		int		$noescapebackslashn	0=Escape also \n. 1=Do not escape \n.
 *  @return     string     		 				Escaped string. Both ' and " are escaped into ' if they are escaped.
 */
function dol_escape_js($stringtoescape, $mode=0, $noescapebackslashn=0)
{
	// escape quotes and backslashes, newlines, etc.
	$substitjs=array("&#039;"=>"\\'","\r"=>'\\r');
	//$substitjs['</']='<\/';	// We removed this. Should be useless.
	if (empty($noescapebackslashn)) { $substitjs["\n"]='\\n'; $substitjs['\\']='\\\\'; }
	if (empty($mode)) { $substitjs["'"]="\\'"; $substitjs['"']="\\'"; }
	else if ($mode == 1) $substitjs["'"]="\\'";
	else if ($mode == 2) { $substitjs['"']='\\"'; }
	else if ($mode == 3) { $substitjs["'"]="\\'"; $substitjs['"']="\\\""; }
	return strtr($stringtoescape, $substitjs);
}


/**
 *  Returns text escaped for inclusion in HTML alt or title tags, or into values of HTML input fields.
 *
 *  @param      string		$stringtoescape		String to escape
 *  @param		int			$keepb				1=Preserve b tags (otherwise, remove them)
 *  @param      int         $keepn              1=Preserve \r\n strings (otherwise, remove them)
 *  @return     string     				 		Escaped string
 *
 *  @see		dol_string_nohtmltag
 */
function dol_escape_htmltag($stringtoescape, $keepb=0, $keepn=0)
{
	// escape quotes and backslashes, newlines, etc.
	$tmp=dol_html_entity_decode($stringtoescape,ENT_COMPAT,'UTF-8');
	if (! $keepb) $tmp=strtr($tmp, array("<b>"=>'','</b>'=>''));
	if (! $keepn) $tmp=strtr($tmp, array("\r"=>'\\r',"\n"=>'\\n'));
	return dol_htmlentities($tmp,ENT_COMPAT,'UTF-8');
}


/**
 * Convert a string to lower. Never use strtolower because it does not works with UTF8 strings.
 *
 * @param 	string		$utf8_string		String to encode
 * @return 	string							String converted
 */
function dol_strtolower($utf8_string)
{
	return mb_strtolower($utf8_string, "UTF-8");
}

/**
 * Convert a string to upper. Never use strtolower because it does not works with UTF8 strings.
 *
 * @param 	string		$utf8_string		String to encode
 * @return 	string							String converted
 */
function dol_strtoupper($utf8_string)
{
	return mb_strtoupper($utf8_string, "UTF-8");
}


/**
 *	Write log message into outputs. Possible outputs can be:
 *	SYSLOG_HANDLERS = ["mod_syslog_file"]  		file name is then defined by SYSLOG_FILE
 *	SYSLOG_HANDLERS = ["mod_syslog_syslog"]  	facility is then defined by SYSLOG_FACILITY
 *  Warning, syslog functions are bugged on Windows, generating memory protection faults. To solve
 *  this, use logging to files instead of syslog (see setup of module).
 *  Note: If SYSLOG_FILE_NO_ERROR defined, we never output any error message when writing to log fails.
 *  Note: You can get log message into html sources by adding parameter &logtohtml=1 (constant MAIN_LOGTOHTML must be set)
 *  This function works only if syslog module is enabled.
 * 	This must not use any call to other function calling dol_syslog (avoid infinite loop).
 *
 * 	@param  string		$message				Line to log. ''=Show nothing
 *  @param  int			$level					Log level
 *												On Windows LOG_ERR=4, LOG_WARNING=5, LOG_NOTICE=LOG_INFO=6, LOG_DEBUG=6 si define_syslog_variables ou PHP 5.3+, 7 si dolibarr
 *												On Linux   LOG_ERR=3, LOG_WARNING=4, LOG_INFO=6, LOG_DEBUG=7
 *  @param	int			$ident					1=Increase ident of 1, -1=Decrease ident of 1
 *  @param	string		$suffixinfilename		When output is a file, append this suffix into default log filename.
 *  @param	string		$restricttologhandler	Output log only for this log handler
 *  @return	void
 */
function dol_syslog($message, $level = LOG_INFO, $ident = 0, $suffixinfilename='', $restricttologhandler='')
{
	global $conf, $user;

	// If syslog module enabled
	if (empty($conf->syslog->enabled)) return;

	if (! empty($message))
	{
    	// Test log level
    	$logLevels = array(LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG);
    	if (!in_array($level, $logLevels, true))
    	{
    		throw new Exception('Incorrect log level');
    	}
    	if ($level > $conf->global->SYSLOG_LEVEL) return;
    
    	// If adding log inside HTML page is required
    	if (! empty($_REQUEST['logtohtml']) && (! empty($conf->global->MAIN_ENABLE_LOG_TO_HTML) || ! empty($conf->global->MAIN_LOGTOHTML)))   // MAIN_LOGTOHTML kept for backward compatibility
    	{
    		$conf->logbuffer[] = dol_print_date(time(),"%Y-%m-%d %H:%M:%S")." ".$message;
    	}
    
    	//TODO: Remove this. MAIN_ENABLE_LOG_INLINE_HTML should be deprecated and use a log handler dedicated to HTML output
    	// If enable html log tag enabled and url parameter log defined, we show output log on HTML comments
    	if (! empty($conf->global->MAIN_ENABLE_LOG_INLINE_HTML) && ! empty($_GET["log"]))
    	{
    		print "\n\n<!-- Log start\n";
    		print $message."\n";
    		print "Log end -->\n";
    	}
    
    	$data = array(
    		'message' => $message,
    		'script' => (isset($_SERVER['PHP_SELF'])? basename($_SERVER['PHP_SELF'],'.php') : false),
    		'level' => $level,
    		'user' => ((is_object($user) && $user->id) ? $user->login : false),
    		'ip' => false
    	);
    
    	if (! empty($_SERVER["REMOTE_ADDR"])) $data['ip'] = $_SERVER['REMOTE_ADDR'];
    	// This is when PHP session is ran inside a web server but not inside a client request (example: init code of apache)
    	else if (! empty($_SERVER['SERVER_ADDR'])) $data['ip'] = $_SERVER['SERVER_ADDR'];
    	// This is when PHP session is ran outside a web server, like from Windows command line (Not always defined, but useful if OS defined it).
    	else if (! empty($_SERVER['COMPUTERNAME'])) $data['ip'] = $_SERVER['COMPUTERNAME'].(empty($_SERVER['USERNAME'])?'':'@'.$_SERVER['USERNAME']);
    	// This is when PHP session is ran outside a web server, like from Linux command line (Not always defined, but usefull if OS defined it).
    	else if (! empty($_SERVER['LOGNAME'])) $data['ip'] = '???@'.$_SERVER['LOGNAME'];
    	// Loop on each log handler and send output
    	foreach ($conf->loghandlers as $loghandlerinstance)
    	{
    		if ($restricttologhandler && $loghandlerinstance->code != $restricttologhandler) continue;
    		$loghandlerinstance->export($data,$suffixinfilename);
    	}
    	unset($data);
	}

	if (! empty($ident))
	{
		foreach ($conf->loghandlers as $loghandlerinstance)
		{
			$loghandlerinstance->setIdent($ident);
		}
	}
}


/**
 *	Show tab header of a card
 *
 *	@param	array	$links				Array of tabs. Currently initialized by calling a function xxx_admin_prepare_head
 *	@param	string	$active     		Active tab name (document', 'info', 'ldap', ....)
 *	@param  string	$title      		Title
 *	@param  int		$notab				0=Add tab header, 1=no tab header. If you set this to 1, using dol_fiche_end() to close tab is not required.
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodyle/img/myimg.png',1) for $picto.
 * 	@return	void
 */
function dol_fiche_head($links=array(), $active='0', $title='', $notab=0, $picto='', $pictoisfullpath=0)
{
	print dol_get_fiche_head($links, $active, $title, $notab, $picto, $pictoisfullpath);
}

/**
 *  Show tab header of a card
 *
 *	@param	array	$links				Array of tabs
 *	@param	string	$active     		Active tab name
 *	@param  string	$title      		Title
 *	@param  int		$notab				0=Add tab header, 1=no tab header. If you set this to 1, using dol_fiche_end() to close tab is not required.
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodyle/img/myimg.png',1) for $picto.
 * 	@return	string
 */
function dol_get_fiche_head($links=array(), $active='', $title='', $notab=0, $picto='', $pictoisfullpath=0)
{
	global $conf, $langs, $hookmanager;

	if ($notab == -1) $notab = 0;  // For better compatiblity with modules for 6.0
	
	$out="\n".'<div class="tabs" data-role="controlgroup" data-type="horizontal">'."\n";

	// Show title
	$showtitle=1;
	if (! empty($conf->dol_optimize_smallscreen)) $showtitle=0;
	if (! empty($title) && $showtitle)
	{
		$limittitle=30;
		$out.='<a class="tabTitle">';
		if ($picto) $out.=img_picto($title,($pictoisfullpath?'':'object_').$picto,'',$pictoisfullpath).' ';
		$out.='<span class="tabTitleText">'.dol_trunc($title,$limittitle).'</span>';
		$out.='</a>';
	}

	// Define max of key (max may be higher than sizeof because of hole due to module disabling some tabs).
	$maxkey=-1;
	if (is_array($links) && ! empty($links))
	{
		$keys=array_keys($links);
		if (count($keys)) $maxkey=max($keys);
	}

	// Show tabs
	$bactive=false;
	// if =0 we don't use the feature
	$limittoshow=(empty($conf->global->MAIN_MAXTABS_IN_CARD)?99:$conf->global->MAIN_MAXTABS_IN_CARD);
	$displaytab=0;
	$nbintab=0;
    $popuptab=0;
	for ($i = 0 ; $i <= $maxkey ; $i++)
	{
		if ((is_numeric($active) && $i == $active) || (! empty($links[$i][2]) && ! is_numeric($active) && $active == $links[$i][2]))
		{
			// si l'active est présent dans la box
			if ($i >= $limittoshow)
				$limittoshow--;
		}
	}

	for ($i = 0 ; $i <= $maxkey ; $i++)
	{
		if ((is_numeric($active) && $i == $active) || (! empty($links[$i][2]) && ! is_numeric($active) && $active == $links[$i][2]))
		{
			$isactive=true;
			$bactive=true;
		}
		else
			$isactive=false;

		if ($i < $limittoshow || $isactive)
		{
			$out.='<div class="inline-block tabsElem'.($isactive ? ' tabsElemActive' : '').((! $isactive && ! empty($conf->global->MAIN_HIDE_INACTIVETAB_ON_PRINT))?' hideonprint':'').'"><!-- id tab = '.(empty($links[$i][2])?'':$links[$i][2]).' -->';
			if (isset($links[$i][2]) && $links[$i][2] == 'image')
			{
				if (!empty($links[$i][0]))
				{
					$out.='<a data-role="button" class="tabimage" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				}
				else
				{
					$out.='<span data-role="button" class="tabspan">'.$links[$i][1].'</span>'."\n";
				}
			}
			else if (! empty($links[$i][1]))
			{
				//print "x $i $active ".$links[$i][2]." z";
				if ($isactive)
				{
					$out.='<a data-role="button"'.(! empty($links[$i][2])?' id="'.$links[$i][2].'"':'').' class="tabactive tab inline-block" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				}
				else
				{
					$out.='<a data-role="button"'.(! empty($links[$i][2])?' id="'.$links[$i][2].'"':'').' class="tabunactive tab inline-block" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				}
			}
			$out.='</div>';
		}
		else
		{
		    // The popup with the other tabs
			if (! $popuptab)
			{
			    $popuptab=1;
			    $outmore.='<div class="popuptabset">';
			}
		    $outmore.='<div class="popuptab" style="display:inherit;">';
			if (isset($links[$i][2]) && $links[$i][2] == 'image')
			{
				if (!empty($links[$i][0]))
					$outmore.='<a class="tabimage" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				else
					$outmore.='<span class="tabspan">'.$links[$i][1].'</span>'."\n";

			}
			else if (! empty($links[$i][1]))
				$outmore.='<a'.(! empty($links[$i][2])?' id="'.$links[$i][2].'"':'').' class="inline-block" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";

			$outmore.='</div>';

			$nbintab++;
		}
		$displaytab=$i;
	}
	if ($popuptab) $outmore.='</div>';

	if ($displaytab > $limittoshow)
	{
		$tabsname=str_replace("@", "", $picto);
		$out.='<div id="moretabs'.$tabsname.'" class="inline-block tabsElem">';
		$out.='<a href="#" data-role="button" class="tab moretab inline-block">'.$langs->trans("More").'... ('.$nbintab.')</a>';
		$out.='<div id="moretabsList'.$tabsname.'" style="position: absolute; left: -999em;text-align: left;margin:0px;padding:2px">'.$outmore.'</div>';
		$out.="</div>\n";

		$out.="<script>";
		$out.="$('#moretabs".$tabsname."').mouseenter( function() { $('#moretabsList".$tabsname."').css('left','auto');});";
		$out.="$('#moretabs".$tabsname."').mouseleave( function() { $('#moretabsList".$tabsname."').css('left','-999em');});";
		$out.="</script>";
	}

	$out.="</div>\n";

	if (! $notab) $out.="\n".'<div class="tabBar">'."\n";

	$parameters=array('tabname' => $active, 'out' => $out);
	$reshook=$hookmanager->executeHooks('printTabsHead',$parameters);	// This hook usage is called just before output the head of tabs. Take also a look at "completeTabsHead"
	if ($reshook > 0)
	{
		$out = $hookmanager->resPrint;
	}
	
	return $out;
}

/**
 *  Show tab footer of a card
 *
 *  @param	int		$notab       0=Add tab footer, 1=no tab footer
 *  @return	void
 */
function dol_fiche_end($notab=0)
{
	print dol_get_fiche_end($notab);
}

/**
 *	Return tab footer of a card
 *
 *	@param  int		$notab		0=Add tab footer, 1=no tab footer
 *  @return	string
 */
function dol_get_fiche_end($notab=0)
{
	if (! $notab) return "\n</div>\n";
	else return '';
}

/**
 *  Show tab footer of a card
 *
 *  @param	object	$object			Object to show
 *  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
 *  @param	string	$morehtml  		More html content to output just before the nav bar
 *  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
 *  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field)
 *  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
 *  @param	string	$morehtmlref  	More html to show after ref
 *  @param	string	$moreparam  	More param to add in nav link url.
 *	@param	int		$nodbprefix		Do not include DB prefix to forge table name
 *	@param	string	$morehtmlleft	More html code to show before ref
 *	@param	string	$morehtmlstatus	More html code to show under navigation arrows
 *  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (add css 'arearefnobottom' on div)
 *	@param	string	$morehtmlright	More html code to show before navigation arrows
 *  @return	void
 */
function dol_banner_tab($object, $paramid, $morehtml='', $shownav=1, $fieldid='rowid', $fieldref='ref', $morehtmlref='', $moreparam='', $nodbprefix=0, $morehtmlleft='', $morehtmlstatus='', $onlybanner=0, $morehtmlright='')
{
	global $conf, $form, $user, $langs;

	$maxvisiblephotos=1;
	$showimage=1;
	$showbarcode=empty($conf->barcode->enabled)?0:($object->barcode?1:0);
	if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;
	$modulepart='unknown';
	if ($object->element == 'societe')		$modulepart='societe';
	if ($object->element == 'contact')		$modulepart='contact';
	if ($object->element == 'member')		$modulepart='memberphoto';
	if ($object->element == 'user')			$modulepart='userphoto';
	if ($object->element == 'product')		$modulepart='product';
	
	if ($object->element == 'product')
	{
	    $width=80; $cssclass='photoref';
        $showimage=$object->is_photo_available($conf->product->multidir_output[$object->entity]);
	    $maxvisiblephotos=(isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO)?$conf->global->PRODUCT_MAX_VISIBLE_PHOTO:5);
		if ($conf->browser->phone) $maxvisiblephotos=1;
		if ($showimage) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos($conf->product->multidir_output[$object->entity],'small',$maxvisiblephotos,0,0,0,$width,0).'</div>';
        else 
        {
			if (!empty($conf->global->PRODUCT_NODISPLAYIFNOPHOTO)) {
				$nophoto='';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			elseif ($conf->browser->layout != 'phone') {    // Show no photo link
				$nophoto='/public/theme/common/nophoto.png';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			}
        }
	}
	else 
	{
        if ($showimage) 
        {
            if ($modulepart != 'unknown') 
            {
                $phototoshow = $form->showphoto($modulepart,$object,0,0,0,'photoref','small',1,0,$maxvisiblephotos);
                if ($phototoshow)
                {
                    $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
                    $morehtmlleft.=$phototoshow;
                    $morehtmlleft.='</div>';
                }
            }
            elseif ($conf->browser->layout != 'phone')      // Show No photo link (picto of pbject)
            {
                $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
                if ($object->element == 'action') 
                {
                    $cssclass='photorefcenter';
                    $nophoto=img_picto('', 'title_agenda', '', false, 1);
                    $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.$nophoto.'"></div></div>';
                }
                else
                {
                    $width=14; $cssclass='photorefcenter';
                    $picto = $object->picto;
                    if ($object->element == 'project' && ! $object->public) $picto = 'project'; // instead of projectpub
    				$nophoto=img_picto('', 'object_'.$picto, '', false, 1);
    				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.$nophoto.'"></div></div>';
                }
                $morehtmlleft.='</div>';
            }
        }
	}
	if ($showbarcode) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object).'</div>';
	if ($object->element == 'societe' && ! empty($conf->use_javascript_ajax) && $user->rights->societe->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
		$morehtmlstatus.=ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
	} 
	elseif ($object->element == 'product')
	{
	    //$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
        if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
            $morehtmlstatus.=ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
        } else {
            $morehtmlstatus.=$object->getLibStatut(5,0);
        }
        $morehtmlstatus.=' &nbsp; ';
        //$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
	    if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
            $morehtmlstatus.=ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
        } else {
            $morehtmlstatus.=$object->getLibStatut(5,1);
        }
	}
	elseif ($object->element == 'facture' || $object->element == 'invoice' || $object->element == 'invoice_supplier')
	{
	    $tmptxt=$object->getLibStatut(6, $object->totalpaye);
	    if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5, $object->totalpaye); 
		$morehtmlstatus.=$tmptxt;
	}
	elseif ($object->element == 'chargesociales')
	{
	    $tmptxt=$object->getLibStatut(6, $object->totalpaye);
	    if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5, $object->totalpaye); 
		$morehtmlstatus.=$tmptxt;
	}
	elseif ($object->element == 'loan')
	{
	    $tmptxt=$object->getLibStatut(6, $object->totalpaye);
	    if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5, $object->totalpaye); 
		$morehtmlstatus.=$tmptxt;
	}
	elseif ($object->element == 'contrat') 
	{
        if ($object->statut==0) $morehtmlstatus.=$object->getLibStatut(2);
        else $morehtmlstatus.=$object->getLibStatut(4);
	}
	else { // Generic case
	    $tmptxt=$object->getLibStatut(6);
	    if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5); 
		$morehtmlstatus.=$tmptxt;
	}
	if (! empty($object->name_alias)) $morehtmlref.='<div class="refidno">'.$object->name_alias.'</div>';      // For thirdparty
	
	if ($object->element == 'product' || $object->element == 'bank_account')
	{
		if(! empty($object->label)) $morehtmlref.='<div class="refidno">'.$object->label.'</div>';
	}

	if ($object->element != 'product' && $object->element != 'bookmark') 
	{
    	$morehtmlref.='<div class="refidno">';
    	$morehtmlref.=$object->getBannerAddress('refaddress',$object);
    	$morehtmlref.='</div>';
	}
	if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && in_array($object->element, array('societe', 'contact', 'member', 'product')))
	{
		$morehtmlref.='<div style="clear: both;"></div><div class="refidno">';
		$morehtmlref.=$langs->trans("TechnicalID").': '.$object->id;
		$morehtmlref.='</div>';
	}
	
	print '<div class="'.($onlybanner?'arearefnobottom ':'arearef ').'heightref valignmiddle" width="100%">';
	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	print '</div>';
	print '<div class="underrefbanner clearboth"></div>';
}

/**
 * Show a string with the label tag dedicated to the HTML edit field.
 *
 * @param	string	$langkey		Translation key
 * @param 	string	$fieldkey		Key of the html select field the text refers to
 * @param	int		$fieldrequired	1=Field is mandatory
 * @deprecated Form::editfieldkey
 */
function fieldLabel($langkey, $fieldkey, $fieldrequired=0)
{
	global $conf, $langs;
	$ret='';
	if ($fieldrequired) $ret.='<span class="fieldrequired">';
	if (($conf->dol_use_jmobile != 4)) $ret.='<label for="'.$fieldkey.'">';
	$ret.=$langs->trans($langkey);
	if (($conf->dol_use_jmobile != 4)) $ret.='</label>';
	if ($fieldrequired) $ret.='</span>';
	return $ret;
}

/**
 * Return string to add class property on html element with pair/impair.
 *
 * @param	string	$var			0 or 1
 * @param	string	$moreclass		More class to add
 * @return	string					String to add class onto HTML element
 */
function dol_bc($var,$moreclass='')
{
	global $bc;
	$ret=' '.$bc[$var];
	if ($moreclass) $ret=preg_replace('/class=\"/','class="'.$moreclass.' ',$ret);
	return $ret;
}

/**
 *      Return a formated address (part address/zip/town/state) according to country rules
 *
 *      @param  Object		$object         A company or contact object
 * 	    @param	int			$withcountry	1=Add country into address string
 *      @param	string		$sep			Separator to use to build string
 *      @param	Translate	$outputlangs	Object lang that contains language for text translation.
 *      @return string          			Formated string
 *      @see dol_print_address
 */
function dol_format_address($object,$withcountry=0,$sep="\n",$outputlangs='')
{
	global $conf,$langs;

	$ret='';
	$countriesusingstate=array('AU','CA','US','IN','GB','ES','UK','TR');    // See also MAIN_FORCE_STATE_INTO_ADDRESS

	// Address
	$ret .= $object->address;
	// Zip/Town/State
	if (in_array($object->country_code,array('AU', 'CA', 'US')) || ! empty($conf->global->MAIN_FORCE_STATE_INTO_ADDRESS))   	// US: title firstname name \n address lines \n town, state, zip \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->town;
		if ($object->state)
		{
			$ret.=($ret?", ":'').$object->state;
		}
		if ($object->zip) $ret .= ($ret?", ":'').$object->zip;
	}
	else if (in_array($object->country_code,array('GB','UK'))) // UK: title firstname name \n address lines \n town state \n zip \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->town;
		if ($object->state)
		{
			$ret.=($ret?", ":'').$object->state;
		}
		if ($object->zip) $ret .= ($ret ? $sep : '' ).$object->zip;
	}
	else if (in_array($object->country_code,array('ES','TR'))) // ES: title firstname name \n address lines \n zip town \n state \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->zip;
		$ret .= ($object->town?(($object->zip?' ':'').$object->town):'');
		if ($object->state)
		{
			$ret.="\n".$object->state;
		}
	}
	else                                        		// Other: title firstname name \n address lines \n zip town \n country
	{
		$ret .= $object->zip ? (($ret ? $sep : '' ).$object->zip) : '';
		$ret .= ($object->town?(($object->zip?' ':($ret ? $sep : '' )).$object->town):'');
		if ($object->state && in_array($object->country_code,$countriesusingstate))
		{
			$ret.=($ret?", ":'').$object->state;
		}
	}
	if (! is_object($outputlangs)) $outputlangs=$langs;
	if ($withcountry) $ret.=($object->country_code?($ret?$sep:'').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->country_code)):'');

	return $ret;
}



/**
 *	Format a string.
 *
 *	@param	string	$fmt		Format of strftime function (http://php.net/manual/fr/function.strftime.php)
 *  @param	int		$ts			Timesamp (If is_gmt is true, timestamp is already includes timezone and daylight saving offset, if is_gmt is false, timestamp is a GMT timestamp and we must compensate with server PHP TZ)
 *  @param	int		$is_gmt		See comment of timestamp parameter
 *	@return	string				A formatted string
 */
function dol_strftime($fmt, $ts=false, $is_gmt=false)
{
	if ((abs($ts) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
		return ($is_gmt)? @gmstrftime($fmt,$ts): @strftime($fmt,$ts);
	}
	else return 'Error date into a not supported range';
}

/**
 *	Output date in a string format according to outputlangs (or langs if not defined).
 * 	Return charset is always UTF-8, except if encodetoouput is defined. In this case charset is output charset
 *
 *	@param	int			$time			GM Timestamps date
 *	@param	string		$format      	Output date format (tag of strftime function)
 *										"%d %b %Y",
 *										"%d/%m/%Y %H:%M",
 *										"%d/%m/%Y %H:%M:%S",
 *                                      "%B"=Long text of month, "%A"=Long text of day, "%b"=Short text of month, "%a"=Short text of day
 *										"day", "daytext", "dayhour", "dayhourldap", "dayhourtext", "dayrfc", "dayhourrfc", "...reduceformat"
 * 	@param	string		$tzoutput		true or 'gmt' => string is for Greenwich location
 * 										false or 'tzserver' => output string is for local PHP server TZ usage
 * 										'tzuser' => output string is for user TZ (current browser TZ with current dst)
 *                                      'tzuserrel' => output string is for user TZ (current browser TZ with dst or not, depending on date position)
 *	@param	Translate	$outputlangs	Object lang that contains language for text translation.
 *  @param  boolean		$encodetooutput false=no convert into output pagecode
 * 	@return string      				Formated date or '' if time is null
 *
 *  @see        dol_mktime, dol_stringtotime, dol_getdate
 */
function dol_print_date($time,$format='',$tzoutput='tzserver',$outputlangs='',$encodetooutput=false)
{
	global $conf,$langs;

	// Clean parameters
	$to_gmt=false;
	$offsettz=$offsetdst=0;
	if ($tzoutput)
	{
		$to_gmt=true;	// For backward compatibility
		if (is_string($tzoutput))
		{
			if ($tzoutput == 'tzserver')
			{
				$to_gmt=false;
				$offsettzstring=@date_default_timezone_get();		// Example 'Europe/Berlin' or 'Indian/Reunion'
				$offsettz=0;
				$offsetdst=0;
			}
			elseif ($tzoutput == 'tzuser')
			{
				$to_gmt=true;
				$offsettzstring=(empty($_SESSION['dol_tz_string'])?'UTC':$_SESSION['dol_tz_string']);	// Example 'Europe/Berlin' or 'Indian/Reunion'
				$offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
				$offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
			}
		}
	}
	if (! is_object($outputlangs)) $outputlangs=$langs;
	if (! $format) $format='daytextshort';
	$reduceformat=(! empty($conf->dol_optimize_smallscreen) && in_array($format,array('day','dayhour')))?1:0;
	$formatwithoutreduce = preg_replace('/reduceformat/','',$format);
	if ($formatwithoutreduce != $format) { $format = $formatwithoutreduce; $reduceformat=1; }  // so format 'dayreduceformat' is processed like day
    
	// Change predefined format into computer format. If found translation in lang file we use it, otherwise we use default.
	// TODO Add format daysmallyear and dayhoursmallyear 
	if ($format == 'day')				$format=($outputlangs->trans("FormatDateShort")!="FormatDateShort"?$outputlangs->trans("FormatDateShort"):$conf->format_date_short);
	else if ($format == 'hour')			$format=($outputlangs->trans("FormatHourShort")!="FormatHourShort"?$outputlangs->trans("FormatHourShort"):$conf->format_hour_short);
	else if ($format == 'hourduration')	$format=($outputlangs->trans("FormatHourShortDuration")!="FormatHourShortDuration"?$outputlangs->trans("FormatHourShortDuration"):$conf->format_hour_short_duration);
	else if ($format == 'daytext')			 $format=($outputlangs->trans("FormatDateText")!="FormatDateText"?$outputlangs->trans("FormatDateText"):$conf->format_date_text);
	else if ($format == 'daytextshort')	$format=($outputlangs->trans("FormatDateTextShort")!="FormatDateTextShort"?$outputlangs->trans("FormatDateTextShort"):$conf->format_date_text_short);
	else if ($format == 'dayhour')			 $format=($outputlangs->trans("FormatDateHourShort")!="FormatDateHourShort"?$outputlangs->trans("FormatDateHourShort"):$conf->format_date_hour_short);
	else if ($format == 'dayhoursec')		 $format=($outputlangs->trans("FormatDateHourSecShort")!="FormatDateHourSecShort"?$outputlangs->trans("FormatDateHourSecShort"):$conf->format_date_hour_sec_short);
	else if ($format == 'dayhourtext')		 $format=($outputlangs->trans("FormatDateHourText")!="FormatDateHourText"?$outputlangs->trans("FormatDateHourText"):$conf->format_date_hour_text);
	else if ($format == 'dayhourtextshort') $format=($outputlangs->trans("FormatDateHourTextShort")!="FormatDateHourTextShort"?$outputlangs->trans("FormatDateHourTextShort"):$conf->format_date_hour_text_short);
	// Format not sensitive to language
	else if ($format == 'dayhourlog')		 $format='%Y%m%d%H%M%S';
	else if ($format == 'dayhourldap')		 $format='%Y%m%d%H%M%SZ';
	else if ($format == 'dayhourxcard')	$format='%Y%m%dT%H%M%SZ';
	else if ($format == 'dayxcard')	 	$format='%Y%m%d';
	else if ($format == 'dayrfc')			 $format='%Y-%m-%d';             // DATE_RFC3339
	else if ($format == 'dayhourrfc')		 $format='%Y-%m-%dT%H:%M:%SZ';   // DATETIME RFC3339
	else if ($format == 'standard')		$format='%Y-%m-%d %H:%M:%S';

	if ($reduceformat)
	{
		$format=str_replace('%Y','%y',$format);
		$format=str_replace('yyyy','yy',$format);
	}

	// If date undefined or "", we return ""
	if (dol_strlen($time) == 0) return '';		// $time=0 allowed (it means 01/01/1970 00:00:00)

	// Clean format
	if (preg_match('/%b/i',$format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format=str_replace('%b','__b__',$format);
		$format=str_replace('%B','__B__',$format);
	}
	if (preg_match('/%a/i',$format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format=str_replace('%a','__a__',$format);
		$format=str_replace('%A','__A__',$format);
	}

	// Analyze date
	if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i',$time,$reg)
	|| preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])$/i',$time,$reg))	// Deprecated. Ex: 1970-01-01, 1970-01-01 01:00:00, 19700101010000
	{
		// This part of code should not be used. TODO Remove this.
		dol_syslog("Functions.lib::dol_print_date function call with deprecated value of time in page ".$_SERVER["PHP_SELF"], LOG_WARNING);
		// Date has format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS' or 'YYYYMMDDHHMMSS'
		$syear	= (! empty($reg[1]) ? $reg[1] : '');
		$smonth	= (! empty($reg[2]) ? $reg[2] : '');
		$sday	= (! empty($reg[3]) ? $reg[3] : '');
		$shour	= (! empty($reg[4]) ? $reg[4] : '');
		$smin	= (! empty($reg[5]) ? $reg[5] : '');
		$ssec	= (! empty($reg[6]) ? $reg[6] : '');

		$time=dol_mktime($shour,$smin,$ssec,$smonth,$sday,$syear,true);
		$ret=adodb_strftime($format,$time+$offsettz+$offsetdst,$to_gmt);
	}
	else
	{
		// Date is a timestamps
		if ($time < 100000000000)	// Protection against bad date values
		{
			$ret=adodb_strftime($format,$time+$offsettz+$offsetdst,$to_gmt);	// TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.
		}
		else $ret='Bad value '.$time.' for date';
	}

	if (preg_match('/__b__/i',$format))
	{
		// Here ret is string in PHP setup language (strftime was used). Now we convert to $outputlangs.
		$month=adodb_strftime('%m',$time+$offsettz+$offsetdst);					// TODO Remove this
		if ($encodetooutput)
		{
			$monthtext=$outputlangs->transnoentities('Month'.$month);
			$monthtextshort=$outputlangs->transnoentities('MonthShort'.$month);
		}
		else
		{
			$monthtext=$outputlangs->transnoentitiesnoconv('Month'.$month);
			$monthtextshort=$outputlangs->transnoentitiesnoconv('MonthShort'.$month);
		}
		//print 'monthtext='.$monthtext.' monthtextshort='.$monthtextshort;
		$ret=str_replace('__b__',$monthtextshort,$ret);
		$ret=str_replace('__B__',$monthtext,$ret);
		//print 'x'.$outputlangs->charset_output.'-'.$ret.'x';
		//return $ret;
	}
	if (preg_match('/__a__/i',$format))
	{
		$w=adodb_strftime('%w',$time+$offsettz+$offsetdst);						// TODO Remove this
		$dayweek=$outputlangs->transnoentitiesnoconv('Day'.$w);
		$ret=str_replace('__A__',$dayweek,$ret);
		$ret=str_replace('__a__',dol_substr($dayweek,0,3),$ret);
	}

	return $ret;
}


/**
 *	Return an array with locale date info.
 *  PHP getdate is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 *  WARNING: This function always use PHP server timezone to return locale informations !!!
 *  Usage must be avoid.
 *  FIXME: Replace this with PHP date function and a parameter $gm
 *
 *	@param	int			$timestamp      Timestamp
 *	@param	boolean		$fast           Fast mode
 *	@return	array						Array of informations
 *										If no fast mode:
 *										'seconds' => $secs,
 *										'minutes' => $min,
 *										'hours' => $hour,
 *										'mday' => $day,
 *										'wday' => $dow,		0=sunday, 6=saturday
 *										'mon' => $month,
 *										'year' => $year,
 *										'yday' => floor($secsInYear/$_day_power),
 *										'weekday' => gmdate('l',$_day_power*(3+$dow)),
 *										'month' => gmdate('F',mktime(0,0,0,$month,2,1971)),
 *										If fast mode:
 *										'seconds' => $secs,
 *										'minutes' => $min,
 *										'hours' => $hour,
 *										'mday' => $day,
 *										'mon' => $month,
 *										'year' => $year,
 *										'yday' => floor($secsInYear/$_day_power),
 *										'leap' => $leaf,
 *										'ndays' => $ndays
 * 	@see 								dol_print_date, dol_stringtotime, dol_mktime
 */
function dol_getdate($timestamp,$fast=false)
{
	global $conf;

	$usealternatemethod=false;
	if ($timestamp <= 0) $usealternatemethod=true;				// <= 1970
	if ($timestamp >= 2145913200) $usealternatemethod=true;		// >= 2038

	if ($usealternatemethod)
	{
		$arrayinfo=adodb_getdate($timestamp,$fast);
	}
	else
	{
		$arrayinfo=getdate($timestamp);
	}

	return $arrayinfo;
}

/**
 *	Return a timestamp date built from detailed informations (by default a local PHP server timestamp)
 * 	Replace function mktime not available under Windows if year < 1970
 *	PHP mktime is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 *
 * 	@param	int			$hour			Hour	(can be -1 for undefined)
 *	@param	int			$minute			Minute	(can be -1 for undefined)
 *	@param	int			$second			Second	(can be -1 for undefined)
 *	@param	int			$month			Month (1 to 12)
 *	@param	int			$day			Day (1 to 31)
 *	@param	int			$year			Year
 *	@param	mixed		$gm				True or 1 or 'gmt'=Input informations are GMT values
 *										False or 0 or 'server' = local to server TZ
 *										'user' = local to user TZ
 *										'tz,TimeZone' = use specified timezone
 *	@param	int			$check			0=No check on parameters (Can use day 32, etc...)
 *	@return	int|string					Date as a timestamp, '' or false if error
 * 	@see 								dol_print_date, dol_stringtotime, dol_getdate
 */
function dol_mktime($hour,$minute,$second,$month,$day,$year,$gm=false,$check=1)
{
	global $conf;
	//print "- ".$hour.",".$minute.",".$second.",".$month.",".$day.",".$year.",".$_SERVER["WINDIR"]." -";

	// Clean parameters
	if ($hour   == -1 || empty($hour)) $hour=0;
	if ($minute == -1 || empty($minute)) $minute=0;
	if ($second == -1 || empty($second)) $second=0;

	// Check parameters
	if ($check)
	{
		if (! $month || ! $day)  return '';
		if ($day   > 31) return '';
		if ($month > 12) return '';
		if ($hour  < 0 || $hour   > 24) return '';
		if ($minute< 0 || $minute > 60) return '';
		if ($second< 0 || $second > 60) return '';
	}

	if (method_exists('DateTime','getTimestamp'))
	{
		if (empty($gm) || $gm === 'server')
		{
			$default_timezone=@date_default_timezone_get();		// Example 'Europe/Berlin'
			$localtz = new DateTimeZone($default_timezone);
		}
		else if ($gm === 'user')
		{
			// We use dol_tz_string first because it is more reliable.
			$default_timezone=(empty($_SESSION["dol_tz_string"])?@date_default_timezone_get():$_SESSION["dol_tz_string"]);		// Example 'Europe/Berlin'
			try {
				$localtz = new DateTimeZone($default_timezone);
			}
			catch(Exception $e)
			{
				dol_syslog("Warning dol_tz_string contains an invalid value ".$_SESSION["dol_tz_string"], LOG_WARNING);
				$default_timezone=@date_default_timezone_get();
			}
		}
		else if (strrpos($gm, "tz,") !== false)
		{
			$timezone=str_replace("tz,", "", $gm);  // Example 'tz,Europe/Berlin'
			try
			{
				$localtz = new DateTimeZone($timezone);
			}
			catch(Exception $e)
			{
				dol_syslog("Warning passed timezone contains an invalid value ".$timezone, LOG_WARNING);
			}
		}

		if (empty($localtz)) {
			$localtz = new DateTimeZone('UTC');
		}

		$dt = new DateTime(null,$localtz);
		$dt->setDate($year,$month,$day);
		$dt->setTime((int) $hour, (int) $minute, (int) $second);
		$date=$dt->getTimestamp();	// should include daylight saving time
		return $date;
	}
	else
	{
		dol_print_error('','PHP version must be 5.3+');
		return '';
	}
}


/**
 *	Return date for now. In mot cases, we use this function without parameters (that means GMT time).
 *
 * 	@param	string		$mode	'gmt' => we return GMT timestamp,
 * 								'tzserver' => we add the PHP server timezone
 *  							'tzref' => we add the company timezone
 * 								'tzuser' => we add the user timezone
 *	@return int   $date	Timestamp
 */
function dol_now($mode='gmt')
{
	// Note that gmmktime and mktime return same value (GMT) when used without parameters
	//if ($mode == 'gmt') $ret=gmmktime(); // Strict Standards: gmmktime(): You should be using the time() function instead
	if ($mode == 'gmt') $ret=time();	// Time for now at greenwich.
	else if ($mode == 'tzserver')		// Time for now with PHP server timezone added
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$tzsecond=getServerTimeZoneInt('now');    // Contains tz+dayling saving time
		$ret=dol_now('gmt')+($tzsecond*3600);
	}
	/*else if ($mode == 'tzref')				// Time for now with parent company timezone is added
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$tzsecond=getParentCompanyTimeZoneInt();    // Contains tz+dayling saving time
		$ret=dol_now('gmt')+($tzsecond*3600);
	}*/
	else if ($mode == 'tzuser')				// Time for now with user timezone added
	{
		//print 'eeee'.time().'-'.mktime().'-'.gmmktime();
		$offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
		$offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
		$ret=dol_now('gmt')+($offsettz+$offsetdst);
	}
	return $ret;
}


/**
 * Return string with formated size
 *
 * @param	int		$size		Size to print
 * @param	int		$shortvalue	Tell if we want long value to use another unit (Ex: 1.5Kb instead of 1500b)
 * @param	int		$shortunit	Use short value of size unit
 * @return	string				Link
 */
function dol_print_size($size,$shortvalue=0,$shortunit=0)
{
	global $conf,$langs;
	$level=1024;

	if (! empty($conf->dol_optimize_smallscreen)) $shortunit=1;

	// Set value text
	if (empty($shortvalue) || $size < ($level*10))
	{
		$ret=$size;
		$textunitshort=$langs->trans("b");
		$textunitlong=$langs->trans("Bytes");
	}
	else
	{
		$ret=round($size/$level,0);
		$textunitshort=$langs->trans("Kb");
		$textunitlong=$langs->trans("KiloBytes");
	}
	// Use long or short text unit
	if (empty($shortunit)) { $ret.=' '.$textunitlong; }
	else { $ret.=' '.$textunitshort; }

	return $ret;
}

/**
 * Show Url link
 *
 * @param	string		$url		Url to show
 * @param	string		$target		Target for link
 * @param	int			$max		Max number of characters to show
 * @param	int			$withpicto	With picto
 * @return	string					HTML Link
 */
function dol_print_url($url,$target='_blank',$max=32,$withpicto=0)
{
	global $langs;

	if (empty($url)) return '';

	$link='<a href="';
	if (! preg_match('/^http/i',$url)) $link.='http://';
	$link.=$url;
	$link.='"';
	if ($target) $link.=' target="'.$target.'"';
	$link.='>';
	if (! preg_match('/^http/i',$url)) $link.='http://';
	$link.=dol_trunc($url,$max);
	$link.='</a>';
	return '<div class="nospan float" style="margin-right: 10px">'.($withpicto?img_picto($langs->trans("Url"), 'object_globe.png').' ':'').$link.'</div>';
}

/**
 * Show EMail link
 *
 * @param	string		$email			EMail to show (only email, without 'Name of recipient' before)
 * @param 	int			$cid 			Id of contact if known
 * @param 	int			$socid 			Id of third party if known
 * @param 	int			$addlink		0=no link, 1=email has a html email link (+ link to create action if constant AGENDA_ADDACTIONFOREMAIL is on)
 * @param	int			$max			Max number of characters to show
 * @param	int			$showinvalid	Show warning if syntax email is wrong
 * @param	int			$withpicto		Show picto
 * @return	string						HTML Link
 */
function dol_print_email($email,$cid=0,$socid=0,$addlink=0,$max=64,$showinvalid=1,$withpicto=0)
{
	global $conf,$user,$langs;

	$newemail=$email;

	if (empty($email)) return '&nbsp;';

	if (! empty($addlink))
	{
		$newemail='<a style="text-overflow: ellipsis;" href="';
		if (! preg_match('/^mailto:/i',$email)) $newemail.='mailto:';
		$newemail.=$email;
		$newemail.='">';
		$newemail.=dol_trunc($email,$max);
		$newemail.='</a>';
		if ($showinvalid && ! isValidEmail($email))
		{
			$langs->load("errors");
			$newemail.=img_warning($langs->trans("ErrorBadEMail",$email));
		}

		if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type='AC_EMAIL'; $link='';
			if (! empty($conf->global->AGENDA_ADDACTIONFOREMAIL)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
			if ($link) $newemail='<div>'.$newemail.' '.$link.'</div>';
		}
	}
	else
	{
		if ($showinvalid && ! isValidEmail($email))
		{
			$langs->load("errors");
			$newemail.=img_warning($langs->trans("ErrorBadEMail",$email));
		}
	}
	return '<div class="nospan float" style="margin-right: 10px">'.($withpicto?img_picto($langs->trans("EMail"), 'object_email.png').' ':'').$newemail.'</div>';
}

/**
 * Show Skype link
 *
 * @param	string		$skype			Skype to show (only skype, without 'Name of recipient' before)
 * @param	int 		$cid 			Id of contact if known
 * @param	int 		$socid 			Id of third party if known
 * @param	int 		$addlink		0=no link to create action
 * @param	int			$max			Max number of characters to show
 * @return	string						HTML Link
 */
function dol_print_skype($skype,$cid=0,$socid=0,$addlink=0,$max=64)
{
	global $conf,$user,$langs;

	$newskype=$skype;

	if (empty($skype)) return '&nbsp;';

	if (! empty($addlink))
	{
		$newskype =img_picto($langs->trans("Skype"), 'object_skype.png');
		$newskype.= '&nbsp;';
		$newskype.=dol_trunc($skype,$max);
		$newskype.= '&nbsp;';
		$newskype.='<a href="skype:';
		$newskype.=dol_trunc($skype,$max);
		$newskype.='?call" alt="'.$langs->trans("Call").'&nbsp;'.$skype.'" title="'.$langs->trans("Call").'&nbsp;'.$skype.'">';
		$newskype.='<img src="'.DOL_URL_ROOT.'/theme/common/skype_callbutton.png" border="0">';
		$newskype.='</a>&nbsp;&nbsp;&nbsp;<a href="skype:';
		$newskype.=dol_trunc($skype,$max);
		$newskype.='?chat" alt="'.$langs->trans("Chat").'&nbsp;'.$skype.'" title="'.$langs->trans("Chat").'&nbsp;'.$skype.'">';
		$newskype.='<img src="'.DOL_URL_ROOT.'/theme/common/skype_chatbutton.png" border="0">';
		$newskype.='</a>';

		if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type='AC_SKYPE'; $link='';
			if (! empty($conf->global->AGENDA_ADDACTIONFORSKYPE)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
			$newskype='<div class="divskype nowrap">'.$newskype.($link?' '.$link:'').'</div>';
		}
	}
	else
	{
		$langs->load("errors");
		$newskype.=img_warning($langs->trans("ErrorBadSkype",$skype));
	}
	return $newskype;
}

/**
 * 	Format phone numbers according to country
 *
 * 	@param  string  $phone          Phone number to format
 * 	@param  string  $countrycode    Country code to use for formatting
 * 	@param 	int		$cid 		    Id of contact if known
 * 	@param 	int		$socid          Id of third party if known
 * 	@param 	string	$addlink	    ''=no link to create action, 'AC_TEL'=add link to clicktodial (if module enabled) and add link to create event (if conf->global->AGENDA_ADDACTIONFORPHONE set)
 * 	@param 	string	$separ 		    Separation between numbers for a better visibility example : xx.xx.xx.xx.xx
 *  @param	string  $withpicto      Show picto
 *  @param	string	$titlealt	    Text to show on alt
 *  @param  int     $adddivfloat    Add div float around phone.
 * 	@return string 				    Formated phone number
 */
function dol_print_phone($phone,$countrycode='',$cid=0,$socid=0,$addlink='',$separ="&nbsp;",$withpicto='',$titlealt='',$adddivfloat=0)
{
	global $conf,$user,$langs,$mysoc;

	// Clean phone parameter
	$phone = preg_replace("/[\s.-]/","",trim($phone));
	if (empty($phone)) { return ''; }
	if (empty($countrycode)) $countrycode=$mysoc->country_code;

	// Short format for small screens
	if ($conf->dol_optimize_smallscreen) $separ='';

	$newphone=$phone;
	if (strtoupper($countrycode) == "FR")
	{
		// France
		if (dol_strlen($phone) == 10) {
			$newphone=substr($newphone,0,2).$separ.substr($newphone,2,2).$separ.substr($newphone,4,2).$separ.substr($newphone,6,2).$separ.substr($newphone,8,2);
		}
		elseif (dol_strlen($newphone) == 7)
		{
			$newphone=substr($newphone,0,3).$separ.substr($newphone,3,2).$separ.substr($newphone,5,2);
		}
		elseif (dol_strlen($newphone) == 9)
		{
			$newphone=substr($newphone,0,2).$separ.substr($newphone,2,3).$separ.substr($newphone,5,2).$separ.substr($newphone,7,2);
		}
		elseif (dol_strlen($newphone) == 11)
		{
			$newphone=substr($newphone,0,3).$separ.substr($newphone,3,2).$separ.substr($newphone,5,2).$separ.substr($newphone,7,2).$separ.substr($newphone,9,2);
		}
		elseif (dol_strlen($newphone) == 12)
		{
			$newphone=substr($newphone,0,4).$separ.substr($newphone,4,2).$separ.substr($newphone,6,2).$separ.substr($newphone,8,2).$separ.substr($newphone,10,2);
		}
	}

	if (strtoupper($countrycode) == "CA")
	{
	    if (dol_strlen($phone) == 10) {
	        $newphone=($separ!=''?'(':'').substr($newphone,0,3).($separ!=''?')':'').$separ.substr($newphone,3,3).($separ!=''?'-':'').substr($newphone,6,4);
	    }
	}
	
	if (! empty($addlink))	// Link on phone number (+ link to add action if conf->global->AGENDA_ADDACTIONFORPHONE set)
	{
		if (! empty($conf->browser->phone) || (! empty($conf->clicktodial->enabled) && ! empty($conf->global->CLICKTODIAL_USE_TEL_LINK_ON_PHONE_NUMBERS)))	// If phone or option for, we use link of phone
		{
			$newphone ='<a href="tel:'.$phone.'"';
			$newphone.='>'.$phone.'</a>';
		}
		else if (! empty($conf->clicktodial->enabled) && $addlink == 'AC_TEL')		// If click to dial, we use click to dial url
		{
			if (empty($user->clicktodial_loaded)) $user->fetch_clicktodial();

			// Define urlmask
			$urlmask='ErrorClickToDialModuleNotConfigured';
			if (! empty($conf->global->CLICKTODIAL_URL)) $urlmask=$conf->global->CLICKTODIAL_URL;
			if (! empty($user->clicktodial_url)) $urlmask=$user->clicktodial_url;

			$clicktodial_poste=(! empty($user->clicktodial_poste)?urlencode($user->clicktodial_poste):'');
			$clicktodial_login=(! empty($user->clicktodial_login)?urlencode($user->clicktodial_login):'');
			$clicktodial_password=(! empty($user->clicktodial_password)?urlencode($user->clicktodial_password):'');
			// This line is for backward compatibility
			$url = sprintf($urlmask, urlencode($phone), $clicktodial_poste, $clicktodial_login, $clicktodial_password);
			// Thoose lines are for substitution
			$substitarray=array('__PHONEFROM__'=>$clicktodial_poste,
								'__PHONETO__'=>urlencode($phone),
								'__LOGIN__'=>$clicktodial_login,
								'__PASS__'=>$clicktodial_password);
			$url = make_substitutions($url, $substitarray);
			$newphonesav=$newphone;
			$newphone ='<a href="'.$url.'"';
			if (! empty($conf->global->CLICKTODIAL_FORCENEWTARGET)) $newphone.=' target="_blank"';
			$newphone.='>'.$newphonesav.'</a>';
		}

		//if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type='AC_TEL'; $link='';
			if ($addlink == 'AC_FAX') $type='AC_FAX';
			if (! empty($conf->global->AGENDA_ADDACTIONFORPHONE)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.($cid?'&amp;contactid='.$cid:'').($socid?'&amp;socid='.$socid:'').'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
			if ($link) $newphone='<div>'.$newphone.' '.$link.'</div>';
		}
	}

	if (empty($titlealt))
	{
		$titlealt=($withpicto=='fax'?$langs->trans("Fax"):$langs->trans("Phone"));
	}
	$rep='';
	if ($adddivfloat) $rep.='<div class="nospan float" style="margin-right: 10px">';
	else $rep.='<span style="margin-right: 10px;">';
	$rep.=($withpicto?img_picto($titlealt, 'object_'.($withpicto=='fax'?'phoning_fax':'phoning').'.png').' ':'').$newphone;
	if ($adddivfloat) $rep.='</div>';
	else $rep.='</span>';
	return $rep;
}

/**
 * 	Return an IP formated to be shown on screen
 *
 * 	@param	string	$ip			IP
 * 	@param	int		$mode		0=return IP + country/flag, 1=return only country/flag, 2=return only IP
 * 	@return string 				Formated IP, with country if GeoIP module is enabled
 */
function dol_print_ip($ip,$mode=0)
{
	global $conf,$langs;

	$ret='';

	if (empty($mode)) $ret.=$ip;

	if (! empty($conf->geoipmaxmind->enabled) && $mode != 2)
	{
		$datafile=$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;
		//$ip='24.24.24.24';
		//$datafile='E:\Mes Sites\Web\Admin1\awstats\maxmind\GeoIP.dat';    Note that this must be downloaded datafile (not same than datafile provided with ubuntu packages)

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
		$geoip=new DolGeoIP('country',$datafile);
		//print 'ip='.$ip.' databaseType='.$geoip->gi->databaseType." GEOIP_CITY_EDITION_REV1=".GEOIP_CITY_EDITION_REV1."\n";
		//print "geoip_country_id_by_addr=".geoip_country_id_by_addr($geoip->gi,$ip)."\n";
		$countrycode=$geoip->getCountryCodeFromIP($ip);
		if ($countrycode)	// If success, countrycode is us, fr, ...
		{
			if (file_exists(DOL_DOCUMENT_ROOT.'/theme/common/flags/'.$countrycode.'.png'))
			{
				$ret.=' '.img_picto($countrycode.' '.$langs->trans("AccordingToGeoIPDatabase"),DOL_URL_ROOT.'/theme/common/flags/'.$countrycode.'.png','',1);
			}
			else $ret.=' ('.$countrycode.')';
		}
	}

	return $ret;
}

/**
 *  Return country code for current user.
 *  If software is used inside a local network, detection may fails (we need a public ip)
 *
 *  @return     string      Country code (fr, es, it, us, ...)
 */
function dol_user_country()
{
	global $conf,$langs,$user;

	//$ret=$user->xxx;
	$ret='';
	if (! empty($conf->geoipmaxmind->enabled))
	{
		$ip=$_SERVER["REMOTE_ADDR"];
		$datafile=$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;
		//$ip='24.24.24.24';
		//$datafile='E:\Mes Sites\Web\Admin1\awstats\maxmind\GeoIP.dat';
		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
		$geoip=new DolGeoIP('country',$datafile);
		$countrycode=$geoip->getCountryCodeFromIP($ip);
		$ret=$countrycode;
	}
	return $ret;
}

/**
 *  Format address string
 *
 *  @param	string	$address    Address
 *  @param  int		$htmlid     Html ID (for example 'gmap')
 *  @param  int		$mode       thirdparty|contact|member|other
 *  @param  int		$id         Id of object
 *  @param	int		$noprint	No output. Result is the function return
 *  @param  string  $charfornl  Char to use instead of nl2br. '' means we use a standad nl2br.  
 *  @return string|void			Nothing if noprint is 0, formatted address if noprint is 1
 *  @see dol_format_address
 */
function dol_print_address($address, $htmlid, $mode, $id, $noprint=0, $charfornl='')
{
	global $conf, $user, $langs, $hookmanager;

	$out = '';

	if ($address)
	{
        if ($hookmanager) {
            $parameters = array('element' => $mode, 'id' => $id);
            $reshook = $hookmanager->executeHooks('printAddress', $parameters, $address);
            $out.=$hookmanager->resPrint;
        }
        if (empty($reshook))
        {
            if (empty($charfornl)) $out.=nl2br($address);
            else $out.=preg_replace('/[\r\n]+/', $charfornl, $address);
            
            $showgmap=$showomap=0;

            // TODO Add a hook here
            if (($mode=='thirdparty' || $mode =='societe') && ! empty($conf->google->enabled) && ! empty($conf->global->GOOGLE_ENABLE_GMAPS)) $showgmap=1;
            if ($mode=='contact' && ! empty($conf->google->enabled) && ! empty($conf->global->GOOGLE_ENABLE_GMAPS_CONTACTS)) $showgmap=1;
            if ($mode=='member' && ! empty($conf->google->enabled) && ! empty($conf->global->GOOGLE_ENABLE_GMAPS_MEMBERS)) $showgmap=1;
            if (($mode=='thirdparty' || $mode =='societe') && ! empty($conf->openstreetmap->enabled) && ! empty($conf->global->OPENSTREETMAP_ENABLE_MAPS)) $showomap=1;
            if ($mode=='contact' && ! empty($conf->openstreetmap->enabled) && ! empty($conf->global->OPENSTREETMAP_ENABLE_MAPS_CONTACTS)) $showomap=1;
            if ($mode=='member' && ! empty($conf->openstreetmap->enabled) && ! empty($conf->global->OPENSTREETMAP_ENABLE_MAPS_MEMBERS)) $showomap=1;

            if ($showgmap)
            {
                $url=dol_buildpath('/google/gmaps.php?mode='.$mode.'&id='.$id,1);
                $out.=' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'" border="0" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
            }
            if ($showomap)
            {
                $url=dol_buildpath('/openstreetmap/maps.php?mode='.$mode.'&id='.$id,1);
                $out.=' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'_openstreetmap" border="0" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
            }
        }
	}
	if ($noprint) return $out;
	else print $out;
}


/**
 *	Return true if email syntax is ok
 *
 *	@param	    string		$address    			email (Ex: "toto@examle.com", "John Do <johndo@example.com>")
 *  @param		int			$acceptsupervisorkey	If 1, the special string '__SUPERVISOREMAIL__' is also accepted as valid
 *	@return     boolean     						true if email syntax is OK, false if KO or empty string
 */
function isValidEmail($address, $acceptsupervisorkey=0)
{
	if ($acceptsupervisorkey && $address == '__SUPERVISOREMAIL__') return true;
	if (filter_var($address, FILTER_VALIDATE_EMAIL)) return true;

	return false;
}

/**
 *  Return true if phone number syntax is ok
 *  TODO Decide what to do with this
 *
 *  @param	string		$phone		phone (Ex: "0601010101")
 *  @return boolean     			true if phone syntax is OK, false if KO or empty string
 */
function isValidPhone($phone)
{
	return true;
}


/**
 * Make a strlen call. Works even if mbstring module not enabled
 *
 * @param   string		$string				String to calculate length
 * @param   string		$stringencoding		Encoding of string
 * @return  int								Length of string
 */
function dol_strlen($string,$stringencoding='UTF-8')
{
	if (function_exists('mb_strlen')) return mb_strlen($string,$stringencoding);
	else return strlen($string);
}

/**
 * Make a substring. Works even in mbstring module is not enabled.
 *
 * @param	string	$string				String to scan
 * @param	string	$start				Start position
 * @param	int		$length				Length
 * @param   string	$stringencoding		Page code used for input string encoding
 * @return  string						substring
 */
function dol_substr($string,$start,$length,$stringencoding='')
{
	global $langs;

	if (empty($stringencoding)) $stringencoding=$langs->charset_output;

	$ret='';
	if (function_exists('mb_substr'))
	{
		$ret=mb_substr($string,$start,$length,$stringencoding);
	}
	else
	{
		$ret=substr($string,$start,$length);
	}
	return $ret;
}


/**
 *  Show a javascript graph.
 *  Do not use this function anymore. Use DolGraph class instead.
 *
 *  @param		string	$htmlid			Html id name
 *  @param		int		$width			Width in pixel
 *  @param		int		$height			Height in pixel
 *  @param		array	$data			Data array
 *  @param		int		$showlegend		1 to show legend, 0 otherwise
 *  @param		string	$type			Type of graph ('pie', 'barline')
 *  @param		int		$showpercent	Show percent (with type='pie' only)
 *  @param		string	$url			Param to add an url to click values
 *  @param		int		$combineother	0=No combine, 0.05=Combine if lower than 5%
 *  @param      int     $shownographyet Show graph to say there is not enough data
 *  @return		void
 *  @deprecated
 *  @see DolGraph
 */
function dol_print_graph($htmlid,$width,$height,$data,$showlegend=0,$type='pie',$showpercent=0,$url='',$combineother=0.05,$shownographyet=0)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	global $conf,$langs;
	global $theme_datacolor;    // To have var kept when function is called several times

	if ($shownographyet)
	{
	    print '<div class="nographyet" style="width:'.$width.'px;height:'.$height.'px;"></div>';
	    print '<div class="nographyettext">'.$langs->trans("NotEnoughDataYet").'</div>';
	    return;
	}
	
	if (empty($conf->use_javascript_ajax)) return;
	$jsgraphlib='flot';
	$datacolor=array();

	// Load colors of theme into $datacolor array
	$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
	if (is_readable($color_file))
	{
		include_once $color_file;
		if (isset($theme_datacolor))
		{
			$datacolor=array();
			foreach($theme_datacolor as $val)
			{
				$datacolor[]="#".sprintf("%02x",$val[0]).sprintf("%02x",$val[1]).sprintf("%02x",$val[2]);
			}
		}
	}
	print '<div id="'.$htmlid.'" style="width:'.$width.'px;height:'.$height.'px;"></div>';

	// We use Flot js lib
	if ($jsgraphlib == 'flot')
	{
		if ($type == 'pie')
		{
			// data is   array('series'=>array(serie1,serie2,...),
			//                 'seriestype'=>array('bar','line',...),
			//                 'seriescolor'=>array(0=>'#999999',1=>'#999999',...)
			//                 'xlabel'=>array(0=>labelx1,1=>labelx2,...));
			// serieX is array('label'=>'label', data=>val)
			print '
			<script type="text/javascript">
			$(function () {
				var data = '.json_encode($data['series']).';

				function plotWithOptions() {
					$.plot($("#'.$htmlid.'"), data,
					{
						series: {
							pie: {
								show: true,
								radius: 0.8,';
			if ($combineother)
			{
				print '
								combine: {
								 	threshold: '.$combineother.'
								},';
			}
			print '
								label: {
									show: true,
									radius: 0.9,
									formatter: function(label, series) {
										var percent=Math.round(series.percent);
										var number=series.data[0][1];
										return \'';
										print '<div style="font-size:8pt;text-align:center;padding:2px;color:black;">';
										if ($url) print '<a style="color: #FFFFFF;" border="0" href="'.$url.'=">';
										print '\'+'.($showlegend?'number':'label+\' \'+number');
										if (! empty($showpercent)) print '+\'<br/>\'+percent+\'%\'';
										print '+\'';
										if ($url) print '</a>';
										print '</div>\';
									},
									background: {
										opacity: 0.0,
										color: \'#000000\'
									},
								}
							}
						},
						zoom: {
							interactive: true
						},
						pan: {
							interactive: true
						},';
						if (count($datacolor))
						{
							print 'colors: '.(! empty($data['seriescolor']) ? json_encode($data['seriescolor']) : json_encode($datacolor)).',';
						}
						print 'legend: {show: '.($showlegend?'true':'false').', position: \'ne\' }
					});
				}
				plotWithOptions();
			});
			</script>';
		}
		else if ($type == 'barline')
		{
			// data is   array('series'=>array(serie1,serie2,...),
			//                 'seriestype'=>array('bar','line',...),
			//                 'seriescolor'=>array(0=>'#999999',1=>'#999999',...)
			//                 'xlabel'=>array(0=>labelx1,1=>labelx2,...));
			// serieX is array('label'=>'label', data=>array(0=>y1,1=>y2,...)) with same nb of value than into xlabel
			print '
			<script type="text/javascript">
			$(function () {
				var data = [';
				$i=0; $outputserie=0;
				foreach($data['series'] as $serie)
				{
					if ($data['seriestype'][$i]=='line') { $i++; continue; };
					if ($outputserie > 0) print ',';
					print '{ bars: { stack: 0, show: true, barWidth: 0.9, align: \'center\' }, label: \''.dol_escape_js($serie['label']).'\', data: '.json_encode($serie['data']).'}'."\n";
					$outputserie++; $i++;
				}
				if ($outputserie) print ', ';
				//print '];
				//var datalines = [';
				$i=0; $outputserie=0;
				foreach($data['series'] as $serie)
				{
					if (empty($data['seriestype'][$i]) || $data['seriestype'][$i]=='bar') { $i++; continue; };
					if ($outputserie > 0) print ',';
					print '{ lines: { show: true }, label: \''.dol_escape_js($serie['label']).'\', data: '.json_encode($serie['data']).'}'."\n";
					$outputserie++; $i++;
				}
				print '];
				var dataticks = '.json_encode($data['xlabel']).'

				function plotWithOptions() {
					$.plot(jQuery("#'.$htmlid.'"), data,
					{
						series: {
							stack: 0
						},
						zoom: {
							interactive: true
						},
						pan: {
							interactive: true
						},';
						if (count($datacolor))
						{
							print 'colors: '.json_encode($datacolor).',';
						}
						print 'legend: {show: '.($showlegend?'true':'false').'},
						xaxis: {ticks: dataticks}
					});
				}
				plotWithOptions();
			});
			</script>';
		}
		else print 'BadValueForParameterType';
	}
}

/**
 *	Truncate a string to a particular length adding '...' if string larger than length.
 * 	If length = max length+1, we do no truncate to avoid having just 1 char replaced with '...'.
 *  MAIN_DISABLE_TRUNC=1 can disable all truncings
 *
 *	@param	string	$string				String to truncate
 *	@param  int		$size				Max string size visible. 0 for no limit. Final string size can be 1 more (if size was max+1) or 3 more (if we added ...)
 *	@param	string	$trunc				Where to trunc: right, left, middle (size must be a 2 power), wrap
 * 	@param	string	$stringencoding		Tell what is source string encoding
 *  @param	int		$nodot				Truncation do not add ... after truncation. So it's an exact truncation.
 *  @param  int     $display            Trunc is use to display and can be changed for small screen. TODO Remove this param (must be dealt with CSS)
 *	@return string						Truncated string
 */
function dol_trunc($string,$size=40,$trunc='right',$stringencoding='UTF-8',$nodot=0, $display=0)
{
	global $conf;

	if ($size==0 || ! empty($conf->global->MAIN_DISABLE_TRUNC)) return $string;
	
	if (empty($stringencoding)) $stringencoding='UTF-8';
	// reduce for small screen
	if ($conf->dol_optimize_smallscreen==1 && $display==1) $size = round($size/3);

	// We go always here
	if ($trunc == 'right')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
		if (dol_strlen($newstring,$stringencoding) > ($size+($nodot?0:1)))
		return dol_substr($newstring,0,$size,$stringencoding).($nodot?'':'...');
		else
		return $string;
	}
	elseif ($trunc == 'middle')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
		if (dol_strlen($newstring,$stringencoding) > 2 && dol_strlen($newstring,$stringencoding) > ($size+1))
		{
			$size1=round($size/2);
			$size2=round($size/2);
			return dol_substr($newstring,0,$size1,$stringencoding).'...'.dol_substr($newstring,dol_strlen($newstring,$stringencoding) - $size2,$size2,$stringencoding);
		}
		else
		return $string;
	}
	elseif ($trunc == 'left')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
		if (dol_strlen($newstring,$stringencoding) > ($size+1))
		return '...'.dol_substr($newstring,dol_strlen($newstring,$stringencoding) - $size,$size,$stringencoding);
		else
		return $string;
	}
	elseif ($trunc == 'wrap')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
		if (dol_strlen($newstring,$stringencoding) > ($size+1))
		return dol_substr($newstring,0,$size,$stringencoding)."\n".dol_trunc(dol_substr($newstring,$size,dol_strlen($newstring,$stringencoding)-$size,$stringencoding),$size,$trunc);
		else
		return $string;
	}
	else return 'BadParam3CallingDolTrunc';
}

/**
 *	Show picto whatever it's its name (generic function)
 *
 *	@param      string		$titlealt         	Text on title and alt. If text is "TextA:TextB", use Text A on alt and Text B on title. Alt only if param notitle is set to 1.
 *	@param      string		$picto       		Name of image file to show ('filenew', ...)
 *												If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
 *                                  			Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                  			Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                  			Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *	@param		string		$morealt			Add more attribute on img tag (For example 'style="float: right"')
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *	@param		int			$srconly			Return only content of the src attribute of img.
 *  @param		int			$notitle			1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *  @return     string       				    Return img tag
 *  @see        #img_object, #img_picto_common
 */
function img_picto($titlealt, $picto, $morealt = '', $pictoisfullpath = false, $srconly=0, $notitle=0)
{
	global $conf;

	// Define fullpathpicto to use into src
	if ($pictoisfullpath)
	{
		// Clean parameters
		if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto .= '.png';
		$fullpathpicto = $picto;
	}
	else
	{
		// We forge fullpathpicto for image to $path/img/$picto. By default, we take DOL_URL_ROOT/theme/$conf->theme/img/$picto
		$url = DOL_URL_ROOT;
		$theme = $conf->theme;

		$path = 'theme/'.$theme;
		if (! empty($conf->global->MAIN_OVERWRITE_THEME_PATH)) $path = $conf->global->MAIN_OVERWRITE_THEME_PATH.'/theme/'.$theme;	// If the theme does not have the same name as the module
		else if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) $path = $conf->global->MAIN_OVERWRITE_THEME_RES.'/theme/'.$conf->global->MAIN_OVERWRITE_THEME_RES;  // To allow an external module to overwrite image resources whatever is activated theme
		else if (! empty($conf->modules_parts['theme']) && array_key_exists($theme, $conf->modules_parts['theme'])) $path = $theme.'/theme/'.$theme;	// If the theme have the same name as the module

		// If we ask an image into $url/$mymodule/img (instead of default path)
		if (preg_match('/^([^@]+)@([^@]+)$/i',$picto,$regs))
		{
			$picto = $regs[1];
			$path = $regs[2];	// $path is $mymodule
		}
		// Clean parameters
		if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto .= '.png';
		// If alt path are defined, define url where img file is, according to physical path
		foreach ($conf->file->dol_document_root as $type => $dirroot)	// ex: array(["main"]=>"/home/maindir/htdocs", ["alt0"]=>"/home/moddir0/htdocs", ...)
		{
			if ($type == 'main') continue;
			if (file_exists($dirroot.'/'.$path.'/img/'.$picto))	// This need a lot of time, that's why enabling alternative dir like "custom" dir is not recommanded
			{
				$url=DOL_URL_ROOT.$conf->file->dol_url_root[$type];
				break;
			}
		}

		// $url is '' or '/custom', $path is current theme or
		$fullpathpicto = $url.'/'.$path.'/img/'.$picto;
	}

	if ($srconly) return $fullpathpicto;
	else
	{
		$tmparray=array(0=>$titlealt);
		if (preg_match('/:[^\s0-9]/',$titlealt)) $tmparray=explode(':',$titlealt);		// We explode if we have TextA:TextB. Not if we have TextA: TextB
		$title=$tmparray[0];
		$alt=empty($tmparray[1])?'':$tmparray[1];
		return '<img src="'.$fullpathpicto.'" border="0" alt="'.dol_escape_htmltag($alt).'"'.($notitle?'':' title="'.dol_escape_htmltag($title).'"').($morealt?' '.$morealt:'').'>';	// Alt is used for accessibility, title for popup
	}
}

/**
 *	Show a picto called object_picto (generic function)
 *
 *	@param	string	$titlealt			Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$picto				Name of image to show object_picto (example: user, group, action, bill, contract, propal, product, ...)
 *										For external modules use imagename@mymodule to search into directory "img" of module.
 *	@param	string	$morealt			Add more attribute on img tag (ie: class="datecallink")
 *	@param	int		$pictoisfullpath	If 1, image path is a full path
 *	@param	int		$srconly			Return only content of the src attribute of img.
 *  @param	int		$notitle			1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *	@return	string						Return img tag
 *	@see	#img_picto, #img_picto_common
 */
function img_object($titlealt, $picto, $morealt = '', $pictoisfullpath = false, $srconly=0, $notitle=0)
{
	return img_picto($titlealt, 'object_'.$picto, $morealt, $pictoisfullpath, $srconly, $notitle);
}

/**
 *	Show weather picto
 *
 *	@param      string		$titlealt         	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param      string		$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		string		$morealt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *	@return     string      					Return img tag
 *  @see        #img_object, #img_picto
 */
function img_weather($titlealt, $picto, $morealt = '', $pictoisfullpath = 0)
{
	global $conf;

	if (! preg_match('/(\.png|\.gif)$/i', $picto)) $picto .= '.png';

	$path = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/weather/'.$picto;

	return img_picto($titlealt, $path, $morealt, 1);
}

/**
 *	Show picto (generic function)
 *
 *	@param      string		$titlealt         	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param      string		$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		string		$morealt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *	@return     string      					Return img tag
 *  @see        #img_object, #img_picto
 */
function img_picto_common($titlealt, $picto, $morealt = '', $pictoisfullpath = 0)
{
	global $conf;

	if (! preg_match('/(\.png|\.gif)$/i', $picto)) $picto .= '.png';

	if ($pictoisfullpath) $path = $picto;
	else
	{
		$path = DOL_URL_ROOT.'/theme/common/'.$picto;

		if (! empty($conf->global->MAIN_MODULE_CAN_OVERWRITE_COMMONICONS))
		{
			$themepath = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/'.$picto;

			if (file_exists($themepath)) $path = $themepath;
		}
	}

	return img_picto($titlealt, $path, $morealt, 1);
}

/**
 *	Show logo action
 *
 *	@param	string		$titlealt       Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string		$numaction   	Action id or code to show
 *	@return string      				Return an img tag
 */
function img_action($titlealt, $numaction)
{
	global $conf, $langs;

	if (empty($titlealt) || $titlealt == 'default')
	{
		if ($numaction == '-1' || $numaction == 'ST_NO')			{ $numaction = -1; $titlealt = $langs->transnoentitiesnoconv('ChangeDoNotContact'); }
		elseif ($numaction ==  '0' || $numaction == 'ST_NEVER') 	{ $numaction = 0; $titlealt = $langs->transnoentitiesnoconv('ChangeNeverContacted'); }
		elseif ($numaction ==  '1' || $numaction == 'ST_TODO')  	{ $numaction = 1; $titlealt = $langs->transnoentitiesnoconv('ChangeToContact'); }
		elseif ($numaction ==  '2' || $numaction == 'ST_PEND')  	{ $numaction = 2; $titlealt = $langs->transnoentitiesnoconv('ChangeContactInProcess'); }
		elseif ($numaction ==  '3' || $numaction == 'ST_DONE')  	{ $numaction = 3; $titlealt = $langs->transnoentitiesnoconv('ChangeContactDone'); }
		else { $titlealt = $langs->transnoentitiesnoconv('ChangeStatus '.$numaction); $numaction = 0; }
	}
	if (! is_numeric($numaction)) $numaction=0;

	return img_picto($titlealt, 'stcomm'.$numaction.'.png');
}

/**
 *  Show pdf logo
 *
 *  @param	string		$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param  int		    $size       Taille de l'icone : 3 = 16x16px , 2 = 14x14px
 *  @return string      			Retourne tag img
 */
function img_pdf($titlealt = 'default', $size = 3)
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Show');

	return img_picto($titlealt, 'pdf'.$size.'.png');
}

/**
 *	Show logo +
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit_add($titlealt = 'default', $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Add');

	return img_picto($titlealt, 'edit_add.png', $other);
}
/**
 *	Show logo -
 *
 *	@param	string	$titlealt	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit_remove($titlealt = 'default', $other='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Remove');

	return img_picto($titlealt, 'edit_remove.png', $other);
}

/**
 *	Show logo editer/modifier fiche
 *
 *	@param  string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      Si il faut y mettre le style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit($titlealt = 'default', $float = 0, $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Modify');

	return img_picto($titlealt, 'edit.png', ($float ? 'style="float: '.($langs->tab_translate["DIRECTION"] == 'rtl'?'left':'right').'"' : $other));
}

/**
 *	Show logo view card
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      Si il faut y mettre le style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_view($titlealt = 'default', $float = 0, $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('View');

	$morealt = ($float ? 'style="float: right" ' : '').$other;

	return img_picto($titlealt, 'view.png', $morealt);
}

/**
 *  Show delete logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_delete($titlealt = 'default', $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Delete');

	return img_picto($titlealt, 'delete.png', $other);
}

/**
 *  Show printer logo
 *
 *  @param  string  $titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param  string  $other      Add more attributes on img
 *  @return string              Retourne tag img
 */
function img_printer($titlealt = "default", $other='')
{
    global $conf,$langs;
    if ($titlealt=="default") $titlealt=$langs->trans("Print");
    return img_picto($titlealt,'printer.png',$other);
}

/**
 *	Show help logo with cursor "?"
 *
 * 	@param	int              	$usehelpcursor		Use help cursor
 * 	@param	int|string	        $usealttitle		Text to use as alt title
 * 	@return string            	           			Return tag img
 */
function img_help($usehelpcursor = 1, $usealttitle = 1)
{
	global $conf, $langs;

	if ($usealttitle)
	{
		if (is_string($usealttitle)) $usealttitle = dol_escape_htmltag($usealttitle);
		else $usealttitle = $langs->trans('Info');
	}

	return img_picto($usealttitle, 'info.png', ($usehelpcursor ? 'style="vertical-align: middle; cursor: help"' : 'style="vertical-align: middle;"'));
}

/**
 *	Show info logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_info($titlealt = 'default')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Informations');

	return img_picto($titlealt, 'info.png', 'style="vertical-align: middle;"');
}

/**
 *	Show warning logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$morealt	Add more attribute on img tag (For example 'style="float: right"'). If 1
 *	@return string      		Return img tag
 */
function img_warning($titlealt = 'default', $morealt = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Warning');

	return img_picto($titlealt, 'warning.png', 'class="pictowarning valignmiddle"'.($morealt ? ($morealt == '1' ? ' style="float: right"' : ' '.$morealt): ''));
}

/**
 *  Show error logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_error($titlealt = 'default')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Error');

	return img_picto($titlealt, 'error.png');
}

/**
 *	Show next logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
*	@param	string	$morealt	Add more attribute on img tag (For example 'style="float: right"')
  *	@return string      		Return img tag
 */
function img_next($titlealt = 'default', $morealt='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Next');

	return img_picto($titlealt, 'next.png', $morealt);
}

/**
 *	Show previous logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$morealt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_previous($titlealt = 'default', $morealt='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Previous');

	return img_picto($titlealt, 'previous.png', $morealt);
}

/**
 *	Show down arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected   Selected
 *  @param	string	$moreclass	Add more CSS classes
 *	@return string      		Return img tag
 */
function img_down($titlealt = 'default', $selected = 0, $moreclass='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Down');

	return img_picto($titlealt, ($selected ? '1downarrow_selected.png' : '1downarrow.png'), 'class="imgdown'.($moreclass?" ".$moreclass:"").'"');
}

/**
 *	Show top arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *  @param	string	$moreclass	Add more CSS classes
 *	@return string      		Return img tag
 */
function img_up($titlealt = 'default', $selected = 0, $moreclass='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Up');

	return img_picto($titlealt, ($selected ? '1uparrow_selected.png' : '1uparrow.png'), 'class="imgup'.($moreclass?" ".$moreclass:"").'"');
}

/**
 *	Show left arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *	@param	string	$morealt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_left($titlealt = 'default', $selected = 0, $morealt='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Left');

	return img_picto($titlealt, ($selected ? '1leftarrow_selected.png' : '1leftarrow.png'), $morealt);
}

/**
 *	Show right arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *	@param	string	$morealt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_right($titlealt = 'default', $selected = 0, $morealt='')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Right');

	return img_picto($titlealt, ($selected ? '1rightarrow_selected.png' : '1rightarrow.png'), $morealt);
}

/**
 *	Show tick logo if allowed
 *
 *	@param	string	$allow		Allow
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_allow($allow, $titlealt = 'default')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Active');

	if ($allow == 1) return img_picto($titlealt, 'tick.png');

	return '-';
}


/**
 *	Show MIME img of a file
 *
 *	@param	string	$file		Filename
 * 	@param	string	$titlealt	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string     			Return img tag
 */
function img_mime($file, $titlealt = '')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$mimetype = dol_mimetype($file, '', 1);
	$mimeimg = dol_mimetype($file, '', 2);

	if (empty($titlealt)) $titlealt = 'Mime type: '.$mimetype;

	return img_picto_common($titlealt, 'mime/'.$mimeimg);
}


/**
 *	Show phone logo.
 *  Use img_picto instead.
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$option		Option
 *	@return string      		Return img tag
 *  @deprecated
 *  @see img_picto
 */
function img_phone($titlealt = 'default', $option = 0)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	global $conf,$langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Call');

	if ($option == 1) $img = 'call';
	else $img = 'call_out';

	return img_picto($titlealt, $img);
}

/**
 *  Show search logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_search($titlealt = 'default', $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Search');

	$img = img_picto($titlealt, 'search.png', $other, false, 1);

	$input = '<input type="image" class="liste_titre" name="button_search" src="'.$img.'" ';
	$input.= 'value="'.dol_escape_htmltag($titlealt).'" title="'.dol_escape_htmltag($titlealt).'" >';

	return $input;
}

/**
 *  Show search logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_searchclear($titlealt = 'default', $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Search');

	$img = img_picto($titlealt, 'searchclear.png', $other, false, 1);

	$input = '<input type="image" class="liste_titre" name="button_removefilter" src="'.$img.'" ';
	$input.= 'value="'.dol_escape_htmltag($titlealt).'" title="'.dol_escape_htmltag($titlealt).'" >';

	return $input;
}

/**
 *	Show information for admin users or standard users
 *
 *	@param	string	$text			Text info
 *	@param  integer	$infoonimgalt	Info is shown only on alt of star picto, otherwise it is show on output after the star picto
 *	@param	int		$nodiv			No div
 *  @param  string  $admin          '1'=Info for admin users. '0'=Info for standard users (change only the look), 'xxx'=Other
 *	@return	string					String with info text
 */
function info_admin($text, $infoonimgalt = 0, $nodiv=0, $admin='1')
{
	global $conf, $langs;

	if ($infoonimgalt)
	{
		return img_picto($text, 'info', 'class="hideonsmartphone"');
	}

	return ($nodiv?'':'<div class="'.(empty($admin)?'':($admin=='1'?'info':$admin)).' hideonsmartphone">').img_picto($admin?$langs->trans('InfoAdmin'):$langs->trans('Note'), ($nodiv?'info':'info_black'), 'class="hideonsmartphone"').' '.$text.($nodiv?'':'</div>');
}


/**
 *	Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontee des bugs.
 *	On doit appeler cette fonction quand une erreur technique bloquante est rencontree.
 *	Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
 *	renvoyer leur erreur par l'intermediaire de leur propriete "error".
 *
 *	@param	 	DoliDB	$db      	Database handler
 *	@param  	mixed	$error		String or array of errors strings to show
 *  @param		array	$errors		Array of errors
 *	@return 	void
 *  @see    	dol_htmloutput_errors
 */
function dol_print_error($db='',$error='',$errors=null)
{
	global $conf,$langs,$argv;
	global $dolibarr_main_prod;

	$out = '';
	$syslog = '';

	// Si erreur intervenue avant chargement langue
	if (! $langs)
	{
		require_once DOL_DOCUMENT_ROOT .'/core/class/translate.class.php';
		$langs = new Translate('', $conf);
		$langs->load("main");
	}
	$langs->load("main");
	$langs->load("errors");

	if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
	{
		$out.=$langs->trans("DolibarrHasDetectedError").".<br>\n";
		if (! empty($conf->global->MAIN_FEATURES_LEVEL)) $out.="You use an experimental or develop level of features, so please do NOT report any bugs, except if problem is confirmed moving option MAIN_FEATURES_LEVEL back to 0.<br>\n";
		$out.=$langs->trans("InformationToHelpDiagnose").":<br>\n";

		$out.="<b>".$langs->trans("Date").":</b> ".dol_print_date(time(),'dayhourlog')."<br>\n";
		$out.="<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";
		if (isset($conf->global->MAIN_FEATURES_LEVEL)) $out.="<b>".$langs->trans("LevelOfFeature").":</b> ".$conf->global->MAIN_FEATURES_LEVEL."<br>\n";
		if (function_exists("phpversion"))
		{
			$out.="<b>".$langs->trans("PHP").":</b> ".phpversion()."<br>\n";
		}
		$out.="<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
		if (function_exists("php_uname"))
		{
			$out.="<b>".$langs->trans("OS").":</b> ".php_uname()."<br>\n";
		}
		$out.="<b>".$langs->trans("UserAgent").":</b> ".$_SERVER["HTTP_USER_AGENT"]."<br>\n";
		$out.="<br>\n";
		$out.="<b>".$langs->trans("RequestedUrl").":</b> ".dol_htmlentities($_SERVER["REQUEST_URI"],ENT_COMPAT,'UTF-8')."<br>\n";
		$out.="<b>".$langs->trans("Referer").":</b> ".(isset($_SERVER["HTTP_REFERER"])?dol_htmlentities($_SERVER["HTTP_REFERER"],ENT_COMPAT,'UTF-8'):'')."<br>\n";
		$out.="<b>".$langs->trans("MenuManager").":</b> ".(isset($conf->standard_menu)?$conf->standard_menu:'')."<br>\n";
		$out.="<br>\n";
		$syslog.="url=".$_SERVER["REQUEST_URI"];
		$syslog.=", query_string=".$_SERVER["QUERY_STRING"];
	}
	else                              // Mode CLI
	{
		$out.='> '.$langs->transnoentities("ErrorInternalErrorDetected").":\n".$argv[0]."\n";
		$syslog.="pid=".dol_getmypid();
	}

	if (is_object($db))
	{
		if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
		{
			$out.="<b>".$langs->trans("DatabaseTypeManager").":</b> ".$db->type."<br>\n";
			$out.="<b>".$langs->trans("RequestLastAccessInError").":</b> ".($db->lastqueryerror()?$db->lastqueryerror():$langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out.="<b>".$langs->trans("ReturnCodeLastAccessInError").":</b> ".($db->lasterrno()?$db->lasterrno():$langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out.="<b>".$langs->trans("InformationLastAccessInError").":</b> ".($db->lasterror()?$db->lasterror():$langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out.="<br>\n";
		}
		else                            // Mode CLI
		{
			$out.='> '.$langs->transnoentities("DatabaseTypeManager").":\n".$db->type."\n";
			$out.='> '.$langs->transnoentities("RequestLastAccessInError").":\n".($db->lastqueryerror()?$db->lastqueryerror():$langs->trans("ErrorNoRequestInError"))."\n";
			$out.='> '.$langs->transnoentities("ReturnCodeLastAccessInError").":\n".($db->lasterrno()?$db->lasterrno():$langs->trans("ErrorNoRequestInError"))."\n";
			$out.='> '.$langs->transnoentities("InformationLastAccessInError").":\n".($db->lasterror()?$db->lasterror():$langs->trans("ErrorNoRequestInError"))."\n";

		}
		$syslog.=", sql=".$db->lastquery();
		$syslog.=", db_error=".$db->lasterror();
	}

	if ($error || $errors)
	{
		$langs->load("errors");

		// Merge all into $errors array
		if (is_array($error) && is_array($errors)) $errors=array_merge($error,$errors);
		elseif (is_array($error)) $errors=$error;
		elseif (is_array($errors)) $errors=array_merge(array($error),$errors);
		else $errors=array_merge(array($error));

		foreach($errors as $msg)
		{
			if (empty($msg)) continue;
			if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
			{
				$out.="<b>".$langs->trans("Message").":</b> ".dol_escape_htmltag($msg)."<br>\n" ;
			}
			else                        // Mode CLI
			{
				$out.='> '.$langs->transnoentities("Message").":\n".$msg."\n" ;
			}
			$syslog.=", msg=".$msg;
		}
	}
	if (empty($dolibarr_main_prod) && $_SERVER['DOCUMENT_ROOT'] && function_exists('xdebug_print_function_stack') && function_exists('xdebug_call_file'))
	{
		xdebug_print_function_stack();
		$out.='<b>XDebug informations:</b>'."<br>\n";
		$out.='File: '.xdebug_call_file()."<br>\n";
		$out.='Line: '.xdebug_call_line()."<br>\n";
		$out.='Function: '.xdebug_call_function()."<br>\n";
		$out.="<br>\n";
	}

	if (empty($dolibarr_main_prod)) print $out;
	else define("MAIN_CORE_ERROR", 1);
	//else print 'Sorry, an error occured but the parameter $dolibarr_main_prod is defined in conf file so no message is reported to your browser. Please read the log file for error message.';
	dol_syslog("Error ".$syslog, LOG_ERR);
}

/**
 * Show a public email and error code to contact if technical error
 *
 * @param	string	$prefixcode		Prefix of public error code
 * @return	void
 */
function dol_print_error_email($prefixcode)
{
	global $langs,$conf;

	$langs->load("errors");
	$now=dol_now();
	print '<br><div class="error">'.$langs->trans("ErrorContactEMail", $conf->global->MAIN_INFO_SOCIETE_MAIL, $prefixcode.dol_print_date($now,'%Y%m%d')).'</div>';
}

/**
 *	Show title line of an array
 *
 *	@param	string	$name        Label of field
 *	@param	string	$file        Url used when we click on sort picto
 *	@param	string	$field       Field to use for new sorting
 *	@param	string	$begin       ("" by defaut)
 *	@param	string	$moreparam   Add more parameters on sort url links ("" by default)
 *	@param  string	$td          Options of attribute td ("" by defaut, example: 'align="center"')
 *	@param  string	$sortfield   Current field used to sort
 *	@param  string	$sortorder   Current sort order
 *  @param	string	$prefix		 Prefix for css. Use space after prefix to add your own CSS tag.
 *	@return	void
 */
function print_liste_field_titre($name, $file="", $field="", $begin="", $moreparam="", $td="", $sortfield="", $sortorder="", $prefix="")
{
	print getTitleFieldOfList($name, 0, $file, $field, $begin, $moreparam, $td, $sortfield, $sortorder, $prefix);
}

/**
 *	Get title line of an array
 *
 *	@param	string	$name        Label of field
 *	@param	int		$thead		 0=To use with standard table format, 1=To use inside <thead><tr>, 2=To use with <div>
 *	@param	string	$file        Url used when we click on sort picto
 *	@param	string	$field       Field to use for new sorting. Empty if this field is not sortable.
 *	@param	string	$begin       ("" by defaut)
 *	@param	string	$moreparam   Add more parameters on sort url links ("" by default)
 *	@param  string	$moreattrib  Add more attributes on th ("" by defaut)
 *	@param  string	$sortfield   Current field used to sort
 *	@param  string	$sortorder   Current sort order
 *  @param	string	$prefix		 Prefix for css. Use space after prefix to add your own CSS tag.
 *	@return	string
 */
function getTitleFieldOfList($name, $thead=0, $file="", $field="", $begin="", $moreparam="", $moreattrib="", $sortfield="", $sortorder="", $prefix="")
{
	global $conf;
	//print "$name, $file, $field, $begin, $options, $moreattrib, $sortfield, $sortorder<br>\n";

	$sortorder=strtoupper($sortorder);
	$out='';
    $sortimg='';

	$tag='th';
	if ($thead==2) $tag='div';

	// If field is used as sort criteria we use a specific class
	// Example if (sortfield,field)=("nom","xxx.nom") or (sortfield,field)=("nom","nom")
	if ($field && ($sortfield == $field || $sortfield == preg_replace("/^[^\.]+\./","",$field))) $out.= '<'.$tag.' class="'.$prefix.'liste_titre_sel" '. $moreattrib.'>';
	else $out.= '<'.$tag.' class="'.$prefix.'liste_titre" '. $moreattrib.'>';

	if (empty($thead) && $field)    // If this is a sort field
	{
		$options=preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i','',$moreparam);
		$options=preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i','',$options);
		$options=preg_replace('/&+/i','&',$options);
		if (! preg_match('/^&/',$options)) $options='&'.$options;

		if ($field != $sortfield)
		{
            if ($sortorder == 'DESC') $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">';
            if ($sortorder == 'ASC' || ! $sortorder) $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">';
		}
		else
		{
            if ($sortorder == 'DESC' || ! $sortorder) $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">';
            if ($sortorder == 'ASC') $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">';
		}
	}

	$out.=$name;

	if (empty($thead) && $field)    // If this is a sort field
	{
		$out.='</a>';
	}

	if (empty($thead) && $field)    // If this is a sort field
	{
		$options=preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i','',$moreparam);
		$options=preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i','',$options);
		$options=preg_replace('/&+/i','&',$options);
		if (! preg_match('/^&/',$options)) $options='&'.$options;

		//print "&nbsp;";
		//$sortimg.= '<img width="2" src="'.DOL_URL_ROOT.'/theme/common/transparent.png" alt="">';
		//$sortimg.= '<span class="nowrap">';

		if (! $sortorder || $field != $sortfield)
		{
			//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
			//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
		}
		else
		{
			if ($sortorder == 'DESC' ) {
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",1).'</a>';
				$sortimg.= '<span class="nowrap">'.img_up("Z-A",0).'</span>';
			}
			if ($sortorder == 'ASC' ) {
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",1).'</a>';
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
				$sortimg.= '<span class="nowrap">'.img_down("A-Z",0).'</span>';
			}
		}

		//$sortimg.= '</span>';
	}

	$out.=$sortimg;

	$out.='</'.$tag.'>';

	return $out;
}

/**
 *	Show a title.
 *
 *	@param	string	$title			Title to show
 *	@return	string					Title to show
 *  @deprecated						Use load_fiche_titre instead
 *  @see load_fiche_titre
 */
function print_titre($title)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	print '<div class="titre">'.$title.'</div>';
}

/**
 *	Show a title with picto
 *
 *	@param	string	$title				Title to show
 *	@param	string	$mesg				Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	int		$id					To force an id on html objects
 * 	@return	void
 *  @deprecated Use print load_fiche_titre instead
 */
function print_fiche_titre($title, $mesg='', $picto='title_generic.png', $pictoisfullpath=0, $id='')
{
	print load_fiche_titre($title, $mesg, $picto, $pictoisfullpath, $id);
}

/**
 *	Load a title with picto
 *
 *	@param	string	$titre				Title to show
 *	@param	string	$morehtmlright		Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	int		$id					To force an id on html objects
 *  @param  string  $morecssontable     More css on table
 * 	@return	string
 *  @see print_barre_liste
 */
function load_fiche_titre($titre, $morehtmlright='', $picto='title_generic.png', $pictoisfullpath=0, $id=0, $morecssontable='')
{
	global $conf;

	$return='';

	if ($picto == 'setup') $picto='title.png';
	if (($conf->browser->name == 'ie') && $picto=='title.png') $picto='title.gif';

	$return.= "\n";
	$return.= '<table '.($id?'id="'.$id.'" ':'').'summary="" class="centpercent notopnoleftnoright'.($morecssontable?' '.$morecssontable:'').'" style="margin-bottom: 2px;"><tr>';
	if ($picto) $return.= '<td class="nobordernopadding widthpictotitle" valign="middle">'.img_picto('',$picto, 'id="pictotitle"', $pictoisfullpath).'</td>';
	$return.= '<td class="nobordernopadding" valign="middle">';
	$return.= '<div class="titre">'.$titre.'</div>';
	$return.= '</td>';
	if (dol_strlen($morehtmlright))
	{
		$return.= '<td class="nobordernopadding titre_right" align="right" valign="middle">'.$morehtmlright.'</td>';
	}
	$return.= '</tr></table>'."\n";

	return $return;
}

/**
 *	Print a title with navigation controls for pagination
 *
 *	@param	string	    $titre				Title to show (required)
 *	@param	int   	    $page				Numero of page to show in navigation links (required)
 *	@param	string	    $file				Url of page (required)
 *	@param	string	    $options         	More parameters for links ('' by default, does not include sortfield neither sortorder)
 *	@param	string    	$sortfield       	Field to sort on ('' by default)
 *	@param	string	    $sortorder       	Order to sort ('' by default)
 *	@param	string	    $center          	String in the middle ('' by default). We often find here string $massaction comming from $form->selectMassAction() 
 *	@param	int		    $num				Number of records found by select with limit+1
 *	@param	int|string  $totalnboflines		Total number of records/lines for all pages (if known). Use a negative value of number to not show number. Use '' if unknown.
 *	@param	string	    $picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		    $pictoisfullpath	1=Icon name is a full absolute url of image
 *  @param	string	    $morehtml			More html to show
 *  @param  string      $morecss            More css to the table
 *  @param  int         $limit              Max number of lines (-1 = use default, 0 = no limit, > 0 = limit).
 *  @param  int         $hideselectlimit    Force to hide select limit
 *	@return	void
 */
function print_barre_liste($titre, $page, $file, $options='', $sortfield='', $sortorder='', $center='', $num=-1, $totalnboflines='', $picto='title_generic.png', $pictoisfullpath=0, $morehtml='', $morecss='', $limit=-1, $hideselectlimit=0)
{
	global $conf,$langs;
	
	$savlimit = $limit;
    $savtotalnboflines = $totalnboflines;
    $totalnboflines=abs($totalnboflines);
    
	if ($picto == 'setup') $picto='title_setup.png';
	if (($conf->browser->name == 'ie') && $picto=='title_generic.png') $picto='title.gif';
	if ($limit < 0) $limit = $conf->liste_limit;
	if ($savlimit != 0 && (($num > $limit) || ($num == -1) || ($limit == 0)))
	{
		$nextpage = 1;
	}
	else
	{
		$nextpage = 0;
	}
	//print 'totalnboflines='.$totalnboflines.'-savlimit='.$savlimit.'-limit='.$limit.'-num='.$num.'-nextpage='.$nextpage;
	
	print "\n";
	print "<!-- Begin title '".$titre."' -->\n";
	print '<table width="100%" border="0" class="notopnoleftnoright'.($morecss?' '.$morecss:'').'" style="margin-bottom: 6px;"><tr>';

	// Left
	//if ($picto && $titre) print '<td class="nobordernopadding hideonsmartphone" width="40" align="left" valign="middle">'.img_picto('', $picto, 'id="pictotitle"', $pictoisfullpath).'</td>';
	print '<td class="nobordernopadding valignmiddle">';
	if ($picto && $titre) print img_picto('', $picto, 'class="hideonsmartphone valignmiddle" id="pictotitle"', $pictoisfullpath);
	print '<div class="titre inline-block">'.$titre;
	if (!empty($titre) && $savtotalnboflines >= 0 && (string) $savtotalnboflines != '') print ' ('.$totalnboflines.')';
	print '</div></td>';

	// Center
	if ($center)
	{
		print '<td class="nobordernopadding center valignmiddle">'.$center.'</td>';
	}

	// Right
	print '<td class="nobordernopadding valignmiddle" align="right">';
	if ($sortfield) $options .= "&amp;sortfield=".$sortfield;
	if ($sortorder) $options .= "&amp;sortorder=".$sortorder;
	// Show navigation bar
	$pagelist = '';
	if ($savlimit != 0 && ($page > 0 || $num > $limit))
	{
		if ($totalnboflines)	// If we know total nb of lines
		{
			$maxnbofpage=(empty($conf->dol_optimize_smallscreen) ? 4 : 1);		// page nb before and after selected page + ... + first or last

			if ($limit > 0) $nbpages=ceil($totalnboflines/$limit);
			else $nbpages=1;
			$cpt=($page-$maxnbofpage);
			if ($cpt < 0) { $cpt=0; }

			if ($cpt>=1)
			{
				$pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><a '.(($conf->dol_use_jmobile != 4)?'':'data-role="button" ').'href="'.$file.'?page=0'.$options.'">1</a></li>';
				if ($cpt > 2) $pagelist.='<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><span '.(($conf->dol_use_jmobile != 4)?'class="inactive"':'data-role="button"').'>...</span></li>';
				else if ($cpt == 2) $pagelist.='<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><a '.(($conf->dol_use_jmobile != 4)?'':'data-role="button" ').'href="'.$file.'?page=1'.$options.'">2</a></li>';
			}

			do
			{
				if ($cpt==$page)
				{
					$pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><span '.(($conf->dol_use_jmobile != 4)?'class="active"':'data-role="button"').'>'.($page+1).'</span></li>';
				}
				else
				{
					$pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><a '.(($conf->dol_use_jmobile != 4)?'':'data-role="button" ').'href="'.$file.'?page='.$cpt.$options.'">'.($cpt+1).'</a></li>';
				}
				$cpt++;
			}
			while ($cpt < $nbpages && $cpt<=$page+$maxnbofpage);

			if ($cpt<$nbpages)
			{
				if ($cpt<$nbpages-2) $pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><span '.(($conf->dol_use_jmobile != 4)?'class="inactive"':'data-role="button"').'>...</span></li>';
				else if ($cpt == $nbpages-2) $pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><a '.(($conf->dol_use_jmobile != 4)?'':'data-role="button" ').'href="'.$file.'?page='.($nbpages-2).$options.'">'.($nbpages - 1).'</a></li>';
				$pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><a '.(($conf->dol_use_jmobile != 4)?'':'data-role="button" ').'href="'.$file.'?page='.($nbpages-1).$options.'">'.$nbpages.'</a></li>';
			}
		}
		else
		{
			$pagelist.= '<li'.(($conf->dol_use_jmobile != 4)?' class="pagination"':'').'><span '.(($conf->dol_use_jmobile != 4)?'class="active"':'data-role="button"').'>'.($page+1)."</li>";
		}
	}
	print_fleche_navigation($page, $file, $options, $nextpage, $pagelist, $morehtml, $savlimit, $totalnboflines, $hideselectlimit);		// output the div and ul for previous/last completed with page numbers into $pagelist
	print '</td>';

	print '</tr></table>'."\n";
	print "<!-- End title -->\n\n";
}

/**
 *	Function to show navigation arrows into lists
 *
 *	@param	int				$page				Number of page
 *	@param	string			$file				Page URL (in most cases provided with $_SERVER["PHP_SELF"])
 *	@param	string			$options         	Other url paramaters to propagate ("" by default, may include sortfield and sortorder)
 *	@param	integer			$nextpage	    	Do we show a next page button
 *	@param	string			$betweenarrows		HTML content to show between arrows. MUST contains '<li> </li>' tags or '<li><span> </span></li>'.
 *  @param	string			$afterarrows		HTML content to show after arrows. Must NOT contains '<li> </li>' tags.
 *  @param  int             $limit              Max nb of record to show  (-1 = no combo with limit, 0 = no limit, > 0 = limit)
 *	@param	int		        $totalnboflines		Total number of records/lines for all pages (if known)
 *  @param  int             $hideselectlimit    Force to hide select limit
 *	@return	void
 */
function print_fleche_navigation($page, $file, $options='', $nextpage=0, $betweenarrows='', $afterarrows='', $limit=-1, $totalnboflines=0, $hideselectlimit=0)
{
	global $conf, $langs;

	print '<div class="pagination"><ul>';
	if ((int) $limit >= 0 && empty($hideselectlimit))
	{
	    $pagesizechoices='10:10,20:20,30:30,40:40,50:50,100:100,250:250,500:500,1000:1000,5000:5000';
	    //$pagesizechoices.=',0:'.$langs->trans("All");     // Not yet supported
	    //$pagesizechoices.=',2:2';
	    if (! empty($conf->global->MAIN_PAGESIZE_CHOICES)) $pagesizechoices=$conf->global->MAIN_PAGESIZE_CHOICES;
	     
        print '<li class="pagination">';
        print '<select class="flat selectlimit" name="limit" title="'.dol_escape_htmltag($langs->trans("MaxNbOfRecordPerPage")).'">';
        $tmpchoice=explode(',',$pagesizechoices);
        $tmpkey=$limit.':'.$limit;
        if (! in_array($tmpkey, $tmpchoice)) $tmpchoice[]=$tmpkey;
        $tmpkey=$conf->liste_limit.':'.$conf->liste_limit;
        if (! in_array($tmpkey, $tmpchoice)) $tmpchoice[]=$tmpkey;
        asort($tmpchoice, SORT_NUMERIC);
        $found=false;
        foreach($tmpchoice as $val)
        {
            $selected='';
            $tmp=explode(':',$val);
            $key=$tmp[0];
            $val=$tmp[1];
            if ($key != '' && $val != '')
            {
                if ((int) $key == (int) $limit)
                {
                    $selected = ' selected="selected"';
                    $found = true;
                }
                print '<option name="'.$key.'"'.$selected.'>'.dol_escape_htmltag($val).'</option>'."\n";
            }
        }
        print '</select>';
        if ($conf->use_javascript_ajax)
        {
            print '<!-- JS CODE TO ENABLE select limit to launch submit of page -->
            		<script type="text/javascript">
                	jQuery(document).ready(function () {
            	  		jQuery(".selectlimit").change(function() {
                            console.log("Change limit. Send submit");
                            $(this).parents(\'form:first\').submit();
            	  		});
                	});
            		</script>
                ';
        }
        print '</li>';	    
	}
	if ($page > 0)
	{
		if (($conf->dol_use_jmobile != 4)) print '<li class="pagination"><a class="paginationprevious" href="'.$file.'?page='.($page-1).$options.'"><</a></li>';
		else print '<li><a data-role="button" data-icon="arrow-l" data-iconpos="left" href="'.$file.'?page='.($page-1).$options.'">'.$langs->trans("Previous").'</a></li>';
	}
	if ($betweenarrows)
	{
		print $betweenarrows;
	}
	if ($nextpage > 0)
	{
		if (($conf->dol_use_jmobile != 4)) print '<li class="pagination"><a class="paginationnext" href="'.$file.'?page='.($page+1).$options.'">></a></li>';
		else print '<li><a data-role="button" data-icon="arrow-r" data-iconpos="right" href="'.$file.'?page='.($page+1).$options.'">'.$langs->trans("Next").'</a></li>';
	}
	if ($afterarrows)
	{
		print '<li class="paginationafterarrows">';
		print $afterarrows;
		print '</li>';
	}
	print '</ul></div>'."\n";
}


/**
 *	Return a string with VAT rate label formated for view output
 *	Used into pdf and HTML pages
 *
 *	@param	string	$rate			Rate value to format ('19.6', '19,6', '19.6%', '19,6%', '19.6 (CODEX)', ...)
 *  @param	boolean	$addpercent		Add a percent % sign in output
 *	@param	int		$info_bits		Miscellaneous information on vat (0=Default, 1=French NPR vat)
 *	@param	int		$usestarfornpr	1=Use '*' for NPR vat rate intead of MAIN_LABEL_MENTION_NPR
 *  @return	string					String with formated amounts ('19,6' or '19,6%' or '8.5% (NPR)' or '8.5% *' or '19,6 (CODEX)')
 */
function vatrate($rate, $addpercent=false, $info_bits=0, $usestarfornpr=0)
{
    $morelabel='';
    
    if (preg_match('/%/',$rate))
	{
		$rate=str_replace('%','',$rate);
		$addpercent=true;
	}
	if (preg_match('/\((.*)\)/',$rate,$reg))
	{
	    $morelabel=' ('.$reg[1].')';
	    $rate=preg_replace('/\s*'.preg_quote($morelabel,'/').'/','',$rate);
	}
	if (preg_match('/\*/',$rate) || preg_match('/'.constant('MAIN_LABEL_MENTION_NPR').'/i',$rate))
	{
		$rate=str_replace('*','',$rate);
		$info_bits |= 1;
	}

	$ret=price($rate,0,'',0,0).($addpercent?'%':'');
	if ($info_bits & 1) $ret.=' '.($usestarfornpr?'*':constant('MAIN_LABEL_MENTION_NPR'));
	$ret.=$morelabel;
	return $ret;
}


/**
 *		Function to format a value into an amount for visual output
 *		Function used into PDF and HTML pages
 *
 *		@param	float		$amount			Amount to format
 *		@param	integer		$form			Type of format, HTML or not (not by default)
 *		@param	Translate	$outlangs		Object langs for output
 *		@param	int			$trunc			1=Truncate if there is too much decimals (default), 0=Does not truncate. Deprecated because amount are rounded (to unit or total amount accurancy) before inserted into database or after a computation, so this parameter should be useless.
 *		@param	int			$rounding		Minimum number of decimal to show. If 0, no change, if -1, we use min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOTAL)
 *		@param	int			$forcerounding	Force the number of decimal to forcerounding decimal (-1=do not force)
 *		@param	string		$currency_code	To add currency symbol (''=add nothing, 'auto'=Use default currency, 'XXX'=add currency symbols for XXX currency)
 *		@return	string						Chaine avec montant formate
 *
 *		@see	price2num					Revert function of price
 */
function price($amount, $form=0, $outlangs='', $trunc=1, $rounding=-1, $forcerounding=-1, $currency_code='')
{
	global $langs,$conf;

	// Clean parameters
	if (empty($amount)) $amount=0;	// To have a numeric value if amount not defined or = ''
	$amount = (is_numeric($amount)?$amount:0); // Check if amount is numeric, for example, an error occured when amount value = o (letter) instead 0 (number)
	if ($rounding < 0) $rounding=min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);
	$nbdecimal=$rounding;

	// Output separators by default (french)
	$dec=','; $thousand=' ';

	// If $outlangs not forced, we use use language
	if (! is_object($outlangs)) $outlangs=$langs;

	if ($outlangs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec=$outlangs->transnoentitiesnoconv("SeparatorDecimal");
	if ($outlangs->transnoentitiesnoconv("SeparatorThousand")!= "SeparatorThousand") $thousand=$outlangs->transnoentitiesnoconv("SeparatorThousand");
	if ($thousand == 'None') $thousand='';
	else if ($thousand == 'Space') $thousand=' ';
	//print "outlangs=".$outlangs->defaultlang." amount=".$amount." html=".$form." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

	//print "amount=".$amount."-";
	$amount = str_replace(',','.',$amount);	// should be useless
	//print $amount."-";
	$datas = explode('.',$amount);
	$decpart = isset($datas[1])?$datas[1]:'';
	$decpart = preg_replace('/0+$/i','',$decpart);	// Supprime les 0 de fin de partie decimale
	//print "decpart=".$decpart."<br>";
	$end='';

	// We increase nbdecimal if there is more decimal than asked (to not loose information)
	if (dol_strlen($decpart) > $nbdecimal) $nbdecimal=dol_strlen($decpart);
	// Si on depasse max
	if ($trunc && $nbdecimal > $conf->global->MAIN_MAX_DECIMALS_SHOWN)
	{
		$nbdecimal=$conf->global->MAIN_MAX_DECIMALS_SHOWN;
		if (preg_match('/\.\.\./i',$conf->global->MAIN_MAX_DECIMALS_SHOWN))
		{
			// Si un affichage est tronque, on montre des ...
			$end='...';
		}
	}

	// If force rounding
	if ($forcerounding >= 0) $nbdecimal = $forcerounding;

	// Format number
	$output=number_format($amount, $nbdecimal, $dec, $thousand);
	if ($form)
	{
		$output=preg_replace('/\s/','&nbsp;',$output);
		$output=preg_replace('/\'/','&#039;',$output);
	}
	// Add symbol of currency if requested
	$cursymbolbefore=$cursymbolafter='';
	if ($currency_code)
	{
		if ($currency_code == 'auto') $currency_code=$conf->currency;

		$listofcurrenciesbefore=array('USD','GBP','AUD','MXN');
		if (in_array($currency_code,$listofcurrenciesbefore)) $cursymbolbefore.=$outlangs->getCurrencySymbol($currency_code);
		else
		{
			$tmpcur=$outlangs->getCurrencySymbol($currency_code);
			$cursymbolafter.=($tmpcur == $currency_code ? ' '.$tmpcur : $tmpcur);
		}
	}
	$output=$cursymbolbefore.$output.$end.($cursymbolafter?' ':'').$cursymbolafter;

	return $output;
}

/**
 *	Function that return a number with universal decimal format (decimal separator is '.') from an amount typed by a user.
 *	Function to use on each input amount before any numeric test or database insert
 *
 *	@param	float	$amount			Amount to convert/clean
 *	@param	string	$rounding		''=No rounding
 * 									'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
 *									'MT'=Round to Max for totals with Tax (MAIN_MAX_DECIMALS_TOT)
 *									'MS'=Round to Max for stock quantity (MAIN_MAX_DECIMALS_STOCK)
 * 	@param	int		$alreadysqlnb	Put 1 if you know that content is already universal format number
 *	@return	string					Amount with universal numeric format (Example: '99.99999') or unchanged text if conversion fails.
 *
 *	@see    price					Opposite function of price2num
 */
function price2num($amount,$rounding='',$alreadysqlnb=0)
{
	global $langs,$conf;

	// Round PHP function does not allow number like '1,234.56' nor '1.234,56' nor '1 234,56'
	// Numbers must be '1234.56'
	// Decimal delimiter for PHP and database SQL requests must be '.'
	$dec=','; $thousand=' ';
	if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec=$langs->transnoentitiesnoconv("SeparatorDecimal");
	if ($langs->transnoentitiesnoconv("SeparatorThousand")!= "SeparatorThousand") $thousand=$langs->transnoentitiesnoconv("SeparatorThousand");
	if ($thousand == 'None') $thousand='';
	elseif ($thousand == 'Space') $thousand=' ';
	//print "amount=".$amount." html=".$form." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

	// Convert value to universal number format (no thousand separator, '.' as decimal separator)
	if ($alreadysqlnb != 1)	// If not a PHP number or unknown, we change format
	{
		//print 'PP'.$amount.' - '.$dec.' - '.$thousand.' - '.intval($amount).'<br>';

		// Convert amount to format with dolibarr dec and thousand (this is because PHP convert a number
		// to format defined by LC_NUMERIC after a calculation and we want source format to be like defined by Dolibarr setup.
		if (is_numeric($amount))
		{
			// We put in temps value of decimal ("0.00001"). Works with 0 and 2.0E-5 and 9999.10
			$temps=sprintf("%0.10F",$amount-intval($amount));	// temps=0.0000000000 or 0.0000200000 or 9999.1000000000
			$temps=preg_replace('/([\.1-9])0+$/','\\1',$temps); // temps=0. or 0.00002 or 9999.1
			$nbofdec=max(0,dol_strlen($temps)-2);	// -2 to remove "0."
			$amount=number_format($amount,$nbofdec,$dec,$thousand);
		}
		//print "QQ".$amount.'<br>';

		// Now make replace (the main goal of function)
		if ($thousand != ',' && $thousand != '.') $amount=str_replace(',','.',$amount);	// To accept 2 notations for french users
		$amount=str_replace(' ','',$amount);		// To avoid spaces
		$amount=str_replace($thousand,'',$amount);	// Replace of thousand before replace of dec to avoid pb if thousand is .
		$amount=str_replace($dec,'.',$amount);
	}

	// Now, make a rounding if required
	if ($rounding)
	{
		$nbofdectoround='';
		if ($rounding == 'MU')     $nbofdectoround=$conf->global->MAIN_MAX_DECIMALS_UNIT;
		elseif ($rounding == 'MT') $nbofdectoround=$conf->global->MAIN_MAX_DECIMALS_TOT;
		elseif ($rounding == 'MS') $nbofdectoround=empty($conf->global->MAIN_MAX_DECIMALS_STOCK)?5:$conf->global->MAIN_MAX_DECIMALS_STOCK;
		elseif (is_numeric($rounding))  $nbofdectoround=$rounding; 	// For admin info page
		//print "RR".$amount.' - '.$nbofdectoround.'<br>';
		if (dol_strlen($nbofdectoround)) $amount = round($amount,$nbofdectoround);	// $nbofdectoround can be 0.
		else return 'ErrorBadParameterProvidedToFunction';
		//print 'SS'.$amount.' - '.$nbofdec.' - '.$dec.' - '.$thousand.' - '.$nbofdectoround.'<br>';

		// Convert amount to format with dolibarr dec and thousand (this is because PHP convert a number
		// to format defined by LC_NUMERIC after a calculation and we want source format to be defined by Dolibarr setup.
		if (is_numeric($amount))
		{
			// We put in temps value of decimal ("0.00001"). Works with 0 and 2.0E-5 and 9999.10
			$temps=sprintf("%0.10F",$amount-intval($amount));	// temps=0.0000000000 or 0.0000200000 or 9999.1000000000
			$temps=preg_replace('/([\.1-9])0+$/','\\1',$temps); // temps=0. or 0.00002 or 9999.1
			$nbofdec=max(0,dol_strlen($temps)-2);	// -2 to remove "0."
			$amount=number_format($amount,min($nbofdec,$nbofdectoround),$dec,$thousand);		// Convert amount to format with dolibarr dec and thousand
		}
		//print "TT".$amount.'<br>';

		// Always make replace because each math function (like round) replace
		// with local values and we want a number that has a SQL string format x.y
		if ($thousand != ',' && $thousand != '.') $amount=str_replace(',','.',$amount);	// To accept 2 notations for french users
		$amount=str_replace(' ','',$amount);		// To avoid spaces
		$amount=str_replace($thousand,'',$amount);	// Replace of thousand before replace of dec to avoid pb if thousand is .
		$amount=str_replace($dec,'.',$amount);
	}

	return $amount;
}


/**
 * Output a dimension with best unit
 *  
 * @param   float       $dimension      Dimension
 * @param   int         $unit           Unit of dimension (0, -3, ...)
 * @param   string      $type           'weight', 'volume', ...
 * @param   Translate   $outputlangs    Translate language object
 * @param   int         $round          -1 = non rounding, x = number of decimal
 * @param   string      $forceunitoutput    'no' or numeric (-3, -6, ...) compared to $unit
 * @return  string                      String to show dimensions
 */
function showDimensionInBestUnit($dimension, $unit, $type, $outputlangs, $round=-1, $forceunitoutput='no')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
    
    if (($forceunitoutput == 'no' && $dimension < 1/10000) || (is_numeric($forceunitoutput) && $forceunitoutput == -6)) 
    {
        $dimension = $dimension * 1000000;
        $unit = $unit - 6; 
    }
    elseif (($forceunitoutput == 'no' && $dimension < 1/10) || (is_numeric($forceunitoutput) && $forceunitoutput == -3))
    {
        $dimension = $dimension * 1000;
        $unit = $unit - 3; 
    }
    elseif (($forceunitoutput == 'no' && $dimension > 100000000) || (is_numeric($forceunitoutput) && $forceunitoutput == 6))
    {
        $dimension = $dimension / 1000000;
        $unit = $unit + 6;
    }
    elseif (($forceunitoutput == 'no' && $dimension > 100000) || (is_numeric($forceunitoutput) && $forceunitoutput == 3))
    {
        $dimension = $dimension / 1000;
        $unit = $unit + 3;
    }
    
    $ret=price($dimension, 0, $outputlangs, 0, 0, $round).' '.measuring_units_string($unit, $type);
    
    return $ret;
}


/**
 *	Return localtax rate for a particular vat, when selling a product with vat $vatrate, from a $thirdparty_buyer to a $thirdparty_seller
 *  Note: This function applies same rules than get_default_tva
 *
 * 	@param	float		$vatrate		        Vat rate. Can be '8.5' or '8.5 (VATCODEX)' for example
 * 	@param  int			$local		         	Local tax to search and return (1 or 2 return only tax rate 1 or tax rate 2)
 *  @param  Societe		$thirdparty_buyer    	Object of buying third party
 *  @param	Societe		$thirdparty_seller		Object of selling third party ($mysoc if not defined)
 *  @param	int			$vatnpr					If vat rate is NPR or not
 * 	@return	mixed			   					0 if not found, localtax rate if found
 *  @see get_default_tva
 */
function get_localtax($vatrate, $local, $thirdparty_buyer="", $thirdparty_seller="", $vatnpr=0)
{
	global $db, $conf, $mysoc;

	if (empty($thirdparty_seller) || ! is_object($thirdparty_seller)) $thirdparty_seller=$mysoc;

	dol_syslog("get_localtax tva=".$vatrate." local=".$local." thirdparty_buyer id=".(is_object($thirdparty_buyer)?$thirdparty_buyer->id:'')."/country_code=".(is_object($thirdparty_buyer)?$thirdparty_buyer->country_code:'')." thirdparty_seller id=".$thirdparty_seller->id."/country_code=".$thirdparty_seller->country_code." thirdparty_seller localtax1_assuj=".$thirdparty_seller->localtax1_assuj."  thirdparty_seller localtax2_assuj=".$thirdparty_seller->localtax2_assuj);

	$vatratecleaned = $vatrate;
	if (preg_match('/^(.*)\s*\((.*)\)$/', $vatrate, $reg))      // If vat is "xx (yy)"
	{
        $vatratecleaned = trim($reg[1]);
	    $vatratecode = $reg[2];
	}
	
	/*if ($thirdparty_buyer->country_code != $thirdparty_seller->country_code)
	{
		return 0;
	}*/
	
	// Some test to guess with no need to make database access
	if ($mysoc->country_code == 'ES') // For spain localtaxes 1 and 2, tax is qualified if buyer use local taxe
	{
		if ($local == 1)
		{
			if (! $mysoc->localtax1_assuj || (string) $vatratecleaned == "0") return 0;
			if ($thirdparty_seller->id == $mysoc->id)
			{
				if (! $thirdparty_buyer->localtax1_assuj) return 0;
			}
			else
			{
				if (! $thirdparty_seller->localtax1_assuj) return 0;
			}
		}

		if ($local == 2)
		{
			if (! $mysoc->localtax2_assuj || (string) $vatratecleaned == "0") return 0;
			if ($thirdparty_seller->id == $mysoc->id)
			{
				if (! $thirdparty_buyer->localtax2_assuj) return 0;
			}
			else
			{
				if (! $thirdparty_seller->localtax2_assuj) return 0;
			}
		}
	}
	else
	{
		if ($local == 1 && ! $thirdparty_seller->localtax1_assuj) return 0;
		if ($local == 2 && ! $thirdparty_seller->localtax2_assuj) return 0;
	}

	// For some country MAIN_GET_LOCALTAXES_VALUES_FROM_THIRDPARTY is forced to on.
	if (in_array($mysoc->country_code, array('ES')))
	{
	    $conf->global->MAIN_GET_LOCALTAXES_VALUES_FROM_THIRDPARTY = 1;
	}
	    
	// Search local taxes
	if (! empty($conf->global->MAIN_GET_LOCALTAXES_VALUES_FROM_THIRDPARTY))
	{
    	if ($local==1)
    	{
    		if ($thirdparty_seller != $mysoc)
    		{
    			if (!isOnlyOneLocalTax($local))  // TODO We should provide $vatrate to search on correct line and not always on line with highest vat rate
    			{
    				return $thirdparty_seller->localtax1_value;
    			}
    		}
    		else  // i am the seller
    		{
    			if (!isOnlyOneLocalTax($local))  // TODO If seller is me, why not always returning this, even if there is only one locatax vat.
    			{
    				return $conf->global->MAIN_INFO_VALUE_LOCALTAX1;
    			}
    		}
    	}
    	if ($local==2)
    	{
    		if ($thirdparty_seller != $mysoc)
    		{
    			if (!isOnlyOneLocalTax($local))  // TODO We should provide $vatrate to search on correct line and not always on line with highest vat rate
    			// TODO We should also return value defined on thirdparty only if defined
    			{
    				return $thirdparty_seller->localtax2_value;
    			}
    		}
    		else  // i am the seller
    		{
    			if (!isOnlyOneLocalTax($local))  // This is for spain only, we don't return value found into datbase even if there is only one locatax vat.
    			{
    				return $conf->global->MAIN_INFO_VALUE_LOCALTAX2;
    			}
    		}
    	}
	}

	// By default, search value of local tax on line of common tax
	$sql  = "SELECT t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
   	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
   	$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$thirdparty_seller->country_code."'";
   	$sql .= " AND t.taux = ".((float) $vatratecleaned)." AND t.active = 1";
   	if ($vatratecode) $sql.= " AND t.code ='".$vatratecode."'";		// If we have the code, we use it in priority
   	else $sql.= " AND t.recuperableonly ='".$vatnpr."'";
   	dol_syslog("get_localtax", LOG_DEBUG);
   	$resql=$db->query($sql);

   	if ($resql)
   	{
   		$obj = $db->fetch_object($resql);
   		if ($local==1) return $obj->localtax1;
   		elseif ($local==2) return $obj->localtax2;
	}
	
	return 0;
}


/**
 * Return true if LocalTax (1 or 2) is unique.
 * Example: If localtax1 is 5 on line with highest common vat rate, return true
 * Example: If localtax1 is 5:8:15 on line with highest common vat rate, return false
 *
 * @param   int 	$local	Local tax to test (1 or 2)
 * @return  boolean 		True if LocalTax have multiple values, False if not
 */
function isOnlyOneLocalTax($local)
{
	$tax=get_localtax_by_third($local);

	$valors=explode(":", $tax);

	if (count($valors)>1)
	{
		return false;
	}
	else
	{
		return true;
	}
}

/**
 * Get values of localtaxes (1 or 2) for company country for the common vat with the highest value
 *
 * @param	int		$local 	LocalTax to get
 * @return	number			Values of localtax
 */
function get_localtax_by_third($local)
{
	global $db, $mysoc;
	$sql ="SELECT t.localtax1, t.localtax2 ";
	$sql.=" FROM ".MAIN_DB_PREFIX."c_tva as t inner join ".MAIN_DB_PREFIX."c_country as c ON c.rowid=t.fk_pays";
	$sql.=" WHERE c.code = '".$mysoc->country_code."' AND t.active = 1 AND t.taux=(";
	$sql.="  SELECT max(tt.taux) FROM ".MAIN_DB_PREFIX."c_tva as tt inner join ".MAIN_DB_PREFIX."c_country as c ON c.rowid=tt.fk_pays";
	$sql.="  WHERE c.code = '".$mysoc->country_code."' AND tt.active = 1";
	$sql.="  )";

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($local==1) return $obj->localtax1;
		elseif ($local==2) return $obj->localtax2;
	}

	return 0;

}


/**
 *  Get vat rate and npr from id.
 *  You can call getLocalTaxesFromRate after to get other fields 
 *
 *  @param	int      $vatrowid			Line ID into vat rate table.
 *  @return	array    	  				array(localtax_type1(1-6 / 0 if not found), rate of localtax1, ...)
 */
function getTaxesFromId($vatrowid)
{
    global $db, $mysoc;

    dol_syslog("getTaxesFromId vatrowid=".$vatrowid);

    // Search local taxes
    $sql = "SELECT t.rowid, t.code, t.taux as rate, t.recuperableonly as npr";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t";
    $sql.= " WHERE t.rowid ='".$vatrowid."'";

    $resql=$db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);

        return array('rowid'=>$obj->rowid, 'code'=>$obj->code, 'rate'=>$obj->rate, 'npr'=>$obj->npr);
    }
    else dol_print_error($db);

    return array();
}

/**
 *  Get type and rate of localtaxes for a particular vat rate/country fo thirdparty
 *  TODO
 *  This function is ALSO called to retrieve type for building PDF. Such call of function must be removed.
 *  Instead this function must be called when adding a line to get the array of localtax and type, and then
 *  provide it to the function calcul_price_total.
 *
 *  @param	string  $vatrate			VAT Rate. Value can be value or the string with code into parenthesis or rowid if $firstparamisid is 1. Example: '8.5' or '8.5 (8.5NPR)' or 123.
 *  @param	int		$local              Number of localtax (1 or 2, or 0 to return 1 & 2)
 *  @param	Societe	$buyer         		Company object
 *  @param	Societe	$seller        		Company object
 *  @param  int     $firstparamisid     1 if first param is id into table (use this if you can)
 *  @return	array    	  				array(localtax_type1(1-6 / 0 if not found), rate of localtax1, ...)
 */
function getLocalTaxesFromRate($vatrate, $local, $buyer, $seller, $firstparamisid=0)
{
	global $db, $mysoc;

	dol_syslog("getLocalTaxesFromRate vatrate=".$vatrate." local=".$local);

	$vatratecleaned = $vatrate;
	if (preg_match('/^(.*)\s*\((.*)\)$/', $vatrate, $reg))      // If vat is "xx (yy)"
	{
	    $vatratecleaned = $reg[1];
	    $vatratecode = $reg[2];
	}
	
	// Search local taxes
	$sql  = "SELECT t.localtax1, t.localtax1_type, t.localtax2, t.localtax2_type, t.accountancy_code_sell, t.accountancy_code_buy";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
	if ($firstparamisid) $sql.= " WHERE t.rowid ='".$vatrate."'";
	else
	{
	    $sql.=", ".MAIN_DB_PREFIX."c_country as c";
    	if ($mysoc->country_code == 'ES') $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$buyer->country_code."'";    // local tax in spain use the buyer country ??
    	else $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$seller->country_code."'";
    	$sql.= " AND t.taux = ".((float) $vatratecleaned)." AND t.active = 1";
    	if ($vatratecode) $sql.= " AND t.code ='".$vatratecode."'";
	}

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($local == 1)
		{
			if (! isOnlyOneLocalTax(1))
			{
				return array($obj->localtax1_type, get_localtax($vatrate, $local, $buyer, $seller), $obj->accountancy_code_sell,$obj->accountancy_code_buy);
			}
			else
			{
				return array($obj->localtax1_type, $obj->localtax1,$obj->accountancy_code_sell,$obj->accountancy_code_buy);
			}
		}
		elseif ($local == 2)
		{
			if (! isOnlyOneLocalTax(2))
			{
				return array($obj->localtax2_type, get_localtax($vatrate, $local, $buyer, $seller),$obj->accountancy_code_sell,$obj->accountancy_code_buy);
			}
			else
			{
				return array($obj->localtax2_type, $obj->localtax2,$obj->accountancy_code_sell,$obj->accountancy_code_buy);
			}
		}
		else
		{
			if(! isOnlyOneLocalTax(1))
			{
				if(! isOnlyOneLocalTax(2))
				{
					return array($obj->localtax1_type, get_localtax($vatrate, 1, $buyer, $seller), $obj->localtax2_type, get_localtax($vatrate, 2, $buyer, $seller),$obj->accountancy_code_sell,$obj->accountancy_code_buy);
				}
				else
				{
					return array($obj->localtax1_type, get_localtax($vatrate, 1, $buyer, $seller), $obj->localtax2_type, $obj->localtax2,$obj->accountancy_code_sell,$obj->accountancy_code_buy);
				}
			}
			else
			{
				if(! isOnlyOneLocalTax(2))
				{
					return array($obj->localtax1_type, $obj->localtax1, $obj->localtax2_type,get_localtax($vatrate, 2, $buyer, $seller) ,$obj->accountancy_code_sell,$obj->accountancy_code_buy);
				}
				else
				{
					return array($obj->localtax1_type, $obj->localtax1, $obj->localtax2_type, $obj->localtax2,$obj->accountancy_code_sell,$obj->accountancy_code_buy);
				}
			}
		}
	}

	return 0;
}

/**
 *	Return vat rate of a product in a particular selling country or default country vat if product is unknown
 *  Function called by get_default_tva
 *  
 *  @param	int			$idprod          	Id of product or 0 if not a predefined product
 *  @param  Societe		$thirdparty_seller  Thirdparty with a ->country_code defined (FR, US, IT, ...)
 *	@param	int			$idprodfournprice	Id product_fournisseur_price (for "supplier" order/invoice)
 *  @return float|string   				    Vat rate to use with format 5.0 or '5.0 (XXX)'
 *  @see get_product_localtax_for_country
 */
function get_product_vat_for_country($idprod, $thirdparty_seller, $idprodfournprice=0)
{
	global $db,$conf,$mysoc;

	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

	$ret=0;
	$found=0;

	if ($idprod > 0)
	{
		// Load product
		$product=new Product($db);
		$result=$product->fetch($idprod);

		if ($mysoc->country_code == $thirdparty_seller->country_code) // If selling country is ours
		{
			if ($idprodfournprice > 0)     // We want vat for product for a "supplier" order or invoice
			{
				$product->get_buyprice($idprodfournprice,0,0,0);
				$ret=$product->vatrate_supplier;
			}
			else
			{
				$ret=$product->tva_tx;    // Default vat of product we defined
				if ($product->default_vat_code) $ret.=' ('.$product->default_vat_code.')';
			}
			$found=1;
		}
		else
		{
			// TODO Read default product vat according to countrycode and product. Vat for couple countrycode/product is a feature not implemeted yet. 
			// May be usefull/required if hidden option SERVICE_ARE_ECOMMERCE_200238EC is on
		}
	}

	if (! $found)
	{
		if (empty($conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS))
		{
			// If vat of product for the country not found or not defined, we return the first higher vat of country.
			$sql = "SELECT taux as vat_rate";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
			$sql.= " WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$thirdparty_seller->country_code."'";
			$sql.= " ORDER BY t.taux DESC, t.code ASC, t.recuperableonly ASC";
			$sql.= $db->plimit(1);

			$resql=$db->query($sql);
			if ($resql)
			{
				$obj=$db->fetch_object($resql);
				if ($obj)
				{
					$ret=$obj->vat_rate;
				}
				$db->free($sql);
			}
			else dol_print_error($db);
		}
		else $ret=$conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS;    // Forced value if autodetect fails
	}

	dol_syslog("get_product_vat_for_country: ret=".$ret);
	return $ret;
}

/**
 *	Return localtax vat rate of a product in a particular selling country or default country vat if product is unknown
 *
 *  @param	int		$idprod         		Id of product
 *  @param  int		$local          		1 for localtax1, 2 for localtax 2
 *  @param  Societe	$thirdparty_seller    	Thirdparty with a ->country_code defined (FR, US, IT, ...)
 *  @return int             				<0 if KO, Vat rate if OK
 *  @see get_product_vat_for_country
 */
function get_product_localtax_for_country($idprod, $local, $thirdparty_seller)
{
	global $db,$mysoc;

	if (! class_exists('Product')) {
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
	}

	$ret=0;
	$found=0;

	if ($idprod > 0)
	{
		// Load product
		$product=new Product($db);
		$result=$product->fetch($idprod);

		if ($mysoc->country_code == $thirdparty_seller->country_code) // If selling country is ours
		{
			/* Not defined yet, so we don't use this
			if ($local==1) $ret=$product->localtax1_tx;
			elseif ($local==2) $ret=$product->localtax2_tx;
			$found=1;
			*/
		}
		else
		{
			// TODO Read default product vat according to countrycode and product


		}
	}

	if (! $found)
	{
		// If vat of product for the country not found or not defined, we return higher vat of country.
		$sql = "SELECT taux as vat_rate, localtax1, localtax2";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql.= " WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$thirdparty_seller->country_code."'";
		$sql.= " ORDER BY t.taux DESC, t.recuperableonly ASC";
		$sql.= $db->plimit(1);

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj=$db->fetch_object($resql);
			if ($obj)
			{
				if ($local==1) $ret=$obj->localtax1;
				elseif ($local==2) $ret=$obj->localtax2;
			}
		}
		else dol_print_error($db);
	}

	dol_syslog("get_product_localtax_for_country: ret=".$ret);
	return $ret;
}

/**
 *	Function that return vat rate of a product line (according to seller, buyer and product vat rate)
 *   Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 *	 Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 *	 Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
 *	 Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par defaut=TVA du produit vendu. Fin de regle
 *	 Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise avec num TVA) intra alors TVA par defaut=0. Fin de regle
 *	 Sinon TVA proposee par defaut=0. Fin de regle.
 *
 *	@param	Societe		$thirdparty_seller    	Objet societe vendeuse
 *	@param  Societe		$thirdparty_buyer   	Objet societe acheteuse
 *	@param  int			$idprod					Id product
 *	@param	int			$idprodfournprice		Id product_fournisseur_price (for supplier order/invoice)
 *	@return float|string   				      	Vat rate to use with format 5.0 or '5.0 (XXX)', -1 if we can't guess it
 *  @see get_default_npr, get_default_localtax
 */
function get_default_tva(Societe $thirdparty_seller, Societe $thirdparty_buyer, $idprod=0, $idprodfournprice=0)
{
	global $conf;

	// Note: possible values for tva_assuj are 0/1 or franchise/reel
	$seller_use_vat=((is_numeric($thirdparty_seller->tva_assuj) && ! $thirdparty_seller->tva_assuj) || (! is_numeric($thirdparty_seller->tva_assuj) && $thirdparty_seller->tva_assuj=='franchise'))?0:1;

	$seller_country_code=$thirdparty_seller->country_code;
	$seller_in_cee=$thirdparty_seller->isInEEC();

	$buyer_country_code=$thirdparty_buyer->country_code;
	$buyer_in_cee=$thirdparty_buyer->isInEEC();

	dol_syslog("get_default_tva: seller use vat=".$seller_use_vat.", seller country=".$seller_country_code.", seller in cee=".$seller_in_cee.", buyer country=".$buyer_country_code.", buyer in cee=".$buyer_in_cee.", idprod=".$idprod.", idprodfournprice=".$idprodfournprice.", SERVICE_ARE_ECOMMERCE_200238EC=".(! empty($conf->global->SERVICES_ARE_ECOMMERCE_200238EC)?$conf->global->SERVICES_ARE_ECOMMERCE_200238EC:''));

	// If services are eServices according to EU Council Directive 2002/38/EC (http://ec.europa.eu/taxation_customs/taxation/vat/traders/e-commerce/article_1610_en.htm)
	// we use the buyer VAT.
	if (! empty($conf->global->SERVICE_ARE_ECOMMERCE_200238EC))
	{
		if ($seller_in_cee && $buyer_in_cee && ! $thirdparty_buyer->isACompany())
		{
			//print 'VATRULE 0';
			return get_product_vat_for_country($idprod,$thirdparty_buyer,$idprodfournprice);
		}
	}

	// If seller does not use VAT
	if (! $seller_use_vat)
	{
		//print 'VATRULE 1';
		return 0;
	}

	// Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concerne si le test suivant n'est pas suffisant.

	// Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
	if (($seller_country_code == $buyer_country_code)
	|| (in_array($seller_country_code,array('FR,MC')) && in_array($buyer_country_code,array('FR','MC')))) // Warning ->country_code not always defined
	{
		//print 'VATRULE 2';
		return get_product_vat_for_country($idprod,$thirdparty_seller,$idprodfournprice);
	}

	// Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
	// Not supported

	// Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise) alors TVA par defaut=0. Fin de regle
	// Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier) alors TVA par defaut=TVA du produit vendu. Fin de regle
	if (($seller_in_cee && $buyer_in_cee))
	{
		$isacompany=$thirdparty_buyer->isACompany();
		if ($isacompany)
		{
			//print 'VATRULE 3';
			return 0;
		}
		else
		{
			//print 'VATRULE 4';
			return get_product_vat_for_country($idprod,$thirdparty_seller,$idprodfournprice);
		}
	}

	// Sinon la TVA proposee par defaut=0. Fin de regle.
	// Rem: Cela signifie qu'au moins un des 2 est hors Communaute europeenne et que le pays differe
	//print 'VATRULE 5';
	return 0;
}


/**
 *	Fonction qui renvoie si tva doit etre tva percue recuperable
 *
 *	@param	Societe		$thirdparty_seller    	Thirdparty seller
 *	@param  Societe		$thirdparty_buyer   	Thirdparty buyer
 *  @param  int			$idprod                 Id product
 *  @param	int			$idprodfournprice		Id supplier price for product
 *	@return float       			        	0 or 1
 *  @see get_default_tva, get_default_localtax
 */
function get_default_npr(Societe $thirdparty_seller, Societe $thirdparty_buyer, $idprod=0, $idprodfournprice=0)
{
	global $db;

	if ($idprodfournprice > 0)
	{
		if (! class_exists('ProductFournisseur'))
			require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
		$prodprice = new ProductFournisseur($db);
		$prodprice->fetch_product_fournisseur_price($idprodfournprice);
		return $prodprice->fourn_tva_npr;
	}
	elseif ($idprod > 0)
	{
		if (! class_exists('Product'))
			require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		$prod = new Product($db);
		$prod->fetch($idprod);
		return $prod->tva_npr;
	}

	return 0;
}

/**
 *	Function that return localtax of a product line (according to seller, buyer and product vat rate)
 *   Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 *	 Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 *	 Sinon TVA proposee par defaut=0. Fin de regle.
 *
 *	@param	Societe		$thirdparty_seller    	Thirdparty seller
 *	@param  Societe		$thirdparty_buyer   	Thirdparty buyer
 *  @param	int			$local					Localtax to process (1 or 2)
 *	@param  int			$idprod					Id product
 *	@return integer        				       	localtax, -1 si ne peut etre determine
 *  @see get_default_tva, get_default_npr
 */
function get_default_localtax($thirdparty_seller, $thirdparty_buyer, $local, $idprod=0)
{
	global $mysoc;

	if (!is_object($thirdparty_seller)) return -1;
	if (!is_object($thirdparty_buyer)) return -1;

	if ($local==1) // Localtax 1
	{
		if ($mysoc->country_code == 'ES')
		{
			if (is_numeric($thirdparty_buyer->localtax1_assuj) && ! $thirdparty_buyer->localtax1_assuj) return 0;
		}
		else
		{
			// Si vendeur non assujeti a Localtax1, localtax1 par default=0
			if (is_numeric($thirdparty_seller->localtax1_assuj) && ! $thirdparty_seller->localtax1_assuj) return 0;
			if (! is_numeric($thirdparty_seller->localtax1_assuj) && $thirdparty_seller->localtax1_assuj=='localtax1off') return 0;
		}
	}
	elseif ($local==2) //I Localtax 2
	{
		// Si vendeur non assujeti a Localtax2, localtax2 par default=0
		if (is_numeric($thirdparty_seller->localtax2_assuj) && ! $thirdparty_seller->localtax2_assuj) return 0;
		if (! is_numeric($thirdparty_seller->localtax2_assuj) && $thirdparty_seller->localtax2_assuj=='localtax2off') return 0;
	}

	if ($thirdparty_seller->country_code == $thirdparty_buyer->country_code)
	{
		return get_product_localtax_for_country($idprod, $local, $thirdparty_seller);
	}

	return 0;
}

/**
 *	Return yes or no in current language
 *
 *	@param	string	$yesno			Value to test (1, 'yes', 'true' or 0, 'no', 'false')
 *	@param	integer	$case			1=Yes/No, 0=yes/no, 2=Disabled checkbox, 3=Disabled checkbox + Yes/No
 *	@param	int		$color			0=texte only, 1=Text is formated with a color font style ('ok' or 'error'), 2=Text is formated with 'ok' color.
 *	@return	string					HTML string
 */
function yn($yesno, $case=1, $color=0)
{
	global $langs;
	$result='unknown';
	if ($yesno == 1 || strtolower($yesno) == 'yes' || strtolower($yesno) == 'true') 	// A mettre avant test sur no a cause du == 0
	{
		$result=$langs->trans('yes');
		if ($case == 1 || $case == 3) $result=$langs->trans("Yes");
		if ($case == 2) $result='<input type="checkbox" value="1" checked disabled>';
		if ($case == 3) $result='<input type="checkbox" value="1" checked disabled> '.$result;

		$classname='ok';
	}
	elseif ($yesno == 0 || strtolower($yesno) == 'no' || strtolower($yesno) == 'false')
	{
		$result=$langs->trans("no");
		if ($case == 1 || $case == 3) $result=$langs->trans("No");
		if ($case == 2) $result='<input type="checkbox" value="0" disabled>';
		if ($case == 3) $result='<input type="checkbox" value="0" disabled> '.$result;

		if ($color == 2) $classname='ok';
		else $classname='error';
	}
	if ($color) return '<font class="'.$classname.'">'.$result.'</font>';
	return $result;
}


/**
 *	Return a path to have a directory according to object.
 *  New usage:       $conf->product->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, 'modulepart')
 *  Old usage:       '015' with level 3->"0/1/5/", '015' with level 1->"5/", 'ABC-1' with level 3 ->"0/0/1/" 
 *
 *	@param	string	$num            Id of object (deprecated, $object will be used in future)
 *	@param  int		$level		    Level of subdirs to return (1, 2 or 3 levels). (deprecated, global option will be used in future)
 * 	@param	int		$alpha		    0=Keep number only to forge path, 1=Use alpha part afer the - (By default, use 0). (deprecated, global option will be used in future)
 *  @param  int		$withoutslash   0=With slash at end (except if '/', we return ''), 1=without slash at end
 *  @param	Object	$object			Object
 *  @param	string	$modulepart		Type of object ('invoice_supplier, 'donation', 'invoice', ...')
 *  @return	string					Dir to use ending. Example '' or '1/' or '1/2/'
 */
function get_exdir($num, $level, $alpha, $withoutslash, $object, $modulepart)
{
	global $conf;

	$path = '';

	$arrayforoldpath=array('cheque','user','category','holiday','shipment','supplier_invoice','invoice_supplier','mailing');
	if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) $arrayforoldpath[]='product';	
	if (! empty($level) && in_array($modulepart, $arrayforoldpath))
	{
		// This part should be removed once all code is using "get_exdir" to forge path, with all parameters provided
		if (empty($alpha)) $num = preg_replace('/([^0-9])/i','',$num);
		else $num = preg_replace('/^.*\-/i','',$num);
		$num = substr("000".$num, -$level);
		if ($level == 1) $path = substr($num,0,1);
		if ($level == 2) $path = substr($num,1,1).'/'.substr($num,0,1);
		if ($level == 3) $path = substr($num,2,1).'/'.substr($num,1,1).'/'.substr($num,0,1);
	}
	else
	{
		// TODO
		// We will enhance here a common way of forging path for document storage
		// Here, object->id, object->ref and object->modulepart are required.
        if (in_array($modulepart, array('thirdparty','contact','member')))
        {
            $path=$object->ref?$object->ref:$object->id;
        }
	}

	if (empty($withoutslash) && ! empty($path)) $path.='/';
	return $path;
}

/**
 *	Creation of a directory (this can create recursive subdir)
 *
 *	@param	string	$dir		Directory to create (Separator must be '/'. Example: '/mydir/mysubdir')
 *	@param	string	$dataroot	Data root directory (To avoid having the data root in the loop. Using this will also lost the warning on first dir PHP has no permission when open_basedir is used)
 *  @param	int		$newmask	Mask for new file (Defaults to $conf->global->MAIN_UMASK or 0755 if unavailable). Example: '0444'
 *	@return int         		< 0 if KO, 0 = already exists, > 0 if OK
 */
function dol_mkdir($dir, $dataroot='', $newmask=null)
{
	global $conf;

	dol_syslog("functions.lib::dol_mkdir: dir=".$dir,LOG_INFO);

	$dir_osencoded=dol_osencode($dir);
	if (@is_dir($dir_osencoded)) return 0;

	$nberr=0;
	$nbcreated=0;

	$ccdir='';
	if (! empty($dataroot)) {
		// Remove data root from loop
		$dir = str_replace($dataroot.'/', '', $dir);
		$ccdir = $dataroot.'/';
	}

	$cdir = explode("/", $dir);
	$num=count($cdir);
	for ($i = 0; $i < $num; $i++)
	{
		if ($i > 0) $ccdir .= '/'.$cdir[$i];
		else $ccdir .= $cdir[$i];
		if (preg_match("/^.:$/",$ccdir,$regs)) continue;	// Si chemin Windows incomplet, on poursuit par rep suivant

		// Attention, le is_dir() peut echouer bien que le rep existe.
		// (ex selon config de open_basedir)
		if ($ccdir)
		{
			$ccdir_osencoded=dol_osencode($ccdir);
			if (! @is_dir($ccdir_osencoded))
			{
				dol_syslog("functions.lib::dol_mkdir: Directory '".$ccdir."' does not exists or is outside open_basedir PHP setting.",LOG_DEBUG);

				umask(0);
				$dirmaskdec=octdec($newmask);
				if (empty($newmask)) {
					$dirmaskdec = empty( $conf->global->MAIN_UMASK ) ? octdec( '0755' ) : octdec( $conf->global->MAIN_UMASK );
				}
				$dirmaskdec |= octdec('0111');  // Set x bit required for directories
				if (! @mkdir($ccdir_osencoded, $dirmaskdec))
				{
					// Si le is_dir a renvoye une fausse info, alors on passe ici.
					dol_syslog("functions.lib::dol_mkdir: Fails to create directory '".$ccdir."' or directory already exists.",LOG_WARNING);
					$nberr++;
				}
				else
				{
					dol_syslog("functions.lib::dol_mkdir: Directory '".$ccdir."' created",LOG_DEBUG);
					$nberr=0;	// On remet a zero car si on arrive ici, cela veut dire que les echecs precedents peuvent etre ignore
					$nbcreated++;
				}
			}
			else
			{
				$nberr=0;	// On remet a zero car si on arrive ici, cela veut dire que les echecs precedents peuvent etre ignores
			}
		}
	}
	return ($nberr ? -$nberr : $nbcreated);
}


/**
 *	Return picto saying a field is required
 *
 *	@return  string		Chaine avec picto obligatoire
 */
function picto_required()
{
	return '<span class="fieldrequired">*</span>';
}


/**
 *	Clean a string from all HTML tags and entities
 *
 *	@param	string	$StringHtml			String to clean
 *	@param	integer	$removelinefeed		1=Replace also new lines by a space, 0=Only last one are removed
 *  @param  string	$pagecodeto      	Encoding of input/output string
 *	@return string	    				String cleaned
 *
 * 	@see		dol_escape_htmltag
 */
function dol_string_nohtmltag($StringHtml,$removelinefeed=1,$pagecodeto='UTF-8')
{
	$pattern = "/<[^<>]+>/";
	$StringHtml = preg_replace('/<br[^>]*>/', "\n", $StringHtml);
	$temp = dol_html_entity_decode($StringHtml,ENT_COMPAT,$pagecodeto);
	$temp = preg_replace($pattern,"",$temp);

	// Supprime aussi les retours
	if ($removelinefeed) $temp=str_replace(array("\r\n","\r","\n")," ",$temp);

	// et les espaces doubles
	while(strpos($temp,"  "))
	{
		$temp = str_replace("  "," ",$temp);
	}
	$CleanString = trim($temp);
	return $CleanString;
}


/**
 * Return first line of text. Cut will depends if content is HTML or not.
 *
 * @param 	string	$text		Input text
 * @return	string				Output text
 * @see dol_nboflines_bis, dol_string_nohtmltag, dol_escape_htmltag
 */
function dolGetFirstLineOfText($text)
{
	if (dol_textishtml($text))
	{
		$firstline=preg_replace('/<br[^>]*>.*$/s','',$text);		// The s pattern modifier means the . can match newline characters
		$firstline=preg_replace('/<div[^>]*>.*$/s','',$firstline);	// The s pattern modifier means the . can match newline characters
		
	}
	else
	{
    	$firstline=preg_replace('/[\n\r].*/','',$text);
	}
    return $firstline.((strlen($firstline) != strlen($text))?'...':'');
}


/**
 * Replace CRLF in string with a HTML BR tag
 *
 * @param	string	$stringtoencode		String to encode
 * @param	int     $nl2brmode			0=Adding br before \n, 1=Replacing \n by br
 * @param   bool	$forxml             false=Use <br>, true=Use <br />
 * @return	string						String encoded
 * @see dol_nboflines, dolGetFirstLineOfText
 */
function dol_nl2br($stringtoencode,$nl2brmode=0,$forxml=false)
{
	if (!$nl2brmode) {
		return nl2br($stringtoencode, $forxml);
	} else {
		$ret=preg_replace('/(\r\n|\r|\n)/i', ($forxml?'<br />':'<br>'), $stringtoencode);
		return $ret;
	}
}


/**
 *	This function is called to encode a string into a HTML string but differs from htmlentities because
 * 	a detection is done before to see if text is already HTML or not. Also, all entities but &,<,> are converted.
 *  This permits to encode special chars to entities with no double encoding for already encoded HTML strings.
 * 	This function also remove last EOL or BR if $removelasteolbr=1 (default).
 *  For PDF usage, you can show text by 2 ways:
 *              - writeHTMLCell -> param must be encoded into HTML.
 *              - MultiCell -> param must not be encoded into HTML.
 *              Because writeHTMLCell convert also \n into <br>, if function
 *              is used to build PDF, nl2brmode must be 1.
 *
 *	@param	string	$stringtoencode		String to encode
 *	@param	int		$nl2brmode			0=Adding br before \n, 1=Replacing \n by br (for use with FPDF writeHTMLCell function for example)
 *  @param  string	$pagecodefrom       Pagecode stringtoencode is encoded
 *  @param	int		$removelasteolbr	1=Remove last br or lasts \n (default), 0=Do nothing
 *  @return	string						String encoded
 */
function dol_htmlentitiesbr($stringtoencode,$nl2brmode=0,$pagecodefrom='UTF-8',$removelasteolbr=1)
{
	$newstring=$stringtoencode;
	if (dol_textishtml($stringtoencode))	// Check if text is already HTML or not
	{
		$newstring=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i','<br>',$newstring);	// Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
		if ($removelasteolbr) $newstring=preg_replace('/<br>$/i','',$newstring);	// Remove last <br> (remove only last one)
		$newstring=strtr($newstring,array('&'=>'__and__','<'=>'__lt__','>'=>'__gt__','"'=>'__dquot__'));
		$newstring=dol_htmlentities($newstring,ENT_COMPAT,$pagecodefrom);	// Make entity encoding
		$newstring=strtr($newstring,array('__and__'=>'&','__lt__'=>'<','__gt__'=>'>','__dquot__'=>'"'));
	}
	else
	{
		if ($removelasteolbr) $newstring=preg_replace('/(\r\n|\r|\n)$/i','',$newstring);	// Remove last \n (may remove several)
		$newstring=dol_nl2br(dol_htmlentities($newstring,ENT_COMPAT,$pagecodefrom),$nl2brmode);
	}
	// Other substitutions that htmlentities does not do
	//$newstring=str_replace(chr(128),'&euro;',$newstring);	// 128 = 0x80. Not in html entity table.     // Seems useles with TCPDF. Make bug with UTF8 languages
	return $newstring;
}

/**
 *	This function is called to decode a HTML string (it decodes entities and br tags)
 *
 *	@param	string	$stringtodecode		String to decode
 *	@param	string	$pagecodeto			Page code for result
 *	@return	string						String decoded
 */
function dol_htmlentitiesbr_decode($stringtodecode,$pagecodeto='UTF-8')
{
	$ret=dol_html_entity_decode($stringtodecode,ENT_COMPAT,$pagecodeto);
	$ret=preg_replace('/'."\r\n".'<br(\s[\sa-zA-Z_="]*)?\/?>/i',"<br>",$ret);
	$ret=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>'."\r\n".'/i',"\r\n",$ret);
	$ret=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>'."\n".'/i',"\n",$ret);
	$ret=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i',"\n",$ret);
	return $ret;
}

/**
 *	This function remove all ending \n and br at end
 *
 *	@param	string	$stringtodecode		String to decode
 *	@return	string						String decoded
 */
function dol_htmlcleanlastbr($stringtodecode)
{
	$ret=preg_replace('/(<br>|<br(\s[\sa-zA-Z_="]*)?\/?>|'."\n".'|'."\r".')+$/i',"",$stringtodecode);
	return $ret;
}

/**
 * Replace html_entity_decode functions to manage errors
 *
 * @param   string	$a		Operand a
 * @param   string	$b		Operand b (ENT_QUOTES=convert simple and double quotes)
 * @param   string	$c		Operand c
 * @return  string			String decoded
 */
function dol_html_entity_decode($a,$b,$c='UTF-8')
{
	return html_entity_decode($a,$b,$c);
}

/**
 * Replace htmlentities functions to manage errors http://php.net/manual/en/function.htmlentities.php
 * Goal of this function is to be sure to have default values of htmlentities that match what we need.
 *
 * @param   string  $string         The input string.
 * @param   int     $flags          Flags(see PHP doc above)
 * @param   string  $encoding       Encoding
 * @param   bool    $double_encode  When double_encode is turned off PHP will not encode existing html entities
 * @return  string  $ret            Encoded string
 */
function dol_htmlentities($string, $flags=null, $encoding='UTF-8', $double_encode=false)
{
	return htmlentities($string, $flags, $encoding, $double_encode);
}


/**
 *	Check if a string is a correct iso string
 *	If not, it will we considered not HTML encoded even if it is by FPDF.
 *	Example, if string contains euro symbol that has ascii code 128
 *
 *	@param	string	$s      String to check
 *	@return	int     		0 if bad iso, 1 if good iso
 */
function dol_string_is_good_iso($s)
{
	$len=dol_strlen($s);
	$ok=1;
	for($scursor=0;$scursor<$len;$scursor++)
	{
		$ordchar=ord($s{$scursor});
		//print $scursor.'-'.$ordchar.'<br>';
		if ($ordchar < 32 && $ordchar != 13 && $ordchar != 10) { $ok=0; break; }
		if ($ordchar > 126 && $ordchar < 160) { $ok=0; break; }
	}
	return $ok;
}


/**
 *	Return nb of lines of a clear text
 *
 *	@param	string	$s			String to check
 * 	@param	int     $maxchar	Not yet used
 *	@return	int					Number of lines
 *  @see	dol_nboflines_bis, dolGetFirstLineOfText
 */
function dol_nboflines($s,$maxchar=0)
{
	if ($s == '') return 0;
	$arraystring=explode("\n",$s);
	$nb=count($arraystring);

	return $nb;
}


/**
 *	Return nb of lines of a formated text with \n and <br> (we can't have both \n and br)
 *
 *	@param	string	$text      		Text
 *	@param	int		$maxlinesize  	Largeur de ligne en caracteres (ou 0 si pas de limite - defaut)
 * 	@param	string	$charset		Give the charset used to encode the $text variable in memory.
 *	@return int						Number of lines
 *	@see	dol_nboflines, dolGetFirstLineOfText
 */
function dol_nboflines_bis($text,$maxlinesize=0,$charset='UTF-8')
{
	$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
	if (dol_textishtml($text)) $repTable = array("\t" => " ", "\n" => " ", "\r" => " ", "\0" => " ", "\x0B" => " ");

	$text = strtr($text, $repTable);
	if ($charset == 'UTF-8') { $pattern = '/(<br[^>]*>)/Uu'; }	// /U is to have UNGREEDY regex to limit to one html tag. /u is for UTF8 support
	else $pattern = '/(<br[^>]*>)/U';							// /U is to have UNGREEDY regex to limit to one html tag.
	$a = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

	$nblines = (int) floor((count($a)+1)/2);
	// count possible auto line breaks
	if($maxlinesize)
	{
		foreach ($a as $line)
		{
			if (dol_strlen($line)>$maxlinesize)
			{
				//$line_dec = html_entity_decode(strip_tags($line));
				$line_dec = html_entity_decode($line);
				if(dol_strlen($line_dec)>$maxlinesize)
				{
					$line_dec=wordwrap($line_dec,$maxlinesize,'\n',true);
					$nblines+=substr_count($line_dec,'\n');
				}
			}
		}
	}
	return $nblines;
}

/**
 *	 Same function than microtime in PHP 5 but compatible with PHP4
 *
 * @return		float		Time (millisecondes) with microsecondes in decimal part
 * @deprecated Dolibarr does not support PHP4, you should use native function
 * @see microtime()
 */
function dol_microtime_float()
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	return microtime(true);
}

/**
 *	Return if a text is a html content
 *
 *	@param	string	$msg		Content to check
 *	@param	int		$option		0=Full detection, 1=Fast check
 *	@return	boolean				true/false
 *	@see	dol_concatdesc
 */
function dol_textishtml($msg,$option=0)
{
	if ($option == 1)
	{
		if (preg_match('/<html/i',$msg))				return true;
		elseif (preg_match('/<body/i',$msg))			return true;
		elseif (preg_match('/<br/i',$msg))				return true;
		return false;
	}
	else
	{
		if (preg_match('/<html/i',$msg))				return true;
		elseif (preg_match('/<body/i',$msg))			return true;
		elseif (preg_match('/<(b|em|i|u)>/i',$msg))		return true;
		elseif (preg_match('/<(br|div|font|li|p|span|strong|table)>/i',$msg)) 	  return true;
		elseif (preg_match('/<(br|div|font|li|p|span|strong|table)\s+[^<>\/]*>/i',$msg)) return true;
		elseif (preg_match('/<(br|div|font|li|p|span|strong|table)\s+[^<>\/]*\/>/i',$msg)) return true;
		elseif (preg_match('/<img\s+[^<>]*src[^<>]*>/i',$msg)) return true;	// must accept <img src="http://example.com/aaa.png" />
		elseif (preg_match('/<a\s+[^<>]*href[^<>]*>/i',$msg)) return true;	// must accept <a href="http://example.com/aaa.png" />
		elseif (preg_match('/<h[0-9]>/i',$msg))			return true;
		elseif (preg_match('/&[A-Z0-9]{1,6};/i',$msg))	return true;    // Html entities names (http://www.w3schools.com/tags/ref_entities.asp)
		elseif (preg_match('/&#[0-9]{2,3};/i',$msg))	return true;    // Html entities numbers (http://www.w3schools.com/tags/ref_entities.asp)
		return false;
	}
}

/**
 *  Concat 2 descriptions with a new line between them (second operand after first one with appropriate new line separator)
 *  text1 html + text2 html => text1 + '<br>' + text2
 *  text1 html + text2 txt  => text1 + '<br>' + dol_nl2br(text2)
 *  text1 txt  + text2 html => dol_nl2br(text1) + '<br>' + text2
 *  text1 txt  + text2 txt  => text1 + '\n' + text2
 *
 *  @param	string	$text1		Text 1
 *  @param	string	$text2		Text 2
 *  @param  bool	$forxml     false=Use <br>, true=Use <br />
 *  @return	string				Text 1 + new line + Text2
 *  @see    dol_textishtml
 */
function dol_concatdesc($text1,$text2,$forxml=false)
{
	$ret='';
	$ret.= (! dol_textishtml($text1) && dol_textishtml($text2))?dol_nl2br($text1, 0, $forxml):$text1;
	$ret.= (! empty($text1) && ! empty($text2)) ? ((dol_textishtml($text1) || dol_textishtml($text2))?($forxml?"<br \>\n":"<br>\n") : "\n") : "";
	$ret.= (dol_textishtml($text1) && ! dol_textishtml($text2))?dol_nl2br($text2, 0, $forxml):$text2;
	return $ret;
}

/**
 *  Make substition into a string replacing key with vals from $substitutionarray (oldval=>newval)
 *
 *  @param	string	$chaine      			Source string in which we must do substitution
 *  @param  array	$substitutionarray		Array with key->val to substitute
 * 	@return string  		    			Output string after subsitutions
 *  @see	complete_substitutions_array
 */
function make_substitutions($chaine,$substitutionarray)
{
	global $conf;

	if (! is_array($substitutionarray)) return 'ErrorBadParameterSubstitutionArrayWhenCalling_make_substitutions';

	// Make substitition
	foreach ($substitutionarray as $key => $value)
	{
		if ($key == '__SIGNATURE__' && (! empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))) $value='';
		$chaine=str_replace("$key","$value",$chaine);	// We must keep the " to work when value is 123.5 for example
	}

	return $chaine;
}

/**
 *  Complete the $substitutionarray with more entries
 *
 *  @param  array		$substitutionarray		Array substitution old value => new value value
 *  @param  Translate	$outputlangs            If we want substitution from special constants, we provide a language
 *  @param  object		$object                 If we want substitution from special constants, we provide data in a source object
 *  @param  Mixed		$parameters       		Add more parameters (useful to pass product lines)
 *  @param  string      $callfunc               What is the name of the custom function that will be called? (default: completesubstitutionarray)
 *  @return	void
 *  @see 	make_substitutions
 */
function complete_substitutions_array(&$substitutionarray,$outputlangs,$object='',$parameters=null,$callfunc="completesubstitutionarray")
{
	global $conf,$user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Check if there is external substitution to do asked by plugins
	$dirsubstitutions=array_merge(array(),(array) $conf->modules_parts['substitutions']);

	foreach($dirsubstitutions as $reldir)
	{
		$dir=dol_buildpath($reldir,0);

		// Check if directory exists
		if (! dol_is_dir($dir)) continue;

		$substitfiles=dol_dir_list($dir,'files',0,'functions_');
		foreach($substitfiles as $substitfile)
		{
			if (preg_match('/functions_(.*)\.lib\.php/i',$substitfile['name'],$reg))
			{
				$module=$reg[1];

				dol_syslog("Library functions_".$substitfile['name']." found into ".$dir);
				// Include the user's functions file
				require_once $dir.$substitfile['name'];
				// Call the user's function, and only if it is defined
				$function_name=$module."_".$callfunc;
				if (function_exists($function_name)) $function_name($substitutionarray,$outputlangs,$object,$parameters);
			}
		}
	}
}

/**
 *    Format output for start and end date
 *
 *    @param	int	$date_start    Start date
 *    @param    int	$date_end      End date
 *    @param    string		$format        Output format
 *    @param	Translate	$outputlangs   Output language
 *    @return	void
 */
function print_date_range($date_start,$date_end,$format = '',$outputlangs='')
{
	print get_date_range($date_start,$date_end,$format,$outputlangs);
}

/**
 *    Format output for start and end date
 *
 *    @param	int			$date_start    		Start date
 *    @param    int			$date_end      		End date
 *    @param    string		$format        		Output format
 *    @param	Translate	$outputlangs   		Output language
 *    @param	integer		$withparenthesis	1=Add parenthesis, 0=non parenthesis
 *    @return	string							String
 */
function get_date_range($date_start,$date_end,$format = '',$outputlangs='', $withparenthesis=1)
{
	global $langs;

	$out='';

	if (! is_object($outputlangs)) $outputlangs=$langs;

	if ($date_start && $date_end)
	{
		$out.= ($withparenthesis?' (':'').$outputlangs->transnoentitiesnoconv('DateFromTo',dol_print_date($date_start, $format, false, $outputlangs),dol_print_date($date_end, $format, false, $outputlangs)).($withparenthesis?')':'');
	}
	if ($date_start && ! $date_end)
	{
		$out.= ($withparenthesis?' (':'').$outputlangs->transnoentitiesnoconv('DateFrom',dol_print_date($date_start, $format, false, $outputlangs)).($withparenthesis?')':'');
	}
	if (! $date_start && $date_end)
	{
		$out.= ($withparenthesis?' (':'').$outputlangs->transnoentitiesnoconv('DateUntil',dol_print_date($date_end, $format, false, $outputlangs)).($withparenthesis?')':'');
	}

	return $out;
}

/**
 * Return firstname and lastname in correct order
 *
 * @param	string	$firstname		Firstname
 * @param	string	$lastname		Lastname
 * @param	int		$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname, 2=Firstname
 * @return	string					Firstname + lastname or Lastname + firstname
 */
function dolGetFirstLastname($firstname,$lastname,$nameorder=-1)
{
	global $conf;

	$ret='';
	// If order not defined, we use the setup
	if ($nameorder < 0) $nameorder=(empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION));
	if ($nameorder && ((string) $nameorder != '2'))
	{
        $ret.=$firstname;
		if ($firstname && $lastname) $ret.=' ';
		$ret.=$lastname;
	}
	else if ($nameorder == 2)
	{
	   $ret.=$firstname;
	}
	else
	{
		$ret.=$lastname;
		if ($firstname && $lastname) $ret.=' ';
		$ret.=$firstname;
	}
	return $ret;
}


/**
 *	Set event message in dol_events session object. Will be output by calling dol_htmloutput_events.
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
 *  Note: Prefer to use setEventMessages instead.
 *
 *	@param	mixed	$mesgs			Message string or array
 *  @param  string	$style      	Which style to use ('mesgs' by default, 'warnings', 'errors')
 *  @return	void
 *  @see	dol_htmloutput_events
 */
function setEventMessage($mesgs, $style='mesgs')
{
	//dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);		This is not deprecated, it is used by setEventMessages function
	if (! is_array($mesgs))		// If mesgs is a string
	{
		if ($mesgs) $_SESSION['dol_events'][$style][] = $mesgs;
	}
	else						// If mesgs is an array
	{
		foreach($mesgs as $mesg)
		{
			if ($mesg) $_SESSION['dol_events'][$style][] = $mesg;
		}
	}
}

/**
 *	Set event messages in dol_events session object. Will be output by calling dol_htmloutput_events.
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
 *
 *	@param	string	$mesg			Message string
 *	@param	array	$mesgs			Message array
 *  @param  string	$style      	Which style to use ('mesgs' by default, 'warnings', 'errors')
 *  @return	void
 *  @see	dol_htmloutput_events
 */
function setEventMessages($mesg, $mesgs, $style='mesgs')
{
	if (! in_array((string) $style, array('mesgs','warnings','errors'))) dol_print_error('','Bad parameter style='.$style.' for setEventMessages');
	if (empty($mesgs)) setEventMessage($mesg, $style);
	else
	{
		if (! empty($mesg) && ! in_array($mesg, $mesgs)) setEventMessage($mesg, $style);	// Add message string if not already into array
		setEventMessage($mesgs, $style);
	}
}

/**
 *	Print formated messages to output (Used to show messages on html output).
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function, so there is
 *  no need to call it explicitely.
 *
 *  @return	void
 *  @see    dol_htmloutput_mesg
 */
function dol_htmloutput_events()
{
	// Show mesgs
	if (isset($_SESSION['dol_events']['mesgs'])) {
		dol_htmloutput_mesg('', $_SESSION['dol_events']['mesgs']);
		unset($_SESSION['dol_events']['mesgs']);
	}

	// Show errors
	if (isset($_SESSION['dol_events']['errors'])) {
		dol_htmloutput_mesg('', $_SESSION['dol_events']['errors'], 'error');
		unset($_SESSION['dol_events']['errors']);
	}

	// Show warnings
	if (isset($_SESSION['dol_events']['warnings'])) {
		dol_htmloutput_mesg('', $_SESSION['dol_events']['warnings'], 'warning');
		unset($_SESSION['dol_events']['warnings']);
	}
}

/**
 *	Get formated messages to output (Used to show messages on html output).
 *  This include also the translation of the message key.
 *
 *	@param	string		$mesgstring		Message string or message key
 *	@param	string[]	$mesgarray      Array of message strings or message keys
 *  @param  string		$style          Style of message output ('ok' or 'error')
 *  @param  int			$keepembedded   Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *	@return	string						Return html output
 *
 *  @see    dol_print_error
 *  @see    dol_htmloutput_errors
 *  @see    setEventMessages
 */
function get_htmloutput_mesg($mesgstring='',$mesgarray='', $style='ok', $keepembedded=0)
{
	global $conf, $langs;

	$ret='';
	$out='';
	$divstart=$divend='';

	// If inline message with no format, we add it.
	if ((empty($conf->use_javascript_ajax) || ! empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) || $keepembedded) && ! preg_match('/<div class=".*">/i',$out))
	{
		$divstart='<div class="'.$style.'">';
		$divend='</div>';
	}

	if ((is_array($mesgarray) && count($mesgarray)) || $mesgstring)
	{
		$langs->load("errors");
		$out.=$divstart;
		if (is_array($mesgarray) && count($mesgarray))
		{
			foreach($mesgarray as $message)
			{
				$ret++;
				$out.= $langs->trans($message);
				if ($ret < count($mesgarray)) $out.= "<br>\n";
			}
		}
		if ($mesgstring)
		{
			$langs->load("errors");
			$ret++;
			$out.= $langs->trans($mesgstring);
		}
		$out.=$divend;
	}

	if ($out)
	{
		if (! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && empty($keepembedded))
		{
			$return = '<script type="text/javascript">
					$(document).ready(function() {
						var block = '.(! empty($conf->global->MAIN_USE_JQUERY_BLOCKUI)?"true":"false").'
						if (block) {
							$.dolEventValid("","'.dol_escape_js($out).'");
						} else {
							/* jnotify(message, preset of message type, keepmessage) */
							$.jnotify("'.dol_escape_js($out).'",
							"'.($style=="ok" ? 3000 : $style).'",
							'.($style=="ok" ? "false" : "true").',
							{ remove: function (){} } );
						}
					});
				</script>';
		}
		else
		{
			$return = $out;
		}
	}

	return $return;
}

/**
 *  Get formated error messages to output (Used to show messages on html output).
 *
 *  @param	string	$mesgstring         Error message
 *  @param  array	$mesgarray          Error messages array
 *  @param  int		$keepembedded       Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return string                		Return html output
 *
 *  @see    dol_print_error
 *  @see    dol_htmloutput_mesg
 */
function get_htmloutput_errors($mesgstring='', $mesgarray='', $keepembedded=0)
{
	return get_htmloutput_mesg($mesgstring, $mesgarray,'error',$keepembedded);
}

/**
 *	Print formated messages to output (Used to show messages on html output).
 *
 *	@param	string		$mesgstring		Message string or message key
 *	@param	string[]	$mesgarray      Array of message strings or message keys
 *  @param  string      $style          Which style to use ('ok', 'warning', 'error')
 *  @param  int         $keepembedded   Set to 1 if message must be kept embedded into its html place (this disable jnotify)
 *  @return	void
 *
 *  @see    dol_print_error
 *  @see    dol_htmloutput_errors
 *  @see    setEventMessages
 */
function dol_htmloutput_mesg($mesgstring='',$mesgarray='', $style='ok', $keepembedded=0)
{
	if (empty($mesgstring) && (! is_array($mesgarray) || count($mesgarray) == 0)) return;

	$iserror=0;
	$iswarning=0;
	if (is_array($mesgarray))
	{
		foreach($mesgarray as $val)
		{
			if ($val && preg_match('/class="error"/i',$val)) { $iserror++; break; }
			if ($val && preg_match('/class="warning"/i',$val)) { $iswarning++; break; }
		}
	}
	else if ($mesgstring && preg_match('/class="error"/i',$mesgstring)) $iserror++;
	else if ($mesgstring && preg_match('/class="warning"/i',$mesgstring)) $iswarning++;
	if ($style=='error') $iserror++;
	if ($style=='warning') $iswarning++;

	if ($iserror || $iswarning)
	{
		// Remove div from texts
		$mesgstring=preg_replace('/<\/div><div class="(error|warning)">/','<br>',$mesgstring);
		$mesgstring=preg_replace('/<div class="(error|warning)">/','',$mesgstring);
		$mesgstring=preg_replace('/<\/div>/','',$mesgstring);
		// Remove div from texts array
		if (is_array($mesgarray))
		{
			$newmesgarray=array();
			foreach($mesgarray as $val)
			{
				$tmpmesgstring=preg_replace('/<\/div><div class="(error|warning)">/','<br>',$val);
				$tmpmesgstring=preg_replace('/<div class="(error|warning)">/','',$tmpmesgstring);
				$tmpmesgstring=preg_replace('/<\/div>/','',$tmpmesgstring);
				$newmesgarray[]=$tmpmesgstring;
			}
			$mesgarray=$newmesgarray;
		}
		print get_htmloutput_mesg($mesgstring,$mesgarray,($iserror?'error':'warning'),$keepembedded);
	}
	else print get_htmloutput_mesg($mesgstring,$mesgarray,'ok',$keepembedded);
}

/**
 *  Print formated error messages to output (Used to show messages on html output).
 *
 *  @param	string	$mesgstring          Error message
 *  @param  array	$mesgarray           Error messages array
 *  @param  int		$keepembedded        Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return	void
 *
 *  @see    dol_print_error
 *  @see    dol_htmloutput_mesg
 */
function dol_htmloutput_errors($mesgstring='', $mesgarray='', $keepembedded=0)
{
	dol_htmloutput_mesg($mesgstring, $mesgarray, 'error', $keepembedded);
}

/**
 * 	Advanced sort array by second index function, which produces ascending (default)
 *  or descending output and uses optionally natural case insensitive sorting (which
 *  can be optionally case sensitive as well).
 *
 *  @param      array		$array      		Array to sort (array of array('key','otherkey1','otherkey2'...))
 *  @param      string		$index				Key in array to use for sorting criteria
 *  @param      int			$order				Sort order ('asc' or 'desc')
 *  @param      int			$natsort			1=use "natural" sort (natsort), 0=use "standard" sort (asort)
 *  @param      int			$case_sensitive		1=sort is case sensitive, 0=not case sensitive
 *  @param		int			$keepindex			If 0 and index key of array to sort is a numeric, than index will be rewrote. If 1 or index key is not numeric, key for index is kept after sorting.
 *  @return     array							Sorted array
 */
function dol_sort_array(&$array, $index, $order='asc', $natsort=0, $case_sensitive=0, $keepindex=0)
{
	// Clean parameters
	$order=strtolower($order);

	$sizearray=count($array);
	if (is_array($array) && $sizearray>0)
	{
		foreach(array_keys($array) as $key) $temp[$key]=$array[$key][$index];

		if (!$natsort) ($order=='asc') ? asort($temp) : arsort($temp);
		else
		{
			($case_sensitive) ? natsort($temp) : natcasesort($temp);
			if($order!='asc') $temp=array_reverse($temp,TRUE);
		}

		$sorted = array();

		foreach(array_keys($temp) as $key)
		{
			(is_numeric($key) && empty($keepindex)) ? $sorted[]=$array[$key] : $sorted[$key]=$array[$key];
		}

		return $sorted;
	}
	return $array;
}


/**
 *      Check if a string is in UTF8
 *
 *      @param	string	$str        String to check
 * 		@return	boolean				True if string is UTF8 or ISO compatible with UTF8, False if not (ISO with special char or Binary)
 */
function utf8_check($str)
{
	// We must use here a binary strlen function (so not dol_strlen)
	$strLength = dol_strlen($str);
	for ($i=0; $i<$strLength; $i++)
	{
		if (ord($str[$i]) < 0x80) continue; // 0bbbbbbb
		elseif ((ord($str[$i]) & 0xE0) == 0xC0) $n=1; // 110bbbbb
		elseif ((ord($str[$i]) & 0xF0) == 0xE0) $n=2; // 1110bbbb
		elseif ((ord($str[$i]) & 0xF8) == 0xF0) $n=3; // 11110bbb
		elseif ((ord($str[$i]) & 0xFC) == 0xF8) $n=4; // 111110bb
		elseif ((ord($str[$i]) & 0xFE) == 0xFC) $n=5; // 1111110b
		else return false; // Does not match any model
		for ($j=0; $j<$n; $j++) { // n bytes matching 10bbbbbb follow ?
			if ((++$i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80))
			return false;
		}
	}
	return true;
}


/**
 *      Return a string encoded into OS filesystem encoding. This function is used to define
 * 	    value to pass to filesystem PHP functions.
 *
 *      @param	string	$str        String to encode (UTF-8)
 * 		@return	string				Encoded string (UTF-8, ISO-8859-1)
 */
function dol_osencode($str)
{
	global $conf;

	$tmp=ini_get("unicode.filesystem_encoding");						// Disponible avec PHP 6.0
	if (empty($tmp) && ! empty($_SERVER["WINDIR"])) $tmp='iso-8859-1';	// By default for windows
	if (empty($tmp)) $tmp='utf-8';										// By default for other
	if (! empty($conf->global->MAIN_FILESYSTEM_ENCODING)) $tmp=$conf->global->MAIN_FILESYSTEM_ENCODING;

	if ($tmp == 'iso-8859-1') return utf8_decode($str);
	return $str;
}


/**
 *      Return an id or code from a code or id.
 *      Store also Code-Id into a cache to speed up next request on same key.
 *
 * 		@param	DoliDB	$db			Database handler
 * 		@param	string	$key		Code or Id to get Id or Code
 * 		@param	string	$tablename	Table name without prefix
 * 		@param	string	$fieldkey	Field for code
 * 		@param	string	$fieldid	Field for id
 *      @return int					<0 if KO, Id of code if OK
 *      @see $langs->getLabelFromKey
 */
function dol_getIdFromCode($db,$key,$tablename,$fieldkey='code',$fieldid='id')
{
	global $cache_codes;

	// If key empty
	if ($key == '') return '';

	// Check in cache
	if (isset($cache_codes[$tablename][$key]))	// Can be defined to 0 or ''
	{
		return $cache_codes[$tablename][$key];   // Found in cache
	}

	$sql = "SELECT ".$fieldid." as valuetoget";
	$sql.= " FROM ".MAIN_DB_PREFIX.$tablename;
	$sql.= " WHERE ".$fieldkey." = '".$db->escape($key)."'";
	dol_syslog('dol_getIdFromCode', LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj) $cache_codes[$tablename][$key]=$obj->valuetoget;
		else $cache_codes[$tablename][$key]='';
		$db->free($resql);
		return $cache_codes[$tablename][$key];
	}
	else
	{
		return -1;
	}
}

/**
 * Verify if condition in string is ok or not
 *
 * @param 	string		$strRights		String with condition to check
 * @return 	boolean						True or False. Return true if strRights is ''
 */
function verifCond($strRights)
{
	global $user,$conf,$langs;
	global $leftmenu;
	global $rights;    // To export to dol_eval function

	//print $strRights."<br>\n";
	$rights = true;
	if ($strRights != '')
	{
		//$tab_rights = explode('&&', $strRights);
		//$i = 0;
		//while (($i < count($tab_rights)) && ($rights == true)) {
		$str = 'if(!(' . $strRights . ')) { $rights = false; }';
		dol_eval($str);
		//	$i++;
		//}
	}
	return $rights;
}

/**
 * Replace eval function to add more security.
 * This function is called by verifCond() or trans() and transnoentitiesnoconv().
 *
 * @param 	string	$s				String to evaluate
 * @param	int		$returnvalue	0=No return (used to execute eval($a=something)). 1=Value of eval is returned (used to eval($something)).
 * @return	mixed					Nothing or return of eval
 */
function dol_eval($s,$returnvalue=0)
{
	// Only global variables can be changed by eval function and returned to caller
	global $langs, $user, $conf;
	global $leftmenu;
	global $rights;
	global $object;
    global $soc;

	//print $s."<br>\n";
	if ($returnvalue) return @eval('return '.$s.';');
	else @eval($s);
}

/**
 * Return if var element is ok
 *
 * @param   string      $element    Variable to check
 * @return  boolean                 Return true of variable is not empty
 */
function dol_validElement($element)
{
	return (trim($element) != '');
}

/**
 * 	Return img flag of country for a language code or country code
 *
 * 	@param	string	$codelang	Language code (en_IN, fr_CA...) or Country code (IN, FR)
 * 	@return	string				HTML img string with flag.
 */
function picto_from_langcode($codelang)
{
	global $langs;

	if (empty($codelang)) return '';

	if (empty($codelang)) return '';

	if ($codelang == 'auto')
	{
		return img_picto_common($langs->trans('AutoDetectLang'), 'flags/int.png');
	}

	$langtocountryflag = array(
		'ar_AR' => '',
		'ca_ES' => 'catalonia',
		'da_DA' => 'dk',
		'fr_CA' => 'mq',
		'sv_SV' => 'se'
	);

	if (isset($langtocountryflag[$codelang])) $flagImage = $langtocountryflag[$codelang];
	else
	{
		$tmparray = explode('_', $codelang);
		$flagImage = empty($tmparray[1]) ? $tmparray[0] : $tmparray[1];
	}

	return img_picto_common($codelang, 'flags/'.strtolower($flagImage).'.png');
}

/**
 *  Complete or removed entries into a head array (used to build tabs). 
 *  For example, with value added by external modules. Such values are declared into $conf->modules_parts['tab'].
 *  Or by change using hook completeTabsHead
 *
 *  @param	Conf			$conf           Object conf
 *  @param  Translate		$langs          Object langs
 *  @param  object|null		$object         Object object
 *  @param  array			$head          	Object head
 *  @param  int				$h				New position to fill
 *  @param  string			$type           Value for object where objectvalue can be
 *                              			'thirdparty'       to add a tab in third party view
 *		                        	      	'intervention'     to add a tab in intervention view
 *     		                    	     	'supplier_order'   to add a tab in supplier order view
 *          		            	        'supplier_invoice' to add a tab in supplier invoice view
 *                  		    	        'invoice'          to add a tab in customer invoice view
 *                          			    'order'            to add a tab in customer order view
 *                      			        'product'          to add a tab in product view
 *                              			'propal'           to add a tab in propal view
 *                              			'user'             to add a tab in user view
 *                              			'group'            to add a tab in group view
 * 		        	               	     	'member'           to add a tab in fundation member view
 *      		                        	'categories_x'	   to add a tab in category view ('x': type of category (0=product, 1=supplier, 2=customer, 3=member)
 *      									'ecm'			   to add a tab for another ecm view
 *                                          'stock'            to add a tab for warehouse view
 *  @param  string		$mode  	        	'add' to complete head, 'remove' to remove entries
 *	@return	void
 */
function complete_head_from_modules($conf,$langs,$object,&$head,&$h,$type,$mode='add')
{
	global $hookmanager;
	
	if (isset($conf->modules_parts['tabs'][$type]) && is_array($conf->modules_parts['tabs'][$type]))
	{
		foreach ($conf->modules_parts['tabs'][$type] as $value)
		{
			$values=explode(':',$value);

			if ($mode == 'add' && ! preg_match('/^\-/',$values[1]))
			{
				if (count($values) == 6)       // new declaration with permissions:  $value='objecttype:+tabname1:Title1:langfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__'
				{
					if ($values[0] != $type) continue;

					if (verifCond($values[4]))
					{
						if ($values[3]) $langs->load($values[3]);
						if (preg_match('/SUBSTITUTION_([^_]+)/i',$values[2],$reg))
						{
							$substitutionarray=array();
							complete_substitutions_array($substitutionarray,$langs,$object,array('needforkey'=>$values[2]));
							$label=make_substitutions($reg[1], $substitutionarray);
						}
						else $label=$langs->trans($values[2]);

						$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i', ((is_object($object) && ! empty($object->id))?$object->id:''), $values[5]), 1);
						$head[$h][1] = $label;
						$head[$h][2] = str_replace('+','',$values[1]);
						$h++;
					}
				}
				else if (count($values) == 5)       // deprecated
				{
					dol_syslog('Passing 5 values in tabs module_parts is deprecated. Please update to 6 with permissions.', LOG_WARNING);

					if ($values[0] != $type) continue;
					if ($values[3]) $langs->load($values[3]);
					if (preg_match('/SUBSTITUTION_([^_]+)/i',$values[2],$reg))
					{
						$substitutionarray=array();
						complete_substitutions_array($substitutionarray,$langs,$object,array('needforkey'=>$values[2]));
						$label=make_substitutions($reg[1], $substitutionarray);
					}
					else $label=$langs->trans($values[2]);

					$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i', ((is_object($object) && ! empty($object->id))?$object->id:''), $values[4]), 1);
					$head[$h][1] = $label;
					$head[$h][2] = str_replace('+','',$values[1]);
					$h++;
				}
			}
			else if ($mode == 'remove' && preg_match('/^\-/',$values[1]))
			{
				if ($values[0] != $type) continue;
				$tabname=str_replace('-','',$values[1]);
				foreach($head as $key => $val)
				{
					$condition = (! empty($values[3]) ? verifCond($values[3]) : 1);
					if ($head[$key][2]==$tabname && $condition)
					{
						unset($head[$key]);
						break;
					}
				}
			}
		}
	}
	
	// No need to make a return $head. Var is modified as a reference
	if (! empty($hookmanager))
	{
		$parameters=array('object' => $object, 'mode' => $mode, 'head'=>$head);
		$reshook=$hookmanager->executeHooks('completeTabsHead',$parameters);
		if ($reshook > 0)
		{
			$head = $hookmanager->resArray;
		}
	}
}

/**
 * Print common footer :
 * 		conf->global->MAIN_HTML_FOOTER
 * 		conf->global->MAIN_GOOGLE_AN_ID
 * 		conf->global->MAIN_SHOW_TUNING_INFO or $_SERVER["MAIN_SHOW_TUNING_INFO"]
 * 		conf->logbuffer
 *
 * @param	string	$zone	'private' (for private pages) or 'public' (for public pages)
 * @return	void
 */
function printCommonFooter($zone='private')
{
	global $conf, $hookmanager;
	global $micro_start_time;

	if ($zone == 'private') print "\n".'<!-- Common footer for private page -->'."\n";
	else print "\n".'<!-- Common footer for public page -->'."\n";

	if (! empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER."\n";

	print "\n";
	if (! empty($conf->use_javascript_ajax))
	{
		print '<!-- Reposition management (does not work if a redirect is done after action of submission) -->'."\n";
    	print '<script type="text/javascript" language="javascript">jQuery(document).ready(function() {'."\n";
    	
    	print '<!-- If page_y set, we set scollbar with it -->'."\n";
    	print "page_y=getParameterByName('page_y', 0);";
    	print "if (page_y > 0) $('html, body').scrollTop(page_y);\n";
    	
    	print '<!-- Set handler to add page_y param on some a href links -->'."\n";
    	print 'jQuery(".reposition").click(function() {
    	           var page_y = $(document).scrollTop();
    	           /*alert(page_y);*/
    	           this.href=this.href+\'&page_y=\'+page_y;
    	           });'."\n";
    	print '});'."\n";
    	
    	if (empty($conf->dol_use_jmobile))
    	{
        	print '<!-- Set handler to switch left menu page -->'."\n";
        	print 'jQuery(".menuhider").click(function() {';
        	print "  $('.side-nav').toggle();";
        	if ($conf->theme == 'md') print "  $('.login_block').toggle();";
        	print '});'."\n";
    	}
    	
    	print '</script>'."\n";
	}
	
	// Google Analytics (need Google module)
	if (! empty($conf->google->enabled) && ! empty($conf->global->MAIN_GOOGLE_AN_ID))
	{
		if (($conf->dol_use_jmobile != 4))
		{
			print "\n";
			print '<script type="text/javascript">'."\n";
			print '  var _gaq = _gaq || [];'."\n";
			print '  _gaq.push([\'_setAccount\', \''.$conf->global->MAIN_GOOGLE_AN_ID.'\']);'."\n";
			print '  _gaq.push([\'_trackPageview\']);'."\n";
			print ''."\n";
			print '  (function() {'."\n";
			print '    var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;'."\n";
			print '    ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';'."\n";
			print '    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);'."\n";
			print '  })();'."\n";
			print '</script>'."\n";
		}
	}

	// End of tuning
	if (! empty($_SERVER['MAIN_SHOW_TUNING_INFO']) || ! empty($conf->global->MAIN_SHOW_TUNING_INFO))
	{
		print "\n".'<script type="text/javascript">'."\n";
		print 'window.console && console.log("';
		if (! empty($conf->global->MEMCACHED_SERVER)) print 'MEMCACHED_SERVER='.$conf->global->MEMCACHED_SERVER.' - ';
		print 'MAIN_OPTIMIZE_SPEED='.(isset($conf->global->MAIN_OPTIMIZE_SPEED)?$conf->global->MAIN_OPTIMIZE_SPEED:'off');
		if (! empty($micro_start_time))   // Works only if MAIN_SHOW_TUNING_INFO is defined at $_SERVER level. Not in global variable.
		{
			$micro_end_time = microtime(true);
			print ' - Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
		}
		if (function_exists("memory_get_usage"))
		{
			print ' - Mem: '.memory_get_usage();
		}
		if (function_exists("xdebug_memory_usage"))
		{
			print ' - XDebug time: '.ceil(1000*xdebug_time_index()).' ms';
			print ' - XDebug mem: '.xdebug_memory_usage();
			print ' - XDebug mem peak: '.xdebug_peak_memory_usage();
		}
		if (function_exists("zend_loader_file_encoded"))
		{
			print ' - Zend encoded file: '.(zend_loader_file_encoded()?'yes':'no');
		}
		print '");'."\n";
		print '</script>'."\n";

		// Add Xdebug coverage of code
		if (defined('XDEBUGCOVERAGE'))
		{
			print_r(xdebug_get_code_coverage());
		}
	}

	// If there is some logs in buffer to show
	if (count($conf->logbuffer))
	{
		print "\n";
		print "<!-- Start of log output\n";
		//print '<div class="hidden">'."\n";
		foreach($conf->logbuffer as $logline)
		{
			print $logline."<br>\n";
		}
		//print '</div>'."\n";
		print "End of log output -->\n";
	}

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printCommonFooter',$parameters);    // Note that $action and $object may have been modified by some hooks
}

/**
 * Split a string with 2 keys into key array.
 * For example: "A=1;B=2;C=2" is exploded into array('A'=>1,'B'=>2,'C'=>3)
 *
 * @param 	string	$string		String to explode
 * @param 	string	$delimiter	Delimiter between each couple of data
 * @param 	string	$kv			Delimiter between key and value
 * @return	array				Array of data exploded
 */
function dolExplodeIntoArray($string, $delimiter = ';', $kv = '=')
{
	if ($a = explode($delimiter, $string))
	{
		foreach ($a as $s) { // each part
			if ($s) {
				if ($pos = strpos($s, $kv)) { // key/value delimiter
					$ka[trim(substr($s, 0, $pos))] = trim(substr($s, $pos + strlen($kv)));
				} else { // key delimiter not found
					$ka[] = trim($s);
				}
			}
		}
		return $ka;
	}
	return array();
}


/**
 * Set focus onto field with selector
 *
 * @param 	string	$selector	Selector ('#id')
 * @return	string				HTML code to set focus
 */
function dol_set_focus($selector)
{
	print '<!-- Set focus onto a specific field -->'."\n";
	print '<script type="text/javascript" language="javascript">jQuery(document).ready(function() { jQuery("'.$selector.'").focus(); });</script>'."\n";
}


/**
 * Return getmypid() or random PID when function is disabled
 * Some web hosts disable this php function for security reasons
 * and sometimes we can't redeclare function
 *
 * @return	int
 */
function dol_getmypid()
{
    if (! function_exists('getmypid')) {
        return mt_rand(1,32768);
    } else {
        return getmypid();
    }
}


/**
 * Generate natural SQL search string for a criteria (this criteria can be tested on one or several fields)
 *
 * @param 	string|string[]	$fields 	String or array of strings, filled with the name of all fields in the SQL query we must check (combined with a OR)
 * @param 	string 			$value 		The value to look for.
 *                          		    If param $mode is 0, can contains several keywords separated with a space or |
 *                                         like "keyword1 keyword2" = We want record field like keyword1 AND field like keyword2
 *                                         or like "keyword1|keyword2" = We want record field like keyword1 OR field like keyword2
 *                             			If param $mode is 1, can contains an operator <, > or = like "<10" or ">=100.5 < 1000"
 *                             			If param $mode is 2, can contains a list of id separated by comma like "1,3,4"
 * @param	integer			$mode		0=value is list of keywords, 1=value is a numeric test (Example ">5.5 <10"), 2=value is a list of id separated with comma (Example '1,3,4')
 * @param	integer			$nofirstand	1=Do not output the first 'AND'
 * @return 	string 			$res 		The statement to append to the SQL query
 */
function natural_search($fields, $value, $mode=0, $nofirstand=0)
{
    global $db,$langs;

    if ($mode == 0)
    {
    	$value=preg_replace('/\*/','%',$value);	// Replace * with %
    }
    if ($mode == 1)
    {
    	$value=preg_replace('/([<>=]+)\s+([0-9'.preg_quote($langs->trans("DecimalSeparator"),'/').'\-])/','\1\2',$value);	// Clean string '< 10' into '<10' so we can the explode on space to get all tests to do
    }
    
    $value = preg_replace('/\s*\|\s*/','|', $value);
    
    $crits = explode(' ', $value);
    $res = '';
    if (! is_array($fields)) $fields = array($fields);

    $nboffields = count($fields);
    $end2 = count($crits);
    $j = 0;
    foreach ($crits as $crit)
    {
        $i = 0; $i2 = 0;
        $newres = '';
        foreach ($fields as $field)
        {
            if ($mode == 1)
            {
            	$operator='=';
            	$newcrit = preg_replace('/([<>=]+)/','',trim($crit));

            	preg_match('/([<>=]+)/',trim($crit), $reg);
            	if ($reg[1])
            	{
            		$operator = $reg[1];
            	}
            	if ($newcrit != '')
            	{
            		$numnewcrit = price2num($newcrit);
            		if (is_numeric($numnewcrit))
            		{
            			$newres .= ($i2 > 0 ? ' OR ' : '') . $field . ' '.$operator.' '.$numnewcrit;
            		}
            		else
            		{
            			$newres .= ($i2 > 0 ? ' OR ' : '') . '1 = 2';	// force false
            		}
            		$i2++;	// a criteria was added to string
            	}
            }
            else if ($mode == 2)
            {
				$newres .= ($i2 > 0 ? ' OR ' : '') . $field . " IN (" . $db->escape(trim($crit)) . ")";
            	$i2++;	// a criteria was added to string
            }
            else    // $mode=0
			{
				$textcrit = '';
				$tmpcrits = explode('|',$crit);
				$i3 = 0;
				foreach($tmpcrits as $tmpcrit)
				{
	            	$newres .= (($i2 > 0 || $i3 > 0) ? ' OR ' : '') . $field . " LIKE '";

	            	$tmpcrit=trim($tmpcrit);
	            	$tmpcrit2=$tmpcrit;
	            	$tmpbefore='%'; $tmpafter='%';
	            	if (preg_match('/^[\^\$]/', $tmpcrit)) 
	            	{ 
	            	    $tmpbefore='';
	            	    $tmpcrit2 = preg_replace('/^[\^\$]/', '', $tmpcrit2); 
	            	}
					if (preg_match('/[\^\$]$/', $tmpcrit)) 
	            	{ 
	            	    $tmpafter='';
	            	    $tmpcrit2 = preg_replace('/[\^\$]$/', '', $tmpcrit2); 
	            	}
	            	$newres .= $tmpbefore;
	            	$newres .= $db->escape($tmpcrit2);
	            	$newres .= $tmpafter;
	            	$newres .= "'";
	            	if (empty($tmpcrit2))
	            	{
	            	    $newres .= ' OR ' . $field . " IS NULL";
	            	}
	            	$i3++;
				}
				$i2++;	// a criteria was added to string
            }
            $i++;
        }
        if ($newres) $res = $res . ($res ? ' AND ' : '') . ($i2 > 1 ? '(' : '') .$newres . ($i2 > 1 ? ')' : '');
        $j++;
    }
    $res = ($nofirstand?"":" AND ")."(" . $res . ")";
    //print 'xx'.$res.'yy';
    return $res;
}

/**
 * Return the filename of file to get the thumbs
 *
 * @param   string  $file           Original filename (full or relative path)
 * @param   string  $extName        Extension to differenciate thumb file name ('', '_small', '_mini')
 * @param   string  $extImgTarget   Force image extension for thumbs. Use '' to keep same extension than original image (default).
 * @return  string                  New file name (full or relative path, including the thumbs/)
 */
function getImageFileNameForSize($file, $extName, $extImgTarget='')
{
	$dirName = dirname($file);
	if ($dirName == '.') $dirName='';

    $fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$file);	// We remove extension, whatever is its case
	$fileName = basename($fileName);

	if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.jpg$/i',$file)?'.jpg':'');
    if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.jpeg$/i',$file)?'.jpeg':'');
    if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.gif$/i',$file)?'.gif':'');
    if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.png$/i',$file)?'.png':'');
    if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.bmp$/i',$file)?'.bmp':'');

    if (! $extImgTarget) return $file;

    $subdir='';
    if ($extName) $subdir = 'thumbs/';

    return ($dirName?$dirName.'/':'').$subdir.$fileName.$extName.$extImgTarget; // New filename for thumb
}


/**
 * Return URL we can use for advanced preview links
 *
 * @param   string    $modulepart     propal, facture, facture_fourn, ...
 * @param   string    $relativepath   Relative path of docs
 * @return  string                    Output string with HTML
 */
function getAdvancedPreviewUrl($modulepart, $relativepath)
{
    global $conf;
    
    if (empty($conf->use_javascript_ajax)) return '';

    $mime_preview = array('bmp', 'jpeg', 'png', 'gif', 'tiff', 'pdf', 'plain', 'css');
    //$mime_preview[]='vnd.oasis.opendocument.presentation';
    //$mime_preview[]='archive';
    $num_mime = array_search(dol_mimetype($relativepath, '', 1), $mime_preview);

    if ($num_mime !== false) return 'javascript:document_preview(\''.dol_escape_js(DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&amp;attachment=0&amp;file='.$relativepath).'\', \''.dol_mimetype($relativepath).'\', \''.dol_escape_js('Preview').'\')';
    else return '';
}


/**
 *	Return mime type of a file
 *
 *	@param	string	$file		Filename we looking for MIME type
 *  @param  string	$default    Default mime type if extension not found in known list
 * 	@param	int		$mode    	0=Return full mime, 1=otherwise short mime string, 2=image for mime type, 3=source language
 *	@return string 		    	Return a mime type family (text/xxx, application/xxx, image/xxx, audio, video, archive)
 *  @see    image_format_supported (images.lib.php)
 */
function dol_mimetype($file,$default='application/octet-stream',$mode=0)
{
    $mime=$default;
    $imgmime='other.png';
    $srclang='';

    $tmpfile=preg_replace('/\.noexe$/','',$file);

    // Text files
    if (preg_match('/\.txt$/i',$tmpfile))         			   { $mime='text/plain'; $imgmime='text.png'; }
    if (preg_match('/\.rtx$/i',$tmpfile))                      { $mime='text/richtext'; $imgmime='text.png'; }
    if (preg_match('/\.csv$/i',$tmpfile))					   { $mime='text/csv'; $imgmime='text.png'; }
    if (preg_match('/\.tsv$/i',$tmpfile))					   { $mime='text/tab-separated-values'; $imgmime='text.png'; }
    if (preg_match('/\.(cf|conf|log)$/i',$tmpfile))            { $mime='text/plain'; $imgmime='text.png'; }
    if (preg_match('/\.ini$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='ini'; }
    if (preg_match('/\.css$/i',$tmpfile))                      { $mime='text/css'; $imgmime='css.png'; $srclang='css'; }
    // Certificate files
    if (preg_match('/\.(crt|cer|key|pub)$/i',$tmpfile))        { $mime='text/plain'; $imgmime='text.png'; }
    // HTML/XML
    if (preg_match('/\.(html|htm|shtml)$/i',$tmpfile))         { $mime='text/html'; $imgmime='html.png'; $srclang='html'; }
    if (preg_match('/\.(xml|xhtml)$/i',$tmpfile))              { $mime='text/xml'; $imgmime='other.png'; $srclang='xml'; }
    // Languages
    if (preg_match('/\.bas$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='bas'; }
    if (preg_match('/\.(c)$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='c'; }
    if (preg_match('/\.(cpp)$/i',$tmpfile))                    { $mime='text/plain'; $imgmime='text.png'; $srclang='cpp'; }
    if (preg_match('/\.(h)$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='h'; }
    if (preg_match('/\.(java|jsp)$/i',$tmpfile))               { $mime='text/plain'; $imgmime='text.png'; $srclang='java'; }
    if (preg_match('/\.php([0-9]{1})?$/i',$tmpfile))           { $mime='text/plain'; $imgmime='php.png'; $srclang='php'; }
    if (preg_match('/\.phtml$/i',$tmpfile))                    { $mime='text/plain'; $imgmime='php.png'; $srclang='php'; }
    if (preg_match('/\.(pl|pm)$/i',$tmpfile))                  { $mime='text/plain'; $imgmime='pl.png'; $srclang='perl'; }
    if (preg_match('/\.sql$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='sql'; }
    if (preg_match('/\.js$/i',$tmpfile))                       { $mime='text/x-javascript'; $imgmime='jscript.png'; $srclang='js'; }
    // Open office
    if (preg_match('/\.odp$/i',$tmpfile))                      { $mime='application/vnd.oasis.opendocument.presentation'; $imgmime='ooffice.png'; }
    if (preg_match('/\.ods$/i',$tmpfile))                      { $mime='application/vnd.oasis.opendocument.spreadsheet'; $imgmime='ooffice.png'; }
    if (preg_match('/\.odt$/i',$tmpfile))                      { $mime='application/vnd.oasis.opendocument.text'; $imgmime='ooffice.png'; }
    // MS Office
    if (preg_match('/\.mdb$/i',$tmpfile))					   { $mime='application/msaccess'; $imgmime='mdb.png'; }
    if (preg_match('/\.doc(x|m)?$/i',$tmpfile))				   { $mime='application/msword'; $imgmime='doc.png'; }
    if (preg_match('/\.dot(x|m)?$/i',$tmpfile))				   { $mime='application/msword'; $imgmime='doc.png'; }
    if (preg_match('/\.xlt(x)?$/i',$tmpfile))				   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
    if (preg_match('/\.xla(m)?$/i',$tmpfile))				   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
    if (preg_match('/\.xls$/i',$tmpfile))			           { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
    if (preg_match('/\.xls(b|m|x)$/i',$tmpfile))			   { $mime='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; $imgmime='xls.png'; }
    if (preg_match('/\.pps(m|x)?$/i',$tmpfile))				   { $mime='application/vnd.ms-powerpoint'; $imgmime='ppt.png'; }
    if (preg_match('/\.ppt(m|x)?$/i',$tmpfile))				   { $mime='application/x-mspowerpoint'; $imgmime='ppt.png'; }
    // Other
    if (preg_match('/\.pdf$/i',$tmpfile))                      { $mime='application/pdf'; $imgmime='pdf.png'; }
    // Scripts
    if (preg_match('/\.bat$/i',$tmpfile))                      { $mime='text/x-bat'; $imgmime='script.png'; $srclang='dos'; }
    if (preg_match('/\.sh$/i',$tmpfile))                       { $mime='text/x-sh'; $imgmime='script.png'; $srclang='bash'; }
    if (preg_match('/\.ksh$/i',$tmpfile))                      { $mime='text/x-ksh'; $imgmime='script.png'; $srclang='bash'; }
    if (preg_match('/\.bash$/i',$tmpfile))                     { $mime='text/x-bash'; $imgmime='script.png'; $srclang='bash'; }
    // Images
    if (preg_match('/\.ico$/i',$tmpfile))                      { $mime='image/x-icon'; $imgmime='image.png'; }
    if (preg_match('/\.(jpg|jpeg)$/i',$tmpfile))			   { $mime='image/jpeg'; $imgmime='image.png'; }
    if (preg_match('/\.png$/i',$tmpfile))					   { $mime='image/png'; $imgmime='image.png'; }
    if (preg_match('/\.gif$/i',$tmpfile))					   { $mime='image/gif'; $imgmime='image.png'; }
    if (preg_match('/\.bmp$/i',$tmpfile))					   { $mime='image/bmp'; $imgmime='image.png'; }
    if (preg_match('/\.(tif|tiff)$/i',$tmpfile))			   { $mime='image/tiff'; $imgmime='image.png'; }
    // Calendar
    if (preg_match('/\.vcs$/i',$tmpfile))					   { $mime='text/calendar'; $imgmime='other.png'; }
    if (preg_match('/\.ics$/i',$tmpfile))					   { $mime='text/calendar'; $imgmime='other.png'; }
    // Other
    if (preg_match('/\.torrent$/i',$tmpfile))				   { $mime='application/x-bittorrent'; $imgmime='other.png'; }
    // Audio
    if (preg_match('/\.(mp3|ogg|au|wav|wma|mid)$/i',$tmpfile)) { $mime='audio'; $imgmime='audio.png'; }
    // Video
    if (preg_match('/\.ogv$/i',$tmpfile))                      { $mime='video/ogg'; $imgmime='video.png'; }
    if (preg_match('/\.webm$/i',$tmpfile))                     { $mime='video/webm'; $imgmime='video.png'; }
    if (preg_match('/\.avi$/i',$tmpfile))                      { $mime='video/x-msvideo'; $imgmime='video.png'; }
    if (preg_match('/\.divx$/i',$tmpfile))                     { $mime='video/divx'; $imgmime='video.png'; }
    if (preg_match('/\.xvid$/i',$tmpfile))                     { $mime='video/xvid'; $imgmime='video.png'; }
    if (preg_match('/\.(wmv|mpg|mpeg)$/i',$tmpfile))           { $mime='video'; $imgmime='video.png'; }
    // Archive
    if (preg_match('/\.(zip|rar|gz|tgz|z|cab|bz2|7z|tar|lzh)$/i',$tmpfile))   { $mime='archive'; $imgmime='archive.png'; }    // application/xxx where zzz is zip, ...
    // Exe
    if (preg_match('/\.(exe|com)$/i',$tmpfile))                { $mime='application/octet-stream'; $imgmime='other.png'; }
    // Lib
    if (preg_match('/\.(dll|lib|o|so|a)$/i',$tmpfile))         { $mime='library'; $imgmime='library.png'; }
    // Err
    if (preg_match('/\.err$/i',$tmpfile))                      { $mime='error'; $imgmime='error.png'; }

    // Return string
    if ($mode == 1)
    {
        $tmp=explode('/',$mime);
        return (! empty($tmp[1])?$tmp[1]:$tmp[0]);
    }
    if ($mode == 2)
    {
        return $imgmime;
    }
    if ($mode == 3)
    {
        return $srclang;
    }

    return $mime;
}

