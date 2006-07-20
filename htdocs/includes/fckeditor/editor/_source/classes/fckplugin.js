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
 * File Name: fckplugin.js
 * 	FCKPlugin Class: Represents a single plugin.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKPlugin = function( name, availableLangs, basePath )
{
	this.Name = name ;
	this.BasePath = basePath ? basePath : FCKConfig.PluginsPath ;
	this.Path = this.BasePath + name + '/' ;
	
	if ( !availableLangs || availableLangs.length == 0 )
		this.AvailableLangs = new Array() ;
	else
		this.AvailableLangs = availableLangs.split(',') ;
}

FCKPlugin.prototype.Load = function()
{
	// Load the language file, if defined.
	if ( this.AvailableLangs.length > 0 )
	{
		var sLang ;
		
		// Check if the plugin has the language file for the active language.
		if ( this.AvailableLangs.indexOf( FCKLanguageManager.ActiveLanguage.Code ) >= 0 )
			sLang = FCKLanguageManager.ActiveLanguage.Code ;
		else
			// Load the default language file (first one) if the current one is not available.
			sLang = this.AvailableLangs[0] ;
		
		// Add the main plugin script.
		LoadScript( this.Path + 'lang/' + sLang + '.js' ) ;		
	}
		
	// Add the main plugin script.
	LoadScript( this.Path + 'fckplugin.js' ) ;
}