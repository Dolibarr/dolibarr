<?php
/* Copyright (C) 2005-2018  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file		htdocs/core/js/lib_head.js.php
 * \brief		File that include javascript functions (included if option use_javascript activated)
 * 				JQuery (providing object $) and JQuery-UI (providing $datepicker) libraries must be loaded before this file.
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

require_once '../../main.inc.php';


/*
 * View
 */

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}



// Define tradMonths javascript array (we define this in datepicker AND in parent page to avoid errors with IE8)
$tradMonths = array(
dol_escape_js($langs->transnoentitiesnoconv("Month01")),
dol_escape_js($langs->transnoentitiesnoconv("Month02")),
dol_escape_js($langs->transnoentitiesnoconv("Month03")),
dol_escape_js($langs->transnoentitiesnoconv("Month04")),
dol_escape_js($langs->transnoentitiesnoconv("Month05")),
dol_escape_js($langs->transnoentitiesnoconv("Month06")),
dol_escape_js($langs->transnoentitiesnoconv("Month07")),
dol_escape_js($langs->transnoentitiesnoconv("Month08")),
dol_escape_js($langs->transnoentitiesnoconv("Month09")),
dol_escape_js($langs->transnoentitiesnoconv("Month10")),
dol_escape_js($langs->transnoentitiesnoconv("Month11")),
dol_escape_js($langs->transnoentitiesnoconv("Month12"))
);

$tradMonthsShort = array(
$langs->trans("MonthShort01"),
$langs->trans("MonthShort02"),
$langs->trans("MonthShort03"),
$langs->trans("MonthShort04"),
$langs->trans("MonthShort05"),
$langs->trans("MonthShort06"),
$langs->trans("MonthShort07"),
$langs->trans("MonthShort08"),
$langs->trans("MonthShort09"),
$langs->trans("MonthShort10"),
$langs->trans("MonthShort11"),
$langs->trans("MonthShort12")
);

$tradDays = array(
$langs->trans("Sunday"),
$langs->trans("Monday"),
$langs->trans("Tuesday"),
$langs->trans("Wednesday"),
$langs->trans("Thursday"),
$langs->trans("Friday"),
$langs->trans("Saturday")
);

$tradDaysShort = array(
$langs->trans("ShortSunday"),
$langs->trans("ShortMonday"),
$langs->trans("ShortTuesday"),
$langs->trans("ShortWednesday"),
$langs->trans("ShortThursday"),
$langs->trans("ShortFriday"),
$langs->trans("ShortSaturday")
);

$tradDaysMin = array(
$langs->trans("SundayMin"),
$langs->trans("MondayMin"),
$langs->trans("TuesdayMin"),
$langs->trans("WednesdayMin"),
$langs->trans("ThursdayMin"),
$langs->trans("FridayMin"),
$langs->trans("SaturdayMin")
);


$dec = ',';
$thousand = ' ';
if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal") {
	$dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
}
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") {
	$thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
}
if ($thousand == 'Space') {
	$thousand = ' ';
}

?>
// Javascript libraries for Dolibarr ERP CRM (https://www.dolibarr.org)

// For jQuery date picker
var tradMonths = <?php echo json_encode($tradMonths) ?>;
var tradMonthsShort = <?php echo json_encode($tradMonthsShort) ?>;
var tradDays = <?php echo json_encode($tradDays) ?>;
var tradDaysShort = <?php echo json_encode($tradDaysShort) ?>;
var tradDaysMin = <?php echo json_encode($tradDaysMin) ?>;
var currencyCache = <?php echo json_encode($langs->cache_currencies) ?>;

// For JQuery date picker
$(document).ready(function() {
	$.datepicker.setDefaults({
		autoSize: true,
		changeMonth: true,
		changeYear: true,
		altField: '#timestamp',
		altFormat: '@'			// Gives a timestamp dateformat
	});
});

jQuery(function($){
	$.datepicker.regional['<?php echo $langs->defaultlang ?>'] = {
		closeText: '<?php echo $langs->trans("Close2") ?>',
		prevText: '<?php echo $langs->trans("Previous") ?>',
		nextText: '<?php echo $langs->trans("Next") ?>',
		currentText: '<?php echo $langs->trans("Now") ?>',
		monthNames: tradMonths,
		monthNamesShort: tradMonthsShort,
		dayNames: tradDays,
		dayNamesShort: tradDaysShort,
		dayNamesMin: tradDaysMin,
		weekHeader: '<?php echo $langs->trans("Week"); ?>',
		dateFormat: '<?php echo $langs->trans("FormatDateShortJQuery"); ?>',	/* Note dd/mm/yy means year on 4 digit in jquery format */
		firstDay: <?php echo(isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : '1'); ?>,
		isRTL: <?php echo($langs->trans("DIRECTION") == 'rtl' ? 'true' : 'false'); ?>,
		showMonthAfterYear: false,  	/* TODO add specific to country	*/
		 yearSuffix: ''			/* TODO add specific to country */
	};
	$.datepicker.setDefaults($.datepicker.regional['<?php echo $langs->defaultlang ?>']);
});



/**
 * Set array used for select2 translations
 */

var select2arrayoflanguage = {
	matches: function (matches) { return matches + " <?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2ResultFoundUseArrows")); ?>"; },
	noResults: function () { return "<?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2NotFound")); ?>"; },
	inputTooShort: function (input) {
		var n = input.minimum;
		/*console.log(input); console.log(input.minimum);*/
		if (n > 1) return "<?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2Enter")); ?> " + n + " <?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2MoreCharacters")); ?>";
			else return "<?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2Enter")); ?> " + n + " <?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2MoreCharacter")); ?>"
		},
	loadMore: function (pageNumber) { return "<?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2LoadingMoreResults")); ?>"; },
	searching: function () { return "<?php echo dol_escape_js($langs->transnoentitiesnoconv("Select2SearchInProgress")); ?>"; }
};


/**
 * For calendar input
 */

// Returns an object given an id
function getObjectFromID(id){
	var theObject;
	if(document.getElementById)
		theObject=document.getElementById(id);
	else
		theObject=document.all[id];
	return theObject;
}

// Called after selection of a date to save details into detailed fields
function dpChangeDay(dateFieldID, format)
{
	//showDP.datefieldID=dateFieldID;
	console.log("Call dpChangeDay, we save date into detailed fields from format = "+format);

	var thefield=getObjectFromID(dateFieldID);
	var thefieldday=getObjectFromID(dateFieldID+"day");
	var thefieldmonth=getObjectFromID(dateFieldID+"month");
	var thefieldyear=getObjectFromID(dateFieldID+"year");

	var date=getDateFromFormat(thefield.value, format);
	//console.log(date);
	if (date)
	{
		thefieldday.value=date.getDate();
		if(thefieldday.onchange) thefieldday.onchange.call(thefieldday);
		thefieldmonth.value=date.getMonth()+1;
		if(thefieldmonth.onchange) thefieldmonth.onchange.call(thefieldmonth);
		thefieldyear.value=date.getFullYear();
		if(thefieldyear.onchange) thefieldyear.onchange.call(thefieldyear);
	}
	else
	{
		thefieldday.value='';
		if(thefieldday.onchange) thefieldday.onchange.call(thefieldday);
		thefieldmonth.value='';
		if(thefieldmonth.onchange) thefieldmonth.onchange.call(thefieldmonth);
		thefieldyear.value='';
		if(thefieldyear.onchange) thefieldyear.onchange.call(thefieldyear);
	}
}

/*
 * =================================================================
 * Function: formatDate(javascript object Date(), format)
 * Purpose: Returns a date in the output format specified. The format string can use the following tags:
 * Year | yyyy (4 digits), yy (2 digits)
 * Month | MM (2 digits)
 * Day of Month | dd (2 digits)
 * Hour (1-12) | hh (2 digits) Hour (0-23) | HH (2 digits)
 * Minute | mm (2 digits)
 * Second | ss (2 digits)
 * Author: Laurent Destailleur Author: Matelli (see http://matelli.fr/showcases/patchs-dolibarr/update-date-input-in-action-form.html)
 * Licence: GPL
 * ==================================================================
 */
function formatDate(date,format)
{
	// alert('formatDate date='+date+' format='+format);

	// Force parameters en chaine
	format=format+"";

	var result="";

	var year=date.getFullYear();
	var month=date.getMonth()+1;
	var day=date.getDate();
	var hour=date.getHours();
	var minute=date.getMinutes();
	var seconde=date.getSeconds();

	var i=0;
	while (i < format.length)
	{
		c=format.charAt(i);	// Recupere char du format
		var substr = '';
		j=i;
		while ((format.charAt(j)==c) && (j < format.length))	// Recupere char successif identiques
		{
			substr += format.charAt(j++);
		}

		// alert('substr='+substr);
		if (substr == 'yyyy')      { result=result+year; }
		else if (substr == 'yy')   { result=result+year.substring(2,4); }
		else if (substr == 'M')    { result=result+month; }
		else if (substr == 'MM')   { result=result+(month<1||month>9?"":"0")+month; }
		else if (substr == 'd')    { result=result+day; }
		else if (substr == 'dd')   { result=result+(day<1||day>9?"":"0")+day; }
		else if (substr == 'hh')   { if (hour > 12) hour-=12; result=result+(hour<0||hour>9?"":"0")+hour; }
		else if (substr == 'HH')   { result=result+(hour<0||hour>9?"":"0")+hour; }
		else if (substr == 'mm')   { result=result+(minute<0||minute>9?"":"0")+minute; }
		else if (substr == 'ss')   { result=result+(seconde<0||seconde>9?"":"0")+seconde; }
		else { result=result+substr; }

		i+=substr.length;
	}

	// alert(result);
	return result;
}


/*
 * =================================================================
 * Function: getDateFromFormat(date_string, format_string)
 * Purpose:  This function takes a date string and a format string.
 * It parses the date string with format and it
 * returns the date as a javascript Date() object. If date does not match
 * format, it returns 0. The format string can use the following tags:
 * Field        | Tags
 * -------------+-----------------------------------
 * Year         | yyyy (4 digits), yy (2 digits)
 * Month        | MM (2 digits)
 * Day of Month | dd (2 digits)
 * Hour (1-12)  | hh (2 digits)
 * Hour (0-23)  | HH (2 digits)
 * Minute       | mm (2 digits)
 * Second       | ss (2 digits)
 * Author: Laurent Destailleur
 * Licence: GPL
 * ==================================================================
 */
function getDateFromFormat(val,format)
{
	// alert('getDateFromFormat val='+val+' format='+format);

	// Force parameters en chaine
	val=val+"";
	format=format+"";

	if (val == '') return 0;

	var now=new Date();
	var year=now.getFullYear();
	var month=now.getMonth()+1;
	var day=now.getDate();
	var hour=now.getHours();
	var minute=now.getMinutes();
	var seconde=now.getSeconds();

	var i=0;
	var d=0;    // -d- follows the date string while -i- follows the format
				// string

	while (i < format.length)
	{
		c=format.charAt(i);	// Recupere char du format
		substr="";
		j=i;
		while ((format.charAt(j)==c) && (j < format.length))	// Recupere char
																// successif
																// identiques
		{
			substr += format.charAt(j++);
		}

		// alert('substr='+substr);
		if (substr == "yyyy") year=getIntegerInString(val,d,4,4);
		if (substr == "yy")   year=""+(getIntegerInString(val,d,2,2)-0+2000); /*  #28334 */
		if (substr == "MM" ||substr == "M")
		{
			month=getIntegerInString(val,d,1,2);
			if (month) d -= 2- month.length;
		}
		if (substr == "dd")
		{
			day=getIntegerInString(val,d,1,2);
			if (day) d -= 2- day.length;
		}
		if (substr == "HH" ||substr == "hh" )
		{
			hour=getIntegerInString(val,d,1,2);
			if (dhouray) d -= 2- hour.length;
		}
		if (substr == "mm"){
			minute=getIntegerInString(val,d,1,2);
			if (minute) d -= 2- minute.length;
		}
		if (substr == "ss")
		{
			seconde=getIntegerInString(val,d,1,2);
			if (seconde) d -= 2- seconde.length;
		}

		i+=substr.length;
		d+=substr.length;
	}

	// Check if format param are ok
	if (year==null||year<1) { return 0; }
	if (month==null||(month<1)||(month>12)) { return 0; }
	if (day==null||(day<1)||(day>31)) { return 0; }
	if (hour==null||(hour<0)||(hour>24)) { return 0; }
	if (minute==null||(minute<0)||(minute>60)) { return 0; }
	if (seconde==null||(seconde<0)||(seconde>60)) { return 0; }

	// alert(year+' '+month+' '+day+' '+hour+' '+minute+' '+seconde);
	return new Date(year,month-1,day,hour,minute,seconde);
}

/*
 * =================================================================
 * Function: stringIsInteger(string)
 * Purpose:  Return true if string is an integer
 * ==================================================================
 */
function stringIsInteger(str)
{
	var digits="1234567890";
	for (var i=0; i < str.length; i++)
	{
		if (digits.indexOf(str.charAt(i))==-1)
		{
			return false;
		}
	}
	return true;
}

/*
 * =================================================================
 * Function: getIntegerInString(string,pos,minlength,maxlength)
 * Purpose:  Return part of string from position i that is integer
 * ==================================================================
 */
function getIntegerInString(str,i,minlength,maxlength)
{
	for (var x=maxlength; x>=minlength; x--)
	{
		var substr=str.substring(i,i+x);
		if (substr.length < minlength) { return null; }
		if (stringIsInteger(substr)) { return substr; }
	}
	return null;
}


/*
 * =================================================================
 * Purpose: Clean string to have it url encoded
 * Input:   s
 * Author:  Laurent Destailleur
 * Licence: GPL
 * ==================================================================
 */
function urlencode(s) {
	var news = s;
	news = news.replace(/\+/gi,'%2B');
	news = news.replace(/&/gi,'%26');
	return news;
}

/*
 * =================================================================
 * Purpose: Clean string to get a HTML coded string.
 * Input:   s
 * Author:  Laurent Destailleur
 * Licence: GPL
 * ==================================================================
 */
function htmlEntityDecodeJs(inp){
	var replacements = {'&lt;':'<','&gt;':'>','&sol;':'/','&quot;':'"','&apos;':'\'','&amp;':'&','&nbsp;':' '};
	if (inp) {
	  for(var r in replacements){
		inp = inp.replace(new RegExp(r,'g'),replacements[r]);
	  }
	  return inp.replace(/&#(\d+);/g, function(match, dec) {
		return String.fromCharCode(dec);
	  });
	} else {
		return '';
	}
}


/*
 * =================================================================
 * Purpose: Applique un delai avant execution. Used for autocompletion of companies.
 * Input:   funct, delay
 * Author:  Regis Houssin
 * Licence: GPL
 * ==================================================================
 */
 function ac_delay(funct,delay) {
	 // delay before start of action
	  setTimeout(funct,delay);
}


/*
 * =================================================================
 * Purpose:
 * Clean values of a "Sortable.serialize". Used by drag and drop.
 * Input:   expr
 * Author:  Regis Houssin
 * Licence: GPL
 * ==================================================================
 */
function cleanSerialize(expr) {
	if (typeof(expr) != 'string') {
		return '';
	}
	var reg = new RegExp("(&)", "g");
	var reg2 = new RegExp("[^A-Z0-9,]", "g");
	var liste1 = expr.replace(reg, ",");
	return liste1.replace(reg2, "");
}


/*
 * =================================================================
 * Purpose: Display a temporary message in input text fields (For showing help message on
 *          input field).
 * Input:   fieldId
 * Input:   message
 * Author:  Regis Houssin
 * Licence: GPL
 * ==================================================================
 */
function displayMessage(fieldId,message) {
	var textbox = document.getElementById(fieldId);
	if (textbox.value == '') {
		textbox.style.color = 'grey';
		textbox.value = message;
	}
}

/*
 * =================================================================
 * Purpose: Hide a temporary message in input text fields (For showing help message on
 *          input field).
 * Input:   fiedId
 * Input:   message
 * Author:  Regis Houssin
 * Licence: GPL
 * ==================================================================
 */
function hideMessage(fieldId,message) {
	var textbox = document.getElementById(fieldId);
	textbox.style.color = 'black';
	if (textbox.value == message) textbox.value = '';
}


/*
 * Used by button to set on/off.
 * Call url then make complementary action (like show/hide, enable/disable or set another option).
 *
 * @param	string	url			Url (warning: as any url called in ajax mode, the url called here must not renew the token)
 * @param	string	code		Code
 * @param	string	input		Array of complementary actions to do if success
 * @param	int		entity		Entity
 * @param	int		strict		Strict (0=?, 1=?)
 * @param   int     forcereload Force reload
 * @param   int     userid      User id
 * @param	int		value       Value to set
 * @param   string  token       Token
 * @return   boolean
 */
function setConstant(url, code, input, entity, strict, forcereload, userid, token, value) {
	var saved_url = url; /* avoid undefined url */
	$.post( url, {
		action: "set",
		name: code,
		entity: entity,
		token: token,
		value: value
	},
	function() {	/* handler for success of post */
		console.log("Ajax url request to set constant is a success. Make complementary actions and then forcereload="+forcereload+" value="+value);
		if (value == 0) {
			$("#set_" + code).show();
			$("#del_" + code).hide();
		} else {
			$("#set_" + code).hide();
			$("#del_" + code).show();
		}
		$.each(input, function(type, data) {
			// Enable another element
			if (type == "disabled" && strict != 1) {
				$.each(data, function(key, value) {
					var newvalue=((value.search("^#") < 0 && value.search("^\.") < 0) ? "#" : "") + value;
					$(newvalue).removeAttr("disabled");
					if ($(newvalue).hasClass("butActionRefused") == true) {
						$(newvalue).removeClass("butActionRefused");
						$(newvalue).addClass("butAction");
					}
				});
			} else if (type == "enabled") {
				$.each(data, function(key, value) {
					var newvalue=((value.search("^#") < 0 && value.search("^\.") < 0) ? "#" : "") + value;
					if (strict == 1)
						$(newvalue).removeAttr("disabled");
					else
						$(newvalue).attr("disabled", true);
					if ($(newvalue).hasClass("butAction") == true) {
						$(newvalue).removeClass("butAction");
						$(newvalue).addClass("butActionRefused");
					}
				});
			// Show another element
			} else if (type == "showhide" || type == "show") {
				$.each(data, function(key, value) {
					var newvalue=((value.search("^#") < 0 && value.search("^\.") < 0) ? "#" : "") + value;
					$(newvalue).show();
				});
			// Set another constant
			} else if (type == "set") {
				$.each(data, function(key, value) {
					$("#set_" + key).hide();
					$("#del_" + key).show();
					$.post( saved_url, {
						action: "set",
						name: key,
						value: value,
						entity: entity,
						token: token
					});
				});
			}
		});
		if (forcereload) {
			var url = window.location.href;
			if (url.indexOf('dol_resetcache') < 0) {
				if (url.indexOf('?') > -1) {
					url = url + "&dol_resetcache=1";
				} else {
					url = url + "?dol_resetcache=1";
				}
			}
			var page_y = $(document).scrollTop();
			url = url.replace(/page_y=\d+/g, '');
			if (page_y > 0) {
				if (url.indexOf('?') > -1) {
					url = url + "&page_y="+page_y;
				} else {
					url = url + "?page_y="+page_y;
				}
			}
			url = url.replace(/&&+/, '&');
			console.log("url ro redirect = "+url);

			window.location.href = url;
			//location.reload();
			return false;
		}
	}).fail(function(error) { console.log("Error, we force reload"); location.reload(); });	/* When it fails, we always force reload to have setEventErrorMessages in session visible */

	return true;
}

/*
 * Used by button to set on/off
 * Call url then make complementary action (like show/hide, enable/disable or set another option).
 *
 * @param	{string}	url			Url (warning: as any url called in ajax mode, the url called here must not renew the token)
 * @param	{string}	code		Code
 * @param	{string}	input		Array of complementary actions to do if success
 * @param	{int}		entity		Entity
 * @param	{int}		strict		Strict
 * @param   {int}     forcereload Force reload
 * @param   {int}     userid      User id
 * @param   {string}  token       Token
 * @return  boolean
 */
function delConstant(url, code, input, entity, strict, forcereload, userid, token) {
	var saved_url = url; /* avoid undefined url */
	$.post( url, {
		action: "del",
		name: code,
		entity: entity,
		token: token
	},
	function() {
		console.log("Ajax url request to delete constant is success. Make complementary actions and then forcereload="+forcereload);
		$("#del_" + code).hide();
		$("#set_" + code).show();
		$.each(input, function(type, data) {
			// Disable another element
			if (type == "disabled") {
				$.each(data, function(key, value) {
					var newvalue=((value.search("^#") < 0 && value.search("^\.") < 0) ? "#" : "") + value;
					$(newvalue).attr("disabled", true);
					if ($(newvalue).hasClass("butAction") == true) {
						$(newvalue).removeClass("butAction");
						$(newvalue).addClass("butActionRefused");
					}
				});
			} else if (type == "enabled" && strict != 1) {
				$.each(data, function(key, value) {
					var newvalue=((value.search("^#") < 0 && value.search("^\.") < 0) ? "#" : "") + value;
					$(newvalue).removeAttr("disabled");
					if ($(newvalue).hasClass("butActionRefused") == true) {
						$(newvalue).removeClass("butActionRefused");
						$(newvalue).addClass("butAction");
					}
				});
			// Hide another element
			} else if (type == "showhide" || type == "hide") {
				$.each(data, function(key, value) {
					var newvalue=((value.search("^#") < 0 && value.search("^\.") < 0) ? "#" : "") + value;
					$(newvalue).hide();
				});
			// Delete another constant
			} else if (type == "del") {
				$.each(data, function(key, value) {
					$("#del_" + value).hide();
					$("#set_" + value).show();
					$.post( saved_url, {
						action: "del",
						name: value,
						entity: entity,
						token: token
					});
				});
			}
		});
		if (forcereload) {
			var url = window.location.href;
			if (url.indexOf('dol_resetcache') < 0) {
				if (url.indexOf('?') > -1) {
					url = url + "&dol_resetcache=1";
				} else {
					url = url + "?dol_resetcache=1";
				}
			}
			var page_y = $(document).scrollTop();
			url = url.replace(/page_y=\d+/g, '');
			if (page_y > 0) {
				if (url.indexOf('?') > -1) {
					url = url + "&page_y="+page_y;
				} else {
					url = url + "?page_y="+page_y;
				}
			}
			url = url.replace(/&&+/, '&');
			console.log("url ro redirect = "+url);

			window.location.href = url;
			//location.reload();
			return false;
		}
	}).fail(function(error) { console.log("Error, we force reload"); location.reload(); });	/* When it fails, we always force reload to have setEventErrorMessages in session visible */

	return true;
}

/*
 * Call the setConstant or delConstant but with a confirmation before.
 * Used by button to set on/off.
 *
 * @param	string	action		Action
 * @param	string	url			Url
 * @param	string	code		Code
 * @param	string	input		Array of complementary actions to do if success
 * @param	string	box			Box
 * @param	int		entity		Entity
 * @param	int		yesButton	yesButton
 * @param	int		noButton	noButton
 * @param	int		strict		Strict
 * @param   int     userid      User id
 * @param   string  token       Token
 * @return  boolean
 */
function confirmConstantAction(action, url, code, input, box, entity, yesButton, noButton, strict, userid, token) {
	var boxConfirm = box;
	$("#confirm_" + code)
			.attr("title", boxConfirm.title)
			.html(boxConfirm.content)
			.dialog({
				resizable: false,
				height: 170,
				width: 500,
				modal: true,
				buttons: [
					{
						id : 'yesButton_' + code,
						text : yesButton,
						click : function() {
							if (action == "set") {
								setConstant(url, code, input, entity, strict, 0, userid, token, 1);
							} else if (action == "del") {
								delConstant(url, code, input, entity, strict, 0, userid, token);
							}
							// Close dialog
							$(this).dialog("close");
							// Execute another method
							if (boxConfirm.method) {
								var fnName = boxConfirm.method;
								if (window.hasOwnProperty(fnName)) {
									window[fnName]();
								}
							}
						}
					},
					{
						id : 'noButton_' + code,
						text : noButton,
						click : function() {
							$(this).dialog("close");
						}
					}
				]
			});
	// For information dialog box only, hide the noButton
	if (boxConfirm.info) {
		$("#noButton_" + code).button().hide();
	}

	return true;
}


/*
 * =================================================================
 * This is to allow to transform all select box into ajax autocomplete box
 * with just one line:
 * $(function() { $( "#idofmylist" ).combobox(); });
 * Do not use it on large combo boxes
 * =================================================================
 */
(function( $ ) {
	$.widget( "ui.combobox", {
		options: {
			minLengthToAutocomplete: 0
		},
		_create: function() {
			var savMinLengthToAutocomplete = this.options.minLengthToAutocomplete;
			var self = this,
				select = this.element.hide(),
				selected = select.children( ":selected" ),
				value = selected.val() ? selected.text() : "";
			var input = this.input = $( "<input>" )
				.insertAfter( select )
				.val( value )
				.attr('id', 'inputautocomplete'+select.attr('id'))
				.autocomplete({
					delay: 0,
					minLength: this.options.minLengthToAutocomplete,
					source: function( request, response ) {
						var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
						response( select.children( "option:enabled" ).map(function() {
							var text = $( this ).text();
							if ( this.value && ( !request.term || matcher.test(text) ) )
								return {
									label: text.replace(
										new RegExp(
											"(?![^&;]+;)(?!<[^<>]*)(" +
											$.ui.autocomplete.escapeRegex(request.term) +
											")(?![^<>]*>)(?![^&;]+;)", "gi"
										), "<strong>$1</strong>" ),
									value: text,
									option: this
								};
						}) );
					},
					select: function( event, ui ) {
						ui.item.option.selected = true;
						self._trigger( "selected", event, {
							item: ui.item.option
						});
					},
					change: function( event, ui ) {
						if ( !ui.item ) {
							var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
								valid = false;
							select.children( "option" ).each(function() {
								if ( $( this ).text().match( matcher ) ) {
									this.selected = valid = true;
									return false;
								}
							});
							if ( !valid ) {
								// remove invalid value, as it didn't match anything
								$( this ).val( "" );
								select.val( "" );
								input.data("ui-autocomplete").term = "";
								return false;
							}
						}
					}
				})
				.addClass( "ui-widget ui-widget-content ui-corner-left dolibarrcombobox" );

			input.data("ui-autocomplete")._renderItem = function( ul, item ) {
				return $("<li>")
					.data( "ui-autocomplete-item", item ) // jQuery UI > 1.10.0
					.append( "<a>" + item.label + "</a>" )
					.appendTo( ul );
			};

			this.button = $( "<button type=\'button\'>&nbsp;</button>" )
				.attr( "tabIndex", -1 )
				.attr( "title", "Show All Items" )
				.insertAfter( input )
				.button({
					icons: {
						primary: "ui-icon-triangle-1-s"
					},
					text: false
				})
				.removeClass( "ui-corner-all" )
				.addClass( "ui-corner-right ui-button-icon" )
				.click(function() {
					// close if already visible
					if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
						input.autocomplete( "close" );
						return;
					}

					// pass empty string as value to search for, displaying all results
					input.autocomplete({ minLength: 0 });
					input.autocomplete( "search", "" );
					input.autocomplete({ minLength: savMinLengthToAutocomplete });
					input.focus();
				});
		},

		destroy: function() {
			this.input.remove();
			this.button.remove();
			this.element.show();
			$.Widget.prototype.destroy.call( this );
		}
	});
})( jQuery );



/**
 * Function to output a dialog box for copy/paste
 *
 * @param	text	Text to put into copy/paste area
 * @param	text2	Text to put under the copy/paste area
 */
function copyToClipboard(text,text2)
{
	text = text.replace(/<br>/g,"\n");
	var newElem = '<textarea id="coordsforpopup" style="border: none; width: 90%; height: 120px;">'+text+'</textarea><br><br>'+text2;
	/* alert(newElem); */
	$("#dialogforpopup").html(newElem);
	$("#dialogforpopup").dialog();
	$("#coordsforpopup").select();

	return false;
}


/**
 * Show a popup HTML page. Use the "window.open" function.
 *
 * @param	url			Url
 * @param	title  		Title of popup
 * @return	boolean		False
 * @see document_preview()
 */
function newpopup(url, title) {
	var argv = newpopup.arguments;
	var argc = newpopup.arguments.length;
	var tmp = url;
	console.log("newpopup "+argv[2]+" "+argv[3]);
	var l = (argc > 2) ? argv[2] : 600;
	var h = (argc > 3) ? argv[3] : 400;
	var left = (screen.width - l)/2;
	var top = (screen.height - h)/2;
	var wfeatures = "directories=0,menubar=0,status=0,resizable=0,scrollbars=1,toolbar=0,location=0,width=" + l +",height=" + h + ",left=" + left + ",top=" + top;
	fen = window.open(tmp, title, wfeatures);

	return false;
}

/**
 * Function show document preview. It uses the "dialog" function.
 * The a tag around the img must have the src='', class='documentpreview', mime='image/xxx', target='_blank' from getAdvancedPreviewUrl().
 *
 * @param 	file 		Url
 * @param 	type 		Mime file type ("image/jpeg", "application/pdf", "text/html")
 * @param 	title		Title of popup
 * @return	void
 * @see newpopup()
 */
function document_preview(file, type, title)
{
	var ValidImageTypes = ["image/gif", "image/jpeg", "image/png", "image/webp"];
	var showOriginalSizeButton = false;

	console.log("document_preview A click was done: file="+file+", type="+type+", title="+title);

	if ($.inArray(type, ValidImageTypes) < 0) {
		/* Not an image */
		var width='85%';
		var object_width='100%';
		var height = ($( window ).height() - 60) * 0.90;
		var object_height='98%';

		show_preview('notimage');

	} else {
		/* This is an image */
		var object_width=0;
		var object_height=0;

		var img = new Image();

		img.onload = function() {
			object_width = this.width;
			object_height = this.height;

			width = $( window ).width()*0.90;
			console.log("object_width="+object_width+" window width="+width);
			if(object_width < width){
				console.log("Object width is small, we set width of popup according to image width.");
				width = object_width + 30
			}
			height = $( window ).height()*0.85;
			console.log("object_height="+object_height+" window height="+height);
			if(object_height < height){
				console.log("Object height is small, we set height of popup according to image height.");
				height = object_height + 80
			}
			else
			{
				showOriginalSizeButton = true;
			}

			show_preview('image');

		};
		img.src = file;

	}

	function show_preview(mode) {
		/* console.log("mode="+mode+" file="+file+" type="+type+" width="+width+" height="+height); */
		var newElem = '<object name="objectpreview" data="'+file+'" type="'+type+'" width="'+object_width+'" height="'+object_height+'" param="noparam"></object>';

		optionsbuttons = {}
		if (mode == 'image' && showOriginalSizeButton)
		{
			var curRot = 0;
			optionsbuttons = {
				"<?php echo dol_escape_js($langs->transnoentitiesnoconv("OriginalSize")); ?>": function() { console.log("Click on original size"); jQuery(".ui-dialog-content.ui-widget-content > object").css({ "max-height": "none" }); },
				"<?php echo dol_escape_js($langs->transnoentitiesnoconv("RotateImage")); ?>": function() { curRot += 90; jQuery(".ui-dialog-content.ui-widget-content > object").css("transform","rotate(" + curRot + "deg)"); },
				"<?php echo dol_escape_js($langs->transnoentitiesnoconv("CloseWindow")); ?>": function() { $( this ).dialog( "close" ); }
				};
		}

		$("#dialogforpopup").html(newElem);

		$("#dialogforpopup").dialog({
			closeOnEscape: true,
			resizable: true,
			width: width,
			height: height,
			modal: true,
			title: title,
			buttons: optionsbuttons
		});

		if (showOriginalSizeButton)
		{
			jQuery(".ui-dialog-content.ui-widget-content > object").css({ "max-height": "100%", "width": "auto", "margin-left": "auto", "margin-right": "auto", "display": "block" });
		}
	}
}

/*
 * Provide a function to get an URL GET parameter in javascript
 *
 * @param 	name				Name of parameter
 * @param	valueifnotfound		Value if not found
 * @return	string				Value
 */
function getParameterByName(name, valueifnotfound)
{
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
		results = regex.exec(location.search);
	return results === null ? valueifnotfound : decodeURIComponent(results[1].replace(/\+/g, " "));
}


// Code in the public domain from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round
(function() {
	/**
	 * Decimal adjustment of a number.
	 *
	 * @param {String}  type  The type of adjustment.
	 * @param {Number}  value The number.
	 * @param {Integer} exp   The exponent (the 10 logarithm of the adjustment base).
	 * @returns {Number} The adjusted value.
	 */
	function decimalAdjust(type, value, exp) {
		// If the exp is undefined or zero...
		if (typeof exp === 'undefined' || +exp === 0) {
			return Math[type](value);
		}
		value = +value;
		exp = +exp;
		// If the value is not a number or the exp is not an integer...
		if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
			return NaN;
		}
		// Shift
		value = value.toString().split('e');
		value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
		// Shift back
		value = value.toString().split('e');
		return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
	}

	// Decimal round
	if (!Math.round10) {
		Math.round10 = function(value, exp) {
			return decimalAdjust('round', value, exp);
		};
	}
	// Decimal floor
	if (!Math.floor10) {
		Math.floor10 = function(value, exp) {
			return decimalAdjust('floor', value, exp);
		};
	}
	// Decimal ceil
	if (!Math.ceil10) {
		Math.ceil10 = function(value, exp) {
			return decimalAdjust('ceil', value, exp);
		};
	}
})();

// Another solution, easier, to build a javascript rounding function
function dolroundjs(number, decimals) { return +(Math.round(number + "e+" + decimals) + "e-" + decimals); }

/**
 * Function similar to PHP price()
 *
 * Example use:
 *   pricejs(13312.448, 'MT', 'EUR', 'fr_FR')
 *   // (depending on conf for 'MT'): '13 312.45 €'
 *
 *   pricejs(343000.121, 'MT')
 *   // assuming conf for 'MT' is 2 and $langs->defaultlang is 'en_US': '343,000.12'
 *
 * @param  {number|string} amount    The amount to show
 * @param  {string} mode             'MT' or 'MU'
 * @param  {string} currency_code    ISO code of currency (empty by default)
 * @param  {string} force_locale     ISO code locale to use (if empty, will use Dolibarr's current locale code)
 * @return {string}                  The amount with digits
 *
 */
function pricejs(amount, mode = 'MT', currency_code = '', force_locale = '') {
	var main_max_dec_shown = <?php echo (int) str_replace('.', '', getDolGlobalString('MAIN_MAX_DECIMALS_SHOWN')); ?>;
	var main_rounding_unit = <?php echo (int) getDolGlobalInt('MAIN_MAX_DECIMALS_UNIT'); ?>;
	var main_rounding_tot = <?php echo (int) getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'); ?>;
	var main_decimal_separator = <?php echo json_encode($dec) ?>;
	var main_thousand_separator = <?php echo json_encode($thousand) ?>;
	var locale_code = force_locale || <?php echo json_encode($langs->defaultlang) ?>;
	var amountAsLocalizedString;
	var useIntl = Boolean(Intl && Intl.NumberFormat);
	var nDigits;
	if (currency_code === 'auto') currency_code = <?php echo json_encode($conf->currency) ?>;

	if (mode === 'MU') nDigits = main_rounding_unit;
	else if (mode === 'MT') nDigits = main_rounding_tot;
	else return 'Bad value for parameter mode';

	if (useIntl) {
		// simple version: let the browser decide how to format the number using the provided language / currency
		// parameters
		var formattingOptions = {
			minimumFractionDigits: nDigits,
			maximumFractionDigits: nDigits
		};
		if (currency_code) {
			formattingOptions['style'] = 'currency';
			formattingOptions['currency'] = currency_code;
		}
		return Intl.NumberFormat(locale_code.replace('_', '-'), formattingOptions).format(amount);
	}

	// No Intl -> attempt to format the number in a way similar to Dolibarr PHP's `price()` function
	amountAsLocalizedString = amount.toFixed(nDigits).replace(
		/((?!^)(?:\d{3})*)(?:\.(\d+))?$/,
		(fullMatch, digitsByThree, decimals) =>
			digitsByThree.replace(
				/\d{3}/g,
				(groupOfThree) => main_thousand_separator + groupOfThree
			) + (decimals !== undefined ? main_decimal_separator + decimals : '')
	).replace(/ /, ' ');
	if (!currency_code) return amountAsLocalizedString;

	// print with currency
	var currency_symbol = currency_code;

	// codes of languages / currencies where the symbol must be placed before the amount
	var currencyBeforeAmountCodes = {
		currency: ['AUD', 'CAD', 'CNY', 'COP', 'CLP', 'GBP', 'HKD', 'MXN', 'PEN', 'USD'],
		language: ['nl_NL']
	};

	if (currencyCache[currency_code]
		&& currencyCache[currency_code]['unicode']
		&& currencyCache[currency_code]['unicode'].length) {
		currency_symbol = currencyCache[currency_code]['unicode'].reduce(function (res, cur) {return res + cur}, '');
	}

	if (currencyBeforeAmountCodes.currency.indexOf(currency_code) >= 0
		|| currencyBeforeAmountCodes.language.indexOf(locale_code)) {
		// if we use a language or a currency where the symbol is placed before the amount
		return currency_symbol + amountAsLocalizedString;
	}

	// by default: currency symbol after the amount
	return amountAsLocalizedString + ' ' + currency_symbol;
}

/**
 * Function similar to PHP price2num()
 *
 * @param  {number|string} amount    The amount to convert/clean
 * @return {number}                  The amount in universal numeric format (Example: '99.99999')
 * @todo Implement rounding parameter
 */
function price2numjs(amount) {
	if (amount == '') return '';

	var dec = <?php echo json_encode($dec) ?>;
	var thousand = <?php echo json_encode($thousand) ?>;

	var main_max_dec_shown = <?php echo (int) str_replace('.', '', getDolGlobalString('MAIN_MAX_DECIMALS_SHOWN')); ?>;
	var main_rounding_unit = <?php echo (int) getDolGlobalInt('MAIN_MAX_DECIMALS_UNIT'); ?>;
	var main_rounding_tot = <?php echo (int) getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'); ?>;

	var amount = amount.toString();

	// rounding for unit price
	var rounding = main_rounding_unit;
	var pos = amount.indexOf(dec);
	var decpart = '';
	if (pos >= 0) {
		decpart = amount.substring(pos + 1).replace('/0+$/i', '');    // Remove 0 for decimal part
	}
	var nbdec = decpart.length;
	if (nbdec > rounding) {
		rounding = nbdec;
	}
	// If rounding higher than max shown
	if (rounding > main_max_dec_shown) rounding = main_max_dec_shown;
	if (thousand != ',' && thousand != '.') amount = amount.replace(',', '.');
	amount = amount.replace(' ', '');             // To avoid spaces
	amount = amount.replace(thousand, '');        // Replace of thousand before replace of dec to avoid pb if thousand is .
	amount = amount.replace(dec, '.');

	//console.log("amount before="+amount+" rounding="+rounding)
	var res = Math.round10(amount, - rounding);
	// Other solution is
	// var res = dolroundjs(amount, rounding)

	console.log("price2numjs text="+amount+" return="+res);

	return res;
}


<?php
if (!getDolGlobalString('MAIN_DISABLE_JQUERY_JNOTIFY') && !defined('DISABLE_JQUERY_JNOTIFY')) {
	?>
// Defined properties for JNotify
$(document).ready(function() {
	if (typeof $.jnotify == 'function') {
		$.jnotify.setup({
			delay: 3000									// the default time to show each notification (in milliseconds)
			, sticky: false								// determines if the message should be considered "sticky" (user must manually close notification)
			, closeLabel: "&times;"						// the HTML to use for the "Close" link
			, showClose: true							// determines if the "Close" link should be shown if notification is also sticky
			, fadeSpeed: 1000							// the speed to fade messages out (in milliseconds)
			, slideSpeed: 250                           // the speed used to slide messages out (in milliseconds)
			, classContainer: "jnotify-container"
			, classNotification: "jnotify-notification"
			, classBackground: "jnotify-background"
			, classClose: "jnotify-close"
			, classMessage: "jnotify-message"
			, init: null                                // callback that occurs when the main jnotify container is created
			, create: null                              // callback that occurs when when the note is created (occurs just before appearing in DOM)
			, beforeRemove: null                        // callback that occurs when before the notification starts to fade away
		});
	}
});
	<?php
} ?>



jQuery(document).ready(function() {
	// Force to hide menus when page is inside an iFrame so we can show any page into a dialog popup
	if (window.location && window.location.pathname.indexOf("externalsite/frametop.php") == -1 && window.location !== window.parent.location ) {
		console.log("Page is detected to be into an iframe, we hide by CSS the menus");
		// The page is in an iframe
		jQuery(".side-nav-vert, .side-nav, .websitebar").hide();
		jQuery(".id-container").css('width', '100%');

	}

	// Code to set tooltip on search field
	jQuery('table.liste tr.liste_titre_filter td.liste_titre input[name^="search"][type=text]:not(".maxwidthdate")').attr('title', '<?php echo dol_escape_js($langs->transnoentities("SearchSyntaxTooltipForStringOrNum")) ?>');
});


jQuery(document).ready(function() {
	jQuery(".butAction.dropdown-toggle").on("click", function(event) {
		console.log("Click on .butAction.dropdown-toggle");
		let parentHolder = jQuery(event.target).parent();
		let dropDownContent = parentHolder.children(".dropdown-content");
		let offset = parentHolder.offset();
		let widthDocument = $(document).width();
		let heightDocument = $(document).height();
		let right = widthDocument - offset.left - parentHolder.width();
		let widthPopup = parentHolder.children(".dropdown-content").width();
		if (widthPopup + right >= widthDocument) {
			//right = 10;
		}
		parentHolder.toggleClass("open");

		// Check tooltip is in viewport
		let dropDownContentTop = dropDownContent.offset().top;
		let dropDownContentLeft = dropDownContent.offset().left;
		let dropDownContentHeight = dropDownContent.outerHeight();
		let dropDownContentBottom = dropDownContentTop + dropDownContentHeight;
		let viewportBottom = $(window).scrollTop() + $(window).height();

		// Change dropdown Up/Down orientation if dropdown is close to bottom viewport
		if(parentHolder.hasClass('open')
			&& dropDownContentBottom > viewportBottom // Check bottom of dropdown is behind viewport
			&& dropDownContentTop - dropDownContentHeight > 0 // check if set dropdown to --up will not go over the top of document
		){
			parentHolder.addClass("--up");
		}else{
			parentHolder.removeClass("--up");
		}

		// Change dropdown left/right offset if dropdown is close to left viewport
		if(parentHolder.hasClass('open') && dropDownContentLeft < 0){
			parentHolder.addClass("--left");
		}else{
			parentHolder.removeClass("--left");
		}
	});

	// Close drop down
	jQuery(document).on("click", function(event) {
		// search if click was outside drop down
		if (!$(event.target).closest('.dropdown-toggle').length) {
			let parentholder = jQuery(".dropdown-toggle").closest(".dropdown.open");
			if(parentholder){
				// Hide the menus.
				parentholder.removeClass("open --up --left");
			}
		}
	});
});


<?php
if (!getDolGlobalString('MAIN_DISABLE_SELECT2_FOCUS_PROTECTION') && !defined('DISABLE_SELECT2_FOCUS_PROTECTION')) {
	?>
/*
 * Hacky fix for a bug in select2 with jQuery 3.6.4's new nested-focus "protection"
 * This fix needs to click a second time when clicking into a combo with ajax (see Test4d and Test5a in test_forms.php
 * see: https://github.com/select2/select2/issues/5993
 * see: https://github.com/jquery/jquery/issues/4382
 *
 * TODO: Recheck with the select2 GH issue and remove once this is fixed on their side
 */
$(document).on('select2:open', (e) => {
	console.log("Execute the focus (click on combo or use space when on component");
	const target = $(e.target);
	if (target && target.length) {
		let id = target[0].id || target[0].name;
		if (id.substr(-2) == "[]") id = id.substr(0,id.length-2);
		document.querySelector('input[aria-controls*='+id+']').focus();
	}
});
	<?php
}
?>


// End of lib_head.js.php
