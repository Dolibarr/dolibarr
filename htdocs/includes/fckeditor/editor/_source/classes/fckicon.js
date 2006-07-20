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
 * File Name: fckicon.js
 * 	FCKIcon Class: renders an icon from a single image, a strip or even a
 * 	spacer.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKIcon = function( iconPathOrStripInfoArray )
{
	var sTypeOf = iconPathOrStripInfoArray ? typeof( iconPathOrStripInfoArray ) : 'undefined' ;
	switch ( sTypeOf )
	{
		case 'number' :
			this.Path = FCKConfig.SkinPath + 'fck_strip.gif' ;
			this.Size = 16 ;
			this.Position = iconPathOrStripInfoArray ;
			break ;
		
		case 'undefined' :
			this.Path = FCK_SPACER_PATH ;
			break ;
		
		case 'string' :
			this.Path = iconPathOrStripInfoArray ;
			break ;
		
		default :
			// It is an array in the format [ StripFilePath, IconSize, IconPosition ]
			this.Path		= iconPathOrStripInfoArray[0] ;
			this.Size		= iconPathOrStripInfoArray[1] ;
			this.Position	= iconPathOrStripInfoArray[2] ;
	}
}

FCKIcon.prototype.CreateIconElement = function( document )
{
	var eIcon ;
	
	if ( this.Position )		// It is using an icons strip image.
	{
		var sPos = '-' + ( ( this.Position - 1 ) * this.Size ) + 'px' ;
	
		if ( FCKBrowserInfo.IsIE )
		{
			// <div class="TB_Button_Image"><img src="strip.gif" style="top:-16px"></div>
			
			eIcon = document.createElement( 'DIV' ) ;
			
			var eIconImage = eIcon.appendChild( document.createElement( 'IMG' ) ) ;
			eIconImage.src = this.Path ;
			eIconImage.style.top = sPos ;
		}
		else
		{
			// <img class="TB_Button_Image" src="spacer.gif" style="background-position: 0px -16px;background-image: url(strip.gif);">
			
			eIcon = document.createElement( 'IMG' ) ;
			eIcon.src = FCK_SPACER_PATH ;
			eIcon.style.backgroundPosition	= '0px ' + sPos ;
			eIcon.style.backgroundImage		= 'url(' + this.Path + ')' ;
		}
	}
	else					// It is using a single icon image.
	{
		// <img class="TB_Button_Image" src="smiley.gif">
		eIcon = document.createElement( 'IMG' ) ;
		eIcon.src = this.Path ? this.Path : FCK_SPACER_PATH ;
	}
	
	eIcon.className = 'TB_Button_Image' ;

	return eIcon ;
}