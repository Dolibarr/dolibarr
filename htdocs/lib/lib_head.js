// Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
// Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
//
// Script javascript mis en en-tete de pages (dans section head)
//
// \file       htdocs/lib/lib_head.js
// \brief      Fichier qui inclue les fonctions javascript d'en-tete (inclue si option use_javascript active)
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
	Input:    postalcode,objectville
	Author:   Eric Seigne
	Licence:  GPL
==================================================================*/

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

//	loadXMLDoc(theURL,alertContents,false);	Cree erreur javascript avec IE
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

function loadXMLDoc(url,readyStateFunction,async) 
{
	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		req = new XMLHttpRequest();
		if (req.overrideMimeType) {
      req.overrideMimeType('text/xml');
    }
    if (req) {
    	if(readyStateFunction) req.onreadystatechange = readyStateFunction;
    	req.open("GET", url, async);
    	req.send(null);
    } else {
    	alert('Cannot create XMLHTTP instance');
      return false;
    }
	// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		req = new ActiveXObject("Microsoft.XMLHTTP");
		if (req) {
			if(readyStateFunction) req.onreadystatechange = readyStateFunction;
			req.open("GET", url, async);
			req.send();
		}	else {
      alert('Cannot create XMLHTTP instance');
      return false;
    }
	}
}

function alertContents(httpRequest)
{
	if (httpRequest.readyState == 4) {
		if (httpRequest.status == 200) {
			alert(httpRequest.responseText);
    } else {
    	alert('There was a problem with the request.');
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


// Afficher/cacher les champs d'un formulaire
function formDisplayHideId(baliseId,numField) 
  {
  //if (document.getElementById && document.getElementById(baliseId) != null) 
    //{
    	//var balise = document.getElementById(baliseId);

    	var numDiv = 1
    	
      if (document.formsoc.typent_id.value == 8)
    	  {

    	  	while ( document.getElementById( baliseId + numDiv) ) {
    	  	
    	  	var balise = document.getElementById( baliseId + numDiv);
   	  	
    	  	if (balise && balise.className == "hidden") 
              balise.className = "visible";
              
          if (balise && balise.className == "visible") 
              balise.className = "hidden";
              numDiv++

            }
    	  }
      else
    	  {

    	  	while ( document.getElementById( baliseId + numDiv) ) {
    	    
    	    var balise = document.getElementById( baliseId + numDiv);

    		  if (balise && balise.className == "visible") 
              balise.className = "hidden";
              
          if (balise && balise.className == "hidden") 
              balise.className = "visible";
              numDiv++

            }
    	  }
     //}
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

function hidetip()
{
	if (ns6||ie)
	{
		enabletip=false
		tipobj.style.visibility="hidden"
		tipobj.style.left="-1000px"
		tipobj.style.backgroundColor=''
		tipobj.style.width=''
	}
}

document.onmousemove=positiontip;



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
	var min=date.getMinutes();
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
		else if (substr == 'MM')   { result=result+(month<1||month>9?"":"0")+month; }
		else if (substr == 'd')    { result=result+day; }
		else if (substr == 'dd')   { result=result+(day<1||day>9?"":"0")+day; }
		else if (substr == 'hh')   { if (hour > 12) hour-=12; result=result+(hour<1||hour>9?"":"0")+hour; }
		else if (substr == 'HH')   { result=result+(hour<1||hour>9?"":"0")+hour; }
		else if (substr == 'mm')   { result=result+(minute<1||minute>9?"":"0")+minute; }
		else if (substr == 'ss')   { result=result+(seconde<1||seconde>9?"":"0")+seconde; }
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
		if (substr == "yyyy") year=getIntegerInString(val,i,4,4);
		if (substr == "yy")   year=""+(getIntegerInString(val,i,2,2)-0+1900);
		if (substr == "MM")   month=getIntegerInString(val,i,2,2);
		if (substr == "M")    month=getIntegerInString(val,i,1,2);
		if (substr == "dd")   day=getIntegerInString(val,i,1,2);
		if (substr == "hh")   hour=getIntegerInString(val,i,1,2);
		if (substr == "HH")   hour=getIntegerInString(val,i,1,2);
		if (substr == "mm")   minute=getIntegerInString(val,i,1,2);
		if (substr == "ss")   seconde=getIntegerInString(val,i,1,2);
	
		i+=substr.length;
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
	Purpose:  Récupère l'id d'une autcompletion Ajax
	Input:    field,item
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
function ac_return(field, item){
        // on met en place l'expression régulière
        var regex = new RegExp('[0123456789]*-idcache', 'i');
        // on l'applique au contenu
        var idCache = regex.exec($(item).innerHTML);
        //on récupère l'id
        id = idCache[0].replace('-idcache', '');
        // et on l'affecte au champ caché
        $(field.name+'_id').value = id;
}

/*=================================================================
	Purpose:  Applique un délai avant execution
	Input:    funct, delay
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
 function ac_delay(funct,delay) {
 	// délai exprimé en millisecondes avant le déclenchement de l'action
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
	Purpose:  Affiche un message de confirmation
	Input:    linkurl,message,ok,cancel,objectID
	Author:   Regis Houssin
	Licence:  GPL
==================================================================*/
function dialogConfirm(linkurl,message,ok,cancel,objectID) {
	Dialog.confirm(message, {
		width:300,
		okLabel: ok,
		cancelLabel: cancel,
		buttonClass: "button",
		id: objectID,
		destroyOnClose: true,
		cancel:function(win){},
		ok:function(win) {window.location.href=linkurl; return true;} 
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