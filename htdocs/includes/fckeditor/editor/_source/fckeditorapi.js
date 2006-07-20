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
 * File Name: fckeditorapi.js
 * 	Create the FCKeditorAPI object that is available as a global object in
 * 	the page where the editor is placed in.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKeditorAPI ;

function InitializeAPI()
{
	var oAPI ;
	
	if ( !( oAPI = FCKeditorAPI = window.parent.FCKeditorAPI ) )
	{
		// Make the FCKeditorAPI object available in the parent window.
		oAPI = FCKeditorAPI = window.parent.FCKeditorAPI = new Object() ;
		oAPI.Version		= '2.3' ;
		oAPI.VersionBuild	= '1054' ;
		
		oAPI.__Instances = new Object() ;

		// Function used to get a instance of an existing editor present in the 
		// page.
		oAPI.GetInstance = FCKeditorAPI_GetInstance ;
		
		var oQueue = oAPI._FunctionQueue = new Object() ;
		oQueue.Functions	= new Array() ;
		oQueue.IsRunning	= false ;
		oQueue.Add			= FCKeditorAPI_FunctionQueue_Add ;
		oQueue.StartNext	= FCKeditorAPI_FunctionQueue_StartNext ;
		oQueue.Remove		= FCKeditorAPI_FunctionQueue_Remove ;
	}

	// Add the current instance to the FCKeditorAPI's instances collection.
	oAPI.__Instances[ FCK.Name ] = FCK ;
}

function FCKeditorAPI_GetInstance( instanceName )
{
	return this.__Instances[ instanceName ] ;
}

function FCKeditorAPI_FunctionQueue_Add( functionToAdd )
{
	this.Functions.push( functionToAdd ) ;

	if ( !this.IsRunning )
		this.StartNext() ;
}

function FCKeditorAPI_FunctionQueue_StartNext()
{
	var aQueue = this.Functions ;
	
	if ( aQueue.length > 0 )
	{
		this.IsRunning = true ;
		FCKTools.RunFunction( aQueue[0] ) ;
	}
	else
		this.IsRunning = false ;
}

function FCKeditorAPI_FunctionQueue_Remove( func )
{
	var aQueue = this.Functions ;
	
	var i = 0, fFunc ;
	while( fFunc = aQueue[ i ] )
	{
		if ( fFunc == func )
			aQueue.splice( i,1 ) ;
		i++ ;
	}

	this.StartNext() ;
}