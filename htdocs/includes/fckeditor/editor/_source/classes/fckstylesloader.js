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
 * File Name: fckstylesloader.js
 * 	FCKStylesLoader Class: this class define objects that are responsible
 * 	for loading the styles defined in the XML file.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKStylesLoader = function()
{
	this.Styles = new Object() ;
	this.StyleGroups = new Object() ;
	this.Loaded = false ;
	this.HasObjectElements = false ;
}

FCKStylesLoader.prototype.Load = function( stylesXmlUrl )
{
	// Load the XML file into a FCKXml object.
	var oXml = new FCKXml() ;
	oXml.LoadUrl( stylesXmlUrl ) ;
	
	// Get the "Style" nodes defined in the XML file.
	var aStyleNodes = oXml.SelectNodes( 'Styles/Style' ) ;
	
	// Add each style to our "Styles" collection.
	for ( var i = 0 ; i < aStyleNodes.length ; i++ )
	{
		var sElement = aStyleNodes[i].attributes.getNamedItem('element').value.toUpperCase() ;
	
		// Create the style definition object.
		var oStyleDef = new FCKStyleDef( aStyleNodes[i].attributes.getNamedItem('name').value, sElement ) ;
		
		if ( oStyleDef.IsObjectElement )
			this.HasObjectElements = true ;
		
		// Get the attributes defined for the style (if any).
		var aAttNodes = oXml.SelectNodes( 'Attribute', aStyleNodes[i] ) ;
		
		// Add the attributes to the style definition object.
		for ( var j = 0 ; j < aAttNodes.length ; j++ )
		{
			var sAttName	= aAttNodes[j].attributes.getNamedItem('name').value ;
			var sAttValue	= aAttNodes[j].attributes.getNamedItem('value').value ;

			// IE changes the "style" attribute value when applied to an element
			// so we must get the final resulting value (for comparision issues).
			if ( sAttName.toLowerCase() == 'style' )
			{
				var oTempE = document.createElement( 'SPAN' ) ;
				oTempE.style.cssText = sAttValue ;
				sAttValue = oTempE.style.cssText ;
			}
			
			oStyleDef.AddAttribute( sAttName, sAttValue ) ;
		}

		// Add the style to the "Styles" collection using it's name as the key.
		this.Styles[ oStyleDef.Name ] = oStyleDef ;
		
		// Add the style to the "StyleGroups" collection.
		var aGroup = this.StyleGroups[sElement] ;
		if ( aGroup == null )
		{
			this.StyleGroups[sElement] = new Array() ;
			aGroup = this.StyleGroups[sElement] ;
		}
		aGroup[aGroup.length] = oStyleDef ;
	}
	
	this.Loaded = true ;
}