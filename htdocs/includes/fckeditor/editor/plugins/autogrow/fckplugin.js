/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * 
 * == BEGIN LICENSE ==
 * 
 * Licensed under the terms of any of the following licenses at your
 * choice:
 * 
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 * 
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 * 
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 * 
 * == END LICENSE ==
 * 
 * File Name: fckplugin.js
 * 	Plugin: automatically resizes the editor until a configurable maximun 
 * 	height (FCKConfig.AutoGrowMax), based on its contents.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
 */

var FCKAutoGrow_Min = window.frameElement.offsetHeight ;

function FCKAutoGrow_Check()
{
	var oInnerDoc = FCK.EditorDocument ;

	var iFrameHeight, iInnerHeight ;
	
	if ( FCKBrowserInfo.IsIE )
	{
		iFrameHeight = FCK.EditorWindow.frameElement.offsetHeight ;
		iInnerHeight = oInnerDoc.body.scrollHeight ;
	}
	else
	{
		iFrameHeight = FCK.EditorWindow.innerHeight ;
		iInnerHeight = oInnerDoc.body.offsetHeight ;
	}

	var iDiff = iInnerHeight - iFrameHeight ;

	if ( iDiff != 0 )
	{
		var iMainFrameSize = window.frameElement.offsetHeight ;
		
		if ( iDiff > 0 && iMainFrameSize < FCKConfig.AutoGrowMax )
		{
			iMainFrameSize += iDiff ;
			if ( iMainFrameSize > FCKConfig.AutoGrowMax )
				iMainFrameSize = FCKConfig.AutoGrowMax ;
		}
		else if ( iDiff < 0 && iMainFrameSize > FCKAutoGrow_Min )
		{
			iMainFrameSize += iDiff ;
			if ( iMainFrameSize < FCKAutoGrow_Min )
				iMainFrameSize = FCKAutoGrow_Min ;
		}
		else
			return ;
			
		window.frameElement.height = iMainFrameSize ;
	}
}

FCK.AttachToOnSelectionChange( FCKAutoGrow_Check ) ;

function FCKAutoGrow_SetListeners()
{
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
		return ;

	FCK.EditorWindow.attachEvent( 'onscroll', FCKAutoGrow_Check ) ;
	FCK.EditorDocument.attachEvent( 'onkeyup', FCKAutoGrow_Check ) ;
}

if ( FCKBrowserInfo.IsIE )
{
//	FCKAutoGrow_SetListeners() ;
	FCK.Events.AttachEvent( 'OnAfterSetHTML', FCKAutoGrow_SetListeners ) ;
}

function FCKAutoGrow_CheckEditorStatus( sender, status )
{
	if ( status == FCK_STATUS_COMPLETE )
		FCKAutoGrow_Check() ;
}

FCK.Events.AttachEvent( 'OnStatusChange', FCKAutoGrow_CheckEditorStatus ) ;