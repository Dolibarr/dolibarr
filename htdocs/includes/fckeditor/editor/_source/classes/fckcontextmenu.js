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
 * File Name: fckcontextmenu.js
 * 	FCKContextMenu Class: renders an control a context menu.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKContextMenu = function( parentWindow, mouseClickWindow, langDir )
{
	var oPanel = this._Panel = new FCKPanel( parentWindow, true ) ;
	oPanel.AppendStyleSheet( FCKConfig.SkinPath + 'fck_editor.css' ) ;
	oPanel.IsContextMenu = true ;
	
	var oMenuBlock = this._MenuBlock = new FCKMenuBlock() ;
	oMenuBlock.Panel = oPanel ;
	oMenuBlock.OnClick = FCKTools.CreateEventListener( FCKContextMenu_MenuBlock_OnClick, this ) ;
	
	this._Redraw = true ;
	
	this.SetMouseClickWindow( mouseClickWindow || parentWindow ) ;
}


FCKContextMenu.prototype.SetMouseClickWindow = function( mouseClickWindow )
{
	if ( !FCKBrowserInfo.IsIE )
	{
		this._Document = mouseClickWindow.document ;
		this._Document.addEventListener( 'contextmenu', FCKContextMenu_Document_OnContextMenu, false ) ;
	}
}

FCKContextMenu.prototype.AddItem = function( name, label, iconPathOrStripInfoArrayOrIndex, isDisabled )
{
	var oItem = this._MenuBlock.AddItem( name, label, iconPathOrStripInfoArrayOrIndex, isDisabled) ;
	this._Redraw = true ;
	return oItem ;
}

FCKContextMenu.prototype.AddSeparator = function()
{
	this._MenuBlock.AddSeparator() ;
	this._Redraw = true ;
}

FCKContextMenu.prototype.RemoveAllItems = function()
{
	this._MenuBlock.RemoveAllItems() ;
	this._Redraw = true ;
}

FCKContextMenu.prototype.AttachToElement = function( element )
{
	if ( FCKBrowserInfo.IsIE )
		FCKTools.AddEventListenerEx( element, 'contextmenu', FCKContextMenu_AttachedElement_OnContextMenu, this ) ;
	else
		element._FCKContextMenu = this ;

//	element.onmouseup		= FCKContextMenu_AttachedElement_OnMouseUp ;
}

function FCKContextMenu_Document_OnContextMenu( e )
{
	var el = e.target ;
	
	while ( el )
	{
		if ( el._FCKContextMenu )
		{
			FCKTools.CancelEvent( e ) ;
			FCKContextMenu_AttachedElement_OnContextMenu( e, el._FCKContextMenu, el ) ;
		}
		el = el.parentNode ;
	}
}

function FCKContextMenu_AttachedElement_OnContextMenu( ev, fckContextMenu, el )
{
//	var iButton = e ? e.which - 1 : event.button ;

//	if ( iButton != 2 )
//		return ;

	var eTarget = el || this ;

	if ( fckContextMenu.OnBeforeOpen )
		fckContextMenu.OnBeforeOpen.call( fckContextMenu, eTarget ) ;
	
	if ( fckContextMenu._Redraw )
	{
		fckContextMenu._MenuBlock.Create( fckContextMenu._Panel.MainNode ) ;
		fckContextMenu._Redraw = false ;
	}

	fckContextMenu._Panel.Show( 
		ev.pageX || ev.screenX, 
		ev.pageY || ev.screenY, 
		ev.currentTarget || null
	) ;
	
	return false ;
}

function FCKContextMenu_MenuBlock_OnClick( menuItem, contextMenu )
{
	contextMenu._Panel.Hide() ;
	FCKTools.RunFunction( contextMenu.OnItemClick, contextMenu, menuItem ) ;
}