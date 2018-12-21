// Copyright (C) 2011-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
// Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@inodbox.com>
// Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/
//

//
// \file       htdocs/core/js/dst.js
// \brief      File that include javascript functions for detect user tz, tz_string, dst_observed, dst_first, dst_second,
//             screenwidth and screenheight
//

$(document).ready(function () {

    var timezone = jstz.determine();

    // Detect and save TZ and DST
	var rightNow = new Date();
	var jan1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);
	var temp = jan1.toGMTString();
	var jan2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
	var std_time_offset = (jan1 - jan2) / (1000 * 60 * 60);
	var june1 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0);
	temp = june1.toGMTString();
	var june2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
	var daylight_time_offset = (june1 - june2) / (1000 * 60 * 60);
	var dst;
	if (std_time_offset == daylight_time_offset) {
	    dst = "0"; // daylight savings time is NOT observed
	} else {
	    dst = "1"; // daylight savings time is observed
	}
	var now=new Date();
	//alert('date=' + now + ' string=' + now.toTimeString());
	var dst_first=DisplayDstSwitchDates('first');
	var dst_second=DisplayDstSwitchDates('second');
	//alert(dst);
	$('#tz').val(std_time_offset);   				  					// returns TZ
    $('#tz_string').val(timezone.name());		// returns TZ string
	$('#dst_observed').val(dst);   				  						// returns if DST is observed on summer
	$('#dst_first').val(dst_first);   									// returns DST first switch in year
	$('#dst_second').val(dst_second);   								// returns DST second switch in year
	// Detect and save screen resolution
	$('#screenwidth').val($(window).width());   	// returns width of browser viewport
	$('#screenheight').val($(window).height());   	// returns width of browser viewport
});

function DisplayDstSwitchDates(firstsecond)
{
    var year = new Date().getYear();
    if (year < 1000) year += 1900;

    var firstSwitch = 0;
    var secondSwitch = 0;
    var lastOffset = 99;

    // Loop through every month of the current year
    for (i = 0; i < 12; i++)
    {
        // Fetch the timezone value for the month
        var newDate = new Date(Date.UTC(year, i, 0, 0, 0, 0, 0));
        var tz = -1 * newDate.getTimezoneOffset() / 60;

        // Capture when a timzezone change occurs
        if (tz > lastOffset)
            firstSwitch = i-1;
        else if (tz < lastOffset)
            secondSwitch = i-1;

        lastOffset = tz;
    }

    // Go figure out date/time occurences a minute before
    // a DST adjustment occurs
    var secondDstDate = FindDstSwitchDate(year, secondSwitch);
    var firstDstDate = FindDstSwitchDate(year, firstSwitch);

	if (firstsecond == 'first') return firstDstDate;
	if (firstsecond == 'second') return secondDstDate;

    if (firstDstDate == null && secondDstDate == null)
        return 'Daylight Savings is not observed in your timezone.';
    else
        return 'Last minute before DST change occurs in ' +
           year + ': ' + firstDstDate + ' and ' + secondDstDate;
}

function FindDstSwitchDate(year, month)
{
    // Set the starting date
    var baseDate = new Date(Date.UTC(year, month, 0, 0, 0, 0, 0));
    var changeDay = 0;
    var changeMinute = -1;
    var baseOffset = -1 * baseDate.getTimezoneOffset() / 60;
    var dstDate;

    // Loop to find the exact day a timezone adjust occurs
    for (day = 0; day < 50; day++)
    {
        var tmpDate = new Date(Date.UTC(year, month, day, 0, 0, 0, 0));
        var tmpOffset = -1 * tmpDate.getTimezoneOffset() / 60;

        // Check if the timezone changed from one day to the next
        if (tmpOffset != baseOffset)
        {
            var minutes = 0;
            changeDay = day;

            // Back-up one day and grap the offset
            tmpDate = new Date(Date.UTC(year, month, day-1, 0, 0, 0, 0));
            tmpOffset = -1 * tmpDate.getTimezoneOffset() / 60;

            // Count the minutes until a timezone chnage occurs
            while (changeMinute == -1)
            {
                tmpDate = new Date(Date.UTC(year, month, day-1, 0, minutes, 0, 0));
                tmpOffset = -1 * tmpDate.getTimezoneOffset() / 60;

                // Determine the exact minute a timezone change
                // occurs
                if (tmpOffset != baseOffset)
                {
                    // Back-up a minute to get the date/time just
                    // before a timezone change occurs
                    tmpOffset = new Date(Date.UTC(year, month,
                                         day-1, 0, minutes-1, 0, 0));
                    changeMinute = minutes;
                    break;
                }
                else
                    minutes++;
            }

            // Add a month (for display) since JavaScript counts
            // months from 0 to 11
            dstDate = tmpOffset.getMonth() + 1;

            // Pad the month as needed
            if (dstDate < 10) dstDate = "0" + dstDate;

            // Add the day and year
            dstDate = year + '-' + dstDate + '-' + tmpOffset.getDate() + 'T';

            // Capture the time stamp
            tmpDate = new Date(Date.UTC(year, month,
                               day-1, 0, minutes-1, 0, 0));
            dstDate += tmpDate.toTimeString().split(' ')[0] + 'Z';
            return dstDate;
        }
    }
}
