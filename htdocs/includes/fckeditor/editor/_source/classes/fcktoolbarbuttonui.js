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
 * File Name: fcktoolbarbuttonui.js
 * 	FCKToolbarButtonUI Class: interface representation of a toolbar button.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbarButtonUI = function( name, label, tooltip, iconPathOrStripInfoArray, style, state )
{
	this.Name		= name ;
	this.Label		= label || name ;
	this.Tooltip	= tooltip || this.Label ;
	this.Style		= style || FCK_TOOLBARITEM_ONLYICON ;
	this.State		= state || FCK_TRISTATE_OFF ;
	
	this.Icon = new FCKIcon( iconPathOrStripInfoArray ) ;

	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( this, FCKToolbarButtonUI_Cleanup ) ;
}


FCKToolbarButtonUI.prototype._CreatePaddingElement = function( document )
{
	var oImg = document.createElement( 'IMG' ) ;
	oImg.className = 'TB_Button_Padding' ;
	oImg.src = FCK_SPACER_PATH ;
	return oImg ;
}

FCKToolbarButtonUI.prototype.Create = function( parentElement )
{
	var oMainElement = this.MainElement ;
	
	if ( oMainElement )
	{
		FCKToolbarButtonUI_Cleanup.call(this) ;
		
		if ( oMainElement.parentNode )
			oMainElement.parentNode.removeChild( oMainElement ) ;
		oMainElement = this.MainElement = null ;
	}

	var oDoc = parentElement.ownerDocument ;	// This is IE 6+
	
	// Create the Main Element.
	oMainElement = this.MainElement = oDoc.createElement( 'DIV' ) ;
	oMainElement._FCKButton = this ;		// IE Memory Leak (Circular reference).
	oMainElement.title		= this.Tooltip ;

	// The following will prevent the button from catching the focus.
	if ( FCKBrowserInfo.IsGecko )
		 oMainElement.onmousedown	= FCKTools.CancelEvent ;

	this.ChangeState( this.State, true ) ;

	if ( this.Style == FCK_TOOLBARITEM_ONLYICON && !this.ShowArrow )
	{
		// <td><div class="TB_Button_On" title="Smiley">{Image}</div></td>
		
		oMainElement.appendChild( this.Icon.CreateIconElement( oDoc ) ) ;
	}
	else
	{
		// <td><div class="TB_Button_On" title="Smiley"><table cellpadding="0" cellspacing="0"><tr><td>{Image}</td><td nowrap>Toolbar Button</td><td><img class="TB_Button_Padding"></td></tr></table></div></td>
		// <td><div class="TB_Button_On" title="Smiley"><table cellpadding="0" cellspacing="0"><tr><td><img class="TB_Button_Padding"></td><td nowrap>Toolbar Button</td><td><img class="TB_Button_Padding"></td></tr></table></div></td>
		
		var oTable = oMainElement.appendChild( oDoc.createElement( 'TABLE' ) ) ;
		oTable.cellPadding = 0 ;
		oTable.cellSpacing = 0 ;

		var oRow = oTable.insertRow(-1) ;
		
		// The Image cell (icon or padding).
		var oCell = oRow.insertCell(-1) ;
		
		if ( this.Style == FCK_TOOLBARITEM_ONLYICON || this.Style == FCK_TOOLBARITEM_ICONTEXT )
			oCell.appendChild( this.Icon.CreateIconElement( oDoc ) ) ;
		else
			oCell.appendChild( this._CreatePaddingElement( oDoc ) ) ;
		
		if ( this.Style == FCK_TOOLBARITEM_ONLYTEXT || this.Style == FCK_TOOLBARITEM_ICONTEXT )
		{
			// The Text cell.
			oCell = oRow.insertCell(-1) ;
			oCell.className = 'TB_Button_Text' ;
			oCell.noWrap = true ;
			oCell.appendChild( oDoc.createTextNode( this.Label ) ) ;
		}
		
		if ( this.ShowArrow )
		{
			if ( this.Style != FCK_TOOLBARITEM_ONLYICON )
			{	
				// A padding cell.
				oRow.insertCell(-1).appendChild( this._CreatePaddingElement( oDoc ) ) ;
			}
			
			oCell = oRow.insertCell(-1) ;
			var eImg = oCell.appendChild( oDoc.createElement( 'IMG' ) ) ;
			eImg.src	= FCKConfig.SkinPath + 'images/toolbar.buttonarrow.gif' ;
			eImg.width	= 5 ;
			eImg.height	= 3 ;
		}

		// The last padding cell.
		oCell = oRow.insertCell(-1) ;
		oCell.appendChild( this._CreatePaddingElement( oDoc ) ) ;
	}
	
	parentElement.appendChild( oMainElement ) ;
}

FCKToolbarButtonUI.prototype.ChangeState = function( newState, force )
{
	if ( !force && this.State == newState )
		return ;

	var e = this.MainElement ;

	switch ( parseInt( newState ) )
	{
		case FCK_TRISTATE_OFF :
			e.className		= 'TB_Button_Off' ;
			e.onmouseover	= FCKToolbarButton_OnMouseOverOff ;
			e.onmouseout	= FCKToolbarButton_OnMouseOutOff ;
			e.onclick		= FCKToolbarButton_OnClick ;
			
			break ;
			
		case FCK_TRISTATE_ON :
			e.className		= 'TB_Button_On' ;
			e.onmouseover	= FCKToolbarButton_OnMouseOverOn ;
			e.onmouseout	= FCKToolbarButton_OnMouseOutOn ;
			e.onclick		= FCKToolbarButton_OnClick ;
			
			break ;

		case FCK_TRISTATE_DISABLED :
			e.className		= 'TB_Button_Disabled' ;
			e.onmouseover	= null ;
			e.onmouseout	= null ;
			e.onclick		= null ;
			bEnableEvents = false ;
			break ;
	}

	this.State = newState ;
}

function FCKToolbarButtonUI_Cleanup()
{
	if ( this.MainElement )
	{
		this.MainElement._FCKButton = null ;
		this.MainElement = null ;
	}
}	

// Event Handlers.

function FCKToolbarButton_OnMouseOverOn()
{
	this.className = 'TB_Button_On_Over' ;
}

function FCKToolbarButton_OnMouseOutOn()
{
	this.className = 'TB_Button_On' ;
}

function FCKToolbarButton_OnMouseOverOff()
{
	this.className = 'TB_Button_Off_Over' ;
}

function FCKToolbarButton_OnMouseOutOff()
{
	this.className = 'TB_Button_Off' ;
}

function FCKToolbarButton_OnClick( e )
{
	if ( this._FCKButton.OnClick )
		this._FCKButton.OnClick( this._FCKButton ) ;
}

/* 
	Sample outputs:

	This is the base structure. The variation is the image that is marked as {Image}:
		<td><div class="TB_Button_On" title="Smiley">{Image}</div></td>
		<td><div class="TB_Button_On" title="Smiley"><table cellpadding="0" cellspacing="0"><tr><td>{Image}</td><td nowrap>Toolbar Button</td><td><img class="TB_Button_Padding"></td></tr></table></div></td>
		<td><div class="TB_Button_On" title="Smiley"><table cellpadding="0" cellspacing="0"><tr><td><img class="TB_Button_Padding"></td><td nowrap>Toolbar Button</td><td><img class="TB_Button_Padding"></td></tr></table></div></td>

	These are samples of possible {Image} values:
		
		Strip - IE version:
			<div class="TB_Button_Image"><img src="strip.gif" style="top:-16px"></div>
		
		Strip : Firefox, Safari and Opera version
			<img class="TB_Button_Image" style="background-position: 0px -16px;background-image: url(strip.gif);">
		
		No-Strip : Browser independent:
			<img class="TB_Button_Image" src="smiley.gif">
*/