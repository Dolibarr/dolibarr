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
 * File Name: fcktoolbarstylecombo.js
 * 	FCKToolbarPanelButton Class: Handles the Fonts combo selector.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbarStyleCombo = function( tooltip, style )
{
	this.CommandName = 'Style' ;
	this.Label		= this.GetLabel() ;
	this.Tooltip	= tooltip ? tooltip : this.Label ;
	this.Style		= style ? style : FCK_TOOLBARITEM_ICONTEXT ;
}

// Inherit from FCKToolbarSpecialCombo.
FCKToolbarStyleCombo.prototype = new FCKToolbarSpecialCombo ;


FCKToolbarStyleCombo.prototype.GetLabel = function()
{
	return FCKLang.Style ;
}

FCKToolbarStyleCombo.prototype.CreateItems = function( targetSpecialCombo )
{
	var oTargetDoc = targetSpecialCombo._Panel.Document ;
	
	// Add the Editor Area CSS to the Styles panel so the style classes are previewed correctly.
	var aCSSs = FCKConfig.EditorAreaCSS ;
	for ( var i = 0 ; i < aCSSs.length ; i++ )
		FCKTools.AppendStyleSheet( oTargetDoc, aCSSs[i] ) ;
	
	oTargetDoc.body.className += ' ForceBaseFont' ;

	// For some reason Gecko is blocking inside the "RefreshVisibleItems" function.
	if ( ! FCKBrowserInfo.IsGecko )
		targetSpecialCombo.OnBeforeClick = this.RefreshVisibleItems ;

	// Add the styles to the special combo.
	var aCommandStyles = FCK.ToolbarSet.CurrentInstance.Commands.GetCommand( this.CommandName ).Styles ;
	for ( var s in aCommandStyles )
	{
		var oStyle = aCommandStyles[s] ;
		var oItem ;
		
		if ( oStyle.IsObjectElement )
			oItem = targetSpecialCombo.AddItem( s, s ) ;
		else
			oItem = targetSpecialCombo.AddItem( s, oStyle.GetOpenerTag() + s + oStyle.GetCloserTag() ) ;
			
		oItem.Style = oStyle ;
	}
}

FCKToolbarStyleCombo.prototype.RefreshActiveItems = function( targetSpecialCombo )
{
	// Clear the actual selection.
	targetSpecialCombo.DeselectAll() ;
	
	// Get the active styles.
	var aStyles = FCK.ToolbarSet.CurrentInstance.Commands.GetCommand( this.CommandName ).GetActiveStyles() ;
	
	if ( aStyles.length > 0 )
	{
		// Select the active styles in the combo.
		for ( var i = 0 ; i < aStyles.length ; i++ )
			targetSpecialCombo.SelectItem( aStyles[i].Name ) ;

		// Set the combo label to the first style in the collection.
		targetSpecialCombo.SetLabelById( aStyles[0].Name ) ;
	}
	else
		targetSpecialCombo.SetLabel('') ;
}

FCKToolbarStyleCombo.prototype.RefreshVisibleItems = function( targetSpecialCombo )
{
	if ( FCKSelection.GetType() == 'Control' )
		var sTagName = FCKSelection.GetSelectedElement().tagName ;

	for ( var i in targetSpecialCombo.Items )
	{
		var oItem = targetSpecialCombo.Items[i] ;
		if ( ( sTagName && oItem.Style.Element == sTagName ) || ( ! sTagName && ! oItem.Style.IsObjectElement ) )
			oItem.style.display = '' ;
		else
			oItem.style.display = 'none' ;	// For some reason Gecko is blocking here.
	}
}