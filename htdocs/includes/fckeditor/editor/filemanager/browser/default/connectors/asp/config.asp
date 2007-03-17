<!--
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
 * File Name: config.asp
 * 	Configuration file for the File Manager Connector for ASP.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
-->
<%

' SECURITY: You must explicitelly enable this "connector" (set it to "True"). 
Dim ConfigIsEnabled
ConfigIsEnabled = False

' Path to user files relative to the document root.
Dim ConfigUserFilesPath
ConfigUserFilesPath = "/userfiles/"

Dim ConfigAllowedExtensions, ConfigDeniedExtensions
Set ConfigAllowedExtensions	= CreateObject( "Scripting.Dictionary" )
Set ConfigDeniedExtensions	= CreateObject( "Scripting.Dictionary" )

ConfigAllowedExtensions.Add	"File", ""
ConfigDeniedExtensions.Add	"File", "html|htm|php|php2|php3|php4|php5|phtml|pwml|inc|asp|aspx|ascx|jsp|cfm|cfc|pl|bat|exe|com|dll|vbs|js|reg|cgi|htaccess|asis"

ConfigAllowedExtensions.Add	"Image", "jpg|gif|jpeg|png|bmp"
ConfigDeniedExtensions.Add	"Image", ""

ConfigAllowedExtensions.Add	"Flash", "swf|fla"
ConfigDeniedExtensions.Add	"Flash", ""

ConfigAllowedExtensions.Add	"Media", "swf|fla|jpg|gif|jpeg|png|avi|mpg|mpeg|mp(1-4)|wma|wmv|wav|mid|midi|rmi|rm|ram|rmvb|mov|qt"
ConfigDeniedExtensions.Add	"Media", ""

%>