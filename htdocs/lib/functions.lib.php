<?php
/* Copyright (C) 2000-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *	\file			htdocs/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 *	\version		$Id: functions.lib.php,v 1.552 2011/08/04 22:01:23 eldy Exp $
 */

// For compatibility during upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	    define('DOL_DOCUMENT_ROOT', '..');
if (! defined('ADODB_DATE_VERSION'))    include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


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

		for ($i=0; $i<strlen($json); $i++)
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
 *  This function output memory used by PHP and exit everything. Used for debugging purpose.
 */
function dol_stopwithmem()
{
    print memory_get_usage();
    llxFooter();
    exit;
}

/**
 *  Function called at end of web php process
 */
function dol_shutdown()
{
    global $conf,$user,$langs,$db;
    $disconnectdone=false;
    if (is_object($db) && ! empty($db->connected)) $disconnectdone=$db->close();
    dol_syslog("--- End access to ".$_SERVER["PHP_SELF"].($disconnectdone?' (Warn: db disconnection forced)':''));
}

/**
 *  Return value of a param into GET or POST supervariable
 *  @param          paramname   Name of parameter to found
 *  @param			check		Type of check (''=no check,  'int'=check it's numeric, 'alpha'=check it's alpha only)
 *  @param			method		Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get)
 *  @return         string      Value found or '' if check fails
 */
function GETPOST($paramname,$check='',$method=0)
{
    if (empty($method)) $out = isset($_GET[$paramname])?$_GET[$paramname]:(isset($_POST[$paramname])?$_POST[$paramname]:'');
    elseif ($method==1) $out = isset($_GET[$paramname])?$_GET[$paramname]:'';
    elseif ($method==2) $out = isset($_POST[$paramname])?$_POST[$paramname]:'';
    elseif ($method==3) $out = isset($_POST[$paramname])?$_POST[$paramname]:(isset($_GET[$paramname])?$_GET[$paramname]:'');

    if (!empty($check))
    {
        // Check if numeric
        if ($check == 'int' && ! preg_match('/^[\.,0-9]+$/i',trim($out))) $out='';
        // Check if alpha
        //if ($check == 'alpha' && ! preg_match('/^[ =:@#\/\\\(\)\-\._a-z0-9]+$/i',trim($out))) $out='';
        if ($check == 'alpha' && preg_match('/"/',trim($out))) $out='';    // Only " is dangerous because param in url can close the href= or src= and add javascript functions
    }

    return $out;
}


/**
 *  Return a prefix to use for this Dolibarr instance for session or cookie names.
 *  This prefix is unique for instance and avoid conflict between multi-instances Dolibarrs,
 *  even when having two instances with one root dir or two instances in virtual servers.
 *  @return         string      A calculated prefix
 */
function dol_getprefix()
{
    return md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT);
}

/**
 *	Make an include_once using default root and alternate root if it fails.
 *	WARNING: In most cases, you should not use this function:
 *  To link to a core file, use include(DOL_DOCUMENT_ROOT.'/pathtofile')
 *  To link to a module file from a module file, use include('./mymodulefile');
 *  To link to a module file from a core file, then this function can be used.
 * 	@param			relpath		Relative path to file (Ie: mydir/myfile, ../myfile, ...)
 *  @return         int			false if include fails.
 */
function dol_include_once($relpath)
{
    global $conf,$langs,$user,$mysoc;   // Other global var must be retreived with $GLOBALS['var']
    return @include_once(dol_buildpath($relpath));
}


/**
 *	Return path of url or filesystem. Return default_root or alternate root if file_exist fails.
 * 	@param			path		Relative path to file (if mode=0, ie: mydir/myfile, ../myfile, ...) or relative url (if mode=1).
 *  @param			type		0=Used for a Filesystem path, 1=Used for an URL path (output relative), 2=Used for an URL path (output full path)
 *  @return         string		Full filsystem path (if mode=0), Full url path (if mode=1)
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
 * 	This function works for both PHP4 and PHP5.
 * 	@param			object		Object to clone
 *	@return         date		Timestamp
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
 * 	@param			size		Size we want
 * 	@param			type		Type of optimizing(''=Optimize for a truncate, 'width'=Optimize for screen width)
 *	@return         int			New size after optimizing
 */
function dol_size($size,$type='')
{
    global $conf;
    if (empty($conf->browser->phone)) return $size;
    if ($type == 'width') return 250;
    else return 10;
}


/**
 *	Return date for now. We should always use this function without parameters (that means GMT time).
 * 	@param			mode		'gmt' => we return GMT timestamp,
 * 								'tzserver' => we add the PHP server timezone
 *  							'tzref' => we add the company timezone
 * 								'tzuser' => we add the user timezone
 *	@return         date		Timestamp
 */
function dol_now($mode='gmt')
{
    // Note that gmmktime and mktime return same value (GMT) whithout parameters
    if ($mode == 'gmt') $ret=gmmktime();	// Time for now at greenwich.
    else if ($mode == 'tzserver')			// Time for now with PHP server timezone added
    {
        $tzsecond=-dol_mktime(0,0,0,1,1,1970);
        $ret=gmmktime()+$tzsecond;
    }
    else if ($mode == 'tzref')				// Time for now where parent company timezone is added
    {
        // TODO Should add the company timezone
        $ret=gmmktime();
    }
    else if ($mode == 'tzuser')				// Time for now where user timezone is added
    {
        //print 'eeee'.time().'-'.mktime().'-'.gmmktime();
        $tzhour=isset($_SESSION['dol_tz'])?$_SESSION['dol_tz']:0;
        $ret=gmmktime()+($tzhour*60*60);
    }
    return $ret;
}


/**
 *	Clean a string to use it as a file name.
 *	@param          str             String to clean
 * 	@param			newstr			String to replace bad chars with
 *	@return         string          String cleaned (a-zA-Z_)
 * 	@see        	dol_string_nospecial, dol_string_unaccent
 */
function dol_sanitizeFileName($str,$newstr='_')
{
    global $conf;
    return dol_string_nospecial(dol_string_unaccent($str),$newstr,$conf->filesystem_forbidden_chars);
}

/**
 *	Clean a string from all accent characters to be used as ref, login or by dol_sanitizeFileName.
 *	@param          str             String to clean
 *	@return         string          Cleaned string
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
        $string = strtr($str,
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
	        uuuyy");
        $string = strtr($string, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH", "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));
        return $string;
    }
}

/**
 *	Clean a string from all punctuation characters to use it as a ref or login.
 *	@param          str             String to clean
 * 	@param			newstr			String to replace forbidden chars with
 *  @param          badchars        List of forbidden characters
 * 	@return         string          Cleaned string
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
 *  @param       $stringtoescape	String to escape
 *  @return      string      		Escaped string
 */
function dol_escape_js($stringtoescape)
{
    // escape quotes and backslashes, newlines, etc.
    $substitjs=array("&#039;"=>"\\'",'\\'=>'\\\\',"'"=>"\\'",'"'=>"\\'","\r"=>'\\r',"\n"=>'\\n','</'=>'<\/');
    return strtr($stringtoescape, $substitjs);
}


/**
 *  Returns text escaped for inclusion in HTML alt or title tags
 *  @param      $stringtoescape		String to escape
 *  @param		$keepb				Do not clean b tags
 *  @return     string      		Escaped string
 */
function dol_escape_htmltag($stringtoescape,$keepb=0)
{
    // escape quotes and backslashes, newlines, etc.
    $tmp=dol_html_entity_decode($stringtoescape,ENT_COMPAT,'UTF-8');
    if ($keepb) $tmp=strtr($tmp, array('"'=>'',"\r"=>'\\r',"\n"=>'\\n'));
    else $tmp=strtr($tmp, array('"'=>'',"\r"=>'\\r',"\n"=>'\\n',"<b>"=>'','</b>'=>''));
    return dol_htmlentities($tmp,ENT_COMPAT,'UTF-8');
}

/* For backward compatiblity */
function dolibarr_syslog($message, $level=LOG_INFO)
{
    return dol_syslog($message, $level);
}

/**
 *	\brief      Write log message in a file or to syslog process
 *				Pour fichier:   	fichier defined by SYSLOG_FILE
 *				Pour syslog:    	facility defined by SYSLOG_FACILITY
 * 				Warning, les fonctions syslog sont buggues sous Windows et generent des
 *				fautes de protection memoire. Pour resoudre, utiliser le loggage fichier,
 *				au lieu du loggage syslog (configuration du module).
 *				Si SYSLOG_FILE_NO_ERROR defini, on ne gere pas erreur ecriture log
 * 	\param      message		    	Line to log. Ne doit pas etre traduit si level = LOG_ERR
 *	\param      level           	Log level
 *	\remarks	This function works only if syslog module is enabled.
 * 	\remarks	This must must not use any call to other function calling dol_syslog (avoid infinite loop).
 *	\remarks	On Windows LOG_ERR=4, LOG_WARNING=5, LOG_NOTICE=LOG_INFO=6, LOG_DEBUG=6 si define_syslog_variables ou PHP 5.3+, 7 si dolibarr
 *				On Linux   LOG_ERR=3, LOG_WARNING=4, LOG_INFO=6, LOG_DEBUG=7
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

        // Check if log is to a file (SYSLOG_FILE defined) or to syslog
        if (defined("SYSLOG_FILE") && SYSLOG_FILE)
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
                // Do not use call to functions that make call to dol_syslog, so no call to langs.
                print "Error, failed to open file ".$filelog."\n";
            }
        }
        else
        {
            if (function_exists('openlog'))	// This function does not exists on some ISP (Ex: Free in France)
            {
                $facility = LOG_USER;

                if (defined("SYSLOG_FACILITY") && SYSLOG_FACILITY)
                {
                    // Exemple: SYSLOG_FACILITY vaut LOG_USER qui vaut 8. On a besoin de 8 dans $facility.
                    $facility = constant("SYSLOG_FACILITY");
                }

                openlog("dolibarr", LOG_PID | LOG_PERROR, (int) $facility);		// (int) is required to avoid error parameter 3 expected to be long

                if (! $level)
                {
                    syslog(LOG_ERR, $message);
                }
                else
                {
                    syslog($level, $message);
                }

                closelog();
            }
        }
    }
}


/**
 *	Show tab header of a card
 *	@param	    links		Array of tabs
 *	@param	    active      Active tab name
 *	@param      title       Title
 *	@param      notab		0=Add tab header, 1=no tab header
 * 	@param		picto		Add a picto on tab title
 */
function dol_fiche_head($links=array(), $active='0', $title='', $notab=0, $picto='')
{
    print dol_get_fiche_head($links, $active, $title, $notab, $picto);
}

/**
 *  Show tab header of a card
 *  @param      links       Array of tabs
 *  @param      active      Active tab name
 *  @param      title       Title
 *  @param      notab       0=Add tab header, 1=no tab header
 *  @param      picto       Add a picto on tab title
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
        if (sizeof($keys)) $maxkey=max($keys);
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
 *  @param      notab       0=Add tab footer, 1=no tab footer
 */
function dol_fiche_end($notab=0)
{
    print dol_get_fiche_end($notab);
}

/**
 *	Return tab footer of a card
 *	@param      notab		0=Add tab footer, 1=no tab footer
 */
function dol_get_fiche_end($notab=0)
{
    if (! $notab) return "\n</div>\n";
    else return '';
}


/* For backward compatibility */
function dolibarr_print_date($time,$format='',$to_gmt=false,$outputlangs='',$encodetooutput=false)
{
    return dol_print_date($time,$format,$to_gmt,$outputlangs,$encodetooutput);
}

/**
 *	Output date in a string format according to outputlangs (or langs if not defined).
 * 	Return charset is always UTF-8, except if encodetoouput is defined. In this cas charset is output charset.
 *	@param	    time        	GM Timestamps date (or deprecated strings 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS')
 *	@param	    format      	Output date format
 *								"%d %b %Y",
 *								"%d/%m/%Y %H:%M",
 *								"%d/%m/%Y %H:%M:%S",
 *								"day", "daytext", "dayhour", "dayhourldap", "dayhourtext"
 * 	@param		tzoutput		true=output or 'gmt' => string is for Greenwich location
 * 								false or 'tzserver' => output string is for local PHP server TZ usage
 * 								'tzuser' => output string is for local browser TZ usage
 *	@param		outputlangs		Object lang that contains language for text translation.
 *  @param      encodetooutput  false=no convert into output pagecode
 * 	@return     string      	Formated date or '' if time is null
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
            if ($tzoutput == 'tzuser')
            {
                $to_gmt=true;
                $offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
                $offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
            }
            if ($tzoutput == 'tzcompany')
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
 *	Convert a string date into a GM Timestamps date
 *	@param		string			Date in a string
 *				                YYYYMMDD
 *	                 			YYYYMMDDHHMMSS
 *								YYYY-MM-DDTHH:MM:SSZ (RFC3339)
 *		                		DD/MM/YY or DD/MM/YYYY (this format should not be used anymore)
 *		                		DD/MM/YY HH:MM:SS or DD/MM/YYYY HH:MM:SS (this format should not be used anymore)
 *		                		19700101020000 -> 7200
 *  @param      gm              1=Input date is GM date, 0=Input date is local date
 *  @return		date			Date
 *  @see        dol_print_date, dol_mktime, dol_getdate
 */
function dol_stringtotime($string, $gm=1)
{
    if (preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)\s?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i',$string,$reg))
    {
        // This part of code should not be used.
        dol_syslog("Functions.lib::dol_stringtotime call to function with deprecated parameter", LOG_WARNING);
        // Date est au format 'DD/MM/YY' ou 'DD/MM/YY HH:MM:SS'
        // Date est au format 'DD/MM/YYYY' ou 'DD/MM/YYYY HH:MM:SS'
        $sday = $reg[1];
        $smonth = $reg[2];
        $syear = $reg[3];
        $shour = $reg[4];
        $smin = $reg[5];
        $ssec = $reg[6];
        if ($syear < 50) $syear+=1900;
        if ($syear >= 50 && $syear < 100) $syear+=2000;
        $string=sprintf("%04d%02d%02d%02d%02d%02d",$syear,$smonth,$sday,$shour,$smin,$ssec);
    }
    // Convert date RFC3339
    else if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})Z$/i',$string,$reg))
    {
        $syear = $reg[1];
        $smonth = $reg[2];
        $sday = $reg[3];
        $shour = $reg[4];
        $smin = $reg[5];
        $ssec = $reg[6];
        $string=sprintf("%04d%02d%02d%02d%02d%02d",$syear,$smonth,$sday,$shour,$smin,$ssec);
    }

    $string=preg_replace('/([^0-9])/i','',$string);
    $tmp=$string.'000000';
    $date=dol_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4),$gm);
    return $date;
}


/**
 *	Return an array with date info
 *  PHP getdate is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows.
 *	@param		timestamp		Timestamp
 *	@param		fast			Fast mode
 *	@return		array			Array of informations
 *				If no fast mode:
 *				'seconds' => $secs,
 *				'minutes' => $min,
 *				'hours' => $hour,
 *				'mday' => $day,
 *				'wday' => $dow,
 *				'mon' => $month,
 *				'year' => $year,
 *				'yday' => floor($secsInYear/$_day_power),
 *				'weekday' => gmdate('l',$_day_power*(3+$dow)),
 *				'month' => gmdate('F',mktime(0,0,0,$month,2,1971)),
 *				If fast mode:
 *				'seconds' => $secs,
 *				'minutes' => $min,
 *				'hours' => $hour,
 *				'mday' => $day,
 *				'mon' => $month,
 *				'year' => $year,
 *				'yday' => floor($secsInYear/$_day_power),
 *				'leap' => $leaf,
 *				'ndays' => $ndays
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

/* For backward compatibility */
function dolibarr_mktime($hour,$minute,$second,$month,$day,$year,$gm=false,$check=1)
{
    return dol_mktime($hour,$minute,$second,$month,$day,$year,$gm,$check);
}

/**
 *	Return a timestamp date built from detailed informations (by default a local PHP server timestamp)
 * 	Replace function mktime not available under Windows if year < 1970
 *	PHP mktime is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 * 	@param		hour			Hour	(can be -1 for undefined)
 *	@param		minute			Minute	(can be -1 for undefined)
 *	@param		second			Second	(can be -1 for undefined)
 *	@param		month			Month
 *	@param		day				Day
 *	@param		year			Year
 *	@param		gm				1=Input informations are GMT values, otherwise local to server TZ
 *	@param		check			0=No check on parameters (Can use day 32, etc...)
 *  @param		isdst			Dayling saving time
 *	@return		timestamp		Date as a timestamp, '' if error
 * 	@see 		dol_print_date, dol_stringtotime
 */
function dol_mktime($hour,$minute,$second,$month,$day,$year,$gm=false,$check=1,$isdst=true)
{
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

    $usealternatemethod=false;
    if ($year <= 1970) $usealternatemethod=true;		// <= 1970
    if ($year >= 2038) $usealternatemethod=true;		// >= 2038

    if ($usealternatemethod || $gm)	// Si time gm, seule adodb peut convertir
    {
        /*
         // On peut utiliser strtotime pour obtenir la traduction.
         // strtotime is ok for range: Friday 13 December 1901 20:45:54 GMT to Tuesday 19 January 2038 03:14:07 GMT.
         $montharray=array(1=>'january',2=>'february',3=>'march',4=>'april',5=>'may',6=>'june',
         7=>'july',8=>'august',9=>'september',10=>'october',11=>'november',12=>'december');
         $string=$day." ".$montharray[0+$month]." ".$year." ".$hour.":".$minute.":".$second." GMT";
         $date=strtotime($string);
         print "- ".$string." ".$date." -";
         */
        $date=adodb_mktime($hour,$minute,$second,$month,$day,$year,$isdst,$gm);
    }
    else
    {
        $date=mktime($hour,$minute,$second,$month,$day,$year);
    }
    return $date;
}


/* For backward compatibility */
function dolibarr_date($fmt, $timestamp, $gm=false)
{
    return dol_date($fmt, $timestamp, $gm);
}

/**
 *	Returns formated date
 *	@param		fmt				Format (Exemple: 'Y-m-d H:i:s')
 *	@param		timestamp		Date. Example: If timestamp=0 and gm=1, return 01/01/1970 00:00:00
 *	@param		gm				1 if timestamp was built with gmmktime, 0 if timestamp was build with mktime
 *	@return		string			Formated date
 *  @deprecated Replaced by dol_print_date
 */
function dol_date($fmt, $timestamp, $gm=false)
{
    $usealternatemethod=false;
    if ($timestamp <= 0) $usealternatemethod=true;
    if ($timestamp >= 2145913200) $usealternatemethod=true;

    if ($usealternatemethod || $gm)	// Si time gm, seule adodb peut convertir
    {
        $string=adodb_date($fmt,$timestamp,$gm);
    }
    else
    {
        $string=date($fmt,$timestamp);
    }

    return $string;
}


/**
 * Return string with formated size
 * @param		size		Size to print
 * @param		shortvalue	Tell if we want long value to use another unit (Ex: 1.5Kb instead of 1500b)
 * @param		shortunit	Use short value of size unit
 * @return		string		Link
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
 * @param		url			Url to show
 * @param		target		Target for link
 * @param		max			Max number of characters to show
 * @return		string		HTML Link
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
 * @param		email		EMail to show (only email, without 'Name of recipient' before)
 * @param 		cid 		Id of contact if known
 * @param 		socid 		Id of third party if known
 * @param 		addlink		0=no link to create action
 * @param		max			Max number of characters to show
 * @param		showinvalid	Show warning if syntax email is wrong
 * @return		string		HTML Link
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

/* For backward compatibility */
function dolibarr_print_phone($phone,$country="FR",$cid=0,$socid=0,$addlink=0,$separ="&nbsp;")
{
    return dol_print_phone($phone,$country,$cid,$socid,$addlink,$separ);
}

/**
 * 	Format phone numbers according to country
 * 	@param 		phone 		Phone number to format
 * 	@param 		country 	Country to use for formatting
 * 	@param 		cid 		Id of contact if known
 * 	@param 		socid 		Id of third party if known
 * 	@param 		addlink		0=no link to create action
 * 	@param 		separ 		separation between numbers for a better visibility example : xx.xx.xx.xx.xx
 * 	@return 	string 		Formated phone number
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
 * 	@param 		ip			IP
 * 	@param		mode		1=return only country/flag,2=return only IP
 * 	@return 	string 		Formated IP, with country if GeoIP module is enabled
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

        include_once(DOL_DOCUMENT_ROOT.'/lib/dolgeoip.class.php');
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
 *  @return     string      country code (fr, es, it, us, ...)
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
        include_once(DOL_DOCUMENT_ROOT.'/lib/dolgeoip.class.php');
        $geoip=new DolGeoIP('country',$datafile);
        $countrycode=$geoip->getCountryCodeFromIP($ip);
        $ret=$countrycode;
    }
    return $ret;
}

/**
 *  Format address string
 *  @param      address     Address
 *  @param      htmlid      Html ID
 *  @param      mode        thirdparty|contact|member|other
 *  @param      id          Id of object
 *  @param      address     Address string
 */
function dol_print_address($address, $htmlid='gmap', $mode, $id)
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
 *	Return true if email syntax is ok.
 *	@param	    address     email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
 *	@return     boolean     true if email syntax is OK, false if KO or empty string
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
 *  Return true if phone number syntax is ok.
 *  @param      address     phone (Ex: "0601010101")
 *  @return     boolean     true if phone syntax is OK, false if KO or empty string
 */
function isValidPhone($address)
{
    return true;
}


/**
 * Make a strlen call. Works even if mbstring module not enabled.
 * @param   $string
 * @param   $stringencoding
 * @return  int
 */
function dol_strlen($string,$stringencoding='UTF-8')
{
    //    print $stringencoding."xxx";
    //    $stringencoding='rrr';
    if (function_exists('mb_strlen')) return mb_strlen($string,$stringencoding);
    else return strlen($string);
}

/**
 * Make a substring. Works even in mbstring module not enabled
 * @param   $string
 * @param   $start
 * @param   $length
 * @param   $stringencoding
 * @return  string
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


/* For backward compatibility */
function dolibarr_trunc($string,$size=40,$trunc='right',$stringencoding='')
{
    return dol_trunc($string,$size,$trunc,$stringencoding);
}


/**
 *  Show a javascript graph
 *  @param      htmlid          Html id name
 *  @param      width           Width in pixel
 *  @param      height          Height in pixel
 *  @param      data            Data array
 *  @param      showlegend      1 to show legend, 0 otherwise
 *  @param      type            Type of graph ('pie', 'barline')
 *  @param      showpercent     Show percent (with type='pie' only)
 *  @param      url             Param to add an url to click values
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
            // serieX is array('label'=>'label', values=>array(0=>val))
        	print '
        	<script type="text/javascript">
			jQuery(function () {
        	var data = ['."\n";
            $i=0;
            foreach($data['series'] as $serie)
            {
                //print '{ label: "'.($showlegend?$serie['values'][0]:$serie['label'].'<br>'.$serie['values'][0]).'", data: '.$serie['values'][0].' }';
                print '{ label: "'.dol_escape_js($serie['label']).'", data: '.$serie['values'][0].' }';
                if ($i < sizeof($data['series'])) print ',';
                print "\n";
                $i++;
            }
            print '];

            function plotWithOptions() {
                jQuery.plot(jQuery("#'.$htmlid.'"), data,
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
                    } },
                    zoom: {
                        interactive: true
                    },
                    pan: {
                        interactive: true
                    },
                    ';
                    $i=0; $outputserie=0;
            		if (sizeof($datacolor))
            		{
	                    print 'colors: [';
	                    foreach($datacolor as $val)
	                    {
                            if ($outputserie > 0) print ',';
	                        print '"'.(empty($data['seriescolor'][$i])?$val:$data['seriescolor'][$i]).'"';
	                        $outputserie++;
	                        $i++;
	                    }
            			print '], ';
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
            // serieX is array('label'=>'label', values=>array(0=>y1,1=>y2,...)) with same nb of value than into xlabel
            print '
            <script type="text/javascript">
            jQuery(function () {
            var data = [';
            $i=1; $outputserie=0;
            foreach($data['series'] as $serie)
            {
                if ($data['seriestype'][$i-1]=='line') { $i++; continue; };
                if ($outputserie > 0) print ',';
                print '{ bars: { stack: 0, show: true, barWidth: 0.9, align: \'center\' }, label: \''.dol_escape_js($serie['label']).'\', data: [';
                $j=1;
                foreach($serie['values'] as $val)
                {
                    print '['.$j.','.$val.']';
                    if ($j < sizeof($serie['values'])) print ', ';
                    $j++;
                }
                print ']}'."\n";
                $outputserie++;
                $i++;
            }
            if ($outputserie) print ', ';
            //print '];
            //var datalines = [';
            $i=1; $outputserie=0;
            foreach($data['series'] as $serie)
            {
                if (empty($data['seriestype'][$i-1]) || $data['seriestype'][$i-1]=='bar') { $i++; continue; };
                if ($outputserie > 0) print ',';
                print '{ lines: { show: true }, label: \''.dol_escape_js($serie['label']).'\', data: [';
                $j=1;
                foreach($serie['values'] as $val)
                {
                    print '['.$j.','.$val.']';
                    if ($j < sizeof($serie['values'])) print ', ';
                    $j++;
                }
                print ']}'."\n";
                $outputserie++;
                $i++;
            }
            print '];
            var dataticks = [';
            $i=1;
            foreach($data['xlabel'] as $label)
            {
                print '['.$i.',\''.$label.'\']';
                if ($i < sizeof($data['xlabel'])) print ',';
                $i++;
            }
            print '];

            function plotWithOptions() {
                jQuery.plot(jQuery("#'.$htmlid.'"), data,
                {
                    series: {
                            stack: 0
                    },
                    zoom: {
                        interactive: true
                    },
                    pan: {
                        interactive: true
                    },
                    ';
                    if (sizeof($datacolor))
                    {
                        print 'colors: [';
                        $j=0;
                        foreach($datacolor as $val)
                        {
                            print '"'.$val.'"';
                            if ($j < sizeof($datacolor)) print ',';
                            $j++;
                        }
                        print '], ';
                    }
                    print 'legend: {show: '.($showlegend?'true':'false').'},
                    xaxis: {ticks: dataticks},
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
 *	@param      string				String to truncate
 *	@param      size				Max string size. 0 for no limit.
 *	@param		trunc				Where to trunc: right, left, middle, wrap
 * 	@param		stringencoding		Tell what is source string encoding
 *	@return     string				Truncated string
 */
function dol_trunc($string,$size=40,$trunc='right',$stringencoding='UTF-8')
{
    global $conf;

    if ($size==0) return $string;
    if (empty($conf->global->MAIN_DISABLE_TRUNC))
    {
        // We go always here
        if ($trunc == 'right')
        {
            $newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
            if (dol_strlen($newstring,$stringencoding) > ($size+1))
            return dol_substr($newstring,0,$size,$stringencoding).'...';
            else
            return $string;
        }
        if ($trunc == 'middle')
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
        if ($trunc == 'left')
        {
            $newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
            if (dol_strlen($newstring,$stringencoding) > ($size+1))
            return '...'.dol_substr($newstring,dol_strlen($newstring,$stringencoding) - $size,$size,$stringencoding);
            else
            return $string;
        }
        if ($trunc == 'wrap')
        {
            $newstring=dol_textishtml($string)?dol_string_nohtmltag($string,1):$string;
            if (dol_strlen($newstring,$stringencoding) > ($size+1))
            return dol_substr($newstring,0,$size,$stringencoding)."\n".dol_trunc(dol_substr($newstring,$size,dol_strlen($newstring,$stringencoding)-$size,$stringencoding),$size,$trunc);
            else
            return $string;
        }
    }
    else
    {
        return $string;
    }
}


/**
 *	Show a picto called object_picto (generic function)
 *	@param      alt                 Text of alt on image
 *	@param      picto               Name of image to show object_picto (example: user, group, action, bill, contract, propal, product, ...)
 *							        For external modules use imagename@mymodule to search into directory "img" of module.
 *  @param      options             Add more attribute on img tag
 *  @param      pictoisfullpath     If 1, image path is a full path
 *	@return     string              Return img tag
 *  @see        img_picto, img_picto_common
 */
function img_object($alt, $picto, $options='', $pictoisfullpath=0)
{
    global $conf,$langs;

    $path = 'theme/'.$conf->theme;
    $url = DOL_URL_ROOT;

    if (preg_match('/^([^@]+)@([^@]+)$/i',$picto,$regs))
    {
        $picto = $regs[1];
        $path = $regs[2];
        if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
        // If img file not into standard path, we use alternate path
        if (defined('DOL_URL_ROOT_ALT') && DOL_URL_ROOT_ALT && ! file_exists(DOL_DOCUMENT_ROOT.'/'.$path.'/img/object_'.$picto)) $url = DOL_URL_ROOT_ALT;
    }
    else
    {
        if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
    }
    if ($pictoisfullpath) return '<img src="'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
    return '<img src="'.$url.'/'.$path.'/img/object_'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	Show picto whatever it's its name (generic function)
 *	@param      alt         		Text on alt and title of image
 *	@param      picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into img directory.
 *                                  Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                  Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                  Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *	@param		options				Add more attribute on img tag
 *	@param		pictoisfullpath		If 1, image path is a full path
 *  @return     string              Return img tag
 *  @see        img_object, img_picto_common
 */
function img_picto($alt, $picto, $options='', $pictoisfullpath=0)
{
    global $conf;

    $path =  'theme/'.$conf->theme;
    $url = DOL_URL_ROOT;

    if (preg_match('/^([^@]+)@([^@]+)$/i',$picto,$regs))
    {
        $picto = $regs[1];
        $path = $regs[2];
        if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
        // If img file not into standard path, we use alternate path
        if (defined('DOL_URL_ROOT_ALT') && DOL_URL_ROOT_ALT && ! file_exists(DOL_DOCUMENT_ROOT.'/'.$path.'/img/'.$picto)) $url = DOL_URL_ROOT_ALT;
    }
    else
    {
        if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto.='.png';
    }
    if ($pictoisfullpath) return '<img src="'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
    return '<img src="'.$url.'/'.$path.'/img/'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	Show picto (generic function)
 *	@param      alt         		Text on alt and title of image
 *	@param      picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		options				Add more attribute on img tag
 *	@param		pictoisfullpath		If 1, image path is a full path
 *	@return     string      		Return img tag
 *  @see        img_object, img_picto
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
 *	@param      alt         Text for image alt and title
 *	@param      numaction   Action to show
 *	@return     string      Return an img tag
 */
function img_action($alt = "default", $numaction)
{
    global $conf,$langs;
    if ($alt=="default") {
        if ($numaction == -1) $alt=$langs->trans("ChangeDoNotContact");
        if ($numaction == 0)  $alt=$langs->trans("ChangeNeverContacted");
        if ($numaction == 1)  $alt=$langs->trans("ChangeToContact");
        if ($numaction == 2)  $alt=$langs->trans("ChangeContactInProcess");
        if ($numaction == 3)  $alt=$langs->trans("ChangeContactDone");
    }
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm'.$numaction.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}


/**
 *	Affiche logo fichier
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_file($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Show");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/file.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo refresh
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_refresh($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Refresh");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo dossier
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_folder($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Dossier");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/folder.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo nouveau fichier
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_file_new($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Show");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *  Affiche logo pdf
 *  @param      alt         Texte sur le alt de l'image
 *  @param      $size       Taille de l'icone : 3 = 16x16px , 2 = 14x14px
 *  @return     string      Retourne tag img
 */
function img_pdf($alt = "default",$size=3)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Show");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf'.$size.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo +
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_edit_add($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Add");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_add.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}
/**
 *	Affiche logo -
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_edit_remove($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Remove");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_remove.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo editer/modifier fiche
 *	@param      alt         Texte sur le alt de l'image
 *	@param      float       Si il faut y mettre le style "float: right"
 *	@param      other		Add more attributes on img
 *	@return     string      Retourne tag img
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
 *	Affiche logo voir fiche
 *	@param      alt         Texte sur le alt de l'image
 *	@param      float       Si il faut y mettre le style "float: right"
 *	@param      other		Add more attributes on img
 *	@return     string      Retourne tag img
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
 *  @param      alt         Texte sur le alt de l'image
 *  @return     string      Retourne tag img
 */
function img_delete($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Delete");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}


/**
 *	Show help logo with cursor "?"
 * 	@param		usehelpcursor
 * 	@param		usealttitle		Texte to use as alt title
 * 	@return     string      	Retourne tag img
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
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_info($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Informations");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo warning
 *	@param      alt         Texte sur le alt de l'image
 *	@param      float       Si il faut afficher le style "float: right"
 *	@return     string      Retourne tag img
 */
function img_warning($alt = "default",$float=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Warning");
    $img='<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/warning.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"';
    if ($float) $img.=' style="float: right"';
    $img.='>';

    return $img;
}

/**
 *	Affiche logo redstar
 *	@param      alt         Texte sur le alt de l'image
 *	@param      float       Si il faut afficher le style "float: right"
 *	@return     string      Retourne tag img
 */
function img_redstar($alt = "default",$float=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("SuperAdministrator");
    $img='<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/redstar.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"';
    if ($float) $img.=' style="float: right"';
    $img.='>';

    return $img;
}

/**
 *  Affiche logo error
 *  @param      alt         Texte sur le alt de l'image
 *  @return     string      Retourne tag img
 */
function img_error($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Error");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/error.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo telephone
 *	@param      alt         Texte sur le alt de l'image
 *	@param		option		Choose of logo
 *	@return     string      Retourne tag img
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
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
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
 *	@param      alt     Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_previous($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Previous");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/previous.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Show logo down arrow
 *	@param      alt         Texte sur le alt de l'image
 *	@param      selected    Affiche version "selected" du logo
 *	@return     string      Retourne tag img
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
 *	@param      alt         Texte sur le alt de l'image
 *	@param      selected    Affiche version "selected" du logo
 *	@return     string      Retourne tag img
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
 *	@param      alt         Texte sur le alt de l'image
 *	@param      selected    Affiche version "selected" du logo
 *	@return     string      Retourne tag img
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
 *	@param      alt         Texte sur le alt de l'image
 *	@param      selected    Affiche version "selected" du logo
 *	@return     string      Retourne tag img
 */
function img_right($alt = "default", $selected=0)
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Right");
    if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
    else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche logo tick
 *	@param      alt         Texte sur le alt de l'image
 *	@return     string      Retourne tag img
 */
function img_tick($alt = "default")
{
    global $conf,$langs;
    if ($alt=="default") $alt=$langs->trans("Active");
    return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	Affiche le logo tick si allow
 *	@param      allow       Authorise ou non
 *	@param      alt			Alt text for img
 *	@return     string      Retourne tag img
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
 *	@param      file		Filename
 * 	@param		alt			Alternate text to show on img mous hover
 *	@return     string     	Return img tag
 */
function img_mime($file,$alt='')
{
    require_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

    $mimetype=dol_mimetype($file,'',1);
    $mimeimg=dol_mimetype($file,'',2);

    if (empty($alt)) $alt='Mime type: '.$mimetype;

    return '<img src="'.DOL_URL_ROOT.'/theme/common/mime/'.$mimeimg.'" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 *	Show information for admin users
 *	@param      text			Text info
 *	@param      infoonimgalt	Info is shown only on alt of star picto, otherwise it is show on output after the star picto
 *	@return		string			String with info text
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
 *	Check permissions of a user to show a page and an object. Check read permission
 * 	If $_REQUEST['action'] defined, we also check write and delete permission.
 *	@param      user      	  	User to check
 *	@param      features	    Features to check (in most cases, it's module name)
 *	@param      objectid      	Object ID if we want to check permission on a particular record (optionnal)
 *	@param      dbtablename    	Table name where object is stored. Not used if objectid is null (optionnal)
 *	@param      feature2		Feature to check (second level of permission)
 *  @param      dbt_keyfield    Field name for socid foreign key if not fk_soc. (optionnal)
 *  @param      dbt_select      Field name for select if not rowid. (optionnal)
 * 	@return		int				Always 1, die process if not allowed
 */
function restrictedArea($user, $features='societe', $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid')
{
    global $db, $conf;

    //dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename,$feature2,$dbt_socfield,$dbt_select");
    if ($dbt_select != 'rowid') $objectid = "'".$objectid."'";

    //print "user_id=".$user->id.", features=".$features.", feature2=".$feature2.", objectid=".$objectid;
    //print ", dbtablename=".$dbtablename.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select;
    //print ", perm: ".$features."->".$feature2."=".$user->rights->$features->$feature2->lire."<br>";

    // More features to check
    $features = explode("&",$features);
    //var_dump($features);

    // Check read permission from module
    // TODO Replace "feature" param by permission for reading
    $readok=1;
    foreach ($features as $feature)
    {
        if ($feature == 'societe')
        {
            if (! $user->rights->societe->lire && ! $user->rights->fournisseur->lire) $readok=0;
        }
        else if ($feature == 'contact')
        {
            if (! $user->rights->societe->contact->lire) $readok=0;
        }
        else if ($feature == 'produit|service')
        {
            if (! $user->rights->produit->lire && ! $user->rights->service->lire) $readok=0;
        }
        else if ($feature == 'prelevement')
        {
            if (! $user->rights->prelevement->bons->lire) $readok=0;
        }
        else if ($feature == 'commande_fournisseur')
        {
            if (! $user->rights->fournisseur->commande->lire) $readok=0;
        }
        else if ($feature == 'cheque')
        {
            if (! $user->rights->banque->cheque) $readok=0;
        }
        else if ($feature == 'projet')
        {
            if (! $user->rights->projet->lire && ! $user->rights->projet->all->lire) $readok=0;
        }
        else if (! empty($feature2))	// This should be used for future changes
        {
            if (empty($user->rights->$feature->$feature2->lire)
            && empty($user->rights->$feature->$feature2->read)) $readok=0;
        }
        else if (! empty($feature) && ($feature!='user' && $feature!='usergroup'))		// This is for old permissions
        {
            if (empty($user->rights->$feature->lire)
            && empty($user->rights->$feature->read)
            && empty($user->rights->$feature->run)) $readok=0;
        }
    }

    if (! $readok)
    {
        //print "Read access is down";
        accessforbidden();
    }
    //print "Read access is ok";

    // Check write permission from module
    $createok=1;
    if ( GETPOST("action") && GETPOST("action")  == 'create')
    {
        foreach ($features as $feature)
        {
            if ($feature == 'contact')
            {
                if (! $user->rights->societe->contact->creer) $createok=0;
            }
            else if ($feature == 'produit|service')
            {
                if (! $user->rights->produit->creer && ! $user->rights->service->creer) $createok=0;
            }
            else if ($feature == 'prelevement')
            {
                if (! $user->rights->prelevement->bons->creer) $createok=0;
            }
            else if ($feature == 'commande_fournisseur')
            {
                if (! $user->rights->fournisseur->commande->creer) $createok=0;
            }
            else if ($feature == 'banque')
            {
                if (! $user->rights->banque->modifier) $createok=0;
            }
            else if ($feature == 'cheque')
            {
                if (! $user->rights->banque->cheque) $createok=0;
            }
            else if (! empty($feature2))	// This should be used for future changes
            {
                if (empty($user->rights->$feature->$feature2->creer)
                && empty($user->rights->$feature->$feature2->write)) $createok=0;
            }
            else if (! empty($feature))		// This is for old permissions
            {
                //print '<br>feature='.$feature.' creer='.$user->rights->$feature->creer.' write='.$user->rights->$feature->write;
                if (empty($user->rights->$feature->creer)
                && empty($user->rights->$feature->write)) $createok=0;
            }
        }

        if (! $createok) accessforbidden();
        //print "Write access is ok";
    }

    // Check create user permission
    $createuserok=1;
    if ( GETPOST("action") && (GETPOST("action") == 'confirm_create_user' && GETPOST("confirm") == 'yes') )
    {
        if (! $user->rights->user->user->creer) $createuserok=0;

        if (! $createuserok) accessforbidden();
        //print "Create user access is ok";
    }

    // Check delete permission from module
    $deleteok=1;
    if ( GETPOST("action") && ( (GETPOST("action")  == 'confirm_delete' && GETPOST("confirm") && GETPOST("confirm") == 'yes') || GETPOST("action")  == 'delete') )
    {
        foreach ($features as $feature)
        {
            if ($feature == 'contact')
            {
                if (! $user->rights->societe->contact->supprimer) $deleteok=0;
            }
            else if ($feature == 'produit|service')
            {
                if (! $user->rights->produit->supprimer && ! $user->rights->service->supprimer) $deleteok=0;
            }
            else if ($feature == 'commande_fournisseur')
            {
                if (! $user->rights->fournisseur->commande->supprimer) $deleteok=0;
            }
            else if ($feature == 'banque')
            {
                if (! $user->rights->banque->modifier) $deleteok=0;
            }
            else if ($feature == 'cheque')
            {
                if (! $user->rights->banque->cheque) $deleteok=0;
            }
            else if ($feature == 'ecm')
            {
                if (! $user->rights->ecm->upload) $deleteok=0;
            }
            else if ($feature == 'ftp')
            {
                if (! $user->rights->ftp->write) $deleteok=0;
            }
            else if (! empty($feature2))	// This should be used for future changes
            {
                if (empty($user->rights->$feature->$feature2->supprimer)
                && empty($user->rights->$feature->$feature2->delete)) $deleteok=0;
            }
            else if (! empty($feature))		// This is for old permissions
            {
                //print '<br>feature='.$feature.' creer='.$user->rights->$feature->supprimer.' write='.$user->rights->$feature->delete;
                if (empty($user->rights->$feature->supprimer)
                && empty($user->rights->$feature->delete)) $deleteok=0;
            }
        }

        //print "Delete access is ko";
        if (! $deleteok) accessforbidden();
        //print "Delete access is ok";
    }

    // If we have a particular object to check permissions on, we check this object
    // is linked to a company allowed to $user.
    if (! empty($objectid) && $objectid > 0)
    {
        foreach ($features as $feature)
        {
            $sql='';

            $check = array('banque','user','usergroup','produit','service','produit|service'); // Test on entity only (Objects with no link to company)
            $checksoc = array('societe');	 // Test for societe object
            $checkother = array('contact');	 // Test on entity and link to societe. Allowed if link is empty (Ex: contacts...).
            $checkproject = array('projet'); // Test for project object
            $nocheck = array('categorie','barcode','stock','fournisseur');	// No test
            $checkdefault = 'all other not already defined'; // Test on entity and link to third party. Not allowed if link is empty (Ex: invoice, orders...).

            // If dbtable not defined, we use same name for table than module name
            if (empty($dbtablename)) $dbtablename = $feature;

            // Check permission for object with entity
            if (in_array($feature,$check))
            {
                $sql = "SELECT dbt.".$dbt_select;
                $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                $sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                $sql.= " AND dbt.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
            }
            else if (in_array($feature,$checksoc))
            {
                // If external user: Check permission for external users
                if ($user->societe_id > 0)
                {
                    if ($user->societe_id <> $objectid) accessforbidden();
                }
                // If internal user: Check permission for internal users that are restricted on their objects
                else if (! $user->rights->societe->client->voir)
                {
                    $sql = "SELECT sc.fk_soc";
                    $sql.= " FROM (".MAIN_DB_PREFIX."societe_commerciaux as sc";
                    $sql.= ", ".MAIN_DB_PREFIX."societe as s)";
                    $sql.= " WHERE sc.fk_soc = ".$objectid;
                    $sql.= " AND sc.fk_user = ".$user->id;
                    $sql.= " AND sc.fk_soc = s.rowid";
                    $sql.= " AND s.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
                }
                // If multicompany and internal users with all permissions, check user is in correct entity
                else if ($conf->global->MAIN_MODULE_MULTICOMPANY)
                {
                    $sql = "SELECT s.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
                    $sql.= " WHERE s.rowid = ".$objectid;
                    $sql.= " AND s.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
                }
            }
            else if (in_array($feature,$checkother))
            {
                // If external user: Check permission for external users
                if ($user->societe_id > 0)
                {
                    $sql = "SELECT dbt.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND dbt.fk_soc = ".$user->societe_id;
                }
                // If internal user: Check permission for internal users that are restricted on their objects
                else if (! $user->rights->societe->client->voir)
                {
                    $sql = "SELECT dbt.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON dbt.fk_soc = sc.fk_soc AND sc.fk_user = '".$user->id."'";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL)";	// Contact not linked to a company or to a company of user
                    $sql.= " AND dbt.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
                }
                // If multicompany and internal users with all permissions, check user is in correct entity
                else if ($conf->global->MAIN_MODULE_MULTICOMPANY)
                {
                    $sql = "SELECT dbt.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND dbt.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
                }
            }
            else if (in_array($feature,$checkproject))
            {
                if (! $user->rights->projet->all->lire)
                {
                    include_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                    $projectstatic=new Project($db);
                    $tmps=$projectstatic->getProjectsAuthorizedForUser($user,0,1,$user->societe_id);
                    $tmparray=explode(',',$tmps);
                    if (! in_array($objectid,$tmparray)) accessforbidden();
                }
            }
            else if (! in_array($feature,$nocheck))	// By default we check with link to third party
            {
                // If external user: Check permission for external users
                if ($user->societe_id > 0)
                {
                    $sql = "SELECT dbt.".$dbt_keyfield;
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND dbt.".$dbt_keyfield." = ".$user->societe_id;
                }
                // If internal user: Check permission for internal users that are restricted on their objects
                else if (! $user->rights->societe->client->voir)
                {
                    $sql = "SELECT sc.fk_soc";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
                    $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
                    $sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                    $sql.= " AND sc.fk_soc = dbt.".$dbt_keyfield;
                    $sql.= " AND dbt.".$dbt_keyfield." = s.rowid";
                    $sql.= " AND s.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
                    $sql.= " AND sc.fk_user = ".$user->id;
                }
                // If multicompany and internal users with all permissions, check user is in correct entity
                else if ($conf->global->MAIN_MODULE_MULTICOMPANY)
                {
                    $sql = "SELECT dbt.".$dbt_select;
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                    $sql.= " AND dbt.entity IN (0,".(! empty($conf->entities[$dbtablename]) ? $conf->entities[$dbtablename] : $conf->entity).")";
                }
            }

            //print $sql."<br>";
            if ($sql)
            {
                $resql=$db->query($sql);
                if ($resql)
                {
                    if ($db->num_rows($resql) == 0)	accessforbidden();
                }
                else
                {
                    dol_syslog("functions.lib:restrictedArea sql=".$sql, LOG_ERR);
                    accessforbidden();
                }
            }
        }
    }

    return 1;
}


/**
 *	Show a message to say access is forbidden and stop program
 *	Calling this function terminate execution of PHP.
 *	@param		message			    Force error message
 *	@param		printheader		    Show header before
 *  @param      printfooter         Show footer after
 *  @param      showonlymessage     Show only message parameter. Otherwise add more information.
 */
function accessforbidden($message='',$printheader=1,$printfooter=1,$showonlymessage=0)
{
    global $conf, $db, $user, $langs;
    if (! is_object($langs))
    {
        include_once(DOL_DOCUMENT_ROOT.'/core/class/translate.class.php');
        $langs=new Translate('',$conf);
    }

    $langs->load("other");

    if ($printheader)
    {
        if (function_exists("llxHeader")) llxHeader('');
        else if (function_exists("llxHeaderVierge")) llxHeaderVierge('');
    }
    print '<div class="error">';
    if (! $message) print $langs->trans("ErrorForbidden");
    else print $message;
    print '</div>';
    print '<br>';
    if (empty($showonlymessage))
    {
        if ($user->login)
        {
            print $langs->trans("CurrentLogin").': <font class="error">'.$user->login.'</font><br>';
            print $langs->trans("ErrorForbidden2",$langs->trans("Home"),$langs->trans("Users"));
        }
        else
        {
            print $langs->trans("ErrorForbidden3");
        }
    }
    if ($printfooter && function_exists("llxFooter")) llxFooter('');
    exit(0);
}


/* For backward compatibility */
function dolibarr_print_error($db='',$error='')
{
    return dol_print_error($db, $error);
}

/**
 *	Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontee des bugs.
 *	On doit appeler cette fonction quand une erreur technique bloquante est rencontree.
 *	Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
 *	renvoyer leur erreur par l'intermediaire de leur propriete "error".
 *	@param      db      	Database handler
 *	@param      error		String or array of errors strings to show
 *  @see        dol_htmloutput_errors
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
 */
function dol_print_error_email()
{
    global $langs,$conf;

    $langs->load("errors");
    print '<br><div class="error">'.$langs->trans("ErrorContactEMail",$conf->global->MAIN_INFO_SOCIETE_MAIL,'ERRORNEWPAYMENT'.dol_print_date(mktime(),'%Y%m%d')).'</div>';
}


/**
 *	Show title line of an array
 *	@param	    name        Label of field
 *	@param	    file        Url used when we click on sort picto
 *	@param	    field       Field to use for new sorting
 *	@param	    begin       ("" by defaut)
 *	@param	    moreparam   Add more parameters on sort url links ("" by default)
 *	@param      td          Options of attribute td ("" by defaut)
 *	@param      sortfield   Current field used to sort
 *	@param      sortorder   Current sort order
 */
function print_liste_field_titre($name, $file="", $field="", $begin="", $moreparam="", $td="", $sortfield="", $sortorder="")
{
    global $conf;
    //print "$name, $file, $field, $begin, $options, $td, $sortfield, $sortorder<br>\n";

    // Le champ de tri est mis en evidence.
    // Exemple si (sortfield,field)=("nom","xxx.nom") ou (sortfield,field)=("nom","nom")
    if ($field && ($sortfield == $field || $sortfield == preg_replace("/^[^\.]+\./","",$field)))
    {
        print '<td class="liste_titre_sel" '. $td.'>';
    }
    else
    {
        print '<td class="liste_titre" '. $td.'>';
    }
    print $name;

    // If this is a sort field
    if ($field)
    {
        $options=preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i','',$moreparam);
        $options=preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i','',$options);
        $options=preg_replace('/&+/i','&',$options);
        if (! preg_match('/^&/',$options)) $options='&'.$options;

        //print "&nbsp;";
        print '<img width="2" src="'.DOL_URL_ROOT.'/theme/common/transparent.png" alt="">';
        if (! $sortorder)
        {
            print '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
            print '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
        }
        else
        {
            if ($field != $sortfield)
            {
                print '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
                print '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
            }
            else {
                $sortorder=strtoupper($sortorder);
                if ($sortorder == 'DESC' ) {
                    print '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
                    print '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",1).'</a>';
                }
                if ($sortorder == 'ASC' ) {
                    print '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",1).'</a>';
                    print '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
                }
            }
        }
    }
    print "</td>";
}

/**
 *	Show a title (deprecated. use print_fiche_titre instrad)
 *	@param	titre			Title to show
 */
function print_titre($titre)
{
    print '<div class="titre">'.$titre.'</div>';
}

/**
 *	Show a title with picto
 *	@param	titre				Title to show
 *	@param	mesg				Added message to show on right
 *	@param	picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	pictoisfullpath		1=Icon name is a full absolute url of image
 * 	@param	id					To force an id on html objects
 */
function print_fiche_titre($titre, $mesg='', $picto='title.png', $pictoisfullpath=0, $id='')
{
    print load_fiche_titre($titre, $mesg, $picto, $pictoisfullpath, $id);
}

/**
 *	Load a title with picto
 *	@param	titre				Title to show
 *	@param	mesg				Added message to show on right
 *	@param	picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	pictoisfullpath		1=Icon name is a full absolute url of image
 * 	@param	id					To force an id on html objects
 */
function load_fiche_titre($titre, $mesg='', $picto='title.png', $pictoisfullpath=0, $id='')
{
    global $conf;

    $return='';

    if ($picto == 'setup') $picto='title.png';
    if (!empty($conf->browser->ie) && $picto=='title.png') $picto='title.gif';

    $return.= "\n";
    $return.= '<table '.($id?'id="'.$id.'" ':'').'summary="" width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;"><tr>';
    if (empty($conf->browser->phone) && $picto) $return.= '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, 'id="pictotitle"', $pictoisfullpath).'</td>';
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
 *	@param	titre				Title to show (required)
 *	@param	page				Numero of page (required)
 *	@param	file				Url of page (required)
 *	@param	options         	parametres complementaires lien ('' par defaut)
 *	@param	sortfield       	champ de tri ('' par defaut)
 *	@param	sortorder       	ordre de tri ('' par defaut)
 *	@param	center          	chaine du centre ('' par defaut)
 *	@param	num					number of records found by select with limit+1
 *	@param	totalnboflines		Total number of records/lines for all pages (if known)
 *	@param	picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	pictoisfullpath		1=Icon name is a full absolute url of image
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
 *	@param	page				Numero of page
 *	@param	file				Lien
 *	@param	options         	Autres parametres d'url a propager dans les liens ("" par defaut)
 *	@param	nextpage	    	Faut-il une page suivante
 *	@param	betweenarrows		HTML Content to show between arrows
 */
function print_fleche_navigation($page,$file,$options='',$nextpage,$betweenarrows='')
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
 *	Fonction qui retourne un taux de tva formate pour visualisation
 *	Utilisee dans les pdf et les pages html
 *	@param	    rate			Rate value to format (19.6 19,6 19.6% 19,6%,...)
 *  @param		addpercent		Add a percent % sign in output
 *	@param		info_bits		Miscellanous information on vat
 *  @return		string			Chaine avec montant formate (19,6 ou 19,6% ou 8.5% *)
 */
function vatrate($rate,$addpercent=false,$info_bits=0,$usestarfornpr=0)
{
    // Test for compatibility
    if (preg_match('/%/',$rate))
    {
        $rate=str_replace('%','',$rate);
        $addpercent=true;
    }
    if (preg_match('/\*/',$rate) || preg_match('/'.MAIN_LABEL_MENTION_NPR.'/i',$rate))
    {
        $rate=str_replace('*','',$rate);
        $info_bits |= 1;
    }

    $ret=price($rate,0,'',0,0).($addpercent?'%':'');
    if ($info_bits & 1) $ret.=' '.($usestarfornpr?'*':MAIN_LABEL_MENTION_NPR);
    return $ret;
}


/**
 *		Fonction qui formate un montant pour visualisation
 *		Fonction utilisee dans les pdf et les pages html
 *		@param	    amount			Montant a formater
 *		@param	    html			Type de formatage, html ou pas (par defaut)
 *		@param	    outlangs		Objet langs pour formatage text
 *		@param		trunc			1=Tronque affichage si trop de decimales,0=Force le non troncage
 *		@param		rounding		Minimum number of decimal. If not defined we use min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOTAL)
 *		@param		forcerounding	Force the number of decimal
 *		@return		string			Chaine avec montant formate
 *		@see		price2num		Revert function of price
 */
function price($amount, $html=0, $outlangs='', $trunc=1, $rounding=-1, $forcerounding=-1)
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
    //print "amount=".$amount." html=".$html." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

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
    if ($html)
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
 *	Function to use on each input amount before any numeric test or database insert.
 *	@param	    	amount			Amount to convert/clean
 *	@param	    	rounding		''=No rounding
 * 									'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
 *									'MT'=Round to Max for totals with Tax (MAIN_MAX_DECIMALS_TOT)
 *									'MS'=Round to Max Shown (MAIN_MAX_DECIMALS_SHOWN)
 * 	@param			alreadysqlnb	Put 1 if you know that content is already universal format number
 *	@return			string			Amount with universal numeric format (Example: '99.99999')
 *	@see     		price			Opposite function of price2num
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
    //print "amount=".$amount." html=".$html." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

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
 * 	@param      tva			         Vat taxe
 * 	@param      local		         Local taxe to search and return
 *  @param      societe_acheteuse    Object of buying third party
 * 	@return		int			         0 if not found, localtax if found
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
    $sql .= " AND t.taux =".$tva." AND t.active = 1";
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
 *  vat if product is unknown.
 *  @param      idprod          Id of product or 0 if not a predefined product
 *  @param      countrycode     Country code (FR, US, IT, ...)
 *  @return     int             <0 if KO, Vat rate if OK
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
 *  @param      idprod          Id of product
 *  @package    local           1 for localtax1, 2 for localtax 2
 *  @param      countrycode     Country code (FR, US, IT, ...)
 *  @return     int             <0 if KO, Vat rate if OK
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
 *	@param      	societe_vendeuse    	Objet societe vendeuse
 *	@param      	societe_acheteuse   	Objet societe acheteuse
 *	@param      	idprod					Id product
 *	@return     	float               	Taux de tva a appliquer, -1 si ne peut etre determine
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

    //if (is_object($societe_acheteuse) && ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id) && ($societe_acheteuse->tva_assuj == 1 || $societe_acheteuse->tva_assuj == 'reel'))
    // Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concerne si le test suivant n'est pas suffisant.

    // Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
    if ($societe_vendeuse->pays_code == $societe_acheteuse->pays_code) // Warning ->pays_code not always defined
    {
        //print 'VATRULE 3';
        return get_product_vat_for_country($idprod,$societe_vendeuse->pays_code);
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
            return get_product_vat_for_country($idprod,$societe_vendeuse->pays_code);
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
            return get_product_vat_for_country($idprod,$societe_acheteuse->pays_code);
        }
    }

    // Sinon la TVA proposee par defaut=0. Fin de regle.
    // Rem: Cela signifie qu'au moins un des 2 est hors Communaute europeenne et que le pays differe
    //print 'VATRULE 7';
    return 0;
}


/**
 *	Fonction qui renvoie si tva doit etre tva percue recuperable
 *	             	Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 *					Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 *					Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
 *					Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par defaut=TVA du produit vendu. Fin de regle
 *					Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise avec num TVA) intra alors TVA par defaut=0. Fin de regle
 *					Sinon TVA proposee par defaut=0. Fin de regle.
 *	@param      	societe_vendeuse    	Objet societe vendeuse
 *	@param      	societe_acheteuse   	Objet societe acheteuse
 *  @param          idprod                  Id product
 *	@return     	float               	0 or 1
 */
function get_default_npr($societe_vendeuse, $societe_acheteuse, $idprod)
{
    return 0;
}

/**
 *	Function that return localtax of a product line (according to seller, buyer and product vat rate)
 *	@param      	societe_vendeuse    	Objet societe vendeuse
 *	@param      	societe_acheteuse   	Objet societe acheteuse
 *  @param			local					Localtax to process (1 or 2)
 *	@param      	idprod					Id product
 *	@return     	float               	Taux de localtax appliquer, -1 si ne peut etre determine
 */
function get_default_localtax($societe_vendeuse, $societe_acheteuse, $local, $idprod=0)
{
    if (!is_object($societe_vendeuse)) return -1;
    if (!is_object($societe_acheteuse)) return -1;

    if($societe_vendeuse->pays_id=='ES')
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

        if ($idprod) return get_product_localtax_for_country($idprod, $local, $societe_vendeuse->pays_code);
        else return -1;
    }
    return 0;
}

/**
 *	Return yes or no in current language
 *	@param	yesno			Value to test (1, 'yes', 'true' or 0, 'no', 'false')
 *	@param	case			1=Yes/No, 0=yes/no
 *	@param	color			0=texte only, 1=Text is formated with a color font style ('ok' or 'error'), 2=Text is formated with 'ok' color.
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
 *	@param      $num            Id to develop
 *	@param      $level		    Level of development (1, 2 or 3 level)
 * 	@param		$alpha		    Use alpha ref
 *  @param      withoutslash    0=With slash at end, 1=without slash at end
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
 *	Creation of a directory (recursive)
 *	@param      $dir        Directory to create
 *	@return     int         < 0 if KO, 0 = already exists, > 0 if OK
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
    for ($i = 0 ; $i < sizeof($cdir) ; $i++)
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
 *	@return  string		Chaine avec picto obligatoire
 */
function picto_required()
{
    return '<span class="fieldrequired">*</span>';
}


/**
 *	Clean a string from all HTML tags and entities
 *	@param   	StringHtml			String to clean
 *	@param		removelinefeed		Replace also all lines feeds by a space
 *	@return  	string	    		String cleaned
 */
function dol_string_nohtmltag($StringHtml,$removelinefeed=1)
{
    $pattern = "/<[^>]+>/";
    $temp = dol_entity_decode($StringHtml);
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
 *	Replace CRLF in string with a HTML BR tag.
 *	@param		stringtoencode		String to encode
 *	@param		nl2brmode			0=Adding br before \n, 1=Replacing \n by br
 *  @param      forxml              false=Use <br>, true=Use <br />
 *	@return		string				String encoded
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
 *              is used to build PDF, nl2brmode must be 1.
 *	@param		stringtoencode		String to encode
 *	@param		nl2brmode			0=Adding br before \n, 1=Replacing \n by br (for use with FPDF writeHTMLCell function for example)
 *  @param      pagecodefrom        Pagecode stringtoencode is encoded
 */
function dol_htmlentitiesbr($stringtoencode,$nl2brmode=0,$pagecodefrom='UTF-8')
{
    if (dol_textishtml($stringtoencode))
    {
        $newstring=$stringtoencode;
        //$newstring=preg_replace('/([^<li\s*>]+)(\r\n|\r|\n)+/i',($forxml?'$1<br />':'$1<br>'),$stringtoencode); // Don't replace if in list
        //$newstring=preg_replace('/<li\s*>(\r\n|\r|\n)+/','__li__',$newstring); // Don't replace if \n is just after a li
        //$newstring=preg_replace('/(\r\n|\r|\n)+/i',($forxml?'<br />':'<br>'),$newstring); // If already HTML, CR should be <br> so we don't change \n
        $newstring=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i','<br>',$newstring);	// Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
        $newstring=preg_replace('/<br>$/i','',$newstring);	// Remove last <br>
        $newstring=strtr($newstring,array('&'=>'__and__','<'=>'__lt__','>'=>'__gt__','"'=>'__dquot__'));
        $newstring=dol_htmlentities($newstring,ENT_COMPAT,$pagecodefrom);	// Make entity encoding
        $newstring=strtr($newstring,array('__and__'=>'&','__lt__'=>'<','__gt__'=>'>','__dquot__'=>'"'));
        //$newstring=strtr($newstring,array('__li__'=>"<li>\n")); // Restore <li>\n
    }
    else {
        $newstring=dol_nl2br(dol_htmlentities($stringtoencode,ENT_COMPAT,$pagecodefrom),$nl2brmode);
    }
    // Other substitutions that htmlentities does not do
    $newstring=str_replace(chr(128),'&euro;',$newstring);	// 128 = 0x80. Not in html entity table.
    return $newstring;
}

/**
 *	This function is called to decode a HTML string (it decodes entities and br tags)
 *	@param		stringtodecode		String to decode
 *	@param		pagecodeto			Page code for result
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
 *	@param		stringtodecode		String to decode
 */
function dol_htmlcleanlastbr($stringtodecode)
{
    $ret=preg_replace('/(<br>|<br(\s[\sa-zA-Z_="]*)?\/?>|'."\n".'|'."\r".')+$/i',"",$stringtodecode);
    return $ret;
}

/**
 *	This function is called to decode a string with HTML entities (it decodes entities tags)
 * 	@param   	stringhtml      stringhtml
 *  @param      pagecodeto      Encoding of input string
 * 	@return  	string	  	    decodestring
 */
function dol_entity_decode($stringhtml,$pagecodeto='UTF-8')
{
    $ret=dol_html_entity_decode($stringhtml,ENT_COMPAT,$pagecodeto);
    return $ret;
}

/**
 * Replace html_entity_decode functions to manage errors
 * @param   a
 * @param   b
 * @param   c
 * @return  string      String decoded
 */
function dol_html_entity_decode($a,$b,$c)
{
    // We use @ to avoid warning on PHP4 that does not support entity decoding to UTF8;
    $ret=@html_entity_decode($a,$b,$c);
    return $ret;
}

/**
 * Replace htmlentities functions to manage errors
 * @param   a
 * @param   b
 * @param   c
 * @return  string      String encoded
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
 *	Example, if string contains euro symbol that has ascii code 128.
 *	@param       s       String to check
 *	@return	     int     0 if bad iso, 1 if good iso
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
 *	@param		s			String to check
 * 	@param		maxchar		Not yet used
 *	@return		int			Number of lines
 */
function dol_nboflines($s,$maxchar=0)
{
    if ($s == '') return 0;
    $arraystring=explode("\n",$s);
    $nb=sizeof($arraystring);

    return $nb;
}


/**
 *	Return nb of lines of a formated text with \n and <br>
 *	@param	   	text      		Text
 *	@param	   	maxlinesize  	Largeur de ligne en caracteres (ou 0 si pas de limite - defaut)
 * 	@param		charset			Give the charset used to encode the $text variable in memory.
 *	@return    	int				Number of lines
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
 *	 @return		float		Time (millisecondes) with microsecondes in decimal part
 */
function dol_microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 *		Return if a text is a html content
 *		@param		msg			Content to check
 *		@param		option		0=Full detection, 1=Fast check
 *		@return		boolean		true/false
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
 *    	@param      chaine      			Source string in which we must do substitution
 *    	@param      substitutionarray		Array with key->val to substitute
 *    	@return     string      			Output string after subsitutions
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
 *      Complete the $substitutionarray with more entries
 *      @param      substitutionarray       Array substitution old value => new value value
 *      @param      outputlangs             If we want substitution from special constants, we provide a language
 *      @param      object                  If we want substitution from special constants, we provide data in a source object
 */
function complete_substitutions_array(&$substitutionarray,$outputlangs,$object='')
{
    global $conf,$user;

    require_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

    // Check if there is external substitution to do asked by plugins
    // We look files into the includes/modules/substitutions directory
    // By default, there is no such external plugins.
    foreach ($conf->file->dol_document_root as $dirroot)
    {
        $substitfiles=dol_dir_list($dirroot.'/includes/modules/substitutions','files',0,'functions_');
        foreach($substitfiles as $substitfile)
        {
            if (preg_match('/functions_(.*)\.lib\.php/i',$substitfile['name'],$reg))
            {
                $module=$reg[1];
                if (! empty($conf->$module->enabled))   // If module enabled
                {
                    dol_syslog("Library functions_".$module.".lib.php found into ".$dirroot);
                    require_once($dirroot."/includes/modules/substitutions/functions_".$module.".lib.php");
                    $function_name=$module."_completesubstitutionarray";
                    $function_name($substitutionarray,$outputlangs,$object);
                }
            }
        }
    }
}

/**
 *    Format output for start and end date
 *    @param      	date_start    Start date
 *    @param      	date_end      End date
 *    @param      	format        Output format
 *    @param		outputlangs   Output language
 */
function print_date_range($date_start,$date_end,$format = '',$outputlangs='')
{
    print  get_date_range($date_start,$date_end,$format,$outputlangs);
}

/**
 *    Format output for start and end date
 *    @param      	date_start    Start date
 *    @param      	date_end      End date
 *    @param      	format        Output format
 *    @param		outputlangs   Output language
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
 *	Retourne un tableau des mois ou le mois selectionne
 *	@param   selected			Mois a selectionner ou -1
 *	@return  string or array	Month string or array if selected < 0
 */
function monthArrayOrSelected($selected=0)
{
    global $langs;
    $langs->load("main");

    $month = array (1  => $langs->trans("January"),
    2  => $langs->trans("February"),
    3  => $langs->trans("March"),
    4  => $langs->trans("April"),
    5  => $langs->trans("May"),
    6  => $langs->trans("June"),
    7  => $langs->trans("July"),
    8  => $langs->trans("August"),
    9  => $langs->trans("September"),
    10 => $langs->trans("October"),
    11 => $langs->trans("November"),
    12 => $langs->trans("December")
    );

    if ($selected >=0)
    {
        $return='';
        foreach ($month as $key => $val)
        {
            if ($selected == $key)
            {
                $return = $val;
            }
        }
        return $return;
    }
    else
    {
        return $month;
    }
}


/**
 *	Get formated messages to output (Used to show messages on html output)
 *	@param		mesgstring		Message string
 *	@param		mesgarray       Messages array
 *  @param      style           Style of message output ('ok' or 'error')
 *  @param      keepembedded    Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *	@return		string			Return html output
 *  @see        dol_print_error
 *  @see        dol_htmloutput_errors
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

    if ((is_array($mesgarray) && sizeof($mesgarray)) || $mesgstring)
    {
        $langs->load("errors");
        $out.=$divstart;
        if (is_array($mesgarray) && sizeof($mesgarray))
        {
            foreach($mesgarray as $message)
            {
                $ret++;
                $out.= $langs->trans($message);
                if ($ret < sizeof($mesgarray)) $out.= "<br>\n";
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
                        {
                          closeLabel: "&times;"                     // the HTML to use for the "Close" link
                          , showClose: true                           // determines if the "Close" link should be shown if notification is also sticky
                          , fadeSpeed: 1000                           // the speed to fade messages out (in milliseconds)
                          , slideSpeed: 250                           // the speed used to slide messages out (in milliseconds)
                          , classContainer: "jnotify-container"
                          , classNotification: "jnotify-notification"
                          , classBackground: "jnotify-background"
                          , classClose: "jnotify-close"
                          , classMessage: "jnotify-message"
                          , init: null                                // callback that occurs when the main jnotify container is created
                          , create: null                              // callback that occurs when when the note is created (occurs just before
                                                                      // appearing in DOM)
                          , beforeRemove: null                        // callback that occurs when before the notification starts to fade away
                        },
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
 *  Get formated error messages to output (Used to show messages on html output)
 *  @param      mesgstring          Error message
 *  @param      mesgarray           Error messages array
 *  @param      keepembedded        Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return     html                Return html output
 *  @see        dol_print_error
 *  @see        dol_htmloutput_mesg
 */
function get_htmloutput_errors($mesgstring='', $mesgarray='', $keepembedded=0)
{
    return get_htmloutput_mesg($mesgstring, $mesgarray,'error',$keepembedded);
}

/**
 *	Print formated messages to output (Used to show messages on html output)
 *	@param		mesgstring		Message
 *	@param		mesgarray       Messages array
 *  @param      style           Which style to use ('ok', 'error')
 *  @param      keepembedded    Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @see        dol_print_error
 *  @see        dol_htmloutput_errors
 */
function dol_htmloutput_mesg($mesgstring='',$mesgarray='', $style='ok', $keepembedded=0)
{
    if (empty($mesgstring) && (! is_array($mesgarray) || sizeof($mesgarray) == 0)) return;

    $iserror=0;
    if (is_array($mesgarray))
    {
        foreach($mesgarray as $val)
        {
            if ($val && preg_match('/class="error"/i',$val)) { $iserror++; break; }
        }
    }
    else if ($mesgstring && preg_match('/class="error"/i',$mesgstring)) $iserror++;
    if ($style=='error') $iserror++;

    if ($iserror)
    {
        // Remove div from texts
        $mesgstring=preg_replace('/<\/div><div class="error">/','<br>',$mesgstring);
        $mesgstring=preg_replace('/<div class="error">/','',$mesgstring);
        $mesgstring=preg_replace('/<\/div>/','',$mesgstring);
        // Remove div from texts array
        if (is_array($mesgarray))
        {
            $newmesgarray=array();
            foreach($mesgarray as $val)
            {
                $tmpmesgstring=preg_replace('/<\/div><div class="error">/','<br>',$val);
                $tmpmesgstring=preg_replace('/<div class="error">/','',$tmpmesgstring);
                $tmpmesgstring=preg_replace('/<\/div>/','',$tmpmesgstring);
                $newmesgarray[]=$tmpmesgstring;
            }
            $mesgarray=$newmesgarray;
        }
        print get_htmloutput_mesg($mesgstring,$mesgarray,'error',$keepembedded);
    }
    else print get_htmloutput_mesg($mesgstring,$mesgarray,'ok',$keepembedded);
}

/**
 *  Print formated error messages to output (Used to show messages on html output)
 *  @param      mesgstring          Error message
 *  @param      mesgarray           Error messages array
 *  @param      keepembedded        Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @see        dol_print_error
 *  @see        dol_htmloutput_mesg
 */
function dol_htmloutput_errors($mesgstring='', $mesgarray='', $keepembedded=0)
{
    dol_htmloutput_mesg($mesgstring, $mesgarray, 'error', $keepembedded);
}

/**
 * 	Advanced sort array by second index function, which produces
 *	ascending (default) or descending output and uses optionally
 *	natural case insensitive sorting (which can be optionally case
 *	sensitive as well).
 *  @param      array           	Array to sort
 *  @param      index
 *  @param      order
 *  @param      natsort
 *  @param      case_sensitive		Sort is case sensitive
 *  @return     Sorted array
 */
function dol_sort_array(&$array, $index, $order='asc', $natsort, $case_sensitive)
{
    // Clean parameters
    $order=strtolower($order);

    if (is_array($array) && count($array)>0)
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
 *      @param      $str        String to check
 * 		@return		boolean		True if string is UTF8 or ISO compatible with UTF8, False if not (ISO with special char or Binary)
 */
function utf8_check($str)
{
    // We must use here a binary strlen function (so not dol_strlen)
    for ($i=0; $i<strlen($str); $i++)
    {
        if (ord($str[$i]) < 0x80) continue; # 0bbbbbbb
        elseif ((ord($str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
        elseif ((ord($str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
        elseif ((ord($str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
        elseif ((ord($str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
        elseif ((ord($str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
        else return false; # Does not match any model
        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
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
 *      @param      string	$str        String to encode (UTF-8)
 * 		@return		string				Encoded string (UTF-8, ISO-8859-1)
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
 *      Return an id from a Code. Store Code-Id in a cache.
 * 		@param		db			Database handler
 * 		@param		key			Code to get Id
 * 		@param		tablename	Table name without prefix
 * 		@param		fieldkey	Field for code
 * 		@param		fieldid		Field for id
 *      @return     int			Id of code
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
 * @param 	string 		$strRights
 * @return 	boolean		true or false
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
 * This function is called by verifCond().
 * @param 	string	$s
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


if (! function_exists('glob') && ! is_callable('glob'))
{
    /**
     *  To define glob() function if not exists
     */
    function glob($pattern)
    {
        #get pathname (everything up until the last / or \)
        $path=$output=null;
        if(PHP_OS=='WIN32') $slash='\\';
        else $slash='/';
        $lastpos=strrpos($pattern,$slash);

        if(!($lastpos===false))
        {
            $path=substr($pattern,0,-$lastpos-1);
            $pattern=substr($pattern,$lastpos);
        }
        else
        {
            #no dir info, use current dir
            $path=getcwd();
        }

        $handle=@opendir($path);
        if($handle===false) return false;

        while($dir=readdir($handle))
        {
            if(pattern_match($pattern,$dir)) $output[]=$dir;
        }

        closedir($handle);

        if(is_array($output)) return $output;
        return false;
    }
}

/**
 * 	For dol_glob() function
 */
function pattern_match($pattern,$string)
{
    #basically prepare a regular expression
    $out=null;
    $chunks=explode(';',$pattern);
    foreach($chunks as $pattern)
    {
        $escape=array('$','^','.','{','}','(',')','[',']','|');
        while(strpos($pattern,'**')!==false) $pattern=str_replace('**','*',$pattern);

        foreach($escape as $probe) $pattern=str_replace($probe,"\\$probe",$pattern);

        $pattern=str_replace('?*','*',str_replace('*?','*',str_replace('*',".*",str_replace('?','.{1,1}',$pattern))));
        $out[]=$pattern;
    }

    if(count($out)==1)
    {
        return(preg_match('/^'.$out[0].'$/i',$string));
    }
    else
    {
        foreach($out as $tester)
        {
            if(preg_match('/^'.$tester.'$/i',$string)) return true;
            return false;
        }
    }
}

/**
 * 	Return img flag of country for a language code or country code
 * 	@param		codelang	Language code (en_IN, fr_CA...) or Country code (IN, FR)
 * 	@return		string		HTML img string with flag.
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
 *  Complete or removed entries into a head array (used to build tabs) with value added by external modules
 *  @param      conf            Object conf
 *  @param      langs           Object langs
 *  @param      object          Object object
 *  @param      head            Object head
 *  @param      h               New position to fill
 *  @param      type            Value for object where objectvalue can be
 *                              'thirdparty'       to add a tab in third party view
 *                              'intervention'     to add a tab in intervention view
 *                              'supplier_order'   to add a tab in supplier order view
 *                              'supplier_invoice' to add a tab in supplier invoice view
 *                              'invoice'          to add a tab in customer invoice view
 *                              'order'            to add a tab in customer order view
 *                              'product'          to add a tab in product view
 *                              'propal'           to add a tab in propal view
 *                              'member'           to add a tab in fundation member view
 *                              'categories_x'	   to add a tab in category view ('x': type of category (0=product, 1=supplier, 2=customer, 3=member)
 *  @param      mode            'add' to complete head, 'remove' to remove entries
 */
function complete_head_from_modules($conf,$langs,$object,&$head,&$h,$type,$mode='add')
{
    if (is_array($conf->tabs_modules[$type]))
    {
        $i=0;
        foreach ($conf->tabs_modules[$type] as $value)
        {
            $values=explode(':',$value);
            if ($mode == 'add')
            {
                if (sizeof($values) == 6)       // new declaration with permissions
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
                else if (sizeof($values) == 5)       // new declaration
                {
                    if ($values[0] != $type) continue;
                    if ($values[3]) $langs->load($values[3]);
                    $head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[4]),1);
                    $head[$h][1] = $langs->trans($values[2]);
                    $head[$h][2] = str_replace('+','',$values[1]);
                    $h++;
                }
                else if (sizeof($values) == 4)   // old declaration, for backward compatibility
                {
                    if ($values[0] != $type) continue;
                    if ($values[2]) $langs->load($values[2]);
                    $head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[3]),1);
                    $head[$h][1] = $langs->trans($values[1]);
                    $head[$h][2] = 'tab'.$values[1];
                    $h++;
                }
            }
            else if ($mode == 'remove')
            {
                if ($values[0] != $type) continue;
                $tabname=str_replace('-','',$values[1]);
                foreach($head as $key => $val)
                {
                    if ($head[$key][2]==$tabname)
                    {
                        //print 'on vire '.$tabname.' key='.$key;
                        unset($head[$key]);
                        break;
                    }
                }
            }
        }
    }
}

?>
