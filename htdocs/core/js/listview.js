var TListTBS_include = true;

function TListTBS_OrderDown(idListe, column) {
	var base_url = document.location.href;
	
	base_url = TListTBS_recup_form_param(idListe,base_url);
	base_url = TListTBS_removeParam(base_url,'TListTBS['+encodeURIComponent(idListe)+'][orderBy]');
	
	base_url = TListTBS_removeParam(base_url,'get-all-for-export');
	
	document.location.href=TListTBS_modifyUrl(base_url,"TListTBS["+encodeURIComponent(idListe)+"][orderBy]["+encodeURIComponent(column)+"]","DESC");
}
function TListTBS_OrderUp(idListe, column) {
	
	var base_url = document.location.href;
	
	base_url = TListTBS_recup_form_param(idListe,base_url);
	base_url = TListTBS_removeParam(base_url,'TListTBS['+encodeURIComponent(idListe)+'][orderBy]');
	
	base_url = TListTBS_removeParam(base_url,'get-all-for-export');
	
	document.location.href=TListTBS_modifyUrl(base_url,"TListTBS["+encodeURIComponent(idListe)+"][orderBy]["+encodeURIComponent(column)+"]","ASC");
}
function TListTBS_modifyUrl(strURL,paramName,paramNewValue){
	    if (strURL.indexOf(paramName+'=')!=-1){
        	
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
        
        return strURL;
}
function TListTBS_removeParam(strURL, paramMask) {
	var cpt=0;
	var url = '';
	
	 while(strURL.indexOf(paramMask)!=-1 && cpt++ <50){
	 	var strFirstPart= strURL.substring(0,strURL.indexOf(paramMask)-1);
	 	
	 	var strLastPart='';
	 	if (strURL.indexOf('&',strFirstPart.length+1)>0) {
	 		strLastPart = strURL.substring(strURL.indexOf('&',strFirstPart.length+1),strURL.length);	
	 	}
	 		
		url = strFirstPart+strLastPart;
	 	
	 }
	 
	 if(url=='')url = strURL;
	 
	 return url;
}

function TListTBS_recup_form_param(idListe,base_url) {
	
	$('#'+idListe+' tr.barre-recherche [listviewtbs],#'+idListe+' tr.barre-recherche-head input,#'+idListe+' tr.barre-recherche-head select,#'+idListe+' div.tabsAction input[listviewtbs]').each(function(i,item) {
		if($(item).attr("name")) {
			base_url = TListTBS_modifyUrl(base_url, $(item).attr("name") , $(item).val());
		}
		
	});
	
	return base_url;
}

function TListTBS_GoToPage(idListe,pageNumber){
	
	var base_url = document.location.href;
	
	base_url = TListTBS_recup_form_param(idListe,base_url);
	base_url =TListTBS_modifyUrl(base_url,"TListTBS["+encodeURIComponent(idListe)+"][page]",pageNumber);
	
	base_url = TListTBS_removeParam(base_url,'get-all-for-export');
	
	document.location.href=base_url;
}
function TListTBS_submitSearch(obj) {
	
	$(obj).closest('form').submit();
	//console.log($(obj).closest('form'));
}
function TListTBS_launch_downloadAs(mode,url,token,session_name) {
	 $('#listTBSdAS_export_form').remove();
	
	$form = $('<form action="'+url+'" method="post" name="listTBSdAS_export_form" id="listTBSdAS_export_form"></form>');
	$form.append('<input type="hidden" name="mode" value="'+mode+'" />');
	$form.append('<input type="hidden" name="token" value="'+token+'" />');
	$form.append('<input type="hidden" name="session_name" value="'+session_name+'" />');
	
	$('body').append($form);
	
    $('#listTBSdAS_export_form').submit();
	
}

function TListTBS_downloadAs(obj, mode,url,token,session_name) {
	
	$form = $(obj).closest('form');
	$div = $form.find('div.tabsAction');
	$div.append('<input type="hidden" listviewtbs="hidden" name="token" value="'+token+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="mode" value="'+mode+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="url" value="'+url+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="session_name" value="'+session_name+'" />');
	$div.append('<input type="hidden" listviewtbs="hidden" name="get-all-for-export" value="1" />');
	
	TListTBS_submitSearch(obj);
}

$(document).ready(function() {
	$('tr.barre-recherche input').keypress(function(e) {
	    if(e.which == 13) {
	       
	       var id_list = $(this).closest('table').attr('id');
	       
	       $('#'+id_list+' .list-search-link').click();
	       
	    }
	});
	
	var $_GET = {};
	
	document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
	    function decode(s) {
	        return decodeURIComponent(s.split("+").join(" "));
	    }
	
	    $_GET[decode(arguments[1])] = decode(arguments[2]);
	});
	
	if(typeof $_GET["get-all-for-export"] != "undefined") {
		TListTBS_launch_downloadAs($_GET["mode"],$_GET["url"],$_GET["token"],$_GET["session_name"]);
	}
	
});
