<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 *  \file		htdocs/lib/date.lib.php
 *  \brief		Ensemble de fonctions de base de dolibarr sous forme d'include
 *  \version	$Id$
 */


/**
 *  Add a delay to a date
 *  @param      time                Date timestamp ou au format YYYY-MM-DD
 *  @param      duration_value      Value of delay to add
 *  @param      duration_unit       Unit of added delay (d, m, y)
 *  @return     int                 New timestamp
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


/**   Converti les heures et minutes en secondes
 *    @param      iHours      Heures
 *    @param      iMinutes    Minutes
 *    @param      iSeconds    Secondes
 *    @return     iResult	    Temps en secondes
 */
function ConvertTime2Seconds($iHours=0,$iMinutes=0,$iSeconds=0)
{
	$iResult=($iHours*3600)+($iMinutes*60)+$iSeconds;
	return $iResult;
}


/**	  	Return, in clear text, value of a number of seconds in days, hours and minutes
 *    	@param      iSecond		Number of seconds
 *    	@param      format		Output format (all: complete display, hour: displays only hours, min: displays only minutes)
 *    	@param      lengthOfDay	Length of day (default 86400 seconds)
 *    	@return     sTime		Formated text of duration
 * 	                            Example: 0 return 00:00, 3600 return 1:00, 86400 return 1d, 90000 return 1 Day 01:00
 */
function ConvertSecondToTime($iSecond,$format='all',$lengthOfDay=86400)
{
	global $langs;

	if (empty($lengthOfDay)) $lengthOfDay = 86400;

	if ($format == 'all')
	{
		if ($iSecond === 0) return '0';	// This is to avoid having 0 return a 12:00 AM for en_US

		$sDay=0;
		if ($iSecond >= $lengthOfDay)
		{
			for($i = $iSecond; $i >= $lengthOfDay; $i -= $lengthOfDay )
			{
				$sDay++;
				$iSecond-=$lengthOfDay;
			}
			$dayTranslate = $langs->trans("Day");
			if ($iSecond >= ($lengthOfDay*2)) $dayTranslate = $langs->trans("Days");
		}
		$sTime='';
		if ($sDay) $sTime.=$sDay.' '.$dayTranslate.' ';
		if ($iSecond || empty($sDay))
		{
			$sTime.= dol_print_date($iSecond,'hourduration',true);
		}
	}
	else if ($format == 'hour')
	{
		$sTime=dol_print_date($iSecond,'%H',true);
	}
	else if ($format == 'min')
	{
		$sTime=dol_print_date($iSecond,'%M',true);
	}
	return trim($sTime);
}


/**	Return previous month
 *	@param		month	Month
 *	@param		year	Year
 *	@return		array	Previous year,month
 */
function dol_get_prev_month($month, $year)
{
	if ($month == 1)
	{
		$prev_month = 12;
		$prev_year  = $year - 1;
	}
	else
	{
		$prev_month = $month-1;
		$prev_year  = $year;
	}
	return array('year' => $prev_year, 'month' => $prev_month);
}

/**	Return next month
 *	@param		month	Month
 *	@param		year	Year
 *	@return		array	Next year,month
 */
function dol_get_next_month($month, $year)
{
	if ($month == 12)
	{
		$next_month = 1;
		$next_year  = $year + 1;
	}
	else
	{
		$next_month = $month + 1;
		$next_year  = $year;
	}
	return array('year' => $next_year, 'month' => $next_month);
}


/**	Return GMT time for first day of a month or year
 *	@param		year		Year
 * 	@param		month		Month
 * 	@param		gm			False = Return date to compare with server TZ, True to compare with GM date.
 *                          Exemple: dol_get_first_day(1970,1,false) will return -3600 with TZ+1, after a dol_print_date will return 1970-01-01 00:00:00
 *                          Exemple: dol_get_first_day(1970,1,true) will return 0 whatever is TZ, after a dol_print_date will return 1970-01-01 00:00:00
 *  @return		Timestamp	Date for first day
 */
function dol_get_first_day($year,$month=1,$gm=false)
{
	return dol_mktime(0,0,0,$month,1,$year,$gm);
}


/**	Return GMT time for last day of a month or year
 *	@param		year		Year
 * 	@param		month		Month
 * 	@param		gm			False = Return date to compare with server TZ, True to compare with GM date.
 *	@return		Timestamp	Date for first day
 */
function dol_get_last_day($year,$month=12,$gm=false)
{
	if ($month == 12)
	{
		$month = 1;
		$year += 1;
	}
	else
	{
		$month += 1;
	}

	// On se deplace au debut du mois suivant, et on retire un jour
	$datelim=dol_mktime(23,59,59,$month,1,$year,$gm);
	$datelim -= (3600 * 24);

	return $datelim;
}


/**
 *	Fonction retournant le nombre de jour fieries samedis et dimanches entre 2 dates entrees en timestamp
 *	@remarks	Called by function num_open_day
 *	@param	    timestampStart      Timestamp de debut
 *	@param	    timestampEnd        Timestamp de fin
 *	@return   	nbFerie             Nombre de jours feries
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
 *	Fonction retournant le nombre de jour entre deux dates
 *	@param	   timestampStart      Timestamp de debut
 *	@param	   timestampEnd        Timestamp de fin
 *	@param     lastday             On prend en compte le dernier jour, 0: non, 1:oui
 *	@return    nbjours             Nombre de jours
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
 *	Fonction retournant le nombre de jour entre deux dates sans les jours feries (jours ouvres)
 *	@param	   timestampStart      Timestamp de debut
 *	@param	   timestampEnd        Timestamp de fin
 *	@param     inhour              0: sort le nombre de jour , 1: sort le nombre d'heure (72 max)
 *	@param     lastday             On prend en compte le dernier jour, 0: non, 1:oui
 *	@return    nbjours             Nombre de jours ou d'heures
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

?>