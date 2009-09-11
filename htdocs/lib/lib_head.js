// Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
// Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
//
// Script javascript added in header of pages (in HEAD section)
//
// \file       htdocs/lib/lib_head.js
// \brief      File that include javascript functions (included if option use_javascript activated)
// \version    $Revision$


function dolibarr_type_reload(param)
{
    document.formsoc.action.value='create';
    document.formsoc.private.value=param;
    document.formsoc.cleartype.value=1;
    document.formsoc.submit();
}

function barcode_coder_save(formNameID)
{
    var formName = document.getElementById(formNameID);
    formName.action.value='setcoder';
    formName.submit();
}

/*=================================================================
	Purpose:  Pour la fonction de saisie auto des villes
	Input:    postalcode,objecttown,objectcountry,objectstate
	Author:   Eric Seigne
	Licence:  GPL
==================================================================*/

function autofilltownfromzip_PopupPostalCode(postalcode,objecttown,objectcountry,objectstate)
{
    var url = 'searchpostalcode.php?cp=' + postalcode;
    url = url + '&targettown=window.opener.document.formsoc.' + objecttown.name;
    url = url + '&targetcountry=window.opener.document.formsoc.' + objectcountry.name;
    url = url + '&targetstate=window.opener.document.formsoc.' + objectstate.name;
    //  alert(url);
    var hWnd = window.open(url, "SearchPostalCodeWindow", "width=" + 300 + ",height=" + 150 + ",resizable=yes,scrollbars=yes");
    if((document.window != null) && (!hWnd.opener)) hWnd.opener = document.window;
}

function company_save_refresh_edit()
{
    document.formsoc.action.value="edit";
    document.formsoc.submit();
}

function company_save_refresh_create()
{
    document.formsoc.action.value="create";
    document.formsoc.submit();
}

function company_save_refresh()
{
    document.form_index.action.value="updateedit";
    document.form_index.submit();
}



/*=================================================================
	Purpose:  Pour la saisie des dates par calendrier
	Input:    base			   "/theme/eldy"
					  dateFieldID  "dateo"			  Nom du champ
				    format			 "dd/MM/yyyy"   Format issu de Dolibarr de SimpleDateFormat à utiliser pour retour
==================================================================*/

function showDP(base,dateFieldID,format)
{
	//check to see if another box is already showing
	var alreadybox=getObjectFromID("DPCancel");
	if (alreadybox) closeDPBox();	// This erase value of showDP.datefieldID

	//alert("showDP "+dateFieldID);
	showDP.datefieldID=dateFieldID;	// Must be after the close

	var dateField=getObjectFromID(dateFieldID);
	
	//get positioning
	var thetop=getTop(dateField)+dateField.offsetHeight;

//	var xxx=getObjectFromID('bottompage');
//alert(xxx.style.pixelTop);
//alert(document.body.clientHeight);
//alert(document.body.style.offsetTop);
//alert(thetop);
//alert(window.innerHeight);
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
	loadMonth(base,month,year,ymd);
	hideSelectBoxes();
	document.body.appendChild(showDP.box);
}

function resetDP(base,dateFieldID,format)
{
	var dateField=getObjectFromID(dateFieldID);
	dateField.value = formatDate(new Date(), format);
	dpChangeDay(dateFieldID, format);
	
	var alreadybox=getObjectFromID("DPCancel");
	if (alreadybox) showDP(base,dateFieldID,format);
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

	var req=null;
	
	req=loadXMLDoc(theURL,null,false);
	if (req.responseText == '') alert('Failed to get URL '.theURL);
 	//alert(theURL+' - '+req.responseText);  // L'url doit avoir la meme racine que la pages et elements sinon pb de securite.
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

	var dt = new Date();
	dt.setMonth(month-1);
	dt.setYear(year);
	dt.setDate(day);

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

//Returns an object given an id
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
		
//		if (req.overrideMimeType) {
//      		req.overrideMimeType('text/xml');
//    	}
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
       //     if ( (req.readyState == 4) && (req.status == 200) ) {
       //        if (req.responseText == 1) { newStatus = 'AAA'; }
       //        if (req.responseText == 0) { newStatus = 'BBB'; }
       //        if (currentStatus != newStatus) {
       //            if (newStatus == "AAA") { obj.innerHTML = 'AAA'; }
       //            else { obj.innerHTML = 'BBB'; }
       //            currentStatus = newStatus;
       //        }
       //    }
       // }
	req.open("GET", url, async);
	req.send(null);
	return req;
}

// To hide/show select Boxes with IE6 (and only IE6 because IE6 has a bug and not put popup completely on the front)
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



/*=================================================================
	Function: formatDate (javascript object Date(), format)
	Purpose:  Returns a date in the output format specified.
              The format string can use the following tags:
				 Field        | Tags
				 -------------+-------------------------------
				 Year         | yyyy (4 digits), yy (2 digits)
				 Month        | MM (2 digits)
				 Day of Month | dd (2 digits)
				 Hour (1-12)  | hh (2 digits)
				 Hour (0-23)  | HH (2 digits)
				 Minute       | mm (2 digits)
				 Second       | ss (2 digits)
	Author:   Laurent Destailleur
	Author:   Matelli (see http://matelli.fr/showcases/patchs-dolibarr/update-date-input-in-action-form.html)
	Licence:  GPL
==================================================================*/
function formatDate(date,format)
{
	//alert('formatDate date='+date+' format='+format);
	
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
		while ((format.charAt(j)==c) && (j < format.length))	// Recupere char successif identiques
		{
			substr += format.charAt(j++);
		}

		//alert('substr='+substr);
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

	//alert(result);
	return result;
}


/*=================================================================
	Function: getDateFromFormat(date_string, format_string)
	Purpose:  This function takes a date string and a format string.
			  It parses the date string with format and it returns
			  the date as a javascript Date() object.
			  If date does not match format, it returns 0.
              The format string can use the following tags:
				 Field        | Tags
				 -------------+-------------------------------
				 Year         | yyyy (4 digits), yy (2 digits)
				 Month        | MM (2 digits)
				 Day of Month | dd (2 digits)
				 Hour (1-12)  | hh (2 digits)
				 Hour (0-23)  | HH (2 digits)
				 Minute       | mm (2 digits)
				 Second       | ss (2 digits)
	Author:   Laurent Destailleur
	Licence:  GPL
==================================================================*/
function getDateFromFormat(val,format)
{
	//alert('getDateFromFormat val='+val+' format='+format);

	// Force parametres en chaine
	val=val+"";
	format=format+"";

	var now=new Date();
	var year=now.getYear(); if (year.length < 4) { year=""+(year-0+1900); }
	var month=now.getMonth()+1;
	var day=now.getDate();
	var hour=now.getHours();
	var minute=now.getMinutes();
	var seconde=now.getSeconds();

	var i=0;
	var d=0;    // -d- follows the date string while -i- follows the format string 

	while (i < format.length)
	{
		c=format.charAt(i);	// Recupere char du format
		substr="";
		j=i;
		while ((format.charAt(j)==c) && (j < format.length))	// Recupere char successif identiques
		{
			substr += format.charAt(j++);
		}

		//alert('substr='+substr);
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
		
	//alert(year+' '+month+' '+day+' '+hour+' '+minute+' '+seconde);
	var newdate=new Date(year,month-1,day,hour,minute,seconde);

	return newdate;
}

/*=================================================================
	Function: stringIsInteger(string)
	Purpose:  Return true if string is an integer
==================================================================*/
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

/*=================================================================
	Function: getIntegerInString(string,pos,minlength,maxlength)
	Purpose:  Return part of string from position i that is integer
==================================================================*/
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


/*=================================================================
	Purpose:  Fonction pour champ saisie en mode ajax
	Author:   Laurent Destailleur
	Licence:  GPL
==================================================================*/
function publish_selvalue(obj) { $(obj.name).value = obj.options[obj.selectedIndex].value; }



/*=================================================================
	Purpose:  Affiche popup
	Input:    url,title
	Author:   Laurent Destailleur
	Licence:  GPL
==================================================================*/
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


/*=================================================================
	Purpose:  Set value of a field after return of Ajax call
	Input:    HTML field name, val
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
function ac_return(field, val){
/*        alert('field.name='+field.name+'-'+val.innerHTML); */
        /* on met en place l'expression reguliere */
        var regex = new RegExp('[0123456789]*-idcache', 'i');
        /* on l'applique au contenu */
        var idCache = regex.exec(val.innerHTML);
        /* on recupere id */
        id = idCache[0].replace('-idcache', '');
/*        alert('field.name='+field.name+'-'+idCache[0]+'-'+id); */ 
        /* et on l'affecte au champ cache */
/*        alert('field.name='+field.name+'-'+val.innerHTML+'-id='+id); */
        $(field.name+'_id').value = id;
}

/*=================================================================
	Purpose:  Applique un delai avant execution
	Input:    funct, delay
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
 function ac_delay(funct,delay) {
 	// delay before start of action
  	setTimeout(funct,delay);
}

/*=================================================================
	Purpose:  Nettoie les valeurs d'un "Sortable.serialize"
	Input:    expr
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
function cleanSerialize(expr) {
	var reg = new RegExp("(&)", "g");
	var reg2 = new RegExp("[^A-Z0-9,]", "g");
	var liste1 = expr.replace(reg, ",");
	var liste = liste1.replace(reg2, "");
	return liste;
}

/*=================================================================
	Purpose:  Show a confim popup
	Input:    title,linkurlyes,linkurlno,message,ok,cancel,objectID
	Author:   Regis Houssin, Laurent Destailleur
	Licence:  GPL
==================================================================*/
function dialogConfirm(title,linkurlyes,linkurlno,message,ok,cancel,objectID) {
	Dialog.confirm(message, {
		width:560,
		okLabel: ok,
		cancelLabel: cancel,
		buttonClass: "buttonajax",
		id: objectID,
		destroyOnClose: true,
		ok:function(win) {window.location.href=linkurlyes; return true;},
		cancel:function(win) { if (linkurlno!='') { window.location.href=linkurlno; return true; } }
	});
}

/*=================================================================
	Purpose:  Affiche un message d'information
	Input:    message
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
function dialogInfo(message) {
	Dialog.info(message, {width:700});
}

/*=================================================================
	Purpose:  Affiche une fenetre
	Input:    message
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
function dialogWindow(message,windowTitle) {
var win = new Window({className: "dialog",  
	                    width:600,
	                    height:400,
	                    zIndex: 100,
	                    resizable: false,
	                    title: windowTitle,
	                    showEffect:Effect.BlindDown,
	                    hideEffect: Effect.SwitchOff,
	                    draggable:true
                    })
/*win.setHTMLContent(message);*/
/*win.getContent().innerHTML = message;*/
win.getContent().update(message);
win.showCenter();
}
