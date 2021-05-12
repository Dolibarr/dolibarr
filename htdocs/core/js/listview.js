// Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
// \file       htdocs/core/js/listview.js
// \brief      File that include javascript functions for lists
//
var Listview_include = true;

function Listview_OrderDown(idListe, column) {
	var base_url = document.location.href;
<<<<<<< HEAD
	
	base_url = Listview_recup_form_param(idListe,base_url);
	base_url = Listview_removeParam(base_url,'Listview['+encodeURIComponent(idListe)+'][orderBy]');
	
	base_url = Listview_removeParam(base_url,'get-all-for-export');
	
	document.location.href=Listview_modifyUrl(base_url,"Listview["+encodeURIComponent(idListe)+"][orderBy]["+encodeURIComponent(column)+"]","DESC");
}
function Listview_OrderUp(idListe, column) {
	
	var base_url = document.location.href;
	
	base_url = Listview_recup_form_param(idListe,base_url);
	base_url = Listview_removeParam(base_url,'Listview['+encodeURIComponent(idListe)+'][orderBy]');
	
	base_url = Listview_removeParam(base_url,'get-all-for-export');
	
=======

	base_url = Listview_recup_form_param(idListe,base_url);
	base_url = Listview_removeParam(base_url,'Listview['+encodeURIComponent(idListe)+'][orderBy]');

	base_url = Listview_removeParam(base_url,'get-all-for-export');

	document.location.href=Listview_modifyUrl(base_url,"Listview["+encodeURIComponent(idListe)+"][orderBy]["+encodeURIComponent(column)+"]","DESC");
}
function Listview_OrderUp(idListe, column) {

	var base_url = document.location.href;

	base_url = Listview_recup_form_param(idListe,base_url);
	base_url = Listview_removeParam(base_url,'Listview['+encodeURIComponent(idListe)+'][orderBy]');

	base_url = Listview_removeParam(base_url,'get-all-for-export');

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	document.location.href=Listview_modifyUrl(base_url,"Listview["+encodeURIComponent(idListe)+"][orderBy]["+encodeURIComponent(column)+"]","ASC");
}
function Listview_modifyUrl(strURL,paramName,paramNewValue){
	    if (strURL.indexOf(paramName+'=')!=-1){
<<<<<<< HEAD
        	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                var strFirstPart=strURL.substring(0,strURL.indexOf(paramName+'=',0))+paramName+'=';
                var strLastPart="";
                if (strURL.indexOf('&',strFirstPart.length-1)>0)
                      strLastPart=strURL.substring(strURL.indexOf('&',strFirstPart.length-1),strURL.length);
              		  strURL=strFirstPart+paramNewValue+strLastPart;
                }
        else{
                if (strURL.search('=')!=-1) // permet de verifier s'il y a dej� des param�tres dans l'URL
                        strURL+='&'+paramName+'='+paramNewValue;
                else
                        strURL+='?'+paramName+'='+paramNewValue;
        }
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        return strURL;
}
function Listview_removeParam(strURL, paramMask) {
	var cpt=0;
	var url = '';
<<<<<<< HEAD
	
	 while(strURL.indexOf(paramMask)!=-1 && cpt++ <50){
	 	var strFirstPart= strURL.substring(0,strURL.indexOf(paramMask)-1);
	 	
	 	var strLastPart='';
	 	if (strURL.indexOf('&',strFirstPart.length+1)>0) {
	 		strLastPart = strURL.substring(strURL.indexOf('&',strFirstPart.length+1),strURL.length);	
	 	}
	 		
		url = strFirstPart+strLastPart;
	 	
	 }
	 
	 if(url=='')url = strURL;
	 
=======

	 while(strURL.indexOf(paramMask)!=-1 && cpt++ <50){
	 	var strFirstPart= strURL.substring(0,strURL.indexOf(paramMask)-1);

	 	var strLastPart='';
	 	if (strURL.indexOf('&',strFirstPart.length+1)>0) {
	 		strLastPart = strURL.substring(strURL.indexOf('&',strFirstPart.length+1),strURL.length);
	 	}

		url = strFirstPart+strLastPart;

	 }

	 if(url=='')url = strURL;

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 return url;
}

function Listview_recup_form_param(idListe,base_url) {
<<<<<<< HEAD
	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$('#'+idListe+' tr.barre-recherche [listviewtbs],#'+idListe+' tr.barre-recherche-head input,#'+idListe+' tr.barre-recherche-head select,#'+idListe+' div.tabsAction input[listviewtbs]').each(function(i,item) {
		if($(item).attr("name")) {
			base_url = Listview_modifyUrl(base_url, $(item).attr("name") , $(item).val());
		}
<<<<<<< HEAD
		
	});
	
=======

	});

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	return base_url;
}

function Listview_GoToPage(idListe,pageNumber){
<<<<<<< HEAD
	
	var base_url = document.location.href;
	
	base_url = Listview_recup_form_param(idListe,base_url);
	base_url =Listview_modifyUrl(base_url,"Listview["+encodeURIComponent(idListe)+"][page]",pageNumber);
	
	base_url = Listview_removeParam(base_url,'get-all-for-export');
	
	document.location.href=base_url;
}
function Listview_submitSearch(obj) {
	
=======

	var base_url = document.location.href;

	base_url = Listview_recup_form_param(idListe,base_url);
	base_url =Listview_modifyUrl(base_url,"Listview["+encodeURIComponent(idListe)+"][page]",pageNumber);

	base_url = Listview_removeParam(base_url,'get-all-for-export');

	document.location.href=base_url;
}
function Listview_submitSearch(obj) {

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$form = $(obj).closest('form');
	console.log($form);
	if($form.length>0){
		$form.submit();
	}
}
function Listview_launch_downloadAs(mode,url,token,session_name) {
	 $('#listviewdAS_export_form').remove();
<<<<<<< HEAD
	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$form = $('<form action="'+url+'" method="post" name="listviewdAS_export_form" id="listTBSdAS_export_form"></form>');
	$form.append('<input type="hidden" name="mode" value="'+mode+'" />');
	$form.append('<input type="hidden" name="token" value="'+token+'" />');
	$form.append('<input type="hidden" name="session_name" value="'+session_name+'" />');
<<<<<<< HEAD
	
	$('body').append($form);
	
    $('#listviewdAS_export_form').submit();
	
}

function Listview_downloadAs(obj, mode,url,token,session_name) {
	
=======

	$('body').append($form);

    $('#listviewdAS_export_form').submit();

}

function Listview_downloadAs(obj, mode,url,token,session_name) {

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$form = $(obj).closest('form');
	$div = $form.find('div.tabsAction');
	$div.append('<input type="hidden" listviewtbs="hidden" name="token" value="'+token+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="mode" value="'+mode+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="url" value="'+url+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="session_name" value="'+session_name+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="get-all-for-export" value="1" />');
<<<<<<< HEAD
	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	Listview_submitSearch(obj);
}

$(document).ready(function() {
	$('tr.barre-recherche input').keypress(function(e) {
	    if(e.which == 13) {
<<<<<<< HEAD
	       
	       var id_list = $(this).closest('table').attr('id');
	       
	       $('#'+id_list+' .list-search-link').click();
	       
	    }
	});
	
	var $_GET = {};
	
=======

	       var id_list = $(this).closest('table').attr('id');

	       $('#'+id_list+' .list-search-link').click();

	    }
	});

	var $_GET = {};

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
	    function decode(s) {
	        return decodeURIComponent(s.split("+").join(" "));
	    }
<<<<<<< HEAD
	
	    $_GET[decode(arguments[1])] = decode(arguments[2]);
	});
	
	if(typeof $_GET["get-all-for-export"] != "undefined") {
		Listview_launch_downloadAs($_GET['mode'],$_GET['url'],$_GET['token'],$_GET['session_name']);
	}
	
=======

	    $_GET[decode(arguments[1])] = decode(arguments[2]);
	});

	if(typeof $_GET["get-all-for-export"] != "undefined") {
		Listview_launch_downloadAs($_GET['mode'],$_GET['url'],$_GET['token'],$_GET['session_name']);
	}

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
});
