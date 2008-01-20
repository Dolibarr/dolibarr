<?php
/* Copyright (C) 2000-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 */

/**
   \file       htdocs/lib/functions.inc.php
   \brief      Ensemble de fonctions de base de dolibarr sous forme d'include
*/

// Pour compatibilité lors de l'upgrade
if (! defined('DOL_DOCUMENT_ROOT'))
{
	define('DOL_DOCUMENT_ROOT', '..');
}

include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


/**
   \brief      Renvoi une version en chaine depuis une version en tableau
   \param	    versionarray        Tableau de version (vermajeur,vermineur,autre)
   \return     string              Chaine version
*/
function versiontostring($versionarray)
{
  $string='?';
  if (isset($versionarray[0])) $string=$versionarray[0];
  if (isset($versionarray[1])) $string.='.'.$versionarray[1];
  if (isset($versionarray[2])) $string.='.'.$versionarray[2];
  return $string;
}

/**
   \brief      Compare 2 versions
   \param      versionarray1       Tableau de version (vermajeur,vermineur,autre)
   \param      versionarray2       Tableau de version (vermajeur,vermineur,autre)
   \return     int                 <0 si versionarray1<versionarray2, 0 si =, >0 si versionarray1>versionarray2
*/
function versioncompare($versionarray1,$versionarray2)
{
    $ret=0;
    $i=0;
    while ($i < max(sizeof($versionarray1),sizeof($versionarray1)))
    {
        $operande1=isset($versionarray1[$i])?$versionarray1[$i]:0;
        $operande2=isset($versionarray2[$i])?$versionarray2[$i]:0;
        if ($operande1 < $operande2) { $ret = -1; break; }
        if ($operande1 > $operande2) { $ret =  1; break; }
        $i++;
    }
    return $ret;
}


/**
   \brief      Renvoie version PHP
   \return     array               Tableau de version (vermajeur,vermineur,autre)
*/
function versionphp()
{
  return split('\.',PHP_VERSION);
}


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
   \brief      Renvoi vrai si l'email a un nom de domaine qui résoud via dns
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
        \brief          Nettoie chaine de caractere de caracteres speciaux
		\remarks		Fonction appelee par exemple pour definir un nom de fichier depuis un identifiant chaine libre
        \param          str             Chaine a nettoyer
        \return         string          Chaine nettoyee (A-Z_)
*/
function sanitize_string($str)
{
    $forbidden_chars_to_underscore=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
    //$forbidden_chars_to_remove=array("(",")");
    $forbidden_chars_to_remove=array();
    return str_replace($forbidden_chars_to_underscore,"_",str_replace($forbidden_chars_to_remove,"",$str));
}


/**
	\brief      Envoi des messages dolibarr dans un fichier ou dans syslog
				Pour fichier:   fichier défini par SYSLOG_FILE
				Pour syslog:    facility défini par SYSLOG_FACILITY
	\param      message		    Message a tracer. Ne doit pas etre traduit si level = LOG_ERR
	\param      level           Niveau de l'erreur
	\remarks	Cette fonction n'a un effet que si le module syslog est activé.
				Warning, les fonctions syslog sont buggués sous Windows et générent des
				fautes de protection mémoire. Pour résoudre, utiliser le loggage fichier,
				au lieu du loggage syslog (configuration du module).
				Si SYSLOG_FILE_NO_ERROR défini, on ne gére pas erreur ecriture log
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
			if (defined("SYSLOG_FILE_NO_ERROR")) $file=@fopen(SYSLOG_FILE,"a+");
			else $file=fopen(SYSLOG_FILE,"a+");
			if ($file)
			{
				$liblevelarray=array(LOG_ERR=>'ERROR',LOG_WARNING=>'WARN',LOG_INFO=>'INFO',LOG_DEBUG=>'DEBUG');
				$liblevel=$liblevelarray[$level];
				if (! $liblevel) $liblevel='UNDEF';
				$message=strftime("%Y-%m-%d %H:%M:%S",time())." ".sprintf("%-5s",$liblevel)." ".$message;
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
				print $langs->trans("ErrorFailedToOpenFile",SYSLOG_FILE);
			}
		}
		else
		{
			//define_syslog_variables(); déja définit dans master.inc.php
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
		\brief      Récupére une constante depuis la base de données.
		\see        dolibarr_del_const, dolibarr_set_const
		\param	    db          Handler d'accés base
		\param	    name		Nom de la constante
		\return     string      Valeur de la constante
*/
function dolibarr_get_const($db, $name)
{
    $value='';

    $sql ="SELECT value";
    $sql.=" FROM llx_const";
    $sql.=" WHERE name = '".addslashes($name)."'";
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj=$db->fetch_object($resql);
        $value=$obj->value;
    }
    return $value;
}


/**
   \brief      Insertion d'une constante dans la base de données.
   \see        dolibarr_del_const, dolibarr_get_const
   \param	    db          Handler d'accés base
   \param	    name		Nom de la constante
   \param	    value		Valeur de la constante
   \param	    type		Type de constante (chaine par défaut)
   \param	    visible	    La constante est elle visible (0 par défaut)
   \param	    note		Explication de la constante
   \return     int         <0 si ko, >0 si ok
*/
function dolibarr_set_const($db, $name, $value, $type='chaine', $visible=0, $note='')
{
    global $conf;

    $db->begin();

    if (! $name)
    {
    	dolibarr_print_error("Error: Call to function dolibarr_set_const with wrong parameters");
    	exit;
    }

    //dolibarr_syslog("dolibarr_set_const name=$name, value=$value");
    $sql = "DELETE FROM llx_const WHERE name = '".addslashes($name)."';";
    $resql=$db->query($sql);

    $sql = "INSERT INTO llx_const(name,value,type,visible,note)";
    $sql.= " VALUES ('$name','".addslashes($value)."','$type',$visible,'".addslashes($note)."');";
    $resql=$db->query($sql);

    if ($resql)
    {
        $db->commit();
        $conf->global->$name=$value;
        return 1;
    }
    else
    {
        $db->rollback();
        return -1;
    }
}

/**
	\brief		Effacement d'une constante dans la base de données
	\see        	dolibarr_get_const, dolibarr_sel_const
	\param	    db          Handler d'accés base
	\param	    name		Nom ou rowid de la constante
	\return     	int         <0 si ko, >0 si ok
*/
function dolibarr_del_const($db, $name)
{
	global $conf;
	
	$sql = "DELETE FROM llx_const";
	$sql.=" WHERE name='".addslashes($name)."' or rowid='".addslashes($name)."'";
	$resql=$db->query($sql);
	if ($resql)
	{
		$conf->global->$name='';
		return 1;
	}
	else
	{
		return -1;
	}
}


/**
   \brief      Sauvegarde parametrage personnel
   \param	    db          Handler d'accés base
   \param	    user        Objet utilisateur
   \param	    url         Si defini, on sauve parametre du tableau tab dont clé = (url avec sortfield, sortorder, begin et page)
   Si non defini on sauve tous parametres du tableau tab
   \param	    tab         Tableau (clé=>valeur) des paramétres a sauvegarder
   \return     int         <0 si ko, >0 si ok
*/
function dolibarr_set_user_page_param($db, &$user, $url='', $tab)
{
    // Verification parametres
    if (sizeof($tab) < 1) return -1;
    
    $db->begin();

    // On efface anciens paramétres pour toutes les clé dans $tab
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
    dolibarr_syslog("functions.inc.php::dolibarr_set_user_page_param $sql");

    $resql=$db->query($sql);
    if (! $resql)
    {
        dolibarr_print_error($db);
        $db->rollback();
    	exit;
    }

    foreach ($tab as $key => $value)
    {
        // On positionne nouveaux paramétres
        if ($value && (! $url || in_array($key,array('sortfield','sortorder','begin','page'))))
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,page,param,value)";
            $sql.= " VALUES (".$user->id.",";
            if ($url) $sql.= " '".urlencode($url)."',";
            else $sql.= " '',";
            $sql.= " '".$key."','".addslashes($value)."');";
            dolibarr_syslog("functions.inc.php::dolibarr_set_user_page_param $sql");

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
		\return	int			valeur formatée
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
		\brief      Effectue un décalage de date par rapport a une durée
		\param	    time                Date timestamp ou au format YYYY-MM-DD
		\param	    duration_value      Valeur de la durée a ajouter
		\param	    duration_unit       Unité de la durée a ajouter (d, m, y)
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
		\brief      Formattage de la date en fonction de la langue $conf->langage
		\param	    time        Date 'timestamp' ou format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
		\param	    format      Format d'affichage de la date
									"%d %b %Y",
									"%d/%m/%Y %H:%M",
									"%d/%m/%Y %H:%M:%S",
									"day", "daytext", "dayhour", "dayhourldap", "dayhourtext"
		\return     string      Date formatée ou '' si time null
*/
function dolibarr_print_date($time,$format='')
{
    global $conf;

    // Si format non défini, on prend $conf->format_date_text_short sinon %Y-%m-%d %H:%M:%S
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

        return strftime($format,dolibarr_mktime($shour,$smin,$ssec,$smonth,$sday,$syear));
    }
    else
    {
        // Date est un timestamps
        return strftime($format,$time);
    }
}


/**
		\brief  	Retourne une date fabriquée depuis une chaine
		\param		string			Date formatée en chaine
									YYYYMMDD
									YYYYMMDDHHMMSS
									DD/MM/YY ou DD/MM/YYYY
									DD/MM/YY HH:MM:SS ou DD/MM/YYYY HH:MM:SS
		\return		date			Date
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
		\brief  	Retourne une date fabriquée depuis infos.
					Remplace la fonction mktime non implémentée sous Windows si année < 1970
		\param		hour			Heure
		\param		minute			Minute
		\param		second			Seconde
		\param		month			Mois
		\param		day				Jour
		\param		year			Année
		\param		gm				Time gm
		\param		check			No check on parameters (Can use day 32, etc...)
		\return		timestamp		Date en timestamp, '' if error
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
		// strtotime is ok for range: Vendredi 13 Décembre 1901 20:45:54 GMT au Mardi 19 Janvier 2038 03:14:07 GMT.
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
        \brief      Formatage des numéros de telephone en fonction du format d'un pays
        \param	    phone			Numéro de telephone a formater
        \param	    country			Pays selon lequel formatter
        \return     string			Numéro de téléphone formaté
*/
function dolibarr_print_phone($phone,$country="FR")
{
    $phone=trim($phone);
    if (strstr($phone, ' ')) { return $phone; }
    if (strtoupper($country) == "FR") {
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
        \brief      Tronque une chaine a une taille donnée en ajoutant les points de suspension si cela dépasse
        \param      string				String to truncate
        \param      size				Max string size. 0 for no limit.
		\param		trunc				Where to trunc: right, left, middle
        \return     string				Truncated string
		\remarks	USE_SHORT_TITLE=0 can disable all truncings
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
        \brief      Compléte une chaine a une taille donnée par des espaces
        \param      string		Chaine a compléter
        \param      size		Longueur de la chaine.
        \param      side		0=Complétion a droite, 1=Complétion a gauche
        \param		char		Chaine de complétion
        \return     string		Chaine complétée
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
        \brief      Affiche picto propre a une notion/module (fonction générique)
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
        \brief      Affiche picto (fonction générique)
        \param      alt         Texte sur le alt de l'image
        \param      picto       Nom de l'image a afficher (Si pas d'extension, on met '.png')
        \param		options		Attribut supplémentaire a la balise img
		\return     string      Retourne tag img
*/
function img_picto($alt, $picto, $options='')
{
	global $conf;
	if (! eregi('(\.png|\.gif)$',$picto)) $picto.='.png';
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
        \brief      Affiche logo désactiver
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
        \brief      Affiche logo téléphone in
        \param      alt         Texte sur le alt de l'image
        \return     string      Retourne tag img
*/
function img_phone_in($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Modify");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/call.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief      Affiche logo téléphone out
        \param      alt         Texte sur le alt de l'image
        \return     string      Retourne tag img
*/
function img_phone_out($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Modify");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/call.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
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
        \brief      Affiche logo précédent
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
     \brief      Vérifie les droits de l'utilisateur
     \param      user      	  Utilisateur courant
     \param      module        Module a vérifier
     \param      objectid      ID du document
     \param      dbtable       Table de la base correspondant au module (optionnel)
     \param      list          Défini si la page sert de liste et donc ne fonctionne pas avec un id
*/
 function restrictedArea($user, $modulename, $objectid='', $dbtablename='', $list=0)
 {
 	global $db;
 	
 	if (!$modulename)
 	{
 		$modulename = 'societe';
 		$list = 1;
 	}
 		
 	$user->getrights($modulename);
 	$user->getrights('commercial');
 	
 	$socid = 0;
 	$nocreate = 0; 
 	
 	//si dbtable non défini, méme nom que le module
 	if (!$dbtablename) $dbtablename = $modulename;

 	if (!$user->rights->$modulename->lire)
 	{
 		accessforbidden();
 	}
 	else if (!$user->rights->$modulename->creer)
 	{
 		$nocreate = 1;
 		if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
 		{
 			accessforbidden();
 		}
 	}
 	
 	if ($user->societe_id > 0)
 	{
    $_GET["action"] = '';
	  $_POST["action"] = '';
    $socid = $user->societe_id;
    if (!$objectid) $objectid = $socid;
    if ($modulename == 'societe' && $socid <> $objectid) accessforbidden();
  }

  if ($objectid)
  {
  	if ($modulename == 'societe' && !$user->rights->commercial->client->voir && !$socid > 0)
  	{
  		$sql = "SELECT sc.fk_soc";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
      $sql .= " WHERE sc.fk_soc = ".$objectid." AND sc.fk_user = ".$user->id;
    }
    else if (!$user->rights->commercial->client->voir || $socid > 0)
    {
  	  $sql = "SELECT sc.fk_soc, dbt.fk_soc";
  	  $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX.$dbtablename." as dbt";
  	  $sql .= " WHERE dbt.rowid = ".$objectid;
      if (!$user->rights->commercial->client->voir && !$socid > 0)
      {
    	  $sql .= " AND sc.fk_soc = dbt.fk_soc AND sc.fk_user = ".$user->id;
      }
      if ($socid > 0) $sql .= " AND dbt.fk_soc = ".$socid;
    }
//print $sql;
    if ($sql && $db->query($sql))
    {
      if ($db->num_rows() == 0)
      {
      	accessforbidden();
      }
    }
  }
  else if ((!$objectid && $list==0) && $nocreate == 1)
  {
  	accessforbidden();
  }
  return $objectid;
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
		\brief      Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontée des bugs.
                    On doit appeler cette fonction quand une erreur technique bloquante est rencontrée.
                    Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
                    renvoyer leur erreur par l'intermédiaire de leur propriété "error".
        \param      db      Handler de base utilisé
        \param      error	Chaine erreur ou tableau de chaines erreur complémentaires a afficher
*/
function dolibarr_print_error($db='',$error='')
{
    global $conf,$langs,$argv;
    $syslog = '';

    // Si erreur intervenue avant chargement langue
    if (! $langs)
    {
        require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
        $langs = new Translate(DOL_DOCUMENT_ROOT ."/langs", $conf);
    }
    $langs->load("main");

    if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
    {
        print $langs->trans("DolibarrHasDetectedError").".<br>\n";
        print $langs->trans("InformationToHelpDiagnose").":<br><br>\n";

        print "<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";;
        print "<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";;
        print "<b>".$langs->trans("RequestedUrl").":</b> ".$_SERVER["REQUEST_URI"]."<br>\n";;
        print "<b>QUERY_STRING:</b> ".$_SERVER["QUERY_STRING"]."<br>\n";;
        print "<b>".$langs->trans("Referer").":</b> ".$_SERVER["HTTP_REFERER"]."<br>\n";;
        $syslog.="url=".$_SERVER["REQUEST_URI"];
        $syslog.=", query_string=".$_SERVER["QUERY_STRING"];
    }
    else                              // Mode CLI
    {
        print $langs->transnoentities("ErrorInternalErrorDetected").": ".$argv[0]."\n";
        $syslog.="pid=".getmypid();
    }

    if (is_object($db))
    {
        if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
        {
            print "<br>\n";
            print "<b>".$langs->trans("DatabaseTypeManager").":</b> ".$db->type."<br>\n";
            print "<b>".$langs->trans("RequestLastAccessInError").":</b> ".($db->lastqueryerror()?$db->lastqueryerror():$langs->trans("ErrorNoRequestInError"))."<br>\n";
            print "<b>".$langs->trans("ReturnCodeLastAccess").":</b> ".$db->errno()."<br>\n";
            print "<b>".$langs->trans("InformationLastAccess").":</b> ".$db->error()."<br>\n";
        }
        else                            // Mode CLI
        {
            print $langs->transnoentities("DatabaseTypeManager").":\n".$db->type."\n";
            print $langs->transnoentities("RequestLastAccessInError").":\n".($db->lastqueryerror()?$db->lastqueryerror():$langs->trans("ErrorNoRequestInError"))."\n";
            print $langs->transnoentities("ReturnCodeLastAccess").":\n".$db->errno()."\n";
            print $langs->transnoentities("InformationLastAccess").":\n".$db->error()."\n";

        }
        $syslog.=", sql=".$db->lastquery();
        $syslog.=", db_error=".$db->error();
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
	            print $langs->transnoentities("Message").":\n".$msg."\n" ;
	        }
	        $syslog.=", msg=".$msg;
		}
	}

    dolibarr_syslog("Error $syslog",LOG_ERROR);
}


/**
		\brief  Deplacer les fichiers telechargés, apres quelques controles divers
		\param	src_file	fichier source
		\param	dest_file	fichier de destination
		\return int         true=Deplacement OK, false=Pas de deplacement ou KO
*/
function doliMoveFileUpload($src_file, $dest_file)
{
	global $conf;
	
	$file_name = $dest_file;
	
	if ($conf->global->MAIN_USE_AVSCAN)
	{
		$malware = dol_avscan_file($src_file);
		if ($malware) return $malware;
	}

	// Security:
	// On renomme les fichiers avec extention executable car si on a mis le rep
	// documents dans un rep de la racine web (pas bien), cela permet d'executer
	// du code a la demande.
	if (eregi('\.htm|\.html|\.php|\.pl|\.cgi$',$file_name))
	{
		$file_name.= '.txt';
	}

	// Security:
	// On interdit les remontées de repertoire ainsi que les pipe dans 
	// les noms de fichiers.
	if (eregi('\.\.',$src_file) || eregi('[<>|]',$src_file))
	{
		dolibarr_syslog("Refused to deliver file ".$src_file);
		return false;
	}

	// Security:
	// On interdit les remontées de repertoire ainsi que les pipe dans 
	// les noms de fichiers.
	if (eregi('\.\.',$dest_file) || eregi('[<>|]',$dest_file))
	{
		dolibarr_syslog("Refused to deliver file ".$dest_file);
		return false;
	}

	$return=move_uploaded_file($src_file, $file_name);
	
	return $return;
}


/**
		\brief      Affichage de la ligne de titre d'un tabelau
		\param	    name        libelle champ
		\param	    file        url pour clic sur tri
		\param	    field       champ de tri
		\param	    begin       ("" par defaut)
		\param	    options     ("" par defaut)
		\param      td          options de l'attribut td ("" par defaut)
		\param      sortfield   nom du champ sur lequel est effectué le tri du tableau
		\param      sortorder   ordre du tri
*/
function print_liste_field_titre($name, $file, $field, $begin="", $options="", $td="", $sortfield="", $sortorder="")
{
    global $conf;
	//print "$name, $file, $field, $begin, $options, $td, $sortfield, $sortorder<br>\n";

    // Le champ de tri est mis en évidence.
    // Exemple si (sortfield,field)=("nom","xxx.nom") ou (sortfield,field)=("nom","nom")
    if ($sortfield == $field || $sortfield == ereg_replace("^[^\.]+\.","",$field))
    {
        print '<td class="liste_titre_sel" '. $td.'>';
    }
    else
    {
        print '<td class="liste_titre" '. $td.'>';
    }
    print $name."&nbsp;";
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
		\brief  Affichage d'un titre d'une fiche, aligné a gauche
		\param	titre			Le titre a afficher
		\param	mesg			Message suplémentaire a afficher a droite
		\param	picto			Picto pour ligne de titre
*/
function print_fiche_titre($titre, $mesg='', $picto='')
{
    print "\n";
    print '<table width="100%" border="0" class="notopnoleftnoright"><tr>';
	if ($picto) print '<td width="24" align="left" valign="middle">'.img_picto('',$picto).'</td>';
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
		else dolibarr_syslog("Failed to remove file $filename",LOG_ERROR);
	}
	return $ok;
}

/**
		\brief  	Effacement d'un répertoire
		\param		file			Répertoire a effacer
*/
function dol_delete_dir($dir)
{
	return rmdir($dir);
}

/**
		\brief  	Effacement d'un répertoire $dir et de son arborescence
		\param		file			Répertoire a effacer
		\param		count			Compteur pour comptage nb elements supprimés
		\return		int				Nombre de fichier+repértoires supprimés
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
		\return	 malware	Nom du virus si infecté sinon retourne "null"
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
		\param	titre			titre de la page
		\param	page			numéro de la page
		\param	file			lien
		\param	options         parametres complementaires lien ('' par defaut)
		\param	sortfield       champ de tri ('' par defaut)
		\param	sortorder       ordre de tri ('' par defaut)
		\param	center          chaine du centre ('' par defaut)
		\param	num             nombre d'élément total
*/
function print_barre_liste($titre, $page, $file, $options='', $sortfield='', $sortorder='', $center='', $num=-1)
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

    if ($page > 0 || $num > $conf->liste_limit)
    {
        print '<tr><td class="notopnoleftnoright"><div class="titre">'.$titre.($titre?' - ':'').$langs->trans('page').' '.($page+1);
        print '</div></td>';
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

    if ($sortfield) $options .= "&amp;sortfield=$sortfield";
    if ($sortorder) $options .= "&amp;sortorder=$sortorder";

    // Affichage des fleches de navigation
    print_fleche_navigation($page,$file,$options,$nextpage);

    print '</td></tr></table>';
}

/**
   \brief  Fonction servant a afficher les fleches de navigation dans les pages de listes
   \param	page			numéro de la page
   \param	file			lien
   \param	options         autres parametres d'url a propager dans les liens ("" par defaut)
   \param	nextpage	    faut-il une page suivante
*/
function print_fleche_navigation($page,$file,$options='',$nextpage)
{
  global $conf, $langs;
  if ($page > 0)
    {
      print '<a href="'.$file.'?page='.($page-1).$options.'">'.img_previous($langs->trans("Previous")).'</a>';
    }

  if ($nextpage > 0)
    {
      print '<a href="'.$file.'?page='.($page+1).$options.'">'.img_next($langs->trans("Next")).'</a>';
    }
}


/**
*		\brief      Fonction qui retourne un taux de tva formaté pour visualisation
*		\remarks    Fonction utilisée dans les pdf et les pages html
*		\param	    rate			Taux a formater (19.6 19,6 19.6% 19,6%...)
*		\return		string			Chaine avec montant formaté (19,6 ou 19,6%)
*/
function vatrate($rate)
{
	$foundpercent=false;
	if (eregi('%',$rate))
	{
		$rate=eregi_replace('%','',$rate);
		$foundpercent=true;
	}
	return price($rate,0,'',0,0).($foundpercent?'%':'');
}


/**
*		\brief      Fonction qui retourne un montant monétaire formaté pour visualisation
*		\remarks    Fonction utilisée dans les pdf et les pages html
*		\param	    amount			Montant a formater
*		\param	    html			Formatage html ou pas (0 par defaut)
*		\param	    outlangs		Objet langs pour formatage text
*		\param		trunc			1=Tronque affichage si trop de décimales,0=Force le non troncage
*		\param		nbdecimal		Nbre decimals minimum.
*		\return		string			Chaine avec montant formaté
*		\seealso	price2num		Fonction inverse de price
*/
function price($amount, $html=0, $outlangs='', $trunc=1, $nbdecimal=2)
{
	global $langs,$conf;

	// Separateurs par defaut
	$dec='.'; $thousand=' ';

	// Si $outlangs non force, on prend langue utilisateur
	if (! is_object($outlangs)) $outlangs=$langs;

	if ($outlangs->trans("SeparatorDecimal") != "SeparatorDecimal")  $dec=$outlangs->trans("SeparatorDecimal");
	if ($outlangs->trans("SeparatorThousand")!= "SeparatorThousand") $thousand=$outlangs->trans("SeparatorThousand");
	//print "amount=".$amount." html=".$html." trunc=".$trunc." nbdecimal=".$nbdecimal." dec=".$dec." thousand=".$thousand;

	//print "amount=".$amount."-";	
	$amount = ereg_replace(',','.',$amount);
	//print $amount."-";
	$datas = split('\.',$amount);
	$decpart = $datas[1];
	$decpart = eregi_replace('0+$','',$decpart);	// Supprime les 0 de fin de partie décimale
	//print "decpart=".$decpart."<br>";
	$end='';

	// On augmente au besoin si il y a plus de 2 décimales
	if (strlen($decpart) > $nbdecimal) $nbdecimal=strlen($decpart);
	// Si on depasse max
	if ($trunc && $nbdecimal > $conf->global->MAIN_MAX_DECIMALS_SHOWN) 
	{
		$nbdecimal=$conf->global->MAIN_MAX_DECIMALS_SHOWN;
		if (eregi('\.\.\.',$conf->global->MAIN_MAX_DECIMALS_SHOWN))
		{
			// Si un affichage est tronqué, on montre des ...
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
	\brief     		Fonction qui retourne un numérique conforme PHP et SQL, depuis un montant au
					format utilisateur.
	\remarks   		Fonction a appeler sur montants saisis avant un insert en base
	\param	    	amount		Montant a formater
	\param	    	rounding	'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
								'MT'=Round to Max with Tax (MAIN_MAX_DECIMALS_TOT)
								'MS'=Round to Max Shown (MAIN_MAX_DECIMALS_SHOWN)
								''=No rounding
	\return			string		Montant au format numérique PHP et SQL (Exemple: '99.99999')
	\seealso		price		Fonction inverse de price2num
*/
function price2num($amount,$rounding='')
{
	global $conf;
	
	// Round PHP function does not allow number like '1,234.5'.
	// Numbers must be '1234.5'
	$amount=ereg_replace(',','.',$amount);
	$amount=ereg_replace(' ','',$amount);
	if ($rounding)
	{
		if ($rounding == 'MU')     $amount = round($amount,$conf->global->MAIN_MAX_DECIMALS_UNIT);
		elseif ($rounding == 'MT') $amount = round($amount,$conf->global->MAIN_MAX_DECIMALS_TOT);
		elseif ($rounding == 'MS') $amount = round($amount,$conf->global->MAIN_MAX_DECIMALS_SHOWN);
		else $amount='ErrorBadParameterProvidedToFunction';
		$amount=ereg_replace(',','.',$amount);
		$amount=ereg_replace(' ','',$amount);
	}
	return $amount;
}


/**
   \brief      	Fonction qui renvoie la tva d'une ligne (en fonction du vendeur, acheteur et taux du produit)
   \remarks    	Si vendeur non assujeti a TVA, TVA par défaut=0. Fin de régle.
				Si le (pays vendeur = pays acheteur) alors TVA par défaut=TVA du produit vendu. Fin de régle.
				Si (vendeur et acheteur dans Communauté européenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par défaut=0 (La TVA doit étre payé par acheteur au centre d'impots de son pays et non au vendeur). Fin de régle.
				Si (vendeur et acheteur dans Communauté européenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par défaut=TVA du produit vendu. Fin de régle.
				Si (vendeur et acheteur dans Communauté européenne) et (acheteur = entreprise avec num TVA) intra alors TVA par défaut=0. Fin de régle.
				Sinon TVA proposée par défaut=0. Fin de régle.
   \param      	societe_vendeuse    	Objet société vendeuse
   \param      	societe_acheteuse   	Objet société acheteuse
   \param      	taux_produit        	Taux par defaut du produit vendu
   \return     	float               	Taux de tva a appliquer, -1 si ne peut etre déterminé
 */
function get_default_tva($societe_vendeuse, $societe_acheteuse, $taux_produit)
{
	dolibarr_syslog("get_default_tva vendeur_assujeti=".$societe_vendeuse->tva_assuj." pays_vendeur=".$societe_vendeuse->pays_id.", pays_acheteur=".$societe_acheteuse->pays_id.", taux_produit=".$taux_produit);

	if (!is_object($societe_vendeuse)) return -1;
	if (!is_object($societe_acheteuse)) return -1;

	// Si vendeur non assujeti a TVA (tva_assuj vaut 0/1 ou franchise/reel)
	if (is_numeric($societe_vendeuse->tva_assuj) && ! $societe_vendeuse->tva_assuj) return 0;
	if (! is_numeric($societe_vendeuse->tva_assuj) && $societe_vendeuse->tva_assuj=='franchise') return 0;

	// Si le (pays vendeur = pays acheteur) alors la TVA par défaut=TVA du produit vendu. Fin de régle.
	//if (is_object($societe_acheteuse) && ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id) && ($societe_acheteuse->tva_assuj == 1 || $societe_acheteuse->tva_assuj == 'reel'))
	// Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concercné si le test suivant n'est pas suffisant.
	if ($societe_vendeuse->pays_id == $societe_acheteuse->pays_id)
	{
		if (strlen($taux_produit) == 0) return -1;	// Si taux produit = '', on ne peut déterminer taux tva
	    return $taux_produit;
	}

	// Si (vendeur et acheteur dans Communauté européenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par défaut=0 (La TVA doit étre payé par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de régle.
	// Non géré

 	// Si (vendeur et acheteur dans Communauté européenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par défaut=TVA du produit vendu. Fin de régle.
	if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()) && ! $societe_acheteuse->tva_intra)
	{
		if (strlen($taux_produit) == 0) return -1;	// Si taux produit = '', on ne peut déterminer taux tva
	    return $taux_produit;
	}

 	// Si (vendeur et acheteur dans Communauté européenne) et (acheteur = entreprise avec num TVA intra) alors TVA par défaut=0. Fin de régle.
	if (($societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC()) && $societe_acheteuse->tva_intra)
	{
	    return 0;
	}

	// Sinon la TVA proposée par défaut=0. Fin de régle.
	// Rem: Cela signifie qu'au moins un des 2 est hors Communauté européenne et que le pays différe
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
   \brief      Fonction pour initialiser un salt pour la fonction crypt
   \param		$type		2=>renvoi un salt pour cryptage DES
   12=>renvoi un salt pour cryptage MD5
   non defini=>renvoi un salt pour cryptage par defaut
   \return		string		Chaine salt
*/
function makesalt($type=CRYPT_SALT_LENGTH)
{
  dolibarr_syslog("functions.inc::makesalt type=".$type);
  switch($type)
    {
    case 12:	// 8 + 4
      $saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
    case 8:		// 8 + 4 (Pour compatibilite, ne devrait pas etre utilisé)
      $saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
    case 2:		// 2
    default: 	// by default, fall back on Standard DES (should work everywhere)
      $saltlen=2; $saltprefix=''; $saltsuffix=''; break;
    }
  $salt='';
  while(strlen($salt) < $saltlen) $salt.=chr(rand(64,126));
  
  $result=$saltprefix.$salt.$saltsuffix;
  dolibarr_syslog("functions.inc::makesalt return=".$result);
  return $result;
}
/**
   \brief  Fonction pour qui retourne le rowid d'un departement par son code
   \param  db          handler d'accés base
   \param	code		Code région
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
   \brief      Renvoi un chemin de classement répertoire en fonction d'un id
   \remarks    Examples: 1->"0/0/1/", 15->"0/1/5/"
   \param      $num        	Id a décomposer
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
   \brief      Création de répertoire recursive
   \param      $dir        Répertoire a créer
   \return     int         < 0 si erreur, >= 0 si succés
*/
function create_exdir($dir)
{
    dolibarr_syslog("functions.inc.php::create_exdir: dir=$dir",LOG_INFO);

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

		// Attention, le is_dir() peut échouer bien que le rep existe.
		// (ex selon config de open_basedir)
        if ($ccdir)
        {
        	if (! @is_dir($ccdir))
        	{
		  dolibarr_syslog("functions.inc.php::create_exdir: Directory '".$ccdir."' does not exists or is outside open_basedir PHP setting.",LOG_DEBUG);

		  umask(0);
		  if (! @mkdir($ccdir, 0755))
		    {
		      // Si le is_dir a renvoyé une fausse info, alors on passe ici.
		      dolibarr_syslog("functions.inc.php::create_exdir: Fails to create directory '".$ccdir."' or directory already exists.",LOG_WARNING);
		      $nberr++;
		    }
		  else
		    {
		      dolibarr_syslog("functions.inc.php::create_exdir: Directory '".$ccdir."' created",LOG_DEBUG);
		      $nberr=0;	// On remet a zéro car si on arrive ici, cela veut dire que les échecs précédents peuvent etre ignorés
		      $nbcreated++;
		    }
		}
		else
		  {
		    $nberr=0;	// On remet a zéro car si on arrive ici, cela veut dire que les échecs précédents peuvent etre ignorés
		  }
        }
    }
    return ($nberr ? -$nberr : $nbcreated);
}


/**
   \brief		Scan a directory and return a list of files/directories
   \param		$path        	Starting path from which to search
   \param		$types        	Can be "directories", "files", or "all"
   \param		$recursive		Determines whether subdirectories are searched
   \param		$filter        	Regex for filter
   \param		$exludefilter  	Regex for exclude filter
   \param		$sortcriteria	Sort criteria ("name","date","size")
   \param		$sortorder		Sort order (SORT_ASC, SORT_DESC)
   \return		array			Array of array('name'=>xxx,'date'=>yyy,'size'=>zzz)
 */
function dolibarr_dir_list($path, $types="all", $recursive=0, $filter="", $excludefilter="", $sortcriteria="name", $sortorder=SORT_ASC)
{
	dolibarr_syslog("functions.inc.php::dolibarr_dir_list $path");

	$loaddate=false;
	$loadsize=false;

	if (! is_dir($path)) return array();

	if ($dir = opendir($path))
	{
		$file_list = array();
		while (false !== ($file = readdir($dir)))
		{
			$qualified=1;
			
			// Check if file is qualified
			if (eregi('^\.',$file)) $qualified=0;
			if ($excludefilter && eregi($excludefilter,$file)) $qualified=0;
			
			if ($qualified)
			{
				// Check whether this is a file or directory and whether we're interested in that type
				if ((is_dir($path."/".$file)) && (($types=="directories") || ($types=="all")))
				{
					// Add entry into file_list array
					if ($loaddate || $sortcriteria == 'date') $filedate=filemtime($path."/".$file);
					if ($loadsize || $sortcriteria == 'size') $filesize=filesize($path."/".$file);
					
					if (! $filter || eregi($filter,$path.'/'.$file))
					{
						$file_list[] = array(
						"name" => $file,
						"fullname" => $path.'/'.$file,
						"date" => $filedate,
						"size" => $filesize
						);
					}
					
					// if we're in a directory and we want recursive behavior, call this function again
					if ($recursive)
					{
						$file_list = array_merge($file_list, dolibarr_dir_list($path."/".$file."/", $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder));
					}
				}
				else if (($types == "files") || ($types == "all"))
				{
					// Add file into file_list array
					if ($loaddate || $sortcriteria == 'date') $filedate=filemtime($path."/".$file);
					if ($loadsize || $sortcriteria == 'size') $filesize=filesize($path."/".$file);
					if (! $filter || eregi($filter,$path.'/'.$file))
					{
						$file_list[] = array(
						"name" => $file,
						"fullname" => $path.'/'.$file,
						"date" => $filedate,
						"size" => $filesize
						);
					}
				}
			}
		}
		closedir($dir);
		
		// Obtain a list of columns
		$myarray=array();
		foreach ($file_list as $key => $row)
		{
			$myarray[$key]  = $row[$sortcriteria];
		}
		// Sort the data
		array_multisort($myarray, $sortorder, $file_list);
		
		return $file_list;
	}
	else
	{
		return false;
	}
}

/**
   \brief   Retourne le numéro de la semaine par rapport a une date
   \param   time   	Date au format 'timestamp'
   \return  int		Numéro de semaine
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
     * - La semaine 1 de toute année est celle qui contient le 4 janvier ou que la semaine 1 de toute année est celle qui contient le 1er jeudi de janvier.
     * - La majorité des années ont 52 semaines mais les années qui commence un jeudi et les années bissextiles commenéant un mercredi en posséde 53.
     * - Le 1er jour de la semaine est le Lundi
     */

    // Définition du Jeudi de la semaine
    if (date("w",mktime(12,0,0,$mois,$jour,$annee))==0) // Dimanche
        $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)-3*24*60*60;
    else if (date("w",mktime(12,0,0,$mois,$jour,$annee))<4) // du Lundi au Mercredi
        $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)+(4-date("w",mktime(12,0,0,$mois,$jour,$annee)))*24*60*60;
    else if (date("w",mktime(12,0,0,$mois,$jour,$annee))>4) // du Vendredi au Samedi
        $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)-(date("w",mktime(12,0,0,$mois,$jour,$annee))-4)*24*60*60;
    else // Jeudi
        $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee);

    // Définition du premier Jeudi de l'année
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

    // Définition du numéro de semaine: nb de jours entre "premier Jeudi de l'année" et "Jeudi de la semaine";
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
        // Les années qui commence un Jeudi et les années bissextiles commenéant un Mercredi en posséde 53
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
   \todo    gerer les autres unités de mesure comme la livre, le gallon, le litre, ...
*/
function measuring_units_string($unit,$measuring_style='')
{
  /* Note Rodo aux dev :)
   * Ne pas insérer dans la base de données ces valeurs
   * cela surchagerait inutilement d'une requete supplémentaire
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
    $measuring_units[-3] = $langs->trans("VolumeUnitcm3");
    $measuring_units[-6] = $langs->trans("VolumeUnitmm3");
  }

  return $measuring_units[$unit];
}

/**
   \brief   Decode le code html
   \param   string      StringHtml
   \return  string	    DecodeString
*/
function dol_entity_decode($StringHtml)
{
	$DecodeString = html_entity_decode($StringHtml);
	return $DecodeString;
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
   \brief   	Clean a string from all html tags
   \param   	StringHtml			String to clean
   \param		removelinefeed		Replace also all lines feeds by a space
   \return  	string	    		String cleaned
*/
function clean_html($StringHtml,$removelinefeed=1)
{
	$pattern = "<[^>]+>";
	$temp = dol_entity_decode($StringHtml);
	$temp = ereg_replace($pattern,"",$temp);

	// Supprime aussi les retours
	if ($removelinefeed) $temp=str_replace("\n"," ",$temp);

	// et les espaces doubles
	while(STRPOS($temp,"  "))
	{
		$temp = STR_REPLACE("  "," ",$temp);
	}
	$CleanString = $temp;
	return $CleanString;
}

/**
   \brief   Convertir du binaire en héxadécimal
   \param   string      bin
   \return  string	    x
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
   \brief   Convertir de l'héxadécimal en binaire
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

/*
*	\brief		Cette fonction est appelée pour coder ou non une chaine en html.
*	\param		stringtoencode		String to encode
*	\param		htmlinfo			1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
*	\remarks	Selon qu'on compte l'afficher dans le PDF avec:
*					writeHTMLCell -> a besoin d'etre encodé en HTML
*					MultiCell -> ne doit pas etre encodé en HTML
*/
function dol_htmlentities($stringtoencode,$htmlinfo=-1)
{
	if ($htmlinfo == 1) return $stringtoencode;
	if ($htmlinfo == 0) return htmlentities($stringtoencode);
	if ($htmlinfo == -1)
	{
		if (dol_textishtml($stringtoencode)) return $stringtoencode;
		else return htmlentities($stringtoencode);
	}
	return $stringtoencode;
}

/**
   \brief   Encode\decode le mot de passe de la base de données dans le fichier de conf
   \param   level    niveau d'encodage : 0 non encodé, 1 encodé
*/
function encodedecode_dbpassconf($level=0)
{
	$config = '';

	if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','r'))
	{
		while(!feof($fp))
		{
			$buffer = fgets($fp,4096);
			
			if (strstr($buffer,"\$dolibarr_main_db_encrypted_pass") && $level == 0)
			{
				$passwd = strstr($buffer,"$dolibarr_main_db_encrypted_pass=");
				$passwd = substr(substr($passwd,2),0,-3);
				$passwd = dolibarr_decode($passwd);
				$config .= "\$dolibarr_main_db_pass=\"$passwd\";\n";
			}
			else if (strstr($buffer,"\$dolibarr_main_db_pass") && $level == 1)
			{
				$passwd = strstr($buffer,"$dolibarr_main_db_pass=");
				$passwd = substr(substr($passwd,2),0,-3);
				$passwd = dolibarr_encode($passwd);
				$config .= "\$dolibarr_main_db_encrypted_pass=\"$passwd\";\n";
			}
			else
			{
				$config .= $buffer;
			}
		}
		fclose($fp);
		
		if ($fp = @fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','w'))
		{
			fputs($fp, $config, strlen($config));
			fclose($fp);
			return 1;
		}
		else
		{
			return -1;
		}
	}
	else
	{
		return -2;
	}
}

/**
   \brief   Encode une chaine de caractére
   \param   chain    chaine de caractéres a encoder
   \return  string_coded  chaine de caractéres encodée
*/
function dolibarr_encode($chain)
{
        for($i=0;$i<strlen($chain);$i++)
        {
        	$output_tab[$i] = chr(ord(substr($chain,$i,1))+17);
        }
        
        $string_coded = base64_encode(implode ("",$output_tab));
        return $string_coded;
}

/**
   \brief   Decode une chaine de caractére
   \param   chain    chaine de caractéres a decoder
   \return  string_coded  chaine de caractéres decodée
*/
function dolibarr_decode($chain)
{
        $chain = base64_decode($chain);
        
        for($i=0;$i<strlen($chain);$i++)
        {
        	$output_tab[$i] = chr(ord(substr($chain,$i,1))-17);
        }
        
        $string_decoded = implode ("",$output_tab);
        return $string_decoded;
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
   \param	    timestampStart      Timestamp de début
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
   \brief     Fonction retournant le nombre de jour entre deux dates sans les jours fériés (jours ouvrés)
   \param	    timestampStart      Timestamp de début
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
   \brief     Fonction retournant le nombre de lignes dans un texte formaté
   \param	    texte      Texte
   \return    nblines    Nombre de lignes
*/
function num_lines($texte)
{
	$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " "); 
	$texte = strtr($texte, $repTable);
	$pattern = '/(<[^>]+>)/Uu';
	$a = preg_split($pattern, $texte, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$nblines = ((count($a)+1)/2);
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
 *    \brief      Effectue les substitutions des mots clés par les données en fonction du tableau
 *    \param      chaine      			Chaine dans laquelle faire les substitutions
 *    \param      substitutionarray		Tableau clé substitution => valeur a mettre
 *    \return     string      			Chaine avec les substitutions effectuées
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
 *    \brief      Convertit une variable php en variable javascript
 *    \param      var      			variable php
 *    \return     result        variable javascript      	
 */
 function php2js($var)
 {
 	if (is_array($var))
 	{
    $array = array();
    foreach ($var as $a_var)
    {
    	$array[] = php2js($a_var);
    }
    $result = "[" . join(",", $array) . "]";
    return $result;
  }
  else if (is_bool($var))
  {
  	$result = $var ? "true" : "false";
  	return $result;
  }
  else if (is_int($var) || is_integer($var) || is_double($var) || is_float($var))
  {
  	$result = $var;
  	return $result;
  }
  else if (is_string($var))
  {
  	$result = "\"" . addslashes(stripslashes($var)) . "\"";
  	return $result;
  }
  // autres cas: objets, on ne les gére pas
  $result = FALSE;
  return $result;
}

/*
 *    \brief      Formate l'affichage de date de début et de fin
 *    \param      date_start    date de début
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
 *    \brief     Création de 2 vignettes a partir d'un fichier image (une small et un mini)
 *    \brief     Les extension prise en compte sont jpg et png
 *    \param     file           Chemin du fichier image a redimensionner
 *    \param     maxWidth       Largeur maximum que dois faire la miniature (160 par défaut)
 *    \param     maxHeight      Hauteur maximum que dois faire l'image (120 par défaut)
 *    \param     extName        Extension pour différencier le nom de la vignette
 *    \param     quality        Qualité de compression (0=worst, 100=best)
 *    \return    string			Chemin de la vignette
 */
function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName='_small', $quality=50)
{
	global $langs;

	dolibarr_syslog("functions.inc::vignette file=".$file." extName=".$extName." maxWidth=".$maxWidth." maxHeight=".$maxHeight." quality=".$quality);
	
	// Nettoyage parametres
	$file=trim($file);

	// Vérification des paramétres
	if (! $file)
	{
		// Si le fichier n'a pas été indiqué
		return 'Bad parameter file';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passé en paramétre n'existe pas
		return $langs->trans("ErrorFileNotFound",$file);
	}
	elseif(image_format_supported($file) < 0)
	{
		return 'This file '.$file.' does not seem to be an image format file name.';
	}
	elseif(!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < 0){
		// Si la largeur max est incorrecte (n'est pas numérique, est vide, ou est inférieure a 0)
		return 'Valeur de la largeur incorrecte.';
	}
	elseif(!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < 0){
		// Si la hauteur max est incorrecte (n'est pas numérique, est vide, ou est inférieure a 0)
		return 'Valeur de la hauteur incorrecte.';
	}

	$fichier = realpath($file); // Chemin canonique absolu de l'image
	$dir = dirname($file).'/'; // Chemin du dossier contenant l'image
	$dirthumb = $dir.'thumbs/'; // Chemin du dossier contenant les vignettes

	$infoImg = getimagesize($fichier); // Récupération des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	// Si l'image est plus petite que la largeur et la hauteur max, on ne crée pas de vignette
	if ($infoImg[0] < $maxWidth && $infoImg[1] < $maxHeight)
	{
		// On cree toujours les vignettes
		dolibarr_syslog("File size is smaller than thumb size",LOG_DEBUG);
		//return 'Le fichier '.$file.' ne nécessite pas de création de vignette';
	}

	$imgfonction='';
	switch($infoImg[2])
	{
		case 1:	// IMG_GIF
			$imgfonction = 'imagecreatefromgif';
			break;
		case 2:	// IMG_JPG
			$imgfonction = 'imagecreatefromjpeg';
			break;
		case 3:	// IMG_PNG
			$imgfonction = 'imagecreatefrompng';
			break;
		case 4:	// IMG_WBMP
			$imgfonction = 'imagecreatefromwbmp';
			break;
	}
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 'Creation de vignette impossible. Ce PHP ne supporte pas les fonctions du module GD '.$imgfonction;
		}
	}

	// On crée le répertoire contenant les vignettes
	if (! file_exists($dirthumb))
	{
		create_exdir($dirthumb);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif'; // Extension de l'image
			$newquality='NU';
			break;
		case 2:	// Jpg
			$img = imagecreatefromjpeg($fichier);
			$extImg = '.jpg'; // Extension de l'image
			$newquality=$quality;
			break;
		case 3:	// Png
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			$newquality=$quality-100;
			$newquality=round(abs($quality-100)*9/100);
			break;
		case 4:	// Bmp
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			$newquality='NU';
			break;
	}

	// Initialisation des dimensions de la vignette si elles sont supérieures a l'original
	if($maxWidth > $imgWidth){ $maxWidth = $imgWidth; }
	if($maxHeight > $imgHeight){ $maxHeight = $imgHeight; }

	$whFact = $maxWidth/$maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
	$imgWhFact = $imgWidth/$imgHeight; // Facteur largeur/hauteur de l'original

	// Fixe les dimensions de la vignette
	if($whFact < $imgWhFact){
		// Si largeur déterminante
		$thumbWidth  = $maxWidth;
		$thumbHeight = $thumbWidth / $imgWhFact;
	} else {
		// Si hauteur déterminante
		$thumbHeight = $maxHeight;
		$thumbWidth  = $thumbHeight * $imgWhFact;
	}
	$thumbHeight=round($thumbHeight);
	$thumbWidth=round($thumbWidth);
	
	// Create empty image
	if ($infoImg[2] == 1)
	{
		// Compatibilité image GIF
		$imgThumb = imagecreate($thumbWidth, $thumbHeight);
	}
	else
	{
		$imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias'))
	{
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha'))
	{
		imagesavealpha($imgThumb, true);
	}
	
	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procéde autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
			break;
		case 2:	// Jpg
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
		case 3:	// Png
			imagealphablending($imgThumb,false); // Pour compatibilité sur certain systéme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127);	// Keep transparent channel
			break;
		case 4:	// Bmp
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	dolibarr_syslog("vignette: convert image from ($imgWidth x $imgHeight) to ($thumbWidth x $thumbHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insére l'image de base redimensionnée
	imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insére l'image de base redimensionnée

	$fileName = eregi_replace('(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$','',$file);	// On enleve extension quelquesoit la casse
	$fileName = basename($fileName);
	$imgThumbName = $dirthumb.$fileName.$extName.$extImg; // Chemin complet du fichier de la vignette

	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch($infoImg[2])
	{
		case 1:	// Gif
			imagegif($imgThumb, $imgThumbName);
			break;
		case 2:	// Jpg
			imagejpeg($imgThumb, $imgThumbName, $newquality);
			break;
		case 3:	// Png
			imagepng($imgThumb, $imgThumbName, $newquality);
			break;
		case 4:	// Bmp
			image2wmp($imgThumb, $imgThumbName);
			break;
	}

	// Free memory
	imagedestroy($imgThumb);

	return $imgThumbName;
}

/*
 *    \brief      Converti les heures et minutes en secondes
 *    \param      iHours      Heures
 *    \param      iMinutes    Minutes
 *    \param      iSeconds    Secondes
 *    \return     iResult	    Temps en secondes
 */
function ConvertTime2Seconds($iHours=0,$iMinutes=0,$iSeconds=0)
{
	$iResult=($iHours*3600)+($iMinutes*60)+$iSeconds;
	return $iResult;
}

/*
 *    \brief      Converti les secondes en heures et minutes
 *    \param      iSecond     Nombre de secondes
 *    \param      format      Choix de l'affichage (all:affichage complet, hour: n'affiche que les heures, min: n'affiche que les minutes)
 *    \return     sTime       Temps formaté 	
 */
function ConvertSecondToTime($iSecond,$format='all'){
	if ($format == 'all'){
		$sTime=date("H",$iSecond)-1;
		$sTime.='h'.date("i",$iSecond);
	}else if ($format == 'hour'){
		$sTime=date("H",$iSecond)-1;
	}else if ($format == 'min'){
		$sTime=date("i",$iSecond);
	}
	return $sTime;
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
   \brief   Retourne un tableau des mois ou le mois sélectionné
   \param   selected			Mois à sélectionner ou -1
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

?>
