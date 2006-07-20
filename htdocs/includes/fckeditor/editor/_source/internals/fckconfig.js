/*
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
 * File Name: fckconfig.js
 * 	Creates and initializes the FCKConfig object.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKConfig = FCK.Config = new Object() ;

/*
	For the next major version (probably 3.0) we should move all this stuff to
	another dedicated object and leave FCKConfig as a holder object for settings only).
*/

// Editor Base Path
if ( document.location.protocol == 'file:' )
{
	FCKConfig.BasePath = unescape( document.location.pathname.substr(1) ) ;
	FCKConfig.BasePath = FCKConfig.BasePath.replace( /\\/gi, '/' ) ;
	FCKConfig.BasePath = 'file://' + FCKConfig.BasePath.substring(0,FCKConfig.BasePath.lastIndexOf('/')+1) ;
	FCKConfig.FullBasePath = FCKConfig.BasePath ;
}
else
{
	FCKConfig.BasePath = document.location.pathname.substring(0,document.location.pathname.lastIndexOf('/')+1) ;
	FCKConfig.FullBasePath = document.location.protocol + '//' + document.location.host + FCKConfig.BasePath ;
}

FCKConfig.EditorPath = FCKConfig.BasePath.replace( /editor\/$/, '' ) ;

// There is a bug in Gecko. If the editor is hidden on startup, an error is 
// thrown when trying to get the screen dimentions.
try
{
	FCKConfig.ScreenWidth	= screen.width ;
	FCKConfig.ScreenHeight	= screen.height ;
}
catch (e) 
{
	FCKConfig.ScreenWidth	= 800 ;
	FCKConfig.ScreenHeight	= 600 ;
}

// Override the actual configuration values with the values passed throw the 
// hidden field "<InstanceName>___Config".
FCKConfig.ProcessHiddenField = function()
{
	this.PageConfig = new Object() ;

	// Get the hidden field.
	var oConfigField = window.parent.document.getElementById( FCK.Name + '___Config' ) ;
	
	// Do nothing if the config field was not defined.
	if ( ! oConfigField ) return ;

	var aCouples = oConfigField.value.split('&') ;

	for ( var i = 0 ; i < aCouples.length ; i++ )
	{
		if ( aCouples[i].length == 0 )
			continue ;

		var aConfig = aCouples[i].split( '=' ) ;
		var sKey = unescape( aConfig[0] ) ;
		var sVal = unescape( aConfig[1] ) ;

		if ( sKey == 'CustomConfigurationsPath' )	// The Custom Config File path must be loaded immediately.
			FCKConfig[ sKey ] = sVal ;
			
		else if ( sVal.toLowerCase() == "true" )	// If it is a boolean TRUE.
			this.PageConfig[ sKey ] = true ;
			
		else if ( sVal.toLowerCase() == "false" )	// If it is a boolean FALSE.
			this.PageConfig[ sKey ] = false ;
			
		else if ( ! isNaN( sVal ) )					// If it is a number.
			this.PageConfig[ sKey ] = parseInt( sVal ) ;
			
		else										// In any other case it is a string.
			this.PageConfig[ sKey ] = sVal ;
	}
}

function FCKConfig_LoadPageConfig()
{
	var oPageConfig = FCKConfig.PageConfig ;
	for ( var sKey in oPageConfig )
		FCKConfig[ sKey ] = oPageConfig[ sKey ] ;
}

function FCKConfig_PreProcess()
{
	var oConfig = FCKConfig ;
	
	// Force debug mode if fckdebug=true in the QueryString (main page).
	if ( oConfig.AllowQueryStringDebug && (/fckdebug=true/i).test( window.top.location.search ) )
		oConfig.Debug = true ;

	// Certifies that the "PluginsPath" configuration ends with a slash.
	if ( !oConfig.PluginsPath.endsWith('/') )
		oConfig.PluginsPath += '/' ;

	// EditorAreaCSS accepts an array of paths or a single path (as string).
	// In the last case, transform it in an array.
	if ( typeof( oConfig.EditorAreaCSS ) == 'string' )
		oConfig.EditorAreaCSS = [ oConfig.EditorAreaCSS ] ;
}

// Define toolbar sets collection.
FCKConfig.ToolbarSets = new Object() ;

// Defines the plugins collection.
FCKConfig.Plugins = new Object() ;
FCKConfig.Plugins.Items = new Array() ;

FCKConfig.Plugins.Add = function( name, langs, path )
{
	FCKConfig.Plugins.Items.AddItem( [name, langs, path] ) ;
}

// FCKConfig.ProtectedSource: object that holds a collection of Regular 
// Expressions that defined parts of the raw HTML that must remain untouched
// like custom tags, scripts, server side code, etc...
FCKConfig.ProtectedSource = new Object() ;
FCKConfig.ProtectedSource.RegexEntries = new Array() ;

FCKConfig.ProtectedSource.Add = function( regexPattern )
{
	this.RegexEntries.AddItem( regexPattern ) ;
}

FCKConfig.ProtectedSource.Protect = function( html )
{
	function _Replace( protectedSource )
	{
		var index = FCKTempBin.AddElement( protectedSource ) ;
		return '<!--{PS..' + index + '}-->' ;
	}
	
	for ( var i = 0 ; i < this.RegexEntries.length ; i++ )
	{
		html = html.replace( this.RegexEntries[i], _Replace ) ;
	}
	
	return html ;
}


FCKConfig.ProtectedSource.Revert = function( html, clearBin )
{
	function _Replace( m, opener, index )
	{
		var protectedValue = clearBin ? FCKTempBin.RemoveElement( index ) : FCKTempBin.Elements[ index ] ;
		// There could be protected source inside another one.
		return FCKConfig.ProtectedSource.Revert( protectedValue, clearBin ) ;
	}

	return html.replace( /(<|&lt;)!--\{PS..(\d+)\}--(>|&gt;)/g, _Replace ) ;
}

// First of any other protection, we must protect all comments to avoid 
// loosing them (of course, IE related).
FCKConfig.ProtectedSource.Add( /<!--[\s\S]*?-->/g ) ;