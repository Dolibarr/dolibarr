<?php
/* Copyright (C) 2000-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *	\brief			Ensemble de fonctions de base de dolibarr sous forme d'include
 *	\version		$Id$
 */

// For compatibility during upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	 define('DOL_DOCUMENT_ROOT', '..');
if (! defined('ADODB_DATE_VERSION')) include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");



/**
 \brief      Renvoi vrai si l'email est syntaxiquement valide
 \param	    address     adresse email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
 \return     boolean     true si email valide, false sinon
 */
function ValidEmail($address)
{
	if (ereg( ".*<(.+)>", $address, $regs)) {
		$address = $regs[1];
	}
	if (ereg( "^[^@  ]+@([a-zA-Z0-9\-]+\.)+([a-zA-Z0-9\-]{2}|coop|aero|biz|com|edu|gov|info|int|mil|name|net|org)\$",$address))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 \brief      Renvoi vrai si l'email a un nom de domaine qui rï¿½soud via dns
 \param	    mail        adresse email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
 \return     boolean     true si email valide, false sinon
 */
function check_mail ($mail)
{
	list($user, $domain) = split("@", $mail, 2);
	if (checkdnsrr($domain, "MX"))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 \brief          Nettoie chaine de caractere iso des accents
 \param          str             Chaine a nettoyer
 \return         string          Chaine nettoyee
 */
function unaccent_isostring($str)
{
	$translation = array(
	  	"\xE0" => "a",
	  	"\xE1" => "a",
	  	"\xE2" => "a",
	  	"\xE8" => "e",
	  	"\xE9" => "e",
	  	"\xEA" => "e",
	  	"\xEB" => "e",
	  	"\xEE" => "i",
	  	"\xEF" => "i",
	  	"\xF4" => "o",
	  	"\xF6" => "o",
	  	"\xFB" => "u",
	  	"\xFC" => "u"
	  	);

	  	return str_replace(array_keys($translation), array_values($translation), $str);
}

/**
 *	\brief          Nettoie chaine de caractere de caracteres speciaux
 *	\remarks		Fonction appelee par exemple pour definir un nom de fichier depuis un identifiant chaine libre
 *	\param          str             String to clean
 * 	\param			newstr			String to replace bad chars by
 *	\return         string          String cleaned (a-zA-Z_)
 */
function sanitize_string($str,$newstr='_')
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
 \brief      Envoi des messages dolibarr dans un fichier ou dans syslog
 Pour fichier:   fichier defini par SYSLOG_FILE
 Pour syslog:    facility defini par SYSLOG_FACILITY
 \param      message		    Message a tracer. Ne doit pas etre traduit si level = LOG_ERR
 \param      level           Niveau de l'erreur
 \remarks	Cette fonction n'a un effet que si le module syslog est activï¿½.
 Warning, les fonctions syslog sont bugguï¿½s sous Windows et gï¿½nï¿½rent des
 fautes de protection mï¿½moire. Pour rï¿½soudre, utiliser le loggage fichier,
 au lieu du loggage syslog (configuration du module).
 Si SYSLOG_FILE_NO_ERROR dï¿½fini, on ne gï¿½re pas erreur ecriture log
 \remarks	On Windows LOG_ERR=4, LOG_WARNING=5, LOG_NOTICE=LOG_INFO=LOG_DEBUG=6
 On Linux   LOG_ERR=3, LOG_WARNING=4, LOG_INFO=6, LOG_DEBUG=7
 */
function dolibarr_syslog($message, $level=LOG_INFO)
{
	global $conf,$user,$langs;

	if (isset($conf->syslog->enabled) && $conf->syslog->enabled)
	{
		//print $level.' - '.$conf->global->SYSLOG_LEVEL.' - '.$conf->syslog->enabled." \n";
		if ($level > $conf->global->SYSLOG_LEVEL) return;

		// Traduction du message
		if ($level == LOG_ERR)
		{
			$langs->load("errors");
			if ($message != $langs->trans($message)) $message = $langs->trans($message);
		}

		// Ajout user a la log
		$login='???';
		if (is_object($user) && $user->id) $login=$user->login;
		$message=sprintf("%-8s",$login)." ".$message;

		if (defined("SYSLOG_FILE") && SYSLOG_FILE)
		{
			$filelog=SYSLOG_FILE;
			$filelog=eregi_replace('DOL_DATA_ROOT',DOL_DATA_ROOT,$filelog);
			if (defined("SYSLOG_FILE_NO_ERROR")) $file=@fopen($filelog,"a+");
			else $file=fopen($filelog,"a+");
			if ($file)
			{
				$ip='unknown_ip';
				if (! empty($_SERVER["REMOTE_ADDR"])) $ip=$_SERVER["REMOTE_ADDR"];

				$liblevelarray=array(LOG_ERR=>'ERROR',LOG_WARNING=>'WARN',LOG_INFO=>'INFO',LOG_DEBUG=>'DEBUG');
				$liblevel=$liblevelarray[$level];
				if (! $liblevel) $liblevel='UNDEF';

				$message=strftime("%Y-%m-%d %H:%M:%S",time())." ".sprintf("%-5s",$liblevel)." ".$ip." ".$message;

				fwrite($file,$message."\n");
				fclose($file);

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
				$langs->load("main");
				print $langs->trans("ErrorFailedToOpenFile",$filelog);
			}
		}
		else
		{
			//define_syslog_variables(); dï¿½ja dï¿½finit dans master.inc.php
			if (defined("MAIN_SYSLOG_FACILITY") && MAIN_SYSLOG_FACILITY)
			{
				$facility = MAIN_SYSLOG_FACILITY;
			}
			elseif (defined("SYSLOG_FACILITY") && SYSLOG_FACILITY && defined(SYSLOG_FACILITY))
			{
				// Exemple: SYSLOG_FACILITY vaut LOG_USER qui vaut 8. On a besoin de 8 dans $facility.
				$facility = constant(SYSLOG_FACILITY);
			}
			else
			{
				$facility = LOG_USER;
			}

			openlog("dolibarr", LOG_PID | LOG_PERROR, $facility);

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

/**
 \brief      Affiche le header d'une fiche
 \param	    links		Tableau de titre d'onglets
 \param	    active      0=onglet non actif, 1=onglet actif
 \param      title       Titre tabelau ("" par defaut)
 \param      notab		0=Add tab header, 1=no tab header
 */
function dolibarr_fiche_head($links, $active='0', $title='', $notab=0)
{
	print "\n".'<div class="tabs">'."\n";

	// Affichage titre
	if ($title)
	{
		$limittitle=30;
		print '<a class="tabTitle">';
		print
		((!defined('MAIN_USE_SHORT_TITLE')) || (defined('MAIN_USE_SHORT_TITLE') &&  MAIN_USE_SHORT_TITLE))
		? dolibarr_trunc($title,$limittitle)
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
 \brief      Sauvegarde parametrage personnel
 \param	    db          Handler d'accï¿½s base
 \param	    user        Objet utilisateur
 \param	    url         Si defini, on sauve parametre du tableau tab dont clï¿½ = (url avec sortfield, sortorder, begin et page)
 Si non defini on sauve tous parametres du tableau tab
 \param	    tab         Tableau (clï¿½=>valeur) des paramï¿½tres a sauvegarder
 \return     int         <0 si ko, >0 si ok
 */
function dolibarr_set_user_page_param($db, &$user, $url='', $tab)
{
	// Verification parametres
	if (sizeof($tab) < 1) return -1;

	$db->begin();

	// On efface anciens paramï¿½tres pour toutes les clï¿½ dans $tab
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
	$sql.= " WHERE fk_user = ".$user->id;
	if ($url) $sql.=" AND page='".$url."'";
	else $sql.=" AND page=''";	// Page ne peut etre null
	$sql.= " AND param in (";
	$i=0;
	foreach ($tab as $key => $value)
	{
		if ($i > 0) $sql.=',';
		$sql.="'".$key."'";
		$i++;
	}
	$sql.= ")";
	dolibarr_syslog("functions.lib.php::dolibarr_set_user_page_param $sql");

	$resql=$db->query($sql);
	if (! $resql)
	{
		dolibarr_print_error($db);
		$db->rollback();
		exit;
	}

	foreach ($tab as $key => $value)
	{
		// On positionne nouveaux paramï¿½tres
		if ($value && (! $url || in_array($key,array('sortfield','sortorder','begin','page'))))
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,page,param,value)";
			$sql.= " VALUES (".$user->id.",";
			if ($url) $sql.= " '".urlencode($url)."',";
			else $sql.= " '',";
			$sql.= " '".$key."','".addslashes($value)."');";
			dolibarr_syslog("functions.lib.php::dolibarr_set_user_page_param $sql");

			$result=$db->query($sql);
			if (! $result)
			{
				dolibarr_print_error($db);
				$db->rollback();
				exit;
			}

			$user->page_param[$key] = $value;
		}
	}

	$db->commit();
	return 1;
}


/**
 \brief  Formattage des nombres
 \param	ca			valeur a formater
 \return	int			valeur formatï¿½e
 */
function dolibarr_print_ca($ca)
{
	global $langs,$conf;

	if ($ca > 1000)
	{
		$cat = round(($ca / 1000),2);
		$cat = "$cat K".$langs->trans("Currency".$conf->monnaie);
	}
	else
	{
		$cat = round($ca,2);
		$cat = "$cat ".$langs->trans("Currency".$conf->monnaie);
	}

	if ($ca > 1000000)
	{
		$cat = round(($ca / 1000000),2);
		$cat = "$cat M".$langs->trans("Currency".$conf->monnaie);
	}

	return $cat;
}


/**
 \brief      Effectue un dï¿½calage de date par rapport a une durï¿½e
 \param	    time                Date timestamp ou au format YYYY-MM-DD
 \param	    duration_value      Valeur de la durï¿½e a ajouter
 \param	    duration_unit       Unitï¿½ de la durï¿½e a ajouter (d, m, y)
 \return     int                 Nouveau timestamp
 */
function dolibarr_time_plus_duree($time,$duration_value,$duration_unit)
{
	if ($duration_value == 0) return $time;
	if ($duration_value > 0) $deltastring="+".abs($duration_value);
	if ($duration_value < 0) $deltastring="-".abs($duration_value);
	if ($duration_unit == 'd') { $deltastring.=" day"; }
	if ($duration_unit == 'm') { $deltastring.=" month"; }
	if ($duration_unit == 'y') { $deltastring.=" year"; }
	return strtotime($deltastring,$time);
}


/**
 *	\brief      Formattage de la date en fonction de la langue $conf->langage
 *	\param	    time        Date 'timestamp' ou format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
 *	\param	    format      Format d'affichage de la date
 *							"%d %b %Y",
 *							"%d/%m/%Y %H:%M",
 *							"%d/%m/%Y %H:%M:%S",
 *							"day", "daytext", "dayhour", "dayhourldap", "dayhourtext"
 *	\return     string      Date formatee ou '' si time null
 */
function dolibarr_print_date($time,$format='',$to_gmt=false)
{
	global $conf;

	// Si format non defini, on prend $conf->format_date_text_short sinon %Y-%m-%d %H:%M:%S
	if (! $format) $format=(isset($conf->format_date_text_short) ? $conf->format_date_text_short : '%Y-%m-%d %H:%M:%S');

	if ($format == 'day')          $format=$conf->format_date_short;
	if ($format == 'hour')         $format=$conf->format_hour_short;
	if ($format == 'daytext')      $format=$conf->format_date_text_short;
	if ($format == 'dayhour')      $format=$conf->format_date_hour_short;
	if ($format == 'dayhourtext')  $format=$conf->format_date_hour_text_short;
	if ($format == 'dayhourldap')  $format='%Y%m%d%H%M%SZ';
	if ($format == 'dayhourxcard') $format='%Y%m%dT%H%M%SZ';

	// Si date non definie, on renvoie ''
	if ($time == '') return '';		// $time=0 permis car signifie 01/01/1970 00:00:00

	// Analyse de la date
	if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?',$time,$reg))
	{
		// Date est au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
		$syear = $reg[1];
		$smonth = $reg[2];
		$sday = $reg[3];
		$shour = $reg[4];
		$smin = $reg[5];
		$ssec = $reg[6];

		return adodb_strftime($format,dolibarr_mktime($shour,$smin,$ssec,$smonth,$sday,$syear),$to_gmt);
	}
	else
	{
		// Date est un timestamps
		return adodb_strftime($format,$time,$to_gmt);
	}
}


/**
 *	\brief  	Convert a string date into a TMS date
 *	\param		string			Date in a string
 *				YYYYMMDD
 *				YYYYMMDDHHMMSS
 *				DD/MM/YY ou DD/MM/YYYY
 *				DD/MM/YY HH:MM:SS ou DD/MM/YYYY HH:MM:SS
 *	\return		date			Date
 */
function dolibarr_stringtotime($string)
{
	if (eregi('^([0-9]+)\/([0-9]+)\/([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?',$string,$reg))
	{
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
	$date=dolibarr_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4));
	return $date;
}


/**
 \brief  	Return an array with date info
 \param		timestamp		Timestamp
 \param		fast			Fast mode
 \return		array			Array of informations
 If no fast mode:
 'seconds' => $secs,
 'minutes' => $min,
 'hours' => $hour,
 'mday' => $day,
 'wday' => $dow,
 'mon' => $month,
 'year' => $year,
 'yday' => floor($secsInYear/$_day_power),
 'weekday' => gmdate('l',$_day_power*(3+$dow)),
 'month' => gmdate('F',mktime(0,0,0,$month,2,1971)),
 0 => $origd
 If fast mode:
 'seconds' => $secs,
 'minutes' => $min,
 'hours' => $hour,
 'mday' => $day,
 'mon' => $month,
 'year' => $year,
 'yday' => floor($secsInYear/$_day_power),
 'leap' => $leaf,
 'ndays' => $ndays
 \remarks	PHP getdate is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 */
function dolibarr_getdate($timestamp,$fast=false)
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
 \brief  	Retourne une date fabriquee depuis infos.
 Remplace la fonction mktime non implementee sous Windows si annee < 1970
 \param		hour			Hour
 \param		minute			Minute
 \param		second			Second
 \param		month			Month
 \param		day				Day
 \param		year			Year
 \param		gm				Time gm
 \param		check			No check on parameters (Can use day 32, etc...)
 \return	timestamp		Date en timestamp, '' if error
 \remarks	PHP mktime is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 */
function dolibarr_mktime($hour,$minute,$second,$month,$day,$year,$gm=0,$check=1)
{
	//print "- ".$hour.",".$minute.",".$second.",".$month.",".$day.",".$year.",".$_SERVER["WINDIR"]." -";

	// Check parameters
	if ($check)
	{
		if (! $month || ! $day)  return '';
		if ($day   > 31) return '';
		if ($month > 12) return '';
		if ($min  < 0 || $min  > 60) return '';
		if ($hour < 0 || $hour > 24) return '';
		if ($min  < 0 || $min  > 60) return '';
	}

	$usealternatemethod=false;
	if ($year <= 1970) $usealternatemethod=true;		// <= 1970
	if ($year >= 2038) $usealternatemethod=true;		// >= 2038

	if ($usealternatemethod || $gm)	// Si time gm, seule adodb peut convertir
	{
		/*
		 // On peut utiliser strtotime pour obtenir la traduction.
		 // strtotime is ok for range: Vendredi 13 Dï¿½cembre 1901 20:45:54 GMT au Mardi 19 Janvier 2038 03:14:07 GMT.
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



/**
 \brief  	Returns formated date
 \param		fmt				Format (Exemple: 'Y-m-d H:i:s')
 \param		timestamp		Date. Exemple: Si timestamp=0 et gm=1, renvoi 01/01/1970 00:00:00
 \param		gm				1 if timestamp was built with gmmktime, 0 if timestamp was build with mktime
 \return		string			Formated date
 */
function dolibarr_date($fmt, $timestamp, $gm=0)
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
 \brief  Affiche les informations d'un objet
 \param	object			objet a afficher
 */
function dolibarr_print_object_info($object)
{
	global $langs;
	$langs->load("other");

	if (isset($object->user_creation) && $object->user_creation->fullname)
	print $langs->trans("CreatedBy")." : " . $object->user_creation->fullname . '<br>';

	if (isset($object->date_creation))
	print $langs->trans("DateCreation")." : " . dolibarr_print_date($object->date_creation,"dayhourtext") . '<br>';

	if (isset($object->user_modification) && $object->user_modification->fullname)
	print $langs->trans("ModifiedBy")." : " . $object->user_modification->fullname . '<br>';

	if (isset($object->date_modification))
	print $langs->trans("DateLastModification")." : " . dolibarr_print_date($object->date_modification,"dayhourtext") . '<br>';

	if (isset($object->user_validation) && $object->user_validation->fullname)
	print $langs->trans("ValidatedBy")." : " . $object->user_validation->fullname . '<br>';

	if (isset($object->date_validation))
	print $langs->trans("DateValidation")." : " . dolibarr_print_date($object->date_validation,"dayhourtext") . '<br>';

	if (isset($object->user_cloture) && $object->user_cloture->fullname )
	print $langs->trans("ClosedBy")." : " . $object->user_cloture->fullname . '<br>';

	if (isset($object->date_cloture))
	print $langs->trans("DateClosing")." : " . dolibarr_print_date($object->date_cloture,"dayhourtext") . '<br>';

	if (isset($object->user_rappro) && $object->user_rappro->fullname )
	print $langs->trans("ConciliatedBy")." : " . $object->user_rappro->fullname . '<br>';

	if (isset($object->date_rappro))
	print $langs->trans("DateConciliating")." : " . dolibarr_print_date($object->date_rappro,"dayhourtext") . '<br>';
}

/**
 \brief      Formatage des numï¿½ros de telephone en fonction du format d'un pays
 \param	    phone			Numï¿½ro de telephone a formater
 \param	    country			Pays selon lequel formatter
 \return     string			Numï¿½ro de tï¿½lï¿½phone formatï¿½
 */
function dolibarr_print_phone($phone,$country="FR")
{
	$phone=trim($phone);
	if (! $phone) { return $phone; }

	if (strtoupper($country) == "FR")
	{
		// France
		if (strlen($phone) == 10) {
			return substr($phone,0,2)."&nbsp;".substr($phone,2,2)."&nbsp;".substr($phone,4,2)."&nbsp;".substr($phone,6,2)."&nbsp;".substr($phone,8,2);
		}
		elseif (strlen($phone) == 7)
		{

			return substr($phone,0,3)."&nbsp;".substr($phone,3,2)."&nbsp;".substr($phone,5,2);
		}
		elseif (strlen($phone) == 9)
		{
			return substr($phone,0,2)."&nbsp;".substr($phone,2,3)."&nbsp;".substr($phone,5,2)."&nbsp;".substr($phone,7,2);
		}
		elseif (strlen($phone) == 11)
		{
			return substr($phone,0,3)."&nbsp;".substr($phone,3,2)."&nbsp;".substr($phone,5,2)."&nbsp;".substr($phone,7,2)."&nbsp;".substr($phone,9,2);
		}
		elseif (strlen($phone) == 12)
		{
			return substr($phone,0,4)."&nbsp;".substr($phone,4,2)."&nbsp;".substr($phone,6,2)."&nbsp;".substr($phone,8,2)."&nbsp;".substr($phone,10,2);
		}
	}

	return $phone;
}


/**
 * \brief		Return string with formated size
 * \param		size		Size to print
 * \return		string		Link
 */
function dol_print_size($size)
{
	global $langs;

	return $size.' '.$langs->trans("Bytes");
}


/**
 * \brief		Show click to dial link
 * \param		phone		Phone to call
 * \param		option		Type of picto
 * \return		string		Link
 */
function dol_phone_link($phone,$option=0)
{
	global $conf,$user;

	$link='';
	//if (! empty($conf->global->CLICKTODIAL_URL))
	if ($conf->clicktodial->enabled)
	{
		$phone=trim($phone);
		$url = $conf->global->CLICKTODIAL_URL;
		$url.= "?login=".urlencode($user->clicktodial_login)."&password=".urlencode($user->clicktodial_password);
		$url.= "&caller=".urlencode($user->clicktodial_poste)."&called=".urlencode(trim($phone));
		$link.='<a href="URL_DEFINED_IN_CLICKTODIAL_MODULE" onclick="newpopup(\''.$url.'\',\'\'); return false;">'.img_phone("default",0).'</a>';
	}
	return $link;
}

/**
 *	\brief      Truncate a string to a particular length adding '...' if string larger than length
 *	\param      string				String to truncate
 *	\param      size				Max string size. 0 for no limit.
 *	\param		trunc				Where to trunc: right, left, middle
 *	\return     string				Truncated string
 *	\remarks	USE_SHORT_TITLE=0 can disable all truncings
 */
function dolibarr_trunc($string,$size=40,$trunc='right')
{
	if ($size==0) return $string;
	if (! defined('USE_SHORT_TITLE') || (defined('USE_SHORT_TITLE') && USE_SHORT_TITLE))
	{
		// We go always here
		if ($trunc == 'right')
		{
			if (strlen($string) > $size)
			return substr($string,0,$size).'...';
			else
			return $string;
		}
		if ($trunc == 'middle')
		{
			if (strlen($string) > 2 && strlen($string) > $size)
			{
				$size1=round($size/2);
				$size2=round($size/2);
				return substr($string,0,$size1).'...'.substr($string,strlen($string) - $size2,$size2);
			}
			else
			return $string;
		}
		if ($trunc == 'left')
		{
			if (strlen($string) > $size)
			return '...'.substr($string,strlen($string) - $size,$size);
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
 \brief      Complï¿½te une chaine a une taille donnï¿½e par des espaces
 \param      string		Chaine a complï¿½ter
 \param      size		Longueur de la chaine.
 \param      side		0=Complï¿½tion a droite, 1=Complï¿½tion a gauche
 \param		char		Chaine de complï¿½tion
 \return     string		Chaine complï¿½tï¿½e
 */
function dolibarr_pad($string,$size,$side,$char=' ')
{
	$taille=sizeof($string);
	$i=0;
	while($i < ($size - $taille))
	{
		if ($side > 0) $string.=$char;
		else $string=$char.$string;
		$i++;
	}
	return $string;
}

/**
 \brief      Affiche picto propre a une notion/module (fonction gï¿½nï¿½rique)
 \param      alt         Texte sur le alt de l'image
 \param      object      Objet pour lequel il faut afficher le logo (exemple: user, group, action, bill, contract, propal, product, ...)
 \return     string      Retourne tag img
 */
function img_object($alt, $object)
{
	global $conf,$langs;
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_'.$object.'.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche picto (fonction gï¿½nï¿½rique)
 \param      alt         		Texte sur le alt de l'image
 \param      picto       		Nom de l'image a afficher (Si pas d'extension, on met '.png')
 \param		options				Attribut supplï¿½mentaire a la balise img
 \param		pictoisfullpath		If 1, image path is a full path
 \return     string      		Retourne tag img
 */
function img_picto($alt, $picto, $options='', $pictoisfullpath=0)
{
	global $conf;
	if (! eregi('(\.png|\.gif)$',$picto)) $picto.='.png';
	if ($pictoisfullpath) return '<img src="'.$picto.'" border="0" alt="'.$alt.'" title="'.$alt.'"'.($options?' '.$options:'').'>';
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/'.$picto.'" border="0" alt="'.$alt.'" title="'.$alt.'"'.($options?' '.$options:'').'>';
}

/**
 \brief      Affiche logo action
 \param      alt         Texte sur le alt de l'image
 \param      numaction   Determine image action
 \return     string      Retourne tag img
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
	return '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm'.$numaction.'.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 \brief      Affiche logo fichier
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_file($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Show");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/file.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo refresh
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_refresh($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Refresh");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo dossier
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_folder($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Dossier");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/folder.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo nouveau fichier
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_file_new($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Show");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
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
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf'.$size.'.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo vcard
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_vcard($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("VCard");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/vcard.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo +
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_edit_add($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Add");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_add.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}
/**
 \brief      Affiche logo -
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_edit_remove($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Remove");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_remove.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo editer/modifier fiche
 \param      alt         Texte sur le alt de l'image
 \param      float       Si il faut y mettre le style "float: right"
 \return     string      Retourne tag img
 */
function img_edit($alt = "default", $float=0, $other='')
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Modify");
	$img='<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" alt="'.$alt.'" title="'.$alt.'"';
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
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo dï¿½sactiver
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_disable($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Disable");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/disable.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 \brief      Affiche logo help avec curseur "?"
 \return     string      Retourne tag img
 */
function img_help($usehelpcursor=1,$usealttitle=1)
{
	global $conf,$langs;
	$s ='<img ';
	if ($usehelpcursor) $s.='style="cursor: help;" ';
	$s.='src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" border="0"';
	if ($usealttitle) $s.=' alt="'.$langs->trans("Info").'" title="'.$langs->trans("Info").'"';
	$s.='>';
	return $s;
}

/**
 \brief      Affiche picto calendrier "?"
 \return     string      Retourne tag img
 */
function img_cal()
{
	global $conf,$langs;
	return '<img style="vertical-align:middle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/calendar.png" border="0" alt="" title="">';
}

/**
 \brief      Affiche logo info
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_info($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Informations");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo calculatrice
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_calc($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Calculate");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/calc.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo warning
 \param      alt         Texte sur le alt de l'image
 \param      float       Si il faut afficher le style "float: right"
 \return     string      Retourne tag img
 */
function img_warning($alt = "default",$float=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Warning");
	$img='<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/warning.png" border="0" alt="'.$alt.'" title="'.$alt.'"';
	if ($float) $img.=' style="float: right"';
	$img.='>';

	return $img;
}

/**
 \brief      Affiche logo warning
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_error($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Error");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/error.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo alerte
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_alerte($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Alert");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/alerte.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo tï¿½lï¿½phone
 \param      alt         Texte sur le alt de l'image
 \param		option		Choose of logo
 \return     string      Retourne tag img
 */
function img_phone($alt = "default",$option=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Call");
	$img='call_out';
	if ($option == 1) $img='call';
	$img='object_commercial';
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/'.$img.'.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 \brief      Affiche logo suivant
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_next($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") {
		$alt=$langs->trans("Next");
	}
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/next.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo prï¿½cï¿½dent
 \param      alt     Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_previous($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Previous");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/previous.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo bas
 \param      alt         Texte sur le alt de l'image
 \param      selected    Affiche version "selected" du logo
 \return     string      Retourne tag img
 */
function img_down($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Down");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow_selected.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo haut
 \param      alt         Texte sur le alt de l'image
 \param      selected    Affiche version "selected" du logo
 \return     string      Retourne tag img
 */
function img_up($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Up");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow_selected.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo gauche
 \param      alt         Texte sur le alt de l'image
 \param      selected    Affiche version "selected" du logo
 \return     string      Retourne tag img
 */
function img_left($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Left");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow_selected.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo droite
 \param      alt         Texte sur le alt de l'image
 \param      selected    Affiche version "selected" du logo
 \return     string      Retourne tag img
 */
function img_right($alt = "default", $selected=0)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Right");
	if ($selected) return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow_selected.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
	else return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche logo tick
 \param      alt         Texte sur le alt de l'image
 \return     string      Retourne tag img
 */
function img_tick($alt = "default")
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Active");
	return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 \brief      Affiche le logo tick si allow
 \param      allow       Authorise ou non
 \return     string      Retourne tag img
 */
function img_allow($allow)
{
	global $conf,$langs;
	if ($alt=="default") $alt=$langs->trans("Active");

	if ($allow == 1)
	{
		return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
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
	if (eregi('\.pdf',$file))        { $mime='pdf'; }
	if (eregi('\.(html|htm)',$file)) { $mime='html'; }
	if (eregi('\.txt',$file))        { $mime='other'; }
	if (eregi('\.php',$file))        { $mime='php'; }
	if (eregi('\.pl',$file))         { $mime='pl'; }
	if (eregi('\.js',$file))         { $mime='jscript'; }
	if (eregi('\.(png|bmp|jpg|jpeg|gif)',$file)) $mime='image';
	if (eregi('\.(mp3|ogg|au)',$file))           $mime='audio';
	if (eregi('\.(avi|mvw|divx|xvid)',$file))    $mime='video';
	if (eregi('\.(zip|rar|gz|tgz|z|cab|bz2)',$file))       $mime='archive';
	if (empty($alt)) $alt='Mime type: '.$mime;
	
	$mime.='.png';
	return '<img src="'.DOL_URL_ROOT.'/theme/common/mime/'.$mime.'" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
 \brief      Return if a filename is file name of a supported image format
 \param      file		Filename
 \return		int			-1=Not image filename, 0=Image filename but format not supported by PHP, 1=Image filename with format supported
 */
function image_format_supported($file)
{
	// Case filename is not a format image
	if (! eregi('(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$',$file,$reg)) return -1;

	// Case filename is a format image but not supported by this PHP
	$imgfonction='';
	if (strtolower($reg[1]) == '.gif')  $imgfonction = 'imagecreatefromgif';
	if (strtolower($reg[1]) == '.png')  $imgfonction = 'imagecreatefrompng';
	if (strtolower($reg[1]) == '.jpg')  $imgfonction = 'imagecreatefromjpeg';
	if (strtolower($reg[1]) == '.jpeg') $imgfonction = 'imagecreatefromjpeg';
	if (strtolower($reg[1]) == '.bmp')  $imgfonction = 'imagecreatefromwbmp';
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 0;
		}
	}

	// Filename is a format image and supported by this PHP
	return 1;
}

/**
 \brief      Affiche info admin
 \param      text			Text info
 \param      infoonimgalt	Info is shown on alt of star picto
 \return		string			String with info text
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
 \brief      Check permissions of a user to show a page and an object.
 \param      user      	  	User to check
 \param      feature		Feature to check (in most cases, it's module name)
 \param      objectid      	Object ID if we want to check permission on on object (optionnal)
 \param      dbtablename    Table name where object is stored. Not used if objectid is null (optionnel)
 \param      feature2		Feature to check (second level of permission)
 */
function restrictedArea($user, $feature='societe', $objectid=0, $dbtablename='',$feature2='')
{
	global $db;

	//print "$user->id, $feature, $objectid, $dbtablename, $list ".$user->rights->societe->contact->lire;

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
	else if (! empty($feature2))	// This should be used for future changes
	{
		if (! $user->rights->$feature->$feature2->read) $readok=0;
	}
	else if (! empty($feature))		// This is for old permissions
	{
		if (! $user->rights->$feature->lire) $readok=0;
	}
	if (! $readok) accessforbidden();
	//print "Read access is ok";

	// Check write permission from module
	$createok=1;
	if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
	{
		if ($feature == 'societe')
		{
			if (! $user->rights->societe->creer && ! $user->rights->fournisseur->creer) $createok=0;
		}
		else if ($feature == 'contact')
		{
			if (! $user->rights->societe->contact->creer) $createok=0;
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
			if (! $user->rights->$feature->$feature2->write) $createok=0;
		}
		else if (! empty($feature))		// This is for old permissions
		{
			if (! $user->rights->$feature->creer) $createok=0;
		}
		if (! $createok) accessforbidden();
		//print "Write access is ok";
	}

	// If we have a particular object to check permissions on
	if ($objectid)
	{
		$sql='';
		// Check permission for external users
		if ($user->societe_id > 0)
		{
			if ($feature == 'societe')
			{
				if ($user->societe_id <> $objectid) accessforbidden();
			}
			else
			{
				if (!$dbtablename) $dbtablename = $feature;	// Si dbtable non dï¿½fini, meme nom que le module
					
				$sql = "SELECT dbt.fk_soc";
				$sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql.= " WHERE dbt.rowid = ".$objectid;
				$sql.= " AND dbt.fk_soc = ".$user->societe_id;
			}
		}
		// Check permission for internal users that are restricted on their objects
		else if (! $user->rights->societe->client->voir)
		{
			if ($feature == 'societe')
			{
				$sql = "SELECT sc.fk_soc";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
				$sql.= " WHERE sc.fk_soc = ".$objectid." AND sc.fk_user = ".$user->id;
			}
			else
			{
				if (!$dbtablename) $dbtablename = $feature;	// Si dbtable non dï¿½fini, meme nom que le module

				$sql = "SELECT sc.fk_soc";
				$sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = dbt.fk_soc";
				$sql.= " WHERE dbt.rowid = ".$objectid;
				$sql.= " AND IFNULL(sc.fk_user, ".$user->id.") = ".$user->id;
			}
		}

		//print $sql;
		if ($sql)
		{
			$resql=$db->query($sql);
			if ($resql)
			{
				if ($db->num_rows($resql) == 0)	accessforbidden();
			}
			else
			{
				dolibarr_syslog("functions.lib.php::restrictedArea sql=".$sql, LOG_ERR);
				accessforbidden();
			}
		}
	}

	return 1;
}


/**
 \brief      Affiche message erreur de type acces interdit et arrete le programme
 \param		message			Force error message
 \param		printheader		Affiche avant le header
 \remarks    L'appel a cette fonction termine le code.
 */
function accessforbidden($message='',$printheader=1)
{
	global $user, $langs;
	$langs->load("other");

	if ($printheader && function_exists("llxHeader")) llxHeader();
	print '<div class="error">';
	if (! $message) print $langs->trans("ErrorForbidden");
	else print $message;
	print '</div>';
	print '<br>';
	if ($user->login)
	{
		print $langs->trans("CurrentLogin").': <font class="error">'.$user->login.'</font><br>';
		print $langs->trans("ErrorForbidden2",$langs->trans("Home"),$langs->trans("Users"));
	}
	elseif (! empty($_SERVER["REMOTE_USER"]))
	{
		print $langs->trans("CurrentLogin").': <font class="error">'.$_SERVER["REMOTE_USER"]."</font><br>";
		print $langs->trans("ErrorForbidden2",$langs->trans("Home"),$langs->trans("Users"));
	}
	else
	{
		print $langs->trans("ErrorForbidden3");
	}
	if (function_exists("llxFooter")) llxFooter();
	exit(0);
}


/**
 *	\brief      Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontï¿½e des bugs.
 *				On doit appeler cette fonction quand une erreur technique bloquante est rencontree.
 *				Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
 *				renvoyer leur erreur par l'intermediaire de leur propriete "error".
 *				\param      db      Database handler
 *				\param      error	Chaine erreur ou tableau de chaines erreur complementaires a afficher
 */
function dolibarr_print_error($db='',$error='')
{
	global $conf,$langs,$argv;
	$syslog = '';

	// Si erreur intervenue avant chargement langue
	if (! $langs)
	{
		require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
		$langs = new Translate("", $conf);
	}
	$langs->load("main");

	if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
	{
		print $langs->trans("DolibarrHasDetectedError").".<br>\n";
		print $langs->trans("InformationToHelpDiagnose").":<br><br>\n";

		print "<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";;
		if (isset($conf->global->MAIN_FEATURES_LEVEL)) print "<b>".$langs->trans("LevelOfFeature").":</b> ".$conf->global->MAIN_FEATURES_LEVEL."<br>\n";;
		print "<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";;
		print "<b>".$langs->trans("RequestedUrl").":</b> ".$_SERVER["REQUEST_URI"]."<br>\n";;
		print "<b>".$langs->trans("Referer").":</b> ".$_SERVER["HTTP_REFERER"]."<br>\n";;
		$syslog.="url=".$_SERVER["REQUEST_URI"];
		$syslog.=", query_string=".$_SERVER["QUERY_STRING"];
	}
	else                              // Mode CLI
	{
		print '> '.$langs->transnoentities("ErrorInternalErrorDetected").":\n".$argv[0]."\n";
		$syslog.="pid=".getmypid();
	}

	if (is_object($db))
	{
		if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
		{
			print "<br>\n";
			print "<b>".$langs->trans("DatabaseTypeManager").":</b> ".$db->type."<br>\n";
			print "<b>".$langs->trans("RequestLastAccessInError").":</b> ".($db->lastqueryerror()?$db->lastqueryerror():$langs->trans("ErrorNoRequestInError"))."<br>\n";
			print "<b>".$langs->trans("ReturnCodeLastAccessInError").":</b> ".($db->lasterrno()?$db->lasterrno():$langs->trans("ErrorNoRequestInError"))."<br>\n";
			print "<b>".$langs->trans("InformationLastAccessInError").":</b> ".($db->lasterror()?$db->lasterror():$langs->trans("ErrorNoRequestInError"))."<br>\n";
		}
		else                            // Mode CLI
		{
			print '> '.$langs->transnoentities("DatabaseTypeManager").":\n".$db->type."\n";
			print '> '.$langs->transnoentities("RequestLastAccessInError").":\n".($db->lastqueryerror()?$db->lastqueryerror():$langs->trans("ErrorNoRequestInError"))."\n";
			print '> '.$langs->transnoentities("ReturnCodeLastAccessInError").":\n".($db->lasterrno()?$db->lasterrno():$langs->trans("ErrorNoRequestInError"))."\n";
			print '> '.$langs->transnoentities("InformationLastAccessInError").":\n".($db->lasterror()?$db->lasterror():$langs->trans("ErrorNoRequestInError"))."\n";

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
				print "<b>".$langs->trans("Message").":</b> ".$msg."<br>\n" ;
			}
			else                            // Mode CLI
			{
				print '> '.$langs->transnoentities("Message").":\n".$msg."\n" ;
			}
			$syslog.=", msg=".$msg;
		}
	}

	dolibarr_syslog("Error ".$syslog, LOG_ERR);
}


/**
 *	\brief  Deplacer les fichiers telechargï¿½s, apres quelques controles divers
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
	// On interdit les remontï¿½es de repertoire ainsi que les pipes dans
	// les noms de fichiers.
	if (eregi('\.\.',$src_file) || eregi('[<>|]',$src_file))
	{
		dolibarr_syslog("Refused to deliver file ".$src_file);
		return -1;
	}

	// Security:
	// On interdit les remontï¿½es de repertoire ainsi que les pipe dans
	// les noms de fichiers.
	if (eregi('\.\.',$dest_file) || eregi('[<>|]',$dest_file))
	{
		dolibarr_syslog("Refused to deliver file ".$dest_file);
		return -1;
	}

	// Check if destination file already exists
	if (! $allowoverwrite)
	{
		if (file_exists($file_name))
		{
			dolibarr_syslog("Functions.lib::dol_move_uploaded_file File ".$file_name." already exists", LOG_WARNING);
			return -2;			
		}
	}
	
	// Move file
	$return=move_uploaded_file($src_file, $file_name);
	if ($return)
	{
		dolibarr_syslog("Functions.lib::dol_move_uploaded_file Success to move ".$src_file." to ".$file_name, LOG_DEBUG);
		return 1;			
	}
	else 
	{
		dolibarr_syslog("Functions.lib::dol_move_uploaded_file Failed to move ".$src_file." to ".$file_name, LOG_ERR);			
		return -3;
	}
}


/**
 \brief      Show title line of an array
 \param	    name        libelle champ
 \param	    file        url pour clic sur tri
 \param	    field       champ de tri
 \param	    begin       ("" par defaut)
 \param	    options     ("" par defaut)
 \param      td          options de l'attribut td ("" par defaut)
 \param      sortfield   nom du champ sur lequel est effectuï¿½ le tri du tableau
 \param      sortorder   ordre du tri
 */
function print_liste_field_titre($name, $file, $field, $begin="", $options="", $td="", $sortfield="", $sortorder="")
{
	global $conf;
	//print "$name, $file, $field, $begin, $options, $td, $sortfield, $sortorder<br>\n";

	// Le champ de tri est mis en ï¿½vidence.
	// Exemple si (sortfield,field)=("nom","xxx.nom") ou (sortfield,field)=("nom","nom")
	if ($sortfield == $field || $sortfield == ereg_replace("^[^\.]+\.","",$field))
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
		print '<img width="2" src="'.DOL_URL_ROOT.'/theme/common/transparent.png">';
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
 \brief  Affichage d'un titre
 \param	titre			Le titre a afficher
 */
function print_titre($titre)
{
	print '<div class="titre">'.$titre.'</div>';
}

/**
 \brief  Affichage d'un titre d'une fiche, alignï¿½ a gauche
 \param	titre				Le titre a afficher
 \param	mesg				Message suplï¿½mentaire a afficher a droite
 \param	picto				Picto pour ligne de titre
 \param	pictoisfullpath		1=Picto is a full absolute url of image
 */
function print_fiche_titre($titre, $mesg='', $picto='', $pictoisfullpath=0)
{
	print "\n";
	print '<table width="100%" border="0" class="notopnoleftnoright"><tr>';
	if ($picto) print '<td width="24" align="left" valign="middle">'.img_picto('',$picto, '', $pictoisfullpath).'</td>';
	print '<td class="notopnoleftnoright" valign="middle">';
	print '<div class="titre">'.$titre.'</div>';
	print '</td>';
	if (strlen($mesg))
	{
		print '<td align="right" valign="middle"><b>'.$mesg.'</b></td>';
	}
	print '</tr></table>'."\n";
}

/**
 \brief  Effacement d'un fichier
 \param	file			Fichier a effacer ou masque de fichier a effacer
 \param	boolean			true if file deleted, false if error
 */
function dol_delete_file($file)
{
	$ok=true;
	foreach (glob($file) as $filename)
	{
		$ok=unlink($filename);
		if ($ok) dolibarr_syslog("Removed file $filename",LOG_DEBUG);
		else dolibarr_syslog("Failed to remove file $filename",LOG_ERR);
	}
	return $ok;
}

/**
 \brief  	Effacement d'un rï¿½pertoire
 \param		file			Rï¿½pertoire a effacer
 */
function dol_delete_dir($dir)
{
	return rmdir($dir);
}

/**
 \brief  	Effacement d'un rï¿½pertoire $dir et de son arborescence
 \param		file			Rï¿½pertoire a effacer
 \param		count			Compteur pour comptage nb elements supprimï¿½s
 \return		int				Nombre de fichier+repï¿½rtoires supprimï¿½s
 */
function dol_delete_dir_recursive($dir,$count=0)
{
	if ($handle = opendir("$dir"))
	{
		while (false !== ($item = readdir($handle)))
		{
			if ($item != "." && $item != "..")
			{
				if (is_dir("$dir/$item"))
				{
					$count=dol_delete_dir_recursive("$dir/$item",$count);
				}
				else
				{
					unlink("$dir/$item");
					$count++;
					//echo " removing $dir/$item<br>\n";
				}
			}
		}
		closedir($handle);
		rmdir($dir);
		$count++;
		//echo "removing $dir<br>\n";
	}

	//echo "return=".$count;
	return $count;
}

/**
 \brief  Scan les fichiers avec un anti-virus
 \param	 file			Fichier a scanner
 \return	 malware	Nom du virus si infectï¿½ sinon retourne "null"
 */
function dol_avscan_file($file)
{
	$malware = '';

	// Clamav
	if (function_exists("cl_scanfile"))
	{
		$maxreclevel = 5 ; // maximal recursion level
		$maxfiles = 1000; // maximal number of files to be scanned within archive
		$maxratio = 200; // maximal compression ratio
		$archivememlim = 0; // limit memory usage for bzip2 (0/1)
		$maxfilesize = 10485760; // archived files larger than this value (in bytes) will not be scanned

		cl_setlimits($maxreclevel, $maxfiles, $maxratio, $archivememlim, $maxfilesize);
		$malware = cl_scanfile($file);
	}

	return $malware;
}

/**
 \brief  Fonction print_barre_liste
 \param	titre				Titre de la page
 \param	page				numï¿½ro de la page
 \param	file				lien
 \param	options         	parametres complementaires lien ('' par defaut)
 \param	sortfield       	champ de tri ('' par defaut)
 \param	sortorder       	ordre de tri ('' par defaut)
 \param	center          	chaine du centre ('' par defaut)
 \param	num					number of records found by select with limit+1
 \param	totalnboflines		Total number of records/lines for all pages (if known)
 */
function print_barre_liste($titre, $page, $file, $options='', $sortfield='', $sortorder='', $center='', $num=-1, $totalnboflines=0)
{
	global $conf,$langs;

	if ($num > $conf->liste_limit or $num == -1)
	{
		$nextpage = 1;
	}
	else
	{
		$nextpage = 0;
	}

	print '<table width="100%" border="0" class="notopnoleftnoright">';

	$pagelist = '';

	if ($page > 0 || $num > $conf->liste_limit)
	{
		if ($totalnboflines)
		{
			print '<tr><td class="notopnoleftnoright">';
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
			print '<tr><td class="notopnoleftnoright">';
			print '<div class="titre">'.$titre.'</div>';
			$pagelist.= $langs->trans('Page').' '.($page+1);
			print '</td>';
		}
	}
	else
	{
		print '<tr><td class="notopnoleftnoright"><div class="titre">'.$titre.'</div></td>';
	}

	if ($center)
	{
		print '<td align="left">'.$center.'</td>';
	}

	print '<td align="right">';

	if ($sortfield) $options .= "&amp;sortfield=".$sortfield;
	if ($sortorder) $options .= "&amp;sortorder=".$sortorder;

	// Affichage des fleches de navigation
	print_fleche_navigation($page,$file,$options,$nextpage,$pagelist);

	print '</td></tr></table>';
}

/**
 \brief  	Fonction servant a afficher les fleches de navigation dans les pages de listes
 \param	page				Numï¿½ro de la page
 \param	file				Lien
 \param	options         	Autres parametres d'url a propager dans les liens ("" par defaut)
 \param	nextpage	    	Faut-il une page suivante
 \param	betweenarraows		HTML Content to show between arrows
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
 *		\brief      Fonction qui retourne un taux de tva formatï¿½ pour visualisation
 *		\remarks    Fonction utilisï¿½e dans les pdf et les pages html
 *		\param	    rate			Rate value to format (19.6 19,6 19.6% 19,6%,...)
 *		\param		foundpercent	Add a percent % sign in output
 *		\param		info_bits		Miscellanous information on vat
 *		\return		string			Chaine avec montant formatï¿½ (19,6 ou 19,6% ou 8.5% *)
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

	// Si $outlangs non force, on prend langue utilisateur
	if (! is_object($outlangs)) $outlangs=$langs;

	if ($outlangs->trans("SeparatorDecimal") != "SeparatorDecimal")  $dec=$outlangs->trans("SeparatorDecimal");
	if ($outlangs->trans("SeparatorThousand")!= "SeparatorThousand") $thousand=$outlangs->trans("SeparatorThousand");
	//print "amount=".$amount." html=".$html." trunc=".$trunc." nbdecimal=".$nbdecimal." dec=".$dec." thousand=".$thousand;

	//print "amount=".$amount."-";
	$amount = ereg_replace(',','.',$amount);	// should be useless
	//print $amount."-";
	$datas = split('\.',$amount);
	$decpart = $datas[1];
	$decpart = eregi_replace('0+$','',$decpart);	// Supprime les 0 de fin de partie decimale
	//print "decpart=".$decpart."<br>";
	$end='';

	// On augmente nbdecimal au besoin si il y a plus de decimales que nbdecimal
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

	// Formate nombre
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
 *	\brief     		Fonction qui retourne un numerique conforme SQL, depuis un montant au
 *					format utilisateur.
 *	\remarks   		Fonction a appeler sur montants saisis avant un insert en base
 *	\param	    	amount		Montant a formater
 *	\param	    	rounding	'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
 *								'MT'=Round to Max for totals with Tax (MAIN_MAX_DECIMALS_TOT)
 *								'MS'=Round to Max Shown (MAIN_MAX_DECIMALS_SHOWN)
 *								''=No rounding
 *	\return			string		Montant au format numerique PHP et SQL (Exemple: '99.99999')
 *	\seealso		price		Fonction inverse de price2num
 */
function price2num($amount,$rounding='',$alreadysqlnb=-1)
{
	global $langs,$conf;

	// Round PHP function does not allow number like '1,234.5' nor '1.234,5' nor '1 234,5'
	// Numbers must be '1234.5'
	// Decimal delimiter for database SQL request must be '.'

	$dec=','; $thousand=' ';
	if ($langs->trans("SeparatorDecimal") != "SeparatorDecimal")  $dec=$langs->trans("SeparatorDecimal");
	if ($langs->trans("SeparatorThousand")!= "SeparatorThousand") $thousand=$langs->trans("SeparatorThousand");

	if ($alreadysqlnb != 1)	// If not a PHP number or unknown, we change format
	{
		if ($thousand != ',' && $thousand != '.') $amount=str_replace(',','.',$amount);	// To accept 2 notations for french users
		$amount=str_replace(' ','',$amount);	// To avoid spaces
		$amount=str_replace($thousand,'',$amount);	// Replace of thousand before replace of dec to avoid pb if thousand is .
		$amount=str_replace($dec,'.',$amount);
	}
	if ($rounding)
	{
		if ($rounding == 'MU')     $amount = round($amount,$conf->global->MAIN_MAX_DECIMALS_UNIT);
		elseif ($rounding == 'MT') $amount = round($amount,$conf->global->MAIN_MAX_DECIMALS_TOT);
		elseif ($rounding == 'MS') $amount = round($amount,$conf->global->MAIN_MAX_DECIMALS_SHOWN);
		else $amount='ErrorBadParameterProvidedToFunction';
		// Always make replace because each math function (like round) replace
		// with local values and we want a number that has a SQL string format x.y
		if ($thousand != ',' && $thousand != '.') $amount=str_replace(',','.',$amount);	// To accept 2 notations for french users
		$amount=str_replace(' ','',$amount);	// To avoid spaces
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
 \brief      	Fonction qui renvoie la tva d'une ligne (en fonction du vendeur, acheteur et taux du produit)
 \remarks    	Si vendeur non assujeti a TVA, TVA par dï¿½faut=0. Fin de rï¿½gle.
 Si le (pays vendeur = pays acheteur) alors TVA par dï¿½faut=TVA du produit vendu. Fin de rï¿½gle.
 Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par dï¿½faut=0 (La TVA doit ï¿½tre payï¿½ par acheteur au centre d'impots de son pays et non au vendeur). Fin de rï¿½gle.
 Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par dï¿½faut=TVA du produit vendu. Fin de rï¿½gle.
 Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (acheteur = entreprise avec num TVA) intra alors TVA par dï¿½faut=0. Fin de rï¿½gle.
 Sinon TVA proposï¿½e par dï¿½faut=0. Fin de rï¿½gle.
 \param      	societe_vendeuse    	Objet sociï¿½tï¿½ vendeuse
 \param      	societe_acheteuse   	Objet sociï¿½tï¿½ acheteuse
 \param      	taux_produit        	Taux par defaut du produit vendu (old way to get product vat rate)
 \param      	idprod					Id product (new way to get product vat rate)
 \return     	float               	Taux de tva a appliquer, -1 si ne peut etre dï¿½terminï¿½
 */
function get_default_tva($societe_vendeuse, $societe_acheteuse, $taux_produit, $idprod=0)
{
	if (!is_object($societe_vendeuse)) return -1;
	if (!is_object($societe_acheteuse)) return -1;

	dolibarr_syslog("get_default_tva vendeur_assujeti=".$societe_vendeuse->tva_assuj." pays_vendeur=".$societe_vendeuse->pays_code.", seller in cee=".$societe_vendeuse->isInEEC().", pays_acheteur=".$societe_acheteuse->pays_code.", buyer in cee=".$societe_acheteuse->isInEEC().", taux_produit(deprecated)=".$taux_produit.", idprod=".$idprod);

	// Si vendeur non assujeti a TVA (tva_assuj vaut 0/1 ou franchise/reel)
	if (is_numeric($societe_vendeuse->tva_assuj) && ! $societe_vendeuse->tva_assuj) return 0;
	if (! is_numeric($societe_vendeuse->tva_assuj) && $societe_vendeuse->tva_assuj=='franchise') return 0;

	// Si le (pays vendeur = pays acheteur) alors la TVA par dï¿½faut=TVA du produit vendu. Fin de rï¿½gle.
	//if (is_object($societe_acheteuse) && ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id) && ($societe_acheteuse->tva_assuj == 1 || $societe_acheteuse->tva_assuj == 'reel'))
	// Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concercnï¿½ si le test suivant n'est pas suffisant.
	if ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id)
	{
		if ($idprod) return get_product_vat_for_country($idprod,$societe_vendeuse->pays_code);
		if (strlen($taux_produit) == 0) return -1;	// Si taux produit = '', on ne peut dï¿½terminer taux tva
		return $taux_produit;
	}

	// Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par dï¿½faut=0 (La TVA doit ï¿½tre payï¿½ par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de rï¿½gle.
	// Non gï¿½rï¿½

	// Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par dï¿½faut=TVA du produit vendu. Fin de rï¿½gle.
	if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()) && ! $societe_acheteuse->tva_intra)
	{
		if ($idprod) return get_product_vat_for_country($idprod,$societe_vendeuse->pays_code);
		if (strlen($taux_produit) == 0) return -1;	// Si taux produit = '', on ne peut dï¿½terminer taux tva
		return $taux_produit;
	}

	// Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (acheteur = entreprise avec num TVA intra) alors TVA par dï¿½faut=0. Fin de rï¿½gle.
	if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()) && $societe_acheteuse->tva_intra)
	{
		return 0;
	}

	// Sinon la TVA proposï¿½e par dï¿½faut=0. Fin de rï¿½gle.
	// Rem: Cela signifie qu'au moins un des 2 est hors Communautï¿½ europï¿½enne et que le pays diffï¿½re
	return 0;
}


/**
 \brief      	Fonction qui renvoie si tva doit etre tva percue rï¿½cupï¿½rable
 \remarks    	Si vendeur non assujeti a TVA, TVA par dï¿½faut=0. Fin de rï¿½gle.
 Si le (pays vendeur = pays acheteur) alors TVA par dï¿½faut=TVA du produit vendu. Fin de rï¿½gle.
 Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par dï¿½faut=0 (La TVA doit ï¿½tre payï¿½ par acheteur au centre d'impots de son pays et non au vendeur). Fin de rï¿½gle.
 Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par dï¿½faut=TVA du produit vendu. Fin de rï¿½gle.
 Si (vendeur et acheteur dans Communautï¿½ europï¿½enne) et (acheteur = entreprise avec num TVA) intra alors TVA par dï¿½faut=0. Fin de rï¿½gle.
 Sinon TVA proposï¿½e par dï¿½faut=0. Fin de rï¿½gle.
 \param      	societe_vendeuse    	Objet sociï¿½tï¿½ vendeuse
 \param      	societe_acheteuse   	Objet sociï¿½tï¿½ acheteuse
 \param      	taux_produit        	Taux par defaut du produit vendu
 \return     	float               	0 or 1
 */
function get_default_npr($societe_vendeuse, $societe_acheteuse, $taux_produit)
{

	return 0;
}


/**
 \brief  Renvoie oui ou non dans la langue choisie
 \param	yesno			Variable pour test si oui ou non
 \param	case			1=Yes/No, 0=yes/no
 \param	color			0=texte only, 1=Text is format with a color font style
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
		$class='error';
	}
	if ($color) return '<font class="'.$class.'">'.$result.'</font>';
	return $result;
}


/**
 \brief  Fonction pour qui retourne le rowid d'un departement par son code
 \param  db          handler d'accï¿½s base
 \param	code		Code rï¿½gion
 \param	pays_id		Id du pays
 */
function departement_rowid($db,$code, $pays_id)
{
	$sql = "SELECT c.rowid FROM ".MAIN_DB_PREFIX."c_departements as c,".MAIN_DB_PREFIX."c_regions as r";
	$sql .= " WHERE c.code_departement=". $code;
	$sql .= " AND c.fk_region = r.code_region";
	$sql .= " AND r.fk_pays =".$pays_id;

	if ($db->query($sql))
	{
		$num = $db->num_rows();
		if ($num)
		{
	  $obj = $db->fetch_object();
	  return  $obj->rowid;
		}
		else
		{
	  return 0;
		}
		$db->free();
	}
	else
	{
		return 0;
	}
}

/**
 \brief      Renvoi un chemin de classement rï¿½pertoire en fonction d'un id
 \remarks    Examples: 1->"0/0/1/", 15->"0/1/5/"
 \param      $num        	Id a dï¿½composer
 \param      $level		Niveau de decoupage (1, 2 ou 3 niveaux)
 */
function get_exdir($num,$level=3)
{
	$num = eregi_replace('[^0-9]','',$num);
	$num = substr("000".$num, -$level);
	if ($level == 1) return substr($num,0,1).'/';
	if ($level == 2) return substr($num,1,1).'/'.substr($num,0,1).'/';
	if ($level == 3) return substr($num,2,1).'/'.substr($num,1,1).'/'.substr($num,0,1).'/';
	return '';
}

/**
 \brief      Crï¿½ation de rï¿½pertoire recursive
 \param      $dir        Rï¿½pertoire a crï¿½er
 \return     int         < 0 si erreur, >= 0 si succï¿½s
 */
function create_exdir($dir)
{
	dolibarr_syslog("functions.lib.php::create_exdir: dir=$dir",LOG_INFO);

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

		// Attention, le is_dir() peut ï¿½chouer bien que le rep existe.
		// (ex selon config de open_basedir)
		if ($ccdir)
		{
			if (! @is_dir($ccdir))
			{
		  dolibarr_syslog("functions.lib.php::create_exdir: Directory '".$ccdir."' does not exists or is outside open_basedir PHP setting.",LOG_DEBUG);

		  umask(0);
		  if (! @mkdir($ccdir, 0755))
		  {
		  	// Si le is_dir a renvoyï¿½ une fausse info, alors on passe ici.
		  	dolibarr_syslog("functions.lib.php::create_exdir: Fails to create directory '".$ccdir."' or directory already exists.",LOG_WARNING);
		  	$nberr++;
		  }
		  else
		  {
		  	dolibarr_syslog("functions.lib.php::create_exdir: Directory '".$ccdir."' created",LOG_DEBUG);
		  	$nberr=0;	// On remet a zï¿½ro car si on arrive ici, cela veut dire que les ï¿½checs prï¿½cï¿½dents peuvent etre ignorï¿½s
		  	$nbcreated++;
		  }
			}
			else
			{
				$nberr=0;	// On remet a zï¿½ro car si on arrive ici, cela veut dire que les ï¿½checs prï¿½cï¿½dents peuvent etre ignorï¿½s
			}
		}
	}
	return ($nberr ? -$nberr : $nbcreated);
}




/**
 \brief   Retourne le numï¿½ro de la semaine par rapport a une date
 \param   time   	Date au format 'timestamp'
 \return  int		Numï¿½ro de semaine
 */
function numero_semaine($time)
{
	$stime = strftime( '%Y-%m-%d',$time);

	if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?',$stime,$reg))
	{
		// Date est au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
		$annee = $reg[1];
		$mois = $reg[2];
		$jour = $reg[3];
	}

	/*
	 * Norme ISO-8601:
	 * - La semaine 1 de toute annï¿½e est celle qui contient le 4 janvier ou que la semaine 1 de toute annï¿½e est celle qui contient le 1er jeudi de janvier.
	 * - La majoritï¿½ des annï¿½es ont 52 semaines mais les annï¿½es qui commence un jeudi et les annï¿½es bissextiles commenï¿½ant un mercredi en possï¿½de 53.
	 * - Le 1er jour de la semaine est le Lundi
	 */

	// Dï¿½finition du Jeudi de la semaine
	if (date("w",mktime(12,0,0,$mois,$jour,$annee))==0) // Dimanche
	$jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)-3*24*60*60;
	else if (date("w",mktime(12,0,0,$mois,$jour,$annee))<4) // du Lundi au Mercredi
	$jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)+(4-date("w",mktime(12,0,0,$mois,$jour,$annee)))*24*60*60;
	else if (date("w",mktime(12,0,0,$mois,$jour,$annee))>4) // du Vendredi au Samedi
	$jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)-(date("w",mktime(12,0,0,$mois,$jour,$annee))-4)*24*60*60;
	else // Jeudi
	$jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee);

	// Dï¿½finition du premier Jeudi de l'annï¿½e
	if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))==0) // Dimanche
	{
		$premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine))+4*24*60*60;
	}
	else if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))<4) // du Lundi au Mercredi
	{
		$premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine))+(4-date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine))))*24*60*60;
	}
	else if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))>4) // du Vendredi au Samedi
	{
		$premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine))+(7-(date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))-4))*24*60*60;
	}
	else // Jeudi
	{
		$premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine));
	}

	// Dï¿½finition du numï¿½ro de semaine: nb de jours entre "premier Jeudi de l'annï¿½e" et "Jeudi de la semaine";
	$numeroSemaine =     (
	(
	date("z",mktime(12,0,0,date("m",$jeudiSemaine),date("d",$jeudiSemaine),date("Y",$jeudiSemaine)))
	-
	date("z",mktime(12,0,0,date("m",$premierJeudiAnnee),date("d",$premierJeudiAnnee),date("Y",$premierJeudiAnnee)))
	) / 7
	) + 1;

	// Cas particulier de la semaine 53
	if ($numeroSemaine==53)
	{
		// Les annï¿½es qui commence un Jeudi et les annï¿½es bissextiles commenï¿½ant un Mercredi en possï¿½de 53
		if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))==4 || (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))==3 && date("z",mktime(12,0,0,12,31,date("Y",$jeudiSemaine)))==365))
		{
			$numeroSemaine = 53;
		}
		else
		{
			$numeroSemaine = 1;
		}
	}

	//echo $jour."-".$mois."-".$annee." (".date("d-m-Y",$premierJeudiAnnee)." - ".date("d-m-Y",$jeudiSemaine).") -> ".$numeroSemaine."<BR>";

	return sprintf("%02d",$numeroSemaine);
}


/**
 \brief   Retourne le picto champ obligatoire
 \return  string		Chaine avec picto obligatoire
 */
function picto_required()
{
	return '<b>*</b>';
}
/**
 \brief   Convertit une masse d'une unite vers une autre unite
 \param   weight    float	Masse a convertir
 \param   from_unit int     Unite originale en puissance de 10
 \param   to_unit   int     Nouvelle unite  en puissance de 10
 \return  float	        Masse convertie
 */
function weight_convert($weight,&$from_unit,$to_unit)
{
	/* Pour convertire 320 gr en Kg appeler
	 *  $f = -3
	 *  weigh_convert(320, $f, 0) retournera 0.32
	 *
	 */
	while ($from_unit  <> $to_unit)
	{
		if ($from_unit > $to_unit)
		{
	  $weight = $weight * 10;
	  $from_unit = $from_unit - 1;
	  $weight = weight_convert($weight,$from_unit, $to_unit);
		}
		if ($from_unit < $to_unit)
		{
	  $weight = $weight / 10;
	  $from_unit = $from_unit + 1;
	  $weight = weight_convert($weight,$from_unit, $to_unit);
		}
	}

	return $weight;
}

/**
 \brief   Renvoi le texte d'une unite
 \param   int                 Unit
 \param   measuring_style     Le style de mesure : weight, volume,...
 \return  string	            Unite
 \todo    gerer les autres unitï¿½s de mesure comme la livre, le gallon, le litre, ...
 */
function measuring_units_string($unit,$measuring_style='')
{
	/* Note Rodo aux dev :)
	 * Ne pas insï¿½rer dans la base de donnï¿½es ces valeurs
	 * cela surchagerait inutilement d'une requete supplï¿½mentaire
	 * pour quelque chose qui est somme toute peu variable
	 */

	global $langs;

	if ($measuring_style == 'weight')
	{
		$measuring_units[3] = $langs->trans("WeightUnitton");
		$measuring_units[0] = $langs->trans("WeightUnitkg");
		$measuring_units[-3] = $langs->trans("WeightUnitg");
		$measuring_units[-6] = $langs->trans("WeightUnitmg");
	}
	else if ($measuring_style == 'volume')
	{
		$measuring_units[0] = $langs->trans("VolumeUnitm3");
		$measuring_units[-3] = $langs->trans("VolumeUnitdm3");
		$measuring_units[-6] = $langs->trans("VolumeUnitcm3");
		$measuring_units[-9] = $langs->trans("VolumeUnitmm3");
	}

	return $measuring_units[$unit];
}

/**
 \brief   	Clean an url
 \param   	url			Url
 \param   	http		1: keep http, 0: remove also http
 \return  	string	    CleanUrl
 */
function clean_url($url,$http=1)
{
	if (eregi('^(https?:[\\\/]+)?([0-9A-Z\-\.]+\.[A-Z]{2,4})(:[0-9]+)?',$url,$regs))
	{
		$proto=$regs[1];
		$domain=$regs[2];
		$port=$regs[3];
		//print $url." -> ".$proto." - ".$domain." - ".$port;
		$url = unaccent_isostring(trim($url));

		// Si http: defini on supprime le http (Si https on ne supprime pas)
		if ($http==0)
		{
			if (eregi('^http:[\\\/]+',$url))
			{
				$url = eregi_replace('^http:[\\\/]+','',$url);
				$proto = '';
			}
		}

		// On passe le nom de domaine en minuscule
		$url = eregi_replace('^(https?:[\\\/]+)?'.$domain,$proto.strtolower($domain),$url);

		return $url;
	}
}



/**
 *	\brief   	Clean a string from all html tags
 *	\param   	StringHtml			String to clean
 *	\param		removelinefeed		Replace also all lines feeds by a space
 *	\return  	string	    		String cleaned
 */
function clean_html($StringHtml,$removelinefeed=1)
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
	$CleanString = $temp;
	return $CleanString;
}

/**
 \brief   Convert a binaray data to string that represent hexadecimal value
 \param   bin			Value to convert
 \param   pad      	Add 0
 \param   upper		Convert to tupper
 \return  string		x
 */
function binhex($bin, $pad=false, $upper=false){
	$last = strlen($bin)-1;
	for($i=0; $i<=$last; $i++){ $x += $bin[$last-$i] * pow(2,$i); }
	$x = dechex($x);
	if($pad){ while(strlen($x) < intval(strlen($bin))/4){ $x = "0$x"; } }
	if($upper){ $x = strtoupper($x); }
	return $x;
}

/**
 \brief   Convertir de l'hï¿½xadï¿½cimal en binaire
 \param   string      hexa
 \return  string	    bin
 */
function hexbin($hexa){
	$bin='';
	for($i=0;$i<strlen($hexa);$i++)
	{
		$bin.=str_pad(decbin(hexdec($hexa{$i})),4,'0',STR_PAD_LEFT);
	}
	return $bin;
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
 *	\brief		This function is called to encode a string into a HTML string
 *	\param		stringtoencode		String to encode
 *	\param		nl2brmode			0=Adding br before \n, 1=Replacing \n by br (for use with FPDF writeHTMLCell function for example)
 *	\remarks	For PDF usage, you can show text by 2 ways:
 *				- writeHTMLCell -> param must be encoded into HTML.
 *				- MultiCell -> param must not be encoded into HTML.
 *				Because writeHTMLCell convert also \n into <br>, if function
 *				is used to build PDF, nl2brmode must be 1.
 */
function dol_htmlentitiesbr($stringtoencode,$nl2brmode=0)
{
	if (dol_textishtml($stringtoencode)) 
	{
		// Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
		$stringtoencode=eregi_replace('<br( [ a-zA-Z_="]*)?/?>','<br>',$stringtoencode);
		return $stringtoencode;
	}
	else {
		$newstring=dol_nl2br(htmlentities($stringtoencode),$nl2brmode);
		// Other substitutions that htmlentities does not do
		$newstring=str_replace(chr(128),'&euro;',$newstring);	// 128 = 0x80
		return $newstring;
	}
}

/*
 *	\brief		This function is called to decode a HTML string
 *	\param		stringtodecode		String to decode
 */
function dol_htmlentitiesbr_decode($stringtodecode)
{
	$ret=html_entity_decode($stringtodecode);
	$ret=eregi_replace("\r\n".'<br( [ a-zA-Z_="]*)?/?>',"<br>",$ret);
	$ret=eregi_replace('<br( [ a-zA-Z_="]*)?/?>'."\r\n","\r\n",$ret);
	$ret=eregi_replace('<br( [ a-zA-Z_="]*)?/?>'."\n","\n",$ret);
	$ret=eregi_replace('<br( [ a-zA-Z_="]*)?/?>',"\n",$ret);
	return $ret;
}

/**
 * 	\brief   Decode le code html
 * 	\param   string      stringhtml
 * 	\return  string	  decodestring
 */
function dol_entity_decode($stringhtml)
{
	$decodedstring = html_entity_decode($stringhtml);
	return $decodedstring;
}

/**
 \brief		Check if a string is a correct iso string
 			If not, it will we considered not HTML encoded even if it is by FPDF.
 \remarks	Example, if string contains euro symbol that has ascii code 128.
 \param		s		String to check
 \return	int		0 if bad iso, 1 if good iso
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
 *	\brief		Return nb of lines of a text
 *	\param		s			String to check
 * 	\param		maxchar		Not yet used
 *	\return		int			0 if bad iso, 1 if good iso
 */
function dol_nboflines($s,$maxchar=0)
{
	$arraystring=split("\n",$s);
	$nb=sizeof($arraystring);
	
	return $nb;
}


/**
 \brief     	Fonction retournant le nombre de jour fieries samedis et dimanches entre 2 dates entrees en timestamp
 \remarks		SERVANT AU CALCUL DES JOURS OUVRABLES
 \param	    timestampStart      Timestamp de debut
 \param	    timestampEnd        Timestamp de fin
 \return   	nbFerie             Nombre de jours feries
 */
function num_public_holiday($timestampStart, $timestampEnd, $countrycode='FR')
{
	$nbFerie = 0;

	while ($timestampStart != $timestampEnd)
	{
		$ferie=false;
		$countryfound=0;

		$jour  = date("d", $timestampStart);
		$mois  = date("m", $timestampStart);
		$annee = date("Y", $timestampStart);

		if ($countrycode == 'FR')
		{
			$countryfound=1;

			// Definition des dates feriees fixes
			if($jour == 1 && $mois == 1)   $ferie=true; // 1er janvier
			if($jour == 1 && $mois == 5)   $ferie=true; // 1er mai
			if($jour == 8 && $mois == 5)   $ferie=true; // 5 mai
			if($jour == 14 && $mois == 7)  $ferie=true; // 14 juillet
			if($jour == 15 && $mois == 8)  $ferie=true; // 15 aout
			if($jour == 1 && $mois == 11)  $ferie=true; // 1 novembre
			if($jour == 11 && $mois == 11) $ferie=true; // 11 novembre
			if($jour == 25 && $mois == 12) $ferie=true; // 25 decembre

			// Calcul du jour de paques
			$date_paques = easter_date($annee);
			$jour_paques = date("d", $date_paques);
			$mois_paques = date("m", $date_paques);
			if($jour_paques == $jour && $mois_paques == $mois) $ferie=true;
			// Paques

			// Calcul du jour de l ascension (38 jours apres Paques)
			$date_ascension = mktime(date("H", $date_paques),
			date("i", $date_paques),
			date("s", $date_paques),
			date("m", $date_paques),
			date("d", $date_paques) + 38,
			date("Y", $date_paques)
			);
			$jour_ascension = date("d", $date_ascension);
			$mois_ascension = date("m", $date_ascension);
			if($jour_ascension == $jour && $mois_ascension == $mois) $ferie=true;
			//Ascension

			// Calcul de Pentecote (11 jours apres Paques)
			$date_pentecote = mktime(date("H", $date_ascension),
			date("i", $date_ascension),
			date("s", $date_ascension),
			date("m", $date_ascension),
			date("d", $date_ascension) + 11,
			date("Y", $date_ascension)
			);
			$jour_pentecote = date("d", $date_pentecote);
			$mois_pentecote = date("m", $date_pentecote);
			if($jour_pentecote == $jour && $mois_pentecote == $mois) $ferie=true;
			//Pentecote

			// Calul des samedis et dimanches
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if($jour_semaine == 0 || $jour_semaine == 6) $ferie=true;
			//Samedi (6) et dimanche (0)
		}

		// Mettre ici cas des autres pays


		// Cas pays non defini
		if (! $countryfound)
		{
			// Calul des samedis et dimanches
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if($jour_semaine == 0 || $jour_semaine == 6) $ferie=true;
			//Samedi (6) et dimanche (0)
		}

		// On incremente compteur
		if ($ferie) $nbFerie++;

		// Incrementation du nombre de jour (on avance dans la boucle)
		$jour++;
		$timestampStart=mktime(0,0,0,$mois,$jour,$annee);
	}

	return $nbFerie;
}

/**
 \brief     Fonction retournant le nombre de jour entre deux dates
 \param	    timestampStart      Timestamp de dï¿½but
 \param	    timestampEnd        Timestamp de fin
 \param     lastday             On prend en compte le dernier jour, 0: non, 1:oui
 \return    nbjours             Nombre de jours
 */
function num_between_day($timestampStart, $timestampEnd, $lastday=0)
{
	if ($timestampStart < $timestampEnd)
	{
		if ($lastday == 1)
		{
			$bit = 0;
		}
		else
		{
			$bit = 1;
		}
		$nbjours = round(($timestampEnd - $timestampStart)/(60*60*24)-$bit);
	}
	return $nbjours;
}

/**
 \brief     Fonction retournant le nombre de jour entre deux dates sans les jours fï¿½riï¿½s (jours ouvrï¿½s)
 \param	    timestampStart      Timestamp de dï¿½but
 \param	    timestampEnd        Timestamp de fin
 \param     inhour              0: sort le nombre de jour , 1: sort le nombre d'heure (72 max)
 \param     lastday             On prend en compte le dernier jour, 0: non, 1:oui
 \return    nbjours             Nombre de jours ou d'heures
 */
function num_open_day($timestampStart, $timestampEnd,$inhour=0,$lastday=0)
{
	global $langs;

	if ($timestampStart < $timestampEnd)
	{
		$bit = 0;
		if ($lastday == 1) $bit = 1;
		$nbOpenDay = num_between_day($timestampStart, $timestampEnd, $bit) - num_public_holiday($timestampStart, $timestampEnd);
		$nbOpenDay.= " ".$langs->trans("Days");
		if ($inhour == 1 && $nbOpenDay <= 3) $nbOpenDay = $nbOpenDay*24 . $langs->trans("HourShort");
		return $nbOpenDay;
	}
	else
	{
		return $langs->trans("Error");
	}
}

/**
 *	\brief     Fonction retournant le nombre de lignes dans un texte formate
 *	\param	    texte      Texte
 *	\param	    maxlinesize      Largeur de ligne en caracteres(ou 0 si pas de limite - defaut)
 *	\return    nblines    Nombre de lignes
 */
function num_lines($texte,$maxlinesize=0)
{
	$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
	$texte = strtr($texte, $repTable);
	$pattern = '/(<[^>]+>)/Uu';
	$a = preg_split($pattern, $texte, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$nblines = ((count($a)+1)/2);
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

/*
 *    \brief      Effectue les substitutions des mots clï¿½s par les donnï¿½es en fonction du tableau
 *    \param      chaine      			Chaine dans laquelle faire les substitutions
 *    \param      substitutionarray		Tableau clï¿½ substitution => valeur a mettre
 *    \return     string      			Chaine avec les substitutions effectuï¿½es
 */
function make_substitutions($chaine,$substitutionarray)
{
	foreach ($substitutionarray as $key => $value)
	{
		$chaine=ereg_replace($key,$value,$chaine);
	}
	return $chaine;
}


/*
 *    \brief      Formate l'affichage de date de dï¿½but et de fin
 *    \param      date_start    date de dï¿½but
 *    \param      date_end      date de fin
 */
function print_date_range($date_start,$date_end)
{
	global $langs;

	if ($date_start && $date_end)
	{
		print ' ('.$langs->trans('DateFromTo',dolibarr_print_date($date_start),dolibarr_print_date($date_end)).')';
	}
	if ($date_start && ! $date_end)
	{
		print ' ('.$langs->trans('DateFrom',dolibarr_print_date($date_start)).')';
	}
	if (! $date_start && $date_end)
	{
		print ' ('.$langs->trans('DateUntil',dolibarr_print_date($date_end)).')';
	}
}


/*
 *
 */
function make_alpha_from_numbers($number)
{
	$numeric = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if($number<strlen($numeric))
	{
		return $numeric[$number];
	}
	else
	{
		$dev_by = floor($number/strlen($numeric));
		return "" . make_alpha_from_numbers($dev_by-1) . make_alpha_from_numbers($number-($dev_by*strlen($numeric)));
	}
}


/**
 \brief   Retourne un tableau des mois ou le mois sï¿½lectionnï¿½
 \param   selected			Mois ï¿½ sï¿½lectionner ou -1
 \return  string or array		Month string or array if selected < 0
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
 \brief  	Returns formated reduction
 \param		reduction		Reduction percentage
 \return		string			Formated reduction
 */
function dolibarr_print_reduction($reduction=0)
{
	global $langs;
	$langs->load("main");

	$string = '';

	if ($reduction == 100)
	{
		$string = $langs->trans("Offered");
	}
	else
	{
		$string = $reduction.'%';
	}

	return $string;
}


/**
 \brief  	Returns formated reduction
 \param		reduction		Reduction percentage
 \return		int				Return number of error messages shown
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
function dol_sort_array($array, $index, $order='asc', $natsort, $case_sensitive)
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
 * 	\brief	Test if a folder is empty
 * 	\return true is empty or non-existing, false if it contains files
 */
function is_emtpy_folder($folder){
   if(is_dir($folder) ){
       $handle = opendir($folder);
       while( (gettype( $name = readdir($handle)) != "boolean")){
               $name_array[] = $name;
       }
       foreach($name_array as $temp)
           $folder_content .= $temp;

       if($folder_content == "...")
           return true;
       else
           return false;
       
       closedir($handle);
   }
   else
       return true; // Le répertoire n'existe pas
} 

/**
 * 	\brief	Return an html table from an array
 */
function array2table($data,$tableMarkup=1,$tableoptions='',$troptions='',$tdoptions=''){
	$text='' ;
	if($tableMarkup) $text = '<table '.$tableoptions.'>' ;
	foreach($data as $key => $item){
		if(is_array($item)){
			$text.=array2tr($item,$troptions,$tdoptions) ;
		} else {
			$text.= '<tr '.$troptions.'>' ;
			$text.= '<td '.$tdoptions.'>'.$key.'</td>' ;
			$text.= '<td '.$tdoptions.'>'.$item.'</td>' ;
			$text.= '</tr>' ;
		}
	}
	if($tableMarkup) $text.= '</table>' ;
	return $text ;
}

/**
 * 	\brief	Return lines of an html table from an array
 */
function array2tr($data,$troptions='',$tdoptions=''){
	$text = '<tr '.$troptions.'>' ;
	foreach($data as $key => $item){
		$text.= '<td '.$tdoptions.'>'.$item.'</td>' ;
	}
	$text.= '</tr>' ;
	return $text ;
}
?>