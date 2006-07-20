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
 * File Name: dialogue.js
 * 	Scripts for the fck_universalkey.html page.
 * 
 * File Authors:
 * 		Michel Staelens (michel.staelens@wanadoo.fr)
 * 		Bernadette Cierzniak
 * 		Abdul-Aziz Al-Oraij (top7up@hotmail.com)
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

function afficher(txt)
{
	document.getElementById( 'uni_area' ).value = txt ;
}

function rechercher()
{
	return document.getElementById( 'uni_area' ).value ;
}