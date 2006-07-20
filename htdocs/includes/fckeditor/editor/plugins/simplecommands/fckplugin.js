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
 * File Name: fckplugin.js
 * 	This plugin register Toolbar items for the combos modifying the style to 
 * 	not show the box.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCKToolbarItems.RegisterItem( 'SourceSimple'	, new FCKToolbarButton( 'Source', FCKLang.Source, null, FCK_TOOLBARITEM_ONLYICON, true, true, 1 ) ) ;
FCKToolbarItems.RegisterItem( 'StyleSimple'		, new FCKToolbarStyleCombo( null, FCK_TOOLBARITEM_ONLYTEXT ) ) ;
FCKToolbarItems.RegisterItem( 'FontNameSimple'	, new FCKToolbarFontsCombo( null, FCK_TOOLBARITEM_ONLYTEXT ) ) ;
FCKToolbarItems.RegisterItem( 'FontSizeSimple'	, new FCKToolbarFontSizeCombo( null, FCK_TOOLBARITEM_ONLYTEXT ) ) ;
FCKToolbarItems.RegisterItem( 'FontFormatSimple', new FCKToolbarFontFormatCombo( null, FCK_TOOLBARITEM_ONLYTEXT ) ) ;
