<cfsetting enablecfoutputonly="yes" showdebugoutput="no">
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
 * File Name: connector.cfm
 * 	File Browser connector for ColdFusion.
 * 	(based on the original CF connector by Hendrik Kramer - hk@lwd.de)
 * 
 * 	Note: 
 * 	FCKeditor requires that the connector responds with UTF-8 encoded XML.
 * 	As ColdFusion 5 does not fully support UTF-8 encoding, we force ASCII 
 * 	file and folder names in this connector to allow CF5 send a UTF-8 
 * 	encoded response - code points under 127 in UTF-8 are stored using a 
 * 	single byte, using the same encoding as ASCII, which is damn handy. 
 * 	This is all grand for the English speakers, like meself, but I dunno 
 * 	how others are gonna take to it. Well, the previous version of this 
 * 	connector already did this with file names and nobody seemed to mind, 
 * 	so fingers-crossed nobody will mind their folder names being munged too.
 * 	  
 * 
 * File Authors:
 * 		Mark Woods (mark@thickpaddy.com)
 * 		Wim Lemmens (didgiman@gmail.com)
--->

<cfparam name="url.command">
<cfparam name="url.type"> 
<cfparam name="url.currentFolder">
<!--- note: no serverPath url parameter - see config.cfm if you need to set the serverPath manually --->

<cfinclude template="config.cfm">

<cfscript>
	userFilesPath = config.userFilesPath;
	lAllowedExtensions = config.allowedExtensions[url.type];
	lDeniedExtensions = config.deniedExtensions[url.type];
	
	// make sure the user files path is correctly formatted
	userFilesPath = replace(userFilesPath, "\", "/", "ALL");
	userFilesPath = replace(userFilesPath, '//', '/', 'ALL');
	if ( right(userFilesPath,1) neq "/" ) {
		userFilesPath = userFilesPath & "/";
	}
	if ( left(userFilesPath,1) neq "/" ) {
		userFilesPath = "/" & userFilesPath;
	}
	
	// make sure the current folder is correctly formatted
	url.currentFolder = replace(url.currentFolder, "\", "/", "ALL");
	url.currentFolder = replace(url.currentFolder, '//', '/', 'ALL');
	if ( right(url.currentFolder,1) neq "/" ) {
		url.currentFolder = url.currentFolder & "/";
	}
	if ( left(url.currentFolder,1) neq "/" ) {
		url.currentFolder = "/" & url.currentFolder;
	}

	if ( find("/",getBaseTemplatePath()) neq 0 ) {
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
	
	xmlContent = ""; // append to this string to build content
</cfscript>

<cfif not config.enabled>

	<cfset xmlContent = "<Error number=""1"" text=""This connector is disabled. Please check the 'editor/filemanager/browser/default/connectors/cfm/config.cfm' file"" />">
	
<cfelseif find("..",url.currentFolder)>
	
	<cfset xmlContent = "<Error number=""102"" />">
	
</cfif>

<cfif not len(xmlContent)>

<!--- create directories in physical path if they don't already exist --->
<cfset currentPath = serverPath>
<cftry>

	<cfloop list="#userFilesPath#" index="name" delimiters="/">
		
		<cfif not directoryExists(currentPath & fs & name)>
				<cfdirectory action="create" directory="#currentPath##fs##name#" mode="755">
		</cfif>
		
		<cfset currentPath = currentPath & fs & name>
		
	</cfloop>
	
	<!--- create sub-directory for file type if it doesn't already exist --->
		<cfif not directoryExists(userFilesServerPath & url.type)>	
		<cfdirectory action="create" directory="#userFilesServerPath##url.type#" mode="755">
	</cfif>

<cfcatch>

	<!--- this should only occur as a result of a permissions problem --->
	<cfset xmlContent = "<Error number=""103"" />">

</cfcatch>
</cftry>

</cfif>

<cfif not len(xmlContent)>

	<!--- no errors thus far - run command --->
	
	<!--- we need to know the physical path to the current folder for all commands --->
	<cfset currentFolderPath = userFilesServerPath & url.type & replace(url.currentFolder,"/",fs,"all")>
	
	<cfswitch expression="#url.command#">
	
	
		<cfcase value="FileUpload">
		
			<cfset fileName = "">
			<cfset fileExt = "">
		
			<cftry>
			
				<!--- TODO: upload to a temp directory and move file if extension is allowed --->
			
				<!--- first upload the file with an unique filename --->
				<cffile action="upload"
					fileField="NewFile"
					destination="#currentFolderPath#"
					nameConflict="makeunique"
					mode="644"
					attributes="normal">
				
				<cfif cffile.fileSize EQ 0>
					<cfthrow>
				</cfif>
				
				<cfif ( len(lAllowedExtensions) and not listFindNoCase(lAllowedExtensions,cffile.ServerFileExt) )
					or ( len(lDeniedExtensions) and listFindNoCase(lDeniedExtensions,cffile.ServerFileExt) )>
				
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
				
					<cfset errorNumber = "202">
					
				</cfcatch>
				
			</cftry>
			
			
			<cfif errorNumber eq 201>
			
				<!--- file was changed (201), submit the new filename --->
				<cfoutput>
				<script type="text/javascript">
				window.parent.frames['frmUpload'].OnUploadCompleted(#errorNumber#,'#replace( fileName & "." & fileExt, "'", "\'", "ALL")#');
				</script>
				</cfoutput>

			<cfelse>
			
				<!--- file was uploaded succesfully(0) or an error occured(202). Submit only the error code. --->
				<cfoutput>
				<script type="text/javascript">
				window.parent.frames['frmUpload'].OnUploadCompleted(#errorNumber#);
				</script>
				</cfoutput>
				
			</cfif>
			
			<cfabort>
		
		</cfcase>
		
		
		<cfcase value="GetFolders">
		
			<!--- Sort directories first, name ascending --->
			<cfdirectory 
				action="list" 
				directory="#currentFolderPath#" 
				name="qDir"
				sort="type,name">
			
			<cfscript>
				i=1;
				folders = "";
				while( i lte qDir.recordCount ) {
					if( not compareNoCase( qDir.type[i], "FILE" ))
						break;
					if( not listFind(".,..", qDir.name[i]) )
						folders = folders & '<Folder name="#qDir.name[i]#" />';
					i=i+1;
				}
		
				xmlContent = xmlContent & '<Folders>' & folders & '</Folders>';
			</cfscript>
		
		</cfcase>
		
		
		<cfcase value="GetFoldersAndFiles">
		
			<!--- Sort directories first, name ascending --->
			<cfdirectory 
				action="list" 
				directory="#currentFolderPath#" 
				name="qDir"
				sort="type,name">
				
			<cfscript>
				i=1;
				folders = "";
				files = "";
				while( i lte qDir.recordCount ) {
					if( not compareNoCase( qDir.type[i], "DIR" ) and not listFind(".,..", qDir.name[i]) ) {
						folders = folders & '<Folder name="#qDir.name[i]#" />';
					} else if( not compareNoCase( qDir.type[i], "FILE" ) ) {
						fileSizeKB = round(qDir.size[i] / 1024);
						files = files & '<File name="#qDir.name[i]#" size="#IIf( fileSizeKB GT 0, DE( fileSizeKB ), 1)#" />';
					}
					i=i+1;
				}
		
				xmlContent = xmlContent & '<Folders>' & folders & '</Folders>';
				xmlContent = xmlContent & '<Files>' & files & '</Files>';
			</cfscript>
		
		</cfcase>
		
		
		<cfcase value="CreateFolder">
		
			<cfparam name="url.newFolderName" default="">
			
			<cfscript>
				newFolderName = url.newFolderName;
				if( reFind("[^A-Za-z0-9_\-\.]", newFolderName) ) {
					// Munge folder name same way as we do the filename
					// This means folder names are always US-ASCII so we don't have to worry about CF5 and UTF-8
					newFolderName = reReplace(newFolderName, "[^A-Za-z0-9\-\.]", "_", "all");
					newFolderName = reReplace(newFolderName, "_{2,}", "_", "all");
					newFolderName = reReplace(newFolderName, "([^_]+)_+$", "\1", "all");
					newFolderName = reReplace(newFolderName, "$_([^_]+)$", "\1", "all");
				}
			</cfscript>
		
			<cfif not len(newFolderName) or len(newFolderName) gt 255>
				<cfset errorNumber = 102>	
			<cfelseif directoryExists(currentFolderPath & newFolderName)>
				<cfset errorNumber = 101>
			<cfelseif reFind("^\.\.",newFolderName)>
				<cfset errorNumber = 103>
			<cfelse>
				<cfset errorNumber = 0>
		
				<cftry>
					<cfdirectory
						action="create"
						directory="#currentFolderPath##newFolderName#"
						mode="755">
					<cfcatch>
						<!--- 
						un-resolvable error numbers in ColdFusion:
						* 102 : Invalid folder name. 
						* 103 : You have no permissions to create the folder. 
						--->
						<cfset errorNumber = 110>
					</cfcatch>
				</cftry>
			</cfif>
			
			<cfset xmlContent = xmlContent & '<Error number="#errorNumber#" />'>
		
		</cfcase>
		
		
		<cfdefaultcase>
		
			<cfthrow type="fckeditor.connector" message="Illegal command: #url.command#">
			
		</cfdefaultcase>
		
		
	</cfswitch>
	
</cfif>

<cfscript>
	xmlHeader = '<?xml version="1.0" encoding="utf-8" ?><Connector command="#url.command#" resourceType="#url.type#">';
	xmlHeader = xmlHeader & '<CurrentFolder path="#url.currentFolder#" url="#userFilesPath##url.type##url.currentFolder#" />';
	xmlFooter = '</Connector>';
</cfscript>

<cfheader name="Expires" value="#GetHttpTimeString(Now())#">
<cfheader name="Pragma" value="no-cache">
<cfheader name="Cache-Control" value="no-cache, no-store, must-revalidate">
<cfcontent reset="true" type="text/xml; charset=UTF-8">
<cfoutput>#xmlHeader##xmlContent##xmlFooter#</cfoutput>	