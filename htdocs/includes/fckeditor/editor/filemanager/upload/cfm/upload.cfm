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
 * File Name: upload.cfm
 * 	This is the "File Uploader" for ColdFusion.
 * 	Based on connector.cfm by Mark Woods (mark@thickpaddy.com)
 * 
 * File Authors:
 * 		Wim Lemmens (didgiman@gmail.com)
--->

<cfinclude template="config.cfm">

<cfparam name="url.type" default="File">

<cffunction name="SendResults">
	<cfargument name="errorNumber" type="numeric" required="yes">
	<cfargument name="fileUrl" type="string" required="no" default="">
	<cfargument name="fileName" type="string" required="no" default="">
	<cfargument name="customMsg" type="string" required="no" default="">
	
	<cfoutput>
		<script type="text/javascript">
			window.parent.OnUploadCompleted(#errorNumber#, "#JSStringFormat(fileUrl)#", "#JSStringFormat(fileName)#", "#JSStringFormat(customMsg)#");			
		</script>
	</cfoutput>

	<cfabort><!--- Result sent, stop processing this page --->
</cffunction>

<cfif NOT config.enabled>
	<cfset SendResults(1, '', '', 'This file uploader is disabled. Please check the "editor/filemanager/upload/cfm/config.cfm" file')>
<cfelse>
	<cfscript>
		
		userFilesPath = config.userFilesPath;
		lAllowedExtensions = config.allowedExtensions[url.type];
		lDeniedExtensions = config.deniedExtensions[url.type];
		customMsg = ''; // Can be overwritten. The last value will be sent with the result
		
		// make sure the user files path is correctly formatted
		userFilesPath = replace(userFilesPath, "\", "/", "ALL");
		userFilesPath = replace(userFilesPath, '//', '/', 'ALL');
		if ( right(userFilesPath,1) NEQ "/" ) {
			userFilesPath = userFilesPath & "/";
		}
		if ( left(userFilesPath,1) NEQ "/" ) {
			userFilesPath = "/" & userFilesPath;
		}
		
		if (find("/",getBaseTemplatePath())) {
			fs = "/";
		} else {
			fs = "\";
		}
		
		// Get the base physical path to the web root for this application. The code to determine the path automatically assumes that
		// the "FCKeditor" directory in the http request path is directly off the web root for the application and that it's not a 
		// virtual directory or a symbolic link / junction. Use the serverPath config setting to force a physical path if necessary.
		if ( len(config.serverPath) ) {
			serverPath = config.serverPath;
		} else {
			serverPath = replaceNoCase(getBaseTemplatePath(),replace(cgi.script_name,"/",fs,"all"),"");
		}
				
		// map the user files path to a physical directory
		userFilesServerPath = serverPath & replace(userFilesPath,"/",fs,"all");
	</cfscript>
	
	<cfset fileName = "">
	<cfset fileExt = "">
	
	<cftry>
	
		<!--- we need to know the physical path to the current folder for all commands --->
		<cfset currentFolderPath = userFilesServerPath & url.type & fs>
	
		<!--- TODO: upload to a temp directory and move file if extension is allowed --->
	
		<!--- first upload the file with an unique filename --->
		<cffile action="upload"
			fileField="NewFile"
			destination="#currentFolderPath#"
			nameConflict="makeunique"
			mode="644"
			attributes="normal">
		
		<cfif (Len(lAllowedExtensions) AND NOT listFindNoCase(lAllowedExtensions, cffile.ServerFileExt))
			OR (Len(lDeniedExtensions) AND listFindNoCase(lDeniedExtensions, cffile.ServerFileExt))>
			
			<!--- Extension of the uploaded file is not allowed --->
			<cfset errorNumber = "202">
			<cffile action="delete" file="#cffile.ServerDirectory##fs##cffile.ServerFile#">
		
		<cfelse>
		
			<cfscript>
				errorNumber = 0;
				fileName = cffile.ClientFileName;
				fileExt = cffile.ServerFileExt;
		
				// munge filename for html download. Only a-z, 0-9, _, - and . are allowed
				if( reFind("[^A-Za-z0-9_\-\.]", fileName) ) {
					fileName = reReplace(fileName, "[^A-Za-z0-9\-\.]", "_", "ALL");
					fileName = reReplace(fileName, "_{2,}", "_", "ALL");
					fileName = reReplace(fileName, "([^_]+)_+$", "\1", "ALL");
					fileName = reReplace(fileName, "$_([^_]+)$", "\1", "ALL");
				}
				
				// When the original filename already exists, add numbers (0), (1), (2), ... at the end of the filename.
				if( compare( cffile.ServerFileName, fileName ) ) {
					counter = 0;
					tmpFileName = fileName;
					while( fileExists("#currentFolderPath##fileName#.#fileExt#") ) {
						counter = counter + 1;
						fileName = tmpFileName & '(#counter#)';
					}
				}
			</cfscript>
			
			<!--- Rename the uploaded file, if neccessary --->
			<cfif compare(cffile.ServerFileName,fileName)>
			
				<cfset errorNumber = "201">
				<cffile
					action="rename"
					source="#currentFolderPath##cffile.ServerFileName#.#cffile.ServerFileExt#"
					destination="#currentFolderPath##fileName#.#fileExt#"
					mode="644"
					attributes="normal">
			
			</cfif>					
		
		</cfif>
	
		<cfcatch type="Any">
		
			<cfset errorNumber = "1">
			<cfset customMsg = "An error occured: " & cfcatch.message & " - " & cfcatch.detail>
			
		</cfcatch>
		
	</cftry>
	
	<cfif errorNumber EQ 0>
		<!--- file was uploaded succesfully --->
		<cfset SendResults(errorNumber, '#userFilesPath##url.type#/#fileName#.#fileExt#')>
	<cfelseif errorNumber EQ 201>
		<!--- file was changed (201), submit the new filename --->
		<cfset SendResults(errorNumber, '#userFilesPath##url.type#/#fileName#.#fileExt#', replace( fileName & "." & fileExt, "'", "\'", "ALL"), customMsg)>
	<cfelse>
		<!--- An error occured(202). Submit only the error code and a message (if available). --->
		<cfset SendResults(errorNumber, '', '', customMsg)>
	</cfif>
</cfif>