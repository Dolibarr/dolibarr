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
 * File Name: fcktextcolorcommand.js
 * 	FCKTextColorCommand Class: represents the text color comand. It shows the
 * 	color selection panel.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// FCKTextColorCommand Contructor
//		type: can be 'ForeColor' or 'BackColor'.
var FCKTextColorCommand = function( type )
{
	this.Name = type == 'ForeColor' ? 'TextColor' : 'BGColor' ;
	this.Type = type ;

	var oWindow ;
	
	if ( FCKBrowserInfo.IsIE )
		oWindow = window ;
	else if ( FCK.ToolbarSet._IFrame )
		oWindow = FCKTools.GetElementWindow( FCK.ToolbarSet._IFrame ) ;
	else
		oWindow = window.parent ;

	this._Panel = new FCKPanel( oWindow, true ) ;
	this._Panel.AppendStyleSheet( FCKConfig.SkinPath + 'fck_editor.css' ) ;
	this._Panel.MainNode.className = 'FCK_Panel' ;
	this._CreatePanelBody( this._Panel.Document, this._Panel.MainNode ) ;
	
	FCKTools.DisableSelection( this._Panel.Document.body ) ;
}

FCKTextColorCommand.prototype.Execute = function( panelX, panelY, relElement )
{
	// We must "cache" the actual panel type to be used in the SetColor method.
	FCK._ActiveColorPanelType = this.Type ;

	// Show the Color Panel at the desired position.
	this._Panel.Show( panelX, panelY, relElement ) ;
}

FCKTextColorCommand.prototype.SetColor = function( color )
{
	if ( FCK._ActiveColorPanelType == 'ForeColor' )
		FCK.ExecuteNamedCommand( 'ForeColor', color ) ;
	else if ( FCKBrowserInfo.IsGeckoLike )
	{
		if ( FCKBrowserInfo.IsGecko && !FCKConfig.GeckoUseSPAN )
			FCK.EditorDocument.execCommand( 'useCSS', false, false ) ;
			
		FCK.ExecuteNamedCommand( 'hilitecolor', color ) ;

		if ( FCKBrowserInfo.IsGecko && !FCKConfig.GeckoUseSPAN )
			FCK.EditorDocument.execCommand( 'useCSS', false, true ) ;
	}
	else
		FCK.ExecuteNamedCommand( 'BackColor', color ) ;
	
	// Delete the "cached" active panel type.
	delete FCK._ActiveColorPanelType ;
}

FCKTextColorCommand.prototype.GetState = function()
{
	return FCK_TRISTATE_OFF ;
}

function FCKTextColorCommand_OnMouseOver()	{ this.className='ColorSelected' ; }

function FCKTextColorCommand_OnMouseOut()	{ this.className='ColorDeselected' ; }

function FCKTextColorCommand_OnClick()
{
	this.className = 'ColorDeselected' ;
	this.Command.SetColor( '#' + this.Color ) ;
	this.Command._Panel.Hide() ;
}

function FCKTextColorCommand_AutoOnClick()
{
	this.className = 'ColorDeselected' ;
	this.Command.SetColor( '' ) ;
	this.Command._Panel.Hide() ;
}

function FCKTextColorCommand_MoreOnClick()
{
	this.className = 'ColorDeselected' ;
	this.Command._Panel.Hide() ;
	FCKDialog.OpenDialog( 'FCKDialog_Color', FCKLang.DlgColorTitle, 'dialog/fck_colorselector.html', 400, 330, this.Command.SetColor ) ;
}

FCKTextColorCommand.prototype._CreatePanelBody = function( targetDocument, targetDiv )
{
	function CreateSelectionDiv()
	{
		var oDiv = targetDocument.createElement( "DIV" ) ;
		oDiv.className		= 'ColorDeselected' ;
		oDiv.onmouseover	= FCKTextColorCommand_OnMouseOver ;
		oDiv.onmouseout		= FCKTextColorCommand_OnMouseOut ;
		
		return oDiv ;
	}

	// Create the Table that will hold all colors.
	var oTable = targetDiv.appendChild( targetDocument.createElement( "TABLE" ) ) ;
	oTable.className = 'ForceBaseFont' ;		// Firefox 1.5 Bug.
	oTable.style.tableLayout = 'fixed' ;
	oTable.cellPadding = 0 ;
	oTable.cellSpacing = 0 ;
	oTable.border = 0 ;
	oTable.width = 150 ;

	var oCell = oTable.insertRow(-1).insertCell(-1) ;
	oCell.colSpan = 8 ;

	// Create the Button for the "Automatic" color selection.
	var oDiv = oCell.appendChild( CreateSelectionDiv() ) ;
	oDiv.innerHTML = 
		'<table cellspacing="0" cellpadding="0" width="100%" border="0">\
			<tr>\
				<td><div class="ColorBoxBorder"><div class="ColorBox" style="background-color: #000000"></div></div></td>\
				<td nowrap width="100%" align="center">' + FCKLang.ColorAutomatic + '</td>\
			</tr>\
		</table>' ;

	oDiv.Command = this ;
	oDiv.onclick = FCKTextColorCommand_AutoOnClick ;

	// Create an array of colors based on the configuration file.
	var aColors = FCKConfig.FontColors.toString().split(',') ;

	// Create the colors table based on the array.
	var iCounter = 0 ;
	while ( iCounter < aColors.length )
	{
		var oRow = oTable.insertRow(-1) ;
		
		for ( var i = 0 ; i < 8 && iCounter < aColors.length ; i++, iCounter++ )
		{
			oDiv = oRow.insertCell(-1).appendChild( CreateSelectionDiv() ) ;
			oDiv.Color = aColors[iCounter] ;
			oDiv.innerHTML = '<div class="ColorBoxBorder"><div class="ColorBox" style="background-color: #' + aColors[iCounter] + '"></div></div>' ;

			oDiv.Command = this ;
			oDiv.onclick = FCKTextColorCommand_OnClick ;
		}
	}

	// Create the Row and the Cell for the "More Colors..." button.
	oCell = oTable.insertRow(-1).insertCell(-1) ;
	oCell.colSpan = 8 ;

	oDiv = oCell.appendChild( CreateSelectionDiv() ) ;
	oDiv.innerHTML = '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td nowrap align="center">' + FCKLang.ColorMoreColors + '</td></tr></table>' ;

	oDiv.Command = this ;
	oDiv.onclick = FCKTextColorCommand_MoreOnClick ;
}