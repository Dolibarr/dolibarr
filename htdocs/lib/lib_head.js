// Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
//
// Script javascript mis en en-tete de pages (dans section head)
//
// \file       htdocs/lib/lib_head.js
// \brief      Fichier qui inclue les fonctions javascript d'en-tete (inclue si option use_javascript active)
// \version    $Revision$



// *****************************************
// Pour la fonction de saisi auto des villes
// *****************************************

function autofilltownfromzip_PopupPostalCode(postalcode,objectville)
{
    var url = 'searchpostalcode.php?cp=' + postalcode + '&targetobject=window.opener.document.formsoc.' + objectville.name;
    //  alert(url);
    var hWnd = window.open(url, "SearchPostalCodeWindow", "width=" + 300 + ",height=" + 150 + ",resizable=yes,scrollbars=yes");
    if((document.window != null) && (!hWnd.opener)) hWnd.opener = document.window;
}

function autofilltownfromzip_save_refresh_edit()
{
    document.formsoc.action.value="edit";
    document.formsoc.submit();
}

function autofilltownfromzip_save_refresh_create()
{
    document.formsoc.action.value="create";
    document.formsoc.submit();
}




// *****************************************
// Pour la saisie des dates par calendrier
// *****************************************

//	base			"/theme/eldy"
//	dateFieldID		"dateo"			Nom du champ
// 	format			"dd/MM/yyyy"	Format issu de Dolibarr de SimpleDateFormat à utiliser pour retour
function showDP(base,dateFieldID,format)
{
	showDP.datefieldID=dateFieldID;

	var dateField=getObjectFromID(dateFieldID);
	
	//check to see if another box is already showing
	var alreadybox=getObjectFromID("DPCancel");
	if(alreadybox) closeDPBox();

	//get positioning
	var thetop=getTop(dateField)+dateField.offsetHeight;
	var theleft=getLeft(dateField);
	if (theleft+140 > window.innerWidth)
		theleft= theleft-140+dateField.offsetWidth-15;

	showDP.box=document.createElement("div");
	showDP.box.className="bodyline";
	showDP.box.style.siplay="block";
	showDP.box.style.position="absolute";
	showDP.box.style.top=thetop + "px";
	showDP.box.style.left=theleft + "px";
	
	if(dateField.value)	// Si il y avait valeur initiale dans champ
	{
		selDate=getDateFromFormat(dateField.value,format);
		if (selDate)
		{
			year=selDate.getFullYear();
			month=selDate.getMonth()+1;
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
	loadMonth(base,month,year,ymd);
	hideSelectBoxes();
	document.body.appendChild(showDP.box);
}

function loadMonth(base,month,year,ymd)
{
	showDP.box.innerHTML="Loading...";
	var theURL=base+"datepicker.php?cm=shw";
	theURL+="&m="+encodeURIComponent(month);
	theURL+="&y="+encodeURIComponent(year);
	if (selDate)
	{
		theURL+="&sd="+ymd;
	}

	loadXMLDoc(theURL,null,false);
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
	var thefield=getObjectFromID(dateFieldID);

	var date=getDateFromFormat(thefield.value,format);

	if (date) dpClickDay(date.getFullYear(),date.getMonth()+1,date.getDate(),format)
	else dpClickDay(0,0,0,format);
}

function dpClickDay(year,month,day,format)
{
	var thefield=getObjectFromID(showDP.datefieldID);
	var thefieldday=getObjectFromID(showDP.datefieldID+"day");
	var thefieldmonth=getObjectFromID(showDP.datefieldID+"month");
	var thefieldyear=getObjectFromID(showDP.datefieldID+"year");

	var dt = new Date();
	dt.setMonth(month-1);
	dt.setYear(year);
	dt.setDate(day);

	thefield.value=formatDate(dt,format);
//	if(thefield.onchange) thefield.onchange.call(thefield);

	thefieldday.value=day;
	if(thefieldday.onchange) thefieldday.onchange.call(thefieldday);
	thefieldmonth.value=month;
	if(thefieldmonth.onchange) thefieldmonth.onchange.call(thefieldmonth);
	thefieldyear.value=year;
	if(thefieldyear.onchange) thefieldyear.onchange.call(thefieldyear);

	closeDPBox();
}

function dpHighlightDay(year,month,day){
	var displayinfo=getObjectFromID("dpExp");
	var months=Array("January","February","March","April","May","June","July","August","September","October","November","December");
	displayinfo.innerHTML=months[month-1]+" "+day+", "+year;
}


//Returns an object given an id
function getObjectFromID(id){
	var theObject;
	if(document.getElementById)
		theObject=document.getElementById(id);
	else
		theObject=document.all[id];
	return theObject;
}


// This Function returns the Top position of an object
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

// This Function returns the Left position of an object
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

function loadXMLDoc(url,readyStateFunction,async) 
{
	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		req = new XMLHttpRequest();
		req.onreadystatechange = readyStateFunction;
		req.open("GET", url, async);
		req.send(null);
	// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		req = new ActiveXObject("Microsoft.XMLHTTP");
		if (req) {
			if(readyStateFunction) req.onreadystatechange = readyStateFunction;
			req.open("GET", url, async);
			req.send();
		}
	}
}

function hideSelectBoxes() {
	var brsVersion = parseInt(window.navigator.appVersion.charAt(0), 10);
	if (brsVersion <= 6 && window.navigator.userAgent.indexOf("MSIE") > -1) {		
		for(var i = 0; i < document.all.length; i++) {
			if(document.all[i].tagName)
				if(document.all[i].tagName == "SELECT") 
					document.all[i].style.visibility="hidden";
		}
	}
}

function displaySelectBoxes() {
	var brsVersion = parseInt(window.navigator.appVersion.charAt(0), 10);
	if (brsVersion <= 6 && window.navigator.userAgent.indexOf("MSIE") > -1) {		
        for(var i = 0; i < document.all.length; i++) {
                if(document.all[i].tagName)
                        if(document.all[i].tagName == "SELECT")
                                document.all[i].style.visibility="visible";
        }
	}
}



/***********************************************
* Cool DHTML tooltip script- © Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/

var offsetxpoint=-60 //Customize x offset of tooltip
var offsetypoint=20 //Customize y offset of tooltip
var ie=document.all
var ns6=document.getElementById && !document.all
var enabletip=false
if (ie||ns6)
var tipobj=document.all? document.all["dhtmltooltip"] : document.getElementById? document.getElementById("dhtmltooltip") : ""

function ietruebody()
{
	return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function showtip(thetext)
{
	if (ns6||ie)
	{
		tipobj.innerHTML=thetext
		enabletip=true
		return false
	}
}

function positiontip(e)
{
	if (enabletip)
	{
		var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
		var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
		//Find out how close the mouse is to the corner of the window
		var rightedge=ie&&!window.opera? ietruebody().clientWidth-event.clientX-offsetxpoint : window.innerWidth-e.clientX-offsetxpoint-20
		var bottomedge=ie&&!window.opera? ietruebody().clientHeight-event.clientY-offsetypoint : window.innerHeight-e.clientY-offsetypoint-20
		
		var leftedge=(offsetxpoint<0)? offsetxpoint*(-1) : -1000
		
		//if the horizontal distance isn't enough to accomodate the width of the context menu
		if (rightedge<tipobj.offsetWidth)
		//move the horizontal position of the menu to the left by it's width
		tipobj.style.left=ie? ietruebody().scrollLeft+event.clientX-tipobj.offsetWidth+"px" : window.pageXOffset+e.clientX-tipobj.offsetWidth+"px"
		else if (curX<leftedge)
		tipobj.style.left="5px"
		else
		//position the horizontal position of the menu where the mouse is positioned
		tipobj.style.left=curX+offsetxpoint+"px"
		
		//same concept with the vertical position
		if (bottomedge<tipobj.offsetHeight)
		tipobj.style.top=ie? ietruebody().scrollTop+event.clientY-tipobj.offsetHeight-offsetypoint+"px" : window.pageYOffset+e.clientY-tipobj.offsetHeight-offsetypoint+"px"
		else
		tipobj.style.top=curY+offsetypoint+"px"
		tipobj.style.visibility="visible"
	}
}

function hidetip(){
if (ns6||ie){
enabletip=false
tipobj.style.visibility="hidden"
tipobj.style.left="-1000px"
tipobj.style.backgroundColor=''
tipobj.style.width=''
}
}

document.onmousemove=positiontip;




// ===================================================================
// Author: Matt Kruse <matt@mattkruse.com>
// WWW: http://www.mattkruse.com/
//
// NOTICE: You may use this code for any purpose, commercial or
// private, without any further permission from the author. You may
// remove this notice from your final code if you wish, however it is
// appreciated by the author if at least my web site address is kept.
//
// You may *NOT* re-distribute this code in any way except through its
// use. That means, you can include it in your product, or your web
// site, or any other form where the code is actually being used. You
// may not put the plain javascript up on your site for download or
// include it in your javascript libraries for download. 
// If you wish to share this code with others, please just point them
// to the URL instead.
// Please DO NOT link directly to my .js files from your site. Copy
// the files to your server and use them there. Thank you.
// ===================================================================

// HISTORY
// ------------------------------------------------------------------
// May 17, 2003: Fixed bug in parseDate() for dates <1970
// March 11, 2003: Added parseDate() function
// March 11, 2003: Added "NNN" formatting option. Doesn't match up
//                 perfectly with SimpleDateFormat formats, but 
//                 backwards-compatability was required.

// ------------------------------------------------------------------
// These functions use the same 'format' strings as the 
// java.text.SimpleDateFormat class, with minor exceptions.
// The format string consists of the following abbreviations:
// 
// Field        | Full Form          | Short Form
// -------------+--------------------+-----------------------
// Year         | yyyy (4 digits)    | yy (2 digits), y (2 or 4 digits)
// Month        | MMM (name or abbr.)| MM (2 digits), M (1 or 2 digits)
//              | NNN (abbr.)        |
// Day of Month | dd (2 digits)      | d (1 or 2 digits)
// Day of Week  | EE (name)          | E (abbr)
// Hour (1-12)  | hh (2 digits)      | h (1 or 2 digits)
// Hour (0-23)  | HH (2 digits)      | H (1 or 2 digits)
// Hour (0-11)  | KK (2 digits)      | K (1 or 2 digits)
// Hour (1-24)  | kk (2 digits)      | k (1 or 2 digits)
// Minute       | mm (2 digits)      | m (1 or 2 digits)
// Second       | ss (2 digits)      | s (1 or 2 digits)
// AM/PM        | a                  |
//
// NOTE THE DIFFERENCE BETWEEN MM and mm! Month=MM, not mm!
// Examples:
//  "MMM d, y" matches: January 01, 2000
//                      Dec 1, 1900
//                      Nov 20, 00
//  "M/d/yy"   matches: 01/20/00
//                      9/2/00
//  "MMM dd, yyyy hh:mm:ssa" matches: "January 01, 2000 12:30:45AM"
// ------------------------------------------------------------------

var MONTH_NAMES=new Array('January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
var DAY_NAMES=new Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sun','Mon','Tue','Wed','Thu','Fri','Sat');
function LZ(x) {return(x<0||x>9?"":"0")+x}

// ------------------------------------------------------------------
// isDate ( date_string, format_string )
// Returns true if date string matches format of format string and
// is a valid date. Else returns false.
// It is recommended that you trim whitespace around the value before
// passing it to this function, as whitespace is NOT ignored!
// ------------------------------------------------------------------
function isDate(val,format) {
	var date=getDateFromFormat(val,format);
	dt=date.getTime();
	if (dt==0) { return false; }
	return true;
}

// -------------------------------------------------------------------
// compareDates(date1,date1format,date2,date2format)
//   Compare two date strings to see which is greater.
//   Returns:
//   1 if date1 is greater than date2
//   0 if date2 is greater than date1 of if they are the same
//  -1 if either of the dates is in an invalid format
// -------------------------------------------------------------------
function compareDates(date1,dateformat1,date2,dateformat2) {
	var dd1=getDateFromFormat(date1,dateformat1);
	var dd2=getDateFromFormat(date2,dateformat2);
	var d1=dd1.getTime();
	var d2=dd2.getTime();
	if (d1==0 || d2==0) {
		return -1;
		}
	else if (d1 > d2) {
		return 1;
		}
	return 0;
}

// ------------------------------------------------------------------
// formatDate (date_object, format)
// Returns a date in the output format specified.
// The format string uses the same abbreviations as in getDateFromFormat()
// ------------------------------------------------------------------
function formatDate(date,format) {
	//alert(date+' '+format);
	format=format+"";
	var result="";
	var i_format=0;
	var c="";
	var token="";
	var y=date.getYear()+"";
	var M=date.getMonth()+1;
	var d=date.getDate();
	var E=date.getDay();
	var H=date.getHours();
	var m=date.getMinutes();
	var s=date.getSeconds();
	var yyyy,yy,MMM,MM,dd,hh,h,mm,ss,ampm,HH,H,KK,K,kk,k;
	// Convert real date parts into formatted versions
	var value=new Object();
	if (y.length < 4) {y=""+(y-0+1900);}
	value["y"]=""+y;
	value["yyyy"]=y;
	value["yy"]=y.substring(2,4);
	value["M"]=M;
	value["MM"]=LZ(M);
	value["MMM"]=MONTH_NAMES[M-1];
	value["NNN"]=MONTH_NAMES[M+11];
	value["d"]=d;
	value["dd"]=LZ(d);
	value["E"]=DAY_NAMES[E+7];
	value["EE"]=DAY_NAMES[E];
	value["H"]=H;
	value["HH"]=LZ(H);
	if (H==0){value["h"]=12;}
	else if (H>12){value["h"]=H-12;}
	else {value["h"]=H;}
	value["hh"]=LZ(value["h"]);
	if (H>11){value["K"]=H-12;} else {value["K"]=H;}
	value["k"]=H+1;
	value["KK"]=LZ(value["K"]);
	value["kk"]=LZ(value["k"]);
	if (H > 11) { value["a"]="PM"; }
	else { value["a"]="AM"; }
	value["m"]=m;
	value["mm"]=LZ(m);
	value["s"]=s;
	value["ss"]=LZ(s);
	while (i_format < format.length) {
		c=format.charAt(i_format);
		token="";
		while ((format.charAt(i_format)==c) && (i_format < format.length)) {
			token += format.charAt(i_format++);
			}
		if (value[token] != null) { result=result + value[token]; }
		else { result=result + token; }
		}
	//alert(format+' -> '+result);
	return result;
}
	
// ------------------------------------------------------------------
// Utility functions for parsing in getDateFromFormat()
// ------------------------------------------------------------------
function _isInteger(val) {
	var digits="1234567890";
	for (var i=0; i < val.length; i++) {
		if (digits.indexOf(val.charAt(i))==-1) { return false; }
		}
	return true;
}

function _getInt(str,i,minlength,maxlength) {
	for (var x=maxlength; x>=minlength; x--) {
		var token=str.substring(i,i+x);
		if (token.length < minlength) { return null; }
		if (_isInteger(token)) { return token; }
		}
	return null;
}
	
// ------------------------------------------------------------------
// getDateFromFormat( date_string , format_string )
//
// This function takes a date string and a format string. It matches
// If the date string matches the format string, it returns the date
// If it does not match, it returns 0.
// ------------------------------------------------------------------
function getDateFromFormat(val,format) {
	val=val+"";
	format=format+"";

//	alert(val+' '+format);
	var i_val=0;
	var i_format=0;
	var c="";
	var token="";
	var token2="";
	var x,y;
	var now=new Date();
	var year=now.getYear();
	var month=now.getMonth()+1;
	var date=1;
	var hh=now.getHours();
	var mm=now.getMinutes();
	var ss=now.getSeconds();
	var ampm="";
	
	while (i_format < format.length) {
		// Get next token from format string
		c=format.charAt(i_format);
		token="";
		while ((format.charAt(i_format)==c) && (i_format < format.length)) {
			token += format.charAt(i_format++);
			}
		// Extract contents of value based on format token
		if (token=="yyyy" || token=="yy" || token=="y") {
			if (token=="yyyy") { x=4;y=4; }
			if (token=="yy")   { x=2;y=2; }
			if (token=="y")    { x=2;y=4; }
			year=_getInt(val,i_val,x,y);
			if (year==null) { return 0; }
			i_val += year.length;
			if (year.length==2) {
				if (year > 70) { year=1900+(year-0); }
				else { year=2000+(year-0); }
				}
			}
		else if (token=="MMM"||token=="NNN"){
			month=0;
			for (var i=0; i<MONTH_NAMES.length; i++) {
				var month_name=MONTH_NAMES[i];
				if (val.substring(i_val,i_val+month_name.length).toLowerCase()==month_name.toLowerCase()) {
					if (token=="MMM"||(token=="NNN"&&i>11)) {
						month=i+1;
						if (month>12) { month -= 12; }
						i_val += month_name.length;
						break;
						}
					}
				}
			if ((month < 1)||(month>12)){return 0;}
			}
		else if (token=="EE"||token=="E"){
			for (var i=0; i<DAY_NAMES.length; i++) {
				var day_name=DAY_NAMES[i];
				if (val.substring(i_val,i_val+day_name.length).toLowerCase()==day_name.toLowerCase()) {
					i_val += day_name.length;
					break;
					}
				}
			}
		else if (token=="MM"||token=="M") {
			month=_getInt(val,i_val,token.length,2);
			if(month==null||(month<1)||(month>12)){return 0;}
			i_val+=month.length;}
		else if (token=="dd"||token=="d") {
			date=_getInt(val,i_val,token.length,2);
			if(date==null||(date<1)||(date>31)){return 0;}
			i_val+=date.length;}
		else if (token=="hh"||token=="h") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<1)||(hh>12)){return 0;}
			i_val+=hh.length;}
		else if (token=="HH"||token=="H") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<0)||(hh>23)){return 0;}
			i_val+=hh.length;}
		else if (token=="KK"||token=="K") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<0)||(hh>11)){return 0;}
			i_val+=hh.length;}
		else if (token=="kk"||token=="k") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<1)||(hh>24)){return 0;}
			i_val+=hh.length;hh--;}
		else if (token=="mm"||token=="m") {
			mm=_getInt(val,i_val,token.length,2);
			if(mm==null||(mm<0)||(mm>59)){return 0;}
			i_val+=mm.length;}
		else if (token=="ss"||token=="s") {
			ss=_getInt(val,i_val,token.length,2);
			if(ss==null||(ss<0)||(ss>59)){return 0;}
			i_val+=ss.length;}
		else if (token=="a") {
			if (val.substring(i_val,i_val+2).toLowerCase()=="am") {ampm="AM";}
			else if (val.substring(i_val,i_val+2).toLowerCase()=="pm") {ampm="PM";}
			else {return 0;}
			i_val+=2;}
		else {
			if (val.substring(i_val,i_val+token.length)!=token) {return 0;}
			else {i_val+=token.length;}
			}
		}
	// If there are any trailing characters left in the value, it doesn't match
	if (i_val != val.length) { return 0; }
	// Is date valid for month?
	if (month==2) {
		// Check for leap year
		if ( ( (year%4==0)&&(year%100 != 0) ) || (year%400==0) ) { // leap year
			if (date > 29){ return 0; }
			}
		else { if (date > 28) { return 0; } }
		}
	if ((month==4)||(month==6)||(month==9)||(month==11)) {
		if (date > 30) { return 0; }
		}
	// Correct hours value
	if (hh<12 && ampm=="PM") { hh=hh-0+12; }
	else if (hh>11 && ampm=="AM") { hh-=12; }
	var newdate=new Date(year,month-1,date,hh,mm,ss);

//	alert(year+' '+month+' '+date);

//	return newdate.getTime();
	return newdate;
}

// ------------------------------------------------------------------
// parseDate( date_string [, prefer_euro_format] )
//
// This function takes a date string and tries to match it to a
// number of possible date formats to get the value. It will try to
// match against the following international formats, in this order:
// y-M-d   MMM d, y   MMM d,y   y-MMM-d   d-MMM-y  MMM d
// M/d/y   M-d-y      M.d.y     MMM-d     M/d      M-d
// d/M/y   d-M-y      d.M.y     d-MMM     d/M      d-M
// A second argument may be passed to instruct the method to search
// for formats like d/M/y (european format) before M/d/y (American).
// Returns a Date object or null if no patterns match.
// ------------------------------------------------------------------
function parseDate(val) {
	var preferEuro=(arguments.length==2)?arguments[1]:false;
	generalFormats=new Array('y-M-d','MMM d, y','MMM d,y','y-MMM-d','d-MMM-y','MMM d');
	monthFirst=new Array('M/d/y','M-d-y','M.d.y','MMM-d','M/d','M-d');
	dateFirst =new Array('d/M/y','d-M-y','d.M.y','d-MMM','d/M','d-M');
	var checkList=new Array('generalFormats',preferEuro?'dateFirst':'monthFirst',preferEuro?'monthFirst':'dateFirst');
	var d=null;
	for (var i=0; i<checkList.length; i++) {
		var l=window[checkList[i]];
		for (var j=0; j<l.length; j++) {
			date=getDateFromFormat(val,l[j]);
			d=date.getTime();
			if (d!=0) { return new Date(d); }
			}
		}
	return null;
}


