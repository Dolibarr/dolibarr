<?php
/* Copyright (C) 2000-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */


if (! function_exists('json_encode'))
{
    /**
     * Implement json_encode for PHP that does not support it
     *
     * @param	mixed	$elements		PHP Object to json encode
     * @return 	string					Json encoded string
     */
    function json_encode($elements)
    {
    	$num = count($elements);

    	// determine type
    	if (is_numeric(key($elements)))
    	{
    		// indexed (list)
    		$output = '[';
    		for ($i = 0, $last = ($num - 1); isset($elements[$i]); ++$i)
    		{
    			if (is_array($elements[$i])) $output.= json_encode($elements[$i]);
    			else $output .= _val($elements[$i]);
    			if($i !== $last) $output.= ',';
    		}
    		$output.= ']';
    	}
    	else
    	{
    		// associative (object)
    		$output = '{';
    		$last = $num - 1;
    		$i = 0;
    		foreach($elements as $key => $value)
    		{
    			$output .= '"'.$key.'":';
    			if (is_array($value)) $output.= json_encode($value);
    			else $output .= _val($value);
    			if ($i !== $last) $output.= ',';
    			++$i;
    		}
    		$output.= '}';
    	}

    	// return
    	return $output;
    }

    /**
     * Return text according to type
     *
     * @param 	mixed	$val	Value to show
     * @return	string			Formated value
     */
    function _val($val)
    {
    	if (is_string($val)) return '"'.rawurlencode($val).'"';
    	elseif (is_int($val)) return sprintf('%d', $val);
    	elseif (is_float($val)) return sprintf('%F', $val);
    	elseif (is_bool($val)) return ($val ? 'true' : 'false');
    	else  return 'null';
    }
}

if (! function_exists('json_decode'))
{
	/**
	 * Implement json_decode for PHP that does not support it
	 *
	 * @param	string	$json		Json encoded to PHP Object or Array
	 * @param	bool	$assoc		False return an object, true return an array
	 * @return 	mixed				Object or Array
	 */
	function json_decode($json, $assoc=false)
	{
		$comment = false;

		$strLength = dol_strlen($json);
		for ($i=0; $i<$strLength; $i++)
		{
			if (! $comment)
			{
				if (($json[$i] == '{') || ($json[$i] == '[')) $out.= 'array(';
				else if (($json[$i] == '}') || ($json[$i] == ']')) $out.= ')';
				else if ($json[$i] == ':') $out.= ' => ';
				else $out.= $json[$i];
			}
			else $out.= $json[$i];
			if ($json[$i] == '"' && $json[($i-1)]!="\\") $comment = !$comment;
		}

		// Return an array
		eval('$array = '.$out.';');

		// Return an object
		if (! $assoc)
		{
			if (! empty($array))
			{
				$object = false;

				foreach ($array as $key => $value)
				{
					$object->{$key} = $value;
				}

				return $object;
			}

			return false;
		}

		return $array;
	}
}

/**
 * Function to return value of a static property when class
 * name is dynamically defined (not hard coded).
 * This is because $myclass::$myvar works from PHP 5.3.0+ only
 *
 * @param	string 	$class		Class name
 * @param 	string 	$member		Name of property
 * @return 	string				Return value of static property.
 */
function getStaticMember($class, $member)
{
	if (is_object($class)) $class = get_class($class);
	$classObj = new ReflectionClass($class);
	$result = null;

	foreach($classObj->getStaticProperties() as $prop => $value)
	{
		if($prop == $member)
		{
			$result = $value;
			break;
		}
	}

	return $result;
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
    require_once(DOL_DOCUMENT_ROOT ."/core/db/".$type.".class.php");

    $class='DoliDB'.ucfirst($type);
    $dolidb=new $class($type, $host, $user, $pass, $name, $port);
    return $dolidb;
}

/**
 * 	Get entity to use
 *
 * 	@param	string	$element	Current element
 * 	@param	int		$shared		1=Return shared entities
 * 	@return	mixed				Entity id(s) to use
 */
function getEntity($element=false, $shared=false)
{
	global $conf, $mc;

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
 *  Function called at end of web php process
 *
 *  @return	void
 */
function dol_shutdown()
{
    global $conf,$user,$langs,$db;
    $disconnectdone=false; $depth=0;
    if (is_object($db) && ! empty($db->connected)) { $depth=$db->transaction_opened; $disconnectdone=$db->close(); }
    dol_syslog("--- End access to ".$_SERVER["PHP_SELF"].($disconnectdone?' (Warn: db disconnection forced, transaction depth was '.$depth.')':''), ($disconnectdone?LOG_WARNING:LOG_DEBUG));
}


/**
 *  Return value of a param into GET or POST supervariable
 *
 *  @param	string	$paramname   Name of parameter to found
 *  @param	string	$check	     Type of check (''=no check,  'int'=check it's numeric, 'alpha'=check it's alpha only)
 *  @param	int		$method	     Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get)
 *  @return string      		 Value found or '' if check fails
 */
function GETPOST($paramname,$check='',$method=0)
{
	if (empty($method)) $out = isset($_GET[$paramname])?$_GET[$paramname]:(isset($_POST[$paramname])?$_POST[$paramname]:'');
	elseif ($method==1) $out = isset($_GET[$paramname])?$_GET[$paramname]:'';
	elseif ($method==2) $out = isset($_POST[$paramname])?$_POST[$paramname]:'';
	elseif ($method==3) $out = isset($_POST[$paramname])?$_POST[$paramname]:(isset($_GET[$paramname])?$_GET[$paramname]:'');

	if (! empty($check))
	{
		// Check if numeric
		if ($check == 'int' && ! preg_match('/^[-\.,0-9]+$/i',trim($out))) $out='';
		// Check if alpha
		//if ($check == 'alpha' && ! preg_match('/^[ =:@#\/\\\(\)\-\._a-z0-9]+$/i',trim($out))) $out='';
		// '"' is dangerous because param in url can close the href= or src= and add javascript functions.
		if ($check == 'alpha' && preg_match('/"/',trim($out))) $out='';
	}

	return $out;
}


/**
 *  Return a prefix to use for this Dolibarr instance for session or cookie names.
 *  This prefix is unique for instance and avoid conflict between multi-instances,
 *  even when having two instances with one root dir or two instances in virtual servers
 *
 *  @return	string      		A calculated prefix
 */
function dol_getprefix()
{
    return dol_hash($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT);
}

/**
 *	Make an include_once using default root and alternate root if it fails.
 *	WARNING: In most cases, you should not use this function:
 *  To link to a core file, use include(DOL_DOCUMENT_ROOT.'/pathtofile')
 *  To link to a module file from a module file, use include('./mymodulefile');
 *  To link to a module file from a core file, then this function can be used
 *
 * 	@param	string	$relpath	Relative path to file (Ie: mydir/myfile, ../myfile, ...)
 *  @return int					false if include fails.
 */
function dol_include_once($relpath)
{
    global $conf,$langs,$user,$mysoc;   // Other global var must be retreived with $GLOBALS['var']
    return @include_once(dol_buildpath($relpath));
}


/**
 *	Return path of url or filesystem. Return default_root or alternate root if file_exist fails
 *
 * 	@param	string	$path		Relative path to file (if mode=0, ie: mydir/myfile, ../myfile, ...) or relative url (if mode=1).
 *  @param	int		$type		0=Used for a Filesystem path, 1=Used for an URL path (output relative), 2=Used for an URL path (output full path)
 *  @return string				Full filsystem path (if mode=0), Full url path (if mode=1)
 */
function dol_buildpath($path,$type=0)
{
    if (empty($type))	// For a filesystem path
    {
        $res = DOL_DOCUMENT_ROOT.$path;	// Standard value
        if (defined('DOL_DOCUMENT_ROOT_ALT') && DOL_DOCUMENT_ROOT_ALT)	// We check only if alternate feature is used
        {
            if (! file_exists(DOL_DOCUMENT_ROOT.$path)) $res = DOL_DOCUMENT_ROOT_ALT.$path;
        }
    }
    else				// For an url path
    {
        // We try to get local path of file on filesystem from url
        // Note that trying to know if a file on disk exist by forging path on disk from url
        // works only for some web server and some setup. This is bugged when
        // using proxy, rewriting, virtual path, etc...
        if ($type == 1)
        {
            $res = DOL_URL_ROOT.$path;		// Standard value
            if (defined('DOL_URL_ROOT_ALT') && DOL_URL_ROOT_ALT)			// We check only if alternate feature is used
            {
                preg_match('/^([^\?]+(\.css\.php|\.css|\.js\.php|\.js|\.png|\.jpg|\.php)?)/i',$path,$regs);    // Take part before '?'
                if (! empty($regs[1]))
                {
                    if (! file_exists(DOL_DOCUMENT_ROOT.$regs[1])) $res = DOL_URL_ROOT_ALT.$path;
                }
            }
        }
        if ($type == 2)
        {
            $res = DOL_MAIN_URL_ROOT.$path;      // Standard value
            if (defined('DOL_URL_ROOT_ALT') && DOL_URL_ROOT_ALT)            // We check only if alternate feature is used
            {
                preg_match('/^([^\?]+(\.css\.php|\.css|\.js\.php|\.js|\.png|\.jpg|\.php)?)/i',$path,$regs);    // Take part before '?'
                if (! empty($regs[1]))
                {
                    if (! file_exists(DOL_DOCUMENT_ROOT.$regs[1])) $res = DOL_MAIN_URL_ROOT_ALT.$path;
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
 */
function dol_clone($object)
{
    dol_syslog("Functions.lib::dol_clone Clone object");

    // We create dynamically a clone function, making a =
    if (version_compare(phpversion(), '5.0') < 0 && ! function_exists('clone'))
    {
        eval('function clone($object){return($object);}');
    }
    $myclone=clone($object);
    return $myclone;
}

/**
 *	Optimize a size for some browsers (phone, smarphone, ...)
 *
 * 	@param	int		$size		Size we want
 * 	@param	string	$type		Type of optimizing:
 * 										'' = function used to define a size for truncation
 * 										'width' = function is used to define a width
 *	@return int					New size after optimizing
 */
function dol_size($size,$type='')
{
    global $conf;
    if (empty($conf->browser->phone)) return $size;
    if ($type == 'width' && $size > 250) return 250;
    else return 10;
}


/**
 *	Clean a string to use it as a file name
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace bad chars with
 *	@return string          		String cleaned (a-zA-Z_)
 *
 * 	@see        	dol_string_nospecial, dol_string_unaccent
 */
function dol_sanitizeFileName($str,$newstr='_')
{
	$filesystem_forbidden_chars = array('<','>',':','/','\\','?','*','|','"');
    return dol_string_nospecial(dol_string_unaccent($str), $newstr, $filesystem_forbidden_chars);
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
        $string = rawurlencode($str);
        $replacements = array(
		'%C3%80' => 'A','%C3%81' => 'A',
		'%C3%88' => 'E','%C3%89' => 'E',
		'%C3%8C' => 'I','%C3%8D' => 'I',
		'%C3%92' => 'O','%C3%93' => 'O',
		'%C3%99' => 'U','%C3%9A' => 'U',
		'%C3%A0' => 'a','%C3%A1' => 'a','%C3%A2' => 'a',
		'%C3%A8' => 'e','%C3%A9' => 'e','%C3%AA' => 'e','%C3%AB' => 'e',
		'%C3%AC' => 'i','%C3%AD' => 'i','%C3%AE' => 'i',
		'%C3%B2' => 'o','%C3%B3' => 'o',
		'%C3%B9' => 'u','%C3%BA' => 'u'
		);
		$string=strtr($string, $replacements);
		return rawurldecode($string);
    }
    else
    {
        $string = strtr(
            $str,
			"\xC0\xC1\xC2\xC3\xC5\xC7
	        \xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
	        \xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD
	        \xE0\xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB
	        \xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8
	        \xF9\xFA\xFB\xFD\xFF",
			"AAAAAC
	        EEEEIIIIDN
	        OOOOOUUUY
	        aaaaaceeee
	        iiiidnooooo
	        uuuyy"
        );
        $string = strtr($string, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH", "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));
        return $string;
    }
}

/**
 *	Clean a string from all punctuation characters to use it as a ref or login
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace forbidden chars with
 *  @param  array	$badchars       List of forbidden characters
 * 	@return string          		Cleaned string
 *
 * 	@see    		dol_sanitizeFilename, dol_string_unaccent
 */
function dol_string_nospecial($str,$newstr='_',$badchars='')
{
    $forbidden_chars_to_replace=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
    $forbidden_chars_to_remove=array();
    if (is_array($badchars)) $forbidden_chars_to_replace=$badchars;
    //$forbidden_chars_to_remove=array("(",")");

    return str_replace($forbidden_chars_to_replace,$newstr,str_replace($forbidden_chars_to_remove,"",$str));
}

/**
 *  Returns text escaped for inclusion into javascript code
 *
 *  @param       string		$stringtoescape		String to escape
 *  @return      string     		 			Escaped string
 */
function dol_escape_js($stringtoescape)
{
    // escape quotes and backslashes, newlines, etc.
    $substitjs=array("&#039;"=>"\\'",'\\'=>'\\\\',"'"=>"\\'",'"'=>"\\'","\r"=>'\\r',"\n"=>'\\n','</'=>'<\/');
    return strtr($stringtoescape, $substitjs);
}


/**
 *  Returns text escaped for inclusion in HTML alt or title tags
 *
 *  @param      string		$stringtoescape		String to escape
 *  @param		int			$keepb				Do not clean b tags
 *  @return     string     				 		Escaped string
 */
function dol_escape_htmltag($stringtoescape,$keepb=0)
{
    // escape quotes and backslashes, newlines, etc.
    $tmp=dol_html_entity_decode($stringtoescape,ENT_COMPAT,'UTF-8');
    if ($keepb) $tmp=strtr($tmp, array('"'=>'',"\r"=>'\\r',"\n"=>'\\n'));
    else $tmp=strtr($tmp, array('"'=>'',"\r"=>'\\r',"\n"=>'\\n',"<b>"=>'','</b>'=>''));
    return dol_htmlentities($tmp,ENT_COMPAT,'UTF-8');
}

/**
 *	Write log message into outputs. Possible outputs can be:
 *	A file if SYSLOG_FILE_ON defined:   	file name is then defined by SYSLOG_FILE
 *	Syslog if SYSLOG_SYSLOG_ON defined:    	facility is then defined by SYSLOG_FACILITY
 * 	Warning, syslog functions are bugged on Windows, generating memory protection faults. To solve
 *	this, use logging to files instead of syslog (see setup of module).
 *	Note: If SYSLOG_FILE_NO_ERROR defined, we never output any error message when writing to log fails.
 *  Note: You can get log message into html sources by adding parameter &logtohtml=1 (constant MAIN_LOGTOHTML must be set)
 *
 *	This function works only if syslog module is enabled.
 * 	This must not use any call to other function calling dol_syslog (avoid infinite loop).
 *
 * 	@param  string		$message	Line to log. Ne doit pas etre traduit si level = LOG_ERR
 *	@param  int			$level		Log level
 *									On Windows LOG_ERR=4, LOG_WARNING=5, LOG_NOTICE=LOG_INFO=6, LOG_DEBUG=6 si define_syslog_variables ou PHP 5.3+, 7 si dolibarr
 *									On Linux   LOG_ERR=3, LOG_WARNING=4, LOG_INFO=6, LOG_DEBUG=7
 *	@return	void
 */
function dol_syslog($message, $level=LOG_INFO)
{
    global $conf,$user,$langs,$_REQUEST;

    // If adding log inside HTML page is required
    if (! empty($_REQUEST['logtohtml']) && ! empty($conf->global->MAIN_LOGTOHTML))
    {
        $conf->logbuffer[]=dol_print_date(time(),"%Y-%m-%d %H:%M:%S")." ".$message;
    }

    // If syslog module enabled
    if (! empty($conf->syslog->enabled))
    {
        //print $level.' - '.$conf->global->SYSLOG_LEVEL.' - '.$conf->syslog->enabled." \n";
        if ($level > $conf->global->SYSLOG_LEVEL) return;

        // Translate error message if this is an error message (rare) and langs is loaded
        if ($level == LOG_ERR)
        {
            if (is_object($langs))
            {
                $langs->load("errors");
                if ($message != $langs->trans($message)) $message = $langs->trans($message);
            }
        }

        // Add page/script name to log message
        $script=isset($_SERVER['PHP_SELF'])?basename($_SERVER['PHP_SELF'],'.php').' ':'';
        $message=$script.$message;

        // Add user to log message
        $login='nologin';
        if (is_object($user) && $user->id) $login=$user->login;
        $message=sprintf("%-8s",$login)." ".$message;

        // Check if log is to a file (SYSLOG_FILE_ON defined)
        if (defined("SYSLOG_FILE_ON") && constant("SYSLOG_FILE_ON"))
        {
            $filelog=SYSLOG_FILE;
            $filelog=preg_replace('/DOL_DATA_ROOT/i',DOL_DATA_ROOT,$filelog);
            //print "filelog=".$filelog."\n";
            if (defined("SYSLOG_FILE_NO_ERROR")) $file=@fopen($filelog,"a+");
            else $file=fopen($filelog,"a+");

            if ($file)
            {
                $ip='???';	// $ip contains information to identify computer that run the code
                if (! empty($_SERVER["REMOTE_ADDR"])) $ip=$_SERVER["REMOTE_ADDR"];			// In most cases.
                else if (! empty($_SERVER['SERVER_ADDR'])) $ip=$_SERVER['SERVER_ADDR'];		// This is when PHP session is ran inside a web server but not inside a client request (example: init code of apache)
                else if (! empty($_SERVER['COMPUTERNAME'])) $ip=$_SERVER['COMPUTERNAME'].(empty($_SERVER['USERNAME'])?'':'@'.$_SERVER['USERNAME']);	// This is when PHP session is ran outside a web server, like from Windows command line (Not always defined, but usefull if OS defined it).
                else if (! empty($_SERVER['LOGNAME'])) $ip='???@'.$_SERVER['LOGNAME'];	// This is when PHP session is ran outside a web server, like from Linux command line (Not always defined, but usefull if OS defined it).

                $liblevelarray=array(LOG_ERR=>'ERROR',LOG_WARNING=>'WARN',LOG_INFO=>'INFO',LOG_DEBUG=>'DEBUG');
                $liblevel=$liblevelarray[$level];
                if (! $liblevel) $liblevel='UNDEF';

                $message=dol_print_date(time(),"%Y-%m-%d %H:%M:%S")." ".sprintf("%-5s",$liblevel)." ".sprintf("%-15s",$ip)." ".$message;

                fwrite($file,$message."\n");
                fclose($file);
                // This is for log file, we do not change permissions

                // If enable html log tag enabled and url parameter log defined, we show output log on HTML comments
                if (! empty($conf->global->MAIN_ENABLE_LOG_HTML) && ! empty($_GET["log"]))
                {
                    print "\n\n<!-- Log start\n";
                    print $message."\n";
                    print "Log end -->\n";
                }
            }
            elseif (! defined("SYSLOG_FILE_NO_ERROR"))
            {
                // Do not use here a call to functions that make call to dol_syslog so making call to langs. A simple print is enough.
                print "Error, failed to open file ".$filelog."\n";
            }
        }

        // Check if log is to syslog (SYSLOG_SYSLOG_ON defined)
        if (defined("SYSLOG_SYSLOG_ON") && constant("SYSLOG_SYSLOG_ON"))
        {
            if (function_exists('openlog'))	// This function does not exists on some ISP (Ex: Free in France)
            {
                $facility = LOG_USER;
                if (defined("SYSLOG_FACILITY") && constant("SYSLOG_FACILITY"))
                {
                    // Exemple: SYSLOG_FACILITY vaut LOG_USER qui vaut 8. On a besoin de 8 dans $facility.
                    $facility = constant("SYSLOG_FACILITY");
                }

                openlog("dolibarr", LOG_PID | LOG_PERROR, (int) $facility);		// (int) is required to avoid error parameter 3 expected to be long
                if (! $level) syslog(LOG_ERR, $message);
                else          syslog($level, $message);
                closelog();
            }
        }

        // Check if log is to syslog (SYSLOG_FIREPHP_ON defined)
        if (defined("SYSLOG_FIREPHP_ON") && constant("SYSLOG_FIREPHP_ON") && ! empty($_SERVER["SERVER_NAME"]))     //! empty($_SERVER["SERVER_NAME"]) to be sure to enable this in Web mode only
        {
            try
            {
                // Warning FirePHPCore must be into PHP include path. It is not possible to use into require_once() a constant from
                // database or config file because we must be able to log data before database or config file read.
			    $oldinclude=get_include_path();
                set_include_path('/usr/share/php/');
                include_once('FirePHPCore/FirePHP.class.php');
                set_include_path($oldinclude);
                ob_start();
                $firephp = FirePHP::getInstance(true);
                if ($level == LOG_ERR) $firephp->error($message);
                elseif ($level == LOG_WARNING) $firephp->warn($message);
                elseif ($level == LOG_INFO) $firephp->log($message);
                else $firephp->log($message);
            }
            catch(Exception $e)
            {
                // Do not use dol_syslog to avoid infinite loop
            }
        }
    }
}


/**
 *	Show tab header of a card
 *
 *	@param	array	$links		Array of tabs
 *	@param	string	$active     Active tab name (document', 'info', 'ldap', ....)
 *	@param  string	$title      Title
 *	@param  int		$notab		0=Add tab header, 1=no tab header
 * 	@param	string	$picto		Add a picto on tab title
 * 	@return	void
 */
function dol_fiche_head($links=array(), $active='0', $title='', $notab=0, $picto='')
{
    print dol_get_fiche_head($links, $active, $title, $notab, $picto);
}

/**
 *  Show tab header of a card
 *
 *	@param	array	$links		Array of tabs
 *	@param	int		$active     Active tab name
 *	@param  string	$title      Title
 *	@param  int		$notab		0=Add tab header, 1=no tab header
 * 	@param	string	$picto		Add a picto on tab title
 * 	@return	void
 */
function dol_get_fiche_head($links=array(), $active='0', $title='', $notab=0, $picto='')
{
    $out="\n".'<div class="tabs">'."\n";

    // Affichage titre
    if ($title)
    {
        $limittitle=30;
        $out.='<a class="tabTitle">';
        if ($picto) $out.=img_object('',$picto).' ';
        $out.=dol_trunc($title,$limittitle);
        $out.='</a>';
    }

    // Define max of key (max may be higher than sizeof because of hole due to module disabling some tabs).
    $maxkey=-1;
    if (is_array($links))
    {
        $keys=array_keys($links);
        if (count($keys)) $maxkey=max($keys);
    }

    // Show tabs
    for ($i = 0 ; $i <= $maxkey ; $i++)
    {
        if (isset($links[$i][2]) && $links[$i][2] == 'image')
        {
            if (!empty($links[$i][0]))
            {
                $out.='<a class="tabimage" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
            }
            else
            {
                $out.='<span class="tabspan">'.$links[$i][1].'</span>'."\n";
            }
        }
        else if (! empty($links[$i][1]))
        {
            //print "x $i $active ".$links[$i][2]." z";
            if ((is_numeric($active) && $i == $active)
            || (! is_numeric($active) && $active == $links[$i][2]))
            {
                $out.='<a id="active" class="tab" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
            }
            else
            {
                $out.='<a id="'.$links[$i][2].'" class="tab" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
            }
        }
    }

    $out.="</div>\n";

    if (! $notab) $out.="\n".'<div class="tabBar">'."\n";

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
 *  @return	void
 */
function dol_get_fiche_end($notab=0)
{
    if (! $notab) return "\n</div>\n";
    else return '';
}

/**
 *      Return a formated address (part address/zip/town/state) according to country rules
 *
 *      @param  Object		$object         A company or contact object
 *      @return string          			Formated string
 */
function dol_format_address($object)
{
	$ret='';
	$countriesusingstate=array('US','IN');

	// Address
	$ret .= $object->address;
	// Zip/Town/State
	if (in_array($object->country_code,array('US')))   // US: town, state, zip
	{
		$ret .= ($ret ? "\n" : '' ).$object->town;
		if ($object->state && in_array($object->country_code,$countriesusingstate))
		{
			$ret.=", ".$object->departement;
		}
		if ($object->zip) $ret .= ', '.$object->zip;
	}
	else                                        // Other: zip town, state
	{
		$ret .= ($ret ? "\n" : '' ).$object->zip;
		$ret .= ' '.$object->town;
		if ($object->state && in_array($object->country_code,$countriesusingstate))
		{
			$ret.=", ".$object->state;
		}
	}

	return $ret;
}


/**
 *	Output date in a string format according to outputlangs (or langs if not defined).
 * 	Return charset is always UTF-8, except if encodetoouput is defined. In this case charset is output charset
 *
 *	@param	timestamp	$time        	GM Timestamps date
 *	@param	string		$format      	Output date format
 *										"%d %b %Y",
 *										"%d/%m/%Y %H:%M",
 *										"%d/%m/%Y %H:%M:%S",
 *										"day", "daytext", "dayhour", "dayhourldap", "dayhourtext"
 * 	@param	string		$tzoutput		true=output or 'gmt' => string is for Greenwich location
 * 										false or 'tzserver' => output string is for local PHP server TZ usage
 * 										'tzuser' => output string is for local browser TZ usage
 *	@param	Tranlsate	$outputlangs	Object lang that contains language for text translation.
 *  @param  boolean		$encodetooutput false=no convert into output pagecode
 * 	@return string      				Formated date or '' if time is null
 *
 *  @see        dol_mktime, dol_stringtotime, dol_getdate
 */
function dol_print_date($time,$format='',$tzoutput='tzserver',$outputlangs='',$encodetooutput=false)
{
    global $conf,$langs;

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
                $offsettz=$offsetdst=0;
            }
            elseif ($tzoutput == 'tzuser')
            {
                $to_gmt=true;
                $offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
                $offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
            }
            elseif ($tzoutput == 'tzcompany')
            {
                $to_gmt=false;
                $offsettz=$offsetdst=0;	// TODO Define this and use it later
            }
        }
    }

    if (! is_object($outputlangs)) $outputlangs=$langs;

    // Si format non defini, on prend $conf->format_date_text_short sinon %Y-%m-%d %H:%M:%S
    if (! $format) $format=(isset($conf->format_date_text_short) ? $conf->format_date_text_short : '%Y-%m-%d %H:%M:%S');

    // Change predefined format into computer format. If found translation in lang file we use it, otherwise we use default.
    if ($format == 'day')               $format=($outputlangs->trans("FormatDateShort")!="FormatDateShort"?$outputlangs->trans("FormatDateShort"):$conf->format_date_short);
    if ($format == 'hour')              $format=($outputlangs->trans("FormatHourShort")!="FormatHourShort"?$outputlangs->trans("FormatHourShort"):$conf->format_hour_short);
    if ($format == 'hourduration')      $format=($outputlangs->trans("FormatHourShortDuration")!="FormatHourShortDuration"?$outputlangs->trans("FormatHourShortDuration"):$conf->format_hour_short_duration);
    if ($format == 'daytext')           $format=($outputlangs->trans("FormatDateText")!="FormatDateText"?$outputlangs->trans("FormatDateText"):$conf->format_date_text);
    if ($format == 'daytextshort')      $format=($outputlangs->trans("FormatDateTextShort")!="FormatDateTextShort"?$outputlangs->trans("FormatDateTextShort"):$conf->format_date_text_short);
    if ($format == 'dayhour')           $format=($outputlangs->trans("FormatDateHourShort")!="FormatDateHourShort"?$outputlangs->trans("FormatDateHourShort"):$conf->format_date_hour_short);
    if ($format == 'dayhourtext')       $format=($outputlangs->trans("FormatDateHourText")!="FormatDateHourText"?$outputlangs->trans("FormatDateHourText"):$conf->format_date_hour_text);
    if ($format == 'dayhourtextshort')  $format=($outputlangs->trans("FormatDateHourTextShort")!="FormatDateHourTextShort"?$outputlangs->trans("FormatDateHourTextShort"):$conf->format_date_hour_text_short);

    // Format not sensitive to language
    if ($format == 'dayhourlog')        $format='%Y%m%d%H%M%S';
    if ($format == 'dayhourldap')       $format='%Y%m%d%H%M%SZ';
    if ($format == 'dayhourxcard')      $format='%Y%m%dT%H%M%SZ';
    if ($format == 'dayxcard')          $format='%Y%m%d';
    if ($format == 'dayrfc')            $format='%Y-%m-%d';             // DATE_RFC3339
    if ($format == 'dayhourrfc')        $format='%Y-%m-%dT%H:%M:%SZ';   // DATETIME RFC3339

    // If date undefined or "", we return ""
    if (dol_strlen($time) == 0) return '';		// $time=0 allowed (it means 01/01/1970 00:00:00)

    //print 'x'.$time;

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

    // Analyze date (deprecated)   Ex: 1970-01-01, 1970-01-01 01:00:00, 19700101010000
    if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i',$time,$reg)
    || preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])$/i',$time,$reg))
    {
        // This part of code should not be used.
        dol_syslog("Functions.lib::dol_print_date function call with deprecated value of time in page ".$_SERVER["PHP_SELF"], LOG_WARNING);
        // Date has format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS' or 'YYYYMMDDHHMMSS'
        $syear = $reg[1];
        $smonth = $reg[2];
        $sday = $reg[3];
        $shour = $reg[4];
        $smin = $reg[5];
        $ssec = $reg[6];

        $time=dol_mktime($shour,$smin,$ssec,$smonth,$sday,$syear,true);
        $ret=adodb_strftime($format,$time+$offsettz+$offsetdst,$to_gmt);
    }
    else
    {
        // Date is a timestamps
        if ($time < 100000000000)	// Protection against bad date values
        {
            $ret=adodb_strftime($format,$time+$offsettz+$offsetdst,$to_gmt);
        }
        else $ret='Bad value '.$time.' for date';
    }

    if (preg_match('/__b__/i',$format))
    {
        // Here ret is string in PHP setup language (strftime was used). Now we convert to $outputlangs.
        $month=adodb_strftime('%m',$time+$offsettz+$offsetdst);
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
        $w=adodb_strftime('%w',$time+$offsettz+$offsetdst);
        $dayweek=$outputlangs->transnoentitiesnoconv('Day'.$w);
        $ret=str_replace('__A__',$dayweek,$ret);
        $ret=str_replace('__a__',dol_substr($dayweek,0,3),$ret);
    }

    return $ret;
}


/**
 *	Return an array with locale date info.
 *  PHP getdate is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 *
 *  WARNING: This function always use PHP server timezone to return locale informations.
 *  Usage must be avoid.
 *
 *	@param	timestamp	$timestamp		Timestamp
 *	@param	boolean		$fast			Fast mode
 *	@return	array						Array of informations
 *										If no fast mode:
 *										'seconds' => $secs,
 *										'minutes' => $min,
 *										'hours' => $hour,
 *										'mday' => $day,
 *										'wday' => $dow,
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
 *	@param	int			$gm				1=Input informations are GMT values, otherwise local to server TZ
 *	@param	int			$check			0=No check on parameters (Can use day 32, etc...)
 *	@return	timestamp					Date as a timestamp, '' if error
 * 	@see 								dol_print_date, dol_stringtotime, dol_getdate
 */
function dol_mktime($hour,$minute,$second,$month,$day,$year,$gm=false,$check=1)
{
    global $conf;
    //print "- ".$hour.",".$minute.",".$second.",".$month.",".$day.",".$year.",".$_SERVER["WINDIR"]." -";

    // Clean parameters
    if ($hour   == -1) $hour=0;
    if ($minute == -1) $minute=0;
    if ($second == -1) $second=0;

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

    if (class_exists('DateTime') && ! empty($conf->global->MAIN_NEW_DATE))
    {
        if (empty($gm)) $localtz = new DateTimeZone(date_default_timezone_get());
        else $localtz = new DateTimeZone('UTC');
        $dt = new DateTime(null,$localtz);
        $dt->setDate($year,$month,$day);
        $dt->setTime($hour,$minute,$second);
        $date=$dt->getTimestamp();
    }
    else
    {
        $usealternatemethod=false;
        if ($year <= 1970) $usealternatemethod=true;		// <= 1970
        if ($year >= 2038) $usealternatemethod=true;		// >= 2038

        if ($usealternatemethod || $gm)	// Si time gm, seule adodb peut convertir
        {
            $date=adodb_mktime($hour,$minute,$second,$month,$day,$year,0,$gm);
        }
        else
        {
            $date=mktime($hour,$minute,$second,$month,$day,$year);
        }
    }
    return $date;
}


/**
 *	Return date for now. We should always use this function without parameters (that means GMT time)
 *
 * 	@param	string		$mode	'gmt' => we return GMT timestamp,
 * 								'tzserver' => we add the PHP server timezone
 *  							'tzref' => we add the company timezone
 * 								'tzuser' => we add the user timezone
 *	@return timestamp   $date	Timestamp
 */
function dol_now($mode='gmt')
{
    // Note that gmmktime and mktime return same value (GMT) whithout parameters
    if ($mode == 'gmt') $ret=gmmktime();	// Time for now at greenwich.
    else if ($mode == 'tzserver')			// Time for now with PHP server timezone added
    {
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
        $tzsecond=getServerTimeZoneInt();    // Contains tz+dayling saving time
        $ret=dol_now('gmt')+($tzsecond*3600);
    }
    /*else if ($mode == 'tzref')				// Time for now with parent company timezone is added
    {
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
        $tzsecond=getParentCompanyTimeZoneInt();    // Contains tz+dayling saving time
        $ret=dol_now('gmt')+($tzsecond*3600);
    }*/
    else if ($mode == 'tzuser')				// Time for now with user timezone is added
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
    global $langs;
    $level=1024;

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
 * @return	string					HTML Link
 */
function dol_print_url($url,$target='_blank',$max=32)
{
    if (empty($url)) return '';

    $link='<a href="';
    if (! preg_match('/^http/i',$url)) $link.='http://';
    $link.=$url;
    if ($target) $link.='" target="'.$target.'">';
    if (! preg_match('/^http/i',$url)) $link.='http://';
    $link.=dol_trunc($url,$max);
    $link.='</a>';
    return $link;
}

/**
 * Show EMail link
 *
 * @param	string		$email			EMail to show (only email, without 'Name of recipient' before)
 * @param 	int			$cid 			Id of contact if known
 * @param 	int			$socid 			Id of third party if known
 * @param 	int			$addlink		0=no link to create action
 * @param	int			$max			Max number of characters to show
 * @param	int			$showinvalid	Show warning if syntax email is wrong
 * @return	string						HTML Link
 */
function dol_print_email($email,$cid=0,$socid=0,$addlink=0,$max=64,$showinvalid=1)
{
    global $conf,$user,$langs;

    $newemail=$email;

    if (empty($email)) return '&nbsp;';

    if (! empty($addlink))
    {
        $newemail='<a href="';
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

        if (($cid || $socid) && $conf->agenda->enabled && $user->rights->agenda->myactions->create)
        {
            $type='AC_EMAIL'; $link='';
            if (! empty($conf->global->AGENDA_ADDACTIONFOREMAIL)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;backtopage=1&amp;actioncode='.$type.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
            $newemail='<table class="nobordernopadding"><tr><td>'.$newemail.' </td><td>&nbsp;'.$link.'</td></tr></table>';
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
    return $newemail;
}

/**
 * 	Format phone numbers according to country
 *
 * 	@param	string	$phone 		Phone number to format
 * 	@param 	string	$country 	Country code to use for formatting
 * 	@param 	int		$cid 		Id of contact if known
 * 	@param 	int		$socid 		Id of third party if known
 * 	@param 	int		$addlink	0=no link to create action
 * 	@param 	string	$separ 		separation between numbers for a better visibility example : xx.xx.xx.xx.xx
 * 	@return string 				Formated phone number
 */
function dol_print_phone($phone,$country="FR",$cid=0,$socid=0,$addlink=0,$separ="&nbsp;")
{
    global $conf,$user,$langs;

    // Clean phone parameter
    $phone = preg_replace("/[\s.-]/","",trim($phone));
    if (empty($phone)) { return ''; }

    $newphone=$phone;
    if (strtoupper($country) == "FR")
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

    if (! empty($addlink))
    {
        if ($conf->clicktodial->enabled)
        {
            if (empty($user->clicktodial_loaded)) $user->fetch_clicktodial();

            if (empty($conf->global->CLICKTODIAL_URL)) $urlmask='ErrorClickToDialModuleNotConfigured';
            else $urlmask=$conf->global->CLICKTODIAL_URL;
            // This line is for backward compatibility
            $url = sprintf($urlmask, urlencode($phone), urlencode($user->clicktodial_poste), urlencode($user->clicktodial_login), urlencode($user->clicktodial_password));
            // Thoose lines are for substitution
            $substitarray=array('__PHONEFROM__'=>urlencode($user->clicktodial_poste),
			               		'__PHONETO__'=>urlencode($phone),
						   		'__LOGIN__'=>urlencode($user->clicktodial_login),
			               		'__PASS__'=>urlencode($user->clicktodial_password));
            $url = make_substitutions($url, $substitarray);
            $newphonesav=$newphone;
            $newphone ='<a href="'.$url.'"';
            if (! empty($conf->global->CLICKTODIAL_FORCENEWTARGET)) $newphone.=' target="_blank"';
            $newphone.='>'.$newphonesav.'</a>';
        }

        //if (($cid || $socid) && $conf->agenda->enabled && $user->rights->agenda->myactions->create)
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
        {
            $type='AC_TEL'; $link='';
            if ($addlink == 'AC_FAX') $type='AC_FAX';
            if (! empty($conf->global->AGENDA_ADDACTIONFORPHONE)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;backtopage=1&amp;actioncode='.$type.($cid?'&amp;contactid='.$cid:'').($socid?'&amp;socid='.$socid:'').'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
            $newphone='<table class="nobordernopadding"><tr><td>'.$newphone.' </td><td>&nbsp;'.$link.'</td></tr></table>';
        }
    }

    return $newphone;
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

        include_once(DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php');
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
        include_once(DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php');
        $geoip=new DolGeoIP('country',$datafile);
        $countrycode=$geoip->getCountryCodeFromIP($ip);
        $ret=$countrycode;
    }
    return $ret;
}

/**
 *  Format address string
 *
 *  @param	string	$address     Address
 *  @param  int		$htmlid      Html ID (for example 'gmap')
 *  @param  int		$mode        thirdparty|contact|member|other
 *  @param  int		$id          Id of object
 *  @return void
 */
function dol_print_address($address, $htmlid, $mode, $id)
{
    global $conf,$user,$langs;

    if ($address)
    {
        print nl2br($address);
        $showmap=0;
        if ($mode=='thirdparty' && $conf->google->enabled && $conf->global->GOOGLE_ENABLE_GMAPS) $showmap=1;
        if ($mode=='contact' && $conf->google->enabled && $conf->global->GOOGLE_ENABLE_GMAPS_CONTACTS) $showmap=1;
        if ($mode=='member' && $conf->google->enabled && $conf->global->GOOGLE_ENABLE_GMAPS_MEMBERS) $showmap=1;

        if ($showmap)
        {
            $url=dol_buildpath('/google/gmaps.php?mode='.$mode.'&id='.$id,1);
            /*    print ' <img id="'.$htmlid.'" src="'.DOL_URL_ROOT.'/theme/common/gmap.png">';
             print '<script type="text/javascript">
             $(\'#gmap\').css(\'cursor\',\'pointer\');
             $(\'#gmap\').click(function() {
             $( \'<div>\').dialog({
             modal: true,
             open: function ()
             {
             $(this).load(\''.$url.'\');
             },
             height: 400,
             width: 600,
             title: \'GMap\'
             });
             });
             </script>
             '; */
            print ' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'" border="0" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
        }
    }
}


/**
 *	Return true if email syntax is ok
 *
 *	@param	    string		$address    email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
 *	@return     boolean     			true if email syntax is OK, false if KO or empty string
 */
function isValidEmail($address)
{
    if (preg_match("/.*<(.+)>/i", $address, $regs)) {
        $address = $regs[1];
    }
    // 2 letters domains extensions are for countries
    // 3 letters domains extensions: biz|com|edu|gov|int|mil|net|org|pro|...
    if (preg_match("/^[^@\s\t]+@([a-zA-Z0-9\-]+\.)+([a-zA-Z0-9\-]{2,3}|asso|aero|coop|info|name)\$/i",$address))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 *  Return true if phone number syntax is ok
 *
 *  @param      string		$address    phone (Ex: "0601010101")
 *  @return     boolean     			true if phone syntax is OK, false if KO or empty string
 */
function isValidPhone($address)
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
 *  @return		void
 *  @deprecated
 */
function dol_print_graph($htmlid,$width,$height,$data,$showlegend=0,$type='pie',$showpercent=0,$url='')
{
	global $conf,$langs;
	global $theme_datacolor;    // To have var kept when function is called several times
	if (empty($conf->use_javascript_ajax)) return;
	$jsgraphlib='flot';
	$datacolor=array();

	// Load colors of theme into $datacolor array
	$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
	if (is_readable($color_file))
	{
		include_once($color_file);
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
								radius: 3/4,
								label: {
									show: true,
									radius: 3/4,
									formatter: function(label, series) {
										var percent=Math.round(series.percent);
										var number=series.data[0][1];
										return \'';
										print '<div style="font-size:8pt;text-align:center;padding:2px;color:white;">';
										if ($url) print '<a style="color: #FFFFFF;" border="0" href="'.$url.'=">';
										print '\'+'.($showlegend?'number':'label+\'<br/>\'+number');
										if (! empty($showpercent)) print '+\'<br/>\'+percent+\'%\'';
										print '+\'';
										if ($url) print '</a>';
										print '</div>\';
									},
									background: {
										opacity: 0.5,
										color: \'#000000\'
									}
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
		else print 'BadValueForPArameterType';
	}
}

/**
 *	Truncate a string to a particular length adding '...' if string larger than length.
 * 	If length = max length+1, we do no truncate to avoid having just 1 char replaced with '...'.
 *  MAIN_DISABLE_TRUNC=1 can disable all truncings
 *
 *	@param	string	$string				String to truncate
 *	@param  int		$size				Max string size visible. 0 for no limit. finale string size can be 1 more (if size was max+1) or 3 more (if we added ...)
 *	@param	string	$trunc				Where to trunc: right, left, middle (size must be a 2 power), wrap
 * 	@param	string	$stringencoding		Tell what is source string encoding
 *  @param	int		$nodot				Truncation do not add ... after truncation. So it's an exact truncation.
 *	@return string						Truncated string
 */
function dol_trunc($string,$size=40,$trunc='right',$stringencoding='UTF-8',$nodot=0)
{
    global $conf;

    if ($size==0 || ! empty($conf->global->MAIN_DISABLE_TRUNC)) return $string;

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
 *	Show a picto called object_picto (generic function)
 *
 *	@param      string		$alt           		Text of alt on image
 *	@param      string		$picto         		Name of image to show object_picto (example: user, group, action, bill, contract, propal, product, ...)
 *							 		       		For external modules use imagename@mymodule to search into directory "img" of module.
 *  @param      string		$options       		Add more attribute on img tag (ie: class="datecallink")
 *  @param      int			$pictoisfullpath    If 1, image path is a full path
 *	@return     string      					Return img tag
 *  @see        #img_picto, #img_picto_common
 */
function img_object($alt, $picto, $options='', $pictoisfullpath=0)
{
    global $conf;

    // Clean parameters
    if (! preg_match('/(\.png|\.gif)$/i',$picto) && ! preg_match('/^([^@]+)@([^@]+)$/i',$picto)) $picto.='.png';

    // Define fullpathpicto to use into src
    if (! empty($pictoisfullpath)) $fullpathpicto=$picto;
    else
    {
        // By default, we search into theme directory
        $url = DOL_URL_ROOT;
        $path = 'theme/'.$conf->theme;
        if (! empty($conf->global->MAIN_FORCETHEMEDIR)) $path=preg_replace('/^\//','',$conf->global->MAIN_FORCETHEMEDIR).'/'.$path;
        // If we ask an image into module/img (not into a theme path)
        if (preg_match('/^([^@]+)@([^@]+)$/i',$picto,$regs)) { $picto = $regs[1]; $path=$regs[2]; }      // If image into a module/img path
        if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
        // If img file not into standard path, we use alternate path
        if (defined('DOL_URL_ROOT_ALT') && DOL_URL_ROOT_ALT && ! file_exists(DOL_DOCUMENT_ROOT.'/'.$path.'/img/object_'.$picto)) $url = DOL_URL_ROOT_ALT;

        $fullpathpicto=$url.'/'.$path.'/img/object_'.$picto;
    }

    return '<img src="'.$fullpathpicto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	Show picto whatever it's its name (generic function)
 *
 *	@param      string		$alt         		Text on alt and title of image
 *	@param      string		$picto       		Name of image file to show ('filenew', ...)
 *												If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
 *                                  			Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                  			Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                  			Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *	@param		string		$options			Add more attribute on img tag (For example 'style="float: right"')
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *  @return     string       				    Return img tag
 *  @see        #img_object, #img_picto_common
 */
function img_picto($alt, $picto, $options='', $pictoisfullpath=0)
{
    global $conf;

    // Clean parameters
    if (! preg_match('/(\.png|\.gif)$/i',$picto) && ! preg_match('/^([^@]+)@([^@]+)$/i',$picto)) $picto.='.png';

    // Define fullpathpicto to use into src
    if (! empty($pictoisfullpath)) $fullpathpicto=$picto;
    else
    {
        // By default, we search into theme directory
        $url = DOL_URL_ROOT;
        $path = 'theme/'.$conf->theme;
        if (! empty($conf->global->MAIN_FORCETHEMEDIR)) $path=preg_replace('/^\//','',$conf->global->MAIN_FORCETHEMEDIR).'/'.$path;
        // If we ask an image into module/img (not into a theme path)
        if (preg_match('/^([^@]+)@([^@]+)$/i',$picto,$regs)) { $picto = $regs[1]; $path=$regs[2]; }      // If image into a module/img path
        if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
        // If img file not into standard path, we use alternate path
        if (defined('DOL_URL_ROOT_ALT') && DOL_URL_ROOT_ALT && ! file_exists(DOL_DOCUMENT_ROOT.'/'.$path.'/img/'.$picto)) $url = DOL_URL_ROOT_ALT;

        $fullpathpicto=$url.'/'.$path.'/img/'.$picto;
    }

    return '<img src="'.$fullpathpicto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	Show picto (generic function)
 *
 *	@param      string		$alt         		Text on alt and title of image
 *	@param      string		$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		string		$options			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *	@return     string      					Return img tag
 *  @see        #img_object, #img_picto
 */
function img_picto_common($alt, $picto, $options='', $pictoisfullpath=0)
{
    global $conf;
    if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
    if ($pictoisfullpath) return '<img src="'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
    if (! empty($conf->global->MAIN_MODULE_CAN_OVERWRITE_COMMONICONS) && file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/'.$picto)) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
    return '<img src="'.DOL_URL_ROOT.'/theme/common/'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	Show logo action
 *
 *	@param	string	$alt         	Text for image alt and title ('default', ...)
 *	@param  int		$numaction   	Action to show
 *	@return string      			Return an img tag
 */
function img_action($alt, $numaction)
{
    global $conf,$langs;
    if ($alt=="default")
    {
        if ($numaction == -1) $alt=$langs->transnoentitiesnoconv("ChangeDoNotContact");
        if ($numaction == 0)  $alt=$langs->transnoentitiesnoconv("ChangeNeverContacted");
        if ($numaction == 1)  $alt=$langs->transnoentitiesnoconv("ChangeToContact");
        if ($numaction == 2)  $alt=$langs->transnoentitiesnoconv("ChangeContactInProcess");
        if ($numaction == 3)  $alt=$langs->transnoentitiesnoconv("ChangeContactDone");
    }
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm'.$numaction.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *  Show pdf logo
 *
 *  @param	string		$alt        Texte sur le alt de l'image
 *  @param  int		    $size       Taille de l'icone : 3 = 16x16px , 2 = 14x14px
 *  @return string      			Retourne tag img
 */
function img_pdf($alt = "default",$size=3)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Show");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf'.$size.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Show logo +
 *
 *	@param	string	$alt        Texte sur le alt de l'image
 *	@return string      		Return tag img
 */
function img_edit_add($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Add");
    return img_picto($alt,'edit_add.png');
}
/**
 *	Show logo -
 *
 *	@param	string	$alt         Texte sur le alt de l'image
 *	@return string      Retourne tag img
 */
function img_edit_remove($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Remove");
    return img_picto($alt,'edit_remove.png');
}

/**
 *	Show logo editer/modifier fiche
 *
 *	@param  string	$alt        Texte sur le alt de l'image
 *	@param  float	$float      Si il faut y mettre le style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Retourne tag img
 */
function img_edit($alt = "default", $float=0, $other='')
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Modify");
    $img='<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"';
    if ($float) $img.=' style="float: right"';
    if ($other) $img.=' '.$other;
    $img.='>';
    return $img;
}

/**
 *	Show logo view card
 *
 *	@param	string	$alt         Texte sur le alt de l'image
 *	@param  float	$float       Si il faut y mettre le style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      Retourne tag img
 */
function img_view($alt = "default", $float=0, $other='')
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("View");
    $img='<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/view.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"';
    if ($float) $img.=' style="float: right"';
    if ($other) $img.=' '.$other;
    $img.='>';
    return $img;
}

/**
 *  Show delete logo
 *
 *  @param	string	$alt        Text on alt image
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_delete($alt = "default", $other='')
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Delete");
    return img_picto($alt,'delete.png',$other);
}


/**
 *	Show help logo with cursor "?"
 *
 * 	@param	string	$usehelpcursor		Use help cursor
 * 	@param	string	$usealttitle		Text to use as alt title
 * 	@return string      				Retourne tag img
 */
function img_help($usehelpcursor=1,$usealttitle=1)
{
    global $conf,$langs;
    $s ='<img ';
    if ($usehelpcursor) $s.='style="cursor: help;" ';
    $s.='src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" border="0"';
    if ($usealttitle)
    {
        if (is_string($usealttitle)) $s.=' alt="'.dol_escape_htmltag($usealttitle).'" title="'.dol_escape_htmltag($usealttitle).'"';
        else $s.=' alt="'.$langs->trans("Info").'" title="'.$langs->trans("Info").'"';
    }
    else $s.=' alt=""';
    $s.='>';
    return $s;
}

/**
 *	Affiche logo info
 *
 *	@param	string	$alt        Text to show on alt image
 *	@return string      		Return img tag
 */
function img_info($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Informations");
    return img_picto($alt,'info.png');
}

/**
 *	Show logo warning
 *
 *	@param	string	$alt        Text to show on alt image
 *	@param  int		$float      If we must add style "float: right"
 *	@return string      		Return img tag
 */
function img_warning($alt = "default",$float=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Warning");
    return img_picto($alt,'warning.png',$float?'style="float: right"':'');
}

/**
 *  Affiche logo error
 *
 *	@param	string	$alt        Text to show on alt image
 *	@return string      		Return img tag
 */
function img_error($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Error");
    return img_picto($alt,'error.png');
}

/**
 *	Affiche logo telephone
 *
 *	@param	string	$alt        Text to show on alt image
 *	@param  int		$option		Option
 *	@return string      		Return img tag
 */
function img_phone($alt = "default",$option=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Call");
    $img='call_out';
    if ($option == 1) $img='call';
    $img='object_commercial';
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/'.$img.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}


/**
 *	Affiche logo suivant
 *
 *	@param	string	$alt        Text to show on alt image
 *	@return string      		Return img tag
 */
function img_next($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") {
        $alt=$langs->trans("Next");
    }
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/next.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo precedent
 *
 *	@param	string	$alt        Text to show on alt image
 *	@return string      		Return img tag
 */
function img_previous($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Previous");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/previous.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Show logo down arrow
 *
 *	@param	string	$alt        Text to show on alt image
 *	@param  int		$selected   Selected
 *	@return string      		Return img tag
 */
function img_down($alt = "default", $selected=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Down");
    if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'" class="imgdown">';
    else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'" class="imgdown">';
}

/**
 *	Show logo top arrow
 *
 *	@param	string	$alt        Text to show on alt image
 *	@param  int		$selected	Selected
 *	@return string      		Return img tag
 */
function img_up($alt = "default", $selected=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Up");
    if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'" class="imgup">';
    else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'" class="imgup">';
}

/**
 *	Affiche logo gauche
 *
 *	@param	string	$alt        Text to show on alt image
 *	@param  int		$selected	Selected
 *	@return string      		Return img tag
 */
function img_left($alt = "default", $selected=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Left");
    if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
    else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo droite
 *
 *	@param	string	$alt        Text to show on alt image
 *	@param  int		$selected	Selected
 *	@return string      		Return img tag
 */
function img_right($alt = "default", $selected=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Right");
    if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
    else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche le logo tick si allow
 *
 *	@param	string	$allow		Allow
 *	@param	string	$alt        Text to show on alt image
 *	@return string      		Return img tag
 */
function img_allow($allow,$alt='default')
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Active");

    if ($allow == 1)
    {
        return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
    }
    else
    {
        return "-";
    }
}


/**
 *	Show MIME img of a file
 *
 *	@param	string	$file		Filename
 * 	@param	string	$alt		Alternate text to show on img mous hover
 *	@return string     			Return img tag
 */
function img_mime($file,$alt='')
{
    require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

    $mimetype=dol_mimetype($file,'',1);
    $mimeimg=dol_mimetype($file,'',2);

    if (empty($alt)) $alt='Mime type: '.$mimetype;

    return '<img src="'.DOL_URL_ROOT.'/theme/common/mime/'.$mimeimg.'" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 *	Show information for admin users
 *
 *	@param	string	$text			Text info
 *	@param  string	$infoonimgalt	Info is shown only on alt of star picto, otherwise it is show on output after the star picto
 *	@return	string					String with info text
 */
function info_admin($text,$infoonimgalt=0)
{
    global $conf,$langs;
    $s='';
    if ($infoonimgalt)
    {
        $s.=img_picto($text,'star');
    }
    else
    {
        $s.='<div class="info">';
        $s.=img_picto($langs->trans("InfoAdmin"),'star');
        $s.=' ';
        $s.=$text;
        $s.='</div>';
    }
    return $s;
}


/**
 *	Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontee des bugs.
 *	On doit appeler cette fonction quand une erreur technique bloquante est rencontree.
 *	Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
 *	renvoyer leur erreur par l'intermediaire de leur propriete "error".
 *
 *	@param	DoliDB	$db      	Database handler
 *	@param  string	$error		String or array of errors strings to show
 *	@return	void
 *  @see    dol_htmloutput_errors
 */
function dol_print_error($db='',$error='')
{
    global $conf,$langs,$argv;
    global $dolibarr_main_prod;

    $out = '';
    $syslog = '';

    // Si erreur intervenue avant chargement langue
    if (! $langs)
    {
        require_once(DOL_DOCUMENT_ROOT ."/core/class/translate.class.php");
        $langs = new Translate("", $conf);
        $langs->load("main");
    }
    $langs->load("main");
    $langs->load("errors");

    if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
    {
        $out.=$langs->trans("DolibarrHasDetectedError").".<br>\n";
        if (! empty($conf->global->MAIN_FEATURES_LEVEL))
        $out.="You use an experimental level of features, so please do NOT report any bugs, anywhere, until going back to MAIN_FEATURES_LEVEL = 0.<br>\n";
        $out.=$langs->trans("InformationToHelpDiagnose").":<br>\n";

        $out.="<b>".$langs->trans("Date").":</b> ".dol_print_date(time(),'dayhourlog')."<br>\n";;
        $out.="<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";;
        if (isset($conf->global->MAIN_FEATURES_LEVEL)) $out.="<b>".$langs->trans("LevelOfFeature").":</b> ".$conf->global->MAIN_FEATURES_LEVEL."<br>\n";;
        if (function_exists("phpversion"))
        {
            $out.="<b>".$langs->trans("PHP").":</b> ".phpversion()."<br>\n";
            //phpinfo();       // This is to show location of php.ini file
        }
        $out.="<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";;
        $out.="<br>\n";
        $out.="<b>".$langs->trans("RequestedUrl").":</b> ".$_SERVER["REQUEST_URI"]."<br>\n";;
        $out.="<b>".$langs->trans("Referer").":</b> ".(isset($_SERVER["HTTP_REFERER"])?$_SERVER["HTTP_REFERER"]:'')."<br>\n";;
        $out.="<b>".$langs->trans("MenuManager").":</b> ".$conf->top_menu."<br>\n";
        $out.="<br>\n";
        $syslog.="url=".$_SERVER["REQUEST_URI"];
        $syslog.=", query_string=".$_SERVER["QUERY_STRING"];
    }
    else                              // Mode CLI
    {
        $out.='> '.$langs->transnoentities("ErrorInternalErrorDetected").":\n".$argv[0]."\n";
        $syslog.="pid=".getmypid();
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

    if ($error)
    {
        $langs->load("errors");

        if (is_array($error)) $errors=$error;
        else $errors=array($error);

        foreach($errors as $msg)
        {
            $msg=$langs->trans($msg);
            if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
            {
                $out.="<b>".$langs->trans("Message").":</b> ".$msg."<br>\n" ;
            }
            else                            // Mode CLI
            {
                $out.='> '.$langs->transnoentities("Message").":\n".$msg."\n" ;
            }
            $syslog.=", msg=".$msg;
        }
    }
    if (empty($dolibarr_main_prod) && $_SERVER['DOCUMENT_ROOT'] && function_exists('xdebug_call_file'))
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
 * Show email to contact if technical error
 *
 * @return	void
 */
function dol_print_error_email()
{
    global $langs,$conf;

    $langs->load("errors");
    print '<br><div class="error">'.$langs->trans("ErrorContactEMail",$conf->global->MAIN_INFO_SOCIETE_MAIL,'ERRORNEWPAYMENT'.dol_print_date(mktime(),'%Y%m%d')).'</div>';
}

/**
 *	Show title line of an array
 *
 *	@param	string	$name        Label of field
 *	@param	string	$file        Url used when we click on sort picto
 *	@param	string	$field       Field to use for new sorting
 *	@param	string	$begin       ("" by defaut)
 *	@param	string	$moreparam   Add more parameters on sort url links ("" by default)
 *	@param  string	$td          Options of attribute td ("" by defaut)
 *	@param  string	$sortfield   Current field used to sort
 *	@param  string	$sortorder   Current sort order
 *	@return	void
 */
function print_liste_field_titre($name, $file="", $field="", $begin="", $moreparam="", $td="", $sortfield="", $sortorder="")
{
	print getTitleFieldOfList($name, 0, $file, $field, $begin, $moreparam, $td, $sortfield, $sortorder);
}

/**
 *	Get title line of an array
 *
 *	@param	string	$name        Label of field
 *	@param	int		$thead		For thead format
 *	@param	string	$file        Url used when we click on sort picto
 *	@param	string	$field       Field to use for new sorting
 *	@param	string	$begin       ("" by defaut)
 *	@param	string	$moreparam   Add more parameters on sort url links ("" by default)
 *	@param  string	$moreattrib  Add more attributes on th ("" by defaut)
 *	@param  string	$sortfield   Current field used to sort
 *	@param  string	$sortorder   Current sort order
 *	@return	void
 */
function getTitleFieldOfList($name, $thead=0, $file="", $field="", $begin="", $moreparam="", $moreattrib="", $sortfield="", $sortorder="")
{
    global $conf;
    //print "$name, $file, $field, $begin, $options, $moreattrib, $sortfield, $sortorder<br>\n";

    $out='';
	// If field is used as sort criteria we use a specific class
    // Example if (sortfield,field)=("nom","xxx.nom") or (sortfield,field)=("nom","nom")
    if ($field && ($sortfield == $field || $sortfield == preg_replace("/^[^\.]+\./","",$field)))
    {
        $out.= '<th class="liste_titre_sel" '. $moreattrib.'>';
    }
    else
    {
        $out.= '<th class="liste_titre" '. $moreattrib.'>';
    }
    $out.=$name;

    if (empty($thead) && $field)    // If this is a sort field
    {
        $options=preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i','',$moreparam);
        $options=preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i','',$options);
        $options=preg_replace('/&+/i','&',$options);
        if (! preg_match('/^&/',$options)) $options='&'.$options;

        //print "&nbsp;";
        $out.= '<img width="2" src="'.DOL_URL_ROOT.'/theme/common/transparent.png" alt="">';
        if (! $sortorder)
        {
            $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
            $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
        }
        else
        {
            if ($field != $sortfield)
            {
                $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
                $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
            }
            else {
                $sortorder=strtoupper($sortorder);
                if ($sortorder == 'DESC' ) {
                    $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
                    $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",1).'</a>';
                }
                if ($sortorder == 'ASC' ) {
                    $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",1).'</a>';
                    $out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
                }
            }
        }
    }
    $out.='</th>';

    return $out;
}

/**
 *	Show a title (deprecated. use print_fiche_titre instrad)
 *
 *	@param	string	$title			Title to show
 *	@return	string					Title to show
 */
function print_titre($title)
{
    print '<div class="titre">'.$title.'</div>';
}

/**
 *	Show a title with picto
 *
 *	@param	string	$titre				Title to show
 *	@param	string	$mesg				Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	int		$id					To force an id on html objects
 * 	@return	void
 */
function print_fiche_titre($titre, $mesg='', $picto='title.png', $pictoisfullpath=0, $id='')
{
    print load_fiche_titre($titre, $mesg, $picto, $pictoisfullpath, $id);
}

/**
 *	Load a title with picto
 *
 *	@param	string	$titre				Title to show
 *	@param	string	$mesg				Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath		1=Icon name is a full absolute url of image
 * 	@param	int		$id					To force an id on html objects
 * 	@return	void
 */
function load_fiche_titre($titre, $mesg='', $picto='title.png', $pictoisfullpath=0, $id='')
{
    global $conf;

    $return='';

    if ($picto == 'setup') $picto='title.png';
    if (!empty($conf->browser->ie) && $picto=='title.png') $picto='title.gif';

    $return.= "\n";
    $return.= '<table '.($id?'id="'.$id.'" ':'').'summary="" width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;"><tr>';
    if ($picto) $return.= '<td class="nobordernopadding hideonsmartphone" width="40" align="left" valign="middle">'.img_picto('',$picto, 'id="pictotitle"', $pictoisfullpath).'</td>';
    $return.= '<td class="nobordernopadding" valign="middle">';
    $return.= '<div class="titre">'.$titre.'</div>';
    $return.= '</td>';
    if (dol_strlen($mesg))
    {
        $return.= '<td class="nobordernopadding" align="right" valign="middle"><b>'.$mesg.'</b></td>';
    }
    $return.= '</tr></table>'."\n";

    return $return;
}

/**
 *	Print a title with navigation controls for pagination
 *
 *	@param	string	$titre				Title to show (required)
 *	@param	string	$page				Numero of page (required)
 *	@param	string	$file				Url of page (required)
 *	@param	string	$options         	parametres complementaires lien ('' par defaut)
 *	@param	string	$sortfield       	champ de tri ('' par defaut)
 *	@param	string	$sortorder       	ordre de tri ('' par defaut)
 *	@param	string	$center          	chaine du centre ('' par defaut)
 *	@param	int		$num				number of records found by select with limit+1
 *	@param	int		$totalnboflines		Total number of records/lines for all pages (if known)
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath		1=Icon name is a full absolute url of image
 *	@return	void
 */
function print_barre_liste($titre, $page, $file, $options='', $sortfield='', $sortorder='', $center='', $num=-1, $totalnboflines=0, $picto='title.png', $pictoisfullpath=0)
{
    global $conf,$langs;

    if ($picto == 'setup') $picto='title.png';
    if (!empty($conf->browser->ie) && $picto=='title.png') $picto='title.gif';

    if ($num > $conf->liste_limit or $num == -1)
    {
        $nextpage = 1;
    }
    else
    {
        $nextpage = 0;
    }

    print "\n";
    print "<!-- Begin title '".$titre."' -->\n";
    print '<table width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;"><tr>';

    $pagelist = '';

    // Left
    if ($page > 0 || $num > $conf->liste_limit)
    {
        if ($totalnboflines)
        {
            if ($picto && $titre) print '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, '', $pictoisfullpath).'</td>';
            print '<td class="nobordernopadding">';
            print '<div class="titre">'.$titre.'</div>';
            print '</td>';

            $maxnbofpage=10;

            $nbpages=ceil($totalnboflines/$conf->liste_limit);
            $cpt=($page-$maxnbofpage);
            if ($cpt < 0) { $cpt=0; }
            $pagelist.=$langs->trans('Page');
            if ($cpt>=1)
            {
                $pagelist.=' <a href="'.$file.'?page=0'.$options.'&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">1</a>';
                if ($cpt >= 2) $pagelist.=' ...';
            }
            do
            {
                if($cpt==$page)
                {
                    $pagelist.= ' <u>'.($page+1).'</u>';
                }
                else
                {
                    $pagelist.= ' <a href="'.$file.'?page='.$cpt.$options.'&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">'.($cpt+1).'</a>';
                }
                $cpt++;
            }
            while ($cpt < $nbpages && $cpt<=$page+$maxnbofpage);
            if ($cpt<$nbpages)
            {
                if ($cpt<$nbpages-1) $pagelist.= ' ...';
                $pagelist.= ' <a href="'.$file.'?page='.($nbpages-1).$options.'&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">'.$nbpages.'</a>';
            }
        }
        else
        {
            if (empty($conf->browser->phone) && $picto && $titre) print '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, '', $pictoisfullpath).'</td>';
            print '<td class="nobordernopadding">';
            print '<div class="titre">'.$titre.'</div>';
            $pagelist.= $langs->trans('Page').' '.($page+1);
            print '</td>';
        }
    }
    else
    {
        if (empty($conf->browser->phone) && $picto && $titre) print '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, '', $pictoisfullpath).'</td>';
        print '<td class="nobordernopadding"><div class="titre">'.$titre.'</div></td>';
    }

    // Center
    if ($center)
    {
        print '<td class="nobordernopadding" align="left" valign="middle">'.$center.'</td>';
    }

    // Right
    print '<td class="nobordernopadding" align="right" valign="middle">';
    if ($sortfield) $options .= "&amp;sortfield=".$sortfield;
    if ($sortorder) $options .= "&amp;sortorder=".$sortorder;
    // Affichage des fleches de navigation
    print_fleche_navigation($page,$file,$options,$nextpage,$pagelist);
    print '</td>';

    print '</tr></table>'."\n";
    print "<!-- End title -->\n\n";
}

/**
 *	Fonction servant a afficher les fleches de navigation dans les pages de listes
 *
 *	@param	int		$page				Numero of page
 *	@param	string	$file				Lien
 *	@param	string	$options         	Autres parametres d'url a propager dans les liens ("" par defaut)
 *	@param	int		$nextpage	    	Faut-il une page suivante
 *	@param	string	$betweenarrows		HTML Content to show between arrows
 *	@return	void
 */
function print_fleche_navigation($page,$file,$options='',$nextpage=0,$betweenarrows='')
{
    global $conf, $langs;
    if ($page > 0)
    {
        print '<a href="'.$file.'?page='.($page-1).$options.'">'.img_previous($langs->trans("Previous")).'</a>';
    }
    if ($betweenarrows) print ($page > 0?' ':'').$betweenarrows.($nextpage>0?' ':'');
    if ($nextpage > 0)
    {
        print '<a href="'.$file.'?page='.($page+1).$options.'">'.img_next($langs->trans("Next")).'</a>';
    }
}


/**
 *	Return a string with VAT rate label formated for view output
 *	Used into pdf and HTML pages
 *
 *	@param	float	$rate			Rate value to format (19.6 19,6 19.6% 19,6%,...)
 *  @param	boolean	$addpercent		Add a percent % sign in output
 *	@param	int		$info_bits		Miscellanous information on vat (0=Default, 1=French NPR vat)
 *	@param	int		$usestarfornpr	1=Use '*' for NPR vat rate intead of MAIN_LABEL_MENTION_NPR
 *  @return	string					String with formated amounts (19,6 or 19,6% or 8.5% NPR or 8.5% *)
 */
function vatrate($rate,$addpercent=false,$info_bits=0,$usestarfornpr=0)
{
    // Test for compatibility
    if (preg_match('/%/',$rate))
    {
        $rate=str_replace('%','',$rate);
        $addpercent=true;
    }
    if (preg_match('/\*/',$rate) || preg_match('/'.constant('MAIN_LABEL_MENTION_NPR').'/i',$rate))
    {
        $rate=str_replace('*','',$rate);
        $info_bits |= 1;
    }

    $ret=price($rate,0,'',0,0).($addpercent?'%':'');
    if ($info_bits & 1) $ret.=' '.($usestarfornpr?'*':constant('MAIN_LABEL_MENTION_NPR'));
    return $ret;
}


/**
 *		Fonction qui formate un montant pour visualisation
 *		Fonction utilisee dans les pdf et les pages html
 *
 *		@param	float		$amount			Montant a formater
 *		@param	string		$form			Type de formatage, html ou pas (par defaut)
 *		@param	Translate	$outlangs		Objet langs pour formatage text
 *		@param	int			$trunc			1=Tronque affichage si trop de decimales,0=Force le non troncage
 *		@param	int			$rounding		Minimum number of decimal. If not defined we use min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOTAL)
 *		@param	int			$forcerounding	Force the number of decimal
 *		@return	string						Chaine avec montant formate
 *
 *		@see	price2num					Revert function of price
 */
function price($amount, $form=0, $outlangs='', $trunc=1, $rounding=-1, $forcerounding=-1)
{
    global $langs,$conf;

    // Clean parameters
    if (empty($amount)) $amount=0;	// To have a numeric value if amount not defined or = ''
    if ($rounding < 0) $rounding=min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);

    $nbdecimal=$rounding;

    // Output separators by default (french)
    $dec=','; $thousand=' ';

    // If $outlangs not forced, we use use language
    if (! is_object($outlangs)) $outlangs=$langs;

    if ($outlangs->trans("SeparatorDecimal") != "SeparatorDecimal")  $dec=$outlangs->trans("SeparatorDecimal");
    if ($outlangs->trans("SeparatorThousand")!= "SeparatorThousand") $thousand=$outlangs->trans("SeparatorThousand");
    if ($thousand == 'None') $thousand='';
    //print "amount=".$amount." html=".$form." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

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
    if ($form)
    {
        $output=preg_replace('/\s/','&nbsp;',number_format($amount, $nbdecimal, $dec, $thousand));
    }
    else
    {
        $output=number_format($amount, $nbdecimal, $dec, $thousand);
    }
    $output.=$end;

    return $output;
}

/**
 *	Function that return a number with universal decimal format (decimal separator is '.') from
 *	an amount typed by a user.
 *	Function to use on each input amount before any numeric test or database insert
 *
 *	@param	float	$amount			Amount to convert/clean
 *	@param	string	$rounding		''=No rounding
 * 									'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
 *									'MT'=Round to Max for totals with Tax (MAIN_MAX_DECIMALS_TOT)
 *									'MS'=Round to Max Shown (MAIN_MAX_DECIMALS_SHOWN)
 * 	@param	int		$alreadysqlnb	Put 1 if you know that content is already universal format number
 *	@return	string					Amount with universal numeric format (Example: '99.99999')
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
    if ($langs->trans("SeparatorDecimal") != "SeparatorDecimal")  $dec=$langs->trans("SeparatorDecimal");
    if ($langs->trans("SeparatorThousand")!= "SeparatorThousand") $thousand=$langs->trans("SeparatorThousand");
    if ($thousand == 'None') $thousand='';
    //print "amount=".$amount." html=".$form." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

    // Convert value to universal number format (no thousand separator, '.' as decimal separator)
    if ($alreadysqlnb != 1)	// If not a PHP number or unknown, we change format
    {
        //print 'PP'.$amount.' - '.$dec.' - '.$thousand.'<br>';

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
        elseif ($rounding == 'MS') $nbofdectoround=$conf->global->MAIN_MAX_DECIMALS_SHOWN;
        elseif ($rounding == '2')  $nbofdectoround=2; 	// For admin info page
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
 *	Return localtaxe rate for a particular tva
 *
 * 	@param	float		$tva			        Vat taxe
 * 	@param  int			$local		         	Local taxe to search and return
 *  @param  Societe		$societe_acheteuse    	Object of buying third party
 * 	@return	int				   					0 if not found, localtax if found
 */
function get_localtax($tva, $local, $societe_acheteuse="")
{
    global $db, $conf, $mysoc;

    $code_pays=$mysoc->pays_code;

    if (is_object($societe_acheteuse))
    {
        if ($code_pays!=$societe_acheteuse->pays_code) return 0;
        if ($local==1 && !$societe_acheteuse->localtax1_assuj) return 0;
        elseif ($local==2 && !$societe_acheteuse->localtax2_assuj) return 0;
    }

    // Search local taxes
    $sql  = "SELECT t.localtax1, t.localtax2";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
    $sql .= " WHERE t.fk_pays = p.rowid AND p.code = '".$code_pays."'";
    $sql .= " AND t.taux = ".$tva." AND t.active = 1";
    $sql .= " ORDER BY t.localtax1 ASC, t.localtax2 ASC";

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
 *	Return vat rate of a product in a particular selling country or default country
 *  vat if product is unknown
 *
 *  @param	int		$idprod          Id of product or 0 if not a predefined product
 *  @param  string	$countrycode     Country code (FR, US, IT, ...)
 *  @return int				         <0 if KO, Vat rate if OK
 *	TODO May be this should be better as a method of product class
 */
function get_product_vat_for_country($idprod, $countrycode)
{
    global $db,$mysoc;

    $ret=0;
    $found=0;

    if ($idprod > 0)
    {
        // Load product
        $product=new Product($db);
        $result=$product->fetch($idprod);

        if ($mysoc->pays_code == $countrycode) // If selling country is ours
        {
            $ret=$product->tva_tx;    // Default vat of product we defined
            $found=1;
        }
        else
        {
            // TODO Read default product vat according to countrycode and product


        }
    }

    if (! $found)
    {
        // If vat of product for the country not found or not defined, we return higher vat of country.
        $sql.="SELECT taux as vat_rate";
        $sql.=" FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
        $sql.=" WHERE t.active=1 AND t.fk_pays = p.rowid AND p.code='".$countrycode."'";
        $sql.=" ORDER BY t.taux DESC, t.recuperableonly ASC";
        $sql.=$db->plimit(1);

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj=$db->fetch_object($resql);
            if ($obj)
            {
                $ret=$obj->vat_rate;
            }
        }
        else dol_print_error($db);
    }

    dol_syslog("get_product_vat_for_country: ret=".$ret);
    return $ret;
}

/**
 *	Return localtax rate of a product in a particular selling country
 *
 *  @param	int		$idprod         Id of product
 *  @param  int		$local          1 for localtax1, 2 for localtax 2
 *  @param  string	$countrycode    Country code (FR, US, IT, ...)
 *  @return int             		<0 if KO, Vat rate if OK
 *	TODO May be this should be better as a method of product class
 */
function get_product_localtax_for_country($idprod, $local, $countrycode)
{
    global $db;

    $product=new Product($db);
    $product->fetch($idprod);

    if ($local==1) return $product->localtax1_tx;
    elseif ($local==2) return $product->localtax2_tx;

    return -1;
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
 *	@param	Societe		$societe_vendeuse    	Objet societe vendeuse
 *	@param  Societe		$societe_acheteuse   	Objet societe acheteuse
 *	@param  int			$idprod					Id product
 *	@return float         				      	Taux de tva a appliquer, -1 si ne peut etre determine
 */
function get_default_tva($societe_vendeuse, $societe_acheteuse, $idprod=0)
{
    global $conf;

    if (!is_object($societe_vendeuse)) return -1;
    if (!is_object($societe_acheteuse)) return -1;

    dol_syslog("get_default_tva: seller use vat=".$societe_vendeuse->tva_assuj.", seller country=".$societe_vendeuse->pays_code.", seller in cee=".$societe_vendeuse->isInEEC().", buyer country=".$societe_acheteuse->pays_code.", buyer in cee=".$societe_acheteuse->isInEEC().", idprod=".$idprod.", SERVICE_ARE_ECOMMERCE_200238EC=".$conf->global->SERVICES_ARE_ECOMMERCE_200238EC);

    // Si vendeur non assujeti a TVA (tva_assuj vaut 0/1 ou franchise/reel)
    if (is_numeric($societe_vendeuse->tva_assuj) && ! $societe_vendeuse->tva_assuj)
    {
        //print 'VATRULE 1';
        return 0;
    }
    if (! is_numeric($societe_vendeuse->tva_assuj) && $societe_vendeuse->tva_assuj=='franchise')
    {
        //print 'VATRULE 2';
        return 0;
    }

    //if (is_object($societe_acheteuse) && ($societe_vendeuse->country_id == $societe_acheteuse->country_id) && ($societe_acheteuse->tva_assuj == 1 || $societe_acheteuse->tva_assuj == 'reel'))
    // Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concerne si le test suivant n'est pas suffisant.

    // Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
    if ($societe_vendeuse->country_code == $societe_acheteuse->country_code) // Warning ->country_code not always defined
    {
        //print 'VATRULE 3';
        return get_product_vat_for_country($idprod,$societe_vendeuse->country_code);
    }

    // Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
    // Non gere

    // Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise) alors TVA par defaut=0. Fin de regle
    // Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier) alors TVA par defaut=TVA du produit vendu. Fin de regle
    if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()))
    {
        $isacompany=$societe_acheteuse->isACompany();
        if ($isacompany)
        {
            //print 'VATRULE 4';
            return 0;
        }
        else
        {
            //print 'VATRULE 5';
            return get_product_vat_for_country($idprod,$societe_vendeuse->country_code);
        }
    }

    // If services are eServices according to EU Council Directive 2002/38/EC (ec.europa.eu/taxation_customs/taxation/v.../article_1610_en.htm)
    // we use the buyer VAT.
    if (! empty($conf->global->SERVICE_ARE_ECOMMERCE_200238EC))
    {
        //print "eee".$societe_acheteuse->isACompany();exit;
        if (! $societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC() && ! $societe_acheteuse->isACompany())
        {
            //print 'VATRULE 6';
            return get_product_vat_for_country($idprod,$societe_acheteuse->country_code);
        }
    }

    // Sinon la TVA proposee par defaut=0. Fin de regle.
    // Rem: Cela signifie qu'au moins un des 2 est hors Communaute europeenne et que le pays differe
    //print 'VATRULE 7';
    return 0;
}


/**
 *	Fonction qui renvoie si tva doit etre tva percue recuperable
 *
 *	@param	Societe		$societe_vendeuse    	Objet societe vendeuse
 *	@param  Societe		$societe_acheteuse   	Objet societe acheteuse
 *  @param  int			$idprod                 Id product
 *	@return float       			        	0 or 1
 */
function get_default_npr($societe_vendeuse, $societe_acheteuse, $idprod)
{
    return 0;
}

/**
 *	Function that return localtax of a product line (according to seller, buyer and product vat rate)
 *
 *	@param	Societe		$societe_vendeuse    	Objet societe vendeuse
 *	@param  Societe		$societe_acheteuse   	Objet societe acheteuse
 *  @param	int			$local					Localtax to process (1 or 2)
 *	@param  int			$idprod					Id product
 *	@return float        				       	Taux de localtax appliquer, -1 si ne peut etre determine
 */
function get_default_localtax($societe_vendeuse, $societe_acheteuse, $local, $idprod=0)
{
    if (!is_object($societe_vendeuse)) return -1;
    if (!is_object($societe_acheteuse)) return -1;

    if ($societe_vendeuse->country_id=='ES' || $societe_vendeuse->country_code=='ES')
    {
        if ($local==1) //RE
        {
            // Si achatteur non assujeti a RE, localtax1 par default=0
            if (is_numeric($societe_acheteuse->localtax1_assuj) && ! $societe_acheteuse->localtax1_assuj) return 0;
            if (! is_numeric($societe_acheteuse->localtax1_assuj) && $societe_acheteuse->localtax1_assuj=='localtax1off') return 0;
        }
        elseif ($local==2) //IRPF
        {
            // Si vendeur non assujeti a IRPF, localtax2 par default=0
            if (is_numeric($societe_vendeuse->localtax2_assuj) && ! $societe_vendeuse->localtax2_assuj) return 0;
            if (! is_numeric($societe_vendeuse->localtax2_assuj) && $societe_vendeuse->localtax2_assuj=='localtax2off') return 0;
        } else return -1;

        if ($idprod) return get_product_localtax_for_country($idprod, $local, $societe_vendeuse->country_code);
        else return -1;
    }
    return 0;
}

/**
 *	Return yes or no in current language
 *
 *	@param	string	$yesno			Value to test (1, 'yes', 'true' or 0, 'no', 'false')
 *	@param	string	$case			1=Yes/No, 0=yes/no
 *	@param	int		$color			0=texte only, 1=Text is formated with a color font style ('ok' or 'error'), 2=Text is formated with 'ok' color.
 *	@return	string					HTML string
 */
function yn($yesno, $case=1, $color=0)
{
    global $langs;
    $result='unknown';
    if ($yesno == 1 || strtolower($yesno) == 'yes' || strtolower($yesno) == 'true') 	// A mettre avant test sur no a cause du == 0
    {
        $result=($case?$langs->trans("Yes"):$langs->trans("yes"));
        $classname='ok';
    }
    elseif ($yesno == 0 || strtolower($yesno) == 'no' || strtolower($yesno) == 'false')
    {
        $result=($case?$langs->trans("No"):$langs->trans("no"));
        if ($color == 2) $classname='ok';
        else $classname='error';
    }
    if ($color) return '<font class="'.$classname.'">'.$result.'</font>';
    return $result;
}


/**
 *	Return a path to have a directory according to an id
 *  Examples:       '001' with level 3->"0/0/1/", '015' with level 3->"0/1/5/"
 *  Examples:       'ABC-1' with level 3 ->"0/0/1/", '015' with level 1->"5/"
 *
 *	@param	string	$num            Id to develop
 *	@param  int		$level		    Level of development (1, 2 or 3 level)
 * 	@param	int		$alpha		    Use alpha ref
 *  @param  int		$withoutslash   0=With slash at end, 1=without slash at end
 *  @return	string					Dir to use
 */
function get_exdir($num,$level=3,$alpha=0,$withoutslash=0)
{
    $path = '';
    if (empty($alpha)) $num = preg_replace('/([^0-9])/i','',$num);
    else $num = preg_replace('/^.*\-/i','',$num);
    $num = substr("000".$num, -$level);
    if ($level == 1) $path = substr($num,0,1);
    if ($level == 2) $path = substr($num,1,1).'/'.substr($num,0,1);
    if ($level == 3) $path = substr($num,2,1).'/'.substr($num,1,1).'/'.substr($num,0,1);
    if (empty($withoutslash)) $path.='/';
    return $path;
}

// For backward compatibility
function create_exdir($dir)
{
    dol_mkdir($dir);
}

/**
 *	Creation of a directory (this can create recursive subdir)
 *
 *	@param	string	$dir	Directory to create (Separator must be '/'. Example: '/mydir/mysubdir')
 *	@return int         	< 0 if KO, 0 = already exists, > 0 if OK
 */
function dol_mkdir($dir)
{
    global $conf;

    dol_syslog("functions.lib::create_exdir: dir=".$dir,LOG_INFO);

    $dir_osencoded=dol_osencode($dir);
    if (@is_dir($dir_osencoded)) return 0;

    $nberr=0;
    $nbcreated=0;

    $ccdir = '';
    $cdir = explode("/",$dir);
    $num=count($cdir);
    for ($i = 0; $i < $num; $i++)
    {
        if ($i > 0) $ccdir .= '/'.$cdir[$i];
        else $ccdir = $cdir[$i];
        if (preg_match("/^.:$/",$ccdir,$regs)) continue;	// Si chemin Windows incomplet, on poursuit par rep suivant

        // Attention, le is_dir() peut echouer bien que le rep existe.
        // (ex selon config de open_basedir)
        if ($ccdir)
        {
            $ccdir_osencoded=dol_osencode($ccdir);
            if (! @is_dir($ccdir_osencoded))
            {
                dol_syslog("functions.lib::create_exdir: Directory '".$ccdir."' does not exists or is outside open_basedir PHP setting.",LOG_DEBUG);

                umask(0);
                $dirmaskdec=octdec('0755');
                if (! empty($conf->global->MAIN_UMASK)) $dirmaskdec=octdec($conf->global->MAIN_UMASK);
                $dirmaskdec |= octdec('0111');  // Set x bit required for directories
                if (! @mkdir($ccdir_osencoded, $dirmaskdec))
                {
                    // Si le is_dir a renvoye une fausse info, alors on passe ici.
                    dol_syslog("functions.lib::create_exdir: Fails to create directory '".$ccdir."' or directory already exists.",LOG_WARNING);
                    $nberr++;
                }
                else
                {
                    dol_syslog("functions.lib::create_exdir: Directory '".$ccdir."' created",LOG_DEBUG);
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
 *	@param	string	$removelinefeed		Replace also all lines feeds by a space
 *  @param  string	$pagecodeto      	Encoding of input string
 *	@return string	    				String cleaned
 */
function dol_string_nohtmltag($StringHtml,$removelinefeed=1,$pagecodeto='UTF-8')
{
    $pattern = "/<[^>]+>/";
    $temp = dol_entity_decode($StringHtml,$pagecodeto);
    $temp = preg_replace($pattern,"",$temp);

    // Supprime aussi les retours
    if ($removelinefeed) $temp=str_replace("\n"," ",$temp);

    // et les espaces doubles
    while(strpos($temp,"  "))
    {
        $temp = str_replace("  "," ",$temp);
    }
    $CleanString = trim($temp);
    return $CleanString;
}


/**
 *	Replace CRLF in string with a HTML BR tag
 *
 *	@param	string	$stringtoencode		String to encode
 *	@param	string	$nl2brmode			0=Adding br before \n, 1=Replacing \n by br
 *  @param  string	$forxml             false=Use <br>, true=Use <br />
 *	@return	string						String encoded
 */
function dol_nl2br($stringtoencode,$nl2brmode=0,$forxml=false)
{
    if (! $nl2brmode)
    {
        // We use @ to avoid warning on PHP4 that does not support entity encoding from UTF8;
        if (version_compare(PHP_VERSION, '5.3.0') < 0) return @nl2br($stringtoencode);
        else return @nl2br($stringtoencode,$forxml);
    }
    else
    {
        $ret=preg_replace('/(\r\n|\r|\n)/i',($forxml?'<br />':'<br>'),$stringtoencode);
        return $ret;
    }
}

/**
 *	This function is called to encode a string into a HTML string but differs from htmlentities because
 * 	all entities but &,<,> are converted. This permits to encode special chars to entities with no double
 *  encoding for already encoded HTML strings.
 * 	This function also remove last CR/BR.
 *  For PDF usage, you can show text by 2 ways:
 *              - writeHTMLCell -> param must be encoded into HTML.
 *              - MultiCell -> param must not be encoded into HTML.
 *              Because writeHTMLCell convert also \n into <br>, if function
 *              is used to build PDF, nl2brmode must be 1
 *
 *	@param	string	$stringtoencode		String to encode
 *	@param	int		$nl2brmode			0=Adding br before \n, 1=Replacing \n by br (for use with FPDF writeHTMLCell function for example)
 *  @param  string	$pagecodefrom       Pagecode stringtoencode is encoded
 *  @return	string						String encoded
 */
function dol_htmlentitiesbr($stringtoencode,$nl2brmode=0,$pagecodefrom='UTF-8')
{
    if (dol_textishtml($stringtoencode))
    {
        $newstring=$stringtoencode;
        $newstring=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i','<br>',$newstring);	// Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
        $newstring=preg_replace('/<br>$/i','',$newstring);	// Remove last <br>
        $newstring=strtr($newstring,array('&'=>'__and__','<'=>'__lt__','>'=>'__gt__','"'=>'__dquot__'));
        $newstring=dol_htmlentities($newstring,ENT_COMPAT,$pagecodefrom);	// Make entity encoding
        $newstring=strtr($newstring,array('__and__'=>'&','__lt__'=>'<','__gt__'=>'>','__dquot__'=>'"'));
        //$newstring=strtr($newstring,array('__li__'=>"<li>\n")); // Restore <li>\n
    }
    else
    {
        $newstring=dol_nl2br(dol_htmlentities($stringtoencode,ENT_COMPAT,$pagecodefrom),$nl2brmode);
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
 *	This function is called to decode a string with HTML entities (it decodes entities tags)
 *
 * 	@param	string	$stringhtml     stringhtml
 *  @param  string	$pagecodeto     Encoding of input string
 * 	@return string	  	    		decodestring
 */
function dol_entity_decode($stringhtml,$pagecodeto='UTF-8')
{
    $ret=dol_html_entity_decode($stringhtml,ENT_COMPAT,$pagecodeto);
    return $ret;
}

/**
 * Replace html_entity_decode functions to manage errors
 *
 * @param   string	$a		Operand a
 * @param   string	$b		Operand b
 * @param   string	$c		Operand c
 * @return  string			String decoded
 */
function dol_html_entity_decode($a,$b,$c)
{
    // We use @ to avoid warning on PHP4 that does not support entity decoding to UTF8;
    $ret=@html_entity_decode($a,$b,$c);
    return $ret;
}

/**
 * Replace htmlentities functions to manage errors
 *
 * @param   string	$a		Operand a
 * @param   string	$b		Operand b
 * @param   string	$c		Operand c
 * @return  string      	String encoded
 */
function dol_htmlentities($a,$b,$c)
{
    // We use @ to avoid warning on PHP4 that does not support entity decoding to UTF8;
    $ret=@htmlentities($a,$b,$c);
    return $ret;
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
 * 	@param	string	$maxchar	Not yet used
 *	@return	int					Number of lines
 */
function dol_nboflines($s,$maxchar=0)
{
    if ($s == '') return 0;
    $arraystring=explode("\n",$s);
    $nb=count($arraystring);

    return $nb;
}


/**
 *	Return nb of lines of a formated text with \n and <br>
 *
 *	@param	string	$text      		Text
 *	@param	int		$maxlinesize  	Largeur de ligne en caracteres (ou 0 si pas de limite - defaut)
 * 	@param	string	$charset		Give the charset used to encode the $text variable in memory.
 *	@return int						Number of lines
 */
function dol_nboflines_bis($text,$maxlinesize=0,$charset='UTF-8')
{
    //print $text;
    $repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
    $text = strtr($text, $repTable);
    if ($charset == 'UTF-8') { $pattern = '/(<[^>]+>)/Uu'; }	// /U is to have UNGREEDY regex to limit to one html tag. /u is for UTF8 support
    else $pattern = '/(<[^>]+>)/U';								// /U is to have UNGREEDY regex to limit to one html tag.
    $a = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $nblines = floor((count($a)+1)/2);
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
 *	 @return		float		Time (millisecondes) with microsecondes in decimal part
 */
function dol_microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

/**
 *		Return if a text is a html content
 *
 *		@param	string	$msg		Content to check
 *		@param	int		$option		0=Full detection, 1=Fast check
 *		@return	boolean				true/false
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
        elseif (preg_match('/<br/i',$msg))				return true;
        elseif (preg_match('/<span/i',$msg))			return true;
        elseif (preg_match('/<div/i',$msg))				return true;
        elseif (preg_match('/<li/i',$msg))				return true;
        elseif (preg_match('/<table/i',$msg))			return true;
        elseif (preg_match('/<font/i',$msg))			return true;
        elseif (preg_match('/<strong/i',$msg))			return true;
        elseif (preg_match('/<img/i',$msg))				return true;
        elseif (preg_match('/<i>/i',$msg))				return true;
        elseif (preg_match('/<b>/i',$msg))				return true;
        elseif (preg_match('/&[A-Z0-9]{1,6};/i',$msg))	return true;
        return false;
    }
}

/**
 *    	Make substition into a string
 *      There is two type of substitions:
 * 		- From $substitutionarray (oldval=>newval)
 * 		- From special constants (__XXX__=>f(objet->xxx)) by substitutions modules
 *
 *    	@param	string	$chaine      			Source string in which we must do substitution
 *    	@param  array	$substitutionarray		Array with key->val to substitute
 *    	@return string  		    			Output string after subsitutions
 */
function make_substitutions($chaine,$substitutionarray)
{
    if (! is_array($substitutionarray)) return 'ErrorBadParameterSubstitutionArrayWhenCalling_make_substitutions';

    // Make substitition
    foreach ($substitutionarray as $key => $value)
    {
        $chaine=str_replace("$key","$value",$chaine);	// We must keep the " to work when value is 123.5 for example
    }
    return $chaine;
}

/**
 *  Complete the $substitutionarray with more entries
 *
 *  @param  array		&$substitutionarray       Array substitution old value => new value value
 *  @param  Translate	$outputlangs             If we want substitution from special constants, we provide a language
 *  @param  Object		$object                  If we want substitution from special constants, we provide data in a source object
 *  @return	void
 */
function complete_substitutions_array(&$substitutionarray,$outputlangs,$object='')
{
    global $conf,$user;

    require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

    // Check if there is external substitution to do asked by plugins
    // We look files into the core/modules/substitutions directory
    // By default, there is no such external plugins.
    foreach ($conf->file->dol_document_root as $dirroot)
    {
        $substitfiles=dol_dir_list($dirroot.'/core/modules/substitutions','files',0,'functions_');
        foreach($substitfiles as $substitfile)
        {
            if (preg_match('/functions_(.*)\.lib\.php/i',$substitfile['name'],$reg))
            {
                $module=$reg[1];
                if (! empty($conf->$module->enabled))   // If module enabled
                {
                    dol_syslog("Library functions_".$module.".lib.php found into ".$dirroot);
                    require_once($dirroot."/core/modules/substitutions/functions_".$module.".lib.php");
                    $function_name=$module."_completesubstitutionarray";
                    $function_name($substitutionarray,$outputlangs,$object);
                }
            }
        }
    }
}

/**
 *    Format output for start and end date
 *
 *    @param	timestamp	$date_start    Start date
 *    @param    timestamp	$date_end      End date
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
 *    @param	timestamp	$date_start    Start date
 *    @param    timestamp	$date_end      End date
 *    @param    string		$format        Output format
 *    @param	Translate	$outputlangs   Output language
 *    @return	string						String
 */
function get_date_range($date_start,$date_end,$format = '',$outputlangs='')
{
    global $langs;

    $out='';

    if (! is_object($outputlangs)) $outputlangs=$langs;

    if ($date_start && $date_end)
    {
        $out.= ' ('.$outputlangs->trans('DateFromTo',dol_print_date($date_start, $format, false, $outputlangs),dol_print_date($date_end, $format, false, $outputlangs)).')';
    }
    if ($date_start && ! $date_end)
    {
        $out.= ' ('.$outputlangs->trans('DateFrom',dol_print_date($date_start, $format, false, $outputlangs)).')';
    }
    if (! $date_start && $date_end)
    {
        $out.= ' ('.$outputlangs->trans('DateUntil',dol_print_date($date_end, $format, false, $outputlangs)).')';
    }

    return $out;
}


/**
 *	Get formated messages to output (Used to show messages on html output).
 *
 *	@param	string		$mesgstring		Message string
 *	@param	array		$mesgarray      Messages array
 *  @param  string		$style          Style of message output ('ok' or 'error')
 *  @param  int			$keepembedded   Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *	@return	string						Return html output
 *
 *  @see    dol_print_error
 *  @see    dol_htmloutput_errors
 */
function get_htmloutput_mesg($mesgstring='',$mesgarray='', $style='ok', $keepembedded=0)
{
    global $conf, $langs;

    $ret='';
    $out='';
    $divstart=$divend='';

    // Use session mesg
    if (isset($_SESSION['mesg']))
    {
    	$mesgstring=$_SESSION['mesg'];
    	unset($_SESSION['mesg']);
    }
	if (isset($_SESSION['mesgarray']))
    {
    	$mesgarray=$_SESSION['mesgarray'];
    	unset($_SESSION['mesgarray']);
    }

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
        if ($conf->use_javascript_ajax && empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && empty($keepembedded))
        {
            $return = '<script type="text/javascript">
    				jQuery(document).ready(function() {
    					jQuery.jnotify("'.dol_escape_js($out).'",
    					"'.($style=="ok" ? 3000 : $style).'",
    					'.($style=="ok" ? "false" : "true").',
    					{ remove: function (){} } );
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
 *	@param	string	$mesgstring		 Message
 *	@param	array	$mesgarray       Messages array
 *  @param  string	$style           Which style to use ('ok', 'warning', 'error')
 *  @param  int		$keepembedded    Set to 1 if message must be kept embedded into its html place (this disable jnotify)
 *  @return	void
 *
 *  @see    dol_print_error
 *  @see    dol_htmloutput_errors
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
 *  @param      array		&$array      		Array to sort
 *  @param      string		$index				Key in array to use for sorting criteria
 *  @param      int			$order				Sort order
 *  @param      int			$natsort			1=use "natural" sort (natsort), 0=use "standard sort (asort)
 *  @param      int			$case_sensitive		1=sort is case sensitive, 0=not case sensitive
 *  @return     array							Sorted array
 */
function dol_sort_array(&$array, $index, $order='asc', $natsort=0, $case_sensitive=0)
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
        foreach(array_keys($temp) as $key) (is_numeric($key))? $sorted[]=$array[$key] : $sorted[$key]=$array[$key];
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
 *      Return an UTF-8 string encoded into OS filesystem encoding. This function is used to define
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
 *      Return an id or code from a code or id. Store Code-Id in a cache.
 *
 * 		@param	DoliDB	$db			Database handler
 * 		@param	string	$key		Code to get Id
 * 		@param	string	$tablename	Table name without prefix
 * 		@param	string	$fieldkey	Field for code
 * 		@param	string	$fieldid	Field for id
 *      @return int					Id of code
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

    $sql = "SELECT ".$fieldid." as id";
    $sql.= " FROM ".MAIN_DB_PREFIX.$tablename;
    $sql.= " WHERE ".$fieldkey." = '".$key."'";
    dol_syslog('dol_getIdFromCode sql='.$sql,LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);
        if ($obj) $cache_codes[$tablename][$key]=$obj->id;
        else $cache_codes[$tablename][$key]='';
        $db->free($resql);
        return $cache_codes[$tablename][$key];
    }
    else
    {
        dol_syslog("dol_getIdFromCode error=".$db->lasterror(),LOG_ERR);
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
 * This function is called by verifCond()
 *
 * @param 	string	$s		String to evaluate
 * @return	mixed			Result of eval
 */
function dol_eval($s)
{
    // Only global variables can be changed by eval function and returned to caller
    global $langs, $user, $conf;
    global $leftmenu;
    global $rights;

    //print $s."<br>\n";
    eval($s);
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
    $ret='';
    if (! empty($codelang))
    {
        if ($codelang == 'auto') $ret=img_picto('',DOL_URL_ROOT.'/theme/common/flags/int.png','',1);
        else {
            //print $codelang;
            $langtocountryflag=array('ar_AR'=>'','ca_ES'=>'catalonia','da_DA'=>'dk','fr_CA'=>'mq','sv_SV'=>'se');
            $tmpcode='';
            if (isset($langtocountryflag[$codelang])) $tmpcode=$langtocountryflag[$codelang];
            else
            {
                $tmparray=explode('_',$codelang);
                $tmpcode=empty($tmparray[1])?$tmparray[0]:$tmparray[1];
            }
            if ($tmpcode) $ret.=img_picto($codelang,DOL_URL_ROOT.'/theme/common/flags/'.strtolower($tmpcode).'.png','',1);
        }
    }
    return $ret;
}

/**
 *  Complete or removed entries into a head array (used to build tabs) with value added by external modules.
 *  Such values are declared into $conf->tabs_modules.
 *
 *  @param	Conf		$conf           Object conf
 *  @param  Translate	$langs          Object langs
 *  @param  Object		$object         Object object
 *  @param  array		&$head          Object head
 *  @param  int			&$h             New position to fill
 *  @param  string		$type           Value for object where objectvalue can be
 *                              		'thirdparty'       to add a tab in third party view
 *		                              	'intervention'     to add a tab in intervention view
 *     		                         	'supplier_order'   to add a tab in supplier order view
 *          		                    'supplier_invoice' to add a tab in supplier invoice view
 *                  		            'invoice'          to add a tab in customer invoice view
 *                          		    'order'            to add a tab in customer order view
 *                      		        'product'          to add a tab in product view
 *                              		'propal'           to add a tab in propal view
 *                              		'user'             to add a tab in user view
 *                              		'group'            to add a tab in group view
 * 		        	                    'member'           to add a tab in fundation member view
 *      		                        'categories_x'	   to add a tab in category view ('x': type of category (0=product, 1=supplier, 2=customer, 3=member)
 *  @param  string		$mode  	        'add' to complete head, 'remove' to remove entries
 *	@return	void
 */
function complete_head_from_modules($conf,$langs,$object,&$head,&$h,$type,$mode='add')
{
	if (is_array($conf->tabs_modules[$type]))
	{
		foreach ($conf->tabs_modules[$type] as $value)
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
						$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[5]),1);
						$head[$h][1] = $langs->trans($values[2]);
						$head[$h][2] = str_replace('+','',$values[1]);
						$h++;
					}
				}
				else if (count($values) == 5)       // new declaration
				{
					if ($values[0] != $type) continue;
					if ($values[3]) $langs->load($values[3]);
					$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[4]),1);
					$head[$h][1] = $langs->trans($values[2]);
					$head[$h][2] = str_replace('+','',$values[1]);
					$h++;
				}
				else if (count($values) == 4)   // old declaration, for backward compatibility
				{
					if ($values[0] != $type) continue;
					if ($values[2]) $langs->load($values[2]);
					$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[3]),1);
					$head[$h][1] = $langs->trans($values[1]);
					$head[$h][2] = 'tab'.$values[1];
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
}

/**
 * Print common footer :
 * 		conf->global->MAIN_HTML_FOOTER
 * 		conf->global->MAIN_GOOGLE_AN_ID
 * 		DOL_TUNING
 * 		conf->logbuffer
 *
 * @param	string	$zone	'private' (for private pages) or 'public' (for public pages)
 * @return	void
 */
function printCommonFooter($zone='private')
{
    global $conf;
	global $micro_start_time;

    if ($zone == 'private') print "\n".'<!-- Common footer for private page -->'."\n";
    else print "\n".'<!-- Common footer for public page -->'."\n";

    if (! empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER."\n";

    // Google Analytics (need Google module)
    if (! empty($conf->global->MAIN_GOOGLE_AN_ID))
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

    // End of tuning
    if (! empty($_SERVER['DOL_TUNING']))
    {
        $micro_end_time=dol_microtime_float(true);
        print "\n".'<script type="text/javascript">'."\n";
        print 'console.log("';
        if (! empty($conf->global->MEMCACHED_SERVER)) print 'MEMCACHED_SERVER='.$conf->global->MEMCACHED_SERVER.' - ';
        print 'MAIN_OPTIMIZE_SPEED='.(isset($conf->global->MAIN_OPTIMIZE_SPEED)?$conf->global->MAIN_OPTIMIZE_SPEED:'off');
        print ' - Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
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
        print '")'."\n";
        print '</script>'."\n";

        // Add Xdebug coverage of code
        if (defined('XDEBUGCOVERAGE')) {
            var_dump(xdebug_get_code_coverage());
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

}


if (! function_exists('getmypid'))
{
    /**
     * Return PID
     *
     * @return	void
     */
    function getmypid()
    {
        return rand(1,32768);
    }
}

?>
