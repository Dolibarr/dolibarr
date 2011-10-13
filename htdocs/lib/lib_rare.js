// Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
//
// Script javascript that contains functions not frequently used
//
// \file       htdocs/lib/lib_rare.js
// \brief      File that include javascript functions not frequently used (included if option use_javascript activated)


// in [-]HH:MM format...
// won't yet work with non-even tzs
function fetchTimezone() 
{
	var localclock = new Date();
	// returns negative offset from GMT in minutes
	var tzRaw = localclock.getTimezoneOffset();
	var tzHour = Math.floor( Math.abs(tzRaw) / 60);
	var tzMin = Math.abs(tzRaw) % 60;
	var tzString = ((tzRaw >= 0) ? "-" : "") + ((tzHour < 10) ? "0" : "") + tzHour +
		":" + ((tzMin < 10) ? "0" : "") + tzMin;
	return tzString;
}
