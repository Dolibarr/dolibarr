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
 * File Name: fcktools_ie.js
 * 	Utility functions. (IE version).
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCKTools.CancelEvent = function( e )
{
	return false ;
}

// Appends a CSS file to a document.
FCKTools.AppendStyleSheet = function( documentElement, cssFileUrl )
{
	return documentElement.createStyleSheet( cssFileUrl ).owningElement ;
}

// Removes all attributes and values from the element.
FCKTools.ClearElementAttributes = function( element )
{
	element.clearAttributes() ;
}

FCKTools.GetAllChildrenIds = function( parentElement )
{
	var aIds = new Array() ;
	for ( var i = 0 ; i < parentElement.all.length ; i++ )
	{
		var sId = parentElement.all[i].id ;
		if ( sId && sId.length > 0 )
			aIds[ aIds.length ] = sId ;
	}
	return aIds ;
}

FCKTools.RemoveOuterTags = function( e )
{
	e.insertAdjacentHTML( 'beforeBegin', e.innerHTML ) ;
	e.parentNode.removeChild( e ) ;
}

FCKTools.CreateXmlObject = function( object )
{
	var aObjs ;
	
	switch ( object )
	{
		case 'XmlHttp' :
			aObjs = [ 'MSXML2.XmlHttp', 'Microsoft.XmlHttp' ] ;
			break ;
				
		case 'DOMDocument' :
			aObjs = [ 'MSXML2.DOMDocument', 'Microsoft.XmlDom' ] ;
			break ;
	}

	for ( var i = 0 ; i < 2 ; i++ )
	{
		try { return new ActiveXObject( aObjs[i] ) ; }
		catch (e) 
		{}
	}
	
	if ( FCKLang.NoActiveX )
	{
		alert( FCKLang.NoActiveX ) ;
		FCKLang.NoActiveX = null ;
	}
}

FCKTools.DisableSelection = function( element )
{
	element.unselectable = 'on' ;

	var e, i = 0 ;
	while ( e = element.all[ i++ ] )
	{
		switch ( e.tagName )
		{
			case 'IFRAME' :
			case 'TEXTAREA' :
			case 'INPUT' :
			case 'SELECT' :
				/* Ignore the above tags */
				break ;
			default :
				e.unselectable = 'on' ;
		}
	}
}

FCKTools.GetScrollPosition = function( relativeWindow )
{
	var oDoc = relativeWindow.document ;

	// Try with the doc element.
	var oPos = { X : oDoc.documentElement.scrollLeft, Y : oDoc.documentElement.scrollTop } ;
	
	if ( oPos.X > 0 || oPos.Y > 0 )
		return oPos ;

	// If no scroll, try with the body.
	return { X : oDoc.body.scrollLeft, Y : oDoc.body.scrollTop } ;
}

FCKTools.AddEventListener = function( sourceObject, eventName, listener )
{
	sourceObject.attachEvent( 'on' + eventName, listener ) ;
}

FCKTools.RemoveEventListener = function( sourceObject, eventName, listener )
{
	sourceObject.detachEvent( 'on' + eventName, listener ) ;
}

// Listeners attached with this function cannot be detached.
FCKTools.AddEventListenerEx = function( sourceObject, eventName, listener, paramsArray )
{
	// Ok... this is a closures party, but is the only way to make it clean of memory leaks.
	var o = new Object() ;
	o.Source = sourceObject ;
	o.Params = paramsArray || [] ;	// Memory leak if we have DOM objects here.
	o.Listener = function( ev )
	{
		return listener.apply( o.Source, [ ev ].concat( o.Params ) ) ;
	}
	
	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( null, function() { o.Source = null ; o.Params = null ; } ) ;
	
	sourceObject.attachEvent( 'on' + eventName, o.Listener ) ;

	sourceObject = null ;	// Memory leak cleaner (because of the above closure).
	paramsArray = null ;	// Memory leak cleaner (because of the above closure).
}

// Returns and object with the "Width" and "Height" properties.
FCKTools.GetViewPaneSize = function( win )
{
	var oSizeSource ;
	
	var oDoc = win.document.documentElement ;
	if ( oDoc && oDoc.clientWidth )				// IE6 Strict Mode
		oSizeSource = oDoc ;
	else
		oSizeSource = top.document.body ;		// Other IEs
	
	if ( oSizeSource )
		return { Width : oSizeSource.clientWidth, Height : oSizeSource.clientHeight } ;
	else
		return { Width : 0, Height : 0 } ;
}

FCKTools.SaveStyles = function( element )
{
	var oSavedStyles = new Object() ;
	
	if ( element.className.length > 0 )
	{
		oSavedStyles.Class = element.className ;
		element.className = '' ;
	}

	var sInlineStyle = element.style.cssText ;

	if ( sInlineStyle.length > 0 )
	{
		oSavedStyles.Inline = sInlineStyle ;
		element.style.cssText = '' ;
	}
	
	return oSavedStyles ;
}

FCKTools.RestoreStyles = function( element, savedStyles )
{
	element.className		= savedStyles.Class || '' ;
	element.style.cssText	= savedStyles.Inline || '' ;
}

FCKTools.RegisterDollarFunction = function( targetWindow )
{
	targetWindow.$ = targetWindow.document.getElementById ;
}