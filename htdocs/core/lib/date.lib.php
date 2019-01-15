<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Charlene Benke       <charlie@patas-monkey.com>
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
    $tzarray=array(
        -11=>"Pacific/Midway",
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
 * Return server timezone string
 *
 * @return string			PHP server timezone string ('Europe/Paris')
 */
function getServerTimeZoneString()
{
    return @date_default_timezone_get();
}

/**
 * Return server timezone int.
 *
 * @param	string	$refgmtdate		Reference period for timezone (timezone differs on winter and summer. May be 'now', 'winter' or 'summer')
 * @return 	int						An offset in hour (+1 for Europe/Paris on winter and +2 for Europe/Paris on summer)
 */
function getServerTimeZoneInt($refgmtdate='now')
{
    global $conf;
    if (method_exists('DateTimeZone','getOffset'))
    {
        // Method 1 (include daylight)
        $gmtnow=dol_now('gmt'); $yearref=dol_print_date($gmtnow,'%Y'); $monthref=dol_print_date($gmtnow,'%m'); $dayref=dol_print_date($gmtnow,'%d');
        if ($refgmtdate == 'now') $newrefgmtdate=$yearref.'-'.$monthref.'-'.$dayref;
        elseif ($refgmtdate == 'summer') $newrefgmtdate=$yearref.'-08-01';
        else $newrefgmtdate=$yearref.'-01-01';
        $newrefgmtdate.='T00:00:00+00:00';
        $localtz = new DateTimeZone(getServerTimeZoneString());
        $localdt = new DateTime($newrefgmtdate, $localtz);
        $tmp=-1*$localtz->getOffset($localdt);
        //print $refgmtdate.'='.$tmp;
    }
    else
    {
    	$tmp=0;
    	dol_print_error('','PHP version must be 5.3+');
    }
    $tz=round(($tmp<0?1:-1)*abs($tmp/3600));
    return $tz;
}


/**
 *  Add a delay to a date
 *
 *  @param      int			$time               Date timestamp (or string with format YYYY-MM-DD)
 *  @param      int			$duration_value     Value of delay to add
 *  @param      int			$duration_unit      Unit of added delay (d, m, y, w, h)
 *  @return     int      			        	New timestamp
 */
function dol_time_plus_duree($time, $duration_value, $duration_unit)
{
	global $conf;

	if ($duration_value == 0)  return $time;
	if ($duration_unit == 'h') return $time + (3600*$duration_value);
	if ($duration_unit == 'w') return $time + (3600*24*7*$duration_value);

	$deltastring='P';

	if ($duration_value > 0){ $deltastring.=abs($duration_value); $sub= false; }
	if ($duration_value < 0){ $deltastring.=abs($duration_value); $sub= true; }
	if ($duration_unit == 'd') { $deltastring.="D"; }
	if ($duration_unit == 'm') { $deltastring.="M"; }
	if ($duration_unit == 'y') { $deltastring.="Y"; }

	$date = new DateTime();
	if (! empty($conf->global->MAIN_DATE_IN_MEMORY_ARE_GMT)) $date->setTimezone(new DateTimeZone('UTC'));
	$date->setTimestamp($time);
	$interval = new DateInterval($deltastring);

	if($sub) $date->sub($interval);
	else $date->add( $interval );

	return $date->getTimestamp();
}


/**
 * Convert hours and minutes into seconds
 *
 * @param      int		$iHours     	Hours
 * @param      int		$iMinutes   	Minutes
 * @param      int		$iSeconds   	Seconds
 * @return     int						Time into seconds
 * @see convertSecondToTime
 */
function convertTime2Seconds($iHours=0, $iMinutes=0, $iSeconds=0)
{
	$iResult=($iHours*3600)+($iMinutes*60)+$iSeconds;
	return $iResult;
}


/**	  	Return, in clear text, value of a number of seconds in days, hours and minutes.
 *      Can be used to show a duration.
 *
 *    	@param      int		$iSecond		Number of seconds
 *    	@param      string	$format		    Output format ('all': total delay days hour:min like "2 days 12:30",
 *                                          - 'allwithouthour': total delay days without hour part like "2 days",
 *                                          - 'allhourmin': total delay with format hours:min like "60:30",
 *                                          - 'allhour': total delay hours without min/sec like "60:30",
 *                                          - 'fullhour': total delay hour decimal like "60.5" for 60:30,
 *                                          - 'hour': only hours part "12",
 *                                          - 'min': only minutes part "30",
 *                                          - 'sec': only seconds part,
 *                                          - 'month': only month part,
 *                                          - 'year': only year part);
 *      @param      int		$lengthOfDay    Length of day (default 86400 seconds for 1 day, 28800 for 8 hour)
 *      @param      int		$lengthOfWeek   Length of week (default 7)
 *    	@return     string		 		 	Formated text of duration
 * 	                                		Example: 0 return 00:00, 3600 return 1:00, 86400 return 1d, 90000 return 1 Day 01:00
 *      @see convertTime2Seconds
 */
function convertSecondToTime($iSecond, $format='all', $lengthOfDay=86400, $lengthOfWeek=7)
{
	global $langs;

	if (empty($lengthOfDay))  $lengthOfDay = 86400;         // 1 day = 24 hours
    if (empty($lengthOfWeek)) $lengthOfWeek = 7;            // 1 week = 7 days

	if ($format == 'all' || $format == 'allwithouthour' || $format == 'allhour' || $format == 'allhourmin')
	{
		if ((int) $iSecond === 0) return '0';	// This is to avoid having 0 return a 12:00 AM for en_US

        $sTime='';
        $sDay=0;
        $sWeek=0;

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
            }
		}
		if ($sDay>0)
		{
			$dayTranslate = $langs->trans("Day");
			if ($sDay > 1) $dayTranslate = $langs->trans("Days");
			$sTime.=$sDay.' '.$dayTranslate.' ';
		}

		if ($format == 'all')
		{
			if ($iSecond || empty($sDay))
			{
				$sTime.= dol_print_date($iSecond,'hourduration',true);
			}
		}
		if ($format == 'allhourmin')
		{
		    return sprintf("%02d",($sWeek*$lengthOfWeek*24 + $sDay*24 + (int) floor($iSecond/3600))).':'.sprintf("%02d",((int) floor(($iSecond % 3600)/60)));
		}
		if ($format == 'allhour')
		{
			return sprintf("%02d",($sWeek*$lengthOfWeek*24 + $sDay*24 + (int) floor($iSecond/3600)));
		}
	}
	else if ($format == 'hour')	// only hour part
	{
		$sTime=dol_print_date($iSecond,'%H',true);
	}
	else if ($format == 'fullhour')
	{
		if (!empty($iSecond)) {
			$iSecond=$iSecond/3600;
		}
		else {
			$iSecond=0;
		}
		$sTime=$iSecond;
	}
	else if ($format == 'min')	// only min part
	{
		$sTime=dol_print_date($iSecond,'%M',true);
	}
    else if ($format == 'sec')	// only sec part
    {
        $sTime=dol_print_date($iSecond,'%S',true);
    }
    else if ($format == 'month')	// only month part
    {
        $sTime=dol_print_date($iSecond,'%m',true);
    }
    else if ($format == 'year')	// only year part
    {
        $sTime=dol_print_date($iSecond,'%Y',true);
    }
    return trim($sTime);
}


/**
 * Generate a SQL string to make a filter into a range (for second of date until last second of date)
 *
 * @param      string	$datefield		Name of SQL field where apply sql date filter
 * @param      int		$day_date		Day date
 * @param      int		$month_date		Month date
 * @param      int		$year_date		Year date
 * @return     string	$sqldate		String with SQL filter
 */
function dolSqlDateFilter($datefield, $day_date, $month_date, $year_date)
{
	global $db;
	$sqldate="";
	if ($month_date > 0) {
		if ($year_date > 0 && empty($day_date)) {
			$sqldate.= " AND ".$datefield." BETWEEN '".$db->idate(dol_get_first_day($year_date, $month_date, false));
			$sqldate.= "' AND '".$db->idate(dol_get_last_day($year_date, $month_date, false))."'";
		} else if ($year_date > 0 && ! empty($day_date)) {
			$sqldate.= " AND ".$datefield." BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_date, $day_date, $year_date));
			$sqldate.= "' AND '".$db->idate(dol_mktime(23, 59, 59, $month_date, $day_date, $year_date))."'";
		} else
			$sqldate.= " AND date_format( ".$datefield.", '%m') = '".$db->escape($month_date)."'";
	} else if ($year_date > 0){
		$sqldate.= " AND ".$datefield." BETWEEN '".$db->idate(dol_get_first_day($year_date, 1, false));
		$sqldate.= "' AND '".$db->idate(dol_get_last_day($year_date, 12, false))."'";
	}
	return $sqldate;
}

/**
 *	Convert a string date into a GM Timestamps date
 *	Warning: YYYY-MM-DDTHH:MM:SS+02:00 (RFC3339) is not supported. If parameter gm is 1, we will use no TZ, if not we will use TZ of server, not the one inside string.
 *
 *	@param	string	$string		Date in a string
 *				     	        YYYYMMDD
 *	                 			YYYYMMDDHHMMSS
 *								YYYYMMDDTHHMMSSZ
 *								YYYY-MM-DDTHH:MM:SSZ (RFC3339)
 *		                		DD/MM/YY or DD/MM/YYYY (deprecated)
 *		                		DD/MM/YY HH:MM:SS or DD/MM/YYYY HH:MM:SS (deprecated)
 *  @param	int		$gm         1 =Input date is GM date,
 *                              0 =Input date is local date using PHP server timezone
 *  @return	int					Date as a timestamp
 *		                		19700101020000 -> 7200 with gm=1
 *
 *  @see    dol_print_date, dol_mktime, dol_getdate
 */
function dol_stringtotime($string, $gm=1)
{
    // Convert date with format DD/MM/YYY HH:MM:SS. This part of code should not be used.
    if (preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)\s?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i',$string,$reg))
    {
        dol_syslog("dol_stringtotime call to function with deprecated parameter format", LOG_WARNING);
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
    else if (
    	   preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})Z$/i',$string,$reg)	// Convert date with format YYYY-MM-DDTHH:MM:SSZ (RFC3339)
    	|| preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/i',$string,$reg)	// Convert date with format YYYY-MM-DD HH:MM:SS
   		|| preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2})([0-9]{2})([0-9]{2})Z$/i',$string,$reg)		// Convert date with format YYYYMMDDTHHMMSSZ
    )
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
    $date=dol_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4),($gm?1:0));
    return $date;
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

	$time=dol_mktime(12,0,0,$tmparray['first_month'],$tmparray['first_day'],$tmparray['first_year'],1,0);
	$time+=24*60*60*7;
	$tmparray=dol_getdate($time,true);

	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**	Return GMT time for first day of a month or year
 *
 *	@param		int			$year		Year
 * 	@param		int			$month		Month
 * 	@param		mixed		$gm			False or 0 or 'server' = Return date to compare with server TZ, True or 1 to compare with GM date.
 *                          			Exemple: dol_get_first_day(1970,1,false) will return -3600 with TZ+1, after a dol_print_date will return 1970-01-01 00:00:00
 *                          			Exemple: dol_get_first_day(1970,1,true) will return 0 whatever is TZ, after a dol_print_date will return 1970-01-01 00:00:00
 *  @return		int						Date for first day, '' if error
 */
function dol_get_first_day($year,$month=1,$gm=false)
{
	if ($year > 9999) return '';
	return dol_mktime(0,0,0,$month,1,$year,$gm);
}


/**	Return GMT time for last day of a month or year
 *
 *	@param		int			$year		Year
 * 	@param		int			$month		Month
 * 	@param		boolean		$gm			False or 0 or 'server' = Return date to compare with server TZ, True or 1 to compare with GM date.
 *	@return		int						Date for first day, '' if error
 */
function dol_get_last_day($year,$month=12,$gm=false)
{
	if ($year > 9999) return '';
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

/**	Return first day of week for a date. First day of week may be monday if option MAIN_START_WEEK is 1.
 *
 *	@param		int		$day		Day
 * 	@param		int		$month		Month
 *  @param		int		$year		Year
 * 	@param		int		$gm			False or 0 or 'server' = Return date to compare with server TZ, True or 1 to compare with GM date.
 *	@return		array				year,month,week,first_day,first_month,first_year,prev_day,prev_month,prev_year
 */
function dol_get_first_day_week($day,$month,$year,$gm=false)
{
	global $conf;

	//$day=2; $month=2; $year=2015;
	$date = dol_mktime(0,0,0,$month,$day,$year,$gm);

	//Checking conf of start week
	$start_week = (isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1);

	$tmparray = dol_getdate($date,true);	// detail of current day

	//Calculate days = offset from current day
	$days = $start_week - $tmparray['wday'];
 	if ($days>=1) $days=7-$days;
 	$days = abs($days);
    $seconds = $days*24*60*60;
	//print 'start_week='.$start_week.' tmparray[wday]='.$tmparray['wday'].' day offset='.$days.' seconds offset='.$seconds.'<br>';

    //Get first day of week
    $tmpdaytms = date($tmparray[0])-$seconds; // $tmparray[0] is day of parameters
	$tmpday = date("d",$tmpdaytms);

	//Check first day of week is in same month than current day or not
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
	$tmpmonth = $prev_month;
	$tmpyear = $prev_year;

	//Get first day of next week
	$tmptime=dol_mktime(12,0,0,$month,$tmpday,$year,1,0);
	$tmptime-=24*60*60*7;
	$tmparray=dol_getdate($tmptime,true);
    $prev_day   = $tmparray['mday'];

    //Check prev day of week is in same month than first day or not
	if ($prev_day > $tmpday)
    {
    	$prev_month = $month-1;
		$prev_year  = $year;

    	if ($prev_month==0)
    	{
    		$prev_month = 12;
    		$prev_year  = $year-1;
    	}
    }

    $week = date("W",dol_mktime(0,0,0,$tmpmonth,$tmpday,$tmpyear,$gm));

	return array('year' => $year, 'month' => $month, 'week' => $week, 'first_day' => $tmpday, 'first_month' => $tmpmonth, 'first_year' => $tmpyear, 'prev_year' => $prev_year, 'prev_month' => $prev_month, 'prev_day' => $prev_day);
}

/**
 *	Fonction retournant le nombre de jour feries, samedis et dimanches entre 2 dates entrees en timestamp. Dates must be UTC with hour, day, min to 0
 *	Called by function num_open_day
 *
 *	@param	    int			$timestampStart     Timestamp de debut
 *	@param	    int			$timestampEnd       Timestamp de fin
 *  @param      string		$countrycode        Country code
 *	@param      int			$lastday            Last day is included, 0: no, 1:yes
 *	@return   	int								Nombre de jours feries
 *  @see num_between_day, num_open_day
 */
function num_public_holiday($timestampStart, $timestampEnd, $countrycode='FR', $lastday=0)
{
	global $conf;

	$nbFerie = 0;

	// Check to ensure we use correct parameters
	if ((($timestampEnd - $timestampStart) % 86400) != 0) return 'ErrorDates must use same hours and must be GMT dates';

	$i=0;
	while (( ($lastday == 0 && $timestampStart < $timestampEnd) || ($lastday && $timestampStart <= $timestampEnd) )
	    && ($i < 50000))		// Loop end when equals (Test on i is a security loop to avoid infinite loop)
	{
		$ferie=false;
		$countryfound=0;
		$includesaturdayandsunday=1;

		$jour  = date("d", $timestampStart);
		$mois  = date("m", $timestampStart);
		$annee = date("Y", $timestampStart);


		// Check into var $conf->global->HOLIDAY_MORE_DAYS   MM-DD,YYYY-MM-DD, ...
		if (! empty($conf->global->HOLIDAY_MORE_PUBLIC_HOLIDAYS))
		{
			$arrayofdaystring=explode(',',$conf->global->HOLIDAY_MORE_PUBLIC_HOLIDAYS);
			foreach($arrayofdaystring as $daystring)
			{
				$tmp=explode('-',$daystring);
				if ($tmp[2])
				{
					if ($tmp[0] == $annee && $tmp[1] == $mois && $tmp[2] == $jour) $ferie=true;
				}
				else
				{
					if ($tmp[0] == $mois && $tmp[1] == $jour) $ferie=true;
				}
			}
		}

		if ($countrycode == 'FR')
		{
			$countryfound=1;

			// Definition of fixed working days
			if($jour == 1 && $mois == 1)   $ferie=true; // 1er january
			if($jour == 1 && $mois == 5)   $ferie=true; // 1er may
			if($jour == 8 && $mois == 5)   $ferie=true; // 5 may
			if($jour == 14 && $mois == 7)  $ferie=true; // 14 july
			if($jour == 15 && $mois == 8)  $ferie=true; // 15 august
			if($jour == 1 && $mois == 11)  $ferie=true; // 1 november
			if($jour == 11 && $mois == 11) $ferie=true; // 11 november
			if($jour == 25 && $mois == 12) $ferie=true; // 25 december

			// Calculation for easter date
			$date_paques = easter_date($annee);
			$jour_paques = date("d", $date_paques);
			$mois_paques = date("m", $date_paques);
			if($jour_paques == $jour && $mois_paques == $mois) $ferie=true;
			// Pâques

			// Calculation for the monday of easter date
            $date_lundi_paques = mktime(
                date("H", $date_paques),
                date("i", $date_paques),
                date("s", $date_paques),
                date("m", $date_paques),
                date("d", $date_paques) + 1,
                date("Y", $date_paques)
            );
			$jour_lundi_ascension = date("d", $date_lundi_paques);
			$mois_lundi_ascension = date("m", $date_lundi_paques);
			if($jour_lundi_ascension == $jour && $mois_lundi_ascension == $mois) $ferie=true;
			// Lundi de Pâques

			// Calcul du jour de l'ascension (38 days after easter day)
            $date_ascension = mktime(
                date("H", $date_paques),
                date("i", $date_paques),
                date("s", $date_paques),
                date("m", $date_paques),
                date("d", $date_paques) + 39,
                date("Y", $date_paques)
            );
			$jour_ascension = date("d", $date_ascension);
			$mois_ascension = date("m", $date_ascension);
			if($jour_ascension == $jour && $mois_ascension == $mois) $ferie=true;
			// Ascension

			// Calculation of "Pentecote" (11 days after easter day)
            $date_pentecote = mktime(
                date("H", $date_paques),
                date("i", $date_paques),
                date("s", $date_paques),
                date("m", $date_paques),
                date("d", $date_paques) + 49,
                date("Y", $date_paques)
            );
			$jour_pentecote = date("d", $date_pentecote);
			$mois_pentecote = date("m", $date_pentecote);
			if($jour_pentecote == $jour && $mois_pentecote == $mois) $ferie=true;
			// "Pentecote"
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
		}

		if ($countrycode == 'IN')
		{
			$countryfound=1;

			if($jour == 1 && $mois == 1) $ferie=true; // New Year's Day
			if($jour == 26 && $mois == 1) $ferie=true; // Republic Day
			if($jour == 1 && $mois == 5) $ferie=true; // May Day
			if($jour == 15 && $mois == 8) $ferie=true; // Independence Day
			if($jour == 2 && $mois == 10) $ferie=true; // Gandhi Jayanti
			if($jour == 25 && $mois == 12) $ferie=true; // Christmas
		}

		if ($countrycode == 'ES')
		{
			$countryfound=1;

			// Definition des dates feriees fixes
			if($jour == 1 && $mois == 1)   $ferie=true; // Año nuevo
			if($jour == 6 && $mois == 1)   $ferie=true; // Día Reyes
			if($jour == 1 && $mois == 5)   $ferie=true; // 1 Mayo
			if($jour == 15 && $mois == 8)  $ferie=true; // 15 Agosto
			if($jour == 12 && $mois == 10)  $ferie=true; // Día Hispanidad
			if($jour == 1 && $mois == 11)  $ferie=true; // 1 noviembre
			if($jour == 6 && $mois == 12) $ferie=true; // Constitución
			if($jour == 8 && $mois == 12)  $ferie=true; // Inmaculada
			if($jour == 25 && $mois == 12) $ferie=true; // 25 diciembre

			// Calcul día de Pascua
			$date_paques = easter_date($annee);
			$jour_paques = date("d", $date_paques);
			$mois_paques = date("m", $date_paques);
			if($jour_paques == $jour && $mois_paques == $mois) $ferie=true;
			// Paques

			// Viernes Santo
            $date_viernes = mktime(
                date("H", $date_paques),
                date("i", $date_paques),
                date("s", $date_paques),
                date("m", $date_paques),
                date("d", $date_paques) -2,
                date("Y", $date_paques)
            );
			$jour_viernes = date("d", $date_viernes);
			$mois_viernes = date("m", $date_viernes);
			if($jour_viernes == $jour && $mois_viernes == $mois) $ferie=true;
			//Viernes Santo
		}

		if ($countrycode == 'AT')
		{
		    $countryfound=1;

		    // Definition des dates feriees fixes
		    if($jour == 1 && $mois == 1)   $ferie=true; // Neujahr
		    if($jour == 6 && $mois == 1)   $ferie=true; // Hl. 3 Koenige
		    if($jour == 1 && $mois == 5)   $ferie=true; // 1. Mai
		    if($jour == 15 && $mois == 8)  $ferie=true; // Mariae Himmelfahrt
		    if($jour == 26 && $mois == 10) $ferie=true; // 26. Oktober
		    if($jour == 1 && $mois == 11)  $ferie=true; // Allerheiligen
		    if($jour == 8 && $mois == 12)  $ferie=true; // Mariae Empfaengnis
		    if($jour == 24 && $mois == 12) $ferie=true; // Heilig abend
		    if($jour == 25 && $mois == 12) $ferie=true; // Christtag
		    if($jour == 26 && $mois == 12) $ferie=true; // Stefanietag
		    if($jour == 31 && $mois == 12) $ferie=true; // Silvester

		    // Easter calculation
		    $date_paques = easter_date($annee);
		    $jour_paques = date("d", $date_paques);
		    $mois_paques = date("m", $date_paques);
		    if($jour_paques == $jour && $mois_paques == $mois) $ferie=true;
		    // Easter sunday

		    // Monday after easter
		    $date_eastermonday = mktime(
		        date("H", $date_paques),
		        date("i", $date_paques),
		        date("s", $date_paques),
		        date("m", $date_paques),
		        date("d", $date_paques) + 1,
		        date("Y", $date_paques)
		        );
		    $jour_eastermonday = date("d", $date_eastermonday);
		    $mois_eastermonday = date("m", $date_eastermonday);
		    if($jour_eastermonday == $jour && $mois_eastermonday == $mois) $ferie=true;
		    // Easter monday

		    // Christi Himmelfahrt (39 days after easter sunday)
		    $date_ch = mktime(
		        date("H", $date_paques),
		        date("i", $date_paques),
		        date("s", $date_paques),
		        date("m", $date_paques),
		        date("d", $date_paques) + 39,
		        date("Y", $date_paques)
		        );
		    $jour_ch = date("d", $date_ch);
		    $mois_ch = date("m", $date_ch);
		    if($jour_ch == $jour && $mois_ch == $mois) $ferie=true;
		    // Christi Himmelfahrt

		    // Pfingsten (50 days after easter sunday)
		    $date_pentecote = mktime(
		        date("H", $date_paques),
		        date("i", $date_paques),
		        date("s", $date_paques),
		        date("m", $date_paques),
		        date("d", $date_paques) + 50,
		        date("Y", $date_paques)
		        );
		    $jour_pentecote = date("d", $date_pentecote);
		    $mois_pentecote = date("m", $date_pentecote);
		    if($jour_pentecote == $jour && $mois_pentecote == $mois) $ferie=true;
		    // Pfingsten

		    // Fronleichnam (60 days after easter sunday)
		    $date_fronleichnam = mktime(
		        date("H", $date_paques),
		        date("i", $date_paques),
		        date("s", $date_paques),
		        date("m", $date_paques),
		        date("d", $date_paques) + 60,
		        date("Y", $date_paques)
		        );
		    $jour_fronleichnam = date("d", $date_fronleichnam);
		    $mois_fronleichnam = date("m", $date_fronleichnam);
		    if($jour_fronleichnam == $jour && $mois_fronleichnam == $mois) $ferie=true;
		    // Fronleichnam
		}

		// If we have to include saturday and sunday
		if ($includesaturdayandsunday)
		{
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if($jour_semaine == 0 || $jour_semaine == 6) $ferie=true;
			//Saturday (6) and Sunday (0)
		}

		// On incremente compteur
		if ($ferie) $nbFerie++;

		// Increase number of days (on go up into loop)
		$timestampStart=dol_time_plus_duree($timestampStart, 1, 'd');
		//var_dump($jour.' '.$mois.' '.$annee.' '.$timestampStart);

		$i++;
	}

	return $nbFerie;
}

/**
 *	Function to return number of days between two dates (date must be UTC date !)
 *  Example: 2012-01-01 2012-01-02 => 1 if lastday=0, 2 if lastday=1
 *
 *	@param	   int			$timestampStart     Timestamp start UTC
 *	@param	   int			$timestampEnd       Timestamp end UTC
 *	@param     int			$lastday            Last day is included, 0: no, 1:yes
 *	@return    int								Number of days
 *  @see also num_public_holiday, num_open_day
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
		$nbjours = (int) floor(($timestampEnd - $timestampStart)/(60*60*24)) + 1 - $bit;
	}
	//print ($timestampEnd - $timestampStart) - $lastday;
	return $nbjours;
}

/**
 *	Function to return number of working days (and text of units) between two dates (working days)
 *
 *	@param	   	int			$timestampStart     Timestamp for start date (date must be UTC to avoid calculation errors)
 *	@param	   	int			$timestampEnd       Timestamp for end date (date must be UTC to avoid calculation errors)
 *	@param     	int			$inhour             0: return number of days, 1: return number of hours
 *	@param		int			$lastday            We include last day, 0: no, 1:yes
 *  @param		int			$halfday			Tag to define half day when holiday start and end
 *  @param      string		$country_code       Country code (company country code if not defined)
 *	@return    	int								Number of days or hours
 *  @see also num_between_day, num_public_holiday
 */
function num_open_day($timestampStart, $timestampEnd, $inhour=0, $lastday=0, $halfday=0, $country_code='')
{
	global $langs,$mysoc;

	if (empty($country_code)) $country_code=$mysoc->country_code;

	dol_syslog('num_open_day timestampStart='.$timestampStart.' timestampEnd='.$timestampEnd.' bit='.$lastday.' country_code='.$country_code);

	// Check parameters
	if (! is_int($timestampStart) && ! is_float($timestampStart)) return 'ErrorBadParameter_num_open_day';
	if (! is_int($timestampEnd) && ! is_float($timestampEnd)) return 'ErrorBadParameter_num_open_day';

	//print 'num_open_day timestampStart='.$timestampStart.' timestampEnd='.$timestampEnd.' bit='.$lastday;
	if ($timestampStart < $timestampEnd)
	{
		$numdays = num_between_day($timestampStart, $timestampEnd, $lastday);
		$numholidays = num_public_holiday($timestampStart, $timestampEnd, $country_code, $lastday);
		$nbOpenDay = $numdays - $numholidays;
		$nbOpenDay.= " " . $langs->trans("Days");
		if ($inhour == 1 && $nbOpenDay <= 3) $nbOpenDay = $nbOpenDay*24 . $langs->trans("HourShort");
		return $nbOpenDay - (($inhour == 1 ? 12 : 0.5) * abs($halfday));
	}
	elseif ($timestampStart == $timestampEnd)
	{
		$nbOpenDay=$lastday;
		if ($inhour == 1) $nbOpenDay = $nbOpenDay*24 . $langs->trans("HourShort");
		return $nbOpenDay - (($inhour == 1 ? 12 : 0.5) * abs($halfday));
	}
	else
	{
		return $langs->trans("Error");
	}
}



/**
 *	Return array of translated months or selected month.
 *  This replace old function monthArrayOrSelected.
 *
 *	@param	Translate	$outputlangs	Object langs
 *  @param	int			$short			0=Return long label, 1=Return short label
 *	@return array						Month string or array if selected < 0
 */
function monthArray($outputlangs,$short=0)
{
	$montharray = array (
	    1  => $outputlangs->trans("Month01"),
	    2  => $outputlangs->trans("Month02"),
	    3  => $outputlangs->trans("Month03"),
	    4  => $outputlangs->trans("Month04"),
	    5  => $outputlangs->trans("Month05"),
	    6  => $outputlangs->trans("Month06"),
	    7  => $outputlangs->trans("Month07"),
	    8  => $outputlangs->trans("Month08"),
	    9  => $outputlangs->trans("Month09"),
	    10 => $outputlangs->trans("Month10"),
	    11 => $outputlangs->trans("Month11"),
	    12 => $outputlangs->trans("Month12")
    );

	if (! empty($short))
	{
		$montharray = array (
		    1  => $outputlangs->trans("MonthShort01"),
		    2  => $outputlangs->trans("MonthShort02"),
		    3  => $outputlangs->trans("MonthShort03"),
		    4  => $outputlangs->trans("MonthShort04"),
		    5  => $outputlangs->trans("MonthShort05"),
		    6  => $outputlangs->trans("MonthShort06"),
		    7  => $outputlangs->trans("MonthShort07"),
		    8  => $outputlangs->trans("MonthShort08"),
		    9  => $outputlangs->trans("MonthShort09"),
		    10 => $outputlangs->trans("MonthShort10"),
		    11 => $outputlangs->trans("MonthShort11"),
		    12 => $outputlangs->trans("MonthShort12")
			);
	}

	return $montharray;
}
