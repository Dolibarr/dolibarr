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
 * 	This plugin register the required Toolbar items to be able to insert the
 * 	toolbar commands in the toolbar.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
 */

FCKToolbarItems.RegisterItem( 'TableInsertRow'		, new FCKToolbarButton( 'TableInsertRow'	, FCKLang.InsertRow, null, null, null, null, 62 ) ) ;
FCKToolbarItems.RegisterItem( 'TableDeleteRows'		, new FCKToolbarButton( 'TableDeleteRows'	, FCKLang.DeleteRows, null, null, null, null, 63 ) ) ;
FCKToolbarItems.RegisterItem( 'TableInsertColumn'	, new FCKToolbarButton( 'TableInsertColumn'	, FCKLang.InsertColumn, null, null, null, null, 64 ) ) ;
FCKToolbarItems.RegisterItem( 'TableDeleteColumns'	, new FCKToolbarButton( 'TableDeleteColumns', FCKLang.DeleteColumns, null, null, null, null, 65 ) ) ;
FCKToolbarItems.RegisterItem( 'TableInsertCell'		, new FCKToolbarButton( 'TableInsertCell'	, FCKLang.InsertCell, null, null, null, null, 58 ) ) ;
FCKToolbarItems.RegisterItem( 'TableDeleteCells'	, new FCKToolbarButton( 'TableDeleteCells'	, FCKLang.DeleteCells, null, null, null, null, 59 ) ) ;
FCKToolbarItems.RegisterItem( 'TableMergeCells'		, new FCKToolbarButton( 'TableMergeCells'	, FCKLang.MergeCells, null, null, null, null, 60 ) ) ;
FCKToolbarItems.RegisterItem( 'TableSplitCell'		, new FCKToolbarButton( 'TableSplitCell'	, FCKLang.SplitCell, null, null, null, null, 61 ) ) ;