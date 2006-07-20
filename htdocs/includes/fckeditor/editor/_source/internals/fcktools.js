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
 * File Name: fcktools.js
 * 	Utility functions.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKTools = new Object() ;

/**
 * Gets the value of the hidden INPUT element that is associated to the editor.
 * This element has its ID set to the editor's instance name so the user refers
 * to the instance name when getting the posted data.
 */
FCKTools.GetLinkedFieldValue = function()
{
	return FCK.LinkedField.value ;
}

/**
 * Attachs a function call to the submit event of the linked field form. This
 * function us generally used to update the linked field value before
 * submitting the form.
 */
FCKTools.AttachToLinkedFieldFormSubmit = function( functionPointer )
{
	// Gets the linked field form
	var oForm = FCK.LinkedField.form ;
	
	// Return now if no form is available
	if (!oForm) return ;

	// Attaches the functionPointer call to the onsubmit event
	if ( FCKBrowserInfo.IsIE )
		oForm.attachEvent( "onsubmit", functionPointer ) ;
	else
		oForm.addEventListener( 'submit', functionPointer, false ) ;
	
	//**
	// Attaches the functionPointer call to the submit method 
	// This is done because IE doesn't fire onsubmit when the submit method is called
	// BEGIN --
	
	// Creates a Array in the form object that will hold all Attached function call
	// (in the case there are more than one editor in the same page)
	if (! oForm.updateFCKeditor) oForm.updateFCKeditor = new Array() ;
	
	// Adds the function pointer to the array of functions to call when "submit" is called
	oForm.updateFCKeditor[oForm.updateFCKeditor.length] = functionPointer ;

	// Switches the original submit method with a new one that first call all functions
	// on the above array and the call the original submit
	// IE sees it oForm.submit function as an 'object'.
	if (! oForm.originalSubmit && ( typeof( oForm.submit ) == 'function' || ( !oForm.submit.tagName && !oForm.submit.length ) ) )
	{
		// Creates a copy of the original submit
		oForm.originalSubmit = oForm.submit ;
		
		// Creates our replacement for the submit
		oForm.submit = FCKTools_SubmitReplacer ;
	}
	// END --
}

function FCKTools_SubmitReplacer()
{
	if (this.updateFCKeditor)
	{
		// Calls all functions in the functions array
		for (var i = 0 ; i < this.updateFCKeditor.length ; i++)
			this.updateFCKeditor[i]() ;
	}
	// Calls the original "submit"
	this.originalSubmit() ;
}

// Get the window object where the element is placed in.
FCKTools.GetElementWindow = function( element )
{
	return FCKTools.GetDocumentWindow( element.ownerDocument ) ;
}

FCKTools.GetDocumentWindow = function( doc )
{
	// With Safari, there is not way to retrieve the window from the document, so we must fix it.
	if ( FCKBrowserInfo.IsSafari && !doc.parentWindow )
		FCKTools.FixDocumentParentWindow( window.top ) ;
	
	return doc.parentWindow || doc.defaultView ;
}

FCKTools.GetElementPosition = function( el, relativeWindow )
{
	// Initializes the Coordinates object that will be returned by the function.
	var c = { X:0, Y:0 } ;
	
	var oWindow = relativeWindow || window ;

	// Loop throw the offset chain.
	while ( el )
	{
		c.X += el.offsetLeft - el.scrollLeft ;
		c.Y += el.offsetTop - el.scrollTop  ;

		if ( el.offsetParent == null )
		{
			var oOwnerWindow = FCKTools.GetElementWindow( el ) ;
			
			if ( oOwnerWindow != oWindow )
				el = oOwnerWindow.frameElement ;
			else
			{
				c.X += el.scrollLeft ;
				c.Y += el.scrollTop  ;
				break ;
			}
		}
		else
			el = el.offsetParent ;
	}

	// Return the Coordinates object
	return c ;
}

/*
	This is a Safari specific function that fix the reference to the parent 
	window from the document object.
*/
FCKTools.FixDocumentParentWindow = function( targetWindow )
{
	targetWindow.document.parentWindow = targetWindow ; 
	
	for ( var i = 0 ; i < targetWindow.frames.length ; i++ )
		FCKTools.FixDocumentParentWindow( targetWindow.frames[i] ) ;
}

FCKTools.GetParentWindow = function( document )
{
	return document.contentWindow ? document.contentWindow : document.parentWindow ;
}

FCKTools.HTMLEncode = function( text )
{
	if ( !text )
		return '' ;

	text = text.replace( /&/g, '&amp;' ) ;
	text = text.replace( /</g, '&lt;' ) ;
	text = text.replace( />/g, '&gt;' ) ;

	return text ;
}

/**
 * Adds an option to a SELECT element.
 */
FCKTools.AddSelectOption = function( selectElement, optionText, optionValue )
{
	var oOption = selectElement.ownerDocument.createElement( "OPTION" ) ;

	oOption.text	= optionText ;
	oOption.value	= optionValue ;	

	selectElement.options.add(oOption) ;

	return oOption ;
}

FCKTools.RunFunction = function( func, thisObject, paramsArray, timerWindow )
{
	if ( func )
		this.SetTimeout( func, 0, thisObject, paramsArray, timerWindow ) ;
}

FCKTools.SetTimeout = function( func, milliseconds, thisObject, paramsArray, timerWindow )
{
	return ( timerWindow || window ).setTimeout( 
		function()
		{
			if ( paramsArray )
				func.apply( thisObject, [].concat( paramsArray ) ) ;
			else
				func.apply( thisObject ) ;
		},
		milliseconds ) ;
}

FCKTools.SetInterval = function( func, milliseconds, thisObject, paramsArray, timerWindow )
{
	return ( timerWindow || window ).setInterval( 
		function()
		{
			func.apply( thisObject, paramsArray || [] ) ;
		},
		milliseconds ) ;
}

FCKTools.ConvertStyleSizeToHtml = function( size )
{
	return size.endsWith( '%' ) ? size : parseInt( size ) ;
}

FCKTools.ConvertHtmlSizeToStyle = function( size )
{
	return size.endsWith( '%' ) ? size : ( size + 'px' ) ;
}

// START iCM MODIFICATIONS
// Amended to accept a list of one or more ascensor tag names
// Amended to check the element itself before working back up through the parent hierarchy
FCKTools.GetElementAscensor = function( element, ascensorTagNames )
{
//	var e = element.parentNode ;
	var e = element ;
	var lstTags = "," + ascensorTagNames.toUpperCase() + "," ;

	while ( e )
	{
		if ( lstTags.indexOf( "," + e.nodeName.toUpperCase() + "," ) != -1 )
			return e ;

		e = e.parentNode ;
	}
	return null ;
}
// END iCM MODIFICATIONS

FCKTools.CreateEventListener = function( func, params )
{
	var f = function()
	{
		var aAllParams = [] ;
		
		for ( var i = 0 ; i < arguments.length ; i++ )
			aAllParams.push( arguments[i] ) ;

		func.apply( this, aAllParams.concat( params ) ) ;
	} 

	return f ;
}