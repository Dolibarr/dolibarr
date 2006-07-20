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
 * File Name: fcknamespace.js
 * 	This file declares the namespace (object holder) where the common editor 
 * 	objects and classes are defined.
 * 	The namespace is located in the page the editor is running on, so it is
 * 	shared by all editor instances.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var NS ;

if ( !( NS = window.parent.__FCKeditorNS ) )
	NS = window.parent.__FCKeditorNS = new Object() ;

