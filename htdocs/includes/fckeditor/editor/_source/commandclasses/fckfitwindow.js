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
 * File Name: fckfitwindow.js
 * 	Stretch the editor to full window size and back.
 * 
 * File Authors:
 * 		Paul Moers (mail@saulmade.nl)
 * 		Thanks to Christian Fecteau (webmaster@christianfecteau.com)
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKFitWindow = function()
{
	this.Name = 'FitWindow' ;
}

FCKFitWindow.prototype.Execute = function()
{
	var eEditorFrame		= window.frameElement ;
	var eEditorFrameStyle	= eEditorFrame.style ;

	var eMainWindow			= parent ;
	var eDocEl				= eMainWindow.document.documentElement ;
	var eBody				= eMainWindow.document.body ;
	var eBodyStyle			= eBody.style ;

	// No original style properties known? Go fullscreen.
	if ( !this.IsMaximized )
	{
		// Registering an event handler when the window gets resized.
		if( FCKBrowserInfo.IsIE )
			eMainWindow.attachEvent( 'onresize', FCKFitWindow_Resize ) ;
		else
			eMainWindow.addEventListener( 'resize', FCKFitWindow_Resize, true ) ;

		// Save the scrollbars position.
		this._ScrollPos = FCKTools.GetScrollPosition( eMainWindow ) ;
		
		// Save and reset the styles for the entire node tree. They could interfere in the result.
		var eParent = eEditorFrame ;
		while( eParent = eParent.parentNode )
		{
			if ( eParent.nodeType == 1 )
				eParent._fckSavedStyles = FCKTools.SaveStyles( eParent ) ;
		}		

		// Hide IE scrollbars (in strict mode).
		if ( FCKBrowserInfo.IsIE )
		{
			this.documentElementOverflow = eDocEl.style.overflow ;
			eDocEl.style.overflow	= 'hidden' ;
			eBodyStyle.overflow		= 'hidden' ;
		}
		else
		{
			// Hide the scroolbars in Firefox.
			eBodyStyle.overflow = 'hidden' ;
			eBodyStyle.width = '0px' ;
			eBodyStyle.height = '0px' ;
		}
		
		// Save the IFRAME styles.
		this._EditorFrameStyles = FCKTools.SaveStyles( eEditorFrame ) ;
		
		// Resize.
		var oViewPaneSize = FCKTools.GetViewPaneSize( eMainWindow ) ;

		eEditorFrameStyle.position	= "absolute";
		eEditorFrameStyle.zIndex	= FCKConfig.FloatingPanelsZIndex - 1;
		eEditorFrameStyle.left		= "0px";
		eEditorFrameStyle.top		= "0px";
		eEditorFrameStyle.width		= oViewPaneSize.Width + "px";
		eEditorFrameStyle.height	= oViewPaneSize.Height + "px";
		
		// Giving the frame some (huge) borders on his right and bottom
		// side to hide the background that would otherwise show when the
		// editor is in fullsize mode and the window is increased in size
		// not for IE, because IE immediately adapts the editor on resize, 
		// without showing any of the background oddly in firefox, the
		// editor seems not to fill the whole frame, so just setting the
		// background of it to white to cover the page laying behind it anyway.
		if ( !FCKBrowserInfo.IsIE )
		{
			eEditorFrameStyle.borderRight = eEditorFrameStyle.borderBottom = "9999px solid white" ;
			eEditorFrameStyle.backgroundColor		= "white";
		}

		// Scroll to top left.
		eMainWindow.scrollTo(0, 0);

		this.IsMaximized = true ;
	}
	else	// Resize to original size.
	{
		// Remove the event handler of window resizing.
		if( FCKBrowserInfo.IsIE )
			eMainWindow.detachEvent( "onresize", FCKFitWindow_Resize ) ;
		else
			eMainWindow.removeEventListener( "resize", FCKFitWindow_Resize, true ) ;

		// Restore the CSS position for the entire node tree.
		var eParent = eEditorFrame ;
		while( eParent = eParent.parentNode )
		{
			if ( eParent._fckSavedStyles )
			{
				FCKTools.RestoreStyles( eParent, eParent._fckSavedStyles ) ;
				eParent._fckSavedStyles = null ;
			}
		}
		
		// Restore IE scrollbars
		if ( FCKBrowserInfo.IsIE )
			eDocEl.style.overflow = this.documentElementOverflow ;

		// Restore original size
		FCKTools.RestoreStyles( eEditorFrame, this._EditorFrameStyles ) ;
		
		// Restore the window scroll position.
		eMainWindow.scrollTo( this._ScrollPos.X, this._ScrollPos.Y ) ;

		this.IsMaximized = false ;
	}
	
	FCKToolbarItems.GetItem('FitWindow').RefreshState() ;

	// It seams that Firefox restarts the editing area when making this changes.
	// On FF 1.0.x, the area is not anymore editable. On FF 1.5+, the special 
	//configuration, like DisableFFTableHandles and DisableObjectResizing get 
	//lost, so we must reset it. Also, the cursor position and selection are 
	//also lost, even if you comment the following line (MakeEditable).
	// if ( FCKBrowserInfo.IsGecko10 )	// Initially I thought it was a FF 1.0 only problem.
	FCK.EditingArea.MakeEditable() ;
	
	FCK.Focus() ;
}

FCKFitWindow.prototype.GetState = function()
{
	if ( FCKConfig.ToolbarLocation != 'In' )
		return FCK_TRISTATE_DISABLED ;
	else
		return ( this.IsMaximized ? FCK_TRISTATE_ON : FCK_TRISTATE_OFF );
}

function FCKFitWindow_Resize()
{
	var oViewPaneSize = FCKTools.GetViewPaneSize( parent ) ;

	var eEditorFrameStyle = window.frameElement.style ;

	eEditorFrameStyle.width		= oViewPaneSize.Width + 'px' ;
	eEditorFrameStyle.height	= oViewPaneSize.Height + 'px' ;
}
