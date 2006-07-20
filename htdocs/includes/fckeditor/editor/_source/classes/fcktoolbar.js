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
 * File Name: fcktoolbar.js
 * 	FCKToolbar Class: represents a toolbar in the toolbarset. It is a group of
 * 	toolbar items.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbar = function()
{
	this.Items = new Array() ;

	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( this, FCKToolbar_Cleanup ) ;
}

FCKToolbar.prototype.AddItem = function( item )
{
	return this.Items[ this.Items.length ] = item ;
}

FCKToolbar.prototype.AddButton = function( name, label, tooltip, iconPathOrStripInfoArrayOrIndex, style, state )
{
	if ( typeof( iconPathOrStripInfoArrayOrIndex ) == 'number' )
		 iconPathOrStripInfoArrayOrIndex = [ this.DefaultIconsStrip, this.DefaultIconSize, iconPathOrStripInfoArrayOrIndex ] ;

	var oButton = new FCKToolbarButtonUI( name, label, tooltip, iconPathOrStripInfoArrayOrIndex, style, state ) ;
	oButton._FCKToolbar = this ;
	oButton.OnClick = FCKToolbar_OnItemClick ;
	
	return this.AddItem( oButton ) ;
}

function FCKToolbar_OnItemClick( item )
{
	var oToolbar = item._FCKToolbar ;
	
	if ( oToolbar.OnItemClick )
		oToolbar.OnItemClick( oToolbar, item ) ;
}

FCKToolbar.prototype.AddSeparator = function()
{
	this.AddItem( new FCKToolbarSeparator() ) ;
}

FCKToolbar.prototype.Create = function( parentElement )
{
	if ( this.MainElement )
	{
//		this._Cleanup() ;
		if ( this.MainElement.parentNode )
			this.MainElement.parentNode.removeChild( this.MainElement ) ;
		this.MainElement = null ;
	}

	var oDoc = parentElement.ownerDocument ;	// This is IE 6+

	var e = this.MainElement = oDoc.createElement( 'table' ) ;
	e.className = 'TB_Toolbar' ;
	e.style.styleFloat = e.style.cssFloat = ( FCKLang.Dir == 'ltr' ? 'left' : 'right' ) ;
	e.dir = FCKLang.Dir ;
	e.cellPadding = 0 ;
	e.cellSpacing = 0 ;
	
	this.RowElement = e.insertRow(-1) ;
	
	// Insert the start cell.
	var eCell ;
	
	if ( !this.HideStart )
	{
		eCell = this.RowElement.insertCell(-1) ;
		eCell.appendChild( oDoc.createElement( 'div' ) ).className = 'TB_Start' ;
	}
	
	for ( var i = 0 ; i < this.Items.length ; i++ )
	{
		this.Items[i].Create( this.RowElement.insertCell(-1) ) ;
	}
	
	// Insert the ending cell.
	if ( !this.HideEnd )
	{
		eCell = this.RowElement.insertCell(-1) ;
		eCell.appendChild( oDoc.createElement( 'div' ) ).className = 'TB_End' ;
	}

	parentElement.appendChild( e ) ;
}

function FCKToolbar_Cleanup()
{
	this.MainElement = null ;
	this.RowElement = null ;
}

var FCKToolbarSeparator = function()
{}

FCKToolbarSeparator.prototype.Create = function( parentElement )
{
	parentElement.appendChild( parentElement.ownerDocument.createElement( 'div' ) ).className = 'TB_Separator' ;
}