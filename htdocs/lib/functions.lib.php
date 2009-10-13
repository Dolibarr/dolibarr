<?php
/* Copyright (C) 2000-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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
 *	\file			htdocs/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 *	\version		$Id$
 */

// For compatibility during upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	 define('DOL_DOCUMENT_ROOT', '..');
if (! defined('ADODB_DATE_VERSION')) include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


/**
 *	\brief          Create a clone of instance of object (new instance with same properties)
 * 					This function works for both PHP4 and PHP5
 * 	\param			object		Object to clone
 *	\return         date		Timestamp
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
 *	\brief          Optimize a size for some browsers (phone, smarphone, ...)
 * 	\param			size		Size we want
 * 	\param			type		Type of optimizing(''=Optimize for a truncate, 'width'=Optimize for screen width)
 *	\return         int			New size after optimizing
 */
function dol_size($size,$type='')
{
	global $conf;
	if (empty($conf->browser->phone)) return $size;
	if ($type == 'width') return 250;
	else return 10;
}


/**
 *	\brief          Return date for now
 * 	\param			mode		'gmt' => we return GMT timestamp,
 * 								'tzserver' => we use the PHP server timezone
 *  							'tzref' => we use the company timezone
 * 								'tzuser' => we use the user timezone
 *	\return         date		Timestamp
 */
function dol_now($mode='tzserver')
{
	if ($mode == 'gmt') $ret=gmmktime();	// Time for now at greenwich.
	else if ($mode == 'tzserver')			// Time for now where PHP server is located
	{
		$ret=mktime();
	}
	else if ($mode == 'tzref')				// Time for now where the parent company is located
	{
		// TODO Should use the timezone of the company instead of timezone of server
		$ret=mktime();
	}
	else if ($mode == 'tzuser')				// Time for now where the user is located
	{
		// TODO Should use the timezone of the user instead of timezone of server
		$ret=mktime();
	}
	return $ret;
}


/**
 *	\brief          Clean a string to use it as a file name.
 *	\param          str             String to clean
 * 	\param			newstr			String to replace bad chars by
 *	\return         string          String cleaned (a-zA-Z_)
 * 	\seealso		dol_string_nospecial, dol_string_unaccent
 */
function dol_sanitizeFileName($str,$newstr='_')
{
	return dol_string_nospecial(dol_string_unaccent($str),$newstr);
}

/**
 *	\brief          Clean a string from all accent characters to be used as ref, login or by dol_sanitizeFileName.
 *	\param          str             String to clean
 *	\return         string          Cleaned string
 * 	\seealso		dol_sanitizeFilename, dol_string_nospecial
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
		'%C3%AC' => 'i','%C3%AD' => 'i',
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
 *	\brief          Clean a string from all punctuation characters to use it as a ref or login.
 *	\param          str             String to clean
 * 	\param			newstr			String to replace bad chars by
 * 	\return         string          Cleaned string
 * 	\seealso		dol_sanitizeFilename, dol_string_unaccent
 */
function dol_string_nospecial($str,$newstr='_')
{
	$forbidden_chars_to_underscore=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
	//$forbidden_chars_to_remove=array("(",")");
	$forbidden_chars_to_remove=array();

	return str_replace($forbidden_chars_to_underscore,$newstr,str_replace($forbidden_chars_to_remove,"",$str));
}

/**
 *  \brief       Returns text escaped for inclusion in javascript code
 *  \param       $stringtoescape	String to escape
 *  \return      string      		Escaped string
 */
function dol_escape_js($stringtoescape)
{
	// escape quotes and backslashes, newlines, etc.
	return strtr($stringtoescape, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
}


/**
 *  \brief       Returns text escaped for inclusion in HTML alt or title tags
 *  \param       $stringtoescape	String to escape
 *  \return      string      		Escaped string
 */
function dol_escape_htmltag($stringtoescape)
{
	// escape quotes and backslashes, newlines, etc.
	return strtr($stringtoescape, array('"'=>'',"\r"=>'\\r',"\n"=>'\\n'));
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
 *	\remarks	On Windows LOG_ERR=4, LOG_WARNING=5, LOG_NOTICE=LOG_INFO=LOG_DEBUG=6
 *				On Linux   LOG_ERR=3, LOG_WARNING=4, LOG_INFO=6, LOG_DEBUG=7
 */
function dol_syslog($message, $level=LOG_INFO)
{
	global $conf,$user,$langs,$_REQUEST;

	// If adding log inside HTML page is required
	if (! empty($_REQUEST['logtohtml']) && ! empty($conf->global->MAIN_LOGTOHTML))
	{
		$conf->logbuffer[]=strftime("%Y-%m-%d %H:%M:%S",time())." ".$message;
	}

	// If syslog module enabled
	if (! empty($conf->syslog->enabled))
	{
		//print $level.' - '.$conf->global->SYSLOG_LEVEL.' - '.$conf->syslog->enabled." \n";
		if ($level > $conf->global->SYSLOG_LEVEL) return;

		// Load error message files if this is an error message (rare)
		if ($level == LOG_ERR)
		{
			$langs->load("errors");
			if ($message != $langs->trans($message)) $message = $langs->trans($message);
		}

		// Add page/script name to log message
		$script=isset($_SERVER['PHP_SELF'])?basename($_SERVER['PHP_SELF'],'.php').' ':'';
		$message=$script.$message;

		// Add user to log message
		$login=isset($_SERVER['USERNAME'])?$_SERVER['USERNAME']:'nologin';
		if (is_object($user) && $user->id) $login=$user->login;
		$message=sprintf("%-8s",$login)." ".$message;

		// Check if log is to a file (SYSLOG_FILE defined) or to syslog
		if (defined("SYSLOG_FILE") && SYSLOG_FILE)
		{
			$filelog=SYSLOG_FILE;
			$filelog=eregi_replace('DOL_DATA_ROOT',DOL_DATA_ROOT,$filelog);
			if (defined("SYSLOG_FILE_NO_ERROR")) $file=@fopen($filelog,"a+");
			else $file=fopen($filelog,"a+");

			if ($file)
			{
				$ip=$_SERVER['COMPUTERNAME'];
				if (! empty($_SERVER["REMOTE_ADDR"])) $ip=$_SERVER["REMOTE_ADDR"];

				$liblevelarray=array(LOG_ERR=>'ERROR',LOG_WARNING=>'WARN',LOG_INFO=>'INFO',LOG_DEBUG=>'DEBUG');
				$liblevel=$liblevelarray[$level];
				if (! $liblevel) $liblevel='UNDEF';

				$message=strftime("%Y-%m-%d %H:%M:%S",time())." ".sprintf("%-5s",$liblevel)." ".sprintf("%-15s",$ip)." ".$message;

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

				//define_syslog_variables(); already defined in master.inc.php
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


/* For backward compatibility */
function dolibarr_fiche_head($links, $active='0', $title='', $notab=0)
{
	return dol_fiche_head($links, $active, $title, $notab);
}

/**
 *	\brief      Show tab header of a card
 *	\param	    links		Array of tabs
 *	\param	    active      Active tab name
 *	\param      title       Title
 *	\param      notab		0=Add tab header, 1=no tab header
 * 	\param		picto		Add a picto on tab titel
 */
function dol_fiche_head($links, $active='0', $title='', $notab=0, $picto='')
{
	print "\n".'<div class="tabs">'."\n";

	// Affichage titre
	if ($title)
	{
		$limittitle=30;
		print '<a class="tabTitle">';
		if ($picto) print img_object('',$picto).' ';
		print
		((!defined('MAIN_USE_SHORT_TITLE')) || (defined('MAIN_USE_SHORT_TITLE') &&  MAIN_USE_SHORT_TITLE))
		? dol_trunc($title,$limittitle)
		: $title;
		print '</a>';
	}

	// Affichage onglets
	for ($i = 0 ; $i < sizeof($links) ; $i++)
	{
		if ($links[$i][2] == 'image')
		{
			print '<a class="tabimage" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
		}
		else
		{
			//print "x $i $active ".$links[$i][2]." z";
			if ((is_numeric($active) && $i == $active)
			|| (! is_numeric($active) && $active == $links[$i][2]))
			{
				print '<a id="active" class="tab" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
			}
			else
			{
				print '<a class="tab" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
			}
		}
	}

	print "</div>\n";

	if (! $notab) print '<div class="tabBar">'."\n\n";
}


/**
 *	\brief      Add a delay to a date
 *	\param	    time                Date timestamp ou au format YYYY-MM-DD
 *	\param	    duration_value      Value of delay to add
 *	\param	    duration_unit       Unit of added delay (d, m, y)
 *	\return     int                 New timestamp
 */
function dol_time_plus_duree($time,$duration_value,$duration_unit)
{
	if ($duration_value == 0) return $time;
	if ($duration_value > 0) $deltastring="+".abs($duration_value);
	if ($duration_value < 0) $deltastring="-".abs($duration_value);
	if ($duration_unit == 'd') { $deltastring.=" day"; }
	if ($duration_unit == 'm') { $deltastring.=" month"; }
	if ($duration_unit == 'y') { $deltastring.=" year"; }
	return strtotime($deltastring,$time);
}


/* For backward compatibility */
function dolibarr_print_date($time,$format='',$to_gmt=false,$outputlangs='',$encodetooutput=false)
{
	return dol_print_date($time,$format,$to_gmt,$outputlangs,$encodetooutput);
}

/**
 *	\brief      Output date in a string format according to outputlangs (or langs if not defined).
 * 				Return charset is always UTF-8, except if encodetoouput is defined. In this cas charset is output charset.
 *	\param	    time        	GM Timestamps date (or 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS' in server TZ)
 *	\param	    format      	Output date format
 *								"%d %b %Y",
 *								"%d/%m/%Y %H:%M",
 *								"%d/%m/%Y %H:%M:%S",
 *								"day", "daytext", "dayhour", "dayhourldap", "dayhourtext"
 * 	\param		to_gmt			false=output string if for local server TZ users, true=output string is for GMT users
 *	\param		outputlangs		Object lang that contains language for text translation.
 * 	\return     string      	Formated date or '' if time is null
 */
function dol_print_date($time,$format='',$to_gmt=false,$outputlangs='',$encodetooutput=false)
{
	global $conf,$langs;

	// Si format non defini, on prend $conf->format_date_text_short sinon %Y-%m-%d %H:%M:%S
	if (! $format) $format=(isset($conf->format_date_text_short) ? $conf->format_date_text_short : '%Y-%m-%d %H:%M:%S');

	if ($format == 'day')               $format=$conf->format_date_short;
	if ($format == 'hour')              $format=$conf->format_hour_short;
	if ($format == 'daytext')           $format=$conf->format_date_text;
	if ($format == 'daytextshort')      $format=$conf->format_date_text_short;
	if ($format == 'dayhour')           $format=$conf->format_date_hour_short;
	if ($format == 'dayhourtext')       $format=$conf->format_date_hour_text;
	if ($format == 'dayhourtextshort')  $format=$conf->format_date_hour_text_short;

	if ($format == 'dayhourlog')        $format='%Y%m%d%H%M%S';
	if ($format == 'dayhourldap')       $format='%Y%m%d%H%M%SZ';
	if ($format == 'dayhourxcard')      $format='%Y%m%dT%H%M%SZ';

	// If date undefined or "", we return ""
	if (strlen($time) == 0) return '';		// $time=0 allowed (it means 01/01/1970 00:00:00)

	//print 'x'.$time;

	if (eregi('%b',$format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format=ereg_replace('%b','__b__',$format);
		$format=ereg_replace('%B','__B__',$format);
	}
	if (eregi('%a',$format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format=ereg_replace('%a','__a__',$format);
		$format=ereg_replace('%A','__A__',$format);
	}

	// Analyse de la date (deprecated)   Ex: 19700101, 19700101010000
	if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?',$time,$reg)
	|| eregi('^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])$',$time,$reg))
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

		$time=dol_mktime($shour,$smin,$ssec,$smonth,$sday,$syear);
		$ret=adodb_strftime($format,$time,$to_gmt);
	}
	else
	{
		// Date is a timestamps
		if ($time < 100000000000)	// Protection against bad date values
		{
			$ret=adodb_strftime($format,$time,$to_gmt);
		}
		else $ret='Bad value '.$time.' for date';
	}

	if (! is_object($outputlangs)) $outputlangs=$langs;

	if (eregi('__b__',$format))
	{
		// Here ret is string in PHP setup language (strftime was used). Now we convert to $outputlangs.
		$month=adodb_strftime('%m',$time);
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
		$ret=ereg_replace('__b__',$monthtextshort,$ret);
		$ret=ereg_replace('__B__',$monthtext,$ret);
		//print 'x'.$outputlangs->charset_output.'-'.$ret.'x';
		//return $ret;
	}
	if (eregi('__a__',$format))
	{
		$w=adodb_strftime('%w',$time);
		$dayweek=$outputlangs->transnoentitiesnoconv('Day'.$w);
		$ret=ereg_replace('__A__',$dayweek,$ret);
		$ret=ereg_replace('__a__',dol_substr($dayweek,0,3),$ret);
	}

	return $ret;
}


/**
 *	\brief  	Convert a GM string date into a GM Timestamps date
 *	\param		string			Date in a string
 *				YYYYMMDD
 *				YYYYMMDDHHMMSS
 *				DD/MM/YY or DD/MM/YYYY (this format should not be used anymore)
 *				DD/MM/YY HH:MM:SS or DD/MM/YYYY HH:MM:SS (this format should not be used anymore)
 *				19700101020000 -> 7200
 *  \return		date			Date
 */
function dol_stringtotime($string)
{
	if (eregi('^([0-9]+)\/([0-9]+)\/([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?',$string,$reg))
	{
		// This part of code should not be used.
		dol_syslog("Functions.lib::dol_stringtotime call to function with deprecated parameter", LOG_WARN);
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

	$string=eregi_replace('[^0-9]','',$string);
	$tmp=$string.'000000';
	$date=dol_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4),1);
	return $date;
}


/**
 *	\brief  	Return an array with date info
 *	\param		timestamp		Timestamp
 *	\param		fast			Fast mode
 *	\return		array			Array of informations
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
 *				0 => $origd
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
 *	\remarks	PHP getdate is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
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
function dolibarr_mktime($hour,$minute,$second,$month,$day,$year,$gm=0,$check=1)
{
	return dol_mktime($hour,$minute,$second,$month,$day,$year,$gm,$check);
}

/**
 *	Return a GMT date built from detailed informations
 * 	Replace function mktime not available under Windows if year < 1970
 *	PHP mktime is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 * 	@param		hour			Hour	(can be -1 for undefined)
 *	@param		minute			Minute	(can be -1 for undefined)
 *	@param		second			Second	(can be -1 for undefined)
 *	@param		month			Month
 *	@param		day				Day
 *	@param		year			Year
 *	@param		gm				1=Input informations are GMT values, otherwise local to user
 *	@param		check			0=No check on parameters (Can use day 32, etc...)
 *	@return		timestamp		Date en timestamp, '' if error
 * 	@see 		dol_date
 */
function dol_mktime($hour,$minute,$second,$month,$day,$year,$gm=0,$check=1)
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
		$date=adodb_mktime($hour,$minute,$second,$month,$day,$year,0,$gm);
	}
	else
	{
		$date=mktime($hour,$minute,$second,$month,$day,$year);
	}
	return $date;
}


/* For backward compatibility */
function dolibarr_date($fmt, $timestamp, $gm=0)
{
	return dol_date($fmt, $timestamp, $gm);
}

/**
 *	\brief  	Returns formated date
 *	\param		fmt				Format (Exemple: 'Y-m-d H:i:s')
 *	\param		timestamp		Date. Example: If timestamp=0 and gm=1, return 01/01/1970 00:00:00
 *	\param		gm				1 if timestamp was built with gmmktime, 0 if timestamp was build with mktime
 *	\return		string			Formated date
 * 	\see		dol_mktime
 */
function dol_date($fmt, $timestamp, $gm=0)
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
 * \brief		Return string with formated size
 * \param		size		Size to print
 * \param		shortvalue	Tell if we want long value to use another unit (Ex: 1.5Kb instead of 1500b)
 * \param		shortunit	Use short value of size unit
 * \return		string		Link
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
 * \brief		Show Url link
 * \param		url			Url to show
 * \param		target		Target for link
 * \param		max			Max number of characters to show
 * \return		string		HTML Link
 */
function dol_print_url($url,$target='_blank',$max=32)
{
	if (empty($url)) return '';

	$link='<a href="';
	if (! eregi('^http',$url)) $link.='http://';
	$link.=$url;
	if ($target) $link.='" target="'.$target.'">';
	if (! eregi('^http',$url)) $link.='http://';
	$link.=dol_trunc($url,$max);
	$link.='</a>';
	return $link;
}

/**
 * \brief		Show EMail link
 * \param		email		EMail to show (only email without <Name of recipient>)
 * \param 		cid 		Id of contact if known
 * \param 		socid 		Id of third party if known
 * \param 		addlink		0=no link to create action
 * \param		max			Max number of characters to show
 * \return		string		HTML Link
 */
function dol_print_email($email,$cid=0,$socid=0,$addlink=0,$max=64,$showinvalid=1)
{
	global $conf,$user,$langs;

	$newemail=$email;

	if (empty($email)) return '&nbsp;';

	if (! empty($addlink))
	{
		$newemail='<a href="';
		if (! eregi('^mailto:',$email)) $newemail.='mailto:';
		$newemail.=$email;
		$newemail.='">';
		$newemail.=dol_trunc($email,$max);
		$newemail.='</a>';
		if ($showinvalid && ! isValidEmail($email)) $newemail.=img_warning($langs->trans("ErrorBadEMail",$email));

		if (($cid || $socid) && $conf->agenda->enabled && $user->rights->agenda->myactions->create)
		{
			$type='AC_EMAIL';
			$link='<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;backtopage=1&amp;actioncode='.$type.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
			$newemail='<table class="nobordernopadding"><tr><td>'.$newemail.' </td><td>&nbsp;'.$link.'</td></tr></table>';
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
 * 	\brief 		Format phone numbers according to country
 * 	\param 		phone 		Phone number to format
 * 	\param 		country 	Country to use for formatting
 * 	\param 		cid 		Id of contact if known
 * 	\param 		socid 		Id of third party if known
 * 	\param 		addlink		0=no link to create action
 * 	\param 		separ 		separation between numbers for a better visibility example : xx.xx.xx.xx.xx
 * 	\return 	string 		Formated phone number
 */
function dol_print_phone($phone,$country="FR",$cid=0,$socid=0,$addlink=0,$separ="&nbsp;")
{
	global $conf,$user,$langs;

	// Clean phone parameter
	$phone = ereg_replace("[ .-]","",trim($phone));
	if (empty($phone)) { return ''; }

	$newphone=$phone;
	if (strtoupper($country) == "FR")
	{
		// France
		if (strlen($phone) == 10) {
			$newphone=substr($newphone,0,2).$separ.substr($newphone,2,2).$separ.substr($newphone,4,2).$separ.substr($newphone,6,2).$separ.substr($newphone,8,2);
		}
		elseif (strlen($newphone) == 7)
		{
			$newphone=substr($newphone,0,3).$separ.substr($newphone,3,2).$separ.substr($newphone,5,2);
		}
		elseif (strlen($newphone) == 9)
		{
			$newphone=substr($newphone,0,2).$separ.substr($newphone,2,3).$separ.substr($newphone,5,2).$separ.substr($newphone,7,2);
		}
		elseif (strlen($newphone) == 11)
		{
			$newphone=substr($newphone,0,3).$separ.substr($newphone,3,2).$separ.substr($newphone,5,2).$separ.substr($newphone,7,2).$separ.substr($newphone,9,2);
		}
		elseif (strlen($newphone) == 12)
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
			$url = sprintf($urlmask, urlencode($phone), urlencode($user->clicktodial_poste), urlencode($user->clicktodial_login), urlencode($user->clicktodial_password));
			$newphone='<a href="'.$url.'">'.$newphone.'</a>';
		}

		//if (($cid || $socid) && $conf->agenda->enabled && $user->rights->agenda->myactions->create)
		if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
		{
			$type='AC_TEL';
			if ($addlink == 'AC_FAX') $type='AC_FAX';
			$link='<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;backtopage=1&amp;actioncode='.$type.($cid?'&amp;contactid='.$cid:'').($socid?'&amp;socid='.$socid:'').'">'.img_object($langs->trans("AddAction"),"calendar").'</a>';
			$newphone='<table class="nobordernopadding"><tr><td>'.$newphone.' </td><td>&nbsp;'.$link.'</td></tr></table>';
		}
	}

	return $newphone;
}

function dol_print_ip($ip)
{
	global $conf,$langs;

	print $ip;
	if (! empty($conf->geoipmaxmind->enabled))
	{
		$datafile=$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;

		//$ip='24.24.24.24';
		$datafile='E:\Mes Sites\Web\Admin1\awstats\maxmind\GeoIP.dat';

		include_once(DOL_DOCUMENT_ROOT.'/lib/dolgeoip.class.php');
		$geoip=new DolGeoIP('country',$datafile);
		$countrycode=$geoip->getCountryCodeFromIP($ip);
		if ($countrycode)	// If success, countrycode is us, fr, ...
		{
			if (file_exists(DOL_DOCUMENT_ROOT.'/theme/common/flags/'.$countrycode.'.png'))
			{
				print ' '.img_picto($langs->trans("AccordingToGeoIPDatabase"),DOL_URL_ROOT.'/theme/common/flags/'.$countrycode.'.png','',1);
			}
			else print ' ('.$countrycode.')';
		}
	}
}

/**
 *	\brief      Return true if email syntax is ok
 *	\param	    address     email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
 *	\return     boolean     true if email syntax is OK, false if KO
 */
function isValidEmail($address)
{
	if (eregi(".*<(.+)>", $address, $regs)) {
		$address = $regs[1];
	}
	// 2 letters domains extensions are for countries
	// 3 letters domains extensions: biz|com|edu|gov|int|mil|net|org|pro|...
	if (eregi("^[^@  ]+@([a-zA-Z0-9\-]+\.)+([a-zA-Z0-9\-]{2,3}|asso|aero|coop|info|name)\$",$address))
	{
		return true;
	}
	else
	{
		return false;
	}
}


/**
 * Make a strlen call. Works even in mbstring module not enabled
 *
 * @param unknown_type $string
 * @param unknown_type $stringencoding
 * @return unknown
 */
function dol_strlen($string,$stringencoding='')
{
	global $langs;

	if (empty($stringencoding)) $stringencoding=$langs->charset_output;

	$ret='';
	if (function_exists('mb_strlen'))
	{
		$ret=mb_strlen($string,$stringencoding);
	}
	else
	{
		$ret=strlen($string);
	}
	return $ret;
}

/**
 * Make a substring. Works even in mbstring module not enabled
 *
 * @param unknown_type $string
 * @param unknown_type $start
 * @param unknown_type $length
 * @param unknown_type $stringencoding
 * @return unknown
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
 *	\brief      Truncate a string to a particular length adding '...' if string larger than length.
 * 				If length = max length+1, we do no truncate to avoid having just 1 char replaced with '...'.
 *	\param      string				String to truncate
 *	\param      size				Max string size. 0 for no limit.
 *	\param		trunc				Where to trunc: right, left, middle
 * 	\param		stringencoding		Tell what is source string encoding
 *	\return     string				Truncated string
 *	\remarks	USE_SHORT_TITLE=0 can disable all truncings
 */
function dol_trunc($string,$size=40,$trunc='right',$stringencoding='')
{
	global $conf;

	if ($size==0) return $string;
	if (! defined('USE_SHORT_TITLE') || USE_SHORT_TITLE)
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
	}
	else
	{
		return $string;
	}
}


/**
 *	\brief      Show a picto according to module/object (generic function)
 *	\param      alt         Text of alt on image
 *	\param      object      Objet pour lequel il faut afficher le logo (example: user, group, action, bill, contract, propal, product, ...)
 *	\return     string      Retourne tag img
 */
function img_object($alt, $object)
{
	global $conf,$langs;
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_'.$object.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Show picto (generic function)
 *	\param      alt         		Text on alt and title of image
 *	\param      picto       		Nom de l'image a afficher (Si pas d'extension, on met '.png')
 *	\param		options				Attribut supplementaire a la balise img
 *	\param		pictoisfullpath		If 1, image path is a full path
 *	\return     string      		Retourne tag img
 */
function img_picto($alt, $picto, $options='', $pictoisfullpath=0)
{
	global $conf;
	if (! eregi('(\.png|\.gif)$',$picto)) $picto.='.png';
	if ($pictoisfullpath) return '<img src="'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	\brief      Show picto (generic function)
 *	\param      alt         		Text on alt and title of image
 *	\param      picto       		Nom de l'image a afficher (Si pas d'extension, on met '.png')
 *	\param		options				Attribut supplementaire a la balise img
 *	\param		pictoisfullpath		If 1, image path is a full path
 *	\return     string      		Retourne tag img
 */
function img_picto_common($alt, $picto, $options='', $pictoisfullpath=0)
{
	global $conf;
	if (! eregi('(\.png|\.gif)$',$picto)) $picto.='.png';
	if ($pictoisfullpath) return '<img src="'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
	return '<img src="'.DOL_URL_ROOT.'/theme/common/'.$picto.'" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'"'.($options?' '.$options:'').'>';
}

/**
 *	\brief      Show logo action
 *	\param      alt         Text for image alt and title
 *	\param      numaction   Action to show
 *	\return     string      Return an img tag
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
 *	\brief      Affiche logo fichier
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_file($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Show");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/file.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo refresh
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_refresh($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Refresh");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo dossier
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_folder($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Dossier");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/folder.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo nouveau fichier
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_file_new($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Show");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 \brief      Affiche logo pdf
 \param      alt         Texte sur le alt de l'image
 \param      $size       Taille de l'icone : 3 = 16x16px , 2 = 14x14px
 \return     string      Retourne tag img
 */
function img_pdf($alt = "default",$size=3)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Show");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf'.$size.'.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo +
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_edit_add($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Add");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_add.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}
/**
 *	\brief      Affiche logo -
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_edit_remove($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Remove");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_remove.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo editer/modifier fiche
 *	\param      alt         Texte sur le alt de l'image
 *	\param      float       Si il faut y mettre le style "float: right"
 *	\return     string      Retourne tag img
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
 *	\brief      Affiche logo voir fiche
 *	\param      alt         Texte sur le alt de l'image
 *	\param      float       Si il faut y mettre le style "float: right"
 *	\return     string      Retourne tag img
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
 \brief      Affiche logo effacer
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_delete($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Delete");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}


/**
 *	\brief      Affiche logo help avec curseur "?"
 * 	\param		usehelpcursor
 * 	\param		usealttitle		Texte to use as alt title
 * 	\return     string      	Retourne tag img
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
 *	\brief      Affiche logo info
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_info($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Informations");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo warning
 *	\param      alt         Texte sur le alt de l'image
 *	\param      float       Si il faut afficher le style "float: right"
 *	\return     string      Retourne tag img
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
 *	\brief      Affiche logo redstar
 *	\param      alt         Texte sur le alt de l'image
 *	\param      float       Si il faut afficher le style "float: right"
 *	\return     string      Retourne tag img
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
 \brief      Affiche logo error
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_error($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Error");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/error.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo telephone
 *	\param      alt         Texte sur le alt de l'image
 *	\param		option		Choose of logo
 *	\return     string      Retourne tag img
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
 *	\brief      Affiche logo suivant
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
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
 *	\brief      Affiche logo precedent
 *	\param      alt     Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_previous($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Previous");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/previous.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo bas
 *	\param      alt         Texte sur le alt de l'image
 *	\param      selected    Affiche version "selected" du logo
 *	\return     string      Retourne tag img
 */
function img_down($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Down");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo haut
 *	\param      alt         Texte sur le alt de l'image
 *	\param      selected    Affiche version "selected" du logo
 *	\return     string      Retourne tag img
 */
function img_up($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Up");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo gauche
 *	\param      alt         Texte sur le alt de l'image
 *	\param      selected    Affiche version "selected" du logo
 *	\return     string      Retourne tag img
 */
function img_left($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Left");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo droite
 *	\param      alt         Texte sur le alt de l'image
 *	\param      selected    Affiche version "selected" du logo
 *	\return     string      Retourne tag img
 */
function img_right($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Right");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow_selected.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche logo tick
 *	\param      alt         Texte sur le alt de l'image
 *	\return     string      Retourne tag img
 */
function img_tick($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Active");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0" alt="'.dol_escape_htmltag($alt).'" title="'.dol_escape_htmltag($alt).'">';
}

/**
 *	\brief      Affiche le logo tick si allow
 *	\param      allow       Authorise ou non
 *	\return     string      Retourne tag img
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
 *	\brief      Show mime picto
 *	\param      file		Filename
 * 	\param		alt			Alternate text
 *	\return     string     	Return img tag
 */
function img_mime($file,$alt='')
{
	$mime='other';

	if (eregi('\.xls',$file) || eregi('\.xlsx',$file))        { $mime='xls'; }
	if (eregi('\.ppt',$file) || eregi('\.pptx',$file))        { $mime='ppt'; }
	if (eregi('\.doc',$file) || eregi('\.docx',$file))        { $mime='doc'; }

	if (eregi('\.pdf',$file))        { $mime='pdf'; }
	if (eregi('\.(html|htm)',$file)) { $mime='html'; }
	if (eregi('\.txt',$file))        { $mime='other'; }
	if (eregi('\.php',$file))        { $mime='php'; }
	if (eregi('\.pl',$file))         { $mime='pl'; }
	if (eregi('\.js',$file))         { $mime='jscript'; }
	if (eregi('\.(png|bmp|jpg|jpeg|gif)',$file)) 		$mime='image';
	if (eregi('\.(mp3|ogg|au)',$file))           		$mime='audio';
	if (eregi('\.(avi|mvw|divx|xvid)',$file))    		$mime='video';
	if (eregi('\.(zip|rar|gz|tgz|z|cab|bz2)',$file))	$mime='archive';
	if (empty($alt)) $alt='Mime type: '.$mime;

	$mime.='.png';
	return '<img src="'.DOL_URL_ROOT.'/theme/common/mime/'.$mime.'" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 *	\brief      Show information for admin users
 *	\param      text			Text info
 *	\param      infoonimgalt	Info is shown on alt of star picto, otherwise it is show on output
 *	\return		string			String with info text
 */
function info_admin($texte,$infoonimgalt=0)
{
	global $conf,$langs;
	$s='';
	if ($infoonimgalt)
	{
		$s.=img_picto($texte,'star');
	}
	else
	{
		$s.='<div class="info">';
		$s.=img_picto($langs->trans("InfoAdmin"),'star');
		$s.=' ';
		$s.=$texte;
		$s.='</div>';
	}
	return $s;
}


/**
 *	\brief      Check permissions of a user to show a page and an object.
 *	\param      user      	  	User to check
 *	\param      feature			    Feature to check (in most cases, it's module name)
 *	\param      objectid      	Object ID if we want to check permission on on object (optionnal)
 *	\param      dbtablename    	Table name where object is stored. Not used if objectid is null (optionnal)
 *	\param      feature2		    Feature to check (second level of permission)
 *  \param      dbt_keyfield    Field name for socid foreign key if not fk_soc. (optionnal)
 *  \param      dbt_select      Field name for select if not rowid. (optionnal)
 */
function restrictedArea($user, $feature='societe', $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid')
{
	global $db, $conf;

	//dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename,$feature2,$dbt_socfield,$dbt_select");
	if ($dbt_select != 'rowid') $objectid = "'".$objectid."'";

	//print "user_id=".$user->id.", feature=".$feature.", feature2=".$feature2.", object_id=".$objectid;
	//print ", dbtablename=".$dbtablename.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select;
	//print ", user_societe_contact_lire=".$user->rights->societe->contact->lire."<br>";

	// Check read permission from module
	// TODO Replace "feature" param by permission for reading
	$readok=1;
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
	else if ($feature == 'ecm')
	{
		if (! $user->rights->ecm->download) $readok=0;
	}
	else if (! empty($feature2))	// This should be used for future changes
	{
		if (empty($user->rights->$feature->$feature2->lire)
		&& empty($user->rights->$feature->$feature2->read)) $readok=0;
	}
	else if (! empty($feature) && ($feature!='user' && $feature!='usergroup'))		// This is for old permissions
	{
		if (empty($user->rights->$feature->lire)
		&& empty($user->rights->$feature->read)) $readok=0;
	}
	if (! $readok)
	{
		//print "Read access is down";
		accessforbidden();
	}
	//print "Read access is ok";

	// Check write permission from module
	$createok=1;
	if ( (isset($_GET["action"])  && $_GET["action"]  == 'create')
	|| (isset($_POST["action"]) && $_POST["action"] == 'create') )
	{
		if ($feature == 'societe')
		{
			if (! $user->rights->societe->creer && ! $user->rights->fournisseur->creer) $createok=0;
		}
		else if ($feature == 'contact')
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
			if (empty($user->rights->$feature->creer)
			&& empty($user->rights->$feature->write)) $createok=0;
		}
		if (! $createok) accessforbidden();
		//print "Write access is ok";
	}

	// If we have a particular object to check permissions on
	if ($objectid > 0)
	{
		$sql='';

		// If dbtable not defined, we use same name for table than module name
		if (empty($dbtablename)) $dbtablename = $feature;

		// Check permission for object with entity
		if ($feature == 'user' || $feature == 'usergroup' || $feature == 'produit' || $feature == 'service' || $feature == 'produit|service')
		{
			$sql = "SELECT dbt.".$dbt_select;
			$sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
			$sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
			$sql.= " AND dbt.entity IN (0,".$conf->entity.")";
		}
		else if ($feature == 'societe')
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
				$sql.= " AND s.entity = ".$conf->entity;
			}
			// If multicompany and internal users with all permissions, check user is in correct entity
			else if ($conf->global->MAIN_MODULE_MULTICOMPANY)
			{
				$sql = "SELECT s.rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
				$sql.= " WHERE s.rowid = ".$objectid;
				$sql.= " AND s.entity = ".$conf->entity;
			}
		}
		else
		{
			// If external user: Check permission for external users
			if ($user->societe_id > 0)
			{
				$sql = "SELECT dbt.fk_soc";
				$sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql.= " WHERE dbt.rowid = ".$objectid;
				$sql.= " AND dbt.fk_soc = ".$user->societe_id;
			}
			// If internal user: Check permission for internal users that are restricted on their objects
			else if (! $user->rights->societe->client->voir)
			{
				$sql = "SELECT sc.fk_soc";
				$sql.= " FROM (".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql.= ", ".MAIN_DB_PREFIX."societe as s)";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = dbt.".$dbt_keyfield;
				$sql.= " WHERE dbt.rowid = ".$objectid;
				$sql.= " AND dbt.fk_soc = s.rowid";
				$sql.= " AND s.entity = ".$conf->entity;
				$sql.= " AND IFNULL(sc.fk_user, ".$user->id.") = ".$user->id;
			}
			// If multicompany and internal users with all permissions, check user is in correct entity
			else if ($conf->global->MAIN_MODULE_MULTICOMPANY)
			{
				$sql = "SELECT dbt.".$dbt_select;
				$sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
				$sql.= " AND dbt.entity = ".$conf->entity;
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

	return 1;
}


/**
 *	\brief      Affiche message erreur de type acces interdit et arrete le programme
 *	\param		message			Force error message
 *	\param		printheader		Affiche avant le header
 *	\remarks    L'appel a cette fonction termine le code.
 */
function accessforbidden($message='',$printheader=1,$printfooter=1,$showonlymessage=0)
{
	global $user, $langs;
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
 *	\brief      Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontee des bugs.
 *				On doit appeler cette fonction quand une erreur technique bloquante est rencontree.
 *				Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
 *				renvoyer leur erreur par l'intermediaire de leur propriete "error".
 *	\param      db      Database handler
 *	\param      error	Chaine erreur ou tableau de chaines erreur complementaires a afficher
 */
function dol_print_error($db='',$error='')
{
	global $conf,$langs,$argv;
	$out = '';
	$syslog = '';

	// Si erreur intervenue avant chargement langue
	if (! $langs)
	{
		require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
		$langs = new Translate("", $conf);
		$langs->load("main");
	}
	$langs->load("errors");

	if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
	{
		$out.=$langs->trans("DolibarrHasDetectedError").".<br>\n";
		if (! empty($conf->global->MAIN_FEATURES_LEVEL))
		$out.="You use an experimental level of features, so please do NOT report any bugs, anywhere, until going back to MAIN_FEATURES_LEVEL = 0.<br>\n";
		$out.=$langs->trans("InformationToHelpDiagnose").":<br>\n";

		$out.="<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";;
		$out.="<b>".$langs->trans("Date").":</b> ".dol_print_date(time(),'dayhourlog')."<br>\n";;
		if (isset($conf->global->MAIN_FEATURES_LEVEL)) $out.="<b>".$langs->trans("LevelOfFeature").":</b> ".$conf->global->MAIN_FEATURES_LEVEL."<br>\n";;
		$out.="<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";;
		$out.="<b>".$langs->trans("Referer").":</b> ".$_SERVER["HTTP_REFERER"]."<br>\n";;
		$out.="<b>".$langs->trans("RequestedUrl").":</b> ".$_SERVER["REQUEST_URI"]."<br>\n";;
		$out.="<b>".$langs->trans("MenuManager").":</b> ".$conf->left_menu.'/'.$conf->top_menu."<br>\n";
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
	if ($_SERVER['DOCUMENT_ROOT'] && function_exists('xdebug_call_file'))
	{
		$out.='<b>XDebug informations:</b>'."<br>\n";
		$out.='File: '.xdebug_call_file()."<br>\n";
		$out.='Line: '.xdebug_call_line()."<br>\n";
		$out.="<br>\n";
	}

	global $dolibarr_main_prod;
	if (empty($dolibarr_main_prod)) print $out;
	else print 'Sorry, an error occured but the parameter $dolibarr_main_prod is defined in conf file so no message is reported on browsers. Please read the log file for error message.';
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
 *	\brief  Deplacer les fichiers telecharges, apres quelques controles divers
 *	\param	src_file			Source filename
 *	\param	dest_file			Target filename
 * 	\param	allowoverwrite		Overwrite if exists
 *	\return int         		>0 if OK, <0 if KO, Name of virus if virus found
 */
function dol_move_uploaded_file($src_file, $dest_file, $allowoverwrite)
{
	global $conf;

	$file_name = $dest_file;

	// If we need to make a virus scan
	if ($conf->global->MAIN_USE_AVSCAN)
	{
		require_once(DOL_DOCUMENT_ROOT.'/lib/security.lib.php');
		$malware = dol_avscan_file($src_file);
		if ($malware) return $malware;
	}

	// Security:
	// On renomme les fichiers avec extention script web car si on a mis le rep
	// documents dans un rep de la racine web (pas bien), cela permet d'executer
	// du code a la demande.
	if (eregi('\.htm|\.html|\.php|\.pl|\.cgi$',$file_name))
	{
		$file_name.= '.noexe';
	}

	// Security:
	// On interdit fichiers caches, remontees de repertoire ainsi que les pipes dans
	// les noms de fichiers.
	if (eregi('^\.',$src_file) || eregi('\.\.',$src_file) || eregi('[<>|]',$src_file))
	{
		dol_syslog("Refused to deliver file ".$src_file, LOG_WARNING);
		return -1;
	}

	// Security:
	// On interdit fichiers caches, remontees de repertoire ainsi que les pipe dans
	// les noms de fichiers.
	if (eregi('^\.',$dest_file) || eregi('\.\.',$dest_file) || eregi('[<>|]',$dest_file))
	{
		dol_syslog("Refused to deliver file ".$dest_file, LOG_WARNING);
		return -1;
	}

	// The file functions are ISO and data are stored in UTF8 in memory.
	$src_file_iso=utf8_decode($src_file);
	$file_name_iso=utf8_decode($file_name);

	// Check if destination file already exists
	if (! $allowoverwrite)
	{
		if (file_exists($file_name_iso))
		{
			dol_syslog("Functions.lib::dol_move_uploaded_file File ".$file_name." already exists", LOG_WARNING);
			return -2;
		}
	}

	// Move file
	$return=move_uploaded_file($src_file_iso, $file_name_iso);
	if ($return)
	{
		if (! empty($conf->global->MAIN_UMASK)) @chmod($file_name, octdec($conf->global->MAIN_UMASK));
		dol_syslog("Functions.lib::dol_move_uploaded_file Success to move ".$src_file." to ".$file_name." - Umask=".$conf->global->MAIN_UMASK, LOG_DEBUG);
		return 1;
	}
	else
	{
		dol_syslog("Functions.lib::dol_move_uploaded_file Failed to move ".$src_file." to ".$file_name, LOG_ERR);
		return -3;
	}
}


/**
 *	\brief      Show title line of an array
 *	\param	    name        libelle champ
 *	\param	    file        url pour clic sur tri
 *	\param	    field       champ de tri
 *	\param	    begin       ("" par defaut)
 *	\param	    options     ("" par defaut)
 *	\param      td          options de l'attribut td ("" par defaut)
 *	\param      sortfield   field currently used to sort
 *	\param      sortorder   ordre du tri
 */
function print_liste_field_titre($name, $file, $field, $begin="", $options="", $td="", $sortfield="", $sortorder="")
{
	global $conf;
	//print "$name, $file, $field, $begin, $options, $td, $sortfield, $sortorder<br>\n";

	// Le champ de tri est mis en evidence.
	// Exemple si (sortfield,field)=("nom","xxx.nom") ou (sortfield,field)=("nom","nom")
	if ($field && ($sortfield == $field || $sortfield == ereg_replace("^[^\.]+\.","",$field)))
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
		//print "&nbsp;";
		print '<img width="2" src="'.DOL_URL_ROOT.'/theme/common/transparent.png" alt="">';
		if (! $sortorder)
		{
			print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
			print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
		}
		else
		{
			if ($field != $sortfield)
			{
				print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
				print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
			}
			else {
				$sortorder=strtoupper($sortorder);
				if ($sortorder == 'DESC' ) {
					print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
					print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">'.img_up("Z-A",1).'</a>';
				}
				if ($sortorder == 'ASC' ) {
					print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">'.img_down("A-Z",1).'</a>';
					print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
				}
			}
		}
	}
	print "</td>";
}

/**
 *	\brief  Affichage d'un titre
 *	\param	titre			Le titre a afficher
 */
function print_titre($titre)
{
	print '<div class="titre">'.$titre.'</div>';
}

/**
 *	\brief  Affichage d'un titre d'une fiche, aligne a gauche
 *	\param	titre				Le titre a afficher
 *	\param	mesg				Message suplementaire a afficher a droite
 *	\param	picto				Icon to use before title (should be a 32x32 transparent png file)
 *	\param	pictoisfullpath		1=Icon name is a full absolute url of image
 */
function print_fiche_titre($titre, $mesg='', $picto='title.png', $pictoisfullpath=0)
{
	global $conf;

	if ($picto == 'setup') $picto='title.png';
	if (empty($conf->browser->firefox) && $picto=='title.png') $picto='title.gif';

	print "\n";
	print '<table summary="" width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;"><tr>';
	if (empty($conf->browser->phone) && $picto && $titre) print '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, 'id="pictotitle"', $pictoisfullpath).'</td>';
	print '<td class="nobordernopadding" valign="middle">';
	print '<div class="titre">'.$titre.'</div>';
	print '</td>';
	if (strlen($mesg))
	{
		print '<td class="nobordernopadding" align="right" valign="middle"><b>'.$mesg.'</b></td>';
	}
	print '</tr></table>'."\n";
}

/**
 *	\brief  Print a title with navigation controls for pagination
 *	\param	titre				Title to show (required)
 *	\param	page				Numero of page (required)
 *	\param	file				Url of page (required)
 *	\param	options         	parametres complementaires lien ('' par defaut)
 *	\param	sortfield       	champ de tri ('' par defaut)
 *	\param	sortorder       	ordre de tri ('' par defaut)
 *	\param	center          	chaine du centre ('' par defaut)
 *	\param	num					number of records found by select with limit+1
 *	\param	totalnboflines		Total number of records/lines for all pages (if known)
 *	\param	picto				Icon to use before title (should be a 32x32 transparent png file)
 *	\param	pictoisfullpath		1=Icon name is a full absolute url of image
 */
function print_barre_liste($titre, $page, $file, $options='', $sortfield='', $sortorder='', $center='', $num=-1, $totalnboflines=0, $picto='title.png', $pictoisfullpath=0)
{
	global $conf,$langs;

	if ($picto == 'setup') $picto='title.png';
	if (empty($conf->browser->firefox) && $picto=='title.png') $picto='title.gif';

	if ($num > $conf->liste_limit or $num == -1)
	{
		$nextpage = 1;
	}
	else
	{
		$nextpage = 0;
	}

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
			if ($picto && $titre) print '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, '', $pictoisfullpath).'</td>';
			print '<td class="nobordernopadding">';
			print '<div class="titre">'.$titre.'</div>';
			$pagelist.= $langs->trans('Page').' '.($page+1);
			print '</td>';
		}
	}
	else
	{
		if ($picto && $titre) print '<td class="nobordernopadding" width="40" align="left" valign="middle">'.img_picto('',$picto, '', $pictoisfullpath).'</td>';
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

	print '</tr></table>';
}

/**
 *	\brief  	Fonction servant a afficher les fleches de navigation dans les pages de listes
 *	\param	page				Numero of page
 *	\param	file				Lien
 *	\param	options         	Autres parametres d'url a propager dans les liens ("" par defaut)
 *	\param	nextpage	    	Faut-il une page suivante
 *	\param	betweenarraows		HTML Content to show between arrows
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
 *	\brief  	Remove a file or several files with a mask
 *	\param		file			File to delete or mask of file to delete
 * 	\param		disableglob		Disable usage of globa like *
 *	\param		boolean			True if file deleted, False if error
 */
function dol_delete_file($file,$disableglob=0)
{
	$ok=true;
	$newfile=utf8_check($file)?utf8_decode($file):$file;	// glob function accepts only ISO string
	if (empty($disableglob))
	{
		foreach (glob($newfile) as $filename)
		{
			$ok=unlink($filename);	// The unlink encapsulated by dolibarr
			if ($ok) dol_syslog("Removed file ".$filename,LOG_DEBUG);
			else dol_syslog("Failed to remove file ".$filename,LOG_ERR);
		}
	}
	else
	{
		$ok=unlink($newfile);		// The unlink encapsulated by dolibarr
		if ($ok) dol_syslog("Removed file ".$newfile,LOG_DEBUG);
		else dol_syslog("Failed to remove file ".$newfile,LOG_ERR);
	}
	return $ok;
}

/**
 *	\brief  	Remove a directory
 *	\param		file			Directory to delete
 * 	\return		boolean			True if success, false if error
 * 	\remarks	If directory is not empty, return false
 */
function dol_delete_dir($dir)
{
	$newdir=utf8_check($dir)?utf8_decode($dir):$dir;
	return rmdir($newdir);
}

/**
 *	\brief  	Remove a directory $dir and its subdirectories
 *	\param		file			Dir to delete
 *	\param		count			Counter to count nb of deleted elements
 *	\return		int				Number of files and directory removed
 */
function dol_delete_dir_recursive($dir,$count=0)
{
	//dol_syslog("functions.lib:dol_delete_dir_recursive ".$dir,LOG_DEBUG);
	$newdir=utf8_check($dir)?utf8_decode($dir):$dir;
	if ($handle = opendir("$newdir"))
	{
		while (false !== ($item = readdir($handle)))
		{
			// readdir return value in ISO and we want UTF8 in memory
			$newitem=$item;
			if (! utf8_check($item)) $item=utf8_encode($item);

			if ($item != "." && $item != "..")
			{
				if (is_dir("$newdir/$newitem"))
				{
					$count=dol_delete_dir_recursive("$dir/$item",$count);
				}
				else
				{
					unlink("$newdir/$newitem");
					$count++;
					//echo " removing $dir/$item<br>\n";
				}
			}
		}
		closedir($handle);
		rmdir($newdir);
		$count++;
		//echo "removing $dir<br>\n";
	}

	//echo "return=".$count;
	return $count;
}


/**
 *		\brief      Fonction qui retourne un taux de tva formate pour visualisation
 *		\remarks    Fonction utilisee dans les pdf et les pages html
 *		\param	    rate			Rate value to format (19.6 19,6 19.6% 19,6%,...)
 *		\param		foundpercent	Add a percent % sign in output
 *		\param		info_bits		Miscellanous information on vat
 *		\return		string			Chaine avec montant formate (19,6 ou 19,6% ou 8.5% *)
 */
function vatrate($rate,$addpercent=false,$info_bits=0)
{
	// Test for compatibility
	if (eregi('%',$rate))
	{
		$rate=eregi_replace('%','',$rate);
		$addpercent=true;
	}
	if (eregi('\*',$rate) || eregi(MAIN_LABEL_MENTION_NPR,$rate))
	{
		$rate=eregi_replace('\*','',$rate);
		$info_bits |= 1;
	}

	$ret=price($rate,0,'',0,0).($addpercent?'%':'');
	if ($info_bits & 1) $ret.=' '.MAIN_LABEL_MENTION_NPR;
	return $ret;
}


/**
 *		\brief      Fonction qui formate un montant pour visualisation
 *		\remarks    Fonction utilisee dans les pdf et les pages html
 *		\param	    amount			Montant a formater
 *		\param	    html			Type de formatage, html ou pas (par defaut)
 *		\param	    outlangs		Objet langs pour formatage text
 *		\param		trunc			1=Tronque affichage si trop de decimales,0=Force le non troncage
 *		\param		rounding		Nbre decimals minimum.
 *		\return		string			Chaine avec montant formate
 *		\seealso	price2num		Revert function of price
 */
function price($amount, $html=0, $outlangs='', $trunc=1, $rounding=2)
{
	global $langs,$conf;

	$nbdecimal=$rounding;

	// Output separators by default (french)
	$dec=','; $thousand=' ';

	// If $outlangs not forced, we use use language
	if (! is_object($outlangs)) $outlangs=$langs;

	if ($outlangs->trans("SeparatorDecimal") != "SeparatorDecimal")  $dec=$outlangs->trans("SeparatorDecimal");
	if ($outlangs->trans("SeparatorThousand")!= "SeparatorThousand") $thousand=$outlangs->trans("SeparatorThousand");
	//print "amount=".$amount." html=".$html." trunc=".$trunc." nbdecimal=".$nbdecimal." dec=".$dec." thousand=".$thousand;

	//print "amount=".$amount."-";
	$amount = ereg_replace(',','.',$amount);	// should be useless
	//print $amount."-";
	$datas = split('\.',$amount);
	$decpart = isset($datas[1])?$datas[1]:'';
	$decpart = eregi_replace('0+$','',$decpart);	// Supprime les 0 de fin de partie decimale
	//print "decpart=".$decpart."<br>";
	$end='';

	// We increase nbdecimal if there is more decimal than asked (to not loose information)
	if (strlen($decpart) > $nbdecimal) $nbdecimal=strlen($decpart);
	// Si on depasse max
	if ($trunc && $nbdecimal > $conf->global->MAIN_MAX_DECIMALS_SHOWN)
	{
		$nbdecimal=$conf->global->MAIN_MAX_DECIMALS_SHOWN;
		if (eregi('\.\.\.',$conf->global->MAIN_MAX_DECIMALS_SHOWN))
		{
			// Si un affichage est tronque, on montre des ...
			$end='...';
		}
	}

	// Format number
	if ($html)
	{
		$output=ereg_replace(' ','&nbsp;',number_format($amount, $nbdecimal, $dec, $thousand));
	}
	else
	{
		$output=number_format($amount, $nbdecimal, $dec, $thousand);
	}
	$output.=$end;

	return $output;
}

/**
 *	\brief     		Function that return a number with universal decimal format (decimal separator is '.') from
 *					an amount typed by a user.
 *	\remarks   		Function to use on each input amount before any numeric test or database insert.
 *	\param	    	amount			Amount to convert/clean
 *	\param	    	rounding		''=No rounding
 * 									'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
 *									'MT'=Round to Max for totals with Tax (MAIN_MAX_DECIMALS_TOT)
 *									'MS'=Round to Max Shown (MAIN_MAX_DECIMALS_SHOWN)
 * 	\param			alreadysqlnb	Put 1 if you know that content is already universal format number
 *	\return			string			Amount with universal numeric format (Example: '99.99999')
 *	\seealso		price			Opposite function of price2num
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

	// Convert value to universal number format (no thousand separator, '.' as decimal separator)
	if ($alreadysqlnb != 1)	// If not a PHP number or unknown, we change format
	{
		//print 'ZZ'.$nbofdec.'=>'.$amount.'<br>';

		// Convert amount to format with dolibarr dec and thousand (this is because PHP convert a number
		// to format defined by LC_NUMERIC after a calculation and we want source format to be like defined by Dolibarr setup.
		if (is_numeric($amount))
		{
			$nbofdec=max(0,strlen($amount-intval($amount))-2);
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
		if (strlen($nbofdectoround)) $amount = round($amount,$nbofdectoround);	// $nbofdectoround can be 0.
		else return 'ErrorBadParameterProvidedToFunction';
		//print 'ZZ'.$nbofdec.'-'.$nbofdectoround.'=>'.$amount.'<br>';

		// Convert amount to format with dolibarr dec and thousand (this is because PHP convert a number
		// to format defined by LC_NUMERIC after a calculation and we want source format to be defined by Dolibarr setup.
		if (is_numeric($amount))
		{
			$nbofdec=max(0,strlen($amount-intval($amount))-2);
			$amount=number_format($amount,min($nbofdec,$nbofdectoround),$dec,$thousand);		// Convert amount to format with dolibarr dec and thousand
		}
		//print "RR".$amount.'<br>';

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
 *	\brief	Return vat rate of a product in a particular selling country
 */
function get_product_vat_for_country($idprod, $countrycode)
{
	global $db;

	$product=new Product($db);
	$product->fetch($idprod);

	// \TODO Read rate according to countrycode
	// For the moment only one rate supported

	return $product->tva_tx;
}


/**
 *	\brief      	Fonction qui renvoie la tva d'une ligne (en fonction du vendeur, acheteur et taux du produit)
 *	\remarks    	Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 *					Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 *					Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
 *					Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par defaut=TVA du produit vendu. Fin de regle
 *					Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise avec num TVA) intra alors TVA par defaut=0. Fin de regle
 *					Sinon TVA proposee par defaut=0. Fin de regle.
 *	\param      	societe_vendeuse    	Objet societe vendeuse
 *	\param      	societe_acheteuse   	Objet societe acheteuse
 *	\param      	taux_produit        	Taux par defaut du produit vendu (old way to get product vat rate)
 *	\param      	idprod					Id product (new way to get product vat rate)
 *	\return     	float               	Taux de tva a appliquer, -1 si ne peut etre determine
 */
function get_default_tva($societe_vendeuse, $societe_acheteuse, $taux_produit, $idprod=0)
{
	if (!is_object($societe_vendeuse)) return -1;
	if (!is_object($societe_acheteuse)) return -1;

	dol_syslog("get_default_tva vendeur_assujeti=".$societe_vendeuse->tva_assuj." pays_vendeur=".$societe_vendeuse->pays_code.", seller in cee=".$societe_vendeuse->isInEEC().", pays_acheteur=".$societe_acheteuse->pays_code.", buyer in cee=".$societe_acheteuse->isInEEC().", taux_produit(deprecated)=".$taux_produit.", idprod=".$idprod);

	// Si vendeur non assujeti a TVA (tva_assuj vaut 0/1 ou franchise/reel)
	if (is_numeric($societe_vendeuse->tva_assuj) && ! $societe_vendeuse->tva_assuj) return 0;
	if (! is_numeric($societe_vendeuse->tva_assuj) && $societe_vendeuse->tva_assuj=='franchise') return 0;

	// Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
	//if (is_object($societe_acheteuse) && ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id) && ($societe_acheteuse->tva_assuj == 1 || $societe_acheteuse->tva_assuj == 'reel'))
	// Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concercne si le test suivant n'est pas suffisant.
	if ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id)
	{
		if ($idprod) return get_product_vat_for_country($idprod,$societe_vendeuse->pays_code);
		if (strlen($taux_produit) == 0) return -1;	// Si taux produit = '', on ne peut determiner taux tva
		return $taux_produit;
	}

	// Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
	// Non gere

	// Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par defaut=TVA du produit vendu. Fin de regle
	if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()) && ! $societe_acheteuse->tva_intra)
	{
		if ($idprod) return get_product_vat_for_country($idprod,$societe_vendeuse->pays_code);
		if (strlen($taux_produit) == 0) return -1;	// Si taux produit = '', on ne peut determiner taux tva
		return $taux_produit;
	}

	// Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise avec num TVA intra) alors TVA par defaut=0. Fin de regle
	if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()) && $societe_acheteuse->tva_intra)
	{
		return 0;
	}

	// Sinon la TVA proposee par defaut=0. Fin de regle.
	// Rem: Cela signifie qu'au moins un des 2 est hors Communaute europeenne et que le pays differe
	return 0;
}


/**
 \brief      	Fonction qui renvoie si tva doit etre tva percue recuperable
 \remarks    	Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
 Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par defaut=TVA du produit vendu. Fin de regle
 Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise avec num TVA) intra alors TVA par defaut=0. Fin de regle
 Sinon TVA proposee par defaut=0. Fin de regle.
 \param      	societe_vendeuse    	Objet societe vendeuse
 \param      	societe_acheteuse   	Objet societe acheteuse
 \param      	taux_produit        	Taux par defaut du produit vendu
 \return     	float               	0 or 1
 */
function get_default_npr($societe_vendeuse, $societe_acheteuse, $taux_produit)
{

	return 0;
}


/**
 *	\brief  Return yes or no in current language
 *	\param	yesno			Value to test (1, 'yes', 'true' or 0, 'no', 'false')
 *	\param	case			1=Yes/No, 0=yes/no
 *	\param	color			0=texte only, 1=Text is formated with a color font style ('ok' or 'error'), 2=Text is formated with 'ok' color.
 */
function yn($yesno, $case=1, $color=0)
{
	global $langs;
	$result='unknown';
	if ($yesno == 1 || strtolower($yesno) == 'yes' || strtolower($yesno) == 'true') 	// A mettre avant test sur no a cause du == 0
	{
		$result=($case?$langs->trans("Yes"):$langs->trans("yes"));
		$class='ok';
	}
	elseif ($yesno == 0 || strtolower($yesno) == 'no' || strtolower($yesno) == 'false')
	{
		$result=($case?$langs->trans("No"):$langs->trans("no"));
		if ($color == 2) $class='ok';
		else $class='error';
	}
	if ($color) return '<font class="'.$class.'">'.$result.'</font>';
	return $result;
}


/**
 *	\brief      Return a path to class a directory according to an id
 *	\param      $num        Id to develop
 *	\param      $level		Level of development (1, 2 or 3 level)
 * 	\param		$alpha		Use alpha ref
 *	\remarks    Examples: 	'001' with level 3->"0/0/1/", '015' with level 3->"0/1/5/"
 *	\remarks    Examples: 	'ABC-1' with level 3 ->"0/0/1/", '015' with level 1->"5/"
 */
function get_exdir($num,$level=3,$alpha=0)
{
	$path = '';
	if (empty($alpha)) $num = eregi_replace('[^0-9]','',$num);
	else $num = eregi_replace('^.*\-','',$num);
	$num = substr("000".$num, -$level);
	if ($level == 1) $path = substr($num,0,1).'/';
	if ($level == 2) $path = substr($num,1,1).'/'.substr($num,0,1).'/';
	if ($level == 3) $path = substr($num,2,1).'/'.substr($num,1,1).'/'.substr($num,0,1).'/';
	return $path;
}

/**
 *	\brief      Creation of a directory (recursive)
 *	\param      $dir        Directory to create
 *	\return     int         < 0 if KO, >= 0 if OK
 */
function create_exdir($dir)
{
	global $conf;

	dol_syslog("functions.lib::create_exdir: dir=".$dir,LOG_INFO);

	if (@is_dir($dir)) return 0;

	$nberr=0;
	$nbcreated=0;

	$ccdir = '';
	$cdir = explode("/",$dir);
	for ($i = 0 ; $i < sizeof($cdir) ; $i++)
	{
		if ($i > 0) $ccdir .= '/'.$cdir[$i];
		else $ccdir = $cdir[$i];
		if (eregi("^.:$",$ccdir,$regs)) continue;	// Si chemin Windows incomplet, on poursuit par rep suivant

		// Attention, le is_dir() peut echouer bien que le rep existe.
		// (ex selon config de open_basedir)
		if ($ccdir)
		{
			if (! @is_dir($ccdir))
			{
				dol_syslog("functions.lib::create_exdir: Directory '".$ccdir."' does not exists or is outside open_basedir PHP setting.",LOG_DEBUG);

				umask(0);
				$dirmaskdec=octdec('0755');
				if (! empty($conf->global->MAIN_UMASK)) $dirmaskdec=octdec($conf->global->MAIN_UMASK);
				$dirmaskdec |= octdec('0110');
				if (! @mkdir($ccdir, $dirmaskdec))
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
 *	\brief   Retourne le picto champ obligatoire
 *	\return  string		Chaine avec picto obligatoire
 */
function picto_required()
{
	return '<b>*</b>';
}


/**
 *	\brief   	Clean a string from all HTML tags and entities
 *	\param   	StringHtml			String to clean
 *	\param		removelinefeed		Replace also all lines feeds by a space
 *	\return  	string	    		String cleaned
 */
function dol_string_nohtmltag($StringHtml,$removelinefeed=1)
{
	$pattern = "<[^>]+>";
	$temp = dol_entity_decode($StringHtml);
	$temp = ereg_replace($pattern,"",$temp);

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
 *	\brief		Replace CRLF in string with a HTML BR tag.
 *	\param		string2encode		String to encode
 *	\param		nl2brmode			0=Adding br before \n, 1=Replacing \n by br
 *	\return		string				String encoded
 */
function dol_nl2br($stringtoencode,$nl2brmode=0)
{
	if (! $nl2brmode) return nl2br($stringtoencode);
	else
	{
		$ret=ereg_replace("\r","",$stringtoencode);
		$ret=ereg_replace("\n","<br>",$ret);
		return $ret;
	}
}

/**
 *	\brief		This function is called to encode a string into a HTML string but differs from htmlentities because
 * 				all entities but &,<,> are converted. This permits to encode special chars to entities with no double
 *              encoding for already encoded HTML strings.
 * 				This function also remove last CR/BR.
 *	\param		stringtoencode		String to encode
 *	\param		nl2brmode			0=Adding br before \n, 1=Replacing \n by br (for use with FPDF writeHTMLCell function for example)
 *	\remarks	For PDF usage, you can show text by 2 ways:
 *				- writeHTMLCell -> param must be encoded into HTML.
 *				- MultiCell -> param must not be encoded into HTML.
 *				Because writeHTMLCell convert also \n into <br>, if function
 *				is used to build PDF, nl2brmode must be 1.
 */
function dol_htmlentitiesbr($stringtoencode,$nl2brmode=0,$pagecodefrom='UTF-8')
{
	if (dol_textishtml($stringtoencode))
	{
		//$trans = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT); var_dump($trans);
		$newstring=eregi_replace('<br( [ a-zA-Z_="]*)?/?>','<br>',$stringtoencode);	// Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
		$newstring=eregi_replace('<br>$','',$newstring);	// Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
		$newstring=strtr($newstring,array('&'=>'__and__','<'=>'__lt__','>'=>'__gt__','"'=>'__dquot__'));
		$newstring=@htmlentities($newstring,ENT_COMPAT,$pagecodefrom);	// Make entity encoding
		$newstring=strtr($newstring,array('__and__'=>'&','__lt__'=>'<','__gt__'=>'>','__dquot__'=>'"'));
		// If already HTML, CR should be <br> so we don't change \n
	}
	else {
		// We use @ to avoid warning on PHP4 that does not support entity encoding from UTF8;
		$newstring=dol_nl2br(@htmlentities($stringtoencode,ENT_COMPAT,$pagecodefrom),$nl2brmode);
	}
	// Other substitutions that htmlentities does not do
	$newstring=str_replace(chr(128),'&euro;',$newstring);	// 128 = 0x80. Not in html entity table.
	return $newstring;
}

/**
 *	\brief		This function is called to decode a HTML string (it decodes entities and br tags)
 *	\param		stringtodecode		String to decode
 */
function dol_htmlentitiesbr_decode($stringtodecode,$pagecodeto='UTF-8')
{
	// We use @ to avoid warning on PHP4 that does not support entity decoding to UTF8;
	$ret=@html_entity_decode($stringtodecode,ENT_COMPAT,$pagecodeto);
	$ret=eregi_replace("\r\n".'<br( [ a-zA-Z_="]*)?/?>',"<br>",$ret);
	$ret=eregi_replace('<br( [ a-zA-Z_="]*)?/?>'."\r\n","\r\n",$ret);
	$ret=eregi_replace('<br( [ a-zA-Z_="]*)?/?>'."\n","\n",$ret);
	$ret=eregi_replace('<br( [ a-zA-Z_="]*)?/?>',"\n",$ret);
	return $ret;
}

/**
 *	\brief		This function remove all ending \n and br at end
 *	\param		stringtodecode		String to decode
 */
function dol_htmlcleanlastbr($stringtodecode)
{
	$ret=eregi_replace('(<br>|<br( [ a-zA-Z_="]*)?/?>|'."\n".'|'."\r".')+$',"",$stringtodecode);
	return $ret;
}

/**
 *	\brief		This function is called to decode a string with HTML entities (it decodes entities tags)
 * 	\param   	string      stringhtml
 * 	\return  	string	  	decodestring
 */
function dol_entity_decode($stringhtml,$pagecodeto='UTF-8')
{
	// We use @ to avoid warning on PHP4 that does not support entity decoding to UTF8;
	$ret=@html_entity_decode($stringhtml,ENT_COMPAT,$pagecodeto);
	return $ret;
}

/**
 *	\brief		Check if a string is a correct iso string
 *				If not, it will we considered not HTML encoded even if it is by FPDF.
 *	\remarks	Example, if string contains euro symbol that has ascii code 128.
 *	\param		s		String to check
 *	\return	int		0 if bad iso, 1 if good iso
 */
function dol_string_is_good_iso($s)
{
	$len=strlen($s);
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
 *	\brief		Return nb of lines of a clear text
 *	\param		s			String to check
 * 	\param		maxchar		Not yet used
 *	\return		int			Number of lines
 */
function dol_nboflines($s,$maxchar=0)
{
	if ($s == '') return 0;
	$arraystring=split("\n",$s);
	$nb=sizeof($arraystring);

	return $nb;
}


/**
 *	\brief     	Return nb of lines of a formated text with \n and <br>
 *	\param	   	texte      		Text
 *	\param	   	maxlinesize  	Largeur de ligne en caracteres (ou 0 si pas de limite - defaut)
 * 	\param		charset			Give the charset used to encode the $texte variable in memory.
 *	\return    	int				Number of lines
 */
function dol_nboflines_bis($texte,$maxlinesize=0,$charset='UTF-8')
{
	//print $texte;
	$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
	$texte = strtr($texte, $repTable);
	if ($charset == 'UTF-8') { $pattern = '/(<[^>]+>)/Uu'; }	// /U is to have UNGREEDY regex to limit to one html tag. /u is for UTF8 support
	else $pattern = '/(<[^>]+>)/U';								// /U is to have UNGREEDY regex to limit to one html tag.
	$a = preg_split($pattern, $texte, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$nblines = floor((count($a)+1)/2);
	// count possible auto line breaks
	if($maxlinesize)
	{
		foreach ($a as $line)
		{
			if (strlen($line)>$maxlinesize)
			{
				//$line_dec = html_entity_decode(strip_tags($line));
				$line_dec = html_entity_decode($line);
				if(strlen($line_dec)>$maxlinesize)
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
 *	\brief		Fonction simple identique a microtime de PHP 5 mais compatible PHP 4
 *	\return		float		Time en millisecondes avec decimal pour microsecondes
 */
function dol_microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

/*
 *		\brief		Return if a text is a html content
 *		\param		msg			Content to check
 *		\param		option		0=Full detection, 1=Fast check
 *		\return		boolean		true/false
 */
function dol_textishtml($msg,$option=0)
{
	if ($option == 1)
	{
		if (eregi('<html',$msg))     return true;
		elseif (eregi('<body',$msg)) return true;
		elseif (eregi('<br',$msg))   return true;
		return false;
	}
	else
	{
		if (eregi('<html',$msg))                 return true;
		elseif (eregi('<body',$msg))             return true;
		elseif (eregi('<br',$msg))               return true;
		elseif (eregi('<span',$msg))             return true;
		elseif (eregi('<div',$msg))              return true;
		elseif (eregi('<table',$msg))            return true;
		elseif (eregi('<font',$msg))             return true;
		elseif (eregi('<strong',$msg))           return true;
		elseif (eregi('<img',$msg))              return true;
		elseif (eregi('<i>',$msg))               return true;
		elseif (eregi('<b>',$msg))               return true;
		elseif (eregi('&[A-Z0-9]{1,6};',$msg))   return true;
		return false;
	}
}

/**
 *    	\brief      Add substitution required by external modules then make substitutions in array substitutionarray
 *    	\param      chaine      			Source string in which we must do substitution
 *    	\param      substitutionarray		Array substitution old value => new value value
 * 		\param		outputlangs				If we want to add more substitution, we provide a language
 * 		\param		object					If we want to add more substitution, we provide a source object
 *    	\return     string      			Output string after subsitutions
 */
function make_substitutions($chaine,$substitutionarray,$outputlangs='',$object='')
{
	global $conf,$user;

	// Check if there is external substitution to do asked by plugins
	// We look files into the includes/modules/substitutions directory
	// By default, there is no such external plugins.
	foreach ($conf->file->dol_document_root as $dirroot)
	{
		$dir=$dirroot."/includes/modules/substitutions";
		$fonc='numberwords';	// For the moment only one file scan
		if (file_exists($dir.'/functions_'.$fonc.'.lib.php'))
		{
			dol_syslog("Library functions_".$fonc.".lib.php found into ".$dir);
			require_once($dir."/functions_".$fonc.".lib.php");
			numberwords_completesubstitutionarray($substitutionarray,$outputlangs,$object);
			break;
		}
	}

	// Make substitition
	foreach ($substitutionarray as $key => $value)
	{
		$chaine=ereg_replace("$key","$value",$chaine);	// We must keep the " to work when value is 123.5 for example
	}
	return $chaine;
}


/**
 *    \brief      	Format output for start and end date
 *    \param      	date_start    Start date
 *    \param      	date_end      End date
 *    \param      	format        Output format
 *    \remarks   	Updated by Matelli : added format paramter
 *    \remarks   	See http://matelli.fr/showcases/patchs-dolibarr/update-date-range-format.html for details
 */
function print_date_range($date_start,$date_end,$format = '',$outputlangs='')
{
	global $langs;

	if (! is_object($outputlangs)) $outputlangs=$langs;

	if ($date_start && $date_end)
	{
		print ' ('.$outputlangs->trans('DateFromTo',dol_print_date($date_start, $format, false, $outputlangs),dol_print_date($date_end, $format, false, $outputlangs)).')';
	}
	if ($date_start && ! $date_end)
	{
		print ' ('.$outputlangs->trans('DateFrom',dol_print_date($date_start, $format, false, $outputlangs)).')';
	}
	if (! $date_start && $date_end)
	{
		print ' ('.$outputlangs->trans('DateUntil',dol_print_date($date_end, $format, false, $outputlangs)).')';
	}
}



/**
 *	\brief   Retourne un tableau des mois ou le mois selectionne
 *	\param   selected			Mois a selectionner ou -1
 *	\return  string or array	Month string or array if selected < 0
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
 *	\brief  	Returns formated reduction
 *	\param		reduction		Reduction percentage
 *	\return		int				Return number of error messages shown
 */
function dol_htmloutput_errors($mesgstring='',$mesgarray='')
{
	global $langs;

	$ret = 0;
	$langs->load("errors");

	if (is_array($mesgarray) && sizeof($mesgarray))
	{
		print '<div class="error">';
		foreach($mesgarray as $message)
		{
			$ret++;
			print $langs->trans($message)."<br>\n";
		}
		print '</div>';
	}
	if ($mesgstring)
	{
		$ret++;
		print '<div class="error">';
		print $mesgstring;
		print '</div>';
	}

	return $ret;
}


/**
 *	\brief		This function output memory used by PHP and exit everything. Used for debugging purpose.
 */
function stopwithmem()
{
	print memory_get_usage();
	llxFooter();
	exit;
}


/**
 * 	\brief	Advanced sort array by second index function, which produces
 *			ascending (default) or descending output and uses optionally
 *			natural case insensitive sorting (which can be optionally case
 *			sensitive as well).
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
 *      \brief      Check if a string is in UTF8
 *      \param      $Str        String to check
 * 		\return		boolean		True if string is UTF8 or ISO compatible with UTF8, False if not (ISO with special char or Binary)
 */
function utf8_check($Str)
{
	for ($i=0; $i<strlen($Str); $i++)
	{
		if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
		elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
		elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
		elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
		elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
		elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
		else return false; # Does not match any model
		for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
			if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
			return false;
		}
	}
	return true;
}

?>