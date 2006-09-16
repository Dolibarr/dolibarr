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
 * 	This plugin register the required Toolbar items to be able to insert the
 * 	toolbar commands in the toolbar.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCKToolbarItems.RegisterItem( 'TableInsertRow'		, new FCKToolbarButton( 'TableInsertRow'	, FCKLang.InsertRow, null, null, null, null, 62 ) ) ;
FCKToolbarItems.RegisterItem( 'TableDeleteRows'		, new FCKToolbarButton( 'TableDeleteRows'	, FCKLang.DeleteRows, null, null, null, null, 63 ) ) ;
FCKToolbarItems.RegisterItem( 'TableInsertColumn'	, new FCKToolbarButton( 'TableInsertColumn'	, FCKLang.InsertColumn, null, null, null, null, 64 ) ) ;
FCKToolbarItems.RegisterItem( 'TableDeleteColumns'	, new FCKToolbarButton( 'TableDeleteColumns', FCKLang.DeleteColumns, null, null, null, null, 65 ) ) ;
FCKToolbarItems.RegisterItem( 'TableInsertCell'		, new FCKToolbarButton( 'TableInsertCell'	, FCKLang.InsertCell, null, null, null, null, 58 ) ) ;
FCKToolbarItems.RegisterItem( 'TableDeleteCells'	, new FCKToolbarButton( 'TableDeleteCells'	, FCKLang.DeleteCells, null, null, null, null, 59 ) ) ;
FCKToolbarItems.RegisterItem( 'TableMergeCells'		, new FCKToolbarButton( 'TableMergeCells'	, FCKLang.MergeCells, null, null, null, null, 60 ) ) ;
FCKToolbarItems.RegisterItem( 'TableSplitCell'		, new FCKToolbarButton( 'TableSplitCell'	, FCKLang.SplitCell, null, null, null, null, 61 ) ) ;