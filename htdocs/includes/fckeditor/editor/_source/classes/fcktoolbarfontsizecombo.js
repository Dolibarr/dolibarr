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
 * File Name: fcktoolbarfontsizecombo.js
 * 	FCKToolbarPanelButton Class: Handles the Fonts combo selector.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbarFontSizeCombo = function( tooltip, style )
{
	this.CommandName	= 'FontSize' ;
	this.Label		= this.GetLabel() ;
	this.Tooltip	= tooltip ? tooltip : this.Label ;
	this.Style		= style ? style : FCK_TOOLBARITEM_ICONTEXT ;
}

// Inherit from FCKToolbarSpecialCombo.
FCKToolbarFontSizeCombo.prototype = new FCKToolbarSpecialCombo ;


FCKToolbarFontSizeCombo.prototype.GetLabel = function()
{
	return FCKLang.FontSize ;
}

FCKToolbarFontSizeCombo.prototype.CreateItems = function( targetSpecialCombo )
{
	targetSpecialCombo.FieldWidth = 70 ;
	
	var aSizes = FCKConfig.FontSizes.split(';') ;
	
	for ( var i = 0 ; i < aSizes.length ; i++ )
	{
		var aSizeParts = aSizes[i].split('/') ;
		this._Combo.AddItem( aSizeParts[0], '<font size="' + aSizeParts[0] + '">' + aSizeParts[1] + '</font>', aSizeParts[1] ) ;
	}
}