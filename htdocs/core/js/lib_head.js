// Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
// Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
// \file       htdocs/core/js/lib_head.js
// \brief      File that include javascript functions (included if option use_javascript activated)
//


/*
 * ================================================================= 
 * Purpose:
 * Pour la saisie des dates par calendrier Input: base "/theme/eldy" dateFieldID
 * "dateo" Nom du champ format "dd/MM/yyyy" Format issu de Dolibarr de
 * SimpleDateFormat a utiliser pour retour
 * ==================================================================
 */
function showDP(base,dateFieldID,format,codelang)
{
	// check to see if another box is already showing
	var alreadybox=getObjectFromID("DPCancel");
	if (alreadybox) closeDPBox();	// This erase value of showDP.datefieldID

	// alert("showDP "+codelang);
	showDP.datefieldID=dateFieldID;	// Must be after the close

	var dateField=getObjectFromID(dateFieldID);
	
	// get positioning
	var thetop=getTop(dateField)+dateField.offsetHeight;

// var xxx=getObjectFromID('bottompage');
// alert(xxx.style.pixelTop);
// alert(document.body.clientHeight);
// alert(document.body.style.offsetTop);
// alert(thetop);
// alert(window.innerHeight);
	if (thetop+160 > window.innerHeight)
		thetop=thetop-160-20;
	var theleft=getLeft(dateField);
	if (theleft+140 > window.innerWidth)
		theleft= theleft-140+dateField.offsetWidth-15;

	showDP.box=document.createElement("div");
	showDP.box.className="bodyline";
	showDP.box.style.siplay="block";
	showDP.box.style.position="absolute";
	showDP.box.style.top=thetop + "px";
	showDP.box.style.left=theleft + "px";
	
	if (dateField.value)	// Si il y avait valeur initiale dans champ
	{
		selDate=getDateFromFormat(dateField.value,format);
		if (selDate)
		{
			// Success to parse value in field according to format
			year=selDate.getFullYear();
			month=selDate.getMonth()+1;
			day=selDate.getDate();
			datetime=selDate.getTime();
			ymd=formatDate(selDate,'yyyyMMdd');
		}
		else
		{
			// Failed to parse value in field according to format
			selDate=new Date();
			year=selDate.getFullYear();
			month=selDate.getUTCMonth()+1;
			day=selDate.getDate();
			datetime=selDate.getTime();
			ymd=formatDate(selDate,'yyyyMMdd');
		}
	}
	else
	{
		selDate=new Date();
		year=selDate.getFullYear();
		month=selDate.getUTCMonth()+1;
		day=selDate.getDate();
		datetime=selDate.getTime();
		ymd=formatDate(selDate,'yyyyMMdd');
	}
	loadMonth(base,month,year,ymd,codelang);
	hideSelectBoxes();
	document.body.appendChild(showDP.box);
}

function resetDP(base,dateFieldID,format,codelang)
{
	var dateField=getObjectFromID(dateFieldID);
	dateField.value = formatDate(new Date(), format);
	dpChangeDay(dateFieldID,format);
	
	var alreadybox=getObjectFromID("DPCancel");
	if (alreadybox) showDP(base,dateFieldID,format,codelang);
}

function loadMonth(base,month,year,ymd,codelang)
{
	/* showDP.box.innerHTML="Loading..."; */
	// alert(codelang);
	var theURL=base+"datepicker.php?cm=shw&lang="+codelang;
	theURL+="&m="+encodeURIComponent(month);
	theURL+="&y="+encodeURIComponent(year);
	if (selDate)
	{
		theURL+="&sd="+ymd;
	}

	var req=null;
	
	req=loadXMLDoc(theURL,null,false);
	if (req.responseText == '') alert('Failed to get URL '.theURL);
 	// alert(theURL+' - '+req.responseText); // L'url doit avoir la meme racine
	// que la pages et elements sinon pb de securite.
	showDP.box.innerHTML=req.responseText;	
}

function closeDPBox()
{
	document.body.removeChild(showDP.box);
	displaySelectBoxes();
	showDP.box=null;	
	showDP.datefieldID=null;	
}

function dpChangeDay(dateFieldID,format)
{
	showDP.datefieldID=dateFieldID;

	var thefield=getObjectFromID(showDP.datefieldID);
	var thefieldday=getObjectFromID(showDP.datefieldID+"day");
	var thefieldmonth=getObjectFromID(showDP.datefieldID+"month");
	var thefieldyear=getObjectFromID(showDP.datefieldID+"year");

	var date=getDateFromFormat(thefield.value,format);
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

function dpClickDay(year,month,day,format)
{
	var thefield=getObjectFromID(showDP.datefieldID);
	var thefieldday=getObjectFromID(showDP.datefieldID+"day");
	var thefieldmonth=getObjectFromID(showDP.datefieldID+"month");
	var thefieldyear=getObjectFromID(showDP.datefieldID+"year");

	var dt = new Date(year, month-1, day); 

	thefield.value=formatDate(dt,format);
	if(thefield.onchange) thefield.onchange.call(thefield);

	thefieldday.value=day;
	if(thefieldday.onchange) thefieldday.onchange.call(thefieldday);
	thefieldmonth.value=month;
	if(thefieldmonth.onchange) thefieldmonth.onchange.call(thefieldmonth);
	thefieldyear.value=year;
	if(thefieldyear.onchange) thefieldyear.onchange.call(thefieldyear);

	closeDPBox();
}

function dpHighlightDay(year,month,day,tradMonths){
	var displayinfo=getObjectFromID("dpExp");
	var months = tradMonths;
	displayinfo.innerHTML=months[month-1]+" "+day+", "+year;
}

// Returns an object given an id
function getObjectFromID(id){
	var theObject;
	if(document.getElementById)
		theObject=document.getElementById(id);
	else
		theObject=document.all[id];
	return theObject;
}

// This Function returns the top position of an object
function getTop(theitem){
	var offsetTrail = theitem;
	var offsetTop = 0;
	while (offsetTrail) {
		offsetTop += offsetTrail.offsetTop;
		offsetTrail = offsetTrail.offsetParent;
	}
	if (navigator.userAgent.indexOf("Mac") != -1 && typeof document.body.leftMargin != "undefined") 
		offsetLeft += document.body.TopMargin;
	return offsetTop;
}

// This Function returns the left position of an object
function getLeft(theitem){
	var offsetTrail = theitem;
	var offsetLeft = 0;
	while (offsetTrail) {
		offsetLeft += offsetTrail.offsetLeft;
		offsetTrail = offsetTrail.offsetParent;
	}
	if (navigator.userAgent.indexOf("Mac") != -1 && typeof document.body.leftMargin != "undefined") 
		offsetLeft += document.body.leftMargin;
	return offsetLeft;
}


// Create XMLHttpRequest object and load url
// Used by calendar or other ajax processes
// Return req built or false if error
function loadXMLDoc(url,readyStateFunction,async) 
{
	// req must be defined by caller with
	// var req = false;
 
	// branch for native XMLHttpRequest object (Mozilla, Safari...)
	if (window.XMLHttpRequest)
	{
		req = new XMLHttpRequest();
		
// if (req.overrideMimeType) {
// req.overrideMimeType('text/xml');
// }
	}
	// branch for IE/Windows ActiveX version
	else if (window.ActiveXObject)
	{
        try
        {
            req = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e)
        {
            try {
                req = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e) {}
        } 
	}

	// If XMLHttpRequestObject req is ok, call URL
	if (! req)
	{
    	alert('Cannot create XMLHTTP instance');
      	return false;
	}

	if (readyStateFunction) req.onreadystatechange = readyStateFunction;
	// Exemple of function for readyStateFuncyion:
	// function ()
       // {
       // if ( (req.readyState == 4) && (req.status == 200) ) {
       // if (req.responseText == 1) { newStatus = 'AAA'; }
       // if (req.responseText == 0) { newStatus = 'BBB'; }
       // if (currentStatus != newStatus) {
       // if (newStatus == "AAA") { obj.innerHTML = 'AAA'; }
       // else { obj.innerHTML = 'BBB'; }
       // currentStatus = newStatus;
       // }
       // }
       // }
	req.open("GET", url, async);
	req.send(null);
	return req;
}

// To hide/show select Boxes with IE6 (and only IE6 because IE6 has a bug and
// not put popup completely on the front)
function hideSelectBoxes() {
	var brsVersion = parseInt(window.navigator.appVersion.charAt(0), 10);
	if (brsVersion <= 6 && window.navigator.userAgent.indexOf("MSIE 6") > -1) 
	{  
		for(var i = 0; i < document.all.length; i++) 
		{
			if(document.all[i].tagName)
				if(document.all[i].tagName == "SELECT")
			  		document.all[i].style.visibility="hidden";
		}
	}
}
function displaySelectBoxes() {
	var brsVersion = parseInt(window.navigator.appVersion.charAt(0), 10);
	if (brsVersion <= 6 && window.navigator.userAgent.indexOf("MSIE 6") > -1) 
	{  
	       for(var i = 0; i < document.all.length; i++) 
	       {
	               if(document.all[i].tagName)
	                       if(document.all[i].tagName == "SELECT")
	                               document.all[i].style.visibility="visible";
	       }
	}
}



/*
 * ================================================================= 
 * Function:
 * formatDate (javascript object Date(), format) Purpose: Returns a date in the
 * output format specified. The format string can use the following tags: Field |
 * Tags -------------+------------------------------- Year | yyyy (4 digits), yy
 * (2 digits) Month | MM (2 digits) Day of Month | dd (2 digits) Hour (1-12) |
 * hh (2 digits) Hour (0-23) | HH (2 digits) Minute | mm (2 digits) Second | ss
 * (2 digits) Author: Laurent Destailleur Author: Matelli (see
 * http://matelli.fr/showcases/patchs-dolibarr/update-date-input-in-action-form.html)
 * Licence: GPL
 * ==================================================================
 */
function formatDate(date,format)
{
	// alert('formatDate date='+date+' format='+format);
	
	// Force parametres en chaine
	format=format+"";
	
	var result="";

	var year=date.getYear()+""; if (year.length < 4) { year=""+(year-0+1900); }
	var month=date.getMonth()+1;
	var day=date.getDate();
	var hour=date.getHours();
	var minute=date.getMinutes();
	var seconde=date.getSeconds();

	var i=0;
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
 * Function:
 * getDateFromFormat(date_string, format_string) Purpose: This function takes a
 * date string and a format string. It parses the date string with format and it
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

	// Force parametres en chaine
	val=val+"";
	format=format+"";

	if (val == '') return 0;
	
	var now=new Date();
	var year=now.getYear(); if (year.length < 4) { year=""+(year-0+1900); }
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
        if (substr == "yy")   year=""+(getIntegerInString(val,d,2,2)-0+1900); 
        if (substr == "MM" ||substr == "M") 
        { 
            month=getIntegerInString(val,d,1,2); 
            d -= 2- month.length; 
        } 
        if (substr == "dd") 
        { 
            day=getIntegerInString(val,d,1,2); 
            d -= 2- day.length; 
        } 
        if (substr == "HH" ||substr == "hh" ) 
        { 
            hour=getIntegerInString(val,d,1,2); 
            d -= 2- hour.length; 
        } 
        if (substr == "mm"){ 
            minute=getIntegerInString(val,d,1,2); 
            d -= 2- minute.length; 
        } 
        if (substr == "ss") 
        { 
            seconde=getIntegerInString(val,d,1,2); 
            d -= 2- seconde.length; 
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
	var newdate=new Date(year,month-1,day,hour,minute,seconde);

	return newdate;
}

/*
 * ================================================================= 
 * Function:
 * stringIsInteger(string) 
 * Purpose: Return true if string is an integer
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
 * Function:
 * getIntegerInString(string,pos,minlength,maxlength) 
 * Purpose: Return part of string from position i that is integer
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
 * Purpose:
 * Clean string to have it url encoded 
 * Input: s 
 * Author: Laurent Destailleur
 * Licence: GPL
 * ==================================================================
 */
function urlencode(s) {
	news=s;
	news=news.replace(/\+/gi,'%2B');
	news=news.replace(/&/gi,'%26');
	return news;
}


/*
 * ================================================================= 
 * Purpose: Show a popup HTML page. 
 * Input:   url,title 
 * Author:  Laurent Destailleur 
 * Licence: GPL 
 * ==================================================================
 */
function newpopup(url,title) {
	var argv = newpopup.arguments;
	var argc = newpopup.arguments.length;
	tmp=url;
	var l = (argc > 2) ? argv[2] : 600;
	var h = (argc > 3) ? argv[3] : 400;
	var wfeatures="directories=0,menubar=0,status=0,resizable=0,scrollbars=1,toolbar=0,width="+l+",height="+h+",left=" + eval("(screen.width - l)/2") + ",top=" + eval("(screen.height - h)/2");
	fen=window.open(tmp,title,wfeatures);
	return false;
}


/*
 * ================================================================= 
 * Purpose:
 * Applique un delai avant execution. Used for autocompletion of companies.
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
	if (typeof(expr) != 'string') return '';
	var reg = new RegExp("(&)", "g");
	var reg2 = new RegExp("[^A-Z0-9,]", "g");
	var liste1 = expr.replace(reg, ",");
	var liste = liste1.replace(reg2, "");
	return liste;
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
 * 
 */
function setConstant(url, code, input, entity) {
	$.get( url, {
		action: "set",
		name: code,
		entity: entity
	},
	function() {
		$("#set_" + code).hide();
		$("#del_" + code).show();
		$.each(input, function(type, data) {
			// Enable another element
			if (type == "disabled") {
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
					$.get( url, {
						action: "set",
						name: key,
						value: value,
						entity: entity
					});
				});
			}
		});
	});
}

/*
 * 
 */
function delConstant(url, code, input, entity) {
	$.get( url, {
		action: "del",
		name: code,
		entity: entity
	},
	function() {
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
			} else if (type == "enabled") {
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
					$.get( url, {
						action: "del",
						name: value,
						entity: entity
					});
				});
			}
		});
	});
}

/*
 * 
 */
function confirmConstantAction(action, url, code, input, box, entity, yesButton, noButton) {
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
								setConstant(url, code, input, entity);
							} else if (action == "del") {
								delConstant(url, code, input, entity);
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
}

/* 
 * ================================================================= 
 * This is to allow to transform all select box into ajax autocomplete box
 * with just one line: $(function() { $( "#idofmylist" ).combobox(); });
 * ================================================================= 
 */
(function( $ ) {
	$.widget( "ui.combobox", {
		options: {
			minLengthToAutocomplete: 0,
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
                                // remove invalid value, as it didnt match anything
                            	$( this ).val( "" );
                                select.val( "" );
                                input.data( "autocomplete" ).term = "";
                                return false;
                            }
                        }
                    }
                })
                .addClass( "ui-widget ui-widget-content ui-corner-left dolibarrcombobox" );

            input.data( "autocomplete" )._renderItem = function( ul, item ) {
                return $( "<li></li>" )
                    .data( "item.autocomplete", item )
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

/* 
 * Timer for delayed keyup function
 */
(function($){
	$.widget("ui.onDelayedKeyup", {
	    _init : function() {
	        var self = this;
	        $(this.element).bind('keyup input', function() {
	            if(typeof(window['inputTimeout']) != "undefined"){
	                window.clearTimeout(inputTimeout);
	            }  
	            var handler = self.options.handler;
	            window['inputTimeout'] = window.setTimeout(function() { handler.call(self.element) }, self.options.delay);
	        });
	    },
	    options: {
	        handler: $.noop(),
	        delay: 500
	    }
	});
})(jQuery);

