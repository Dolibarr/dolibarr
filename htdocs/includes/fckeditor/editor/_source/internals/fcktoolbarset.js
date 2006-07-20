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
 * File Name: fcktoolbarset.js
 * 	Defines the FCKToolbarSet object that is used to load and draw the 
 * 	toolbar.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

function FCKToolbarSet_Create( overhideLocation )
{
	var oToolbarSet ;
	
	var sLocation = overhideLocation || FCKConfig.ToolbarLocation ;
	switch ( sLocation )
	{
		case 'In' :
				document.getElementById( 'xToolbarRow' ).style.display = '' ;
				oToolbarSet = new FCKToolbarSet( document ) ;
			break ;
			
//		case 'OutTop' :
			// Not supported.
			
		default :
			FCK.Events.AttachEvent( 'OnBlur', FCK_OnBlur ) ;
			FCK.Events.AttachEvent( 'OnFocus', FCK_OnFocus ) ;

			var eToolbarTarget ;
			
			// Out:[TargetWindow]([TargetId])
			var oOutMatch = sLocation.match( /^Out:(.+)\((\w+)\)$/ ) ;
			if ( oOutMatch )
			{
				eToolbarTarget = eval( 'parent.' + oOutMatch[1] ).document.getElementById( oOutMatch[2] ) ;
			}
			else
			{
				// Out:[TargetId]
				oOutMatch = sLocation.match( /^Out:(\w+)$/ ) ;
				if ( oOutMatch )
					eToolbarTarget = parent.document.getElementById( oOutMatch[1] ) ;
			}
			
			if ( !eToolbarTarget )
			{
				alert( 'Invalid value for "ToolbarLocation"' ) ;
				return this._Init( 'In' ) ;
			}
			
			// If it is a shared toolbar, it may be already available in the target element.
			if ( oToolbarSet = eToolbarTarget.__FCKToolbarSet )
				break ;

			// Create the IFRAME that will hold the toolbar inside the target element.
			var eToolbarIFrame = eToolbarTarget.ownerDocument.createElement( 'IFRAME' ) ;
			eToolbarIFrame.frameBorder = 0 ;
			eToolbarIFrame.width = '100%' ;
			eToolbarIFrame.height = '10' ;
			eToolbarTarget.appendChild( eToolbarIFrame ) ;
			eToolbarIFrame.unselectable = 'on' ;
			
			// Write the basic HTML for the toolbar (copy from the editor main page).
			var eTargetDocument = eToolbarIFrame.contentWindow.document ;
			eTargetDocument.open() ;
			eTargetDocument.write( '<html><head><script type="text/javascript"> window.onload = window.onresize = function() { window.frameElement.height = document.body.scrollHeight ; } </script></head><body style="overflow: hidden">' + document.getElementById( 'xToolbarSpace' ).innerHTML + '</body></html>' ) ;
			eTargetDocument.close() ;
			
			eTargetDocument.oncontextmenu = FCKTools.CancelEvent ;

			// Load external resources (must be done here, otherwise Firefox will not
			// have the document DOM ready to be used right away.
			FCKTools.AppendStyleSheet( eTargetDocument, FCKConfig.SkinPath + 'fck_editor.css' ) ;
			
			oToolbarSet = eToolbarTarget.__FCKToolbarSet = new FCKToolbarSet( eTargetDocument ) ;
			oToolbarSet._IFrame = eToolbarIFrame ;

			if ( FCK.IECleanup )
				FCK.IECleanup.AddItem( eToolbarTarget, FCKToolbarSet_Target_Cleanup ) ;
	}
	
	oToolbarSet.CurrentInstance = FCK ;

	FCK.AttachToOnSelectionChange( oToolbarSet.RefreshItemsState ) ;

	return oToolbarSet ;
}

function FCK_OnBlur( editorInstance )
{
	var eToolbarSet = editorInstance.ToolbarSet ;
	
	if ( eToolbarSet.CurrentInstance == editorInstance )
	{
//		var eIFrame = eToolbarSet._IFrame ;
//		if ( eIFrame.ownerDocument.activeElement != eIFrame )
		eToolbarSet.Disable() ;
	}
}

function FCK_OnFocus( editorInstance )
{
	var oToolbarset = editorInstance.ToolbarSet ;
	var oInstance = editorInstance || FCK ;
	
	// Unregister the toolbar window from the current instance.
	oToolbarset.CurrentInstance.FocusManager.RemoveWindow( oToolbarset._IFrame.contentWindow ) ;
	
	// Set the new current instance.
	oToolbarset.CurrentInstance = oInstance ;
	
	// Register the toolbar window in the current instance.
	oInstance.FocusManager.AddWindow( oToolbarset._IFrame.contentWindow, true ) ;

	oToolbarset.Enable() ;
}

function FCKToolbarSet_Cleanup()
{
	this._TargetElement = null ;
	this._IFrame = null ;
}

function FCKToolbarSet_Target_Cleanup()
{
	this.__FCKToolbarSet = null ;
}

var FCKToolbarSet = function( targetDocument )
{
	this._Document = targetDocument ; 

	// Get the element that will hold the elements structure.
	this._TargetElement	= targetDocument.getElementById( 'xToolbar' ) ;
	
	// Setup the expand and collapse handlers.
	var eExpandHandle	= targetDocument.getElementById( 'xExpandHandle' ) ;
	var eCollapseHandle	= targetDocument.getElementById( 'xCollapseHandle' ) ;

	eExpandHandle.title		= FCKLang.ToolbarExpand ;
	eExpandHandle.onclick	= FCKToolbarSet_Expand_OnClick ;
	
	eCollapseHandle.title	= FCKLang.ToolbarCollapse ;
	eCollapseHandle.onclick	= FCKToolbarSet_Collapse_OnClick ;

	// Set the toolbar state at startup.
	if ( !FCKConfig.ToolbarCanCollapse || FCKConfig.ToolbarStartExpanded )
		this.Expand() ;
	else
		this.Collapse() ;

	// Enable/disable the collapse handler
	eCollapseHandle.style.display = FCKConfig.ToolbarCanCollapse ? '' : 'none' ;

	if ( FCKConfig.ToolbarCanCollapse )
		eCollapseHandle.style.display = '' ;
	else
		targetDocument.getElementById( 'xTBLeftBorder' ).style.display = '' ;
		
	// Set the default properties.
	this.Toolbars = new Array() ;
	this.IsLoaded = false ;

	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( this, FCKToolbarSet_Cleanup ) ;
}

function FCKToolbarSet_Expand_OnClick()
{
	FCK.ToolbarSet.Expand() ;
}

function FCKToolbarSet_Collapse_OnClick()
{
	FCK.ToolbarSet.Collapse() ;
}

FCKToolbarSet.prototype.Expand = function()
{
	this._ChangeVisibility( false ) ;
}

FCKToolbarSet.prototype.Collapse = function()
{
	this._ChangeVisibility( true ) ;
}

FCKToolbarSet.prototype._ChangeVisibility = function( collapse )
{
	this._Document.getElementById( 'xCollapsed' ).style.display = collapse ? '' : 'none' ;
	this._Document.getElementById( 'xExpanded' ).style.display = collapse ? 'none' : '' ;
	
	if ( FCKBrowserInfo.IsGecko )
	{
		// I had to use "setTimeout" because Gecko was not responding in a right
		// way when calling window.onresize() directly.
		FCKTools.RunFunction( window.onresize ) ;
	}
}

FCKToolbarSet.prototype.Load = function( toolbarSetName )
{
	this.Name = toolbarSetName ;

	this.Items = new Array() ;
	
	// Reset the array of toolbat items that are active only on WYSIWYG mode.
	this.ItemsWysiwygOnly = new Array() ;

	// Reset the array of toolbar items that are sensitive to the cursor position.
	this.ItemsContextSensitive = new Array() ;
	
	// Cleanup the target element.
	this._TargetElement.innerHTML = '' ;
	
	var ToolbarSet = FCKConfig.ToolbarSets[toolbarSetName] ;
	
	if ( !ToolbarSet )
	{
		alert( FCKLang.UnknownToolbarSet.replace( /%1/g, toolbarSetName ) ) ;
		return ;
	}
	
	this.Toolbars = new Array() ;
	
	for ( var x = 0 ; x < ToolbarSet.length ; x++ ) 
	{
		var oToolbarItems = ToolbarSet[x] ;
		
		var oToolbar ;
		
		if ( typeof( oToolbarItems ) == 'string' )
		{
			if ( oToolbarItems == '/' )
				oToolbar = new FCKToolbarBreak() ;
		}
		else
		{
			oToolbar = new FCKToolbar() ;
			
			for ( var j = 0 ; j < oToolbarItems.length ; j++ ) 
			{
				var sItem = oToolbarItems[j] ;
				
				if ( sItem == '-')
					oToolbar.AddSeparator() ;
				else
				{
					var oItem = FCKToolbarItems.GetItem( sItem ) ;
					if ( oItem )
					{
						oToolbar.AddItem( oItem ) ;

						this.Items.push( oItem ) ;

						if ( !oItem.SourceView )
							this.ItemsWysiwygOnly.push( oItem ) ;
						
						if ( oItem.ContextSensitive )
							this.ItemsContextSensitive.push( oItem ) ;
					}
				}
			}
			
			// oToolbar.AddTerminator() ;
		}
		
		oToolbar.Create( this._TargetElement ) ;

		this.Toolbars[ this.Toolbars.length ] = oToolbar ;
	}
	
	FCKTools.DisableSelection( this._Document.getElementById( 'xCollapseHandle' ).parentNode ) ;

	if ( FCK.Status != FCK_STATUS_COMPLETE )
		FCK.Events.AttachEvent( 'OnStatusChange', this.RefreshModeState ) ;
	else
		this.RefreshModeState() ;

	this.IsLoaded = true ;
	this.IsEnabled = true ;

	FCKTools.RunFunction( this.OnLoad ) ;
}

FCKToolbarSet.prototype.Enable = function()
{
	if ( this.IsEnabled )
		return ;

	this.IsEnabled = true ;

	var aItems = this.Items ;
	for ( var i = 0 ; i < aItems.length ; i++ )
		aItems[i].RefreshState() ;
}

FCKToolbarSet.prototype.Disable = function()
{
	if ( !this.IsEnabled )
		return ;

	this.IsEnabled = false ;

	var aItems = this.Items ;
	for ( var i = 0 ; i < aItems.length ; i++ )
		aItems[i].Disable() ;
}

FCKToolbarSet.prototype.RefreshModeState = function( editorInstance )
{
	if ( FCK.Status != FCK_STATUS_COMPLETE )
		return ;

	var oToolbarSet = editorInstance ? editorInstance.ToolbarSet : this ;
	var aItems = oToolbarSet.ItemsWysiwygOnly ;
	
	if ( FCK.EditMode == FCK_EDITMODE_WYSIWYG )
	{
		// Enable all buttons that are available on WYSIWYG mode only.
		for ( var i = 0 ; i < aItems.length ; i++ )
			aItems[i].Enable() ;

		// Refresh the buttons state.
		oToolbarSet.RefreshItemsState( editorInstance ) ;
	}
	else
	{
		// Refresh the buttons state.
		oToolbarSet.RefreshItemsState( editorInstance ) ;

		// Disable all buttons that are available on WYSIWYG mode only.
		for ( var i = 0 ; i < aItems.length ; i++ )
			aItems[i].Disable() ;
	}	
}

FCKToolbarSet.prototype.RefreshItemsState = function( editorInstance )
{
	
	var aItems = ( editorInstance ? editorInstance.ToolbarSet : this ).ItemsContextSensitive ;
	
	for ( var i = 0 ; i < aItems.length ; i++ )
		aItems[i].RefreshState() ;
}
