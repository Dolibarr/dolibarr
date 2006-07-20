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
 * File Name: fck_1_gecko.js
 * 	This is the first part of the "FCK" object creation. This is the main
 * 	object that represents an editor instance.
 * 	(Gecko specific implementations)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCK.Description = "FCKeditor for Gecko Browsers" ;

FCK.InitializeBehaviors = function()
{
	FCKFocusManager.AddWindow( this.EditorWindow ) ;

	// Handle pasting operations.
	var oOnKeyDown = function( e )
	{

		// START iCM Modifications
		/*
		// Need to amend carriage return key handling so inserts block element tags rather than BR all the time
		if ( e.which == 13 && !e.shiftKey && !e.ctrlKey && !e.altKey && !FCKConfig.UseBROnCarriageReturn && !FCK.Events.FireEvent( "OnEnter" ) )
		{
			e.preventDefault() ;
			e.stopPropagation() ;
		}
		// Amend backspace handling so correctly removes empty block elements i.e. those block elements containing nothing or
		// just the bogus BR node
		if ( e.which == 8 && !e.shiftKey && !e.ctrlKey && !e.altKey && !FCKConfig.UseBROnCarriageReturn && !FCK.Events.FireEvent( "OnBackSpace" ) )
		{
			e.preventDefault() ;
			e.stopPropagation() ;
		}
		*/
		// END iCM Modifications

		var bPrevent ;

		if ( e.ctrlKey && !e.shiftKey && !e.altKey )
		{
			switch ( e.which ) 
			{
				case 66 :	// B
				case 98 :	// b
					FCK.ExecuteNamedCommand( 'bold' ) ; bPrevent = true ;
					break;
				case 105 :	// i
				case 73 :	// I
					FCK.ExecuteNamedCommand( 'italic' ) ; bPrevent = true ;
					break;
				case 117 :	// u
				case 85 :	// U
					FCK.ExecuteNamedCommand( 'underline' ) ; bPrevent = true ;
					break;
				case 86 :	// V
				case 118 :	// v
					bPrevent = ( FCK.Status != FCK_STATUS_COMPLETE || !FCK.Events.FireEvent( "OnPaste" ) ) ;
					break ;
			}
		}
		else if ( e.shiftKey && !e.ctrlKey && !e.altKey && e.keyCode == 45 )	// SHIFT + <INS>
			bPrevent = ( FCK.Status != FCK_STATUS_COMPLETE || !FCK.Events.FireEvent( "OnPaste" ) ) ;
		
		if ( bPrevent ) 
		{
			e.preventDefault() ;
			e.stopPropagation() ;
		}
	}
	this.EditorDocument.addEventListener( 'keypress', oOnKeyDown, true ) ;

	this.ExecOnSelectionChange = function()
	{
		FCK.Events.FireEvent( "OnSelectionChange" ) ;
	}

	this.ExecOnSelectionChangeTimer = function()
	{
		if ( FCK.LastOnChangeTimer )
			window.clearTimeout( FCK.LastOnChangeTimer ) ;

		FCK.LastOnChangeTimer = window.setTimeout( FCK.ExecOnSelectionChange, 100 ) ;
	}

	this.EditorDocument.addEventListener( 'mouseup', this.ExecOnSelectionChange, false ) ;

	// On Gecko, firing the "OnSelectionChange" event on every key press started to be too much
	// slow. So, a timer has been implemented to solve performance issues when tipying to quickly.
	this.EditorDocument.addEventListener( 'keyup', this.ExecOnSelectionChangeTimer, false ) ;

	this._DblClickListener = function( e )
	{
		FCK.OnDoubleClick( e.target ) ;
		e.stopPropagation() ;
	}
	this.EditorDocument.addEventListener( 'dblclick', this._DblClickListener, true ) ;

	// Reset the context menu.
	FCK.ContextMenu._InnerContextMenu.SetMouseClickWindow( FCK.EditorWindow ) ;
	FCK.ContextMenu._InnerContextMenu.AttachToElement( FCK.EditorDocument ) ;
}

FCK.MakeEditable = function()
{
	this.EditingArea.MakeEditable() ;
}

// Disable the context menu in the editor (outside the editing area).
function Document_OnContextMenu( e )
{
	if ( !e.target._FCKShowContextMenu )
		e.preventDefault() ;
}
document.oncontextmenu = Document_OnContextMenu ;