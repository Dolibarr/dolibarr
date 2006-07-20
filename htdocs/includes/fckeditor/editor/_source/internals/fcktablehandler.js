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
 * File Name: fcktablehandler.js
 * 	Manage table operations.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKTableHandler = new Object() ;

FCKTableHandler.InsertRow = function()
{
	// Get the row where the selection is placed in.	
	var oRow = FCKSelection.MoveToAncestorNode("TR") ;
	if ( !oRow ) return ;

	// Create a clone of the row.
	var oNewRow = oRow.cloneNode( true ) ;

	// Insert the new row (copy) before of it.
	oRow.parentNode.insertBefore( oNewRow, oRow ) ;

	// Clean the row (it seems that the new row has been added after it).
	FCKTableHandler.ClearRow( oRow ) ;
}

FCKTableHandler.DeleteRows = function( row )
{
	// If no row has been passed as a parameer,
	// then get the row where the selection is placed in.	
	if ( !row )
		row = FCKSelection.MoveToAncestorNode("TR") ;
	if ( !row ) return ;

	// Get the row's table.	
	var oTable = FCKTools.GetElementAscensor( row, 'TABLE' ) ;

	// If just one row is available then delete the entire table.
	if ( oTable.rows.length == 1 ) 
	{
		FCKTableHandler.DeleteTable( oTable ) ;
		return ;
	}

	// Delete the row.
	row.parentNode.removeChild( row ) ;
}

FCKTableHandler.DeleteTable = function( table )
{
	// If no table has been passed as a parameer,
	// then get the table where the selection is placed in.	
	if ( !table )
	{
		var table = FCKSelection.GetSelectedElement() ;
		if ( !table || table.tagName != 'TABLE' )
			table = FCKSelection.MoveToAncestorNode("TABLE") ;
	}
	if ( !table ) return ;

	// Delete the table.
	FCKSelection.SelectNode( table ) ;
	FCKSelection.Collapse();
	table.parentNode.removeChild( table ) ;
}

FCKTableHandler.InsertColumn = function()
{
	// Get the cell where the selection is placed in.
	var oCell = FCKSelection.MoveToAncestorNode("TD") ;
	if ( !oCell )
	    oCell =  FCKSelection.MoveToAncestorNode("TH") ;

	if ( !oCell ) return ;
	
	// Get the cell's table.
	var oTable = FCKTools.GetElementAscensor( oCell, 'TABLE' ) ;

	// Get the index of the column to be created (based on the cell).
	var iIndex = oCell.cellIndex + 1 ;

	// Loop throw all rows available in the table.
	for ( var i = 0 ; i < oTable.rows.length ; i++ )
	{
		// Get the row.
		var oRow = oTable.rows[i] ;
	
		// If the row doens't have enought cells, ignore it.
		if ( oRow.cells.length < iIndex )
			continue ;
		
		oCell = oRow.cells[iIndex-1].cloneNode(false) ;
		
		if ( FCKBrowserInfo.IsGecko )
			oCell.innerHTML = GECKO_BOGUS ;
		
		// Get the cell that is placed in the new cell place.
		var oBaseCell = oRow.cells[iIndex] ;

		// If the cell is available (we are not in the last cell of the row).
		if ( oBaseCell )
			oRow.insertBefore( oCell, oBaseCell ) ;	// Insert the new cell just before of it.
		else
			oRow.appendChild( oCell ) ;				// Append the cell at the end of the row.
	}
}

FCKTableHandler.DeleteColumns = function()
{
	// Get the cell where the selection is placed in.
	var oCell = FCKSelection.MoveToAncestorNode('TD') || FCKSelection.MoveToAncestorNode('TH') ;

	if ( !oCell ) return ;
	
	// Get the cell's table.	
	var oTable = FCKTools.GetElementAscensor( oCell, 'TABLE' ) ;

	// Get the cell index.
	var iIndex = oCell.cellIndex ;

	// Loop throw all rows (from down to up, because it's possible that some
	// rows will be deleted).
	for ( var i = oTable.rows.length - 1 ; i >= 0 ; i-- )
	{
		// Get the row.
		var oRow = oTable.rows[i] ;
		
		// If the cell to be removed is the first one and the row has just one cell.
		if ( iIndex == 0 && oRow.cells.length == 1 )
		{
			// Remove the entire row.
			FCKTableHandler.DeleteRows( oRow ) ;
			continue ;
		}
		
		// If the cell to be removed exists the delete it.
		if ( oRow.cells[iIndex] )
			oRow.removeChild( oRow.cells[iIndex] ) ;
	}
}

FCKTableHandler.InsertCell = function( cell )
{
	// Get the cell where the selection is placed in.
	var oCell = cell ? cell : FCKSelection.MoveToAncestorNode("TD") ;
	if ( !oCell ) return ;

	// Create the new cell element to be added.
	var oNewCell = FCK.EditorDocument.createElement("TD");
	if ( FCKBrowserInfo.IsGecko )
		oNewCell.innerHTML = GECKO_BOGUS ;
//	oNewCell.innerHTML = "&nbsp;" ;

	// If it is the last cell in the row.
	if ( oCell.cellIndex == oCell.parentNode.cells.length - 1 )
	{
		// Add the new cell at the end of the row.
		oCell.parentNode.appendChild( oNewCell ) ;
	}
	else
	{
		// Add the new cell before the next cell (after the active one).
		oCell.parentNode.insertBefore( oNewCell, oCell.nextSibling ) ;
	}
	
	return oNewCell ;
}

FCKTableHandler.DeleteCell = function( cell )
{
	// If this is the last cell in the row.
	if ( cell.parentNode.cells.length == 1 )
	{
		// Delete the entire row.
		FCKTableHandler.DeleteRows( FCKTools.GetElementAscensor( cell, 'TR' ) ) ;
		return ;
	}

	// Delete the cell from the row.
	cell.parentNode.removeChild( cell ) ;
}

FCKTableHandler.DeleteCells = function()
{
	var aCells = FCKTableHandler.GetSelectedCells() ;
	
	for ( var i = aCells.length - 1 ; i >= 0  ; i-- )
	{
		FCKTableHandler.DeleteCell( aCells[i] ) ;
	}
}

FCKTableHandler.MergeCells = function()
{
	// Get all selected cells.
	var aCells = FCKTableHandler.GetSelectedCells() ;
	
	// At least 2 cells must be selected.
	if ( aCells.length < 2 )
		return ;
		
	// The merge can occour only if the selected cells are from the same row.
	if ( aCells[0].parentNode != aCells[aCells.length-1].parentNode )
		return ;

	// Calculate the new colSpan for the first cell.
	var iColSpan = isNaN( aCells[0].colSpan ) ? 1 : aCells[0].colSpan ;

	var sHtml = '' ;
	var oCellsContents = FCK.EditorDocument.createDocumentFragment() ;
	
	for ( var i = aCells.length - 1 ; i >= 0 ; i-- )
	{
		var eCell = aCells[i] ;
		
		// Move its contents to the document fragment.
		for ( var c = eCell.childNodes.length - 1 ; c >= 0 ; c-- )
		{
			var eChild = eCell.removeChild( eCell.childNodes[c] ) ;
	
			if ( ( eChild.hasAttribute && eChild.hasAttribute('_moz_editor_bogus_node') ) || ( eChild.getAttribute && eChild.getAttribute( 'type', 2 ) == '_moz' ) )
				continue ;
			
				oCellsContents.insertBefore( eChild, oCellsContents.firstChild ) ;
		}
		
		if ( i > 0 )
		{
			// Accumulate the colspan of the cell.
			iColSpan += isNaN( eCell.colSpan ) ? 1 : eCell.colSpan ;

			// Delete the cell.
			FCKTableHandler.DeleteCell( eCell ) ;
		}
	}
	
	// Set the innerHTML of the remaining cell (the first one).
	aCells[0].colSpan = iColSpan ;
	
	if ( FCKBrowserInfo.IsGecko && oCellsContents.childNodes.length == 0 )
		aCells[0].innerHTML = GECKO_BOGUS ;
	else
		aCells[0].appendChild( oCellsContents ) ;
}

FCKTableHandler.SplitCell = function()
{
	// Check that just one cell is selected, otherwise return.
	var aCells = FCKTableHandler.GetSelectedCells() ;
	if ( aCells.length != 1 )
		return ;
	
	var aMap = this._CreateTableMap( aCells[0].parentNode.parentNode ) ;
	var iCellIndex = FCKTableHandler._GetCellIndexSpan( aMap, aCells[0].parentNode.rowIndex , aCells[0] ) ;
		
	var aCollCells = this._GetCollumnCells( aMap, iCellIndex ) ;
	
	for ( var i = 0 ; i < aCollCells.length ; i++ )
	{
		if ( aCollCells[i] == aCells[0] )
		{
			var oNewCell = this.InsertCell( aCells[0] ) ;
			if ( !isNaN( aCells[0].rowSpan ) && aCells[0].rowSpan > 1 )
				oNewCell.rowSpan = aCells[0].rowSpan ;
		}
		else
		{
			if ( isNaN( aCollCells[i].colSpan ) )
				aCollCells[i].colSpan = 2 ;
			else
				aCollCells[i].colSpan += 1 ;
		}
	}
}

// Get the cell index from a TableMap.
FCKTableHandler._GetCellIndexSpan = function( tableMap, rowIndex, cell )
{
	if ( tableMap.length < rowIndex + 1 )
		return null ;
	
	var oRow = tableMap[ rowIndex ] ;
	
	for ( var c = 0 ; c < oRow.length ; c++ )
	{
		if ( oRow[c] == cell )
			return c ;
	}
	
	return null ;
}

// Get the cells available in a collumn of a TableMap.
FCKTableHandler._GetCollumnCells = function( tableMap, collumnIndex )
{
	var aCollCells = new Array() ;

	for ( var r = 0 ; r < tableMap.length ; r++ )
	{
		var oCell = tableMap[r][collumnIndex] ;
		if ( oCell && ( aCollCells.length == 0 || aCollCells[ aCollCells.length - 1 ] != oCell ) )
			aCollCells[ aCollCells.length ] = oCell ;
	}
	
	return aCollCells ;
}

// This function is quite hard to explain. It creates a matrix representing all cells in a table.
// The difference here is that the "spanned" cells (colSpan and rowSpan) are duplicated on the matrix
// cells that are "spanned". For example, a row with 3 cells where the second cell has colSpan=2 and rowSpan=3
// will produce a bi-dimensional matrix with the following values (representing the cells):
//		Cell1, Cell2, Cell2, Cell 3
//		Cell4, Cell2, Cell2, Cell 5
FCKTableHandler._CreateTableMap = function( table )
{
	var aRows = table.rows ;
	
	// Row and Collumn counters.
	var r = -1 ;
	
	var aMap = new Array() ;
	
	for ( var i = 0 ; i < aRows.length ; i++ )
	{
		r++ ;
		if ( !aMap[r] )
			aMap[r] = new Array() ;
		
		var c = -1 ;
		
		for ( var j = 0 ; j < aRows[i].cells.length ; j++ )
		{
			var oCell = aRows[i].cells[j] ;
		
			c++ ;
			while ( aMap[r][c] )
				c++ ;
			
			var iColSpan = isNaN( oCell.colSpan ) ? 1 : oCell.colSpan ;
			var iRowSpan = isNaN( oCell.rowSpan ) ? 1 : oCell.rowSpan ;

			for ( var rs = 0 ; rs < iRowSpan ; rs++ )
			{
				if ( !aMap[r + rs] )
					aMap[r + rs] = new Array() ;
					
				for ( var cs = 0 ; cs < iColSpan ; cs++ )
				{
					aMap[r + rs][c + cs] = aRows[i].cells[j] ;
				}
			}
			
			c += iColSpan - 1 ;
		}
	}
	return aMap ;
}

FCKTableHandler.ClearRow = function( tr )
{
	// Get the array of row's cells.
	var aCells = tr.cells ;

	// Replace the contents of each cell with "nothing".
	for ( var i = 0 ; i < aCells.length ; i++ ) 
	{
		if ( FCKBrowserInfo.IsGecko )
			aCells[i].innerHTML = GECKO_BOGUS ;
		else
			aCells[i].innerHTML = '' ;
	}
}
