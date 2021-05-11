<?php
/**
ADOdb Date Library, part of the ADOdb abstraction library
Download: http://phplens.com/phpeverywhere/

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

COPYRIGHT

(c) 2003-2014 John Lim and released under BSD-style license except for code by
jackbbs, which includes adodb_mktime, adodb_get_gmt_diff, adodb_is_leap_year
and originally found at http://www.php.net/manual/en/function.mktime.php

=============================================================================

BUG REPORTS

These should be posted to the ADOdb forums at

	http://phplens.com/lens/lensforum/topics.php?id=4

=============================================================================

FUNCTION DESCRIPTIONS

** FUNCTION adodb_time()

Returns the current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) as an unsigned integer.

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
	r - RFC 2822 formatted date; e.g. "Thu, 21 Dec 2000 16:01:07 +0200"
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


** FUNCTION adodb_date2($fmt, $isoDateString = false)
Same as adodb_date, but 2nd parameter accepts iso date, eg.

  adodb_date2('d-M-Y H:i','2003-12-25 13:01:34');


** FUNCTION adodb_gmdate($fmt, $timestamp = false)

Convert a timestamp to a formatted GMT date. If $timestamp is not defined, the
current timestamp is used. Unlike the function date(), it supports dates
outside the 1901 to 2038 range.


** FUNCTION adodb_mktime($hr, $min, $sec[, $month, $day, $year])

Converts a local date to a unix timestamp.  Unlike the function mktime(), it supports
dates outside the 1901 to 2038 range. All parameters are optional.


** FUNCTION adodb_gmmktime($hr, $min, $sec [, $month, $day, $year])

Converts a gmt date to a unix timestamp.  Unlike the function gmmktime(), it supports
dates outside the 1901 to 2038 range. Differs from gmmktime() in that all parameters
are currently compulsory.

** FUNCTION adodb_gmstrftime($fmt, $timestamp = false)
Convert a timestamp to a formatted GMT date.

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
- 16 Jan 2011 0.36
Added adodb_time() which returns current time. If > 2038, will return as float

- 7 Feb 2011 0.35
Changed adodb_date to be symmetric with adodb_mktime. See $jan1_71. fix for bc.

- 13 July 2010 0.34
Changed adodb_get_gm_diff to use DateTimeZone().

- 11 Feb 2008 0.33
* Bug in 0.32 fix for hour handling. Fixed.

- 1 Feb 2008 0.32
* Now adodb_mktime(0,0,0,12+$m,20,2040) works properly.

- 10 Jan 2008 0.31
* Now adodb_mktime(0,0,0,24,1,2037) works correctly.

- 15 July 2007 0.30
Added PHP 5.2.0 compatability fixes.
 * gmtime behaviour for 1970 has changed. We use the actual date if it is between 1970 to 2038 to get the
 * timezone, otherwise we use the current year as the baseline to retrieve the timezone.
 * Also the timezone's in php 5.2.* support historical data better, eg. if timezone today was +8, but
   in 1970 it was +7:30, then php 5.2 return +7:30, while this library will use +8.
 *

- 19 March 2006 0.24
Changed strftime() locale detection, because some locales prepend the day of week to the date when %c is used.

- 10 Feb 2006 0.23
PHP5 compat: when we detect PHP5, the RFC2822 format for gmt 0000hrs is changed from -0000 to +0000.
	In PHP4, we will still use -0000 for 100% compat with PHP4.

- 08 Sept 2005 0.22
In adodb_date2(), $is_gmt not supported properly. Fixed.

- 18 July  2005 0.21
In PHP 4.3.11, the 'r' format has changed. Leading 0 in day is added. Changed for compat.
Added support for negative months in adodb_mktime().

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
define('ADODB_DATE_VERSION',0.35);

$ADODB_DATETIME_CLASS = (PHP_VERSION >= 5.2);

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

function adodb_date_test_date($y1,$m,$d=13)
{
	$h = round(rand()% 24);
	$t = adodb_mktime($h,0,0,$m,$d,$y1);
	$rez = adodb_date('Y-n-j H:i:s',$t);
	if ($h == 0) $h = '00';
	else if ($h < 10) $h = '0'.$h;
	if ("$y1-$m-$d $h:00:00" != $rez) {
		print "<b>$y1 error, expected=$y1-$m-$d $h:00:00, adodb=$rez</b><br>";
		return false;
	}
	return true;
}

function adodb_date_test_strftime($fmt)
{
	$s1 = strftime($fmt);
	$s2 = adodb_strftime($fmt);

	if ($s1 == $s2) return true;

	echo "error for $fmt,  strftime=$s1, adodb=$s2<br>";
	return false;
}

/**
	 Test Suite
*/
function adodb_date_test()
{

	for ($m=-24; $m<=24; $m++)
		echo "$m :",adodb_date('d-m-Y',adodb_mktime(0,0,0,1+$m,20,2040)),"<br>";

	error_reporting(E_ALL);
	print "<h4>Testing adodb_date and adodb_mktime. version=".ADODB_DATE_VERSION.' PHP='.PHP_VERSION."</h4>";
	@set_time_limit(0);
	$fail = false;

	// This flag disables calling of PHP native functions, so we can properly test the code
	if (!defined('ADODB_TEST_DATES')) define('ADODB_TEST_DATES',1);

	$t = time();


	$fmt = 'Y-m-d H:i:s';
	echo '<pre>';
	echo 'adodb: ',adodb_date($fmt,$t),'<br>';
	echo 'php  : ',date($fmt,$t),'<br>';
	echo '</pre>';

	adodb_date_test_strftime('%Y %m %x %X');
	adodb_date_test_strftime("%A %d %B %Y");
	adodb_date_test_strftime("%H %M S");

	$t = adodb_mktime(0,0,0);
	if (!(adodb_date('Y-m-d') == date('Y-m-d'))) print 'Error in '.adodb_mktime(0,0,0).'<br>';

	$t = adodb_mktime(0,0,0,6,1,2102);
	if (!(adodb_date('Y-m-d',$t) == '2102-06-01')) print 'Error in '.adodb_date('Y-m-d',$t).'<br>';

	$t = adodb_mktime(0,0,0,2,1,2102);
	if (!(adodb_date('Y-m-d',$t) == '2102-02-01')) print 'Error in '.adodb_date('Y-m-d',$t).'<br>';


	print "<p>Testing gregorian <=> julian conversion<p>";
	$t = adodb_mktime(0,0,0,10,11,1492);
	//http://www.holidayorigins.com/html/columbus_day.html - Friday check
	if (!(adodb_date('D Y-m-d',$t) == 'Fri 1492-10-11')) print 'Error in Columbus landing<br>';

	$t = adodb_mktime(0,0,0,2,29,1500);
	if (!(adodb_date('Y-m-d',$t) == '1500-02-29')) print 'Error in julian leap years<br>';

	$t = adodb_mktime(0,0,0,2,29,1700);
	if (!(adodb_date('Y-m-d',$t) == '1700-03-01')) print 'Error in gregorian leap years<br>';

	print  adodb_mktime(0,0,0,10,4,1582).' ';
	print adodb_mktime(0,0,0,10,15,1582);
	$diff = (adodb_mktime(0,0,0,10,15,1582) - adodb_mktime(0,0,0,10,4,1582));
	if ($diff != 3600*24) print " <b>Error in gregorian correction = ".($diff/3600/24)." days </b><br>";

	print " 15 Oct 1582, Fri=".(adodb_dow(1582,10,15) == 5 ? 'Fri' : '<b>Error</b>')."<br>";
	print " 4 Oct 1582, Thu=".(adodb_dow(1582,10,4) == 4 ? 'Thu' : '<b>Error</b>')."<br>";

	print "<p>Testing overflow<p>";

	$t = adodb_mktime(0,0,0,3,33,1965);
	if (!(adodb_date('Y-m-d',$t) == '1965-04-02')) print 'Error in day overflow 1 <br>';
	$t = adodb_mktime(0,0,0,4,33,1971);
	if (!(adodb_date('Y-m-d',$t) == '1971-05-03')) print 'Error in day overflow 2 <br>';
	$t = adodb_mktime(0,0,0,1,60,1965);
	if (!(adodb_date('Y-m-d',$t) == '1965-03-01')) print 'Error in day overflow 3 '.adodb_date('Y-m-d',$t).' <br>';
	$t = adodb_mktime(0,0,0,12,32,1965);
	if (!(adodb_date('Y-m-d',$t) == '1966-01-01')) print 'Error in day overflow 4 '.adodb_date('Y-m-d',$t).' <br>';
	$t = adodb_mktime(0,0,0,12,63,1965);
	if (!(adodb_date('Y-m-d',$t) == '1966-02-01')) print 'Error in day overflow 5 '.adodb_date('Y-m-d',$t).' <br>';
	$t = adodb_mktime(0,0,0,13,3,1965);
	if (!(adodb_date('Y-m-d',$t) == '1966-01-03')) print 'Error in mth overflow 1 <br>';

	print "Testing 2-digit => 4-digit year conversion<p>";
	if (adodb_year_digit_check(00) != 2000) print "Err 2-digit 2000<br>";
	if (adodb_year_digit_check(10) != 2010) print "Err 2-digit 2010<br>";
	if (adodb_year_digit_check(20) != 2020) print "Err 2-digit 2020<br>";
	if (adodb_year_digit_check(30) != 2030) print "Err 2-digit 2030<br>";
	if (adodb_year_digit_check(40) != 1940) print "Err 2-digit 1940<br>";
	if (adodb_year_digit_check(50) != 1950) print "Err 2-digit 1950<br>";
	if (adodb_year_digit_check(90) != 1990) print "Err 2-digit 1990<br>";

	// Test string formating
	print "<p>Testing date formating</p>";

	$fmt = '\d\a\t\e T Y-m-d H:i:s a A d D F g G h H i j l L m M n O \R\F\C2822 r s t U w y Y z Z 2003';
	$s1 = date($fmt,0);
	$s2 = adodb_date($fmt,0);
	if ($s1 != $s2) {
		print " date() 0 failed<br>$s1<br>$s2<br>";
	}
	flush();
	for ($i=100; --$i > 0; ) {

		$ts = 3600.0*((rand()%60000)+(rand()%60000))+(rand()%60000);
		$s1 = date($fmt,$ts);
		$s2 = adodb_date($fmt,$ts);
		//print "$s1 <br>$s2 <p>";
		$pos = strcmp($s1,$s2);

		if (($s1) != ($s2)) {
			for ($j=0,$k=strlen($s1); $j < $k; $j++) {
				if ($s1[$j] != $s2[$j]) {
					print substr($s1,$j).' ';
					break;
				}
			}
			print "<b>Error date(): $ts<br><pre>
&nbsp; \"$s1\" (date len=".strlen($s1).")
&nbsp; \"$s2\" (adodb_date len=".strlen($s2).")</b></pre><br>";
			$fail = true;
		}

		$a1 = getdate($ts);
		$a2 = adodb_getdate($ts);
		$rez = array_diff($a1,$a2);
		if (sizeof($rez)>0) {
			print "<b>Error getdate() $ts</b><br>";
				print_r($a1);
			print "<br>";
				print_r($a2);
			print "<p>";
			$fail = true;
		}
	}

	// Test generation of dates outside 1901-2038
	print "<p>Testing random dates between 100 and 4000</p>";
	adodb_date_test_date(100,1);
	for ($i=100; --$i >= 0;) {
		$y1 = 100+rand(0,1970-100);
		$m = rand(1,12);
		adodb_date_test_date($y1,$m);

		$y1 = 3000-rand(0,3000-1970);
		adodb_date_test_date($y1,$m);
	}
	print '<p>';
	$start = 1960+rand(0,10);
	$yrs = 12;
	$i = 365.25*86400*($start-1970);
	$offset = 36000+rand(10000,60000);
	$max = 365*$yrs*86400;
	$lastyear = 0;

	// we generate a timestamp, convert it to a date, and convert it back to a timestamp
	// and check if the roundtrip broke the original timestamp value.
	print "Testing $start to ".($start+$yrs).", or $max seconds, offset=$offset: ";
	$cnt = 0;
	for ($max += $i; $i < $max; $i += $offset) {
		$ret = adodb_date('m,d,Y,H,i,s',$i);
		$arr = explode(',',$ret);
		if ($lastyear != $arr[2]) {
			$lastyear = $arr[2];
			print " $lastyear ";
			flush();
		}
		$newi = adodb_mktime($arr[3],$arr[4],$arr[5],$arr[0],$arr[1],$arr[2]);
		if ($i != $newi) {
			print "Error at $i, adodb_mktime returned $newi ($ret)";
			$fail = true;
			break;
		}
		$cnt += 1;
	}
	echo "Tested $cnt dates<br>";
	if (!$fail) print "<p>Passed !</p>";
	else print "<p><b>Failed</b> :-(</p>";
}

function adodb_time()
{
	$d = new DateTime();
	return $d->format('U');
}

/**
	Returns day of week, 0 = Sunday,... 6=Saturday.
	Algorithm from PEAR::Date_Calc
*/
function adodb_dow($year, $month, $day)
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
 checks for leap year, returns true if it is. Has 2-digit year check
*/
function adodb_is_leap_year($year)
{
	return  _adodb_is_leap_year(adodb_year_digit_check($year));
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

function adodb_get_gmt_diff_ts($ts)
{
	if (0 <= $ts && $ts <= 0x7FFFFFFF) { // check if number in 32-bit signed range) {
		$arr = getdate($ts);
		$y = $arr['year'];
		$m = $arr['mon'];
		$d = $arr['mday'];
		return adodb_get_gmt_diff($y,$m,$d);
	} else {
		return adodb_get_gmt_diff(false,false,false);
	}

}

/**
 get local time zone offset from GMT. Does not handle historical timezones before 1970.
*/
function adodb_get_gmt_diff($y,$m,$d)
{
static $TZ,$tzo;
global $ADODB_DATETIME_CLASS;

	if (!defined('ADODB_TEST_DATES')) $y = false;
	else if ($y < 1970 || $y >= 2038) $y = false;

	if ($ADODB_DATETIME_CLASS && $y !== false) {
		$dt = new DateTime();
		$dt->setISODate($y,$m,$d);
		if (empty($tzo)) {
			$tzo = new DateTimeZone(date_default_timezone_get());
		#	$tzt = timezone_transitions_get( $tzo );
		}
		return -$tzo->getOffset($dt);
	} else {
		if (isset($TZ)) return $TZ;
		$y = date('Y');
		/*
		if (function_exists('date_default_timezone_get') && function_exists('timezone_offset_get')) {
			$tzonename = date_default_timezone_get();
			if ($tzonename) {
				$tobj = new DateTimeZone($tzonename);
				$TZ = -timezone_offset_get($tobj,new DateTime("now",$tzo));
			}
		}
		*/
		if (empty($TZ)) $TZ = mktime(0,0,0,12,2,$y) - gmmktime(0,0,0,12,2,$y);
	}
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

/*
// generate $YRS table for _adodb_getdate()
function adodb_date_gentable($out=true)
{

	for ($i=1970; $i >= 1600; $i-=10) {
		$s = adodb_gmmktime(0,0,0,1,1,$i);
		echo "$i => $s,<br>";
	}
}
adodb_date_gentable();

for ($i=1970; $i > 1500; $i--) {

echo "<hr />$i ";
	adodb_date_test_date($i,1,1);
}

*/


$_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
$_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

function adodb_validdate($y,$m,$d)
{
global $_month_table_normal,$_month_table_leaf;

	if (_adodb_is_leap_year($y)) $marr = $_month_table_leaf;
	else $marr = $_month_table_normal;

	if ($m > 12 || $m < 1) return false;

	if ($d > 31 || $d < 1) return false;

	if ($marr[$m] < $d) return false;

	if ($y < 1000 && $y > 3000) return false;

	return true;
}

/**
	Low-level function that returns the getdate() array. We have a special
	$fast flag, which if set to true, will return fewer array values,
	and is much faster as it does not calculate dow, etc.
*/
function _adodb_getdate($origd=false,$fast=false,$is_gmt=false)
{
static $YRS;
global $_month_table_normal,$_month_table_leaf;

	$d =  $origd - ($is_gmt ? 0 : adodb_get_gmt_diff_ts($origd));
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

		# old algorithm iterates through all years. new algorithm does it in
		# 10 year blocks

		/*
		# old algo
		for ($a = 1970 ; --$a >= 0;) {
			$lastd = $d;

			if ($leaf = _adodb_is_leap_year($a)) $d += $d366;
			else $d += $d365;

			if ($d >= 0) {
				$year = $a;
				break;
			}
		}
		*/

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


	$dow = adodb_dow($year,$month,$day);

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
/*
		if ($isphp5)
				$dates .= sprintf('%s%04d',($gmt<=0)?'+':'-',abs($gmt)/36);
			else
				$dates .= sprintf('%s%04d',($gmt<0)?'+':'-',abs($gmt)/36);
			break;*/
function adodb_tz_offset($gmt,$isphp5)
{
	$zhrs = abs($gmt)/3600;
	$hrs = floor($zhrs);
	if ($isphp5)
		return sprintf('%s%02d%02d',($gmt<=0)?'+':'-',floor($zhrs),($zhrs-$hrs)*60);
	else
		return sprintf('%s%02d%02d',($gmt<0)?'+':'-',floor($zhrs),($zhrs-$hrs)*60);
}


function adodb_gmdate($fmt,$d=false)
{
	return adodb_date($fmt,$d,true);
}

// accepts unix timestamp and iso date format in $d
function adodb_date2($fmt, $d=false, $is_gmt=false)
{
	if ($d !== false) {
		if (!preg_match(
			"|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ -]?(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|",
			($d), $rr)) return adodb_date($fmt,false,$is_gmt);

		if ($rr[1] <= 100 && $rr[2]<= 1) return adodb_date($fmt,false,$is_gmt);

		// h-m-s-MM-DD-YY
		if (!isset($rr[5])) $d = adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1],false,$is_gmt);
		else $d = @adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1],false,$is_gmt);
	}

	return adodb_date($fmt,$d,$is_gmt);
}


/**
	Return formatted date based on timestamp $d
*/
function adodb_date($fmt,$d=false,$is_gmt=false)
{
static $daylight;
global $ADODB_DATETIME_CLASS;
static $jan1_1971;


	if (!isset($daylight)) {
		$daylight = function_exists('adodb_daylight_sv');
		if (empty($jan1_1971)) $jan1_1971 = mktime(0,0,0,1,1,1971); // we only use date() when > 1970 as adodb_mktime() only uses mktime() when > 1970
	}

	if ($d === false) return ($is_gmt)? @gmdate($fmt): @date($fmt);
	if (!defined('ADODB_TEST_DATES')) {
		if ((abs($d) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range

			if (!defined('ADODB_NO_NEGATIVE_TS') || $d >= $jan1_1971) // if windows, must be +ve integer
				return ($is_gmt)? @gmdate($fmt,$d): @date($fmt,$d);

		}
	}
	$_day_power = 86400;

	$arr = _adodb_getdate($d,true,$is_gmt);

	if ($daylight) adodb_daylight_sv($arr, $is_gmt);

	$year = $arr['year'];
	$month = $arr['mon'];
	$day = $arr['mday'];
	$hour = $arr['hours'];
	$min = $arr['minutes'];
	$secs = $arr['seconds'];

	$max = strlen($fmt);
	$dates = '';

	$isphp5 = PHP_VERSION >= 5;

	/*
		at this point, we have the following integer vars to manipulate:
		$year, $month, $day, $hour, $min, $secs
	*/
	for ($i=0; $i < $max; $i++) {
		switch($fmt[$i]) {
		case 'e':
			$dates .= date('e');
			break;
		case 'T':
			if ($ADODB_DATETIME_CLASS) {
				$dt = new DateTime();
				$dt->SetDate($year,$month,$day);
				$dates .= $dt->Format('T');
			} else
				$dates .= date('T');
			break;
		// YEAR
		case 'L': $dates .= $arr['leap'] ? '1' : '0'; break;
		case 'r': // Thu, 21 Dec 2000 16:01:07 +0200

			// 4.3.11 uses '04 Jun 2004'
			// 4.3.8 uses  ' 4 Jun 2004'
			$dates .= gmdate('D',$_day_power*(3+adodb_dow($year,$month,$day))).', '
				. ($day<10?'0'.$day:$day) . ' '.date('M',mktime(0,0,0,$month,2,1971)).' '.$year.' ';

			if ($hour < 10) $dates .= '0'.$hour; else $dates .= $hour;

			if ($min < 10) $dates .= ':0'.$min; else $dates .= ':'.$min;

			if ($secs < 10) $dates .= ':0'.$secs; else $dates .= ':'.$secs;

			$gmt = adodb_get_gmt_diff($year,$month,$day);

			$dates .= ' '.adodb_tz_offset($gmt,$isphp5);
			break;

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
		case 'w': $dates .= adodb_dow($year,$month,$day); break;
		case 'l': $dates .= gmdate('l',$_day_power*(3+adodb_dow($year,$month,$day))); break;
		case 'D': $dates .= gmdate('D',$_day_power*(3+adodb_dow($year,$month,$day))); break;
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
			$dates .= ($is_gmt) ? 0 : -adodb_get_gmt_diff($year,$month,$day); break;
		case 'O':
			$gmt = ($is_gmt) ? 0 : adodb_get_gmt_diff($year,$month,$day);

			$dates .= adodb_tz_offset($gmt,$isphp5);
			break;

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
	Returns a timestamp given a GMT/UTC time.
	Note that $is_dst is not implemented and is ignored.
*/
function adodb_gmmktime($hr,$min,$sec,$mon=false,$day=false,$year=false,$is_dst=false)
{
	return adodb_mktime($hr,$min,$sec,$mon,$day,$year,$is_dst,true);
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
		$usephpfns = (1970 < $year && $year < 2038
			|| !defined('ADODB_NO_NEGATIVE_TS') && (1901 < $year && $year < 2038)
			);


		if ($usephpfns && ($year + $mon/12+$day/365.25+$hr/(24*365.25) >= 2038)) $usephpfns = false;

		if ($usephpfns) {
				return $is_gmt ?
					@gmmktime($hr,$min,$sec,$mon,$day,$year):
					@mktime($hr,$min,$sec,$mon,$day,$year);
		}
	}

	$gmt_different = ($is_gmt) ? 0 : adodb_get_gmt_diff($year,$mon,$day);

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
		$y = floor(($mon-1)/ 12);
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

function adodb_gmstrftime($fmt, $ts=false)
{
	return adodb_strftime($fmt,$ts,true);
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
	/*
		$tstr = strtoupper(gmstrftime('%c',31366800)); // 30 Dec 1970, 1 am
		$sep = substr($tstr,2,1);
		$hasAM = strrpos($tstr,'M') !== false;
	*/
		# see http://phplens.com/lens/lensforum/msgs.php?id=14865 for reasoning, and changelog for version 0.24
		$dstr = gmstrftime('%x',31366800); // 30 Dec 1970, 1 am
		$sep = substr($dstr,2,1);
		$tstr = strtoupper(gmstrftime('%X',31366800)); // 30 Dec 1970, 1 am
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
			case 'u': $fmtdate .= '?u'; $parseu = true; break; // wrong strftime=1-based, date=0-based
			case 'U': $fmtdate .= '?U'; $parseU = true; break;// wrong strftime=1-based, date=0-based
			case 'x': $fmtdate .= $ADODB_DATE_LOCALE[0]; break;
			case 'X': $fmtdate .= $ADODB_DATE_LOCALE[1]; break;
			case 'w': $fmtdate .= '?w'; $parseu = true; break; // wrong strftime=1-based, date=0-based
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
