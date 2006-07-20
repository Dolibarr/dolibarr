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
 * 	This is a sample plugin definition file.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// Here we define our custom Style combo, with custom widths.
var oMyBigStyleCombo = new FCKToolbarStyleCombo() ;
oMyBigStyleCombo.FieldWidth = 250 ;
oMyBigStyleCombo.PanelWidth = 300 ;
FCKToolbarItems.RegisterItem( 'My_BigStyle', oMyBigStyleCombo ) ;


// ##### Defining a custom context menu entry.

// ## 1. Define the command to be executed when selecting the context menu item.
var oMyCMCommand = new Object() ;
oMyCMCommand.Name = 'OpenImage' ;

// This is the standard function used to execute the command (called when clicking in the context menu item).
oMyCMCommand.Execute = function()
{
	// This command is called only when an image element is selected (IMG).
	// Get image URL (src).
	var sUrl = FCKSelection.GetSelectedElement().src ;
	
	// Open the URL in a new window.
	window.top.open( sUrl ) ;
}

// This is the standard function used to retrieve the command state (it could be disabled for some reason).
oMyCMCommand.GetState = function()
{
	// Let's make it always enabled.
	return FCK_TRISTATE_OFF ;
}

// ## 2. Register our custom command.
FCKCommands.RegisterCommand( 'OpenImage', oMyCMCommand ) ;

// ## 3. Define the context menu "listener".
var oMyContextMenuListener = new Object() ;

// This is the standard function called right before sowing the context menu.
oMyContextMenuListener.AddItems = function( contextMenu, tag, tagName )
{
	// Let's show our custom option only for images.
	if ( tagName == 'IMG' )
	{
		contextMenu.AddSeparator() ;
		contextMenu.AddItem( 'OpenImage', 'Open image in a new window (Custom)' ) ;
	}
}

// ## 4. Register our context menu listener.
FCK.ContextMenu.RegisterListener( oMyContextMenuListener ) ;