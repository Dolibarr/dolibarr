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
 * File Name: fckmenublock.js
 * 	Renders a list of menu items.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */


var FCKMenuBlock = function()
{
	this._Items	= new Array() ;
}


FCKMenuBlock.prototype.AddItem = function( name, label, iconPathOrStripInfoArrayOrIndex, isDisabled )
{
	var oItem = new FCKMenuItem( this, name, label, iconPathOrStripInfoArrayOrIndex, isDisabled ) ;
	
	oItem.OnClick		= FCKTools.CreateEventListener( FCKMenuBlock_Item_OnClick, this ) ;
	oItem.OnActivate	= FCKTools.CreateEventListener( FCKMenuBlock_Item_OnActivate, this ) ;
	
	this._Items.push( oItem ) ;

	return oItem ;
}

FCKMenuBlock.prototype.AddSeparator = function()
{
	this._Items.push( new FCKMenuSeparator() ) ;
}

FCKMenuBlock.prototype.RemoveAllItems = function()
{
	this._Items = new Array() ;
	
	var eItemsTable = this._ItemsTable ;
	if ( eItemsTable )
	{
		while ( eItemsTable.rows.length > 0 )
			eItemsTable.deleteRow( 0 ) ;
	}
}

FCKMenuBlock.prototype.Create = function( parentElement )
{
	if ( !this._ItemsTable )
	{
		if ( FCK.IECleanup )
			FCK.IECleanup.AddItem( this, FCKMenuBlock_Cleanup ) ;

		this._Window = FCKTools.GetElementWindow( parentElement ) ;

		var oDoc = parentElement.ownerDocument ;

		var eTable = parentElement.appendChild( oDoc.createElement( 'table' ) ) ;
		eTable.cellPadding = 0 ;
		eTable.cellSpacing = 0 ;

		FCKTools.DisableSelection( eTable ) ;
		
		var oMainElement = eTable.insertRow(-1).insertCell(-1) ;
		oMainElement.className = 'MN_Menu' ;
	
		var eItemsTable = this._ItemsTable = oMainElement.appendChild( oDoc.createElement( 'table' ) ) ;
		eItemsTable.cellPadding = 0 ;
		eItemsTable.cellSpacing = 0 ;		
	}
	
	for ( var i = 0 ; i < this._Items.length ; i++ )
		this._Items[i].Create( this._ItemsTable ) ;
}

/* Events */

function FCKMenuBlock_Item_OnClick( clickedItem, menuBlock )
{
	FCKTools.RunFunction( menuBlock.OnClick, menuBlock, [ clickedItem ] ) ;
}

function FCKMenuBlock_Item_OnActivate( menuBlock )
{
	var oActiveItem = menuBlock._ActiveItem ;
	
	if ( oActiveItem && oActiveItem != this )
	{
		// Set the focus to this menu block window (to fire OnBlur on opened panels).
		if ( !FCKBrowserInfo.IsIE && oActiveItem.HasSubMenu && !this.HasSubMenu )
			menuBlock._Window.focus() ;

		oActiveItem.Deactivate() ;		
	}

	menuBlock._ActiveItem = this ;
}

function FCKMenuBlock_Cleanup()
{
	this._Window = null ;
	this._ItemsTable = null ;
}

// ################# //

var FCKMenuSeparator = function()
{}

FCKMenuSeparator.prototype.Create = function( parentTable )
{
	var oDoc = parentTable.ownerDocument ;	// This is IE 6+

	var r = parentTable.insertRow(-1) ;
	
	var eCell = r.insertCell(-1) ;
	eCell.className = 'MN_Separator MN_Icon' ;

	eCell = r.insertCell(-1) ;
	eCell.className = 'MN_Separator' ;
	eCell.appendChild( oDoc.createElement( 'DIV' ) ).className = 'MN_Separator_Line' ;

	eCell = r.insertCell(-1) ;
	eCell.className = 'MN_Separator' ;
	eCell.appendChild( oDoc.createElement( 'DIV' ) ).className = 'MN_Separator_Line' ;
}