<cfcomponent output="false" displayname="FCKeditor" hint="Create an instance of the FCKeditor.">
<!---
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: fckeditor.cfc
 * 	ColdFusion MX integration. 
 * 	Note this CFC is created for use only with Coldfusion MX and above.
 * 	For older version, check the fckeditor.cfm.
 * 
 * 	Syntax: 
 * 
 * 	<cfscript>
 * 			fckEditor = createObject("component", "fckEditorV2/fckeditor");
 * 			fckEditor.instanceName="myEditor";
 * 			fckEditor.basePath="/fckEditorV2/";
 * 			fckEditor.value="This is my <strong>initial</strong> html text.";
 * 			fckEditor.width="100%";
 * 			fckEditor.height="200";
 * 		 	// ... additional parameters ...
 * 			fckEditor.create(); // create instance now.
 * 	</cfscript>
 * 
 * 	See your macromedia coldfusion mx documentation for more info.
 * 
 * 	*** Note: 
 * 	Do not use path names with a "." (dot) in the name. This is a coldfusion 
 * 	limitation with the cfc invocation.
 * 
 * File Authors:
 * 		Hendrik Kramer (hk@lwd.de)
--->
<cffunction 
	name="create" 
	access="public" 
	output="true" 
	returntype="void" 
	hint="Initialize the FCKeditor instance."
>

	<cfparam name="this.instanceName" type="string" />
	<cfparam name="this.width" type="string" default="100%" />
	<cfparam name="this.height" type="string" default="200" />
	<cfparam name="this.toolbarSet" type="string" default="Default" />
	<cfparam name="this.value" type="string" default="" />
	<cfparam name="this.basePath" type="string" default="/fckeditor/" />
	<cfparam name="this.checkBrowser" type="boolean" default="true" />
	<cfparam name="this.config" type="struct" default="#structNew()#" />

	<cfscript>
	// display the html editor or a plain textarea?
	if( isCompatible() )
		showHTMLEditor();
	else
		showTextArea();
	</cfscript>

</cffunction>

<cffunction
	name="isCompatible"
	access="private"
	output="false"
	returnType="boolean"
	hint="Check browser compatibility via HTTP_USER_AGENT, if checkBrowser is true"
>

	<cfscript>
	var sAgent = lCase( cgi.HTTP_USER_AGENT );
	var stResult = "";
	var sBrowserVersion = "";

	// do not check if argument "checkBrowser" is false
	if( not this.checkBrowser )
		return true;

	// check for Internet Explorer ( >= 5.5 )
	if( find( "msie", sAgent ) and not find( "mac", sAgent ) and not find( "opera", sAgent ) )
	{
		// try to extract IE version
		stResult = reFind( "msie ([5-9]\.[0-9])", sAgent, 1, true );
		if( arrayLen( stResult.pos ) eq 2 )
		{
			// get IE Version
			sBrowserVersion = mid( sAgent, stResult.pos[2], stResult.len[2] );
			return ( sBrowserVersion GTE 5.5 );
		}
	}
	// check for Gecko ( >= 20030210+ )
	else if( find( "gecko/", sAgent ) )
	{
		// try to extract Gecko version date
		stResult = reFind( "gecko/(200[3-9][0-1][0-9][0-3][0-9])", sAgent, 1, true );
		if( arrayLen( stResult.pos ) eq 2 )
		{
			// get Gecko build (i18n date)
			sBrowserVersion = mid( sAgent, stResult.pos[2], stResult.len[2] );
			return ( sBrowserVersion GTE 20030210 );
		}
	}

	return false;
	</cfscript>
</cffunction>

<cffunction
	name="showTextArea"
	access="private"
	output="true"
	returnType="void"
	hint="Create a textarea field for non-compatible browsers."
>

	<cfscript>
	// append unit "px" for numeric width and/or height values
	if( isNumeric( this.width ) )
		this.width = this.width & "px";
	if( isNumeric( this.height ) )
		this.height = this.height & "px";
	</cfscript>

	<cfoutput>
	<div>
	<textarea name="#this.instanceName#" rows="4" cols="40" style="WIDTH: #width#; HEIGHT: #height#">#HTMLEditFormat(this.value)#</textarea>
	</div>
	</cfoutput>

</cffunction>

<cffunction
	name="showHTMLEditor"
	access="private"
	output="true"
	returnType="void"
	hint="Create the html editor instance for compatible browsers."
>
	
	<cfscript>
	var sURL = "";
	
	// try to fix the basePath, if ending slash is missing
	if( len( this.basePath) and right( this.basePath, 1 ) is not "/" )
		this.basePath = this.basePath & "/";

	// construct the url
	sURL = this.basePath & "editor/fckeditor.html?InstanceName=" & this.instanceName;

	// append toolbarset name to the url
	if( len( this.toolbarSet ) )
		sURL = sURL & "&amp;Toolbar=" & this.toolbarSet;
	</cfscript>

	<cfoutput>
	<div>
	<input type="hidden" id="#this.instanceName#" name="#this.instanceName#" value="#HTMLEditFormat(this.value)#" style="display:none" />
	<input type="hidden" id="#this.instanceName#___Config" value="#GetConfigFieldString()#" style="display:none" />
	<iframe id="#this.instanceName#___Frame" src="#sURL#" width="#this.width#" height="#this.height#" frameborder="0" scrolling="no"></iframe>
	</div>
	</cfoutput>

</cffunction>

<cffunction
	name="GetConfigFieldString"
	access="private"
	output="false"
	returnType="string"
	hint="Create configuration string: Key1=Value1&Key2=Value2&... (Key/Value:HTML encoded)"
>

	<cfscript>
	var sParams = "";
	var key = "";
	var fieldValue = "";
	var fieldLabel = "";
	var lConfigKeys = "";
	var iPos = "";
	
	/**
	 * CFML doesn't store casesensitive names for structure keys, but the configuration names must be casesensitive for js.
	 * So we need to find out the correct case for the configuration keys.
	 * We "fix" this by comparing the caseless configuration keys to a list of all available configuration options in the correct case.
	 * changed 20041206 hk@lwd.de (improvements are welcome!)
	 */
	lConfigKeys = lConfigKeys & "CustomConfigurationsPath,EditorAreaCSS,DocType,BaseHref,FullPage,Debug,SkinPath,PluginsPath,AutoDetectLanguage,DefaultLanguage,ContentLangDirection,EnableXHTML,EnableSourceXHTML,ProcessHTMLEntities,IncludeLatinEntities,IncludeGreekEntities";
	lConfigKeys = lConfigKeys & ",FillEmptyBlocks,FormatSource,FormatOutput,FormatIndentator,GeckoUseSPAN,StartupFocus,ForcePasteAsPlainText,ForceSimpleAmpersand,TabSpaces,ShowBorders,UseBROnCarriageReturn";
	lConfigKeys = lConfigKeys & ",ToolbarStartExpanded,ToolbarCanCollapse,ToolbarSets,ContextMenu,FontColors,FontNames,FontSizes,FontFormats,StylesXmlPath,SpellChecker,IeSpellDownloadUrl,MaxUndoLevels";
	lConfigKeys = lConfigKeys & ",LinkBrowser,LinkBrowserURL,LinkBrowserWindowWidth,LinkBrowserWindowHeight";
	lConfigKeys = lConfigKeys & ",LinkUpload,LinkUploadURL,LinkUploadWindowWidth,LinkUploadWindowHeight,LinkUploadAllowedExtensions,LinkUploadDeniedExtensions";
	lConfigKeys = lConfigKeys & ",ImageBrowser,ImageBrowserURL,ImageBrowserWindowWidth,ImageBrowserWindowHeight,SmileyPath,SmileyImages,SmileyColumns,SmileyWindowWidth,SmileyWindowHeight";
	
	for( key in this.config )
	{
		iPos = listFindNoCase( lConfigKeys, key );
		if( iPos GT 0 )
		{
			if( len( sParams ) )
				sParams = sParams & "&amp;";

			fieldValue = this.config[key];
			fieldName = listGetAt( lConfigKeys, iPos );
			
			// set all boolean possibilities in CFML to true/false values
			if( isBoolean( fieldValue) and fieldValue )
				fieldValue = "true";
			else if( isBoolean( fieldValue) )
				fieldValue = "false";
		
			sParams = sParams & HTMLEditFormat( fieldName ) & '=' & HTMLEditFormat( fieldValue );
		}
	}
	return sParams;
	</cfscript>

</cffunction>

</cfcomponent>