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
 * File Name: fckmenublockpanel.js
 * 	This class is a menu block that behaves like a panel. It's a mix of the
 * 	FCKMenuBlock and FCKPanel classes.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */


var FCKMenuBlockPanel = function()
{
	// Call the "base" constructor.
	FCKMenuBlock.call( this ) ;
}

FCKMenuBlockPanel.prototype = new FCKMenuBlock() ;


// Override the create method.
FCKMenuBlockPanel.prototype.Create = function()
{
	var oPanel = this.Panel = ( this.Parent && this.Parent.Panel ? this.Parent.Panel.CreateChildPanel() : new FCKPanel() ) ;
	oPanel.AppendStyleSheet( FCKConfig.SkinPath + 'fck_editor.css' ) ;

	// Call the "base" implementation.
	FCKMenuBlock.prototype.Create.call( this, oPanel.MainNode ) ;
}

FCKMenuBlockPanel.prototype.Show = function( x, y, relElement )
{
	if ( !this.Panel.CheckIsOpened() )
		this.Panel.Show( x, y, relElement ) ;
}

FCKMenuBlockPanel.prototype.Hide = function()
{
	if ( this.Panel.CheckIsOpened() )
		this.Panel.Hide() ;
}