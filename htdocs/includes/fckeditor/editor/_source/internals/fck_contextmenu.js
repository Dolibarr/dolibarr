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
 * File Name: fck_contextmenu.js
 * 	Defines the FCK.ContextMenu object that is responsible for all
 * 	Context Menu operations in the editing area.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCK.ContextMenu = new Object() ;
FCK.ContextMenu.Listeners = new Array() ;

FCK.ContextMenu.RegisterListener = function( listener )
{
	if ( listener )
		this.Listeners.push( listener ) ;
}

function FCK_ContextMenu_Init()
{
	var oInnerContextMenu = FCK.ContextMenu._InnerContextMenu = new FCKContextMenu( FCKBrowserInfo.IsIE ? window : window.parent, FCK.EditorWindow, FCKLang.Dir ) ;
	oInnerContextMenu.OnBeforeOpen	= FCK_ContextMenu_OnBeforeOpen ;
	oInnerContextMenu.OnItemClick	= FCK_ContextMenu_OnItemClick ;

	// Get the registering function.
	var oMenu = FCK.ContextMenu ;

	// Register all configured context menu listeners.
	for ( var i = 0 ; i < FCKConfig.ContextMenu.length ; i++ )
		oMenu.RegisterListener( FCK_ContextMenu_GetListener( FCKConfig.ContextMenu[i] ) ) ;
}

function FCK_ContextMenu_GetListener( listenerName )
{
	switch ( listenerName )
	{
		case 'Generic' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				menu.AddItem( 'Cut'		, FCKLang.Cut	, 7, FCKCommands.GetCommand( 'Cut' ).GetState() == FCK_TRISTATE_DISABLED ) ;
				menu.AddItem( 'Copy'	, FCKLang.Copy	, 8, FCKCommands.GetCommand( 'Copy' ).GetState() == FCK_TRISTATE_DISABLED ) ;
				menu.AddItem( 'Paste'	, FCKLang.Paste	, 9, FCKCommands.GetCommand( 'Paste' ).GetState() == FCK_TRISTATE_DISABLED ) ;
			}} ;

		case 'Table' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				var bIsTable	= ( tagName == 'TABLE' ) ;
				var bIsCell		= ( !bIsTable && FCKSelection.HasAncestorNode( 'TABLE' ) ) ;
				
				if ( bIsCell )
				{
					menu.AddSeparator() ;
					var oItem = menu.AddItem( 'Cell'	, FCKLang.CellCM ) ;
					oItem.AddItem( 'TableInsertCell'	, FCKLang.InsertCell, 58 ) ;
					oItem.AddItem( 'TableDeleteCells'	, FCKLang.DeleteCells, 59 ) ;
					oItem.AddItem( 'TableMergeCells'	, FCKLang.MergeCells, 60 ) ;
					oItem.AddItem( 'TableSplitCell'		, FCKLang.SplitCell, 61 ) ;
					oItem.AddSeparator() ;
					oItem.AddItem( 'TableCellProp'		, FCKLang.CellProperties, 57 ) ;

					menu.AddSeparator() ;
					oItem = menu.AddItem( 'Row'			, FCKLang.RowCM ) ;
					oItem.AddItem( 'TableInsertRow'		, FCKLang.InsertRow, 62 ) ;
					oItem.AddItem( 'TableDeleteRows'	, FCKLang.DeleteRows, 63 ) ;
					
					menu.AddSeparator() ;
					oItem = menu.AddItem( 'Column'		, FCKLang.ColumnCM ) ;
					oItem.AddItem( 'TableInsertColumn'	, FCKLang.InsertColumn, 64 ) ;
					oItem.AddItem( 'TableDeleteColumns'	, FCKLang.DeleteColumns, 65 ) ;
				}

				if ( bIsTable || bIsCell )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'TableDelete'			, FCKLang.TableDelete ) ;
					menu.AddItem( 'TableProp'			, FCKLang.TableProperties, 39 ) ;
				}
			}} ;

		case 'Link' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( FCK.GetNamedCommandState( 'Unlink' ) != FCK_TRISTATE_DISABLED )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Link'	, FCKLang.EditLink		, 34 ) ;
					menu.AddItem( 'Unlink'	, FCKLang.RemoveLink	, 35 ) ;
				}
			}} ;

		case 'Image' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'IMG' && !tag.getAttribute( '_fckfakelement' ) )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Image', FCKLang.ImageProperties, 37 ) ;
				}
			}} ;

		case 'Anchor' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'IMG' && tag.getAttribute( '_fckanchor' ) )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Anchor', FCKLang.AnchorProp, 36 ) ;
				}
			}} ;

		case 'Flash' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'IMG' && tag.getAttribute( '_fckflash' ) )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Flash', FCKLang.FlashProperties, 38 ) ;
				}
			}} ;

		case 'Form' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( FCKSelection.HasAncestorNode('FORM') )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Form', FCKLang.FormProp, 48 ) ;
				}
			}} ;

		case 'Checkbox' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'INPUT' && tag.type == 'checkbox' )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Checkbox', FCKLang.CheckboxProp, 49 ) ;
				}
			}} ;

		case 'Radio' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'INPUT' && tag.type == 'radio' )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Radio', FCKLang.RadioButtonProp, 50 ) ;
				}
			}} ;

		case 'TextField' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'INPUT' && ( tag.type == 'text' || tag.type == 'password' ) )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'TextField', FCKLang.TextFieldProp, 51 ) ;
				}
			}} ;

		case 'HiddenField' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'INPUT' && tag.type == 'hidden' )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'HiddenField', FCKLang.HiddenFieldProp, 56 ) ;
				}
			}} ;

		case 'ImageButton' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'INPUT' && tag.type == 'image' )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'ImageButton', FCKLang.ImageButtonProp, 55 ) ;
				}
			}} ;

		case 'Button' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'INPUT' && ( tag.type == 'button' || tag.type == 'submit' || tag.type == 'reset' ) )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Button', FCKLang.ButtonProp, 54 ) ;
				}
			}} ;

		case 'Select' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'SELECT' )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Select', FCKLang.SelectionFieldProp, 53 ) ;
				}
			}} ;

		case 'Textarea' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( tagName == 'TEXTAREA' )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'Textarea', FCKLang.TextareaProp, 52 ) ;
				}
			}} ;

		case 'BulletedList' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( FCKSelection.HasAncestorNode('UL') )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'BulletedList', FCKLang.BulletedListProp, 27 ) ;
				}
			}} ;

		case 'NumberedList' :
			return {
			AddItems : function( menu, tag, tagName )
			{
				if ( FCKSelection.HasAncestorNode('OL') )
				{
					menu.AddSeparator() ;
					menu.AddItem( 'NumberedList', FCKLang.NumberedListProp, 26 ) ;
				}
			}} ;
	}
}

function FCK_ContextMenu_OnBeforeOpen()
{
	// Update the UI.
	FCK.Events.FireEvent( "OnSelectionChange" ) ;

  	// Get the actual selected tag (if any).
	var oTag, sTagName ;
	
	if ( oTag = FCKSelection.GetSelectedElement() )
		sTagName = oTag.tagName ;

	// Cleanup the current menu items.
	var oMenu = FCK.ContextMenu._InnerContextMenu ;
	oMenu.RemoveAllItems() ;

	// Loop through the listeners.
	var aListeners = FCK.ContextMenu.Listeners ;
	for ( var i = 0 ; i < aListeners.length ; i++ )
		aListeners[i].AddItems( oMenu, oTag, sTagName ) ;
}

function FCK_ContextMenu_OnItemClick( item )
{
	FCK.Focus() ;
	FCKCommands.GetCommand( item.Name ).Execute() ;
}