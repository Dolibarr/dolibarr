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
 * File Name: fckpanel.js
 * 	Component that creates floating panels. It is used by many 
 * 	other components, like the toolbar items, context menu, etc...
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */


var FCKPanel = function( parentWindow )
{
	this.IsRTL			= ( FCKLang.Dir == 'rtl' ) ;
	this.IsContextMenu	= false ;
	this._LockCounter	= 0 ;
	
	this._Window = parentWindow || window ;
	
	var oDocument ;
	
	if ( FCKBrowserInfo.IsIE )
	{
		// Create the Popup that will hold the panel.
		this._Popup	= this._Window.createPopup() ;
		oDocument = this.Document = this._Popup.document ;
	}
	else
	{
		var oIFrame = this._IFrame = this._Window.document.createElement('iframe') ; 
		oIFrame.src					= 'javascript:void(0)' ;
		oIFrame.allowTransparency	= true ;
		oIFrame.frameBorder			= '0' ;
		oIFrame.scrolling			= 'no' ;
		oIFrame.style.position		= 'absolute';
		oIFrame.style.zIndex		= FCKConfig.FloatingPanelsZIndex ;
		oIFrame.width = oIFrame.height = 0 ;

		this._Window.document.body.appendChild( oIFrame ) ;
		
		var oIFrameWindow = oIFrame.contentWindow ; 
		
		oDocument = this.Document = oIFrameWindow.document ;

		// Initialize the IFRAME document body.
		oDocument.open() ;
		oDocument.write( '<html><head></head><body style="margin:0px;padding:0px;"><\/body><\/html>' ) ;
		oDocument.close() ;

		FCKTools.AddEventListenerEx( oIFrameWindow, 'focus', FCKPanel_Window_OnFocus, this ) ;
		FCKTools.AddEventListenerEx( oIFrameWindow, 'blur', FCKPanel_Window_OnBlur, this ) ;
	}

	oDocument.dir = FCKLang.Dir ;
	
	oDocument.oncontextmenu = FCKTools.CancelEvent ;


	// Create the main DIV that is used as the panel base.
	this.MainNode = oDocument.body.appendChild( oDocument.createElement('DIV') ) ;

	// The "float" property must be set so Firefox calculates the size correcly.
	this.MainNode.style.cssFloat = this.IsRTL ? 'right' : 'left' ;

	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( this, FCKPanel_Cleanup ) ;
}


FCKPanel.prototype.AppendStyleSheet = function( styleSheet )
{
	FCKTools.AppendStyleSheet( this.Document, styleSheet ) ;
}

FCKPanel.prototype.Preload = function( x, y, relElement )
{
	// The offsetWidth and offsetHeight properties are not available if the 
	// element is not visible. So we must "show" the popup with no size to
	// be able to use that values in the second call (IE only).
	if ( this._Popup )
		this._Popup.show( x, y, 0, 0, relElement ) ;
}

FCKPanel.prototype.Show = function( x, y, relElement, width, height )
{
	if ( this._Popup )
	{
		// The offsetWidth and offsetHeight properties are not available if the 
		// element is not visible. So we must "show" the popup with no size to
		// be able to use that values in the second call.
		this._Popup.show( x, y, 0, 0, relElement ) ;

		// The following lines must be place after the above "show", otherwise it 
		// doesn't has the desired effect.
		this.MainNode.style.width	= width ? width + 'px' : '' ;
		this.MainNode.style.height	= height ? height + 'px' : '' ;
		
		var iMainWidth = this.MainNode.offsetWidth ;

		if ( this.IsRTL )
		{
			if ( this.IsContextMenu )
				x  = x - iMainWidth + 1 ;
			else if ( relElement )
				x  = ( x * -1 ) + relElement.offsetWidth - iMainWidth ;
		}
	
		// Second call: Show the Popup at the specified location, with the correct size.
		this._Popup.show( x, y, iMainWidth, this.MainNode.offsetHeight, relElement ) ;
		
		if ( this.OnHide )
		{
			if ( this._Timer )
				CheckPopupOnHide.call( this, true ) ;

			this._Timer = FCKTools.SetInterval( CheckPopupOnHide, 100, this ) ;
		}
	}
	else
	{
		// Do not fire OnBlur while the panel is opened.
		FCKFocusManager.Lock() ;

		if ( this.ParentPanel )
			this.ParentPanel.Lock() ;

		this.MainNode.style.width	= width ? width + 'px' : '' ;
		this.MainNode.style.height	= height ? height + 'px' : '' ;

		var iMainWidth = this.MainNode.offsetWidth ;

		if ( !width )	this._IFrame.width	= 1 ;
		if ( !height )	this._IFrame.height	= 1 ;

		// This is weird... but with Firefox, we must get the offsetWidth before
		// setting the _IFrame size (which returns "0"), and then after that,
		// to return the correct width. Remove the first step and it will not
		// work when the editor is in RTL.
		iMainWidth = this.MainNode.offsetWidth ;

		var oPos = FCKTools.GetElementPosition( ( relElement.nodeType == 9 ? relElement.body : relElement), this._Window ) ;

		if ( this.IsRTL && !this.IsContextMenu )
			x = ( x * -1 ) ;

		x += oPos.X ;
		y += oPos.Y ;

		if ( this.IsRTL )
		{
			if ( this.IsContextMenu )
				x  = x - iMainWidth + 1 ;
			else if ( relElement )
				x  = x + relElement.offsetWidth - iMainWidth ;
		}
		else
		{
			var oViewPaneSize = FCKTools.GetViewPaneSize( this._Window ) ;
			var oScrollPosition = FCKTools.GetScrollPosition( this._Window ) ;
			
			var iViewPaneHeight	= oViewPaneSize.Height + oScrollPosition.Y ;
			var iViewPaneWidth	= oViewPaneSize.Width + oScrollPosition.X ;

			if ( ( x + iMainWidth ) > iViewPaneWidth )
				x -= x + iMainWidth - iViewPaneWidth ;

			if ( ( y + this.MainNode.offsetHeight ) > iViewPaneHeight )
				y -= y + this.MainNode.offsetHeight - iViewPaneHeight ;
		}
		
		if ( x < 0 )
			 x = 0 ;

		// Set the context menu DIV in the specified location.
		this._IFrame.style.left	= x + 'px' ;
		this._IFrame.style.top	= y + 'px' ;
		
		var iWidth	= iMainWidth ;
		var iHeight	= this.MainNode.offsetHeight ;
		
		this._IFrame.width	= iWidth ;
		this._IFrame.height = iHeight ;

		// Move the focus to the IFRAME so we catch the "onblur".
		this._IFrame.contentWindow.focus() ;
	}

	this._IsOpened = true ;

	FCKTools.RunFunction( this.OnShow, this ) ;
}

FCKPanel.prototype.Hide = function( ignoreOnHide )
{
	if ( this._Popup )
		this._Popup.hide() ;
	else
	{
		if ( !this._IsOpened )
			return ;
		
		// Enable the editor to fire the "OnBlur".
		FCKFocusManager.Unlock() ;

		// It is better to set the sizes to 0, otherwise Firefox would have 
		// rendering problems.
		this._IFrame.width = this._IFrame.height = 0 ;

		this._IsOpened = false ;
		
		if ( this.ParentPanel )
			this.ParentPanel.Unlock() ;

		if ( !ignoreOnHide )
			FCKTools.RunFunction( this.OnHide, this ) ;
	}
}

FCKPanel.prototype.CheckIsOpened = function()
{
	if ( this._Popup )
		return this._Popup.isOpen ;
	else
		return this._IsOpened ;
}

FCKPanel.prototype.CreateChildPanel = function()
{
	var oWindow = this._Popup ? FCKTools.GetParentWindow( this.Document ) : this._Window ;

	var oChildPanel = new FCKPanel( oWindow, true ) ;
	oChildPanel.ParentPanel = this ;
	
	return oChildPanel ;
}

FCKPanel.prototype.Lock = function()
{
	this._LockCounter++ ;
}

FCKPanel.prototype.Unlock = function()
{
	if ( --this._LockCounter == 0 && !this.HasFocus )
		this.Hide() ;
}

/* Events */

function FCKPanel_Window_OnFocus( e, panel )
{
	panel.HasFocus = true ;
}

function FCKPanel_Window_OnBlur( e, panel )
{
	panel.HasFocus = false ;
	
	if ( panel._LockCounter == 0 )
		FCKTools.RunFunction( panel.Hide, panel ) ;
}

function CheckPopupOnHide( forceHide )
{
	if ( forceHide || !this._Popup.isOpen )
	{
		window.clearInterval( this._Timer ) ;
		this._Timer = null ;
	
		FCKTools.RunFunction( this.OnHide, this ) ;
	}
}

function FCKPanel_Cleanup()
{
	this._Popup = null ;
	this._Window = null ;
	this.Document = null ;
	this.MainNode = null ;
}