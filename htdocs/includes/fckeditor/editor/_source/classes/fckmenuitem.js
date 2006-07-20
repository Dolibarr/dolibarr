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
 * File Name: fckmenuitem.js
 * 	Defines and renders a menu items in a menu block.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */


var FCKMenuItem = function( parentMenuBlock, name, label, iconPathOrStripInfoArray, isDisabled )
{
	this.Name		= name ;
	this.Label		= label || name ;
	this.IsDisabled	= isDisabled ;
	
	this.Icon = new FCKIcon( iconPathOrStripInfoArray ) ;
	
	this.SubMenu			= new FCKMenuBlockPanel() ;
	this.SubMenu.Parent		= parentMenuBlock ;
	this.SubMenu.OnClick	= FCKTools.CreateEventListener( FCKMenuItem_SubMenu_OnClick, this ) ;

	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( this, FCKMenuItem_Cleanup ) ;
}


FCKMenuItem.prototype.AddItem = function( name, label, iconPathOrStripInfoArrayOrIndex, isDisabled )
{
	this.HasSubMenu = true ;
	return this.SubMenu.AddItem( name, label, iconPathOrStripInfoArrayOrIndex, isDisabled ) ;
}

FCKMenuItem.prototype.AddSeparator = function()
{
	this.SubMenu.AddSeparator() ;
}

FCKMenuItem.prototype.Create = function( parentTable )
{
	var bHasSubMenu = this.HasSubMenu ;
	
	var oDoc = parentTable.ownerDocument ;		// This is not IE 5.5

	// Add a row in the table to hold the menu item.
	var r = this.MainElement = parentTable.insertRow(-1) ;
	r.className = this.IsDisabled ? 'MN_Item_Disabled' : 'MN_Item' ;

	// Set the row behavior.
	if ( !this.IsDisabled )
	{
		FCKTools.AddEventListenerEx( r, 'mouseover', FCKMenuItem_OnMouseOver, [ this ] ) ;
		FCKTools.AddEventListenerEx( r, 'click', FCKMenuItem_OnClick, [ this ] ) ;

		if ( !bHasSubMenu )
			FCKTools.AddEventListenerEx( r, 'mouseout', FCKMenuItem_OnMouseOut, [ this ] ) ;
	}
	
	// Create the icon cell.
	var eCell = r.insertCell(-1) ;
	eCell.className = 'MN_Icon' ;
	eCell.appendChild( this.Icon.CreateIconElement( oDoc ) ) ;

	// Create the label cell.
	eCell = r.insertCell(-1) ;
	eCell.className = 'MN_Label' ;
	eCell.noWrap = true ;
	eCell.appendChild( oDoc.createTextNode( this.Label ) ) ;
	
	// Create the arrow cell and setup the sub menu panel (if needed).
	eCell = r.insertCell(-1) ;
	if ( bHasSubMenu )
	{
		eCell.className = 'MN_Arrow' ;

		// The arrow is a fixed size image.
		var eArrowImg = eCell.appendChild( oDoc.createElement( 'IMG' ) ) ;
		eArrowImg.src = FCK_IMAGES_PATH + 'arrow_' + FCKLang.Dir + '.gif' ;
		eArrowImg.width	 = 4 ;
		eArrowImg.height = 7 ;
		
		this.SubMenu.Create() ;
		this.SubMenu.Panel.OnHide = FCKTools.CreateEventListener( FCKMenuItem_SubMenu_OnHide, this ) ;
	}
}

FCKMenuItem.prototype.Activate = function()
{
	this.MainElement.className = 'MN_Item_Over' ;

	if ( this.HasSubMenu )
	{
		// Show the child menu block. The ( +2, -2 ) correction is done because
		// of the padding in the skin. It is not a good solution because one
		// could change the skin and so the final result would not be accurate.
		// For now it is ok because we are controlling the skin.
		this.SubMenu.Show( this.MainElement.offsetWidth + 2, -2, this.MainElement ) ;
	}

	FCKTools.RunFunction( this.OnActivate, this ) ;
}

FCKMenuItem.prototype.Deactivate = function()
{
	this.MainElement.className = 'MN_Item' ;

	if ( this.HasSubMenu )
		this.SubMenu.Hide() ;
}

/* Events */

function FCKMenuItem_SubMenu_OnClick( clickedItem, listeningItem )
{
	FCKTools.RunFunction( listeningItem.OnClick, listeningItem, [ clickedItem ] ) ;
}

function FCKMenuItem_SubMenu_OnHide( menuItem )
{
	menuItem.Deactivate() ;
}

function FCKMenuItem_OnClick( ev, menuItem )
{
	if ( menuItem.HasSubMenu )
		menuItem.Activate() ;
	else
	{
		menuItem.Deactivate() ;
		FCKTools.RunFunction( menuItem.OnClick, menuItem, [ menuItem ] ) ;
	}
}

function FCKMenuItem_OnMouseOver( ev, menuItem )
{
	menuItem.Activate() ;
}

function FCKMenuItem_OnMouseOut( ev, menuItem )
{
	menuItem.Deactivate() ;
}

function FCKMenuItem_Cleanup()
{
	this.MainElement = null ;
}