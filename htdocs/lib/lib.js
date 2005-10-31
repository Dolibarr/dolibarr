
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


// Pour la saisie des dates par calendrier
// ***************************************

function showDP(base,dateFieldID,format)
{
	var dateField= getObjectFromID(dateFieldID);
	
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
	
	showDP.datefieldID=dateFieldID;
	
	if(dateField.value)
	{
		selDate=stringToDate(dateField.value,format);
		year=selDate.getFullYear();
		month=selDate.getMonth()
		day=selDate.getDay();
	}
	else
	{
		tdate=new Date();
		year=tdate.getFullYear();
		month=tdate.getUTCMonth()+1;
		day=tdate.getDay();
	}
	loadMonth(base,month,year,year+'-'+month+'-'+day);
	hideSelectBoxes();
	document.body.appendChild(showDP.box);
}

// selectedDate must be in format YYYY-MM-DD
function loadMonth(base,month,year,selectedDate)
{
	showDP.box.innerHTML="Loading...";
	var theURL=base+"datepicker.php?cm=shw";
	theURL+="&m="+encodeURIComponent(month);
	theURL+="&y="+encodeURIComponent(year);
	if (selectedDate){
		tempdate=mysqlstringToDate(selectedDate);
		theURL+="&sd="+encodeURIComponent(tempdate.getFullYear()+"-"+tempdate.getMonth()+"-"+tempdate.getDate());
	}

	loadXMLDoc(theURL,null,false);
	showDP.box.innerHTML=req.responseText;	
}


function closeDPBox(){
	document.body.removeChild(showDP.box);
	displaySelectBoxes();
	showDP.box=null;	
	showDP.datefieldID=null;	
}

function dpClickDay(year,month,day){
	var thefield=getObjectFromID(showDP.datefieldID);
	thefield.value=day+"/"+month+"/"+year;
	if(thefield.onchange) thefield.onchange.call(thefield);
	closeDPBox();
}

function dpHighlightDay(year,month,day){
	var displayinfo=getObjectFromID("dpExp");
	var months=Array("January","February","March","April","May","June","July","August","September","October","November","December");
	displayinfo.innerHTML=months[month-1]+" "+day+", "+year;
}

function stringToDate(sDate,format){
// \todo fonction a ecrire pour tenir compte de format
	var sep="/";
	var month=sDate.substring(0,sDate.indexOf(sep))
	var day=sDate.substring(sDate.indexOf(sep)+1,sDate.indexOf(sep,sDate.indexOf(sep)+1))
	var year=sDate.substring(sDate.lastIndexOf(sep)+1);
	return new Date(year,month,day);
}

function mysqlstringToDate(sDate){
	var sep="-";
	var year=sDate.substring(0,sDate.indexOf(sep))
	var month=sDate.substring(sDate.indexOf(sep)+1,sDate.indexOf(sep,sDate.indexOf(sep)+1))
	var day=sDate.substring(sDate.lastIndexOf(sep)+1);
//	alert(year+','+month+','+day);
	return new Date(year,month,day);
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

function addEvent(obj, evType, fn){
 if (obj.addEventListener){
    obj.addEventListener(evType, fn, true);
    return true;
 } else if (obj.attachEvent){
    var r = obj.attachEvent("on"+evType, fn);
    return r;
 } else {
    return false;
 }
}

function removeEvent(obj, evType, fn, useCapture){
  if (obj.removeEventListener){
    obj.removeEventListener(evType, fn, useCapture);
    return true;
  } else if (obj.detachEvent){
    var r = obj.detachEvent("on"+evType, fn);
    return r;
  } else {
    window.status=("Handler could not be removed");
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
