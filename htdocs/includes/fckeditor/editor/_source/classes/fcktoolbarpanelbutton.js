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
 * File Name: fcktoolbarpanelbutton.js
 * 	FCKToolbarPanelButton Class: represents a special button in the toolbar
 * 	that shows a panel when pressed.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbarPanelButton = function( commandName, label, tooltip, style, icon )
{
	this.CommandName = commandName ;

	var oIcon ;
	
	if ( icon == null )
		oIcon = FCKConfig.SkinPath + 'toolbar/' + commandName.toLowerCase() + '.gif' ;
	else if ( typeof( icon ) == 'number' )
		oIcon = [ FCKConfig.SkinPath + 'fck_strip.gif', 16, icon ] ;
	
	var oUIButton = this._UIButton = new FCKToolbarButtonUI( commandName, label, tooltip, oIcon, style ) ;
	oUIButton._FCKToolbarPanelButton = this ;
	oUIButton.ShowArrow = true ;
	oUIButton.OnClick = FCKToolbarPanelButton_OnButtonClick ;
}

FCKToolbarPanelButton.prototype.TypeName = 'FCKToolbarPanelButton' ;

FCKToolbarPanelButton.prototype.Create = function( parentElement )
{
	parentElement.className += 'Menu' ;

	this._UIButton.Create( parentElement ) ;
	
	var oPanel = FCK.ToolbarSet.CurrentInstance.Commands.GetCommand( this.CommandName )._Panel ;
	oPanel._FCKToolbarPanelButton = this ;
	
	var eLineDiv = oPanel.Document.body.appendChild( oPanel.Document.createElement( 'div' ) ) ;
	eLineDiv.style.position = 'absolute' ;
	eLineDiv.style.top = '0px' ;
	
	var eLine = this.LineImg = eLineDiv.appendChild( oPanel.Document.createElement( 'IMG' ) ) ;
	eLine.className = 'TB_ConnectionLine' ;
//	eLine.style.backgroundColor = 'Red' ;
	eLine.src = FCK_SPACER_PATH ;

	oPanel.OnHide = FCKToolbarPanelButton_OnPanelHide ;
}

/*
	Events
*/

function FCKToolbarPanelButton_OnButtonClick( toolbarButton )
{
	var oButton = this._FCKToolbarPanelButton ;
	var e = oButton._UIButton.MainElement ;
	
	oButton._UIButton.ChangeState( FCK_TRISTATE_ON ) ;
	
	oButton.LineImg.style.width = ( e.offsetWidth - 2 ) + 'px' ;

	FCK.ToolbarSet.CurrentInstance.Commands.GetCommand( oButton.CommandName ).Execute( 0, e.offsetHeight - 1, e ) ; // -1 to be over the border
}

function FCKToolbarPanelButton_OnPanelHide()
{
	var oMenuButton = this._FCKToolbarPanelButton ;
	oMenuButton._UIButton.ChangeState( FCK_TRISTATE_OFF ) ;
}

// The Panel Button works like a normal button so the refresh state functions
// defined for the normal button can be reused here.
FCKToolbarPanelButton.prototype.RefreshState	= FCKToolbarButton.prototype.RefreshState ;
FCKToolbarPanelButton.prototype.Enable			= FCKToolbarButton.prototype.Enable ;
FCKToolbarPanelButton.prototype.Disable			= FCKToolbarButton.prototype.Disable ;
