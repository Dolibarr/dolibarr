<!---
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * 
 * == BEGIN LICENSE ==
 * 
 * Licensed under the terms of any of the following licenses at your
 * choice:
 * 
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 * 
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 * 
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 * 
 * == END LICENSE ==
 * 
 * File Name: config.cfm
 * 	Configuration file for the ColdFusion File Uploader.
 * 
 * File Authors:
 * 		Wim Lemmens (didgiman@gmail.com)
--->

<cfscript>
	config = StructNew();
		 
	// SECURITY: You must explicitelly enable this "uploader". 
	config.enabled = false;
	
	// Path to uploaded files relative to the document root.
	config.userFilesPath = "/userfiles/";
	
	config.serverPath = ""; // use this to force the server path if FCKeditor is not running directly off the root of the application or the FCKeditor directory in the URL is a virtual directory or a symbolic link / junction
		
	config.allowedExtensions = StructNew();
	config.deniedExtensions = StructNew();
	
	config.allowedExtensions["File"] = "";
	config.deniedExtensions["File"] = "html,htm,php,php2,php3,php4,php5,phtml,pwml,inc,asp,aspx,ascx,jsp,cfm,cfc,pl,bat,exe,com,dll,vbs,js,reg,cgi,htaccess,asis";
	
	config.allowedExtensions["Image"] = "png,gif,jpg,jpeg,bmp";
	config.deniedExtensions["Image"] = "";
	
	config.allowedExtensions["Flash"] = "swf,fla";
	config.deniedExtensions["Flash"] = "";
</cfscript>

<!--- code to maintain backwards compatibility with previous version of cfm connector --->
<cfif isDefined("application.userFilesPath")>

	<cflock scope="application" type="readonly" timeout="5">
		<cfset config.userFilesPath = application.userFilesPath>
	</cflock>

<cfelseif isDefined("server.userFilesPath")>
	
	<cflock scope="server" type="readonly" timeout="5">
		<cfset config.userFilesPath = server.userFilesPath>
	</cflock>
	
</cfif>

<!--- look for config struct in request, application and server scopes --->
<cfif isDefined("request.FCKeditor") and isStruct(request.FCKeditor)>

	<cfset variables.FCKeditor = request.FCKeditor>

<cfelseif isDefined("application.FCKeditor") and isStruct(application.FCKeditor)>

	<cflock scope="application" type="readonly" timeout="5">
	<cfset variables.FCKeditor = duplicate(application.FCKeditor)>
	</cflock>

<cfelseif isDefined("server.FCKeditor") and isStruct(server.FCKeditor)>

	<cflock scope="server" type="readonly" timeout="5">
	<cfset variables.FCKeditor = duplicate(server.FCKeditor)>
	</cflock>

</cfif>

<cfif isDefined("FCKeditor")>

	<!--- copy key values from external to local config (i.e. override default config as required) --->
	<cfscript>
		function structCopyKeys(stFrom, stTo) {
			for ( key in stFrom ) {
				if ( isStruct(stFrom[key]) ) {
					structCopyKeys(stFrom[key],stTo[key]);
				} else {
					stTo[key] = stFrom[key];
				}
			}
		}
		structCopyKeys(FCKeditor, config);
	</cfscript>

</cfif>