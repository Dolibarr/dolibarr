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
 * File Name: fcklanguagemanager.js
 * 	Defines the FCKLanguageManager object that is used for language 
 * 	operations.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */
var FCKLanguageManager = FCK.Language = new Object() ;

FCKLanguageManager.AvailableLanguages = 
{
	'ar'		: 'Arabic',
	'bg'		: 'Bulgarian',
	'bn'		: 'Bengali/Bangla',
	'bs'		: 'Bosnian',
	'ca'		: 'Catalan',
	'cs'		: 'Czech',
	'da'		: 'Danish',
	'de'		: 'German',
	'el'		: 'Greek',
	'en'		: 'English',
	'en-au'		: 'English (Australia)',
	'en-ca'		: 'English (Canadian)',
	'en-uk'		: 'English (United Kingdom)',
	'eo'		: 'Esperanto',
	'es'		: 'Spanish',
	'et'		: 'Estonian',
	'eu'		: 'Basque',
	'fa'		: 'Persian',
	'fi'		: 'Finnish',
	'fo'		: 'Faroese',
	'fr'		: 'French',
	'gl'		: 'Galician',
	'he'		: 'Hebrew',
	'hi'		: 'Hindi',
	'hr'		: 'Croatian',
	'hu'		: 'Hungarian',
	'it'		: 'Italian',
	'ja'		: 'Japanese',
	'km'		: 'Khmer',
	'ko'		: 'Korean',
	'lt'		: 'Lithuanian',
	'lv'		: 'Latvian',
	'mn'		: 'Mongolian',
	'ms'		: 'Malay',
	'nl'		: 'Dutch',
	'no'		: 'Norwegian',
	'pl'		: 'Polish',
	'pt'		: 'Portuguese (Portugal)',
	'pt-br'		: 'Portuguese (Brazil)',
	'ro'		: 'Romanian',
	'ru'		: 'Russian',
	'sk'		: 'Slovak',
	'sl'		: 'Slovenian',
	'sr'		: 'Serbian (Cyrillic)',
	'sr-latn'	: 'Serbian (Latin)',
	'sv'		: 'Swedish',
	'th'		: 'Thai',
	'tr'		: 'Turkish',
	'uk'		: 'Ukrainian',
	'vi'		: 'Vietnamese',
	'zh'		: 'Chinese Traditional',
	'zh-cn'		: 'Chinese Simplified'
}

FCKLanguageManager.GetActiveLanguage = function()
{
	if ( FCKConfig.AutoDetectLanguage )
	{
		var sUserLang ;
		
		// IE accepts "navigator.userLanguage" while Gecko "navigator.language".
		if ( navigator.userLanguage )
			sUserLang = navigator.userLanguage.toLowerCase() ;
		else if ( navigator.language )
			sUserLang = navigator.language.toLowerCase() ;
		else
		{
			// Firefox 1.0 PR has a bug: it doens't support the "language" property.
			return FCKConfig.DefaultLanguage ;
		}
		
		// Some language codes are set in 5 characters, 
		// like "pt-br" for Brasilian Portuguese.
		if ( sUserLang.length >= 5 )
		{
			sUserLang = sUserLang.substr(0,5) ;
			if ( this.AvailableLanguages[sUserLang] ) return sUserLang ;
		}
		
		// If the user's browser is set to, for example, "pt-br" but only the 
		// "pt" language file is available then get that file.
		if ( sUserLang.length >= 2 )
		{
			sUserLang = sUserLang.substr(0,2) ;
			if ( this.AvailableLanguages[sUserLang] ) return sUserLang ;
		}
	}
	
	return this.DefaultLanguage ;
}

FCKLanguageManager.TranslateElements = function( targetDocument, tag, propertyToSet, encode )
{
	var e = targetDocument.getElementsByTagName(tag) ;
	var sKey, s ;
	for ( var i = 0 ; i < e.length ; i++ )
	{
		if ( sKey = e[i].getAttribute( 'fckLang' ) )
		{
			if ( s = FCKLang[ sKey ] )
			{
				if ( encode )
					s = FCKTools.HTMLEncode( s ) ;
				eval( 'e[i].' + propertyToSet + ' = s' ) ;
			}
		}
	}
}

FCKLanguageManager.TranslatePage = function( targetDocument )
{
	this.TranslateElements( targetDocument, 'INPUT', 'value' ) ;
	this.TranslateElements( targetDocument, 'SPAN', 'innerHTML' ) ;
	this.TranslateElements( targetDocument, 'LABEL', 'innerHTML' ) ;
	this.TranslateElements( targetDocument, 'OPTION', 'innerHTML', true ) ;
}

FCKLanguageManager.Initialize = function()
{
	if ( this.AvailableLanguages[ FCKConfig.DefaultLanguage ] )
		this.DefaultLanguage = FCKConfig.DefaultLanguage ;
	else
		this.DefaultLanguage = 'en' ;

	this.ActiveLanguage = new Object() ;
	this.ActiveLanguage.Code = this.GetActiveLanguage() ;
	this.ActiveLanguage.Name = this.AvailableLanguages[ this.ActiveLanguage.Code ] ;
}