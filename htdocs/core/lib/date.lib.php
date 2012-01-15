<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file		htdocs/core/lib/date.lib.php
 *  \brief		Set of function to manipulate dates
 */


/**
 *  Return an array with timezone values
 *
 *  @return     array   Array with timezone values
 */
function get_tz_array()
{
    $tzarray=array( -11=>"Pacific/Midway",
                    -10=>"Pacific/Fakaofo",
                    -9=>"America/Anchorage",
                    -8=>"America/Los_Angeles",
                    -7=>"America/Dawson_Creek",
                    -6=>"America/Chicago",
                    -5=>"America/Bogota",
                    -4=>"America/Anguilla",
                    -3=>"America/Araguaina",
                    -2=>"America/Noronha",
                    -1=>"Atlantic/Azores",
                    0=>"Africa/Abidjan",
                    1=>"Europe/Paris",
                    2=>"Europe/Helsinki",
                    3=>"Europe/Moscow",
                    4=>"Asia/Dubai",
                    5=>"Asia/Karachi",
                    6=>"Indian/Chagos",
                    7=>"Asia/Jakarta",
                    8=>"Asia/Hong_Kong",
                    9=>"Asia/Tokyo",
                    10=>"Australia/Sydney",
                    11=>"Pacific/Noumea",
                    12=>"Pacific/Auckland",
                    13=>"Pacific/Enderbury"
                    );
    return $tzarray;
}


/**
 * Return server timezone
 *
 * @return string	TimeZone
 */
function getCurrentTimeZone()
{
    // Method 1
    //$tzstring=date_default_timezone_get(); // Then convert into tz

    // Method 2
    $tmp=dol_mktime(0,0,0,1,1,1970);
    $tz=($tmp<0?'+':'-').sprintf("%02d",abs($tmp/3600));
    return $tz;
}


/**
 *  Add a delay to a date
 *
 *  @param      timestamp	$time               Date timestamp (or string with format YYYY-MM-DD)
 *  @param      int			$duration_value     Value of delay to add
 *  @param      int			$duration_unit      Unit of added delay (d, m, y)
 *  @return     timestamp      			        New timestamp
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
 *
 *    @param      int		$iHours      Heures
 *    @param      int		$iMinutes    Minutes
 *    @param      int		$iSeconds    Secondes
 *    @return     int		$iResult	 Temps en secondes
 */
function ConvertTime2Seconds($iHours=0,$iMinutes=0,$iSeconds=0)
{
	$iResult=($iHours*3600)+($iMinutes*60)+$iSeconds;
	return $iResult;
}


/**	  	Return, in clear text, value of a number of seconds in days, hours and minutes
 *
 *    	@param      int		$iSecond		Number of seconds
 *    	@param      string	$format		    Output format (all: complete display, hour: displays only hours, min: displays only minutes, sec: displays only seconds, month: display month only, year: displays only year);
 *      @param      int		$lengthOfDay    Length of day (default 86400 seconds for 1 day, 28800 for 8 hour)
 *      @param      int		$lengthOfWeek   Length of week (default 7)
 *    	@return     sTime		 		 	Formated text of duration
 * 	                                		Example: 0 return 00:00, 3600 return 1:00, 86400 return 1d, 90000 return 1 Day 01:00
 */
function ConvertSecondToTime($iSecond,$format='all',$lengthOfDay=86400,$lengthOfWeek=7)
{
	global $langs;

	if (empty($lengthOfDay))  $lengthOfDay = 86400;         // 1 day = 24 hours
    if (empty($lengthOfWeek)) $lengthOfWeek = 7;            // 1 week = 7 days

	if ($format == 'all')
	{
		if ($iSecond === 0) return '0';	// This is to avoid having 0 return a 12:00 AM for en_US

        $sTime='';
		$sDay=0;
        $sWeek='';

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

		if ($lengthOfWeek < 7)
		{
        	if ($sDay)
            {
                if ($sDay >= $lengthOfWeek)
                {
                    $sWeek = (int) (($sDay - $sDay % $lengthOfWeek ) / $lengthOfWeek);
                    $sDay = $sDay % $lengthOfWeek;
                    $weekTranslate = $langs->trans("DurationWeek");
                    if ($sWeek >= 2) $weekTranslate = $langs->trans("DurationWeeks");
                    $sTime.=$sWeek.' '.$weekTranslate.' ';
                }
                if ($sDay>0)
                {
                    $dayTranslate = $langs->trans("Day");
                    if ($sDay > 1) $dayTranslate = $langs->trans("Days");
                    $sTime.=$sDay.' '.$dayTranslate.' ';
                }
            }
		}

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
    else if ($format == 'sec')
    {
        $sTime=dol_print_date($iSecond,'%S',true);
    }
    else if ($format == 'month')
    {
        $sTime=dol_print_date($iSecond,'%m',true);
    }
    else if ($format == 'year')
    {
        $sTime=dol_print_date($iSecond,'%Y',true);
    }
    return trim($sTime);
}


/** Return previous day
 *
 *  @param      int			$day     	Day
 *  @param      int			$month   	Month
 *  @param      int			$year    	Year
 *  @return     array   				Previous year,month,day
 */
function dol_get_prev_day($day, $month, $year)
{
	$time=dol_mktime(12,0,0,$month,$day,$year,1,0);
	$time-=24*60*60;
	$tmparray=dol_getdate($time,true);
	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/** Return next day
 *
 *  @param      int			$day    	Day
 *  @param      int			$month  	Month
 *  @param      int			$year   	Year
 *  @return     array   				Next year,month,day
 */
function dol_get_next_day($day, $month, $year)
{
	$time=dol_mktime(12,0,0,$month,$day,$year,1,0);
	$time+=24*60*60;
	$tmparray=dol_getdate($time,true);
	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**	Return previous month
 *
 *	@param		int			$month		Month
 *	@param		int			$year		Year
 *	@return		array					Previous year,month
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
 *
 *	@param		int			$month		Month
 *	@param		int			$year		Year
 *	@return		array					Next year,month
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

/**	Return previous week
 *
 *  @param      int			$day     	Day
 *  @param      int			$week    	Week
 *  @param      int			$month   	Month
 *	@param		int			$year		Year
 *	@return		array					Previous year,month,day
 */
function dol_get_prev_week($day, $week, $month, $year)
{
	$tmparray = dol_get_first_day_week($day, $month, $year);

	$time=dol_mktime(12,0,0,$month,$tmparray['first_day'],$year,1,0);
	$time-=24*60*60*7;
	$tmparray=dol_getdate($time,true);
	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**	Return next week
 *
 *  @param      int			$day     	Day
 *  @param      int			$week    	Week
 *  @param      int			$month   	Month
 *	@param		int			$year		Year
 *	@return		array					Next year,month,day
 */
function dol_get_next_week($day, $week, $month, $year)
{
	$tmparray = dol_get_first_day_week($day, $month, $year);

	$time=dol_mktime(12,0,0,$month,$tmparray['first_day'],$year,1,0);
	$time+=24*60*60*7;
	$tmparray=dol_getdate($time,true);

	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);

}

/**	Return GMT time for first day of a month or year
 *
 *	@param		int			$year		Year
 * 	@param		int			$month		Month
 * 	@param		boolean		$gm			False = Return date to compare with server TZ, True to compare with GM date.
 *                          			Exemple: dol_get_first_day(1970,1,false) will return -3600 with TZ+1, after a dol_print_date will return 1970-01-01 00:00:00
 *                          			Exemple: dol_get_first_day(1970,1,true) will return 0 whatever is TZ, after a dol_print_date will return 1970-01-01 00:00:00
 *  @return		timestamp				Date for first day
 */
function dol_get_first_day($year,$month=1,$gm=false)
{
	return dol_mktime(0,0,0,$month,1,$year,$gm);
}


/**	Return GMT time for last day of a month or year
 *
 *	@param		int			$year		Year
 * 	@param		int			$month		Month
 * 	@param		boolean		$gm			False = Return date to compare with server TZ, True to compare with GM date.
 *	@return		timestamp				Date for first day
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

/**	Return first day of week for a date
 *
 *	@param		int		$day		Day
 * 	@param		int		$month		Month
 *  @param		int		$year		Year
 * 	@param		int		$gm			False = Return date to compare with server TZ, True to compare with GM date.
 *	@return		array				year,month, week,first_day,prev_year,prev_month,prev_day
 */
function dol_get_first_day_week($day,$month,$year,$gm=false)
{
	global $conf;

	$date = dol_mktime(0,0,0,$month,$day,$year,$gm);

	//Checking conf of start week
	$start_week = (isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1);

	$tmparray = dol_getdate($date,true);

	//Calculate days to count
	$days = $start_week - $tmparray['wday'];
 	if ($days>=1) $days=7-$days;
 	$days = abs($days);
    $seconds = $days*24*60*60;

    //Get first day of week
    $tmpday = date($tmparray[0])-$seconds;
	$tmpday = date("d",$tmpday);

	//Check first day of week is form this month or not
	if ($tmpday>$day)
    {
    	$prev_month = $month-1;
		$prev_year  = $year;

    	if ($prev_month==0)
    	{
    		$prev_month = 12;
    		$prev_year  = $year-1;
    	}
    }
    else
    {
    	$prev_month = $month;
		$prev_year  = $year;
    }

    //Get first day of next week
	$tmptime=dol_mktime(12,0,0,$month,$tmpday,$year,1,0);
	$tmptime-=24*60*60*7;
	$tmparray=dol_getdate($tmptime,true);
    $prev_day   = $tmparray['mday'];

    //Check first day of week is form this month or not
	if ($prev_day>$tmpday)
    {
    	$prev_month = $month-1;
		$prev_year  = $year;

    	if ($prev_month==0)
    	{
    		$prev_month = 12;
    		$prev_year  = $year-1;
    	}
    }

    $week = date("W",dol_mktime(0,0,0,$month,$tmpday,$year,$gm));

	return array('year' => $year, 'month' => $month, 'week' => $week, 'first_day' => $tmpday, 'prev_year' => $prev_year, 'prev_month' => $prev_month, 'prev_day' => $prev_day);
}

/**
 *	Fonction retournant le nombre de jour fieries samedis et dimanches entre 2 dates entrees en timestamp
 *	Called by function num_open_day
 *
 *	@param	    timestamp	$timestampStart     Timestamp de debut
 *	@param	    timestamp	$timestampEnd       Timestamp de fin
 *  @param      string		$countrycode        Country code
 *	@return   	int								Nombre de jours feries
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
			$date_ascension = mktime(
                date("H", $date_paques),
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
			$date_pentecote = mktime(
                date("H", $date_ascension),
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

		// Pentecoste and Ascensione in Italy go to the sunday after: isn't holiday.
		// Pentecoste is 50 days after Easter, Ascensione 40
		if ($countrycode == 'IT')
		{
			$countryfound=1;

			// Definition des dates feriees fixes
			if($jour == 1 && $mois == 1) $ferie=true; // Capodanno
			if($jour == 6 && $mois == 1) $ferie=true; // Epifania
			if($jour == 25 && $mois == 4) $ferie=true; // Anniversario Liberazione
			if($jour == 1 && $mois == 5) $ferie=true; // Festa del Lavoro
			if($jour == 2 && $mois == 6) $ferie=true; // Festa della Repubblica
			if($jour == 15 && $mois == 8) $ferie=true; // Ferragosto
			if($jour == 1 && $mois == 11) $ferie=true; // Tutti i Santi
			if($jour == 8 && $mois == 12) $ferie=true; // Immacolata Concezione
			if($jour == 25 && $mois == 12) $ferie=true; // 25 decembre
			if($jour == 26 && $mois == 12) $ferie=true; // Santo Stefano

			// Calcul du jour de paques
			$date_paques = easter_date($annee);
			$jour_paques = date("d", $date_paques);
			$mois_paques = date("m", $date_paques);
			if($jour_paques == $jour && $mois_paques == $mois) $ferie=true;
			// Paques

			// Calul des samedis et dimanches
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if($jour_semaine == 0 || $jour_semaine == 6) $ferie=true;
			//Samedi (6) et dimanche (0)
		}

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
 *
 *	@param	   timestamp	$timestampStart     Timestamp de debut
 *	@param	   timestamp	$timestampEnd       Timestamp de fin
 *	@param     int			$lastday            On prend en compte le dernier jour, 0: non, 1:oui
 *	@return    int								Nombre de jours
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
 *
 *	@param	   timestamp	$timestampStart     Timestamp de debut
 *	@param	   timestamp	$timestampEnd       Timestamp de fin
 *	@param     int			$inhour             0: sort le nombre de jour , 1: sort le nombre d'heure (72 max)
 *	@param     int			$lastday            On prend en compte le dernier jour, 0: non, 1:oui
 *	@return    int								Nombre de jours ou d'heures
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
 *	Return array of translated months or selected month
 *
 *	@param   int		$selected		-1 to return array of all months or motnh to select
 *	@return  mixed						Month string or array if selected < 0
 */
function monthArrayOrSelected($selected=0)
{
    global $langs;

    $month = array (
    1  => $langs->trans("January"),
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
                break;
            }
        }
        return $return;
    }
    else
    {
        return $month;
    }
}

?>