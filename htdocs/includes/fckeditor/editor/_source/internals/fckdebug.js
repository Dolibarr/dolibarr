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
 * File Name: fckdebug.js
 * 	Debug window control and operations.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKDebug = new Object() ;

FCKDebug.Output = function( message, color, noParse )
{
	if ( ! FCKConfig.Debug ) return ;
	
	if ( !noParse && message != null && isNaN( message ) )
		message = message.replace(/</g, "&lt;") ;

	if ( !this.DebugWindow || this.DebugWindow.closed )
		this.DebugWindow = window.open( FCKConfig.BasePath + 'fckdebug.html', 'FCKeditorDebug', 'menubar=no,scrollbars=no,resizable=yes,location=no,toolbar=no,width=600,height=500', true ) ;
	
	if ( this.DebugWindow && this.DebugWindow.Output)
	{
		try 
		{
			this.DebugWindow.Output( message, color ) ;
		} 
		catch ( e ) {}	 // Ignore errors
	}
}

FCKDebug.OutputObject = function( anyObject, color )
{
	if ( ! FCKConfig.Debug ) return ;

	var message ;
	
	if ( anyObject != null ) 
	{
		message = 'Properties of: ' + anyObject + '</b><blockquote>' ;
		
		for (var prop in anyObject)
		{
			try 
			{
				var sVal = anyObject[ prop ] ? anyObject[ prop ] + '' : '[null]' ;
				message += '<b>' + prop + '</b> : ' + sVal.replace(/</g, '&lt;') + '<br>' ;
			} 
			catch (e)
			{
				try
				{
					message += '<b>' + prop + '</b> : [' + typeof( anyObject[ prop ] ) + ']<br>' ;
				}
				catch (e)
				{
					message += '<b>' + prop + '</b> : [-error-]<br>' ;
				}
			}
		}

		message += '</blockquote><b>' ; 
	} else
		message = 'OutputObject : Object is "null".' ;
		
	FCKDebug.Output( message, color, true ) ;
}