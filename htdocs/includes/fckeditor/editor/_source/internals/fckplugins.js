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
 * File Name: fckplugins.js
 * 	Defines the FCKPlugins object that is responsible for loading the Plugins.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKPlugins = FCK.Plugins = new Object() ;
FCKPlugins.ItemsCount = 0 ;
FCKPlugins.Items = new Object() ;
	
FCKPlugins.Load = function()
{
	var oItems = FCKPlugins.Items ;

	// build the plugins collection.
	for ( var i = 0 ; i < FCKConfig.Plugins.Items.length ; i++ )
	{
		var oItem = FCKConfig.Plugins.Items[i] ;
		var oPlugin = oItems[ oItem[0] ] = new FCKPlugin( oItem[0], oItem[1], oItem[2] ) ;
		FCKPlugins.ItemsCount++ ;
	}

	// Load all items in the plugins collection.
	for ( var s in oItems )
		oItems[s].Load() ;

	// This is a self destroyable function (must be called once).
	FCKPlugins.Load = null ;
}