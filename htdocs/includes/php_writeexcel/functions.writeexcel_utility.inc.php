<?php

/*
 * Copyleft 2002 Johann Hanne
 *
 * This is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307 USA
 */

/*
 * This is the Spreadsheet::WriteExcel Perl package ported to PHP
 * Spreadsheet::WriteExcel was written by John McNamara, jmcnamara@cpan.org
 */

/*
 * Converts numeric $row/$col notation to an Excel cell reference string in
 * A1 notation.
 */
function xl_rowcol_to_cell($row, $col, $row_abs=false, $col_abs=false) {

    $row_abs = $row_abs ? '$' : '';
    $col_abs = $col_abs ? '$' : '';

    $int  = floor($col / 26);
    $frac = $col % 26;

    $chr1 = ''; // Most significant character in AA1

    if ($int > 0) {
        $chr1 = chr(ord('A') + $int - 1);
    }

    $chr2 = chr(ord('A') + $frac);

    // Zero index to 1-index
    $row++;

    return $col_abs.$chr1.$chr2.$row_abs.$row;
}

/*
 * Converts an Excel cell reference string in A1 notation
 * to numeric $row/$col notation.
 *
 * Returns: array($row, $col, $row_absolute, $col_absolute)
 *
 * The $row_absolute and $col_absolute parameters aren't documented because
 * they are mainly used internally and aren't very useful to the user.
 */
function xl_cell_to_rowcol($cell) {

    preg_match('/(\$?)([A-I]?[A-Z])(\$?)(\d+)/', $cell, $reg);

    $col_abs = ($reg[1] == "") ? 0 : 1;
    $col     = $reg[2];
    $row_abs = ($reg[3] == "") ? 0 : 1;
    $row     = $reg[4];

    // Convert base26 column string to number
    // All your Base are belong to us.
    $chars  = preg_split('//', $col, -1, PREG_SPLIT_NO_EMPTY);
    $expn   = 0;
    $col    = 0;

    while (sizeof($chars)>0) {
        $char = array_pop($chars); // Least significant character first
        $col += (ord($char) - ord('A') + 1) * pow(26, $expn);
        $expn++;
    }

    // Convert 1-index to zero-index
    $row--;
    $col--;

    return array($row, $col, $row_abs, $col_abs);
}

/*
 * Increments the row number of an Excel cell reference string
 * in A1 notation.
 * For example C4 to C5
 *
 * Returns: a cell reference string in A1 notation.
 */
function xl_inc_row($cell) {
    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);
    return xl_rowcol_to_cell(++$row, $col, $row_abs, $col_abs);
}

/*
 * Decrements the row number of an Excel cell reference string
 * in A1 notation.
 * For example C4 to C3
 *
 * Returns: a cell reference string in A1 notation.
 */
function xl_dec_row($cell) {
    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);
    return xl_rowcol_to_cell(--$row, $col, $row_abs, $col_abs);
}

/*
 * Increments the column number of an Excel cell reference string
 * in A1 notation.
 * For example C3 to D3
 *
 * Returns: a cell reference string in A1 notation.
 */
function xl_inc_col($cell) {
    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);
    return xl_rowcol_to_cell($row, ++$col, $row_abs, $col_abs);
}

/*
 * Decrements the column number of an Excel cell reference string
 * in A1 notation.
 * For example C3 to B3
 *
 * Returns: a cell reference string in A1 notation.
 */
function xl_dec_col($cell) {
    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);
    return xl_rowcol_to_cell($row, --$col, $row_abs, $col_abs);
}

function xl_date_list($year, $month=1, $day=1,
                      $hour=0, $minute=0, $second=0) {

    $monthdays=array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    // Leap years since 1900 (year is dividable by 4)
    $leapyears=floor(($year-1900)/4);

    // Non-leap years since 1900 (year is dividable by 100)
    $nonleapyears=floor(($year-1900)/100);

    // Non-non-leap years since 1900 (year is dividable by 400)
    // (Yes, it MUST be "1600", not "1900")
    $nonnonleapyears=floor(($year-1600)/400);

    // Don't count the leap day of the specified year if it didn't
    // happen yet (i.e. before 1 March)
    //
    // Please note that $leapyears becomes -1 for dates before 1 March 1900;
    // this is not logical, but later we will add a day for Excel's
    // phantasie leap day in 1900 without checking if the date is actually
    // after 28 February 1900; so these two logic errors "neutralize"
    // each other
    if ($year%4==0 && $month<3) {
      $leapyears--;
    }

    $days=365*($year-1900)+$leapyears-$nonleapyears+$nonnonleapyears;

    for ($c=1;$c<$month;$c++) {
      $days+=$monthdays[$c-1];
    }

    // Excel actually wants the days since 31 December 1899, not since
    // 1 January 1900; this will also add this extra day
    $days+=$day;

    // Excel treats 1900 erroneously as a leap year, so we must
    // add one day
    //
    // Please note that we DON'T have to check if the date is after
    // 28 February 1900, because for such dates $leapyears is -1
    // (see above)
    $days++;

    return (float)($days+($hour*3600+$minute*60+$second)/86400);
}

function xl_parse_time($time) {

    if (preg_match('/(\d{1,2}):(\d\d):?((?:\d\d)(?:\.\d+)?)?(?:\s+)?(am|pm)?/i', $time, $reg)) {

        $hours       = $reg[1];
        $minutes     = $reg[2];
        $seconds     = $reg[3] || 0;
        $meridian    = strtolower($reg[4]) || '';

        // Normalise midnight and midday
        if ($hours == 12 && $meridian != '') {
            $hours = 0;
        }

        // Add 12 hours to the pm times. Note: 12.00 pm has been set to 0.00.
        if ($meridian == 'pm') {
            $hours += 12;
        }

        // Calculate the time as a fraction of 24 hours in seconds
        return (float)(($hours*3600+$minutes*60+$seconds)/86400);

    } else {
        return false; // Not a valid time string
    }
}

/*
 * Automagically converts almost any date/time string to an Excel date.
 * This function will always only be as good as strtotime() is.
 */
function xl_parse_date($date) {

    $unixtime=strtotime($date);

    $year=date("Y", $unixtime);
    $month=date("m", $unixtime);
    $day=date("d", $unixtime);
    $hour=date("H", $unixtime);
    $minute=date("i", $unixtime);
    $second=date("s", $unixtime);

    // Convert to Excel date
    return xl_date_list($year, $month, $day, $hour, $minute, $second);
}

/*
 * Dummy function to be "compatible" to Spreadsheet::WriteExcel
 */
function xl_parse_date_init() {
    // Erm... do nothing...
    // strtotime() doesn't require anything to be initialized
    // (do not ask me how to set the timezone...)
}

/*
 * xl_decode_date_EU() and xl_decode_date_US() are mapped
 * to xl_parse_date(); there seems to be no PHP function that
 * differentiates between EU and US dates; I've never seen
 * somebody using dd/mm/yyyy anyway, it always should be one of:
 * - yyyy-mm-dd (international)
 * - dd.mm.yyyy (european)
 * - mm/dd/yyyy (english/US/british?)
*/

function xl_decode_date_EU($date) {
    return xl_parse_date($date);
}

function xl_decode_date_US($date) {
    return xl_parse_date($date);
}

function xl_date_1904($exceldate) {

    if ($exceldate < 1462) {
        // date is before 1904
        $exceldate = 0;
    } else {
        $exceldate -= 1462;
    }

    return $exceldate;
}

?>
