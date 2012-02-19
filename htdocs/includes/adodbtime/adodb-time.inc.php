<?php
/**
 * Copyright (C) 2003-2005 	John Lim
 * Copyright (C)            Jackbbs
 *
 * This file is released under BSD-style license
 */

/* =============================================================================
PHP native date functions use integer timestamps for computations.
Because of this, dates are restricted to the years 1901-2038 on Unix
and 1970-2038 on Windows due to integer overflow for dates beyond
those years. This library overcomes these limitations by replacing the
native function's signed integers (normally 32-bits) with PHP floating
point numbers (normally 64-bits).

Dates from 100 A.D. to 3000 A.D. and later
have been tested. The minimum is 100 A.D. as <100 will invoke the
2 => 4 digit year conversion. The maximum is billions of years in the
future, but this is a theoretical limit as the computation of that year
would take too long with the current implementation of adodb_mktime().

This library replaces native functions as follows:

<pre>
	getdate()  with  adodb_getdate()
	date()     with  adodb_date()
	gmdate()   with  adodb_gmdate()
	mktime()   with  adodb_mktime()
	gmmktime() with  adodb_gmmktime()
	strftime() with  adodb_strftime()
	strftime() with  adodb_gmstrftime()
</pre>

The parameters are identical, except that adodb_date() accepts a subset
of date()'s field formats. Mktime() will convert from local time to GMT,
and date() will convert from GMT to local time, but daylight savings is
not handled currently.

This library is independant of the rest of ADOdb, and can be used
as standalone code.

PERFORMANCE

For high speed, this library uses the native date functions where
possible, and only switches to PHP code when the dates fall outside
the 32-bit signed integer range.

GREGORIAN CORRECTION

Pope Gregory shortened October of A.D. 1582 by ten days. Thursday,
October 4, 1582 (Julian) was followed immediately by Friday, October 15,
1582 (Gregorian).

Since 0.06, we handle this correctly, so:

adodb_mktime(0,0,0,10,15,1582) - adodb_mktime(0,0,0,10,4,1582)
	== 24 * 3600 (1 day)

=============================================================================

BUG REPORTS

These should be posted to the ADOdb forums at

	http://phplens.com/lens/lensforum/topics.php?id=4

=============================================================================

FUNCTION DESCRIPTIONS


** FUNCTION adodb_getdate($date=false)

Returns an array containing date information, as getdate(), but supports
dates greater than 1901 to 2038. The local date/time format is derived from a
heuristic the first time adodb_getdate is called.


** FUNCTION adodb_date($fmt, $timestamp = false)

Convert a timestamp to a formatted local date. If $timestamp is not defined, the
current timestamp is used. Unlike the function date(), it supports dates
outside the 1901 to 2038 range.

The format fields that adodb_date supports:

<pre>
	a - "am" or "pm"
	A - "AM" or "PM"
	d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
	D - day of the week, textual, 3 letters; e.g. "Fri"
	F - month, textual, long; e.g. "January"
	g - hour, 12-hour format without leading zeros; i.e. "1" to "12"
	G - hour, 24-hour format without leading zeros; i.e. "0" to "23"
	h - hour, 12-hour format; i.e. "01" to "12"
	H - hour, 24-hour format; i.e. "00" to "23"
	i - minutes; i.e. "00" to "59"
	j - day of the month without leading zeros; i.e. "1" to "31"
	l (lowercase 'L') - day of the week, textual, long; e.g. "Friday"
	L - boolean for whether it is a leap year; i.e. "0" or "1"
	m - month; i.e. "01" to "12"
	M - month, textual, 3 letters; e.g. "Jan"
	n - month without leading zeros; i.e. "1" to "12"
	O - Difference to Greenwich time in hours; e.g. "+0200"
	Q - Quarter, as in 1, 2, 3, 4
	r - RFC 822 formatted date; e.g. "Thu, 21 Dec 2000 16:01:07 +0200"
	s - seconds; i.e. "00" to "59"
	S - English ordinal suffix for the day of the month, 2 characters;
	   			i.e. "st", "nd", "rd" or "th"
	t - number of days in the given month; i.e. "28" to "31"
	T - Timezone setting of this machine; e.g. "EST" or "MDT"
	U - seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
	w - day of the week, numeric, i.e. "0" (Sunday) to "6" (Saturday)
	Y - year, 4 digits; e.g. "1999"
	y - year, 2 digits; e.g. "99"
	z - day of the year; i.e. "0" to "365"
	Z - timezone offset in seconds (i.e. "-43200" to "43200").
	   			The offset for timezones west of UTC is always negative,
				and for those east of UTC is always positive.
</pre>

Unsupported:
<pre>
	B - Swatch Internet time
	I (capital i) - "1" if Daylight Savings Time, "0" otherwise.
	W - ISO-8601 week number of year, weeks starting on Monday

</pre>


** FUNCTION adodb_mktime($hr, $min, $sec[, $month, $day, $year])

Converts a local date to a unix timestamp.  Unlike the function mktime(), it supports
dates outside the 1901 to 2038 range. All parameters are optional.


** FUNCTION adodb_strftime($fmt, $timestamp = false)

Convert a timestamp to a formatted local date. Internally converts $fmt into
adodb_date format, then echo result.

For best results, you can define the local date format yourself. Define a global
variable $ADODB_DATE_LOCALE which is an array, 1st element is date format using
adodb_date syntax, and 2nd element is the time format, also in adodb_date syntax.

    eg. $ADODB_DATE_LOCALE = array('d/m/Y','H:i:s');

	Supported format codes:

<pre>
	%a - abbreviated weekday name according to the current locale
	%A - full weekday name according to the current locale
	%b - abbreviated month name according to the current locale
	%B - full month name according to the current locale
	%c - preferred date and time representation for the current locale
	%d - day of the month as a decimal number (range 01 to 31)
	%D - same as %m/%d/%y
	%e - day of the month as a decimal number, a single digit is preceded by a space (range ' 1' to '31')
	%h - same as %b
	%H - hour as a decimal number using a 24-hour clock (range 00 to 23)
	%I - hour as a decimal number using a 12-hour clock (range 01 to 12)
	%m - month as a decimal number (range 01 to 12)
	%M - minute as a decimal number
	%n - newline character
	%p - either `am' or `pm' according to the given time value, or the corresponding strings for the current locale
	%r - time in a.m. and p.m. notation
	%R - time in 24 hour notation
	%S - second as a decimal number
	%t - tab character
	%T - current time, equal to %H:%M:%S
	%x - preferred date representation for the current locale without the time
	%X - preferred time representation for the current locale without the date
	%y - year as a decimal number without a century (range 00 to 99)
	%Y - year as a decimal number including the century
	%Z - time zone or name or abbreviation
	%% - a literal `%' character
</pre>

	Unsupported codes:
<pre>
	%C - century number (the year divided by 100 and truncated to an integer, range 00 to 99)
	%g - like %G, but without the century.
	%G - The 4-digit year corresponding to the ISO week number (see %V).
	     This has the same format and value as %Y, except that if the ISO week number belongs
		 to the previous or next year, that year is used instead.
	%j - day of the year as a decimal number (range 001 to 366)
	%u - weekday as a decimal number [1,7], with 1 representing Monday
	%U - week number of the current year as a decimal number, starting
	    with the first Sunday as the first day of the first week
	%V - The ISO 8601:1988 week number of the current year as a decimal number,
	     range 01 to 53, where week 1 is the first week that has at least 4 days in the
		 current year, and with Monday as the first day of the week. (Use %G or %g for
		 the year component that corresponds to the week number for the specified timestamp.)
	%w - day of the week as a decimal, Sunday being 0
	%W - week number of the current year as a decimal number, starting with the
	     first Monday as the first day of the first week
</pre>

=============================================================================

NOTES

Useful url for generating test timestamps:
	http://www.4webhelp.net/us/timestamp.php

Possible future optimizations include

a. Using an algorithm similar to Plauger's in "The Standard C Library"
(page 428, xttotm.c _Ttotm() function). Plauger's algorithm will not
work outside 32-bit signed range, so i decided not to implement it.

b. Implement daylight savings, which looks awfully complicated, see
	http://webexhibits.org/daylightsaving/


CHANGELOG

- 18 July  2005  0.21
- In PHP 4.3.11, the 'r' format has changed. Leading 0 in day is added. Changed for compat.
- Added support for negative months in adodb_mktime().

- 24 Feb 2005 0.20
Added limited strftime/gmstrftime support. x10 improvement in performance of adodb_date().

- 21 Dec 2004 0.17
In adodb_getdate(), the timestamp was accidentally converted to gmt when $is_gmt is false.
Also adodb_mktime(0,0,0) did not work properly. Both fixed thx Mauro.

- 17 Nov 2004 0.16
Removed intval typecast in adodb_mktime() for secs, allowing:
	 adodb_mktime(0,0,0 + 2236672153,1,1,1934);
Suggested by Ryan.

- 18 July 2004 0.15
All params in adodb_mktime were formerly compulsory. Now only the hour, min, secs is compulsory.
This brings it more in line with mktime (still not identical).

- 23 June 2004 0.14

Allow you to define your own daylights savings function, adodb_daylight_sv.
If the function is defined (somewhere in an include), then you can correct for daylights savings.

In this example, we apply daylights savings in June or July, adding one hour. This is extremely
unrealistic as it does not take into account time-zone, geographic location, current year.

function adodb_daylight_sv(&$arr, $is_gmt)
{
	if ($is_gmt) return;
	$m = $arr['mon'];
	if ($m == 6 || $m == 7) $arr['hours'] += 1;
}

This is only called by adodb_date() and not by adodb_mktime().

The format of $arr is
Array (
   [seconds] => 0
   [minutes] => 0
   [hours] => 0
   [mday] => 1      # day of month, eg 1st day of the month
   [mon] => 2       # month (eg. Feb)
   [year] => 2102
   [yday] => 31     # days in current year
   [leap] =>        # true if leap year
   [ndays] => 28    # no of days in current month
   )


- 28 Apr 2004 0.13
Fixed adodb_date to properly support $is_gmt. Thx to Dimitar Angelov.

- 20 Mar 2004 0.12
Fixed month calculation error in adodb_date. 2102-June-01 appeared as 2102-May-32.

- 26 Oct 2003 0.11
Because of daylight savings problems (some systems apply daylight savings to
January!!!), changed adodb_get_gmt_diff() to ignore daylight savings.

- 9 Aug 2003 0.10
Fixed bug with dates after 2038.
See http://phplens.com/lens/lensforum/msgs.php?id=6980

- 1 July 2003 0.09
Added support for Q (Quarter).
Added adodb_date2(), which accepts ISO date in 2nd param

- 3 March 2003 0.08
Added support for 'S' adodb_date() format char. Added constant ADODB_ALLOW_NEGATIVE_TS
if you want PHP to handle negative timestamps between 1901 to 1969.

- 27 Feb 2003 0.07
All negative numbers handled by adodb now because of RH 7.3+ problems.
See http://bugs.php.net/bug.php?id=20048&edit=2

- 4 Feb 2003 0.06
Fixed a typo, 1852 changed to 1582! This means that pre-1852 dates
are now correctly handled.

- 29 Jan 2003 0.05

Leap year checking differs under Julian calendar (pre 1582). Also
leap year code optimized by checking for most common case first.

We also handle month overflow correctly in mktime (eg month set to 13).

Day overflow for less than one month's days is supported.

- 28 Jan 2003 0.04

Gregorian correction handled. In PHP5, we might throw an error if
mktime uses invalid dates around 5-14 Oct 1582. Released with ADOdb 3.10.
Added limbo 5-14 Oct 1582 check, when we set to 15 Oct 1582.

- 27 Jan 2003 0.03

Fixed some more month problems due to gmt issues. Added constant ADODB_DATE_VERSION.
Fixed calculation of days since start of year for <1970.

- 27 Jan 2003 0.02

Changed _adodb_getdate() to inline leap year checking for better performance.
Fixed problem with time-zones west of GMT +0000.

- 24 Jan 2003 0.01

First implementation.
*/


/* Initialization */

/*
	Version Number
*/
define('ADODB_DATE_VERSION',0.21);

/*
	This code was originally for windows. But apparently this problem happens
	also with Linux, RH 7.3 and later!

	glibc-2.2.5-34 and greater has been changed to return -1 for dates <
	1970.  This used to work.  The problem exists with RedHat 7.3 and 8.0
	echo (mktime(0, 0, 0, 1, 1, 1960));  // prints -1

	References:
	 http://bugs.php.net/bug.php?id=20048&edit=2
	 http://lists.debian.org/debian-glibc/2002/debian-glibc-200205/msg00010.html
*/

if (!defined('ADODB_ALLOW_NEGATIVE_TS')) define('ADODB_NO_NEGATIVE_TS',1);


/**
	Returns day of week, 0 = Sunday,... 6=Saturday.
	Algorithm from PEAR::Date_Calc
*/
function _adodb_dow($year, $month, $day)
{
/*
Pope Gregory removed 10 days - October 5 to October 14 - from the year 1582 and
proclaimed that from that time onwards 3 days would be dropped from the calendar
every 400 years.

Thursday, October 4, 1582 (Julian) was followed immediately by Friday, October 15, 1582 (Gregorian).
*/
	if ($year <= 1582) {
		if ($year < 1582 ||
			($year == 1582 && ($month < 10 || ($month == 10 && $day < 15)))) $greg_correction = 3;
		 else
			$greg_correction = 0;
	} else
		$greg_correction = 0;

	if($month > 2)
	    $month -= 2;
	else {
	    $month += 10;
	    $year--;
	}

	$day =  floor((13 * $month - 1) / 5) +
	        $day + ($year % 100) +
	        floor(($year % 100) / 4) +
	        floor(($year / 100) / 4) - 2 *
	        floor($year / 100) + 77 + $greg_correction;

	return $day - 7 * floor($day / 7);
}


/**
 Checks for leap year, returns true if it is. No 2-digit year check. Also
 handles julian calendar correctly.
*/
function _adodb_is_leap_year($year)
{
	if ($year % 4 != 0) return false;

	if ($year % 400 == 0) {
		return true;
	// if gregorian calendar (>1582), century not-divisible by 400 is not leap
	} else if ($year > 1582 && $year % 100 == 0 ) {
		return false;
	}

	return true;
}


/**
	Fix 2-digit years. Works for any century.
 	Assumes that if 2-digit is more than 30 years in future, then previous century.
*/
function adodb_year_digit_check($y)
{
	if ($y < 100) {

		$yr = (integer) date("Y");
		$century = (integer) ($yr /100);

		if ($yr%100 > 50) {
			$c1 = $century + 1;
			$c0 = $century;
		} else {
			$c1 = $century;
			$c0 = $century - 1;
		}
		$c1 *= 100;
		// if 2-digit year is less than 30 years in future, set it to this century
		// otherwise if more than 30 years in future, then we set 2-digit year to the prev century.
		if (($y + $c1) < $yr+30) $y = $y + $c1;
		else $y = $y + $c0*100;
	}
	return $y;
}

/**
 get local time zone offset from GMT
*/
function adodb_get_gmt_diff()
{
    static $TZ;
	if (isset($TZ)) return $TZ;

//	$TZ = mktime(0,0,0,1,2,1970,0) - gmmktime(0,0,0,1,2,1970,0);
// LDR Fix To avoid warning on PHP 5.1
	$TZ = mktime(0,0,0,1,2,1970) - gmmktime(0,0,0,1,2,1970);
	return $TZ;
}

/**
	Returns an array with date info.
*/
function adodb_getdate($d=false,$fast=false)
{
	if ($d === false) return getdate();
	if (!defined('ADODB_TEST_DATES')) {
		if ((abs($d) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
			if (!defined('ADODB_NO_NEGATIVE_TS') || $d >= 0) // if windows, must be +ve integer
				return @getdate($d);
		}
	}
	return _adodb_getdate($d);
}


/**
	Low-level function that returns the getdate() array. We have a special
	$fast flag, which if set to true, will return fewer array values,
	and is much faster as it does not calculate dow, etc.
*/
function _adodb_getdate($origd=false,$fast=false,$is_gmt=false)
{
    static $YRS;

	$d =  $origd - ($is_gmt ? 0 : adodb_get_gmt_diff());

	$_day_power = 86400;
	$_hour_power = 3600;
	$_min_power = 60;

	if ($d < -12219321600) $d -= 86400*10; // if 15 Oct 1582 or earlier, gregorian correction

	$_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
	$_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

	$d366 = $_day_power * 366;
	$d365 = $_day_power * 365;

	if ($d < 0) {

		if (empty($YRS)) $YRS = array(
			1970 => 0,
			1960 => -315619200,
			1950 => -631152000,
			1940 => -946771200,
			1930 => -1262304000,
			1920 => -1577923200,
			1910 => -1893456000,
			1900 => -2208988800,
			1890 => -2524521600,
			1880 => -2840140800,
			1870 => -3155673600,
			1860 => -3471292800,
			1850 => -3786825600,
			1840 => -4102444800,
			1830 => -4417977600,
			1820 => -4733596800,
			1810 => -5049129600,
			1800 => -5364662400,
			1790 => -5680195200,
			1780 => -5995814400,
			1770 => -6311347200,
			1760 => -6626966400,
			1750 => -6942499200,
			1740 => -7258118400,
			1730 => -7573651200,
			1720 => -7889270400,
			1710 => -8204803200,
			1700 => -8520336000,
			1690 => -8835868800,
			1680 => -9151488000,
			1670 => -9467020800,
			1660 => -9782640000,
			1650 => -10098172800,
			1640 => -10413792000,
			1630 => -10729324800,
			1620 => -11044944000,
			1610 => -11360476800,
			1600 => -11676096000);

		if ($is_gmt) $origd = $d;
		// The valid range of a 32bit signed timestamp is typically from
		// Fri, 13 Dec 1901 20:45:54 GMT to Tue, 19 Jan 2038 03:14:07 GMT
		//

		$lastsecs = 0;
		$lastyear = 1970;
		foreach($YRS as $year => $secs) {
			if ($d >= $secs) {
				$a = $lastyear;
				break;
			}
			$lastsecs = $secs;
			$lastyear = $year;
		}

		$d -= $lastsecs;
		if (!isset($a)) $a = $lastyear;

		//echo ' yr=',$a,' ', $d,'.';

		for (; --$a >= 0;) {
			$lastd = $d;

			if ($leaf = _adodb_is_leap_year($a)) $d += $d366;
			else $d += $d365;

			if ($d >= 0) {
				$year = $a;
				break;
			}
		}
		/**/

		$secsInYear = 86400 * ($leaf ? 366 : 365) + $lastd;

		$d = $lastd;
		$mtab = ($leaf) ? $_month_table_leaf : $_month_table_normal;
		for ($a = 13 ; --$a > 0;) {
			$lastd = $d;
			$d += $mtab[$a] * $_day_power;
			if ($d >= 0) {
				$month = $a;
				$ndays = $mtab[$a];
				break;
			}
		}

		$d = $lastd;
		$day = $ndays + ceil(($d+1) / ($_day_power));

		$d += ($ndays - $day+1)* $_day_power;
		$hour = floor($d/$_hour_power);

	} else {
		for ($a = 1970 ;; $a++) {
			$lastd = $d;

			if ($leaf = _adodb_is_leap_year($a)) $d -= $d366;
			else $d -= $d365;
			if ($d < 0) {
				$year = $a;
				break;
			}
		}
		$secsInYear = $lastd;
		$d = $lastd;
		$mtab = ($leaf) ? $_month_table_leaf : $_month_table_normal;
		for ($a = 1 ; $a <= 12; $a++) {
			$lastd = $d;
			$d -= $mtab[$a] * $_day_power;
			if ($d < 0) {
				$month = $a;
				$ndays = $mtab[$a];
				break;
			}
		}
		$d = $lastd;
		$day = ceil(($d+1) / $_day_power);
		$d = $d - ($day-1) * $_day_power;
		$hour = floor($d /$_hour_power);
	}

	$d -= $hour * $_hour_power;
	$min = floor($d/$_min_power);
	$secs = $d - $min * $_min_power;
	if ($fast) {
		return array(
		'seconds' => $secs,
		'minutes' => $min,
		'hours' => $hour,
		'mday' => $day,
		'mon' => $month,
		'year' => $year,
		'yday' => floor($secsInYear/$_day_power),
		'leap' => $leaf,
		'ndays' => $ndays
		);
	}


	$dow = _adodb_dow($year,$month,$day);

	return array(
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
	);
}

/**
	Return formatted date based on timestamp $d
*/
function adodb_date($fmt,$d=false,$is_gmt=false)
{
static $daylight;

	if ($d === false) return ($is_gmt)? @gmdate($fmt): @date($fmt);
	if (!defined('ADODB_TEST_DATES')) {
		if ((abs($d) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
			if (!defined('ADODB_NO_NEGATIVE_TS') || $d >= 0) // if windows, must be +ve integer
				return ($is_gmt)? @gmdate($fmt,$d): @date($fmt,$d);

		}
	}
	$_day_power = 86400;

	$arr = _adodb_getdate($d,true,$is_gmt);

	if (!isset($daylight)) $daylight = function_exists('adodb_daylight_sv');
	if ($daylight) adodb_daylight_sv($arr, $is_gmt);

	$year = $arr['year'];
	$month = $arr['mon'];
	$day = $arr['mday'];
	$hour = $arr['hours'];
	$min = $arr['minutes'];
	$secs = $arr['seconds'];

	$max = strlen($fmt);
	$dates = '';

	/*
		at this point, we have the following integer vars to manipulate:
		$year, $month, $day, $hour, $min, $secs
	*/
	for ($i=0; $i < $max; $i++) {
		switch($fmt[$i]) {
		case 'T': $dates .= date('T');break;
		// YEAR
		case 'L': $dates .= $arr['leap'] ? '1' : '0'; break;
		case 'r': // Thu, 21 Dec 2000 16:01:07 +0200

			// 4.3.11 uses '04 Jun 2004'
			// 4.3.8 uses  ' 4 Jun 2004'
			$dates .= gmdate('D',$_day_power*(3+_adodb_dow($year,$month,$day))).', '
				. ($day<10?'0'.$day:$day) . ' '.date('M',mktime(0,0,0,$month,2,1971)).' '.$year.' ';

			if ($hour < 10) $dates .= '0'.$hour; else $dates .= $hour;

			if ($min < 10) $dates .= ':0'.$min; else $dates .= ':'.$min;

			if ($secs < 10) $dates .= ':0'.$secs; else $dates .= ':'.$secs;

			$gmt = adodb_get_gmt_diff();
			$dates .= sprintf(' %s%04d',($gmt<0)?'+':'-',abs($gmt)/36); break;

		case 'Y': $dates .= $year; break;
		case 'y': $dates .= substr($year,strlen($year)-2,2); break;
		// MONTH
		case 'm': if ($month<10) $dates .= '0'.$month; else $dates .= $month; break;
		case 'Q': $dates .= ($month+3)>>2; break;
		case 'n': $dates .= $month; break;
		case 'M': $dates .= date('M',mktime(0,0,0,$month,2,1971)); break;
		case 'F': $dates .= date('F',mktime(0,0,0,$month,2,1971)); break;
		// DAY
		case 't': $dates .= $arr['ndays']; break;
		case 'z': $dates .= $arr['yday']; break;
		case 'w': $dates .= _adodb_dow($year,$month,$day); break;
		case 'l': $dates .= gmdate('l',$_day_power*(3+_adodb_dow($year,$month,$day))); break;
		case 'D': $dates .= gmdate('D',$_day_power*(3+_adodb_dow($year,$month,$day))); break;
		case 'j': $dates .= $day; break;
		case 'd': if ($day<10) $dates .= '0'.$day; else $dates .= $day; break;
		case 'S':
			$d10 = $day % 10;
			if ($d10 == 1) $dates .= 'st';
			else if ($d10 == 2 && $day != 12) $dates .= 'nd';
			else if ($d10 == 3) $dates .= 'rd';
			else $dates .= 'th';
			break;

		// HOUR
		case 'Z':
			$dates .= ($is_gmt) ? 0 : -adodb_get_gmt_diff(); break;
		case 'O':
			$gmt = ($is_gmt) ? 0 : adodb_get_gmt_diff();
			$dates .= sprintf('%s%04d',($gmt<0)?'+':'-',abs($gmt)/36); break;

		case 'H':
			if ($hour < 10) $dates .= '0'.$hour;
			else $dates .= $hour;
			break;
		case 'h':
			if ($hour > 12) $hh = $hour - 12;
			else {
				if ($hour == 0) $hh = '12';
				else $hh = $hour;
			}

			if ($hh < 10) $dates .= '0'.$hh;
			else $dates .= $hh;
			break;

		case 'G':
			$dates .= $hour;
			break;

		case 'g':
			if ($hour > 12) $hh = $hour - 12;
			else {
				if ($hour == 0) $hh = '12';
				else $hh = $hour;
			}
			$dates .= $hh;
			break;
		// MINUTES
		case 'i': if ($min < 10) $dates .= '0'.$min; else $dates .= $min; break;
		// SECONDS
		case 'U': $dates .= $d; break;
		case 's': if ($secs < 10) $dates .= '0'.$secs; else $dates .= $secs; break;
		// AM/PM
		// Note 00:00 to 11:59 is AM, while 12:00 to 23:59 is PM
		case 'a':
			if ($hour>=12) $dates .= 'pm';
			else $dates .= 'am';
			break;
		case 'A':
			if ($hour>=12) $dates .= 'PM';
			else $dates .= 'AM';
			break;
		default:
			$dates .= $fmt[$i]; break;
		// ESCAPE
		case "\\":
			$i++;
			if ($i < $max) $dates .= $fmt[$i];
			break;
		}
	}
	return $dates;
}

/**
	Return a timestamp given a local time. Originally by jackbbs.
	Note that $is_dst is not implemented and is ignored.

	Not a very fast algorithm - O(n) operation. Could be optimized to O(1).
*/
function adodb_mktime($hr,$min,$sec,$mon=false,$day=false,$year=false,$is_dst=false,$is_gmt=false)
{
	if (!defined('ADODB_TEST_DATES')) {

		if ($mon === false) {
			return $is_gmt? @gmmktime($hr,$min,$sec): @mktime($hr,$min,$sec);
		}

		// for windows, we don't check 1970 because with timezone differences,
		// 1 Jan 1970 could generate negative timestamp, which is illegal
		if (1971 < $year && $year < 2038
			|| !defined('ADODB_NO_NEGATIVE_TS') && (1901 < $year && $year < 2038)
			) {
				return $is_gmt ?
					@gmmktime($hr,$min,$sec,$mon,$day,$year):
					@mktime($hr,$min,$sec,$mon,$day,$year);
			}
	}

	$gmt_different = ($is_gmt) ? 0 : adodb_get_gmt_diff();

	/*
	# disabled because some people place large values in $sec.
	# however we need it for $mon because we use an array...
	$hr = intval($hr);
	$min = intval($min);
	$sec = intval($sec);
	*/
	$mon = intval($mon);
	$day = intval($day);
	$year = intval($year);


	$year = adodb_year_digit_check($year);

	if ($mon > 12) {
		$y = floor($mon / 12);
		$year += $y;
		$mon -= $y*12;
	} else if ($mon < 1) {
		$y = ceil((1-$mon) / 12);
		$year -= $y;
		$mon += $y*12;
	}

	$_day_power = 86400;
	$_hour_power = 3600;
	$_min_power = 60;

	$_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
	$_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

	$_total_date = 0;
	if ($year >= 1970) {
		for ($a = 1970 ; $a <= $year; $a++) {
			$leaf = _adodb_is_leap_year($a);
			if ($leaf == true) {
				$loop_table = $_month_table_leaf;
				$_add_date = 366;
			} else {
				$loop_table = $_month_table_normal;
				$_add_date = 365;
			}
			if ($a < $year) {
				$_total_date += $_add_date;
			} else {
				for($b=1;$b<$mon;$b++) {
					$_total_date += $loop_table[$b];
				}
			}
		}
		$_total_date +=$day-1;
		$ret = $_total_date * $_day_power + $hr * $_hour_power + $min * $_min_power + $sec + $gmt_different;

	} else {
		for ($a = 1969 ; $a >= $year; $a--) {
			$leaf = _adodb_is_leap_year($a);
			if ($leaf == true) {
				$loop_table = $_month_table_leaf;
				$_add_date = 366;
			} else {
				$loop_table = $_month_table_normal;
				$_add_date = 365;
			}
			if ($a > $year) { $_total_date += $_add_date;
			} else {
				for($b=12;$b>$mon;$b--) {
					$_total_date += $loop_table[$b];
				}
			}
		}
		$_total_date += $loop_table[$mon] - $day;

		$_day_time = $hr * $_hour_power + $min * $_min_power + $sec;
		$_day_time = $_day_power - $_day_time;
		$ret = -( $_total_date * $_day_power + $_day_time - $gmt_different);
		if ($ret < -12220185600) $ret += 10*86400; // if earlier than 5 Oct 1582 - gregorian correction
		else if ($ret < -12219321600) $ret = -12219321600; // if in limbo, reset to 15 Oct 1582.
	}
	//print " dmy=$day/$mon/$year $hr:$min:$sec => " .$ret;
	return $ret;
}

// hack - convert to adodb_date
function adodb_strftime($fmt, $ts=false,$is_gmt=false)
{
global $ADODB_DATE_LOCALE;

	if (!defined('ADODB_TEST_DATES')) {
		if ((abs($ts) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
			if (!defined('ADODB_NO_NEGATIVE_TS') || $ts >= 0) // if windows, must be +ve integer
				return ($is_gmt)? @gmstrftime($fmt,$ts): @strftime($fmt,$ts);

		}
	}

	if (empty($ADODB_DATE_LOCALE)) {
		$tstr = strtoupper(gmstrftime('%c',31366800)); // 30 Dec 1970, 1 am
		$sep = substr($tstr,2,1);
		$hasAM = strrpos($tstr,'M') !== false;

		$ADODB_DATE_LOCALE = array();
		$ADODB_DATE_LOCALE[] =  strncmp($tstr,'30',2) == 0 ? 'd'.$sep.'m'.$sep.'y' : 'm'.$sep.'d'.$sep.'y';
		$ADODB_DATE_LOCALE[]  = ($hasAM) ? 'h:i:s a' : 'H:i:s';

	}
	$inpct = false;
	$fmtdate = '';
	for ($i=0,$max = strlen($fmt); $i < $max; $i++) {
		$ch = $fmt[$i];
		if ($ch == '%') {
			if ($inpct) {
				$fmtdate .= '%';
				$inpct = false;
			} else
				$inpct = true;
		} else if ($inpct) {

			$inpct = false;
			switch($ch) {
			case '0':
			case '1':
			case '2':
			case '3':
			case '4':
			case '5':
			case '6':
			case '7':
			case '8':
			case '9':
			case 'E':
			case 'O':
				/* ignore format modifiers */
				$inpct = true;
				break;

			case 'a': $fmtdate .= 'D'; break;
			case 'A': $fmtdate .= 'l'; break;
			case 'h':
			case 'b': $fmtdate .= 'M'; break;
			case 'B': $fmtdate .= 'F'; break;
			case 'c': $fmtdate .= $ADODB_DATE_LOCALE[0].$ADODB_DATE_LOCALE[1]; break;
			case 'C': $fmtdate .= '\C?'; break; // century
			case 'd': $fmtdate .= 'd'; break;
			case 'D': $fmtdate .= 'm/d/y'; break;
			case 'e': $fmtdate .= 'j'; break;
			case 'g': $fmtdate .= '\g?'; break; //?
			case 'G': $fmtdate .= '\G?'; break; //?
			case 'H': $fmtdate .= 'H'; break;
			case 'I': $fmtdate .= 'h'; break;
			case 'j': $fmtdate .= '?z'; $parsej = true; break; // wrong as j=1-based, z=0-basd
			case 'm': $fmtdate .= 'm'; break;
			case 'M': $fmtdate .= 'i'; break;
			case 'n': $fmtdate .= "\n"; break;
			case 'p': $fmtdate .= 'a'; break;
			case 'r': $fmtdate .= 'h:i:s a'; break;
			case 'R': $fmtdate .= 'H:i:s'; break;
			case 'S': $fmtdate .= 's'; break;
			case 't': $fmtdate .= "\t"; break;
			case 'T': $fmtdate .= 'H:i:s'; break;
			case 'u': $fmtdate .= '?u'; $parseu = true; break; // wrong strftime=1-based, date=0-basde
			case 'U': $fmtdate .= '?U'; $parseU = true; break;// wrong strftime=1-based, date=0-based
			case 'x': $fmtdate .= $ADODB_DATE_LOCALE[0]; break;
			case 'X': $fmtdate .= $ADODB_DATE_LOCALE[1]; break;
			case 'w': $fmtdate .= '?w'; $parseu = true; break; // wrong strftime=1-based, date=0-basde
			case 'W': $fmtdate .= '?W'; $parseU = true; break;// wrong strftime=1-based, date=0-based
			case 'y': $fmtdate .= 'y'; break;
			case 'Y': $fmtdate .= 'Y'; break;
			case 'Z': $fmtdate .= 'T'; break;
			}
		} else if (('A' <= ($ch) && ($ch) <= 'Z' ) || ('a' <= ($ch) && ($ch) <= 'z' ))
			$fmtdate .= "\\".$ch;
		else
			$fmtdate .= $ch;
	}
	//echo "fmt=",$fmtdate,"<br>";
	if ($ts === false) $ts = time();
	$ret = adodb_date($fmtdate, $ts, $is_gmt);
	return $ret;
}


?>